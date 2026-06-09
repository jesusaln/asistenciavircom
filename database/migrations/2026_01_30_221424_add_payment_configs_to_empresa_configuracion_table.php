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
            // MercadoPago
            if (!Schema::hasColumn('empresa_configuracion', 'mercadopago_access_token')) {
                $table->string('mercadopago_access_token')->nullable();
                $table->string('mercadopago_public_key')->nullable();
                $table->boolean('mercadopago_sandbox')->default(true);
            }

            // PayPal
            if (!Schema::hasColumn('empresa_configuracion', 'paypal_client_id')) {
                $table->string('paypal_client_id')->nullable();
                $table->string('paypal_client_secret')->nullable();
                $table->boolean('paypal_sandbox')->default(true);
            }

            // Stripe
            if (!Schema::hasColumn('empresa_configuracion', 'stripe_public_key')) {
                $table->string('stripe_public_key')->nullable();
                $table->string('stripe_secret_key')->nullable();
                $table->string('stripe_webhook_secret')->nullable();
                $table->boolean('stripe_sandbox')->default(true);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            $table->dropColumn([
                'mercadopago_access_token',
                'mercadopago_public_key',
                'mercadopago_sandbox',
                'paypal_client_id',
                'paypal_client_secret',
                'paypal_sandbox',
                'stripe_public_key',
                'stripe_secret_key',
                'stripe_webhook_secret',
                'stripe_sandbox'
            ]);
        });
    }
};
