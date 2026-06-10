<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputError from '@/Components/InputError.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

const props = defineProps({
  empleados: { type: Array, default: () => [] },
  catalogoPercepciones: { type: Array, default: () => [] },
  catalogoDeducciones: { type: Array, default: () => [] },
  empleadoPreseleccionado: { type: Object, default: null },
  tiposPeriodo: { type: Array, default: () => [] },
})

const notyf = new Notyf({ duration: 4000, position: { x: 'right', y: 'top' } })

const page = usePage()
onMounted(() => {
  if (props.empleadoPreseleccionado) {
    form.empleado_id = props.empleadoPreseleccionado.id
    actualizarSalarioBase()
  }
})

const form = useForm({
  empleado_id: '',
  periodo_inicio: '',
  periodo_fin: '',
  tipo_periodo: 'quincenal',
  dias_trabajados: 15,
  horas_extra: 0,
  monto_horas_extra: 0,
  notas: '',
  conceptos: [],
})

const empleadoSeleccionado = computed(() => {
  return props.empleados.find(e => e.value == form.empleado_id)
})

const actualizarSalarioBase = () => {
  if (empleadoSeleccionado.value) {
    const salarioMensual = empleadoSeleccionado.value.salario_base || 0
    let salarioPeriodo = salarioMensual

    switch (form.tipo_periodo) {
      case 'semanal':
        salarioPeriodo = salarioMensual / 4
        form.dias_trabajados = 7
        break
      case 'quincenal':
        salarioPeriodo = salarioMensual / 2
        form.dias_trabajados = 15
        break
      case 'mensual':
        salarioPeriodo = salarioMensual
        form.dias_trabajados = 30
        break
    }
  }
}

const calcularPeriodo = () => {
  if (!form.periodo_inicio) return

  const inicio = new Date(form.periodo_inicio)
  let fin = new Date(inicio)

  switch (form.tipo_periodo) {
    case 'semanal':
      fin.setDate(fin.getDate() + 6)
      break
    case 'quincenal':
      fin.setDate(fin.getDate() + 14)
      break
    case 'mensual':
      fin.setMonth(fin.getMonth() + 1)
      fin.setDate(fin.getDate() - 1)
      break
  }

  form.periodo_fin = fin.toISOString().split('T')[0]
  actualizarSalarioBase()
}

// Conceptos adicionales
const agregarConcepto = (tipo) => {
  form.conceptos.push({
    tipo: tipo,
    concepto: '',
    clave: '',
    monto: 0,
  })
}

const eliminarConcepto = (index) => {
  form.conceptos.splice(index, 1)
}

const submit = () => {
  form.post('/nominas', {
    onSuccess: () => notyf.success('Nómina creada exitosamente'),
    onError: () => notyf.error('Error al crear la nómina'),
  })
}

const cancelar = () => router.visit('/nominas')

const formatearMoneda = (num) => {
  const value = parseFloat(num)
  return isNaN(value) ? '$0.00' : `$${value.toLocaleString('es-MX', { minimumFractionDigits: 2 })}`
}
</script>

