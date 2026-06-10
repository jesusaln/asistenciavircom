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

  <div class="min-h-screen bg-[var(--ui-surface)]">
    <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Header -->
      <div class="mb-8">
        <div class="bg-gradient-to-r from-blue-700 to-indigo-800 rounded-2xl p-6 text-white shadow-xl">
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
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-8">
        <div class="flex flex-col sm:flex-row gap-4 items-end">
          <div class="w-full sm:w-auto">
            <label class="block text-sm font-medium text-slate-700 mb-2">Fecha de Corte</label>
            <input
              v-model="filtros.fecha_corte"
              type="date"
              class="w-full border border-slate-300 rounded-xl px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors"
            />
          </div>
          <button
            @click="aplicarFiltros"
            :disabled="loading"
            class="px-6 py-2.5 bg-sky-600 hover:bg-sky-700 disabled:bg-blue-400 text-white font-medium rounded-xl transition-colors shadow-sm"
          >
            <span v-if="loading">Cargando...</span>
            <span v-else>Actualizar Reporte</span>
          </button>
        </div>
      </div>

      <!-- Tarjetas de Resumen -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Vencido Total -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 relative overflow-hidden">
          <div class="relative z-10">
            <h3 class="text-sm font-medium text-slate-500 uppercase tracking-wider">Total Vencido</h3>
            <p class="text-2xl font-bold text-rose-600 mt-2">{{ formatearMoneda(totalVencido) }}</p>
            <div class="w-full bg-slate-200 h-1.5 rounded-full mt-3">
              <div class="bg-brand-500 h-1.5 rounded-full" :style="`width: ${Math.min((totalVencido / totales.total) * 100, 100)}%`"></div>
            </div>
            <p class="text-xs text-slate-500 mt-2">{{ ((totalVencido / (totales.total || 1)) * 100).toFixed(1) }}% de la deuda total</p>
          </div>
          <div class="absolute right-0 top-0 h-full w-1/3 bg-gradient-to-l from-rose-50 to-transparent"></div>
        </div>

        <!-- Por Vencer (Corriente) -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 relative overflow-hidden">
             <div class="relative z-10">
            <h3 class="text-sm font-medium text-slate-500 uppercase tracking-wider">Por Vencer</h3>
            <p class="text-2xl font-bold text-emerald-600 mt-2">{{ formatearMoneda(totales.por_vencer) }}</p>
             <div class="w-full bg-slate-200 h-1.5 rounded-full mt-3">
              <div class="bg-brand-500 h-1.5 rounded-full" :style="`width: ${Math.min((totales.por_vencer / totales.total) * 100, 100)}%`"></div>
            </div>
             </div>
             <div class="absolute right-0 top-0 h-full w-1/3 bg-gradient-to-l from-emerald-50 to-transparent"></div>
        </div>

         <!-- Crítico (+90 días) -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 relative overflow-hidden">
             <div class="relative z-10">
            <h3 class="text-sm font-medium text-slate-500 uppercase tracking-wider">Crítico (+90 Días)</h3>
            <p class="text-2xl font-bold text-rose-800 dark:text-rose-200 mt-2">{{ formatearMoneda(totales.vencido_90_mas) }}</p>
             <div class="w-full bg-slate-200 h-1.5 rounded-full mt-3">
              <div class="bg-rose-800 h-1.5 rounded-full" :style="`width: ${Math.min((totales.vencido_90_mas / totales.total) * 100, 100)}%`"></div>
            </div>
             </div>
             <div class="absolute right-0 top-0 h-full w-1/3 bg-gradient-to-l from-rose-100 to-transparent"></div>
        </div>

        <!-- Clientes con Deuda -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex items-center">
            <div>
                <h3 class="text-sm font-medium text-slate-500 uppercase tracking-wider">Clientes con Deuda</h3>
                <p class="text-3xl font-bold text-slate-800 mt-2">{{ reporte.length }}</p>
            </div>
        </div>
      </div>

      <!-- Tabla Principal -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-800/50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Cliente</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Límite Crédito</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Total Deuda</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-emerald-600 uppercase tracking-wider bg-emerald-50 dark:bg-emerald-900/20">Por Vencer</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-brand-600 uppercase tracking-wider bg-brand-50 dark:bg-brand-900/20">1-30 Días</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-brand-600 uppercase tracking-wider bg-orange-50">31-60 Días</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-rose-600 uppercase tracking-wider bg-rose-50 dark:bg-rose-900/20">61-90 Días</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-rose-800 dark:text-rose-200 uppercase tracking-wider bg-rose-100">+90 Días</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
              <tr v-if="reporte.length === 0">
                <td colspan="8" class="px-6 py-10 text-center text-slate-500">
                    No hay saldos pendientes para la fecha de corte seleccionada.
                </td>
              </tr>
              <tr v-for="row in reporte" :key="row.id" class="hover:bg-white transition-colors">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-slate-900">
                    <Link :href="route('clientes.show', row.id)" class="hover:text-blue-600 hover:underline">
                        {{ row.nombre }}
                    </Link>
                  </div>
                  <div class="text-xs text-slate-500">{{ row.telefono }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-slate-500">
                  {{ formatearMoneda(row.limite_credito) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-slate-900">
                  {{ formatearMoneda(row.total) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20/50">
                  <span v-if="row.por_vencer > 0">{{ formatearMoneda(row.por_vencer) }}</span>
                  <span v-else class="text-slate-300">-</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-brand-600 bg-brand-50 dark:bg-brand-900/20/50">
                   <span v-if="row.vencido_1_30 > 0">{{ formatearMoneda(row.vencido_1_30) }}</span>
                   <span v-else class="text-slate-300">-</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-brand-600 bg-orange-50/50">
                   <span v-if="row.vencido_31_60 > 0">{{ formatearMoneda(row.vencido_31_60) }}</span>
                   <span v-else class="text-slate-300">-</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-rose-600 bg-rose-50 dark:bg-rose-900/20/50">
                   <span v-if="row.vencido_61_90 > 0">{{ formatearMoneda(row.vencido_61_90) }}</span>
                   <span v-else class="text-slate-300">-</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-rose-800 dark:text-rose-200 bg-rose-100/50">
                   <span v-if="row.vencido_90_mas > 0">{{ formatearMoneda(row.vencido_90_mas) }}</span>
                   <span v-else class="text-slate-300">-</span>
                </td>
              </tr>
              
              <!-- Totales Footer -->
              <tr class="bg-slate-100 font-bold">
                <td class="px-6 py-4 text-right">TOTALES</td>
                <td class="px-6 py-4"></td>
                <td class="px-6 py-4 text-right">{{ formatearMoneda(totales.total) }}</td>
                <td class="px-6 py-4 text-right text-emerald-800 dark:text-emerald-200 dark:text-emerald-200">{{ formatearMoneda(totales.por_vencer) }}</td>
                <td class="px-6 py-4 text-right text-brand-800 dark:text-brand-200 dark:text-amber-200">{{ formatearMoneda(totales.vencido_1_30) }}</td>
                <td class="px-6 py-4 text-right text-orange-700">{{ formatearMoneda(totales.vencido_31_60) }}</td>
                <td class="px-6 py-4 text-right text-rose-800 dark:text-rose-200 dark:text-rose-200">{{ formatearMoneda(totales.vencido_61_90) }}</td>
                <td class="px-6 py-4 text-right text-rose-900">{{ formatearMoneda(totales.vencido_90_mas) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Navegación -->
      <div class="flex justify-center mt-8">
        <Link
          href="/reportes"
          class="inline-flex items-center px-6 py-3 bg-slate-600 hover:bg-slate-700 text-white text-sm font-semibold rounded-xl transition-all duration-200 shadow-xl hover:shadow-xl"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
          </svg>
          Volver a Reportes
        </Link>
      </div>

    </div>
  </div>
</template>

