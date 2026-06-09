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
        Schema::table('polizas_servicio', function (Blueprint $table) {
            $table->timestamp('ultimo_cobro_generado_at')->nullable()->after('dia_cobro');
            $table->integer('sla_horas_respuesta')->nullable()->after('limite_mensual_tickets');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('polizas_servicio', function (Blueprint $table) {
            $table->dropColumn(['ultimo_cobro_generado_at', 'sla_horas_respuesta']);
        });
    }
};
