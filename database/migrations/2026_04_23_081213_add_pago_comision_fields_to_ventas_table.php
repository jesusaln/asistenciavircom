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
        if (Schema::hasTable('ventas')) {
            Schema::table('ventas', function (Blueprint $table) {
                if (!Schema::hasColumn('ventas', 'comision_pagada')) {
                    $table->boolean('comision_pagada')->default(false)->after('total');
                }
                if (!Schema::hasColumn('ventas', 'comision_pagada_at')) {
                    $table->timestamp('comision_pagada_at')->nullable()->after('comision_pagada');
                }
                if (!Schema::hasColumn('ventas', 'pago_comision_id')) {
                    $table->unsignedBigInteger('pago_comision_id')->nullable()->after('comision_pagada_at');
                }
                
                // Si existe la tabla de pagos de comisión, añadir la llave foránea
                if (Schema::hasTable('pagos_comisiones') && Schema::hasColumn('ventas', 'pago_comision_id')) {
                    // Check if foreign key already exists (optional but safer)
                    $table->foreign('pago_comision_id')->references('id')->on('pagos_comisiones')->onDelete('set null');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['pago_comision_id']);
            }
            $table->dropColumn(['comision_pagada', 'comision_pagada_at', 'pago_comision_id']);
        });
    }
};
