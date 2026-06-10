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
        Schema::create('contrato_plantillas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('tipo'); // contrato_inicial, adenda_ley_silla, aviso_nom035, etc.
            $table->longText('contenido'); // Soporta variables {{nombre}}, {{rfc}}, etc.
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contrato_plantillas');
    }
};
