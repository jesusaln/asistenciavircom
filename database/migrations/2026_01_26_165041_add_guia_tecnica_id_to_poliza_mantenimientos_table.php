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
        Schema::table('poliza_mantenimientos', function (Blueprint $table) {
            $table->foreignId('guia_tecnica_id')->nullable()->constrained('guia_tecnicas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('poliza_mantenimientos', function (Blueprint $table) {
            $table->dropForeign(['guia_tecnica_id']);
            $table->dropColumn('guia_tecnica_id');
        });
    }
};
