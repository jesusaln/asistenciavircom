<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import ProductosHeader from '@/Components/IndexComponents/ProductosHeader.vue'
import ProductosTable from '@/Components/IndexComponents/ProductosTable.vue'
import SatClaveProdServSearch from '@/Components/Sat/SatClaveProdServSearch.vue'
import Pagination from '@/Components/Pagination.vue'

defineOptions({ layout: AppLayout })

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

const props = defineProps({
  productos: { type: [Object, Array], required: true },
  stats: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  sorting: { type: Object, default: () => ({ sort_by: 'nombre', sort_direction: 'asc' }) },
  defaults: { type: Object, default: () => ({}) }
})

const showModal = ref(false)
const modalMode = ref('details')
const selectedProducto = ref(null)
const selectedId = ref(null)
const showStockModal = ref(false)
const stockDetalle = ref(null)
const loadingStock = ref(false)
const showSatModal = ref(false)
const satTarget = ref(null)
const satForm = ref({ sat_clave_prod_serv: '' })
const satClaveDescription = ref('')
const satSuggestion = ref(null)
const satSaving = ref(false)
const satSuggesting = ref(false)

const showSeriesModal = ref(false)
const seriesDetalle = ref(null)
const seriesCountMap = ref({})
const seriesLoadingMap = ref({})
const seriesSearch = ref({ enStock: '', vendidas: '' })

const searchTerm = ref(props.filters?.search ?? '')
const sortBy = ref(`${props.sorting?.sort_by}-${props.sorting?.sort_direction}`)
const filtroEstado = ref(props.filters?.estado ?? '')
const perPage = ref(props.filters?.per_page ?? 10)

const valorTotalCosto = computed(() => {
  if (productosData.value && productosData.value.length > 0) {
    const totalValor = productosData.value.reduce((sum, p) =>
      sum + ((parseFloat(p.precio_compra) || 0) * (parseFloat(p.stock) || 0)), 0
    )
    return totalValor
  }
  return 0
})

const valorTotalVenta = computed(() => {
  if (productosData.value && productosData.value.length > 0) {
    const totalValor = productosData.value.reduce((sum, p) =>
      sum + ((parseFloat(p.precio_venta) || 0) * (parseFloat(p.stock) || 0)), 0
    )
    return totalValor
  }
  return 0
})

const productosPaginator = computed(() => props.productos)
const productosData = computed(() => productosPaginator.value?.data || [])

const estadisticas = computed(() => ({
  total: props.stats?.total ?? 0,
  activos: props.stats?.activos ?? 0,
  inactivos: props.stats?.inactivos ?? 0,
  agotado: props.stats?.agotado ?? 0,
}))

const productosDocumentos = computed(() => {
  return productosData.value.map(p => ({
    id: p.id,
    titulo: p.nombre || 'Sin nombre',
    subtitulo: p.descripcion ? p.descripcion.substring(0, 50) + (p.descripcion.length > 50 ? '...' : '') : 'Sin descripción',
    estado: p.estado || 'activo',
    fecha: p.created_at,
    raw: p
  }))
})

