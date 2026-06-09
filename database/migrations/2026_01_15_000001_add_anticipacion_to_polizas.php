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
            $table->integer('mantenimiento_dias_anticipacion')->default(7)->after('generar_cita_automatica');
        });

        Schema::table('polizas_servicio', function (Blueprint $table) {
            $table->integer('mantenimiento_dias_anticipacion')->default(7)->after('generar_cita_automatica');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_polizas', function (Blueprint $table) {
            $table->dropColumn('mantenimiento_dias_anticipacion');
        });

        Schema::table('polizas_servicio', function (Blueprint $table) {
            $table->dropColumn('mantenimiento_dias_anticipacion');
        });
    }
};
