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
        Schema::create('landing_ofertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->onDelete('cascade');

            // Información de la oferta
            $table->string('titulo')->default('🔥 OFERTA ESPECIAL');
            $table->string('subtitulo');
            $table->text('descripcion')->nullable();

            // Precios y descuento
            $table->integer('descuento_porcentaje')->default(20);
            $table->decimal('precio_original', 12, 2);
            $table->decimal('precio_oferta', 12, 2)->nullable();

            // Características (hasta 3)
            $table->string('caracteristica_1')->nullable();
            $table->string('caracteristica_2')->nullable();
            $table->string('caracteristica_3')->nullable();

            // Vigencia
            $table->timestamp('fecha_inicio')->nullable();
            $table->timestamp('fecha_fin')->nullable();

            // Control
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_ofertas');
    }
};
