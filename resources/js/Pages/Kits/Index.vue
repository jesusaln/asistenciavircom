<template>
  <AppLayout title="Kits">
    <div class="kits-index">
      <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
          <div class="flex justify-between items-center">
            <div>
              <h1 class="text-3xl font-bold text-gray-900">Kits de Productos</h1>
              <p class="mt-2 text-sm text-gray-600">Gestiona los kits compuestos por múltiples productos</p>
            </div>
            <Link href="/kits/create" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-amber-500 hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              Nuevo Kit
            </Link>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Kits</dt>
                    <dd class="text-lg font-semibold text-gray-900">{{ stats.totalKits }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Kits Activos</dt>
                    <dd class="text-lg font-semibold text-gray-900">{{ stats.kitsActivos }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Valor Total</dt>
                    <dd class="text-lg font-semibold text-gray-900">{{ formatCurrency(stats.valorTotal) }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Search and Filters -->
        <div class="bg-white shadow rounded-lg mb-6">
          <div class="p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-3 md:space-y-0">
              <div class="flex-1 max-w-lg">
                <label for="search" class="sr-only">Buscar kits</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                  </div>
                  <input v-model="searchQuery" @input="debouncedSearch" type="text" id="search"
                         class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 sm:text-sm"
                         placeholder="Buscar por nombre, código o descripción...">
                </div>
              </div>
              <button v-if="searchQuery" @click="clearSearch"
                      class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                Limpiar
              </button>
            </div>
          </div>
        </div>

        <!-- Kits Table -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Código
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Nombre
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Componentes
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Precio
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Estado
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Fecha
                  </th>
                  <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Acciones
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-if="loading">
                  <td colspan="7" class="px-6 py-12 text-center">
                    <div class="flex justify-center">
                      <svg class="animate-spin h-8 w-8 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                    </div>
                  </td>
                </tr>
                <tr v-else-if="kits.length === 0">
                  <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">
                    No se encontraron kits
                  </td>
                </tr>
                <tr v-else v-for="kit in kits" :key="kit.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {{ kit.codigo }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">{{ kit.nombre }}</div>
                    <div v-if="kit.descripcion" class="text-sm text-gray-500 truncate max-w-xs">{{ kit.descripcion }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                      {{ kit.componentes_count }} productos
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ formatCurrency(kit.precio_venta) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="[
                      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                      kit.estado === 'activo' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                    ]">
                      {{ kit.estado === 'activo' ? 'Activo' : 'Inactivo' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ kit.created_at }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex justify-end space-x-2">
                      <button @click="viewKitDetails(kit.id)" class="text-amber-600 hover:text-indigo-900" title="Ver detalles">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                      </button>
                      <Link :href="`/kits/${kit.id}/edit`" class="text-yellow-600 hover:text-yellow-900" title="Editar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                      </Link>
                      <button @click="deleteKit(kit.id)" class="text-red-600 hover:text-red-900" title="Eliminar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="pagination.last_page > 1" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
            <div class="flex-1 flex justify-between sm:hidden">
              <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
                      class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                Anterior
              </button>
              <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page"
                      class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                Siguiente
              </button>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
              <div>
                <p class="text-sm text-gray-700">
                  Mostrando
                  <span class="font-medium">{{ pagination.from }}</span>
                  a
                  <span class="font-medium">{{ pagination.to }}</span>
                  de
                  <span class="font-medium">{{ pagination.total }}</span>
                  resultados
                </p>
              </div>
              <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                  <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
                          class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span class="sr-only">Anterior</span>
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                  </button>
                  <button v-for="page in visiblePages" :key="page" @click="changePage(page)"
                          :class="[
                            'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                            page === pagination.current_page
                              ? 'z-10 bg-indigo-50 border-amber-500 text-amber-600'
                              : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                          ]">
                    {{ page }}
                  </button>
                  <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page"
                          class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span class="sr-only">Siguiente</span>
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                  </button>
                </nav>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal de Detalles del Kit -->
      <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
          <!-- Background overlay -->
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal"></div>

          <!-- Modal panel -->
          <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
              <div class="flex justify-between items-start mb-4">
                <h3 class="text-2xl font-bold text-gray-900" id="modal-title">Detalles del Kit</h3>
                <button @click="closeModal" class="text-gray-400 hover:text-gray-500">
                  <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                  </svg>
                </button>
              </div>

              <div v-if="loadingDetails" class="flex justify-center py-12">
                <svg class="animate-spin h-8 w-8 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
              </div>

              <div v-else-if="selectedKit" class="space-y-6">
                <!-- Información Básica -->
                <div class="bg-gray-50 rounded-lg p-4">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-500">Código</label>
                      <p class="mt-1 text-lg font-semibold text-gray-900">{{ selectedKit.codigo }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-500">Nombre</label>
                      <p class="mt-1 text-lg font-semibold text-gray-900">{{ selectedKit.nombre }}</p>
                    </div>
                    <div class="md:col-span-2" v-if="selectedKit.descripcion">
                      <label class="block text-sm font-medium text-gray-500">Descripción</label>
                      <p class="mt-1 text-gray-900">{{ selectedKit.descripcion }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-500">Precio de Venta</label>
                      <p class="mt-1 text-lg font-semibold text-green-600">{{ formatCurrency(selectedKit.precio_venta) }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-500">Costo Estimado</label>
                      <p class="mt-1 text-lg font-semibold text-blue-600">{{ formatCurrency(selectedKit.costo_estimado) }}</p>
                    </div>
                  </div>
                </div>

                <!-- Componentes del Kit -->
                <div>
                  <h4 class="text-lg font-medium text-gray-900 mb-3">Componentes ({{ selectedKit.kit_items?.length || 0 }})</h4>
                  <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                      <thead class="bg-gray-50">
                        <tr>
                          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                          <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                          <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Precio Unit.</th>
                          <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                        </tr>
                      </thead>
                      <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="item in selectedKit.kit_items" :key="item.id">
                          <td class="px-4 py-3 text-sm text-gray-900">{{ item.producto?.codigo || 'N/A' }}</td>
                          <td class="px-4 py-3 text-sm text-gray-900">{{ item.producto?.nombre || 'Producto sin nombre' }}</td>
                          <td class="px-4 py-3 text-sm text-center text-gray-900">{{ item.cantidad }}</td>
                          <td class="px-4 py-3 text-sm text-right text-gray-900">{{ formatCurrency(item.precio_unitario) }}</td>
                          <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">{{ formatCurrency(item.precio_unitario * item.cantidad) }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- Margen de Ganancia -->
                <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                  <div class="flex justify-between items-center">
                    <div>
                      <h4 class="text-sm font-medium text-blue-900">Margen de Ganancia</h4>
                      <p class="text-xs text-blue-700">Basado en precio de venta sin IVA vs costo estimado</p>
                    </div>
                    <div class="text-right">
                      <span class="text-2xl font-bold text-blue-900">{{ calculateMargin(selectedKit) }}%</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
              <Link :href="`/kits/${selectedKit?.id}/edit`" v-if="selectedKit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-amber-500 text-base font-medium text-white hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 sm:ml-3 sm:w-auto sm:text-sm">
                Editar Kit
              </Link>
              <button @click="closeModal" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 sm:mt-0 sm:w-auto sm:text-sm">
                Cerrar
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false }
  ]
})

// State
const kits = ref([])
const loading = ref(false)
const searchQuery = ref('')
const pagination = ref({
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0
})
const stats = ref({
  totalKits: 0,
  kitsActivos: 0,
  valorTotal: 0
})
const showModal = ref(false)
const selectedKit = ref(null)
const loadingDetails = ref(false)

// Computed
const visiblePages = computed(() => {
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const delta = 2
  const range = []
  const rangeWithDots = []

  for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
    range.push(i)
  }

  if (current - delta > 2) {
    rangeWithDots.push(1, '...')
  } else {
    rangeWithDots.push(1)
  }

  rangeWithDots.push(...range)

  if (current + delta < last - 1) {
    rangeWithDots.push('...', last)
  } else if (last > 1) {
    rangeWithDots.push(last)
  }

  return rangeWithDots.filter(p => p !== '...' || rangeWithDots.indexOf(p) === rangeWithDots.lastIndexOf(p))
})

// Methods
const fetchKits = async (page = 1) => {
  loading.value = true
  try {
    const params = new URLSearchParams({
      page: page,
      length: 10,
      search: searchQuery.value
    })

    const response = await fetch(`/kits/api/data?${params}`)
    const data = await response.json()

    kits.value = data.data
    pagination.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      from: data.from,
      to: data.to,
      total: data.recordsFiltered
    }

    updateStats(data)
  } catch (error) {
    console.error('Error fetching kits:', error)
    notyf.error('Error al cargar los kits')
  } finally {
    loading.value = false
  }
}

