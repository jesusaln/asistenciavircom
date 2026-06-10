<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            if (!Schema::hasColumn('empresas', 'whatsapp_chatbot_enabled')) {
                $table->boolean('whatsapp_chatbot_enabled')->default(false)->after('whatsapp_enabled');
            }
            if (!Schema::hasColumn('empresas', 'whatsapp_chatbot_prompt')) {
                $table->text('whatsapp_chatbot_prompt')->nullable()->after('whatsapp_chatbot_enabled');
            }
            if (!Schema::hasColumn('empresas', 'whatsapp_chatbot_mode')) {
                $table->string('whatsapp_chatbot_mode')->default('off_hours'); // always, off_hours, off
            }
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('empresas', 'whatsapp_chatbot_enabled')) {
                $cols[] = 'whatsapp_chatbot_enabled';
            }
            if (Schema::hasColumn('empresas', 'whatsapp_chatbot_prompt')) {
                $cols[] = 'whatsapp_chatbot_prompt';
            }
            if (Schema::hasColumn('empresas', 'whatsapp_chatbot_mode')) {
                $cols[] = 'whatsapp_chatbot_mode';
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
