<!-- /resources/js/Pages/OrdenesCompra/Index.vue -->
<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { ref, computed, watch, onMounted } from 'vue'
import { router, Head, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

import { generarPDF } from '@/Utils/pdfGenerator'
import AppLayout from '@/Layouts/AppLayout.vue'

import OrdenesCompraHeader from '@/Components/IndexComponents/OrdenesCompraHeader.vue'
import OrdenesCompraTable from '@/Components/IndexComponents/OrdenesCompraTable.vue'
import ModalOrdenCompra from '@/Components/IndexComponents/ModalOrdenCompra.vue'
import Modal from '@/Components/IndexComponents/Modales.vue'
import Pagination from '@/Components/Pagination.vue'

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

const page = usePage()
onMounted(() => {
  const flash = page.props.flash
  if (flash?.success) notyf.success(flash.success)
  if (flash?.error) notyf.error(flash.error)
})

const showModal = ref(false)
const modalMode = ref('details')
const selectedOrden = ref(null)
const selectedId = ref(null)
const loading = ref(false)

// Series Capture Logic
const showSeriesModal = ref(false)
const seriesProductos = ref([]) 
const seriesInputs = ref({}) 
const seriesOrder = ref(null) 
const selectedAlmacen = ref(null) 

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
  if (!seriesOrder.value) {
    closeSeriesModal()
    return
  }
  const seriesArray = []
  for (const p of seriesProductos.value) {
    const arr = (seriesInputs.value[p.id] || []).map(s => String(s || '').trim()).filter(Boolean)
    if (arr.length !== (Number(p.cantidad) || 0)) {
      notyf.error(`Debes capturar ${p.cantidad} series para "${p.nombre}"`)
      return
    }
    seriesArray.push({ producto_id: p.id, series: arr })
  }
  if (!selectedAlmacen.value) {
    notyf.error('Debes seleccionar un almacén')
    return
  }
  try {
    loading.value = true
    const { data } = await axios.post(`/ordenescompra/${seriesOrder.value.id}/convertir-directo`, { 
      series: seriesArray,
      almacen_id: selectedAlmacen.value
    })
    if (!data?.success) {
      notyf.error(data?.error || data?.message || 'No se pudo convertir la orden')
      return
    }
    closeSeriesModal()
    showModal.value = false
    notyf.success(data.message || 'Orden convertida exitosamente')
    setTimeout(() => { router.visit('/compras') }, 1200)
  } catch (err) {
    notyf.error(err?.response?.data?.error || 'Error al convertir orden')
  } finally {
    loading.value = false
  }
}

// Filters & Sorting
const searchTerm = ref('')
const filtroEstado = ref('')
const sortBy = ref('fecha-desc')

function handleSearchChange(newSearch) {
  searchTerm.value = newSearch
  aplicarFiltros()
}

function handleEstadoChange(newEstado) {
  filtroEstado.value = newEstado
  aplicarFiltros()
}

function aplicarFiltros(params = {}) {
  router.get('/ordenescompra', {
    search: searchTerm.value,
    estado: filtroEstado.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    per_page: params.per_page || props.pagination?.per_page || 10,
    page: params.page || 1
  }, { preserveState: true, preserveScroll: true })
}

const verDetalles = (doc) => {
  selectedOrden.value = doc
  modalMode.value = 'details'
  showModal.value = true
}

const cerrarModal = () => {
  showModal.value = false
  selectedOrden.value = null
  selectedId.value = null
}

const editarOrden = (id) => router.visit(`/ordenescompra/${id}/edit`)

const imprimirOrden = async (orden) => {
  try {
    loading.value = true
    notyf.success('Generando PDF...')
    await generarPDF('Orden de Compra', { ...orden, fecha: orden.fecha_orden || orden.created_at })
    notyf.success('PDF generado correctamente')
  } catch (error) {
    notyf.error('Error al generar el PDF')
  } finally {
    loading.value = false
  }
}

const confirmarEliminacion = (id) => {
  selectedId.value = id
  selectedOrden.value = props.ordenesCompra.data.find(o => o.id === id)
  modalMode.value = 'confirm-delete'
  showModal.value = true
}

const eliminarOrden = async () => {
  if (!selectedId.value) return
  router.post(`/ordenescompra/${selectedId.value}/cancel`, {}, {
    onSuccess: () => {
      notyf.success('Orden cancelada exitosamente')
      cerrarModal()
    }
  })
}

const limpiarFiltros = () => {
  searchTerm.value = ''
  filtroEstado.value = ''
  router.visit('/ordenescompra')
}

const handlePageChange = (newPage) => aplicarFiltros({ page: newPage })
const handlePerPageChange = (newPerPage) => aplicarFiltros({ per_page: newPerPage, page: 1 })

</script>

