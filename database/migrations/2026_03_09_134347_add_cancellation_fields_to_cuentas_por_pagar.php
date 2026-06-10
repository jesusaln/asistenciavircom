<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cuentas_por_pagar', function (Blueprint $table) {
            if (!Schema::hasColumn('cuentas_por_pagar', 'motivo_cancelacion')) {
                $table->text('motivo_cancelacion')->nullable()->after('notas_pago');
            }

            if (!Schema::hasColumn('cuentas_por_pagar', 'fecha_cancelacion')) {
                $table->dateTime('fecha_cancelacion')->nullable()->after('motivo_cancelacion');
            }

            if (!Schema::hasColumn('cuentas_por_pagar', 'saldo_favor_generado')) {
                $table->decimal('saldo_favor_generado', 15, 2)->default(0)->after('fecha_cancelacion');
            }

            if (!Schema::hasColumn('cuentas_por_pagar', 'fecha_saldo_favor')) {
                $table->dateTime('fecha_saldo_favor')->nullable()->after('saldo_favor_generado');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cuentas_por_pagar', function (Blueprint $table) {
            if (Schema::hasColumn('cuentas_por_pagar', 'fecha_cancelacion')) {
                $table->dropColumn('fecha_cancelacion');
            }

            if (Schema::hasColumn('cuentas_por_pagar', 'motivo_cancelacion')) {
                $table->dropColumn('motivo_cancelacion');
            }

            if (Schema::hasColumn('cuentas_por_pagar', 'fecha_saldo_favor')) {
                $table->dropColumn('fecha_saldo_favor');
            }

            if (Schema::hasColumn('cuentas_por_pagar', 'saldo_favor_generado')) {
                $table->dropColumn('saldo_favor_generado');
            }
        });
    }
};
