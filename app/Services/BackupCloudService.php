<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupCloudService
{
    protected int $localRetention = 2;
    protected int $cloudRetention = 3;

    public function __construct()
    {
        $this->localRetention = (int) (config('backup.retention.daily_backups', 7) ?: 2);
        $this->cloudRetention = (int) (env('GOOGLE_DRIVE_BACKUP_RETENTION', 3));
    }

    public function syncToCloud(): array
    {
        $result = ['success' => false, 'uploaded' => null, 'deleted' => 0];

        $latestLocal = $this->getLatestLocalBackup();
        if (!$latestLocal) {
            $result['message'] = 'No hay backups locales para sincronizar.';
            return $result;
        }

        $cloudDisk = $this->getCloudDisk();
        if (!$cloudDisk) {
            $result['message'] = 'Google Drive no está configurado. Define GOOGLE_DRIVE_CLIENT_ID, GOOGLE_DRIVE_CLIENT_SECRET, GOOGLE_DRIVE_REFRESH_TOKEN.';
            return $result;
        }

        try {
            $localPath = $latestLocal['path'];
            $filename = $latestLocal['filename'];
            $fullPath = Storage::disk('local')->path($localPath);

            if (!file_exists($fullPath)) {
                $result['message'] = "Archivo local no encontrado: {$fullPath}";
                return $result;
            }

            $stream = fopen($fullPath, 'r');
            $cloudDisk->write($filename, $stream);
            if (is_resource($stream)) fclose($stream);

            $result['uploaded'] = $filename;
            $result['success'] = true;

            $deleted = $this->cleanOldCloudBackups($cloudDisk);
            $result['deleted'] = $deleted;

            Log::info("Backup subido a Google Drive: {$filename}");
        } catch (\Exception $e) {
            Log::error("Error subiendo backup a Google Drive: " . $e->getMessage());
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    public function cleanOldLocalBackups(): int
    {
        $files = Storage::disk('local')->files('backups/database/');
        $backupFiles = collect($files)->filter(fn($f) => str_ends_with($f, '.sql') || str_ends_with($f, '.sql.gz') || str_ends_with($f, '.zip'))
            ->sortDesc()
            ->values();

        $keep = $backupFiles->take($this->localRetention);
        $delete = $backupFiles->slice($this->localRetention);

        $deletedCount = 0;
        foreach ($delete as $file) {
            try {
                Storage::disk('local')->delete($file);
                $deletedCount++;
            } catch (\Exception $e) {
                Log::warning("No se pudo eliminar backup local: {$file}");
            }
        }

        return $deletedCount;
    }

    protected function cleanOldCloudBackups($cloudDisk): int
    {
        $files = $cloudDisk->listContents('/', true)
            ->filter(fn($item) => $item->isFile() && str_ends_with($item->path(), '.sql.gz'))
            ->sort(fn($a, $b) => strcmp($b->lastModified(), $a->lastModified()))
            ->values();

        $deletedCount = 0;
        $keep = $files->take($this->cloudRetention);

        foreach ($files as $file) {
            $shouldKeep = $keep->first(fn($k) => $k->path() === $file->path());
            if (!$shouldKeep) {
                try {
                    $cloudDisk->delete($file->path());
                    $deletedCount++;
                } catch (\Exception $e) {
                    Log::warning("No se pudo eliminar backup cloud: " . $file->path());
                }
            }
        }

        return $deletedCount;
    }

    protected function getLatestLocalBackup(): ?array
    {
        $paths = [
            Storage::disk('local')->files('backups/database/'),
        ];

        $allFiles = collect($paths)->flatten()
            ->filter(fn($f) => str_ends_with($f, '.sql') || str_ends_with($f, '.sql.gz'))
            ->sort()
            ->reverse()
            ->values();

        if ($allFiles->isEmpty()) return null;

        $latest = $allFiles->first();
        return [
            'path' => $latest,
            'filename' => basename($latest),
        ];
    }

    protected function getCloudDisk()
    {
        try {
            $disk = Storage::disk('google-drive');
            if ($disk === null) return null;
            return $disk;
        } catch (\Exception $e) {
            return null;
        }
    }
}
