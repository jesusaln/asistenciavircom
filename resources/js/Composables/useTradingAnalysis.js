import { 
    calculateEMA, calculateBollingerBands, calculateRSI, calculateATR 
} from '@/Composables/useTradingMath.js';

export class BayesianConfidence {
    constructor() {
        this.prior = { win: 1, loss: 1 };
        this.recentPerformance = [];
    }
    update(result) {
        if (result) this.prior.win++;
        else this.prior.loss++;
        this.recentPerformance.push(result);
        if (this.recentPerformance.length > 20) this.recentPerformance.shift();
    }
    getCredibility() {
        const expected = this.prior.win / (this.prior.win + this.prior.loss);
        const recent = this.recentPerformance.length === 0 ? 0.5 : 
                        this.recentPerformance.filter(r => r).length / this.recentPerformance.length;
        const recencyWeight = Math.min(0.4, this.recentPerformance.length / 50);
        const combined = expected * (1 - recencyWeight) + recent * recencyWeight;
        const sampleConfidence = Math.min(0.3, (this.prior.win + this.prior.loss) / 100);
        return Math.min(0.95, combined + sampleConfidence);
    }
}

export const getSignalContextFromCandles = (sourceCandles, params) => {
    const { strategyWeights, currentMarketRegime, isDeepTraining, detectLiquidityZones } = params;
    const closes = sourceCandles.map(candle => candle.close);
    const volumes = sourceCandles.map(c => c.volume);
    
    if (closes.length < 30) return null;

    const emaFastVal = calculateEMA(closes, 7)?.slice(-1)[0] || null;
    const emaSlowVal = calculateEMA(closes, 18)?.slice(-1)[0] || null;
    const rsi = calculateRSI(closes, 14);
    const bb = calculateBollingerBands(closes, 20);
    const atr = calculateATR(sourceCandles, 14);
    
    const macdFast = calculateEMA(closes, 12);
    const macdSlow = calculateEMA(closes, 26);
    const macdVal = (macdFast && macdSlow) ? macdFast[macdFast.length-1] - macdSlow[macdSlow.length-1] : 0;
    const macdSignal = macdVal > 0 ? 1 : -1;

    const avgVolume = volumes.slice(-20).reduce((a, b) => a + b, 0) / 20;
    const currentVolume = volumes[volumes.length - 1];
    const isHighVolume = currentVolume > avgVolume * 1.2;
    const isLowVolume = currentVolume < avgVolume * 0.6;

    const recentCloses = closes.slice(-5);
    const momentum = recentCloses.length > 1 
        ? ((recentCloses[recentCloses.length - 1] - recentCloses[0]) / recentCloses[0]) * 100 
        : 0;

    const indicators = {
        ema: emaFastVal && emaSlowVal ? (emaFastVal > emaSlowVal ? 1 : -1) : 0,
        rsi: rsi ? (rsi < 35 ? 1 : (rsi > 65 ? -1 : 0)) : 0,
        bb: bb ? (closes[closes.length-1] < bb.lower ? 1 : (closes[closes.length-1] > bb.upper ? -1 : 0)) : 0,
        volume: isHighVolume ? 1 : (isLowVolume ? -0.5 : 0),
        macd: macdSignal,
        momentum: Math.min(1, Math.max(-1, momentum / 3))
    };

    const continuousIndicators = {
        ema: emaFastVal && emaSlowVal ? (emaFastVal - emaSlowVal) / (emaSlowVal || 1) * 100 : 0,
        rsi: rsi ? (rsi - 50) / 50 : 0,
        bb: bb ? (closes[closes.length-1] - bb.lower) / ((bb.upper - bb.lower) || 1) : 0,
        volume: avgVolume ? (currentVolume - avgVolume) / avgVolume : 0,
        macd: macdVal ? macdVal / (closes[closes.length-1] || 1) * 100 : 0
    };

    const weights = strategyWeights.value || { ema: 0.2, rsi: 0.2, bb: 0.2, volume: 0.2, macd: 0.2 };
    const totalWeight = Object.values(weights).reduce((a, b) => a + b, 0);
    const normalizedWeights = {
        ema: (weights.ema || 0) / (totalWeight || 1),
        rsi: (weights.rsi || 0) / (totalWeight || 1),
        bb: (weights.bb || 0) / (totalWeight || 1),
        volume: (weights.volume || 0) / (totalWeight || 1),
        macd: (weights.macd || 0) / (totalWeight || 1)
    };

    const bias = (indicators.ema * normalizedWeights.ema) +
                 (indicators.rsi * normalizedWeights.rsi) +
                 (indicators.bb * normalizedWeights.bb) +
                 (indicators.volume * normalizedWeights.volume) +
                 (indicators.macd * normalizedWeights.macd);

    const regime = currentMarketRegime.value?.regime || 'ranging';
    let threshold = 0.55;
    let biasMultiplier = 1.0;
    
    if (regime === 'bull_trend' || regime === 'bear_trend') {
        biasMultiplier = 1.2;
        threshold = 0.45;
    } else if (regime === 'high_volatility') {
        biasMultiplier = 1.3;
        threshold = 0.5;
    } else if (regime === 'low_volatility') {
        biasMultiplier = 0.8;
        threshold = 0.6;
    }

    const finalBias = bias * biasMultiplier;
    let signal = 'wait';
    const dynamicThreshold = isDeepTraining.value ? 0.3 : threshold;
    
    if (finalBias > dynamicThreshold) signal = 'buy';
    else if (finalBias < -dynamicThreshold) signal = 'sell';

    const confidence = Math.min(0.95, Math.abs(finalBias) / 1.2);
    const liquidityZones = detectLiquidityZones(sourceCandles);
    const nearSupport = liquidityZones.support.some(s => Math.abs(s.price - closes[closes.length-1]) / closes[closes.length-1] < 0.005);
    const nearResistance = liquidityZones.resistance.some(r => Math.abs(r.price - closes[closes.length-1]) / closes[closes.length-1] < 0.005);

    return {
        signal,
        confidence,
        bias: finalBias,
        indicators,
        continuousIndicators,
        emaFast: emaFastVal,
        emaSlow: emaSlowVal,
        rsi,
        momentum,
        bb,
        isHighVolume,
        isLowVolume,
        nearSupport,
        nearResistance,
        atr,
        currentPrice: closes[closes.length - 1]
    };
};

