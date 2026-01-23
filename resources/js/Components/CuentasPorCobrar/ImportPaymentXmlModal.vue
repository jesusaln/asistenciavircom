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
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
    <div class="bg-white dark:bg-slate-900 dark:bg-slate-950 rounded-3xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col border border-gray-100 dark:border-slate-800 dark:border-slate-800">
      <!-- Header -->
      <div class="bg-gradient-to-r from-blue-600 to-indigo-700 dark:from-blue-700 dark:to-indigo-900 text-white px-8 py-6 flex justify-between items-center">
        <div>
          <h2 class="text-lg font-black uppercase tracking-widest">
            {{ step === 'upload' ? 'Importar XML de Pago' : step === 'preview' ? 'Vista Previa de Pagos' : '¡Pagos Aplicados!' }}
          </h2>
          <p class="text-[10px] text-blue-100 font-bold uppercase tracking-[0.2em] mt-1">{{ step === 'upload' ? 'Seleccionar archivo .xml' : 'Validación de documentos' }}</p>
        </div>
        <button @click="close" class="p-2 hover:bg-white dark:bg-slate-900/10 rounded-xl transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
      </div>
      
      <!-- Content -->
      <div class="flex-1 overflow-y-auto p-8 dark:bg-slate-900/50">
        <!-- Step: Upload -->
        <div v-if="step === 'upload'" class="space-y-6">
          <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-slate-400 font-medium">
            Suba un XML de complemento de pago (CFDI tipo P) para aplicar pagos automáticamente a las facturas relacionadas en el sistema.
          </p>
          
          <!-- Drag & Drop Zone -->
          <div
            @dragover="handleDragOver"
            @dragleave="handleDragLeave"
            @drop="handleDrop"
            :class="[
              'border-4 border-dashed rounded-3xl p-16 text-center transition-all cursor-pointer group',
              dragOver 
                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' 
                : 'border-gray-100 dark:border-slate-800 dark:border-slate-800 hover:border-blue-400 dark:hover:border-blue-600 hover:bg-gray-50/50 dark:hover:bg-slate-900'
            ]"
            @click="$refs.fileInput.click()"
          >
            <div v-if="loading" class="flex flex-col items-center">
              <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-600 border-t-transparent mb-4"></div>
              <p class="text-sm font-black text-blue-600 uppercase tracking-widest">Procesando XML...</p>
            </div>
            <div v-else class="flex flex-col items-center">
              <div class="w-20 h-20 bg-blue-50 dark:bg-slate-900 rounded-2xl flex items-center justify-center mb-6 border border-blue-100 dark:border-slate-800 transition-transform group-hover:scale-110">
                <svg class="w-10 h-10 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
              </div>
              <p class="text-lg font-black text-gray-900 dark:text-white dark:text-white mb-2">Arrastra tu archivo XML aquí</p>
              <p class="text-[10px] text-gray-400 dark:text-slate-500 font-bold uppercase tracking-widest">o haz clic para seleccionar desde tu equipo</p>
            </div>
            <input type="file" ref="fileInput" class="hidden" accept=".xml" @change="handleFileSelect">
          </div>
          
          <!-- Error -->
          <div v-if="error" class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800/50 text-red-700 dark:text-red-400 rounded-2xl flex items-start gap-3">
             <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
             <p class="text-xs font-bold">{{ error }}</p>
          </div>
        </div>
        
        <!-- Step: Preview -->
        <div v-if="step === 'preview'" class="space-y-8">
          <!-- Payment Info Card -->
          <div class="bg-white dark:bg-slate-900 dark:bg-slate-900 rounded-3xl border border-gray-100 dark:border-slate-800 dark:border-slate-800 p-6 shadow-sm overflow-hidden relative">
            <div class="absolute top-0 right-0 p-8 opacity-5">
              <svg class="w-24 h-24 text-blue-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
            </div>
            <h3 class="text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-4">Información del REP detectado</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
              <div>
                <p class="text-[9px] text-gray-400 dark:text-slate-500 font-bold uppercase tracking-wider mb-1">Fecha de Pago</p>
                <p class="text-sm font-black text-gray-900 dark:text-white dark:text-white">{{ formatDate(paymentInfo?.fecha_pago) }}</p>
              </div>
              <div>
                <p class="text-[9px] text-gray-400 dark:text-slate-500 font-bold uppercase tracking-wider mb-1">Monto Total</p>
                <p class="text-lg font-black text-emerald-600 dark:text-emerald-400">{{ formatCurrency(paymentInfo?.monto_total) }}</p>
              </div>
              <div>
                <p class="text-[9px] text-gray-400 dark:text-slate-500 font-bold uppercase tracking-wider mb-1">Forma Pago</p>
                <p class="text-sm font-black text-gray-900 dark:text-white dark:text-white">{{ paymentInfo?.forma_pago || 'N/A' }}</p>
              </div>
              <div>
                <p class="text-[9px] text-gray-400 dark:text-slate-500 font-bold uppercase tracking-wider mb-1">UUID REP</p>
                <p class="text-[10px] font-mono font-bold text-blue-600 dark:text-blue-400">{{ paymentInfo?.uuid?.slice(0, 13) }}...</p>
              </div>
            </div>
          </div>
          
          <!-- Match Summary Bar -->
          <div class="flex items-center justify-between px-2">
            <div class="flex items-center gap-3">
              <span class="text-xs font-black text-gray-900 dark:text-white dark:text-white uppercase tracking-widest">Documentos:</span>
              <span class="px-2.5 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-lg text-xs font-black">{{ documentosEncontrados }} / {{ totalDocumentos }} Coincidencias</span>
            </div>
            <span v-if="documentosEncontrados < totalDocumentos" class="text-[10px] text-amber-600 dark:text-amber-400 font-black uppercase tracking-widest flex items-center gap-1">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
              Inconsistencias detectadas
            </span>
          </div>
          
          <!-- Matches Table -->
          <div class="bg-white dark:bg-slate-900 dark:bg-slate-900 rounded-3xl border border-gray-100 dark:border-slate-800 dark:border-slate-800 overflow-hidden shadow-sm">
            <table class="w-full text-left">
              <thead>
                <tr class="bg-gray-50 dark:bg-slate-800/50 border-b border-gray-100 dark:border-slate-800 dark:border-slate-800">
                  <th class="px-5 py-4 text-[9px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest">Aplicar</th>
                  <th class="px-5 py-4 text-[9px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest">Factura / Venta</th>
                  <th class="px-5 py-4 text-[9px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest">Cliente</th>
                  <th class="px-5 py-4 text-[9px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest text-right">Monto XML</th>
                  <th class="px-5 py-4 text-[9px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest text-center">Estado</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-50 dark:divide-slate-800">
                <tr v-for="(match, idx) in matches" :key="idx" 
                    :class="[match.found ? 'hover:bg-gray-50 dark:hover:bg-slate-800/30 transition-colors' : 'bg-gray-50/50 dark:bg-slate-900/50 grayscale opacity-60']"
                    @click="match.found && toggleSelection(match)">
                  <td class="px-5 py-4">
                    <input
                      type="checkbox"
                      :checked="match.selected"
                      :disabled="!match.found"
                      @click.stop
                      @change="toggleSelection(match)"
                      class="w-5 h-5 rounded-lg border-2 border-gray-200 dark:border-slate-800 dark:border-slate-700 text-blue-600 focus:ring-0 transition-all cursor-pointer"
                    >
                  </td>
                  <td class="px-5 py-4">
                    <div class="flex flex-col gap-0.5">
                      <span class="text-[10px] font-black text-gray-900 dark:text-white dark:text-white uppercase">{{ match.numero_venta }}</span>
                      <span class="text-[9px] font-mono text-gray-400 dark:text-slate-500 uppercase">{{ match.uuid.slice(0, 13) }}...</span>
                    </div>
                  </td>
                  <td class="px-5 py-4">
                    <p class="text-[10px] font-black text-gray-600 dark:text-slate-300 uppercase line-clamp-1">{{ match.cliente_nombre }}</p>
                  </td>
                  <td class="px-5 py-4 text-right">
                    <div class="flex flex-col gap-0.5">
                      <span class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 italic">{{ formatCurrency(match.imp_pagado) }}</span>
                      <span v-if="match.found" class="text-[8px] text-gray-400 dark:text-slate-500 font-bold uppercase italic">Saldo Ant: {{ formatCurrency(match.imp_saldo_ant) }}</span>
                    </div>
                  </td>
                  <td class="px-5 py-4 text-center">
                    <span v-if="match.found" class="px-2 py-1 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 rounded-lg text-[9px] font-black uppercase tracking-widest border border-emerald-100 dark:border-emerald-800/50">Vínculo OK</span>
                    <span v-else class="px-2 py-1 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-lg text-[9px] font-black uppercase tracking-widest border border-red-100 dark:border-red-800/50">No encontrada</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <!-- Payment Options -->
          <div class="bg-gray-50/50 dark:bg-slate-900 rounded-3xl border border-gray-100 dark:border-slate-800 dark:border-slate-800 p-8 space-y-6">
            <h4 class="text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest">Opciones de Registro Financiero</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <label class="block text-[9px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-2">Método de Pago</label>
                <select v-model="metodoPago" class="w-full py-3 px-4 bg-white dark:bg-slate-900 dark:bg-slate-950 border-2 border-gray-100 dark:border-slate-800 dark:border-slate-800 rounded-2xl font-black text-xs text-gray-900 dark:text-white dark:text-white focus:border-blue-500 focus:ring-0 transition-all">
                  <option value="transferencia">Transferencia</option>
                  <option value="efectivo">Efectivo</option>
                  <option value="cheque">Cheque</option>
                  <option value="tarjeta">Tarjeta</option>
                  <option value="otros">Otros</option>
                </select>
              </div>
              <div>
                <label class="block text-[9px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-2">Cuenta Destino</label>
                <select v-model="cuentaBancariaId" class="w-full py-3 px-4 bg-white dark:bg-slate-900 dark:bg-slate-950 border-2 border-gray-100 dark:border-slate-800 dark:border-slate-800 rounded-2xl font-black text-xs text-gray-900 dark:text-white dark:text-white focus:border-blue-500 focus:ring-0 transition-all">
                  <option :value="null">-- Seleccionar Cuenta --</option>
                  <option v-for="cb in cuentasBancarias" :key="cb.id" :value="cb.id">
                    {{ cb.banco }} - {{ cb.nombre }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-[9px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-2">Notas Adicionales</label>
                <input v-model="notas" type="text" placeholder="Ej: Pago masivo..." class="w-full py-3 px-4 bg-white dark:bg-slate-900 dark:bg-slate-950 border-2 border-gray-100 dark:border-slate-800 dark:border-slate-800 rounded-2xl font-black text-xs text-gray-900 dark:text-white dark:text-white focus:border-blue-500 focus:ring-0 transition-all">
              </div>
            </div>
          </div>
          
          <!-- Large Error -->
          <div v-if="error" class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800/50 text-red-700 dark:text-red-400 rounded-2xl text-xs font-bold">
            {{ error }}
          </div>
        </div>
        
        <!-- Step: Success -->
        <div v-if="step === 'success'" class="text-center py-20">
          <div class="w-24 h-24 bg-emerald-50 dark:bg-emerald-900/20 rounded-full flex items-center justify-center mx-auto mb-8 border-4 border-emerald-100 dark:border-emerald-800/50 animate-bounce">
            <svg class="w-12 h-12 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
            </svg>
          </div>
          <h3 class="text-2xl font-black text-gray-900 dark:text-white dark:text-white uppercase tracking-widest mb-2">¡Pagos Aplicados!</h3>
          <p class="text-sm font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">Los movimientos se registraron exitosamente</p>
        </div>
      </div>
      
      <!-- Footer -->
      <div class="bg-white dark:bg-slate-900 dark:bg-slate-950 px-8 py-6 flex justify-between items-center border-t border-gray-100 dark:border-slate-800 dark:border-slate-800">
        <button
          v-if="step === 'preview'"
          @click="reset"
          class="text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest hover:text-gray-900 dark:text-white dark:hover:text-white transition-colors"
        >
          ← Regresar a Carga
        </button>
        <div v-else></div>
        
        <div class="flex items-center gap-4">
          <button
            @click="close"
            class="px-6 py-3 font-black text-gray-400 dark:text-slate-500 uppercase text-[10px] tracking-widest hover:text-gray-900 dark:text-white dark:hover:text-white transition-colors"
          >
            Cancelar
          </button>
          <button
            v-if="step === 'preview'"
            @click="applyPayments"
            :disabled="!canApply"
            :class="[
              'px-8 py-4 rounded-2xl font-black uppercase text-[10px] tracking-[0.2em] shadow-xl transition-all active:scale-95',
              canApply
                ? 'bg-blue-600 hover:bg-blue-700 text-white shadow-blue-200 dark:shadow-none'
                : 'bg-gray-100 dark:bg-slate-800 text-gray-400 dark:text-slate-600 cursor-not-allowed shadow-none'
            ]"
          >
            <span v-if="applying" class="flex items-center gap-2">
              <svg class="animate-spin h-3 w-3 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
              Aplicando...
            </span>
            <span v-else>Aplicar {{ selectedPayments.length }} Pagos Seleccionados</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
