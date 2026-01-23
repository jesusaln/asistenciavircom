<!-- /resources/js/Pages/ReportesInventario/ProductosBajoStock.vue -->
<template>
  <Head title="Productos Bajo Stock" />

  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-red-50 p-6">
    <div class="w-full">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Productos Bajo Stock</h1>
            <p class="text-gray-600 dark:text-gray-300 mt-1">Productos que necesitan reposición inmediata</p>
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

      <!-- Estadísticas -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6">
          <div class="flex items-center">
            <div class="p-3 bg-red-100 rounded-lg">
              <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Productos Críticos</p>
              <p class="text-2xl font-bold text-red-600">{{ productos.filter(p => p.deficit > 10).length }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6">
          <div class="flex items-center">
            <div class="p-3 bg-yellow-100 rounded-lg">
              <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Total Productos</p>
              <p class="text-2xl font-bold text-yellow-600">{{ productos.length }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6">
          <div class="flex items-center">
            <div class="p-3 bg-blue-100 rounded-lg">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Valor Total Perdido</p>
              <p class="text-2xl font-bold text-blue-600">${{ formatCurrency(totalValorPerdido) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabla de productos -->
      <div v-if="productos.length > 0" class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
            <thead class="bg-white dark:bg-slate-900">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Producto</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stock Actual</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stock Mínimo</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Déficit</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Precio Venta</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Valor Perdido</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Último Movimiento</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Distribución</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-800">
              <tr v-for="producto in productos" :key="producto.id" class="hover:bg-white dark:bg-slate-900">
                <td class="px-6 py-4">
                  <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10">
                      <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                        <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                      </div>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900 dark:text-white">{{ producto.nombre }}</div>
                      <div class="text-sm text-gray-500 dark:text-gray-400">{{ producto.categoria }} • {{ producto.marca }}</div>
                      <div class="text-xs text-gray-400">Código: {{ producto.codigo }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    {{ producto.stock_actual }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900 dark:text-white">{{ producto.stock_minimo }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                    -{{ producto.deficit }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900 dark:text-white">${{ formatCurrency(producto.precio_venta) }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-red-600">${{ formatCurrency(producto.deficit * producto.precio_venta) }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div v-if="producto.ultimo_movimiento" class="text-sm text-gray-900 dark:text-white">
                    <div>{{ producto.ultimo_movimiento.tipo === 'entrada' ? 'Entrada' : 'Salida' }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(producto.ultimo_movimiento.fecha) }}</div>
                  </div>
                  <div v-else class="text-sm text-gray-500 dark:text-gray-400">Sin movimientos</div>
                </td>
                <td class="px-6 py-4">
                  <div class="space-y-1">
                    <div v-for="dist in producto.distribucion_almacenes.slice(0, 2)" :key="dist.almacen" class="text-xs">
                      <span class="font-medium">{{ dist.almacen }}:</span> {{ dist.cantidad }}
                    </div>
                    <div v-if="producto.distribucion_almacenes.length > 2" class="text-xs text-gray-500 dark:text-gray-400">
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
      <div v-else class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-12 text-center">
        <svg class="mx-auto h-12 w-12 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">¡Excelente!</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Todos los productos están por encima del stock mínimo.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

// Props
const props = defineProps({
  productos: {
    type: Array,
    default: () => []
  }
})

// Computed
const totalValorPerdido = computed(() => {
  return props.productos.reduce((total, producto) => {
    return total + (producto.deficit * producto.precio_venta)
  }, 0)
})

// Funciones
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN'
  }).format(amount || 0)
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('es-MX', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}
</script>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>

