<!-- /resources/js/Pages/MovimientosManuales/Create.vue -->
<script setup>
import { ref, onMounted } from 'vue'
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
  tipo: 'entrada',
  cantidad: '',
  costo_unitario: '',
  categoria: '',
  motivo: '',
  observaciones: '',
  referencia: '',
})

// Estados
const loading = ref(false)

// Métodos
const submit = () => {
  loading.value = true

  router.post(route('movimientos-manuales.store'), form.value, {
    onSuccess: () => {
      notyf.success('Movimiento manual registrado correctamente')
      router.visit(route('movimientos-manuales.index'))
    },
    onError: (errors) => {
      console.error('Errores de validación:', errors)
      notyf.error('Error al registrar el movimiento manual')
    },
    onFinish: () => {
      loading.value = false
    }
  })
}

const cancel = () => {
  router.visit(route('movimientos-manuales.index'))
}
</script>

<template>
  <Head title="Crear Movimiento Manual" />

  <div class="min-h-screen bg-white">
    <div class="w-full px-6 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Crear Movimiento Manual</h1>
            <p class="text-gray-600 mt-1">Registrar entrada o salida manual de inventario</p>
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

      <!-- Formulario -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
        <form @submit.prevent="submit" class="space-y-6">
          <!-- Producto y Almacén -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="producto_id" class="block text-sm font-medium text-gray-700 mb-2">
                Producto *
              </label>
              <select
                id="producto_id"
                v-model="form.producto_id"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
              >
                <option value="">Seleccionar producto</option>
                <option v-for="producto in productos" :key="producto.id" :value="producto.id">
                  {{ producto.nombre }} ({{ producto.codigo }})
                </option>
              </select>
            </div>

            <div>
              <label for="almacen_id" class="block text-sm font-medium text-gray-700 mb-2">
                Almacén *
              </label>
              <select
                id="almacen_id"
                v-model="form.almacen_id"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
              >
                <option value="">Seleccionar almacén</option>
                <option v-for="almacen in almacenes" :key="almacen.id" :value="almacen.id">
                  {{ almacen.nombre }}
                </option>
              </select>
            </div>
          </div>

          <!-- Tipo de Movimiento -->
          <div>
            <label for="tipo" class="block text-sm font-medium text-gray-700 mb-2">
              Tipo de Movimiento *
            </label>
            <div class="space-y-3">
              <div class="flex items-center">
                <input
                  id="entrada"
                  v-model="form.tipo"
                  type="radio"
                  value="entrada"
                  class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300"
                />
                <label for="entrada" class="ml-3 block text-sm font-medium text-gray-700">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    Entrada
                  </span>
                  <span class="ml-2">Aumentar el stock disponible</span>
                </label>
              </div>
              <div class="flex items-center">
                <input
                  id="salida"
                  v-model="form.tipo"
                  type="radio"
                  value="salida"
                  class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300"
                />
                <label for="salida" class="ml-3 block text-sm font-medium text-gray-700">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    Salida
                  </span>
                  <span class="ml-2">Reducir el stock disponible</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Cantidad y Costo -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="cantidad" class="block text-sm font-medium text-gray-700 mb-2">
                Cantidad *
              </label>
              <input
                id="cantidad"
                v-model.number="form.cantidad"
                type="number"
                min="1"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                placeholder="Cantidad del movimiento"
              />
            </div>

            <div>
              <label for="costo_unitario" class="block text-sm font-medium text-gray-700 mb-2">
                Costo Unitario
              </label>
              <input
                id="costo_unitario"
                v-model.number="form.costo_unitario"
                type="number"
                min="0"
                step="0.01"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                placeholder="Costo por unidad (opcional)"
              />
            </div>
          </div>

          <!-- Categoría -->
          <div>
            <label for="categoria" class="block text-sm font-medium text-gray-700 mb-2">
              Categoría
            </label>
            <select
              id="categoria"
              v-model="form.categoria"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
            >
              <option value="">Seleccionar categoría</option>
              <option value="recepcion">Recepción</option>
              <option value="donacion">Donación</option>
              <option value="merma">Merma</option>
              <option value="consumo">Consumo interno</option>
              <option value="devolucion">Devolución</option>
              <option value="prestamo">Préstamo</option>
              <option value="otro">Otro</option>
            </select>
          </div>

          <!-- Motivo -->
          <div>
            <label for="motivo" class="block text-sm font-medium text-gray-700 mb-2">
              Motivo
            </label>
            <input
              id="motivo"
              v-model="form.motivo"
              type="text"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
              placeholder="Motivo del movimiento"
            />
          </div>

          <!-- Referencia -->
          <div>
            <label for="referencia" class="block text-sm font-medium text-gray-700 mb-2">
              Referencia
            </label>
            <input
              id="referencia"
              v-model="form.referencia"
              type="text"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
              placeholder="Número de documento, factura, etc."
            />
          </div>

          <!-- Observaciones -->
          <div>
            <label for="observaciones" class="block text-sm font-medium text-gray-700 mb-2">
              Observaciones
            </label>
            <textarea
              id="observaciones"
              v-model="form.observaciones"
              rows="3"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
              placeholder="Observaciones adicionales"
            ></textarea>
          </div>

          <!-- Información de ayuda -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">
                  Información importante
                </h3>
                <div class="mt-2 text-sm text-blue-700">
                  <ul class="list-disc pl-5 space-y-1">
                    <li>Los movimientos afectan directamente el stock disponible</li>
                    <li>Se registra automáticamente en el historial de movimientos</li>
                    <li>Las salidas requieren stock suficiente disponible</li>
                    <li>El costo unitario es opcional pero recomendado para valuación</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>

          <!-- Botones de acción -->
          <div class="flex justify-end gap-4 pt-6 border-t border-gray-200">
            <button
              type="button"
              @click="cancel"
              :disabled="loading"
              class="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 focus:ring-4 focus:ring-gray-300 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white font-semibold rounded-lg hover:from-green-700 hover:to-green-800 focus:ring-4 focus:ring-green-300 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
            >
              <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              {{ loading ? 'Registrando...' : 'Registrar Movimiento' }}
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

