<!-- /resources/js/Pages/MovimientosInventario/Index.vue -->
<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

// Notificaciones
const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false },
    { type: 'warning', background: '#f59e0b', icon: false }
  ]
})

// Props
const props = defineProps({
  movimientos: { type: [Object, Array], required: true },
  stats: { type: Object, default: () => ({}) },
  productos: { type: Array, default: () => [] },
  almacenes: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
})

// Estado
const searchTerm = ref(props.filters?.search ?? '')
const productoFilter = ref(props.filters?.producto_id ?? '')
const almacenFilter = ref(props.filters?.almacen_id ?? '')
const tipoFilter = ref(props.filters?.tipo ?? '')

// Computed
const movimientosData = computed(() => props.movimientos?.data || [])
const stats = computed(() => ({
  total: props.stats?.total_movimientos ?? 0,
  entradas: props.stats?.entradas ?? 0,
  salidas: props.stats?.salidas ?? 0,
  traspasos: props.stats?.traspasos ?? 0,
}))

// Métodos
function applyFilters() {
  router.get(route('movimientos-inventario.index'), {
    search: searchTerm.value,
    producto_id: productoFilter.value,
    almacen_id: almacenFilter.value,
    tipo: tipoFilter.value,
  }, { preserveState: true, preserveScroll: true })
}

function clearFilters() {
  searchTerm.value = ''
  productoFilter.value = ''
  almacenFilter.value = ''
  tipoFilter.value = ''
  applyFilters()
}

function getTipoBadge(tipo) {
  return tipo === 'entrada'
    ? { text: 'Entrada', class: 'bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300' }
    : { text: 'Salida', class: 'bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300' }
}
</script>

<template>
  <Head title="Movimientos de Inventario" />

  <div class="min-h-screen bg-gray-50 dark:bg-slate-950 dark:bg-gray-900">
    <div class="w-full px-6 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-gray-100">Movimientos de Inventario</h1>
            <p class="text-gray-600 dark:text-gray-300 dark:text-gray-400 mt-1">Historial completo de entradas, salidas y traspasos</p>
          </div>
        </div>
      </div>

      <!-- Estadísticas -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-300 dark:text-gray-300">Total Movimientos</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white dark:text-gray-100">{{ stats.total }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-300 dark:text-gray-300">Entradas</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white dark:text-gray-100">{{ stats.entradas }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-300 dark:text-gray-300">Salidas</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white dark:text-gray-100">{{ stats.salidas }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-8 w-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-300 dark:text-gray-300">Traspasos</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white dark:text-gray-100">{{ stats.traspasos }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-lg shadow mb-6 p-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Buscar</label>
            <input
              v-model="searchTerm"
              type="text"
              placeholder="Producto, almacén, motivo..."
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md focus:ring-blue-500 focus:border-blue-500"
              @keyup.enter="applyFilters"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Producto</label>
            <select
              v-model="productoFilter"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md focus:ring-blue-500 focus:border-blue-500"
              @change="applyFilters"
            >
              <option value="">Todos los productos</option>
              <option v-for="producto in productos" :key="producto.id" :value="producto.id">
                {{ producto.nombre }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Almacén</label>
            <select
              v-model="almacenFilter"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md focus:ring-blue-500 focus:border-blue-500"
              @change="applyFilters"
            >
              <option value="">Todos los almacenes</option>
              <option v-for="almacen in almacenes" :key="almacen.id" :value="almacen.id">
                {{ almacen.nombre }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo</label>
            <select
              v-model="tipoFilter"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md focus:ring-blue-500 focus:border-blue-500"
              @change="applyFilters"
            >
              <option value="">Todos los tipos</option>
              <option value="entrada">Entradas</option>
              <option value="salida">Salidas</option>
            </select>
          </div>

          <div class="flex items-end">
            <button
              @click="clearFilters"
              class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
            >
              Limpiar
            </button>
          </div>
        </div>
      </div>

      <!-- Tabla -->
      <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-slate-950 dark:bg-gray-700/50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">
                  Fecha
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">
                  Producto
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">
                  Almacén
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">
                  Tipo
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">
                  Cantidad
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">
                  Motivo
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">
                  Usuario
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-slate-900 dark:bg-gray-800 divide-y divide-gray-200 dark:divide-slate-800 dark:divide-gray-700">
              <tr v-for="movimiento in movimientosData" :key="movimiento.id" class="hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-700/50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white dark:text-gray-100">
                  {{ new Date(movimiento.created_at).toLocaleDateString('es-MX') }}
                  <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">
                    {{ new Date(movimiento.created_at).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' }) }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white dark:text-gray-100">
                  {{ movimiento.producto_nombre }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white dark:text-gray-100">
                  {{ movimiento.almacen_nombre }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="getTipoBadge(movimiento.tipo).class"
                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                  >
                    {{ getTipoBadge(movimiento.tipo).text }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white dark:text-gray-100">
                  <span :class="movimiento.tipo === 'entrada' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                    {{ movimiento.tipo === 'entrada' ? '+' : '-' }}{{ movimiento.cantidad }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white dark:text-gray-100 max-w-xs truncate" :title="movimiento.motivo">
                  {{ movimiento.motivo }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white dark:text-gray-100">
                  {{ movimiento.usuario_nombre || 'Sistema' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div v-if="props.movimientos?.last_page > 1" class="bg-white dark:bg-slate-900 dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-slate-800 dark:border-gray-700 sm:px-6">
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700 dark:text-gray-300">
              Mostrando {{ props.movimientos?.from }} a {{ props.movimientos?.to }} de {{ props.movimientos?.total }} resultados
            </div>
            <div class="flex space-x-1">
              <Link
                v-if="props.movimientos?.prev_page_url"
                :href="props.movimientos?.prev_page_url"
                class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-slate-900 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-600"
              >
                Anterior
              </Link>

              <Link
                v-if="props.movimientos?.next_page_url"
                :href="props.movimientos?.next_page_url"
                class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-slate-900 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-600"
              >
                Siguiente
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>




