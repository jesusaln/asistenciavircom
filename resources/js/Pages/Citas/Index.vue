<!-- /resources/js/Pages/Citas/IndexNew.vue -->
<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

import CitasHeader from '@/Components/IndexComponents/CitasHeader.vue'

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
  citas: { type: [Object, Array], required: true },
  stats: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  sorting: { type: Object, default: () => ({ sort_by: 'created_at', sort_direction: 'desc' }) },
  pagination: { type: Object, default: () => ({}) },
})

// Estado UI
const showModal = ref(false)
const modalMode = ref('details')
const selectedCita = ref(null)
const selectedId = ref(null)
const viewMode = ref('table') // 'table' or 'calendar'
const currentMonth = ref(new Date())

// Galería de imágenes
const showGalleryModal = ref(false)
const galleryImages = ref([])
const currentImageIndex = ref(0)
const imageTitle = ref('')

const openGallery = (images, title = 'Galería') => {
  if (!images || images.length === 0) return
  galleryImages.value = images.map(img => img.startsWith('/') ? img : '/storage/' + img)
  currentImageIndex.value = 0
  imageTitle.value = title
  showGalleryModal.value = true
}

const closeGallery = () => {
  showGalleryModal.value = false
  galleryImages.value = []
}

const nextImage = () => {
  currentImageIndex.value = (currentImageIndex.value + 1) % galleryImages.value.length
}

const prevImage = () => {
  currentImageIndex.value = (currentImageIndex.value - 1 + galleryImages.value.length) % galleryImages.value.length
}

// Navegación con teclado para la galería
const handleKeydown = (e) => {
  if (!showGalleryModal.value) return
  if (e.key === 'Escape') closeGallery()
  if (e.key === 'ArrowRight') nextImage()
  if (e.key === 'ArrowLeft') prevImage()
}

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
})

// Filtros
const searchTerm = ref(props.filters?.search ?? '')
const sortBy = ref('created_at-desc')
const filtroEstadoCita = ref('')

// Paginación
const perPage = ref(10)

// Función para crear nueva cita
const crearNuevaCita = () => {
  router.visit(route('citas.create'))
}

// Función para limpiar filtros
const limpiarFiltros = () => {
  searchTerm.value = ''
  sortBy.value = 'created_at-desc'
  filtroEstadoCita.value = ''
  router.visit(route('citas.index'))
  notyf.success('Filtros limpiados correctamente')
}

// Datos
const citasPaginator = computed(() => props.citas)
const citasData = computed(() => citasPaginator.value?.data || [])


// Estadísticas
const estadisticas = computed(() => ({
  total: props.stats?.total ?? 0,
  pendientes: props.stats?.pendientes ?? 0,
  enProceso: props.stats?.en_proceso ?? 0,
  completadas: props.stats?.completadas ?? 0,
  canceladas: props.stats?.canceladas ?? 0
}))

// Transformación de datos
const citasDocumentos = computed(() => {
  let citas = [...citasData.value];

  // Ordenar por estado: En proceso -> Programado -> Pendientes -> Reprogramado -> Completadas -> Canceladas
  const ordenEstados = {
    'en_proceso': 1,
    'programado': 2,
    'pendiente': 3,
    'reprogramado': 4,
    'completado': 5,
    'cancelado': 6
  };

  citas.sort((a, b) => {
    const estadoA = ordenEstados[a.estado] || 999;
    const estadoB = ordenEstados[b.estado] || 999;

    if (estadoA !== estadoB) {
      return estadoA - estadoB;
    }

    // Si tienen el mismo estado, ordenar por fecha_hora
    const fechaA = new Date(a.fecha_hora);
    const fechaB = new Date(b.fecha_hora);
    return fechaA - fechaB;
  });

  return citas.map(c => {
    return {
      id: c.id,
      titulo: `Cita #${c.id}`,
      subtitulo: c.cliente?.nombre_razon_social || 'Cliente no disponible',
      estado: c.activo ? 'activo' : 'inactivo',
      extra: `Técnico: ${c.tecnico?.name || 'N/A'} | Estado: ${c.estado}`,
      fecha: c.created_at,
      raw: c
    }
  })
})

