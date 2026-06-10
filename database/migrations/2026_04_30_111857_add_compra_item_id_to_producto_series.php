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
        Schema::table('producto_series', function (Blueprint $table) {
            if (!Schema::hasColumn('producto_series', 'compra_item_id')) {
                $table->foreignId('compra_item_id')->nullable()->constrained('compra_items')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('producto_series', function (Blueprint $table) {
            $table->dropColumn('compra_item_id');
        });
    }
};
