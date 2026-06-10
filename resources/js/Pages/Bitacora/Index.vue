
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
    'pendiente': 'bg-brand-100 text-brand-800 dark:text-brand-200 dark:text-amber-200',
    'en_proceso': 'bg-sky-100 text-sky-800 dark:text-sky-200',
    'completado': 'bg-emerald-100 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200',
    'cancelado': 'bg-rose-100 text-rose-800 dark:text-rose-200 dark:text-rose-200'
  }
  return clases[estado] || 'bg-slate-100 text-slate-700'
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
  <div class="bitacora-index min-h-screen bg-[var(--ui-surface)]">
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

            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">

              <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">

                  <thead class="bg-slate-50 dark:bg-slate-800/50">

                    <tr>

                      <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Fecha</th>

                      <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Título</th>

                      <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cliente</th>

                      <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Usuario</th>

                      <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado</th>

                      <th class="px-6 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Acciones</th>

                    </tr>

                  </thead>

                  <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">

                    <tr v-for="actividad in actividadesDocumentos" :key="actividad.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors duration-150">

                      <td class="px-6 py-4">

                        <div class="text-sm text-slate-900 dark:text-slate-100">{{ formatearFecha(actividad.fecha) }}</div>

                      </td>

                      <td class="px-6 py-4">

                        <div class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ actividad.titulo }}</div>

                        <div class="text-sm text-slate-500 dark:text-slate-400 max-w-xs truncate">{{ actividad.subtitulo }}</div>

                      </td>

                      <td class="px-6 py-4">

                        <div class="text-sm text-slate-700 dark:text-slate-200">{{ actividad.raw.cliente?.nombre_razon_social || 'N/A' }}</div>

                      </td>

                      <td class="px-6 py-4">

                        <div class="text-sm text-slate-700 dark:text-slate-200">{{ actividad.raw.usuario?.name || 'N/A' }}</div>

                      </td>

                      <td class="px-6 py-4">

                        <span :class="obtenerClasesEstado(actividad.estado).replace('bg-amber-100', 'bg-brand-50 dark:bg-brand-900/20/40').replace('text-brand-800 dark:text-brand-200 dark:text-amber-200', 'text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-amber-300').replace('bg-sky-100', 'bg-blue-50 dark:bg-sky-900/20/40').replace('text-sky-800 dark:text-sky-200', 'text-sky-800 dark:text-sky-200 dark:text-blue-300').replace('bg-emerald-100', 'bg-emerald-100 dark:bg-slate-800/50').replace('text-emerald-800 dark:text-emerald-200 dark:text-emerald-200', 'text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-emerald-300').replace('bg-rose-100', 'bg-rose-50 dark:bg-rose-900/20/40').replace('text-rose-800 dark:text-rose-200 dark:text-rose-200', 'text-rose-800 dark:text-rose-200 dark:text-rose-200 dark:text-rose-300').replace('bg-slate-100', 'bg-slate-100 dark:bg-slate-700').replace('text-slate-700', 'text-slate-700 dark:text-slate-200')" class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium">

                          {{ obtenerLabelEstado(actividad.estado) }}

                        </span>

                      </td>

                      <td class="px-6 py-4 text-right">

                        <div class="flex items-center justify-end space-x-1">

                          <button @click="verDetalles(actividad)" class="w-10 h-10 bg-sky-50 dark:bg-sky-900/20 dark:bg-sky-900/20 text-blue-600 dark:text-blue-400 rounded-xl hover:bg-sky-100 dark:hover:bg-blue-900/40 transition-colors duration-150" title="Ver detalles">

                            <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />

                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />

                            </svg>

                          </button>

                          <button @click="editarActividad(actividad.id)" class="w-10 h-10 bg-brand-50 dark:bg-brand-900/20 text-brand-600 dark:text-brand-400 rounded-xl hover:bg-brand-100 dark:hover:bg-brand-900/40 transition-colors duration-150" title="Editar">

                            <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />

                            </svg>

                          </button>

                          <button @click="confirmarEliminacion(actividad.id)" class="w-10 h-10 bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 rounded-xl hover:bg-rose-100 dark:hover:bg-rose-900/40 transition-colors duration-150" title="Eliminar">

                            <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />

                            </svg>

                          </button>

                        </div>

                      </td>

                    </tr>

                    <tr v-if="actividadesDocumentos.length === 0">

                      <td colspan="6" class="px-6 py-16 text-center">

                        <div class="flex flex-col items-center space-y-6">

                          <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center">

                            <svg class="w-10 h-10 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">

                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />

                            </svg>

                          </div>

                          <div class="space-y-1">

                            <p class="text-slate-700 dark:text-slate-200 font-medium">No hay actividades</p>

                            <p class="text-sm text-slate-500 dark:text-slate-400">Las actividades aparecerán aquí cuando se creen</p>

                          </div>

                        </div>

                      </td>

                    </tr>

                  </tbody>

                </table>

              </div>

        <!-- Paginación -->
        <div v-if="paginationData.lastPage > 1" class="bg-white dark:bg-slate-800 border-t border-slate-300 dark:border-slate-600 px-4 py-3 sm:px-6">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4">
              <p class="text-sm text-slate-700 dark:text-slate-200">
                Mostrando {{ paginationData.from }} - {{ paginationData.to }} de {{ paginationData.total }} resultados
              </p>
              <select
                :value="paginationData.perPage"
                @change="handlePerPageChange(parseInt($event.target.value))"
                class="border border-slate-300 dark:border-slate-700 rounded-xl text-sm py-1 px-2 bg-white dark:bg-slate-700 dark:text-slate-200"
              >
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
              </select>
            </div>

            <nav class="relative z-0 inline-flex rounded-xl shadow-sm -space-x-px">
              <button
                v-if="paginationData.prevPageUrl"
                @click="handlePageChange(paginationData.currentPage - 1)"
                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-700 text-sm font-medium text-slate-500 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-600"
              >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </button>

              <span v-else class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-700 text-sm font-medium text-slate-400 dark:text-slate-500">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </span>

              <button
                v-for="page in [paginationData.currentPage - 1, paginationData.currentPage, paginationData.currentPage + 1].filter(p => p > 0 && p <= paginationData.lastPage)"
                :key="page"
                @click="handlePageChange(page)"
                :class="page === paginationData.currentPage ? 'bg-sky-50 dark:bg-sky-900/20 dark:bg-sky-900/40 border-blue-500 dark:border-blue-700 text-blue-600 dark:text-blue-300' : 'bg-white dark:bg-slate-700 border-slate-300 dark:border-slate-700 text-slate-500 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-600'"
                class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
              >
                {{ page }}
              </button>

              <button
                v-if="paginationData.nextPageUrl"
                @click="handlePageChange(paginationData.currentPage + 1)"
                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-700 text-sm font-medium text-slate-500 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-600"
              >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
              </button>

              <span v-else class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-700 text-sm font-medium text-slate-400 dark:text-slate-500">
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
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto custom-scrollbar">
          <!-- Header del modal -->
          <div class="flex items-center justify-between p-6 border-b border-slate-300 dark:border-slate-600">
            <h3 class="text-lg font-medium text-slate-900 dark:text-slate-100">
              {{ modalMode === 'details' ? 'Detalles de la Actividad' : 'Confirmar Eliminación' }}
            </h3>
            <button @click="showModal = false" class="text-slate-400 dark:text-slate-500 hover:text-brand-600 dark:hover:text-slate-300 transition-colors">
              <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="p-6">
            <div v-if="modalMode === 'details' && selectedActividad">
              <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="space-y-3">
                    <div>
                      <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Título</label>
                      <p class="mt-1 text-sm text-slate-900 dark:text-slate-100 bg-white dark:bg-slate-700 px-3 py-2 rounded-xl">{{ selectedActividad.titulo }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Tipo</label>
                      <p class="mt-1 text-sm text-slate-900 dark:text-slate-100 bg-white dark:bg-slate-700 px-3 py-2 rounded-xl">{{ selectedActividad.tipo }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Fecha</label>
                      <p class="mt-1 text-sm text-slate-900 dark:text-slate-100 bg-white dark:bg-slate-700 px-3 py-2 rounded-xl">{{ formatearFecha(selectedActividad.fecha) }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Estado</label>
                      <span :class="obtenerClasesEstado(selectedActividad.estado).replace('bg-amber-100', 'bg-brand-50 dark:bg-brand-900/20/40').replace('text-brand-800 dark:text-brand-200 dark:text-amber-200', 'text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-amber-300').replace('bg-sky-100', 'bg-blue-50 dark:bg-sky-900/20/40').replace('text-sky-800 dark:text-sky-200', 'text-sky-800 dark:text-sky-200 dark:text-blue-300').replace('bg-emerald-100', 'bg-emerald-100 dark:bg-slate-800/50').replace('text-emerald-800 dark:text-emerald-200 dark:text-emerald-200', 'text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-emerald-300').replace('bg-rose-100', 'bg-rose-50 dark:bg-rose-900/20/40').replace('text-rose-800 dark:text-rose-200 dark:text-rose-200', 'text-rose-800 dark:text-rose-200 dark:text-rose-200 dark:text-rose-300').replace('bg-slate-100', 'bg-slate-100 dark:bg-slate-700').replace('text-slate-700', 'text-slate-700 dark:text-slate-200')" class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-medium mt-1">
                        {{ obtenerLabelEstado(selectedActividad.estado) }}
                      </span>
                    </div>
                  </div>
                  <div class="space-y-3">
                    <div>
                      <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Cliente</label>
                      <p class="mt-1 text-sm text-slate-900 dark:text-slate-100 bg-white dark:bg-slate-700 px-3 py-2 rounded-xl">{{ selectedActividad.cliente?.nombre_razon_social || 'N/A' }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Usuario</label>
                      <p class="mt-1 text-sm text-slate-900 dark:text-slate-100 bg-white dark:bg-slate-700 px-3 py-2 rounded-xl">{{ selectedActividad.usuario?.name || 'N/A' }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Ubicación</label>
                      <p class="mt-1 text-sm text-slate-900 dark:text-slate-100 bg-white dark:bg-slate-700 px-3 py-2 rounded-xl">{{ selectedActividad.ubicacion || 'N/A' }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Costo</label>
                      <p class="mt-1 text-sm text-slate-900 dark:text-slate-100 bg-white dark:bg-slate-700 px-3 py-2 rounded-xl">${{ formatNumber(selectedActividad.costo_mxn || 0) }}</p>
                    </div>
                  </div>
                </div>
                <div v-if="selectedActividad.descripcion">
                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Descripción</label>
                  <p class="mt-1 text-sm text-slate-900 dark:text-slate-100 bg-white dark:bg-slate-700 px-3 py-2 rounded-xl whitespace-pre-wrap">{{ selectedActividad.descripcion }}</p>
                </div>
              </div>
            </div>

            <div v-if="modalMode === 'confirm'">
              <div class="text-center">
                <div class="w-10 h-10 mx-auto bg-rose-50 dark:bg-rose-900/20/20 rounded-full flex items-center justify-center mb-4">
                  <svg class="w-10 h-10 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                  </svg>
                </div>
                <h3 class="text-lg font-medium text-slate-900 dark:text-slate-100 mb-2">¿Eliminar Actividad?</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                  ¿Estás seguro de que deseas eliminar la actividad <strong>{{ selectedActividad?.titulo }}</strong>?
                  Esta acción no se puede deshacer.
                </p>
              </div>

              <div class="flex justify-end space-x-3">
                <button @click="showModal = false" class="px-4 py-2 bg-slate-300 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl hover:bg-slate-400 dark:hover:bg-slate-600 transition-colors">
                  Cancelar
                </button>
                <button @click="eliminarActividad" class="px-4 py-2 bg-rose-600 text-white rounded-xl hover:bg-rose-700 transition-colors">
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




