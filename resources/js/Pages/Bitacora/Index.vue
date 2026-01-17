
<!-- /resources/js/Pages/Bitacora/IndexNew.vue -->
<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

import BitacoraHeader from '@/Components/IndexComponents/BitacoraHeader.vue'

defineOptions({ layout: AppLayout })

// Notificaciones
const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false },
    { type: 'warning', background: '#f59e0b', icon: false }
  ]
})

const page = usePage()
onMounted(() => {
  const flash = page.props.flash
  if (flash?.success) notyf.success(flash.success)
  if (flash?.error) notyf.error(flash.error)
})

// Props
const props = defineProps({
  actividades: { type: [Object, Array], required: true },
  stats: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  sorting: { type: Object, default: () => ({ sort_by: 'fecha', sort_direction: 'desc' }) },
  usuarios: { type: Array, default: () => [] },
  clientes: { type: Array, default: () => [] },
  tipos: { type: Array, default: () => [] },
  estados: { type: Array, default: () => [] },
})

// Estado UI
const showModal = ref(false)
const modalMode = ref('details')
const selectedActividad = ref(null)
const selectedId = ref(null)

// Filtros/ordenamiento
const searchTerm = ref(props.filters?.q ?? '')
const sortBy = ref('fecha-desc')
const filtroEstado = ref(props.filters?.estado ?? '')
const filtroUsuario = ref('')
const filtroAccion = ref('')
const filtroFecha = ref('')

// Paginación del lado del cliente
const currentPage = ref(1)
const perPage = ref(15)

// Estadísticas adicionales para el header moderno
const estadisticasHoy = computed(() => {
  // Contar actividades del día de hoy
  if (actividadesData.value && actividadesData.value.length > 0) {
    const hoy = new Date().toDateString()
    return actividadesData.value.filter(actividad => {
      const fechaActividad = new Date(actividad.fecha).toDateString()
      return fechaActividad === hoy
    }).length
  }
  return 0
})

const estadisticasEstaSemana = computed(() => {
  // Contar actividades de la semana actual
  if (actividadesData.value && actividadesData.value.length > 0) {
    const hoy = new Date()
    const inicioSemana = new Date(hoy.setDate(hoy.getDate() - hoy.getDay()))
    const finSemana = new Date(hoy.setDate(hoy.getDate() - hoy.getDay() + 6))

    return actividadesData.value.filter(actividad => {
      const fechaActividad = new Date(actividad.fecha)
      return fechaActividad >= inicioSemana && fechaActividad <= finSemana
    }).length
  }
  return 0
})

const estadisticasEsteMes = computed(() => {
  // Contar actividades del mes actual
  if (actividadesData.value && actividadesData.value.length > 0) {
    const hoy = new Date()
    const inicioMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1)
    const finMes = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0)

    return actividadesData.value.filter(actividad => {
      const fechaActividad = new Date(actividad.fecha)
      return fechaActividad >= inicioMes && fechaActividad <= finMes
    }).length
  }
  return 0
})

const usuariosActivos = computed(() => {
  // Contar usuarios únicos que han creado actividades
  if (actividadesData.value && actividadesData.value.length > 0) {
    const usuariosUnicos = new Set()
    actividadesData.value.forEach(actividad => {
      if (actividad.usuario_id) {
        usuariosUnicos.add(actividad.usuario_id)
      }
    })
    return usuariosUnicos.size
  }
  return 0
})

