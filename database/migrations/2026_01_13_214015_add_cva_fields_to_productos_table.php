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
        Schema::table('productos', function (Blueprint $blueprint) {
            $blueprint->string('origen')->default('local')->index();
            $blueprint->string('cva_clave')->nullable()->index();
            $blueprint->integer('stock_cedis')->default(0);
            $blueprint->timestamp('cva_last_sync')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $blueprint) {
            $blueprint->dropColumn(['origen', 'cva_clave', 'stock_cedis', 'cva_last_sync']);
        });
    }
};
