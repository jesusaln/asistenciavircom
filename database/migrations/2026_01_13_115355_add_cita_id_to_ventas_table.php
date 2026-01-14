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
        Schema::table('ventas', function (Blueprint $blueprint) {
            $blueprint->foreignId('cita_id')->nullable()->constrained('citas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $blueprint) {
            $blueprint->dropForeign(['cita_id']);
            $blueprint->dropColumn('cita_id');
        });
    }
};
