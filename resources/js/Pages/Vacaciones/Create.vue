<template>
  <Head :title="props.empleadoSeleccionado && $page.props.auth.user && props.empleadoSeleccionado.id === $page.props.auth.user.id ? 'Solicitar Vacaciones' : (props.empleadoSeleccionado ? `Vacaciones para ${props.empleadoSeleccionado.name}` : 'Nueva Solicitud de Vacaciones')" />
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-green-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">
      <!-- Header -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-green-600 to-blue-600 rounded-full mb-4">
          <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
          {{ props.empleadoSeleccionado && $page.props.auth.user && props.empleadoSeleccionado.id === $page.props.auth.user.id ? 'Solicitar Vacaciones' : (props.empleadoSeleccionado ? `Vacaciones para ${props.empleadoSeleccionado.name}` : 'Nueva Solicitud de Vacaciones') }}
        </h1>
        <p class="text-gray-600">
          {{ props.empleadoSeleccionado && $page.props.auth.user && props.empleadoSeleccionado.id === $page.props.auth.user.id ? 'Completa la información para solicitar tus vacaciones' : (props.empleadoSeleccionado ? 'Crear vacaciones para el empleado seleccionado' : 'Completa la información para solicitar vacaciones') }}
        </p>
      </div>

      <!-- Form Card -->
      <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        <form @submit.prevent="submit" class="p-8 space-y-8">
          <!-- Información del Empleado -->
          <div class="space-y-6">
            <div class="border-b border-gray-200 pb-4">
              <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Información del Empleado
              </h2>
            </div>

            <!-- Si es el propio empleado, mostrar información en lugar del selector -->
            <div v-if="props.empleadoSeleccionado && $page.props.auth.user && props.empleadoSeleccionado.id === $page.props.auth.user.id" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <div class="flex items-center">
                <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <div>
                  <p class="text-sm font-medium text-blue-800">
