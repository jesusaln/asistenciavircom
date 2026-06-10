<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sat_descargas_masivas') && !Schema::hasColumn('sat_descargas_masivas', 'empresa_id')) {
            Schema::table('sat_descargas_masivas', function (Blueprint $table) {
                $table->unsignedBigInteger('empresa_id')->nullable()->after('id');
                $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            });

            // Asignar los registros existentes a la primera empresa
            try {
                $firstEmpresaId = DB::table('empresas')->orderBy('id')->value('id');
                if ($firstEmpresaId) {
                    DB::table('sat_descargas_masivas')->whereNull('empresa_id')->update(['empresa_id' => $firstEmpresaId]);
                }
            } catch (\Throwable $e) {
                // Silenciar si falla por alguna razón
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sat_descargas_masivas') && Schema::hasColumn('sat_descargas_masivas', 'empresa_id')) {
            Schema::table('sat_descargas_masivas', function (Blueprint $table) {
                $table->dropForeign(['empresa_id']);
                $table->dropColumn('empresa_id');
            });
        }
    }
};
