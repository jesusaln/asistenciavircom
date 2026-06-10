<script setup>
import { ref, watch, computed } from 'vue'
import { router, Head, Link } from '@inertiajs/vue3'
import axios from 'axios'
import Swal from '@/Utils/Swal'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import CfdiPreviewModal from '@/Pages/Cfdi/Partials/CfdiPreviewModal.vue'
import CfdiSecondaryModals from '@/Pages/Cfdi/Partials/CfdiSecondaryModals.vue'
import ModalCrearCuentaCfdi from '@/Components/ModalCrearCuentaCfdi.vue'
import { route } from 'ziggy-js'

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
    getTipoLabel,
    getTipoBadgeClass,
    getStatusBadgeClass
} from '@/Utils/cfdiFormatting'

const props = defineProps({
    cfdis: { type: Object, default: () => ({ data: [], links: [] }) },
    contadores: { type: Object, default: () => ({ total: 0, emitidos: 0, recibidos: 0 }) },
    filters: { type: Object, default: () => ({}) },
    descargasMasivas: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({ ingresos: 0, egresos: 0, pagos: 0, count: 0 }) },
    cuentasBancarias: { type: Array, default: () => [] }
})

const notyf = new Notyf({
    duration: 4000,
    position: { x: 'right', y: 'top' },
    types: [
        { type: 'success', background: '#10b981', icon: false },
        { type: 'error', background: '#ef4444', icon: false }
    ]
})

const selectedDescargaIds = ref([])
const isDeletingBulk = ref(false)
const toggleDescargaSelect = (id) => {
    const i = selectedDescargaIds.value.indexOf(id)
    i >= 0 ? selectedDescargaIds.value.splice(i, 1) : selectedDescargaIds.value.push(id)
}
const toggleSelectAllDescargas = () => {
    if (selectedDescargaIds.value.length === descargasItems.value.length) {
        selectedDescargaIds.value = []
    } else {
        selectedDescargaIds.value = descargasItems.value.map(d => d.id)
    }
}
const eliminarDescargasSeleccionadas = async () => {
    if (!selectedDescargaIds.value.length) return
    const { isConfirmed } = await Swal.fire({ title: 'Eliminar descargas?', text: selectedDescargaIds.value.length + ' solicitudes', icon: 'warning', showCancelButton: true, confirmButtonText: 'Eliminar', cancelButtonText: 'Cancelar' })
    if (!isConfirmed) return
    isDeletingBulk.value = true
    try {
        await axios.delete(route('cfdi.descarga-masiva.bulk-destroy'), { data: { ids: selectedDescargaIds.value } })
        selectedDescargaIds.value = []
        router.reload({ only: ['descargasMasivas'], preserveState: true })
        notyf.success('Descargas eliminadas')
    } catch (e) { notyf.error('Error al eliminar') }
    finally { isDeletingBulk.value = false }
}

// Initialize Composables
const { parseCfdiXml } = useCfdiXmlParser()
const { 
    filters, 
    setQuickRange, 
    setCurrentMonthRange,
    toggleSort, 
    handlePageChange,
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
    descargasItems,
    isDeletingDescarga,
    documentosStaging,
    selectedStagingIds,
    isLoadingReview,
    isImportingSeleccionados,
    solicitarDescarga: rawSolicitarDescarga,
    verificarDescarga,
    eliminarDescarga,
    abrirRevisor: rawAbrirRevisor,
    importarSeleccionados: rawImportarSeleccionados
} = useCfdiDownloads(props, notyf)

const {
    isCheckingSat,
    isSendingEmail,
    isDeletingCfdi,
    selectedUuid,
    xmlContent,
    isLoadingXml,
    parsedCfdiData,
    checkSatStatus,
    deleteCfdi: rawDeleteCfdi,
    enviarCorreo,
    fetchXml
} = useCfdiActions(notyf, parseCfdiXml)

// Quick range for descarga form (separate from table filters)
const setDescargaQuickRange = (days) => {
    const end = new Date()
    const start = new Date()
    start.setDate(start.getDate() - days)
    const fmt = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0')
    descargaForm.value.fecha_inicio = fmt(start)
    descargaForm.value.fecha_fin = fmt(end)
}
const setDescargaCurrentMonth = () => {
    const now = new Date()
    const first = new Date(now.getFullYear(), now.getMonth(), 1)
    const last = new Date(now.getFullYear(), now.getMonth() + 1, 0)
    const fmt = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0')
    descargaForm.value.fecha_inicio = fmt(first)
    descargaForm.value.fecha_fin = fmt(last)
}

// UI State
const showAdvancedFilters = ref(false)
const showUploadModal = ref(false)
const showDescargaModal = ref(false)
const showReviewModal = ref(false)
const showDeleteConfirmModal = ref(false)
const showPreviewModal = ref(false)
const showModalCrearCuenta = ref(false)
const cfdiParaCrearCuenta = ref(null)
const cfdiParaEliminar = ref(null)
const isUploading = ref(false)
const uploadPreview = ref(null)
const selectedFile = ref(null)
const expandedRows = ref([])

const showReportModal = ref(false)
const reportParams = ref({
    mes: String(new Date().getMonth() + 1).padStart(2, '0'),
    anio: String(new Date().getFullYear()),
    direccion: 'todos'
})

const descargarReporteMensual = () => {
    const url = route('cfdi.reporte-mensual', {
        mes: reportParams.value.mes,
        anio: reportParams.value.anio,
        direccion: reportParams.value.direccion
    });
    window.open(url, '_blank');
    showReportModal.value = false;
}

const showSyncModal = ref(false)
const isSyncing = ref(false)
const syncParams = ref({
    mes: String(new Date().getMonth() + 1).padStart(2, '0'),
    anio: String(new Date().getFullYear())
})

const showResultModal = ref(false)
const syncResults = ref({
    success: true,
    message: '',
    updatedCount: 0,
    failedCount: 0,
    failedCfdis: []
})

const syncSatByMonth = async () => {
    isSyncing.value = true
    try {
        const res = await axios.post(route('cfdi.sync-status-month'), {
            month: parseInt(syncParams.value.mes),
            year: parseInt(syncParams.value.anio)
        }, {
            timeout: 180000 // 3 minutos
        })
        
        syncResults.value = res.data
        showSyncModal.value = false
        showResultModal.value = true
    } catch (e) {
        notyf.error(e.response?.data?.message || 'Error al sincronizar con el SAT. Puede que haya tardado demasiado.')
    } finally {
        isSyncing.value = false
    }
}

const eliminarFallidos = async () => {
    if (!syncResults.value.failedCfdis.length) return
    
    const { isConfirmed } = await Swal.fire({ title: '¿Eliminar CFDIs fallidos?', text: `¿Estás seguro de que deseas eliminar los ${syncResults.value.failedCount} CFDIs que fallaron la validación? Esta acción no se puede deshacer.`, icon: 'warning', showCancelButton: true, confirmButtonText: 'Eliminar', cancelButtonText: 'Cancelar' })
    if (!isConfirmed) return

    const ids = syncResults.value.failedCfdis.map(c => c.id)
    
    try {
        const res = await axios.post(route('cfdi.bulk-destroy'), { ids })
        if (res.data.success) {
            notyf.success(res.data.message)
            showResultModal.value = false
            router.reload({ preserveScroll: true })
        }
    } catch (e) {
        notyf.error('Error al intentar eliminar los registros')
    }
}

const toggleRow = (id) => {
    const index = expandedRows.value.indexOf(id)
    if (index > -1) expandedRows.value.splice(index, 1)
    else expandedRows.value.push(id)
}
const isExpanded = (id) => expandedRows.value.includes(id)

const setDireccion = (dir) => { filters.value.direccion = dir }
const setContabilizada = (val) => { filters.value.contabilizada = val }

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

const abrirVisorPdf = (uuid) => {
    window.open(route('cfdi.ver-pdf-view', uuid), '_blank', 'noopener,noreferrer')
}

