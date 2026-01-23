<!-- /resources/js/Pages/Categorias/Index.vue -->
<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

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
  categorias: { type: [Object, Array], required: true },
  stats: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  sorting: { type: Object, default: () => ({ sort_by: 'nombre', sort_direction: 'asc' }) },
})

// Estado UI
const showModal = ref(false)
const modalMode = ref('details')
const selectedCategoria = ref(null)
const selectedId = ref(null)

// Filtros
const searchTerm = ref(props.filters?.search ?? '')
const sortBy = ref('nombre-asc')
const filtroEstado = ref('')

// Paginación
const perPage = ref(10)

// Header config
const headerConfig = {
  module: 'categorias',
  createButtonText: 'Nueva Categoría',
  searchPlaceholder: 'Buscar por nombre o descripción...'
}

// Datos
const categoriasPaginator = computed(() => props.categorias)
const categoriasData = computed(() => categoriasPaginator.value?.data || [])

// Estadísticas
const estadisticas = computed(() => ({
  total: props.stats?.total ?? 0,
  activos: props.stats?.activos ?? 0,
  inactivos: props.stats?.inactivos ?? 0,
  activosPorcentaje: props.stats?.activos_porcentaje ?? 0,
  inactivosPorcentaje: props.stats?.inactivos_porcentaje ?? 0
}))

// Transformación de datos
const categoriasDocumentos = computed(() => {
  return categoriasData.value.map(c => ({
    id: c.id,
    titulo: c.nombre || 'Sin nombre',
    subtitulo: c.descripcion ? `Descripción: ${c.descripcion.substring(0, 50)}${c.descripcion.length > 50 ? '...' : ''}` : 'Sin descripción',
    estado: c.estado || 'activo',
    extra: `Creada: ${new Date(c.created_at).toLocaleDateString('es-MX')}`,
    fecha: c.created_at,
    raw: c
  }))
})

