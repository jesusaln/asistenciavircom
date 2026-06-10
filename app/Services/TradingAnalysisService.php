<?php

namespace App\Services;

class TradingAnalysisService
{
    public function getTradingConfig(): array
    {
        return [
            'atr_period' => (int) config('services.trading.atr_period', 14),
            'stop_loss_atr_multiplier' => (float) config('services.trading.stop_loss_atr_multiplier', 1.8),
            'trailing_stop_atr_multiplier' => (float) config('services.trading.trailing_stop_atr_multiplier', 1.35),
            'trailing_activation_atr_multiplier' => (float) config('services.trading.trailing_activation_atr_multiplier', 1.1),
        ];
    }

    /**
     * Calcula la Media Móvil Exponencial (EMA)
     */
    public function calculateEMA(array $prices, int $period): array
    {
        if (count($prices) < $period) return [];

        $ema = [];
        $multiplier = 2 / ($period + 1);

        // Primer valor es un SMA simple
        $initialSma = array_sum(array_slice($prices, 0, $period)) / $period;
        $ema[$period - 1] = $initialSma;

        for ($i = $period; $i < count($prices); $i++) {
            $ema[$i] = ($prices[$i] - $ema[$i - 1]) * $multiplier + $ema[$i - 1];
        }

        return $ema;
    }

    /**
     * Calcula el Relative Strength Index (RSI)
     */
    public function calculateRSI(array $prices, int $period = 14): array
    {
        if (count($prices) <= $period) return [];

        $rsi = [];
        $gains = [];
        $losses = [];

        for ($i = 1; $i < count($prices); $i++) {
            $diff = $prices[$i] - $prices[$i - 1];
            $gains[] = $diff > 0 ? $diff : 0;
            $losses[] = $diff < 0 ? abs($diff) : 0;
        }

        $avgGain = array_sum(array_slice($gains, 0, $period)) / $period;
        $avgLoss = array_sum(array_slice($losses, 0, $period)) / $period;

        if ($avgLoss == 0) {
            $rsi[$period] = 100;
        } else {
            $rs = $avgGain / $avgLoss;
            $rsi[$period] = 100 - (100 / (1 + $rs));
        }

        for ($i = $period + 1; $i < count($prices); $i++) {
            $currentGain = $gains[$i - 1];
            $currentLoss = $losses[$i - 1];

            $avgGain = (($avgGain * ($period - 1)) + $currentGain) / $period;
            $avgLoss = (($avgLoss * ($period - 1)) + $currentLoss) / $period;

            if ($avgLoss == 0) {
                $rsi[$i] = 100;
            } else {
                $rs = $avgGain / $avgLoss;
                $rsi[$i] = 100 - (100 / (1 + $rs));
            }
        }

        return $rsi;
    }

    /**
     * Calcula las Bandas de Bollinger
     */
    public function calculateBollingerBands(array $prices, int $period = 20, float $stdDev = 2.0): array
    {
        if (count($prices) < $period) return [];

        $bands = [];

        for ($i = $period - 1; $i < count($prices); $i++) {
            $slice = array_slice($prices, $i - $period + 1, $period);
            $middle = array_sum($slice) / $period;

            $variance = 0;
            foreach ($slice as $p) {
                $variance += pow($p - $middle, 2);
            }
            $std = sqrt($variance / $period);

            $bands[$i] = [
                'middle' => $middle,
                'upper' => $middle + ($stdDev * $std),
                'lower' => $middle - ($stdDev * $std)
            ];
        }

        return $bands;
    }

    /**
     * Calcula el Average True Range (ATR) para Stop Loss dinámico
     */
    public function calculateATR(array $candles, ?int $period = null): float
    {
        $period ??= $this->getTradingConfig()['atr_period'];

        if (count($candles) <= $period) return 0.015; // 1.5% default

        $trueRanges = [];
        for ($i = 1; $i < count($candles); $i++) {
            $high = (float)$candles[$i]['high'];
            $low = (float)$candles[$i]['low'];
            $prevClose = (float)$candles[$i - 1]['close'];

            $tr = max(
                $high - $low,
                abs($high - $prevClose),
                abs($low - $prevClose)
            );
            $trueRanges[] = $tr;
        }

        $atr = array_sum(array_slice($trueRanges, -$period)) / $period;
        $currentClose = (float)$candles[count($candles) - 1]['close'];
        
        return $atr / $currentClose;
    }

    public function calculateMacroTrend(array $candles, int $emaPeriod = 50): array
    {
        if (count($candles) < max(20, $emaPeriod)) {
            return [
                'is_bullish' => true,
                'ema' => null,
                'close' => null,
                'label' => 'insufficient_data',
            ];
        }

        $closes = array_map(fn($c) => (float) $c['close'], $candles);
        $ema = $this->calculateEMA($closes, $emaPeriod);
        $lastIndex = count($closes) - 1;
        $emaValue = $ema[$lastIndex] ?? null;
        $close = $closes[$lastIndex];
        $isBullish = $emaValue === null ? true : $close >= $emaValue;

        return [
            'is_bullish' => $isBullish,
            'ema' => $emaValue,
            'close' => $close,
            'label' => $isBullish ? 'bullish' : 'bearish',
        ];
    }

