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
            if (!Schema::hasColumn('ventas', 'taller_orden_id')) {
                $table->unsignedBigInteger('taller_orden_id')->nullable()->after('cita_id');
                $table->foreign('taller_orden_id')->references('id')->on('taller_ordenes')->onDelete('set null');
            }
        });

        Schema::table('taller_ordenes', function (Blueprint $table) {
            if (!Schema::hasColumn('taller_ordenes', 'venta_id')) {
                $table->unsignedBigInteger('venta_id')->nullable()->after('tecnico_id');
                $table->foreign('venta_id')->references('id')->on('ventas')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            if (Schema::hasColumn('ventas', 'taller_orden_id')) {
                $table->dropForeign(['taller_orden_id']);
                $table->dropColumn('taller_orden_id');
            }
        });

        Schema::table('taller_ordenes', function (Blueprint $table) {
            if (Schema::hasColumn('taller_ordenes', 'venta_id')) {
                $table->dropForeign(['venta_id']);
                $table->dropColumn('venta_id');
            }
        });
    }
};