const enviarAContabilidad = async (cfdi) => {
    try {
        // 1. Get preview (default/automatic)
        const previewRes = await axios.post(route('cfdi.contabilidad.preview', cfdi.uuid))
        if (!previewRes.data.success) {
            notyf.error(previewRes.data.message)
            return
        }
        const p = previewRes.data.preview
        const esCompra = p.tipo === 'egreso' || p.tipo === 'diario'

        // 2. Build conceptos HTML (from cfdi data)
        let conceptosHtml = ''
        if (cfdi.conceptos && cfdi.conceptos.length) {
            conceptosHtml = `
                <div>
                    <h4 class="text-xs font-semibold text-slate-500 uppercase mb-2">Conceptos</h4>
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-slate-100">
                                <th class="px-3 py-1.5 text-[10px] font-semibold text-slate-500 text-left">Descripción</th>
                                <th class="px-3 py-1.5 text-[10px] font-semibold text-slate-500 text-right">Cant</th>
                                <th class="px-3 py-1.5 text-[10px] font-semibold text-slate-500 text-right">P/U</th>
                                <th class="px-3 py-1.5 text-[10px] font-semibold text-slate-500 text-right">Importe</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${cfdi.conceptos.map(c => `
                                <tr>
                                    <td class="px-3 py-1.5 text-xs border-b border-slate-100">${c.descripcion || c.Descripcion || '-'}</td>
                                    <td class="px-3 py-1.5 text-xs border-b border-slate-100 text-right">${c.cantidad || c.Cantidad || 1}</td>
                                    <td class="px-3 py-1.5 text-xs border-b border-slate-100 text-right">$${(c.valor_unitario || c.ValorUnitario || 0).toFixed(2)}</td>
                                    <td class="px-3 py-1.5 text-xs border-b border-slate-100 text-right font-mono">$${(c.importe || c.Importe || 0).toFixed(2)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `
        }

        // 3. Build accounting entries HTML
        let rowsHtml = p.asientos.map(a => `
            <tr>
                <td class="px-3 py-2 text-xs border-b border-slate-200">${a.cuenta_codigo}</td>
                <td class="px-3 py-2 text-xs border-b border-slate-200">${a.cuenta_nombre}${a.auxiliar ? '<br><span class="text-[10px] text-slate-400">' + a.auxiliar + '</span>' : ''}</td>
                <td class="px-3 py-2 text-xs border-b border-slate-200 text-right font-mono">${a.debe > 0 ? '$' + a.debe.toFixed(2) : '-'}</td>
                <td class="px-3 py-2 text-xs border-b border-slate-200 text-right font-mono">${a.haber > 0 ? '$' + a.haber.toFixed(2) : '-'}</td>
            </tr>
        `).join('')

        // Classification selector only shows for purchases/expenses
        const clasificacionHtml = esCompra ? `
            <div class="p-4 rounded-xl bg-brand-50 border border-brand-100 mb-4">
                <label class="block text-[10px] font-black uppercase tracking-wider text-brand-700 mb-2">Clasificación Contable</label>
                <select id="swal-clasificacion" class="w-full h-10 px-3 bg-white border-0 ring-1 ring-brand-200 rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-brand-500 transition-all">
                    <option value="">Automática (Sugerida por IA)</option>
                    <option value="costo">Costo de Venta (Para Clientes)</option>
                    <option value="gasto">Gasto Administrativo / Oficina</option>
                    <option value="activo">Activo Fijo (Equipo / Inversión)</option>
                </select>
                <p class="text-[10px] text-brand-600/80 mt-2 italic font-medium">La IA de Gemini analizará el CFDI y sugerirá la cuenta contable correcta.</p>
            </div>
        ` : ''

        // Bank account selector
        const bancosOptions = props.cuentasBancarias.length 
            ? props.cuentasBancarias.map(c => `<option value="${c.id}">${c.nombre} (${c.banco}) - ${c.tipo === 'tarjeta_credito' ? '💳 ' : '🏦 '}${c.cuenta || 'S/N'} | $${c.saldo.toLocaleString('es-MX', {minimumFractionDigits: 2})}</option>`).join('')
            : '<option value="">No hay cuentas registradas</option>'
        const bancoHtml = `
            <div class="p-4 rounded-xl bg-indigo-50 border border-indigo-100 mb-4">
                <label class="block text-[10px] font-black uppercase tracking-wider text-indigo-700 mb-2">Cuenta Bancaria / Caja</label>
                <select id="swal-banco" class="w-full h-10 px-3 bg-white border-0 ring-1 ring-indigo-200 rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 transition-all">
                    <option value="">No especificar (sin movimiento bancario)</option>
                    ${bancosOptions}
                </select>
                <p class="text-[10px] text-indigo-600/80 mt-2 italic font-medium">Selecciona la cuenta donde se registró el pago/cobro. Si no hubo movimiento, déjalo vacío.</p>
            </div>
        `

        const confirm = await Swal.fire({
            title: 'Vista Previa de Póliza',
            html: `
                <div class="text-left space-y-4 max-h-[60vh] overflow-y-auto custom-scrollbar pr-1">
                    ${clasificacionHtml}
                    ${bancoHtml}
                    <div class="grid grid-cols-2 gap-4 text-sm bg-slate-50 p-4 rounded-xl border border-slate-100">
                        <div><span class="font-semibold text-slate-500">Fecha:</span> ${p.fecha}</div>
                        <div><span class="font-semibold text-slate-500">Tipo:</span> ${p.tipo.toUpperCase()}</div>
                        <div class="col-span-2"><span class="font-semibold text-slate-500">Concepto:</span> ${p.concepto}</div>
                        <div class="col-span-2"><span class="font-semibold text-slate-500">Total:</span> <strong>$${p.total.toLocaleString('es-MX', {minimumFractionDigits: 2})}</strong></div>
                    </div>
                    ${conceptosHtml}
                    <div>
                        <h4 class="text-xs font-semibold text-slate-500 uppercase mb-2">Asientos Contables (Vista Previa)</h4>
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-slate-100">
                                    <th class="px-3 py-2 text-[10px] font-semibold text-slate-500 uppercase text-left">Cuenta</th>
                                    <th class="px-3 py-2 text-[10px] font-semibold text-slate-500 uppercase text-left">Nombre</th>
                                    <th class="px-3 py-2 text-[10px] font-semibold text-slate-500 uppercase text-right">Debe</th>
                                    <th class="px-3 py-2 text-[10px] font-semibold text-slate-500 uppercase text-right">Haber</th>
                                </tr>
                            </thead>
                            <tbody>${rowsHtml}</tbody>
                        </table>
                    </div>
                    <p class="text-xs text-slate-400 font-medium">¿Confirmar la integración a contabilidad?</p>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Sí, generar póliza',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#F59E0B',
            width: 750,
            preConfirm: () => {
                const sel = document.getElementById('swal-clasificacion')
                const banco = document.getElementById('swal-banco')
                return { clasificacion: sel ? sel.value : null, banco_id: banco ? (banco.value || null) : null }
            }
        })
        if (!confirm.isConfirmed) return
        const selectedClasificacion = confirm.value?.clasificacion || null
        const selectedBancoId = confirm.value?.banco_id || null

        // 4. Create poliza with the selected classification and bank account
        const createRes = await axios.post(route('cfdi.contabilidad', cfdi.uuid), {
            clasificacion: selectedClasificacion,
            banco_id: selectedBancoId
        })
        if (createRes.data.success) {
            notyf.success(createRes.data.message || 'Integrado a contabilidad')
            cfdi.contabilidad_integrada = true
        }
    } catch (e) {
        notyf.error(e.response?.data?.message || 'Error al integrar a contabilidad')
    }
}

/** 
 * Descarga XML mejorada. 
 * Si tiene_xml es false, avisa al usuario.
 */
const descargarXmlCfdi = (cfdi) => {
    if (!cfdi.tiene_xml) {
        Swal.fire({
            title: 'XML no disponible',
            text: 'El archivo físico del XML no se encuentra en el servidor. Esto puede ocurrir si el documento fue eliminado manualmente o hubo un error en la sincronización.',
            icon: 'warning',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#e11d48'
        })
        return
    }
    window.open(route('cfdi.xml', cfdi.uuid), '_blank', 'noopener,noreferrer')
}

