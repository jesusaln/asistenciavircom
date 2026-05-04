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
        Schema::table('citas', function (Blueprint $table) {
            $table->text('direccion_calle')->nullable()->change();
            $table->text('direccion_colonia')->nullable()->change();
            $table->text('marca_equipo')->nullable()->change();
            $table->text('modelo_equipo')->nullable()->change();
            $table->text('tipo_equipo')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->string('direccion_calle', 255)->nullable()->change();
            $table->string('direccion_colonia', 255)->nullable()->change();
            $table->string('marca_equipo', 255)->nullable()->change();
            $table->string('modelo_equipo', 255)->nullable()->change();
            $table->string('tipo_equipo', 255)->nullable()->change();
        });
    }
};
