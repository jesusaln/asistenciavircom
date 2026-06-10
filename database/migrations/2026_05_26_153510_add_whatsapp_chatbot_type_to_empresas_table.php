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
        Schema::table('empresas', function (Blueprint $table) {
            if (!Schema::hasColumn('empresas', 'whatsapp_chatbot_type')) {
                $table->string('whatsapp_chatbot_type')->default('ai')->after('whatsapp_chatbot_mode');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            if (Schema::hasColumn('empresas', 'whatsapp_chatbot_type')) {
                $table->dropColumn('whatsapp_chatbot_type');
            }
        });
    }
};
