<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            if (!Schema::hasColumn('productos', 'stock_minimo')) {
                $table->integer('stock_minimo')->default(0);
            }
            if (!Schema::hasColumn('productos', 'expires')) {
                $table->boolean('expires')->default(false);
            }
            if (!Schema::hasColumn('productos', 'margen_ganancia')) {
                $table->decimal('margen_ganancia', 8, 2)->default(0);
            }
            if (!Schema::hasColumn('productos', 'comision_vendedor')) {
                $table->decimal('comision_vendedor', 8, 2)->default(0);
            }
            if (!Schema::hasColumn('productos', 'codigo')) {
                $table->string('codigo')->nullable();
            }
            if (!Schema::hasColumn('productos', 'codigo_barras')) {
                $table->string('codigo_barras')->nullable();
            } else {
                try {
                    \Illuminate\Support\Facades\DB::statement('ALTER TABLE productos ALTER COLUMN codigo_barras DROP NOT NULL');
                } catch (\Throwable $e) {}
            }
            if (!Schema::hasColumn('productos', 'numero_serie')) {
                $table->string('numero_serie')->nullable();
            }
            if (!Schema::hasColumn('productos', 'proveedor_id')) {
                $table->unsignedBigInteger('proveedor_id')->nullable();
            }
            if (!Schema::hasColumn('productos', 'almacen_id')) {
                $table->unsignedBigInteger('almacen_id')->nullable();
            }
            if (!Schema::hasColumn('productos', 'precio_compra')) {
                $table->decimal('precio_compra', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('productos', 'unidad_medida')) {
                $table->string('unidad_medida')->default('Pieza');
            }
            if (!Schema::hasColumn('productos', 'fecha_vencimiento')) {
                $table->date('fecha_vencimiento')->nullable();
            }
            if (!Schema::hasColumn('productos', 'tipo_producto')) {
                $table->string('tipo_producto')->default('fisico');
            }
            if (!Schema::hasColumn('productos', 'imagen')) {
                $table->string('imagen')->nullable();
            }
        });
    }

    public function down(): void
    {
    }
};
