import { 
    getSignalContextFromCandles 
} from '@/Composables/useTradingAnalysis.js';


export function useTradingAI(params) {
    const { 
        candles, strategyWeights, isDeepTraining, 
        trainingSamples, trainingWins, strategyConfidence,
        showToast, saveState, route, selectedSymbol, timeframe,
        trainingAccuracy, isNeuralTraining
    } = params;


    const runNeuralNetworkTraining = async (isManual = false) => {
        if (candles.value.length < 100) {
            if (isManual) showToast('Se requieren al menos 100 velas para entrenar la red neuronal', 'warning');
            return;
        }
        if (isManual) showToast('🧠 Cargando TensorFlow.js...', 'info');
        
        const tf = await import('@tensorflow/tfjs');
        
        if (isManual) showToast('🧠 Inicializando Red Neuronal (TensorFlow)...', 'info');
        
        if (isNeuralTraining) isNeuralTraining.value = true;

        
        try {
            await tf.setBackend('cpu');
            const sequenceLength = 15;
            const numFeatures = 8;
            const features = [];
            const labels = [];
            
            const paramsForContext = {
                strategyWeights: strategyWeights,
                currentMarketRegime: { value: { regime: 'ranging' } },
                isDeepTraining: { value: false },
                detectLiquidityZones: detectLiquidityZones
            };
            
            // Pre-calcular indicadores para cada vela
            const preCalc = candles.value.map((candle, idx) => {
                if (idx < 30) return null;
                const subset = candles.value.slice(0, idx + 1);
                const ctx = getSignalContextFromCandles(subset, paramsForContext);
                if (!ctx) return null;
                const ci = ctx.continuousIndicators;
                return {
                    ema: ci.ema || 0,
                    rsi: ci.rsi || 0,
                    bb: ci.bb || 0,
                    volume: ci.volume || 0,
                    macd: ci.macd || 0,
                    momentum: (ctx.momentum || 0) / 5,
                    atr: ctx.atr ? ctx.atr / (candle.close || 1) : 0,
                    pricePos: ctx.bb ? (candle.close - ctx.bb.lower) / ((ctx.bb.upper - ctx.bb.lower) || 1) : 0.5
                };
            });

            const futureWindow = 8;
            const deadZone = 0.0015; // Ignora movimientos menores a 0.15%

            for (let i = 30 + sequenceLength; i < candles.value.length - futureWindow; i++) {
                const sequence = [];
                let valid = true;
                for (let j = i - sequenceLength + 1; j <= i; j++) {
                    const p = preCalc[j];
                    if (!p) { valid = false; break; }
                    sequence.push([p.ema, p.rsi, p.bb, p.volume, p.macd, p.momentum, p.atr, p.pricePos]);
                }
                
                if (valid) {
                    const currentPrice = candles.value[i].close;
                    const futurePrice = candles.value[i + futureWindow].close;
                    const change = (futurePrice - currentPrice) / currentPrice;
                    
                    // Dead zone: saltar señales ambiguas
                    if (Math.abs(change) < deadZone) continue;
                    
                    features.push(sequence);
                    labels.push(change > 0 ? 1 : 0);
                }
            }
            
            if (features.length < 50) throw new Error(`Solo ${features.length} muestras válidas. Se necesitan mínimo 50.`);
            
            // Balancear clases (mismo número de bulls y bears)
            const bulls = features.filter((_, i) => labels[i] === 1);
            const bears = features.filter((_, i) => labels[i] === 0);
            const minClass = Math.min(bulls.length, bears.length);
            const balancedFeatures = [...bulls.slice(0, minClass), ...bears.slice(0, minClass)];
            const balancedLabels = [...Array(minClass).fill(1), ...Array(minClass).fill(0)];
            
            // Shuffle
            for (let i = balancedFeatures.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [balancedFeatures[i], balancedFeatures[j]] = [balancedFeatures[j], balancedFeatures[i]];
                [balancedLabels[i], balancedLabels[j]] = [balancedLabels[j], balancedLabels[i]];
            }
            
            const xs = tf.tensor3d(balancedFeatures);
            const ys = tf.tensor2d(balancedLabels, [balancedLabels.length, 1]);
            
            const model = tf.sequential();
            model.add(tf.layers.lstm({
                units: 32,
                returnSequences: true,
                inputShape: [sequenceLength, numFeatures],
                recurrentInitializer: 'glorotUniform'
            }));
            model.add(tf.layers.dropout({ rate: 0.2 }));
            model.add(tf.layers.lstm({
                units: 16,
                returnSequences: false,
                recurrentInitializer: 'glorotUniform'
            }));
            model.add(tf.layers.dropout({ rate: 0.15 }));
            model.add(tf.layers.dense({ units: 8, activation: 'relu' }));
            model.add(tf.layers.dense({ units: 1, activation: 'sigmoid' }));
            
            model.compile({
                optimizer: tf.train.adam(0.005),
                loss: 'binaryCrossentropy',
                metrics: ['accuracy']
            });
            
            const history = await model.fit(xs, ys, {
                epochs: 30,
                batchSize: 64,
                validationSplit: 0.2,
                shuffle: true,
                verbose: 0
            });
            
            const valAcc = history.history.val_acc ? history.history.val_acc[history.history.val_acc.length - 1] : 0;
            trainingAccuracy.value = parseFloat((valAcc * 100).toFixed(1));
            trainingSamples.value = balancedFeatures.length;
            trainingWins.value = Math.round(valAcc * balancedFeatures.length);
            
            // Predicción con datos más recientes
            const latestSequence = [];
            const startIdx = Math.max(30, candles.value.length - sequenceLength);
            for (let k = startIdx; k < candles.value.length; k++) {
                const p = preCalc[k];
                if (p) {
                    latestSequence.push([p.ema, p.rsi, p.bb, p.volume, p.macd, p.momentum, p.atr, p.pricePos]);
                } else {
                    latestSequence.push([0, 0, 0, 0, 0, 0, 0, 0.5]);
                }
            }
            
            while (latestSequence.length < sequenceLength) {
                latestSequence.unshift([0, 0, 0, 0, 0, 0, 0, 0.5]);
            }
            
            const input = tf.tensor3d([latestSequence]);
            const prediction = model.predict(input);
            const prob = (await prediction.data())[0];
            
            strategyConfidence.value = prob;
            if (isManual) showToast(`🧠 Red LSTM (2 capas, ${numFeatures} features): Prob. subida ${(prob * 100).toFixed(1)}% | Val Acc: ${(valAcc*100).toFixed(1)}%`, 'success');
            
            if (saveState) saveState();
            
            input.dispose();
            prediction.dispose();
            xs.dispose();
            ys.dispose();
            model.dispose();
            
        } catch (error) {
            showToast(`Error en Red Neuronal: ${error.message}`, 'error');
        } finally {
            if (isNeuralTraining) isNeuralTraining.value = false;
        }
    };


    const detectLiquidityZones = (sourceCandles) => {

        const highs = sourceCandles.map(c => c.high);
        const lows = sourceCandles.map(c => c.low);
        const zones = { resistance: [], support: [] };
        const lookback = 20;
        for (let i = lookback; i < sourceCandles.length - lookback; i++) {
            if (highs[i] === Math.max(...highs.slice(i-lookback, i+lookback))) zones.resistance.push({ price: highs[i], strength: 1 });
            if (lows[i] === Math.min(...lows.slice(i-lookback, i+lookback))) zones.support.push({ price: lows[i], strength: 1 });
        }
        const groupZones = (zList) => {
            const grouped = [];
            for (const z of zList) {
                let found = false;
                for (const g of grouped) {
                    if (Math.abs(g.price - z.price) / z.price < 0.005) { g.strength++; g.price = (g.price + z.price) / 2; found = true; break; }
                }
                if (!found) grouped.push({ ...z });
            }
            return grouped.sort((a,b) => b.strength - a.strength);
        };
        return { resistance: groupZones(zones.resistance), support: groupZones(zones.support) };
    };

    return {
        detectLiquidityZones,
        runNeuralNetworkTraining
    };
}
