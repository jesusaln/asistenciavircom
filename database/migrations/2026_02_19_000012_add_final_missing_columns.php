<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // EMPRESA_CONFIGURACION - calle, enable_retencion_iva
        if (!Schema::hasColumn('empresa_configuracion', 'calle')) {
            Schema::table('empresa_configuracion', function (Blueprint $table) {
                $table->string('calle', 255)->nullable()->after('codigo_postal');
            });
        }
        if (!Schema::hasColumn('empresa_configuracion', 'enable_retencion_iva')) {
            Schema::table('empresa_configuracion', function (Blueprint $table) {
                $table->boolean('enable_retencion_iva')->default(false)->after('iva');
            });
        }

        // KIT_ITEMS - add kit_id column (kit_producto_id already exists)
        if (!Schema::hasColumn('kit_items', 'kit_id')) {
            Schema::table('kit_items', function (Blueprint $table) {
                $table->foreignId('kit_id')->nullable()->constrained('productos')->onDelete('cascade');
            });
        }

        // PRODUCTO_SERIES - numero_serie
        if (!Schema::hasColumn('producto_series', 'numero_serie')) {
            Schema::table('producto_series', function (Blueprint $table) {
                $table->string('numero_serie', 100)->nullable()->unique()->after('producto_id');
            });
        }

        // VENTAS - updated_by
        if (!Schema::hasColumn('ventas', 'updated_by')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
    }
};
