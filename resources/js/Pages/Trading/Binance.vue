<template>
    <AppLayout title="Binance Live">
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-black text-[var(--ui-text)] uppercase tracking-wider">
                        Binance <span class="text-brand-500">Live</span>
                    </h2>
                    <p class="text-xs font-bold text-[var(--ui-text-soft)] uppercase tracking-wide mt-1">
                        Seguimiento en tiempo real de criptoactivos
                    </p>
                </div>
            </div>
        </template>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <div v-for="ticker in tickers" :key="ticker.symbol" class="glass-panel p-6 rounded-[2rem] border border-[var(--ui-border)] bg-gradient-to-br from-[var(--ui-surface)] to-[var(--ui-surface-soft)] shadow-xl hover:border-[var(--ui-accent)]/30 transition-all duration-200">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center font-black text-brand-500 text-xs">
                                {{ ticker.symbol.replace('USDT', '') }}
                            </div>
                            <div>
                                <h3 class="text-sm font-black text-[var(--ui-text)] uppercase tracking-wider">{{ ticker.symbol }}</h3>
                                <p class="text-[9px] font-bold text-[var(--ui-text-soft)] uppercase tracking-wide">Binance Spot</p>
                            </div>
                        </div>
                        <div :class="ticker.priceChangePercent >= 0 ? 'bg-brand-500/10 text-emerald-500' : 'bg-brand-500/10 text-rose-500'" class="px-3 py-1 rounded-full text-[10px] font-black tracking-wide">
                            {{ ticker.priceChangePercent >= 0 ? '+' : '' }}{{ ticker.priceChangePercent }}%
                        </div>
                    </div>

                    <div class="mt-6">
                        <span class="text-[10px] font-black text-[var(--ui-text-muted)] uppercase tracking-wide">Precio Actual</span>
                        <div class="text-2xl font-black text-[var(--ui-text)] tracking-tight">
                            ${{ formatPrice(ticker.curPrice, ticker.symbol) }}
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-[var(--ui-border)] grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-[8px] font-black text-[var(--ui-text-muted)] uppercase tracking-wide">Máximo 24h</span>
                            <p class="text-[10px] font-bold text-[var(--ui-text)]">${{ formatPrice(ticker.highPrice, ticker.symbol) }}</p>
                        </div>
                        <div>
                            <span class="text-[8px] font-black text-[var(--ui-text-muted)] uppercase tracking-wide">Mínimo 24h</span>
                            <p class="text-[10px] font-bold text-[var(--ui-text)]">${{ formatPrice(ticker.lowPrice, ticker.symbol) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, onMounted, onBeforeUnmount } from 'vue';

const tickers = ref([
    { symbol: 'BTCUSDT', curPrice: 0, priceChangePercent: 0, highPrice: 0, lowPrice: 0 },
    { symbol: 'ETHUSDT', curPrice: 0, priceChangePercent: 0, highPrice: 0, lowPrice: 0 },
    { symbol: 'BNBUSDT', curPrice: 0, priceChangePercent: 0, highPrice: 0, lowPrice: 0 },
    { symbol: 'SOLUSDT', curPrice: 0, priceChangePercent: 0, highPrice: 0, lowPrice: 0 },
    { symbol: 'ADAUSDT', curPrice: 0, priceChangePercent: 0, highPrice: 0, lowPrice: 0 },
    { symbol: 'XRPUSDT', curPrice: 0, priceChangePercent: 0, highPrice: 0, lowPrice: 0 },
    { symbol: 'DOTUSDT', curPrice: 0, priceChangePercent: 0, highPrice: 0, lowPrice: 0 },
    { symbol: 'DOGEUSDT', curPrice: 0, priceChangePercent: 0, highPrice: 0, lowPrice: 0 }
]);

let socket = null;

const formatPrice = (price, symbol) => {
    if (!price) return '0.00';
    const p = parseFloat(price);
    if (p < 1) return p.toFixed(4);
    if (p < 10) return p.toFixed(3);
    return p.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

onMounted(() => {
    // Multi-stream websocket for 24h ticker info
    const streams = tickers.value.map(t => `${t.symbol.toLowerCase()}@ticker`).join('/');
    socket = new WebSocket(`wss://stream.binance.com:9443/ws/${streams}`);

    socket.onmessage = (event) => {
        const data = JSON.parse(event.data);
        const index = tickers.value.findIndex(t => t.symbol === data.s);
        if (index !== -1) {
            tickers.value[index] = {
                symbol: data.s,
                curPrice: data.c,
                priceChangePercent: data.P,
                highPrice: data.h,
                lowPrice: data.l
            };
        }
    };
});

onBeforeUnmount(() => {
    if (socket) socket.close();
});
</script>
