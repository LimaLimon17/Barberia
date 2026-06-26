<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EditarBarberoRequest;
use App\Models\Barbero;
use App\Models\HorarioBarbero;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarberoController extends Controller
{
    /**
     * Lista todos los barberos activos.
     */
    public function index(Request $request)
    {
        $barberos = Barbero::with(['usuario.rol'])
            ->get()
            ->map(function ($barbero) {
                return [
                    'id_barbero' => $barbero->IdBarbero,
                    'nombre_completo' => $barbero->usuario->nombre_completo,
                    'correo' => $barbero->usuario->Correo,
                    'fecha_ingreso' => $barbero->FechaIngreso->format('Y-m-d'),
                    'antiguedad_dias' => $barbero->antiguedad_dias,
                    'estado' => $barbero->estado_texto,
                    'estado_activo' => $barbero->EstadoA,
                ];
            });

        return response()->json([
            'barberos' => $barberos,
        ], 200);
    }

    /**
     * HU-19: Perfil completo del barbero (vista administrador).
     * Muestra nombre, correo, fecha ingreso, antigüedad, estado y horario asignado.
     * Registra auditoría de consulta admin.
     */
    public function show(Request $request, $id)
    {
        $admin = $request->user();
        $ip = $request->ip();

        $barbero = Barbero::with(['usuario.rol'])->find($id);

        if (!$barbero) {
            return response()->json([
                'mensaje' => 'Barbero no encontrado',
            ], 404);
        }

        // Registrar auditoría de consulta admin (no bloquear si falla)
        try {
            DB::statement('CALL sp_RegistrarAuditoria(?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                'Barberos',
                (string) $barbero->IdBarbero,
                'CONSULTA_ADMIN',
                'Perfil',
                null,
                null,
                $admin->IdUsuario,
                $ip,
                'Admin consultó perfil del barbero',
            ]);
        } catch (\Exception $e) {
            // Si falla la auditoría, no impedir el flujo
        }

        // Obtener horarios asignados al barbero via HorariosBarberos -> Horarios
        $horariosBarbero = HorarioBarbero::where('IdBarbero', $barbero->IdBarbero)
            ->where('EstadoA', 1)
            ->with('horario')
            ->get();

        $horarios = $horariosBarbero->map(function ($hb) {
            $h = $hb->horario;
            if (!$h) return null;
            return [
                'dia_semana' => $h->DiaSemana,
                'hora_entrada' => $h->HoraEntrada,
                'hora_salida' => $h->HoraSalida,
                'dia_descanso' => $h->DiaDescanso,
            ];
        })->filter()->unique('dia_semana')->values();

        return response()->json([
            'barbero' => [
                'id_barbero' => $barbero->IdBarbero,
                'nombre1' => $barbero->usuario->Nombre1,
                'nombre2' => $barbero->usuario->Nombre2,
                'apellido1' => $barbero->usuario->Apellido1,
                'apellido2' => $barbero->usuario->Apellido2,
                'nombre_completo' => $barbero->usuario->nombre_completo,
                'correo' => $barbero->usuario->Correo,
                'fecha_ingreso' => $barbero->FechaIngreso->format('Y-m-d'),
                'antiguedad_dias' => $barbero->antiguedad_dias,
                'estado' => $barbero->estado_texto,
                'estado_activo' => $barbero->EstadoA,
                'horarios' => $horarios,
            ],
        ], 200);
    }

    /**
     * HU-20: Editar perfil del barbero por administrador.
     * Usa procedimiento almacenado sp_EditarPerfilBarbero con validaciones de
     * correo duplicado y fecha futura.
     */
    public function update(EditarBarberoRequest $request, $id)
    {
        $admin = $request->user();
        $ip = $request->ip();

        $barbero = Barbero::find($id);

        if (!$barbero) {
            return response()->json([
                'mensaje' => 'Barbero no encontrado',
            ], 404);
        }

        try {
            DB::statement('CALL sp_EditarPerfilBarbero(?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                $barbero->IdBarbero,
                $request->input('nombre1'),
                $request->input('nombre2', ''),
                $request->input('apellido1'),
                $request->input('apellido2', ''),
                $request->input('correo'),
                $request->input('fecha_ingreso'),
                $admin->IdUsuario,
                $ip,
            ]);
        } catch (\Exception $e) {
            $message = $e->getMessage();

            // Escenario 3: Correo duplicado
            if (str_contains($message, 'El correo ya existe')) {
                return response()->json([
                    'mensaje' => 'El correo electrónico ya está registrado en el sistema',
                ], 422);
            }

            // Escenario 4: Fecha futura
            if (str_contains($message, 'fecha de ingreso no puede ser futura')) {
                return response()->json([
                    'mensaje' => 'La fecha de ingreso no puede ser posterior a la fecha actual',
                ], 422);
            }

            return response()->json([
                'mensaje' => 'Error al actualizar el perfil del barbero',
                'error' => $message,
            ], 500);
        }

        // Recargar datos actualizados
        $barbero->refresh();
        $barbero->load('usuario');

        // Escenario 5: Confirmación de cambios con antigüedad recalculada
        return response()->json([
            'mensaje' => 'Perfil del barbero actualizado correctamente',
            'barbero' => [
                'id_barbero' => $barbero->IdBarbero,
                'nombre1' => $barbero->usuario->Nombre1,
                'nombre2' => $barbero->usuario->Nombre2,
                'apellido1' => $barbero->usuario->Apellido1,
                'apellido2' => $barbero->usuario->Apellido2,
                'nombre_completo' => $barbero->usuario->nombre_completo,
                'correo' => $barbero->usuario->Correo,
                'fecha_ingreso' => $barbero->FechaIngreso->format('Y-m-d'),
                'antiguedad_dias' => $barbero->antiguedad_dias,
                'estado' => $barbero->estado_texto,
            ],
        ], 200);
    }
}
