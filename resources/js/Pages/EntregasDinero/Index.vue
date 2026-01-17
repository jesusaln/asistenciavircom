<!-- /resources/js/Pages/EntregasDinero/Index.vue -->
<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

import EntregasDineroHeader from '@/Components/IndexComponents/EntregasDineroHeader.vue'

defineOptions({ layout: AppLayout })

// Notificaciones
const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false },
    { type: 'warning', background: '#f59e0b', icon: false }
  ]
})

// Props
const props = defineProps({
  entregas: { type: Object, default: () => ({}) },
  registrosAutomaticos: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  usuarios: { type: Array, default: () => [] }
})

// Estado
const showModal = ref(false)
const modalMode = ref('details')
const selectedEntrega = ref(null)
const selectedId = ref(null)
const page = usePage()
const currentUser = computed(() => page.props.auth?.user)

// Modal de monto recibido para registros autom�ticos
const showMontoModal = ref(false)
const selectedRegistro = ref(null)
const montoRecibido = ref('')
const metodoPagoEntrega = ref('')
const notasRecibido = ref('')

// Modal de recepci�n para entregas manuales
const showRecibirModal = ref(false)
const entregaParaRecibir = ref(null)
const metodoRecibo = ref('')
const notasRecibo = ref('')
const cuentaBancariaId = ref('')
const cuentasBancarias = ref([])

// Filtros
const filtroEstado = ref(props.filters?.estado ?? '')
const filtroUserId = ref(props.filters?.user_id ?? '')

// Nuevas variables para el header
const searchTerm = ref('')
const sortBy = ref('fecha_entrega-desc')

// Helpers
const formatNumber = (num) => new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num)

const formatearFecha = (date) => {
  if (!date) return 'Fecha no disponible'
  try {
    const d = new Date(date)
    return d.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' })
  } catch {
    return 'Fecha inv�lida'
  }
}

const obtenerClasesEstado = (estado) => {
  const clases = {
    'pendiente': 'bg-yellow-100 text-yellow-700',
    'recibido': 'bg-green-100 text-green-700',
    'cancelado': 'bg-gray-100 text-gray-600'
  }
  return clases[estado] || 'bg-gray-100 text-gray-700'
}

const obtenerLabelEstado = (estado) => {
  const labels = {
    'pendiente': 'Pendiente',
    'recibido': 'Recibido',
    'cancelado': 'Cancelado'
  }
  return labels[estado] || 'Pendiente'
}

const obtenerEstadoEntrega = (registro) => {
  // Para registros autom�ticos (cobranzas y ventas) - segunda tabla
  if (registro.tipo_origen && !registro.estado) {
    const saldoPendiente = registro.saldo_pendiente || registro.total
    const yaEntregado = registro.ya_entregado || 0
    const total = registro.total

    if (yaEntregado === 0) {
      return { label: 'Sin Entregar', clase: 'bg-red-100 text-red-700' }
    } else if (saldoPendiente > 0) {
      return { label: 'Entrega Parcial', clase: 'bg-orange-100 text-orange-700' }
    } else {
      return { label: 'Completado', clase: 'bg-green-100 text-green-700' }
    }
  }

  // Para entregas autom�ticas ya creadas (primera tabla) que est�n "recibido"
  if (registro.tipo_origen && registro.estado === 'recibido') {
    return { label: 'Completado', clase: 'bg-green-100 text-green-700' }
  }

  // Para entregas manuales, no mostrar estado de entrega
  return null
}

