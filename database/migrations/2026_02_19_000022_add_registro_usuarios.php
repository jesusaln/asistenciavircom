<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // EMPRESA_CONFIGURACION - add registro_usuarios column if missing
        if (!Schema::hasColumn('empresa_configuracion', 'registro_usuarios')) {
            Schema::table('empresa_configuracion', function (Blueprint $table) {
                $table->boolean('registro_usuarios')->default(true)->after('pais');
            });
        }
    }

    public function down(): void
    {
    }
};
