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
            $table->string('rustdesk_server_address')->nullable()->after('minutos_tolerancia_retardo');
            $table->string('rustdesk_relay_server')->nullable()->after('rustdesk_server_address');
            $table->text('rustdesk_public_key')->nullable()->after('rustdesk_relay_server');
            $table->string('rustdesk_api_url')->nullable()->after('rustdesk_public_key');
            $table->string('rustdesk_api_token')->nullable()->after('rustdesk_api_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            $table->dropColumn([
                'rustdesk_server_address',
                'rustdesk_relay_server',
                'rustdesk_public_key',
                'rustdesk_api_url',
                'rustdesk_api_token'
            ]);
        });
    }
};
