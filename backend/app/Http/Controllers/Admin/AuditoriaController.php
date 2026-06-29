<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditoriaGeneral;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AuditoriaGeneral::query()->with('usuario:IdUsuario,Nombre1,Apellido1,Correo');

        if ($request->filled('tabla')) {
            $query->where('TablaNombre', $request->tabla);
        }

        if ($request->filled('accion')) {
            $query->where('Accion', $request->accion);
        }

        if ($request->filled('buscar')) {
            $buscar = '%' . $request->buscar . '%';
            $query->where(function ($q) use ($buscar) {
                $q->where('TablaNombre', 'like', $buscar)
                    ->orWhere('Accion', 'like', $buscar)
                    ->orWhere('Detalles', 'like', $buscar)
                    ->orWhere('RegistroId', 'like', $buscar);
            });
        }

        return response()->json([
            'ok' => true,
            'data' => $query->orderByDesc('FechaA')->paginate($request->integer('per_page', 20)),
        ]);
    }
}
