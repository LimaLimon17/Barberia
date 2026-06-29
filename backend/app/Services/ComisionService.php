<?php

namespace App\Services;

use App\Models\Reserva;
use App\Models\Venta;
use App\Models\Comision;
use App\Models\Barbero;
use Carbon\Carbon;

class ComisionService
{
    /**
     * Calcula las ganancias (comisiones) de un barbero en un periodo dado.
     * Retorna un arreglo con el detalle y el total.
     */
    public function calcularGanancias(Barbero $barbero, Carbon $fechaInicio, Carbon $fechaFin)
    {
        // 1. Servicios completados
        $reservasCompletadas = Reserva::with(['cliente', 'servicios'])
            ->where('IdBarbero', $barbero->IdBarbero)
            ->where('EstadoReserva', 'Completada')
            ->whereBetween('FechaCita', [$fechaInicio->format('Y-m-d'), $fechaFin->format('Y-m-d')])
            ->get();

        $totalServicios = 0;
        $comisionServicios = 0;
        $detalleServicios = [];

        foreach ($reservasCompletadas as $reserva) {
            $totalServicios += $reserva->CostoTotal;
            $comision = $reserva->CostoTotal * 0.50; // RF18: 50% de servicios
            $comisionServicios += $comision;
            
            $detalleServicios[] = [
                'IdReserva' => $reserva->IdReserva,
                'Fecha' => $reserva->FechaCita->format('Y-m-d') . ' ' . $reserva->HoraInicio,
                'Cliente' => $reserva->cliente ? $reserva->cliente->Nombre . ' ' . $reserva->cliente->Apellido : 'Desconocido',
                'MontoTotal' => $reserva->CostoTotal,
                'Comision' => $comision,
                'Detalle' => $reserva->servicios->pluck('Nombre')->implode(', ')
            ];
        }

        // 2. Clientes ausentes (50% del anticipo)
        $reservasAusentes = Reserva::with(['cliente'])
            ->where('IdBarbero', $barbero->IdBarbero)
            ->where('EstadoReserva', 'Ausente')
            ->whereBetween('FechaCita', [$fechaInicio->format('Y-m-d'), $fechaFin->format('Y-m-d')])
            ->get();

        $totalAusentes = 0;
        $comisionAusentes = 0;
        $detalleAusentes = [];

        foreach ($reservasAusentes as $reserva) {
            $totalAusentes += $reserva->MontoAnticipo;
            $comision = $reserva->MontoAnticipo * 0.50;
            $comisionAusentes += $comision;
            
            $detalleAusentes[] = [
                'IdReserva' => $reserva->IdReserva,
                'Fecha' => $reserva->FechaCita->format('Y-m-d') . ' ' . $reserva->HoraInicio,
                'Cliente' => $reserva->cliente ? $reserva->cliente->Nombre . ' ' . $reserva->cliente->Apellido : 'Desconocido',
                'MontoTotal' => $reserva->MontoAnticipo,
                'Comision' => $comision,
                'Detalle' => 'Anticipo por ausencia'
            ];
        }

        // 3. Ventas de productos
        // Se asume que la fecha en Venta incluye la hora, usamos whereBetween normal.
        $ventas = Venta::with(['detalles.producto', 'cliente'])
            ->where('IdBarbero', $barbero->IdBarbero)
            ->whereBetween('Fecha', [$fechaInicio->startOfDay(), $fechaFin->endOfDay()])
            ->get();

        $totalVentas = 0;
        $comisionVentas = 0;
        $detalleProductos = [];

        foreach ($ventas as $venta) {
            $totalVentas += $venta->MontoTotal;
            $comisionVentaLocal = 0;
            $nombresProductos = [];

            foreach ($venta->detalles as $detalle) {
                // Si la tabla detalle_ventas no tiene un campo ComisionBarbero guardado con su % correspondiente, 
                // se puede calcular usando el % del producto. Aunque DetalleVenta tiene 'ComisionBarbero' precalculado.
                $comisionVentaLocal += $detalle->ComisionBarbero;
                $nombresProductos[] = $detalle->producto ? $detalle->producto->Nombre . ' (x' . $detalle->Cantidad . ')' : 'Producto';
            }
            
            $comisionVentas += $comisionVentaLocal;

            $detalleProductos[] = [
                'IdVenta' => $venta->IdVenta,
                'Fecha' => $venta->Fecha->format('Y-m-d H:i'),
                'Cliente' => $venta->cliente ? $venta->cliente->Nombre . ' ' . $venta->cliente->Apellido : 'Desconocido',
                'MontoTotal' => $venta->MontoTotal,
                'Comision' => $comisionVentaLocal,
                'Detalle' => implode(', ', $nombresProductos)
            ];
        }

        $totalComision = $comisionServicios + $comisionAusentes + $comisionVentas;
        
        // Unir todo y ordenar cronológicamente
        $transacciones = array_merge($detalleServicios, $detalleAusentes, $detalleProductos);
        usort($transacciones, function($a, $b) {
            return strtotime($a['Fecha']) - strtotime($b['Fecha']);
        });

        return [
            'total_servicios' => $totalServicios,
            'comision_servicios' => $comisionServicios,
            'total_ausentes' => $totalAusentes,
            'comision_ausentes' => $comisionAusentes,
            'total_ventas' => $totalVentas,
            'comision_ventas' => $comisionVentas,
            'total_ganado' => $totalComision,
            'detalle' => $transacciones
        ];
    }

