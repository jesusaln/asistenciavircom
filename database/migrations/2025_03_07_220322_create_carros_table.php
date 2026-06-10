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
        if (!Schema::hasTable('carros')) {
            Schema::create('carros', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->string('marca')->nullable();
                $table->string('modelo')->nullable();
                $table->integer('anio')->nullable();
                $table->string('color')->nullable();
                $table->decimal('precio', 15, 2)->nullable();
                $table->string('numero_serie')->nullable()->unique();
                $table->string('combustible')->nullable();
                $table->integer('kilometraje')->nullable();
                $table->string('placa')->nullable();
                $table->string('foto')->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carros');
    }
};