Creando vacaciones para: <strong>{{ props.empleadoSeleccionado.name }}</strong>
                  </p>
                  <p class="text-xs text-blue-600">{{ props.empleadoSeleccionado.puesto }}</p>
                </div>
              </div>
              <input type="hidden" v-model="form.user_id" />
            </div>

            <!-- Si es administrador creando para otro empleado, mostrar selector -->
            <div v-else class="form-group">
              <label for="user_id" class="block text-sm font-semibold text-gray-700 mb-2">
                Empleado *
              </label>
              <select
                v-model="form.user_id"
                id="user_id"
                class="block w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-gray-50 hover:bg-white"
                :class="{
                  'border-red-300 bg-red-50 focus:ring-red-500': form.errors.user_id,
                  'border-green-300 bg-green-50': form.user_id && !form.errors.user_id
                }"
              >
                <option value="" disabled>Seleccionar empleado</option>
                <option v-for="empleado in empleados" :key="empleado.id" :value="empleado.id">
{{ empleado.name }} - {{ empleado.puesto }}
                </option>
              </select>
              <InputError class="mt-2" :message="form.errors.user_id" />
            </div>
          </div>

          <!-- Información de Vacaciones -->
          <div class="space-y-6">
            <div class="border-b border-gray-200 pb-4">
              <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Información de Vacaciones
              </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="form-group">
                <label for="fecha_inicio" class="block text-sm font-semibold text-gray-700 mb-2">
                  Fecha de Inicio *
                </label>
                <input
                  v-model="form.fecha_inicio"
                  type="date"
                  id="fecha_inicio"
                  :min="minDate"
                  class="block w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-gray-50 hover:bg-white"
                  :class="{
                    'border-red-300 bg-red-50 focus:ring-red-500': form.errors.fecha_inicio,
                    'border-green-300 bg-green-50': form.fecha_inicio && !form.errors.fecha_inicio
                  }"
                />
                <InputError class="mt-2" :message="form.errors.fecha_inicio" />
              </div>

              <div class="form-group">
                <label for="fecha_fin" class="block text-sm font-semibold text-gray-700 mb-2">
                  Fecha de Fin *
                </label>
                <input
                  v-model="form.fecha_fin"
                  type="date"
                  id="fecha_fin"
                  :min="form.fecha_inicio || minDate"
                  class="block w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-gray-50 hover:bg-white"
                  :class="{
                    'border-red-300 bg-red-50 focus:ring-red-500': form.errors.fecha_fin,
                    'border-green-300 bg-green-50': form.fecha_fin && !form.errors.fecha_fin
                  }"
                />
                <InputError class="mt-2" :message="form.errors.fecha_fin" />
              </div>
            </div>

            <!-- Información de días disponibles -->
            <div v-if="props.empleadoSeleccionado && props.registroVacaciones" class="bg-green-50 border border-green-200 rounded-lg p-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center">
                  <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  <div>
                    <p class="text-sm text-green-800">
                      Días disponibles: <strong>{{ props.registroVacaciones.dias_disponibles }}</strong>
                    </p>
                    <p class="text-xs text-green-600">
                      Año {{ props.registroVacaciones.anio }} • {{ props.registroVacaciones.dias_correspondientes }} días correspondientes
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Información de días solicitados -->
            <div v-if="form.fecha_inicio && form.fecha_fin" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center">
                  <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  <span class="text-sm text-blue-800">
                    Días solicitados: <strong>{{ diasSolicitados }}</strong>
                  </span>
                </div>

                <!-- Verificación de días disponibles -->
                <div v-if="props.registroVacaciones" class="flex items-center">
                  <span v-if="diasSolicitados <= props.registroVacaciones.dias_disponibles" class="text-green-600 text-sm">
                    ✅ Disponibles
                  </span>
                  <span v-else class="text-red-600 text-sm">
                    ❌ Insuficientes (necesitas {{ diasSolicitados - props.registroVacaciones.dias_disponibles }} más)
                  </span>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label for="motivo" class="block text-sm font-semibold text-gray-700 mb-2">
                Motivo
              </label>
              <textarea
                v-model="form.motivo"
                id="motivo"
                rows="3"
                class="block w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-gray-50 hover:bg-white"
                placeholder="Describe el motivo de las vacaciones (opcional)"
              ></textarea>
              <InputError class="mt-2" :message="form.errors.motivo" />
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="pt-6 border-t border-gray-200">
            <div class="flex flex-col sm:flex-row gap-4 justify-end">
              <Link :href="props.empleadoSeleccionado && $page.props.auth.user && props.empleadoSeleccionado.id === $page.props.auth.user.id ? route('vacaciones.mis-vacaciones') : route('vacaciones.index')"
                    class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-3 border border-gray-300 rounded-xl text-gray-700 bg-white hover:bg-gray-50 font-semibold transition-all duration-200 hover:shadow-md">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Cancelar
              </Link>
              <button
                type="submit"
                class="w-full sm:w-auto inline-flex justify-center items-center px-8 py-3 bg-gradient-to-r from-green-600 to-blue-600 hover:from-green-700 hover:to-blue-700 text-white font-semibold rounded-xl transition-all duration-200 hover:shadow-lg transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none disabled:hover:shadow-none"
                :disabled="form.processing || !isFormValid"
              >
                <div v-if="form.processing" class="flex items-center">
                  <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span>Enviando Solicitud...</span>
                </div>
                <div v-else class="flex items-center">
                  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                  </svg>
                  <span>Enviar Solicitud</span>
                </div>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import InputError from '@/Components/InputError.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import AppLayout from '@/Layouts/AppLayout.vue'
import { computed, ref } from 'vue'

defineOptions({
  layout: AppLayout,
  inheritAttrs: false
})

const props = defineProps({
  empleados: Array,
  empleadoSeleccionado: Object,
  registroVacaciones: Object,
})

const notyf = new Notyf({
  duration: 3000,
  position: { x: 'right', y: 'top' },
  ripple: true,
  dismissible: true
})

const minDate = new Date().toISOString().split('T')[0]

const form = useForm({
  user_id: props.empleadoSeleccionado?.id || '',
  fecha_inicio: '',
  fecha_fin: '',
  motivo: '',
})

