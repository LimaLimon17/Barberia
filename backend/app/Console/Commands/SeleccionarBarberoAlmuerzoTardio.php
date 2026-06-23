<?php

namespace App\Console\Commands;

use App\Services\AlmuerzoService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Command para que la asignacion de que barbero se queda durante la hora de almuerzo
 * sea automática cada semana, sin repetir al barbero de la semana anterior. 
 * Si se usa se puede ejecutar cada lunes con:
 *   $schedule->command('almuerzo:rotar-semana')->weeklyOn(1, '00:05');
 * Sino, lo borramos
 */
class SeleccionarBarberoAlmuerzoTardio extends Command
{
    protected $signature = 'almuerzo:rotar-semana';
    protected $description = 'Selecciona aleatoriamente, sin repetir la semana anterior, al barbero con almuerzo tardío 13:00-14:00 de la semana actual';

    public function handle(AlmuerzoService $almuerzoService): int
    {
        $hoy = Carbon::now();
        $turno = $almuerzoService->seleccionarBarberoTardioParaSemana(
            (int) $hoy->isoWeek(),
            (int) $hoy->isoWeekYear()
        );

        $this->info("Barbero tardío semana {$turno->Semana}/{$turno->Año}: IdBarbero={$turno->IdBarbero}" .
            ($turno->IdBarberoSustituto ? ", sustituto IdBarbero={$turno->IdBarberoSustituto} el día {$turno->DiaSustituto}" : ''));

        return self::SUCCESS;
    }
}
