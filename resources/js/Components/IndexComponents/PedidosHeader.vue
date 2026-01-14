<template>
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" :style="cssVars">
    <!-- Header con estadísticas -->
    <div class="px-6 py-6 border-b border-white/30 text-white" :style="headerGradientStyle">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold tracking-tight">Pedidos</h1>
          <p class="text-sm text-white/90 mt-1">Gestiona todos tus pedidos en un solo lugar</p>
        </div>
        <button
          @click="onCrearNueva"
          class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg text-white shadow-sm hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-white/60 focus:ring-offset-2 focus:ring-offset-transparent transition-all duration-200"
          :style="primaryButtonStyle"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Nuevo Pedido
        </button>
      </div>

      <!-- Estadísticas -->
      <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
        <div class="bg-white/80 rounded-lg p-4 border border-white/40">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-white/80">Total</p>
              <p class="text-2xl font-bold text-white">{{ total }}</p>
            </div>
            <div class="w-10 h-10 rounded-full flex items-center justify-center" :style="{ backgroundColor: `${colors.principal}25` }">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" :style="{ color: colors.principal }">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white/80 rounded-lg p-4 border border-white/40">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-white/80">Borradores</p>
              <p class="text-2xl font-bold text-white">{{ borradores }}</p>
            </div>
            <div class="w-10 h-10 rounded-full flex items-center justify-center" :style="{ backgroundColor: `${colors.secundario}25` }">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" :style="{ color: colors.secundario }">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white/80 rounded-lg p-4 border border-white/40">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-white/80">Pendientes</p>
              <p class="text-2xl font-bold text-white">{{ pendientes }}</p>
            </div>
            <div class="w-10 h-10 rounded-full flex items-center justify-center" :style="{ backgroundColor: `${colors.principal}25` }">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" :style="{ color: colors.principal }">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white/80 rounded-lg p-4 border border-white/40">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-white/80">Confirmados</p>
              <p class="text-2xl font-bold text-white">{{ confirmados }}</p>
            </div>
            <div class="w-10 h-10 rounded-full flex items-center justify-center" :style="{ backgroundColor: `${colors.principal}25` }">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" :style="{ color: colors.principal }">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white/80 rounded-lg p-4 border border-white/40">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-white/80">Enviados</p>
              <p class="text-2xl font-bold text-white">{{ enviados_venta }}</p>
            </div>
            <div class="w-10 h-10 rounded-full flex items-center justify-center" :style="{ backgroundColor: `${colors.secundario}25` }">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" :style="{ color: colors.secundario }">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white/80 rounded-lg p-4 border border-white/40">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-white/80">Cancelados</p>
              <p class="text-2xl font-bold text-white">{{ cancelados }}</p>
            </div>
            <div class="w-10 h-10 rounded-full flex items-center justify-center" :style="{ backgroundColor: `${colors.principal}25` }">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" :style="{ color: colors.principal }">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="px-6 py-4 bg-gray-50/60 border-b border-gray-200/60">
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
              placeholder="Buscar por cliente, número..."
              class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:border-transparent text-sm"
              :style="focusRingStyle"
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
            class="block w-48 pl-3 pr-10 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:border-transparent bg-white"
            :style="focusRingStyle"
          >
            <option value="">Todos los estados</option>
            <option value="borrador">Borrador</option>
            <option value="pendiente">Pendiente</option>
            <option value="confirmado">Confirmado</option>
            <option value="enviado_venta">Enviado a Venta</option>
            <option value="cancelado">Cancelado</option>
          </select>

          <!-- Filtro de cliente -->
          <select
            v-model="filtroCliente"
            @change="onFiltroClienteChange"
            class="block w-48 pl-3 pr-10 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:border-transparent bg-white"
            :style="focusRingStyle"
          >
            <option value="">Todos los clientes</option>
            <option v-for="cliente in filterOptions?.clientes || []" :key="cliente.value" :value="cliente.value">
              {{ cliente.label }}
            </option>
          </select>

          <!-- Ordenamiento -->
          <select
            v-model="sortBy"
            @change="onSortChange"
            class="block w-48 pl-3 pr-10 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:border-transparent bg-white"
            :style="focusRingStyle"
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
            class="inline-flex items-center px-3 py-2 border text-sm leading-4 font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200"
            :style="focusRingStyle"
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

const props = defineProps({
  total: { type: Number, default: 0 },
  borradores: { type: Number, default: 0 },
  pendientes: { type: Number, default: 0 },
  confirmados: { type: Number, default: 0 },
  enviados_venta: { type: Number, default: 0 },
  cancelados: { type: Number, default: 0 },
  filterOptions: { type: Object, default: () => ({}) }
})

const emit = defineEmits([
  'crear-nueva', 'search-change', 'filtro-estado-change', 'filtro-cliente-change', 'sort-change', 'limpiar-filtros'
])

const { colors, cssVars, headerGradientStyle, primaryButtonStyle, focusRingStyle } = useCompanyColors()

// Estados locales para filtros
const searchTerm = defineModel('searchTerm', { type: String, default: '' })
const sortBy = defineModel('sortBy', { type: String, default: 'fecha-desc' })
const filtroEstado = defineModel('filtroEstado', { type: String, default: '' })
const filtroCliente = defineModel('filtroCliente', { type: String, default: '' })

// Métodos de emisión
const onCrearNueva = () => emit('crear-nueva')
const onSearchChange = () => emit('search-change', searchTerm.value)
const onFiltroEstadoChange = () => emit('filtro-estado-change', filtroEstado.value)
const onFiltroClienteChange = () => emit('filtro-cliente-change', filtroCliente.value)
const onSortChange = () => emit('sort-change', sortBy.value)
const onLimpiarFiltros = () => emit('limpiar-filtros')

// Watch para limpiar filtros automáticamente
watch([searchTerm, sortBy, filtroEstado, filtroCliente], () => {
  // Emitir cambios automáticamente
}, { immediate: true })
</script>

<style scoped>
/* Estilos específicos para el header de pedidos */
.pedidos-header {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
}

@media (max-width: 768px) {
  .pedidos-header .grid {
    grid-template-columns: 1fr;
  }

  .pedidos-header h1 {
    font-size: 1.5rem;
  }
}
</style>
