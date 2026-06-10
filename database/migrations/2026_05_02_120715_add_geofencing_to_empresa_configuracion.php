<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            if (!Schema::hasColumn('empresa_configuracion', 'oficina_latitud')) {
                $table->decimal('oficina_latitud', 10, 8)->nullable();
            }
            if (!Schema::hasColumn('empresa_configuracion', 'oficina_longitud')) {
                $table->decimal('oficina_longitud', 11, 8)->nullable();
            }
            if (!Schema::hasColumn('empresa_configuracion', 'geofence_radio')) {
                $table->integer('geofence_radio')->default(200); // en metros
            }
            if (!Schema::hasColumn('empresa_configuracion', 'bloquear_fuera_de_rango')) {
                $table->boolean('bloquear_fuera_de_rango')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            $cols = [];
            foreach (['oficina_latitud', 'oficina_longitud', 'geofence_radio', 'bloquear_fuera_de_rango'] as $col) {
                if (Schema::hasColumn('empresa_configuracion', $col)) {
                    $cols[] = $col;
                }
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
