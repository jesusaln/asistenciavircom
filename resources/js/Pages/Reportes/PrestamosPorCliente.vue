<!-- /resources/js/Pages/Reportes/PrestamosPorCliente.vue -->
<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { ref, computed, watch, onMounted } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

const props = defineProps({
  prestamosPorCliente: {
    type: Array,
    default: () => []
  },
  estadisticas: {
    type: Object,
    default: () => ({
      total_clientes: 0,
      total_prestamos: 0,
      total_prestado: 0,
      total_pagado: 0,
      total_pendiente: 0,
      total_interes: 0,
      prestamos_activos: 0,
      prestamos_completados: 0,
      prestamos_cancelados: 0,
    })
  },
  clientes: {
    type: Array,
    default: () => []
  },
  filtros: {
    type: Object,
    default: () => ({
      fecha_inicio: '',
      fecha_fin: '',
      cliente_id: '',
      estado: 'todos',
    })
  },
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

const page = usePage()
onMounted(() => {
  const flash = page.props.flash
  if (flash?.success) notyf.success(flash.success)
  if (flash?.error) notyf.error(flash.error)
})

// Estado local para filtros
const filtros = ref({ ...props.filtros })
const loading = ref(false)
const showExportModal = ref(false)

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

// Función para formatear fecha
const formatearFecha = (date) => {
  if (!date) return 'Fecha no disponible'
  try {
    const time = new Date(date).getTime()
    if (Number.isNaN(time)) return 'Fecha inválida'
    return new Date(time).toLocaleDateString('es-MX', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    })
  } catch {
    return 'Fecha inválida'
  }
}

// Función para formatear porcentaje
const formatearPorcentaje = (num) => {
  const value = parseFloat(num)
  const safe = Number.isFinite(value) ? value : 0
  return `${safe.toFixed(1)}%`
}

// Configuración de estados para préstamos
const configEstados = {
  'activo': {
    label: 'Activo',
    classes: 'bg-emerald-100 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200',
    color: 'bg-emerald-400'
  },
  'completado': {
    label: 'Completado',
    classes: 'bg-sky-100 text-sky-800 dark:text-sky-200',
    color: 'bg-blue-400'
  },
  'cancelado': {
    label: 'Cancelado',
    classes: 'bg-rose-100 text-rose-800 dark:text-rose-200 dark:text-rose-200',
    color: 'bg-rose-400'
  }
}

const obtenerClasesEstado = (estado) => {
  return configEstados[estado]?.classes || 'bg-slate-100 text-slate-700'
}

const obtenerColorPuntoEstado = (estado) => {
  return configEstados[estado]?.color || 'bg-slate-400'
}

const obtenerLabelEstado = (estado) => {
  return configEstados[estado]?.label || 'Pendiente'
}

// Función para aplicar filtros
const aplicarFiltros = () => {
  loading.value = true
  router.visit('/reportes/prestamos-por-cliente', {
    data: filtros.value,
    onFinish: () => {
      loading.value = false
    }
  })
}

// Función para limpiar filtros
const limpiarFiltros = () => {
  filtros.value = {
    fecha_inicio: new Date().toISOString().split('T')[0].replace(/(\d{4})-(\d{2})-(\d{2})/, '$1-$2-$3'),
    fecha_fin: new Date().toISOString().split('T')[0].replace(/(\d{4})-(\d{2})-(\d{2})/, '$1-$2-$3'),
    cliente_id: '',
    estado: 'todos',
  }
  aplicarFiltros()
}

// Función para exportar datos
const exportarDatos = () => {
  router.visit('/reportes/prestamos-por-cliente/export', {
    data: filtros.value,
    onSuccess: (response) => {
      // Crear y descargar archivo JSON
      const dataStr = JSON.stringify(response.props.data, null, 2)
      const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr)

      const exportFileDefaultName = response.props.filename || 'reporte_prestamos_por_cliente.json'

      const linkElement = document.createElement('a')
      linkElement.setAttribute('href', dataUri)
      linkElement.setAttribute('download', exportFileDefaultName)
      linkElement.click()

      notyf.success('Reporte exportado correctamente')
      showExportModal.value = false
    },
    onError: () => {
      notyf.error('Error al exportar el reporte')
    }
  })
}

