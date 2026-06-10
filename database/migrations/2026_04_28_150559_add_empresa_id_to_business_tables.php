<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $tables = [
        'lotes',
        'inventario_fisico_items',
        'cita_historial',
        'cita_productos_utilizados',
        'cita_productos_vendidos',
        'cita_servicios',
        'empleados',
        'tecnicos',
        'plan_poliza_servicios',
        'poliza_audit_logs',
        'poliza_consumos',
        'poliza_mantenimientos',
        'poliza_mantenimiento_ejecuciones',
        'equipo_poliza_servicio',
        'price_list_products',
        'product_prices',
        'producto_precio_historial',
        'categoria_herramientas',
        'mantenimientos_herramientas',
        'cfdi_conceptos',
        'marketing_audiencia_clientes',
        'marketing_destinatarios',
        'proyecto_productos',
        'ticket_comments',
        'cliente_documentos',
        'solicitud_material_items',
        'pedidos_online_bitacora',
        'credenciales_accesos_logs',
        'trading_experience',
        'trading_weights',
        'trading_api_keys',
        'trading_orders_queue'
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'empresa_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('empresa_id')->nullable()->default(8);
                    
                    // Intentamos agregar la restricción de clave foránea
                    // Si falla por datos inconsistentes, al menos la columna se crea.
                    // Pero para ser seguros en esta fase, solo creamos la columna con índice.
                    $table->index('empresa_id');
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'empresa_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropIndex(['empresa_id']);
                    $table->dropColumn('empresa_id');
                });
            }
        }
    }
};
