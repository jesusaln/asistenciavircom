<!-- /resources/js/Pages/Usuarios/IndexNew.vue -->
<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import { useCompanyColors } from '@/Composables/useCompanyColors'

import UsuariosHeader from '@/Components/IndexComponents/UsuariosHeader.vue'

defineOptions({ layout: AppLayout })

// Estado reactivo para Modo Oscuro
const isDark = ref(false)
let observer = null

onMounted(() => {
  isDark.value = document.documentElement.classList.contains('dark')
  observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      if (mutation.attributeName === 'class') {
        isDark.value = document.documentElement.classList.contains('dark')
      }
    })
  })
  observer.observe(document.documentElement, { attributes: true })
})

onBeforeUnmount(() => {
  if (observer) observer.disconnect()
})

// Colores de empresa
const { cssVars, colors } = useCompanyColors()

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

onMounted(() => {
  const flash = page.props.flash
  if (flash?.success) notyf.success(flash.success)
  if (flash?.error) notyf.error(flash.error)
})

const page = usePage()

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
      usuario.roles && usuario.roles.some(role => ['admin', 'administrador', 'super-admin'].includes(role.name))
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
  current_page: usuariosPaginator.value?.current_page || 1,
  last_page: usuariosPaginator.value?.last_page || 1,
  per_page: usuariosPaginator.value?.per_page || 10,
  from: usuariosPaginator.value?.from || 0,
  to: usuariosPaginator.value?.to || 0,
  total: usuariosPaginator.value?.total || 0,
  prev_page_url: usuariosPaginator.value?.prev_page_url,
  next_page_url: usuariosPaginator.value?.next_page_url,
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
  <Head title="Usuarios" />
  
  <div class="usuarios-index min-h-screen bg-white dark:bg-slate-900 dark:bg-gray-900 transition-colors" :style="cssVars">
    <div class="w-full px-4 lg:px-8 py-8 transition-all">
      
      <!-- Header Area -->
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
        @crear-nueva="crearNuevoUsuario"
        @search-change="handleSearchChange"
        @filtro-estado-change="handleEstadoChange"
        @filtro-rol-change="handleRolChange"
        @sort-change="handleSortChange"
        @limpiar-filtros="limpiarFiltros"
      />

      <!-- Información de paginación -->
      <div class="flex justify-between items-center mb-4 text-sm text-gray-600 dark:text-gray-300 dark:text-gray-400 transition-colors">
        <div>
          Mostrando {{ paginationData.from }} - {{ paginationData.to }} de {{ paginationData.total }} usuarios
        </div>
        <div class="flex items-center space-x-2">
          <span>Elementos por página:</span>
          <select
            :value="paginationData.per_page"
            @change="handlePerPageChange"
            class="border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded px-2 py-1 text-sm transition-colors"
          >
            <option value="10">10</option>
            <option value="15">15</option>
            <option value="25">25</option>
            <option value="50">50</option>
          </select>
        </div>
      </div>

      <!-- Tabla de usuarios -->
      <div class="mt-6">
        <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden transition-colors">
          <!-- Header con gradiente de empresa -->
          <div 
            class="px-6 py-4 border-b border-gray-200 dark:border-slate-800/60 transition-colors" 
            :style="{ background: isDark ? 'linear-gradient(135deg, #1f2937 0%, #111827 100%)' : `linear-gradient(135deg, ${colors.principal}15 0%, ${colors.secundario}10 100%)` }"
          >
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-white tracking-tight flex items-center gap-2 transition-colors">
                <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: colors.principal }"></span>
                Lista de Usuarios
              </h2>
              <div class="text-sm font-medium px-3 py-1 rounded-full border transition-colors" :style="isDark ? { backgroundColor: '#1f2937', color: '#e5e7eb', borderColor: '#374151' } : { backgroundColor: `${colors.principal}10`, color: colors.principal, borderColor: `${colors.principal}30` }">
                {{ usuariosData.length }} de {{ estadisticas.total }} registros
              </div>
            </div>
          </div>

          <!-- Table -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800/60">
              <thead class="bg-white dark:bg-slate-900/60 dark:bg-gray-900/60 transition-colors">
                <tr>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-400 uppercase tracking-wider">Usuario & Perfil</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-400 uppercase tracking-wider">Email</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-400 uppercase tracking-wider text-center">Roles</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-400 uppercase tracking-wider text-center">Estado</th>
                  <th v-if="$can('edit usuarios') || $can('delete usuarios') || $can('view usuarios')" class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
                </tr>
              </thead>
            <tbody class="bg-white dark:bg-slate-900 dark:bg-gray-800 divide-y divide-gray-200 dark:divide-slate-800/40 dark:divide-gray-700/40 transition-colors">
              <tr 
                v-for="usuario in usuariosDocumentos" 
                :key="usuario.id" 
                class="group hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-all duration-150 relative"
              >
                <!-- Usuario -->
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="relative w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center overflow-hidden border border-gray-200 dark:border-gray-600">
                      <img v-if="usuario.raw.profile_photo_url" :src="usuario.raw.profile_photo_url" class="w-full h-full object-cover">
                      <span v-else class="text-sm font-bold text-gray-500 dark:text-gray-400">{{ usuario.titulo.charAt(0) }}</span>
                    </div>
                    <div>
                      <div class="text-sm font-medium text-gray-900 dark:text-white">{{ usuario.titulo }}</div>
                      <div class="text-xs text-gray-500 dark:text-gray-400">ID: #{{ usuario.id }}</div>
                    </div>
                  </div>
                </td>
                
                <!-- Email -->
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-700 dark:text-gray-300">{{ usuario.subtitulo }}</div>
                  <div class="text-xs text-gray-400 dark:text-gray-500">{{ formatearFecha(usuario.fecha) }}</div>
                </td>
                
                <!-- Roles -->
                <td class="px-6 py-4 text-center">
                  <div class="flex flex-wrap gap-1 justify-center">
                    <span v-for="rol in usuario.raw.roles" :key="rol.id" 
                          class="px-2.5 py-0.5 rounded-full text-xs font-medium border"
                          :class="{
                            'bg-indigo-50 text-indigo-700 border-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-300 dark:border-indigo-800': ['admin', 'super-admin'].includes(rol.name),
                            'bg-blue-50 text-blue-700 border-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800': ['vendedor', 'ventas'].includes(rol.name),
                            'bg-green-50 text-green-700 border-green-100 dark:bg-green-900/30 dark:text-green-300 dark:border-green-800': ['cobranza'].includes(rol.name),
                            'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800': ['tecnico'].includes(rol.name),
                            'bg-gray-50 text-gray-700 border-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700': !['admin', 'super-admin', 'vendedor', 'cobranza', 'tecnico', 'ventas'].includes(rol.name)
                          }">
                      {{ rol.name }}
                    </span>
                    <span v-if="!usuario.raw.roles || usuario.raw.roles.length === 0" class="text-xs text-gray-400 italic">
                      Sin roles
                    </span>
                  </div>
                </td>

                <!-- Estado -->
                <td class="px-6 py-4 text-center">
                  <span :class="[
                      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                      usuario.estado === 'activo' 
                        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' 
                        : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
                    ]">
                    <span :class="['w-1.5 h-1.5 rounded-full mr-1.5', usuario.estado === 'activo' ? 'bg-green-500' : 'bg-red-500']"></span>
                    {{ obtenerLabelEstado(usuario.estado) }}
                  </span>
                </td>

                <!-- Acciones -->
                <td v-if="$can('edit usuarios') || $can('delete usuarios') || $can('view usuarios')" class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button v-if="$can('view usuarios')" @click="verDetalles(usuario)" 
                            class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 dark:hover:text-blue-400 transition-colors"
                            title="Ver detalles">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>

                    <button v-if="$can('edit usuarios')" @click="editarUsuario(usuario.id)" 
                            class="p-1.5 rounded-lg text-gray-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/30 dark:hover:text-amber-400 transition-colors"
                            title="Editar">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>

                    <button v-if="$can('edit usuarios')" @click="toggleUsuario(usuario.id)" 
                            class="p-1.5 rounded-lg text-gray-400 hover:text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/30 dark:hover:text-purple-400 transition-colors"
                            title="Alternar Estado">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                      </svg>
                    </button>

                    <button v-if="$can('delete usuarios')" @click="confirmarEliminacion(usuario.id)" 
                            class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 dark:hover:text-red-400 transition-colors"
                            title="Eliminar">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>

              <!-- Empty State -->
              <tr v-if="usuariosDocumentos.length === 0">
                <td colspan="5" class="px-6 py-12 text-center">
                  <div class="flex flex-col items-center justify-center">
                     <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                     </div>
                     <h3 class="text-sm font-medium text-gray-900 dark:text-white">No se encontraron usuarios</h3>
                     <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm mt-1">Intenta ajustar los filtros de búsqueda o crea un nuevo usuario.</p>
                     <button @click="crearNuevoUsuario" class="mt-4 text-sm text-[var(--color-primary)] font-semibold hover:underline">
                        Crear nuevo usuario
                     </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Paginación Inferior (Simple) -->
      <div v-if="paginationData.last_page > 1" class="mt-6 flex justify-end">
        <nav class="flex items-center gap-2">
          <button 
            v-if="paginationData.prev_page_url"
            @click="handlePageChange(paginationData.current_page - 1)"
            class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition-colors"
          >
            Anterior
          </button>
          
          <span class="text-sm text-gray-600 dark:text-gray-400 px-2">
            Página {{ paginationData.current_page }} de {{ paginationData.last_page }}
          </span>

          <button 
            v-if="paginationData.next_page_url"
            @click="handlePageChange(paginationData.current_page + 1)"
            class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition-colors"
          >
            Siguiente
          </button>
        </nav>
      </div>

    </div>
  </div>
    <!-- Modal de detalles / confirmación -->
    <Transition name="modal">
      <div
        v-if="showModal"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
        @click.self="showModal = false"
      >
        <div
          class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6 outline-none transition-colors"
          role="dialog"
          aria-modal="true"
          :aria-label="`Modal de Usuario`"
          tabindex="-1"
          ref="modalRef"
          @keydown.esc.prevent="showModal = false"
        >
          <!-- Modo: Confirmación de eliminación -->
          <div v-if="modalMode === 'confirm'" class="text-center">
            <div class="w-12 h-12 mx-auto bg-red-100 rounded-full flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"
                />
              </svg>
            </div>
            <h3 class="text-lg font-medium mb-2">
              ¿Eliminar usuario?
            </h3>
            <p class="text-gray-600 dark:text-gray-300 mb-6">
              Esta acción no se puede deshacer y el usuario perderá acceso al sistema permanentemente.
            </p>
            <div class="flex gap-3">
              <button
                @click="showModal = false"
                class="flex-1 px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors"
              >
                Cancelar
              </button>
              <button
                @click="eliminarUsuario"
                class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
              >
                Eliminar
              </button>
            </div>
          </div>

          <!-- Modo: Detalles -->
          <div v-else-if="modalMode === 'details' && selectedUsuario" class="space-y-4">
            <h3 class="text-lg font-medium mb-1 flex items-center gap-2">
              Detalles de Usuario
              <span v-if="selectedUsuario?.id" class="text-sm text-gray-500 dark:text-gray-400">#{{ selectedUsuario.id }}</span>
            </h3>

            <div class="space-y-4">
              <!-- Información general -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <p class="text-sm text-gray-600 dark:text-gray-300">
                    <strong>Nombre:</strong> {{ selectedUsuario.titulo || 'Sin nombre' }}
                  </p>
                  <p class="text-sm text-gray-600 dark:text-gray-300">
                    <strong>Email:</strong> {{ selectedUsuario.subtitulo || 'N/A' }}
                  </p>
                  <p class="text-sm text-gray-600 dark:text-gray-300">
                     <strong>Fecha Alta:</strong> {{ formatearFecha(selectedUsuario.fecha) }}
                  </p>
                  <p class="text-sm text-gray-600 dark:text-gray-300 mt-2">
                    <strong>Estado:</strong>
                    <span
                      :class="obtenerClasesEstado(selectedUsuario.activo ? 'activo' : 'inactivo')"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ml-2"
                    >
                      {{ obtenerLabelEstado(selectedUsuario.activo ? 'activo' : 'inactivo') }}
                    </span>
                  </p>
                </div>

                <div>
                   <div class="relative w-24 h-24 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center overflow-hidden border border-gray-200 dark:border-gray-600 mx-auto sm:mx-0">
                      <img v-if="selectedUsuario.raw?.profile_photo_url" :src="selectedUsuario.raw.profile_photo_url" class="w-full h-full object-cover">
                      <span v-else class="text-3xl font-bold text-gray-500 dark:text-gray-400">{{ selectedUsuario.titulo?.charAt(0) }}</span>
                    </div>
                </div>
              </div>

              <!-- Roles -->
              <div class="pt-4 border-t border-gray-200 dark:border-slate-800 dark:border-gray-700 transition-colors">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white dark:text-white mb-3">Roles y Permisos</h4>
                <div class="flex flex-wrap gap-2">
                   <span v-for="rol in selectedUsuario.raw?.roles" :key="rol.id" 
                          class="px-2.5 py-0.5 rounded-full text-xs font-medium border bg-gray-50 text-gray-700 border-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700"
                   >
                      {{ rol.name }}
                   </span>
                   <span v-if="!selectedUsuario.raw?.roles?.length" class="text-xs text-gray-500 italic">Sin roles asignados</span>
                </div>
              </div>
            </div>

            <!-- Botones de acción -->
            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-slate-800">
              <button
                @click="showModal = false"
                class="inline-flex items-center px-4 py-2 bg-white dark:bg-slate-900 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-600 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500/50 focus:ring-offset-1"
              >
                Cerrar
              </button>

              <button
                v-if="$can('edit usuarios')"
                @click="editarUsuario(selectedUsuario.id)"
                class="inline-flex items-center px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 hover:shadow-md transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:ring-offset-1"
              >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Editar Usuario
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Loading overlay -->
    <div v-if="loading" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 transition-all">
      <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 p-6 rounded-xl shadow-xl transition-colors">
        <div class="flex items-center space-x-3">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2" :style="{ borderColor: colors.principal }"></div>
          <span class="text-gray-700 dark:text-gray-200 font-medium transition-colors">Procesando...</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.scale-in {
  animation: scale-in 0.2s ease-out;
}

@keyframes scale-in {
  from { transform: scale(0.95); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}

select {
  background-image: url('data:image/svg+xml,%3csvg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20"%3e%3cpath stroke="%2364748b" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 8l4 4 4-4"/%3e%3c/svg%3e');
  background-position: right 1rem center;
  background-repeat: no-repeat;
  background-size: 1.5em 1.5em;
}

.dark select {
  background-image: url('data:image/svg+xml,%3csvg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20"%3e%3cpath stroke="%2394a3b8" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 8l4 4 4-4"/%3e%3c/svg%3e');
}

/* Transiciones suaves para todo */
div, button, input, select, span {
  transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
}

.usuarios-index {
  min-height: 100vh;
}
</style>




