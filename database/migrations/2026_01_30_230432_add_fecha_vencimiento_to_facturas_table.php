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
        if (!Schema::hasColumn('facturas', 'fecha_vencimiento')) {
            Schema::table('facturas', function (Blueprint $table) {
                $table->date('fecha_vencimiento')->nullable()->after('fecha_emision');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropColumn('fecha_vencimiento');
        });
    }
};
