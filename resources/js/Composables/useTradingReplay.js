import { onUnmounted } from 'vue';

export function useTradingReplay(params) {
    const { 
        candles, currentPrice, isReplayMode, replayIndex, 
        replaySpeed, candleSeries, volumeSeries, updateMarkers, 
        updatePositionsPnL, autoTradingEnabled,
        trainStrategy, maybeAutoBuy, getSignalContext, latestSignal, strategyConfidence,
        placeOrder, showToast, connectWebSocket, closeSocket
    } = params;


    let replayInterval = null;

    const cleanupReplay = () => {
        if (replayInterval) {
            clearInterval(replayInterval);
            replayInterval = null;
        }
    };

    const startReplay = () => {
        if (candles.value.length === 0) {
            showToast('No hay datos para el replay', 'warning');
            return;
        }
        
        const uniqueCandles = [];
        const seenTimes = new Set();
        [...candles.value].sort((a, b) => a.time - b.time).forEach(c => {
            if (!seenTimes.has(c.time)) {
                seenTimes.add(c.time);
                uniqueCandles.push(c);
            }
        });
        candles.value = uniqueCandles;

        isReplayMode.value = true;
        replayIndex.value = Math.min(100, candles.value.length - 1);
        
        if (closeSocket) closeSocket();
        
        const initialSubCandles = candles.value.slice(0, replayIndex.value + 1);


        candleSeries.value?.setData(initialSubCandles);
        if (volumeSeries.value) {
            const volData = initialSubCandles.map(c => ({
                time: c.time, value: c.volume, 
                color: c.close >= c.open ? 'rgba(16, 185, 129, 0.5)' : 'rgba(244, 63, 94, 0.5)'
            }));
            volumeSeries.value.setData(volData);
        }

        showToast('🎬 Modo Replay Iniciado', 'info');
    };


    const pauseReplay = () => {
        if (replayInterval) {
            clearInterval(replayInterval);
            replayInterval = null;
        }
    };

    const stopReplay = () => {
        pauseReplay();
        isReplayMode.value = false;
        
        if (connectWebSocket) connectWebSocket();
        
        candleSeries.value?.setData(candles.value);

        if (volumeSeries.value) {
            const volData = candles.value.map(c => ({
                time: c.time, value: c.volume, 
                color: c.close >= c.open ? 'rgba(16, 185, 129, 0.5)' : 'rgba(244, 63, 94, 0.5)'
            }));
            volumeSeries.value.setData(volData);
        }

        showToast('🎬 Modo Replay Finalizado', 'info');
    };


    const stepReplay = () => {
        if (replayIndex.value >= candles.value.length - 1) {
            stopReplay();
            return;
        }
        replayIndex.value++;
        const currentCandle = candles.value[replayIndex.value];
        handleNewReplayCandle(currentCandle);
    };

    const playReplay = () => {
        if (replayInterval) return;
        replayInterval = setInterval(stepReplay, replaySpeed.value);
    };

    const handleNewReplayCandle = (candle) => {
        currentPrice.value = candle.close;
        
        const subCandles = candles.value.slice(0, replayIndex.value + 1);
        candleSeries.value?.setData(subCandles);
        if (volumeSeries.value) {
            const volData = subCandles.map(c => ({
                time: c.time, value: c.volume, 
                color: c.close >= c.open ? 'rgba(16, 185, 129, 0.5)' : 'rgba(244, 63, 94, 0.5)'
            }));
            volumeSeries.value.setData(volData);
        }

        updateMarkers(candle.time);
        updatePositionsPnL();
        
        if (trainStrategy) trainStrategy();
        
        const context = getSignalContext ? getSignalContext() : null;
        if (context && latestSignal) {
            latestSignal.value = context.signal;
            if (strategyConfidence) {
                strategyConfidence.value = context.confidence;
            }
        }
        
        if (maybeAutoBuy) maybeAutoBuy();
    };

    onUnmounted(() => cleanupReplay());

    return {
        startReplay,
        pauseReplay,
        stopReplay,
        stepReplay,
        playReplay,
        cleanupReplay
    };
}
