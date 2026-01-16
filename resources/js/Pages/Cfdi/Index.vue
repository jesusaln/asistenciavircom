<script setup>
import ModalCrearCuentaCfdi from '@/Components/ModalCrearCuentaCfdi.vue'
import { ref, watch, onMounted, onUnmounted, computed } from 'vue'
import { router, Head } from '@inertiajs/vue3'
import axios from 'axios'
import Swal from 'sweetalert2'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import CfdiPreviewModal from '@/Pages/Cfdi/Partials/CfdiPreviewModal.vue'
import CfdiSecondaryModals from '@/Pages/Cfdi/Partials/CfdiSecondaryModals.vue'

defineOptions({ layout: AppLayout })

const showModalCrearCuenta = ref(false)
const cfdiParaCrearCuenta = ref(null)

const abrirModalCrearCuenta = (cfdi) => {
    cfdiParaCrearCuenta.value = cfdi
    showModalCrearCuenta.value = true
}

const onCuentaCreated = (cuenta) => {
    router.reload({ only: ['cfdis'] })
}

const props = defineProps({
    cfdis: {
        type: Object,
        default: () => ({ data: [], links: [] })
    },
    contadores: {
        type: Object,
        default: () => ({ total: 0, emitidos: 0, recibidos: 0 })
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    descargasMasivas: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({ ingresos: 0, egresos: 0, pagos: 0, count: 0 })
    }
})

const notyf = new Notyf({
    duration: 4000,
    position: { x: 'right', y: 'top' },
    types: [
        { type: 'success', background: '#10b981', icon: false },
        { type: 'error', background: '#ef4444', icon: false }
    ]
})

const filters = ref({
    direccion: props.filters.direccion || '',
    search: props.filters.search || '',
    tipo_comprobante: props.filters.tipo_comprobante || '',
    estatus: props.filters.estatus || '',
    fecha_inicio: props.filters.fecha_inicio || '',
    fecha_fin: props.filters.fecha_fin || '',
    rfc_emisor: props.filters.rfc_emisor || '',
    rfc_receptor: props.filters.rfc_receptor || '',
    serie: props.filters.serie || '',
    folio: props.filters.folio || ''
})

const showAdvancedFilters = ref(false)

const isSendingEmail = ref({})
const showUploadModal = ref(false)
const isUploading = ref(false)
const uploadPreview = ref(null)
const selectedFile = ref(null)
const isDragging = ref(false)
const showDescargaModal = ref(false)
const descargaForm = ref({
    direccion: 'emitido',
    fecha_inicio: '',
    fecha_fin: ''
})
const descargaSending = ref(false)
const isDeletingDescarga = ref({})
const isRevalidatingDescarga = ref({})

const descargasItems = computed(() => {
    if (!Array.isArray(props.descargasMasivas)) {
        return []
    }
    return props.descargasMasivas.filter(Boolean)
})

const setQuickRange = (days) => {
    const end = new Date()
    const start = new Date()
    start.setDate(end.getDate() - (days - 1))
    descargaForm.value.fecha_inicio = formatDateInput(start)
    descargaForm.value.fecha_fin = formatDateInput(end)
}

const setCurrentMonthRange = () => {
    const now = new Date()
    const today = new Date()
    const start = new Date(now.getFullYear(), now.getMonth(), 1)
    let end = new Date(now.getFullYear(), now.getMonth() + 1, 0)
    
    // Cap fecha_fin a hoy si es futura
    if (end > today) {
        end = today
    }
    
    descargaForm.value.fecha_inicio = formatDateInput(start)
    descargaForm.value.fecha_fin = formatDateInput(end)
}

const formatDateInput = (date) => {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
}

// Nuevas funcionalidades
const viewMode = ref('table') // table o grid
const satStatus = ref({})
const isCheckingSat = ref({})
const isCreatingProvider = ref({})

// Revisor de Descargas (Staging)
const showReviewModal = ref(false)
const isLoadingReview = ref(false)
const selectedDescargaParaReview = ref(null)
const documentosStaging = ref([])
const duplicadosStaging = ref([])
const selectedStagingIds = ref([])
const isImportingSeleccionados = ref(false)

// Confirmación de Borrado
const showDeleteConfirmModal = ref(false)
const cfdiParaEliminar = ref(null)
const isDeletingCfdi = ref(false)

// Preview Modal State
const showPreviewModal = ref(false)
const selectedUuid = ref(null)
const xmlContent = ref('')
const isLoadingXml = ref(false)
const parsedCfdiData = ref(null)

const selectedIds = ref([])
const isBulkProcessing = ref(false)
const isBulkDownloading = ref(false)

const cfdiItems = computed(() => {
    if (!props.cfdis || !Array.isArray(props.cfdis.data)) {
        return []
    }
    return props.cfdis.data.filter(Boolean)
})

const toggleSelectAll = (e) => {
    if (e.target.checked) {
        selectedIds.value = cfdiItems.value.map(c => c.id)
    } else {
        selectedIds.value = []
    }
}

const toggleSelect = (id) => {
    const index = selectedIds.value.indexOf(id)
    if (index > -1) {
        selectedIds.value.splice(index, 1)
    } else {
        selectedIds.value.push(id)
    }
}

const isSelected = (id) => selectedIds.value.includes(id)

// Quick View Logic
const expandedRows = ref([])
const toggleRow = (id) => {
    const index = expandedRows.value.indexOf(id)
    if (index > -1) {
        expandedRows.value.splice(index, 1)
    } else {
        expandedRows.value.push(id)
    }
}
const isExpanded = (id) => expandedRows.value.includes(id)

const bulkCheckSat = async () => {
    if (!selectedIds.value.length) return
    isBulkProcessing.value = true
    try {
        const response = await axios.post(route('cfdi.bulk-check-sat'), { ids: selectedIds.value })
        if (response.data.success) {
            notyf.success(response.data.message)
            selectedIds.value = []
            router.reload()
        }
    } catch (e) {
        notyf.error('Error al procesar consulta masiva')
    } finally {
        isBulkProcessing.value = false
    }
}

const bulkSendEmail = async () => {
    if (!selectedIds.value.length) return
    if (!confirm(`¿Deseas enviar ${selectedIds.value.length} comprobantes por correo?`)) return
    
    isBulkProcessing.value = true
    try {
        const response = await axios.post(route('cfdi.bulk-send-email'), { ids: selectedIds.value })
        if (response.data.success) {
            notyf.success(response.data.message)
            selectedIds.value = []
        }
    } catch (e) {
        notyf.error('Error al enviar correos masivos')
    } finally {
        isBulkProcessing.value = false
    }
}

const bulkDownloadZip = async () => {
    if (!selectedIds.value.length) return
    isBulkDownloading.value = true
    
    try {
        const response = await axios.post(route('cfdi.bulk-download'), { ids: selectedIds.value })
        if (response.data.success && response.data.url) {
            window.location.href = response.data.url
            notyf.success('Descarga iniciada')
            selectedIds.value = []
        } else {
            notyf.error(response.data.message || 'Error al generar ZIP')
        }
    } catch (e) {
        notyf.error('Error al solicitar descarga ZIP')
    } finally {
        isBulkDownloading.value = false
    }
}

// XML Syntax Highlighting

