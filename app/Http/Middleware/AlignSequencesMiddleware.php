<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlignSequencesMiddleware
{
    /**
     * Tablas a revisar (columna id por defecto).
     * Agrega aquí las tablas principales que crean registros frecuentemente.
     */
    private array $tables = [
        ['table' => 'compras', 'column' => 'id'],
        ['table' => 'cuentas_por_pagar', 'column' => 'id'],
        ['table' => 'cuentas_por_cobrar', 'column' => 'id'],
        ['table' => 'ventas', 'column' => 'id'],
        ['table' => 'entregas_dinero', 'column' => 'id'],
        ['table' => 'ordenes_compra', 'column' => 'id'],
        ['table' => 'inventario_movimientos', 'column' => 'id'],
        ['table' => 'compra_items', 'column' => 'id'],
        ['table' => 'producto_series', 'column' => 'id'],
    ];

    public function handle(Request $request, Closure $next)
    {
        // Solo aplica a PostgreSQL; en otros motores no hace nada.
        if (DB::getDriverName() === 'pgsql') {
            foreach ($this->tables as $item) {
                $table = $item['table'];
                $column = $item['column'] ?? 'id';

                if (!Schema::hasTable($table)) {
                    continue;
                }

                $this->ensureSequenceAligned($table, $column);
            }
        }

        return $next($request);
    }

    /**
     * Alinea la secuencia de autoincremento con el máximo ID actual.
     */
    private function ensureSequenceAligned(string $table, string $column = 'id'): void
    {
        $seqResult = DB::selectOne("SELECT pg_get_serial_sequence(?, ?) AS seq_name", [$table, $column]);
        $seqName = $seqResult?->seq_name;

        if (!$seqName) {
            return;
        }

        $maxId = (int) (DB::table($table)->max($column) ?? 0);
        $nextVal = DB::selectOne("SELECT last_value, is_called FROM {$seqName}");

        if ($nextVal && ($nextVal->last_value < $maxId || ($nextVal->last_value === $maxId && !$nextVal->is_called))) {
            $target = $maxId + 1;
            DB::statement("SELECT setval('{$seqName}', {$target}, false)");
        }
    }
}
