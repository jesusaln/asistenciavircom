<?php

namespace App\Console\Commands;

use App\Models\Producto;
use App\Models\Inventario;
use App\Models\Lote;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Comando para reconciliar y detectar discrepancias de inventario
 * 
 * Compara productos.stock con SUM(inventarios.cantidad) y reporta diferencias
 * Diseñado para ejecutarse diariamente via scheduler
 */
class ReconciliarInventario extends Command
{
    protected $signature = 'inventario:reconciliar 
                            {--fix : Corregir automáticamente las discrepancias}
                            {--producto= : ID de producto específico a reconciliar}
                            {--empresa-id= : Forzar empresa_id para multitenant}
                            {--confirm= : Confirmación explícita para aplicar correcciones}';

    protected $description = 'Detecta y opcionalmente corrige discrepancias entre productos.stock e inventarios.cantidad';

    public function handle(): int
    {
        $this->info('🔍 Iniciando reconciliación de inventario...');
        $start = microtime(true);

        $productoId = $this->option('producto');
        $autoFix = $this->option('fix');
        $confirm = (string) $this->option('confirm');
        $empresaId = $this->option('empresa-id') ?: (\App\Support\EmpresaResolver::resolveId() ?? null);

        if (!$empresaId) {
            $this->warn('No se detectó empresa_id. Usa --empresa-id para evitar afectar múltiples empresas.');
        }

        $discrepancias = $this->detectarDiscrepancias($productoId, $empresaId);

        if ($discrepancias->isEmpty()) {
            $this->info('✅ No se encontraron discrepancias. El inventario está sincronizado.');
            Log::info('Reconciliación de inventario completada sin discrepancias');
            return Command::SUCCESS;
        }

        $this->warn("⚠️ Se encontraron {$discrepancias->count()} discrepancias:");
        $this->newLine();

        $headers = ['ID', 'Producto', 'stock (tabla productos)', 'SUM(inventarios)', 'Diferencia'];
        $rows = $discrepancias->map(fn($d) => [
            $d->id,
            mb_strimwidth($d->nombre, 0, 30, '...'),
            $d->stock,
            $d->stock_real,
            $d->diferencia,
        ])->toArray();

        $this->table($headers, $rows);

        // Log para auditoría
        Log::warning('Reconciliación de inventario: discrepancias detectadas', [
            'total_discrepancias' => $discrepancias->count(),
            'productos' => $discrepancias->pluck('id')->toArray(),
        ]);

        if ($autoFix) {
            // Safety Check for Multi-Tenant Mass Update
            if (!$empresaId && $confirm !== 'FIX-ALL-COMPANIES') {
                $this->error('⛔ PELIGRO: Estás intentando corregir inventario en TODAS las empresas.');
                $this->error('  Para una sola empresa: usa --empresa-id=ID --confirm=FIX-INVENTARIO');
                $this->error('  Para TODAS: usa --confirm=FIX-ALL-COMPANIES');
                return Command::FAILURE;
            }

            // Standard Confirmation for Single Tenant
            if ($empresaId && $confirm !== 'FIX-INVENTARIO') {
                $this->warn('Para aplicar correcciones a la empresa ' . $empresaId . ', usa --confirm=FIX-INVENTARIO');
                return Command::FAILURE;
            }

            $this->corregirDiscrepancias($discrepancias);
        } else {
            $this->info('💡 Usa --fix para corregir automáticamente');
        }

        // Reconciliar lotes (productos con expires = true)
        $this->reconciliarLotes($productoId, $empresaId);

        Log::info('Reconciliación de inventario completada', [
            'total_discrepancias' => $discrepancias->count(),
            'duration_seconds' => round(microtime(true) - $start, 2),
            'empresa_id' => $empresaId,
        ]);
        return Command::SUCCESS;
    }