// Parse CFDI XML to structured data - robust (namespaces)
const parseCfdiXml = (xmlString) => {
    if (!xmlString) return null
    try {
        const cleanXml = xmlString.replace(/^\uFEFF/, '').trim()
        const parser = new DOMParser()
        const xmlDoc = parser.parseFromString(cleanXml, 'text/xml')
        const parseError = xmlDoc.getElementsByTagName('parsererror')[0]
        if (parseError) return null

        const getByLocalName = (name) => {
            const all = xmlDoc.getElementsByTagName('*')
            for (const node of all) {
                if (node.localName === name) return node
            }
            return null
        }

        const getAllByLocalName = (name) => {
            const nodes = []
            const all = xmlDoc.getElementsByTagName('*')
            for (const node of all) {
                if (node.localName === name) nodes.push(node)
            }
            return nodes
        }

        const getAttr = (el, attr) => el?.getAttribute(attr) || ''
        const comprobante = getByLocalName('Comprobante')
        if (!comprobante) return null

        const emisor = getByLocalName('Emisor')
        const receptor = getByLocalName('Receptor')
        const timbre = getByLocalName('TimbreFiscalDigital')
        const impuestos = getByLocalName('Impuestos')
        const conceptos = getAllByLocalName('Concepto')
        const traslados = getAllByLocalName('Traslado')
        const retenciones = getAllByLocalName('Retencion')
        const relacionados = getAllByLocalName('CfdiRelacionado')
        const pagos = getAllByLocalName('Pago')

        const docsRelacionados = []
        const doctos = getAllByLocalName('DoctoRelacionado')
        doctos.forEach(doc => {
            docsRelacionados.push({
                idDocumento: getAttr(doc, 'IdDocumento'),
                serie: getAttr(doc, 'Serie'),
                folio: getAttr(doc, 'Folio'),
                moneda: getAttr(doc, 'MonedaDR'),
                metodoPago: getAttr(doc, 'MetodoDePagoDR'),
                numParcialidad: getAttr(doc, 'NumParcialidad'),
                impSaldoAnt: getAttr(doc, 'ImpSaldoAnt'),
                impPagado: getAttr(doc, 'ImpPagado'),
                impSaldoInsoluto: getAttr(doc, 'ImpSaldoInsoluto'),
                objetoImp: getAttr(doc, 'ObjetoImpDR')
            })
        })

        return {
            version: getAttr(comprobante, 'Version'),
            serie: getAttr(comprobante, 'Serie'),
            folio: getAttr(comprobante, 'Folio'),
            fecha: getAttr(comprobante, 'Fecha'),
            formaPago: getAttr(comprobante, 'FormaPago'),
            metodoPago: getAttr(comprobante, 'MetodoPago'),
            tipoComprobante: getAttr(comprobante, 'TipoDeComprobante'),
            moneda: getAttr(comprobante, 'Moneda'),
            tipoCambio: getAttr(comprobante, 'TipoCambio'),
            lugarExpedicion: getAttr(comprobante, 'LugarExpedicion'),
            exportacion: getAttr(comprobante, 'Exportacion'),
            noCertificado: getAttr(comprobante, 'NoCertificado'),
            condicionesPago: getAttr(comprobante, 'CondicionesDePago'),
            descuento: getAttr(comprobante, 'Descuento'),
            subtotal: getAttr(comprobante, 'SubTotal'),
            total: getAttr(comprobante, 'Total'),
            emisor: emisor ? {
                rfc: getAttr(emisor, 'Rfc'),
                nombre: getAttr(emisor, 'Nombre'),
                regimenFiscal: getAttr(emisor, 'RegimenFiscal'),
                facAtrAdquirente: getAttr(emisor, 'FacAtrAdquirente')
            } : null,
            receptor: receptor ? {
                rfc: getAttr(receptor, 'Rfc'),
                nombre: getAttr(receptor, 'Nombre'),
                usoCfdi: getAttr(receptor, 'UsoCFDI'),
                domicilioFiscal: getAttr(receptor, 'DomicilioFiscalReceptor'),
                regimenFiscal: getAttr(receptor, 'RegimenFiscalReceptor'),
                numRegIdTrib: getAttr(receptor, 'NumRegIdTrib'),
                residenciaFiscal: getAttr(receptor, 'ResidenciaFiscal')
            } : null,
            conceptos: conceptos.map(c => ({
                clave: getAttr(c, 'ClaveProdServ'),
                noIdentificacion: getAttr(c, 'NoIdentificacion'),
                cantidad: getAttr(c, 'Cantidad'),
                unidad: getAttr(c, 'ClaveUnidad'),
                unidadNombre: getAttr(c, 'Unidad'),
                descripcion: getAttr(c, 'Descripcion'),
                valorUnitario: getAttr(c, 'ValorUnitario'),
                importe: getAttr(c, 'Importe'),
                descuento: getAttr(c, 'Descuento'),
                objetoImp: getAttr(c, 'ObjetoImp')
            })),
            impuestos: {
                totalImpuestosTrasladados: impuestos ? getAttr(impuestos, 'TotalImpuestosTrasladados') : '',
                totalImpuestosRetenidos: impuestos ? getAttr(impuestos, 'TotalImpuestosRetenidos') : '',
                traslados: traslados.map(t => ({
                    base: getAttr(t, 'Base'),
                    impuesto: getAttr(t, 'Impuesto'),
                    tipoFactor: getAttr(t, 'TipoFactor'),
                    tasaOCuota: getAttr(t, 'TasaOCuota'),
                    importe: getAttr(t, 'Importe')
                })),
                retenciones: retenciones.map(r => ({
                    impuesto: getAttr(r, 'Impuesto'),
                    importe: getAttr(r, 'Importe')
                }))
            },
            timbre: timbre ? {
                uuid: getAttr(timbre, 'UUID'),
                fechaTimbrado: getAttr(timbre, 'FechaTimbrado'),
                rfcProvCertif: getAttr(timbre, 'RfcProvCertif'),
                noCertificadoSAT: getAttr(timbre, 'NoCertificadoSAT'),
                selloCFD: getAttr(timbre, 'SelloCFD'),
                selloSAT: getAttr(timbre, 'SelloSAT')
            } : null,
            pagos: pagos.map(p => ({
                fechaPago: getAttr(p, 'FechaPago'),
                formaDePago: getAttr(p, 'FormaDePagoP'),
                moneda: getAttr(p, 'MonedaP'),
                tipoCambio: getAttr(p, 'TipoCambioP'),
                monto: getAttr(p, 'Monto'),
                numOperacion: getAttr(p, 'NumOperacion'),
                rfcCtaOrden: getAttr(p, 'RfcEmisorCtaOrd'),
                ctaOrdenante: getAttr(p, 'CtaOrdenante'),
                rfcCtaBenef: getAttr(p, 'RfcEmisorCtaBen'),
                ctaBeneficiario: getAttr(p, 'CtaBeneficiario')
            })),
            relacionados: relacionados.map(r => ({
                tipoRelacion: getAttr(r.parentElement, 'TipoRelacion') || '',
                uuid: getAttr(r, 'UUID')
            })),
            docsRelacionados
        }
    } catch (e) { return null }
}
const formatMoney = (val) => {
    const n = parseFloat(val)
    return isNaN(n) ? (val || '-') : n.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })
}

const checkSatStatus = async (cfdi) => {
    isCheckingSat.value[cfdi.id] = true
    try {
        const response = await axios.post(route('cfdi.check-sat', cfdi.id))
        if (response.data.success) {
            satStatus.value[cfdi.id] = {
                estado: response.data.estado,
                es_cancelable: response.data.es_cancelable
            }
            notyf.success(`Estado SAT: ${response.data.estado}`)
            if (response.data.estado.toLowerCase() === 'cancelado') {
                router.reload()
            }
        } else {
            notyf.error(response.data.message || 'Error al consultar SAT')
        }
    } catch (e) {
        notyf.error(e.response?.data?.message || 'Error de conexión con el SAT')
    } finally {
        isCheckingSat.value[cfdi.id] = false
    }
}

const createProvider = async (cfdiId) => {
    isCreatingProvider.value[cfdiId] = true
    try {
        const response = await axios.post(route('cfdi.create-provider', cfdiId))
        if (response.data.success) {
            notyf.success('Proveedor creado correctamente')
            if (uploadPreview.value) {
                uploadPreview.value.emisor_exists = true
            }
            router.reload()
        } else {
            notyf.error(response.data.message || 'Error al crear proveedor')
        }
    } catch (e) {
        notyf.error(e.response?.data?.message || 'Error al conectar con el servidor')
    } finally {
        isCreatingProvider.value[cfdiId] = false
    }
}

// Watch para filtros con debounce simple
let timeout = null
watch(filters, (newFilters) => {
    if (timeout) clearTimeout(timeout)
    timeout = setTimeout(() => {
        router.get(route('cfdi.index'), newFilters, {
            preserveState: true,
            replace: true
        })
    }, 500)
}, { deep: true })

// Polling
const tieneDescargasActivas = computed(() => {
    return descargasItems.value.some(d => ['solicitando', 'pendiente', 'verificando'].includes(d.status))
})

let pollingInterval = null

const startPolling = () => {
    if (pollingInterval) return
    pollingInterval = setInterval(() => {
        if (tieneDescargasActivas.value) {
            router.reload({ 
                only: ['descargasMasivas'], 
                preserveState: true,
                preserveScroll: true
            })
        } else {
            stopPolling()
        }
    }, 3000)
}

const stopPolling = () => {
    if (pollingInterval) {
        clearInterval(pollingInterval)
        pollingInterval = null
    }
}

watch(tieneDescargasActivas, (newVal) => {
    if (newVal) startPolling()
    else stopPolling()
})

// Watch for download completion to show SweetAlert automatically
watch(() => props.descargasMasivas, (newDescargas, oldDescargas) => {
    if (!Array.isArray(newDescargas) || !Array.isArray(oldDescargas)) return
    
    const estadosActivos = ['solicitando', 'pendiente', 'verificando', 'descargando']
    const estadosCompletados = ['finished', 'completado', 'completo', 'error']
    
    newDescargas.forEach(descarga => {
        if (!descarga?.id) return
        
        const anterior = oldDescargas.find(d => d.id === descarga.id)
        if (!anterior) return
        
        // Si pasó de estado activo a completado, mostrar resumen
        const eraActivo = estadosActivos.includes(anterior.status)
        const ahoraCompletado = estadosCompletados.includes(descarga.status)
        
        if (eraActivo && ahoraCompletado) {
            // Pequeño delay para asegurar que los datos estén actualizados
            setTimeout(() => mostrarResumenDescarga(descarga), 500)
        }
    })
}, { deep: true })

onMounted(() => {
    if (tieneDescargasActivas.value) startPolling()
})

onUnmounted(() => {
    stopPolling()
})

const currencyFormatter = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' })
const formatCurrency = (value) => currencyFormatter.format(value || 0)
const formatDateShort = (date) => {
    if (!date) return '---'
    // Forzar interpretación como fecha local agregando hora
    const dateStr = date.includes('T') ? date : `${date}T00:00:00`
    return new Date(dateStr).toLocaleDateString('es-MX')
}

const formatDateTime = (dateStr) => {
    if (!dateStr) return '---'
    const date = new Date(dateStr)
    return date.toLocaleString('es-MX', { 
        day: '2-digit', 
        month: '2-digit', 
        hour: '2-digit', 
        minute: '2-digit'
    })
}

const verPdf = (uuid) => {
    verXml(uuid)
}

const verPdfStaging = async (doc) => {
    verXmlStaging(doc)
}

const verXml = async (uuid) => {
    selectedUuid.value = uuid
    showPreviewModal.value = true
    xmlContent.value = ''
    parsedCfdiData.value = null
    isLoadingXml.value = true
    
    try {
        const response = await axios.get(route('cfdi.xml', { uuid, inline: 1 }), {
            responseType: 'text',
            headers: { Accept: 'application/xml' }
        })
        xmlContent.value = typeof response.data === 'string' ? response.data : JSON.stringify(response.data, null, 2)
        parsedCfdiData.value = parseCfdiXml(xmlContent.value)
    } catch (e) {
        notyf.error('Error al cargar el XML')
        showPreviewModal.value = false
    } finally {
        isLoadingXml.value = false
    }
}

const verXmlStaging = (doc) => {
    selectedUuid.value = doc.uuid
    showPreviewModal.value = true
    xmlContent.value = doc.xml_content
    parsedCfdiData.value = parseCfdiXml(xmlContent.value)
    isLoadingXml.value = false
}

