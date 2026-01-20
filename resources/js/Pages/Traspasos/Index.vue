<!-- /resources/js/Pages/Traspasos/Index.vue -->
<template>
  <Head title="Traspasos de Inventario" />

  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 p-6">
    <div class="w-full">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Traspasos de Inventario</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Historial completo de movimientos entre almacenes</p>
          </div>
          <Link
            :href="route('traspasos.create')"
            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all duration-200 shadow-lg hover:shadow-xl"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Nuevo Traspaso
          </Link>
        </div>
      </div>

      <!-- Estadísticas -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
          <div class="flex items-center">
            <div class="p-3 bg-blue-100 dark:bg-blue-900/40 rounded-lg">
              <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Total Traspasos</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.total }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
          <div class="flex items-center">
            <div class="p-3 bg-green-100 dark:bg-green-900/40 rounded-lg">
              <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Productos Trasladados</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.productos_trasladados }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
          <div class="flex items-center">
            <div class="p-3 bg-orange-100 dark:bg-orange-900/40 rounded-lg">
              <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Almacenes Origen</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.almacenes_origen }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
          <div class="flex items-center">
            <div class="p-3 bg-purple-100 dark:bg-purple-900/40 rounded-lg">
              <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Almacenes Destino</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.almacenes_destino }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Búsqueda -->
          <div>
            <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Buscar</label>
            <input
              id="search"
              v-model="filters.search"
              type="text"
              placeholder="ID, producto, almacén..."
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              @input="handleSearch"
            />
          </div>

          <!-- Producto -->
          <div>
            <label for="producto_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Producto</label>
            <select
              id="producto_id"
              v-model="filters.producto_id"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              @change="handleFilter"
            >
              <option value="">Todos los productos</option>
              <option v-for="producto in productos" :key="producto.id" :value="producto.id">
                {{ producto.nombre }}
              </option>
            </select>
          </div>

          <!-- Almacén Origen -->
          <div>
            <label for="almacen_origen_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Almacén Origen</label>
            <select
              id="almacen_origen_id"
              v-model="filters.almacen_origen_id"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              @change="handleFilter"
            >
              <option value="">Todos los orígenes</option>
              <option v-for="almacen in almacenes" :key="almacen.id" :value="almacen.id">
                {{ almacen.nombre }}
              </option>
            </select>
          </div>

          <!-- Almacén Destino -->
          <div>
            <label for="almacen_destino_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Almacén Destino</label>
            <select
              id="almacen_destino_id"
              v-model="filters.almacen_destino_id"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              @change="handleFilter"
            >
              <option value="">Todos los destinos</option>
              <option v-for="almacen in almacenes" :key="almacen.id" :value="almacen.id">
                {{ almacen.nombre }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Tabla -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Productos</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Origen → Destino</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="traspaso in traspasos.data" :key="traspaso.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                  #{{ traspaso.id }}
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10">
                      <div class="h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                        <span v-if="traspaso.productos_count > 1" class="text-xs font-bold text-gray-600 dark:text-gray-300">{{ traspaso.productos_count }}</span>
                        <svg v-else class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                      </div>
                    </div>
                    <div class="ml-4">
                      <div v-if="traspaso.productos && traspaso.productos.length > 1" class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ traspaso.productos.length }} productos
                        <span class="text-xs text-gray-500 dark:text-gray-400 block">
                          {{ traspaso.productos.slice(0, 2).map(p => p.nombre).join(', ') }}
                          <span v-if="traspaso.productos.length > 2">...</span>
                        </span>
                      </div>
                      <div v-else class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ traspaso.producto?.nombre || traspaso.productos?.[0]?.nombre || 'Producto no encontrado' }}
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900 dark:text-gray-100">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300">
                      {{ traspaso.almacen_origen?.nombre || 'N/A' }}
                    </span>
                    <svg class="inline w-4 h-4 mx-2 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300">
                      {{ traspaso.almacen_destino?.nombre || 'N/A' }}
                    </span>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300">
                    {{ traspaso.cantidad_total || traspaso.cantidad || 0 }} unidades
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                  {{ formatDate(traspaso.created_at) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                  <div class="flex items-center gap-2">
                    <button type="button" @click="verTraspaso(traspaso)" class="p-2 rounded-full text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-gray-700" title="Ver">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                    <Link :href="route('traspasos.edit', traspaso.id)" class="p-2 rounded-full text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-gray-700" title="Editar">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </Link>
                    <button @click="confirmarEliminar(traspaso)" class="p-2 rounded-full text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-gray-700" title="Eliminar">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700 dark:text-gray-300">
              Mostrando {{ pagination.from }} a {{ pagination.to }} de {{ pagination.total }} resultados
            </div>
            <div class="flex space-x-1">
              <Link
                v-if="traspasos.prev_page_url"
                :href="traspasos.prev_page_url"
                class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600"
              >
                Anterior
              </Link>
              <Link
                v-if="traspasos.next_page_url"
                :href="traspasos.next_page_url"
                class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600"
              >
                Siguiente
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Ver Traspaso -->
  <div v-if="mostrarModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" @click.self="cerrarModal">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-2xl w-full p-6 space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Traspaso #{{ traspasoSeleccionado?.id }}</h2>
          <p class="text-sm text-gray-600 dark:text-gray-300">Fecha: {{ formatDate(traspasoSeleccionado?.created_at) }}</p>
        </div>
        <button @click="cerrarModal" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div class="p-3 bg-white dark:bg-gray-700 rounded-lg">
          <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Origen</p>
          <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ traspasoSeleccionado?.almacen_origen?.nombre || 'N/D' }}</p>
        </div>
        <div class="p-3 bg-white dark:bg-gray-700 rounded-lg">
          <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Destino</p>
          <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ traspasoSeleccionado?.almacen_destino?.nombre || 'N/D' }}</p>
        </div>
        <div class="p-3 bg-white dark:bg-gray-700 rounded-lg">
          <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Referencia</p>
          <p class="text-sm text-gray-800 dark:text-gray-200">{{ traspasoSeleccionado?.referencia || 'Sin referencia' }}</p>
        </div>
        <div class="p-3 bg-white dark:bg-gray-700 rounded-lg">
          <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Costo transporte</p>
          <p class="text-sm text-gray-800 dark:text-gray-200">{{ traspasoSeleccionado?.costo_transporte ?? 0 }}</p>
        </div>
      </div>

      <!-- Lista de Productos -->
      <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
        <div class="bg-gray-100 dark:bg-gray-700/50 px-4 py-2 border-b border-gray-200 dark:border-gray-700">
          <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Productos Traspasados ({{ traspasoSeleccionado?.productos?.length || 1 }})</p>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
          <div v-if="traspasoSeleccionado?.productos && traspasoSeleccionado.productos.length" v-for="(prod, idx) in traspasoSeleccionado.productos" :key="idx" class="px-4 py-3 flex justify-between items-center">
            <span class="text-sm text-gray-900 dark:text-gray-100">{{ prod.nombre }}</span>
            <span class="text-sm font-semibold text-blue-600 dark:text-blue-400">{{ prod.cantidad }} unid.</span>
          </div>
          <div v-else class="px-4 py-3 flex justify-between items-center">
            <span class="text-sm text-gray-900 dark:text-gray-100">{{ traspasoSeleccionado?.producto?.nombre || 'N/D' }}</span>
            <span class="text-sm font-semibold text-blue-600 dark:text-blue-400">{{ traspasoSeleccionado?.cantidad || traspasoSeleccionado?.cantidad_total }} unid.</span>
          </div>
        </div>
      </div>

      <div class="p-3 bg-white dark:bg-gray-700 rounded-lg">
        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Observaciones</p>
        <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ traspasoSeleccionado?.observaciones || 'Sin observaciones' }}</p>
      </div>

      <div class="flex justify-end">
        <button @click="cerrarModal" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Cerrar</button>
      </div>
    </div>
  </div>

  <!-- Modal Eliminar -->
  <div v-if="mostrarModalEliminar" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" @click.self="cerrarModalEliminar">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-lg w-full p-6 space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Eliminar traspaso</h2>
        <button @click="cerrarModalEliminar" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
      <p class="text-sm text-gray-700 dark:text-gray-200">
        ¿Deseas eliminar el traspaso #{{ traspasoAEliminar?.id }} y revertir el stock al almacén origen?
      </p>
      <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800/50 rounded-lg p-3 text-sm text-orange-700 dark:text-orange-200">
        Esta acción regresará la cantidad al almacén origen y descontará del destino.
      </div>
      <div class="flex justify-end gap-3">
        <button @click="cerrarModalEliminar" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600">Cancelar</button>
        <button @click="eliminarTraspaso" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">Eliminar</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import AppLayout from '@/Layouts/AppLayout.vue';

