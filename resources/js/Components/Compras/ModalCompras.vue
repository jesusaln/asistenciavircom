<template>
  <!-- Modal para Compras -->
  <Transition name="modal">
    <div
      v-if="show"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="$emit('close')"
    >
      <div
        class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 outline-none"
        role="dialog"
        aria-modal="true"
        @keydown.esc.prevent="$emit('close')"
      >
        <!-- Modo: Confirmación de cancelación -->
        <div v-if="mode === 'cancel'" class="text-center">
          <div class="w-12 h-12 mx-auto bg-orange-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"
              />
            </svg>
          </div>
          <h3 class="text-lg font-medium mb-2">
            ¿Cancelar compra?
          </h3>
          <p class="text-gray-600 mb-6">
            La compra será cancelada y el inventario será revertido. Esta acción no se puede deshacer.
          </p>
          <div class="flex gap-3">
            <button
              @click="$emit('close')"
              class="flex-1 px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors"
            >
              No, volver
            </button>
            <button
              @click="$emit('confirm')"
              class="flex-1 px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors"
            >
              Sí, cancelar compra
            </button>
          </div>
        </div>

        <!-- Modo: Confirmación de eliminación -->
        <div v-else-if="mode === 'delete'" class="text-center">
          <div class="w-12 h-12 mx-auto bg-red-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
              />
            </svg>
          </div>
          <h3 class="text-lg font-medium mb-2">
            ¿Eliminar compra?
          </h3>
          <p class="text-gray-600 mb-6">
            Esta acción no se puede deshacer.
          </p>
          <div class="flex gap-3">
            <button
              @click="$emit('close')"
              class="flex-1 px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors"
            >
              Cancelar
            </button>
            <button
              @click="$emit('confirm')"
              class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
            >
              Eliminar
            </button>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
defineProps({
  show: {
    type: Boolean,
    default: false
  },
  mode: {
    type: String,
    default: 'cancel', // 'cancel' o 'delete'
    validator: (value) => ['cancel', 'delete'].includes(value)
  }
})

defineEmits(['close', 'confirm'])
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
  transform: scale(0.97);
}

.modal-enter-to,
.modal-leave-from {
  opacity: 1;
  transform: scale(1);
}
</style>

