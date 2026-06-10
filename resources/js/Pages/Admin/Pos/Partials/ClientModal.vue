<script setup>
import { computed } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    clientSearch: { type: String, default: '' },
    filteredClientes: { type: Array, default: () => [] },
    clientes: { type: Array, default: () => [] },
    selectedClienteId: { type: [String, Number], default: '' },
});

const emit = defineEmits([
    'update:show',
    'update:clientSearch',
    'select',
]);

const close = () => emit('update:show', false);
const onSearchInput = (event) => emit('update:clientSearch', event.target.value);
const showPublico = computed(() => !props.clientSearch);
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-md" @click="close"></div>

        <div class="relative bg-slate-900 border border-white/10 rounded-[2.5rem] w-full max-w-2xl overflow-hidden shadow-2xl flex flex-col max-h-[85vh] animate-in zoom-in-95 duration-200">
            <div class="p-8 border-b border-white/5 bg-black/50 flex items-center justify-between">
                <div>
                    <h3 class="text-2xl font-black text-white">Buscar Cliente</h3>
                    <p class="text-sm text-slate-400 font-bold opacity-70">Selecciona el cliente para esta venta</p>
                </div>
                <button @click="close" class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center hover:bg-slate-500/20 hover:text-rose-500 transition-all">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="p-4 bg-slate-950/30 border-b border-white/5">
                <div class="relative">
                    <input 
                        :value="clientSearch"
                        type="text" 
                        placeholder="Nombre, RFC o Correo..."
                        class="w-full bg-slate-900 border-2 border-white/5 rounded-2xl px-12 py-4 text-lg font-bold focus:border-brand-500 focus:ring-0 transition-all"
                        autofocus
                        @input="onSearchInput"
                    />
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-4 custom-scrollbar min-h-[400px]">
                <div class="space-y-2">
                    <div v-if="showPublico" 
                        @click="emit('select', null)"
                        class="p-4 rounded-2xl border border-dashed border-white/10 hover:border-brand-500/50 hover:bg-purple-500/5 transition-all cursor-pointer flex items-center gap-4 group"
                    >
                        <div class="w-10 h-10 rounded-xl bg-slate-800 flex items-center justify-center text-slate-400 group-hover:bg-purple-500 group-hover:text-white transition-all">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-black text-white text-lg">Público General</h4>
                            <p class="text-xs text-slate-500 font-bold uppercase tracking-wide">RFC: XAXX010101000</p>
                        </div>
                        <div v-if="!selectedClienteId" class="w-2 h-2 rounded-full bg-brand-500 shadow-xl shadow-emerald-500/50"></div>
                    </div>

                    <div v-for="c in filteredClientes" 
                        :key="c.id"
                        @click="emit('select', c)"
                        :class="selectedClienteId === c.id ? 'bg-purple-600 border-purple-400 text-white' : 'bg-slate-800/50 border-white/5 text-slate-400 hover:border-brand-500/30 hover:bg-slate-800'"
                        class="p-4 rounded-2xl border transition-all cursor-pointer flex items-center gap-4 group">
                        
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-lg transition-all"
                            :class="selectedClienteId === c.id ? 'bg-white/20 text-white' : 'bg-slate-800 text-slate-500 group-hover:bg-purple-500/20 group-hover:text-purple-400'">
                            {{ c.nombre_razon_social?.substring(0,1).toUpperCase() }}
                        </div>

                        <div class="flex-1 min-w-0">
                            <h4 class="font-black truncate transition-colors" :class="selectedClienteId === c.id ? 'text-white' : 'text-slate-200 group-hover:text-white'">
                                {{ c.nombre_razon_social }}
                            </h4>
                            <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-wider opacity-50">
                                <span>RFC: {{ c.rfc }}</span>
                                <span v-if="c.email" class="truncate">• {{ c.email }}</span>
                            </div>
                        </div>

                        <div v-if="selectedClienteId === c.id" class="w-10 h-10 rounded-full bg-white flex items-center justify-center shadow-xl">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                        </div>
                    </div>

                    <div v-if="filteredClientes.length === 0" class="h-64 flex flex-col items-center justify-center opacity-20">
                        <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 9.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <p class="font-black italic">No se encontraron clientes</p>
                    </div>
                </div>
            </div>

            <div class="p-6 border-t border-white/5 bg-black/50 text-center">
                <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Mostrando {{ filteredClientes.length }} de {{ clientes.length }} clientes registrados</p>
            </div>
        </div>
    </div>
</template>
