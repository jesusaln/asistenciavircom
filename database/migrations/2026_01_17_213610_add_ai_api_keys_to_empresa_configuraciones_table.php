<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            // AI Configuration
            $table->string('ai_provider')->default('groq')->nullable();
            $table->text('groq_api_key')->nullable(); // Encrypted
            $table->string('groq_model')->default('llama-3.3-70b-versatile')->nullable();
            $table->decimal('groq_temperature', 3, 2)->default(0.70)->nullable();

            // Ollama (backup/local)
            $table->string('ollama_base_url')->default('http://localhost:11434')->nullable();
            $table->string('ollama_model')->default('llama3.1')->nullable();

            // Chatbot settings
            $table->boolean('chatbot_enabled')->default(true);
            $table->text('chatbot_system_prompt')->nullable();
            $table->string('chatbot_name')->default('VircomBot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            $table->dropColumn([
                'ai_provider',
                'groq_api_key',
                'groq_model',
                'groq_temperature',
                'ollama_base_url',
                'ollama_model',
                'chatbot_enabled',
                'chatbot_system_prompt',
                'chatbot_name',
            ]);
        });
    }
};
