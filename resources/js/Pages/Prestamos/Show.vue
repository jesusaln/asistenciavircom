<!-- /resources/js/Pages/Prestamos/Show.vue -->
<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

const props = defineProps({
  prestamo: {
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
  if (props.prestamo.numero_pagos == 0) return 0;
  return Math.round((props.prestamo.pagos_realizados / props.prestamo.numero_pagos) * 100);
})

const montoPendiente = computed(() => {
  return Math.max(0, props.prestamo.monto_total_pagar - props.prestamo.monto_pagado);
})

const historialPagos = computed(() => {
  if (!props.prestamo.pagos) return [];

  return props.prestamo.pagos.map(pago => ({
    ...pago,
    historial_pagos: pago.historial_pagos || []
  }));
})
</script>

<template>
  <Head title="Detalles de Préstamo" />

  <div class="prestamos-show min-h-screen bg-white dark:bg-slate-950 transition-colors duration-300">
    <div class="w-full px-6 py-8">
      <!-- Header -->
      <div class="mb-10">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-slate-100 tracking-tight">Detalles del Préstamo</h1>
            <p class="text-gray-600 dark:text-slate-400 mt-2">Visión integral del crédito, amortización y seguimiento de cobros</p>
          </div>
          <div class="flex items-center space-x-4">
            <Link
              href="/prestamos"
              class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-slate-800 text-sm font-bold rounded-xl text-gray-700 dark:text-slate-300 bg-white dark:bg-slate-900 hover:bg-gray-50 dark:hover:bg-slate-800 transition-all duration-200"
            >
              ← Volver a Préstamos
            </Link>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Información principal -->
        <div class="lg:col-span-2 space-y-8">
          <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl shadow-black/5 border border-gray-100 dark:border-slate-800 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 dark:border-slate-800/60 bg-gray-50/30 dark:bg-slate-900/50">
              <h2 class="text-lg font-bold text-gray-900 dark:text-slate-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Ficha Técnica
              </h2>
            </div>

            <div class="p-8">
              <!-- Información general -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                <div class="space-y-4">
                  <div>
                    <p class="text-xs font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-1">Cliente Beneficiario</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-slate-100">{{ prestamo.cliente?.nombre_razon_social || 'Sin cliente' }}</p>
                  </div>
                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <p class="text-xs font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-1">Monto Base</p>
                      <p class="text-base font-bold text-gray-900 dark:text-slate-100">${{ formatearMoneda(prestamo.monto_prestado) }}</p>
                    </div>
                    <div>
                      <p class="text-xs font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-1">Interés Pactado</p>
                      <p class="text-base font-bold text-gray-900 dark:text-slate-100">{{ prestamo.tasa_interes_mensual }}% <span class="text-[10px] text-gray-400">/ mes</span></p>
                    </div>
                  </div>
                </div>

                <div class="space-y-4">
                   <div>
                    <p class="text-xs font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-2">Estatus del Contrato</p>
                    <span
                      :class="getEstadoColor(prestamo.estado)"
                      class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-wider shadow-sm"
                    >
                      <span class="w-2 h-2 rounded-full mr-2 bg-current opacity-70"></span>
                      {{ getEstadoLabel(prestamo.estado) }}
                    </span>
                  </div>
                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <p class="text-xs font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-1">Amortización</p>
                      <p class="text-base font-bold text-gray-900 dark:text-slate-100">{{ prestamo.pagos_realizados }} <span class="text-xs font-medium text-gray-400">de {{ prestamo.numero_pagos }} cuotas</span></p>
                    </div>
                    <div>
                      <p class="text-xs font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-1">Inicio vigencia</p>
                      <p class="text-base font-bold text-gray-900 dark:text-slate-100">{{ formatearFecha(prestamo.fecha_inicio) }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Información financiera -->
              <div class="bg-blue-50/30 dark:bg-blue-900/10 rounded-2xl p-6 border border-blue-100/30 dark:border-blue-800/20">
                <h4 class="text-xs font-black text-blue-600 dark:text-blue-500 uppercase tracking-widest mb-6 border-b border-blue-100/50 dark:border-blue-900/30 pb-3">Resumen Consolidado</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                  <div>
                    <p class="text-[10px] font-black text-gray-500 dark:text-slate-500 uppercase mb-2">Total Proyectado</p>
                    <p class="text-xl font-black text-gray-900 dark:text-slate-100 tracking-tight">${{ formatearMoneda(prestamo.monto_total_pagar) }}</p>
                  </div>
                  <div>
                    <p class="text-[10px] font-black text-gray-500 dark:text-slate-500 uppercase mb-2">Total Recibido</p>
                    <p class="text-xl font-black text-green-600 tracking-tight">${{ formatearMoneda(prestamo.monto_pagado) }}</p>
                  </div>
                  <div>
                    <p class="text-[10px] font-black text-red-500 uppercase mb-2">Saldo Pendiente</p>
                    <p class="text-xl font-black text-red-600 tracking-tight">${{ formatearMoneda(prestamo.monto_pendiente) }}</p>
                  </div>
                  <div>
                    <p class="text-[10px] font-black text-blue-500 uppercase mb-2">Interés Total</p>
                    <p class="text-xl font-black text-blue-600 tracking-tight">${{ formatearMoneda(prestamo.monto_interes_total) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Historial de Pagos -->
          <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl shadow-black/5 border border-gray-100 dark:border-slate-800 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 dark:border-slate-800/60 bg-gray-50/30 dark:bg-slate-900/50">
              <h4 class="text-lg font-bold text-gray-900 dark:text-slate-100">Plan de Pagos y Amortización</h4>
              <p class="text-xs font-medium text-gray-500 dark:text-slate-500 mt-1 uppercase tracking-widest">Estado cronológico de las cuotas</p>
            </div>

            <div class="p-6">
              <div v-if="historialPagos.length > 0" class="grid grid-cols-1 gap-4">
                <div
                  v-for="pago in historialPagos"
                  :key="pago.id"
                  class="group border border-gray-100 dark:border-slate-800 rounded-2xl p-5 hover:bg-gray-50 dark:hover:bg-slate-800/40 transition-all duration-300"
                >
                  <div class="flex justify-between items-center mb-4">
                    <div class="flex items-center gap-4">
                       <div class="w-10 h-10 bg-gray-100 dark:bg-slate-950 rounded-xl flex items-center justify-center font-bold text-gray-400 group-hover:bg-blue-600 group-hover:text-white transition-all">
                        #{{ pago.numero_pago }}
                      </div>
                      <div>
                        <div class="text-sm font-bold text-gray-900 dark:text-slate-200">Cuota Programada</div>
                        <div class="text-xs font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">{{ formatearFecha(pago.fecha_programada) }}</div>
                      </div>
                    </div>
                    <div class="text-right">
                      <div class="text-sm font-black text-gray-900 dark:text-slate-100">
                        ${{ formatearMoneda(pago.monto_pagado) }} <span class="text-[10px] text-gray-400 font-medium">/ ${{ formatearMoneda(pago.monto_programado) }}</span>
                      </div>
                      <div class="mt-1">
                        <span class="text-[10px] font-black uppercase tracking-widest" :class="pago.estado === 'pagado' ? 'text-green-500' : 'text-orange-500'">
                          {{ getEstadoLabel(pago.estado) }}
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- Historial individual de este pago -->
                  <div v-if="pago.historial_pagos && pago.historial_pagos.length > 0" class="mt-4 pl-6 border-l-2 border-blue-500/30 space-y-3">
                    <div class="text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-3">Movimientos:</div>
                    <div
                        v-for="historial in pago.historial_pagos"
                        :key="historial.id"
                        class="flex justify-between items-center text-xs bg-gray-50/50 dark:bg-slate-950/40 p-3 rounded-xl border border-gray-100 dark:border-slate-800/50"
                      >
                        <div class="flex items-center gap-3">
                          <div class="w-6 h-6 bg-green-500/10 text-green-500 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                          </div>
                          <div>
                            <span class="font-bold text-gray-900 dark:text-slate-200">${{ formatearMoneda(historial.monto_pagado) }}</span>
                            <span class="text-gray-400 dark:text-slate-500 ml-2">{{ formatearFecha(historial.fecha_pago) }}</span>
                          </div>
                        </div>
                        <div class="text-right">
                          <span class="text-[10px] font-black uppercase tracking-widest text-gray-500 dark:text-slate-400">{{ getMetodoPagoLabel(historial.metodo_pago) }}</span>
                          <span v-if="historial.referencia" class="block text-[10px] font-mono text-gray-400 dark:text-slate-500">{{ historial.referencia }}</span>
                        </div>
                    </div>
                  </div>
                </div>
              </div>

              <div v-else class="text-center py-20">
                <div class="w-20 h-20 bg-gray-50 dark:bg-slate-800/50 rounded-full flex items-center justify-center mx-auto mb-6 border border-gray-100 dark:border-slate-700/50 shadow-inner">
                  <svg class="w-10 h-10 text-gray-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                  </svg>
                </div>
                <h5 class="text-lg font-bold text-gray-900 dark:text-slate-200">Sin movimientos</h5>
                <p class="text-sm text-gray-500 dark:text-slate-500 mt-2">Aún no se han registrado cobros para este préstamo.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Panel lateral -->
        <div class="lg:col-span-1">
          <!-- Progreso del préstamo -->
          <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl shadow-black/5 border border-gray-100 dark:border-slate-800 overflow-hidden mb-8">
            <div class="px-6 py-5 border-b border-gray-100 dark:border-slate-800/60 bg-gray-50/30 dark:bg-slate-900/50">
              <h3 class="text-base font-bold text-gray-900 dark:text-slate-100">Progreso</h3>
            </div>

            <div class="p-8">
              <div class="text-center mb-6">
                <div class="text-4xl font-black text-gray-900 dark:text-slate-100 mb-4">{{ progreso }}%</div>
                <div class="w-full bg-gray-100 dark:bg-slate-950 rounded-full h-3 p-1 shadow-inner">
                  <div
                    class="h-full rounded-full transition-all duration-1000 ease-out shadow-lg"
                    :class="progreso === 100 ? 'bg-green-500' : 'bg-blue-600'"
                    :style="{ width: progreso + '%' }"
                  ></div>
                </div>
                <div class="text-[10px] font-black text-gray-400 dark:text-slate-500 mt-4 uppercase tracking-widest">
                  {{ prestamo.pagos_realizados }} de {{ prestamo.numero_pagos }} amortizaciones
                </div>
              </div>

              <!-- Información de estado -->
              <div class="space-y-4 pt-4 border-t border-gray-100 dark:border-slate-800/50">
                <div class="flex justify-between items-center">
                  <span class="text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest">Saldo Restante:</span>
                  <span class="text-sm font-black text-red-600 tracking-tight">${{ formatearMoneda(montoPendiente) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Próximo pago -->
          <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl shadow-black/5 border border-gray-100 dark:border-slate-800 overflow-hidden">
             <div class="px-6 py-5 border-b border-gray-100 dark:border-slate-800/60 bg-gray-50/30 dark:bg-slate-900/50">
                <h3 class="text-base font-bold text-gray-900 dark:text-slate-100 flex items-center gap-2">
                   <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                   Siguiente Cobro
                </h3>
            </div>

            <div class="p-8">
              <div v-if="prestamo.proximo_pago" class="text-center">
                <div class="text-3xl font-black text-green-600 mb-2 tracking-tight">
                  ${{ formatearMoneda(prestamo.proximo_pago.monto_programado) }}
                </div>
                <div class="text-[10px] font-black text-gray-400 dark:text-slate-500 mb-4 uppercase tracking-widest">
                  Recibo #{{ prestamo.proximo_pago.numero_pago }}
                </div>
                <div class="inline-flex items-center px-4 py-2 bg-gray-50 dark:bg-slate-950 rounded-xl text-xs font-bold text-gray-700 dark:text-slate-300 border border-gray-100 dark:border-slate-800">
                  {{ formatearFecha(prestamo.proximo_pago.fecha_programada) }}
                </div>
              </div>

              <div v-else class="text-center py-6">
                <div class="w-16 h-16 bg-green-500/10 rounded-full flex items-center justify-center mx-auto mb-4 border border-green-500/20 shadow-lg">
                  <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <p class="text-sm font-bold text-green-600 uppercase tracking-widest italic font-serif">Liquidado</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.prestamos-show {
  min-height: 100vh;
}
</style>

