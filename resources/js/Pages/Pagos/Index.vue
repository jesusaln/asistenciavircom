<!-- /resources/js/Pages/Pagos/Index.vue -->
<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

const props = defineProps({
  pagos: {
    type: Object,
    default: () => ({ data: [] })
  },
  estadisticas: {
    type: Object,
    default: () => ({
      total_vencido: 0,
      total_pendiente: 0,
      pagos_vencidos: 0,
      pagos_pendientes: 0,
    })
  },
  prestamos: {
    type: Array,
    default: () => []
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  sorting: {
    type: Object,
    default: () => ({ sort_by: 'fecha_programada', sort_direction: 'asc' })
  },
  pagination: {
    type: Object,
    default: () => ({})
  },
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
   Estado local y modal
========================= */
const showModal = ref(false)
const modalMode = ref('details')
const selectedPago = ref(null)
const selectedId = ref(null)
const loading = ref(false)

/* =========================
   Filtros, orden y datos
========================= */
const searchTerm = ref('')
const sortBy = ref('fecha_programada-asc')
const filtroEstado = ref('')
const filtroPrestamo = ref('')

/* =========================
   Paginación del servidor
========================= */
const paginationData = computed(() => ({
  current_page: props.pagination?.current_page || 1,
  last_page: props.pagination?.last_page || 1,
  per_page: props.pagination?.per_page || 15,
  from: props.pagination?.from || 0,
  to: props.pagination?.to || 0,
  total: props.pagination?.total || 0,
}))

const goToPage = (page) => {
  const query = {
    page,
    search: searchTerm.value,
    estado: filtroEstado.value,
    prestamo_id: filtroPrestamo.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'asc'
  }
  router.visit('/pagos', { data: query })
}

const nextPage = () => {
  const currentPage = props.pagination?.current_page || 1
  const lastPage = props.pagination?.last_page || 1

  if (currentPage < lastPage) {
    goToPage(currentPage + 1)
  }
}

const prevPage = () => {
  const currentPage = props.pagination?.current_page || 1

  if (currentPage > 1) {
    goToPage(currentPage - 1)
  }
}

const handleLimpiarFiltros = () => {
  searchTerm.value = ''
  sortBy.value = 'fecha_programada-asc'
  filtroEstado.value = ''
  filtroPrestamo.value = ''
  router.visit('/pagos')
  notyf.success('Filtros limpiados correctamente')
}

const updateSort = (newSort) => {
  if (newSort && typeof newSort === 'string') {
    sortBy.value = newSort
    const query = {
      sort_by: newSort.split('-')[0],
      sort_direction: newSort.split('-')[1] || 'asc',
      search: searchTerm.value,
      estado: filtroEstado.value,
      prestamo_id: filtroPrestamo.value
    }
    router.visit('/pagos', { data: query })
  }
}

const changePerPage = (event) => {
  const perPage = event.target.value
  const query = {
    per_page: perPage,
    search: searchTerm.value,
    estado: filtroEstado.value,
    prestamo_id: filtroPrestamo.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'asc'
  }
  router.visit('/pagos', { data: query })
}

const handleSearch = () => {
  const query = {
    search: searchTerm.value,
    estado: filtroEstado.value,
    prestamo_id: filtroPrestamo.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'asc'
  }
  router.visit('/pagos', { data: query })
}

const handleFilter = () => {
  const query = {
    search: searchTerm.value,
    estado: filtroEstado.value,
    prestamo_id: filtroPrestamo.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'asc'
  }
  router.visit('/pagos', { data: query })
}

/* =========================
   Validaciones y utilidades
========================= */
function validarPago(pago) {
  if (!pago?.id) {
    throw new Error('ID de pago no válido')
  }
  return true
}

/* =========================
   Acciones CRUD
========================= */
const verDetalles = (pago) => {
  try {
    validarPago(pago)
    selectedPago.value = pago
    modalMode.value = 'details'
    showModal.value = true
  } catch (error) {
    notyf.error(error.message)
  }
}

const registrarPago = (pago) => {
  try {
    const pagoId = pago?.id
    if (!pagoId) throw new Error('ID de pago no válido')

    router.visit(`/pagos/create?prestamo_id=${pago.prestamo_id}&pago_id=${pagoId}`)
  } catch (error) {
    notyf.error(error.message)
  }
}

const confirmarEliminacion = (id) => {
  try {
    if (!id) throw new Error('ID de pago no válido')

    selectedId.value = id
    modalMode.value = 'confirm'
    showModal.value = true
  } catch (error) {
    notyf.error(error.message)
  }
}

const eliminarPago = async () => {
  try {
    if (!selectedId.value) throw new Error('No se seleccionó ningún pago')

    loading.value = true

    router.delete(`/pagos/${selectedId.value}`, {
      onStart: () => {
        notyf.success('Eliminando pago...')
      },
      onSuccess: (response) => {
        notyf.success('Pago eliminado exitosamente')
        showModal.value = false
        selectedId.value = null
      },
      onError: (errors) => {
        console.error('Error al eliminar:', errors)
        notyf.error('Error al eliminar el pago')
      },
      onFinish: () => {
        loading.value = false
      }
    })
  } catch (error) {
    notyf.error(error.message)
    loading.value = false
  }
}

// Configuración de estados para pagos
const configEstados = {
  'pendiente': {
    label: 'Pendiente',
    classes: 'bg-yellow-100 text-yellow-700',
    color: 'bg-yellow-400'
  },
  'pagado': {
    label: 'Pagado',
    classes: 'bg-green-100 text-green-700',
    color: 'bg-green-400'
  },
  'atrasado': {
    label: 'Atrasado',
    classes: 'bg-red-100 text-red-700',
    color: 'bg-red-400'
  },
  'parcial': {
    label: 'Pago Parcial',
    classes: 'bg-orange-100 text-orange-700',
    color: 'bg-orange-400'
  }
};

const obtenerClasesEstado = (estado) => {
  return configEstados[estado]?.classes || 'bg-gray-100 text-gray-700';
}

const obtenerColorPuntoEstado = (estado) => {
  return configEstados[estado]?.color || 'bg-gray-400';
}

const obtenerLabelEstado = (estado) => {
  return configEstados[estado]?.label || 'Pendiente';
}

// Función para formatear moneda
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

// Funciones para Modal
const modalRef = ref(null)

const focusFirst = () => { try { modalRef.value?.focus() } catch {} }
watch(() => showModal, (v) => { if (v) setTimeout(focusFirst, 0) })

const onKey = (e) => { if (e.key === 'Escape' && showModal.value) onClose() }
onMounted(() => window.addEventListener('keydown', onKey))
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))

const onCancel = () => { showModal.value = false; selectedPago.value = null; selectedId.value = null; }
const onConfirm = () => { eliminarPago() }
const onClose = () => { showModal.value = false; selectedPago.value = null; selectedId.value = null; }
</script>

<template>
  <Head title="Pagos de Préstamos" />

  <div class="pagos-index min-h-screen bg-white dark:bg-slate-950 transition-colors duration-300">
    <!-- Contenido principal -->
    <div class="w-full px-6 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100 tracking-tight">Pagos de Préstamos</h1>
            <p class="text-gray-600 dark:text-slate-400 mt-2">Gestiona y registra los pagos de préstamos de tus clientes</p>
          </div>
          <Link
            href="/prestamos"
            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-slate-800 text-sm font-medium rounded-xl text-gray-700 dark:text-slate-300 bg-white dark:bg-slate-900 hover:bg-gray-50 dark:hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200"
          >
            ← Volver a Préstamos
          </Link>
        </div>
      </div>

      <!-- Estadísticas -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <!-- Total Vencido -->
          <div class="bg-white dark:bg-slate-900/40 border border-gray-100 dark:border-slate-800 rounded-2xl p-6 shadow-xl shadow-black/5 hover:border-red-500/50 transition-all duration-300 group">
              <div class="flex items-center">
                  <div class="w-12 h-12 bg-red-50 dark:bg-red-900/20 rounded-xl flex items-center justify-center border border-red-100 dark:border-red-900/30 group-hover:scale-110 transition-transform">
                      <svg class="w-6 h-6 text-red-600 dark:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>
                  </div>
                  <div class="ml-4">
                      <p class="text-sm font-medium text-gray-500 dark:text-slate-400">Total Vencido</p>
                      <p class="text-xl font-bold text-gray-900 dark:text-slate-100 mt-0.5">${{ formatearMoneda(estadisticas.total_vencido) }}</p>
                  </div>
              </div>
          </div>

          <!-- Total Pendiente -->
          <div class="bg-white dark:bg-slate-900/40 border border-gray-100 dark:border-slate-800 rounded-2xl p-6 shadow-xl shadow-black/5 hover:border-orange-500/50 transition-all duration-300 group">
              <div class="flex items-center">
                  <div class="w-12 h-12 bg-orange-50 dark:bg-orange-900/20 rounded-xl flex items-center justify-center border border-orange-100 dark:border-orange-900/30 group-hover:scale-110 transition-transform">
                      <svg class="w-6 h-6 text-orange-600 dark:text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path></svg>
                  </div>
                  <div class="ml-4">
                      <p class="text-sm font-medium text-gray-500 dark:text-slate-400">Total Pendiente</p>
                      <p class="text-xl font-bold text-gray-900 dark:text-slate-100 mt-0.5">${{ formatearMoneda(estadisticas.total_pendiente) }}</p>
                  </div>
              </div>
          </div>

          <!-- Pagos Pendientes -->
          <div class="bg-white dark:bg-slate-900/40 border border-gray-100 dark:border-slate-800 rounded-2xl p-6 shadow-xl shadow-black/5 hover:border-blue-500/50 transition-all duration-300 group">
              <div class="flex items-center">
                  <div class="w-12 h-12 bg-blue-50 dark:bg-blue-900/20 rounded-xl flex items-center justify-center border border-blue-100 dark:border-blue-900/30 group-hover:scale-110 transition-transform">
                      <svg class="w-6 h-6 text-blue-600 dark:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                  </div>
                  <div class="ml-4">
                      <p class="text-sm font-medium text-gray-500 dark:text-slate-400">Pagos Pendientes</p>
                      <p class="text-xl font-bold text-gray-900 dark:text-slate-100 mt-0.5">{{ estadisticas.pagos_pendientes }}</p>
                  </div>
              </div>
          </div>

          <!-- Pagos Vencidos -->
          <div class="bg-white dark:bg-slate-900/40 border border-gray-100 dark:border-slate-800 rounded-2xl p-6 shadow-xl shadow-black/5 hover:border-red-500/50 transition-all duration-300 group">
              <div class="flex items-center">
                  <div class="w-12 h-12 bg-red-50 dark:bg-red-900/20 rounded-xl flex items-center justify-center border border-red-100 dark:border-red-900/30 group-hover:scale-110 transition-transform">
                      <svg class="w-6 h-6 text-red-600 dark:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                  </div>
                  <div class="ml-4">
                      <p class="text-sm font-medium text-gray-500 dark:text-slate-400">Pagos Vencidos</p>
                      <p class="text-xl font-bold text-gray-900 dark:text-slate-100 mt-0.5">{{ estadisticas.pagos_vencidos }}</p>
                  </div>
              </div>
          </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl shadow-black/5 border border-gray-100 dark:border-slate-800 overflow-hidden mb-8 transition-colors duration-300">
        <div class="px-6 py-4 bg-gray-50/50 dark:bg-slate-900/50 border-b border-gray-200 dark:border-slate-800/60">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
            <!-- Filtros -->
            <div class="flex items-center space-x-4">
              <!-- Filtro de préstamo -->
              <div class="relative">
                <select
                  v-model="filtroPrestamo"
                  @change="handleFilter"
                  class="block w-72 pl-3 pr-10 py-2.5 text-sm border border-gray-300 dark:border-slate-800 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white dark:bg-slate-950 text-gray-700 dark:text-slate-200 transition-all cursor-pointer"
                >
                  <option value="">Todos los préstamos</option>
                  <option v-for="prestamo in prestamos" :key="prestamo.id" :value="prestamo.id">
                    {{ prestamo.cliente_nombre }} - ${{ formatearMoneda(prestamo.monto_prestado) }}
                  </option>
                </select>
              </div>

              <!-- Filtro de estado -->
              <select
                v-model="filtroEstado"
                @change="handleFilter"
                class="block w-52 pl-3 pr-10 py-2.5 text-sm border border-gray-300 dark:border-slate-800 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white dark:bg-slate-950 text-gray-700 dark:text-slate-200 transition-all cursor-pointer"
              >
                <option value="">Todos los estados</option>
                <option value="pendiente">Pendientes</option>
                <option value="pagado">Pagados</option>
                <option value="atrasado">Atrasados</option>
                <option value="parcial">Parciales</option>
              </select>

              <!-- Limpiar filtros -->
              <button
                @click="handleLimpiarFiltros"
                class="inline-flex items-center px-4 py-2.5 border border-gray-300 dark:border-slate-800 text-sm leading-4 font-semibold rounded-xl text-gray-700 dark:text-slate-300 bg-white dark:bg-slate-950 hover:bg-gray-50 dark:hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 group"
              >
                <span class="mr-2 group-hover:rotate-12 transition-transform inline-block">✨</span>
                Limpiar Filtros
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Información de paginación -->
      <div class="flex justify-between items-center mb-4 text-sm text-gray-600 dark:text-slate-400">
        <div>
          Mostrando <span class="font-semibold text-gray-900 dark:text-slate-200">{{ paginationData.from }}</span> - <span class="font-semibold text-gray-900 dark:text-slate-200">{{ paginationData.to }}</span> de <span class="font-semibold text-gray-900 dark:text-slate-200">{{ paginationData.total }}</span> pagos
        </div>
        <div class="flex items-center space-x-3">
          <span class="text-xs uppercase tracking-wider font-bold text-gray-400 dark:text-slate-500">Por página</span>
          <select
            :value="paginationData.per_page"
            @change="changePerPage"
            class="border border-gray-300 dark:border-slate-800 rounded-xl px-3 py-1.5 text-sm bg-white dark:bg-slate-950 text-gray-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500/20"
          >
            <option value="10">10</option>
            <option value="15">15</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
      </div>

      <!-- Tabla de pagos -->
      <div class="mt-6">
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl shadow-black/5 border border-gray-100 dark:border-slate-800 overflow-hidden">
          <!-- Header -->
          <div class="bg-gradient-to-r from-gray-50 to-gray-100/50 dark:from-slate-900 dark:to-slate-800/50 px-6 py-4 border-b border-gray-200 dark:border-slate-800/60">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-slate-100 tracking-tight">Historial de Pagos</h2>
              <div class="text-xs font-bold uppercase tracking-widest text-gray-500 dark:text-slate-400 bg-white dark:bg-slate-950/70 px-3 py-1.5 rounded-full border border-gray-200 dark:border-slate-800/50">
                {{ paginationData.total }} registros encontrados
              </div>
            </div>
          </div>

          <!-- Table -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800/60">
              <thead class="bg-white dark:bg-slate-950/60 transition-colors">
                <tr>
                  <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-500 uppercase tracking-widest">Folio Prest.</th>
                  <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-500 uppercase tracking-widest">Planificado para</th>
                  <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-500 uppercase tracking-widest">Titular</th>
                  <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-500 uppercase tracking-widest">Monto Cuota</th>
                  <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-500 uppercase tracking-widest">Recibido</th>
                  <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-500 uppercase tracking-widest">Estatus</th>
                  <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-500 uppercase tracking-widest">Demora</th>
                  <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-slate-500 uppercase tracking-widest">Acciones</th>
                </tr>
              </thead>

              <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-800/40">
                <template v-if="props.pagos.data && props.pagos.data.length > 0">
                  <tr
                    v-for="pago in props.pagos.data"
                    :key="pago.id"
                    class="group hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-all duration-150"
                  >
                    <!-- Folio Prestamo -->
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ pago.prestamo?.folio || `#${pago.prestamo_id}` }}</span>
                    </td>
                    <!-- Fecha Programada -->
                    <td class="px-6 py-4">
                      <div class="flex flex-col space-y-0.5">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                          {{ formatearFecha(pago.fecha_programada) }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                          Pago #{{ pago.numero_pago }}
                        </div>
                      </div>
                    </td>

                    <!-- Cliente -->
                    <td class="px-6 py-4">
                      <div class="flex flex-col space-y-0.5">
                        <div class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-gray-800 dark:text-gray-100">
                          {{ pago.prestamo?.cliente?.nombre_razon_social || 'Sin cliente' }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                          {{ pago.prestamo?.cliente?.rfc || '' }}
                        </div>
                      </div>
                    </td>

                    <!-- Monto Programado -->
                    <td class="px-6 py-4">
                      <div class="text-sm font-medium text-gray-900 dark:text-white">
                        ${{ formatearMoneda(pago.monto_programado) }}
                      </div>
                    </td>

                    <!-- Monto Pagado -->
                    <td class="px-6 py-4">
                      <div v-if="pago.monto_pagado > 0" class="text-sm font-medium text-green-600">
                        ${{ formatearMoneda(pago.monto_pagado) }}
                      </div>
                      <div v-else class="text-sm text-gray-500 dark:text-gray-400">
                        No pagado
                      </div>
                    </td>

                    <!-- Estado -->
                    <td class="px-6 py-4">
                      <span
                        :class="obtenerClasesEstado(pago.estado)"
                        class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium transition-all duration-150 hover:shadow-sm"
                      >
                        <span
                          class="w-2 h-2 rounded-full mr-2 transition-all duration-150"
                          :class="obtenerColorPuntoEstado(pago.estado)"
                        ></span>
                        {{ obtenerLabelEstado(pago.estado) }}
                      </span>
                    </td>

                    <!-- Días Atraso -->
                    <td class="px-6 py-4">
                      <div v-if="pago.dias_atraso > 0" class="text-sm text-red-600 font-medium">
                        {{ pago.dias_atraso }} días
                      </div>
                      <div v-else class="text-sm text-gray-500 dark:text-gray-400">
                        A tiempo
                      </div>
                    </td>

                    <!-- Acciones -->
                    <td class="px-6 py-4">
                      <div class="flex items-center justify-end space-x-2">
                        <!-- Ver detalles -->
                        <button
                          @click="verDetalles(pago)"
                          class="group/btn relative inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 hover:text-blue-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:ring-offset-1"
                          title="Ver detalles"
                        >
                          <svg class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                          </svg>
                        </button>

                        <!-- Registrar pago (solo pendientes) -->
                        <button
                          v-if="pago.estado === 'pendiente' || pago.estado === 'parcial'"
                          @click="registrarPago(pago)"
                          class="group/btn relative inline-flex items-center justify-center w-8 h-8 rounded-lg bg-green-50 text-green-600 hover:bg-green-100 hover:text-green-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500/50 focus:ring-offset-1"
                          title="Registrar pago"
                        >
                          <svg class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                          </svg>
                        </button>

                        <!-- Editar pago (solo pagados) -->
                        <button
                          v-if="pago.estado === 'pagado'"
                          @click="editarPago(pago.id)"
                          class="group/btn relative inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 hover:text-amber-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:ring-offset-1"
                          title="Editar pago"
                        >
                          <svg class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                        </button>
                      </div>
                    </td>
                  </tr>
                </template>

                <tr v-else>
                  <td :colspan="8" class="px-6 py-20 text-center">
                    <div class="flex flex-col items-center space-y-4">
                      <div class="w-20 h-20 bg-gray-50 dark:bg-slate-800/50 rounded-full flex items-center justify-center border border-gray-100 dark:border-slate-700/50 shadow-inner">
                        <svg class="w-10 h-10 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                      </div>
                      <div class="space-y-1">
                        <p class="text-gray-900 dark:text-slate-100 font-semibold text-lg">No hay cobros registrados</p>
                        <p class="text-gray-500 dark:text-slate-400 max-w-xs mx-auto">La lista de pagos programados aparecerá aquí una vez que generes nuevos préstamos.</p>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Controles de paginación -->
      <div v-if="paginationData.last_page > 1" class="flex justify-center items-center space-x-2 mt-6">
        <button
          @click="prevPage"
          :disabled="paginationData.current_page === 1"
          class="px-3 py-2 text-sm font-medium text-gray-500 dark:text-gray-400 bg-white dark:bg-slate-900 border border-gray-300 rounded-md hover:bg-white dark:bg-slate-900 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Anterior
        </button>

        <div class="flex space-x-1">
          <button
            v-for="page in [paginationData.current_page - 1, paginationData.current_page, paginationData.current_page + 1].filter(p => p > 0 && p <= paginationData.last_page)"
            :key="page"
            @click="goToPage(page)"
            :class="[
              'px-3 py-2 text-sm font-medium border border-gray-300 rounded-md',
              page === paginationData.current_page
                ? 'bg-blue-500 text-white border-blue-500'
                : 'text-gray-700 bg-white dark:bg-slate-900 hover:bg-white dark:bg-slate-900'
            ]"
          >
            {{ page }}
          </button>
        </div>

        <button
          @click="nextPage"
          :disabled="paginationData.current_page === paginationData.last_page"
          class="px-3 py-2 text-sm font-medium text-gray-500 dark:text-gray-400 bg-white dark:bg-slate-900 border border-gray-300 rounded-md hover:bg-white dark:bg-slate-900 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Siguiente
        </button>
      </div>
    </div>

    <!-- Modal de detalles / confirmación -->
    <Transition name="modal">
      <div
        v-if="showModal"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
        @click.self="onClose"
      >
        <div
          class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-8 outline-none border border-gray-100 dark:border-slate-800 relative"
          role="dialog"
          aria-modal="true"
          :aria-label="`Modal de Pago`"
          tabindex="-1"
          ref="modalRef"
          @keydown.esc.prevent="onClose"
        >
          <!-- Botón cerrar -->
          <button @click="onClose" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
          </button>
          <!-- Modo: Confirmación de eliminación -->
          <div v-if="modalMode === 'confirm'" class="text-center py-4">
            <div class="w-20 h-20 mx-auto bg-red-50 dark:bg-red-900/20 rounded-full flex items-center justify-center mb-6 border border-red-100 dark:border-red-900/30">
              <svg class="w-10 h-10 text-red-600 dark:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-slate-100 mb-3">
              ¿Eliminar pago?
            </h3>
            <p class="text-gray-600 dark:text-slate-400 mb-8 max-w-sm mx-auto">
              Esta acción es irreversible y podría afectar el saldo pendiente y el historial del préstamo asociado.
            </p>
            <div class="flex gap-4">
              <button
                @click="onCancel"
                class="flex-1 px-6 py-3 bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-slate-700 transition-all"
              >
                No, mantener
              </button>
              <button
                @click="onConfirm"
                class="flex-1 px-6 py-3 bg-red-600 text-white rounded-xl font-semibold hover:bg-red-700 shadow-lg shadow-red-600/20 transition-all"
              >
                Sí, eliminar
              </button>
            </div>
          </div>

          <!-- Modo: Detalles -->
          <div v-else-if="modalMode === 'details'" class="space-y-6">
            <div>
              <h3 class="text-2xl font-bold text-gray-900 dark:text-slate-100 mb-1 flex items-center gap-3">
                Detalles del Pago
                <span v-if="selectedPago?.id" class="text-sm font-medium bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400 px-3 py-1 rounded-full border border-gray-200 dark:border-slate-700">#{{ selectedPago.numero_pago }}</span>
              </h3>
              <p class="text-gray-500 dark:text-slate-400">Información detallada sobre el cobro seleccionado.</p>
            </div>

            <div v-if="selectedPago" class="space-y-6">
              <!-- Información general -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 bg-gray-50/50 dark:bg-slate-950/40 p-6 rounded-2xl border border-gray-100 dark:border-slate-800/50">
                <div class="space-y-3">
                  <div>
                    <p class="text-xs font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-1">Cliente</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-slate-100">{{ selectedPago.prestamo?.cliente?.nombre_razon_social || 'Sin cliente' }}</p>
                  </div>
                  <div>
                    <p class="text-xs font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-1">ID Préstamo</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-slate-100">#{{ selectedPago.prestamo_id }}</p>
                  </div>
                  <div>
                    <p class="text-xs font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-1">Estatus Actual</p>
                    <span
                      :class="obtenerClasesEstado(selectedPago.estado)"
                      class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold mt-1"
                    >
                      <span class="w-2 h-2 rounded-full mr-2" :class="obtenerColorPuntoEstado(selectedPago.estado)"></span>
                      {{ obtenerLabelEstado(selectedPago.estado) }}
                    </span>
                  </div>
                </div>

                <div class="space-y-3">
                  <div>
                    <p class="text-xs font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-1">Fecha Programada</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-slate-100">{{ formatearFecha(selectedPago.fecha_programada) }}</p>
                  </div>
                  <div v-if="selectedPago.fecha_pago">
                    <p class="text-xs font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-1">Fecha de Pago</p>
                    <p class="text-sm font-semibold text-green-600">{{ formatearFecha(selectedPago.fecha_pago) }}</p>
                  </div>
                  <div>
                    <p class="text-xs font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-1">Fecha Registro</p>
                    <p class="text-sm font-semibold text-gray-600 dark:text-slate-400">{{ formatearFecha(selectedPago.fecha_registro) }}</p>
                  </div>
                </div>
              </div>

              <!-- Información financiera -->
              <div class="bg-blue-50/30 dark:bg-blue-900/10 rounded-2xl p-6 border border-blue-100/50 dark:border-blue-900/20">
                <h4 class="text-xs font-bold text-blue-600 dark:text-blue-500 uppercase tracking-widest mb-4">Resumen Financiero</h4>
                <div class="grid grid-cols-2 gap-6">
                  <div>
                    <p class="text-xs text-blue-500/70 dark:text-blue-400/50 mb-1">Monto Programado</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-slate-100 tracking-tight">${{ formatearMoneda(selectedPago.monto_programado) }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-blue-500/70 dark:text-blue-400/50 mb-1">Monto Recibido</p>
                    <p class="text-2xl font-bold text-green-600 tracking-tight">${{ formatearMoneda(selectedPago.monto_pagado) }}</p>
                  </div>
                  <div v-if="selectedPago.dias_atraso > 0" class="col-span-2 pt-2 border-t border-blue-100/30 dark:border-blue-900/10">
                    <div class="flex items-center gap-2 text-red-600 dark:text-red-500">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                      <p class="text-sm font-bold">Demora: {{ selectedPago.dias_atraso }} días naturales</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Botones de acción -->
            <div class="flex gap-4 pt-4">
               <button
                @click="onClose"
                class="flex-1 px-6 py-3 bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-200 rounded-xl font-bold hover:bg-gray-200 dark:hover:bg-slate-700 transition-all"
              >
                Cerrar
              </button>
              <button
                v-if="selectedPago.estado === 'pendiente' || selectedPago.estado === 'parcial'"
                @click="registrarPago(selectedPago)"
                class="flex-2 px-8 py-3 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-600/20 transition-all flex items-center justify-center gap-2"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Registrar Cobro
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Loading overlay -->
    <div v-if="loading" class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm flex items-center justify-center z-[100] transition-all">
      <div class="bg-white dark:bg-slate-900 p-8 rounded-2xl shadow-2xl border border-gray-100 dark:border-slate-800 flex flex-col items-center space-y-4">
        <div class="relative w-12 h-12">
          <div class="absolute inset-0 border-4 border-blue-500/20 rounded-full"></div>
          <div class="absolute inset-0 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
        </div>
        <span class="text-gray-900 dark:text-slate-100 font-semibold tracking-wide">Procesando pago...</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.pagos-index {
  min-height: 100vh;
}

.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
  transform: scale(0.97);
}
.modal-enter-to,
.modal-leave-from {
  opacity: 1;
  transform: scale(1);
}
</style>




