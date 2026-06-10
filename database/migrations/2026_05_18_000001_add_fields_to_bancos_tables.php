<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bancos_cuentas', function (Blueprint $table) {
            if (!Schema::hasColumn('bancos_cuentas', 'saldo_actual')) {
                $table->decimal('saldo_actual', 15, 2)->default(0)->after('saldo_inicial');
            }
        });

        // Copiar saldo_inicial a saldo_actual para cuentas existentes donde saldo_actual sea 0
        DB::statement('UPDATE bancos_cuentas SET saldo_actual = saldo_inicial WHERE saldo_actual = 0');

        Schema::table('bancos_movimientos', function (Blueprint $table) {
            if (!Schema::hasColumn('bancos_movimientos', 'conciliable_type')) {
                $table->string('conciliable_type')->nullable()->after('poliza_id');
            }
            if (!Schema::hasColumn('bancos_movimientos', 'conciliable_id')) {
                $table->unsignedBigInteger('conciliable_id')->nullable()->after('conciliable_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bancos_cuentas', function (Blueprint $table) {
            if (Schema::hasColumn('bancos_cuentas', 'saldo_actual')) {
                $table->dropColumn('saldo_actual');
            }
        });

        Schema::table('bancos_movimientos', function (Blueprint $table) {
            if (Schema::hasColumn('bancos_movimientos', 'conciliable_type')) {
                $table->dropColumn(['conciliable_type', 'conciliable_id']);
            }
        });
    }
};
