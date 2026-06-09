<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Tabla pivote para definir qué servicios están incluidos/elegibles en cada plan de póliza.
     * Esto permite configurar, por ejemplo, que el Plan Mini solo cubra:
     * - Soporte CONTPAQi
     * - Soporte Equipos de Cómputo
     * - Servicio a Domicilio
     * 
     * Mientras que otros servicios como "Instalación de Cámaras" o "Asesorías" 
     * NO estarían cubiertos y se cobrarían extra.
     */
    public function up(): void
    {
        Schema::create('plan_poliza_servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_poliza_id')->constrained('plan_polizas')->onDelete('cascade');
            $table->foreignId('servicio_id')->constrained('servicios')->onDelete('cascade');

            // Prioridad para ordenar en listas
            $table->integer('orden')->default(0);

            // Notas adicionales (ej: "Máximo 2 por mes")
            $table->string('notas')->nullable();

            $table->timestamps();

            // Un servicio solo puede estar una vez por plan
            $table->unique(['plan_poliza_id', 'servicio_id']);
        });

        // Asegurarnos de que plan_polizas tenga el campo horas_incluidas
        if (!Schema::hasColumn('plan_polizas', 'horas_incluidas')) {
            Schema::table('plan_polizas', function (Blueprint $table) {
                $table->decimal('horas_incluidas', 8, 2)->nullable()->after('precio_anual')
                    ->comment('Banco de horas mensuales incluidas en el plan');
            });
        }

        // Campo para alerta de horas próximas a agotarse
        if (!Schema::hasColumn('polizas_servicio', 'alerta_horas_20_enviada')) {
            Schema::table('polizas_servicio', function (Blueprint $table) {
                $table->boolean('alerta_horas_20_enviada')->default(false)->after('alerta_vencimiento_enviada');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_poliza_servicios');

        if (Schema::hasColumn('plan_polizas', 'horas_incluidas')) {
            Schema::table('plan_polizas', function (Blueprint $table) {
                $table->dropColumn('horas_incluidas');
            });
        }

        if (Schema::hasColumn('polizas_servicio', 'alerta_horas_20_enviada')) {
            Schema::table('polizas_servicio', function (Blueprint $table) {
                $table->dropColumn('alerta_horas_20_enviada');
            });
        }
    }
};
