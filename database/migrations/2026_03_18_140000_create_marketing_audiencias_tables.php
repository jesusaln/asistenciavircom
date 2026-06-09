<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_audiencias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('marketing_audiencia_clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audiencia_id')->constrained('marketing_audiencias')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['audiencia_id', 'cliente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_audiencia_clientes');
        Schema::dropIfExists('marketing_audiencias');
    }
};
