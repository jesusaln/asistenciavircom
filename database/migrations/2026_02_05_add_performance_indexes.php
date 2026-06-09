<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Agrega índices faltantes para mejorar rendimiento de queries frecuentes.
     * Soluciona Error #34: Missing Indexes en tablas de movimientos y facturas.
     *
     * Índices agregados:
     * - movimientos_bancarios: cuenta_bancaria_id, estado, fecha, conciliable
     * - cuentas_por_cobrar: cliente_id, cobrable, estado, fecha_vencimiento
     * - cuentas_por_pagar: proveedor_id, estado, fecha_vencimiento
     * - facturas: cliente_id, numero_factura, estado, fecha_emision
     */
    public function up(): void
    {
        // ========== MOVIMIENTOS BANCARIOS ==========
        if (Schema::hasTable('movimientos_bancarios')) {
            $this->addIndexes('movimientos_bancarios', [
                ['columns' => ['cuenta_bancaria_id'], 'name' => 'idx_mov_bancarios_cuenta'],
                ['columns' => ['estado'], 'name' => 'idx_mov_bancarios_estado'],
                ['columns' => ['fecha'], 'name' => 'idx_mov_bancarios_fecha'],
                ['columns' => ['conciliado_at'], 'name' => 'idx_mov_bancarios_conciliado'],
                // Índice compuesto para búsquedas comunes
                ['columns' => ['cuenta_bancaria_id', 'estado', 'fecha'], 'name' => 'idx_mov_bancarios_cuenta_estado_fecha'],
            ]);
        }

        // ========== CUENTAS POR COBRAR ==========
        if (Schema::hasTable('cuentas_por_cobrar')) {
            $this->addIndexes('cuentas_por_cobrar', [
                ['columns' => ['cliente_id'], 'name' => 'idx_cxc_cliente'],
                ['columns' => ['cobrable_type', 'cobrable_id'], 'name' => 'idx_cxc_cobrable'],
                ['columns' => ['estado'], 'name' => 'idx_cxc_estado'],
                ['columns' => ['fecha_vencimiento'], 'name' => 'idx_cxc_vencimiento'],
                ['columns' => ['empresa_id', 'estado'], 'name' => 'idx_cxc_empresa_estado'],
                ['columns' => ['monto_pendiente'], 'name' => 'idx_cxc_pendiente'],
            ]);
        }

        // ========== CUENTAS POR PAGAR ==========
        if (Schema::hasTable('cuentas_por_pagar')) {
            $this->addIndexes('cuentas_por_pagar', [
                ['columns' => ['proveedor_id'], 'name' => 'idx_cxp_proveedor'],
                ['columns' => ['estado'], 'name' => 'idx_cxp_estado'],
                ['columns' => ['fecha_vencimiento'], 'name' => 'idx_cxp_vencimiento'],
                ['columns' => ['empresa_id', 'estado'], 'name' => 'idx_cxp_empresa_estado'],
            ]);
        }

        // ========== FACTURAS ==========
        if (Schema::hasTable('facturas')) {
            $this->addIndexes('facturas', [
                ['columns' => ['cliente_id'], 'name' => 'idx_facturas_cliente'],
                ['columns' => ['numero_factura'], 'name' => 'idx_facturas_numero'],
                ['columns' => ['estado'], 'name' => 'idx_facturas_estado'],
                ['columns' => ['fecha_emision'], 'name' => 'idx_facturas_emision'],
                ['columns' => ['empresa_id', 'estado'], 'name' => 'idx_facturas_empresa_estado'],
                ['columns' => ['folio'], 'name' => 'idx_facturas_folio'],
            ]);
        }

        // ========== CFDI ==========
        if (Schema::hasTable('cfdis')) {
            $this->addIndexes('cfdis', [
                ['columns' => ['uuid'], 'name' => 'idx_cfdis_uuid'],
                ['columns' => ['estado'], 'name' => 'idx_cfdis_estado'],
                ['columns' => ['rfc_emisor'], 'name' => 'idx_cfdis_emisor'],
                ['columns' => ['rfc_receptor'], 'name' => 'idx_cfdis_receptor'],
                ['columns' => ['fecha_timbrado'], 'name' => 'idx_cfdis_timbrado'],
                ['columns' => ['empresa_id', 'estado'], 'name' => 'idx_cfdis_empresa_estado'],
            ]);
        }

        echo "  ✅ Performance indexes added successfully\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'movimientos_bancarios' => [
                'idx_mov_bancarios_cuenta',
                'idx_mov_bancarios_estado',
                'idx_mov_bancarios_fecha',
                'idx_mov_bancarios_conciliado',
                'idx_mov_bancarios_cuenta_estado_fecha',
            ],
            'cuentas_por_cobrar' => [
                'idx_cxc_cliente',
                'idx_cxc_cobrable',
                'idx_cxc_estado',
                'idx_cxc_vencimiento',
                'idx_cxc_empresa_estado',
                'idx_cxc_pendiente',
            ],
            'cuentas_por_pagar' => [
                'idx_cxp_proveedor',
                'idx_cxp_estado',
                'idx_cxp_vencimiento',
                'idx_cxp_empresa_estado',
            ],
            'facturas' => [
                'idx_facturas_cliente',
                'idx_facturas_numero',
                'idx_facturas_estado',
                'idx_facturas_emision',
                'idx_facturas_empresa_estado',
                'idx_facturas_folio',
            ],
            'cfdis' => [
                'idx_cfdis_uuid',
                'idx_cfdis_estado',
                'idx_cfdis_emisor',
                'idx_cfdis_receptor',
                'idx_cfdis_timbrado',
                'idx_cfdis_empresa_estado',
            ],
        ];

        foreach ($tables as $table => $indexes) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($indexes as $indexName) {
                try {
                    Schema::table($table, function (Blueprint $table) use ($indexName) {
                        $table->dropIndex($indexName);
                    });
                    echo "  ✅ Dropped index {$indexName} from {$table}\n";
                } catch (\Throwable $e) {
                    echo "  ⚠️  Could not drop index {$indexName}: {$e->getMessage()}\n";
                }
            }
        }
    }

    /**
     * Agrega índices a una tabla
     */
    protected function addIndexes(string $table, array $indexes): void
    {
        foreach ($indexes as $index) {
            $indexName = $index['name'];
            $columns = $index['columns'];

            // Verificar si el índice ya existe
            if ($this->indexExists($table, $indexName)) {
                echo "  ⏭️  Index {$indexName} already exists on {$table}\n";
                continue;
            }

            try {
                Schema::table($table, function (Blueprint $table) use ($columns, $indexName) {
                    $table->index($columns, $indexName);
                });
                echo "  ✅ Added index {$indexName} to {$table} (" . implode(', ', $columns) . ")\n";
            } catch (\Throwable $e) {
                echo "  ⚠️  Could not add index {$indexName}: {$e->getMessage()}\n";
            }
        }
    }

    /**
     * Verifica si un índice existe
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
            return false;
        }
    }
};