// Funciones para manejar filtros adicionales
const handleUsuarioChange = (usuarioId) => {
  filtroUsuario.value = usuarioId
  router.get(route('bitacora.index'), {
    q: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    estado: filtroEstado.value,
    usuario_id: usuarioId,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const handleAccionChange = (accion) => {
  filtroAccion.value = accion
  router.get(route('bitacora.index'), {
    q: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    estado: filtroEstado.value,
    tipo: accion,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const handleFechaChange = (fecha) => {
  filtroFecha.value = fecha
  router.get(route('bitacora.index'), {
    q: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    estado: filtroEstado.value,
    fecha: fecha,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

// Datos
const actividadesPaginator = computed(() => props.actividades)
const actividadesData = computed(() => actividadesPaginator.value?.data || [])

// Estadísticas
const estadisticas = computed(() => ({
  total: props.stats?.total ?? 0,
  pendientes: props.stats?.pendientes ?? 0,
  en_proceso: props.stats?.en_proceso ?? 0,
  completados: props.stats?.completados ?? 0,
  cancelados: props.stats?.cancelados ?? 0,
  costo_total_mes: props.stats?.costo_total_mes ?? 0,
  actividades_mes: props.stats?.actividades_mes ?? 0,
  pendientesPorcentaje: props.stats?.pendientes > 0 ? Math.round((props.stats.pendientes / props.stats.total) * 100) : 0,
  enProcesoPorcentaje: props.stats?.en_proceso > 0 ? Math.round((props.stats.en_proceso / props.stats.total) * 100) : 0,
  completadosPorcentaje: props.stats?.completados > 0 ? Math.round((props.stats.completados / props.stats.total) * 100) : 0,
  canceladosPorcentaje: props.stats?.cancelados > 0 ? Math.round((props.stats.cancelados / props.stats.total) * 100) : 0
}))

// Transformación de datos
const actividadesDocumentos = computed(() => {
  return actividadesData.value.map(a => ({
    id: a.id,
    titulo: a.titulo || 'Sin título',
    subtitulo: a.descripcion ? a.descripcion.substring(0, 50) + (a.descripcion.length > 50 ? '...' : '') : 'Sin descripción',
    estado: a.estado || 'pendiente',
    extra: `Cliente: ${a.cliente?.nombre_razon_social || 'N/A'} | Usuario: ${a.usuario?.name || 'N/A'} | Tipo: ${a.tipo || 'N/A'}`,
    fecha: a.fecha,
    raw: a
  }))
})

// Handler para limpiar filtros
function handleLimpiarFiltros() {
  searchTerm.value = ''
  sortBy.value = 'fecha-desc'
  filtroEstado.value = ''
  filtroUsuario.value = ''
  filtroAccion.value = ''
  filtroFecha.value = ''
  perPage.value = 15

  router.get(route('bitacora.index'), {
    q: '',
    sort_by: 'fecha',
    sort_direction: 'desc',
    estado: '',
    per_page: 15,
    page: 1
  }, { preserveState: false, preserveScroll: false })

  notyf.success('Filtros limpiados')
}

// Handler para búsqueda
function handleSearchChange(newSearch) {
  searchTerm.value = newSearch
  router.get(route('bitacora.index'), {
    q: newSearch,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    estado: filtroEstado.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: false, preserveScroll: false })
}

// Handler para filtro de estado
function handleEstadoChange(newEstado) {
  filtroEstado.value = newEstado
  router.get(route('bitacora.index'), {
    q: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    estado: newEstado,
    per_page: perPage.value,
    page: 1
  }, { preserveState: false, preserveScroll: false })
}

// Handler para ordenamiento
function handleSortChange(newSort) {
  sortBy.value = newSort
  router.get(route('bitacora.index'), {
    q: searchTerm.value,
    sort_by: newSort.split('-')[0],
    sort_direction: newSort.split('-')[1] || 'desc',
    estado: filtroEstado.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: false, preserveScroll: false })
}

const verDetalles = (doc) => {
  selectedActividad.value = doc.raw
  modalMode.value = 'details'
  showModal.value = true
}

const editarActividad = (id) => {
  router.visit(route('bitacora.edit', id))
}

const confirmarEliminacion = (id) => {
  selectedId.value = id
  modalMode.value = 'confirm'
  showModal.value = true
}

const eliminarActividad = () => {
  router.delete(route('bitacora.destroy', selectedId.value), {
    preserveScroll: true,
    onSuccess: () => {
      notyf.success('Actividad eliminada correctamente')
      showModal.value = false
      selectedId.value = null
      router.reload()
    },
    onError: (errors) => {
      notyf.error('No se pudo eliminar la actividad')
    }
  })
}

const exportActividades = () => {
  const params = new URLSearchParams()
  if (searchTerm.value) params.append('q', searchTerm.value)
  if (filtroEstado.value) params.append('estado', filtroEstado.value)
  const queryString = params.toString()
  const url = route('bitacora.export') + (queryString ? `?${queryString}` : '')
  window.location.href = url
}

// Paginación del lado del servidor
const paginationData = computed(() => ({
  current_page: actividadesPaginator.value?.current_page || 1,
  last_page: actividadesPaginator.value?.last_page || 1,
  per_page: actividadesPaginator.value?.per_page || 15,
  from: actividadesPaginator.value?.from || 0,
  to: actividadesPaginator.value?.to || 0,
  total: actividadesPaginator.value?.total || 0,
  prev_page_url: actividadesPaginator.value?.prev_page_url,
  next_page_url: actividadesPaginator.value?.next_page_url,
  links: actividadesPaginator.value?.links || []
}))

const handlePerPageChange = (newPerPage) => {
  router.get(route('bitacora.index'), {
    ...props.filters,
    ...props.sorting,
    per_page: newPerPage,
    page: 1
  }, { preserveState: false, preserveScroll: false })
}

const handlePageChange = (newPage) => {
  router.get(route('bitacora.index'), {
    ...props.filters,
    ...props.sorting,
    page: newPage
  }, { preserveState: false, preserveScroll: false })
}

// Helpers
const formatNumber = (num) => new Intl.NumberFormat('es-ES').format(num)
const formatearFecha = (date) => {
  if (!date) return 'Fecha no disponible'
  try {
    const d = new Date(date)
    return d.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' })
  } catch {
    return 'Fecha inválida'
  }
}

const obtenerClasesEstado = (estado) => {
  const clases = {
    'pendiente': 'bg-yellow-100 text-yellow-700',
    'en_proceso': 'bg-blue-100 text-blue-700',
    'completado': 'bg-green-100 text-green-700',
    'cancelado': 'bg-red-100 text-red-700'
  }
  return clases[estado] || 'bg-gray-100 text-gray-700'
}

const obtenerLabelEstado = (estado) => {
  const labels = {
    'pendiente': 'Pendiente',
    'en_proceso': 'En Proceso',
    'completado': 'Completado',
    'cancelado': 'Cancelado'
  }
  return labels[estado] || 'Pendiente'
}
</script>

<template>
  <Head title="Bitácora de Actividades" />
  <div class="bitacora-index min-h-screen bg-gray-50">
    <div class="w-full px-6 py-8">
      <!-- Header específico de bitácora -->
      <BitacoraHeader
        :total="estadisticas.total"
        :hoy="estadisticasHoy"
        :esta-semana="estadisticasEstaSemana"
        :este-mes="estadisticasEsteMes"
        :usuarios-activos="usuariosActivos"
        :usuarios="usuarios"
        v-model:search-term="searchTerm"
        v-model:sort-by="sortBy"
        v-model:filtro-usuario="filtroUsuario"
        v-model:filtro-accion="filtroAccion"
        v-model:filtro-fecha="filtroFecha"
        @exportar="exportActividades"
        @search-change="handleSearchChange"
        @filtro-usuario-change="handleUsuarioChange"
        @filtro-accion-change="handleAccionChange"
        @filtro-fecha-change="handleFechaChange"
        @sort-change="handleSortChange"
        @limpiar-filtros="handleLimpiarFiltros"
      />

      <!-- Tabla -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Fecha</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Título</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Cliente</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Usuario</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Estado</th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="actividad in actividadesDocumentos" :key="actividad.id" class="hover:bg-gray-50 transition-colors duration-150">
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-900">{{ formatearFecha(actividad.fecha) }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-gray-900">{{ actividad.titulo }}</div>
                  <div class="text-sm text-gray-500 max-w-xs truncate">{{ actividad.subtitulo }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-700">{{ actividad.raw.cliente?.nombre_razon_social || 'N/A' }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-700">{{ actividad.raw.usuario?.name || 'N/A' }}</div>
                </td>
                <td class="px-6 py-4">
                  <span :class="obtenerClasesEstado(actividad.estado)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ obtenerLabelEstado(actividad.estado) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end space-x-1">
                    <button @click="verDetalles(actividad)" class="w-8 h-8 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors duration-150" title="Ver detalles">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>
                    <button @click="editarActividad(actividad.id)" class="w-8 h-8 bg-amber-50 text-amber-600 rounded-lg hover:bg-amber-100 transition-colors duration-150" title="Editar">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button @click="confirmarEliminacion(actividad.id)" class="w-8 h-8 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors duration-150" title="Eliminar">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="actividadesDocumentos.length === 0">
                <td colspan="6" class="px-6 py-16 text-center">
                  <div class="flex flex-col items-center space-y-4">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                      <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                    </div>
                    <div class="space-y-1">
                      <p class="text-gray-700 font-medium">No hay actividades</p>
                      <p class="text-sm text-gray-500">Las actividades aparecerán aquí cuando se creen</p>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div v-if="paginationData.lastPage > 1" class="bg-white border-t border-gray-200 px-4 py-3 sm:px-6">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4">
              <p class="text-sm text-gray-700">
                Mostrando {{ paginationData.from }} - {{ paginationData.to }} de {{ paginationData.total }} resultados
              </p>
              <select
                :value="paginationData.perPage"
                @change="handlePerPageChange(parseInt($event.target.value))"
                class="border border-gray-300 rounded-md text-sm py-1 px-2 bg-white"
              >
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
              </select>
            </div>

            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
              <button
                v-if="paginationData.prevPageUrl"
                @click="handlePageChange(paginationData.currentPage - 1)"
                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
              >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </button>

              <span v-else class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </span>

              <button
                v-for="page in [paginationData.currentPage - 1, paginationData.currentPage, paginationData.currentPage + 1].filter(p => p > 0 && p <= paginationData.lastPage)"
                :key="page"
                @click="handlePageChange(page)"
                :class="page === paginationData.currentPage ? 'bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'"
                class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
              >
                {{ page }}
              </button>

              <button
                v-if="paginationData.nextPageUrl"
                @click="handlePageChange(paginationData.currentPage + 1)"
                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
              >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
              </button>

              <span v-else class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
              </span>
            </nav>
          </div>
        </div>
      </div>

      <!-- Modal -->
      <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showModal = false">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
          <!-- Header del modal -->
          <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">
              {{ modalMode === 'details' ? 'Detalles de la Actividad' : 'Confirmar Eliminación' }}
            </h3>
            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="p-6">
            <div v-if="modalMode === 'details' && selectedActividad">
              <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="space-y-3">
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Título</label>
                      <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">{{ selectedActividad.titulo }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Tipo</label>
                      <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">{{ selectedActividad.tipo }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Fecha</label>
                      <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">{{ formatearFecha(selectedActividad.fecha) }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Estado</label>
                      <span :class="obtenerClasesEstado(selectedActividad.estado)" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium mt-1">
                        {{ obtenerLabelEstado(selectedActividad.estado) }}
                      </span>
                    </div>
                  </div>
                  <div class="space-y-3">
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Cliente</label>
                      <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">{{ selectedActividad.cliente?.nombre_razon_social || 'N/A' }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Usuario</label>
                      <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">{{ selectedActividad.usuario?.name || 'N/A' }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Ubicación</label>
                      <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">{{ selectedActividad.ubicacion || 'N/A' }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Costo</label>
                      <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">${{ formatNumber(selectedActividad.costo_mxn || 0) }}</p>
                    </div>
                  </div>
                </div>
                <div v-if="selectedActividad.descripcion">
                  <label class="block text-sm font-medium text-gray-700">Descripción</label>
                  <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md whitespace-pre-wrap">{{ selectedActividad.descripcion }}</p>
                </div>
              </div>
            </div>

            <div v-if="modalMode === 'confirm'">
              <div class="text-center">
                <div class="w-12 h-12 mx-auto bg-red-100 rounded-full flex items-center justify-center mb-4">
                  <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                  </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">¿Eliminar Actividad?</h3>
                <p class="text-sm text-gray-500 mb-4">
                  ¿Estás seguro de que deseas eliminar la actividad <strong>{{ selectedActividad?.titulo }}</strong>?
                  Esta acción no se puede deshacer.
                </p>
              </div>

              <div class="flex justify-end space-x-3">
                <button @click="showModal = false" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                  Cancelar
                </button>
                <button @click="eliminarActividad" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
                  Eliminar
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>




