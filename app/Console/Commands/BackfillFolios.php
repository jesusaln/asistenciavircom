<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Folio\FolioService;
use App\Models\Cliente;
use App\Models\Cita;
use App\Models\Herramienta;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Servicio;
use Illuminate\Support\Facades\Schema;

class BackfillFolios extends Command
{
    protected $signature = 'folio:backfill';
    protected $description = 'Backfill missing folios/codes for all modules';

    public function handle(FolioService $folioService)
    {
        $this->info('Starting Folio Backfill...');
        $skippedTables = 0;
        $skippedColumns = 0;
        $missingTables = [];
        $missingColumns = [];

        $modules = [
            'cliente' => Cliente::class,
            // 'cita' => Cita::class,
            'herramienta' => Herramienta::class,
            'proveedor' => Proveedor::class,
            'producto' => Producto::class,
            'servicio' => Servicio::class,
            // Phase 2
            'mantenimiento' => \App\Models\Mantenimiento::class,
            'nomina' => \App\Models\Nomina::class,
            'prestamo' => \App\Models\Prestamo::class,
            'traspaso' => \App\Models\Traspaso::class,
            'vacacion' => \App\Models\Vacacion::class,
            // Renta & Ticket handled separately or here if needed, but Renta has numero_contrato which is different field.
            // Our Service handles mapping, so we can trust it.
            'renta' => \App\Models\Renta::class,
            'ticket' => \App\Models\Ticket::class,
        ];

        foreach ($modules as $type => $modelClass) {
            $this->info("Processing {$type}...");
            $fieldName = $folioService->getFieldNameByType($type);
            $model = new $modelClass();
            $table = $model->getTable();

            if (!Schema::hasTable($table)) {
                $this->warn("Skipping {$type}: table {$table} does not exist.");
                $skippedTables++;
                $missingTables[] = $table;
                continue;
            }

            if (!Schema::hasColumn($table, $fieldName)) {
                $this->warn("Skipping {$type}: column {$fieldName} not found in {$table}.");
                $skippedColumns++;
                $missingColumns[] = "{$table}.{$fieldName}";
                continue;
            }

            // Find records with empty folio/code
            $records = $modelClass::whereNull($fieldName)->orWhere($fieldName, '')->orderBy('id')->cursor();

            $count = 0;
            foreach ($records as $record) {
                try {
                    $folio = $folioService->getNextFolio($type);
                    $record->{$fieldName} = $folio;
                    $record->saveQuietly(); // Avoid triggering observers if possible, or use save() 
                    $count++;
                    $this->output->write('.');
                } catch (\Exception $e) {
                    $this->error("Failed to update {$type} ID {$record->id}: " . $e->getMessage());
                }
            }
            $this->newLine();
            $this->info("Updated {$count} records for {$type}.");
        }

        // Citas separately if needed or just uncomment above
        // Doing Citas now
        $this->info("Processing cita...");
        $fieldName = $folioService->getFieldNameByType('cita');
        $model = new Cita();
        $table = $model->getTable();

        if (!Schema::hasTable($table)) {
            $this->warn("Skipping cita: table {$table} does not exist.");
            $skippedTables++;
            $missingTables[] = $table;
            $this->info("Skipped tables: {$skippedTables}. Skipped columns: {$skippedColumns}.");
            if (!empty($missingTables)) {
                $this->line('Missing tables: ' . implode(', ', array_unique($missingTables)));
            }
            if (!empty($missingColumns)) {
                $this->line('Missing columns: ' . implode(', ', array_unique($missingColumns)));
            }
            $this->info('Backfill completed!');
            return 0;
        }

        if (!Schema::hasColumn($table, $fieldName)) {
            $this->warn("Skipping cita: column {$fieldName} not found in {$table}.");
            $skippedColumns++;
            $missingColumns[] = "{$table}.{$fieldName}";
            $this->info("Skipped tables: {$skippedTables}. Skipped columns: {$skippedColumns}.");
            if (!empty($missingTables)) {
                $this->line('Missing tables: ' . implode(', ', array_unique($missingTables)));
            }
            if (!empty($missingColumns)) {
                $this->line('Missing columns: ' . implode(', ', array_unique($missingColumns)));
            }
            $this->info('Backfill completed!');
            return 0;
        }

        $records = Cita::whereNull($fieldName)->orWhere($fieldName, '')->orderBy('id')->cursor();
        $count = 0;
        foreach ($records as $record) {
            try {
                $folio = $folioService->getNextFolio('cita');
                $record->{$fieldName} = $folio;
                $record->saveQuietly();
                $count++;
                $this->output->write('.');
            } catch (\Exception $e) {
                $this->error("Failed to update cita ID {$record->id}: " . $e->getMessage());
            }
        }
        $this->newLine();
        $this->info("Updated {$count} records for cita.");

        $this->info("Skipped tables: {$skippedTables}. Skipped columns: {$skippedColumns}.");
        if (!empty($missingTables)) {
            $this->line('Missing tables: ' . implode(', ', array_unique($missingTables)));
        }
        if (!empty($missingColumns)) {
            $this->line('Missing columns: ' . implode(', ', array_unique($missingColumns)));
        }
        $this->info('Backfill completed!');
    }
}
