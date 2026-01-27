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
        Schema::create('cliente_direcciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->string('nombre')->nullable()->comment('Nombre de la sucursal o ubicación, e.g. "Matriz", "Almacén"');
            $table->string('calle');
            $table->string('numero_exterior');
            $table->string('numero_interior')->nullable();
            $table->string('colonia');
            $table->string('codigo_postal');
            $table->string('ciudad')->nullable();
            $table->string('municipio');
            $table->string('estado');
            $table->string('pais')->default('MX');
            $table->string('referencias')->nullable();
            $table->string('contacto_nombre')->nullable();
            $table->string('contacto_telefono')->nullable();
            $table->boolean('es_principal')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cliente_direcciones');
    }
};
