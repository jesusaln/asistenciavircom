<script setup>
import { ref, computed } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputError from '@/Components/InputError.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

const props = defineProps({
  usuariosDisponibles: { type: Array, default: () => [] },
  departamentos: { type: Array, default: () => [] },
  puestos: { type: Array, default: () => [] },
  tiposContrato: { type: Array, default: () => [] },
  tiposJornada: { type: Array, default: () => [] },
  frecuenciasPago: { type: Array, default: () => [] },
})

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
})

const form = useForm({
  user_id: '',
  numero_empleado: '',
  fecha_nacimiento: '',
  curp: '',
  rfc: '',
  nss: '',
  direccion: '',
  puesto: '',
  departamento: '',
  fecha_contratacion: '',
  salario_base: '',
  tipo_contrato: 'tiempo_completo',
  tipo_jornada: 'diurna',
  horas_jornada: 8,
  hora_entrada: '08:00',
  hora_salida: '17:00',
  trabaja_sabado: false,
  hora_entrada_sabado: '08:00',
  hora_salida_sabado: '14:00',
  frecuencia_pago: 'quincenal',
  banco: '',
  numero_cuenta: '',
  clabe_interbancaria: '',
  contacto_emergencia_nombre: '',
  contacto_emergencia_telefono: '',
  contacto_emergencia_parentesco: '',
  observaciones: '',
  contrato_adjunto: null,
})

// Helper to cleaner parsing
const cleanNumber = (val) => {
  if (!val) return 0
  if (typeof val === 'number') return val
  // Remove commas if present and parse
  const clean = val.toString().replace(/,/g, '')
  return parseFloat(clean) || 0
}

// Computed: Salario diario (salario base / 30 días)
const salarioDiario = computed(() => {
  const base = cleanNumber(form.salario_base)
  return base / 30
})

// Computed: Salario por periodo según frecuencia
const salarioPorPeriodo = computed(() => {
  const base = cleanNumber(form.salario_base)
  if (form.frecuencia_pago === 'semanal') {
    return base / 4 // 4 semanas al mes
  } else if (form.frecuencia_pago === 'quincenal') {
    return base / 2 // 2 quincenas al mes
  }
  return base // mensual
})

// Computed: Total mensual (para verificación)
const totalMensual = computed(() => {
  if (form.frecuencia_pago === 'semanal') {
    return salarioPorPeriodo.value * 4
  } else if (form.frecuencia_pago === 'quincenal') {
    return salarioPorPeriodo.value * 2
  }
  return cleanNumber(form.salario_base)
})

// Computed: Número de pagos al mes
const pagosPorMes = computed(() => {
  if (form.frecuencia_pago === 'semanal') return 4
  if (form.frecuencia_pago === 'quincenal') return 2
  return 1
})

// Formato de moneda seguro
const formatCurrency = (value) => {
  try {
    const num = cleanNumber(value)
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(num)
  } catch (e) {
    console.error('Error formatting currency:', e)
    return '$0.00'
  }
}

const submit = () => {
  form.post('/empleados', {
    onSuccess: () => notyf.success('Empleado creado exitosamente'),
    onError: () => notyf.error('Error al crear el empleado'),
  })
}

const cancelar = () => {
  router.visit('/empleados')
}

const usuarioSeleccionado = computed(() => {
  return props.usuariosDisponibles.find(u => u.id == form.user_id)
})
</script>

