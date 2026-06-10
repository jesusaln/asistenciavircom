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
        Schema::table('taller_ordenes', function (Blueprint $table) {
            if (!Schema::hasColumn('taller_ordenes', 'fecha_compromiso')) {
                $table->timestamp('fecha_compromiso')->nullable()->after('fecha_recepcion');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taller_ordenes', function (Blueprint $table) {
            if (Schema::hasColumn('taller_ordenes', 'fecha_compromiso')) {
                $table->dropColumn('fecha_compromiso');
            }
        });
    }
};
