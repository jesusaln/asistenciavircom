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
    <aside class="w-[480px] min-w-[320px] bg-slate-50 dark:bg-slate-950/50 border-l border-slate-200 dark:border-white/10 flex flex-col shadow-[-12px_0_40px_-12px_rgba(0,0,0,0.15)] dark:shadow-[-12px_0_40px_-12px_rgba(0,0,0,0.55)] transition-colors duration-500">
        <div class="p-10 space-y-8 flex-1 overflow-y-auto no-scrollbar">
            <!-- Totals Card -->
            <div class="bg-white dark:bg-gradient-to-br dark:from-purple-600/15 dark:to-slate-900/40 border-2 border-slate-200 dark:border-purple-500/25 rounded-[3rem] p-10 text-center relative overflow-hidden group shadow-2xl shadow-purple-950/5 dark:shadow-purple-950/20 ring-1 ring-white/5 transition-all">
                <div class="absolute inset-0 bg-gradient-to-br from-purple-500/10 dark:from-purple-500/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <p class="text-[10px] font-black text-purple-600 dark:text-purple-400 uppercase tracking-[0.4em] mb-4">Total a Liquidar</p>
                <h2 class="text-7xl font-black tracking-tighter dark:text-white text-slate-900 drop-shadow-sm transition-all transform group-hover:scale-105 duration-500">
                    {{ formatCurrency(totals.total) }}
                </h2>
                <div class="mt-6 flex items-center justify-center gap-3 text-slate-400 dark:text-slate-500 text-[10px] font-black uppercase tracking-wide">
                    <span class="bg-slate-100 dark:bg-white/5 px-3 py-1 rounded-full border border-slate-200 dark:border-white/5">{{ selectedItemsCount }} Artículos</span>
                    <span class="w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-700"></span>
                    <span>IVA {{ defaults.ivaPorcentaje }}%</span>
                </div>
            </div>

            <div class="space-y-8">
                <!-- Báscula Indicator -->
                <Transition name="fade">
                    <div v-if="scaleActive" class="flex items-center justify-between p-6 bg-emerald-50 dark:bg-emerald-950/10 rounded-3xl border border-emerald-100 dark:border-emerald-500/20 shadow-inner">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></div>
                            <span class="text-[10px] font-black text-emerald-600 dark:text-emerald-500 uppercase tracking-wide">Báscula Activa</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400">
                                {{ scaleWeight.toFixed(3) }} <span class="text-xs">kg</span>
                            </span>
                            <button @click="emit('try-weight')" class="w-10 h-10 flex items-center justify-center bg-emerald-600 text-white rounded-xl hover:bg-emerald-500 transition-all shadow-xl shadow-emerald-500/20 active:scale-90">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            </button>
                        </div>
                    </div>
                </Transition>

                <!-- Action Buttons -->
                <div class="flex gap-4">
                    <button 
                        @click="emit('park-sale')"
                        class="flex-1 p-6 bg-orange-50 dark:bg-brand-500/5 hover:bg-brand-500 hover:text-white text-brand-600 dark:text-orange-400 rounded-3xl border border-orange-200 dark:border-brand-500/20 transition-all group flex flex-col items-center justify-center gap-3 shadow-sm hover:shadow-brand-500/30 active:scale-95"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-brand-100 dark:bg-brand-500/10 flex items-center justify-center group-hover:bg-white/20 transition-all">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <span class="text-[10px] font-black uppercase tracking-[0.2em]">Pausar (F8)</span>
                    </button>

                    <button 
                        @click="emit('open-parked')"
                        class="flex-1 p-6 bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 dark:text-slate-400 text-slate-600 hover:text-slate-900 dark:hover:text-white rounded-3xl border border-slate-200 dark:border-white/5 transition-all group flex flex-col items-center justify-center gap-3 relative shadow-sm active:scale-95"
                    >
                        <div v-if="parkedSalesCount > 0" class="absolute top-4 right-4 w-6 h-6 bg-rose-600 rounded-full flex items-center justify-center text-[10px] font-black text-white shadow-xl shadow-rose-500/50 animate-bounce">
                            {{ parkedSalesCount }}
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-slate-200 dark:bg-white/5 flex items-center justify-center group-hover:bg-white/20 transition-all">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <span class="text-[10px] font-black uppercase tracking-[0.2em]">En Espera (F9)</span>
                    </button>
                </div>

                <!-- Shortcuts Widget -->
                <div class="bg-white dark:bg-slate-900/50 rounded-[2.5rem] p-8 border border-slate-200 dark:border-white/5 shadow-inner">
                    <h4 class="text-[10px] font-black text-slate-400 dark:text-slate-600 uppercase tracking-[0.3em] mb-6 flex items-center gap-3">
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                        Asistente de Atajos
                    </h4>
                    <div class="grid grid-cols-2 gap-y-5 gap-x-8">
                        <div v-for="(label, key) in { 'F1': 'Buscar', 'F2': 'Clientes', 'F3': 'Nueva Tab', 'F5': 'Cobrar', 'F8': 'Pausar', 'F9': 'En Espera', 'F10': 'Gastos' }" :key="key" class="flex items-center justify-between group/key">
                            <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide group-hover/key:text-purple-500 transition-colors">{{ label }}</span>
                            <kbd class="px-2 py-1 bg-slate-100 dark:bg-slate-950 rounded-xl text-[10px] font-black text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-white/5 shadow-sm min-w-[32px] text-center">{{ key }}</kbd>
                        </div>
                        
                        <div class="col-span-2 mt-4 pt-6 border-t border-slate-100 dark:border-white/5">
                            <button @click="emit('prepare-cierre')" class="w-full flex items-center justify-between p-3 rounded-2xl hover:bg-rose-50 dark:hover:bg-rose-950/20 group/corte transition-all border border-transparent hover:border-rose-200 dark:hover:border-rose-500/20">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-rose-100 dark:bg-rose-500/10 flex items-center justify-center text-rose-600 dark:text-rose-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                    </div>
                                    <span class="text-[10px] font-black text-rose-600 dark:text-rose-500 uppercase tracking-wide">Corte de Caja</span>
                                </div>
                                <kbd class="px-2 py-1 bg-rose-100 dark:bg-rose-500/20 text-rose-600 dark:text-rose-500 rounded-xl text-[10px] font-black border border-rose-200 dark:border-rose-500/30">F12</kbd>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Primary Checkout Button -->
        <div class="p-10 bg-white/50 dark:bg-slate-950/80 backdrop-blur-2xl border-t border-slate-200 dark:border-white/5">
            <button 
                @click="emit('open-payment')"
                class="w-full h-28 bg-purple-600 hover:bg-purple-500 text-white rounded-[2.5rem] shadow-[0_20px_50px_rgba(147,51,234,0.3)] dark:shadow-[0_20px_50px_rgba(147,51,234,0.4)] flex items-center justify-center gap-6 transition-all active:scale-[0.97] transform hover:-translate-y-1"
            >
                <div class="w-16 h-16 rounded-[1.5rem] bg-white/20 flex items-center justify-center backdrop-blur-md">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div class="text-left">
                    <p class="text-xs font-black uppercase tracking-[0.3em] opacity-80 mb-1">Finalizar Operación</p>
                    <p class="text-3xl font-black tracking-tighter">COBRAR (F5)</p>
                </div>
            </button>
        </div>
    </aside>
</template>
