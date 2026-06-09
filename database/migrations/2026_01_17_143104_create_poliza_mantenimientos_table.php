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
        // 1. Catálogo de Tareas recurrentes por Póliza
        Schema::create('poliza_mantenimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poliza_id')->constrained('polizas_servicio')->onDelete('cascade');
            $table->string('tipo', 50); // 'respaldo', 'disco', 'cctv', 'alarma', 'general'
            $table->string('nombre');
            $table->text('descripcion')->nullable();

            // Configuración de recurrencia
            $table->string('frecuencia', 20); // 'semanal', 'quincenal', 'mensual', 'bimestral', 'trimestral', 'semestral'
            $table->integer('dia_preferido')->default(1); // Día del mes sugerido (1-28) o día de semana (1-7)

            $table->boolean('requiere_visita')->default(false);
            $table->boolean('activo')->default(true);

            $table->timestamp('ultima_generacion_at')->nullable(); // Cuándo se generó la última orden
            $table->timestamps();

            $table->index(['poliza_id', 'activo']);
        });

        // 2. Instancias de ejecución (La Agenda)
        Schema::create('poliza_mantenimiento_ejecuciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mantenimiento_id')->constrained('poliza_mantenimientos')->onDelete('cascade');
            $table->foreignId('tecnico_id')->nullable()->constrained('users')->nullOnDelete();

            // Control de Agenda
            $table->datetime('fecha_programada'); // Cuándo se debe hacer (editable)
            $table->datetime('fecha_original');   // Cuándo tocaba originalmente (para métricas)
            $table->integer('reprogramado_count')->default(0); // Cuántas veces se ha movido
            $table->text('notas_reprogramacion')->nullable(); // Historial de por qué se movió

            // Ejecución
            $table->datetime('fecha_ejecucion')->nullable(); // Cuándo se hizo realmente
            $table->string('estado', 20)->default('pendiente'); // 'pendiente', 'completado', 'cancelado', 'vencido'
            $table->string('resultado', 20)->nullable(); // 'ok', 'alerta', 'critico'

            $table->text('notas_tecnico')->nullable();
            $table->json('evidencia')->nullable(); // URLs de fotos, valores medidos, etc.

            $table->boolean('notificado_cliente')->default(false);
            $table->timestamps();

            // Índices
            $table->index('fecha_programada');
            $table->index('estado');
            $table->index(['tecnico_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poliza_mantenimiento_ejecuciones');
        Schema::dropIfExists('poliza_mantenimientos');
    }
};
