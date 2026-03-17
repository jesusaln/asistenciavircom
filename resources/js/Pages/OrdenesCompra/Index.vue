<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { router, Head } from '@inertiajs/vue3'
import axios from 'axios'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

import { generarPDF } from '@/Utils/pdfGenerator'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

import OrdenesCompraHeader from '@/Components/IndexComponents/OrdenesCompraHeader.vue'
import OrdenesCompraTable from '@/Components/IndexComponents/OrdenesCompraTable.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  ordenesCompra: { type: Object, default: () => ({ data: [] }) },
  pagination: { type: Object, default: () => ({}) },
  stats: { type: Object, default: () => ({}) },
  almacenes: { type: Array, default: () => [] }
})

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false },
    { type: 'warning', background: '#f59e0b', icon: false }
  ]
})

const showModal = ref(false)
const fila = ref(null)
const modalMode = ref('details')
const selectedId = ref(null)
const loading = ref(false)

const showSeriesModal = ref(false)
const seriesProductos = ref([])
const seriesInputs = ref({})
const seriesOrder = ref(null)
const selectedAlmacen = ref(null)

const searchTerm = ref('')
const sortBy = ref('created_at-desc')
const filtroEstado = ref('')
const ordenesOriginales = ref([...(props.ordenesCompra?.data || [])])

const openSeriesModal = (productos, orden) => {
  seriesProductos.value = Array.isArray(productos) ? productos : []
  seriesOrder.value = orden
  const inputs = {}
  for (const p of seriesProductos.value) {
    const cantidad = Number(p.cantidad) || 0
    inputs[p.id] = Array.from({ length: cantidad }, () => '')
  }
  seriesInputs.value = inputs
  selectedAlmacen.value = props.almacenes?.[0]?.id || null
  showSeriesModal.value = true
}

const closeSeriesModal = () => {
  showSeriesModal.value = false
  seriesProductos.value = []
  seriesInputs.value = {}
  seriesOrder.value = null
  selectedAlmacen.value = null
}

const submitSeriesConversion = async () => {
  if (!seriesOrder.value) { closeSeriesModal(); return; }
  const seriesArray = []
  for (const p of seriesProductos.value) {
    const arr = (seriesInputs.value[p.id] || []).map(s => String(s || '').trim()).filter(Boolean)
    if (arr.length !== (Number(p.cantidad) || 0)) {
      notyf.error(`Debes capturar ${p.cantidad} series para "${p.nombre}"`);
      return;
    }
    seriesArray.push({ producto_id: p.id, series: arr });
  }
  if (!selectedAlmacen.value) { notyf.error('Debes seleccionar un almacén'); return; }

  try {
    loading.value = true
    const { data } = await axios.post(`/ordenescompra/${seriesOrder.value.id}/convertir-directo`, { 
      series: seriesArray,
      almacen_id: selectedAlmacen.value
    })
    if (!data?.success) { notyf.error(data?.error || 'No se pudo convertir'); return; }
    actualizarEstadoLocal(seriesOrder.value.id, 'procesada')
    closeSeriesModal()
    showModal.value = false
    notyf.success('Orden convertida exitosamente')
    setTimeout(() => { router.visit('/compras') }, 1200)
  } catch (err) {
    notyf.error('Error al convertir orden')
  } finally {
    loading.value = false
  }
}

const estadisticas = computed(() => ({
  total: props.stats.total || 0,
  pendientes: props.stats.pendientes || 0,
  enviadas_a_proveedor: props.stats.enviadas_a_proveedor || 0,
  procesadas: props.stats.procesadas || 0,
  canceladas: props.stats.canceladas || 0,
}))

const handleLimpiarFiltros = () => {
  searchTerm.value = ''
  sortBy.value = 'created_at-desc'
  filtroEstado.value = ''
  router.visit('/ordenescompra')
}

