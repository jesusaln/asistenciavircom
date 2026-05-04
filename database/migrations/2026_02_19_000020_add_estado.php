<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // EMPRESA_CONFIGURACION - add estado column if missing
        if (!Schema::hasColumn('empresa_configuracion', 'estado')) {
            Schema::table('empresa_configuracion', function (Blueprint $table) {
                $table->string('estado', 255)->nullable()->after('municipio');
            });
        }
    }

    public function down(): void
    {
    }
};
