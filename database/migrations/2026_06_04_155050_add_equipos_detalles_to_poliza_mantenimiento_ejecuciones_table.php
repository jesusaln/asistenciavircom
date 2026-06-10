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
        Schema::table('poliza_mantenimiento_ejecuciones', function (Blueprint $table) {
            $table->json('equipos_detalles')->nullable()->after('evidencia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('poliza_mantenimiento_ejecuciones', function (Blueprint $table) {
            $table->dropColumn('equipos_detalles');
        });
    }
};
