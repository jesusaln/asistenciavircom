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
        Schema::create('marketing_mensajes_entrantes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes');
            $table->string('telefono');
            $table->text('mensaje');
            $table->enum('plataforma', ['whatsapp', 'sms'])->default('whatsapp');
            $table->json('metadata')->nullable(); // Guardar el payload bruto de Meta
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_mensajes_entrantes');
    }
};
