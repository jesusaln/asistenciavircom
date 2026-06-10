<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('cotizaciones', 'poliza_id')) {
            Schema::table('cotizaciones', function (Blueprint $table) {
                $table->foreignId('poliza_id')->nullable()->constrained('polizas_servicio')->nullOnDelete()->after('cliente_id');
                $table->string('equipo_nombre')->nullable()->after('poliza_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('poliza_id');
            $table->dropColumn('equipo_nombre');
        });
    }
};
