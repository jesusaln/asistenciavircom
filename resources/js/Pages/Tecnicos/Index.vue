<!-- /resources/js/Pages/Tecnicos/IndexNew.vue -->
<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

import TecnicosHeader from '@/Components/IndexComponents/TecnicosHeader.vue'

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
  tecnicos: { type: [Object, Array], required: true },
  stats: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  sorting: { type: Object, default: () => ({ sort_by: 'created_at', sort_direction: 'desc' }) },
})

// Estado UI
const showModal = ref(false)
const modalMode = ref('details')
const selectedTecnico = ref(null)
const selectedId = ref(null)

// Filtros
const searchTerm = ref(props.filters?.search ?? '')
const sortBy = ref('created_at-desc')
const filtroEstado = ref('')
const filtroEspecialidad = ref('')

// Paginación
const perPage = ref(10)

// Función para crear nuevo técnico
const crearNuevoTecnico = () => {
  router.visit(route('tecnicos.create'))
}

// Función para limpiar filtros
const limpiarFiltros = () => {
  searchTerm.value = ''
  sortBy.value = 'created_at-desc'
  filtroEstado.value = ''
  filtroEspecialidad.value = ''
  router.visit(route('tecnicos.index'))
  notyf.success('Filtros limpiados correctamente')
}

// Estadísticas adicionales para el header moderno
const conEspecialidad = computed(() => {
  // Contar técnicos que tienen especialidad asignada
  if (tecnicosData.value && tecnicosData.value.length > 0) {
    return tecnicosData.value.filter(tecnico =>
      tecnico.especialidad_id || tecnico.especialidad
    ).length
  }
  return 0
})

const conCertificacion = computed(() => {
  // Contar técnicos que tienen certificación
  if (tecnicosData.value && tecnicosData.value.length > 0) {
    return tecnicosData.value.filter(tecnico =>
      tecnico.certificacion || tecnico.certificaciones
    ).length
  }
  return 0
})

const especialidades = computed(() => {
  // Obtener lista única de especialidades de los técnicos
  if (tecnicosData.value && tecnicosData.value.length > 0) {
    const especialidadesMap = new Map()

    tecnicosData.value.forEach(tecnico => {
      if (tecnico.especialidad) {
        especialidadesMap.set(tecnico.especialidad.id, tecnico.especialidad)
      }
    })

    return Array.from(especialidadesMap.values())
  }
  return []
})

