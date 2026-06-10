<?php

namespace App\Console\Commands;

use App\Services\BackupCloudService;
use Illuminate\Console\Command;

class SyncBackupsCloudCommand extends Command
{
    protected $signature = 'backup:sync-cloud
                            {--clean-local : Solo limpiar backups locales viejos, sin subir}
                            {--force : Subir aunque Google Drive no esté configurado}';

    protected $description = 'Sincroniza el backup más reciente a Google Drive y limpia backups viejos';

    public function handle(BackupCloudService $service): int
    {
        $this->info('=== Sincronización de Backups a la Nube ===');

        $this->info('Limpiando backups locales viejos...');
        $localDeleted = $service->cleanOldLocalBackups();
        $this->info("Backups locales eliminados: {$localDeleted}");

        if ($this->option('clean-local')) {
            $this->info('Modo --clean-local: solo se limpiaron locales.');
            return self::SUCCESS;
        }

        $this->info('Subiendo backup a Google Drive...');
        $result = $service->syncToCloud();

        if ($result['success']) {
            $this->info("✓ Backup subido: {$result['uploaded']}");
            $this->info("Backups antiguos eliminados de Google Drive: {$result['deleted']}");
            return self::SUCCESS;
        }

        $this->warn("✗ {$result['message']}");

        if ($this->option('force')) {
            return self::SUCCESS;
        }

        return self::FAILURE;
    }
}
