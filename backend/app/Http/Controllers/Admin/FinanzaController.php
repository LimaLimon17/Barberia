<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reserva;
use App\Models\Venta;
use App\Models\Barbero;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FinanzaController extends Controller
{
    /**
     * HU-16 / RF27: Panel de finanzas (rendimiento semanal acumulado).
     */
    public function index(Request $request)
    {
        $inicioSemana = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $finSemana = Carbon::now()->endOfWeek(Carbon::SUNDAY);
        
        $idBarbero = $request->input('id_barbero');

        $reservasQuery = Reserva::whereBetween('FechaCita', [$inicioSemana->format('Y-m-d'), $finSemana->format('Y-m-d')])
            ->where('EstadoReserva', 'Completada');
            
        $ausentesQuery = Reserva::whereBetween('FechaCita', [$inicioSemana->format('Y-m-d'), $finSemana->format('Y-m-d')])
            ->where('EstadoReserva', 'Ausente')
            ->where('MontoAnticipo', '>', 0);
            
        $ventasQuery = Venta::with('detalles')->whereBetween('Fecha', [$inicioSemana, $finSemana])->where('EstadoA', 1);

        if ($idBarbero) {
            $reservasQuery->where('IdBarbero', $idBarbero);
            $ausentesQuery->where('IdBarbero', $idBarbero);
            $ventasQuery->where('IdBarbero', $idBarbero);
        }

        $reservas = $reservasQuery->get();
        $ausentes = $ausentesQuery->get();
        $ventas = $ventasQuery->get();

        $ingresosServicios = $reservas->sum('CostoTotal');
        $ingresosVentas = $ventas->sum('MontoTotal');
        $totalAusentes = $ausentes->sum('MontoAnticipo');

        // Fondos que se queda la barbería (El otro 50% y retenciones de productos)
        $fondoServicios = $ingresosServicios * 0.50;
        $fondoAusentes = $totalAusentes * 0.50;
        $fondoProductos = 0;
        $comisionProductos = 0;

        foreach ($ventas as $venta) {
            foreach ($venta->detalles as $detalle) {
                $subtotal = $detalle->PrecioUnitario * $detalle->Cantidad;
                $comisionBarbero = $detalle->ComisionBarbero;
                $fondoProductos += ($subtotal - $comisionBarbero);
                $comisionProductos += $comisionBarbero;
            }
        }

        $fondoTotal = $fondoServicios + $fondoAusentes + $fondoProductos;
        
        // Comisiones a pagar a los barberos
        $comisionesPagar = ($ingresosServicios * 0.50) + ($totalAusentes * 0.50) + $comisionProductos;

        // Desglose por barbero
        $barberos = Barbero::with('usuario')->where('EstadoA', 1)->get();
        $desgloseBarberos = [];
        foreach ($barberos as $barbero) {
            if ($idBarbero && $barbero->IdBarbero != $idBarbero) continue;

            $bServ = Reserva::where('IdBarbero', $barbero->IdBarbero)
                ->whereBetween('FechaCita', [$inicioSemana->format('Y-m-d'), $finSemana->format('Y-m-d')])
                ->where('EstadoReserva', 'Completada')->sum('CostoTotal') * 0.5;

            $bAus = Reserva::where('IdBarbero', $barbero->IdBarbero)
                ->whereBetween('FechaCita', [$inicioSemana->format('Y-m-d'), $finSemana->format('Y-m-d')])
                ->where('EstadoReserva', 'Ausente')->sum('MontoAnticipo') * 0.5;

            $bVent = Venta::with('detalles')->where('IdBarbero', $barbero->IdBarbero)
                ->whereBetween('Fecha', [$inicioSemana, $finSemana])->where('EstadoA', 1)->get();
            $bProd = 0;
            foreach ($bVent as $v) {
                foreach ($v->detalles as $d) {
                    $bProd += $d->ComisionBarbero;
                }
            }

            $bTotal = $bServ + $bAus + $bProd;

            $desgloseBarberos[] = [
                'id' => $barbero->IdBarbero,
                'nombre' => $barbero->usuario->Nombre1 . ' ' . $barbero->usuario->Apellido1,
                'servicios' => round($bServ, 2),
                'ausentes' => round($bAus, 2),
                'productos' => round($bProd, 2),
                'total' => round($bTotal, 2)
            ];
        }

        return response()->json([
            'periodo' => [
                'inicio' => $inicioSemana->format('d/m/Y'),
                'fin' => $finSemana->format('d/m/Y')
            ],
            'ingresos_servicios' => round($ingresosServicios, 2),
            'ingresos_ventas' => round($ingresosVentas, 2),
            'ingresos_totales' => round($ingresosServicios + $ingresosVentas + $totalAusentes, 2),
            'fondos_barberia' => [
                'servicios' => round($fondoServicios, 2),
                'ausentes' => round($fondoAusentes, 2),
                'productos' => round($fondoProductos, 2),
                'total' => round($fondoTotal, 2)
            ],
            'comisiones_a_pagar' => round($comisionesPagar, 2),
            'desglose_barberos' => $desgloseBarberos
        ]);
    }
}