// Función para manejar filtro de especialidad
const handleEspecialidadChange = (especialidadId) => {
  filtroEspecialidad.value = especialidadId
  router.get(route('tecnicos.index'), {
    search: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    activo: filtroEstado.value,
    especialidad_id: especialidadId,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

// Datos
const tecnicosPaginator = computed(() => props.tecnicos)
const tecnicosData = computed(() => tecnicosPaginator.value?.data || [])

// Estadísticas
const estadisticas = computed(() => ({
  total: props.stats?.total ?? 0,
  activos: props.stats?.activos ?? 0,
  inactivos: props.stats?.inactivos ?? 0,
  activosPorcentaje: props.stats?.activos > 0 ? Math.round((props.stats.activos / props.stats.total) * 100) : 0,
  inactivosPorcentaje: props.stats?.inactivos > 0 ? Math.round((props.stats.inactivos / props.stats.total) * 100) : 0
}))

// Transformación de datos
const tecnicosDocumentos = computed(() => {
  return tecnicosData.value.map(t => {
    return {
      id: t.id,
      titulo: `${t.nombre} ${t.apellido}`,
      subtitulo: t.email || '',
      estado: t.activo ? 'activo' : 'inactivo',
      extra: `Teléfono: ${t.telefono || 'N/A'} | Dirección: ${t.direccion || 'N/A'}`,
      fecha: t.created_at,
      raw: t
    }
  })
})

// Handlers
function handleSearchChange(newSearch) {
  searchTerm.value = newSearch
  router.get(route('tecnicos.index'), {
    search: newSearch,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    activo: filtroEstado.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

function handleEstadoChange(newEstado) {
  filtroEstado.value = newEstado
  router.get(route('tecnicos.index'), {
    search: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    activo: newEstado,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

function handleSortChange(newSort) {
  sortBy.value = newSort
  router.get(route('tecnicos.index'), {
    search: searchTerm.value,
    sort_by: newSort.split('-')[0],
    sort_direction: newSort.split('-')[1] || 'desc',
    activo: filtroEstado.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const verDetalles = (doc) => {
  selectedTecnico.value = doc.raw
  modalMode.value = 'details'
  showModal.value = true
}

const editarTecnico = (id) => {
  router.visit(route('tecnicos.edit', id))
}

const confirmarEliminacion = (id) => {
  selectedId.value = id
  modalMode.value = 'confirm'
  showModal.value = true
}

const eliminarTecnico = () => {
  router.delete(route('tecnicos.destroy', selectedId.value), {
    preserveScroll: true,
    onSuccess: () => {
      notyf.success('Técnico eliminado correctamente')
      showModal.value = false
      selectedId.value = null
      router.reload()
    },
    onError: (errors) => {
      notyf.error('No se pudo eliminar el técnico')
    }
  })
}

const toggleTecnico = (id) => {
  const tecnico = tecnicosData.value.find(t => t.id === id)
  if (!tecnico) return notyf.error('Técnico no encontrado')
  const nuevoEstado = tecnico.activo ? 'inactivo' : 'activo'
  const mensaje = nuevoEstado === 'activo' ? 'Técnico activado correctamente' : 'Técnico desactivado correctamente'

  router.put(route('tecnicos.toggle', id), {
    preserveScroll: true,
    onSuccess: () => {
      notyf.success(mensaje)
      router.reload()
    },
    onError: (errors) => {
      notyf.error('No se pudo cambiar el estado del técnico')
    }
  })
}

const exportTecnicos = () => {
  const params = new URLSearchParams()
  if (searchTerm.value) params.append('search', searchTerm.value)
  if (filtroEstado.value) params.append('activo', filtroEstado.value)
  const queryString = params.toString()
  const url = route('tecnicos.export') + (queryString ? `?${queryString}` : '')
  window.location.href = url
}

// Paginación
const paginationData = computed(() => ({
  current_page: tecnicosPaginator.value?.current_page || 1,
  last_page: tecnicosPaginator.value?.last_page || 1,
  per_page: tecnicosPaginator.value?.per_page || 10,
  from: tecnicosPaginator.value?.from || 0,
  to: tecnicosPaginator.value?.to || 0,
  total: tecnicosPaginator.value?.total || 0,
  prev_page_url: tecnicosPaginator.value?.prev_page_url,
  next_page_url: tecnicosPaginator.value?.next_page_url,
  links: tecnicosPaginator.value?.links || []
}))

const handlePerPageChange = (newPerPage) => {
  router.get(route('tecnicos.index'), {
    ...props.filters,
    ...props.sorting,
    per_page: newPerPage,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const handlePageChange = (newPage) => {
  router.get(route('tecnicos.index'), {
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
  <Head title="Técnicos" />
  <div class="tecnicos-index min-h-screen bg-white">
    <div class="w-full px-6 py-8">
      <!-- Header específico de técnicos -->
      <TecnicosHeader
        :total="estadisticas.total"
        :activos="estadisticas.activos"
        :inactivos="estadisticas.inactivos"
        :con-especialidad="conEspecialidad"
        :con-certificacion="conCertificacion"
        :especialidades="especialidades"
        v-model:search-term="searchTerm"
        v-model:sort-by="sortBy"
        v-model:filtro-estado="filtroEstado"
        v-model:filtro-especialidad="filtroEspecialidad"
        @crear-nueva="crearNuevoTecnico"
        @search-change="handleSearchChange"
        @filtro-estado-change="handleEstadoChange"
        @filtro-especialidad-change="handleEspecialidadChange"
        @sort-change="handleSortChange"
        @limpiar-filtros="limpiarFiltros"
      />

      <!-- Tabla -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-white">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Fecha</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Técnico</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Email</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Teléfono</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Usuario</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Estado</th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="tecnico in tecnicosDocumentos" :key="tecnico.id" class="hover:bg-white transition-colors duration-150">
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-900">{{ formatearFecha(tecnico.fecha) }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-gray-900">{{ tecnico.titulo }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-700">{{ tecnico.subtitulo }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-700">{{ tecnico.raw.telefono || 'N/A' }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-700">{{ tecnico.raw.user ? tecnico.raw.user.name : 'Sin asignar' }}</div>
                </td>
                <td class="px-6 py-4">
                  <span :class="obtenerClasesEstado(tecnico.estado)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ obtenerLabelEstado(tecnico.estado) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end space-x-1">
                    <button @click="verDetalles(tecnico)" class="w-8 h-8 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors duration-150" title="Ver detalles">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>
                    <button @click="editarTecnico(tecnico.id)" class="w-8 h-8 bg-amber-50 text-amber-600 rounded-lg hover:bg-amber-100 transition-colors duration-150" title="Editar">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button @click="toggleTecnico(tecnico.id)" class="w-8 h-8 bg-green-50 text-green-600 rounded-lg hover:bg-green-100 transition-colors duration-150" title="Cambiar estado">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                      </svg>
                    </button>
                    <button @click="confirmarEliminacion(tecnico.id)" class="w-8 h-8 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors duration-150" title="Eliminar">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="tecnicosDocumentos.length === 0">
                <td colspan="7" class="px-6 py-16 text-center">
                  <div class="flex flex-col items-center space-y-4">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                      <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                    </div>
                    <div class="space-y-1">
                      <p class="text-gray-700 font-medium">No hay técnicos</p>
                      <p class="text-sm text-gray-500">Los técnicos aparecerán aquí cuando se creen</p>
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
                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-white"
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
                :class="page === paginationData.currentPage ? 'bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-white'"
                class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
              >
                {{ page }}
              </button>

              <button
                v-if="paginationData.nextPageUrl"
                @click="handlePageChange(paginationData.currentPage + 1)"
                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-white"
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

      <!-- Modal mejorado -->
      <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showModal = false">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
          <!-- Header del modal -->
          <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">
              {{ modalMode === 'details' ? 'Detalles del Técnico' : 'Confirmar Eliminación' }}
            </h3>
            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="p-6">
            <div v-if="modalMode === 'details' && selectedTecnico">
              <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="space-y-3">
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Nombre Completo</label>
                      <p class="mt-1 text-sm text-gray-900 bg-white px-3 py-2 rounded-md">{{ selectedTecnico.nombre }} {{ selectedTecnico.apellido }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Email</label>
                      <p class="mt-1 text-sm text-gray-900 bg-white px-3 py-2 rounded-md">{{ selectedTecnico.email || 'N/A' }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Estado</label>
                      <span :class="obtenerClasesEstado(selectedTecnico.activo ? 'activo' : 'inactivo')" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium mt-1">
                        {{ selectedTecnico.activo ? 'Activo' : 'Inactivo' }}
                      </span>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Usuario Asignado</label>
                      <p class="mt-1 text-sm text-gray-900 bg-white px-3 py-2 rounded-md">{{ selectedTecnico.user ? selectedTecnico.user.name : 'Sin asignar' }}</p>
                    </div>
                  </div>
                  <div class="space-y-3">
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Teléfono</label>
                      <p class="mt-1 text-sm text-gray-900 bg-white px-3 py-2 rounded-md">{{ selectedTecnico.telefono || 'N/A' }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Fecha de creación</label>
                      <p class="mt-1 text-sm text-gray-900 bg-white px-3 py-2 rounded-md">{{ formatearFecha(selectedTecnico.created_at) }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Última actualización</label>
                      <p class="mt-1 text-sm text-gray-900 bg-white px-3 py-2 rounded-md">{{ formatearFecha(selectedTecnico.updated_at) }}</p>
                    </div>
                  </div>
                </div>
                <div v-if="selectedTecnico.direccion">
                  <label class="block text-sm font-medium text-gray-700">Dirección</label>
                  <p class="mt-1 text-sm text-gray-900 bg-white px-3 py-2 rounded-md whitespace-pre-wrap">{{ selectedTecnico.direccion }}</p>
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
                <h3 class="text-lg font-medium text-gray-900 mb-2">¿Eliminar Técnico?</h3>
                <p class="text-sm text-gray-500 mb-4">
                  ¿Estás seguro de que deseas eliminar el técnico <strong>{{ selectedTecnico?.nombre }} {{ selectedTecnico?.apellido }}</strong>?
                  Esta acción no se puede deshacer.
                </p>
              </div>
            </div>
          </div>

          <!-- Footer del modal -->
          <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-white">
            <button @click="showModal = false" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
              {{ modalMode === 'details' ? 'Cerrar' : 'Cancelar' }}
            </button>
            <div v-if="modalMode === 'details'" class="flex gap-2">
              <button @click="toggleTecnico(selectedTecnico.id)" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                Cambiar Estado
              </button>
              <button @click="editarTecnico(selectedTecnico.id)" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors">
                Editar
              </button>
            </div>
            <button v-if="modalMode === 'confirm'" @click="eliminarTecnico" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
              Eliminar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.tecnicos-index {
  min-height: 100vh;
}
</style>




