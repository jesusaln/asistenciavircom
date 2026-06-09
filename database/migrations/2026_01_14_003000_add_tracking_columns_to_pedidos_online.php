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
        Schema::table('pedidos_online', function (Blueprint $blueprint) {
            $blueprint->string('cva_pedido_id')->nullable()->after('payment_details')->index();
            $blueprint->string('guia_envio')->nullable()->after('cva_pedido_id');
            $blueprint->string('paqueteria')->nullable()->after('guia_envio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos_online', function (Blueprint $blueprint) {
            $blueprint->dropColumn(['cva_pedido_id', 'guia_envio', 'paqueteria']);
        });
    }
};
