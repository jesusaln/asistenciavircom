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
            $table->integer('mantenimiento_frecuencia_meses')->nullable()->after('monto_mensual')
                ->comment('Frecuencia en meses para generar mantenimientos preventivos');
            $table->date('proximo_mantenimiento_at')->nullable()->after('mantenimiento_frecuencia_meses')
                ->comment('Fecha programada para el próximo mantenimiento preventivo');
            $table->boolean('generar_cita_automatica')->default(false)->after('proximo_mantenimiento_at')
                ->comment('Indica si se debe generar una cita automática al llegar la fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('polizas_servicio', function (Blueprint $table) {
            $table->dropColumn([
                'mantenimiento_frecuencia_meses',
                'proximo_mantenimiento_at',
                'generar_cita_automatica'
            ]);
        });
    }
};
