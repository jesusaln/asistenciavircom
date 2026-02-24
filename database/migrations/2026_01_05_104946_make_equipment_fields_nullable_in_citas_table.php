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
        Schema::table('citas', function (Blueprint $table) {
            if (Schema::hasColumn('citas', 'tipo_equipo')) {
                $table->string('tipo_equipo')->nullable()->change();
            }
            if (Schema::hasColumn('citas', 'marca_equipo')) {
                $table->string('marca_equipo')->nullable()->change();
            }
            if (Schema::hasColumn('citas', 'modelo_equipo')) {
                $table->string('modelo_equipo')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->string('tipo_equipo')->nullable(false)->change();
            $table->string('marca_equipo')->nullable(false)->change();
            $table->string('modelo_equipo')->nullable(false)->change();
        });
    }
};