// Usar layout
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
onMounted(() => {
  const flash = page.props.flash
  if (flash?.success) notyf.success(flash.success)
  if (flash?.error) notyf.error(flash.error)
})

// Props
const props = defineProps({
  traspasos: { type: Object, required: true },
  stats: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  sorting: { type: Object, default: () => ({}) },
  pagination: { type: Object, default: () => ({}) },
  productos: { type: Array, default: () => [] },
  almacenes: { type: Array, default: () => [] },
})

// Estado reactivo
const filters = ref({ ...props.filters })
const mostrarModal = ref(false)
const traspasoSeleccionado = ref(null)
const mostrarModalEliminar = ref(false)
const traspasoAEliminar = ref(null)

// Funciones
const handleSearch = () => {
  router.get(route('traspasos.index'), {
    search: filters.value.search,
    producto_id: filters.value.producto_id,
    almacen_origen_id: filters.value.almacen_origen_id,
    almacen_destino_id: filters.value.almacen_destino_id,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const handleFilter = () => {
  router.get(route('traspasos.index'), {
    search: filters.value.search,
    producto_id: filters.value.producto_id,
    almacen_origen_id: filters.value.almacen_origen_id,
    almacen_destino_id: filters.value.almacen_destino_id,
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

const verTraspaso = (traspaso) => {
  traspasoSeleccionado.value = traspaso
  mostrarModal.value = true
}

const cerrarModal = () => {
  mostrarModal.value = false
  traspasoSeleccionado.value = null
}

const confirmarEliminar = (traspaso) => {
  traspasoAEliminar.value = traspaso
  mostrarModalEliminar.value = true
}

const cerrarModalEliminar = () => {
  mostrarModalEliminar.value = false
  traspasoAEliminar.value = null
}

const eliminarTraspaso = () => {
  if (!traspasoAEliminar.value) return
  router.delete(route('traspasos.destroy', traspasoAEliminar.value.id), {
    preserveScroll: true,
    onSuccess: (page) => {
      cerrarModalEliminar()
      const flash = page?.props?.flash
      if (flash?.error) {
        notyf.error(flash.error)
      } else {
        notyf.success('Traspaso eliminado y stock revertido')
      }
    },
    onError: (errors) => {
      console.error(errors)
      notyf.error('No se pudo eliminar el traspaso')
    }
  })
}
</script>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>




