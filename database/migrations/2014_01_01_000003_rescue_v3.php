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
        // 1. categoria_herramientas
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

        // 2. herramientas
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

        // 3. categoria_proyectos (just in case)
        if (!Schema::hasTable('categoria_proyectos')) {
            Schema::create('categoria_proyectos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->string('nombre');
                $table->text('descripcion')->nullable();
                $table->string('color', 20)->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        // 4. proveedores
        if (!Schema::hasTable('proveedores')) {
            Schema::create('proveedores', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->string('nombre_razon_social');
                $table->string('rfc', 15)->nullable()->index();
                $table->string('email')->nullable();
                $table->string('telefono', 20)->nullable();
                $table->text('direccion')->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        // 5. cuentas_por_pagar
        if (!Schema::hasTable('cuentas_por_pagar')) {
            Schema::create('cuentas_por_pagar', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->unsignedBigInteger('proveedor_id')->nullable()->index();
                $table->decimal('monto_total', 15, 2);
                $table->decimal('monto_pendiente', 15, 2);
                $table->date('fecha_emision');
                $table->date('fecha_vencimiento')->nullable();
                $table->string('estado')->default('pendiente');
                $table->string('referencia')->nullable();
                $table->boolean('pue_pagado')->default(false);
                $table->boolean('pagado_con_rep')->default(false);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
