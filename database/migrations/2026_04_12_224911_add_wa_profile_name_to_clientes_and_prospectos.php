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
            if (!Schema::hasColumn('clientes', 'wa_profile_name')) {
                $table->string('wa_profile_name')->nullable()->after('wa_username');
            }
        });

        if (Schema::hasTable('crm_prospectos')) {
            Schema::table('crm_prospectos', function (Blueprint $table) {
                if (!Schema::hasColumn('crm_prospectos', 'wa_profile_name')) {
                    $table->string('wa_profile_name')->nullable()->after('wa_username');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            if (Schema::hasColumn('clientes', 'wa_profile_name')) {
                $table->dropColumn('wa_profile_name');
            }
        });

        if (Schema::hasTable('crm_prospectos')) {
            Schema::table('crm_prospectos', function (Blueprint $table) {
                if (Schema::hasColumn('crm_prospectos', 'wa_profile_name')) {
                    $table->dropColumn('wa_profile_name');
                }
            });
        }
    }
};
