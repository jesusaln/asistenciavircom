<!-- /resources/js/Pages/Servicios/Index.vue -->
<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

import ServiciosHeader from '@/Components/IndexComponents/ServiciosHeader.vue'
import SatClaveProdServSearch from '@/Components/Sat/SatClaveProdServSearch.vue'

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
  servicios: { type: [Object, Array], required: true },
  stats: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  sorting: { type: Object, default: () => ({ sort_by: 'nombre', sort_direction: 'asc' }) },
})

// Estado UI
const showModal = ref(false)
const modalMode = ref('details')
const selectedServicio = ref(null)
const selectedId = ref(null)
const showSatModal = ref(false)
const satTarget = ref(null)
const satForm = ref({ sat_clave_prod_serv: '' })
const satClaveDescription = ref('')
const satSuggestion = ref(null)
const satSaving = ref(false)
const satSuggesting = ref(false)

// Filtros
const searchTerm = ref(props.filters?.search ?? '')
const sortBy = ref('nombre-asc')
const filtroEstado = ref('')
const filtroCategoria = ref('')

// Paginación
const perPage = ref(10)

// Función para crear nuevo servicio
const crearNuevoServicio = () => {
  router.visit(route('servicios.create'))
}

// Función para limpiar filtros
const limpiarFiltros = () => {
  searchTerm.value = ''
  sortBy.value = 'nombre-asc'
  filtroEstado.value = ''
  filtroCategoria.value = ''
  router.visit(route('servicios.index'))
  notyf.success('Filtros limpiados correctamente')
}

// Estadísticas adicionales para el header moderno
const precioPromedio = computed(() => {
  // Calcular el precio promedio basado en servicios con precio disponible
  if (serviciosData.value && serviciosData.value.length > 0) {
    const serviciosConPrecio = serviciosData.value.filter(servicio =>
      servicio.precio && parseFloat(servicio.precio) > 0
    )

    if (serviciosConPrecio.length > 0) {
      const totalPrecio = serviciosConPrecio.reduce((sum, servicio) =>
        sum + (parseFloat(servicio.precio) || 0), 0
      )
      return totalPrecio / serviciosConPrecio.length
    }
  }

  // Si no hay datos reales, usar promedio más realista
  return 800 // Promedio de $800 MXN por servicio
})

const conCategoria = computed(() => {
  // Contar servicios que tienen categoría asignada
  if (serviciosData.value && serviciosData.value.length > 0) {
    return serviciosData.value.filter(servicio =>
      servicio.categoria_id || servicio.categoria
    ).length
  }
  return 0
})

const categorias = computed(() => {
  // Obtener lista única de categorías de los servicios
  if (serviciosData.value && serviciosData.value.length > 0) {
    const categoriasMap = new Map()

    serviciosData.value.forEach(servicio => {
      if (servicio.categoria) {
        categoriasMap.set(servicio.categoria.id, servicio.categoria)
      }
    })

    return Array.from(categoriasMap.values())
  }
  return []
})