const getTipoBadge = (tipo) => {
    // Si tipo es vacío o nulo, asumir que es Ingreso para que no salga "Otro"
    if (!tipo) tipo = 'I'
    
    // Normalizar a mayúsculas por si acaso
    tipo = tipo.toUpperCase()

    const map = {
        'I': { label: 'Ingreso', color: 'bg-emerald-100 text-emerald-700 border-emerald-200' },
        'E': { label: 'Egreso', color: 'bg-red-100 text-red-700 border-red-200' },
        'P': { label: 'Pago', color: 'bg-blue-100 text-blue-700 border-blue-200' },
        'N': { label: 'Nómina', color: 'bg-amber-100 text-amber-700 border-amber-200' },
        'T': { label: 'Traslado', color: 'bg-indigo-100 text-indigo-700 border-indigo-200' }
    }
    return map[tipo] || { label: 'Otro', color: 'bg-gray-100 text-gray-700 border-gray-200' }
}

const abrirRevisor = async (descarga) => {
    selectedDescargaParaReview.value = descarga
    showReviewModal.value = true
    documentosStaging.value = []
    duplicadosStaging.value = []
    selectedStagingIds.value = []
    isLoadingReview.value = true
    
    try {
        const response = await axios.get(route('cfdi.descarga-masiva.detalles', descarga.id))
        if (response.data.success) {
            const detalles = Array.isArray(response.data.detalles)
                ? response.data.detalles.filter(Boolean)
                : []
            const duplicados = Array.isArray(response.data.duplicados)
                ? response.data.duplicados.filter(Boolean)
                : []
            documentosStaging.value = detalles
            duplicadosStaging.value = duplicados
            // Seleccionar solo los no importados por defecto
            selectedStagingIds.value = detalles.filter(d => !d.importado).map(d => d.id)
        }
    } catch (e) {
        notyf.error('Error al cargar detalles de la descarga')
    } finally {
        isLoadingReview.value = false
    }
}

const toggleSeleccionStaging = (id) => {
    const index = selectedStagingIds.value.indexOf(id)
    if (index > -1) {
        selectedStagingIds.value.splice(index, 1)
    } else {
        selectedStagingIds.value.push(id)
    }
}

const seleccionarTodoStaging = () => {
    selectedStagingIds.value = documentosStaging.value.map(d => d.id)
}

const deseleccionarTodoStaging = () => {
    selectedStagingIds.value = []
}

const importarSeleccionados = async () => {
    if (selectedStagingIds.value.length === 0) {
        notyf.error('Selecciona al menos un documento para importar')
        return
    }
    
    isImportingSeleccionados.value = true
    try {
        const response = await axios.post(route('cfdi.descarga-masiva.importar'), {
            ids: selectedStagingIds.value
        })
        if (response.data.success) {
            notyf.success(response.data.message)
            showReviewModal.value = false
            router.reload()
        }
    } catch (e) {
        notyf.error('Error al importar documentos')
    } finally {
        isImportingSeleccionados.value = false
    }
}

// Métodos para Borrado
const confirmarEliminacion = (cfdi) => {
    cfdiParaEliminar.value = cfdi
    showDeleteConfirmModal.value = true
}

const ejecutarEliminacion = async () => {
    if (!cfdiParaEliminar.value) return
    
    isDeletingCfdi.value = true
    try {
        const response = await axios.delete(route('cfdi.destroy', cfdiParaEliminar.value.id))
        if (response.data.success) {
            notyf.success('CFDI y archivo XML eliminados correctamente')
            showDeleteConfirmModal.value = false
            router.reload()
        } else {
            notyf.error(response.data.message || 'No se pudo eliminar el documento')
        }
    } catch (e) {
        notyf.error(e.response?.data?.message || 'Error al intentar eliminar')
    } finally {
        isDeletingCfdi.value = false
        cfdiParaEliminar.value = null
    }
}

const enviarCorreo = async (uuid) => {
    if (!confirm('¿Deseas enviar los archivos por correo al cliente?')) return
    
    isSendingEmail.value[uuid] = true
    try {
        const response = await axios.post(route('cfdi.enviar-correo', uuid))
        if (response.data.success) {
            notyf.success('Correo enviado correctamente')
        } else {
            notyf.error(response.data.message || 'Error al enviar correo')
        }
    } catch (e) {
        notyf.error('Error al conectar con el servidor')
    } finally {
        isSendingEmail.value[uuid] = false
    }
}

const getStatusBadgeClass = (status) => {
    switch (status?.toLowerCase()) {
        case 'timbrado':
        case 'vigente':
            return 'bg-emerald-50 text-emerald-700 border-emerald-100'
        case 'cancelado':
            return 'bg-red-50 text-red-700 border-red-100'
        default:
            return 'bg-gray-50 text-gray-700 border-gray-100'
    }
}


const toggleSort = (field) => {
    if (filters.value.sort === field) {
        filters.value.sort_dir = filters.value.sort_dir === 'asc' ? 'desc' : 'asc'
    } else {
        filters.value.sort = field
        filters.value.sort_dir = 'desc'
    }
}

const getSortIndicator = (field) => {
    if (filters.value.sort !== field) return ''
    return filters.value.sort_dir === 'asc' ? '^' : 'v'
}

const getTipoLabel = (tipo) => {
    const tipos = {
        'I': 'Factura',
        'P': 'Pago (REP)',
        'E': 'Egreso',
        'N': 'Nómina',
        'T': 'Traslado'
    }
    return tipos[tipo] || tipo
}

const getTipoBadgeClass = (tipo) => {
    switch (tipo) {
        case 'I': return 'bg-blue-50 text-blue-700 border-blue-100'
        case 'P': return 'bg-purple-50 text-purple-700 border-purple-100'
        case 'E': return 'bg-orange-50 text-orange-700 border-orange-100'
        default: return 'bg-gray-50 text-gray-600 border-gray-100'
    }
}

// Handler para cambio de página
const handlePageChange = (page) => {
    router.get(route('cfdi.index'), { ...filters.value, page }, {
        preserveState: true,
        preserveScroll: true
    })
}

// Funciones de dirección
const setDireccion = (dir) => {
    filters.value.direccion = dir
}

// Funciones de Upload
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
    if (file && file.name.endsWith('.xml')) {
        await previewXmlFile(file)
    } else {
        notyf.error('Por favor selecciona un archivo XML')
    }
}

const handleFileSelect = async (e) => {
    const file = e.target.files[0]
    if (file) {
        await previewXmlFile(file)
    }
}

