<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // EMPRESA_CONFIGURACION - add missing ciudad column
        if (!Schema::hasColumn('empresa_configuracion', 'ciudad')) {
            Schema::table('empresa_configuracion', function (Blueprint $table) {
                $table->string('ciudad', 255)->nullable()->after('estado');
            });
        }
    }

    public function down(): void
    {
    }
};
