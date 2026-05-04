<?php

namespace App\Console\Commands\Database;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

class CheckOrphanColumns extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'db:check-orphan-columns
                            {--model= : Check only specific model (full class name)}
                            {--json : Output as JSON}
                            {--fix : Generate migration for orphan columns}';

    /**
     * The console command description.
     */
    protected $description = 'Detect columns in database that are not defined in Eloquent models';

    /**
     * Mapping de tablas a modelos conocidos
     */
    protected array $tableToModelMap = [
        'clientes' => 'App\Models\Cliente',
        'ventas' => 'App\Models\Venta',
        'productos' => 'App\Models\Producto',
        'servicios' => 'App\Models\Servicio',
        'cotizaciones' => 'App\Models\Cotizacion',
        'pedidos' => 'App\Models\Pedido',
        'compras' => 'App\Models\Compra',
        'proveedores' => 'App\Models\Proveedor',
        'users' => 'App\Models\User',
        'empresas' => 'App\Models\Empresa',
        'categorias' => 'App\Models\Categoria',
        'marcas' => 'App\Models\Marca',
        'almacenes' => 'App\Models\Almacen',
        'rentas' => 'App\Models\Renta',
        'citas' => 'App\Models\Cita',
        'tickets' => 'App\Models\Ticket',
        'prestamos' => 'App\Models\Prestamo',
        'ordenes_compra' => 'App\Models\OrdenCompra',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $model = $this->option('model');
        $json = $this->option('json');
        $fix = $this->option('fix');

        if ($model) {
            $results = $this->checkSingleModel($model);

            if (empty($results)) {
                $this->info("No orphan columns found for {$model}");
                return 0;
            }

            $this->displayResults($results, $json);

            if ($fix) {
                $this->generateMigration($results);
            }

            return count($results['orphan_columns'] ?? []) > 0 ? 1 : 0;
        }

        // Check all models
        $allResults = $this->checkAllModels();

        $totalOrphans = array_sum(array_column($allResults, 'count'));

        if ($json) {
            $this->line(json_encode($allResults, JSON_PRETTY_PRINT));
            return 0;
        }

        $this->info("Found {$totalOrphans} orphan columns across " . count($allResults) . " tables\n");

        foreach ($allResults as $table => $result) {
            $this->displayResults($result, false, $table);
        }

        if ($fix && $totalOrphans > 0) {
            $this->generateMigrationBatch($allResults);
        }

        return $totalOrphans > 0 ? 1 : 0;
    }

    /**
     * Check a single model
     */
    protected function checkSingleModel(string $model): array
    {
        if (!class_exists($model)) {
            $this->error("Model {$model} not found");
            return [];
        }

        $instance = new $model;
        $table = $instance->getTable();

        $dbColumns = $this->getTableColumns($table);
        $modelColumns = $this->getModelColumns($model);

        $orphanColumns = array_diff($dbColumns, $modelColumns);

        return [
            'model' => $model,
            'table' => $table,
            'db_columns' => $dbColumns,
            'model_columns' => $modelColumns,
            'orphan_columns' => array_values($orphanColumns),
            'count' => count($orphanColumns),
        ];
    }

    /**
     * Check all models
     */
    protected function checkAllModels(): array
    {
        $results = [];

        foreach ($this->tableToModelMap as $table => $model) {
            if (!class_exists($model)) {
                continue;
            }

            $result = $this->checkSingleModel($model);

            if (!empty($result) && $result['count'] > 0) {
                $results[$table] = $result;
            }
        }

        return $results;
    }

    /**
     * Get columns from database table
     */
    protected function getTableColumns(string $table): array
    {
        try {
            return DB::getDoctrineSchemaManager()->listTableColumns($table);
        } catch (\Exception $e) {
            // Fallback to query for PostgreSQL
            return collect(DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = '{$table}'"))
                ->pluck('column_name')
                ->toArray();
        }
    }

    /**
     * Get fillable/guarded/casted columns from model
     */
    protected function getModelColumns(string $model): array
    {
        $instance = new $model;
        $columns = [];

        // Get fillable
        $fillable = $instance->getFillable();

        // Get guarded (if not empty, infer remaining)
        $guarded = $instance->getGuarded();

        if (empty($fillable) && empty($guarded)) {
            // Model with $guarded = [] and no $fillable
            // We can't safely determine columns, skip
            return [];
        }

        // Get casts keys
        $casts = array_keys($instance->getCasts());

        // Merge all
        $columns = array_merge($fillable, $casts);

        // Add timestamps if model uses them
        if ($instance->timestamps && !in_array('created_at', $columns)) {
            $columns[] = 'created_at';
            $columns[] = 'updated_at';
        }

        // Add deleted_at if using SoftDeletes
        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model))) {
            $columns[] = 'deleted_at';
        }

        return array_unique($columns);
    }

    /**
     * Display results
     */
    protected function displayResults(array $result, bool $json = false, ?string $table = null): void
    {
        $displayTable = $table ?? $result['table'] ?? 'unknown';

        if ($json) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
            return;
        }

        $this->newLine();
        $this->warn("Table: {$displayTable}");
        $this->warn("Model: {$result['model']}");
        $this->table(
            ['Orphan Column', 'Type', 'Nullable'],
            array_map(function ($col) use ($result) {
                $type = $result['db_columns'][$col] ?? 'unknown';
                $nullable = $type->getNotnull() ? 'YES' : 'NO';
                return [
                    $col,
                    $type->getType()->getName() ?? 'unknown',
                    $nullable,
                ];
            }, $result['orphan_columns'])
        );
    }

    /**
     * Generate migration for orphan columns
     */
    protected function generateMigration(array $result): void
    {
        $table = $result['table'];
        $columns = $result['orphan_columns'];
        $migrationName = 'add_orphan_columns_to_' . $table . '_table';

        $stub = File::get(app_path('Console/Commands/stubs/migration.stub'));

        // Simplificado: solo crea el stub
        $this->info("Would create migration: {$migrationName}");
        $this->line("Columns to add: " . implode(', ', $columns));
    }

    /**
     * Generate migrations batch for all results
     */
    protected function generateMigrationBatch(array $allResults): void
    {
        $this->newLine();
        $this->info("Generating migrations for orphan columns...");

        foreach ($allResults as $table => $result) {
            $this->generateMigration($result);
        }
    }
}
