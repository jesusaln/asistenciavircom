<!-- /resources/js/Pages/Ventas/Index.vue -->
<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { router, Head } from '@inertiajs/vue3'
import axios from 'axios'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import { generarPDF } from '@/Utils/pdfGenerator'
import AppLayout from '@/Layouts/AppLayout.vue'
import VentasHeader from '@/Components/IndexComponents/VentasHeader.vue'
import VentasTable from '@/Components/IndexComponents/VentasTable.vue'
import ModalVenta from '@/Components/IndexComponents/ModalVenta.vue'
import Pagination from '@/Components/Pagination.vue'
import { useCompanyColors } from '@/Composables/useCompanyColors'

defineOptions({ layout: AppLayout })

// Colores de empresa
const { colors, cssVars } = useCompanyColors()

const props = defineProps({
  ventas: {
    type: Object,
    default: () => ({ data: [] })
  },
  estadisticas: {
    type: Object,
    default: () => ({
      total: 0,
      borrador: 0,
      aprobadas: 0,
      pendientes: 0,
      cancelada: 0
    })
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  sorting: {
    type: Object,
    default: () => ({ sort_by: 'created_at', sort_direction: 'desc' })
  },
  pagination: {
    type: Object,
    default: () => ({})
  }
})

/* =========================
   Configuraci�n de notificaciones
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
const selectedId = ref(null)
const loading = ref(false)

// Modal de pago
const showPaymentModal = ref(false)
const selectedVenta = ref(null)
const metodoPago = ref('')
const notasPago = ref('')

// Modal de cancelaci�n
const showCancelModal = ref(false)
const selectedVentaCancel = ref(null)
const motivoCancelacion = ref('')
const forceWithPayments = ref(false)

// Modal de eliminaci�n
// Modal de eliminacin
const showDeleteModal = ref(false)
const selectedVentaDelete = ref(null)

// Modal de envo de email
const showEmailModal = ref(false)
const selectedVentaEmail = ref(null)

const abrirDetalles = (row) => {
  fila.value = row || null
  showModal.value = true
}

const cerrarModal = () => {
  showModal.value = false
  fila.value = null
}

/* =========================
   Filtros, orden y datos
========================= */
const searchTerm = ref('')
const sortBy = ref('fecha-desc')
const filtroCfdi = ref('')
const ventasOriginales = ref([...props.ventas.data])

/* =========================
    Auditor�a - ahora manejada directamente en ModalVenta
========================= */

/* =========================
    Datos para los componentes
 ========================= */
const documentosVentas = computed(() => {
  return [...ventasOriginales.value]
})

/* =========================
    Paginaci�n del servidor
========================= */
const paginationData = computed(() => ({
  current_page: props.pagination?.current_page || 1,
  last_page: props.pagination?.last_page || 1,
  per_page: props.pagination?.per_page || 10,
  from: props.pagination?.from || 0,
  to: props.pagination?.to || 0,
  total: props.pagination?.total || 0,
}))

const goToPage = (page) => {
  const query = {
    page,
    search: searchTerm.value,
    cfdi: filtroCfdi.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc'
  }
  router.visit('/ventas', { data: query })
}

const nextPage = () => {
  const currentPage = props.pagination?.current_page || 1
  const lastPage = props.pagination?.last_page || 1

  if (currentPage < lastPage) {
    goToPage(currentPage + 1)
  }
}

const prevPage = () => {
  const currentPage = props.pagination?.current_page || 1

  if (currentPage > 1) {
    goToPage(currentPage - 1)
  }
}

const changePerPage = (event) => {
  const perPage = event.target.value
  const query = {
    per_page: perPage,
    search: searchTerm.value,
    cfdi: filtroCfdi.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc'
  }
  router.visit('/ventas', { data: query })
}

const handleSearch = () => {
  const query = {
    search: searchTerm.value,
    cfdi: filtroCfdi.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc'
  }
  router.visit('/ventas', { data: query })
}

const handleFilter = () => {
  const query = {
    search: searchTerm.value,
    cfdi: filtroCfdi.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc'
  }
  router.visit('/ventas', { data: query })
}

/* =========================
    Paginaci�n del lado del cliente (legacy - mantener por compatibilidad)
========================= */
const currentPage = ref(1)
const perPage = ref(10)

// Ventas filtradas y ordenadas (sin paginaci�n)
const ventasFiltradasYOrdenadas = computed(() => {
  let result = [...ventasOriginales.value]

  // Aplicar filtro de b�squeda
  if (searchTerm.value.trim()) {
    const search = searchTerm.value.toLowerCase().trim()
    result = result.filter(venta => {
      const cliente = venta.cliente?.nombre?.toLowerCase() || ''
      const numero = String(venta.numero_venta || venta.id || '').toLowerCase()
      const estado = venta.estado?.toLowerCase() || ''

      return cliente.includes(search) ||
             numero.includes(search) ||
             estado.includes(search)
    })
  }

  // Aplicar filtro CFDI (timbradas / sin timbrar)
  if (filtroCfdi.value) {
    result = result.filter(venta => {
      const timbrada = Boolean(venta.esta_facturada || venta.factura_uuid)
      return filtroCfdi.value === 'timbrada' ? timbrada : !timbrada
    })
  }

  // Aplicar ordenamiento
  if (sortBy.value) {
    const [field, order] = sortBy.value.split('-')
    const isDesc = order === 'desc'

    result.sort((a, b) => {
      let valueA, valueB

      switch (field) {
        case 'fecha':
          valueA = new Date(a.fecha || a.created_at || 0)
          valueB = new Date(b.fecha || b.created_at || 0)
          break
        case 'cliente':
          valueA = a.cliente?.nombre || ''
          valueB = b.cliente?.nombre || ''
          break
        case 'total':
          valueA = parseFloat(a.total || 0)
          valueB = parseFloat(b.total || 0)
          break
        case 'estado':
          valueA = a.estado || ''
          valueB = b.estado || ''
          break
        default:
          valueA = a[field] || ''
          valueB = b[field] || ''
      }

      if (valueA < valueB) return isDesc ? 1 : -1
      if (valueA > valueB) return isDesc ? -1 : 1
      return 0
    })
  }

  return result
})

// Documentos para mostrar (usando paginaci�n del servidor)
const documentosVentasPaginados = computed(() => {
  return props.ventas.data || []
})

// Informaci�n de paginaci�n
const totalPages = computed(() => Math.ceil(ventasFiltradasYOrdenadas.value.length / perPage.value))
const totalFiltered = computed(() => ventasFiltradasYOrdenadas.value.length)

// Usar paginaci�n del servidor

// Watch para resetear p�gina cuando cambien filtros
watch([searchTerm, sortBy, filtroCfdi, perPage], () => {
  currentPage.value = 1
}, { deep: true })

// Manejo de paginaci�n
const handlePerPageChange = (newPerPage) => {
  perPage.value = newPerPage
  currentPage.value = 1 // Reset to first page when changing per_page
}

const handlePageChange = (newPage) => {
  currentPage.value = newPage
}

// Watchers para props y filtros
watch(() => props.ventas, (newVal) => {
  if (newVal && newVal.data && Array.isArray(newVal.data)) {
    ventasOriginales.value = [...newVal.data]
  }
}, { deep: true, immediate: true })

// Watcher para estadsticas del backend
watch(() => props.estadisticas, (newVal) => {
  if (newVal && typeof newVal === 'object') {
    // Las estadsticas se actualizarn automticamente en el computed
    // console.log('Estadsticas del backend actualizadas:', newVal)
  }
}, { deep: true, immediate: true })

// Resetear pgina al cambiar filtros
watch([searchTerm, filtroCfdi], () => {
  currentPage.value = 1
})

// Ajustar p�gina si se queda sin elementos despu�s de eliminar
watch(totalPages, (newTotal) => {
  if (currentPage.value > newTotal && newTotal > 0) {
    currentPage.value = newTotal
  }
})

// Estad�sticas calculadas (usar backend si est� disponible, sino calcular localmente)
const estadisticas = computed(() => {
  // Si tenemos estad�sticas del backend, usarlas
  if (props.estadisticas && props.estadisticas.total > 0) {
    return {
      total: props.estadisticas.total || 0,
      borrador: props.estadisticas.borrador || 0,
      aprobados: props.estadisticas.aprobadas || 0,
      pendientes: props.estadisticas.pendientes || 0,
      cancelado: props.estadisticas.cancelada || 0,
    };
  }

  // Calcular localmente como fallback
  const stats = {
    total: ventasOriginales.value.length,
    aprobados: 0,
    pendientes: 0,
    borrador: 0,
    cancelado: 0,
    pagadas: 0, // Agregar estado pagado
    enviadas: 0,
    facturadas: 0,
    vencidas: 0,
    anuladas: 0,
  };

  ventasOriginales.value.forEach(v => {
    switch (String(v.estado || '').toLowerCase()) {
      case 'aprobada':
        stats.aprobados++; break;
      case 'pendiente':
        stats.pendientes++; break;
      case 'borrador':
        stats.borrador++; break;
      case 'cancelada':
        stats.cancelado++; break;
      case 'pagado':
        stats.pagadas++; break;
      case 'enviada':
        stats.enviadas++; break;
      case 'facturada':
        stats.facturadas++; break;
      case 'vencido':
        stats.vencidas++; break;
      case 'anulado':
        stats.anuladas++; break;
    }
  });

  return stats;
});

const handleLimpiarFiltros = () => {
  searchTerm.value = ''
  sortBy.value = 'fecha-desc'
  filtroCfdi.value = ''
  perPage.value = 10
  currentPage.value = 1
  notyf.success('Filtros limpiados correctamente')
}

const updateSort = (newSort) => {
  if (newSort && typeof newSort === 'string') {
    sortBy.value = newSort
    currentPage.value = 1 // Resetear p�gina al cambiar ordenamiento
  }
}

/* =========================
    Helpers
========================= */
const formatearMoneda = (num) => {
  const value = parseFloat(num)
  const safe = Number.isFinite(value) ? value : 0
  return new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(safe)
}

// ? MEDIUM PRIORITY FIX #11: Helper para manejo consistente de errores
const extractErrorMessage = (error, defaultMessage = 'Ha ocurrido un error') => {
  // Prioridad 1: error.response.data.error
  if (error.response?.data?.error) {
    return error.response.data.error
  }
  // Prioridad 2: error.response.data.message
  if (error.response?.data?.message) {
    return error.response.data.message
  }
  // Prioridad 3: error.message
  if (error.message) {
    return error.message
  }
  // Fallback: mensaje por defecto
  return defaultMessage
}

/* =========================
   Validaciones y utilidades
========================= */
function validarVenta(venta) {
  if (!venta?.id) {
    throw new Error('ID de venta no v�lido')
  }
  return true
}

function validarVentaParaPDF(doc) {
  if (!doc.id) throw new Error('ID del documento no encontrado')
  if (!doc.cliente) throw new Error('Datos del cliente no encontrados')
  if (!Array.isArray(doc.productos) || !doc.productos.length) {
    throw new Error('Lista de productos no v�lida')
  }
  if (!doc.fecha) throw new Error('Fecha no especificada')
  return true
}

/* =========================
   Acciones CRUD
========================= */
const verDetalles = (venta) => {
  try {
    validarVenta(venta)
    abrirDetalles(venta)
  } catch (error) {
    notyf.error(error.message)
  }
}

const editarVenta = (id) => {
  try {
    const ventaId = id || fila.value?.id
    if (!ventaId) throw new Error('ID de venta no v�lido')

    router.visit(`/ventas/${ventaId}/edit`)
  } catch (error) {
    notyf.error(error.message)
  }
}

const editarFila = (id) => {
  editarVenta(id)
}

const duplicarVenta = async (venta) => {
  try {
    validarVenta(venta)

    if (!confirm(`�Duplicar venta #${venta.numero_venta || venta.id}?`)) {
      return
    }

    loading.value = true
    notyf.success('Duplicando venta...')

    const { data } = await axios.post(`/ventas/${venta.id}/duplicate`)

    if (data?.success) {
      notyf.success(data.message || 'Venta duplicada exitosamente')

      // Recargar la p�gina para mostrar la venta duplicada
      router.visit('/ventas', {
        method: 'get',
        replace: true
      })
    } else {
      throw new Error(data?.error || 'Error al duplicar la venta')
    }

  } catch (error) {
    console.error('Error al duplicar:', error)
    // ? MEDIUM PRIORITY FIX #11: Manejo consistente de errores
    const mensaje = extractErrorMessage(error, 'Error al duplicar la venta')
    notyf.error(mensaje)
  } finally {
    loading.value = false
  }
}

const imprimirVenta = async (venta) => {
  try {
    // Descargar el PDF usando axios
    const response = await axios.get(`/ventas/${venta.id}/pdf`, {
      responseType: 'blob'
    })

    // Crear URL del blob
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)

    // Crear enlace de descarga
    const link = document.createElement('a')
    link.href = url
    link.download = `venta-${venta.numero_venta || venta.id}.pdf`
    link.style.display = 'none'

    // Agregar al DOM, hacer clic y remover
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)

    // Liberar el objeto URL
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Error al descargar PDF:', error)
    notyf.error('Error al descargar el PDF')
  }
}

