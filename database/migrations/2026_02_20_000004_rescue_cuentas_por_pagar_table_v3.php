<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cuentas_por_pagar', function (Blueprint $table) {
            if (!Schema::hasColumn('cuentas_por_pagar', 'compra_id')) {
                $table->unsignedBigInteger('compra_id')->nullable()->after('proveedor_id');
            }
            if (!Schema::hasColumn('cuentas_por_pagar', 'monto_pagado')) {
                $table->decimal('monto_pagado', 15, 2)->default(0)->after('monto_pendiente');
            }
            if (!Schema::hasColumn('cuentas_por_pagar', 'estado')) {
                $table->string('estado')->default('pendiente')->after('fecha_vencimiento');
            }
            if (!Schema::hasColumn('cuentas_por_pagar', 'pagado')) {
                $table->boolean('pagado')->default(false)->after('estado');
            }
            if (!Schema::hasColumn('cuentas_por_pagar', 'metodo_pago')) {
                $table->string('metodo_pago')->nullable()->after('pagado');
            }
            if (!Schema::hasColumn('cuentas_por_pagar', 'cuenta_bancaria_id')) {
                $table->unsignedBigInteger('cuenta_bancaria_id')->nullable()->after('metodo_pago');
            }
            if (!Schema::hasColumn('cuentas_por_pagar', 'fecha_pago')) {
                $table->dateTime('fecha_pago')->nullable()->after('cuenta_bancaria_id');
            }
            if (!Schema::hasColumn('cuentas_por_pagar', 'pagado_por')) {
                $table->unsignedBigInteger('pagado_por')->nullable()->after('fecha_pago');
            }
            if (!Schema::hasColumn('cuentas_por_pagar', 'notas_pago')) {
                $table->text('notas_pago')->nullable()->after('pagado_por');
            }
            if (!Schema::hasColumn('cuentas_por_pagar', 'notas')) {
                $table->text('notas')->nullable()->after('notas_pago');
            }
        });
    }

    public function down(): void
    {
    }
};
