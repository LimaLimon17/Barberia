<?php

namespace App\Http\Controllers\Barbero;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Barbero;
use App\Models\Reserva;
use App\Models\Venta;
use App\Services\ComisionService;
use Carbon\Carbon;

class ReporteBarberoController extends Controller
{
    protected $comisionService;

    public function __construct(ComisionService $comisionService)
    {
        $this->comisionService = $comisionService;
    }

    /**
     * HU-17 y HU-10: Reportes personalizados y comisiones (consolidadas y parciales) del barbero.
     */
    public function index(Request $request)
    {
        $usuario = $request->user();
        $barbero = Barbero::where('IdUsuario', $usuario->IdUsuario)
            ->where('EstadoA', 1)
            ->first();

        if (!$barbero) {
            return response()->json(['mensaje' => 'Barbero no encontrado'], 404);
        }

        // Filtro por fecha: por defecto semana actual
        $periodo = $request->query('periodo', 'semanal'); // 'diario' o 'semanal'
        
        if ($periodo === 'diario') {
            $fechaInicio = $request->query('fecha') ? Carbon::parse($request->query('fecha'))->startOfDay() : Carbon::today();
            $fechaFin = $fechaInicio->copy()->endOfDay();
        } else {
            // Semanal
            if ($request->query('fecha')) {
                $fechaBase = Carbon::parse($request->query('fecha'));
                $fechaInicio = $fechaBase->copy()->startOfWeek();
                $fechaFin = $fechaBase->copy()->endOfWeek();
            } else {
                $fechaInicio = Carbon::now()->startOfWeek();
                $fechaFin = Carbon::now()->endOfWeek();
            }
        }

        // 1. Citas completadas (Para el detalle separado)
        $citasCompletadas = Reserva::with(['cliente', 'servicios'])
            ->where('IdBarbero', $barbero->IdBarbero)
            ->where('EstadoReserva', 'Completada')
            ->whereBetween('FechaCita', [$fechaInicio->format('Y-m-d'), $fechaFin->format('Y-m-d')])
            ->orderBy('FechaCita', 'desc')
            ->orderBy('HoraInicio', 'desc')
            ->get()
            ->map(function ($reserva) {
                return [
                    'fecha' => $reserva->FechaCita->format('Y-m-d'),
                    'hora' => $reserva->HoraInicio,
                    'cliente' => $reserva->cliente ? $reserva->cliente->Nombre . ' ' . $reserva->cliente->Apellido : 'Desconocido',
                    'servicios' => $reserva->servicios->pluck('Nombre')->implode(', '),
                    'monto' => $reserva->CostoTotal
                ];
            });

        // 2. Ventas de productos
        $ventasProductos = Venta::with(['detalles.producto'])
            ->where('IdBarbero', $barbero->IdBarbero)
            ->whereBetween('Fecha', [$fechaInicio->startOfDay(), $fechaFin->endOfDay()])
            ->get()
            ->flatMap(function ($venta) {
                return $venta->detalles->map(function ($detalle) use ($venta) {
                    return [
                        'fecha' => $venta->Fecha->format('Y-m-d H:i:s'),
                        'producto' => $detalle->producto ? $detalle->producto->Nombre : 'Producto',
                        'cantidad' => $detalle->Cantidad,
                        'monto' => $detalle->Cantidad * $detalle->PrecioUnitario
                    ];
                });
            })
            ->sortByDesc('fecha')
            ->values();

        // 3. Ganancias y comisiones (usando ComisionService)
        // El servicio maneja todo el acumulado parcial o ya consolidado en base al periodo.
        $ganancias = $this->comisionService->calcularGanancias($barbero, $fechaInicio, $fechaFin);

        return response()->json([
            'periodo' => [
                'tipo' => $periodo,
                'inicio' => $fechaInicio->format('Y-m-d'),
                'fin' => $fechaFin->format('Y-m-d')
            ],
            'citas_completadas' => $citasCompletadas,
            'ventas_productos' => $ventasProductos,
            'ingresos_totales' => $ganancias['total_servicios'] + $ganancias['total_ventas'],
            'comision_calculada' => $ganancias['total_ganado'],
            'desglose_ganancias' => [
                'servicios' => $ganancias['comision_servicios'],
                'productos' => $ganancias['comision_ventas'],
                'ausentes' => $ganancias['comision_ausentes']
            ],
            // El detalle unificado que agrupa servicios y productos por cita
            'detalle_transacciones' => $ganancias['detalle'] 
        ]);
    }
}
