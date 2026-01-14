<!-- /resources/js/Pages/Cotizaciones/Index.vue -->
<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { router, Head } from '@inertiajs/vue3'
import axios from 'axios'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

import { generarPDF } from '@/Utils/pdfGenerator'
import AppLayout from '@/Layouts/AppLayout.vue'
import CotizacionesHeader from '@/Components/IndexComponents/CotizacionesHeader.vue'
import CotizacionesTable from '@/Components/IndexComponents/CotizacionesTable.vue'
import Modal from '@/Components/IndexComponents/Modales.vue'
import ModalCotizacion from '@/Components/IndexComponents/ModalCotizacion.vue'
import Pagination from '@/Components/Pagination.vue'
import { useCompanyColors } from '@/Composables/useCompanyColors'

defineOptions({ layout: AppLayout })

// Colores de empresa
const { colors, cssVars } = useCompanyColors()

const props = defineProps({
  cotizaciones: {
    type: Array,
    default: () => []
  },
  pagination: {
    type: Object,
    default: () => ({ current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 })
  },
  filters: {
    type: Object,
    default: () => ({ search: '', estado: '', per_page: 10 })
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

// Estado local para manipulación optimista
const cotizacionesOriginales = ref([])

watch(() => props.cotizaciones, (newVal) => {
  cotizacionesOriginales.value = newVal ? [...newVal] : []
}, { immediate: true })

const abrirDetalles = (row) => {
  fila.value = row || null
  modalMode.value = 'details'
  showModal.value = true
}

const cerrarModal = () => {
  showModal.value = false
  fila.value = null
  selectedId.value = null
}

/* =========================
   Filtros, orden y datos (Paginación del servidor)
========================= */
const searchTerm = ref(props.filters?.search || '')
const sortBy = ref('fecha-desc')
const filtroEstado = ref(props.filters?.estado || '')
const perPage = ref(props.filters?.per_page || 10)
const currentPage = ref(props.pagination?.current_page || 1)
const isLoadingData = ref(false)

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
    Filtrado y ordenamiento (ordenamiento local, filtros en servidor)
 ========================= */
const cotizacionesFiltradasYOrdenadas = computed(() => {
  let result = [...cotizacionesOriginales.value]

  // Aplicar ordenamiento (local, ya que el servidor no ordena por cliente)
  if (sortBy.value) {
    const [field, order] = sortBy.value.split('-')
    const isDesc = order === 'desc'

    result.sort((a, b) => {
      let valueA, valueB

      switch (field) {
        case 'fecha':
          valueA = new Date(a.fecha || a.created_at || 0).getTime()
          valueB = new Date(b.fecha || b.created_at || 0).getTime()
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

/* =========================
    Paginación del servidor
========================= */

// Función de debounce para evitar muchas peticiones
let searchTimeout = null
const fetchData = (params = {}) => {
  isLoadingData.value = true
  
  const queryParams = {
    search: searchTerm.value,
    estado: filtroEstado.value,
    per_page: perPage.value,
    page: params.page || currentPage.value,
  }
  
  router.get(route('cotizaciones.index'), queryParams, {
    preserveState: true,
    preserveScroll: true,
    only: ['cotizaciones', 'pagination', 'filters'],
    onFinish: () => {
      isLoadingData.value = false
    }
  })
}

// Función para formatear el número de cotización
const formatearNumeroCotizacion = (numero) => {
  if (!numero) return numero
  const match = numero.match(/COT-(\d{4}-)?(\d{8}-)?(\d+)$/)
  if (match) {
    const numeroFinal = match[3]
    return `COT-${numeroFinal.padStart(5, '0')}`
  }
  return numero
}

// Documentos para mostrar (ya paginados desde servidor)
const documentosCotizaciones = computed(() => {
  return cotizacionesFiltradasYOrdenadas.value.map(cotizacion => ({
    ...cotizacion,
    numero_cotizacion_display: formatearNumeroCotizacion(cotizacion.numero_cotizacion)
  }))
})

// Datos de paginación del servidor
const paginationData = computed(() => ({
  current_page: props.pagination?.current_page || 1,
  last_page: props.pagination?.last_page || 1,
  per_page: props.pagination?.per_page || 10,
  from: props.pagination?.from || 0,
  to: props.pagination?.to || 0,
  total: props.pagination?.total || 0,
  prev_page_url: props.pagination?.current_page > 1 ? '#' : null,
  next_page_url: props.pagination?.current_page < props.pagination?.last_page ? '#' : null,
  links: []
}))

// Watch para búsqueda con debounce
watch(searchTerm, (newVal) => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    currentPage.value = 1
    fetchData({ page: 1 })
  }, 400)
})

// Watch para filtro de estado (sin debounce)
watch(filtroEstado, () => {
  currentPage.value = 1
  fetchData({ page: 1 })
})

// Manejo de paginación
const handlePerPageChange = (newPerPage) => {
  perPage.value = newPerPage
  currentPage.value = 1
  fetchData({ page: 1 })
}

const handlePageChange = (newPage) => {
  currentPage.value = newPage
  fetchData({ page: newPage })
}

// Estadísticas calculadas - Solo estados relevantes (basadas en página actual)
const estadisticas = computed(() => {
  const cotizaciones = props.cotizaciones || []

  return {
    total: props.pagination?.total || cotizaciones.length,
    pendientes: cotizaciones.filter(c => c.estado === 'pendiente').length,
    enviado_pedido: cotizaciones.filter(c => c.estado === 'enviado_pedido').length,
    cancelado: cotizaciones.filter(c => c.estado === 'cancelado').length,
  }
})

/* =========================
    Funciones de manejo - Fáciles de modificar
 ========================= */
const handleLimpiarFiltros = () => {
  searchTerm.value = ''
  sortBy.value = 'fecha-desc'
  filtroEstado.value = ''
  perPage.value = 10
  currentPage.value = 1
  notyf.success('Filtros limpiados correctamente')
}

const updateSort = (newSort) => {
  sortBy.value = newSort || 'fecha-desc'
  currentPage.value = 1
}

/* =========================
   Validaciones y utilidades
========================= */
function puedeEnviarAPedido(cotizacion) {
  if (!cotizacion) return false
  return cotizacion.estado === 'aprobado' || cotizacion.estado === 'aprobada'
}

function validarCotizacion(cotizacion) {
  if (!cotizacion?.id) {
    throw new Error('ID de cotización no válido')
  }
  return true
}

function validarCotizacionBasica(cotizacion) {
  if (!cotizacion?.id) {
    throw new Error('ID de cotización no válido')
  }
  if (!cotizacion.cliente?.nombre) {
    throw new Error('Datos del cliente no encontrados')
  }
  if (!Array.isArray(cotizacion.productos) || !cotizacion.productos.length) {
    throw new Error('Lista de productos no válida')
  }
  if (!cotizacion.fecha && !cotizacion.created_at) {
    throw new Error('Fecha no especificada')
  }
  return true
}

function validarCotizacionParaPDF(doc) {
  if (!doc.id) throw new Error('ID del documento no encontrado')
  if (!doc.cliente?.nombre) throw new Error('Datos del cliente no encontrados')
  if (!Array.isArray(doc.productos) || !doc.productos.length) {
    throw new Error('Lista de productos no válida')
  }
  if (!doc.fecha) throw new Error('Fecha no especificada')
  return true
}

/* =========================
   Acciones CRUD
========================= */
const verDetalles = (cotizacion) => {
  try {
    validarCotizacion(cotizacion)
    abrirDetalles(cotizacion)
  } catch (error) {
    notyf.error(error.message)
  }
}

const editarCotizacion = (id) => {
  try {
    const cotizacionId = id || fila.value?.id
    if (!cotizacionId) throw new Error('ID de cotización no válido')

    router.visit(`/cotizaciones/${cotizacionId}/edit`)
  } catch (error) {
    notyf.error(error.message)
  }
}

const editarFila = (id) => {
  editarCotizacion(id)
}

const duplicarCotizacion = async (cotizacion) => {
  try {
    validarCotizacion(cotizacion)

    // Usar modal de confirmación personalizado
    fila.value = cotizacion
    modalMode.value = 'confirm-duplicate'
    showModal.value = true
  } catch (error) {
    notyf.error(error.message)
  }
}

const confirmarDuplicarCotizacion = async () => {
  try {
    const cotizacion = fila.value
    if (!cotizacion?.id) throw new Error('Cotización no válida')

    loading.value = true
    cerrarModal()

    router.post(`/cotizaciones/${cotizacion.id}/duplicate`, {}, {
      onStart: () => {
        notyf.success('Duplicando cotización...')
      },
      onSuccess: () => {
        notyf.success('Cotización duplicada exitosamente')
      },
      onError: (errors) => {
        console.error('Error al duplicar:', errors)
        notyf.error('Error al duplicar la cotización')
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

const imprimirCotizacion = async (cotizacion) => {
  try {
    console.log('=== IMPRIMIENDO COTIZACIÓN ===')
    console.log('Cotización recibida:', cotizacion)
    console.log('ID:', cotizacion?.id)
    console.log('Número:', cotizacion?.numero_cotizacion)

    validarCotizacion(cotizacion)

    if (!cotizacion?.id) {
      throw new Error('ID de cotización no válido')
    }

    loading.value = true
    notyf.success('Generando PDF...')

    const url = `/cotizaciones/${cotizacion.id}/pdf`
    const filename = `cotizacion-${cotizacion.numero_cotizacion || cotizacion.id}.pdf`

    console.log('URL del PDF:', url)
    console.log('Nombre del archivo:', filename)

    // Método 1: Intentar con window.open (más confiable)
    try {
      const newWindow = window.open(url, '_blank')
      if (!newWindow) {
        throw new Error('El navegador bloqueó la ventana emergente')
      }
      notyf.success('PDF generado correctamente')
    } catch (windowError) {
      console.warn('Error con window.open, intentando método alternativo:', windowError)

      // Método 2: Usar enlace programático como fallback
      const link = document.createElement('a')
      link.href = url
      link.download = filename
      link.target = '_blank'
      link.style.display = 'none'

      document.body.appendChild(link)
      link.click()

      setTimeout(() => {
        document.body.removeChild(link)
        notyf.success('PDF generado correctamente')
      }, 100)
    }

  } catch (error) {
    console.error('Error al generar PDF:', error)
    notyf.error(`Error al generar el PDF: ${error.message}`)

    // Si hay un error de red, mostrar más detalles
    if (error.response) {
      console.error('Error de respuesta:', error.response)
      notyf.error(`Error del servidor: ${error.response.status} - ${error.response.statusText}`)
    }
  } finally {
    loading.value = false
  }
}

const imprimirFila = () => {
  if (fila.value) {
    imprimirCotizacion(fila.value)
  }
}

const confirmarEliminacion = (id) => {
  try {
    if (!id) throw new Error('ID de cotización no válido')

    selectedId.value = id
    fila.value = cotizacionesOriginales.value.find(c => c.id === id) || null
    modalMode.value = 'confirm'
    showModal.value = true
  } catch (error) {
    notyf.error(error.message)
  }
}

const eliminarCotizacion = async () => {
  try {
    if (!selectedId.value) throw new Error('No se seleccionó ninguna cotización')

    loading.value = true

    const cotizacion = cotizacionesOriginales.value.find(c => c.id === selectedId.value)
    const jaCancelada = cotizacion && (cotizacion.estado === 'cancelado' || cotizacion.estado === 'Cancelado')

    loading.value = true

    if (jaCancelada) {
      // Si ya está cancelada, eliminar definitivamente
      router.delete(`/cotizaciones/${selectedId.value}`, {
        onStart: () => {
          notyf.success('Eliminando cotización permanentemente...')
        },
        onSuccess: () => {
          notyf.success('Cotización eliminada exitosamente')
          // Eliminar de la lista local
          cotizacionesOriginales.value = cotizacionesOriginales.value.filter(c => c.id !== selectedId.value)
          cerrarModal()
        },
        onError: (errors) => {
          console.error('Error al eliminar:', errors)
          notyf.error('Error al eliminar la cotización')
        },
        onFinish: () => {
          loading.value = false
        }
      })
    } else {
      // Si no está cancelada, proceder con la cancelación normal
      router.post(`/cotizaciones/${selectedId.value}/cancel`, {}, {
        onStart: () => {
          notyf.success('Cancelando cotización...')
        },
        onSuccess: (response) => {
          notyf.success('Cotización cancelada exitosamente')

          // Actualizar datos locales - marcar como cancelada en lugar de eliminar
          const index = cotizacionesOriginales.value.findIndex(c => c.id === selectedId.value)
          if (index !== -1) {
            cotizacionesOriginales.value[index] = {
              ...cotizacionesOriginales.value[index],
              estado: 'cancelado',
              eliminado_por: response?.data?.eliminado_por || 'Usuario actual',
              deleted_at: new Date().toISOString()
            }
          }

          cerrarModal()
        },
        onError: (errors) => {
          console.error('Error al cancelar:', errors)
          notyf.error('Error al cancelar la cotización')
        },
        onFinish: () => {
          loading.value = false
        }
      })
    }
  } catch (error) {
    notyf.error(error.message)
    loading.value = false
  }
}

// Pon este ref junto a tus otros estados
const isSendingPedido = ref(false)

/**
 * Enviar una cotización a Pedido
 * @param {Object} cotizacionData - La cotización (opcional; si no viene, toma `fila.value`)
 * @param {Object} options
 * @param {'index'|'show'|'edit'|null} options.redirectTo - A dónde navegar tras crear el pedido.
 *    'show' o 'edit' requieren que el backend devuelva `pedido_id`. Si es null, no redirige.
 *    Default: 'show' si hay `pedido_id`, de lo contrario 'index'.
 */
const enviarAPedido = async (cotizacionData, { redirectTo = 'index' } = {}) => {
  try {
    const docRaw = cotizacionData?.id ? cotizacionData : fila.value
    validarCotizacionBasica(docRaw)

    const doc = { ...docRaw, fecha: docRaw.fecha || docRaw.created_at || new Date().toISOString() }

    loading.value = true
    notyf.success('Enviando cotización a pedido...')

    const { data } = await axios.post(`/cotizaciones/${doc.id}/enviar-pedido`, {
      forzarReenvio: !!cotizacionData?.forzarReenvio
    })

    if (!data?.success) throw new Error(data?.error || 'No se pudo enviar a pedido')

    // Actualiza estado local
    const i = cotizacionesOriginales.value.findIndex(c => c.id === doc.id)
    if (i !== -1) cotizacionesOriginales.value[i] = { ...cotizacionesOriginales.value[i], estado: 'enviado_pedido' }

    cerrarModal()
    notyf.success(data.message || 'Cotización enviada a pedido exitosamente')

    // 🔁 Ir siempre al index de pedidos (usa el helper de rutas si lo tienes)
    router.visit(route ? route('pedidos.index') : '/pedidos')

  } catch (err) {
    console.error(err)
    notyf.error(err.response?.data?.error || err.response?.data?.message || err.message || 'Error al enviar a pedido')
  } finally {
    loading.value = false
  }
}




const enviarAVenta = () => {
  notyf.open({ type: 'warning', message: 'Esta acción no está disponible desde Cotizaciones.' })
}

// Función para enviar cotización por email
const enviarCotizacionPorEmail = async (cotizacion) => {
  try {
    // Verificar que el cliente tenga email
    if (!cotizacion.cliente?.email) {
      notyf.error('El cliente no tiene email configurado')
      return
    }

    console.log('=== ENVIANDO COTIZACIÓN POR EMAIL ===')
    console.log('Cotización ID:', cotizacion.id)
    console.log('Cliente email:', cotizacion.cliente.email)

    // Configurar modal de confirmación personalizado
    fila.value = {
      ...cotizacion,
      numero_cotizacion: cotizacion.numero_cotizacion || `C${String(cotizacion.id).padStart(3, '0')}`,
      email_destino: cotizacion.cliente.email
    }
    modalMode.value = 'confirm-email'
    showModal.value = true

  } catch (error) {
    console.error('Error en enviarCotizacionPorEmail:', error)
    notyf.error('Error inesperado al preparar envío de cotización')
  }
}

// Función para confirmar envío de email
const confirmarEnvioEmail = async () => {
  try {
    const cotizacion = fila.value
    if (!cotizacion?.email_destino) {
      notyf.error('Email de destino no válido')
      return
    }

    console.log('✅ Usuario confirmó envío de cotización por email');
    loading.value = true
    cerrarModal()

    // Usar axios para tener control total sobre la respuesta
    const { data } = await axios.post(`/cotizaciones/${cotizacion.id}/enviar-email`, {
      email_destino: cotizacion.email_destino,
    })

    if (data?.success) {
      notyf.success(data.message || 'Cotización enviada por email correctamente')

      // Actualizar estado local de la cotización usando los datos del servidor
      const index = cotizacionesOriginales.value.findIndex(c => c.id === cotizacion.id)
      if (index !== -1 && data.cotizacion) {
        cotizacionesOriginales.value[index] = {
          ...cotizacionesOriginales.value[index],
          email_enviado: data.cotizacion.email_enviado,
          email_enviado_fecha: data.cotizacion.email_enviado_fecha,
          estado: data.cotizacion.estado
        }
      }
    } else {
      throw new Error(data?.error || 'Error desconocido al enviar email')
    }

  } catch (error) {
    console.error('Error al enviar cotización:', error)
    const errorMessage = error.response?.data?.error || error.response?.data?.message || error.message
    notyf.error('Error al enviar cotización: ' + errorMessage)
  } finally {
    loading.value = false
  }
}

const crearNuevaCotizacion = () => {
  router.visit('/cotizaciones/create')
}
</script>

<template>
  <Head title="Cotizaciones" />

  <div :style="cssVars" class="cotizaciones-index min-h-screen bg-gray-50">
    <!-- Contenido principal -->
    <div class="max-w-8xl mx-auto px-6 py-8">
      <!-- Header específico de cotizaciones -->
      <CotizacionesHeader
        :total="estadisticas.total"
        :pendientes="estadisticas.pendientes"
        :enviado_pedido="estadisticas.enviado_pedido"
        :cancelado="estadisticas.cancelado"
        v-model:search-term="searchTerm"
        v-model:sort-by="sortBy"
        v-model:filtro-estado="filtroEstado"
        @crear-nueva="crearNuevaCotizacion"
        @search-change="updateSort"
        @filtro-estado-change="updateSort"
        @sort-change="updateSort"
        @limpiar-filtros="handleLimpiarFiltros"
      />

      <!-- Tabla específica de cotizaciones -->
      <div class="mt-6">
        <CotizacionesTable
          :documentos="documentosCotizaciones"
          :search-term="searchTerm"
          :sort-by="sortBy"
          @ver-detalles="verDetalles"
          @editar="editarCotizacion"
          @eliminar="confirmarEliminacion"
          @imprimir="imprimirCotizacion"
          @enviar-pedido="enviarAPedido"
          @enviar-email="enviarCotizacionPorEmail"
          @sort="updateSort"
        />

        <!-- Componente de paginación -->
        <Pagination
          :pagination-data="paginationData"
          @per-page-change="handlePerPageChange"
          @page-change="handlePageChange"
        />
      </div>

    </div>

    <!-- Modal de detalles -->
    <ModalCotizacion
      v-if="modalMode === 'details'"
      :show="showModal"
      :selected="fila || {}"
      :auditoria="auditoriaForModal"
      @close="cerrarModal"
      @editar="editarFila"
      @duplicar="confirmarDuplicarCotizacion"
      @enviar-a-pedido="enviarAPedido"
      @cancelar="eliminarCotizacion"
      @eliminar="eliminarCotizacion"
      @imprimir="imprimirFila"
    />

    <!-- Modal de confirmación -->
    <Modal
      v-else
      :show="showModal"
      :mode="modalMode"
      tipo="cotizaciones"
      :selected="fila || {}"
      :auditoria="auditoriaForModal"
      @close="cerrarModal"
      @confirm-delete="eliminarCotizacion"
      @confirm-duplicate="confirmarDuplicarCotizacion"
      @confirm-email="confirmarEnvioEmail"
      @imprimir="imprimirFila"
      @editar="editarFila"
      @enviar-pedido="enviarAPedido"
      @enviar-a-venta="enviarAVenta"
    />

    <!-- Loading overlay -->
    <div v-if="loading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded-xl shadow-lg">
        <div class="flex items-center space-x-3">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2" :style="{ borderColor: colors.principal }"></div>
          <span class="text-gray-700">Procesando...</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import "tailwindcss" reference;
.cotizaciones-index {
  min-height: 100vh;
  background-color: #f9fafb;
}

@media (max-width: 640px) {
  .cotizaciones-index .max-w-8xl {
    padding-left: 1rem;
    padding-right: 1rem;
  }
  .cotizaciones-index h1 {
    font-size: 1.5rem;
  }
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.cotizaciones-index > * {
  animation: fadeIn 0.3s ease-out;
}

/* Estilos adicionales para la paginación */
.pagination-info {
  font-size: 0.875rem;
  color: #4b5563;
}

.pagination-controls button:focus {
  outline: none;
  /* Approximate Tailwind's ring-2 + ring-blue-500 + ring-offset-2 */
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.18);
}

.loading-overlay {
  backdrop-filter: blur(2px);
}
</style>




