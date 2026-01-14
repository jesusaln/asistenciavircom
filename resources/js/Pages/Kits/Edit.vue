<template>
  <AppLayout :title="`Editar Kit: ${kit.nombre}`">
    <div class="kits-edit">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
          <div class="flex justify-between items-center">
            <div>
              <h1 class="text-3xl font-bold text-gray-900">Editar Kit</h1>
              <p class="mt-2 text-sm text-gray-600">Modifica la información del kit y sus componentes</p>
            </div>
            <div class="flex space-x-3">
              <Link :href="`/kits/${kit.id}`" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                Ver Kit
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

        <!-- Form -->
        <form @submit.prevent="submitForm" class="bg-white shadow-lg rounded-lg overflow-hidden">
          <!-- Información Básica del Kit -->
          <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Información del Kit</h3>
            <p class="mt-1 text-sm text-gray-600">Modifica el nombre, precio y detalles básicos del kit</p>
          </div>

          <div class="px-6 py-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Nombre -->
              <div>
                <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre del Kit *</label>
                <input v-model="form.nombre" type="text" id="nombre"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm"
                       required>
                <div v-if="errors.nombre" class="mt-1 text-sm text-red-600">{{ errors.nombre[0] }}</div>
              </div>

              <!-- Código -->
              <div>
                <label for="codigo" class="block text-sm font-medium text-gray-700">Código</label>
                <input v-model="form.codigo" type="text" id="codigo"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm">
                <div v-if="errors.codigo" class="mt-1 text-sm text-red-600">{{ errors.codigo[0] }}</div>
              </div>
            </div>

            <!-- Descripción -->
            <div>
              <label for="descripcion" class="block text-sm font-medium text-gray-700">Descripción</label>
              <textarea v-model="form.descripcion" id="descripcion" rows="3"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm"
                        placeholder="Describe el kit y su propósito"></textarea>
              <div v-if="errors.descripcion" class="mt-1 text-sm text-red-600">{{ errors.descripcion[0] }}</div>
            </div>

            <!-- Precio de Venta -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="precio_venta" class="block text-sm font-medium text-gray-700">Precio de Venta *</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-gray-500 sm:text-sm">$</span>
                  </div>
                  <input v-model.number="form.precio_venta" type="number" step="0.01" min="0" id="precio_venta"
                         class="pl-7 block w-full border-gray-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm"
                         required>
                </div>
                <div v-if="errors.precio_venta" class="mt-1 text-sm text-red-600">{{ errors.precio_venta[0] }}</div>
              </div>

              <!-- Estado -->
              <div>
                <label for="estado" class="block text-sm font-medium text-gray-700">Estado</label>
                <select v-model="form.estado" id="estado"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm">
                  <option value="activo">Activo</option>
                  <option value="inactivo">Inactivo</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Componentes del Kit -->
          <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <div class="flex justify-between items-center">
              <div>
                <h3 class="text-lg font-medium text-gray-900">Componentes del Kit</h3>
                <p class="mt-1 text-sm text-gray-600">Modifica los productos y/o servicios que conforman este kit</p>
              </div>
              <button type="button" @click="addComponent"
                      class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Agregar Componente
              </button>
            </div>
          </div>

          <div class="px-6 py-6">
            <!-- Lista de Componentes -->
            <div class="space-y-4">
              <div v-for="(componente, index) in form.componentes" :key="index"
                   class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <div class="flex justify-between items-start mb-4">
                  <h4 class="text-sm font-medium text-gray-900">Componente #{{ index + 1 }}</h4>
                  <button type="button" @click="removeComponent(index)"
                          class="text-red-600 hover:text-red-800 p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                  </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                  <!-- Tipo -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Tipo *</label>
                    <select v-model="componente.item_type"
                            @change="clearItemSelection(index)"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm"
                            required>
                      <option value="">Seleccionar</option>
                      <option value="producto">Producto</option>
                      <option value="servicio">Servicio</option>
                    </select>
                  </div>

                  <!-- Item (Producto o Servicio) -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700">
                      {{ componente.item_type === 'servicio' ? 'Servicio' : 'Producto' }} *
                    </label>
                    <select v-model="componente.item_id"
                            @change="updateItemInfo(index)"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm"
                            :disabled="!componente.item_type"
                            required>
                      <option value="">Seleccionar {{ componente.item_type === 'servicio' ? 'servicio' : 'producto' }}</option>
                  <template v-if="componente.item_type === 'producto'">
                    <option
                      v-for="producto in productosDisponibles"
                      :key="producto.id"
                      :value="producto.id">
                      {{ producto.codigo }} - {{ producto.nombre }}
                    </option>
                  </template>
                      <template v-else-if="componente.item_type === 'servicio'">
                        <option v-for="servicio in serviciosDisponibles" :key="servicio.id" :value="servicio.id">
                          {{ servicio.codigo }} - {{ servicio.nombre }}
                        </option>
                      </template>
                    </select>
                  </div>

                  <!-- Cantidad -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Cantidad *</label>
                    <input v-model.number="componente.cantidad" type="number" min="1" step="1"
                           @input="calculateCosts"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm"
                           required>
                  </div>

                  <!-- Precio Unitario (opcional) -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Precio Unitario</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">$</span>
                      </div>
                      <input v-model.number="componente.precio_unitario" type="number" step="0.01" min="0"
                             @input="calculateCosts"
                             class="pl-7 block w-full border-gray-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm"
                             :placeholder="getItemPrecio(componente)">
                    </div>
                  </div>
                </div>

                <!-- Series Button for products that require series -->
                <div v-if="componente.item_type === 'producto' && componente.requiereSeries" class="mt-3 flex items-center gap-2">
                  <button type="button" @click="openSerials(componente, index)"
                          class="inline-flex items-center px-3 py-1 border border-orange-300 rounded-md text-xs font-medium text-orange-700 bg-orange-50 hover:bg-orange-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Seleccionar Series ({{ componente.cantidad }} necesarias)
                  </button>
                  <span class="text-xs text-orange-600">* Este producto requiere números de serie</span>
                </div>
              </div>
            </div>

            <div v-if="errors.componentes" class="mt-4 text-sm text-red-600">{{ errors.componentes[0] }}</div>

            <!-- Costo Total Calculado -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-md p-4">
              <div class="flex justify-between items-center">
                <div>
                  <h4 class="text-sm font-medium text-blue-900">Costo Total Estimado</h4>
                  <p class="text-xs text-blue-700">Solo productos (servicios tienen costo $0)</p>
                </div>
                <div class="text-right">
                  <span class="text-2xl font-bold text-blue-900">{{ formatCurrency(costoTotal) }}</span>
                  <p class="text-xs text-blue-600">Margen: {{ margen }}%</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3">
            <Link href="/kits" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
              Cancelar
            </Link>
            <button type="submit" :disabled="loading"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-amber-500 hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 disabled:opacity-50">
              <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              {{ loading ? 'Guardando...' : 'Guardar Cambios' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Seleccionar Series -->
    <div v-if="showSeriesPicker" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="closeSeriesPicker">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900">Seleccionar series: {{ pickerProducto?.nombre || '' }}</h3>
          <button @click="closeSeriesPicker" class="text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <div class="p-6">
          <div class="mb-3 text-sm text-gray-700">
            Selecciona exactamente {{ pickerRequired }} {{ pickerRequired === 1 ? 'serie' : 'series' }}. Seleccionadas: {{ selectedSeries.length }}.
          </div>
          <div class="mb-3 grid grid-cols-1 md:grid-cols-2 gap-3">
            <input v-model.trim="pickerSearch" type="text" placeholder="Buscar número de serie" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500" />
            <div class="text-xs text-gray-500 self-center">
              <span class="inline-block px-2 py-1 bg-emerald-100 text-emerald-700 rounded">En stock: {{ pickerSeries.length }}</span>
            </div>
          </div>
          <div class="max-h-72 overflow-y-auto border border-gray-200 rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Sel</th>
                  <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Número de serie</th>
                  <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Almacén</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="s in filteredPickerSeries" :key="s.id">
                  <td class="px-4 py-2 text-sm">
                    <input type="checkbox" :checked="selectedSeries.includes(s.numero_serie)" @change="toggleSerie(s.numero_serie)" :disabled="!selectedSeries.includes(s.numero_serie) && selectedSeries.length >= pickerRequired" />
                  </td>
                  <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ s.numero_serie }}</td>
                  <td class="px-4 py-2 text-sm text-gray-700">{{ nombreAlmacen(s.almacen_id) }}</td>
                </tr>
                <tr v-if="filteredPickerSeries.length === 0">
                  <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500">Sin series disponibles</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 text-right">
          <button @click="closeSeriesPicker" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors mr-2">Cancelar</button>
          <button @click="confirmSeries" :disabled="selectedSeries.length !== pickerRequired" class="px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-colors disabled:opacity-50">Usar {{ selectedSeries.length }}/{{ pickerRequired }} series</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'

