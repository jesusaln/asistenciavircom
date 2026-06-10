<template>
  <div class="space-y-8">
    <div>
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white dark:text-gray-100 mb-2 flex items-center gap-2">
        <FontAwesomeIcon icon="file-image" class="text-emerald-600 dark:text-emerald-400" />
        Optimización de Imágenes
      </h2>
      <p class="text-sm text-gray-600 dark:text-gray-300 mb-6">
        Controla por empresa si las imágenes se convierten a WebP y define la calidad de compresión.
      </p>
    </div>

    <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-slate-800 dark:border-gray-700 space-y-6">
      <label class="relative inline-flex items-center cursor-pointer">
        <input type="checkbox" v-model="form.images_webp_enabled" class="sr-only peer">
        <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white dark:bg-slate-900 after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
        <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white dark:text-gray-100">
          Convertir imágenes automáticamente a WebP
        </span>
      </label>

      <div>
        <div class="flex items-center justify-between mb-2">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Calidad WebP (10 - 100)
          </label>
          <span class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">
            {{ form.images_webp_quality }}%
          </span>
        </div>
        <input
          type="range"
          min="10"
          max="100"
          step="1"
          v-model.number="form.images_webp_quality"
          class="w-full accent-emerald-600"
        />
        <div class="mt-3">
          <input
            type="number"
            min="10"
            max="100"
            v-model.number="form.images_webp_quality"
            class="w-28 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
          >
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
          Recomendado: 75-85 para buen balance entre tamaño y nitidez.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { watch } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
  form: { type: Object, required: true },
});

watch(
  () => props.form.images_webp_quality,
  (value) => {
    const normalized = Math.max(10, Math.min(100, Number(value) || 80));
    if (normalized !== value) {
      props.form.images_webp_quality = normalized;
    }
  },
  { immediate: true }
);
</script>

