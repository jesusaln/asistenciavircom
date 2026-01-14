<?php

namespace App\Jobs;

use App\Services\DatabaseBackupService;
use App\Models\BackupLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DatabaseBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $options;

    public function __construct($options = [])
    {
        $this->options = $options;
    }

    public function handle(DatabaseBackupService $backupService)
    {
        try {
            $result = $backupService->createBackup($this->options);

            if ($result['success']) {
                BackupLog::create([
                    'filename' => basename($result['path']),
                    'path' => $result['path'],
                    'size' => $result['size'],
                    'type' => pathinfo($result['path'], PATHINFO_EXTENSION),
                    'method' => $result['method'] ?? 'unknown',
                    'status' => 'success',
                    'message' => $result['message'],
                    'metadata' => $result
                ]);
            } else {
                BackupLog::create([
                    'filename' => 'failed_backup_' . now()->format('Y-m-d_H-i-s'),
                    'path' => '',
                    'status' => 'failed',
                    'message' => $result['message'],
                    'metadata' => $result
                ]);
            }
        } catch (\Exception $e) {
            BackupLog::create([
                'filename' => 'failed_backup_' . now()->format('Y-m-d_H-i-s'),
                'path' => '',
                'status' => 'failed',
                'message' => $e->getMessage(),
                'metadata' => ['exception' => $e->getTraceAsString()]
            ]);

            throw $e; // Re-lanzar para que Laravel lo registre
        }
    }
}
