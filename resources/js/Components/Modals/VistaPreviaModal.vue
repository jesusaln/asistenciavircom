<template>
  <div v-if="show" class="fixed inset-0 bg-black/60 dark:bg-black/70 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto custom-scrollbar border border-slate-200 dark:border-slate-800">
      <!-- Encabezado del modal -->
      <div class="sticky top-0 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 px-6 py-4 flex justify-between items-center">
        <h3 class="text-xl font-bold text-slate-900 dark:text-slate-100">
          Vista Previa de {{ documentTypeLabel }}
        </h3>
        <button @click="close" class="text-slate-400 hover:text-slate-600 dark:text-slate-400 dark:hover:text-slate-200">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <div class="p-6">
        <!-- Encabezado del documento -->
        <div class="text-center mb-8">
          <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100 mb-2 uppercase">
            {{ documentTypeLabel }}
          </h1>
          <p class="text-slate-600 dark:text-slate-400">{{ currentDate }}</p>
        </div>

        <!-- Información del cliente -->
        <div v-if="cliente" class="mb-8 p-6 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
          <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">Cliente</h2>
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
          <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">Detalles</h2>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
              <thead class="bg-slate-50 dark:bg-slate-800/50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Descripción</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cant.</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">P. Unitario</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Descuento</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Subtotal</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                <tr v-for="item in items" :key="item.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-slate-100">
                    {{ item.nombre || item.descripcion }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                    {{ item.cantidad }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                    ${{ (item.precio || 0).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-brand-600 dark:text-orange-300">
                    {{ item.descuento }}%
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-slate-100">
                    ${{ ((item.cantidad * item.precio) - (item.cantidad * item.precio * item.descuento / 100)).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Resumen de totales -->
        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-6 mb-8">
          <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">Resumen</h3>
          <div class="space-y-3">
            <div class="flex justify-between items-center text-slate-700 dark:text-slate-300">
              <span>Subtotal:</span>
              <span class="font-semibold">${{ totals.subtotal.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
            </div>
            <div v-if="totals.descuentoItems > 0" class="flex justify-between items-center text-brand-600 dark:text-orange-300">
              <span>Descuentos por ítem:</span>
              <span class="font-semibold">-${{ totals.descuentoItems.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
            </div>
            <div v-if="totals.descuentoGeneral > 0" class="flex justify-between items-center text-brand-600 dark:text-orange-300">
              <span>Descuento general ({{ descuentoGeneral }}%):</span>
              <span class="font-semibold">-${{ totals.descuentoGeneral.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
            </div>
            <div class="flex justify-between items-center text-slate-700 dark:text-slate-300">
              <span>Subtotal con descuentos:</span>
              <span class="font-semibold">${{ totals.subtotalConDescuentos.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
            </div>
            <div class="flex justify-between items-center text-blue-600 dark:text-blue-300">
              <span>IVA (16%):</span>
              <span class="font-semibold">${{ totals.iva.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
            </div>
            <div v-if="depositoGarantia && depositoGarantia > 0" class="flex justify-between items-center text-emerald-600 dark:text-emerald-300">
              <span>Depósito de Garantía:</span>
              <span class="font-semibold">${{ Number(depositoGarantia).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
            </div>
            <div class="border-t border-slate-300 dark:border-slate-700 pt-3">
              <div class="flex justify-between items-center text-lg font-bold text-slate-900 dark:text-slate-100">
                <span>Total Final:</span>
                <span>${{ (totals.total + Number(depositoGarantia || 0)).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Notas -->
        <div v-if="notas" class="mb-8 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-900/40 rounded-xl">
          <h3 class="text-sm font-semibold text-yellow-800 dark:text-yellow-200 mb-2">Notas:</h3>
          <p class="text-sm text-yellow-700 dark:text-yellow-200">{{ notas }}</p>
        </div>

        <!-- Botones de acción -->
        <div class="flex justify-end gap-3">
          <button
            @click="imprimir"
            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-xl text-sm font-medium text-white hover:bg-blue-700 transition-colors duration-200 shadow-sm"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Imprimir
          </button>
          <button
            @click="close"
            class="px-4 py-2 bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-200 rounded-xl hover:bg-slate-300 dark:hover:bg-slate-700 transition-colors duration-200"
          >
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
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
