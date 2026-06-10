import axios from 'axios';

export function useTradingPersistence(params) {
    const { 
        balance, positions, closedTrades, trainingSamples, 
        trainingWins, strategyWeights, selectedSymbol, timeframe, 
        riskSettings, strategyConfidence, showToast, route 
    } = params;

    const saveState = () => {
        const state = {
            balance: balance.value,
            positions: positions.value,
            closedTrades: closedTrades.value,
            trainingSamples: trainingSamples.value,
            trainingWins: trainingWins.value,
            strategyWeights: strategyWeights.value,
            riskSettings: riskSettings.value,
            strategyConfidence: strategyConfidence.value,
            lastSaved: Date.now()
        };
        localStorage.setItem(`trading_sim_state_${selectedSymbol.value}_${timeframe.value}`, JSON.stringify(state));
    };

    const loadState = () => {
        const saved = localStorage.getItem(`trading_sim_state_${selectedSymbol.value}_${timeframe.value}`);
        if (saved) {
            const state = JSON.parse(saved);
            balance.value = state.balance || balance.value;
            positions.value = state.positions || [];
            closedTrades.value = state.closedTrades || [];
            trainingSamples.value = state.trainingSamples || 0;
            trainingWins.value = state.trainingWins || 0;
            if (state.strategyWeights) strategyWeights.value = state.strategyWeights;
            if (state.riskSettings) riskSettings.value = state.riskSettings;
            if (state.strategyConfidence !== undefined) strategyConfidence.value = state.strategyConfidence;
        }
    };


    const syncToBackend = async (data) => {
        try {
            await axios.post(route('trading.sync'), {
                symbol: selectedSymbol.value,
                timeframe: timeframe.value,
                data: data,
                weights: strategyWeights.value,
                accuracy: trainingSamples.value > 0 ? (trainingWins.value / trainingSamples.value) * 100 : 0
            });
        } catch (e) { console.error('Sync failed', e); }
    };

    const syncExperience = async (candle, signal, trade = null) => {
        try {
            await axios.post(route('trading.experience'), {
                symbol: selectedSymbol.value,
                timeframe: timeframe.value,
                candle,
                signal,
                trade
            });
        } catch (e) {}
    };

    const fetchWeights = async () => {
        try {
            const response = await axios.get(route('trading.get-weights'), {
                params: {
                    symbol: selectedSymbol.value,
                    timeframe: timeframe.value
                }
            });
            if (response.data) {
                const data = response.data;
                if (data.weights) strategyWeights.value = data.weights;
                if (data.accuracy !== undefined) {
                    // Si el backend tiene datos, sincronizamos el conteo de muestras y aciertos
                    trainingSamples.value = data.total_trades || trainingSamples.value;
                    trainingWins.value = Math.round((data.accuracy / 100) * trainingSamples.value);
                }
                return true;
            }
        } catch (e) {
            // No hacemos nada si falla, el frontend seguirá con sus datos locales
        }
        return false;
    };

    return {
        saveState,
        loadState,
        syncToBackend,
        syncExperience,
        fetchWeights
    };
}
