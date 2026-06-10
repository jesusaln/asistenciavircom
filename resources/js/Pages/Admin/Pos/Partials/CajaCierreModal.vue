<script setup>
const props = defineProps({
    show: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    denominaciones: { type: Object, default: () => ({}) },
    closingDetails: { type: Object, default: null },
    totalDeclarado: { type: Number, default: 0 },
    formatCurrency: { type: Function, required: true },
});

const emit = defineEmits(['update:show', 'update-denominacion', 'confirm']);

const close = () => emit('update:show', false);

const onInputDenom = (key, value) => {
    const parsed = Number(value);
    emit('update-denominacion', {
        key,
        value: Number.isFinite(parsed) ? parsed : 0,
    });
};

const bills = ['500', '200', '100', '50', '20'];
const coins = { '20': 'moneda_20', '10': 'moneda_10', '5': 'moneda_5', '2': 'moneda_2', '1': 'moneda_1', '.50': 'moneda_050' };
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/90 backdrop-blur-md">
            <div class="bg-slate-900 border border-slate-700 rounded-3xl w-full max-w-5xl shadow-2xl overflow-hidden flex flex-col max-h-[95vh] animate-in zoom-in-95 duration-200">
                <div class="p-6 border-b border-white/5 flex justify-between items-center bg-slate-950/50 backdrop-blur-xl">
                    <div>
                        <h2 class="text-2xl font-black text-white flex items-center gap-2">
                            <span class="w-3 h-8 bg-gradient-to-b from-rose-500 to-rose-700 rounded-full shadow-xl shadow-rose-500/20"></span>
                            Cierre de Caja
                        </h2>
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-wide mt-1 ml-6">Arqueo de Efectivo y Corte</p>
                    </div>
                    <button @click="close" class="text-slate-400 hover:text-white hover:bg-slate-800 p-2 rounded-xl transition-all">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-8 grid grid-cols-1 lg:grid-cols-12 gap-8 custom-scrollbar">
                    <div class="lg:col-span-7 space-y-6">
                        <div class="bg-slate-800/30 rounded-2xl p-6 border border-white/5">
                            <h3 class="flex items-center gap-2 text-emerald-400 font-bold uppercase text-xs tracking-wide mb-6">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                Billetes
                            </h3>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                                <div v-for="denom in bills" :key="denom" class="relative group">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-slate-500 font-mono text-xs font-bold">$</span>
                                        <span class="text-slate-300 font-mono text-sm font-bold ml-0.5">{{ denom }}</span>
                                    </div>
                                    <input 
                                        type="number" 
                                        :value="denominaciones[denom]" 
                                        class="w-full bg-slate-950 border border-slate-700 rounded-xl pl-16 pr-4 py-3 text-right text-white font-mono font-bold focus:border-emerald-500 focus:ring-1 focus:ring-brand-500/50 transition-all group-hover:bg-slate-900" 
                                        min="0" 
                                        placeholder="0"
                                        @input="onInputDenom(denom, $event.target.value)"
                                        @focus="$event.target.select()"
                                    >
                                    <div class="absolute -top-2 right-2 bg-slate-800 text-[9px] text-slate-400 px-1.5 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                                        {{ formatCurrency((denominaciones[denom] || 0) * Number(denom)) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-800/30 rounded-2xl p-6 border border-white/5">
                            <h3 class="flex items-center gap-2 text-brand-400 font-bold uppercase text-xs tracking-wide mb-6">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Monedas
                            </h3>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                                <div v-for="(label, key) in coins" :key="key" class="relative group">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-slate-500 font-mono text-xs font-bold">$</span>
                                        <span class="text-slate-300 font-mono text-sm font-bold ml-0.5">{{ key }}</span>
                                    </div>
                                    <input 
                                        type="number" 
                                        :value="denominaciones[label]" 
                                        class="w-full bg-slate-950 border border-slate-700 rounded-xl pl-16 pr-4 py-3 text-right text-white font-mono font-bold focus:border-brand-500 focus:ring-1 focus:ring-brand-500/50 transition-all group-hover:bg-slate-900" 
                                        min="0" 
                                        placeholder="0"
                                        @input="onInputDenom(label, $event.target.value)"
                                        @focus="$event.target.select()"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-5 flex flex-col gap-6">
                        <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 shadow-xl relative overflow-hidden">
                            <div class="absolute top-0 right-0 p-3 opacity-10">
                                <svg class="w-32 h-32 text-purple-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.67v-1.93c-1.71-.36-3.15-1.46-3.27-3.4h1.96c.1 1.05.82 1.87 2.61 1.87 1.61 0 2.67-.81 2.67-2.13 0-1.09-.77-1.91-2.73-2.11L12 11.39c-2.68-.27-3.98-1.62-3.98-3.55 0-2.13 1.56-3.66 3.98-3.97V2H13.41v1.96c1.61.32 2.94 1.48 3 3.39h-1.92c-.1-1.22-.85-1.96-2.67-1.96-1.55 0-2.31.75-2.31 1.76 0 1.09.91 1.76 2.76 2.02l.62.08c2.81.38 4.12 1.63 4.12 3.82 0 2.22-1.68 3.73-4.5 4.02z"/></svg>
                            </div>

                            <h3 class="text-purple-400 font-bold uppercase text-xs tracking-wide mb-6 relative z-10">Resumen Financiero</h3>

                            <div class="space-y-6 relative z-10" v-if="closingDetails && closingDetails.total_sistema !== null">
                                <div class="bg-slate-950/50 rounded-xl p-4 border border-white/5 flex justify-between items-center group hover:border-brand-500/30 transition-colors">
                                    <span class="text-slate-400 font-medium">Fondo Inicial</span>
                                    <span class="font-mono text-white font-bold">{{ formatCurrency(closingDetails.monto_inicial || 0) }}</span>
                                </div>
                                <div class="bg-slate-950/50 rounded-xl p-4 border border-white/5 flex justify-between items-center group hover:border-brand-500/30 transition-colors">
                                    <span class="text-slate-400 font-medium">Ventas Efectivo</span>
                                    <span class="font-mono text-emerald-400 font-bold">+ {{ formatCurrency(closingDetails.ventas_efectivo || 0) }}</span>
                                </div>
                                <div v-if="closingDetails.ingresos > 0" class="bg-slate-950/50 rounded-xl p-4 border border-white/5 flex justify-between items-center group hover:border-brand-500/30 transition-colors">
                                    <span class="text-slate-400 font-medium">Ingresos Varios</span>
                                    <span class="font-mono text-emerald-400 font-bold">+ {{ formatCurrency(closingDetails.ingresos || 0) }}</span>
                                </div>
                                <div class="bg-slate-950/50 rounded-xl p-4 border border-white/5 flex justify-between items-center group hover:border-brand-500/30 transition-colors">
                                    <span class="text-slate-400 font-medium">Retiros / Gastos</span>
                                    <span class="font-mono text-rose-400 font-bold">- {{ formatCurrency(closingDetails.egresos || 0) }}</span>
                                </div>

                                <div class="h-px bg-gradient-to-r from-transparent via-slate-600 to-transparent my-6"></div>

                                <div class="flex justify-between items-end">
                                    <span class="text-slate-300 font-bold text-sm uppercase tracking-wide">Esperado en Sistema</span>
                                    <div class="text-right">
                                        <div class="font-mono text-2xl font-black text-white leading-none">
                                            {{ formatCurrency(closingDetails.total_sistema || 0) }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="relative z-10 flex flex-col items-center justify-center py-12 text-center">
                                <div class="w-16 h-16 bg-slate-800 rounded-full flex items-center justify-center mb-4 border border-white/10">
                                    <svg class="w-10 h-10 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </div>
                                <h4 class="text-white font-bold text-lg">Conteo Ciego Activo</h4>
                                <p class="text-slate-400 text-sm mt-2 max-w-xs">Los totales del sistema están ocultos por seguridad. Realiza el conteo físico y confirma el cierre.</p>
                            </div>
                        </div>

                        <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 shadow-xl flex-1 flex flex-col justify-center">
                            <h3 class="text-slate-400 font-bold uppercase text-xs tracking-wide mb-2 text-center">Total Contado</h3>
                            <div class="text-center mb-6">
                                <span class="font-mono text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-white to-slate-400">
                                    {{ formatCurrency(totalDeclarado) }}
                                </span>
                            </div>

                            <div v-if="closingDetails && closingDetails.total_sistema !== null"
                                class="rounded-xl p-4 border-l-4 transition-all duration-200"
                                :class="(totalDeclarado - (closingDetails.total_sistema || 0)) >= 0 
                                    ? 'bg-brand-500/10 border-emerald-500' 
                                    : 'bg-brand-500/10 border-rose-500'"
                            >
                                <div class="flex justify-between items-center">
                                    <span class="font-bold text-sm uppercase tracking-wider" 
                                        :class="(totalDeclarado - (closingDetails.total_sistema || 0)) >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                                        {{ (totalDeclarado - (closingDetails.total_sistema || 0)) >= 0 ? 'Sobrante' : 'Faltante' }}
                                    </span>
                                    <span class="font-mono text-2xl font-black"
                                        :class="(totalDeclarado - (closingDetails.total_sistema || 0)) >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                                        {{ formatCurrency(Math.abs(totalDeclarado - (closingDetails.total_sistema || 0))) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 bg-slate-950/80 backdrop-blur-xl border-t border-white/5 flex justify-end gap-4">
                    <button 
                        @click="close" 
                        class="px-6 py-3 text-slate-400 font-bold hover:text-white transition-colors uppercase tracking-wider text-xs"
                    >
                        Cancelar
                    </button>
                    <button 
                        @click="emit('confirm')" 
                        :disabled="loading"
                        class="px-10 py-4 bg-gradient-to-r from-rose-600 to-rose-500 hover:from-rose-500 hover:to-rose-400 text-white font-bold rounded-2xl shadow-xl shadow-rose-600/20 disabled:opacity-50 transition-all flex items-center gap-2 transform active:scale-95 duration-200"
                    >
                        <span v-if="loading" class="animate-spin">⌛</span>
                        <span v-else class="uppercase tracking-wider text-sm">Confirmar Cierre y Corte</span>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
