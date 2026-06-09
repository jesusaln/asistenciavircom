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
        Schema::table('ventas', function (Blueprint $table) {
            if (!Schema::hasColumn('ventas', 'aplicar_retencion_iva')) {
                $table->boolean('aplicar_retencion_iva')->default(false);
            }
            if (!Schema::hasColumn('ventas', 'aplicar_retencion_isr')) {
                $table->boolean('aplicar_retencion_isr')->default(false);
            }
            if (!Schema::hasColumn('ventas', 'cuenta_bancaria_id')) {
                $table->foreignId('cuenta_bancaria_id')->nullable()->constrained('cuentas_bancarias')->nullOnDelete();
            }
            if (!Schema::hasColumn('ventas', 'pagado_por')) {
                $table->foreignId('pagado_por')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['aplicar_retencion_iva', 'aplicar_retencion_isr', 'cuenta_bancaria_id', 'pagado_por']);
        });
    }
};
