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
            <ImportXmlModalUpload
              v-if="!cfdiData && !loading && activeTab === 'upload'"
              :key="uploadInputKey"
              v-model:isDragging="isDragging"
              :error="error"
              @file-select="handleFileSelect"
              @file-drop="handleDrop"
            />

            <!-- Estado: Seleccionar Recibido -->
            <ImportXmlModalSelect
              v-if="!cfdiData && !loading && activeTab === 'select'"
              v-model:searchCfdi="searchCfdi"
              v-model:showImported="showImported"
              :loadingCfdis="loadingCfdis"
              :receivedCfdis="receivedCfdis"
              :displayedCfdis="displayedCfdis"
              :selectedCfdis="selectedCfdis"
              :allSelected="allSelected"
              :selectedEmisorNombre="selectedEmisorNombre"
              :totalSeleccionado="totalSeleccionado"
              :bulkImporting="bulkImporting"
              :bulkProgress="bulkProgress"
              :selectedProducts="selectedProducts"
              :formatDate="formatDate"
              :formatMoney="formatMoney"
              :debounceSearch="debounceSearch"
              :toggleSelectAll="toggleSelectAll"
              :toggleCfdiSelection="toggleCfdiSelection"
              :processSelectedCfdi="processSelectedCfdi"
              :bulkImportCfdis="bulkImportCfdis"
              :openProductModal="openProductModal"
            />

            <!-- Estado: Cargando -->
            <div v-if="loading" class="text-center py-12">
              <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-emerald-500 mx-auto mb-4"></div>
              <p class="text-gray-600">Procesando XML...</p>
            </div>

            <!-- Estado: Datos del CFDI -->
            <div v-if="cfdiData && !loading">
              <ImportXmlModalCfdiInfo
                :cfdiData="cfdiData"
                :formatDate="formatDate"
                :formatMoney="formatMoney"
                :getRegimenFiscalNombre="getRegimenFiscalNombre"
                v-model:newProviderEmail="newProviderEmail"
                v-model:newProviderPhone="newProviderPhone"
                :registrandoProveedor="registrandoProveedor"
                :registrarProveedorExpress="registrarProveedorExpress"
                v-model:selectedAlmacenId="selectedAlmacenId"
                :almacenes="almacenes"
                v-model:puePagado="puePagado"
                v-model:pueCuentaBancariaId="pueCuentaBancariaId"
                v-model:pueMetodoPago="pueMetodoPago"
                :cuentasBancarias="cuentasBancarias"
              />

              <ImportXmlModalConceptos
                :cfdiData="cfdiData"
                :formatMoney="formatMoney"
                :openProductModal="openProductModal"
                :openSerialModal="openSerialModal"
              />
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
    <ImportXmlModalProductModal
      :show="showProductModal"
      :productForm="productForm"
      :catalogos="catalogos"
      :currentConcept="currentConcept"
      :productSerials="productSerials"
      v-model:currentSerial="currentSerial"
      :savingProduct="savingProduct"
      :serialInput="serialInput"
      :addSerial="addSerial"
      :removeSerial="removeSerial"
      :saveProduct="saveProduct"
      @close="showProductModal = false"
      @open-categoria="showCategoriaModal = true"
      @open-marca="showMarcaModal = true"
    />

    <ImportXmlModalCategoriaModal
      :show="showCategoriaModal"
      :nuevaCategoria="nuevaCategoria"
      :savingCategoria="savingCategoria"
      :saveCategoria="saveCategoria"
      @close="showCategoriaModal = false"
    />

    <ImportXmlModalMarcaModal
      :show="showMarcaModal"
      :nuevaMarca="nuevaMarca"
      :savingMarca="savingMarca"
      :saveMarca="saveMarca"
      @close="showMarcaModal = false"
    />
    
    <ImportXmlModalConceptSerialModal
      :show="showSerialModal"
      :requiredCantidad="serialRequiredCantidad"
      v-model:conceptSerialInput="conceptSerialInput"
      :currentSerials="currentSerials"
      :addConceptSerial="addConceptSerial"
      :removeConceptSerial="removeConceptSerial"
      @close="closeSerialModal"
    />
  </Teleport>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import Swal from 'sweetalert2';
