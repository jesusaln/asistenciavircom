<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
  show: { type: Boolean, default: false },
  cuentasBancarias: { type: Array, default: () => [] }
});

const emit = defineEmits(['close', 'imported']);

// State
const loading = ref(false);
const loadingCfdis = ref(false);
const applying = ref(false);
const step = ref('source'); // 'source' | 'preview' | 'success'
const sourceTab = ref('select'); // 'select' | 'upload'
const error = ref('');

// CFDI Selection
const paymentCfdis = ref([]);
const searchQuery = ref('');
const selectedCfdiId = ref(null);

// File upload
const xmlFile = ref(null);
const dragOver = ref(false);

// Parsed payment data
const paymentInfo = ref(null);
const matches = ref([]);
const totalDocumentos = ref(0);
const documentosEncontrados = ref(0);
const excedente = ref(0);
const otrasCuentas = ref([]);
const mostrarOtrasCuentas = ref(false);

// Apply payment form
const metodoPago = ref('transferencia');
const cuentaBancariaId = ref(null);
const notas = ref('');

// Computed
const selectedPayments = computed(() => {
  return matches.value.filter(m => m.found && m.selected);
});

const canApply = computed(() => {
  return selectedPayments.value.length > 0 && !applying.value;
});

// Watch for modal open/close
watch(() => props.show, (newVal) => {
  if (newVal) {
    fetchPaymentCfdis();
  }
});

// Fetch CFDIs on mount if shown
onMounted(() => {
  if (props.show) {
    fetchPaymentCfdis();
  }
});

// Fetch payment CFDIs
const fetchPaymentCfdis = async () => {
  loadingCfdis.value = true;
  error.value = '';
  try {
    console.log('Fetching payment CFDIs...');
    const response = await axios.get('/cuentas-por-pagar/get-payment-cfdis', {
      params: { search: searchQuery.value }
    });
    console.log('Response:', response.data);
    if (response.data.success) {
      paymentCfdis.value = response.data.cfdis;
      console.log('Loaded CFDIs:', paymentCfdis.value.length);
    } else {
      error.value = response.data.message || 'Error al cargar CFDIs';
    }
  } catch (err) {
    console.error('Error fetching CFDIs:', err);
    error.value = 'Error al cargar CFDIs: ' + (err.response?.data?.message || err.message);
  } finally {
    loadingCfdis.value = false;
  }
};

// Search with debounce
let searchTimeout = null;
const handleSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchPaymentCfdis();
  }, 300);
};

// Select a CFDI and process it
const selectCfdi = async (cfdi) => {
  selectedCfdiId.value = cfdi.id;
  loading.value = true;
  error.value = '';
  
  try {
    const response = await axios.post('/cuentas-por-pagar/process-payment-cfdi', {
      cfdi_id: cfdi.id
    });
    
    if (response.data.success) {
      paymentInfo.value = response.data.payment_info;
      metodoPago.value = response.data.payment_info.metodo_pago_sistema || 'transferencia';
      matches.value = response.data.matches.map(m => ({ ...m, selected: m.found }));
      otrasCuentas.value = response.data.otras_cuentas || [];
      excedente.value = response.data.excedente || 0;
      totalDocumentos.value = response.data.total_documentos;
      documentosEncontrados.value = response.data.documentos_encontrados;
      step.value = 'preview';
    } else {
      error.value = response.data.message || 'Error al procesar el CFDI';
    }
  } catch (err) {
    console.error('Error:', err);
    error.value = err.response?.data?.message || 'Error al procesar el CFDI';
  } finally {
    loading.value = false;
    selectedCfdiId.value = null;
  }
};

// File upload methods
const handleDragOver = (e) => {
  e.preventDefault();
  dragOver.value = true;
};

const handleDragLeave = () => {
  dragOver.value = false;
};

const handleDrop = (e) => {
  e.preventDefault();
  dragOver.value = false;
  const files = e.dataTransfer.files;
  if (files.length > 0) {
    handleFile(files[0]);
  }
};

const handleFileSelect = (e) => {
  const files = e.target.files;
  if (files.length > 0) {
    handleFile(files[0]);
  }
};

