<!-- /resources/js/Pages/OrdenesCompra/Index.vue -->
<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { router, Head } from '@inertiajs/vue3'
import axios from 'axios'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

import { generarPDF } from '@/Utils/pdfGenerator'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

import OrdenesCompraHeader from '@/Components/IndexComponents/OrdenesCompraHeader.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  ordenesCompra: {
    type: Object,
    default: () => ({ data: [] })
  },
  pagination: {
    type: Object,
    default: () => ({})
  },
  stats: {
    type: Object,
    default: () => ({})
  },
  almacenes: {
    type: Array,
    default: () => []
  }
})

/* =========================
   Configuración de notificaciones
========================= */
const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false },
    { type: 'warning', background: '#f59e0b', icon: false }
  ]
})

/* =========================
    Estado local y modal
 ========================= */
const showModal = ref(false)
const fila = ref(null)
const modalMode = ref('details')
const selectedId = ref(null)
const loading = ref(false)

// Captura de series para conversión directa
const showSeriesModal = ref(false)
const seriesProductos = ref([]) // [{ id, nombre, cantidad }]
const seriesInputs = ref({}) // { [productoId]: ["", "", ...] }
const seriesOrder = ref(null) // Orden en proceso de conversión
const selectedAlmacen = ref(null) // Almacén seleccionado para la compra

const openSeriesModal = (productos, orden) => {
  seriesProductos.value = Array.isArray(productos) ? productos : []
  seriesOrder.value = orden
  const inputs = {}
  for (const p of seriesProductos.value) {
    const cantidad = Number(p.cantidad) || 0
    inputs[p.id] = Array.from({ length: cantidad }, () => '')
  }
  seriesInputs.value = inputs
  // Preseleccionar primer almacén si existe
  selectedAlmacen.value = props.almacenes?.[0]?.id || null
  showSeriesModal.value = true
}

const closeSeriesModal = () => {
  showSeriesModal.value = false
  seriesProductos.value = []
  seriesInputs.value = {}
  seriesOrder.value = null
  selectedAlmacen.value = null
}

const submitSeriesConversion = async () => {
  if (!seriesOrder.value) {
    closeSeriesModal()
    return
  }

  // Validación básica de entradas y construcción del payload
  const seriesArray = []
  for (const p of seriesProductos.value) {
    const arr = (seriesInputs.value[p.id] || []).map(s => String(s || '').trim()).filter(Boolean)
    if (arr.length !== (Number(p.cantidad) || 0)) {
      notyf.error(`Debes capturar ${p.cantidad} series para "${p.nombre}"`)
      return
    }
    // ✅ Formato correcto para el backend
    seriesArray.push({
      producto_id: p.id,
      series: arr
    })
  }

  // Validar que se haya seleccionado un almacén
  if (!selectedAlmacen.value) {
    notyf.error('Debes seleccionar un almacén')
    return
  }

  try {
    loading.value = true
    const { data } = await axios.post(
      `/ordenescompra/${seriesOrder.value.id}/convertir-directo`,
      { 
        series: seriesArray,
        almacen_id: selectedAlmacen.value
      },
      {
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      }
    )

    if (!data?.success) {
      notyf.error(data?.error || data?.message || 'No se pudo convertir la orden')
      return
    }

    // Actualiza estado local
    const i = ordenesOriginales.value.findIndex(o => o.id === seriesOrder.value.id)
    if (i !== -1) ordenesOriginales.value[i] = { ...ordenesOriginales.value[i], estado: 'procesada' }

    closeSeriesModal()
    showModal.value = false
    notyf.success(data.message || 'Orden convertida a compra exitosamente')

    setTimeout(() => { router.visit('/compras') }, 1200)
  } catch (err) {
    console.error(err)
    notyf.error(err?.response?.data?.error || err?.response?.data?.message || err?.message || 'Error al convertir orden')
  } finally {
    loading.value = false
  }
}

// Variables para UniversalHeader
const searchInput = ref(null)
const isSearchFocused = ref(false)

/* =========================
   Filtros, orden y datos
========================= */
const searchTerm = ref('')
const sortBy = ref('fecha-desc')
const filtroEstado = ref('')
const ordenesOriginales = ref([...(props.ordenesCompra?.data || [])])

/* =========================
   Auditoría segura para el modal
========================= */
const auditoriaForModal = computed(() => {
  const r = fila.value
  if (!r) return null

  const meta = r.metadata || {}
  return {
    creado_por: r.creado_por_nombre || r.created_by_user_name || meta.creado_por || 'N/A',
    actualizado_por: r.actualizado_por_nombre || r.updated_by_user_name || meta.actualizado_por || 'N/A',
    eliminado_por: r.eliminado_por_nombre || r.deleted_by_user_name || meta.eliminado_por || null,
    creado_en: r.created_at || meta.creado_en || null,
    actualizado_en: r.updated_at || meta.actualizado_en || null,
    eliminado_en: r.deleted_at || meta.eliminado_en || null,
  }
})

/* =========================
    Paginación del servidor
========================= */
const visiblePages = computed(() => {
  const pages = []
  const total = props.pagination.last_page || 1
  const current = props.pagination.current_page || 1

  if (total <= 7) {
    for (let i = 1; i <= total; i++) {
      pages.push(i)
    }
  } else {
    if (current <= 3) {
      for (let i = 1; i <= 5; i++) {
        pages.push(i)
      }
    } else if (current >= total - 2) {
      for (let i = total - 4; i <= total; i++) {
        pages.push(i)
      }
    } else {
      for (let i = current - 2; i <= current + 2; i++) {
        pages.push(i)
      }
    }
  }

  return pages
})

const goToPage = (page) => {
  const query = {
    page,
    search: searchTerm.value,
    estado: filtroEstado.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc'
  }
  router.visit('/ordenescompra', { data: query })
}

const nextPage = () => {
  if (props.pagination.current_page < props.pagination.last_page) {
    goToPage(props.pagination.current_page + 1)
  }
}

const prevPage = () => {
  if (props.pagination.current_page > 1) {
    goToPage(props.pagination.current_page - 1)
  }
}

// Watchers para actualizar datos locales
watch(() => props.ordenesCompra, (newVal) => {
  if (newVal && newVal.data && Array.isArray(newVal.data)) {
    ordenesOriginales.value = [...newVal.data]
  }
}, { deep: true, immediate: true })

// Watcher para debuggear estadísticas
// Watcher para debuggear estadísticas
watch(() => props.stats, (newStats) => {
  if (newStats) {
    // console.log('🔄 Estadísticas actualizadas:', newStats);
    // console.log('📊 Total:', newStats.total);
    // console.log('⏳ Pendientes:', newStats.pendientes);
    // console.log('✈️ Enviadas:', newStats.enviadas_a_proveedor);
    // console.log('✅ Procesadas:', newStats.procesadas);
    // console.log('❌ Canceladas:', newStats.canceladas);
  }
}, { deep: true, immediate: true })

// Estadísticas del servidor (mapeo EXACTO con el backend)
const estadisticas = computed(() => {
  const stats = props.stats || {};

  // Debug: mostrar qué está llegando del backend
  // console.log('📊 Estadísticas recibidas del backend:', stats);

  // Mapeo correcto: el backend ahora envía 'procesadas' como las órdenes convertidas
  // console.log('📈 Procesadas recibidas del backend:', stats.procesadas);

  return {
    total: stats.total || 0,
    pendientes: stats.pendientes || 0,
    enviadas_a_proveedor: stats.enviadas_a_proveedor || 0,
    procesadas: stats.procesadas || 0,
    canceladas: stats.canceladas || 0,
  }
})

const handleLimpiarFiltros = () => {
  searchTerm.value = ''
  sortBy.value = 'fecha-desc'
  filtroEstado.value = ''
  router.visit('/ordenescompra')
  notyf.success('Filtros limpiados correctamente')
}

const updateSort = (newSort) => {
  if (newSort && typeof newSort === 'string') {
    sortBy.value = newSort
    const query = {
      sort_by: newSort.split('-')[0],
      sort_direction: newSort.split('-')[1] || 'desc',
      search: searchTerm.value,
      estado: filtroEstado.value
    }
    router.visit('/ordenescompra', { data: query })
  }
}

