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
            $table->string('stripe_public_key')->nullable()->after('paypal_sandbox');
            $table->string('stripe_secret_key')->nullable()->after('stripe_public_key');
            $table->string('stripe_webhook_secret')->nullable()->after('stripe_secret_key');
            $table->boolean('stripe_sandbox')->default(true)->after('stripe_webhook_secret');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_public_key',
                'stripe_secret_key',
                'stripe_webhook_secret',
                'stripe_sandbox'
            ]);
        });
    }
};
