<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixSequences extends Command
{
    protected $signature = 'db:fix-sequences {--all : Intenta reparar secuencias comunes}';
    protected $description = 'Realinea secuencias PostgreSQL para evitar violaciones de llave duplicada';

    public function handle(): int
    {
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
        $this->line("- $table.$column ...");
        $sql = "SELECT setval(pg_get_serial_sequence('".$table."','".$column."'), COALESCE(MAX(".$column."), 1), MAX(".$column.") IS NOT NULL) FROM " . $table;
        DB::statement($sql);
    }
}

