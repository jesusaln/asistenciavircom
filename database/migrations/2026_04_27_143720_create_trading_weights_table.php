<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('trading_weights')) {
            Schema::create('trading_weights', function (Blueprint $table) {
                $table->id();
                $table->string('symbol')->index();
                $table->string('timeframe')->index();
                $table->json('weights');
                $table->float('accuracy')->default(0);
                $table->integer('total_trades')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('trading_weights');
    }
};
