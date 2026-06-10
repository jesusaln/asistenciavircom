<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { router, Head, Link, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

import { generarPDF } from '@/Utils/pdfGenerator'
import AppLayout from '@/Layouts/AppLayout.vue'
import CrudPageHeader from '@/Components/CrudPageHeader.vue'
import IndexTable from '@/Components/IndexTable.vue'
import ModalCompra from '@/Components/IndexComponents/ModalCompra.vue'
import ModalCompras from '@/Components/Compras/ModalCompras.vue'
import ImportXmlModal from '@/Components/Compras/ImportXmlModal.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  compras: { type: Object, default: () => ({ data: [] }) },
  stats: { type: Object, default: () => ({ total: 0, procesadas: 0, canceladas: 0 }) },
  filters: { type: Object, default: () => ({}) },
  sorting: { type: Object, default: () => ({ sort_by: 'created_at', sort_direction: 'desc', allowed_sorts: ['created_at', 'total', 'estado'] }) },
  pagination: { type: Object, default: () => ({ current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0 }) },
  is_admin: { type: Boolean, default: false },
  almacenes_list: { type: Array, default: () => [] }
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

onMounted(() => {
  const flash = usePage().props.flash
  if (flash?.success) notyf.success(flash.success)
  if (flash?.error) notyf.error(flash.error)
})

const showModal = ref(false)
const fila = ref(null)
const modalMode = ref('details')
const selectedId = ref(null)
const loading = ref(false)
const showImportXmlModal = ref(false)

const closeModal = () => { showModal.value = false; fila.value = null; selectedId.value = null; modalMode.value = 'details' }

const searchTerm = ref(props.filters.search || '')
const sortBy = ref(`${props.sorting.sort_by}-${props.sorting.sort_direction}`)
const filtroEstado = ref(props.filters.estado || '')
const filtroOrigen = ref(props.filters.origen || '')
const comprasOriginales = ref([...(props.compras?.data || [])])

const auditoriaForModal = computed(() => {
  const r = fila.value
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

const paginationData = computed(() => props.pagination)

const goToPage = (page) => {
  if (page >= 1 && page <= (props.pagination.last_page || 1)) {
    updateFilters({ page })
  }
}

const updateFilters = (newFilters = {}) => {
  router.get('/compras', {
    search: searchTerm.value, estado: filtroEstado.value, origen: filtroOrigen.value,
    sort_by: sortBy.value.split('-')[0], sort_direction: sortBy.value.split('-')[1],
    page: 1, per_page: props.pagination.per_page || 10, ...newFilters
  }, { preserveState: true, replace: true })
}

const onSearch = () => updateFilters({ page: 1 })

watch(() => props.compras, (newVal) => {
  if (newVal?.data && Array.isArray(newVal.data)) comprasOriginales.value = [...newVal.data]
}, { deep: true, immediate: true })

const estadisticas = computed(() => ({
  total: props.stats.total || 0, procesadas: props.stats.procesadas || 0, canceladas: props.stats.canceladas || 0,
}))

const montoTotal = computed(() => props.stats.monto_total || 0)
const pendientesPago = computed(() => props.stats.pendientes_pago || 0)

const handleLimpiarFiltros = () => {
  searchTerm.value = ''; sortBy.value = 'created_at-desc'; filtroEstado.value = ''; filtroOrigen.value = ''
  updateFilters({ page: 1 })
  notyf.success('Filtros limpiados correctamente')
}

const updateSort = (newSort) => { if (newSort) sortBy.value = newSort }

const formatearFecha = (v) => {
  if (!v) return '-'
  try { return new Date(v).toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' }) }
  catch { return '-' }
}

const formatearMoneda = (num) => {
  const value = parseFloat(num)
  const safe = Number.isFinite(value) ? value : 0
  return new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(safe)
}

const columns = [
  { key: 'fecha', label: 'Fecha', format: (v, row) => formatearFecha(row.created_at || row.fecha) },
  { key: 'proveedor', label: 'Proveedor', format: (v, row) => row.proveedor?.nombre_razon_social || row.proveedor?.nombre || 'Sin proveedor' },
  { key: 'numero_compra', label: 'N° Compra', format: (v) => v || 'N/A' },
  { key: 'total', label: 'Total', format: (v) => '$' + formatearMoneda(v) },
  { key: 'estado', label: 'Estado' },
]

/* =========================
   Acciones CRUD
========================= */
const verDetalles = (compra) => {
  if (!compra?.id) { notyf.error('ID de compra no válido'); return }
  fila.value = compra; modalMode.value = 'details'; showModal.value = true
}

const editarCompra = (id) => {
  if (!id) { notyf.error('ID de compra no válido'); return }
  router.visit(`/compras/${id}/edit`)
}

const editarFila = (id) => editarCompra(id)

const imprimirCompra = async (compra) => {
  try {
    const doc = { ...compra, cliente: compra.proveedor, productos: compra.productos, fecha: compra.fecha || compra.created_at || new Date().toISOString() }
    if (!doc.id) throw new Error('ID del documento no encontrado')
    loading.value = true; notyf.success('Generando PDF...')
    await generarPDF('Compra', doc)
    notyf.success('PDF generado correctamente')
  } catch (error) { notyf.error(`Error al generar el PDF: ${error.message}`) }
  finally { loading.value = false }
}

const imprimirFila = () => { if (fila.value) imprimirCompra(fila.value) }

const confirmarEliminacion = (id) => {
  if (!id) { notyf.error('ID de compra no válido'); return }
  const compra = comprasOriginales.value.find(c => c.id === id)
  selectedId.value = id
  if (compra && compra.estado === 'procesada') {
    modalMode.value = 'cancel'; showModal.value = true
  } else {
    modalMode.value = 'delete'; showModal.value = true
  }
}

const cancelarCompra = async () => {
  if (!selectedId.value) { notyf.error('No se seleccionó ninguna compra'); return }
  loading.value = true
  router.post(`/compras/${selectedId.value}/cancel`, {}, {
    onStart: () => notyf.success('Cancelando compra...'),
    onSuccess: (page) => {
      if (page.props.flash?.error) { notyf.error(page.props.flash.error) } else { notyf.success('Compra cancelada exitosamente') }
      closeModal(); reloadCurrentPage()
    },
    onError: (errors) => { notyf.error(errors?.message || 'Error al cancelar la compra'); closeModal() },
    onFinish: () => { loading.value = false }
  })
}

const eliminarCompra = async () => {
  if (!selectedId.value) { notyf.error('No se seleccionó ninguna compra'); return }
  loading.value = true
  router.delete(`/compras/${selectedId.value}`, {
    onStart: () => notyf.success('Eliminando compra...'),
    onSuccess: (page) => {
      if (page.props.flash?.error) { notyf.error(page.props.flash.error) } else { notyf.success('Compra eliminada exitosamente') }
      closeModal(); reloadCurrentPage()
    },
    onError: (errors) => { notyf.error(errors?.message || 'Error al eliminar la compra'); closeModal() },
    onFinish: () => { loading.value = false }
  })
}

const reloadCurrentPage = () => {
  router.get('/compras', {
    search: searchTerm.value, estado: filtroEstado.value, origen: filtroOrigen.value,
    sort_by: sortBy.value.split('-')[0], sort_direction: sortBy.value.split('-')[1],
    page: props.pagination.current_page, per_page: props.pagination.per_page,
  }, { preserveState: true, preserveScroll: true, replace: true })
}

const crearNuevaCompra = () => router.visit('/compras/create')

const importarDesdeXml = () => { showImportXmlModal.value = true }

const handleXmlImport = (cfdiData) => {
  if (cfdiData.compra_creada) { notyf.success('Compra importada exitosamente'); reloadCurrentPage(); return }
  sessionStorage.setItem('cfdi_import_data', JSON.stringify(cfdiData))
  notyf.success('Redirigiendo al formulario de compra...')
  router.visit('/compras/create?from_xml=1')
}

const handleConfirm = () => {
  if (modalMode.value === 'cancel') cancelarCompra()
  else if (modalMode.value === 'delete') eliminarCompra()
}
</script>

<template>
  <Head title="Compras" />

  <div class="min-h-screen">
    <div class="w-full px-4 sm:px-6 py-6">
      <CrudPageHeader title="Compras" subtitle="Gestión de compras">
        <template #actions>
          <div class="flex items-center gap-2">
            <div class="relative">
              <input v-model="searchTerm" @keyup.enter="onSearch" type="text" placeholder="Buscar..."
                class="w-48 lg:w-64 px-4 py-2.5 text-sm border border-slate-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all" />
            </div>
            <select v-model="filtroEstado" @change="onSearch"
              class="px-4 py-2.5 text-sm border border-slate-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all">
              <option value="">Todos los estados</option>
              <option value="procesada">Procesada</option>
              <option value="cancelada">Cancelada</option>
              <option value="borrador">Borrador</option>
            </select>
            <button @click="importarDesdeXml"
              class="inline-flex items-center px-4 py-2.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl transition-all duration-200 shadow-sm">
              <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
              Importar XML
            </button>
            <Link :href="route('compras.create')"
              class="inline-flex items-center px-4 py-2.5 bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold rounded-xl transition-all duration-200 shadow-sm">
              <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Nueva Compra
            </Link>
          </div>
        </template>
      </CrudPageHeader>

      <IndexTable
        :columns="columns"
        :rows="props.compras?.data || []"
        empty-text="No hay compras registradas"
        empty-subtext="Crea la primera compra usando el botón Nueva Compra"
      >
        <template #actions="{ row }">
          <div class="flex justify-end gap-1.5">
            <button @click="verDetalles(row)"
              class="w-9 h-9 flex items-center justify-center rounded-xl transition-all duration-200 bg-sky-50 dark:bg-sky-900/20 text-sky-600 dark:text-sky-400 hover:bg-sky-100 dark:hover:bg-sky-900/30"
              title="Ver detalles">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </button>
            <button @click="editarCompra(row.id)"
              class="w-9 h-9 flex items-center justify-center rounded-xl transition-all duration-200 bg-brand-50 dark:bg-brand-900/20 text-brand-600 dark:text-brand-400 hover:bg-brand-100 dark:hover:bg-brand-900/30"
              title="Editar">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <button @click="imprimirCompra(row)"
              class="w-9 h-9 flex items-center justify-center rounded-xl transition-all duration-200 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-600"
              title="Imprimir">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
              </svg>
            </button>
            <button @click="confirmarEliminacion(row.id)"
              class="w-9 h-9 flex items-center justify-center rounded-xl transition-all duration-200 bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900/30"
              title="Eliminar">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </template>
        <template #pagination>
          <div v-if="paginationData.last_page > 1" class="flex justify-between items-center">
            <div class="text-sm text-slate-500">
              Mostrando {{ paginationData.from || 0 }} - {{ paginationData.to || 0 }} de {{ paginationData.total || 0 }}
            </div>
            <div class="flex gap-1.5">
              <button @click="goToPage(paginationData.current_page - 1)" :disabled="paginationData.current_page <= 1"
                class="px-3 py-1.5 text-sm rounded-lg transition-all duration-150 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 disabled:opacity-50">Anterior</button>
              <Link v-for="(link, i) in (props.compras.links || [])" :key="i"
                :href="link.url || '#'"
                v-html="link.label"
                class="px-3 py-1.5 text-sm rounded-lg transition-all duration-150"
                :class="link.active
                  ? 'bg-brand-500 text-white'
                  : link.url ? 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' : 'text-slate-300 cursor-default'" />
            </div>
          </div>
        </template>
      </IndexTable>

      <!-- Modal de detalles -->
      <ModalCompra
        v-if="modalMode === 'details'"
        :show="showModal"
        :selected="fila || {}"
        :auditoria="auditoriaForModal"
        @close="closeModal"
        @editar="editarFila"
        @eliminar="confirmarEliminacion"
        @imprimir="imprimirFila"
      />

      <!-- Modal de cancelación/eliminación -->
      <ModalCompras
        v-if="modalMode === 'cancel' || modalMode === 'delete'"
        :show="showModal"
        :mode="modalMode"
        @close="closeModal"
        @confirm="handleConfirm"
      />

      <!-- Modal de importación XML -->
      <ImportXmlModal
        :show="showImportXmlModal"
        :almacenes-list="props.almacenes_list"
        @close="showImportXmlModal = false"
        @import="handleXmlImport"
      />

      <!-- Loading overlay -->
      <div v-if="loading" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-100 dark:border-slate-700 p-6">
          <div class="flex items-center space-x-3">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-brand-500"></div>
            <span class="text-slate-700 dark:text-slate-200">Procesando...</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
