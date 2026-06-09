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
        Schema::table('compra_items', function (Blueprint $table) {
            if (!Schema::hasColumn('compra_items', 'descripcion')) {
                $table->string('descripcion')->nullable()->after('descuento_monto');
            }
            if (!Schema::hasColumn('compra_items', 'unidad_medida')) {
                $table->string('unidad_medida')->nullable()->after('descripcion');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compra_items', function (Blueprint $table) {
            if (Schema::hasColumn('compra_items', 'descripcion')) {
                $table->dropColumn('descripcion');
            }
            if (Schema::hasColumn('compra_items', 'unidad_medida')) {
                $table->dropColumn('unidad_medida');
            }
        });
    }
};
