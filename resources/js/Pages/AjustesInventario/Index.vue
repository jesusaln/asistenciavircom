<!-- /resources/js/Pages/AjustesInventario/Index.vue -->
<template>
  <Head title="Ajustes de Inventario" />

  <div class="min-h-screen bg-[var(--ui-surface)] p-6">
    <div class="w-full">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">Ajustes de Inventario</h1>
            <p class="text-slate-500 dark:text-slate-200 mt-1">Historial completo de ajustes manuales de stock</p>
          </div>
          <Link
            :href="route('ajustes-inventario.create')"
            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-brand-600 to-brand-700 text-white font-semibold rounded-xl hover:from-brand-700 hover:to-orange-800 transition-all duration-200 shadow-xl hover:shadow-xl"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Nuevo Ajuste
          </Link>
        </div>
      </div>

      <!-- Estadísticas -->
      <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm p-6">
          <div class="flex items-center">
            <div class="p-3 bg-blue-50 dark:bg-sky-900/20/40 rounded-xl">
              <svg class="w-10 h-10 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-slate-500 dark:text-slate-200">Total Ajustes</p>
              <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ stats.total }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm p-6">
          <div class="flex items-center">
            <div class="p-3 bg-emerald-100 dark:bg-slate-800/50 rounded-xl">
              <svg class="w-10 h-10 text-emerald-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-slate-500 dark:text-slate-200">Incrementos</p>
              <p class="text-2xl font-bold text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-slate-400">{{ stats.incrementos }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm p-6">
          <div class="flex items-center">
            <div class="p-3 bg-rose-50 dark:bg-rose-900/20/40 rounded-xl">
              <svg class="w-10 h-10 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-slate-500 dark:text-slate-200">Decrementos</p>
              <p class="text-2xl font-bold text-rose-800 dark:text-rose-200 dark:text-rose-200 dark:text-rose-400">{{ stats.decrementos }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm p-6">
          <div class="flex items-center">
            <div class="p-3 bg-purple-100 dark:bg-purple-900/40 rounded-xl">
              <svg class="w-10 h-10 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-slate-500 dark:text-slate-200">Productos Ajustados</p>
              <p class="text-2xl font-bold text-purple-700 dark:text-purple-400">{{ stats.productos_ajustados }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm p-6">
          <div class="flex items-center">
            <div class="p-3 bg-brand-100 dark:bg-brand-900/40 rounded-xl">
              <svg class="w-10 h-10 text-brand-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-slate-500 dark:text-slate-200">Almacenes Afectados</p>
              <p class="text-2xl font-bold text-brand-700 dark:text-orange-400">{{ stats.almacenes_afectados }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Búsqueda -->
          <div>
            <label for="search" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Buscar</label>
            <input
              id="search"
              v-model="filters.search"
              type="text"
              placeholder="ID, producto, almacén..."
              class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200"
              @input="handleSearch"
            />
          </div>

          <!-- Producto -->
          <div>
            <label for="producto_id" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Producto</label>
            <select
              id="producto_id"
              v-model="filters.producto_id"
              class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200"
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
            <label for="almacen_id" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Almacén</label>
            <select
              id="almacen_id"
              v-model="filters.almacen_id"
              class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200"
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
            <label for="tipo" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Tipo de Ajuste</label>
            <select
              id="tipo"
              v-model="filters.tipo"
              class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200"
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
      <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-800/50">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 dark:text-slate-200 uppercase tracking-wider">ID</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 dark:text-slate-200 uppercase tracking-wider">Producto</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 dark:text-slate-200 uppercase tracking-wider">Almacén</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 dark:text-slate-200 uppercase tracking-wider">Tipo</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 dark:text-slate-200 uppercase tracking-wider">Cantidades</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 dark:text-slate-200 uppercase tracking-wider">Motivo</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 dark:text-slate-200 uppercase tracking-wider">Usuario</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 dark:text-slate-200 uppercase tracking-wider">Fecha</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
              <tr v-for="ajuste in ajustes.data" :key="ajuste.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-slate-100">
                  #{{ ajuste.id }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-slate-900 dark:text-slate-100">
                    {{ ajuste.producto?.nombre || 'Producto no encontrado' }}
                  </div>
                  <div class="text-sm text-slate-500 dark:text-slate-400">
                    {{ ajuste.producto?.codigo || '' }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-slate-900 dark:text-slate-100">
                    {{ ajuste.almacen?.nombre || 'Almacén no encontrado' }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="ajuste.tipo === 'incremento' ? 'bg-emerald-100 dark:bg-slate-800/50 text-emerald-800 dark:text-emerald-200 dark:text-emerald-300' : 'bg-rose-50 dark:bg-rose-900/20/40 text-rose-800 dark:text-rose-200 dark:text-rose-300'"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium"
                  >
                    {{ ajuste.tipo === 'incremento' ? 'Incremento' : 'Decremento' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-slate-100">
                  <div class="space-y-1">
                    <div class="flex items-center gap-2">
                      <span class="text-slate-500 dark:text-slate-400">Antes:</span>
                      <span class="font-medium">{{ ajuste.cantidad_anterior }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <span :class="ajuste.tipo === 'incremento' ? 'text-emerald-600 dark:text-slate-400' : 'text-rose-600 dark:text-rose-400'">
                        {{ ajuste.tipo === 'incremento' ? '+' : '-' }}{{ ajuste.cantidad_ajuste }}
                      </span>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="text-slate-500 dark:text-slate-400">Después:</span>
                      <span class="font-medium text-blue-600 dark:text-blue-400">{{ ajuste.cantidad_nueva }}</span>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-slate-900 dark:text-slate-100 max-w-xs truncate" :title="ajuste.motivo">
                    {{ ajuste.motivo }}
                  </div>
                  <div v-if="ajuste.observaciones" class="text-xs text-slate-500 dark:text-slate-400 max-w-xs truncate" :title="ajuste.observaciones">
                    {{ ajuste.observaciones }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-slate-100">
                  {{ ajuste.usuario?.name || 'Usuario no encontrado' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                  {{ formatDate(ajuste.created_at) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div class="bg-white dark:bg-slate-800 px-6 py-4 border-t border-slate-200 dark:border-slate-700 sm:px-6">
          <div class="flex items-center justify-between">
            <div class="text-sm text-slate-700 dark:text-slate-200">
              Mostrando {{ pagination.from }} a {{ pagination.to }} de {{ pagination.total }} resultados
            </div>
            <div class="flex space-x-1">
              <Link
                v-if="ajustes.prev_page_url"
                :href="ajustes.prev_page_url"
                class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700"
              >
                Anterior
              </Link>
              <Link
                v-if="ajustes.next_page_url"
                :href="ajustes.next_page_url"
                class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700"
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
import { useFormatters } from '@/Composables/useFormatters';
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