const descargarPdfCfdi = (uuid) => {
    window.open(route('cfdi.ver-pdf', { uuid, download: 1 }), '_blank', 'noopener,noreferrer')
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
            selectedFile.value = null
            uploadPreview.value = null
            router.reload()
        }
    } finally {
        isUploading.value = false
    }
}

watch(() => props.descargasMasivas, (newDescargas) => {
    // Lógica de resumen de descarga masiva si cambia el status
}, { deep: true })
</script>

<template>
  <Head title="ADD - Administrador de Documentos Digitales" />

  <AppLayout title="Facturación y ADD">
    <div
      class="cfdi-container min-h-[calc(100vh-5rem)] w-full bg-[var(--ui-surface)] text-slate-800 dark:text-slate-100 px-4 pb-10 pt-4 border-t border-slate-300 dark:border-slate-600 transition-all"
    >
      <!-- Header Section -->
      <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6 mb-8">
        <div>
          <p class="text-[10px] font-black uppercase tracking-[0.35em] text-cyan-600 dark:text-cyan-400/90 mb-2">Administrador de Documentos Digitales</p>
          <h1 class="text-4xl font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-4">
            ADD
            <span class="text-lg font-bold text-slate-500 dark:text-slate-400 italic">Sincronización SAT 4.0</span>
          </h1>
          <p class="mt-2 max-w-3xl text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
            Gestión centralizada de comprobantes fiscales emitidos y recibidos. Visualización de PDFs, descarga de XMLs y conciliación con el SAT.
          </p>
        </div>
        
        <div class="flex items-center gap-2">
          <button 
            @click="showUploadModal = true"
            class="group relative inline-flex items-center gap-2 px-5 py-3 bg-white dark:bg-white/5 hover:bg-slate-100 dark:hover:bg-white/10 rounded-2xl text-xs font-black uppercase tracking-wide text-slate-700 dark:text-slate-200 ring-1 ring-slate-200 dark:ring-white/10 transition-all overflow-hidden shadow-sm"
          >
            <div class="absolute inset-0 bg-gradient-to-r from-violet-600/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <svg class="w-4 h-4 text-violet-500 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
            Subir XML
          </button>
          
          <button 
            @click="showReportModal = true"
            class="inline-flex items-center gap-2 px-5 py-3 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-2xl text-xs font-black uppercase tracking-wide border border-slate-200 dark:border-white/10 hover:bg-slate-50 transition-all shadow-sm"
          >
            <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            Reporte PDF
          </button>

          <button 
            @click="showSyncModal = true"
            class="inline-flex items-center gap-2 px-5 py-3 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-2xl text-xs font-black uppercase tracking-wide border border-slate-200 dark:border-white/10 hover:bg-slate-50 transition-all shadow-sm"
          >
            <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
            Validar Estatus SAT
          </button>

          <button 
            @click="showDescargaModal = true"
            class="group relative inline-flex items-center gap-2 px-5 py-3 bg-emerald-600 text-white rounded-2xl text-xs font-black uppercase tracking-wide shadow-xl shadow-emerald-900/30 hover:bg-slate-500 transition-all overflow-hidden"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" /></svg>
            Descarga SAT
          </button>
        </div>
      </div>

      <!-- Quick Stats Dashboard -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="relative overflow-hidden rounded-[2rem] p-6 bg-white dark:bg-slate-800/50 ring-1 ring-slate-100 dark:ring-white/10 shadow-sm backdrop-blur-sm group">
          <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-brand-500/5 blur-2xl group-hover:bg-slate-500/10 transition-colors"></div>
          <p class="text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500">Ingresos (Emitidos)</p>
          <p class="mt-1 text-2xl font-black text-slate-800 dark:text-white tabular-nums">{{ formatMoney(stats.ingresos) }}</p>
          <div class="mt-2 h-1 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
            <div class="h-full bg-brand-500/50 w-full"></div>
          </div>
        </div>

        <div class="relative overflow-hidden rounded-[2rem] p-6 bg-white dark:bg-slate-800/50 ring-1 ring-slate-100 dark:ring-white/10 shadow-sm backdrop-blur-sm group">
          <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-brand-500/5 blur-2xl group-hover:bg-slate-500/10 transition-colors"></div>
          <p class="text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500">Egresos (Gastos)</p>
          <p class="mt-1 text-2xl font-black text-slate-800 dark:text-white tabular-nums">{{ formatMoney(stats.egresos) }}</p>
          <div class="mt-2 h-1 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
            <div class="h-full bg-brand-500/50 w-full"></div>
          </div>
        </div>

        <div class="relative overflow-hidden rounded-[2rem] p-6 bg-white dark:bg-slate-800/50 ring-1 ring-slate-100 dark:ring-white/10 shadow-sm backdrop-blur-sm group">
          <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-brand-500/5 blur-2xl group-hover:bg-slate-500/10 transition-colors"></div>
          <p class="text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500">Pagos / Recibos</p>
          <p class="mt-1 text-2xl font-black text-slate-800 dark:text-white tabular-nums">{{ formatMoney(stats.pagos) }}</p>
          <div class="mt-2 h-1 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
            <div class="h-full bg-brand-500/50 w-full"></div>
          </div>
        </div>

        <div class="relative overflow-hidden rounded-[2rem] p-6 bg-white dark:bg-slate-800/50 ring-1 ring-slate-100 dark:ring-white/10 shadow-sm backdrop-blur-sm group">
          <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-cyan-500/5 blur-2xl group-hover:bg-cyan-500/10 transition-colors"></div>
          <p class="text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500">Documentos Totales</p>
          <p class="mt-1 text-2xl font-black text-cyan-600 dark:text-cyan-400 tabular-nums">{{ cfdis.total }}</p>
          <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 uppercase font-bold tracking-tighter">En base de datos</p>
        </div>
      </div>

      <!-- Direction Tabs -->
      <div class="flex flex-wrap gap-2 mb-8">
        <button 
          @click="setDireccion('')" 
          :class="['px-6 py-3 rounded-2xl text-xs font-black uppercase tracking-wide transition-all border', 
                   filters.direccion === '' ? 'bg-white text-slate-950 border-white dark:border-slate-800 shadow-xl shadow-slate-200 dark:shadow-white/10' : 'bg-white/50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 border-slate-100 dark:border-white/5 hover:border-brand-500 dark:hover:border-white/20 hover:text-slate-700 dark:hover:text-slate-200']"
        >
          Todos
          <span class="ml-2 opacity-40 tabular-nums text-[10px]">{{ contadores.total }}</span>
        </button>
        <button 
          @click="setDireccion('emitido')" 
          :class="['px-6 py-3 rounded-2xl text-xs font-black uppercase tracking-wide transition-all border flex items-center gap-2', 
                   filters.direccion === 'emitido' ? 'bg-emerald-600 text-white border-emerald-500 shadow-xl shadow-emerald-900/20' : 'bg-white/50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 border-slate-100 dark:border-white/5 hover:border-brand-500 dark:hover:border-white/20 hover:text-slate-700 dark:hover:text-slate-200']"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
          Emitidos
          <span class="ml-1 opacity-40 tabular-nums text-[10px]">{{ contadores.emitidos }}</span>
        </button>
        <button 
          @click="setDireccion('recibido')" 
          :class="['px-6 py-3 rounded-2xl text-xs font-black uppercase tracking-wide transition-all border flex items-center gap-2', 
                   filters.direccion === 'recibido' ? 'bg-violet-600 text-white border-violet-500 shadow-xl shadow-violet-900/20' : 'bg-white/50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 border-slate-100 dark:border-white/5 hover:border-brand-500 dark:hover:border-white/20 hover:text-slate-700 dark:hover:text-slate-200']"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
          Recibidos
          <span class="ml-1 opacity-40 tabular-nums text-[10px]">{{ contadores.recibidos }}</span>
        </button>
        <!-- Contabilizada filter -->
        <div class="flex gap-2 mb-6">
          <button @click="setContabilizada('')"
            :class="['px-4 py-2 rounded-xl text-[10px] font-bold uppercase tracking-wide transition-all border', !filters.contabilizada ? 'bg-brand-500 text-white border-brand-500' : 'bg-white/50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-700 hover:border-amber-300']">
            Todas
          </button>
          <button @click="setContabilizada('si')"
            :class="['px-4 py-2 rounded-xl text-[10px] font-bold uppercase tracking-wide transition-all border flex items-center gap-1.5', filters.contabilizada === 'si' ? 'bg-emerald-600 text-white border-emerald-500' : 'bg-white/50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-700 hover:border-emerald-300']">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            Contabilizadas
            <span class="ml-1 opacity-60 tabular-nums text-[10px]">{{ contadores.contabilizadas }}</span>
          </button>
          <button @click="setContabilizada('no')"
            :class="['px-4 py-2 rounded-xl text-[10px] font-bold uppercase tracking-wide transition-all border flex items-center gap-1.5', filters.contabilizada === 'no' ? 'bg-rose-600 text-white border-rose-500' : 'bg-white/50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-700 hover:border-rose-300']">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            No Contabilizadas
            <span class="ml-1 opacity-60 tabular-nums text-[10px]">{{ contadores.no_contabilizadas }}</span>
          </button>
        </div>
      </div>

      <!-- Main Content Area -->
      <div class="flex flex-col lg:flex-row gap-8">
        
        <!-- Left: Filters and Active Downloads -->
        <div class="w-full lg:w-80 space-y-6">
          
          <!-- Filters Card -->
          <div class="rounded-[2rem] bg-white dark:bg-slate-800/50 border border-slate-100 dark:border-white/10 p-6 backdrop-blur-md shadow-sm dark:shadow-2xl">
            <h3 class="text-xs font-black uppercase tracking-wide text-slate-800 dark:text-slate-200 mb-6 flex items-center gap-2">
              <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
              Filtros
            </h3>
            
            <div class="space-y-5">
              <div>
                <label class="block text-[9px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 mb-2 pl-1">Búsqueda</label>
                <input 
                  v-model="filters.search" 
                  type="text" 
                  aria-label="Buscar folio o UUID"
                  placeholder="Folio o UUID..."
                  class="w-full h-12 bg-[var(--ui-surface)] dark:bg-slate-950/80 border-0 ring-1 ring-slate-200 dark:ring-white/10 rounded-xl text-xs font-bold text-slate-800 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-brand-500/40 transition-all"
                >
              </div>

              <div>
                <label class="block text-[9px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 mb-2 pl-1">Tipo de Comprobante</label>
                <select 
                  v-model="filters.tipo_comprobante"
                  class="w-full h-12 bg-[var(--ui-surface)] dark:bg-slate-950/80 border-0 ring-1 ring-slate-200 dark:ring-white/10 rounded-xl text-xs font-bold text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-brand-500/40 transition-all"
                >
                  <option value="">Cualquier Tipo</option>
                  <option value="I">Factura (Ingreso)</option>
                  <option value="P">Pago (Complemento)</option>
                  <option value="E">Egreso (N. Crédito)</option>
                </select>
              </div>

              <div>
                <label class="block text-[9px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 mb-2 pl-1">Rango de Fecha</label>
                <div class="grid grid-cols-1 gap-2">
                  <input type="date" v-model="filters.fecha_inicio" aria-label="Fecha inicio" class="w-full h-10 bg-[var(--ui-surface)] dark:bg-slate-950/50 border-0 ring-1 ring-slate-200 dark:ring-white/10 rounded-xl text-[11px] font-bold text-slate-700 dark:text-slate-200">
                  <input type="date" v-model="filters.fecha_fin" aria-label="Fecha fin" class="w-full h-10 bg-[var(--ui-surface)] dark:bg-slate-950/50 border-0 ring-1 ring-slate-200 dark:ring-white/10 rounded-xl text-[11px] font-bold text-slate-700 dark:text-slate-200">
                </div>
              </div>

              <button 
                @click="showAdvancedFilters = !showAdvancedFilters"
                class="w-full py-2 text-[9px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 hover:text-slate-700 dark:hover:text-slate-200 transition-colors flex items-center justify-center gap-2"
              >
                {{ showAdvancedFilters ? 'Ocultar Avanzados' : 'Más Filtros' }}
                <svg class="w-3 h-3" :class="showAdvancedFilters ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
              </button>

              <div v-if="showAdvancedFilters" class="space-y-6 pt-2 border-t border-slate-100 dark:border-white/5 animate-fadeIn">
                <div>
                  <label class="block text-[9px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 mb-2 pl-1">RFC Emisor</label>
                  <input v-model="filters.rfc_emisor" aria-label="RFC Emisor" class="w-full h-10 bg-[var(--ui-surface)] dark:bg-slate-950/50 border-0 ring-1 ring-slate-200 dark:ring-white/10 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 uppercase">
                </div>
                <div>
                  <input v-model="filters.rfc_receptor" aria-label="RFC Receptor" class="w-full h-10 bg-[var(--ui-surface)] dark:bg-slate-950/50 border-0 ring-1 ring-slate-200 dark:ring-white/10 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 uppercase">
                </div>
              </div>

              <button 
                @click="Object.keys(filters).forEach(k => filters[k] = k === 'sort_dir' ? 'desc' : '')"
                class="w-full h-12 rounded-xl text-[10px] font-black uppercase tracking-[0.2em] bg-slate-100 dark:bg-white/5 text-slate-500 dark:text-slate-400 hover:bg-slate-500/10 hover:text-rose-600 dark:hover:text-rose-400 transition-all border border-transparent hover:border-brand-500/20"
              >
                Limpiar Filtros
              </button>
            </div>
          </div>

          <!-- Downloads Progress Card -->
          <div v-if="descargasItems.length" class="rounded-[2rem] bg-white dark:bg-slate-800/50 border border-slate-100 dark:border-white/10 p-6 backdrop-blur-md shadow-sm dark:shadow-2xl">
            <div class="flex items-center justify-between mb-4 gap-2">
              <h3 class="text-xs font-black uppercase tracking-wide text-slate-800 dark:text-slate-200 flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" /></svg>
                Sincronización SAT
              </h3>
              
              <button 
                v-if="selectedDescargaIds.length"
                @click="eliminarDescargasSeleccionadas"
                :disabled="isDeletingBulk"
                class="px-2.5 py-1.5 bg-rose-600 hover:bg-rose-500 text-white text-[9px] font-black uppercase tracking-wide rounded-xl transition-all flex items-center gap-1 shadow-md shadow-rose-900/20 disabled:opacity-50"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                Eliminar ({{ selectedDescargaIds.length }})
              </button>
            </div>

            <!-- Seleccionar todos checkbox -->
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100 dark:border-white/5">
              <label class="flex items-center gap-2 cursor-pointer select-none">
                <input 
                  type="checkbox" 
                  :checked="selectedDescargaIds.length === descargasItems.length && descargasItems.length > 0"
                  @change="toggleSelectAllDescargas"
                  class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 bg-[var(--ui-surface)] text-cyan-600 focus:ring-brand-500/40 cursor-pointer" 
                />
                <span class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider">Seleccionar Todo</span>
              </label>
              <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500">{{ selectedDescargaIds.length }}/{{ descargasItems.length }}</span>
            </div>
            
            <div class="space-y-3 max-h-[50vh] overflow-y-auto pr-1 custom-scrollbar">
              <div v-for="descarga in descargasItems" :key="descarga.id" 
                   :class="['p-4 rounded-2xl border transition-all flex items-start gap-3 group', selectedDescargaIds.includes(descarga.id) ? 'bg-cyan-500/5 border-cyan-500/30' : 'bg-[var(--ui-surface)] dark:bg-slate-950/50 border-slate-100 dark:border-white/5 hover:border-brand-500 dark:hover:border-white/10']">
                
                <!-- Individual Select Checkbox -->
                <div class="pt-0.5 flex items-center justify-center">
                  <input 
                    type="checkbox" 
                    :checked="selectedDescargaIds.includes(descarga.id)" 
                    @change="toggleDescargaSelect(descarga.id)" 
                    class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-cyan-600 focus:ring-brand-500/40 cursor-pointer" 
                  />
                </div>

                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                      <span :class="['text-[8px] font-black uppercase px-2 py-0.5 rounded-xl', descarga.direccion === 'emitido' ? 'bg-brand-500/10 text-emerald-600 dark:text-slate-400' : 'bg-violet-500/10 text-violet-600 dark:text-violet-400']">
                        {{ descarga.direccion }}
                      </span>
                      <span class="text-[8px] font-mono font-bold text-slate-500 dark:text-slate-400">#{{ descarga.id }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <span v-if="descarga.retry_count > 0" class="text-[8px] font-bold text-brand-500 dark:text-brand-400 uppercase">Intento {{ descarga.retry_count }}/{{ descarga.max_retries || 3 }}</span>
                      <span class="text-[8px] font-bold text-slate-400 dark:text-slate-500 uppercase">{{ descarga.status }}</span>
                    </div>
                  </div>
                  
                  <div class="text-[10px] font-bold text-slate-700 dark:text-slate-200 truncate">{{ formatDateShort(descarga.fecha_inicio) }} → {{ formatDateShort(descarga.fecha_fin) }}</div>
                  <div v-if="descarga.request_id" class="text-[9px] font-mono text-brand-500 dark:text-brand-400 truncate mt-0.5" title="Folio SAT: {{ descarga.request_id }}">Folio: {{ descarga.request_id.substring(0, 20) }}...</div>
                  
                  <div v-if="descarga.total_cfdis > 0" class="mb-3 mt-2">
                    <div class="h-1.5 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                      <div class="h-full bg-cyan-500 transition-all duration-500" :style="{ width: Math.min(100, ((descarga.inserted_cfdis + descarga.duplicate_cfdis) / descarga.total_cfdis) * 100) + '%' }"></div>
                    </div>
                  </div>

                  <div class="flex items-center gap-2 mt-3">
                    <button v-if="['finished', 'error', 'completo', 'completado'].includes(descarga.status)"
                            @click="abrirRevisor(descarga)"
                            class="flex-1 py-1.5 bg-cyan-600 hover:bg-cyan-500 text-white text-[9px] font-black uppercase tracking-wide rounded-xl transition-colors">
                      Ver Resultados
                    </button>
                    <button @click="eliminarDescarga(descarga.id)" class="p-2 text-slate-400 dark:text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 transition-colors">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>

        <!-- Right: Table Area -->
        <div class="flex-1">
          <div class="rounded-[2.5rem] bg-white dark:bg-slate-800/50 border border-slate-100 dark:border-white/10 shadow-sm dark:shadow-2xl backdrop-blur-md overflow-hidden">
            <div class="overflow-x-auto overflow-y-visible">
              <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead>
                  <tr class="text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 bg-[var(--ui-surface)] dark:bg-slate-950/80 border-b border-slate-100 dark:border-white/5">
                    <th class="px-4 py-5 text-center">
                      <input type="checkbox" :checked="selectedIds.length === cfdiItems.length && cfdiItems.length > 0" @change="toggleSelectAll" class="w-4 h-4 rounded-xl border-slate-300 dark:border-slate-600 bg-[var(--ui-surface)] text-cyan-600 focus:ring-brand-500/40 cursor-pointer" />
                    </th>
                    <th class="px-6 py-5">
                      <button @click="toggleSort('folio')" class="group flex items-center gap-2 hover:text-slate-800 dark:hover:text-slate-200">
                        Folio / UUID
                        <span class="opacity-0 group-hover:opacity-100 transition-opacity">{{ getSortIndicator('folio') }}</span>
                      </button>
                    </th>
                    <th class="px-6 py-5">
                      <button @click="toggleSort('nombre')" class="group flex items-center gap-2 hover:text-slate-800 dark:hover:text-slate-200">
                        Entidad (Emisor/Receptor)
                        <span class="opacity-0 group-hover:opacity-100 transition-opacity">{{ getSortIndicator('nombre') }}</span>
                      </button>
                    </th>
                    <th class="px-6 py-5">
                      <button @click="toggleSort('fecha_emision')" class="group flex items-center gap-2 hover:text-slate-800 dark:hover:text-slate-200">
                        Fecha
                        <span class="opacity-0 group-hover:opacity-100 transition-opacity">{{ getSortIndicator('fecha_emision') }}</span>
                      </button>
                    </th>
                    <th class="px-6 py-5 text-right">
                      <button @click="toggleSort('total')" class="group flex items-center gap-2 justify-end hover:text-slate-800 dark:hover:text-slate-200">
                        Importe
                        <span class="opacity-0 group-hover:opacity-100 transition-opacity">{{ getSortIndicator('total') }}</span>
                      </button>
                    </th>
                    <th class="px-6 py-5 text-center">Estatus</th>
                    <th class="px-6 py-5 text-right">Acciones</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                  <template v-for="cfdi in cfdiItems" :key="cfdi.id">
                    <tr 
                      :class="['hover:bg-slate-50 dark:hover:bg-white/[0.03] transition-all group cursor-pointer text-slate-700 dark:text-slate-200', isSelected(cfdi.id) ? 'bg-cyan-500/5' : 'bg-white dark:bg-transparent', isExpanded(cfdi.id) ? 'bg-slate-50 dark:bg-white/[0.05]' : '']"
                      @click="toggleRow(cfdi.id)"
                    >
                      <td class="px-4 py-5 text-center" @click.stop>
                        <input type="checkbox" :checked="isSelected(cfdi.id)" @change="toggleSelect(cfdi.id)" class="w-4 h-4 rounded-xl border-slate-300 dark:border-slate-600 bg-[var(--ui-surface)] text-cyan-600 focus:ring-brand-500/40 cursor-pointer" />
                      </td>
                      <td class="px-6 py-5">
                        <div class="flex flex-col">
                          <div class="flex items-center gap-2">
                            <span class="font-black text-slate-800 dark:text-slate-100 tracking-tight">{{ cfdi.folio }}</span>
                            <span :class="['px-1.5 py-0.5 rounded-xl text-[8px] font-black uppercase tracking-wide ring-1', getTipoBadgeClass(cfdi.tipo_comprobante)]">
                              {{ getTipoLabel(cfdi.tipo_comprobante) }}
                            </span>
                          </div>
                          <span class="text-[10px] font-mono text-slate-400 dark:text-slate-500 truncate max-w-[120px]" :title="cfdi.uuid">{{ cfdi.uuid }}</span>
                        </div>
                      </td>
                      <td class="px-6 py-5">
                        <div class="flex flex-col">
                          <template v-if="cfdi.direccion === 'emitido'">
                            <span class="text-[8px] font-black text-emerald-600 dark:text-emerald-500/80 uppercase tracking-wide mb-0.5">Receptor</span>
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200 line-clamp-1" :title="cfdi.receptor">{{ cfdi.receptor }}</span>
                            <span class="text-[10px] font-mono text-slate-400 dark:text-slate-500">{{ cfdi.rfc_receptor }}</span>
                          </template>
                          <template v-else>
                            <span class="text-[8px] font-black text-violet-600 dark:text-violet-500/80 uppercase tracking-wide mb-0.5">Emisor</span>
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200 line-clamp-1" :title="cfdi.emisor">{{ cfdi.emisor }}</span>
                            <span class="text-[10px] font-mono text-slate-400 dark:text-slate-500">{{ cfdi.rfc_emisor }}</span>
                          </template>
                        </div>
                      </td>
                      <td class="px-6 py-5">
                        <div class="flex flex-col">
                          <span class="text-xs font-bold text-slate-500 dark:text-slate-200">{{ cfdi.fecha }}</span>
                          <span class="text-[9px] uppercase font-black text-slate-400 dark:text-slate-500">Timbrado</span>
                        </div>
                      </td>
                      <td class="px-6 py-5 text-right font-black text-slate-800 dark:text-slate-100 tabular-nums">
                        {{ formatMoney(cfdi.total) }}
                      </td>
                      <td class="px-6 py-5 text-center">
                        <span :class="['px-2 py-0.5 rounded-xl text-[9px] font-black uppercase tracking-wide ring-1 ring-inset', getStatusBadgeClass(cfdi.estado_sat)]">
                          {{ cfdi.estado_sat || 'Vigente' }}
                        </span>
                      </td>
                      <td class="px-6 py-5">
                        <div class="flex items-center justify-end gap-1.5" @click.stop>
                          <button @click="verXml(cfdi.uuid)" class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-white/5 hover:bg-cyan-500 hover:text-white text-slate-500 dark:text-slate-400 transition-all flex items-center justify-center" title="Ver XML">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                          </button>
                          <button @click="abrirVisorPdf(cfdi.uuid)" class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-white/5 hover:bg-slate-500 hover:text-white text-slate-500 dark:text-slate-400 transition-all flex items-center justify-center" title="Abrir PDF">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                          </button>
                          <button @click="descargarXmlCfdi(cfdi)" :class="['w-10 h-10 rounded-xl bg-slate-100 dark:bg-white/5 hover:bg-indigo-500 hover:text-white transition-all flex items-center justify-center', !cfdi.tiene_xml ? 'opacity-30 grayscale' : 'text-slate-500 dark:text-slate-400']" title="Descargar XML">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                          </button>
                          <button v-if="!cfdi.contabilidad_integrada" @click="enviarAContabilidad(cfdi)" class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-white/5 hover:bg-brand-500 hover:text-white text-slate-500 dark:text-slate-400 transition-all flex items-center justify-center" title="Enviar a Contabilidad">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                          </button>
                          <button v-if="cfdi.contabilidad_integrada" class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center cursor-default" title="Integrado a Contabilidad">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                          </button>
                          <button @click="confirmarEliminacion(cfdi)" class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-white/5 hover:bg-rose-600 hover:text-white text-slate-500 dark:text-slate-400 transition-all flex items-center justify-center" title="Eliminar">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                          </button>
                        </div>
                      </td>
                    </tr>
                    <!-- Expanded Row Detail -->
                    <tr v-if="isExpanded(cfdi.id)">
                      <td colspan="7" class="px-10 py-8 bg-[var(--ui-surface)] dark:bg-slate-950/40 border-y border-slate-100 dark:border-white/5 animate-slideDown">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                          <div class="space-y-6">
                            <div class="p-4 rounded-2xl bg-white dark:bg-slate-800/50 ring-1 ring-slate-100 dark:ring-white/10 shadow-sm">
                              <h5 class="text-[9px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 mb-3">Datos del Emisor</h5>
                              <p class="text-xs font-bold text-slate-800 dark:text-slate-100">{{ cfdi.emisor }}</p>
                              <p class="text-[10px] font-mono text-slate-400 dark:text-slate-500 mt-1">{{ cfdi.rfc_emisor }}</p>
                            </div>
                            <div class="p-4 rounded-2xl bg-white dark:bg-slate-800/50 ring-1 ring-slate-100 dark:ring-white/10 shadow-sm">
                              <h5 class="text-[9px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 mb-3">Datos del Receptor</h5>
                              <p class="text-xs font-bold text-slate-800 dark:text-slate-100">{{ cfdi.receptor }}</p>
                              <p class="text-[10px] font-mono text-slate-400 dark:text-slate-500 mt-1">{{ cfdi.rfc_receptor }}</p>
                            </div>
                          </div>
                          
                          <div class="md:col-span-2 p-5 rounded-2xl bg-white dark:bg-slate-800/50 ring-1 ring-slate-100 dark:ring-white/10 shadow-sm">
                            <h5 class="text-[9px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 mb-4">Conceptos Facturados</h5>
                            <div class="space-y-3 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                              <div v-for="concept in cfdi.conceptos" :key="concept.id" class="flex justify-between items-start py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
                                <div>
                                  <p class="text-xs font-bold text-slate-700 dark:text-slate-200">{{ concept.descripcion }}</p>
                                  <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">
                                    {{ concept.cantidad }} {{ concept.unidad || 'PZ' }} × {{ formatMoney(concept.valor_unitario) }}
                                  </p>
                                </div>
                                <span class="text-xs font-black text-slate-800 dark:text-slate-100">{{ formatMoney(concept.importe) }}</span>
                              </div>
                            </div>
                            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-white/10 flex justify-between items-center">
                              <span class="text-[10px] font-black uppercase text-slate-400 dark:text-slate-500">Subtotal del Documento</span>
                              <span class="text-sm font-black text-slate-800 dark:text-slate-200">{{ formatMoney(cfdi.subtotal) }}</span>
                            </div>
                          </div>
                        </div>

                        <div class="mt-8 flex justify-end gap-3">
                          <button @click="abrirModalCrearCuenta(cfdi)" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-wide bg-[var(--ui-surface)] dark:bg-white/5 hover:bg-slate-100 dark:hover:bg-white/10 text-slate-500 dark:text-slate-200 ring-1 ring-slate-200 dark:ring-white/10 transition-all flex items-center gap-2 shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Crear Proveedor
                          </button>
                          <button @click="confirmarEliminacion(cfdi)" v-if="!cfdi.venta_id" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-wide bg-rose-50 dark:bg-rose-900/20 dark:bg-brand-500/10 hover:bg-rose-100 dark:hover:bg-slate-500/20 text-rose-600 dark:text-rose-400 ring-1 ring-rose-200 dark:ring-rose-500/20 transition-all flex items-center gap-2 shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            Eliminar Registro
                          </button>
                        </div>
                      </td>
                    </tr>
                  </template>
                  <tr v-if="cfdiItems.length === 0">
                    <td colspan="7" class="py-20 text-center">
                      <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 mb-4">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                      </div>
                      <p class="text-sm font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">No se encontraron documentos</p>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Bulk Actions Bar -->
            <Transition name="slide-up">
              <div v-if="selectedIds.length > 0" class="fixed bottom-8 left-1/2 -translate-x-1/2 z-[100] w-[90%] max-w-2xl">
                <div class="bg-white/80 dark:bg-slate-800/95 backdrop-blur-xl border border-slate-200 dark:border-white/20 p-4 rounded-[2rem] shadow-[0_20px_50px_rgba(0,0,0,0.1)] dark:shadow-[0_20px_50px_rgba(0,0,0,0.5)] flex items-center justify-between">
                  <div class="flex items-center gap-4 pl-4">
                    <div class="h-10 w-10 rounded-full bg-cyan-600 flex items-center justify-center font-black text-white shadow-xl shadow-cyan-900/50">
                      {{ selectedIds.length }}
                    </div>
                    <div>
                      <p class="text-[10px] font-black uppercase text-slate-800 dark:text-slate-100">Seleccionados</p>
                      <p class="text-[9px] text-slate-400 dark:text-slate-500 uppercase font-bold tracking-wide">Acciones masivas</p>
                    </div>
                  </div>
                  
                  <div class="flex items-center gap-2">
                    <button @click="bulkDownloadZip" :disabled="isBulkDownloading" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-[10px] font-black uppercase transition-all flex items-center gap-2 disabled:opacity-50">
                      <svg v-if="isBulkDownloading" class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                      Descargar ZIP
                    </button>
                    <button @click="bulkCheckSat" class="px-5 py-2.5 bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 text-slate-700 dark:text-white rounded-xl text-[10px] font-black uppercase transition-all">
                      Validar SAT
                    </button>
                    <button @click="selectedIds = []" class="px-5 py-2.5 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition-colors text-[10px] font-black uppercase">
                      Cancelar
                    </button>
                  </div>
                </div>
              </div>
            </Transition>

            <div class="p-6 border-t border-white/5 flex justify-center">
              <Pagination :pagination-data="cfdis" @page-change="handlePageChange" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <CfdiPreviewModal 
      :show="showPreviewModal"
      :uuid="selectedUuid"
      :xml-content="xmlContent"
      :is-loading="isLoadingXml"
      :parsed-data="parsedCfdiData"
      @close="showPreviewModal = false"
    />

    <CfdiSecondaryModals
      :show-descarga-modal="showDescargaModal"
      :descarga-form="descargaForm"
      :descarga-sending="isBulkProcessing"
      @close-descarga="showDescargaModal = false"
      @set-quick-range="setDescargaQuickRange"
      @set-current-month-range="setDescargaCurrentMonth"
      @solicitar-descarga="solicitarDescarga"
      
      :show-review-modal="showReviewModal"
      :is-loading-review="isLoadingReview"
      :documentos-staging="documentosStaging"
      :selected-staging-ids="selectedStagingIds"
      :is-importing="isImportingSeleccionados"
      @close-review="showReviewModal = false"
      @toggle-seleccion-staging="id => selectedStagingIds.includes(id) ? selectedStagingIds.splice(selectedStagingIds.indexOf(id), 1) : selectedStagingIds.push(id)"
      @seleccionar-todo-staging="selectedStagingIds = documentosStaging.map(d => d.id)"
      @deseleccionar-todo-staging="selectedStagingIds = []"
      @importar-seleccionados="importarSeleccionados"
      @ver-pdf-staging="doc => window.open(route('cfdi.ver-pdf-view', doc.uuid), '_blank')"

      :show-delete-confirm-modal="showDeleteConfirmModal"
      :cfdi-para-eliminar="cfdiParaEliminar"
      :is-deleting-cfdi="isDeletingCfdi"
      @close-delete="showDeleteConfirmModal = false"
      @ejecutar-eliminacion="ejecutarEliminacion"
    />

    <ModalCrearCuentaCfdi
      v-if="showModalCrearCuenta"
      :show="showModalCrearCuenta"
      :cfdi="cfParaCrearCuenta"
      @close="showModalCrearCuenta = false"
      @created="onCuentaCreated"
    />

    <!-- Custom Upload Modal -->
    <div v-if="showUploadModal" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" @click="showUploadModal = false"></div>
      <div class="relative w-full max-w-lg bg-white dark:bg-slate-800 border border-slate-100 dark:border-white/10 rounded-[2.5rem] shadow-2xl overflow-hidden animate-zoomIn">
        <div class="p-8">
          <div class="flex items-center justify-between mb-8">
            <h3 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-wider">Cargar Comprobante XML</h3>
            <button @click="showUploadModal = false" class="text-slate-400 dark:text-slate-500 hover:text-slate-700 dark:hover:text-white transition-colors">
              <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          </div>

          <div 
            class="group relative border-2 border-dashed border-slate-200 dark:border-white/10 rounded-3xl p-12 text-center transition-all hover:border-brand-500/50 hover:bg-violet-500/5 cursor-pointer"
            @click="$refs.fileInput.click()"
          >
            <input type="file" ref="fileInput" class="hidden" accept=".xml" @change="handleFileSelect">
            
            <div class="mb-4 inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-violet-500/10 text-violet-600 dark:text-violet-400 ring-1 ring-violet-500/20 group-hover:scale-105 transition-transform">
              <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
            </div>
            
            <p class="text-sm font-bold text-slate-700 dark:text-slate-200">Selecciona o arrastra el archivo XML</p>
            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-2 uppercase tracking-wide font-black">Solo formatos .xml válidos</p>
          </div>

          <div v-if="selectedFile" class="mt-6 p-4 rounded-2xl bg-[var(--ui-surface)] dark:bg-white/5 border border-slate-100 dark:border-white/5 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-10 h-10 rounded-xl bg-brand-500/20 text-emerald-600 dark:text-slate-400 flex items-center justify-center">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              </div>
              <div class="flex flex-col">
                <span class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ selectedFile.name }}</span>
                <span class="text-[10px] text-slate-400 dark:text-slate-500">{{ (selectedFile.size / 1024).toFixed(2) }} KB</span>
              </div>
            </div>
            <button @click="selectedFile = null; uploadPreview = null" class="text-slate-400 dark:text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 transition-colors">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          </div>

          <div v-if="uploadPreview" class="mt-4 p-4 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 animate-slideDown">
            <div class="flex justify-between items-center mb-2">
              <span class="text-[10px] font-black uppercase text-indigo-600 dark:text-indigo-400 tracking-wide">Vista Previa CFDI</span>
              <span v-if="uploadPreview.is_duplicate" class="text-[9px] font-black uppercase bg-brand-500 text-white px-2 py-0.5 rounded-xl">Duplicado</span>
            </div>
            <p class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ uploadPreview.data.emisor?.nombre || 'Emisor desconocido' }}</p>
            <p class="text-[11px] font-black text-slate-700 dark:text-slate-100 mt-1">Total: {{ formatMoney(uploadPreview.data.total) }}</p>
          </div>

          <div class="mt-8 flex gap-3">
            <button 
              @click="showUploadModal = false"
              class="flex-1 py-4 rounded-2xl text-[11px] font-black uppercase tracking-wide bg-slate-100 dark:bg-white/5 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-white/10 transition-all"
            >
              Cancelar
            </button>
            <button 
              @click="uploadXml"
              :disabled="!selectedFile || isUploading"
              class="flex-1 py-4 rounded-2xl text-[11px] font-black uppercase tracking-wide bg-violet-600 text-white shadow-xl shadow-violet-900/40 hover:bg-violet-500 transition-all disabled:opacity-50"
            >
              {{ isUploading ? 'Procesando...' : 'Cargar Documento' }}
            </button>
          </div>
        </div>
      </div>
    </div>
    <!-- Report Modal -->
    <div v-if="showReportModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-md" @click="showReportModal = false"></div>
      <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl p-8 border border-slate-100 dark:border-white/10 animate-zoomIn">
        <div class="flex flex-col items-center text-center mb-8">
          <div class="w-16 h-16 rounded-[2rem] bg-rose-500/10 text-rose-600 flex items-center justify-center mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
          </div>
          <h2 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Reporte Mensual PDF</h2>
          <p class="text-sm text-slate-400 dark:text-slate-500 mt-1 font-bold">Selecciona el periodo para tu contador</p>
        </div>


        <div class="space-y-6">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 mb-2 ml-1">Mes</label>
              <select v-model="reportParams.mes" class="w-full h-12 rounded-2xl bg-slate-50 dark:bg-white/5 border-0 ring-1 ring-slate-100 dark:ring-white/10 text-sm font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-rose-500 transition-all">
                <option value="01">Enero</option>
                <option value="02">Febrero</option>
                <option value="03">Marzo</option>
                <option value="04">Abril</option>
                <option value="05">Mayo</option>
                <option value="06">Junio</option>
                <option value="07">Julio</option>
                <option value="08">Agosto</option>
                <option value="09">Septiembre</option>
                <option value="10">Octubre</option>
                <option value="11">Noviembre</option>
                <option value="12">Diciembre</option>
              </select>
            </div>
            <div>
              <label class="block text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 mb-2 ml-1">Año</label>
              <select v-model="reportParams.anio" class="w-full h-12 rounded-2xl bg-slate-50 dark:bg-white/5 border-0 ring-1 ring-slate-100 dark:ring-white/10 text-sm font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-rose-500 transition-all">
                <option v-for="y in [2024, 2025, 2026]" :key="y" :value="y">{{ y }}</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 mb-2 ml-1">Tipo de Documentos</label>
            <div class="grid grid-cols-3 gap-2">
              <button @click="reportParams.direccion = 'todos'" :class="['py-2 rounded-xl text-[10px] font-black uppercase transition-all border', reportParams.direccion === 'todos' ? 'bg-slate-800 text-white border-slate-700' : 'bg-slate-50 dark:bg-white/5 text-slate-400 border-transparent']">Todos</button>
              <button @click="reportParams.direccion = 'emitido'" :class="['py-2 rounded-xl text-[10px] font-black uppercase transition-all border', reportParams.direccion === 'emitido' ? 'bg-emerald-600 text-white border-emerald-500' : 'bg-slate-50 dark:bg-white/5 text-slate-400 border-transparent']">Emitidos</button>
              <button @click="reportParams.direccion = 'recibido'" :class="['py-2 rounded-xl text-[10px] font-black uppercase transition-all border', reportParams.direccion === 'recibido' ? 'bg-indigo-600 text-white border-indigo-500' : 'bg-slate-50 dark:bg-white/5 text-slate-400 border-transparent']">Recibidos</button>
            </div>
          </div>

          <div class="mt-8 flex gap-3">
            <button @click="showReportModal = false" class="flex-1 py-4 rounded-2xl text-[11px] font-black uppercase tracking-wide bg-slate-100 dark:bg-white/5 text-slate-500 dark:text-slate-400 hover:bg-slate-200 transition-all">Cerrar</button>
            <button @click="descargarReporteMensual" class="flex-1 py-4 rounded-2xl text-[11px] font-black uppercase tracking-wide bg-rose-600 text-white shadow-xl shadow-rose-900/40 hover:bg-rose-500 transition-all flex items-center justify-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" /></svg>
              Descargar PDF
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- SAT Sync Modal -->
    <div v-if="showSyncModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-md" @click="showSyncModal = false"></div>
      <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl p-8 border border-slate-100 dark:border-white/10 animate-zoomIn">
        <div class="flex flex-col items-center text-center mb-8">
          <div :class="['w-16 h-16 rounded-[2rem] bg-cyan-500/10 text-cyan-600 flex items-center justify-center mb-4', isSyncing ? 'animate-spin' : '']">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
          </div>
          <h2 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Sincronizar Estatus SAT</h2>
          <p class="text-sm text-slate-400 dark:text-slate-500 mt-1 font-bold">Verifica si tus facturas siguen vigentes o fueron canceladas</p>
        </div>

        <div class="space-y-6">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 mb-2 ml-1">Mes</label>
              <select v-model="syncParams.mes" class="w-full h-12 rounded-2xl bg-slate-50 dark:bg-white/5 border-0 ring-1 ring-slate-100 dark:ring-white/10 text-sm font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-cyan-500 transition-all">
                <option value="01">Enero</option>
                <option value="02">Febrero</option>
                <option value="03">Marzo</option>
                <option value="04">Abril</option>
                <option value="05">Mayo</option>
                <option value="06">Junio</option>
                <option value="07">Julio</option>
                <option value="08">Agosto</option>
                <option value="09">Septiembre</option>
                <option value="10">Octubre</option>
                <option value="11">Noviembre</option>
                <option value="12">Diciembre</option>
              </select>
            </div>
            <div>
              <label class="block text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 mb-2 ml-1">Año</label>
              <select v-model="syncParams.anio" class="w-full h-12 rounded-2xl bg-slate-50 dark:bg-white/5 border-0 ring-1 ring-slate-100 dark:ring-white/10 text-sm font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-cyan-500 transition-all">
                <option v-for="y in [2024, 2025, 2026]" :key="y" :value="y">{{ y }}</option>
              </select>
            </div>
          </div>

          <div class="p-4 rounded-2xl bg-brand-500/5 border border-brand-500/20">
            <p class="text-[10px] font-bold text-brand-600 dark:text-brand-400/80 leading-relaxed">
              <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              Esta acción consultará cada factura directamente en el portal del SAT. Puede tardar unos segundos dependiendo del volumen de facturas.
            </p>
          </div>

          <div class="mt-8 flex gap-3">
            <button @click="showSyncModal = false" :disabled="isSyncing" class="flex-1 py-4 rounded-2xl text-[11px] font-black uppercase tracking-wide bg-slate-100 dark:bg-white/5 text-slate-500 dark:text-slate-400 hover:bg-slate-200 transition-all">Cerrar</button>
            <button @click="syncSatByMonth" :disabled="isSyncing" class="flex-1 py-4 rounded-2xl text-[11px] font-black uppercase tracking-wide bg-cyan-600 text-white shadow-xl shadow-cyan-900/40 hover:bg-cyan-500 transition-all flex items-center justify-center gap-2">
              <span v-if="isSyncing">Sincronizando...</span>
              <span v-else>Iniciar Validación</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Resultados de Sincronización SAT -->
    <div v-if="showResultModal" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-md" @click="showResultModal = false"></div>
      <div class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl p-8 border border-slate-100 dark:border-white/10 animate-zoomIn max-h-[90vh] overflow-hidden flex flex-col">
        <div class="flex flex-col items-center text-center mb-8">
          <div :class="['w-16 h-16 rounded-[2rem] flex items-center justify-center mb-4', syncResults.success ? 'bg-emerald-500/10 text-emerald-500' : 'bg-brand-500/10 text-brand-500']">
            <svg v-if="syncResults.success" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <svg v-else class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
          </div>
          <h2 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Resultado de Validación</h2>
          <p class="text-sm text-slate-400 dark:text-slate-500 mt-1 font-bold">{{ syncResults.message }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-6">
          <div class="p-4 rounded-2xl bg-slate-50 dark:bg-white/5 border border-slate-100 dark:border-white/5 text-center">
            <span class="block text-[10px] font-black uppercase text-slate-400 mb-1">Actualizados</span>
            <span class="text-2xl font-black text-emerald-500">{{ syncResults.updatedCount }}</span>
          </div>
          <div class="p-4 rounded-2xl bg-slate-50 dark:bg-white/5 border border-slate-100 dark:border-white/5 text-center">
            <span class="block text-[10px] font-black uppercase text-slate-400 mb-1">Con errores</span>
            <span class="text-2xl font-black text-brand-500">{{ syncResults.failedCount }}</span>
          </div>
        </div>

        <div v-if="syncResults.failedCount > 0" class="flex-1 overflow-hidden flex flex-col mb-6">
          <label class="block text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 mb-2 ml-1">Detalle de fallos</label>
          <div class="flex-1 overflow-y-auto rounded-2xl border border-slate-100 dark:border-white/10 bg-slate-50/50 dark:bg-black/20 custom-scrollbar">
            <table class="w-full text-left">
              <thead class="sticky top-0 bg-slate-100 dark:bg-slate-800 text-[10px] font-black uppercase text-slate-400">
                <tr>
                  <th class="p-3">Folio</th>
                  <th class="p-3">UUID</th>
                  <th class="p-3 text-right">Error</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                <tr v-for="cfdi in syncResults.failedCfdis" :key="cfdi.uuid" class="text-[11px] font-bold text-slate-600 dark:text-slate-300">
                  <td class="p-3 text-cyan-600 dark:text-cyan-400">{{ cfdi.folio }}</td>
                  <td class="p-3 opacity-50 truncate max-w-[120px]" :title="cfdi.uuid">{{ cfdi.uuid }}</td>
                  <td class="p-3 text-right text-rose-500">{{ cfdi.error }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div v-if="syncResults.success" class="py-6 flex flex-col items-center">
          <div class="w-16 h-16 bg-emerald-500/20 rounded-full flex items-center justify-center animate-pulse mb-3">
            <i class="fas fa-check text-2xl text-emerald-500"></i>
          </div>
          <p class="text-xs font-bold text-slate-400">Todo coincide perfectamente con el SAT</p>
        </div>

        <div class="mt-auto pt-6 border-t border-slate-100 dark:border-white/10 flex gap-3">
          <button 
            @click="showResultModal = false; router.reload({ preserveScroll: true })" 
            class="flex-1 py-4 rounded-2xl text-[11px] font-black uppercase tracking-wide bg-slate-100 dark:bg-white/5 text-slate-500 dark:text-slate-400 hover:bg-slate-200 transition-all"
          >
            Cerrar
          </button>
          <button 
            v-if="syncResults.failedCount > 0"
            @click="eliminarFallidos" 
            class="flex-1 py-4 rounded-2xl text-[11px] font-black uppercase tracking-wide bg-rose-600 text-white shadow-xl shadow-rose-900/40 hover:bg-rose-500 transition-all flex items-center justify-center gap-2 active:scale-95"
          >
            <i class="fas fa-trash-alt"></i>
            Eliminar Erróneos
          </button>
          <button 
            v-else
            @click="showResultModal = false; router.reload({ preserveScroll: true })" 
            class="flex-1 py-4 rounded-2xl text-[11px] font-black uppercase tracking-wide bg-slate-800 dark:bg-white text-white dark:text-slate-900 shadow-xl hover:opacity-90 transition-all active:scale-95"
          >
            Entendido
          </button>
        </div>
      </div>
    </div>

  </AppLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }

.animate-fadeIn { animation: fadeIn 0.3s ease-out; }
.animate-slideDown { animation: slideDown 0.3s ease-out; }
.animate-zoomIn { animation: zoomIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
@keyframes zoomIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }

</style>
