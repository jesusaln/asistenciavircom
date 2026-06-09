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
        Schema::table('mantenimientos', function (Blueprint $table) {
            if (!Schema::hasColumn('mantenimientos', 'alerta_enviada')) {
                $table->boolean('alerta_enviada')->default(false);
            }
            if (!Schema::hasColumn('mantenimientos', 'proximo_kilometraje')) {
                $table->integer('proximo_kilometraje')->nullable();
            }
            if (!Schema::hasColumn('mantenimientos', 'km_anticipacion_alerta')) {
                $table->integer('km_anticipacion_alerta')->nullable();
            }
            if (!Schema::hasColumn('mantenimientos', 'recordatorios_enviados')) {
                $table->json('recordatorios_enviados')->nullable();
            }
            if (!Schema::hasColumn('mantenimientos', 'frecuencia_recordatorio_dias')) {
                $table->integer('frecuencia_recordatorio_dias')->default(7);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->dropColumn([
                'alerta_enviada',
                'proximo_kilometraje',
                'km_anticipacion_alerta',
                'recordatorios_enviados',
                'frecuencia_recordatorio_dias'
            ]);
        });
    }
};
