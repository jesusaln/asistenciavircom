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
        Schema::create('polizas_servicio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresa_configuracion')->onDelete('cascade');
            $table->string('folio')->unique();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->string('nombre'); // Nombre descriptivo de la póliza
            $table->text('descripcion')->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->decimal('monto_mensual', 15, 2);
            $table->integer('dia_cobro')->default(1);
            $table->string('estado')->default('activa'); // activa, inactiva, vencida, cancelada
            $table->integer('limite_mensual_tickets')->nullable();
            $table->boolean('notificar_exceso_limite')->default(true);
            $table->boolean('renovacion_automatica')->default(true);
            $table->json('condiciones_especiales')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('polizas_servicio');
    }
};