// Función para ver préstamos de un cliente específico
const verPrestamosCliente = (clienteId) => {
  filtros.value.cliente_id = clienteId
  aplicarFiltros()
}

// Función para calcular estadísticas por cliente
const calcularEstadisticasCliente = (clienteData) => {
  const resumen = clienteData.resumen_financiero
  return {
    ...resumen,
    progreso_general: resumen.numero_prestamos > 0
      ? ((resumen.prestamos_activos + resumen.prestamos_completados) / resumen.numero_prestamos * 100)
      : 0
  }
}

// Computed para estadísticas formateadas
const estadisticasFormateadas = computed(() => ({
  total_clientes: props.estadisticas.total_clientes || 0,
  total_prestamos: props.estadisticas.total_prestamos || 0,
  total_prestado: formatearMoneda(props.estadisticas.total_prestado || 0),
  total_pagado: formatearMoneda(props.estadisticas.total_pagado || 0),
  total_pendiente: formatearMoneda(props.estadisticas.total_pendiente || 0),
  total_interes: formatearMoneda(props.estadisticas.total_interes || 0),
  prestamos_activos: props.estadisticas.prestamos_activos || 0,
  prestamos_completados: props.estadisticas.prestamos_completados || 0,
  prestamos_cancelados: props.estadisticas.prestamos_cancelados || 0,
}))
</script>

