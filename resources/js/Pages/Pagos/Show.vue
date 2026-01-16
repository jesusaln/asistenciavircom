<!-- /resources/js/Pages/Pagos/Show.vue -->
<script setup>
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
    'pendiente': 'bg-yellow-100 text-yellow-800',
    'pagado': 'bg-green-100 text-green-800',
    'atrasado': 'bg-red-100 text-red-800',
    'parcial': 'bg-orange-100 text-orange-800'
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

  <div class="pagos-show min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto px-6 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Detalles de Pago</h1>
            <p class="text-gray-600 mt-2">Información completa del pago del préstamo</p>
          </div>
          <div class="flex items-center space-x-3">
            <Link
              :href="`/pagos/create?prestamo_id=${pago.prestamo_id}&pago_id=${pago.id}`"
              class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200"
            >
              💳 Registrar Pago
            </Link>
            <Link
              href="/pagos"
              class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
            >
              ← Volver a Pagos
            </Link>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Información principal -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
              <h2 class="text-lg font-semibold text-gray-900">Información del Pago</h2>
            </div>

            <div class="p-6">
              <!-- Información básica -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                  <h3 class="text-sm font-medium text-gray-700 mb-3">Datos del Pago</h3>
                  <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                      <span class="text-gray-600">Número de Pago:</span>
                      <span class="font-semibold text-gray-900">#{{ pago.numero_pago }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-600">Fecha Programada:</span>
                      <span class="font-semibold text-gray-900">{{ formatearFecha(pago.fecha_programada) }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-600">Estado:</span>
                      <span :class="['font-semibold', getEstadoColor(pago.estado)]">
                        {{ getEstadoLabel(pago.estado) }}
                      </span>
                    </div>
                  </div>
                </div>

                <div>
                  <h3 class="text-sm font-medium text-gray-700 mb-3">Información Financiera</h3>
                  <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                      <span class="text-gray-600">Monto Programado:</span>
                      <span class="font-semibold text-gray-900">${{ formatearMoneda(pago.monto_programado) }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-600">Monto Pagado:</span>
                      <span class="font-semibold text-green-600">${{ formatearMoneda(pago.monto_pagado) }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-600">Monto Pendiente:</span>
                      <span class="font-semibold text-orange-600">${{ formatearMoneda(montoPendiente) }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Información del préstamo -->
              <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h3 class="text-sm font-medium text-gray-700 mb-3">Información del Préstamo</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                  <div>
                    <span class="text-gray-600">Cliente:</span>
                    <span class="font-semibold text-gray-900 ml-2">{{ pago.prestamo?.cliente?.nombre_razon_social }}</span>
                  </div>
                  <div>
                    <span class="text-gray-600">Monto del préstamo:</span>
                    <span class="font-semibold text-gray-900 ml-2">${{ formatearMoneda(pago.prestamo?.monto_prestado) }}</span>
                  </div>
                  <div>
                    <span class="text-gray-600">Progreso del préstamo:</span>
                    <span class="font-semibold text-gray-900 ml-2">{{ pago.prestamo?.progreso }}%</span>
                  </div>
                  <div>
                    <span class="text-gray-600">Pagos realizados:</span>
                    <span class="font-semibold text-gray-900 ml-2">{{ pago.prestamo?.pagos_realizados }} / {{ pago.prestamo?.numero_pagos }}</span>
                  </div>
                </div>
              </div>

              <!-- Información adicional -->
              <div v-if="pago.fecha_pago || pago.dias_atraso > 0" class="bg-blue-50 rounded-lg p-4 mb-6">
                <h3 class="text-sm font-medium text-blue-700 mb-3">Información de Pago</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                  <div v-if="pago.fecha_pago">
                    <span class="text-blue-600">Fecha de Pago:</span>
                    <span class="font-semibold text-blue-900 ml-2">{{ formatearFecha(pago.fecha_pago) }}</span>
                  </div>
                  <div v-if="pago.dias_atraso > 0">
                    <span class="text-blue-600">Días de Atraso:</span>
                    <span class="font-semibold text-red-600 ml-2">{{ pago.dias_atraso }} días</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Panel lateral -->
        <div class="lg:col-span-1">
          <!-- Progreso del pago -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900">Progreso del Pago</h3>
            </div>

            <div class="p-6">
              <div class="text-center mb-4">
                <div class="text-3xl font-bold text-gray-900 mb-2">{{ progreso }}%</div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div
                    class="h-2 rounded-full transition-all duration-300"
                    :class="progreso === 100 ? 'bg-green-500' : progreso > 0 ? 'bg-blue-500' : 'bg-gray-300'"
                    :style="{ width: progreso + '%' }"
                  ></div>
                </div>
                <div class="text-sm text-gray-600 mt-2">
                  ${{ formatearMoneda(pago.monto_pagado) }} de ${{ formatearMoneda(pago.monto_programado) }}
                </div>
              </div>

              <!-- Información de estado -->
              <div class="space-y-3">
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Estado:</span>
                  <span :class="['text-sm font-medium', getEstadoColor(pago.estado)]">
                    {{ getEstadoLabel(pago.estado) }}
                  </span>
                </div>

                <div v-if="pago.dias_atraso > 0" class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Días de atraso:</span>
                  <span class="text-sm font-medium text-red-600">{{ pago.dias_atraso }} días</span>
                </div>

                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Pendiente:</span>
                  <span class="text-sm font-medium text-orange-600">${{ formatearMoneda(montoPendiente) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Historial de pagos (si existe) -->
          <div v-if="tieneHistorial" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900">Historial de Pagos</h3>
              <p class="text-sm text-gray-600 mt-1">{{ pago.historial_pagos.length }} pago(s) registrado(s)</p>
            </div>

            <div class="p-6">
              <div class="space-y-3">
                <div
                  v-for="historial in pago.historial_pagos"
                  :key="historial.id"
                  class="border border-gray-200 rounded-lg p-3"
                >
                  <div class="flex justify-between items-start mb-2">
                    <div>
                      <div class="font-medium text-gray-900">${{ formatearMoneda(historial.monto_pagado) }}</div>
                      <div class="text-xs text-gray-500">{{ formatearFecha(historial.fecha_pago) }}</div>
                    </div>
                    <div class="text-right">
                      <div class="text-xs text-gray-600">{{ getMetodoPagoLabel(historial.metodo_pago) }}</div>
                      <div v-if="historial.referencia" class="text-xs text-gray-500">{{ historial.referencia }}</div>
                    </div>
                  </div>
                  <div v-if="historial.notas" class="text-xs text-gray-600 mt-2 p-2 bg-gray-50 rounded">
                    {{ historial.notas }}
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
  background-color: #f9fafb;
}
</style>

