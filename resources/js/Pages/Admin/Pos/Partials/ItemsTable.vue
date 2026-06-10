<script setup>
const props = defineProps({
    selectedItems: { type: Array, default: () => [] },
    formatCurrency: { type: Function, required: true },
    priceWithIva: { type: Function, required: true },
    round2: { type: Function, required: true },
    selectedIndex: { type: Number, default: -1 }, // 🔥 NUEVO
});

const emit = defineEmits(['remove', 'select']); // 🔥 NUEVO
</script>

<template>
    <section class="flex-1 min-w-0 p-8 overflow-y-auto no-scrollbar transition-colors duration-500">
        <transition-group 
            v-if="selectedItems.length > 0" 
            name="list" 
            tag="div" 
            class="space-y-6"
        >
            <div v-for="(item, index) in selectedItems" :key="item.key" 
                @click="emit('select', index)"
                class="group relative transition-all duration-300 flex items-center gap-8 rounded-[2.5rem] p-8 cursor-pointer border shadow-sm"
                :class="selectedIndex === index 
                    ? 'bg-purple-600/10 dark:bg-purple-600/20 border-purple-600 shadow-2xl shadow-purple-600/10 scale-[1.01]' 
                    : 'bg-white/80 dark:bg-slate-900/50 border-slate-200 dark:border-white/5 hover:bg-slate-50 dark:hover:bg-purple-600/10 hover:border-purple-600/30'"
            >
                <!-- Indicador de Selección lateral -->
                <div v-if="selectedIndex === index" class="absolute left-0 top-1/2 -translate-y-1/2 w-1.5 h-12 bg-purple-600 rounded-r-full shadow-[0_0_20px_rgba(147,51,234,0.5)]"></div>
                
                <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-950 flex flex-col items-center justify-center border border-slate-200 dark:border-white/5 shadow-inner overflow-hidden relative group-hover:scale-110 transition-transform">
                    <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-0.5">{{ item.unidad }}</span>
                    <span class="text-lg font-black dark:text-white text-slate-900 animate-scale-in" :key="item.cantidad">{{ item.cantidad }}</span>
                </div>

                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <h3 class="text-lg font-black dark:text-white text-slate-900 uppercase tracking-wider">{{ item.nombre }}</h3>
                        <span class="text-[10px] font-black text-slate-400 dark:text-slate-600 bg-slate-50 dark:bg-white/5 px-2 py-0.5 rounded-xl border border-slate-100 dark:border-white/5">SKU: {{ item.codigo }}</span>
                    </div>
                    
                    <div v-if="item.series?.length > 0" class="mt-3 flex flex-wrap gap-2">
                        <span v-for="s in item.series" :key="s" class="text-[9px] bg-purple-600/10 text-purple-600 dark:text-purple-400 px-3 py-1 rounded-xl border border-purple-600/20 font-black uppercase tracking-wider">
                            S/N: {{ s }}
                        </span>
                    </div>
                </div>

                <div class="text-right px-4 hidden md:block">
                    <p class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-1.5">Unidad</p>
                    <p class="text-base font-black dark:text-white text-slate-900">{{ formatCurrency(priceWithIva(item.precio)) }}</p>
                </div>

                <div class="min-w-[160px] text-right bg-slate-100/50 dark:bg-white/5 p-4 rounded-3xl border border-slate-200 dark:border-white/5 group-hover:border-purple-600/30 transition-all">
                    <p class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-1">Subtotal</p>
                    <p class="text-2xl font-black dark:text-white text-slate-900 tracking-tighter">{{ formatCurrency(round2(priceWithIva(item.precio) * item.cantidad)) }}</p>
                </div>

                <button @click.stop="emit('remove', index)" class="w-12 h-12 flex items-center justify-center rounded-2xl bg-rose-500/5 hover:bg-rose-500 text-rose-500 hover:text-white opacity-0 group-hover:opacity-100 transition-all duration-300 transform group-hover:translate-x-0 translate-x-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        </transition-group>

        <div v-else class="h-full flex flex-col items-center justify-center">
            <div class="w-48 h-48 bg-slate-100 dark:bg-slate-900 rounded-[3rem] flex items-center justify-center mb-10 shadow-inner border border-slate-200 dark:border-white/5 group relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-tr from-purple-600/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <svg class="w-20 h-20 text-slate-300 dark:text-slate-700 relative z-10 group-hover:scale-110 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <h2 class="text-2xl font-black dark:text-white text-slate-900 uppercase tracking-wide mb-2">Caja en Espera</h2>
            <p class="text-[10px] font-black text-slate-400 dark:text-slate-600 uppercase tracking-[0.3em]">Escanea un código o usa F1 para buscar productos</p>
        </div>
    </section>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

.list-enter-active, .list-leave-active {
    transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.list-enter-from { opacity: 0; transform: scale(0.9) translateY(20px); }
.list-leave-to { opacity: 0; transform: translateX(100px); }

@keyframes scaleIn {
    0% { transform: scale(1); }
    50% { transform: scale(1.3); color: #10b981; }
    100% { transform: scale(1); }
}
.animate-scale-in {
    animation: scaleIn 0.3s ease-out;
}
</style>
