<template>
    <AppLayout title="Simulación de Velas">
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-black text-[var(--ui-text)] uppercase tracking-wider">
                        Trading <span class="text-[var(--ui-accent)]">Training</span>
                    </h2>
                    <p class="text-xs font-bold text-[var(--ui-text-soft)] uppercase tracking-wide mt-1">
                        Simulador de Velas Japonesas (Paper Trading)
                    </p>
                </div>
                <div class="flex items-center gap-2 bg-[var(--ui-surface-soft)] px-6 py-3 rounded-2xl border border-[var(--ui-border)] shadow-sm">
                    <div class="flex flex-col items-center px-4 border-r border-[var(--ui-border)]">
                        <span class="text-[9px] font-black text-[var(--ui-text-muted)] uppercase tracking-wide">Win Rate</span>
                        <span class="text-sm font-black text-[var(--ui-text)]">{{ tradingStats.winRate.toFixed(1) }}%</span>
                    </div>
                    <div class="flex flex-col items-end">
                        <span class="text-[9px] font-black text-[var(--ui-text-muted)] uppercase tracking-wide">
                            {{ realTradingEnabled ? 'Balance Real (Testnet)' : 'Balance Simulado' }}
                        </span>
                        <span class="text-lg font-black" :class="(realTradingEnabled ? realBalance : balance) >= 10000 ? 'text-emerald-500' : 'text-rose-500'">
                            ${{ (realTradingEnabled ? realBalance : balance).toLocaleString('en-US', { minimumFractionDigits: 2 }) }} USD
                        </span>
                    </div>
                    <div class="w-px h-8 bg-[var(--ui-border)] mx-1"></div>
                    <div class="flex flex-col">
                        <span class="text-[9px] font-black text-[var(--ui-text-muted)] uppercase tracking-wide">P&L Acumulado</span>
                        <span class="text-xs font-bold" :class="tradingStats.profit >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                            {{ tradingStats.profit >= 0 ? '+' : '' }}${{ tradingStats.profit.toFixed(2) }}
                        </span>
                    </div>
                </div>
            </div>
        </template>

        <div class="p-6 space-y-6">
                <!-- Chart Area -->
                <div class="lg:col-span-4 space-y-6">
                    <div class="glass-panel rounded-[2.5rem] overflow-hidden border border-[var(--ui-border)] shadow-xl relative min-h-[500px]">
                        <!-- Chart Header -->
                        <div class="px-8 py-6 border-b border-[var(--ui-border)] flex items-center justify-between bg-white/[0.02]">
                            <div class="flex items-center gap-4">
                                <button @click="toggleChartLock" 
                                    class="flex items-center gap-2 px-4 py-2 rounded-xl border transition-all text-[10px] font-black uppercase tracking-wide"
                                    :class="isChartLocked ? 'bg-brand-500/10 border-brand-500/20 text-brand-500' : 'bg-brand-500/10 border-emerald-500/20 text-emerald-500'"
                                >
                                    <FontAwesomeIcon :icon="isChartLocked ? icons.lock : icons.unlock" />
                                    {{ isChartLocked ? 'Gráfico Bloqueado' : 'Gráfico Activo' }}
                                </button>

                                <div v-if="isReplayMode" class="flex items-center gap-2 bg-indigo-500/20 px-4 py-2 rounded-xl border border-indigo-500/30">
                                    <button @click="pauseReplay" class="text-white hover:text-indigo-400"><FontAwesomeIcon :icon="icons.pause" /></button>
                                    <button @click="playReplay" class="text-white hover:text-indigo-400"><FontAwesomeIcon :icon="icons.play" /></button>
                                    <button @click="stepReplay" class="text-white hover:text-indigo-400"><FontAwesomeIcon :icon="icons.forwardStep" /></button>
                                    <button @click="stopReplay" class="text-rose-400 hover:text-rose-500 ml-2"><FontAwesomeIcon :icon="icons.xmark" /></button>
                                    <span class="text-[9px] font-black text-indigo-300 uppercase ml-2">REPLAY: {{ replayIndex }}/{{ candles.length }}</span>
                                </div>
                                <button v-else @click="startReplay" class="flex items-center gap-2 bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 px-4 py-2 rounded-xl border border-slate-200 dark:border-white/10 text-[10px] font-black uppercase tracking-wide text-slate-700 dark:text-white transition-all">
                                    <FontAwesomeIcon :icon="icons.clapperboard" class="text-indigo-400" />
                                    Modo Replay
                                </button>

                                <div class="w-10 h-10 rounded-xl bg-brand-500/10 flex items-center justify-center">
                                    <FontAwesomeIcon :icon="icons.chartArea" class="text-brand-500" />
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <select 
                                            v-model="selectedSymbol" 
                                            class="bg-[var(--ui-surface-alt)] border border-[var(--ui-border)] rounded-xl px-4 py-1.5 text-[10px] font-black text-[var(--ui-text)] outline-none focus:border-brand-500/50 transition-all uppercase"
                                        >
                                            <option v-for="s in symbols" :key="s.value" :value="s.value">{{ s.label }}</option>
                                        </select>
                                        <span class="text-[10px] font-black text-slate-300 dark:text-white/20">/</span>
                                        <span class="text-[10px] font-black text-slate-400 dark:text-white/40 uppercase tracking-wide">{{ timeframe }}</span>
                                    </div>
                                    <div class="flex items-center gap-1 mt-2">
                                        <button 
                                            v-for="tf in ['1m', '5m', '15m', '1h', '4h']" 
                                            :key="tf"
                                            @click="timeframe = tf"
                                            class="text-[8px] font-black uppercase px-2 py-0.5 rounded-xl transition-colors"
                                            :class="timeframe === tf ? 'bg-brand-500 text-slate-900' : 'bg-[var(--ui-surface-alt)] text-[var(--ui-text-soft)] hover:text-[var(--ui-text)]'"
                                        >
                                            {{ tf }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <button @click="openApiSettingsModal" class="text-white/40 hover:text-white flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-white/10 hover:border-white/20 text-[9px] font-black uppercase tracking-wider transition-all">
                                    <FontAwesomeIcon :icon="icons.gear" />
                                    <span>API Keys</span>
                                </button>
                                <div class="flex items-center gap-2">
                                    <span class="flex h-2 w-2 relative">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-brand-500"></span>
                                    </span>
                                    <span class="text-[10px] font-black text-emerald-500 uppercase tracking-wide">Live</span>
                                </div>
                            </div>
                        </div>

                        <!-- The Chart Container -->
                        <div ref="chartContainer" class="w-full h-[550px]"></div>

                        <!-- Floating Legend -->
                        <div class="absolute top-24 left-8 pointer-events-none z-10">
                            <div class="glass-panel p-4 rounded-2xl border border-white/5 bg-black/50 backdrop-blur-md">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl font-black text-slate-800 dark:text-white">${{ currentPrice.toLocaleString() }}</span>
                                    <span class="text-xs font-bold" :class="priceChange >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                                        {{ priceChange >= 0 ? '▲' : '▼' }} {{ Math.abs(priceChange).toFixed(2) }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <!-- Dashboard Horizontal de Inteligencia -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                
                <!-- Col 1: Estado IA & Entrenamiento -->
                <div class="glass-panel rounded-[2.5rem] p-6 border border-[var(--ui-border)] bg-[var(--ui-surface)] space-y-6 shadow-xl">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-[10px] font-black text-[var(--ui-text)] uppercase tracking-[0.2em]">Cerebro IA</h3>
                        <div class="flex items-center gap-2">
                            <div class="flex items-center gap-1.5 px-2 py-0.5 rounded-xl bg-cyan-500/10 border border-cyan-500/20" :class="isSyncing ? 'opacity-100' : 'opacity-40'">
                                <span class="relative flex h-1.5 w-1.5">
                                    <span v-if="isSyncing" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-1.5 w-1.5" :class="isSyncing ? 'bg-cyan-500' : 'bg-white/20'"></span>
                                </span>
                                <span class="text-[7px] font-black uppercase text-cyan-400 tracking-wide">Cloud Sync</span>
                            </div>

                            <div v-if="isNeuralTraining" class="flex items-center gap-1.5 px-2 py-0.5 rounded-xl bg-brand-500/10 border border-brand-500/20 animate-pulse">
                                <span class="relative flex h-1.5 w-1.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-brand-500"></span>
                                </span>
                                <span class="text-[7px] font-black uppercase text-brand-400 tracking-wide">IA Pensando</span>
                            </div>


                        </div>
                    </div>
                    
                    <div class="p-3 rounded-xl bg-[var(--ui-surface-soft)]/50 flex items-center justify-between border border-emerald-500/20 mb-2 mt-3">
                        <div class="flex flex-col">
                            <p class="text-[8px] font-black text-emerald-600 dark:text-slate-400 uppercase tracking-wide flex items-center gap-1">
                                <FontAwesomeIcon :icon="icons.bolt" />
                                <span>Mercado Spot</span>
                            </p>
                            <p class="text-[6px] font-bold text-slate-400 dark:text-white/40 uppercase tracking-wider mt-0.5">
                                Flujo de datos en vivo
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-brand-500 animate-pulse"></div>
                            <span class="text-[8px] font-black uppercase text-emerald-500">Conectado</span>
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-[var(--ui-surface-soft)]/50 flex items-center justify-between border border-rose-500/20">
                        <div class="flex flex-col">
                            <p class="text-[8px] font-black text-rose-600 dark:text-rose-400 uppercase tracking-wide flex items-center gap-1">
                                <FontAwesomeIcon :icon="icons.fire" />
                                <span>Ejecución Real</span>
                            </p>
                            <p class="text-[6px] font-bold text-slate-400 dark:text-white/40 uppercase tracking-wider mt-0.5">
                                Mapea operaciones a Binance
                            </p>
                        </div>
                        <button @click="realTradingEnabled = !realTradingEnabled" class="w-8 h-4 rounded-full bg-slate-200 dark:bg-white/10 relative transition-colors" :class="{'bg-brand-500/40': realTradingEnabled}">
                            <div class="absolute top-0.5 w-2 h-2 rounded-full bg-white dark:bg-slate-800 transition-all" :class="realTradingEnabled ? 'right-0.5' : 'left-0.5'"></div>
                        </button>
                    </div>
                    
                    <div class="p-3 rounded-xl bg-[var(--ui-surface-soft)]/50">
                        <p class="text-[8px] font-black text-[var(--ui-text-muted)] uppercase tracking-wide">Modo Actual</p>
                        <p class="text-xs font-black text-[var(--ui-text)] mt-1">{{ isTraining ? 'Entrenando' : 'Operativa' }}</p>
                        <div class="mt-2 w-full h-1 bg-slate-200 dark:bg-white/5 rounded-full overflow-hidden">
                            <div class="h-full bg-emerald-400 transition-all" :style="{ width: `${trainingProgress}%` }"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div class="p-2 rounded-xl bg-[var(--ui-surface-soft)]/50 text-center">
                            <p class="text-[6px] font-black text-slate-400 dark:text-white/40 uppercase">Confianza</p>
                            <p class="text-[10px] font-black text-cyan-600 dark:text-cyan-400">{{ (strategyConfidence >= 1 ? strategyConfidence : strategyConfidence * 100).toFixed(0) }}%</p>
                        </div>
                        <div class="p-2 rounded-xl bg-[var(--ui-surface-soft)]/50 text-center">
                            <p class="text-[6px] font-black text-slate-400 dark:text-white/40 uppercase">Precisión</p>
                            <p class="text-[10px] font-black text-emerald-600 dark:text-slate-400">{{ trainingAccuracyLabel }}</p>
                        </div>
                        <div class="p-2 rounded-xl bg-[var(--ui-surface-soft)]/50 text-center">
                            <p class="text-[6px] font-black text-slate-400 dark:text-white/40 uppercase">Señal</p>
                            <p class="text-[10px] font-black" :class="latestSignal === 'buy' ? 'text-emerald-600 dark:text-slate-400' : 'text-brand-600 dark:text-amber-400'">{{ latestSignalLabel }}</p>
                        </div>
                    </div>

                </div>

                <!-- Col 2: Backtest -->
                <div class="glass-panel rounded-[2.5rem] p-6 border border-[var(--ui-border)] bg-[var(--ui-surface)] shadow-xl">
                    <h3 class="text-[10px] font-black text-slate-400 dark:text-[var(--ui-text)] uppercase tracking-[0.2em] mb-4">Rendimiento Histórico</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3 rounded-xl bg-[var(--ui-surface-soft)]/50">
                            <p class="text-[7px] font-black text-slate-400 dark:text-white/40 uppercase">Neto</p>
                            <p class="text-sm font-black mt-1" :class="backtestMetrics.netPercent >= 0 ? 'text-emerald-600 dark:text-slate-400' : 'text-rose-600 dark:text-rose-400'">
                                {{ backtestMetrics.netPercent >= 0 ? '+' : '' }}{{ backtestMetrics.netPercent.toFixed(2) }}%
                            </p>
                        </div>
                        <div class="p-3 rounded-xl bg-[var(--ui-surface-soft)]/50">
                            <p class="text-[7px] font-black text-slate-400 dark:text-white/40 uppercase">Win Rate</p>
                            <p class="text-sm font-black text-[var(--ui-text)] mt-1">{{ backtestMetrics.winRate.toFixed(1) }}%</p>
                        </div>
                        <div class="p-3 rounded-xl bg-[var(--ui-surface-soft)]/50">
                            <p class="text-[7px] font-black text-slate-400 dark:text-white/40 uppercase">Expectativa</p>
                            <p class="text-sm font-black text-[var(--ui-text)] mt-1">${{ backtestMetrics.expectancy.toFixed(2) }}</p>
                        </div>
                        <div class="p-3 rounded-xl bg-[var(--ui-surface-soft)]/50">
                            <p class="text-[7px] font-black text-slate-400 dark:text-white/40 uppercase">Max Drawdown</p>
                            <p class="text-sm font-black text-rose-400 mt-1">{{ backtestMetrics.maxDrawdown.toFixed(2) }}%</p>
                        </div>
                    </div>
                </div>

                <!-- Col 3: Terminal -->
                <div class="glass-panel rounded-[2.5rem] p-6 border border-rose-500/20 bg-[var(--ui-surface)] space-y-6 shadow-xl">
                    <div class="flex items-center justify-between">
                        <h3 class="text-[10px] font-black text-rose-600 dark:text-rose-400 uppercase tracking-[0.2em]">Terminal</h3>
                        <div class="text-[8px] font-black text-rose-600/60 dark:text-rose-400/60 uppercase">{{ riskLabel }} Risk</div>
                    </div>
                    <div class="relative">
                        <input v-model="orderAmount" type="number" class="w-full bg-[var(--ui-surface-alt)] border border-[var(--ui-border)] rounded-xl py-3 px-4 text-xs font-black text-[var(--ui-text)] outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <button @click="placeOrder('buy')" class="py-4 rounded-xl bg-brand-500 hover:bg-emerald-600 text-white shadow-xl shadow-emerald-500/20 transition-all flex flex-col items-center">
                            <FontAwesomeIcon :icon="icons.arrowTrendUp" class="text-xs mb-1" />
                            <span class="text-[8px] font-black">LONG</span>
                        </button>
                        <button @click="placeOrder('sell')" class="py-4 rounded-xl bg-brand-500 hover:bg-rose-600 text-white shadow-xl shadow-rose-500/20 transition-all flex flex-col items-center">
                            <FontAwesomeIcon :icon="icons.arrowTrendDown" class="text-xs mb-1" />
                            <span class="text-[8px] font-black">SHORT</span>
                        </button>
                    </div>
                </div>

                <!-- Col 4: Estatus -->
                <div class="glass-panel rounded-[2.5rem] p-6 border border-[var(--ui-border)] bg-[var(--ui-surface)] shadow-xl overflow-hidden">
                    <h3 class="text-[10px] font-black text-slate-400 dark:text-[var(--ui-text)] uppercase tracking-[0.2em] mb-4">Estatus</h3>
                    <div class="space-y-3">
                        <div class="p-3 rounded-xl bg-[var(--ui-surface-soft)]/50">
                            <p class="text-[6px] font-black text-slate-400 dark:text-white/40 uppercase">Mercado</p>
                            <p class="text-[9px] font-black text-[var(--ui-text)]" :class="marketRegimeClass">{{ marketRegimeLabel }}</p>
                        </div>
                        <div class="p-3 rounded-xl bg-[var(--ui-surface-soft)]/50">
                            <p class="text-[6px] font-black text-slate-400 dark:text-white/40 uppercase">Sentimiento (Fear & Greed)</p>
                            <p class="text-[9px] font-black" :class="sentimentScore > 0.55 ? 'text-emerald-600 dark:text-slate-400' : (sentimentScore < 0.45 ? 'text-rose-600 dark:text-rose-400' : 'text-brand-600 dark:text-amber-400')">
                                {{ sentimentLabel }} ({{ (sentimentScore * 100).toFixed(0) }}/100)
                            </p>
                        </div>
                        <div class="p-3 rounded-xl bg-[var(--ui-surface-soft)]/50">
                            <p class="text-[6px] font-black text-slate-400 dark:text-white/40 uppercase mb-1">Pesos IA (Evolución Genética)</p>
                            <div class="grid grid-cols-2 gap-x-3 gap-y-1 text-[8px] font-bold text-[var(--ui-text)]">
                                <div v-for="(weight, name) in strategyWeights" :key="name" class="flex justify-between border-b border-slate-100 dark:border-white/5 py-0.5">
                                    <span class="uppercase text-slate-400 dark:text-white/50">{{ name }}</span>
                                    <span class="text-cyan-600 dark:text-cyan-400 font-black">{{ typeof weight === 'number' ? weight.toFixed(4) : weight }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="p-3 rounded-xl bg-[var(--ui-surface-soft)]/50">
                            <p class="text-[6px] font-black text-slate-400 dark:text-white/40 uppercase">Posiciones Abiertas</p>
                            <div v-for="pos in positions" :key="pos.id" class="flex justify-between items-center mt-1">
                                <span class="text-[8px] font-black" :class="pos.type === 'buy' ? 'text-emerald-600 dark:text-slate-400' : 'text-rose-600 dark:text-rose-400'">{{ pos.type === 'buy' ? 'LONG' : 'SHORT' }}</span>
                                <span class="text-[9px] font-black" :class="pos.pnl >= 0 ? 'text-emerald-600 dark:text-slate-400' : 'text-rose-600 dark:text-rose-400'">${{ pos.pnl.toFixed(2) }}</span>
                                <button @click="closePosition(pos)" class="text-[7px] font-black text-rose-500">CERRAR</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>



            <!-- Heatmap -->
            <div class="glass-panel rounded-[2.5rem] p-8 border border-[var(--ui-border)] bg-[var(--ui-surface)] shadow-xl">
                <div>
                    <h3 class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-wider">Mapa de Calor Horario</h3>
                    <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide mt-1 mb-6">Rendimiento (P&L) por hora del día basado en operaciones cerradas.</p>
                </div>
                <div class="grid grid-cols-12 md:grid-cols-24 gap-2">
                    <div v-for="stat in hourlyStats" :key="stat.hour" class="flex flex-col items-center" :title="`Hora ${stat.hour}h | Trades: ${stat.count} | P&L: $${stat.pnl.toFixed(2)}`">
                        <div :class="['w-full aspect-square rounded-xl transition-all', getHeatmapColor(stat)]"></div>
                        <span class="text-[7px] font-black text-slate-400 dark:text-white/40 mt-1">{{ stat.hour }}h</span>
                    </div>
                </div>
            </div>

            <!-- History -->
            <div class="glass-panel rounded-[2.5rem] border border-[var(--ui-border)] bg-[var(--ui-surface)] shadow-xl overflow-hidden">
                <div class="px-8 py-6 border-b border-[var(--ui-border)] flex justify-between items-center">
                    <h3 class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-wider">Historial</h3>
                    <div class="flex gap-4">
                        <button @click="exportToCSV" class="text-[9px] font-black text-indigo-400 uppercase">Exportar</button>
                        <button @click="resetHistory" class="text-[9px] font-black text-rose-400 uppercase">Resetear</button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead>
                            <tr class="bg-[var(--ui-surface-alt)] text-[var(--ui-text-muted)] font-black uppercase tracking-wide">
                                <th class="px-8 py-4">Fecha</th>
                                <th class="px-8 py-4">Tipo</th>
                                <th class="px-8 py-4">Entrada</th>
                                <th class="px-8 py-4">Salida</th>
                                <th class="px-8 py-4 text-right">P&L</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                            <tr v-for="trade in closedTrades.slice().reverse().slice(0, 50)" :key="trade.id" class="hover:bg-[var(--ui-surface-soft)]/50 text-[var(--ui-text)]">
                                <td class="px-8 py-4 text-[var(--ui-text-muted)]">{{ formatDateTime(trade.entryTime * 1000) }}</td>
                                <td class="px-8 py-4 font-black" :class="trade.type === 'buy' ? 'text-emerald-600 dark:text-slate-400' : 'text-rose-600 dark:text-rose-400'">{{ trade.type.toUpperCase() }}</td>
                                <td class="px-8 py-4 text-[var(--ui-text)]">${{ trade.entryPrice.toLocaleString() }}</td>
                                <td class="px-8 py-4 text-[var(--ui-text)]">${{ trade.exitPrice ? trade.exitPrice.toLocaleString() : '-' }}</td>
                                <td class="px-8 py-4 text-right font-black" :class="trade.pnl >= 0 ? 'text-emerald-600 dark:text-emerald-500' : 'text-rose-600 dark:text-rose-500'">${{ trade.pnl.toFixed(2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- API Keys Modal -->
            <div v-if="showApiModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                <div class="glass-panel w-full max-w-md rounded-[2.5rem] border border-white/10 p-8 space-y-6 bg-slate-900/90 shadow-2xl relative">
                    <button @click="showApiModal = false" class="absolute top-6 right-6 text-white/40 hover:text-white">
                        <FontAwesomeIcon :icon="icons.xmark" />
                    </button>
                    
                    <div>
                        <h3 class="text-lg font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <FontAwesomeIcon :icon="icons.gear" class="text-brand-500" />
                            <span>Binance API Keys</span>
                        </h3>
                        <p class="text-[9px] font-bold text-white/40 uppercase tracking-wide mt-1">
                            Encriptación segura AES-256-CBC en el servidor.
                        </p>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-[8px] font-black text-white/60 uppercase tracking-wide mb-1">API Key (Spot)</label>
                            <input v-model="binanceApiKey" type="text" class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-xs font-bold text-white outline-none focus:border-brand-500/50 transition-all" placeholder="Introduce tu Binance API Key">
                        </div>
                        <div>
                            <label class="block text-[8px] font-black text-white/60 uppercase tracking-wide mb-1">Secret Key</label>
                            <input v-model="binanceApiSecret" type="password" class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-xs font-bold text-white outline-none focus:border-brand-500/50 transition-all" placeholder="Introduce tu Secret Key">
                        </div>
                    </div>

                    <div class="flex items-center gap-2 py-1">
                        <input v-model="binanceIsTestnet" type="checkbox" id="is_testnet" class="rounded-xl bg-black/50 border-white/10 text-brand-500 focus:ring-brand-500/50">
                        <label for="is_testnet" class="text-[9px] font-black text-white/60 uppercase tracking-wide cursor-pointer select-none">
                            Usar Binance Testnet (Modo Pruebas)
                        </label>
                    </div>

                    <div class="flex flex-col gap-2 pt-2">
                        <button @click="saveApiKeys" class="w-full py-3 bg-brand-500 hover:bg-brand-600 text-black text-xs font-black uppercase rounded-2xl shadow-xl shadow-brand-500/20 transition-all">
                            Guardar Llaves de Acceso
                        </button>
                        <p class="text-[7px] font-bold text-rose-400 text-center uppercase tracking-wide">
                            ⚠️ Asegúrate de que tu clave API SOLO tenga permisos de Trading (Spot/Margin). NUNCA habilites Retiros (Withdrawals).
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, onMounted, onBeforeUnmount, computed, watch } from 'vue';
import { usePoll } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { library } from '@fortawesome/fontawesome-svg-core';
import { 
    faChartArea, faArrowTrendUp, faArrowTrendDown, faHistory, faShieldHalved, 
    faMoon, faFlask, faRotate, faCheck, faPause, faPlay, faForwardStep, 
    faXmark, faClapperboard, faDna, faFileCsv, faFire, faBolt, 
    faMicrochip, faCircleInfo, faTriangleExclamation, faLock, faUnlock, faSun, faInbox, faGear
} from '@fortawesome/free-solid-svg-icons';

import { useTradingAI } from '@/Composables/useTradingAI.js';
import { BayesianConfidence, classifyMarketRegime, getSignalContextFromCandles, validateSignal } from '@/Composables/useTradingAnalysis.js';
import { useTradingReplay } from '@/Composables/useTradingReplay.js';
import { useTradingControls } from '@/Composables/useTradingControls.js';
import { calculateATR } from '@/Composables/useTradingMath.js';

import { useTradingChart } from '@/Composables/useTradingChart.js';
import { useTradingData } from '@/Composables/useTradingData.js';
import { useTradingPersistence } from '@/Composables/useTradingPersistence.js';
import axios from 'axios';
const { formatDateTime } = useFormatters();

library.add(
    faChartArea, faArrowTrendUp, faArrowTrendDown, faHistory, faShieldHalved, 
    faMoon, faFlask, faRotate, faCheck, faPause, faPlay, faForwardStep, 
    faXmark, faClapperboard, faDna, faFileCsv, faFire, faBolt, 
    faMicrochip, faCircleInfo, faTriangleExclamation, faLock, faUnlock, faSun, faInbox, faGear
);

// --- CORE STATE ---
const props = defineProps({ initial_balance: Number, binance_balances: Array });
const timeframe = ref('15m');
const selectedSymbol = ref('BTCUSDT');
const chartContainer = ref(null);

function loadInitialState(key, defaultValue) {
    try {
        // Try new combined state first
        const combinedKey = `trading_sim_state_${selectedSymbol.value}_${timeframe.value}`;
        const combinedSaved = localStorage.getItem(combinedKey);
        if (combinedSaved) {
            const state = JSON.parse(combinedSaved);
            if (state[key] !== undefined) return state[key];
        }
        
        // Fallback to old individual keys
        const oldKeyMap = {
            'balance': 'balance',
            'positions': 'positions',
            'closedTrades': 'closed_trades',
            'trainingSamples': 'samples',
            'trainingWins': 'wins',
            'strategyWeights': 'weights'
        };
        const oldKey = oldKeyMap[key] || key;
        const saved = localStorage.getItem(`trading_${oldKey}`);
        return saved ? JSON.parse(saved) : defaultValue;
    } catch (e) { return defaultValue; }
}

const balance = ref(loadInitialState('balance', props.initial_balance));
const orderAmount = ref(100);
const currentPrice = ref(0);
const priceChange = ref(0);
const positions = ref(loadInitialState('positions', []));
const candles = ref([]);
const closedTrades = ref(loadInitialState('closedTrades', []));
const totalPnL = computed(() => {
    return closedTrades.value.reduce((sum, trade) => sum + (trade.pnl || 0), 0);
});
const sentimentScore = ref(0.5);
const trainingSamples = ref(loadInitialState('trainingSamples', 0));
const trainingWins = ref(loadInitialState('trainingWins', 0));
const strategyConfidence = ref(0);
const isChartLocked = ref(true);
const latestSignal = ref('wait');
const autoTradingEnabled = ref(true);
const isDeepTraining = ref(false);
const isReplayMode = ref(false);
const replayIndex = ref(0);
const replaySpeed = ref(1000);
const isNightModeActive = ref(false);
const htfTrend = ref('neutral');
const currentMarketRegime = ref({ regime: 'unknown', confidence: 0 });
const strategyWeights = ref(loadInitialState('strategyWeights', { ema: 1.2, rsi: 0.8, bb: 1.0, volume: 1.5, macd: 0.5 }));
const riskSettings = ref({ sessionStartingBalance: props.initial_balance, dailyLossLimit: 5 });
const consecutiveLosses = ref(0);
const lastCircuitBreakerTime = ref(0);
const backtestMetrics = ref({ trades: 0, wins: 0, losses: 0, netProfit: 0, netPercent: 0, winRate: 0, maxDrawdown: 0, expectancy: 0 });
const isSyncing = ref(false);
const isNeuralTraining = ref(false);

const showApiModal = ref(false);
const binanceApiKey = ref('');
const binanceApiSecret = ref('');
const binanceIsTestnet = ref(true);
const realTradingEnabled = ref(true);
const realBalance = ref(0);

// Polling de balance automático a través de Inertia 3 en lugar de setInterval
usePoll(60000, { only: ['binance_balances'] });

watch(() => props.binance_balances, (newBalances) => {
    if (realTradingEnabled.value && newBalances && Array.isArray(newBalances)) {
        const usdt = newBalances.find(b => b.asset === 'USDT');
        if (usdt) {
            realBalance.value = parseFloat(usdt.free);
        }
    }
}, { immediate: true, deep: true });

const icons = {
    bolt: ['fas', 'bolt'],
    lock: ['fas', 'lock'],
    unlock: ['fas', 'unlock'],
    pause: ['fas', 'pause'],
    play: ['fas', 'play'],
    forwardStep: ['fas', 'forward-step'],
    xmark: ['fas', 'xmark'],
    clapperboard: ['fas', 'clapperboard'],
    chartArea: ['fas', 'chart-area'],
    arrowTrendUp: ['fas', 'arrow-trend-up'],
    arrowTrendDown: ['fas', 'arrow-trend-down'],
    sun: ['fas', 'sun'],
    moon: ['fas', 'moon'],
    fire: ['fas', 'fire'],
    history: ['fas', 'history'],
    fileCsv: ['fas', 'file-csv'],
    inbox: ['fas', 'inbox'],
    gear: ['fas', 'gear'],
    microchip: ['fas', 'microchip']
};


const symbols = [
    { label: 'Bitcoin (BTC)', value: 'BTCUSDT' },
    { label: 'Ethereum (ETH)', value: 'ETHUSDT' },
    { label: 'Solana (SOL)', value: 'SOLUSDT' },
    { label: 'Binance Coin (BNB)', value: 'BNBUSDT' }
];

const bayesianConfidence = new BayesianConfidence();
const trainingAccuracy = computed(() => trainingSamples.value > 0 ? ((trainingWins.value / trainingSamples.value) * 100).toFixed(1) : '0.0');

watch(candles, (newCandles) => {
    if (newCandles && newCandles.length > 20) {
        currentMarketRegime.value = classifyMarketRegime(newCandles);
    }
}, { deep: true });

// --- COMPOSABLES ---
const { saveState, loadState, syncToBackend, syncExperience, fetchWeights } = useTradingPersistence({
    balance, positions, closedTrades, trainingSamples, trainingWins, 
    strategyWeights, selectedSymbol, timeframe, riskSettings, strategyConfidence, showToast: (m, t) => showToast(m, t), route
});


const { initChart, updateIndicators, updateMarkers, syncCandleSeries, getChart, getCandleSeries, getVolumeSeries } = useTradingChart(chartContainer, {
    candles, closedTrades, positions, showToast: (m, t) => showToast(m, t), stopLossPercent: 0.015, takeProfitPercent: 0.025
});

const { detectLiquidityZones, runNeuralNetworkTraining } = useTradingAI({
    candles, strategyWeights, isDeepTraining, trainingSamples, trainingWins, strategyConfidence,
    showToast: (m, t) => showToast(m, t), saveState,
    route, selectedSymbol, timeframe, trainingAccuracy, isNeuralTraining
});


const getSignalContext = () => getSignalContextFromCandles(candles.value, {
    strategyWeights: strategyWeights.value, currentMarketRegime: currentMarketRegime.value, isDeepTraining: isDeepTraining.value, detectLiquidityZones
});

const { applyRiskProtection, placeOrder, closePosition, updatePositionsPnL } = useTradingControls({
    balance, positions, closedTrades, currentPrice, orderAmount, strategyWeights, bayesianConfidence, 
    strategyConfidence,
    isTradingLocked: computed(() => false), htfTrend, candles, isReplayMode, showToast: (m, t) => showToast(m, t), 
    saveState, updateMarkers, syncExperience, consecutiveLosses, lastCircuitBreakerTime, 
    autoTradingEnabled, isNightModeActive, updateTotalPnL: () => {}, syncToBackend, 
    maxConsecutiveLosses: 3, commissionPercent: 0.001, slippagePercent: 0.0008, 
    maxPositionExposure: 0.3, stopLossPercent: 0.015, takeProfitPercent: 0.025, 
    calculateSafeOrderAmount: () => balance.value < 10 ? 0 : Math.max(11, balance.value * 0.1), getSignalContext, calculateATR: (c) => calculateATR(c, 14), props,
    realTradingEnabled, selectedSymbol
});

const maybeAutoBuy = () => {
    if (positions.value.length > 0) return; 
    
    const signal = latestSignal.value;
    if (signal === 'wait') return;
    
    // Gate de confianza: la IA debe tener al menos 60% de certeza
    const conf = strategyConfidence.value;
    if (conf < 0.6 && conf > 0.4) return; // Zona de indecisión, no operar
    
    // Validar coherencia de la señal con indicadores macro
    const ctx = getSignalContext();
    if (ctx) {
        const validation = validateSignal(ctx, { htfTrend });
        if (!validation.isValid && !isReplayMode.value) return; // Solo operar señales validadas (excepto en replay)
    }
    
    // Spot Trading: Solo podemos abrir posiciones LONG (comprar barato, vender caro)
    // No podemos hacer Short Selling en Spot.
    if (conf > 0.6 && signal === 'buy') {
        placeOrder('buy');
    } else if (signal === 'sell' && isReplayMode.value) {
        // En modo simulación (replay) sí podemos probar Shorts
        placeOrder('sell');
    }
};

const { connectWebSocket, loadHistoricalCandles, updateHTFTrend, closeSocket } = useTradingData({
    selectedSymbol, timeframe, candles, currentPrice, priceChange, 
    candleSeries: computed(() => getCandleSeries()), volumeSeries: computed(() => getVolumeSeries()), 
    updateMarkers, updateIndicators, trainStrategy: () => runNeuralNetworkTraining(false), getSignalContext, latestSignal, 
    strategyConfidence, trainingAccuracy, 
    maybeAutoClose: () => {
        // Las reglas de Stop Loss / Take Profit ya se ejecutan solas en useTradingControls.js
    }, 
    maybeAutoBuy, 
    syncExperience, updatePositionsPnL, updateTotalPnL: () => {}, 
    route, showToast: (m, t) => showToast(m, t), syncCandleSeries, htfTrend
});

const { startReplay, pauseReplay, stopReplay, stepReplay, playReplay } = useTradingReplay({
    candles, currentPrice, isReplayMode, replayIndex, replaySpeed, 
    candleSeries: computed(() => getCandleSeries()), volumeSeries: computed(() => getVolumeSeries()), 
    updateMarkers, updatePositionsPnL, autoTradingEnabled, 
    trainStrategy: () => runNeuralNetworkTraining(false),
    maybeAutoBuy, getSignalContext, latestSignal, strategyConfidence,
    placeOrder, showToast: (m, t) => showToast(m, t), connectWebSocket, closeSocket

});


// --- UI HELPERS ---

const showToast = (message, type = 'info') => {
    const toast = document.createElement('div');
    toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded-2xl shadow-xl z-[9999] bg-slate-800 text-white border border-white/10`;
    toast.innerHTML = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
};

const resetHistory = () => {
    if (confirm('¿Resetear historial?')) {
        balance.value = props.initial_balance;
        positions.value = [];
        closedTrades.value = [];
        trainingSamples.value = 0;
        trainingWins.value = 0;
        saveState();
        updateMarkers();
    }
};

const toggleChartLock = () => {
    isChartLocked.value = !isChartLocked.value;
    const chart = getChart();
    if (chart) {
        chart.applyOptions({ handleScroll: !isChartLocked.value, handleScale: !isChartLocked.value });
    }
};


const openApiSettingsModal = async () => {
    showApiModal.value = true;
    try {
        const response = await window.axios.get(route('trading.get-api-keys'));
        if (response.data && response.data.has_keys) {
            binanceApiKey.value = '********';
            binanceApiSecret.value = '********';
            binanceIsTestnet.value = response.data.is_testnet;
        } else {
            binanceApiKey.value = '';
            binanceApiSecret.value = '';
            binanceIsTestnet.value = true;
        }
    } catch (e) {
        binanceApiKey.value = '';
        binanceApiSecret.value = '';
        binanceIsTestnet.value = true;
    }
};

const saveApiKeys = async () => {
    try {
        await window.axios.post(route('trading.save-api-keys'), {
            binance_key: binanceApiKey.value,
            binance_secret: binanceApiSecret.value,
            is_testnet: binanceIsTestnet.value
        });
        showToast('API Keys guardadas con éxito (Encriptadas AES-256)', 'success');
        showApiModal.value = false;
    } catch (e) {
        showToast('Error al guardar API Keys', 'error');
    }
};

// --- COMPUTED ---
const tradingStats = computed(() => ({
    winRate: closedTrades.value.length > 0 ? (closedTrades.value.filter(t => t.pnl > 0).length / closedTrades.value.length) * 100 : 0,
    profit: closedTrades.value.reduce((sum, t) => sum + t.pnl, 0),
    count: closedTrades.value.length
}));

const sentimentLabel = computed(() => {
    const val = sentimentScore.value * 100;
    if (val < 25) return 'Miedo Extremo';
    if (val < 45) return 'Miedo';
    if (val < 55) return 'Neutral';
    if (val < 75) return 'Codicia';
    return 'Codicia Extrema';
});

const marketRegimeLabel = computed(() => currentMarketRegime.value?.regime || 'Lateral');
const marketRegimeClass = computed(() => 'text-slate-500 dark:text-white/40');
const aiCredibility = computed(() => bayesianConfidence.getCredibility());
const latestSignalLabel = computed(() => ({'buy': 'Comprar', 'sell': 'Salir', 'wait': 'Esperar'}[latestSignal.value] || 'Esperar'));
const trainingProgress = computed(() => Math.min(100, (trainingSamples.value / 200) * 100));
const isTraining = computed(() => trainingSamples.value < 200);
const trainingAccuracyLabel = computed(() => `${trainingAccuracy.value}%`);
const dailyPnL = computed(() => ((balance.value - riskSettings.value.sessionStartingBalance) / riskSettings.value.sessionStartingBalance) * 100);
const riskLabel = computed(() => strategyConfidence.value > 0.7 ? 'Bajo' : 'Medio');

const hourlyStats = computed(() => {
    const hours = Array.from({ length: 24 }, (_, i) => ({ hour: i, pnl: 0, count: 0 }));
    closedTrades.value.forEach(t => {
        const hour = new Date(t.entryTime * 1000).getHours();
        hours[hour].pnl += t.pnl;
        hours[hour].count++;
    });
    return hours;
});

const getHeatmapColor = (stat) => {
    if (stat.count === 0) return 'bg-white/5';
    if (stat.pnl > 0) return 'bg-brand-500/60 shadow-sm';
    if (stat.pnl < 0) return 'bg-brand-500/60 shadow-sm';
    return 'bg-white/10';
};

// --- LIFECYCLE ---
const fetchSentiment = async () => {
    try {
        const res = await fetch('https://api.alternative.me/fng/');
        if (res.ok) {
            const json = await res.json();
            if (json.data && json.data.length > 0) {
                sentimentScore.value = parseFloat(json.data[0].value) / 100;
            }
        }
    } catch (e) { console.warn('No se pudo cargar el sentimiento general'); }
};

onMounted(async () => {
    initChart();
    loadState();
    
    // Sincronizar pesos del servidor (VPS)
    isSyncing.value = true;
    await fetchWeights();
    isSyncing.value = false;
    
    fetchSentiment();
    loadHistoricalCandles();
    connectWebSocket();
});


onBeforeUnmount(() => {
    closeSocket();
});

watch([selectedSymbol, timeframe], async () => {
    isSyncing.value = true;
    await fetchWeights();
    isSyncing.value = false;
    
    loadHistoricalCandles();
    connectWebSocket();
});
</script>

<style scoped>
.glass-panel {
    background: var(--ui-surface, #ffffff);
    border-color: var(--ui-border, #f1f5f9);
}

:global(.dark) .glass-panel {
    background: var(--ui-surface, rgba(15, 23, 42, 0.8));
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-color: var(--ui-border, rgba(255, 255, 255, 0.05));
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 20px;
}
</style>