const imprimirFila = () => {
  if (fila.value) {
    imprimirVenta(fila.value)
  }
}

const confirmarEliminacion = (id) => {
  try {
    console.log('confirmarEliminacion llamado con ID:', id)
    if (!id) throw new Error('ID de venta no v�lido')

    selectedId.value = id
    // Tambi�n establecer fila para que el modal muestre informaci�n si es posible
    const venta = ventasOriginales.value.find(v => v.id === id)
    if (venta) fila.value = venta

    showModal.value = true
  } catch (error) {
    console.error('Error en confirmarEliminacion:', error)
    notyf.error(error.message)
  }
}

const eliminarVenta = async (idParam = null) => {
  // Esta funci�n maneja la l�gica de "Trash Icon" en la tabla
  try {
    // Si idParam es un evento (objeto), ignorarlo
    if (idParam && typeof idParam !== 'object') {
      selectedId.value = idParam
    }

    const ventaId = selectedId.value || fila.value?.id

    if (!ventaId) {
      throw new Error('No se seleccion� ninguna venta')
    }

    const venta = ventasOriginales.value.find(v => v.id === ventaId)
    const isCancelada = venta?.estado === 'cancelada' || venta?.estado === 'cancelado'

    if (isCancelada) {
      // Si ya est� cancelada, mostrar confirmaci�n de borrado real
      confirmarBorradoReal(ventaId)
    } else {
      // Si no est� cancelada, mostrar modal de cancelaci�n
      cancelarVenta(ventaId)
    }

  } catch (error) {
    console.error('Error en eliminarVenta:', error)
    notyf.error(error.message)
  }
}

