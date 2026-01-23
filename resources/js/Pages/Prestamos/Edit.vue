<!-- /resources/js/Pages/Prestamos/Edit.vue -->
<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import axios from 'axios'
import 'notyf/notyf.min.css'
import BuscarCliente from '@/Components/CreateComponents/BuscarCliente.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  prestamo: {
    type: Object,
    required: true
  },
  clientes: {
    type: Array,
    default: () => []
  },
  puede_editar: {
    type: Boolean,
    default: true
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

  // Verificar si el préstamo puede ser editado
  if (!props.puede_editar) {
    notyf.error('Este préstamo no puede ser editado porque ya tiene pagos registrados')
  }

  // Inicializar clienteSeleccionado con el cliente actual del préstamo
  if (props.prestamo.cliente_id) {
    const clienteActual = props.clientes.find(c => c.id === props.prestamo.cliente_id)
    clienteSeleccionado.value = clienteActual || null
  }
})

/* =========================
   Estado del formulario
========================= */
const form = ref({ ...props.prestamo })
const loading = ref(false)
const calculando = ref(false)
const puedeEditarTerminos = ref(props.puede_editar)
const clienteSeleccionado = ref(null)

/* =========================
   Funciones para manejo del cliente
========================= */
const onClienteSeleccionado = (cliente) => {
  clienteSeleccionado.value = cliente
  form.value.cliente_id = cliente ? cliente.id : null

  // Limpiar error cuando se selecciona un cliente
  if (cliente && errors.value.cliente_id) {
    delete errors.value.cliente_id
  }
}

const onCrearNuevoCliente = (nombreCliente) => {
  // Redirigir a crear cliente con el nombre pre-llenado
  router.visit('/clientes/create', {
    data: { nombre_razon_social: nombreCliente }
  })
}

/* =========================
   Cálculos financieros
========================= */
const calculos = ref({
  pago_periodico: props.prestamo.pago_periodico || 0,
  interes_total: props.prestamo.monto_interes_total || 0,
  total_pagar: props.prestamo.monto_total_pagar || 0,
})

const calcularPagos = async () => {
  if (!puedeEditarTerminos.value) return

  if (!form.value.monto_prestado || !form.value.numero_pagos || form.value.tasa_interes === undefined) {
    calculos.value = { pago_periodico: 0, interes_total: 0, total_pagar: 0 }
    return
  }

  calculando.value = true

  try {
    const response = await axios.post('/prestamos/calcular-pagos', {
      monto_prestado: form.value.monto_prestado,
      tasa_interes: form.value.tasa_interes,
      numero_pagos: form.value.numero_pagos,
      frecuencia_pago: form.value.frecuencia_pago,
    })
    
    // Con axios, response.data es el cuerpo de la respuesta
    const data = response.data
    if (data.success) {
      calculos.value = data.calculos
    }
  } catch (error) {
    console.error('Error calculando pagos:', error)
    if (error.response?.status === 419) {
       notyf.error('Tu sesión ha expirado. Por favor recarga la página.')
    } else {
       notyf.error('Error al calcular pagos: ' + (error.response?.data?.message || error.message))
    }
  } finally {
    calculando.value = false
  }
}

/* =========================
   Watchers para recálculo automático
========================= */
watch([() => form.value.monto_prestado, () => form.value.tasa_interes, () => form.value.numero_pagos], () => {
  if (puedeEditarTerminos.value) {
    calcularPagos()
  }
})

/* =========================
   Validación del formulario
========================= */
const errors = ref({})
const buscarClienteRef = ref(null)

