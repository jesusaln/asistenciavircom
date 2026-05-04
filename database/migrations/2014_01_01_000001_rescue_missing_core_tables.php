<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * This is a rescue migration to ensure core tables that are missing their 
     * creation migrations (or have them missing from the repo) are created.
     */
    public function up(): void
    {
        // 1. compra_items
        if (!Schema::hasTable('compra_items')) {
            Schema::create('compra_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('compra_id')->index();
                $table->string('comprable_type')->nullable();
                $table->unsignedBigInteger('comprable_id')->nullable();
                $table->decimal('cantidad', 15, 2)->default(1);
                $table->decimal('precio', 15, 2)->default(0);
                $table->decimal('descuento', 15, 2)->default(0);
                $table->decimal('descuento_monto', 15, 2)->default(0);
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 2. orden_compras
        if (!Schema::hasTable('orden_compras')) {
            Schema::create('orden_compras', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->unsignedBigInteger('proveedor_id')->nullable();
                $table->unsignedBigInteger('pedido_id')->nullable();
                $table->unsignedBigInteger('almacen_id')->nullable();
                $table->string('numero_orden', 50)->nullable();
                $table->date('fecha_orden')->nullable();
                $table->date('fecha_entrega_esperada')->nullable();
                $table->string('prioridad', 20)->default('media');
                $table->text('direccion_entrega')->nullable();
                $table->string('terminos_pago')->nullable();
                $table->string('metodo_pago')->nullable();
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('descuento_items', 15, 2)->default(0);
                $table->decimal('descuento_general', 15, 2)->default(0);
                $table->decimal('iva', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                $table->string('estado')->default('pendiente');
                $table->text('observaciones')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 3. orden_compra_producto
        if (!Schema::hasTable('orden_compra_producto')) {
            Schema::create('orden_compra_producto', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('orden_compra_id')->index();
                $table->unsignedBigInteger('producto_id')->index();
                $table->decimal('cantidad', 15, 2)->default(1);
                $table->decimal('precio', 15, 2)->default(0);
                $table->decimal('descuento', 15, 2)->default(0);
                $table->string('unidad_medida', 20)->nullable();
                $table->timestamps();
            });
        }

        // 4. categoria_gastos
        if (!Schema::hasTable('categoria_gastos')) {
            Schema::create('categoria_gastos', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->string('descripcion')->nullable();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        // 5. cotizacion_items
        if (!Schema::hasTable('cotizacion_items')) {
            Schema::create('cotizacion_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('cotizacion_id')->index();
                $table->string('cotizable_type')->nullable();
                $table->unsignedBigInteger('cotizable_id')->nullable();
                $table->decimal('cantidad', 15, 2)->default(1);
                $table->decimal('precio', 15, 2)->default(0);
                $table->decimal('descuento', 15, 2)->default(0);
                $table->decimal('descuento_monto', 15, 2)->default(0);
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->timestamps();
            });
        }

        // 6. pedido_items
        if (!Schema::hasTable('pedido_items')) {
            Schema::create('pedido_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pedido_id')->index();
                $table->string('pedible_type')->nullable();
                $table->unsignedBigInteger('pedible_id')->nullable();
                $table->decimal('cantidad', 15, 2)->default(1);
                $table->decimal('precio', 15, 2)->default(0);
                $table->decimal('descuento', 15, 2)->default(0);
                $table->decimal('descuento_monto', 15, 2)->default(0);
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->timestamps();
            });
        }

        // 7. marcas
        if (!Schema::hasTable('marcas')) {
            Schema::create('marcas', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        // 8. venta_item_series
        if (!Schema::hasTable('venta_item_series')) {
            Schema::create('venta_item_series', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('venta_item_id')->index();
                $table->unsignedBigInteger('producto_serie_id')->index();
                $table->string('numero_serie')->nullable();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 9. compra_producto (legacy pivot)
        if (!Schema::hasTable('compra_producto')) {
            Schema::create('compra_producto', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('compra_id')->index();
                $table->unsignedBigInteger('producto_id')->index();
                $table->decimal('cantidad', 15, 2)->default(1);
                $table->decimal('precio', 15, 2)->default(0);
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->timestamps();
            });
        }

        // 10. cotizaciones
        if (!Schema::hasTable('cotizaciones')) {
            Schema::create('cotizaciones', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->unsignedBigInteger('cliente_id')->nullable()->index();
                $table->unsignedBigInteger('almacen_id')->nullable();
                $table->string('numero_cotizacion', 50)->nullable();
                $table->date('fecha_cotizacion')->nullable();
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('descuento_general', 15, 2)->default(0);
                $table->decimal('descuento_items', 15, 2)->default(0);
                $table->decimal('iva', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                $table->text('notas')->nullable();
                $table->string('estado')->default('pendiente');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 11. pedidos
        if (!Schema::hasTable('pedidos')) {
            Schema::create('pedidos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->unsignedBigInteger('cliente_id')->nullable()->index();
                $table->unsignedBigInteger('cotizacion_id')->nullable();
                $table->string('numero_pedido', 50)->nullable();
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('descuento_general', 15, 2)->default(0);
                $table->decimal('iva', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                $table->text('notas')->nullable();
                $table->string('estado')->default('pendiente');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 12. rentas
        if (!Schema::hasTable('rentas')) {
            Schema::create('rentas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->unsignedBigInteger('cliente_id')->nullable()->index();
                $table->string('numero_contrato', 50)->nullable();
                $table->date('fecha_inicio')->nullable();
                $table->date('fecha_fin')->nullable();
                $table->decimal('monto_mensual', 15, 2)->default(0);
                $table->string('estado')->default('pendiente');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 13. equipo_renta
        if (!Schema::hasTable('equipo_renta')) {
            Schema::create('equipo_renta', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('renta_id')->index();
                $table->unsignedBigInteger('equipo_id')->index();
                $table->decimal('precio_mensual', 15, 2)->default(0);
                $table->timestamps();
            });
        }

        // 14. inventarios
        if (!Schema::hasTable('inventarios')) {
            Schema::create('inventarios', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->unsignedBigInteger('producto_id')->index();
                $table->unsignedBigInteger('almacen_id')->index();
                $table->decimal('stock', 15, 2)->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 15. inventario_movimientos
        if (!Schema::hasTable('inventario_movimientos')) {
            Schema::create('inventario_movimientos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->unsignedBigInteger('producto_id')->index();
                $table->unsignedBigInteger('almacen_id')->index();
                $table->string('tipo', 20); // entrada, salida
                $table->decimal('cantidad', 15, 2);
                $table->string('motivo')->nullable();
                $table->string('referencia_type')->nullable();
                $table->unsignedBigInteger('referencia_id')->nullable();
                $table->timestamps();
            });
        }

        // 16. categoria_herramientas
        if (!Schema::hasTable('categoria_herramientas')) {
            Schema::create('categoria_herramientas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->string('nombre');
                $table->string('descripcion')->nullable();
                $table->string('color', 20)->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        // 17. herramientas
        if (!Schema::hasTable('herramientas')) {
            Schema::create('herramientas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->unsignedBigInteger('categoria_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index(); // tecnico asignado
                $table->string('nombre');
                $table->string('codigo_inventario')->nullable()->unique();
                $table->string('numero_serie')->nullable();
                $table->string('foto')->nullable();
                $table->string('estado')->default('disponible');
                $table->integer('vida_util_meses')->nullable();
                $table->date('fecha_ultimo_mantenimiento')->nullable();
                $table->decimal('costo_reemplazo', 15, 2)->default(0);
                $table->string('categoria')->nullable(); // Legacy category string
                $table->text('descripcion')->nullable();
                $table->boolean('requiere_mantenimiento')->default(false);
                $table->integer('dias_para_mantenimiento')->nullable();
                $table->datetime('fecha_asignacion')->nullable();
                $table->datetime('fecha_recepcion')->nullable();
                $table->unsignedBigInteger('usuario_entrega_id')->nullable();
                $table->unsignedBigInteger('usuario_recepcion_id')->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        // 18. facturas
        if (!Schema::hasTable('facturas')) {
            Schema::create('facturas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->index();
                $table->unsignedBigInteger('cliente_id')->index();
                $table->string('numero_factura')->unique();
                $table->string('folio')->nullable();
                $table->date('fecha_emision');
                $table->date('fecha_vencimiento')->nullable();
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('descuento_general', 12, 2)->default(0);
                $table->decimal('impuestos', 12, 2)->default(0);
                $table->decimal('iva', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->string('estado')->default('borrador');
                $table->string('metodo_pago')->nullable();
                $table->string('forma_pago')->nullable();
                $table->string('uso_cfdi')->nullable();
                $table->string('moneda')->default('MXN');
                $table->decimal('tasa_cambio', 10, 4)->default(1);
                $table->text('observaciones')->nullable();
                $table->json('direccion_facturacion')->nullable();
                $table->json('datos_fiscales')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 17. cfdis
        if (!Schema::hasTable('cfdis')) {
            Schema::create('cfdis', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->index();
                $table->unsignedBigInteger('cliente_id')->index();
                $table->nullableMorphs('cfdiable');
                $table->unsignedBigInteger('venta_id')->nullable();
                $table->unsignedBigInteger('factura_id')->nullable();
                $table->string('uuid')->nullable()->index();
                $table->string('serie')->nullable();
                $table->string('folio')->nullable();
                $table->string('estatus')->default('borrador');
                $table->string('rfc_emisor')->nullable();
                $table->string('rfc_receptor')->nullable();
                $table->dateTime('fecha_emision')->nullable();
                $table->dateTime('fecha_timbrado')->nullable();
                $table->text('xml_url')->nullable();
                $table->text('pdf_url')->nullable();
                $table->string('tipo_comprobante')->default('I');
                $table->decimal('total', 12, 2)->default(0);
                $table->text('cadena_original')->nullable();
                $table->string('no_certificado_sat')->nullable();
                $table->string('no_certificado_cfdi')->nullable();
                $table->text('sello_sat')->nullable();
                $table->text('sello_cfdi')->nullable();
                $table->string('uso_cfdi')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 18. Maintenance check for existing tables (runs after above creations if tables already existed)
        if (Schema::hasTable('facturas') && !Schema::hasColumn('facturas', 'folio')) {
            Schema::table('facturas', function (Blueprint $table) {
                $table->string('folio')->nullable()->after('numero_factura');
            });
        }

        if (Schema::hasTable('cfdis')) {
            Schema::table('cfdis', function (Blueprint $table) {
                if (!Schema::hasColumn('cfdis', 'rfc_emisor')) {
                    $table->string('rfc_emisor')->nullable();
                }
                if (!Schema::hasColumn('cfdis', 'rfc_receptor')) {
                    $table->string('rfc_receptor')->nullable();
                }
                if (!Schema::hasColumn('cfdis', 'fecha_emision')) {
                    $table->dateTime('fecha_emision')->nullable();
                }
                if (!Schema::hasColumn('cfdis', 'folio')) {
                    $table->string('folio')->nullable();
                }
                if (!Schema::hasColumn('cfdis', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        // 18. productos
        if (Schema::hasTable('productos')) {
            Schema::table('productos', function (Blueprint $table) {
                if (!Schema::hasColumn('productos', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        } else {
            Schema::create('productos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->string('nombre');
                $table->text('descripcion')->nullable();
                $table->string('codigo')->nullable()->unique();
                $table->string('codigo_barras')->nullable();
                $table->unsignedBigInteger('categoria_id')->nullable()->index();
                $table->unsignedBigInteger('marca_id')->nullable()->index();
                $table->unsignedBigInteger('proveedor_id')->nullable()->index();
                $table->decimal('precio_compra', 15, 2)->default(0);
                $table->decimal('precio_venta', 15, 2)->default(0);
                $table->string('estado')->default('activo');
                $table->boolean('destacado')->default(false);
                $table->string('tipo_producto')->default('simple');
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No drops for safety in rescue migrations
    }
};