    /**
     * Detectar discrepancias entre productos.stock e inventarios.cantidad
     */
    private function detectarDiscrepancias($productoId = null, $empresaId = null)
    {
        $query = DB::table('productos as p')
            ->leftJoin('inventarios as i', 'p.id', '=', 'i.producto_id')
            ->select([
                'p.id',
                'p.nombre',
                'p.stock',
                DB::raw('COALESCE(SUM(i.cantidad), 0) as stock_real'),
                DB::raw('p.stock - COALESCE(SUM(i.cantidad), 0) as diferencia'),
            ])
            ->groupBy('p.id', 'p.nombre', 'p.stock')
            ->havingRaw('p.stock != COALESCE(SUM(i.cantidad), 0)');

        if ($productoId) {
            $query->where('p.id', $productoId);
        }

        if ($empresaId) {
            $query->where('p.empresa_id', $empresaId);
        }

        return $query->get();
    }

    /**
     * Corregir discrepancias actualizando productos.stock con el valor real
     */
    private function corregirDiscrepancias($discrepancias): void
    {
        $this->info('🔧 Corrigiendo discrepancias...');

        DB::transaction(function () use ($discrepancias) {
            foreach ($discrepancias as $d) {
                // Auditoría previa al update
                Log::info('Reconciliación Audited: Ajuste de stock', [
                    'producto_id' => $d->id,
                    'stock_anterior' => $d->stock,
                    'stock_nuevo' => $d->stock_real,
                    'diferencia' => $d->diferencia,
                    'usuario_id' => 'SYSTEM_CMD',
                    'timestamp' => now()->toIso8601String(),
                ]);

                Producto::where('id', $d->id)->update(['stock' => $d->stock_real]);

                $this->line("  ✓ Producto #{$d->id}: {$d->stock} → {$d->stock_real}");
            }
        });

        $this->info("✅ {$discrepancias->count()} productos corregidos.");
    }

    /**
     * Reconciliar lotes: SUM(lotes.cantidad_actual) debe igualar inventarios.cantidad
     */
    private function reconciliarLotes($productoId = null, $empresaId = null): void
    {
        $this->info('🔍 Verificando consistencia de lotes...');

        $query = DB::table('productos as p')
            ->join('inventarios as i', 'p.id', '=', 'i.producto_id')
            ->leftJoin('lotes as l', function ($join) {
                $join->on('p.id', '=', 'l.producto_id')
                    ->on('i.almacen_id', '=', 'l.almacen_id');
            })
            ->where('p.expires', true)
            ->select([
                'p.id as producto_id',
                'p.nombre',
                'i.almacen_id',
                'i.cantidad as inventario_cantidad',
                DB::raw('COALESCE(SUM(l.cantidad_actual), 0) as lotes_cantidad'),
                DB::raw('i.cantidad - COALESCE(SUM(l.cantidad_actual), 0) as diferencia'),
            ])
            ->groupBy('p.id', 'p.nombre', 'i.almacen_id', 'i.cantidad')
            ->havingRaw('i.cantidad != COALESCE(SUM(l.cantidad_actual), 0)');

        if ($productoId) {
            $query->where('p.id', $productoId);
        }

        if ($empresaId) {
            $query->where('p.empresa_id', $empresaId);
        }

        $discrepanciasLotes = $query->get();

        if ($discrepanciasLotes->isEmpty()) {
            $this->info('✅ Lotes sincronizados correctamente.');
            return;
        }

        $this->warn("⚠️ {$discrepanciasLotes->count()} discrepancias en lotes:");

        $headers = ['Producto', 'Almacén', 'Inventario', 'SUM(Lotes)', 'Diferencia'];
        $rows = $discrepanciasLotes->map(fn($d) => [
            "{$d->producto_id}: " . mb_strimwidth($d->nombre, 0, 20, '...'),
            $d->almacen_id,
            $d->inventario_cantidad,
            $d->lotes_cantidad,
            $d->diferencia,
        ])->toArray();

        $this->table($headers, $rows);

        Log::warning('Reconciliación: discrepancias en lotes detectadas', [
            'total' => $discrepanciasLotes->count(),
        ]);
    }
}
