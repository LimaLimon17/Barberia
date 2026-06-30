<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barbero;
use App\Models\HorarioBarbero;
use Illuminate\Http\Request;

class HorarioController extends Controller
{
    /**
     * GET /api/admin/barberos/{id}/horarios
     * Historial de asignaciones semanales de un barbero (semana por semana).
     */
    public function index(Request $request, $idBarbero)
    {
        $barbero = Barbero::find($idBarbero);

        if (!$barbero) {
            return response()->json(['mensaje' => 'Barbero no encontrado'], 404);
        }

        $asignaciones = HorarioBarbero::where('IdBarbero', $idBarbero)
            ->where('EstadoA', 1)
            ->with('horario')
            ->orderByDesc('FechaInicio')
            ->get()
            ->groupBy(fn ($a) => $a->FechaInicio->format('Y-m-d') . '_' . $a->FechaFin->format('Y-m-d'));

        $semanas = $asignaciones->map(function ($filas, $clave) {
            [$inicio, $fin] = explode('_', $clave);
            return [
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'dias' => $filas->map(fn ($f) => [
                    'dia' => $f->horario->DiaSemana,
                    'hora_entrada' => $f->horario->HoraEntrada,
                    'hora_salida' => $f->horario->HoraSalida,
                    'dia_descanso' => (bool) $f->horario->DiaDescanso,
                ])->values(),
            ];
        })->values();

        return response()->json(['horarios' => $semanas], 200);
    }
}