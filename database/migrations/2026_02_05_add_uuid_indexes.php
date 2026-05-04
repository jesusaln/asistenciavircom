<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Agrega índices únicos a campos UUID para mejorar rendimiento y garantizar unicidad.
     * Soluciona Error #33: UUID sin validación ni índice único.
     */
    public function up(): void
    {
        // Tablas con campo uuid que necesitan índice único
        $tablesWithUuid = [
            'clientes' => 'uuid',
            'cfdis' => 'uuid',
        ];

        foreach ($tablesWithUuid as $table => $column) {
            // Verificar que la tabla y columna existan
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                continue;
            }

            // Agregar índice único si no existe
            $indexName = "idx_{$table}_{$column}_unique";

            // PostgreSQL: Verificar si el índice existe
            $indexExists = $this->indexExists($table, $indexName);

            if (!$indexExists) {
                Schema::table($table, function (Blueprint $table) use ($column, $indexName) {
                    $table->string($column, 50)->nullable()->change();
                    $table->unique($column, $indexName)->nullable();
                });

                echo "  ✅ Added unique index {$indexName} to {$table}.{$column}\n";
            }
        }

        // Para sharing_token en cotizaciones, ventas, pedidos
        $tablesWithSharingToken = [
            'cotizaciones' => 'sharing_token',
            'ventas' => 'sharing_token',
            'pedidos' => 'sharing_token',
        ];

        foreach ($tablesWithSharingToken as $table => $column) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                continue;
            }

            $indexName = "idx_{$table}_{$column}_unique";
            $indexExists = $this->indexExists($table, $indexName);

            if (!$indexExists) {
                Schema::table($table, function (Blueprint $table) use ($column, $indexName) {
                    $table->unique($column, $indexName);
                });

                echo "  ✅ Added unique index {$indexName} to {$table}.{$column}\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar índices agregados
        $tablesWithUuid = [
            'clientes' => ['uuid', 'idx_clientes_uuid_unique'],
            'cfdis' => ['uuid', 'idx_cfdis_uuid_unique'],
            'cotizaciones' => ['sharing_token', 'idx_cotizaciones_sharing_token_unique'],
            'ventas' => ['sharing_token', 'idx_ventas_sharing_token_unique'],
            'pedidos' => ['sharing_token', 'idx_pedidos_sharing_token_unique'],
        ];

        foreach ($tablesWithUuid as $table => [$column, $indexName]) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            try {
                Schema::table($table, function (Blueprint $table) use ($indexName) {
                    $table->dropUnique($indexName);
                });
                echo "  ✅ Dropped index {$indexName} from {$table}\n";
            } catch (\Throwable $e) {
                echo "  ⚠️  Could not drop index {$indexName}: {$e->getMessage()}\n";
            }
        }
    }

    /**
     * Verifica si un índice existe (para PostgreSQL)
     */
    protected function indexExists(string $table, string $indexName): bool
    {
        try {
            $result = \DB::select("
                SELECT 1 FROM pg_indexes
                WHERE tablename = ?
                AND indexname = ?
            ", [$table, $indexName]);

            return !empty($result);
        } catch (\Throwable $e) {
            // Si falla la consulta, asumir que no existe
            return false;
        }
    }
};
