<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import axios from 'axios'
import 'notyf/notyf.min.css'
import Swal from '@/Utils/Swal'
import { useCompanyColors } from '@/Composables/useCompanyColors'
import EntregasDineroHeader from '@/Components/IndexComponents/EntregasDineroHeader.vue'

defineOptions({ layout: AppLayout })

// Colores de empresa y modo oscuro
const { cssVars, primaryButtonStyle, colors } = useCompanyColors()
const isDark = ref(false)
let observer = null

onMounted(() => {
  isDark.value = document.documentElement.classList.contains('dark')
  observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      if (mutation.attributeName === 'class') {
        isDark.value = document.documentElement.classList.contains('dark')
      }
    })
  })
  observer.observe(document.documentElement, { attributes: true })
})

onBeforeUnmount(() => {
  if (observer) observer.disconnect()
})

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

// Modal de monto recibido para registros automáticos
const showMontoModal = ref(false)
const selectedRegistro = ref(null)
const montoRecibido = ref('')
const metodoPagoEntrega = ref('')
const notasRecibido = ref('')

// Modal de recepción para entregas manuales
const showRecibirModal = ref(false)
const entregaParaRecibir = ref(null)
const metodoRecibo = ref('')
const notasRecibo = ref('')
const cuentaBancariaId = ref('')
const cuentasBancarias = ref([])

// Filtros
const searchTerm = ref(props.filters?.search ?? '')
const filtroEstado = ref(props.filters?.estado ?? '')
const filtroUserId = ref(props.filters?.user_id ?? '')
const sortBy = ref((props.filters?.sort_by ?? 'fecha_entrega') + '-' + (props.filters?.sort_direction ?? 'desc'))

// Helpers
const formatNumber = (num) => new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num)

const formatearFecha = (date) => {
  if (!date) return '—'
  try {
    const d = new Date(date)
    return d.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' })
  } catch {
    return '—'
  }
}

const formatearHora = (date) => {
  if (!date) return ''
  try {
    const d = new Date(date)
    return d.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })
  } catch {
    return ''
  }
}

const obtenerClasesEstado = (estado) => {
  const clases = {
    'pendiente': 'bg-brand-50 dark:bg-brand-900/20/40 text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-amber-300',
    'recibido': 'bg-emerald-100 dark:bg-slate-800/50 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-emerald-300',
    'cancelado': 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400'
  }
  return clases[estado] || 'bg-slate-100 text-slate-700'
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
  if (registro.tipo_origen && !registro.estado) {
    const saldoPendiente = registro.saldo_pendiente || registro.total
    const yaEntregado = registro.ya_entregado || 0
    if (yaEntregado === 0) return { label: 'Sin Entregar', clase: 'bg-rose-50 dark:bg-rose-900/20/40 text-rose-800 dark:text-rose-200 dark:text-rose-200 dark:text-rose-300' }
    if (saldoPendiente > 0) return { label: 'Entrega Parcial', clase: 'bg-brand-100 dark:bg-brand-900/40 text-brand-700 dark:text-orange-300' }
    return { label: 'Completado', clase: 'bg-emerald-100 dark:bg-slate-800/50 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-emerald-300' }
  }
  if (registro.tipo_origen && registro.estado === 'recibido') return { label: 'Completado', clase: 'bg-emerald-100 dark:bg-slate-800/50 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-emerald-300' }
  return null
}