const crearNuevaVenta = () => {
  router.visit('/ventas/create')
}

const marcarComoPagado = (venta) => {
  selectedVenta.value = venta
  metodoPago.value = ''
  notasPago.value = ''
  showPaymentModal.value = true
}

const cerrarPaymentModal = () => {
  showPaymentModal.value = false
  selectedVenta.value = null
  metodoPago.value = ''
  notasPago.value = ''
}

// ? MEDIUM PRIORITY FIX #10: Validaciones completas en modal de pago
const confirmarPago = async () => {
  // Validaci�n 1: M�todo de pago
  if (!metodoPago.value) {
    notyf.error('Debe seleccionar un m�todo de pago')
    return
  }

  // ? Validaci�n 2: Venta seleccionada
  if (!selectedVenta.value) {
    notyf.error('No hay venta seleccionada')
    return
  }

  // ? Validaci�n 3: Venta no cancelada
  if (selectedVenta.value.estado === 'cancelada' || selectedVenta.value.estado === 'cancelado') {
    notyf.error('No se puede marcar como pagada una venta cancelada')
    return
  }

  // ? Validaci�n 4: Venta no pagada previamente
  if (selectedVenta.value.pagado) {
    notyf.error('Esta venta ya est� marcada como pagada')
    return
  }

  // ? Validaci�n 5: Total mayor a cero
  if (!selectedVenta.value.total || selectedVenta.value.total <= 0) {
    notyf.error('El total de la venta debe ser mayor a cero')
    return
  }

  try {
    loading.value = true
    notyf.success('Procesando pago...')

    console.log('Enviando pago:', {
      id: selectedVenta.value.id,
      metodo_pago: metodoPago.value,
      notas_pago: notasPago.value
    })

    const { data } = await axios.post(`/ventas/${selectedVenta.value.id}/marcar-pagado`, {
      metodo_pago: metodoPago.value,
      notas_pago: notasPago.value
    })

    if (data?.success) {
      notyf.success(data.message || 'Venta marcada como pagada exitosamente')

      // Actualizar datos locales
      const index = ventasOriginales.value.findIndex(v => v.id === selectedVenta.value.id)
      if (index !== -1) {
        ventasOriginales.value[index] = {
          ...ventasOriginales.value[index],
          estado: 'aprobada',
          pagado: true,
          metodo_pago: metodoPago.value,
          fecha_pago: new Date().toISOString().split('T')[0],
          notas_pago: notasPago.value
        }
      }

      cerrarPaymentModal()

      // Recargar la p�gina para actualizar estad�sticas y datos del backend
      router.visit('/ventas', {
        method: 'get',
        replace: true
      })
    } else {
      throw new Error(data?.error || 'Error al procesar el pago')
    }

  } catch (error) {
    // ? MEDIUM PRIORITY FIX #11: Manejo consistente de errores
    const mensaje = extractErrorMessage(error, 'Error al procesar el pago')
    console.error('Error al marcar como pagado:', error)
    notyf.error(mensaje)
  } finally {
    loading.value = false
  }
}

