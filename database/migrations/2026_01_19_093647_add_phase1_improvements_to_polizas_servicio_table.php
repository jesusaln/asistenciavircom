<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FASE 1 - Mejora 1.4: Periodo de gracia para pagos atrasados
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('polizas_servicio', function (Blueprint $table) {
            // Días de gracia antes de marcar como vencida (default 5 días)
            $table->unsignedTinyInteger('dias_gracia')->default(5)->after('fecha_fin');
            // Nuevo estado intermedio para cuando está en periodo de gracia
            // El estado 'vencida_en_gracia' se manejará como string en el campo estado existente
        });
    }

    public function down(): void
    {
        Schema::table('polizas_servicio', function (Blueprint $table) {
            $table->dropColumn('dias_gracia');
        });
    }
};
