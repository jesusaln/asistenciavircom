<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('productos', 'precio_tienda_online')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->decimal('precio_tienda_online', 12, 2)->nullable()->after('precio_venta')
                    ->comment('Precio competitivo para tienda online (sugerido por ML)');
            });
        }
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            if (Schema::hasColumn('productos', 'precio_tienda_online')) {
                $table->dropColumn('precio_tienda_online');
            }
        });
    }
};