<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixSequences extends Command
{
    protected $signature = 'db:fix-sequences {--all : Intenta reparar secuencias comunes} {--confirm= : Confirmación explícita}';
    protected $description = 'Realinea secuencias PostgreSQL para evitar violaciones de llave duplicada';

    public function handle(): int
    {
        $confirm = (string) $this->option('confirm');
        if ($confirm !== 'FIX-SEQUENCES') {
            $this->warn('Este comando modifica secuencias. Usa --confirm=FIX-SEQUENCES para ejecutar.');
            return self::FAILURE;
        }

        $this->info('Reparando secuencias...');

        $targets = [
            // Tabla de migraciones
            ['table' => 'migrations', 'column' => 'id'],
            // Tabla pivote de orden de compra
            ['table' => 'orden_compra_producto', 'column' => 'id'],
            // Tabla de movimientos de inventario
            ['table' => 'inventario_movimientos', 'column' => 'id'],
        ];

        foreach ($targets as $t) {
            $this->fixSequence($t['table'], $t['column']);
        }

        $this->info('Listo.');
        return self::SUCCESS;
    }

    private function fixSequence(string $table, string $column): void
    {
        if (!$this->obtainPgAdvisoryLock()) {
            $this->error('No se pudo adquirir lock global. Otro proceso podría estar ajustando secuencias.');
            return;
        }

        $this->line("- $table.$column ...");
        $sql = "SELECT setval(pg_get_serial_sequence('".$table."','".$column."'), COALESCE(MAX(".$column."), 1), MAX(".$column.") IS NOT NULL) FROM " . $table;
        DB::statement($sql);

        $this->releasePgAdvisoryLock();
    }

    private function obtainPgAdvisoryLock(): bool
    {
        try {
            $row = DB::selectOne("SELECT pg_try_advisory_lock(7200103) AS locked");
            return (bool) ($row->locked ?? false);
        } catch (\Throwable) {
            return false;
        }
    }

    private function releasePgAdvisoryLock(): void
    {
        try {
            DB::selectOne("SELECT pg_advisory_unlock(7200103)");
        } catch (\Throwable) {
            // no-op
        }
    }
}
