<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Barbero;
use App\Models\Reserva;
use App\Services\ComisionService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected $comisionService;

    public function __construct(ComisionService $comisionService)
    {
        $this->comisionService = $comisionService;
    }

    /**
     * Retorna la información consolidada para el panel principal del Administrador.
     */
    public function index(Request $request)
    {
        $fechaInicioSemana = Carbon::now()->startOfWeek();
        $fechaFinSemana = Carbon::now()->endOfWeek();
        $hoy = Carbon::today()->format('Y-m-d');

        // 1. Visualización consolidada de barberos
        $barberos = Barbero::with(['usuario'])->get();
        $consolidadoBarberos = [];

        foreach ($barberos as $barbero) {
            // Ganancias de la semana actual
            $ganancias = $this->comisionService->calcularGanancias($barbero, $fechaInicioSemana, $fechaFinSemana);
            
            // Citas completadas del día
            $citasCompletadasHoy = Reserva::where('IdBarbero', $barbero->IdBarbero)
                ->where('EstadoReserva', 'Completada')
                ->where('FechaCita', $hoy)
                ->count();

            $consolidadoBarberos[] = [
                'id' => $barbero->IdBarbero,
                'nombre' => $barbero->usuario->Nombre . ' ' . $barbero->usuario->Apellido,
                'estado' => $barbero->EstadoA ? 'Activo' : 'Inactivo',
                'ganancia_semanal' => $ganancias['total_ganado'],
                'citas_completadas_hoy' => $citasCompletadasHoy
            ];
        }

        // 2. Citas del día
        $citasHoy = Reserva::with(['barbero.usuario', 'cliente', 'servicios'])
            ->where('FechaCita', $hoy)
            ->orderBy('HoraInicio', 'asc')
            ->get()
            ->map(function ($reserva) {
                return [
                    'hora' => $reserva->HoraInicio . ' - ' . $reserva->HoraFin,
                    'barbero' => $reserva->barbero->usuario->Nombre . ' ' . $reserva->barbero->usuario->Apellido,
                    'cliente' => $reserva->cliente ? $reserva->cliente->Nombre . ' ' . $reserva->cliente->Apellido : 'Desconocido',
                    'servicios' => $reserva->servicios->pluck('Nombre')->implode(', '),
                    'estado' => $reserva->EstadoReserva
                ];
            });

        return response()->json([
            'barberos' => $consolidadoBarberos,
            'citas_hoy' => $citasHoy
        ]);
    }
}
