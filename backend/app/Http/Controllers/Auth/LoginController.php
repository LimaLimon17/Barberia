<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
class LoginController extends Controller
{
    /**
     * HU-01: Login de usuario (Barbero/Admin).
     * Valida credenciales y genera token Sanctum.
     * Registra auditoría de login exitoso/fallido.
     */
    public function login(LoginRequest $request)
    {
        $correo = $request->input('correo');
        $contraseña = $request->input('contraseña');
        $ip = $request->ip();
        // Buscar usuario por correo y estado activo
        $usuario = User::where('Correo', $correo)
            ->where('EstadoA', 1)
            ->first();
        // Escenario 2: Credenciales incorrectas
        if (!$usuario || $usuario->Contraseña !== $contraseña) {
            // Registrar auditoría de login fallido
            try {
                DB::statement('CALL sp_AuditoriaLoginFallido(?, ?, ?)', [$correo, $contraseña, $ip]);
            } catch (\Exception $e) {
                // Si falla la auditoría, no impedir el flujo
            }
            return response()->json([
                'mensaje' => 'Credenciales incorrectas. Verifique su correo y contraseña.',
            ], 401);
        }
        // Generar token Sanctum
        $token = $usuario->createToken('auth_token')->plainTextToken;
        // Registrar auditoría de login exitoso
        try {
            DB::statement('CALL sp_AuditoriaLoginExitoso(?, ?, ?)', [$usuario->IdUsuario, $correo, $ip]);
        } catch (\Exception $e) {
            // Si falla la auditoría, no impedir el flujo
        }
        // Cargar relación con rol
        $usuario->load('rol');
        // Escenario 1 y 3: Acceso exitoso con redirección según rol
        return response()->json([
            'mensaje' => 'Inicio de sesión exitoso',
            'token' => $token,
            'usuario' => [
                'id' => $usuario->IdUsuario,
                'nombre1' => $usuario->Nombre1,
                'nombre2' => $usuario->Nombre2,
                'apellido1' => $usuario->Apellido1,
                'apellido2' => $usuario->Apellido2,
                'nombre_completo' => $usuario->nombre_completo,
                'correo' => $usuario->Correo,
                'rol' => [
                    'id' => $usuario->rol->IdRol,
                    'nombre' => $usuario->rol->Nombre,
                ],
            ],
        ], 200);
    }
    /**
     * Logout: Revoca el token actual.
     */
    public function logout()
    {
        $user = request()->user();
        if ($user) {
            $user->currentAccessToken()->delete();
        }
        return response()->json([
            'mensaje' => 'Sesión cerrada correctamente',
        ], 200);
    }
}