<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Console\Traits\EnforcesMaintenanceMode;
use Carbon\Carbon;

/**
 * Comando para crear respaldo de tablas antes de la consolidación de empleados/técnicos/users.
 */
class ConsolidacionBackupCommand extends Command
{
    use EnforcesMaintenanceMode;

    protected $signature = 'consolidacion:backup 
                            {--restore : Restaurar desde el último backup}
                            {--force : Forzar ejecución sin mantenimiento}
                            {--confirm= : Confirmación explícita para restaurar}';

    protected $description = 'Crear respaldo de las tablas empleados, tecnicos, users y sus relaciones antes de la consolidación';

    private array $tablasRespaldar = [
        'users',
        'empleados',
        'tecnicos',
        'nominas',
        'citas',
        'herramientas',
        'asignacion_herramientas',
        'historial_herramientas',
        'responsabilidad_herramientas',
        'ventas',
        'pago_comisiones',
    ];

    public function handle(): int
    {
        if ($this->option('restore')) {
            // Check Maintenance Mode
            if (!$this->checkMaintenanceMode($this->option('force'))) {
                return Command::FAILURE;
            }

            if (!$this->obtainPgAdvisoryLock()) {
                $this->error('No se pudo adquirir lock global. Otro proceso podría estar restaurando datos.');
                return Command::FAILURE;
            }

            $result = $this->restaurar();

            $this->releasePgAdvisoryLock();
            return $result;
        }

        return $this->respaldar();
    }

    private function respaldar(): int
    {
        $this->info('🔄 Iniciando respaldo de tablas para consolidación...');

        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $backupDir = "backups/consolidacion/{$timestamp}";

        // Crear directorio si no existe
        Storage::makeDirectory($backupDir);

        $this->info("📁 Directorio de respaldo: storage/app/{$backupDir}");

        $bar = $this->output->createProgressBar(count($this->tablasRespaldar));
        $bar->start();

        foreach ($this->tablasRespaldar as $tabla) {
            try {
                // Verificar si la tabla existe
                if (!DB::getSchemaBuilder()->hasTable($tabla)) {
                    $this->warn("\n⚠️  Tabla {$tabla} no existe, saltando...");
                    $bar->advance();
                    continue;
                }

                // Obtener todos los registros
                $datos = DB::table($tabla)->get()->toArray();

                // Guardar como JSON
                Storage::put(
                    "{$backupDir}/{$tabla}.json",
                    json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                );

                $bar->advance();

            } catch (\Exception $e) {
                $this->error("\n❌ Error respaldando {$tabla}: " . $e->getMessage());
            }
        }

        $bar->finish();
        $this->newLine(2);

        // Guardar metadatos del backup
        $metadata = [
            'timestamp' => $timestamp,
            'tablas' => $this->tablasRespaldar,
            'database' => config('database.connections.' . config('database.default') . '.database'),
            'created_at' => Carbon::now()->toIso8601String(),
        ];

        Storage::put(
            "{$backupDir}/metadata.json",
            json_encode($metadata, JSON_PRETTY_PRINT)
        );

        // Guardar referencia al último backup
        Storage::put(
            'backups/consolidacion/latest.txt',
            $timestamp
        );

        $this->info("✅ Respaldo completado en: storage/app/{$backupDir}");
        $this->info("📝 Total de tablas respaldadas: " . count($this->tablasRespaldar));

        return Command::SUCCESS;
    }

    private function restaurar(): int
    {
        $confirm = (string) $this->option('confirm');

        // Leer último backup
        if (!Storage::exists('backups/consolidacion/latest.txt')) {
            $this->error('❌ No se encontró ningún backup previo.');
            return Command::FAILURE;
        }

        $timestamp = trim(Storage::get('backups/consolidacion/latest.txt'));
        $backupDir = "backups/consolidacion/{$timestamp}";

        if (!Storage::exists($backupDir)) {
            $this->error("❌ Directorio de backup no encontrado: {$backupDir}");
            return Command::FAILURE;
        }

        $this->warn("⚠️  ADVERTENCIA: Esto restaurará las tablas al estado del backup: {$timestamp}");

        if ($confirm !== 'RESTORE-CONSOLIDACION' && $confirm !== 'RESTORE-CONSOLIDACION-FORCE' && !$this->confirm('¿Estás seguro de continuar?')) {
            $this->info('Operación cancelada.');
            return Command::SUCCESS;
        }

        $this->info('🔄 Restaurando desde backup...');

        // Restaurar en orden inverso para respetar FK
        $tablasOrdenadas = array_reverse($this->tablasRespaldar);

        DB::beginTransaction();

        try {
            foreach ($tablasOrdenadas as $tabla) {
                $archivoBackup = "{$backupDir}/{$tabla}.json";

                if (!Storage::exists($archivoBackup)) {
                    $this->warn("⚠️  Archivo de backup no encontrado para {$tabla}, saltando...");
                    continue;
                }

                $datos = json_decode(Storage::get($archivoBackup), true);

                // Truncar tabla (desactivar FK temporalmente)
                DB::statement('SET session_replication_role = replica;');
                DB::table($tabla)->truncate();
                DB::statement('SET session_replication_role = DEFAULT;');

                // Insertar datos
                if (!empty($datos)) {
                    // Insertar en lotes para evitar límites de memoria
                    foreach (array_chunk($datos, 100) as $chunk) {
                        DB::table($tabla)->insert($chunk);
                    }
                }

                $this->info("✅ Restaurada tabla: {$tabla} (" . count($datos) . " registros)");
            }

            DB::commit();
            $this->info('✅ Restauración completada exitosamente.');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error durante la restauración: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function obtainPgAdvisoryLock(): bool
    {
        try {
            // Lock ID único para consolidación
            $row = DB::selectOne("SELECT pg_try_advisory_lock(7200103) AS locked");
            return (bool) ($row->locked ?? false);
        } catch (\Throwable) {
            return false;
        }
    }

    private function releasePgAdvisoryLock(): void
    {
        try {
            DB::selectOne("SELECT pg_advisory_unlock(7200103)");
        } catch (\Throwable) {
            // no-op
        }
    }
}
