<template>
  <div class="min-h-screen bg-[var(--ui-surface)]">
    <div class="w-full px-6 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-slate-900">Historial de Precios</h1>
            <p class="text-slate-500 mt-1">Consulta el historial completo de cambios de precios de productos</p>
          </div>
          <button
            @click="$inertia.visit(route('productos.index'))"
            class="inline-flex items-center px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-colors"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Volver a Productos
          </button>
        </div>
      </div>

      <!-- Información del producto -->
      <div v-if="producto" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <h3 class="text-lg font-semibold text-slate-900 mb-2">{{ producto.nombre }}</h3>
            <p class="text-sm text-slate-500">{{ producto.descripcion || 'Sin descripción' }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Precio de Compra Actual</label>
            <p class="text-xl font-bold text-emerald-600">${{ producto.precio_compra_actual?.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) || '0.00' }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Precio de Venta Actual</label>
            <p class="text-xl font-bold text-blue-600">${{ producto.precio_venta_actual?.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) || '0.00' }}</p>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Tipo de Cambio</label>
            <select v-model="filtros.tipo_cambio" class="w-full border border-slate-300 rounded-xl px-3 py-2">
              <option value="">Todos</option>
              <option value="orden_compra">Orden de Compra</option>
              <option value="edicion_orden_compra">Edición Orden Compra</option>
              <option value="orden_compra_directa">Orden Compra Directa</option>
              <option value="manual">Cambio Manual</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Fecha Desde</label>
            <input v-model="filtros.fecha_desde" type="date" class="w-full border border-slate-300 rounded-xl px-3 py-2">
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Fecha Hasta</label>
            <input v-model="filtros.fecha_hasta" type="date" class="w-full border border-slate-300 rounded-xl px-3 py-2">
          </div>
          <div class="flex items-end">
            <button @click="aplicarFiltros" class="w-full bg-blue-600 text-white px-4 py-2 rounded-xl hover:bg-blue-700">
              Aplicar Filtros
            </button>
          </div>
        </div>
      </div>

      <!-- Tabla de historial -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
          <h3 class="text-lg font-semibold text-slate-900">
            Historial de Cambios ({{ historial.length }} registros)
          </h3>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-800/50">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Fecha</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tipo de Cambio</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Precio Compra Anterior</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Precio Compra Nuevo</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cambio</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Precio Venta Anterior</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Precio Venta Nuevo</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Usuario</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Notas</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
              <tr v-for="registro in historial" :key="registro.id" class="hover:bg-white">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                  {{ registro.fecha }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium"
                        :class="getTipoCambioClass(registro.tipo_cambio)">
                    {{ getTipoCambioLabel(registro.tipo_cambio) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                  ${{ registro.precio_compra_anterior?.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) || '0.00' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">
                  ${{ registro.precio_compra_nuevo?.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) || '0.00' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <span class="font-medium" :class="registro.cambio_compra >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                    {{ registro.cambio_compra >= 0 ? '+' : '' }}${{ registro.cambio_compra?.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) || '0.00' }}
                  </span>
                  <span class="text-slate-500 ml-1">
                    ({{ registro.cambio_compra >= 0 ? '+' : '' }}{{ ((registro.cambio_compra / (registro.precio_compra_anterior || 1)) * 100).toFixed(1) }}%)
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                  <span v-if="registro.precio_venta_anterior">
                    ${{ registro.precio_venta_anterior?.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                  </span>
                  <span v-else class="text-slate-400">N/A</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                  <span v-if="registro.precio_venta_nuevo">
                    ${{ registro.precio_venta_nuevo?.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                  </span>
                  <span v-else class="text-slate-400">N/A</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                  {{ registro.usuario || 'Sistema' }}
                </td>
                <td class="px-6 py-4 text-sm text-slate-900">
                  <div class="max-w-xs truncate" :title="registro.notas">
                    {{ registro.notas || 'Sin notas' }}
                  </div>
                </td>
              </tr>

              <!-- Mensaje cuando no hay registros -->
              <tr v-if="historial.length === 0">
                <td colspan="9" class="px-6 py-16 text-center">
                  <div class="flex flex-col items-center space-y-6">
                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center">
                      <svg class="w-10 h-10 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                      </svg>
                    </div>
                    <div class="space-y-1">
                      <p class="text-slate-700 font-medium">No hay historial de precios</p>
                      <p class="text-sm text-slate-500">Los cambios de precios aparecerán aquí</p>
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
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-slate-500">Precio Más Alto</p>
              <p class="text-2xl font-semibold text-slate-900">
                ${{ estadisticas.precio_max?.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) || '0.00' }}
              </p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-10 h-10 bg-rose-100 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                </svg>
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-slate-500">Precio Más Bajo</p>
              <p class="text-2xl font-semibold text-slate-900">
                ${{ estadisticas.precio_min?.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) || '0.00' }}
              </p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-10 h-10 bg-sky-100 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-slate-500">Promedio</p>
              <p class="text-2xl font-semibold text-slate-900">
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
    'orden_compra': 'bg-sky-100 text-sky-800 dark:text-sky-200',
    'edicion_orden_compra': 'bg-emerald-100 text-emerald-800 dark:text-emerald-200',
    'orden_compra_directa': 'bg-purple-100 text-purple-800',
    'manual': 'bg-slate-100 text-slate-800'
  }
  return classes[tipo] || 'bg-slate-100 text-slate-800'
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
