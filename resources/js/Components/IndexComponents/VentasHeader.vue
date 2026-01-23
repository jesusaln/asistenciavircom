<template>
  <div class="bg-white dark:bg-slate-900 dark:bg-slate-950 rounded-3xl shadow-xl border border-gray-100 dark:border-slate-800 dark:border-slate-800 overflow-hidden transition-all duration-300">
    <!-- Header principal -->
    <div class="px-8 py-6 border-b border-gray-100 dark:border-slate-800 dark:border-slate-800" :style="{ background: `linear-gradient(135deg, ${colors.principal}08 0%, ${colors.secundario}05 100%)` }">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center space-x-5">
          <div class="w-14 h-14 rounded-2xl flex items-center justify-center shadow-xl transform transition-transform hover:scale-105" :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }">
            <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div>
            <h1 class="text-2xl font-black text-gray-900 dark:text-white dark:text-white uppercase tracking-wider">Ventas</h1>
            <p class="text-xs font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">Panel de Control Financiero</p>
          </div>
        </div>

        <div class="flex items-center space-x-3">
          <button
            @click="onCrearNuevo"
            class="group inline-flex items-center px-6 py-3.5 text-white text-[10px] font-black uppercase tracking-[0.2em] rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 active:translate-y-0"
            :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }"
          >
            <svg class="w-4 h-4 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            {{ config.createButtonText || 'Crear Venta' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Estadísticas -->
    <div class="px-8 py-6 bg-gray-50/30 dark:bg-slate-900/40 border-b border-gray-100 dark:border-slate-800 dark:border-slate-800">
      <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <!-- Card 1: Total -->
        <div class="bg-white dark:bg-slate-900/80 dark:bg-slate-800/40 backdrop-blur-md p-5 rounded-2xl border border-gray-100 dark:border-slate-800 dark:border-slate-700/50 shadow-sm transition-all hover:bg-white dark:bg-slate-900 dark:hover:bg-slate-800/60">
          <div class="flex flex-col">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[9px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest">Total Global</span>
                <div class="w-7 h-7 rounded-lg flex items-center justify-center bg-slate-100 dark:bg-slate-700">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-gray-900 dark:text-white dark:text-white">{{ total }}</p>
          </div>
        </div>

        <!-- Card 2: Borradores -->
        <div class="bg-white dark:bg-slate-900/80 dark:bg-slate-800/40 backdrop-blur-md p-5 rounded-2xl border border-gray-100 dark:border-slate-800 dark:border-slate-700/50 shadow-sm transition-all hover:bg-white dark:bg-slate-900 dark:hover:bg-slate-800/60">
          <div class="flex flex-col">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[9px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest">Borrador</span>
                <div class="w-7 h-7 rounded-lg flex items-center justify-center bg-gray-100 dark:bg-gray-800">
                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-gray-600 dark:text-slate-400">{{ borrador }}</p>
          </div>
        </div>

        <!-- Card 3: Pendientes -->
        <div class="bg-white dark:bg-slate-900/80 dark:bg-slate-800/40 backdrop-blur-md p-5 rounded-2xl border border-gray-100 dark:border-slate-800 dark:border-slate-700/50 shadow-sm transition-all hover:bg-white dark:bg-slate-900 dark:hover:bg-slate-800/60">
          <div class="flex flex-col">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[9px] font-black text-amber-600 dark:text-amber-500 uppercase tracking-widest">Pendientes</span>
                <div class="w-7 h-7 rounded-lg flex items-center justify-center bg-amber-100/50 dark:bg-amber-900/30">
                    <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-amber-600 dark:text-amber-500">{{ pendientes }}</p>
          </div>
        </div>

        <!-- Card 4: Aprobadas -->
        <div class="bg-white dark:bg-slate-900/80 dark:bg-slate-800/40 backdrop-blur-md p-5 rounded-2xl border border-gray-100 dark:border-slate-800 dark:border-slate-700/50 shadow-sm transition-all hover:bg-white dark:bg-slate-900 dark:hover:bg-slate-800/60">
          <div class="flex flex-col">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[9px] font-black text-emerald-600 dark:text-emerald-500 uppercase tracking-widest">Aprobadas</span>
                <div class="w-7 h-7 rounded-lg flex items-center justify-center bg-emerald-100/50 dark:bg-emerald-900/30">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-emerald-600 dark:text-emerald-500">{{ aprobadas }}</p>
          </div>
        </div>

        <!-- Card 5: Canceladas -->
        <div class="bg-white dark:bg-slate-900/80 dark:bg-slate-800/40 backdrop-blur-md p-5 rounded-2xl border border-gray-100 dark:border-slate-800 dark:border-slate-700/50 shadow-sm transition-all hover:bg-white dark:bg-slate-900 dark:hover:bg-slate-800/60">
          <div class="flex flex-col">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[9px] font-black text-rose-600 dark:text-rose-500 uppercase tracking-widest">Canceladas</span>
                <div class="w-7 h-7 rounded-lg flex items-center justify-center bg-rose-100/50 dark:bg-rose-900/30">
                    <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-rose-600 dark:text-rose-500">{{ cancelada }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filtros -->
    <div class="px-8 py-5 bg-white dark:bg-slate-900 dark:bg-slate-900">
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <!-- Búsqueda -->
        <div class="flex-1 max-w-xl">
          <div class="relative group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-emerald-500">
              <svg class="h-5 w-5 text-gray-400 group-hover:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input
              v-model="searchTerm"
              type="text"
              :placeholder="config.searchPlaceholder || 'Buscar ventas por cliente, folio, estado...'"
              class="block w-full pl-12 pr-4 py-3.5 bg-gray-50 dark:bg-slate-950 border-2 border-transparent dark:border-slate-800 rounded-2xl font-bold text-gray-900 dark:text-white dark:text-white placeholder-gray-400 dark:placeholder-slate-600 focus:bg-white dark:bg-slate-900 dark:focus:bg-slate-900 focus:border-gray-900 dark:focus:border-slate-700 focus:ring-0 transition-all duration-300 text-sm"
              @input="onSearchChange"
            />
          </div>
        </div>

        <!-- Filtros adicionales -->
        <div class="flex items-center gap-3">
          <!-- Filtro CFDI -->
          <div class="relative min-w-[180px]">
            <select
              v-model="filtroCfdi"
              class="appearance-none block w-full pl-4 pr-10 py-3.5 bg-gray-50 dark:bg-slate-950 border-2 border-transparent dark:border-slate-800 rounded-2xl font-black uppercase text-[10px] tracking-widest text-gray-900 dark:text-white dark:text-white focus:bg-white dark:bg-slate-900 dark:focus:bg-slate-900 focus:border-gray-900 dark:focus:border-slate-700 focus:ring-0 transition-all duration-300"
              @change="onFiltroCfdiChange"
            >
              <option value="">Todos los CFDI</option>
              <option value="timbrada">Timbradas</option>
              <option value="sin_timbrar">Sin timbrar</option>
            </select>
            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
              <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </div>
          </div>

          <!-- Limpiar filtros -->
          <button
            @click="onLimpiarFiltros"
            class="inline-flex items-center px-5 py-3.5 bg-gray-50 dark:bg-slate-950 border-2 border-transparent dark:border-slate-800 rounded-2xl font-black uppercase text-[10px] tracking-widest text-gray-500 dark:text-gray-400 dark:text-slate-500 hover:text-gray-900 dark:text-white dark:hover:text-white hover:border-gray-900 dark:hover:border-slate-700 transition-all duration-300"
          >
            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
import { computed, watch } from 'vue'
import { useCompanyColors } from '@/Composables/useCompanyColors'

// Colores de empresa
const { colors } = useCompanyColors()

const props = defineProps({
  total: { type: Number, default: 0 },
  borrador: { type: Number, default: 0 },
  aprobadas: { type: Number, default: 0 },
  pendientes: { type: Number, default: 0 },
  cancelada: { type: Number, default: 0 },
  searchTerm: { type: String, default: '' },
  sortBy: { type: String, default: 'fecha-desc' },
  filtroCfdi: { type: String, default: '' },
  config: { type: Object, default: () => ({}) }
})

const emit = defineEmits([
  'update:searchTerm',
  'update:sortBy',
  'update:filtroCfdi',
  'filtro-cfdi-change',
  'limpiar-filtros',
  'crear-nuevo'
])

// Computed para v-model
const searchTerm = computed({
  get: () => props.searchTerm,
  set: (value) => emit('update:searchTerm', value)
})

const sortBy = computed({
  get: () => props.sortBy,
  set: (value) => emit('update:sortBy', value)
})

const filtroCfdi = computed({
  get: () => props.filtroCfdi,
  set: (value) => emit('update:filtroCfdi', value)
})

// Event handlers
const onSearchChange = (event) => {
  emit('update:searchTerm', event.target.value)
}

const onFiltroCfdiChange = (event) => {
  emit('update:filtroCfdi', event.target.value)
  emit('filtro-cfdi-change')
}

const onLimpiarFiltros = () => {
  emit('limpiar-filtros')
}

const onCrearNuevo = () => {
  emit('crear-nuevo')
}
</script>

<style scoped>
/* Estilos específicos para el header de ventas */
@media (max-width: 640px) {
  .grid-cols-2.md\\:grid-cols-5 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (prefers-contrast: high) {
  .bg-green-50 { background-color: #f0fdf4; }
  .border-gray-200 dark:border-slate-800 { border-color: #d1d5db; }
}

button:focus-visible { outline: 2px solid; outline-offset: 2px; }

@media (hover: none) {
  .hover\\:bg-gray-50:hover { background-color: transparent; }
}
</style>
