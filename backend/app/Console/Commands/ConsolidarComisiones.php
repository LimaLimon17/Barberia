<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ComisionService;
use Carbon\Carbon;

class ConsolidarComisiones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:consolidar-comisiones';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consolida las comisiones de los barberos para la semana actual';

    /**
     * Execute the console command.
     */
    public function handle(ComisionService $comisionService)
    {
        $this->info('Iniciando consolidación de comisiones...');
        
        $fechaInicio = Carbon::now()->startOfWeek();
        $fechaFin = Carbon::now()->endOfWeek();

        $registros = $comisionService->consolidarSemana($fechaInicio, $fechaFin);

        $this->info("Consolidación finalizada. Registros creados: {$registros}");
    }
}
