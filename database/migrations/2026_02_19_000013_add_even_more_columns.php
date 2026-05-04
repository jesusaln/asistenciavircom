<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // EMPRESA_CONFIGURACION
        if (!Schema::hasColumn('empresa_configuracion', 'enable_retencion_isr')) {
            Schema::table('empresa_configuracion', function (Blueprint $table) {
                $table->boolean('enable_retencion_isr')->default(false)->after('enable_retencion_iva');
            });
        }
        if (!Schema::hasColumn('empresa_configuracion', 'numero_exterior')) {
            Schema::table('empresa_configuracion', function (Blueprint $table) {
                $table->string('numero_exterior', 20)->nullable()->after('calle');
            });
        }

        // PRODUCTO_SERIES - estado
        if (!Schema::hasColumn('producto_series', 'estado')) {
            Schema::table('producto_series', function (Blueprint $table) {
                $table->string('estado', 20)->default('disponible')->after('numero_serie');
            });
        }

        // KIT_ITEMS - item_type
        if (!Schema::hasColumn('kit_items', 'item_type')) {
            Schema::table('kit_items', function (Blueprint $table) {
                $table->string('item_type', 50)->default('producto')->after('producto_id');
            });
        }
    }

    public function down(): void
    {
    }
};
