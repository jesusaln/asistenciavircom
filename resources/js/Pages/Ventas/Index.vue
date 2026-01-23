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
import DialogModal from '@/Components/DialogModal.vue'
import Pagination from '@/Components/Pagination.vue'
import { useCompanyColors } from '@/Composables/useCompanyColors'

defineOptions({ layout: AppLayout, inheritAttrs: false })

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

const facturarVenta = (venta) => {
  if (!venta || !venta.id) return;
  try {
      notyf.success('Redirigiendo al módulo de facturación...');
      // Redirigir al formulario de creación pasando la venta para pre-llenado
      router.visit('/facturas/crear', {
        data: { venta_id: venta.id }
      });
  } catch (error) {
      console.error(error);
      notyf.error('No se pudo redirigir a facturación');
  }
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

const onOpenEmailModal = (venta) => {
    if (!venta) return
    selectedVentaEmail.value = venta
    showEmailModal.value = true
}

const confirmarEnviarEmail = async () => {
    if (!selectedVentaEmail.value) return

    try {
        loading.value = true
        notyf.success('Enviando correo...')

        const { data } = await axios.post(`/ventas/${selectedVentaEmail.value.id}/email`)

        if (data?.success) {
            notyf.success(data.message || 'Correo enviado exitosamente')
            cerrarEmailModal()
        } else {
            throw new Error(data?.error || 'Error al enviar el correo')
        }
    } catch (error) {
        console.error('Error al enviar email:', error)
        const mensaje = extractErrorMessage(error, 'Error al enviar el correo')
        notyf.error(mensaje)
    } finally {
        loading.value = false
    }
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
</script>

<template>
    <Head title="Ventas" />
    <div :style="cssVars" class="bg-white dark:bg-slate-900 dark:bg-slate-950 min-h-screen px-6 py-8">
        <VentasHeader
                :total="estadisticas.total"
                :borrador="estadisticas.borrador"
                :aprobadas="estadisticas.aprobados"
                :pendientes="estadisticas.pendientes"
                :cancelada="estadisticas.cancelado"
                v-model:searchTerm="searchTerm"
                v-model:sortBy="sortBy"
                v-model:filtroCfdi="filtroCfdi"
                @filtrar="handleSearch"
                @filtro-cfdi-change="handleFilter"
                @limpiar-filtros="handleLimpiarFiltros"
                @crear-nuevo="crearNuevaVenta"
            />

            <div class="mt-8">
                <VentasTable
                    :documentos="documentosVentasPaginados"
                    :searchTerm="searchTerm"
                    :sortBy="sortBy"
                    @ver-detalles="verDetalles"
                    @editar="editarVenta"
                    @eliminar="confirmarBorradoReal"
                    @imprimir="imprimirVenta"
                    @sort="updateSort"
                    @marcar-pagado="marcarComoPagado"
                    @cancelar="cancelarVenta"
                    @enviar-email="onOpenEmailModal"
                    @facturar="facturarVenta"
                />
            </div>

            <!-- Paginación -->
            <div class="mt-8 bg-white dark:bg-slate-900 dark:bg-slate-900 border border-gray-100 dark:border-slate-800 rounded-3xl p-6 shadow-sm">
                <Pagination
                    :pagination-data="paginationData"
                    @page-change="goToPage"
                />
            </div>

            <!-- Modales (Movidos dentro del contenedor raíz) -->
            <!-- ModalVenta (Detalles) -->
            <ModalVenta
                v-if="showModal"
                :show="showModal"
                :selected="fila"
                @close="cerrarModal"
                @editar="editarFila"
                @imprimir="imprimirFila"
                @eliminar="confirmarBorradoReal"
            />

            <!-- Modal de Pago -->
            <DialogModal :show="showPaymentModal" @close="cerrarPaymentModal" maxWidth="md">
                <template #content>
                    <div class="bg-white dark:bg-slate-900 dark:bg-slate-950 rounded-3xl shadow-xl w-full overflow-hidden border border-gray-100 dark:border-slate-800 transform transition-all font-sans">
                        <div class="px-8 py-6 bg-white dark:bg-slate-900 dark:bg-slate-900 border-b border-gray-50 dark:border-slate-800 flex justify-between items-center">
                            <h3 class="font-black uppercase tracking-[0.15em] text-sm text-gray-900 dark:text-white dark:text-white">Registrar Pago</h3>
                            <button @click="cerrarPaymentModal" class="text-gray-300 dark:text-slate-600 hover:text-gray-900 dark:text-white dark:hover:text-white transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <div class="p-8 space-y-6 dark:bg-slate-900/50">
                            <div v-if="selectedVenta" class="mb-4">
                                <p class="text-[10px] text-gray-400 dark:text-slate-500 font-black uppercase tracking-widest mb-1">{{ selectedVenta.cliente?.nombre_razon_social || 'Venta' }} #{{ selectedVenta.numero_venta }}</p>
                                <p class="text-2xl font-black text-gray-900 dark:text-white dark:text-white">Monto: ${{ formatearMoneda(selectedVenta.total) }}</p>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-2">Método de Pago</label>
                                <select v-model="metodoPago" class="w-full py-4 px-5 bg-white dark:bg-slate-900 dark:bg-slate-900 border-2 border-gray-100 dark:border-slate-800 rounded-2xl font-bold text-gray-900 dark:text-white dark:text-white focus:border-gray-900 dark:focus:border-slate-400 focus:ring-0 transition-all">
                                    <option value="">Seleccionar...</option>
                                    <option value="efectivo">Efectivo</option>
                                    <option value="transferencia">Transferencia</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="tarjeta">Tarjeta</option>
                                    <option value="otros">Otros</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-2">Notas / Referencia</label>
                                <textarea v-model="notasPago" rows="2" class="w-full px-5 py-4 bg-white dark:bg-slate-900 dark:bg-slate-900 border-2 border-gray-100 dark:border-slate-800 rounded-2xl font-bold text-gray-900 dark:text-white dark:text-white focus:border-gray-900 dark:focus:border-slate-400 focus:ring-0 transition-all" placeholder="Referencia de pago..."></textarea>
                            </div>
                        </div>

                        <div class="px-8 py-6 bg-white dark:bg-slate-900/50 dark:bg-slate-950 border-t border-gray-100 dark:border-slate-800 flex flex-col gap-3">
                            <button @click="confirmarPago" :disabled="loading" class="w-full py-4 bg-gray-900 dark:bg-white dark:bg-slate-900 text-white dark:text-slate-900 rounded-2xl font-black uppercase text-[10px] tracking-[0.2em] shadow-xl disabled:opacity-50 flex items-center justify-center gap-3 transition-transform active:scale-95">
                                <span v-if="loading" class="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin"></span>
                                {{ loading ? 'Procesando...' : 'Confirmar Pago' }}
                            </button>
                            <button @click="cerrarPaymentModal" class="w-full py-3 font-black text-gray-400 dark:text-slate-500 hover:text-gray-900 dark:text-white dark:hover:text-white uppercase text-[10px] tracking-widest transition-colors">Cancelar</button>
                        </div>
                    </div>
                </template>
            </DialogModal>

            <!-- Modal de Cancelación -->
            <DialogModal :show="showCancelModal" @close="cerrarCancelModal" maxWidth="md">
                <template #content>
                    <div class="bg-white dark:bg-slate-900 dark:bg-slate-950 rounded-3xl shadow-xl w-full overflow-hidden border border-red-100 dark:border-red-900/30 transform transition-all font-sans">
                        <div class="px-8 py-6 bg-red-50 dark:bg-red-950 border-b border-red-100 dark:border-red-900/30 flex justify-between items-center">
                            <h3 class="font-black uppercase tracking-[0.15em] text-sm text-red-700 dark:text-red-400">Cancelar Venta</h3>
                            <button @click="cerrarCancelModal" class="text-red-300 hover:text-red-700 dark:hover:text-red-300 transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <div class="p-8 space-y-6">
                            <div class="flex items-start gap-4 p-4 bg-red-50/50 dark:bg-red-900/10 rounded-2xl border border-red-100/50 dark:border-red-900/20">
                                <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-red-800 dark:text-red-400 uppercase tracking-widest mb-1">¡Atención!</p>
                                    <p class="text-xs font-bold text-red-600/80 dark:text-red-400/60 leading-relaxed italic">Esta acción liberará el stock y cancelará los folios de las series vendidas. Esta operación NO se puede deshacer.</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-2">Motivo de Cancelación</label>
                                <textarea v-model="motivoCancelacion" rows="3" class="w-full px-5 py-4 bg-white dark:bg-slate-900 dark:bg-slate-900 border-2 border-red-100 dark:border-red-900/30 rounded-2xl font-bold text-gray-900 dark:text-white dark:text-white focus:border-red-500 focus:ring-0 transition-all" placeholder="Describa el motivo..."></textarea>
                            </div>

                            <div class="flex items-center gap-3 p-4 bg-amber-50 dark:bg-amber-900/10 rounded-2xl border border-amber-100 dark:border-amber-900/20">
                                <input type="checkbox" v-model="forceWithPayments" id="forceCancel" class="w-5 h-5 rounded-lg border-2 border-amber-200 dark:border-amber-900/30 text-amber-600 focus:ring-0 bg-white dark:bg-slate-900 dark:bg-slate-900 transition-all cursor-pointer">
                                <label for="forceCancel" class="text-[10px] font-black text-amber-800 dark:text-amber-400 uppercase tracking-widest cursor-pointer">Forzar cancelación (Admin)</label>
                            </div>
                        </div>

                        <div class="px-8 py-6 bg-red-50/50 dark:bg-slate-950 border-t border-red-100 dark:border-red-900/30 flex flex-col gap-3">
                            <button @click="confirmarCancelacion" :disabled="loading" class="w-full py-4 bg-red-600 hover:bg-red-700 text-white rounded-2xl font-black uppercase text-[10px] tracking-[0.2em] shadow-xl transition-all flex items-center justify-center gap-3">
                                 {{ loading ? 'Cancelando...' : 'Confirmar Cancelación' }}
                            </button>
                            <button @click="cerrarCancelModal" class="w-full py-3 font-black text-red-400 dark:text-red-500 hover:text-red-800 dark:hover:text-red-300 uppercase text-[10px] tracking-widest transition-colors">Abortar</button>
                        </div>
                    </div>
                </template>
            </DialogModal>

            <!-- Modal de Eliminación -->
            <DialogModal :show="showDeleteModal" @close="cerrarDeleteModal" maxWidth="md">
                <template #content>
                    <div class="bg-white dark:bg-slate-900 dark:bg-slate-950 rounded-3xl shadow-xl w-full overflow-hidden border border-red-100 dark:border-red-900/30 transform transition-all font-sans">
                        <div class="px-8 py-10 flex flex-col items-center text-center">
                            <div class="w-20 h-20 rounded-full bg-red-50 dark:bg-red-900/20 flex items-center justify-center mb-6">
                                <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </div>
                            <h3 class="text-xl font-black text-gray-900 dark:text-white dark:text-white uppercase tracking-widest mb-2">Eliminar Definitivamente</h3>
                            <p class="text-xs font-bold text-gray-400 dark:text-slate-500 uppercase tracking-[0.2em] leading-relaxed">¿Estás seguro de borrar la venta #{{ selectedVentaDelete?.numero_venta || selectedVentaDelete?.id }}? Esta acción es irreversible.</p>
                        </div>

                        <div class="px-8 py-6 bg-gray-50 dark:bg-slate-950 dark:bg-slate-900/50 border-t border-gray-100 dark:border-slate-800 flex flex-col gap-3">
                            <button @click="ejecutarBorrado" :disabled="loading" class="w-full py-4 bg-red-600 text-white rounded-2xl font-black uppercase text-[10px] tracking-[0.2em] shadow-xl transition-all">
                                {{ loading ? 'Borrando...' : 'Sí, Eliminar Registro' }}
                            </button>
                            <button @click="cerrarDeleteModal" class="w-full py-3 font-black text-gray-400 dark:text-slate-500 hover:text-gray-900 dark:text-white dark:hover:text-white uppercase text-[10px] tracking-widest transition-colors">Mantener Registro</button>
                        </div>
                    </div>
                </template>
            </DialogModal>

            <!-- Modal de Enviar Email -->
            <DialogModal :show="showEmailModal" @close="cerrarEmailModal" maxWidth="md">
                <template #content>
                    <div class="bg-white dark:bg-slate-900 dark:bg-slate-950 rounded-3xl shadow-xl w-full overflow-hidden border border-blue-100 dark:border-blue-900/30 transform transition-all font-sans">
                        <div class="px-8 py-10 flex flex-col items-center text-center">
                            <div class="w-20 h-20 rounded-full bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center mb-6">
                                <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                            </div>
                            <h3 class="text-xl font-black text-gray-900 dark:text-white dark:text-white uppercase tracking-widest mb-2">Enviar Documento</h3>
                            <p class="text-xs font-bold text-gray-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-6">Se enviará el PDF de la venta al cliente:</p>
                            
                            <div class="w-full p-4 bg-blue-50/50 dark:bg-blue-900/10 rounded-2xl border border-blue-100 dark:border-blue-900/20 text-left">
                                <p class="text-[9px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-1">Destinatario</p>
                                <p class="text-sm font-black text-gray-900 dark:text-white dark:text-white truncate">{{ selectedVentaEmail?.cliente?.nombre_razon_social }}</p>
                                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 dark:text-slate-400 italic mb-2">{{ selectedVentaEmail?.cliente?.email }}</p>
                            </div>
                        </div>

                        <div class="px-8 py-6 bg-gray-50 dark:bg-slate-950 dark:bg-slate-900/50 border-t border-gray-100 dark:border-slate-800 flex flex-col gap-3">
                            <button @click="confirmarEnviarEmail" :disabled="loading" class="w-full py-4 bg-blue-600 text-white rounded-2xl font-black uppercase text-[10px] tracking-[0.2em] shadow-xl transition-all flex items-center justify-center gap-3">
                                <span v-if="loading" class="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin"></span>
                                {{ loading ? 'Enviando...' : 'Enviar Correo Ahora' }}
                            </button>
                            <button @click="cerrarEmailModal" class="w-full py-3 font-black text-gray-400 dark:text-slate-500 hover:text-gray-900 dark:text-white dark:hover:text-white uppercase text-[10px] tracking-widest transition-colors">Cancelar</button>
                        </div>
                    </div>
                </template>
            </DialogModal>
        </div>
</template>

<style scoped>
  .ventas-index .w-full {
    padding-left: 1rem;
    padding-right: 1rem;
  }

  .ventas-index h1 {
    font-size: 1.5rem;
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




