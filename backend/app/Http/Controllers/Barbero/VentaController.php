<?php

namespace App\Http\Controllers\Barbero;

use App\Http\Controllers\Controller;
use App\Models\Barbero;
use App\Models\Comision;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\Reserva;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * VentaController (Barbero)
 * ─────────────────────────────────────────────────────────────────
 * Fase 2: Venta de productos ligada a una cita Completada.
 * RF12 - Descuenta stock, calcula precio y comisión vigentes, registra
 *        la venta en el desglose y guarda los porcentajes aplicados
 *        (vía el snapshot de PrecioUnitario/ComisionBarbero por línea).
 * RF13 - El barbero ve el precio calculado, sin poder modificarlo.
 * HU-08 - Venta de productos.
 * HU-29 (RF29) - El botón "Vender" solo aplica sobre citas Completadas.
 */
class VentaController extends Controller
{
    // ──────────────────────────────────────────────────────────────
    // GET /api/barbero/productos
    // Catálogo con stock visible y precio ya calculado (no editable).
    // ──────────────────────────────────────────────────────────────
    public function productosDisponibles()
    {
        $productos = Producto::where('EstadoA', 1)
            ->orderBy('Nombre')
            ->get()
            ->map(fn ($p) => [
                'id_producto' => $p->IdProducto,
                'nombre' => $p->Nombre,
                'precio_venta' => (float) $p->PrecioVenta,
                'stock' => $p->StockActual,
            ]);

        return response()->json(['productos' => $productos]);
    }

