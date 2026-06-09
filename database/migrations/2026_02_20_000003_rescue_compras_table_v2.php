<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            // Folio/Numero columns
            if (!Schema::hasColumn('compras', 'numero_compra')) {
                $table->string('numero_compra')->nullable()->after('id');
            }

            // Re-rescue basic relations
            if (!Schema::hasColumn('compras', 'almacen_id')) {
                $table->unsignedBigInteger('almacen_id')->nullable()->after('proveedor_id');
            }
            if (!Schema::hasColumn('compras', 'orden_compra_id')) {
                $table->unsignedBigInteger('orden_compra_id')->nullable()->after('almacen_id');
            }
            if (!Schema::hasColumn('compras', 'categoria_gasto_id')) {
                $table->unsignedBigInteger('categoria_gasto_id')->nullable()->after('orden_compra_id');
            }

            // Financial columns
            if (!Schema::hasColumn('compras', 'subtotal')) {
                $table->decimal('subtotal', 15, 2)->default(0)->after('numero_compra');
            }
            if (!Schema::hasColumn('compras', 'descuento_general')) {
                $table->decimal('descuento_general', 15, 2)->default(0)->after('subtotal');
            }
            if (!Schema::hasColumn('compras', 'descuento_items')) {
                $table->decimal('descuento_items', 15, 2)->default(0)->after('descuento_general');
            }
            if (!Schema::hasColumn('compras', 'iva')) {
                $table->decimal('iva', 15, 2)->default(0)->after('descuento_items');
            }
            if (!Schema::hasColumn('compras', 'retencion_iva')) {
                $table->decimal('retencion_iva', 15, 2)->default(0)->after('iva');
            }
            if (!Schema::hasColumn('compras', 'retencion_isr')) {
                $table->decimal('retencion_isr', 15, 2)->default(0)->after('retencion_iva');
            }
            if (!Schema::hasColumn('compras', 'isr')) {
                $table->decimal('isr', 15, 2)->default(0)->after('retencion_isr');
            }

            // Flags
            if (!Schema::hasColumn('compras', 'aplicar_retencion_iva')) {
                $table->boolean('aplicar_retencion_iva')->default(false)->after('isr');
            }
            if (!Schema::hasColumn('compras', 'aplicar_retencion_isr')) {
                $table->boolean('aplicar_retencion_isr')->default(false)->after('aplicar_retencion_iva');
            }

            // Payment columns
            if (!Schema::hasColumn('compras', 'metodo_pago')) {
                $table->string('metodo_pago')->nullable()->after('estado');
            }
            if (!Schema::hasColumn('compras', 'cuenta_bancaria_id')) {
                $table->unsignedBigInteger('cuenta_bancaria_id')->nullable()->after('metodo_pago');
            }

            // Extra info
            if (!Schema::hasColumn('compras', 'tipo')) {
                $table->string('tipo')->default('inventario')->after('empresa_id');
            }
            if (!Schema::hasColumn('compras', 'fecha_compra')) {
                $table->dateTime('fecha_compra')->nullable()->after('tipo');
            }
            if (!Schema::hasColumn('compras', 'moneda')) {
                $table->string('moneda', 5)->default('MXN')->after('fecha_compra');
            }
            if (!Schema::hasColumn('compras', 'tipo_cambio')) {
                $table->decimal('tipo_cambio', 12, 4)->default(1)->after('moneda');
            }

            // Blameable columns
            if (!Schema::hasColumn('compras', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('updated_at');
            }
            if (!Schema::hasColumn('compras', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
            if (!Schema::hasColumn('compras', 'deleted_by')) {
                $table->unsignedBigInteger('deleted_by')->nullable()->after('updated_by');
            }

            // SAT/CFDI extra columns
            if (!Schema::hasColumn('compras', 'cfdi_tipo_comprobante')) {
                $table->string('cfdi_tipo_comprobante', 5)->nullable()->after('cfdi_serie');
            }
            if (!Schema::hasColumn('compras', 'cfdi_forma_pago')) {
                $table->string('cfdi_forma_pago', 10)->nullable()->after('cfdi_tipo_comprobante');
            }
            if (!Schema::hasColumn('compras', 'cfdi_metodo_pago')) {
                $table->string('cfdi_metodo_pago', 10)->nullable()->after('cfdi_forma_pago');
            }
            if (!Schema::hasColumn('compras', 'cfdi_uso')) {
                $table->string('cfdi_uso', 10)->nullable()->after('cfdi_metodo_pago');
            }
        });
    }

    public function down(): void
    {
    }
};
