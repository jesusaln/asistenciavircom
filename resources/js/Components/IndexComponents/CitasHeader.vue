<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden transition-colors">
    <!-- Header con estadísticas -->
    <div class="px-6 py-6 border-b border-gray-200/60 dark:border-gray-700/50 transition-colors" :style="headerStyle">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight transition-colors">Citas</h1>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 transition-colors">Gestiona todas tus citas en un solo lugar</p>
        </div>
        <button
          @click="onCrearNueva"
          class="inline-flex items-center px-4 py-2 bg-blue-600 dark:bg-blue-500 text-white text-sm font-medium rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 shadow-sm transition-all active:scale-95"
          :style="{ backgroundColor: colors.principal }"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Nueva Cita
        </button>
      </div>

      <!-- Estadísticas -->
      <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-4 border border-gray-200/50 dark:border-gray-700/50 shadow-sm transition-colors">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Total</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 transition-colors">{{ total }}</p>
            </div>
            <div class="w-10 h-10 bg-blue-100/50 dark:bg-blue-900/30 rounded-xl flex items-center justify-center transition-colors">
              <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-4 border border-gray-200/50 dark:border-gray-700/50 shadow-sm transition-colors">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Pendientes</p>
              <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 transition-colors">{{ pendientes }}</p>
            </div>
            <div class="w-10 h-10 bg-yellow-100/50 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center transition-colors">
              <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-4 border border-gray-200/50 dark:border-gray-700/50 shadow-sm transition-colors">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">En Proceso</p>
              <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 transition-colors">{{ enProceso }}</p>
            </div>
            <div class="w-10 h-10 bg-blue-100/50 dark:bg-blue-900/30 rounded-xl flex items-center justify-center transition-colors">
              <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-4 border border-gray-200/50 dark:border-gray-700/50 shadow-sm transition-colors">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Hechas</p>
              <p class="text-2xl font-bold text-green-600 dark:text-green-400 transition-colors">{{ completadas }}</p>
            </div>
            <div class="w-10 h-10 bg-green-100/50 dark:bg-green-900/30 rounded-xl flex items-center justify-center transition-colors">
              <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-4 border border-gray-200/50 dark:border-gray-700/50 shadow-sm transition-colors">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Canceladas</p>
              <p class="text-2xl font-bold text-red-600 dark:text-red-400 transition-colors">{{ canceladas }}</p>
            </div>
            <div class="w-10 h-10 bg-red-100/50 dark:bg-red-900/30 rounded-xl flex items-center justify-center transition-colors">
              <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-900/30 border-b border-gray-200/60 dark:border-gray-700 transition-colors">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0 gap-4">
        <!-- Búsqueda -->
        <div class="flex-1">
          <div class="relative group">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none transition-colors">
              <svg class="h-5 w-5 text-gray-400 group-focus-within:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input
              v-model="searchTerm"
              type="text"
              placeholder="Buscar por cliente, técnico o problema..."
              class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 dark:border-gray-700 rounded-xl leading-5 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/50 dark:focus:ring-blue-500/30 transition-all text-sm"
              @input="onSearchChange"
            />
          </div>
        </div>

        <!-- Filtros -->
        <div class="flex flex-wrap items-center gap-3">
          <!-- Toggle Vista -->
          <div class="flex bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl p-1 transition-colors">
            <button
              @click="viewMode = 'table'"
              :class="['px-3 py-1.5 rounded-lg flex items-center gap-2 text-xs font-bold transition-all shadow-sm', 
                       viewMode === 'table' ? 'bg-blue-600 text-white' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700']"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
              Lista
            </button>
            <button
              @click="viewMode = 'calendar'"
              :class="['px-3 py-1.5 rounded-lg flex items-center gap-2 text-xs font-bold transition-all shadow-sm', 
                       viewMode === 'calendar' ? 'bg-blue-600 text-white' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700']"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              Calendario
            </button>
          </div>

          <!-- Estado de Cita -->
          <select
            v-model="filtroEstadoCita"
            @change="onFiltroEstadoCitaChange"
            class="block w-40 pl-3 pr-10 py-2.5 text-sm border border-gray-300 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/50 dark:focus:ring-blue-500/30 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 transition-all outline-none"
          >
            <option value="">Todos los Estados</option>
            <option value="pendiente">Pendiente</option>
            <option value="en_proceso">En Proceso</option>
            <option value="completado">Completado</option>
            <option value="cancelado">Cancelado</option>
          </select>

          <!-- Ordenamiento -->
          <select
            v-model="sortBy"
            @change="onSortChange"
            class="block w-44 pl-3 pr-10 py-2.5 text-sm border border-gray-300 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/50 dark:focus:ring-blue-500/30 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 transition-all outline-none"
          >
            <option value="created_at-desc">Fecha (Reciente)</option>
            <option value="created_at-asc">Fecha (Antiguo)</option>
            <option value="fecha_hora-asc">Cita ↑</option>
            <option value="fecha_hora-desc">Cita ↓</option>
          </select>

          <!-- Limpiar filtros -->
          <button
            @click="onLimpiarFiltros"
            class="inline-flex items-center px-4 py-2.5 border border-gray-300 dark:border-gray-700 text-sm font-bold rounded-xl text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-900 focus:ring-blue-500 transition-all shadow-sm active:scale-95"
          >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            Limpiar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useCompanyColors } from '@/Composables/useCompanyColors'
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'

// Colores de empresa
const { colors } = useCompanyColors()

// Estado reactivo para Modo Oscuro
const isDark = ref(false)
let observer = null

const headerStyle = computed(() => {
  if (isDark.value) {
    return { background: 'linear-gradient(135deg, #1f2937 0%, #111827 100%)' }
  }
  return { background: `linear-gradient(135deg, ${colors.value.principal}15 0%, ${colors.value.secundario}10 100%)` }
})

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

const props = defineProps({
  total: { type: Number, default: 0 },
  pendientes: { type: Number, default: 0 },
  enProceso: { type: Number, default: 0 },
  completadas: { type: Number, default: 0 },
  canceladas: { type: Number, default: 0 },
})

const emit = defineEmits([
  'crear-nueva', 'search-change', 'filtro-estado-cita-change', 'sort-change', 'limpiar-filtros'
])

// Estados locales para filtros
const searchTerm = defineModel('searchTerm', { type: String, default: '' })
const sortBy = defineModel('sortBy', { type: String, default: 'created_at-desc' })
const filtroEstadoCita = defineModel('filtroEstadoCita', { type: String, default: '' })
const viewMode = defineModel('viewMode', { type: String, default: 'table' })

// Métodos de emisión
const onCrearNueva = () => emit('crear-nueva')
const onSearchChange = () => emit('search-change', searchTerm.value)
const onFiltroEstadoCitaChange = () => emit('filtro-estado-cita-change', filtroEstadoCita.value)
const onSortChange = () => emit('sort-change', sortBy.value)
const onLimpiarFiltros = () => emit('limpiar-filtros')

// Watch para limpiar filtros automáticamente
watch([searchTerm, sortBy, filtroEstadoCita], () => {
  // Emitir cambios automáticamente
}, { immediate: true })
</script>

<style scoped>
/* Estilos específicos para el header de citas */
@media (max-width: 768px) {
  .citas-header h1 {
    font-size: 1.5rem;
  }
}

.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
  background: #475569;
}
</style>