const handleFile = async (file) => {
  if (!file.name.toLowerCase().endsWith('.xml')) {
    error.value = 'Por favor seleccione un archivo XML';
    return;
  }
  
  xmlFile.value = file;
  error.value = '';
  loading.value = true;
  
  try {
    const formData = new FormData();
    formData.append('xml', file);
    
    const response = await axios.post('/cuentas-por-pagar/import-payment-xml', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    
    if (response.data.success) {
      paymentInfo.value = response.data.payment_info;
      metodoPago.value = response.data.payment_info.metodo_pago_sistema || 'transferencia';
      matches.value = response.data.matches.map(m => ({ ...m, selected: m.found }));
      otrasCuentas.value = response.data.otras_cuentas || [];
      excedente.value = response.data.excedente || 0;
      totalDocumentos.value = response.data.total_documentos;
      documentosEncontrados.value = response.data.documentos_encontrados;
      step.value = 'preview';
    } else {
      error.value = response.data.message || 'Error al procesar el XML';
    }
  } catch (err) {
    console.error('Error:', err);
    error.value = err.response?.data?.message || 'Error al procesar el archivo XML';
  } finally {
    loading.value = false;
  }
};

const applyPayments = async () => {
  if (!canApply.value) return;
  
  applying.value = true;
  error.value = '';
  
  try {
    const payments = selectedPayments.value.map(m => ({
      cuenta_id: m.cuenta_id,
      monto: m.imp_pagado
    }));
    
    const response = await axios.post('/cuentas-por-pagar/apply-payments-xml', {
      payments,
      metodo_pago: metodoPago.value,
      cuenta_bancaria_id: cuentaBancariaId.value,
      fecha_pago: paymentInfo.value?.fecha_pago,
      notas: notas.value || 'Importado desde XML de Pago'
    });
    
    step.value = 'success';
    emit('imported');
    
    setTimeout(() => {
      close();
      window.location.reload();
    }, 1500);
    
  } catch (err) {
    console.error('Error:', err);
    error.value = err.response?.data?.errors?.error || 'Error al aplicar los pagos';
  } finally {
    applying.value = false;
  }
};

const agregarCuentaExtra = (cuenta) => {
  matches.value.push({
    uuid: 'Excedente - ' + cuenta.numero_compra,
    serie_folio: cuenta.numero_compra,
    imp_saldo_ant: cuenta.monto_pendiente,
    imp_pagado: 0,
    imp_pagado_xml: 0,
    imp_saldo_insoluto: cuenta.monto_pendiente,
    found: true,
    selected: true,
    cuenta_id: cuenta.id,
    cuenta_estado: 'pendiente',
    monto_pendiente: cuenta.monto_pendiente,
    proveedor_nombre: '',
    numero_compra: cuenta.numero_compra,
    manual: true
  });
  
  // Quitar de otrasCuentas
  otrasCuentas.value = otrasCuentas.value.filter(c => c.id !== cuenta.id);
};

const reset = () => {
  step.value = 'source';
  sourceTab.value = 'select';
  xmlFile.value = null;
  paymentInfo.value = null;
  matches.value = [];
  error.value = '';
  selectedCfdiId.value = null;
};

const close = () => {
  reset();
  emit('close');
};

const toggleSelection = (match) => {
  if (match.found) {
    match.selected = !match.selected;
  }
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value || 0);
};