<template>
  <Head title="Reporte de Préstamos por Cliente" />

  <div class="prestamos-por-cliente min-h-screen bg-[var(--ui-surface)]">
    <!-- Contenido principal -->
    <div class="w-full px-6 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl p-6 text-white">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-3xl font-bold tracking-tight mb-2">Préstamos por Cliente</h1>
              <p class="text-blue-100 text-lg">Reporte consolidado de préstamos agrupados por cliente</p>
              <div class="flex items-center gap-4 mt-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white/20 text-white border border-white/30">
                  📊 Reporte Financiero
                </span>
                <span class="text-sm text-blue-100">
                  {{ estadisticasFormateadas.total_clientes }} clientes • {{ estadisticasFormateadas.total_prestamos }} préstamos
                </span>
              </div>
            </div>
            <div class="hidden md:flex items-center gap-2">
              <div class="text-center">
                <div class="text-2xl font-bold">{{ estadisticasFormateadas.total_prestamos }}</div>
                <div class="text-xs text-blue-100 uppercase tracking-wide">Total Préstamos</div>
              </div>
              <div class="w-px h-12 bg-blue-400"></div>
              <div class="text-center">
                <div class="text-2xl font-bold text-emerald-300">{{ estadisticasFormateadas.total_prestado }}</div>
                <div class="text-xs text-blue-100 uppercase tracking-wide">Total Prestado</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-6 mt-4">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Fecha inicio -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">Fecha Inicio</label>
              <input
                v-model="filtros.fecha_inicio"
                type="date"
                class="w-full border border-slate-300 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent"
              />
            </div>

            <!-- Fecha fin -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">Fecha Fin</label>
              <input
                v-model="filtros.fecha_fin"
                type="date"
                class="w-full border border-slate-300 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent"
              />
            </div>

            <!-- Cliente -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">Cliente</label>
              <select
                v-model="filtros.cliente_id"
                class="w-full border border-slate-300 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent"
              >
                <option value="">Todos los clientes</option>
                <option
                  v-for="cliente in clientes"
                  :key="cliente.id"
                  :value="cliente.id"
                >
                  {{ cliente.nombre_razon_social }}
                </option>
              </select>
            </div>

            <!-- Estado -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">Estado</label>
              <select
                v-model="filtros.estado"
                class="w-full border border-slate-300 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent"
              >
                <option value="todos">Todos los estados</option>
                <option value="activo">Activo</option>
                <option value="completado">Completado</option>
                <option value="cancelado">Cancelado</option>
              </select>
            </div>
          </div>

          <!-- Botones de acción -->
          <div class="flex flex-wrap gap-3 mt-4">
            <button
              @click="aplicarFiltros"
              :disabled="loading"
              class="inline-flex items-center px-6 py-3 bg-sky-600 hover:bg-sky-700 disabled:bg-blue-400 text-white text-sm font-semibold rounded-xl transition-all duration-200 shadow-xl hover:shadow-xl"
            >
              <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
              </svg>
              Aplicar Filtros
            </button>

            <button
              @click="limpiarFiltros"
              class="inline-flex items-center px-4 py-2 border border-slate-300 text-sm font-medium rounded-xl text-slate-700 bg-white hover:bg-white transition-colors"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
              Limpiar
            </button>

            <button
              @click="exportarDatos"
              class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-all duration-200 shadow-xl hover:shadow-xl"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l4-4m-4 4l-4-4m14 2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2z"></path>
              </svg>
              Exportar
            </button>
          </div>
        </div>
      </div>

      <!-- Estadísticas generales -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
          <div class="flex items-center">
            <div class="w-10 h-10 bg-sky-100 rounded-xl flex items-center justify-center mr-4">
              <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
            </div>
            <div>
              <div class="text-2xl font-bold text-slate-900">{{ estadisticasFormateadas.total_clientes }}</div>
              <div class="text-sm text-slate-500">Clientes con Préstamos</div>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
          <div class="flex items-center">
            <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center mr-4">
              <svg class="w-10 h-10 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div>
              <div class="text-2xl font-bold text-slate-900">{{ estadisticasFormateadas.total_prestado }}</div>
              <div class="text-sm text-slate-500">Total Prestado</div>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
          <div class="flex items-center">
            <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center mr-4">
              <svg class="w-10 h-10 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div>
              <div class="text-2xl font-bold text-slate-900">{{ estadisticasFormateadas.total_pagado }}</div>
              <div class="text-sm text-slate-500">Total Pagado</div>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
          <div class="flex items-center">
            <div class="w-10 h-10 bg-brand-100 rounded-xl flex items-center justify-center mr-4">
              <svg class="w-10 h-10 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div>
              <div class="text-2xl font-bold text-slate-900">{{ estadisticasFormateadas.total_pendiente }}</div>
              <div class="text-sm text-slate-500">Total Pendiente</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Lista de clientes con préstamos -->
      <div class="space-y-6">
        <div v-if="prestamosPorCliente.length === 0" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-12 text-center">
          <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
          </div>
          <h3 class="text-lg font-medium text-slate-900 mb-2">No hay préstamos</h3>
          <p class="text-slate-500">No se encontraron préstamos con los filtros aplicados.</p>
        </div>

        <div v-else v-for="clienteData in prestamosPorCliente" :key="clienteData.cliente_id" class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
          <!-- Header del cliente -->
          <div class="bg-gradient-to-r from-slate-50 to-slate-100 px-6 py-4 border-b border-slate-200">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-4">
                <div class="w-10 h-10 bg-sky-100 rounded-xl flex items-center justify-center">
                  <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                  </svg>
                </div>
                <div>
                  <h3 class="text-lg font-semibold text-slate-900">{{ clienteData.cliente.nombre_razon_social }}</h3>
                  <p class="text-sm text-slate-500">{{ clienteData.cliente.rfc }}</p>
                </div>
              </div>
              <div class="flex items-center space-x-4">
                <button
                  @click="verPrestamosCliente(clienteData.cliente.id)"
                  class="inline-flex items-center px-3 py-2 bg-sky-600 hover:bg-sky-700 text-white text-sm font-medium rounded-xl transition-colors"
                >
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                  </svg>
                  Ver solo este cliente
                </button>
              </div>
            </div>
          </div>

          <!-- Resumen financiero del cliente -->
          <div class="px-6 py-4 bg-sky-50 dark:bg-sky-900/20 border-b border-slate-200">
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
              <div class="text-center">
                <div class="text-lg font-bold text-blue-900">{{ formatearMoneda(clienteData.resumen_financiero.total_prestado) }}</div>
                <div class="text-xs text-sky-800 dark:text-sky-200">Total Prestado</div>
              </div>
              <div class="text-center">
                <div class="text-lg font-bold text-emerald-900">{{ formatearMoneda(clienteData.resumen_financiero.total_pagado) }}</div>
                <div class="text-xs text-emerald-800 dark:text-emerald-200 dark:text-emerald-200">Total Pagado</div>
              </div>
              <div class="text-center">
                <div class="text-lg font-bold text-orange-900">{{ formatearMoneda(clienteData.resumen_financiero.total_pendiente) }}</div>
                <div class="text-xs text-orange-700">Total Pendiente</div>
              </div>
              <div class="text-center">
                <div class="text-lg font-bold text-purple-900">{{ formatearMoneda(clienteData.resumen_financiero.total_interes) }}</div>
                <div class="text-xs text-purple-700">Interés Total</div>
              </div>
              <div class="text-center">
                <div class="text-lg font-bold text-slate-900">{{ clienteData.resumen_financiero.numero_prestamos }}</div>
                <div class="text-xs text-slate-700">Préstamos</div>
              </div>
              <div class="text-center">
                <div class="text-lg font-bold text-indigo-900">{{ formatearPorcentaje(calcularEstadisticasCliente(clienteData).progreso_general) }}</div>
                <div class="text-xs text-indigo-700">Progreso</div>
              </div>
            </div>
          </div>

          <!-- Lista de préstamos del cliente -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
              <thead class="bg-slate-50 dark:bg-slate-800/50">
                <tr>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">ID Préstamo</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Monto</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pagos</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Fechas</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Acciones</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                <tr v-for="prestamo in clienteData.prestamos" :key="prestamo.id" class="hover:bg-white">
                  <td class="px-6 py-4">
                    <div class="text-sm font-medium text-slate-900">#{{ prestamo.id }}</div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="text-sm text-slate-900">{{ formatearMoneda(prestamo.monto_prestado) }}</div>
                    <div class="text-xs text-slate-500">Pago: {{ formatearMoneda(prestamo.pago_periodico) }}</div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="text-sm text-slate-900">{{ prestamo.pagos_realizados }} / {{ prestamo.numero_pagos }}</div>
                    <div class="text-xs text-slate-500">{{ formatearPorcentaje(prestamo.progreso) }} completado</div>
                  </td>
                  <td class="px-6 py-4">
                    <span :class="obtenerClasesEstado(prestamo.estado)" class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium">
                      <span :class="obtenerColorPuntoEstado(prestamo.estado)" class="w-2 h-2 rounded-full mr-1.5"></span>
                      {{ obtenerLabelEstado(prestamo.estado) }}
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <div class="text-sm text-slate-900">Inicio: {{ formatearFecha(prestamo.fecha_inicio) }}</div>
                    <div class="text-xs text-slate-500">Primer pago: {{ formatearFecha(prestamo.fecha_primer_pago) }}</div>
                  </td>
                  <td class="px-6 py-4 text-right">
                    <div class="flex items-center justify-end space-x-2">
                      <Link
                        :href="`/prestamos/${prestamo.id}`"
                        class="inline-flex items-center px-3 py-1.5 bg-sky-50 dark:bg-sky-900/20 text-blue-600 hover:bg-sky-100 text-xs font-medium rounded-xl transition-colors"
                      >
                        Ver Detalles
                      </Link>
                      <Link
                        :href="`/prestamos/${prestamo.id}/pagare`"
                        class="inline-flex items-center px-3 py-1.5 bg-rose-50 dark:bg-rose-900/20 text-rose-600 hover:bg-rose-100 text-xs font-medium rounded-xl transition-colors"
                      >
                        Pagaré
                      </Link>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
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

    <!-- Loading overlay -->
    <div v-if="loading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded-2xl shadow-xl">
        <div class="flex items-center space-x-3">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
          <span class="text-slate-700">Aplicando filtros...</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.prestamos-por-cliente {
  min-height: 100vh;
}
</style>
