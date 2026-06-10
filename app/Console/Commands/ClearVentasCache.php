<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Ventas\VentaQueryService;
use Illuminate\Support\Facades\Cache;

class ClearVentasCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ventas:clear-cache {--empresa_id= : ID de empresa específica}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpiar cache de datos para creación de ventas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $empresaId = $this->option('empresa_id');

        if ($empresaId) {
            // Limpiar cache para empresa específica
            Cache::forget("ventas_clientes_{$empresaId}");
            Cache::forget("ventas_vendedores_{$empresaId}");
            Cache::forget("productos_stock_{$empresaId}");

            $this->info("Cache limpiado para empresa ID: {$empresaId}");
        } else {
            // Limpiar todo el cache de ventas
            $ventaQueryService = app(VentaQueryService::class);
            $ventaQueryService->clearCreateDataCache();

            $this->info('Todo el cache de ventas ha sido limpiado');
        }

        // También limpiar caches globales
        Cache::forget('ventas_servicios_activos');
        Cache::forget('ventas_almacenes_activos');
        Cache::forget('ventas_price_lists_activas');
        Cache::forget('ventas_catalogs');

        $this->info('Cache global de ventas limpiado exitosamente');
    }
}