const previewXmlFile = async (file) => {
    selectedFile.value = file
    isUploading.value = true
    
    try {
        const formData = new FormData()
        formData.append('xml_file', file)
        
        const response = await axios.post(route('cfdi.preview-xml'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        
        if (response.data.success) {
            uploadPreview.value = response.data.data
        } else {
            notyf.error(response.data.message || 'Error al procesar XML')
            selectedFile.value = null
        }
    } catch (e) {
        notyf.error(e.response?.data?.message || 'Error al procesar XML')
        selectedFile.value = null
    } finally {
        isUploading.value = false
    }
}

const uploadXml = async () => {
    if (!selectedFile.value) return
    
    isUploading.value = true
    
    try {
        const formData = new FormData()
        formData.append('xml_file', selectedFile.value)
        
        const response = await axios.post(route('cfdi.store'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        
        if (response.data.success) {
            notyf.success('CFDI cargado correctamente')
            closeUploadModal()
            router.reload()
        } else {
            notyf.error(response.data.message || 'Error al cargar CFDI')
        }
    } catch (e) {
        notyf.error(e.response?.data?.message || 'Error al cargar CFDI')
    } finally {
        isUploading.value = false
    }
}

const closeUploadModal = () => {
    showUploadModal.value = false
    selectedFile.value = null
    uploadPreview.value = null
}


const resetUploadPreview = () => {
    selectedFile.value = null
    uploadPreview.value = null
}

const getDireccionBadgeClass = (direccion) => {
    return direccion === 'recibido' 
        ? 'bg-violet-50 text-violet-700 border-violet-200' 
        : 'bg-emerald-50 text-emerald-700 border-emerald-200'
}

// Verificar si se puede eliminar un CFDI (solo si no tiene venta asociada)
const canDelete = (cfdi) => {
    return !cfdi.venta_id
}

const isDeleting = ref({})

const deleteCfdi = async (cfdi) => {
    if (!canDelete(cfdi)) {
        notyf.error('No se puede eliminar este CFDI porque está asociado a una venta')
        return
    }
    
    if (!confirm(`¿Estás seguro de eliminar el CFDI ${cfdi.uuid}?`)) {
        return
    }
    
    isDeleting.value[cfdi.id] = true
    
    try {
        const response = await axios.delete(route('cfdi.destroy', cfdi.id))
        if (response.data.success) {
            notyf.success('CFDI eliminado correctamente')
            router.reload()
        } else {
            notyf.error(response.data.message || 'Error al eliminar CFDI')
        }
    } catch (e) {
        notyf.error(e.response?.data?.message || 'Error al eliminar CFDI')
    } finally {
        isDeleting.value[cfdi.id] = false
    }
}

const solicitarDescarga = async () => {
    if (!descargaForm.value.fecha_inicio || !descargaForm.value.fecha_fin) {
        notyf.error('Selecciona un rango de fechas')
        return
    }

    descargaSending.value = true
    try {
        const response = await axios.post(route('cfdi.descarga-masiva'), descargaForm.value)
        if (response.data.success) {
            notyf.success(response.data.message || 'Solicitud enviada')
            showDescargaModal.value = false
            router.reload({ preserveState: true })
        } else {
            notyf.error(response.data.message || 'Error al solicitar descarga')
        }
    } catch (e) {
        notyf.error(e.response?.data?.message || 'Error al solicitar descarga')
    } finally {
        descargaSending.value = false
    }
}

const verificarDescarga = async (id) => {
    try {
        const response = await axios.post(route('cfdi.descarga-masiva.verificar', id))
        if (response.data.success) {
            notyf.success(response.data.message || 'Consulta en proceso')
            router.reload({ preserveState: true })
        } else {
            notyf.error(response.data.message || 'Error al consultar')
        }
    } catch (e) {
        notyf.error(e.response?.data?.message || 'Error al consultar')
    }
}

// Reintentar descarga manualmente (para estados pausado/esperando)
const reintentarDescargaManual = async (descarga) => {
    const confirmacion = await Swal.fire({
        title: '¿Reintentar descarga?',
        html: `
            <div class="text-left text-sm">
                <p class="text-gray-600 mb-3">Esto intentará nuevamente la descarga del SAT.</p>
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                    <p class="text-amber-700 text-xs">
                        ⚠️ Si el SAT sigue bloqueando, el sistema pausará de nuevo.
                        <br>Intenta esperar al menos 4 horas entre reintentos.
                    </p>
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, Reintentar',
        cancelButtonText: 'Cancelar'
    })

    if (!confirmacion.isConfirmed) return

    try {
        const response = await axios.post(route('cfdi.descarga-masiva.reintentar', descarga.id))
        if (response.data.success) {
            notyf.success('Reintento iniciado. Se procesará en segundo plano.')
            router.reload({ preserveState: true })
        } else {
            notyf.error(response.data.message || 'Error al reintentar')
        }
    } catch (e) {
        notyf.error(e.response?.data?.message || 'Error al reintentar')
    }
}

const revalidarDescarga = async (descarga) => {
    if (!confirm('¿Deseas revalidar el estatus SAT de este rango?')) {
        return
    }

    isRevalidatingDescarga.value[descarga.id] = true
    try {
        const response = await axios.post(route('cfdi.descarga-masiva.revalidar', descarga.id))
        if (response.data.success) {
            notyf.success(response.data.message || 'Revalidación en proceso')
            router.reload({ preserveState: true })
        } else {
            notyf.error(response.data.message || 'Error al revalidar')
        }
    } catch (e) {
        notyf.error(e.response?.data?.message || 'Error al revalidar')
    } finally {
        isRevalidatingDescarga.value[descarga.id] = false
    }
}

const eliminarDescarga = async (descarga) => {
    if (!confirm('¿Deseas eliminar este registro de descarga masiva?')) {
        return
    }

    if (!descarga?.id) {
        notyf.error('No se pudo identificar la descarga para eliminar')
        return
    }

    isDeletingDescarga.value[descarga.id] = true
    try {
        const response = await axios.delete(route('cfdi.descarga-masiva.eliminar', { descarga: descarga.id }))
        if (response.data.success) {
            notyf.success(response.data.message || 'Descarga eliminada')
            router.reload({ preserveState: true })
        } else {
            notyf.error(response.data.message || 'Error al eliminar')
        }
    } catch (e) {
        notyf.error(e.response?.data?.message || 'Error al eliminar')
    } finally {
        isDeletingDescarga.value[descarga.id] = false
    }
}

// Mostrar resumen detallado de una descarga completada con SweetAlert
const mostrarResumenDescarga = (descarga) => {
    const total = descarga.total_cfdis || 0
    const nuevos = descarga.imported_cfdis || descarga.inserted_cfdis || 0
    const duplicados = descarga.duplicate_cfdis || 0
    const errores = descarga.error_cfdis || 0
    const pendientes = descarga.pending_cfdis || 0
    
    // Obtener listas de errores/duplicados del JSON si existe
    const errorsData = descarga.errors || {}
    const listaErrores = errorsData.errors || []
    const listaDuplicados = errorsData.duplicates || []
    
    // Construir HTML del resumen
    let html = `
        <div class="text-left">
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-gray-800">${total}</div>
                    <div class="text-xs text-gray-500 uppercase">Total procesados</div>
                </div>
                <div class="bg-emerald-50 rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-emerald-600">${nuevos}</div>
                    <div class="text-xs text-emerald-600 uppercase">✅ Nuevos insertados</div>
                </div>
                <div class="bg-amber-50 rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-amber-600">${duplicados}</div>
                    <div class="text-xs text-amber-600 uppercase">⚠️ Ya existían</div>
                </div>
                <div class="bg-red-50 rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-red-600">${errores}</div>
                    <div class="text-xs text-red-600 uppercase">❌ Errores</div>
                </div>
            </div>
    `
    
    // Mostrar lista de duplicados (máximo 10)
    if (listaDuplicados.length > 0) {
        html += `
            <div class="border-t border-gray-200 pt-3 mt-3">
                <p class="text-sm font-bold text-amber-700 mb-2">⚠️ CFDIs Duplicados (ya en ADD):</p>
                <div class="max-h-32 overflow-y-auto bg-amber-50 rounded p-2">
                    <ul class="text-xs text-gray-600 space-y-1">
        `
        const maxDuplicados = Math.min(listaDuplicados.length, 10)
        for (let i = 0; i < maxDuplicados; i++) {
            const uuid = listaDuplicados[i]
            html += `<li class="font-mono truncate">• ${uuid}</li>`
        }
        if (listaDuplicados.length > 10) {
            html += `<li class="text-amber-600 font-medium">... y ${listaDuplicados.length - 10} más</li>`
        }
        html += `
                    </ul>
                </div>
            </div>
        `
    }
    
    // Mostrar lista de errores
    if (listaErrores.length > 0) {
        html += `
            <div class="border-t border-gray-200 pt-3 mt-3">
                <p class="text-sm font-bold text-red-700 mb-2">❌ Errores encontrados:</p>
                <div class="max-h-32 overflow-y-auto bg-red-50 rounded p-2">
                    <ul class="text-xs text-gray-600 space-y-1">
        `
        const maxErrores = Math.min(listaErrores.length, 10)
        for (let i = 0; i < maxErrores; i++) {
            html += `<li class="text-red-600">• ${listaErrores[i]}</li>`
        }
        if (listaErrores.length > 10) {
            html += `<li class="text-red-600 font-medium">... y ${listaErrores.length - 10} más</li>`
        }
        html += `
                    </ul>
                </div>
            </div>
        `
    }
    
    // Mostrar nota si hay pendientes
    if (pendientes > 0) {
        html += `
            <div class="border-t border-gray-200 pt-3 mt-3">
                <p class="text-sm text-orange-600">
                    📋 <strong>${pendientes}</strong> CFDIs están pendientes de importar. 
                    Usa el botón "Revisar Docs" para importarlos al ADD.
                </p>
            </div>
        `
    }
    
    html += '</div>'
    
    const tieneErrores = errores > 0 || listaErrores.length > 0
    const direccionLabel = descarga.direccion === 'emitido' ? 'Emitidos' : 'Recibidos'
    
    Swal.fire({
        title: tieneErrores ? '⚠️ Descarga Completada con Errores' : '✅ Descarga Masiva Completada',
        html: html,
        icon: tieneErrores ? 'warning' : 'success',
        width: 500,
        confirmButtonText: pendientes > 0 ? 'Revisar Documentos' : 'Cerrar',
        confirmButtonColor: pendientes > 0 ? '#10b981' : '#3b82f6',
        showCancelButton: pendientes > 0,
        cancelButtonText: 'Cerrar',
        footer: `<span class="text-xs text-gray-400">${direccionLabel} | ${formatDateShort(descarga.fecha_inicio)} → ${formatDateShort(descarga.fecha_fin)}</span>`
    }).then((result) => {
        if (result.isConfirmed && pendientes > 0) {
            abrirRevisor(descarga)
        }
    })
}

// Track previous statuses to detect completion
const previousDescargaStatuses = ref({})
</script>

<template>
    <Head title="ADD - Administrador de Documentos Digitales" />

    <div class="py-12 bg-gray-50 min-h-screen transition-colors duration-500">
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
                    <!-- Toggle Vista -->
                    <div class="flex bg-white rounded-2xl p-1 border border-gray-100 shadow-sm mr-2">
                        <button @click="viewMode = 'table'" 
                                :class="['p-2 rounded-xl transition-all', viewMode === 'table' ? 'bg-blue-50 text-blue-600 shadow-sm' : 'text-gray-400 hover:text-gray-600']">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
                        </button>
                        <button @click="viewMode = 'grid'" 
                                :class="['p-2 rounded-xl transition-all', viewMode === 'grid' ? 'bg-blue-50 text-blue-600 shadow-sm' : 'text-gray-400 hover:text-gray-600']">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                        </button>
                    </div>

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
                                 filters.direccion === '' ? 'bg-gray-900 text-white shadow-lg shadow-blue-500/20' : 'bg-white text-gray-600 hover:bg-gray-50 border-gray-200']">
                    Todos
                    <span class="ml-2 px-2 py-0.5 rounded-lg text-xs" :class="filters.direccion === '' ? 'bg-white/20' : 'bg-gray-100'">{{ contadores.total }}</span>
                </button>
                <button @click="setDireccion('emitido')" 
                        :class="['px-5 py-2.5 rounded-xl font-bold text-sm transition-all flex items-center gap-2 border', 
                                 filters.direccion === 'emitido' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/20' : 'bg-white text-gray-600 hover:bg-gray-50 border-gray-200']">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                    Emitidos
                    <span class="px-2 py-0.5 rounded-lg text-xs" :class="filters.direccion === 'emitido' ? 'bg-white/20' : 'bg-gray-100'">{{ contadores.emitidos }}</span>
                </button>
                <button @click="setDireccion('recibido')" 
                        :class="['px-5 py-2.5 rounded-xl font-bold text-sm transition-all flex items-center gap-2 border', 
                                 filters.direccion === 'recibido' ? 'bg-violet-600 text-white shadow-lg shadow-violet-600/20' : 'bg-white text-gray-600 hover:bg-gray-50 border-gray-200']">
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
                                        : 'bg-gray-50 border-gray-100']">
                        <!-- Encabezado con dirección y fechas -->
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                            <div class="flex flex-col">
                                <div class="flex items-center gap-2">
                                    <span :class="['px-3 py-1 rounded-lg text-xs font-black uppercase tracking-widest',
                                                   descarga.direccion === 'emitido' ? 'bg-emerald-100 text-emerald-700' : 'bg-violet-100 text-violet-700']">
                                        {{ descarga.direccion === 'emitido' ? '↑ Emitidos' : '↓ Recibidos' }}
                                    </span>
                                    <span :class="['px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider',
                                                   descarga.status === 'completado' || descarga.status === 'finished' || descarga.status === 'completo' ? 'bg-emerald-500 text-white shadow-sm shadow-emerald-200' :
                                                   descarga.status === 'error' ? 'bg-red-100 text-red-700' :
                                                   descarga.status === 'esperando' ? 'bg-amber-500 text-white animate-pulse' :
                                                   descarga.status === 'pausado' ? 'bg-red-600 text-white' :
                                                   'bg-blue-100 text-blue-700 animate-pulse']">
                                        {{ descarga.status === 'completado' || descarga.status === 'finished' || descarga.status === 'completo' 
                                            ? '✓ YA DESCARGADOS' 
                                            : descarga.status === 'esperando' 
                                                ? '⏳ ESPERANDO' 
                                                : descarga.status === 'pausado'
                                                    ? '⏸️ PAUSADO'
                                                    : descarga.status }}
                                    </span>
                                    <!-- Indicador de reintentos -->
                                    <span v-if="descarga.retry_count > 0" class="px-2 py-0.5 rounded text-[10px] font-black bg-gray-200 text-gray-600">
                                        Intento {{ descarga.retry_count }}/{{ descarga.max_retries || 3 }}
                                    </span>
                                </div>
                                <span class="text-sm text-gray-600 mt-1">
                                    📅 {{ formatDateShort(descarga.fecha_inicio) }} → {{ formatDateShort(descarga.fecha_fin) }}
                                </span>
                            </div>
                            
                            <!-- Indicador de actividad en tiempo real -->
                            <div v-if="['solicitando', 'pendiente', 'verificando', 'descargando'].includes(descarga.status)" 
                                 class="flex items-center gap-2 text-blue-600">
                                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span class="text-xs font-bold animate-pulse">Procesando...</span>
                            </div>
                            <!-- Indicador de espera para reintento -->
                            <div v-else-if="descarga.status === 'esperando' && descarga.next_retry_at" 
                                 class="flex items-center gap-2 text-amber-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-xs font-bold">Próximo intento: {{ formatDateTime(descarga.next_retry_at) }}</span>
                            </div>
                        </div>

                        <!-- Mensaje Amigable para Límites del SAT -->
                        <div v-if="descarga.mensaje_usuario || descarga.limite_tipo" 
                             :class="['rounded-xl p-4 flex items-start gap-3',
                                      descarga.limite_tipo === 'por_vida' ? 'bg-red-100 border border-red-200' : 'bg-amber-100 border border-amber-200']">
                            <span class="text-2xl">{{ descarga.limite_tipo === 'por_vida' ? '🚫' : '⏳' }}</span>
                            <div class="flex-1">
                                <p :class="['text-sm font-medium', descarga.limite_tipo === 'por_vida' ? 'text-red-800' : 'text-amber-800']">
                                    {{ descarga.mensaje_usuario || descarga.mensaje_amigable || 'Límite del SAT detectado' }}
                                </p>
                                <p v-if="descarga.status === 'esperando'" class="text-xs text-gray-600 mt-1">
                                    El sistema reintentará automáticamente. No necesitas hacer nada.
                                </p>
                                <p v-else-if="descarga.status === 'pausado'" class="text-xs text-red-600 mt-1 font-medium">
                                    Después de varios intentos, el SAT sigue bloqueando. Espera 24-48 horas antes de reintentar.
                                </p>
                            </div>
                        </div>

                        <!-- Barra de Progreso en Tiempo Real -->
                        <div v-if="descarga.total_cfdis > 0 || ['solicitando', 'pendiente', 'verificando', 'descargando'].includes(descarga.status)" 
                             class="w-full">
                            <div class="flex justify-between text-xs font-bold text-gray-500 mb-1">
                                <span>Progreso de Descarga</span>
                                <span v-if="descarga.total_cfdis > 0">
                                    {{ descarga.inserted_cfdis + descarga.duplicate_cfdis }} / {{ descarga.total_cfdis }} procesados
                                </span>
                            </div>
                            <div class="w-full h-3 bg-gray-200 rounded-full overflow-hidden">
                                <div v-if="descarga.total_cfdis > 0"
                                     class="h-full rounded-full transition-all duration-500 ease-out"
                                     :style="{ width: Math.min(100, ((descarga.inserted_cfdis + descarga.duplicate_cfdis) / descarga.total_cfdis) * 100) + '%' }"
                                     :class="['solicitando', 'pendiente', 'verificando', 'descargando'].includes(descarga.status) 
                                              ? 'bg-gradient-to-r from-blue-500 to-indigo-500 animate-pulse' 
                                              : 'bg-gradient-to-r from-emerald-500 to-teal-500'">
                                </div>
                                <div v-else class="h-full w-full bg-gradient-to-r from-blue-400 to-indigo-400 animate-pulse opacity-50"></div>
                            </div>
                        </div>

                        <!-- Contadores en Tiempo Real -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div class="bg-white rounded-xl p-3 border border-gray-100 shadow-sm text-center">
                                <div class="text-2xl font-black text-gray-800">{{ descarga.total_cfdis || 0 }}</div>
                                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total</div>
                            </div>
                             <div :class="['rounded-xl p-3 border shadow-sm text-center transition-all duration-500', 
                                         descarga.imported_cfdis > 0 ? 'bg-emerald-50 border-emerald-200 scale-105 z-10' : 'bg-white border-emerald-100']">
                                <div class="text-2xl font-black text-emerald-600">{{ descarga.imported_cfdis || 0 }}</div>
                                <div class="text-[10px] font-bold text-emerald-500 uppercase tracking-wider">Nuevos (ADD)</div>
                            </div>
                            <div :class="['rounded-xl p-3 border shadow-sm text-center transition-all duration-500',
                                         descarga.pending_cfdis > 0 ? 'bg-orange-50 border-orange-200 animate-pulse' : 'bg-white border-gray-100']">
                                <div :class="['text-2xl font-black', descarga.pending_cfdis > 0 ? 'text-orange-600' : 'text-gray-800']">
                                    {{ descarga.pending_cfdis || 0 }}
                                </div>
                                <div :class="['text-[10px] font-bold uppercase tracking-wider', descarga.pending_cfdis > 0 ? 'text-orange-500' : 'text-gray-400']">Pendientes</div>
                            </div>
                            <div class="bg-white rounded-xl p-3 border border-amber-100 shadow-sm text-center">
                                <div class="text-2xl font-black text-amber-600">{{ descarga.duplicate_cfdis || 0 }}</div>
                                <div class="text-[10px] font-bold text-amber-500 uppercase tracking-wider">Duplicados</div>
                            </div>
                            <div class="bg-white rounded-xl p-3 border border-red-100 shadow-sm text-center">
                                <div class="text-2xl font-black text-red-600">{{ descarga.error_cfdis || 0 }}</div>
                                <div class="text-[10px] font-bold text-red-500 uppercase tracking-wider">Errores</div>
                            </div>
                        </div>

                        <!-- Error Message (solo si no hay mensaje amigable) -->
                        <div v-if="descarga.last_error && !descarga.mensaje_usuario && !descarga.limite_tipo" class="bg-red-50 border border-red-200 rounded-xl p-3">
                            <div class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-red-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-xs text-red-700 font-medium">{{ descarga.last_error }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button v-if="descarga.status === 'pendiente' || descarga.status === 'verificando'"
                                    @click="verificarDescarga(descarga.id)"
                                    class="px-3 py-2 bg-blue-600 text-white rounded-lg text-xs font-bold hover:bg-blue-700">
                                Consultar
                            </button>
                            <!-- Botón Reintentar Manual (para estados pausado/esperando) -->
                            <button v-if="descarga.status === 'pausado' || descarga.status === 'esperando'"
                                    @click="reintentarDescargaManual(descarga)"
                                    class="px-3 py-2 bg-amber-600 text-white rounded-lg text-xs font-bold hover:bg-amber-700 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Reintentar Ahora
                            </button>
                            <!-- Nuevo botón de revisión -->
                            <button v-if="descarga.status === 'finished' || descarga.status === 'error' || descarga.status === 'completo' || descarga.status === 'completado'"
                                    @click="abrirRevisor(descarga)"
                                    class="px-3 py-2 bg-emerald-600 text-white rounded-lg text-xs font-bold hover:bg-emerald-700 flex items-center gap-1.5 shadow-md shadow-emerald-500/20">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                Revisar Docs
                            </button>
                            
                            <!-- Botón Ver Resumen con SweetAlert -->
                            <button v-if="descarga.status === 'finished' || descarga.status === 'error' || descarga.status === 'completo' || descarga.status === 'completado'"
                                    @click="mostrarResumenDescarga(descarga)"
                                    class="px-3 py-2 bg-blue-600 text-white rounded-lg text-xs font-bold hover:bg-blue-700 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                Ver Resumen
                            </button>

                            <button v-if="descarga.status === 'completado'"
                                    @click="revalidarDescarga(descarga)"
                                    :disabled="isRevalidatingDescarga[descarga.id]"
                                    class="px-3 py-2 bg-amber-600 text-white rounded-lg text-xs font-bold hover:bg-amber-700 disabled:opacity-60">
                                {{ isRevalidatingDescarga[descarga.id] ? 'Revalidando...' : 'Revalidar SAT' }}
                            </button>

                            <button @click="eliminarDescarga(descarga)"
                                    :disabled="isDeletingDescarga[descarga.id]"
                                    class="px-3 py-2 bg-red-600 text-white rounded-lg text-xs font-bold hover:bg-red-700 disabled:opacity-60">
                                <svg v-if="!isDeletingDescarga[descarga.id]" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                <svg v-else class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
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
                                   class="w-full h-14 pl-12 pr-4 bg-gray-50 border-2 border-transparent rounded-2xl text-sm font-bold focus:bg-white focus:border-blue-500 focus:ring-0 transition-all placeholder:text-gray-400" />
                            <svg class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[14px] font-black text-gray-400 uppercase tracking-[0.2em] pl-1">Tipo Documento</label>
                        <select v-model="filters.tipo_comprobante" class="w-full h-14 px-5 bg-gray-50 border-2 border-transparent rounded-2xl text-sm font-bold focus:bg-white focus:border-blue-500 focus:ring-0 transition-all appearance-none">
                            <option value="">Cualquier Tipo</option>
                            <option value="I">Factura (Ingreso)</option>
                            <option value="P">Pago (Complemento)</option>
                            <option value="E">Egreso (N. Crédito)</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[14px] font-black text-gray-400 uppercase tracking-[0.2em] pl-1">Estado Fiscal</label>
                        <select v-model="filters.estatus" class="w-full h-14 px-5 bg-gray-50 border-2 border-transparent rounded-2xl text-sm font-bold focus:bg-white focus:border-blue-500 focus:ring-0 transition-all appearance-none">
                            <option value="">Todos los Estados</option>
                            <option value="vigente">Vigente / Timbrado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[14px] font-black text-gray-400 uppercase tracking-[0.2em] pl-1">Rango de Fecha</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="date" v-model="filters.fecha_inicio" class="w-full h-14 px-3 bg-gray-50 border-2 border-transparent rounded-2xl text-[11px] font-bold focus:bg-white focus:border-blue-100 focus:ring-0 transition-all" />
                            <input type="date" v-model="filters.fecha_fin" class="w-full h-14 px-3 bg-gray-50 border-2 border-transparent rounded-2xl text-[11px] font-bold focus:bg-white focus:border-blue-100 focus:ring-0 transition-all" />
                        </div>
                    </div>

                    <div class="flex items-end pb-1">
                        <button @click="Object.keys(filters).forEach(k => filters[k] = k === 'sort_dir' ? 'desc' : '')" 
                                class="w-full h-14 text-[10px] font-black text-gray-400 hover:text-red-500 uppercase tracking-[0.2em] transition-all flex items-center justify-center gap-2 hover:bg-red-50 rounded-2xl">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            Borrar Filtros
                        </button>
                    </div>

                    <!-- Toggle Filtros Avanzados -->
                    <div class="flex items-end pb-1 lg:col-span-5 border-t border-gray-100 pt-6 mt-2">
                        <button @click="showAdvancedFilters = !showAdvancedFilters" class="flex items-center gap-2 text-xs font-black text-blue-600 uppercase tracking-widest hover:text-blue-700 transition-colors">
                            <svg class="w-4 h-4 transition-transform duration-300" :class="showAdvancedFilters ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            {{ showAdvancedFilters ? 'Menos Filtros' : 'Filtros Avanzados' }}
                        </button>
                    </div>

                    <!-- Campos Avanzados -->
                    <template v-if="showAdvancedFilters">
                        <div class="space-y-2 lg:col-span-2">
                            <label class="text-[14px] font-black text-gray-400 uppercase tracking-[0.2em] pl-1">RFC Emisor</label>
                            <input v-model="filters.rfc_emisor" placeholder="AAA010101AAA" 
                                   class="w-full h-14 px-4 bg-gray-50 border-2 border-transparent rounded-2xl text-sm font-bold focus:bg-white focus:border-blue-500 focus:ring-0 transition-all uppercase placeholder:normal-case" />
                        </div>
                        <div class="space-y-2 lg:col-span-2">
                            <label class="text-[14px] font-black text-gray-400 uppercase tracking-[0.2em] pl-1">RFC Receptor</label>
                            <input v-model="filters.rfc_receptor" placeholder="AAA010101AAA" 
                                   class="w-full h-14 px-4 bg-gray-50 border-2 border-transparent rounded-2xl text-sm font-bold focus:bg-white focus:border-blue-500 focus:ring-0 transition-all uppercase placeholder:normal-case" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-[14px] font-black text-gray-400 uppercase tracking-[0.2em] pl-1">Serie</label>
                            <input v-model="filters.serie" placeholder="A" 
                                   class="w-full h-14 px-4 bg-gray-50 border-2 border-transparent rounded-2xl text-sm font-bold focus:bg-white focus:border-blue-500 focus:ring-0 transition-all" />
                        </div>
                    </template>
                </div>
            </div>

            <!-- Listado de Documentos (Tabla / Grid) -->
            <div v-if="viewMode === 'table'" class="bg-white rounded-[2.5rem] shadow-sm border border-gray-200 transition-all overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-gray-200">
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
                                    <!-- 1. Folio & Fiscal -->
                                    <td class="px-8 py-6">
                                        <div class="flex flex-col gap-1.5">
                                            <div class="flex items-center gap-2">
                                                <span class="text-lg font-black text-gray-900 tracking-tight">{{ cfdi.folio }}</span>
                                                <span :class="['px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider border', 
                                                    cfdi.tipo_comprobante_nombre === 'Ingreso' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' :
                                                    cfdi.tipo_comprobante_nombre === 'Egreso' ? 'bg-rose-50 text-rose-600 border-rose-100' :
                                                    cfdi.tipo_comprobante_nombre === 'Pago' ? 'bg-sky-50 text-sky-600 border-sky-100' :
                                                    cfdi.tipo_comprobante_nombre === 'Nómina' ? 'bg-orange-50 text-orange-600 border-orange-100' :
                                                    cfdi.tipo_comprobante_nombre === 'Traslado' ? 'bg-violet-50 text-violet-600 border-violet-100' :
                                                    'bg-gray-50 text-gray-600 border-gray-100']"
                                                    :title="cfdi.tipo_comprobante_nombre">
                                                    {{ cfdi.tipo_comprobante_nombre || cfdi.tipo_comprobante }}
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                                                <span class="text-[14px] font-mono text-gray-600 group-hover:text-blue-500 transition-colors uppercase truncate max-w-[120px]" :title="cfdi.uuid">
                                                    {{ cfdi.uuid }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- 2. Cliente/Proveedor (según dirección) -->
                                    <td class="px-8 py-6">
                                         <div class="flex flex-col gap-1">
                                            <!-- Para EMITIDOS: Mostrar receptor (tu cliente) -->
                                            <template v-if="cfdi.direccion === 'emitido'">
                                                <div class="flex items-center gap-1">
                                                    <span class="text-[9px] font-black text-blue-600 uppercase">Cliente:</span>
                                                    <span class="text-xs font-bold text-gray-900 truncate max-w-[180px]" :title="cfdi.receptor">
                                                        {{ cfdi.receptor || 'Sin especificar' }}
                                                    </span>
                                                </div>
                                                <span class="text-[14px] font-mono text-gray-600 truncate max-w-[120px]">{{ cfdi.rfc_receptor || '' }}</span>
                                            </template>
                                            <!-- Para RECIBIDOS: Mostrar emisor (tu proveedor) -->
                                            <template v-else>
                                                <div class="flex items-center gap-1">
                                                    <span class="text-[9px] font-black text-emerald-600 uppercase">Proveedor:</span>
                                                    <span class="text-xs font-bold text-gray-900 truncate max-w-[180px]" :title="cfdi.emisor">
                                                        {{ cfdi.emisor || 'Sin especificar' }}
                                                    </span>
                                                </div>
                                                <span class="text-[14px] font-mono text-gray-600 truncate max-w-[120px]">{{ cfdi.rfc_emisor || '' }}</span>
                                            </template>
                                         </div>
                                    </td>

                                    <!-- 3. Fecha de Emisión -->
                                    <td class="px-8 py-6">
                                        <div class="flex flex-col">
                                            <span class="text-[13px] font-black text-gray-400 uppercase tracking-widest">Emisión</span>
                                            <span class="text-base font-bold text-gray-700">{{ cfdi.fecha || 'Sin fecha' }}</span>
                                        </div>
                                    </td>

                                    <!-- 4. Monto Total -->
                                    <td class="px-8 py-6 text-right">
                                        <span class="text-sm font-black text-emerald-600 tracking-tight tabular-nums">
                                            ${{ Number(cfdi.total).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                                        </span>
                                    </td>

                                    <!-- 5. Estatus -->
                                    <td class="px-8 py-6 text-center">
                                         <div v-if="satStatus[cfdi.id]" 
                                             :class="['inline-flex px-2 py-1 rounded-lg text-[8px] font-black uppercase tracking-widest', 
                                                      satStatus[cfdi.id].estado === 'Vigente' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700']">
                                            {{ satStatus[cfdi.id].estado }}
                                        </div>
                                        <div v-else 
                                             :class="['inline-flex px-2 py-1 rounded-lg text-[8px] font-black uppercase tracking-widest',
                                                      (cfdi.estado_sat === 'Cancelado') ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700']">
                                            {{ cfdi.estado_sat || 'Vigente' }}
                                        </div>
                                    </td>

                                    <!-- 6. Gestión -->
                                    <td class="px-8 py-6">
                                        <div class="flex items-center justify-end gap-2">
                                            <button @click="verPdf(cfdi.uuid)" class="w-8 h-8 flex items-center justify-center bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-600 hover:text-white transition-all">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                            </button>
                                            <button @click="abrirModalCrearCuenta(cfdi)"
                                                    class="w-8 h-8 flex items-center justify-center bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-600 hover:text-white transition-all"
                                                    title="Crear Cuenta por Pagar/Cobrar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                </svg>
                                            </button>
                                            <button @click="verXml(cfdi.uuid)" class="w-8 h-8 flex items-center justify-center bg-indigo-50 text-indigo-600 rounded-lg hover:bg-indigo-600 hover:text-white transition-all">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" /></svg>
                                            </button>
                                            
                                            <div class="w-[1px] h-6 bg-gray-100 mx-1"></div>

                                            <button v-if="!cfdi.venta_id" @click="deleteCfdi(cfdi)" class="w-8 h-8 flex items-center justify-center bg-red-50 text-red-600 rounded-lg hover:bg-red-600 hover:text-white transition-all">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <!-- Quick View Content -->
                                <tr v-if="isExpanded(cfdi.id)">
                                    <td colspan="7" class="px-8 py-6 bg-gradient-to-br from-gray-50 to-slate-50 border-t border-gray-200">
                                        <div class="animate-fadeIn space-y-6">
                                            <!-- Header con UUID y Tipo -->
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-3">
                                                    <span :class="['px-3 py-1 rounded-lg text-xs font-black uppercase tracking-widest',
                                                        cfdi.tipo_comprobante === 'I' ? 'bg-emerald-100 text-emerald-700' :
                                                        cfdi.tipo_comprobante === 'E' ? 'bg-rose-100 text-rose-700' :
                                                        cfdi.tipo_comprobante === 'P' ? 'bg-sky-100 text-sky-700' :
                                                        cfdi.tipo_comprobante === 'N' ? 'bg-orange-100 text-orange-700' :
                                                        cfdi.tipo_comprobante === 'T' ? 'bg-violet-100 text-violet-700' :
                                                        'bg-gray-100 text-gray-700']">
                                                        {{ cfdi.tipo_comprobante_nombre }}
                                                    </span>
                                                    <span class="text-xs font-mono text-gray-500">UUID: {{ cfdi.uuid }}</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <button @click.stop="verPdf(cfdi.uuid)" class="px-3 py-1.5 bg-blue-100 text-blue-700 rounded-lg text-xs font-bold hover:bg-blue-200 transition-all">PDF</button>
                                                    <button @click.stop="verXml(cfdi.uuid)" class="px-3 py-1.5 bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold hover:bg-indigo-200 transition-all">XML</button>
                                                </div>
                                            </div>

                                            <!-- Grid Principal -->
                                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                                <!-- Columna 1: Emisor y Receptor -->
                                                <div class="space-y-4">
                                                    <!-- Emisor -->
                                                    <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
                                                        <h4 class="text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-2 flex items-center gap-1">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                                                            Emisor
                                                        </h4>
                                                        <p class="text-sm font-bold text-gray-900 truncate" :title="cfdi.emisor">{{ cfdi.emisor }}</p>
                                                        <p class="text-xs font-mono text-gray-500">{{ cfdi.rfc_emisor }}</p>
                                                    </div>
                                                    <!-- Receptor -->
                                                    <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
                                                        <h4 class="text-[10px] font-black text-violet-600 uppercase tracking-widest mb-2 flex items-center gap-1">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                                                            Receptor
                                                        </h4>
                                                        <p class="text-sm font-bold text-gray-900 truncate" :title="cfdi.receptor || cfdi.datos_adicionales?.receptor?.nombre">
                                                            {{ cfdi.receptor || cfdi.datos_adicionales?.receptor?.nombre || 'N/A' }}
                                                        </p>
                                                        <p class="text-xs font-mono text-gray-500">{{ cfdi.rfc_receptor || cfdi.datos_adicionales?.receptor?.rfc }}</p>
                                                        <div class="flex gap-2 mt-2 text-[9px]">
                                                            <span class="px-2 py-0.5 bg-gray-100 rounded text-gray-600">Uso: {{ cfdi.datos_adicionales?.receptor?.uso_cfdi || 'N/A' }}</span>
                                                            <span class="px-2 py-0.5 bg-gray-100 rounded text-gray-600">Régimen: {{ cfdi.datos_adicionales?.receptor?.regimen_fiscal || 'N/A' }}</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Columna 2: Conceptos o Pagos -->
                                                <div class="space-y-4">
                                                <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                                        {{ cfdi.tipo_comprobante === 'P' ? 'Pagos Aplicados' : cfdi.tipo_comprobante === 'E' ? 'Aplicación' : cfdi.tipo_comprobante === 'N' ? 'Nómina' : 'Conceptos' }}
                                                    </h4>
                                                    
                                                    <!-- CFDIs Relacionados (para Egresos/Notas de Crédito) -->
                                                    <div v-if="cfdi.tipo_comprobante === 'E' && cfdi.datos_adicionales?.cfdi_relacionados?.length" class="mb-3">
                                                        <div v-for="(rel, rIdx) in cfdi.datos_adicionales.cfdi_relacionados" :key="rIdx" class="bg-rose-50 p-3 rounded-xl border border-rose-200 shadow-sm mb-2">
                                                            <div class="flex items-center gap-2 mb-2">
                                                                <span class="text-[10px] font-black text-rose-600 uppercase">
                                                                    {{ rel.tipo_relacion === '07' ? '🔗 Aplica Anticipo' : rel.tipo_relacion === '01' ? '📄 Nota de Crédito' : '🔗 CFDI Relacionado' }}
                                                                </span>
                                                                <span class="text-[9px] text-rose-400">(Tipo {{ rel.tipo_relacion }})</span>
                                                            </div>
                                                            <div v-for="(uuid, uIdx) in rel.uuids" :key="uIdx" class="bg-white p-2 rounded-lg mt-1 text-xs font-mono text-gray-600 truncate" :title="uuid">
                                                                {{ uuid }}
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="space-y-2 max-h-64 overflow-y-auto pr-2 custom-scrollbar">
                                                        <!-- Para CFDIs de tipo Pago (P) -->
                                                        <template v-if="cfdi.tipo_comprobante === 'P' && cfdi.complementos?.pagos">
                                                            <div v-for="(pago, idx) in cfdi.complementos.pagos" :key="idx" class="bg-white p-3 rounded-xl border border-sky-100 shadow-sm">
                                                                <div class="flex items-center justify-between mb-2">
                                                                    <span class="text-[10px] font-black text-sky-600 uppercase">Pago {{ idx + 1 }}</span>
                                                                    <span class="text-sm font-black text-emerald-600">${{ Number(pago.monto).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                                                                </div>
                                                                <div class="text-[10px] text-gray-500 mb-2">
                                                                    📅 {{ pago.fecha_pago?.split('T')[0] }} | Forma: {{ pago.forma_pago }}
                                                                </div>
                                                                <div v-for="(doc, dIdx) in pago.doctos_relacionados" :key="dIdx" class="bg-gray-50 p-2 rounded-lg mt-1 text-xs">
                                                                    <div class="flex justify-between">
                                                                        <span class="font-bold">{{ doc.serie }}-{{ doc.folio }}</span>
                                                                        <span class="font-black text-gray-700">${{ Number(doc.imp_pagado).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                                                                    </div>
                                                                    <div class="text-[9px] text-gray-400">Parcialidad {{ doc.num_parcialidad }} | Saldo: ${{ Number(doc.imp_saldo_insoluto).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</div>
                                                                </div>
                                                            </div>
                                                        </template>
                                                        <!-- Para CFDIs de tipo Nómina (N) -->
                                                        <template v-else-if="cfdi.tipo_comprobante === 'N' && cfdi.complementos?.nomina">
                                                            <!-- Datos del Empleado -->
                                                            <div class="bg-violet-50 p-3 rounded-xl border border-violet-200 shadow-sm mb-2">
                                                                <div class="text-[10px] font-black text-violet-600 uppercase mb-2">👤 Empleado</div>
                                                                <div class="grid grid-cols-2 gap-2 text-xs">
                                                                    <div><span class="text-gray-500">No. Empleado:</span> <span class="font-bold">{{ cfdi.complementos.nomina.receptor?.num_empleado }}</span></div>
                                                                    <div><span class="text-gray-500">Depto:</span> <span class="font-bold">{{ cfdi.complementos.nomina.receptor?.departamento || 'N/A' }}</span></div>
                                                                    <div><span class="text-gray-500">Puesto:</span> <span class="font-bold">{{ cfdi.complementos.nomina.receptor?.puesto || 'N/A' }}</span></div>
                                                                    <div><span class="text-gray-500">Antigüedad:</span> <span class="font-bold">{{ cfdi.complementos.nomina.receptor?.antiguedad }}</span></div>
                                                                </div>
                                                                <div class="mt-2 text-[9px] text-gray-500">
                                                                    CURP: {{ cfdi.complementos.nomina.receptor?.curp }} | NSS: {{ cfdi.complementos.nomina.receptor?.num_seguridad_social }}
                                                                </div>
                                                            </div>
                                                            <!-- Periodo de Pago -->
                                                            <div class="bg-white p-3 rounded-xl border border-gray-100 shadow-sm mb-2">
                                                                <div class="text-[10px] font-black text-gray-400 uppercase mb-2">📅 Periodo</div>
                                                                <div class="text-xs">
                                                                    <span class="font-bold">{{ cfdi.complementos.nomina.fecha_inicial_pago }}</span> → 
                                                                    <span class="font-bold">{{ cfdi.complementos.nomina.fecha_final_pago }}</span>
                                                                    <span class="text-gray-500 ml-2">({{ cfdi.complementos.nomina.num_dias_pagados }} días)</span>
                                                                </div>
                                                            </div>
                                                            <!-- Percepciones -->
                                                            <div v-if="cfdi.complementos.nomina.percepciones" class="bg-emerald-50 p-3 rounded-xl border border-emerald-200 shadow-sm mb-2">
                                                                <div class="flex justify-between items-center mb-2">
                                                                    <span class="text-[10px] font-black text-emerald-600 uppercase">💰 Percepciones</span>
                                                                    <span class="text-sm font-black text-emerald-700">${{ Number(cfdi.complementos.nomina.total_percepciones).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                                                                </div>
                                                                <div v-for="(perc, pIdx) in cfdi.complementos.nomina.percepciones.detalle" :key="pIdx" class="flex justify-between text-xs py-1 border-b border-emerald-100 last:border-0">
                                                                    <span>{{ perc.concepto }}</span>
                                                                    <div class="text-right">
                                                                        <span class="font-bold">${{ Number(perc.importe_gravado + perc.importe_exento).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                                                                        <span v-if="perc.importe_exento > 0" class="text-[9px] text-emerald-500 ml-1">(Exento: ${{ Number(perc.importe_exento).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }})</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <!-- Deducciones -->
                                                            <div v-if="cfdi.complementos.nomina.deducciones" class="bg-red-50 p-3 rounded-xl border border-red-200 shadow-sm mb-2">
                                                                <div class="flex justify-between items-center mb-2">
                                                                    <span class="text-[10px] font-black text-red-600 uppercase">📉 Deducciones</span>
                                                                    <span class="text-sm font-black text-red-700">-${{ Number(cfdi.complementos.nomina.total_deducciones).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                                                                </div>
                                                                <div v-for="(ded, dIdx) in cfdi.complementos.nomina.deducciones.detalle" :key="dIdx" class="flex justify-between text-xs py-1 border-b border-red-100 last:border-0">
                                                                    <span>{{ ded.concepto }}</span>
                                                                    <span class="font-bold">-${{ Number(ded.importe).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                                                                </div>
                                                            </div>
                                                            <!-- Otros Pagos -->
                                                            <div v-if="cfdi.complementos.nomina.otros_pagos?.length" class="bg-amber-50 p-3 rounded-xl border border-amber-200 shadow-sm">
                                                                <div class="text-[10px] font-black text-amber-600 uppercase mb-2">📋 Otros Pagos</div>
                                                                <div v-for="(op, oIdx) in cfdi.complementos.nomina.otros_pagos" :key="oIdx" class="flex justify-between text-xs py-1">
                                                                    <span>{{ op.concepto }}</span>
                                                                    <span class="font-bold">${{ Number(op.importe).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                                                                </div>
                                                            </div>
                                                        </template>
                                                        <!-- Para otros tipos -->
                                                        <template v-else>
                                                            <div v-for="concept in cfdi.conceptos" :key="concept.id" class="bg-white p-3 rounded-xl border border-gray-100 shadow-sm">
                                                                <div class="flex justify-between items-start">
                                                                    <div class="flex-1 mr-2">
                                                                        <p class="text-xs font-bold text-gray-800 break-words leading-tight">{{ concept.descripcion }}</p>
                                                                        <div class="flex flex-wrap gap-1 mt-1">
                                                                            <span class="text-[9px] px-1.5 py-0.5 bg-blue-50 text-blue-600 rounded">{{ concept.clave_prod_serv || 'N/A' }}</span>
                                                                            <span class="text-[9px] px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded">{{ concept.clave_unidad || 'N/A' }}</span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="text-right flex-shrink-0">
                                                                        <p class="text-sm font-black text-gray-800">${{ Number(concept.importe).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</p>
                                                                        <p class="text-[9px] text-gray-400">{{ concept.cantidad }} × ${{ Number(concept.valor_unitario).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</p>
                                                                    </div>
                                                                </div>
                                                                <!-- Impuestos del concepto -->
                                                                <div v-if="concept.impuestos" class="mt-2 pt-2 border-t border-gray-50 flex flex-wrap gap-2 text-[9px]">
                                                                    <span v-if="concept.impuestos?.traslados?.length" class="px-1.5 py-0.5 bg-emerald-50 text-emerald-600 rounded">
                                                                        IVA: ${{ concept.impuestos.traslados.reduce((sum, t) => sum + (t.importe || 0), 0).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                                                                    </span>
                                                                    <span v-if="concept.impuestos?.retenciones?.length" class="px-1.5 py-0.5 bg-amber-50 text-amber-600 rounded">
                                                                        Ret: ${{ concept.impuestos.retenciones.reduce((sum, r) => sum + (r.importe || 0), 0).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>

                                                <!-- Columna 3: Resumen Fiscal -->
                                                <div class="space-y-4">
                                                    <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Resumen Fiscal</h4>
                                                    <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm space-y-3">
                                                        <div class="flex justify-between text-sm">
                                                            <span class="text-gray-500">Subtotal</span>
                                                            <span class="font-bold">${{ Number(cfdi.subtotal).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                                                        </div>
                                                        <!-- IVA Trasladado -->
                                                        <div v-if="cfdi.datos_adicionales?.traslados?.length" class="space-y-1">
                                                            <div v-for="(t, tIdx) in cfdi.datos_adicionales.traslados" :key="tIdx" class="flex justify-between text-xs text-emerald-600">
                                                                <span>+ IVA {{ (t.tasa_o_cuota * 100).toFixed(0) }}%</span>
                                                                <span class="font-bold">${{ Number(t.importe).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                                                            </div>
                                                        </div>
                                                        <!-- Retenciones -->
                                                        <div v-if="cfdi.datos_adicionales?.retenciones?.length" class="space-y-1">
                                                            <div v-for="(r, rIdx) in cfdi.datos_adicionales.retenciones" :key="rIdx" class="flex justify-between text-xs text-amber-600">
                                                                <span>- Ret. {{ r.impuesto === '001' ? 'ISR' : 'IVA' }}</span>
                                                                <span class="font-bold">-${{ Number(r.importe).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="pt-3 border-t border-gray-100 flex justify-between">
                                                            <span class="text-sm font-black text-blue-600 uppercase">Total</span>
                                                            <span class="text-xl font-black text-gray-900">${{ Number(cfdi.total).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                                                        </div>
                                                    </div>

                                                    <!-- Datos de Timbrado -->
                                                    <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
                                                        <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Timbrado</h4>
                                                        <div class="space-y-1 text-xs">
                                                            <div class="flex justify-between">
                                                                <span class="text-gray-500">Fecha</span>
                                                                <span class="font-mono text-gray-700">{{ cfdi.fecha }}</span>
                                                            </div>
                                                            <div class="flex justify-between">
                                                                <span class="text-gray-500">Método Pago</span>
                                                                <span class="font-bold">{{ cfdi.datos_adicionales?.metodo_pago || 'N/A' }}</span>
                                                            </div>
                                                            <div class="flex justify-between">
                                                                <span class="text-gray-500">Forma Pago</span>
                                                                <span class="font-bold">{{ cfdi.datos_adicionales?.forma_pago || 'N/A' }}</span>
                                                            </div>
                                                        </div>
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

                <!-- Bulk Actions Floating Bar -->
                <Transition name="slide-up">
                    <div v-if="selectedIds.length > 0" class="fixed bottom-8 left-1/2 -translate-x-1/2 z-50">
                        <div class="bg-gray-900/95 backdrop-blur-md text-white px-8 py-4 rounded-[2rem] shadow-2xl shadow-blue-900/40 flex items-center gap-8 border border-white/10">
                            <div class="flex flex-col">
                                <span class="text-xs font-black text-blue-400 uppercase tracking-widest">{{ selectedIds.length }} seleccionados</span>
                                <span class="text-[10px] text-gray-400 font-bold">CFDIs Marcados</span>
                            </div>
                            
                            <div class="w-[1px] h-8 bg-white/10"></div>
                            
                            <div class="flex items-center gap-3">
                                <button @click="bulkCheckSat" :disabled="isBulkProcessing" class="px-5 py-2.5 bg-white/10 hover:bg-white/20 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    Consultar SAT
                                </button>
                                <button @click="bulkSendEmail" :disabled="isBulkProcessing" class="px-5 py-2.5 bg-white/10 hover:bg-white/20 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    Enviar Email
                                </button>
                                <button @click="bulkDownloadZip" :disabled="isBulkDownloading" class="px-5 py-2.5 bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-400 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center gap-2 border border-emerald-500/20">
                                    <svg v-if="!isBulkDownloading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                    <svg v-else class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    ZIP
                                </button>
                                <button @click="selectedIds = []" class="px-5 py-2.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-xl text-xs font-black uppercase tracking-widest transition-all">
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>

                <!-- Paginación Premium Grid -->
                <div class="flex items-center justify-center py-4">
                    <Pagination :paginationData="cfdis" @page-change="handlePageChange" />
                </div>

                <!-- Empty State para Grid -->
                <div v-if="cfdiItems.length === 0" class="bg-white rounded-[2rem] p-20 text-center border border-gray-100 shadow-xl shadow-blue-900/5">
                    <div class="w-24 h-24 bg-gray-50 rounded-[2.5rem] flex items-center justify-center mx-auto mb-6 border-4 border-white shadow-inner">
                        <svg class="w-10 h-10 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </div>
                    <h3 class="text-xl font-black text-gray-400 uppercase tracking-widest">Bóveda Vacía</h3>
                    <p class="text-sm text-gray-300 font-bold mt-2">No se encontraron documentos digitales.</p>
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
        :on-reset-upload="resetUploadPreview"
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
        :format-currency="formatCurrency"
        :get-tipo-badge="getTipoBadge"
        :ver-pdf-staging="verPdfStaging"
        :ver-xml-staging="verXmlStaging"
        :ver-pdf="verPdf"
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

    <!-- Modal de Previsualizacion Integrada -->
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

<!-- Modal Crear Cuenta desde CFDI -->
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

.animate-fadeIn {
    animation: fadeIn 0.3s ease-out forwards;
}

/* Custom Scrollbar for XML */
.custom-scrollbar::-webkit-scrollbar {
    width: 10px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #111827;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #374151;
    border-radius: 10px;
    border: 3px solid #111827;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #4B5563;
}
</style>
















