<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración de rescate: añade todas las columnas faltantes a cuentas_por_pagar
 * 
 * Columnas necesarias según el modelo:
 * - pue_pagado: indica si fue marcada como pagada con método PUE
 * - pagado_con_rep: indica si fue pagada con REP (Recibo Electrónico de Pago)
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('cuentas_por_pagar', function (Blueprint $table) {
            // Columna para marcar si fue pagado con REP
            if (!Schema::hasColumn('cuentas_por_pagar', 'pagado_con_rep')) {
                $table->boolean('pagado_con_rep')->default(false)->after('pue_pagado');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cuentas_por_pagar', function (Blueprint $table) {
            if (Schema::hasColumn('cuentas_por_pagar', 'pagado_con_rep')) {
                $table->dropColumn('pagado_con_rep');
            }
        });
    }
};
