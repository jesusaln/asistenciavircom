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
                            {--producto= : ID de producto específico a reconciliar}';

    protected $description = 'Detecta y opcionalmente corrige discrepancias entre productos.stock e inventarios.cantidad';

    public function handle(): int
    {
        $this->info('🔍 Iniciando reconciliación de inventario...');

        $productoId = $this->option('producto');
        $autoFix = $this->option('fix');

        $discrepancias = $this->detectarDiscrepancias($productoId);

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
            $this->corregirDiscrepancias($discrepancias);
        } else {
            $this->info('💡 Usa --fix para corregir automáticamente');
        }

        // Reconciliar lotes (productos con expires = true)
        $this->reconciliarLotes($productoId);

        return Command::SUCCESS;
    }

    /**
     * Detectar discrepancias entre productos.stock e inventarios.cantidad
     */
    private function detectarDiscrepancias($productoId = null)
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
                Producto::where('id', $d->id)->update(['stock' => $d->stock_real]);

                $this->line("  ✓ Producto #{$d->id}: {$d->stock} → {$d->stock_real}");

                Log::info('Reconciliación: stock corregido', [
                    'producto_id' => $d->id,
                    'stock_anterior' => $d->stock,
                    'stock_corregido' => $d->stock_real,
                ]);
            }
        });

        $this->info("✅ {$discrepancias->count()} productos corregidos.");
    }

    /**
     * Reconciliar lotes: SUM(lotes.cantidad_actual) debe igualar inventarios.cantidad
     */
    private function reconciliarLotes($productoId = null): void
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
