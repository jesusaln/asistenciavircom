<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
  show: { type: Boolean, default: false },
  cuentasBancarias: { type: Array, default: () => [] }
});

const emit = defineEmits(['close', 'imported']);

// State
const loading = ref(false);
const applying = ref(false);
const step = ref('upload'); // 'upload' | 'preview' | 'success'
const error = ref('');

// File upload
const xmlFile = ref(null);
const dragOver = ref(false);

// Parsed payment data
const paymentInfo = ref(null);
const matches = ref([]);
const totalDocumentos = ref(0);
const documentosEncontrados = ref(0);

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

// Methods
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
    
    const response = await axios.post('/cuentas-por-cobrar/import-payment-xml', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    
    if (response.data.success) {
      paymentInfo.value = response.data.payment_info;
      matches.value = response.data.matches.map(m => ({ ...m, selected: m.found }));
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
    
    const response = await axios.post('/cuentas-por-cobrar/apply-payments-xml', {
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

const reset = () => {
  step.value = 'upload';
  xmlFile.value = null;
  paymentInfo.value = null;
  matches.value = [];
  error.value = '';
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
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
      <!-- Header -->
      <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4 flex justify-between items-center">
        <h2 class="text-xl font-semibold">
          {{ step === 'upload' ? 'Importar XML de Pago' : step === 'preview' ? 'Vista Previa de Pagos' : '¡Pagos Aplicados!' }}
        </h2>
        <button @click="close" class="text-white hover:text-gray-200 text-2xl">&times;</button>
      </div>
      
      <!-- Content -->
      <div class="flex-1 overflow-y-auto p-6">
        <!-- Step: Upload -->
        <div v-if="step === 'upload'" class="space-y-4">
          <p class="text-gray-600">
            Suba un XML de complemento de pago (CFDI tipo P) para aplicar pagos automáticamente a las facturas relacionadas.
          </p>
          
          <!-- Drag & Drop Zone -->
          <div
            @dragover="handleDragOver"
            @dragleave="handleDragLeave"
            @drop="handleDrop"
            :class="[
              'border-2 border-dashed rounded-lg p-12 text-center transition-colors cursor-pointer',
              dragOver ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-gray-400'
            ]"
            @click="$refs.fileInput.click()"
          >
            <div v-if="loading" class="flex flex-col items-center">
              <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
              <p class="text-gray-600">Procesando XML...</p>
            </div>
            <div v-else class="flex flex-col items-center">
              <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
              </svg>
              <p class="text-lg text-gray-600 mb-2">Arrastra y suelta tu archivo XML aquí</p>
              <p class="text-sm text-gray-500">o haz clic para seleccionar</p>
            </div>
            <input type="file" ref="fileInput" class="hidden" accept=".xml" @change="handleFileSelect">
          </div>
          
          <!-- Error -->
          <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
            {{ error }}
          </div>
        </div>
        
        <!-- Step: Preview -->
        <div v-if="step === 'preview'" class="space-y-6">
          <!-- Payment Info -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="font-semibold text-blue-800 mb-2">Información del Pago</h3>
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
                  <th class="px-4 py-2 text-left">Venta</th>
                  <th class="px-4 py-2 text-left">Cliente</th>
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
                      class="rounded border-gray-300 text-blue-600"
                    >
                  </td>
                  <td class="px-4 py-2 font-mono text-xs">{{ match.uuid.slice(0, 8) }}...</td>
                  <td class="px-4 py-2">{{ match.numero_venta }}</td>
                  <td class="px-4 py-2">{{ match.cliente_nombre }}</td>
                  <td class="px-4 py-2 text-right">{{ formatCurrency(match.imp_saldo_ant) }}</td>
                  <td class="px-4 py-2 text-right text-green-600 font-medium">{{ formatCurrency(match.imp_pagado) }}</td>
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
                ? 'bg-green-600 text-white hover:bg-green-700'
                : 'bg-gray-300 text-gray-500 cursor-not-allowed'
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
