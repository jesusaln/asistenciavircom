<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { computed } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    parkedSales: { type: Array, default: () => [] },
    formatCurrency: { type: Function, default: (val) => `$${val}` }
});

const emit = defineEmits(['update:show', 'restore', 'delete']);

const close = () => emit('update:show', false);

const timeAgo = (dateStr) => {
    const seconds = Math.floor((new Date() - new Date(dateStr)) / 1000);
    if (seconds < 60) return 'Hace unos segundos';
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `Hace ${minutes} min`;
    const hours = Math.floor(minutes / 60);
    return `Hace ${hours} horas`;
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-md" @click="close"></div>

        <div class="relative bg-slate-900 border border-white/10 rounded-[2.5rem] w-full max-w-4xl overflow-hidden shadow-2xl flex flex-col max-h-[85vh] animate-in zoom-in-95 duration-200">
            <!-- Header -->
            <div class="p-8 border-b border-white/5 bg-black/50 flex items-center justify-between">
                <div>
                    <h3 class="text-2xl font-black text-white flex items-center gap-2">
                        <span class="w-10 h-10 rounded-xl bg-brand-500/20 text-orange-400 flex items-center justify-center">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </span>
                        Ventas en Espera
                    </h3>
                    <p class="text-sm text-slate-400 font-bold opacity-70 mt-1 pl-[52px]">
                        {{ parkedSales.length }} ventas guardadas temporalmente
                    </p>
                </div>
                <button @click="close" class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center hover:bg-slate-500/20 hover:text-rose-500 transition-all">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-8 custom-scrollbar bg-slate-950/30">
                <div v-if="parkedSales.length === 0" class="h-64 flex flex-col items-center justify-center opacity-30">
                    <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
                    <p class="font-black text-lg">No hay ventas en espera</p>
                    <p class="font-bold text-sm mt-2">Usa el botón "Pausar" para guardar una venta temporalmente</p>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="(sale, index) in parkedSales" :key="sale.id" 
                        class="bg-slate-800/50 hover:bg-slate-800 border border-white/5 rounded-2xl p-5 transition-all group relative overflow-hidden">
                        
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h4 class="font-black text-white text-lg truncate max-w-[200px]">{{ sale.clientName }}</h4>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-[10px] font-black bg-black/50 px-2 py-0.5 rounded-xl text-slate-400">
                                        {{ new Date(sale.timestamp).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}
                                    </span>
                                    <span class="text-[10px] font-bold text-slate-500">{{ timeAgo(sale.timestamp) }}</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-black text-emerald-400">{{ formatCurrency(sale.total) }}</p>
                                <p class="text-[10px] font-bold text-slate-500 uppercase">{{ sale.itemCount }} Artículos</p>
                            </div>
                        </div>

                        <!-- Preview Items (First 3) -->
                        <div class="space-y-1 mb-4 opacity-60">
                            <div v-for="item in sale.items.slice(0, 2)" :key="item.key" class="text-xs flex justify-between">
                                <span class="truncate max-w-[70%]">{{ item.cantidad }}x {{ item.nombre }}</span>
                                <span>{{ formatCurrency(item.precio * item.cantidad) }}</span>
                            </div>
                            <div v-if="sale.items.length > 2" class="text-[10px] italic opacity-50 pl-1">
                                + {{ sale.items.length - 2 }} más...
                            </div>
                        </div>

                        <div class="flex gap-3 mt-2">
                             <button @click="emit('delete', index)" 
                                class="w-10 h-10 rounded-xl bg-slate-900 border border-white/5 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:bg-slate-500/10 transition-colors"
                                title="Eliminar definitivamente"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                            
                            <button @click="emit('restore', index)" 
                                class="flex-1 bg-white text-slate-900 hover:bg-purple-100 font-black py-2.5 rounded-xl flex items-center justify-center gap-2 transition-all shadow-xl shadow-white/5"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                RECUPERAR
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
             <div class="p-6 border-t border-white/5 bg-slate-900 flex justify-end">
                <button @click="close" class="px-6 py-2 text-slate-400 font-bold hover:text-white transition-colors">
                    Cerrar (ESC)
                </button>
            </div>
        </div>
    </div>
</template>
