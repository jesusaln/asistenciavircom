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
        Schema::table('servicios', function (Blueprint $table) {
            if (!Schema::hasColumn('servicios', 'tipo_comision_tecnica')) {
                $table->string('tipo_comision_tecnica')->nullable()->default('otro')
                    ->comment('instalacion, desinstalacion, refrigeracion, tierra, diagnostico, preventivo, otro');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servicios', function (Blueprint $table) {
            if (Schema::hasColumn('servicios', 'tipo_comision_tecnica')) {
                $table->dropColumn('tipo_comision_tecnica');
            }
        });
    }
};
