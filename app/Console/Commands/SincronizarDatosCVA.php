<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CVAService;
use App\Models\Marca;
use App\Models\Categoria;
use Illuminate\Support\Facades\Log;

class SincronizarDatosCVA extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cva:sincronizar-datos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza marcas y categorías (grupos) desde el catálogo de CVA';

    /**
     * Execute the console command.
     */
    public function handle(CVAService $cvaService)
    {
        $this->info('Iniciando sincronización de datos CVA...');

        // 1. Obtener catálogo completo
        $this->info('Consultando API de CVA (esto puede tardar)...');
        // Solicitamos solo lo básico para agilizar, aunque getCatalogo trae todo por defecto según el servicio
        $catalogo = $cvaService->getCatalogo(['completos' => false]);

        if (isset($catalogo['error'])) {
            $this->error('Error obteniendo catálogo: ' . $catalogo['error']);
            Log::error('CVA Sync Error', ['error' => $catalogo]);
            return 1;
        }

        if (!is_array($catalogo)) {
            $this->error('Formato de respuesta inválido.');
            return 1;
        }

        $total = count($catalogo);
        $this->info("Se obtuvieron {$total} productos. Procesando marcas y categorías...");

        // 2. Extraer Marcas y Categorías únicas
        $marcasCVA = [];
        $categoriasCVA = [];

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($catalogo as $item) {
            if (!empty($item['marca'])) {
                $marcasCVA[$item['marca']] = true; // Usar array como set
            }
            if (!empty($item['grupo'])) {
                $categoriasCVA[$item['grupo']] = true;
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        $marcasUnicas = array_keys($marcasCVA);
        $categoriasUnicas = array_keys($categoriasCVA);

        $this->info('Marcas encontradas: ' . count($marcasUnicas));
        $this->info('Categorías encontradas: ' . count($categoriasUnicas));

        // 3. Sincronizar Marcas
        $this->info('Sincronizando Marcas...');
        $marcasNuevas = 0;
        foreach ($marcasUnicas as $nombreMarca) {
            // firstOrCreate es seguro, aunque uno por uno. 
            // Para optimizar en volumenes grandes se podría hacer whereIn, 
            // pero las marcas suelen ser cientos, no millones.
            $marca = Marca::firstOrCreate(['nombre' => $nombreMarca]);
            if ($marca->wasRecentlyCreated) {
                $marcasNuevas++;
            }
        }
        $this->info("Marcas nuevas registradas: {$marcasNuevas}");

        // 4. Sincronizar Categorías
        $this->info('Sincronizando Categorías...');
        $categoriasNuevas = 0;
        foreach ($categoriasUnicas as $nombreGrupo) {
            $categoria = Categoria::firstOrCreate(['nombre' => $nombreGrupo]);
            if ($categoria->wasRecentlyCreated) {
                $categoriasNuevas++;
            }
        }
        $this->info("Categorías nuevas registradas: {$categoriasNuevas}");

        $this->info('Sincronización completada con éxito.');
        Log::info('CVA Sync Completed', [
            'marcas_nuevas' => $marcasNuevas,
            'categorias_nuevas' => $categoriasNuevas
        ]);

        return 0;
    }
}
