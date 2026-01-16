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
        Schema::table('sat_estados', function (Blueprint $table) {
            if (!Schema::hasColumn('sat_estados', 'vigencia_inicio')) {
                $table->date('vigencia_inicio')->nullable()->default('2000-01-01');
            }
            if (!Schema::hasColumn('sat_estados', 'vigencia_fin')) {
                $table->date('vigencia_fin')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sat_estados', function (Blueprint $table) {
            $table->dropColumn(['vigencia_inicio', 'vigencia_fin']);
        });
    }
};
