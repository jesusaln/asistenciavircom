<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Console\Traits\EnforcesMaintenanceMode;
use PDO;

class RestoreDatabase extends Command
{
    use EnforcesMaintenanceMode;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:restore {file? : Archivo SQL de respaldo a restaurar} {--force : Ejecutar sin confirmación} {--confirm= : Confirmación explícita}';

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
        // 1. Check Maintenance Mode
        if (!$this->checkMaintenanceMode($this->option('force'))) {
            return Command::FAILURE;
        }

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
            usort($files, function ($a, $b) {
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

        if (!$this->obtainPgAdvisoryLock()) {
            $this->error('No se pudo adquirir lock global. Otro proceso podría estar restaurando la base de datos.');
            return Command::FAILURE;
        }

        $this->info("Iniciando restauración desde: {$file}");
        $this->warn('⚠️  Esta operación eliminará todos los datos actuales de la base de datos');

        $confirm = (string) $this->option('confirm');

        if (!$this->option('force') && $confirm !== 'RESTORE-DB' && $confirm !== 'RESTORE-DB-FORCE' && !$this->confirm('¿Estás seguro de que deseas continuar?', false)) {
            $this->releasePgAdvisoryLock();
            $this->info('Operación cancelada');
            return Command::SUCCESS;
        }

        try {
            $this->info('Iniciando proceso de restauración...');

            $dbConfig = config('database.connections.' . config('database.default'));
            $driver = $dbConfig['driver'] ?? 'unknown';

            if ($driver === 'pgsql') {
                $this->info('Detectado PostgreSQL. Usando psql para la restauración...');

                $host = $dbConfig['host'] ?? '127.0.0.1';
                $port = $dbConfig['port'] ?? 5432;
                $database = $dbConfig['database'];
                $username = $dbConfig['username'];
                $password = $dbConfig['password'] ?? '';

                $env = '';
                if ($password !== '') {
                    $env = 'PGPASSWORD=' . escapeshellarg($password) . ' ';
                }

                $command = sprintf(
                    '%spsql -h %s -p %s -U %s -d %s -f %s 2>&1',
                    $env,
                    escapeshellarg($host),
                    escapeshellarg((string) $port),
                    escapeshellarg($username),
                    escapeshellarg($database),
                    escapeshellarg($file)
                );

                $this->info('Ejecutando comando psql...');
                exec($command, $output, $returnCode);

                if ($returnCode !== 0) {
                    $this->releasePgAdvisoryLock();
                    $this->error('Error durante la restauración con psql:');
                    foreach ($output as $line) {
                        $this->error($line);
                    }
                    return Command::FAILURE;
                }

                $this->info('✓ Restauración completada exitosamente con psql');
            } else {
                // Fallback para otros drivers (MySQL, SQLite)
                $sql = file_get_contents($file);

                if (!$sql) {
                    $this->releasePgAdvisoryLock();
                    $this->error('No se pudo leer el archivo SQL');
                    return Command::FAILURE;
                }

                $this->info('Ejecutando restauración (fallback mode)...');

                $queries = $this->parseSqlFile($sql);
                $totalQueries = count($queries);
                $this->info("Se encontraron {$totalQueries} consultas");

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
                        $this->warn("\nError en consulta: " . substr($query, 0, 100) . "...");
                        $errors++;
                    }
                    $bar->advance();
                }

                $bar->finish();
                $this->newLine(2);
                $this->info("✓ Restauración completada con {$errors} errores.");
            }

            $this->releasePgAdvisoryLock();
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->releasePgAdvisoryLock();
            $this->error('Error durante la restauración: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function obtainPgAdvisoryLock(): bool
    {
        try {
            // Lock ID único para restauración de DB
            $row = DB::selectOne("SELECT pg_try_advisory_lock(7200101) AS locked");
            return (bool) ($row->locked ?? false);
        } catch (\Throwable) {
            return false;
        }
    }

    private function releasePgAdvisoryLock(): void
    {
        try {
            DB::selectOne("SELECT pg_advisory_unlock(7200101)");
        } catch (\Throwable) {
            // no-op
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

                if (($char === '"' || $char === "'") && ($i === 0 || $line[$i - 1] !== '\\')) {
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
