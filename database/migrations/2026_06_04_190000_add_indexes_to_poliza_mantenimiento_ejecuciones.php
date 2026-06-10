<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poliza_mantenimiento_ejecuciones', function (Blueprint $table) {
            $table->index(['tecnico_id', 'estado', 'fecha_programada'], 'idx_pme_tecnico_estado_fecha');
            $table->index('mantenimiento_id', 'idx_pme_mantenimiento_id');
        });
    }

    public function down(): void
    {
        Schema::table('poliza_mantenimiento_ejecuciones', function (Blueprint $table) {
            $table->dropIndex('idx_pme_tecnico_estado_fecha');
            $table->dropIndex('idx_pme_mantenimiento_id');
        });
    }
};
