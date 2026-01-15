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
        Schema::table('citas', function (Blueprint $table) {
            $table->longText('firma_cliente')->nullable()->after('fotos_finales');
            $table->string('nombre_firmante')->nullable()->after('firma_cliente');
            $table->dateTime('fecha_firma')->nullable()->after('nombre_firmante');
            $table->longText('firma_tecnico')->nullable()->after('fecha_firma');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropColumn(['firma_cliente', 'nombre_firmante', 'fecha_firma', 'firma_tecnico']);
        });
    }
};
