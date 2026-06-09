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
        Schema::table('plan_rentas', function (Blueprint $table) {
            $table->decimal('precio_venta', 10, 2)->nullable()->after('precio_mensual')->comment('Precio de venta total si el cliente desea comprar');
            $table->boolean('disponible_venta')->default(false)->after('precio_venta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_rentas', function (Blueprint $table) {
            $table->dropColumn(['precio_venta', 'disponible_venta']);
        });
    }
};
