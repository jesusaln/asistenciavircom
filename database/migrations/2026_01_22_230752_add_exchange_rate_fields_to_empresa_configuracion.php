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
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            $table->decimal('cva_tipo_cambio', 10, 4)->default(20.50)->after('cva_utility_tiers');
            $table->decimal('cva_tipo_cambio_buffer', 5, 2)->default(2.00)->after('cva_tipo_cambio'); // Porcentaje adicional
            $table->boolean('cva_tipo_cambio_auto')->default(true)->after('cva_tipo_cambio_buffer');
            $table->timestamp('cva_tipo_cambio_last_update')->nullable()->after('cva_tipo_cambio_auto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            $table->dropColumn([
                'cva_tipo_cambio',
                'cva_tipo_cambio_buffer',
                'cva_tipo_cambio_auto',
                'cva_tipo_cambio_last_update'
            ]);
        });
    }
};
