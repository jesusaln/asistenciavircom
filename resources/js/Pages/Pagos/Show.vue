<!-- /resources/js/Pages/Pagos/Show.vue -->
<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
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

const formatearFechaCompleta = (date) => {
  if (!date) return 'Fecha no disponible';
  try {
    const time = new Date(date).getTime();
    if (Number.isNaN(time)) return 'Fecha inválida';
    return new Date(time).toLocaleDateString('es-MX', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  } catch {
    return 'Fecha inválida';
  }
}

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

const getMetodoPagoLabel = (metodo) => {
  const labels = {
    'efectivo': 'Efectivo',
    'transferencia': 'Transferencia Bancaria',
    'tarjeta_debito': 'Tarjeta de Débito',
    'tarjeta_credito': 'Tarjeta de Crédito',
    'cheque': 'Cheque',
    'otro': 'Otro'
  }
  return labels[metodo] || 'No especificado'
}

// Propiedades computadas
const progreso = computed(() => {
  if (props.pago.monto_programado == 0) return 0;
  return Math.round((props.pago.monto_pagado / props.pago.monto_programado) * 100);
})

const montoPendiente = computed(() => {
  return Math.max(0, props.pago.monto_programado - props.pago.monto_pagado);
})

const tieneHistorial = computed(() => {
  return props.pago.historial_pagos && props.pago.historial_pagos.length > 0;
})
</script>

<template>
  <Head title="Detalles de Pago" />

  <div class="pagos-show min-h-screen bg-[var(--ui-surface)] text-slate-200 font-sans selection:bg-indigo-500/30">
    <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-12 py-10">
      <!-- Header -->
      <div class="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center gap-5">
           <div class="w-16 h-16 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center shadow-[0_0_15px_rgba(99,102,241,0.15)] backdrop-blur-sm">
              <svg class="w-10 h-10 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
           </div>
           <div>
              <h1 class="text-2xl font-black text-white tracking-tight mb-1">
                Detalles de Pago
              </h1>
              <p class="text-slate-400 text-sm font-medium">Información completa del pago #{{ pago.numero_pago }}</p>
           </div>
        </div>

        <div class="flex items-center space-x-3">
          <Link
            v-if="pago.estado !== 'pagado'"
            :href="`/pagos/create?prestamo_id=${pago.prestamo_id}&pago_id=${pago.id}`"
             class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-sm font-bold rounded-xl hover:bg-slate-500 shadow-xl shadow-emerald-600/20 transition-all transform hover:scale-105"
          >
             <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
            Registrar Pago
          </Link>
          <Link
            href="/pagos"
            class="inline-flex items-center px-4 py-2 bg-slate-900 border border-white/10 text-slate-300 text-sm font-bold rounded-xl hover:bg-slate-800 hover:text-white transition-all shadow-xl"
          >
            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            Volver a Pagos
          </Link>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Información principal -->
        <div class="lg:col-span-2 space-y-6">
          <div class="relative group">
            <div class="absolute -inset-0.5 bg-gradient-to-br from-indigo-500/20 to-purple-600/20 rounded-3xl blur-xl opacity-0 group-hover:opacity-100 transition duration-700"></div>
            <div class="relative bg-black/50 border border-white/10 rounded-3xl shadow-2xl backdrop-blur-xl overflow-hidden">
              <div class="px-8 py-6 border-b border-white/5 bg-slate-950/30 flex items-center justify-between">
                <h2 class="text-xl font-black text-white tracking-tight uppercase">Información General</h2>
                <div :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wide border shadow-xl', getEstadoColor(pago.estado)]">
                  {{ getEstadoLabel(pago.estado) }}
                </div>
              </div>
  
              <div class="p-8 space-y-10">
                <!-- Información básica (Grid) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                  <div class="space-y-6">
                    <h3 class="text-[10px] font-black text-indigo-400 uppercase tracking-[0.2em] flex items-center gap-2">
                       <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                       Datos del Pago
                    </h3>
                    <div class="space-y-6">
                      <div class="flex justify-between items-center group/item p-3 rounded-2xl hover:bg-white/[0.03] transition-colors border border-transparent hover:border-white/5">
                        <span class="text-slate-500 text-xs font-bold uppercase tracking-wide group-hover/item:text-slate-300 transition-colors">Número de Pago</span>
                        <span class="font-mono text-indigo-300 bg-indigo-500/10 px-3 py-1 rounded-xl text-xs border border-indigo-500/20 shadow-sm font-black">#{{ pago.numero_pago }}</span>
                      </div>
                      <div class="flex justify-between items-center group/item p-3 rounded-2xl hover:bg-white/[0.03] transition-colors border border-transparent hover:border-white/5">
                        <span class="text-slate-500 text-xs font-bold uppercase tracking-wide group-hover/item:text-slate-300 transition-colors">Fecha Programada</span>
                        <span class="font-black text-white text-sm tracking-tight">{{ formatearFecha(pago.fecha_programada) }}</span>
                      </div>
                      <div class="flex justify-between items-center group/item p-3 rounded-2xl hover:bg-white/[0.03] transition-colors border border-transparent hover:border-white/5">
                        <span class="text-slate-500 text-xs font-bold uppercase tracking-wide group-hover/item:text-slate-300 transition-colors">Estado Actual</span>
                        <span :class="['font-black text-[10px] uppercase tracking-wide px-3 py-1 rounded-xl border', getEstadoColor(pago.estado)]">
                          {{ getEstadoLabel(pago.estado) }}
                        </span>
                      </div>
                    </div>
                  </div>
  
                  <div class="space-y-6">
                    <h3 class="text-[10px] font-black text-emerald-400 uppercase tracking-[0.2em] flex items-center gap-2">
                       <span class="w-2 h-2 rounded-full bg-brand-500 animate-pulse"></span>
                       Resumen Financiero
                    </h3>
                    <div class="space-y-6">
                      <div class="flex justify-between items-center group/item p-3 rounded-2xl hover:bg-white/[0.03] transition-colors border border-transparent hover:border-white/5">
                        <span class="text-slate-500 text-xs font-bold uppercase tracking-wide group-hover/item:text-slate-300 transition-colors">Monto Programado</span>
                        <span class="font-black text-white text-base tracking-tighter">${{ formatearMoneda(pago.monto_programado) }}</span>
                      </div>
                      <div class="flex justify-between items-center group/item p-3 rounded-2xl hover:bg-white/[0.03] transition-colors border border-transparent hover:border-white/5">
                        <span class="text-slate-500 text-xs font-bold uppercase tracking-wide group-hover/item:text-slate-300 transition-colors">Monto Pagado</span>
                        <span class="font-black text-emerald-400 text-xl shadow-emerald-500/10 drop-shadow-xl tracking-tighter">${{ formatearMoneda(pago.monto_pagado) }}</span>
                      </div>
                      <div class="flex justify-between items-center group/item p-3 rounded-2xl hover:bg-white/[0.03] transition-colors border border-transparent hover:border-white/5">
                        <span class="text-slate-500 text-xs font-bold uppercase tracking-wide group-hover/item:text-slate-300 transition-colors">Monto Pendiente</span>
                        <span class="font-black text-brand-400 text-base tracking-tighter">${{ formatearMoneda(montoPendiente) }}</span>
                      </div>
                    </div>
                  </div>
                </div>
  
                <div class="h-px bg-gradient-to-r from-transparent via-white/10 to-transparent"></div>
  
                <!-- Información del préstamo -->
                <div class="space-y-6">
                   <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] flex items-center gap-2">
                       Préstamo Relacionado
                   </h3>
                   <div class="bg-slate-950/40 rounded-3xl p-8 border border-white/5 shadow-inner">
                      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center md:text-left">
                        <div class="space-y-2">
                          <span class="block text-[10px] text-slate-500 uppercase font-black tracking-wide">Cliente / Deudor</span>
                          <Link :href="`/prestamos/${pago.prestamo_id}`" class="block text-lg font-black text-white hover:text-indigo-400 transition-colors tracking-tight">
                             {{ pago.prestamo?.cliente?.nombre_razon_social }}
                          </Link>
                        </div>
                        <div class="space-y-2">
                          <span class="block text-[10px] text-slate-500 uppercase font-black tracking-wide">Monto Original</span>
                          <span class="block text-lg font-black text-white tracking-tighter">${{ formatearMoneda(pago.prestamo?.monto_prestado) }}</span>
                        </div>
                         <div class="space-y-2">
                          <span class="block text-[10px] text-slate-500 uppercase font-black tracking-wide">Progreso Global</span>
                          <div class="flex items-center justify-center md:justify-start gap-2">
                             <div class="w-12 bg-slate-800 h-1.5 rounded-full overflow-hidden">
                               <div class="h-full bg-indigo-500" :style="`width: ${pago.prestamo?.progreso}%`"></div>
                             </div>
                             <span class="text-sm font-black text-indigo-400">{{ pago.prestamo?.progreso }}%</span>
                          </div>
                        </div>
                      </div>
                   </div>
                </div>
  
                <!-- Notas y Observaciones -->
                <div v-if="pago.fecha_pago || pago.dias_atraso > 0" class="bg-indigo-500/5 rounded-3xl p-6 border border-indigo-500/10 shadow-[0_0_25px_rgba(99,102,241,0.02)]">
                  <h3 class="text-[10px] font-black text-indigo-400 uppercase tracking-[0.2em] mb-4 flex items-center gap-2">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                      Información de Auditoría
                  </h3>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                    <div v-if="pago.fecha_pago" class="flex flex-col gap-1">
                      <span class="text-[10px] font-black text-slate-500 uppercase tracking-wide">Registro Último Pago</span>
                      <span class="text-white font-bold">{{ formatearFecha(pago.fecha_pago) }}</span>
                    </div>
                    <div v-if="pago.dias_atraso > 0" class="flex flex-col gap-1">
                      <span class="text-[10px] font-black text-rose-500 uppercase tracking-wide">Atraso Crítico</span>
                      <span class="text-rose-400 font-bold bg-brand-500/10 px-3 py-1 rounded-xl border border-rose-500/20 w-fit">
                        {{ pago.dias_atraso }} días de retraso acumulados
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
  
        <!-- Panel lateral -->
        <div class="lg:col-span-1 space-y-6">
          <!-- Progreso del pago -->
          <div class="relative group">
             <div class="absolute -inset-0.5 bg-gradient-to-br from-emerald-500/20 to-teal-600/20 rounded-3xl blur-xl opacity-0 group-hover:opacity-100 transition duration-700"></div>
             <div class="relative bg-black/50 rounded-3xl shadow-2xl border border-white/10 overflow-hidden backdrop-blur-xl">
              <div class="px-8 py-6 border-b border-white/5 bg-slate-950/30">
                <h3 class="text-lg font-black text-white tracking-tight uppercase">Progreso del Pago</h3>
              </div>
  
              <div class="p-8">
                <div class="text-center mb-8 relative">
                   <div class="text-6xl font-black text-white mb-2 tracking-tighter drop-shadow-sm">{{ progreso }}%</div>
                   <div class="w-full bg-slate-800 rounded-full h-4 overflow-hidden shadow-inner p-1 border border-white/5">
                    <div
                      class="h-full rounded-full transition-all duration-700 relative"
                      :class="progreso === 100 ? 'bg-gradient-to-r from-emerald-600 to-teal-500' : progreso > 0 ? 'bg-gradient-to-r from-indigo-600 to-blue-500' : 'bg-slate-700'"
                      :style="{ width: progreso + '%' }"
                    >
                       <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
                    </div>
                  </div>
                  <div class="text-[10px] font-black text-slate-400 mt-4 bg-slate-950/50 py-2 px-4 rounded-2xl inline-block border border-white/5 uppercase tracking-wide">
                    {{ formatCurrency ? formatCurrency(pago.monto_pagado) : '$' + formatearMoneda(pago.monto_pagado) }} / {{ formatCurrency ? formatCurrency(pago.monto_programado) : '$' + formatearMoneda(pago.monto_programado) }}
                  </div>
                </div>
  
                <!-- Información de estado lista -->
                <div class="space-y-6">
                  <div class="flex justify-between items-center group p-4 bg-white/[0.02] border border-white/5 rounded-2xl hover:bg-white/[0.04] transition-colors">
                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-wide">Estado</span>
                    <span :class="['text-[10px] font-black uppercase tracking-wide px-3 py-1 rounded-xl border', getEstadoColor(pago.estado)]">
                      {{ getEstadoLabel(pago.estado) }}
                    </span>
                  </div>
  
                  <div v-if="pago.dias_atraso > 0" class="flex justify-between items-center group p-4 bg-brand-500/5 border border-rose-500/10 rounded-2xl hover:bg-slate-500/10 transition-colors">
                     <span class="text-[10px] font-black text-rose-500 uppercase tracking-wide">Mora</span>
                    <span class="text-[10px] font-black text-rose-400 bg-brand-500/10 px-3 py-1 rounded-xl border border-rose-500/20 shadow-sm">{{ pago.dias_atraso }} DÍAS</span>
                  </div>
  
                  <div class="flex justify-between items-center group p-4 bg-brand-500/5 border border-brand-500/10 rounded-2xl hover:bg-slate-500/10 transition-colors">
                     <span class="text-[10px] font-black text-brand-500 uppercase tracking-wide">Pendiente</span>
                    <span class="text-lg font-black text-brand-500 tracking-tighter">${{ formatearMoneda(montoPendiente) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
  
          <!-- Historial de pagos (si existe) -->
          <div v-if="tieneHistorial" class="bg-black/50 rounded-3xl shadow-2xl border border-white/10 overflow-hidden backdrop-blur-xl">
            <div class="px-8 py-6 border-b border-white/5 bg-slate-950/30 flex justify-between items-center">
              <h3 class="text-lg font-black text-white tracking-tight uppercase">Historial de Abonos</h3>
              <span class="bg-indigo-600 text-white text-[10px] font-black px-3 py-1 rounded-2xl shadow-xl shadow-indigo-500/20">{{ pago.historial_pagos.length }}</span>
            </div>
  
            <div class="p-8">
              <div class="space-y-6">
                <div
                  v-for="historial in pago.historial_pagos"
                  :key="historial.id"
                  class="bg-slate-950/50 border border-white/5 rounded-2xl p-5 hover:border-brand-500/30 transition-all group/hist duration-200 relative overflow-hidden"
                >
                  <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 to-transparent opacity-0 group-hover/hist:opacity-100 transition-opacity"></div>
                  <div class="relative z-10">
                    <div class="flex justify-between items-start mb-4">
                      <div>
                        <div class="font-black text-white text-2xl tracking-tighter group-hover/hist:text-indigo-400 transition-colors">${{ formatearMoneda(historial.monto_pagado) }}</div>
                        <div class="text-[9px] uppercase font-black text-slate-500 tracking-[0.2em] mt-1">{{ formatearFecha(historial.fecha_pago) }}</div>
                      </div>
                      <div class="text-right flex flex-col items-end gap-2">
                        <div class="text-[10px] uppercase font-black text-indigo-400 bg-indigo-500/10 px-3 py-1 rounded-xl border border-indigo-500/20 inline-block mb-1 shadow-sm">{{ getMetodoPagoLabel(historial.metodo_pago) }}</div>
                        <a :href="`/pagos/comprobante/${historial.id}`" target="_blank" class="text-[9px] font-black text-white bg-blue-600 hover:bg-slate-500 px-3 py-1.5 rounded-xl flex items-center transition-all shadow-xl shadow-blue-600/10">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            Imprimir Recibo
                        </a>
                        <div v-if="historial.referencia" class="text-[10px] text-slate-400 font-mono font-bold tracking-wide block uppercase">{{ historial.referencia }}</div>
                      </div>
                    </div>
                    <div v-if="historial.notas" class="mt-4 p-4 bg-black/50 rounded-2xl border border-white/5 text-xs text-slate-400/80 font-medium italic group-hover/hist:text-slate-300 transition-colors">
                      "{{ historial.notas }}"
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
.pagos-show {
  min-height: 100vh;
}
</style>
