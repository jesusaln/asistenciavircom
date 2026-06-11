<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos_online', function (Blueprint $table) {
            if (!Schema::hasColumn('pedidos_online', 'meli_order_id')) {
                $table->bigInteger('meli_order_id')->nullable()->unique()->after('cva_pedido_id');
            }
            if (!Schema::hasColumn('pedidos_online', 'meli_shipment_id')) {
                $table->bigInteger('meli_shipment_id')->nullable()->after('meli_order_id');
            }
            if (!Schema::hasColumn('pedidos_online', 'meli_tracking_notified')) {
                $table->boolean('meli_tracking_notified')->default(false)->after('meli_shipment_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pedidos_online', function (Blueprint $table) {
            $table->dropColumn(['meli_order_id', 'meli_shipment_id', 'meli_tracking_notified']);
        });
    }
};
