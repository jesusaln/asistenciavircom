<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mantenimientos', function (Blueprint $table) {
            if (!Schema::hasColumn('mantenimientos', 'fecha_programada')) {
                $table->date('fecha_programada')->nullable()->after('fecha');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mantenimientos', function (Blueprint $table) {
            if (Schema::hasColumn('mantenimientos', 'fecha_programada')) {
                $table->dropColumn('fecha_programada');
            }
        });
    }
};
