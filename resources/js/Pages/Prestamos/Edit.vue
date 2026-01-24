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

  if (!form.value.monto_prestado || !form.value.numero_pagos || form.value.tasa_interes_mensual === undefined) {
    calculos.value = { pago_periodico: 0, interes_total: 0, total_pagar: 0 }
    return
  }

  calculando.value = true

  try {
    const response = await axios.post('/prestamos/calcular-pagos', {
      monto_prestado: form.value.monto_prestado,
      tasa_interes_mensual: form.value.tasa_interes_mensual,
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

// Debounce para evitar saturar el servidor
let timeoutCalculo = null
const calcularPagosDebounced = () => {
  if (timeoutCalculo) clearTimeout(timeoutCalculo)
  timeoutCalculo = setTimeout(() => {
    calcularPagos()
  }, 400)
}

/* =========================
   Watchers para recálculo automático
========================= */
watch(
  [() => form.value.monto_prestado, () => form.value.tasa_interes_mensual, () => form.value.numero_pagos, () => form.value.frecuencia_pago],
  () => {
    if (puedeEditarTerminos.value) {
      calcularPagosDebounced()
    }
  }
)

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

  if (form.value.tasa_interes_mensual < 0 || form.value.tasa_interes_mensual > 100) {
    errors.value.tasa_interes_mensual = 'La tasa de interés debe estar entre 0% y 100%'
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
      const clienteActual = props.clientes.find(c => c.id === form.value.cliente_id)
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

  <div class="prestamos-edit min-h-screen bg-white dark:bg-slate-950 transition-colors duration-300">
    <div class="w-full px-6 py-8">
      <!-- Header -->
      <div class="mb-10">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-slate-100 tracking-tight">Editar Préstamo</h1>
            <p class="text-gray-600 dark:text-slate-400 mt-2 flex items-center gap-2">
              <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
              Gestión y re-estructuración de crédito
              <span v-if="!puedeEditarTerminos" class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-orange-100 text-orange-800 dark:bg-orange-500/10 dark:text-orange-400 ring-1 ring-inset ring-orange-500/20">
                Términos Bloqueados (Tiene Pagos)
              </span>
            </p>
          </div>
          <div class="flex items-center space-x-4">
            <span :class="['inline-flex items-center px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-wider shadow-sm', getEstadoColor(prestamo.estado)]">
              {{ getEstadoLabel(prestamo.estado) }}
            </span>
             <Link
              :href="`/prestamos/${prestamo.id}`"
              class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-slate-800 text-sm font-bold rounded-xl text-gray-700 dark:text-slate-300 bg-white dark:bg-slate-900 hover:bg-gray-50 dark:hover:bg-slate-800 transition-all duration-200"
            >
              👁 Ver Detalles
            </Link>
            <Link
              href="/prestamos"
              class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-slate-800 text-sm font-bold rounded-xl text-gray-700 dark:text-slate-300 bg-white dark:bg-slate-900 hover:bg-gray-50 dark:hover:bg-slate-800 transition-all duration-200"
            >
              ← Volver
            </Link>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Formulario principal -->
        <div class="lg:col-span-2 space-y-8">
          <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl shadow-black/5 border border-gray-100 dark:border-slate-800 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 dark:border-slate-800/60 bg-gray-50/30 dark:bg-slate-900/50">
              <h2 class="text-lg font-bold text-gray-900 dark:text-slate-100 flex items-center gap-2">
                 <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                 Formulario de Edición
              </h2>
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

               <!-- Información de progreso (aviso de bloqueo) -->
              <div v-if="!puedeEditarTerminos" class="bg-orange-50/30 dark:bg-orange-950/20 border border-orange-100 dark:border-orange-900/40 rounded-2xl p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-orange-100 dark:bg-orange-950 rounded-xl flex items-center justify-center flex-shrink-0">
                         <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-orange-900 dark:text-orange-400 uppercase tracking-widest mb-1">Términos Protegidos</h4>
                        <p class="text-xs text-orange-800 dark:text-orange-400/80 leading-relaxed font-medium">
                            Este contrato de préstamo ya cuenta con pagos registrados ({{ prestamo.pagos_realizados }}). Por integridad contable, los campos financieros bases han sido bloqueados. Solo puede editar la descripción y notas.
                        </p>
                    </div>
                </div>
              </div>

              <!-- Grid de información financiera -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Monto prestado -->
                <div>
                  <label for="monto_prestado" class="block text-xs font-black text-gray-500 dark:text-slate-500 uppercase tracking-widest mb-2">
                    Monto a Prestar
                  </label>
                  <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                      <span class="text-gray-400 font-bold">$</span>
                    </div>
                    <input
                      id="monto_prestado"
                      v-model.number="form.monto_prestado"
                      type="number"
                      step="0.01"
                      min="0"
                      placeholder="0.00"
                      :disabled="!puedeEditarTerminos"
                      class="block w-full pl-8 pr-4 py-3 bg-white dark:bg-slate-950 border border-gray-200 dark:border-slate-800 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:text-slate-100 transition-all font-bold disabled:bg-gray-50 dark:disabled:bg-slate-900/50 disabled:opacity-60"
                      :class="{ 'border-red-500 ring-red-500/20': errors.monto_prestado }"
                    />
                  </div>
                  <p v-if="errors.monto_prestado" class="mt-1 text-sm text-red-600">{{ errors.monto_prestado }}</p>
                </div>

                <!-- Tasa de interés -->
                <div>
                  <label for="tasa_interes_mensual" class="block text-sm font-medium text-gray-700 mb-2">
                    Tasa de Interés (%) *
                    <span v-if="!puedeEditarTerminos" class="text-orange-600 text-xs">(Bloqueado)</span>
                  </label>
                  <div class="relative">
                    <input
                      id="tasa_interes_mensual"
                      v-model.number="form.tasa_interes_mensual"
                      type="number"
                      step="0.01"
                      min="0"
                      max="100"
                      placeholder="0.00"
                      :disabled="!puedeEditarTerminos"
                      class="block w-full pr-8 pl-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                      :class="{ 'border-red-300': errors.tasa_interes_mensual }"
                    />
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                      <span class="text-gray-500 dark:text-gray-400 sm:text-sm">%</span>
                    </div>
                  </div>
                  <p v-if="errors.tasa_interes_mensual" class="mt-1 text-sm text-red-600">{{ errors.tasa_interes_mensual }}</p>
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
                <label for="descripcion" class="block text-xs font-black text-gray-500 dark:text-slate-500 uppercase tracking-widest mb-2">
                  Descripción del Crédito
                </label>
                <textarea
                  id="descripcion"
                  v-model="form.descripcion"
                  rows="3"
                  placeholder="Detalles sobre el préstamo..."
                  class="block w-full px-4 py-3 bg-white dark:bg-slate-950 border border-gray-200 dark:border-slate-800 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:text-slate-100 transition-all resize-none font-medium"
                ></textarea>
              </div>

              <!-- Notas -->
              <div>
                <label for="notas" class="block text-xs font-black text-gray-500 dark:text-slate-500 uppercase tracking-widest mb-2">
                  Observaciones Internas
                </label>
                <textarea
                  id="notas"
                  v-model="form.notas"
                  rows="3"
                  placeholder="Notas privadas para administración..."
                  class="block w-full px-4 py-3 bg-white dark:bg-slate-950 border border-gray-200 dark:border-slate-800 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:text-slate-100 transition-all resize-none font-medium"
                ></textarea>
              </div>

              <!-- Botones de acción -->
              <div class="flex items-center justify-end space-x-4 pt-10 border-t border-gray-100 dark:border-slate-800/80">
                <Link
                   :href="`/prestamos/${prestamo.id}`"
                  class="px-8 py-3 bg-white dark:bg-slate-950 border border-gray-200 dark:border-slate-800 text-gray-700 dark:text-slate-300 text-sm font-bold rounded-xl hover:bg-gray-50 dark:hover:bg-slate-900 transition-all duration-300"
                >
                  Cancelar Edición
                </Link>
                <button
                  type="submit"
                  :disabled="loading"
                  class="px-10 py-3 bg-blue-600 text-white text-sm font-black uppercase tracking-widest rounded-xl hover:bg-blue-700 hover:scale-[1.02] shadow-lg shadow-blue-500/20 focus:outline-none focus:ring-4 focus:ring-blue-500/30 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 transition-all duration-300"
                >
                  <span v-if="loading" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Procesando...
                  </span>
                  <span v-else>Guardar Cambios</span>
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Panel de cálculos e información -->
        <div class="lg:col-span-1 space-y-8">
          <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl shadow-black/5 border border-gray-100 dark:border-slate-800 overflow-hidden sticky top-8 transition-all duration-300">
             <div class="px-6 py-5 border-b border-gray-100 dark:border-slate-800/60 bg-gray-50/30 dark:bg-slate-900/50">
                <h3 class="text-lg font-bold text-gray-900 dark:text-slate-100 flex items-center gap-2">
                  <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                  {{ puedeEditarTerminos ? 'Simulador Activo' : 'Resumen Actual' }}
                </h3>
            </div>

            <div class="p-8">
              <div v-if="puedeEditarTerminos && calculando" class="flex flex-col items-center justify-center py-10">
                <div class="w-12 h-12 border-4 border-blue-500/20 border-t-blue-600 rounded-full animate-spin mb-4"></div>
                <p class="text-xs font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest">Recalculando...</p>
              </div>

              <div v-else-if="puedeEditarTerminos && form.monto_prestado > 0 && form.numero_pagos > 0">
                <div class="space-y-6">
                  <!-- Pago periódico -->
                  <div class="bg-gray-50 dark:bg-slate-950 rounded-2xl p-5 border border-gray-100 dark:border-slate-800/50">
                    <p class="text-[10px] font-black text-gray-500 dark:text-slate-500 uppercase tracking-widest mb-2">Cuota {{ form.frecuencia_pago }}</p>
                    <p class="text-3xl font-black text-blue-600 tracking-tight">${{ formatearMoneda(calculos.pago_periodico) }}</p>
                  </div>

                  <!-- Detalles en grid -->
                  <div class="grid grid-cols-1 gap-4">
                    <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-slate-800">
                      <span class="text-xs font-bold text-gray-500 dark:text-slate-400">INTERÉS TOTAL</span>
                      <span class="text-sm font-black text-gray-900 dark:text-slate-100">${{ formatearMoneda(calculos.interes_total) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-3">
                      <span class="text-xs font-bold text-gray-500 dark:text-slate-400">VALOR FINAL</span>
                      <span class="text-lg font-black text-gray-900 dark:text-slate-100 tracking-tight">${{ formatearMoneda(calculos.total_pagar) }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <div v-else class="space-y-6">
                 <div class="bg-gray-50 dark:bg-slate-950 rounded-2xl p-5 border border-gray-100 dark:border-slate-800/50">
                    <p class="text-[10px] font-black text-gray-500 dark:text-slate-500 uppercase tracking-widest mb-2">Cuota Actual</p>
                    <p class="text-3xl font-black text-green-600 tracking-tight">${{ formatearMoneda(prestamo.pago_periodico) }}</p>
                  </div>

                  <div class="grid grid-cols-1 gap-3">
                    <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-slate-800">
                      <span class="text-xs font-bold text-gray-500 dark:text-slate-400 uppercase">Pagado</span>
                      <span class="text-xs font-black text-green-600">${{ formatearMoneda(prestamo.monto_pagado) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-slate-800">
                      <span class="text-xs font-bold text-gray-500 dark:text-slate-400 uppercase">Pendiente</span>
                      <span class="text-xs font-black text-red-600">${{ formatearMoneda(prestamo.monto_pendiente) }}</span>
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

