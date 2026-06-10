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
        Schema::table('cuentas_por_pagar', function (Blueprint $table) {
            if (!Schema::hasColumn('cuentas_por_pagar', 'fecha_emision')) {
                $table->dateTime('fecha_emision')->nullable()->after('monto_pendiente');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuentas_por_pagar', function (Blueprint $table) {
            $table->dropColumn('fecha_emision');
        });
    }
};
