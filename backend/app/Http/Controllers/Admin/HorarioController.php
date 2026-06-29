<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\HorarioRequest;
use App\Models\Barbero;
use App\Models\HorarioSemanal;
use App\Models\Horario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HorarioController extends Controller
{
    /**
     * Lista los horarios asignados a un barbero.
     */
    public function index(Request $request, $idBarbero)
    {
        $barbero = Barbero::find($idBarbero);
    
        if (!$barbero) {
            return response()->json(['mensaje' => 'Barbero no encontrado'], 404);
        }
    
        $horariosBarbero = HorarioSemanal::where('IdBarbero', $idBarbero)
            ->where('EstadoA', 1)
            ->with('horario')
            ->get();
    
        // Agrupar por semana ISO derivada de FechaInicio
        $agrupados = [];
    
        foreach ($horariosBarbero as $hb) {
            $fecha  = \Carbon\Carbon::parse($hb->FechaInicio);
            $semana = $fecha->weekOfYear;
            $ano    = $fecha->year;
            $clave  = "{$ano}-{$semana}";
        
            if (!isset($agrupados[$clave])) {
                $agrupados[$clave] = [
                    'id_horario_semanal' => $hb->IdHorarioBarbero, // ← clave que usa la vista
                    'semana'             => $semana,
                    'ano'                => $ano,
                    'dias'               => [],
                ];
            }
        
            $agrupados[$clave]['dias'][] = [
                'dia'          => $hb->horario->DiaSemana   ?? null,
                'hora_entrada' => $hb->horario->HoraEntrada ?? null,
                'hora_salida'  => $hb->horario->HoraSalida  ?? null,
                'dia_descanso' => (bool) ($hb->horario->DiaDescanso ?? false),
            ];
        }
    
        // Ordenar por año y semana descendente (más reciente primero)
        $horarios = collect(array_values($agrupados))
            ->sortByDesc(fn($h) => $h['ano'] * 100 + $h['semana'])
            ->values();
    
        return response()->json(['horarios' => $horarios], 200);
    }

    /**
     * Crear nueva asignación de horario para un barbero.
     */
    public function store(HorarioRequest $request)
    {
        $admin = $request->user();
        $ip    = $request->ip();
        $dias  = json_encode($request->input('dias'));

        try {
            DB::statement('CALL sp_AsignarHorarioSemanal(?, ?, ?, ?, ?, ?)', [
                $request->input('id_barbero'),
                $request->input('fecha_inicio'),
                $request->input('fecha_fin'),
                $dias,
                $admin->IdUsuario,
                $ip,
            ]);
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
     * Modificar horario existente de un barbero (por IdHorarioBarbero).
     */
    public function update(Request $request, $idHorarioBarbero)
    {
        $admin = $request->user();
        $ip    = $request->ip();

        $request->validate([
            'dias'                => 'required|array|min:1',
            'dias.*.dia'          => 'required|string',
            'dias.*.hora_entrada' => 'nullable|date_format:H:i',
            'dias.*.hora_salida'  => 'nullable|date_format:H:i',
            'dias.*.dia_descanso' => 'required|boolean',
        ]);

        $horarioBarbero = HorarioSemanal::find($idHorarioBarbero);

        if (!$horarioBarbero) {
            return response()->json(['mensaje' => 'Horario no encontrado'], 404);
        }

        foreach ($request->input('dias') as $dia) {
            if ($dia['dia_descanso']) continue;

            $entrada = strtotime($dia['hora_entrada']);
            $salida  = strtotime($dia['hora_salida']);
            $horas   = ($salida - $entrada) / 3600 - 1;

            if ($horas < 8) {
                return response()->json([
                    'mensaje' => "El día {$dia['dia']} no cumple las 8 horas mínimas efectivas",
                ], 422);
            }
        }

        foreach ($request->input('dias') as $dia) {
            // Buscar o crear el horario plantilla
            $horario = Horario::where('DiaSemana', $dia['dia'])
                ->where('DiaDescanso', $dia['dia_descanso'] ? 1 : 0)
                ->when(!$dia['dia_descanso'], function ($q) use ($dia) {
                    $q->where('HoraEntrada', $dia['hora_entrada'])
                      ->where('HoraSalida',  $dia['hora_salida']);
                })
                ->first();

            if (!$horario) {
                $horario = Horario::create([
                    'DiaSemana'  => $dia['dia'],
                    'HoraEntrada'=> $dia['dia_descanso'] ? null : $dia['hora_entrada'],
                    'HoraSalida' => $dia['dia_descanso'] ? null : $dia['hora_salida'],
                    'DiaDescanso'=> $dia['dia_descanso'] ? 1 : 0,
                    'EstadoA'    => 1,
                    'FechaA'     => now(),
                    'UsuarioA'   => $admin->IdUsuario,
                ]);
            }

            // Actualizar la asignación del barbero al nuevo horario
            HorarioSemanal::where('IdBarbero', $horarioBarbero->IdBarbero)
                ->where('EstadoA', 1)
                ->whereHas('horario', fn($q) => $q->where('DiaSemana', $dia['dia']))
                ->update(['IdHorario' => $horario->IdHorario]);
        }

        DB::statement('CALL sp_RegistrarAuditoria(?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            'HorariosBarberos', $idHorarioBarbero, 'UPDATE', 'Horario editado',
            null, 'Días actualizados', $admin->IdUsuario, $ip,
            'Admin modificó horario del barbero',
        ]);

        return response()->json(['mensaje' => 'Horario actualizado correctamente'], 200);
    }
}