const updateSort = (newSort) => {
  sortBy.value = newSort
  const [field, direction] = newSort.split('-')
  router.visit('/ordenescompra', { data: { sort_by: field, sort_direction: direction || 'desc', search: searchTerm.value, estado: filtroEstado.value } })
}

const handleSearch = () => {
    const [field, direction] = sortBy.value.split('-')
    router.visit('/ordenescompra', { data: { search: searchTerm.value, estado: filtroEstado.value, sort_by: field, sort_direction: direction } })
}

const handleFilter = () => {
    const [field, direction] = sortBy.value.split('-')
    router.visit('/ordenescompra', { data: { search: searchTerm.value, estado: filtroEstado.value, sort_by: field, sort_direction: direction } })
}

const goToPage = (page) => {
  const [field, direction] = sortBy.value.split('-')
  router.visit('/ordenescompra', { data: { page, search: searchTerm.value, estado: filtroEstado.value, sort_by: field, sort_direction: direction } })
}

const onSort = (field) => {
    let direction = 'asc'
    if (sortBy.value.startsWith(field) && sortBy.value.endsWith('asc')) {
        direction = 'desc'
    }
    updateSort(`${field}-${direction}`)
}

const verDetalles = (orden) => { fila.value = orden; modalMode.value = 'details'; showModal.value = true; }
const editarOrden = (id) => router.visit(`/ordenescompra/${id}/edit`)
const confirmarEliminacion = (id) => { selectedId.value = id; modalMode.value = 'confirm'; showModal.value = true; }

const onClose = () => {
    showModal.value = false
    fila.value = null
    selectedId.value = null
}

const onConfirm = () => {
    if (modalMode.value === 'confirm') {
        eliminarOrden()
    }
}

const eliminarOrden = async () => {
  loading.value = true
  router.post(`/ordenescompra/${selectedId.value}/cancel`, {}, {
    onSuccess: () => {
        actualizarEstadoLocal(selectedId.value, 'cancelada')
        showModal.value = false
        notyf.success('Orden cancelada')
    },
    onFinish: () => loading.value = false
  })
}

const convertirDirecto = async (orden) => {
  loading.value = true
  try {
    const { data } = await axios.post(`/ordenescompra/${orden.id}/convertir-directo`)
    if (data?.requiere_series) { 
        openSeriesModal(data.productos_con_serie, orden); 
    } else if (data?.success) {
        actualizarEstadoLocal(orden.id, 'procesada')
        notyf.success('Convertida a compra')
        setTimeout(() => router.visit('/compras'), 1200)
    } else {
        notyf.error(data?.error || 'Error')
    }
  } catch { notyf.error('Error') } finally { loading.value = false }
}

const enviarEmailOrden = async (orden) => {
    if (!orden.proveedor?.email) { notyf.error('Proveedor sin email'); return; }
    loading.value = true
    try {
        const { data } = await axios.post(`/ordenescompra/${orden.id}/enviar-email`)
        if (data?.success) {
            notyf.success('Email enviado')
            const idx = ordenesOriginales.value.findIndex(o => o.id === orden.id)
            if (idx !== -1) ordenesOriginales.value[idx].email_enviado = true
        }
    } catch { notyf.error('Error enviando email') } finally { loading.value = false }
}

const recibirOrden = async (orden) => {
    loading.value = true
    try {
        const { data } = await axios.post(`/ordenescompra/${orden.id}/recibir-mercancia`)
        if (data?.success) {
            actualizarEstadoLocal(orden.id, 'procesada')
            notyf.success('Recibida correctamente')
            setTimeout(() => router.visit('/compras'), 1500)
        }
    } catch { notyf.error('Error') } finally { loading.value = false }
}

const actualizarEstadoLocal = (id, estado) => {
  const i = ordenesOriginales.value.findIndex(o => o.id === id)
  if (i !== -1) ordenesOriginales.value[i].estado = estado
}

