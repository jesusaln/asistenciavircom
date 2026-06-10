<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('polizas_servicio', 'meses_mantenimiento')) {
            Schema::table('polizas_servicio', function (Blueprint $table) {
                $table->json('meses_mantenimiento')->nullable()->after('mantenimiento_frecuencia_meses');
            });
        }

        if (!Schema::hasColumn('plan_polizas', 'meses_mantenimiento')) {
            Schema::table('plan_polizas', function (Blueprint $table) {
                $table->json('meses_mantenimiento')->nullable()->after('mantenimiento_frecuencia_meses');
            });
        }
    }

    public function down(): void
    {
        Schema::table('polizas_servicio', function (Blueprint $table) {
            $table->dropColumn('meses_mantenimiento');
        });

        Schema::table('plan_polizas', function (Blueprint $table) {
            $table->dropColumn('meses_mantenimiento');
        });
    }
};
