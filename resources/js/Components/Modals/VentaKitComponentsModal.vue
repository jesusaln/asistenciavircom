<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white dark:bg-slate-900 w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
      <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
        <div>
          <h3 class="font-bold text-slate-900 dark:text-white uppercase tracking-wider text-sm">Series del Kit</h3>
          <p class="text-[10px] text-slate-500 mt-1 uppercase tracking-widest font-black">{{ kit?.nombre }}</p>
        </div>
        <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <div class="p-6 overflow-y-auto flex-1 space-y-4">
        <div v-for="component in components" :key="component.id" 
             class="p-4 bg-slate-50 dark:bg-slate-950/50 border-2 border-slate-100 dark:border-slate-800 rounded-2xl transition-all">
          <div class="flex items-start justify-between gap-4 mb-3">
            <div class="flex-1 min-w-0">
               <h4 class="text-xs font-black text-slate-800 dark:text-slate-200 uppercase tracking-tight">{{ component.item?.nombre }}</h4>
               <p class="text-[10px] text-slate-500 mt-0.5 font-bold uppercase tracking-widest">Código: {{ component.item?.codigo || '---' }}</p>
            </div>
            <div class="px-3 py-1 bg-indigo-500/10 text-indigo-400 rounded-full text-[9px] font-black uppercase border border-indigo-500/20">
               RQ: {{ calculateRequired(component) }}
            </div>
          </div>

          <div v-if="getSerials(component).length > 0" class="mb-3 flex flex-wrap gap-1.5">
             <span v-for="s in getSerials(component)" :key="s" 
                   class="px-2 py-0.5 bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 rounded text-[9px] font-bold font-mono">
               {{ s }}
             </span>
          </div>
          <div v-else class="mb-3">
             <span class="text-[10px] text-rose-500 font-bold uppercase tracking-widest flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                Series pendientes
             </span>
          </div>

          <button 
            @click="$emit('select-component', component)"
            class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-black uppercase tracking-widest rounded-xl transition-all shadow-lg shadow-indigo-500/20 active:scale-95">
            {{ getSerials(component).length > 0 ? 'Cambiar Selección' : 'Seleccionar Series' }}
          </button>
        </div>
      </div>

      <div class="p-6 border-t border-slate-100 dark:border-slate-800 flex justify-end">
        <button @click="$emit('close')" class="px-8 py-3 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-slate-200 dark:hover:bg-slate-750 transition-all">
          Listo
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  show: Boolean,
  kit: Object,
  quantities: Object,
  serials: Object,
});

const emit = defineEmits(['close', 'select-component']);

const components = computed(() => {
  const items = props.kit?.kit_items || props.kit?.componentes || [];
  return items.filter(ki => {
    // Check if the item itself requires serials
    const item = ki.item || ki.producto || ki;
    return item?.requiere_serie || item?.maneja_series;
  });
});

const calculateRequired = (component) => {
  const kitId = props.kit?.id;
  const kitQty = props.quantities[`producto-${kitId}`] || 1;
  return (component.cantidad || 1) * kitQty;
};

const getSerials = (component) => {
  if (!props.kit?.id) return [];
  const kitId = props.kit.id;
  const itemId = component.item_id || component.producto_id || component.item?.id || component.id;
  
  // Try multiple variations of the key just in case
  const keys = [
    `kit-${kitId}-component-${itemId}`,
    `kit-${kitId}-component-${String(itemId)}`,
  ];
  
  for (const k of keys) {
    if (props.serials[k] && props.serials[k].length > 0) {
      return props.serials[k];
    }
  }
  
  if (props.show) {
     console.log(`[KIT MODAL] Rechecking key for ${component.item?.nombre}:`, keys[0], 'Available keys:', Object.keys(props.serials));
  }
  
  return [];
};
</script>
