<template>
  <div v-if="show" class="fixed inset-0 z-[70] overflow-y-auto">
    <div class="fixed inset-0 bg-black bg-opacity-50" @click="$emit('close', false)"></div>
    <div class="flex min-h-full items-center justify-center p-4">
      <div class="relative w-full max-w-lg bg-white rounded-xl shadow-2xl">
        <div class="bg-blue-600 px-6 py-4 rounded-t-xl flex justify-between items-center">
          <h3 class="text-lg font-semibold text-white">Captura de Series</h3>
          <button @click="$emit('close', false)" class="text-white hover:text-gray-200">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-6">
          <p class="text-sm text-gray-600 mb-4">
            Series capturadas: {{ currentSerials.length }}/{{ requiredCantidad }}
          </p>

          <div class="flex space-x-2 mb-4">
            <input
              :value="conceptSerialInput"
              @input="$emit('update:conceptSerialInput', $event.target.value)"
              @keyup.enter="addConceptSerial"
              type="text"
              class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
              placeholder="Escanee o escriba número de serie..."
              autofocus
            />
            <button
              @click="addConceptSerial"
              class="px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700"
            >
              Agregar
            </button>
          </div>

          <div v-if="currentSerials.length > 0" class="mb-4">
            <p class="text-sm font-medium text-gray-700 mb-2">Series capturadas:</p>
            <ul class="space-y-1 max-h-40 overflow-y-auto">
              <li v-for="(serial, idx) in currentSerials" :key="idx" class="flex justify-between items-center p-2 bg-gray-100 rounded text-sm">
                <span class="font-mono">{{ serial }}</span>
                <button @click="removeConceptSerial(idx)" class="text-red-500 hover:text-red-700">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </li>
            </ul>
          </div>

          <div class="flex justify-end space-x-3">
            <button
              @click="$emit('close', false)"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              Cancelar
            </button>
            <button
              @click="$emit('close', true)"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700"
            >
              Guardar Series
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  show: { type: Boolean, default: false },
  requiredCantidad: { type: Number, default: 0 },
  conceptSerialInput: { type: String, default: '' },
  currentSerials: { type: Array, default: () => [] },
  addConceptSerial: { type: Function, required: true },
  removeConceptSerial: { type: Function, required: true },
});

defineEmits(['close', 'update:conceptSerialInput']);
</script>
