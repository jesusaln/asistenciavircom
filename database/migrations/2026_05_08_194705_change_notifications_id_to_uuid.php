<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Eliminar la columna id actual (numérica)
            // En Postgres, primero quitamos la llave primaria
            $table->dropColumn('id');
        });

        Schema::table('notifications', function (Blueprint $table) {
            // Añadir el nuevo id tipo UUID como primaria con valor por defecto para filas existentes
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('id');
        });
        Schema::table('notifications', function (Blueprint $table) {
            $table->bigIncrements('id')->primary()->first();
        });
    }
};