// Debug toggle: add ?debugVacaciones=1 or set localStorage.debugVacaciones = '1'
const DEBUG = computed(() => {
  try {
    const hasQuery = typeof window !== 'undefined' && new URLSearchParams(window.location.search).has('debugVacaciones')
    const ls = typeof window !== 'undefined' && window.localStorage?.getItem('debugVacaciones') === '1'
    return !!(hasQuery || ls)
  } catch { return false }
})

const diasSolicitados = computed(() => {
  if (!form.fecha_inicio || !form.fecha_fin) return 0

  try {
    const inicio = new Date(form.fecha_inicio)
    const fin = new Date(form.fecha_fin)
    const diffTime = Math.abs(fin - inicio)
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1
    return diffDays
  } catch {
    return 0
  }
})

const isFormValid = computed(() => {
  return form.user_id &&
         form.fecha_inicio &&
         form.fecha_fin &&
         form.fecha_inicio <= form.fecha_fin
})

const submit = () => {
  try {
    if (DEBUG.value) {
      // eslint-disable-next-line no-console
      console.log('[Vacaciones][Create] Enviando', {
        route: route('vacaciones.store'),
        data: { ...form }
      })
    }
    // Log de payload enviado (incondicional)
    // eslint-disable-next-line no-console
    console.log('[Vacaciones][Create] Payload a enviar', {
      ...form,
      diasSolicitados: diasSolicitados.value
    })
    form.post(route('vacaciones.store'), {
      onSuccess: (page) => {
        const empleadoNombre = props.empleadoSeleccionado ? props.empleadoSeleccionado.name : 'el empleado'
        notyf.success(`Solicitud de vacaciones para ${empleadoNombre} enviada exitosamente.`)
        if (DEBUG.value) {
          // eslint-disable-next-line no-console
          console.log('[Vacaciones][Create] Success', { page })
        }
        form.reset()
      },
      onError: (errors) => {
        // Log detallado de errores y payload
        // eslint-disable-next-line no-console
        console.error('[Vacaciones][Create] onError', {
          errors,
          sent: { ...form },
          diasSolicitados: diasSolicitados.value
        })
        // eslint-disable-next-line no-console
        console.error('[Vacaciones][Create] Errores de validación', errors)
        notyf.error('Error al enviar la solicitud. Revisa los campos.')

        const firstErrorField = Object.keys(errors)[0]
        if (firstErrorField) {
          const element = document.getElementById(firstErrorField)
          if (element) {
            element.scrollIntoView({ behavior: 'smooth', block: 'center' })
            element.focus()
          }
        }
      },
      onFinish: () => {
        if (DEBUG.value) {
          // eslint-disable-next-line no-console
          console.log('[Vacaciones][Create] Finalizado')
        }
      }
    })
  } catch (e) {
    // eslint-disable-next-line no-console
    console.error('[Vacaciones][Create] Error inesperado', e)
    notyf.error('Ocurrió un error inesperado al enviar la solicitud.')
  }
}
</script>

<style scoped>
@import "tailwindcss" reference;
.form-group {
  margin-bottom: 1.5rem;
}

/* Estilos para el input focus */
input:focus, select:focus, textarea:focus {
  outline: none;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
}

/* Animación para los botones */
button:not(:disabled):hover {
  transform: translateY(-1px);
}

button:disabled {
  background-color: #d1d5db;
  cursor: not-allowed;
  transform: none;
}

/* Estilos para el select personalizado */
select {
  background-image: none;
}

/* Animaciones suaves */
.transition-all {
  transition: all 0.2s ease-in-out;
}

/* Efecto hover para los inputs */
input:hover:not(:focus), select:hover:not(:focus), textarea:hover:not(:focus) {
  border-color: #9ca3af;
}

/* Estilo para campos válidos */
.border-green-300 {
  border-color: #86efac;
}

.bg-green-50 {
  background-color: #f0fdf4;
}

/* Estilo para campos con error */
.border-red-300 {
  border-color: #fca5a5;
}

.bg-red-50 {
  background-color: #fef2f2;
}
</style>