const formatDate = (dateStr) => {
  if (!dateStr) return 'N/A';
  return new Date(dateStr).toLocaleDateString('es-MX');
};
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white dark:bg-slate-900 rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
      <!-- Header -->
      <div class="bg-gradient-to-r from-orange-500 to-orange-600 text-white px-6 py-4 flex justify-between items-center">
        <h2 class="text-xl font-semibold">
          {{ step === 'source' ? 'Importar XML de Pago' : step === 'preview' ? 'Vista Previa de Pagos' : '¡Pagos Aplicados!' }}
        </h2>
        <button @click="close" class="text-white hover:text-gray-200 text-2xl">&times;</button>
      </div>
      
      <!-- Content -->
      <div class="flex-1 overflow-y-auto p-6">
        <!-- Step: Select Source -->
        <div v-if="step === 'source'" class="space-y-4">
          <!-- Tabs -->
          <div class="flex border-b border-gray-200 dark:border-slate-800">
            <button
              @click="sourceTab = 'select'"
              :class="[
                'px-4 py-2 font-medium text-sm border-b-2 transition-colors',
                sourceTab === 'select'
                  ? 'border-orange-500 text-orange-600'
                  : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700'
              ]"
            >
              📋 Seleccionar CFDI Existente
            </button>
            <button
              @click="sourceTab = 'upload'"
              :class="[
                'px-4 py-2 font-medium text-sm border-b-2 transition-colors',
                sourceTab === 'upload'
                  ? 'border-orange-500 text-orange-600'
                  : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700'
              ]"
            >
              📤 Subir Archivo XML
            </button>
          </div>
          
          <!-- Tab: Select CFDI -->
          <div v-if="sourceTab === 'select'" class="space-y-4">
            <p class="text-sm text-gray-600">
              Seleccione un complemento de pago (CFDI tipo P) del administrador de documentos:
            </p>
            
            <!-- Search -->
            <div class="relative">
              <input
                v-model="searchQuery"
                @input="handleSearch"
                type="text"
                placeholder="Buscar por UUID, emisor, RFC..."
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
              >
              <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
              </svg>
            </div>
            
            <!-- CFDI List -->
            <div class="border rounded-lg overflow-hidden max-h-80 overflow-y-auto">
              <div v-if="loadingCfdis" class="p-8 text-center text-gray-500 dark:text-gray-400">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-orange-600 mx-auto mb-2"></div>
                Cargando CFDIs...
              </div>
              <div v-else-if="paymentCfdis.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                No se encontraron CFDIs de pago (tipo P) recibidos
              </div>
              <table v-else class="w-full text-sm">
                <thead class="bg-gray-50 sticky top-0">
                  <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Fecha</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Emisor</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">UUID</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Monto</th>
                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Docs</th>
                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Acción</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <tr v-for="cfdi in paymentCfdis" :key="cfdi.id" class="hover:bg-orange-50 transition-colors">
                    <td class="px-4 py-3 whitespace-nowrap">{{ formatDate(cfdi.fecha_emision) }}</td>
                    <td class="px-4 py-3">
                      <div class="font-medium text-gray-900 dark:text-white truncate max-w-48">{{ cfdi.nombre_emisor }}</div>
                      <div class="text-xs text-gray-500 dark:text-gray-400">{{ cfdi.rfc_emisor }}</div>
                    </td>
                    <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">{{ cfdi.uuid?.slice(0, 8) }}...</td>
                    <td class="px-4 py-3 text-right font-medium text-green-600">{{ formatCurrency(cfdi.total) }}</td>
                    <td class="px-4 py-3 text-center">
                      <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">{{ cfdi.num_documentos || 0 }}</span>
                    </td>
                    <td class="px-4 py-3 text-center">
                      <button
                        @click="selectCfdi(cfdi)"
                        :disabled="loading && selectedCfdiId === cfdi.id"
                        class="px-3 py-1 bg-orange-500 text-white text-xs font-medium rounded hover:bg-orange-600 disabled:opacity-50"
                      >
                        {{ loading && selectedCfdiId === cfdi.id ? 'Procesando...' : 'Seleccionar' }}
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          
          <!-- Tab: Upload File -->
          <div v-if="sourceTab === 'upload'" class="space-y-4">
            <p class="text-gray-600">
              O suba un XML de complemento de pago (CFDI tipo P) desde su computadora:
            </p>
            
            <!-- Drag & Drop Zone -->
            <div
              @dragover="handleDragOver"
              @dragleave="handleDragLeave"
              @drop="handleDrop"
              :class="[
                'border-2 border-dashed rounded-lg p-12 text-center transition-colors cursor-pointer',
                dragOver ? 'border-orange-500 bg-orange-50' : 'border-gray-300 hover:border-gray-400'
              ]"
              @click="$refs.fileInput.click()"
            >
              <div v-if="loading" class="flex flex-col items-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-600 mb-4"></div>
                <p class="text-gray-600">Procesando XML...</p>
              </div>
              <div v-else class="flex flex-col items-center">
                <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <p class="text-lg text-gray-600 mb-2">Arrastra y suelta tu archivo XML aquí</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">o haz clic para seleccionar</p>
              </div>
              <input type="file" ref="fileInput" class="hidden" accept=".xml" @change="handleFileSelect">
            </div>
          </div>
          
          <!-- Error -->
          <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
            {{ error }}
          </div>
        </div>
        
        <!-- Step: Preview -->
        <div v-if="step === 'preview'" class="space-y-6">
          <!-- Payment Info -->
          <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
            <h3 class="font-semibold text-orange-800 mb-2">Información del Pago</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
              <div>
                <span class="text-gray-600">Fecha:</span>
                <span class="ml-2 font-medium">{{ formatDate(paymentInfo?.fecha_pago) }}</span>
              </div>
              <div>
                <span class="text-gray-600">Monto Total:</span>
                <span class="ml-2 font-medium text-green-600">{{ formatCurrency(paymentInfo?.monto_total) }}</span>
              </div>
              <div>
                <span class="text-gray-600">Forma de Pago:</span>
                <span class="ml-2 font-medium">{{ paymentInfo?.forma_pago || 'N/A' }}</span>
              </div>
              <div>
                <span class="text-gray-600">UUID:</span>
                <span class="ml-2 font-mono text-xs">{{ paymentInfo?.uuid?.slice(0, 8) }}...</span>
              </div>
            </div>
          </div>

          <!-- Excedente Warning -->
          <div v-if="excedente >= 1.00" class="bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-start">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-blue-800">Excedente de Pago Detectado</h3>
              <div class="mt-2 text-sm text-blue-700">
                <p>El XML tiene un excedente de <strong>{{ formatCurrency(excedente) }}</strong> que no corresponde a las facturas vinculadas. Puede aplicar este monto manualmente a otras facturas pendientes del proveedor.</p>
              </div>
              <div v-if="otrasCuentas.length > 0" class="mt-3">
                <button @click="mostrarOtrasCuentas = !mostrarOtrasCuentas" class="text-xs font-semibold text-blue-600 hover:text-blue-500 uppercase tracking-wider">
                  {{ mostrarOtrasCuentas ? 'Ocultar facturas pendientes' : 'Ver otras facturas del proveedor' }}
                </button>
              </div>
            </div>
          </div>

          <!-- Otras Cuentas List -->
          <div v-if="mostrarOtrasCuentas && otrasCuentas.length > 0" class="bg-gray-50 border border-gray-200 dark:border-slate-800 rounded-lg p-4 mt-2">
             <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-3 text-center">Otras Facturas Pendientes de este Proveedor</h4>
             <div class="space-y-2 max-h-48 overflow-y-auto pr-2">
                <div v-for="cuenta in otrasCuentas" :key="cuenta.id" class="flex items-center justify-between text-sm bg-white dark:bg-slate-900 p-3 rounded border border-gray-100 dark:border-slate-800 shadow-sm transition-all hover:border-indigo-200">
                   <div class="flex flex-col">
                      <span class="font-bold text-gray-800">{{ cuenta.numero_compra }}</span>
                      <span class="text-xs text-gray-500 dark:text-gray-400">Vence: {{ cuenta.fecha_vencimiento }}</span>
                   </div>
                   <div class="flex items-center space-x-4">
                      <div class="text-right">
                         <div class="text-xs text-gray-400 uppercase font-semibold">Pendiente</div>
                         <div class="font-bold text-gray-700">{{ formatCurrency(cuenta.monto_pendiente) }}</div>
                      </div>
                      <button @click="agregarCuentaExtra(cuenta)" class="bg-indigo-50 text-indigo-600 px-3 py-2 rounded-md hover:bg-indigo-100 font-bold text-xs transition-colors border border-indigo-100 uppercase">
                        + Añadir
                      </button>
                   </div>
                </div>
             </div>
          </div>
          
          <!-- Match Summary -->
          <div class="flex items-center justify-between">
            <span class="text-gray-600">
              Documentos encontrados: <strong>{{ documentosEncontrados }}</strong> de {{ totalDocumentos }}
            </span>
            <span v-if="documentosEncontrados < totalDocumentos" class="text-yellow-600 text-sm">
              ⚠️ Algunos documentos no fueron encontrados en el sistema
            </span>
          </div>
          
          <!-- Matches Table -->
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-100">
                <tr>
                  <th class="px-4 py-2 text-left">Aplicar</th>
                  <th class="px-4 py-2 text-left">UUID Factura</th>
                  <th class="px-4 py-2 text-left">Compra</th>
                  <th class="px-4 py-2 text-left">Proveedor</th>
                  <th class="px-4 py-2 text-right">Saldo Ant.</th>
                  <th class="px-4 py-2 text-right">Pagado</th>
                  <th class="px-4 py-2 text-right">Saldo Final</th>
                  <th class="px-4 py-2 text-center">Estado</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(match, idx) in matches" :key="idx" :class="match.found ? 'hover:bg-gray-50' : 'bg-gray-100 opacity-60'">
                  <td class="px-4 py-2">
                    <input
                      type="checkbox"
                      :checked="match.selected"
                      :disabled="!match.found"
                      @change="toggleSelection(match)"
                      class="rounded border-gray-300 text-orange-600"
                    >
                  </td>
                  <td class="px-4 py-2 font-mono text-xs">{{ match.uuid.slice(0, 8) }}...</td>
                  <td class="px-4 py-2">{{ match.numero_compra }}</td>
                  <td class="px-4 py-2">{{ match.proveedor_nombre }}</td>
                  <td class="px-4 py-2 text-right">{{ formatCurrency(match.imp_saldo_ant) }}</td>
                  <td class="px-4 py-2 text-right text-green-600 font-medium">
                    <div class="flex items-center justify-end">
                      <span class="mr-1">$</span>
                      <input 
                        type="number" 
                        step="0.01" 
                        v-model.number="match.imp_pagado" 
                        class="w-24 text-right border-gray-300 rounded-md shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-xs"
                        :disabled="!match.selected"
                        @input="match.imp_saldo_insoluto = match.imp_saldo_ant - match.imp_pagado"
                      >
                    </div>
                  </td>
                  <td class="px-4 py-2 text-right">{{ formatCurrency(match.imp_saldo_insoluto) }}</td>
                  <td class="px-4 py-2 text-center">
                    <span v-if="match.found" class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">Encontrada</span>
                    <span v-else class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs">No encontrada</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <!-- Payment Options -->
          <div class="bg-gray-50 rounded-lg p-4 space-y-4">
            <h4 class="font-medium text-gray-700">Opciones de Registro</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="block text-sm text-gray-600 mb-1">Método de Pago</label>
                <select v-model="metodoPago" class="w-full border-gray-300 rounded-md shadow-sm">
                  <option value="transferencia">Transferencia</option>
                  <option value="efectivo">Efectivo</option>
                  <option value="cheque">Cheque</option>
                  <option value="tarjeta">Tarjeta</option>
                  <option value="otros">Otros</option>
                </select>
              </div>
              <div>
                <label class="block text-sm text-gray-600 mb-1">Cuenta Bancaria</label>
                <select v-model="cuentaBancariaId" class="w-full border-gray-300 rounded-md shadow-sm">
                  <option :value="null">-- Sin cuenta --</option>
                  <option v-for="cb in cuentasBancarias" :key="cb.id" :value="cb.id">
                    {{ cb.banco }} - {{ cb.nombre }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm text-gray-600 mb-1">Notas</label>
                <input v-model="notas" type="text" placeholder="Notas adicionales..." class="w-full border-gray-300 rounded-md shadow-sm">
              </div>
            </div>
          </div>
          
          <!-- Error -->
          <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
            {{ error }}
          </div>
        </div>
        
        <!-- Step: Success -->
        <div v-if="step === 'success'" class="text-center py-12">
          <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
          </div>
          <h3 class="text-xl font-semibold text-green-600 mb-2">¡Pagos Aplicados!</h3>
          <p class="text-gray-600">Los pagos han sido registrados correctamente. La página se recargará...</p>
        </div>
      </div>
      
      <!-- Footer -->
      <div class="bg-gray-50 px-6 py-4 flex justify-between border-t">
        <button
          v-if="step === 'preview'"
          @click="reset"
          class="px-4 py-2 text-gray-600 hover:text-gray-800"
        >
          ← Volver
        </button>
        <div v-else></div>
        
        <div class="flex space-x-3">
          <button
            @click="close"
            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-100"
          >
            Cancelar
          </button>
          <button
            v-if="step === 'preview'"
            @click="applyPayments"
            :disabled="!canApply"
            :class="[
              'px-6 py-2 rounded-md font-medium',
              canApply
                ? 'bg-orange-600 text-white hover:bg-orange-700'
                : 'bg-gray-300 text-gray-500 dark:text-gray-400 cursor-not-allowed'
            ]"
          >
            <span v-if="applying" class="flex items-center">
              <svg class="animate-spin h-4 w-4 mr-2" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              Aplicando...
            </span>
            <span v-else>Aplicar {{ selectedPayments.length }} Pago(s)</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
