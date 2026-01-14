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
        Schema::table('plan_polizas', function (Blueprint $table) {
            $table->integer('mantenimiento_frecuencia_meses')->nullable()->after('tickets_incluidos')
                ->comment('Frecuencia en meses sugerida para mantenimientos preventivos');
            $table->boolean('generar_cita_automatica')->default(false)->after('mantenimiento_frecuencia_meses')
                ->comment('Sugerencia de si se debe generar cita automática');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_polizas', function (Blueprint $table) {
            $table->dropColumn([
                'mantenimiento_frecuencia_meses',
                'generar_cita_automatica'
            ]);
        });
    }
};
