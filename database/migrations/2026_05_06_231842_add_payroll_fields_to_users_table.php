<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $blueprint) {
            if (!Schema::hasColumn('users', 'tipo_regimen')) {
                $blueprint->string('tipo_regimen', 5)->nullable()->after('frecuencia_pago');
            }
            if (!Schema::hasColumn('users', 'riesgo_puesto')) {
                $blueprint->string('riesgo_puesto', 5)->nullable()->after('tipo_regimen');
            }
            if (!Schema::hasColumn('users', 'salario_diario_integrado')) {
                $blueprint->decimal('salario_diario_integrado', 12, 4)->nullable()->after('salario_base');
            }
            if (!Schema::hasColumn('users', 'salario_base_cotizacion')) {
                $blueprint->decimal('salario_base_cotizacion', 12, 4)->nullable()->after('salario_diario_integrado');
            }
            if (!Schema::hasColumn('users', 'clave_ent_fed')) {
                $blueprint->string('clave_ent_fed', 5)->nullable()->after('riesgo_puesto');
            }
            if (!Schema::hasColumn('users', 'registro_patronal')) {
                $blueprint->string('registro_patronal', 20)->nullable()->after('clave_ent_fed');
            }
            if (!Schema::hasColumn('users', 'sindicalizado')) {
                $blueprint->boolean('sindicalizado')->default(false)->after('registro_patronal');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $blueprint) {
            $blueprint->dropColumn([
                'tipo_regimen',
                'riesgo_puesto',
                'salario_diario_integrado',
                'salario_base_cotizacion',
                'clave_ent_fed',
                'registro_patronal',
                'sindicalizado'
            ]);
        });
    }
};
