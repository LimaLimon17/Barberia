<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barbero;
use App\Models\HorarioSemanal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HorarioSemanalController extends Controller
{
    /**
     * Devuelve el estado de horarios de una semana específica.
     * Incluye barberos, día de descanso FIFO y turno de almuerzo tardío.
     */
    public function index(Request $request)
    {
        $semana = $request->query('semana', now()->weekOfYear);
        $ano    = $request->query('ano',    now()->year);

        // Barberos activos ordenados por antigüedad (FIFO)
        $barberos = Barbero::with('usuario')
            ->whereHas('usuario', fn($q) => $q->where('EstadoA', 1))
            ->where('EstadoA', 1)
            ->orderBy('FechaIngreso', 'asc')
            ->get();

        // Horarios generados para esta semana
        $horariosGenerados = HorarioSemanal::where('Semana', $semana)
            ->where('Año', $ano)
            ->where('EstadoA', 1)
            ->pluck('IdBarbero')
            ->toArray();

        $semanaGenerada = count($horariosGenerados) > 0;

        // Turno de almuerzo tardío esta semana
        $turnoAlmuerzo = DB::table('TurnoAlmuerzosTardios')
            ->where('Semana', $semana)
            ->where('Año', $ano)
            ->where('EstadoA', 1)
            ->first();

        // Días FIFO asignados (posición = día)
        $diasFifo = ['Lunes', 'Martes', 'Miércoles', 'Jueves'];

        $listaBarberos = $barberos->values()->map(function ($b, $index) use ($diasFifo, $horariosGenerados, $turnoAlmuerzo) {
            $diaDescanso = $index < count($diasFifo) ? $diasFifo[$index] : null;

            return [
                'id_barbero'       => $b->IdBarbero,
                'nombre_completo'  => $b->usuario->nombre_completo,
                'fecha_ingreso'    => $b->FechaIngreso->format('Y-m-d'),
                'antiguedad_dias'  => $b->antiguedad_dias,
                'dia_descanso_fifo'=> $diaDescanso,
                'horario_generado' => in_array($b->IdBarbero, $horariosGenerados),
                'turno_almuerzo_tardio' => $turnoAlmuerzo
                    ? $turnoAlmuerzo->IdBarbero === $b->IdBarbero
                    : false,
            ];
        });

        return response()->json([
            'semana'          => (int) $semana,
            'ano'             => (int) $ano,
            'semana_generada' => $semanaGenerada,
            'turno_almuerzo'  => $turnoAlmuerzo ? [
                'id_barbero' => $turnoAlmuerzo->IdBarbero,
            ] : null,
            'barberos'        => $listaBarberos,
        ], 200);
    }

    /**
     * Genera los horarios de la semana aplicando FIFO y rotación de almuerzo.
     */
    public function store(Request $request)
    {
        $semana = $request->input('semana', now()->weekOfYear);
        $ano    = $request->input('ano',    now()->year);
        $admin  = $request->user();
        $ip     = $request->ip();

        try {
            DB::statement('CALL sp_GenerarHorarioSemana(?, ?, ?, ?)', [
                $semana,
                $ano,
                $admin->IdUsuario,
                $ip,
            ]);
        } catch (\Exception $e) {
            $msg = $e->getMessage();

            if (str_contains($msg, 'Ya existe un horario')) {
                return response()->json([
                    'mensaje' => 'Ya existe un horario generado para esta semana',
                ], 422);
            }

            return response()->json([
                'mensaje' => 'Error al generar el horario',
                'error'   => $msg,
            ], 500);
        }

        return response()->json([
            'mensaje' => 'Horario semanal generado correctamente',
            'semana'  => $semana,
            'ano'     => $ano,
        ], 201);
    }

    /**
     * Actualiza el día de descanso de un barbero específico para la semana.
     * Permite al admin ajustar manualmente el FIFO si hay conflictos.
     */
    public function update(Request $request, $idBarbero)
    {
        $request->validate([
            'semana'       => 'required|integer',
            'ano'          => 'required|integer',
            'dia_descanso' => 'required|string|in:Lunes,Martes,Miércoles,Jueves',
        ]);

        $horario = HorarioSemanal::where('IdBarbero', $idBarbero)
            ->where('Semana', $request->semana)
            ->where('Año', $request->ano)
            ->first();

        if (!$horario) {
            return response()->json([
                'mensaje' => 'No existe horario para este barbero en la semana indicada',
            ], 404);
        }

        // Actualizar el día de descanso en Horarios
        DB::table('Horarios')
            ->where('IdHorarioSemanal', $horario->IdHorarioSemanal)
            ->update(['DiaDescanso' => 0]);

        DB::table('Horarios')
            ->where('IdHorarioSemanal', $horario->IdHorarioSemanal)
            ->where('DiaSemana', $request->dia_descanso)
            ->update(['DiaDescanso' => 1]);

        return response()->json([
            'mensaje' => 'Día de descanso actualizado correctamente',
        ], 200);
    }
}
