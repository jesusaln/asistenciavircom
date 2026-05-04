<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add auto-incrementing id to sat_estados (drop primary key on clave first)
        if (Schema::hasTable('sat_estados') && !Schema::hasColumn('sat_estados', 'id')) {
            Schema::table('sat_estados', function (Blueprint $table) {
                // Drop existing primary key (PostgreSQL requires this before adding new one)
                $table->dropPrimary('sat_estados_pkey');
                $table->bigIncrements('id')->primary()->first();
            });
        }

        // Add auto-incrementing id to sat_usos_cfdi
        if (Schema::hasTable('sat_usos_cfdi') && !Schema::hasColumn('sat_usos_cfdi', 'id')) {
            Schema::table('sat_usos_cfdi', function (Blueprint $table) {
                $table->dropPrimary('sat_usos_cfdi_pkey');
                $table->bigIncrements('id')->primary()->first();
            });
        }

        // Add auto-incrementing id to sat_regimenes_fiscales
        if (Schema::hasTable('sat_regimenes_fiscales') && !Schema::hasColumn('sat_regimenes_fiscales', 'id')) {
            Schema::table('sat_regimenes_fiscales', function (Blueprint $table) {
                $table->dropPrimary('sat_regimenes_fiscales_pkey');
                $table->bigIncrements('id')->primary()->first();
            });
        }

        // Add auto-incrementing id to sat_metodos_pago
        if (Schema::hasTable('sat_metodos_pago') && !Schema::hasColumn('sat_metodos_pago', 'id')) {
            Schema::table('sat_metodos_pago', function (Blueprint $table) {
                $table->dropPrimary('sat_metodos_pago_pkey');
                $table->bigIncrements('id')->primary()->first();
            });
        }

        // Add auto-incrementing id to sat_formas_pago
        if (Schema::hasTable('sat_formas_pago') && !Schema::hasColumn('sat_formas_pago', 'id')) {
            Schema::table('sat_formas_pago', function (Blueprint $table) {
                $table->dropPrimary('sat_formas_pago_pkey');
                $table->bigIncrements('id')->primary()->first();
            });
        }

        // Add auto-incrementing id to sat_productos_servicios
        if (Schema::hasTable('sat_productos_servicios') && !Schema::hasColumn('sat_productos_servicios', 'id')) {
            Schema::table('sat_productos_servicios', function (Blueprint $table) {
                $table->dropPrimary('sat_productos_servicios_pkey');
                $table->bigIncrements('id')->primary()->first();
            });
        }

        // Add auto-incrementing id to sat_unidades
        if (Schema::hasTable('sat_unidades') && !Schema::hasColumn('sat_unidades', 'id')) {
            Schema::table('sat_unidades', function (Blueprint $table) {
                $table->dropPrimary('sat_unidades_pkey');
                $table->bigIncrements('id')->primary()->first();
            });
        }

        // Add auto-incrementing id to sat_paises
        if (Schema::hasTable('sat_paises') && !Schema::hasColumn('sat_paises', 'id')) {
            Schema::table('sat_paises', function (Blueprint $table) {
                $table->dropPrimary('sat_paises_pkey');
                $table->bigIncrements('id')->primary()->first();
            });
        }
    }

    public function down(): void
    {
    }
};
