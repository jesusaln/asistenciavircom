<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MirageScraperService;

class ScrapeMirageRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:scrape-mirage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa nuevas solicitudes de servicio desde el portal de Mirage Postventa';

    /**
     * Execute the console command.
     */
    public function handle(MirageScraperService $scraper)
    {
        $this->info('Iniciando escaneo de Mirage...');
        
        $result = $scraper->scrapeAndImport();

        if ($result['success']) {
            $this->success($result['message']);
        } else {
            $this->error($result['message']);
        }
    }
}
