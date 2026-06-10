<script setup>
import { useFormatters } from '@/Composables/useFormatters';
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

const { formatCurrency } = useFormatters();

const formatDate = (dateStr) => {
  if (!dateStr) return 'N/A';
  return new Date(dateStr).toLocaleDateString('es-MX');
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" @click="close"></div>
      
      <div class="relative bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-hidden flex flex-col border border-slate-200 dark:border-slate-800 transition-all duration-500 scale-100">
        <!-- Header -->
        <div class="bg-gradient-to-r from-slate-900 to-slate-800 dark:from-slate-950 dark:to-slate-900 text-white px-10 py-8 flex justify-between items-center border-b border-white/5">
          <div>
            <h2 class="text-2xl font-black uppercase tracking-wide">
              {{ step === 'source' ? 'Importar XML' : step === 'preview' ? 'Validación de Pagos' : 'Proceso Exitoso' }}
            </h2>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">{{ step === 'source' ? 'Selección de origen de datos' : 'Confirmación de vinculación' }}</p>
          </div>
          <button @click="close" class="w-10 h-10 flex items-center justify-center rounded-2xl bg-white/5 hover:bg-white/10 transition-colors text-white/50 hover:text-white">&times;</button>
        </div>
        
        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-10">
          <!-- Step: Select Source -->
          <div v-if="step === 'source'" class="space-y-8">
            <!-- Tabs Premium -->
            <div class="flex p-1.5 bg-slate-100 dark:bg-slate-800/50 rounded-[1.5rem] w-fit">
              <button
                @click="sourceTab = 'select'"
                :class="[
                  'px-6 py-2.5 font-black text-[10px] uppercase tracking-wide rounded-[1.2rem] transition-all duration-300',
                  sourceTab === 'select'
                    ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-md'
                    : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
                ]"
              >
                📋 CFDI en Sistema
              </button>
              <button
                @click="sourceTab = 'upload'"
                :class="[
                  'px-6 py-2.5 font-black text-[10px] uppercase tracking-wide rounded-[1.2rem] transition-all duration-300',
                  sourceTab === 'upload'
                    ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-md'
                    : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
                ]"
              >
                📤 Cargar Archivo
              </button>
            </div>
            
            <!-- Tab: Select CFDI -->
            <div v-if="sourceTab === 'select'" class="space-y-6">
              <!-- Search -->
              <div class="relative group">
                <input
                  v-model="searchQuery"
                  @input="handleSearch"
                  type="text"
                  placeholder="Buscar complemento de pago por UUID o RFC..."
                  class="w-full pl-12 pr-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-800 rounded-3xl text-sm font-bold text-slate-700 dark:text-white focus:ring-[var(--color-primary)]/20 focus:border-[var(--color-primary)] transition-all"
                >
                <svg class="absolute left-4 top-4 h-5 w-5 text-slate-400 group-focus-within:text-brand-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
              </div>
              
              <!-- CFDI List Premium -->
              <div class="border border-slate-200 dark:border-slate-800 rounded-[2rem] overflow-hidden bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm shadow-xl">
                <div v-if="loadingCfdis" class="p-16 text-center">
                  <div class="inline-flex relative w-12 h-12">
                    <div class="absolute inset-0 border-4 border-brand-500/20 rounded-full"></div>
                    <div class="absolute inset-0 border-4 border-t-brand-500 rounded-full animate-spin"></div>
                  </div>
                  <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mt-4">Sincronizando con repositorio...</p>
                </div>
                <div v-else-if="paymentCfdis.length === 0" class="p-16 text-center text-slate-500">
                  <div class="w-16 h-16 mx-auto mb-4 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center opacity-50">
                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                  </div>
                  <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Sin documentos tipo "P" encontrados</p>
                </div>
                <div v-else class="overflow-x-auto max-h-96 custom-scrollbar">
                  <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-950/20 border-b border-slate-100 dark:border-slate-800 sticky top-0 z-10 backdrop-blur-md">
                      <tr>
                        <th class="px-6 py-4 text-left text-[9px] font-black text-slate-400 uppercase tracking-wide">Emisor</th>
                        <th class="px-6 py-4 text-right text-[9px] font-black text-slate-400 uppercase tracking-wide">Monto</th>
                        <th class="px-6 py-4 text-center text-[9px] font-black text-slate-400 uppercase tracking-wide">Documentos</th>
                        <th class="px-6 py-4 text-right text-[9px] font-black text-slate-400 uppercase tracking-wide">Acción</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                      <tr v-for="cfdi in paymentCfdis" :key="cfdi.id" class="group hover:bg-white dark:hover:bg-slate-950/40 transition-all duration-200">
                        <td class="px-6 py-4">
                          <div class="font-black text-xs text-slate-800 dark:text-slate-200 uppercase tracking-wider">{{ cfdi.nombre_emisor }}</div>
                          <div class="text-[9px] font-bold text-slate-400 tracking-[0.1em] mt-0.5">{{ cfdi.rfc_emisor }} • <span class="font-mono">{{ cfdi.uuid?.slice(0, 13) }}...</span></div>
                        </td>
                        <td class="px-6 py-4 text-right">
                          <div class="text-xs font-black text-emerald-600 dark:text-emerald-400 tracking-tight">{{ formatCurrency(cfdi.total) }}</div>
                          <div class="text-[9px] font-bold text-slate-400 uppercase tracking-wide mt-0.5">{{ formatDate(cfdi.fecha_emision) }}</div>
                        </td>
                        <td class="px-6 py-4 text-center">
                          <span class="px-2 py-0.5 bg-blue-500/10 text-blue-600 dark:text-blue-400 text-[10px] font-black rounded-xl border border-blue-500/10">{{ cfdi.num_documentos || 0 }} DOCS</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                          <button
                            @click="selectCfdi(cfdi)"
                            :disabled="loading && selectedCfdiId === cfdi.id"
                            class="px-4 py-2 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-[9px] font-black uppercase tracking-wide rounded-xl hover:scale-105 active:scale-95 transition-all shadow-md disabled:opacity-50"
                          >
                            {{ loading && selectedCfdiId === cfdi.id ? 'Cargando...' : 'Vincular' }}
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            
            <!-- Tab: Upload File -->
            <div v-if="sourceTab === 'upload'" class="space-y-6">
              <div
                @dragover="handleDragOver"
                @dragleave="handleDragLeave"
                @drop="handleDrop"
                :class="[
                  'relative border-2 border-dashed rounded-[2.5rem] p-20 text-center transition-all duration-500 cursor-pointer group',
                  dragOver ? 'border-brand-500 bg-brand-500/5 ring-8 ring-brand-500/5 scale-[1.02]' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700'
                ]"
                @click="$refs.fileInput.click()"
              >
                <div v-if="loading" class="flex flex-col items-center">
                  <div class="animate-spin rounded-full h-16 w-16 border-4 border-slate-200 border-t-brand-500 mb-6"></div>
                  <p class="text-sm font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide">Procesando Estructura XML...</p>
                </div>
                <div v-else class="flex flex-col items-center">
                  <div class="w-20 h-20 bg-slate-50 dark:bg-slate-800 rounded-3xl flex items-center justify-center mb-6 group-hover:scale-110 group-hover:bg-brand-500/10 transition-all border border-slate-100 dark:border-slate-700 shadow-sm">
                    <svg class="w-10 h-10 text-slate-400 group-hover:text-brand-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                  </div>
                  <p class="text-lg font-black text-slate-800 dark:text-white uppercase tracking-wide">Arrastre su archivo XML</p>
                  <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-2">o haga clic para explorar sus carpetas</p>
                  
                  <div class="mt-8 px-4 py-1.5 bg-slate-100 dark:bg-slate-800 rounded-xl text-[9px] font-black text-slate-500 dark:text-slate-500 uppercase tracking-wide">Complementos de Pago .XML</div>
                </div>
                <input type="file" ref="fileInput" class="hidden" accept=".xml" @change="handleFileSelect">
              </div>
            </div>
            
            <!-- Error Premium -->
            <div v-if="error" class="bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 px-6 py-4 rounded-2xl flex items-center gap-4 animate-head-shake">
              <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
              <div class="text-xs font-bold">{{ error }}</div>
            </div>
          </div>
          
          <!-- Step: Preview Premium -->
          <div v-if="step === 'preview'" class="space-y-8">
            <!-- Summary Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
               <div class="bg-slate-50 dark:bg-slate-800/50 p-6 rounded-3xl border border-slate-200 dark:border-slate-800">
                  <p class="text-[9px] font-black text-slate-400 uppercase tracking-wide mb-1">Monto del Pago</p>
                  <p class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter">{{ formatCurrency(paymentInfo?.monto_total) }}</p>
                  <div class="mt-2 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wide">{{ paymentInfo?.forma_pago || 'TRANSFERENCIA' }}</span>
                  </div>
               </div>
               <div class="bg-slate-50 dark:bg-slate-800/50 p-6 rounded-3xl border border-slate-200 dark:border-slate-800">
                  <p class="text-[9px] font-black text-slate-400 uppercase tracking-wide mb-1">Fecha de Operación</p>
                  <p class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter">{{ formatDate(paymentInfo?.fecha_pago) }}</p>
                  <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wide mt-2 truncate">{{ paymentInfo?.uuid?.slice(0, 20) }}...</p>
               </div>
               <div class="bg-slate-50 dark:bg-slate-800/50 p-6 rounded-3xl border border-slate-200 dark:border-slate-800">
                  <p class="text-[9px] font-black text-slate-400 uppercase tracking-wide mb-1">Emparejamiento</p>
                  <div class="flex items-end justify-between">
                    <p class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter">{{ documentosEncontrados }}<span class="text-lg text-slate-400">/{{ totalDocumentos }}</span></p>
                    <div class="pb-1">
                       <span :class="documentosEncontrados === totalDocumentos ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' : 'bg-brand-500/10 text-brand-500 border-brand-500/20'" class="px-2 py-0.5 text-[9px] font-black rounded-xl border uppercase tracking-wide">
                         {{ documentosEncontrados === totalDocumentos ? 'Completo' : 'Parcial' }}
                       </span>
                    </div>
                  </div>
               </div>
            </div>

            <!-- Matches Table Premium -->
            <div class="border border-slate-200 dark:border-slate-800 rounded-[2.5rem] overflow-hidden bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm shadow-xl">
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                  <thead>
                    <tr class="bg-slate-50 dark:bg-slate-950/20 border-b border-slate-100 dark:border-slate-800">
                      <th class="px-6 py-4 text-center text-[9px] font-black text-slate-400 uppercase tracking-wide w-12">SEL</th>
                      <th class="px-6 py-4 text-left text-[9px] font-black text-slate-400 uppercase tracking-wide">Compra / UUID</th>
                      <th class="px-6 py-4 text-left text-[9px] font-black text-slate-400 uppercase tracking-wide">Proveedor</th>
                      <th class="px-6 py-4 text-right text-[9px] font-black text-slate-400 uppercase tracking-wide">Monto Aplicar</th>
                      <th class="px-6 py-4 text-center text-[9px] font-black text-slate-400 uppercase tracking-wide">Estatus</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                    <tr v-for="(match, idx) in matches" :key="idx" :class="[match.found ? 'group hover:bg-white dark:hover:bg-slate-950/40' : 'bg-slate-50/50 dark:bg-slate-950/40 opacity-60']" class="transition-all duration-200">
                      <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center">
                           <input
                            type="checkbox"
                            :checked="match.selected"
                            :disabled="!match.found"
                            @change="toggleSelection(match)"
                            class="w-5 h-5 rounded-xl border-slate-300 dark:border-slate-700 text-brand-500 focus:ring-brand-500/20 transition-all checked:scale-110"
                          >
                        </div>
                      </td>
                      <td class="px-6 py-4">
                        <div class="text-xs font-black text-slate-800 dark:text-slate-200 uppercase tracking-wider">{{ match.numero_compra || 'XML DOC' }}</div>
                        <div class="text-[9px] font-bold text-slate-400 tracking-wide mt-0.5 italic font-mono">{{ match.uuid.slice(0, 10) }}...</div>
                      </td>
                      <td class="px-6 py-4">
                        <div class="text-[10px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider truncate max-w-[200px]">{{ match.proveedor_nombre || 'IDENTIFICANDO...' }}</div>
                      </td>
                      <td class="px-6 py-4 text-right">
                        <div class="inline-flex items-center gap-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-1.5 focus-within:ring-2 focus-within:ring-brand-500/20 transition-all">
                          <span class="text-[10px] font-black text-slate-400">$</span>
                          <input 
                            type="number" 
                            step="0.01" 
                            v-model.number="match.imp_pagado" 
                            class="w-24 text-right bg-transparent border-none p-0 text-xs font-black text-slate-900 dark:text-white focus:ring-0"
                            :disabled="!match.selected"
                            @input="match.imp_saldo_insoluto = match.imp_saldo_ant - match.imp_pagado"
                          >
                        </div>
                      </td>
                      <td class="px-6 py-4 text-center">
                        <span v-if="match.found" class="px-2 py-0.5 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-[8px] font-black rounded-xl border border-emerald-500/20 uppercase tracking-wide">Sincronizado</span>
                        <span v-else class="px-2 py-0.5 bg-rose-500/10 text-rose-600 dark:text-rose-400 text-[8px] font-black rounded-xl border border-rose-500/20 uppercase tracking-wide">No hallado</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            
            <!-- Global Options Premium -->
            <div class="bg-slate-900 dark:bg-slate-950 p-8 rounded-[2.5rem] shadow-2xl relative overflow-hidden">
               <div class="absolute top-0 right-0 p-8 opacity-5 scale-150 rotate-12">
                  <svg class="w-32 h-32 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/><path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/></svg>
               </div>
               <div class="relative z-10 grid grid-cols-1 md:grid-cols-2 gap-8 items-end">
                  <div class="space-y-4">
                     <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] pl-1">Configuración del Registro</h4>
                     <div class="grid grid-cols-1 gap-4">
                        <div>
                          <label class="block text-[8px] font-black text-slate-500 uppercase tracking-wide mb-1.5 ml-1">Origen de Fondos (Opcional)</label>
                          <select v-model="cuentaBancariaId" class="w-full bg-slate-800/50 border-slate-700 rounded-2xl text-[10px] font-black text-white hover:bg-slate-800 transition-all focus:ring-brand-500/20 focus:border-brand-500 uppercase tracking-wide">
                            <option :value="null">-- SIN CUENTA BANCARIA --</option>
                            <option v-for="cb in cuentasBancarias" :key="cb.id" :value="cb.id">
                              {{ cb.banco }} • {{ cb.nombre }}
                            </option>
                          </select>
                        </div>
                        <div>
                          <label class="block text-[8px] font-black text-slate-500 uppercase tracking-wide mb-1.5 ml-1">Notas de la Transacción</label>
                          <input v-model="notas" type="text" placeholder="EJ. PAGO DE PROVEEDOR SEMANAL..." class="w-full bg-slate-800/50 border-slate-700 rounded-2xl text-[10px] font-black text-white hover:bg-slate-800 transition-all focus:ring-brand-500/20 focus:border-brand-500 uppercase tracking-wide">
                        </div>
                     </div>
                  </div>
                  <div class="flex flex-col gap-3">
                     <button
                        @click="applyPayments"
                        :disabled="!canApply"
                        class="w-full py-5 bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-600 hover:to-brand-700 disabled:from-slate-700 disabled:to-slate-800 text-white font-black text-xs uppercase tracking-[0.2em] rounded-3xl shadow-xl shadow-brand-500/20 transition-all duration-300 active:scale-95 group flex items-center justify-center gap-3 overflow-hidden relative"
                      >
                        <span v-if="applying" class="flex items-center">
                          <svg class="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                          </svg>
                        </span>
                        <span v-else class="flex items-center gap-3">
                          EJECUTAR {{ selectedPayments.length }} PAGOS
                          <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                        </span>
                      </button>
                      <p class="text-center text-[8px] font-bold text-slate-500 uppercase tracking-wide italic opacity-50">Pulse para confirmar la vinculación contable</p>
                  </div>
               </div>
            </div>
          </div>
          
          <!-- Step: Success Premium -->
          <div v-if="step === 'success'" class="py-20 text-center animate-bounce-in">
            <div class="relative inline-flex mb-8">
               <div class="absolute inset-0 bg-emerald-500 blur-2xl opacity-20 animate-pulse"></div>
               <div class="relative w-24 h-24 bg-emerald-500 text-white rounded-[2rem] flex items-center justify-center shadow-2xl shadow-emerald-500/40">
                  <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"/>
                  </svg>
               </div>
            </div>
            <h3 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-wide">¡Operación Completada!</h3>
            <p class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mt-3">Los saldos han sido actualizados en tiempo real.</p>
            
            <div class="mt-12 inline-flex items-center gap-2 px-6 py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-[10px] font-black uppercase tracking-[0.2em] rounded-2xl shadow-lg">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                Sincronizando vistas...
            </div>
          </div>
        </div>
        
        <!-- Footer Premium -->
        <div class="bg-slate-50 dark:bg-slate-950/40 px-10 py-6 flex justify-between items-center border-t border-slate-100 dark:border-slate-800">
          <button
            v-if="step === 'preview'"
            @click="reset"
            class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide hover:text-slate-800 dark:hover:text-white transition-all flex items-center gap-2 group"
          >
            <svg class="w-4 h-4 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Reconfigurar Origen
          </button>
          <div v-else></div>
          
          <div class="flex items-center gap-4">
            <p v-if="step === 'source'" class="text-[9px] font-bold text-slate-400 uppercase tracking-wide italic mr-4 hidden sm:block">Asegúrese que sea un XML de pagos (Tipo P)</p>
            <button
              @click="close"
              class="px-8 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide hover:border-rose-500/50 hover:text-rose-600 transition-all active:scale-95"
            >
              Cerrar
            </button>
          </div>
        </div>
      </div>
    </div>
</template>
