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
        Schema::table('clientes', function (Blueprint $table) {
            // Campos de domicilio fiscal completo (cuando es diferente al de servicio)
            $table->string('domicilio_fiscal_calle', 255)->nullable()->after('domicilio_fiscal_cp');
            $table->string('domicilio_fiscal_numero', 50)->nullable()->after('domicilio_fiscal_calle');
            $table->string('domicilio_fiscal_colonia', 255)->nullable()->after('domicilio_fiscal_numero');
            $table->string('domicilio_fiscal_municipio', 255)->nullable()->after('domicilio_fiscal_colonia');
            $table->string('domicilio_fiscal_estado', 10)->nullable()->after('domicilio_fiscal_municipio');
            $table->boolean('misma_direccion_fiscal')->default(true)->after('domicilio_fiscal_estado');

            // Razón social separada (puede ser diferente al nombre comercial)
            $table->string('razon_social', 255)->nullable()->after('nombre_razon_social');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn([
                'domicilio_fiscal_calle',
                'domicilio_fiscal_numero',
                'domicilio_fiscal_colonia',
                'domicilio_fiscal_municipio',
                'domicilio_fiscal_estado',
                'misma_direccion_fiscal',
                'razon_social',
            ]);
        });
    }
};