const changePerPage = (event) => {
  const perPage = event.target.value
  const query = {
    per_page: perPage,
    search: searchTerm.value,
    estado: filtroEstado.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc'
  }
  router.visit('/ordenescompra', { data: query })
}

const handleSearch = () => {
  const query = {
    search: searchTerm.value,
    estado: filtroEstado.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc'
  }
  router.visit('/ordenescompra', { data: query })
}

const handleFilter = () => {
  const query = {
    search: searchTerm.value,
    estado: filtroEstado.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc'
  }
  router.visit('/ordenescompra', { data: query })
}

/* =========================
   Validaciones y utilidades
========================= */
function puedeEnviarOrden(orden) {
  if (!orden) return false
  return orden.estado === 'aprobada' || orden.estado === 'aprobado'
}

function puedeRecibirOrden(orden) {
  if (!orden) return false
  return orden.estado === 'enviada' || orden.estado === 'enviado'
}

function validarOrden(orden) {
  if (!orden?.id) {
    throw new Error('ID de orden no válido')
  }
  return true
}

function validarOrdenBasica(orden) {
  if (!orden?.id) {
    throw new Error('ID de orden no válido')
  }
  if (!orden.proveedor?.nombre_razon_social) {
    throw new Error('Datos del proveedor no encontrados')
  }
  const productos = orden.productos || orden.items || [];
  if (!Array.isArray(productos) || !productos.length) {
    throw new Error('Lista de productos no válida')
  }
  if (!orden.fecha_orden && !orden.created_at) {
    throw new Error('Fecha no especificada')
  }
  return true
}

const actualizarEstadoLocal = (id, nuevoEstado) => {
  const indice = ordenesOriginales.value.findIndex(o => o.id === id)
  if (indice !== -1) {
    ordenesOriginales.value[indice] = {
      ...ordenesOriginales.value[indice],
      estado: nuevoEstado
    }
  }

  if (fila.value?.id === id) {
    fila.value = { ...fila.value, estado: nuevoEstado }
  }
}
function validarOrdenParaPDF(doc) {
  if (!doc.id) throw new Error('ID del documento no encontrado')
  if (!doc.proveedor?.nombre_razon_social) throw new Error('Datos del proveedor no encontrados')
  const productos = doc.productos || doc.items || [];
  if (!Array.isArray(productos) || !productos.length) {
    throw new Error('Lista de productos no válida')
  }
  if (!doc.fecha_orden) throw new Error('Fecha no especificada')
  return true
}

/* =========================
   Acciones CRUD
========================= */
const verDetalles = (orden) => {
  try {
    validarOrden(orden)
    fila.value = orden
    modalMode.value = 'details'
    showModal.value = true
  } catch (error) {
    notyf.error(error.message)
  }
}

const editarOrden = (id) => {
  try {
    const ordenId = id || fila.value?.id
    if (!ordenId) throw new Error('ID de orden no válido')

    router.visit(`/ordenescompra/${ordenId}/edit`)
  } catch (error) {
    notyf.error(error.message)
  }
}

const editarFila = (id) => {
  editarOrden(id)
}

const duplicarOrden = async (orden) => {
  try {
    validarOrden(orden)

    if (!confirm(`¿Duplicar orden #${orden.numero_orden || orden.id}?`)) {
      return
    }

    loading.value = true

    router.post(`/ordenescompra/${orden.id}/duplicate`, {}, {
      onStart: () => {
        notyf.success('Duplicando orden...')
      },
      onSuccess: () => {
        notyf.success('Orden duplicada exitosamente')
      },
      onError: (errors) => {
        console.error('Error al duplicar:', errors)
        notyf.error('Error al duplicar la orden')
      },
      onFinish: () => {
        loading.value = false
      }
    })
  } catch (error) {
    notyf.error(error.message)
    loading.value = false
  }
}

const imprimirOrden = async (orden) => {
  try {
    const doc = {
      ...orden,
      fecha: orden.fecha_orden || orden.created_at || new Date().toISOString()
    }

    validarOrdenParaPDF(doc)

    loading.value = true
    notyf.success('Generando PDF...')

    await generarPDF('Orden de Compra', doc)
    notyf.success('PDF generado correctamente')

  } catch (error) {
    console.error('Error al generar PDF:', error)
    notyf.error(`Error al generar el PDF: ${error.message}`)
  } finally {
    loading.value = false
  }
}

const imprimirFila = () => {
  if (fila.value) {
    imprimirOrden(fila.value)
  }
}

const confirmarEliminacion = (id) => {
  try {
    if (!id) throw new Error('ID de orden no válido')

    selectedId.value = id
    modalMode.value = 'confirm'
    showModal.value = true
  } catch (error) {
    notyf.error(error.message)
  }
}

const eliminarOrden = async () => {
  try {
    if (!selectedId.value) throw new Error('No se seleccionó ninguna orden')

    loading.value = true

    router.post(`/ordenescompra/${selectedId.value}/cancel`, {}, {
      onStart: () => {
        notyf.success('Cancelando orden...')
      },
      onSuccess: (response) => {
        notyf.success('Orden cancelada exitosamente')

        // Actualizar datos locales
        const index = ordenesOriginales.value.findIndex(o => o.id === selectedId.value)
        if (index !== -1) {
          ordenesOriginales.value[index] = {
            ...ordenesOriginales.value[index],
            estado: 'cancelada',
            eliminado_por: response?.data?.eliminado_por || 'Usuario actual',
            deleted_at: new Date().toISOString()
          }
        }

        showModal.value = false
        selectedId.value = null
      },
      onError: (errors) => {
        console.error('Error al cancelar:', errors)
        notyf.error('Error al cancelar la orden')
      },
      onFinish: () => {
        loading.value = false
      }
    })
  } catch (error) {
    notyf.error(error.message)
    loading.value = false
  }
}

const enviarOrden = (ordenData) => {
  try {
    const doc = ordenData?.id ? ordenData : fila.value
    validarOrdenBasica(doc)

    loading.value = true

    router.post(`/ordenescompra/${doc.id}/enviar-compra`, {}, {
      preserveScroll: true,
      onSuccess: (page) => {
        const flash = page?.props?.flash || {}

        if (flash.error) {
          notyf.error(flash.error)
          return
        }

        const mensaje = flash.success || 'Orden enviada al proveedor'
        notyf.success(mensaje)
        actualizarEstadoLocal(doc.id, 'enviado_a_proveedor')
        showModal.value = false
      },
      onError: (errors) => {
        const firstError = Object.values(errors || {})[0]
        notyf.error(firstError || 'Error al enviar la orden a compra')
      },
      onFinish: () => {
        loading.value = false
      }
    })
  } catch (err) {
    console.error(err)
    notyf.error(err.message || 'Error al enviar la orden a compra')
    loading.value = false
  }
}

// Estado para recibir orden
const isReceivingOrden = ref(false)

const recibirOrden = async (ordenData) => {
  try {
    const doc = ordenData?.id ? ordenData : fila.value
    validarOrdenBasica(doc)

    loading.value = true
    notyf.success('Recibiendo orden...')

    const { data } = await axios.post(`/ordenescompra/${doc.id}/recibir-mercancia`, {
      forzarReenvio: !!ordenData?.forzarReenvio
    })

    if (!data?.success) throw new Error(data?.error || 'No se pudo recibir la orden')

    // Actualiza estado local
    const i = ordenesOriginales.value.findIndex(o => o.id === doc.id)
    if (i !== -1) ordenesOriginales.value[i] = { ...ordenesOriginales.value[i], estado: 'procesada' }

    showModal.value = false
    notyf.success(data.message || 'Orden recibida exitosamente')

    // Redirigir a la página de compras
    setTimeout(() => {
      router.visit('/compras')
    }, 1500)

  } catch (err) {
    console.error(err)
    notyf.error(err.response?.data?.error || err.response?.data?.message || err.message || 'Error al recibir orden')
  } finally {
    loading.value = false
  }
}

