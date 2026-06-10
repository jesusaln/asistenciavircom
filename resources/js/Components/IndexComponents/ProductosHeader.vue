<template>
  <div class="glass-panel bg-white dark:bg-slate-800/50 rounded-xl shadow-lg border border-slate-200 dark:border-slate-800 overflow-hidden index-header-root transition-colors">
    <!-- Header con estadísticas -->
    <div class="px-6 py-6 border-b border-slate-200 dark:border-slate-800" :style="{ background: `linear-gradient(135deg, ${colors.principal}10 0%, ${colors.secundario}05 100%)` }">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-md" :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
          </div>
          <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100 tracking-tight">Productos</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Gestiona todos tus productos en un solo lugar</p>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <Link
            v-if="$page.props.empresa_config?.cva_active"
            :href="route('cva.import')"
            class="inline-flex items-center px-5 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-300 transform hover:-translate-y-0.5"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            Importar CVA
          </Link>
          <button
            @click="onCrearNueva"
            class="inline-flex items-center px-5 py-2.5 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-300 transform hover:-translate-y-0.5"
            :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)`, '--tw-ring-color': colors.principal }"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nuevo Producto
          </button>
        </div>
      </div>

      <!-- Estadísticas -->
      <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
        <div class="bg-transparent dark:bg-slate-900/50 backdrop-blur-sm rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total</p>
              <p class="text-2xl font-bold" :style="{ color: colors.principal }">{{ total }}</p>
            </div>
            <div class="w-10 h-10 rounded-full flex items-center justify-center" :style="{ backgroundColor: `${colors.principal}20` }">
              <svg class="w-5 h-5" :style="{ color: colors.principal }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-transparent dark:bg-slate-900/50 backdrop-blur-sm rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Activos</p>
              <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ activos }}</p>
            </div>
            <div class="w-10 h-10 bg-emerald-500/10 rounded-full flex items-center justify-center">
              <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-transparent dark:bg-slate-900/50 backdrop-blur-sm rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Inactivos</p>
              <p class="text-2xl font-bold text-rose-600 dark:text-rose-400">{{ inactivos }}</p>
            </div>
            <div class="w-10 h-10 bg-rose-500/10 rounded-full flex items-center justify-center">
              <svg class="w-5 h-5 text-rose-600 dark:text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-transparent dark:bg-slate-900/50 backdrop-blur-sm rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Agotados</p>
              <p class="text-2xl font-bold text-brand-600 dark:text-orange-400">{{ agotado }}</p>
            </div>
            <div class="w-10 h-10 bg-brand-500/10 rounded-full flex items-center justify-center">
              <svg class="w-5 h-5 text-brand-600 dark:text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-transparent dark:bg-slate-900/50 backdrop-blur-sm rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Valor Costo</p>
              <p class="text-2xl font-bold" :style="{ color: colors.secundario }">${{ formatearMoneda(valorTotalCosto) }}</p>
            </div>
            <div class="w-10 h-10 rounded-full flex items-center justify-center" :style="{ backgroundColor: `${colors.secundario}20` }">
              <svg class="w-5 h-5" :style="{ color: colors.secundario }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-transparent dark:bg-slate-900/50 backdrop-blur-sm rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Valor Venta</p>
              <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">${{ formatearMoneda(valorTotalVenta) }}</p>
            </div>
            <div class="w-10 h-10 bg-emerald-500/10 rounded-full flex items-center justify-center">
              <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
              </svg>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="px-6 py-4 bg-transparent dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-800">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <!-- Búsqueda -->
        <div class="flex-1 max-w-md" role="search">
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input
              v-model="searchTerm"
              type="text"
              aria-label="Buscar producto por nombre, código o descripción"
              placeholder="Buscar por nombre, código o descripción..."
              class="block w-full pl-10 pr-3 py-2 border border-slate-200 dark:border-slate-700 rounded-xl leading-5 bg-white dark:bg-slate-800 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-brand-500 focus:border-brand-500 text-sm text-slate-800 dark:text-slate-100"
              @input="onSearchChange"
              @keydown.enter="onSearchKeydown"
            />
          </div>
        </div>

        <!-- Filtros -->
        <div class="flex items-center space-x-3">
          <!-- Estado -->
          <select
            v-model="filtroEstado"
            aria-label="Filtrar por estado"
            @change="onFiltroEstadoChange"
            class="block w-48 pl-3 pr-10 py-2 text-sm border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-1 focus:ring-brand-500 focus:border-brand-500 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-100"
          >
            <option value="">Todos los Estados</option>
            <option value="activo">Activos</option>
            <option value="inactivo">Inactivos</option>
            <option value="agotado">Agotados</option>
          </select>

          <!-- Ordenamiento -->
          <select
            v-model="sortBy"
            aria-label="Ordenar por"
            @change="onSortChange"
            class="block w-48 pl-3 pr-10 py-2 text-sm border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-1 focus:ring-brand-500 focus:border-brand-500 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-100"
          >
            <option value="nombre-asc">Nombre (A-Z)</option>
            <option value="nombre-desc">Nombre (Z-A)</option>
            <option value="precio_venta-desc">Precio Mayor</option>
            <option value="precio_venta-asc">Precio Menor</option>
            <option value="stock-desc">Stock Mayor</option>
            <option value="stock-asc">Stock Menor</option>
          </select>

          <!-- Limpiar filtros -->
          <button
            @click="onLimpiarFiltros"
            class="inline-flex items-center px-3 py-2 border border-slate-200 dark:border-slate-700 text-sm leading-4 font-medium rounded-xl text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-colors duration-200"
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
import { computed, watch, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useCompanyColors } from '@/Composables/useCompanyColors'

// Colores de empresa
const { colors } = useCompanyColors()

const props = defineProps({
  total: { type: Number, default: 0 },
  activos: { type: Number, default: 0 },
  inactivos: { type: Number, default: 0 },
  agotado: { type: Number, default: 0 },
  valorTotalCosto: { type: Number, default: 0 },
  valorTotalVenta: { type: Number, default: 0 },
})

const emit = defineEmits([
  'crear-nueva', 'search-change', 'filtro-estado-change', 'sort-change', 'limpiar-filtros'
])

// Estados locales para filtros
const searchTerm = defineModel('searchTerm', { type: String, default: '' })
const sortBy = defineModel('sortBy', { type: String, default: 'nombre-asc' })
const filtroEstado = defineModel('filtroEstado', { type: String, default: '' })

// Debounce timer
let searchTimeout = null

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

// Búsqueda con debounce de 400ms
const onSearchChange = () => {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
  searchTimeout = setTimeout(() => {
    emit('search-change', searchTerm.value)
  }, 400)
}

// Búsqueda inmediata al presionar Enter
const onSearchKeydown = (event) => {
  if (event.key === 'Enter') {
    if (searchTimeout) {
      clearTimeout(searchTimeout)
    }
    emit('search-change', searchTerm.value)
  }
}

const onFiltroEstadoChange = () => emit('filtro-estado-change', filtroEstado.value)
const onSortChange = () => emit('sort-change', sortBy.value)
const onLimpiarFiltros = () => emit('limpiar-filtros')
</script>

<style scoped>
/* Estilos específicos para el header de productos */
.productos-header {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
}

@media (max-width: 768px) {
  .productos-header .grid {
    grid-template-columns: 1fr;
  }

  .productos-header h1 {
    font-size: 1.5rem;
  }
}
</style>

