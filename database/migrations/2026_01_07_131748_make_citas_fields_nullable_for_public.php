<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Hacer campos nullable para soportar citas públicas que no tienen fecha/hora confirmada
     */
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            // fecha_hora se confirma después por el administrador
            $table->dateTime('fecha_hora')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dateTime('fecha_hora')->nullable(false)->change();
        });
    }
};
