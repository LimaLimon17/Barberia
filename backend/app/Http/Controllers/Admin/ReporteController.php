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
    $idServicio = $request->query('id_servicio');

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

         if ($idServicio) {
        $queryReservas->whereHas('servicios', fn ($q) => $q->where('Servicios.IdServicio', $idServicio));
        // Las ventas sueltas no tienen servicios asociados: si se filtra por
        // servicio, no aplica incluirlas en el listado.
        $queryVentasSueltas->whereRaw('1 = 0');
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

            $montoTotal = (float) $reserva->CostoTotal + $montoProductos;
            $ingresoTotal += $montoTotal;
            $cantidadServicios += $servicios->count();

            $transacciones[] = [
                'referencia' => 'R-' . $reserva->IdReserva,
                'fecha' => $reserva->FechaCita->format('Y-m-d'),
                'hora' => $reserva->HoraInicio,
                'barbero' => trim(($reserva->barbero->usuario->Nombre1 ?? '') . ' ' . ($reserva->barbero->usuario->Apellido1 ?? '')),
                'servicios' => $servicios->pluck('Nombre')->implode(', '),
                'productos' => $productos->map(fn ($d) => ($d->producto->Nombre ?? '???') . ' (x' . $d->Cantidad . ')')->implode(', '),
                'metodos_pago' => $reserva->pagos->pluck('MetodoPago')->unique()->implode(', '),
                'monto_total' => round($montoTotal, 2),
            ];
        }

        foreach ($ventasSueltas as $venta) {
            $productos = $venta->detalles;
            $montoTotal = (float) $venta->MontoTotal;
            $ingresoTotal += $montoTotal;

            foreach ($productos as $detalle) {
                $cantidadProductos += $detalle->Cantidad;
            }

            $metodoPago = $venta->pagos()->value('MetodoPago') ?? '—';

            $transacciones[] = [
                'referencia' => 'V-' . $venta->IdVenta,
                'fecha' => $venta->Fecha->format('Y-m-d'),
                'hora' => $venta->Fecha->format('H:i:s'),
                'barbero' => trim(($venta->barbero->usuario->Nombre1 ?? '') . ' ' . ($venta->barbero->usuario->Apellido1 ?? '')),
                'servicios' => '',
                'productos' => $productos->map(fn ($d) => ($d->producto->Nombre ?? '???') . ' (x' . $d->Cantidad . ')')->implode(', '),
                'metodos_pago' => $metodoPago,
                'monto_total' => round($montoTotal, 2),
            ];
        }

        usort($transacciones, function ($a, $b) {
            $timeA = strtotime($a['fecha'] . ' ' . $a['hora']);
            $timeB = strtotime($b['fecha'] . ' ' . $b['hora']);
            return $timeA - $timeB;
        });

        return response()->json([
            'resumen' => [
                'ingreso_total' => round($ingresoTotal, 2),
                'cantidad_servicios' => $cantidadServicios,
                'cantidad_productos' => $cantidadProductos,
            ],
            'transacciones' => $transacciones,
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

        $queryDetalles = DetalleVenta::with(['venta', 'producto'])
            ->whereHas('venta', function ($q) use ($fechaInicio, $fechaFin, $idBarbero) {
                $q->whereBetween('Fecha', [$fechaInicio, $fechaFin]);
                if ($idBarbero) {
                    $q->where('IdBarbero', $idBarbero);
                }
            })->get();

        $reporte = [];

        foreach ($productos as $producto) {
            $ventasProducto = $queryDetalles->where('IdProducto', $producto->IdProducto);

            $cantidadVendida = (int) $ventasProducto->sum('Cantidad');
            $gananciaAcumulada = $ventasProducto->sum(function ($detalle) use ($producto) {
                // Ganancia de la barbería = (PrecioVenta - CostoCompra) * Cantidad - ComisionBarbero
                return ((float) $detalle->PrecioUnitario - (float) $producto->CostoCompra) * $detalle->Cantidad - (float) $detalle->ComisionBarbero;
            });

            if ($idBarbero && $cantidadVendida === 0) {
                continue;
            }

            $stockFinal = $producto->StockActual;
            $stockInicial = $stockFinal + $cantidadVendida;

            $reporte[] = [
                'id_producto' => $producto->IdProducto,
                'nombre' => $producto->Nombre,
                'stock_inicial' => $stockInicial,
                'cantidad_vendida' => $cantidadVendida,
                'stock_final' => $stockFinal,
                'ganancia_acumulada' => round($gananciaAcumulada, 2),
                'alerta' => $stockFinal <= 5,
            ];
        }

        return response()->json([
            'periodo' => [
                'inicio' => $fechaInicio->format('Y-m-d'),
                'fin' => $fechaFin->format('Y-m-d'),
            ],
            'inventario' => $reporte,
        ]);
    }
}