// Handlers con Inertia
const updateQuery = () => {
  router.get(route('entregas-dinero.index'), {
    search: searchTerm.value,
    estado: filtroEstado.value,
    user_id: filtroUserId.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const handleEstadoChange = (newEstado) => { filtroEstado.value = newEstado; updateQuery(); }
const handleUserChange = (newUserId) => { filtroUserId.value = newUserId; updateQuery(); }
const handleSearchChange = (newSearch) => { searchTerm.value = newSearch; updateQuery(); }
const handleSortChange = (newSort) => { sortBy.value = newSort; updateQuery(); }

const crearNuevaEntrega = () => router.visit(route('entregas-dinero.create'))
const limpiarFiltros = () => {
  searchTerm.value = ''
  sortBy.value = 'fecha_entrega-desc'
  filtroEstado.value = ''
  filtroUserId.value = ''
  router.visit(route('entregas-dinero.index'))
}

const verDetalles = (entrega) => { selectedEntrega.value = entrega; modalMode.value = 'details'; showModal.value = true; }
const editarEntrega = (id) => router.visit(route('entregas-dinero.edit', id))
const confirmarEliminacion = (id) => { selectedId.value = id; modalMode.value = 'confirm'; showModal.value = true; }

const eliminarEntrega = () => {
  router.delete(route('entregas-dinero.destroy', selectedId.value), {
    preserveScroll: true,
    onSuccess: () => {
      notyf.success('Entrega eliminada correctamente')
      showModal.value = false
      selectedId.value = null
    }
  })
}

const revertirAPendiente = async (id) => {
  const { isConfirmed } = await Swal.fire({ title: 'Confirmar', text: '¿Estás seguro de que deseas revertir esta entrega a estado pendiente?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí', cancelButtonText: 'No' })
  if (!isConfirmed) return
  router.post(route('entregas-dinero.revertir-pendiente', id), {}, {
    preserveScroll: true,
    onSuccess: () => notyf.success('Entrega revertida a pendiente correctamente')
  })
}

const abrirModalRecibir = async (entrega) => {
  entregaParaRecibir.value = entrega
  metodoRecibo.value = entrega?.registro_original?.metodo_pago || entrega?.metodo_pago || 'efectivo'
  showRecibirModal.value = true
  try {
    const { data } = await axios.get(route('cuentas-bancarias.activas'))
    cuentasBancarias.value = data
  } catch (error) {
    console.error('Error:', error)
  }
}

const confirmarRecepcionEntrega = () => {
  if (!cuentaBancariaId.value) return notyf.error('Selecciona una cuenta bancaria')
  
  router.post(route('entregas-dinero.marcar-recibido', entregaParaRecibir.value.id), {
    cuenta_bancaria_id: cuentaBancariaId.value,
    notas_recibido: notasRecibo.value
  }, {
    onSuccess: () => {
      notyf.success('Entrega marcada como recibida y registrada en banco')
      showRecibirModal.value = false
      entregaParaRecibir.value = null
      cuentaBancariaId.value = ''
      notasRecibo.value = ''
    },
    onError: (errors) => {
       Object.values(errors).forEach(err => notyf.error(err))
    }
  })
}

const marcarAutomaticoRecibido = async (registro) => {
  selectedRegistro.value = registro
  montoRecibido.value = (registro.saldo_pendiente || registro.total).toString()
  metodoPagoEntrega.value = registro?.registro_original?.metodo_pago || registro?.metodo_pago || 'efectivo'
  cuentaBancariaId.value = ''
  showMontoModal.value = true
  
  try {
    const { data } = await axios.get(route('cuentas-bancarias.activas'))
    cuentasBancarias.value = data
  } catch (error) {
    console.error('Error cargando cuentas:', error)
  }
}

const confirmarMontoRecibido = () => {
  const monto = parseFloat(montoRecibido.value)
  if (isNaN(monto) || monto <= 0) return notyf.error('Monto inválido')
  
  router.post(route('entregas-dinero.marcar-automatico', {
    tipo_origen: selectedRegistro.value.tipo_origen,
    id_origen: selectedRegistro.value.id_origen
  }), {
    monto_recibido: monto,
    metodo_pago_entrega: metodoPagoEntrega.value,
    notas_recibido: notasRecibido.value,
    cuenta_bancaria_id: cuentaBancariaId.value
  }, {
    onSuccess: () => {
      notyf.success('Monto registrado correctamente')
      showMontoModal.value = false
      router.reload()
    }
  })
}

// Lógica de Lotes
const selectedRegistros = ref([])
const showCrearLoteModal = ref(false)
const notasLote = ref('')
const totalSeleccionado = computed(() => selectedRegistros.value.reduce((total, r) => total + (r.saldo_pendiente || r.total), 0))
const isAllSelected = computed(() => {
  const pendientes = props.registrosAutomaticos.filter(r => (r.saldo_pendiente || r.total) > 0.01)
  return pendientes.length > 0 && selectedRegistros.value.length === pendientes.length
})

const toggleCheckbox = (registro) => {
  const idx = selectedRegistros.value.findIndex(r => r.id === registro.id)
  idx === -1 ? selectedRegistros.value.push(registro) : selectedRegistros.value.splice(idx, 1)
}

const toggleAll = (e) => {
  selectedRegistros.value = e.target.checked ? props.registrosAutomaticos.filter(r => (r.saldo_pendiente || r.total) > 0.01) : []
}

const confirmarLote = () => {
  router.post(route('entregas-dinero.lote'), {
    items: selectedRegistros.value.map(r => ({
      tipo_origen: r.tipo_origen,
      id_origen: r.id_origen,
      total: r.saldo_pendiente || r.total,
      metodo_pago: r.registro_original?.metodo_pago || r.metodo_pago || 'efectivo'
    })),
    notas: notasLote.value
  }, {
    onSuccess: () => {
      notyf.success('Lote generado correctamente')
      showCrearLoteModal.value = false
      selectedRegistros.value = []
    }
  })
}

</script>

<template>
  <Head title="Corte de Caja y Cobranza" />
  <div class="entregas-dinero-index min-h-screen bg-[var(--ui-surface)] transition-colors" :style="cssVars">
    <div class="w-full px-4 lg:px-8 py-8">
      <!-- Header Premium -->
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

      <!-- Tabla Principal (Entregas Manuales/Lotes) -->
      <div class="mt-8 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700 overflow-hidden transition-all">
        <div class="px-6 py-4 border-b border-slate-200/60 dark:border-slate-700/60" :style="{ background: isDark ? 'linear-gradient(135deg, #1f2937 0%, #111827 100%)' : `linear-gradient(135deg, ${colors.principal}15 0%, ${colors.secundario}10 100%)` }">
           <div class="flex items-center justify-between">
              <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: colors.principal }"></span>
                Entregas Realizadas
              </h2>
           </div>
        </div>
        
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-800/50">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Entregado por</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Fecha</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Referencia</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Monto Total</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado</th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
              <tr v-for="entrega in entregas.data" :key="entrega.id" @click="verDetalles(entrega)" class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors cursor-pointer group">
                <td class="px-6 py-4">
                  <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-blue-50 dark:bg-sky-900/20/40 text-blue-600 dark:text-blue-300 flex items-center justify-center font-bold mr-3 text-xs">
                      {{ (entrega.usuario?.name || 'U').charAt(0) }}
                    </div>
                    <div>
                      <div class="text-sm font-bold text-slate-900 dark:text-white">{{ entrega.usuario?.name }}</div>
                      <div class="text-[10px] text-slate-500 dark:text-slate-400 italic">Recibe: {{ entrega.recibido_por?.name || '—' }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4">
                   <div class="text-sm font-medium text-slate-900 dark:text-white">{{ formatearFecha(entrega.fecha_entrega) }}</div>
                   <div class="text-[10px] text-slate-500 dark:text-slate-400">{{ formatearHora(entrega.created_at) }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ entrega.venta_numero ? 'Venta #' + entrega.venta_numero : (entrega.es_lote ? 'Lote (' + entrega.conteo_items + ' items)' : 'Manual') }}</div>
                  <div class="text-xs text-slate-500 truncate max-w-[150px]">{{ entrega.venta_cliente || entrega.notas || 'Sin notas' }}</div>
                </td>
                <td class="px-6 py-4">
                   <div class="text-sm font-black text-slate-900 dark:text-white">${{ formatNumber(entrega.total) }}</div>
                   <div class="text-[9px] text-slate-500">E: {{ formatNumber(entrega.monto_efectivo) }} | O: {{ formatNumber(entrega.total - entrega.monto_efectivo) }}</div>
                </td>
                <td class="px-6 py-4 text-center">
                  <span :class="obtenerClasesEstado(entrega.estado)" class="inline-flex px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wide">
                    {{ obtenerLabelEstado(entrega.estado) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button v-if="entrega.estado === 'pendiente'" @click.stop="abrirModalRecibir(entrega)" class="p-2 bg-emerald-50 dark:bg-emerald-900/20 dark:bg-slate-800/30 text-emerald-600 dark:text-slate-400 rounded-xl hover:bg-emerald-100">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    </button>
                    <button @click.stop="editarEntrega(entrega.id)" class="p-2 bg-brand-50 dark:bg-brand-900/20 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400 rounded-xl hover:bg-amber-100">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!entregas.data?.length">
                <td colspan="6" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400 italic">No se encontraron entregas.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- SECCIÓN DE COBROS POR ENTREGAR (AUTOMÁTICOS) -->
      <div v-if="registrosAutomaticos.length > 0" class="mt-12 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-6 bg-sky-50 dark:bg-sky-900/20 dark:bg-sky-900/20 border-b border-blue-100 dark:border-blue-800/50 flex flex-wrap justify-between items-center gap-4">
          <div>
            <h3 class="text-xl font-black text-blue-900 dark:text-blue-300 uppercase tracking-wide">Ventas y Cobranzas por Formalizar</h3>
            <p class="text-xs text-sky-800 dark:text-sky-200 dark:text-blue-400 mt-1">Selecciona los registros para crear un lote de entrega masiva.</p>
          </div>
          <div v-if="selectedRegistros.length > 0" class="flex items-center gap-6 animate-fade-in">
             <div class="text-right">
               <div class="text-xs font-bold text-sky-800 dark:text-sky-200 dark:text-blue-300 uppercase tracking-wide">{{ selectedRegistros.length }} SELECCIONADOS</div>
               <div class="text-2xl font-black text-blue-900 dark:text-blue-100">${{ formatNumber(totalSeleccionado) }}</div>
             </div>
             <button @click="showCrearLoteModal = true" class="px-8 py-3 bg-blue-600 text-white rounded-2xl shadow-xl shadow-sky-500/40 hover:bg-blue-700 font-black transition-all transform hover:scale-105 active:scale-95">
               CREAR LOTE
             </button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-800/50">
              <tr>
                <th class="px-6 py-4 text-left w-10">
                  <input type="checkbox" :checked="isAllSelected" @change="toggleAll" class="w-4 h-4 rounded-xl border-slate-300 text-blue-600 focus:ring-brand-500 dark:bg-slate-700">
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Origen / Vendedor</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Fecha Pago</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Concepto / Cliente</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Monto Pendiente</th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Acción</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
              <tr v-for="registro in registrosAutomaticos" :key="registro.id_origen + '-' + registro.tipo_origen" class="hover:bg-slate-50/50 dark:hover:bg-blue-900/10 transition-colors">
                <td class="px-6 py-4">
                  <input type="checkbox" :checked="selectedRegistros.some(r => r.id_origen === registro.id_origen && r.tipo_origen === registro.tipo_origen)" @change="toggleCheckbox(registro)" class="w-4 h-4 rounded-xl border-slate-300 text-blue-600 focus:ring-brand-500 dark:bg-slate-700">
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-2">
                    <span :class="registro.tipo_origen === 'venta' ? 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-indigo-300' : 'bg-brand-100 text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:bg-brand-900/40 dark:text-amber-300'" class="px-2 py-0.5 rounded-xl text-[10px] font-black uppercase">
                      {{ registro.tipo_origen }}
                    </span>
                    <div class="text-sm font-bold text-slate-900 dark:text-white">{{ registro.vendedor || '—' }}</div>
                  </div>
                </td>
                <td class="px-6 py-4 text-center">
                  <div class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ formatearFecha(registro.fecha_pago) }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm font-bold text-slate-900 dark:text-white">{{ registro.concepto }}</div>
                  <div class="text-xs text-slate-500 dark:text-slate-400 italic">{{ registro.cliente }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm font-black text-sky-800 dark:text-sky-200 dark:text-blue-400">${{ formatNumber(registro.saldo_pendiente || registro.total) }}</div>
                  <div class="text-[10px] text-slate-500">{{ registro.metodo_pago }}</div>
                </td>
                <td class="px-6 py-4 text-right">
                  <button @click="marcarAutomaticoRecibido(registro)" class="px-4 py-1.5 bg-sky-50 dark:bg-sky-900/20 dark:bg-sky-900/30 text-blue-600 dark:text-blue-300 rounded-xl font-bold text-xs hover:bg-sky-100 transition-all">
                    RECIBIR
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modales -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4" @click.self="showModal = false">
      <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden border border-slate-300 dark:border-slate-600">
          <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-wide">
              {{ modalMode === 'details' ? 'Detalles de Entrega' : 'Confirmar Eliminación' }}
            </h3>
            <button @click="showModal = false" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-full transition-colors">
              <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          </div>
          
          <div class="p-8">
            <div v-if="modalMode === 'details' && selectedEntrega">
               <div class="grid grid-cols-2 gap-8 mb-8">
                  <div class="space-y-6">
                     <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-wide">Entregado por</label>
                        <div class="text-lg font-bold text-slate-900 dark:text-white">{{ selectedEntrega.usuario?.name }}</div>
                     </div>
                     <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-wide">Fecha</label>
                        <div class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ formatearFecha(selectedEntrega.fecha_entrega) }}</div>
                     </div>
                     <div v-if="selectedEntrega.venta_cliente">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-wide">Cliente</label>
                        <div class="text-sm font-bold text-slate-900 dark:text-white">{{ selectedEntrega.venta_cliente }}</div>
                     </div>
                     <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-wide">Estado</label>
                        <div>
                          <span :class="obtenerClasesEstado(selectedEntrega.estado)" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wide">
                            {{ obtenerLabelEstado(selectedEntrega.estado) }}
                          </span>
                        </div>
                     </div>
                     <div v-if="selectedEntrega.recibido_por">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-wide">Recibido por</label>
                        <div class="text-sm font-bold text-slate-900 dark:text-white">{{ selectedEntrega.recibido_por?.name }}</div>
                     </div>
                  </div>
                  <div class="bg-[var(--ui-surface)]/50 p-6 rounded-2xl border border-slate-100 dark:border-slate-700">
                     <label class="text-[10px] font-black text-slate-400 uppercase tracking-wide block mb-2">Total Entregado</label>
                     <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter">${{ formatNumber(selectedEntrega.total) }}</div>
                     <div class="mt-4 space-y-1 text-xs">
                        <div class="flex justify-between"><span>Efectivo:</span><span class="font-bold">${{ formatNumber(selectedEntrega.monto_efectivo) }}</span></div>
                        <div class="flex justify-between"><span>Otros:</span><span class="font-bold">${{ formatNumber(selectedEntrega.total - selectedEntrega.monto_efectivo) }}</span></div>
                     </div>
                  </div>
               </div>
               <div v-if="selectedEntrega.notas" class="mb-8">
                  <label class="text-[10px] font-black text-slate-400 uppercase tracking-wide block mb-2">Notas</label>
                  <div class="bg-white dark:bg-slate-800 p-4 border border-slate-100 dark:border-slate-700 rounded-xl text-sm text-slate-500 dark:text-slate-400 italic">
                    "{{ selectedEntrega.notas }}"
                  </div>
               </div>
               
               <div v-if="selectedEntrega.es_lote && selectedEntrega.children?.length" class="border-t border-slate-100 dark:border-slate-700 pt-6">
                  <h4 class="text-xs font-black text-blue-600 dark:text-blue-400 uppercase mb-4 tracking-wide">Contenido del Lote</h4>
                  <div class="max-h-48 overflow-y-auto custom-scrollbar space-y-2 pr-2">
                      <div v-for="child in selectedEntrega.children" :key="child.id" class="flex justify-between items-center p-3 bg-[var(--ui-surface)]/30 rounded-xl border border-slate-100 dark:border-slate-700">
                        <div class="flex flex-col">
                            <div class="text-xs font-bold text-slate-700 dark:text-slate-200">
                                {{ child.venta_numero ? 'Venta #' + child.venta_numero : (child.cobranza_concepto || child.tipo_origen + ' #' + child.id_origen) }}
                            </div>
                            <div v-if="child.venta_cliente" class="text-[10px] text-slate-500 dark:text-slate-400 italic">
                                Cliente: {{ child.venta_cliente }}
                            </div>
                        </div>
                        <div class="text-xs font-black text-slate-900 dark:text-white">${{ formatNumber(child.total) }}</div>
                      </div>
                  </div>
               </div>
            </div>
            
            <div v-if="modalMode === 'confirm'" class="text-center py-6">
                <div class="w-16 h-16 bg-rose-50 dark:bg-rose-900/20/30 text-rose-600 dark:text-rose-400 rounded-3xl flex items-center justify-center mx-auto mb-6">
                  <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </div>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter mb-2 uppercase">¿Eliminar Entrega?</h3>
                <p class="text-slate-500 dark:text-slate-400 font-medium">Esta acción no se puede deshacer y liberará los registros asociados.</p>
            </div>
          </div>
          
          <div class="px-8 py-6 bg-[var(--ui-surface)]/50 flex justify-end gap-3 border-t border-slate-100 dark:border-slate-700">
              <button @click="showModal = false" class="px-6 py-2.5 bg-white dark:bg-slate-800 border-2 border-slate-300 dark:border-slate-600 rounded-xl text-sm font-bold text-slate-500 dark:text-slate-200 hover:bg-slate-100 transition-all">
                Cerrar
              </button>
              <button v-if="modalMode === 'confirm'" @click="eliminarEntrega" class="px-8 py-2.5 bg-rose-600 text-white rounded-xl text-sm font-bold hover:bg-rose-700 shadow-xl shadow-rose-500/30 transition-all">
                ELIMINAR AHORA
              </button>
          </div>
      </div>
    </div>

    <!-- Modal Monto Recibido (Registros Automáticos) -->
    <div v-if="showMontoModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[60] p-4" @click.self="showMontoModal = false">
       <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-blue-100 dark:border-blue-900/30">
          <div class="p-8 bg-sky-600 text-white">
             <h3 class="text-2xl font-black uppercase tracking-wide">Registrar Recepción</h3>
             <p class="text-sky-100 text-sm mt-1">Vas a registrar el ingreso de dinero de un cobro directo.</p>
          </div>
          <div class="p-8 space-y-6">
             <div class="bg-slate-50 dark:bg-slate-900/40 p-4 rounded-xl border border-slate-100 dark:border-slate-700">
                <div class="text-[10px] font-black text-slate-400 uppercase mb-1">Registro</div>
                <div class="text-sm font-bold text-slate-900 dark:text-white">{{ selectedRegistro?.concepto }}</div>
                <div class="text-xs text-slate-500">{{ selectedRegistro?.cliente }}</div>
             </div>

             <div class="grid grid-cols-2 gap-4">
                <div>
                   <label class="text-xs font-black text-slate-400 uppercase tracking-wide block mb-2">Monto a Recibir</label>
                   <div class="relative">
                      <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">$</span>
                      <input v-model="montoRecibido" type="number" step="0.01" class="w-full bg-[var(--ui-surface)] border-2 border-slate-100 dark:border-slate-700 rounded-xl pl-8 pr-4 py-3 text-sm font-black focus:border-brand-500 outline-none transition-all dark:text-white" />
                   </div>
                </div>
                <div>
                   <label class="text-xs font-black text-slate-400 uppercase tracking-wide block mb-2">Método de Pago</label>
                   <select v-model="metodoPagoEntrega" class="w-full bg-[var(--ui-surface)] border-2 border-slate-100 dark:border-slate-700 rounded-xl px-4 py-3 text-sm font-bold focus:border-brand-500 outline-none transition-all dark:text-white">
                      <option value="efectivo">Efectivo</option>
                      <option value="transferencia">Transferencia</option>
                      <option value="cheque">Cheque</option>
                      <option value="tarjeta">Tarjeta</option>
                   </select>
                </div>
             </div>

             <div>
                <label class="text-xs font-black text-slate-400 uppercase tracking-wide block mb-2">Depositar en Cuenta</label>
                <select v-model="cuentaBancariaId" class="w-full bg-[var(--ui-surface)] border-2 border-slate-100 dark:border-slate-700 rounded-xl px-4 py-3 text-sm font-bold focus:border-brand-500 outline-none transition-all dark:text-white">
                   <option value="">Selecciona cuenta...</option>
                   <option v-for="c in cuentasBancarias" :key="c.id" :value="c.id">{{ c.nombre }} (${{ formatNumber(c.saldo_actual) }})</option>
                </select>
             </div>

             <div>
                <label class="text-xs font-black text-slate-400 uppercase tracking-wide block mb-2">Notas / Referencia</label>
                <textarea v-model="notasRecibido" rows="2" class="w-full bg-[var(--ui-surface)] border-2 border-slate-100 dark:border-slate-700 rounded-xl px-4 py-3 text-sm focus:border-brand-500 outline-none transition-all dark:text-white" placeholder="Opcional..."></textarea>
             </div>
          </div>
          <div class="px-8 py-6 bg-[var(--ui-surface)]/50 flex justify-end gap-3 border-t border-slate-100 dark:border-slate-700">
              <button @click="showMontoModal = false" class="px-6 py-2.5 bg-white dark:bg-slate-800 border-2 border-slate-300 dark:border-slate-600 rounded-xl text-sm font-bold text-slate-500 dark:text-slate-200 hover:bg-slate-100">Cancelar</button>
              <button @click="confirmarMontoRecibido" :disabled="!cuentaBancariaId || !montoRecibido" class="px-8 py-2.5 bg-sky-600 text-white rounded-xl text-sm font-black hover:bg-sky-700 shadow-xl shadow-sky-500/20 disabled:opacity-50 transition-all uppercase">CONFIRMAR INGRESO</button>
          </div>
       </div>
    </div>

    <!-- Modal Recibir (Formalizar) -->
    <div v-if="showRecibirModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4" @click.self="showRecibirModal = false">
       <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl w-full max-w-xl overflow-hidden">
          <div class="p-8 border-b border-slate-100 dark:border-slate-700">
             <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-wide">Recibir Dinero</h3>
             <p class="text-sm text-slate-500 dark:text-slate-400 font-medium mt-1">Confirma la recepción física del efectivo y deposita a una cuenta.</p>
          </div>
          <div class="p-8 space-y-6">
             <div class="bg-emerald-50 dark:bg-emerald-900/20 dark:bg-slate-800/20 p-6 rounded-2xl border border-emerald-100 dark:border-emerald-800/50 flex justify-between items-center">
                <div>
                  <div class="text-[10px] font-black text-emerald-600 dark:text-slate-400 uppercase tracking-wide">Total a Recibir</div>
                  <div class="text-2xl font-black text-emerald-900 dark:text-emerald-100">${{ formatNumber(entregaParaRecibir?.total) }}</div>
                </div>
                <div class="text-right">
                  <div class="text-xs font-bold text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-emerald-300">{{ entregaParaRecibir?.usuario?.name }}</div>
                  <div class="text-[10px] text-emerald-600/60">{{ formatearFecha(entregaParaRecibir?.fecha_entrega) }}</div>
                </div>
             </div>
             
             <div>
                <label class="text-xs font-black text-slate-400 uppercase tracking-wide block mb-2">Cuenta de Depósito</label>
                <select v-model="cuentaBancariaId" class="w-full bg-[var(--ui-surface)] border-2 border-slate-100 dark:border-slate-700 rounded-xl px-4 py-3 text-sm font-bold focus:border-brand-500 outline-none transition-all dark:text-white">
                   <option value="">Selecciona cuenta...</option>
                   <option v-for="c in cuentasBancarias" :key="c.id" :value="c.id">{{ c.nombre }} (${{ formatNumber(c.saldo_actual) }})</option>
                </select>
             </div>
             
             <div>
                <label class="text-xs font-black text-slate-400 uppercase tracking-wide block mb-2">Notas de Recepción</label>
                <textarea v-model="notasRecibo" rows="2" class="w-full bg-[var(--ui-surface)] border-2 border-slate-100 dark:border-slate-700 rounded-xl px-4 py-3 text-sm focus:border-brand-500 outline-none transition-all dark:text-white" placeholder="Opcional..."></textarea>
             </div>
          </div>
          <div class="px-8 py-6 bg-[var(--ui-surface)]/50 flex justify-end gap-3 border-t border-slate-100 dark:border-slate-700">
              <button @click="showRecibirModal = false" class="px-6 py-2.5 bg-white dark:bg-slate-800 border-2 border-slate-300 dark:border-slate-600 rounded-xl text-sm font-bold text-slate-500 dark:text-slate-200 hover:bg-slate-100">Cancelar</button>
              <button @click="confirmarRecepcionEntrega" :disabled="!cuentaBancariaId" class="px-8 py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-black hover:bg-emerald-700 shadow-xl shadow-emerald-500/20 disabled:opacity-50 transition-all uppercase">CONFIRMAR RECEPCIÓN</button>
          </div>
       </div>
    </div>

    <!-- Modal Lote Masivo -->
    <div v-if="showCrearLoteModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4" @click.self="showCrearLoteModal = false">
       <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-blue-100 dark:border-blue-900/30">
          <div class="p-8 bg-blue-600 text-white">
             <h3 class="text-2xl font-black uppercase tracking-wide">Generar Lote Masivo</h3>
             <p class="text-blue-100 text-sm mt-1">Vas a agrupar {{ selectedRegistros.length }} registros en una sola entrega.</p>
          </div>
          <div class="p-8 space-y-6 text-slate-900 dark:text-white">
             <div class="flex justify-between items-center pb-4 border-b border-slate-100 dark:border-slate-700">
                <span class="text-sm font-bold opacity-60 uppercase">Total del Lote</span>
                <span class="text-2xl font-black text-blue-600 dark:text-blue-400 tracking-tighter">${{ formatNumber(totalSeleccionado) }}</span>
             </div>
             <div>
                <label class="text-xs font-black text-slate-400 uppercase tracking-wide block mb-2">Folio de Referencia / Notas</label>
                <textarea v-model="notasLote" class="w-full bg-[var(--ui-surface)] border-2 border-slate-100 dark:border-slate-700 rounded-xl px-4 py-3 text-sm focus:border-brand-500 outline-none transition-all" placeholder="Ej: Depósito Santander #9012"></textarea>
             </div>
          </div>
          <div class="px-8 py-6 bg-[var(--ui-surface)]/50 flex justify-end gap-3 border-t border-slate-100 dark:border-slate-700">
              <button @click="showCrearLoteModal = false" class="px-6 py-2.5 bg-white dark:bg-slate-800 border-2 border-slate-300 dark:border-slate-600 rounded-xl text-sm font-bold text-slate-500 dark:text-slate-200 hover:bg-slate-100">Cancelar</button>
              <button @click="confirmarLote" class="px-8 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-black hover:bg-blue-700 shadow-xl shadow-sky-500/30 transition-all uppercase">GENERAR LOTE AHORA</button>
          </div>
       </div>
    </div>
  </div>
</template>

<style scoped>
.entregas-dinero-index { font-family: 'Inter', sans-serif; }
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>
