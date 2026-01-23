<!-- /resources/js/Pages/Pedidos/Index.vue -->
<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { router, Head } from '@inertiajs/vue3'
import axios from 'axios'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

import { generarPDF } from '@/Utils/pdfGenerator'
import { useCompanyColors } from '@/Composables/useCompanyColors'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import PedidosHeader from '@/Components/IndexComponents/PedidosHeader.vue'
import Modal from '@/Components/IndexComponents/Modales.vue'
import ModalPedido from '@/Components/IndexComponents/ModalPedido.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  pedidos: {
    type: Object,
    default: () => ({ data: [] })
  },
  stats: {
    type: Object,
    default: () => ({})
  },
  filterOptions: {
    type: Object,
    default: () => ({})
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
  },
})

const { colors, cssVars, headerGradientStyle, subtleGradientStyle, focusRingStyle } = useCompanyColors()

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

// Variables para filtros
const searchTerm = ref(props.filters?.search ?? '')
const sortBy = ref(props.sorting?.sort_by ? `${props.sorting.sort_by}-${props.sorting.sort_direction}` : 'created_at-desc')
const filtroEstado = ref(props.filters?.estado ?? '')
const filtroCliente = ref(props.filters?.cliente_id ?? '')

// Paginación
const perPage = ref(10)

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
   Estadísticas
========================= */
const estadisticas = computed(() => {
  const stats = props.stats || {}
  const total = stats.total || 0

  return {
    total: total,
    borradores: stats.borradores || 0,
    pendientes: stats.pendientes || 0,
    confirmados: stats.confirmados || 0,
    enviados_venta: stats.enviados_venta || 0,
    cancelados: stats.cancelados || 0,
    con_cotizacion: stats.con_cotizacion || 0,
    sin_cotizacion: stats.sin_cotizacion || 0,
    borradoresPorcentaje: total > 0 ? Math.round(((stats.borradores || 0) / total) * 100) : 0,
    pendientesPorcentaje: total > 0 ? Math.round(((stats.pendientes || 0) / total) * 100) : 0,
    confirmadosPorcentaje: total > 0 ? Math.round(((stats.confirmados || 0) / total) * 100) : 0,
  }
})

/* =========================
   Paginación
========================= */
const paginationData = computed(() => ({
  current_page: props.pagination?.current_page || 1,
  last_page: props.pagination?.last_page || 1,
  per_page: props.pagination?.per_page || 10,
  from: props.pagination?.from || 0,
  to: props.pagination?.to || 0,
  total: props.pagination?.total || 0,
}))

/* =========================
   Validaciones y utilidades
========================= */
function validarPedido(pedido) {
  if (!pedido?.id) {
    throw new Error('ID de pedido no válido')
  }
  return true
}

function validarPedidoParaPDF(doc) {
  if (!doc.id) throw new Error('ID del documento no encontrado')
  if (!doc.cliente?.nombre_razon_social) throw new Error('Datos del cliente no encontrados')
  const productos = doc.productos || doc.items || [];
  if (!Array.isArray(productos) || !productos.length) {
    throw new Error('Lista de productos no válida')
  }
  if (!doc.fecha) throw new Error('Fecha no especificada')
  return true
}

/* =========================
   Acciones CRUD
========================= */
const verDetalles = (pedido) => {
  try {
    validarPedido(pedido)
    fila.value = pedido
    modalMode.value = 'details'
    showModal.value = true
  } catch (error) {
    notyf.error(error.message)
  }
}

const editarPedido = (id) => {
  try {
    const pedidoId = id || fila.value?.id
    if (!pedidoId) throw new Error('ID de pedido no válido')

    router.visit(`/pedidos/${pedidoId}/edit`)
  } catch (error) {
    notyf.error(error.message)
  }
}

const editarFila = (id) => {
  editarPedido(id)
}

const duplicarPedido = (pedido) => {
  try {
    validarPedido(pedido)

    // Usar modal de confirmación personalizado
    fila.value = pedido
    modalMode.value = 'confirm-duplicate'
    showModal.value = true
  } catch (error) {
    notyf.error(error.message)
  }
}

const imprimirPedido = async (pedido) => {
  try {
    const doc = {
      ...pedido,
      fecha: pedido.fecha || pedido.created_at || new Date().toISOString()
    }

    validarPedidoParaPDF(doc)

    loading.value = true
    notyf.success('Generando PDF...')

    await generarPDF('Pedido', doc)
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
    imprimirPedido(fila.value)
  }
}

