<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden transition-colors">
    <!-- Header con estadísticas -->
    <div 
      class="px-6 py-6 border-b border-gray-200/60 transition-colors" 
      :style="{ background: isDark ? 'linear-gradient(135deg, #1f2937 0%, #111827 100%)' : `linear-gradient(135deg, ${colors.principal}15 0%, ${colors.secundario}10 100%)` }"
    >
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-md scale-100 hover:scale-110 transition-transform" :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight transition-colors">Clientes</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5 transition-colors">Gestiona todos tus clientes en un solo lugar</p>
          </div>
        </div>
        <button
          v-if="$can('create clientes')"
          @click="onCrearNueva"
          class="inline-flex items-center px-5 py-2.5 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-300 transform hover:-translate-y-0.5"
          :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)`, '--tw-ring-color': colors.principal }"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Nuevo Cliente
        </button>
      </div>

      <!-- Estadísticas -->
      <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
        <!-- Stat Card Item (reusable logic) -->
        <div 
          v-for="(stat, idx) in [
            { label: 'Total', value: total, color: colors.principal, icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
            { label: 'Activos', value: activos, color: '#10b981', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
            { label: 'Pendientes', value: inactivos, color: '#f59e0b', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
            { label: 'P. Físicas', value: personas_fisicas, color: colors.principal, icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
            { label: 'P. Morales', value: personas_morales, color: '#8b5cf6', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
            { label: 'Nuevos', value: nuevos_mes, color: colors.secundario, icon: 'M12 4v16m8-8H4' }
          ]"
          :key="idx"
          class="bg-white/80 dark:bg-gray-800/60 backdrop-blur-sm rounded-xl p-4 border border-gray-200/50 dark:border-gray-700/50 shadow-sm transition-all"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ stat.label }}</p>
              <p class="text-xl font-bold transition-colors" :style="{ color: isDark ? 'white' : stat.color }">{{ stat.value }}</p>
            </div>
            <div class="w-8 h-8 rounded-full flex items-center justify-center transition-colors" :style="{ backgroundColor: isDark ? '#374151' : `${stat.color}20` }">
              <svg class="w-4 h-4" :style="{ color: isDark ? 'white' : stat.color }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="stat.icon" />
              </svg>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-800/40 border-b border-gray-200/60 dark:border-gray-700/40 transition-colors">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0 gap-4">
        <!-- Búsqueda -->
        <div class="flex-1 max-w-md">
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input
              v-model="searchTerm"
              type="text"
              placeholder="Buscar cliente..."
              class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg leading-5 bg-white dark:bg-gray-800 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all font-medium text-sm"
              @input="onSearchChange"
            />
          </div>
        </div>

        <!-- Filtros -->
        <div class="flex flex-wrap items-center gap-2">
          <!-- Filtro de tipo de persona -->
          <select
            v-model="filtroTipoPersona"
            @change="onFiltroTipoPersonaChange"
            class="block pl-3 pr-10 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 transition-all outline-none"
          >
            <option value="">Tipo: Todos</option>
            <option value="fisica">Física</option>
            <option value="moral">Moral</option>
          </select>

          <!-- Filtro de estado activo -->
          <select
            v-model="filtroEstado"
            @change="onFiltroEstadoChange"
            class="block pl-3 pr-10 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 transition-all outline-none"
          >
            <option value="">Estado: Todos</option>
            <option value="1">Activos</option>
            <option value="0">Pendientes</option>
          </select>

          <!-- Filtro de estado de México -->
          <select
            v-model="filtroEstadoMexico"
            @change="onFiltroEstadoMexicoChange"
            class="hidden lg:block pl-3 pr-10 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 transition-all outline-none"
          >
            <option value="">Ubicación: Todo MX</option>
            <option value="CIUDAD DE MÉXICO">Ciudad de México</option>
            <option value="SONORA">Sonora</option>
            <option value="JALISCO">Jalisco</option>
            <option value="NUEVO LEÓN">Nuevo León</option>
          </select>

          <!-- Ordenamiento -->
          <select
            v-model="sortBy"
            @change="onSortChange"
            class="block pl-3 pr-10 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 transition-all outline-none"
          >
            <option value="created_at-desc">Recientes</option>
            <option value="nombre_razon_social-asc">Nombre A-Z</option>
          </select>

          <!-- Limpiar filtros -->
          <button
            @click="onLimpiarFiltros"
            class="p-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            title="Limpiar filtros"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
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

// Colores de empresa
const { colors } = useCompanyColors()
// Función local $can que usa usePage() reactivo (NO el $can global estático)
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
  personas_fisicas: { type: Number, default: 0 },
  personas_morales: { type: Number, default: 0 },
  nuevos_mes: { type: Number, default: 0 },
})

const emit = defineEmits([
  'crear-nueva', 'search-change', 'filtro-tipo-persona-change', 'filtro-estado-change', 'filtro-estado-mexico-change', 'sort-change', 'limpiar-filtros'
])

// Estados locales para filtros
const searchTerm = defineModel('searchTerm', { type: String, default: '' })
const sortBy = defineModel('sortBy', { type: String, default: 'created_at-desc' })
const filtroTipoPersona = defineModel('filtroTipoPersona', { type: String, default: '' })
const filtroEstado = defineModel('filtroEstado', { type: String, default: '' })
const filtroEstadoMexico = defineModel('filtroEstadoMexico', { type: String, default: '' })

// Métodos de emisión
const onCrearNueva = () => emit('crear-nueva')
const onSearchChange = () => emit('search-change', searchTerm.value)
const onFiltroTipoPersonaChange = () => emit('filtro-tipo-persona-change', filtroTipoPersona.value)
const onFiltroEstadoChange = () => emit('filtro-estado-change', filtroEstado.value)
const onFiltroEstadoMexicoChange = () => emit('filtro-estado-mexico-change', filtroEstadoMexico.value)
const onSortChange = () => emit('sort-change', sortBy.value)
const onLimpiarFiltros = () => emit('limpiar-filtros')

// Watch para limpiar filtros automáticamente
watch([searchTerm, sortBy, filtroTipoPersona, filtroEstado, filtroEstadoMexico], () => {
  // Emitir cambios automáticamente
}, { immediate: true })
</script>

<style scoped>
/* Estilos específicos para el header de clientes */
.clientes-header {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
}

@media (max-width: 768px) {
  .clientes-header .grid {
    grid-template-columns: 1fr;
  }

  .clientes-header h1 {
    font-size: 1.5rem;
  }
}
</style>

