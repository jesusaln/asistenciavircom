<!-- /resources/js/Pages/AjustesInventario/Create.vue -->
<script setup>
import { ref, onMounted, computed, watch } from 'vue'
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
onMounted(() => {
  const flash = page.props.flash
  if (flash?.success) notyf.success(flash.success)
  if (flash?.error) notyf.error(flash.error)
})

// Props
const props = defineProps({
  productos: { type: Array, default: () => [] },
  almacenes: { type: Array, default: () => [] },
})

// Form data
const form = ref({
  producto_id: '',
  almacen_id: '',
  tipo: 'incremento',
  cantidad_ajuste: '',
  motivo: '',
  observaciones: '',
})

// Estados
const loading = ref(false)
const stockActual = ref(0)
const loadingStock = ref(false)

// Computed
const selectedProducto = computed(() => {
  const id = form.value.producto_id
  return props.productos.find(p => String(p.id) === String(id)) || null
})
const requiereSerie = computed(() => !!(selectedProducto.value && selectedProducto.value.requiere_serie))

const cantidadNueva = computed(() => {
  const actual = parseInt(stockActual.value) || 0
  const ajuste = parseInt(form.value.cantidad_ajuste) || 0

  if (form.value.tipo === 'incremento') {
    return actual + ajuste
  } else {
    return Math.max(0, actual - ajuste)
  }
})

// Series para productos que lo requieren
const seriales = ref([])
const availableSerials = ref([])
const loadingSerials = ref(false)
const requiredSerials = computed(() => requiereSerie.value ? (parseInt(form.value.cantidad_ajuste) || 0) : 0)

watch(requiredSerials, (n) => {
  const next = (seriales.value || []).slice(0, n)
  while (next.length < n) next.push('')
  seriales.value = next
})

watch(() => form.value.producto_id, () => { 
  seriales.value = [] 
  loadAvailableSerials()
})
watch(() => form.value.tipo, () => { 
  seriales.value = [] 
  loadAvailableSerials()
})

const puedeGuardar = computed(() => {
  const baseOk = form.value.producto_id &&
    form.value.almacen_id &&
    form.value.cantidad_ajuste > 0 &&
    form.value.motivo.trim() &&
    (form.value.tipo !== 'decremento' || cantidadNueva.value >= 0)

  if (!baseOk) return false
  if (!requiereSerie.value) return true

  const req = requiredSerials.value
  if (req <= 0) return false
  const trimmed = (seriales.value || []).map(s => (s || '').trim()).filter(Boolean)
  const unique = new Set(trimmed)
  return trimmed.length === req && unique.size === trimmed.length
})

// Métodos
const loadAvailableSerials = async () => {
  if (!form.value.producto_id || !form.value.almacen_id || form.value.tipo !== 'decremento' || !requiereSerie.value) {
    availableSerials.value = []
    return
  }

  loadingSerials.value = true
  try {
    const response = await fetch(route('productos.series', { 
      producto: form.value.producto_id, 
      almacen_id: form.value.almacen_id 
    }))
    if (response.ok) {
      const data = await response.json()
      availableSerials.value = data.series.en_stock || []
    } else {
      availableSerials.value = []
    }
  } catch (error) {
    console.error('Error cargando series:', error)
    availableSerials.value = []
  } finally {
    loadingSerials.value = false
  }
}

const cargarStockActual = async () => {
  if (!form.value.producto_id || !form.value.almacen_id) {
    stockActual.value = 0
    return
  }

  loadingStock.value = true
  try {
    const response = await fetch(route('productos.stock-detalle', form.value.producto_id))
    if (response.ok) {
      const data = await response.json()
      const stockAlmacen = data.stock_por_almacen.find(s => s.almacen_id == form.value.almacen_id)
      stockActual.value = stockAlmacen ? stockAlmacen.cantidad : 0
    } else {
      stockActual.value = 0
    }
  } catch (error) {
    console.error('Error cargando stock:', error)
    stockActual.value = 0
  } finally {
    loadingStock.value = false
  }
}

