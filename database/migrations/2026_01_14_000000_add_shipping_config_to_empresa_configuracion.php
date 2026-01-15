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
        Schema::table('empresa_configuracion', function (Blueprint $blueprint) {
            $blueprint->string('shipping_local_cp_prefix')->default('83')->nullable()->after('cva_paqueteria_envio')->comment('Prefijo de CP para envío local');
            $blueprint->decimal('shipping_local_cost', 10, 2)->default(100.00)->after('shipping_local_cp_prefix')->comment('Costo de envío local en MXN');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresa_configuracion', function (Blueprint $blueprint) {
            $blueprint->dropColumn(['shipping_local_cp_prefix', 'shipping_local_cost']);
        });
    }
};
