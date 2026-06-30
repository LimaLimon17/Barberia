<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reserva;
use App\Models\Venta;
use App\Models\Producto;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteController extends Controller
{
    /**
     * HU-15: Reporte de Ventas Consolidadas
     */
    public function ventas(Request $request)
    {
        $fechaInicio = $request->input('inicio', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $fechaFin = $request->input('fin', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $idBarbero = $request->input('id_barbero');
        $idServicio = $request->input('id_servicio');

        $reservasQuery = Reserva::with(['cliente', 'barbero.usuario', 'servicios', 'pagos'])
            ->whereBetween('FechaCita', [$fechaInicio, $fechaFin])
            ->where('EstadoReserva', 'Completada');

        $ventasQuery = Venta::with(['cliente', 'barbero.usuario', 'detalles.producto'])
            ->whereBetween(DB::raw('DATE(Fecha)'), [$fechaInicio, $fechaFin])
            ->where('EstadoA', 1);

        if ($idBarbero) {
            $reservasQuery->where('IdBarbero', $idBarbero);
            $ventasQuery->where('IdBarbero', $idBarbero);
        }

        if ($idServicio) {
            $reservasQuery->whereHas('servicios', function ($q) use ($idServicio) {
                $q->where('Servicios.IdServicio', $idServicio);
            });
            // Si filtra por servicio, no mostramos ventas directas de productos
            $ventas = collect();
        } else {
            $ventas = $ventasQuery->get();
        }

        $reservas = $reservasQuery->get();

        $transacciones = collect();
        $ingresoTotal = 0;
        $cantidadServicios = 0;
        $cantidadProductos = 0;

        foreach ($reservas as $res) {
            $servs = $res->servicios->pluck('Nombre')->implode(', ');
            $pagos = $res->pagos->pluck('MetodoPago')->unique()->implode(', ');
            $transacciones->push([
                'referencia' => 'RES-'.$res->IdReserva,
                'fecha' => Carbon::parse($res->FechaCita)->format('d/m/Y') . ' ' . Carbon::parse($res->HoraInicio)->format('H:i'),
                'barbero' => $res->barbero->usuario->Nombre1 . ' ' . $res->barbero->usuario->Apellido1,
                'servicios' => $servs,
                'productos' => null,
                'metodos_pago' => $pagos,
                'monto_total' => $res->CostoTotal
            ]);
            $ingresoTotal += $res->CostoTotal;
            $cantidadServicios += $res->servicios->count();
        }

        foreach ($ventas as $ven) {
            $prods = $ven->detalles->map(function($d) { return $d->producto->Nombre . ' (x'.$d->Cantidad.')'; })->implode(', ');
            $transacciones->push([
                'referencia' => 'VEN-'.$ven->IdVenta,
                'fecha' => Carbon::parse($ven->Fecha)->format('d/m/Y H:i'),
                'barbero' => $ven->barbero->usuario->Nombre1 . ' ' . $ven->barbero->usuario->Apellido1,
                'servicios' => null,
                'productos' => $prods,
                'metodos_pago' => 'Venta Directa',
                'monto_total' => $ven->MontoTotal
            ]);
            $ingresoTotal += $ven->MontoTotal;
            $cantidadProductos += $ven->detalles->sum('Cantidad');
        }

        // Ordenar por fecha desc
        $transacciones = $transacciones->sortByDesc('fecha')->values();

        return response()->json([
            'resumen' => [
                'ingreso_total' => round($ingresoTotal, 2),
                'cantidad_servicios' => $cantidadServicios,
                'cantidad_productos' => $cantidadProductos
            ],
            'transacciones' => $transacciones
        ]);
    }

    /**
     * RF20: Reporte de Inventario
     */
    public function inventario(Request $request)
    {
        $fechaInicio = $request->input('inicio', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $fechaFin = $request->input('fin', Carbon::now()->endOfMonth()->format('Y-m-d'));
        
        $productos = Producto::where('EstadoA', 1)->get();
        
        $inventario = [];

        foreach ($productos as $producto) {
            // Historial de ventas de este producto
            $ventas = DB::table('DetalleVenta')
                ->join('Ventas', 'DetalleVenta.IdVenta', '=', 'Ventas.IdVenta')
                ->where('DetalleVenta.IdProducto', $producto->IdProducto)
                ->whereBetween(DB::raw('DATE(Ventas.Fecha)'), [$fechaInicio, $fechaFin])
                ->where('Ventas.EstadoA', 1)
                ->get();

            $cantidadVendida = $ventas->sum('Cantidad');
            $gananciaAcumulada = 0;
            foreach ($ventas as $v) {
                // Ganancia Barbería = (PrecioVenta - CostoCompra - ComisionBarbero) * Cantidad
                // O de forma más simple según el porcentaje de venta de la barberia
                $gananciaAcumulada += (($v->PrecioUnitario - $producto->CostoCompra) * $v->Cantidad) - $v->ComisionBarbero;
            }

            // Stock Inicial = Stock Actual + Vendidos en el periodo (aproximación para el reporte simple)
            $stockInicial = $producto->StockActual + $cantidadVendida;

            $inventario[] = [
                'id' => $producto->IdProducto,
                'nombre' => $producto->Nombre,
                'stock_inicial' => $stockInicial,
                'cantidad_vendida' => $cantidadVendida,
                'stock_final' => $producto->StockActual,
                'ganancia_acumulada' => round($gananciaAcumulada, 2),
                'alerta' => $producto->StockActual <= 5
            ];
        }

        return response()->json([
            'inventario' => $inventario
        ]);
    }
}
