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
            $table->integer('visitas_sitio_mensuales')->nullable()->after('horas_consumidas_mes')
                ->comment('Visitas en sitio incluidas por mes');
            $table->integer('visitas_sitio_consumidas_mes')->default(0)->after('visitas_sitio_mensuales')
                ->comment('Visitas en sitio consumidas en el mes actual');
            $table->decimal('costo_visita_sitio_extra', 10, 2)->nullable()->after('visitas_sitio_consumidas_mes')
                ->comment('Costo por visita en sitio adicional');
        });

        Schema::table('plan_polizas', function (Blueprint $table) {
            $table->integer('visitas_sitio_mensuales')->nullable()->after('horas_incluidas');
            $table->decimal('costo_visita_sitio_extra', 10, 2)->nullable()->after('visitas_sitio_mensuales');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('polizas_servicio', function (Blueprint $table) {
            $table->dropColumn(['visitas_sitio_mensuales', 'visitas_sitio_consumidas_mes', 'costo_visita_sitio_extra']);
        });

        Schema::table('plan_polizas', function (Blueprint $table) {
            $table->dropColumn(['visitas_sitio_mensuales', 'costo_visita_sitio_extra']);
        });
    }
};
