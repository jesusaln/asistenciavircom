<template>
  <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-100 dark:border-slate-800 overflow-hidden">
    <!-- Header con estadísticas -->
    <div class="px-6 py-6 border-b border-gray-200 dark:border-slate-800/60" :style="{ background: `linear-gradient(135deg, ${colors.principal}15 0%, ${colors.secundario}10 100%)` }">
        <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-md" :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Compras</h1>
            <p class="text-sm text-gray-600 mt-0.5">Gestiona todas tus compras en un solo lugar</p>
          </div>
        </div>
        <div class="flex items-center space-x-3">
          <button
            @click="onImportarXml"
            class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-xl hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-colors duration-200"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Importar XML
          </button>
          <button
            @click="onCrearNueva"
            class="inline-flex items-center px-5 py-2.5 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-300 transform hover:-translate-y-0.5"
            :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)`, '--tw-ring-color': colors.principal }"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nueva Compra
          </button>
        </div>
      </div>

      <!-- Estadísticas -->
      <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-slate-900/80 backdrop-blur-sm rounded-xl p-4 border border-gray-200 dark:border-slate-800/50 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Total</p>
              <p class="text-2xl font-bold" :style="{ color: colors.principal }">{{ total }}</p>
            </div>
            <div class="w-10 h-10 rounded-full flex items-center justify-center" :style="{ backgroundColor: `${colors.principal}20` }">
              <svg class="w-5 h-5" :style="{ color: colors.principal }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900/80 backdrop-blur-sm rounded-xl p-4 border border-gray-200 dark:border-slate-800/50 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Procesadas</p>
              <p class="text-2xl font-bold text-green-600">{{ procesadas }}</p>
            </div>
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
              <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900/80 backdrop-blur-sm rounded-xl p-4 border border-gray-200 dark:border-slate-800/50 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Canceladas</p>
              <p class="text-2xl font-bold text-red-600">{{ canceladas }}</p>
            </div>
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
              <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900/80 backdrop-blur-sm rounded-xl p-4 border border-gray-200 dark:border-slate-800/50 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Monto Total</p>
              <p class="text-2xl font-bold" :style="{ color: colors.secundario }">${{ formatearMoneda(montoTotal) }}</p>
            </div>
            <div class="w-10 h-10 rounded-full flex items-center justify-center" :style="{ backgroundColor: `${colors.secundario}20` }">
              <svg class="w-5 h-5" :style="{ color: colors.secundario }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900/80 backdrop-blur-sm rounded-xl p-4 border border-gray-200 dark:border-slate-800/50 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Pendientes de Pago</p>
              <p class="text-2xl font-bold text-amber-600">${{ formatearMoneda(pendientesPago) }}</p>
            </div>
            <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">
              <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
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
              placeholder="Buscar por proveedor, número de compra..."
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
            <option value="procesada">Procesadas</option>
            <option value="cancelada">Canceladas</option>
          </select>

          <!-- Origen -->
          <select
            v-model="filtroOrigen"
            @change="onFiltroOrigenChange"
            class="block w-48 pl-3 pr-10 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-slate-900"
          >
            <option value="">Todos los Orígenes</option>
            <option value="directa">Compras Directas</option>
            <option value="orden_compra">De Orden de Compra</option>
          </select>

          <!-- Ordenamiento -->
          <select
            v-model="sortBy"
            @change="onSortChange"
            class="block w-48 pl-3 pr-10 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-slate-900"
          >
            <option value="created_at-desc">Fecha (Más reciente)</option>
            <option value="created_at-asc">Fecha (Más antiguo)</option>
            <option value="total-desc">Monto (Mayor)</option>
            <option value="total-asc">Monto (Menor)</option>
            <option value="proveedor-asc">Proveedor (A-Z)</option>
            <option value="proveedor-desc">Proveedor (Z-A)</option>
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
import { computed, watch } from 'vue'
import { useCompanyColors } from '@/Composables/useCompanyColors'

// Colores de empresa
const { colors } = useCompanyColors()

const props = defineProps({
  total: { type: Number, default: 0 },
  procesadas: { type: Number, default: 0 },
  canceladas: { type: Number, default: 0 },
  montoTotal: { type: Number, default: 0 },
  pendientesPago: { type: Number, default: 0 },
})

const emit = defineEmits([
  'crear-nueva', 'importar-xml', 'search-change', 'filtro-estado-change', 'filtro-origen-change', 'sort-change', 'limpiar-filtros'
])

// Estados locales para filtros
const searchTerm = defineModel('searchTerm', { type: String, default: '' })
const sortBy = defineModel('sortBy', { type: String, default: 'created_at-desc' })
const filtroEstado = defineModel('filtroEstado', { type: String, default: '' })
const filtroOrigen = defineModel('filtroOrigen', { type: String, default: '' })

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
const onImportarXml = () => emit('importar-xml')
const onSearchChange = () => emit('search-change', searchTerm.value)
const onFiltroEstadoChange = () => emit('filtro-estado-change', filtroEstado.value)
const onFiltroOrigenChange = () => emit('filtro-origen-change', filtroOrigen.value)
const onSortChange = () => emit('sort-change', sortBy.value)
const onLimpiarFiltros = () => emit('limpiar-filtros')

// Watch para limpiar filtros automáticamente
watch([searchTerm, sortBy, filtroEstado, filtroOrigen], () => {
  // Emitir cambios automáticamente
}, { immediate: true })
</script>

<style scoped>
/* Estilos específicos para el header de compras */
.compras-header {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
}

@media (max-width: 768px) {
  .compras-header .grid {
    grid-template-columns: 1fr;
  }

  .compras-header h1 {
    font-size: 1.5rem;
  }
}
</style>