<template>
  <Head title="Órdenes de Compra" />
  <div class="ordenes-index min-h-screen bg-[var(--ui-surface)] transition-colors duration-500">
    <div class="w-full px-4 lg:px-10 py-10 space-y-10">
      <!-- Header Premium -->
      <OrdenesCompraHeader
        :total="stats.total || 0"
        :pendientes="stats.pendientes || 0"
        :enviadas_a_proveedor="stats.enviadas_a_proveedor || 0"
        :procesadas="stats.procesadas || 0"
        :canceladas="stats.canceladas || 0"
        v-model:search-term="searchTerm"
        v-model:filtro-estado="filtroEstado"
        @crear-nueva="() => router.visit('/ordenescompra/create')"
        @search-change="handleSearchChange"
        @filtro-estado-change="handleEstadoChange"
        @limpiar-filtros="limpiarFiltros"
      />

      <!-- Tabla Premium -->
      <div class="space-y-8">
        <OrdenesCompraTable
          :items="ordenesCompra.data"
          @ver-detalles="verDetalles"
          @editar="editarOrden"
          @imprimir="imprimirOrden"
          @eliminar="confirmarEliminacion"
        />

      <!-- Paginación Premium -->
      <Pagination 
        :pagination-data="pagination" 
        @page-change="handlePageChange"
        @per-page-change="handlePerPageChange"
      />
      </div>
    </div>

    <!-- Visor de Orden (Expediente 360°) -->
    <ModalOrdenCompra
      :show="showModal && modalMode === 'details'"
      :selected="selectedOrden"
      @close="cerrarModal"
      @editar="editarOrden"
      @imprimir="imprimirOrden"
    />

    <!-- Modal de Confirmación para Cancelación -->
    <Modal 
      v-if="modalMode === 'confirm-delete'"
      :show="showModal" 
      :mode="'confirm'"
      :tipo="'ordenes'"
      :selected="selectedOrden"
      @close="cerrarModal"
      @confirm-delete="eliminarOrden"
    >
        <div class="p-8 text-center">
            <div class="w-20 h-20 bg-rose-50 dark:bg-rose-900/20 rounded-[2rem] flex items-center justify-center mx-auto mb-6 text-rose-600 border border-rose-100 dark:border-rose-800">
               <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            </div>

            <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-wider mb-2">¿Cancelar Orden de Compra?</h3>
            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide leading-loose mb-8">
                Esta acción cancelará la orden <strong>#{{ selectedOrden?.numero_orden || selectedOrden?.id }}</strong>. Esta operación no se puede revertir.
            </p>

            <div class="flex gap-4">
               <button @click="cerrarModal" class="flex-1 py-4 bg-slate-100 dark:bg-slate-800 text-[10px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wide rounded-2xl transition-all">Conservar</button>
               <button @click="eliminarOrden" class="flex-1 py-4 bg-rose-600 text-white text-[10px] font-black uppercase tracking-wide rounded-2xl shadow-xl shadow-rose-600/20 transition-all hover:bg-rose-700">Confirmar Cancelación</button>
            </div>
        </div>
    </Modal>

    <!-- Modal de Series -->
    <Teleport to="body">
      <div v-if="showSeriesModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="closeSeriesModal" />
        <div class="relative bg-white dark:bg-slate-950 w-full max-w-2xl rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden">
          <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-wider">Captura de Series</h3>
            <button @click="closeSeriesModal" class="text-slate-400 hover:text-rose-500 transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="p-8 space-y-6 max-h-[70vh] overflow-y-auto custom-scrollbar">
             <!-- Selector Almacén -->
             <div class="p-6 bg-blue-50/30 dark:bg-blue-900/10 rounded-[2rem] border border-blue-100 dark:border-blue-900/20">
                <label class="block text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-wide mb-4">Selección de Destino</label>
                <select v-model="selectedAlmacen" class="w-full bg-white dark:bg-slate-900 border-2 border-transparent dark:border-slate-800 rounded-2xl font-bold text-sm py-4 px-6 text-slate-900 dark:text-white focus:border-sky-500 transition-all">
                   <option v-for="almacen in almacenes" :key="almacen.id" :value="almacen.id">{{ almacen.nombre }}</option>
                </select>
             </div>

             <div v-for="p in seriesProductos" :key="p.id" class="p-6 bg-slate-50/50 dark:bg-slate-900/50 rounded-[2rem] border border-slate-100 dark:border-slate-800">
                <div class="flex items-center justify-between mb-6">
                   <div>
                      <p class="text-sm font-black text-slate-900 dark:text-white uppercase leading-none mb-1">{{ p.nombre }}</p>
                      <p class="text-[9px] font-black text-slate-400 uppercase tracking-wide">Requerido: {{ p.cantidad }} unidades</p>
                   </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                   <input v-for="(val, idx) in (seriesInputs[p.id] || [])" :key="idx" v-model="seriesInputs[p.id][idx]" type="text" class="bg-white dark:bg-slate-950 border-2 border-slate-100 dark:border-slate-800 rounded-xl py-3 px-4 text-xs font-mono font-bold text-blue-600 dark:text-blue-400 focus:border-sky-500 transition-all outline-none" :placeholder="`Serie #${idx + 1}`" />
                </div>
             </div>
          </div>

          <div class="px-8 py-6 bg-slate-50/50 dark:bg-slate-900/50 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-4">
             <button @click="closeSeriesModal" class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-wide hover:text-slate-900 dark:hover:text-white transition-colors">Cancelar</button>
             <button @click="submitSeriesConversion" class="px-8 py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-[10px] font-black uppercase tracking-[0.2em] rounded-2xl shadow-xl hover:scale-105 transition-all">Finalizar Conversión</button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Loading overlay -->
    <div v-if="loading" class="fixed inset-0 bg-slate-950/40 backdrop-blur-md flex items-center justify-center z-[200]">
      <div class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] shadow-2xl flex flex-col items-center">
        <div class="animate-spin rounded-full h-12 w-12 border-4 border-slate-200 border-t-slate-900 dark:border-slate-800 dark:border-t-white mb-4"></div>
        <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.3em]">Procesando Operación</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.ordenes-index {
  animation: fadeIn 0.4s ease-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; }
</style>
