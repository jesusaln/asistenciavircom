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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'empresas_acceso')) {
                // Guardaremos los slugs de las empresas permitidas separados por comas (ej: "climas,vircom")
                // Si es null, el usuario solo tiene acceso a la empresa donde fue creado.
                $table->text('empresas_acceso')->nullable()->after('email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'empresas_acceso')) {
                $table->dropColumn('empresas_acceso');
            }
        });
    }
};
