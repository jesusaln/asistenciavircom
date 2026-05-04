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
            $table->string('wa_profile_name')->nullable()->after('wa_username');
        });

        Schema::table('crm_prospectos', function (Blueprint $table) {
            $table->string('wa_profile_name')->nullable()->after('wa_username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('wa_profile_name');
        });

        Schema::table('crm_prospectos', function (Blueprint $table) {
            $table->dropColumn('wa_profile_name');
        });
    }
};
