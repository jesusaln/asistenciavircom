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
            $table->boolean('comision_pagada')->default(false)->after('total');
            $table->timestamp('comision_pagada_at')->nullable()->after('comision_pagada');
            $table->unsignedBigInteger('pago_comision_id')->nullable()->after('comision_pagada_at');
            
            // Si existe la tabla de pagos de comisión, añadir la llave foránea
            if (Schema::hasTable('pago_comisiones')) {
                $table->foreign('pago_comision_id')->references('id')->on('pago_comisiones')->onDelete('set null');
            }
        });
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
