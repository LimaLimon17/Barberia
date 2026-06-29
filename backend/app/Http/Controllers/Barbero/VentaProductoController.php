<?php

namespace App\Http\Controllers\Barbero;

use App\Http\Controllers\Controller;
use App\Models\Barbero;
use App\Models\Cliente;
use App\Models\Comision;
use App\Models\DetalleVenta;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\Reserva;
use App\Models\Venta;
use App\Services\AuditoriaService;
use App\Services\InventarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VentaProductoController extends Controller
{
    public function __construct(
        private readonly InventarioService $inventarioService,
        private readonly AuditoriaService $auditoriaService
    ) {}

    public function catalogo(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => [
                'productos' => Producto::where(function ($q) {
                    $q->whereNull('EstadoA')->orWhere('EstadoA', 1);
                })->where('StockActual', '>', 0)->orderBy('Nombre')->get(),
                'barberos' => Barbero::with('usuario:IdUsuario,Nombre1,Apellido1,Correo')->where(function ($q) {
                    $q->whereNull('EstadoA')->orWhere('EstadoA', 1);
                })->get(),
                'clientes' => Cliente::where(function ($q) {
                    $q->whereNull('EstadoA')->orWhere('EstadoA', 1);
                })->orderBy('Nombre1')->get(),
                'reservas' => Reserva::where(function ($q) {
                    $q->whereNull('EstadoA')->orWhere('EstadoA', 1);
                })->orderByDesc('FechaCita')->limit(50)->get(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'IdBarbero' => ['required', 'integer', 'exists:Barberos,IdBarbero'],
            'IdCliente' => ['nullable', 'string', 'exists:Clientes,CI'],
            'IdReserva' => ['nullable', 'integer', 'exists:Reservas,IdReserva'],
            'Fecha' => ['nullable', 'date'],
            'MetodoPago' => ['nullable', 'string', 'max:50'],
            'UsuarioA' => ['nullable', 'integer'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.IdProducto' => ['required', 'integer', 'exists:Productos,IdProducto'],
            'items.*.Cantidad' => ['required', 'integer', 'min:1'],
        ]);

        $resultado = DB::transaction(function () use ($data) {
            $itemsAgrupados = collect($data['items'])
                ->groupBy('IdProducto')
                ->map(fn ($grupo, $idProducto) => [
                    'IdProducto' => (int) $idProducto,
                    'Cantidad' => $grupo->sum('Cantidad'),
                ])->values();

            $productos = Producto::whereIn('IdProducto', $itemsAgrupados->pluck('IdProducto'))->lockForUpdate()->get()->keyBy('IdProducto');
            $fecha = isset($data['Fecha']) ? Carbon::parse($data['Fecha']) : Carbon::now();
            $totalVenta = 0;
            $totalComision = 0;
            $detallesCalculados = [];

            foreach ($itemsAgrupados as $item) {
                $producto = $productos->get($item['IdProducto']);

                if (!$producto) {
                    throw ValidationException::withMessages(['items' => 'Uno de los productos no existe.']);
                }

                if ((int) $producto->StockActual < (int) $item['Cantidad']) {
                    throw ValidationException::withMessages([
                        'items' => "Stock insuficiente para {$producto->Nombre}. Stock actual: {$producto->StockActual}.",
                    ]);
                }

                $subtotal = round(((float) $producto->PrecioVenta) * (int) $item['Cantidad'], 2);
                $comision = round($subtotal * (((float) $producto->PorcentajeBarbero) / 100), 2);

                $totalVenta += $subtotal;
                $totalComision += $comision;

                $detallesCalculados[] = [
                    'producto' => $producto,
                    'cantidad' => (int) $item['Cantidad'],
                    'precioUnitario' => (float) $producto->PrecioVenta,
                    'comision' => $comision,
                ];
            }

            $venta = Venta::create([
                'IdBarbero' => $data['IdBarbero'],
                'IdCliente' => $data['IdCliente'] ?? null,
                'IdReserva' => $data['IdReserva'] ?? null,
                'Fecha' => $fecha,
                'MontoTotal' => round($totalVenta, 2),
                'EstadoA' => 1,
                'FechaA' => Carbon::now(),
                'UsuarioA' => $data['UsuarioA'] ?? null,
            ]);

            foreach ($detallesCalculados as $detalle) {
                DetalleVenta::create([
                    'IdVenta' => $venta->IdVenta,
                    'IdProducto' => $detalle['producto']->IdProducto,
                    'Cantidad' => $detalle['cantidad'],
                    'PrecioUnitario' => $detalle['precioUnitario'],
                    'ComisionBarbero' => $detalle['comision'],
                    'EstadoA' => 1,
                    'FechaA' => Carbon::now(),
                    'UsuarioA' => $data['UsuarioA'] ?? null,
                ]);

                $this->inventarioService->descontarStock($detalle['producto'], $detalle['cantidad']);
            }

            Comision::create([
                'IdBarbero' => $data['IdBarbero'],
                'IdReserva' => null,
                'IdVenta' => $venta->IdVenta,
                'TipoComision' => 'PRO',
                'Fecha' => $fecha,
                'MontoBase' => round($totalVenta, 2),
                'Porcentaje' => null,
                'MontoComision' => round($totalComision, 2),
                'EstadoA' => 1,
                'FechaA' => Carbon::now(),
                'UsuarioA' => $data['UsuarioA'] ?? null,
            ]);

            if (!empty($data['MetodoPago'])) {
                Pago::create([
                    'IdReserva' => null,
                    'IdVenta' => $venta->IdVenta,
                    'TipoPago' => 'VEN',
                    'Monto' => round($totalVenta, 2),
                    'FechaPago' => Carbon::now(),
                    'MetodoPago' => $data['MetodoPago'],
                    'EstadoPago' => 'PAGADO',
                    'EstadoA' => 1,
                    'FechaA' => Carbon::now(),
                    'UsuarioA' => $data['UsuarioA'] ?? null,
                ]);
            }

            $this->auditoriaService->registrar(
                'Ventas',
                $venta->IdVenta,
                'CREAR',
                null,
                null,
                $venta->load('detalles.producto')->toArray(),
                $data['UsuarioA'] ?? null,
                'Venta de productos registrada, stock descontado y comisión generada.'
            );

            return $venta->load(['detalles.producto', 'comision', 'pago']);
        });

        return response()->json(['ok' => true, 'data' => $resultado], 201);
    }
}
