<?php

namespace App\Console\Commands\App;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Finder\Finder;

class CacheForceClear extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cache:force-clear
                            {--tags= : Clear specific cache tags (comma separated)}
                            {--skip-config : Skip config cache clearing}
                            {--skip-route : Skip route cache clearing}
                            {--skip-view : Skip view cache clearing}
                            {--skip-events : Skip events cache clearing}
                            {--fresh : Run optimize:clear instead of individual clears}
                            {--dry-run : Show what would be cleared without actually clearing}';

    /**
     * The console command description.
     */
    protected $description = 'Force clear all application caches including config, routes, views, events and application cache';

    /**
     * Directorios de cache a limpiar
     */
    protected array $cacheDirectories = [
        'bootstrap/cache' => 'Bootstrap cache',
        'storage/framework/cache' => 'Framework cache',
        'storage/framework/views' => 'Compiled views',
        'storage/framework/sessions' => 'Sessions',
        'storage/framework/testing' => 'Testing cache',
        'storage/logs' => 'Logs (old)',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tags = $this->option('tags');
        $fresh = $this->option('fresh');
        $dryRun = $this->option('dry-run');

        $this->info('🧹 Clearing application caches...');
        $this->newLine();

        $results = [];

        // Si se especifican tags, usar cache tags
        if ($tags) {
            $tagList = array_map('trim', explode(',', $tags));
            $results[] = $this->clearCacheTags($tagList, $dryRun);
        }

        // Si --fresh, usar optimize:clear
        if ($fresh) {
            $results[] = $this->runOptimizeClear($dryRun);
        } else {
            // Limpiar cada cache individualmente
            $results[] = $this->clearConfigCache($dryRun);
            $results[] = $this->clearRouteCache($dryRun);
            $results[] = $this->clearViewCache($dryRun);
            $results[] = $this->clearEventsCache($dryRun);
            $results[] = $this->clearApplicationCache($dryRun);
            $results[] = $this->clearFileCaches($dryRun);
            $results[] = $this->clearOldLogs($dryRun);
            $results[] = $this->clearCompiledClass($dryRun);
        }

        // Limpiar cache de base de datos si es necesario
        if ($this->canClearDatabaseCache()) {
            $results[] = $this->clearDatabaseQueryCache($dryRun);
        }

        $this->newLine();
        $this->info('✅ Cache clearing completed!');

        // Mostrar resumen
        $this->displaySummary($results);

        // Loguear la operación
        $this->logOperation($results);

        return 0;
    }

    /**
     * Limpiar config cache
     */
    protected function clearConfigCache(bool $dryRun): array
    {
        $configPath = base_path('bootstrap/cache/config.php');

        if (!File::exists($configPath)) {
            return ['config' => 'skipped (not exists)'];
        }

        if ($dryRun) {
            $this->warn("  [DRY-RUN] Would delete: {$configPath}");
            return ['config' => 'would delete (dry-run)'];
        }

        try {
            File::delete($configPath);
            $this->info('  ✓ Config cache cleared');
            return ['config' => 'cleared'];
        } catch (\Exception $e) {
            $this->error("  ✗ Failed to clear config cache: {$e->getMessage()}");
            return ['config' => 'failed'];
        }
    }

    /**
     * Limpiar route cache
     */
    protected function clearRouteCache(bool $dryRun): array
    {
        $routePath = base_path('bootstrap/cache/routes-v7.php');

        if (!File::exists($routePath)) {
            return ['routes' => 'skipped (not exists)'];
        }

        if ($dryRun) {
            $this->warn("  [DRY-RUN] Would delete: {$routePath}");
            return ['routes' => 'would delete (dry-run)'];
        }

        try {
            File::delete($routePath);
            $this->info('  ✓ Route cache cleared');
            return ['routes' => 'cleared'];
        } catch (\Exception $e) {
            $this->error("  ✗ Failed to clear route cache: {$e->getMessage()}");
            return ['routes' => 'failed'];
        }
    }

    /**
     * Limpiar view cache
     */
    protected function clearViewCache(bool $dryRun): array
    {
        $viewsPath = resource_path('views');
        $compiledPath = storage_path('framework/views');

        if (!File::exists($compiledPath)) {
            return ['views' => 'skipped (not exists)'];
        }

        if ($dryRun) {
            $files = File::allFiles($compiledPath);
            $this->warn("  [DRY-RUN] Would delete " . count($files) . " compiled view files");
            return ['views' => 'would delete ' . count($files) . ' files (dry-run)'];
        }

        try {
            File::cleanDirectory($compiledPath);
            $this->info('  ✓ View cache cleared');
            return ['views' => 'cleared'];
        } catch (\Exception $e) {
            $this->error("  ✗ Failed to clear view cache: {$e->getMessage()}");
            return ['views' => 'failed'];
        }
    }

    /**
     * Limpiar events cache
     */
    protected function clearEventsCache(bool $dryRun): array
    {
        $eventsPath = storage_path('framework/cache/events.php');

        if (!File::exists($eventsPath)) {
            return ['events' => 'skipped (not exists)'];
        }

        if ($dryRun) {
            $this->warn("  [DRY-RUN] Would delete: {$eventsPath}");
            return ['events' => 'would delete (dry-run)'];
        }

        try {
            File::delete($eventsPath);
            $this->info('  ✓ Events cache cleared');
            return ['events' => 'cleared'];
        } catch (\Exception $e) {
            $this->error("  ✗ Failed to clear events cache: {$e->getMessage()}");
            return ['events' => 'failed'];
        }
    }

    /**
     * Limpiar application cache
     */
    protected function clearApplicationCache(bool $dryRun): array
    {
        if ($dryRun) {
            $this->warn("  [DRY-RUN] Would clear all application cache");
            return ['app_cache' => 'would clear (dry-run)'];
        }

        try {
            Cache::flush();
            $this->info('  ✓ Application cache cleared');
            return ['app_cache' => 'cleared'];
        } catch (\Exception $e) {
            $this->error("  ✗ Failed to clear application cache: {$e->getMessage()}");
            return ['app_cache' => 'failed'];
        }
    }

    /**
     * Limpiar directorios de cache específicos
     */
    protected function clearFileCaches(bool $dryRun): array
    {
        $cleared = [];

        foreach ($this->cacheDirectories as $path => $label) {
            $fullPath = base_path($path);

            if (!File::exists($fullPath)) {
                continue;
            }

            if ($dryRun) {
                $count = count(File::allFiles($fullPath));
                $this->warn("  [DRY-RUN] Would clear {$count} files in {$path}");
                $cleared[$label] = "would clear {$count} files (dry-run)";
                continue;
            }

            try {
                $count = 0;
                foreach (File::allFiles($fullPath) as $file) {
                    if ($file->isFile() && $file->getExtension() !== 'gitkeep') {
                        File::delete($file->getPathname());
                        $count++;
                    }
                }
                $this->info("  ✓ {$label}: {$count} files cleared");
                $cleared[$label] = "{$count} files cleared";
            } catch (\Exception $e) {
                $this->error("  ✗ Failed to clear {$label}: {$e->getMessage()}");
                $cleared[$label] = 'failed';
            }
        }

        return ['files' => $cleared];
    }

    /**
     * Limpiar logs antiguos
     */
    protected function clearOldLogs(bool $dryRun): array
    {
        $logsPath = storage_path('logs');

        if (!File::exists($logsPath)) {
            return ['logs' => 'skipped (not exists)'];
        }

        $retentionDays = 30; // Mantener logs de los últimos 30 días
        $cutoffTime = now()->subDays($retentionDays);

        if ($dryRun) {
            $oldLogs = $this->findOldLogs($logsPath, $cutoffTime);
            $this->warn("  [DRY-RUN] Would delete " . count($oldLogs) . " log files older than {$retentionDays} days");
            return ['old_logs' => 'would delete ' . count($oldLogs) . ' files (dry-run)'];
        }

        try {
            $deleted = 0;
            foreach ($this->findOldLogs($logsPath, $cutoffTime) as $log) {
                File::delete($log);
                $deleted++;
            }

            $this->info("  ✓ Old logs ({$retentionDays}+ days): {$deleted} files deleted");
            return ['old_logs' => "{$deleted} files deleted"];
        } catch (\Exception $e) {
            $this->error("  ✗ Failed to clear old logs: {$e->getMessage()}");
            return ['old_logs' => 'failed'];
        }
    }

    /**
     * Encontrar logs antiguos
     */
    protected function findOldLogs(string $path, $cutoffTime): array
    {
        $files = [];

        foreach (File::files($path) as $file) {
            if ($file->getExtension() === 'log' && $file->getMTime() < $cutoffTime->timestamp) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Limpiar compiled class
     */
    protected function clearCompiledClass(bool $dryRun): array
    {
        $compiledPath = base_path('bootstrap/cache/compiled.php');

        if (!File::exists($compiledPath)) {
            return ['compiled' => 'skipped (not exists)'];
        }

        if ($dryRun) {
            $this->warn("  [DRY-RUN] Would delete: {$compiledPath}");
            return ['compiled' => 'would delete (dry-run)'];
        }

        try {
            File::delete($compiledPath);
            $this->info('  ✓ Compiled classes cleared');
            return ['compiled' => 'cleared'];
        } catch (\Exception $e) {
            $this->error("  ✗ Failed to clear compiled classes: {$e->getMessage()}");
            return ['compiled' => 'failed'];
        }
    }

    /**
     * Limpiar cache de tags específicos
     */
    protected function clearCacheTags(array $tags, bool $dryRun): array
    {
        if ($dryRun) {
            $this->warn("  [DRY-RUN] Would clear cache tags: " . implode(', ', $tags));
            return ['tags' => 'would clear ' . implode(', ', $tags) . ' (dry-run)'];
        }

        try {
            foreach ($tags as $tag) {
                Cache::tags([$tag])->flush();
                $this->info("  ✓ Cache tag '{$tag}' cleared");
            }
            return ['tags' => 'cleared: ' . implode(', ', $tags)];
        } catch (\Exception $e) {
            $this->error("  ✗ Failed to clear cache tags: {$e->getMessage()}");
            return ['tags' => 'failed'];
        }
    }

    /**
     * Verificar si se puede limpiar cache de base de datos
     */
    protected function canClearDatabaseCache(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Limpiar query cache de PostgreSQL
     */
    protected function clearDatabaseQueryCache(bool $dryRun): array
    {
        // Solo para PostgreSQL
        $driver = DB::connection()->getDriverName();

        if ($driver !== 'pgsql') {
            return ['db_cache' => "skipped (driver: {$driver})"];
        }

        if ($dryRun) {
            $this->warn("  [DRY-RUN] Would clear PostgreSQL query cache");
            return ['db_cache' => 'would clear (dry-run)'];
        }

        try {
            DB::statement('DISCARD ALL');
            $this->info('  ✓ PostgreSQL query cache cleared');
            return ['db_cache' => 'cleared'];
        } catch (\Exception $e) {
            $this->error("  ✗ Failed to clear DB cache: {$e->getMessage()}");
            return ['db_cache' => 'failed'];
        }
    }

    /**
     * Ejecutar optimize:clear
     */
    protected function runOptimizeClear(bool $dryRun): array
    {
        if ($dryRun) {
            $this->warn('  [DRY-RUN] Would run: php artisan optimize:clear');
            return ['optimize' => 'would run optimize:clear (dry-run)'];
        }

        try {
            Artisan::call('optimize:clear');
            $output = Artisan::output();
            $this->info('  ✓ Optimize clear executed');
            return ['optimize' => 'cleared'];
        } catch (\Exception $e) {
            $this->error("  ✗ Failed to run optimize:clear: {$e->getMessage()}");
            return ['optimize' => 'failed'];
        }
    }

    /**
     * Mostrar resumen de la operación
     */
    protected function displaySummary(array $results): void
    {
        $this->newLine();
        $this->info('📊 Summary:');

        $totalCleared = 0;
        $totalFailed = 0;

        foreach ($results as $category => $result) {
            if (is_array($result)) {
                foreach ($result as $sub => $status) {
                    if (str_contains($status, 'cleared') || str_contains($status, 'deleted')) {
                        $totalCleared++;
                    } elseif (str_contains($status, 'failed')) {
                        $totalFailed++;
                    }
                }
            } else {
                if (str_contains($result, 'cleared') || str_contains($result, 'deleted')) {
                    $totalCleared++;
                } elseif (str_contains($result, 'failed')) {
                    $totalFailed++;
                }
            }
        }

        $this->line("  Cleared/Failed: {$totalCleared}/{$totalFailed}");
    }

    /**
     * Loguear la operación
     */
    protected function logOperation(array $results): void
    {
        try {
            Log::channel('daily')->info('Cache force clear executed', [
                'results' => $results,
                'user_id' => auth()->id() ?? 'system',
                'ip' => request()->ip() ?? 'cli',
            ]);
        } catch (\Exception $e) {
            // Silently fail logging
        }
    }
}
