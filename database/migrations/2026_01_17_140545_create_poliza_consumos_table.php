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
        Schema::create('poliza_consumos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poliza_id')->constrained('polizas_servicio')->onDelete('cascade');
            $table->string('tipo'); // 'ticket', 'visita', 'hora'
            $table->morphs('consumible'); // Ticket, Cita, etc.
            $table->integer('cantidad')->default(1);
            $table->decimal('valor_unitario', 10, 2)->default(0); // Valor de referencia del servicio
            $table->decimal('ahorro', 10, 2)->default(0); // Lo que el cliente se ahorró
            $table->text('descripcion')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_consumo')->useCurrent();
            $table->timestamps();

            // Índices para búsquedas rápidas
            $table->index(['poliza_id', 'tipo']);
            $table->index(['poliza_id', 'fecha_consumo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poliza_consumos');
    }
};
