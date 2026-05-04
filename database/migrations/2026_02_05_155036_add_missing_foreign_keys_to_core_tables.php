<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     * Addressing Error #32: Falta de Constraints de FK.
     */
    public function up(): void
    {
        // 1. Venta Items -> Ventas (Cascade)
        if (Schema::hasTable('venta_items') && Schema::hasTable('ventas')) {
            if (Schema::hasColumn('venta_items', 'venta_id')) {
                // Clean orphans first to avoid migration failure
                DB::statement("DELETE FROM venta_items WHERE venta_id NOT IN (SELECT id FROM ventas)");

                Schema::table('venta_items', function (Blueprint $table) {
                    $exists = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_NAME = 'venta_items' AND CONSTRAINT_NAME = 'venta_items_venta_id_foreign'");
                    if (empty($exists)) {
                        $table->foreign('venta_id')->references('id')->on('ventas')->onDelete('cascade');
                    }
                });
            }
        }

        // 2. Compra Items -> Compras (Cascade)
        if (Schema::hasTable('compra_items') && Schema::hasTable('compras')) {
            if (Schema::hasColumn('compra_items', 'compra_id')) {
                DB::statement("DELETE FROM compra_items WHERE compra_id NOT IN (SELECT id FROM compras)");

                Schema::table('compra_items', function (Blueprint $table) {
                    $exists = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_NAME = 'compra_items' AND CONSTRAINT_NAME = 'compra_items_compra_id_foreign'");
                    if (empty($exists)) {
                        $table->foreign('compra_id')->references('id')->on('compras')->onDelete('cascade');
                    }
                });
            }
        }

        // 3. Ventas -> Clientes (Restrict)
        if (Schema::hasTable('ventas') && Schema::hasTable('clientes')) {
            if (Schema::hasColumn('ventas', 'cliente_id')) {
                Schema::table('ventas', function (Blueprint $table) {
                    $exists = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_NAME = 'ventas' AND CONSTRAINT_NAME = 'ventas_cliente_id_foreign'");
                    if (empty($exists)) {
                        $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('restrict');
                    }
                });
            }
        }

        // 4. Facturas -> Clientes
        if (Schema::hasTable('facturas') && Schema::hasTable('clientes')) {
            if (Schema::hasColumn('facturas', 'cliente_id')) {
                Schema::table('facturas', function (Blueprint $table) {
                    $exists = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_NAME = 'facturas' AND CONSTRAINT_NAME = 'facturas_cliente_id_foreign'");
                    if (empty($exists)) {
                        $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('restrict');
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('facturas')) {
            Schema::table('facturas', function (Blueprint $table) {
                if (Schema::hasColumn('facturas', 'cliente_id')) {
                    $table->dropForeign(['cliente_id']);
                }
            });
        }

        // Mantenimientos was removed from UP, so we skip it here too

        if (Schema::hasTable('ventas')) {
            Schema::table('ventas', function (Blueprint $table) {
                if (Schema::hasColumn('ventas', 'cliente_id')) {
                    $table->dropForeign(['cliente_id']);
                }
            });
        }

        if (Schema::hasTable('compra_items')) {
            Schema::table('compra_items', function (Blueprint $table) {
                if (Schema::hasColumn('compra_items', 'compra_id')) {
                    $table->dropForeign(['compra_id']);
                }
            });
        }

        if (Schema::hasTable('venta_items')) {
            Schema::table('venta_items', function (Blueprint $table) {
                if (Schema::hasColumn('venta_items', 'venta_id')) {
                    $table->dropForeign(['venta_id']);
                }
            });
        }
    }
};
