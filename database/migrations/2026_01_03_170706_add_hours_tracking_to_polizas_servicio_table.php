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
            // Sistema de Horas
            $table->integer('horas_incluidas_mensual')->nullable()->after('limite_mensual_tickets')
                ->comment('Horas de servicio incluidas por mes');
            $table->decimal('horas_consumidas_mes', 8, 2)->default(0)->after('horas_incluidas_mensual')
                ->comment('Horas consumidas en el mes actual');
            $table->decimal('costo_hora_excedente', 10, 2)->nullable()->after('horas_consumidas_mes')
                ->comment('Costo por hora adicional cuando se exceden las incluidas');

            // Sistema de Alertas
            $table->integer('dias_alerta_vencimiento')->default(30)->after('notas')
                ->comment('Días antes del vencimiento para enviar alerta');
            $table->boolean('alerta_vencimiento_enviada')->default(false)->after('dias_alerta_vencimiento')
                ->comment('Indica si ya se envió la alerta de vencimiento');
            $table->timestamp('ultima_alerta_exceso_at')->nullable()->after('alerta_vencimiento_enviada')
                ->comment('Última vez que se envió alerta de exceso de límite');

            // Reset mensual de consumo
            $table->timestamp('ultimo_reset_consumo_at')->nullable()->after('ultimo_cobro_generado_at')
                ->comment('Última vez que se reseteó el consumo mensual');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('polizas_servicio', function (Blueprint $table) {
            $table->dropColumn([
                'horas_incluidas_mensual',
                'horas_consumidas_mes',
                'costo_hora_excedente',
                'dias_alerta_vencimiento',
                'alerta_vencimiento_enviada',
                'ultima_alerta_exceso_at',
                'ultimo_reset_consumo_at',
            ]);
        });
    }
};
