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
    'activo': 'bg-emerald-100 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200',
    'inactivo': 'bg-rose-100 text-rose-800 dark:text-rose-200 dark:text-rose-200'
  }
  return clases[estado] || 'bg-slate-100 text-slate-300'
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
  <div class="servicios-index">
    <div class="w-full px-6 py-8">
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

      <!-- Tabla -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-800/50">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Fecha</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Servicio</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Código</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">SAT</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Esquema de Pago</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Precio</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Duración</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado</th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
              <tr v-for="servicio in serviciosDocumentos" :key="servicio.id" class="hover:bg-white dark:hover:bg-slate-700/50 transition-colors duration-150">
                <td class="px-6 py-4">
                  <div class="text-sm text-slate-900 dark:text-slate-100">{{ formatearFecha(servicio.fecha) }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ servicio.titulo }}</div>
                  <div class="text-sm text-slate-500 dark:text-slate-400">{{ servicio.subtitulo }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-slate-500 dark:text-slate-400">{{ servicio.raw.codigo || 'N/A' }}</div>
                </td>
                <td class="px-6 py-4">
                  <button
                    class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-medium"
                    :class="servicio.raw.sat_clave_prod_serv ? 'bg-brand-500/10 text-emerald-600 dark:text-slate-400' : 'bg-brand-500/10 text-rose-600 dark:text-rose-400'"
                    @click="openSatModal(servicio.raw)"
                  >
                    {{ servicio.raw.sat_clave_prod_serv || 'Sin clave' }}
                  </button>
                </td>
                <td class="px-6 py-4">
                  <span v-if="servicio.raw.tipo_comision_tecnica === 'instalacion'" class="inline-flex items-center px-2 py-0.5 rounded-xl text-xs font-medium bg-brand-500/10 text-blue-600 dark:text-blue-400">
                    Instalación ($300)
                  </span>
                  <span v-else-if="servicio.raw.tipo_comision_tecnica === 'refrigeracion'" class="inline-flex items-center px-2 py-0.5 rounded-xl text-xs font-medium bg-cyan-500/10 text-cyan-600 dark:text-cyan-400">
                    Refrigeración ($350)
                  </span>
                  <span v-else-if="servicio.raw.tipo_comision_tecnica === 'desinstalacion'" class="inline-flex items-center px-2 py-0.5 rounded-xl text-xs font-medium bg-brand-500/10 text-brand-600 dark:text-orange-400">
                    Desinstalación ($100)
                  </span>
                  <span v-else-if="servicio.raw.tipo_comision_tecnica === 'tierra'" class="inline-flex items-center px-2 py-0.5 rounded-xl text-xs font-medium bg-brand-500/10 text-brand-600 dark:text-amber-400">
                    Tierra ($100)
                  </span>
                  <span v-else class="inline-flex items-center px-2 py-0.5 rounded-xl text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-200">
                    General (30%)
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-slate-900 dark:text-slate-100 font-semibold">${{ formatNumber(servicio.raw.precio || 0) }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-slate-500 dark:text-slate-400">{{ servicio.raw.duracion || 0 }} min</div>
                </td>
                <td class="px-6 py-4">
                  <span :class="obtenerClasesEstado(servicio.estado)" class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium">
                    {{ obtenerLabelEstado(servicio.estado) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end space-x-1">
                    <button @click="verDetalles(servicio)" class="w-10 h-10 bg-sky-50 dark:bg-sky-900/20 dark:bg-sky-900/40 text-blue-600 dark:text-blue-400 rounded-xl hover:bg-sky-100 dark:hover:bg-blue-900/60 transition-colors duration-150" title="Ver detalles">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>
                    <button @click="editarServicio(servicio.id)" class="w-10 h-10 bg-brand-50 dark:bg-brand-900/20 dark:bg-brand-900/40 text-brand-600 dark:text-brand-400 rounded-xl hover:bg-brand-100 dark:hover:bg-brand-900/60 transition-colors duration-150" title="Editar">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button v-if="servicio.raw.can_delete" @click="confirmarEliminacion(servicio.id)" class="w-10 h-10 bg-rose-50 dark:bg-rose-900/20 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400 rounded-xl hover:bg-rose-100 dark:hover:bg-rose-900/60 transition-colors duration-150" title="Eliminar">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="serviciosDocumentos.length === 0">
                <td colspan="9" class="px-6 py-16 text-center">
                  <div class="flex flex-col items-center space-y-6">
                    <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center">
                      <svg class="w-10 h-10 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                    </div>
                    <div class="space-y-1">
                      <p class="text-slate-700 dark:text-slate-200 font-medium">No hay servicios</p>
                      <p class="text-sm text-slate-500 dark:text-slate-400">Los servicios aparecerán aquí cuando se creen</p>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div v-if="paginationData.lastPage > 1" class="bg-slate-900 border-t border-slate-800 px-4 py-3 sm:px-6">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4">
              <p class="text-sm text-slate-400">
                Mostrando {{ paginationData.from }} - {{ paginationData.to }} de {{ paginationData.total }} resultados
              </p>
              <select
                :value="paginationData.perPage"
                @change="handlePerPageChange(parseInt($event.target.value))"
                class="border border-slate-700 rounded-xl text-sm py-1 px-2 bg-slate-800 text-slate-200"
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
                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-slate-700 bg-slate-800 text-sm font-medium text-slate-400 hover:bg-slate-700"
              >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </button>

              <span v-else class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-slate-700 bg-slate-900 text-sm font-medium text-slate-500">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </span>

              <button
                v-for="page in [paginationData.currentPage - 1, paginationData.currentPage, paginationData.currentPage + 1].filter(p => p > 0 && p <= paginationData.lastPage)"
                :key="page"
                @click="handlePageChange(page)"
                :class="page === paginationData.currentPage ? 'bg-brand-500/20 border-blue-500 text-blue-400' : 'bg-slate-800 border-slate-700 text-slate-400 hover:bg-slate-700'"
                class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
              >
                {{ page }}
              </button>

              <button
                v-if="paginationData.nextPageUrl"
                @click="handlePageChange(paginationData.currentPage + 1)"
                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-slate-700 bg-slate-800 text-sm font-medium text-slate-400 hover:bg-slate-700"
              >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
              </button>

              <span v-else class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-slate-700 bg-slate-900 text-sm font-medium text-slate-500">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
              </span>
            </nav>
          </div>
        </div>
      </div>

      <!-- Modal SAT -->
      <div v-if="showSatModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4" @click.self="closeSatModal">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-xl overflow-hidden border border-slate-200 dark:border-slate-800 transition-all">
          <div class="flex items-center justify-between p-6 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100">
              Clave SAT del servicio: {{ satTarget?.nombre || '' }}
            </h3>
            <button @click="closeSatModal" class="text-slate-400 hover:text-brand-600 dark:hover:text-slate-200 transition-colors">
              <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="p-6 space-y-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">
              Se usa el nombre para sugerir la primera coincidencia del catalogo SAT.
            </p>
            <div v-if="satSuggesting" class="text-xs text-slate-500">Buscando sugerencia...</div>
            <SatClaveProdServSearch
              v-model="satForm.sat_clave_prod_serv"
              :initial-description="satClaveDescription"
            />
            <div v-if="satSuggestion" class="text-xs text-slate-500">
              Sugerencia aplicada: {{ satSuggestion.clave }} - {{ satSuggestion.descripcion }}
            </div>
          </div>
          <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-[var(--ui-surface)] dark:bg-black/50">
            <button @click="closeSatModal" class="px-4 py-2 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-xl hover:bg-slate-300 dark:hover:bg-slate-700 transition-colors">
              Cancelar
            </button>
            <button @click="saveSatClave" :disabled="satSaving" class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors disabled:opacity-50">
              {{ satSaving ? 'Guardando...' : 'Guardar' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Modal mejorado -->
      <div v-if="showModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4" @click.self="showModal = false">
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto custom-scrollbar">
          <!-- Header del modal -->
          <div class="flex items-center justify-between p-6 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100">
              {{ modalMode === 'details' ? 'Detalles del Servicio' : 'Confirmar Eliminación' }}
            </h3>
            <button @click="showModal = false" class="text-slate-400 hover:text-brand-600 dark:hover:text-slate-200 transition-colors">
              <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="p-6">
            <div v-if="modalMode === 'details' && selectedServicio">
              <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="space-y-3">
                    <div>
                      <label class="block text-sm font-semibold text-slate-500 dark:text-slate-400">Nombre</label>
                      <p class="mt-1 text-sm text-slate-800 dark:text-slate-100 bg-[var(--ui-surface)] dark:bg-slate-800/50 px-3 py-2 rounded-xl border border-slate-100 dark:border-slate-800 font-medium">{{ selectedServicio.nombre }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-semibold text-slate-500 dark:text-slate-400">Código</label>
                      <p class="mt-1 text-sm text-slate-800 dark:text-slate-100 bg-[var(--ui-surface)] dark:bg-slate-800/50 px-3 py-2 rounded-xl border border-slate-100 dark:border-slate-800 font-medium">{{ selectedServicio.codigo || 'N/A' }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-semibold text-slate-500 dark:text-slate-400">Precio</label>
                      <p class="mt-1 text-sm text-slate-800 dark:text-slate-100 bg-[var(--ui-surface)] dark:bg-slate-800/50 px-3 py-2 rounded-xl border border-slate-100 dark:border-slate-800 font-bold">${{ formatNumber(selectedServicio.precio || 0) }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-semibold text-slate-500 dark:text-slate-400">Duración</label>
                      <p class="mt-1 text-sm text-slate-800 dark:text-slate-100 bg-[var(--ui-surface)] dark:bg-slate-800/50 px-3 py-2 rounded-xl border border-slate-100 dark:border-slate-800 font-medium">{{ selectedServicio.duracion || 0 }} minutos</p>
                    </div>
                    <div>
                      <label class="block text-sm font-semibold text-slate-500 dark:text-slate-400">Estado</label>
                      <span :class="obtenerClasesEstado(selectedServicio.estado)" class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-medium mt-2">
                        {{ obtenerLabelEstado(selectedServicio.estado) }}
                      </span>
                    </div>
                    <div class="mt-2">
                       <label class="block text-sm font-semibold text-slate-500 dark:text-slate-400">Categoría Técnica</label>
                       <p class="mt-1 text-sm text-slate-800 dark:text-slate-100 font-bold uppercase tracking-wider">{{ selectedServicio.tipo_comision_tecnica?.toUpperCase() || 'GENERAL' }}</p>
                    </div>
                  </div>
                  <div class="space-y-3">
                    <div>
                      <label class="block text-sm font-semibold text-slate-500 dark:text-slate-400">Categoría</label>
                      <p class="mt-1 text-sm text-slate-800 dark:text-slate-100 bg-[var(--ui-surface)] dark:bg-slate-800/50 px-3 py-2 rounded-xl border border-slate-100 dark:border-slate-800 font-medium">{{ selectedServicio.categoria?.nombre || 'N/A' }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-semibold text-slate-500 dark:text-slate-400">Fecha de Creación</label>
                      <p class="mt-1 text-sm text-slate-800 dark:text-slate-100 bg-[var(--ui-surface)] dark:bg-slate-800/50 px-3 py-2 rounded-xl border border-slate-100 dark:border-slate-800 font-medium">{{ formatearFecha(selectedServicio.created_at) }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-semibold text-slate-500 dark:text-slate-400">Última Actualización</label>
                      <p class="mt-1 text-sm text-slate-800 dark:text-slate-100 bg-[var(--ui-surface)] dark:bg-slate-800/50 px-3 py-2 rounded-xl border border-slate-100 dark:border-slate-800 font-medium">{{ formatearFecha(selectedServicio.updated_at) }}</p>
                    </div>
                  </div>
                </div>
                <div v-if="selectedServicio.descripcion">
                  <label class="block text-sm font-semibold text-slate-500 dark:text-slate-400">Descripción</label>
                  <p class="mt-1 text-sm text-slate-800 dark:text-slate-100 bg-[var(--ui-surface)] dark:bg-slate-800/50 px-3 py-2 rounded-xl border border-slate-100 dark:border-slate-800 whitespace-pre-wrap font-medium">{{ selectedServicio.descripcion }}</p>
                </div>
              </div>
            </div>

            <div v-if="modalMode === 'confirm'">
              <div class="text-center">
                <div class="w-10 h-10 mx-auto bg-brand-500/10 rounded-full flex items-center justify-center mb-4">
                  <svg class="w-10 h-10 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                  </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-2">¿Eliminar Servicio?</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                  ¿Estás seguro de que deseas eliminar el servicio <strong class="text-slate-800 dark:text-slate-200">{{ selectedServicio?.nombre }}</strong>?
                  Esta acción no se puede deshacer.
                </p>
              </div>
            </div>
          </div>

          <!-- Footer del modal -->
          <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-[var(--ui-surface)] dark:bg-black/50">
            <button @click="showModal = false" class="px-4 py-2 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-xl hover:bg-slate-300 dark:hover:bg-slate-700 transition-colors">
              {{ modalMode === 'details' ? 'Cerrar' : 'Cancelar' }}
            </button>
            <div v-if="modalMode === 'details'" class="flex gap-2">
              <button @click="toggleServicio(selectedServicio.id)" class="px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition-colors">
                Cambiar Estado
              </button>
              <button @click="editarServicio(selectedServicio.id)" class="px-4 py-2 bg-brand-600 text-white rounded-xl hover:bg-brand-700 transition-colors">
                Editar
              </button>
            </div>
            <button v-if="modalMode === 'confirm'" @click="eliminarServicio" class="px-4 py-2 bg-rose-600 text-white rounded-xl hover:bg-rose-700 transition-colors">
              Eliminar
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
