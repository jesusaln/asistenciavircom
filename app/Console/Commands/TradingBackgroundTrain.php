<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TradingExperience;
use App\Models\TradingWeight;

class TradingBackgroundTrain extends Command
{
    protected $signature = 'trading:background-train {symbol=BTCUSDT} {timeframe=15m}';
    protected $description = 'Analyze stored market data, run backtesting, and evolve AI weights';

    public function handle()
    {
        $symbol = $this->argument('symbol');
        $timeframe = $this->argument('timeframe');
        
        $this->info("🧠 AI Training: {$symbol} ({$timeframe})");

        // 1. Load candles from internal database
        $candles = TradingExperience::where('symbol', $symbol)
            ->where('timeframe', $timeframe)
            ->orderBy('timestamp', 'asc')
            ->get()
            ->map(fn($r) => [
                'time'   => $r->timestamp,
                'open'   => (float) $r->open,
                'high'   => (float) $r->high,
                'low'    => (float) $r->low,
                'close'  => (float) $r->close,
                'volume' => (float) $r->volume,
            ])
            ->values()
            ->all();

        $count = count($candles);
        $this->info("📊 Loaded {$count} candles from database.");

        if ($count < 200) {
            $this->warn("⚠️  Need at least 200 candles for meaningful training. Aborting.");
            return;
        }

        // 2. Calculate indicators for each candle
        $this->info("📐 Calculating indicators (EMA, RSI, MACD, BB)...");
        $enriched = $this->calculateIndicators($candles);

        // 3. Run genetic optimization — test multiple weight combinations
        $this->info("🧬 Running genetic optimization (50 generations, 20 individuals)...");
        $bestWeights = $this->geneticOptimize($enriched, generations: 50, population: 20);

        // 4. Backtest with best weights to get real accuracy
        $result = $this->backtest($enriched, $bestWeights);

        $this->info("📈 Backtest results:");
        $this->info("   Trades: {$result['trades']}");
        $this->info("   Wins:   {$result['wins']}");
        $this->info("   Win%:   " . round($result['accuracy'], 1) . "%");
        $this->info("   PnL:    " . round($result['pnl'], 2) . "%");

        // 5. Persist to database
        TradingWeight::updateOrCreate(
            ['symbol' => $symbol, 'timeframe' => $timeframe],
            [
                'weights'      => $bestWeights,
                'accuracy'     => round($result['accuracy'], 1),
                'total_trades' => $result['trades'],
            ]
        );

        // 6. Update experience records with calculated indicators
        $this->updateExperienceIndicators($enriched, $symbol, $timeframe, $bestWeights);

        $this->info("✅ Intelligence persisted. AI is now smarter.");
    }

    /**
     * Calculate technical indicators for each candle.
     */
    private function calculateIndicators(array $candles): array
    {
        $closes = array_column($candles, 'close');
        $volumes = array_column($candles, 'volume');
        $n = count($candles);

        // Pre-calculate arrays
        $emaFast = $this->ema($closes, 9);
        $emaSlow = $this->ema($closes, 21);
        $rsi = $this->rsi($closes, 14);
        $macd = $this->macd($closes);
        $bb = $this->bollingerBands($closes, 20, 2);
        $avgVolume = $this->sma($volumes, 20);

        $enriched = [];
        for ($i = 0; $i < $n; $i++) {
            $candles[$i]['indicators'] = [
                'ema'      => isset($emaFast[$i], $emaSlow[$i]) ? ($emaFast[$i] > $emaSlow[$i] ? 1 : -1) : 0,
                'rsi'      => $rsi[$i] ?? 50,
                'macd'     => $macd[$i] ?? 0,
                'bb'       => isset($bb[$i]) ? $bb[$i] : 0,
                'volume'   => isset($avgVolume[$i]) && $avgVolume[$i] > 0
                    ? ($volumes[$i] / $avgVolume[$i]) - 1
                    : 0,
                'momentum' => $i >= 5 ? ($closes[$i] - $closes[$i - 5]) / $closes[$i - 5] : 0,
            ];
            $enriched[] = $candles[$i];
        }

        return $enriched;
    }

