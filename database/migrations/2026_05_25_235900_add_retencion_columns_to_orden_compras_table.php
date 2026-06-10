<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orden_compras', function (Blueprint $table) {
            if (!Schema::hasColumn('orden_compras', 'retencion_iva')) {
                $table->decimal('retencion_iva', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('orden_compras', 'retencion_isr')) {
                $table->decimal('retencion_isr', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('orden_compras', 'isr')) {
                $table->decimal('isr', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('orden_compras', 'aplicar_retencion_iva')) {
                $table->boolean('aplicar_retencion_iva')->default(false);
            }
            if (!Schema::hasColumn('orden_compras', 'aplicar_retencion_isr')) {
                $table->boolean('aplicar_retencion_isr')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('orden_compras', function (Blueprint $table) {
            $table->dropColumn([
                'retencion_iva',
                'retencion_isr',
                'isr',
                'aplicar_retencion_iva',
                'aplicar_retencion_isr',
            ]);
        });
    }
};
