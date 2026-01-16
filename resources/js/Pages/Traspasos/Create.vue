<!-- /resources/js/Pages/Traspasos/Create.vue -->
<script setup>
import { ref, computed, watch } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
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

const page = usePage()
const props = defineProps({
  productos: { type: Array, default: () => [] },
  almacenes: { type: Array, default: () => [] },
  inventarios: { type: Array, default: () => [] }
})

// Form data - Nueva estructura para múltiples productos
const form = ref({
  almacen_origen_id: '',
  almacen_destino_id: '',
  items: [],
  referencia: '',
  costo_transporte: 0,
  observaciones: ''
})

// Estados
const loading = ref(false)
const showNoStockModal = ref(false)
const suggestedAlmacen = ref(null)

// Item temporal para agregar
const nuevoItem = ref({
  producto_id: '',
  cantidad: 1,
  series: []
})
const seriesDisponibles = ref([])
const seriesSeleccionadas = ref([])
const cargandoSeries = ref(false)

// Computed
const productoSeleccionado = computed(() => props.productos.find(p => p.id == nuevoItem.value.producto_id))
const requiereSerie = computed(() => !!productoSeleccionado.value?.requiere_serie)

const almacenesDestino = computed(() => {
  return props.almacenes.filter(almacen => almacen.id != form.value.almacen_origen_id)
})

const stockProductoEnOrigen = computed(() => {
  if (!nuevoItem.value.producto_id || !form.value.almacen_origen_id) return 0
  const inventario = props.inventarios.find(inv =>
    inv.producto_id == nuevoItem.value.producto_id &&
    inv.almacen_id == form.value.almacen_origen_id
  )
  return inventario ? inventario.cantidad : 0
})

// Productos disponibles para agregar (no repetidos)
const productosDisponibles = computed(() => {
  const idsAgregados = form.value.items.map(i => i.producto_id)
  return props.productos.filter(p => !idsAgregados.includes(p.id))
})

// Productos que tienen stock en el almacén origen
const productosConStock = computed(() => {
  if (!form.value.almacen_origen_id) return productosDisponibles.value
  return productosDisponibles.value.filter(producto => {
    const inv = props.inventarios.find(i =>
      i.producto_id == producto.id &&
      i.almacen_id == form.value.almacen_origen_id
    )
    return inv && inv.cantidad > 0
  })
})

// Total de productos en el traspaso
const totalProductos = computed(() => form.value.items.length)
const totalUnidades = computed(() => form.value.items.reduce((sum, item) => sum + item.cantidad, 0))

// Watches
watch(() => form.value.almacen_destino_id, () => {
  if (form.value.almacen_destino_id === form.value.almacen_origen_id) {
    form.value.almacen_destino_id = ''
  }
})

watch(() => [nuevoItem.value.producto_id, form.value.almacen_origen_id], () => {
  seriesSeleccionadas.value = []
  nuevoItem.value.series = []
  if (requiereSerie.value && form.value.almacen_origen_id) {
    cargarSeriesDisponibles()
  } else {
    seriesDisponibles.value = []
  }
}, { deep: true })

watch(() => seriesSeleccionadas.value.length, (val) => {
  if (requiereSerie.value) {
    nuevoItem.value.cantidad = val
    nuevoItem.value.series = [...seriesSeleccionadas.value]
  }
})

// Métodos
const cargarSeriesDisponibles = async () => {
  if (!nuevoItem.value.producto_id || !form.value.almacen_origen_id || !requiereSerie.value) {
    seriesDisponibles.value = []
    return
  }

  cargandoSeries.value = true
  try {
    const url = route('productos.series', nuevoItem.value.producto_id) + `?almacen_id=${form.value.almacen_origen_id}`
    const res = await fetch(url, { headers: { Accept: 'application/json' } })
    if (!res.ok) throw new Error(`Error ${res.status}`)
    const data = await res.json()
    seriesDisponibles.value = data?.series?.en_stock || []
  } catch (error) {
    console.error('Error cargando series disponibles', error)
    notyf.error('No se pudieron cargar las series del almacén origen')
    seriesDisponibles.value = []
  } finally {
    cargandoSeries.value = false
  }
}

