<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;

class RestoreDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:restore {file? : Archivo SQL de respaldo a restaurar} {--force : Ejecutar sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restaura la base de datos desde un archivo SQL de respaldo';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = $this->argument('file');

        // Si no se especifica archivo, buscar el más reciente
        if (!$file) {
            $backupPath = storage_path('app/private/backups/database');
            $files = glob($backupPath . '/backup_*.sql');

            if (empty($files)) {
                $this->error('No se encontraron archivos de respaldo en ' . $backupPath);
                return Command::FAILURE;
            }

            // Ordenar por fecha de modificación (más reciente primero)
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });

            $file = $files[0];
            $this->info('Usando el archivo de respaldo más reciente: ' . basename($file));
        }

        // Verificar que el archivo existe
        if (!file_exists($file)) {
            $this->error("El archivo {$file} no existe");
            return Command::FAILURE;
        }

        $this->info("Iniciando restauración desde: {$file}");
        $this->warn('⚠️  Esta operación eliminará todos los datos actuales de la base de datos');

        if (!$this->option('force') && !$this->confirm('¿Estás seguro de que deseas continuar?', false)) {
            $this->info('Operación cancelada');
            return Command::SUCCESS;
        }

        try {
            $this->info('Iniciando proceso de restauración...');

            // Leer el archivo SQL
            $sql = file_get_contents($file);

            if (!$sql) {
                $this->error('No se pudo leer el archivo SQL');
                return Command::FAILURE;
            }

            $this->info('Ejecutando restauración...');

            // Dividir el SQL en consultas individuales
            $queries = $this->parseSqlFile($sql);

            $totalQueries = count($queries);
            $this->info("Se encontraron {$totalQueries} consultas para ejecutar");

            $bar = $this->output->createProgressBar($totalQueries);
            $bar->start();

            $executed = 0;
            $errors = 0;

            foreach ($queries as $query) {
                $query = trim($query);
                if (empty($query) || strpos($query, '--') === 0) {
                    $bar->advance();
                    continue;
                }

                try {
                    DB::statement($query);
                    $executed++;
                } catch (\Exception $e) {
                    $this->warn("Error en consulta: " . substr($query, 0, 100) . "...");
                    $this->warn("Error: " . $e->getMessage());
                    $errors++;
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            $this->info("Restauración completada:");
            $this->info("✓ Consultas ejecutadas: {$executed}");
            if ($errors > 0) {
                $this->warn("⚠️ Errores encontrados: {$errors}");
            }
            $this->info("✓ Archivo de respaldo utilizado: " . basename($file));

            return $errors > 0 ? Command::FAILURE : Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error durante la restauración: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Parsea un archivo SQL y lo divide en consultas individuales
     */
    private function parseSqlFile($sql)
    {
        $queries = [];
        $lines = explode("\n", $sql);
        $currentQuery = '';
        $inString = false;
        $stringChar = '';

        foreach ($lines as $line) {
            $line = trim($line);

            // Saltar comentarios y líneas vacías
            if (empty($line) || strpos($line, '--') === 0) {
                continue;
            }

            // Detectar inicio/fin de strings
            for ($i = 0; $i < strlen($line); $i++) {
                $char = $line[$i];

                if (($char === '"' || $char === "'") && ($i === 0 || $line[$i-1] !== '\\')) {
                    if (!$inString) {
                        $inString = true;
                        $stringChar = $char;
                    } elseif ($char === $stringChar) {
                        $inString = false;
                    }
                }
            }

            $currentQuery .= $line . "\n";

            // Si no estamos dentro de un string, verificar si termina la consulta
            if (!$inString && substr($line, -1) === ';') {
                $queries[] = $currentQuery;
                $currentQuery = '';
                $inString = false;
            }
        }

        // Agregar la última consulta si existe
        if (!empty($currentQuery)) {
            $queries[] = $currentQuery;
        }

        return $queries;
    }
}
