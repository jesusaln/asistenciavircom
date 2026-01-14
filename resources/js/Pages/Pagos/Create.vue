<!-- /resources/js/Pages/Pagos/Create.vue -->
<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

const props = defineProps({
  prestamo: {
    type: Object,
    required: true
  },
  pagos_pendientes: {
    type: Array,
    default: () => []
  },
  cuentasBancarias: {
    type: Array,
    default: () => []
  }
})

/* =========================
   Configuración de notificaciones
========================= */
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

/* =========================
   Estado del formulario
========================= */
const form = ref({
  prestamo_id: props.prestamo.id,
  pago_id: null,
  monto_pagado: 0,
  fecha_pago: new Date().toISOString().split('T')[0],
  metodo_pago: 'efectivo',
  cuenta_bancaria_id: null,
  referencia: '',
  notas: '',
})

const loading = ref(false)

/* =========================
   Validación del formulario
========================= */
const errors = ref({})

const validateForm = () => {
  errors.value = {}

  if (!form.value.pago_id) {
    errors.value.pago_id = 'Debe seleccionar un pago'
  }

  if (!form.value.monto_pagado || form.value.monto_pagado <= 0) {
    errors.value.monto_pagado = 'El monto debe ser mayor a cero'
  }

  // Si hay un pago seleccionado, verificar que no exceda el monto pendiente
  if (form.value.pago_id && form.value.monto_pagado) {
    const pagoSeleccionado = props.pagos_pendientes.find(p => p.id == form.value.pago_id)
    if (pagoSeleccionado) {
      const montoPendiente = pagoSeleccionado.monto_programado - pagoSeleccionado.monto_pagado
      if (form.value.monto_pagado > montoPendiente) {
        errors.value.monto_pagado = `El monto no puede exceder el pendiente: $${formatearMoneda(montoPendiente)}`
      }
    }
  }

  if (!form.value.fecha_pago) {
    errors.value.fecha_pago = 'La fecha de pago es requerida'
  }

  return Object.keys(errors.value).length === 0
}

/* =========================
   Envío del formulario
========================= */
const submitForm = () => {
  if (!validateForm()) {
    notyf.error('Por favor corrija los errores del formulario')
    return
  }

  loading.value = true

  router.post('/pagos', form.value, {
    onStart: () => {
      notyf.success('Registrando pago...')
    },
    onSuccess: () => {
      notyf.success('Pago registrado correctamente')
    },
    onError: (errors) => {
      console.error('Errores de validación:', errors)
      notyf.error('Error al registrar el pago')
    },
    onFinish: () => {
      loading.value = false
    }
  })
}

/* =========================
   Funciones auxiliares
========================= */
const formatearMoneda = (num) => {
  const value = parseFloat(num);
  const safe = Number.isFinite(value) ? value : 0;
  return new Intl.NumberFormat('es-MX', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(safe);
}

const formatearFecha = (date) => {
  if (!date) return 'Fecha no disponible';
  try {
    const time = new Date(date).getTime();
    if (Number.isNaN(time)) return 'Fecha inválida';
    return new Date(time).toLocaleDateString('es-MX', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    });
  } catch {
    return 'Fecha inválida';
  }
}

// Propiedades computadas para el formulario
const montoMaximo = computed(() => {
  if (!form.value.pago_id) return null;
  const pagoSeleccionado = props.pagos_pendientes.find(p => p.id == form.value.pago_id)
  return pagoSeleccionado ? pagoSeleccionado.monto_programado - pagoSeleccionado.monto_pagado : null
})

const placeholderMonto = computed(() => {
  if (!form.value.pago_id) return '0.00'
  const pagoSeleccionado = props.pagos_pendientes.find(p => p.id == form.value.pago_id)
  if (pagoSeleccionado) {
    const pendiente = pagoSeleccionado.monto_programado - pagoSeleccionado.monto_pagado
    return `Máximo: ${formatearMoneda(pendiente)}`
  }
  return '0.00'
})

const opcionesMetodoPago = [
  { value: 'efectivo', label: 'Efectivo' },
  { value: 'transferencia', label: 'Transferencia Bancaria' },
  { value: 'tarjeta_debito', label: 'Tarjeta de Débito' },
  { value: 'tarjeta_credito', label: 'Tarjeta de Crédito' },
  { value: 'cheque', label: 'Cheque' },
  { value: 'otro', label: 'Otro' },
]

