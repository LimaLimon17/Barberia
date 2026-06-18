<?php

namespace App\Http\Controllers\Barbero;

use App\Http\Controllers\Controller;
use App\Models\Barbero;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            DB::statement('CALL sp_AuditoriaVerPerfilBarbero(?, ?, ?)', [
                $barbero->IdBarbero,
                $usuario->IdUsuario,
                $ip,
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
}
