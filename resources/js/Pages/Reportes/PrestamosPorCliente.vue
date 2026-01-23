<!-- /resources/js/Pages/Reportes/PrestamosPorCliente.vue -->
<script setup>
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
    classes: 'bg-green-100 text-green-700',
    color: 'bg-green-400'
  },
  'completado': {
    label: 'Completado',
    classes: 'bg-blue-100 text-blue-700',
    color: 'bg-blue-400'
  },
  'cancelado': {
    label: 'Cancelado',
    classes: 'bg-red-100 text-red-700',
    color: 'bg-red-400'
  }
}

const obtenerClasesEstado = (estado) => {
  return configEstados[estado]?.classes || 'bg-gray-100 text-gray-700'
}

const obtenerColorPuntoEstado = (estado) => {
  return configEstados[estado]?.color || 'bg-gray-400'
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

  <div class="prestamos-por-cliente min-h-screen bg-white dark:bg-slate-900">
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
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white dark:bg-slate-900/20 text-white border border-white/30">
                  📊 Reporte Financiero
                </span>
                <span class="text-sm text-blue-100">
                  {{ estadisticasFormateadas.total_clientes }} clientes • {{ estadisticasFormateadas.total_prestamos }} préstamos
                </span>
              </div>
            </div>
            <div class="hidden md:flex items-center gap-3">
              <div class="text-center">
                <div class="text-2xl font-bold">{{ estadisticasFormateadas.total_prestamos }}</div>
                <div class="text-xs text-blue-100 uppercase tracking-wide">Total Préstamos</div>
              </div>
              <div class="w-px h-12 bg-blue-400"></div>
              <div class="text-center">
                <div class="text-2xl font-bold text-green-300">{{ estadisticasFormateadas.total_prestado }}</div>
                <div class="text-xs text-blue-100 uppercase tracking-wide">Total Prestado</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-100 p-6 mt-4">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Fecha inicio -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Inicio</label>
              <input
                v-model="filtros.fecha_inicio"
                type="date"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <!-- Fecha fin -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Fin</label>
              <input
                v-model="filtros.fecha_fin"
                type="date"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <!-- Cliente -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Cliente</label>
              <select
                v-model="filtros.cliente_id"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
              <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
              <select
                v-model="filtros.estado"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
              class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white text-sm font-semibold rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl"
            >
              <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
              </svg>
              Aplicar Filtros
            </button>

            <button
              @click="limpiarFiltros"
              class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-xl text-gray-700 bg-white dark:bg-slate-900 hover:bg-white dark:bg-slate-900 transition-colors"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
              Limpiar
            </button>

            <button
              @click="exportarDatos"
              class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl"
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
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-100 p-6">
          <div class="flex items-center">
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mr-4">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
            </div>
            <div>
              <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ estadisticasFormateadas.total_clientes }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-300">Clientes con Préstamos</div>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-100 p-6">
          <div class="flex items-center">
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mr-4">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div>
              <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ estadisticasFormateadas.total_prestado }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-300">Total Prestado</div>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-100 p-6">
          <div class="flex items-center">
            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mr-4">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div>
              <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ estadisticasFormateadas.total_pagado }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-300">Total Pagado</div>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-100 p-6">
          <div class="flex items-center">
            <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mr-4">
              <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div>
              <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ estadisticasFormateadas.total_pendiente }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-300">Total Pendiente</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Lista de clientes con préstamos -->
      <div class="space-y-6">
        <div v-if="prestamosPorCliente.length === 0" class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-100 p-12 text-center">
          <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
          </div>
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No hay préstamos</h3>
          <p class="text-gray-600 dark:text-gray-300">No se encontraron préstamos con los filtros aplicados.</p>
        </div>

        <div v-else v-for="clienteData in prestamosPorCliente" :key="clienteData.cliente_id" class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-100 overflow-hidden">
          <!-- Header del cliente -->
          <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200 dark:border-slate-800">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                  <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                  </svg>
                </div>
                <div>
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ clienteData.cliente.nombre_razon_social }}</h3>
                  <p class="text-sm text-gray-600 dark:text-gray-300">{{ clienteData.cliente.rfc }}</p>
                </div>
              </div>
              <div class="flex items-center space-x-4">
                <button
                  @click="verPrestamosCliente(clienteData.cliente.id)"
                  class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors"
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
          <div class="px-6 py-4 bg-blue-50 border-b border-gray-200 dark:border-slate-800">
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
              <div class="text-center">
                <div class="text-lg font-bold text-blue-900">{{ formatearMoneda(clienteData.resumen_financiero.total_prestado) }}</div>
                <div class="text-xs text-blue-700">Total Prestado</div>
              </div>
              <div class="text-center">
                <div class="text-lg font-bold text-green-900">{{ formatearMoneda(clienteData.resumen_financiero.total_pagado) }}</div>
                <div class="text-xs text-green-700">Total Pagado</div>
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
                <div class="text-lg font-bold text-gray-900 dark:text-white">{{ clienteData.resumen_financiero.numero_prestamos }}</div>
                <div class="text-xs text-gray-700">Préstamos</div>
              </div>
              <div class="text-center">
                <div class="text-lg font-bold text-indigo-900">{{ formatearPorcentaje(calcularEstadisticasCliente(clienteData).progreso_general) }}</div>
                <div class="text-xs text-indigo-700">Progreso</div>
              </div>
            </div>
          </div>

          <!-- Lista de préstamos del cliente -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
              <thead class="bg-white dark:bg-slate-900">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID Préstamo</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Monto</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pagos</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fechas</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-800">
                <tr v-for="prestamo in clienteData.prestamos" :key="prestamo.id" class="hover:bg-white dark:bg-slate-900">
                  <td class="px-6 py-4">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">#{{ prestamo.id }}</div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="text-sm text-gray-900 dark:text-white">{{ formatearMoneda(prestamo.monto_prestado) }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Pago: {{ formatearMoneda(prestamo.pago_periodico) }}</div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="text-sm text-gray-900 dark:text-white">{{ prestamo.pagos_realizados }} / {{ prestamo.numero_pagos }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ formatearPorcentaje(prestamo.progreso) }} completado</div>
                  </td>
                  <td class="px-6 py-4">
                    <span :class="obtenerClasesEstado(prestamo.estado)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                      <span :class="obtenerColorPuntoEstado(prestamo.estado)" class="w-2 h-2 rounded-full mr-1.5"></span>
                      {{ obtenerLabelEstado(prestamo.estado) }}
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <div class="text-sm text-gray-900 dark:text-white">Inicio: {{ formatearFecha(prestamo.fecha_inicio) }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Primer pago: {{ formatearFecha(prestamo.fecha_primer_pago) }}</div>
                  </td>
                  <td class="px-6 py-4 text-right">
                    <div class="flex items-center justify-end space-x-2">
                      <Link
                        :href="`/prestamos/${prestamo.id}`"
                        class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 text-xs font-medium rounded-lg transition-colors"
                      >
                        Ver Detalles
                      </Link>
                      <Link
                        :href="`/prestamos/${prestamo.id}/pagare`"
                        class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-600 hover:bg-red-100 text-xs font-medium rounded-lg transition-colors"
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
          class="inline-flex items-center px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white text-sm font-semibold rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
          </svg>
          Volver a Reportes
        </Link>
      </div>
    </div>

    <!-- Loading overlay -->
    <div v-if="loading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-slate-900 p-6 rounded-lg shadow-lg">
        <div class="flex items-center space-x-3">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
          <span class="text-gray-700">Aplicando filtros...</span>
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
