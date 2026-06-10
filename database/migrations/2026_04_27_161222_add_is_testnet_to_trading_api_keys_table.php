<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trading_api_keys', function (Blueprint $table) {
            if (!Schema::hasColumn('trading_api_keys', 'is_testnet')) {
                $table->boolean('is_testnet')->default(true)->after('binance_secret_encrypted');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trading_api_keys', function (Blueprint $table) {
            if (Schema::hasColumn('trading_api_keys', 'is_testnet')) {
                $table->dropColumn('is_testnet');
            }
        });
    }
};
