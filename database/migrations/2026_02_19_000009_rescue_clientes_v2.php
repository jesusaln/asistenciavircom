<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            if (!Schema::hasColumn('clientes', 'tipo_persona')) {
                $table->string('tipo_persona', 20)->nullable()->default('fisica');
            }
            if (!Schema::hasColumn('clientes', 'regimen_fiscal')) {
                $table->string('regimen_fiscal', 10)->nullable()->after('rfc');
            }
            if (!Schema::hasColumn('clientes', 'uso_cfdi')) {
                $table->string('uso_cfdi', 10)->nullable()->after('regimen_fiscal');
            }
            if (!Schema::hasColumn('clientes', 'calle')) {
                $table->string('calle', 255)->nullable();
            }
            if (!Schema::hasColumn('clientes', 'numero_exterior')) {
                $table->string('numero_exterior', 50)->nullable();
            }
            if (!Schema::hasColumn('clientes', 'numero_interior')) {
                $table->string('numero_interior', 50)->nullable();
            }
            if (!Schema::hasColumn('clientes', 'colonia')) {
                $table->string('colonia', 255)->nullable();
            }
            if (!Schema::hasColumn('clientes', 'codigo_postal')) {
                $table->string('codigo_postal', 10)->nullable();
            }
            if (!Schema::hasColumn('clientes', 'municipio')) {
                $table->string('municipio', 255)->nullable();
            }
            if (!Schema::hasColumn('clientes', 'estado')) {
                $table->string('estado', 255)->nullable();
            }
            if (!Schema::hasColumn('clientes', 'pais')) {
                $table->string('pais', 255)->nullable()->default('MX');
            }
            if (!Schema::hasColumn('clientes', 'curp')) {
                $table->string('curp', 20)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn([
                'tipo_persona',
                'regimen_fiscal',
                'uso_cfdi',
                'calle',
                'numero_exterior',
                'numero_interior',
                'colonia',
                'codigo_postal',
                'municipio',
                'estado',
                'pais',
                'curp'
            ]);
        });
    }
};
