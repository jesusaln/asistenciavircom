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
            $table->boolean('cva_auto_pago')->default(false)->after('cva_tipo_cambio_last_update');
            $table->decimal('cva_monedero_balance', 15, 2)->default(0.00)->after('cva_auto_pago');
            $table->timestamp('cva_monedero_last_update')->nullable()->after('cva_monedero_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            $table->dropColumn([
                'cva_auto_pago',
                'cva_monedero_balance',
                'cva_monedero_last_update'
            ]);
        });
    }
};
