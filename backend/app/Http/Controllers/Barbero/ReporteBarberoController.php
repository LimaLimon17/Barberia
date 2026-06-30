<?php
namespace App\Http\Controllers\Barbero;

use App\Http\Controllers\Controller;
use App\Models\Barbero;
use App\Models\Reserva;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteBarberoController extends Controller
{
    /**
     * HU-17 / RF26: Reportes personalizados del barbero
     */
    public function index(Request $request)
    {
        $usuario = $request->user();
        $barbero = Barbero::where('IdUsuario', $usuario->IdUsuario)->first();

        if (!$barbero) {
            return response()->json(['message' => 'Barbero no encontrado'], 404);
        }

        $idBarbero = $barbero->IdBarbero;
        
        $filtro = $request->input('periodo', 'semana');
        
        if ($filtro === 'dia') {
            $inicio = Carbon::today();
            $fin = Carbon::today()->endOfDay();
        } else {
            $inicio = Carbon::now()->startOfWeek(Carbon::MONDAY);
            $fin = Carbon::now()->endOfWeek(Carbon::SUNDAY);
        }

        // Citas completadas
        $reservas = Reserva::with(['cliente', 'servicios'])
            ->where('IdBarbero', $idBarbero)
            ->whereBetween('FechaCita', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])
            ->where('EstadoReserva', 'Completada')
            ->get();

        // Ausentes (para comisiones)
        $ausentes = Reserva::with('cliente')
            ->where('IdBarbero', $idBarbero)
            ->whereBetween('FechaCita', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])
            ->where('EstadoReserva', 'Ausente')
            ->where('MontoAnticipo', '>', 0)
            ->get();

        // Ventas de productos
        $ventas = Venta::with(['cliente', 'detalles.producto'])
            ->where('IdBarbero', $idBarbero)
            ->whereBetween(DB::raw('DATE(Fecha)'), [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])
            ->where('EstadoA', 1)
            ->get();

        $ingresosServicios = $reservas->sum('CostoTotal');
        $comisionServicios = $ingresosServicios * 0.50; // RF18: 50%

        $comisionAusentes = $ausentes->sum('MontoAnticipo') * 0.50;

        $ingresosProductos = 0;
        $comisionProductos = 0;

        $detalleTransacciones = collect();

        foreach ($reservas as $res) {
            $servs = $res->servicios->pluck('Nombre')->implode(', ');
            $detalleTransacciones->push([
                'Fecha' => Carbon::parse($res->FechaCita)->format('d/m/Y') . ' ' . Carbon::parse($res->HoraInicio)->format('H:i'),
                'Cliente' => $res->cliente->Nombre1 . ' ' . $res->cliente->Apellido1,
                'Detalle' => 'Servicios: ' . $servs,
                'MontoTotal' => $res->CostoTotal,
                'Comision' => $res->CostoTotal * 0.5
            ]);
        }

        foreach ($ausentes as $aus) {
            $detalleTransacciones->push([
                'Fecha' => Carbon::parse($aus->FechaCita)->format('d/m/Y') . ' ' . Carbon::parse($aus->HoraInicio)->format('H:i'),
                'Cliente' => $aus->cliente->Nombre1 . ' ' . $aus->cliente->Apellido1,
                'Detalle' => 'Cita Ausente (Anticipo)',
                'MontoTotal' => $aus->MontoAnticipo,
                'Comision' => $aus->MontoAnticipo * 0.5
            ]);
        }

        foreach ($ventas as $ven) {
            $prods = $ven->detalles->map(function($d) { return $d->producto->Nombre . ' (x'.$d->Cantidad.')'; })->implode(', ');
            $totalVenta = $ven->MontoTotal;
            $comisionVenta = $ven->detalles->sum('ComisionBarbero');
            
            $ingresosProductos += $totalVenta;
            $comisionProductos += $comisionVenta;

            $detalleTransacciones->push([
                'Fecha' => Carbon::parse($ven->Fecha)->format('d/m/Y H:i'),
                'Cliente' => $ven->cliente ? ($ven->cliente->Nombre1 . ' ' . $ven->cliente->Apellido1) : 'Consumidor Final',
                'Detalle' => 'Productos: ' . $prods,
                'MontoTotal' => $totalVenta,
                'Comision' => $comisionVenta
            ]);
        }

        // Ordenar cronológicamente (RF17)
        $detalleTransacciones = $detalleTransacciones->sortBy('Fecha')->values();

        return response()->json([
            'periodo' => [
                'inicio' => $inicio->format('d/m/Y'),
                'fin' => $fin->format('d/m/Y')
            ],
            'ingresos_totales' => round($ingresosServicios + $ingresosProductos, 2),
            'comision_calculada' => round($comisionServicios + $comisionProductos + $comisionAusentes, 2),
            'desglose_ganancias' => [
                'servicios' => round($comisionServicios, 2),
                'productos' => round($comisionProductos, 2),
                'ausentes' => round($comisionAusentes, 2)
            ],
            'detalle_transacciones' => $detalleTransacciones
        ]);
    }
}
