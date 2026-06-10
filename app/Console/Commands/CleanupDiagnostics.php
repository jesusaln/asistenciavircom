<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanupDiagnostics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-diagnostics {--force : Forzar la eliminación sin preguntar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina scripts de diagnóstico, volcados SQL y logs temporales de la raíz del proyecto para mejorar la seguridad.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $patterns = [
            '*.sql',
            '*.dump',
            'check_*.php',
            'debug_*.php',
            'test_*.php',
            'fix_*.php',
            'recover_*.php',
            'reporte_*.php',
            'update_*.php',
            'insert_*.php',
            '*.log',
            'build_log.txt',
            'debug_log.txt',
            'restore_log.txt',
            'cookies.txt',
            'temp_*.js',
            'temp_*.conf',
            '*.tar.gz',
            'all_sql_files_*.txt',
            'main_backup_*.txt',
        ];

        $filesFound = [];
        $rootPath = base_path();

        foreach ($patterns as $pattern) {
            $matches = glob($rootPath . '/' . $pattern);
            if ($matches) {
                $filesFound = array_merge($filesFound, $matches);
            }
        }

        // Excluir archivos vitales si por error coinciden con el patrón
        $filesFound = array_filter($filesFound, function($file) {
            $basename = basename($file);
            return !in_array($basename, ['artisan', 'composer.json', 'package.json']);
        });

        if (empty($filesFound)) {
            $this->info('No se encontraron archivos de diagnóstico para limpiar.');
            return;
        }

        $this->warn('Se encontraron ' . count($filesFound) . ' archivos potencialmente sensibles en la raíz:');
        foreach ($filesFound as $file) {
            $this->line('- ' . basename($file));
        }

        if (!$this->option('force') && !$this->confirm('¿Estás seguro de que deseas eliminar estos archivos?', false)) {
            $this->info('Operación cancelada.');
            return;
        }

        $deletedCount = 0;
        foreach ($filesFound as $file) {
            if (File::delete($file)) {
                $deletedCount++;
            }
        }

        $this->info("Limpieza completada. Se eliminaron {$deletedCount} archivos.");
    }
}
