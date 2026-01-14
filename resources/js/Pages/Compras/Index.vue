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

  <div class="compras-index min-h-screen bg-gray-50">
    <!-- Contenido principal -->
    <div class="max-w-8xl mx-auto px-6 py-8">
      <!-- Header específico de compras -->
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

      <!-- Información de paginación -->
      <div class="flex justify-between items-center mb-4 text-sm text-gray-600">
        <div>
          Mostrando {{ props.pagination.from }} -
          {{ props.pagination.to }}
          de {{ props.pagination.total }} compras
        </div>
        <div class="flex items-center space-x-2">
          <span>Elementos por página:</span>
          <select
            :value="props.pagination.per_page"
            @change="updateFilters({ per_page: $event.target.value, page: 1 })"
            class="border border-gray-300 rounded px-2 py-1 text-sm"
          >
            <option value="10">10</option>
            <option value="15">15</option>
            <option value="25">25</option>
            <option value="50">50</option>
          </select>
        </div>
      </div>

      <!-- Tabla de compras -->
      <div class="mt-6">
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

      <!-- Controles de paginación -->
      <div v-if="props.pagination.total > 0" class="flex justify-center items-center space-x-2 mt-6">
        <button
          @click="prevPage"
          :disabled="currentPage === 1"
          class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Anterior
        </button>

        <div class="flex space-x-1">
          <!-- Primera página (solo si no está en visiblePages) -->
          <template v-if="!visiblePages.includes(1) && totalPages > 7">
            <button
              @click="goToPage(1)"
              class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
            >
              1
            </button>
            <span class="px-3 py-2 text-sm text-gray-500">...</span>
          </template>

          <!-- Páginas visibles -->
          <button
            v-for="page in visiblePages"
            :key="page"
            @click="goToPage(page)"
            :class="[
              'px-3 py-2 text-sm font-medium border border-gray-300 rounded-md',
              page === currentPage
                ? 'bg-blue-500 text-white border-blue-500'
                : 'text-gray-700 bg-white hover:bg-gray-50'
            ]"
          >
            {{ page }}
          </button>

          <!-- Última página (solo si no está en visiblePages) -->
          <template v-if="!visiblePages.includes(totalPages) && totalPages > 7">
            <span class="px-3 py-2 text-sm text-gray-500">...</span>
            <button
              @click="goToPage(totalPages)"
              class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
            >
              {{ totalPages }}
            </button>
          </template>
        </div>

        <button
          @click="nextPage"
          :disabled="currentPage === totalPages"
          class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Siguiente
        </button>
      </div>
    </div>

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
      @close="showImportXmlModal = false"
      @import="handleXmlImport"
    />

    <!-- Loading overlay -->
    <div v-if="loading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded-lg shadow-lg">
        <div class="flex items-center space-x-3">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
          <span class="text-gray-700">Procesando...</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import "tailwindcss" reference;
.compras-index {
  min-height: 100vh;
  background-color: #f9fafb;
}

@media (max-width: 640px) {
  .compras-index .max-w-7xl {
    padding-left: 1rem;
    padding-right: 1rem;
  }

  .compras-index h1 {
    font-size: 1.5rem;
  }
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.compras-index > * {
  animation: fadeIn 0.3s ease-out;
}
</style>



