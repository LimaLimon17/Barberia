<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Servicio;
use App\Services\AuditoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ServicioController extends Controller
{
    public function __construct(private readonly AuditoriaService $auditoriaService) {}

    public function categorias(Request $request): JsonResponse
    {
        $query = Categoria::query();

        if ($request->boolean('soloActivas', true)) {
            $query->where(function ($q) {
                $q->whereNull('EstadoA')->orWhere('EstadoA', 1);
            });
        }

        return response()->json([
            'ok' => true,
            'data' => $query->orderBy('Nombre')->get(),
        ]);
    }

    public function storeCategoria(Request $request): JsonResponse
    {
        $data = $request->validate([
            'Nombre' => ['required', 'string', 'max:100', Rule::unique('Categorias', 'Nombre')],
            'DuracionMinimaMinutos' => ['required', 'integer', 'min:1'],
            'DuracionMaximaMinutos' => ['required', 'integer', 'gte:DuracionMinimaMinutos'],
            'PrecioMin' => ['required', 'numeric', 'min:0'],
            'PrecioMax' => ['required', 'numeric', 'gte:PrecioMin'],
        ]);

        $idUsuario = $request->user()->IdUsuario;

        $categoria = Categoria::create(array_merge($data, [
            'EstadoA' => 1,
            'FechaA' => Carbon::now(),
            'UsuarioA' => $idUsuario,
        ]));

        $this->auditoriaService->registrar('Categorias', $categoria->IdCategoria, 'CREAR', null, null, $categoria->toArray(), $idUsuario, 'Categoría creada.');

        return response()->json(['ok' => true, 'data' => $categoria], 201);
    }

    public function updateCategoria(Request $request, int $id): JsonResponse
    {
        $categoria = Categoria::findOrFail($id);
        $anterior = $categoria->toArray();

        $data = $request->validate([
            'Nombre' => ['required', 'string', 'max:100', Rule::unique('Categorias', 'Nombre')->ignore($categoria->IdCategoria, 'IdCategoria')],
            'DuracionMinimaMinutos' => ['required', 'integer', 'min:1'],
            'DuracionMaximaMinutos' => ['required', 'integer', 'gte:DuracionMinimaMinutos'],
            'PrecioMin' => ['required', 'numeric', 'min:0'],
            'PrecioMax' => ['required', 'numeric', 'gte:PrecioMin'],
        ]);

        $idUsuario = $request->user()->IdUsuario;

        $categoria->fill(array_merge($data, [
            'FechaA' => Carbon::now(),
            'UsuarioA' => $idUsuario,
        ]));
        $categoria->save();

        $this->auditoriaService->registrar('Categorias', $categoria->IdCategoria, 'ACTUALIZAR', null, $anterior, $categoria->toArray(), $idUsuario, 'Categoría actualizada.');

        return response()->json(['ok' => true, 'data' => $categoria]);
    }

    public function desactivarCategoria(Request $request, int $id): JsonResponse
    {
        $categoria = Categoria::findOrFail($id);
        $anterior = $categoria->toArray();
        $idUsuario = $request->user()->IdUsuario;

        $categoria->EstadoA = 0;
        $categoria->FechaA = Carbon::now();
        $categoria->UsuarioA = $idUsuario;
        $categoria->save();

        $this->auditoriaService->registrar('Categorias', $categoria->IdCategoria, 'DESACTIVAR', 'EstadoA', $anterior, $categoria->toArray(), $idUsuario, 'Categoría desactivada.');

        return response()->json(['ok' => true, 'data' => $categoria]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Servicio::with('categoria');

        if ($request->boolean('soloActivos', true)) {
            $query->where(function ($q) {
                $q->whereNull('EstadoA')->orWhere('EstadoA', 1);
            });
        }

        if ($request->filled('buscar')) {
            $query->where('Nombre', 'like', '%' . $request->buscar . '%');
        }

        return response()->json([
            'ok' => true,
            'data' => $query->orderBy('Nombre')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'IdCategoria' => ['required', 'integer', 'exists:Categorias,IdCategoria'],
            'Nombre' => ['required', 'string', 'max:100', Rule::unique('Servicios', 'Nombre')],
            'FotoURL' => ['nullable', 'string', 'max:255'],
            'Precio' => ['required', 'numeric', 'min:0'],
            'DuracionMinutos' => ['required', 'integer', 'min:1'],
        ]);

        $idUsuario = $request->user()->IdUsuario;

        $servicio = Servicio::create(array_merge($data, [
            'EstadoA' => 1,
            'FechaA' => Carbon::now(),
            'UsuarioA' => $idUsuario,
        ]));

        $this->auditoriaService->registrar('Servicios', $servicio->IdServicio, 'CREAR', null, null, $servicio->toArray(), $idUsuario, 'Servicio creado.');

        return response()->json(['ok' => true, 'data' => $servicio->load('categoria')], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $servicio = Servicio::findOrFail($id);
        $anterior = $servicio->toArray();

        $data = $request->validate([
            'IdCategoria' => ['required', 'integer', 'exists:Categorias,IdCategoria'],
            'Nombre' => ['required', 'string', 'max:100', Rule::unique('Servicios', 'Nombre')->ignore($servicio->IdServicio, 'IdServicio')],
            'FotoURL' => ['nullable', 'string', 'max:255'],
            'Precio' => ['required', 'numeric', 'min:0'],
            'DuracionMinutos' => ['required', 'integer', 'min:1'],
        ]);

        $idUsuario = $request->user()->IdUsuario;

        $servicio->fill(array_merge($data, [
            'FechaA' => Carbon::now(),
            'UsuarioA' => $idUsuario,
        ]));
        $servicio->save();

        $this->auditoriaService->registrar('Servicios', $servicio->IdServicio, 'ACTUALIZAR', null, $anterior, $servicio->toArray(), $idUsuario, 'Servicio actualizado.');

        return response()->json(['ok' => true, 'data' => $servicio->load('categoria')]);
    }

    public function desactivar(Request $request, int $id): JsonResponse
    {
        $servicio = Servicio::findOrFail($id);
        $anterior = $servicio->toArray();
        $idUsuario = $request->user()->IdUsuario;

        $servicio->EstadoA = 0;
        $servicio->FechaA = Carbon::now();
        $servicio->UsuarioA = $idUsuario;
        $servicio->save();

        $this->auditoriaService->registrar('Servicios', $servicio->IdServicio, 'DESACTIVAR', 'EstadoA', $anterior, $servicio->toArray(), $idUsuario, 'Servicio desactivado.');

        return response()->json(['ok' => true, 'data' => $servicio]);
    }
}