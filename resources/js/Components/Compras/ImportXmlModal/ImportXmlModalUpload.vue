<template>
  <div>
    <div
      class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-emerald-400 transition-colors cursor-pointer"
      :class="{ 'border-emerald-500 bg-emerald-50': isDragging }"
      @dragover.prevent="$emit('update:isDragging', true)"
      @dragleave.prevent="$emit('update:isDragging', false)"
      @drop.prevent="onDrop"
      @click="triggerFileInput"
    >
      <input
        ref="fileInput"
        type="file"
        accept=".xml"
        class="hidden"
        @change="$emit('file-select', $event)"
      />
      <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
      </svg>
      <p class="text-lg font-medium text-gray-700 mb-2">
        Arrastra tu archivo XML aquí
      </p>
      <p class="text-sm text-gray-500">
        o haz clic para seleccionar
      </p>
      <p class="text-xs text-gray-400 mt-2">
        Solo archivos XML de CFDI (facturas tipo Ingreso)
      </p>
    </div>

    <div v-if="error" class="mt-4 bg-red-50 border border-red-200 rounded-lg p-4">
      <div class="flex items-center text-red-700">
        <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
        </svg>
        <span>{{ error }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
  isDragging: { type: Boolean, default: false },
  error: { type: String, default: '' },
});

const emit = defineEmits(['update:isDragging', 'file-select', 'file-drop']);

const fileInput = ref(null);

const triggerFileInput = () => {
  if (fileInput.value) {
    fileInput.value.click();
  }
};

const onDrop = (event) => {
  emit('update:isDragging', false);
  emit('file-drop', event);
};
</script>
