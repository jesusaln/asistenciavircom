<template>
  <div v-if="show" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white dark:bg-slate-900 w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
      <div class="p-6 border-b border-slate-100 dark:border-slate-800">
        <h3 class="font-bold text-slate-900 dark:text-white">Seleccionar Series</h3>
        <p class="text-sm font-medium text-indigo-600 dark:text-indigo-400 mt-1">{{ product?.nombre || 'Producto' }}</p>
        <div class="flex gap-1 mt-2">
           <span v-for="tag in getTrazabilidadTags(product?.nombre || '')" :key="tag"
                 class="px-1.5 py-0.5 rounded-xl text-[9px] font-black border bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400">
             {{ tag }}
           </span>
        </div>
        <p class="text-[10px] text-slate-500 mt-3 font-semibold uppercase tracking-wider">Requeridas: {{ pickerRequired }} | Seleccionadas: {{ selectedSeries.length }}</p>
      </div>
      <div class="p-6 overflow-y-auto custom-scrollbar flex-1">
        <input :value="pickerSearch" @input="handleSearch" placeholder="Buscar serie..." class="w-full mb-4 px-4 py-2 rounded-xl border-2 border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-sm" />
        <div class="space-y-2">
          <div
            v-for="s in filteredPickerSeries"
            :key="s.id"
            @click="$emit('toggle', s.numero_serie)"
            :class="{
              'bg-indigo-50 border-indigo-200 dark:bg-sky-900/30 dark:border-indigo-800': selectedSeries.includes(s.numero_serie),
              'border-slate-100 dark:border-slate-800': !selectedSeries.includes(s.numero_serie)
            }"
            class="p-3 rounded-xl border flex justify-between items-center cursor-pointer transition-colors"
          >
            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ s.numero_serie }}</span>
            <div v-if="selectedSeries.includes(s.numero_serie)" class="w-5 h-5 bg-indigo-500 rounded-full flex items-center justify-center">
              <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
          </div>
        </div>
      </div>
      <div class="p-6 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-3">
        <button @click="$emit('close')" class="px-4 py-2 text-xs font-bold text-slate-500 uppercase">Cancelar</button>
        <button @click="$emit('confirm')" :disabled="selectedSeries.length !== pickerRequired" class="px-6 py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold uppercase disabled:opacity-50">Confirmar</button>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  show: { type: Boolean, default: false },
  product: { type: Object, default: () => ({}) },
  pickerRequired: { type: Number, default: 0 },
  selectedSeries: { type: Array, default: () => [] },
  pickerSearch: { type: String, default: '' },
  filteredPickerSeries: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'confirm', 'toggle', 'update:pickerSearch']);

const getTrazabilidadTags = (nombre) => {
  if (!nombre) return [];
  const tags = [];
  const lower = nombre.toLowerCase();
  
  if (lower.includes('condensador')) tags.push('C');
  if (lower.includes('manejadora') || lower.includes('evaporador')) tags.push('M');
  if (lower.includes('solo frío')) tags.push('S/F');
  if (lower.includes('calor') || lower.includes('calefacción')) tags.push('C/H');
  
  return tags;
};

const handleSearch = (event) => {
  emit('update:pickerSearch', event.target.value);
};
</script>
