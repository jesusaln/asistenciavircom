export function useTradingControls(params) {
    const { 
        balance, positions, closedTrades, currentPrice, 
        orderAmount, strategyWeights, bayesianConfidence, 
        strategyConfidence,
        isTradingLocked, htfTrend, candles, isReplayMode, 
        showToast, saveState, updateMarkers, syncExperience, 
        consecutiveLosses, lastCircuitBreakerTime, autoTradingEnabled, 
        isNightModeActive, updateTotalPnL, syncToBackend, 
        maxConsecutiveLosses, commissionPercent, slippagePercent, 
        maxPositionExposure, stopLossPercent, takeProfitPercent, 
        calculateSafeOrderAmount, getSignalContext, calculateATR,
        props, realTradingEnabled, selectedSymbol
    } = params;

    const applyRiskProtection = () => {
        orderAmount.value = parseFloat(calculateSafeOrderAmount().toFixed(2));
    };

    const placeOrder = (type) => {
        if (isTradingLocked.value) {
            showToast('Trading bloqueado: Has alcanzado tu límite de pérdida diaria.', 'warning');
            return;
        }

        if (type === 'buy' && htfTrend.value === 'bearish' && !isReplayMode.value) {
            showToast('BLOQUEADO: Tendencia 4H Bajista', 'warning');
            return;
        }

        if (type === 'sell' && htfTrend.value === 'bullish' && !isReplayMode.value) {
            showToast('BLOQUEADO: Tendencia 4H Alcista', 'warning');
            return;
        }

        // 🛡️ Periodo de Enfriamiento (Anti-Revenge Trading)
        if (closedTrades.value.length > 0 && !isReplayMode.value) {
            const lastTrade = closedTrades.value[0];
            const now = Math.floor(Date.now() / 1000);
            if (lastTrade.pnl < 0 && (now - lastTrade.exitTime) < 1800) {
                const waitMin = Math.ceil((1800 - (now - lastTrade.exitTime)) / 60);
                showToast(`🛡️ Bloqueo Anti-Revenge: Espera ${waitMin} min antes de operar`, 'warning');
                return;
            }
        }

        let normalizedAmount = Number(orderAmount.value);
        
        // IA Dynamic Risk Scaling
        if (strategyConfidence && strategyConfidence.value > 0) {
            const conf = strategyConfidence.value;
            if (conf > 0.75) {
                normalizedAmount *= 1.5;
                showToast(`🔥 IA Alta Confianza (${(conf*100).toFixed(0)}%): Capital x1.5`, 'info');
            } else if (conf < 0.55) {
                normalizedAmount *= 0.6;
                showToast(`🛡️ IA Precaución (${(conf*100).toFixed(0)}%): Capital x0.6`, 'info');
            }
        }

        const context = getSignalContext();
        
        // 🤖 Auditoría Pre-Operación
        if (context) {
            const reasons = [];
            if (type === 'buy') {
                if (context.rsi < 40) reasons.push('RSI favorable');
                if (context.emaFast > context.emaSlow) reasons.push('Cruce EMA Alcista');
                if (context.isHighVolume) reasons.push('Volumen alto');
            } else {
                if (context.rsi > 60) reasons.push('RSI favorable');
                if (context.emaFast < context.emaSlow) reasons.push('Cruce EMA Bajista');
                if (context.isHighVolume) reasons.push('Volumen alto');
            }
            const auditText = reasons.length > 0 ? `🤖 IA Audit: ${reasons.join(', ')}` : '🤖 IA Audit: Operativa por predicción pura';
            showToast(auditText, 'info');
        }

        if (!normalizedAmount || normalizedAmount <= 0) {
            showToast('Ingresa un monto válido', 'warning');
            return;
        }

        if (normalizedAmount > balance.value) {
            showToast('Balance insuficiente', 'error');
            return;
        }

        const atr = calculateATR(candles.value);
        const currentPriceVal = currentPrice.value;
        
        const dynamicSL = ((atr * 1.5) / currentPriceVal) || stopLossPercent;
        const dynamicTP = (dynamicSL * 2.5) || takeProfitPercent;

        const position = {
            id: Date.now(),
            type,
            amount: normalizedAmount,
            entryPrice: currentPriceVal,
            pnl: 0,
            pnlPercent: 0,
            entryContext: context ? { ...context } : null,
            sl: dynamicSL,
            tp: dynamicTP,
            entryTime: isReplayMode.value ? candles.value[candles.value.length-1].time : Math.floor(Date.now() / 1000)
        };
        
        balance.value -= normalizedAmount;
        positions.value.push(position);
        
        if (realTradingEnabled.value) {
            window.axios.post('/trading/execute-order', {
                symbol: selectedSymbol.value,
                side: type.toUpperCase(),
                amount: normalizedAmount
            }).then(res => {
                if (res.data && res.data.error) {
                    showToast(`Error Binance: ${res.data.error}`, 'error');
                } else {
                    showToast(`🚀 Ejecutada orden real en Binance (${type.toUpperCase()})`, 'success');
                }
            }).catch(err => {
                showToast(`Error de Conexión Binance`, 'error');
            });
        }
        
        showToast(`✅ ${type.toUpperCase()} Abierto (SL: ${(dynamicSL*100).toFixed(2)}%)`, 'success');
        saveState();
        updateMarkers();
    };

    const auditTrade = (trade) => {
        if (!trade.entryContext) return 'Análisis no disponible';
        
        const ctx = trade.entryContext;
        const isWin = trade.pnl > 0;
        const reasons = [];

        if (trade.type === 'buy') {
            if (ctx.rsi > 70) reasons.push('Sobrecompra (RSI alto)');
            if (ctx.emaFast < ctx.emaSlow) reasons.push('Contra tendencia (EMA)');
            if (!ctx.isHighVolume) reasons.push('Bajo volumen');
        } else {
            if (ctx.rsi < 30) reasons.push('Sobreventa (RSI bajo)');
            if (ctx.emaFast > ctx.emaSlow) reasons.push('Contra tendencia (EMA)');
        }

        if (isWin) return reasons.length === 0 ? 'Ejecución perfecta' : `Ganancia a pesar de: ${reasons.join(', ')}`;
        else return reasons.length === 0 ? 'Movimiento inesperado del mercado' : `Error: ${reasons.join(', ')}`;
    };

    const closePosition = (position) => {
        const exitPrice = currentPrice.value;
        const pnl = position.pnl;
        const pnlPercent = position.pnlPercent;

        const trade = {
            id: position.id,
            type: position.type,
            amount: position.amount,
            entryPrice: position.entryPrice,
            exitPrice: exitPrice,
            pnl: pnl,
            pnlPercent: pnlPercent,
            entryTime: position.entryTime,
            exitTime: isReplayMode.value ? candles.value[candles.value.length-1].time : Math.floor(Date.now() / 1000),
            entryContext: position.entryContext
        };

        trade.analysis = auditTrade(trade);
        
        const isWin = trade.pnl > 0;
        const ctx = trade.entryContext;
        if (ctx) {
            const learningRate = 0.05;
            const adjustment = isWin ? learningRate : -learningRate;
            if (Math.abs(ctx.emaFast - ctx.emaSlow) / ctx.emaSlow > 0.002) strategyWeights.value.ema += adjustment;
            if (ctx.rsi < 40 || ctx.rsi > 60) strategyWeights.value.rsi += adjustment;
            if (ctx.isHighVolume) strategyWeights.value.volume += adjustment;
            Object.keys(strategyWeights.value).forEach(k => {
                strategyWeights.value[k] = Math.max(0.5, Math.min(2.0, strategyWeights.value[k]));
            });
        }

        closedTrades.value.unshift(trade);
        bayesianConfidence.update(isWin);
        
        if (candles.value.length > 0) {
            syncExperience(candles.value[candles.value.length - 1], trade.type, trade);
        }
        
        if (trade.pnl < 0) {
            consecutiveLosses.value++;
            if (consecutiveLosses.value >= maxConsecutiveLosses) {
                lastCircuitBreakerTime.value = Date.now();
                autoTradingEnabled.value = false;
                isNightModeActive.value = false;
            }
        } else {
            consecutiveLosses.value = 0;
        }
        
        if (isTradingLocked.value) {
            lastCircuitBreakerTime.value = Date.now();
            autoTradingEnabled.value = false;
            isNightModeActive.value = false;
        }

        if (closedTrades.value.length > 50) closedTrades.value.pop();

        balance.value += position.amount + position.pnl;
        if (realTradingEnabled.value) {
            const oppositeSide = position.type === 'buy' ? 'SELL' : 'BUY';
            window.axios.post('/trading/execute-order', {
                symbol: selectedSymbol.value,
                side: oppositeSide,
                amount: position.amount
            }).then(res => {
                if (res.data && res.data.error) {
                    showToast(`Error Binance al cerrar: ${res.data.error}`, 'error');
                } else {
                    showToast(`🚀 Posición real cerrada en Binance`, 'success');
                }
            }).catch(err => {
                showToast(`Error de Conexión Binance`, 'error');
            });
        }

        positions.value = positions.value.filter(p => p.id !== position.id);
        updateTotalPnL();
        saveState();
        updateMarkers();
        syncToBackend(trade);
    };

    const updatePositionsPnL = () => {
        positions.value.forEach(pos => {
            const diff = currentPrice.value - pos.entryPrice;
            const percentChange = (diff / pos.entryPrice);
            
            // Binance Spot Fee: 0.1% Taker/Maker per transaction. (0.2% total for round trip)
            const exchangeFee = pos.amount * 0.002;

            if (pos.type === 'buy') {
                pos.pnlPercent = (percentChange * 100) - 0.2;
                pos.pnl = (pos.amount * percentChange) - exchangeFee;
                if (!pos.highestPrice || currentPrice.value > pos.highestPrice) {
                    pos.highestPrice = currentPrice.value;
                    pos.trailingStopPrice = pos.highestPrice * (1 - (pos.sl || stopLossPercent));
                }
            } else {
                pos.pnlPercent = (-percentChange * 100) - 0.2;
                pos.pnl = (pos.amount * -percentChange) - exchangeFee;
                if (!pos.lowestPrice || currentPrice.value < pos.lowestPrice) {
                    pos.lowestPrice = currentPrice.value;
                    pos.trailingStopPrice = pos.lowestPrice * (1 + (pos.sl || stopLossPercent));
                }
            }
            
            if (pos.originalSL === undefined) {
                pos.originalSL = pos.sl || stopLossPercent;
            }

            const currentSL = pos.sl || stopLossPercent;
            const currentTP = pos.tp || takeProfitPercent;
            
            const oneR = pos.originalSL;
            const oneAndHalfR = pos.originalSL * 1.5;
            
            // FASE 1: Break-Even Seguro al alcanzar +1R
            if (pos.pnlPercent / 100 >= oneR && !pos.phaseOneSet) {
                pos.sl = -0.001; // Asegura +0.1% de ganancia (cubre comisiones)
                pos.phaseOneSet = true;
                showToast('🛡️ Fase 1: Break-Even Asegurado (+0.1%)', 'info');
            }
            
            // FASE 2: Asegurar +1R al alcanzar +2R
            if (pos.pnlPercent / 100 >= (oneR * 1.5) && !pos.phaseTwoSet) {
                pos.sl = -(oneR * 0.8); // Asegura casi todo el riesgo inicial
                pos.phaseTwoSet = true;
                showToast(`🚀 Fase 2: Ganancia Asegurada (+${(oneR*80).toFixed(2)}%)`, 'info');
            }

            // FASE 3 (Trailing Agresivo): Sustituye al Take Profit fijo.
            if (pos.pnlPercent / 100 >= currentTP && !pos.phaseThreeSet) {
                pos.phaseThreeSet = true;
                showToast('🔥 Fase 3: Trailing Stop Agresivo Activado (Persiguiendo Tendencia)', 'success');
            }

            if (pos.phaseThreeSet) {
                // Mueve el stop muy pegado al precio (0.5% de distancia) para exprimir la tendencia máxima
                const trailingDistance = 0.005;
                if (pos.type === 'buy') {
                    pos.trailingStopPrice = Math.max(pos.trailingStopPrice, currentPrice.value * (1 - trailingDistance));
                } else {
                    pos.trailingStopPrice = Math.min(pos.trailingStopPrice, currentPrice.value * (1 + trailingDistance));
                }
            }
            
            const isTrailingStopTriggered = (pos.type === 'buy' && currentPrice.value <= pos.trailingStopPrice) ||
                                            (pos.type === 'sell' && currentPrice.value >= pos.trailingStopPrice);

            // Nota: Ya NO cerramos por Take Profit fijo, dejamos que el trailing stop agresivo lo cierre cuando la tendencia acabe.
            if (pos.pnlPercent / 100 <= -currentSL || isTrailingStopTriggered) {
                closePosition(pos);
            }
        });
    };

    return {
        applyRiskProtection,
        placeOrder,
        closePosition,
        updatePositionsPnL
    };
}
