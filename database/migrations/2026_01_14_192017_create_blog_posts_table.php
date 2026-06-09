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
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->nullable()->index();
            $table->string('titulo');
            $table->string('slug')->unique();
            $table->text('resumen')->nullable();
            $table->longText('contenido');
            $table->string('imagen_portada')->nullable();
            $table->string('categoria')->nullable()->index();
            $table->enum('status', ['draft', 'published', 'scheduled'])->default('draft');
            $table->timestamp('publicado_at')->nullable();
            $table->integer('visitas')->default(0);

            // SEO
            $table->string('meta_titulo')->nullable();
            $table->text('meta_descripcion')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
