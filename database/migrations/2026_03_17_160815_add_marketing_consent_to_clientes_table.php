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
        if (!Schema::hasColumn('clientes', 'sms_optin')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->boolean('sms_optin')->default(false)->after('whatsapp_consent_source');
                $table->boolean('marketing_optin')->default(false)->after('sms_optin');
                $table->timestamp('opt_out_at')->nullable()->after('marketing_optin');
                $table->string('unsubscribed_reason')->nullable()->after('opt_out_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['sms_optin', 'marketing_optin', 'opt_out_at', 'unsubscribed_reason']);
        });
    }
};
