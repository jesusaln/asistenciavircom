<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Rescue almacenes
        Schema::table('almacenes', function (Blueprint $table) {
            if (!Schema::hasColumn('almacenes', 'descripcion')) {
                $table->text('descripcion')->nullable();
            }
            if (!Schema::hasColumn('almacenes', 'ubicacion')) {
                $table->string('ubicacion')->nullable();
            }
            if (!Schema::hasColumn('almacenes', 'direccion')) {
                $table->string('direccion')->nullable();
            }
            if (!Schema::hasColumn('almacenes', 'telefono')) {
                $table->string('telefono')->nullable();
            }
            if (!Schema::hasColumn('almacenes', 'responsable')) {
                $table->string('responsable')->nullable();
            }
            if (!Schema::hasColumn('almacenes', 'estado')) {
                $table->string('estado')->nullable();
            }
        });

        // 2. Rescue productos
        Schema::table('productos', function (Blueprint $table) {
            if (!Schema::hasColumn('productos', 'precio_venta')) {
                $table->decimal('precio_venta', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('productos', 'requiere_serie')) {
                $table->boolean('requiere_serie')->default(false);
            }
        });

        // Asegurarnos de que stock y stock_cedis en productos soporten decimales
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE productos ALTER COLUMN stock TYPE numeric(15,2) USING stock::numeric');
        if (Schema::hasColumn('productos', 'stock_cedis')) {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE productos ALTER COLUMN stock_cedis TYPE numeric(15,2) USING stock_cedis::numeric');
        }

        // 3. Rescue ventas
        Schema::table('ventas', function (Blueprint $table) {
            if (!Schema::hasColumn('ventas', 'descuento_general')) {
                $table->decimal('descuento_general', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('ventas', 'iva')) {
                $table->decimal('iva', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('ventas', 'isr')) {
                $table->decimal('isr', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('ventas', 'retencion_iva')) {
                $table->decimal('retencion_iva', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('ventas', 'retencion_isr')) {
                $table->decimal('retencion_isr', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('ventas', 'forma_pago_sat')) {
                $table->string('forma_pago_sat', 10)->nullable();
            }
            if (!Schema::hasColumn('ventas', 'metodo_pago_sat')) {
                $table->string('metodo_pago_sat', 10)->nullable();
            }
            if (!Schema::hasColumn('ventas', 'cotizacion_id')) {
                $table->unsignedBigInteger('cotizacion_id')->nullable();
            }
            if (!Schema::hasColumn('ventas', 'pedido_id')) {
                $table->unsignedBigInteger('pedido_id')->nullable();
            }
        });

        Schema::table('cuentas_por_cobrar', function (Blueprint $table) {
            if (!Schema::hasColumn('cuentas_por_cobrar', 'monto_total')) {
                $table->decimal('monto_total', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('cuentas_por_cobrar', 'monto_pagado')) {
                $table->decimal('monto_pagado', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('cuentas_por_cobrar', 'monto_pendiente')) {
                $table->decimal('monto_pendiente', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('cuentas_por_cobrar', 'cfdi_id')) {
                $table->unsignedBigInteger('cfdi_id')->nullable();
            }
            if (!Schema::hasColumn('cuentas_por_cobrar', 'venta_id')) {
                $table->unsignedBigInteger('venta_id')->nullable();
            }
            if (!Schema::hasColumn('cuentas_por_cobrar', 'pagado_por')) {
                $table->string('pagado_por')->nullable();
            }
            if (!Schema::hasColumn('cuentas_por_cobrar', 'notas_pago')) {
                $table->text('notas_pago')->nullable();
            }
        });

        // 4. Rescue venta_items
        Schema::table('venta_items', function (Blueprint $table) {
            if (!Schema::hasColumn('venta_items', 'descuento')) {
                $table->decimal('descuento', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('venta_items', 'descuento_monto')) {
                $table->decimal('descuento_monto', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('venta_items', 'subtotal')) {
                $table->decimal('subtotal', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('venta_items', 'iva')) {
                $table->decimal('iva', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('venta_items', 'price_list_id')) {
                $table->unsignedBigInteger('price_list_id')->nullable();
            }
        });

        Schema::table('inventario_movimientos', function (Blueprint $table) {
            if (!Schema::hasColumn('inventario_movimientos', 'producto_nombre')) {
                $table->string('producto_nombre', 255)->nullable();
            }
            if (!Schema::hasColumn('inventario_movimientos', 'almacen_nombre')) {
                $table->string('almacen_nombre', 255)->nullable();
            }
            if (!Schema::hasColumn('inventario_movimientos', 'usuario_nombre')) {
                $table->string('usuario_nombre', 255)->nullable();
            }
            if (!Schema::hasColumn('inventario_movimientos', 'stock_anterior')) {
                $table->decimal('stock_anterior', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('inventario_movimientos', 'stock_posterior')) {
                $table->decimal('stock_posterior', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('inventario_movimientos', 'detalles')) {
                $table->json('detalles')->nullable();
            }
            if (!Schema::hasColumn('inventario_movimientos', 'lote_id')) {
                $table->unsignedBigInteger('lote_id')->nullable();
            }
            if (!Schema::hasColumn('inventario_movimientos', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable();
            }
            if (!Schema::hasColumn('inventario_movimientos', 'referencia_type')) {
                $table->string('referencia_type')->nullable();
            }
            if (!Schema::hasColumn('inventario_movimientos', 'referencia_id')) {
                $table->unsignedBigInteger('referencia_id')->nullable();
            }
            if (!Schema::hasColumn('inventario_movimientos', 'motivo')) {
                $table->string('motivo')->nullable();
            }
            if (!Schema::hasColumn('inventario_movimientos', 'empresa_id')) {
                $table->unsignedBigInteger('empresa_id')->nullable();
            }
        });

        // 5. Rescue inventarios
        Schema::table('inventarios', function (Blueprint $table) {
            if (!Schema::hasColumn('inventarios', 'cantidad')) {
                $table->decimal('cantidad', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('inventarios', 'stock_minimo')) {
                $table->decimal('stock_minimo', 12, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        // Don't drop columns in rescue
    }
};
