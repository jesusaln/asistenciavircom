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
        $tables = [
            'venta_items',
            'venta_item_series',
            'orden_compras',
            'orden_compra_producto',
            'compra_items',
            'compra_producto',
            'cotizacion_items',
            'pedido_items',
            'categoria_gastos',
            'marcas',
            'categorias',
            'carros',
            'herramientas',
            'cuentas_por_cobrar',
            'cuentas_por_pagar',
            'prestamos',
            'pagos_prestamos',
            'crm_prospectos',
            'crm_tareas',
            'crm_actividades',
            'crm_scripts',
            'crm_metas',
            'crm_campanias',
            'proyectos',
            'proyecto_tareas',
            'proyecto_user',
            'rentas',
            'renta_equipos',
            'equipo_renta',
            'inventarios',
            'inventario_movimientos',
            'ajustes_inventario',
            'traspaso_items',
            'traspaso_bancarios',
            'movimientos_bancarios',
            'movimientos_manuales',
            'caja_chica',
            'caja_chica_adjuntos',
            'entregas_dinero',
            'cobranzas',
            'recordatorios_cobranza',
            'alertas_stock',
            'poliza_servicio_equipos',
            'poliza_servicio_items',
            'historial_herramientas',
            'responsabilidades_herramientas',
            'estados_herramientas',
            'asignaciones_masivas',
            'detalle_asignaciones_masivas',
            'vacaciones',
            'vacacions',
            'ajustes_vacaciones',
            'registro_vacaciones',
            'nomina_conceptos',
            'nominas',
            'catalogo_conceptos_nomina',
            'historial_pagos_prestamos',
            'reportes',
            'backup_logs',
            'venta_audit_logs'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'empresa_id')) {
                        $table->unsignedBigInteger('empresa_id')->nullable()->index();
                    }
                });

                // Asignar empresa_id = 8 (ID de la única empresa actual) a todos los registros
                \Illuminate\Support\Facades\DB::table($tableName)->whereNull('empresa_id')->update(['empresa_id' => 8]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down needed as this is a corrective migration
    }
};
