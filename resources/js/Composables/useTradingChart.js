import { ref, onMounted, onBeforeUnmount } from 'vue';
import { createChart, CandlestickSeries, LineSeries, HistogramSeries, createSeriesMarkers } from 'lightweight-charts';
import { calculateEMA, calculateBollingerBands } from '@/Composables/useTradingMath.js';

export function useTradingChart(containerRef, params) {
    const { 
        candles, closedTrades, positions, showToast, 
        stopLossPercent, takeProfitPercent 
    } = params;

    let chart = null;
    let candleSeries = null;
    let volumeSeries = null;
    let emaFastSeries = null;
    let emaSlowSeries = null;
    let bbUpperSeries = null;
    let bbMiddleSeries = null;
    let bbLowerSeries = null;
    let markersPrimitive = null;
    const priceLines = ref([]);

    const initChart = () => {
        if (!containerRef.value) return;

        chart = createChart(containerRef.value, {
            handleScroll: false,
            handleScale: false,
            layout: { background: { color: 'transparent' }, textColor: '#94a3b8' },
            grid: {
                vertLines: { color: 'rgba(255, 255, 255, 0.05)' },
                horzLines: { color: 'rgba(255, 255, 255, 0.05)' },
            },
            crosshair: { mode: 0 },
            timeScale: { borderColor: 'rgba(255, 255, 255, 0.1)', timeVisible: true, rightOffset: 12 },
            rightPriceScale: { borderColor: 'rgba(255, 255, 255, 0.1)' },
        });

        candleSeries = chart.addSeries(CandlestickSeries, {
            upColor: '#10b981', downColor: '#f43f5e', borderVisible: false,
            wickUpColor: '#10b981', wickDownColor: '#f43f5e',
        });
        markersPrimitive = createSeriesMarkers(candleSeries, []);

        emaFastSeries = chart.addSeries(LineSeries, { color: 'rgba(56, 189, 248, 0.5)', lineWidth: 1, priceLineVisible: false });
        emaSlowSeries = chart.addSeries(LineSeries, { color: 'rgba(251, 191, 36, 0.4)', lineWidth: 1, priceLineVisible: false });
        
        bbUpperSeries = chart.addSeries(LineSeries, { color: 'rgba(168, 85, 247, 0.3)', lineWidth: 1, priceLineVisible: false });
        bbMiddleSeries = chart.addSeries(LineSeries, { color: 'rgba(168, 85, 247, 0.1)', lineWidth: 1, lineStyle: 2, priceLineVisible: false });
        bbLowerSeries = chart.addSeries(LineSeries, { color: 'rgba(168, 85, 247, 0.3)', lineWidth: 1, priceLineVisible: false });

        volumeSeries = chart.addSeries(HistogramSeries, {
            color: '#26a69a', priceFormat: { type: 'volume' }, priceScaleId: '',
        });

        chart.priceScale('').applyOptions({ scaleMargins: { top: 0.8, bottom: 0 } });
    };

    const updateIndicators = (data) => {
        if (!emaFastSeries || data.length < 20) return;
        const closes = data.map(c => c.close);
        
        const emaFastData = [];
        const emaSlowData = [];
        for (let i = 7; i < data.length; i++) {
            const valArr = calculateEMA(closes.slice(0, i + 1), 7);
            if (valArr) emaFastData.push({ time: data[i].time, value: valArr.slice(-1)[0] });
        }
        for (let i = 18; i < data.length; i++) {
            const valArr = calculateEMA(closes.slice(0, i + 1), 18);
            if (valArr) emaSlowData.push({ time: data[i].time, value: valArr.slice(-1)[0] });
        }
        emaFastSeries.setData(emaFastData);
        emaSlowSeries.setData(emaSlowData);

        const bbUpperData = [], bbMiddleData = [], bbLowerData = [];
        for (let i = 19; i < data.length; i++) {
            const bands = calculateBollingerBands(closes.slice(0, i + 1));
            if (bands) {
                bbUpperData.push({ time: data[i].time, value: bands.upper });
                bbMiddleData.push({ time: data[i].time, value: bands.middle });
                bbLowerData.push({ time: data[i].time, value: bands.lower });
            }
        }
        bbUpperSeries.setData(bbUpperData);
        bbMiddleSeries.setData(bbMiddleData);
        bbLowerSeries.setData(bbLowerData);
    };

    const updateMarkers = (maxTime = null) => {
        if (!candleSeries) return;
        const markers = [];
        const currentTime = maxTime || (candles.value.length > 0 ? candles.value[candles.value.length-1].time : null);
        
        priceLines.value.forEach(line => { try { candleSeries.removePriceLine(line); } catch (e) {} });
        priceLines.value = [];

        closedTrades.value.forEach(trade => {
            if (!trade.entryTime || (currentTime && trade.entryTime > currentTime)) return;
            markers.push({
                time: trade.entryTime, position: trade.type === 'buy' ? 'belowBar' : 'aboveBar',
                color: trade.type === 'buy' ? '#10b981' : '#f43f5e', shape: trade.type === 'buy' ? 'arrowUp' : 'arrowDown',
                text: trade.type === 'buy' ? 'BUY' : 'SELL',
            });
            if (trade.exitTime && (!currentTime || trade.exitTime <= currentTime)) {
                markers.push({
                    time: trade.exitTime, position: trade.type === 'buy' ? 'aboveBar' : 'belowBar',
                    color: '#94a3b8', shape: trade.type === 'buy' ? 'arrowDown' : 'arrowUp', text: 'CLOSE',
                });
            }
        });

        positions.value.forEach(pos => {
            if (!pos.entryTime || (currentTime && pos.entryTime > currentTime)) return;
            markers.push({
                time: pos.entryTime, position: pos.type === 'buy' ? 'belowBar' : 'aboveBar',
                color: pos.type === 'buy' ? '#10b981' : '#f43f5e', shape: pos.type === 'buy' ? 'arrowUp' : 'arrowDown',
                text: pos.type === 'buy' ? 'BUY' : 'SELL',
            });
            const line = candleSeries.createPriceLine({
                price: pos.entryPrice, color: pos.type === 'buy' ? '#10b981' : '#f43f5e',
                lineWidth: 1, lineStyle: 2, axisLabelVisible: true, title: 'Entry',
            });
            priceLines.value.push(line);
        });

        if (markersPrimitive) {
            markersPrimitive.setMarkers(markers);
        }
    };

    const syncCandleSeries = (nextCandles) => {
        candles.value = nextCandles;
        candleSeries.setData(nextCandles);
        updateIndicators(nextCandles);
        updateMarkers();
        chart.timeScale().fitContent();
    };

    return {
        initChart,
        updateIndicators,
        updateMarkers,
        syncCandleSeries,
        getChart: () => chart,
        getCandleSeries: () => candleSeries,
        getVolumeSeries: () => volumeSeries
    };
}
