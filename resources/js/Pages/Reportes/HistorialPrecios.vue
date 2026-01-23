<template>
  <div class="min-h-screen bg-white dark:bg-slate-900">
    <div class="w-full px-6 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Historial de Precios</h1>
            <p class="text-gray-600 dark:text-gray-300 mt-1">Consulta el historial completo de cambios de precios de productos</p>
          </div>
          <button
            @click="$inertia.visit(route('productos.index'))"
            class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Volver a Productos
          </button>
        </div>
      </div>

      <!-- Información del producto -->
      <div v-if="producto" class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ producto.nombre }}</h3>
            <p class="text-sm text-gray-600 dark:text-gray-300">{{ producto.descripcion || 'Sin descripción' }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Precio de Compra Actual</label>
            <p class="text-xl font-bold text-green-600">${{ producto.precio_compra_actual?.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) || '0.00' }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Precio de Venta Actual</label>
            <p class="text-xl font-bold text-blue-600">${{ producto.precio_venta_actual?.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) || '0.00' }}</p>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Cambio</label>
            <select v-model="filtros.tipo_cambio" class="w-full border border-gray-300 rounded-lg px-3 py-2">
              <option value="">Todos</option>
              <option value="orden_compra">Orden de Compra</option>
              <option value="edicion_orden_compra">Edición Orden Compra</option>
              <option value="orden_compra_directa">Orden Compra Directa</option>
              <option value="manual">Cambio Manual</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Desde</label>
            <input v-model="filtros.fecha_desde" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Hasta</label>
            <input v-model="filtros.fecha_hasta" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2">
          </div>
          <div class="flex items-end">
            <button @click="aplicarFiltros" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
              Aplicar Filtros
            </button>
          </div>
        </div>
      </div>

      <!-- Tabla de historial -->
      <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Historial de Cambios ({{ historial.length }} registros)
          </h3>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
            <thead class="bg-white dark:bg-slate-900">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tipo de Cambio</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Precio Compra Anterior</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Precio Compra Nuevo</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cambio</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Precio Venta Anterior</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Precio Venta Nuevo</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Usuario</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Notas</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-800">
              <tr v-for="registro in historial" :key="registro.id" class="hover:bg-white dark:bg-slate-900">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ registro.fecha }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                        :class="getTipoCambioClass(registro.tipo_cambio)">
                    {{ getTipoCambioLabel(registro.tipo_cambio) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  ${{ registro.precio_compra_anterior?.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) || '0.00' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                  ${{ registro.precio_compra_nuevo?.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) || '0.00' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <span class="font-medium" :class="registro.cambio_compra >= 0 ? 'text-green-600' : 'text-red-600'">
                    {{ registro.cambio_compra >= 0 ? '+' : '' }}${{ registro.cambio_compra?.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) || '0.00' }}
                  </span>
                  <span class="text-gray-500 dark:text-gray-400 ml-1">
                    ({{ registro.cambio_compra >= 0 ? '+' : '' }}{{ ((registro.cambio_compra / (registro.precio_compra_anterior || 1)) * 100).toFixed(1) }}%)
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  <span v-if="registro.precio_venta_anterior">
                    ${{ registro.precio_venta_anterior?.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                  </span>
                  <span v-else class="text-gray-400">N/A</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  <span v-if="registro.precio_venta_nuevo">
                    ${{ registro.precio_venta_nuevo?.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                  </span>
                  <span v-else class="text-gray-400">N/A</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ registro.usuario || 'Sistema' }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                  <div class="max-w-xs truncate" :title="registro.notas">
                    {{ registro.notas || 'Sin notas' }}
                  </div>
                </td>
              </tr>

              <!-- Mensaje cuando no hay registros -->
              <tr v-if="historial.length === 0">
                <td colspan="9" class="px-6 py-16 text-center">
                  <div class="flex flex-col items-center space-y-4">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                      <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                      </svg>
                    </div>
                    <div class="space-y-1">
                      <p class="text-gray-700 font-medium">No hay historial de precios</p>
                      <p class="text-sm text-gray-500 dark:text-gray-400">Los cambios de precios aparecerán aquí</p>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Estadísticas -->
      <div v-if="historial.length > 0" class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Precio Más Alto</p>
              <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                ${{ estadisticas.precio_max?.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) || '0.00' }}
              </p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                </svg>
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Precio Más Bajo</p>
              <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                ${{ estadisticas.precio_min?.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) || '0.00' }}
              </p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Promedio</p>
              <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                ${{ estadisticas.precio_promedio?.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) || '0.00' }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Head } from '@inertiajs/vue3'

defineProps({
  producto: {
    type: Object,
    default: null
  },
  historial: {
    type: Array,
    default: () => []
  }
})

// Filtros
const filtros = ref({
  tipo_cambio: '',
  fecha_desde: '',
  fecha_hasta: ''
})

// Estadísticas calculadas
const estadisticas = computed(() => {
  if (historial.value.length === 0) {
    return {
      precio_max: 0,
      precio_min: 0,
      precio_promedio: 0
    }
  }

  const precios = historial.value.map(h => h.precio_compra_nuevo).filter(p => p > 0)

  return {
    precio_max: Math.max(...precios),
    precio_min: Math.min(...precios),
    precio_promedio: precios.reduce((sum, p) => sum + p, 0) / precios.length
  }
})

// Función para obtener etiqueta del tipo de cambio
const getTipoCambioLabel = (tipo) => {
  const labels = {
    'orden_compra': 'Orden de Compra',
    'edicion_orden_compra': 'Edición Orden Compra',
    'orden_compra_directa': 'Orden Compra Directa',
    'manual': 'Cambio Manual'
  }
  return labels[tipo] || tipo
}

// Función para obtener clase CSS del tipo de cambio
const getTipoCambioClass = (tipo) => {
  const classes = {
    'orden_compra': 'bg-blue-100 text-blue-800',
    'edicion_orden_compra': 'bg-green-100 text-green-800',
    'orden_compra_directa': 'bg-purple-100 text-purple-800',
    'manual': 'bg-gray-100 text-gray-800 dark:text-gray-100'
  }
  return classes[tipo] || 'bg-gray-100 text-gray-800 dark:text-gray-100'
}

// Función para aplicar filtros
const aplicarFiltros = () => {
  // Esta función emitiría un evento para recargar los datos con filtros
  // Por simplicidad, recargamos la página con parámetros
  const params = new URLSearchParams()

  if (filtros.value.tipo_cambio) params.append('tipo_cambio', filtros.value.tipo_cambio)
  if (filtros.value.fecha_desde) params.append('fecha_desde', filtros.value.fecha_desde)
  if (filtros.value.fecha_hasta) params.append('fecha_hasta', filtros.value.fecha_hasta)

  const queryString = params.toString()
  window.location.href = window.location.pathname + (queryString ? '?' + queryString : '')
}

onMounted(() => {
  // Inicializar filtros desde URL si existen
  const urlParams = new URLSearchParams(window.location.search)
  filtros.value.tipo_cambio = urlParams.get('tipo_cambio') || ''
  filtros.value.fecha_desde = urlParams.get('fecha_desde') || ''
  filtros.value.fecha_hasta = urlParams.get('fecha_hasta') || ''
})
</script>

<style scoped>
/* Animaciones para las filas */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

tbody tr {
  animation: fadeIn 0.3s ease-out;
}

/* Hover effects mejorados */
tbody tr:hover {
  transform: translateX(2px);
  transition: all 0.2s ease;
}
</style>
