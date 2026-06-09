<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Agrega columna deleted_at a tablas que carecen de SoftDeletes.
     * Soluciona Error #38: Soft Deletes en tablas criticas.
     *
     * Nota: Las tablas de catalogos SAT generalmente NO deben tener soft deletes
     * porque son datos de referencia del gobierno.
     */
    public function up(): void
    {
        // Tablas criticas de negocio que necesitan SoftDeletes
        $tablesRequiringSoftDeletes = [
            'compras' => 'Compra',
            'movimientos_bancarios' => 'MovimientoBancario',
            'movimientos_manual' => 'MovimientoManual',
            'traspasos' => 'Traspaso',
            'pedidos' => 'Pedido',
            'pedidos_online' => 'PedidoOnline',
            'citas' => 'Cita',
            'tickets' => 'Ticket',
            'ventas' => 'Venta',
            'cotizaciones' => 'Cotizacion',
        ];

        foreach ($tablesRequiringSoftDeletes as $table => $model) {
            // Verificar si la tabla existe y no tiene deleted_at
            if (!Schema::hasTable($table)) {
                continue;
            }

            // Verificar si ya tiene deleted_at
            if (Schema::hasColumn($table, 'deleted_at')) {
                echo "  ⏭️  Table {$table} already has deleted_at\n";
                continue;
            }

            try {
                Schema::table($table, function (Blueprint $table) {
                    $table->timestamp('deleted_at')->nullable()->after('updated_at');
                });
                echo "  ✅ Added deleted_at to {$table}\n";
            } catch (\Throwable $e) {
                echo "  ⚠️  Error adding deleted_at to {$table}: {$e->getMessage()}\n";
            }
        }

        echo "\n  💡 Note: Remember to add 'SoftDeletes' trait to the corresponding models\n";
        echo "     and run 'composer dump-autoload' after updating models.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'compras',
            'movimientos_bancarios',
            'movimientos_manual',
            'traspasos',
            'pedidos',
            'pedidos_online',
            'citas',
            'tickets',
            'ventas',
            'cotizaciones',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            try {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('deleted_at');
                });
                echo "  ✅ Dropped deleted_at from {$table}\n";
            } catch (\Throwable $e) {
                echo "  ⚠️  Error dropping deleted_at from {$table}: {$e->getMessage()}\n";
            }
        }
    }
};
