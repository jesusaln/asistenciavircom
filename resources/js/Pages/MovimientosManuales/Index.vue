<!-- /resources/js/Pages/MovimientosManuales/Index.vue -->
<template>
  <Head title="Movimientos Manuales" />

  <div class="min-h-screen bg-[var(--ui-surface)] p-6">
    <div class="w-full">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-slate-900">Movimientos Manuales</h1>
            <p class="text-slate-500 mt-1">Entradas y salidas manuales de inventario</p>
          </div>
          <Link
            :href="route('movimientos-manuales.create')"
            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-emerald-600 to-emerald-700 text-white font-semibold rounded-xl hover:from-emerald-700 hover:to-emerald-800 transition-all duration-200 shadow-xl hover:shadow-xl"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Nuevo Movimiento
          </Link>
        </div>
      </div>

      <!-- Estadísticas -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
          <div class="flex items-center">
            <div class="p-3 bg-sky-100 rounded-xl">
              <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-slate-500">Total Movimientos</p>
              <p class="text-2xl font-bold text-slate-900">{{ stats.total }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
          <div class="flex items-center">
            <div class="p-3 bg-emerald-100 rounded-xl">
              <svg class="w-10 h-10 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-slate-500">Entradas</p>
              <p class="text-2xl font-bold text-emerald-800 dark:text-emerald-200 dark:text-emerald-200">{{ stats.entradas }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
          <div class="flex items-center">
            <div class="p-3 bg-rose-100 rounded-xl">
              <svg class="w-10 h-10 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16V4m0 0L21 8m-4-4l-4 4m-6 0v12m0 0l-4-4m4 4l4-4"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-slate-500">Salidas</p>
              <p class="text-2xl font-bold text-rose-800 dark:text-rose-200 dark:text-rose-200">{{ stats.salidas }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
          <div class="flex items-center">
            <div class="p-3 bg-purple-100 rounded-xl">
              <svg class="w-10 h-10 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-slate-500">Productos Afectados</p>
              <p class="text-2xl font-bold text-purple-700">{{ stats.productos_afectados }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label for="search" class="block text-sm font-medium text-slate-700 mb-2">Buscar</label>
            <input
              id="search"
              v-model="filters.search"
              type="text"
              placeholder="Producto, almacén, motivo..."
              class="w-full px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent"
            />
          </div>

          <div>
            <label for="tipo" class="block text-sm font-medium text-slate-700 mb-2">Tipo</label>
            <select
              id="tipo"
              v-model="filters.tipo"
              class="w-full px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent"
            >
              <option value="">Todos los tipos</option>
              <option value="entrada">Entradas</option>
              <option value="salida">Salidas</option>
            </select>
          </div>

          <div>
            <label for="categoria" class="block text-sm font-medium text-slate-700 mb-2">Categoría</label>
            <select
              id="categoria"
              v-model="filters.categoria"
              class="w-full px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent"
            >
              <option value="">Todas las categorías</option>
              <option value="recepcion">Recepción</option>
              <option value="donacion">Donación</option>
              <option value="merma">Merma</option>
              <option value="consumo">Consumo interno</option>
              <option value="devolucion">Devolución</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Tabla -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-800/50">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">ID</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Producto</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Almacén</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Tipo</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Cantidad</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Motivo</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Usuario</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Fecha</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
              <tr v-for="movimiento in movimientos.data" :key="movimiento.id" class="hover:bg-white">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">
                  #{{ movimiento.id }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-slate-900">
                    {{ movimiento.producto?.nombre || 'Producto no encontrado' }}
                  </div>
                  <div class="text-sm text-slate-500">
                    {{ movimiento.producto?.codigo || '' }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-slate-900">
                    {{ movimiento.almacen?.nombre || 'Almacén no encontrado' }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="movimiento.tipo === 'entrada' ? 'bg-emerald-100 text-emerald-800 dark:text-emerald-200' : 'bg-rose-100 text-rose-800 dark:text-rose-200'"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium"
                  >
                    {{ movimiento.tipo === 'entrada' ? 'Entrada' : 'Salida' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                  {{ movimiento.cantidad }}
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-slate-900 max-w-xs truncate" :title="movimiento.motivo">
                    {{ movimiento.motivo || 'Sin motivo' }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                  {{ movimiento.usuario?.name || 'Usuario no encontrado' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                  {{ formatDate(movimiento.created_at) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div class="bg-white px-6 py-4 border-t border-slate-200 sm:px-6">
          <div class="text-sm text-slate-700 text-center py-4">
            Paginación próximamente...
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
defineOptions({
  layout: AppLayout
});


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
  movimientos: { type: Object, required: true },
  stats: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
})

// Estado reactivo
const filters = ref({ ...props.filters })

// Funciones
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