    /**
     * Simple genetic optimizer: evolves weight sets through tournament selection.
     */
    private function geneticOptimize(array $candles, int $generations, int $population): array
    {
        // Seed initial population with random weights
        $pop = [];
        for ($i = 0; $i < $population; $i++) {
            $pop[] = $this->randomWeights();
        }

        for ($gen = 0; $gen < $generations; $gen++) {
            // Evaluate fitness of each individual
            $scored = [];
            foreach ($pop as $weights) {
                $r = $this->backtest($candles, $weights);
                $fitness = ($r['accuracy'] * 0.6) + (max(0, $r['pnl']) * 0.4);
                $scored[] = ['weights' => $weights, 'fitness' => $fitness];
            }

            usort($scored, fn($a, $b) => $b['fitness'] <=> $a['fitness']);

            // Keep top 40%, breed the rest
            $elite = array_slice($scored, 0, (int)($population * 0.4));
            $newPop = array_map(fn($s) => $s['weights'], $elite);

            while (count($newPop) < $population) {
                $p1 = $elite[array_rand($elite)]['weights'];
                $p2 = $elite[array_rand($elite)]['weights'];
                $child = $this->crossover($p1, $p2);
                $child = $this->mutate($child, rate: 0.15);
                $newPop[] = $child;
            }

            $pop = $newPop;
        }

        // Return the best weights from the final generation
        $best = null;
        $bestFitness = -INF;
        foreach ($pop as $weights) {
            $r = $this->backtest($candles, $weights);
            $fitness = ($r['accuracy'] * 0.6) + (max(0, $r['pnl']) * 0.4);
            if ($fitness > $bestFitness) {
                $bestFitness = $fitness;
                $best = $weights;
            }
        }

        return $best;
    }

    private function randomWeights(): array
    {
        $w = [
            'ema_weight'    => mt_rand(10, 60) / 100,
            'rsi_weight'    => mt_rand(5, 40) / 100,
            'bb_weight'     => mt_rand(5, 30) / 100,
            'macd_weight'   => mt_rand(5, 30) / 100,
            'volume_weight' => mt_rand(1, 20) / 100,
        ];
        // Normalize to sum = 1
        $sum = array_sum($w);
        return array_map(fn($v) => round($v / $sum, 4), $w);
    }

    private function crossover(array $p1, array $p2): array
    {
        $child = [];
        foreach ($p1 as $key => $val) {
            $child[$key] = mt_rand(0, 1) ? $val : $p2[$key];
        }
        $sum = array_sum($child);
        return $sum > 0 ? array_map(fn($v) => round($v / $sum, 4), $child) : $p1;
    }

    private function mutate(array $w, float $rate): array
    {
        foreach ($w as $key => &$val) {
            if (mt_rand(1, 100) / 100 < $rate) {
                $val = max(0.01, $val + (mt_rand(-20, 20) / 100));
            }
        }
        $sum = array_sum($w);
        return $sum > 0 ? array_map(fn($v) => round($v / $sum, 4), $w) : $w;
    }

    /**
     * Run a backtest against enriched candle data using given weights.
     */
    private function backtest(array $candles, array $weights): array
    {
        $trades = 0;
        $wins = 0;
        $totalPnl = 0;
        $n = count($candles);

        for ($i = 50; $i < $n - 5; $i++) {
            $ind = $candles[$i]['indicators'] ?? null;
            if (!$ind) continue;

            // Calculate weighted signal score
            $score = 0;
            $score += ($ind['ema'] ?? 0) * ($weights['ema_weight'] ?? 0.3);
            $score += (($ind['rsi'] ?? 50) > 50 ? 1 : -1) * ($weights['rsi_weight'] ?? 0.2);
            $score += ($ind['macd'] ?? 0) > 0 ? ($weights['macd_weight'] ?? 0.15) : -($weights['macd_weight'] ?? 0.15);
            $score += ($ind['bb'] ?? 0) * ($weights['bb_weight'] ?? 0.1);
            $score += ($ind['volume'] ?? 0) > 0.3 ? ($weights['volume_weight'] ?? 0.05) : 0;

            // Only trade on strong signals
            if (abs($score) < 0.3) continue;

            $direction = $score > 0 ? 'long' : 'short';

            // Look ahead 5 candles for outcome
            $entryPrice = $candles[$i]['close'];
            $exitPrice = $candles[min($i + 5, $n - 1)]['close'];

            if ($direction === 'long') {
                $pnl = (($exitPrice - $entryPrice) / $entryPrice) * 100;
            } else {
                $pnl = (($entryPrice - $exitPrice) / $entryPrice) * 100;
            }

            $trades++;
            $totalPnl += $pnl;
            if ($pnl > 0) $wins++;

            $i += 5; // Skip forward to avoid overlapping trades
        }

        return [
            'trades'   => $trades,
            'wins'     => $wins,
            'accuracy' => $trades > 0 ? ($wins / $trades) * 100 : 0,
            'pnl'      => $totalPnl,
        ];
    }

