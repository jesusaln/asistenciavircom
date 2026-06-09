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
            $table->unsignedBigInteger('cuenta_id_paypal')->nullable()->after('paypal_sandbox');
            $table->unsignedBigInteger('cuenta_id_mercadopago')->nullable()->after('mercadopago_sandbox');
            $table->unsignedBigInteger('cuenta_id_stripe')->nullable()->after('stripe_sandbox');

            if (Schema::hasTable('cuentas_bancarias')) {
                $table->foreign('cuenta_id_paypal')->references('id')->on('cuentas_bancarias')->onDelete('set null');
                $table->foreign('cuenta_id_mercadopago')->references('id')->on('cuentas_bancarias')->onDelete('set null');
                $table->foreign('cuenta_id_stripe')->references('id')->on('cuentas_bancarias')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            $table->dropForeign(['cuenta_id_paypal']);
            $table->dropForeign(['cuenta_id_mercadopago']);
            $table->dropForeign(['cuenta_id_stripe']);
            $table->dropColumn(['cuenta_id_paypal', 'cuenta_id_mercadopago', 'cuenta_id_stripe']);
        });
    }
};