import axios from 'axios';
import { useConceptSerialModal } from '@/Composables/useConceptSerialModal';
import { useProductModal } from '@/Composables/useProductModal';
import { useBulkReview } from '@/Composables/useBulkReview';
import { useCfdiSelection } from '@/Composables/useCfdiSelection';
import { useAlmacenesPue } from '@/Composables/useAlmacenesPue';
import { useXmlImport } from '@/Composables/useXmlImport';
import { useCompraImport } from '@/Composables/useCompraImport';
import { useProveedorExpress } from '@/Composables/useProveedorExpress';
import { formatMoney, formatDate, getRegimenFiscalNombre, generarUrlProveedor } from '@/Utils/cfdiUtils';
import { useCfdiReceivedList } from '@/Composables/useCfdiReceivedList';
import ImportXmlModalUpload from '@/Components/Compras/ImportXmlModal/ImportXmlModalUpload.vue';
import ImportXmlModalSelect from '@/Components/Compras/ImportXmlModal/ImportXmlModalSelect.vue';
import ImportXmlModalCfdiInfo from '@/Components/Compras/ImportXmlModal/ImportXmlModalCfdiInfo.vue';
import ImportXmlModalConceptos from '@/Components/Compras/ImportXmlModal/ImportXmlModalConceptos.vue';
import ImportXmlModalProductModal from '@/Components/Compras/ImportXmlModal/ImportXmlModalProductModal.vue';
import ImportXmlModalConceptSerialModal from '@/Components/Compras/ImportXmlModal/ImportXmlModalConceptSerialModal.vue';
import ImportXmlModalCategoriaModal from '@/Components/Compras/ImportXmlModal/ImportXmlModalCategoriaModal.vue';
import ImportXmlModalMarcaModal from '@/Components/Compras/ImportXmlModal/ImportXmlModalMarcaModal.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  almacenesList: { type: Array, default: () => [] }
});

const emit = defineEmits(['close', 'import']);

const loading = ref(false);
const error = ref('');
const isDragging = ref(false);
const cfdiData = ref(null);
const uploadInputKey = ref(0);
const newProviderEmail = ref('');
const newProviderPhone = ref('');

const {
  almacenes,
  selectedAlmacenId,
  puePagado,
  pueMetodoPago,
  pueCuentaBancariaId,
  cuentasBancarias,
  fetchAlmacenes,
  fetchCuentasBancarias,
} = useAlmacenesPue({ axios, almacenesList: () => props.almacenesList });

// Logic for tabs and selecting received CFDIs
const activeTab = ref('select');
const searchCfdi = ref('');
const showImported = ref(false); // Default hidden

const {
  receivedCfdis,
  loadingCfdis,
  fetchReceivedCfdis,
  debounceSearch,
} = useCfdiReceivedList({
  axios,
  route,
  activeTab,
  searchCfdi,
  showImported,
  propsShow: () => props.show,
});

const {
  selectedCfdis,
  selectedRfc,
  selectedProducts,
  displayedCfdis,
  allSelected,
  selectedEmisorNombre,
  totalSeleccionado,
  fetchSelectedProducts,
  toggleCfdiSelection,
  toggleSelectAll,
} = useCfdiSelection({ receivedCfdis, showImported, searchCfdi, Swal, axios });

onMounted(async () => {
  await Promise.all([
    fetchAlmacenes(),
    fetchCuentasBancarias(),
  ]);
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
const {
  resetUpload,
  handleFileSelect,
  handleDrop,
  handleCfdiLoaded,
  processFile,
} = useXmlImport({
  axios,
  cfdiData,
  loading,
  error,
  isDragging,
  uploadInputKey,
  newProviderEmail,
  newProviderPhone,
  puePagado,
  pueMetodoPago,
  pueCuentaBancariaId,
  cuentasBancarias,
  fetchCuentasBancarias,
});

const {
  bulkImporting,
  bulkProgress,
  bulkReviewMode,
  bulkQueue,
  bulkCurrentIndex,
  bulkResults,
  bulkImportCfdis,
  nextInBulkQueue,
  skipCurrentInBulk,
  cancelBulkReview,
} = useBulkReview({
  axios,
  Swal,
  cfdiData,
  loading,
  error,
  selectedCfdis,
  selectedRfc,
  selectedProducts,
  receivedCfdis,
  fetchReceivedCfdis,
  handleCfdiLoaded,
  emit,
});

const { creandoCompra, confirmarImportacion } = useCompraImport({
  axios,
  Swal,
  cfdiData,
  selectedAlmacenId,
  puePagado,
  pueMetodoPago,
  pueCuentaBancariaId,
  bulkReviewMode,
  bulkQueue,
  bulkCurrentIndex,
  bulkResults,
  nextInBulkQueue,
  receivedCfdis,
  emit,
  close,
  error,
  fetchReceivedCfdis,
});

const { registrandoProveedor, registrarProveedorExpress } = useProveedorExpress({
  axios,
  Swal,
  cfdiData,
  newProviderEmail,
  newProviderPhone,
});

const registrandoProducto = ref(new Set()); // Deprecated logic state, kept if needed for transition but currently unused

// Modal de Series (para conceptos que requieren serie)
const {
  showSerialModal,
  serialModalIndex,
  conceptSerialInput,
  currentSerials,
  serialRequiredCantidad,
  openSerialModal,
  addConceptSerial,
  removeConceptSerial,
  closeSerialModal,
} = useConceptSerialModal({ cfdiData, Swal });

const {
  showProductModal,
  savingProduct,
  catalogos,
  currentConceptIndex,
  currentConcept,
  showCategoriaModal,
  savingCategoria,
  nuevaCategoria,
  saveCategoria,
  showMarcaModal,
  savingMarca,
  nuevaMarca,
  saveMarca,
  productForm,
  productSerials,
  currentSerial,
  serialInput,
  addSerial,
  removeSerial,
  openProductModal,
  saveProduct,
} = useProductModal({ cfdiData, Swal, axios, fetchSelectedProducts });
</script>
