<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Verifica que el usuario autenticado tenga el rol requerido.
     * Uso: ->middleware('role:admin') o ->middleware('role:barbero')
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'mensaje' => 'No autenticado',
            ], 401);
        }

        $rolesPermitidos = [
            'admin' => 1,
            'barbero' => 2,
        ];

        if (!isset($rolesPermitidos[$role])) {
            return response()->json([
                'mensaje' => 'Rol no válido',
            ], 403);
        }

        if ($user->IdRol !== $rolesPermitidos[$role]) {
            return response()->json([
                'mensaje' => 'No tiene permisos para acceder a este recurso',
            ], 403);
        }

        return $next($request);
    }
}
