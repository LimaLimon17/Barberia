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
     */
    public function index()
    {
        $hoy = Carbon::today();
        $inicioSemana = Carbon::now()->startOfWeek(Carbon::MONDAY);

        $citasHoy = Reserva::with(['cliente', 'barbero.usuario', 'servicios'])
            ->whereDate('FechaCita', $hoy)
            ->orderBy('HoraInicio')
            ->get()
            ->map(function ($reserva) {
                return [
                    'id' => $reserva->IdReserva,
                    'hora' => Carbon::parse($reserva->HoraInicio)->format('H:i'),
                    'barbero' => trim(($reserva->barbero->usuario->Nombre1 ?? '') . ' ' . ($reserva->barbero->usuario->Apellido1 ?? '')),
                    'cliente' => trim(($reserva->cliente->Nombre1 ?? '') . ' ' . ($reserva->cliente->Apellido1 ?? '')),
                    'servicios' => $reserva->servicios->pluck('Nombre')->implode(', '),
                    'estado' => $reserva->EstadoReserva,
                ];
            });

        $barberosData = Barbero::with('usuario')->where('EstadoA', 1)->get()->map(function ($barbero) use ($hoy, $inicioSemana) {
            $citasCompletadasHoy = Reserva::where('IdBarbero', $barbero->IdBarbero)
                ->whereDate('FechaCita', $hoy)
                ->where('EstadoReserva', 'Completada')
                ->count();

            $ingresosServicios = Reserva::where('IdBarbero', $barbero->IdBarbero)
                ->whereBetween('FechaCita', [$inicioSemana->format('Y-m-d'), Carbon::today()->format('Y-m-d')])
                ->where('EstadoReserva', 'Completada')
                ->sum('CostoTotal');

            $gananciaAprox = $ingresosServicios * 0.50;

            return [
                'id' => $barbero->IdBarbero,
                'nombre' => trim(($barbero->usuario->Nombre1 ?? '') . ' ' . ($barbero->usuario->Apellido1 ?? '')),
                'citas_hoy' => $citasCompletadasHoy,
                'ganancia_semana' => round($gananciaAprox, 2),
                'estado' => 'Activo',
            ];
        });

        return response()->json([
            'citas_hoy' => $citasHoy,
            'barberos' => $barberosData,
        ]);
    }
}