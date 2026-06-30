<?php

namespace App\Http\Controllers\Barbero;

use App\Http\Controllers\Controller;
use App\Models\Barbero;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PerfilController extends Controller
{
    /**
     * HU-18: Perfil del barbero (solo lectura).
     * Muestra nombre completo, correo, fecha de ingreso y antigüedad en días.
     * Registra auditoría de consulta.
     */
    public function miPerfil(Request $request)
    {
        $usuario = $request->user();
        $ip = $request->ip();

        // Buscar el barbero asociado al usuario
        $barbero = Barbero::where('IdUsuario', $usuario->IdUsuario)
            ->where('EstadoA', 1)
            ->first();

        if (!$barbero) {
            return response()->json([
                'mensaje' => 'No se encontró el perfil de barbero',
            ], 404);
        }

        // Registrar auditoría de consulta de perfil
        try {
            DB::statement('CALL sp_RegistrarAuditoria(?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                'Barberos',
                (string) $barbero->IdBarbero,
                'CONSULTA_PERFIL',
                'Perfil',
                null,
                null,
                $usuario->IdUsuario,
                $ip,
                'Barbero consultó su propio perfil',
            ]);
        } catch (\Exception $e) {
            // Si falla la auditoría, no impedir el flujo
        }

        return response()->json([
            'barbero' => [
                'id_barbero' => $barbero->IdBarbero,
                'nombre1' => $usuario->Nombre1,
                'nombre2' => $usuario->Nombre2,
                'apellido1' => $usuario->Apellido1,
                'apellido2' => $usuario->Apellido2,
                'nombre_completo' => $usuario->nombre_completo,
                'correo' => $usuario->Correo,
                'fecha_ingreso' => $barbero->FechaIngreso->format('Y-m-d'),
                'antiguedad_dias' => $barbero->antiguedad_dias,
                'estado' => $barbero->estado_texto,
            ],
        ], 200);
    }

// ... dentro de la clase, junto a miPerfil()

/**
 * PUT /api/barbero/perfil/cambiar-password
 * El barbero cambia su propia contraseña. Requiere conocer la actual.
 */
public function cambiarPassword(Request $request)
{
    $request->validate([
        'password_actual' => ['required', 'string'],
        'password_nueva' => ['required', 'string', 'min:8', 'confirmed'],
    ], [
        'password_actual.required' => 'Debes ingresar tu contraseña actual.',
        'password_nueva.required' => 'Debes ingresar la nueva contraseña.',
        'password_nueva.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
        'password_nueva.confirmed' => 'La confirmación no coincide con la nueva contraseña.',
    ]);

    $usuario = $request->user();

    if (!Hash::check($request->input('password_actual'), $usuario->Contraseña)) {
        throw ValidationException::withMessages([
            'password_actual' => 'La contraseña actual es incorrecta.',
        ]);
    }

    $usuario->Contraseña = Hash::make($request->input('password_nueva'));
    $usuario->save();

    // Auditoría: se registra el evento, nunca el valor de la contraseña.
    try {
        DB::statement('CALL sp_RegistrarAuditoria(?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            'Usuarios',
            (string) $usuario->IdUsuario,
            'CAMBIO_PASSWORD',
            'Contraseña',
            null,
            'Cambiado por el propio usuario',
            $usuario->IdUsuario,
            $request->ip(),
            'Barbero actualizó su propia contraseña',
        ]);
    } catch (\Exception $e) {
        // No bloquea el flujo si la auditoría falla.
    }

    return response()->json(['mensaje' => 'Contraseña actualizada correctamente.']);
}
}
