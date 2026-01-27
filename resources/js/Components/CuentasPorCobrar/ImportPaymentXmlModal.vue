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
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 backdrop-blur-md p-4">
    <div class="bg-[#0F172A] text-slate-300 rounded-3xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col border border-slate-700/50 relative">
      <!-- Decorative Gradients -->
      <div class="absolute top-0 right-0 w-96 h-96 bg-blue-600/10 blur-[100px] rounded-full pointer-events-none"></div>
      <div class="absolute bottom-0 left-0 w-96 h-96 bg-purple-600/10 blur-[100px] rounded-full pointer-events-none"></div>

      <!-- Header -->
      <div class="bg-slate-900/80 backdrop-blur-xl px-8 py-6 flex justify-between items-center border-b border-slate-800 z-10">
        <div>
          <h2 class="text-lg font-black uppercase tracking-widest text-white flex items-center gap-2">
            <span class="p-1.5 bg-blue-500/10 rounded-lg text-blue-400">
               <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            </span>
            {{ step === 'upload' ? 'Importar XML' : step === 'preview' ? 'Validación de Pagos' : 'Proceso Completado' }}
          </h2>
          <p class="text-[10px] text-slate-500 font-bold uppercase tracking-[0.2em] mt-1 pl-9">
            {{ step === 'upload' ? 'Carga de Comprobante CFDI' : 'Revisión preliminar' }}
          </p>
        </div>
        <button @click="close" class="p-2 text-slate-500 hover:text-white hover:bg-slate-800 rounded-xl transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
      </div>
      
      <!-- Content -->
      <div class="flex-1 overflow-y-auto p-8 relative z-10 scrollbar-thin scrollbar-thumb-slate-700 scrollbar-track-transparent">
        <!-- Step: Upload -->
        <div v-if="step === 'upload'" class="space-y-8">
          <p class="text-sm text-slate-400 font-medium max-w-2xl mx-auto text-center leading-relaxed">
            Suba el archivo XML del <span class="text-blue-400 font-bold">Recibo Electrónico de Pago (REP)</span>. El sistema identificará automáticamente las facturas relacionadas y aplicará los abonos correspondientes.
          </p>
          
          <!-- Drag & Drop Zone -->
          <div
            @dragover="handleDragOver"
            @dragleave="handleDragLeave"
            @drop="handleDrop"
            :class="[
              'border-2 border-dashed rounded-3xl p-16 text-center transition-all cursor-pointer group relative overflow-hidden',
              dragOver 
                ? 'border-blue-500 bg-blue-500/10' 
                : 'border-slate-700 hover:border-blue-400 hover:bg-slate-800/50'
            ]"
            @click="$refs.fileInput.click()"
          >
            <div v-if="loading" class="flex flex-col items-center relative z-10">
              <div class="animate-spin rounded-full h-16 w-16 border-4 border-blue-500 border-t-transparent mb-6 shadow-lg shadow-blue-500/20"></div>
              <p class="text-sm font-black text-blue-400 uppercase tracking-widest animate-pulse">Analizando estructura XML...</p>
            </div>
            <div v-else class="flex flex-col items-center relative z-10">
              <div class="w-24 h-24 bg-slate-800 rounded-3xl flex items-center justify-center mb-6 border border-slate-700 shadow-xl group-hover:scale-110 transition-transform duration-300 group-hover:shadow-blue-500/20 group-hover:border-blue-500/50">
                <svg class="w-10 h-10 text-slate-400 group-hover:text-blue-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
              </div>
              <p class="text-xl font-black text-white mb-2">Arrastra tu archivo XML aquí</p>
              <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest bg-slate-800/50 px-3 py-1 rounded-full border border-slate-700/50">o clic para explorar archivos</p>
            </div>
            <input type="file" ref="fileInput" class="hidden" accept=".xml" @change="handleFileSelect">
          </div>
          
          <!-- Error -->
          <div v-if="error" class="p-4 bg-red-500/10 border border-red-500/20 text-red-400 rounded-2xl flex items-start gap-3 backdrop-blur-sm">
             <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
             <p class="text-xs font-bold leading-relaxed">{{ error }}</p>
          </div>
        </div>
        
        <!-- Step: Preview -->
        <div v-if="step === 'preview'" class="space-y-8">
          <!-- Payment Info Card -->
          <div class="bg-slate-800/50 backdrop-blur-md rounded-3xl border border-slate-700/50 p-8 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-8 opacity-[0.03]">
              <svg class="w-32 h-32 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
            </div>
            <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-6 border-b border-slate-700/50 pb-2">Datos del Comprobante</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
              <div>
                <p class="text-[9px] text-slate-500 font-bold uppercase tracking-wider mb-1">Fecha Pago</p>
                <p class="text-sm font-black text-white">{{ formatDate(paymentInfo?.fecha_pago) }}</p>
              </div>
              <div>
                <p class="text-[9px] text-slate-500 font-bold uppercase tracking-wider mb-1">Monto Total</p>
                <p class="text-xl font-black text-emerald-400 tabular-nums">{{ formatCurrency(paymentInfo?.monto_total) }}</p>
              </div>
              <div>
                <p class="text-[9px] text-slate-500 font-bold uppercase tracking-wider mb-1">Forma Pago</p>
                <p class="text-sm font-black text-white">{{ paymentInfo?.forma_pago || 'N/A' }}</p>
              </div>
              <div>
                <p class="text-[9px] text-slate-500 font-bold uppercase tracking-wider mb-1">UUID</p>
                <p class="text-[10px] font-mono font-bold text-blue-400 bg-blue-500/10 px-2 py-1 rounded inline-block truncate max-w-full" :title="paymentInfo?.uuid">{{ paymentInfo?.uuid?.slice(0, 13) }}...</p>
              </div>
            </div>
          </div>
          
          <!-- Match Summary Bar -->
          <div class="flex items-center justify-between px-2">
            <div class="flex items-center gap-3">
              <span class="text-xs font-black text-white uppercase tracking-widest">Documentos Relacionados</span>
              <span class="px-2.5 py-1 bg-blue-500/20 text-blue-400 rounded-lg text-[10px] font-black border border-blue-500/30">{{ documentosEncontrados }} / {{ totalDocumentos }} Coincidencias</span>
            </div>
            <span v-if="documentosEncontrados < totalDocumentos" class="text-[10px] text-amber-500 font-black uppercase tracking-widest flex items-center gap-1 bg-amber-500/10 px-2 py-1 rounded-lg border border-amber-500/20">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
              Inconsistencias
            </span>
          </div>
          
          <!-- Matches Table -->
          <div class="bg-slate-800/30 rounded-3xl border border-slate-700/50 overflow-hidden">
            <table class="w-full text-left">
              <thead>
                <tr class="bg-slate-800/80 border-b border-slate-700">
                  <th class="px-5 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Select</th>
                  <th class="px-5 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Referencia</th>
                  <th class="px-5 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Cliente</th>
                  <th class="px-5 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Abono XML</th>
                  <th class="px-5 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Estado</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-700/50">
                <tr v-for="(match, idx) in matches" :key="idx" 
                    :class="[match.found ? 'hover:bg-slate-700/30 transition-colors' : 'bg-slate-900/50 opacity-50 grayscale']"
                    @click="match.found && toggleSelection(match)">
                  <td class="px-5 py-4">
                    <input
                      type="checkbox"
                      :checked="match.selected"
                      :disabled="!match.found"
                      @click.stop
                      @change="toggleSelection(match)"
                      class="w-5 h-5 rounded-lg border-2 border-slate-600 bg-slate-800 text-blue-500 focus:ring-offset-slate-900 focus:ring-blue-500 transition-all cursor-pointer disabled:opacity-50"
                    >
                  </td>
                  <td class="px-5 py-4">
                    <div class="flex flex-col gap-0.5">
                      <span class="text-[10px] font-black text-white uppercase tracking-wider">{{ match.numero_venta }}</span>
                      <span class="text-[9px] font-mono text-slate-500 uppercase">{{ match.uuid.slice(0, 13) }}...</span>
                    </div>
                  </td>
                  <td class="px-5 py-4">
                    <p class="text-[10px] font-bold text-slate-300 uppercase line-clamp-1">{{ match.cliente_nombre }}</p>
                  </td>
                  <td class="px-5 py-4 text-right">
                    <div class="flex flex-col gap-0.5">
                      <span class="text-[10px] font-black text-emerald-400 tabular-nums italic">{{ formatCurrency(match.imp_pagado) }}</span>
                      <span v-if="match.found" class="text-[8px] text-slate-500 font-bold uppercase italic tabular-nums">Saldo Ant: {{ formatCurrency(match.imp_saldo_ant) }}</span>
                    </div>
                  </td>
                  <td class="px-5 py-4 text-center">
                    <span v-if="match.found" class="px-2 py-1 bg-emerald-500/10 text-emerald-500 rounded-lg text-[9px] font-black uppercase tracking-widest border border-emerald-500/20">OK</span>
                    <span v-else class="px-2 py-1 bg-red-500/10 text-red-500 rounded-lg text-[9px] font-black uppercase tracking-widest border border-red-500/20">Error</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <!-- Payment Options -->
          <div class="bg-slate-800/30 rounded-3xl border border-slate-700/50 p-8 space-y-6">
            <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-widest border-b border-slate-700/50 pb-2">Destino Financiero</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-wider mb-2">Método Sistema</label>
                <select v-model="metodoPago" class="w-full py-3 px-4 bg-slate-900 border border-slate-700 rounded-xl font-bold text-xs text-white focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all">
                  <option value="transferencia">Transferencia</option>
                  <option value="efectivo">Efectivo</option>
                  <option value="cheque">Cheque</option>
                  <option value="tarjeta">Tarjeta</option>
                  <option value="otros">Otros</option>
                </select>
              </div>
              <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-wider mb-2">Cuenta Bancaria</label>
                <select v-model="cuentaBancariaId" class="w-full py-3 px-4 bg-slate-900 border border-slate-700 rounded-xl font-bold text-xs text-white focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all">
                  <option :value="null">-- Seleccionar Cuenta --</option>
                  <option v-for="cb in cuentasBancarias" :key="cb.id" :value="cb.id">
                    {{ cb.banco }} - {{ cb.nombre }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-wider mb-2">Referencia / Notas</label>
                <input v-model="notas" type="text" placeholder="Ej: Pago masivo XML..." class="w-full py-3 px-4 bg-slate-900 border border-slate-700 rounded-xl font-bold text-xs text-white placeholder-slate-600 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all">
              </div>
            </div>
          </div>
          
          <!-- Large Error -->
          <div v-if="error" class="p-4 bg-red-500/10 border border-red-500/20 text-red-400 rounded-2xl text-xs font-bold text-center">
            {{ error }}
          </div>
        </div>
        
        <!-- Step: Success -->
        <div v-if="step === 'success'" class="text-center py-20">
          <div class="w-24 h-24 bg-emerald-500/10 rounded-full flex items-center justify-center mx-auto mb-8 border-4 border-emerald-500/20 animate-bounce shadow-lg shadow-emerald-500/20">
            <svg class="w-12 h-12 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
            </svg>
          </div>
          <h3 class="text-2xl font-black text-white uppercase tracking-widest mb-2">¡Pagos Aplicados!</h3>
          <p class="text-sm font-bold text-slate-500 uppercase tracking-widest">La importación se completó correctamente</p>
        </div>
      </div>
      
      <!-- Footer -->
      <div class="bg-slate-900/80 backdrop-blur-xl px-8 py-6 flex justify-between items-center border-t border-slate-800 z-10">
        <button
          v-if="step === 'preview'"
          @click="reset"
          class="text-[10px] font-black text-slate-500 uppercase tracking-widest hover:text-white transition-colors flex items-center gap-2"
        >
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
          Volver a Carga
        </button>
        <div v-else></div>
        
        <div class="flex items-center gap-4">
          <button
            @click="close"
            class="px-6 py-3 font-black text-slate-500 uppercase text-[10px] tracking-widest hover:text-white hover:bg-slate-800 rounded-xl transition-all"
          >
            Cancelar
          </button>
          <button
            v-if="step === 'preview'"
            @click="applyPayments"
            :disabled="!canApply"
            :class="[
              'px-8 py-4 rounded-xl font-black uppercase text-[10px] tracking-[0.2em] shadow-lg transition-all active:scale-95',
              canApply
                ? 'bg-blue-600 hover:bg-blue-500 text-white shadow-blue-900/40'
                : 'bg-slate-800 text-slate-600 cursor-not-allowed shadow-none'
            ]"
          >
            <span v-if="applying" class="flex items-center gap-2">
              <svg class="animate-spin h-3 w-3 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
              Aplicando...
            </span>
            <span v-else>Confirmar {{ selectedPayments.length }} Pagos</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
