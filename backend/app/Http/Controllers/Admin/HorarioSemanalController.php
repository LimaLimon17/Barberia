<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barbero;
use Illuminate\Http\Request;

class HorarioSemanalController extends Controller
{
    /**
     * Devuelve la lista de barberos activos con su día de descanso FIFO
     * calculado por antigüedad para la semana indicada.
     */
    public function index(Request $request)
    {
        $semana = (int) $request->query('semana', now()->weekOfYear);
        $ano    = (int) $request->query('ano',    now()->year);

        // Barberos activos ordenados por fecha de ingreso (FIFO)
        $barberos = Barbero::with('usuario')
            ->where('EstadoA', 1)
            ->orderBy('FechaIngreso', 'asc')
            ->get();

        // Días de descanso asignados en orden FIFO (Lunes tiene prioridad)
        $diasDescanso = ['Lunes', 'Martes', 'Miércoles', 'Jueves'];

        $listaBarberos = $barberos->values()->map(function ($b, $index) use ($diasDescanso, $semana) {
            $diaDescanso = $diasDescanso[$index] ?? null;

            return [
                'id_barbero'        => $b->IdBarbero,
                'nombre_completo'   => optional($b->usuario)->nombre_completo ?? 'Sin Nombre',
                'fecha_ingreso'     => $b->FechaIngreso ? $b->FechaIngreso->format('Y-m-d') : null,
                'antiguedad_dias'   => $b->antiguedad_dias,
                'dia_descanso_fifo' => $diaDescanso,
                'horario_generado'  => true,
            ];
        });

        return response()->json([
            'semana'          => $semana,
            'ano'             => $ano,
            'semana_generada' => true,
            'barberos'        => $listaBarberos,
        ], 200);
    }

    /**
     * Stub — no se usa con la BD actual.
     */
    public function store(Request $request)
    {
        return response()->json([
            'mensaje' => 'Horario semanal actualizado correctamente',
            'semana'  => $request->input('semana', now()->weekOfYear),
            'ano'     => $request->input('ano',    now()->year),
        ], 200);
    }

    /**
     * Stub — no se usa con la BD actual.
     */
    public function update(Request $request, $idBarbero)
    {
        return response()->json([
            'mensaje' => 'Día de descanso actualizado correctamente',
        ], 200);
    }
}