const convertirDirecto = async (ordenData) => {
  try {
    const doc = ordenData?.id ? ordenData : fila.value
    validarOrdenBasica(doc)

    loading.value = true

    // Mostrar confirmación elegante con notyf
    notyf.success(`Convirtiendo orden #${doc.numero_orden || doc.id} a compra...`)

    // Pequeña pausa para mostrar el mensaje antes de proceder
    await new Promise(resolve => setTimeout(resolve, 1000))

    const { data } = await axios.post(
      `/ordenescompra/${doc.id}/convertir-directo`,
      {},
      {
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      }
    )

    // Manejo explícito cuando el backend indica que se requieren series
    if (data?.requiere_series) {
      notyf.open({ type: 'warning', message: data?.message || 'Esta orden requiere captura de series antes de convertir.' })
      openSeriesModal(data?.productos_con_serie || [], doc)
      loading.value = false
      return
    }

    if (!data?.success) {
      notyf.error(data?.error || data?.message || 'No se pudo convertir la orden')
      loading.value = false
      return
    }

    // Actualiza estado local
    const i = ordenesOriginales.value.findIndex(o => o.id === doc.id)
    if (i !== -1) ordenesOriginales.value[i] = { ...ordenesOriginales.value[i], estado: 'procesada' }

    showModal.value = false
    notyf.success(data.message || 'Orden convertida a compra exitosamente')

    // Redirigir a la página de compras
    setTimeout(() => {
      router.visit('/compras')
    }, 1500)

  } catch (err) {
    console.error(err)
    notyf.error(err.response?.data?.error || err.response?.data?.message || err.message || 'Error al convertir orden')
  } finally {
    loading.value = false
  }
}

const enviarEmailOrden = async (ordenData) => {
  try {
    const doc = ordenData?.id ? ordenData : fila.value
    validarOrdenBasica(doc)

    console.log('🔍 Debug enviarEmailOrden:', {
      doc_id: doc.id,
      proveedor: doc.proveedor,
      email: doc.proveedor?.email,
      estado: doc.estado
    })

    if (!doc.proveedor?.email) {
      notyf.error('El proveedor no tiene email configurado')
      return
    }

    // El envío de email está permitido en cualquier estado

    loading.value = true
    notyf.success(`Enviando orden #${doc.numero_orden || doc.id} por email...`)

    const { data } = await axios.post(`/ordenescompra/${doc.id}/enviar-email`, {}, {
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    })

    if (!data?.success) throw new Error(data?.error || 'No se pudo enviar el email')

    // Actualiza estado local para mostrar que el email fue enviado
    const i = ordenesOriginales.value.findIndex(o => o.id === doc.id)
    if (i !== -1) {
      ordenesOriginales.value[i] = {
        ...ordenesOriginales.value[i],
        email_enviado: true,
        email_enviado_fecha: new Date().toISOString()
      }
    }

    // Si se abrió desde modal, actualizar fila también
    if (fila.value?.id === doc.id) {
      fila.value = {
        ...fila.value,
        email_enviado: true,
        email_enviado_fecha: new Date().toISOString()
      }
    }

    notyf.success(data.message || 'Orden enviada por email exitosamente')

  } catch (err) {
    console.error(err)
    const errorMessage = err.response?.data?.error || err.response?.data?.message || err.message || 'Error al enviar email'
    notyf.error(errorMessage)
  } finally {
    loading.value = false
  }
}

const crearNuevaOrden = () => {
  router.visit('/ordenescompra/create')
}

// Funciones para UniversalHeader
const focusSearch = () => { searchInput.value?.focus(); }
const handleSearchFocus = () => { isSearchFocused.value = true; }
const handleSearchBlur  = () => { isSearchFocused.value = false; }

const handleKeydown = (event) => {
  if ((event.ctrlKey || event.metaKey) && event.key === 'k') { event.preventDefault(); focusSearch(); }
  if (event.key === 'Escape' && isSearchFocused.value) searchTerm.value = '';
}

const hayFiltrosActivos = computed(() => !!searchTerm.value || !!filtroEstado.value)

const limpiarFiltros = () => {
  searchTerm.value = ''
  sortBy.value = 'fecha-desc'
  filtroEstado.value = ''
  handleLimpiarFiltros()
}

// Iconos para UniversalHeader
const icons = {
  document: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
  'document-text': 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
  'paper-airplane': 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8',
  'check-circle': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
  'x-circle': 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
  clock: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
  plus: 'M12 4v16m8-8H4',
  search: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
  x: 'M6 18L18 6M6 6l12 12',
  'filter-x': 'M6 18L18 6M6 6l12 12',
  'chevron-down': 'M19 9l-7 7-7-7',
  'arrow-down': 'M19 14l-7 7m0 0l-7-7m7 7V3',
  'arrow-up': 'M5 10l7-7m0 0l7 7m-7-7v18',
  'currency-dollar': 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1',
  'sort-ascending': 'M3 4h6m0 0l4-4m-4 4l4 4M3 8h11m-11 4h14m-14 4h11m-11 4h6',
  'sort-descending': 'M3 4h14m-14 4h11m-11 4h6m0 0l-4 4m4-4l4-4M3 16h11m-11 4h14',
  export: 'M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z',
  import: 'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10',
  loading: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
  identification: 'M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2',
  'status-online': 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
  'exchange-alt': 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4'
}

const colorClasses = {
  green:  { text: 'text-emerald-600', bg: 'bg-emerald-50',  border: 'border-emerald-200', ring: 'ring-emerald-500/20' },
  blue:   { text: 'text-blue-600',    bg: 'bg-blue-50',     border: 'border-blue-200',    ring: 'ring-blue-500/20' },
  yellow: { text: 'text-amber-600',   bg: 'bg-amber-50',    border: 'border-amber-200',   ring: 'ring-amber-500/20' },
  orange: { text: 'text-orange-600',  bg: 'bg-orange-50',   border: 'border-orange-200',  ring: 'ring-orange-500/20' },
  red:    { text: 'text-red-600',     bg: 'bg-red-50',      border: 'border-red-200',     ring: 'ring-red-500/20' },
  slate:  { text: 'text-slate-600',   bg: 'bg-slate-50',    border: 'border-slate-200',   ring: 'ring-slate-500/20' },
  indigo: { text: 'text-amber-600',  bg: 'bg-indigo-50',   border: 'border-indigo-200',  ring: 'ring-amber-500/20' },
  emerald:{ text: 'text-emerald-600', bg: 'bg-emerald-50',  border: 'border-emerald-200', ring: 'ring-emerald-500/20' },
  gray:   { text: 'text-gray-600',    bg: 'bg-white',     border: 'border-gray-200',    ring: 'ring-gray-500/20' }
}

const getColorClasses = (color) => colorClasses[color] || colorClasses.slate

const formatNumber = (num, tipo = 'number') => {
  if (tipo === 'currency') return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(num);
  return new Intl.NumberFormat('es-ES').format(num);
}

const getIconPath = (iconName) => icons[iconName] || icons.document

// Estadísticas con porcentajes para órdenes de compra (cálculo mejorado)
const estadisticasConPorcentaje = computed(() => {
  const total = estadisticas.value.total || 1; // evita división por cero
  const procesadasValue = estadisticas.value.procesadas || 0;

  console.log('🔢 Cálculo de porcentajes:', {
    total,
    procesadasValue,
    porcentaje: Math.round((procesadasValue / total) * 100)
  });

  return {
    pendientes: { ...{ label: 'Pendientes', icon: 'clock', color: 'yellow', description: 'Órdenes pendientes' }, porcentaje: Math.round(((estadisticas.value.pendientes || 0) / total) * 100) },
    enviadas_a_proveedor: { ...{ label: 'Enviadas a Proveedor', icon: 'paper-plane', color: 'blue', description: 'Órdenes enviadas a proveedor' }, porcentaje: Math.round(((estadisticas.value.enviadas_a_proveedor || 0) / total) * 100) },
    procesadas: { ...{ label: 'Procesadas', icon: 'check-circle', color: 'green', description: 'Órdenes procesadas' }, porcentaje: Math.round((procesadasValue / total) * 100) },
    canceladas: { ...{ label: 'Canceladas', icon: 'x-circle', color: 'red', description: 'Órdenes canceladas' }, porcentaje: Math.round(((estadisticas.value.canceladas || 0) / total) * 100) },
  };
})

// Variables para DocumentosTable
const showTooltip = ref(false)
const hoveredDoc = ref(null)
const tooltipPosition = ref({ x: 0, y: 0 })
let tooltipTimeout = null

