<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

const props = defineProps({
  nomina: { type: Object, required: true },
})

const notyf = new Notyf({ duration: 4000, position: { x: 'right', y: 'top' } })

const page = usePage()
onMounted(() => {
  if (page.props.flash?.success) notyf.success(page.props.flash.success)
  if (page.props.flash?.error) notyf.error(page.props.flash.error)
})

const formatearMoneda = (num) => {
  const value = parseFloat(num)
  return isNaN(value) ? '$0.00' : `$${value.toLocaleString('es-MX', { minimumFractionDigits: 2 })}`
}

const formatearFecha = (date) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' })
}

const percepciones = computed(() => props.nomina.conceptos?.filter(c => c.tipo === 'percepcion') || [])
const deducciones = computed(() => props.nomina.conceptos?.filter(c => c.tipo === 'deduccion') || [])

const procesarNomina = () => {
  if (confirm('¿Está seguro de procesar esta nómina? Ya no podrá editarla después.')) {
    router.patch(`/nominas/${props.nomina.id}/procesar`, {}, {
      onSuccess: () => notyf.success('Nómina procesada'),
      onError: () => notyf.error('Error al procesar'),
    })
  }
}

const pagarNomina = () => {
  if (confirm('¿Marcar esta nómina como pagada?')) {
    router.patch(`/nominas/${props.nomina.id}/pagar`, { metodo_pago: 'transferencia' }, {
      onSuccess: () => notyf.success('Nómina marcada como pagada'),
      onError: () => notyf.error('Error al marcar como pagada'),
    })
  }
}

const editarNomina = () => router.visit(`/nominas/${props.nomina.id}/edit`)
const volver = () => router.visit('/nominas')
</script>

