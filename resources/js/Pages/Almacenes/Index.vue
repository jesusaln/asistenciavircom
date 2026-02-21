<!-- /resources/js/Pages/Almacenes/Index.vue -->
<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

import AlmacenesHeader from '@/Components/IndexComponents/AlmacenesHeader.vue'

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
  almacenes: { type: [Object, Array], required: true },
  stats: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  sorting: { type: Object, default: () => ({ sort_by: 'nombre', sort_direction: 'asc' }) },
})

// Estado UI
const showModal = ref(false)
const modalMode = ref('details')
const selectedAlmacen = ref(null)
const selectedId = ref(null)

// Filtros
const searchTerm = ref(props.filters?.search ?? '')
const sortBy = ref('nombre-asc')
const filtroEstado = ref(props.filters?.estado ?? '')
const filtroTipo = ref('')

// Paginación
const perPage = ref(10)

// Función para crear nuevo almacén
const crearNuevoAlmacen = () => {
  router.visit(route('almacenes.create'))
}

// Función para limpiar filtros
const limpiarFiltros = () => {
  searchTerm.value = ''
  sortBy.value = 'nombre-asc'
  filtroEstado.value = ''
  filtroTipo.value = ''
  router.visit(route('almacenes.index'))
  notyf.success('Filtros limpiados correctamente')
}

// Estadísticas adicionales para el header moderno
const conResponsable = computed(() => {
  // Contar almacenes que tienen responsable asignado
  if (almacenesData.value && almacenesData.value.length > 0) {
    return almacenesData.value.filter(almacen =>
      almacen.responsable && almacen.responsable.id
    ).length
  }
  return 0
})

const conTelefono = computed(() => {
  // Contar almacenes que tienen teléfono
  if (almacenesData.value && almacenesData.value.length > 0) {
    return almacenesData.value.filter(almacen =>
      almacen.telefono && typeof almacen.telefono === 'string' && almacen.telefono.trim() !== ''
    ).length
  }
  return 0
})