const cancelarVenta = (id) => {
  // Find the venta by id
  const venta = ventasOriginales.value.find(v => v.id === id)
  if (!venta) {
    notyf.error('Venta no encontrada')
    return
  }

  selectedVentaCancel.value = venta
  motivoCancelacion.value = ''
  showCancelModal.value = true
}

const confirmarCancelacion = () => {
  if (!selectedVentaCancel.value) return

  router.post(route('ventas.cancel', selectedVentaCancel.value.id), {
    motivo: motivoCancelacion.value,
    force_with_payments: forceWithPayments.value
  }, {
    onStart: () => {
      loading.value = true
      notyf.success('Cancelando venta...')
    },
    onSuccess: () => {
      notyf.success('Venta cancelada exitosamente')
      showCancelModal.value = false
      selectedVentaCancel.value = null
      motivoCancelacion.value = ''
      loading.value = false
      // Recargar la p�gina para actualizar datos
      router.visit('/ventas', { method: 'get', replace: true })
    },
    onError: (errors) => {
      console.error('Error al cancelar:', errors)
      const msg = errors.error || 'Error al cancelar la venta'
      notyf.error(msg)
      loading.value = false
    },
    onFinish: () => {
      loading.value = false
    }
  })
}

const cerrarCancelModal = () => {
  showCancelModal.value = false
  selectedVentaCancel.value = null
  motivoCancelacion.value = ''
  forceWithPayments.value = false
}