const agregarProducto = () => {
  if (!nuevoItem.value.producto_id) {
    notyf.error('Selecciona un producto')
    return
  }

  if (requiereSerie.value) {
    if (seriesSeleccionadas.value.length === 0) {
      notyf.error('Selecciona al menos una serie')
      return
    }
    nuevoItem.value.cantidad = seriesSeleccionadas.value.length
    nuevoItem.value.series = [...seriesSeleccionadas.value]
  } else {
    if (nuevoItem.value.cantidad < 1) {
      notyf.error('La cantidad debe ser al menos 1')
      return
    }
    if (nuevoItem.value.cantidad > stockProductoEnOrigen.value) {
      notyf.error('La cantidad excede el stock disponible')
      return
    }
  }

  const producto = props.productos.find(p => p.id == nuevoItem.value.producto_id)
  
  form.value.items.push({
    producto_id: nuevoItem.value.producto_id,
    producto_nombre: producto?.nombre || 'Producto',
    cantidad: nuevoItem.value.cantidad,
    series: [...nuevoItem.value.series],
    requiere_serie: requiereSerie.value
  })

  // Reset
  nuevoItem.value = { producto_id: '', cantidad: 1, series: [] }
  seriesSeleccionadas.value = []
  seriesDisponibles.value = []

  notyf.success('Producto agregado al traspaso')
}

const eliminarItem = (index) => {
  form.value.items.splice(index, 1)
}

const submit = () => {
  if (form.value.items.length === 0) {
    notyf.error('Agrega al menos un producto al traspaso')
    return
  }

  if (!form.value.almacen_origen_id) {
    notyf.error('Selecciona el almacén origen')
    return
  }

  if (!form.value.almacen_destino_id) {
    notyf.error('Selecciona el almacén destino')
    return
  }

  loading.value = true

  router.post(route('traspasos.store'), form.value, {
    onSuccess: () => {
      notyf.success('Traspaso realizado correctamente')
      router.visit(route('traspasos.index'))
    },
    onError: (errors) => {
      console.error('Errores de validación:', errors)
      const errorMsg = errors.error || Object.values(errors)[0] || 'Error al realizar el traspaso'
      notyf.error(errorMsg)
    },
    onFinish: () => {
      loading.value = false
    }
  })
}

const cancel = () => {
  router.visit(route('traspasos.index'))
}
</script>

