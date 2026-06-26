<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\HorarioRequest;
use App\Models\Barbero;
use App\Models\HorarioSemanal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HorarioController extends Controller
{
    /**
     * Lista los horarios semanales de un barbero.
     */
    public function index(Request $request, $idBarbero)
    {
        $barbero = Barbero::find($idBarbero);

        if (!$barbero) {
            return response()->json(['mensaje' => 'Barbero no encontrado'], 404);
        }

        $horarios = HorarioSemanal::where('IdBarbero', $idBarbero)
            ->where('EstadoA', 1)
            ->orderBy('Año', 'desc')
            ->orderBy('Semana', 'desc')
            ->with('horarios')
            ->get()
            ->map(function ($hs) {
                return [
                    'id_horario_semanal' => $hs->IdHorarioSemanal,
                    'semana'             => $hs->Semana,
                    'ano'                => $hs->Año,
                    'dias'               => $hs->horarios->map(fn($h) => [
                        'dia'          => $h->DiaSemana,
                        'hora_entrada' => $h->HoraEntrada,
                        'hora_salida'  => $h->HoraSalida,
                        'dia_descanso' => $h->DiaDescanso,
                    ]),
                ];
            });

        return response()->json(['horarios' => $horarios], 200);
    }

    /**
     * HU-11 Escenario 8: Crear nueva configuración de horario.
     */
    public function store(HorarioRequest $request)
    {
        $admin  = $request->user();
        $ip     = $request->ip();
        $dias   = json_encode($request->input('dias'));

        try {
            DB::statement('CALL sp_AsignarHorarioSemanal(?, ?, ?, ?, ?, ?, @id_horario)', [
                $request->input('id_barbero'),
                $request->input('semana'),
                $request->input('ano'),
                $dias,
                $admin->IdUsuario,
                $ip,
            ]);

            $resultado = DB::select('SELECT @id_horario AS id')[0];

        } catch (\Exception $e) {
            $message = $e->getMessage();

            if (str_contains($message, 'mínimo 8 horas')) {
                return response()->json([
                    'mensaje' => 'Cada día laboral debe tener mínimo 8 horas efectivas de trabajo',
                ], 422);
            }

            if (str_contains($message, 'al menos un día')) {
                return response()->json([
                    'mensaje' => 'Debe configurar al menos un día de trabajo',
                ], 422);
            }

            return response()->json([
                'mensaje' => 'Error al crear el horario',
                'error'   => $message,
            ], 500);
        }

        return response()->json([
            'mensaje'            => 'Horario creado correctamente',
            'id_horario_semanal' => $resultado->id,
        ], 201);
    }
}
