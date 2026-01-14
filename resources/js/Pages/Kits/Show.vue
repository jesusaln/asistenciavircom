<template>
  <AppLayout :title="`Kit: ${kit.nombre}`">
    <div class="kits-show">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
          <div class="flex justify-between items-center">
            <div>
              <h1 class="text-3xl font-bold text-gray-900">{{ kit.nombre }}</h1>
              <p class="mt-2 text-sm text-gray-600">Detalles del kit y sus componentes</p>
            </div>
            <div class="flex space-x-3">
              <Link :href="`/kits/${kit.id}/edit`" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-amber-500 hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Editar Kit
              </Link>
              <Link href="/kits" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
                </svg>
                Volver a Kits
              </Link>
            </div>
          </div>
        </div>

        <!-- Kit Info Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <!-- Estado -->
          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div :class="kit.estado === 'activo' ? 'bg-green-100' : 'bg-red-100'" class="p-3 rounded-full">
                    <svg :class="kit.estado === 'activo' ? 'text-green-600' : 'text-red-600'" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Estado</dt>
                    <dd class="text-lg font-medium text-gray-900">
                      <span :class="kit.estado === 'activo' ? 'text-green-600' : 'text-red-600'">
                        {{ kit.estado === 'activo' ? 'Activo' : 'Inactivo' }}
                      </span>
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <!-- Precio de Venta -->
          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="bg-blue-100 p-3 rounded-full">
                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Precio de Venta</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ formatCurrency(kit.precio_venta) }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <!-- Componentes -->
          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="bg-purple-100 p-3 rounded-full">
                    <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Componentes</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ kit.kit_items?.length || 0 }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Alerta si no hay componentes -->
        <div v-if="!kit.kit_items || kit.kit_items.length === 0" 
             class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-8">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm text-yellow-700">
                <strong class="font-medium">Advertencia:</strong> Este kit no tiene componentes definidos. Agrega componentes para poder utilizarlo.
              </p>
            </div>
          </div>
        </div>

        <!-- Detalles del Kit -->
        <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-8">
          <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Información del Kit</h3>
          </div>

          <div class="px-6 py-6">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-6">
              <div>
                <dt class="text-sm font-medium text-gray-500">Código</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ kit.codigo || 'Sin código' }}</dd>
              </div>

              <div>
                <dt class="text-sm font-medium text-gray-500">Categoría</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ kit.categoria?.nombre || 'Sin categoría' }}</dd>
              </div>

              <div class="md:col-span-2">
                <dt class="text-sm font-medium text-gray-500">Descripción</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ kit.descripcion || 'Sin descripción' }}</dd>
              </div>

              <div>
                <dt class="text-sm font-medium text-gray-500">Creado</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ formatDate(kit.created_at) }}</dd>
              </div>

              <div>
                <dt class="text-sm font-medium text-gray-500">Última actualización</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ formatDate(kit.updated_at) }}</dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- Componentes del Kit -->
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
          <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Componentes del Kit</h3>
            <p class="mt-1 text-sm text-gray-600">Productos que conforman este kit</p>
          </div>

          <div class="px-6 py-6">
            <div v-if="kit.kit_items && kit.kit_items.length > 0" class="space-y-4">
              <div v-for="item in kit.kit_items" :key="item.id"
                   class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                <div class="flex justify-between items-start">
                  <div class="flex-1">
                    <div class="flex items-center space-x-3">
                      <div class="flex-shrink-0">
                        <div :class="isProducto(item) ? 'bg-gray-100' : 'bg-blue-50'" class="w-10 h-10 rounded-lg flex items-center justify-center">
                          <svg v-if="isProducto(item)" class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                          </svg>
                          <svg v-else class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                          </svg>
                        </div>
                      </div>
                      <div>
                        <div class="flex items-center gap-2">
                          <h4 class="text-sm font-medium text-gray-900">{{ getItemName(item) }}</h4>
                          <span :class="isProducto(item) ? 'bg-gray-100 text-gray-800' : 'bg-blue-100 text-blue-800'" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium">
                            {{ isProducto(item) ? 'Producto' : 'Servicio' }}
                          </span>
                        </div>
                        <p class="text-sm text-gray-500">{{ getItemCode(item) }}</p>
                      </div>
                    </div>
                  </div>

                  <div class="text-right">
                    <div class="text-sm font-medium text-gray-900">Cantidad: {{ item.cantidad }}</div>
                    <div class="text-sm text-gray-500">
                      Precio unitario: {{ formatCurrency(getItemPrice(item)) }}
                    </div>
                    <div class="text-sm font-medium text-amber-600">
                      Subtotal: {{ formatCurrency(getItemPrice(item) * item.cantidad) }}
                    </div>
                  </div>
                </div>
              </div>

              <!-- Total -->
              <div class="mt-6 border-t border-gray-200 pt-4">
                <div class="flex justify-end">
                  <div class="text-right">
                    <div class="text-sm text-gray-500 flex items-center justify-end gap-2">
                      Costo total estimado (Tiempo Real)
                      <svg v-if="loadingCosto" class="animate-spin h-4 w-4 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                    </div>
                    <div class="text-lg font-bold text-gray-900">{{ formatCurrency(costoFinal) }}</div>
                    <div class="text-sm text-gray-600 flex items-center gap-2 justify-end">
                      <span>Margen:</span>
                      <span :class="{
                        'text-green-600 font-semibold': margen >= 20,
                        'text-yellow-600 font-semibold': margen >= 10 && margen < 20,
                        'text-red-600 font-semibold': margen < 10
                      }">
                        {{ margen }}%
                      </span>
                      <span v-if="margen >= 20" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                        Excelente
                      </span>
                      <span v-else-if="margen >= 10" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                        Aceptable
                      </span>
                      <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                        Bajo
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div v-else class="text-center py-12">
              <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
              </svg>
              <h3 class="mt-2 text-sm font-medium text-gray-900">No hay componentes</h3>
              <p class="mt-1 text-sm text-gray-500">Este kit no tiene componentes configurados.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  kit: Object,
  costoEstimado: {
    type: Number,
    default: 0
  }
})

