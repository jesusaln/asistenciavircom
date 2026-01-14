<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tablas que necesitan columna folio
        $tablesWithFolio = [
            'citas',
            'ventas',
            'compras',
            'cotizaciones',
            'pedidos',
            'ordenes_compra',
            'pagos',
            'remisiones',
            'facturas'
        ];

        foreach ($tablesWithFolio as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'folio')) {
                        $table->string('folio')->nullable()->after('id');
                    }
                });
            }
        }

        // Clientes necesita columna codigo
        if (Schema::hasTable('clientes')) {
            Schema::table('clientes', function (Blueprint $table) {
                if (!Schema::hasColumn('clientes', 'codigo')) {
                    $table->string('codigo')->nullable()->after('id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tablesWithFolio = [
            'citas',
            'ventas',
            'compras',
            'cotizaciones',
            'pedidos',
            'ordenes_compra',
            'pagos',
            'remisiones',
            'facturas'
        ];

        foreach ($tablesWithFolio as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (Schema::hasColumn($tableName, 'folio')) {
                        $table->dropColumn('folio');
                    }
                });
            }
        }

        if (Schema::hasTable('clientes')) {
            Schema::table('clientes', function (Blueprint $table) {
                if (Schema::hasColumn('clientes', 'codigo')) {
                    $table->dropColumn('codigo');
                }
            });
        }
    }
};