    public function buildAdaptiveRiskProfile(array $candles, float $entryPrice, ?array $activePosition = null): array
    {
        $config = $this->getTradingConfig();
        $currentCandle = end($candles) ?: null;
        $currentPrice = $currentCandle ? (float) $currentCandle['close'] : $entryPrice;
        $atrPercent = $this->calculateATR($candles, $config['atr_period']);
        $atrValue = max($entryPrice * $atrPercent, 0.00000001);

        $initialStop = $entryPrice - ($atrValue * $config['stop_loss_atr_multiplier']);
        $highestPrice = max(
            $entryPrice,
            $currentPrice,
            (float) ($activePosition['highest_price'] ?? 0)
        );

        $activationPrice = $entryPrice + ($atrValue * $config['trailing_activation_atr_multiplier']);
        $shouldActivateTrailing = $highestPrice >= $activationPrice;
        $trailingStop = null;

        if ($shouldActivateTrailing) {
            $trailingStop = $highestPrice - ($atrValue * $config['trailing_stop_atr_multiplier']);
        }

        $effectiveStop = $initialStop;
        if ($trailingStop !== null) {
            $effectiveStop = max($effectiveStop, $trailingStop);
        }

        $pnlPercent = $entryPrice > 0
            ? (($currentPrice - $entryPrice) / $entryPrice) * 100
            : 0.0;

        return [
            'entry_price' => $entryPrice,
            'current_price' => $currentPrice,
            'atr_percent' => $atrPercent,
            'atr_value' => $atrValue,
            'initial_stop_loss' => $initialStop,
            'stop_loss' => $effectiveStop,
            'trailing_stop' => $trailingStop,
            'trailing_active' => $shouldActivateTrailing,
            'highest_price' => $highestPrice,
            'activation_price' => $activationPrice,
            'pnl_percent' => $pnlPercent,
            'should_close' => $currentPrice <= $effectiveStop,
        ];
    }

    public function summarizeIndicators(array $candles, bool $isBullishMacro = true): array
    {
        $closes = array_map(fn($c) => (float) $c['close'], $candles);
        $volumes = array_map(fn($c) => (float) $c['volume'], $candles);
        $lastIndex = count($candles) - 1;

        $emaFast = $this->calculateEMA($closes, 9);
        $emaSlow = $this->calculateEMA($closes, 21);
        $rsi = $this->calculateRSI($closes, 14);
        $atrPercent = $this->calculateATR($candles);

        $avgVolume = count($volumes) >= 20
            ? array_sum(array_slice($volumes, -20)) / 20
            : 0.0;
        $currentVolume = $volumes[$lastIndex] ?? 0.0;

        $highs = array_map(fn($c) => (float) $c['high'], array_slice($candles, -30));
        $recentResistance = !empty($highs) ? max($highs) : null;
        $currentClose = (float) ($closes[$lastIndex] ?? 0.0);

        return [
            'ema_fast' => $emaFast[$lastIndex] ?? null,
            'ema_slow' => $emaSlow[$lastIndex] ?? null,
            'rsi' => $rsi[$lastIndex] ?? null,
            'atr_percent' => $atrPercent,
            'volume_ratio' => $avgVolume > 0 ? ($currentVolume / $avgVolume) : null,
            'recent_resistance' => $recentResistance,
            'macro_bias' => $isBullishMacro ? 'bullish' : 'bearish',
        ];
    }

    /**
     * Genera la señal final de Trading Mejorada con 3 Escudos
     */
    public function analyzeSignal(array $candles, bool $isBullishMacro = true): string
    {
        if (count($candles) < 30) return 'wait';

        $closes = array_map(fn($c) => (float)$c['close'], $candles);

        $emaFast = $this->calculateEMA($closes, 9);
        $emaSlow = $this->calculateEMA($closes, 21);
        $rsi = $this->calculateRSI($closes, 14);

        $lastIndex = count($closes) - 1;

        if (!isset($emaFast[$lastIndex], $emaSlow[$lastIndex], $rsi[$lastIndex])) {
            return 'wait';
        }

        $isBullishEMA = $emaFast[$lastIndex] > $emaSlow[$lastIndex];
        $currentRSI = $rsi[$lastIndex];

        // Escudo 1: Confirmación de Volumen (Mayor al promedio de 20 velas)
        $volumes = array_map(fn($c) => (float)$c['volume'], $candles);
        $avgVolume = array_sum(array_slice($volumes, -21, 20)) / 20;
        $isValidVolume = (float)$candles[$lastIndex]['volume'] > ($avgVolume * 1.15);

        // Escudo 3: Zonas de Soporte y Resistencia (Evitar comprar cerca de techos)
        $highs = array_map(fn($c) => (float)$c['high'], array_slice($candles, -30));
        $recentResistance = max($highs);
        $currentClose = (float)$candles[$lastIndex]['close'];
        $isSafeToBuy = (($recentResistance - $currentClose) / $currentClose) > 0.005; // Margen de seguridad > 0.5%

        // Señal de COMPRA con Filtros Blindados
        if ($isBullishEMA && $currentRSI < 50 && $isBullishMacro && $isValidVolume && $isSafeToBuy) {
            return 'buy';
        }

        // Señal de VENTA / CIERRE
        if ($currentRSI > 70 || (!$isBullishMacro && $currentRSI > 55)) {
            return 'sell';
        }

        return 'wait';
    }
}
