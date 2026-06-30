<?php

namespace App\Http\Controllers\Barbero;

use App\Http\Controllers\Controller;
use App\Models\Barbero;
use App\Models\Cliente;
use App\Models\Comision;
use App\Models\DetalleVenta;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\PagoQRService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VentaDirectaController extends Controller
{
    public function __construct(private PagoQRService $pagoQRService) {}

    // ──────────────────────────────────────────────────────────────
    // POST /api/barbero/venta-directa
    // body: { ci, nombre1, apellido1, telefono?, correo?, productos:[{id_producto,cantidad}], metodo_pago }
    // Efectivo: registra todo de inmediato.
    // QR: solo valida stock y devuelve el QR; NO descuenta stock ni
    //     persiste nada hasta que se llame /confirmar con el mismo payload.
    // ──────────────────────────────────────────────────────────────
    public function iniciar(Request $request)
    {
        $datos = $this->validarPayload($request);
        $barbero = $this->barberoAutenticado($request);

        $total = $this->calcularTotalYValidarStock($datos['productos']);

        if ($datos['metodo_pago'] === 'QR') {
            $qr = $this->pagoQRService->generarQRMonto('VENTA-DIRECTA', $total);
            return response()->json(['pendiente' => true, 'qr' => $qr]);
        }

        $nota = $this->registrarVenta($barbero, $datos, $request->user()->IdUsuario, $request->ip());
        return response()->json(['pendiente' => false, 'nota' => $nota]);
    }

    // ──────────────────────────────────────────────────────────────
    // POST /api/barbero/venta-directa/confirmar
    // Mismo body que /iniciar. Se llama tras confirmar el pago QR.
    // ──────────────────────────────────────────────────────────────
    public function confirmar(Request $request)
    {
        $datos = $this->validarPayload($request);
        $barbero = $this->barberoAutenticado($request);

        $nota = $this->registrarVenta($barbero, $datos, $request->user()->IdUsuario, $request->ip());
        return response()->json(['nota' => $nota]);
    }

    private function validarPayload(Request $request): array
    {
        return $request->validate([
            'ci' => 'required|string|max:20',
            'nombre1' => 'required|string|max:50',
            'apellido1' => 'required|string|max:50',
            'telefono' => 'nullable|string|max:20',
            'correo' => 'nullable|email|max:100',
            'productos' => 'required|array|min:1',
            'productos.*.id_producto' => 'required|integer|exists:Productos,IdProducto',
            'productos.*.cantidad' => 'required|integer|min:1',
            'metodo_pago' => 'required|in:Efectivo,QR',
        ], [
            'ci.required' => 'El CI del cliente es obligatorio.',
            'nombre1.required' => 'El nombre es obligatorio.',
            'apellido1.required' => 'El apellido es obligatorio.',
            'productos.required' => 'Debe seleccionar al menos un producto.',
        ]);
    }

    // ── Valida stock disponible (sin descontar) y retorna el total ──
    private function calcularTotalYValidarStock(array $lineas): float
    {
        $ids = collect($lineas)->pluck('id_producto')->unique();
        $productos = Producto::whereIn('IdProducto', $ids)->where('EstadoA', 1)->get()->keyBy('IdProducto');

        if ($productos->count() !== $ids->count()) {
            throw ValidationException::withMessages(['productos' => 'Uno o más productos no están disponibles.']);
        }

        $total = 0;
        foreach ($lineas as $linea) {
            $producto = $productos[$linea['id_producto']];
            if ($producto->StockActual < $linea['cantidad']) {
                throw ValidationException::withMessages([
                    'productos' => "Stock insuficiente de \"{$producto->Nombre}\". Disponible: {$producto->StockActual}.",
                ]);
            }
            $total += (float) $producto->PrecioVenta * $linea['cantidad'];
        }

        return round($total, 2);
    }

    private function registrarVenta(Barbero $barbero, array $datos, int $idUsuario, ?string $ip): array
    {
        DB::statement("SET @v_auditoria_ip = ?", [$ip]);

        try {
            return DB::transaction(function () use ($barbero, $datos, $idUsuario) {
                $idsProductos = collect($datos['productos'])->pluck('id_producto')->unique()->sort()->values();
                $productos = Producto::whereIn('IdProducto', $idsProductos)
                    ->where('EstadoA', 1)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('IdProducto');

                foreach ($datos['productos'] as $linea) {
                    $producto = $productos[$linea['id_producto']] ?? null;
                    if (!$producto || $producto->StockActual < $linea['cantidad']) {
                        throw ValidationException::withMessages([
                            'productos' => "Stock insuficiente de \"" . ($producto->Nombre ?? '???') . "\".",
                        ]);
                    }
                }

                $cliente = Cliente::updateOrCreate(
                    ['CI' => $datos['ci']],
                    [
                        'Nombre1' => $datos['nombre1'],
                        'Apellido1' => $datos['apellido1'],
                        'Telefono' => $datos['telefono'] ?? null,
                        'Correo' => $datos['correo'] ?? null,
                        'EstadoA' => 1,
                        'FechaA' => now(),
                        'UsuarioA' => $idUsuario,
                    ]
                );

                $venta = Venta::create([
                    'IdBarbero' => $barbero->IdBarbero,
                    'IdCliente' => $cliente->CI,
                    'IdReserva' => null,
                    'Fecha' => now(),
                    'MontoTotal' => 0,
                    'EstadoA' => 1,
                    'FechaA' => now(),
                    'UsuarioA' => $idUsuario,
                ]);

                $montoTotal = 0;
                $comisionTotal = 0;
                $detalleParaNota = [];

                foreach ($datos['productos'] as $linea) {
                    $producto = $productos[$linea['id_producto']];
                    $cantidad = (int) $linea['cantidad'];

                    $producto->decrement('StockActual', $cantidad);

                    $precioUnitario = (float) $producto->PrecioVenta;
                    $comisionUnitaria = round((float) $producto->CostoCompra * ((float) $producto->PorcentajeBarbero / 100), 2);
                    $subtotal = round($precioUnitario * $cantidad, 2);

                    DetalleVenta::create([
                        'IdVenta' => $venta->IdVenta,
                        'IdProducto' => $producto->IdProducto,
                        'Cantidad' => $cantidad,
                        'PrecioUnitario' => $precioUnitario,
                        'ComisionBarbero' => $comisionUnitaria * $cantidad,
                        'EstadoA' => 1,
                        'FechaA' => now(),
                        'UsuarioA' => $idUsuario,
                    ]);

                    $montoTotal += $subtotal;
                    $comisionTotal += $comisionUnitaria * $cantidad;
                    $detalleParaNota[] = [
                        'nombre' => $producto->Nombre,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precioUnitario,
                        'subtotal' => $subtotal,
                    ];
                }

                $venta->update(['MontoTotal' => round($montoTotal, 2)]);

                Comision::create([
                    'IdBarbero' => $barbero->IdBarbero,
                    'IdReserva' => null,
                    'IdVenta' => $venta->IdVenta,
                    'TipoComision' => Comision::TIPO_PRODUCTO,
                    'Fecha' => now(),
                    'MontoBase' => round($montoTotal, 2),
                    'Porcentaje' => null,
                    'MontoComision' => round($comisionTotal, 2),
                    'EstadoA' => 1,
                    'FechaA' => now(),
                    'UsuarioA' => $idUsuario,
                ]);

                Pago::create([
                    'IdReserva' => null,
                    'IdVenta' => $venta->IdVenta,
                    'TipoPago' => 'Total',
                    'Monto' => round($montoTotal, 2),
                    'FechaPago' => now(),
                    'MetodoPago' => $datos['metodo_pago'],
                    'EstadoPago' => 'Pagado',
                    'EstadoA' => 1,
                    'FechaA' => now(),
                    'UsuarioA' => $idUsuario,
                ]);

                return [
                    'numero' => 'VENTA-' . $venta->IdVenta,
                    'fecha' => now()->toDateTimeString(),
                    'barbero' => trim($barbero->usuario->Nombre1 . ' ' . $barbero->usuario->Apellido1),
                    'cliente' => [
                        'nombre' => trim($cliente->Nombre1 . ' ' . $cliente->Apellido1),
                        'telefono' => $cliente->Telefono,
                        'correo' => $cliente->Correo,
                    ],
                    'servicios' => [],
                    'productos' => $detalleParaNota,
                    'subtotal_servicios' => 0,
                    'subtotal_productos' => round($montoTotal, 2),
                    'anticipo_ya_pagado' => 0,
                    'saldo_pagado_ahora' => round($montoTotal, 2),
                    'total_pagado_ahora' => round($montoTotal, 2),
                    'metodo_pago' => $datos['metodo_pago'],
                ];
            });
        } catch (ValidationException $e) {
            throw $e;
        }
    }

    private function barberoAutenticado(Request $request): Barbero
    {
        return Barbero::where('IdUsuario', $request->user()->IdUsuario)
            ->where('EstadoA', 1)
            ->with(['usuario' => fn ($q) => $q->select('IdUsuario', 'Nombre1', 'Apellido1')])
            ->firstOrFail();
    }
}