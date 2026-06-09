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
        // 1. Campos financieros para Pólizas (Seguimiento de Revenue e IFRS15)
        Schema::table('polizas_servicio', function (Blueprint $table) {
            $table->decimal('ingreso_devengado', 15, 2)->default(0)->after('monto_mensual');
            $table->decimal('ingreso_diferido', 15, 2)->default(0)->after('ingreso_devengado');
            $table->decimal('costo_acumulado_tecnico', 15, 2)->default(0)->after('ingreso_diferido');
            $table->date('ultima_emision_fac_at')->nullable()->after('proximo_cobro_at');
        });

        // 2. Costo interno para Usuarios (Técnicos)
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('costo_hora_interno', 10, 2)->default(0)->after('salario_base');
        });

        // 3. Registro de costo en cada consumo individual
        Schema::table('poliza_consumos', function (Blueprint $table) {
            $table->decimal('costo_interno', 12, 2)->default(0)->after('valor_unitario');
            $table->unsignedBigInteger('tecnico_id')->nullable()->after('registrado_por');

            $table->foreign('tecnico_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('poliza_consumos', function (Blueprint $table) {
            $table->dropForeign(['tecnico_id']);
            $table->dropColumn(['costo_interno', 'tecnico_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('costo_hora_interno');
        });

        Schema::table('polizas_servicio', function (Blueprint $table) {
            $table->dropColumn(['ingreso_devengado', 'ingreso_diferido', 'costo_acumulado_tecnico', 'ultima_emision_fac_at']);
        });
    }
};
