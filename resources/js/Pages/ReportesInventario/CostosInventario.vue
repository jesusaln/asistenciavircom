<!-- /resources/js/Pages/ReportesInventario/CostosInventario.vue -->
<template>
  <Head title="Costos de Inventario" />

  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-purple-50 p-6">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Costos de Inventario</h1>
            <p class="text-gray-600 mt-1">Valorización y análisis de costos del inventario</p>
          </div>
          <Link
            :href="route('reportes.inventario.dashboard')"
            class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Volver
          </Link>
        </div>
      </div>

      <!-- Filtros y Totales -->
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
        <!-- Filtro de tipo de costo -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
          <label for="tipo_costo" class="block text-sm font-medium text-gray-700 mb-2">Tipo de Costo</label>
          <select
            id="tipo_costo"
            v-model="filtros.tipo_costo"
            @change="aplicarFiltros"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
          >
            <option value="promedio">Costo Promedio</option>
            <option value="ultimo">Último Costo</option>
          </select>
          <p class="mt-2 text-xs text-gray-500">
            {{ filtros.tipo_costo === 'promedio' ? 'Calcula el costo promedio ponderado' : 'Usa el último costo de entrada' }}
          </p>
        </div>

        <!-- Estadísticas -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
          <div class="flex items-center">
            <div class="p-3 bg-blue-100 rounded-lg">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Productos con Stock</p>
              <p class="text-2xl font-bold text-blue-600">{{ totales.productos_con_stock }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
          <div class="flex items-center">
            <div class="p-3 bg-green-100 rounded-lg">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Valor Total</p>
              <p class="text-2xl font-bold text-green-600">${{ formatCurrency(totales.valor_total_inventario) }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
          <div class="flex items-center">
            <div class="p-3 bg-yellow-100 rounded-lg">
              <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Margen Promedio</p>
              <p class="text-2xl font-bold text-yellow-600">{{ (totales.margen_promedio || 0).toFixed(1) }}%</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabla de productos -->
      <div v-if="productos.length > 0" class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Costo Unitario</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio Venta</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor Inventario</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Margen</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Distribución</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="producto in productos" :key="producto.id" class="hover:bg-gray-50">
                <td class="px-6 py-4">
                  <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10">
                      <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                        <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                      </div>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900">{{ producto.nombre }}</div>
                      <div class="text-sm text-gray-500">{{ producto.categoria }} • {{ producto.marca }}</div>
                      <div class="text-xs text-gray-400">Código: {{ producto.codigo }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900">{{ producto.stock_total }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900">${{ formatCurrency(producto.costo_unitario) }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900">${{ formatCurrency(producto.precio_venta) }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-green-600">${{ formatCurrency(producto.valor_total_inventario) }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="producto.margen_ganancia >= 20 ? 'bg-green-100 text-green-800' : producto.margen_ganancia >= 10 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    {{ producto.margen_ganancia.toFixed(1) }}%
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div class="space-y-1">
                    <div v-for="dist in producto.distribucion_almacenes.slice(0, 2)" :key="dist.almacen" class="text-xs">
                      <span class="font-medium">{{ dist.almacen }}:</span> {{ dist.cantidad }} (${{ formatCurrency(dist.valor) }})
                    </div>
                    <div v-if="producto.distribucion_almacenes.length > 2" class="text-xs text-gray-500">
                      +{{ producto.distribucion_almacenes.length - 2 }} más...
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Sin resultados -->
      <div v-else class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No hay productos con stock</h3>
        <p class="mt-1 text-sm text-gray-500">No se encontraron productos con inventario para calcular costos.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

// Props
const props = defineProps({
  productos: {
    type: [Array, Object],
    default: () => []
  },
  totales: {
    type: Object,
    default: () => ({})
  },
  filtros: {
    type: Object,
    default: () => ({})
  }
})

// Estado reactivo
const filtros = ref({ ...props.filtros })

// Funciones
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN'
  }).format(amount || 0)
}

const aplicarFiltros = () => {
  router.get(route('reportes.inventario.costos'), filtros.value, { preserveState: true })
}
</script>

<style scoped>
@import "tailwindcss" reference;
/* Estilos adicionales si son necesarios */
</style>

