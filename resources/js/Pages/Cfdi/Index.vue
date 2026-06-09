<script setup>
import ModalCrearCuentaCfdi from '@/Components/ModalCrearCuentaCfdi.vue'
import { ref, watch, onMounted, computed } from 'vue'
import { router, Head } from '@inertiajs/vue3'
import axios from 'axios'
import Swal from 'sweetalert2'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import CfdiPreviewModal from '@/Pages/Cfdi/Partials/CfdiPreviewModal.vue'
import CfdiSecondaryModals from '@/Pages/Cfdi/Partials/CfdiSecondaryModals.vue'

// Composables
import { useCfdiXmlParser } from '@/Composables/Cfdi/useCfdiXmlParser'
import { useCfdiFilters } from '@/Composables/Cfdi/useCfdiFilters'
import { useCfdiBulkActions } from '@/Composables/Cfdi/useCfdiBulkActions'
import { useCfdiDownloads } from '@/Composables/Cfdi/useCfdiDownloads'
import { useCfdiActions } from '@/Composables/Cfdi/useCfdiActions'

// Utilities
import { 
    formatMoney, 
    formatDateShort, 
    formatDateTime, 
    getTipoBadge, 
    getStatusBadgeClass,
    getTipoLabel,
    getTipoBadgeClass
} from '@/Utils/cfdiFormatting'

defineOptions({ layout: AppLayout })

const props = defineProps({
    cfdis: { type: Object, default: () => ({ data: [], links: [] }) },
    contadores: { type: Object, default: () => ({ total: 0, emitidos: 0, recibidos: 0 }) },
    filters: { type: Object, default: () => ({}) },
    descargasMasivas: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({ ingresos: 0, egresos: 0, pagos: 0, count: 0 }) }
})

const notyf = new Notyf({
    duration: 4000,
    position: { x: 'right', y: 'top' },
    types: [
        { type: 'success', background: '#10b981', icon: false },
        { type: 'error', background: '#ef4444', icon: false }
    ]
})

// Initialize Composables
const { parseCfdiXml } = useCfdiXmlParser()
const { 
    filters, 
    setQuickRange, 
    setCurrentMonthRange, 
    toggleSort, 
    handlePageChange,
    formatDateInput,
    getSortIndicator
} = useCfdiFilters(props.filters)

const cfdiItems = computed(() => props.cfdis.data || [])

const {
    selectedIds,
    isBulkProcessing,
    isBulkDownloading,
    toggleSelectAll,
    toggleSelect,
    isSelected,
    bulkCheckSat,
    bulkSendEmail,
    bulkDownloadZip
} = useCfdiBulkActions(cfdiItems, notyf)

const {
    descargaForm,
    descargaSending,
    descargasItems,
    isDeletingDescarga,
    documentosStaging,
    duplicadosStaging,
    selectedStagingIds,
    selectedDescargaParaReview,
    isLoadingReview,
    isImportingSeleccionados,
    solicitarDescarga: rawSolicitarDescarga,
    verificarDescarga,
    eliminarDescarga,
    reintentarDescargaManual,
    abrirRevisor: rawAbrirRevisor,
    toggleSeleccionStaging,
    importarSeleccionados: rawImportarSeleccionados
} = useCfdiDownloads(props, notyf)

const {
    isCheckingSat,
    isCreatingProvider,
    isSendingEmail,
    isDeletingCfdi,
    satStatus,
    selectedUuid,
    xmlContent,
    isLoadingXml,
    parsedCfdiData,
    checkSatStatus,
    createProvider,
    deleteCfdi: rawDeleteCfdi,
    enviarCorreo,
    fetchXml
} = useCfdiActions(notyf, parseCfdiXml)

// UI State Control
const showAdvancedFilters = ref(false)
const showUploadModal = ref(false)
const showDescargaModal = ref(false)
const showReviewModal = ref(false)
const showDeleteConfirmModal = ref(false)
const showPreviewModal = ref(false)
const showModalCrearCuenta = ref(false)

const cfdiParaCrearCuenta = ref(null)
const cfdiParaEliminar = ref(null)
const isDragging = ref(false)
const isUploading = ref(false)
const uploadPreview = ref(null)
const selectedFile = ref(null)

const expandedRows = ref([])
const toggleRow = (id) => {
    const index = expandedRows.value.indexOf(id)
    if (index > -1) expandedRows.value.splice(index, 1)
    else expandedRows.value.push(id)
}
const isExpanded = (id) => expandedRows.value.includes(id)

// Local Methods
const setDireccion = (dir) => { filters.value.direccion = dir }
const formatCurrency = formatMoney // alias for template consistency

// Modal Handlers
const abrirModalCrearCuenta = (cfdi) => {
    cfdiParaCrearCuenta.value = cfdi
    showModalCrearCuenta.value = true
}

const onCuentaCreated = () => {
    router.reload({ only: ['cfdis'] })
}

const solicitarDescarga = async () => {
    if (await rawSolicitarDescarga()) showDescargaModal.value = false
}