const cerrarDeleteModal = () => {
  showDeleteModal.value = false
  selectedVentaDelete.value = null
}

const cerrarEmailModal = () => {
    showEmailModal.value = false
    selectedVentaEmail.value = null
}

const confirmarBorradoReal = (id) => {
  // Esta funci�n se llama desde el ModalVenta cuando se hace clic en "Eliminar Venta"
  // O desde la tabla si la venta ya est� cancelada
  const venta = ventasOriginales.value.find(v => v.id === id)
  if (!venta) {
    notyf.error('Venta no encontrada')
    return
  }
  
  // Cerrar otros modales si est�n abiertos
  showModal.value = false
  
  selectedVentaDelete.value = venta
  showDeleteModal.value = true
}

const ejecutarBorrado = async () => {
  if (!selectedVentaDelete.value) return
  
  const ventaId = selectedVentaDelete.value.id
  
  try {
    loading.value = true
    notyf.success('Eliminando venta...')
    
    await axios.delete(`/ventas/${ventaId}`)
    
    notyf.success('Venta eliminada exitosamente')
    showDeleteModal.value = false
    selectedVentaDelete.value = null
    
    // Recargar la p�gina
    router.visit('/ventas', {
      method: 'get',
      replace: true
    })
    
  } catch (error) {
    console.error('Error al eliminar:', error)
    const mensaje = extractErrorMessage(error, 'Error al eliminar la venta')
    notyf.error(mensaje)
  } finally {
    loading.value = false
  }
}

// Funci�n para enviar venta por email
const enviarVentaPorEmail = async (venta) => {
  try {
    // Verificar que el cliente tenga email
    if (!venta.cliente?.email) {
      notyf.error('El cliente no tiene email configurado')
      return
    }

    selectedVentaEmail.value = {
      ...venta,
      numero_venta: venta.numero_venta || `V${String(venta.id).padStart(3, "0")}`,
      email_destino: venta.cliente.email
    }
    showEmailModal.value = true

  } catch (error) {
    console.error('Error en enviarVentaPorEmail:', error)
    notyf.error('Error inesperado al preparar env�o de venta')
  }
}

