<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { router, Head, Link, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

import { generarPDF } from '@/Utils/pdfGenerator'
import AppLayout from '@/Layouts/AppLayout.vue'
import PedidosHeader from '@/Components/IndexComponents/PedidosHeader.vue'
import IndexTable from '@/Components/IndexTable.vue'
import Modal from '@/Components/IndexComponents/Modales.vue'
import ModalPedido from '@/Components/IndexComponents/ModalPedido.vue'
import debounce from 'lodash-es/debounce'

defineOptions({ layout: AppLayout })

const props = defineProps({
  pedidos: { type: Object, default: () => ({ data: [] }) },
  stats: { type: Object, default: () => ({}) },
  filterOptions: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  sorting: { type: Object, default: () => ({ sort_by: 'created_at', sort_direction: 'desc' }) },
  pagination: { type: Object, default: () => ({}) },
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

const searchTerm = ref(props.filters?.search ?? '')
const sortBy = ref(props.sorting?.sort_by ? `${props.sorting.sort_by}-${props.sorting.sort_direction}` : 'created_at-desc')
const filtroEstado = ref(props.filters?.estado ?? '')
const filtroCliente = ref(props.filters?.cliente_id ?? '')
const perPage = ref(10)

onMounted(() => {
  const flash = usePage().props.flash
  if (flash?.success) notyf.success(flash.success)
  if (flash?.error) notyf.error(flash.error)
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

const estadisticas = computed(() => {
  const s = props.stats || {}
  const total = s.total || 0
  return {
    total,
    borradores: s.borradores || 0,
    pendientes: s.pendientes || 0,
    confirmados: s.confirmados || 0,
    enviados_venta: s.enviados_venta || 0,
    cancelados: s.cancelados || 0,
    con_cotizacion: s.con_cotizacion || 0,
    sin_cotizacion: s.sin_cotizacion || 0,
  }
})

const paginationData = computed(() => ({
  current_page: props.pagination?.current_page || 1,
  last_page: props.pagination?.last_page || 1,
  per_page: props.pagination?.per_page || 10,
  from: props.pagination?.from || 0,
  to: props.pagination?.to || 0,
  total: props.pagination?.total || 0,
}))

const formatearFecha = (date) => {
  if (!date) return 'Fecha no disponible'
  try {
    const d = new Date(date)
    return d.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' })
  } catch { return 'Fecha inválida' }
}

const formatearMoneda = (num) => {
  const value = parseFloat(num)
  const safe = Number.isFinite(value) ? value : 0
  return new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(safe)
}

const configEstados = {
  'borrador': { label: 'Borrador', classes: 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200', color: 'bg-slate-400' },
  'pendiente': { label: 'Pendiente', classes: 'bg-brand-50 dark:bg-brand-900/20 text-brand-800 dark:text-amber-200', color: 'bg-amber-400' },
  'confirmado': { label: 'Confirmado', classes: 'bg-blue-50 dark:bg-sky-900/20 text-sky-800 dark:text-sky-200', color: 'bg-blue-400' },
  'enviado_venta': { label: 'Enviado a Venta', classes: 'bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300', color: 'bg-purple-400' },
  'cancelado': { label: 'Cancelado', classes: 'bg-rose-50 dark:bg-rose-900/20 text-rose-800 dark:text-rose-200', color: 'bg-rose-400' }
}

const obtenerClasesEstado = (estado) => configEstados[estado]?.classes || 'bg-slate-100 text-slate-700'
const obtenerColorPuntoEstado = (estado) => configEstados[estado]?.color || 'bg-slate-400'
const obtenerLabelEstado = (estado) => configEstados[estado]?.label || 'Pendiente'

const columns = [
  { key: 'fecha', label: 'Fecha', format: (v, row) => formatearFecha(row.fecha || row.created_at) },
  { key: 'cliente', label: 'Cliente', format: (v, row) => row.cliente?.nombre_razon_social || row.cliente?.nombre || 'Sin cliente' },
  { key: 'numero_pedido', label: 'N° Pedido', format: (v) => v || 'N/A' },
  { key: 'total', label: 'Total', format: (v) => '$' + formatearMoneda(v) },
  { key: 'estado', label: 'Estado', format: (v) => obtenerLabelEstado(v) },
]

const onSearch = () => {
  router.get(route('pedidos.index'), {
    search: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    estado: filtroEstado.value,
    cliente_id: filtroCliente.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const debouncedSearch = debounce(() => {
  onSearch()
}, 400)

const handlePageChange = (page) => {
  router.get(route('pedidos.index'), {
    ...props.filters,
    ...props.sorting,
    page
  }, { preserveState: true, preserveScroll: true })
}

const crearNuevoPedido = () => router.visit('/pedidos/create')

const verDetalles = (pedido) => {
  if (!pedido?.id) { notyf.error('ID de pedido no válido'); return }
  fila.value = pedido
  modalMode.value = 'details'
  showModal.value = true
}

const editarPedido = (id) => {
  if (!id) { notyf.error('ID de pedido no válido'); return }
  router.visit(`/pedidos/${id}/edit`)
}

const duplicarPedido = (pedido) => {
  if (!pedido?.id) { notyf.error('Pedido no válido'); return }
  fila.value = pedido
  modalMode.value = 'confirm-duplicate'
  showModal.value = true
}

const imprimirPedido = async (pedido) => {
  try {
    const doc = { ...pedido, fecha: pedido.fecha || pedido.created_at || new Date().toISOString() }
    if (!doc.id) throw new Error('ID del documento no encontrado')
    loading.value = true
    notyf.success('Generando PDF...')
    await generarPDF('Pedido', doc)
    notyf.success('PDF generado correctamente')
  } catch (error) {
    notyf.error(`Error al generar el PDF: ${error.message}`)
  } finally { loading.value = false }
}

const confirmarEliminacion = (id) => {
  if (!id) { notyf.error('ID de pedido no válido'); return }
  selectedId.value = id
  modalMode.value = 'confirm'
  showModal.value = true
}

const eliminarPedido = (id = null) => {
  const targetId = id || selectedId.value
  if (!targetId) return
  loading.value = true
  router.delete(route('pedidos.destroy', targetId), {
    preserveScroll: true,
    onSuccess: () => { notyf.success('Pedido eliminado exitosamente'); showModal.value = false; selectedId.value = null },
    onError: () => notyf.error('Error al eliminar el pedido'),
    onFinish: () => { loading.value = false }
  })
}

const cerrarPedido = async () => {
  if (!selectedId.value) { notyf.error('No se seleccionó ningún pedido'); return }
  try {
    loading.value = true
    const { data } = await axios.post(`/pedidos/${selectedId.value}/cancel`)
    if (data?.success) {
      notyf.success(data.message || 'Pedido cerrado exitosamente')
      showModal.value = false; selectedId.value = null
      router.reload()
    } else throw new Error(data?.error || 'Error al cerrar el pedido')
  } catch (error) {
    notyf.error('Error al cerrar el pedido')
  } finally { loading.value = false }
}

const enviarAVenta = async (pedidoData) => {
  const doc = pedidoData?.id ? pedidoData : fila.value
  if (!doc?.id) { notyf.error('Pedido no válido'); return }
  router.visit(route('ventas.create', { pedido_id: doc.id }))
  showModal.value = false
  notyf.success('Redirigiendo a nueva venta...')
}

const enviarPedidoPorEmail = (pedido) => {
  if (!pedido.cliente?.email) { notyf.error('El cliente no tiene email configurado'); return }
  fila.value = { ...pedido, numero_pedido: pedido.numero_pedido || `P${String(pedido.id).padStart(4, '0')}`, email_destino: pedido.cliente.email }
  modalMode.value = 'confirm-email'
  showModal.value = true
}

const confirmarEnvioEmail = async () => {
  const pedido = fila.value
  if (!pedido?.email_destino) { notyf.error('Email de destino no válido'); return }
  try {
    loading.value = true; cerrarModal()
    const { data } = await axios.post(`/pedidos/${pedido.id}/enviar-email`, { email_destino: pedido.email_destino })
    if (data?.success) notyf.success(data.message || 'Pedido enviado por email correctamente')
    else throw new Error(data?.error || 'Error desconocido')
  } catch (error) {
    notyf.error('Error al enviar pedido')
  } finally { loading.value = false }
}

const confirmarDuplicarPedido = async () => {
  const pedido = fila.value
  if (!pedido?.id) { notyf.error('Pedido no válido'); return }
  try {
    loading.value = true; cerrarModal()
    const { data } = await axios.post(`/pedidos/${pedido.id}/duplicate`)
    if (data?.success) { notyf.success(data.message || 'Pedido duplicado exitosamente'); router.reload() }
    else throw new Error(data?.error || 'Error al duplicar')
  } catch (error) {
    notyf.error('Error al duplicar el pedido')
  } finally { loading.value = false }
}

const hayFiltrosActivos = computed(() => !!searchTerm.value || !!filtroEstado.value || !!filtroCliente.value)

const limpiarFiltros = () => {
  searchTerm.value = ''; filtroEstado.value = ''; filtroCliente.value = ''
  router.visit('/pedidos')
  notyf.success('Filtros limpiados correctamente')
}

const cerrarModal = () => { showModal.value = false; fila.value = null; selectedId.value = null }

const onCancel = () => cerrarModal()
const onConfirm = () => eliminarPedido()
const onClose = () => cerrarModal()
const imprimirFila = () => { if (fila.value) imprimirPedido(fila.value) }
const editarFila = (id) => editarPedido(id || fila.value?.id)
</script>

<template>
  <Head title="Pedidos" />

  <div class="min-h-screen">
    <div class="w-full px-4 sm:px-6 py-6">
      <PedidosHeader
        :total="estadisticas.total"
        :borradores="estadisticas.borradores"
        :pendientes="estadisticas.pendientes"
        :confirmados="estadisticas.confirmados"
        :enviados_venta="estadisticas.enviados_venta"
        :cancelados="estadisticas.cancelados"
        v-model:search-term="searchTerm"
        v-model:sort-by="sortBy"
        v-model:filtro-estado="filtroEstado"
        v-model:filtro-cliente="filtroCliente"
        :filter-options="filterOptions"
        @crear-nueva="crearNuevoPedido"
        @search-change="debouncedSearch"
        @filtro-estado-change="onSearch"
        @filtro-cliente-change="onSearch"
        @sort-change="onSearch"
        @limpiar-filtros="limpiarFiltros"
      />

      <div class="flex justify-between items-center mb-4 text-sm text-slate-500 dark:text-slate-400">
        <div>Mostrando {{ paginationData.from }} - {{ paginationData.to }} de {{ paginationData.total }} pedidos</div>
        <div v-if="hayFiltrosActivos">
          <button @click="limpiarFiltros" class="text-xs text-brand-600 hover:text-brand-700 font-semibold">
            Limpiar filtros
          </button>
        </div>
      </div>

      <IndexTable
        :columns="columns"
        :rows="props.pedidos.data || []"
        empty-text="No hay pedidos registrados"
        empty-subtext="Los pedidos aparecerán aquí cuando se creen"
      >
        <template #actions="{ row }">
          <div class="flex justify-end gap-1.5">
            <button @click="verDetalles(row)"
              class="w-9 h-9 flex items-center justify-center rounded-xl transition-all duration-200 bg-sky-50 dark:bg-sky-900/20 text-sky-600 dark:text-sky-400 hover:bg-sky-100 dark:hover:bg-sky-900/30"
              title="Ver detalles">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </button>
            <button v-if="['borrador', 'pendiente'].includes(row.estado)" @click="editarPedido(row.id)"
              class="w-9 h-9 flex items-center justify-center rounded-xl transition-all duration-200 bg-brand-50 dark:bg-brand-900/20 text-brand-600 dark:text-brand-400 hover:bg-brand-100 dark:hover:bg-brand-900/30"
              title="Editar">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <button v-if="row.estado !== 'cancelado' && row.cliente?.email" @click="enviarPedidoPorEmail(row)"
              class="w-9 h-9 flex items-center justify-center rounded-xl transition-all duration-200 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/30"
              :title="row.email_enviado ? 'Reenviar por Email' : 'Enviar por Email'">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
            </button>
            <button v-if="['confirmado', 'en_preparacion', 'borrador', 'pendiente'].includes(row.estado)" @click="enviarAVenta(row)"
              class="w-9 h-9 flex items-center justify-center rounded-xl transition-all duration-200 bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 hover:bg-purple-100 dark:hover:bg-purple-900/30"
              title="Enviar a venta">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
              </svg>
            </button>
            <button @click="duplicarPedido(row)"
              class="w-9 h-9 flex items-center justify-center rounded-xl transition-all duration-200 bg-sky-50 dark:bg-sky-900/20 text-sky-600 dark:text-sky-400 hover:bg-sky-100 dark:hover:bg-sky-900/30"
              title="Duplicar">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
            </button>
            <button v-if="row.estado !== 'cancelado'" @click="confirmarEliminacion(row.id)"
              class="w-9 h-9 flex items-center justify-center rounded-xl transition-all duration-200 bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900/30"
              title="Cancelar pedido">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </template>
        <template #pagination>
          <div v-if="paginationData.last_page > 1" class="flex justify-between items-center">
            <div class="text-sm text-slate-500">
              Mostrando {{ paginationData.from }} - {{ paginationData.to }} de {{ paginationData.total }}
            </div>
            <div class="flex gap-1.5">
              <button @click="handlePageChange(1)" :disabled="paginationData.current_page === 1"
                class="px-3 py-1.5 text-sm rounded-lg transition-all duration-150 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 disabled:opacity-50">Primera</button>
              <Link v-for="(link, i) in (props.pedidos.links || [])" :key="i"
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
      <ModalPedido
        v-if="modalMode === 'details'"
        :show="showModal"
        :selected="fila || {}"
        :auditoria="auditoriaForModal"
        @close="cerrarModal"
        @editar="editarFila"
        @duplicar="duplicarPedido"
        @enviar-a-venta="enviarAVenta"
        @cancelar="eliminarPedido"
        @eliminar="eliminarPedido"
        @imprimir="imprimirFila"
      />

      <!-- Modal de confirmación -->
      <Modal
        v-else
        :show="showModal"
        :mode="modalMode"
        tipo="pedidos"
        :selected="fila || {}"
        :auditoria="auditoriaForModal"
        @close="cerrarModal"
        @confirm-delete="eliminarPedido"
        @confirm-duplicate="confirmarDuplicarPedido"
        @confirm-email="confirmarEnvioEmail"
        @cerrar-pedido="cerrarPedido"
        @imprimir="imprimirFila"
        @editar="editarFila"
        @enviar-a-venta="enviarAVenta"
      />

      <!-- Loading overlay -->
      <div v-if="loading" class="fixed inset-0 bg-black/50 dark:bg-black/50 flex items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-800 p-6 rounded-xl shadow-xl">
          <div class="flex items-center space-x-3">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-brand-500"></div>
            <span class="text-slate-700 dark:text-slate-200">Procesando...</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