// Handlers
function handleSearchChange(newSearch) {
  searchTerm.value = newSearch
  router.get(route('citas.index'), {
    search: newSearch,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    estado: filtroEstadoCita.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

function handleEstadoCitaChange(newEstadoCita) {
  filtroEstadoCita.value = newEstadoCita
  router.get(route('citas.index'), {
    search: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    estado: newEstadoCita,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

function handleSortChange(newSort) {
  sortBy.value = newSort
  router.get(route('citas.index'), {
    search: searchTerm.value,
    sort_by: newSort.split('-')[0],
    sort_direction: newSort.split('-')[1] || 'desc',
    estado: filtroEstadoCita.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const verDetalles = (doc) => {
  selectedCita.value = doc.raw
  modalMode.value = 'details'
  showModal.value = true
}

const editarCita = (id) => {
  router.visit(route('citas.edit', id))
}

const confirmarEliminacion = (id) => {
  selectedId.value = id
  modalMode.value = 'confirm'
  showModal.value = true
}

const eliminarCita = () => {
  router.delete(route('citas.destroy', selectedId.value), {
    preserveScroll: true,
    onSuccess: () => {
      notyf.success('Cita eliminada correctamente')
      showModal.value = false
      selectedId.value = null
      router.reload()
    },
    onError: (errors) => {
      notyf.error('No se pudo eliminar la cita')
    }
  })
}


const exportCitas = () => {
  const params = new URLSearchParams()
  if (searchTerm.value) params.append('search', searchTerm.value)
  if (filtroEstadoCita.value) params.append('estado', filtroEstadoCita.value)
  const queryString = params.toString()
  const url = route('citas.export') + (queryString ? `?${queryString}` : '')
  window.location.href = url
}

// Paginación
const paginationData = computed(() => {
  const p = citasPaginator.value || {}
  return {
    currentPage: props.pagination?.current_page || p.current_page || 1,
    lastPage:    props.pagination?.last_page || p.last_page || 1,
    perPage:     props.pagination?.per_page || p.per_page || 10,
    from:        props.pagination?.from || p.from || 0,
    to:          props.pagination?.to || p.to || 0,
    total:       props.pagination?.total || p.total || 0,
    prevPageUrl: p.prev_page_url ?? null,
    nextPageUrl: p.next_page_url ?? null,
    links:       p.links ?? []
  }
})


const handlePerPageChange = (newPerPage) => {
  perPage.value = newPerPage
  router.get(route('citas.index'), {
    search: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    estado: filtroEstadoCita.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const handlePageChange = (newPage) => {
  router.get(route('citas.index'), {
    search: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    estado: filtroEstadoCita.value,
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

const formatearHora = (date) => {
  if (!date) return 'Hora no disponible'
  try {
    const d = new Date(date)
    return d.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })
  } catch {
    return 'Hora inválida'
  }
}


const obtenerEstadoCitaClase = (estado) => {
  const clases = {
    'pendiente': 'bg-yellow-100 text-yellow-700',
    'programado': 'bg-blue-100 text-blue-700',
    'en_proceso': 'bg-indigo-100 text-indigo-700',
    'completado': 'bg-green-100 text-green-700',
    'cancelado': 'bg-red-100 text-red-700',
    'reprogramado': 'bg-purple-100 text-purple-700'
  }
  return clases[estado] || 'bg-gray-100 text-gray-700'
}

const obtenerEstadoCitaLabel = (estado) => {
  const labels = {
    'pendiente': 'Pendiente',
    'programado': 'Programado',
    'en_proceso': 'En Proceso',
    'completado': 'Completado',
    'cancelado': 'Cancelado',
    'reprogramado': 'Reprogramado'
  }
  return labels[estado] || 'Desconocido'
}

// Lógica de Calendario
const daysInMonth = computed(() => {
  const year = currentMonth.value.getFullYear()
  const month = currentMonth.value.getMonth()
  const date = new Date(year, month, 1)
  const days = []
  
  // Rellenar días del mes anterior
  const firstDayOfWeek = date.getDay()
  const prevMonthLastDay = new Date(year, month, 0).getDate()
  for (let i = firstDayOfWeek - 1; i >= 0; i--) {
    days.push({ 
      day: prevMonthLastDay - i, 
      month: 'prev',
      date: new Date(year, month - 1, prevMonthLastDay - i)
    })
  }

  // Días del mes actual
  const lastDay = new Date(year, month + 1, 0).getDate()
  for (let i = 1; i <= lastDay; i++) {
    days.push({ 
      day: i, 
      month: 'current',
      date: new Date(year, month, i)
    })
  }

  // Rellenar días del mes siguiente
  const remainingCells = 42 - days.length
  for (let i = 1; i <= remainingCells; i++) {
    days.push({ 
      day: i, 
      month: 'next',
      date: new Date(year, month + 1, i)
    })
  }

  return days
})

const monthYearLabel = computed(() => {
  return currentMonth.value.toLocaleDateString('es-MX', { month: 'long', year: 'numeric' })
})

const changeMonth = (offset) => {
  currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() + offset, 1)
}

const getCitasForDay = (date) => {
  const dateStr = date.toISOString().split('T')[0]
  return citasData.value.filter(c => {
    const citaDate = new Date(c.fecha_hora).toISOString().split('T')[0]
    return citaDate === dateStr
  })
}

const isToday = (date) => {
  const today = new Date()
  return date.getDate() === today.getDate() && 
         date.getMonth() === today.getMonth() && 
         date.getFullYear() === today.getFullYear()
}
</script>

<template>
  <Head title="Citas" />
  <div class="citas-index min-h-screen bg-gray-50">
    <div class="w-full px-6 py-8">
      <!-- Header específico de citas -->
      <CitasHeader
        :total="estadisticas.total"
        :pendientes="estadisticas.pendientes"
        :enProceso="estadisticas.enProceso"
        :completadas="estadisticas.completadas"
        :canceladas="estadisticas.canceladas"
        v-model:search-term="searchTerm"
        v-model:sort-by="sortBy"
        v-model:filtro-estado-cita="filtroEstadoCita"
        @crear-nueva="crearNuevaCita"
        @search-change="handleSearchChange"
        @filtro-estado-cita-change="handleEstadoCitaChange"
        @sort-change="handleSortChange"
        @limpiar-filtros="limpiarFiltros"
        v-model:view-mode="viewMode"
      />

      <!-- Tabla -->
      <div v-if="viewMode === 'table'" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Fecha</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Cita</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Cliente</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Técnico</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Estado Cita</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Reporte / Fotos</th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="cita in citasDocumentos" :key="cita.id" class="hover:bg-gray-50 transition-colors duration-150">
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-900">{{ formatearFecha(cita.raw.fecha_hora) }}</div>
                  <div class="text-xs text-gray-500">{{ formatearHora(cita.raw.fecha_hora) }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-gray-900">{{ cita.titulo }}</div>
                  <div class="text-xs text-gray-500">{{ formatearFecha(cita.raw.fecha_hora) }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-700">{{ cita.subtitulo }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-700">{{ cita.raw.tecnico?.name || 'N/A' }}</div>
                </td>
                <td class="px-6 py-4">
                  <span :class="obtenerEstadoCitaClase(cita.raw.estado)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ obtenerEstadoCitaLabel(cita.raw.estado) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-center">
                  <div v-if="cita.raw.fotos_finales?.length > 0" 
                       @click="openGallery(cita.raw.fotos_finales, `Evidencias - Cita #${cita.id}`)"
                       class="cursor-pointer group flex flex-col items-center gap-1">
                    
                    <!-- Texto descriptivo con Folio -->
                    <span class="text-[10px] font-bold text-indigo-600 group-hover:text-indigo-800 uppercase tracking-wide flex items-center gap-1 bg-indigo-50 px-2 py-0.5 rounded-full border border-indigo-100 group-hover:border-indigo-200 transition-colors">
                       📸 Fotos #{{ cita.id }}
                    </span>

                    <div class="flex -space-x-2 overflow-hidden justify-center hover:space-x-1 transition-all mt-1">
                      <img 
                        v-for="(foto, idx) in cita.raw.fotos_finales.slice(0, 3)" 
                        :key="idx"
                        :src="'/storage/' + foto" 
                        class="inline-block h-8 w-8 rounded-lg ring-2 ring-white object-cover shadow-sm bg-gray-100 group-hover:ring-indigo-200"
                        :title="cita.raw.trabajo_realizado || 'Ver evidencias'"
                      >
                      <div v-if="cita.raw.fotos_finales.length > 3" class="flex items-center justify-center h-8 w-8 rounded-lg ring-2 ring-white bg-gray-100 text-[10px] font-bold text-gray-500 shadow-sm group-hover:bg-indigo-50 group-hover:text-indigo-600">
                        +{{ cita.raw.fotos_finales.length - 3 }}
                      </div>
                    </div>
                  </div>
                  <div v-else-if="cita.raw.trabajo_realizado" class="text-[10px] font-bold text-gray-400 italic">
                    Reporte sin fotos
                  </div>
                  <span v-else class="text-xs text-gray-300">—</span>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end space-x-1">
                    <button @click="verDetalles(cita)" class="w-8 h-8 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors duration-150" title="Ver detalles">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>
                    <button @click="editarCita(cita.id)" class="w-8 h-8 bg-amber-50 text-amber-600 rounded-lg hover:bg-amber-100 transition-colors duration-150" title="Editar">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button @click="confirmarEliminacion(cita.id)" class="w-8 h-8 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors duration-150" title="Eliminar">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="citasDocumentos.length === 0">
                <td colspan="6" class="px-6 py-16 text-center">
                  <div class="flex flex-col items-center space-y-4">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                      <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                    </div>
                    <div class="space-y-1">
                      <p class="text-gray-700 font-medium">No hay citas</p>
                      <p class="text-sm text-gray-500">Las citas aparecerán aquí cuando se creen</p>
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

      <!-- Calendario -->
      <div v-else class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
          <div class="flex items-center gap-4">
            <h2 class="text-lg font-bold text-gray-800 capitalize">{{ monthYearLabel }}</h2>
            <div class="flex gap-1">
              <button @click="changeMonth(-1)" class="p-1.5 hover:bg-gray-200 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
              </button>
              <button @click="currentMonth = new Date()" class="px-3 py-1 text-xs font-bold text-gray-600 hover:bg-gray-200 rounded-lg transition-colors border border-gray-300">
                Hoy
              </button>
              <button @click="changeMonth(1)" class="p-1.5 hover:bg-gray-200 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
              </button>
            </div>
          </div>
          <div class="flex gap-3 text-xs">
            <div class="flex items-center gap-1.5">
              <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
              <span class="text-gray-500">Pendiente</span>
            </div>
            <div class="flex items-center gap-1.5">
              <div class="w-3 h-3 rounded-full bg-indigo-500"></div>
              <span class="text-gray-500">Proceso</span>
            </div>
            <div class="flex items-center gap-1.5">
              <div class="w-3 h-3 rounded-full bg-green-500"></div>
              <span class="text-gray-500">Hecho</span>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-7 border-b border-gray-100">
          <div v-for="day in ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']" :key="day" class="py-3 text-center text-[10px] font-black uppercase tracking-widest text-gray-400 bg-gray-50">
            {{ day }}
          </div>
        </div>

        <div class="grid grid-cols-7 auto-rows-[120px]">
          <div v-for="(day, idx) in daysInMonth" :key="idx" 
               :class="['border-r border-b border-gray-100 p-2 transition-colors relative', 
                        day.month === 'current' ? 'bg-white' : 'bg-gray-50/50 opacity-60']">
            <div class="flex justify-between items-start mb-1">
              <span :class="['text-xs font-bold px-1.5 py-0.5 rounded-md', 
                             isToday(day.date) ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-400']">
                {{ day.day }}
              </span>
            </div>
            
            <div class="space-y-1 overflow-y-auto max-h-[85px] custom-scrollbar">
              <div v-for="cita in getCitasForDay(day.date)" :key="cita.id"
                   @click="verDetalles({ raw: cita, titulo: `Cita #${cita.id}` })"
                   :class="['p-1 rounded text-[9px] font-bold cursor-pointer truncate transition-all hover:scale-[1.02]',
                            obtenerEstadoCitaClase(cita.estado),
                            cita.estado === 'pendiente' ? 'border-l-2 border-yellow-500' : 
                            cita.estado === 'en_proceso' ? 'border-l-2 border-indigo-500' : 
                            cita.estado === 'completado' ? 'border-l-2 border-green-500' : '']"
                   :title="`${cita.cliente?.nombre_razon_social} - ${cita.problema_reportado}`">
                {{ formatearHora(cita.fecha_hora) }} {{ cita.cliente?.nombre_razon_social }}
              </div>
            </div>
          </div>
        </div>
      </div>


      <!-- Modal mejorado -->
      <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showModal = false">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
          <!-- Header del modal -->
          <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">
              {{ modalMode === 'details' ? 'Detalles de la Cita' : 'Confirmar Eliminación' }}
            </h3>
            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="p-6">
            <div v-if="modalMode === 'details' && selectedCita">
              <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="space-y-3">
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Cliente</label>
                      <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">{{ selectedCita.cliente?.nombre_razon_social || 'N/A' }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Técnico</label>
                      <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">{{ selectedCita.tecnico?.name || 'N/A' }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Tipo de Servicio</label>
                      <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">{{ selectedCita.tipo_servicio || 'N/A' }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Estado de Cita</label>
                      <span :class="obtenerEstadoCitaClase(selectedCita.estado)" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium mt-1">
                        {{ obtenerEstadoCitaLabel(selectedCita.estado) }}
                      </span>
                    </div>
                  </div>
                  <div class="space-y-3">
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Fecha y Hora</label>
                      <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">{{ formatearFecha(selectedCita.fecha_hora) }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Fecha de creación</label>
                      <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">{{ formatearFecha(selectedCita.created_at) }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Última actualización</label>
                      <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">{{ formatearFecha(selectedCita.updated_at) }}</p>
                    </div>
                  </div>
                </div>
                <div v-if="selectedCita.descripcion">
                  <label class="block text-sm font-medium text-gray-700">Descripción inicial</label>
                  <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md whitespace-pre-wrap">{{ selectedCita.descripcion }}</p>
                </div>
                <div v-if="selectedCita.problema_reportado">
                  <label class="block text-sm font-medium text-gray-700">Problema Reportado</label>
                  <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md whitespace-pre-wrap">{{ selectedCita.problema_reportado }}</p>
                </div>

                <!-- Reporte de técnico (Cierre) -->
                <div v-if="selectedCita.trabajo_realizado || selectedCita.fotos_finales" class="mt-6 pt-6 border-t border-gray-200">
                  <h4 class="text-md font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <span class="text-green-600">✅</span> Reporte de Cierre (Técnico)
                  </h4>
                  
                  <div v-if="selectedCita.trabajo_realizado" class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Trabajo Realizado</label>
                    <p class="text-sm text-gray-900 bg-green-50/50 border border-green-100 px-3 py-3 rounded-xl whitespace-pre-wrap italic">
                      "{{ selectedCita.trabajo_realizado }}"
                    </p>
                  </div>

                  <div v-if="selectedCita.fotos_finales?.length > 0">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Evidencias Finales ({{ selectedCita.fotos_finales.length }})</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                      <div v-for="(foto, idx) in selectedCita.fotos_finales" :key="idx" class="aspect-square rounded-lg overflow-hidden border border-gray-200 bg-gray-100 group cursor-pointer hover:shadow-md transition-all">
                        <a :href="'/storage/' + foto" target="_blank" class="block w-full h-full">
                          <img :src="'/storage/' + foto" class="w-full h-full object-cover transition-transform group-hover:scale-110" alt="Evidencia final">
                        </a>
                      </div>
                    </div>
                  </div>
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
                <h3 class="text-lg font-medium text-gray-900 mb-2">¿Eliminar Cita?</h3>
                <p class="text-sm text-gray-500 mb-4">
                  ¿Estás seguro de que deseas eliminar la cita <strong>#{{ selectedCita?.id }}</strong>?
                  Esta acción no se puede deshacer.
                </p>
              </div>
            </div>
          </div>

          <!-- Footer del modal -->
          <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
            <button @click="showModal = false" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
              {{ modalMode === 'details' ? 'Cerrar' : 'Cancelar' }}
            </button>
            <div v-if="modalMode === 'details'">
              <button @click="editarCita(selectedCita.id)" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors">
                Editar
              </button>
            </div>
            <button v-if="modalMode === 'confirm'" @click="eliminarCita" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
              Eliminar
            </button>
          </div>
        </div>
      </div>
      <!-- Nuevo Modal de Galería de Fotos -->
      <div v-if="showGalleryModal" class="fixed inset-0 bg-black/90 z-[60] flex flex-col" @click.self="closeGallery">
        <!-- Toolbar -->
        <div class="flex justify-between items-center p-4 text-white bg-black/50 backdrop-blur-sm">
           <div class="text-sm font-medium">
             {{ imageTitle }}
             <span class="ml-2 text-white/50 text-xs">({{ currentImageIndex + 1 }} / {{ galleryImages.length }})</span>
           </div>
           <button @click="closeGallery" class="p-2 hover:bg-white/20 rounded-full transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
           </button>
        </div>

        <!-- Main Image Area -->
        <div class="flex-1 flex items-center justify-center relative p-4 overflow-hidden">
           <button v-if="galleryImages.length > 1" @click.stop="prevImage" class="absolute left-4 p-3 bg-black/50 hover:bg-black/70 text-white rounded-full backdrop-blur-sm transition-all hover:scale-110">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
           </button>
           
           <img :src="galleryImages[currentImageIndex]" class="max-h-full max-w-full object-contain rounded-lg shadow-2xl transition-all duration-300" :key="currentImageIndex">

           <button v-if="galleryImages.length > 1" @click.stop="nextImage" class="absolute right-4 p-3 bg-black/50 hover:bg-black/70 text-white rounded-full backdrop-blur-sm transition-all hover:scale-110">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
           </button>
        </div>

        <!-- Thumbnails Strip -->
        <div v-if="galleryImages.length > 1" class="p-4 bg-black/50 backdrop-blur-sm overflow-x-auto flex justify-center gap-2">
           <button 
             v-for="(img, idx) in galleryImages" 
             :key="idx" 
             @click.stop="currentImageIndex = idx"
             :class="['w-16 h-16 rounded-lg overflow-hidden border-2 transition-all', currentImageIndex === idx ? 'border-indigo-500 scale-110' : 'border-transparent opacity-50 hover:opacity-100']"
           >
             <img :src="img" class="w-full h-full object-cover">
           </button>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.citas-index {
  min-height: 100vh;
  background-color: #f9fafb;
}
</style>