<template>
  <Head title="Nuevo Traspaso" />

  <div class="min-h-screen bg-gray-50">
    <div class="max-w-5xl mx-auto px-6 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Nuevo Traspaso</h1>
            <p class="text-gray-600 mt-1">Transfiere múltiples productos entre almacenes</p>
          </div>
          <button
            @click="cancel"
            class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Cancelar
          </button>
        </div>
      </div>

      <!-- Formulario Principal -->
      <div class="space-y-6">
        <!-- Almacenes Origen/Destino -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            Almacenes
          </h2>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Almacén Origen -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Almacén Origen *</label>
              <select
                v-model="form.almacen_origen_id"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">Seleccionar origen</option>
                <option v-for="almacen in almacenes" :key="almacen.id" :value="almacen.id">
                  {{ almacen.nombre }}
                </option>
              </select>
            </div>

            <!-- Almacén Destino -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Almacén Destino *</label>
              <select
                v-model="form.almacen_destino_id"
                :disabled="!form.almacen_origen_id"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100"
              >
                <option value="">Seleccionar destino</option>
                <option v-for="almacen in almacenesDestino" :key="almacen.id" :value="almacen.id">
                  {{ almacen.nombre }}
                </option>
              </select>
            </div>
          </div>
        </div>

        <!-- Agregar Productos -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6" v-if="form.almacen_origen_id">
          <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Agregar Producto
          </h2>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <!-- Producto -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Producto</label>
              <select
                v-model="nuevoItem.producto_id"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">Seleccionar producto</option>
                <option v-for="producto in productosConStock" :key="producto.id" :value="producto.id">
                  {{ producto.nombre }} {{ producto.requiere_serie ? '(Serie)' : '' }}
                </option>
              </select>
              <p v-if="nuevoItem.producto_id && stockProductoEnOrigen > 0" class="mt-1 text-sm text-gray-500">
                Stock disponible: <strong>{{ stockProductoEnOrigen }}</strong>
              </p>
            </div>

            <!-- Cantidad (solo si no requiere serie) -->
            <div v-if="!requiereSerie">
              <label class="block text-sm font-medium text-gray-700 mb-2">Cantidad</label>
              <input
                v-model.number="nuevoItem.cantidad"
                type="number"
                min="1"
                :max="stockProductoEnOrigen"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <!-- Botón Agregar -->
            <div class="flex items-end">
              <button
                @click="agregarProducto"
                :disabled="!nuevoItem.producto_id"
                class="w-full px-4 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Agregar
              </button>
            </div>
          </div>

          <!-- Selector de Series -->
          <div v-if="requiereSerie" class="border border-blue-200 bg-blue-50 rounded-lg p-4 mt-4">
            <div class="flex items-center justify-between mb-3">
              <div>
                <p class="font-medium text-blue-800">Series disponibles en {{ almacenes.find(a => a.id == form.almacen_origen_id)?.nombre }}</p>
                <p class="text-sm text-blue-700">Selecciona las series que deseas traspasar</p>
              </div>
              <button
                type="button"
                @click="cargarSeriesDisponibles"
                :disabled="cargandoSeries"
                class="text-sm text-blue-700 hover:underline disabled:opacity-50"
              >
                {{ cargandoSeries ? 'Cargando...' : 'Recargar' }}
              </button>
            </div>
            
            <div v-if="cargandoSeries" class="text-center py-4">
              <svg class="animate-spin h-6 w-6 mx-auto text-blue-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </div>
            <div v-else-if="!seriesDisponibles.length" class="text-sm text-blue-700">
              No hay series en stock en este almacén para este producto.
            </div>
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 max-h-48 overflow-y-auto">
              <label
                v-for="serie in seriesDisponibles"
                :key="serie.id"
                class="flex items-center gap-3 p-2 bg-white border border-blue-200 rounded-md hover:bg-blue-100 cursor-pointer"
              >
                <input
                  type="checkbox"
                  :value="serie.id"
                  v-model="seriesSeleccionadas"
                  class="text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                />
                <span class="text-sm text-gray-800 font-mono">{{ serie.numero_serie }}</span>
              </label>
            </div>
            <p class="mt-3 text-sm text-blue-800 font-medium">
              Seleccionadas: {{ seriesSeleccionadas.length }}
            </p>
          </div>
        </div>

        <!-- Lista de Productos Agregados -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6" v-if="form.items.length > 0">
          <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center justify-between">
            <span class="flex items-center">
              <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
              Productos a Traspasar
            </span>
            <span class="text-sm font-normal text-gray-500">
              {{ totalProductos }} producto(s) • {{ totalUnidades }} unidad(es)
            </span>
          </h2>

          <div class="space-y-3">
            <div
              v-for="(item, index) in form.items"
              :key="index"
              class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200"
            >
              <div class="flex-1">
                <p class="font-medium text-gray-900">{{ item.producto_nombre }}</p>
                <p class="text-sm text-gray-500">
                  Cantidad: <strong>{{ item.cantidad }}</strong>
                  <span v-if="item.requiere_serie" class="ml-2 text-blue-600">
                    ({{ item.series.length }} series)
                  </span>
                </p>
              </div>
              <button
                @click="eliminarItem(index)"
                class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                title="Eliminar"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Información Adicional -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h2 class="text-lg font-semibold text-gray-800 mb-4">Información Adicional</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Referencia</label>
              <input
                v-model="form.referencia"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Ej: TRSP-001"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Costo transporte</label>
              <input
                v-model.number="form.costo_transporte"
                type="number"
                min="0"
                step="0.01"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">Observaciones</label>
              <textarea
                v-model="form.observaciones"
                rows="3"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Notas adicionales sobre el traspaso..."
              ></textarea>
            </div>
          </div>
        </div>

        <!-- Botones -->
        <div class="flex justify-end gap-4">
          <button
            type="button"
            @click="cancel"
            :disabled="loading"
            class="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors disabled:opacity-50"
          >
            Cancelar
          </button>
          <button
            @click="submit"
            :disabled="loading || form.items.length === 0 || !form.almacen_origen_id || !form.almacen_destino_id"
            class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
          >
            <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
            </svg>
            {{ loading ? 'Procesando...' : 'Realizar Traspaso' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>

