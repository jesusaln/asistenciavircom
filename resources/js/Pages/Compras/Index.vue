<!-- /resources/js/Pages/Compras/Index.vue -->
<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { router, Head } from '@inertiajs/vue3'
import axios from 'axios'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

import { generarPDF } from '@/Utils/pdfGenerator'
import AppLayout from '@/Layouts/AppLayout.vue'
import ComprasHeader from '@/Components/IndexComponents/ComprasHeader.vue'
import ComprasTable from '@/Components/IndexComponents/ComprasTable.vue'
import Modal from '@/Components/IndexComponents/Modales.vue'
import ModalCompra from '@/Components/IndexComponents/ModalCompra.vue'
import ModalCompras from '@/Components/Compras/ModalCompras.vue'
import ImportXmlModal from '@/Components/Compras/ImportXmlModal.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  compras: {
    type: Object,
    default: () => ({ data: [] })
  },
  stats: {
    type: Object,
    default: () => ({
      total: 0,
      procesadas: 0,
      canceladas: 0
    })
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  sorting: {
    type: Object,
    default: () => ({
      sort_by: 'created_at',
      sort_direction: 'desc',
      allowed_sorts: ['created_at', 'total', 'estado']
    })
  },
  pagination: {
    type: Object,
    default: () => ({
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 0,
      from: 0,
    })
  },
  is_admin: {
    type: Boolean,
    default: false
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

/* =========================
    Estado local y modal
========================= */
const showModal = ref(false)
const fila = ref(null)
const modalMode = ref('details')
const selectedId = ref(null)
const loading = ref(false)
const showImportXmlModal = ref(false)

/* =========================
    Funciones del modal
========================= */
const closeModal = () => {
  showModal.value = false
  fila.value = null
  selectedId.value = null
  modalMode.value = 'details'
}

/* =========================
    Filtros, orden y datos
========================= */
const searchTerm = ref(props.filters.search || '')
const sortBy = ref(`${props.sorting.sort_by}-${props.sorting.sort_direction}`)
const filtroEstado = ref(props.filters.estado || '')
const filtroOrigen = ref(props.filters.origen || '')
const comprasOriginales = ref([...(props.compras?.data || [])])

/* =========================
   Auditoría segura para el modal
========================= */
onMounted(() => {
  console.log('Admin Status:', props.is_admin)
})

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

/* =========================
    Filtrado y ordenamiento (paginación del servidor)
========================= */
const comprasFiltradas = computed(() => {
  return props.compras?.data || []
})

/* =========================
    Paginación (del servidor)
========================= */
const currentPage = computed(() => props.pagination.current_page)
const itemsPerPage = computed(() => props.pagination.per_page)
const totalPages = computed(() => props.pagination.last_page)

const paginatedCompras = computed(() => {
  return comprasFiltradas.value
})

const visiblePages = computed(() => {
  const pages = []
  const total = totalPages.value
  const current = currentPage.value

  if (total <= 7) {
    for (let i = 1; i <= total; i++) {
      pages.push(i)
    }
  } else {
    if (current <= 3) {
      for (let i = 1; i <= 5; i++) {
        pages.push(i)
      }
    } else if (current >= total - 2) {
      for (let i = total - 4; i <= total; i++) {
        pages.push(i)
      }
    } else {
      for (let i = current - 2; i <= current + 2; i++) {
        pages.push(i)
      }
    }
  }

  return pages
})

const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    updateFilters({ page })
  }
}

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    updateFilters({ page: currentPage.value + 1 })
  }
}

const prevPage = () => {
  if (currentPage.value > 1) {
    updateFilters({ page: currentPage.value - 1 })
  }
}

