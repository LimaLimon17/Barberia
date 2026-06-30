<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barbero;
use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * HU-14: Panel principal del administrador.
     * Muestra información consolidada de los barberos, y citas del día.
     */
    public function index()
    {
        $hoy = Carbon::today();
        // Semana empieza el Lunes
        $inicioSemana = Carbon::now()->startOfWeek(Carbon::MONDAY);

        // 1. Citas del Día (Todas las citas de hoy ordenadas)
        $citasHoy = Reserva::with(['cliente', 'barbero.usuario', 'servicios'])
            ->whereDate('FechaCita', $hoy)
            ->orderBy('HoraInicio')
            ->get()
            ->map(function ($reserva) {
                return [
                    'id' => $reserva->IdReserva,
                    'hora' => Carbon::parse($reserva->HoraInicio)->format('H:i'),
                    'barbero' => $reserva->barbero->usuario->Nombre1 . ' ' . $reserva->barbero->usuario->Apellido1,
                    'cliente' => $reserva->cliente->Nombre1 . ' ' . $reserva->cliente->Apellido1,
                    'servicios' => $reserva->servicios->pluck('Nombre')->implode(', '),
                    'estado' => $reserva->EstadoReserva
                ];
            });

        // 2. Visualización consolidada de barberos (ganancias de la semana, citas completadas hoy)
        $barberosData = Barbero::with('usuario')->where('EstadoA', 1)->get()->map(function ($barbero) use ($hoy, $inicioSemana) {
            
            $citasCompletadasHoy = Reserva::where('IdBarbero', $barbero->IdBarbero)
                ->whereDate('FechaCita', $hoy)
                ->where('EstadoReserva', 'Completada')
                ->count();

            // Ganancias estimadas de la semana por servicios (50%)
            $ingresosServicios = Reserva::where('IdBarbero', $barbero->IdBarbero)
                ->whereBetween('FechaCita', [$inicioSemana->format('Y-m-d'), Carbon::today()->format('Y-m-d')])
                ->where('EstadoReserva', 'Completada')
                ->sum('CostoTotal');
                
            $gananciaAprox = $ingresosServicios * 0.50; // 50% para el barbero

            return [
                'id' => $barbero->IdBarbero,
                'nombre' => $barbero->usuario->Nombre1 . ' ' . $barbero->usuario->Apellido1,
                'citas_hoy' => $citasCompletadasHoy,
                'ganancia_semana' => round($gananciaAprox, 2),
                'estado' => 'Activo'
            ];
        });

        return response()->json([
            'citas_hoy' => $citasHoy,
            'barberos' => $barberosData
        ]);
    }
}
