<script setup>
const props = defineProps({
    totals: { type: Object, required: true },
    selectedItemsCount: { type: Number, default: 0 },
    defaults: { type: Object, required: true },
    formatCurrency: { type: Function, required: true },
    scaleWeight: { type: Number, default: 0 },
    scaleActive: { type: Boolean, default: false },
    showCorteQuick: { type: Boolean, default: true },
    parkedSalesCount: { type: Number, default: 0 },
});

const emit = defineEmits(['try-weight', 'open-payment', 'prepare-cierre', 'park-sale', 'open-parked']);
</script>

<template>
    <aside class="w-[450px] min-w-[300px] bg-slate-950/50 border-l border-white/10 flex flex-col shadow-[-12px_0_40px_-12px_rgba(0,0,0,0.55)]">
        <div class="p-8 space-y-8 flex-1">
            <div class="bg-gradient-to-br from-purple-600/15 to-slate-900/40 border border-purple-500/25 rounded-[2.5rem] p-8 text-center relative overflow-hidden group shadow-lg shadow-purple-950/20 ring-1 ring-white/5">
                <div class="absolute inset-0 bg-gradient-to-br from-purple-500/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <p class="text-xs font-black text-purple-400 uppercase tracking-[0.3em] mb-4">Total a Pagar</p>
                <h2 class="text-6xl font-black tracking-tighter">{{ formatCurrency(totals.total) }}</h2>
                <div class="mt-4 flex items-center justify-center gap-2 text-slate-500 text-[10px] font-bold">
                    <span>Artículos: {{ selectedItemsCount }}</span>
                    <span class="w-1 h-1 rounded-full bg-slate-700"></span>
                    <span>IVA ({{ defaults.ivaPorcentaje }}%): {{ formatCurrency(totals.iva) }}</span>
                </div>
            </div>

            <div class="space-y-4">
                <div v-if="scaleActive" class="flex items-center justify-between p-4 bg-slate-800/50 rounded-2xl">
                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Báscula</span>
                    <div class="flex items-center gap-3">
                        <span class="animate-pulse text-emerald-400 text-lg font-black font-mono">
                            {{ scaleWeight.toFixed(3) }} kg
                        </span>
                        <button @click="emit('try-weight')" class="p-2 bg-purple-500 rounded-lg hover:bg-purple-600 transition-all shadow-lg shadow-purple-500/20">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        </button>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button 
                        @click="emit('park-sale')"
                        class="flex-1 p-4 bg-orange-500/10 hover:bg-orange-500 hover:text-white text-orange-400 rounded-2xl border border-orange-500/20 transition-all group flex flex-col items-center justify-center gap-2"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span class="text-[10px] font-black uppercase tracking-wider">Pausar (F8)</span>
                    </button>

                    <button 
                        @click="emit('open-parked')"
                        class="flex-1 p-4 bg-slate-800 hover:bg-slate-700 rounded-2xl border border-white/5 transition-all group flex flex-col items-center justify-center gap-2 relative"
                    >
                        <div v-if="parkedSalesCount > 0" class="absolute top-2 right-2 w-5 h-5 bg-orange-500 rounded-full flex items-center justify-center text-[10px] font-black text-white shadow-lg shadow-orange-500/50 animate-bounce">
                            {{ parkedSalesCount }}
                        </div>
                        <svg class="w-6 h-6 text-slate-400 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 group-hover:text-white">Espera (F9)</span>
                    </button>
                </div>

                <div class="bg-slate-800/30 rounded-2xl p-6 border border-white/5">
                    <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-4">Atajos Rápidos</h4>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="flex items-center gap-3 text-xs">
                            <kbd class="w-7 h-7 flex items-center justify-center bg-slate-900 rounded font-black border border-white/10">F1</kbd>
                            <span class="text-slate-400">Buscar</span>
                        </div>
                        <div class="flex items-center gap-3 text-xs">
                            <kbd class="w-7 h-7 flex items-center justify-center bg-slate-900 rounded font-black border border-white/10">F2</kbd>
                            <span class="text-slate-400">Clientes</span>
                        </div>
                        <div class="flex items-center gap-3 text-xs">
                            <kbd class="w-7 h-7 flex items-center justify-center bg-slate-900 rounded font-black border border-white/10">F5</kbd>
                            <span class="text-slate-400">Cobrar</span>
                        </div>
                        <div class="flex items-center gap-3 text-xs">
                            <kbd class="w-7 h-7 flex items-center justify-center bg-slate-900 rounded font-black border border-white/10">F7</kbd>
                            <span class="text-slate-400">Pesar</span>
                        </div>
                        <div class="flex items-center gap-3 text-xs">
                            <kbd class="w-8 h-7 flex items-center justify-center bg-slate-900 rounded font-black border border-purple-500/30 text-purple-400">F10</kbd>
                            <span class="text-purple-400 font-bold">Gastos</span>
                        </div>
                        <div class="flex items-center gap-3 text-xs text-rose-400">
                            <kbd class="w-8 h-7 flex items-center justify-center bg-slate-900 rounded font-black border border-rose-500/30">SUPR</kbd>
                            <span class="font-bold">Limpiar</span>
                        </div>
                        <div class="flex items-center gap-3 text-xs">
                            <kbd class="w-8 h-7 flex items-center justify-center bg-slate-900 rounded font-black border border-white/10">ESC</kbd>
                            <span class="text-slate-400">Cerrar</span>
                        </div>
                        <div v-if="showCorteQuick" class="flex items-center gap-3 text-xs col-span-2 mt-2 pt-2 border-t border-white/5 cursor-pointer hover:bg-white/5 rounded-lg transition-all p-1" @click="emit('prepare-cierre')">
                            <kbd class="w-8 h-7 flex items-center justify-center bg-rose-500/20 text-rose-400 rounded font-black border border-rose-500/30">F12</kbd>
                            <span class="text-rose-400 font-bold uppercase">Corte de Caja</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-8 bg-slate-900/50 backdrop-blur-md border-t border-white/5">
            <button 
                @click="emit('open-payment')"
                class="w-full h-24 bg-purple-600 hover:bg-purple-500 text-white rounded-[2rem] shadow-2xl shadow-purple-600/30 flex items-center justify-center gap-4 transition-all active:scale-[0.98]"
            >
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <div class="text-left">
                    <p class="text-sm font-black uppercase tracking-widest opacity-80">COBRAR VENTA</p>
                    <p class="text-3xl font-black">PRESIONA F5</p>
                </div>
            </button>
        </div>
    </aside>
</template>
