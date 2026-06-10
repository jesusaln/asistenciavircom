<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\TradingExperience;
use Carbon\Carbon;

class TradingHistoryInjector extends Command
{
    protected $signature = 'trading:inject-history {symbol=BTCUSDT} {timeframe=15m} {days=30}';
    protected $description = 'Inject historical data from Binance into the AI experience table';

    public function handle()
    {
        $symbol = $this->argument('symbol');
        $timeframe = $this->argument('timeframe');
        $days = (int) $this->argument('days');

        $this->info("🚀 Injecting $days days of history for $symbol ($timeframe)...");

        $endTime = now()->timestamp * 1000;
        $startTime = now()->subDays($days)->timestamp * 1000;
        
        $allKlines = [];
        $currentStartTime = $startTime;

        $bar = $this->output->createProgressBar($days);
        $bar->start();

        while ($currentStartTime < $endTime) {
            $response = Http::get("https://api.binance.com/api/v3/klines", [
                'symbol' => $symbol,
                'interval' => $timeframe,
                'startTime' => $currentStartTime,
                'limit' => 1000
            ]);

            if (!$response->successful()) {
                $this->error("\nFailed to fetch batch from Binance");
                break;
            }

            $klines = $response->json();
            if (empty($klines)) break;

            foreach ($klines as $k) {
                $ts = $k[0] / 1000;
                
                // Simple injection (Indicators will be calculated by the trainer later)
                TradingExperience::updateOrCreate(
                    ['symbol' => $symbol, 'timeframe' => $timeframe, 'timestamp' => $ts],
                    [
                        'open' => $k[1],
                        'high' => $k[2],
                        'low' => $k[3],
                        'close' => $k[4],
                        'volume' => $k[5],
                        'market_regime' => 'historical_injection'
                    ]
                );
            }

            $lastTs = end($klines)[0];
            $currentStartTime = $lastTs + 1;
            $bar->advance();
            
            // Safety sleep to avoid rate limits
            usleep(100000); 
        }

        $bar->finish();
        $this->info("\n✅ Injection complete! Your AI now has $days days of new memories.");
    }
}