    /**
     * Update experience records with calculated indicators and signal.
     */
    private function updateExperienceIndicators(array $enriched, string $symbol, string $timeframe, array $weights): void
    {
        $batch = 0;
        foreach ($enriched as $candle) {
            if (!isset($candle['indicators'])) continue;
            
            $ind = $candle['indicators'];
            $score = 0;
            $score += ($ind['ema'] ?? 0) * ($weights['ema_weight'] ?? 0.3);
            $score += (($ind['rsi'] ?? 50) > 50 ? 1 : -1) * ($weights['rsi_weight'] ?? 0.2);
            $score += ($ind['macd'] ?? 0) > 0 ? ($weights['macd_weight'] ?? 0.15) : -($weights['macd_weight'] ?? 0.15);

            $signal = abs($score) < 0.3 ? 'wait' : ($score > 0 ? 'buy' : 'sell');
            $confidence = min(1.0, abs($score));

            TradingExperience::where('symbol', $symbol)
                ->where('timeframe', $timeframe)
                ->where('timestamp', $candle['time'])
                ->update([
                    'indicators_state' => json_encode($ind),
                    'signal'           => $signal,
                    'ai_confidence'    => round($confidence, 2),
                    'market_regime'    => $this->detectRegime($ind),
                ]);

            $batch++;
        }

        $this->info("📝 Updated {$batch} experience records with indicators.");
    }

    private function detectRegime(array $ind): string
    {
        $vol = abs($ind['momentum'] ?? 0);
        if ($vol > 0.02) return 'high_volatility';
        if ($vol < 0.005) return 'low_volatility';
        return $ind['ema'] > 0 ? 'trending_up' : 'trending_down';
    }

    // ─── Technical Indicator Functions ───────────────────────────

    private function ema(array $data, int $period): array
    {
        $result = [];
        $k = 2 / ($period + 1);
        $prev = null;

        foreach ($data as $i => $val) {
            if ($i < $period - 1) {
                $result[$i] = null;
                continue;
            }
            if ($prev === null) {
                $prev = array_sum(array_slice($data, 0, $period)) / $period;
            }
            $prev = ($val * $k) + ($prev * (1 - $k));
            $result[$i] = $prev;
        }

        return $result;
    }

    private function sma(array $data, int $period): array
    {
        $result = [];
        for ($i = 0; $i < count($data); $i++) {
            if ($i < $period - 1) {
                $result[$i] = null;
                continue;
            }
            $slice = array_slice($data, $i - $period + 1, $period);
            $result[$i] = array_sum($slice) / $period;
        }
        return $result;
    }

    private function rsi(array $data, int $period): array
    {
        $result = [];
        $gains = [];
        $losses = [];

        for ($i = 0; $i < count($data); $i++) {
            if ($i === 0) {
                $result[$i] = 50;
                continue;
            }

            $change = $data[$i] - $data[$i - 1];
            $gains[] = max(0, $change);
            $losses[] = max(0, -$change);

            if (count($gains) < $period) {
                $result[$i] = 50;
                continue;
            }

            $recentGains = array_slice($gains, -$period);
            $recentLosses = array_slice($losses, -$period);

            $avgGain = array_sum($recentGains) / $period;
            $avgLoss = array_sum($recentLosses) / $period;

            if ($avgLoss == 0) {
                $result[$i] = 100;
            } else {
                $rs = $avgGain / $avgLoss;
                $result[$i] = 100 - (100 / (1 + $rs));
            }
        }

        return $result;
    }

    private function macd(array $data): array
    {
        $ema12 = $this->ema($data, 12);
        $ema26 = $this->ema($data, 26);
        $result = [];

        for ($i = 0; $i < count($data); $i++) {
            if (isset($ema12[$i], $ema26[$i]) && $ema12[$i] !== null && $ema26[$i] !== null) {
                $result[$i] = $ema12[$i] - $ema26[$i];
            } else {
                $result[$i] = 0;
            }
        }

        return $result;
    }

    private function bollingerBands(array $data, int $period, float $stdDev): array
    {
        $sma = $this->sma($data, $period);
        $result = [];

        for ($i = 0; $i < count($data); $i++) {
            if ($sma[$i] === null) {
                $result[$i] = 0;
                continue;
            }

            $slice = array_slice($data, max(0, $i - $period + 1), $period);
            $mean = array_sum($slice) / count($slice);
            $variance = array_sum(array_map(fn($v) => pow($v - $mean, 2), $slice)) / count($slice);
            $sd = sqrt($variance);

            $upper = $sma[$i] + ($sd * $stdDev);
            $lower = $sma[$i] - ($sd * $stdDev);

            // Position relative to bands: -1 = near lower, +1 = near upper, 0 = middle
            $bandwidth = $upper - $lower;
            if ($bandwidth > 0) {
                $result[$i] = (($data[$i] - $lower) / $bandwidth) * 2 - 1;
            } else {
                $result[$i] = 0;
            }
        }

        return $result;
    }
}