const props = defineProps({
  kit: Object,
  productosDisponibles: Array,
  serviciosDisponibles: Array,
  almacenPrincipal: Object
})

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false },
    { type: 'info', background: '#3b82f6', icon: false }
  ]
})

// Reactive data
const form = ref({
  nombre: props.kit.nombre || '',
  descripcion: props.kit.descripcion || '',
  codigo: props.kit.codigo || '',
  precio_venta: props.kit.precio_venta || null,
  estado: props.kit.estado || 'activo',
  componentes: []
})

const errors = ref({})
const loading = ref(false)
const costoTotal = ref(0)
const margen = ref(0)

// Series picker variables
const showSeriesPicker = ref(false)
const pickerKey = ref('')
const pickerProducto = ref(null)
const pickerSeries = ref([])
const pickerSearch = ref('')
const selectedSeries = ref([])
const pickerRequiredOverride = ref(null)
const pickerRequired = computed(() => {
  if (pickerRequiredOverride.value !== null) return pickerRequiredOverride.value
  if (!pickerKey.value) return 0
  const componente = form.value.componentes.find(c => `${c.item_type}-${c.item_id}` === pickerKey.value)
  return componente ? Number(componente.cantidad) || 0 : 0
})

// Initialize componentes from kit.kit_items
onMounted(() => {
  if (props.kit.kit_items) {
    form.value.componentes = props.kit.kit_items.map(item => {
      // Determinar el tipo basado en item_type (ya normalizado por el backend)
      const itemType = item.item_type === 'servicio' ? 'servicio' : 'producto'

      return {
        item_type: itemType,
        item_id: item.item_id,
        cantidad: item.cantidad,
        precio_unitario: item.precio_unitario
      }
    })
  }

  if (form.value.componentes.length === 0) {
    addComponent()
  }

  calculateCosts()
})

