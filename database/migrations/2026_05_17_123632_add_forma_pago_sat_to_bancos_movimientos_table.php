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
        Schema::table('bancos_movimientos', function (Blueprint $table) {
            $table->string('forma_pago_sat', 10)->default('03')->after('tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bancos_movimientos', function (Blueprint $table) {
            $table->dropColumn('forma_pago_sat');
        });
    }
};
