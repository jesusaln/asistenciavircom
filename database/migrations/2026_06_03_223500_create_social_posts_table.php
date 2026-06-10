<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('plataforma'); // facebook, instagram
            $table->string('post_id')->nullable(); // ID del post en Meta
            $table->string('estado'); // borrador, publicado, error
            $table->text('mensaje')->nullable();
            $table->string('imagen_url')->nullable();
            $table->string('error_message')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'estado']);
            $table->index(['empresa_id', 'plataforma']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_posts');
    }
};
