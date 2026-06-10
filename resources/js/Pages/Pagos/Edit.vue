<!-- /resources/js/Pages/Pagos/Edit.vue -->
<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { ref, computed, watch, onMounted } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

const props = defineProps({
  pago: {
    type: Object,
    required: true
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
  monto_pagado: props.pago.monto_pagado || 0,
  fecha_pago: props.pago.fecha_pago ? new Date(props.pago.fecha_pago).toISOString().split('T')[0] : '',
  metodo_pago: props.pago.metodo_pago || 'efectivo',
  referencia: props.pago.referencia || '',
  notas: props.pago.notas || '',
})

const loading = ref(false)

/* =========================
   Validación del formulario
========================= */
const errors = ref({})

const validateForm = () => {
  errors.value = {}

  if (!form.value.monto_pagado || form.value.monto_pagado < 0) {
    errors.value.monto_pagado = 'El monto no puede ser negativo'
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

  router.put(`/pagos/${props.pago.id}`, form.value, {
    onStart: () => {
      notyf.success('Actualizando pago...')
    },
    onSuccess: () => {
      notyf.success('Pago actualizado correctamente')
    },
    onError: (err) => {
      console.error('Errores de validación:', err)
      notyf.error('Error al actualizar el pago')
      errors.value = err
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
</script>

<template>
  <Head title="Modificar Pago" />

  <div class="pagos-edit min-h-screen bg-[var(--ui-surface)] text-slate-200 font-sans selection:bg-indigo-500/30">
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      <!-- Header Premium -->
      <div class="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-6 animate-in fade-in slide-in-from-top-4 duration-700">
        <div class="flex items-center gap-6">
           <div class="relative group">
              <div class="absolute -inset-1 bg-gradient-to-r from-brand-500 to-brand-600 rounded-2xl blur opacity-25 group-hover:opacity-50 transition duration-500"></div>
              <div class="relative w-16 h-16 rounded-2xl bg-slate-900 border border-white/10 flex items-center justify-center shadow-2xl backdrop-blur-xl">
                 <svg class="w-10 h-10 text-brand-400 group-hover:scale-105 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                 </svg>
              </div>
           </div>
           <div>
              <h1 class="text-4xl font-black text-white tracking-tighter mb-1 uppercase">
                Modificar <span class="bg-clip-text text-transparent bg-gradient-to-r from-brand-400 to-orange-400">Pago</span>
              </h1>
              <p class="text-slate-500 text-sm font-bold uppercase tracking-wide">Edición de registros históricos de cobranza</p>
           </div>
        </div>
 
        <div class="flex items-center gap-4">
           <Link
            :href="`/pagos/${pago.id}`"
            class="px-5 py-2.5 bg-black/50 border border-white/10 text-slate-400 text-[10px] font-black uppercase tracking-wide rounded-2xl hover:bg-slate-800 hover:text-white transition-all shadow-xl backdrop-blur-md flex items-center gap-2 group"
          >
            <svg class="w-4 h-4 text-indigo-500 group-hover:rotate-12 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
            Detalles
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
              <h2 class="text-lg font-bold text-white">Editar Información</h2>
            </div>

            <form @submit.prevent="submitForm" class="p-6 space-y-6">
              <!-- Grid de información del pago -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Monto pagado -->
                <div>
                  <label for="monto_pagado" class="block text-sm font-bold text-slate-300 mb-2">
                    Monto Pagado <span class="text-rose-500">*</span>
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
                      class="block w-full pl-8 pr-3 py-3 bg-slate-950 border rounded-2xl shadow-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-white font-bold placeholder-slate-600 transition-all border-white/10"
                      :class="errors.monto_pagado ? 'border-rose-500/50 focus:ring-brand-500' : ''"
                    />
                  </div>
                  <p v-if="errors.monto_pagado" class="mt-2 text-sm text-rose-500">{{ errors.monto_pagado }}</p>
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

              <!-- Notas -->
              <div>
                <label for="notas" class="block text-sm font-bold text-slate-300 mb-2">
                  Notas Adicionales
                </label>
                <textarea
                  id="notas"
                  v-model="form.notas"
                  rows="3"
                  placeholder="Notas adicionales sobre el pago"
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
                    Actualizando...
                  </span>
                  <span v-else>Guardar Cambios</span>
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Panel de información visual -->
        <div class="lg:col-span-1">
          <div class="bg-black/50 rounded-2xl shadow-xl border border-white/5 overflow-hidden sticky top-8 backdrop-blur-sm">
            <div class="px-6 py-5 border-b border-white/5 bg-slate-950/30">
              <h3 class="text-lg font-bold text-white">Contexto</h3>
            </div>
            
            <div class="p-6 space-y-6">
                <div class="bg-indigo-500/5 rounded-xl p-5 border border-indigo-500/10">
                    <h4 class="text-xs font-black text-indigo-400 uppercase tracking-wider mb-2">Original Programado</h4>
                    <p class="text-2xl font-black text-white">${{ formatearMoneda(pago.monto_programado) }}</p>
                    <p class="text-sm text-slate-400 mt-1">Fecha: {{ formatearFecha(pago.fecha_programada) }}</p>
                </div>

                 <div class="bg-slate-950/50 rounded-xl p-5 border border-white/5">
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-2">Estado Actual</h4>
                    <span :class="['font-bold text-xs inline-block', getEstadoColor(pago.estado)]">
                      {{ getEstadoLabel(pago.estado) }}
                    </span>
                 </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.pagos-edit {
  min-height: 100vh;
}
</style>
