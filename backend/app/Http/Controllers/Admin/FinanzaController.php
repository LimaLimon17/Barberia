<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barbero;
use App\Models\Comision;
use App\Models\Reserva;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FinanzaController extends Controller
{
    /**
     * GET /api/admin/finanzas?semana=&ano=&id_barbero=
     * RF26: ingresos por servicios/productos, fondos de la barbería por
     * concepto, comisiones a pagar y desglose por barbero. Las comisiones
     * siempre se leen de la tabla Comisiones (misma fuente que el barbero
     * ve en su propio panel).
     */
    public function index(Request $request)
    {
        $request->validate([
            'semana' => 'nullable|integer|min:1|max:53',
            'anio' => 'nullable|integer|min:2020',
            'id_barbero' => 'nullable|integer|exists:Barberos,IdBarbero',
        ]);

        $semana = (int) ($request->input('semana') ?? now()->isoWeek());
        $anio = (int) ($request->input('anio') ?? now()->isoWeekYear());
        $idBarbero = $request->input('id_barbero');

        $inicioSemana = Carbon::now()->setISODate($anio, $semana, 1)->startOfDay();
        $finSemana = Carbon::now()->setISODate($anio, $semana, 7)->endOfDay();

        // ── Ingresos brutos (antes de comisión) ──
        $reservasCompletadas = Reserva::whereBetween('FechaCita', [$inicioSemana->format('Y-m-d'), $finSemana->format('Y-m-d')])
            ->where('EstadoReserva', 'Completada')
            ->when($idBarbero, fn ($q) => $q->where('IdBarbero', $idBarbero))
            ->get();
        $ingresosServicios = (float) $reservasCompletadas->sum('CostoTotal');

        $ventas = Venta::whereBetween('Fecha', [$inicioSemana, $finSemana])
            ->where('EstadoA', 1)
            ->when($idBarbero, fn ($q) => $q->where('IdBarbero', $idBarbero))
            ->get();
        $ingresosVentas = (float) $ventas->sum('MontoTotal');

        // ── Comisiones (fuente única: tabla Comisiones) ──
        $barberos = Barbero::with('usuario')
            ->where('EstadoA', 1)
            ->when($idBarbero, fn ($q) => $q->where('IdBarbero', $idBarbero))
            ->orderBy('FechaIngreso')
            ->get();

        $comisiones = Comision::whereIn('IdBarbero', $barberos->pluck('IdBarbero'))
            ->whereBetween('Fecha', [$inicioSemana, $finSemana])
            ->get();

        $comisionServiciosTotal = (float) $comisiones->where('TipoComision', Comision::TIPO_SERVICIO)->sum('MontoComision');
        $comisionProductosTotal = (float) $comisiones->where('TipoComision', Comision::TIPO_PRODUCTO)->sum('MontoComision');
        $comisionAusentesTotal = (float) $comisiones->where('TipoComision', Comision::TIPO_AUSENTE)->sum('MontoComision');
        $comisionesAPagar = $comisionServiciosTotal + $comisionProductosTotal + $comisionAusentesTotal;

        // ── Fondos de la barbería: lo que NO se paga como comisión ──
        $fondoServicios = $ingresosServicios - $comisionServiciosTotal;
        $fondoProductos = $ingresosVentas - $comisionProductosTotal;
        // El fondo por ausentes es la otra mitad del anticipo retenido,
        // exactamente el mismo monto que se paga de comisión por ese concepto.
        $fondoAusentes = $comisionAusentesTotal;
        $fondoTotal = $fondoServicios + $fondoProductos + $fondoAusentes;

        // ── Desglose por barbero ──
        $comisionesPorBarbero = $comisiones->groupBy('IdBarbero');
        $desgloseBarberos = $barberos->map(function ($barbero) use ($comisionesPorBarbero) {
            $filas = $comisionesPorBarbero->get($barbero->IdBarbero, collect());

            $servicios = (float) $filas->where('TipoComision', Comision::TIPO_SERVICIO)->sum('MontoComision');
            $productos = (float) $filas->where('TipoComision', Comision::TIPO_PRODUCTO)->sum('MontoComision');
            $ausentes = (float) $filas->where('TipoComision', Comision::TIPO_AUSENTE)->sum('MontoComision');

            return [
                'id' => $barbero->IdBarbero,
                'nombre' => trim(($barbero->usuario->Nombre1 ?? '') . ' ' . ($barbero->usuario->Apellido1 ?? '')),
                'servicios' => round($servicios, 2),
                'productos' => round($productos, 2),
                'ausentes' => round($ausentes, 2),
                'total' => round($servicios + $productos + $ausentes, 2),
            ];
        })->values();

        return response()->json([
            'periodo' => [
                'inicio' => $inicioSemana->format('d/m/Y'),
                'fin' => $finSemana->format('d/m/Y'),
            ],
            'semana' => $semana,
            'anio' => $anio,
            'consolidado' => now()->gte(Carbon::now()->setISODate($anio, $semana, 7)->setTime(21, 0, 0)),
            'ingresos_servicios' => round($ingresosServicios, 2),
            'ingresos_ventas' => round($ingresosVentas, 2),
            'ingresos_totales' => round($ingresosServicios + $ingresosVentas, 2),
            'fondos_barberia' => [
                'servicios' => round($fondoServicios, 2),
                'productos' => round($fondoProductos, 2),
                'ausentes' => round($fondoAusentes, 2),
                'total' => round($fondoTotal, 2),
            ],
            'comisiones_a_pagar' => round($comisionesAPagar, 2),
            'desglose_barberos' => $desgloseBarberos,
        ]);
    }
}