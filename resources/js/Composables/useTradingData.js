import { onUnmounted } from 'vue';

export function useTradingData(params) {
    const { 
        selectedSymbol, timeframe, candles, currentPrice, 
        priceChange, candleSeries, volumeSeries, updateMarkers, 
        updateIndicators, trainStrategy, getSignalContext, 
        latestSignal, strategyConfidence, trainingAccuracy, 
        maybeAutoClose, maybeAutoBuy, syncExperience, 
        updatePositionsPnL, updateTotalPnL, route, showToast,
        syncCandleSeries, htfTrend
    } = params;

    let binanceSocket = null;

    const connectWebSocket = () => {
        if (binanceSocket) {
            binanceSocket.close();
            binanceSocket = null;
        }
        const symbol = selectedSymbol.value.toLowerCase();
        const stream = `${symbol}@kline_${timeframe.value}`;
        binanceSocket = new WebSocket(`wss://stream.binance.com:9443/ws/${stream}`);

        binanceSocket.onmessage = (event) => {
            const data = JSON.parse(event.data);
            const kline = data.k;
            const candle = {
                time: kline.t / 1000,
                open: parseFloat(kline.o),
                high: parseFloat(kline.h),
                low: parseFloat(kline.l),
                close: parseFloat(kline.c),
                volume: parseFloat(kline.v),
            };

            if (!validateCandle(candle)) return;

            candleSeries.value?.update(candle);
            if (volumeSeries.value) {
                volumeSeries.value.update({
                    time: candle.time,
                    value: candle.volume,
                    color: candle.close >= candle.open ? 'rgba(16, 185, 129, 0.3)' : 'rgba(244, 63, 94, 0.3)'
                });
            }
            
            const filtered = candles.value.filter(existing => existing.time !== candle.time);
            candles.value = [...filtered, candle].slice(-1000);

            if (kline.x) {
                syncCandleSeries(candles.value);
                updateHTFTrend();
                updateMarkers();
                trainStrategy();
                const context = getSignalContext();
                if (context) {
                    latestSignal.value = context.signal;
                    strategyConfidence.value = Math.max(trainingAccuracy.value, context.confidence);
                }
                maybeAutoClose();
                maybeAutoBuy();
                syncExperience(candle, latestSignal.value);
            }
            
            const oldPrice = currentPrice.value;
            currentPrice.value = candle.close;
            if (oldPrice > 0) priceChange.value = ((currentPrice.value - oldPrice) / oldPrice) * 100;
            updatePositionsPnL();
            updateTotalPnL();
            updateIndicators(candles.value);
        };

        binanceSocket.onclose = () => {
            setTimeout(() => {
                if (!binanceSocket || binanceSocket.readyState === WebSocket.CLOSED) connectWebSocket();
            }, 5000);
        };
    };

    const loadHistoricalCandles = async () => {
        await updateHTFTrend();
        const serverUrl = route('trading.get-history', { symbol: selectedSymbol.value, timeframe: timeframe.value, limit: 1000 });
        let rows = [];
        try {
            const res = await fetch(serverUrl);
            if (res.ok) rows = await res.json();
        } catch (e) { console.warn('Server history failed, falling back to Binance'); }

        if (!rows || rows.length === 0) {
            const response = await fetch(`https://api.binance.com/api/v3/klines?symbol=${selectedSymbol.value}&interval=${timeframe.value}&limit=1000`);
            if (response.ok) rows = await response.json();
        }
        
        const historicalCandles = rows.map((row) => ({
            time: Number(row[0]) / 1000,
            open: parseFloat(row[1]), high: parseFloat(row[2]),
            low: parseFloat(row[3]), close: parseFloat(row[4]), volume: parseFloat(row[5]),
        }));

        syncCandleSeries(historicalCandles);
        if (historicalCandles.length > 1) {
            const previousClose = historicalCandles[historicalCandles.length - 2].close;
            const latestClose = historicalCandles[historicalCandles.length - 1].close;
            priceChange.value = ((latestClose - previousClose) / previousClose) * 100;
        }
        updateMarkers();
        trainStrategy();
        const context = getSignalContext();
        if (context) {
            latestSignal.value = context.signal;
            strategyConfidence.value = Math.max(trainingAccuracy.value, context.confidence);
        }
        maybeAutoBuy();
    };

    const validateCandle = (candle) => {
        if (!candle || isNaN(candle.close) || candle.close <= 0) return false;
        if (candles.value.length > 0) {
            const last = candles.value[candles.value.length - 1];
            if (candle.time < last.time) return false;
        }
        return true;
    };

    const updateHTFTrend = async () => {
        try {
            const response = await fetch(`https://api.binance.com/api/v3/klines?symbol=${selectedSymbol.value}&interval=4h&limit=50`);
            if (response.ok) {
                const rows = await response.json();
                const closes = rows.map(r => parseFloat(r[4]));
                const ema = closes.reduce((a, b) => a + b, 0) / closes.length;
                htfTrend.value = closes[closes.length - 1] > ema ? 'bullish' : 'bearish';
            }
        } catch (e) {}
    };

    const closeSocket = () => {
        if (binanceSocket) {
            binanceSocket.onclose = null;
            binanceSocket.close();
            binanceSocket = null;
        }
    };

    onUnmounted(() => closeSocket());

    return {
        connectWebSocket,
        loadHistoricalCandles,
        validateCandle,
        updateHTFTrend,
        closeSocket
    };
}

