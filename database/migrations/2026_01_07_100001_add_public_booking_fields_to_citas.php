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
        Schema::table('citas', function (Blueprint $table) {
            // Identificación de citas públicas
            $table->boolean('es_publica')->default(false)->after('notas');
            $table->string('origen_tienda')->nullable()->after('es_publica')
                ->comment('Liverpool, Coppel, Elektra, Sears, etc.');
            $table->string('numero_ticket_tienda')->nullable()->after('origen_tienda')
                ->comment('Número de factura/ticket de la tienda');

            // Preferencias del cliente
            $table->string('horario_preferido')->nullable()->after('numero_ticket_tienda')
                ->comment('mañana, mediodia, tarde, noche');
            $table->json('dias_preferidos')->nullable()->after('horario_preferido')
                ->comment('Array de fechas preferidas por el cliente');

            // Confirmación por admin
            $table->date('fecha_confirmada')->nullable()->after('dias_preferidos');
            $table->time('hora_confirmada')->nullable()->after('fecha_confirmada');

            // Dirección detallada (para citas públicas)
            $table->string('direccion_calle')->nullable()->after('hora_confirmada');
            $table->string('direccion_colonia')->nullable()->after('direccion_calle');
            $table->string('direccion_cp', 10)->nullable()->after('direccion_colonia');
            $table->text('direccion_referencias')->nullable()->after('direccion_cp')
                ->comment('Referencias para llegar: entre calles, color casa, etc.');

            // Seguimiento y notificaciones
            $table->uuid('link_seguimiento')->nullable()->unique()->after('direccion_referencias')
                ->comment('UUID para página pública de seguimiento');
            $table->boolean('whatsapp_recepcion_enviado')->default(false)->after('link_seguimiento');
            $table->boolean('whatsapp_confirmacion_enviado')->default(false)->after('whatsapp_recepcion_enviado');
            $table->timestamp('whatsapp_recepcion_at')->nullable()->after('whatsapp_confirmacion_enviado');
            $table->timestamp('whatsapp_confirmacion_at')->nullable()->after('whatsapp_recepcion_at');

            // Índices
            $table->index('es_publica');
            $table->index('origen_tienda');
            $table->index('link_seguimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropIndex(['es_publica']);
            $table->dropIndex(['origen_tienda']);
            $table->dropIndex(['link_seguimiento']);

            $table->dropColumn([
                'es_publica',
                'origen_tienda',
                'numero_ticket_tienda',
                'horario_preferido',
                'dias_preferidos',
                'fecha_confirmada',
                'hora_confirmada',
                'direccion_calle',
                'direccion_colonia',
                'direccion_cp',
                'direccion_referencias',
                'link_seguimiento',
                'whatsapp_recepcion_enviado',
                'whatsapp_confirmacion_enviado',
                'whatsapp_recepcion_at',
                'whatsapp_confirmacion_at',
            ]);
        });
    }
};