const abrirRevisor = async (descarga) => {
    if (await rawAbrirRevisor(descarga)) showReviewModal.value = true
}

const importarSeleccionados = async () => {
    if (await rawImportarSeleccionados()) showReviewModal.value = false
}

const seleccionarTodoStaging = () => {
    selectedStagingIds.value = documentosStaging.value.map(d => d.id)
}

const deseleccionarTodoStaging = () => {
    selectedStagingIds.value = []
}

const confirmarEliminacion = (cfdi) => {
    cfdiParaEliminar.value = cfdi
    showDeleteConfirmModal.value = true
}

const ejecutarEliminacion = async () => {
    if (await rawDeleteCfdi(cfdiParaEliminar.value.id)) {
        showDeleteConfirmModal.value = false
        cfdiParaEliminar.value = null
    }
}

const verXml = async (uuid) => {
    if (await fetchXml(uuid)) showPreviewModal.value = true
}

/** Abre el visor HTML del PDF (misma ventana de lectura). */
const abrirVisorPdf = (uuid) => {
    window.open(route('cfdi.ver-pdf-view', uuid), '_blank', 'noopener,noreferrer')
}

/** Descarga XML con nombre amigable (Content-Disposition del servidor). */
const descargarXmlCfdi = (uuid) => {
    window.open(route('cfdi.xml', uuid), '_blank', 'noopener,noreferrer')
}

/** Descarga PDF con nombre amigable. */
const descargarPdfCfdi = (uuid) => {
    window.open(route('cfdi.ver-pdf', { uuid, download: 1 }), '_blank', 'noopener,noreferrer')
}

const verXmlStaging = (doc) => {
    selectedUuid.value = doc.uuid
    showPreviewModal.value = true
    xmlContent.value = doc.xml_content
    parsedCfdiData.value = parseCfdiXml(xmlContent.value)
}

/** En revisor SAT: abrir visor PDF (antes abría la vista XML por error). */
const verPdfStaging = (doc) => {
    if (doc?.uuid) abrirVisorPdf(doc.uuid)
}

const handleDragOver = (e) => {
    e.preventDefault()
    isDragging.value = true
}

const handleDragLeave = () => {
    isDragging.value = false
}

const handleDrop = async (e) => {
    e.preventDefault()
    isDragging.value = false
    const file = e.dataTransfer.files[0]
    if (file && file.name.endsWith('.xml')) await previewXmlFile(file)
    else notyf.error('Por favor selecciona un archivo XML')
}

const handleFileSelect = async (e) => {
    const file = e.target.files[0]
    if (file) await previewXmlFile(file)
}

const previewXmlFile = async (file) => {
    selectedFile.value = file
    isUploading.value = true
    try {
        const formData = new FormData()
        formData.append('xml_file', file)
        const response = await axios.post(route('cfdi.preview-xml'), formData)
        if (response.data.success) uploadPreview.value = response.data.data
        else {
            notyf.error(response.data.message)
            selectedFile.value = null
        }
    } catch (e) {
        notyf.error('Error al procesar XML')
        selectedFile.value = null
    } finally {
        isUploading.value = false
    }
}

const uploadXml = async () => {
    if (!selectedFile.value) return
    isUploading.value = true
    const formData = new FormData()
    formData.append('xml_file', selectedFile.value)
    try {
        const response = await axios.post(route('cfdi.store'), formData)
        if (response.data.success) {
            notyf.success('CFDI cargado correctamente')
            showUploadModal.value = false
            resetUpload()
            router.reload()
        }
    } finally {
        isUploading.value = false
    }
}

const resetUpload = () => {
    selectedFile.value = null
    uploadPreview.value = null
}

const closeUploadModal = () => {
    showUploadModal.value = false
    resetUpload()
}

const previousDescargaStatuses = ref({})
watch(() => props.descargasMasivas, (newDescargas) => {
    newDescargas?.forEach(descarga => {
        const oldStatus = previousDescargaStatuses.value[descarga.id]
        if (oldStatus && oldStatus !== descarga.status && ['finished', 'completado', 'error'].includes(descarga.status)) {
            mostrarResumenDescarga(descarga)
        }
        previousDescargaStatuses.value[descarga.id] = descarga.status
    })
}, { deep: true })

