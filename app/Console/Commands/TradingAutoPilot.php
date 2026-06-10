<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TradingExperience;
use App\Services\BinanceOrderService;
use App\Services\TradingAnalysisService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TradingAutoPilot extends Command
{
    protected $signature = 'trading:auto-pilot {--symbol=BTCUSDT} {--timeframe=15m} {--macro-timeframe=}';
    protected $description = 'Motor de trading perpetuo para ejecución algorítmica 24/7';

    public function handle()
    {
        $symbol = $this->option('symbol');
        $timeframe = $this->option('timeframe');
        $macroTimeframe = $this->option('macro-timeframe') ?: config('services.trading.macro_timeframe', '4h');
        $orderAmount = (float) config('services.trading.default_order_amount', 100);
        
        $this->info("🚀 Iniciando Piloto Automático para {$symbol} ({$timeframe})");
        Log::info("AutoPilot: Iniciado para {$symbol}");

        $analysisService = new TradingAnalysisService();
        $binanceService = new BinanceOrderService(1); // Mapeado a Jesus Lopez

        while (true) {
            try {
                $this->line("[" . now()->toDateTimeString() . "] Escaneando mercado...");
                
                $macroTrend = $this->fetchMacroTrend($symbol, $macroTimeframe, $analysisService);
                $isBullishMacro = $macroTrend['is_bullish'];
                $this->line("Macro Tendencia {$macroTimeframe}: " . ($isBullishMacro ? 'ALCISTA 🟢' : 'BAJISTA 🔴'));

                // 1. Obtener velas operativas
                $url = "https://api.binance.com/api/v3/klines?symbol={$symbol}&interval={$timeframe}&limit=100";
                $response = Http::get($url);
                
                if ($response->ok()) {
                    $candles = $this->mapCandles($response->json());
                    $latestClose = (float) end($candles)['close'];

                    // 2. Ejecutar análisis técnico blindado
                    $signal = $analysisService->analyzeSignal($candles, $isBullishMacro);
                    $indicatorState = $analysisService->summarizeIndicators($candles, $isBullishMacro);
                    $this->info("Resultado del Análisis: " . strtoupper($signal));

                    // 3. Evaluar Ejecución
                    if ($signal === 'buy') {
                        $activePosition = \Illuminate\Support\Facades\Cache::get("active_autopilot_trade_{$symbol}");
                        
                        if (!$activePosition) {
                            $this->info("🎯 Señal COMPRA encontrada. Ejecutando orden Spot...");
                            $order = $binanceService->executeMarketOrder($symbol, 'BUY', $orderAmount);
                            
                            if (isset($order['orderId'])) {
                                $riskProfile = $analysisService->buildAdaptiveRiskProfile($candles, $latestClose);
                                $positionState = [
                                    'orderId' => $order['orderId'],
                                    'entryPrice' => $latestClose,
                                    'amount' => $orderAmount,
                                    'opened_at' => now()->timestamp,
                                    'macro_timeframe' => $macroTimeframe,
                                    'macro_trend' => $macroTrend['label'],
                                    'atr_percent' => $riskProfile['atr_percent'],
                                    'atr_value' => $riskProfile['atr_value'],
                                    'stop_loss' => $riskProfile['stop_loss'],
                                    'trailing_stop' => $riskProfile['trailing_stop'],
                                    'highest_price' => $riskProfile['highest_price'],
                                    'trailing_active' => $riskProfile['trailing_active'],
                                ];

                                \Illuminate\Support\Facades\Cache::put("active_autopilot_trade_{$symbol}", $positionState, 18000);
                                Log::info("AutoPilot: COMPRA exitosa", ['order' => $order]);
                            } else {
                                Log::error("AutoPilot: Error al ejecutar orden", ['response' => $order]);
                            }
                        }
                    } 

                    // 4. Monitorear Stop Loss y Trailing Stop adaptativo
                    $activePosition = \Illuminate\Support\Facades\Cache::get("active_autopilot_trade_{$symbol}");
                    if ($activePosition) {
                        $entryPrice = (float)$activePosition['entryPrice'];
                        $riskProfile = $analysisService->buildAdaptiveRiskProfile($candles, $entryPrice, $activePosition);
                        $activePosition['atr_percent'] = $riskProfile['atr_percent'];
                        $activePosition['atr_value'] = $riskProfile['atr_value'];
                        $activePosition['stop_loss'] = $riskProfile['stop_loss'];
                        $activePosition['trailing_stop'] = $riskProfile['trailing_stop'];
                        $activePosition['highest_price'] = $riskProfile['highest_price'];
                        $activePosition['trailing_active'] = $riskProfile['trailing_active'];
                        \Illuminate\Support\Facades\Cache::put("active_autopilot_trade_{$symbol}", $activePosition, 18000);

                        if ($riskProfile['trailing_active']) {
                            $this->line('Trailing Stop activo en: ' . round($riskProfile['trailing_stop'], 4));
                        } else {
                            $this->line('Stop Loss ATR activo en: ' . round($riskProfile['stop_loss'], 4));
                        }

                        $shouldClose = $riskProfile['should_close'] || $signal === 'sell';
                        if ($riskProfile['should_close']) {
                            $this->info("🛑 Stop dinámico ejecutado (" . round($riskProfile['pnl_percent'], 2) . "%)");
                        } elseif ($signal === 'sell') {
                            $this->info("📉 Salida por reversión de señal");
                        }

                        if ($shouldClose) {
                            $this->info("🎯 Ejecutando orden de venta de protección...");
                            $binanceService->executeMarketOrder($symbol, 'SELL', $activePosition['amount']);
                            \Illuminate\Support\Facades\Cache::forget("active_autopilot_trade_{$symbol}");
                            $activePosition = null;
                        }
                    }

                    $this->persistExperienceSnapshot(
                        $symbol,
                        $timeframe,
                        $candles,
                        $indicatorState,
                        $macroTrend,
                        $activePosition,
                        $signal
                    );
                } else {
                    $this->error("Error al conectar con la API de Binance");
                }

            } catch (\Exception $e) {
                $this->error("Error fatal en el ciclo: " . $e->getMessage());
                Log::error("AutoPilot Exception", ['msg' => $e->getMessage()]);
            }

            // Pausar 5 segundos para monitoreo de alta frecuencia
            $this->line("Esperando 5 segundos para el siguiente bloque...");
            sleep(5);
        }
    }

    private function mapCandles(array $rows): array
    {
        return array_map(function ($r) {
            return [
                'time' => $r[0],
                'open' => $r[1],
                'high' => $r[2],
                'low' => $r[3],
                'close' => $r[4],
                'volume' => $r[5],
            ];
        }, $rows);
    }

    private function fetchMacroTrend(string $symbol, string $macroTimeframe, TradingAnalysisService $analysisService): array
    {
        $response = Http::get("https://api.binance.com/api/v3/klines?symbol={$symbol}&interval={$macroTimeframe}&limit=80");
        if (!$response->ok()) {
            return [
                'is_bullish' => true,
                'label' => 'unknown',
                'ema' => null,
                'close' => null,
            ];
        }

        return $analysisService->calculateMacroTrend($this->mapCandles($response->json()));
    }

    private function persistExperienceSnapshot(
        string $symbol,
        string $timeframe,
        array $candles,
        array $indicatorState,
        array $macroTrend,
        ?array $activePosition,
        string $signal
    ): void {
        $lastCandle = end($candles);
        if (!$lastCandle) {
            return;
        }

        TradingExperience::updateOrCreate(
            [
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'timestamp' => (int) ($lastCandle['time'] / 1000),
            ],
            [
                'open' => $lastCandle['open'],
                'high' => $lastCandle['high'],
                'low' => $lastCandle['low'],
                'close' => $lastCandle['close'],
                'volume' => $lastCandle['volume'],
                'indicators_state' => $indicatorState,
                'market_regime' => $macroTrend['label'] ?? 'unknown',
                'signal' => $signal,
                'atr_percent' => $indicatorState['atr_percent'] ?? null,
                'atr_value' => $activePosition['atr_value'] ?? null,
                'macro_timeframe' => $activePosition['macro_timeframe'] ?? config('services.trading.macro_timeframe', '4h'),
                'macro_trend' => $macroTrend['label'] ?? 'unknown',
                'stop_loss' => $activePosition['stop_loss'] ?? null,
                'trailing_stop' => $activePosition['trailing_stop'] ?? null,
                'highest_price' => $activePosition['highest_price'] ?? null,
            ]
        );
    }
}
