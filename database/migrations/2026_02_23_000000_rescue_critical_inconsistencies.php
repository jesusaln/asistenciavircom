<?php

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Tablas que necesitan SoftDeletes (deleted_at)
        $tablesWithSoftDeletes = [
            'pedidos',
            'venta_items',
            'user_notifications',
            'equipos',
            'compra_items',
            'venta_item_series'
        ];

        foreach ($tablesWithSoftDeletes as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }

        // 2. Tablas que necesitan empresa_id para Multi-tenancy
        $tablesWithEmpresaId = [
            'folio_configs',
            'cita_items'
        ];

        foreach ($tablesWithEmpresaId as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'empresa_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('empresa_id')->nullable()->index();
                });
            }
        }

        // 3. Tablas que necesitan Blameable (created_by, updated_by, deleted_by)
        $tablesWithBlameable = [
            'compras'
        ];

        foreach ($tablesWithBlameable as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    if (!Schema::hasColumn($table->getTable(), 'created_by'))
                        $table->unsignedBigInteger('created_by')->nullable();
                    if (!Schema::hasColumn($table->getTable(), 'updated_by'))
                        $table->unsignedBigInteger('updated_by')->nullable();
                    if (!Schema::hasColumn($table->getTable(), 'deleted_by'))
                        $table->unsignedBigInteger('deleted_by')->nullable();
                });
            }
        }

        // 4. Ampliación de la tabla compras (Sincronizar con Modelo)
        if (Schema::hasTable('compras')) {
            Schema::table('compras', function (Blueprint $table) {
                if (!Schema::hasColumn('compras', 'deleted_at'))
                    $table->softDeletes();
                if (!Schema::hasColumn('compras', 'tipo'))
                    $table->string('tipo')->default('inventario');
                if (!Schema::hasColumn('compras', 'categoria_gasto_id'))
                    $table->unsignedBigInteger('categoria_gasto_id')->nullable();
                if (!Schema::hasColumn('compras', 'almacen_id'))
                    $table->unsignedBigInteger('almacen_id')->nullable();
                if (!Schema::hasColumn('compras', 'orden_compra_id'))
                    $table->unsignedBigInteger('orden_compra_id')->nullable();
                if (!Schema::hasColumn('compras', 'moneda'))
                    $table->string('moneda', 3)->default('MXN');
                if (!Schema::hasColumn('compras', 'tipo_cambio'))
                    $table->decimal('tipo_cambio', 12, 4)->default(1);
                if (!Schema::hasColumn('compras', 'subtotal'))
                    $table->decimal('subtotal', 15, 2)->default(0);
                if (!Schema::hasColumn('compras', 'descuento_general'))
                    $table->decimal('descuento_general', 15, 2)->default(0);
                if (!Schema::hasColumn('compras', 'descuento_items'))
                    $table->decimal('descuento_items', 15, 2)->default(0);
                if (!Schema::hasColumn('compras', 'iva'))
                    $table->decimal('iva', 15, 2)->default(0);
                if (!Schema::hasColumn('compras', 'retencion_iva'))
                    $table->decimal('retencion_iva', 15, 2)->default(0);
                if (!Schema::hasColumn('compras', 'retencion_isr'))
                    $table->decimal('retencion_isr', 15, 2)->default(0);
                if (!Schema::hasColumn('compras', 'isr'))
                    $table->decimal('isr', 15, 2)->default(0);
                if (!Schema::hasColumn('compras', 'aplicar_retencion_iva'))
                    $table->boolean('aplicar_retencion_iva')->default(false);
                if (!Schema::hasColumn('compras', 'aplicar_retencion_isr'))
                    $table->boolean('aplicar_retencion_isr')->default(false);
                if (!Schema::hasColumn('compras', 'notas'))
                    $table->text('notas')->nullable();
                if (!Schema::hasColumn('compras', 'inventario_procesado'))
                    $table->boolean('inventario_procesado')->default(false);
                if (!Schema::hasColumn('compras', 'metodo_pago'))
                    $table->string('metodo_pago')->nullable();
                if (!Schema::hasColumn('compras', 'cuenta_bancaria_id'))
                    $table->unsignedBigInteger('cuenta_bancaria_id')->nullable();
                if (!Schema::hasColumn('compras', 'proyecto_id'))
                    $table->unsignedBigInteger('proyecto_id')->nullable();

                // ✅ FIX: Sincronizar columna folio -> numero_compra
                if (!Schema::hasColumn('compras', 'numero_compra')) {
                    if (Schema::hasColumn('compras', 'folio')) {
                        $table->renameColumn('folio', 'numero_compra');
                    } else {
                        $table->string('numero_compra')->nullable();
                    }
                }

                // Campos CFDI
                if (!Schema::hasColumn('compras', 'cfdi_uuid'))
                    $table->string('cfdi_uuid')->nullable();
                if (!Schema::hasColumn('compras', 'cfdi_folio'))
                    $table->string('cfdi_folio')->nullable();
                if (!Schema::hasColumn('compras', 'cfdi_serie'))
                    $table->string('cfdi_serie')->nullable();
                if (!Schema::hasColumn('compras', 'cfdi_emisor_rfc'))
                    $table->string('cfdi_emisor_rfc')->nullable();
                if (!Schema::hasColumn('compras', 'cfdi_emisor_nombre'))
                    $table->string('cfdi_emisor_nombre')->nullable();
            });
        }
    }

    public function down(): void
    {
        // No destructivo en este caso
    }
};
