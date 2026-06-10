<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Crear tabla categorias si no existe
        if (!Schema::hasTable('categorias')) {
            Schema::create('categorias', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->text('descripcion')->nullable();
                $table->string('estado')->default('activo');
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->timestamps();
            });
        }

        // 2. Asegurar columnas en servicios
        if (Schema::hasTable('servicios')) {
            Schema::table('servicios', function (Blueprint $table) {
                if (!Schema::hasColumn('servicios', 'categoria_id')) {
                    $table->unsignedBigInteger('categoria_id')->nullable()->after('id');
                }
                if (!Schema::hasColumn('servicios', 'empresa_id')) {
                    $table->unsignedBigInteger('empresa_id')->nullable()->after('categoria_id');
                }
                if (!Schema::hasColumn('servicios', 'codigo')) {
                    $table->string('codigo')->nullable()->after('nombre');
                }
                if (!Schema::hasColumn('servicios', 'estado')) {
                    $table->string('estado')->default('activo')->after('precio');
                }
                if (!Schema::hasColumn('servicios', 'duracion')) {
                    $table->integer('duracion')->default(0)->after('estado');
                }
                if (!Schema::hasColumn('servicios', 'unidad_medida')) {
                    $table->string('unidad_medida')->default('servicio')->after('duracion');
                }
            });
        }

        // 3. Asegurar columnas en productos (para evitar futuros fallos)
        if (Schema::hasTable('productos')) {
            Schema::table('productos', function (Blueprint $table) {
                if (!Schema::hasColumn('productos', 'categoria_id')) {
                    $table->unsignedBigInteger('categoria_id')->nullable()->after('id');
                }
                if (!Schema::hasColumn('productos', 'empresa_id')) {
                    $table->unsignedBigInteger('empresa_id')->nullable()->after('categoria_id');
                }
                if (!Schema::hasColumn('productos', 'estado')) {
                    $table->string('estado')->default('activo')->after('precio');
                }
            });
        }
    }

    public function down(): void
    {
        // No destructivo
    }
};
