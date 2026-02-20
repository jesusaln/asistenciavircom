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
const loading = ref(false)
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
let searchTimeout = null
function handleSearchChange(newSearch) {
  searchTerm.value = newSearch
  if (searchTimeout) clearTimeout(searchTimeout)
  
  searchTimeout = setTimeout(() => {
    loading.value = true
    router.get(route('usuarios.index'), {
      search: newSearch,
      sort_by: sortBy.value.split('-')[0],
      sort_direction: sortBy.value.split('-')[1] || 'desc',
      activo: filtroEstado.value,
      per_page: perPage.value,
      page: 1
    }, { 
      preserveState: true, 
      preserveScroll: true,
      onFinish: () => { loading.value = false }
    })
  }, 500)
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
  
  <div class="usuarios-index min-h-screen bg-slate-50 dark:bg-slate-950 transition-colors duration-300" :style="cssVars">
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

      <!-- Información de paginación Premium -->
      <div class="flex justify-between items-center mt-8 mb-4">
        <div class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">
          Mostrando {{ paginationData.from }} - {{ paginationData.to }} de {{ paginationData.total }} usuarios registrados
        </div>
        <div class="flex items-center space-x-3 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-xl border border-gray-100 dark:border-slate-800 shadow-sm">
          <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Filas:</span>
          <select
            :value="paginationData.per_page"
            @change="handlePerPageChange"
            class="bg-transparent border-none text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-0 cursor-pointer pr-8"
          >
            <option value="10">10</option>
            <option value="15">15</option>
            <option value="25">25</option>
            <option value="50">50</option>
          </select>
        </div>
      </div>

      <!-- Tabla de usuarios Premium -->
      <div class="bg-white dark:bg-slate-900/40 dark:backdrop-blur-xl rounded-2xl shadow-xl border border-gray-100 dark:border-slate-800/60 overflow-hidden ring-1 ring-black/5 dark:ring-white/5 transition-all duration-300">
        <!-- Table -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-100 dark:divide-slate-800/60">
            <thead class="bg-slate-50/50 dark:bg-slate-950/50">
              <tr>
                <th class="px-6 py-5 text-left text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">Usuario y Perfil</th>
                <th class="px-6 py-5 text-left text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">Contacto</th>
                <th class="px-6 py-5 text-left text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest text-center">Roles Asignados</th>
                <th class="px-6 py-5 text-left text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest text-center">Estado</th>
                <th v-if="$can('edit usuarios') || $can('delete usuarios') || $can('view usuarios')" class="px-6 py-5 text-right text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-800/40 transition-colors">
              <tr 
                v-for="usuario in usuariosDocumentos" 
                :key="usuario.id" 
                class="group hover:bg-slate-50/80 dark:hover:bg-slate-800/30 transition-all duration-200 border-l-4 border-transparent hover:border-blue-500"
              >
                <!-- Usuario -->
                <td class="px-6 py-5">
                  <div class="flex items-center gap-4">
                    <div class="relative group/avatar">
                      <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center overflow-hidden border-2 border-white dark:border-slate-700 shadow-md group-hover/avatar:scale-105 transition-all duration-300">
                        <img v-if="usuario.raw.profile_photo_url" :src="usuario.raw.profile_photo_url" class="w-full h-full object-cover">
                        <span v-else class="text-lg font-black text-slate-400">{{ usuario.titulo.charAt(0) }}</span>
                      </div>
                      <div v-if="usuario.estado === 'activo'" class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 border-2 border-white dark:border-slate-900 rounded-full shadow-sm"></div>
                    </div>
                    <div>
                      <div class="text-sm font-black text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{{ usuario.titulo }}</div>
                      <div class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">ID Sistema: <span class="text-slate-500 dark:text-slate-300">#{{ usuario.id }}</span></div>
                    </div>
                  </div>
                </td>
                
                <!-- Email -->
                <td class="px-6 py-5">
                  <div class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ usuario.subtitulo }}</div>
                  <div class="flex items-center gap-1.5 mt-1 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    {{ formatearFecha(usuario.fecha) }}
                  </div>
                </td>
                
                <!-- Roles -->
                <td class="px-6 py-5 text-center">
                  <div class="flex flex-wrap gap-1.5 justify-center">
                    <span v-for="rol in usuario.raw.roles" :key="rol.id" 
                          class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-widest border shadow-sm transition-all"
                          :class="{
                            'bg-indigo-50 text-indigo-700 border-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-400 dark:border-indigo-500/20': ['admin', 'super-admin'].includes(rol.name),
                            'bg-blue-50 text-blue-700 border-blue-100 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20': ['vendedor', 'ventas'].includes(rol.name),
                            'bg-green-50 text-green-700 border-green-100 dark:bg-green-500/10 dark:text-green-400 dark:border-green-500/20': ['cobranza'].includes(rol.name),
                            'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20': ['tecnico'].includes(rol.name),
                            'bg-slate-50 text-slate-600 border-slate-100 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700': !['admin', 'super-admin', 'vendedor', 'cobranza', 'tecnico', 'ventas'].includes(rol.name)
                          }">
                      {{ rol.name }}
                    </span>
                    <span v-if="!usuario.raw.roles || usuario.raw.roles.length === 0" class="text-[10px] text-slate-400 font-bold italic uppercase tracking-widest">
                      Sin Acceso
                    </span>
                  </div>
                </td>

                <!-- Estado -->
                <td class="px-6 py-5 text-center">
                  <span :class="[
                      'inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border shadow-sm',
                      usuario.estado === 'activo' 
                        ? 'bg-green-50 text-green-700 border-green-100 dark:bg-green-500/10 dark:text-green-400 dark:border-green-500/20' 
                        : 'bg-rose-50 text-rose-700 border-rose-100 dark:bg-rose-500/10 dark:text-rose-400 dark:border-rose-500/20'
                    ]">
                    <span :class="['w-1.5 h-1.5 rounded-full mr-2', usuario.estado === 'activo' ? 'bg-green-500' : 'bg-rose-500']"></span>
                    {{ obtenerLabelEstado(usuario.estado) }}
                  </span>
                </td>

                <!-- Acciones -->
                <td v-if="$can('edit usuarios') || $can('delete usuarios') || $can('view usuarios')" class="px-6 py-5 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button v-if="$can('view usuarios')" @click="verDetalles(usuario)" 
                            class="w-9 h-9 flex items-center justify-center rounded-xl text-slate-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-500/10 dark:hover:text-blue-400 transition-all duration-200 border border-transparent hover:border-blue-200 dark:hover:border-blue-500/30"
                            title="Ver Perfil">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>

                    <button v-if="$can('edit usuarios')" @click="editarUsuario(usuario.id)" 
                            class="w-9 h-9 flex items-center justify-center rounded-xl text-slate-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-500/10 dark:hover:text-amber-400 transition-all duration-200 border border-transparent hover:border-amber-200 dark:hover:border-amber-500/30"
                            title="Modificar">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>

                    <button v-if="$can('edit usuarios')" @click="toggleUsuario(usuario.id)" 
                            class="w-9 h-9 flex items-center justify-center rounded-xl text-slate-400 hover:text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-500/10 dark:hover:text-purple-400 transition-all duration-200 border border-transparent hover:border-purple-200 dark:hover:border-purple-500/30"
                            :title="usuario.estado === 'activo' ? 'Suspender' : 'Reactivar'">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                      </svg>
                    </button>

                    <button v-if="$can('delete usuarios')" @click="confirmarEliminacion(usuario.id)" 
                            class="w-9 h-9 flex items-center justify-center rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-500/10 dark:hover:text-rose-400 transition-all duration-200 border border-transparent hover:border-rose-200 dark:hover:border-rose-500/30"
                            title="Eliminar Permanente">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>

              <!-- Empty State -->
              <tr v-if="usuariosDocumentos.length === 0">
                <td colspan="5" class="px-6 py-24 text-center">
                  <div class="flex flex-col items-center justify-center">
                     <div class="w-20 h-20 bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-3xl flex items-center justify-center mb-6 shadow-sm">
                        <svg class="w-10 h-10 text-slate-400 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                     </div>
                     <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-widest">No hay usuarios</h3>
                     <p class="text-sm font-medium text-slate-500 dark:text-slate-400 max-w-sm mt-2">No se encontraron registros que coincidan con los criterios de búsqueda actuales.</p>
                     <button @click="crearNuevoUsuario" class="mt-8 px-6 py-3 bg-blue-600 text-white text-xs font-black uppercase tracking-widest rounded-xl hover:bg-blue-700 transition-all shadow-lg hover:shadow-blue-500/20">
                        Crear nuevo usuario ahora
                     </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Paginación Inferior Premium -->
      <div v-if="paginationData.last_page > 1" class="mt-10 flex justify-center">
        <nav class="flex items-center gap-2 bg-white dark:bg-slate-900 p-1.5 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-800 transition-all duration-300">
          <button 
            v-if="paginationData.current_page > 1"
            @click="handlePageChange(paginationData.current_page - 1)"
            class="w-10 h-10 flex items-center justify-center rounded-xl text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-blue-600 transition-all border border-transparent hover:border-slate-200 dark:hover:border-slate-700"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
          </button>
          
          <div class="flex items-center px-4">
            <span class="text-xs font-black uppercase tracking-widest text-slate-400">Página</span>
            <span class="mx-2 text-sm font-black text-blue-600 dark:text-blue-400">{{ paginationData.current_page }}</span>
            <span class="text-xs font-black uppercase tracking-widest text-slate-400">de {{ paginationData.last_page }}</span>
          </div>

          <button 
            v-if="paginationData.current_page < paginationData.last_page"
            @click="handlePageChange(paginationData.current_page + 1)"
            class="w-10 h-10 flex items-center justify-center rounded-xl text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-blue-600 transition-all border border-transparent hover:border-slate-200 dark:hover:border-slate-700"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
          </button>
        </nav>
      </div>

    </div>

    <!-- Modal de detalles / confirmación Premium -->
    <Transition name="modal">
      <div
        v-if="showModal"
        class="fixed inset-0 bg-slate-950/80 backdrop-blur-md flex items-center justify-center z-50 p-4"
        @click.self="showModal = false"
      >
        <div
          class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden border border-gray-100 dark:border-slate-800 transition-all duration-300"
          role="dialog"
          aria-modal="true"
          ref="modalRef"
          @keydown.esc.prevent="showModal = false"
        >
          <!-- Modo: Confirmación de eliminación -->
          <div v-if="modalMode === 'confirm'" class="p-8 text-center">
            <div class="w-20 h-20 mx-auto bg-rose-50 dark:bg-rose-500/10 rounded-3xl flex items-center justify-center mb-6 shadow-inner">
              <svg class="w-10 h-10 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
              </svg>
            </div>
            <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight mb-2">Eliminar Usuario</h3>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-8 max-w-sm mx-auto">
              Esta acción es irreversible y retirará todos los privilegios del sistema para este usuario permanentemente.
            </p>
            <div class="flex gap-4">
              <button
                @click="showModal = false"
                class="flex-1 px-6 py-3 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-black uppercase tracking-widest rounded-2xl hover:bg-slate-200 dark:hover:bg-slate-700 transition-all border border-transparent hover:border-slate-300 shadow-sm"
              >
                Mantener
              </button>
              <button
                @click="eliminarUsuario"
                class="flex-1 px-6 py-3 bg-rose-600 text-white text-xs font-black uppercase tracking-widest rounded-2xl hover:bg-rose-700 transition-all shadow-lg shadow-rose-500/20"
              >
                Eliminar Ahora
              </button>
            </div>
          </div>

          <!-- Modo: Detalles Premium -->
          <div v-else-if="modalMode === 'details' && selectedUsuario" class="flex flex-col h-full">
            <div class="p-8 pb-4 border-b border-gray-100 dark:border-slate-800 flex justify-between items-start">
              <div>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight flex items-center gap-3">
                  Perfil de Usuario
                  <span class="text-xs font-bold text-slate-400 bg-slate-50 dark:bg-slate-800 px-3 py-1 rounded-lg">#ID: {{ selectedUsuario.id }}</span>
                </h3>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mt-1">Información detallada y niveles de acceso</p>
              </div>
              <button @click="showModal = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
            </div>

            <div class="p-8 space-y-8 overflow-y-auto">
              <!-- Información general -->
              <div class="grid grid-cols-1 md:flex items-start gap-8">
                <div class="flex-shrink-0">
                  <div class="relative group/modalavatar">
                    <div class="w-32 h-32 rounded-[2.5rem] bg-slate-100 dark:bg-slate-800 flex items-center justify-center overflow-hidden border-4 border-white dark:border-slate-700 shadow-xl group-hover/modalavatar:scale-105 transition-all duration-500">
                      <img v-if="selectedUsuario.raw?.profile_photo_url" :src="selectedUsuario.raw.profile_photo_url" class="w-full h-full object-cover">
                      <span v-else class="text-4xl font-black text-slate-300">{{ selectedUsuario.titulo?.charAt(0) }}</span>
                    </div>
                    <div class="absolute -bottom-2 -right-2 w-10 h-10 border-4 border-white dark:border-slate-900 rounded-2xl shadow-lg flex items-center justify-center" :class="selectedUsuario.activo ? 'bg-green-500' : 'bg-rose-500'">
                      <svg v-if="selectedUsuario.activo" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                      <svg v-else class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" /></svg>
                    </div>
                  </div>
                </div>

                <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-6">
                  <div class="space-y-1">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Nombre Completo</p>
                    <p class="text-lg font-black text-slate-900 dark:text-white">{{ selectedUsuario.titulo || 'Sin identificar' }}</p>
                  </div>
                  <div class="space-y-1">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Correo Electrónico</p>
                    <p class="text-lg font-bold text-blue-600 dark:text-blue-400 truncate">{{ selectedUsuario.subtitulo || 'N/A' }}</p>
                  </div>
                  <div class="space-y-1">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Dado de Alta el</p>
                    <p class="text-md font-bold text-slate-700 dark:text-slate-300">{{ formatearFecha(selectedUsuario.fecha) }}</p>
                  </div>
                  <div class="space-y-1">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Estado Actual</p>
                    <div class="flex items-center gap-2 mt-1">
                      <span :class="obtenerClasesEstado(selectedUsuario.activo ? 'activo' : 'inactivo')" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border border-current opacity-80 shadow-sm">
                        {{ obtenerLabelEstado(selectedUsuario.activo ? 'activo' : 'inactivo') }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Roles / Permisos -->
              <div class="bg-slate-50 dark:bg-slate-950/50 p-6 rounded-3xl border border-slate-100 dark:border-slate-800/80">
                <div class="flex items-center gap-2 mb-4">
                  <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-500/20 flex items-center justify-center text-blue-600 dark:text-blue-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                  </div>
                  <h4 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest">Capacidades del Sistema</h4>
                </div>
                
                <div class="flex flex-wrap gap-2">
                   <span v-for="rol in selectedUsuario.raw?.roles" :key="rol.id" 
                          class="px-4 py-2 rounded-2xl text-xs font-black uppercase tracking-widest border bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border-slate-100 dark:border-slate-800 shadow-sm"
                   >
                      {{ rol.name }}
                   </span>
                   <div v-if="!selectedUsuario.raw?.roles?.length" class="flex flex-col items-center py-4 text-center w-full">
                      <p class="text-xs font-black text-rose-500 uppercase tracking-widest italic opacity-60">¡Alerta: Usuario sin privilegios asignados!</p>
                   </div>
                </div>
              </div>
            </div>

            <!-- Botones de acción Modal Premium -->
            <div class="p-8 bg-slate-50 dark:bg-slate-950/30 border-t border-gray-100 dark:border-slate-800 flex justify-end gap-3 mt-auto">
              <button
                @click="showModal = false"
                class="px-6 py-3 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 text-xs font-black uppercase tracking-widest rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-all border border-slate-200 dark:border-slate-700 shadow-sm"
              >
                Cerrar Perfil
              </button>

              <button
                v-if="$can('edit usuarios')"
                @click="editarUsuario(selectedUsuario.id)"
                class="inline-flex items-center px-6 py-3 bg-amber-500 text-white text-xs font-black uppercase tracking-widest rounded-2xl hover:bg-amber-600 hover:shadow-xl hover:shadow-amber-500/20 transition-all duration-300 transform hover:-translate-y-1 active:translate-y-0"
              >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Actualizar Datos
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Loading overlay Premium -->
    <div v-if="loading" class="fixed inset-0 bg-slate-950/80 backdrop-blur-xl flex flex-col items-center justify-center z-[100] transition-all duration-500">
      <div class="relative">
        <div class="h-24 w-24 rounded-3xl border-4 border-slate-800 border-t-blue-500 animate-spin"></div>
        <div class="absolute inset-0 flex items-center justify-center">
          <div class="h-12 w-12 rounded-2xl bg-slate-900 border-2 border-slate-700"></div>
        </div>
      </div>
      <p class="mt-8 text-xs font-black text-white uppercase tracking-[0.3em] animate-pulse">Sincronizando Usuarios...</p>
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




