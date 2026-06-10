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
        Schema::table('cita_items', function (Blueprint $table) {
            if (!Schema::hasColumn('cita_items', 'series')) {
                $table->json('series')->nullable()->after('notas');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cita_items', function (Blueprint $table) {
            if (Schema::hasColumn('cita_items', 'series')) {
                $table->dropColumn('series');
            }
        });
    }
};
