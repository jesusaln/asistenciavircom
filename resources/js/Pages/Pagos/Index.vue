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

/** ID del último abono en historial (comprobante oficial). */
const idHistorialParaComprobante = (pago) => {
  const raw = pago?.historial_pagos ?? pago?.historialPagos ?? []
  if (!Array.isArray(raw) || raw.length === 0) return null
  const sorted = [...raw].sort((a, b) => {
    const ta = new Date(a.fecha_pago ?? a.created_at ?? 0).getTime()
    const tb = new Date(b.fecha_pago ?? b.created_at ?? 0).getTime()
    return tb - ta
  })
  return sorted[0]?.id ?? null
}

const editarPago = (id) => {
  router.visit(route('pagos.edit', id))
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

// Configuración de estados para pagos (Dark Mode)
const configEstados = {
  'pendiente': {
    label: 'Pendiente',
    classes: 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
    color: 'bg-amber-400'
  },
  'pagado': {
    label: 'Pagado',
    classes: 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
    color: 'bg-emerald-400'
  },
  'atrasado': {
    label: 'Atrasado',
    classes: 'bg-rose-500/10 text-rose-400 border border-rose-500/20',
    color: 'bg-rose-400'
  },
  'parcial': {
    label: 'Pago Parcial',
    classes: 'bg-blue-500/10 text-blue-400 border border-blue-500/20',
    color: 'bg-blue-400'
  }
};

const obtenerClasesEstado = (estado) => {
  return configEstados[estado]?.classes || 'bg-slate-500/10 text-slate-400 border border-slate-500/20';
}

const obtenerColorPuntoEstado = (estado) => {
  return configEstados[estado]?.color || 'bg-slate-400';
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

  <div class="pagos-index min-h-screen bg-slate-950 text-slate-200 font-sans selection:bg-indigo-500/30">
    <!-- Contenido principal -->
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      
      <!-- Header Premium -->
      <div class="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-6 animate-in fade-in slide-in-from-top-4 duration-700">
        <div class="flex items-center gap-6">
           <div class="relative group">
              <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl blur opacity-25 group-hover:opacity-50 transition duration-500"></div>
              <div class="relative w-16 h-16 rounded-2xl bg-slate-900 border border-white/10 flex items-center justify-center shadow-2xl backdrop-blur-xl">
                 <svg class="w-8 h-8 text-indigo-400 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                 </svg>
              </div>
           </div>
           <div>
              <h1 class="text-4xl font-black text-white tracking-tighter mb-1 uppercase">
                Pagos de <span class="bg-clip-text text-transparent bg-gradient-to-r from-indigo-400 to-purple-400">Préstamos</span>
              </h1>
              <p class="text-slate-500 text-sm font-bold uppercase tracking-widest">Control financiero y seguimiento de cobranza</p>
           </div>
        </div>
 
        <Link
          href="/prestamos"
          class="px-5 py-2.5 bg-slate-900/50 border border-white/10 text-slate-400 text-[10px] font-black uppercase tracking-widest rounded-2xl hover:bg-slate-800 hover:text-white transition-all shadow-xl backdrop-blur-md flex items-center gap-2 group"
        >
          <svg class="w-4 h-4 text-slate-500 group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
          Volver a Préstamos
        </Link>
      </div>

      <!-- Estadísticas Premium (Glow Effect) -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
          <!-- Total Vencido -->
          <div class="relative group h-full">
              <div class="absolute -inset-0.5 bg-gradient-to-r from-rose-500 to-red-600 rounded-3xl blur opacity-20 group-hover:opacity-40 transition duration-500"></div>
              <div class="relative h-full bg-slate-900/80 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl shadow-2xl flex flex-col justify-between overflow-hidden">
                  <div class="flex justify-between items-start">
                    <div class="space-y-1">
                        <p class="text-[10px] font-black text-rose-400 uppercase tracking-[0.2em]">Total Vencido</p>
                        <h3 class="text-3xl font-black text-white tracking-tight">${{ formatearMoneda(estadisticas.total_vencido) }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-rose-500/10 flex items-center justify-center border border-rose-500/20 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                  </div>
                  <div class="mt-4 flex items-center gap-2">
                     <span class="px-2 py-0.5 bg-rose-500/10 text-rose-500 text-[10px] font-black rounded-lg uppercase tracking-tighter">{{ estadisticas.pagos_vencidos }} Pagos</span>
                     <span class="text-[9px] text-slate-500 font-bold uppercase tracking-widest italic">Vencido</span>
                  </div>
              </div>
          </div>
 
          <!-- Total Pendiente -->
          <div class="relative group h-full">
              <div class="absolute -inset-0.5 bg-gradient-to-r from-amber-500 to-orange-600 rounded-3xl blur opacity-20 group-hover:opacity-40 transition duration-500"></div>
              <div class="relative h-full bg-slate-900/80 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl shadow-2xl flex flex-col justify-between overflow-hidden">
                  <div class="flex justify-between items-start">
                    <div class="space-y-1">
                        <p class="text-[10px] font-black text-amber-400 uppercase tracking-[0.2em]">Total Pendiente</p>
                        <h3 class="text-3xl font-black text-white tracking-tight">${{ formatearMoneda(estadisticas.total_pendiente) }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/10 flex items-center justify-center border border-amber-500/20 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                  </div>
                  <div class="mt-4 flex items-center gap-2">
                     <span class="px-2 py-0.5 bg-amber-500/10 text-amber-500 text-[10px] font-black rounded-lg uppercase tracking-tighter">{{ estadisticas.pagos_pendientes }} Pagos</span>
                     <span class="text-[9px] text-slate-500 font-bold uppercase tracking-widest italic">Pendiente</span>
                  </div>
              </div>
          </div>
 
          <!-- Conteo Pendientes -->
          <div class="relative group h-full">
              <div class="absolute -inset-0.5 bg-gradient-to-r from-indigo-500 to-blue-600 rounded-3xl blur opacity-10 group-hover:opacity-30 transition duration-500"></div>
              <div class="relative h-full bg-slate-900/80 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl shadow-2xl flex flex-col justify-between overflow-hidden">
                  <div class="flex justify-between items-start">
                    <div class="space-y-1">
                        <p class="text-[10px] font-black text-indigo-400 uppercase tracking-[0.2em]">Registros Pend.</p>
                        <h3 class="text-3xl font-black text-white tracking-tight">{{ estadisticas.pagos_pendientes }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 flex items-center justify-center border border-indigo-500/20 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                  </div>
                  <div class="mt-4 flex items-center gap-2">
                     <span class="px-2 py-0.5 bg-indigo-500/10 text-indigo-500 text-[10px] font-black rounded-lg uppercase tracking-tighter">Acción requerida</span>
                  </div>
              </div>
          </div>
 
          <!-- Conteo Vencidos -->
          <div class="relative group h-full">
              <div class="absolute -inset-0.5 bg-gradient-to-r from-rose-600 to-rose-400 rounded-3xl blur opacity-10 group-hover:opacity-30 transition duration-500"></div>
              <div class="relative h-full bg-slate-900/80 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl shadow-2xl flex flex-col justify-between overflow-hidden">
                  <div class="flex justify-between items-start">
                    <div class="space-y-1">
                        <p class="text-[10px] font-black text-rose-500 uppercase tracking-[0.2em]">Cuentas Críticas</p>
                        <h3 class="text-3xl font-black text-white tracking-tight">{{ estadisticas.pagos_vencidos }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-rose-500/10 flex items-center justify-center border border-rose-500/20 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                  </div>
                  <div class="mt-4 flex items-center gap-2">
                     <span class="px-2 py-0.5 bg-rose-500/10 text-rose-600 text-[10px] font-black rounded-lg uppercase tracking-tighter">Prioridad Alta</span>
                  </div>
              </div>
          </div>
      </div>

      <!-- Filtros Toolbar -->
      <div class="bg-slate-900/80 border border-white/5 rounded-2xl p-1 mb-8 backdrop-blur-xl shadow-2xl">
        <div class="px-5 py-4">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0 gap-4">
            <!-- Filtros Group -->
            <div class="flex flex-wrap items-center gap-3 w-full">
              <!-- Filtro de préstamo -->
              <div class="relative flex-grow md:flex-grow-0 md:w-72">
                 <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                 </div>
                 <select
                    v-model="filtroPrestamo"
                    @change="handleFilter"
                    class="block w-full pl-10 pr-10 py-2.5 text-sm bg-slate-950/50 border border-white/10 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-slate-200 placeholder-slate-500 appearance-none transition-all hover:bg-slate-950/80"
                  >
                    <option value="" class="bg-slate-900">Todos los préstamos</option>
                    <option v-for="prestamo in prestamos" :key="prestamo.id" :value="prestamo.id" class="bg-slate-900 text-slate-200">
                      {{ prestamo.cliente_nombre }} - ${{ formatearMoneda(prestamo.monto_prestado) }}
                    </option>
                 </select>
              </div>

              <!-- Filtro de estado -->
              <div class="relative flex-grow md:flex-grow-0 md:w-48">
                 <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                 </div>
                 <select
                    v-model="filtroEstado"
                    @change="handleFilter"
                    class="block w-full pl-10 pr-10 py-2.5 text-sm bg-slate-950/50 border border-white/10 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-slate-200 appearance-none transition-all hover:bg-slate-950/80"
                  >
                    <option value="" class="bg-slate-900">Todos los estados</option>
                    <option value="pendiente" class="text-amber-400 bg-slate-900">Pendientes</option>
                    <option value="pagado" class="text-emerald-400 bg-slate-900">Pagados</option>
                    <option value="atrasado" class="text-rose-400 bg-slate-900">Atrasados</option>
                    <option value="parcial" class="text-blue-400 bg-slate-900">Parciales</option>
                 </select>
              </div>

              <!-- Limpiar filtros -->
              <button
                @click="handleLimpiarFiltros"
                class="inline-flex items-center px-4 py-2.5 border border-white/10 text-sm font-bold rounded-xl text-slate-400 bg-white/5 hover:bg-rose-500/10 hover:text-rose-400 hover:border-rose-500/30 transition-all duration-300"
              >
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                Limpiar
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Información de paginación Top -->
      <div class="flex justify-between items-center mb-5 px-2">
        <div class="text-sm font-medium text-slate-400">
          Mostrando <span class="text-white font-bold">{{ paginationData.from }}</span> - <span class="text-white font-bold">{{ paginationData.to }}</span> de <span class="text-white font-bold">{{ paginationData.total }}</span> pagos
        </div>
        <div class="flex items-center space-x-3">
          <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Mostrar:</span>
          <select
            :value="paginationData.per_page"
            @change="changePerPage"
            class="bg-slate-900/80 border border-white/10 rounded-lg px-3 py-1.5 text-xs text-white font-bold focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer hover:bg-slate-800 transition-colors"
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
      <div class="bg-slate-900/50 border border-white/5 rounded-3xl overflow-hidden shadow-2xl backdrop-blur-sm">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-white/5">
            <thead>
              <tr class="bg-slate-950/50">
                <th scope="col" class="px-6 py-5 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Fecha Programada</th>
                <th scope="col" class="px-6 py-5 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Cliente</th>
                <th scope="col" class="px-6 py-5 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Monto Programado</th>
                <th scope="col" class="px-6 py-5 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Monto Pagado</th>
                <th scope="col" class="px-6 py-5 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Estado</th>
                <th scope="col" class="px-6 py-5 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Días Atraso</th>
                <th scope="col" class="px-6 py-5 text-right text-xs font-black text-slate-500 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5 bg-transparent">
              <template v-if="props.pagos.data && props.pagos.data.length > 0">
                <tr
                  v-for="pago in props.pagos.data"
                  :key="pago.id"
                  class="group hover:bg-white/[0.03] transition-all duration-300 hover:scale-[1.002] hover:shadow-xl relative z-0 hover:z-10"
                >
                  <!-- Fecha Programada -->
                  <td class="px-6 py-5 whitespace-nowrap">
                    <div class="flex flex-col">
                      <div class="text-sm font-bold text-white group-hover:text-indigo-300 transition-colors">
                        {{ formatearFecha(pago.fecha_programada) }}
                      </div>
                      <div class="text-xs text-slate-500 mt-1 font-mono bg-white/5 px-2 py-0.5 rounded w-fit">
                        Pago #{{ pago.numero_pago }}
                      </div>
                    </div>
                  </td>

                  <!-- Cliente -->
                  <td class="px-6 py-5">
                    <div class="flex flex-col">
                      <div class="text-sm font-bold text-slate-200">
                        {{ pago.prestamo?.cliente?.nombre_razon_social || 'Sin cliente' }}
                      </div>
                      <div class="text-xs text-slate-500 mt-0.5">
                        {{ pago.prestamo?.cliente?.rfc || '' }}
                      </div>
                      <div class="text-[10px] text-indigo-400 mt-1">
                        Prestamo #{{ pago.prestamo_id }}
                      </div>
                    </div>
                  </td>

                  <!-- Monto Programado -->
                  <td class="px-6 py-5 whitespace-nowrap">
                    <div class="text-sm font-black text-white bg-slate-800/50 px-3 py-1 rounded-lg inline-block border border-white/5">
                      ${{ formatearMoneda(pago.monto_programado) }}
                    </div>
                  </td>

                  <!-- Monto Pagado -->
                  <td class="px-6 py-5 whitespace-nowrap">
                    <div v-if="pago.monto_pagado > 0" class="flex items-center">
                       <svg class="w-3 h-3 text-emerald-500 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                       <span class="text-sm font-bold text-emerald-400">
                        ${{ formatearMoneda(pago.monto_pagado) }}
                       </span>
                    </div>
                    <div v-else class="text-xs font-bold text-slate-500 uppercase tracking-wider pl-2 border-l-2 border-slate-700">
                      Sin abono
                    </div>
                  </td>

                  <!-- Estado -->
                  <td class="px-6 py-5 whitespace-nowrap">
                    <span
                      :class="obtenerClasesEstado(pago.estado)"
                      class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider shadow-sm"
                    >
                      <span
                        class="w-1.5 h-1.5 rounded-full mr-2 animate-pulse"
                        :class="obtenerColorPuntoEstado(pago.estado)"
                      ></span>
                      {{ obtenerLabelEstado(pago.estado) }}
                    </span>
                  </td>

                  <!-- Días Atraso -->
                  <td class="px-6 py-5 whitespace-nowrap">
                    <div v-if="pago.dias_atraso > 0" class="flex items-center text-rose-400 bg-rose-500/10 px-3 py-1 rounded-lg border border-rose-500/20 w-fit">
                      <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                      <span class="text-xs font-black">{{ pago.dias_atraso }} días</span>
                    </div>
                    <div v-else class="flex items-center text-emerald-400/80">
                      <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                      <span class="text-xs font-bold">A tiempo</span>
                    </div>
                  </td>

                  <!-- Acciones -->
                  <td class="px-6 py-5 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex items-center justify-end space-x-2 opacity-80 group-hover:opacity-100 transition-opacity">
                      <!-- Ver detalles -->
                      <button
                        @click="verDetalles(pago)"
                        class="p-2 rounded-xl bg-slate-800 text-slate-400 hover:bg-indigo-500 hover:text-white hover:shadow-[0_0_15px_rgba(99,102,241,0.4)] transition-all duration-300"
                        title="Ver detalles"
                      >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                      </button>

                      <!-- Nota de pago / comprobante (último abono del historial) -->
                      <a
                        v-if="idHistorialParaComprobante(pago)"
                        :href="route('pagos.comprobante', { historial: idHistorialParaComprobante(pago) })"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="p-2 rounded-xl bg-slate-800 text-slate-400 hover:bg-cyan-500 hover:text-white hover:shadow-[0_0_15px_rgba(6,182,212,0.4)] transition-all duration-300 inline-flex"
                        title="Nota de pago (comprobante)"
                        @click.stop
                      >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                      </a>

                      <!-- Registrar pago -->
                      <button
                        v-if="pago.estado === 'pendiente' || pago.estado === 'parcial' || pago.estado === 'atrasado'"
                        @click="registrarPago(pago)"
                        class="p-2 rounded-xl bg-slate-800 text-slate-400 hover:bg-emerald-500 hover:text-white hover:shadow-[0_0_15px_rgba(16,185,129,0.4)] transition-all duration-300"
                        title="Registrar pago"
                      >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                      </button>

                      <!-- Editar pago -->
                      <button
                        v-if="pago.estado === 'pagado'"
                        @click="editarPago(pago.id)"
                        class="p-2 rounded-xl bg-slate-800 text-slate-400 hover:bg-amber-500 hover:text-white hover:shadow-[0_0_15px_rgba(245,158,11,0.4)] transition-all duration-300"
                        title="Editar pago"
                      >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </template>

              <!-- Empty State -->
              <tr v-else>
                <td :colspan="7" class="px-6 py-24 text-center">
                  <div class="flex flex-col items-center justify-center">
                    <div class="w-24 h-24 bg-slate-900 rounded-full flex items-center justify-center border border-white/5 mb-6 shadow-xl">
                      <svg class="w-10 h-10 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                      </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">No se encontraron pagos</h3>
                    <p class="text-slate-500 max-w-sm mx-auto">No hay registros que coincidan con tus criterios de búsqueda o filtros seleccionados.</p>
                    <button
                      @click="handleLimpiarFiltros"
                      class="mt-6 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold shadow-lg shadow-indigo-500/20 transition-all duration-300"
                    >
                      Limpiar Filtros
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Controles de paginación Bottom -->
      <div v-if="paginationData.last_page > 1" class="flex justify-center items-center space-x-2 mt-8">
        <button
          @click="prevPage"
          :disabled="paginationData.current_page === 1"
          class="px-4 py-2 text-sm font-bold text-slate-400 bg-slate-900/50 border border-white/10 rounded-xl hover:bg-slate-800 disabled:opacity-30 disabled:cursor-not-allowed transition-all"
        >
          Anterior
        </button>

        <div class="flex space-x-1">
          <button
            v-for="page in [paginationData.current_page - 1, paginationData.current_page, paginationData.current_page + 1].filter(p => p > 0 && p <= paginationData.last_page)"
            :key="page"
            @click="goToPage(page)"
            :class="[
              'w-10 h-10 flex items-center justify-center text-sm font-bold rounded-xl transition-all',
              page === paginationData.current_page
                ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20 scale-105'
                : 'text-slate-400 bg-slate-900/50 border border-white/5 hover:bg-slate-800 hover:text-white'
            ]"
          >
            {{ page }}
          </button>
        </div>

        <button
          @click="nextPage"
          :disabled="paginationData.current_page === paginationData.last_page"
          class="px-4 py-2 text-sm font-bold text-slate-400 bg-slate-900/50 border border-white/10 rounded-xl hover:bg-slate-800 disabled:opacity-30 disabled:cursor-not-allowed transition-all"
        >
          Siguiente
        </button>
      </div>
    </div>

    <!-- Modal de detalles / confirmación (Estilo Dark) -->
    <Transition name="modal">
      <div
        v-if="showModal"
        class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-50 p-4"
        @click.self="onClose"
      >
        <div
          class="bg-slate-900 border border-white/10 rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto outline-none"
          role="dialog"
          aria-modal="true"
          ref="modalRef"
          @keydown.esc.prevent="onClose"
        >
          <!-- Modal Header -->
           <div class="px-8 py-6 border-b border-white/5 flex justify-between items-center bg-slate-950/30">
              <h3 class="text-xl font-black text-white tracking-tight">
                 {{ modalMode === 'confirm' ? 'Confirmar Acción' : 'Detalles del Pago' }}
              </h3>
              <button @click="onClose" class="text-slate-500 hover:text-white transition-colors">
                 <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
           </div>

           <div class="p-8">
            <!-- Modo: Confirmación de eliminación -->
            <div v-if="modalMode === 'confirm'" class="text-center py-6">
              <div class="w-20 h-20 mx-auto bg-rose-500/10 border border-rose-500/20 rounded-full flex items-center justify-center mb-6 shadow-[0_0_20px_rgba(244,63,94,0.2)]">
                <svg class="w-10 h-10 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </div>
              <h3 class="text-2xl font-bold text-white mb-3">
                ¿Eliminar pago?
              </h3>
              <p class="text-slate-400 mb-8 max-w-md mx-auto">
                Esta acción eliminará el registro de pago y recalculará el saldo del préstamo. Esta acción no se puede deshacer.
              </p>
              <div class="flex gap-4 justify-center">
                <button
                  @click="onCancel"
                  class="px-6 py-3 bg-slate-800 text-slate-300 rounded-xl font-bold hover:bg-slate-700 hover:text-white transition-all"
                >
                  Cancelar
                </button>
                <button
                  @click="onConfirm"
                  class="px-6 py-3 bg-rose-600 text-white rounded-xl font-bold hover:bg-rose-500 shadow-lg shadow-rose-600/20 transition-all"
                >
                  Sí, Eliminar
                </button>
              </div>
            </div>

            <!-- Modo: Detalles -->
            <div v-else-if="modalMode === 'details'" class="space-y-8">
              <div v-if="selectedPago" class="space-y-6">
                <!-- Info Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <!-- Columna 1 -->
                  <div class="space-y-4">
                    <div>
                       <label class="block text-[10px] uppercase font-bold text-slate-500 tracking-widest mb-1">Cliente</label>
                       <p class="text-base font-bold text-white">{{ selectedPago.prestamo?.cliente?.nombre_razon_social || 'Sin cliente' }}</p>
                    </div>
                    <div>
                       <label class="block text-[10px] uppercase font-bold text-slate-500 tracking-widest mb-1">Folio Préstamo</label>
                       <p class="text-sm font-mono text-indigo-400">#{{ selectedPago.prestamo_id }}</p>
                    </div>
                     <div>
                       <label class="block text-[10px] uppercase font-bold text-slate-500 tracking-widest mb-1">Estado</label>
                       <span
                        :class="obtenerClasesEstado(selectedPago.estado)"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold"
                      >
                        {{ obtenerLabelEstado(selectedPago.estado) }}
                      </span>
                    </div>
                  </div>

                  <!-- Columna 2 -->
                  <div class="space-y-4">
                     <div>
                       <label class="block text-[10px] uppercase font-bold text-slate-500 tracking-widest mb-1">Fecha Programada</label>
                       <p class="text-sm font-medium text-slate-200">{{ formatearFecha(selectedPago.fecha_programada) }}</p>
                    </div>
                    <div v-if="selectedPago.fecha_pago">
                       <label class="block text-[10px] uppercase font-bold text-slate-500 tracking-widest mb-1">Fecha de Pago Real</label>
                       <p class="text-sm font-medium text-emerald-400">{{ formatearFecha(selectedPago.fecha_pago) }}</p>
                    </div>
                    <div>
                       <label class="block text-[10px] uppercase font-bold text-slate-500 tracking-widest mb-1">Número de Pago</label>
                       <div class="bg-slate-800/50 px-3 py-1 rounded inline-block text-xs font-mono text-white">
                          {{ selectedPago.numero_pago }}
                       </div>
                    </div>
                  </div>
                </div>

                <div class="h-px bg-white/5 my-6"></div>

                <!-- Financial Card -->
                <div class="bg-indigo-500/5 border border-indigo-500/10 rounded-2xl p-6 relative overflow-hidden">
                   <div class="grid grid-cols-2 gap-8 relative z-10">
                      <div>
                        <p class="text-xs font-bold text-indigo-400 uppercase tracking-wider mb-1">Monto Programado</p>
                        <p class="text-2xl font-black text-white">${{ formatearMoneda(selectedPago.monto_programado) }}</p>
                      </div>
                      <div>
                         <p class="text-xs font-bold text-emerald-400 uppercase tracking-wider mb-1">Monto Pagado</p>
                         <p class="text-2xl font-black text-emerald-400">${{ formatearMoneda(selectedPago.monto_pagado) }}</p>
                      </div>
                   </div>
                   <!-- Decor -->
                   <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-indigo-500/10 rounded-full blur-xl"></div>
                </div>

                <div v-if="selectedPago.dias_atraso > 0" class="bg-rose-500/5 border border-rose-500/10 rounded-xl p-4 flex items-center">
                   <svg class="w-5 h-5 text-rose-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                   <div>
                      <p class="text-xs font-bold text-rose-400 uppercase">Atraso registrado</p>
                      <p class="text-rose-300 text-sm font-medium">Este pago tiene {{ selectedPago.dias_atraso }} días de retraso.</p>
                   </div>
                </div>
              </div>

              <!-- Botones de acción Modal -->
              <div class="flex flex-wrap justify-end gap-3 mt-8 pt-6 border-t border-white/5">
                <a
                  v-if="selectedPago && idHistorialParaComprobante(selectedPago)"
                  :href="route('pagos.comprobante', { historial: idHistorialParaComprobante(selectedPago) })"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="px-5 py-2.5 bg-cyan-600/20 text-cyan-300 border border-cyan-500/30 rounded-xl hover:bg-cyan-500 hover:text-white transition-all font-bold text-sm inline-flex items-center"
                >
                  <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  Nota de pago
                </a>
                <button
                  @click="onClose"
                  class="px-5 py-2.5 bg-slate-800 text-slate-300 rounded-xl hover:bg-slate-700 hover:text-white transition-colors font-bold text-sm"
                >
                  Cerrar
                </button>

                <button
                  v-if="selectedPago?.estado !== 'pagado'"
                  @click="registrarPago(selectedPago)"
                  class="px-5 py-2.5 bg-emerald-600 text-white rounded-xl hover:bg-emerald-500 shadow-lg shadow-emerald-600/20 transition-all font-bold text-sm flex items-center"
                >
                  <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                  Registrar Pago
                </button>
              </div>
            </div>
           </div>
        </div>
      </div>
    </Transition>

    <!-- Loading overlay -->
    <div v-if="loading" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center z-[100]">
      <div class="bg-slate-900 border border-white/10 p-8 rounded-2xl shadow-2xl flex flex-col items-center">
        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-500 mb-4"></div>
        <span class="text-slate-200 font-bold animate-pulse">Procesando solicitud...</span>
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
