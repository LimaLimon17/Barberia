<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reserva;
use App\Models\Venta;
use App\Models\Producto;
use App\Models\DetalleVenta;
use Carbon\Carbon;

class ReporteController extends Controller
{
    /**
     * Reporte de ventas consolidadas (HU-15)
     */
    public function ventas(Request $request)
    {
        $fechaInicio = $request->query('fecha_inicio') ? Carbon::parse($request->query('fecha_inicio'))->startOfDay() : Carbon::now()->startOfWeek();
        $fechaFin = $request->query('fecha_fin') ? Carbon::parse($request->query('fecha_fin'))->endOfDay() : Carbon::now()->endOfWeek();
        $idBarbero = $request->query('id_barbero');

        $queryReservas = Reserva::with(['barbero.usuario', 'cliente', 'servicios', 'ventas.detalles.producto', 'pagos'])
            ->where('EstadoReserva', 'Completada')
            ->whereBetween('FechaCita', [$fechaInicio->format('Y-m-d'), $fechaFin->format('Y-m-d')]);

        $queryVentasSueltas = Venta::with(['barbero.usuario', 'cliente', 'detalles.producto'])
            ->whereNull('IdReserva')
            ->whereBetween('Fecha', [$fechaInicio, $fechaFin]);

        if ($idBarbero) {
            $queryReservas->where('IdBarbero', $idBarbero);
            $queryVentasSueltas->where('IdBarbero', $idBarbero);
        }

        $reservas = $queryReservas->get();
        $ventasSueltas = $queryVentasSueltas->get();

        $transacciones = [];
        $ingresoTotal = 0;
        $cantidadServicios = 0;
        $cantidadProductos = 0;

        foreach ($reservas as $reserva) {
            $servicios = $reserva->servicios;
            $productos = collect();
            $montoProductos = 0;
            
            foreach ($reserva->ventas as $venta) {
                foreach ($venta->detalles as $detalle) {
                    $productos->push($detalle);
                    $montoProductos += ($detalle->PrecioUnitario * $detalle->Cantidad);
                    $cantidadProductos += $detalle->Cantidad;
                }
            }
            
            $montoTotal = $reserva->CostoTotal + $montoProductos;
            $ingresoTotal += $montoTotal;
            $cantidadServicios += $servicios->count();

            $transacciones[] = [
                'referencia' => 'R-' . $reserva->IdReserva,
                'fecha' => $reserva->FechaCita->format('Y-m-d'),
                'hora' => $reserva->HoraInicio,
                'barbero' => $reserva->barbero->usuario->Nombre . ' ' . $reserva->barbero->usuario->Apellido,
                'servicios' => $servicios->pluck('Nombre')->implode(', '),
                'productos' => $productos->map(function($d) { return $d->producto->Nombre . ' (x' . $d->Cantidad . ')'; })->implode(', '),
                'metodos_pago' => $reserva->pagos->pluck('MetodoPago')->unique()->implode(', '),
                'monto_total' => $montoTotal
            ];
        }

        foreach ($ventasSueltas as $venta) {
            $productos = $venta->detalles;
            $montoTotal = $venta->MontoTotal;
            $ingresoTotal += $montoTotal;
            
            foreach ($productos as $detalle) {
                $cantidadProductos += $detalle->Cantidad;
            }

            $transacciones[] = [
                'referencia' => 'V-' . $venta->IdVenta,
                'fecha' => $venta->Fecha->format('Y-m-d'),
                'hora' => $venta->Fecha->format('H:i:s'),
                'barbero' => $venta->barbero->usuario->Nombre . ' ' . $venta->barbero->usuario->Apellido,
                'servicios' => '',
                'productos' => $productos->map(function($d) { return $d->producto->Nombre . ' (x' . $d->Cantidad . ')'; })->implode(', '),
                'metodos_pago' => 'Efectivo/Transferencia', // TODO: Agregar metodo_pago a Venta si es necesario
                'monto_total' => $montoTotal
            ];
        }

        // Ordenar por fecha y hora
        usort($transacciones, function($a, $b) {
            $timeA = strtotime($a['fecha'] . ' ' . $a['hora']);
            $timeB = strtotime($b['fecha'] . ' ' . $b['hora']);
            return $timeA - $timeB;
        });

        return response()->json([
            'resumen' => [
                'ingreso_total' => $ingresoTotal,
                'cantidad_servicios' => $cantidadServicios,
                'cantidad_productos' => $cantidadProductos,
            ],
            'transacciones' => $transacciones
        ]);
    }

    /**
     * Reporte de Inventario (RF20)
     */
    public function inventario(Request $request)
    {
        $fechaInicio = $request->query('fecha_inicio') ? Carbon::parse($request->query('fecha_inicio'))->startOfDay() : Carbon::now()->startOfWeek();
        $fechaFin = $request->query('fecha_fin') ? Carbon::parse($request->query('fecha_fin'))->endOfDay() : Carbon::now()->endOfWeek();
        $idBarbero = $request->query('id_barbero');

        $productos = Producto::where('EstadoA', true)->get();
        
        $queryDetalles = DetalleVenta::with('venta')
            ->whereHas('venta', function($q) use ($fechaInicio, $fechaFin, $idBarbero) {
                $q->whereBetween('Fecha', [$fechaInicio, $fechaFin]);
                if ($idBarbero) {
                    $q->where('IdBarbero', $idBarbero);
                }
            })->get();

        $reporte = [];

        foreach ($productos as $producto) {
            $ventasProducto = $queryDetalles->where('IdProducto', $producto->IdProducto);
            
            $cantidadVendida = $ventasProducto->sum('Cantidad');
            $gananciaAcumulada = $ventasProducto->sum(function($detalle) {
                // Ganancia = (PrecioVenta - CostoCompra) * Cantidad - ComisionBarbero
                // Simplificamos asumiendo que la ganancia es para la barbería (PrecioVenta - CostoCompra - ComisionBarbero)
                return ($detalle->PrecioUnitario - $detalle->producto->CostoCompra) * $detalle->Cantidad - $detalle->ComisionBarbero;
            });

            // Si se filtra por barbero, y este barbero no vendio este producto, lo podemos omitir
            if ($idBarbero && $cantidadVendida == 0) {
                continue;
            }

            // RF20 pide Stock Inicial y Stock Final. 
            // Aproximación simple: Stock Final = StockActual. Stock Inicial = StockActual + Cantidad Vendida.
            $stockFinal = $producto->StockActual;
            $stockInicial = $stockFinal + $cantidadVendida; 

            $reporte[] = [
                'id_producto' => $producto->IdProducto,
                'nombre' => $producto->Nombre,
                'stock_inicial' => $stockInicial,
                'cantidad_vendida' => $cantidadVendida,
                'stock_final' => $stockFinal,
                'ganancia_acumulada' => $gananciaAcumulada,
                'alerta' => $stockFinal <= 5 // true para rojo/amarillo
            ];
        }

        return response()->json([
            'periodo' => [
                'inicio' => $fechaInicio->format('Y-m-d'),
                'fin' => $fechaFin->format('Y-m-d')
            ],
            'inventario' => $reporte
        ]);
    }
}
