<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * Tabla para configurar la disponibilidad de horarios de cada técnico
     */
    public function up(): void
    {
        Schema::create('disponibilidad_tecnicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->onDelete('cascade');
            $table->foreignId('tecnico_id')->constrained('users')->onDelete('cascade');

            // Día de la semana (0 = Domingo, 1 = Lunes, ... 6 = Sábado)
            $table->unsignedTinyInteger('dia_semana')->comment('0=Dom, 1=Lun, 2=Mar, 3=Mie, 4=Jue, 5=Vie, 6=Sab');

            // Horario de disponibilidad
            $table->time('hora_inicio')->default('08:00:00');
            $table->time('hora_fin')->default('18:00:00');

            // Límites
            $table->unsignedTinyInteger('max_citas_dia')->default(5)
                ->comment('Máximo de citas que puede tener este día');

            // Estado
            $table->boolean('activo')->default(true);

            $table->timestamps();

            // Índices
            $table->unique(['tecnico_id', 'dia_semana'], 'tecnico_dia_unico');
            $table->index(['empresa_id', 'dia_semana', 'activo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disponibilidad_tecnicos');
    }
};
