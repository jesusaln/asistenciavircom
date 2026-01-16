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
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('servicio_inicio_at')->nullable()->after('horas_trabajadas');
            $table->timestamp('servicio_fin_at')->nullable()->after('servicio_inicio_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['servicio_inicio_at', 'servicio_fin_at']);
        });
    }
};