const confirmarEliminacion = (id) => {
  try {
    if (!id) throw new Error('ID de pedido no válido')

    selectedId.value = id
    modalMode.value = 'confirm'
    showModal.value = true
  } catch (error) {
    notyf.error(error.message)
  }
}

const confirmarCerrarPedido = () => {
  try {
    if (!fila.value?.id) throw new Error('ID de pedido no válido')

    selectedId.value = fila.value.id
    modalMode.value = 'close'
    showModal.value = true
  } catch (error) {
    notyf.error(error.message)
  }
}

// Función para enviar pedido por email (para el modal)
const enviarPedidoPorEmail = async (pedido) => {
  try {
    // Verificar que el cliente tenga email
    if (!pedido.cliente?.email) {
      notyf.error('El cliente no tiene email configurado')
      return
    }

    console.log('=== ENVIANDO PEDIDO POR EMAIL ===')
    console.log('Pedido ID:', pedido.id)
    console.log('Cliente email:', pedido.cliente.email)

    // Configurar modal de confirmación personalizado
    fila.value = {
      ...pedido,
      numero_pedido: pedido.numero_pedido || `P${String(pedido.id).padStart(4, '0')}`,
      email_destino: pedido.cliente.email
    }
    modalMode.value = 'confirm-email'
    showModal.value = true

  } catch (error) {
    console.error('Error en enviarPedidoPorEmail:', error)
    notyf.error('Error inesperado al preparar envío de pedido')
  }
}

// Función para confirmar envío de email
const confirmarEnvioEmail = async () => {
  try {
    const pedido = fila.value
    if (!pedido?.email_destino) {
      notyf.error('Email de destino no válido')
      return
    }

    console.log('✅ Usuario confirmó envío de pedido por email');
    loading.value = true
    cerrarModal()

    // Usar axios para tener control total sobre la respuesta
    const { data } = await axios.post(`/pedidos/${pedido.id}/enviar-email`, {
      email_destino: pedido.email_destino,
    })

    if (data?.success) {
      notyf.success(data.message || 'Pedido enviado por email correctamente')

      // Actualizar estado local del pedido usando los datos del servidor
      const index = props.pedidos.data.findIndex(p => p.id === pedido.id)
      if (index !== -1 && data.pedido) {
        // Note: This would need to be updated in the reactive data
        // For now, just show success
      }
    } else {
      throw new Error(data?.error || 'Error desconocido al enviar email')
    }

  } catch (error) {
    console.error('Error al enviar pedido:', error)
    const errorMessage = error.response?.data?.error || error.response?.data?.message || error.message
    notyf.error('Error al enviar pedido: ' + errorMessage)
  } finally {
    loading.value = false
  }
}

// Función para duplicar pedido (para el modal)
const confirmarDuplicarPedido = async () => {
  try {
    const pedido = fila.value
    if (!pedido?.id) throw new Error('Pedido no válido')

    loading.value = true
    cerrarModal()

    const { data } = await axios.post(`/pedidos/${pedido.id}/duplicate`)

    if (data?.success) {
      notyf.success(data.message || 'Pedido duplicado exitosamente')
      // Reload the page to show the new pedido
      router.reload()
    } else {
      throw new Error(data?.error || 'Error al duplicar el pedido')
    }

  } catch (error) {
    console.error('Error al duplicar pedido:', error)
    notyf.error('Error al duplicar el pedido')
  } finally {
    loading.value = false
  }
}

const eliminarPedido = (id = null) => {
  const targetId = id || selectedId.value
  if (!targetId) return

  loading.value = true

  router.delete(route('pedidos.destroy', targetId), {
    preserveScroll: true,
    onSuccess: () => {
      notyf.success('Pedido eliminado exitosamente')
      showModal.value = false
      selectedId.value = null
    },
    onError: (errors) => {
      console.error('Error al eliminar:', errors)
      notyf.error('Error al eliminar el pedido')
    },
    onFinish: () => {
      loading.value = false
    }
  })
}

const cerrarPedido = async () => {
  try {
    if (!selectedId.value) throw new Error('No se seleccionó ningún pedido')

    loading.value = true

    const { data } = await axios.post(`/pedidos/${selectedId.value}/cancel`)

    if (data?.success) {
      notyf.success(data.message || 'Pedido cerrado exitosamente')
      showModal.value = false
      selectedId.value = null
      router.reload()
    } else {
      throw new Error(data?.error || 'Error al cerrar el pedido')
    }

  } catch (error) {
    console.error('Error al cerrar:', error)
    notyf.error('Error al cerrar el pedido')
  } finally {
    loading.value = false
  }
}

