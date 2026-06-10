<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { ref } from 'vue'

const props = defineProps({
    showDescargaModal: { type: Boolean, default: false },
    descargaForm: { type: Object, required: true },
    descargaSending: { type: Boolean, default: false },

    showReviewModal: { type: Boolean, default: false },
    isLoadingReview: { type: Boolean, default: false },
    documentosStaging: { type: Array, default: () => [] },
    duplicadosStaging: { type: Array, default: () => [] },
    selectedStagingIds: { type: Array, default: () => [] },
    isImporting: { type: Boolean, default: false },

    showDeleteConfirmModal: { type: Boolean, default: false },
    cfdiParaEliminar: { type: Object, default: null },
    isDeletingCfdi: { type: Boolean, default: false },
})

const emit = defineEmits([
    'closeDescarga', 'setQuickRange', 'setCurrentMonthRange', 'solicitarDescarga',
    'closeReview', 'toggleSeleccionStaging', 'seleccionarTodoStaging', 'deseleccionarTodoStaging',
    'verPdfStaging', 'importarSeleccionados',
    'closeDelete', 'ejecutarEliminacion'
])

// Local formatters to avoid passing them as props
const formatCurrency = (val) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(val || 0)
}

const formatDateShort = (dateStr) => {
    if (!dateStr) return '-'
    return new Date(dateStr).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' })
}

const getTipoBadge = (tipo) => {
    const map = {
        'I': { label: 'Ingreso', color: 'bg-brand-500/10 text-emerald-400 ring-emerald-500/20' },
        'E': { label: 'Egreso', color: 'bg-brand-500/10 text-rose-400 ring-rose-500/20' },
        'P': { label: 'Pago', color: 'bg-brand-500/10 text-blue-400 ring-blue-500/20' },
        'T': { label: 'Traslado', color: 'bg-slate-500/10 text-slate-400 ring-slate-500/20' },
        'N': { label: 'Nómina', color: 'bg-violet-500/10 text-violet-400 ring-violet-500/20' },
    }
    return map[tipo] || { label: tipo, color: 'bg-slate-500/10 text-slate-400 ring-slate-500/20' }
}

const closeDescarga = () => emit('closeDescarga')
const closeReview = () => emit('closeReview')
const closeDelete = () => emit('closeDelete')
</script>