const getEstadoLabel = (estado) => {
  const labels = {
    'pendiente': 'Pendiente',
    'pagado': 'Pagado',
    'atrasado': 'Atrasado',
    'parcial': 'Pago Parcial'
  }
  return labels[estado] || estado
}

const getEstadoColor = (estado) => {
   const colors = {
     'pendiente': 'bg-yellow-100 text-yellow-800',
     'pagado': 'bg-green-100 text-green-800',
     'atrasado': 'bg-red-100 text-red-800',
     'parcial': 'bg-orange-100 text-orange-800'
   }
   return colors[estado] || 'bg-gray-100 text-gray-800'
 }

/* =========================
    Watcher para autocompletar monto
 ========================= */
watch(
  () => form.value.pago_id,
  (newPagoId, oldPagoId) => {
    console.log('Cambio en pago_id:', oldPagoId, '->', newPagoId)

    if (newPagoId) {
      const pagoSeleccionado = props.pagos_pendientes.find(p => p.id == newPagoId)
      if (pagoSeleccionado) {
        const montoPendiente = pagoSeleccionado.monto_programado - pagoSeleccionado.monto_pagado
        form.value.monto_pagado = montoPendiente
        console.log('Monto autocompletado:', montoPendiente)

        // Mostrar notificación informativa
        notyf.success(`Monto autocompletado: $${formatearMoneda(montoPendiente)}`)
      }
    } else {
      // Si se deselecciona el pago, limpiar el monto
      form.value.monto_pagado = 0
    }

    // Limpiar errores cuando cambia la selección
    if (errors.value.monto_pagado) {
      delete errors.value.monto_pagado
    }
  },
  { immediate: false }
)
</script>

