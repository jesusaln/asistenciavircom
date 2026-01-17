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
            // Si no existe limite_mensual_tickets (usualmente ya existe), pero por si acaso
            if (!Schema::hasColumn('polizas_servicio', 'limite_mensual_tickets')) {
                $table->integer('limite_mensual_tickets')->nullable()->after('estado');
            }

            // Contador persistente de soporte técnico (Sistemas)
            if (!Schema::hasColumn('polizas_servicio', 'tickets_soporte_consumidos_mes')) {
                $table->integer('tickets_soporte_consumidos_mes')->default(0)->after('limite_mensual_tickets');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('polizas_servicio', function (Blueprint $table) {
            $table->dropColumn(['tickets_soporte_consumidos_mes']);
        });
    }
};
