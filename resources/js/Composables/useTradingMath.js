export const average = (values) => values.length === 0 ? 0 : values.reduce((sum, value) => sum + value, 0) / values.length;

export const calculateEMA = (values, period) => {
    if (values.length < period) return null;

    const multiplier = 2 / (period + 1);
    const result = new Array(values.length).fill(null);
    
    let ema = average(values.slice(0, period));
    result[period - 1] = ema;

    for (let i = period; i < values.length; i++) {
        ema = ((values[i] - ema) * multiplier) + ema;
        result[i] = ema;
    }

    return result;
};

export const calculateMACD = (data) => {
    if (data.length < 35) return data.map(() => ({ macd: 0, signal: 0, histogram: 0 }));
    const closes = data.map(c => c.close);
    const ema12 = calculateEMA(closes, 12);
    const ema26 = calculateEMA(closes, 26);
    
    if (!ema12 || !ema26) return data.map(() => ({ macd: 0, signal: 0, histogram: 0 }));
    
    const macdLine = ema12.map((e12, i) => (e12 !== null && ema26[i] !== null) ? e12 - ema26[i] : 0);
    const signalLine = calculateEMA(macdLine, 9);
    
    if (!signalLine) return data.map(() => ({ macd: 0, signal: 0, histogram: 0 }));

    return macdLine.map((m, i) => ({
        macd: m,
        signal: signalLine[i] || 0,
        histogram: m - (signalLine[i] || 0)
    }));
};

export const calculateBollingerBands = (values, period = 20, multiplier = 2) => {
    if (values.length < period) return null;

    const sma = average(values.slice(-period));
    const squareDiffs = values.slice(-period).map(v => Math.pow(v - sma, 2));
    const stdDev = Math.sqrt(average(squareDiffs));

    return {
        middle: sma,
        upper: sma + (multiplier * stdDev),
        lower: sma - (multiplier * stdDev)
    };
};

export const calculateRSI = (values, period = 14) => {
    if (values.length <= period) return null;

    let gains = 0;
    let losses = 0;

    for (let index = values.length - period; index < values.length; index += 1) {
        const previous = values[index - 1];
        const change = values[index] - previous;
        if (change >= 0) {
            gains += change;
        } else {
            losses += Math.abs(change);
        }
    }

    if (losses === 0) return 100;

    const relativeStrength = (gains / period) / (losses / period);
    return 100 - (100 / (1 + relativeStrength));
};

export const calculateATR = (candles, period = 14) => {
    if (candles.length < period + 1) return 0;
    const trs = [];
    for (let i = 1; i < candles.length; i++) {
        const h = candles[i].high;
        const l = candles[i].low;
        const pc = candles[i-1].close;
        trs.push(Math.max(h - l, Math.abs(h - pc), Math.abs(l - pc)));
    }
    return trs.slice(-period).reduce((a, b) => a + b, 0) / period;
};