const reloadCurrentPage = () => {
  const params = {
    search: searchTerm.value,
    estado: filtroEstado.value,
    origen: filtroOrigen.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1],
    page: currentPage.value,
    per_page: itemsPerPage.value,
  }

  router.get('/compras', params, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

// Función para actualizar filtros y recargar datos
const updateFilters = (newFilters = {}) => {
  const params = {
    search: searchTerm.value,
    estado: filtroEstado.value,
    origen: filtroOrigen.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1],
    page: 1,
    per_page: itemsPerPage.value,
    ...newFilters
  }

  router.get('/compras', params, {
    preserveState: true,
    replace: true
  })
}

// Watchers para props y filtros
watch(() => props.compras, (newVal) => {
  if (newVal && newVal.data && Array.isArray(newVal.data)) {
    comprasOriginales.value = [...newVal.data]
  }
}, { deep: true, immediate: true })

// Aplicar filtros al cambiar valores
watch([searchTerm, filtroEstado, filtroOrigen, sortBy], () => {
  updateFilters()
})

// Estadísticas calculadas (usando datos del servidor)
const estadisticas = computed(() => {
  return {
    total: props.stats.total || 0,
    procesadas: props.stats.procesadas || 0,
    canceladas: props.stats.canceladas || 0,
  }
})

// Estadísticas adicionales para el header moderno
const montoTotal = computed(() => {
  return props.stats.monto_total || 0
})

const pendientesPago = computed(() => {
  return props.stats.pendientes_pago || 0
})

const handleLimpiarFiltros = () => {
  searchTerm.value = ''
  sortBy.value = 'created_at-desc'
  filtroEstado.value = ''
  filtroOrigen.value = ''
  updateFilters({ page: 1 })
  notyf.success('Filtros limpiados correctamente')
}

const updateSort = (newSort) => {
  if (newSort && typeof newSort === 'string') {
    sortBy.value = newSort
    updateFilters({ page: 1 })
  }
}

/* =========================
   Validaciones y utilidades
========================= */
function puedeCancelarCompra(compra) {
  if (!compra) return false
  return compra.estado === 'procesada'
}

function validarCompra(compra) {
  if (!compra?.id) {
    throw new Error('ID de compra no válido')
  }
  return true
}

function validarCompraBasica(compra) {
  if (!compra?.id) {
    throw new Error('ID de compra no válido')
  }
  if (!compra.proveedor?.nombre_razon_social) {
    throw new Error('Datos del proveedor no encontrados')
  }
  if (!Array.isArray(compra.productos) || !compra.productos.length) {
    throw new Error('Lista de productos no válida')
  }
  if (!compra.fecha && !compra.created_at) {
    throw new Error('Fecha no especificada')
  }
  return true
}

function validarCompraParaPDF(doc) {
  if (!doc.id) throw new Error('ID del documento no encontrado')
  if (!doc.cliente?.nombre_razon_social) throw new Error('Datos del cliente no encontrados')
  if (!Array.isArray(doc.productos) || !doc.productos.length) {
    throw new Error('Lista de productos no válida')
  }
  if (!doc.fecha) throw new Error('Fecha no especificada')
  return true
}

/* =========================
   Acciones CRUD
========================= */
const verDetalles = (compra) => {
  try {
    validarCompra(compra)
    fila.value = compra
    modalMode.value = 'details'
    showModal.value = true
  } catch (error) {
    notyf.error(error.message)
  }
}

const abrirModalDetalles = (compra) => {
  fila.value = compra
  modalMode.value = 'details'
  showModal.value = true
}

const abrirModalConfirmacion = (id) => {
  selectedId.value = id
  modalMode.value = 'confirm'
  showModal.value = true
}

const editarCompra = (id) => {
  try {
    const compraId = id || fila.value?.id
    if (!compraId) throw new Error('ID de compra no válido')

    router.visit(`/compras/${compraId}/edit`)
  } catch (error) {
    notyf.error(error.message)
  }
}

const editarFila = (id) => {
  editarCompra(id)
}


