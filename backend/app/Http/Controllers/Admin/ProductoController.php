<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Services\AuditoriaService;
use App\Services\InventarioService;
use App\Services\PorcentajeProductoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductoController extends Controller
{
    public function __construct(
        private readonly AuditoriaService $auditoriaService,
        private readonly InventarioService $inventarioService,
        private readonly PorcentajeProductoService $porcentajeProductoService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Producto::with(['historialActual']);

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
            'Nombre' => ['required', 'string', 'max:100', Rule::unique('Productos', 'Nombre')],
            'CostoCompra' => ['required', 'numeric', 'min:0'],
            'PrecioVenta' => ['required', 'numeric', 'min:0'],
            'PorcentajeVenta' => ['required', 'numeric', 'min:0'],
            'PorcentajeBarbero' => ['required', 'numeric', 'min:0'],
            'StockActual' => ['required', 'integer', 'min:0'],
            'UsuarioA' => ['nullable', 'integer'],
        ]);

        $producto = DB::transaction(function () use ($data) {
            $producto = Producto::create(array_merge($data, [
                'EstadoA' => 1,
                'FechaA' => Carbon::now(),
            ]));

            $this->porcentajeProductoService->crearHistorialInicial($producto, $data['UsuarioA'] ?? null);

            $this->auditoriaService->registrar(
                'Productos',
                $producto->IdProducto,
                'CREAR',
                null,
                null,
                $producto->toArray(),
                $data['UsuarioA'] ?? null,
                'Producto creado con historial inicial de porcentajes.'
            );

            return $producto->fresh(['historialActual']);
        });

        return response()->json(['ok' => true, 'data' => $producto], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $producto = Producto::findOrFail($id);
        $anterior = $producto->toArray();

        $data = $request->validate([
            'Nombre' => ['required', 'string', 'max:100', Rule::unique('Productos', 'Nombre')->ignore($producto->IdProducto, 'IdProducto')],
            'CostoCompra' => ['required', 'numeric', 'min:0'],
            'PrecioVenta' => ['required', 'numeric', 'min:0'],
            'PorcentajeVenta' => ['required', 'numeric', 'min:0'],
            'PorcentajeBarbero' => ['required', 'numeric', 'min:0'],
            'StockActual' => ['required', 'integer', 'min:0'],
            'UsuarioA' => ['nullable', 'integer'],
        ]);

        $producto = DB::transaction(function () use ($producto, $data, $anterior) {
            $cambioPorcentajes =
                (string) $producto->PrecioVenta !== (string) $data['PrecioVenta'] ||
                (string) $producto->PorcentajeVenta !== (string) $data['PorcentajeVenta'] ||
                (string) $producto->PorcentajeBarbero !== (string) $data['PorcentajeBarbero'];

            $producto->fill(array_merge($data, [
                'FechaA' => Carbon::now(),
            ]));
            $producto->save();

            if ($cambioPorcentajes) {
                $this->porcentajeProductoService->actualizarPorcentajes($producto, [
                    'PrecioVenta' => $data['PrecioVenta'],
                    'PorcentajeVenta' => $data['PorcentajeVenta'],
                    'PorcentajeBarbero' => $data['PorcentajeBarbero'],
                    'UsuarioA' => $data['UsuarioA'] ?? null,
                ]);
            }

            $this->auditoriaService->registrar(
                'Productos',
                $producto->IdProducto,
                'ACTUALIZAR',
                null,
                $anterior,
                $producto->fresh()->toArray(),
                $data['UsuarioA'] ?? null,
                'Producto actualizado.'
            );

            return $producto->fresh(['historialActual']);
        });

        return response()->json(['ok' => true, 'data' => $producto]);
    }

    public function desactivar(Request $request, int $id): JsonResponse
    {
        $producto = Producto::findOrFail($id);
        $anterior = $producto->toArray();

        $producto->EstadoA = 0;
        $producto->FechaA = Carbon::now();
        $producto->UsuarioA = $request->input('UsuarioA');
        $producto->save();

        $this->auditoriaService->registrar(
            'Productos',
            $producto->IdProducto,
            'DESACTIVAR',
            'EstadoA',
            $anterior,
            $producto->toArray(),
            $request->input('UsuarioA'),
            'Producto desactivado.'
        );

        return response()->json(['ok' => true, 'data' => $producto]);
    }

    public function registrarLote(Request $request, int $id): JsonResponse
    {
        $producto = Producto::findOrFail($id);

        $data = $request->validate([
            'CantidadRecibida' => ['required', 'integer', 'min:1'],
            'CostoUnitario' => ['required', 'numeric', 'min:0'],
            'FechaIngreso' => ['nullable', 'date'],
            'UsuarioA' => ['nullable', 'integer'],
        ]);

        $stockAnterior = $producto->StockActual;
        $lote = $this->inventarioService->registrarEntrada($producto, $data);
        $productoActualizado = $producto->fresh();

        $this->auditoriaService->registrar(
            'Lotes',
            $lote->IdLote,
            'CREAR',
            'StockActual',
            ['StockActual' => $stockAnterior],
            ['StockActual' => $productoActualizado->StockActual, 'Lote' => $lote->toArray()],
            $data['UsuarioA'] ?? null,
            'Ingreso de stock por lote.'
        );

        return response()->json([
            'ok' => true,
            'data' => [
                'lote' => $lote,
                'producto' => $productoActualizado,
            ],
        ], 201);
    }
}