<template>
  <Head :title="`Nómina #${nomina.id}`" />

  <div class="min-h-screen bg-white py-8">
    <div class="w-full px-6">
      <!-- Header -->
      <div class="mb-8 flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <button @click="volver" class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 transition-colors">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </button>
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Recibo de Nómina</h1>
            <p class="text-sm text-gray-500">{{ nomina.periodo_formateado }} • {{ nomina.tipo_periodo_formateado }}</p>
          </div>
        </div>
        <div class="flex space-x-3">
          <span :class="nomina.estado_info?.color" class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium">
            <span :class="nomina.estado_info?.dot" class="w-2 h-2 rounded-full mr-2"></span>
            {{ nomina.estado_info?.label }}
          </span>
        </div>
      </div>

      <!-- Recibo de Nómina -->
      <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        <!-- Encabezado del Recibo -->
        <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-8 py-6 text-white">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-xl font-bold">{{ nomina.empleado?.user?.name }}</h2>
              <p class="text-emerald-100">{{ nomina.empleado?.puesto }} • {{ nomina.empleado?.departamento }}</p>
              <p class="text-sm text-emerald-200 mt-1">{{ nomina.empleado?.numero_empleado }}</p>
            </div>
            <div class="text-right">
              <p class="text-sm text-emerald-200">Neto a Pagar</p>
              <p class="text-3xl font-bold">{{ formatearMoneda(nomina.total_neto) }}</p>
            </div>
          </div>
        </div>

        <!-- Información del Período -->
        <div class="grid grid-cols-4 gap-4 px-8 py-4 bg-white border-b">
          <div>
            <p class="text-xs text-gray-500">Período</p>
            <p class="font-medium text-gray-900">{{ nomina.periodo_formateado }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">Tipo</p>
            <p class="font-medium text-gray-900">{{ nomina.tipo_periodo_formateado }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">Días Trabajados</p>
            <p class="font-medium text-gray-900">{{ nomina.dias_trabajados }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">Período #</p>
            <p class="font-medium text-gray-900">{{ nomina.numero_periodo }} / {{ nomina.anio }}</p>
          </div>
        </div>

        <!-- Contenido del Recibo -->
        <div class="p-8">
          <div class="grid grid-cols-2 gap-8">
            <!-- Percepciones -->
            <div>
              <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <span class="w-3 h-3 bg-green-500 rounded-full mr-2"></span>
                Percepciones
              </h3>
              <div class="space-y-3">
                <div v-for="concepto in percepciones" :key="concepto.id" class="flex justify-between items-center py-2 border-b border-gray-100">
                  <div>
                    <p class="font-medium text-gray-800">{{ concepto.concepto }}</p>
                    <p v-if="concepto.clave" class="text-xs text-gray-500">{{ concepto.clave }}</p>
                  </div>
                  <p class="font-medium text-green-600">{{ formatearMoneda(concepto.monto) }}</p>
                </div>
                <div v-if="!percepciones.length" class="text-sm text-gray-500 py-4 text-center">
                  Sin percepciones
                </div>
              </div>
              <div class="mt-4 pt-4 border-t-2 border-green-200 flex justify-between">
                <span class="font-semibold text-gray-700">Total Percepciones</span>
                <span class="font-bold text-green-600 text-lg">{{ formatearMoneda(nomina.total_percepciones) }}</span>
              </div>
            </div>

            <!-- Deducciones -->
            <div>
              <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <span class="w-3 h-3 bg-red-500 rounded-full mr-2"></span>
                Deducciones
              </h3>
              <div class="space-y-3">
                <div v-for="concepto in deducciones" :key="concepto.id" class="flex justify-between items-center py-2 border-b border-gray-100">
                  <div>
                    <p class="font-medium text-gray-800">{{ concepto.concepto }}</p>
                    <p v-if="concepto.clave" class="text-xs text-gray-500">{{ concepto.clave }}</p>
                  </div>
                  <p class="font-medium text-red-600">{{ formatearMoneda(concepto.monto) }}</p>
                </div>
                <div v-if="!deducciones.length" class="text-sm text-gray-500 py-4 text-center">
                  Sin deducciones
                </div>
              </div>
              <div class="mt-4 pt-4 border-t-2 border-red-200 flex justify-between">
                <span class="font-semibold text-gray-700">Total Deducciones</span>
                <span class="font-bold text-red-600 text-lg">{{ formatearMoneda(nomina.total_deducciones) }}</span>
              </div>
            </div>
          </div>

          <!-- Totales -->
          <div class="mt-8 bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600">Neto a Pagar</p>
                <p class="text-3xl font-bold text-emerald-600">{{ formatearMoneda(nomina.total_neto) }}</p>
              </div>
              <div class="text-right text-sm text-gray-500">
                <p v-if="nomina.fecha_pago">Fecha de pago: {{ formatearFecha(nomina.fecha_pago) }}</p>
                <p v-if="nomina.metodo_pago">Método: {{ nomina.metodo_pago }}</p>
              </div>
            </div>
          </div>

          <!-- Notas -->
          <div v-if="nomina.notas" class="mt-6 bg-white rounded-lg p-4">
            <h4 class="text-sm font-semibold text-gray-700 mb-2">Notas</h4>
            <p class="text-sm text-gray-600">{{ nomina.notas }}</p>
          </div>
        </div>

        <!-- Acciones -->
        <div class="px-8 py-6 bg-white border-t flex items-center justify-between">
          <div class="text-xs text-gray-500">
            <p>Creado por: {{ nomina.creado_por?.name || 'Sistema' }}</p>
            <p v-if="nomina.procesado_por">Procesado por: {{ nomina.procesado_por?.name }}</p>
          </div>

          <div class="flex space-x-3">
            <Link :href="`/nominas/${nomina.id}/pdf`" class="px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors flex items-center">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
              </svg>
              Descargar PDF
            </Link>

            <button v-if="nomina.estado === 'borrador'" @click="editarNomina" class="px-4 py-2 bg-amber-100 text-amber-700 rounded-lg hover:bg-amber-200 transition-colors">
              Editar
            </button>

            <button v-if="nomina.estado === 'borrador'" @click="procesarNomina" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
              Procesar
            </button>

            <button v-if="nomina.estado === 'procesada'" @click="pagarNomina" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
              Marcar Pagada
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