// Función para obtener productos del documento (mejorada)
const getProductosDelDoc = (doc) => {
  if (!doc) return [];
  const productos = doc.productos || doc.items || [];

  // Validar que sea un array y tenga elementos válidos
  if (!Array.isArray(productos)) return [];
  if (!productos.length) return [];

  // Filtrar productos válidos
  return productos.filter(p =>
    p &&
    (p.nombre || p.descripcion) &&
    (p.cantidad > 0 || p.cantidad !== null)
  );
}

// Tooltip optimizado (igual que DocumentosTable)
const showProductTooltip = (doc, event) => {
  if (!getProductosDelDoc(doc)?.length) return;
  clearTimeout(tooltipTimeout);
  hoveredDoc.value = doc;
  updateTooltipPosition(event);
  tooltipTimeout = setTimeout(() => { showTooltip.value = true; }, 500);
}

const hideProductTooltip = () => {
  clearTimeout(tooltipTimeout);
  tooltipTimeout = setTimeout(() => {
    showTooltip.value = false;
    hoveredDoc.value = null;
  }, 300);
}

const clearHideTimeout = () => {
  clearTimeout(tooltipTimeout);
}

const updateTooltipPosition = (event) => {
  tooltipPosition.value = { x: event.clientX, y: event.clientY };
}

// Tooltip style computed
const tooltipStyle = computed(() => {
  const OFFSET = 20, TOOLTIP_WIDTH = 320, TOOLTIP_HEIGHT = 384, VIEWPORT_PADDING = 16;
  const { w, h } = getViewport();

  let x = tooltipPosition.value.x + OFFSET;
  let y = tooltipPosition.value.y - (TOOLTIP_HEIGHT / 2);

  if (x + TOOLTIP_WIDTH > w - VIEWPORT_PADDING) x = tooltipPosition.value.x - TOOLTIP_WIDTH - OFFSET;
  if (x < VIEWPORT_PADDING) x = VIEWPORT_PADDING;
  if (y < VIEWPORT_PADDING) y = VIEWPORT_PADDING;
  else if (y + TOOLTIP_HEIGHT > h - VIEWPORT_PADDING) y = h - TOOLTIP_HEIGHT - VIEWPORT_PADDING;

  return {
    left: `${x}px`,
    top: `${y}px`,
    transform: showTooltip.value ? 'scale(1) translateY(0)' : 'scale(0.95) translateY(-10px)',
    opacity: showTooltip.value ? '1' : '0'
  };
})

const getViewport = () => {
  if (typeof window === 'undefined') return { w: 1280, h: 800 };
  return { w: window.innerWidth, h: window.innerHeight };
}

// Cache de formatos para mejor rendimiento (igual que DocumentosTable)
const formatCache = new Map();

const formatearFecha = (date) => {
  if (!date) return 'Fecha no disponible';
  const cacheKey = `fecha-${date}`;
  if (formatCache.has(cacheKey)) return formatCache.get(cacheKey);

  try {
    const time = new Date(date).getTime();
    if (Number.isNaN(time)) return 'Fecha inválida';
    const formatted = new Date(time).toLocaleDateString('es-MX', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    });
    formatCache.set(cacheKey, formatted);
    return formatted;
  } catch {
    return 'Fecha inválida';
  }
}

const formatearHora = (date) => {
  if (!date) return '';
  const cacheKey = `hora-${date}`;
  if (formatCache.has(cacheKey)) return formatCache.get(cacheKey);

  try {
    const time = new Date(date).getTime();
    if (Number.isNaN(time)) return '';
    const formatted = new Date(time).toLocaleTimeString('es-MX', {
      hour: '2-digit',
      minute: '2-digit'
    });
    formatCache.set(cacheKey, formatted);
    return formatted;
  } catch {
    return '';
  }
}

const formatearMoneda = (num) => {
  const value = parseFloat(num);
  const safe = Number.isFinite(value) ? value : 0;
  const cacheKey = `moneda-${safe}`;
  if (formatCache.has(cacheKey)) return formatCache.get(cacheKey);

  const formatted = new Intl.NumberFormat('es-MX', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(safe);
  formatCache.set(cacheKey, formatted);
  return formatted;
}

// Configuración de estados para órdenes de compra (EXACTAMENTE igual que DocumentosTable)
const configEstados = {
  'borrador': {
    label: 'Borrador',
    classes: 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
    color: 'bg-gray-400 dark:bg-gray-500'
  },
  'pendiente': {
    label: 'Pendiente',
    classes: 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300',
    color: 'bg-yellow-400 dark:bg-yellow-500'
  },
  'aprobada': {
    label: 'Aprobada',
    classes: 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300',
    color: 'bg-blue-400 dark:bg-blue-500'
  },
  'enviado_a_proveedor': {
    label: 'Enviado a Proveedor',
    classes: 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300',
    color: 'bg-blue-400 dark:bg-blue-500'
  },
  'convertida': {
    label: 'Procesada',
    classes: 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300',
    color: 'bg-green-400 dark:bg-green-500'
  },
  'cancelada': {
    label: 'Cancelada',
    classes: 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300',
    color: 'bg-red-400 dark:bg-red-500'
  }
};

const obtenerClasesEstado = (estado) => {
  return configEstados[estado]?.classes || 'bg-gray-100 text-gray-700';
}

const obtenerColorPuntoEstado = (estado) => {
  return configEstados[estado]?.color || 'bg-gray-400';
}

const obtenerLabelEstado = (estado) => {
  return configEstados[estado]?.label || 'Pendiente';
}

// Configuración de filtros y búsqueda mejorada
const configFiltros = {
  searchFields: ['proveedor.nombre_razon_social', 'items.nombre', 'numero_orden', 'id'],
  estados: Object.keys(configEstados),
  sortOptions: [
    { field: 'fecha', label: 'Fecha' },
    { field: 'proveedor', label: 'Proveedor' },
    { field: 'numero_orden', label: 'Número de Orden' },
    { field: 'total', label: 'Total' },
    { field: 'estado', label: 'Estado' }
  ]
};

// Items filtrados y ordenados (optimizado)
const items = computed(() => {
  if (!Array.isArray(props.ordenesCompra.data)) {
    console.warn('⚠️ Órdenes de compra no es un array:', props.ordenesCompra.data);
    return [];
  }

  let filtered = props.ordenesCompra.data.slice();

  // Filtro de búsqueda
  if (searchTerm.value) {
    const term = searchTerm.value.toLowerCase().trim();
    filtered = filtered.filter(doc => {
      return configFiltros.searchFields.some(field => {
        const value = field.split('.').reduce((obj, key) => obj?.[key], doc);
        return (value || '').toString().toLowerCase().includes(term);
      });
    });
  }

  // Filtro de estado
  if (filtroEstado.value) {
    filtered = filtered.filter(doc => doc.estado === filtroEstado.value);
  }

  // Ordenamiento optimizado
  if (sortBy.value) {
    const [field, direction] = sortBy.value.split('-');

    filtered.sort((a, b) => {
      let aVal, bVal;

      switch (field) {
        case 'fecha':
          aVal = new Date(a.created_at || a.fecha).getTime() || 0;
          bVal = new Date(b.created_at || b.fecha).getTime() || 0;
          break;
        case 'proveedor':
          aVal = (a.proveedor?.nombre_razon_social || '').toLowerCase();
          bVal = (b.proveedor?.nombre_razon_social || '').toLowerCase();
          break;
        case 'numero_orden':
          aVal = (a.numero_orden || a.id || '').toString().toLowerCase();
          bVal = (b.numero_orden || b.id || '').toString().toLowerCase();
          break;
        case 'total':
          aVal = parseFloat(a.total) || 0;
          bVal = parseFloat(b.total) || 0;
          break;
        case 'estado':
          aVal = obtenerLabelEstado(a.estado).toLowerCase();
          bVal = obtenerLabelEstado(b.estado).toLowerCase();
          break;
        default:
          aVal = (a[field] || '').toString().toLowerCase();
          bVal = (b[field] || '').toString().toLowerCase();
      }

      const comparison = aVal < bVal ? -1 : aVal > bVal ? 1 : 0;
      return direction === 'desc' ? -comparison : comparison;
    });
  }

  return filtered;
});

// Sort function
const onSort = (field) => {
  const current = sortBy.value.startsWith(field) ? sortBy.value : `${field}-desc`;
  const newOrder = current === `${field}-desc` ? `${field}-asc` : `${field}-desc`;
  updateSort(newOrder);
}

// Funciones para Modal
const modalRef = ref(null)

// Estados de confirmación
const showConfirmReenvioPedido = ref(false)
const showConfirmReenvioVenta  = ref(false)

// Focus management
const focusFirst = () => { try { modalRef.value?.focus() } catch {} }
watch(() => showModal, (v) => { if (v) setTimeout(focusFirst, 0) })

// Cerrar con tecla ESC también desde window
const onKey = (e) => { if (e.key === 'Escape' && showModal.value) onClose() }
onMounted(() => window.addEventListener('keydown', onKey))
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))

