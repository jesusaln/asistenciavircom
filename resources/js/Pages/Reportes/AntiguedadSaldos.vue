<script setup>
import { ref, watch, onMounted } from 'vue'
import { Head, router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

const props = defineProps({
  reporte: {
    type: Array,
    default: () => []
  },
  totales: {
    type: Object,
    default: () => ({
      por_vencer: 0,
      vencido_1_30: 0,
      vencido_31_60: 0,
      vencido_61_90: 0,
      vencido_90_mas: 0,
      total: 0,
    })
  },
  fecha_corte: {
    type: String,
    default: ''
  }
})

// Configuración de notificaciones
const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false },
    { type: 'warning', background: '#f59e0b', icon: false }
  ]
})

const filtros = ref({
  fecha_corte: props.fecha_corte
})

const loading = ref(false)

// Función para formatear moneda
const formatearMoneda = (num) => {
  const value = parseFloat(num)
  const safe = Number.isFinite(value) ? value : 0
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(safe)
}

const aplicarFiltros = () => {
  loading.value = true
  router.get(route('reportes.antiguedad-saldos'), filtros.value, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false
    }
  })
}

// Total Vencido (Everything except "por_vencer")
const totalVencido = (props.totales.vencido_1_30 + props.totales.vencido_31_60 + props.totales.vencido_61_90 + props.totales.vencido_90_mas)

</script>