// Handlers
function handleEstadoChange(newEstado) {
  filtroEstado.value = newEstado
  router.get(route('entregas-dinero.index'), {
    search: searchTerm.value,
    estado: newEstado,
    user_id: filtroUserId.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

function handleUserChange(newUserId) {
  filtroUserId.value = newUserId
  router.get(route('entregas-dinero.index'), {
    search: searchTerm.value,
    estado: filtroEstado.value,
    user_id: newUserId,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

// Nuevas funciones para el header moderno
const crearNuevaEntrega = () => {
  router.visit(route('entregas-dinero.create'))
}

const limpiarFiltros = () => {
  searchTerm.value = ''
  sortBy.value = 'fecha_entrega-desc'
  filtroEstado.value = ''
  filtroUserId.value = ''
  router.visit(route('entregas-dinero.index'))
  notyf.success('Filtros limpiados correctamente')
}

function handleSearchChange(newSearch) {
  searchTerm.value = newSearch
  router.get(route('entregas-dinero.index'), {
    search: newSearch,
    estado: filtroEstado.value,
    user_id: filtroUserId.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

function handleSortChange(newSort) {
  sortBy.value = newSort
  router.get(route('entregas-dinero.index'), {
    search: searchTerm.value,
    estado: filtroEstado.value,
    user_id: filtroUserId.value,
    sort_by: newSort.split('-')[0],
    sort_direction: newSort.split('-')[1] || 'desc',
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const verDetalles = (entrega) => {
  selectedEntrega.value = entrega
  modalMode.value = 'details'
  showModal.value = true
}

const editarEntrega = (id) => {
  router.visit(route('entregas-dinero.edit', id))
}

const confirmarEliminacion = (id) => {
  selectedId.value = id
  modalMode.value = 'confirm'
  showModal.value = true
}

const eliminarEntrega = () => {
  router.delete(route('entregas-dinero.destroy', selectedId.value), {
    preserveScroll: true,
    onSuccess: () => {
      notyf.success('Entrega eliminada correctamente')
      showModal.value = false
      selectedId.value = null
      router.reload()
    },
    onError: (errors) => {
      notyf.error('No se pudo eliminar la entrega')
    }
  })
}

// Revertir una entrega de recibido a pendiente (solo admin)
const revertirAPendiente = (id) => {
  if (!confirm('�Est�s seguro de que deseas revertir esta entrega a estado pendiente? El dinero ya no estar� marcado como recibido.')) {
    return
  }
  router.post(route('entregas-dinero.revertir-pendiente', id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      notyf.success('Entrega revertida a pendiente correctamente')
      router.reload()
    },
    onError: (errors) => {
      notyf.error('No se pudo revertir la entrega')
    }
  })
}

const abrirModalRecibir = async (entrega) => {
  entregaParaRecibir.value = entrega
  const metodo = entrega?.registro_original?.metodo_pago || entrega?.metodo_pago || 'efectivo'
  metodoRecibo.value = metodo
  notasRecibo.value = ''
  cuentaBancariaId.value = ''
  showModal.value = false
  showRecibirModal.value = true
  
  // Cargar cuentas bancarias
  try {
    const response = await fetch(route('api.cuentas-bancarias.activas'))
    cuentasBancarias.value = await response.json()
  } catch (error) {
    console.error('Error cargando cuentas:', error)
  }
}

const cerrarRecibirModal = () => {
  showRecibirModal.value = false
  entregaParaRecibir.value = null
  metodoRecibo.value = ''
  notasRecibo.value = ''
  cuentaBancariaId.value = ''
}

const marcarAutomaticoRecibido = (registro) => {
  selectedRegistro.value = registro
  montoRecibido.value = (registro.saldo_pendiente || registro.total).toString()
  metodoPagoEntrega.value = registro?.registro_original?.metodo_pago || registro?.metodo_pago || 'efectivo'
  notasRecibido.value = ''
  showMontoModal.value = true
}

const confirmarMontoRecibido = () => {
  if (!selectedRegistro.value) return

  const monto = parseFloat(montoRecibido.value)

  if (!montoRecibido.value || isNaN(monto) || monto <= 0) {
    notyf.error('Debe ingresar un monto valido mayor a cero')
    return
  }

  const saldoPendiente = selectedRegistro.value.saldo_pendiente || selectedRegistro.value.total
  if (monto > saldoPendiente) {
    notyf.error(`El monto recibido no puede ser mayor al saldo pendiente de $${formatNumber(saldoPendiente)}`)
    return
  }

  const metodo = metodoPagoEntrega.value || selectedRegistro.value?.registro_original?.metodo_pago || selectedRegistro.value?.metodo_pago || 'efectivo'

  router.post(route('entregas-dinero.marcar-automatico', {
    tipo_origen: selectedRegistro.value.tipo_origen,
    id_origen: selectedRegistro.value.id_origen
  }), {
    monto_recibido: monto,
    metodo_pago_entrega: metodo,
    notas_recibido: notasRecibido.value
  }, {
    onSuccess: () => {
      notyf.success('Monto registrado correctamente')
      cerrarMontoModal()
      router.reload()
    },
    onError: () => {
      notyf.error('Error al registrar el monto')
    }
  })
}

const cerrarMontoModal = () => {
  showMontoModal.value = false
  selectedRegistro.value = null
  montoRecibido.value = ''
  metodoPagoEntrega.value = ''
  notasRecibido.value = ''
}

const getMetodoPagoLabel = (registro) => {
  // Para registros autom�ticos necesitamos obtener el m�todo de pago del registro original
  if (registro.registro_original && registro.registro_original.metodo_pago) {
    const metodos = {
      'efectivo': 'Efectivo',
      'transferencia': 'Transferencia',
      'cheque': 'Cheque',
      'tarjeta': 'Tarjeta',
      'otros': 'Otros'
    }
    return metodos[registro.registro_original.metodo_pago] || registro.registro_original.metodo_pago
  }
  // Si no tiene registro_original pero tiene metodo_pago directamente
  if (registro.metodo_pago) {
    const metodos = {
      'efectivo': 'Efectivo',
      'transferencia': 'Transferencia',
      'cheque': 'Cheque',
      'tarjeta': 'Tarjeta',
      'otros': 'Otros'
    }
    return metodos[registro.metodo_pago] || registro.metodo_pago
  }
  return 'No especificado'
}

const getMetodoPagoClass = (registro) => {
  const metodoPago = registro.registro_original?.metodo_pago || registro.metodo_pago
  if (metodoPago) {
    const clases = {
      'efectivo': 'bg-green-100 text-green-800',
      'transferencia': 'bg-blue-100 text-blue-800',
      'cheque': 'bg-purple-100 text-purple-800',
      'tarjeta': 'bg-orange-100 text-orange-800',
      'otros': 'bg-gray-100 text-gray-800'
    }
    return clases[metodoPago] || 'bg-gray-100 text-gray-800'
  }
  return 'bg-gray-100 text-gray-800'
}

const tieneSaldoPendiente = (registro) => {
  const pendiente = (registro?.saldo_pendiente ?? (registro?.total - (registro?.ya_entregado || 0)))
  return pendiente > 0.01
}

const getMetodoReciboLabel = (value) => {
  const metodos = {
    efectivo: 'Efectivo',
    transferencia: 'Transferencia',
    cheque: 'Cheque',
    tarjeta: 'Tarjeta',
    mixto: 'Mixto',
    otros: 'Otros'
  }
  return metodos[value] || 'No especificado'
}

const confirmarRecepcionEntrega = () => {
  if (!entregaParaRecibir.value) return
  if (!cuentaBancariaId.value) {
    notyf.error('Debes seleccionar una cuenta bancaria')
    return
  }

  const montos = []
  if (entregaParaRecibir.value.monto_efectivo > 0) montos.push(`Efectivo $${formatNumber(entregaParaRecibir.value.monto_efectivo)}`)
  if (entregaParaRecibir.value.monto_transferencia > 0) montos.push(`Transferencia $${formatNumber(entregaParaRecibir.value.monto_transferencia)}`)
  if (entregaParaRecibir.value.monto_cheques > 0) montos.push(`Cheques $${formatNumber(entregaParaRecibir.value.monto_cheques)}`)
  if (entregaParaRecibir.value.monto_tarjetas > 0) montos.push(`Tarjetas $${formatNumber(entregaParaRecibir.value.monto_tarjetas)}`)

  const notasDetalladas = [
    `Metodo de recepcion: ${getMetodoReciboLabel(metodoRecibo.value)}`,
    montos.length ? `Detalle de montos: ${montos.join(' | ')}` : null,
    notasRecibo.value ? `Notas: ${notasRecibo.value}` : null,
    currentUser.value ? `Recibido por: ${currentUser.value?.name || 'Usuario'}` : null
  ].filter(Boolean).join(' - ')

  router.post(route('entregas-dinero.marcar-recibido', entregaParaRecibir.value.id), {
    notas_recibido: notasDetalladas,
    cuenta_bancaria_id: cuentaBancariaId.value || null
  }, {
    onSuccess: () => {
      notyf.success('Entrega marcada como recibida')
      cerrarRecibirModal()
      router.reload()
    },
    onError: () => {
      notyf.error('Error al marcar como recibida')
    }
  })
}

// Paginaci�n
const paginationData = computed(() => {
  const p = props.entregas || {}
  return {
    currentPage: p.current_page ?? 1,
    lastPage: p.last_page ?? 1,
    perPage: p.per_page ?? 10,
    from: p.from ?? 0,
    to: p.to ?? 0,
    total: p.total ?? 0,
    prevPageUrl: p.prev_page_url ?? null,
    nextPageUrl: p.next_page_url ?? null,
    links: p.links ?? []
  }
})

const handlePerPageChange = (newPerPage) => {
  router.get(route('entregas-dinero.index'), {
    search: searchTerm.value,
    estado: filtroEstado.value,
    user_id: filtroUserId.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    per_page: newPerPage,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const handlePageChange = (newPage) => {
  router.get(route('entregas-dinero.index'), {
    search: searchTerm.value,
    estado: filtroEstado.value,
    user_id: filtroUserId.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    page: newPage
  }, { preserveState: true, preserveScroll: true })
}
</script>

<template>
  <Head title="Entregas de Dinero" />
  <div class="entregas-dinero-index min-h-screen bg-gray-50">
    <div class="w-full px-6 py-8">
      <!-- Header espec�fico de entregas de dinero -->
      <EntregasDineroHeader
        :total="stats.total || 0"
        :total-pendientes="stats.total_pendientes || 0"
        :total-recibidas="stats.total_recibidas || 0"
        :total-efectivo="stats.total_efectivo || 0"
        :total-otros="stats.total_otros || 0"
        :usuarios="usuarios || []"
        v-model:search-term="searchTerm"
        v-model:sort-by="sortBy"
        v-model:filtro-estado="filtroEstado"
        v-model:filtro-usuario="filtroUserId"
        @crear-nueva="crearNuevaEntrega"
        @search-change="handleSearchChange"
        @filtro-estado-change="handleEstadoChange"
        @filtro-usuario-change="handleUserChange"
        @sort-change="handleSortChange"
        @limpiar-filtros="limpiarFiltros"
      />

      <!-- Tabla -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Entregado por</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Recibido por</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Fecha Entrega</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Folio Venta</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Total</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Estado</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Estado Entrega</th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="entrega in entregas.data" :key="entrega.id" class="hover:bg-gray-50 transition-colors duration-150">
                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-gray-900">{{ entrega.usuario?.name || 'Usuario' }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-900">{{ entrega.recibidoPor?.name || (entrega.estado === 'recibido' ? $page.props.auth?.user?.name : '-') }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-900">{{ formatearFecha(entrega.fecha_entrega) }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-blue-600">
                    {{ entrega.venta_numero || '-' }}
                  </div>
                  <div v-if="entrega.venta_cliente" class="text-xs text-gray-500">
                    {{ entrega.venta_cliente }}
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm font-semibold text-gray-900">${{ formatNumber(entrega.total) }}</div>
                  <div class="text-xs text-gray-500">
                    E: ${{ formatNumber(entrega.monto_efectivo) }} |
                    C: ${{ formatNumber(entrega.monto_cheques) }} |
                    T: ${{ formatNumber(entrega.monto_tarjetas) }}
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span :class="obtenerClasesEstado(entrega.estado)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ obtenerLabelEstado(entrega.estado) }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <span v-if="obtenerEstadoEntrega(entrega)" :class="obtenerEstadoEntrega(entrega).clase" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ obtenerEstadoEntrega(entrega).label }}
                  </span>
                  <span v-else class="text-gray-400 text-xs">-</span>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end space-x-1">
                    <button @click="verDetalles(entrega)" class="w-8 h-8 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors duration-150" title="Ver detalles">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>
                    <button @click="editarEntrega(entrega.id)" class="w-8 h-8 bg-amber-50 text-amber-600 rounded-lg hover:bg-amber-100 transition-colors duration-150" title="Editar">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button
                      v-if="entrega.estado === 'pendiente'"
                      @click="abrirModalRecibir(entrega)"
                      class="w-8 h-8 bg-green-50 text-green-600 rounded-lg hover:bg-green-100 transition-colors duration-150"
                      title="Marcar como recibida"
                    >
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </button>
                    <!-- Bot�n Revertir a Pendiente (solo para estado recibido) -->
                    <button
                      v-if="entrega.estado === 'recibido'"
                      @click="revertirAPendiente(entrega.id)"
                      class="w-8 h-8 bg-orange-50 text-orange-600 rounded-lg hover:bg-orange-100 transition-colors duration-150"
                      title="Revertir a Pendiente"
                    >
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="entregas.data?.length === 0">
                <td colspan="8" class="px-6 py-16 text-center">
                  <div class="flex flex-col items-center space-y-4">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                      <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                      </svg>
                    </div>
                    <div class="space-y-1">
                      <p class="text-gray-700 font-medium">No hay entregas registradas</p>
                      <p class="text-sm text-gray-500">Las entregas aparecer�n aqu� cuando se registren</p>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginaci�n -->
        <div v-if="paginationData.lastPage > 1" class="bg-white border-t border-gray-200 px-4 py-3 sm:px-6">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4">
              <p class="text-sm text-gray-700">
                Mostrando {{ paginationData.from }} - {{ paginationData.to }} de {{ paginationData.total }} resultados
              </p>
              <select
                :value="paginationData.perPage"
                @change="handlePerPageChange(parseInt($event.target.value))"
                class="border border-gray-300 rounded-md text-sm py-1 px-2 bg-white"
              >
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
              </select>
            </div>

            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
              <button
                v-if="paginationData.prevPageUrl"
                @click="handlePageChange(paginationData.currentPage - 1)"
                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
              >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </button>

              <span v-else class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </span>

              <button
                v-for="page in [paginationData.currentPage - 1, paginationData.currentPage, paginationData.currentPage + 1].filter(p => p > 0 && p <= paginationData.lastPage)"
                :key="page"
                @click="handlePageChange(page)"
                :class="page === paginationData.currentPage ? 'bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'"
                class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
              >
                {{ page }}
              </button>

              <button
                v-if="paginationData.nextPageUrl"
                @click="handlePageChange(paginationData.currentPage + 1)"
                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
              >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
              </button>

              <span v-else class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
              </span>
            </nav>
          </div>
        </div>
      </div>

      <!-- Registros Autom�ticos (Cobranzas y Ventas) -->
      <div v-if="registrosAutomaticos.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mt-6">
        <div class="bg-blue-50 px-6 py-4 border-b border-blue-200">
          <h3 class="text-lg font-semibold text-blue-900">Cobranzas y Ventas por Entregar</h3>
          <p class="text-sm text-blue-700">Registros que has cobrado/vendido y puedes marcar como entregados</p>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tipo</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Usuario</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Fecha</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Concepto</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Cliente</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Monto</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Estado Entrega</th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Acci�n</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="registro in registrosAutomaticos" :key="registro.id" class="hover:bg-gray-50 transition-colors duration-150">
                <td class="px-6 py-4">
                  <span :class="registro.tipo === 'cobranza' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ registro.tipo === 'cobranza' ? 'Cobranza' : 'Venta' }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-900">{{ registro.usuario?.name || 'Usuario' }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-900">{{ formatearFecha(registro.fecha_entrega) }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-gray-900">{{ registro.concepto }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-700">{{ registro.cliente }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm font-semibold text-gray-900">${{ formatNumber(registro.saldo_pendiente || registro.total) }}</div>
                  <div v-if="tieneSaldoPendiente(registro) && registro.ya_entregado > 0" class="text-xs text-blue-600">
                    Ya entregado: ${{ formatNumber(registro.ya_entregado) }}
                  </div>
                  <div v-if="registro.saldo_pendiente && registro.saldo_pendiente < registro.total" class="text-xs text-orange-600">
                    Total: ${{ formatNumber(registro.total) }}
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span v-if="obtenerEstadoEntrega(registro)" :class="obtenerEstadoEntrega(registro).clase" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ obtenerEstadoEntrega(registro).label }}
                  </span>
                  <span v-else class="text-gray-400 text-xs">-</span>
                </td>
                <td class="px-6 py-4 text-right">
                  <button
                          v-if="tieneSaldoPendiente(registro)"
                          @click="marcarAutomaticoRecibido(registro)"
                          class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Entregar
                  </button>
                  <span v-else class="text-xs text-gray-500">Sin saldo pendiente</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal de detalles / confirmaci�n -->
      <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showModal = false">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
          <!-- Header del modal -->
          <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">
              {{ modalMode === 'details' ? 'Detalles de la Entrega' : 'Confirmar Eliminaci�n' }}
            </h3>
            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="p-6">
            <div v-if="modalMode === 'details' && selectedEntrega">
              <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="space-y-3">
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Usuario</label>
                      <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">{{ selectedEntrega.usuario?.name || 'Usuario' }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Fecha de Entrega</label>
                      <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">{{ formatearFecha(selectedEntrega.fecha_entrega) }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Estado</label>
                      <span :class="obtenerClasesEstado(selectedEntrega.estado)" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium mt-1">
                        {{ obtenerLabelEstado(selectedEntrega.estado) }}
                      </span>
                    </div>
                  </div>
                  <div class="space-y-3">
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Monto Efectivo</label>
                      <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">${{ formatNumber(selectedEntrega.monto_efectivo) }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Monto Cheques</label>
                      <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">${{ formatNumber(selectedEntrega.monto_cheques) }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Monto Tarjetas</label>
                      <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">${{ formatNumber(selectedEntrega.monto_tarjetas) }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Total</label>
                      <p class="mt-1 text-sm font-bold text-gray-900 bg-gray-50 px-3 py-2 rounded-md">${{ formatNumber(selectedEntrega.total) }}</p>
                    </div>
                  </div>
                </div>
                <div v-if="selectedEntrega.notas">
                  <label class="block text-sm font-medium text-gray-700">Notas</label>
                  <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">{{ selectedEntrega.notas }}</p>
                </div>
                <div v-if="selectedEntrega.fecha_recibido">
                  <label class="block text-sm font-medium text-gray-700">Fecha de Recepci�n</label>
                  <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">{{ formatearFecha(selectedEntrega.fecha_recibido) }}</p>
                </div>
                <div v-if="selectedEntrega.recibido_por">
                  <label class="block text-sm font-medium text-gray-700">Recibido Por</label>
                  <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">{{ selectedEntrega.recibido_por_usuario?.name || 'Desconocido' }}</p>
                </div>
                <div v-if="selectedEntrega.notas_recibido">
                  <label class="block text-sm font-medium text-gray-700">Notas de Recepci�n</label>
                  <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">{{ selectedEntrega.notas_recibido }}</p>
                </div>
              </div>
            </div>

            <div v-if="modalMode === 'confirm'">
              <div class="text-center">
                <div class="w-12 h-12 mx-auto bg-red-100 rounded-full flex items-center justify-center mb-4">
                  <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                  </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">�Eliminar Entrega?</h3>
                <p class="text-sm text-gray-500 mb-4">
                  �Est�s seguro de que deseas eliminar esta entrega?
                  Esta acci�n no se puede deshacer.
                </p>
              </div>
            </div>
          </div>

          <!-- Footer del modal -->
          <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
            <button @click="showModal = false" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
              {{ modalMode === 'details' ? 'Cerrar' : 'Cancelar' }}
            </button>
            <div v-if="modalMode === 'details'">
              <button
                v-if="selectedEntrega.estado === 'pendiente'"
                @click="abrirModalRecibir(selectedEntrega)"
                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors mr-2"
              >
                Marcar Recibida
              </button>
              <button @click="editarEntrega(selectedEntrega.id)" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors">
                Editar
              </button>
            </div>
            <button v-if="modalMode === 'confirm'" @click="eliminarEntrega" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
              Eliminar
            </button>
      </div>
    </div>
  </div>

  <!-- Modal para marcar como recibida (entregas manuales) -->
  <div v-if="showRecibirModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="cerrarRecibirModal">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between p-6 border-b border-gray-200">
        <div>
          <h3 class="text-lg font-medium text-gray-900">Confirmar recepcion</h3>
          <p class="text-sm text-gray-500">Registra como se recibio la entrega y deja una nota.</p>
        </div>
        <button @click="cerrarRecibirModal" class="text-gray-400 hover:text-gray-600 transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="p-6 space-y-5" v-if="entregaParaRecibir">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <p class="text-sm text-gray-500">Entregado por</p>
            <p class="text-sm font-semibold text-gray-900 bg-gray-50 px-3 py-2 rounded-md">{{ entregaParaRecibir.usuario?.name }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Recibe</p>
            <p class="text-sm font-semibold text-gray-900 bg-gray-50 px-3 py-2 rounded-md">{{ currentUser?.name }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Fecha de entrega</p>
            <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">{{ formatearFecha(entregaParaRecibir.fecha_entrega) }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Total</p>
            <p class="text-lg font-bold text-gray-900 bg-gray-50 px-3 py-2 rounded-md">${{ formatNumber(entregaParaRecibir.total) }}</p>
          </div>
        </div>

        <div class="bg-gray-50 p-4 rounded-lg space-y-2">
          <p class="text-sm font-medium text-gray-700">Detalle de montos registrados</p>
          <div class="flex flex-wrap gap-2">
            <span v-if="entregaParaRecibir.monto_efectivo > 0" class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">Efectivo ${{ formatNumber(entregaParaRecibir.monto_efectivo) }}</span>
            <span v-if="entregaParaRecibir.monto_transferencia > 0" class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">Transferencia ${{ formatNumber(entregaParaRecibir.monto_transferencia) }}</span>
            <span v-if="entregaParaRecibir.monto_cheques > 0" class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">Cheques ${{ formatNumber(entregaParaRecibir.monto_cheques) }}</span>
            <span v-if="entregaParaRecibir.monto_tarjetas > 0" class="px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-xs font-medium">Tarjetas ${{ formatNumber(entregaParaRecibir.monto_tarjetas) }}</span>
            <span v-if="entregaParaRecibir.monto_efectivo <= 0 && entregaParaRecibir.monto_transferencia <= 0 && entregaParaRecibir.monto_cheques <= 0 && entregaParaRecibir.monto_tarjetas <= 0" class="text-xs text-gray-500">Sin detalle de montos</span>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Metodo de recepcion *</label>
          <select
            v-model="metodoRecibo"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
          >
            <option value="">Seleccionar metodo</option>
            <option value="efectivo">Efectivo</option>
            <option value="transferencia">Transferencia</option>
            <option value="cheque">Cheque</option>
            <option value="tarjeta">Tarjeta</option>
            <option value="mixto">Mixto</option>
            <option value="otros">Otros</option>
          </select>
          <p class="text-xs text-gray-500 mt-1">Como se recibe la entrega fisicamente.</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Notas de recepcion</label>
          <textarea
            v-model="notasRecibo"
            rows="3"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
            placeholder="Detalles adicionales, referencia de transferencia, folio, etc."
          ></textarea>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Depositar a cuenta bancaria <span class="text-red-500">*</span></label>
          <select
            v-model="cuentaBancariaId"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
          >
            <option value="">-- Seleccionar cuenta (obligatorio) --</option>
            <option v-for="cuenta in cuentasBancarias" :key="cuenta.id" :value="cuenta.id">
              {{ cuenta.nombre }} - {{ cuenta.banco }} (Saldo: ${{ formatNumber(cuenta.saldo_actual) }})
            </option>
          </select>
          <p class="text-xs text-gray-500 mt-1">Selecciona la cuenta bancaria donde se depositar� este dinero. El saldo se actualizar� autom�ticamente.</p>
        </div>
      </div>

      <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
        <button @click="cerrarRecibirModal" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
          Cancelar
        </button>
        <button
          @click="confirmarRecepcionEntrega"
          :disabled="!entregaParaRecibir || !cuentaBancariaId"
          class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          Confirmar recepcion
        </button>
      </div>
    </div>
  </div>

  <!-- Modal de Monto Recibido -->
  <div v-if="showMontoModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="cerrarMontoModal">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
      <!-- Header del modal -->
      <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Registrar Monto Recibido</h3>
            <button @click="cerrarMontoModal" class="text-gray-400 hover:text-gray-600 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="p-6">
            <div v-if="selectedRegistro" class="space-y-4">
              <!-- Informaci�n del registro -->
              <div class="bg-gray-50 p-4 rounded-lg space-y-3">
                <div class="flex justify-between items-center">
                  <span class="text-sm font-medium text-gray-700">Tipo:</span>
                  <span class="text-sm text-gray-900">
                    <span :class="selectedRegistro.tipo === 'cobranza' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'"
                          class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium">
                      {{ selectedRegistro.tipo === 'cobranza' ? 'Cobranza' : 'Venta' }}
                    </span>
                  </span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm font-medium text-gray-700">Concepto:</span>
                  <span class="text-sm text-gray-900">{{ selectedRegistro.concepto }}</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm font-medium text-gray-700">Cliente:</span>
                  <span class="text-sm text-gray-900">{{ selectedRegistro.cliente }}</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm font-medium text-gray-700">M�todo de Pago Original:</span>
                  <span class="text-sm font-semibold text-gray-900">
                    <span :class="getMetodoPagoClass(selectedRegistro)">
                      {{ getMetodoPagoLabel(selectedRegistro) }}
                    </span>
                  </span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm font-medium text-gray-700">Saldo Pendiente:</span>
                  <span class="text-lg font-bold text-gray-900">${{ formatNumber(selectedRegistro.saldo_pendiente || selectedRegistro.total) }}</span>
                </div>
                <div v-if="tieneSaldoPendiente(selectedRegistro) && selectedRegistro.ya_entregado > 0" class="flex justify-between items-center">
                  <span class="text-sm font-medium text-gray-600">Ya entregado:</span>
                  <span class="text-sm text-blue-600">${{ formatNumber(selectedRegistro.ya_entregado) }}</span>
                </div>
                <div class="flex justify-between items-center pt-2 border-t border-gray-200">
                  <span class="text-sm font-medium text-gray-700">Total Original:</span>
                  <span class="text-sm text-gray-900">${{ formatNumber(selectedRegistro.total) }}</span>
                </div>
              </div>

              <!-- M�todo de Pago en Entrega -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">M�todo de Pago en Entrega</label>
                <select
                  v-model="metodoPagoEntrega"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                  <option value="">Seleccionar m�todo de pago</option>
                  <option value="efectivo">Efectivo</option>
                  <option value="transferencia">Transferencia</option>
                  <option value="cheque">Cheque</option>
                  <option value="tarjeta">Tarjeta</option>
                  <option value="otros">Otros</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">
                  Especifica c�mo se entrega f�sicamente el dinero
                </p>
              </div>

              <!-- Monto recibido -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Monto Recibido *</label>
                <input
                  v-model="montoRecibido"
                  type="number"
                  step="0.01"
                  min="0.01"
                  :max="selectedRegistro.saldo_pendiente || selectedRegistro.total"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="0.00"
                />
                <p class="text-xs text-gray-500 mt-1">
                  M�ximo: ${{ formatNumber(selectedRegistro.saldo_pendiente || selectedRegistro.total) }}
                </p>
              </div>

              <!-- Notas -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Notas (opcional)</label>
                <textarea
                  v-model="notasRecibido"
                  rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="Agregar notas sobre la entrega..."
                ></textarea>
              </div>
            </div>
          </div>

          <!-- Footer del modal -->
          <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
            <button @click="cerrarMontoModal" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
              Cancelar
            </button>
            <button
              @click="confirmarMontoRecibido"
              :disabled="!montoRecibido || isNaN(parseFloat(montoRecibido)) || parseFloat(montoRecibido) <= 0"
              class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              Registrar Monto
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.entregas-dinero-index {
  min-height: 100vh;
  background-color: #f9fafb;
}
</style>














