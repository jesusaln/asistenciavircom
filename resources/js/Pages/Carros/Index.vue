<!-- /resources/js/Pages/Carros/Index.vue -->
<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

const props = defineProps({
  carros: {
    type: Object,
    default: () => ({ data: [] })
  },
  estadisticas: {
    type: Object,
    default: () => ({
      total: 0,
      activos: 0,
      inactivos: 0,
      gasolina: 0,
      diesel: 0,
      electrico: 0,
      hibrido: 0,
      nuevos_mes: 0
    })
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  sorting: {
    type: Object,
    default: () => ({ sort_by: 'created_at', sort_direction: 'desc' })
  },
  pagination: {
    type: Object,
    default: () => ({})
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
   Estado local y modal
========================= */
const showModal = ref(false)
const modalMode = ref('details')
const selectedCarro = ref(null)
const selectedId = ref(null)
const loading = ref(false)

/* =========================
   Filtros, orden y datos
========================= */
const searchTerm = ref('')
const sortBy = ref('fecha-desc')
const filtroEstado = ref('')
const filtroCombustible = ref('')

/* =========================
   Auditoría segura para el modal
========================= */
const auditoriaForModal = computed(() => {
  const r = selectedCarro.value
  if (!r) return null

  const meta = r.metadata || {}
  return {
    creado_por: r.creado_por_nombre || r.created_by_user_name || meta.creado_por || 'N/A',
    actualizado_por: r.actualizado_por_nombre || r.updated_by_user_name || meta.actualizado_por || 'N/A',
    eliminado_por: r.eliminado_por_nombre || r.deleted_by_user_name || meta.eliminado_por || null,
    creado_en: r.created_at || meta.creado_en || null,
    actualizado_en: r.updated_at || meta.actualizado_en || null,
    eliminado_en: r.deleted_at || meta.eliminado_en || null,
  }
})

/* =========================
   Paginación del servidor
========================= */
const paginationData = computed(() => ({
  current_page: props.pagination?.current_page || 1,
  last_page: props.pagination?.last_page || 1,
  per_page: props.pagination?.per_page || 10,
  from: props.pagination?.from || 0,
  to: props.pagination?.to || 0,
  total: props.pagination?.total || 0,
}))

const goToPage = (page) => {
  const query = {
    page,
    search: searchTerm.value,
    estado: filtroEstado.value,
    combustible: filtroCombustible.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc'
  }
  router.visit('/carros', { data: query })
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
  sortBy.value = 'fecha-desc'
  filtroEstado.value = ''
  filtroCombustible.value = ''
  router.visit('/carros')
  notyf.success('Filtros limpiados correctamente')
}

const updateSort = (newSort) => {
  if (newSort && typeof newSort === 'string') {
    sortBy.value = newSort
    const query = {
      sort_by: newSort.split('-')[0],
      sort_direction: newSort.split('-')[1] || 'desc',
      search: searchTerm.value,
      estado: filtroEstado.value,
      combustible: filtroCombustible.value
    }
    router.visit('/carros', { data: query })
  }
}

const changePerPage = (event) => {
  const perPage = event.target.value
  const query = {
    per_page: perPage,
    search: searchTerm.value,
    estado: filtroEstado.value,
    combustible: filtroCombustible.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc'
  }
  router.visit('/carros', { data: query })
}

const handleSearch = () => {
  const query = {
    search: searchTerm.value,
    estado: filtroEstado.value,
    combustible: filtroCombustible.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc'
  }
  router.visit('/carros', { data: query })
}

const handleFilter = () => {
  const query = {
    search: searchTerm.value,
    estado: filtroEstado.value,
    combustible: filtroCombustible.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc'
  }
  router.visit('/carros', { data: query })
}

/* =========================
   Validaciones y utilidades
========================= */
function validarCarro(carro) {
  if (!carro?.id) {
    throw new Error('ID de carro no válido')
  }
  return true
}

/* =========================
   Acciones CRUD
========================= */
const verDetalles = (carro) => {
  try {
    validarCarro(carro)
    selectedCarro.value = carro
    modalMode.value = 'details'
    showModal.value = true
  } catch (error) {
    notyf.error(error.message)
  }
}

const editarCarro = (id) => {
  try {
    const carroId = id || selectedCarro.value?.id
    if (!carroId) throw new Error('ID de carro no válido')

    router.visit(`/carros/${carroId}/edit`)
  } catch (error) {
    notyf.error(error.message)
  }
}

const confirmarEliminacion = async (carro) => {
  try {
    if (!carro?.id) throw new Error('ID de carro no válido')

    selectedId.value = carro.id
    modalMode.value = 'confirm'
    showModal.value = true
  } catch (error) {
    notyf.error(error.message)
  }
}

const eliminarCarro = async () => {
  try {
    if (!selectedId.value) throw new Error('No se seleccionó ningún carro')

    loading.value = true

    router.delete(route('carros.destroy', selectedId.value), {}, {
      onStart: () => {
        notyf.success('Eliminando carro...')
      },
      onSuccess: (response) => {
        notyf.success('Carro eliminado exitosamente')
        showModal.value = false
        selectedId.value = null
      },
      onError: (errors) => {
        console.error('Error al eliminar:', errors)
        notyf.error('Error al eliminar el carro')
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

const crearNuevoCarro = () => {
  router.visit('/carros/create')
}

const exportCarros = () => {
  const params = new URLSearchParams()
  if (searchTerm.value) params.append('search', searchTerm.value)
  if (filtroCombustible.value) params.append('combustible', filtroCombustible.value)
  if (filtroEstado.value) params.append('estado', filtroEstado.value)
  const queryString = params.toString()
  const url = '/carros/export' + (queryString ? `?${queryString}` : '')
  window.location.href = url
}

// Estadísticas con porcentajes para carros
const estadisticasConPorcentaje = computed(() => {
  const total = estadisticas.value.total || 1;

  return {
    activos: { ...{ label: 'Activos', icon: 'check-circle', color: 'green', description: 'Vehículos activos' }, porcentaje: Math.round(((estadisticas.value.activos || 0) / total) * 100) },
    inactivos: { ...{ label: 'Inactivos', icon: 'x-circle', color: 'red', description: 'Vehículos inactivos' }, porcentaje: Math.round(((estadisticas.value.inactivos || 0) / total) * 100) },
    gasolina: { ...{ label: 'Gasolina', icon: 'gas-pump', color: 'blue', description: 'Vehículos a gasolina' }, porcentaje: Math.round(((estadisticas.value.gasolina || 0) / total) * 100) },
    diesel: { ...{ label: 'Diésel', icon: 'truck', color: 'green', description: 'Vehículos diésel' }, porcentaje: Math.round(((estadisticas.value.diesel || 0) / total) * 100) },
  };
})

// Variables para la tabla
const showTooltip = ref(false)
const hoveredCarro = ref(null)
const tooltipPosition = ref({ x: 0, y: 0 })
let tooltipTimeout = null

// Tooltip optimizado
const showCarroTooltip = (carro, event) => {
  if (!carro) return;
  clearTimeout(tooltipTimeout);
  hoveredCarro.value = carro;
  updateTooltipPosition(event);
  tooltipTimeout = setTimeout(() => { showTooltip.value = true; }, 500);
}

const hideCarroTooltip = () => {
  clearTimeout(tooltipTimeout);
  tooltipTimeout = setTimeout(() => {
    showTooltip.value = false;
    hoveredCarro.value = null;
  }, 300);
}

const clearHideTimeout = () => {
  clearTimeout(tooltipTimeout);
}

const updateTooltipPosition = (event) => {
  tooltipPosition.value = { x: event.clientX, y: event.clientY };
}

// Tooltip style computed
const tooltipStyle = computed(() => {
  const OFFSET = 20, TOOLTIP_WIDTH = 320, TOOLTIP_HEIGHT = 384, VIEWPORT_PADDING = 16;
  const { w, h } = getViewport();

  let x = tooltipPosition.value.x + OFFSET;
  let y = tooltipPosition.value.y - (TOOLTIP_HEIGHT / 2);

  if (x + TOOLTIP_WIDTH > w - VIEWPORT_PADDING) x = tooltipPosition.value.x - TOOLTIP_WIDTH - OFFSET;
  if (x < VIEWPORT_PADDING) x = VIEWPORT_PADDING;
  if (y < VIEWPORT_PADDING) y = VIEWPORT_PADDING;
  else if (y + TOOLTIP_HEIGHT > h - VIEWPORT_PADDING) y = h - TOOLTIP_HEIGHT - VIEWPORT_PADDING;

  return {
    left: `${x}px`,
    top: `${y}px`,
    transform: showTooltip.value ? 'scale(1) translateY(0)' : 'scale(0.95) translateY(-10px)',
    opacity: showTooltip.value ? '1' : '0'
  };
})

const getViewport = () => {
  if (typeof window === 'undefined') return { w: 1280, h: 800 };
  return { w: window.innerWidth, h: window.innerHeight };
}

// Cache de formatos para mejor rendimiento
const formatCache = new Map();

const formatearFecha = (date) => {
  if (!date) return 'Fecha no disponible';
  const cacheKey = `fecha-${date}`;
  if (formatCache.has(cacheKey)) return formatCache.get(cacheKey);

  try {
    const time = new Date(date).getTime();
    if (Number.isNaN(time)) return 'Fecha inválida';
    const formatted = new Date(time).toLocaleDateString('es-MX', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    });
    formatCache.set(cacheKey, formatted);
    return formatted;
  } catch {
    return 'Fecha inválida';
  }
}

const formatearHora = (date) => {
  if (!date) return '';
  const cacheKey = `hora-${date}`;
  if (formatCache.has(cacheKey)) return formatCache.get(cacheKey);

  try {
    const time = new Date(date).getTime();
    if (Number.isNaN(time)) return '';
    const formatted = new Date(time).toLocaleTimeString('es-MX', {
      hour: '2-digit',
      minute: '2-digit'
    });
    formatCache.set(cacheKey, formatted);
    return formatted;
  } catch {
    return '';
  }
}

const formatearMoneda = (num) => {
  const value = parseFloat(num);
  const safe = Number.isFinite(value) ? value : 0;
  const cacheKey = `moneda-${safe}`;
  if (formatCache.has(cacheKey)) return formatCache.get(cacheKey);

  const formatted = new Intl.NumberFormat('es-MX', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(safe);
  formatCache.set(cacheKey, formatted);
  return formatted;
}

// Configuración de estados para carros
const configEstados = {
  'activo': {
    label: 'Activo',
    classes: 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300',
    color: 'bg-green-400 dark:bg-green-500'
  },
  'inactivo': {
    label: 'Inactivo',
    classes: 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300',
    color: 'bg-red-400 dark:bg-red-500'
  }
};

const configCombustible = {
  'Gasolina': {
    label: 'Gasolina',
    classes: 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300',
    color: 'bg-blue-400 dark:bg-blue-500'
  },
  'Diésel': {
    label: 'Diésel',
    classes: 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300',
    color: 'bg-green-400 dark:bg-green-500'
  },
  'Eléctrico': {
    label: 'Eléctrico',
    classes: 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300',
    color: 'bg-yellow-400 dark:bg-yellow-500'
  },
  'Híbrido': {
    label: 'Híbrido',
    classes: 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300',
    color: 'bg-purple-400 dark:bg-purple-500'
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

const obtenerClasesCombustible = (combustible) => {
  return configCombustible[combustible]?.classes || 'bg-gray-100 text-gray-700';
}

const obtenerColorCombustible = (combustible) => {
  return configCombustible[combustible]?.color || 'bg-gray-400';
}

const obtenerLabelCombustible = (combustible) => {
  return configCombustible[combustible]?.label || 'Sin combustible';
}

// Items filtrados y ordenados
const items = computed(() => {
  if (!Array.isArray(props.carros.data)) {
    return [];
  }

  let filtered = props.carros.data.slice();

  // Filtro de búsqueda
  if (searchTerm.value) {
    const term = searchTerm.value.toLowerCase().trim();
    filtered = filtered.filter(carro => {
      const marca = carro.marca?.toLowerCase() || '';
      const modelo = carro.modelo?.toLowerCase() || '';
      const placa = carro.placa?.toLowerCase() || '';
      const serie = carro.numero_serie?.toLowerCase() || '';

      return marca.includes(term) ||
             modelo.includes(term) ||
             placa.includes(term) ||
             serie.includes(term);
    });
  }

  // Filtro de estado
  if (filtroEstado.value) {
    filtered = filtered.filter(carro => {
      if (filtroEstado.value === '1') return carro.activo === true || carro.activo === null;
      if (filtroEstado.value === '0') return carro.activo === false;
      return true;
    });
  }

  // Filtro de combustible
  if (filtroCombustible.value) {
    filtered = filtered.filter(carro => {
      return carro.combustible === filtroCombustible.value;
    });
  }

  // Ordenamiento
  if (sortBy.value) {
    const [field, direction] = sortBy.value.split('-');

    filtered.sort((a, b) => {
      let aVal, bVal;

      switch (field) {
        case 'fecha':
          aVal = new Date(a.created_at || a.fecha).getTime() || 0;
          bVal = new Date(b.created_at || b.fecha).getTime() || 0;
          break;
        case 'marca':
          aVal = (a.marca || '').toLowerCase();
          bVal = (b.marca || '').toLowerCase();
          break;
        case 'modelo':
          aVal = (a.modelo || '').toLowerCase();
          bVal = (b.modelo || '').toLowerCase();
          break;
        case 'anio':
          aVal = parseInt(a.anio) || 0;
          bVal = parseInt(b.anio) || 0;
          break;
        case 'precio':
          aVal = parseFloat(a.precio) || 0;
          bVal = parseFloat(b.precio) || 0;
          break;
        case 'kilometraje':
          aVal = parseInt(a.kilometraje) || 0;
          bVal = parseInt(b.kilometraje) || 0;
          break;
        case 'estado':
          aVal = obtenerLabelEstado(a.activo ? 'activo' : 'inactivo').toLowerCase();
          bVal = obtenerLabelEstado(b.activo ? 'activo' : 'inactivo').toLowerCase();
          break;
        case 'combustible':
          aVal = obtenerLabelCombustible(a.combustible || '').toLowerCase();
          bVal = obtenerLabelCombustible(b.combustible || '').toLowerCase();
          break;
        default:
          aVal = (a[field] || '').toString().toLowerCase();
          bVal = (b[field] || '').toString().toLowerCase();
      }

      const comparison = aVal < bVal ? -1 : aVal > bVal ? 1 : 0;
      return direction === 'desc' ? -comparison : comparison;
    });
  }

  return filtered;
});

// Sort function
const onSort = (field) => {
  const current = sortBy.value.startsWith(field) ? sortBy.value : `${field}-desc`;
  const newOrder = current === `${field}-desc` ? `${field}-asc` : `${field}-desc`;
  updateSort(newOrder);
}

// Funciones para Modal
const modalRef = ref(null)

const focusFirst = () => { try { modalRef.value?.focus() } catch {} }
watch(() => showModal, (v) => { if (v) setTimeout(focusFirst, 0) })

const onKey = (e) => { if (e.key === 'Escape' && showModal.value) onClose() }
onMounted(() => window.addEventListener('keydown', onKey))
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))

const onCancel = () => { showModal.value = false; selectedCarro.value = null; selectedId.value = null; }
const onConfirm = () => { eliminarCarro() }
const onClose = () => { showModal.value = false; selectedCarro.value = null; selectedId.value = null; }
const onEditarFila = () => { editarCarro(selectedCarro.value?.id) }

const hayFiltrosActivos = computed(() => !!searchTerm.value || !!filtroEstado.value || !!filtroCombustible.value)

const limpiarFiltros = () => {
  searchTerm.value = ''
  sortBy.value = 'fecha-desc'
  filtroEstado.value = ''
  filtroCombustible.value = ''
  handleLimpiarFiltros()
}

// Helper functions
const isNumber = (n) => Number.isFinite(parseFloat(n))

// Función para formatear números
const formatNumber = (num) => {
  const value = parseFloat(num);
  return Number.isFinite(value) ? new Intl.NumberFormat('es-ES').format(value) : '0';
}
</script>

<template>
  <Head title="Vehículos" />

  <div class="carros-index min-h-screen bg-gray-50 dark:bg-slate-950 dark:bg-gray-900">
    <!-- Contenido principal -->
    <div class="w-full px-6 py-8">
      <!-- Header específico de vehículos -->
      <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 border border-gray-200 dark:border-slate-800 dark:border-gray-700 rounded-xl shadow-sm p-8 mb-6">
        <div class="flex flex-col lg:flex-row gap-8 items-start lg:items-center justify-between">
          <!-- Izquierda -->
          <div class="flex flex-col gap-6 w-full lg:w-auto">
            <div class="flex items-center gap-3">
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-gray-100">Vehículos</h1>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center">
              <Link
                href="/carros/create"
                class="inline-flex items-center gap-2.5 px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all duration-200 shadow-lg"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Nuevo Vehículo</span>
              </Link>

              <button
                @click="exportCarros"
                class="inline-flex items-center gap-2 px-4 py-3 bg-green-50 dark:bg-green-900/40 text-green-700 dark:text-green-300 rounded-xl hover:bg-green-100 dark:hover:bg-green-900/60 transition-all duration-200 border border-green-200 dark:border-green-700"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                </svg>
                <span class="text-sm font-medium">Exportar</span>
              </button>
            </div>

            <!-- Estadísticas mejoradas -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
              <div class="flex items-center gap-2 px-4 py-3 bg-white dark:bg-slate-900 dark:bg-gray-700 rounded-xl border border-gray-200 dark:border-slate-800 dark:border-gray-600">
                <svg class="w-5 h-5 text-gray-500 dark:text-gray-400 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <div class="flex flex-col">
                  <span class="font-medium text-gray-700 dark:text-gray-300 text-sm">Total</span>
                  <span class="font-bold text-gray-900 dark:text-white dark:text-gray-100 text-lg">{{ formatNumber(estadisticas.total) }}</span>
                </div>
              </div>

              <div class="flex items-center gap-2 px-4 py-3 bg-green-50 dark:bg-green-900/40 rounded-xl border border-green-200 dark:border-green-700">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="flex flex-col">
                  <span class="font-medium text-gray-700 dark:text-gray-300 text-sm">Activos</span>
                  <span class="font-bold text-green-700 dark:text-green-300 text-lg">{{ formatNumber(estadisticas.activos) }}</span>
                </div>
              </div>

              <div class="flex items-center gap-2 px-4 py-3 bg-blue-50 dark:bg-blue-900/40 rounded-xl border border-blue-200 dark:border-blue-700">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
                </svg>
                <div class="flex flex-col">
                  <span class="font-medium text-gray-700 dark:text-gray-300 text-sm">Gasolina</span>
                  <span class="font-bold text-blue-700 dark:text-blue-300 text-lg">{{ formatNumber(estadisticas.gasolina) }}</span>
                </div>
              </div>

              <div class="flex items-center gap-2 px-4 py-3 bg-green-50 dark:bg-green-900/40 rounded-xl border border-green-200 dark:border-green-700">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <div class="flex flex-col">
                  <span class="font-medium text-gray-700 dark:text-gray-300 text-sm">Diésel</span>
                  <span class="font-bold text-green-700 dark:text-green-300 text-lg">{{ formatNumber(estadisticas.diesel) }}</span>
                </div>
              </div>

              <div class="flex items-center gap-2 px-4 py-3 bg-yellow-50 dark:bg-yellow-900/40 rounded-xl border border-yellow-200 dark:border-yellow-700">
                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <div class="flex flex-col">
                  <span class="font-medium text-gray-700 dark:text-gray-300 text-sm">Eléctrico</span>
                  <span class="font-bold text-yellow-700 dark:text-yellow-300 text-lg">{{ formatNumber(estadisticas.electrico) }}</span>
                </div>
              </div>

              <div class="flex items-center gap-2 px-4 py-3 bg-purple-50 dark:bg-purple-900/40 rounded-xl border border-purple-200 dark:border-purple-700">
                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <div class="flex flex-col">
                  <span class="font-medium text-gray-700 dark:text-gray-300 text-sm">Híbrido</span>
                  <span class="font-bold text-purple-700 dark:text-purple-300 text-lg">{{ formatNumber(estadisticas.hibrido) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Derecha: Filtros -->
          <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto lg:flex-shrink-0">
            <!-- Búsqueda -->
            <div class="relative">
              <input
                v-model="searchTerm"
                @input="handleSearch"
                type="text"
                placeholder="Buscar por marca, modelo, placa..."
                class="w-full sm:w-64 lg:w-80 pl-4 pr-10 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all duration-200"
              />
              <svg class="absolute right-3 top-3.5 w-4 h-4 text-gray-400 dark:text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>

            <!-- Estado -->
            <select
              v-model="filtroEstado"
              @change="handleFilter"
              class="px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all duration-200"
            >
              <option value="">Todos los Estados</option>
              <option value="1">Activos</option>
              <option value="0">Inactivos</option>
            </select>

            <!-- Combustible -->
            <select
              v-model="filtroCombustible"
              @change="handleFilter"
              class="px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all duration-200"
            >
              <option value="">Todos los Combustibles</option>
              <option value="Gasolina">Gasolina</option>
              <option value="Diésel">Diésel</option>
              <option value="Eléctrico">Eléctrico</option>
              <option value="Híbrido">Híbrido</option>
            </select>

            <!-- Limpiar filtros -->
            <button
              v-if="hayFiltrosActivos"
              @click="limpiarFiltros"
              class="px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-all duration-200 border border-gray-300 dark:border-gray-600"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Información de paginación -->
      <div class="flex justify-between items-center mb-4 text-sm text-gray-600 dark:text-gray-300 dark:text-gray-400">
        <div>
          Mostrando {{ paginationData.from }} - {{ paginationData.to }} de {{ paginationData.total }} vehículos
        </div>
        <div class="flex items-center space-x-2">
          <span>Elementos por página:</span>
          <select
            :value="paginationData.per_page"
            @change="changePerPage"
            class="border border-gray-300 dark:border-gray-600 rounded px-2 py-1 text-sm bg-white dark:bg-slate-900 dark:bg-gray-700 dark:text-gray-200"
          >
            <option value="10">10</option>
            <option value="15">15</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
      </div>

      <!-- Tabla de vehículos -->
      <div class="mt-6">
        <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
          <!-- Header -->
          <div class="bg-gradient-to-r from-gray-50 to-gray-100/50 dark:from-gray-700/50 dark:to-gray-800/50 px-6 py-4 border-b border-gray-200 dark:border-slate-800/60 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-gray-100 tracking-tight">Lista de Vehículos</h2>
              <div class="text-sm text-gray-600 dark:text-gray-300 dark:text-gray-300 bg-white dark:bg-slate-900/70 dark:bg-gray-700/50 px-3 py-1 rounded-full border border-gray-200 dark:border-slate-800/50 dark:border-gray-600">
                {{ items.length }} de {{ paginationData.total }} vehículos
              </div>
            </div>
          </div>

          <!-- Table -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800/60 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-slate-950/60 dark:bg-gray-700/60">
                <tr>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-300 uppercase tracking-wider">
                    <button @click="onSort('fecha')" class="flex items-center gap-1 hover:text-gray-900 dark:text-white dark:hover:text-gray-100">
                      Fecha
                      <svg class="w-3 h-3" :class="sortBy.startsWith('fecha') ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-gray-500 dark:text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                      </svg>
                    </button>
                  </th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-300 uppercase tracking-wider">
                    <button @click="onSort('marca')" class="flex items-center gap-1 hover:text-gray-900 dark:text-white dark:hover:text-gray-100">
                      Vehículo
                      <svg class="w-3 h-3" :class="sortBy.startsWith('marca') ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-gray-500 dark:text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                      </svg>
                    </button>
                  </th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-300 uppercase tracking-wider">Año</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-300 uppercase tracking-wider">Precio</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-300 uppercase tracking-wider">Kilometraje</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-300 uppercase tracking-wider">Estado</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-300 uppercase tracking-wider">Combustible</th>
                  <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                </tr>
              </thead>

              <tbody class="bg-white dark:bg-slate-900 dark:bg-gray-800 divide-y divide-gray-200 dark:divide-slate-800/40 dark:divide-gray-700/40">
                <template v-if="items.length > 0">
                  <tr
                    v-for="carro in items"
                    :key="carro.id"
                    class="group hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-700/50 transition-all duration-150 hover:shadow-sm"
                  >
                    <!-- Fecha -->
                    <td class="px-6 py-4">
                      <div class="flex flex-col space-y-0.5">
                        <div class="text-sm font-medium text-gray-900 dark:text-white dark:text-gray-100">
                          {{ formatearFecha(carro.created_at || carro.fecha) }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">
                          {{ formatearHora(carro.created_at || carro.fecha) }}
                        </div>
                      </div>
                    </td>

                    <!-- Vehículo -->
                    <td class="px-6 py-4">
                      <div class="flex flex-col space-y-0.5">
                        <div class="text-sm font-medium text-gray-900 dark:text-white dark:text-gray-100 group-hover:text-gray-800 dark:text-gray-100 dark:group-hover:text-gray-200">
                          {{ carro.marca || 'Sin marca' }} {{ carro.modelo || 'Sin modelo' }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">
                          Placa: {{ carro.placa || 'N/A' }}
                        </div>
                      </div>
                    </td>

                    <!-- Año -->
                    <td class="px-6 py-4">
                      <div class="text-sm text-gray-700 dark:text-gray-300">{{ carro.anio || 'N/A' }}</div>
                    </td>

                    <!-- Precio -->
                    <td class="px-6 py-4">
                      <div class="text-sm text-gray-700 dark:text-gray-300">${{ formatearMoneda(carro.precio) }}</div>
                    </td>

                    <!-- Kilometraje -->
                    <td class="px-6 py-4">
                      <div class="text-sm text-gray-700 dark:text-gray-300">{{ formatNumber(carro.kilometraje || 0) }} km</div>
                    </td>

                    <!-- Estado -->
                    <td class="px-6 py-4">
                      <span
                        :class="obtenerClasesEstado(carro.activo ? 'activo' : 'inactivo')"
                        class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium transition-all duration-150 hover:shadow-sm"
                      >
                        <span
                          class="w-2 h-2 rounded-full mr-2 transition-all duration-150"
                          :class="obtenerColorPuntoEstado(carro.activo ? 'activo' : 'inactivo')"
                        ></span>
                        {{ obtenerLabelEstado(carro.activo ? 'activo' : 'inactivo') }}
                      </span>
                    </td>

                    <!-- Combustible -->
                    <td class="px-6 py-4">
                      <span
                        :class="obtenerClasesCombustible(carro.combustible)"
                        class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium transition-all duration-150 hover:shadow-sm"
                      >
                        <span
                          class="w-2 h-2 rounded-full mr-2 transition-all duration-150"
                          :class="obtenerColorCombustible(carro.combustible)"
                        ></span>
                        {{ obtenerLabelCombustible(carro.combustible) }}
                      </span>
                    </td>

                    <!-- Acciones -->
                    <td class="px-6 py-4">
                      <div class="flex items-center justify-end space-x-2">
                        <!-- Ver detalles -->
                        <button
                          @click="verDetalles(carro)"
                          class="group/btn relative inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/60 hover:text-blue-700 dark:hover:text-blue-300 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:ring-offset-1"
                          title="Ver detalles"
                        >
                          <svg class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                          </svg>
                        </button>

                        <!-- Editar -->
                        <button
                          @click="editarCarro(carro.id)"
                          class="group/btn relative inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-900/60 hover:text-amber-700 dark:hover:text-amber-300 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:ring-offset-1"
                          title="Editar vehículo"
                        >
                          <svg class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                        </button>

                        <!-- Eliminar -->
                        <button
                          @click="confirmarEliminacion(carro)"
                          class="group/btn relative inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 dark:bg-red-900/40 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/60 hover:text-red-700 dark:hover:text-red-300 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-500/50 focus:ring-offset-1"
                          title="Eliminar vehículo"
                        >
                          <svg class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                          </svg>
                        </button>
                      </div>
                    </td>
                  </tr>
                </template>

                <!-- Empty State -->
                <tr v-else>
                  <td :colspan="8" class="px-6 py-16 text-center">
                    <div class="flex flex-col items-center space-y-4">
                      <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400 dark:text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                      </div>
                      <div class="space-y-1">
                        <p class="text-gray-700 dark:text-gray-300 font-medium">No hay vehículos</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">Los vehículos aparecerán aquí cuando se creen</p>
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
          class="px-3 py-2 text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 bg-white dark:bg-slate-900 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Anterior
        </button>

        <div class="flex space-x-1">
          <button
            v-for="page in [paginationData.current_page - 1, paginationData.current_page, paginationData.current_page + 1].filter(p => p > 0 && p <= paginationData.last_page)"
            :key="page"
            @click="goToPage(page)"
            :class="[
              'px-3 py-2 text-sm font-medium border border-gray-300 dark:border-gray-600 rounded-md',
              page === paginationData.current_page
                ? 'bg-blue-500 text-white border-blue-500'
                : 'bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-600'
            ]"
          >
            {{ page }}
          </button>
        </div>

        <button
          @click="nextPage"
          :disabled="paginationData.current_page === paginationData.last_page"
          class="px-3 py-2 text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 bg-white dark:bg-slate-900 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
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
          class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6 outline-none"
          role="dialog"
          aria-modal="true"
          :aria-label="`Modal de Vehículo`"
          tabindex="-1"
          ref="modalRef"
          @keydown.esc.prevent="onClose"
        >
          <!-- Modo: Confirmación de eliminación -->
          <div v-if="modalMode === 'confirm'" class="text-center">
            <div class="w-12 h-12 mx-auto bg-red-100 rounded-full flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"
                />
              </svg>
            </div>
            <h3 class="text-lg font-medium mb-2 text-gray-900 dark:text-white dark:text-gray-100">
              ¿Eliminar vehículo?
            </h3>
            <p class="text-gray-600 dark:text-gray-300 dark:text-gray-400 mb-6">
              Esta acción no se puede deshacer.
            </p>
            <div class="flex gap-3">
              <button
                @click="onCancel"
                class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-9500 transition-colors"
              >
                Cancelar
              </button>
              <button
                @click="onConfirm"
                class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
              >
                Eliminar
              </button>
            </div>
          </div>

          <!-- Modo: Detalles -->
          <div v-else-if="modalMode === 'details'" class="space-y-4">
            <h3 class="text-lg font-medium mb-1 flex items-center gap-2 text-gray-900 dark:text-white dark:text-gray-100">
              Detalles de Vehículo
              <span v-if="selectedCarro?.id" class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">#{{ selectedCarro.id }}</span>
            </h3>

            <div v-if="selectedCarro" class="space-y-4">
              <!-- Información general -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <p class="text-sm text-gray-600 dark:text-gray-300 dark:text-gray-300">
                    <strong>Marca:</strong> {{ selectedCarro.marca || 'Sin marca' }}
                  </p>
                  <p class="text-sm text-gray-600 dark:text-gray-300 dark:text-gray-300">
                    <strong>Modelo:</strong> {{ selectedCarro.modelo || 'Sin modelo' }}
                  </p>
                  <p class="text-sm text-gray-600 dark:text-gray-300 dark:text-gray-300">
                    <strong>Año:</strong> {{ selectedCarro.anio || 'N/A' }}
                  </p>
                  <p class="text-sm text-gray-600 dark:text-gray-300 dark:text-gray-300">
                    <strong>Color:</strong> {{ selectedCarro.color || 'N/A' }}
                  </p>
                  <p class="text-sm text-gray-600 dark:text-gray-300 dark:text-gray-300">
                    <strong>Estado:</strong>
                    <span
                      :class="obtenerClasesEstado(selectedCarro.activo ? 'activo' : 'inactivo')"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    >
                      {{ obtenerLabelEstado(selectedCarro.activo ? 'activo' : 'inactivo') }}
                    </span>
                  </p>
                </div>

                <div>
                  <p class="text-sm text-gray-600 dark:text-gray-300 dark:text-gray-300">
                    <strong>Precio:</strong> ${{ formatearMoneda(selectedCarro.precio) }}
                  </p>
                  <p class="text-sm text-gray-600 dark:text-gray-300 dark:text-gray-300">
                    <strong>Kilometraje:</strong> {{ formatNumber(selectedCarro.kilometraje || 0) }} km
                  </p>
                  <p class="text-sm text-gray-600 dark:text-gray-300 dark:text-gray-300">
                    <strong>Placa:</strong> {{ selectedCarro.placa || 'N/A' }}
                  </p>
                  <p class="text-sm text-gray-600 dark:text-gray-300 dark:text-gray-300">
                    <strong>Número de Serie:</strong> {{ selectedCarro.numero_serie || 'N/A' }}
                  </p>
                  <p class="text-sm text-gray-600 dark:text-gray-300 dark:text-gray-300">
                    <strong>Combustible:</strong>
                    <span
                      :class="obtenerClasesCombustible(selectedCarro.combustible)"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    >
                      {{ obtenerLabelCombustible(selectedCarro.combustible) }}
                    </span>
                  </p>
                </div>
              </div>
            </div>

            <!-- Botones de acción -->
            <div class="flex flex-wrap justify-end gap-2 mt-6">
              <button
                @click="onEditarFila"
                class="px-3 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors text-sm"
              >
                ✏️ Editar
              </button>

              <button
                @click="onClose"
                class="px-3 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-9500 transition-colors text-sm"
              >
                Cerrar
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Loading overlay -->
    <div v-if="loading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 p-6 rounded-lg shadow-lg">
        <div class="flex items-center space-x-3">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 dark:border-blue-400"></div>
          <span class="text-gray-700 dark:text-gray-200">Procesando...</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.carros-index {
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