// Methods
const addComponent = () => {
  form.value.componentes.push({
    item_type: '',
    item_id: '',
    cantidad: 1,
    precio_unitario: null
  })
}

const removeComponent = (index) => {
  form.value.componentes.splice(index, 1)
  calculateCosts()
}

const clearItemSelection = (index) => {
  form.value.componentes[index].item_id = ''
  form.value.componentes[index].precio_unitario = null
  calculateCosts()
}

const updateItemInfo = (index) => {
  const componente = form.value.componentes[index]
  if (!componente.item_type || !componente.item_id) return

  // Check if product requires series
  if (componente.item_type === 'producto') {
    const producto = props.productosDisponibles.find(p => p.id == componente.item_id)
    const requiereSeries = producto && (producto.requiere_serie || producto.maneja_series || producto.expires)
    
    // Store series requirement info on the component
    componente.requiereSeries = requiereSeries
    componente.productoNombre = producto?.nombre || 'Producto'
    
    // If product requires series, open selector immediately
    if (requiereSeries) {
      openSerials(componente, index)
    }
  } else if (componente.item_type === 'servicio') {
    componente.requiereSeries = false
    componente.productoNombre = props.serviciosDisponibles.find(s => s.id == componente.item_id)?.nombre || 'Servicio'
  }

  if (!componente.precio_unitario) {
    if (componente.item_type === 'producto') {
      const producto = props.productosDisponibles.find(p => p.id == componente.item_id)
      if (producto) {
        componente.precio_unitario = producto.precio_venta
      }
    } else if (componente.item_type === 'servicio') {
      const servicio = props.serviciosDisponibles.find(s => s.id == componente.item_id)
      if (servicio) {
        componente.precio_unitario = servicio.precio
      }
    }
  }
  calculateCosts()
}