<template>
  <Head title="Nuevo Empleado" />

  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-emerald-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-emerald-600 to-teal-600 rounded-full mb-4">
          <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Nuevo Empleado</h1>
        <p class="text-gray-600">Registra un nuevo empleado en el sistema de RRHH</p>
      </div>

      <!-- Formulario -->
      <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        <form @submit.prevent="submit" class="p-8 space-y-8">

          <!-- Selección de Usuario -->
          <div class="space-y-6">
            <div class="border-b border-gray-200 pb-4">
              <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                <svg class="w-5 h-5 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Usuario del Sistema
              </h2>
              <p class="text-sm text-gray-600 mt-1">Selecciona el usuario que será registrado como empleado</p>
            </div>

            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">Usuario *</label>
              <select
                v-model="form.user_id"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                required
              >
                <option value="">Seleccionar usuario...</option>
                <option v-for="user in usuariosDisponibles" :key="user.id" :value="user.id">
                  {{ user.name }} ({{ user.email }})
                </option>
              </select>
              <InputError :message="form.errors.user_id" class="mt-2" />
            </div>

            <div v-if="usuarioSeleccionado" class="bg-emerald-50 border border-emerald-200 rounded-lg p-4">
              <p class="text-sm text-emerald-800">
                <strong>Seleccionado:</strong> {{ usuarioSeleccionado.name }} - {{ usuarioSeleccionado.email }}
              </p>
            </div>
          </div>

          <!-- Información Personal -->
          <div class="space-y-6">
            <div class="border-b border-gray-200 pb-4">
              <h2 class="text-lg font-semibold text-gray-900">Información Personal</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Número de Empleado</label>
                <input
                  v-model="form.numero_empleado"
                  type="text"
                  placeholder="Se genera automáticamente"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
                />
                <InputError :message="form.errors.numero_empleado" class="mt-2" />
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Fecha de Nacimiento</label>
                <input
                  v-model="form.fecha_nacimiento"
                  type="date"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
                />
                <InputError :message="form.errors.fecha_nacimiento" class="mt-2" />
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">CURP</label>
                <input
                  v-model="form.curp"
                  type="text"
                  maxlength="18"
                  placeholder="18 caracteres"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 uppercase"
                />
                <InputError :message="form.errors.curp" class="mt-2" />
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">RFC</label>
                <input
                  v-model="form.rfc"
                  type="text"
                  maxlength="13"
                  placeholder="13 caracteres"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 uppercase"
                />
                <InputError :message="form.errors.rfc" class="mt-2" />
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">NSS (Seguro Social)</label>
                <input
                  v-model="form.nss"
                  type="text"
                  maxlength="11"
                  placeholder="11 dígitos"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
                />
                <InputError :message="form.errors.nss" class="mt-2" />
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Dirección</label>
                <input
                  v-model="form.direccion"
                  type="text"
                  placeholder="Dirección completa"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
                />
                <InputError :message="form.errors.direccion" class="mt-2" />
              </div>
            </div>
          </div>

          <!-- Información Laboral -->
          <div class="space-y-6">
            <div class="border-b border-gray-200 pb-4">
              <h2 class="text-lg font-semibold text-gray-900">Información Laboral</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Puesto</label>
                <input
                  v-model="form.puesto"
                  type="text"
                  list="puestos-list"
                  placeholder="Ej: Desarrollador, Vendedor..."
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
                />
                <datalist id="puestos-list">
                  <option v-for="p in puestos" :key="p" :value="p" />
                </datalist>
                <InputError :message="form.errors.puesto" class="mt-2" />
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Departamento</label>
                <input
                  v-model="form.departamento"
                  type="text"
                  list="departamentos-list"
                  placeholder="Ej: Ventas, TI, RRHH..."
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
                />
                <datalist id="departamentos-list">
                  <option v-for="d in departamentos" :key="d" :value="d" />
                </datalist>
                <InputError :message="form.errors.departamento" class="mt-2" />
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Fecha de Contratación</label>
                <input
                  v-model="form.fecha_contratacion"
                  type="date"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
                />
                <InputError :message="form.errors.fecha_contratacion" class="mt-2" />
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Contrato *</label>
                <select
                  v-model="form.tipo_contrato"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
                >
                  <option v-for="tipo in tiposContrato" :key="tipo.value" :value="tipo.value">
                    {{ tipo.label }}
                  </option>
                </select>
                <InputError :message="form.errors.tipo_contrato" class="mt-2" />
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Jornada</label>
                <select
                  v-model="form.tipo_jornada"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
                >
                  <option v-for="tipo in tiposJornada" :key="tipo.value" :value="tipo.value">
                    {{ tipo.label }}
                  </option>
                </select>
              </div>
            </div>

            <!-- Horario de Trabajo -->
            <div class="bg-blue-50 rounded-xl p-6 border border-blue-200">
              <h3 class="font-semibold text-blue-800 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Horario de Trabajo
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-semibold text-gray-700 mb-2">Hora de Entrada</label>
                  <input 
                    v-model="form.hora_entrada" 
                    type="time" 
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-lg"
                  />
                </div>
                <div>
                  <label class="block text-sm font-semibold text-gray-700 mb-2">Hora de Salida</label>
                  <input 
                    v-model="form.hora_salida" 
                    type="time" 
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-lg"
                  />
                </div>
                <div class="flex items-end">
                  <div class="w-full bg-white rounded-lg p-4 border border-blue-100 text-center">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Horario L-V</p>
                    <p class="text-xl font-bold text-blue-600">{{ form.hora_entrada }} - {{ form.hora_salida }}</p>
                  </div>
                </div>
              </div>

              <!-- Horario de Sábado -->
              <div class="mt-4 pt-4 border-t border-blue-200">
                <div class="flex items-center justify-between mb-4">
                  <div class="flex items-center">
                    <label class="relative inline-flex items-center cursor-pointer">
                      <input type="checkbox" v-model="form.trabaja_sabado" class="sr-only peer">
                      <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                    <span class="ml-3 text-sm font-semibold text-gray-700">Trabaja los Sábados</span>
                  </div>
                  <span v-if="form.trabaja_sabado" class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">Activo</span>
                </div>

                <div v-if="form.trabaja_sabado" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Entrada Sábado</label>
                    <input 
                      v-model="form.hora_entrada_sabado" 
                      type="time" 
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-lg"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Salida Sábado</label>
                    <input 
                      v-model="form.hora_salida_sabado" 
                      type="time" 
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-lg"
                    />
                  </div>
                  <div class="flex items-end">
                    <div class="w-full bg-white rounded-lg p-4 border border-blue-100 text-center">
                      <p class="text-xs text-gray-500 uppercase tracking-wide">Horario Sábado</p>
                      <p class="text-xl font-bold text-blue-600">{{ form.hora_entrada_sabado }} - {{ form.hora_salida_sabado }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Información Salarial -->
          <div class="space-y-6">
            <div class="border-b border-gray-200 pb-4">
              <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Información Salarial
              </h2>
              <p class="text-sm text-gray-600 mt-1">Configura el salario y la frecuencia de pago del empleado</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Frecuencia de Pago -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Frecuencia de Pago *</label>
                <div class="grid grid-cols-2 gap-3">
                  <label 
                    class="relative flex items-center justify-center p-4 border-2 rounded-xl cursor-pointer transition-all"
                    :class="form.frecuencia_pago === 'semanal' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 hover:border-gray-300'"
                  >
                    <input type="radio" v-model="form.frecuencia_pago" value="semanal" class="sr-only" />
                    <div class="text-center">
                      <svg class="w-6 h-6 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      <span class="font-semibold">Semanal</span>
                      <span class="block text-xs opacity-75">4 pagos/mes</span>
                    </div>
                  </label>
                  <label 
                    class="relative flex items-center justify-center p-4 border-2 rounded-xl cursor-pointer transition-all"
                    :class="form.frecuencia_pago === 'quincenal' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 hover:border-gray-300'"
                  >
                    <input type="radio" v-model="form.frecuencia_pago" value="quincenal" class="sr-only" />
                    <div class="text-center">
                      <svg class="w-6 h-6 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                      </svg>
                      <span class="font-semibold">Quincenal</span>
                      <span class="block text-xs opacity-75">2 pagos/mes</span>
                    </div>
                  </label>
                </div>
                <InputError :message="form.errors.frecuencia_pago" class="mt-2" />
              </div>

              <!-- Salario Base Mensual -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Salario Base Mensual *</label>
                <div class="relative">
                  <span class="absolute left-3 top-3 text-gray-500 font-medium">$</span>
                  <input
                    v-model="form.salario_base"
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="0.00"
                    class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 text-lg font-semibold"
                  />
                </div>
                <p class="text-xs text-gray-500 mt-1">Este es el salario total mensual del empleado</p>
                <InputError :message="form.errors.salario_base" class="mt-2" />
              </div>
            </div>

            <!-- Resumen de Salarios Calculados -->
            <div v-if="form.salario_base" class="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl p-6 border border-emerald-200">
              <h3 class="font-semibold text-emerald-800 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                Desglose Salarial
              </h3>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg p-4 border border-emerald-100">
                  <p class="text-xs text-gray-500 uppercase tracking-wide">Salario Diario</p>
                  <p class="text-xl font-bold text-gray-900">{{ formatCurrency(salarioDiario) }}</p>
                  <p class="text-xs text-gray-400">Base / 30 días</p>
                </div>
                <div class="bg-white rounded-lg p-4 border border-emerald-100">
                  <p class="text-xs text-gray-500 uppercase tracking-wide">Pago {{ form.frecuencia_pago === 'semanal' ? 'Semanal' : 'Quincenal' }}</p>
                  <p class="text-xl font-bold text-emerald-600">{{ formatCurrency(salarioPorPeriodo) }}</p>
                  <p class="text-xs text-gray-400">{{ pagosPorMes }} pagos/mes</p>
                </div>
                <div class="bg-white rounded-lg p-4 border border-emerald-100">
                  <p class="text-xs text-gray-500 uppercase tracking-wide">Total Mensual</p>
                  <p class="text-xl font-bold text-gray-900">{{ formatCurrency(totalMensual) }}</p>
                  <p class="text-xs text-gray-400">Verificación</p>
                </div>
                <div class="bg-white rounded-lg p-4 border border-emerald-100">
                  <p class="text-xs text-gray-500 uppercase tracking-wide">Diferencia</p>
                  <p class="text-xl font-bold text-green-600">
                    {{ formatCurrency(0) }}
                  </p>
                  <p class="text-xs text-green-500">
                    ✓ Cuadra
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Información Bancaria -->
          <div class="space-y-6">
            <div class="border-b border-gray-200 pb-4">
              <h2 class="text-lg font-semibold text-gray-900">Información Bancaria</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Banco</label>
                <input
                  v-model="form.banco"
                  type="text"
                  placeholder="Ej: BBVA, Santander..."
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
                />
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Número de Cuenta</label>
                <input
                  v-model="form.numero_cuenta"
                  type="text"
                  placeholder="Número de cuenta"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
                />
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">CLABE Interbancaria</label>
                <input
                  v-model="form.clabe_interbancaria"
                  type="text"
                  maxlength="18"
                  placeholder="18 dígitos"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
                />
              </div>
            </div>
          </div>

          <!-- Contacto de Emergencia -->
          <div class="space-y-6">
            <div class="border-b border-gray-200 pb-4">
              <h2 class="text-lg font-semibold text-gray-900">Contacto de Emergencia</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Nombre</label>
                <input
                  v-model="form.contacto_emergencia_nombre"
                  type="text"
                  placeholder="Nombre completo"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
                />
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Teléfono</label>
                <input
                  v-model="form.contacto_emergencia_telefono"
                  type="tel"
                  placeholder="Teléfono de contacto"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
                />
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Parentesco</label>
                <input
                  v-model="form.contacto_emergencia_parentesco"
                  type="text"
                  placeholder="Ej: Esposo/a, Padre, Madre..."
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
                />
              </div>
            </div>
          </div>

          
          <!-- Contrato Adjunto -->
          <div class="space-y-6">
            <div class="border-b border-gray-200 pb-4">
              <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                <svg class="w-5 h-5 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Contrato Físico
              </h2>
              <p class="text-sm text-gray-600 mt-1">Adjunta el contrato firmado en formato PDF o imagen</p>
            </div>

            <div class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl p-8 flex flex-col items-center justify-center transition-all hover:border-emerald-400 group">
              <input
                type="file"
                id="contrato_adjunto"
                @input="form.contrato_adjunto = $event.target.files[0]"
                class="hidden"
                accept=".pdf,image/*"
              />
              <label for="contrato_adjunto" class="cursor-pointer flex flex-col items-center">
                <div class="w-12 h-12 bg-white rounded-full shadow-sm flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                  <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                  </svg>
                </div>
                <span class="text-sm font-medium text-gray-700">
                  {{ form.contrato_adjunto ? form.contrato_adjunto.name : 'Seleccionar archivo' }}
                </span>
                <span class="text-xs text-gray-500 mt-1">PDF, JPG o PNG hasta 5MB</span>
              </label>
              <button
                v-if="form.contrato_adjunto"
                type="button"
                @click="form.contrato_adjunto = null"
                class="mt-2 text-xs text-red-600 hover:underline"
              >
                Eliminar archivo
              </button>
            </div>
            <InputError :message="form.errors.contrato_adjunto" class="mt-2" />
          </div>

          <!-- Observaciones -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Observaciones</label>
            <textarea
              v-model="form.observaciones"
              rows="3"
              placeholder="Notas adicionales sobre el empleado..."
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
            ></textarea>
          </div>

          <!-- Botones -->
          <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
            <button
              type="button"
              @click="cancelar"
              class="px-6 py-3 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="form.processing"
              class="px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-semibold rounded-lg shadow-sm hover:from-emerald-700 hover:to-teal-700 transition-all duration-200 disabled:opacity-50"
            >
              <span v-if="form.processing">Guardando...</span>
              <span v-else>Crear Empleado</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
