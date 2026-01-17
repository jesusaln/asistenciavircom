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
  return colors[estado] || 'bg-gray-100 text-gray-800'
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

  <div class="prestamos-show min-h-screen bg-white">
    <div class="w-full px-6 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Detalles de Préstamo</h1>
            <p class="text-gray-600 mt-2">Información completa del préstamo y su historial de pagos</p>
          </div>
          <div class="flex items-center space-x-3">
            <Link
              href="/prestamos"
              class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
            >
              ← Volver a Préstamos
            </Link>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Información principal -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
              <h2 class="text-lg font-semibold text-gray-900">Información del Préstamo</h2>
            </div>

            <div class="p-6 space-y-4">
              <!-- Información general -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <p class="text-sm text-gray-600">
                    <strong>Cliente:</strong> {{ prestamo.cliente?.nombre_razon_social || 'Sin cliente' }}
                  </p>
                  <p class="text-sm text-gray-600">
                    <strong>Monto Prestado:</strong> ${{ formatearMoneda(prestamo.monto_prestado) }}
                  </p>
                  <p class="text-sm text-gray-600">
                    <strong>Tasa Mensual:</strong> {{ prestamo.tasa_interes_mensual }}%
                  </p>
                  <p class="text-sm text-gray-600">
                    <strong>Pago Periódico:</strong> ${{ formatearMoneda(prestamo.pago_periodico) }}
                  </p>
                </div>

                <div>
                  <p class="text-sm text-gray-600">
                    <strong>Estado:</strong>
                    <span
                      :class="getEstadoColor(prestamo.estado)"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ml-2"
                    >
                      {{ getEstadoLabel(prestamo.estado) }}
                    </span>
                  </p>
                  <p class="text-sm text-gray-600">
                    <strong>Pagos Realizados:</strong> {{ prestamo.pagos_realizados }} / {{ prestamo.numero_pagos }}
                  </p>
                  <p class="text-sm text-gray-600">
                    <strong>Fecha de Inicio:</strong> {{ formatearFecha(prestamo.fecha_inicio) }}
                  </p>
                </div>
              </div>

              <!-- Información financiera -->
              <div class="bg-white rounded-lg p-4">
                <h4 class="text-sm font-medium text-gray-900 mb-3">Resumen Financiero</h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                  <div>
                    <p class="text-gray-600">Total a Pagar:</p>
                    <p class="text-lg font-semibold text-gray-900">${{ formatearMoneda(prestamo.monto_total_pagar) }}</p>
                  </div>
                  <div>
                    <p class="text-gray-600">Total Pagado:</p>
                    <p class="text-lg font-semibold text-green-600">${{ formatearMoneda(prestamo.monto_pagado) }}</p>
                  </div>
                  <div>
                    <p class="text-gray-600">Monto Pendiente:</p>
                    <p class="text-lg font-semibold text-orange-600">${{ formatearMoneda(prestamo.monto_pendiente) }}</p>
                  </div>
                  <div>
                    <p class="text-gray-600">Interés Total:</p>
                    <p class="text-lg font-semibold text-blue-600">${{ formatearMoneda(prestamo.monto_interes_total) }}</p>
                  </div>
                </div>
              </div>

              <!-- Historial de Pagos -->
              <div class="bg-white border border-gray-200 rounded-lg">
                <div class="px-4 py-3 border-b border-gray-200">
                  <h4 class="text-sm font-medium text-gray-900">Historial de Pagos</h4>
                  <p class="text-sm text-gray-600 mt-1">Registro completo de todos los pagos realizados</p>
                </div>

                <div class="p-4">
                  <div v-if="historialPagos.length > 0" class="space-y-3">
                    <div
                      v-for="pago in historialPagos"
                      :key="pago.id"
                      class="border border-gray-200 rounded-lg p-3"
                    >
                      <div class="flex justify-between items-start mb-2">
                        <div>
                          <div class="font-medium text-gray-900">Pago #{{ pago.numero_pago }}</div>
                          <div class="text-sm text-gray-600">{{ formatearFecha(pago.fecha_programada) }}</div>
                        </div>
                        <div class="text-right">
                          <div class="text-sm font-medium text-gray-900">
                            ${{ formatearMoneda(pago.monto_pagado) }} / ${{ formatearMoneda(pago.monto_programado) }}
                          </div>
                          <div class="text-xs text-gray-500">{{ getEstadoLabel(pago.estado) }}</div>
                        </div>
                      </div>

                      <!-- Historial individual de este pago -->
                      <div v-if="pago.historial_pagos && pago.historial_pagos.length > 0" class="mt-3 pl-4 border-l-2 border-gray-200">
                        <div class="text-xs text-gray-600 mb-2 font-medium">Pagos individuales:</div>
                        <div class="space-y-2">
                          <div
                            v-for="historial in pago.historial_pagos"
                            :key="historial.id"
                            class="flex justify-between items-center text-xs bg-white p-2 rounded"
                          >
                            <div>
                              <span class="font-medium">${{ formatearMoneda(historial.monto_pagado) }}</span>
                              <span class="text-gray-500 ml-2">{{ formatearFecha(historial.fecha_pago) }}</span>
                            </div>
                            <div class="text-right">
                              <span class="text-gray-600">{{ getMetodoPagoLabel(historial.metodo_pago) }}</span>
                              <span v-if="historial.referencia" class="text-gray-500 ml-2">{{ historial.referencia }}</span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div v-else class="text-center py-8">
                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                      <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                      </svg>
                    </div>
                    <p class="text-sm text-gray-500">No hay pagos registrados aún</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Panel lateral -->
        <div class="lg:col-span-1">
          <!-- Progreso del préstamo -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900">Progreso del Préstamo</h3>
            </div>

            <div class="p-6">
              <div class="text-center mb-4">
                <div class="text-3xl font-bold text-gray-900 mb-2">{{ progreso }}%</div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div
                    class="h-2 rounded-full transition-all duration-300"
                    :class="progreso === 100 ? 'bg-green-500' : 'bg-blue-500'"
                    :style="{ width: progreso + '%' }"
                  ></div>
                </div>
                <div class="text-sm text-gray-600 mt-2">
                  {{ prestamo.pagos_realizados }} de {{ prestamo.numero_pagos }} pagos
                </div>
              </div>

              <!-- Información de estado -->
              <div class="space-y-3">
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Estado:</span>
                  <span :class="['text-sm font-medium', getEstadoColor(prestamo.estado)]">
                    {{ getEstadoLabel(prestamo.estado) }}
                  </span>
                </div>

                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Pendiente:</span>
                  <span class="text-sm font-medium text-orange-600">${{ formatearMoneda(montoPendiente) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Próximo pago -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900">Próximo Pago</h3>
            </div>

            <div class="p-6">
              <div v-if="prestamo.proximo_pago" class="text-center">
                <div class="text-2xl font-bold text-green-600 mb-2">
                  ${{ formatearMoneda(prestamo.proximo_pago.monto_programado) }}
                </div>
                <div class="text-sm text-gray-600 mb-4">
                  Pago #{{ prestamo.proximo_pago.numero_pago }}
                </div>
                <div class="text-sm text-gray-600">
                  {{ formatearFecha(prestamo.proximo_pago.fecha_programada) }}
                </div>
              </div>

              <div v-else class="text-center py-8">
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                  <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <p class="text-sm text-gray-500">Préstamo completado</p>
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
  background-color: #f9fafb;
}
</style>

