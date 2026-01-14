<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ImportSatClaves extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sat:import-claves {--force : Sobrescribir registros existentes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa el catálogo completo de Claves de Productos y Servicios del SAT (52,000+ registros)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ini_set('memory_limit', '512M');
        $url = 'https://raw.githubusercontent.com/bambucode/catalogos_sat_JSON/master/c_ClaveProdServ.json';
        
        $this->info("Conectando con el repositorio del SAT...");
        
        try {
            $response = Http::withoutVerifying()->get($url);
            
            if (!$response->successful()) {
                $this->error("No se pudo descargar el catálogo desde la fuente remota.");
                return 1;
            }

            $items = $response->json();
            
            if (!is_array($items)) {
                $this->error("El formato del JSON no es un array.");
                return 1;
            }

            $total = count($items);
            $this->info("Se han encontrado {$total} registros. Iniciando importación...");

            $bar = $this->output->createProgressBar($total);
            $bar->start();

            $chunkSize = 500;
            $chunks = array_chunk($items, $chunkSize);

            foreach ($chunks as $chunk) {
                $toInsert = [];
                foreach ($chunk as $item) {
                    $toInsert[] = [
                        'clave' => $item['id'],
                        'descripcion' => $item['descripcion'],
                        'activo' => true,
                        'importado_xml' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // Usamos insertOrIgnore para evitar duplicados si ya existen algunos
                DB::table('sat_claves_prod_serv')->insertOrIgnore($toInsert);
                
                $bar->advance(count($chunk));
            }

            $bar->finish();
            $this->newLine();
            $this->info("Importación completada con éxito. Tu sistema ahora tiene el catálogo completo del SAT.");

        } catch (\Exception $e) {
            $this->error("Error durante la importación: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
