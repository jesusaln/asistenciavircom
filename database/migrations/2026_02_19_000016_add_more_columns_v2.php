<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // EMPRESA_CONFIGURACION
        if (!Schema::hasColumn('empresa_configuracion', 'codigo_postal')) {
            Schema::table('empresa_configuracion', function (Blueprint $table) {
                $table->string('codigo_postal', 10)->nullable()->after('colonia');
            });
        }
        if (!Schema::hasColumn('empresa_configuracion', 'formato_hora')) {
            Schema::table('empresa_configuracion', function (Blueprint $table) {
                $table->string('formato_hora', 20)->default('H:i')->after('formato_fecha');
            });
        }
    }

    public function down(): void
    {
    }
};
