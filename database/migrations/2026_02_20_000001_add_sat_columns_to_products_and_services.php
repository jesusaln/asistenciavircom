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
        Schema::table('productos', function (Blueprint $table) {
            if (!Schema::hasColumn('productos', 'sat_clave_prod_serv')) {
                $table->string('sat_clave_prod_serv', 20)->nullable();
            }
            if (!Schema::hasColumn('productos', 'sat_clave_unidad')) {
                $table->string('sat_clave_unidad', 10)->nullable();
            }
            if (!Schema::hasColumn('productos', 'sat_objeto_imp')) {
                $table->string('sat_objeto_imp', 3)->nullable();
            }
            if (!Schema::hasColumn('productos', 'reservado')) {
                $table->decimal('reservado', 12, 2)->default(0);
            }
        });

        Schema::table('servicios', function (Blueprint $table) {
            if (!Schema::hasColumn('servicios', 'sat_clave_prod_serv')) {
                $table->string('sat_clave_prod_serv', 20)->nullable();
            }
            if (!Schema::hasColumn('servicios', 'sat_clave_unidad')) {
                $table->string('sat_clave_unidad', 10)->nullable();
            }
            if (!Schema::hasColumn('servicios', 'sat_objeto_imp')) {
                $table->string('sat_objeto_imp', 3)->nullable();
            }
            if (!Schema::hasColumn('servicios', 'margen_ganancia')) {
                $table->decimal('margen_ganancia', 8, 2)->default(0);
            }
            if (!Schema::hasColumn('servicios', 'comision_vendedor')) {
                $table->decimal('comision_vendedor', 8, 2)->default(0);
            }
            if (!Schema::hasColumn('servicios', 'es_instalacion')) {
                $table->boolean('es_instalacion')->default(false);
            }
            if (!Schema::hasColumn('servicios', 'codigo')) {
                $table->string('codigo')->nullable();
            }
            if (!Schema::hasColumn('servicios', 'duracion')) {
                $table->integer('duracion')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['sat_clave_prod_serv', 'sat_clave_unidad', 'sat_objeto_imp', 'reservado']);
        });

        Schema::table('servicios', function (Blueprint $table) {
            $table->dropColumn(['sat_clave_prod_serv', 'sat_clave_unidad', 'sat_objeto_imp', 'margen_ganancia']);
        });
    }
};
