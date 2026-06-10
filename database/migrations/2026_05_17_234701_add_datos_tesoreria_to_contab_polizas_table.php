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
        Schema::table('contab_polizas', function (Blueprint $table) {
            $table->foreignId('banco_movimiento_id')->nullable()->after('created_by')->constrained('bancos_movimientos')->nullOnDelete();
            $table->string('metodo_pago_sat', 10)->nullable()->after('banco_movimiento_id');
            $table->string('clave_spei_rastreo', 100)->nullable()->after('metodo_pago_sat');
            $table->string('rfc_tercero', 20)->nullable()->after('clave_spei_rastreo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contab_polizas', function (Blueprint $table) {
            $table->dropForeign(['banco_movimiento_id']);
            $table->dropColumn([
                'banco_movimiento_id',
                'metodo_pago_sat',
                'clave_spei_rastreo',
                'rfc_tercero',
            ]);
        });
    }
};
