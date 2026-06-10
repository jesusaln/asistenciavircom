<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('polizas_servicio', function (Blueprint $table) {
            if (!Schema::hasColumn('polizas_servicio', 'sla_horas_resolucion')) {
                $table->integer('sla_horas_resolucion')->nullable()->after('sla_horas_respuesta');
            }
            if (!Schema::hasColumn('polizas_servicio', 'mantenimientos_anuales')) {
                $table->integer('mantenimientos_anuales')->default(0)->after('sla_horas_resolucion');
            }
        });

        Schema::table('plan_polizas', function (Blueprint $table) {
            if (!Schema::hasColumn('plan_polizas', 'sla_horas_resolucion')) {
                $table->integer('sla_horas_resolucion')->nullable()->after('sla_horas_respuesta');
            }
            if (!Schema::hasColumn('plan_polizas', 'mantenimientos_anuales')) {
                $table->integer('mantenimientos_anuales')->default(0)->after('sla_horas_resolucion');
            }
        });

        Schema::table('ticket_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('ticket_categories', 'servicio_id')) {
                $table->foreignId('servicio_id')->nullable()->constrained('servicios')->nullOnDelete()->after('consume_poliza');
            }
        });
    }

    public function down(): void
    {
        Schema::table('polizas_servicio', function (Blueprint $table) {
            $table->dropColumn(['sla_horas_resolucion', 'mantenimientos_anuales']);
        });

        Schema::table('plan_polizas', function (Blueprint $table) {
            $table->dropColumn(['sla_horas_resolucion', 'mantenimientos_anuales']);
        });

        Schema::table('ticket_categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('servicio_id');
        });
    }
};