// Reactive state
const costoActual = ref(null)
const loadingCosto = ref(false)

// Computed
const costoTotalComponentes = computed(() => {
  if (!props.kit.kit_items) return 0

  return props.kit.kit_items.reduce((total, item) => {
    const precio = getItemPrice(item)
    return total + (precio * item.cantidad)
  }, 0)
})

const costoFinal = computed(() => {
  // Prioridad: costo actual (API) > costo estimado (backend) > costo de componentes
  return costoActual.value !== null ? costoActual.value : (props.costoEstimado || costoTotalComponentes.value)
})

const isProducto = (item) => {
  // Verificar si es producto basado en item_type o si tiene la relación producto
  return item.item_type === 'App\\Models\\Producto' || item.item_type === 'producto' || item.producto
}

const getItemName = (item) => {
  if (item.item) return item.item.nombre || 'Sin nombre'
  if (item.producto) return item.producto.nombre || 'Producto no encontrado'
  return 'Item no encontrado'
}

const getItemCode = (item) => {
  if (item.item) return item.item.codigo || 'N/A'
  if (item.producto) return item.producto.codigo || 'N/A'
  return 'N/A'
}

const getItemPrice = (item) => {
  if (item.precio_unitario) return item.precio_unitario
  
  // Si tiene relación item (polimórfica)
  if (item.item) {
    return isProducto(item) ? (item.item.precio_venta || 0) : (item.item.precio || 0)
  }
  
  // Fallback a relación producto legacy
  if (item.producto) {
    return item.producto.precio_venta || 0
  }
  
  return 0
}

const margen = computed(() => {
  const precioVenta = props.kit.precio_venta || 0
  const costo = costoFinal.value

  if (costo > 0 && precioVenta > 0) {
    // Quitar IVA del precio de venta (16%)
    const precioVentaSinIVA = precioVenta / 1.16
    return ((precioVentaSinIVA - costo) / costo * 100).toFixed(1)
  }
  return 0
})

const formatCurrency = (value) => {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN'
  }).format(value || 0)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('es-MX', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

// Calcular costo actual en tiempo real
const calcularCostoActual = async () => {
  if (!props.kit.kit_items || props.kit.kit_items.length === 0) {
    return
  }

  loadingCosto.value = true

  try {
    const componentes = props.kit.kit_items
      .filter(item => item.item_type && item.item_id && item.cantidad > 0)
      .map(item => ({
        item_type: item.item_type === 'App\\Models\\Producto' ? 'producto' : 
                   item.item_type === 'App\\Models\\Servicio' ? 'servicio' : 
                   item.item_type,
        item_id: Number(item.item_id),
        cantidad: Number(item.cantidad),
        precio_unitario: item.precio_unitario
      }))

    if (componentes.length === 0) {
      return
    }

    const response = await fetch('/kits/api/calcular-costo', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        componentes: componentes,
        almacen_id: 1 // Almacén principal por defecto
      })
    })

    const data = await response.json()

    if (data.success) {
      costoActual.value = data.costo_total
    } else {
      console.error('Error calculando costo:', data.error)
    }
  } catch (error) {
    console.error('Error:', error)
  } finally {
    loadingCosto.value = false
  }
}

// Lifecycle
onMounted(() => {
  calcularCostoActual()
})
</script>

<style scoped>
@import "tailwindcss" reference;
.kits-show {
  min-height: 100vh;
  background-color: #f9fafb;
}
</style>
