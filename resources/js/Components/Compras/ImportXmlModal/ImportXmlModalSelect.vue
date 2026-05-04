<template>
  <div>
    <div class="mb-4 flex space-x-2">
      <input
        :value="searchCfdi"
        @input="handleSearchInput"
        type="text"
        placeholder="Buscar por serie, folio, RFC o nombre..."
        class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
      />
      <div class="flex items-center space-x-2 bg-gray-50 px-3 rounded-lg border border-gray-200">
        <input
          id="showImported"
          type="checkbox"
          :checked="showImported"
          @change="handleShowImported"
          class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded cursor-pointer"
        />
        <label for="showImported" class="text-sm text-gray-700 select-none whitespace-nowrap cursor-pointer">
          Ver Importados (12 meses)
        </label>
      </div>
    </div>

    <div class="overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm h-96 overflow-y-auto">
      <div v-if="loadingCfdis" class="flex justify-center items-center h-full">
        <svg class="animate-spin h-8 w-8 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </div>
      <div v-else-if="receivedCfdis.length === 0" class="flex flex-col justify-center items-center h-full text-gray-500 p-4 text-center">
        <svg class="w-12 h-12 mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <p>No se encontraron facturas recibidas disponibles.</p>
        <p class="text-xs mt-1">Solo se muestran facturas de Ingreso (I) no vinculadas a compras.</p>
      </div>
      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50 sticky top-0">
          <tr>
            <th class="px-2 py-2 text-center">
              <input
                type="checkbox"
                :checked="allSelected"
                @change="toggleSelectAll"
                class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded cursor-pointer"
                title="Seleccionar todos"
              />
            </th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Folio</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Emisor</th>
            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acción</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="cfdi in displayedCfdis" :key="cfdi.id" class="hover:bg-gray-50 transition-colors" :class="{ 'bg-emerald-50': selectedCfdis.includes(cfdi.id), 'bg-gray-50 opacity-75': cfdi.importado }">
            <td class="px-2 py-2 text-center">
              <input
                type="checkbox"
                :checked="selectedCfdis.includes(cfdi.id)"
                :disabled="cfdi.importado"
                @change="toggleCfdiSelection(cfdi.id)"
                class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
              />
            </td>
            <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">{{ formatDate(cfdi.fecha) }}</td>
            <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900">
              {{ cfdi.serie }}{{ cfdi.folio }}
            </td>
            <td class="px-4 py-2 text-xs text-gray-700">
              <div class="font-medium text-gray-900">{{ cfdi.emisor_nombre }}</div>
              <div class="text-gray-500">{{ cfdi.emisor_rfc }}</div>
            </td>
            <td class="px-4 py-2 whitespace-nowrap text-sm text-right font-medium text-emerald-600">
              ${{ formatMoney(cfdi.total) }}
            </td>
            <td class="px-4 py-2 whitespace-nowrap text-center">
              <div v-if="cfdi.importado" class="flex flex-col items-center">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 mb-1">
                  Importado
                </span>
                <a v-if="cfdi.compra_id" :href="`/compras/${cfdi.compra_id}/edit`" target="_blank" class="text-[10px] text-blue-600 hover:text-blue-800 hover:underline">
                  Ver #{{ cfdi.compra_numero || cfdi.compra_id }}
                </a>
              </div>
              <button
                v-else
                @click="processSelectedCfdi(cfdi.id)"
                class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors"
              >
                Importar
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="selectedCfdis.length > 0" class="mt-4 p-4 bg-emerald-50 border border-emerald-200 rounded-lg">
        <div class="flex items-center justify-between">
          <div class="text-sm text-emerald-800">
            <span class="font-semibold">{{ selectedCfdis.length }}</span> CFDI(s) de
            <span class="font-bold">{{ selectedEmisorNombre }}</span>
            <span class="mx-2 text-gray-400">|</span>
            <span class="font-bold text-emerald-900">Total: ${{ formatMoney(totalSeleccionado) }}</span>
          </div>
          <button
            @click="bulkImportCfdis"
            :disabled="bulkImporting"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <svg v-if="bulkImporting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ bulkImporting ? `Importando (${bulkProgress}/${selectedCfdis.length})...` : 'Importar Seleccionados' }}
          </button>
        </div>
        <div v-if="bulkImporting" class="mt-3">
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-emerald-600 h-2 rounded-full transition-all duration-300" :style="{ width: `${(bulkProgress / selectedCfdis.length) * 100}%` }"></div>
          </div>
        </div>
      </div>

      <div v-if="selectedProducts.length > 0" class="mt-6 border-t pt-4">
        <div class="flex justify-between items-center mb-3">
          <h4 class="text-sm font-medium text-gray-900">
            Validación de Productos ({{ selectedProducts.length }})
          </h4>
          <div class="flex space-x-2 text-xs">
            <span class="flex items-center px-2 py-1 bg-green-100 text-green-700 rounded-full">
              <span class="w-2 h-2 bg-green-500 rounded-full mr-1"></span>
              {{ selectedProducts.filter(p => p.producto_id).length }} Listos
            </span>
            <span class="flex items-center px-2 py-1 bg-red-100 text-red-700 rounded-full">
              <span class="w-2 h-2 bg-red-500 rounded-full mr-1"></span>
              {{ selectedProducts.filter(p => !p.producto_id).length }} Por Resolver
            </span>
          </div>
        </div>

        <div v-if="selectedProducts.some(p => !p.producto_id)" class="mb-3 bg-amber-50 border border-amber-200 rounded-lg p-3 flex items-start">
          <svg class="w-5 h-5 text-amber-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          <div class="text-xs text-amber-800">
            <p class="font-medium">Atención requerida</p>
            <p>Algunos productos no existen en el catálogo. Usa el botón "Resolver" para crearlos antes de importar, o se importarán como borrador pendiente.</p>
          </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm max-h-80 overflow-y-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50 sticky top-0 z-10">
              <tr>
                <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CFDI</th>
                <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                <th scope="col" class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Cant</th>
                <th scope="col" class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                <th scope="col" class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acción</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="(prod, idx) in selectedProducts" :key="idx" class="hover:bg-gray-50 text-xs text-gray-700">
                <td class="px-3 py-2 whitespace-nowrap">
                  <span v-if="prod.producto_id" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                    OK
                  </span>
                  <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    Nuevo
                  </span>
                </td>
                <td class="px-3 py-2 whitespace-nowrap text-gray-500" :title="prod.cfdi_uuid">
                  {{ prod.cfdi_folio }}
                </td>
                <td class="px-3 py-2">
                  <div class="text-gray-900 truncate max-w-xs font-medium" :title="prod.descripcion">{{ prod.descripcion }}</div>
                  <div class="text-[10px] text-gray-500 font-mono mt-0.5">{{ prod.no_identificacion || 'S/N' }}</div>
                  <div v-if="prod.producto_nombre" class="text-[10px] text-green-600 truncate max-w-xs">
                    Link: {{ prod.producto_nombre }}
                  </div>
                </td>
                <td class="px-3 py-2 whitespace-nowrap text-right text-gray-900">{{ prod.cantidad }}</td>
                <td class="px-3 py-2 whitespace-nowrap text-right font-medium text-emerald-600">${{ formatMoney(prod.importe) }}</td>
                <td class="px-3 py-2 whitespace-nowrap text-center">
                  <button
                    v-if="!prod.producto_id"
                    @click="openProductModal(prod, idx, true)"
                    class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                  >
                    Resolver
                  </button>
                  <span v-else class="text-gray-400 text-[10px]">Vinculado</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  searchCfdi: { type: String, default: '' },
  showImported: { type: Boolean, default: false },
  loadingCfdis: { type: Boolean, default: false },
  receivedCfdis: { type: Array, default: () => [] },
  displayedCfdis: { type: Array, default: () => [] },
  selectedCfdis: { type: Array, default: () => [] },
  allSelected: { type: Boolean, default: false },
  selectedEmisorNombre: { type: [String, null], default: null },
  totalSeleccionado: { type: Number, default: 0 },
  bulkImporting: { type: Boolean, default: false },
  bulkProgress: { type: Number, default: 0 },
  selectedProducts: { type: Array, default: () => [] },
  formatDate: { type: Function, required: true },
  formatMoney: { type: Function, required: true },
  debounceSearch: { type: Function, required: true },
  toggleSelectAll: { type: Function, required: true },
  toggleCfdiSelection: { type: Function, required: true },
  processSelectedCfdi: { type: Function, required: true },
  bulkImportCfdis: { type: Function, required: true },
  openProductModal: { type: Function, required: true },
});

const emit = defineEmits(['update:searchCfdi', 'update:showImported']);

const handleSearchInput = (event) => {
  emit('update:searchCfdi', event.target.value);
  props.debounceSearch(event);
};

const handleShowImported = (event) => {
  emit('update:showImported', event.target.checked);
};
</script>
