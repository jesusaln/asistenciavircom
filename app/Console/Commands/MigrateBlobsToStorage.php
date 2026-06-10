<?php

namespace App\Console\Commands;

use App\Helpers\Base64ToFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Migrar datos base64 (BLOBs) almacenados en la base de datos a archivos en Storage.
 * 
 * Este comando resuelve el problema crítico de rendimiento donde imágenes de firmas,
 * INEs y comprobantes se guardaban como longText (base64) directamente en la BD,
 * causando que cada SELECT cargara megabytes innecesarios en memoria RAM.
 * 
 * SEGURIDAD: El comando es idempotente. Si se ejecuta múltiples veces, no duplicará archivos.
 * Los registros que ya tienen rutas de archivo (no base64) serán ignorados automáticamente.
 * 
 * USO:
 *   php artisan blobs:migrate              # Migrar todo
 *   php artisan blobs:migrate --table=rentas  # Solo migrar tabla rentas
 *   php artisan blobs:migrate --dry-run    # Solo mostrar qué se migraría
 */
class MigrateBlobsToStorage extends Command
{
    protected $signature = 'blobs:migrate 
                            {--table= : Tabla específica a migrar (rentas, clientes, citas, polizas_servicio, empresa_configuracion)}
                            {--dry-run : Solo mostrar qué se migraría, sin hacer cambios}
                            {--batch=50 : Registros por lote}';

    protected $description = 'Migrar datos base64 (firmas, INE, comprobantes) de la BD a archivos en Storage';

    private int $migrated = 0;
    private int $skipped = 0;
    private int $errors = 0;

    /**
     * Definición de tablas y columnas a migrar.
     * Formato: tabla => [columna => directorio_destino]
     */
    private array $tablesConfig = [
        'rentas' => [
            'firma_digital' => 'rentas/firmas',
            'ine_frontal' => 'rentas/documentos/ine',
            'ine_trasera' => 'rentas/documentos/ine',
            'comprobante_domicilio' => 'rentas/documentos/comprobante',
            'solicitud_renta' => 'rentas/documentos/solicitud',
        ],
        'clientes' => [
            'credito_firma' => 'clientes/firmas',
        ],
        'citas' => [
            'firma_cliente' => 'citas/firmas',
            'firma_tecnico' => 'citas/firmas',
        ],
        'polizas_servicio' => [
            'firma_cliente' => 'polizas/firmas',
        ],
        'empresa_configuracion' => [
            'firma_digital' => 'empresa/firmas',
        ],
    ];

    public function handle(): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════╗');
        $this->info('║   🚀 Migración de BLOBs a Storage               ║');
        $this->info('║   Optimización de rendimiento de base de datos   ║');
        $this->info('╚══════════════════════════════════════════════════╝');
        $this->info('');

        $isDryRun = $this->option('dry-run');
        $specificTable = $this->option('table');
        $batchSize = (int) $this->option('batch');

        if ($isDryRun) {
            $this->warn('⚠️  MODO DRY-RUN: No se harán cambios reales.');
            $this->info('');
        }

        // Asegurar que los directorios existen
        if (!$isDryRun) {
            foreach ($this->tablesConfig as $columns) {
                foreach ($columns as $dir) {
                    Storage::disk('public')->makeDirectory($dir);
                }
            }
        }

        $tablesToProcess = $specificTable
            ? [$specificTable => $this->tablesConfig[$specificTable] ?? []]
            : $this->tablesConfig;

        if ($specificTable && !isset($this->tablesConfig[$specificTable])) {
            $this->error("❌ Tabla '{$specificTable}' no configurada. Tablas válidas: " . implode(', ', array_keys($this->tablesConfig)));
            return self::FAILURE;
        }

        foreach ($tablesToProcess as $table => $columns) {
            $this->migrateTable($table, $columns, $batchSize, $isDryRun);
        }

        $this->info('');
        $this->info('═══════════════════════════════════════════════════');
        $this->info("✅ Migración completada:");
        $this->info("   📦 Archivos migrados: {$this->migrated}");
        $this->info("   ⏭️  Registros ignorados (ya migrados): {$this->skipped}");
        if ($this->errors > 0) {
            $this->warn("   ❌ Errores: {$this->errors}");
        }
        $this->info('═══════════════════════════════════════════════════');
        $this->info('');

        Log::info('blobs:migrate completado', [
            'migrated' => $this->migrated,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
            'dry_run' => $isDryRun,
        ]);

        return $this->errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function migrateTable(string $table, array $columns, int $batchSize, bool $isDryRun): void
    {
        $this->info("📋 Procesando tabla: {$table}");

        // Construir query para encontrar registros con datos base64
        $conditions = [];
        foreach (array_keys($columns) as $column) {
            $conditions[] = "({$column} IS NOT NULL AND {$column} LIKE 'data:%')";
        }

        if (empty($conditions)) {
            $this->line("   ⏭️  No hay columnas configuradas para {$table}");
            return;
        }

        $whereClause = implode(' OR ', $conditions);

        $totalCount = DB::table($table)
            ->whereRaw($whereClause)
            ->count();

        if ($totalCount === 0) {
            $this->line("   ✅ No hay datos base64 pendientes en {$table}");
            return;
        }

        $this->info("   📊 Encontrados {$totalCount} registros con datos base64");

        $bar = $this->output->createProgressBar($totalCount);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');
        $bar->start();

        DB::table($table)
            ->whereRaw($whereClause)
            ->select(array_merge(['id'], array_keys($columns)))
            ->orderBy('id')
            ->chunk($batchSize, function ($records) use ($table, $columns, $isDryRun, $bar) {
                foreach ($records as $record) {
                    $updates = [];

                    foreach ($columns as $column => $directory) {
                        $value = $record->{$column} ?? null;

                        if (empty($value)) {
                            continue;
                        }

                        // Solo procesar si es base64 (empieza con "data:")
                        if (!str_starts_with($value, 'data:')) {
                            $this->skipped++;
                            continue;
                        }

                        if ($isDryRun) {
                            $sizeKb = round(strlen($value) / 1024, 1);
                            $this->line("   [DRY] {$table}.{$column} (ID: {$record->id}) - {$sizeKb} KB");
                            $this->migrated++;
                            continue;
                        }

                        // Guardar como archivo
                        $prefix = $column . '_' . $record->id;
                        $path = Base64ToFile::save($value, $directory, $prefix);

                        if ($path) {
                            $updates[$column] = $path;
                            $this->migrated++;
                        } else {
                            $this->errors++;
                            Log::error("blobs:migrate - Error migrando {$table}.{$column} ID:{$record->id}");
                        }
                    }

                    // Actualizar el registro con las rutas de archivo
                    if (!$isDryRun && !empty($updates)) {
                        DB::table($table)
                            ->where('id', $record->id)
                            ->update($updates);
                    }

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->info(''); // Salto de línea después de la barra de progreso
    }
}
