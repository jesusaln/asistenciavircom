<script setup>
defineProps({
  show: { type: Boolean, default: false },
  messages: { type: Array, default: () => [] },
  stockDetails: { type: Array, default: () => [] },
});

defineEmits(['close']);

const isHeader = (msg) => {
  return msg.startsWith('Stock insuficiente') || msg.startsWith('⚠️') || msg.includes(':') && !msg.startsWith('•');
};

const cleanMessage = (msg) => {
  return msg.replace(/^⚠️\s*/, '');
};

const formatNumber = (val) => {
  return parseFloat(val || 0).toFixed(0);
};
</script>

<template>
  <transition name="fade">
    <div v-if="show" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 backdrop-blur-md p-4" @click.self="$emit('close')">
      <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-200 dark:border-slate-800 transform transition-all animate-in fade-in zoom-in duration-300">
        <!-- Header -->
        <div class="px-6 py-4 bg-gradient-to-r from-rose-500 to-red-600 flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-white/20 flex items-center justify-center backdrop-blur-sm">
            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
          </div>
          <div>
            <h3 class="text-lg font-black text-white tracking-tight">Error de Inventario</h3>
            <p class="text-rose-100 text-[10px] font-bold uppercase tracking-wider">No se pudo completar la venta</p>
          </div>
        </div>

        <!-- Body -->
        <div class="p-6 max-h-[40vh] overflow-y-auto custom-scrollbar">
          <ul class="space-y-2">
            <li
              v-for="(msg, index) in messages"
              :key="index"
              :class="[
                'text-sm font-medium p-3 rounded-xl border transition-all',
                isHeader(msg)
                  ? 'bg-rose-50 dark:bg-rose-900/10 border-rose-100 dark:border-rose-800 text-rose-700 dark:text-rose-400 font-bold'
                  : 'bg-slate-50 dark:bg-slate-950 border-slate-100 dark:border-slate-800 text-slate-700 dark:text-slate-300'
              ]"
            >
              <div class="flex items-start gap-2">
                <span v-if="!isHeader(msg)" class="text-rose-400 mt-0.5 flex-shrink-0">•</span>
                <span v-else class="text-rose-500 mt-0.5 flex-shrink-0">⚠️</span>
                <span>{{ cleanMessage(msg) }}</span>
              </div>
            </li>
          </ul>
        </div>

        <!-- Stock Suggestions -->
        <div v-if="stockDetails && stockDetails.length > 0" class="p-6 bg-slate-50 dark:bg-slate-950/50 border-t border-slate-100 dark:border-slate-800 space-y-4">
           <div class="flex items-center gap-2 mb-2">
              <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
              <h4 class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide">Sugerencias de Solución</h4>
           </div>
           
           <div v-for="item in stockDetails" :key="item.producto_id" class="p-4 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
              <div class="flex justify-between items-start">
                  <p class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider">{{ item.producto_nombre }}</p>
                  <span class="px-2 py-0.5 bg-rose-50 dark:bg-rose-900/20/30 text-rose-600 dark:text-rose-400 text-[9px] font-black rounded-full">Sin Stock</span>
              </div>
              
              <!-- Si hay stock en otros almacenes -->
              <div v-if="item.otros_almacenes && item.otros_almacenes.length > 0" class="space-y-2">
                 <p class="text-[9px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-wide flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                    Disponible en otros almacenes:
                 </p>
                 <div class="space-y-1.5">
                    <div v-for="alt in item.otros_almacenes" :key="alt.almacen_id" class="flex items-center justify-between p-2.5 bg-emerald-50/50 dark:bg-emerald-900/10 rounded-xl border border-emerald-100 dark:border-emerald-900/20 group hover:border-emerald-300 transition-all">
                       <span class="text-[10px] font-bold text-slate-600 dark:text-slate-300">{{ alt.almacen_nombre }}</span>
                       <div class="flex items-center gap-3">
                          <span class="text-[11px] font-black text-emerald-700 dark:text-emerald-400">{{ formatNumber(alt.cantidad) }} un.</span>
                          <a :href="`/traspasos/create?producto_id=${item.producto_id}&almacen_destino_id=${item.almacen_id}&almacen_origen_id=${alt.almacen_id}`" 
                             class="px-3 py-1 bg-emerald-600 text-white text-[9px] font-black uppercase rounded-xl hover:bg-emerald-700 transition-all shadow-md shadow-emerald-600/20 active:scale-95">
                             Solicitar Traspaso
                          </a>
                       </div>
                    </div>
                 </div>
              </div>

              <!-- Si NO hay stock en otros almacenes -->
              <div v-else class="p-3 bg-brand-50 dark:bg-brand-900/10 rounded-xl border border-brand-100 dark:border-brand-900/20 flex items-center gap-3">
                 <svg class="w-4 h-4 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                 <span class="text-[9px] font-bold text-brand-700 dark:text-brand-500 uppercase tracking-wider">No se encontró stock en ningún otro almacén.</span>
              </div>
              
              <!-- Botón Orden de Compra -->
              <a :href="`/ordenescompra/create?producto_id=${item.producto_id}`" 
                 class="flex items-center justify-center gap-2 w-full py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-600 text-slate-600 dark:text-slate-400 text-[9px] font-black uppercase rounded-xl border border-slate-200 dark:border-slate-700 hover:border-indigo-500 transition-all group">
                 <svg class="w-3.5 h-3.5 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                 Generar Orden de Compra
              </a>
           </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-950/50 border-t border-slate-100 dark:border-slate-800 flex justify-end">
          <button
            @click="$emit('close')"
            class="px-8 py-3 bg-slate-800 dark:bg-slate-200 text-white dark:text-slate-800 rounded-2xl font-black text-[10px] uppercase tracking-wide hover:bg-slate-700 dark:hover:bg-slate-300 transition-all shadow-lg active:scale-95"
          >
            Entendido
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
