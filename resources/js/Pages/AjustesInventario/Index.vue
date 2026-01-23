<!-- /resources/js/Pages/AjustesInventario/Index.vue -->
<template>
  <Head title="Ajustes de Inventario" />

  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 dark:from-gray-900 dark:to-gray-800 p-6">
    <div class="w-full">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-gray-100">Ajustes de Inventario</h1>
            <p class="text-gray-600 dark:text-gray-300 dark:text-gray-300 mt-1">Historial completo de ajustes manuales de stock</p>
          </div>
          <Link
            :href="route('ajustes-inventario.create')"
            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-orange-600 to-orange-700 text-white font-semibold rounded-lg hover:from-orange-700 hover:to-orange-800 transition-all duration-200 shadow-lg hover:shadow-xl"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Nuevo Ajuste
          </Link>
        </div>
      </div>

      <!-- Estadísticas -->
      <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
        <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-slate-800 dark:border-gray-700 shadow-sm p-6">
          <div class="flex items-center">
            <div class="p-3 bg-blue-100 dark:bg-blue-900/40 rounded-lg">
              <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-300 dark:text-gray-300">Total Ajustes</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white dark:text-gray-100">{{ stats.total }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-slate-800 dark:border-gray-700 shadow-sm p-6">
          <div class="flex items-center">
            <div class="p-3 bg-green-100 dark:bg-green-900/40 rounded-lg">
              <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-300 dark:text-gray-300">Incrementos</p>
              <p class="text-2xl font-bold text-green-700 dark:text-green-400">{{ stats.incrementos }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-slate-800 dark:border-gray-700 shadow-sm p-6">
          <div class="flex items-center">
            <div class="p-3 bg-red-100 dark:bg-red-900/40 rounded-lg">
              <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-300 dark:text-gray-300">Decrementos</p>
              <p class="text-2xl font-bold text-red-700 dark:text-red-400">{{ stats.decrementos }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-slate-800 dark:border-gray-700 shadow-sm p-6">
          <div class="flex items-center">
            <div class="p-3 bg-purple-100 dark:bg-purple-900/40 rounded-lg">
              <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-300 dark:text-gray-300">Productos Ajustados</p>
              <p class="text-2xl font-bold text-purple-700 dark:text-purple-400">{{ stats.productos_ajustados }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-slate-800 dark:border-gray-700 shadow-sm p-6">
          <div class="flex items-center">
            <div class="p-3 bg-orange-100 dark:bg-orange-900/40 rounded-lg">
              <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-300 dark:text-gray-300">Almacenes Afectados</p>
              <p class="text-2xl font-bold text-orange-700 dark:text-orange-400">{{ stats.almacenes_afectados }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-slate-800 dark:border-gray-700 shadow-sm p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Búsqueda -->
          <div>
            <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Buscar</label>
            <input
              id="search"
              v-model="filters.search"
              type="text"
              placeholder="ID, producto, almacén..."
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
              @input="handleSearch"
            />
          </div>

          <!-- Producto -->
          <div>
            <label for="producto_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Producto</label>
            <select
              id="producto_id"
              v-model="filters.producto_id"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
              @change="handleFilter"
            >
              <option value="">Todos los productos</option>
              <option v-for="producto in productos" :key="producto.id" :value="producto.id">
                {{ producto.nombre }}
              </option>
            </select>
          </div>

          <!-- Almacén -->
          <div>
            <label for="almacen_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Almacén</label>
            <select
              id="almacen_id"
              v-model="filters.almacen_id"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
              @change="handleFilter"
            >
              <option value="">Todos los almacenes</option>
              <option v-for="almacen in almacenes" :key="almacen.id" :value="almacen.id">
                {{ almacen.nombre }}
              </option>
            </select>
          </div>

          <!-- Tipo -->
          <div>
            <label for="tipo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tipo de Ajuste</label>
            <select
              id="tipo"
              v-model="filters.tipo"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
              @change="handleFilter"
            >
              <option value="">Todos los tipos</option>
              <option value="incremento">Incremento</option>
              <option value="decremento">Decremento</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Tabla -->
      <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-slate-800 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800 dark:divide-gray-700">
            <thead class="bg-white dark:bg-slate-900 dark:bg-gray-700/50">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">ID</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">Producto</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">Almacén</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">Tipo</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">Cantidades</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">Motivo</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">Usuario</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">Fecha</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-slate-900 dark:bg-gray-800 divide-y divide-gray-200 dark:divide-slate-800 dark:divide-gray-700">
              <tr v-for="ajuste in ajustes.data" :key="ajuste.id" class="hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-700/50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white dark:text-gray-100">
                  #{{ ajuste.id }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900 dark:text-white dark:text-gray-100">
                    {{ ajuste.producto?.nombre || 'Producto no encontrado' }}
                  </div>
                  <div class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                    {{ ajuste.producto?.codigo || '' }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900 dark:text-white dark:text-gray-100">
                    {{ ajuste.almacen?.nombre || 'Almacén no encontrado' }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="ajuste.tipo === 'incremento' ? 'bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300'"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    {{ ajuste.tipo === 'incremento' ? 'Incremento' : 'Decremento' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white dark:text-gray-100">
                  <div class="space-y-1">
                    <div class="flex items-center gap-2">
                      <span class="text-gray-500 dark:text-gray-400 dark:text-gray-400">Antes:</span>
                      <span class="font-medium">{{ ajuste.cantidad_anterior }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <span :class="ajuste.tipo === 'incremento' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                        {{ ajuste.tipo === 'incremento' ? '+' : '-' }}{{ ajuste.cantidad_ajuste }}
                      </span>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="text-gray-500 dark:text-gray-400 dark:text-gray-400">Después:</span>
                      <span class="font-medium text-blue-600 dark:text-blue-400">{{ ajuste.cantidad_nueva }}</span>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-900 dark:text-white dark:text-gray-100 max-w-xs truncate" :title="ajuste.motivo">
                    {{ ajuste.motivo }}
                  </div>
                  <div v-if="ajuste.observaciones" class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 max-w-xs truncate" :title="ajuste.observaciones">
                    {{ ajuste.observaciones }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white dark:text-gray-100">
                  {{ ajuste.usuario?.name || 'Usuario no encontrado' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                  {{ formatDate(ajuste.created_at) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-slate-800 dark:border-gray-700 sm:px-6">
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700 dark:text-gray-300">
              Mostrando {{ pagination.from }} a {{ pagination.to }} de {{ pagination.total }} resultados
            </div>
            <div class="flex space-x-1">
              <Link
                v-if="ajustes.prev_page_url"
                :href="ajustes.prev_page_url"
                class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-slate-900 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-700"
              >
                Anterior
              </Link>
              <Link
                v-if="ajustes.next_page_url"
                :href="ajustes.next_page_url"
                class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-slate-900 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-700"
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

<script setup>
import { ref } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import AppLayout from '@/Layouts/AppLayout.vue'

// Layout
defineOptions({ layout: AppLayout });

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

const page = usePage()

// Mostrar notificaciones si existen
const flash = page.props.flash
if (flash?.success) notyf.success(flash.success)
if (flash?.error) notyf.error(flash.error)

// Props
const props = defineProps({
  ajustes: { type: Object, required: true },
  stats: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  sorting: { type: Object, default: () => ({}) },
  pagination: { type: Object, default: () => ({}) },
  productos: { type: Array, default: () => [] },
  almacenes: { type: Array, default: () => [] },
})

// Estado reactivo
const filters = ref({ ...props.filters })

// Funciones
const handleSearch = () => {
  router.get(route('ajustes-inventario.index'), {
    search: filters.value.search,
    producto_id: filters.value.producto_id,
    almacen_id: filters.value.almacen_id,
    tipo: filters.value.tipo,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const handleFilter = () => {
  router.get(route('ajustes-inventario.index'), {
    search: filters.value.search,
    producto_id: filters.value.producto_id,
    almacen_id: filters.value.almacen_id,
    tipo: filters.value.tipo,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>




