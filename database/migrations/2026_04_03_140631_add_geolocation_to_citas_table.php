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
        if (!Schema::hasColumn('citas', 'latitud')) {
            Schema::table('citas', function (Blueprint $blueprint) {
                $blueprint->decimal('latitud', 10, 8)->nullable();
                $blueprint->decimal('longitud', 11, 8)->nullable();
                $blueprint->timestamp('fecha_gps')->nullable()->comment('Fecha/hora exacta en que se capturó la ubicación');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('citas', function (Blueprint $blueprint) {
            $blueprint->dropColumn(['latitud', 'longitud', 'fecha_gps']);
        });
    }
};
