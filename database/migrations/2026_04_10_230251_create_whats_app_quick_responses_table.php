<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('whats_app_quick_responses')) {
            Schema::create('whats_app_quick_responses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
                $table->string('shortcut'); // ej: /precio
                $table->text('message');
                $table->string('type')->default('text'); // text, image
                $table->string('url')->nullable(); // Para imágenes
                $table->timestamps();

                $table->unique(['empresa_id', 'shortcut']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('whats_app_quick_responses');
    }
};