const imprimirCompra = async (compra) => {
  try {
    const doc = {
      ...compra,
      cliente: compra.proveedor, // Mapear proveedor a cliente para el PDF
      productos: compra.productos,   // Mapear productos para el PDF
      fecha: compra.fecha || compra.created_at || new Date().toISOString()
    }

    validarCompraParaPDF(doc)

    loading.value = true
    notyf.success('Generando PDF...')

    await generarPDF('Compra', doc)
    notyf.success('PDF generado correctamente')

  } catch (error) {
    console.error('Error al generar PDF:', error)
    notyf.error(`Error al generar el PDF: ${error.message}`)
  } finally {
    loading.value = false
  }
}

const imprimirFila = () => {
  if (fila.value) {
    imprimirCompra(fila.value)
  }
}

const confirmarEliminacion = (id) => {
  try {
    if (!id) throw new Error('ID de compra no válido')
    const compra = comprasOriginales.value.find(c => c.id === id)
    if (compra && compra.estado === 'procesada') {
      // Si está procesada, mostrar modal de cancelación
      selectedId.value = id
      modalMode.value = 'cancel'
      showModal.value = true
    } else {
      // Si está cancelada o en otro estado, mostrar modal de eliminación
      selectedId.value = id
      modalMode.value = 'delete'
      showModal.value = true
    }
  } catch (error) {
    notyf.error(error.message)
  }
}

const cancelarCompra = async () => {
  try {
    if (!selectedId.value) throw new Error('No se seleccionó ninguna compra')

    loading.value = true

    router.post(`/compras/${selectedId.value}/cancel`, {}, {
      onStart: () => {
        notyf.success('Cancelando compra...')
      },
      onSuccess: (page) => {
      if (page.props.flash?.error) {
        notyf.error(page.props.flash.error)
        closeModal()
      } else {
        notyf.success('Compra cancelada exitosamente')
        closeModal()
        reloadCurrentPage()
      }
    },
      onError: (errors) => {
        console.error('Error al cancelar:', errors)
        const errorMsg = errors?.message || 'Error al cancelar la compra'
        notyf.error(errorMsg)
        closeModal()
      },
      onFinish: () => {
        loading.value = false
      }
    })
  } catch (error) {
    notyf.error(error.message)
    loading.value = false
    closeModal()
  }
}

const eliminarCompra = async () => {
  try {
    if (!selectedId.value) throw new Error('No se seleccionó ninguna compra')

    loading.value = true

    router.delete(`/compras/${selectedId.value}`, {
      onStart: () => {
        notyf.success('Eliminando compra...')
      },
      onSuccess: (page) => {
      if (page.props.flash?.error) {
        notyf.error(page.props.flash.error)
        closeModal()
      } else {
        notyf.success('Compra eliminada exitosamente')
        closeModal()
        reloadCurrentPage()
      }
    },
      onError: (errors) => {
        console.error('Error al eliminar:', errors)
        const errorMsg = errors?.message || 'Error al eliminar la compra'
        notyf.error(errorMsg)
        closeModal() // Cerrar el modal incluso en error
      },
      onFinish: () => {
        loading.value = false
      }
    })
  } catch (error) {
    notyf.error(error.message)
    loading.value = false
    closeModal() // Cerrar el modal en caso de error
  }
}


const crearNuevaCompra = () => {
  router.visit('/compras/create')
}

const importarDesdeXml = () => {
  showImportXmlModal.value = true
}

const handleXmlImport = (cfdiData) => {
  // Check if purchase was already created by the modal
  if (cfdiData.compra_creada) {
    notyf.success('Compra importada exitosamente')
    reloadCurrentPage()
    return
  }

  // Guardar datos del CFDI en sessionStorage para usar en Create
  sessionStorage.setItem('cfdi_import_data', JSON.stringify(cfdiData))
  
  notyf.success('Redirigiendo al formulario de compra...')
  
  // Redirigir a Create con parámetro de importación
  router.visit('/compras/create?from_xml=1')
}

