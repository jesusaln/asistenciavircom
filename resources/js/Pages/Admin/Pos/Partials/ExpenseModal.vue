<script setup>
import { ref, useTemplateRef, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    show: Boolean,
    processing: Boolean
});

const emit = defineEmits(['update:show', 'confirm']);

const form = ref({
    concepto: '',
    monto: '',
    tipo: 'egreso',
    categoria: '',
    nota: ''
});

const inputMonto = useTemplateRef('inputMonto');

watch(() => props.show, (newVal) => {
    if (newVal) {
        resetForm();
        setTimeout(() => {
            if (inputMonto.value) inputMonto.value.focus();
        }, 100);
    }
});

const resetForm = () => {
    form.value = {
        concepto: '',
        monto: '',
        tipo: 'egreso',
        categoria: 'Gastos POS',
        nota: ''
    };
};

const close = () => {
    emit('update:show', false);
};

const handleConfirm = () => {
    if (!form.value.concepto || !form.value.monto || form.value.monto <= 0) return;
    
    emit('confirm', { ...form.value });
};

const handleKeyDown = (e) => {
    if (!props.show) return;
    if (e.key === 'Escape') close();
    if (e.key === 'Enter' && e.ctrlKey) handleConfirm();
};

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
});
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div class="bg-slate-900 w-full max-w-md rounded-[2.5rem] border border-white/10 shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
            <!-- Header -->
            <div class="p-6 border-b border-white/5 bg-gradient-to-r from-purple-600/10 to-transparent flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 rounded-2xl bg-purple-500/20 flex items-center justify-center text-purple-400">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-white uppercase tracking-wider">Otros Movimientos</h3>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wide">Gastos o Entradas Diversas</p>
                    </div>
                </div>
                <button @click="close" class="p-2 hover:bg-white/5 rounded-xl text-slate-400 transition-colors">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="p-8 space-y-6 text-left">
                <!-- Tipo de Movimiento -->
                <div class="flex gap-2 p-1 bg-slate-800 rounded-2xl border border-white/5">
                    <button 
                        @click="form.tipo = 'egreso'"
                        :class="form.tipo === 'egreso' ? 'bg-brand-500 text-white shadow-xl' : 'text-slate-400 hover:text-white'"
                        class="flex-1 py-3 px-4 rounded-xl text-xs font-black uppercase tracking-wide transition-all"
                    >
                        Gasto (-)
                    </button>
                    <button 
                        @click="form.tipo = 'ingreso'"
                        :class="form.tipo === 'ingreso' ? 'bg-brand-500 text-white shadow-xl' : 'text-slate-400 hover:text-white'"
                        class="flex-1 py-3 px-4 rounded-xl text-xs font-black uppercase tracking-wide transition-all"
                    >
                        Ingreso (+)
                    </button>
                </div>

                <!-- Monto -->
                <div class="space-y-2 text-left">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] ml-2">Monto del Movimiento</label>
                    <div class="relative group">
                        <span class="absolute left-6 top-1/2 -translate-y-1/2 text-2xl font-black text-slate-500 transition-colors group-focus-within:text-purple-400">$</span>
                        <input 
                            v-model="form.monto"
                            type="number" 
                            step="0.01"
                            placeholder="0.00"
                            class="w-full bg-slate-800/50 border-2 border-slate-700/50 rounded-3xl py-6 pl-12 pr-6 text-4xl font-black text-white focus:border-brand-500 focus:bg-slate-800 outline-none transition-all placeholder:text-slate-700"
                            ref="inputMonto"
                        >
                    </div>
                </div>

                <!-- Concepto -->
                <div class="space-y-2 text-left">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] ml-2">Concepto / Motivo</label>
                    <input 
                        v-model="form.concepto"
                        type="text" 
                        placeholder="Ej: Pago de luz, Agua, Comida..."
                        class="w-full bg-slate-800/50 border-2 border-slate-700/50 rounded-2xl py-4 px-6 text-white focus:border-brand-500 outline-none transition-all placeholder:text-slate-700 font-bold"
                    >
                </div>

                <!-- Categoría (Opcional) -->
                <div class="space-y-2 text-left">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] ml-2">Categoría (Opcional)</label>
                    <input 
                        v-model="form.categoria"
                        type="text" 
                        placeholder="Proveedor, Local, Personal..."
                        class="w-full bg-slate-800/50 border-2 border-slate-700/50 rounded-2xl py-3 px-6 text-sm text-white focus:border-brand-500 outline-none transition-all placeholder:text-slate-700 font-bold"
                    >
                </div>
            </div>

            <!-- Footer -->
            <div class="p-6 bg-slate-950/30 border-t border-white/5 flex gap-4">
                <button 
                    @click="close"
                    class="flex-1 py-4 px-6 rounded-2xl border border-white/5 text-slate-400 font-black uppercase text-xs hover:bg-white/5 transition-all"
                >
                    Cancelar
                </button>
                <button 
                    @click="handleConfirm"
                    :disabled="!form.concepto || !form.monto || props.processing"
                    class="flex-[2] py-4 px-6 rounded-2xl bg-purple-600 hover:bg-purple-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-black uppercase text-xs shadow-xl shadow-purple-600/20 transition-all flex items-center justify-center gap-2"
                >
                    <span v-if="!props.processing">Confirmar Movimiento</span>
                    <span v-else>Guardando...</span>
                    <kbd class="px-2 py-0.5 rounded-xl bg-white/10 text-[9px]">CTRL + ENTER</kbd>
                </button>
            </div>
        </div>
    </div>
</template>
