<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\HorarioRequest;
use App\Models\Barbero;
use App\Models\HorarioBarbero;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HorarioController extends Controller
{
    /**
     * Lista los horarios de un barbero.
     */
    public function index(Request $request, $idBarbero)
    {
        $barbero = Barbero::find($idBarbero);

        if (!$barbero) {
            return response()->json(['mensaje' => 'Barbero no encontrado'], 404);
        }

        $horarios = HorarioBarbero::where('IdBarbero', $idBarbero)
            ->where('EstadoA', 1)
            ->with('horario')
            ->get()
            ->map(function ($hb) {
                return [
                    'id_horario_barbero' => $hb->IdHorarioBarbero,
                    'fecha_inicio'       => $hb->FechaInicio,
                    'fecha_fin'          => $hb->FechaFin,
                    'dias' => $hb->horario->map(fn($h) => [
                        'dia'          => $h->DiaSemana,
                        'hora_entrada' => $h->HoraEntrada,
                        'hora_salida'  => $h->HoraSalida,
                        'dia_descanso' => (bool) $h->DiaDescanso,
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
            DB::statement('CALL sp_AsignarHorarioBarbero(?, ?, ?, ?, ?, ?)', [
                $request->input('id_barbero'),
                $request->input('fecha_inicio'),
                $request->input('fecha_fin'),
                $dias,
                $admin->IdUsuario,
                $ip,
            ]);

            // Asumiendo que el SP maneja el retorno o la inserción
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
            'mensaje' => 'Horario creado correctamente',
        ], 201);
    }

    /**
     * HU-11 Escenario 9: Modificar configuración de horario existente.
     */
    public function update(Request $request, $idHorarioBarbero)
    {
        $admin = $request->user();
        $ip    = $request->ip();

        $request->validate([
            'dias'              => 'required|array|min:1',
            'dias.*.dia'        => 'required|string',
            'dias.*.hora_entrada'=> 'required|date_format:H:i',
            'dias.*.hora_salida' => 'required|date_format:H:i',
            'dias.*.dia_descanso'=> 'required|boolean',
        ]);

        $horarioBarbero = HorarioBarbero::find($idHorarioBarbero);

        if (!$horarioBarbero) {
            return response()->json(['mensaje' => 'Horario no encontrado'], 404);
        }

        // Validar 8 horas mínimas y rango 10:00-22:00
        foreach ($request->input('dias') as $dia) {
            if ($dia['dia_descanso']) continue;

            if ($dia['hora_entrada'] < '10:00' || $dia['hora_salida'] > '22:00') {
                return response()->json(['mensaje' => "El horario debe estar entre 10:00 y 22:00"], 422);
            }

            $entrada = strtotime($dia['hora_entrada']);
            $salida  = strtotime($dia['hora_salida']);
            $horas   = ($salida - $entrada) / 3600 - 1; // Resta 1 hora de descanso

            if ($horas < 8) {
                return response()->json(['mensaje' => "El día {$dia['dia']} no cumple las 8 horas mínimas"], 422);
            }
        }

        // Eliminar días existentes y reemplazar en tabla Horarios (usando IdHorarioBarbero)
        DB::table('Horarios')
            ->where('IdHorarioBarbero', $idHorarioBarbero)
            ->delete();

        foreach ($request->input('dias') as $dia) {
            DB::table('Horarios')->insert([
                'IdHorarioBarbero' => $idHorarioBarbero,
                'DiaSemana'        => $dia['dia'],
                'HoraEntrada'      => $dia['hora_entrada'],
                'HoraSalida'       => $dia['hora_salida'],
                'DiaDescanso'      => $dia['dia_descanso'] ? 1 : 0,
                'EstadoA'          => 1,
                'FechaA'           => now(),
                'UsuarioA'         => $admin->IdUsuario,
            ]);
        }

        return response()->json(['mensaje' => 'Horario actualizado correctamente'], 200);
    }
}