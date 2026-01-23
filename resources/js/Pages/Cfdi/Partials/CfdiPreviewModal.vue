<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[70] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="emitClose"></div>

            <div class="relative bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full w-full h-[90vh] flex flex-col overflow-hidden animate-fadeIn">
                <!-- Header -->
                <div class="px-8 py-4 border-b border-gray-100 flex items-center justify-between bg-white dark:bg-slate-900/50">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-lg shadow-blue-600/20">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-black text-gray-900 dark:text-white leading-tight">Vista Previa CFDI</h2>
                            <span class="text-[10px] font-mono text-gray-400 select-all uppercase tracking-widest">{{ selectedUuid }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button v-if="parsedCfdiData" @click="downloadXml" class="px-4 h-10 flex items-center gap-2 text-xs font-black uppercase tracking-widest text-indigo-600 bg-indigo-50 hover:bg-indigo-600 hover:text-white rounded-xl transition-all border border-indigo-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" /></svg>
                            XML
                        </button>
                        <button v-if="parsedCfdiData" @click="downloadPdf" class="px-4 h-10 flex items-center gap-2 text-xs font-black uppercase tracking-widest text-emerald-600 bg-emerald-50 hover:bg-emerald-600 hover:text-white rounded-xl transition-all border border-emerald-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            PDF
                        </button>
                        <div class="w-[1px] h-8 bg-gray-200 mx-1"></div>
                        <button @click="emitClose" class="w-10 h-10 flex items-center justify-center text-gray-400 hover:text-gray-600 dark:text-gray-300 hover:bg-gray-100 rounded-xl transition-all">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="px-8 bg-white dark:bg-slate-900 border-b border-gray-100 flex gap-8">
                    <button v-for="tab in tabs" :key="tab.id" 
                            @click="activeTab = tab.id"
                            :class="['py-4 text-[11px] font-black uppercase tracking-[0.2em] transition-all relative', 
                                     activeTab === tab.id ? 'text-blue-600' : 'text-gray-400 hover:text-gray-600 dark:text-gray-300']">
                        {{ tab.label }}
                        <div v-if="activeTab === tab.id" class="absolute bottom-0 left-0 right-0 h-1 bg-blue-600 rounded-t-full"></div>
                    </button>
                </div>

                <div class="flex-1 overflow-hidden relative bg-white dark:bg-slate-900 flex flex-col p-8">
                    <div v-if="isLoadingXml" class="flex-1 flex flex-col items-center justify-center">
                        <svg class="animate-spin h-10 w-10 text-blue-600 mb-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <p class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Analizando comprobante...</p>
                    </div>

                    <template v-else-if="parsedCfdiData">
                        <!-- Tab: General Info -->
                        <div v-if="activeTab === 'info'" class="h-full overflow-y-auto custom-scrollbar pr-4 space-y-8">
                            <!-- Summary Cards -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="p-6 bg-blue-50 rounded-3xl border border-blue-100">
                                    <span class="text-[10px] font-black text-blue-400 uppercase tracking-widest block mb-2">Total Comprobante</span>
                                    <span class="text-3xl font-black text-blue-700 tracking-tight">{{ formatMoney(parsedCfdiData.total) }}</span>
                                    <span class="block mt-2 text-xs font-bold text-blue-500 uppercase">{{ parsedCfdiData.moneda }}</span>
                                </div>
                                <div class="p-6 bg-white dark:bg-slate-900 rounded-3xl border border-gray-200 dark:border-slate-800">
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Fecha Emisión</span>
                                    <span class="text-2xl font-black text-gray-800 dark:text-gray-100 tracking-tight">{{ formatDate(parsedCfdiData.fecha) }}</span>
                                </div>
                                <div class="p-6 bg-emerald-50 rounded-3xl border border-emerald-100 text-center">
                                    <span class="text-[10px] font-black text-emerald-400 uppercase tracking-widest block mb-2">Tipo de CFDI</span>
                                    <span class="px-4 py-1.5 bg-emerald-600 text-white text-[11px] font-black uppercase rounded-full tracking-widest inline-block mt-2">
                                        {{ getTipoLabel(parsedCfdiData.tipoComprobante) }}
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-8">
                                <!-- Emisor -->
                                <div class="space-y-4">
                                    <h3 class="text-sm font-black text-gray-700 uppercase tracking-widest flex items-center gap-2">
                                        <div class="w-1.5 h-4 bg-blue-600 rounded-full"></div>
                                        Datos del Emisor
                                    </h3>
                                    <div class="p-6 bg-white dark:bg-slate-900 border border-gray-100 rounded-3xl shadow-sm space-y-4">
                                        <div>
                                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block">Nombre o Razón Social</span>
                                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ parsedCfdiData.emisor.nombre }}</span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block">RFC</span>
                                                <span class="text-sm font-mono font-bold text-blue-600">{{ parsedCfdiData.emisor.rfc }}</span>
                                            </div>
                                            <div>
                                                <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block">Régimen Fiscal</span>
                                                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ parsedCfdiData.emisor.regimenFiscal }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Receptor -->
                                <div class="space-y-4">
                                    <h3 class="text-sm font-black text-gray-700 uppercase tracking-widest flex items-center gap-2">
                                        <div class="w-1.5 h-4 bg-violet-600 rounded-full"></div>
                                        Datos del Receptor
                                    </h3>
                                    <div class="p-6 bg-white dark:bg-slate-900 border border-gray-100 rounded-3xl shadow-sm space-y-4">
                                        <div>
                                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block">Nombre o Razón Social</span>
                                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ parsedCfdiData.receptor.nombre }}</span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block">RFC</span>
                                                <span class="text-sm font-mono font-bold text-violet-600">{{ parsedCfdiData.receptor.rfc }}</span>
                                            </div>
                                            <div>
                                                <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block">Uso CFDI</span>
                                                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ parsedCfdiData.receptor.usoCfdi }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab: Conceptos -->
                        <div v-if="activeTab === 'items'" class="h-full flex flex-col">
                            <div class="flex-1 overflow-y-auto custom-scrollbar pr-4 space-y-3">
                                <div v-for="(concepto, idx) in parsedCfdiData.conceptos" :key="idx" 
                                     class="p-5 bg-white dark:bg-slate-900 border border-gray-100 rounded-3xl shadow-sm hover:border-blue-200 transition-all flex justify-between items-center gap-6">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-3 mb-1">
                                            <span class="px-2 py-0.5 bg-gray-100 text-gray-500 dark:text-gray-400 text-[10px] font-black rounded uppercase">{{ concepto.clave }}</span>
                                            <p class="text-sm font-bold text-gray-900 dark:text-white whitespace-normal break-words leading-tight">{{ concepto.descripcion }}</p>
                                        </div>
                                        <div class="flex gap-4 text-[11px] font-bold text-gray-400">
                                            <span>CANT: {{ concepto.cantidad }}</span>
                                            <span>UNIT: {{ formatMoney(concepto.valorUnitario) }}</span>
                                            <span v-if="concepto.descuento > 0" class="text-red-400">DESC: {{ formatMoney(concepto.descuento) }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-sm font-black text-gray-900 dark:text-white italic tracking-tight">{{ formatMoney(concepto.importe) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab: Impuestos -->
                        <div v-if="activeTab === 'taxes'" class="h-full overflow-y-auto custom-scrollbar pr-4 space-y-8">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="space-y-4">
                                    <h3 class="text-sm font-black text-emerald-600 uppercase tracking-widest">Traslados</h3>
                                    <div v-if="parsedCfdiData.impuestos.traslados.length" class="space-y-3">
                                        <div v-for="(t, idx) in parsedCfdiData.impuestos.traslados" :key="idx" class="p-4 bg-emerald-50 border border-emerald-100 rounded-2xl flex justify-between items-center">
                                            <div class="flex flex-col">
                                                <span class="text-[9px] font-black text-emerald-400 uppercase">Impuesto {{ t.impuesto }} ({{ t.tasaOCuota * 100 }}%)</span>
                                                <span class="text-xs font-bold text-emerald-700">Base: {{ formatMoney(t.base) }}</span>
                                            </div>
                                            <span class="text-sm font-black text-emerald-600">{{ formatMoney(t.importe) }}</span>
                                        </div>
                                    </div>
                                    <p v-else class="text-xs font-bold text-gray-400 italic px-4">Sin traslados</p>
                                </div>

                                <div class="space-y-4">
                                    <h3 class="text-sm font-black text-red-600 uppercase tracking-widest">Retenciones</h3>
                                    <div v-if="parsedCfdiData.impuestos.retenciones.length" class="space-y-3">
                                        <div v-for="(r, idx) in parsedCfdiData.impuestos.retenciones" :key="idx" class="p-4 bg-red-50 border border-red-100 rounded-2xl flex justify-between items-center">
                                            <span class="text-[9px] font-black text-red-400 uppercase">Impuesto {{ r.impuesto }}</span>
                                            <span class="text-sm font-black text-red-600">{{ formatMoney(r.importe) }}</span>
                                        </div>
                                    </div>
                                    <p v-else class="text-xs font-bold text-gray-400 italic px-4">Sin retenciones</p>
                                </div>
                            </div>
                        </div>

                        <!-- Tab: XML -->
                        <div v-if="activeTab === 'xml'" class="h-full flex flex-col">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Código Fuente XML</span>
                                <button @click="copyXml" class="text-[10px] font-black text-blue-600 hover:text-blue-700 uppercase tracking-widest flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" /></svg>
                                    Copiar XML
                                </button>
                            </div>
                            <div class="flex-1 bg-gray-900 rounded-3xl p-6 overflow-auto custom-scrollbar font-mono text-xs text-blue-300 select-all whitespace-pre leading-relaxed">
                                {{ xmlContent }}
                            </div>
                        </div>
                    </template>

                    <div v-else class="flex-1 flex flex-col items-center justify-center text-gray-400">
                        <svg class="w-16 h-16 mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <p class="text-sm font-bold uppercase tracking-widest">No se pudo interpretar el archivo digital</p>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
    show: { type: Boolean, default: false },
    selectedUuid: { type: String, default: '' },
    xmlContent: { type: String, default: '' },
    parsedCfdiData: { type: Object, default: null },
    isLoadingXml: { type: Boolean, default: false },
    formatMoney: { type: Function, required: true }
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
    const blob = new Blob([props.xmlContent], { type: 'text/xml' })
    const link = document.createElement('a')
    link.href = window.URL.createObjectURL(blob)
    link.download = `${props.selectedUuid}.xml`
    link.click()
}

const downloadPdf = () => {
    window.open(route('cfdi.ver-pdf', props.selectedUuid), '_blank')
}
</script>

<style scoped>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fadeIn {
    animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #d1d5db;
}

.slide-up-enter-active, .slide-up-leave-active {
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-up-enter-from, .slide-up-leave-to {
    transform: translateY(100%);
    opacity: 0;
}
</style>