const visiblePages = computed(() => {
  const pages = [], total = props.pagination.last_page || 1, current = props.pagination.current_page || 1
  if (total <= 7) for (let i = 1; i <= total; i++) pages.push(i)
  else {
    if (current <= 3) for (let i = 1; i <= 5; i++) pages.push(i)
    else if (current >= total - 2) for (let i = total - 4; i <= total; i++) pages.push(i)
    else for (let i = current - 2; i <= current + 2; i++) pages.push(i)
  }
  return pages
})

// Tooltip helpers (reused logic from previous table)
const showTooltip = ref(false), hoveredDoc = ref(null), tooltipPosition = ref({ x: 0, y: 0 })
let tooltipTimeout = null

const handleShowTooltip = (doc, event) => {
  clearTimeout(tooltipTimeout)
  hoveredDoc.value = doc; tooltipPosition.value = { x: event.clientX, y: event.clientY }
  tooltipTimeout = setTimeout(() => { showTooltip.value = true }, 500)
}
const handleHideTooltip = () => {
    clearTimeout(tooltipTimeout)
    tooltipTimeout = setTimeout(() => { showTooltip.value = false; hoveredDoc.value = null }, 300)
}

const tooltipStyle = computed(() => ({
    left: `${tooltipPosition.value.x + 20}px`,
    top: `${tooltipPosition.value.y - 150}px`,
    opacity: showTooltip.value ? 1 : 0,
    transform: showTooltip.value ? 'scale(1)' : 'scale(0.95)',
    pointerEvents: showTooltip.value ? 'auto' : 'none'
}))

// Formatter mapping for the table component
const configEstados = {
  'borrador': { label: 'Borrador', classes: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400', color: 'bg-slate-400' },
  'pendiente': { label: 'Pendiente', classes: 'bg-amber-100/50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400', color: 'bg-amber-500' },
  'aprobada': { label: 'Aprobada', classes: 'bg-blue-100/50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400', color: 'bg-blue-500' },
  'enviado_a_proveedor': { label: 'Enviada', classes: 'bg-indigo-100/50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400', color: 'bg-indigo-500' },
  'convertida': { label: 'Procesada', classes: 'bg-emerald-100/50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400', color: 'bg-emerald-500' },
  'procesada': { label: 'Procesada', classes: 'bg-emerald-100/50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400', color: 'bg-emerald-500' },
  'cancelada': { label: 'Cancelada', classes: 'bg-rose-100/50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400', color: 'bg-rose-500' }
};

const formatters = {
    estadoClasses: (e) => configEstados[e]?.classes || 'bg-slate-100',
    estadoColor: (e) => configEstados[e]?.color || 'bg-slate-400',
    estadoLabel: (e) => configEstados[e]?.label || 'PENDIENTE',
    fecha: (d) => d ? new Date(d).toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' }) : 'N/A',
    hora: (d) => d ? new Date(d).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' }) : '',
    moneda: (n) => new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2 }).format(n || 0)
}
</script>

