<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            if (!Schema::hasColumn('proveedores', 'tipo_persona')) {
                $table->string('tipo_persona', 20)->nullable()->default('moral');
            }
            if (!Schema::hasColumn('proveedores', 'regimen_fiscal')) {
                $table->string('regimen_fiscal', 10)->nullable()->after('rfc');
            }
            if (!Schema::hasColumn('proveedores', 'uso_cfdi')) {
                $table->string('uso_cfdi', 10)->nullable()->after('regimen_fiscal');
            }
            if (!Schema::hasColumn('proveedores', 'calle')) {
                $table->string('calle', 255)->nullable();
            }
            if (!Schema::hasColumn('proveedores', 'numero_exterior')) {
                $table->string('numero_exterior', 50)->nullable();
            }
            if (!Schema::hasColumn('proveedores', 'numero_interior')) {
                $table->string('numero_interior', 50)->nullable();
            }
            if (!Schema::hasColumn('proveedores', 'colonia')) {
                $table->string('colonia', 255)->nullable();
            }
            if (!Schema::hasColumn('proveedores', 'codigo_postal')) {
                $table->string('codigo_postal', 10)->nullable();
            }
            if (!Schema::hasColumn('proveedores', 'municipio')) {
                $table->string('municipio', 255)->nullable();
            }
            if (!Schema::hasColumn('proveedores', 'estado')) {
                $table->string('estado', 255)->nullable();
            }
            if (!Schema::hasColumn('proveedores', 'pais')) {
                $table->string('pais', 255)->nullable()->default('México');
            }
            if (!Schema::hasColumn('proveedores', 'codigo')) {
                $table->string('codigo')->nullable();
            }
        });
    }

    public function down(): void
    {
    }
};
