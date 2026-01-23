<template>
  <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl shadow-lg border border-gray-100 dark:border-slate-800 dark:border-gray-700 overflow-hidden transition-colors">
    <!-- Header con estadísticas -->
    <div class="px-6 py-6 border-b border-gray-200 dark:border-slate-800/60 dark:border-gray-700" :style="{ background: `linear-gradient(135deg, ${colors.principal}15 0%, ${colors.secundario}10 100%)` }">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-md" :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-white tracking-tight">Cotizaciones</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">Gestiona todas tus cotizaciones en un solo lugar</p>
          </div>
        </div>
        <button
          @click="onCrearNueva"
          class="inline-flex items-center px-5 py-2.5 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-300 transform hover:-translate-y-0.5"
          :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)`, '--tw-ring-color': colors.principal }"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Nueva Cotización
        </button>
      </div>

      <!-- Estadísticas -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900/80 dark:bg-gray-700/80 backdrop-blur-sm rounded-xl p-4 border border-gray-200 dark:border-slate-800/50 dark:border-gray-600/50 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total</p>
              <p class="text-2xl font-bold" :style="{ color: colors.principal }">{{ total }}</p>
            </div>
            <div class="w-10 h-10 rounded-full flex items-center justify-center" :style="{ backgroundColor: `${colors.principal}20` }">
              <svg class="w-5 h-5" :style="{ color: colors.principal }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900/80 dark:bg-gray-700/80 backdrop-blur-sm rounded-xl p-4 border border-gray-200 dark:border-slate-800/50 dark:border-gray-600/50 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Pendientes</p>
              <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ pendientes }}</p>
            </div>
            <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/50 rounded-full flex items-center justify-center">
              <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900/80 dark:bg-gray-700/80 backdrop-blur-sm rounded-xl p-4 border border-gray-200 dark:border-slate-800/50 dark:border-gray-600/50 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Enviado a Pedido</p>
              <p class="text-2xl font-bold" :style="{ color: colors.secundario }">{{ enviado_pedido }}</p>
            </div>
            <div class="w-10 h-10 rounded-full flex items-center justify-center" :style="{ backgroundColor: `${colors.secundario}20` }">
              <svg class="w-5 h-5" :style="{ color: colors.secundario }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900/80 dark:bg-gray-700/80 backdrop-blur-sm rounded-xl p-4 border border-gray-200 dark:border-slate-800/50 dark:border-gray-600/50 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Cancelado</p>
              <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ cancelado }}</p>
            </div>
            <div class="w-10 h-10 bg-red-100 dark:bg-red-900/50 rounded-full flex items-center justify-center">
              <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-slate-800/60 dark:border-gray-700">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <!-- Búsqueda -->
        <div class="flex-1 max-w-md">
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-gray-400 dark:text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input
              v-model="searchTerm"
              type="text"
              placeholder="Buscar por cliente, número..."
              class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg leading-5 bg-white dark:bg-slate-900 dark:bg-gray-800 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm transition-colors"
              @input="onSearchChange"
            />
          </div>
        </div>

        <!-- Filtros -->
        <div class="flex items-center space-x-3">
          <!-- Filtro de estado -->
          <select
            v-model="filtroEstado"
            @change="onFiltroEstadoChange"
            class="block w-48 pl-3 pr-10 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-slate-900 dark:bg-gray-800 dark:text-white transition-colors"
          >
            <option value="">Todos los estados</option>
            <option value="borrador">Borrador</option>
            <option value="pendiente">Pendiente</option>
            <option value="aprobada">Aprobada</option>
            <option value="rechazada">Rechazada</option>
            <option value="enviada">Enviada</option>
            <option value="enviado_pedido">Enviado a Pedido</option>
            <option value="cancelado">Cancelado</option>
          </select>

          <!-- Ordenamiento -->
          <select
            v-model="sortBy"
            @change="onSortChange"
            class="block w-48 pl-3 pr-10 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-slate-900 dark:bg-gray-800 dark:text-white transition-colors"
          >
            <option value="fecha-desc">Fecha (Más reciente)</option>
            <option value="fecha-asc">Fecha (Más antiguo)</option>
            <option value="cliente-asc">Cliente (A-Z)</option>
            <option value="cliente-desc">Cliente (Z-A)</option>
            <option value="total-desc">Total (Mayor)</option>
            <option value="total-asc">Total (Menor)</option>
            <option value="estado-asc">Estado (A-Z)</option>
            <option value="estado-desc">Estado (Z-A)</option>
          </select>

          <!-- Limpiar filtros -->
          <button
            @click="onLimpiarFiltros"
            class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm leading-4 font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-slate-900 dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
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
import { computed, watch } from 'vue'
import { useCompanyColors } from '@/Composables/useCompanyColors'

// Colores de empresa
const { colors } = useCompanyColors()

const props = defineProps({
  total: { type: Number, default: 0 },
  pendientes: { type: Number, default: 0 },
  enviado_pedido: { type: Number, default: 0 },
  cancelado: { type: Number, default: 0 }
})

const emit = defineEmits([
  'crear-nueva', 'search-change', 'filtro-estado-change', 'sort-change', 'limpiar-filtros'
])

// Estados locales para filtros
const searchTerm = defineModel('searchTerm', { type: String, default: '' })
const sortBy = defineModel('sortBy', { type: String, default: 'fecha-desc' })
const filtroEstado = defineModel('filtroEstado', { type: String, default: '' })

// Métodos de emisión
const onCrearNueva = () => emit('crear-nueva')
const onSearchChange = () => emit('search-change', searchTerm.value)
const onFiltroEstadoChange = () => emit('filtro-estado-change', filtroEstado.value)
const onSortChange = () => emit('sort-change', sortBy.value)
const onLimpiarFiltros = () => emit('limpiar-filtros')

// Watch para limpiar filtros automáticamente
watch([searchTerm, sortBy, filtroEstado], () => {
  // Emitir cambios automáticamente
}, { immediate: true })
</script>

<style scoped>
/* Estilos específicos para el header de cotizaciones */
.cotizaciones-header {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
}

@media (max-width: 768px) {
  .cotizaciones-header .grid {
    grid-template-columns: 1fr;
  }

  .cotizaciones-header h1 {
    font-size: 1.5rem;
  }
}
</style>

