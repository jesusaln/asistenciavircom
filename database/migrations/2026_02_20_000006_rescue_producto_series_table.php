<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('producto_series', function (Blueprint $table) {
            if (!Schema::hasColumn('producto_series', 'compra_id')) {
                $table->unsignedBigInteger('compra_id')->nullable()->after('producto_id');
            }
            if (!Schema::hasColumn('producto_series', 'venta_id')) {
                $table->unsignedBigInteger('venta_id')->nullable()->after('compra_id');
            }
            if (!Schema::hasColumn('producto_series', 'cita_id')) {
                $table->unsignedBigInteger('cita_id')->nullable()->after('venta_id');
            }
        });
    }

    public function down(): void
    {
    }
};