<template>
  <!-- Modal Descarga Masiva SAT -->
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="showDescargaModal" class="fixed inset-0 z-[150] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-md" @click="closeDescarga"></div>
        <div class="relative w-full max-w-xl bg-slate-900 border border-white/10 rounded-[2.5rem] shadow-2xl p-10 animate-zoomIn overflow-hidden">
          <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-brand-500/5 blur-[80px]"></div>
          
          <button @click="closeDescarga" class="absolute top-8 right-8 text-slate-500 hover:text-white transition-colors z-10">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>

          <div class="relative z-10">
            <div class="flex items-center gap-4 mb-6">
              <div class="w-10 h-10 rounded-2xl bg-brand-500/10 text-emerald-400 flex items-center justify-center ring-1 ring-emerald-500/20">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" /></svg>
              </div>
              <div>
                <h3 class="text-2xl font-black text-white tracking-tight leading-none mb-2">Sincronización SAT</h3>
                <p class="text-xs text-slate-500 font-bold uppercase tracking-wide">Descarga Masiva de CFDI</p>
              </div>
            </div>

            <div class="space-y-6">
              <div class="flex gap-2">
                <button @click="descargaForm.direccion = 'emitido'"
                        :class="['flex-1 py-4 rounded-2xl font-black text-[11px] uppercase tracking-wide transition-all ring-1', 
                                 descargaForm.direccion === 'emitido' ? 'bg-emerald-600 text-white ring-emerald-500 shadow-xl shadow-emerald-900/40' : 'bg-slate-950/50 text-slate-500 ring-white/5 hover:text-slate-300']">
                  Emitidos (Ingresos)
                </button>
                <button @click="descargaForm.direccion = 'recibido'"
                        :class="['flex-1 py-4 rounded-2xl font-black text-[11px] uppercase tracking-wide transition-all ring-1', 
                                 descargaForm.direccion === 'recibido' ? 'bg-violet-600 text-white ring-violet-500 shadow-xl shadow-violet-900/40' : 'bg-slate-950/50 text-slate-500 ring-white/5 hover:text-slate-300']">
                  Recibidos (Gastos)
                </button>
              </div>

              <div class="flex flex-wrap gap-2 justify-center">
                <button @click="emit('setQuickRange', 1)" class="px-4 py-2 bg-slate-950/50 border border-white/5 hover:border-white/10 text-slate-400 hover:text-white rounded-xl text-[10px] font-black uppercase tracking-wide transition-all">Hoy</button>
                <button @click="emit('setQuickRange', 7)" class="px-4 py-2 bg-slate-950/50 border border-white/5 hover:border-white/10 text-slate-400 hover:text-white rounded-xl text-[10px] font-black uppercase tracking-wide transition-all">7 Días</button>
                <button @click="emit('setQuickRange', 15)" class="px-4 py-2 bg-slate-950/50 border border-white/5 hover:border-white/10 text-slate-400 hover:text-white rounded-xl text-[10px] font-black uppercase tracking-wide transition-all">15 Días</button>
                <button @click="emit('setCurrentMonthRange')" class="px-4 py-2 bg-slate-950/50 border border-white/5 hover:border-white/10 text-slate-400 hover:text-white rounded-xl text-[10px] font-black uppercase tracking-wide transition-all">Mes Actual</button>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wide mb-2 ml-1">Fecha Inicio</label>
                  <input type="date" v-model="descargaForm.fecha_inicio" class="w-full h-14 bg-slate-950 border-0 ring-1 ring-white/10 rounded-2xl text-sm font-bold text-slate-200 focus:ring-brand-500/50 transition-all" />
                </div>
                <div>
                  <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wide mb-2 ml-1">Fecha Fin</label>
                  <input type="date" v-model="descargaForm.fecha_fin" class="w-full h-14 bg-slate-950 border-0 ring-1 ring-white/10 rounded-2xl text-sm font-bold text-slate-200 focus:ring-brand-500/50 transition-all" />
                </div>
              </div>
            </div>

            <div class="flex gap-4 mt-10">
              <button @click="closeDescarga" class="flex-1 py-4 bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white rounded-[1.5rem] font-black text-[11px] uppercase tracking-wide transition-all ring-1 ring-white/5">
                Cancelar
              </button>
              <button @click="emit('solicitarDescarga')" :disabled="descargaSending"
                      class="flex-1 py-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-[1.5rem] font-black text-[11px] uppercase tracking-wide transition-all shadow-xl shadow-emerald-900/40 disabled:opacity-50">
                {{ descargaSending ? 'Conectando...' : 'Iniciar Sincronización' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>

  <!-- Revisor de Staging (Manual Review) -->
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="showReviewModal" class="fixed inset-0 z-[150] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-md" @click="closeReview"></div>
        <div class="relative w-full max-w-6xl h-[90vh] bg-slate-900 border border-white/10 rounded-[3rem] shadow-2xl flex flex-col overflow-hidden animate-zoomIn">
          
          <div class="px-10 py-8 border-b border-white/5 flex items-center justify-between bg-black/50 backdrop-blur-md">
            <div>
              <div class="flex items-center gap-2 mb-1">
                <div class="w-2 h-2 rounded-full bg-cyan-500 animate-pulse"></div>
                <h3 class="text-2xl font-black text-white tracking-tight">Revisión de Descargas</h3>
              </div>
              <p class="text-[10px] text-slate-500 font-black uppercase tracking-[0.3em]">Área de transferencia SAT staging</p>
            </div>
            <button @click="closeReview" class="w-10 h-10 flex items-center justify-center bg-white/5 text-slate-500 hover:text-white rounded-2xl transition-all border border-white/5">
              <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          </div>

          <div class="flex-1 overflow-y-auto p-10 relative bg-slate-950/30">
            <div v-if="isLoadingReview" class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm flex flex-col items-center justify-center gap-6 z-20">
              <div class="w-16 h-16 border-4 border-cyan-500/20 border-t-cyan-500 rounded-full animate-spin"></div>
              <p class="text-xs font-black text-slate-400 uppercase tracking-wide animate-pulse">Recuperando registros del SAT...</p>
            </div>

            <div v-else-if="documentosStaging.length === 0 && duplicadosStaging.length === 0" class="h-full flex flex-col items-center justify-center text-center py-20 border-2 border-dashed border-white/5 rounded-[3rem]">
              <div class="w-16 h-16 bg-white/5 rounded-3xl flex items-center justify-center mb-6 ring-1 ring-white/10">
                <svg class="w-10 h-10 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              </div>
              <p class="text-xl font-black text-white italic tracking-tight">Sin documentos pendientes</p>
              <p class="text-sm text-slate-500 mt-2">Todo el lote ha sido procesado correctamente.</p>
            </div>

            <div v-else class="space-y-6">
              <div v-if="documentosStaging.length">
                <div class="flex items-center justify-between mb-6 px-4">
                  <h4 class="text-xs font-black text-slate-400 uppercase tracking-wide flex items-center gap-2">
                    Documentos Encontrados
                    <span class="bg-brand-500/10 text-emerald-400 px-3 py-1 rounded-full text-[10px] ring-1 ring-emerald-500/20">{{ documentosStaging.length }}</span>
                  </h4>
                  <div class="flex gap-4">
                    <button @click="emit('seleccionarTodoStaging')" class="text-[10px] font-black text-cyan-400 uppercase tracking-wide hover:text-cyan-300 transition-colors">Seleccionar Todo</button>
                    <button @click="emit('deseleccionarTodoStaging')" class="text-[10px] font-black text-rose-500 uppercase tracking-wide hover:text-rose-400 transition-colors">Deseleccionar</button>
                  </div>
                </div>

                <div class="space-y-3">
                  <div v-for="doc in documentosStaging" :key="doc.id" 
                       :class="['p-6 rounded-[2rem] border-2 transition-all flex items-center gap-6 group relative overflow-hidden', 
                                doc.importado ? 'bg-slate-900/20 border-white/5 opacity-50' :
                                selectedStagingIds.includes(doc.id) ? 'bg-brand-500/5 border-emerald-500/50 shadow-xl shadow-emerald-900/20' : 'bg-slate-900/50 border-white/5 hover:border-white/10 hover:bg-slate-900/50']">
                  
                    <div v-if="doc.importado" class="w-10 h-10 rounded-xl bg-brand-500 text-white flex items-center justify-center flex-shrink-0">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                    </div>
                    <div v-else @click="emit('toggleSeleccionStaging', doc.id)"
                         :class="['w-10 h-10 rounded-xl border-2 flex items-center justify-center transition-all cursor-pointer flex-shrink-0', 
                                 selectedStagingIds.includes(doc.id) ? 'bg-emerald-600 border-emerald-500 shadow-xl shadow-emerald-500/50' : 'border-white/10 bg-slate-950 group-hover:border-white/30']">
                      <svg v-if="selectedStagingIds.includes(doc.id)" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    </div>

                    <div class="flex-1 grid grid-cols-1 md:grid-cols-12 gap-6 items-center" @click="!doc.importado && emit('toggleSeleccionStaging', doc.id)">
                      <div class="flex flex-col md:col-span-3">
                        <div class="flex items-center gap-2 mb-1.5">
                          <span :class="['px-2 py-0.5 rounded-xl text-[9px] font-black uppercase tracking-wide ring-1 ring-inset', getTipoBadge(doc.tipo_comprobante).color]">
                            {{ getTipoBadge(doc.tipo_comprobante).label }}
                          </span>
                          <span class="text-[10px] font-mono text-slate-500 uppercase">{{ doc.uuid?.substring(0, 8) }}...</span>
                        </div>
                        <span class="text-xs font-black text-slate-200 tabular-nums italic">{{ formatDateShort(doc.fecha_emision) }}</span>
                      </div>
                      
                      <div class="flex flex-col md:col-span-5">
                        <span class="text-[9px] font-black text-slate-500 uppercase tracking-wide mb-1">{{ doc.direccion === 'recibido' ? 'Emisor' : 'Receptor' }}</span>
                        <span class="text-sm font-bold text-white truncate">{{ doc.direccion === 'recibido' ? (doc.nombre_emisor || 'PROVEEDOR DESCONOCIDO') : (doc.nombre_receptor || 'PÚBLICO GENERAL') }}</span>
                        <span class="text-[10px] font-mono text-cyan-400/70">{{ doc.direccion === 'recibido' ? doc.rfc_emisor : doc.rfc_receptor }}</span>
                      </div>
                      
                      <div class="md:col-span-4 flex items-center justify-end gap-6">
                        <span class="text-lg font-black text-emerald-400 tabular-nums italic tracking-tighter">
                          {{ formatCurrency(doc.total) }}
                        </span>
                        
                        <div class="flex items-center gap-2" @click.stop>
                          <button @click="emit('verPdfStaging', doc)" class="w-9 h-9 flex items-center justify-center bg-white/5 hover:bg-blue-600 hover:text-white rounded-xl transition-all border border-white/5" title="Ver PDF">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Duplicates Section -->
              <div v-if="duplicadosStaging.length" class="mt-12 bg-slate-900/20 p-8 rounded-[3rem] border border-white/5">
                <h4 class="text-xs font-black text-slate-500 uppercase tracking-wide mb-6 flex items-center gap-2">
                  Documentos ya existentes
                  <span class="bg-slate-800 text-slate-500 px-3 py-1 rounded-full text-[10px] ring-1 ring-white/5">{{ duplicadosStaging.length }}</span>
                </h4>
                <div class="space-y-3 opacity-60 grayscale hover:opacity-100 hover:grayscale-0 transition-all">
                  <div v-for="doc in duplicadosStaging" :key="doc.uuid"
                       class="p-5 rounded-2xl border border-white/5 bg-slate-950/40 flex items-center gap-6">
                    <div class="w-10 h-10 rounded-xl bg-slate-800 flex items-center justify-center text-[10px] font-black text-slate-500 ring-1 ring-white/5">D</div>
                    <div class="flex-1 grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
                      <div class="md:col-span-3">
                        <span class="text-[10px] font-mono text-slate-500 block mb-1 uppercase tracking-wide">{{ doc.uuid?.substring(0, 13) }}...</span>
                        <span class="text-xs font-bold text-slate-400">{{ formatDateShort(doc.fecha_emision) }}</span>
                      </div>
                      <div class="md:col-span-5">
                        <p class="text-xs font-bold text-slate-300 truncate">{{ doc.direccion === 'recibido' ? doc.nombre_emisor : doc.nombre_receptor }}</p>
                        <p class="text-[10px] font-mono text-slate-500">{{ doc.direccion === 'recibido' ? doc.rfc_emisor : doc.rfc_receptor }}</p>
                      </div>
                      <div class="md:col-span-4 text-right">
                        <span class="text-sm font-black text-slate-400 tabular-nums">{{ formatCurrency(doc.total) }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="px-10 py-8 border-t border-white/5 bg-slate-900/80 backdrop-blur-md flex items-center justify-between">
            <div class="flex flex-col">
              <span class="text-[10px] font-black text-slate-500 uppercase tracking-wide">Selección Actual</span>
              <p class="text-sm font-black text-white tabular-nums">{{ selectedStagingIds.length }} <span class="text-slate-500 text-xs font-bold uppercase ml-1">Documentos por importar</span></p>
            </div>
            <div class="flex gap-4">
              <button @click="closeReview" class="px-8 py-4 bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white rounded-2xl font-black text-[11px] uppercase tracking-wide border border-white/5 transition-all">
                Cerrar Visor
              </button>
              <button @click="emit('importarSeleccionados')" :disabled="isImporting || selectedStagingIds.length === 0"
                      :class="['px-10 py-4 rounded-2xl font-black text-[11px] uppercase tracking-wide transition-all shadow-xl', 
                               selectedStagingIds.length === 0 ? 'bg-slate-800 text-slate-500 cursor-not-allowed' : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-emerald-900/40']">
                {{ isImporting ? 'Procesando...' : 'Importar Seleccionados' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>

  <!-- Confirm Delete Modal -->
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="showDeleteConfirmModal" class="fixed inset-0 z-[200] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/90 backdrop-blur-md" @click="closeDelete"></div>
        <div class="relative w-full max-w-md bg-slate-900 border border-white/10 rounded-[3rem] shadow-2xl p-10 animate-zoomIn text-center overflow-hidden">
          <div class="absolute -right-20 -top-20 h-40 w-40 rounded-full bg-brand-500/5 blur-3xl"></div>
          
          <div class="w-16 h-16 bg-brand-500/10 text-rose-500 rounded-3xl flex items-center justify-center mx-auto mb-8 ring-1 ring-rose-500/20 shadow-xl shadow-rose-950/20">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
          </div>
          
          <h3 class="text-2xl font-black text-white tracking-tight italic mb-3">¿Eliminar Comprobante?</h3>
          <p class="text-slate-400 font-bold mb-8 text-sm leading-relaxed px-4">
            Se eliminará el registro y el archivo <span class="text-rose-500">XML físico</span> permanentemente. Esta acción es irreversible.
          </p>

          <div v-if="cfdiParaEliminar" class="bg-slate-950/50 rounded-2xl p-5 mb-10 text-left border border-white/5 ring-1 ring-inset ring-white/5">
            <span class="text-[9px] font-black text-slate-500 uppercase tracking-wide block mb-2">Comprobante a purgar</span>
            <div class="flex justify-between items-center">
              <p class="text-base font-black text-slate-200 italic tracking-tight">{{ cfdiParaEliminar.serie }}{{ cfdiParaEliminar.folio || 'S/F' }}</p>
              <span class="text-[10px] font-black text-rose-400 bg-rose-400/10 px-2 py-0.5 rounded-xl uppercase ring-1 ring-rose-400/20">{{ cfdiParaEliminar.total_formatted || formatCurrency(cfdiParaEliminar.total) }}</span>
            </div>
            <p class="text-[9px] font-mono text-slate-500 mt-2 truncate">{{ cfdiParaEliminar.uuid }}</p>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <button @click="closeDelete" :disabled="isDeletingCfdi"
                    class="py-4 bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white rounded-2xl font-black text-[11px] uppercase tracking-wide transition-all ring-1 ring-white/5">
              Cancelar
            </button>
            <button @click="emit('ejecutarEliminacion')" :disabled="isDeletingCfdi"
                    class="py-4 bg-rose-600 hover:bg-rose-700 text-white rounded-2xl font-black text-[11px] uppercase tracking-wide transition-all shadow-xl shadow-rose-900/40">
              {{ isDeletingCfdi ? 'Purgando...' : 'Confirmar' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.05); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.1); }

.animate-zoomIn { animation: zoomIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
.animate-fadeIn { animation: fadeIn 0.3s ease-out; }

@keyframes zoomIn { from { opacity: 0; transform: scale(0.95) translateY(10px); } to { opacity: 1; transform: scale(1) translateY(0); } }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
