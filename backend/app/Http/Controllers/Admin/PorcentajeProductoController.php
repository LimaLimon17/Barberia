<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Services\AuditoriaService;
use App\Services\PorcentajeProductoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PorcentajeProductoController extends Controller
{
    public function __construct(
        private readonly PorcentajeProductoService $porcentajeProductoService,
        private readonly AuditoriaService $auditoriaService
    ) {}

    public function historial(int $id): JsonResponse
    {
        $producto = Producto::with(['historialPorcentajes' => fn ($q) => $q->orderByDesc('FechaInicio')->orderByDesc('IdHistorial')])
            ->findOrFail($id);

        return response()->json([
            'ok' => true,
            'data' => [
                'producto' => $producto,
                'historial' => $producto->historialPorcentajes,
            ],
        ]);
    }

    public function actualizar(Request $request, int $id): JsonResponse
    {
        $producto = Producto::findOrFail($id);
        $anterior = $producto->only(['PrecioVenta', 'PorcentajeVenta', 'PorcentajeBarbero']);

        $data = $request->validate([
            'PrecioVenta' => ['required', 'numeric', 'min:0'],
            'PorcentajeVenta' => ['required', 'numeric', 'min:0'],
            'PorcentajeBarbero' => ['required', 'numeric', 'min:0'],
            'FechaInicio' => ['nullable', 'date'],
        ]);

        $idUsuario = $request->user()->IdUsuario;
        $data['UsuarioA'] = $idUsuario;

        $producto = $this->porcentajeProductoService->actualizarPorcentajes($producto, $data);

        $this->auditoriaService->registrar(
            'HistorialPorcentajeProductos',
            $producto->IdProducto,
            'ACTUALIZAR_PORCENTAJES',
            'PrecioVenta/PorcentajeVenta/PorcentajeBarbero',
            $anterior,
            $producto->only(['PrecioVenta', 'PorcentajeVenta', 'PorcentajeBarbero']),
            $idUsuario,
            'Cambio de porcentajes y precio vigente del producto.'
        );

        return response()->json(['ok' => true, 'data' => $producto]);
    }
}