// Handlers
function handleSearchChange(newSearch) {
  searchTerm.value = newSearch
  router.get(route('categorias.index'), {
    search: newSearch,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'asc',
    estado: filtroEstado.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

function handleEstadoChange(newEstado) {
  filtroEstado.value = newEstado
  router.get(route('categorias.index'), {
    search: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'asc',
    estado: newEstado,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

function handleSortChange(newSort) {
  sortBy.value = newSort
  router.get(route('categorias.index'), {
    search: searchTerm.value,
    sort_by: newSort.split('-')[0],
    sort_direction: newSort.split('-')[1] || 'asc',
    estado: filtroEstado.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const verDetalles = (doc) => {
  selectedCategoria.value = doc.raw
  modalMode.value = 'details'
  showModal.value = true
}

const editarCategoria = (id) => {
  router.visit(route('categorias.edit', id))
}

const confirmarEliminacion = (id) => {
  selectedId.value = id
  modalMode.value = 'confirm'
  showModal.value = true
}

const eliminarCategoria = () => {
  router.delete(route('categorias.destroy', selectedId.value), {
    preserveScroll: true,
    onSuccess: () => {
      notyf.success('Categoría eliminada')
      showModal.value = false
      selectedId.value = null
      router.reload()
    },
    onError: (errors) => {
      notyf.error('No se pudo eliminar la categoría')
    }
  })
}

const toggleCategoria = (id) => {
  const categoria = categoriasData.value.find(c => c.id === id)
  if (!categoria) return notyf.error('Categoría no encontrada')
  const nuevoEstado = categoria.estado === 'activo' ? 'inactivo' : 'activo'
  const mensaje = nuevoEstado === 'activo' ? 'Categoría activada' : 'Categoría desactivada'

  router.put(route('categorias.toggle', id), {
    preserveScroll: true,
    onSuccess: () => {
      notyf.success(mensaje + ' correctamente')
      router.reload()
    },
    onError: (errors) => {
      notyf.error('No se pudo cambiar el estado de la categoría')
    }
  })
}

const exportCategorias = () => {
  const params = new URLSearchParams()
  if (searchTerm.value) params.append('search', searchTerm.value)
  if (filtroEstado.value) params.append('estado', filtroEstado.value)
  const queryString = params.toString()
  const url = route('categorias.export') + (queryString ? `?${queryString}` : '')
  window.location.href = url
}

// Paginación
const paginationData = computed(() => ({
  current_page: categoriasPaginator.value?.current_page || 1,
  last_page: categoriasPaginator.value?.last_page || 1,
  per_page: categoriasPaginator.value?.per_page || 10,
  from: categoriasPaginator.value?.from || 0,
  to: categoriasPaginator.value?.to || 0,
  total: categoriasPaginator.value?.total || 0,
  prev_page_url: categoriasPaginator.value?.prev_page_url,
  next_page_url: categoriasPaginator.value?.next_page_url,
  links: categoriasPaginator.value?.links || []
}))

const handlePerPageChange = (newPerPage) => {
  router.get(route('categorias.index'), {
    ...props.filters,
    ...props.sorting,
    per_page: newPerPage,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const handlePageChange = (newPage) => {
  router.get(route('categorias.index'), {
    ...props.filters,
    ...props.sorting,
    page: newPage
  }, { preserveState: true, preserveScroll: true })
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
    'activo': 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300',
    'inactivo': 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300'
  }
  return clases[estado] || 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'
}

const obtenerLabelEstado = (estado) => {
  const labels = {
    'activo': 'Activo',
    'inactivo': 'Inactivo'
  }
  return labels[estado] || 'Pendiente'
}
</script>

<template>
  <Head title="Categorías" />
  <div class="categorias-index min-h-screen bg-gray-50 dark:bg-slate-950 dark:bg-gray-900">
    <div class="w-full px-6 py-8">
      <!-- Header -->
      <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl shadow-sm p-8 mb-6">
        <div class="flex flex-col lg:flex-row gap-8 items-start lg:items-center justify-between">
          <!-- Izquierda -->
          <div class="flex flex-col gap-6 w-full lg:w-auto">
            <div class="flex items-center gap-3">
              <h1 class="text-2xl font-bold text-slate-900 dark:text-gray-100">Categorías</h1>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center">
              <Link
                :href="route('categorias.create')"
                class="inline-flex items-center gap-2.5 px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all duration-200 shadow-lg"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>{{ headerConfig.createButtonText }}</span>
              </Link>

              <button
                @click="exportCategorias"
                class="inline-flex items-center gap-2 px-4 py-3 bg-green-50 dark:bg-green-900/40 text-green-700 dark:text-green-300 rounded-xl hover:bg-green-100 dark:hover:bg-green-900/60 transition-all duration-200 border border-green-200 dark:border-green-700"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                </svg>
                <span class="text-sm font-medium">Exportar</span>
              </button>
            </div>

            <!-- Estadísticas con barras de progreso -->
            <div class="flex flex-wrap items-center gap-4 text-sm">
              <div class="flex items-center gap-2 px-4 py-3 bg-slate-50 dark:bg-gray-700 rounded-xl border border-slate-200 dark:border-gray-600">
                <svg class="w-5 h-5 text-slate-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span class="font-medium text-slate-700 dark:text-gray-300">Total:</span>
                <span class="font-bold text-slate-900 dark:text-gray-100 text-lg">{{ formatNumber(estadisticas.total) }}</span>
              </div>

              <div class="flex items-center gap-2 px-4 py-3 bg-green-50 dark:bg-green-900/40 rounded-xl border border-green-200 dark:border-green-700">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-medium text-slate-700 dark:text-gray-300">Activos:</span>
                <span class="font-bold text-green-700 dark:text-green-300 text-lg">{{ formatNumber(estadisticas.activos) }}</span>
                <div class="ml-2 flex items-center gap-2">
                  <div class="w-16 h-2 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                    <div
                      class="h-full bg-green-500 transition-all duration-300"
                      :style="{ width: estadisticas.activosPorcentaje + '%' }"
                    ></div>
                  </div>
                  <span class="text-xs text-green-600 dark:text-green-400 font-medium">{{ estadisticas.activosPorcentaje }}%</span>
                </div>
              </div>

              <div class="flex items-center gap-2 px-4 py-3 bg-red-50 dark:bg-red-900/40 rounded-xl border border-red-200 dark:border-red-700">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-medium text-slate-700 dark:text-gray-300">Inactivos:</span>
                <span class="font-bold text-red-700 dark:text-red-300 text-lg">{{ formatNumber(estadisticas.inactivos) }}</span>
                <div class="ml-2 flex items-center gap-2">
                  <div class="w-16 h-2 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                    <div
                      class="h-full bg-red-500 transition-all duration-300"
                      :style="{ width: estadisticas.inactivosPorcentaje + '%' }"
                    ></div>
                  </div>
                  <span class="text-xs text-red-600 dark:text-red-400 font-medium">{{ estadisticas.inactivosPorcentaje }}%</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Derecha: Filtros -->
          <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto lg:flex-shrink-0">
            <!-- Búsqueda -->
            <div class="relative">
              <input
                v-model="searchTerm"
                @input="handleSearchChange($event.target.value)"
                type="text"
                :placeholder="headerConfig.searchPlaceholder"
                class="w-full sm:w-64 lg:w-80 pl-4 pr-10 py-3 border border-slate-300 dark:border-gray-600 rounded-xl bg-white dark:bg-slate-900 dark:bg-gray-700 text-slate-900 dark:text-gray-200 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all duration-200"
              />
              <svg class="absolute right-3 top-3.5 w-4 h-4 text-slate-400 dark:text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>

            <!-- Estado -->
            <select
              v-model="filtroEstado"
              @change="handleEstadoChange($event.target.value)"
              class="px-4 py-3 border border-slate-300 dark:border-gray-600 rounded-xl bg-white dark:bg-slate-900 dark:bg-gray-700 text-slate-900 dark:text-gray-200 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all duration-200"
            >
              <option value="">Todos los Estados</option>
              <option value="activo">Activos</option>
              <option value="inactivo">Inactivos</option>
            </select>

            <!-- Orden -->
            <select
              v-model="sortBy"
              @change="handleSortChange($event.target.value)"
              class="px-4 py-3 border border-slate-300 dark:border-gray-600 rounded-xl bg-white dark:bg-slate-900 dark:bg-gray-700 text-slate-900 dark:text-gray-200 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all duration-200"
            >
              <option value="nombre-asc">Nombre A-Z</option>
              <option value="nombre-desc">Nombre Z-A</option>
              <option value="fecha-desc">Más Recientes</option>
              <option value="fecha-asc">Más Antiguos</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Tabla -->
      <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800 dark:divide-gray-700">
            <thead class="bg-white dark:bg-slate-900 dark:bg-gray-700/50">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-300 uppercase tracking-wider">Fecha</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-300 uppercase tracking-wider">Categoría</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-300 uppercase tracking-wider">Descripción</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-300 uppercase tracking-wider">Estado</th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-slate-900 dark:bg-gray-800 divide-y divide-gray-200 dark:divide-slate-800 dark:divide-gray-700">
              <tr v-for="categoria in categoriasDocumentos" :key="categoria.id" class="hover:bg-white dark:bg-slate-900 dark:hover:bg-gray-700/50 transition-colors duration-150">
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-900 dark:text-white dark:text-gray-100">{{ formatearFecha(categoria.fecha) }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-gray-900 dark:text-white dark:text-gray-100">{{ categoria.titulo }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-700 dark:text-gray-300 max-w-xs truncate">{{ categoria.raw.descripcion || 'Sin descripción' }}</div>
                </td>
                <td class="px-6 py-4">
                  <span :class="obtenerClasesEstado(categoria.estado)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ obtenerLabelEstado(categoria.estado) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end space-x-1">
                    <button @click="verDetalles(categoria)" class="w-8 h-8 bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/60 transition-colors duration-150" title="Ver detalles">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>
                    <button @click="editarCategoria(categoria.id)" class="w-8 h-8 bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/60 transition-colors duration-150" title="Editar">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button @click="toggleCategoria(categoria.id)" class="w-8 h-8 bg-green-50 dark:bg-green-900/40 text-green-600 dark:text-green-400 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/60 transition-colors duration-150" title="Cambiar estado">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                      </svg>
                    </button>
                    <button @click="confirmarEliminacion(categoria.id)" class="w-8 h-8 bg-red-50 dark:bg-red-900/40 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/60 transition-colors duration-150" title="Eliminar">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="categoriasDocumentos.length === 0">
                <td colspan="5" class="px-6 py-16 text-center">
                  <div class="flex flex-col items-center space-y-4">
                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                      <svg class="w-8 h-8 text-gray-400 dark:text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                    </div>
                    <div class="space-y-1">
                      <p class="text-gray-700 dark:text-gray-300 font-medium">No hay categorías</p>
                      <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">Las categorías aparecerán aquí cuando se creen</p>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div v-if="paginationData.lastPage > 1" class="bg-white dark:bg-slate-900 dark:bg-gray-800 border-t border-gray-200 dark:border-slate-800 dark:border-gray-700 px-4 py-3 sm:px-6">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4">
              <p class="text-sm text-gray-700 dark:text-gray-300">
                Mostrando {{ paginationData.from }} - {{ paginationData.to }} de {{ paginationData.total }} resultados
              </p>
              <select
                :value="paginationData.perPage"
                @change="handlePerPageChange(parseInt($event.target.value))"
                class="border border-gray-300 dark:border-gray-600 rounded-md text-sm py-1 px-2 bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
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
                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-600"
              >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </button>

              <span v-else class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-sm font-medium text-gray-400 dark:text-gray-500 dark:text-gray-400">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </span>

              <button
                v-for="page in [paginationData.currentPage - 1, paginationData.currentPage, paginationData.currentPage + 1].filter(p => p > 0 && p <= paginationData.lastPage)"
                :key="page"
                @click="handlePageChange(page)"
                :class="page === paginationData.currentPage ? 'bg-blue-500 text-white border-blue-500' : 'bg-white dark:bg-slate-900 dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-600'"
                class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
              >
                {{ page }}
              </button>

              <button
                v-if="paginationData.nextPageUrl"
                @click="handlePageChange(paginationData.currentPage + 1)"
                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-600"
              >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
              </button>

              <span v-else class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-sm font-medium text-gray-400 dark:text-gray-500 dark:text-gray-400">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
              </span>
            </nav>
          </div>
        </div>
      </div>

      <!-- Modal mejorado -->
      <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showModal = false">
        <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
          <!-- Header del modal -->
          <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-slate-800 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white dark:text-gray-100">
              {{ modalMode === 'details' ? 'Detalles de la Categoría' : 'Confirmar Eliminación' }}
            </h3>
            <button @click="showModal = false" class="text-gray-400 dark:text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="p-6">
            <div v-if="modalMode === 'details' && selectedCategoria">
              <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="space-y-3">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre</label>
                      <p class="mt-1 text-sm text-gray-900 dark:text-white dark:text-gray-100 bg-white dark:bg-slate-900 dark:bg-gray-700 px-3 py-2 rounded-md">{{ selectedCategoria.nombre }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado</label>
                      <span :class="obtenerClasesEstado(selectedCategoria.estado)" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium mt-1">
                        {{ obtenerLabelEstado(selectedCategoria.estado) }}
                      </span>
                    </div>
                  </div>
                  <div class="space-y-3">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha de creación</label>
                      <p class="mt-1 text-sm text-gray-900 dark:text-white dark:text-gray-100 bg-white dark:bg-slate-900 dark:bg-gray-700 px-3 py-2 rounded-md">{{ formatearFecha(selectedCategoria.created_at) }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Última actualización</label>
                      <p class="mt-1 text-sm text-gray-900 dark:text-white dark:text-gray-100 bg-white dark:bg-slate-900 dark:bg-gray-700 px-3 py-2 rounded-md">{{ formatearFecha(selectedCategoria.updated_at) }}</p>
                    </div>
                  </div>
                </div>
                <div v-if="selectedCategoria.descripcion">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descripción</label>
                  <p class="mt-1 text-sm text-gray-900 dark:text-white dark:text-gray-100 bg-white dark:bg-slate-900 dark:bg-gray-700 px-3 py-2 rounded-md whitespace-pre-wrap">{{ selectedCategoria.descripcion }}</p>
                </div>
              </div>
            </div>

            <div v-if="modalMode === 'confirm'">
              <div class="text-center">
                <div class="w-12 h-12 mx-auto bg-red-100 dark:bg-red-900/40 rounded-full flex items-center justify-center mb-4">
                  <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                  </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white dark:text-gray-100 mb-2">¿Eliminar Categoría?</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-4">
                  ¿Estás seguro de que deseas eliminar la categoría <strong>{{ selectedCategoria?.nombre }}</strong>?
                  Esta acción no se puede deshacer.
                </p>
              </div>
            </div>
          </div>

          <!-- Footer del modal -->
          <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-slate-800 dark:border-gray-700 bg-white dark:bg-slate-900 dark:bg-gray-800">
            <button @click="showModal = false" class="px-4 py-2 bg-gray-300 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-600 transition-colors">
              {{ modalMode === 'details' ? 'Cerrar' : 'Cancelar' }}
            </button>
            <div v-if="modalMode === 'details'" class="flex gap-2">
              <button @click="toggleCategoria(selectedCategoria.id)" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                Cambiar Estado
              </button>
              <button @click="editarCategoria(selectedCategoria.id)" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors">
                Editar
              </button>
            </div>
            <button v-if="modalMode === 'confirm'" @click="eliminarCategoria" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
              Eliminar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.categorias-index {
  min-height: 100vh;
}
</style>