const mostrarResumenDescarga = (descarga) => {
    const total = descarga.total_cfdis || 0
    const nuevos = descarga.imported_cfdis || descarga.inserted_cfdis || 0
    const pendientes = descarga.pending_cfdis || 0
    
    let html = `
        <div class="text-left">
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div class="bg-white rounded-lg p-3 text-center border">
                    <div class="text-2xl font-bold text-gray-800">${total}</div>
                    <div class="text-xs text-gray-500 uppercase tracking-tighter">Total Procesados</div>
                </div>
                <div class="bg-emerald-50 rounded-lg p-3 text-center border border-emerald-100">
                    <div class="text-2xl font-bold text-emerald-600">${nuevos}</div>
                    <div class="text-xs text-emerald-600 uppercase tracking-tighter">Nuevos en ADD</div>
                </div>
            </div>
            ${pendientes > 0 ? `<p class="text-sm text-orange-600 font-medium">📋 <b>${pendientes}</b> documentos pendientes de revisión.</p>` : ''}
        </div>`
    
    Swal.fire({
        title: 'Descarga Masiva Finalizada',
        html: html,
        icon: 'success',
        confirmButtonText: pendientes > 0 ? 'Ir al Revisor' : 'Entendido',
        confirmButtonColor: pendientes > 0 ? '#10b981' : '#3b82f6',
        showCancelButton: pendientes > 0,
        cancelButtonText: 'Cerrar'
    }).then(result => {
        if (result.isConfirmed && pendientes > 0) abrirRevisor(descarga)
    })
}
</script>

