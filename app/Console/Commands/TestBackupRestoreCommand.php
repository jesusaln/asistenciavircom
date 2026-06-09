<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\DatabaseBackupService;

use App\Console\Traits\EnforcesMaintenanceMode;

class TestBackupRestoreCommand extends Command
{
    use EnforcesMaintenanceMode;

    protected $signature = 'db:test-restore {--compress : Comprimir el respaldo} {--force : Forzar ejecución}';
    protected $description = 'Crea un respaldo, cambia un valor de prueba y restaura para verificar que la restauración es efectiva';

    public function handle(DatabaseBackupService $backupService): int
    {
        // 1. Check Maintenance Mode
        if (!$this->checkMaintenanceMode($this->option('force'))) {
            return self::FAILURE;
        }

        // 2. Production confirm
        if (app()->environment('production') && !$this->option('force') && !$this->confirm('¿Realmente quieres correr esto en producción? Modificará datos.', false)) {
            $this->error('Abortado por seguridad en producción.');
            return self::FAILURE;
        }

        $this->info('Preparando tabla de prueba...');

        try {
            DB::statement('CREATE TABLE IF NOT EXISTS restore_test_table (id SERIAL PRIMARY KEY, note TEXT NOT NULL)');

            // Limpiar y dejar estado inicial
            DB::table('restore_test_table')->truncate();
            DB::table('restore_test_table')->insert(['note' => 'before']);

            $this->info('Creando respaldo...');
            $result = $backupService->createBackup([
                'name' => 'test_restore_' . date('Y-m-d_H-i-s'),
                'compress' => (bool) $this->option('compress'),
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

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return self::FAILURE;
        } finally {
            // Cleanup: Always drop the test table
            $this->info('Limpiando tabla de prueba...');
            DB::statement('DROP TABLE IF EXISTS restore_test_table');
        }
    }
}

