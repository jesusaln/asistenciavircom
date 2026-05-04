<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // EMPRESA_CONFIGURACION - add pais column if missing
        if (!Schema::hasColumn('empresa_configuracion', 'pais')) {
            Schema::table('empresa_configuracion', function (Blueprint $table) {
                $table->string('pais', 255)->nullable()->after('estado');
            });
        }
    }

    public function down(): void
    {
    }
};
