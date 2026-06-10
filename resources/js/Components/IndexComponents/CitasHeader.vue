<template>
  <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-100 dark:border-slate-700 overflow-hidden transition-colors index-header-root">
    <!-- Header con estadísticas -->
    <div class="px-6 py-6 border-b border-slate-200/60 dark:border-slate-700/50 transition-colors" :style="headerStyle">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight transition-colors">Citas</h1>
          <p class="text-sm text-slate-600 dark:text-slate-400 mt-1 transition-colors">Gestiona todas tus citas en un solo lugar</p>
        </div>
        <div class="flex items-center gap-3">
          <button
            @click="emit('open-availability')"
            class="inline-flex items-center px-4 py-2 bg-emerald-600 dark:bg-emerald-500 text-white text-sm font-medium rounded-xl hover:bg-emerald-700 dark:hover:bg-emerald-600 transition-all active:scale-95 border border-emerald-500 dark:border-emerald-400 shadow-sm"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Disponibilidad
          </button>
          <button
            @click="onCrearNueva"
            class="inline-flex items-center px-4 py-2 bg-blue-600 dark:bg-blue-500 text-white text-sm font-medium rounded-xl hover:bg-blue-700 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 shadow-sm transition-all active:scale-95"
            :style="{ backgroundColor: colors.principal }"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nueva Cita
          </button>
        </div>
      </div>

      <!-- Estadísticas: filtros por estado (clic = mismo criterio que el listado) -->
      <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3">
        <button
          type="button"
          class="stat-filter-btn text-left w-full rounded-xl p-4 border shadow-sm transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900"
          :class="filtroActivo('') ? 'bg-white dark:bg-slate-800 border-blue-400 dark:border-blue-500 ring-2 ring-blue-500/60 ring-offset-2 ring-offset-white dark:ring-offset-slate-900' : 'bg-white/80 dark:bg-slate-800/80 border-slate-200/60 dark:border-slate-700/50 hover:border-blue-200 dark:hover:border-blue-800'"
          :aria-pressed="filtroActivo('')"
          @click="selectEstado('')"
        >
          <div class="flex items-center justify-between gap-2">
            <div class="min-w-0">
              <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Total</p>
              <p class="text-2xl font-bold text-slate-900 dark:text-slate-100 tabular-nums">{{ total }}</p>
              <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">Todas las citas</p>
            </div>
            <div class="w-10 h-10 shrink-0 bg-sky-100/60 dark:bg-sky-900/40 rounded-xl flex items-center justify-center">
              <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
          </div>
        </button>

        <button
          type="button"
          class="stat-filter-btn text-left w-full rounded-xl p-4 border shadow-sm transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900"
          :class="filtroActivo('programado') ? 'bg-sky-50/90 dark:bg-sky-950/30 border-sky-400 dark:border-sky-500 ring-2 ring-sky-500/50 ring-offset-2 ring-offset-white dark:ring-offset-slate-900' : 'bg-white/80 dark:bg-slate-800/80 border-slate-200/60 dark:border-slate-700/50 hover:border-blue-200 dark:hover:border-blue-800'"
          :aria-pressed="filtroActivo('programado')"
          @click="selectEstado('programado')"
        >
          <div class="flex items-center justify-between gap-2">
            <div class="min-w-0">
              <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Programadas</p>
              <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 tabular-nums">{{ programadas }}</p>
              <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">Fecha y técnico</p>
            </div>
            <div class="w-10 h-10 shrink-0 bg-sky-100/60 dark:bg-sky-900/40 rounded-xl flex items-center justify-center">
              <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
          </div>
        </button>

        <button
          type="button"
          class="stat-filter-btn text-left w-full rounded-xl p-4 border shadow-sm transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-yellow-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900"
          :class="filtroActivo(filtroColaAtencion) ? 'bg-brand-50/90 dark:bg-yellow-950/30 border-yellow-400 dark:border-yellow-600 ring-2 ring-yellow-500/50 ring-offset-2 ring-offset-white dark:ring-offset-slate-900' : 'bg-white/80 dark:bg-slate-800/80 border-slate-200/60 dark:border-slate-700/50 hover:border-yellow-200 dark:hover:border-yellow-800'"
          :aria-pressed="filtroActivo(filtroColaAtencion)"
          @click="selectEstado(filtroColaAtencion)"
        >
          <div class="flex items-center justify-between gap-2">
            <div class="min-w-0">
              <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Por atender</p>
              <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 tabular-nums">{{ porAtender }}</p>
              <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">Pendiente + sin asignar</p>
            </div>
            <div class="w-10 h-10 shrink-0 bg-yellow-100/60 dark:bg-yellow-900/40 rounded-xl flex items-center justify-center">
              <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </button>

        <button
          type="button"
          class="stat-filter-btn text-left w-full rounded-xl p-4 border shadow-sm transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900"
          :class="filtroActivo('en_proceso') ? 'bg-sky-50/90 dark:bg-sky-950/30 border-sky-400 dark:border-sky-500 ring-2 ring-sky-500/50 ring-offset-2 ring-offset-white dark:ring-offset-slate-900' : 'bg-white/80 dark:bg-slate-800/80 border-slate-200/60 dark:border-slate-700/50 hover:border-sky-200 dark:hover:border-sky-800'"
          :aria-pressed="filtroActivo('en_proceso')"
          @click="selectEstado('en_proceso')"
        >
          <div class="flex items-center justify-between gap-2">
            <div class="min-w-0">
              <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">En proceso</p>
              <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 tabular-nums">{{ enProceso }}</p>
            </div>
            <div class="w-10 h-10 shrink-0 bg-sky-100/60 dark:bg-sky-900/40 rounded-xl flex items-center justify-center">
              <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
          </div>
        </button>

        <button
          type="button"
          class="stat-filter-btn text-left w-full rounded-xl p-4 border shadow-sm transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900"
          :class="filtroActivo('completado') ? 'bg-emerald-50/90 dark:bg-green-950/30 border-emerald-400 dark:border-emerald-600 ring-2 ring-emerald-500/50 ring-offset-2 ring-offset-white dark:ring-offset-slate-900' : 'bg-white/80 dark:bg-slate-800/80 border-slate-200/60 dark:border-slate-700/50 hover:border-emerald-200 dark:hover:border-emerald-800'"
          :aria-pressed="filtroActivo('completado')"
          @click="selectEstado('completado')"
        >
          <div class="flex items-center justify-between gap-2">
            <div class="min-w-0">
              <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Hechas</p>
              <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 tabular-nums">{{ completadas }}</p>
            </div>
            <div class="w-10 h-10 shrink-0 bg-emerald-100/60 dark:bg-emerald-900/40 rounded-xl flex items-center justify-center">
              <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </button>

        <button
          type="button"
          class="stat-filter-btn text-left w-full rounded-xl p-4 border shadow-sm transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900"
          :class="filtroActivo('cancelado') ? 'bg-rose-50/90 dark:bg-red-950/30 border-rose-400 dark:border-rose-600 ring-2 ring-rose-500/50 ring-offset-2 ring-offset-white dark:ring-offset-slate-900' : 'bg-white/80 dark:bg-slate-800/80 border-slate-200/60 dark:border-slate-700/50 hover:border-rose-200 dark:hover:border-rose-800'"
          :aria-pressed="filtroActivo('cancelado')"
          @click="selectEstado('cancelado')"
        >
          <div class="flex items-center justify-between gap-2">
            <div class="min-w-0">
              <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Canceladas</p>
              <p class="text-2xl font-bold text-rose-600 dark:text-rose-400 tabular-nums">{{ canceladas }}</p>
            </div>
            <div class="w-10 h-10 shrink-0 bg-rose-100/60 dark:bg-rose-900/40 rounded-xl flex items-center justify-center">
              <svg class="w-5 h-5 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </div>
          </div>
        </button>
      </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="px-6 py-4 bg-transparent dark:bg-slate-900/50 border-b border-slate-200/60 dark:border-slate-700 transition-colors">
      <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between space-y-4 xl:space-y-0 gap-4">
        <!-- Búsqueda -->
        <div class="flex-1">
          <div class="relative group">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none transition-colors">
              <svg class="h-5 w-5 text-slate-400 group-focus-within:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input
              v-model="searchTerm"
              type="text"
              placeholder="Buscar por cliente, técnico o folio..."
              class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 dark:border-slate-700 rounded-xl leading-5 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500/50 dark:focus:ring-brand-500/30 transition-all text-sm"
              @input="onSearchChange"
            />
          </div>
        </div>

        <!-- Filtros Temporales y Operativos -->
        <div class="flex flex-wrap items-center gap-3">
          <!-- Rango de Fechas (Paginación Temporal) -->
          <div class="flex items-center gap-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-1 shadow-sm transition-all">
            <div class="flex items-center gap-1.5">
              <span class="text-[9px] font-black text-slate-400 uppercase tracking-tighter">Desde</span>
              <input 
                v-model="fechaDesde" 
                type="date" 
                @change="emit('date-change')"
                class="bg-transparent border-none p-0 text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-0 outline-none w-28"
              >
            </div>
            <div class="w-px h-4 bg-slate-200 dark:bg-slate-700"></div>
            <div class="flex items-center gap-1.5">
              <span class="text-[9px] font-black text-slate-400 uppercase tracking-tighter">Hasta</span>
              <input 
                v-model="fechaHasta" 
                type="date" 
                @change="emit('date-change')"
                class="bg-transparent border-none p-0 text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-0 outline-none w-28"
              >
            </div>
          </div>

          <!-- Toggle Vista -->
          <div class="flex bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl p-1 transition-colors">
            <button
              @click="viewMode = 'table'"
              :class="['px-3 py-1.5 rounded-xl flex items-center gap-2 text-xs font-bold transition-all shadow-sm', 
                       viewMode === 'table' ? 'bg-blue-600 text-white' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700']"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
              Lista
            </button>
            <button
              @click="viewMode = 'calendar'"
              :class="['px-3 py-1.5 rounded-xl flex items-center gap-2 text-xs font-bold transition-all shadow-sm', 
                       viewMode === 'calendar' ? 'bg-blue-600 text-white' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700']"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              Calendario
            </button>
          </div>

          <!-- Ordenamiento -->
          <select
            v-model="sortBy"
            @change="onSortChange"
            class="block w-40 pl-3 pr-8 py-2.5 text-sm border border-slate-300 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/50 dark:focus:ring-brand-500/30 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 transition-all outline-none"
          >
            <option value="created_at-desc">Reciente (Log)</option>
            <option value="fecha_hora-asc">Próximas ↑</option>
            <option value="fecha_hora-desc">Pasadas ↓</option>
          </select>

          <!-- Limpiar filtros -->
          <button
            @click="onLimpiarFiltros"
            class="inline-flex items-center px-4 py-2.5 border border-slate-300 dark:border-slate-700 text-sm font-bold rounded-xl text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-900 focus:ring-brand-500 transition-all shadow-sm active:scale-95"
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