<template>
  <Head title="Órdenes de Compra" />

  <div class="min-h-screen bg-white dark:bg-slate-950 transition-colors duration-500 overflow-x-hidden relative">
    
    <!-- Ambient Background -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden select-none z-0">
        <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-blue-600/5 rounded-full blur-[120px] animate-pulse-slow"></div>
        <div class="absolute bottom-[10%] -right-[5%] w-[30%] h-[30%] bg-indigo-600/5 rounded-full blur-[100px] animate-pulse-fast"></div>
    </div>

    <div class="relative z-10 w-full px-6 lg:px-12 py-10 space-y-10">
      
      <OrdenesCompraHeader
        :total="estadisticas.total"
        :pendientes="estadisticas.pendientes"
        :enviadas_a_proveedor="estadisticas.enviadas_a_proveedor"
        :procesadas="estadisticas.procesadas"
        :canceladas="estadisticas.canceladas"
        v-model:search-term="searchTerm"
        v-model:sort-by="sortBy"
        v-model:filtro-estado="filtroEstado"
        @crear-nueva="() => router.visit('/ordenescompra/create')"
        @search-change="handleSearch"
        @filtro-estado-change="handleFilter"
        @sort-change="updateSort"
        @limpiar-filtros="handleLimpiarFiltros"
      />

      <div class="space-y-6">
          <div class="flex items-center justify-between px-4">
              <div class="flex items-center gap-4">
                  <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">
                    Mostrando {{ props.pagination.from || 0 }}-{{ props.pagination.to || 0 }} de {{ props.pagination.total || 0 }}
                  </span>
                  <div class="h-1 w-1 rounded-full bg-slate-200 dark:bg-slate-800"></div>
                  <div class="flex items-center gap-2">
                      <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Vista:</span>
                      <select :value="props.pagination.per_page" @change="(e) => router.visit('/ordenescompra', { data: { per_page: e.target.value } })" 
                              class="bg-transparent border-none text-[10px] font-black uppercase tracking-widest text-blue-600 focus:ring-0 cursor-pointer">
                          <option value="10">10 LIST</option>
                          <option value="25">25 LIST</option>
                          <option value="50">50 LIST</option>
                      </select>
                  </div>
              </div>
          </div>

          <OrdenesCompraTable
            :items="ordenesOriginales"
            :sort-by="sortBy"
            :obtener-clases-estado="formatters.estadoClasses"
            :obtener-color-punto-estado="formatters.estadoColor"
            :obtener-label-estado="formatters.estadoLabel"
            :formatear-fecha="formatters.fecha"
            :formatear-hora="formatters.hora"
            :formatear-moneda="formatters.moneda"
            @sort="onSort"
            @ver="verDetalles"
            @editar="editarOrden"
            @enviar-email="enviarEmailOrden"
            @convertir="convertirDirecto"
            @cancelar="confirmarEliminacion"
            @show-tooltip="handleShowTooltip"
            @hide-tooltip="handleHideTooltip"
            @update-tooltip="(e) => tooltipPosition = { x: e.clientX, y: e.clientY }"
          />

          <!-- Pagination -->
          <div v-if="props.pagination.last_page > 1" class="flex items-center justify-center gap-2 pt-6">
              <button @click="goToPage(props.pagination.current_page - 1)" :disabled="props.pagination.current_page === 1"
                      class="w-12 h-12 flex items-center justify-center rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-400 hover:text-blue-500 disabled:opacity-30 transition-all">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
              </button>
              <div class="flex items-center gap-1">
                  <button v-for="page in visiblePages" :key="page" @click="goToPage(page)"
                          :class="page === props.pagination.current_page ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/20' : 'bg-white dark:bg-slate-900 text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'"
                          class="w-12 h-12 flex items-center justify-center rounded-2xl text-[10px] font-black transition-all">
                      {{ page }}
                  </button>
              </div>
              <button @click="goToPage(props.pagination.current_page + 1)" :disabled="props.pagination.current_page === props.pagination.last_page"
                      class="w-12 h-12 flex items-center justify-center rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-400 hover:text-blue-500 disabled:opacity-30 transition-all">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
              </button>
          </div>
      </div>
    </div>

    <!-- Product Tooltip -->
    <Teleport to="body">
        <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 scale-95" leave-active-class="transition duration-150 ease-in" leave-to-class="opacity-0 scale-95">
            <div v-if="showTooltip && hoveredDoc" :style="tooltipStyle" class="fixed z-[999] w-80 bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl rounded-[2rem] shadow-2xl border border-slate-200/50 dark:border-slate-800/50 p-6 overflow-hidden pointer-events-none">
                <div class="space-y-4">
                    <div class="flex items-center gap-3 pb-4 border-b border-slate-200/50 dark:border-slate-800/50">
                        <div class="w-10 h-10 rounded-xl bg-blue-600/10 flex items-center justify-center text-blue-600">
                             <font-awesome-icon icon="box-open" class="text-sm" />
                        </div>
                        <div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Desglose de Orden</span>
                            <div class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight">{{ hoveredDoc.numero_orden || 'S/N' }}</div>
                        </div>
                    </div>
                    <div class="max-h-60 overflow-y-auto custom-scrollbar space-y-3">
                        <div v-for="p in (hoveredDoc.productos || hoveredDoc.items || [])" :key="p.id" class="flex justify-between items-start gap-3">
                            <div class="flex-1">
                                <p class="text-[11px] font-bold text-slate-900 dark:text-white uppercase leading-tight">{{ p.nombre || p.descripcion }}</p>
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-0.5">Cant: {{ p.cantidad }}  ·  ${{ formatters.moneda(p.precio) }}</p>
                            </div>
                            <span class="text-[10px] font-black text-slate-900 dark:text-white">${{ formatters.moneda(p.total ?? (p.cantidad * p.precio)) }}</span>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-slate-200/50 dark:border-slate-800/50 flex justify-between">
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Total Orden</span>
                        <span class="text-xs font-black text-blue-600 tracking-widest">${{ formatters.moneda(hoveredDoc.total) }}</span>
                    </div>
                </div>
            </div>
        </transition>
    </Teleport>

    <!-- Modals (Simplified versions for brevity, they should follow the same pattern) -->
    <!-- Details Modal -->
    <Teleport to="body">
        <div v-if="showModal && modalMode === 'details'" class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-slate-950/60 backdrop-blur-sm" @click.self="onClose">
            <div class="w-full max-w-4xl bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-200/50 dark:border-slate-800/50 overflow-hidden flex flex-col max-h-[90vh]">
                <div class="p-8 border-b border-slate-100 dark:divide-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-blue-600/10 flex items-center justify-center text-blue-600">
                             <font-awesome-icon icon="info-circle" class="text-xl" />
                        </div>
                        <div>
                            <h2 class="text-xs font-black uppercase tracking-[0.2em] text-slate-900 dark:text-white">Expediente de Orden</h2>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">ID Transacción: {{ fila?.id }}</p>
                        </div>
                    </div>
                    <button @click="onClose" class="w-10 h-10 flex items-center justify-center bg-slate-100 dark:bg-slate-800 rounded-xl text-slate-400 hover:text-rose-500 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <div class="flex-1 overflow-y-auto p-10 custom-scrollbar space-y-8">
                    <!-- Basic info grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div class="space-y-1">
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Proveedor</span>
                            <p class="text-sm font-black text-slate-900 dark:text-white uppercase">{{ fila?.proveedor?.nombre_razon_social }}</p>
                        </div>
                         <div class="space-y-1">
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Fecha Emisión</span>
                            <p class="text-sm font-black text-slate-900 dark:text-white uppercase">{{ formatters.fecha(fila?.created_at) }}</p>
                        </div>
                         <div class="space-y-1">
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Estado Actual</span>
                            <div class="flex items-center gap-2 mt-1">
                                <div class="w-2 h-2 rounded-full" :class="formatters.estadoColor(fila?.estado)"></div>
                                <p class="text-sm font-black text-slate-900 dark:text-white uppercase">{{ formatters.estadoLabel(fila?.estado) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="rounded-3xl border border-slate-200 dark:border-slate-800 overflow-hidden bg-slate-50/50 dark:bg-slate-950/20">
                         <table class="w-full text-left">
                             <thead class="bg-slate-100 dark:bg-slate-800/50">
                                 <tr>
                                     <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Descripción</th>
                                     <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Cant</th>
                                     <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Unitario</th>
                                     <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Subtotal</th>
                                 </tr>
                             </thead>
                             <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                 <tr v-for="p in (fila?.productos || fila?.items || [])" :key="p.id">
                                     <td class="px-6 py-4 text-xs font-bold text-slate-900 dark:text-white uppercase">{{ p.nombre || p.descripcion }}</td>
                                     <td class="px-6 py-4 text-xs font-black text-slate-600 dark:text-slate-400 text-center">{{ p.cantidad }}</td>
                                     <td class="px-6 py-4 text-xs font-black text-slate-600 dark:text-slate-400 text-right">${{ formatters.moneda(p.precio) }}</td>
                                     <td class="px-6 py-4 text-xs font-black text-slate-900 dark:text-white text-right">${{ formatters.moneda(p.total ?? (p.cantidad * p.precio)) }}</td>
                                 </tr>
                             </tbody>
                         </table>
                         <div class="p-6 bg-slate-100 dark:bg-slate-800/50 flex justify-end gap-10">
                              <div class="text-right">
                                  <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block">Subtotal Neto</span>
                                  <span class="text-sm font-black text-slate-600 dark:text-slate-400 tracking-widest">${{ formatters.moneda(fila?.subtotal) }}</span>
                              </div>
                               <div class="text-right">
                                  <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block">Impuestos</span>
                                  <span class="text-sm font-black text-slate-600 dark:text-slate-400 tracking-widest">${{ formatters.moneda(fila?.iva) }}</span>
                              </div>
                               <div class="text-right">
                                  <span class="text-[9px] font-black text-blue-600 uppercase tracking-widest block">Total General</span>
                                  <span class="text-lg font-black text-blue-600 tracking-widest">${{ formatters.moneda(fila?.total) }}</span>
                              </div>
                         </div>
                    </div>
                </div>

                <div class="p-8 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-4 bg-slate-50/50 dark:bg-slate-950/20">
                     <button @click="onClose" class="px-8 py-4 bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-2xl text-[10px] font-black uppercase tracking-widest">Cerrar</button>
                     <button v-if="fila?.estado === 'enviado_a_proveedor'" @click="recibirOrden(fila)" class="px-8 py-4 bg-emerald-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-emerald-500/20">Recibir Mercancía</button>
                     <button @click="generarPDF('Orden de Compra', fila)" class="px-8 py-4 bg-blue-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-blue-500/20">Exportar PDF</button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Confirm Cancel Modal -->
    <Teleport to="body">
        <div v-if="showModal && modalMode === 'confirm'" class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-slate-950/60 backdrop-blur-sm" @click.self="onClose">
            <div class="w-full max-w-md bg-white dark:bg-slate-900 rounded-[2.5rem] p-10 border border-slate-200 dark:border-slate-800 text-center space-y-6">
                <div class="w-20 h-20 bg-rose-500/10 rounded-[2rem] flex items-center justify-center mx-auto text-rose-500 mb-2">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                </div>
                <div>
                    <h2 class="text-xs font-black uppercase tracking-[0.2em] text-slate-900 dark:text-white">Confirmar Cancelación</h2>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-2 px-4 leading-relaxed">¿Estás seguro de cancelar la orden #{{ selectedId }}? Esta acción es irreversible.</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <button @click="onClose" class="w-full py-4 px-6 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-2xl text-[10px] font-black uppercase tracking-widest">Descartar</button>
                    <button @click="onConfirm" class="w-full py-4 px-6 bg-rose-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-rose-500/20">Confirmar</button>
                </div>
            </div>
        </div>
    </Teleport>

  </div>
</template>

<style>
.animate-pulse-slow { animation: pulse-slow 8s ease-in-out infinite; }
.animate-pulse-fast { animation: pulse-fast 6s ease-in-out infinite; }

@keyframes pulse-slow { 0%, 100% { opacity: 0.1; transform: scale(1); } 50% { opacity: 0.2; transform: scale(1.1); } }
@keyframes pulse-fast { 0%, 100% { opacity: 0.05; transform: scale(1); } 50% { opacity: 0.15; transform: scale(1.2); } }

.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(59, 130, 246, 0.1); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(59, 130, 246, 0.2); }
</style>
