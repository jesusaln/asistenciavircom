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
        if (!Schema::hasColumn('landing_marcas_autorizadas', 'empresa_id')) {
            Schema::table('landing_marcas_autorizadas', function (Blueprint $table) {
                $table->foreignId('empresa_id')->nullable()->constrained()->onDelete('cascade');
            });
        }

        if (!Schema::hasColumn('landing_procesos', 'empresa_id')) {
            Schema::table('landing_procesos', function (Blueprint $table) {
                $table->foreignId('empresa_id')->nullable()->constrained()->onDelete('cascade');
            });
        }

        // Assign existing records to the first company if it exists
        if (Schema::hasTable('empresas')) {
            $firstEmpresa = \DB::table('empresas')->first();
            if ($firstEmpresa) {
                \DB::table('landing_marcas_autorizadas')->update(['empresa_id' => $firstEmpresa->id]);
                \DB::table('landing_procesos')->update(['empresa_id' => $firstEmpresa->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('landing_marcas_autorizadas', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->dropColumn('empresa_id');
        });

        Schema::table('landing_procesos', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->dropColumn('empresa_id');
        });
    }
};
