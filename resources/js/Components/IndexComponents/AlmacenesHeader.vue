<template>
  <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl shadow-lg dark:shadow-none border border-gray-100 dark:border-slate-800 dark:border-gray-700 overflow-hidden">
    <!-- Header con estadísticas -->
    <div class="px-6 py-6 border-b border-gray-200 dark:border-slate-800/60">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Almacenes</h1>
          <p class="text-sm text-gray-600 mt-1">Gestiona todos tus almacenes en un solo lugar</p>
        </div>
        <button
          @click="onCrearNueva"
          class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
          Nuevo Almacén
        </button>
      </div>

      <!-- Estadísticas -->
      <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-slate-900/70 rounded-lg p-4 border border-gray-200 dark:border-slate-800/50">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Total</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ total }}</p>
            </div>
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
              <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900/70 rounded-lg p-4 border border-gray-200 dark:border-slate-800/50">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Activos</p>
              <p class="text-2xl font-bold text-green-600">{{ activos }}</p>
            </div>
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
              <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900/70 rounded-lg p-4 border border-gray-200 dark:border-slate-800/50">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Inactivos</p>
              <p class="text-2xl font-bold text-red-600">{{ inactivos }}</p>
            </div>
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
              <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900/70 rounded-lg p-4 border border-gray-200 dark:border-slate-800/50">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Con Responsable</p>
              <p class="text-2xl font-bold text-blue-600">{{ conResponsable }}</p>
            </div>
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
              <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900/70 rounded-lg p-4 border border-gray-200 dark:border-slate-800/50">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Con Teléfono</p>
              <p class="text-2xl font-bold text-purple-600">{{ conTelefono }}</p>
            </div>
            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
              <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
              </svg>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="px-6 py-4 bg-gray-50/50 border-b border-gray-200 dark:border-slate-800/60">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <!-- Búsqueda -->
        <div class="flex-1 max-w-md">
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input
              v-model="searchTerm"
              type="text"
              placeholder="Buscar por nombre, dirección, responsable..."
              class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white dark:bg-slate-900 placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm"
              @input="onSearchChange"
            />
          </div>
        </div>

        <!-- Filtros -->
        <div class="flex items-center space-x-3">
          <!-- Estado -->
          <select
            v-model="filtroEstado"
            @change="onFiltroEstadoChange"
            class="block w-48 pl-3 pr-10 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-slate-900"
          >
            <option value="">Todos los Estados</option>
            <option value="activo">Activos</option>
            <option value="inactivo">Inactivos</option>
          </select>

          <!-- Tipo de filtro -->
          <select
            v-model="filtroTipo"
            @change="onFiltroTipoChange"
            class="block w-48 pl-3 pr-10 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-slate-900"
          >
            <option value="">Todos los Tipos</option>
            <option value="con_responsable">Con Responsable</option>
            <option value="sin_responsable">Sin Responsable</option>
            <option value="con_telefono">Con Teléfono</option>
            <option value="sin_telefono">Sin Teléfono</option>
          </select>

          <!-- Ordenamiento -->
          <select
            v-model="sortBy"
            @change="onSortChange"
            class="block w-48 pl-3 pr-10 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-slate-900"
          >
            <option value="nombre-asc">Nombre (A-Z)</option>
            <option value="nombre-desc">Nombre (Z-A)</option>
            <option value="created_at-desc">Fecha (Más reciente)</option>
            <option value="created_at-asc">Fecha (Más antiguo)</option>
          </select>

          <!-- Limpiar filtros -->
          <button
            @click="onLimpiarFiltros"
            class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-lg text-gray-700 bg-white dark:bg-slate-900 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
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

// Colores de empresa
const { colors } = useCompanyColors()

import { computed, watch } from 'vue'

const props = defineProps({
  total: { type: Number, default: 0 },
  activos: { type: Number, default: 0 },
  inactivos: { type: Number, default: 0 },
  conResponsable: { type: Number, default: 0 },
  conTelefono: { type: Number, default: 0 },
})

const emit = defineEmits([
  'crear-nueva', 'search-change', 'filtro-estado-change', 'filtro-tipo-change', 'sort-change', 'limpiar-filtros'
])

// Estados locales para filtros
const searchTerm = defineModel('searchTerm', { type: String, default: '' })
const sortBy = defineModel('sortBy', { type: String, default: 'nombre-asc' })
const filtroEstado = defineModel('filtroEstado', { type: String, default: '' })
const filtroTipo = defineModel('filtroTipo', { type: String, default: '' })

// Métodos de emisión
const onCrearNueva = () => emit('crear-nueva')
const onSearchChange = () => emit('search-change', searchTerm.value)
const onFiltroEstadoChange = () => emit('filtro-estado-change', filtroEstado.value)
const onFiltroTipoChange = () => emit('filtro-tipo-change', filtroTipo.value)
const onSortChange = () => emit('sort-change', sortBy.value)
const onLimpiarFiltros = () => emit('limpiar-filtros')

// Watch para limpiar filtros automáticamente
watch([searchTerm, sortBy, filtroEstado, filtroTipo], () => {
  // Emitir cambios automáticamente
}, { immediate: true })
</script>

<style scoped>
/* Estilos específicos para el header de almacenes */
.almacenes-header {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
}

@media (max-width: 768px) {
  .almacenes-header .grid {
    grid-template-columns: 1fr;
  }

  .almacenes-header h1 {
    font-size: 1.5rem;
  }
}
</style>

