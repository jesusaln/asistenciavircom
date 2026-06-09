<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('guia_tecnicas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('route_name')->nullable()->comment('Nombre de la ruta Vue o URL');
            $table->text('descripcion')->nullable();
            $table->json('checklist_default')->nullable(); // Para precargar checklist
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guia_tecnicas');
    }
};