<template>
  <Head title="Registrar Pago de Préstamo" />

  <div class="pagos-create min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto px-6 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Registrar Pago de Préstamo</h1>
            <p class="text-gray-600 mt-2">Registra el pago recibido de un préstamo</p>
          </div>
          <div class="flex items-center space-x-3">
            <Link
              :href="`/prestamos/${prestamo.id}`"
              class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
            >
              👁 Ver Préstamo
            </Link>
            <Link
              href="/pagos"
              class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
            >
              ← Volver a Pagos
            </Link>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Formulario principal -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
              <h2 class="text-lg font-semibold text-gray-900">Información del Pago</h2>
            </div>

            <form @submit.prevent="submitForm" class="p-6 space-y-6">
              <!-- Información del préstamo -->
              <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-blue-900 mb-2">Información del Préstamo</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                  <div>
                    <span class="text-blue-700">Cliente:</span>
                    <span class="font-semibold text-blue-900 ml-2">{{ prestamo.cliente?.nombre_razon_social }}</span>
                  </div>
                  <div>
                    <span class="text-blue-700">Monto del préstamo:</span>
                    <span class="font-semibold text-blue-900 ml-2">${{ formatearMoneda(prestamo.monto_prestado) }}</span>
                  </div>
                  <div>
                    <span class="text-blue-700">Estado del préstamo:</span>
                    <span :class="['font-semibold ml-2', getEstadoColor(prestamo.estado)]">
                      {{ getEstadoLabel(prestamo.estado) }}
                    </span>
                  </div>
                  <div>
                    <span class="text-blue-700">Progreso:</span>
                    <span class="font-semibold text-blue-900 ml-2">{{ prestamo.pagos_realizados }} / {{ prestamo.numero_pagos }} pagos</span>
                  </div>
                </div>
              </div>

              <!-- Información del pago seleccionado -->
              <div v-if="form.pago_id" class="bg-green-50 border border-green-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-green-900 mb-2">Información del Pago Seleccionado</h3>
                <div v-for="pago in pagos_pendientes" :key="pago.id">
                  <div v-if="pago.id == form.pago_id" class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                      <span class="text-green-700">Número de pago:</span>
                      <span class="font-semibold text-green-900 ml-2">#{{ pago.numero_pago }}</span>
                    </div>
                    <div>
                      <span class="text-green-700">Fecha programada:</span>
                      <span class="font-semibold text-green-900 ml-2">{{ formatearFecha(pago.fecha_programada) }}</span>
                    </div>
                    <div>
                      <span class="text-green-700">Monto programado:</span>
                      <span class="font-semibold text-green-900 ml-2">${{ formatearMoneda(pago.monto_programado) }}</span>
                    </div>
                    <div>
                      <span class="text-green-700">Monto pagado actual:</span>
                      <span class="font-semibold text-green-900 ml-2">${{ formatearMoneda(pago.monto_pagado) }}</span>
                    </div>
                    <div>
                      <span class="text-green-700">Estado actual:</span>
                      <span :class="['font-semibold ml-2', getEstadoColor(pago.estado)]">
                        {{ getEstadoLabel(pago.estado) }}
                      </span>
                    </div>
                    <div>
                      <span class="text-green-700">Monto pendiente:</span>
                      <span class="font-semibold text-orange-600 ml-2">${{ formatearMoneda(pago.monto_programado - pago.monto_pagado) }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Selección del pago -->
              <div>
                <label for="pago_id" class="block text-sm font-medium text-gray-700 mb-2">
                  Seleccionar Pago a Registrar *
                </label>
                <select
                  id="pago_id"
                  v-model="form.pago_id"
                  class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                  :class="{ 'border-red-300': errors.pago_id }"
                >
                  <option value="">Seleccionar pago...</option>
                  <option v-for="pago in pagos_pendientes" :key="pago.id" :value="pago.id">
                    Pago #{{ pago.numero_pago }} - ${{ formatearMoneda(pago.monto_programado) }} ({{ formatearFecha(pago.fecha_programada) }})
                  </option>
                </select>
                <p v-if="errors.pago_id" class="mt-1 text-sm text-red-600">{{ errors.pago_id }}</p>
              </div>

              <!-- Grid de información del pago -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Monto pagado -->
                <div>
                  <label for="monto_pagado" class="block text-sm font-medium text-gray-700 mb-2">
                    Monto Pagado *
                    <span v-if="form.pago_id" class="text-xs text-blue-600 font-normal">(Autocompletado automáticamente)</span>
                  </label>
                  <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <span class="text-gray-500 sm:text-sm">$</span>
                    </div>
                    <input
                      id="monto_pagado"
                      v-model.number="form.monto_pagado"
                      type="number"
                      step="0.01"
                      min="0"
                      :max="montoMaximo"
                      :placeholder="placeholderMonto"
                      class="block w-full pl-8 pr-3 py-2 border rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                      :class="[
                        form.pago_id ? 'border-green-300 bg-green-50' : 'border-gray-300',
                        errors.monto_pagado && 'border-red-300'
                      ]"
                    />
                    <div v-if="form.pago_id" class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                      <span class="text-green-600 text-xs">✓</span>
                    </div>
                  </div>
                  <p v-if="errors.monto_pagado" class="mt-1 text-sm text-red-600">{{ errors.monto_pagado }}</p>
                  <p v-if="form.pago_id && !errors.monto_pagado" class="mt-1 text-xs text-green-600">
                    ✓ Monto autocompletado con el pago pendiente
                  </p>
                </div>

                <!-- Fecha de pago -->
                <div>
                  <label for="fecha_pago" class="block text-sm font-medium text-gray-700 mb-2">
                    Fecha de Pago *
                  </label>
                  <input
                    id="fecha_pago"
                    v-model="form.fecha_pago"
                    type="date"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    :class="{ 'border-red-300': errors.fecha_pago }"
                  />
                  <p v-if="errors.fecha_pago" class="mt-1 text-sm text-red-600">{{ errors.fecha_pago }}</p>
                </div>

                <!-- Método de pago -->
                <div>
                  <label for="metodo_pago" class="block text-sm font-medium text-gray-700 mb-2">
                    Método de Pago
                  </label>
                  <select
                    id="metodo_pago"
                    v-model="form.metodo_pago"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                  >
                    <option v-for="opcion in opcionesMetodoPago" :key="opcion.value" :value="opcion.value">
                      {{ opcion.label }}
                    </option>
                  </select>
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
                    placeholder="Número de referencia, folio, etc."
                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>
              </div>

              <!-- Cuenta Bancaria -->
              <div v-if="cuentasBancarias && cuentasBancarias.length > 0">
                <label for="cuenta_bancaria_id" class="block text-sm font-medium text-gray-700 mb-2">
                  Cuenta Bancaria de Destino
                  <span class="text-xs text-gray-500 font-normal">(Opcional - para registrar movimiento bancario)</span>
                </label>
                <select
                  id="cuenta_bancaria_id"
                  v-model="form.cuenta_bancaria_id"
                  class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                >
                  <option :value="null">Sin cuenta bancaria</option>
                  <option v-for="cuenta in cuentasBancarias" :key="cuenta.id" :value="cuenta.id">
                    {{ cuenta.banco }} - {{ cuenta.nombre }}
                  </option>
                </select>
                <p class="mt-1 text-xs text-gray-500">
                  Si selecciona una cuenta, el pago se registrará como depósito en esa cuenta bancaria.
                </p>
              </div>

              <!-- Notas -->
              <div>
                <label for="notas" class="block text-sm font-medium text-gray-700 mb-2">
                  Notas Adicionales
                </label>
                <textarea
                  id="notas"
                  v-model="form.notas"
                  rows="3"
                  placeholder="Notas adicionales sobre el pago (opcional)"
                  class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                ></textarea>
              </div>

              <!-- Botones de acción -->
              <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200">
                <Link
                  href="/pagos"
                  class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
                >
                  ❌ Cancelar
                </Link>
                <button
                  type="submit"
                  :disabled="loading"
                  class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
                >
                  <span v-if="loading" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Registrando...
                  </span>
                  <span v-else>💳 Registrar Pago</span>
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Panel de información -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden sticky top-8">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900">Información del Préstamo</h3>
            </div>

            <div class="p-6">
              <div class="space-y-4">
                <!-- Información del préstamo -->
                <div class="bg-gray-50 rounded-lg p-4">
                  <h4 class="text-sm font-medium text-gray-900 mb-3">Resumen del Préstamo</h4>
                  <div class="space-y-2 text-sm text-gray-600">
                    <div class="flex justify-between">
                      <span>Capital:</span>
                      <span class="font-semibold">${{ formatearMoneda(prestamo.monto_prestado) }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span>Tasa mensual:</span>
                      <span class="font-semibold">{{ prestamo.tasa_interes_mensual }}%</span>
                    </div>
                    <div class="flex justify-between">
                      <span>Pago mensual:</span>
                      <span class="font-semibold">${{ formatearMoneda(prestamo.pago_periodico) }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span>Total a pagar:</span>
                      <span class="font-semibold">${{ formatearMoneda(prestamo.monto_total_pagar) }}</span>
                    </div>
                  </div>
                </div>

                <!-- Progreso del préstamo -->
                <div class="bg-gray-50 rounded-lg p-4">
                  <h4 class="text-sm font-medium text-gray-900 mb-3">Progreso del Préstamo</h4>
                  <div class="space-y-2 text-sm text-gray-600">
                    <div class="flex justify-between">
                      <span>Pagos realizados:</span>
                      <span class="font-semibold">{{ prestamo.pagos_realizados }} / {{ prestamo.numero_pagos }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span>Monto pagado:</span>
                      <span class="font-semibold text-green-600">${{ formatearMoneda(prestamo.monto_pagado) }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span>Monto pendiente:</span>
                      <span class="font-semibold text-orange-600">${{ formatearMoneda(prestamo.monto_pendiente) }}</span>
                    </div>
                  </div>
                </div>

                <!-- Próximo pago -->
                <div v-if="pagos_pendientes.length > 0" class="bg-blue-50 rounded-lg p-4">
                  <h4 class="text-sm font-medium text-blue-900 mb-3">Próximo Pago Pendiente</h4>
                  <div class="space-y-2 text-sm text-blue-800">
                    <div class="flex justify-between">
                      <span>Pago #{{ pagos_pendientes[0].numero_pago }}</span>
                      <span class="font-semibold">${{ formatearMoneda(pagos_pendientes[0].monto_programado) }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span>Fecha programada:</span>
                      <span class="font-semibold">{{ formatearFecha(pagos_pendientes[0].fecha_programada) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import "tailwindcss" reference;
.pagos-create {
  min-height: 100vh;
  background-color: #f9fafb;
}
</style>

