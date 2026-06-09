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
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->uuid('sharing_token')->nullable()->unique()->after('id');
        });

        Schema::table('pedidos', function (Blueprint $table) {
            $table->uuid('sharing_token')->nullable()->unique()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropColumn('sharing_token');
        });

        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn('sharing_token');
        });
    }
};
