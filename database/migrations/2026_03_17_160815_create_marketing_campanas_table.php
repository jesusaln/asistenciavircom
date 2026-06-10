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
        if (!Schema::hasTable('marketing_campanas')) {
            Schema::create('marketing_campanas', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('nombre');
                $table->text('descripcion')->nullable();
                $table->enum('tipo', ['whatsapp', 'sms', 'email'])->default('whatsapp');
                $table->string('plantilla_id')->nullable(); // Para Meta/WhatsApp
                $table->json('data_plantilla')->nullable(); // Para mapeo de variables
                $table->enum('estado', ['borrador', 'programado', 'en_proceso', 'completado', 'fallido'])->default('borrador');
                $table->timestamp('fecha_programacion')->nullable();
                $table->foreignId('user_id')->constrained('users');
                $table->foreignId('empresa_id')->constrained('empresas');
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_campanas');
    }
};