export const classifyMarketRegime = (sourceCandles) => {
    if (sourceCandles.length < 50) return { regime: 'ranging', confidence: 0.5 };
    const closes = sourceCandles.map(c => c.close);
    const returns = [];
    for (let i = 1; i < closes.length; i++) {
        returns.push((closes[i] - closes[i-1]) / (closes[i-1] || 1));
    }
    const volatility = Math.sqrt(returns.reduce((sum, r) => sum + r*r, 0) / returns.length);
    const n = closes.length;
    const x = Array.from({ length: n }, (_, i) => i);
    const sumX = x.reduce((a, b) => a + b, 0);
    const sumY = closes.reduce((a, b) => a + b, 0);
    const sumXY = x.reduce((sum, xi, i) => sum + xi * closes[i], 0);
    const sumX2 = x.reduce((sum, xi) => sum + xi * xi, 0);
    const slope = (n * sumXY - sumX * sumY) / (n * sumX2 - sumX * sumX);
    const trendStrength = Math.abs(slope / (closes[0] || 1)) * 100;
    
    let autoCorr = 0;
    for (let i = 1; i < returns.length; i++) autoCorr += returns[i] * returns[i-1];
    autoCorr = autoCorr / (returns.length - 1) / (volatility * volatility || 0.0001);

    let regime = 'ranging';
    if (trendStrength > 0.5 && autoCorr > 0.3) regime = slope > 0 ? 'bull_trend' : 'bear_trend';
    else if (volatility > 0.015) regime = 'high_volatility';
    else if (volatility < 0.005) regime = 'low_volatility';

    return { regime, confidence: 0.7, trendStrength, volatility, autoCorr };
};

export const validateSignal = (context, params) => {
    const { htfTrend } = params;
    let validationScore = 0;
    const reasons = [];
    
    const htfAlignment = (htfTrend.value === 'bullish' && context.signal === 'buy') ||
                         (htfTrend.value === 'bearish' && context.signal === 'sell');
    if (htfAlignment) { validationScore += 0.3; reasons.push('✅ HTF alineado'); }
    else { reasons.push('⚠️ HTF en contra'); }
    
    if (context.isHighVolume) { validationScore += 0.25; reasons.push('✅ Alto volumen'); }
    else if (context.isLowVolume) { validationScore -= 0.15; reasons.push('⚠️ Bajo volumen'); }
    
    if (context.signal === 'buy' && context.nearSupport) { validationScore += 0.2; reasons.push('✅ Cerca de soporte'); }
    else if (context.signal === 'sell' && context.nearResistance) { validationScore += 0.2; reasons.push('✅ Cerca de resistencia'); }
    else if (context.signal === 'buy' && context.nearResistance) { validationScore -= 0.2; reasons.push('⚠️ Frente a resistencia'); }
    
    const isExtreme = (context.signal === 'buy' && context.rsi > 75) || (context.signal === 'sell' && context.rsi < 25);
    if (isExtreme) { validationScore -= 0.3; reasons.push('⚠️ RSI Exhausto'); }
    
    return { isValid: validationScore >= 0.4, score: validationScore, reasons };
};

