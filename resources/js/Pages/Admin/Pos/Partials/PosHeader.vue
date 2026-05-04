<script setup>
import { ref } from 'vue';

const props = defineProps({
    almacenes: { type: Array, default: () => [] },
    almacenId: { type: [String, Number], default: '' },
    cajaAbierta: { type: Boolean, default: false },
    search: { type: String, default: '' },
    searchResults: { type: Array, default: () => [] },
    selectedResultIndex: { type: Number, default: 0 },
    isAddingItem: { type: Boolean, default: false },
    priceLists: { type: Array, default: () => [] },
    priceListId: { type: [String, Number], default: '' },
    clientes: { type: Array, default: () => [] },
    clienteId: { type: [String, Number], default: '' },
    user: { type: Object, default: () => ({}) },
    puedeVenderComponentesSueltos: { type: Boolean, default: false },
    getLocalStock: { type: Function, required: true },
    formatCurrency: { type: Function, required: true },
    getDisplayPrice: { type: Function, required: true },
    isOnline: { type: Boolean, default: true },
    pendingSalesCount: { type: Number, default: 0 },
});

const emit = defineEmits([
    'update:search',
    'update:priceListId',
    'open-client-modal',
    'prepare-cierre',
    'search-keydown',
    'select-result',
    'hover-result',
]);

const searchInput = ref(null);
const onSearchInput = (event) => emit('update:search', event.target.value);

const focusSearch = () => searchInput.value?.focus();
const blurSearch = () => searchInput.value?.blur();

defineExpose({ focusSearch, blurSearch });
</script>

