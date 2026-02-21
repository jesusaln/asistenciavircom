<template>
  <div class="mb-8 space-y-6">
    <!-- Header Principal -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div class="space-y-1">
        <h1 class="text-3xl font-bold tracking-tight text-white flex items-center gap-3">
          <span class="p-2 bg-indigo-500/10 rounded-lg">
            <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
            </svg>
          </span>
          Traspasos de Inventario
        </h1>
        <p class="text-slate-400 flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
          Gestión de movimientos de stock entre almacenes
        </p>
      </div>

      <Link
        :href="route('traspasos.create')"
        class="inline-flex items-center justify-center px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold transition-all duration-300 shadow-lg shadow-indigo-600/20 hover:shadow-indigo-600/40 hover:-translate-y-0.5 group"
      >
        <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
        Nuevo Traspaso
      </Link>
    </div>

    <!-- Panel de Estadísticas con Glassmorphism -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Total -->
      <div class="group relative overflow-hidden bg-slate-900/50 backdrop-blur-xl border border-slate-800 rounded-2xl p-5 transition-all duration-300 hover:border-indigo-500/50">
        <div class="flex items-center justify-between relative z-10">
          <div>
            <p class="text-sm font-medium text-slate-400">Total Traspasos</p>
            <h3 class="text-2xl font-bold text-white mt-1">{{ stats.total }}</h3>
          </div>
          <div class="p-3 bg-indigo-500/10 rounded-xl group-hover:scale-110 transition-transform duration-300">
            <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
        </div>
        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-indigo-500/5 rounded-full blur-2xl group-hover:bg-indigo-500/10 transition-colors"></div>
      </div>

      <!-- Pendientes -->
      <div class="group relative overflow-hidden bg-slate-900/50 backdrop-blur-xl border border-slate-800 rounded-2xl p-5 transition-all duration-300 hover:border-amber-500/50">
        <div class="flex items-center justify-between relative z-10">
          <div>
            <p class="text-sm font-medium text-slate-400">Pendientes</p>
            <h3 class="text-2xl font-bold text-white mt-1">{{ stats.pendientes }}</h3>
          </div>
          <div class="p-3 bg-amber-500/10 rounded-xl group-hover:scale-110 transition-transform duration-300">
            <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-amber-500/5 rounded-full blur-2xl group-hover:bg-amber-500/10 transition-colors"></div>
      </div>

      <!-- Unidades -->
      <div class="group relative overflow-hidden bg-slate-900/50 backdrop-blur-xl border border-slate-800 rounded-2xl p-5 transition-all duration-300 hover:border-emerald-500/50">
        <div class="flex items-center justify-between relative z-10">
          <div>
            <p class="text-sm font-medium text-slate-400">Unidades Trasladadas</p>
            <h3 class="text-2xl font-bold text-white mt-1">{{ stats.unidades || 0 }}</h3>
          </div>
          <div class="p-3 bg-emerald-500/10 rounded-xl group-hover:scale-110 transition-transform duration-300">
            <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
          </div>
        </div>
        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-emerald-500/5 rounded-full blur-2xl group-hover:bg-emerald-500/10 transition-colors"></div>
      </div>

      <!-- Almacenes Activos -->
      <div class="group relative overflow-hidden bg-slate-900/50 backdrop-blur-xl border border-slate-800 rounded-2xl p-5 transition-all duration-300 hover:border-blue-500/50">
        <div class="flex items-center justify-between relative z-10">
          <div>
            <p class="text-sm font-medium text-slate-400">Rutas Activas</p>
            <h3 class="text-2xl font-bold text-white mt-1">{{ stats.almacenes_origen }} Orígenes</h3>
          </div>
          <div class="p-3 bg-blue-500/10 rounded-xl group-hover:scale-110 transition-transform duration-300">
            <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
          </div>
        </div>
        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-blue-500/5 rounded-full blur-2xl group-hover:bg-blue-500/10 transition-colors"></div>
      </div>
    </div>

    <!-- Barra de Filtros Premium -->
    <div class="bg-slate-900/40 backdrop-blur-xl border border-slate-800 p-4 rounded-2xl">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Búsqueda Inteligente -->
        <div class="relative group">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-slate-500 group-focus-within:text-indigo-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <input
            type="text"
            v-model="internalFilters.search"
            @input="debouncedSearch"
            placeholder="Buscar por ID o producto..."
            class="block w-full pl-10 pr-4 py-2.5 bg-slate-800/50 border border-slate-700 text-white placeholder-slate-500 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all"
          />
        </div>

        <!-- Selector de Origen -->
        <div class="relative">
          <select
            v-model="internalFilters.almacen_origen_id"
            @change="updateFilters"
            class="block w-full pl-4 pr-10 py-2.5 bg-slate-800/50 border border-slate-700 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/50 appearance-none transition-all"
          >
            <option value="">Todo Origen</option>
            <option v-for="alc in almacenes" :key="alc.id" :value="alc.id">{{ alc.nombre }}</option>
          </select>
          <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-500">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
          </div>
        </div>

        <!-- Selector de Destino -->
        <div class="relative">
          <select
            v-model="internalFilters.almacen_destino_id"
            @change="updateFilters"
            class="block w-full pl-4 pr-10 py-2.5 bg-slate-800/50 border border-slate-700 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/50 appearance-none transition-all"
          >
            <option value="">Todo Destino</option>
            <option v-for="alc in almacenes" :key="alc.id" :value="alc.id">{{ alc.nombre }}</option>
          </select>
          <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-500">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
          </div>
        </div>

        <!-- Botón Limpiar -->
        <button
          @click="clearFilters"
          class="flex items-center justify-center px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl border border-slate-700 transition-all font-medium gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          Restablecer
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import debounce from 'lodash/debounce'

const props = defineProps({
  stats: { type: Object, required: true },
  filters: { type: Object, required: true },
  almacenes: { type: Array, default: () => [] }
})

const internalFilters = ref({
  search: props.filters.search || '',
  almacen_origen_id: props.filters.almacen_origen_id || '',
  almacen_destino_id: props.filters.almacen_destino_id || '',
  producto_id: props.filters.producto_id || ''
})

const updateFilters = () => {
  router.get(route('traspasos.index'), {
    ...internalFilters.value,
    page: 1
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  })
}

const debouncedSearch = debounce(updateFilters, 500)

const clearFilters = () => {
  internalFilters.value = {
    search: '',
    almacen_origen_id: '',
    almacen_destino_id: '',
    producto_id: ''
  }
  updateFilters()
}

const emit = defineEmits(['search'])
</script>
