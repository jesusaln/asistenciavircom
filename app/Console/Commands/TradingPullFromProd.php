<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\TradingWeight;
use App\Models\TradingExperience;

class TradingPullFromProd extends Command
{
    protected $signature = 'trading:pull-from-prod {symbol=BTCUSDT} {timeframe=15m}';
    protected $description = 'Pull AI weights and experience from production server to local';

    public function handle()
    {
        $symbol = $this->argument('symbol');
        $timeframe = $this->argument('timeframe');
        
        $this->info("📥 Pulling training data for $symbol ($timeframe) from production...");

        // 1. Pull Weights
        $response = Http::withHeaders([
            'X-Trading-Token' => config('services.trading.sync_token')
        ])->get("https://climasdeldesierto.com/trading/get-weights", [
            'symbol' => $symbol,
            'timeframe' => $timeframe
        ]);

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['weights'])) {
                TradingWeight::updateOrCreate(
                    ['symbol' => $symbol, 'timeframe' => $timeframe],
                    [
                        'weights' => $data['weights'],
                        'accuracy' => $data['accuracy'] ?? 0,
                        'total_trades' => $data['total_trades'] ?? 0
                    ]
                );
                $this->info("✅ AI Weights synchronized.");
            } else {
                $this->warn("⚠️ No weights data in response.");
            }
        } else {
            $this->error("❌ Failed to pull weights: " . $response->status());
        }

        // 2. Pull History (Optional)
        if ($this->confirm('Do you want to pull historical experience too? (This may take a while)', false)) {
            $response = Http::withHeaders([
                'X-Trading-Token' => config('services.trading.sync_token')
            ])->get("https://climasdeldesierto.com/trading/get-history", [
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'limit' => 500
            ]);

            if ($response->successful()) {
                $history = $response->json();
                foreach ($history as $row) {
                    TradingExperience::updateOrCreate(
                        ['symbol' => $symbol, 'timeframe' => $timeframe, 'timestamp' => $row[0] / 1000],
                        [
                            'open' => $row[1],
                            'high' => $row[2],
                            'low' => $row[3],
                            'close' => $row[4],
                            'volume' => $row[5],
                            'market_regime' => 'sync_from_prod'
                        ]
                    );
                }
                $this->info("✅ Experience history synchronized.");
            }
        }

        $this->info("🚀 Local Cerebro IA is now up to date with production.");
    }
}
