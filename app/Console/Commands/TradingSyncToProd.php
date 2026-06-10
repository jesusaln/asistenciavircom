<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\TradingExperience;

class TradingSyncToProd extends Command
{
    protected $signature = 'trading:sync-to-prod {symbol=BTCUSDT} {timeframe=15m}';
    protected $description = 'Sync local trading experience to production server';

    public function handle()
    {
        $symbol = $this->argument('symbol');
        $timeframe = $this->argument('timeframe');
        
        $this->info("🔗 Syncing experience for $symbol ($timeframe) to production...");

        $records = TradingExperience::where('symbol', $symbol)
            ->where('timeframe', $timeframe)
            ->get();

        $total = $records->count();
        $this->info("Found $total records to sync.");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $chunks = $records->chunk(100);

        foreach ($chunks as $chunk) {
            $data = $chunk->map(function ($r) {
                return $r->toArray();
            })->values()->all();

            $response = Http::withHeaders([
                'X-Requested-With' => 'XMLHttpRequest',
                'X-Trading-Token' => config('services.trading.sync_token')
            ])->post("https://climasdeldesierto.com/trading/bulk-save-experience", [
                'experiences' => $data
            ]);

            if (!$response->successful()) {
                $this->error("\nFailed to sync chunk. Status: " . $response->status());
                $this->error($response->body());
                break;
            }

            $bar->advance($chunk->count());
        }

        $bar->finish();
        $this->info("\n✅ Sync complete! Production now has all local memories.");
    }
}
