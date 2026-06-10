<!-- /resources/js/Pages/Usuarios/IndexNew.vue -->
<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

import UsuariosHeader from '@/Components/IndexComponents/UsuariosHeader.vue'
import { ELEVATED_ROLES } from '@/constants/systemRoles'

defineOptions({ layout: AppLayout, inheritAttrs: false })

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
  usuarios: { type: [Object, Array], required: true },
  stats: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  sorting: { type: Object, default: () => ({ sort_by: 'created_at', sort_direction: 'desc' }) },
})

// Estado UI
const showModal = ref(false)
const modalMode = ref('details')
const selectedUsuario = ref(null)
const selectedId = ref(null)

// Filtros
const searchTerm = ref(props.filters?.search ?? '')
const sortBy = ref('created_at-desc')
const filtroEstado = ref('')
const filtroRol = ref('')
const filtroVerificacion = ref('')

// Paginación
const perPage = ref(10)

// Función para crear nuevo usuario
const crearNuevoUsuario = () => {
  router.visit(route('usuarios.create'))
}

// Función para limpiar filtros
const limpiarFiltros = () => {
  searchTerm.value = ''
  sortBy.value = 'created_at-desc'
  filtroEstado.value = ''
  filtroRol.value = ''
  filtroVerificacion.value = ''
  router.visit(route('usuarios.index'))
  notyf.success('Filtros limpiados correctamente')
}

// Estadísticas adicionales para el header moderno
const administradores = computed(() => {
  // Contar usuarios que son administradores
  if (usuariosData.value && usuariosData.value.length > 0) {
    return usuariosData.value.filter(usuario =>
      usuario.roles && usuario.roles.some(role => ELEVATED_ROLES.includes(role.name))
    ).length
  }
  return 0
})

const con2FA = computed(() => {
  // Contar usuarios que tienen 2FA habilitado
  if (usuariosData.value && usuariosData.value.length > 0) {
    return usuariosData.value.filter(usuario =>
      usuario.two_factor_enabled || usuario.has_two_factor
    ).length
  }
  return 0
})

