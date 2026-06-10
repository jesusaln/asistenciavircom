<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración de rescate: añade columna pue_pagado a cuentas_por_pagar
 * 
 * Esta columna indica si una cuenta por pagar ya fue marcada como pagada con
 * método de pago PUE (Pago en Una sola Exhibición) según el CFDI relacionado.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('cuentas_por_pagar', function (Blueprint $table) {
            if (!Schema::hasColumn('cuentas_por_pagar', 'pue_pagado')) {
                $table->boolean('pue_pagado')->default(false)->after('notas_pago');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cuentas_por_pagar', function (Blueprint $table) {
            if (Schema::hasColumn('cuentas_por_pagar', 'pue_pagado')) {
                $table->dropColumn('pue_pagado');
            }
        });
    }
};
