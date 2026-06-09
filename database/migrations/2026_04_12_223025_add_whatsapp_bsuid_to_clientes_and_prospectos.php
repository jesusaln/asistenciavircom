<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('wa_user_id')->nullable()->after('whatsapp_consent_source')->index();
            $table->string('wa_username')->nullable()->after('wa_user_id');
        });

        Schema::table('crm_prospectos', function (Blueprint $table) {
            $table->string('wa_user_id')->nullable()->after('notas')->index();
            $table->string('wa_username')->nullable()->after('wa_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['wa_user_id', 'wa_username']);
        });

        Schema::table('crm_prospectos', function (Blueprint $table) {
            $table->dropColumn(['wa_user_id', 'wa_username']);
        });
    }
};
