<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->boolean('whatsapp_chatbot_enabled')->default(false)->after('whatsapp_enabled');
            $table->text('whatsapp_chatbot_prompt')->nullable()->after('whatsapp_chatbot_enabled');
            $table->string('whatsapp_chatbot_mode')->default('off_hours'); // always, off_hours, off
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_chatbot_enabled', 'whatsapp_chatbot_prompt', 'whatsapp_chatbot_mode']);
        });
    }
};
