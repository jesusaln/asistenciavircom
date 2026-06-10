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
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('tipo')->default('contrato'); // contrato, adenda, aviso, nom035
            $table->string('titulo');
            $table->longText('contenido')->nullable();
            $table->string('archivo_path')->nullable();
            $table->string('estado')->default('borrador'); // borrador, pendiente_firma, firmado, cancelado
            $table->json('metadata')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('constancia_nom151')->nullable();
            $table->string('hash_documento')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contratos');
    }
};
