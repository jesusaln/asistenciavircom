<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bancos_cuentas', function (Blueprint $table) {
            if (!Schema::hasColumn('bancos_cuentas', 'es_fiscal')) {
                $table->boolean('es_fiscal')->default(true)->after('cuenta_contable_id');
            }
            if (!Schema::hasColumn('bancos_cuentas', 'tipo')) {
                $table->string('tipo', 50)->default('cuenta')->after('es_fiscal');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bancos_cuentas', function (Blueprint $table) {
            if (Schema::hasColumn('bancos_cuentas', 'es_fiscal')) {
                $table->dropColumn('es_fiscal');
            }
            if (Schema::hasColumn('bancos_cuentas', 'tipo')) {
                $table->dropColumn('tipo');
            }
        });
    }
};
