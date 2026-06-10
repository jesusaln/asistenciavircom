<template>
  <transition name="fade">
    <div v-if="show" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
      <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-200 dark:border-slate-800">
        <div class="px-6 py-4 bg-gradient-to-r from-brand-500 to-orange-500">
          <h3 class="text-lg font-bold text-white flex items-center"><span class="mr-2">⚠️</span> Precios no encontrados</h3>
        </div>
        <div class="p-6 space-y-4">
          <p class="text-sm text-slate-600 dark:text-slate-300">Los siguientes productos no tienen precio definido en la lista seleccionada y usarán el precio base:</p>
          <ul class="space-y-2 max-h-40 overflow-y-auto custom-scrollbar">
            <li v-for="prod in products" :key="prod.id" class="text-sm font-medium text-slate-700 dark:text-slate-300 p-2 bg-brand-50 dark:bg-brand-900/20 rounded-xl border border-brand-100 dark:border-amber-800">
              {{ prod.nombre }}
            </li>
          </ul>
          <p class="text-xs text-slate-500 dark:text-slate-400">¿Deseas continuar con el precio base para estos productos?</p>
        </div>
        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-950/50 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-3">
          <button @click="$emit('close')" class="px-4 py-2 text-xs font-bold text-slate-500 uppercase tracking-wide hover:text-slate-700 dark:hover:text-slate-300">Cancelar</button>
          <button @click="$emit('accept')" class="px-6 py-2 bg-brand-500 hover:bg-brand-600 text-white text-xs font-black uppercase tracking-wide rounded-xl shadow-lg">Continuar</button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
defineProps({
  show: { type: Boolean, default: false },
  products: { type: Array, default: () => [] },
});

defineEmits(['close', 'accept']);
</script>