/** Mismo valor que envía el backend en `estado` al filtrar “Por atender”. */
const filtroColaAtencion = 'pendiente,pendiente_asignacion'

const props = defineProps({
  total: { type: Number, default: 0 },
  programadas: { type: Number, default: 0 },
  porAtender: { type: Number, default: 0 },
  enProceso: { type: Number, default: 0 },
  completadas: { type: Number, default: 0 },
  canceladas: { type: Number, default: 0 },
})

const emit = defineEmits([
  'crear-nueva', 'search-change', 'filtro-estado-cita-change', 'sort-change', 'limpiar-filtros', 'date-change', 'open-availability'
])

// Estados locales para filtros
const searchTerm = defineModel('searchTerm', { type: String, default: '' })
const sortBy = defineModel('sortBy', { type: String, default: 'created_at-desc' })
const filtroEstadoCita = defineModel('filtroEstadoCita', { type: String, default: '' })
const viewMode = defineModel('viewMode', { type: String, default: 'table' })
const fechaDesde = defineModel('fechaDesde', { type: String, default: '' })
const fechaHasta = defineModel('fechaHasta', { type: String, default: '' })

// Métodos de emisión
const onCrearNueva = () => emit('crear-nueva')
const onSearchChange = () => emit('search-change', searchTerm.value)
const onSortChange = () => emit('sort-change', sortBy.value)

/** Mismo criterio que el backend: '' = sin filtro por estado. */
const filtroActivo = (valor) => (filtroEstadoCita.value || '') === valor

const selectEstado = (valor) => {
  if (filtroActivo(valor)) return
  emit('filtro-estado-cita-change', valor)
}
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

