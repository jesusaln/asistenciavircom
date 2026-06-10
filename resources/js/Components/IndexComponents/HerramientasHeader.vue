<template>
  <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-100 dark:border-slate-700 overflow-hidden transition-colors index-header-root">
    <!-- Header con estadísticas -->
    <div class="px-6 py-6 border-b border-slate-200/60 dark:border-slate-700/50 transition-colors" :style="headerStyle">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight transition-colors">Gestión de Herramientas</h1>
          <p class="text-sm text-slate-600 dark:text-slate-400 mt-1 transition-colors">Administra y controla el inventario de herramientas</p>
        </div>
        <div class="flex gap-3">
          <Link
            href="/herramientas/dashboard"
            class="inline-flex items-center px-4 py-2 bg-purple-600 dark:bg-purple-500 text-white text-sm font-medium rounded-xl hover:bg-purple-700 dark:hover:bg-purple-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 shadow-sm transition-all active:scale-95"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Dashboard
          </Link>
          <Link
            href="/herramientas/create"
            class="inline-flex items-center px-4 py-2 bg-emerald-600 dark:bg-emerald-500 text-white text-sm font-medium rounded-xl hover:bg-emerald-700 dark:hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 shadow-sm transition-all active:scale-95"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nueva Herramienta
          </Link>
        </div>
      </div>

      <!-- Estadísticas: filtros por estado -->
      <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
        <!-- Total -->
        <button
          type="button"
          class="stat-filter-btn text-left w-full rounded-xl p-4 border shadow-sm transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900"
          :class="filtroActivo('') ? 'bg-white dark:bg-slate-800 border-blue-400 dark:border-blue-500 ring-2 ring-blue-500/60 ring-offset-2 ring-offset-white dark:ring-offset-slate-900' : 'bg-white/80 dark:bg-slate-800/80 border-slate-200/60 dark:border-slate-700/50 hover:border-blue-200 dark:hover:border-blue-800'"
          @click="selectEstado('')"
        >
          <div class="flex items-center justify-between gap-2">
            <div class="min-w-0">
              <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Total</p>
              <p class="text-2xl font-bold text-slate-900 dark:text-slate-100 tabular-nums">{{ total }}</p>
            </div>
            <div class="w-10 h-10 shrink-0 bg-sky-100/60 dark:bg-sky-900/40 rounded-xl flex items-center justify-center">
              <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
            </div>
          </div>
        </button>

        <!-- Disponibles -->
        <button
          type="button"
          class="stat-filter-btn text-left w-full rounded-xl p-4 border shadow-sm transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900"
          :class="filtroActivo('disponible') ? 'bg-emerald-50/90 dark:bg-green-950/30 border-emerald-400 dark:border-emerald-600 ring-2 ring-emerald-500/50 ring-offset-2 ring-offset-white dark:ring-offset-slate-900' : 'bg-white/80 dark:bg-slate-800/80 border-slate-200/60 dark:border-slate-700/50 hover:border-emerald-200 dark:hover:border-emerald-800'"
          @click="selectEstado('disponible')"
        >
          <div class="flex items-center justify-between gap-2">
            <div class="min-w-0">
              <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Libres</p>
              <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 tabular-nums">{{ disponibles }}</p>
            </div>
            <div class="w-10 h-10 shrink-0 bg-emerald-100/60 dark:bg-emerald-900/40 rounded-xl flex items-center justify-center">
              <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </button>

        <!-- Asignadas -->
        <button
          type="button"
          class="stat-filter-btn text-left w-full rounded-xl p-4 border shadow-sm transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900"
          :class="filtroActivo('asignada') ? 'bg-sky-50/90 dark:bg-sky-950/30 border-sky-400 dark:border-sky-500 ring-2 ring-sky-500/50 ring-offset-2 ring-offset-white dark:ring-offset-slate-900' : 'bg-white/80 dark:bg-slate-800/80 border-slate-200/60 dark:border-slate-700/50 hover:border-blue-200 dark:hover:border-blue-800'"
          @click="selectEstado('asignada')"
        >
          <div class="flex items-center justify-between gap-2">
            <div class="min-w-0">
              <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">En Uso</p>
              <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 tabular-nums">{{ asignadas }}</p>
            </div>
            <div class="w-10 h-10 shrink-0 bg-sky-100/60 dark:bg-sky-900/40 rounded-xl flex items-center justify-center">
              <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
          </div>
        </button>

        <!-- Mantenimiento -->
        <button
          type="button"
          class="stat-filter-btn text-left w-full rounded-xl p-4 border shadow-sm transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-yellow-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900"
          :class="filtroActivo('mantenimiento') ? 'bg-brand-50/90 dark:bg-yellow-950/30 border-yellow-400 dark:border-yellow-600 ring-2 ring-yellow-500/50 ring-offset-2 ring-offset-white dark:ring-offset-slate-900' : 'bg-white/80 dark:bg-slate-800/80 border-slate-200/60 dark:border-slate-700/50 hover:border-yellow-200 dark:hover:border-yellow-800'"
          @click="selectEstado('mantenimiento')"
        >
          <div class="flex items-center justify-between gap-2">
            <div class="min-w-0">
              <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">En Taller</p>
              <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 tabular-nums">{{ mantenimientoCount }}</p>
            </div>
            <div class="w-10 h-10 shrink-0 bg-yellow-100/60 dark:bg-yellow-900/40 rounded-xl flex items-center justify-center">
              <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </div>
          </div>
        </button>

        <!-- De Baja -->
        <button
          type="button"
          class="stat-filter-btn text-left w-full rounded-xl p-4 border shadow-sm transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900"
          :class="filtroActivo('baja') ? 'bg-rose-50/90 dark:bg-red-950/30 border-rose-400 dark:border-rose-600 ring-2 ring-rose-500/50 ring-offset-2 ring-offset-white dark:ring-offset-slate-900' : 'bg-white/80 dark:bg-slate-800/80 border-slate-200/60 dark:border-slate-700/50 hover:border-rose-200 dark:hover:border-rose-800'"
          @click="selectEstado('baja')"
        >
          <div class="flex items-center justify-between gap-2">
            <div class="min-w-0">
              <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">De Baja</p>
              <p class="text-2xl font-bold text-rose-600 dark:text-rose-400 tabular-nums">{{ baja }}</p>
            </div>
            <div class="w-10 h-10 shrink-0 bg-rose-100/60 dark:bg-rose-900/40 rounded-xl flex items-center justify-center">
              <svg class="w-5 h-5 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </div>
          </div>
        </button>

        <!-- Perdidas -->
        <button
          type="button"
          class="stat-filter-btn text-left w-full rounded-xl p-4 border shadow-sm transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900"
          :class="filtroActivo('perdida') ? 'bg-rose-50/90 dark:bg-red-950/30 border-rose-400 dark:border-rose-600 ring-2 ring-rose-500/50 ring-offset-2 ring-offset-white dark:ring-offset-slate-900' : 'bg-white/80 dark:bg-slate-800/80 border-slate-200/60 dark:border-slate-700/50 hover:border-rose-200 dark:hover:border-rose-800'"
          @click="selectEstado('perdida')"
        >
          <div class="flex items-center justify-between gap-2">
            <div class="min-w-0">
              <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Perdidas</p>
              <p class="text-2xl font-bold text-rose-600 dark:text-rose-400 tabular-nums">{{ perdida }}</p>
            </div>
            <div class="w-10 h-10 shrink-0 bg-rose-100/60 dark:bg-rose-900/40 rounded-xl flex items-center justify-center">
              <svg class="w-5 h-5 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
              </svg>
            </div>
          </div>
        </button>

        <!-- Requieren Mantenimiento -->
        <button
          type="button"
          class="stat-filter-btn text-left w-full rounded-xl p-4 border shadow-sm transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900"
          :class="filtroMantenimientoActivo('requiere') ? 'bg-orange-50/90 dark:bg-orange-950/30 border-orange-400 dark:border-brand-600 ring-2 ring-brand-500/50 ring-offset-2 ring-offset-white dark:ring-offset-slate-900' : 'bg-white/80 dark:bg-slate-800/80 border-slate-200/60 dark:border-slate-700/50 hover:border-orange-200 dark:hover:border-orange-800'"
          @click="selectMantenimiento('requiere')"
        >
          <div class="flex items-center justify-between gap-2">
            <div class="min-w-0">
              <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Alertas</p>
              <p class="text-2xl font-bold text-brand-600 dark:text-orange-400 tabular-nums">{{ requieren_mantenimiento }}</p>
            </div>
            <div class="w-10 h-10 shrink-0 bg-brand-100/60 dark:bg-brand-900/40 rounded-xl flex items-center justify-center">
              <svg class="w-5 h-5 text-brand-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </button>
      </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="px-6 py-4 bg-transparent dark:bg-slate-900/50 border-b border-slate-200/60 dark:border-slate-700 transition-colors">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0 gap-4">
        <!-- Búsqueda -->
        <div class="flex-1">
          <div class="relative group">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none transition-colors">
              <svg class="h-5 w-5 text-slate-400 group-focus-within:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input
              v-model="search"
              type="text"
              placeholder="Buscar por nombre, serie o descripción..."
              class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 dark:border-slate-700 rounded-xl leading-5 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500/50 dark:focus:ring-brand-500/30 transition-all text-sm"
              @input="onSearchChange"
            />
          </div>
        </div>

        <!-- Filtros -->
        <div class="flex flex-wrap items-center gap-3">
          <!-- Categoría -->
          <select
            v-model="categoria"
            @change="onFilterChange"
            class="block w-44 pl-3 pr-10 py-2.5 text-sm border border-slate-300 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/50 dark:focus:ring-brand-500/30 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 transition-all outline-none"
          >
            <option value="">Todas las categorías</option>
            <option value="sin_categoria">Sin categoría</option>
            <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.nombre }}</option>
          </select>

          <!-- Limpiar filtros -->
          <button
            @click="onLimpiarFiltros"
            class="inline-flex items-center px-4 py-2.5 border border-slate-300 dark:border-slate-700 text-sm font-bold rounded-xl text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 hover:bg-slate-700 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-900 focus:ring-brand-500 transition-all shadow-sm active:scale-95"
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
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { Link } from '@inertiajs/vue3'

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
  disponibles: { type: Number, default: 0 },
  asignadas: { type: Number, default: 0 },
  mantenimientoCount: { type: Number, default: 0 },
  baja: { type: Number, default: 0 },
  perdida: { type: Number, default: 0 },
  requieren_mantenimiento: { type: Number, default: 0 },
  categorias: { type: Array, default: () => [] },
})

const emit = defineEmits([
  'filter-change', 'search-change', 'limpiar-filtros'
])

// Modelos v-model
const search = defineModel('search', { type: String, default: '' })
const estado = defineModel('estado', { type: String, default: '' })
const categoria = defineModel('categoria', { type: [String, Number], default: '' })
const mantenimientoFilter = defineModel('mantenimiento', { type: String, default: '' })

const onSearchChange = () => emit('search-change', search.value)
const onFilterChange = () => emit('filter-change')

const filtroActivo = (valor) => (estado.value || '') === valor
const filtroMantenimientoActivo = (valor) => (mantenimientoFilter.value || '') === valor

const selectEstado = (valor) => {
  if (filtroActivo(valor)) {
    estado.value = ''
  } else {
    estado.value = valor
    mantenimientoFilter.value = '' // Limpiar el otro filtro de tipo "alerta"
  }
  emit('filter-change')
}

const selectMantenimiento = (valor) => {
  if (filtroMantenimientoActivo(valor)) {
    mantenimientoFilter.value = ''
  } else {
    mantenimientoFilter.value = valor
    estado.value = '' // Limpiar el filtro de estado normal
  }
  emit('filter-change')
}

const onLimpiarFiltros = () => emit('limpiar-filtros')
</script>


