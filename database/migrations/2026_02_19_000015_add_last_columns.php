<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // EMPRESA_CONFIGURACION
        if (!Schema::hasColumn('empresa_configuracion', 'formato_fecha')) {
            Schema::table('empresa_configuracion', function (Blueprint $table) {
                $table->string('formato_fecha', 20)->default('d/m/Y')->after('formato_numeros');
            });
        }
        if (!Schema::hasColumn('empresa_configuracion', 'sitio_web')) {
            Schema::table('empresa_configuracion', function (Blueprint $table) {
                $table->string('sitio_web', 255)->nullable()->after('telefono');
            });
        }
    }

    public function down(): void
    {
    }
};
