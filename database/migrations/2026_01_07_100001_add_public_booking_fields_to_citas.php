<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * Añade campos para el sistema de agendamiento público desde tiendas departamentales
     */
    public function up(): void
    {
        if (!Schema::hasTable('citas')) {
            return;
        }

        Schema::table('citas', function (Blueprint $table) {
            // Identificación de citas públicas
            if (!Schema::hasColumn('citas', 'es_publica')) {
                $table->boolean('es_publica')->default(false)->after('notas');
            }
            if (!Schema::hasColumn('citas', 'origen_tienda')) {
                $table->string('origen_tienda')->nullable()->after('es_publica')
                    ->comment('Liverpool, Coppel, Elektra, Sears, etc.');
            }
            if (!Schema::hasColumn('citas', 'numero_ticket_tienda')) {
                $table->string('numero_ticket_tienda')->nullable()->after('origen_tienda')
                    ->comment('Número de factura/ticket de la tienda');
            }

            // Preferencias del cliente
            if (!Schema::hasColumn('citas', 'horario_preferido')) {
                $table->string('horario_preferido')->nullable()->after('numero_ticket_tienda')
                    ->comment('mañana, mediodia, tarde, noche');
            }
            if (!Schema::hasColumn('citas', 'dias_preferidos')) {
                $table->json('dias_preferidos')->nullable()->after('horario_preferido')
                    ->comment('Array de fechas preferidas por el cliente');
            }

            // Confirmación por admin
            if (!Schema::hasColumn('citas', 'fecha_confirmada')) {
                $table->date('fecha_confirmada')->nullable()->after('dias_preferidos');
            }
            if (!Schema::hasColumn('citas', 'hora_confirmada')) {
                $table->time('hora_confirmada')->nullable()->after('fecha_confirmada');
            }

            // Dirección detallada (para citas públicas)
            if (!Schema::hasColumn('citas', 'direccion_calle')) {
                $table->string('direccion_calle')->nullable()->after('hora_confirmada');
            }
            if (!Schema::hasColumn('citas', 'direccion_colonia')) {
                $table->string('direccion_colonia')->nullable()->after('direccion_calle');
            }
            if (!Schema::hasColumn('citas', 'direccion_cp')) {
                $table->string('direccion_cp', 10)->nullable()->after('direccion_colonia');
            }
            if (!Schema::hasColumn('citas', 'direccion_referencias')) {
                $table->text('direccion_referencias')->nullable()->after('direccion_cp')
                    ->comment('Referencias para llegar: entre calles, color casa, etc.');
            }

            // Seguimiento y notificaciones
            if (!Schema::hasColumn('citas', 'link_seguimiento')) {
                $table->uuid('link_seguimiento')->nullable()->unique()->after('direccion_referencias')
                    ->comment('UUID para página pública de seguimiento');
            }
            if (!Schema::hasColumn('citas', 'whatsapp_recepcion_enviado')) {
                $table->boolean('whatsapp_recepcion_enviado')->default(false)->after('link_seguimiento');
            }
            if (!Schema::hasColumn('citas', 'whatsapp_confirmacion_enviado')) {
                $table->boolean('whatsapp_confirmacion_enviado')->default(false)->after('whatsapp_recepcion_enviado');
            }
            if (!Schema::hasColumn('citas', 'whatsapp_recepcion_at')) {
                $table->timestamp('whatsapp_recepcion_at')->nullable()->after('whatsapp_confirmacion_enviado');
            }
            if (!Schema::hasColumn('citas', 'whatsapp_confirmacion_at')) {
                $table->timestamp('whatsapp_confirmacion_at')->nullable()->after('whatsapp_recepcion_at');
            }

            // Índices
            if (!Schema::hasColumn('citas', 'es_publica')) {
                return;
            }
        });

        Schema::table('citas', function (Blueprint $table) {
            try {
                $table->index('es_publica');
            } catch (\Throwable $e) {
                // Indice ya existe.
            }
            try {
                $table->index('origen_tienda');
            } catch (\Throwable $e) {
                // Indice ya existe.
            }
            try {
                $table->index('link_seguimiento');
            } catch (\Throwable $e) {
                // Indice ya existe.
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('citas')) {
            return;
        }

        Schema::table('citas', function (Blueprint $table) {
            try {
                $table->dropIndex(['es_publica']);
            } catch (\Throwable $e) {
                // Indice no existe.
            }
            try {
                $table->dropIndex(['origen_tienda']);
            } catch (\Throwable $e) {
                // Indice no existe.
            }
            try {
                $table->dropIndex(['link_seguimiento']);
            } catch (\Throwable $e) {
                // Indice no existe.
            }

            $columns = array_filter([
                Schema::hasColumn('citas', 'es_publica') ? 'es_publica' : null,
                Schema::hasColumn('citas', 'origen_tienda') ? 'origen_tienda' : null,
                Schema::hasColumn('citas', 'numero_ticket_tienda') ? 'numero_ticket_tienda' : null,
                Schema::hasColumn('citas', 'horario_preferido') ? 'horario_preferido' : null,
                Schema::hasColumn('citas', 'dias_preferidos') ? 'dias_preferidos' : null,
                Schema::hasColumn('citas', 'fecha_confirmada') ? 'fecha_confirmada' : null,
                Schema::hasColumn('citas', 'hora_confirmada') ? 'hora_confirmada' : null,
                Schema::hasColumn('citas', 'direccion_calle') ? 'direccion_calle' : null,
                Schema::hasColumn('citas', 'direccion_colonia') ? 'direccion_colonia' : null,
                Schema::hasColumn('citas', 'direccion_cp') ? 'direccion_cp' : null,
                Schema::hasColumn('citas', 'direccion_referencias') ? 'direccion_referencias' : null,
                Schema::hasColumn('citas', 'link_seguimiento') ? 'link_seguimiento' : null,
                Schema::hasColumn('citas', 'whatsapp_recepcion_enviado') ? 'whatsapp_recepcion_enviado' : null,
                Schema::hasColumn('citas', 'whatsapp_confirmacion_enviado') ? 'whatsapp_confirmacion_enviado' : null,
                Schema::hasColumn('citas', 'whatsapp_recepcion_at') ? 'whatsapp_recepcion_at' : null,
                Schema::hasColumn('citas', 'whatsapp_confirmacion_at') ? 'whatsapp_confirmacion_at' : null,
            ]);

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
