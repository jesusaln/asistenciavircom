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
    protected $signature = 'app:sync-cva-catalog {--limit=500 : Cantidad de páginas a procesar} {--page=1 : Página de inicio}';

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

        $this->info('Iniciando sincronización de catálogo CVA (Modo Completo)...');

        // Cache de marcas y categorías para evitar miles de queries
        $marcasCache = Marca::pluck('id', 'nombre')->toArray();
        $categoriasCache = Categoria::pluck('id', 'nombre')->toArray();

        $page = (int) $this->option('page');
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
                $this->error("Error en página {$page}: " . $result['error']);
                if (isset($result['status'])) {
                    $this->error("Status Code: " . $result['status']);
                }

                // Reintentar una vez tras espera
                $this->warn("Reintentando en 3 segundos...");
                sleep(3);
                $result = $cva->getCatalogo([
                    'exist' => 0,
                    'completos' => 0,
                    'page' => $page
                ]);

                if (isset($result['error'])) {
                    $this->error("Segundo fallo en página {$page}. Saltando...");
                    $page++;
                    continue;
                }
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

                    // BUSQUEDA INTELIGENTE para evitar Unique Violation
                    // 1. Buscar por cva_clave (lo ideal)
                    // 2. Buscar por codigo_barras (si ya existía como local o importación previa)
                    $producto = Producto::where('empresa_id', $config->empresa_id)
                        ->where(function ($q) use ($item) {
                            $q->where('cva_clave', $item['clave'])
                                ->orWhere('codigo_barras', $item['clave']);
                        })
                        ->first();

                    if (!$producto) {
                        $producto = new Producto();
                        $producto->empresa_id = $config->empresa_id;
                        $producto->origen = 'CVA';
                        $producto->cva_clave = $item['clave'];
                        $producto->estado = 'activo';
                    }

                    // Actualizar datos
                    $producto->fill([
                        'nombre' => $normalized['nombre'],
                        'descripcion' => $normalized['descripcion'],
                        'codigo' => $normalized['clave'],
                        'codigo_barras' => $item['clave'],
                        'marca_id' => $marcasCache[$marcaNombre],
                        'categoria_id' => $categoriasCache[$catNombre],
                        'precio_compra' => $normalized['precio_compra'],
                        'precio_venta' => $normalized['precio'],
                        'stock' => $normalized['stock_local'],
                        'stock_cedis' => $normalized['stock_cedis'],
                        'imagen' => $normalized['imagen_url'],
                        'origen' => 'CVA', // Asegurar que sea CVA incluso si era local
                        'cva_clave' => $item['clave'],
                        'cva_last_sync' => now(),
                        'sat_clave_prod_serv' => $item['clave_sat'] ?? '43211500',
                        'sat_clave_unidad' => 'H87',
                        'sat_objeto_imp' => '02',
                        'unidad_medida' => 'Pieza',
                    ]);

                    $producto->save();

                    $countSynced++;
                } catch (\Exception $e) {
                    Log::error("Error sincronizando producto CVA: " . ($item['clave'] ?? 'unknown'), ['msg' => $e->getMessage()]);
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            // Usar paginación oficial de CVA si está disponible
            if (isset($result['paginacion']['total_paginas'])) {
                $maxReported = (int) $result['paginacion']['total_paginas'];
                if ($page >= $maxReported) {
                    $continue = false;
                    $this->info("Alcanzada la última página reportada por CVA ({$maxReported}).");
                }
            }

            if ($totalPage < 10 && $continue) { // Si vienen muy pocos, probablemente es el final
                $continue = false;
                $this->info("Finalizando por bajo número de artículos en página ({$totalPage}).");
            } else if ($continue) {
                $page++;
                sleep(1); // Pequeño respiro para la API
            }
        }

        $this->info("Sincronización completada. Total: {$countSynced} productos sincronizados localmente.");
    }
}
