<?php

namespace App\Console\Commands\App;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Finder\Finder;

class StorageCleanup extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'storage:cleanup
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--days=30 : Delete files older than this many days}
                            {--types= : Specific file types to clean (comma separated: log,tmp,old}
                            {--skip-backups : Skip backup files cleanup}
                            {--skip-logs : Skip log files cleanup}
                            {--skip-cache : Skip cache files cleanup}
                            {--skip-tmp : Skip temporary files cleanup}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up temporary files, old backups, and storage bloat';

    /**
     * Directorios a limpiar
     */
    protected array $cleanupPaths = [
        'storage/app/public/tmp' => [
            'label' => 'Temporary uploads',
            'extensions' => ['tmp', 'temp', 'partial'],
            'maxAgeDays' => 1,
        ],
        'storage/app/public/uploads/pending' => [
            'label' => 'Pending uploads',
            'extensions' => null,
            'maxAgeDays' => 7,
        ],
        'storage/debugbar' => [
            'label' => 'Debugbar cache',
            'extensions' => null,
            'maxAgeDays' => 1,
        ],
        'storage/logs' => [
            'label' => 'Application logs',
            'extensions' => ['log'],
            'maxAgeDays' => 30,
        ],
        'bootstrap/cache' => [
            'label' => 'Bootstrap cache (old)',
            'extensions' => ['php'],
            'maxAgeDays' => 7,
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $days = (int) $this->option('days');
        $types = $this->option('types');
        $force = $this->option('force');

        $this->info('🧹 Starting storage cleanup...');
        $this->newLine();

        $results = [];
        $totalFreed = 0;
        $totalDeleted = 0;

        // Determinar qué limpiar
        $cleanupConfig = $this->getCleanupConfig($days, $types);

        // Si no hay nada que limpiar, salir
        if (empty($cleanupConfig)) {
            $this->warn('Nothing to clean. Use --types to specify what to clean.');
            return 0;
        }

        // Mostrar plan de limpieza
        $this->displayCleanupPlan($cleanupConfig);

        if (!$force && !$dryRun) {
            if (!$this->confirm('Proceed with cleanup?')) {
                $this->info('Cleanup cancelled.');
                return 0;
            }
        }

        // Ejecutar limpieza
        foreach ($cleanupConfig as $path => $config) {
            $result = $this->cleanupPath($path, $config, $dryRun);
            $results[$path] = $result;

            $totalDeleted += $result['count'];
            $totalFreed += $result['size'];
        }

        // Limpiar archivos huérfanos en storage/app/public
        if (!$this->option('skip-backups')) {
            $orphanResult = $this->cleanupOrphanFiles($dryRun);
            $results['orphans'] = $orphanResult;
            $totalDeleted += $orphanResult['count'];
            $totalFreed += $orphanResult['size'];
        }

        // Mostrar resultados
        $this->displayResults($results, $totalDeleted, $totalFreed, $dryRun);

        // Loguear
        $this->logOperation($results, $totalDeleted, $totalFreed);

        return $totalDeleted > 0 ? 0 : 1;
    }

    /**
     * Obtener configuración de limpieza
     */
    protected function getCleanupConfig(int $days, ?string $types): array
    {
        $config = [];
        $typesArray = $types ? array_map('trim', explode(',', $types)) : null;

        foreach ($this->cleanupPaths as $path => $defaultConfig) {
            $shouldCleanup = false;

            if ($typesArray) {
                // Filtrar por tipos específicos
                $type = $defaultConfig['label'] ?? basename($path);
                foreach ($typesArray as $t) {
                    if (stripos($type, $t) !== false) {
                        $shouldCleanup = true;
                        break;
                    }
                }
            } else {
                $shouldCleanup = true;
            }

            if (!$shouldCleanup) {
                continue;
            }

            // Verificar skips
            if (stripos($path, 'logs') !== false && $this->option('skip-logs')) {
                continue;
            }

            if (stripos($path, 'backup') !== false && $this->option('skip-backups')) {
                continue;
            }

            if (stripos($path, 'cache') !== false && $this->option('skip-cache')) {
                continue;
            }

            $config[$path] = [
                ...$defaultConfig,
                'maxAgeDays' => $days,
            ];
        }

        return $config;
    }

    /**
     * Mostrar plan de limpieza
     */
    protected function displayCleanupPlan(array $config): void
    {
        $this->info('Cleanup plan:');

        foreach ($config as $path => $c) {
            $fullPath = base_path($path);
            $exists = File::exists($fullPath);
            $files = $exists ? count(File::allFiles($fullPath)) : 0;

            $this->line("  📁 {$c['label']} ({$path})");
            $this->line("     - Files: {$files}");
            $this->line("     - Older than: {$c['maxAgeDays']} days");
            $this->line("     - Extensions: " . ($c['extensions'] ? implode(', ', $c['extensions']) : 'all'));
            $this->newLine();
        }
    }

    /**
     * Limpiar un directorio específico
     */
    protected function cleanupPath(string $path, array $config, bool $dryRun): array
    {
        $fullPath = base_path($path);

        if (!File::exists($fullPath)) {
            return [
                'path' => $path,
                'label' => $config['label'],
                'count' => 0,
                'size' => 0,
                'status' => 'not_exists',
            ];
        }

        $cutoffTime = now()->subDays($config['maxAgeDays']);
        $deleted = 0;
        $freed = 0;
        $errors = [];

        $files = File::allFiles($fullPath);

        foreach ($files as $file) {
            // Verificar extensión si aplica
            if ($config['extensions'] && !in_array($file->getExtension(), $config['extensions'])) {
                continue;
            }

            // Verificar edad
            if ($file->getMTime() > $cutoffTime->timestamp) {
                continue;
            }

            // Verificar que no sea .gitkeep
            if ($file->getFilename() === '.gitkeep') {
                continue;
            }

            if ($dryRun) {
                $this->warn("  [DRY-RUN] Would delete: {$file->getPathname()}");
                $deleted++;
                $freed += $file->getSize();
                continue;
            }

            try {
                $freed += $file->getSize();
                File::delete($file->getPathname());
                $deleted++;
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        $status = empty($errors) ? 'success' : 'partial';
        $statusMessage = empty($errors) ? 'cleaned' : 'cleaned_with_errors';

        if (!$dryRun) {
            $this->info("  ✓ {$config['label']}: {$deleted} files deleted, " . $this->formatBytes($freed) . " freed");
        }

        return [
            'path' => $path,
            'label' => $config['label'],
            'count' => $deleted,
            'size' => $freed,
            'status' => $statusMessage,
            'errors' => $errors,
        ];
    }

    /**
     * Limpiar archivos huérfanos
     */
    protected function cleanupOrphanFiles(bool $dryRun): array
    {
        $this->info("  🔍 Scanning for orphan files in storage...");

        $storagePath = storage_path('app/public');
        $cutoffTime = now()->subDays(90); // 90 días para archivos sin referencias

        if (!File::exists($storagePath)) {
            return [
                'path' => 'orphans',
                'count' => 0,
                'size' => 0,
                'status' => 'not_exists',
            ];
        }

        $deleted = 0;
        $freed = 0;
        $orphans = [];

        // Buscar archivos que no estén en uploads/
        foreach (File::allFiles($storagePath) as $file) {
            // Saltar directorios especiales
            if (str_starts_with($file->getPathname(), $storagePath . '/uploads')) {
                continue;
            }

            // Saltar .gitignore
            if ($file->getFilename() === '.gitignore') {
                continue;
            }

            // Archivos muy antiguos sin acceder
            if ($file->getATime() < $cutoffTime->timestamp) {
                $orphans[] = $file;
            }
        }

        foreach ($orphans as $file) {
            if ($dryRun) {
                $this->warn("  [DRY-RUN] Would delete orphan: {$file->getPathname()}");
                $deleted++;
                $freed += $file->getSize();
                continue;
            }

            try {
                $freed += $file->getSize();
                File::delete($file->getPathname());
                $deleted++;
            } catch (\Exception $e) {
                $this->error("  ✗ Failed to delete: {$file->getPathname()}");
            }
        }

        if (!$dryRun && $deleted > 0) {
            $this->info("  ✓ Orphan files: {$deleted} deleted, " . $this->formatBytes($freed) . " freed");
        }

        return [
            'path' => 'orphans',
            'count' => $deleted,
            'size' => $freed,
            'status' => 'success',
        ];
    }

    /**
     * Mostrar resultados
     */
    protected function displayResults(array $results, int $totalDeleted, int $totalFreed, bool $dryRun): void
    {
        $this->newLine();

        if ($dryRun) {
            $this->warn("  [DRY-RUN MODE] No files were actually deleted.");
        }

        $this->info('📊 Cleanup Summary:');
        $this->line("  Total files: {$totalDeleted}");
        $this->line("  Space freed: " . $this->formatBytes($totalFreed));
    }

    /**
     * Formatear bytes a readable
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Loguear la operación
     */
    protected function logOperation(array $results, int $totalDeleted, int $totalFreed): void
    {
        try {
            Log::channel('daily')->info('Storage cleanup executed', [
                'results' => $results,
                'total_deleted' => $totalDeleted,
                'space_freed' => $totalFreed,
                'user_id' => auth()->id() ?? 'system',
            ]);
        } catch (\Exception $e) {
            // Silently fail
        }
    }
}