const handleConfirm = () => {
  if (modalMode.value === 'cancel') {
    cancelarCompra()
  } else if (modalMode.value === 'delete') {
    eliminarCompra()
  }
}
</script>

<template>
  <Head title="Compras" />

  <div class="compras-index min-h-screen bg-white dark:bg-slate-950 transition-colors duration-500 overflow-x-hidden">
    <!-- Contenido principal -->
    <div class="w-full px-4 sm:px-8 py-10 max-w-[1920px] mx-auto relative z-10">
      
      <!-- Ambient Backdrops (Dark Mode Only) -->
      <div class="fixed inset-0 pointer-events-none overflow-hidden z-0 opacity-30 dark:opacity-100 hidden dark:block">
        <div class="absolute -top-[10%] -right-[5%] w-[40%] h-[40%] bg-blue-600/10 rounded-full blur-[120px] animate-pulse-slow"></div>
        <div class="absolute top-[20%] -left-[5%] w-[30%] h-[30%] bg-indigo-600/5 rounded-full blur-[100px]"></div>
      </div>

      <!-- Header específico de compras -->
      <div class="animate-fade-in-up">
        <ComprasHeader
          :total="estadisticas.total"
          :procesadas="estadisticas.procesadas"
          :canceladas="estadisticas.canceladas"
          :monto-total="montoTotal"
          :pendientes-pago="pendientesPago"
          v-model:search-term="searchTerm"
          v-model:sort-by="sortBy"
          v-model:filtro-estado="filtroEstado"
          v-model:filtro-origen="filtroOrigen"
          @crear-nueva="crearNuevaCompra"
          @importar-xml="importarDesdeXml"
          @search-change="updateFilters"
          @filtro-estado-change="updateFilters"
          @filtro-origen-change="updateFilters"
          @sort-change="updateSort"
          @limpiar-filtros="handleLimpiarFiltros"
        />
      </div>

      <!-- Info & Tools Bar -->
      <div class="flex flex-col sm:flex-row justify-between items-center mt-10 mb-6 gap-4 animate-fade-in-up" style="animation-delay: 100ms">
        <div class="flex items-center gap-3">
            <div class="w-1 h-8 bg-blue-600 rounded-full hidden sm:block"></div>
            <div class="text-sm font-black text-slate-500 dark:text-slate-400 tracking-widest uppercase">
            Mostrando <span class="text-slate-900 dark:text-white">{{ props.pagination.from }} - {{ props.pagination.to }}</span>
            de <span class="text-slate-900 dark:text-white">{{ props.pagination.total }}</span> Compras
            </div>
        </div>
        
        <div class="flex items-center gap-4 bg-white/50 dark:bg-slate-900/50 backdrop-blur-md px-4 py-2 rounded-2xl border border-slate-200/50 dark:border-slate-800/50">
          <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Por página</label>
          <select
            :value="props.pagination.per_page"
            @change="updateFilters({ per_page: $event.target.value, page: 1 })"
            class="bg-transparent border-none text-xs font-black text-slate-900 dark:text-white focus:ring-0 cursor-pointer"
          >
            <option value="10">10</option>
            <option value="15">15</option>
            <option value="25">25</option>
            <option value="50">50</option>
          </select>
        </div>
      </div>

      <!-- Tabla de compras -->
      <div class="mt-2 animate-fade-in-up" style="animation-delay: 200ms">
        <div class="relative group">
            <!-- Glass Overlay behind table if needed -->
            <div class="absolute -inset-1 bg-gradient-to-r from-blue-600/5 to-indigo-600/5 rounded-[2.5rem] blur-xl opacity-0 group-hover:opacity-100 transition-opacity duration-700 pointer-events-none"></div>
            <ComprasTable
                :documentos="paginatedCompras"
                :search-term="searchTerm"
                :sort-by="sortBy"
                :filtro-estado="filtroEstado"
                :filtro-origen="filtroOrigen"
                :is-admin="props.is_admin"
                @ver-detalles="verDetalles"
                @editar="editarCompra"
                @imprimir="imprimirCompra"
                @eliminar="confirmarEliminacion"
                @sort="updateSort"
            />
        </div>
      </div>

      <!-- Controles de paginación Premium -->
      <div v-if="props.pagination.total > 0" class="flex justify-center items-center gap-2 mt-12 animate-fade-in-up" style="animation-delay: 300ms">
        <button
          @click="prevPage"
          :disabled="currentPage === 1"
          class="group w-12 h-12 flex items-center justify-center rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 hover:text-blue-600 hover:border-blue-600/30 hover:shadow-lg disabled:opacity-30 disabled:cursor-not-allowed transition-all duration-300"
        >
          <svg class="w-5 h-5 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
        </button>

        <div class="flex items-center gap-2 p-1 bg-white/50 dark:bg-slate-900/50 backdrop-blur-md rounded-[1.5rem] border border-slate-200/50 dark:border-slate-800/50">
          <template v-if="!visiblePages.includes(1) && totalPages > 7">
            <button @click="goToPage(1)" class="w-10 h-10 flex items-center justify-center rounded-xl text-xs font-black transition-all hover:bg-slate-100 dark:hover:bg-slate-800">1</button>
            <span class="text-slate-300 dark:text-slate-600">•••</span>
          </template>

          <button
            v-for="page in visiblePages"
            :key="page"
            @click="goToPage(page)"
            :class="[
              'w-10 h-10 flex items-center justify-center rounded-xl text-xs font-black transition-all duration-300',
              page === currentPage
                ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30 scale-110'
                : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white'
            ]"
          >
            {{ page }}
          </button>

          <template v-if="!visiblePages.includes(totalPages) && totalPages > 7">
            <span class="text-slate-300 dark:text-slate-600">•••</span>
            <button @click="goToPage(totalPages)" class="w-10 h-10 flex items-center justify-center rounded-xl text-xs font-black transition-all hover:bg-slate-100 dark:hover:bg-slate-800">{{ totalPages }}</button>
          </template>
        </div>

        <button
          @click="nextPage"
          :disabled="currentPage === totalPages"
          class="group w-12 h-12 flex items-center justify-center rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 hover:text-blue-600 hover:border-blue-600/30 hover:shadow-lg disabled:opacity-30 disabled:cursor-not-allowed transition-all duration-300"
        >
          <svg class="w-5 h-5 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
        </button>
      </div>
    </div>

    <!-- Modal de detalles (Themed via component) -->
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

    <!-- Modal de cancelación/eliminación (Themed via component) -->
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

    <!-- Multi-layered Loading Overlay -->
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="loading" class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm flex items-center justify-center z-[100]">
        <div class="relative">
            <div class="absolute inset-0 bg-blue-600 rounded-full blur-2xl opacity-20 animate-pulse"></div>
            <div class="relative bg-white dark:bg-slate-900 p-10 rounded-[2.5rem] border border-slate-200 dark:border-slate-800 shadow-2xl flex flex-col items-center">
                <div class="w-16 h-16 border-4 border-slate-100 dark:border-slate-800 border-t-blue-600 rounded-full animate-spin mb-6"></div>
                <p class="text-xs font-black uppercase tracking-[0.3em] text-slate-900 dark:text-white">Procesando</p>
            </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-up {
    animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes pulse-slow {
    0%, 100% { opacity: 0.1; transform: scale(1); }
    50% { opacity: 0.15; transform: scale(1.1); }
}

.animate-pulse-slow {
    animation: pulse-slow 8s ease-in-out infinite;
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-track {
    background: transparent;
}
::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
.dark ::-webkit-scrollbar-thumb {
    background: #334155;
}
::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.compras-index {
  min-height: 100vh;
}

@media (max-width: 640px) {
  .compras-index .w-full {
    padding-left: 1rem;
    padding-right: 1rem;
  }
}
</style>



