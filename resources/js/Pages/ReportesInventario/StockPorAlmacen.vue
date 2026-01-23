<!-- /resources/js/Pages/ReportesInventario/StockPorAlmacen.vue -->
<template>
  <Head title="Stock por Almacén" />

  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 p-6">
    <div class="w-full">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Stock por Almacén</h1>
            <p class="text-gray-600 dark:text-gray-300 mt-1">Distribución de productos por almacén</p>
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

      <!-- Filtros -->
      <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 mb-6">
        <div class="flex flex-col md:flex-row gap-4">
          <div class="flex-1">
            <label for="almacen" class="block text-sm font-medium text-gray-700 mb-2">Filtrar por Almacén</label>
            <select
              id="almacen"
              v-model="filtros.almacen_id"
              @change="aplicarFiltros"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">Todos los almacenes</option>
              <option v-for="almacen in almacenes" :key="almacen.id" :value="almacen.id">
                {{ almacen.nombre }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Resultados -->
      <div v-if="reporte.length > 0" class="space-y-6">
        <div v-for="almacen in reporte" :key="almacen.almacen" class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden">
          <!-- Header del almacén -->
          <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <svg class="w-6 h-6 text-white mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <h3 class="text-xl font-bold text-white">{{ almacen.almacen }}</h3>
              </div>
              <div class="text-right">
                <p class="text-blue-100 text-sm">Total productos</p>
                <p class="text-white text-2xl font-bold">{{ almacen.total_productos }}</p>
              </div>
            </div>
          </div>

          <!-- Estadísticas del almacén -->
          <div class="bg-white dark:bg-slate-900 px-6 py-4 border-b border-gray-200 dark:border-slate-800">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="text-center">
                <p class="text-sm text-gray-600 dark:text-gray-300">Total Cantidad</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ almacen.total_cantidad }}</p>
              </div>
              <div class="text-center">
                <p class="text-sm text-gray-600 dark:text-gray-300">Valor Total</p>
                <p class="text-2xl font-bold text-green-600">${{ formatCurrency(almacen.valor_total) }}</p>
              </div>
              <div class="text-center">
                <p class="text-sm text-gray-600 dark:text-gray-300">Productos con Stock</p>
                <p class="text-2xl font-bold text-blue-600">{{ almacen.productos.filter(p => p.cantidad > 0).length }}</p>
              </div>
            </div>
          </div>

          <!-- Tabla de productos -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
              <thead class="bg-white dark:bg-slate-900">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Producto</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Código</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cantidad</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stock Mínimo</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Precio Venta</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Valor Total</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-800">
                <tr v-for="producto in almacen.productos" :key="producto.producto" class="hover:bg-white dark:bg-slate-900">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ producto.producto }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ producto.codigo }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900 dark:text-white">{{ producto.cantidad }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ producto.stock_minimo }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900 dark:text-white">${{ formatCurrency(producto.precio_venta) }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-green-600">${{ formatCurrency(producto.valor_total) }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span
                      :class="producto.estado === 'bajo_stock' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    >
                      {{ producto.estado === 'bajo_stock' ? 'Bajo Stock' : 'Normal' }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Sin resultados -->
      <div v-else class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-12 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No hay datos disponibles</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No se encontraron productos en los almacenes seleccionados.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

// Props
const props = defineProps({
  reporte: {
    type: Array,
    default: () => []
  },
  almacenes: {
    type: Array,
    default: () => []
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
  router.get(route('reportes.inventario.stock-por-almacen'), {
    almacen_id: filtros.value.almacen_id
  }, { preserveState: true })
}

onMounted(() => {
  // Inicializar filtros
})
</script>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>

