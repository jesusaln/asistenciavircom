<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('empresa_configuracion', 'gemini_api_key')) {
            Schema::table('empresa_configuracion', function (Blueprint $table) {
                // Google Gemini AI
                $table->text('gemini_api_key')->nullable()->after('groq_temperature');
                $table->string('gemini_model')->default('gemini-2.0-flash')->nullable()->after('gemini_api_key');
                $table->decimal('gemini_temperature', 3, 2)->default(0.70)->nullable()->after('gemini_model');
            });
        }
    }

    public function down(): void
    {
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            $table->dropColumn([
                'gemini_api_key',
                'gemini_model',
                'gemini_temperature',
            ]);
        });
    }
};
