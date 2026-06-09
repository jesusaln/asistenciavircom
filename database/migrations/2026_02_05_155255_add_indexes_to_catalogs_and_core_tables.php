<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Addressing Error #34: Falta de Índices Críticos y Performance.
     */
    public function up(): void
    {
        // SAT Catalogs
        $satTables = [
            'sat_claves_unidad' => 'clave',
            'sat_formas_pago' => 'clave',
            'sat_metodos_pago' => 'clave',
            'sat_usos_cfdi' => 'clave',
            'sat_regimenes_fiscales' => 'clave',
            'sat_impuestos' => 'clave',
        ];

        foreach ($satTables as $table => $column) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                Schema::table($table, function (Blueprint $table) use ($column) {
                    $table->index($column);
                });
            }
        }

        // Core Tables - Search Optimization
        if (Schema::hasTable('productos')) {
            Schema::table('productos', function (Blueprint $table) {
                if (Schema::hasColumn('productos', 'nombre')) {
                    $table->index('nombre');
                }
                if (Schema::hasColumn('productos', 'sku')) {
                    $table->index('sku');
                }
            });
        }

        if (Schema::hasTable('clientes')) {
            Schema::table('clientes', function (Blueprint $table) {
                if (Schema::hasColumn('clientes', 'nombre_fiscal')) {
                    $table->index('nombre_fiscal');
                }
                if (Schema::hasColumn('clientes', 'email')) {
                    $table->index('email');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ... reverse logic if needed
    }
};
