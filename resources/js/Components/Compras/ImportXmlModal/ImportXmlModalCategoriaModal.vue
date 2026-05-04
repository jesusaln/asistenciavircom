<template>
  <div v-if="show" class="fixed inset-0 z-[70] overflow-y-auto">
    <div class="fixed inset-0 bg-black bg-opacity-50" @click="$emit('close')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
      <div class="relative w-full max-w-md bg-white rounded-xl shadow-2xl">
        <div class="bg-blue-600 px-6 py-4 rounded-t-xl flex justify-between items-center">
          <h3 class="text-lg font-semibold text-white">Nueva Categoría</h3>
          <button @click="$emit('close')" class="text-white hover:text-gray-200">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="p-6">
          <form @submit.prevent="saveCategoria">
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Nombre *</label>
                <input v-model="nuevaCategoria.nombre" type="text" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Descripción</label>
                <textarea v-model="nuevaCategoria.descripcion" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
              </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
              <button type="button" @click="$emit('close')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
              <button type="submit" :disabled="savingCategoria" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50">
                {{ savingCategoria ? 'Guardando...' : 'Guardar' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  show: { type: Boolean, default: false },
  nuevaCategoria: { type: Object, required: true },
  savingCategoria: { type: Boolean, default: false },
  saveCategoria: { type: Function, required: true },
});

defineEmits(['close']);
</script>
