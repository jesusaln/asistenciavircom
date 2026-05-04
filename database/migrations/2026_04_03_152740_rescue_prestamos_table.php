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
        Schema::table('prestamos', function (Blueprint $table) {
            if (!Schema::hasColumn('prestamos', 'monto_prestado')) {
                $table->decimal('monto_prestado', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('prestamos', 'tasa_interes_mensual')) {
                $table->decimal('tasa_interes_mensual', 5, 2)->default(0);
            }
            if (!Schema::hasColumn('prestamos', 'numero_pagos')) {
                $table->integer('numero_pagos')->default(0);
            }
            if (!Schema::hasColumn('prestamos', 'frecuencia_pago')) {
                $table->string('frecuencia_pago')->default('mensual');
            }
            if (!Schema::hasColumn('prestamos', 'fecha_inicio')) {
                $table->date('fecha_inicio')->nullable();
            }
            if (!Schema::hasColumn('prestamos', 'fecha_primer_pago')) {
                $table->date('fecha_primer_pago')->nullable();
            }
            if (!Schema::hasColumn('prestamos', 'monto_interes_total')) {
                $table->decimal('monto_interes_total', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('prestamos', 'monto_total_pagar')) {
                $table->decimal('monto_total_pagar', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('prestamos', 'pago_periodico')) {
                $table->decimal('pago_periodico', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('prestamos', 'pagos_realizados')) {
                $table->integer('pagos_realizados')->default(0);
            }
            if (!Schema::hasColumn('prestamos', 'pagos_pendientes')) {
                $table->integer('pagos_pendientes')->default(0);
            }
            if (!Schema::hasColumn('prestamos', 'monto_pagado')) {
                $table->decimal('monto_pagado', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('prestamos', 'monto_pendiente')) {
                $table->decimal('monto_pendiente', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('prestamos', 'descripcion')) {
                $table->text('descripcion')->nullable();
            }
            if (!Schema::hasColumn('prestamos', 'notas')) {
                $table->text('notas')->nullable();
            }
            if (!Schema::hasColumn('prestamos', 'activo')) {
                $table->boolean('activo')->default(true);
            }
            if (!Schema::hasColumn('prestamos', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not dropping for safety in rescue migration
    }
};
