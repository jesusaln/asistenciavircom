<template>
  <!-- Calculadora de Márgenes -->
  <div v-if="showMarginCalculator" class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 px-6 py-4">
      <div class="flex justify-between items-center">
        <h2 class="text-lg font-semibold text-white flex items-center">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
          </svg>
          Calculadora de Márgenes
        </h2>
      <button @click="toggleMarginCalculator" type="button" class="text-white hover:text-yellow-200">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
    </div>
    <div class="p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-blue-50 p-4 rounded-lg">
          <div class="text-sm font-medium text-blue-700 mb-2">Costo Total</div>
          <div class="text-2xl font-bold text-blue-900">
            ${{ marginData.costoTotal.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
          </div>
        </div>
        <div class="bg-green-50 p-4 rounded-lg">
          <div class="text-sm font-medium text-green-700 mb-2">Precio de Venta</div>
          <div class="text-2xl font-bold text-green-900">
            ${{ marginData.precioVenta.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
          </div>
        </div>
        <div class="bg-purple-50 p-4 rounded-lg">
          <div class="text-sm font-medium text-purple-700 mb-2">Margen Bruto</div>
          <div class="text-2xl font-bold text-purple-900">
            {{ marginData.margenPorcentaje.toFixed(1) }}%
          </div>
        </div>
        <div class="bg-yellow-50 p-4 rounded-lg">
          <div class="text-sm font-medium text-yellow-700 mb-2">Ganancia</div>
          <div class="text-2xl font-bold text-yellow-900">
            ${{ marginData.ganancia.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Resumen Total -->
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-6 py-4">
      <h2 class="text-lg font-semibold text-white flex items-center">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
        </svg>
        Resumen de Totales
      </h2>
    </div>
    <div class="p-6">
      <!-- Estadísticas -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="text-center p-6 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl border border-blue-200">
          <div class="text-3xl font-bold text-blue-600 mb-2">{{ itemCount }}</div>
          <div class="text-sm text-blue-600 font-medium">Items Seleccionados</div>
        </div>
        <div class="text-center p-6 bg-gradient-to-br from-green-50 to-green-100 rounded-xl border border-green-200">
          <div class="text-3xl font-bold text-green-600 mb-2">{{ totalQuantity }}</div>
          <div class="text-sm text-green-600 font-medium">Cantidad Total</div>
        </div>
        <div class="text-center p-6 bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl border border-purple-200">
          <div class="text-3xl font-bold text-purple-600 mb-2">${{ totals.total.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</div>
          <div class="text-sm text-purple-600 font-medium">Total Final</div>
        </div>
      </div>

      <!-- Desglose de totales simplificado -->
      <div class="bg-gray-50 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Desglose de Precios</h3>
        <div class="space-y-3">
          <div class="flex justify-between items-center text-gray-700">
            <span>Subtotal:</span>
            <span class="font-semibold">${{ totals.subtotal.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
          </div>
          <div v-if="totals.descuentoItems > 0" class="flex justify-between items-center text-orange-600">
            <span>Descuentos por Items:</span>
            <span class="font-semibold">-${{ totals.descuentoItems.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
          </div>
          <div v-if="totals.descuentoGeneral > 0" class="flex justify-between items-center text-red-600">
            <span>Descuento General:</span>
            <span class="font-semibold">-${{ totals.descuentoGeneral.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
          </div>
          <div v-if="(totals.descuentoItems > 0 || totals.descuentoGeneral > 0)" class="flex justify-between items-center text-gray-700">
            <span>Subtotal con descuentos:</span>
            <span class="font-semibold">${{ totals.subtotalConDescuentos.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
          </div>
          <div class="flex justify-between items-center text-blue-600">
            <span>IVA ({{ ivaPorcentaje }}%):</span>
            <span class="font-semibold">${{ totals.iva.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
          </div>
          <div v-if="totals.isr > 0" class="flex justify-between items-center text-orange-600">
            <span>ISR (Automático PM):</span>
            <span class="font-semibold">-${{ totals.isr.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
          </div>

          <!-- REtención IVA -->
          <div v-if="enableRetencionIva" class="flex justify-between items-center text-orange-600">
            <label class="flex items-center gap-2 cursor-pointer">
              <input 
                type="checkbox" 
                :checked="aplicarRetencionIva" 
                @change="$emit('update:aplicarRetencionIva', $event.target.checked)"
                class="rounded border-gray-300 text-orange-600 shadow-sm focus:border-orange-300 focus:ring focus:ring-orange-200 focus:ring-opacity-50"
              >
              <span>Retención IVA ({{ retencionIvaDefault }}%):</span>
            </label>
            <span v-if="aplicarRetencionIva && totals.retencion_iva > 0" class="font-semibold">-${{ totals.retencion_iva.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
            <span v-else class="text-gray-400">$0.00</span>
          </div>

          <!-- Retención ISR -->
          <div v-if="enableRetencionIsr" class="flex justify-between items-center text-orange-600">
             <label class="flex items-center gap-2 cursor-pointer">
              <input 
                type="checkbox" 
                :checked="aplicarRetencionIsr" 
                @change="$emit('update:aplicarRetencionIsr', $event.target.checked)"
                class="rounded border-gray-300 text-orange-600 shadow-sm focus:border-orange-300 focus:ring focus:ring-orange-200 focus:ring-opacity-50"
              >
              <span>Retención ISR ({{ retencionIsrDefault }}%):</span>
            </label>
            <span v-if="aplicarRetencionIsr && totals.retencion_isr > 0" class="font-semibold">-${{ totals.retencion_isr.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
             <span v-else class="text-gray-400">$0.00</span>
          </div>
          <div v-if="depositoGarantia && depositoGarantia > 0" class="flex justify-between items-center text-green-600">
            <span>Depósito de Garantía:</span>
            <span class="font-semibold">${{ Number(depositoGarantia).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
          </div>
          <div class="border-t border-gray-300 pt-3">
            <div class="flex justify-between items-center text-lg font-bold text-gray-900">
              <span>Total Final:</span>
              <span>${{ (totals.total + Number(depositoGarantia || 0)).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  showMarginCalculator: Boolean,
  marginData: {
    type: Object,
    required: true,
    default: () => ({
      costoTotal: 0,
      precioVenta: 0,
      ganancia: 0,
      margenPorcentaje: 0
    })
  },
  totals: {
    type: Object,
    required: true,
    default: () => ({
      subtotal: 0,
      descuentoItems: 0,
      subtotalConDescuentos: 0,
      iva: 0,
      isr: 0,
      total: 0
    })
  },
  itemCount: {
    type: Number,
    default: 0
  },
  totalQuantity: {
    type: Number,
    default: 0
  },
  depositoGarantia: {
    type: [Number, String],
    default: 0
  },
  ivaPorcentaje: {
    type: Number,
    default: 16
  },
  isrPorcentaje: {
    type: Number,
    default: 1.25
  },
  enableRetencionIva: Boolean,
  enableRetencionIsr: Boolean,
  retencionIvaDefault: {
    type: Number,
    default: 0
  },
  retencionIsrDefault: {
    type: Number,
    default: 0
  },
  aplicarRetencionIva: Boolean,
  aplicarRetencionIsr: Boolean
});

const emit = defineEmits(['toggle-margin-calculator', 'update:aplicarRetencionIva', 'update:aplicarRetencionIsr']);

const toggleMarginCalculator = () => {
  emit('toggle-margin-calculator');
};
</script>