<template>
    <Head title="ADD - Administrador de Documentos Digitales" />

    <div class="py-12 bg-white min-h-screen transition-colors duration-500">
        <div class="max-w-none w-full mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Header -->
            <div class="mb-6 flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <h1 class="text-4xl font-black text-gray-900 tracking-tight mb-2">ADD</h1>
                    <p class="text-sm text-gray-500 font-bold uppercase tracking-[0.2em] flex items-center gap-2">
                        <span class="w-8 h-[2px] bg-blue-600"></span>
                        Administrador de Documentos Digitales
                    </p>
                </div>
                
                <div class="flex items-center gap-3">


                    <!-- Botón Cargar XML -->
                    <button @click="showUploadModal = true" 
                            class="px-5 py-3 bg-violet-600 hover:bg-violet-700 text-white rounded-2xl font-bold text-sm flex items-center gap-2 transition-all shadow-lg shadow-violet-600/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                        Cargar XML
                    </button>
                    <button @click="showDescargaModal = true"
                            class="px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-bold text-sm flex items-center gap-2 transition-all shadow-lg shadow-emerald-600/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" /></svg>
                        Descarga masiva SAT
                    </button>
                    
                    <div class="px-6 py-3 bg-white rounded-2xl border border-gray-200 shadow-sm flex items-center gap-4 transition-all">
                        <div class="text-right">
                            <p class="text-[13px] font-black text-gray-400 uppercase tracking-widest">Total Documentos</p>
                            <p class="text-lg font-black text-gray-900 italic tabular-nums">{{ cfdis.total }}</p>
                        </div>
                        <div class="w-[1px] h-8 bg-gray-100"></div>
                        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Dashboard -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-gray-100 relative overflow-hidden group hover:-translate-y-1 transition-transform duration-300">
                    <div class="absolute right-0 top-0 w-32 h-32 bg-emerald-50 rounded-full -mr-16 -mt-16 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10">
                        <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Ingresos</p>
                        <h3 class="text-2xl font-black text-gray-900 tracking-tight">{{ formatCurrency(stats.ingresos) }}</h3>
                        <p class="text-[10px] font-bold text-emerald-500 mt-2 flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Facturas Emitidas
                        </p>
                    </div>
                </div>

                <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-gray-100 relative overflow-hidden group hover:-translate-y-1 transition-transform duration-300">
                    <div class="absolute right-0 top-0 w-32 h-32 bg-rose-50 rounded-full -mr-16 -mt-16 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10">
                        <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Egresos</p>
                        <h3 class="text-2xl font-black text-gray-900 tracking-tight">{{ formatCurrency(stats.egresos) }}</h3>
                        <p class="text-[10px] font-bold text-rose-500 mt-2 flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            Notas Crédito / Gastos
                        </p>
                    </div>
                </div>

                <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-gray-100 relative overflow-hidden group hover:-translate-y-1 transition-transform duration-300">
                    <div class="absolute right-0 top-0 w-32 h-32 bg-blue-50 rounded-full -mr-16 -mt-16 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10">
                        <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Pagos</p>
                        <h3 class="text-2xl font-black text-gray-900 tracking-tight">{{ formatCurrency(stats.pagos) }}</h3>
                        <p class="text-[10px] font-bold text-blue-500 mt-2 flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            Complementos Pago
                        </p>
                    </div>
                </div>

                <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-gray-100 relative overflow-hidden group hover:-translate-y-1 transition-transform duration-300">
                    <div class="absolute right-0 top-0 w-32 h-32 bg-gray-100 rounded-full -mr-16 -mt-16 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10">
                        <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Total Visibles</p>
                        <h3 class="text-2xl font-black text-gray-900 tracking-tight">{{ stats.count }}</h3>
                        <p class="text-[10px] font-bold text-gray-500 mt-2 flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                            Documentos Filtrados
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tabs de Dirección -->
            <div class="flex gap-2 mb-6">
                <button @click="setDireccion('')" 
                        :class="['px-5 py-2.5 rounded-xl font-bold text-sm transition-all border', 
                                 filters.direccion === '' ? 'bg-gray-900 text-white shadow-lg shadow-blue-500/20' : 'bg-white text-gray-600 hover:bg-white border-gray-200']">
                    Todos
                    <span class="ml-2 px-2 py-0.5 rounded-lg text-xs" :class="filters.direccion === '' ? 'bg-white/20' : 'bg-gray-100'">{{ contadores.total }}</span>
                </button>
                <button @click="setDireccion('emitido')" 
                        :class="['px-5 py-2.5 rounded-xl font-bold text-sm transition-all flex items-center gap-2 border', 
                                 filters.direccion === 'emitido' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/20' : 'bg-white text-gray-600 hover:bg-white border-gray-200']">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                    Emitidos
                    <span class="px-2 py-0.5 rounded-lg text-xs" :class="filters.direccion === 'emitido' ? 'bg-white/20' : 'bg-gray-100'">{{ contadores.emitidos }}</span>
                </button>
                <button @click="setDireccion('recibido')" 
                        :class="['px-5 py-2.5 rounded-xl font-bold text-sm transition-all flex items-center gap-2 border', 
                                 filters.direccion === 'recibido' ? 'bg-violet-600 text-white shadow-lg shadow-violet-600/20' : 'bg-white text-gray-600 hover:bg-white border-gray-200']">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                    Recibidos
                    <span class="px-2 py-0.5 rounded-lg text-xs" :class="filters.direccion === 'recibido' ? 'bg-white/20' : 'bg-gray-100'">{{ contadores.recibidos }}</span>
                </button>
            </div>

            <!-- Descargas masivas -->
            <div v-if="descargasItems.length" class="mb-6 bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-black text-gray-700 uppercase tracking-[0.2em]">Descargas Masivas SAT</h3>
                    <button @click="router.reload({ preserveState: true })" class="text-xs text-gray-400 hover:text-gray-600 font-bold uppercase tracking-widest">
                        Actualizar
                    </button>
                </div>
                <div class="grid gap-3">
                    <div v-for="descarga in descargasItems" :key="descarga.id" 
                      :class="['flex flex-col gap-4 p-5 rounded-2xl border-2 transition-all duration-300', 
                               ['solicitando', 'pendiente', 'verificando', 'descargando'].includes(descarga.status) 
                                 ? 'bg-gradient-to-r from-blue-50 to-indigo-50 border-blue-200 shadow-lg shadow-blue-100' 
                                 : descarga.status === 'esperando' 
                                     ? 'bg-gradient-to-r from-amber-50 to-orange-50 border-amber-300 shadow-lg shadow-amber-100'
                                     : descarga.status === 'pausado'
                                         ? 'bg-gradient-to-r from-red-50 to-rose-50 border-red-300'
                                         : 'bg-white border-gray-100']">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                            <div class="flex flex-col">
                                <div class="flex items-center gap-2">
                                    <span :class="['px-3 py-1 rounded-lg text-xs font-black uppercase tracking-widest',
                                                   descarga.direccion === 'emitido' ? 'bg-emerald-100 text-emerald-700' : 'bg-violet-100 text-violet-700']">
                                        {{ descarga.direccion === 'emitido' ? '↑ Emitidos' : '↓ Recibidos' }}
                                    </span>
                                    <span :class="['px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider',
                                                   ['completado', 'finished', 'completo'].includes(descarga.status) ? 'bg-emerald-500 text-white' :
                                                   descarga.status === 'error' ? 'bg-red-100 text-red-700' :
                                                   descarga.status === 'esperando' ? 'bg-amber-500 text-white animate-pulse' :
                                                   'bg-blue-100 text-blue-700 animate-pulse']">
                                        {{ descarga.status }}
                                    </span>
                                </div>
                                <span class="text-sm text-gray-600 mt-1">
                                    📅 {{ formatDateShort(descarga.fecha_inicio) }} → {{ formatDateShort(descarga.fecha_fin) }}
                                </span>
                            </div>
                            <div v-if="['solicitando', 'pendiente', 'verificando', 'descargando'].includes(descarga.status)" class="flex items-center gap-2 text-blue-600">
                                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                <span class="text-xs font-bold animate-pulse">Procesando...</span>
                            </div>
                        </div>

                        <div v-if="descarga.total_cfdis > 0" class="w-full">
                            <div class="w-full h-2.5 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500 rounded-full transition-all duration-500"
                                     :style="{ width: Math.min(100, ((descarga.inserted_cfdis + descarga.duplicate_cfdis) / descarga.total_cfdis) * 100) + '%' }"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div class="bg-gray-50 rounded-xl p-3 border border-gray-100 text-center">
                                <div class="text-xl font-bold text-gray-800">{{ descarga.total_cfdis || 0 }}</div>
                                <div class="text-[9px] font-bold text-gray-400 uppercase">Total</div>
                            </div>
                             <div class="bg-emerald-50 rounded-xl p-3 border border-emerald-100 text-center">
                                <div class="text-xl font-bold text-emerald-600">{{ descarga.inserted_cfdis || 0 }}</div>
                                <div class="text-[9px] font-bold text-emerald-500 uppercase">Nuevos</div>
                            </div>
                            <div class="bg-orange-50 rounded-xl p-3 border border-orange-100 text-center">
                                <div class="text-xl font-bold text-orange-600">{{ descarga.pending_cfdis || 0 }}</div>
                                <div class="text-[9px] font-bold text-orange-500 uppercase">Pendientes</div>
                            </div>
                            <div class="bg-red-50 rounded-xl p-3 border border-red-100 text-center text-red-600">
                                <div class="text-xl font-bold">{{ descarga.error_cfdis || 0 }}</div>
                                <div class="text-[9px] font-bold uppercase">Errores</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button v-if="descarga.status === 'pendiente' || descarga.status === 'verificando'"
                                    @click="verificarDescarga(descarga.id)"
                                    class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-bold hover:bg-blue-700 transition-colors">
                                Consultar
                            </button>
                            <button v-if="['finished', 'error', 'completo', 'completado'].includes(descarga.status)"
                                    @click="abrirRevisor(descarga)"
                                    class="px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-xs font-bold hover:bg-emerald-700 transition-colors">
                                Revisar Docs
                            </button>
                            <button @click="eliminarDescarga(descarga.id)"
                                    :disabled="isDeletingDescarga[descarga.id]"
                                    class="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-600 hover:text-white transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros Avanzados -->
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-200 p-8 mb-10 transition-all">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                    <div class="space-y-2">
                        <label class="text-[14px] font-black text-gray-400 uppercase tracking-[0.2em] pl-1">Búsqueda Inteligente</label>
                        <div class="relative group">
                            <input v-model="filters.search" placeholder="Folio, UUID..." 
                                   class="w-full h-14 pl-12 pr-4 bg-white border-2 border-transparent rounded-2xl text-sm font-bold focus:bg-white focus:border-blue-500 focus:ring-0 transition-all placeholder:text-gray-400" />
                            <svg class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[14px] font-black text-gray-400 uppercase tracking-[0.2em] pl-1">Tipo Documento</label>
                        <select v-model="filters.tipo_comprobante" class="w-full h-14 px-5 bg-white border-2 border-transparent rounded-2xl text-sm font-bold focus:bg-white focus:border-blue-500 focus:ring-0 transition-all appearance-none">
                            <option value="">Cualquier Tipo</option>
                            <option value="I">Factura (Ingreso)</option>
                            <option value="P">Pago (Complemento)</option>
                            <option value="E">Egreso (N. Crédito)</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[14px] font-black text-gray-400 uppercase tracking-[0.2em] pl-1">Estado Fiscal</label>
                        <select v-model="filters.estatus" class="w-full h-14 px-5 bg-white border-2 border-transparent rounded-2xl text-sm font-bold focus:bg-white focus:border-blue-500 focus:ring-0 transition-all appearance-none">
                            <option value="">Todos los Estados</option>
                            <option value="vigente">Vigente / Timbrado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[14px] font-black text-gray-400 uppercase tracking-[0.2em] pl-1">Rango de Fecha</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="date" v-model="filters.fecha_inicio" class="w-full h-14 px-3 bg-white border-2 border-transparent rounded-2xl text-[11px] font-bold focus:bg-white focus:border-blue-100 focus:ring-0 transition-all" />
                            <input type="date" v-model="filters.fecha_fin" class="w-full h-14 px-3 bg-white border-2 border-transparent rounded-2xl text-[11px] font-bold focus:bg-white focus:border-blue-100 focus:ring-0 transition-all" />
                        </div>
                    </div>

                    <div class="flex items-end pb-1">
                        <button @click="Object.keys(filters).forEach(k => filters[k] = k === 'sort_dir' ? 'desc' : '')" 
                                class="w-full h-14 text-[10px] font-black text-gray-400 hover:text-red-500 uppercase tracking-[0.2em] transition-all flex items-center justify-center gap-2 hover:bg-red-50 rounded-2xl">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            Borrar Filtros
                        </button>
                    </div>

                    <div class="flex items-end pb-1 lg:col-span-5 border-t border-gray-100 pt-6 mt-2">
                        <button @click="showAdvancedFilters = !showAdvancedFilters" class="flex items-center gap-2 text-xs font-black text-blue-600 uppercase tracking-widest hover:text-blue-700 transition-colors">
                            <svg class="w-4 h-4 transition-transform duration-300" :class="showAdvancedFilters ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            {{ showAdvancedFilters ? 'Menos Filtros' : 'Filtros Avanzados' }}
                        </button>
                    </div>

                    <template v-if="showAdvancedFilters">
                        <div class="space-y-2 lg:col-span-2">
                            <label class="text-[14px] font-black text-gray-400 uppercase tracking-[0.2em] pl-1">RFC Emisor</label>
                            <input v-model="filters.rfc_emisor" placeholder="AAA010101AAA" 
                                   class="w-full h-14 px-4 bg-white border-2 border-transparent rounded-2xl text-sm font-bold focus:bg-white focus:border-blue-500 focus:ring-0 transition-all uppercase placeholder:normal-case" />
                        </div>
                        <div class="space-y-2 lg:col-span-2">
                            <label class="text-[14px] font-black text-gray-400 uppercase tracking-[0.2em] pl-1">RFC Receptor</label>
                            <input v-model="filters.rfc_receptor" placeholder="AAA010101AAA" 
                                   class="w-full h-14 px-4 bg-white border-2 border-transparent rounded-2xl text-sm font-bold focus:bg-white focus:border-blue-500 focus:ring-0 transition-all uppercase placeholder:normal-case" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-[14px] font-black text-gray-400 uppercase tracking-[0.2em] pl-1">Serie</label>
                            <input v-model="filters.serie" placeholder="A" 
                                   class="w-full h-14 px-4 bg-white border-2 border-transparent rounded-2xl text-sm font-bold focus:bg-white focus:border-blue-500 focus:ring-0 transition-all" />
                        </div>
                    </template>
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-200 transition-all overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-white/50 border-b border-gray-200">
                                <th class="px-4 py-6 text-center">
                                    <input type="checkbox" :checked="selectedIds.length === cfdiItems.length && cfdiItems.length > 0" @change="toggleSelectAll" class="w-5 h-5 rounded-lg border-gray-300 text-blue-600 focus:ring-blue-500 transition-all cursor-pointer" />
                                </th>
                                <th class="px-8 py-6 text-[14px] font-black text-gray-400 uppercase tracking-[0.2em]">
                                    <button @click="toggleSort('folio')" class="inline-flex items-center gap-2 hover:text-gray-700">
                                        Folio & Fiscal
                                        <span class="text-[9px] font-black text-gray-300">{{ getSortIndicator('folio') }}</span>
                                    </button>
                                </th>
                                <th class="px-8 py-6 text-[14px] font-black text-gray-400 uppercase tracking-[0.2em]">
                                    <button @click="toggleSort('nombre')" class="inline-flex items-center gap-2 hover:text-gray-700">
                                        Emisor / Receptor
                                        <span class="text-[9px] font-black text-gray-300">{{ getSortIndicator('nombre') }}</span>
                                    </button>
                                </th>
                                <th class="px-8 py-6 text-[14px] font-black text-gray-400 uppercase tracking-[0.2em]">
                                    <button @click="toggleSort('fecha_emision')" class="inline-flex items-center gap-2 hover:text-gray-700">
                                        Temporalidad
                                        <span class="text-[9px] font-black text-gray-300">{{ getSortIndicator('fecha_emision') }}</span>
                                    </button>
                                </th>
                                <th class="px-8 py-6 text-[14px] font-black text-gray-400 uppercase tracking-[0.2em] text-right">
                                    <button @click="toggleSort('total')" class="inline-flex items-center gap-2 hover:text-gray-700">
                                        Monto Total
                                        <span class="text-[9px] font-black text-gray-300">{{ getSortIndicator('total') }}</span>
                                    </button>
                                </th>
                                <th class="px-8 py-6 text-[14px] font-black text-gray-400 uppercase tracking-[0.2em] text-center">
                                    <button @click="toggleSort('estatus')" class="inline-flex items-center gap-2 hover:text-gray-700">
                                        Estatus
                                        <span class="text-[9px] font-black text-gray-300">{{ getSortIndicator('estatus') }}</span>
                                    </button>
                                </th>
                                <th class="px-8 py-6 text-[14px] font-black text-gray-400 uppercase tracking-[0.2em] text-right">Gestión</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template v-for="cfdi in cfdiItems" :key="cfdi.id">
                                <tr :class="['group hover:bg-blue-50/80 transition-all duration-300 cursor-pointer', isSelected(cfdi.id) ? 'bg-blue-50/50' : '', isExpanded(cfdi.id) ? 'bg-indigo-50/50' : '']"
                                    @click="toggleRow(cfdi.id)">
                                    <td class="px-4 py-6 text-center" @click.stop>
                                        <input type="checkbox" :checked="isSelected(cfdi.id)" @change="toggleSelect(cfdi.id)" class="w-5 h-5 rounded-lg border-gray-300 text-blue-600 focus:ring-blue-500 transition-all cursor-pointer" />
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="flex flex-col gap-1.5">
                                            <div class="flex items-center gap-2">
                                                <span class="text-lg font-black text-gray-900 tracking-tight">{{ cfdi.folio }}</span>
                                                <span :class="['px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider border', getTipoBadgeClass(cfdi.tipo_comprobante)]">
                                                    {{ getTipoLabel(cfdi.tipo_comprobante) }}
                                                </span>
                                            </div>
                                            <span class="text-[11px] font-mono text-gray-400 truncate max-w-[120px]" :title="cfdi.uuid">{{ cfdi.uuid }}</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="flex flex-col gap-1">
                                            <template v-if="cfdi.direccion === 'emitido'">
                                                <span class="text-[9px] font-black text-blue-600 uppercase">Receptor:</span>
                                                <span class="text-xs font-bold text-gray-900 truncate max-w-[180px]">{{ cfdi.receptor }}</span>
                                                <span class="text-[11px] font-mono text-gray-400">{{ cfdi.rfc_receptor }}</span>
                                            </template>
                                            <template v-else>
                                                <span class="text-[9px] font-black text-emerald-600 uppercase">Emisor:</span>
                                                <span class="text-xs font-bold text-gray-900 truncate max-w-[180px]">{{ cfdi.emisor }}</span>
                                                <span class="text-[11px] font-mono text-gray-400">{{ cfdi.rfc_emisor }}</span>
                                            </template>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="flex flex-col">
                                            <span class="text-[13px] font-black text-gray-400 uppercase tracking-widest">Emisión</span>
                                            <span class="text-base font-bold text-gray-700">{{ cfdi.fecha }}</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <span class="text-sm font-black text-emerald-600 tracking-tight tabular-nums">{{ formatMoney(cfdi.total) }}</span>
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                         <div :class="['inline-flex px-2 py-1 rounded-lg text-[8px] font-black uppercase tracking-widest border', getStatusBadgeClass(cfdi.estado_sat)]">
                                            {{ cfdi.estado_sat || 'Vigente' }}
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="flex flex-wrap items-center justify-end gap-1" @click.stop title="Descargas y vista">
                                            <button type="button" title="Vista previa (XML en panel)" @click="verXml(cfdi.uuid)" class="w-8 h-8 flex items-center justify-center bg-slate-50 text-slate-600 rounded-lg hover:bg-indigo-600 hover:text-white transition-all">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            </button>
                                            <button type="button" title="Ver PDF en el navegador" @click="abrirVisorPdf(cfdi.uuid)" class="w-8 h-8 flex items-center justify-center bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-600 hover:text-white transition-all">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                            </button>
                                            <button type="button" title="Descargar XML" @click="descargarXmlCfdi(cfdi.uuid)" class="w-8 h-8 flex items-center justify-center bg-indigo-50 text-indigo-600 rounded-lg hover:bg-indigo-600 hover:text-white transition-all">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                            </button>
                                            <button type="button" title="Descargar PDF" @click="descargarPdfCfdi(cfdi.uuid)" class="w-8 h-8 flex items-center justify-center bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-600 hover:text-white transition-all">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                            </button>
                                            <button type="button" @click="abrirModalCrearCuenta(cfdi)" title="Crear cuenta (proveedor)" class="w-8 h-8 flex items-center justify-center bg-teal-50 text-teal-600 rounded-lg hover:bg-teal-600 hover:text-white transition-all">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                                            </button>
                                            <button type="button" @click="confirmarEliminacion(cfdi)" v-if="!cfdi.venta_id" title="Eliminar CFDI" class="w-8 h-8 flex items-center justify-center bg-red-50 text-red-600 rounded-lg hover:bg-red-600 hover:text-white transition-all">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="isExpanded(cfdi.id)">
                                    <td colspan="7" class="px-8 py-6 bg-gray-50/50 border-t border-gray-100">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 animate-fadeIn">
                                            <div class="space-y-4">
                                                <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
                                                    <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Emisor</h4>
                                                    <p class="text-sm font-bold">{{ cfdi.emisor }}</p>
                                                    <p class="text-xs text-gray-500 font-mono">{{ cfdi.rfc_emisor }}</p>
                                                </div>
                                                <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
                                                    <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Receptor</h4>
                                                    <p class="text-sm font-bold">{{ cfdi.receptor }}</p>
                                                    <p class="text-xs text-gray-500 font-mono">{{ cfdi.rfc_receptor }}</p>
                                                </div>
                                            </div>
                                            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm md:col-span-2">
                                                <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Conceptos</h4>
                                                <div class="max-h-48 overflow-y-auto pr-2 custom-scrollbar space-y-2">
                                                    <div v-for="concept in cfdi.conceptos" :key="concept.id" class="flex justify-between items-center py-2 border-b last:border-0 border-gray-50">
                                                        <div class="flex-1 mr-4">
                                                            <p class="text-xs font-bold truncate">{{ concept.descripcion }}</p>
                                                            <p class="text-[10px] text-gray-400">{{ concept.cantidad }} {{ concept.clave_unidad }} - ${{ Number(concept.valor_unitario).toFixed(2) }}</p>
                                                        </div>
                                                        <span class="text-sm font-black text-gray-700">${{ Number(concept.importe).toFixed(2) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <Transition name="slide-up">
                    <div v-if="selectedIds.length > 0" class="fixed bottom-8 left-1/2 -translate-x-1/2 z-50">
                        <div class="bg-gray-900/95 backdrop-blur-md text-white px-8 py-4 rounded-[2rem] shadow-2xl flex items-center gap-8 border border-white/10">
                            <div class="flex flex-col">
                                <span class="text-xs font-black text-blue-400 uppercase tracking-widest">{{ selectedIds.length }} seleccionados</span>
                                <span class="text-[10px] text-gray-400 font-bold">CFDIs Marcados</span>
                            </div>
                            <div class="w-[1px] h-8 bg-white/10"></div>
                            <div class="flex items-center gap-3">
                                <button @click="bulkCheckSat" :disabled="isBulkProcessing" class="px-5 py-2.5 bg-white/10 hover:bg-white/20 rounded-xl text-xs font-black uppercase transition-all flex items-center gap-2">
                                    Consultar SAT
                                </button>
                                <button @click="bulkSendEmail" :disabled="isBulkProcessing" class="px-5 py-2.5 bg-white/10 hover:bg-white/20 rounded-xl text-xs font-black uppercase transition-all flex items-center gap-2">
                                    Email
                                </button>
                                <button @click="bulkDownloadZip" :disabled="isBulkDownloading" class="px-5 py-2.5 bg-emerald-500/20 text-emerald-400 rounded-xl text-xs font-black uppercase transition-all flex items-center gap-2">
                                    <svg v-if="isBulkDownloading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    ZIP
                                </button>
                                <button @click="selectedIds = []" class="px-5 py-2.5 bg-red-500/10 text-red-400 rounded-xl text-xs font-black uppercase transition-all">
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>

                <div class="flex items-center justify-center py-4">
                    <Pagination :pagination-data="cfdis" @page-change="handlePageChange" />
                </div>

                <div v-if="cfdiItems.length === 0" class="p-20 text-center">
                    <h3 class="text-xl font-black text-gray-400 uppercase tracking-widest">Sin Documentos</h3>
                    <p class="text-sm text-gray-300 font-bold mt-2">No se encontraron resultados para los filtros actuales.</p>
                </div>
            </div>
        </div>
    </div>

    <CfdiSecondaryModals
        :show-descarga-modal="showDescargaModal"
        :descarga-form="descargaForm"
        :set-quick-range="setQuickRange"
        :set-current-month-range="setCurrentMonthRange"
        :solicitar-descarga="solicitarDescarga"
        :descarga-sending="descargaSending"
        :on-close-descarga="() => (showDescargaModal = false)"
        :show-upload-modal="showUploadModal"
        :on-close-upload="closeUploadModal"
        :is-dragging="isDragging"
        :is-uploading="isUploading"
        :upload-preview="uploadPreview"
        :is-creating-provider="isCreatingProvider"
        :create-provider="createProvider"
        :handle-drag-over="handleDragOver"
        :handle-drag-leave="handleDragLeave"
        :handle-drop="handleDrop"
        :handle-file-select="handleFileSelect"
        :on-reset-upload="resetUpload"
        :upload-xml="uploadXml"
        :show-review-modal="showReviewModal"
        :on-close-review="() => (showReviewModal = false)"
        :is-loading-review="isLoadingReview"
        :documentos-staging="documentosStaging"
        :duplicados-staging="duplicadosStaging"
        :selected-staging-ids="selectedStagingIds"
        :toggle-seleccion-staging="toggleSeleccionStaging"
        :seleccionar-todo-staging="seleccionarTodoStaging"
        :deseleccionar-todo-staging="deseleccionarTodoStaging"
        :format-date-short="formatDateShort"
        :format-currency="formatMoney"
        :get-tipo-badge="getTipoBadge"
        :ver-pdf-staging="verPdfStaging"
        :ver-xml-staging="verXmlStaging"
        :ver-pdf="abrirVisorPdf"
        :ver-xml="verXml"
        :abrir-modal-crear-cuenta="abrirModalCrearCuenta"
        :is-importing-seleccionados="isImportingSeleccionados"
        :importar-seleccionados="importarSeleccionados"
        :show-delete-confirm-modal="showDeleteConfirmModal"
        :on-close-delete="() => (showDeleteConfirmModal = false)"
        :cfdi-para-eliminar="cfdiParaEliminar"
        :is-deleting-cfdi="isDeletingCfdi"
        :ejecutar-eliminacion="ejecutarEliminacion"
    />

    <CfdiPreviewModal
        :show="showPreviewModal"
        :selected-uuid="selectedUuid"
        :xml-content="xmlContent"
        :parsed-cfdi-data="parsedCfdiData"
        :is-loading-xml="isLoadingXml"
        :format-money="formatMoney"
        @close="showPreviewModal = false"
        @copied="notyf.success('XML copiado')"
    />

    <ModalCrearCuentaCfdi 
        :show="showModalCrearCuenta"
        :cfdi="cfdiParaCrearCuenta"
        @close="showModalCrearCuenta = false"
        @created="onCuentaCreated"
    />
</template>

<style scoped>
@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
.animate-fadeIn { animation: fadeIn 0.3s ease-out forwards; }
.custom-scrollbar::-webkit-scrollbar { width: 8px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f9fafb; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #d1d5db; }

.slide-up-enter-active, .slide-up-leave-active { transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
.slide-up-enter-from, .slide-up-leave-to { transform: translate(-50%, 100px); opacity: 0; }
</style>
