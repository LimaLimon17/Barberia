<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ComisionService;

class ConsolidarComisiones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'comisiones:consolidar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consolida las comisiones semanales de los barberos (HU-10/RF19)';

    protected $comisionService;

    public function __construct(ComisionService $comisionService)
    {
        parent::__construct();
        $this->comisionService = $comisionService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando consolidación de comisiones...');
        
        $total = $this->comisionService->consolidarSemana();
        
        $this->info("Consolidación finalizada exitosamente. Se generaron {$total} registros de comisión.");
        return 0;
    }
}
