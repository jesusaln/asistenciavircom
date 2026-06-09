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
        if (!Schema::hasTable('compra_items')) {
            Schema::create('compra_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->nullable()->index();
                $table->foreignId('compra_id')->constrained('compras')->onDelete('cascade');
                $table->nullableMorphs('comprable'); // producto o servicio
                $table->decimal('cantidad', 15, 2)->default(0);
                $table->decimal('precio', 15, 2)->default(0);
                $table->decimal('descuento', 5, 2)->default(0); // porcentaje
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('descuento_monto', 15, 2)->default(0);
                $table->string('unidad_medida')->nullable();
                $table->text('descripcion')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('cuentas_por_pagar')) {
            Schema::create('cuentas_por_pagar', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->nullable()->index();
                $table->foreignId('compra_id')->nullable()->constrained('compras')->onDelete('cascade');
                $table->foreignId('cfdi_id')->nullable();
                $table->foreignId('proveedor_id')->nullable()->constrained('proveedores');
                $table->decimal('monto_total', 15, 2)->default(0);
                $table->decimal('monto_pagado', 15, 2)->default(0);
                $table->decimal('monto_pendiente', 15, 2)->default(0);
                $table->date('fecha_vencimiento')->nullable();
                $table->string('estado')->default('pendiente');
                $table->text('notas')->nullable();
                $table->boolean('pagado')->default(false);
                $table->string('metodo_pago')->nullable();
                $table->foreignId('cuenta_bancaria_id')->nullable();
                $table->dateTime('fecha_pago')->nullable();
                $table->foreignId('pagado_por')->nullable()->constrained('users');
                $table->boolean('pagado_con_rep')->default(false);
                $table->boolean('pue_pagado')->default(false);
                $table->text('notas_pago')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();
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
        Schema::dropIfExists('cuentas_por_pagar');
        Schema::dropIfExists('compra_items');
    }
};
