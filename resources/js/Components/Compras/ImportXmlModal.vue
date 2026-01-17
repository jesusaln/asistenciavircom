<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="close"></div>

      <!-- Modal -->
      <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-4xl bg-white rounded-xl shadow-2xl transform transition-all">
          <!-- Header -->
          <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 px-6 py-4 rounded-t-xl">
            <div class="flex items-center justify-between">
              <h3 class="text-xl font-semibold text-white flex items-center">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Importar desde XML (CFDI)
              </h3>
              <button @click="close" class="text-white hover:text-gray-200 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Bulk Review Progress Bar -->
          <div v-if="bulkReviewMode" class="bg-blue-50 border-b border-blue-200 px-6 py-3">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm text-blue-700 font-medium">
                📋 Revisando factura {{ bulkCurrentIndex + 1 }} de {{ bulkQueue.length }}
              </span>
              <div class="flex space-x-3 text-xs">
                <span class="text-green-600 font-medium">✓ {{ bulkResults.success.length }} importadas</span>
                <span class="text-gray-500">○ {{ bulkResults.skipped.length }} omitidas</span>
                <span class="text-red-600">✗ {{ bulkResults.errors.length }} errores</span>
              </div>
            </div>
            <div class="w-full bg-blue-200 rounded-full h-2">
              <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                   :style="{ width: `${((bulkCurrentIndex + 1) / bulkQueue.length) * 100}%` }">
              </div>
            </div>
          </div>

          <!-- Content -->
          <div class="px-6 pt-2 pb-6">
            <!-- Tabs -->
            <div class="flex space-x-1 rounded-xl bg-gray-100 p-1 mb-6" v-if="!cfdiData">
              <button
                v-for="tab in ['select', 'upload']"
                :key="tab"
                @click="activeTab = tab"
                :class="[
                  'w-full rounded-lg py-2.5 text-sm font-medium leading-5 ring-white ring-opacity-60 ring-offset-2 ring-offset-emerald-400 focus:outline-none focus:ring-2',
                  activeTab === tab
                    ? 'bg-white text-emerald-700 shadow'
                    : 'text-gray-500 hover:bg-white/[0.12] hover:text-emerald-600'
                ]"
              >
                {{ tab === 'upload' ? 'Subir Archivo XML' : 'Seleccionar Recibido' }}
              </button>
            </div>

            <!-- Estado: Subir archivo -->
            <div v-if="!cfdiData && !loading && activeTab === 'upload'">
              <div
                class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-emerald-400 transition-colors cursor-pointer"
                :class="{ 'border-emerald-500 bg-emerald-50': isDragging }"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="handleDrop"
                @click="$refs.fileInput.click()"
              >
                <input
                  ref="fileInput"
                  type="file"
                  accept=".xml"
                  class="hidden"
                  @change="handleFileSelect"
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

            <!-- Estado: Seleccionar Recibido -->
            <div v-if="!cfdiData && !loading && activeTab === 'select'">
                 <div class="mb-4 flex space-x-2">
                    <input 
                        v-model="searchCfdi" 
                        @input="debounceSearch"
                        type="text" 
                        placeholder="Buscar por serie, folio, RFC o nombre..." 
                        class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                    />
                    <div class="flex items-center space-x-2 bg-gray-50 px-3 rounded-lg border border-gray-200">
                      <input 
                        id="showImported" 
                        type="checkbox" 
                        v-model="showImported"
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
                    
                    <!-- Bulk Import Button -->
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
                        <!-- Progress bar -->
                        <div v-if="bulkImporting" class="mt-3">
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-emerald-600 h-2 rounded-full transition-all duration-300" :style="{ width: `${(bulkProgress / selectedCfdis.length) * 100}%` }"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Products Preview & Resolution -->
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

                        <!-- Alert if pending items -->
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

            <!-- Estado: Cargando -->
            <div v-if="loading" class="text-center py-12">
              <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-emerald-500 mx-auto mb-4"></div>
              <p class="text-gray-600">Procesando XML...</p>
            </div>

            <!-- Estado: Datos del CFDI -->
            <div v-if="cfdiData && !loading">
              <!-- Alerta si no es factura válida -->
              <div v-if="!cfdiData.es_factura_valida" class="mb-4 bg-amber-50 border border-amber-200 rounded-lg p-4">
                <div class="flex items-start">
                  <svg class="w-5 h-5 text-amber-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                  </svg>
                  <div>
                    <p class="font-medium text-amber-800">Este XML no es una factura de productos</p>
                    <p class="text-sm text-amber-700 mt-1">
                      Tipo: {{ cfdiData.tipo_comprobante_nombre }}. Solo se pueden importar facturas de Ingreso (I).
                    </p>
                  </div>
                </div>
              </div>

              <!-- Info del CFDI -->
              <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-gray-50 rounded-lg p-3">
                  <p class="text-xs text-gray-500 uppercase tracking-wide">Folio</p>
                  <p class="font-semibold text-gray-900">{{ cfdiData.serie }}{{ cfdiData.folio }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                  <p class="text-xs text-gray-500 uppercase tracking-wide">Fecha</p>
                  <p class="font-semibold text-gray-900">{{ formatDate(cfdiData.fecha) }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                  <p class="text-xs text-gray-500 uppercase tracking-wide">Subtotal</p>
                  <p class="font-semibold text-gray-900">${{ formatMoney(cfdiData.subtotal) }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                  <p class="text-xs text-gray-500 uppercase tracking-wide">IVA (16%)</p>
                  <p class="font-semibold text-blue-600">${{ formatMoney(cfdiData.impuestos?.total_impuestos_trasladados || 0) }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                  <p class="text-xs text-gray-500 uppercase tracking-wide">Total</p>
                  <p class="font-semibold text-emerald-600">${{ formatMoney(cfdiData.total) }}</p>
                </div>
              </div>

              <!-- Descuento si existe -->
              <div v-if="cfdiData.descuento > 0" class="mb-4 bg-red-50 border border-red-200 rounded-lg p-3">
                <div class="flex items-center justify-between">
                  <span class="text-sm font-medium text-red-700">Descuento aplicado:</span>
                  <span class="font-semibold text-red-600">-${{ formatMoney(cfdiData.descuento) }}</span>
                </div>
              </div>

              <!-- Emisor (Proveedor) -->
              <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-start justify-between gap-4">
                  <div class="flex-1">
                    <p class="text-xs text-blue-600 uppercase tracking-wide font-medium mb-2">Proveedor (Emisor)</p>
                    <p class="font-semibold text-gray-900 text-lg">{{ cfdiData.emisor?.nombre }}</p>
                    <div class="mt-2 space-y-1">
                      <p class="text-sm text-gray-600">
                        <span class="font-medium">RFC:</span> {{ cfdiData.emisor?.rfc }}
                        <span v-if="cfdiData.emisor?.rfc?.length === 12" class="ml-2 px-1.5 py-0.5 text-xs bg-blue-100 text-blue-700 rounded">Persona Moral</span>
                        <span v-else-if="cfdiData.emisor?.rfc?.length === 13" class="ml-2 px-1.5 py-0.5 text-xs bg-purple-100 text-purple-700 rounded">Persona Física</span>
                      </p>
                      <p v-if="cfdiData.emisor?.regimen_fiscal" class="text-sm text-gray-600">
                        <span class="font-medium">Régimen Fiscal:</span> {{ cfdiData.emisor?.regimen_fiscal }} - {{ getRegimenFiscalNombre(cfdiData.emisor?.regimen_fiscal) }}
                      </p>
                      <p v-if="cfdiData.lugar_expedicion" class="text-sm text-gray-600">
                        <span class="font-medium">CP Expedición:</span> {{ cfdiData.lugar_expedicion }}
                      </p>
                    </div>
                  </div>
                  <div v-if="cfdiData.proveedor_encontrado" class="flex items-center text-green-600 bg-green-50 px-3 py-2 rounded-lg">
                    <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-sm font-medium">Proveedor encontrado</span>
                  </div>
                  <div v-else class="flex flex-col items-end space-y-3 w-full max-w-sm">
                    <div class="flex items-center text-amber-600 mb-1">
                      <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                      </svg>
                      <span class="text-sm font-medium">Proveedor no registrado</span>
                    </div>
                    
                    <div class="w-full space-y-2 bg-white p-3 rounded-lg border border-gray-200">
                      <p class="text-xs text-gray-500 mb-2">Datos adicionales (opcionales):</p>
                        <input 
                            v-model="newProviderEmail" 
                            type="email" 
                            placeholder="Email de contacto" 
                            class="w-full text-sm rounded border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                        />
                        <input 
                            v-model="newProviderPhone" 
                            type="text" 
                            placeholder="Teléfono" 
                            class="w-full text-sm rounded border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                        />
                    </div>

                    <button 
                      @click="registrarProveedorExpress"
                      :disabled="registrandoProveedor"
                      class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed w-full justify-center"
                    >
                      <svg v-if="!registrandoProveedor" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                      </svg>
                      <svg v-else class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      {{ registrandoProveedor ? 'Registrando...' : 'Registrar Proveedor con estos datos' }}
                    </button>
                    <p class="text-xs text-gray-400 text-center">Se usará: RFC, Nombre y Régimen Fiscal del XML</p>
                  </div>
                </div>
              </div>

              <!-- Selector de Almacén -->
              <div class="mb-4 bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                  <div class="flex items-center">
                    <svg class="w-5 h-5 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <div class="flex flex-col">
                        <label class="text-sm font-medium text-indigo-800">Almacén destino *</label>
                        <span class="text-xs text-indigo-600 mt-1">
                            Método Pago Detectado: <strong>{{ cfdiData?.metodo_pago || 'N/A' }}</strong>
                        </span>
                    </div>
                  </div>
                  <select 
                    v-model="selectedAlmacenId" 
                    class="w-64 text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                  >
                    <option value="">Seleccionar almacén...</option>
                    <option v-for="almacen in almacenes" :key="almacen.id" :value="almacen.id">
                      {{ almacen.nombre }}
                    </option>
                  </select>
                </div>
              </div>

              <!-- PUE Handling Section -->
              <div v-if="cfdiData?.metodo_pago === 'PUE'" class="mb-4 bg-emerald-50 border border-emerald-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                  <label class="flex items-center cursor-pointer">
                    <input type="checkbox" v-model="puePagado" class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded" />
                    <span class="ml-2 text-sm font-medium text-emerald-800">¿Ya está pagada esta factura? (PUE Detectado)</span>
                  </label>
                  <div class="text-xs text-emerald-600 italic">
                    Las facturas PUE se consideran pagadas de contado
                  </div>
                </div>
                
                <div v-if="puePagado" class="grid grid-cols-2 gap-4 mt-3 pt-3 border-t border-emerald-100">
                  <div>
                    <label class="block text-xs font-medium text-emerald-700 mb-1">Cuenta Bancaria *</label>
                    <select v-model="pueCuentaBancariaId" class="w-full text-sm rounded border-emerald-300 focus:border-emerald-500 focus:ring-emerald-500" required>
                      <option value="">Seleccionar cuenta...</option>
                      <option v-for="cuenta in cuentasBancarias" :key="cuenta.id" :value="cuenta.id">
                        {{ cuenta.banco }} - {{ cuenta.numero_cuenta }} (${{ formatMoney(cuenta.saldo_actual) }})
                      </option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-emerald-700 mb-1">Método de Pago *</label>
                    <select v-model="pueMetodoPago" class="w-full text-sm rounded border-emerald-300 focus:border-emerald-500 focus:ring-emerald-500" required>
                      <option value="transferencia">Transferencia</option>
                      <option value="efectivo">Efectivo</option>
                      <option value="tarjeta">Tarjeta</option>
                      <option value="cheque">Cheque</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Estadísticas de mapeo -->
              <div class="mb-4 flex items-center justify-between bg-gray-100 rounded-lg p-3">
                <span class="text-sm font-medium text-gray-700">Productos del CFDI</span>
                <div class="flex items-center space-x-4 text-sm">
                  <span class="flex items-center text-green-600">
                    <span class="w-2 h-2 bg-green-500 rounded-full mr-1"></span>
                    {{ cfdiData.mapeo_stats?.mapeados || 0 }} encontrados
                  </span>
                  <span v-if="cfdiData.mapeo_stats?.similares" class="flex items-center text-amber-600">
                    <span class="w-2 h-2 bg-amber-500 rounded-full mr-1"></span>
                    {{ cfdiData.mapeo_stats.similares }} similares
                  </span>
                  <span v-if="cfdiData.mapeo_stats?.no_encontrados" class="flex items-center text-red-600">
                    <span class="w-2 h-2 bg-red-500 rounded-full mr-1"></span>
                    {{ cfdiData.mapeo_stats.no_encontrados }} no encontrados
                  </span>
                </div>
              </div>

              <!-- Tabla de conceptos -->
              <div class="border border-gray-200 rounded-lg overflow-hidden mb-6">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                        <span class="flex items-center group relative cursor-help">
                          Estado
                          <svg class="w-4 h-4 ml-1 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                          </svg>
                          <!-- Tooltip -->
                          <div class="absolute left-0 bottom-full mb-2 hidden group-hover:block w-64 p-2 bg-gray-800 text-white text-xs rounded-lg shadow-lg z-10">
                            <p class="font-medium mb-1">¿Qué significa el Estado?</p>
                            <p><span class="text-green-400">● Encontrado:</span> Producto existe en tu sistema</p>
                            <p><span class="text-amber-400">● Similar:</span> Producto parecido encontrado</p>
                            <p><span class="text-red-400">● No encontrado:</span> Deberás agregarlo manualmente</p>
                          </div>
                        </span>
                      </th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                      <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Clave SAT</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descripción</th>
                      <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">Unidad</th>
                      <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                      <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Precio</th>
                      <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Importe</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="(concepto, index) in cfdiData.conceptos" :key="index" class="hover:bg-gray-50">
                      <td class="px-4 py-3 whitespace-nowrap">
                        <div class="flex flex-col space-y-1">
                          <span v-if="concepto.match_type === 'exact'" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Encontrado
                          </span>
                          <span v-else-if="concepto.match_type === 'similar'" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            Similar ({{ concepto.match_confidence }}%)
                          </span>
                          <!-- Botón para crear nuevo producto cuando coincidencia < 75% -->
                          <button 
                            v-if="concepto.match_type === 'similar' && (concepto.match_confidence || 0) < 75"
                            @click="openProductModal(concepto, index)"
                            class="inline-flex items-center px-2 py-1 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-700 transition-colors mt-1"
                            title="Crear producto nuevo porque la coincidencia es menor al 75%"
                          >
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Crear Nuevo
                          </button>
                          <div v-else-if="!concepto.match_type || concepto.match_type === 'none'" class="flex flex-col items-start space-y-1">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                              <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                              </svg>
                              No encontrado
                            </span>
                            <button 
                              @click="openProductModal(concepto, index)"
                              class="inline-flex items-center px-2 py-1 text-xs font-medium text-white bg-emerald-600 rounded hover:bg-emerald-700 transition-colors"
                            >
                              <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                              </svg>
                              Agregar
                            </button>
                          </div>
                          
                          <!-- Indicador de Serie Requerida para productos mapeados -->
                          <div v-if="concepto.producto_id && concepto.requiere_serie" class="mt-1">
                            <button 
                              @click="openSerialModal(index)"
                              class="inline-flex items-center px-2 py-1 text-xs font-medium rounded transition-colors"
                              :class="(concepto.seriales?.length || 0) >= concepto.cantidad 
                                ? 'bg-green-100 text-green-800 hover:bg-green-200' 
                                : 'bg-orange-100 text-orange-800 hover:bg-orange-200'"
                            >
                              <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                              </svg>
                              Series: {{ concepto.seriales?.length || 0 }}/{{ concepto.cantidad }}
                            </button>
                          </div>
                        </div>
                      </td>
                      <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                        <div class="flex flex-col">
                          <span :class="{'text-blue-600 font-mono text-xs italic': /^\d{4,8}-\d{1,4}-\d{3,6}$/.test(concepto.no_identificacion?.trim())}">
                            {{ concepto.no_identificacion || '-' }}
                          </span>
                          <span v-if="/^\d{4,8}-\d{1,4}-\d{3,6}$/.test(concepto.no_identificacion?.trim())" class="text-[10px] text-blue-500 font-medium leading-tight">
                            (Serie detectada)
                          </span>
                        </div>
                      </td>
                      <td class="px-3 py-3 whitespace-nowrap text-sm">
                        <span v-if="concepto.clave_prod_serv" class="inline-flex items-center px-2 py-0.5 rounded bg-blue-100 text-blue-700 text-xs font-mono">
                          {{ concepto.clave_prod_serv }}
                        </span>
                        <span v-else class="text-gray-400">-</span>
                      </td>
                      <td class="px-4 py-3 text-sm text-gray-900">
                        <div>{{ concepto.descripcion }}</div>
                        <div v-if="concepto.producto_nombre" class="text-xs text-green-600 mt-1">
                          → {{ concepto.producto_nombre }}
                        </div>
                      </td>
                      <td class="px-3 py-3 whitespace-nowrap text-sm text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 text-gray-700 text-xs font-medium">
                          {{ concepto.unidad || concepto.clave_unidad || 'PZA' }}
                        </span>
                      </td>
                      <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right">
                        {{ concepto.cantidad }}
                      </td>
                      <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right">
                        ${{ formatMoney(concepto.valor_unitario) }}
                      </td>
                      <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right font-medium">
                        ${{ formatMoney(concepto.importe) }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Mensaje de advertencia si hay productos no encontrados -->
              <div v-if="cfdiData.mapeo_stats?.no_encontrados > 0" class="mb-4 bg-amber-50 border border-amber-200 rounded-lg p-4">
                <div class="flex items-start">
                  <svg class="w-5 h-5 text-amber-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                  </svg>
                  <div>
                    <p class="font-medium text-amber-800">Algunos productos no fueron encontrados</p>
                    <p class="text-sm text-amber-700 mt-1">
                      Los productos marcados en rojo no existen en el sistema. Deberás seleccionarlos manualmente en el formulario de compra.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="bg-gray-50 px-6 py-4 rounded-b-xl flex justify-between items-center">
            <div>
              <button
                v-if="cfdiData && !bulkReviewMode"
                @click="resetUpload"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
              >
                Subir otro archivo
              </button>
              <!-- Bulk mode: Cancel All button on left -->
              <button
                v-if="bulkReviewMode"
                @click="cancelBulkReview"
                class="px-4 py-2 text-sm font-medium text-red-600 bg-white border border-red-300 rounded-lg hover:bg-red-50 transition-colors"
              >
                Cancelar Todo
              </button>
            </div>
            <div class="flex space-x-3">
              <!-- Normal mode: Cancel button -->
              <button
                v-if="!bulkReviewMode"
                @click="close"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
              >
                Cancelar
              </button>
              <!-- Bulk mode: Skip button -->
              <button
                v-if="bulkReviewMode && cfdiData"
                @click="skipCurrentInBulk"
                class="px-4 py-2 text-sm font-medium text-amber-700 bg-amber-50 border border-amber-300 rounded-lg hover:bg-amber-100 transition-colors"
              >
                Omitir
              </button>
              <!-- Create Purchase button (works for both modes) -->
              <button
                v-if="cfdiData && cfdiData.es_factura_valida"
                @click="confirmarImportacion"
                :disabled="creandoCompra"
                class="px-6 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors flex items-center disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <svg v-if="creandoCompra" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                {{ creandoCompra ? 'Creando...' : (bulkReviewMode ? 'Confirmar e Importar' : 'Crear Compra') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Modal Agregar Producto Manual -->
    <div v-if="showProductModal" class="fixed inset-0 z-[60] overflow-y-auto">
      <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="showProductModal = false"></div>
      <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-2xl bg-white rounded-xl shadow-2xl transform transition-all">
          <div class="bg-emerald-600 px-6 py-4 rounded-t-xl flex justify-between items-center">
            <h3 class="text-lg font-semibold text-white">Agregar Producto</h3>
            <button @click="showProductModal = false" class="text-white hover:text-gray-200">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div class="p-6">
            <form @submit.prevent="saveProduct" class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                  <label class="block text-sm font-medium text-gray-700">Nombre del Producto</label>
                  <input v-model="productForm.nombre" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm" required />
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700">Código</label>
                  <input v-model="productForm.codigo" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm" />
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700">Código de Barras</label>
                  <input v-model="productForm.codigo_barras" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm" required />
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700">Categoría</label>
                  <div class="flex gap-2 mt-1">
                    <select v-model="productForm.categoria_id" class="flex-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm" required>
                      <option value="" disabled>Seleccionar...</option>
                      <option v-for="cat in catalogos.categorias" :key="cat.id" :value="cat.id">{{ cat.nombre }}</option>
                    </select>
                    <button 
                      type="button" 
                      @click="showCategoriaModal = true"
                      class="px-3 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700"
                      title="Agregar categoría"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                      </svg>
                    </button>
                  </div>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700">Marca</label>
                  <div class="flex gap-2 mt-1">
                    <select v-model="productForm.marca_id" class="flex-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm" required>
                      <option value="" disabled>Seleccionar...</option>
                      <option v-for="marca in catalogos.marcas" :key="marca.id" :value="marca.id">{{ marca.nombre }}</option>
                    </select>
                    <button 
                      type="button" 
                      @click="showMarcaModal = true"
                      class="px-3 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700"
                      title="Agregar marca"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                      </svg>
                    </button>
                  </div>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700">Precio Compra</label>
                  <input v-model.number="productForm.precio_compra" type="number" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm" required />
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700">Precio Venta</label>
                  <input v-model.number="productForm.precio_venta" type="number" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm" required />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">Unidad</label>
                   <select v-model="productForm.unidad_medida" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm" required>
                    <option value="" disabled>Seleccionar...</option>
                    <option v-for="u in catalogos.unidades" :key="u.id" :value="u.nombre">{{ u.nombre }} ({{u.abreviatura}})</option>
                    <!-- Fallback option if unit from XML is not in catalog -->
                    <option v-if="productForm.unidad_medida && !catalogos.unidades.some(u => u.nombre === productForm.unidad_medida)" :value="productForm.unidad_medida">{{ productForm.unidad_medida }}</option>
                  </select>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700">Stock Inicial</label>
                  <input v-model.number="productForm.stock" type="number" step="1" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm" />
                  <p class="text-xs text-gray-500 mt-1">Cantidad del XML: {{ currentConcept?.cantidad }}</p>
                </div>

                <!-- Campos SAT -->
                <div class="col-span-2 border-t pt-4 mt-2">
                  <p class="text-sm font-medium text-gray-700 mb-3">Información SAT (del XML)</p>
                  <div class="grid grid-cols-3 gap-4">
                    <div>
                      <label class="block text-xs font-medium text-gray-600">Clave Producto/Servicio</label>
                      <input v-model="productForm.sat_clave_prod_serv" type="text" maxlength="8" placeholder="Ej: 43211503" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm" />
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-gray-600">Clave Unidad SAT</label>
                      <input v-model="productForm.sat_clave_unidad" type="text" maxlength="3" placeholder="Ej: H87" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm" />
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-gray-600">Objeto de Impuesto</label>
                      <select v-model="productForm.sat_objeto_imp" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm">
                        <option value="01">01 - No objeto de impuesto</option>
                        <option value="02">02 - Sí objeto de impuesto</option>
                        <option value="03">03 - Sí objeto, no obligado desglose</option>
                        <option value="04">04 - Sí objeto, IVA crédito PODEBI</option>
                      </select>
                    </div>
                  </div>
                </div>
                
                 <div class="flex items-center mt-2">
                  <input v-model="productForm.requiere_serie" id="requiere_serie" type="checkbox" class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded">
                  <label for="requiere_serie" class="ml-2 block text-sm text-gray-900">
                    Requiere número de serie
                  </label>
                </div>

                <!-- Sección de captura de series (solo si requiere_serie está activo) -->
                <div v-if="productForm.requiere_serie" class="col-span-2 mt-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                  <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center">
                      <svg class="w-5 h-5 text-amber-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                      </svg>
                      <p class="text-sm font-medium text-amber-800">Captura de Series</p>
                    </div>
                    <span class="text-xs bg-amber-200 text-amber-800 px-2 py-1 rounded-full">
                      {{ productSerials.length }} / {{ productForm.stock }} series
                    </span>
                  </div>
                  
                  <p class="text-xs text-amber-700 mb-3">
                    Escanee o ingrese {{ productForm.stock }} número(s) de serie. Presione Enter o escanee para agregar.
                  </p>
                  
                  <!-- Campo de entrada para escáner -->
                  <div class="flex gap-2 mb-3">
                    <input 
                      ref="serialInput"
                      v-model="currentSerial"
                      @keydown.enter.prevent="addSerial"
                      type="text"
                      :disabled="productSerials.length >= productForm.stock"
                      :placeholder="productSerials.length >= productForm.stock ? 'Series completas' : 'Escanee o escriba número de serie...'"
                      class="flex-1 text-sm rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 disabled:bg-gray-100"
                      autofocus
                    />
                    <button 
                      type="button"
                      @click="addSerial"
                      :disabled="!currentSerial.trim() || productSerials.length >= productForm.stock"
                      class="px-3 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      Agregar
                    </button>
                  </div>
                  
                  <!-- Lista de series capturadas -->
                  <div v-if="productSerials.length > 0" class="max-h-32 overflow-y-auto space-y-1">
                    <div 
                      v-for="(serial, idx) in productSerials" 
                      :key="idx"
                      class="flex items-center justify-between bg-white px-3 py-1.5 rounded border border-gray-200 text-sm"
                    >
                      <span class="font-mono text-gray-700">{{ serial }}</span>
                      <button 
                        type="button"
                        @click="removeSerial(idx)"
                        class="text-red-500 hover:text-red-700"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                      </button>
                    </div>
                  </div>
                  
                  <!-- Mensaje si faltan series -->
                  <p v-if="productSerials.length < productForm.stock && productForm.stock > 0" class="text-xs text-amber-600 mt-2">
                    ⚠️ Faltan {{ productForm.stock - productSerials.length }} serie(s) por capturar
                  </p>
                </div>
              </div>

               <div class="mt-6 flex justify-end space-x-3">
                <button type="button" @click="showProductModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
                <button 
                  type="submit" 
                  :disabled="savingProduct"
                  class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 disabled:opacity-50 flex items-center"
                >
                  <svg v-if="savingProduct" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                  Guardar Producto
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Agregar Categoría -->
    <div v-if="showCategoriaModal" class="fixed inset-0 z-[70] overflow-y-auto">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="showCategoriaModal = false"></div>
      <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-md bg-white rounded-xl shadow-2xl">
          <div class="bg-blue-600 px-6 py-4 rounded-t-xl flex justify-between items-center">
            <h3 class="text-lg font-semibold text-white">Nueva Categoría</h3>
            <button @click="showCategoriaModal = false" class="text-white hover:text-gray-200">
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
                <button type="button" @click="showCategoriaModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
                <button type="submit" :disabled="savingCategoria" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50">
                  {{ savingCategoria ? 'Guardando...' : 'Guardar' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Agregar Marca -->
    <div v-if="showMarcaModal" class="fixed inset-0 z-[70] overflow-y-auto">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="showMarcaModal = false"></div>
      <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-md bg-white rounded-xl shadow-2xl">
          <div class="bg-purple-600 px-6 py-4 rounded-t-xl flex justify-between items-center">
            <h3 class="text-lg font-semibold text-white">Nueva Marca</h3>
            <button @click="showMarcaModal = false" class="text-white hover:text-gray-200">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="p-6">
            <form @submit.prevent="saveMarca">
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Nombre *</label>
                  <input v-model="nuevaMarca.nombre" type="text" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Descripción</label>
                  <textarea v-model="nuevaMarca.descripcion" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"></textarea>
                </div>
              </div>
              <div class="mt-6 flex justify-end space-x-3">
                <button type="button" @click="showMarcaModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
                <button type="submit" :disabled="savingMarca" class="px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 disabled:opacity-50">
                  {{ savingMarca ? 'Guardando...' : 'Guardar' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Modal de Captura de Series para Conceptos -->
    <div v-if="showSerialModal" class="fixed inset-0 z-[70] overflow-y-auto">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="closeSerialModal(false)"></div>
      <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full">
          <div class="px-6 py-4 bg-gradient-to-r from-blue-500 to-blue-600 rounded-t-lg flex justify-between items-center">
            <h3 class="text-lg font-semibold text-white">Capturar Series</h3>
            <button @click="closeSerialModal(false)" class="text-white hover:text-gray-200">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="p-6">
            <!-- Info del producto -->
            <div v-if="serialModalIndex !== null && cfdiData?.conceptos?.[serialModalIndex]" class="mb-4 p-3 bg-gray-50 rounded-lg">
              <p class="text-sm font-medium text-gray-900">{{ cfdiData.conceptos[serialModalIndex].descripcion }}</p>
              <p class="text-xs text-gray-500 mt-1">
                Cantidad: {{ cfdiData.conceptos[serialModalIndex].cantidad }} | 
                Series capturadas: {{ currentSerials.length }}/{{ cfdiData.conceptos[serialModalIndex].cantidad }}
              </p>
            </div>
            
            <!-- Input de serie -->
            <div class="flex space-x-2 mb-4">
              <input 
                v-model="conceptSerialInput"
                @keyup.enter="addConceptSerial"
                type="text" 
                placeholder="Ingresa número de serie"
                class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
              />
              <button 
                @click="addConceptSerial"
                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700"
              >
                Agregar
              </button>
            </div>
            
            <!-- Lista de series capturadas -->
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
            
            <!-- Botones -->
            <div class="flex justify-end space-x-3">
              <button 
                @click="closeSerialModal(false)" 
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
              >
                Cancelar
              </button>
              <button 
                @click="closeSerialModal(true)" 
                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700"
              >
                Guardar Series
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import Swal from 'sweetalert2';
import axios from 'axios';
import { debounce } from 'lodash';

const props = defineProps({
  show: { type: Boolean, default: false },
  almacenesList: { type: Array, default: () => [] }
});

const emit = defineEmits(['close', 'import']);

const loading = ref(false);
const error = ref('');
const isDragging = ref(false);
const cfdiData = ref(null);
const fileInput = ref(null);
const newProviderEmail = ref('');
const newProviderPhone = ref('');

// PUE Handling state
const puePagado = ref(false);
const pueMetodoPago = ref('transferencia');
const pueCuentaBancariaId = ref('');
const cuentasBancarias = ref([]);

// Logic for tabs and selecting received CFDIs
const activeTab = ref('select');
const receivedCfdis = ref([]);
const loadingCfdis = ref(false);
const searchCfdi = ref('');
const showImported = ref(false); // Default hidden

// Bulk selection state
const selectedCfdis = ref([]);
const bulkImporting = ref(false);
const bulkProgress = ref(0);
const selectedRfc = ref(null); // RFC del emisor seleccionado
const selectedProducts = ref([]); // Productos de los CFDIs seleccionados

// Bulk Review Mode - Sequential modal review
const bulkReviewMode = ref(false);      // Modo revisión secuencial activo
const bulkQueue = ref([]);               // Cola de CFDI IDs pendientes
const bulkCurrentIndex = ref(0);         // Índice actual en la cola
const bulkResults = ref({ success: [], skipped: [], errors: [] });

// Computed: CFDIs a mostrar (filtrados por búsqueda, selección de RFC y estado importado)
const displayedCfdis = computed(() => {
  let cfdis = receivedCfdis.value;
  
  // 0. Filtrar importados si no está activo el checkbox
  if (!showImported.value) {
      cfdis = cfdis.filter(c => !c.importado);
  }

  // 1. Filtrar por RFC si hay selección activa
  if (selectedRfc.value) {
    cfdis = cfdis.filter(c => c.emisor_rfc === selectedRfc.value);
  }
  
  // 2. Filtrar por texto de búsqueda si existe
  if (searchCfdi.value) {
    const search = searchCfdi.value.toLowerCase();
    cfdis = cfdis.filter(c => 
      (c.serie + c.folio).toLowerCase().includes(search) ||
      (c.emisor_nombre || '').toLowerCase().includes(search) ||
      (c.emisor_rfc || '').toLowerCase().includes(search)
    );
  }
  
  return cfdis;
});

// Computed for "select all" checkbox - solo del mismo RFC
const allSelected = computed(() => {
  return displayedCfdis.value.length > 0 && selectedCfdis.value.length === displayedCfdis.value.length;
});

// Computed: nombre del emisor seleccionado
const selectedEmisorNombre = computed(() => {
  if (!selectedRfc.value) return null;
  const cfdi = receivedCfdis.value.find(c => c.emisor_rfc === selectedRfc.value);
  return cfdi?.emisor_nombre || selectedRfc.value;
});

// Computed: Total monetario de la selección
const totalSeleccionado = computed(() => {
    return receivedCfdis.value
        .filter(c => selectedCfdis.value.includes(c.id))
        .reduce((sum, c) => sum + (parseFloat(c.total) || 0), 0);
});

// Check if a CFDI can be selected (same RFC or first selection)
const canSelectCfdi = (cfdi) => {
  if (selectedCfdis.value.length === 0) return true;
  return cfdi.emisor_rfc === selectedRfc.value;
};

// Toggle individual CFDI selection (with RFC restriction)
const toggleCfdiSelection = async (cfdiId) => {
  const cfdi = receivedCfdis.value.find(c => c.id === cfdiId);
  if (!cfdi || cfdi.importado) return;
  
  const index = selectedCfdis.value.indexOf(cfdiId);
  if (index === -1) {
    // Adding selection
    if (selectedCfdis.value.length === 0) {
      // First selection - set the RFC
      selectedRfc.value = cfdi.emisor_rfc;
    } else if (cfdi.emisor_rfc !== selectedRfc.value) {
      // Different RFC - don't allow
      Swal.fire({
        icon: 'error',
        title: 'Emisor Diferente',
        text: `Solo puedes seleccionar CFDIs del mismo emisor.\nEmisor actual: ${selectedEmisorNombre.value}`,
        confirmButtonColor: '#EF4444',
      });
      return;
    }
    selectedCfdis.value.push(cfdiId);
  } else {
    // Removing selection
    selectedCfdis.value.splice(index, 1);
    if (selectedCfdis.value.length === 0) {
      selectedRfc.value = null;
      selectedProducts.value = [];
    }
  }
  
  // Fetch products for selected CFDIs
  await fetchSelectedProducts();
};

// Fetch products/conceptos from selected CFDIs
const fetchSelectedProducts = async () => {
  if (selectedCfdis.value.length === 0) {
    selectedProducts.value = [];
    return;
  }
  
  try {
    const response = await axios.post('/compras/get-cfdi-conceptos', { 
      cfdi_ids: selectedCfdis.value 
    });
    selectedProducts.value = response.data.conceptos || [];
  } catch (err) {
    console.error('Error fetching conceptos:', err);
    selectedProducts.value = [];
  }
};

// Toggle select all (only same RFC)
const toggleSelectAll = () => {
  if (allSelected.value) {
    selectedCfdis.value = [];
    selectedRfc.value = null;
    selectedProducts.value = [];
  } else {
    // Select all with same RFC that are NOT imported, or all valid if no RFC selected
    const targetRfc = selectedRfc.value || receivedCfdis.value.find(c => !c.importado)?.emisor_rfc;
    
    if (targetRfc) {
      selectedRfc.value = targetRfc;
      selectedCfdis.value = receivedCfdis.value
        .filter(cfdi => cfdi.emisor_rfc === targetRfc && !cfdi.importado)
        .map(cfdi => cfdi.id);
      fetchSelectedProducts();
    }
  }
};

// Bulk import function - NOW enters review mode instead of direct import
const bulkImportCfdis = async () => {
  if (selectedCfdis.value.length === 0) return;
  
  // Enter review mode
  bulkReviewMode.value = true;
  bulkQueue.value = [...selectedCfdis.value];
  bulkCurrentIndex.value = 0;
  bulkResults.value = { success: [], skipped: [], errors: [] };
  
  // Load first CFDI for review
  await loadCfdiForReview(bulkQueue.value[0]);
};

// Load a CFDI for review modal
const loadCfdiForReview = async (cfdiId) => {
  loading.value = true;
  error.value = '';
  try {
    const response = await axios.get(`/compras/cfdi/${cfdiId}/preview`);
    await handleCfdiLoaded(response.data);
  } catch (err) {
    console.error('Error loading CFDI for review:', err);
    const errorMsg = err.response?.data?.message || err.message || 'Error desconocido';
    // Mark as error and advance
    bulkResults.value.errors.push({ 
      id: cfdiId, 
      folio: receivedCfdis.value.find(c => c.id === cfdiId)?.folio || cfdiId,
      error: errorMsg 
    });
    nextInBulkQueue();
  } finally {
    loading.value = false;
  }
};

// Advance to next CFDI in bulk queue
const nextInBulkQueue = async () => {
  bulkCurrentIndex.value++;
  cfdiData.value = null; // Clear current data
  
  if (bulkCurrentIndex.value >= bulkQueue.value.length) {
    // Queue finished, show summary
    showBulkSummary();
  } else {
    // Load next CFDI
    await loadCfdiForReview(bulkQueue.value[bulkCurrentIndex.value]);
  }
};

// Skip current CFDI in bulk queue
const skipCurrentInBulk = () => {
  const cfdiId = bulkQueue.value[bulkCurrentIndex.value];
  const cfdi = receivedCfdis.value.find(c => c.id === cfdiId);
  bulkResults.value.skipped.push({ 
    id: cfdiId, 
    folio: cfdi ? `${cfdi.serie}${cfdi.folio}` : cfdiId 
  });
  nextInBulkQueue();
};

// Cancel entire bulk review process
const cancelBulkReview = () => {
  bulkReviewMode.value = false;
  bulkQueue.value = [];
  bulkCurrentIndex.value = 0;
  bulkResults.value = { success: [], skipped: [], errors: [] };
  cfdiData.value = null;
  selectedCfdis.value = [];
  selectedRfc.value = null;
  selectedProducts.value = [];
};

// Show summary after bulk review completes
const showBulkSummary = () => {
  bulkReviewMode.value = false;
  cfdiData.value = null;
  
  const successCount = bulkResults.value.success.length;
  const skippedCount = bulkResults.value.skipped.length;
  const errorCount = bulkResults.value.errors.length;
  
  let message = `✅ Importación masiva completada:\n\n`;
  message += `• ${successCount} compra(s) creada(s)\n`;
  if (skippedCount > 0) {
    message += `• ${skippedCount} omitida(s)\n`;
  }
  if (errorCount > 0) {
    message += `• ${errorCount} error(es):\n`;
    bulkResults.value.errors.forEach(e => {
      message += `  - ${e.folio}: ${e.error}\n`;
    });
  }
  
  Swal.fire({
    icon: errorCount > 0 ? 'warning' : 'success',
    title: 'Resumen de Importación Masiva',
    text: message,
    confirmButtonColor: '#10B981',
  }).then(() => {
    // Refresh list of pending files
    fetchReceivedCfdis();
    
    // Notify parent to refresh purchases table
    if (successCount > 0) {
      emit('import', { compra_creada: true, count: successCount });
    }
  });
  
  // Cleanup selections
  selectedCfdis.value = [];
  selectedRfc.value = null;
  selectedProducts.value = [];
  bulkQueue.value = [];
  bulkCurrentIndex.value = 0;
  bulkResults.value = { success: [], skipped: [], errors: [] };
  
  // Refresh list
  fetchReceivedCfdis();
  
  // Emit event to refresh parent
  emit('imported');
  
  // Reload page to show new purchases in datatable
  if (successCount > 0) {
    window.location.reload();
  }
};

// Almacenes para selector
const almacenes = ref([]);
const selectedAlmacenId = ref('');
const creandoCompra = ref(false);

// Cargar almacenes
const fetchAlmacenes = async () => {
  try {
    const response = await axios.get('/api/almacenes');
    // Manejar estructura de respuesta { success: true, data: [...] }
    if (response.data && Array.isArray(response.data.data)) {
      almacenes.value = response.data.data;
    } else if (Array.isArray(response.data)) {
      almacenes.value = response.data;
    } else {
      almacenes.value = [];
    }
    
    // Preseleccionar el primero si existe
    if (almacenes.value.length > 0) {
      selectedAlmacenId.value = almacenes.value[0].id;
    }
  } catch (e) {
    console.error('Error cargando almacenes:', e);
    almacenes.value = [];
  }
};

// Inicializar almacenes desde props si están disponibles
watch(() => props.almacenesList, (newVal) => {
  if (newVal && newVal.length > 0) {
    almacenes.value = newVal;
    if (!selectedAlmacenId.value && almacenes.value.length > 0) {
      selectedAlmacenId.value = almacenes.value[0].id;
    }
  }
}, { immediate: true });

// Cuentas bancarias
const debugBanksResponse = ref("");

// Cargar cuentas bancarias
const fetchCuentasBancarias = async () => {
  try {
    const response = await axios.get('/api/cuentas-bancarias/activas');
    // Try both paths just to be safe while debugging
    if (Array.isArray(response.data)) {
        cuentasBancarias.value = response.data;
    } else if (response.data && Array.isArray(response.data.data)) {
        cuentasBancarias.value = response.data.data;
    } else {
        cuentasBancarias.value = [];
    }
  } catch (e) {
    console.error('Error cargando cuentas bancarias:', e);
  }
};

const fetchReceivedCfdis = async () => {
    loadingCfdis.value = true;
    try {
        const oneYearAgo = new Date();
        oneYearAgo.setFullYear(oneYearAgo.getFullYear() - 1);
        const fechaInicio = oneYearAgo.toISOString().split('T')[0];

        const response = await axios.get(route('compras.received-cfdis'), {
            params: { 
                search: searchCfdi.value,
                fecha_inicio: fechaInicio // Filtro de 12 meses
            }
        });
        receivedCfdis.value = response.data;
    } catch (e) {
        console.error("Error fetching received cfdis", e);
    } finally {
        loadingCfdis.value = false;
    }
};

const debounceSearch = debounce(() => {
    fetchReceivedCfdis();
}, 500);

watch(activeTab, (newVal) => {
    if (newVal === 'select') {
        fetchReceivedCfdis();
    }
});

watch(() => props.show, (newVal) => {
    if (newVal && activeTab.value === 'select') {
        fetchReceivedCfdis();
    }
});

onMounted(async () => {
    await Promise.all([
        fetchAlmacenes(),
        fetchCuentasBancarias()
    ]);
    
    if (activeTab.value === 'select') {
        fetchReceivedCfdis();
    }
});

const processSelectedCfdi = async (cfdiId) => {
    loading.value = true;
    error.value = '';
    
    try {
        const response = await axios.post('/compras/parse-xml', { cfdi_id: cfdiId }); // Send JSON, not FormData

        if (response.data.success) {
            await handleCfdiLoaded(response.data.data);
        } else {
            error.value = response.data.message || 'Error al procesar el XML importado';
        }
    } catch (err) {
        console.error('Error al procesar XML seleccionado:', err);
        error.value = err.response?.data?.message || 'Error al importar datos del CFDI';
    } finally {
        loading.value = false;
    }
};

const close = () => {
  resetUpload();
  emit('close');
};

const resetUpload = () => {
  cfdiData.value = null;
  error.value = '';
  loading.value = false;
  if (fileInput.value) {
    fileInput.value.value = '';
  }
  newProviderEmail.value = '';
  newProviderPhone.value = '';
  puePagado.value = false;
  pueMetodoPago.value = 'transferencia';
  pueCuentaBancariaId.value = '';
  // Don't reset activeTab to keep user context if they cancel and reopen, or maybe restart?
  // activeTab.value = 'upload'; 
};

const handleFileSelect = (event) => {
  const file = event.target.files?.[0];
  if (file) {
    processFile(file);
  }
};

const handleDrop = (event) => {
  isDragging.value = false;
  const file = event.dataTransfer?.files?.[0];
  if (file) {
    processFile(file);
  }
};

// Shared function to handle loaded CFDI data and auto-detect PUE
async function handleCfdiLoaded(data) {
  cfdiData.value = data;
  
  // Reset PUE fields defaults
  puePagado.value = false;
  pueMetodoPago.value = 'transferencia';
  pueCuentaBancariaId.value = '';

  // Auto-check if PUE
  if (data?.metodo_pago === 'PUE') {
      puePagado.value = true;
      
      // Ensure banks are loaded
      if (cuentasBancarias.value.length === 0) {
          await fetchCuentasBancarias();
      }

      // Auto-select first bank account if available
      if (cuentasBancarias.value.length > 0) {
          pueCuentaBancariaId.value = cuentasBancarias.value[0].id;
      }

      // Pre-map payment method if possible
      if (data.forma_pago) {
          const mapping = {
              '01': 'efectivo',
              '02': 'cheque', // Added cheque
              '03': 'transferencia',
              '04': 'tarjeta',
              '28': 'tarjeta'
          };
          // Try to match exact or first 2 chars
          const fp = data.forma_pago.substring(0, 2);
          pueMetodoPago.value = mapping[fp] || 'transferencia';
      }
  }
};

const processFile = async (file) => {
  // Validar extensión
  if (!file.name.toLowerCase().endsWith('.xml')) {
    error.value = 'El archivo debe tener extensión .xml';
    return;
  }

  loading.value = true;
  error.value = '';

  try {
    const formData = new FormData();
    formData.append('xml_file', file);

    const response = await axios.post('/compras/parse-xml', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    if (response.data.success) {
      await handleCfdiLoaded(response.data.data);
    } else {
      error.value = response.data.message || 'Error al procesar el XML';
    }
  } catch (err) {
    console.error('Error al procesar XML:', err);
    error.value = err.response?.data?.message || 'Error al procesar el archivo XML';
  } finally {
    loading.value = false;
  }
};

const confirmarImportacion = async () => {
  if (!cfdiData.value) return;
  
  // Validaciones
  if (!selectedAlmacenId.value) {
    Swal.fire({
      icon: 'warning',
      title: 'Atención',
      text: 'Por favor selecciona un almacén destino.',
      confirmButtonColor: '#10B981',
    });
    return;
  }
  
  if (!cfdiData.value.proveedor_encontrado) {
    Swal.fire({
      icon: 'warning',
      title: 'Proveedor no registrado',
      text: 'Por favor registra el proveedor antes de continuar.',
      confirmButtonColor: '#10B981',
    });
    return;
  }
  
  // Verificar que todos los productos estén mapeados
  const productosNoMapeados = cfdiData.value.conceptos.filter(c => !c.producto_id);
  if (productosNoMapeados.length > 0) {
    Swal.fire({
      icon: 'warning',
      title: 'Productos sin vincular',
      text: `Hay ${productosNoMapeados.length} producto(s) sin mapear. Por favor agrega los productos faltantes antes de importar.`,
      confirmButtonColor: '#10B981',
    });
    return;
  }

  // VALIDACIÓN: Verificar que los productos que requieren serie tengan todas las series capturadas
  const productosSinSeries = cfdiData.value.conceptos.filter(c => 
    c.requiere_serie && (!c.seriales || c.seriales.length < c.cantidad)
  );

  if (productosSinSeries.length > 0) {
    const nombres = productosSinSeries.map(p => p.descripcion.substring(0, 30) + '...').join('<br>');
    Swal.fire({
      icon: 'warning',
      title: 'Series Faltantes',
      html: `Los siguientes productos requieren captura de series:<br><br><b>${nombres}</b><br><br>Por favor captura las series faltantes usando el botón en la columna "Estatus" antes de continuar.`,
      confirmButtonColor: '#10B981',
    });
    return;
  }

  // Validación PUE
  if (cfdiData.value.metodo_pago === 'PUE' && puePagado.value) {
      if (!pueCuentaBancariaId.value) {
          Swal.fire({
            icon: 'warning',
            title: 'Falta Cuenta Bancaria',
            text: 'Por favor selecciona una cuenta bancaria para el pago.',
            confirmButtonColor: '#10B981',
          });
          return;
      }
      if (!pueMetodoPago.value) {
          Swal.fire({
            icon: 'warning',
            title: 'Falta Método de Pago',
            text: 'Por favor selecciona un método de pago.',
            confirmButtonColor: '#10B981',
          });
          return;
      }
  }
  
  creandoCompra.value = true;
  error.value = '';
  
  try {
    // Construir lista de productos para la compra
    const productos = cfdiData.value.conceptos.map(concepto => ({
      id: concepto.producto_id,
      cantidad: parseInt(concepto.cantidad) || 1,
      precio: parseFloat(concepto.valor_unitario) || 0,
      descuento: 0,
      seriales: concepto.seriales || [],
    }));
    
    // Payload para crear compra
    const payload = {
      proveedor_id: cfdiData.value.proveedor_encontrado.id,
      almacen_id: selectedAlmacenId.value,
      metodo_pago: cfdiData.value.metodo_pago || 'transferencia',
      productos: productos,
      descuento_general: cfdiData.value.descuento || 0,
      aplicar_retencion_iva: false,
      aplicar_retencion_isr: false,
      notas: `Importado desde CFDI ${cfdiData.value.serie || ''}${cfdiData.value.folio || ''} - UUID: ${cfdiData.value.uuid || ''}`,
      
      // Pasar fecha del CFDI como fecha de compra
      fecha_compra: cfdiData.value.fecha || cfdiData.value.fecha_emision,
      
      // Datos adicionales del CFDI para persistencia
      cfdi_uuid: cfdiData.value.uuid,
      cfdi_serie: cfdiData.value.serie,
      cfdi_folio: cfdiData.value.folio,
      cfdi_fecha: cfdiData.value.fecha || cfdiData.value.fecha_emision,
      cfdi_emisor_rfc: cfdiData.value.emisor?.rfc,
      cfdi_emisor_nombre: cfdiData.value.emisor?.nombre || cfdiData.value.emisor?.Nombre,
      cfdi_total: cfdiData.value.total,
      
      // Datos de pago PUE
      pagado_importacion: puePagado.value,
      pue_metodo_pago: pueMetodoPago.value,
      pue_cuenta_bancaria_id: pueCuentaBancariaId.value,
    };
    
    console.log('Creando compra con payload:', payload);
    
    const response = await axios.post(route('compras.store'), payload);
    
    // La compra se creó exitosamente
    if (bulkReviewMode.value) {
      // Bulk mode: register success and advance to next
      const cfdiId = bulkQueue.value[bulkCurrentIndex.value];
      const cfdi = receivedCfdis.value.find(c => c.id === cfdiId);
      bulkResults.value.success.push({ 
        id: cfdiId, 
        folio: cfdi ? `${cfdi.serie}${cfdi.folio}` : cfdiId,
        compra_id: response.data.compra?.id || response.data.id
      });
      nextInBulkQueue();
    } else {
      // Normal mode: close and reload
      // alert('¡Compra creada exitosamente!'); // Removed alert to use Notyf in parent
      emit('import', { compra_creada: true, ...response.data });
      close();
      // window.location.reload(); // Dejar que el componente padre maneje el refresco
    }
    
  } catch (err) {
    console.error('Error al crear compra:', err);
    
    // Manejo específico para saldo insuficiente
    if (err.response?.data?.error_code === 'SALDO_INSUFICIENTE') {
        const d = err.response.data.details;
        Swal.fire({
            icon: 'error',
            title: `⚠️ Saldo Insuficiente en ${d.banco}`,
            html: `
                <div class="text-left mt-2">
                    <p class="mb-1"><strong>💰 Disponible:</strong> $${d.disponible}</p>
                    <p class="mb-1"><strong>📉 Requerido:</strong> $${d.requerido}</p>
                    <p class="mb-3 text-red-600 font-bold"><strong>❗ Faltante:</strong> $${d.faltante}</p>
                    <p class="text-sm text-gray-600">Por favor ingresa saldo a la cuenta o desmarca el pago automático.</p>
                </div>
            `,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#EF4444',
        });
        error.value = ''; // Limpiar error genérico para que no estorbe
        return;
    }

    const errorMsg = err.response?.data?.message || err.response?.data?.error || err.message || 'Error desconocido al crear la compra';
    
    if (bulkReviewMode.value) {
      // Bulk mode: register error and advance
      const cfdiId = bulkQueue.value[bulkCurrentIndex.value];
      const cfdi = receivedCfdis.value.find(c => c.id === cfdiId);
      bulkResults.value.errors.push({ 
        id: cfdiId, 
        folio: cfdi ? `${cfdi.serie}${cfdi.folio}` : cfdiId,
        error: errorMsg 
      });
      nextInBulkQueue();
    } else {
      Swal.fire({
          icon: 'error',
          title: 'Error al importar',
          text: errorMsg,
          confirmButtonColor: '#EF4444',
      });
      error.value = errorMsg;
    }
  } finally {
    creandoCompra.value = false;
  }
};

const formatMoney = (value) => {
  const num = parseFloat(value) || 0;
  return num.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  try {
    // Parsear la fecha manualmente para evitar interpretación UTC
    // Soporta formatos: '2025-11-28', '2025-11-28 15:30:00', '2025-11-28T15:30:00'
    const cleanStr = dateStr.replace('T', ' ').split(' ')[0]; // Solo la parte de la fecha
    const [year, month, day] = cleanStr.split('-').map(Number);
    
    if (!year || !month || !day) return dateStr;
    
    // Crear fecha explícitamente en tiempo local
    const date = new Date(year, month - 1, day);
    return date.toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: 'numeric' });
  } catch {
    return dateStr;
  }
};

// Catálogo de regímenes fiscales SAT
const getRegimenFiscalNombre = (codigo) => {
  const regimenes = {
    '601': 'General de Ley PM',
    '603': 'Agricultores, Ganaderos, Silvícolas y Pescadores',
    '605': 'Sueldos y Salarios',
    '606': 'Arrendamiento',
    '607': 'Enajenación de Bienes',
    '608': 'Demás Ingresos',
    '610': 'Residentes en el Extranjero',
    '611': 'Dividendos',
    '612': 'Actividades Empresariales y Profesionales',
    '614': 'Intereses',
    '615': 'Obtención de Premios',
    '616': 'Sin Obligaciones Fiscales',
    '620': 'Sociedades Cooperativas de Producción',
    '621': 'Incorporación Fiscal',
    '622': 'Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras',
    '623': 'Opcional para Grupos de Sociedades',
    '624': 'Coordinados',
    '625': 'RIF (Simplificado de Confianza)',
    '626': 'Simplificado de Confianza',
  };
  return regimenes[codigo] || 'Desconocido';
};

/**
 * Genera URL para crear proveedor con datos del emisor del CFDI
 * @param {Object} emisor - Datos del emisor (rfc, nombre, regimen_fiscal)
 * @returns {string} URL con parámetros prellenados
 */
const generarUrlProveedor = (emisor) => {
  const params = new URLSearchParams();
  if (emisor.rfc) params.append('rfc', emisor.rfc);
  if (emisor.nombre) params.append('nombre_razon_social', emisor.nombre);
  
  if (emisor.rfc && emisor.rfc.length === 12) {
    params.append('tipo_persona', 'moral');
  } else if (emisor.rfc && emisor.rfc.length === 13) {
    params.append('tipo_persona', 'fisica');
  }
  
  if (emisor.regimen_fiscal) {
    params.append('regimen_fiscal', emisor.regimen_fiscal);
  }
  
  // Uso CFDI eliminado por solicitud
  // params.append('uso_cfdi', 'G03');
  
  return `/proveedores/create?${params.toString()}`;
};

const registrandoProveedor = ref(false);

const registrarProveedorExpress = async () => {
  if (!cfdiData.value?.emisor) return;

  registrandoProveedor.value = true;

  try {
    const emisor = cfdiData.value.emisor;
    // Validar nombre antes de enviar
    const nombreEmisor = emisor.nombre || emisor.Nombre || '';
    if (!nombreEmisor) {
      console.warn('Nombre de emisor no encontrado en CFDI data', emisor);
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'El nombre del proveedor no se encontró en el XML.',
        confirmButtonColor: '#EF4444',
      });
      return;
    }

    // Construir payload con los 4 datos requeridos para timbrar + datos adicionales
    const payload = {
      // 4 datos requeridos para timbrar
      rfc: emisor.rfc,
      nombre_razon_social: nombreEmisor,
      regimen_fiscal: emisor.regimen_fiscal,
      codigo_postal: cfdiData.value.lugar_expedicion || '', // CP del lugar de expedición
      
      // Datos adicionales
      tipo_persona: emisor.rfc && emisor.rfc.length === 13 ? 'fisica' : 'moral',
      activo: true,
      email: newProviderEmail.value,
      telefono: newProviderPhone.value,
    };

    console.log('Registrando proveedor con datos:', payload);

    const response = await axios.post(route('proveedores.store'), payload);

    if (response.data.success) {
       // Guardar el objeto completo para que el padre pueda actualizar su lista
       cfdiData.value.proveedor_encontrado = response.data.proveedor; 
       
       // IMPORTANTE: Asignar el ID devuelto para que se use al guardar la compra
       if (cfdiData.value.emisor) {
         cfdiData.value.emisor.id = response.data.proveedor.id; 
       }
       // También actualizar el indicador global si existe
       cfdiData.value.proveedor_id = response.data.proveedor.id;

       Swal.fire({
        icon: 'success',
        title: 'Proveedor Registrado',
        text: 'Proveedor registrado correctamente.',
        timer: 1500,
        showConfirmButton: false,
      });
    }
  } catch (error) {
    console.error(error);
    Swal.fire({
      icon: 'error',
      title: 'Error al registrar',
      text: 'Error al registrar proveedor: ' + (error.response?.data?.message || error.message || 'Error desconocido'),
      confirmButtonColor: '#EF4444',
    });
  } finally {
    registrandoProveedor.value = false;
  }
};

const registrandoProducto = ref(new Set()); // Deprecated logic state, kept if needed for transition but currently unused

// --- Lógica Modal Producto Manual ---
const showProductModal = ref(false);
const savingProduct = ref(false);
const catalogosLoaded = ref(false);
const catalogos = ref({ categorias: [], marcas: [], unidades: [] });
const currentConceptIndex = ref(null);
const currentConcept = ref(null);

// Modal Categoría
const showCategoriaModal = ref(false);
const savingCategoria = ref(false);
const nuevaCategoria = ref({ nombre: '', descripcion: '', estado: 'activo' });

const saveCategoria = async () => {
  if (!nuevaCategoria.value.nombre.trim()) return;
  savingCategoria.value = true;
  try {
    const response = await axios.post('/api/categorias', nuevaCategoria.value);
    if (response.data && response.data.id) {
      catalogos.value.categorias.push(response.data);
    productForm.value.categoria_id = response.data.id;
    showCategoriaModal.value = false;
    nuevaCategoria.value = { nombre: '', descripcion: '', estado: 'activo' };
    Swal.fire({
      icon: 'success',
      title: 'Categoría Guardada',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000
    });
  }
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Error al guardar categoría: ' + (error.response?.data?.message || error.message),
      confirmButtonColor: '#EF4444',
    });
  } finally {
    savingCategoria.value = false;
  }
};

// Modal Marca
const showMarcaModal = ref(false);
const savingMarca = ref(false);
const nuevaMarca = ref({ nombre: '', descripcion: '', estado: 'activo' });

const saveMarca = async () => {
  if (!nuevaMarca.value.nombre.trim()) return;
  savingMarca.value = true;
  try {
    const response = await axios.post('/api/marcas', nuevaMarca.value);
    if (response.data && response.data.id) {
      const marca = response.data;
      catalogos.value.marcas.push(marca);
      productForm.value.marca_id = marca.id;
      showMarcaModal.value = false;
      nuevaMarca.value = { nombre: '', descripcion: '', estado: 'activo' };
    }
  } catch (error) {
    console.error(error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Error al guardar marca: ' + (error.response?.data?.message || error.message),
      confirmButtonColor: '#EF4444',
    });
  } finally {
    savingMarca.value = false;
  }
};

// Modal de Series (para conceptos que requieren serie)
const showSerialModal = ref(false);
const serialModalIndex = ref(null);
const conceptSerialInput = ref('');
const currentSerials = ref([]);

const openSerialModal = (index) => {
  const concepto = cfdiData.value.conceptos[index];
  serialModalIndex.value = index;
  currentSerials.value = [...(concepto.seriales || [])];
  conceptSerialInput.value = '';
  showSerialModal.value = true;
};

const addConceptSerial = () => {
  const serial = conceptSerialInput.value.trim();
  if (!serial) return;
  
  const concepto = cfdiData.value.conceptos[serialModalIndex.value]; // Get the concept here
  if (!concepto.seriales) concepto.seriales = []; // Initialize if not present

  if (concepto.seriales.includes(serial)) {
    Swal.fire({
      icon: 'warning',
      title: 'Serie Duplicada',
      text: 'Este número de serie ya fue agregado.',
      toast: true,
      position: 'top-end',
      timer: 3000,
      showConfirmButton: false
    });
    conceptSerialInput.value = ''; // Clear input after warning
    return;
  }
  
  if (concepto.seriales.length >= concepto.cantidad) {
    Swal.fire({
      icon: 'info',
      title: 'Completo',
      text: `Solo se requieren ${concepto.cantidad} serie(s) para este producto.`,
      toast: true,
      position: 'top-end',
      timer: 3000,
      showConfirmButton: false
    });
    return;
  }
  
  concepto.seriales.push(serial);
  conceptSerialInput.value = '';
};

const removeConceptSerial = (index) => {
  currentSerials.value.splice(index, 1);
};

const closeSerialModal = (save = false) => {
  if (save && serialModalIndex.value !== null) {
    // Guardar seriales en el concepto
    cfdiData.value.conceptos[serialModalIndex.value].seriales = [...currentSerials.value];
  }
  showSerialModal.value = false;
  serialModalIndex.value = null;
  currentSerials.value = [];
  conceptSerialInput.value = '';
};

const productForm = ref({
  nombre: '',
  codigo: '',
  codigo_barras: '',
  categoria_id: '',
  marca_id: '',
  precio_compra: 0,
  precio_venta: 0,
  unidad_medida: '',
  requiere_serie: false,
  tipo_producto: 'fisico',
  estado: 'activo',
  // Campos SAT del XML
  sat_clave_prod_serv: '',
  sat_clave_unidad: '',
  sat_objeto_imp: '02', // Por defecto: Sí objeto de impuesto
  // Stock inicial
  stock: 0,
  descripcion: '',
});

// Variables para captura de series
const productSerials = ref([]);
const currentSerial = ref('');
const serialInput = ref(null);

// Funciones para gestionar series
const addSerial = () => {
  const serial = currentSerial.value.trim();
  if (!serial) return;
  
  // Validar que no exista duplicado
  if (productSerials.value.includes(serial)) {
    Swal.fire({
      icon: 'warning',
      title: 'Serie Duplicada',
      text: 'Este número de serie ya fue agregado.',
      toast: true,
      position: 'top-end',
      timer: 3000,
      showConfirmButton: false
    });
    return;
  }
  
  // Validar que no exceda el stock
  if (productSerials.value.length >= productForm.value.stock) {
    Swal.fire({
      icon: 'info',
      title: 'Completo',
      text: 'Ya se capturaron todas las series necesarias.',
      toast: true,
      position: 'top-end',
      timer: 3000,
      showConfirmButton: false
    });
    return;
  }
  
  productSerials.value.push(serial);
  currentSerial.value = '';
  
  // Mantener focus para escáner continuo
  if (serialInput.value) {
    serialInput.value.focus();
  }
};

const removeSerial = (index) => {
  productSerials.value.splice(index, 1);
};

      // Aceptamos también variaciones menores en la longitud
// Variable para saber si el modal se abrió desde la vista bulk
const isBulkProductModal = ref(false);

const openProductModal = (concepto, index, isBulk = false) => {
  currentConcept.value = concepto;
  currentConceptIndex.value = index;
  isBulkProductModal.value = isBulk;
  
  // Función helper para detectar series
  const pareceNumeroSerie = (val) => /^(\d{4,8}-\d{1,4}-\d{3,6})$/.test(val || '');
  const noIdent = (concepto.no_identificacion || '').trim();
  const esNumeroDeSerie = pareceNumeroSerie(noIdent);

  // Resetear formulario con datos del concepto
  productForm.value = {
    nombre: concepto.descripcion,
    codigo: noIdent || '', // Autofill con no_identificacion si existe
    codigo_barras: esNumeroDeSerie ? ('GEN-' + Date.now()) : (noIdent || ('GEN-' + Date.now())),
    categoria_id: catalogos.value.categorias.length > 0 ? catalogos.value.categorias[0]?.id : '',
    marca_id: catalogos.value.marcas.length > 0 ? catalogos.value.marcas[0]?.id : '',
    precio_compra: Math.round((concepto.valor_unitario || 0) * 100) / 100,
    precio_venta: Math.round(((concepto.valor_unitario || 0) * 1.3) * 100) / 100,
    unidad_medida: concepto.unidad || concepto.clave_unidad || 'PZA',
    requiere_serie: esNumeroDeSerie,
    tipo_producto: 'fisico',
    estado: 'activo',
    sat_clave_prod_serv: concepto.clave_prod_serv || '',
    sat_clave_unidad: concepto.clave_unidad || '',
    sat_objeto_imp: '02',
    stock: parseInt(concepto.cantidad) || 1,
    descripcion: concepto.descripcion
  };

  // Resetear series
  if (esNumeroDeSerie && noIdent) {
      productSerials.value = [noIdent];
  } else {
      productSerials.value = [];
  }
  currentSerial.value = '';
  
  showProductModal.value = true;
  
  // Cargar catálogos si no están cargados
  if (!catalogosLoaded.value) {
    fetchCatalogos();
  }
};

const fetchCatalogos = async () => {
  try {
    // Usar headers que evitan la intercepción de Inertia
    const ajaxHeaders = { 
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    };
    
    const [catRes, marcaRes] = await Promise.all([
      axios.get('/categorias', { headers: ajaxHeaders }),
      axios.get('/marcas', { headers: ajaxHeaders })
    ]);
    
    // Helper para extraer array de respuesta (ya sea directa o envuelta en 'data', o Resource 'data')
    const extractData = (res) => {
        if (Array.isArray(res.data)) return res.data;
        if (res.data && Array.isArray(res.data.data)) return res.data.data;
        return res.data || [];
    };

    catalogos.value = {
      categorias: extractData(catRes),
      marcas: extractData(marcaRes),
      unidades: [] // Ruta /unidades no existe, usar lista vacía
    };
    catalogosLoaded.value = true;
    console.log('Catálogos cargados:', catalogos.value);
  } catch (error) {
    console.error('Error cargando catálogos:', error);
  }
};

const saveProduct = async () => {
  if (!productForm.value.nombre || !productForm.value.precio_compra) {
    Swal.fire({
      icon: 'warning',
      title: 'Campos vacíos',
      text: 'Por favor complete los campos obligatorios',
      confirmButtonColor: '#10B981',
    });
    return;
  }
  
  if (productForm.value.requiere_serie && productSerials.value.length < productForm.value.stock) {
    Swal.fire({
      icon: 'warning',
      title: 'Series incompletas',
      text: `Debe capturar las ${productForm.value.stock} series.`,
      confirmButtonColor: '#10B981',
    });
    return;
  }
  
  savingProduct.value = true;
  try {
    const payload = {
        ...productForm.value,
        seriales: productSerials.value
    };
    
    const response = await axios.post('/productos', payload);
    const newProduct = response.data.producto || response.data;
    
    if (isBulkProductModal.value) {
        await fetchSelectedProducts();
    } else {
        if (cfdiData.value && cfdiData.value.conceptos[currentConceptIndex.value]) {
          const c = cfdiData.value.conceptos[currentConceptIndex.value];
          c.producto_id = newProduct.id;
          c.producto_nombre = newProduct.nombre;
          c.match_type = 'exact';
          if (newProduct.requiere_serie) {
              c.requiere_serie = true;
              c.seriales = [...productSerials.value];
          }
        }
    }
    
    showProductModal.value = false;
    
    Swal.fire({
      icon: 'success',
      title: 'Producto Guardado',
      text: 'El producto se ha guardado correctamente.',
      toast: true,
      position: 'top-end',
      timer: 3000,
      showConfirmButton: false
    });
    
  } catch (error) {
    console.error('Error:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error al guardar',
      text: 'Error al guardar producto: ' + (error.response?.data?.message || error.message),
      confirmButtonColor: '#EF4444',
    });
  } finally {
    savingProduct.value = false;
  }
};
</script>