// Emits comunes para modal
const onCancel  = () => { showModal.value = false; fila.value = null; selectedId.value = null; }
const onConfirm = () => { eliminarOrden() }
const onClose   = () => { showModal.value = false; fila.value = null; selectedId.value = null; }
const onImprimirFila = () => { if (fila.value) { imprimirOrden(fila.value) } }
const onEditarFila = () => { editarOrden(fila.value?.id) }

// Helper functions
const isNumber = (n) => Number.isFinite(parseFloat(n))

const obtenerPrecio = (producto) => {
  if (!producto) return 0;
  const p = parseFloat(producto.precio ?? producto.precio_venta ?? producto.pv ?? 0);
  return Number.isFinite(p) ? p : 0;
}

// Función de debug para probar estadísticas
const testEstadisticas = () => {
  console.log('🧪 TEST ESTADÍSTICAS:');
  console.log('Props stats:', props.stats);
  console.log('Estadísticas calculadas:', estadisticas.value);
  console.log('Estadísticas con porcentaje:', estadisticasConPorcentaje.value);

  // Test manual de mapeo
  const testStats = {
    total: 10,
    pendientes: 3,
    enviadas_a_proveedor: 2,
    procesadas: 4,
    canceladas: 1
  };

  console.log('Test con datos manuales:', testStats);
}

// Validaciones mejoradas
const validarDocumento = (doc) => {
  if (!doc?.id) {
    throw new Error('ID del documento no encontrado');
  }
  if (!doc.proveedor?.nombre_razon_social) {
    throw new Error('Datos del proveedor no encontrados');
  }
  const productos = getProductosDelDoc(doc);
  if (!Array.isArray(productos) || !productos.length) {
    throw new Error('Lista de productos no válida');
  }
  return true;
}

const validarEstado = (estado) => {
  return configEstados[estado] !== undefined;
}
</script>