    /**
     * Consolida las comisiones para todos los barberos de una semana (Lunes a Domingo)
     */
    public function consolidarSemana(Carbon $fechaInicio, Carbon $fechaFin)
    {
        $barberos = Barbero::where('EstadoA', true)->get();
        $registrosCreados = 0;

        foreach ($barberos as $barbero) {
            // Ya existe consolidacion?
            $yaConsolidado = Comision::where('IdBarbero', $barbero->IdBarbero)
                ->whereBetween('Fecha', [$fechaInicio->startOfDay(), $fechaFin->endOfDay()])
                ->exists();
                
            if ($yaConsolidado) {
                continue;
            }

            $ganancias = $this->calcularGanancias($barbero, $fechaInicio, $fechaFin);

            // Crear registros de comision por cada servicio
            $reservas = Reserva::where('IdBarbero', $barbero->IdBarbero)
                ->whereIn('EstadoReserva', ['Completada', 'Ausente'])
                ->whereBetween('FechaCita', [$fechaInicio->format('Y-m-d'), $fechaFin->format('Y-m-d')])
                ->get();

            foreach ($reservas as $reserva) {
                if ($reserva->EstadoReserva === 'Completada') {
                    Comision::create([
                        'IdBarbero' => $barbero->IdBarbero,
                        'IdReserva' => $reserva->IdReserva,
                        'TipoComision' => 'Servicio',
                        'Fecha' => $reserva->FechaCita,
                        'MontoBase' => $reserva->CostoTotal,
                        'Porcentaje' => 50.00,
                        'MontoComision' => $reserva->CostoTotal * 0.50,
                        'EstadoA' => true,
                        'FechaA' => now(),
                        'UsuarioA' => 'Sistema'
                    ]);
                } else if ($reserva->EstadoReserva === 'Ausente') {
                    Comision::create([
                        'IdBarbero' => $barbero->IdBarbero,
                        'IdReserva' => $reserva->IdReserva,
                        'TipoComision' => 'Ausente',
                        'Fecha' => $reserva->FechaCita,
                        'MontoBase' => $reserva->MontoAnticipo,
                        'Porcentaje' => 50.00,
                        'MontoComision' => $reserva->MontoAnticipo * 0.50,
                        'EstadoA' => true,
                        'FechaA' => now(),
                        'UsuarioA' => 'Sistema'
                    ]);
                }
                $registrosCreados++;
            }

            // Crear registros por ventas
            $ventas = Venta::with('detalles')->where('IdBarbero', $barbero->IdBarbero)
                ->whereBetween('Fecha', [$fechaInicio->startOfDay(), $fechaFin->endOfDay()])
                ->get();

            foreach ($ventas as $venta) {
                $comisionBarbero = $venta->detalles->sum('ComisionBarbero');
                if ($comisionBarbero > 0) {
                    Comision::create([
                        'IdBarbero' => $barbero->IdBarbero,
                        'IdVenta' => $venta->IdVenta,
                        'TipoComision' => 'Producto',
                        'Fecha' => $venta->Fecha,
                        'MontoBase' => $venta->MontoTotal,
                        'Porcentaje' => null, 
                        'MontoComision' => $comisionBarbero,
                        'EstadoA' => true,
                        'FechaA' => now(),
                        'UsuarioA' => 'Sistema'
                    ]);
                    $registrosCreados++;
                }
            }
        }
        
        return $registrosCreados;
    }
}
