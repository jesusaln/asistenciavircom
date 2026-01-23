<template>
  <Modal
    :show="show"
    :max-width="maxWidth"
    :closeable="closeable"
    @close="close"
  >
    <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
      <div class="sm:flex sm:items-start">
        <div class="mx-auto shrink-0 flex items-center justify-center size-12 rounded-full bg-yellow-100 sm:mx-0 sm:size-10">
          <svg class="size-6 text-yellow-600 dark:text-yellow-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
          </svg>
        </div>

        <div class="mt-3 text-center sm:mt-0 sm:ms-4 sm:text-start flex-1">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white dark:text-gray-100">
            ⚠️ Productos con Margen Insuficiente
          </h3>

          <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
            <p class="mb-4">
              Los siguientes productos tienen un margen de ganancia por debajo del mínimo requerido (5% adicional al costo de compra).
              Puedes ajustar automáticamente los precios o revisarlos manualmente.
            </p>

            <div class="space-y-3 max-h-60 overflow-y-auto">
              <div
                v-for="item in productosBajoMargen"
                :key="item.producto.id"
                class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3"
              >
                <div class="flex justify-between items-start">
                  <div class="flex-1">
                    <h4 class="font-medium text-gray-900 dark:text-white dark:text-gray-100">
                      {{ item.producto.nombre }}
                    </h4>
                    <div class="mt-2 space-y-1 text-xs text-gray-600 dark:text-gray-400">
                      <div class="flex justify-between">
                        <span>Precio actual:</span>
                        <span class="font-medium">${{ formatNumber(item.precio_actual) }}</span>
                      </div>
                      <div class="flex justify-between">
                        <span>Precio sugerido:</span>
                        <span class="font-medium text-green-600">${{ formatNumber(item.precio_sugerido) }}</span>
                      </div>
                      <div class="flex justify-between">
                        <span>Diferencia:</span>
                        <span class="font-medium text-red-600">${{ formatNumber(item.diferencia) }}</span>
                      </div>
                      <div class="flex justify-between">
                        <span>Costo de compra:</span>
                        <span>${{ formatNumber(item.producto.precio_compra || 0) }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="flex flex-col sm:flex-row justify-end gap-3 px-6 py-4 bg-gray-100 dark:bg-gray-800">
      <SecondaryButton @click="close">
        Revisar Manualmente
      </SecondaryButton>

      <PrimaryButton
        @click="ajustarAutomaticamente"
        :class="{ 'opacity-25': processing }"
        :disabled="processing"
      >
        <span v-if="processing">Ajustando...</span>
        <span v-else>Ajustar Automáticamente</span>
      </PrimaryButton>
    </div>
  </Modal>
</template>

<script setup>
import Modal from './Modal.vue';
import PrimaryButton from './PrimaryButton.vue';
import SecondaryButton from './SecondaryButton.vue';

const emit = defineEmits(['close', 'ajustar-automaticamente']);

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  maxWidth: {
    type: String,
    default: '2xl',
  },
  closeable: {
    type: Boolean,
    default: true,
  },
  productosBajoMargen: {
    type: Array,
    default: () => [],
  },
  processing: {
    type: Boolean,
    default: false,
  },
});

const close = () => {
  emit('close');
};

const ajustarAutomaticamente = () => {
  emit('ajustar-automaticamente');
};

const formatNumber = (number) => {
  return new Intl.NumberFormat('es-MX', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(number);
};
</script>