const updateStats = (data) => {
  // Usar estadísticas globales del backend
  if (data.stats) {
    stats.value.totalKits = data.stats.totalKits
    stats.value.kitsActivos = data.stats.kitsActivos
    stats.value.valorTotal = data.stats.valorTotal
  } else {
    // Fallback a cálculo local si no hay stats del backend
    stats.value.totalKits = data.recordsTotal
    stats.value.kitsActivos = kits.value.filter(k => k.estado === 'activo').length
    stats.value.valorTotal = kits.value.reduce((sum, k) => sum + parseFloat(k.precio_venta || 0), 0)
  }
}

const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    fetchKits(page)
  }
}

let searchTimeout
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchKits(1)
  }, 500)
}

const clearSearch = () => {
  searchQuery.value = ''
  fetchKits(1)
}

const deleteKit = async (kitId) => {
  if (!confirm('¿Estás seguro de que deseas eliminar este kit?')) {
    return
  }

  try {
    const response = await fetch(`/kits/${kitId}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json'
      }
    })

    const data = await response.json()

    if (data.success) {
      notyf.success('Kit eliminado exitosamente')
      fetchKits(pagination.value.current_page)
    } else {
      notyf.error(data.message || 'Error al eliminar el kit')
    }
  } catch (error) {
    console.error('Error:', error)
    notyf.error('Error al eliminar el kit')
  }
}

const formatCurrency = (value) => {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN'
  }).format(value || 0)
}

const viewKitDetails = async (kitId) => {
  showModal.value = true
  loadingDetails.value = true
  selectedKit.value = null

  try {
    const response = await fetch(`/kits/${kitId}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })

    const data = await response.json()
    selectedKit.value = data
  } catch (error) {
    console.error('Error loading kit details:', error)
    notyf.error('Error al cargar los detalles del kit')
    closeModal()
  } finally {
    loadingDetails.value = false
  }
}

const closeModal = () => {
  showModal.value = false
  selectedKit.value = null
}

const calculateMargin = (kit) => {
  if (!kit || !kit.precio_venta || !kit.costo_estimado) return 0
  const precioVentaSinIVA = kit.precio_venta / 1.16
  const margen = ((precioVentaSinIVA - kit.costo_estimado) / kit.costo_estimado) * 100
  return margen.toFixed(1)
}

// Lifecycle
onMounted(() => {
  fetchKits()
})
</script>

<style scoped>
.kits-index {
  min-height: 100vh;
  background-color: #f9fafb;
}
</style>




