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
        Schema::table('polizas_servicio', function (Blueprint $table) {
            $table->timestamp('pausada_at')->nullable()->after('estado');
            $table->timestamp('reanudada_at')->nullable()->after('pausada_at');
            $table->string('motivo_pausa')->nullable()->after('reanudada_at');
            $table->integer('total_dias_pausa')->default(0)->after('motivo_pausa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('polizas_servicio', function (Blueprint $table) {
            $table->dropColumn(['pausada_at', 'reanudada_at', 'motivo_pausa', 'total_dias_pausa']);
        });
    }
};
