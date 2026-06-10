<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add missing columns to cotizaciones
        Schema::table('cotizaciones', function (Blueprint $table) {
            if (!Schema::hasColumn('cotizaciones', 'retencion_iva')) {
                $table->decimal('retencion_iva', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('cotizaciones', 'retencion_isr')) {
                $table->decimal('retencion_isr', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('cotizaciones', 'isr')) {
                $table->decimal('isr', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('cotizaciones', 'email_enviado')) {
                $table->boolean('email_enviado')->default(false);
            }
            if (!Schema::hasColumn('cotizaciones', 'email_enviado_fecha')) {
                $table->timestamp('email_enviado_fecha')->nullable();
            }
            if (!Schema::hasColumn('cotizaciones', 'email_enviado_por')) {
                $table->unsignedBigInteger('email_enviado_por')->nullable();
                $table->foreign('email_enviado_por')->references('id')->on('users')->onDelete('set null');
            }
        });

        // Add missing columns to pedidos
        Schema::table('pedidos', function (Blueprint $table) {
            if (!Schema::hasColumn('pedidos', 'retencion_iva')) {
                $table->decimal('retencion_iva', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('pedidos', 'retencion_isr')) {
                $table->decimal('retencion_isr', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('pedidos', 'isr')) {
                $table->decimal('isr', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('pedidos', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable();
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('pedidos', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('pedidos', 'almacen_id')) {
                $table->unsignedBigInteger('almacen_id')->nullable();
                $table->foreign('almacen_id')->references('id')->on('almacenes')->onDelete('set null');
            }
        });

        // Add missing columns to pedido_items
        Schema::table('pedido_items', function (Blueprint $table) {
            if (!Schema::hasColumn('pedido_items', 'nombre')) {
                $table->string('nombre')->nullable();
            }
            if (!Schema::hasColumn('pedido_items', 'tipo_item')) {
                $table->string('tipo_item')->nullable();
            }
            if (!Schema::hasColumn('pedido_items', 'price_list_id')) {
                $table->unsignedBigInteger('price_list_id')->nullable();
            }
        });

        // Add missing columns to cotizacion_items
        Schema::table('cotizacion_items', function (Blueprint $table) {
            if (!Schema::hasColumn('cotizacion_items', 'price_list_id')) {
                $table->unsignedBigInteger('price_list_id')->nullable();
            }
        });

        // Add missing columns to kit_items
        if (Schema::hasTable('kit_items')) {
            Schema::table('kit_items', function (Blueprint $table) {
                if (!Schema::hasColumn('kit_items', 'deleted_at')) {
                    $table->timestamp('deleted_at')->nullable();
                }
                if (!Schema::hasColumn('kit_items', 'empresa_id')) {
                    $table->unsignedBigInteger('empresa_id')->nullable();
                    $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('set null');
                }
            });
        }

        // Add missing columns to venta_items
        Schema::table('venta_items', function (Blueprint $table) {
            if (!Schema::hasColumn('venta_items', 'costo_unitario')) {
                $table->decimal('costo_unitario', 15, 2)->default(0);
            }
        });

        // Create missing table entregas_dinero if not exists
        if (!Schema::hasTable('entregas_dinero')) {
            Schema::create('entregas_dinero', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->date('fecha_entrega')->nullable();
                $table->decimal('monto_efectivo', 15, 2)->default(0);
                $table->decimal('monto_transferencia', 15, 2)->default(0);
                $table->decimal('monto_cheques', 15, 2)->default(0);
                $table->decimal('monto_tarjetas', 15, 2)->default(0);
                $table->decimal('monto_otros', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                $table->string('estado')->default('pendiente');
                $table->text('notas')->nullable();
                $table->string('tipo_origen')->nullable();
                $table->unsignedBigInteger('id_origen')->nullable();
                $table->unsignedBigInteger('recibido_por')->nullable();
                $table->datetime('fecha_recibido')->nullable();
                $table->text('notas_recibido')->nullable();
                $table->unsignedBigInteger('cuenta_bancaria_id')->nullable();
                $table->boolean('entregado_responsable')->default(false);
                $table->datetime('fecha_entregado_responsable')->nullable();
                $table->string('responsable_organizacion')->nullable();
                $table->text('notas_entrega_responsable')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('recibido_por')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropColumn(['retencion_iva', 'retencion_isr', 'isr', 'email_enviado', 'email_enviado_fecha', 'email_enviado_por']);
        });
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn(['retencion_iva', 'retencion_isr', 'isr', 'created_by', 'updated_by', 'almacen_id']);
        });
        Schema::table('pedido_items', function (Blueprint $table) {
            $table->dropColumn(['nombre', 'tipo_item', 'price_list_id']);
        });
        Schema::table('cotizacion_items', function (Blueprint $table) {
            $table->dropColumn(['price_list_id']);
        });
        Schema::table('kit_items', function (Blueprint $table) {
            $table->dropColumn(['deleted_at', 'empresa_id']);
        });
        Schema::table('venta_items', function (Blueprint $table) {
            $table->dropColumn(['costo_unitario']);
        });
        Schema::dropIfExists('entregas_dinero');
    }
};
