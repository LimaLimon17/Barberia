<?php

namespace App\Http\Controllers\Barbero;

use App\Http\Controllers\Controller;
use App\Models\Barbero;
use App\Models\Comision;
use App\Models\Pago;
use App\Models\Reserva;
use App\Models\Venta;
use App\Services\PagoQRService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * PagoFinalController
 * ─────────────────────────────────────────────────────────────────
 * Fase 3 (parte 1): cobro del 50% restante de una cita Completada,
 * incluyendo los productos vendidos en esa misma cita (si los hay),
 * en un solo pago (QR o efectivo) y una sola Nota de Venta.
 * RF8, RF29, HU-07.
 */
class PagoFinalController extends Controller
{
    public function __construct(private PagoQRService $pagoQRService) {}

    // ──────────────────────────────────────────────────────────────
    // GET /api/barbero/citas/{idReserva}/pago-final/resumen
    // ──────────────────────────────────────────────────────────────
    public function resumen(Request $request, int $idReserva)
    {
        $barbero = $this->barberoAutenticado($request);
        $reserva = $this->reservaCompletadaDelBarbero($barbero, $idReserva);

        [$saldoServicio, $subtotalProductos, $total, $yaPagado] = $this->calcularTotales($reserva);

        return response()->json([
            'saldo_servicio' => $saldoServicio,
            'subtotal_productos' => $subtotalProductos,
            'total' => $total,
            'ya_pagado' => $yaPagado,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // POST /api/barbero/citas/{idReserva}/pago-final
    // body: { metodo_pago: 'Efectivo' | 'QR' }
    // Efectivo: registra todo de inmediato.
    // QR: solo devuelve el QR, no persiste nada todavía.
    // ──────────────────────────────────────────────────────────────
    public function iniciar(Request $request, int $idReserva)
    {
        $request->validate(['metodo_pago' => 'required|in:Efectivo,QR']);

        $barbero = $this->barberoAutenticado($request);
        $reserva = $this->reservaCompletadaDelBarbero($barbero, $idReserva);

        [, , $total, $yaPagado] = $this->calcularTotales($reserva);

        if ($yaPagado) {
            return response()->json(['error' => 'El saldo de esta cita ya fue pagado.'], 422);
        }

        if ($request->metodo_pago === 'QR') {
            $qr = $this->pagoQRService->generarQRMonto("SALDO-{$reserva->IdReserva}", $total);
            return response()->json(['pendiente' => true, 'qr' => $qr]);
        }

        $notaData = $this->registrarPagoYComisiones($barbero, $reserva, 'Efectivo', $request->user()->IdUsuario, $request->ip());
        return response()->json(['pendiente' => false, 'nota' => $notaData]);
    }

    // ──────────────────────────────────────────────────────────────
    // POST /api/barbero/citas/{idReserva}/pago-final/confirmar
    // El barbero confirma que el cliente ya pagó el QR.
    // ──────────────────────────────────────────────────────────────
    public function confirmar(Request $request, int $idReserva)
    {
        $barbero = $this->barberoAutenticado($request);
        $reserva = $this->reservaCompletadaDelBarbero($barbero, $idReserva);

        [, , , $yaPagado] = $this->calcularTotales($reserva);
        if ($yaPagado) {
            return response()->json(['error' => 'El saldo de esta cita ya fue pagado.'], 422);
        }

        $notaData = $this->registrarPagoYComisiones($barbero, $reserva, 'QR', $request->user()->IdUsuario, $request->ip());
        return response()->json(['nota' => $notaData]);
    }

    // ── Helper: calcula saldo del servicio + productos + total, y si ya se pagó ──
    private function calcularTotales(Reserva $reserva): array
    {
        $saldoServicio = round((float) $reserva->CostoTotal - (float) $reserva->MontoAnticipo, 2);

        $venta = Venta::where('IdReserva', $reserva->IdReserva)
            ->where('IdBarbero', $reserva->IdBarbero)
            ->first();
        $subtotalProductos = $venta ? (float) $venta->MontoTotal : 0.0;

        $total = round($saldoServicio + $subtotalProductos, 2);

        $yaPagado = Pago::where('IdReserva', $reserva->IdReserva)
            ->where('TipoPago', 'Saldo')
            ->exists();

        return [$saldoServicio, $subtotalProductos, $total, $yaPagado];
    }

    // ── Helper: persiste Pago(s), Comisión por servicio, y arma datos de Nota de Venta ──
    private function registrarPagoYComisiones(Barbero $barbero, Reserva $reserva, string $metodoPago, int $idUsuario, ?string $ip): array
    {
        DB::statement("SET @v_auditoria_ip = ?", [$ip]);

        return DB::transaction(function () use ($barbero, $reserva, $metodoPago, $idUsuario) {
            [$saldoServicio, $subtotalProductos, $total] = $this->calcularTotales($reserva);

            // Pago del saldo restante del servicio (50%).
            Pago::create([
                'IdReserva' => $reserva->IdReserva,
                'IdVenta' => null,
                'TipoPago' => 'Saldo',
                'Monto' => $saldoServicio,
                'FechaPago' => now(),
                'MetodoPago' => $metodoPago,
                'EstadoPago' => 'Pagado',
                'EstadoA' => 1,
                'FechaA' => now(),
                'UsuarioA' => $idUsuario,
            ]);

            // Si hubo productos vendidos en esta cita, también se registra
            // su pago (referenciado a la Venta, no a la Reserva).
            $venta = Venta::where('IdReserva', $reserva->IdReserva)
                ->where('IdBarbero', $barbero->IdBarbero)
                ->with('detalles.producto')
                ->first();

            if ($venta && (float) $venta->MontoTotal > 0) {
                Pago::create([
                    'IdReserva' => null,
                    'IdVenta' => $venta->IdVenta,
                    'TipoPago' => 'Total',
                    'Monto' => $venta->MontoTotal,
                    'FechaPago' => now(),
                    'MetodoPago' => $metodoPago,
                    'EstadoPago' => 'Pagado',
                    'EstadoA' => 1,
                    'FechaA' => now(),
                    'UsuarioA' => $idUsuario,
                ]);
            }

            // Comisión del 50% sobre el total bruto de los SERVICIOS (RF17).
            // Una sola vez por reserva (protegido por unique key en BD también).
            $yaTieneComisionServicio = Comision::where('IdReserva', $reserva->IdReserva)
                ->where('TipoComision', Comision::TIPO_SERVICIO)
                ->exists();

            if (!$yaTieneComisionServicio) {
                Comision::create([
                    'IdBarbero' => $barbero->IdBarbero,
                    'IdReserva' => $reserva->IdReserva,
                    'IdVenta' => null,
                    'TipoComision' => Comision::TIPO_SERVICIO,
                    'Fecha' => now(),
                    'MontoBase' => $reserva->CostoTotal,
                    'Porcentaje' => 50.00,
                    'MontoComision' => round((float) $reserva->CostoTotal * 0.5, 2),
                    'EstadoA' => 1,
                    'FechaA' => now(),
                    'UsuarioA' => $idUsuario,
                ]);
            }

            $reserva->load(['cliente', 'servicios']);

            // Datos listos para que el frontend arme la Nota de Venta en PDF.
            return [
                'numero' => 'CITA-' . $reserva->IdReserva,
                'fecha' => now()->toDateTimeString(),
                'barbero' => trim($barbero->usuario->Nombre1 . ' ' . $barbero->usuario->Apellido1),
                'cliente' => [
                    'nombre' => trim(($reserva->cliente->Nombre1 ?? '') . ' ' . ($reserva->cliente->Apellido1 ?? '')),
                    'telefono' => $reserva->cliente->Telefono ?? null,
                    'correo' => $reserva->cliente->Correo ?? null,
                ],
                'servicios' => $reserva->servicios->map(fn ($s) => [
                    'nombre' => $s->Nombre,
                    'duracion' => $s->DuracionMinutos,
                    'precio' => (float) $s->Precio,
                ]),
                'productos' => $venta ? $venta->detalles->map(fn ($d) => [
                    'nombre' => $d->producto?->Nombre,
                    'cantidad' => $d->Cantidad,
                    'precio_unitario' => (float) $d->PrecioUnitario,
                    'subtotal' => round($d->Cantidad * (float) $d->PrecioUnitario, 2),
                ]) : [],
                'subtotal_servicios' => (float) $reserva->CostoTotal,
                'subtotal_productos' => $subtotalProductos,
                'anticipo_ya_pagado' => (float) $reserva->MontoAnticipo,
                'saldo_pagado_ahora' => $saldoServicio,
                'total_pagado_ahora' => $total,
                'metodo_pago' => $metodoPago,
            ];
        });
    }

    private function reservaCompletadaDelBarbero(Barbero $barbero, int $idReserva): Reserva
    {
        $reserva = Reserva::where('IdReserva', $idReserva)
            ->where('IdBarbero', $barbero->IdBarbero)
            ->first();

        if (!$reserva) {
            abort(404, 'Cita no encontrada para este barbero.');
        }

        if ($reserva->EstadoReserva !== 'Completada') {
            abort(422, 'La cita debe estar marcada como Completada antes de cobrar el saldo.');
        }

        return $reserva;
    }

    private function barberoAutenticado(Request $request): Barbero
    {
        return Barbero::where('IdUsuario', $request->user()->IdUsuario)
            ->where('EstadoA', 1)
            ->with(['usuario' => fn ($q) => $q->select('IdUsuario', 'Nombre1', 'Apellido1')])
            ->firstOrFail();
    }
}