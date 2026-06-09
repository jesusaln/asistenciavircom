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
            // E-commerce toggle
            $table->boolean('tienda_online_activa')->default(false)->after('zerotier_notas');

            // Google OAuth
            $table->string('google_client_id')->nullable()->after('tienda_online_activa');
            $table->string('google_client_secret')->nullable()->after('google_client_id');

            // Microsoft OAuth
            $table->string('microsoft_client_id')->nullable()->after('google_client_secret');
            $table->string('microsoft_client_secret')->nullable()->after('microsoft_client_id');
            $table->string('microsoft_tenant_id')->default('common')->after('microsoft_client_secret');

            // MercadoPago
            $table->string('mercadopago_access_token')->nullable()->after('microsoft_tenant_id');
            $table->string('mercadopago_public_key')->nullable()->after('mercadopago_access_token');
            $table->boolean('mercadopago_sandbox')->default(true)->after('mercadopago_public_key');

            // PayPal
            $table->string('paypal_client_id')->nullable()->after('mercadopago_sandbox');
            $table->string('paypal_client_secret')->nullable()->after('paypal_client_id');
            $table->boolean('paypal_sandbox')->default(true)->after('paypal_client_secret');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            $table->dropColumn([
                'tienda_online_activa',
                'google_client_id',
                'google_client_secret',
                'microsoft_client_id',
                'microsoft_client_secret',
                'microsoft_tenant_id',
                'mercadopago_access_token',
                'mercadopago_public_key',
                'mercadopago_sandbox',
                'paypal_client_id',
                'paypal_client_secret',
                'paypal_sandbox',
            ]);
        });
    }
};
