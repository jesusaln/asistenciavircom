<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'almacen_venta_id')) {
                $table->foreignId('almacen_venta_id')->nullable()->constrained('almacenes')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'almacen_compra_id')) {
                $table->foreignId('almacen_compra_id')->nullable()->constrained('almacenes')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['almacen_venta_id']);
            $table->dropForeign(['almacen_compra_id']);
            $table->dropColumn(['almacen_venta_id', 'almacen_compra_id']);
        });
    }
};
