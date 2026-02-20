<template>
  <div class="bg-white dark:bg-slate-900/40 dark:backdrop-blur-xl rounded-2xl shadow-xl border border-gray-100 dark:border-slate-800/60 overflow-hidden transition-all duration-300 ring-1 ring-black/5 dark:ring-white/5">
    <!-- Header con estadísticas -->
    <div 
      class="px-6 py-6 border-b border-gray-200 dark:border-slate-800/40 transition-colors" 
      :style="{ background: isDark ? 'linear-gradient(135deg, rgba(15, 23, 42, 0.8) 0%, rgba(2, 6, 23, 0.8) 100%)' : `linear-gradient(135deg, ${colors.principal}15 0%, ${colors.secundario}10 100%)` }"
    >
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-lg transform hover:scale-110 transition-all duration-300" :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
          </div>
          <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight transition-colors">Usuarios</h1>
            <p class="text-sm font-medium text-gray-500 dark:text-slate-400 mt-0.5 transition-colors">Gestión y control de acceso del sistema</p>
          </div>
        </div>
        <button
          v-if="$can('create usuarios')"
          @click="onCrearNueva"
          class="inline-flex items-center justify-center px-6 py-3 text-white text-sm font-bold rounded-xl shadow-lg hover:shadow-2xl focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-300 transform hover:-translate-y-1 active:translate-y-0"
          :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)`, '--tw-ring-color': colors.principal }"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
          </svg>
          Registrar Nuevo Usuario
        </button>
      </div>

      <!-- Estadísticas Modernas -->
      <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div 
          v-for="(stat, idx) in [
            { label: 'Total Usuarios', value: total, color: colors.principal, icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
            { label: 'En Operación', value: activos, color: '#10b981', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
            { label: 'Suspendidos', value: inactivos, color: '#f43f5e', icon: 'M13 7a4 4 0 11-8 0 4 4 0 018 0M9 21v-1a6 6 0 0112 0v1M18 13h4' },
            { label: 'Administradores', value: administradores, color: '#8b5cf6', icon: 'M9 12l2 2 4-4M12 2a11.95 11.95 0 018.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622' },
            { label: 'Seguridad 2FA', value: con2FA, color: '#f59e0b', icon: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z' }
          ]"
          :key="idx"
          class="bg-white/60 dark:bg-slate-900/40 backdrop-blur-md rounded-2xl p-5 border border-gray-100 dark:border-slate-800/40 shadow-sm transition-all duration-300 hover:shadow-md group"
        >
          <div class="flex flex-col gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center transition-all duration-300 group-hover:scale-110" :style="{ backgroundColor: isDark ? 'rgba(30, 41, 59, 0.5)' : `${stat.color}15` }">
              <svg class="w-5 h-5" :style="{ color: stat.color }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="stat.icon" />
              </svg>
            </div>
            <div>
              <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 dark:text-slate-400 opacity-80">{{ stat.label }}</p>
              <p class="text-2xl font-black dark:text-white transition-colors mt-1">{{ stat.value }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filtros y búsqueda Premium -->
    <div class="px-6 py-5 bg-gray-50/50 dark:bg-slate-900/60 transition-colors">
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
        <!-- Búsqueda -->
        <div class="flex-1 max-w-2xl">
          <div class="relative group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-gray-400 group-focus-within:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input
              v-model="searchTerm"
              type="text"
              placeholder="Buscar por nombre, correo o ID..."
              class="block w-full pl-12 pr-4 py-3 bg-white dark:bg-slate-950 border border-gray-200 dark:border-slate-800/80 rounded-2xl text-sm font-medium placeholder-gray-400 dark:placeholder-slate-600 dark:text-white focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500/50 transition-all duration-300 shadow-sm group-hover:shadow-md"
              @input="onSearchChange"
            />
          </div>
        </div>

        <!-- Filtros Group -->
        <div class="flex flex-wrap items-center gap-3">
          <div class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-slate-950 rounded-2xl border border-gray-200 dark:border-slate-800/80 shadow-sm">
            <span class="text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-tight pl-1">Filtrar:</span>
            
            <!-- Roles -->
            <select
              v-model="filtroRol"
              @change="onFiltroRolChange"
              class="bg-transparent border-none text-xs font-bold text-gray-700 dark:text-slate-200 focus:ring-0 cursor-pointer pr-8"
            >
              <option value="">Cualquier Rol</option>
              <option value="admin">Administrador</option>
              <option value="tecnico">Técnico</option>
              <option value="vendedor">Ventas</option>
            </select>

            <div class="w-px h-4 bg-gray-200 dark:bg-slate-800"></div>

            <!-- Estado -->
            <select
              v-model="filtroEstado"
              @change="onFiltroEstadoChange"
              class="bg-transparent border-none text-xs font-bold text-gray-700 dark:text-slate-200 focus:ring-0 cursor-pointer pr-8"
            >
              <option value="">Todo Estado</option>
              <option value="1">Operativos</option>
              <option value="0">Suspendidos</option>
            </select>
          </div>

          <!-- Orden -->
          <select
            v-model="sortBy"
            @change="onSortChange"
            class="px-4 py-2 bg-white dark:bg-slate-950 border border-gray-200 dark:border-slate-800/80 rounded-2xl text-xs font-bold text-gray-700 dark:text-slate-200 focus:ring-4 focus:ring-blue-500/10 transition-all cursor-pointer outline-none shadow-sm"
          >
            <option value="created_at-desc">Recientes primero</option>
            <option value="created_at-asc">Antiguos primero</option>
            <option value="name-asc">Nombre A-Z</option>
          </select>

          <!-- Reset -->
          <button
            @click="onLimpiarFiltros"
            class="p-2.5 bg-white dark:bg-slate-950 border border-gray-200 dark:border-slate-800/80 rounded-2xl text-gray-400 hover:text-rose-500 hover:border-rose-500/50 hover:bg-rose-50 dark:hover:bg-rose-500/5 transition-all duration-300 shadow-sm"
            title="Restablecer filtros"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>


<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useCompanyColors } from '@/Composables/useCompanyColors'

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

const { colors } = useCompanyColors()
const page = usePage()
const auth = computed(() => page.props.auth)

const $can = (permissionOrRole) => {
  const authData = auth.value;
  if (!authData || !authData.user) return false;
  if (authData.user.is_admin) return true;
  const permissions = authData.user.permissions || [];
  const roles = authData.user.roles || [];
  const roleNames = Array.isArray(roles) ? roles.map(r => typeof r === 'string' ? r : r.name) : [];
  if (roleNames.includes('admin') || roleNames.includes('super-admin')) return true;
  return permissions.includes(permissionOrRole) || roleNames.includes(permissionOrRole);
};

const props = defineProps({
  total: { type: Number, default: 0 },
  activos: { type: Number, default: 0 },
  inactivos: { type: Number, default: 0 },
  administradores: { type: Number, default: 0 },
  con2FA: { type: Number, default: 0 },
})

const emit = defineEmits([
  'crear-nueva', 'search-change', 'filtro-estado-change', 'filtro-rol-change', 'sort-change', 'limpiar-filtros'
])

const searchTerm = defineModel('searchTerm', { type: String, default: '' })
const sortBy = defineModel('sortBy', { type: String, default: 'created_at-desc' })
const filtroEstado = defineModel('filtroEstado', { type: String, default: '' })
const filtroRol = defineModel('filtroRol', { type: String, default: '' })

const onCrearNueva = () => emit('crear-nueva')
const onSearchChange = () => emit('search-change', searchTerm.value)
const onFiltroEstadoChange = () => emit('filtro-estado-change', filtroEstado.value)
const onFiltroRolChange = () => emit('filtro-rol-change', filtroRol.value)
const onSortChange = () => emit('sort-change', sortBy.value)
const onLimpiarFiltros = () => emit('limpiar-filtros')
</script>


