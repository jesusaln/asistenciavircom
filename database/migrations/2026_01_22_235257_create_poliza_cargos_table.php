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
        Schema::create('poliza_cargos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poliza_id')->constrained('polizas_servicio')->onDelete('cascade');

            $table->decimal('subtotal', 12, 2);
            $table->decimal('iva', 12, 2);
            $table->decimal('total', 12, 2);
            $table->string('moneda', 5)->default('MXN');

            $table->string('concepto');
            $table->string('tipo_ciclo'); // mensual, trimestral, semestral, anual, instalacion, extra

            $table->date('fecha_emision');
            $table->date('fecha_vencimiento');

            $table->enum('estado', ['pendiente', 'pagado', 'vencido', 'cancelado'])->default('pendiente');

            $table->string('referencia_pago')->nullable();
            $table->string('metodo_pago')->nullable();
            $table->dateTime('fecha_pago')->nullable();

            $table->text('notas')->nullable();
            $table->json('metadata')->nullable(); // Para guardar info de consumos extras si aplica

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poliza_cargos');
    }
};