<template>
  <Head title="Nueva Nómina" />

  <div class="min-h-screen bg-[var(--ui-surface)] py-12 px-4 sm:px-6 lg:px-8">
    <div class="w-full">
      <!-- Header -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-emerald-600 to-teal-600 rounded-full mb-4">
          <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Nueva Nómina</h1>
        <p class="text-slate-500">Genera una nómina para un empleado</p>
      </div>

      <!-- Formulario -->
      <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
        <form @submit.prevent="submit" class="p-8 space-y-6">

          <!-- Selección de Empleado -->
          <div class="space-y-6">
            <div class="border-b border-slate-200 pb-4">
              <h2 class="text-lg font-semibold text-slate-900">Empleado y Período</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-2">Empleado *</label>
                <select
                  v-model="form.empleado_id"
                  @change="actualizarSalarioBase"
                  class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500"
                  required
                >
                  <option value="">Seleccionar empleado...</option>
                  <option v-for="emp in empleados" :key="emp.value" :value="emp.value">
                    {{ emp.label }} - {{ emp.numero_empleado }} ({{ formatearMoneda(emp.salario_base) }}/mes)
                  </option>
                </select>
                <InputError :message="form.errors.empleado_id" class="mt-2" />
              </div>

              <div v-if="empleadoSeleccionado" class="md:col-span-2 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/30 rounded-xl p-4">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="font-medium text-emerald-800 dark:text-emerald-200">{{ empleadoSeleccionado.label }}</p>
                    <p class="text-sm text-emerald-600">{{ empleadoSeleccionado.numero_empleado }}</p>
                  </div>
                  <div class="text-right">
                    <p class="text-sm text-emerald-600">Salario mensual</p>
                    <p class="text-lg font-bold text-emerald-800 dark:text-emerald-200">{{ formatearMoneda(empleadoSeleccionado.salario_base) }}</p>
                  </div>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Tipo de Período *</label>
                <select
                  v-model="form.tipo_periodo"
                  @change="calcularPeriodo"
                  class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500"
                  required
                >
                  <option v-for="tipo in tiposPeriodo" :key="tipo.value" :value="tipo.value">
                    {{ tipo.label }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Días Trabajados</label>
                <input
                  v-model="form.dias_trabajados"
                  type="number"
                  step="0.5"
                  min="0"
                  max="31"
                  class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Fecha Inicio *</label>
                <input
                  v-model="form.periodo_inicio"
                  @change="calcularPeriodo"
                  type="date"
                  class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500"
                  required
                />
                <InputError :message="form.errors.periodo_inicio" class="mt-2" />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Fecha Fin *</label>
                <input
                  v-model="form.periodo_fin"
                  type="date"
                  class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500"
                  required
                />
                <InputError :message="form.errors.periodo_fin" class="mt-2" />
              </div>
            </div>
          </div>

          <!-- Horas Extra -->
          <div class="space-y-6">
            <div class="border-b border-slate-200 pb-4">
              <h2 class="text-lg font-semibold text-slate-900">Horas Extra</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Horas Extra</label>
                <input
                  v-model="form.horas_extra"
                  type="number"
                  step="0.5"
                  min="0"
                  class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Monto Horas Extra</label>
                <div class="relative">
                  <span class="absolute left-3 top-3 text-slate-500">$</span>
                  <input
                    v-model="form.monto_horas_extra"
                    type="number"
                    step="0.01"
                    min="0"
                    class="w-full pl-8 pr-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Conceptos Adicionales -->
          <div class="space-y-6">
            <div class="border-b border-slate-200 pb-4">
              <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Conceptos Adicionales</h2>
                <div class="space-x-2">
                  <button type="button" @click="agregarConcepto('percepcion')" class="px-3 py-1 text-sm bg-emerald-100 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 rounded-xl hover:bg-emerald-200">
                    + Percepción
                  </button>
                  <button type="button" @click="agregarConcepto('deduccion')" class="px-3 py-1 text-sm bg-rose-100 text-rose-800 dark:text-rose-200 dark:text-rose-200 rounded-xl hover:bg-rose-200">
                    + Deducción
                  </button>
                </div>
              </div>
              <p class="text-sm text-slate-500 mt-1">El sueldo base y deducciones por préstamos se agregan automáticamente</p>
            </div>

            <div v-if="form.conceptos.length" class="space-y-6">
              <div
                v-for="(concepto, index) in form.conceptos"
                :key="index"
                :class="['border rounded-xl p-4', concepto.tipo === 'percepcion' ? 'border-emerald-200 dark:border-emerald-800/30 bg-emerald-50 dark:bg-emerald-900/20' : 'border-rose-200 dark:border-rose-800/30 bg-rose-50 dark:bg-rose-900/20']"
              >
                <div class="flex items-start justify-between mb-3">
                  <span :class="['text-xs font-semibold px-2 py-1 rounded-full', concepto.tipo === 'percepcion' ? 'bg-emerald-200 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200' : 'bg-rose-200 text-rose-800 dark:text-rose-200 dark:text-rose-200']">
                    {{ concepto.tipo === 'percepcion' ? 'Percepción' : 'Deducción' }}
                  </span>
                  <button type="button" @click="eliminarConcepto(index)" class="text-slate-400 hover:text-rose-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>

                <div class="grid grid-cols-3 gap-4">
                  <div class="col-span-2">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Concepto</label>
                    <input
                      v-model="concepto.concepto"
                      type="text"
                      placeholder="Nombre del concepto"
                      class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-brand-500"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Monto</label>
                    <input
                      v-model="concepto.monto"
                      type="number"
                      step="0.01"
                      min="0"
                      class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-brand-500"
                    />
                  </div>
                </div>
              </div>
            </div>

            <div v-else class="py-8 text-center text-slate-500 bg-white rounded-xl">
              <p class="text-sm">No hay conceptos adicionales</p>
              <p class="text-xs mt-1">El sueldo base se agregará automáticamente</p>
            </div>
          </div>

          <!-- Notas -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Notas</label>
            <textarea
              v-model="form.notas"
              rows="3"
              placeholder="Observaciones de la nómina..."
              class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand-500"
            ></textarea>
          </div>

          <!-- Botones -->
          <div class="flex items-center justify-end space-x-4 pt-6 border-t border-slate-200">
            <button type="button" @click="cancelar" class="px-6 py-3 text-slate-700 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="form.processing"
              class="px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-semibold rounded-2xl shadow-sm hover:from-emerald-700 hover:to-teal-700 transition-all duration-200 disabled:opacity-50"
            >
              <span v-if="form.processing">Guardando...</span>
              <span v-else>Crear Nómina</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
