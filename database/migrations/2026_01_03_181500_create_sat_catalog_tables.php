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
        // Tabla de Regímenes Fiscales SAT
        if (!Schema::hasTable('sat_regimenes_fiscales')) {
            Schema::create('sat_regimenes_fiscales', function (Blueprint $table) {
                $table->string('clave', 10)->primary();
                $table->string('descripcion');
                $table->boolean('persona_fisica')->default(false);
                $table->boolean('persona_moral')->default(false);
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        // Tabla de Usos CFDI SAT
        if (!Schema::hasTable('sat_usos_cfdi')) {
            Schema::create('sat_usos_cfdi', function (Blueprint $table) {
                $table->string('clave', 10)->primary();
                $table->string('descripcion');
                $table->boolean('persona_fisica')->default(true);
                $table->boolean('persona_moral')->default(true);
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        // Tabla de Estados SAT
        if (!Schema::hasTable('sat_estados')) {
            Schema::create('sat_estados', function (Blueprint $table) {
                $table->string('clave', 10)->primary();
                $table->string('nombre');
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        // Tabla de Métodos de Pago SAT
        if (!Schema::hasTable('sat_metodos_pago')) {
            Schema::create('sat_metodos_pago', function (Blueprint $table) {
                $table->string('clave', 10)->primary();
                $table->string('descripcion');
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        // Tabla de Formas de Pago SAT
        if (!Schema::hasTable('sat_formas_pago')) {
            Schema::create('sat_formas_pago', function (Blueprint $table) {
                $table->string('clave', 10)->primary();
                $table->string('descripcion');
                $table->boolean('bancarizado')->default(false);
                $table->integer('orden')->default(99);
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        // Tabla de Claves de Unidad SAT
        if (!Schema::hasTable('sat_claves_unidad')) {
            Schema::create('sat_claves_unidad', function (Blueprint $table) {
                $table->string('clave', 10)->primary();
                $table->string('nombre');
                $table->boolean('activo')->default(true);
                $table->boolean('uso_comun')->default(false);
                $table->timestamps();
            });
        }

        // Tabla de Impuestos SAT
        if (!Schema::hasTable('sat_impuestos')) {
            Schema::create('sat_impuestos', function (Blueprint $table) {
                $table->string('clave', 10)->primary();
                $table->string('descripcion');
                $table->boolean('retencion')->default(false);
                $table->boolean('traslado')->default(false);
                $table->string('local_o_federal', 20)->default('federal');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sat_impuestos');
        Schema::dropIfExists('sat_claves_unidad');
        Schema::dropIfExists('sat_formas_pago');
        Schema::dropIfExists('sat_metodos_pago');
        Schema::dropIfExists('sat_estados');
        Schema::dropIfExists('sat_usos_cfdi');
        Schema::dropIfExists('sat_regimenes_fiscales');
    }
};