// Función para manejar filtro de rol
const handleRolChange = (rol) => {
  filtroRol.value = rol
  router.get(route('usuarios.index'), {
    search: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    activo: filtroEstado.value,
    role: rol,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

// Función para manejar filtro de verificación
const handleVerificacionChange = (verificacion) => {
  filtroVerificacion.value = verificacion
  router.get(route('usuarios.index'), {
    search: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    activo: filtroEstado.value,
    verificacion: verificacion,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

// Datos
const usuariosPaginator = computed(() => props.usuarios)
const usuariosData = computed(() => usuariosPaginator.value?.data || [])

// Estadísticas
const estadisticas = computed(() => ({
  total: props.stats?.total ?? 0,
  activos: props.stats?.activos ?? 0,
  inactivos: props.stats?.inactivos ?? 0,
  activosPorcentaje: props.stats?.activos > 0 ? Math.round((props.stats.activos / props.stats.total) * 100) : 0,
  inactivosPorcentaje: props.stats?.inactivos > 0 ? Math.round((props.stats.inactivos / props.stats.total) * 100) : 0
}))

// Transformación de datos
const usuariosDocumentos = computed(() => {
  return usuariosData.value.map(u => {
    return {
      id: u.id,
      titulo: u.name,
      subtitulo: u.email || '',
      estado: u.activo ? 'activo' : 'inactivo',
      extra: u.roles && u.roles.length > 0 ? u.roles.map(r => r.name).join(', ') : 'Sin rol',
      fecha: u.created_at,
      raw: u
    }
  })
})

// Handlers
function handleSearchChange(newSearch) {
  searchTerm.value = newSearch
  router.get(route('usuarios.index'), {
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
  router.get(route('usuarios.index'), {
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
  router.get(route('usuarios.index'), {
    search: searchTerm.value,
    sort_by: newSort.split('-')[0],
    sort_direction: newSort.split('-')[1] || 'desc',
    activo: filtroEstado.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const verDetalles = (doc) => {
  selectedUsuario.value = doc.raw
  modalMode.value = 'details'
  showModal.value = true
}

const editarUsuario = (id) => {
  router.visit(route('usuarios.edit', id))
}

const confirmarEliminacion = (id) => {
  selectedId.value = id
  modalMode.value = 'confirm'
  showModal.value = true
}

const eliminarUsuario = () => {
  router.delete(route('usuarios.destroy', selectedId.value), {
    preserveScroll: true,
    onSuccess: () => {
      notyf.success('Usuario eliminado correctamente')
      showModal.value = false
      selectedId.value = null
      router.reload()
    },
    onError: (errors) => {
      notyf.error('No se pudo eliminar el usuario')
    }
  })
}

const toggleUsuario = (id) => {
  const usuario = usuariosData.value.find(u => u.id === id)
  if (!usuario) return notyf.error('Usuario no encontrado')
  const nuevoEstado = usuario.activo ? 'inactivo' : 'activo'
  const mensaje = nuevoEstado === 'activo' ? 'Usuario activado correctamente' : 'Usuario desactivado correctamente'

  router.put(route('usuarios.toggle', id), {
    preserveScroll: true,
    onSuccess: () => {
      notyf.success(mensaje)
      router.reload()
    },
    onError: (errors) => {
      notyf.error('No se pudo cambiar el estado del usuario')
    }
  })
}

const exportUsuarios = () => {
  const params = new URLSearchParams()
  if (searchTerm.value) params.append('search', searchTerm.value)
  if (filtroEstado.value) params.append('activo', filtroEstado.value)
  const queryString = params.toString()
  const url = route('usuarios.export') + (queryString ? `?${queryString}` : '')
  window.location.href = url
}

// Paginación
const paginationData = computed(() => ({
  currentPage: usuariosPaginator.value?.current_page || 1,
  lastPage: usuariosPaginator.value?.last_page || 1,
  perPage: usuariosPaginator.value?.per_page || 10,
  from: usuariosPaginator.value?.from || 0,
  to: usuariosPaginator.value?.to || 0,
  total: usuariosPaginator.value?.total || 0,
  prevPageUrl: usuariosPaginator.value?.prev_page_url,
  nextPageUrl: usuariosPaginator.value?.next_page_url,
  links: usuariosPaginator.value?.links || []
}))

const handlePerPageChange = (newPerPage) => {
  router.get(route('usuarios.index'), {
    ...props.filters,
    ...props.sorting,
    per_page: newPerPage,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const handlePageChange = (newPage) => {
  router.get(route('usuarios.index'), {
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
    'activo': 'bg-emerald-100 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200',
    'inactivo': 'bg-rose-100 text-rose-800 dark:text-rose-200 dark:text-rose-200'
  }
  return clases[estado] || 'bg-slate-100 text-slate-700'
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
  <Head title="Usuarios" />
  <div class="usuarios-index usuarios-premium min-h-screen bg-[var(--ui-surface)] dark:from-slate-950 dark:via-slate-900 dark:to-indigo-950">
    <div class="w-full px-6 py-8">
      <!-- Header específico de usuarios -->
      <UsuariosHeader
        :total="estadisticas.total"
        :activos="estadisticas.activos"
        :inactivos="estadisticas.inactivos"
        :administradores="administradores"
        :con-2fa="con2FA"
        v-model:search-term="searchTerm"
        v-model:sort-by="sortBy"
        v-model:filtro-estado="filtroEstado"
        v-model:filtro-rol="filtroRol"
        v-model:filtro-verificacion="filtroVerificacion"
        @crear-nueva="crearNuevoUsuario"
        @search-change="handleSearchChange"
        @filtro-estado-change="handleEstadoChange"
        @filtro-rol-change="handleRolChange"
        @filtro-verificacion-change="handleVerificacionChange"
        @sort-change="handleSortChange"
        @limpiar-filtros="limpiarFiltros"
      />

      <!-- Tabla -->
      <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl rounded-[2rem] shadow-2xl border border-slate-200/70 dark:border-slate-800/70 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-800/50">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Fecha</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Usuario</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Email</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Rol</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado</th>
                <th v-if="$can('edit usuarios') || $can('delete usuarios') || $can('view usuarios')" class="px-6 py-4 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
              <tr v-for="usuario in usuariosDocumentos" :key="usuario.id" class="hover:bg-indigo-50/60 dark:hover:bg-slate-700/70 transition-colors duration-150">
                <td class="px-6 py-4">
                  <div class="text-sm text-slate-900">{{ formatearFecha(usuario.fecha) }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-slate-900">{{ usuario.titulo }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-slate-700">{{ usuario.subtitulo }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="flex flex-wrap gap-1">
                    <span v-for="rol in usuario.raw.roles" :key="rol.id" 
                          class="inline-flex items-center px-2 py-0.5 rounded-xl text-xs font-medium"
                          :class="{
                            'bg-purple-100 text-purple-800': rol.name === 'admin',
                            'bg-sky-100 text-sky-800 dark:text-sky-200': rol.name === 'vendedor',
                            'bg-emerald-100 text-emerald-800 dark:text-emerald-200': rol.name === 'cobranza',
                            'bg-brand-100 text-brand-800 dark:text-amber-200': rol.name === 'tecnico',
                            'bg-brand-100 text-amber-800': rol.name === 'almacenista',
                            'bg-teal-100 text-teal-800': rol.name === 'compras',
                            'bg-slate-100 text-slate-800': !['admin', 'vendedor', 'cobranza', 'tecnico', 'almacenista', 'compras'].includes(rol.name)
                          }">
                      {{ rol.name }}
                    </span>
                    <span v-if="!usuario.raw.roles || usuario.raw.roles.length === 0" class="text-xs text-slate-500 italic">
                      Sin rol
                    </span>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span :class="obtenerClasesEstado(usuario.estado)" class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium">
                    {{ obtenerLabelEstado(usuario.estado) }}
                  </span>
                </td>
                <td v-if="$can('edit usuarios') || $can('delete usuarios') || $can('view usuarios')" class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end space-x-1">
                    <button v-if="$can('view usuarios')" @click="verDetalles(usuario)" class="w-10 h-10 bg-sky-50 dark:bg-sky-900/20 text-blue-600 rounded-xl hover:bg-sky-100 transition-colors duration-150" title="Ver detalles">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>
                    <button v-if="$can('edit usuarios')" @click="editarUsuario(usuario.id)" class="w-10 h-10 bg-brand-50 dark:bg-brand-900/20 text-brand-600 rounded-xl hover:bg-brand-100 transition-colors duration-150" title="Editar">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button v-if="$can('edit usuarios')" @click="toggleUsuario(usuario.id)" class="w-10 h-10 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 rounded-xl hover:bg-emerald-100 transition-colors duration-150" title="Cambiar estado">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                      </svg>
                    </button>
                    <button v-if="$can('delete usuarios')" @click="confirmarEliminacion(usuario.id)" class="w-10 h-10 bg-rose-50 dark:bg-rose-900/20 text-rose-600 rounded-xl hover:bg-rose-100 transition-colors duration-150" title="Eliminar">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="usuariosDocumentos.length === 0">
                <td colspan="6" class="px-6 py-16 text-center">
                  <div class="flex flex-col items-center space-y-6">
                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center">
                      <svg class="w-10 h-10 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                    </div>
                    <div class="space-y-1">
                      <p class="text-slate-700 font-medium">No hay usuarios</p>
                      <p class="text-sm text-slate-500">Los usuarios aparecerán aquí cuando se creen</p>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div v-if="paginationData.lastPage > 1" class="bg-white border-t border-slate-200 px-4 py-3 sm:px-6">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4">
              <p class="text-sm text-slate-700">
                Mostrando {{ paginationData.from }} - {{ paginationData.to }} de {{ paginationData.total }} resultados
              </p>
              <select
                :value="paginationData.perPage"
                @change="handlePerPageChange(parseInt($event.target.value))"
                class="border border-slate-300 dark:border-slate-700 rounded-xl text-sm py-1 px-2 bg-white/80 dark:bg-slate-800 text-slate-700 dark:text-slate-200"
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
                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-slate-300 bg-white text-sm font-medium text-slate-500 hover:bg-white"
              >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </button>

              <span v-else class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-slate-300 bg-slate-100 text-sm font-medium text-slate-400">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </span>

              <button
                v-for="page in [paginationData.currentPage - 1, paginationData.currentPage, paginationData.currentPage + 1].filter(p => p > 0 && p <= paginationData.lastPage)"
                :key="page"
                @click="handlePageChange(page)"
                :class="page === paginationData.currentPage ? 'bg-sky-50 dark:bg-sky-900/20 border-blue-500 text-blue-600' : 'bg-white border-slate-300 text-slate-500 hover:bg-white'"
                class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
              >
                {{ page }}
              </button>

              <button
                v-if="paginationData.nextPageUrl"
                @click="handlePageChange(paginationData.currentPage + 1)"
                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-slate-300 bg-white text-sm font-medium text-slate-500 hover:bg-white"
              >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
              </button>

              <span v-else class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-slate-300 bg-slate-100 text-sm font-medium text-slate-400">
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
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto custom-scrollbar">
          <!-- Header del modal -->
          <div class="flex items-center justify-between p-6 border-b border-slate-200">
            <h3 class="text-lg font-medium text-slate-900">
              {{ modalMode === 'details' ? 'Detalles del Usuario' : 'Confirmar Eliminación' }}
            </h3>
            <button @click="showModal = false" class="text-slate-400 hover:text-brand-600 transition-colors">
              <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="p-6">
            <div v-if="modalMode === 'details' && selectedUsuario">
              <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="space-y-3">
                    <div>
                      <label class="block text-sm font-medium text-slate-700">Nombre</label>
                      <p class="mt-1 text-sm text-slate-900 bg-white px-3 py-2 rounded-xl">{{ selectedUsuario.name }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-slate-700">Email</label>
                      <p class="mt-1 text-sm text-slate-900 bg-white px-3 py-2 rounded-xl">{{ selectedUsuario.email || 'N/A' }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-slate-700">Estado</label>
                      <span :class="obtenerClasesEstado(selectedUsuario.activo ? 'activo' : 'inactivo')" class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-medium mt-1">
                        {{ selectedUsuario.activo ? 'Activo' : 'Inactivo' }}
                      </span>
                    </div>
                  </div>
                  <div class="space-y-3">
                    <div>
                      <label class="block text-sm font-medium text-slate-700">Rol(es)</label>
                      <div class="mt-1 flex flex-wrap gap-1">
                        <span v-for="rol in selectedUsuario.roles" :key="rol.id" 
                              class="inline-flex items-center px-2 py-0.5 rounded-xl text-xs font-medium bg-slate-100 text-slate-800">
                          {{ rol.name }}
                        </span>
                        <span v-if="!selectedUsuario.roles || selectedUsuario.roles.length === 0" class="text-sm text-slate-500 italic px-3 py-2 bg-white rounded-xl block w-full">
                          Sin rol asignado
                        </span>
                      </div>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-slate-700">Fecha de creación</label>
                      <p class="mt-1 text-sm text-slate-900 bg-white px-3 py-2 rounded-xl">{{ formatearFecha(selectedUsuario.created_at) }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-slate-700">Última actualización</label>
                      <p class="mt-1 text-sm text-slate-900 bg-white px-3 py-2 rounded-xl">{{ formatearFecha(selectedUsuario.updated_at) }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="modalMode === 'confirm'">
              <div class="text-center">
                <div class="w-10 h-10 mx-auto bg-rose-100 rounded-full flex items-center justify-center mb-4">
                  <svg class="w-10 h-10 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                  </svg>
                </div>
                <h3 class="text-lg font-medium text-slate-900 mb-2">¿Eliminar Usuario?</h3>
                <p class="text-sm text-slate-500 mb-4">
                  ¿Estás seguro de que deseas eliminar el usuario <strong>{{ selectedUsuario?.name }}</strong>?
                  Esta acción no se puede deshacer.
                </p>
              </div>
            </div>
          </div>

          <!-- Footer del modal -->
          <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-200 bg-white">
            <button @click="showModal = false" class="px-4 py-2 bg-slate-300 text-slate-700 rounded-xl hover:bg-slate-400 transition-colors">
              {{ modalMode === 'details' ? 'Cerrar' : 'Cancelar' }}
            </button>
            <div v-if="modalMode === 'details'" class="flex gap-2">
              <button v-if="$can('edit usuarios')" @click="toggleUsuario(selectedUsuario.id)" class="px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition-colors">
                Cambiar Estado
              </button>
              <button v-if="$can('edit usuarios')" @click="editarUsuario(selectedUsuario.id)" class="px-4 py-2 bg-brand-600 text-white rounded-xl hover:bg-brand-700 transition-colors">
                Editar
              </button>
            </div>
            <button v-if="modalMode === 'confirm' && $can('delete usuarios')" @click="eliminarUsuario" class="px-4 py-2 bg-rose-600 text-white rounded-xl hover:bg-rose-700 transition-colors">
              Eliminar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.usuarios-index {
  min-height: 100vh;
}

.usuarios-premium .text-slate-900 {
  color: #0f172a !important;
}

.dark .usuarios-premium .text-slate-900 {
  color: #f8fafc !important;
}

.usuarios-premium .text-slate-700,
.usuarios-premium .text-slate-500,
.usuarios-premium .text-slate-500 {
  color: #475569 !important;
}

.dark .usuarios-premium .text-slate-700,
.dark .usuarios-premium .text-slate-500,
.dark .usuarios-premium .text-slate-500 {
  color: #94a3b8 !important;
}

.usuarios-premium .border-slate-100,
.usuarios-premium .border-slate-200,
.usuarios-premium .border-slate-300 {
  border-color: rgba(148, 163, 184, 0.35) !important;
}

.dark .usuarios-premium .border-slate-100,
.dark .usuarios-premium .border-slate-200,
.dark .usuarios-premium .border-slate-300 {
  border-color: rgba(51, 65, 85, 0.9) !important;
}

.usuarios-premium .bg-white {
  background-color: rgba(255, 255, 255, 0.88) !important;
}

.dark .usuarios-premium .bg-white {
  background-color: rgba(15, 23, 42, 0.72) !important;
}
</style>




