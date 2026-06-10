<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // CITAS - activo column
        if (!Schema::hasColumn('citas', 'activo')) {
            Schema::table('citas', function (Blueprint $table) {
                $table->boolean('activo')->default(true)->after('deleted_at');
            });
        }

        // PRODUCTO_SERIES - almacen_id
        if (!Schema::hasColumn('producto_series', 'almacen_id')) {
            Schema::table('producto_series', function (Blueprint $table) {
                $table->foreignId('almacen_id')->nullable()->constrained('almacenes')->onDelete('set null');
            });
        }

        // VENTAS - created_by
        if (!Schema::hasColumn('ventas', 'created_by')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            });
        }

        // EMPRESA_CONFIGURACION - moneda, razon_social
        if (!Schema::hasColumn('empresa_configuracion', 'moneda')) {
            Schema::table('empresa_configuracion', function (Blueprint $table) {
                $table->string('moneda', 3)->default('MXN')->after('pais');
            });
        }
        if (!Schema::hasColumn('empresa_configuracion', 'razon_social')) {
            Schema::table('empresa_configuracion', function (Blueprint $table) {
                $table->string('razon_social', 255)->nullable()->after('nombre_comercial');
            });
        }

        // PRODUCTOS - sku
        if (!Schema::hasColumn('productos', 'sku')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->string('sku', 50)->nullable()->unique()->after('codigo');
            });
        }

        // KIT_ITEMS table
        if (!Schema::hasTable('kit_items')) {
            Schema::create('kit_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('kit_producto_id')->constrained('productos')->onDelete('cascade');
                $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
                $table->integer('cantidad')->default(1);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
    }
};