<template>
  <Head title="Reporte de Antigüedad de Saldos" />

  <div class="min-h-screen bg-white">
    <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Header -->
      <div class="mb-8">
        <div class="bg-gradient-to-r from-blue-700 to-indigo-800 rounded-2xl p-6 text-white shadow-lg">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-3xl font-bold tracking-tight mb-2">Antigüedad de Saldos</h1>
              <p class="text-blue-100 text-lg">Análisis de cartera vencida y saldos por cobrar</p>
            </div>
            <div class="hidden md:block">
              <div class="text-right">
                <div class="text-3xl font-bold">{{ formatearMoneda(totales.total) }}</div>
                <div class="text-xs text-blue-200 uppercase tracking-wide">Deuda Total</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
        <div class="flex flex-col sm:flex-row gap-4 items-end">
          <div class="w-full sm:w-auto">
            <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Corte</label>
            <input
              v-model="filtros.fecha_corte"
              type="date"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
            />
          </div>
          <button
            @click="aplicarFiltros"
            :disabled="loading"
            class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-lg transition-colors shadow-sm"
          >
            <span v-if="loading">Cargando...</span>
            <span v-else>Actualizar Reporte</span>
          </button>
        </div>
      </div>

      <!-- Tarjetas de Resumen -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Vencido Total -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 relative overflow-hidden">
          <div class="relative z-10">
            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Vencido</h3>
            <p class="text-2xl font-bold text-red-600 mt-2">{{ formatearMoneda(totalVencido) }}</p>
            <div class="w-full bg-gray-200 h-1.5 rounded-full mt-3">
              <div class="bg-red-500 h-1.5 rounded-full" :style="`width: ${Math.min((totalVencido / totales.total) * 100, 100)}%`"></div>
            </div>
            <p class="text-xs text-gray-500 mt-2">{{ ((totalVencido / (totales.total || 1)) * 100).toFixed(1) }}% de la deuda total</p>
          </div>
          <div class="absolute right-0 top-0 h-full w-1/3 bg-gradient-to-l from-red-50 to-transparent"></div>
        </div>

        <!-- Por Vencer (Corriente) -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 relative overflow-hidden">
             <div class="relative z-10">
            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Por Vencer</h3>
            <p class="text-2xl font-bold text-green-600 mt-2">{{ formatearMoneda(totales.por_vencer) }}</p>
             <div class="w-full bg-gray-200 h-1.5 rounded-full mt-3">
              <div class="bg-green-500 h-1.5 rounded-full" :style="`width: ${Math.min((totales.por_vencer / totales.total) * 100, 100)}%`"></div>
            </div>
             </div>
             <div class="absolute right-0 top-0 h-full w-1/3 bg-gradient-to-l from-green-50 to-transparent"></div>
        </div>

         <!-- Crítico (+90 días) -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 relative overflow-hidden">
             <div class="relative z-10">
            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Crítico (+90 Días)</h3>
            <p class="text-2xl font-bold text-red-800 mt-2">{{ formatearMoneda(totales.vencido_90_mas) }}</p>
             <div class="w-full bg-gray-200 h-1.5 rounded-full mt-3">
              <div class="bg-red-800 h-1.5 rounded-full" :style="`width: ${Math.min((totales.vencido_90_mas / totales.total) * 100, 100)}%`"></div>
            </div>
             </div>
             <div class="absolute right-0 top-0 h-full w-1/3 bg-gradient-to-l from-red-100 to-transparent"></div>
        </div>

        <!-- Clientes con Deuda -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
            <div>
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Clientes con Deuda</h3>
                <p class="text-3xl font-bold text-gray-800 mt-2">{{ reporte.length }}</p>
            </div>
        </div>
      </div>

      <!-- Tabla Principal -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-white">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Límite Crédito</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Deuda</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-green-600 uppercase tracking-wider bg-green-50">Por Vencer</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-yellow-600 uppercase tracking-wider bg-yellow-50">1-30 Días</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-orange-600 uppercase tracking-wider bg-orange-50">31-60 Días</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-red-600 uppercase tracking-wider bg-red-50">61-90 Días</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-red-800 uppercase tracking-wider bg-red-100">+90 Días</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-if="reporte.length === 0">
                <td colspan="8" class="px-6 py-10 text-center text-gray-500">
                    No hay saldos pendientes para la fecha de corte seleccionada.
                </td>
              </tr>
              <tr v-for="row in reporte" :key="row.id" class="hover:bg-white transition-colors">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">
                    <Link :href="route('clientes.show', row.id)" class="hover:text-blue-600 hover:underline">
                        {{ row.nombre }}
                    </Link>
                  </div>
                  <div class="text-xs text-gray-500">{{ row.telefono }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">
                  {{ formatearMoneda(row.limite_credito) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900">
                  {{ formatearMoneda(row.total) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-green-600 bg-green-50/50">
                  <span v-if="row.por_vencer > 0">{{ formatearMoneda(row.por_vencer) }}</span>
                  <span v-else class="text-gray-300">-</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-yellow-600 bg-yellow-50/50">
                   <span v-if="row.vencido_1_30 > 0">{{ formatearMoneda(row.vencido_1_30) }}</span>
                   <span v-else class="text-gray-300">-</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-orange-600 bg-orange-50/50">
                   <span v-if="row.vencido_31_60 > 0">{{ formatearMoneda(row.vencido_31_60) }}</span>
                   <span v-else class="text-gray-300">-</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-red-600 bg-red-50/50">
                   <span v-if="row.vencido_61_90 > 0">{{ formatearMoneda(row.vencido_61_90) }}</span>
                   <span v-else class="text-gray-300">-</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-red-800 bg-red-100/50">
                   <span v-if="row.vencido_90_mas > 0">{{ formatearMoneda(row.vencido_90_mas) }}</span>
                   <span v-else class="text-gray-300">-</span>
                </td>
              </tr>
              
              <!-- Totales Footer -->
              <tr class="bg-gray-100 font-bold">
                <td class="px-6 py-4 text-right">TOTALES</td>
                <td class="px-6 py-4"></td>
                <td class="px-6 py-4 text-right">{{ formatearMoneda(totales.total) }}</td>
                <td class="px-6 py-4 text-right text-green-700">{{ formatearMoneda(totales.por_vencer) }}</td>
                <td class="px-6 py-4 text-right text-yellow-700">{{ formatearMoneda(totales.vencido_1_30) }}</td>
                <td class="px-6 py-4 text-right text-orange-700">{{ formatearMoneda(totales.vencido_31_60) }}</td>
                <td class="px-6 py-4 text-right text-red-700">{{ formatearMoneda(totales.vencido_61_90) }}</td>
                <td class="px-6 py-4 text-right text-red-900">{{ formatearMoneda(totales.vencido_90_mas) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Navegación -->
      <div class="flex justify-center mt-8">
        <Link
          href="/reportes"
          class="inline-flex items-center px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white text-sm font-semibold rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
          </svg>
          Volver a Reportes
        </Link>
      </div>

    </div>
  </div>
</template>