const submit = () => {
  if (!puedeGuardar.value) return

  loading.value = true

  const payload = { ...form.value }
  if (requiereSerie.value && (seriales.value || []).length > 0) {
    payload.seriales = (seriales.value || []).map(s => (s || '').trim()).filter(Boolean)
  }

  router.post(route('ajustes-inventario.store'), payload, {
    onSuccess: () => {
      notyf.success('Ajuste de inventario realizado correctamente')
      router.visit(route('ajustes-inventario.index'))
    },
    onError: (errors) => {
      console.error('Errores de validación:', errors)
      notyf.error('Error al realizar el ajuste de inventario')
    },
    onFinish: () => {
      loading.value = false
    }
  })
}

const cancel = () => {
  router.visit(route('ajustes-inventario.index'))
}

// Watchers
const watchProducto = () => {
  if (form.value.producto_id && form.value.almacen_id) {
    cargarStockActual()
    loadAvailableSerials()
  }
}

const watchAlmacen = () => {
  if (form.value.producto_id && form.value.almacen_id) {
    cargarStockActual()
    loadAvailableSerials()
  }
}
</script>

<template>
  <Head title="Crear Ajuste de Inventario" />

  <div class="min-h-screen bg-[var(--ui-surface)]">
    <div class="w-full px-6 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">Crear Ajuste de Inventario</h1>
            <p class="text-slate-500 dark:text-slate-200 mt-1">Realiza ajustes manuales al stock de productos</p>
          </div>
          <button
            @click="cancel"
            class="inline-flex items-center px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Cancelar
          </button>
        </div>
      </div>

      <!-- Formulario -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-8">
        <form @submit.prevent="submit" class="space-y-6">
          <!-- Producto y Almacén -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="producto_id" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
                Producto *
              </label>
              <select
                id="producto_id"
                v-model="form.producto_id"
                required
                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all duration-200 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200"
                @change="watchProducto"
              >
                <option value="">Seleccionar producto</option>
                <option v-for="producto in productos" :key="producto.id" :value="producto.id">
                  {{ producto.nombre }} ({{ producto.codigo }})
                </option>
              </select>
            </div>

            <div>
              <label for="almacen_id" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
                Almacén *
              </label>
              <select
                id="almacen_id"
                v-model="form.almacen_id"
                required
                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all duration-200 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200"
                @change="watchAlmacen"
              >
                <option value="">Seleccionar almacén</option>
                <option v-for="almacen in almacenes" :key="almacen.id" :value="almacen.id">
                  {{ almacen.nombre }}
                </option>
              </select>
            </div>
          </div>

          <!-- Información de Stock Actual -->
          <div v-if="form.producto_id && form.almacen_id" class="bg-sky-50 dark:bg-sky-900/20 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800/30 dark:border-blue-700 rounded-xl p-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-sm font-medium text-sky-800 dark:text-sky-200 dark:text-blue-300">Stock Actual</h3>
                <div v-if="loadingStock" class="flex items-center mt-1">
                  <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 dark:border-blue-400"></div>
                  <span class="ml-2 text-sm text-blue-600 dark:text-blue-400">Cargando...</span>
                </div>
                <p v-else class="text-lg font-bold text-blue-900 dark:text-blue-100 mt-1">{{ stockActual }} unidades</p>
              </div>
            </div>
          </div>

          <!-- Tipo de Ajuste -->
          <div>
            <label for="tipo" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
              Tipo de Ajuste *
            </label>
            <div class="space-y-3">
              <div class="flex items-center">
                <input
                  id="incremento"
                  v-model="form.tipo"
                  type="radio"
                  value="incremento"
                  class="h-4 w-4 text-emerald-600 dark:text-slate-400 focus:ring-brand-500 border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-700"
                />
                <label for="incremento" class="ml-3 block text-sm font-medium text-slate-700 dark:text-slate-200">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium bg-emerald-100 dark:bg-slate-800/50 text-emerald-800 dark:text-emerald-200 dark:text-emerald-300">
                    Incremento
                  </span>
                  <span class="ml-2">Aumentar el stock disponible</span>
                </label>
              </div>
              <div class="flex items-center">
                <input
                  id="decremento"
                  v-model="form.tipo"
                  type="radio"
                  value="decremento"
                  class="h-4 w-4 text-rose-600 dark:text-rose-400 focus:ring-brand-500 border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-700"
                />
                <label for="decremento" class="ml-3 block text-sm font-medium text-slate-700 dark:text-slate-200">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium bg-rose-50 dark:bg-rose-900/20/40 text-rose-800 dark:text-rose-200 dark:text-rose-300">
                    Decremento
                  </span>
                  <span class="ml-2">Reducir el stock disponible</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Cantidad de Ajuste -->
          <div>
            <label for="cantidad_ajuste" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
              Cantidad a Ajustar *
            </label>
            <input
              id="cantidad_ajuste"
              v-model.number="form.cantidad_ajuste"
              type="number"
              min="1"
              required
              class="w-full px-4 py-3 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all duration-200 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200"
              placeholder="Ingresa la cantidad"
            />
          </div>

          <!-- Series (solo si el producto requiere serie) -->
          <div v-if="requiereSerie" class="bg-brand-50 dark:bg-brand-900/20 border border-brand-200 dark:border-brand-800/30 dark:border-brand-700 rounded-xl p-4">
            <h3 class="text-sm font-medium text-brand-800 dark:text-brand-200 dark:text-brand-300 mb-2">Captura de series</h3>
            <p class="text-xs text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-brand-200 mb-3">
              Este producto requiere capturar un número de serie por unidad.
              Ingresa exactamente {{ requiredSerials }} {{ requiredSerials === 1 ? 'serie' : 'series' }}.
            </p>
            <div v-if="loadingSerials" class="flex items-center justify-center py-4">
              <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-brand-600 dark:border-amber-400"></div>
              <span class="ml-2 text-sm text-brand-600 dark:text-amber-400">Cargando series disponibles...</span>
            </div>
            <div v-else-if="requiredSerials > 0" class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div v-for="(val, idx) in requiredSerials" :key="idx" class="flex items-center gap-2">
                <span class="text-xs text-slate-500 dark:text-slate-400 w-6 text-right">#{{ idx + 1 }}</span>
                
                <!-- Incremento: Input manual -->
                <input
                  v-if="form.tipo === 'incremento'"
                  type="text"
                  class="flex-1 px-3 py-2 border border-brand-300 dark:border-brand-600 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200"
                  v-model.trim="seriales[idx]"
                  placeholder="Nuevo número de serie"
                />

                <!-- Decremento: Selección -->
                <select
                  v-else
                  class="flex-1 px-3 py-2 border border-brand-300 dark:border-brand-600 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200"
                  v-model="seriales[idx]"
                >
                  <option value="">Seleccionar serie</option>
                  <option 
                    v-for="serie in availableSerials" 
                    :key="serie.id" 
                    :value="serie.numero_serie"
                    :disabled="seriales.includes(serie.numero_serie) && seriales[idx] !== serie.numero_serie"
                  >
                    {{ serie.numero_serie }}
                  </option>
                </select>
              </div>
            </div>
            <div v-else class="text-xs text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-amber-200">Indica primero la cantidad a ajustar.</div>
          </div>

          <!-- Vista Previa del Resultado -->
          <div v-if="form.producto_id && form.almacen_id && form.cantidad_ajuste" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4">
            <h3 class="text-sm font-medium text-slate-800 dark:text-slate-100 mb-3">Vista Previa del Ajuste</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
              <div>
                <span class="text-slate-500 dark:text-slate-200">Stock Actual:</span>
                <span class="ml-2 font-medium">{{ stockActual }}</span>
              </div>
              <div>
                <span class="text-slate-500 dark:text-slate-200">Ajuste:</span>
                <span :class="form.tipo === 'incremento' ? 'text-emerald-600 dark:text-slate-400' : 'text-rose-600 dark:text-rose-400'" class="ml-2 font-medium">
                  {{ form.tipo === 'incremento' ? '+' : '-' }}{{ form.cantidad_ajuste }}
                </span>
              </div>
              <div>
                <span class="text-slate-500 dark:text-slate-200">Stock Final:</span>
                <span :class="cantidadNueva < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-blue-600 dark:text-blue-400'" class="ml-2 font-bold">
                  {{ cantidadNueva }}
                </span>
              </div>
            </div>
            <div v-if="form.tipo === 'decremento' && cantidadNueva < 0" class="mt-3 p-3 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800/30 dark:border-rose-700 rounded-xl">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-rose-400 dark:text-rose-300" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-sm font-medium text-rose-800 dark:text-rose-200 dark:text-rose-300">
                    Stock insuficiente
                  </h3>
                  <div class="mt-2 text-sm text-rose-800 dark:text-rose-200 dark:text-rose-200 dark:text-rose-200">
                    <p>Este ajuste resultaría en stock negativo. El stock actual es de {{ stockActual }} unidades.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Motivo -->
          <div>
            <label for="motivo" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
              Motivo del Ajuste *
            </label>
            <select
              id="motivo"
              v-model="form.motivo"
              required
              class="w-full px-4 py-3 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all duration-200 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200"
            >
              <option value="">Seleccionar motivo</option>
              <option value="Conteo físico">Conteo físico</option>
              <option value="Producto dañado">Producto dañado</option>
              <option value="Producto perdido">Producto perdido</option>
              <option value="Producto encontrado">Producto encontrado</option>
              <option value="Error de captura">Error de captura</option>
              <option value="Devolución">Devolución</option>
              <option value="Corrección de inventario">Corrección de inventario</option>
              <option value="Otro">Otro</option>
            </select>
          </div>

          <!-- Observaciones -->
          <div>
            <label for="observaciones" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
              Observaciones
            </label>
            <textarea
              id="observaciones"
              v-model="form.observaciones"
              rows="3"
              class="w-full px-4 py-3 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all duration-200 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200"
              placeholder="Observaciones adicionales (opcional)"
            ></textarea>
          </div>

          <!-- Información de ayuda -->
          <div class="bg-orange-50 dark:bg-brand-900/20 border border-orange-200 dark:border-brand-700 rounded-xl p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-orange-400 dark:text-orange-300" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-orange-800 dark:text-orange-300">
                  Información importante
                </h3>
                <div class="mt-2 text-sm text-brand-700 dark:text-orange-200">
                  <ul class="list-disc pl-5 space-y-1">
                    <li>Los ajustes afectan directamente el stock disponible</li>
                    <li>Se registra automáticamente en el historial de movimientos</li>
                    <li>El motivo es obligatorio para trazabilidad</li>
                    <li>Los decrementos no pueden resultar en stock negativo</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>

          <!-- Botones de acción -->
          <div class="flex justify-end gap-4 pt-6 border-t border-slate-200 dark:border-slate-700">
            <button
              type="button"
              @click="cancel"
              :disabled="loading"
              class="px-6 py-3 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-600 focus:ring-2 focus:ring-brand-500 dark:focus:ring-brand-600 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="loading || !puedeGuardar"
              class="px-6 py-3 bg-gradient-to-r from-brand-600 to-brand-700 text-white font-semibold rounded-xl hover:from-brand-700 hover:to-orange-800 focus:ring-2 focus:ring-orange-300 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
            >
              <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
              </svg>
              {{ loading ? 'Procesando...' : 'Realizar Ajuste' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Animaciones para el loading */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>

