<?php

namespace App\Jobs;

use App\Models\Reserva;
use App\Services\ReservaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Se despacha con delay(15 minutos) justo al crear la reserva en estado
 * Pendiente (HU-05 Escenario 3). Si para entonces sigue Pendiente, la
 * cancela/expira y libera el bloque horario automáticamente.
 */
class ExpirarReservaPendiente implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $idReserva)
    {
    }

    public function handle(ReservaService $reservaService): void
    {
        $reserva = Reserva::find($this->idReserva);

        if (!$reserva) {
            return;
        }

        $reservaService->expirarSiCorresponde($reserva);
    }
}
