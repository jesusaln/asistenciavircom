<!-- /resources/js/Pages/ReportesInventario/StockPorAlmacen.vue -->
<template>
  <Head title="Stock por Almacén" />

  <div class="min-h-screen bg-[var(--ui-surface)] p-6">
    <div class="w-full">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-slate-900">Stock por Almacén</h1>
            <p class="text-slate-500 mt-1">Distribución de productos por almacén</p>
          </div>
          <Link
            :href="route('reportes.inventario.dashboard')"
            class="inline-flex items-center px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-colors"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Volver
          </Link>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-6">
        <div class="flex flex-col md:flex-row gap-4">
          <div class="flex-1">
            <label for="almacen" class="block text-sm font-medium text-slate-700 mb-2">Filtrar por Almacén</label>
            <select
              id="almacen"
              v-model="filtros.almacen_id"
              @change="aplicarFiltros"
              class="w-full px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent"
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
        <div v-for="almacen in reporte" :key="almacen.almacen" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
          <!-- Header del almacén -->
          <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <svg class="w-10 h-10 text-white mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
          <div class="bg-white px-6 py-4 border-b border-slate-200">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="text-center">
                <p class="text-sm text-slate-500">Total Cantidad</p>
                <p class="text-2xl font-bold text-slate-900">{{ almacen.total_cantidad }}</p>
              </div>
              <div class="text-center">
                <p class="text-sm text-slate-500">Valor Total</p>
                <p class="text-2xl font-bold text-emerald-600">${{ formatCurrency(almacen.valor_total) }}</p>
              </div>
              <div class="text-center">
                <p class="text-sm text-slate-500">Productos con Stock</p>
                <p class="text-2xl font-bold text-blue-600">{{ almacen.productos.filter(p => p.cantidad > 0).length }}</p>
              </div>
            </div>
          </div>

          <!-- Tabla de productos -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
              <thead class="bg-slate-50 dark:bg-slate-800/50">
                <tr>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Producto</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Código</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cantidad</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Stock Mínimo</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Precio Venta</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Valor Total</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                <tr v-for="producto in almacen.productos" :key="producto.producto" class="hover:bg-white">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-slate-900">{{ producto.producto }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-slate-500">{{ producto.codigo }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-slate-900">{{ producto.cantidad }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-slate-500">{{ producto.stock_minimo }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-slate-900">${{ formatCurrency(producto.precio_venta) }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-emerald-600">${{ formatCurrency(producto.valor_total) }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span
                      :class="producto.estado === 'bajo_stock' ? 'bg-rose-100 text-rose-800 dark:text-rose-200' : 'bg-emerald-100 text-emerald-800 dark:text-emerald-200'"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium"
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
      <div v-else class="bg-white rounded-xl border border-slate-200 shadow-sm p-12 text-center">
        <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-slate-900">No hay datos disponibles</h3>
        <p class="mt-1 text-sm text-slate-500">No se encontraron productos en los almacenes seleccionados.</p>
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

