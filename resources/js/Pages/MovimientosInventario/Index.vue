<!-- /resources/js/Pages/MovimientosInventario/Index.vue -->
<script setup>
import { useFormatters } from '@/Composables/useFormatters';
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
    ? { text: 'Entrada', class: 'bg-emerald-100 dark:bg-slate-800/50 text-emerald-800 dark:text-emerald-200 dark:text-emerald-300' }
    : { text: 'Salida', class: 'bg-rose-50 dark:bg-rose-900/20/40 text-rose-800 dark:text-rose-200 dark:text-rose-300' }
}
</script>

<template>
  <Head title="Movimientos de Inventario" />

  <div class="min-h-screen bg-[var(--ui-surface)]">
    <div class="w-full px-6 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">Movimientos de Inventario</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1">Historial completo de entradas, salidas y traspasos</p>
          </div>
        </div>
      </div>

      <!-- Estadísticas -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-slate-500 dark:text-slate-200">Total Movimientos</p>
              <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ stats.total }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-8 w-8 text-emerald-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-slate-500 dark:text-slate-200">Entradas</p>
              <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ stats.entradas }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-8 w-8 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-slate-500 dark:text-slate-200">Salidas</p>
              <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ stats.salidas }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-8 w-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-slate-500 dark:text-slate-200">Traspasos</p>
              <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ stats.traspasos }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl mb-6 p-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Buscar</label>
            <input
              v-model="searchTerm"
              type="text"
              placeholder="Producto, almacén, motivo..."
              class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 dark:bg-slate-700 dark:text-slate-200 rounded-xl focus:ring-brand-500 focus:border-brand-500"
              @keyup.enter="applyFilters"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Producto</label>
            <select
              v-model="productoFilter"
              class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 dark:bg-slate-700 dark:text-slate-200 rounded-xl focus:ring-brand-500 focus:border-brand-500"
              @change="applyFilters"
            >
              <option value="">Todos los productos</option>
              <option v-for="producto in productos" :key="producto.id" :value="producto.id">
                {{ producto.nombre }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Almacén</label>
            <select
              v-model="almacenFilter"
              class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 dark:bg-slate-700 dark:text-slate-200 rounded-xl focus:ring-brand-500 focus:border-brand-500"
              @change="applyFilters"
            >
              <option value="">Todos los almacenes</option>
              <option v-for="almacen in almacenes" :key="almacen.id" :value="almacen.id">
                {{ almacen.nombre }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Tipo</label>
            <select
              v-model="tipoFilter"
              class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 dark:bg-slate-700 dark:text-slate-200 rounded-xl focus:ring-brand-500 focus:border-brand-500"
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
              class="w-full px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors"
            >
              Limpiar
            </button>
          </div>
        </div>
      </div>

      <!-- Tabla -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-800/50">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                  Fecha
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                  Producto
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                  Almacén
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                  Tipo
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                  Cantidad
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                  Motivo
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                  Usuario
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
              <tr v-for="movimiento in movimientosData" :key="movimiento.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-slate-100">
                  {{ new Date(movimiento.created_at).toLocaleDateString('es-MX') }}
                  <div class="text-xs text-slate-500 dark:text-slate-400">
                    {{ new Date(movimiento.created_at).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' }) }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-slate-100">
                  {{ movimiento.producto_nombre }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-slate-100">
                  {{ movimiento.almacen_nombre }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="getTipoBadge(movimiento.tipo).class"
                    class="inline-flex px-2.5 py-0.5 text-xs font-medium rounded-full"
                  >
                    {{ getTipoBadge(movimiento.tipo).text }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-slate-100">
                  <span :class="movimiento.tipo === 'entrada' ? 'text-emerald-600 dark:text-slate-400' : 'text-rose-600 dark:text-rose-400'">
                    {{ movimiento.tipo === 'entrada' ? '+' : '-' }}{{ movimiento.cantidad }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-slate-900 dark:text-slate-100 max-w-xs truncate" :title="movimiento.motivo">
                  {{ movimiento.motivo }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-slate-100">
                  {{ movimiento.usuario_nombre || 'Sistema' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div v-if="props.movimientos?.last_page > 1" class="bg-white dark:bg-slate-800 px-6 py-4 border-t border-slate-300 dark:border-slate-600 sm:px-6">
          <div class="flex items-center justify-between">
            <div class="text-sm text-slate-700 dark:text-slate-200">
              Mostrando {{ props.movimientos?.from }} a {{ props.movimientos?.to }} de {{ props.movimientos?.total }} resultados
            </div>
            <div class="flex space-x-1">
              <Link
                v-if="props.movimientos?.prev_page_url"
                :href="props.movimientos?.prev_page_url"
                class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-700 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-600"
              >
                Anterior
              </Link>

              <Link
                v-if="props.movimientos?.next_page_url"
                :href="props.movimientos?.next_page_url"
                class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-700 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-600"
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




