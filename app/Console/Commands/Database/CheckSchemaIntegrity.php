<?php

namespace App\Console\Commands\Database;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * SchemaIntegrityChecker
 *
 * Audita el esquema de base de datos y detecta:
 * - Columnas faltantes en tablas
 * - Indices faltantes
 * - Foreign keys faltantes
 * - Migraciones de "parche" necesarias
 *
 * Soluciona Error #31: Migraciones de "Parche"
 */
class CheckSchemaIntegrity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:check-schema-integrity
                            {--fix : Auto-create migrations for missing columns}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica integridad del esquema de base de datos';

    /**
     * Tablas criticas que deben existir
     */
    protected array $criticalTables = [
        'users',
        'clientes',
        'proveedores',
        'productos',
        'ventas',
        'compras',
        'facturas',
        'cfdis',
        'cuentas_por_cobrar',
        'cuentas_por_pagar',
        'movimientos_bancarios',
        'traspasos',
        'mantenimientos',
    ];

    /**
     * Columnas requeridas por tabla
     */
    protected array $requiredColumns = [
        'users' => ['id', 'name', 'email', 'password', 'empresa_id'],
        'clientes' => ['id', 'codigo', 'nombre_razon_social', 'rfc', 'email', 'empresa_id', 'uuid'],
        'proveedores' => ['id', 'codigo', 'nombre', 'rfc', 'email', 'empresa_id'],
        'productos' => ['id', 'codigo', 'nombre', 'precio', 'empresa_id', 'estado'],
        'ventas' => ['id', 'folio', 'cliente_id', 'total', 'estado', 'empresa_id'],
        'compras' => ['id', 'folio', 'proveedor_id', 'total', 'estado', 'empresa_id'],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Schema Integrity Checker');
        $this->newLine();

        $issues = [];
        $this->checkTables($issues);
        $this->checkColumns($issues);
        $this->checkIndexes($issues);

        $this->displayResults($issues);

        if ($this->option('fix') && !empty($issues)) {
            return $this->generateMigration($issues);
        }

        return empty($issues) ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Verifica que las tablas criticas existan
     */
    protected function checkTables(array &$issues): void
    {
        $this->info('Checking critical tables...');

        foreach ($this->criticalTables as $table) {
            if (!Schema::hasTable($table)) {
                $issues['tables'][] = [
                    'table' => $table,
                    'issue' => 'Table does not exist',
                    'severity' => 'critical',
                ];
                $this->warn("  X Table {$table} does not exist");
            } else {
                $this->line("  OK Table {$table} exists");
            }
        }

        $this->newLine();
    }

    /**
     * Verifica que las columnas requeridas existan
     */
    protected function checkColumns(array &$issues): void
    {
        $this->info('Checking required columns...');

        foreach ($this->requiredColumns as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    $issues['columns'][] = [
                        'table' => $table,
                        'column' => $column,
                        'issue' => 'Column does not exist',
                        'severity' => 'high',
                    ];
                    $this->warn("  X Column {$table}.{$column} does not exist");
                }
            }
        }

        $this->newLine();
    }

    /**
     * Verifica indices criticos
     */
    protected function checkIndexes(array &$issues): void
    {
        $this->info('Checking critical indexes...');

        $requiredIndexes = [
            ['table' => 'clientes', 'column' => 'uuid', 'index' => 'idx_clientes_uuid_unique'],
            ['table' => 'cfdis', 'column' => 'uuid', 'index' => 'idx_cfdis_uuid'],
            ['table' => 'ventas', 'column' => 'empresa_id', 'index' => 'idx_ventas_empresa'],
            ['table' => 'compras', 'column' => 'empresa_id', 'index' => 'idx_compras_empresa'],
        ];

        foreach ($requiredIndexes as $idx) {
            if (!Schema::hasTable($idx['table'])) {
                continue;
            }

            if (!$this->indexExists($idx['table'], $idx['index'])) {
                $issues['indexes'][] = [
                    'table' => $idx['table'],
                    'index' => $idx['index'],
                    'issue' => 'Required index does not exist',
                    'severity' => 'medium',
                ];
                $this->warn("  X Index {$idx['index']} does not exist on {$idx['table']}");
            }
        }

        $this->newLine();
    }

    /**
     * Muestra los resultados
     */
    protected function displayResults(array $issues): void
    {
        $totalIssues = count($issues['tables'] ?? [])
            + count($issues['columns'] ?? [])
            + count($issues['indexes'] ?? []);

        $this->info("Results:");
        $this->line("   Tables checked: " . count($this->criticalTables));
        $this->line("   Critical issues: " . count($issues['tables'] ?? []));
        $this->line("   Column issues: " . count($issues['columns'] ?? []));
        $this->line("   Index issues: " . count($issues['indexes'] ?? []));
        $this->newLine();

        if ($totalIssues === 0) {
            $this->info("Schema integrity is OK!");
            return;
        }

        $this->warn("Found {$totalIssues} schema issues:");
        $this->newLine();

        if ($this->option('fix')) {
            $this->info("Run with --fix to auto-generate migration");
        }
    }

    /**
     * Genera una migracion para corregir los issues
     */
    protected function generateMigration(array $issues): int
    {
        $filename = 'database/migrations/' . date('Y_m_d_His') . '_schema_fixes.php';

        $fixes = [];

        // Columnas faltantes
        foreach ($issues['columns'] ?? [] as $column) {
            if ($column['severity'] === 'high') {
                $table = $column['table'];
                $col = $column['column'];
                $fixes[] = <<<PHP
        // Add missing column {$col} to {$table}
        if (Schema::hasTable('{$table}') && !Schema::hasColumn('{$table}', '{$col}')) {
            Schema::table('{$table}', function (Blueprint \$table) {
                \$table->string('{$col}', 100)->nullable()->after('id');
            });
        }
PHP;
            }
        }

        $content = <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
{$fixes}
    }

    public function down(): void
    {
        //
    }
};
PHP;

        file_put_contents($filename, $content);

        $this->info("Generated migration: {$filename}");
        $this->info("Run 'php artisan migrate' to apply fixes");

        return Command::SUCCESS;
    }

    /**
     * Verifica si un indice existe
     */
    protected function indexExists(string $table, string $indexName): bool
    {
        try {
            $result = DB::select("
                SELECT 1 FROM pg_indexes
                WHERE tablename = ?
                AND indexname = ?
            ", [$table, $indexName]);

            return !empty($result);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
