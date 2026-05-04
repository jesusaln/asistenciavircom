<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('productos', 'bloquear_venta_directa')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->boolean('bloquear_venta_directa')->default(false)->after('catalogo_web');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('productos', 'bloquear_venta_directa')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->dropColumn('bloquear_venta_directa');
            });
        }
    }
};