// Funci�n para confirmar env�o de email
const confirmarEnvioEmail = async () => {
  try {
    const venta = selectedVentaEmail.value
    if (!venta?.email_destino) {
      notyf.error('Email de destino no v�lido')
      return
    }

    console.log('? Usuario confirm� env�o de venta por email');
    loading.value = true
    cerrarEmailModal()

    // Usar axios para tener control total sobre la respuesta
    const { data } = await axios.post(`/ventas/${venta.id}/enviar-email`, {
      email_destino: venta.email_destino,
    })

    if (data?.success) {
      notyf.success(data.message || 'Venta enviada por email correctamente')

      // Actualizar estado local de la venta usando los datos del servidor
      const index = ventasOriginales.value.findIndex(v => v.id === venta.id)
      if (index !== -1 && data.venta) {
        ventasOriginales.value[index] = {
          ...ventasOriginales.value[index],
          email_enviado: data.venta.email_enviado,
          email_enviado_fecha: data.venta.email_enviado_fecha,
          estado: data.venta.estado
        }
      }
    } else {
      throw new Error(data?.error || 'Error desconocido al enviar email')
    }

  } catch (error) {
    console.error('Error al enviar venta:', error)
    // ? MEDIUM PRIORITY FIX #11: Manejo consistente de errores
    const mensaje = extractErrorMessage(error, 'Error al enviar venta')
    notyf.error(mensaje)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div :style="cssVars">
    <Head title="Ventas" />

    <div class="ventas-index min-h-screen bg-gray-50">
    <!-- Contenido principal -->
    <div class="w-full px-6 py-8">
      <!-- Header espec�fico de ventas -->
      <VentasHeader
        :total="estadisticas.total"
        :borrador="estadisticas.borrador"
        :aprobadas="estadisticas.aprobados"
        :pendientes="estadisticas.pendientes"
        :cancelada="estadisticas.cancelado"
        v-model:search-term="searchTerm"
        v-model:sort-by="sortBy"
        v-model:filtro-cfdi="filtroCfdi"
        :config="{
          module: 'ventas',
          createButtonText: 'Nueva Venta',
          searchPlaceholder: 'Buscar por cliente, número...'
        }"
        @limpiar-filtros="handleLimpiarFiltros"
        @crear-nuevo="crearNuevaVenta"
        @search-change="handleSearch"
        @filtro-cfdi-change="handleFilter"
      />

      <!-- Tabla espec�fica de ventas -->
      <div class="mt-6">
        <VentasTable
          :documentos="documentosVentasPaginados"
          :search-term="searchTerm"
          :sort-by="sortBy"
          @ver-detalles="verDetalles"
          @editar="editarVenta"
          @eliminar="confirmarEliminacion"
          @marcar-pagado="marcarComoPagado"
          @cancelar="cancelarVenta"
          @enviar-email="enviarVentaPorEmail"
          @imprimir="imprimirVenta"
          @sort="updateSort"
        />

        <!-- Controles de paginaci�n -->
        <div v-if="paginationData.last_page > 1" class="flex justify-center items-center space-x-2 mt-6">
          <button
            @click="prevPage"
            :disabled="paginationData.current_page === 1"
            class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Anterior
          </button>

          <div class="flex space-x-1">
            <button
              v-for="page in [paginationData.current_page - 1, paginationData.current_page, paginationData.current_page + 1].filter(p => p > 0 && p <= paginationData.last_page)"
              :key="page"
              @click="goToPage(page)"
              :class="[
                'px-3 py-2 text-sm font-medium border rounded-md transition-all duration-200',
                page === paginationData.current_page
                  ? 'text-white shadow-md'
                  : 'text-gray-700 bg-white hover:bg-gray-50 border-gray-300'
              ]"
              :style="page === paginationData.current_page ? { backgroundColor: colors.principal, borderColor: colors.principal } : {}"
            >
              {{ page }}
            </button>
          </div>

          <button
            @click="nextPage"
            :disabled="paginationData.current_page === paginationData.last_page"
            class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Siguiente
          </button>
        </div>

        <!-- Informaci�n de paginaci�n -->
        <div class="flex justify-between items-center mt-4 text-sm text-gray-600">
          <div>
            Mostrando {{ paginationData.from }} - {{ paginationData.to }} de {{ paginationData.total }} ventas
          </div>
          <div class="flex items-center space-x-2">
            <span>Elementos por p�gina:</span>
            <select
              :value="paginationData.per_page"
              @change="changePerPage"
              class="border border-gray-300 rounded px-2 py-1 text-sm"
            >
              <option value="10">10</option>
              <option value="15">15</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
          </div>
        </div>
      </div>

    </div>

    <!-- Modal de detalles de Venta -->
    <!-- Modal de detalles de Venta -->
    <ModalVenta
      :show="showModal"
      :selected="fila || {}"
      @close="cerrarModal"
      @marcar-pagado="marcarComoPagado"
      @cancelar="cancelarVenta"
      @eliminar="confirmarBorradoReal"
    />

    <!-- Modal de Pago -->
    <div v-if="showPaymentModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="cerrarPaymentModal">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-xl max-h-[90vh] overflow-y-auto max-h-[90vh] overflow-y-auto">
        <!-- Header del modal -->
        <div class="flex items-center justify-between p-6 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Marcar como Pagado</h3>
          <button @click="cerrarPaymentModal" class="text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-6">
          <div v-if="selectedVenta" class="space-y-4">
            <!-- Informaci�n de la venta -->
            <div class="bg-gray-50 p-4 rounded-lg">
              <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-gray-700">Venta:</span>
                <span class="text-sm font-mono text-gray-900">{{ selectedVenta.numero_venta }}</span>
              </div>
              <div class="flex justify-between items-center mt-2">
                <span class="text-sm font-medium text-gray-700">Total:</span>
                <span class="text-lg font-bold text-gray-900">${{ formatearMoneda(selectedVenta.total) }}</span>
              </div>
            </div>

            <!-- M�todo de pago -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">M�todo de Pago *</label>
              <select
                v-model="metodoPago"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">Seleccionar m�todo...</option>
                <option value="efectivo">Efectivo</option>
                <option value="transferencia">Transferencia</option>
                <option value="cheque">Cheque</option>
                <option value="tarjeta">Tarjeta</option>
                <option value="otros">Otros</option>
              </select>
            </div>

            <!-- Notas del pago -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Notas (opcional)</label>
              <textarea
                v-model="notasPago"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Agregar notas sobre el pago..."
              ></textarea>
            </div>
          </div>
        </div>

        <!-- Footer del modal -->
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
          <button @click="cerrarPaymentModal" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
            Cancelar
          </button>
          <button
            @click="confirmarPago"
            :disabled="!metodoPago || loading"
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <span v-if="loading" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Procesando...
            </span>
            <span v-else>Confirmar Pago</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal para Cancelar Venta -->
    <div v-if="showCancelModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="cerrarCancelModal">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-6 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Cancelar Venta</h3>
          <button @click="cerrarCancelModal" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="p-6 space-y-4">
          <div class="bg-red-50 p-4 rounded-lg">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">&iquest;Est&aacute; seguro de que desea cancelar esta venta?</h3>
                <div class="mt-2 text-sm text-red-700">
                  <p>Esta acci&oacute;n:</p>
                  <ul class="list-disc list-inside mt-1 space-y-1">
                    <li>Devolverá&aacute; el inventario al almac&eacute;n</li>
                    <li>Devolverá&aacute; las series a estado disponible</li>
                    <li>Cambiará&aacute; el estado de la venta a "Cancelada"</li>
                    <li>No se podrá&aacute; deshacer esta acci&oacute;n</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="flex justify-between items-center">
              <span class="text-sm font-medium text-gray-700">Venta:</span>
              <span class="text-sm font-mono text-gray-900">{{ selectedVentaCancel?.numero_venta }}</span>
            </div>
            <div class="flex justify-between items-center mt-2">
              <span class="text-sm font-medium text-gray-700">Total:</span>
              <span class="text-lg font-bold text-gray-900">{{ formatearMoneda(selectedVentaCancel?.total) }}</span>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Motivo de cancelaci&oacute;n (opcional)</label>
            <textarea v-model="motivoCancelacion" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500" placeholder="Explique el motivo de la cancelaci�n..."></textarea>
          </div>
          <!-- Admin Force Cancel Option -->
          <div class="bg-orange-50 p-4 rounded-lg border border-orange-200">
            <div class="flex items-start">
              <input id="forceCancelIndex" type="checkbox" v-model="forceWithPayments" 
                     class="h-4 w-4 mt-1 text-orange-600 focus:ring-orange-500 border-gray-300 rounded" />
              <label for="forceCancelIndex" class="ml-3">
                <span class="text-sm font-medium text-orange-800">Forzar cancelaci&oacute;n con pagos (Admin)</span>
                <p class="text-xs text-orange-700 mt-1">
                  Marcar si la venta tiene pagos registrados. Se eliminar�n los registros de entrega de dinero.
                </p>
              </label>
            </div>
          </div>
        </div>
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
          <button @click="cerrarCancelModal" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
            Cancelar
          </button>
          <button @click="confirmarCancelacion" :disabled="loading" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
            <span v-if="loading" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Procesando...
            </span>
            <span>Confirmar Cancelaci&oacute;n</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal para Eliminar Venta (Irreversible) -->
    <div v-if="showDeleteModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="cerrarDeleteModal">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-6 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Eliminar Venta</h3>
          <button @click="cerrarDeleteModal" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="p-6 space-y-4">
          <div class="bg-red-50 p-4 rounded-lg">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">&iquest;Est&aacute; seguro de que desea ELIMINAR esta venta?</h3>
                <div class="mt-2 text-sm text-red-700">
                  <p class="font-bold">Esta acci&oacute;n es IRREVERSIBLE.</p>
                  <ul class="list-disc list-inside mt-1 space-y-1">
                    <li>La venta se eliminar&aacute; permanentemente del sistema.</li>
                    <li>Se mantendrá&aacute; un registro de auditor&iacute;a.</li>
                    <li>Aseg&uacute;rese de que la venta ya est&aacute; cancelada.</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="flex justify-between items-center">
              <span class="text-sm font-medium text-gray-700">Venta:</span>
              <span class="text-sm font-mono text-gray-900">{{ selectedVentaDelete?.numero_venta }}</span>
            </div>
            <div class="flex justify-between items-center mt-2">
              <span class="text-sm font-medium text-gray-700">Total:</span>
              <span class="text-lg font-bold text-gray-900">{{ formatearMoneda(selectedVentaDelete?.total) }}</span>
            </div>
          </div>
        </div>
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
          <button @click="cerrarDeleteModal" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
            Cancelar
          </button>
          <button @click="ejecutarBorrado" :disabled="loading" class="px-4 py-2 bg-red-700 text-white rounded-lg hover:bg-red-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
            <span v-if="loading" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Eliminando...
            </span>
            <span>Confirmar Eliminaci&oacute;n</span>
          </button>
        </div>
      </div>
    </div>
    </div>

    <!-- Modal de Envo de Email -->
    <div v-if="showEmailModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="cerrarEmailModal">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-xl">
        <div class="flex items-center justify-between p-6 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Enviar Venta por Email</h3>
          <button @click="cerrarEmailModal" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="p-6 space-y-4">
          <div class="bg-blue-50 p-4 rounded-lg">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Confirmar env&iacute;o</h3>
                <div class="mt-2 text-sm text-blue-700">
                  <p>Se enviar&aacute; el PDF de la venta al correo del cliente.</p>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-gray-50 p-4 rounded-lg">
             <div class="flex justify-between items-center mb-2">
              <span class="text-sm font-medium text-gray-700">Venta:</span>
              <span class="text-sm font-mono text-gray-900">{{ selectedVentaEmail?.numero_venta }}</span>
            </div>
            <div class="flex justify-between items-center mb-2">
              <span class="text-sm font-medium text-gray-700">Cliente:</span>
              <span class="text-sm text-gray-900">{{ selectedVentaEmail?.cliente?.nombre || selectedVentaEmail?.cliente?.nombre_razon_social }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm font-medium text-gray-700">Email destino:</span>
              <span class="text-sm font-mono text-gray-900 bg-white px-2 py-1 rounded border border-gray-200">{{ selectedVentaEmail?.email_destino }}</span>
            </div>
          </div>
        </div>
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
          <button @click="cerrarEmailModal" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
            Cancelar
          </button>
          <button @click="confirmarEnvioEmail" :disabled="loading" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
             <span v-if="loading" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Enviando...
            </span>
            <span v-else>Enviar Email</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Loading overlay -->
    <div v-if="loading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded-lg shadow-lg">
        <div class="flex items-center space-x-3">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
          <span class="text-gray-700">Procesando...</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.ventas-index {
  min-height: 100vh;
  background-color: #f9fafb;
}

/* Responsive */
@media (max-width: 640px) {
  .ventas-index .max-w-7xl {
    padding-left: 1rem;
    padding-right: 1rem;
  }

  .ventas-index h1 {
    font-size: 1.5rem;
  }
}

/* Animaciones suaves */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.ventas-index > * {
  animation: fadeIn 0.3s ease-out;
}
</style>