const validateForm = () => {
  errors.value = {}

  if (!clienteSeleccionado.value) {
    errors.value.cliente_id = 'Debe seleccionar un cliente'
  }

  if (!form.value.monto_prestado || form.value.monto_prestado <= 0) {
    errors.value.monto_prestado = 'El monto debe ser mayor a cero'
  }

  if (form.value.tasa_interes < 0 || form.value.tasa_interes > 100) {
    errors.value.tasa_interes = 'La tasa de interés debe estar entre 0% y 100%'
  }

  if (!form.value.numero_pagos || form.value.numero_pagos < 1) {
    errors.value.numero_pagos = 'El número de pagos debe ser mayor a cero'
  }

  if (!form.value.fecha_inicio) {
    errors.value.fecha_inicio = 'La fecha de inicio es requerida'
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

  // Crear objeto solo con los campos necesarios
  const datosPrestamo = {
    cliente_id: form.value.cliente_id,
    monto_prestado: form.value.monto_prestado,
    tasa_interes_mensual: form.value.tasa_interes_mensual,
    numero_pagos: form.value.numero_pagos,
    frecuencia_pago: form.value.frecuencia_pago,
    fecha_inicio: form.value.fecha_inicio,
    fecha_primer_pago: form.value.fecha_primer_pago,
    descripcion: form.value.descripcion,
    notas: form.value.notas,
  }

  router.put(`/prestamos/${props.prestamo.id}`, datosPrestamo, {
    onStart: () => {
      notyf.success('Actualizando préstamo...')
    },
    onSuccess: () => {
      notyf.success('Préstamo actualizado correctamente')
      // Actualizar clienteSeleccionado con el cliente actual del préstamo
      const clienteActual = clientes.find(c => c.id === form.value.cliente_id)
      clienteSeleccionado.value = clienteActual || null
    },
    onError: (errors) => {
      console.error('Errores de validación:', errors)
      notyf.error('Error al actualizar el préstamo')
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

// Función para formatear moneda como el componente BuscarCliente
const formatearMonedaCliente = (valor) => {
  if (!valor) return '$0.00';
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN'
  }).format(valor);
}

const opcionesFrecuencia = [
  { value: 'semanal', label: 'Semanal' },
  { value: 'quincenal', label: 'Quincenal' },
  { value: 'mensual', label: 'Mensual' },
]

const opcionesNumeroPagos = Array.from({ length: 60 }, (_, i) => ({
  value: i + 1,
  label: `${i + 1} pago${i > 0 ? 's' : ''}`
}))

const getEstadoLabel = (estado) => {
  const labels = {
    'activo': 'Activo',
    'completado': 'Completado',
    'cancelado': 'Cancelado'
  }
  return labels[estado] || estado
}

const getEstadoColor = (estado) => {
  const colors = {
    'activo': 'bg-green-100 text-green-800',
    'completado': 'bg-blue-100 text-blue-800',
    'cancelado': 'bg-red-100 text-red-800'
  }
  return colors[estado] || 'bg-gray-100 text-gray-800 dark:text-gray-100'
}
</script>

<template>
  <Head title="Editar Préstamo" />

  <div class="prestamos-edit min-h-screen bg-white dark:bg-slate-900">
    <div class="w-full px-6 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">Editar Préstamo</h1>
            <p class="text-gray-600 dark:text-gray-300 mt-2">
              Modifique la información del préstamo
              <span v-if="!puedeEditarTerminos" class="text-orange-600 font-medium">
                (Algunos campos están bloqueados porque ya tiene pagos registrados)
              </span>
            </p>
          </div>
          <div class="flex items-center space-x-3">
            <span :class="['inline-flex items-center px-3 py-1 rounded-full text-sm font-medium', getEstadoColor(prestamo.estado)]">
              {{ getEstadoLabel(prestamo.estado) }}
            </span>
            <Link
              :href="`/prestamos/${prestamo.id}`"
              class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white dark:bg-slate-900 hover:bg-white dark:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200"
            >
              👁 Ver Detalles
            </Link>
            <Link
              href="/prestamos"
              class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white dark:bg-slate-900 hover:bg-white dark:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200"
            >
              ← Volver a Préstamos
            </Link>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Formulario principal -->
        <div class="lg:col-span-2">
          <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Información del Préstamo</h2>
            </div>

            <form @submit.prevent="submitForm" class="p-6 space-y-6">
              <!-- Cliente -->
              <div>
                <BuscarCliente
                  ref="buscarClienteRef"
                  :clientes="clientes"
                  :cliente-seleccionado="clienteSeleccionado"
                  label-busqueda="Seleccionar Cliente"
                  placeholder-busqueda="Buscar cliente por nombre, RFC o email..."
                  :requerido="true"
                  :mostrar-opcion-nuevo-cliente="true"
                  :mostrar-estado-cliente="true"
                  :mostrar-info-comercial="true"
                  titulo-cliente-seleccionado="Cliente Seleccionado para Préstamo"
                  mensaje-vacio="Selecciona un cliente para el préstamo"
                  submensaje-vacio="Busca y selecciona un cliente existente o crea uno nuevo"
                  @cliente-seleccionado="onClienteSeleccionado"
                  @crear-nuevo-cliente="onCrearNuevoCliente"
                />
                <p v-if="errors.cliente_id" class="mt-1 text-sm text-red-600">{{ errors.cliente_id }}</p>
              </div>

              <!-- Información de progreso (solo lectura) -->
              <div v-if="prestamo.pagos_realizados > 0" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-blue-900 mb-2">Progreso del Préstamo</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                  <div>
                    <span class="text-blue-700">Pagos realizados:</span>
                    <span class="font-semibold text-blue-900 ml-2">{{ prestamo.pagos_realizados }} / {{ prestamo.numero_pagos }}</span>
                  </div>
                  <div>
                    <span class="text-blue-700">Monto pagado:</span>
                    <span class="font-semibold text-blue-900 ml-2">${{ formatearMoneda(prestamo.monto_pagado) }}</span>
                  </div>
                </div>
              </div>

              <!-- Grid de información financiera -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Monto prestado -->
                <div>
                  <label for="monto_prestado" class="block text-sm font-medium text-gray-700 mb-2">
                    Monto a Prestar *
                    <span v-if="!puedeEditarTerminos" class="text-orange-600 text-xs">(Bloqueado)</span>
                  </label>
                  <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <span class="text-gray-500 dark:text-gray-400 sm:text-sm">$</span>
                    </div>
                    <input
                      id="monto_prestado"
                      v-model.number="form.monto_prestado"
                      type="number"
                      step="0.01"
                      min="0"
                      placeholder="0.00"
                      :disabled="!puedeEditarTerminos"
                      class="block w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                      :class="{ 'border-red-300': errors.monto_prestado }"
                    />
                  </div>
                  <p v-if="errors.monto_prestado" class="mt-1 text-sm text-red-600">{{ errors.monto_prestado }}</p>
                </div>

                <!-- Tasa de interés -->
                <div>
                  <label for="tasa_interes" class="block text-sm font-medium text-gray-700 mb-2">
                    Tasa de Interés (%) *
                    <span v-if="!puedeEditarTerminos" class="text-orange-600 text-xs">(Bloqueado)</span>
                  </label>
                  <div class="relative">
                    <input
                      id="tasa_interes"
                      v-model.number="form.tasa_interes"
                      type="number"
                      step="0.01"
                      min="0"
                      max="100"
                      placeholder="0.00"
                      :disabled="!puedeEditarTerminos"
                      class="block w-full pr-8 pl-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                      :class="{ 'border-red-300': errors.tasa_interes }"
                    />
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                      <span class="text-gray-500 dark:text-gray-400 sm:text-sm">%</span>
                    </div>
                  </div>
                  <p v-if="errors.tasa_interes" class="mt-1 text-sm text-red-600">{{ errors.tasa_interes }}</p>
                </div>

                <!-- Número de pagos -->
                <div>
                  <label for="numero_pagos" class="block text-sm font-medium text-gray-700 mb-2">
                    Número de Pagos *
                    <span v-if="!puedeEditarTerminos" class="text-orange-600 text-xs">(Bloqueado)</span>
                  </label>
                  <select
                    id="numero_pagos"
                    v-model="form.numero_pagos"
                    :disabled="!puedeEditarTerminos"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                    :class="{ 'border-red-300': errors.numero_pagos }"
                  >
                    <option v-for="opcion in opcionesNumeroPagos" :key="opcion.value" :value="opcion.value">
                      {{ opcion.label }}
                    </option>
                  </select>
                  <p v-if="errors.numero_pagos" class="mt-1 text-sm text-red-600">{{ errors.numero_pagos }}</p>
                </div>

                <!-- Frecuencia de pago -->
                <div>
                  <label for="frecuencia_pago" class="block text-sm font-medium text-gray-700 mb-2">
                    Frecuencia de Pago *
                    <span v-if="!puedeEditarTerminos" class="text-orange-600 text-xs">(Bloqueado)</span>
                  </label>
                  <select
                    id="frecuencia_pago"
                    v-model="form.frecuencia_pago"
                    :disabled="!puedeEditarTerminos"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                  >
                    <option v-for="opcion in opcionesFrecuencia" :key="opcion.value" :value="opcion.value">
                      {{ opcion.label }}
                    </option>
                  </select>
                </div>

                <!-- Fecha de inicio -->
                <div>
                  <label for="fecha_inicio" class="block text-sm font-medium text-gray-700 mb-2">
                    Fecha de Inicio *
                  </label>
                  <input
                    id="fecha_inicio"
                    v-model="form.fecha_inicio"
                    type="date"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                    :class="{ 'border-red-300': errors.fecha_inicio }"
                  />
                  <p v-if="errors.fecha_inicio" class="mt-1 text-sm text-red-600">{{ errors.fecha_inicio }}</p>
                </div>
              </div>

              <!-- Descripción -->
              <div>
                <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-2">
                  Descripción
                </label>
                <textarea
                  id="descripcion"
                  v-model="form.descripcion"
                  rows="3"
                  placeholder="Descripción del préstamo (opcional)"
                  class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                ></textarea>
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
                  placeholder="Notas adicionales (opcional)"
                  class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                ></textarea>
              </div>

              <!-- Botones de acción -->
              <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-slate-800">
                <Link
                  :href="`/prestamos/${prestamo.id}`"
                  class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white dark:bg-slate-900 hover:bg-white dark:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200"
                >
                  ❌ Cancelar
                </Link>
                <button
                  type="submit"
                  :disabled="loading"
                  class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
                >
                  <span v-if="loading" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Actualizando...
                  </span>
                  <span v-else>Actualizar Préstamo</span>
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Panel de cálculos e información -->
        <div class="lg:col-span-1">
          <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-100 overflow-hidden sticky top-8">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ puedeEditarTerminos ? 'Cálculo de Pagos' : 'Información Actual' }}
              </h3>
              <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                {{ puedeEditarTerminos ? 'Se actualiza automáticamente' : 'Valores actuales del préstamo' }}
              </p>
            </div>

            <div class="p-6">
              <div v-if="puedeEditarTerminos && calculando" class="text-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-500 mx-auto"></div>
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-2">Calculando...</p>
              </div>

              <div v-else-if="puedeEditarTerminos && form.monto_prestado > 0 && form.numero_pagos > 0">
                <div class="space-y-4">
                  <!-- Pago periódico -->
                  <div class="flex justify-between items-center py-3 border-b border-gray-100">
                    <span class="text-sm font-medium text-gray-700">Pago {{ form.frecuencia_pago }}:</span>
                    <span class="text-lg font-bold text-green-600">
                      ${{ formatearMoneda(calculos.pago_periodico) }}
                    </span>
                  </div>

                  <!-- Interés total -->
                  <div class="flex justify-between items-center py-3 border-b border-gray-100">
                    <span class="text-sm font-medium text-gray-700">Interés Total:</span>
                    <span class="text-lg font-semibold text-blue-600">
                      ${{ formatearMoneda(calculos.interes_total) }}
                    </span>
                  </div>

                  <!-- Total a pagar -->
                  <div class="flex justify-between items-center py-3 border-b-2 border-gray-200 dark:border-slate-800">
                    <span class="text-sm font-medium text-gray-700">Total a Pagar:</span>
                    <span class="text-xl font-bold text-gray-900 dark:text-white">
                      ${{ formatearMoneda(calculos.total_pagar) }}
                    </span>
                  </div>
                </div>
              </div>

              <div v-else class="space-y-4">
                <!-- Información actual del préstamo -->
                <div class="flex justify-between items-center py-3 border-b border-gray-100">
                  <span class="text-sm font-medium text-gray-700">Pago {{ prestamo.frecuencia_texto?.toLowerCase() }}:</span>
                  <span class="text-lg font-bold text-green-600">
                    ${{ formatearMoneda(prestamo.pago_periodico) }}
                  </span>
                </div>

                <div class="flex justify-between items-center py-3 border-b border-gray-100">
                  <span class="text-sm font-medium text-gray-700">Interés Total:</span>
                  <span class="text-lg font-semibold text-blue-600">
                    ${{ formatearMoneda(prestamo.monto_interes_total) }}
                  </span>
                </div>

                <div class="flex justify-between items-center py-3 border-b-2 border-gray-200 dark:border-slate-800">
                  <span class="text-sm font-medium text-gray-700">Total a Pagar:</span>
                  <span class="text-xl font-bold text-gray-900 dark:text-white">
                    ${{ formatearMoneda(prestamo.monto_total_pagar) }}
                  </span>
                </div>

                <!-- Información adicional -->
                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 mt-4">
                  <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Estado del Préstamo</h4>
                  <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                    <div class="flex justify-between">
                      <span>Pagos realizados:</span>
                      <span>{{ prestamo.pagos_realizados }} / {{ prestamo.numero_pagos }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span>Monto pagado:</span>
                      <span>${{ formatearMoneda(prestamo.monto_pagado) }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span>Monto pendiente:</span>
                      <span>${{ formatearMoneda(prestamo.monto_pendiente) }}</span>
                    </div>
                  </div>

                  <!-- Información del cálculo original -->
                  <div class="mt-3 pt-3 border-t border-gray-200 dark:border-slate-800">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                      <strong>Configuración original:</strong>
                    </div>
                    <div class="space-y-1 text-xs text-gray-500 dark:text-gray-400">
                      <div class="flex justify-between">
                        <span>Tasa anual:</span>
                        <span>{{ prestamo.tasa_interes }}%</span>
                      </div>
                      <div class="flex justify-between">
                        <span>Frecuencia:</span>
                        <span>{{ prestamo.frecuencia_texto }}</span>
                      </div>
                      <div class="flex justify-between">
                        <span>Tipo de cálculo:</span>
                        <span>Amortización francesa</span>
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
  </div>
</template>

<style scoped>
.prestamos-edit {
  min-height: 100vh;
}
</style>

