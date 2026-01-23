<template>
  <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
      <!-- Encabezado del modal -->
      <div class="sticky top-0 bg-white dark:bg-slate-900 border-b border-gray-200 dark:border-slate-800 px-6 py-4 flex justify-between items-center">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white">
          Vista Previa de {{ documentTypeLabel }}
        </h3>
        <button @click="close" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <div class="p-6">
        <!-- Encabezado del documento -->
        <div class="text-center mb-8">
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2 uppercase">
            {{ documentTypeLabel }}
          </h1>
          <p class="text-gray-600">{{ currentDate }}</p>
        </div>

        <!-- Información del cliente -->
        <div v-if="cliente" class="mb-8 p-6 bg-gray-50 rounded-lg">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Cliente</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <p><strong>Nombre:</strong> {{ cliente.nombre_razon_social }}</p>
              <p v-if="cliente.email"><strong>Email:</strong> {{ cliente.email }}</p>
            </div>
            <div>
              <p v-if="cliente.telefono"><strong>Teléfono:</strong> {{ cliente.telefono }}</p>
              <p v-if="cliente.direccion"><strong>Dirección:</strong> {{ cliente.direccion }}</p>
            </div>
          </div>
        </div>

        <!-- Tabla de productos/servicios -->
        <div class="mb-8">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Detalles</h2>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Descripción</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cant.</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">P. Unitario</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Descuento</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Subtotal</th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200">
                <tr v-for="item in items" :key="item.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                    {{ item.nombre || item.descripcion }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                    {{ item.cantidad }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                    ${{ (item.precio || 0).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-orange-600">
                    {{ item.descuento }}%
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                    ${{ ((item.cantidad * item.precio) - (item.cantidad * item.precio * item.descuento / 100)).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Resumen de totales -->
        <div class="bg-gray-50 rounded-lg p-6 mb-8">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Resumen</h3>
          <div class="space-y-3">
            <div class="flex justify-between items-center text-gray-700">
              <span>Subtotal:</span>
              <span class="font-semibold">${{ totals.subtotal.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
            </div>
            <div v-if="totals.descuentoItems > 0" class="flex justify-between items-center text-orange-600">
              <span>Descuentos por ítem:</span>
              <span class="font-semibold">-${{ totals.descuentoItems.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
            </div>
            <div v-if="totals.descuentoGeneral > 0" class="flex justify-between items-center text-orange-600">
              <span>Descuento general ({{ descuentoGeneral }}%):</span>
              <span class="font-semibold">-${{ totals.descuentoGeneral.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
            </div>
            <div class="flex justify-between items-center text-gray-700">
              <span>Subtotal con descuentos:</span>
              <span class="font-semibold">${{ totals.subtotalConDescuentos.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
            </div>
            <div class="flex justify-between items-center text-blue-600">
              <span>IVA (16%):</span>
              <span class="font-semibold">${{ totals.iva.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
            </div>
            <div v-if="depositoGarantia && depositoGarantia > 0" class="flex justify-between items-center text-green-600">
              <span>Depósito de Garantía:</span>
              <span class="font-semibold">${{ Number(depositoGarantia).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
            </div>
            <div class="border-t border-gray-300 pt-3">
              <div class="flex justify-between items-center text-lg font-bold text-gray-900 dark:text-white">
                <span>Total Final:</span>
                <span>${{ (totals.total + Number(depositoGarantia || 0)).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Notas -->
        <div v-if="notas" class="mb-8 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
          <h3 class="text-sm font-semibold text-yellow-800 mb-2">Notas:</h3>
          <p class="text-sm text-yellow-700">{{ notas }}</p>
        </div>

        <!-- Botones de acción -->
        <div class="flex justify-end gap-3">
          <button
            @click="imprimir"
            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-blue-700 transition-colors duration-200 shadow-sm"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Imprimir
          </button>
          <button
            @click="close"
            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors duration-200"
          >
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

// --- PROPS ---
const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  type: {
    type: String,
    required: true,
    validator: (value) => ['cotizacion', 'pedido', 'venta', 'compra', 'ordenescompra', 'renta'].includes(value)
  },
  cliente: {
    type: Object,
    default: null
  },
  items: {
    type: Array,
    default: () => []
  },
  totals: {
    type: Object,
    required: true
  },
  descuentoGeneral: {
    type: Number,
    default: 0
  },
  notas: {
    type: String,
    default: ''
  },
  depositoGarantia: {
    type: [Number, String],
    default: 0
  }
});

// --- EMITS ---
const emit = defineEmits(['close', 'print']);

// --- Etiqueta del documento ---
const documentTypeLabel = computed(() => {
  const labels = {
    cotizacion: 'Cotización',
    pedido: 'Pedido',
    venta: 'Venta',
    compra: 'Compra',
    ordenescompra: 'Órdenes de Compra',
    renta: 'Contrato de Renta'
  };
  return labels[props.type] || 'Documento';
});

// --- Fecha actual ---
const currentDate = computed(() => {
  return new Date().toLocaleDateString('es-MX', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
});

// --- Cerrar modal ---
const close = () => {
  emit('close');
};

// --- Imprimir ---
const imprimir = () => {
  emit('print');
  // O puedes usar window.print() directamente
  // window.print();
};
</script>

