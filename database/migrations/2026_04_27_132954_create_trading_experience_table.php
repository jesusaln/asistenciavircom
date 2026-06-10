<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('trading_experience')) {
            Schema::create('trading_experience', function (Blueprint $blue) {
                $blue->id();
                $blue->string('symbol', 20)->index();
                $blue->string('timeframe', 10)->index();
                $blue->bigInteger('timestamp')->index();
                
                // Market Data
                $blue->decimal('open', 24, 8);
                $blue->decimal('high', 24, 8);
                $blue->decimal('low', 24, 8);
                $blue->decimal('close', 24, 8);
                $blue->decimal('volume', 24, 8);
                
                // AI Analysis State
                $blue->json('indicators_state')->nullable(); // EMA, RSI, BB, etc.
                $blue->string('market_regime', 50)->nullable();
                $blue->decimal('ai_confidence', 5, 2)->nullable();
                
                // Decisions & Outcomes
                $blue->string('signal', 20)->nullable(); // buy, sell, neutral
                $blue->decimal('trade_pnl', 10, 2)->nullable(); // If it was a real signal
                $blue->boolean('is_win')->nullable();
                
                $blue->timestamps();
                
                // Unique index to prevent duplicate data points
                $blue->unique(['symbol', 'timeframe', 'timestamp']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('trading_experience');
    }
};
