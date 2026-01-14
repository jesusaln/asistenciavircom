<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CVAService;
use App\Models\Producto;
use App\Models\Marca;
use App\Models\Categoria;
use App\Models\EmpresaConfiguracion;
use Illuminate\Support\Facades\Log;

class SyncCVACatalog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-cva-catalog {--limit=100 : Cantidad de páginas a procesar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza el catálogo de CVA con la base de datos local para mejorar la velocidad de búsqueda.';

    /**
     * Execute the console command.
     */
    public function handle(CVAService $cva)
    {
        $config = EmpresaConfiguracion::getConfig();
        if (!$config || !$config->cva_active) {
            $this->error('CVA no está activo en la configuración.');
            return;
        }

        $this->info('Iniciando sincronización de catálogo CVA...');

        // Cache de marcas y categorías para evitar miles de queries
        $marcasCache = Marca::pluck('id', 'nombre')->toArray();
        $categoriasCache = Categoria::pluck('id', 'nombre')->toArray();

        $page = 1;
        $countSynced = 0;
        $continue = true;
        $maxPages = (int) $this->option('limit');

        while ($continue && $page <= $maxPages) {
            $this->info("Procesando página {$page}...");

            $result = $cva->getCatalogo([
                'exist' => 0,
                'completos' => 0,
                'page' => $page
            ]);

            if (isset($result['error'])) {
                $this->error('Error de API: ' . $result['error']);
                break;
            }

            $articulos = $result['articulos'] ?? [];
            if (empty($articulos)) {
                $this->info("No hay más artículos en la página {$page}.");
                $continue = false;
                break;
            }

            $totalPage = count($articulos);
            $bar = $this->output->createProgressBar($totalPage);
            $bar->start();

            foreach ($articulos as $item) {
                try {
                    // Normalizar marca
                    $marcaNombre = strtoupper(trim($item['marca'] ?? 'GENERICO'));
                    if (!isset($marcasCache[$marcaNombre])) {
                        $marca = Marca::firstOrCreate([
                            'nombre' => $marcaNombre,
                            'empresa_id' => $config->empresa_id
                        ], ['estado' => 'activo']);
                        $marcasCache[$marcaNombre] = $marca->id;
                    }

                    // Normalizar categoría (Grupo en CVA)
                    $catNombre = strtoupper(trim($item['grupo'] ?? 'OTRO'));
                    if (!isset($categoriasCache[$catNombre])) {
                        $categoria = Categoria::firstOrCreate([
                            'nombre' => $catNombre,
                            'empresa_id' => $config->empresa_id
                        ], ['estado' => 'activo']);
                        $categoriasCache[$catNombre] = $categoria->id;
                    }

                    // Usar normalización de CVAService
                    $normalized = $cva->normalizeProduct($item);

                    // VALIDACIÓN: No sincronizar productos sin precio o sin nombre/descripción útil
                    if ($normalized['precio_compra'] <= 0 || empty($normalized['nombre']) || strlen($normalized['nombre']) < 5) {
                        $bar->advance();
                        continue;
                    }

                    Producto::updateOrCreate(
                        [
                            'empresa_id' => $config->empresa_id,
                            'cva_clave' => $item['clave'],
                            'origen' => 'CVA'
                        ],
                        [
                            'nombre' => $normalized['nombre'],
                            'descripcion' => $normalized['descripcion'],
                            'codigo' => $normalized['clave'],
                            'codigo_barras' => $item['codigo_fabricante'] ?? $item['clave'],
                            'marca_id' => $marcasCache[$marcaNombre],
                            'categoria_id' => $categoriasCache[$catNombre],
                            'precio_compra' => $normalized['precio_compra'],
                            'precio_venta' => $normalized['precio'],
                            'stock' => $normalized['stock_local'],
                            'stock_cedis' => $normalized['stock_cedis'],
                            'imagen' => $normalized['imagen_url'],
                            'estado' => 'activo',
                            'cva_last_sync' => now(),
                            'sat_clave_prod_serv' => $item['clave_sat'] ?? '43211500',
                            'sat_clave_unidad' => 'H87',
                            'sat_objeto_imp' => '02',
                            'unidad_medida' => 'Pieza',
                        ]
                    );

                    $countSynced++;
                } catch (\Exception $e) {
                    Log::error("Error sincronizando producto CVA: " . ($item['clave'] ?? 'unknown'), ['msg' => $e->getMessage()]);
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            // Si recibimos menos de 20 (el default es 36), probablemente es la última
            if ($totalPage < 20) {
                $continue = false;
            } else {
                $page++;
            }
        }

        $this->info("Sincronización completada. Total: {$countSynced} productos sincronizados localmente.");
    }
}
