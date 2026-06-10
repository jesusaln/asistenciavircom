<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cfdis')) {
            return;
        }

        Schema::table('cfdis', function (Blueprint $table) {
            if (!Schema::hasColumn('cfdis', 'direccion')) {
                $table->string('direccion')->nullable();
            }
            if (!Schema::hasColumn('cfdis', 'nombre_emisor')) {
                $table->string('nombre_emisor')->nullable();
            }
            if (!Schema::hasColumn('cfdis', 'regimen_fiscal_emisor')) {
                $table->string('regimen_fiscal_emisor')->nullable();
            }
            if (!Schema::hasColumn('cfdis', 'nombre_receptor')) {
                $table->string('nombre_receptor')->nullable();
            }
            if (!Schema::hasColumn('cfdis', 'estado_sat')) {
                $table->string('estado_sat')->nullable();
            }
            if (!Schema::hasColumn('cfdis', 'fecha_cancelacion')) {
                $table->dateTime('fecha_cancelacion')->nullable();
            }
            if (!Schema::hasColumn('cfdis', 'moneda')) {
                $table->string('moneda', 10)->nullable();
            }
            if (!Schema::hasColumn('cfdis', 'tipo_cambio')) {
                $table->decimal('tipo_cambio', 12, 4)->default(1);
            }
            if (!Schema::hasColumn('cfdis', 'subtotal')) {
                $table->decimal('subtotal', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('cfdis', 'descuento')) {
                $table->decimal('descuento', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('cfdis', 'total_impuestos_trasladados')) {
                $table->decimal('total_impuestos_trasladados', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('cfdis', 'total_impuestos_retenidos')) {
                $table->decimal('total_impuestos_retenidos', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('cfdis', 'metodo_pago')) {
                $table->string('metodo_pago')->nullable();
            }
            if (!Schema::hasColumn('cfdis', 'forma_pago')) {
                $table->string('forma_pago')->nullable();
            }
            if (!Schema::hasColumn('cfdis', 'condiciones_pago')) {
                $table->string('condiciones_pago')->nullable();
            }
            if (!Schema::hasColumn('cfdis', 'complementos')) {
                $table->json('complementos')->nullable();
            }
            if (!Schema::hasColumn('cfdis', 'observaciones')) {
                $table->text('observaciones')->nullable();
            }
            if (!Schema::hasColumn('cfdis', 'datos_adicionales')) {
                $table->json('datos_adicionales')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('cfdis')) {
            return;
        }

        Schema::table('cfdis', function (Blueprint $table) {
            foreach ([
                'direccion',
                'nombre_emisor',
                'regimen_fiscal_emisor',
                'nombre_receptor',
                'estado_sat',
                'fecha_cancelacion',
                'moneda',
                'tipo_cambio',
                'subtotal',
                'descuento',
                'total_impuestos_trasladados',
                'total_impuestos_retenidos',
                'metodo_pago',
                'forma_pago',
                'condiciones_pago',
                'complementos',
                'observaciones',
                'datos_adicionales',
            ] as $column) {
                if (Schema::hasColumn('cfdis', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
