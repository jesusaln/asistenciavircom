<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // EMPRESA_CONFIGURACION
        if (!Schema::hasColumn('empresa_configuracion', 'formato_numeros')) {
            Schema::table('empresa_configuracion', function (Blueprint $table) {
                $table->string('formato_numeros', 20)->default('estandar')->after('numero_exterior');
            });
        }
        if (!Schema::hasColumn('empresa_configuracion', 'numero_interior')) {
            Schema::table('empresa_configuracion', function (Blueprint $table) {
                $table->string('numero_interior', 20)->nullable()->after('numero_exterior');
            });
        }

        // PRODUCTO_SERIES - deleted_at (soft deletes)
        if (!Schema::hasColumn('producto_series', 'deleted_at')) {
            Schema::table('producto_series', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // KIT_ITEMS - item_id (polymorphic)
        if (!Schema::hasColumn('kit_items', 'item_id')) {
            Schema::table('kit_items', function (Blueprint $table) {
                $table->unsignedBigInteger('item_id')->nullable()->after('producto_id');
            });
        }
    }

    public function down(): void
    {
    }
};
