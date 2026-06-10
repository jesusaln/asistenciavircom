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
            if (!Schema::hasColumn('clientes', 'wa_user_id')) {
                $table->string('wa_user_id')->nullable()->after('whatsapp_consent_source')->index();
            }
            if (!Schema::hasColumn('clientes', 'wa_username')) {
                $table->string('wa_username')->nullable()->after('wa_user_id');
            }
        });

        if (Schema::hasTable('crm_prospectos')) {
            Schema::table('crm_prospectos', function (Blueprint $table) {
                if (!Schema::hasColumn('crm_prospectos', 'wa_user_id')) {
                    $table->string('wa_user_id')->nullable()->after('notas')->index();
                }
                if (!Schema::hasColumn('crm_prospectos', 'wa_username')) {
                    $table->string('wa_username')->nullable()->after('wa_user_id');
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
            $cols = [];
            if (Schema::hasColumn('clientes', 'wa_user_id')) {
                $cols[] = 'wa_user_id';
            }
            if (Schema::hasColumn('clientes', 'wa_username')) {
                $cols[] = 'wa_username';
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });

        if (Schema::hasTable('crm_prospectos')) {
            Schema::table('crm_prospectos', function (Blueprint $table) {
                $cols = [];
                if (Schema::hasColumn('crm_prospectos', 'wa_user_id')) {
                    $cols[] = 'wa_user_id';
                }
                if (Schema::hasColumn('crm_prospectos', 'wa_username')) {
                    $cols[] = 'wa_username';
                }
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