// Función para manejar filtro de tipo
const handleTipoChange = (tipo) => {
  filtroTipo.value = tipo
  router.get(route('almacenes.index'), {
    search: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'asc',
    estado: filtroEstado.value,
    tipo: tipo,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

// Datos
const almacenesPaginator = computed(() => props.almacenes)
const almacenesData = computed(() => almacenesPaginator.value?.data || [])

// Estadísticas
const estadisticas = computed(() => ({
  total: props.stats?.total ?? 0,
  activos: props.stats?.activos ?? 0,
  inactivos: props.stats?.inactivos ?? 0,
  activosPorcentaje: props.stats?.activos_porcentaje ?? 0,
  inactivosPorcentaje: props.stats?.inactivos_porcentaje ?? 0
}))

// Transformación de datos
const almacenesDocumentos = computed(() => {
  return almacenesData.value.map(a => ({
    id: a.id,
    titulo: a.nombre || 'Sin nombre',
    subtitulo: a.direccion ? `Dirección: ${a.direccion.substring(0, 40)}${a.direccion.length > 40 ? '...' : ''}` : 'Sin dirección',
    estado: a.estado || 'activo',
    extra: `Responsable: ${a.responsable?.name || 'Sin asignar'} • Tel: ${a.telefono || 'N/A'}`,
    fecha: a.created_at,
    raw: a
  }))
})

// Handlers
function handleSearchChange(newSearch) {
  searchTerm.value = newSearch
  router.get(route('almacenes.index'), {
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
  router.get(route('almacenes.index'), {
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
  router.get(route('almacenes.index'), {
    search: searchTerm.value,
    sort_by: newSort.split('-')[0],
    sort_direction: newSort.split('-')[1] || 'asc',
    estado: filtroEstado.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const verDetalles = (doc) => {
  selectedAlmacen.value = doc.raw
  modalMode.value = 'details'
  showModal.value = true
}

const editarAlmacen = (id) => {
  router.visit(route('almacenes.edit', id))
}

const confirmarEliminacion = (id) => {
  selectedId.value = id
  modalMode.value = 'confirm'
  showModal.value = true
}

const eliminarAlmacen = () => {
  router.delete(route('almacenes.destroy', selectedId.value), {
    preserveScroll: true,
    onSuccess: () => {
      notyf.success('Almacén eliminado')
      showModal.value = false
      selectedId.value = null
      router.reload()
    },
    onError: (errors) => {
      notyf.error('No se pudo eliminar el almacén')
    }
  })
}

const toggleAlmacen = (id) => {
  const almacen = almacenesData.value.find(a => a.id === id)
  if (!almacen) return notyf.error('Almacén no encontrado')
  const nuevoEstado = almacen.estado === 'activo' ? 'inactivo' : 'activo'
  const mensaje = nuevoEstado === 'activo' ? 'Almacén activado' : 'Almacén desactivado'

  router.put(route('almacenes.toggle', id), {
    preserveScroll: true,
    onSuccess: () => {
      notyf.success(mensaje + ' correctamente')
      router.reload()
    },
    onError: (errors) => {
      notyf.error('No se pudo cambiar el estado del almacén')
    }
  })
}

const exportAlmacenes = () => {
  const params = new URLSearchParams()
  if (searchTerm.value) params.append('search', searchTerm.value)
  if (filtroEstado.value) params.append('estado', filtroEstado.value)
  const queryString = params.toString()
  const url = route('almacenes.export') + (queryString ? `?${queryString}` : '')
  window.location.href = url
}

// Paginación
const paginationData = computed(() => ({
  current_page: almacenesPaginator.value?.current_page || 1,
  last_page: almacenesPaginator.value?.last_page || 1,
  per_page: almacenesPaginator.value?.per_page || 10,
  from: almacenesPaginator.value?.from || 0,
  to: almacenesPaginator.value?.to || 0,
  total: almacenesPaginator.value?.total || 0,
  prev_page_url: almacenesPaginator.value?.prev_page_url,
  next_page_url: almacenesPaginator.value?.next_page_url,
  links: almacenesPaginator.value?.links || []
}))

const handlePerPageChange = (newPerPage) => {
  router.get(route('almacenes.index'), {
    ...props.filters,
    ...props.sorting,
    per_page: newPerPage,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const handlePageChange = (newPage) => {
  router.get(route('almacenes.index'), {
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
  <Head title="Almacenes | Gestión de Activos" />
  <div class="almacenes-index min-h-screen bg-slate-50 dark:bg-slate-950 transition-colors duration-500">
    <div class="w-full max-w-[1600px] mx-auto px-6 py-12 lg:px-10">
      
      <!-- Header específico de almacenes -->
      <AlmacenesHeader
        :total="estadisticas.total"
        :activos="estadisticas.activos"
        :inactivos="estadisticas.inactivos"
        :con-responsable="estadisticas.conResponsable"
        :con-telefono="estadisticas.conTelefono"
        v-model:search-term="searchTerm"
        v-model:sort-by="sortBy"
        v-model:filtro-estado="filtroEstado"
        @crear-nueva="crearNuevoAlmacen"
        @search-change="handleSearchChange"
        @filtro-estado-change="handleEstadoChange"
        @sort-change="handleSortChange"
        @limpiar-filtros="limpiarFiltros"
      />

      <!-- Content Section -->
      <div class="space-y-8 animate-fade-in">
        
        <!-- Table Card -->
        <div class="bg-white/70 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] border border-slate-200/50 dark:border-slate-800/50 shadow-xl shadow-slate-200/40 dark:shadow-none overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full">
              <thead>
                <tr class="border-b border-slate-100 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-950/30">
                  <th class="px-8 py-6 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Identificación</th>
                  <th class="px-8 py-6 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Ubicación y Contacto</th>
                  <th class="px-8 py-6 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Gestión</th>
                  <th class="px-8 py-6 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Estatus Operativo</th>
                  <th class="px-8 py-6 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Controles</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                <tr v-for="almacen in almacenesDocumentos" :key="almacen.id" 
                    class="group hover:bg-blue-50/30 dark:hover:bg-blue-500/5 transition-all duration-300">
                  <td class="px-8 py-6 whitespace-nowrap">
                    <div class="flex items-center gap-4">
                      <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:bg-blue-600 group-hover:text-white transition-all duration-500">
                          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                          </svg>
                      </div>
                      <div class="flex flex-col">
                        <span class="text-sm font-black text-slate-900 dark:text-white tracking-tight">{{ almacen.titulo }}</span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ formatearFecha(almacen.fecha) }}</span>
                      </div>
                    </div>
                  </td>
                  <td class="px-8 py-6 max-w-xs">
                    <div class="flex flex-col gap-1">
                      <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="text-xs font-medium truncate">{{ almacen.raw.direccion || 'Sin dirección registrada' }}</span>
                      </div>
                      <div class="flex items-center gap-2 text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <span class="text-[10px] font-bold tracking-widest">{{ almacen.raw.telefono || 'N/A' }}</span>
                      </div>
                    </div>
                  </td>
                  <td class="px-8 py-6">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                        <div class="w-1.5 h-1.5 rounded-full bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.5)]"></div>
                        <span class="text-[10px] font-black uppercase tracking-widest">{{ almacen.raw.responsable?.name || 'SIN ASIGNAR' }}</span>
                    </div>
                  </td>
                  <td class="px-8 py-6">
                    <div :class="[
                        'inline-flex items-center gap-2 px-4 py-2 rounded-2xl text-[10px] font-black uppercase tracking-[0.15em] border transition-all duration-300',
                        almacen.estado === 'activo' 
                            ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20 group-hover:bg-emerald-500 group-hover:text-white' 
                            : 'bg-rose-500/10 text-rose-600 border-rose-500/20 group-hover:bg-rose-500 group-hover:text-white'
                    ]">
                        <span class="w-1.5 h-1.5 rounded-full" :class="almacen.estado === 'activo' ? 'bg-emerald-500 group-hover:bg-white' : 'bg-rose-500 group-hover:bg-white'"></span>
                        {{ obtenerLabelEstado(almacen.estado) }}
                    </div>
                  </td>
                  <td class="px-8 py-6 text-right whitespace-nowrap">
                    <div class="flex items-center justify-end gap-3 opacity-0 group-hover:opacity-100 translate-x-4 group-hover:translate-x-0 transition-all duration-500">
                      <button @click="verDetalles(almacen)" class="control-btn bg-blue-500/10 text-blue-600 hover:bg-blue-600 hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                      </button>
                      <button @click="editarAlmacen(almacen.id)" class="control-btn bg-amber-500/10 text-amber-600 hover:bg-amber-600 hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                      </button>
                      <button @click="toggleAlmacen(almacen.id)" class="control-btn bg-emerald-500/10 text-emerald-600 hover:bg-emerald-600 hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                      </button>
                      <button @click="confirmarEliminacion(almacen.id)" class="control-btn bg-rose-500/10 text-rose-600 hover:bg-rose-600 hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="almacenesDocumentos.length === 0">
                  <td colspan="5" class="px-8 py-32 text-center bg-slate-50/30 dark:bg-slate-950/20">
                    <div class="flex flex-col items-center gap-6 animate-pulse">
                      <div class="w-24 h-24 rounded-[2.5rem] bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                          <svg class="w-10 h-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                          </svg>
                      </div>
                      <div class="space-y-2">
                        <p class="text-xl font-black text-slate-400 uppercase tracking-widest">Zona de Almacenaje Despejada</p>
                        <p class="text-sm text-slate-400 font-medium tracking-tight">Inicia el despliegue registrando el primer centro logístico.</p>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Paginación Premium -->
          <div v-if="paginationData.lastPage > 1" class="px-8 py-8 bg-slate-50/50 dark:bg-slate-950/30 border-t border-slate-100 dark:border-slate-800/50">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
              <div class="flex items-center gap-6">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                  Resultados {{ formatNumber(paginationData.from) }} – {{ formatNumber(paginationData.to) }} de {{ formatNumber(paginationData.total) }}
                </span>
                <div class="h-4 w-px bg-slate-200 dark:bg-slate-800"></div>
                <div class="flex items-center gap-2">
                   <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Ver:</span>
                   <select
                    :value="paginationData.perPage"
                    @change="handlePerPageChange(parseInt($event.target.value))"
                    class="bg-transparent text-[10px] font-black text-slate-600 dark:text-slate-300 focus:outline-none cursor-pointer hover:text-blue-500 transition-colors"
                  >
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                  </select>
                </div>
              </div>

              <div class="flex items-center gap-2">
                <button
                  @click="handlePageChange(paginationData.currentPage - 1)"
                  :disabled="!paginationData.prevPageUrl"
                  class="p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-400 hover:text-blue-500 disabled:opacity-30 transition-all duration-300"
                >
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                </button>

                <div class="flex items-center gap-1">
                  <button
                    v-for="page in [paginationData.currentPage - 1, paginationData.currentPage, paginationData.currentPage + 1].filter(p => p > 0 && p <= paginationData.lastPage)"
                    :key="page"
                    @click="handlePageChange(page)"
                    :class="page === paginationData.currentPage ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/20' : 'bg-white dark:bg-slate-900 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800'"
                    class="w-10 h-10 rounded-xl text-xs font-black tracking-tight border border-slate-200 dark:border-slate-800 transition-all duration-300"
                  >
                    {{ page }}
                  </button>
                </div>

                <button
                  @click="handlePageChange(paginationData.currentPage + 1)"
                  :disabled="!paginationData.nextPageUrl"
                  class="p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-400 hover:text-blue-500 disabled:opacity-30 transition-all duration-300"
                >
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal con Estética Superior -->
      <transition 
        enter-active-class="duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100"
        leave-active-class="duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0"
      >
        <div v-if="showModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center z-[100] p-6" @click.self="showModal = false">
            <div class="bg-white dark:bg-slate-900 rounded-[3rem] shadow-2xl w-full max-w-2xl overflow-hidden animate-pop-in border border-slate-200/50 dark:border-slate-800/50">
              
              <div class="px-10 py-8 border-b border-slate-100 dark:border-slate-800/50 flex items-center justify-between bg-slate-50/50 dark:bg-slate-950/30">
                 <div>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter uppercase">
                        {{ modalMode === 'details' ? 'Expediente Almacén' : 'Confirmar Baja' }}
                    </h3>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Gestión Unificada del Activo</p>
                 </div>
                 <button @click="showModal = false" class="w-12 h-12 bg-white dark:bg-slate-800 rounded-2xl flex items-center justify-center text-slate-400 hover:text-rose-500 transition-colors shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                 </button>
              </div>

              <div class="p-10">
                  <div v-if="modalMode === 'details' && selectedAlmacen" class="space-y-10">
                    <div class="grid grid-cols-2 gap-10">
                        <div class="space-y-1.5">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Razón Social / Identificador</p>
                            <p class="text-sm font-black text-slate-900 dark:text-white">{{ selectedAlmacen.nombre }}</p>
                        </div>
                        <div class="space-y-1.5 text-right">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Disponibilidad</p>
                            <span :class="obtenerClasesEstado(selectedAlmacen.estado)" class="inline-flex px-4 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest animate-pulse">
                                {{ obtenerLabelEstado(selectedAlmacen.estado) }}
                            </span>
                        </div>
                        <div class="space-y-1.5">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Supervisor a Cargo</p>
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-blue-600/10 text-blue-600 flex items-center justify-center text-[10px] font-black">
                                    {{ (selectedAlmacen.responsable?.name || '?').charAt(0) }}
                                </div>
                                <p class="text-sm font-black text-slate-900 dark:text-white">{{ selectedAlmacen.responsable?.name || 'VIRTUAL / NO ASIGNADO' }}</p>
                            </div>
                        </div>
                        <div class="space-y-1.5 text-right">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Enlace de Comunicación</p>
                            <p class="text-sm font-black text-slate-900 dark:text-white tracking-widest">{{ selectedAlmacen.telefono || 'DATOS NO REGISTRADOS' }}</p>
                        </div>
                    </div>
                    
                    <div class="space-y-2 p-6 rounded-3xl bg-slate-50 dark:bg-slate-950/50 border border-slate-100 dark:border-slate-800/50">
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Ubicación Geográfica</p>
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300 leading-relaxed">{{ selectedAlmacen.direccion || 'No se cuenta con coordenadas o dirección física para este registro.' }}</p>
                    </div>

                    <div v-if="selectedAlmacen.descripcion" class="space-y-2">
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Bitácora / Especificaciones Técnicas</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 italic line-clamp-3 leading-relaxed">{{ selectedAlmacen.descripcion }}</p>
                    </div>
                  </div>

                  <div v-if="modalMode === 'confirm'" class="text-center py-6 space-y-8">
                    <div class="w-24 h-24 mx-auto bg-rose-500/10 rounded-[2.5rem] flex items-center justify-center text-rose-500 animate-bounce">
                      <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    </div>
                    <div class="space-y-2">
                        <h3 class="text-3xl font-black text-slate-900 dark:text-white tracking-tighter uppercase">¿EJECUTAR BAJA?</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto leading-relaxed font-medium">
                          Confirmas la eliminación definitiva del almacén <span class="text-rose-500 font-black">{{ selectedAlmacen?.nombre }}</span>. Estos datos serán irrecuperables.
                        </p>
                    </div>
                  </div>
              </div>

              <div class="px-10 py-8 bg-slate-50/50 dark:bg-slate-950/30 border-t border-slate-100 dark:border-slate-800/50 flex justify-end gap-4">
                  <button @click="showModal = false" class="px-8 py-3.5 bg-white dark:bg-slate-800 text-[10px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400 rounded-2xl hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors shadow-sm">
                      {{ modalMode === 'details' ? 'Finalizar Vista' : 'Abortar Operación' }}
                  </button>
                  <div v-if="modalMode === 'details'" class="flex gap-3">
                      <button @click="toggleAlmacen(selectedAlmacen.id)" class="px-8 py-3.5 bg-emerald-500 text-white text-[10px] font-black uppercase tracking-widest rounded-2xl hover:bg-emerald-600 transition-all hover:shadow-xl hover:shadow-emerald-500/30">
                         Alternar Estatus
                      </button>
                      <button @click="editarAlmacen(selectedAlmacen.id)" class="px-8 py-3.5 bg-blue-600 text-white text-[10px] font-black uppercase tracking-widest rounded-2xl hover:bg-blue-700 transition-all hover:shadow-xl hover:shadow-blue-500/30">
                         Modificar Registro
                      </button>
                  </div>
                  <button v-if="modalMode === 'confirm'" @click="eliminarAlmacen" class="px-8 py-3.5 bg-rose-600 text-white text-[10px] font-black uppercase tracking-widest rounded-2xl hover:bg-rose-700 transition-all hover:shadow-xl hover:shadow-rose-500/30">
                      Confirmar Eliminación
                  </button>
              </div>
            </div>
        </div>
      </transition>
    </div>
  </div>
</template>

<style scoped>
.almacenes-index {
  background-image: 
    radial-gradient(circle at 0% 0%, rgba(59, 130, 246, 0.03) 0%, transparent 50%),
    radial-gradient(circle at 100% 100%, rgba(16, 185, 129, 0.03) 0%, transparent 50%);
}

.control-btn {
  width: 38px;
  height: 38px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.control-btn:hover {
  transform: translateY(-4px) scale(1.1);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.animate-fade-in {
  animation: fadeIn 1s cubic-bezier(0.16, 1, 0.3, 1);
}

.animate-pop-in {
  animation: popIn 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes popIn {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}

/* Scrollbar */
::-webkit-scrollbar {
  width: 8px;
}
::-webkit-scrollbar-track {
  background: transparent;
}
::-webkit-scrollbar-thumb {
  background: rgba(148, 163, 184, 0.2);
  border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
  background: rgba(148, 163, 184, 0.4);
}
</style>
