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
        Schema::table('citas', function (Blueprint $blueprint) {
            // Asegurar que existan campos para totales si no están (algunos podrían ya estar)
            if (!Schema::hasColumn('citas', 'subtotal')) {
                $blueprint->decimal('subtotal', 15, 2)->default(0)->after('notas');
            }
            if (!Schema::hasColumn('citas', 'iva')) {
                $blueprint->decimal('iva', 15, 2)->default(0)->after('subtotal');
            }
            if (!Schema::hasColumn('citas', 'total')) {
                $blueprint->decimal('total', 15, 2)->default(0)->after('iva');
            }
            if (!Schema::hasColumn('citas', 'descuento_general')) {
                $blueprint->decimal('descuento_general', 15, 2)->default(0)->after('subtotal');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'iva', 'total', 'descuento_general']);
        });
    }
};
