<!-- /resources/js/Pages/Pagos/Create.vue -->
<script setup>
import { useFormatters } from '@/Composables/useFormatters';
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
     'pendiente': 'text-brand-400 bg-brand-500/10 border border-brand-500/20 px-2 py-0.5 rounded-xl',
     'pagado': 'text-emerald-400 bg-brand-500/10 border border-emerald-500/20 px-2 py-0.5 rounded-xl',
     'atrasado': 'text-rose-400 bg-brand-500/10 border border-rose-500/20 px-2 py-0.5 rounded-xl',
     'parcial': 'text-blue-400 bg-brand-500/10 border border-blue-500/20 px-2 py-0.5 rounded-xl'
   }
   return colors[estado] || 'text-slate-400 bg-slate-500/10 border border-slate-500/20 px-2 py-0.5 rounded-xl'
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
        
        // Clear error if exists
        if (errors.value.monto_pagado) {
          delete errors.value.monto_pagado
        }
      }
    } else {
      // Si se deselecciona el pago, limpiar el monto
      form.value.monto_pagado = 0
    }
  },
  { immediate: false }
)
</script>

<template>
  <Head title="Registrar Pago de Préstamo" />

  <div class="pagos-create min-h-screen bg-[var(--ui-surface)] text-slate-200 font-sans selection:bg-indigo-500/30">
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      <!-- Header Premium -->
      <div class="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-6 animate-in fade-in slide-in-from-top-4 duration-700">
        <div class="flex items-center gap-6">
           <div class="relative group">
              <div class="absolute -inset-1 bg-gradient-to-r from-brand-500 to-brand-600 rounded-2xl blur opacity-25 group-hover:opacity-50 transition duration-500"></div>
              <div class="relative w-16 h-16 rounded-2xl bg-slate-900 border border-white/10 flex items-center justify-center shadow-2xl backdrop-blur-xl">
                 <svg class="w-10 h-10 text-indigo-400 group-hover:scale-105 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                 </svg>
              </div>
           </div>
           <div>
              <h1 class="text-4xl font-black text-white tracking-tighter mb-1 uppercase">
                Registrar <span class="bg-clip-text text-transparent bg-gradient-to-r from-indigo-400 to-purple-400">Pago</span>
              </h1>
              <p class="text-slate-500 text-sm font-bold uppercase tracking-wide">Formalización de abonos a préstamos activos</p>
           </div>
        </div>
 
        <div class="flex items-center gap-4">
          <Link
            :href="`/prestamos/${prestamo.id}`"
            class="px-5 py-2.5 bg-black/50 border border-white/10 text-slate-400 text-[10px] font-black uppercase tracking-wide rounded-2xl hover:bg-slate-800 hover:text-white transition-all shadow-xl backdrop-blur-md flex items-center gap-2 group"
          >
            <svg class="w-4 h-4 text-indigo-500 group-hover:rotate-12 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
            Ver Préstamo
          </Link>
          <Link
            href="/pagos"
            class="px-5 py-2.5 bg-black/50 border border-white/10 text-slate-400 text-[10px] font-black uppercase tracking-wide rounded-2xl hover:bg-slate-800 hover:text-white transition-all shadow-xl backdrop-blur-md flex items-center gap-2 group"
          >
            <svg class="w-4 h-4 text-slate-500 group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            Volver
          </Link>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Formulario principal -->
        <div class="lg:col-span-2">
          <div class="bg-black/50 border border-white/5 rounded-2xl shadow-xl backdrop-blur-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-white/5 bg-slate-950/30">
              <h2 class="text-lg font-bold text-white">Información del Pago</h2>
            </div>

            <form @submit.prevent="submitForm" class="p-6 space-y-6">
              <!-- Información del préstamo -->
              <div class="bg-indigo-500/10 border border-indigo-500/20 rounded-xl p-5">
                <h3 class="text-xs font-black text-indigo-400 uppercase tracking-wider mb-4 flex items-center">
                   <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                   Información del Préstamo
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                  <div>
                    <span class="block text-slate-400 text-xs mb-1">Cliente:</span>
                    <span class="font-bold text-white text-base">{{ prestamo.cliente?.nombre_razon_social }}</span>
                  </div>
                  <div>
                    <span class="block text-slate-400 text-xs mb-1">Monto del préstamo:</span>
                    <span class="font-bold text-white text-base">${{ formatearMoneda(prestamo.monto_prestado) }}</span>
                  </div>
                  <div>
                    <span class="block text-slate-400 text-xs mb-1">Estado del préstamo:</span>
                    <span :class="['font-bold text-xs', getEstadoColor(prestamo.estado)]">
                      {{ getEstadoLabel(prestamo.estado) }}
                    </span>
                  </div>
                  <div>
                    <span class="block text-slate-400 text-xs mb-1">Progreso:</span>
                    <span class="font-bold text-indigo-300 text-base">{{ prestamo.pagos_realizados }} / {{ prestamo.numero_pagos }} pagos</span>
                  </div>
                </div>
              </div>

              <!-- Información del pago seleccionado -->
              <div v-if="form.pago_id" class="bg-brand-500/10 border border-emerald-500/20 rounded-xl p-5 animate-in fade-in slide-in-from-top-2 duration-200">
                <h3 class="text-xs font-black text-emerald-400 uppercase tracking-wider mb-4 flex items-center">
                   <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                   Detalles del Pago Seleccionado
                </h3>
                <div v-for="pago in pagos_pendientes" :key="pago.id">
                  <div v-if="pago.id == form.pago_id" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                      <span class="block text-slate-400 text-xs mb-1">Número de pago:</span>
                      <span class="font-mono text-emerald-300 bg-brand-500/10 px-2 py-0.5 rounded-xl text-xs border border-emerald-500/20">#{{ pago.numero_pago }}</span>
                    </div>
                    <div>
                      <span class="block text-slate-400 text-xs mb-1">Fecha programada:</span>
                      <span class="font-bold text-white">{{ formatearFecha(pago.fecha_programada) }}</span>
                    </div>
                    <div>
                      <span class="block text-slate-400 text-xs mb-1">Monto programado:</span>
                      <span class="font-bold text-white">${{ formatearMoneda(pago.monto_programado) }}</span>
                    </div>
                     <div>
                      <span class="block text-slate-400 text-xs mb-1">Monto pagado al momento:</span>
                      <span class="font-bold text-emerald-400">${{ formatearMoneda(pago.monto_pagado) }}</span>
                    </div>
                     <div>
                      <span class="block text-slate-400 text-xs mb-1">Monto pendiente:</span>
                      <span class="font-bold text-brand-400 text-lg">${{ formatearMoneda(pago.monto_programado - pago.monto_pagado) }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Selección del pago -->
              <div>
                <label for="pago_id" class="block text-sm font-bold text-slate-300 mb-2">
                  Seleccionar Pago a Registrar <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                   <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                   </div>
                  <select
                    id="pago_id"
                    v-model="form.pago_id"
                    class="block w-full pl-10 pr-10 py-3 bg-slate-950 border rounded-2xl shadow-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-slate-200 transaction-all"
                    :class="errors.pago_id ? 'border-rose-500/50 focus:ring-brand-500' : 'border-white/10'"
                  >
                    <option value="" class="bg-slate-900">Seleccionar pago...</option>
                    <option v-for="pago in pagos_pendientes" :key="pago.id" :value="pago.id" class="bg-slate-900">
                      Pago #{{ pago.numero_pago }} - ${{ formatearMoneda(pago.monto_programado) }} ({{ formatearFecha(pago.fecha_programada) }})
                    </option>
                  </select>
                </div>
                <p v-if="errors.pago_id" class="mt-2 text-sm text-rose-500 flex items-center">
                   <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                   {{ errors.pago_id }}
                </p>
              </div>

              <!-- Grid de información del pago -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Monto pagado -->
                <div>
                  <label for="monto_pagado" class="block text-sm font-bold text-slate-300 mb-2">
                    Monto Pagado <span class="text-rose-500">*</span>
                    <span v-if="form.pago_id" class="text-xs text-indigo-400 font-normal ml-2">(Autocompletado)</span>
                  </label>
                  <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <span class="text-slate-500 font-bold">$</span>
                    </div>
                    <input
                      id="monto_pagado"
                      v-model.number="form.monto_pagado"
                      type="number"
                      step="0.01"
                      min="0"
                      :max="montoMaximo"
                      :placeholder="placeholderMonto"
                      class="block w-full pl-8 pr-3 py-3 bg-slate-950 border rounded-2xl shadow-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-white font-bold placeholder-slate-600 transition-all"
                      :class="[
                        form.pago_id ? 'border-emerald-500/30' : 'border-white/10',
                        errors.monto_pagado && 'border-rose-500/50 focus:ring-brand-500'
                      ]"
                    />
                    <div v-if="form.pago_id" class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                      <span class="text-emerald-500">✓</span>
                    </div>
                  </div>
                  <p v-if="errors.monto_pagado" class="mt-2 text-sm text-rose-500 flex items-center">
                      <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                      {{ errors.monto_pagado }}
                  </p>
                </div>

                <!-- Fecha de pago -->
                <div>
                  <label for="fecha_pago" class="block text-sm font-bold text-slate-300 mb-2">
                    Fecha de Pago <span class="text-rose-500">*</span>
                  </label>
                  <input
                    id="fecha_pago"
                    v-model="form.fecha_pago"
                    type="date"
                    class="block w-full px-4 py-3 bg-slate-950 border border-white/10 rounded-2xl shadow-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-white transition-all"
                    :class="{ 'border-rose-500/50 focus:ring-brand-500': errors.fecha_pago }"
                  />
                  <p v-if="errors.fecha_pago" class="mt-2 text-sm text-rose-500">{{ errors.fecha_pago }}</p>
                </div>

                <!-- Método de pago -->
                <div>
                  <label for="metodo_pago" class="block text-sm font-bold text-slate-300 mb-2">
                    Método de Pago
                  </label>
                  <div class="relative">
                      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                      </div>
                      <select
                        id="metodo_pago"
                        v-model="form.metodo_pago"
                        class="block w-full pl-10 pr-10 py-3 bg-slate-950 border border-white/10 rounded-2xl shadow-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-white transition-all"
                      >
                        <option v-for="opcion in opcionesMetodoPago" :key="opcion.value" :value="opcion.value" class="bg-slate-900">
                          {{ opcion.label }}
                        </option>
                      </select>
                  </div>
                </div>

                <!-- Referencia -->
                <div>
                  <label for="referencia" class="block text-sm font-bold text-slate-300 mb-2">
                    Referencia
                  </label>
                  <input
                    id="referencia"
                    v-model="form.referencia"
                    type="text"
                    placeholder="Número de referencia, folio, etc."
                    class="block w-full px-4 py-3 bg-slate-950 border border-white/10 rounded-2xl shadow-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-white placeholder-slate-600 transition-all"
                  />
                </div>
              </div>

              <!-- Cuenta Bancaria -->
              <div v-if="cuentasBancarias && cuentasBancarias.length > 0">
                <label for="cuenta_bancaria_id" class="block text-sm font-bold text-slate-300 mb-2">
                  Cuenta Bancaria de Destino
                  <span class="text-xs text-slate-500 font-normal ml-2">(Opcional)</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                       <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" /></svg>
                    </div>
                    <select
                      id="cuenta_bancaria_id"
                      v-model="form.cuenta_bancaria_id"
                      class="block w-full pl-10 pr-10 py-3 bg-slate-950 border border-white/10 rounded-2xl shadow-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-white transition-all"
                    >
                      <option :value="null" class="bg-slate-900">Sin cuenta bancaria</option>
                      <option v-for="cuenta in cuentasBancarias" :key="cuenta.id" :value="cuenta.id" class="bg-slate-900">
                        {{ cuenta.banco }} - {{ cuenta.nombre }}
                      </option>
                    </select>
                </div>
                <p class="mt-2 text-xs text-slate-400">
                  Si selecciona una cuenta, el pago se registrará como depósito.
                </p>
              </div>

              <!-- Notas -->
              <div>
                <label for="notas" class="block text-sm font-bold text-slate-300 mb-2">
                  Notas Adicionales
                </label>
                <textarea
                  id="notas"
                  v-model="form.notas"
                  rows="3"
                  placeholder="Notas adicionales sobre el pago (opcional)"
                  class="block w-full px-4 py-3 bg-slate-950 border border-white/10 rounded-2xl shadow-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-white placeholder-slate-600 transition-all resize-none"
                ></textarea>
              </div>

              <!-- Botones de acción -->
              <div class="flex items-center justify-end space-x-4 pt-8 border-t border-white/5">
                <Link
                  href="/pagos"
                  class="px-6 py-3 border border-white/10 text-slate-300 text-sm font-bold rounded-xl hover:bg-slate-800 hover:text-white transition-all shadow-xl"
                >
                  Cancelar
                </Link>
                <button
                  type="submit"
                  :disabled="loading"
                  class="px-8 py-3 bg-indigo-600 text-white text-sm font-bold rounded-xl hover:bg-indigo-500 shadow-xl shadow-indigo-600/20 disabled:opacity-50 disabled:cursor-not-allowed transition-all transform hover:scale-105"
                >
                  <span v-if="loading" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Registrando...
                  </span>
                  <span v-else>Confirmar Pago</span>
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Panel de información -->
        <div class="lg:col-span-1">
          <div class="bg-black/50 rounded-2xl shadow-xl border border-white/5 overflow-hidden sticky top-8 backdrop-blur-sm">
            <div class="px-6 py-5 border-b border-white/5 bg-slate-950/30">
              <h3 class="text-lg font-bold text-white">Resumen Global</h3>
            </div>

            <div class="p-6">
              <div class="space-y-6">
                <!-- Información del préstamo -->
                <div class="bg-indigo-500/5 rounded-xl p-5 border border-indigo-500/10">
                  <h4 class="text-xs font-black text-indigo-400 uppercase tracking-wider mb-4">Datos Financieros</h4>
                  <div class="space-y-3 text-sm">
                    <div class="flex justify-between items-center group">
                      <span class="text-slate-400 group-hover:text-slate-300 transition-colors">Capital:</span>
                      <span class="font-bold text-white">${{ formatearMoneda(prestamo.monto_prestado) }}</span>
                    </div>
                    <div class="flex justify-between items-center group">
                      <span class="text-slate-400 group-hover:text-slate-300 transition-colors">Tasa mensual:</span>
                      <span class="font-bold text-white bg-indigo-500/10 px-2 py-0.5 rounded-xl text-xs border border-indigo-500/20">{{ prestamo.tasa_interes_mensual }}%</span>
                    </div>
                    <div class="flex justify-between items-center group">
                      <span class="text-slate-400 group-hover:text-slate-300 transition-colors">Pago periódico:</span>
                      <span class="font-bold text-white">${{ formatearMoneda(prestamo.pago_periodico) }}</span>
                    </div>
                    <div class="h-px bg-indigo-500/10 my-2"></div>
                    <div class="flex justify-between items-center group">
                      <span class="text-slate-300 font-bold group-hover:text-white">Total a pagar:</span>
                      <span class="font-black text-indigo-400 text-lg">${{ formatearMoneda(prestamo.monto_total_pagar) }}</span>
                    </div>
                  </div>
                </div>

                <!-- Progreso del préstamo -->
                <div class="bg-slate-950/50 rounded-xl p-5 border border-white/5">
                  <h4 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-4">Estado de Cuenta</h4>
                  <div class="space-y-3 text-sm">
                    <div class="flex justify-between items-center">
                      <span class="text-slate-400">Progreso:</span>
                      <span class="font-bold text-white">{{ prestamo.pagos_realizados }} <span class="text-slate-500">/</span> {{ prestamo.numero_pagos }}</span>
                    </div>
                     <!-- Progress bar -->
                    <div class="w-full bg-slate-800 rounded-full h-1.5 mb-2">
                       <div class="bg-indigo-500 h-1.5 rounded-full transition-all duration-500" :style="`width: ${(prestamo.pagos_realizados / prestamo.numero_pagos) * 100}%`"></div>
                    </div>

                    <div class="flex justify-between items-center">
                      <span class="text-slate-400">Monto pagado:</span>
                      <span class="font-bold text-emerald-400">${{ formatearMoneda(prestamo.monto_pagado) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                      <span class="text-slate-400">Monto pendiente:</span>
                      <span class="font-bold text-rose-400">${{ formatearMoneda(prestamo.monto_pendiente) }}</span>
                    </div>
                  </div>
                </div>

                <!-- Próximo pago -->
                <div v-if="pagos_pendientes.length > 0" class="bg-gradient-to-br from-indigo-900/40 to-indigo-800/20 rounded-xl p-5 border border-indigo-500/20 shadow-xl">
                  <h4 class="text-xs font-black text-indigo-200 uppercase tracking-wider mb-3">Próximo Vencimiento</h4>
                  <div class="space-y-2">
                    <div class="flex justify-between items-end">
                      <span class="text-indigo-300 text-xs uppercase font-bold">Pago #{{ pagos_pendientes[0].numero_pago }}</span>
                      <span class="font-black text-white text-xl">${{ formatearMoneda(pagos_pendientes[0].monto_programado) }}</span>
                    </div>
                    <div class="flex items-center text-indigo-200 border-t border-indigo-500/20 pt-2 mt-2">
                       <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                      <span class="font-bold text-sm">{{ formatearFecha(pagos_pendientes[0].fecha_programada) }}</span>
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
.pagos-create {
  min-height: 100vh;
}
</style>