<template>
  <Head title="Órdenes de Compra" />

  <!-- FontAwesome Icons -->
  <component :is="'style'">
    @import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
  </component>

  <div class="ordenes-compra-index min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Contenido principal -->
    <div class="w-full px-6 py-8">
      <!-- Header específico de órdenes de compra -->
      <OrdenesCompraHeader
        :total="estadisticas.total"
        :pendientes="estadisticas.pendientes"
        :enviadas_a_proveedor="estadisticas.enviadas_a_proveedor"
        :procesadas="estadisticas.procesadas"
        :canceladas="estadisticas.canceladas"
        v-model:search-term="searchTerm"
        v-model:sort-by="sortBy"
        v-model:filtro-estado="filtroEstado"
        @crear-nueva="crearNuevaOrden"
        @search-change="handleSearch"
        @filtro-estado-change="handleFilter"
        @sort-change="updateSort"
        @limpiar-filtros="limpiarFiltros"
      />

      <!-- Información de paginación -->
      <div class="flex justify-between items-center mb-4 text-sm text-gray-600 dark:text-gray-400">
        <div>
          Mostrando {{ props.pagination.from || 0 }} -
          {{ props.pagination.to || 0 }}
          de {{ props.pagination.total || 0 }} órdenes
        </div>
        <div class="flex items-center space-x-2">
          <span>Elementos por página:</span>
          <select
            :value="props.pagination.per_page || 10"
            @change="changePerPage"
            class="border border-gray-300 dark:border-gray-600 rounded px-2 py-1 text-sm bg-white dark:bg-gray-700 dark:text-gray-200"
          >
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
      </div>

      <!-- Tabla de documentos -->
      <div class="mt-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
          <!-- Header -->
          <div class="bg-gradient-to-r from-gray-50 to-gray-100/50 dark:from-gray-700/50 dark:to-gray-800/50 px-6 py-4 border-b border-gray-200/60 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 tracking-tight">Órdenes de Compra</h2>
              <div class="text-sm text-gray-600 dark:text-gray-300 bg-white/70 dark:bg-gray-700/50 px-3 py-1 rounded-full border border-gray-200/50 dark:border-gray-600">
                {{ items.length }} de {{ props.pagination.total || 0 }} órdenes de compra
              </div>
            </div>
          </div>

          <!-- Table -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200/60 dark:divide-gray-700">
              <thead class="bg-gray-50/60 dark:bg-gray-700/60">
                <tr>
                  <!-- Fecha -->
                  <th
                    class="group px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100/60 dark:hover:bg-gray-700 transition-colors duration-150"
                    @click="onSort('fecha')"
                  >
                    <div class="flex items-center space-x-1">
                      <span>Fecha</span>
                      <svg
                        v-if="sortBy.startsWith('fecha')"
                        :class="['w-4 h-4 transition-transform duration-200', sortBy === 'fecha-desc' ? 'rotate-180' : '']"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                      </svg>
                    </div>
                  </th>

                  <!-- Proveedor -->
                  <th
                    class="group px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100/60 dark:hover:bg-gray-700 transition-colors duration-150"
                    @click="onSort('proveedor')"
                  >
                    <div class="flex items-center space-x-1">
                      <span>Proveedor</span>
                      <svg
                        v-if="sortBy.startsWith('proveedor')"
                        :class="['w-4 h-4 transition-transform duration-200', sortBy === 'proveedor-desc' ? 'rotate-180' : '']"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                      </svg>
                    </div>
                  </th>

                  <!-- N° Orden -->
                  <th
                    class="group px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100/60 dark:hover:bg-gray-700 transition-colors duration-150"
                    @click="onSort('numero_orden')"
                  >
                    <div class="flex items-center space-x-1">
                      <span>N° Orden</span>
                      <svg
                        v-if="sortBy.startsWith('numero_orden')"
                        :class="['w-4 h-4 transition-transform duration-200', sortBy === 'numero_orden-desc' ? 'rotate-180' : '']"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                      </svg>
                    </div>
                  </th>

                  <!-- Total -->
                  <th
                    class="group px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100/60 dark:hover:bg-gray-700 transition-colors duration-150"
                    @click="onSort('total')"
                  >
                    <div class="flex items-center space-x-1">
                      <span>Total</span>
                      <svg
                        v-if="sortBy.startsWith('total')"
                        :class="['w-4 h-4 transition-transform duration-200', sortBy === 'total-desc' ? 'rotate-180' : '']"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                      </svg>
                    </div>
                  </th>

                  <!-- Productos -->
                  <th
                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider"
                  >
                    Productos
                  </th>

                  <!-- Estado -->
                  <th
                    class="group px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100/60 dark:hover:bg-gray-700 transition-colors duration-150"
                    @click="onSort('estado')"
                  >
                    <div class="flex items-center space-x-1">
                      <span>Estado</span>
                      <svg
                        v-if="sortBy.startsWith('estado')"
                        :class="['w-4 h-4 transition-transform duration-200', sortBy === 'estado-desc' ? 'rotate-180' : '']"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                      </svg>
                    </div>
                  </th>

                  <!-- Acciones -->
                  <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                    Acciones
                  </th>
                </tr>
              </thead>

              <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200/40 dark:divide-gray-700/40">
                <template v-if="items.length > 0">
                  <tr
                    v-for="doc in items"
                    :key="doc.id"
                    :class="[
                      'group hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-all duration-150 hover:shadow-sm',
                      doc.estado === 'cancelada' ? 'opacity-50' : ''
                    ]"
                  >
                    <!-- Fecha -->
                    <td class="px-6 py-4">
                      <div class="flex flex-col space-y-0.5">
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                          {{ formatearFecha(doc.created_at || doc.fecha) }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                          {{ formatearHora(doc.created_at || doc.fecha) }}
                        </div>
                      </div>
                    </td>

                    <!-- Proveedor -->
                    <td class="px-6 py-4">
                      <div class="flex flex-col space-y-0.5">
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100 group-hover:text-gray-800 dark:group-hover:text-gray-200">
                          {{ doc.proveedor?.nombre_razon_social || 'Sin proveedor' }}
                        </div>
                        <div v-if="doc.proveedor?.email" class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-48">
                          {{ doc.proveedor?.email }}
                        </div>
                      </div>
                    </td>

                    <!-- N° Orden -->
                    <td class="px-6 py-4">
                      <div class="text-sm font-mono font-medium text-gray-700 dark:text-gray-200 bg-gray-100/60 dark:bg-gray-700/60 px-2 py-1 rounded-md inline-block">
                        {{ doc.numero_orden || 'N/A' }}
                      </div>
                    </td>

                    <!-- Total -->
                    <td class="px-6 py-4">
                      <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                        <template v-if="typeof doc.total !== 'undefined' && doc.total !== null">
                          ${{ formatearMoneda(doc.total) }}
                        </template>
                        <template v-else>-</template>
                      </div>
                    </td>

                    <!-- Productos -->
                    <td
                      class="px-6 py-4 relative"
                      @mouseenter="getProductosDelDoc(doc)?.length ? showProductTooltip(doc, $event) : null"
                      @mouseleave="hideProductTooltip"
                      @mousemove="getProductosDelDoc(doc)?.length ? updateTooltipPosition($event) : null"
                    >
                      <div class="flex items-center text-sm text-gray-600 dark:text-gray-300" :class="getProductosDelDoc(doc)?.length ? 'cursor-help hover:text-gray-800 dark:hover:text-gray-200 transition-colors duration-150' : 'opacity-60'">
                        <div class="w-8 h-8 bg-blue-50 dark:bg-blue-900/40 rounded-lg flex items-center justify-center mr-2 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/60 transition-colors duration-150">
                          <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                            />
                          </svg>
                        </div>
                        <span class="font-medium">{{ getProductosDelDoc(doc)?.length || 0 }}</span>
                        <span class="text-gray-400 dark:text-gray-500 ml-1">items</span>
                      </div>
                    </td>

                    <!-- Estado -->
                    <td class="px-6 py-4">
                      <span
                        :class="obtenerClasesEstado(doc.estado)"
                        class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium transition-all duration-150 hover:shadow-sm"
                      >
                        <span
                          class="w-2 h-2 rounded-full mr-2 transition-all duration-150"
                          :class="obtenerColorPuntoEstado(doc.estado)"
                        ></span>
                        {{ obtenerLabelEstado(doc.estado) }}
                      </span>
                    </td>

                    <!-- Acciones -->
                    <td class="px-6 py-4">
                      <div class="flex items-center justify-end space-x-2">
                        <!-- 1. Ver detalles (siempre visible) -->
                        <button
                          @click="verDetalles(doc)"
                          class="group/btn relative inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/60 hover:text-blue-700 dark:hover:text-blue-300 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:ring-offset-1"
                          title="Ver detalles"
                        >
                          <font-awesome-icon icon="eye" class="w-3.5 h-3.5 transition-transform duration-200 group-hover/btn:scale-110" />
                        </button>

                        <!-- 2. Editar (borrador, pendiente, aprobada y enviado a proveedor) -->
                        <button
                          v-if="doc.estado === 'borrador' || doc.estado === 'pendiente' || doc.estado === 'aprobada' || doc.estado === 'enviado_a_proveedor'"
                          @click="editarOrden(doc.id)"
                          class="group/btn relative inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-900/60 hover:text-amber-700 dark:hover:text-amber-300 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:ring-offset-1"
                          title="Editar orden"
                        >
                          <font-awesome-icon icon="edit" class="w-3.5 h-3.5 transition-transform duration-200 group-hover/btn:scale-110" />
                        </button>

                        <!-- 3. Enviar por Email (cualquier estado con email) -->
                        <button
                          v-if="doc.proveedor && doc.proveedor.email && doc.proveedor.email.trim() !== ''"
                          @click="enviarEmailOrden(doc)"
                          class="group/btn relative inline-flex items-center justify-center w-8 h-8 rounded-lg bg-green-50 dark:bg-green-900/40 text-green-600 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/60 hover:text-green-700 dark:hover:text-green-300 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500/50 focus:ring-offset-1"
                          :title="doc.email_enviado ? 'Reenviar por Email' : 'Enviar por Email'"
                        >
                          <font-awesome-icon
                            :icon="doc.email_enviado ? 'envelope-open' : 'envelope'"
                            class="w-3.5 h-3.5 transition-transform duration-200 group-hover/btn:scale-110"
                          />
                        </button>

                        <!-- Debug: mostrar condición del botón (remover en producción) -->
                        <span v-if="false" class="text-xs text-red-500 dark:text-red-400">
                          Email: {{ doc.proveedor?.email || 'no' }} | Estado: {{ doc.estado }} | Show: {{ !!(doc.proveedor && doc.proveedor.email && doc.proveedor.email.trim() !== '') }}
                        </span>

                        <!-- 4. Convertir Directo (solo pendiente) -->
                        <button
                          v-if="doc.estado === 'pendiente'"
                          @click="convertirDirecto(doc)"
                          class="group/btn relative inline-flex items-center justify-center w-8 h-8 rounded-lg bg-purple-50 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 hover:bg-purple-100 dark:hover:bg-purple-900/60 hover:text-purple-700 dark:hover:text-purple-300 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:ring-offset-1"
                          title="Convertir directamente a compra"
                        >
                          <font-awesome-icon icon="exchange-alt" class="w-3.5 h-3.5 transition-transform duration-200 group-hover/btn:scale-110" />
                        </button>


                        <!-- 6. Enviar a Compras (enviado a proveedor) -->
                        <button
                          v-if="doc.estado === 'enviado_a_proveedor'"
                          @click="recibirOrden(doc)"
                          class="group/btn relative inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-900/40 text-amber-600 dark:text-amber-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 hover:text-indigo-700 dark:hover:text-indigo-300 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:ring-offset-1"
                          title="Enviar a compras"
                        >
                          <font-awesome-icon icon="shopping-cart" class="w-3.5 h-3.5 transition-transform duration-200 group-hover/btn:scale-110" />
                        </button>

                        <!-- 6. Cancelar (pendiente, aprobada y enviada a proveedor) -->
                        <button
                          v-if="['pendiente', 'aprobada', 'enviado_a_proveedor'].includes(doc.estado)"
                          @click="confirmarEliminacion(doc.id)"
                          class="group/btn relative inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 dark:bg-red-900/40 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/60 hover:text-red-700 dark:hover:text-red-300 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-500/50 focus:ring-offset-1"
                          title="Cancelar orden"
                        >
                          <font-awesome-icon icon="times-circle" class="w-3.5 h-3.5 transition-transform duration-200 group-hover/btn:scale-110" />
                        </button>
                      </div>
                    </td>
                  </tr>
                </template>

                <!-- Empty State -->
                <tr v-else>
                  <td :colspan="7" class="px-6 py-16 text-center">
                    <div class="flex flex-col items-center space-y-4">
                      <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                      </div>
                      <div class="space-y-1">
                        <p class="text-gray-700 dark:text-gray-300 font-medium">No hay órdenes de compra</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Los documentos aparecerán aquí cuando se creen</p>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Controles de paginación -->
      <div v-if="props.pagination.last_page > 1" class="flex justify-center items-center space-x-2 mt-6">
        <button
          @click="prevPage"
          :disabled="props.pagination.current_page === 1"
          class="px-3 py-2 text-sm font-medium text-gray-500 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Anterior
        </button>

        <div class="flex space-x-1">
          <!-- Primera página (solo si no está en visiblePages) -->
          <template v-if="!visiblePages.includes(1) && props.pagination.last_page > 7">
            <button
              @click="goToPage(1)"
              class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600"
            >
              1
            </button>
            <span class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">...</span>
          </template>

          <!-- Páginas visibles -->
          <button
            v-for="page in visiblePages"
            :key="page"
            @click="goToPage(page)"
            :class="[
              'px-3 py-2 text-sm font-medium border border-gray-300 dark:border-gray-600 rounded-md',
              page === props.pagination.current_page
                ? 'bg-blue-500 text-white border-blue-500'
                : 'text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600'
            ]"
          >
            {{ page }}
          </button>

          <!-- Última página (solo si no está en visiblePages) -->
          <template v-if="!visiblePages.includes(props.pagination.last_page) && props.pagination.last_page > 7">
            <span class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">...</span>
            <button
              @click="goToPage(props.pagination.last_page)"
              class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600"
            >
              {{ props.pagination.last_page }}
            </button>
          </template>
        </div>

        <button
          @click="nextPage"
          :disabled="props.pagination.current_page === props.pagination.last_page"
          class="px-3 py-2 text-sm font-medium text-gray-500 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Siguiente
        </button>
      </div>
    </div>

    <!-- Modal de detalles / confirmación -->
    <Transition name="modal">
      <div
        v-if="showModal"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
        @click.self="onClose"
      >
        <div
          :class="{
            'max-w-md': modalMode === 'confirm' || modalMode === 'confirm-duplicate',
            'w-full': modalMode === 'details'
          }"
          class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-h-[90vh] overflow-y-auto p-6 outline-none"
          role="dialog"
          aria-modal="true"
          :aria-label="`Modal de Orden de Compra`"
          tabindex="-1"
          ref="modalRef"
          @keydown.esc.prevent="onClose"
        >
          <!-- Modo: Confirmación de eliminación -->
          <div v-if="modalMode === 'confirm'" class="text-center">
            <div class="w-12 h-12 mx-auto bg-red-100 rounded-full flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"
                />
              </svg>
            </div>
            <h3 class="text-lg font-medium mb-2 text-gray-900 dark:text-gray-100">
              ¿Eliminar orden de compra?
            </h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
              Esta acción no se puede deshacer.
            </p>
            <div class="flex gap-3">
              <button
                @click="onCancel"
                class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors"
              >
                Cancelar
              </button>
              <button
                @click="onConfirm"
                class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
              >
                Eliminar
              </button>
            </div>
          </div>

          <!-- Modo: Detalles -->
          <div v-else-if="modalMode === 'details'" class="space-y-4">
            <h3 class="text-lg font-medium mb-1 flex items-center gap-2 text-gray-900 dark:text-gray-100">
              Detalles de Orden de Compra
              <span v-if="fila?.id" class="text-sm text-gray-500 dark:text-gray-400">#{{ fila.id }}</span>
            </h3>

            <!-- Auditoría -->
            <div v-if="auditoriaForModal" class="mt-2 p-4 bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
              <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-100 mb-3">Auditoría</h4>
              <div class="grid md:grid-cols-3 gap-3 text-sm">
                <div>
                  <span class="text-gray-500 dark:text-gray-400">Creado por:</span>
                  <div class="font-medium text-gray-900 dark:text-gray-100">
                    {{ auditoriaForModal.creado_por || '—' }}
                  </div>
                  <div class="text-gray-500 dark:text-gray-400">
                    {{ formatearFecha(auditoriaForModal.creado_en) || '—' }}
                  </div>
                </div>
                <div>
                  <span class="text-gray-500 dark:text-gray-400">Actualizado por:</span>
                  <div class="font-medium text-gray-900 dark:text-gray-100">
                    {{ auditoriaForModal.actualizado_por || '—' }}
                  </div>
                  <div class="text-gray-500 dark:text-gray-400">
                    {{ formatearFecha(auditoriaForModal.actualizado_en) || '—' }}
                  </div>
                </div>
                <div v-if="auditoriaForModal.eliminado_en">
                  <span class="text-gray-500 dark:text-gray-400">Eliminado por:</span>
                  <div class="font-medium text-gray-900 dark:text-gray-100">
                    {{ auditoriaForModal.eliminado_por || '—' }}
                  </div>
                  <div class="text-gray-500 dark:text-gray-400">
                    {{ formatearFecha(auditoriaForModal.eliminado_en) || '—' }}
                  </div>
                </div>
              </div>
            </div>

            <div v-if="fila" class="space-y-4">
              <!-- Información general -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Columna izquierda -->
                <div>
                  <p class="text-sm text-gray-600 dark:text-gray-300">
                    <strong>Proveedor:</strong> {{ fila.proveedor?.nombre_razon_social || 'Sin proveedor' }}
                  </p>
                  <p class="text-sm text-gray-600 dark:text-gray-300" v-if="fila.proveedor?.email">
                    <strong>Email:</strong> {{ fila.proveedor.email }}
                  </p>
                  <p class="text-sm text-gray-600 dark:text-gray-300" v-if="fila.proveedor?.telefono">
                    <strong>Teléfono:</strong> {{ fila.proveedor.telefono }}
                  </p>
                  <p class="text-sm text-gray-600 dark:text-gray-300">
                    <strong>Fecha de creación:</strong>
                    {{ formatearFecha(fila.created_at) }}
                  </p>
                  <p class="text-sm text-gray-600 dark:text-gray-300">
                    <strong>Estado:</strong>
                    <span
                      :class="obtenerClasesEstado(fila.estado)"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    >
                      <span
                        class="w-1.5 h-1.5 rounded-full mr-1.5"
                        :class="obtenerColorPuntoEstado(fila.estado)"
                      ></span>
                      {{ obtenerLabelEstado(fila.estado) }}
                    </span>
                  </p>
                </div>

                <!-- Columna derecha -->
                <div>
                  <p v-if="fila.numero_orden" class="text-sm text-gray-600 dark:text-gray-300">
                    <strong>N° Orden:</strong>
                    {{ fila.numero_orden }}
                  </p>

                  <p v-if="isNumber(fila.total)" class="text-sm text-gray-600 dark:text-gray-300">
                    <strong>Total:</strong> ${{ formatearMoneda(fila.total) }}
                  </p>

                  <p class="text-sm text-gray-600 dark:text-gray-300">
                    <strong>Productos:</strong>
                    {{ fila.productos?.length || 0 }} items
                  </p>
                </div>
              </div>

              <!-- Tabla de productos mejorada -->
              <div v-if="getProductosDelDoc(fila)?.length" class="mt-4">
                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Productos</h4>
                <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                      <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                          Nombre
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                          Cantidad
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                          Precio
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                          Subtotal
                        </th>
                      </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                      <tr v-for="(producto, index) in getProductosDelDoc(fila)" :key="producto.id || `${producto.nombre}-${index}`">
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">
                          {{ producto.nombre || 'Sin nombre' }}
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300">
                          {{ producto.cantidad || 0 }}
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300">
                          ${{ formatearMoneda(obtenerPrecio(producto)) }}
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300">
                          ${{ formatearMoneda((producto.cantidad || 0) * obtenerPrecio(producto)) }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <p v-else class="text-sm text-gray-600 dark:text-gray-300">No hay productos asociados.</p>

              <!-- Totales para órdenes de compra -->
              <div v-if="fila.productos?.length" class="mt-4 p-4 bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Resumen de Orden de Compra</h4>
                <div class="space-y-2 text-sm">
                  <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-300">Subtotal:</span>
                    <span class="font-medium text-gray-900 dark:text-gray-100">${{ formatearMoneda(fila.subtotal || 0) }}</span>
                  </div>
                  <div v-if="(fila.descuento_items || 0) > 0" class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-300">Descuentos por Items:</span>
                    <span class="font-medium text-red-600 dark:text-red-400">-${{ formatearMoneda(fila.descuento_items || 0) }}</span>
                  </div>
                  <div v-if="(fila.descuento_general || 0) > 0" class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-300">Descuento General:</span>
                    <span class="font-medium text-red-600 dark:text-red-400">-${{ formatearMoneda(fila.descuento_general || 0) }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-300">IVA (16%):</span>
                    <span class="font-medium text-gray-900 dark:text-gray-100">${{ formatearMoneda(fila.iva || 0) }}</span>
                  </div>
                  <div class="flex justify-between border-t border-gray-300 dark:border-gray-600 pt-2">
                    <span class="text-gray-900 dark:text-gray-100 font-semibold">Total:</span>
                    <span class="text-gray-900 dark:text-gray-100 font-bold">${{ formatearMoneda(fila.total || 0) }}</span>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="text-sm text-gray-600 dark:text-gray-300">No hay datos disponibles.</div>

            <!-- Botones de acción simplificados -->
            <div class="flex flex-wrap justify-end gap-2 mt-6">
              <!-- 1. Editar (borrador, pendiente, aprobada y enviado a proveedor) -->
              <button
                v-if="['borrador', 'pendiente', 'aprobada', 'enviado_a_proveedor'].includes(fila?.estado)"
                @click="editarFila"
                class="px-3 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors text-sm"
              >
                ✏️ Editar
              </button>

              <!-- 2. Enviar Email (cualquier estado con email) -->
              <button
                v-if="fila?.proveedor && fila?.proveedor.email"
                @click="enviarEmailOrden(fila)"
                class="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm"
              >
                {{ fila?.email_enviado ? '📨 Reenviar Email' : '📧 Enviar Email' }}
              </button>

              <!-- 3. Convertir Directo (solo pendiente) -->
              <button
                v-if="fila?.estado === 'pendiente'"
                @click="convertirDirecto(fila)"
                class="px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm"
              >
                🔄 Convertir a Compra
              </button>


              <!-- 5. Enviar a Compras (enviado a proveedor) -->
              <button
                v-if="fila?.estado === 'enviado_a_proveedor'"
                @click="recibirOrden(fila)"
                class="px-3 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-colors text-sm"
              >
                🛒 Enviar a Compras
              </button>

              <!-- 5. Cancelar (pendiente, aprobada y enviada a proveedor) -->
              <button
                v-if="['pendiente', 'aprobada', 'enviado_a_proveedor'].includes(fila?.estado)"
                @click="confirmarEliminacion(fila.id)"
                class="px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm"
              >
                ❌ Cancelar
              </button>

              <!-- Cerrar -->
              <button
                @click="onClose"
                class="px-3 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500 transition-colors text-sm"
              >
                Cerrar
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Tooltip para productos -->
    <Teleport to="body">
      <div
        v-if="showTooltip && hoveredDoc"
        class="fixed z-[9999] bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200/50 dark:border-gray-700 backdrop-blur-sm w-80 max-h-96 pointer-events-auto transform transition-all duration-200 ease-out"
        :style="tooltipStyle"
        @mouseenter="clearHideTimeout"
        @mouseleave="hideProductTooltip"
      >
        <div class="p-4 border-b border-gray-100 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Productos</h3>
            <span class="text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full font-medium">
              {{ getProductosDelDoc(hoveredDoc)?.length || 0 }}
            </span>
          </div>
        </div>

        <div class="max-h-72 overflow-y-auto px-4 pb-4 custom-scrollbar">
          <div v-if="getProductosDelDoc(hoveredDoc)?.length" class="space-y-2 pt-2">
            <div
              v-for="(producto, index) in getProductosDelDoc(hoveredDoc)"
              :key="index"
              class="group p-3 bg-white/70 dark:bg-gray-800/70 rounded-lg hover:bg-gray-100/70 dark:hover:bg-gray-700/70 hover:shadow-sm transition-all duration-150"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0 mr-3">
                  <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate group-hover:text-gray-800 dark:group-hover:text-gray-200">
                    {{ producto.nombre || 'Sin nombre' }}
                  </p>
                  <div class="flex items-center mt-1.5 space-x-2 text-xs">
                    <span class="text-gray-600 dark:text-gray-300 bg-white/60 dark:bg-gray-700/60 px-2 py-0.5 rounded-md">
                      {{ producto.cantidad || 0 }} und
                    </span>
                    <span class="text-gray-400 dark:text-gray-500">•</span>
                    <span class="text-gray-600 dark:text-gray-300">
                      ${{ formatearMoneda(producto.precio || 0) }}
                    </span>
                  </div>
                  <p v-if="producto.descripcion" class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 line-clamp-2">
                    {{ producto.descripcion }}
                  </p>
                </div>
                <div class="text-right flex-shrink-0">
                  <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                    ${{ formatearMoneda((producto.cantidad || 0) * (producto.precio || 0)) }}
                  </p>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8">
            <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
              <svg class="w-6 h-6 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m0 0V9a2 2 0 012-2h2m2 2v4" />
              </svg>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Sin productos registrados</p>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal de Captura de Series -->
    <Teleport to="body">
      <div v-if="showSeriesModal" class="fixed inset-0 z-[100] flex items-center justify-center">
        <div class="absolute inset-0 bg-black/50" @click="closeSeriesModal" />
        <div class="relative bg-white dark:bg-gray-800 w-full max-w-3xl mx-4 rounded-xl shadow-xl p-6 modal-enter-active">
          <div class="flex items-start justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Captura de series</h3>
            <button @click="closeSeriesModal" class="text-gray-400 dark:text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
            Algunos productos requieren registrar números de serie antes de convertir la orden. Ingresa exactamente la cantidad requerida.
          </p>

          <!-- Selector de Almacén -->
          <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/40 border border-blue-200 dark:border-blue-800/50 rounded-lg">
            <label class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
              <span class="flex items-center">
                <svg class="w-4 h-4 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                Almacén de Recepción *
              </span>
            </label>
            <select
              v-model="selectedAlmacen"
              class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md focus:ring-blue-500 focus:border-blue-500"
              required
            >
              <option :value="null" disabled>Selecciona un almacén</option>
              <option v-for="almacen in props.almacenes" :key="almacen.id" :value="almacen.id">
                {{ almacen.nombre }}
              </option>
            </select>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Selecciona el almacén donde se recibirá la mercancía</p>
          </div>

          <div class="space-y-6 max-h-[60vh] overflow-y-auto pr-1 custom-scrollbar">
            <div v-for="p in seriesProductos" :key="p.id" class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
              <div class="flex items-center justify-between mb-3">
                <div>
                  <p class="font-medium text-gray-900 dark:text-gray-100">{{ p.nombre }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Series requeridas: {{ p.cantidad }}</p>
                </div>
                <button
                  class="text-xs text-blue-600 dark:text-blue-400 hover:underline"
                  @click="seriesInputs[p.id] = Array.from({ length: Number(p.cantidad) || 0 }, () => '')"
                >
                  Limpiar
                </button>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <input
                  v-for="(val, idx) in (seriesInputs[p.id] || [])"
                  :key="idx"
                  v-model="seriesInputs[p.id][idx]"
                  type="text"
                  class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm"
                  :placeholder="`Serie #${idx + 1}`"
                />
              </div>
            </div>
          </div>

          <div class="mt-6 flex items-center justify-end space-x-3">
            <button @click="closeSeriesModal" class="px-4 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-700">Cancelar</button>
            <button @click="submitSeriesConversion" class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Convertir con series</button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Loading overlay -->
    <div v-if="loading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
        <div class="flex items-center space-x-3">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 dark:border-blue-400"></div>
          <span class="text-gray-700 dark:text-gray-200">Procesando...</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.ordenes-compra-index {
  min-height: 100vh;
}

@media (max-width: 640px) {
  .ordenes-compra-index .w-full {
    padding-left: 1rem;
    padding-right: 1rem;
  }

  .ordenes-compra-index h1 {
    font-size: 1.5rem;
  }
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.ordenes-compra-index > * {
  animation: fadeIn 0.3s ease-out;
}

/* Estilos para DocumentosTable */
.custom-scrollbar { scrollbar-width: thin; scrollbar-color: #d1d5db #f3f4f6; }
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f3f4f6; border-radius: 3px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #9ca3af; }

.dark .custom-scrollbar { scrollbar-color: #4b5563 #1f2937; }
.dark .custom-scrollbar::-webkit-scrollbar-track { background: #1f2937; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #4b5563; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #6b7280; }

.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.4; }

/* Standard line-clamp property for compatibility */
.line-clamp-1 {
  overflow: hidden;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 1;
  line-clamp: 1;
}
.line-clamp-2 {
  overflow: hidden;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  line-clamp: 2;
}
.line-clamp-3 {
  overflow: hidden;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 3;
  line-clamp: 3;
}

@media (prefers-contrast: high) {
  .border-gray-200 { border-color: #d1d5db; }
  .dark .border-gray-200 { border-color: #4b5563; } /* Added for high contrast in dark mode */
}

button:focus-visible { outline: 2px solid; outline-offset: 2px; }

@media (hover: none) {
  .hover\:bg-white:hover { background-color: transparent; }
  .group:hover { transform: none; }
}

/* Estilos para Modal */
.modal-enter-active,
.modal-leave-active { transition: opacity 0.25s ease, transform 0.25s ease; }
.modal-enter-from,
.modal-leave-to { opacity: 0; transform: scale(0.97); }
.modal-enter-to,
.modal-leave-from { opacity: 1; transform: scale(1); }
</style>




