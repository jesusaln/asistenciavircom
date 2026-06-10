<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('asistencias')) {
            Schema::create('asistencias', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->enum('tipo', ['entrada', 'salida']);
                $table->dateTime('fecha_hora');
                $table->decimal('latitud', 10, 8)->nullable();
                $table->decimal('longitud', 11, 8)->nullable();
                $table->decimal('distancia_oficina', 10, 2)->nullable(); // en metros
                $table->boolean('fuera_de_rango')->default(false);
                $table->string('dispositivo')->nullable();
                $table->string('foto_path')->nullable();
                $table->text('notas')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