// Series picker functions
const nombreAlmacen = (id) => {
  if (!id) return 'N/D'
  // Buscar en props.almacenPrincipal si está disponible
  const a = props.almacenPrincipal
  return a && String(a.id) === String(id) ? a.nombre : `Almacén ${id}`
}

const filteredPickerSeries = computed(() => {
  const q = (pickerSearch.value || '').toLowerCase()
  let list = pickerSeries.value || []

  // Filtrar por almacén principal del kit
  if (props.almacenPrincipal?.id) {
    list = list.filter(s => String(s.almacen_id) === String(props.almacenPrincipal.id))
  }

  return q ? list.filter(s => (s.numero_serie || '').toLowerCase().includes(q)) : list
})

const openSerials = async (componente, index) => {
  try {
    pickerRequiredOverride.value = null // Limpiar override
    pickerKey.value = `${componente.item_type}-${componente.item_id}`
    pickerProducto.value = componente.item_type === 'producto' 
      ? props.productosDisponibles.find(p => p.id == componente.item_id) 
      : { id: componente.item_id, nombre: 'Servicio' }
    
    // Cargar series del backend
    let url = ''
    try { url = route('productos.series', componente.item_id) } catch (e) { url = `/productos/${componente.item_id}/series` }
    
    if (props.almacenPrincipal?.id) {
      url += `?almacen_id=${props.almacenPrincipal.id}`
    }
    
    const res = await fetch(url, { 
      method: 'GET', 
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, 
      credentials: 'same-origin' 
    })
    
    if (!res.ok) { 
      notyf.error('No se pudieron cargar las series') 
      return 
    }
    
    const data = await res.json()
    pickerSeries.value = data?.series?.en_stock || []
    selectedSeries.value = [] // Reset selection for kit editing
    showSeriesPicker.value = true
  } catch (e) {
    console.error('Error al abrir selector de series:', e)
    notyf.error('Error al abrir selector de series')
  }
}

const closeSeriesPicker = () => {
  showSeriesPicker.value = false
  pickerKey.value = ''
  pickerProducto.value = null
  pickerSeries.value = []
  pickerSearch.value = ''
  selectedSeries.value = []
  pickerRequiredOverride.value = null
}

const toggleSerie = (numero) => {
  const idx = selectedSeries.value.indexOf(numero)
  if (idx >= 0) {
    selectedSeries.value.splice(idx, 1)
  } else if (selectedSeries.value.length < pickerRequired.value) {
    const serieObj = pickerSeries.value.find(s => s.numero_serie === numero)
    if (serieObj && serieObj.almacen_id && props.almacenPrincipal?.id && String(props.almacenPrincipal.id) !== String(serieObj.almacen_id)) {
      const nombre = nombreAlmacen(serieObj.almacen_id)
      notyf.error(`La serie pertenece al almacén "${nombre}". Usa el almacén principal del kit.`)
      return
    }
    selectedSeries.value.push(numero)

    // Auto-confirm when required number is reached
    if (selectedSeries.value.length === pickerRequired.value) {
      setTimeout(() => {
        confirmSeries()
      }, 300)
    }
  }
}

