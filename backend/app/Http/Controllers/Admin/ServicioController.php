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
            'UsuarioA' => ['nullable', 'integer'],
        ]);

        $categoria = Categoria::create(array_merge($data, [
            'EstadoA' => 1,
            'FechaA' => Carbon::now(),
        ]));

        $this->auditoriaService->registrar('Categorias', $categoria->IdCategoria, 'CREAR', null, null, $categoria->toArray(), $data['UsuarioA'] ?? null, 'Categoría creada.');

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
            'UsuarioA' => ['nullable', 'integer'],
        ]);

        $categoria->fill(array_merge($data, ['FechaA' => Carbon::now()]));
        $categoria->save();

        $this->auditoriaService->registrar('Categorias', $categoria->IdCategoria, 'ACTUALIZAR', null, $anterior, $categoria->toArray(), $data['UsuarioA'] ?? null, 'Categoría actualizada.');

        return response()->json(['ok' => true, 'data' => $categoria]);
    }

    public function desactivarCategoria(Request $request, int $id): JsonResponse
    {
        $categoria = Categoria::findOrFail($id);
        $anterior = $categoria->toArray();

        $categoria->EstadoA = 0;
        $categoria->FechaA = Carbon::now();
        $categoria->UsuarioA = $request->input('UsuarioA');
        $categoria->save();

        $this->auditoriaService->registrar('Categorias', $categoria->IdCategoria, 'DESACTIVAR', 'EstadoA', $anterior, $categoria->toArray(), $request->input('UsuarioA'), 'Categoría desactivada.');

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
            'UsuarioA' => ['nullable', 'integer'],
        ]);

        $servicio = Servicio::create(array_merge($data, [
            'EstadoA' => 1,
            'FechaA' => Carbon::now(),
        ]));

        $this->auditoriaService->registrar('Servicios', $servicio->IdServicio, 'CREAR', null, null, $servicio->toArray(), $data['UsuarioA'] ?? null, 'Servicio creado.');

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
            'UsuarioA' => ['nullable', 'integer'],
        ]);

        $servicio->fill(array_merge($data, ['FechaA' => Carbon::now()]));
        $servicio->save();

        $this->auditoriaService->registrar('Servicios', $servicio->IdServicio, 'ACTUALIZAR', null, $anterior, $servicio->toArray(), $data['UsuarioA'] ?? null, 'Servicio actualizado.');

        return response()->json(['ok' => true, 'data' => $servicio->load('categoria')]);
    }

    public function desactivar(Request $request, int $id): JsonResponse
    {
        $servicio = Servicio::findOrFail($id);
        $anterior = $servicio->toArray();

        $servicio->EstadoA = 0;
        $servicio->FechaA = Carbon::now();
        $servicio->UsuarioA = $request->input('UsuarioA');
        $servicio->save();

        $this->auditoriaService->registrar('Servicios', $servicio->IdServicio, 'DESACTIVAR', 'EstadoA', $anterior, $servicio->toArray(), $request->input('UsuarioA'), 'Servicio desactivado.');

        return response()->json(['ok' => true, 'data' => $servicio]);
    }
}
