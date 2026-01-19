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
        // 3.2: Costos de excedente configurables
        Schema::table('plan_polizas', function (Blueprint $table) {
            $table->decimal('costo_ticket_extra', 10, 2)->default(150)->after('costo_hora_extra');
        });

        Schema::table('polizas_servicio', function (Blueprint $table) {
            // Ya tiene costo_hora_excedente y costo_visita_sitio_extra (Fase 1/2)
            // Agregamos costo_ticket_extra para consistencia
            if (!Schema::hasColumn('polizas_servicio', 'costo_ticket_extra')) {
                $table->decimal('costo_ticket_extra', 10, 2)->default(150)->after('costo_visita_sitio_extra');
            }
        });

        // 3.3: Índices de Base de Datos para rendimiento
        Schema::table('tickets', function (Blueprint $table) {
            // Índice compuesto para búsquedas por póliza y fecha (reset mensual)
            $table->index(['poliza_id', 'created_at'], 'tickets_poliza_date_idx');
        });

        Schema::table('poliza_consumos', function (Blueprint $table) {
            $table->index(['poliza_id', 'fecha_consumo'], 'consumos_poliza_date_idx');
        });

        Schema::table('polizas_servicio', function (Blueprint $table) {
            $table->index(['cliente_id', 'estado'], 'polizas_cliente_estado_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_polizas', function (Blueprint $table) {
            $table->dropColumn('costo_ticket_extra');
        });

        Schema::table('polizas_servicio', function (Blueprint $table) {
            $table->dropColumn('costo_ticket_extra');
            $table->dropIndex('polizas_cliente_estado_idx');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_poliza_date_idx');
        });

        Schema::table('poliza_consumos', function (Blueprint $table) {
            $table->dropIndex('consumos_poliza_date_idx');
        });
    }
};
