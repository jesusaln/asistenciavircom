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
        Schema::table('pedidos_online', function (Blueprint $table) {
            if (!Schema::hasColumn('pedidos_online', 'cliente_id')) {
                $table->foreignId('cliente_id')->nullable()->after('cliente_tienda_id')->constrained('clientes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos_online', function (Blueprint $table) {
            $table->dropColumn('cliente_id');
        });
    }
};
