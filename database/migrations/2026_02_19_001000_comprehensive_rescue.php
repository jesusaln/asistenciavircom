<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // ALMACENES - add descripcion and estado
        if (!Schema::hasColumn('almacenes', 'descripcion')) {
            Schema::table('almacenes', function (Blueprint $table) {
                $table->text('descripcion')->nullable()->after('nombre');
            });
        }
        if (!Schema::hasColumn('almacenes', 'estado')) {
            Schema::table('almacenes', function (Blueprint $table) {
                $table->string('estado', 20)->default('activo')->after('activo');
            });
        }

        // MARCAS - add descripcion
        if (!Schema::hasColumn('marcas', 'descripcion')) {
            Schema::table('marcas', function (Blueprint $table) {
                $table->text('descripcion')->nullable()->after('nombre');
            });
        }

        // PRODUCTOS - add reservado
        if (!Schema::hasColumn('productos', 'reservado')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->integer('reservado')->default(0)->after('stock_minimo');
            });
        }

        // EMPRESA_CONFIGURACION - add iva_porcentaje and rfc
        if (!Schema::hasColumn('empresa_configuracion', 'iva_porcentaje')) {
            Schema::table('empresa_configuracion', function (Blueprint $table) {
                $table->decimal('iva_porcentaje', 5, 2)->default(16.00)->after('iva');
            });
        }
        if (!Schema::hasColumn('empresa_configuracion', 'rfc')) {
            Schema::table('empresa_configuracion', function (Blueprint $table) {
                $table->string('rfc', 13)->nullable()->after('razon_social');
            });
        }

        // CLIENTES - ensure these columns exist
        $clienteColumns = [
            'tipo_persona' => "VARCHAR(20) DEFAULT 'fisica'",
            'uso_cfdi' => "VARCHAR(10) DEFAULT 'G03'",
        ];

        foreach ($clienteColumns as $col => $definition) {
            if (!Schema::hasColumn('clientes', $col)) {
                DB::statement("ALTER TABLE clientes ADD COLUMN {$col} {$definition}");
            }
        }

        // Add empresa_configuracion columns that are referenced in tests
        if (!Schema::hasColumn('empresa_configuracion', 'regimen_fiscal')) {
            Schema::table('empresa_configuracion', function (Blueprint $table) {
                $table->string('regimen_fiscal', 10)->nullable()->after('rfc');
            });
        }

        if (!Schema::hasColumn('empresa_configuracion', 'uso_cfdi')) {
            Schema::table('empresa_configuracion', function (Blueprint $table) {
                $table->string('uso_cfdi', 10)->nullable()->after('regimen_fiscal');
            });
        }

        // Add price_lists table if needed
        if (!Schema::hasTable('price_lists')) {
            Schema::create('price_lists', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        // Add price_list_products table if needed
        if (!Schema::hasTable('price_list_products')) {
            Schema::create('price_list_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('price_list_id')->constrained()->onDelete('cascade');
                $table->foreignId('producto_id')->constrained()->onDelete('cascade');
                $table->decimal('precio', 15, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Don't remove columns in down() - too dangerous
    }
};
