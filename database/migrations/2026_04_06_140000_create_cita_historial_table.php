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
        if (!Schema::hasTable('cita_historial')) {
            Schema::create('cita_historial', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cita_id')->constrained('citas')->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->string('estado_anterior')->nullable();
                $table->string('estado_nuevo');
                $table->text('comentario')->nullable();
                $table->json('metadatos')->nullable(); // Para guardar IP, User-Agent, etc.
                $table->timestamps();

                $table->index('cita_id');
                $table->index('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cita_historial');
    }
};