<template>
    <header class="h-20 bg-slate-900/50 border-b border-white/5 flex items-center px-6 gap-6 backdrop-blur-xl transition-colors duration-500"
        :class="{ 'bg-rose-950/20 border-rose-500/20': !isOnline }">
        
        <div class="flex items-center gap-4 min-w-[300px]">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-lg transition-colors duration-300"
                :class="isOnline ? 'bg-purple-600 shadow-purple-600/20' : 'bg-rose-600 shadow-rose-600/20 animate-pulse'">
                <svg v-if="isOnline" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 11-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                <svg v-else class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3" />
                </svg>
            </div>
            <div>
                <h1 class="text-lg font-black tracking-tight uppercase flex items-center gap-2">
                    Caja <span :class="isOnline ? 'text-purple-400' : 'text-rose-400'">{{ isOnline ? 'POS' : 'OFFLINE' }}</span>
                </h1>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest flex items-center gap-2">
                    {{ almacenes.find(a => a.id === almacenId)?.nombre || 'Principal' }}
                    <span v-if="pendingSalesCount > 0" class="text-amber-500 flex items-center gap-1 animate-pulse">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        {{ pendingSalesCount }} Pendientes
                    </span>
                </p>
                <p
                    v-if="!puedeVenderComponentesSueltos"
                    class="text-[10px] text-amber-400/90 max-w-[220px] leading-snug mt-1 font-medium normal-case tracking-normal"
                >
                    Los productos marcados como solo kit no se venden sueltos aquí (equipo completo o permiso especial).
                </p>
            </div>
        </div>

        <div class="flex-1 max-w-2xl relative group">
            <input 
                ref="searchInput"
                :value="search"
                @input="onSearchInput"
                @keydown="emit('search-keydown', $event)"
                type="text" 
                placeholder="Escanear código o buscar producto (F1)..."
                class="w-full bg-slate-950/50 border-2 border-slate-800 rounded-2xl px-6 py-3 text-lg font-bold placeholder:text-slate-600 focus:border-purple-500 focus:ring-0 transition-all group-hover:bg-slate-900"
                autocomplete="off"
            />

            <div v-if="search.trim().length > 0" 
                class="absolute top-full left-0 right-0 mt-2 bg-slate-900 border border-white/10 rounded-2xl shadow-2xl overflow-hidden z-[100]">
                
                <template v-if="searchResults.length > 0">
                    <div v-for="(res, idx) in searchResults" 
                        :key="res.id"
                        @click.stop.prevent="emit('select-result', res)"
                        @mouseenter="emit('hover-result', idx)"
                        :class="[
                            idx === selectedResultIndex 
                                ? 'bg-purple-600 text-white shadow-2xl relative z-10 scale-[1.01]' 
                                : 'text-slate-300 hover:bg-slate-800/70 hover:text-white',
                            isAddingItem || getLocalStock(res) <= 0 ? 'opacity-50 grayscale-[0.8] cursor-not-allowed' : 'cursor-pointer'
                        ]"
                        class="p-4 flex items-center gap-4 border-b border-white/5 last:border-none transition-all duration-75">
                        
                        <div class="w-10 h-10 rounded-lg bg-slate-950 flex items-center justify-center border border-white/10">
                            <span class="text-[10px] font-black opacity-50">{{ res.unidad_medida || 'PZA' }}</span>
                        </div>
                        
                        <div class="flex-1">
                            <div class="font-bold flex items-center gap-2">
                                {{ res.nombre }}
                                <span v-if="getLocalStock(res) <= 0" class="text-[9px] bg-rose-500/20 text-rose-500 px-2 py-0.5 rounded-full font-black uppercase">Agotado</span>
                            </div>
                            <div class="text-[10px] font-mono opacity-60">{{ res.codigo }}</div>
                        </div>

                        <div v-if="isAddingItem && idx === selectedResultIndex" class="flex items-center justify-center">
                            <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </div>

                        <div class="text-right">
                            <div class="text-[10px] font-black opacity-50 uppercase">Stock Local</div>
                            <div class="font-bold" :class="getLocalStock(res) <= 0 ? 'text-rose-400' : getLocalStock(res) < 5 ? 'text-amber-400' : 'text-emerald-400'">
                                {{ getLocalStock(res) }}
                            </div>
                        </div>

                        <div class="text-right min-w-[100px]">
                            <div class="text-[10px] font-black opacity-50 uppercase">Precio</div>
                            <div class="text-lg font-black">{{ formatCurrency(getDisplayPrice(res)) }}</div>
                        </div>

                        <div v-if="idx === selectedResultIndex" class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-8 bg-white rounded-r"></div>
                    </div>
                </template>
                <template v-else-if="search.trim().length > 0">
                    <div class="p-8 text-center text-slate-500">
                        <svg class="w-12 h-12 mx-auto mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <p class="text-sm font-bold opacity-50 italic">Sin resultados para "{{ search }}"</p>
                    </div>
                </template>
                
                <div class="p-2 bg-slate-950/50 text-[9px] font-black text-slate-500 text-center uppercase tracking-widest">
                    Usa ↑ ↓ para navegar • Enter para agregar • Mouse para seleccionar • ESC para limpiar
                </div>
            </div>

            <div class="absolute right-4 top-1/2 -translate-y-1/2 flex items-center gap-2 pointer-events-none">
                <kbd class="px-2 py-1 bg-slate-800 rounded text-[10px] font-black text-slate-400 border border-slate-700">F1</kbd>
            </div>
        </div>

        <div class="flex items-center gap-4 ml-auto">
            <button 
                @click="emit('open-client-modal')"
                class="flex items-center gap-3 px-4 py-2 bg-slate-800 hover:bg-slate-700 rounded-xl border border-white/5 transition-all group"
            >
                <div class="w-8 h-8 rounded-lg bg-purple-500/20 flex items-center justify-center text-purple-400 group-hover:bg-purple-500 group-hover:text-white transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                </div>
                <div class="text-left min-w-[120px]">
                    <p class="text-[9px] font-black text-slate-500 uppercase leading-none mb-1">Cliente</p>
                    <p class="text-xs font-bold leading-none truncate max-w-[150px]">
                        {{ clientes.find(c => c.id === clienteId)?.nombre_razon_social || 'Público General' }}
                    </p>
                </div>
                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
            </button>

            <div class="h-8 w-[1px] bg-white/10"></div>

            <div class="flex items-center gap-3 px-4 py-2 bg-slate-800 hover:bg-slate-700 rounded-xl border border-white/5 transition-all group relative">
                <div class="w-8 h-8 rounded-lg bg-emerald-500/20 flex items-center justify-center text-emerald-400 group-hover:bg-emerald-500 group-hover:text-white transition-all pointer-events-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div class="text-left relative">
                    <p class="text-[9px] font-black text-slate-500 uppercase leading-none mb-1">Precio</p>
                    <select 
                        :value="priceListId" 
                        @change="emit('update:priceListId', $event.target.value)" 
                        class="bg-transparent border-none p-0 text-xs font-bold text-white focus:ring-0 cursor-pointer appearance-none w-[120px]"
                    >
                        <option value="" class="bg-slate-800 text-slate-300">Lista General</option>
                        <option v-for="pl in priceLists" :key="pl.id" :value="pl.id" class="bg-slate-800 text-white">{{ pl.nombre }}</option>
                    </select>
                </div>
                 <svg class="w-4 h-4 text-slate-600 pointer-events-none absolute right-2 bottom-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
            </div>
            <div class="h-8 w-[1px] bg-white/10"></div>
            <div class="flex items-center gap-2 text-slate-400">
                <span class="text-xs font-bold">{{ user?.name }}</span>
                <div class="w-8 h-8 rounded-full bg-slate-800 border border-white/5 flex items-center justify-center text-[10px] font-black">
                    {{ user?.name?.substring(0,2).toUpperCase() }}
                </div>
            </div>
        </div>
    </header>
</template>
