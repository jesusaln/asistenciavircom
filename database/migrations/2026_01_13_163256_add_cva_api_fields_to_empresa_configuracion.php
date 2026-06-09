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
            $blueprint->boolean('cva_active')->default(false)->after('stripe_webhook_secret');
            $blueprint->string('cva_user')->nullable()->after('cva_active');
            $blueprint->string('cva_password')->nullable()->after('cva_user');
            $blueprint->decimal('cva_utility_percentage', 5, 2)->default(15.00)->after('cva_password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresa_configuracion', function (Blueprint $blueprint) {
            $blueprint->dropColumn(['cva_active', 'cva_user', 'cva_password', 'cva_utility_percentage']);
        });
    }
};
