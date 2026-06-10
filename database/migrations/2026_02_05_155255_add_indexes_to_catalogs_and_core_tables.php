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
                $indexName = "{$table}_{$column}_index";
                $exists = \DB::select("SELECT 1 FROM pg_indexes WHERE indexname = ?", [$indexName]);
                if (empty($exists)) {
                    Schema::table($table, function (Blueprint $table) use ($column) {
                        $table->index($column);
                    });
                }
            }
        }

        // Core Tables - Search Optimization
        if (Schema::hasTable('productos')) {
            Schema::table('productos', function (Blueprint $table) {
                if (Schema::hasColumn('productos', 'nombre')) {
                    $indexName = 'productos_nombre_index';
                    $exists = \DB::select("SELECT 1 FROM pg_indexes WHERE indexname = ?", [$indexName]);
                    if (empty($exists)) {
                        $table->index('nombre');
                    }
                }
                if (Schema::hasColumn('productos', 'sku')) {
                    $indexName = 'productos_sku_index';
                    $exists = \DB::select("SELECT 1 FROM pg_indexes WHERE indexname = ?", [$indexName]);
                    if (empty($exists)) {
                        $table->index('sku');
                    }
                }
            });
        }

        if (Schema::hasTable('clientes')) {
            Schema::table('clientes', function (Blueprint $table) {
                if (Schema::hasColumn('clientes', 'nombre_fiscal')) {
                    $indexName = 'clientes_nombre_fiscal_index';
                    $exists = \DB::select("SELECT 1 FROM pg_indexes WHERE indexname = ?", [$indexName]);
                    if (empty($exists)) {
                        $table->index('nombre_fiscal');
                    }
                }
                if (Schema::hasColumn('clientes', 'email')) {
                    $indexName = 'clientes_email_index';
                    $exists = \DB::select("SELECT 1 FROM pg_indexes WHERE indexname = ?", [$indexName]);
                    if (empty($exists)) {
                        $table->index('email');
                    }
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
