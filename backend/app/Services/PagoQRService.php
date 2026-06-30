<?php

namespace App\Services;

use App\Models\Reserva;

/**
 * Encapsula la generación del QR de cobro. Hoy genera un payload simulado;
 * el punto de integración real (API del banco/pasarela QR boliviana) se
 * conecta aquí sin tocar el resto del flujo de reservas.
 */
class PagoQRService
{
    public function generarQR(Reserva $reserva): array
    {
        // De momento se devuelve un payload determinístico para renderizar
        // un QR (por ejemplo con una librería tipo simple-qrcode) y un id
        // de transacción simulado para pruebas manuales.
        $referencia = 'RES-' . $reserva->IdReserva . '-' . now()->timestamp;

        return [
            'referencia' => $referencia,
            'monto' => (float) $reserva->MontoAnticipo,
            'moneda' => 'BOB',
            // Cadena que se codifica en el QR (URL/payload de la pasarela real).
            'payload_qr' => "BARBERIA|{$referencia}|{$reserva->MontoAnticipo}",
            'expira_en_segundos' => 60 * \App\Services\ReservaService::MINUTOS_EXPIRACION_PAGO,
        ];
    }

    /**
     * Simula la confirmación de pago recibida desde la pasarela (webhook).
     * En producción este método sería invocado por el controlador del
     * webhook de la pasarela real, no manualmente desde el frontend.
     */
    public function simularConfirmacionWebhook(string $referencia): bool
    {
        // TODO: validar firma/token del proveedor real.
        return true;
    }
    /**
 * Genera un QR para cualquier monto/referencia, sin depender de una Reserva.
 * Usado por: pago final de cita (saldo + productos) y venta directa sin cita.
 */
public function generarQRMonto(string $prefijoReferencia, float $monto): array
{
    $referencia = $prefijoReferencia . '-' . now()->timestamp;

    return [
        'referencia' => $referencia,
        'monto' => $monto,
        'moneda' => 'BOB',
        'payload_qr' => "BARBERIA|{$referencia}|{$monto}",
    ];
}
}
