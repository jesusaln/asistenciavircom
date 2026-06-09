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
        Schema::create('plan_rentas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->text('descripcion')->nullable();
            $table->text('descripcion_corta')->nullable();
            $table->string('tipo')->default('pdv'); // pdv, oficina, gaming, etc.
            $table->string('icono')->nullable();
            $table->string('color')->default('#3b82f6');
            $table->decimal('precio_mensual', 12, 2);
            $table->decimal('deposito_garantia', 12, 2)->default(0);
            $table->integer('meses_minimos')->default(12);
            $table->json('beneficios')->nullable();
            $table->json('equipamiento_incluido')->nullable(); // JSON con lista de equipos que contempla el plan
            $table->boolean('activo')->default(true);
            $table->boolean('destacado')->default(false);
            $table->boolean('visible_catalogo')->default(true);
            $table->integer('orden')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_rentas');
    }
};
