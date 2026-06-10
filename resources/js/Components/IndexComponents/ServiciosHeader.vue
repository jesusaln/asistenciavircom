<template>
  <div class="bg-slate-100/40 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-xl p-6 sm:p-8 mb-6 transition-all duration-200">
    <div class="flex flex-col lg:flex-row gap-8 items-start lg:items-center justify-between mb-8">
      <!-- Izquierda -->
      <div class="flex flex-col gap-4 w-full lg:w-auto">
        <h1 class="text-3xl font-black text-slate-900 dark:text-slate-100">Servicios</h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm">Gestiona y clasifica tus catálogos de soporte y asistencias</p>
        
        <div class="flex flex-wrap gap-3 items-center mt-2">
          <button
            @click="onCrearNueva"
            class="inline-flex items-center gap-2 px-5 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white text-sm font-bold rounded-xl transition-all shadow-md shadow-blue-500/20"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Nuevo Servicio</span>
          </button>
        </div>
      </div>

      <!-- Derecha: Filtros -->
      <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto lg:flex-shrink-0">
        <!-- Búsqueda -->
        <div class="relative flex-1 sm:flex-initial">
          <input
            v-model="searchTerm"
            @input="onSearchChange"
            type="text"
            placeholder="Buscar por nombre, código..."
            class="w-full sm:w-64 lg:w-80 pl-10 pr-4 py-3 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 focus:outline-none focus:border-brand-500 dark:focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 transition-all font-medium text-sm"
          />
          <svg class="absolute left-3.5 top-3.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>

        <!-- Orden -->
        <select
          v-model="sortBy"
          @change="onSortChange"
          class="px-4 py-3 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-100 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 transition-all font-medium text-sm"
        >
          <option value="nombre-asc">Nombre (A-Z)</option>
          <option value="nombre-desc">Nombre (Z-A)</option>
          <option value="precio-desc">Precio Mayor</option>
          <option value="precio-asc">Precio Menor</option>
          <option value="duracion-desc">Duración Mayor</option>
          <option value="duracion-asc">Duración Menor</option>
          <option value="created_at-desc">Fecha (Más reciente)</option>
          <option value="created_at-asc">Fecha (Más antiguo)</option>
        </select>
      </div>
    </div>

    <!-- Estadísticas como Tarjetas-Filtro -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
      <!-- Total -->
      <div 
        @click="onFiltroEstadoChange('')"
        class="group cursor-pointer p-4 bg-white dark:bg-slate-900/50 rounded-2xl border transition-all duration-200"
        :class="filtroEstado === '' ? 'border-blue-500 shadow-md shadow-blue-500/5 ring-2 ring-blue-500/10' : 'border-slate-200/60 dark:border-slate-800/80 hover:border-slate-300 dark:hover:border-slate-700 hover:shadow-sm'"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Total</p>
            <p class="text-2xl font-black text-slate-900 dark:text-slate-100 mt-1 tabular-nums">{{ total }}</p>
          </div>
          <div class="w-10 h-10 rounded-xl flex items-center justify-center transition-colors bg-blue-50 dark:bg-sky-900/20 text-blue-600 dark:text-blue-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Activos -->
      <div 
        @click="onFiltroEstadoChange('activo')"
        class="group cursor-pointer p-4 bg-white dark:bg-slate-900/50 rounded-2xl border transition-all duration-200"
        :class="filtroEstado === 'activo' ? 'border-emerald-500 shadow-md shadow-emerald-500/5 ring-2 ring-emerald-500/10' : 'border-slate-200/60 dark:border-slate-800/80 hover:border-slate-300 dark:hover:border-slate-700 hover:shadow-sm'"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Activos</p>
            <p class="text-2xl font-black text-slate-900 dark:text-slate-100 mt-1 tabular-nums">{{ activos }}</p>
          </div>
          <div class="w-10 h-10 rounded-xl flex items-center justify-center transition-colors bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Inactivos -->
      <div 
        @click="onFiltroEstadoChange('inactivo')"
        class="group cursor-pointer p-4 bg-white dark:bg-slate-900/50 rounded-2xl border transition-all duration-200"
        :class="filtroEstado === 'inactivo' ? 'border-rose-500 shadow-md shadow-rose-500/5 ring-2 ring-rose-500/10' : 'border-slate-200/60 dark:border-slate-800/80 hover:border-slate-300 dark:hover:border-slate-700 hover:shadow-sm'"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Inactivos</p>
            <p class="text-2xl font-black text-slate-900 dark:text-slate-100 mt-1 tabular-nums">{{ inactivos }}</p>
          </div>
          <div class="w-10 h-10 rounded-xl flex items-center justify-center transition-colors bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Precio Promedio -->
      <div class="p-4 bg-white dark:bg-slate-900/50 rounded-2xl border border-slate-200/60 dark:border-slate-800/80">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Precio Promedio</p>
            <p class="text-2xl font-black text-slate-900 dark:text-slate-100 mt-1 tabular-nums">${{ formatearMoneda(precioPromedio) }}</p>
          </div>
          <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Con Categoría -->
      <div class="p-4 bg-white dark:bg-slate-900/50 rounded-2xl border border-slate-200/60 dark:border-slate-800/80">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Con Categoría</p>
            <p class="text-2xl font-black text-slate-900 dark:text-slate-100 mt-1 tabular-nums">{{ conCategoria }}</p>
          </div>
          <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useCompanyColors } from '@/Composables/useCompanyColors'

// Colores de empresa
const { colors } = useCompanyColors()

import { computed, watch } from 'vue'

const props = defineProps({
  total: { type: Number, default: 0 },
  activos: { type: Number, default: 0 },
  inactivos: { type: Number, default: 0 },
  precioPromedio: { type: Number, default: 0 },
  conCategoria: { type: Number, default: 0 },
  categorias: { type: Array, default: () => [] },
})

const emit = defineEmits([
  'crear-nueva', 'search-change', 'filtro-estado-change', 'filtro-categoria-change', 'sort-change', 'limpiar-filtros'
])

// Estados locales para filtros
const searchTerm = defineModel('searchTerm', { type: String, default: '' })
const sortBy = defineModel('sortBy', { type: String, default: 'nombre-asc' })
const filtroEstado = defineModel('filtroEstado', { type: String, default: '' })
const filtroCategoria = defineModel('filtroCategoria', { type: String, default: '' })

// Función para formatear moneda
const formatearMoneda = (num) => {
  const value = parseFloat(num);
  const safe = Number.isFinite(value) ? value : 0;
  return new Intl.NumberFormat('es-MX', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(safe);
}

// Métodos de emisión
const onCrearNueva = () => emit('crear-nueva')
const onSearchChange = () => emit('search-change', searchTerm.value)
const onFiltroEstadoChange = () => emit('filtro-estado-change', filtroEstado.value)
const onFiltroCategoriaChange = () => emit('filtro-categoria-change', filtroCategoria.value)
const onSortChange = () => emit('sort-change', sortBy.value)
const onLimpiarFiltros = () => emit('limpiar-filtros')

// Watch para limpiar filtros automáticamente
watch([searchTerm, sortBy, filtroEstado, filtroCategoria], () => {
  // Emitir cambios automáticamente
}, { immediate: true })
</script>

<style scoped>
/* Estilos específicos para el header de servicios */
.servicios-header {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
}

@media (max-width: 768px) {
  .servicios-header .grid {
    grid-template-columns: 1fr;
  }

  .servicios-header h1 {
    font-size: 1.5rem;
  }
}
</style>

