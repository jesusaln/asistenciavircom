<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { ref } from 'vue'

const props = defineProps({
    show: { type: Boolean, default: false },
    uuid: { type: String, default: '' },
    xmlContent: { type: String, default: '' },
    parsedData: { type: Object, default: null },
    isLoading: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'copied'])

const activeTab = ref('info')
const tabs = [
    { id: 'info', label: 'Información' },
    { id: 'items', label: 'Conceptos' },
    { id: 'taxes', label: 'Impuestos' },
    { id: 'xml', label: 'XML Raw' },
]

const emitClose = () => emit('close')

const formatMoney = (val) => {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN'
    }).format(val || 0)
}

const copyXml = () => {
    if (!props.xmlContent) return
    navigator.clipboard.writeText(props.xmlContent)
    emit('copied')
}

const formatDate = (dateStr) => {
    if (!dateStr) return '-'
    const date = new Date(dateStr)
    return date.toLocaleDateString('es-MX', { 
        day: '2-digit', 
        month: 'long', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getTipoLabel = (tipo) => {
    const tipos = {
        'I': 'Ingreso/Factura',
        'P': 'Pago/REP',
        'E': 'Egreso/N. Crédito',
        'N': 'Nómina',
        'T': 'Traslado'
    }
    return tipos[tipo] || tipo
}

const downloadXml = () => {
    if (!props.xmlContent) return
    const blob = new Blob([props.xmlContent], { type: 'text/xml' })
    const link = document.createElement('a')
    link.href = window.URL.createObjectURL(blob)
    link.download = `${props.uuid}.xml`
    link.click()
}

const verPdfNuevaPestana = () => {
    window.open(route('cfdi.ver-pdf-view', props.uuid), '_blank', 'noopener,noreferrer')
}

const downloadPdf = () => {
    window.open(route('cfdi.ver-pdf', { uuid: props.uuid, download: 1 }), '_blank', 'noopener,noreferrer')
}
</script>

<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="show" class="fixed inset-0 z-[150] flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-md" @click="emitClose"></div>

        <!-- Modal Content -->
        <div class="relative w-full max-w-5xl h-[85vh] bg-slate-900 border border-white/10 rounded-[2.5rem] shadow-2xl flex flex-col overflow-hidden animate-zoomIn">
          
          <!-- Header -->
          <div class="px-8 py-5 border-b border-white/5 flex items-center justify-between bg-black/50 backdrop-blur-sm">
            <div class="flex items-center gap-4">
              <div class="w-10 h-10 rounded-2xl bg-cyan-500/10 text-cyan-400 flex items-center justify-center ring-1 ring-cyan-500/20 shadow-xl shadow-cyan-900/20">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
              </div>
              <div>
                <h2 class="text-xl font-black text-white tracking-tight leading-none mb-1">Visor de Comprobante</h2>
                <span class="text-[10px] font-mono text-slate-500 uppercase tracking-wide">{{ uuid }}</span>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <button @click="downloadXml" class="h-10 px-4 flex items-center gap-2 text-[10px] font-black uppercase tracking-wide text-indigo-400 bg-indigo-500/10 hover:bg-indigo-500/20 rounded-xl transition-all border border-indigo-500/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" /></svg>
                XML
              </button>
              <button @click="verPdfNuevaPestana" class="h-10 px-4 flex items-center gap-2 text-[10px] font-black uppercase tracking-wide text-cyan-400 bg-cyan-500/10 hover:bg-cyan-500/20 rounded-xl transition-all border border-cyan-500/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                PDF
              </button>
              <div class="w-px h-8 bg-white/5 mx-2"></div>
              <button @click="emitClose" class="w-10 h-10 flex items-center justify-center text-slate-500 hover:text-white hover:bg-white/5 rounded-xl transition-all">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
            </div>
          </div>

          <!-- Tabs -->
          <div class="px-8 bg-slate-900 border-b border-white/5 flex gap-8">
            <button v-for="tab in tabs" :key="tab.id" 
                    @click="activeTab = tab.id"
                    :class="['py-4 text-[10px] font-black uppercase tracking-[0.2em] transition-all relative', 
                             activeTab === tab.id ? 'text-cyan-400' : 'text-slate-500 hover:text-slate-300']">
              {{ tab.label }}
              <div v-if="activeTab === tab.id" class="absolute bottom-0 left-0 right-0 h-1 bg-cyan-500 rounded-t-full shadow-[0_0_15px_rgba(34,211,238,0.5)]"></div>
            </button>
          </div>

          <!-- Body -->
          <div class="flex-1 overflow-hidden p-8 bg-slate-950/20">
            
            <div v-if="isLoading" class="h-full flex flex-col items-center justify-center">
              <svg class="animate-spin h-10 w-10 text-cyan-500 mb-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
              <p class="text-xs font-black uppercase tracking-wide text-slate-500">Decodificando XML...</p>
            </div>

            <template v-else-if="parsedData">
              <!-- Info Tab -->
              <div v-if="activeTab === 'info'" class="h-full overflow-y-auto custom-scrollbar pr-4 space-y-6 animate-fadeIn">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <div class="p-6 bg-slate-900/50 rounded-[2rem] border border-white/5 relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-cyan-500/5 blur-2xl transition-colors group-hover:bg-cyan-500/10"></div>
                    <span class="text-[9px] font-black text-slate-500 uppercase tracking-wide block mb-2">Monto Total</span>
                    <span class="text-2xl font-black text-white tracking-tight tabular-nums">{{ formatMoney(parsedData.total) }}</span>
                    <span class="block mt-1 text-[10px] font-black text-cyan-400/80 uppercase tracking-wide">{{ parsedData.moneda }}</span>
                  </div>
                  
                  <div class="p-6 bg-slate-900/50 rounded-[2rem] border border-white/5">
                    <span class="text-[9px] font-black text-slate-500 uppercase tracking-wide block mb-2">Fecha Certificación</span>
                    <span class="text-2xl font-black text-slate-100 tracking-tight block">{{ formatDate(parsedData.fecha) }}</span>
                  </div>

                  <div class="p-6 bg-slate-900/50 rounded-[2rem] border border-white/5 text-center flex flex-col items-center justify-center">
                    <span class="text-[9px] font-black text-slate-500 uppercase tracking-wide block mb-2">Tipo Comprobante</span>
                    <span class="px-4 py-1.5 bg-cyan-500/10 text-cyan-400 text-[10px] font-black uppercase rounded-full ring-1 ring-cyan-500/20 tracking-[0.1em]">
                      {{ getTipoLabel(parsedData.tipoComprobante) }}
                    </span>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-8">
                  <div class="space-y-6">
                    <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] flex items-center gap-2">
                      <div class="w-1.5 h-1.5 rounded-full bg-cyan-500 shadow-[0_0_8px_rgba(34,211,238,0.5)]"></div>
                      Emisor
                    </h4>
                    <div class="p-6 bg-slate-900/50 border border-white/5 rounded-[2rem] space-y-6">
                      <div>
                        <span class="text-[8px] font-black text-slate-500 uppercase tracking-wide block mb-1">Razón Social</span>
                        <p class="text-sm font-bold text-slate-100 leading-tight">{{ parsedData.emisor.nombre }}</p>
                      </div>
                      <div class="flex gap-8">
                        <div>
                          <span class="text-[8px] font-black text-slate-500 uppercase tracking-wide block mb-1">RFC</span>
                          <p class="text-xs font-mono font-bold text-cyan-400/80">{{ parsedData.emisor.rfc }}</p>
                        </div>
                        <div>
                          <span class="text-[8px] font-black text-slate-500 uppercase tracking-wide block mb-1">Régimen</span>
                          <p class="text-[11px] font-bold text-slate-400">{{ parsedData.emisor.regimenFiscal }}</p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="space-y-6">
                    <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] flex items-center gap-2">
                      <div class="w-1.5 h-1.5 rounded-full bg-violet-500 shadow-[0_0_8px_rgba(139,92,246,0.5)]"></div>
                      Receptor
                    </h4>
                    <div class="p-6 bg-slate-900/50 border border-white/5 rounded-[2rem] space-y-6">
                      <div>
                        <span class="text-[8px] font-black text-slate-500 uppercase tracking-wide block mb-1">Razón Social</span>
                        <p class="text-sm font-bold text-slate-100 leading-tight">{{ parsedData.receptor.nombre }}</p>
                      </div>
                      <div class="flex gap-8">
                        <div>
                          <span class="text-[8px] font-black text-slate-500 uppercase tracking-wide block mb-1">RFC</span>
                          <p class="text-xs font-mono font-bold text-violet-400/80">{{ parsedData.receptor.rfc }}</p>
                        </div>
                        <div>
                          <span class="text-[8px] font-black text-slate-500 uppercase tracking-wide block mb-1">Uso CFDI</span>
                          <p class="text-[11px] font-bold text-slate-400">{{ parsedData.receptor.usoCfdi }}</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Concepts Tab -->
              <div v-if="activeTab === 'items'" class="h-full flex flex-col animate-fadeIn">
                <div class="flex-1 overflow-y-auto custom-scrollbar pr-4 space-y-3">
                  <div v-for="(concepto, idx) in parsedData.conceptos" :key="idx" 
                       class="p-5 bg-slate-900/50 border border-white/5 rounded-2xl hover:bg-slate-900/50 hover:border-white/10 transition-all flex justify-between items-center gap-6 group">
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center gap-2 mb-1.5">
                        <span class="px-2 py-0.5 bg-slate-950 text-slate-500 text-[9px] font-black rounded-xl uppercase ring-1 ring-white/5">{{ concepto.clave }}</span>
                        <p class="text-xs font-bold text-slate-200 group-hover:text-white transition-colors">{{ concepto.descripcion }}</p>
                      </div>
                      <div class="flex gap-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                        <span>Cant: <b class="text-slate-300">{{ concepto.cantidad }}</b></span>
                        <span>Unit: <b class="text-slate-300">{{ formatMoney(concepto.valorUnitario) }}</b></span>
                        <span v-if="concepto.descuento > 0" class="text-rose-500/80">Desc: {{ formatMoney(concepto.descuento) }}</span>
                      </div>
                    </div>
                    <div class="text-right">
                      <span class="text-sm font-black text-slate-100 tabular-nums italic tracking-tighter">{{ formatMoney(concepto.importe) }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Taxes Tab -->
              <div v-if="activeTab === 'taxes'" class="h-full overflow-y-auto custom-scrollbar pr-4 space-y-6 animate-fadeIn">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                  <div class="space-y-6">
                    <h5 class="text-[10px] font-black text-emerald-500/80 uppercase tracking-wide flex items-center gap-2">
                      <div class="w-1.5 h-1.5 bg-brand-500 rounded-full"></div>
                      Traslados (Impuestos Cobrados)
                    </h5>
                    <div v-if="parsedData.impuestos.traslados.length" class="space-y-3">
                      <div v-for="(t, idx) in parsedData.impuestos.traslados" :key="idx" class="p-4 bg-brand-500/5 border border-emerald-500/10 rounded-2xl flex justify-between items-center">
                        <div class="flex flex-col">
                          <span class="text-[9px] font-black text-emerald-600 uppercase tracking-wide">Impuesto {{ t.impuesto }} ({{ t.tasaOCuota * 100 }}%)</span>
                          <span class="text-[11px] font-bold text-slate-400 italic">Base: {{ formatMoney(t.base) }}</span>
                        </div>
                        <span class="text-sm font-black text-emerald-400">{{ formatMoney(t.importe) }}</span>
                      </div>
                    </div>
                    <p v-else class="text-[11px] font-bold text-slate-500 italic px-4">Sin impuestos trasladados</p>
                  </div>

                  <div class="space-y-6">
                    <h5 class="text-[10px] font-black text-rose-500/80 uppercase tracking-wide flex items-center gap-2">
                      <div class="w-1.5 h-1.5 bg-brand-500 rounded-full"></div>
                      Retenciones
                    </h5>
                    <div v-if="parsedData.impuestos.retenciones.length" class="space-y-3">
                      <div v-for="(r, idx) in parsedData.impuestos.retenciones" :key="idx" class="p-4 bg-brand-500/5 border border-rose-500/10 rounded-2xl flex justify-between items-center">
                        <span class="text-[9px] font-black text-rose-600 uppercase tracking-wide">Impuesto {{ r.impuesto }}</span>
                        <span class="text-sm font-black text-rose-400">{{ formatMoney(r.importe) }}</span>
                      </div>
                    </div>
                    <p v-else class="text-[11px] font-bold text-slate-500 italic px-4">Sin retenciones</p>
                  </div>
                </div>
              </div>

              <!-- Raw XML Tab -->
              <div v-if="activeTab === 'xml'" class="h-full flex flex-col animate-fadeIn">
                <div class="flex items-center justify-between mb-4">
                  <span class="text-[9px] font-black text-slate-500 uppercase tracking-wide">Estructura XML original</span>
                  <button @click="copyXml" class="px-3 py-1 bg-white/5 hover:bg-white/10 rounded-xl text-[9px] font-black text-slate-300 uppercase tracking-wide flex items-center gap-2 transition-all border border-white/5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" /></svg>
                    Copiar
                  </button>
                </div>
                <div class="flex-1 bg-slate-950/80 rounded-[2rem] p-6 overflow-auto custom-scrollbar font-mono text-[11px] text-cyan-300/80 select-all whitespace-pre leading-relaxed border border-white/5 shadow-inner">
                  {{ xmlContent }}
                </div>
              </div>
            </template>

            <div v-else class="h-full flex flex-col items-center justify-center text-slate-500">
              <svg class="w-16 h-16 mb-4 opacity-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
              <p class="text-sm font-black uppercase tracking-wide">Error al cargar datos del comprobante</p>
            </div>
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