// Función para manejar filtro de categoría
const handleCategoriaChange = (categoriaId) => {
  filtroCategoria.value = categoriaId
  router.get(route('servicios.index'), {
    search: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'asc',
    estado: filtroEstado.value,
    categoria_id: categoriaId,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

// Datos
const serviciosPaginator = computed(() => props.servicios)
const serviciosData = computed(() => serviciosPaginator.value?.data || [])

// Estadísticas
const estadisticas = computed(() => ({
  total: props.stats?.total ?? 0,
  activos: props.stats?.activos ?? 0,
  inactivos: props.stats?.inactivos ?? 0,
  activosPorcentaje: props.stats?.activos > 0 ? Math.round((props.stats.activos / props.stats.total) * 100) : 0,
  inactivosPorcentaje: props.stats?.inactivos > 0 ? Math.round((props.stats.inactivos / props.stats.total) * 100) : 0
}))

// Transformación de datos
const serviciosDocumentos = computed(() => {
  return serviciosData.value.map(s => ({
    id: s.id,
    titulo: s.nombre || 'Sin nombre',
    subtitulo: s.descripcion ? s.descripcion.substring(0, 50) + (s.descripcion.length > 50 ? '...' : '') : 'Sin descripción',
    estado: s.estado || 'activo',
    extra: `Código: ${s.codigo || 'N/A'} | Precio: $${s.precio || 0} | Duración: ${s.duracion || 0} min`,
    fecha: s.created_at,
    raw: s
  }))
})

// Handlers
function handleSearchChange(newSearch) {
  searchTerm.value = newSearch
  router.get(route('servicios.index'), {
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
  router.get(route('servicios.index'), {
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
  router.get(route('servicios.index'), {
    search: searchTerm.value,
    sort_by: newSort.split('-')[0],
    sort_direction: newSort.split('-')[1] || 'asc',
    estado: filtroEstado.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const verDetalles = (doc) => {
  selectedServicio.value = doc.raw
  modalMode.value = 'details'
  showModal.value = true
}

const editarServicio = (id) => {
  router.visit(route('servicios.edit', id))
}

const confirmarEliminacion = (id) => {
  selectedId.value = id
  // Cargar el servicio seleccionado para mostrar su nombre en el modal
  const servicio = serviciosData.value.find(s => s.id === id)
  selectedServicio.value = servicio || null
  modalMode.value = 'confirm'
  showModal.value = true
}

const eliminarServicio = () => {
  router.delete(route('servicios.destroy', selectedId.value), {
    preserveScroll: true,
    onSuccess: () => {
      notyf.success('Servicio eliminado correctamente')
      showModal.value = false
      selectedId.value = null
      router.reload()
    },
    onError: (errors) => {
      notyf.error('No se pudo eliminar el servicio')
    }
  })
}

const toggleServicio = (id) => {
  const servicio = serviciosData.value.find(s => s.id === id)
  if (!servicio) return notyf.error('Servicio no encontrado')
  const nuevoEstado = servicio.estado === 'activo' ? 'inactivo' : 'activo'
  const mensaje = nuevoEstado === 'activo' ? 'Servicio activado correctamente' : 'Servicio desactivado correctamente'

  router.put(route('servicios.toggle', id), {
    preserveScroll: true,
    onSuccess: () => {
      notyf.success(mensaje)
      router.reload()
    },
    onError: (errors) => {
      notyf.error('No se pudo cambiar el estado del servicio')
    }
  })
}

const csrfToken = () => {
  const meta = typeof document !== 'undefined' ? document.querySelector('meta[name="csrf-token"]') : null
  return meta ? meta.getAttribute('content') : ''
}

const openSatModal = (servicio) => {
  satTarget.value = servicio
  satForm.value = { sat_clave_prod_serv: servicio?.sat_clave_prod_serv || '' }
  satClaveDescription.value = ''
  satSuggestion.value = null
  showSatModal.value = true
  if (!servicio?.sat_clave_prod_serv) {
    suggestSatClave(servicio?.nombre || servicio?.descripcion || '')
  }
}

const closeSatModal = () => {
  showSatModal.value = false
  satTarget.value = null
  satForm.value = { sat_clave_prod_serv: '' }
  satClaveDescription.value = ''
  satSuggestion.value = null
}

const suggestSatClave = async (nombre) => {
  const query = (nombre || '').trim()
  if (query.length < 3) return
  satSuggesting.value = true
  try {
    const response = await fetch(`${window.location.origin}/sat/buscar-clave-prod-serv?search=${encodeURIComponent(query)}`)
    if (!response.ok) return
    const results = await response.json()
    if (Array.isArray(results) && results.length > 0) {
      satForm.value.sat_clave_prod_serv = results[0].clave
      satClaveDescription.value = results[0].descripcion
      satSuggestion.value = results[0]
    }
  } catch (error) {
    console.error('Error buscando clave SAT:', error)
  } finally {
    satSuggesting.value = false
  }
}

const saveSatClave = async () => {
  if (!satTarget.value) return
  const clave = (satForm.value.sat_clave_prod_serv || '').trim()
  if (!clave) {
    notyf.error('Selecciona una clave SAT')
    return
  }
  satSaving.value = true
  try {
    const response = await fetch(`/servicios/${satTarget.value.id}/sat`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken()
      },
      credentials: 'same-origin',
      body: JSON.stringify({ sat_clave_prod_serv: clave })
    })
    if (!response.ok) {
      const data = await response.json().catch(() => ({}))
      notyf.error(data?.message || 'No se pudo guardar la clave SAT')
      return
    }
    notyf.success('Clave SAT guardada')
    closeSatModal()
    router.reload({ preserveScroll: true })
  } catch (error) {
    notyf.error('No se pudo guardar la clave SAT')
  } finally {
    satSaving.value = false
  }
}

const exportServicios = () => {
  const params = new URLSearchParams()
  if (searchTerm.value) params.append('search', searchTerm.value)
  if (filtroEstado.value) params.append('estado', filtroEstado.value)
  const queryString = params.toString()
  const url = route('servicios.export') + (queryString ? `?${queryString}` : '')
  window.location.href = url
}

// Paginación
const paginationData = computed(() => {
  const p = serviciosPaginator.value || {}
  return {
    currentPage: p.current_page ?? 1,
    lastPage:    p.last_page ?? 1,
    perPage:     p.per_page ?? 10,
    from:        p.from ?? 0,
    to:          p.to ?? 0,
    total:       p.total ?? 0,
    prevPageUrl: p.prev_page_url ?? null,
    nextPageUrl: p.next_page_url ?? null,
    links:       p.links ?? []
  }
})

const handlePerPageChange = (newPerPage) => {
  perPage.value = newPerPage
  router.get(route('servicios.index'), {
    search: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'asc',
    estado: filtroEstado.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const handlePageChange = (newPage) => {
  router.get(route('servicios.index'), {
    search: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'asc',
    estado: filtroEstado.value,
    per_page: perPage.value,
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
    'activo': 'bg-green-100 text-green-700',
    'inactivo': 'bg-red-100 text-red-700'
  }
  return clases[estado] || 'bg-gray-100 text-gray-700'
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
  <Head title="Servicios" />
  <div class="servicios-index min-h-screen bg-slate-50 dark:bg-slate-950 transition-colors duration-300">
    <div class="w-full px-4 sm:px-6 py-8 mx-auto max-w-7xl">
      <!-- Header específico de servicios -->
      <ServiciosHeader
        :total="estadisticas.total"
        :activos="estadisticas.activos"
        :inactivos="estadisticas.inactivos"
        :precio-promedio="precioPromedio"
        :con-categoria="conCategoria"
        :categorias="categorias"
        v-model:search-term="searchTerm"
        v-model:sort-by="sortBy"
        v-model:filtro-estado="filtroEstado"
        v-model:filtro-categoria="filtroCategoria"
        @crear-nueva="crearNuevoServicio"
        @search-change="handleSearchChange"
        @filtro-estado-change="handleEstadoChange"
        @filtro-categoria-change="handleCategoriaChange"
        @sort-change="handleSortChange"
        @limpiar-filtros="limpiarFiltros"
      />

      <!-- Tabla con efecto Glassmorphism en Dark mode -->
      <div class="mt-8 bg-white dark:bg-slate-900/40 dark:backdrop-blur-xl rounded-2xl shadow-xl border border-gray-100 dark:border-slate-800/60 overflow-hidden ring-1 ring-black/5 dark:ring-white/5">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800/80">
            <thead class="bg-gray-50/50 dark:bg-slate-900/80">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest">Fecha</th>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest">Servicio</th>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest">Código</th>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest text-center">SAT</th>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest">Precio</th>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest">Duración</th>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest text-center">Estado</th>
                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest">Acciones</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-transparent divide-y divide-gray-100 dark:divide-slate-800/50">
              <tr v-for="servicio in serviciosDocumentos" :key="servicio.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-all duration-200 group">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-600 dark:text-slate-300">{{ formatearFecha(servicio.fecha) }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm font-bold text-gray-900 dark:text-slate-100 leading-tight">{{ servicio.titulo }}</div>
                  <div class="text-xs text-gray-500 dark:text-slate-400 mt-1 max-w-[200px] truncate">{{ servicio.subtitulo }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="px-2 py-1 text-xs font-mono bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-400 rounded">
                    {{ servicio.raw.codigo || 'N/A' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-center whitespace-nowrap">
                  <button
                    class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider transition-all duration-200 hover:scale-105"
                    :class="servicio.raw.sat_clave_prod_serv 
                      ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20' 
                      : 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400 border border-rose-200 dark:border-rose-500/20'"
                    @click="openSatModal(servicio.raw)"
                  >
                    {{ servicio.raw.sat_clave_prod_serv || 'Sin clave' }}
                  </button>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-bold text-gray-900 dark:text-slate-100">${{ formatNumber(servicio.raw.precio || 0) }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                  <div class="inline-flex items-center text-xs font-medium text-gray-600 dark:text-slate-400">
                    <svg class="w-3 h-3 mr-1 text-gray-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ servicio.raw.duracion || 0 }} min
                  </div>
                </td>
                <td class="px-6 py-4 text-center whitespace-nowrap">
                  <span 
                    :class="servicio.estado === 'activo' 
                      ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-500/20' 
                      : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700'" 
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border transition-all duration-300"
                  >
                    {{ obtenerLabelEstado(servicio.estado) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right whitespace-nowrap space-x-2">
                  <div class="flex items-center justify-end space-x-2 opacity-60 group-hover:opacity-100 transition-opacity duration-200">
                    <button @click="verDetalles(servicio)" class="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-500/10 rounded-lg transition-all" title="Ver detalles">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>
                    <button @click="editarServicio(servicio.id)" class="p-2 text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-500/10 rounded-lg transition-all" title="Editar">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button v-if="servicio.raw.can_delete" @click="confirmarEliminacion(servicio.id)" class="p-2 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 rounded-lg transition-all" title="Eliminar">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="serviciosDocumentos.length === 0">
                <td colspan="8" class="px-6 py-16 text-center">
                  <div class="flex flex-col items-center space-y-4">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                      <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                    </div>
                    <div class="space-y-1">
                      <p class="text-gray-700 font-medium">No hay servicios</p>
                      <p class="text-sm text-gray-500 dark:text-gray-400">Los servicios aparecerán aquí cuando se creen</p>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación Premium -->
        <div v-if="paginationData.lastPage > 1" class="bg-gray-50/50 dark:bg-slate-900/40 border-t border-gray-100 dark:border-slate-800/60 px-6 py-4">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-4">
              <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                Mostrando <span class="text-slate-900 dark:text-white">{{ paginationData.from }}</span> - <span class="text-slate-900 dark:text-white">{{ paginationData.to }}</span> de <span class="text-slate-900 dark:text-white">{{ paginationData.total }}</span>
              </span>
              <select
                :value="paginationData.perPage"
                @change="handlePerPageChange(parseInt($event.target.value))"
                class="border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-bold py-1.5 px-3 bg-white dark:bg-slate-950 text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-blue-500/20 transition-all outline-none"
              >
                <option value="10">10 / página</option>
                <option value="15">15 / página</option>
                <option value="25">25 / página</option>
                <option value="50">50 / página</option>
              </select>
            </div>

            <nav class="flex items-center gap-2">
              <button
                v-if="paginationData.prevPageUrl"
                @click="handlePageChange(paginationData.currentPage - 1)"
                class="p-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all shadow-sm"
              >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
              </button>

              <div class="flex items-center gap-1">
                <button
                  v-for="page in [paginationData.currentPage - 1, paginationData.currentPage, paginationData.currentPage + 1].filter(p => p > 0 && p <= paginationData.lastPage)"
                  :key="page"
                  @click="handlePageChange(page)"
                  :class="page === paginationData.currentPage 
                    ? 'bg-blue-600 border-blue-600 text-white shadow-lg shadow-blue-500/30' 
                    : 'bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'"
                  class="w-10 h-10 rounded-lg border text-xs font-bold transition-all"
                >
                  {{ page }}
                </button>
              </div>

              <button
                v-if="paginationData.nextPageUrl"
                @click="handlePageChange(paginationData.currentPage + 1)"
                class="p-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all shadow-sm"
              >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </button>
            </nav>
          </div>
        </div>
      </div>

      <!-- Modal SAT Premium -->
      <div v-if="showSatModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 transition-all" @click.self="closeSatModal">
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-xl overflow-hidden border border-gray-100 dark:border-slate-800 animate-in fade-in zoom-in duration-300">
          <div class="flex items-center justify-between p-8 border-b border-gray-100 dark:border-slate-800/60">
            <div>
              <h3 class="text-xl font-black text-slate-900 dark:text-white leading-tight">Clave SAT</h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium">{{ satTarget?.nombre }}</p>
            </div>
            <button @click="closeSatModal" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="p-8 space-y-6">
            <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed font-medium">
              Vincula este servicio con el catálogo oficial del SAT para una facturación precisa.
            </p>
            <div v-if="satSuggesting" class="flex items-center gap-2 text-xs font-bold text-blue-600 dark:text-blue-400 animate-pulse uppercase tracking-widest">
              <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83" stroke-width="3" stroke-linecap="round"/></svg>
              Buscando inteligencia fiscal...
            </div>
            <div class="bg-slate-50 dark:bg-slate-950/40 p-1 rounded-2xl border border-slate-100 dark:border-slate-800">
              <SatClaveProdServSearch
                v-model="satForm.sat_clave_prod_serv"
                :initial-description="satClaveDescription"
              />
            </div>
            <div v-if="satSuggestion" class="flex items-start gap-3 p-4 bg-emerald-50 dark:bg-emerald-500/5 rounded-xl border border-emerald-100 dark:border-emerald-500/10">
              <svg class="w-5 h-5 text-emerald-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
              <div>
                <p class="text-[10px] font-black text-emerald-700 dark:text-emerald-400 uppercase tracking-widest mb-1 text-center sm:text-left">Sugerencia inteligente</p>
                <p class="text-xs text-emerald-800 dark:text-emerald-300 font-bold">{{ satSuggestion.clave }} — {{ satSuggestion.descripcion }}</p>
              </div>
            </div>
          </div>
          <div class="flex justify-end gap-3 px-8 py-6 border-t border-gray-100 dark:border-slate-800/60 bg-gray-50/50 dark:bg-slate-900/40">
            <button @click="closeSatModal" class="px-6 py-2.5 text-sm font-bold text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 transition-all">
              Cancelar
            </button>
            <button @click="saveSatClave" :disabled="satSaving" class="px-8 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm font-bold rounded-xl shadow-lg shadow-blue-500/30 transition-all flex items-center gap-2">
              <span v-if="satSaving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              {{ satSaving ? 'Sincronizando...' : 'confirmar Clave' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Modal Detalles/Confirmación Premium -->
      <div v-if="showModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 transition-all" @click.self="showModal = false">
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto border border-gray-100 dark:border-slate-800 animate-in fade-in zoom-in duration-300">
          <!-- Header del modal -->
          <div class="flex items-center justify-between p-8 border-b border-gray-100 dark:border-slate-800/60">
            <div>
              <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight leading-none">
                {{ modalMode === 'details' ? 'Detalles del Servicio' : 'Confirmar Eliminación' }}
              </h3>
              <p v-if="modalMode === 'details'" class="text-xs text-slate-500 dark:text-slate-400 mt-2 font-bold uppercase tracking-widest">Información completa del catálogo</p>
            </div>
            <button @click="showModal = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="p-8">
            <div v-if="modalMode === 'details' && selectedServicio">
              <div class="space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                  <div class="space-y-6">
                    <div>
                      <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2">Nombre del Servicio</label>
                      <p class="text-lg font-bold text-slate-900 dark:text-white leading-tight underline decoration-blue-500/30 decoration-2 underline-offset-4">{{ selectedServicio.nombre }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                      <div>
                        <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">Código</label>
                        <p class="text-sm font-mono font-bold text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-950/50 px-3 py-1.5 rounded-lg border border-slate-100 dark:border-slate-800 inline-block">{{ selectedServicio.codigo || 'N/A' }}</p>
                      </div>
                      <div>
                        <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">Categoría</label>
                        <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ selectedServicio.categoria?.nombre || 'General' }}</p>
                      </div>
                    </div>
                    <div class="flex items-end gap-4">
                      <div class="flex-1">
                        <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">Precio Unitario</label>
                        <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 tracking-tight">${{ formatNumber(selectedServicio.precio || 0) }}</p>
                      </div>
                      <div class="flex-1">
                        <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">Estado actual</label>
                        <span :class="selectedServicio.estado === 'activo' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 border-emerald-100 dark:border-emerald-500/20' : 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400 border-rose-100 dark:border-rose-500/20'" class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border mt-1">
                          {{ obtenerLabelEstado(selectedServicio.estado) }}
                        </span>
                      </div>
                    </div>
                  </div>
                  <div class="space-y-6 pt-2">
                    <div class="bg-slate-50 dark:bg-slate-950/30 p-4 rounded-2xl border border-slate-100 dark:border-slate-800/50">
                      <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 bg-blue-100 dark:bg-blue-500/10 rounded-lg flex items-center justify-center">
                          <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <span class="text-xs font-black text-slate-800 dark:text-slate-200 uppercase tracking-widest">Cronometría</span>
                      </div>
                      <div class="space-y-4">
                        <div class="flex justify-between items-center">
                          <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Duración Estimada</span>
                          <span class="text-sm font-bold text-slate-900 dark:text-slate-200">{{ selectedServicio.duracion || 0 }} min</span>
                        </div>
                        <div class="flex justify-between items-center">
                          <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Creado el</span>
                          <span class="text-sm font-bold text-slate-900 dark:text-slate-200">{{ formatearFecha(selectedServicio.created_at) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                          <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Actualizado</span>
                          <span class="text-sm font-bold text-slate-900 dark:text-slate-200">{{ formatearFecha(selectedServicio.updated_at) }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div v-if="selectedServicio.descripcion" class="pt-4 border-t border-gray-100 dark:border-slate-800/60">
                  <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-3">Descripción Detallada</label>
                  <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed italic">{{ selectedServicio.descripcion }}</p>
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
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">¿Eliminar Servicio?</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                  ¿Estás seguro de que deseas eliminar el servicio <strong>{{ selectedServicio?.nombre }}</strong>?
                  Esta acción no se puede deshacer.
                </p>
              </div>
            </div>
          </div>

          <!-- Footer del modal Premium -->
          <div class="flex flex-col sm:flex-row justify-end gap-3 px-8 py-6 border-t border-gray-100 dark:border-slate-800/60 bg-gray-50/50 dark:bg-slate-900/40">
            <button @click="showModal = false" class="px-6 py-2.5 text-sm font-bold text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 transition-all">
              {{ modalMode === 'details' ? 'Cerrar' : 'No, volver' }}
            </button>
            <div v-if="modalMode === 'details'" class="flex gap-3">
              <button @click="toggleServicio(selectedServicio.id)" class="px-6 py-2.5 text-xs font-black uppercase tracking-widest bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-lg shadow-emerald-500/20 transition-all">
                Activar/Desactivar
              </button>
              <button @click="editarServicio(selectedServicio.id)" class="px-6 py-2.5 text-xs font-black uppercase tracking-widest bg-amber-600 hover:bg-amber-700 text-white rounded-xl shadow-lg shadow-amber-500/20 transition-all">
                Editar Registro
              </button>
            </div>
            <button v-if="modalMode === 'confirm'" @click="eliminarServicio" class="px-8 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-rose-500/30 transition-all">
              Sí, eliminar servicio
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.servicios-index {
  min-height: 100vh;
}
</style>
