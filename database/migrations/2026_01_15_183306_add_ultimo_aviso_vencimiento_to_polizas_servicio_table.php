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
        if (!Schema::hasColumn('polizas_servicio', 'ultimo_aviso_vencimiento_at')) {
            Schema::table('polizas_servicio', function (Blueprint $table) {
                $table->timestamp('ultimo_aviso_vencimiento_at')->nullable()->after('ultimo_cobro_generado_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('polizas_servicio', function (Blueprint $table) {
            $table->dropColumn('ultimo_aviso_vencimiento_at');
        });
    }
};
