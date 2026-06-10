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
        Schema::table('poliza_mantenimiento_ejecuciones', function (Blueprint $table) {
            $table->text('notas_iniciales')->nullable()->after('resultado');
            $table->json('fotos_antes')->nullable()->after('notas_iniciales');
            $table->json('fotos_despues')->nullable()->after('fotos_antes');
            $table->string('numero_serie', 100)->nullable()->after('fotos_despues');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('poliza_mantenimiento_ejecuciones', function (Blueprint $table) {
            $table->dropColumn(['notas_iniciales', 'fotos_antes', 'fotos_despues', 'numero_serie']);
        });
    }
};
