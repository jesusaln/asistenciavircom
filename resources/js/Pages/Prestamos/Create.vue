<!-- /resources/js/Pages/Prestamos/Create.vue -->
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
  clientes: {
    type: Array,
    default: () => []
  },
  prestamo: {
    type: Object,
    default: () => ({
      cliente_id: null,
      monto_prestado: 0,
      tasa_interes_mensual: 5, // 5% mensual por defecto
      numero_pagos: 12,
      frecuencia_pago: 'mensual',
      fecha_inicio: new Date().toISOString().split('T')[0],
      descripcion: null,
      notas: null,
    })
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
  ...props.prestamo,
  // Asegurar que tasa_interes_mensual siempre tenga un valor numérico válido
  tasa_interes_mensual: props.prestamo.tasa_interes_mensual || 5
})
const loading = ref(false)
const calculando = ref(false)
const clienteSeleccionado = ref(null)
const mostrarModalDetalles = ref(false)

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
  pago_periodico: 0,
  interes_total: 0,
  total_pagar: 0,
})

// Debounce para evitar saturar el servidor
let timeoutCalculo = null
const calcularPagosDebounced = () => {
  if (timeoutCalculo) clearTimeout(timeoutCalculo)
  timeoutCalculo = setTimeout(() => {
    calcularPagos()
  }, 400)
}

const calcularPagos = async () => {
  console.log('Iniciando cálculo de pagos con datos:', {
    monto_prestado: form.value.monto_prestado,
    tasa_interes_mensual: form.value.tasa_interes_mensual,
    numero_pagos: form.value.numero_pagos,
    frecuencia_pago: form.value.frecuencia_pago
  })

  // Validaciones básicas antes de enviar
  if (!form.value.monto_prestado || form.value.monto_prestado <= 0) {
    calculos.value = { pago_periodico: 0, interes_total: 0, total_pagar: 0 }
    return
  }

  if (!form.value.numero_pagos || form.value.numero_pagos < 1) {
    calculos.value = { pago_periodico: 0, interes_total: 0, total_pagar: 0 }
    return
  }

  if (form.value.tasa_interes_mensual === undefined || form.value.tasa_interes_mensual < 0) {
    calculos.value = { pago_periodico: 0, interes_total: 0, total_pagar: 0 }
    return
  }

  calculando.value = true

  try {
    const requestData = {
      monto_prestado: parseFloat(form.value.monto_prestado),
      tasa_interes_mensual: parseFloat(form.value.tasa_interes_mensual),
      numero_pagos: parseInt(form.value.numero_pagos),
      frecuencia_pago: form.value.frecuencia_pago,
    }

    console.log('Datos a enviar:', requestData)

    // Usamos axios en lugar de fetch para aprovechar el interceptor de CSRF global
    // que maneja automáticamente los errores 419 (token mismatch/expirado)
    const response = await axios.post('/prestamos/calcular-pagos', requestData)
    const data = response.data

    console.log('Respuesta del servidor:', response.status, response.statusText)
    console.log('Datos recibidos:', data)

    if (data.success && data.calculos) {
      calculos.value = data.calculos
      console.log('Cálculos actualizados:', calculos.value)
    } else {
      console.error('Respuesta del servidor sin éxito:', data)
      notyf.error('Error en el cálculo: ' + (data.message || 'Respuesta inválida del servidor'))
      calculos.value = { pago_periodico: 0, interes_total: 0, total_pagar: 0 }
    }
  } catch (error) {
    console.error('Error en petición:', error)
    
    // El interceptor global de axios maneja el reintento de 419.
    // Si llega aquí es porque el reintento falló o es otro error.
    if (error.response?.status === 419) {
      notyf.error('Tu sesión ha expirado. Por favor recarga la página.')
    } else {
      notyf.error('Error de conexión: ' + (error.response?.data?.message || error.message))
    }
    
    calculos.value = { pago_periodico: 0, interes_total: 0, total_pagar: 0 }
  } finally {
    calculando.value = false
  }
}

/* =========================
    Watchers para recálculo automático
 ========================= */
watch(
  () => form.value.monto_prestado,
  () => calcularPagosDebounced()
)

watch(
  () => form.value.tasa_interes_mensual,
  () => calcularPagosDebounced()
)

watch(
  () => form.value.numero_pagos,
  () => calcularPagosDebounced()
)

watch(
  () => form.value.frecuencia_pago,
  () => calcularPagosDebounced()
)

