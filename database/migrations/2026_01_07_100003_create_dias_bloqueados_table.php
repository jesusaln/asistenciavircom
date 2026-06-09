<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * Tabla para bloquear días específicos (vacaciones, festivos, etc.)
     */
    public function up(): void
    {
        Schema::create('dias_bloqueados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->onDelete('cascade');

            // Técnico específico o null para todos
            $table->foreignId('tecnico_id')->nullable()->constrained('users')->onDelete('cascade')
                ->comment('Null = aplica a todos los técnicos');

            // Fecha bloqueada
            $table->date('fecha');

            // Motivo
            $table->string('motivo')->nullable()
                ->comment('Vacaciones, día festivo, capacitación, etc.');

            // Bloqueo parcial (opcional)
            $table->time('hora_inicio')->nullable()->comment('Null = todo el día');
            $table->time('hora_fin')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['empresa_id', 'fecha']);
            $table->index(['tecnico_id', 'fecha']);
            $table->unique(['empresa_id', 'tecnico_id', 'fecha'], 'bloqueo_unico');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dias_bloqueados');
    }
};
