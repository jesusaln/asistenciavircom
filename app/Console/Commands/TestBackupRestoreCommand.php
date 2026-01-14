<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\DatabaseBackupService;

class TestBackupRestoreCommand extends Command
{
    protected $signature = 'db:test-restore {--compress : Comprimir el respaldo}';
    protected $description = 'Crea un respaldo, cambia un valor de prueba y restaura para verificar que la restauración es efectiva';

    public function handle(DatabaseBackupService $backupService): int
    {
        $this->info('Preparando tabla de prueba...');
        DB::statement('CREATE TABLE IF NOT EXISTS restore_test_table (id SERIAL PRIMARY KEY, note TEXT NOT NULL)');

        // Limpiar y dejar estado inicial
        DB::table('restore_test_table')->truncate();
        DB::table('restore_test_table')->insert(['note' => 'before']);

        $this->info('Creando respaldo...');
        $result = $backupService->createBackup([
            'name' => 'test_restore_' . date('Y-m-d_H-i-s'),
            'compress' => (bool)$this->option('compress'),
        ]);

        if (!$result['success']) {
            $this->error('Fallo al crear respaldo: ' . $result['message']);
            return self::FAILURE;
        }

        $this->line('Respaldo: ' . $result['path']);

        // Cambiar dato
        DB::table('restore_test_table')->update(['note' => 'after']);
        $current = DB::table('restore_test_table')->value('note');
        $this->line('Valor actual tras cambio: ' . $current);

        $this->info('Restaurando respaldo...');
        $filename = basename($result['path']);
        $restore = $backupService->restoreBackup($filename);
        if (!$restore['success']) {
            $this->error('Fallo al restaurar: ' . ($restore['message'] ?? 'desconocido'));
            return self::FAILURE;
        }

        // Verificar valor
        $restored = DB::table('restore_test_table')->value('note');
        $this->line('Valor tras restauración: ' . $restored);

        if ($restored === 'before') {
            $this->info('OK: Restauración efectiva.');
            return self::SUCCESS;
        }

        $this->warn('La restauración no revirtió el valor.');
        return self::FAILURE;
    }
}