watch(
  () => form.value.tasa_interes_mensual,
  (newValue) => {
    if (newValue === null || newValue === undefined || newValue === '') {
      form.value.tasa_interes_mensual = 5
    }
    calcularPagosDebounced()
  },
  { immediate: true }
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

  // Validación más estricta para tasa_interes_mensual
  if (form.value.tasa_interes_mensual === null || form.value.tasa_interes_mensual === undefined || form.value.tasa_interes_mensual === '') {
    errors.value.tasa_interes_mensual = 'La tasa de interés es requerida'
  } else if (form.value.tasa_interes_mensual < 0 || form.value.tasa_interes_mensual > 100) {
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
  // Verificación adicional antes de validar
  if (form.value.tasa_interes_mensual === null || form.value.tasa_interes_mensual === undefined || form.value.tasa_interes_mensual === '') {
    form.value.tasa_interes_mensual = 5
  }

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

  console.log('Datos a enviar al servidor:', datosPrestamo)

  router.post('/prestamos', datosPrestamo, {
    onStart: () => {
      notyf.success('Creando préstamo...')
    },
    onSuccess: () => {
      notyf.success('Préstamo creado correctamente')
      // Limpiar formulario después de crear
      form.value = { ...props.prestamo }
      clienteSeleccionado.value = null
      if (buscarClienteRef.value) {
        buscarClienteRef.value.limpiarBusqueda()
      }
    },
    onError: (errors) => {
      console.error('Errores de validación:', errors)
      notyf.error('Error al crear el préstamo')
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

// Función para mostrar detalles del cálculo
const mostrarDetallesCalculo = () => {
  if (!calculos.value.detalles_calculo) return null;

  const detalles = calculos.value.detalles_calculo;
  const tasaMensual = detalles.tasa_mensual;
  const factor = calculos.value.factor_compuesto;

  return {
    paso1: `Tasa mensual = ${form.value.tasa_interes_mensual}% (tasa directa)`,
    paso2: `Factor compuesto = (1 + ${tasaMensual.toFixed(6)})^${detalles.periodos} = ${factor.toFixed(6)}`,
    paso3: `Pago = $${detalles.capital.toLocaleString('es-MX', {minimumFractionDigits: 2})} × (${tasaMensual.toFixed(6)} × ${factor.toFixed(6)}) ÷ (${factor.toFixed(6)} - 1)`,
    resultado: `Pago = $${calculos.value.pago_periodico.toLocaleString('es-MX', {minimumFractionDigits: 2})}`
  };
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
</script>

<template>
  <Head title="Crear Préstamo" />

  <div class="prestamos-create min-h-screen bg-white dark:bg-slate-950 transition-colors duration-300">
    <div class="w-full px-6 py-8">
      <!-- Header -->
      <div class="mb-10">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-slate-100 tracking-tight">Crear Nuevo Préstamo</h1>
            <p class="text-gray-600 dark:text-slate-400 mt-2">Configure los términos del crédito y visualice la proyección de pagos en tiempo real</p>
          </div>
          <Link
            href="/prestamos"
            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-slate-800 text-sm font-semibold rounded-xl text-gray-700 dark:text-slate-300 bg-white dark:bg-slate-900 hover:bg-gray-50 dark:hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200"
          >
            ← Volver a Préstamos
          </Link>
        </div>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-4 gap-8">
        <!-- Formulario principal -->
        <div class="xl:col-span-3">
          <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl shadow-black/5 border border-gray-100 dark:border-slate-800 overflow-hidden transition-all duration-300">
            <div class="px-6 py-5 border-b border-gray-100 dark:border-slate-800/60 bg-gray-50/30 dark:bg-slate-900/50">
              <h2 class="text-lg font-bold text-gray-900 dark:text-slate-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Configuración del Crédito
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
                  :size="'large'"
                  titulo-cliente-seleccionado="Cliente Seleccionado para Préstamo"
                  mensaje-vacio="Selecciona un cliente para el préstamo"
                  submensaje-vacio="Busca y selecciona un cliente existente o crea uno nuevo"
                  @cliente-seleccionado="onClienteSeleccionado"
                  @crear-nuevo-cliente="onCrearNuevoCliente"
                />
                <p v-if="errors.cliente_id" class="mt-1 text-sm text-red-600">{{ errors.cliente_id }}</p>
              </div>

              <!-- Grid de información financiera -->
              <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <!-- Monto prestado -->
                <div>
                  <label for="monto_prestado" class="block text-xs font-black text-gray-500 dark:text-slate-500 uppercase tracking-widest mb-2">
                    Monto a Prestar *
                  </label>
                  <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition-colors">
                      <span class="text-sm font-bold">$</span>
                    </div>
                    <input
                      id="monto_prestado"
                      v-model.number="form.monto_prestado"
                      type="number"
                      step="0.01"
                      min="0"
                      placeholder="0.00"
                      class="block w-full pl-10 pr-4 py-3 bg-white dark:bg-slate-950 border border-gray-200 dark:border-slate-800 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:text-slate-100 transition-all"
                      :class="{ 'border-red-300 dark:border-red-900/50': errors.monto_prestado }"
                    />
                  </div>
                  <p v-if="errors.monto_prestado" class="mt-2 text-xs font-bold text-red-600 dark:text-red-400">{{ errors.monto_prestado }}</p>
                </div>

                <!-- Tasa de interés mensual -->
                <div>
                  <label for="tasa_interes_mensual" class="block text-xs font-black text-gray-500 dark:text-slate-500 uppercase tracking-widest mb-2">
                    Interés Mensual (%) *
                  </label>
                  <div class="relative group">
                    <input
                      id="tasa_interes_mensual"
                      v-model.number="form.tasa_interes_mensual"
                      type="number"
                      step="0.01"
                      min="0"
                      max="100"
                      placeholder="5.00"
                      class="block w-full pl-4 pr-10 py-3 bg-white dark:bg-slate-950 border border-gray-200 dark:border-slate-800 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:text-slate-100 transition-all"
                      :class="{ 'border-red-300 dark:border-red-900/50': errors.tasa_interes_mensual }"
                    />
                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition-colors">
                      <span class="text-sm font-bold">%</span>
                    </div>
                  </div>
                  <p v-if="errors.tasa_interes_mensual" class="mt-2 text-xs font-bold text-red-600 dark:text-red-400">{{ errors.tasa_interes_mensual }}</p>
                  <p class="mt-2 text-[10px] font-medium text-gray-500 dark:text-slate-500 italic">Interés que se devenga mes a mes</p>
                </div>

                <!-- Número de pagos -->
                <div>
                  <label for="numero_pagos" class="block text-xs font-black text-gray-500 dark:text-slate-500 uppercase tracking-widest mb-2">
                    Número de Pagos *
                  </label>
                  <select
                    id="numero_pagos"
                    v-model="form.numero_pagos"
                    class="block w-full px-4 py-3 bg-white dark:bg-slate-950 border border-gray-200 dark:border-slate-800 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:text-slate-100 transition-all cursor-pointer"
                    :class="{ 'border-red-300 dark:border-red-900/50': errors.numero_pagos }"
                  >
                    <option v-for="opcion in opcionesNumeroPagos" :key="opcion.value" :value="opcion.value">
                      {{ opcion.label }}
                    </option>
                  </select>
                  <p v-if="errors.numero_pagos" class="mt-2 text-xs font-bold text-red-600 dark:text-red-400">{{ errors.numero_pagos }}</p>
                </div>

                <!-- Frecuencia de pago -->
                <div>
                  <label for="frecuencia_pago" class="block text-xs font-black text-gray-500 dark:text-slate-500 uppercase tracking-widest mb-2">
                    Frecuencia de Pago *
                  </label>
                  <select
                    id="frecuencia_pago"
                    v-model="form.frecuencia_pago"
                    class="block w-full px-4 py-3 bg-white dark:bg-slate-950 border border-gray-200 dark:border-slate-800 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:text-slate-100 transition-all cursor-pointer"
                  >
                    <option v-for="opcion in opcionesFrecuencia" :key="opcion.value" :value="opcion.value">
                      {{ opcion.label }}
                    </option>
                  </select>
                </div>

                <!-- Fecha de inicio -->
                <div>
                  <label for="fecha_inicio" class="block text-xs font-black text-gray-500 dark:text-slate-500 uppercase tracking-widest mb-2">
                    Fecha de Inicio *
                  </label>
                  <input
                    id="fecha_inicio"
                    v-model="form.fecha_inicio"
                    type="date"
                    class="block w-full px-4 py-3 bg-white dark:bg-slate-950 border border-gray-200 dark:border-slate-800 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:text-slate-100 transition-all"
                    :class="{ 'border-red-300 dark:border-red-900/50': errors.fecha_inicio }"
                  />
                  <p v-if="errors.fecha_inicio" class="mt-2 text-xs font-bold text-red-600 dark:text-red-400">{{ errors.fecha_inicio }}</p>
                </div>
              </div>

              <!-- Descripción -->
              <div>
                <label for="descripcion" class="block text-xs font-black text-gray-500 dark:text-slate-500 uppercase tracking-widest mb-2">
                  Descripción
                </label>
                <textarea
                  id="descripcion"
                  v-model="form.descripcion"
                  rows="3"
                  placeholder="Finalidad del crédito o detalles adicionales relevantes..."
                  class="block w-full px-4 py-3 bg-white dark:bg-slate-950 border border-gray-200 dark:border-slate-800 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:text-slate-100 transition-all resize-none"
                ></textarea>
              </div>

              <!-- Notas -->
              <div>
                <label for="notas" class="block text-xs font-black text-gray-500 dark:text-slate-500 uppercase tracking-widest mb-2">
                  Notas Internas
                </label>
                <textarea
                  id="notas"
                  v-model="form.notas"
                  rows="3"
                  placeholder="Comentarios exclusivos para administración..."
                  class="block w-full px-4 py-3 bg-white dark:bg-slate-950 border border-gray-200 dark:border-slate-800 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:text-slate-100 transition-all resize-none"
                ></textarea>
              </div>

              <!-- Botones de acción -->
              <div class="flex items-center justify-end space-x-4 pt-8 border-t border-gray-100 dark:border-slate-800/60">
                <Link
                  href="/prestamos"
                  class="px-6 py-3 border border-gray-200 dark:border-slate-800 text-sm font-bold rounded-xl text-gray-600 dark:text-slate-400 bg-white dark:bg-slate-900 hover:bg-gray-50 dark:hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 transition-all duration-200"
                >
                  Cancelar
                </Link>
                <button
                  type="submit"
                  :disabled="loading"
                  class="px-8 py-3 bg-blue-600 text-white text-sm font-bold rounded-xl hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-blue-600/20 transition-all duration-200 flex items-center gap-2"
                >
                  <span v-if="loading" class="flex items-center">
                    <svg class="animate-spin h-4 w-4 text-white mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Procesando...
                  </span>
                  <span v-else class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Confirmar y Crear
                  </span>
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Panel de cálculos -->
        <div class="xl:col-span-1">
          <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl shadow-black/5 border border-gray-100 dark:border-slate-800 overflow-hidden sticky top-8 transition-all duration-300">
            <div class="px-6 py-5 border-b border-gray-100 dark:border-slate-800/60 bg-gray-50/30 dark:bg-slate-900/50">
              <div class="flex items-center justify-between">
                <div>
                  <h3 class="text-lg font-bold text-gray-900 dark:text-slate-100">Proyección</h3>
                  <p class="text-xs font-medium text-gray-500 dark:text-slate-500 mt-1 uppercase tracking-widest">Tiempo Real</p>
                </div>
                <button
                  @click="calcularPagos"
                  :disabled="calculando"
                  class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-xl transition-all disabled:opacity-50"
                  title="Recalcular"
                >
                  <svg class="w-5 h-5" :class="{ 'animate-spin': calculando }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                </button>
              </div>
            </div>

            <div class="p-6">
              <div v-if="calculando" class="text-center py-12">
                <div class="relative w-12 h-12 mx-auto mb-4">
                  <div class="absolute inset-0 border-4 border-blue-500/20 rounded-full"></div>
                  <div class="absolute inset-0 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                </div>
                <p class="text-sm font-bold text-gray-900 dark:text-slate-100">Calculando términos...</p>
                <p class="text-xs text-gray-500 dark:text-slate-500 mt-1">Generando tabla de amortización</p>
              </div>

              <div v-else-if="form.monto_prestado > 0 && form.numero_pagos > 0">
                <div class="space-y-6">
                  <!-- Pago periódico -->
                  <div class="bg-blue-50/30 dark:bg-blue-900/10 p-5 rounded-2xl border border-blue-100/50 dark:border-blue-900/20 group hover:border-blue-500/30 transition-all">
                    <p class="text-[10px] font-black text-blue-600 dark:text-blue-500 uppercase tracking-widest mb-1">Pago {{ form.frecuencia_pago }} Est.</p>
                    <p class="text-3xl font-black text-gray-900 dark:text-slate-100 tracking-tight">
                      ${{ formatearMoneda(calculos.pago_periodico) }}
                    </p>
                  </div>

                  <!-- Grid de totales -->
                  <div class="grid grid-cols-1 gap-4">
                    <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-slate-800/60">
                      <span class="text-xs font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">Interés Total</span>
                      <span class="text-sm font-bold text-blue-600 dark:text-blue-400">
                        +${{ formatearMoneda(calculos.interes_total) }}
                      </span>
                    </div>

                    <div class="flex justify-between items-center py-3">
                      <span class="text-xs font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">Total a Pagar</span>
                      <span class="text-lg font-black text-gray-900 dark:text-slate-100">
                        ${{ formatearMoneda(calculos.total_pagar) }}
                      </span>
                    </div>
                  </div>

                  <!-- Información adicional -->
                  <div class="bg-gray-50/50 dark:bg-slate-950/40 rounded-2xl p-5 border border-gray-100 dark:border-slate-800/50 mt-2">
                    <h4 class="text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-4">Resumen de Términos</h4>
                    <div class="space-y-3">
                      <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500 dark:text-slate-400">Capital base</span>
                        <span class="font-bold text-gray-900 dark:text-slate-200">${{ formatearMoneda(form.monto_prestado) }}</span>
                      </div>
                      <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500 dark:text-slate-400">Interés mensual</span>
                        <span class="font-bold text-gray-900 dark:text-slate-200">{{ form.tasa_interes_mensual }}%</span>
                      </div>
                      <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500 dark:text-slate-400">Cuotas totales</span>
                        <span class="font-bold text-gray-900 dark:text-slate-200">{{ form.numero_pagos }} {{ form.frecuencia_pago }}s</span>
                      </div>
                    </div>

                    <!-- Información del cálculo -->
                    <div class="mt-6 pt-5 border-t border-gray-100 dark:border-slate-800/60">
                      <div class="flex items-center gap-2 mb-3">
                        <div class="w-1.5 h-1.5 rounded-full bg-blue-500"></div>
                        <span class="text-[10px] font-black text-blue-600 dark:text-blue-500 uppercase tracking-widest">Método de Amortización</span>
                      </div>
                      <p class="text-[11px] text-gray-500 dark:text-slate-500 leading-relaxed italic">
                        Cálculo mediante Sistema Francés con interés compuesto y tasa directa.
                      </p>

                      <!-- Botón para ver detalles del cálculo -->
                      <div class="mt-4">
                        <button
                          @click="mostrarModalDetalles = !mostrarModalDetalles"
                          class="inline-flex items-center gap-2 text-xs font-bold text-blue-600 hover:text-blue-700 dark:text-blue-400 transition-colors group"
                        >
                          <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                          {{ mostrarModalDetalles ? 'Ocultar' : 'Ver' }} desglose técnico
                        </button>
                      </div>

                      <!-- Detalles del cálculo paso a paso -->
                      <Transition name="fade">
                        <div v-if="mostrarModalDetalles && calculos.detalles_calculo" class="mt-4 p-4 bg-blue-50/50 dark:bg-blue-900/10 rounded-xl border border-blue-100/30 dark:border-blue-800/20">
                          <h5 class="text-[10px] font-black text-blue-700 dark:text-blue-400 uppercase tracking-widest mb-3">Memoria de Cálculo:</h5>
                          <div class="space-y-3">
                            <div class="bg-white/50 dark:bg-slate-900/40 p-2.5 rounded-lg border border-blue-100/20 dark:border-blue-900/20">
                                <p class="text-[10px] text-blue-600/70 dark:text-blue-500/50 uppercase mb-1">Paso 1: Tasa</p>
                                <p class="text-xs font-medium text-blue-900 dark:text-slate-300">{{ mostrarDetallesCalculo().paso1 }}</p>
                            </div>
                            <div class="bg-white/50 dark:bg-slate-900/40 p-2.5 rounded-lg border border-blue-100/20 dark:border-blue-900/20">
                                <p class="text-[10px] text-blue-600/70 dark:text-blue-500/50 uppercase mb-1">Paso 2: Factor</p>
                                <p class="text-xs font-medium text-blue-900 dark:text-slate-300">{{ mostrarDetallesCalculo().paso2 }}</p>
                            </div>
                            <div class="bg-white/50 dark:bg-slate-900/40 p-2.5 rounded-lg border border-blue-100/20 dark:border-blue-900/20">
                                <p class="text-[10px] text-blue-600/70 dark:text-blue-500/50 uppercase mb-1">Paso 3: Amortización</p>
                                <p class="text-xs font-medium text-blue-900 dark:text-slate-300 break-words">{{ mostrarDetallesCalculo().paso3 }}</p>
                            </div>
                            <div class="pt-2 border-t border-blue-100/30 dark:border-blue-900/20">
                                <p class="text-sm font-black text-blue-900 dark:text-blue-400">{{ mostrarDetallesCalculo().resultado }}</p>
                            </div>
                          </div>
                        </div>
                      </Transition>
                    </div>
                  </div>
                </div>
              </div>

              <div v-else class="text-center py-8">
                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                  <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                  </svg>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Complete los datos para ver el cálculo</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.prestamos-create {
  min-height: 100vh;
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>

