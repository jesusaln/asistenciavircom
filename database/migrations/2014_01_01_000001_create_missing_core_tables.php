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
        if (!Schema::hasTable('cuentas_por_cobrar')) {
            Schema::create('cuentas_por_cobrar', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->unsignedBigInteger('cobrable_id')->nullable();
                $table->string('cobrable_type')->nullable();
                $table->unsignedBigInteger('cfdi_id')->nullable();
                $table->unsignedBigInteger('cliente_id')->nullable();
                $table->unsignedBigInteger('venta_id')->nullable(); // Legacy
                $table->decimal('monto_total', 15, 2)->default(0);
                $table->decimal('monto_pagado', 15, 2)->default(0);
                $table->decimal('monto_pendiente', 15, 2)->default(0);
                $table->date('fecha_vencimiento')->nullable();
                $table->string('estado')->default('pendiente');
                $table->text('notas')->nullable();
                $table->boolean('pagado')->default(false);
                $table->string('metodo_pago')->nullable();
                $table->unsignedBigInteger('cuenta_bancaria_id')->nullable();
                $table->dateTime('fecha_pago')->nullable();
                $table->unsignedBigInteger('pagado_por')->nullable();
                $table->text('notas_pago')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('backup_logs')) {
            Schema::create('backup_logs', function (Blueprint $table) {
                $table->id();
                $table->string('filename')->nullable();
                $table->string('path')->nullable();
                $table->string('type')->nullable();
                $table->string('method')->nullable();
                $table->string('status')->nullable();
                $table->text('message')->nullable();
                $table->json('metadata')->nullable();
                $table->integer('size')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pedidos')) {
            Schema::create('pedidos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('cliente_id')->nullable();
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->string('numero_pedido')->nullable();
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('iva', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                $table->dateTime('fecha')->nullable();
                $table->string('estado')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pedido_items')) {
            Schema::create('pedido_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pedido_id')->nullable();
                $table->unsignedBigInteger('pedible_id')->nullable();
                $table->string('pedible_type')->nullable();
                $table->decimal('cantidad', 15, 2)->default(0);
                $table->decimal('precio', 15, 2)->default(0);
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('inventarios')) {
            Schema::create('inventarios', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('producto_id')->nullable();
                $table->unsignedBigInteger('almacen_id')->nullable();
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->decimal('cantidad', 15, 2)->default(0);
                $table->decimal('stock_minimo', 15, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuentas_por_cobrar');
        Schema::dropIfExists('backup_logs');
    }
};
