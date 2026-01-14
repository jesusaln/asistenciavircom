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
        // FAQs - Preguntas frecuentes
        Schema::create('landing_faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->onDelete('cascade');
            $table->string('pregunta');
            $table->text('respuesta');
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Testimonios de clientes
        Schema::create('landing_testimonios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->onDelete('cascade');
            $table->string('nombre');
            $table->string('cargo')->nullable();
            $table->string('empresa_cliente')->nullable();
            $table->text('comentario');
            $table->tinyInteger('calificacion')->default(5); // 1-5 estrellas
            $table->string('foto')->nullable();
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Logos de clientes/empresas que confían
        Schema::create('landing_logos_clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->onDelete('cascade');
            $table->string('nombre_empresa');
            $table->string('logo'); // ruta del archivo
            $table->string('url')->nullable(); // link opcional
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_logos_clientes');
        Schema::dropIfExists('landing_testimonios');
        Schema::dropIfExists('landing_faqs');
    }
};