const enviarAVenta = async (pedidoData) => {
  try {
    const doc = pedidoData?.id ? pedidoData : fila.value
    validarPedido(doc)

    loading.value = true
    notyf.success('Enviando pedido a venta...')

    // ✅ NEW: Redirigir a la pantalla de creación de venta con el ID del pedido
    // En lugar de convertir directamente, llevamos al usuario a la pantalla de venta
    // para que pueda seleccionar series y verificar datos.
    router.visit(route('ventas.create', { pedido_id: doc.id }));
    
    // Cerramos el modal
    showModal.value = false;
    notyf.success('Redirigiendo a nueva venta...');

    /* 
    // Lógica anterior (comentada)
    const { data } = await axios.post(`/pedidos/${doc.id}/enviar-a-venta`)

    if (data?.success) {
      notyf.success(data.message || 'Pedido enviado a venta exitosamente')
      showModal.value = false
      router.visit('/ventas')
    } else {
      throw new Error(data?.error || 'No se pudo enviar a venta')
    }
    */

  } catch (error) {
    console.error('Error al enviar a venta:', error)
    notyf.error('Error al enviar a venta')
  } finally {
    loading.value = false
  }
}

const enviarEmailPedido = async (pedido) => {
  try {
    if (!pedido.cliente?.email) {
      notyf.error('El cliente no tiene email configurado')
      return
    }

    loading.value = true
    notyf.success(`Enviando pedido #${pedido.numero_pedido || pedido.id} por email...`)

    const { data } = await axios.post(`/pedidos/${pedido.id}/enviar-email`, {}, {
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })

    if (data?.success) {
      notyf.success(data.message || 'Pedido enviado por email exitosamente')
    } else {
      throw new Error(data?.error || 'No se pudo enviar el email')
    }

  } catch (error) {
    console.error('Error al enviar email:', error)
    notyf.error('Error al enviar pedido por email')
  } finally {
    loading.value = false
  }
}

const crearNuevoPedido = () => {
  router.visit('/pedidos/create')
}