// Handlers
const handleFiltros = () => {
  router.get(route('productos.index'), {
    search: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'asc',
    estado: filtroEstado.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const handleSearchChange = (val) => {
    searchTerm.value = val
    handleFiltros()
}
const handleEstadoChange = (val) => {
    filtroEstado.value = val
    handleFiltros()
}
const handleSortChange = (val) => {
    sortBy.value = val
    handleFiltros()
}
const handlePageChange = (page) => {
  router.get(route('productos.index'), {
    search: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'asc',
    estado: filtroEstado.value,
    per_page: perPage.value,
    page: page
  }, { preserveState: true, preserveScroll: true })
}

const handlePerPageChange = (newPerPage) => {
    perPage.value = newPerPage
    handleFiltros()
}

const limpiarFiltros = () => {
  searchTerm.value = ''
  sortBy.value = 'nombre-asc'
  filtroEstado.value = ''
  router.get(route('productos.index'), {}, { preserveScroll: true })
  notyf.success('Filtros liquidados')
}

const verDetalles = (doc) => {
  selectedProducto.value = doc.raw
  modalMode.value = 'details'
  showModal.value = true
}

const editarProducto = (id) => router.visit(route('productos.edit', id))

const confirmarEliminacion = (id) => {
  selectedId.value = id
  modalMode.value = 'confirm'
  showModal.value = true
}

const eliminarProducto = () => {
  router.delete(route('productos.destroy', selectedId.value), {
    onSuccess: () => {
      notyf.success('Expediente eliminado')
      showModal.value = false
    }
  })
}

const toggleDestacado = (id) => {
  router.put(route('productos.toggle-destacado', id), {}, {
    preserveScroll: true,
    onSuccess: () => notyf.success('Visibilidad actualizada')
  })
}

const openSatModal = (producto) => {
  satTarget.value = producto
  satForm.value = { sat_clave_prod_serv: producto?.sat_clave_prod_serv || '' }
  showSatModal.value = true
}

const saveSatClave = async () => {
  if (satSaving.value) return
  satSaving.value = true
  try {
    await router.put(route('productos.sat.update', satTarget.value.id), {
        sat_clave_prod_serv: satForm.value.sat_clave_prod_serv
    }, {
        onSuccess: () => {
            notyf.success('Clave SAT sincronizada')
            showSatModal.value = false
        }
    })
  } finally { satSaving.value = false }
}

const verStockDetalle = async (producto) => {
  loadingStock.value = true
  try {
    const response = await fetch(route('productos.stock-detalle', producto.id))
    if (response.ok) {
      stockDetalle.value = await response.json()
      showStockModal.value = true
    }
  } finally { loadingStock.value = false }
}

const verSeries = async (producto) => {
  try {
    const response = await fetch(route('productos.series', producto.id))
    if (response.ok) {
      seriesDetalle.value = await response.json()
      showSeriesModal.value = true
    }
  } catch (err) { notyf.error('Error al cargar series') }
}

const prefetchSeriesCounts = async () => {
  for (const p of productosData.value) {
    if (seriesCountMap.value[p.id] !== undefined || seriesLoadingMap.value[p.id]) continue
    seriesLoadingMap.value[p.id] = true
    try {
      const res = await fetch(route('productos.series', p.id))
      if (res.ok) {
        const data = await res.json()
        seriesCountMap.value[p.id] = { 
            en_stock: Number(data?.counts?.en_stock ?? 0), 
            vendido: Number(data?.counts?.vendido ?? 0) 
        }
      }
    } finally { seriesLoadingMap.value[p.id] = false }
  }
}

onMounted(prefetchSeriesCounts)
watch(productosData, prefetchSeriesCounts)

const formatNumber = (num) => new Intl.NumberFormat('es-MX').format(num)
const formatearFecha = (date) => new Date(date).toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' })
const hasSeries = (p) => (seriesCountMap.value[p.id]?.en_stock > 0 || seriesCountMap.value[p.id]?.vendido > 0) || p.requiere_serie
const faltanSeries = (p) => {
    if (!p.requiere_serie) return 0
    return Math.max(0, Number(p.stock || 0) - (seriesCountMap.value[p.id]?.en_stock || 0))
}
</script>

<template>
  <div class="min-h-screen bg-white dark:bg-slate-950 transition-colors duration-500 overflow-x-hidden relative pb-20">
    <Head title="Ecosistema de Productos" />

    <!-- Ambient Background Effects -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden select-none z-0">
        <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-blue-600/10 rounded-full blur-[120px] animate-pulse-slow"></div>
        <div class="absolute top-[20%] -right-[10%] w-[35%] h-[35%] bg-emerald-600/10 rounded-full blur-[100px] animate-pulse-slow px-2" style="animation-delay: 2s;"></div>
    </div>

    <div class="relative z-10 w-full px-6 lg:px-12 py-10 space-y-10">
      <!-- Header Compartido -->
      <ProductosHeader
        :total="estadisticas.total"
        :activos="estadisticas.activos"
        :inactivos="estadisticas.inactivos"
        :agotado="estadisticas.agotado"
        :valor-total-costo="valorTotalCosto"
        :valor-total-venta="valorTotalVenta"
        v-model:searchTerm="searchTerm"
        v-model:sortBy="sortBy"
        v-model:filtroEstado="filtroEstado"
        @crear-nueva="() => router.visit(route('productos.create'))"
        @search-change="handleSearchChange"
        @filtro-estado-change="handleEstadoChange"
        @sort-change="handleSortChange"
        @limpiar-filtros="limpiarFiltros"
      />

      <!-- Tabla Principal -->
      <ProductosTable
        :items="productosDocumentos"
        :formatearFecha="formatearFecha"
        :formatNumber="formatNumber"
        :hasSeries="hasSeries"
        :faltanSeries="faltanSeries"
        @ver="verDetalles"
        @editar="editarProducto"
        @confirmar-eliminacion="confirmarEliminacion"
        @toggle-destacado="toggleDestacado"
        @open-sat="openSatModal"
        @ver-stock="verStockDetalle"
        @ver-series="verSeries"
      />

      <!-- Paginación Premium -->
      <div v-if="productosPaginator.last_page > 1" class="flex justify-center mt-12 mb-10">
          <Pagination 
            :pagination-data="productosPaginator" 
            variant="premium" 
            @page-change="handlePageChange"
            @per-page-change="handlePerPageChange"
          />
      </div>
    </div>

    <!-- Modal Detalle / Confirmación (Dark Premium) -->
    <Teleport to="body">
        <transition
          enter-active-class="transition duration-300 ease-out"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition duration-200 ease-in"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-slate-950/60 backdrop-blur-sm" @click.self="showModal = false">
            <div class="w-full max-w-2xl bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-200/50 dark:border-slate-800/50 overflow-hidden">
                <div v-if="modalMode === 'details' && selectedProducto">
                    <div class="p-8 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-blue-600/10 flex items-center justify-center text-blue-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <h3 class="text-xs font-black uppercase tracking-[0.2em] text-slate-900 dark:text-white">Especificación Detallada</h3>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Expediente #{{ selectedProducto.id }}</p>
                            </div>
                        </div>
                        <button @click="showModal = false" class="text-slate-400 hover:text-rose-500 transition-colors"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <div class="p-10 space-y-8">
                        <div class="grid grid-cols-2 gap-8">
                            <div><span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1">Nombre Comercial</span><p class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">{{ selectedProducto.nombre }}</p></div>
                            <div><span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1">Código Operativo</span><p class="text-sm font-black text-blue-600 tracking-widest uppercase">{{ selectedProducto.codigo || 'N/A' }}</p></div>
                            <div><span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1">Categorización</span><p class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase">{{ selectedProducto.categoria?.nombre || 'General' }}</p></div>
                            <div><span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1">Stock Actual</span><p class="text-sm font-black text-emerald-500 tracking-widest">{{ selectedProducto.stock }} unidades</p></div>
                        </div>
                        <div class="pt-6 border-t border-slate-100 dark:border-slate-800"><span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-2">Descripción Técnica</span><p class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase leading-relaxed tracking-wide">{{ selectedProducto.descripcion || 'Sin descripción técnica registrada' }}</p></div>
                    </div>
                </div>

                <div v-if="modalMode === 'confirm'">
                    <div class="p-12 text-center space-y-6">
                        <div class="w-20 h-20 bg-rose-500/10 rounded-[2rem] flex items-center justify-center text-rose-500 mx-auto animate-bounce">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </div>
                        <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tighter">¿Eliminar este expediente?</h3>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest max-w-xs mx-auto">Esta acción es irreversible y afectará la trazabilidad histórica de los productos.</p>
                        <div class="flex items-center gap-4 pt-4">
                            <button @click="showModal = false" class="flex-1 px-8 py-5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all">Cancelar Acción</button>
                            <button @click="eliminarProducto" class="flex-1 px-8 py-5 bg-rose-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-rose-600/20 transition-all">Confirmar Baja</button>
                        </div>
                    </div>
                </div>
            </div>
          </div>
        </transition>
    </Teleport>

    <!-- SAT Modal -->
    <Teleport to="body">
        <transition
          enter-active-class="transition duration-300 ease-out"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition duration-200 ease-in"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div v-if="showSatModal" class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-slate-950/60 backdrop-blur-sm" @click.self="showSatModal = false">
            <div class="w-full max-w-xl bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-200/50 dark:border-slate-800/50 overflow-hidden">
                <div class="p-8 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-amber-500/10 flex items-center justify-center text-amber-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </div>
                        <div>
                            <h3 class="text-xs font-black uppercase tracking-[0.2em] text-slate-900 dark:text-white">Regulación Fiscal SAT</h3>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Sincronización de clave prod/serv</p>
                        </div>
                    </div>
                    <button @click="showSatModal = false" class="text-slate-400 hover:text-rose-500 transition-colors"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <div class="p-10 space-y-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Clave de Catálogo</label>
                        <input v-model="satForm.sat_clave_prod_serv" type="text" class="w-full px-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-sm font-black text-slate-900 dark:text-white tracking-widest focus:ring-2 focus:ring-amber-500/20 transition-all uppercase" placeholder="8-Dígitos de clave..." />
                    </div>
                    <div class="pt-4 flex items-center gap-4">
                        <button @click="showSatModal = false" class="flex-1 px-8 py-4 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all">Ignorar</button>
                        <button @click="saveSatClave" class="flex-1 px-8 py-4 bg-amber-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-amber-600/20 transition-all">Sincronizar Clave</button>
                    </div>
                </div>
            </div>
          </div>
        </transition>
    </Teleport>
  </div>
</template>

<style>
.animate-pulse-slow { animation: pulse-slow 8s ease-in-out infinite; }
@keyframes pulse-slow { 0%, 100% { opacity: 0.1; transform: scale(1); } 50% { opacity: 0.15; transform: scale(1.1); } }
input:focus, select:focus, textarea:focus { outline: none; }
</style>