    // ──────────────────────────────────────────────────────────────
    // GET /api/barbero/citas/{idReserva}/venta
    // Desglose actual de productos ya agregados a la venta de esa cita
    // (si aún no se vendió nada, retorna vacío).
    // ──────────────────────────────────────────────────────────────
    public function ventaDeLaCita(Request $request, int $idReserva)
    {
        $barbero = $this->barberoAutenticado($request);
        $reserva = $this->reservaDelBarbero($barbero, $idReserva);

        $venta = Venta::where('IdReserva', $reserva->IdReserva)
            ->where('IdBarbero', $barbero->IdBarbero)
            ->with('detalles.producto')
            ->first();

        if (!$venta) {
            return response()->json(['venta' => null, 'detalle' => [], 'monto_total' => 0]);
        }

        return response()->json([
            'venta' => ['id_venta' => $venta->IdVenta, 'monto_total' => (float) $venta->MontoTotal],
            'detalle' => $venta->detalles->map(fn ($d) => [
                'id_producto' => $d->IdProducto,
                'nombre' => $d->producto?->Nombre,
                'cantidad' => $d->Cantidad,
                'precio_unitario' => (float) $d->PrecioUnitario,
                'subtotal' => round($d->Cantidad * (float) $d->PrecioUnitario, 2),
            ]),
            'monto_total' => (float) $venta->MontoTotal,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // POST /api/barbero/citas/{idReserva}/venta
    // body: { productos: [{ id_producto, cantidad }, ...] }
    // Agrega productos a la venta de la cita (acumulable: si la línea
    // ya existe, suma la cantidad). Descuenta stock en la misma transacción.
    // ──────────────────────────────────────────────────────────────
    public function agregarProductos(Request $request, int $idReserva)
    {
        $request->validate([
            'productos' => 'required|array|min:1',
            'productos.*.id_producto' => 'required|integer|exists:Productos,IdProducto',
            'productos.*.cantidad' => 'required|integer|min:1',
        ]);

        $barbero = $this->barberoAutenticado($request);
        $reserva = $this->reservaDelBarbero($barbero, $idReserva);

        if ($reserva->EstadoReserva !== 'Completada') {
            return response()->json([
                'error' => 'Solo se pueden vender productos sobre una cita marcada como Completada.',
            ], 422);
        }

        DB::statement("SET @v_auditoria_ip = ?", [$request->ip()]);
        $idUsuarioAutenticado = $request->user()->IdUsuario;
        $lineas = collect($request->productos);

        try {
            $venta = DB::transaction(function () use ($barbero, $reserva, $lineas, $idUsuarioAutenticado) {
                // Bloquear los productos involucrados en orden estable (por Id)
                // para evitar deadlocks si dos ventas concurrentes tocan los
                // mismos productos.
                $idsProductos = $lineas->pluck('id_producto')->unique()->sort()->values();
                $productos = Producto::whereIn('IdProducto', $idsProductos)
                    ->where('EstadoA', 1)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('IdProducto');

                if ($productos->count() !== $idsProductos->count()) {
                    throw ValidationException::withMessages([
                        'productos' => 'Uno o más productos no están disponibles.',
                    ]);
                }

                // Validar stock suficiente para CADA línea antes de tocar nada.
                foreach ($lineas as $linea) {
                    $producto = $productos[$linea['id_producto']];
                    if ($producto->StockActual < $linea['cantidad']) {
                        throw ValidationException::withMessages([
                            'productos' => "Stock insuficiente de \"{$producto->Nombre}\". Disponible: {$producto->StockActual}.",
                        ]);
                    }
                }

                $venta = Venta::firstOrCreate(
                    ['IdReserva' => $reserva->IdReserva, 'IdBarbero' => $barbero->IdBarbero],
                    [
                        'IdCliente' => $reserva->IdCliente,
                        'Fecha' => now(),
                        'MontoTotal' => 0,
                        'EstadoA' => 1,
                        'FechaA' => now(),
                        'UsuarioA' => $idUsuarioAutenticado,
                    ]
                );

                foreach ($lineas as $linea) {
                    $producto = $productos[$linea['id_producto']];
                    $cantidad = (int) $linea['cantidad'];

                    // Descuenta stock en tiempo real (RF12/RF15).
                    $producto->decrement('StockActual', $cantidad);

                    $precioUnitario = (float) $producto->PrecioVenta;
                    // Comisión del barbero: porcentaje vigente aplicado sobre
                    // el costo de compra (mismo criterio que usa el admin al
                    // definir PorcentajeBarbero — ver sp_ActualizarPorcentajeProducto).
                    $comisionUnitaria = round((float) $producto->CostoCompra * ((float) $producto->PorcentajeBarbero / 100), 2);

                    $detalleExistente = DetalleVenta::where('IdVenta', $venta->IdVenta)
                        ->where('IdProducto', $producto->IdProducto)
                        ->first();

                    if ($detalleExistente) {
                        $detalleExistente->update([
                            'Cantidad' => $detalleExistente->Cantidad + $cantidad,
                            'ComisionBarbero' => $detalleExistente->ComisionBarbero + ($comisionUnitaria * $cantidad),
                        ]);
                    } else {
                        DetalleVenta::create([
                            'IdVenta' => $venta->IdVenta,
                            'IdProducto' => $producto->IdProducto,
                            'Cantidad' => $cantidad,
                            'PrecioUnitario' => $precioUnitario,
                            'ComisionBarbero' => $comisionUnitaria * $cantidad,
                            'EstadoA' => 1,
                            'FechaA' => now(),
                            'UsuarioA' => $idUsuarioAutenticado,
                        ]);
                    }
                }

                // Recalcular el monto total de la venta a partir del desglose.
                $montoTotal = DetalleVenta::where('IdVenta', $venta->IdVenta)
                    ->get()
                    ->sum(fn ($d) => $d->Cantidad * (float) $d->PrecioUnitario);

                $venta->update(['MontoTotal' => $montoTotal]);

                // Comisión por productos: UNA fila por venta (restricción
                // uq_comisiones_venta_tipo), se recalcula sumando todas las líneas.
                $comisionTotal = DetalleVenta::where('IdVenta', $venta->IdVenta)->sum('ComisionBarbero');

                Comision::updateOrCreate(
                    ['IdVenta' => $venta->IdVenta, 'TipoComision' => Comision::TIPO_PRODUCTO],
                    [
                        'IdBarbero' => $barbero->IdBarbero,
                        'IdReserva' => null,
                        'Fecha' => now(),
                        'MontoBase' => $montoTotal,
                        'Porcentaje' => null, // varía por producto; el detalle ya queda en DetalleVenta
                        'MontoComision' => $comisionTotal,
                        'EstadoA' => 1,
                        'FechaA' => now(),
                        'UsuarioA' => $idUsuarioAutenticado,
                    ]
                );

                return $venta->fresh('detalles.producto');
            });
        } catch (ValidationException $e) {
            return response()->json(['error' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json([
            'venta' => ['id_venta' => $venta->IdVenta, 'monto_total' => (float) $venta->MontoTotal],
            'detalle' => $venta->detalles->map(fn ($d) => [
                'id_producto' => $d->IdProducto,
                'nombre' => $d->producto?->Nombre,
                'cantidad' => $d->Cantidad,
                'precio_unitario' => (float) $d->PrecioUnitario,
                'subtotal' => round($d->Cantidad * (float) $d->PrecioUnitario, 2),
            ]),
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────
    private function reservaDelBarbero(Barbero $barbero, int $idReserva): Reserva
    {
        $reserva = Reserva::where('IdReserva', $idReserva)
            ->where('IdBarbero', $barbero->IdBarbero)
            ->first();

        if (!$reserva) {
            abort(404, 'Cita no encontrada para este barbero.');
        }

        return $reserva;
    }

    private function barberoAutenticado(Request $request): Barbero
    {
        return Barbero::where('IdUsuario', $request->user()->IdUsuario)
            ->where('EstadoA', 1)
            ->firstOrFail();
    }
}