/* =========================
   Filtros y orden
========================= */
const handleSearchChange = (newSearch) => {
  searchTerm.value = newSearch
  router.get(route('pedidos.index'), {
    search: newSearch,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    estado: filtroEstado.value,
    cliente_id: filtroCliente.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const handleEstadoChange = (newEstado) => {
  filtroEstado.value = newEstado
  router.get(route('pedidos.index'), {
    search: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    estado: newEstado,
    cliente_id: filtroCliente.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const handleClienteChange = (newCliente) => {
  filtroCliente.value = newCliente
  router.get(route('pedidos.index'), {
    search: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    estado: filtroEstado.value,
    cliente_id: newCliente,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const handleSortChange = (newSort) => {
  sortBy.value = newSort
  router.get(route('pedidos.index'), {
    search: searchTerm.value,
    sort_by: newSort.split('-')[0],
    sort_direction: newSort.split('-')[1] || 'desc',
    estado: filtroEstado.value,
    cliente_id: filtroCliente.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const handlePerPageChange = (newPerPage) => {
  router.get(route('pedidos.index'), {
    ...props.filters,
    ...props.sorting,
    per_page: newPerPage,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const handlePageChange = (newPage) => {
  router.get(route('pedidos.index'), {
    ...props.filters,
    ...props.sorting,
    page: newPage
  }, { preserveState: true, preserveScroll: true })
}

const hayFiltrosActivos = computed(() => !!searchTerm.value || !!filtroEstado.value || !!filtroCliente.value)

const limpiarFiltros = () => {
  searchTerm.value = ''
  sortBy.value = 'created_at-desc'
  filtroEstado.value = ''
  filtroCliente.value = ''
  router.visit('/pedidos')
  notyf.success('Filtros limpiados correctamente')
}

/* =========================
   Funciones para Modal
========================= */
const modalRef = ref(null)

const focusFirst = () => { try { modalRef.value?.focus() } catch {} }
watch(() => showModal, (v) => { if (v) setTimeout(focusFirst, 0) })

const onKey = (e) => { if (e.key === 'Escape' && showModal.value) onClose() }
onMounted(() => window.addEventListener('keydown', onKey))
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))

const onCancel = () => { showModal.value = false; fila.value = null; selectedId.value = null; }
const onConfirm = () => { eliminarPedido() }
const onClose = () => { showModal.value = false; fila.value = null; selectedId.value = null; }
const cerrarModal = () => { showModal.value = false; fila.value = null; selectedId.value = null; }
const onImprimirFila = () => { if (fila.value) { imprimirPedido(fila.value) } }
const onEditarFila = () => { editarPedido(fila.value?.id) }

/* =========================
   Helpers
========================= */
const formatNumber = (num, tipo = 'number') => {
  if (tipo === 'currency') return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(num);
  return new Intl.NumberFormat('es-ES').format(num);
}

const formatearFecha = (date) => {
  if (!date) return 'Fecha no disponible';
  try {
    const d = new Date(date);
    return d.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });
  } catch {
    return 'Fecha inválida';
  }
}

const formatearHora = (date) => {
  if (!date) return '';
  try {
    const d = new Date(date);
    return d.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
  } catch {
    return '';
  }
}

const formatearMoneda = (num) => {
  const value = parseFloat(num);
  const safe = Number.isFinite(value) ? value : 0;
  return new Intl.NumberFormat('es-MX', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(safe);
}

// Configuración de estados para pedidos
const configEstados = {
  'borrador': {
    label: 'Borrador',
    classes: 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
    color: 'bg-gray-400 dark:bg-gray-50 dark:bg-slate-9500'
  },
  'pendiente': {
    label: 'Pendiente',
    classes: 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300',
    color: 'bg-yellow-400 dark:bg-yellow-500'
  },
  'confirmado': {
    label: 'Confirmado',
    classes: 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300',
    color: 'bg-blue-400 dark:bg-blue-500'
  },
  'enviado_venta': {
    label: 'Enviado a Venta',
    classes: 'bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300',
    color: 'bg-purple-400 dark:bg-purple-500'
  },
  'cancelado': {
    label: 'Cancelado',
    classes: 'bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300',
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

// Items filtrados y ordenados
const items = computed(() => {
  if (!Array.isArray(props.pedidos.data)) {
    return [];
  }

  let filtered = props.pedidos.data.slice();

  // Filtro de búsqueda
  if (searchTerm.value) {
    const term = searchTerm.value.toLowerCase().trim();
    filtered = filtered.filter(doc => {
      const cliente = doc.cliente?.nombre_razon_social?.toLowerCase() || '';
      const numero = String(doc.numero_pedido || doc.id || '').toLowerCase();
      const estado = doc.estado?.toLowerCase() || '';

      return cliente.includes(term) ||
             numero.includes(term) ||
             estado.includes(term);
    });
  }

  // Filtro de estado
  if (filtroEstado.value) {
    filtered = filtered.filter(doc => doc.estado === filtroEstado.value);
  }

  // Filtro de cliente
  if (filtroCliente.value) {
    filtered = filtered.filter(doc => doc.cliente?.id == filtroCliente.value);
  }

  // Ordenamiento
  if (sortBy.value) {
    const [field, direction] = sortBy.value.split('-');

    filtered.sort((a, b) => {
      let aVal, bVal;

      switch (field) {
        case 'fecha':
          aVal = new Date(a.fecha || a.created_at).getTime() || 0;
          bVal = new Date(b.fecha || b.created_at).getTime() || 0;
          break;
        case 'cliente':
          aVal = (a.cliente?.nombre_razon_social || '').toLowerCase();
          bVal = (b.cliente?.nombre_razon_social || '').toLowerCase();
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
  handleSortChange(newOrder);
}

// Iconos
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
  'status-online': 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'
}

const getIconPath = (iconName) => icons[iconName] || icons.document

// Estadísticas con porcentajes
const estadisticasConPorcentaje = computed(() => {
  const total = estadisticas.value.total || 1;

  return {
    borradores: { ...{ label: 'Borradores', icon: 'document', color: 'gray', description: 'Pedidos en borrador' }, porcentaje: Math.round(((estadisticas.value.borradores || 0) / total) * 100) },
    pendientes: { ...{ label: 'Pendientes', icon: 'clock', color: 'yellow', description: 'Pedidos pendientes' }, porcentaje: Math.round(((estadisticas.value.pendientes || 0) / total) * 100) },
    confirmados: { ...{ label: 'Confirmados', icon: 'check-circle', color: 'blue', description: 'Pedidos confirmados' }, porcentaje: Math.round(((estadisticas.value.confirmados || 0) / total) * 100) },
    enviados_venta: { ...{ label: 'Enviados a Venta', icon: 'paper-plane', color: 'purple', description: 'Pedidos enviados a venta' }, porcentaje: Math.round(((estadisticas.value.enviados_venta || 0) / total) * 100) },
    cancelados: { ...{ label: 'Cancelados', icon: 'x-circle', color: 'red', description: 'Pedidos cancelados' }, porcentaje: Math.round(((estadisticas.value.cancelados || 0) / total) * 100) },
  };
})
</script>
<template>
  <Head title="Pedidos" />

  <!-- FontAwesome Icons -->
  <component :is="'style'">
    @import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
  </component>

  <div class="pedidos-index min-h-screen bg-white dark:bg-slate-900 dark:bg-gray-900 transition-colors" :style="cssVars">
    <!-- Contenido principal -->
    <div class="w-full px-6 py-8">
      <!-- Header específico de pedidos -->
      <PedidosHeader
        :total="estadisticas.total"
        :borradores="estadisticas.borradores"
        :pendientes="estadisticas.pendientes"
        :confirmados="estadisticas.confirmados"
        :enviados_venta="estadisticas.enviados_venta"
        :cancelados="estadisticas.cancelados"
        :filter-options="props.filterOptions"
        v-model:search-term="searchTerm"
        v-model:sort-by="sortBy"
        v-model:filtro-estado="filtroEstado"
        v-model:filtro-cliente="filtroCliente"
        @crear-nueva="crearNuevoPedido"
        @search-change="handleSearchChange"
        @filtro-estado-change="handleEstadoChange"
        @filtro-cliente-change="handleClienteChange"
        @sort-change="handleSortChange"
        @limpiar-filtros="limpiarFiltros"
      />

      <!-- Información de paginación -->
      <div class="flex justify-between items-center mb-4 text-sm text-gray-600 dark:text-gray-300 dark:text-gray-400">
        <div>
          Mostrando {{ paginationData.from }} - {{ paginationData.to }} de {{ paginationData.total }} pedidos
        </div>
        <div class="flex items-center space-x-2">
          <span>Elementos por página:</span>
          <select
            :value="paginationData.per_page"
            @change="handlePerPageChange(parseInt($event.target.value))"
            class="border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:border-transparent transition-colors"
            :style="focusRingStyle"
          >
            <option value="10">10</option>
            <option value="15">15</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
      </div>

      <!-- Tabla de pedidos -->
      <div class="mt-6">
        <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden transition-colors">
          <!-- Header -->
          <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800/60 dark:border-gray-700" :style="subtleGradientStyle">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-white tracking-tight">Pedidos</h2>
              <div class="text-sm text-gray-600 dark:text-gray-300 dark:text-gray-400 bg-white dark:bg-slate-900/70 dark:bg-gray-700/70 px-3 py-1 rounded-full border border-gray-200 dark:border-slate-800/50 dark:border-gray-600/50">
                {{ items.length }} de {{ paginationData.total }} pedidos
              </div>
            </div>
          </div>

          <!-- Table -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800/60 dark:divide-gray-700">
              <thead class="bg-white dark:bg-slate-900/60 dark:bg-gray-900/60">
                <tr>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-400 uppercase tracking-wider">Fecha</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-400 uppercase tracking-wider">Cliente</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-400 uppercase tracking-wider">N° Pedido</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-400 uppercase tracking-wider">Total</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                  <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
                </tr>
              </thead>

              <tbody class="bg-white dark:bg-slate-900 dark:bg-gray-800 divide-y divide-gray-200 dark:divide-slate-800/40 dark:divide-gray-700/40">
                <template v-if="items.length > 0">
                  <tr
                    v-for="pedido in items"
                    :key="pedido.id"
                    class="group hover:bg-white dark:bg-slate-900/60 dark:hover:bg-gray-700/60 transition-all duration-150 hover:shadow-sm"
                  >
                    <!-- Fecha -->
                    <td class="px-6 py-4">
                      <div class="flex flex-col space-y-0.5">
                        <div class="text-sm font-medium text-gray-900 dark:text-white dark:text-white">
                          {{ formatearFecha(pedido.fecha || pedido.created_at) }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">
                          {{ formatearHora(pedido.fecha || pedido.created_at) }}
                        </div>
                      </div>
                    </td>

                    <!-- Cliente -->
                    <td class="px-6 py-4">
                      <div class="flex flex-col space-y-0.5">
                        <div class="text-sm font-medium text-gray-900 dark:text-white dark:text-white group-hover:text-gray-800 dark:text-gray-100 dark:group-hover:text-gray-100">
                          {{ pedido.cliente?.nombre_razon_social || 'Sin cliente' }}
                        </div>
                        <div v-if="pedido.cliente?.email" class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 truncate max-w-48">
                          {{ pedido.cliente?.email }}
                        </div>
                      </div>
                    </td>

                    <!-- N° Pedido -->
                    <td class="px-6 py-4">
                      <div class="text-sm font-mono font-medium text-gray-700 dark:text-gray-300 bg-gray-100/60 dark:bg-gray-700/60 px-2 py-1 rounded-md inline-block">
                        {{ pedido.numero_pedido || 'N/A' }}
                      </div>
                    </td>

                    <!-- Total -->
                    <td class="px-6 py-4">
                      <div class="text-sm font-semibold text-gray-900 dark:text-white dark:text-white">
                        ${{ formatearMoneda(pedido.total || 0) }}
                      </div>
                    </td>

                    <!-- Estado -->
                    <td class="px-6 py-4">
                      <span
                        :class="obtenerClasesEstado(pedido.estado)"
                        class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium transition-all duration-150 hover:shadow-sm"
                      >
                        <span
                          class="w-2 h-2 rounded-full mr-2 transition-all duration-150"
                          :class="obtenerColorPuntoEstado(pedido.estado)"
                        ></span>
                        {{ obtenerLabelEstado(pedido.estado) }}
                      </span>
                    </td>

                    <!-- Acciones -->
                    <td class="px-6 py-4">
                      <div class="flex items-center justify-end space-x-2">
                        <!-- Ver detalles -->
                        <button
                          @click="verDetalles(pedido)"
                          class="group/btn relative inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 hover:text-blue-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-1"
                          :style="focusRingStyle"
                          title="Ver detalles"
                        >
                          <font-awesome-icon icon="eye" class="w-3.5 h-3.5 transition-transform duration-200 group-hover/btn:scale-110" />
                        </button>

                        <!-- Editar -->
                        <button
                          v-if="['borrador', 'pendiente'].includes(pedido.estado)"
                          @click="editarPedido(pedido.id)"
                          class="group/btn relative inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 hover:text-amber-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-1"
                          :style="focusRingStyle"
                          title="Editar pedido"
                        >
                          <font-awesome-icon icon="edit" class="w-3.5 h-3.5 transition-transform duration-200 group-hover/btn:scale-110" />
                        </button>

                        <!-- Enviar por Email -->
                        <button
                          v-if="pedido.estado !== 'cancelado' && pedido.cliente?.email"
                          @click="enviarPedidoPorEmail(pedido)"
                          class="group/btn relative inline-flex items-center justify-center w-8 h-8 rounded-lg bg-green-50 text-green-600 hover:bg-green-100 hover:text-green-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-1"
                          :style="focusRingStyle"
                          :title="pedido.email_enviado ? 'Reenviar por Email' : 'Enviar por Email'"
                        >
                          <font-awesome-icon
                            :icon="pedido.email_enviado ? 'envelope-open' : 'envelope'"
                            class="w-3.5 h-3.5 transition-transform duration-200 group-hover/btn:scale-110"
                          />
                        </button>

                        <!-- Enviar a Venta -->
                        <button
                          v-if="['confirmado', 'en_preparacion', 'listo_entrega', 'entregado', 'borrador', 'pendiente'].includes(pedido.estado)"
                          @click="enviarAVenta(pedido)"
                          class="group/btn relative inline-flex items-center justify-center w-8 h-8 rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-100 hover:text-purple-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-1"
                          :style="focusRingStyle"
                          title="Enviar a venta"
                        >
                          <font-awesome-icon icon="shopping-cart" class="w-3.5 h-3.5 transition-transform duration-200 group-hover/btn:scale-110" />
                        </button>

                        <!-- Duplicar -->
                        <button
                          @click="duplicarPedido(pedido)"
                          class="group/btn relative inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 hover:text-blue-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-1"
                          :style="focusRingStyle"
                          title="Duplicar pedido"
                        >
                          <font-awesome-icon icon="copy" class="w-3.5 h-3.5 transition-transform duration-200 group-hover/btn:scale-110" />
                        </button>

                        <!-- Cancelar -->
                        <button
                          v-if="pedido.estado !== 'cancelado'"
                          @click="confirmarEliminacion(pedido.id)"
                          class="group/btn relative inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-1"
                          :style="focusRingStyle"
                          title="Cancelar pedido"
                        >
                          <font-awesome-icon icon="times-circle" class="w-3.5 h-3.5 transition-transform duration-200 group-hover/btn:scale-110" />
                        </button>
                      </div>
                    </td>
                  </tr>
                </template>

                <!-- Empty State -->
                <tr v-else>
                  <td :colspan="6" class="px-6 py-16 text-center">
                    <div class="flex flex-col items-center space-y-4">
                      <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400 dark:text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                      </div>
                      <div class="space-y-1">
                        <p class="text-gray-700 dark:text-gray-300 font-medium">No hay pedidos</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">Los pedidos aparecerán aquí cuando se creen</p>
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
      <div v-if="paginationData.last_page > 1" class="flex justify-center items-center space-x-2 mt-6">
        <button
          @click="handlePageChange(paginationData.current_page - 1)"
          :disabled="paginationData.current_page === 1"
          class="px-3 py-2 text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 bg-white dark:bg-slate-900 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-white dark:bg-slate-900 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:border-transparent transition-colors"
          :style="focusRingStyle"
        >
          Anterior
        </button>

        <div class="flex space-x-1">
          <button
            v-for="page in [paginationData.current_page - 1, paginationData.current_page, paginationData.current_page + 1].filter(p => p > 0 && p <= paginationData.last_page)"
            :key="page"
            @click="handlePageChange(page)"
            :class="[
              'px-3 py-2 text-sm font-medium border border-gray-300 dark:border-gray-600 rounded-md transition-colors',
              page === paginationData.current_page
                ? 'text-white'
                : 'text-gray-700 dark:text-gray-300 bg-white dark:bg-slate-900 dark:bg-gray-800 hover:bg-white dark:bg-slate-900 dark:hover:bg-gray-700'
            ]"
            :style="page === paginationData.current_page ? headerGradientStyle : null"
          >
            {{ page }}
          </button>
        </div>

        <button
          @click="handlePageChange(paginationData.current_page + 1)"
          :disabled="paginationData.current_page === paginationData.last_page"
          class="px-3 py-2 text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 bg-white dark:bg-slate-900 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-white dark:bg-slate-900 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:border-transparent transition-colors"
          :style="focusRingStyle"
        >
          Siguiente
        </button>
      </div>
    </div>

    <!-- Modal de detalles -->
    <ModalPedido
      v-if="modalMode === 'details'"
      :show="showModal"
      :selected="fila || {}"
      :auditoria="auditoriaForModal"
      @close="cerrarModal"
      @editar="editarFila"
      @duplicar="duplicarPedido"
      @enviar-a-venta="enviarAVenta"
      @cancelar="eliminarPedido"
      @eliminar="eliminarPedido"
      @imprimir="imprimirFila"
    />

    <!-- Modal de confirmación -->
    <Modal
      v-else
      :show="showModal"
      :mode="modalMode"
      tipo="pedidos"
      :selected="fila || {}"
      :auditoria="auditoriaForModal"
      @close="cerrarModal"
      @confirm-delete="eliminarPedido"
      @confirm-duplicate="confirmarDuplicarPedido"
      @confirm-email="confirmarEnvioEmail"
      @cerrar-pedido="cerrarPedido"
      @imprimir="imprimirFila"
      @editar="editarFila"
      @enviar-pedido="enviarAVenta"
      @enviar-a-venta="enviarAVenta"
    />

    <!-- Loading overlay -->
    <div v-if="loading" class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50 backdrop-blur-sm">
      <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 p-6 rounded-lg shadow-lg">
        <div class="flex items-center space-x-3">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2" :style="{ borderBottomColor: colors.principal }"></div>
          <span class="text-gray-700 dark:text-gray-200">Procesando...</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.pedidos-index {
  min-height: 100vh;
}

.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
  transform: scale(0.97);
}
.modal-enter-to,
.modal-leave-from {
  opacity: 1;
  transform: scale(1);
}
</style>


