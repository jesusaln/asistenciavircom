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
        Schema::table('rentas', function (Blueprint $table) {
            $table->string('ine_frontal')->nullable()->after('firma_hash');
            $table->string('ine_trasera')->nullable()->after('ine_frontal');
            $table->string('comprobante_domicilio')->nullable()->after('ine_trasera');
            $table->string('solicitud_renta')->nullable()->after('comprobante_domicilio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rentas', function (Blueprint $table) {
            $table->dropColumn([
                'ine_frontal',
                'ine_trasera',
                'comprobante_domicilio',
                'solicitud_renta',
            ]);
        });
    }
};
