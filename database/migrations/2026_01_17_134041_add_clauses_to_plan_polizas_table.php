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
            $table->text('clausulas')->nullable()->after('beneficios')
                ->comment('Cláusulas legales y términos del servicio');
            $table->text('terminos_pago')->nullable()->after('clausulas')
                ->comment('Detalles sobre cómo y cuándo se realizan los pagos');
        });

        Schema::table('polizas_servicio', function (Blueprint $table) {
            $table->text('clausulas_especiales')->nullable()->after('condiciones_especiales')
                ->comment('Cláusulas personalizadas solo para este contrato');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_polizas', function (Blueprint $table) {
            $table->dropColumn(['clausulas', 'terminos_pago']);
        });

        Schema::table('polizas_servicio', function (Blueprint $table) {
            $table->dropColumn(['clausulas_especiales']);
        });
    }
};
