<script setup>
import { ref, watch } from 'vue';
const props = defineProps({
    show: { type: Boolean, default: false },
    productName: { type: String, default: '' },
    seriesSearch: { type: String, default: '' },
    loading: { type: Boolean, default: false },
    error: { type: String, default: null },
    filteredSeries: { type: Array, default: () => [] },
    selectedSeries: { type: Array, default: () => [] },
});

const emit = defineEmits([
    'update:show',
    'update:seriesSearch',
    'toggle',
    'confirm',
    'retry',
]);

const close = () => emit('update:show', false);
const onSearchInput = (event) => emit('update:seriesSearch', event.target.value);
const isSelected = (serie) => props.selectedSeries.some((s) => s.id === serie.id);
const inputRef = ref(null);

watch(() => props.show, (val) => {
    if (val) {
        setTimeout(() => {
            inputRef.value?.focus();
        }, 100);
    }
});
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-md" @click="close"></div>

        <div class="relative bg-slate-900 border border-white/10 rounded-[2.5rem] w-full max-w-2xl overflow-hidden shadow-2xl flex flex-col max-h-[90vh] animate-in zoom-in-95 duration-200">
            <div class="p-8 border-b border-white/5 bg-slate-900/50 flex items-center justify-between">
                <div>
                    <h3 class="text-2xl font-black text-white">Seleccionar Series</h3>
                    <p class="text-sm text-slate-400 font-bold opacity-70">{{ productName }}</p>
                </div>
                <button @click="close" class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center hover:bg-rose-500/20 hover:text-rose-500 transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="p-4 bg-slate-950/30 border-b border-white/5">
                <div class="relative">
                    <input 
                        ref="inputRef"
                        :value="seriesSearch"
                        type="text" 
                        placeholder="Buscar serie o lote..."
                        class="w-full bg-slate-900 border-2 border-white/5 rounded-2xl px-12 py-3 text-sm font-bold focus:border-purple-500 focus:ring-0 transition-all"
                        @input="onSearchInput"
                    />
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-4 custom-scrollbar min-h-[300px]">
                <div v-if="loading" class="h-64 flex flex-col items-center justify-center opacity-40">
                    <svg class="animate-spin h-10 w-10 mb-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <p class="font-black text-xs uppercase tracking-widest">Cargando Series...</p>
                </div>

                <div v-else-if="error" class="h-64 flex flex-col items-center justify-center p-8 text-center">
                    <div class="w-16 h-16 rounded-full bg-rose-500/10 flex items-center justify-center text-rose-500 mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <h4 class="text-white font-black text-lg mb-2">Error de Conexión</h4>
                    <p class="text-slate-400 text-sm mb-6">{{ error }}</p>
                    <button @click="emit('retry')" class="px-6 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl font-bold transition-all border border-white/5">
                        Reintentar
                    </button>
                </div>

                <div v-else-if="filteredSeries.length > 0" class="grid grid-cols-2 gap-3">
                    <div v-for="serie in filteredSeries" 
                        :key="serie.id"
                        @click="emit('toggle', serie)"
                        :class="isSelected(serie) ? 'bg-purple-600 border-purple-400 text-white' : 'bg-slate-800/50 border-white/5 text-slate-400 hover:border-purple-500/30'"
                        class="p-4 rounded-2xl border transition-all cursor-pointer group">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-black uppercase tracking-tighter opacity-50">{{ serie.lote || 'Sin Lote' }}</span>
                            <div class="w-4 h-4 rounded-full border border-white/20 flex items-center justify-center transition-all"
                                :class="isSelected(serie) ? 'bg-white border-white' : ''">
                                <svg v-if="isSelected(serie)" class="w-3 h-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                            </div>
                        </div>
                        <div class="font-black text-base truncate">{{ serie.numero_serie }}</div>
                        <div class="text-[10px] opacity-40 font-bold truncate">Entrada: {{ new Date(serie.fecha_entrada).toLocaleDateString() }}</div>
                    </div>
                </div>

                <div v-else class="h-64 flex flex-col items-center justify-center opacity-20">
                    <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 9.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <p class="font-black">No se encontraron series disponibles</p>
                </div>
            </div>

            <div class="p-8 border-t border-white/5 bg-slate-900 flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-black text-slate-500 uppercase block">Seleccionadas</span>
                    <span class="text-2xl font-black">{{ selectedSeries.length }}</span>
                </div>
                <button 
                    @click="emit('confirm')"
                    :disabled="selectedSeries.length === 0"
                    class="px-10 py-4 bg-purple-600 hover:bg-purple-500 disabled:opacity-20 disabled:cursor-not-allowed text-white rounded-2xl font-black transition-all shadow-xl shadow-purple-600/20"
                >
                    CONFIRMAR Y AGREGAR
                </button>
            </div>
        </div>
    </div>
</template>
