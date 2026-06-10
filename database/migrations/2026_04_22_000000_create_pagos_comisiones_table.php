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
        if (!Schema::hasTable('pagos_comisiones')) {
            Schema::create('pagos_comisiones', function (Blueprint $table) {
                $table->id();
                // Nota: empresa_id se añade en la migración 2026_04_23_082001
                $table->morphs('vendedor');
                $table->date('periodo_inicio');
                $table->date('periodo_fin');
                $table->decimal('monto_comision', 15, 2)->default(0);
                $table->decimal('monto_pagado', 15, 2)->default(0);
                $table->string('estado')->default('pendiente');
                $table->date('fecha_pago')->nullable();
                $table->string('metodo_pago')->nullable();
                $table->string('referencia_pago')->nullable();
                $table->unsignedBigInteger('cuenta_bancaria_id')->nullable();
                $table->json('detalles_ventas')->nullable();
                $table->integer('num_ventas')->default(0);
                $table->decimal('total_ventas', 15, 2)->default(0);
                $table->text('notas')->nullable();
                $table->unsignedBigInteger('pagado_por')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos_comisiones');
    }
};
