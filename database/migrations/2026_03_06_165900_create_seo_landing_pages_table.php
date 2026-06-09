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
        Schema::create('seo_landing_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->string('slug')->unique(); // camaras-de-seguridad-hermosillo
            $table->string('titulo_h1'); // Instalación de Cámaras de Seguridad en Hermosillo
            $table->text('meta_description')->nullable();
            $table->string('hero_image_url')->nullable();

            // Contenido dinámico
            $table->text('hero_title')->nullable();
            $table->text('hero_description')->nullable();

            // Configuración del catálogo a mostrar
            $table->string('service_category')->nullable(); // Para filtrar productos relacionados
            $table->string('location')->nullable(); // Hermosillo, Sonora, etc.

            // Secciones opcionales
            $table->json('features')->nullable(); // [ {icon, title, desc}, ... ]
            $table->json('content_blocks')->nullable(); // Secciones extra de texto para SEO

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_landing_pages');
    }
};
