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
        Schema::table('landing_procesos', function (Blueprint $table) {
            if (!Schema::hasColumn('landing_procesos', 'tipo')) {
                $table->string('tipo')->default('reparacion')->after('icono'); // reparacion, instalacion
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('landing_procesos', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};
