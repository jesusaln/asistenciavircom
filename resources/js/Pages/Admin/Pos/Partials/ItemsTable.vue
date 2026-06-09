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
    <section class="flex-1 min-w-0 p-6 overflow-y-auto no-scrollbar">
        <transition-group 
            v-if="selectedItems.length > 0" 
            name="list" 
            tag="div" 
            class="space-y-4"
        >
            <div v-for="(item, index) in selectedItems" :key="item.key" 
                @click="emit('select', index)"
                class="group relative transition-all duration-300 flex items-center gap-6 rounded-[2.5rem] p-6 cursor-pointer border"
                :class="selectedIndex === index ? 'bg-purple-600/20 border-purple-500 shadow-lg shadow-purple-500/10' : 'bg-slate-900/40 border-white/5 hover:bg-purple-600/10 hover:border-purple-500/30'"
            >
                
                <div class="w-14 h-14 rounded-2xl bg-slate-950 flex flex-col items-center justify-center border border-white/5 ring-emerald-500/50 overflow-hidden relative">
                    <span class="text-[10px] font-black text-slate-600 uppercase">{{ item.unidad }}</span>
                    <span class="text-sm font-black animate-scale-in" :key="item.cantidad">{{ item.cantidad }}</span>
                </div>

                <div class="flex-1">
                    <h3 class="text-lg font-bold group-hover:text-indigo-400 transition-colors">{{ item.nombre }}</h3>
                    <p class="text-xs text-slate-500 font-mono">{{ item.codigo }}</p>
                    
                    <div v-if="item.series?.length > 0" class="mt-2 flex flex-wrap gap-1">
                        <span v-for="s in item.series" :key="s" class="text-[9px] bg-purple-500/10 text-purple-400 px-2 py-0.5 rounded border border-purple-500/20 font-black">
                            SN: {{ s }}
                        </span>
                    </div>
                </div>

                <div class="text-right">
                    <p class="text-[10px] font-black text-slate-500 uppercase">Precio Unitario</p>
                    <p class="text-lg font-bold">{{ formatCurrency(priceWithIva(item.precio)) }}</p>
                </div>

                <div class="w-32 text-right">
                    <p class="text-[10px] font-black text-slate-500 uppercase">Importe</p>
                    <p class="text-xl font-black text-white">{{ formatCurrency(round2(priceWithIva(item.precio) * item.cantidad)) }}</p>
                </div>

                <button @click="emit('remove', index)" class="p-4 rounded-2xl bg-rose-500/5 hover:bg-rose-500/20 text-rose-500 opacity-0 group-hover:opacity-100 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        </transition-group>

        <div v-else class="h-full flex flex-col items-center justify-center text-slate-600 opacity-50">
            <div class="w-32 h-32 rounded-[30%] bg-slate-900 flex items-center justify-center mb-6">
                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <p class="text-xl font-bold italic">La caja está vacía</p>
            <p class="text-sm">Escanea un producto para comenzar</p>
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
