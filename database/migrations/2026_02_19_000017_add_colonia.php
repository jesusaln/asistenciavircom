<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // EMPRESA_CONFIGURACION
        if (!Schema::hasColumn('empresa_configuracion', 'colonia')) {
            Schema::table('empresa_configuracion', function (Blueprint $table) {
                $table->string('colonia', 255)->nullable()->after('calle');
            });
        }
    }

    public function down(): void
    {
    }
};