const confirmSeries = () => {
  if (!pickerKey.value) return
  if (selectedSeries.value.length !== pickerRequired.value) {
    notyf.error(`Debes seleccionar ${pickerRequired.value} series`)
    return
  }
  
  // For kit editing, we don't store series persistently
  // This is just for validation during editing
  notyf.success('Series validadas correctamente')
  closeSeriesPicker()
}

const getItemPrecio = (componente) => {
  if (componente.item_type === 'producto') {
    const producto = props.productosDisponibles.find(p => p.id == componente.item_id)
    return producto ? `$${producto.precio_venta}` : 'Precio del producto'
  } else if (componente.item_type === 'servicio') {
    const servicio = props.serviciosDisponibles.find(s => s.id == componente.item_id)
    return servicio ? `$${servicio.precio}` : 'Precio del servicio'
  }
  return 'Seleccione un item'
}

const calculateCosts = async () => {
  const componentes = form.value.componentes
    .filter(c => c.item_type && c.item_id && c.cantidad > 0)
    .map(c => ({
      item_type: c.item_type,
      item_id: Number(c.item_id),
      cantidad: Number(c.cantidad),
      precio_unitario: c.precio_unitario
    }))

  if (componentes.length === 0) {
    costoTotal.value = 0
    margen.value = 0
    return
  }

  try {
    const response = await fetch('/kits/api/calcular-costo', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        componentes: componentes,
        almacen_id: props.almacenPrincipal?.id || 1
      })
    })

    const data = await response.json()

    if (data.success) {
      costoTotal.value = data.costo_total
      updateMargen()
    } else {
      console.error('Error calculando costo:', data.error)
    }
  } catch (error) {
    console.error('Error:', error)
  }
}

const updateMargen = () => {
  const precioVenta = form.value.precio_venta || 0
  if (costoTotal.value > 0 && precioVenta > 0) {
    // Quitar IVA del precio de venta (16%)
    const precioVentaSinIVA = precioVenta / 1.16
    margen.value = (((precioVentaSinIVA - costoTotal.value) / costoTotal.value) * 100).toFixed(1)
  } else if (costoTotal.value === 0 && precioVenta > 0) {
    margen.value = '100.0' // Solo servicios
  } else {
    margen.value = 0
  }
}

const submitForm = async () => {
  if (form.value.componentes.length === 0) {
    notyf.error('Debes agregar al menos un componente al kit.')
    return
  }

  // Validar componentes
  const componentesInvalidos = form.value.componentes.filter(c =>
    !c.item_type || !c.item_id || !c.cantidad || c.cantidad <= 0
  )

  if (componentesInvalidos.length > 0) {
    notyf.error('Todos los componentes deben tener tipo, item y cantidad válida.')
    return
  }

  const productosCount = form.value.componentes.filter(c => c.item_type === 'producto').length
  if (productosCount === 0) {
    notyf.error('El kit debe incluir al menos un producto.')
    return
  }

  loading.value = true
  errors.value = {}

  try {
    const response = await fetch(`/kits/${props.kit.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(form.value)
    })

    const data = await response.json()

    if (response.ok) {
      notyf.success('Kit actualizado exitosamente')
      router.visit(`/kits/${props.kit.id}`)
    } else {
      if (data.errors) {
        errors.value = data.errors
      } else {
        notyf.error(data.message || 'Error al actualizar el kit')
      }
    }
  } catch (error) {
    console.error('Error:', error)
    notyf.error('Error al actualizar el kit')
  } finally {
    loading.value = false
  }
}

const formatCurrency = (value) => {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN'
  }).format(value || 0)
}

// Watchers
watch(() => form.value.precio_venta, updateMargen)
</script>

<style scoped>
@import "tailwindcss" reference;
.kits-edit {
  min-height: 100vh;
  background-color: #f9fafb;
}
</style>

