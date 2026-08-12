<script setup>
import { ref, computed, onMounted } from 'vue'
import { router, Head, usePage, Link } from '@inertiajs/vue3'
import axios from 'axios'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

import AppLayout from '@/Layouts/AppLayout.vue'
import CrudPageHeader from '@/Components/CrudPageHeader.vue'
import IndexTable from '@/Components/IndexTable.vue'
import Modal from '@/Components/IndexComponents/Modales.vue'
import ModalCotizacion from '@/Components/IndexComponents/ModalCotizacion.vue'

defineOptions({ layout: AppLayout })

const page = usePage()

const props = defineProps({
  cotizaciones: { type: Array, default: () => [] },
  pagination: { type: Object, default: () => ({ current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0, links: [] }) },
  filters: { type: Object, default: () => ({ search: '', estado: '', per_page: 10 }) }
})

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false }
  ]
})

const showModal = ref(false)
const fila = ref(null)
const modalMode = ref('details')
const selectedId = ref(null)
const loading = ref(false)

const searchTerm = ref(props.filters?.search || '')
const sortBy = ref('fecha-desc')
const filtroEstado = ref(props.filters?.estado || '')
const perPage = ref(props.filters?.per_page || 10)
const currentPage = ref(props.pagination?.current_page || 1)

const whatsappCotizacionReciente = ref(null)

onMounted(() => {
  const w = page.props.flash?.whatsapp_cotizacion_reciente
  if (w && typeof w === 'object' && w.id) whatsappCotizacionReciente.value = w
  if (page.props.flash?.success) notyf.success(page.props.flash.success)
  if (page.props.flash?.error) notyf.error(page.props.flash.error)
})

const fetchData = (params = {}) => {
  router.get(route('cotizaciones.index'), {
    search: searchTerm.value,
    estado: filtroEstado.value,
    per_page: perPage.value,
    page: params.page || currentPage.value,
  }, { preserveState: true, preserveScroll: true })
}

const onSearch = () => fetchData({ page: 1 })

const handleLimpiarFiltros = () => {
  searchTerm.value = ''
  sortBy.value = 'fecha-desc'
  filtroEstado.value = ''
  fetchData({ page: 1 })
}

const updateSort = (val) => { sortBy.value = val; }

const formatDate = (v) => v ? new Date(v).toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' }) : '-'
const formatCurrency = (v) => v != null ? '$' + Number(v).toLocaleString('es-MX', { minimumFractionDigits: 2 }) : '-'

const columns = [
  { key: 'numero_cotizacion', label: 'Folio' },
  { key: 'cliente.nombre', label: 'Cliente', format: (v, row) => row.cliente?.nombre || '-' },
  { key: 'fecha', label: 'Fecha', format: (v, row) => formatDate(row.created_at || row.fecha) },
  { key: 'total', label: 'Total', format: (v) => formatCurrency(v) },
  { key: 'estado', label: 'Estado', format: (v) => {
    const estados = { pendiente: 'Pendiente', borrador: 'Borrador', aprobada: 'Aprobada', cancelado: 'Cancelado', enviado_pedido: 'Enviado a Pedido' }
    return estados[v] || v || '-'
  }},
]

const verDetalles = (doc) => {
  fila.value = doc
  modalMode.value = 'details'
  showModal.value = true
}

const cerrarModal = () => {
  showModal.value = false
  fila.value = null
  selectedId.value = null
}

const editarCotizacion = (id) => router.visit(`/cotizaciones/${id}/edit`)

const crearNuevaCotizacion = () => router.visit(route('cotizaciones.create'))

const confirmarEliminacion = (id) => {
  selectedId.value = id
  fila.value = props.cotizaciones.find(c => c.id === id)
  modalMode.value = 'confirm'
  showModal.value = true
}

const eliminarCotizacion = () => {
  router.post(`/cotizaciones/${selectedId.value}/cancel`, {}, {
    onSuccess: () => {
      notyf.success('Propuesta revocada')
      cerrarModal()
    }
  })
}

const enviarAPedido = async (doc) => {
  try {
    loading.value = true
    const { data } = await axios.post(`/cotizaciones/${doc.id}/enviar-a-pedido`)
    if (data.success) {
      notyf.success('Cotización enviada a pedido')
      router.visit(route('pedidos.index'))
    }
  } catch (err) {
    notyf.error('Error al procesar pedido')
  } finally {
    loading.value = false
  }
}

const aprobarCotizacion = async (doc) => {
  try {
    loading.value = true
    const { data } = await axios.post(`/cotizaciones/${doc.id}/aprobar`)
    if (data.success) {
      notyf.success('Cotización aprobada')
      const updated = props.cotizaciones.map(c => c.id === doc.id ? { ...c, estado: data.estado } : c)
      if (typeof window !== 'undefined') {
        window.dispatchEvent(new CustomEvent('cotizaciones:refresh', { detail: updated }))
      }
      if (fila.value && fila.value.id === doc.id) {
        fila.value = { ...fila.value, estado: data.estado }
      }
      cerrarModal()
    }
  } catch (err) {
    const msg = err?.response?.data?.error || 'Error al aprobar'
    notyf.error(msg)
  } finally {
    loading.value = false
  }
}

const imprimirCotizacion = (doc) => window.open(`/cotizaciones/${doc.id}/pdf`, '_blank')

const enviarCotizacionPorEmail = (doc) => {
  if (!doc.cliente?.email) return notyf.error('Cliente sin email')
  fila.value = { ...doc, email_destino: doc.cliente.email }
  modalMode.value = 'confirm-email'
  showModal.value = true
}

const confirmarEnvioEmail = async () => {
  try {
    loading.value = true
    const { data } = await axios.post(`/cotizaciones/${fila.value.id}/enviar-email`, { email_destino: fila.value.email_destino })
    if (data.success) notyf.success('Email enviado')
    cerrarModal()
  } catch (err) {
    notyf.error('Error al enviar email')
  } finally {
    loading.value = false
  }
}

const irAlInboxWhatsappCotizacion = (doc) => {
  if (!doc.cliente?.telefono) return notyf.error('Cliente sin teléfono')
  router.visit(route('marketing.whatsapp.inbox', { cotizacion: doc.id }))
}

const enviarWhatsappDesdeBanner = () => {
  irAlInboxWhatsappCotizacion(whatsappCotizacionReciente.value)
  cerrarBannerWhatsappReciente()
}

const cerrarBannerWhatsappReciente = () => { whatsappCotizacionReciente.value = null }

const estadisticas = computed(() => ({
  total: props.pagination?.total || 0,
  pendientes: props.cotizaciones.filter(c => c.estado === 'pendiente').length,
  enviado_pedido: props.cotizaciones.filter(c => c.estado === 'enviado_pedido').length,
  cancelado: props.cotizaciones.filter(c => c.estado === 'cancelado').length,
}))

const documentosCotizaciones = computed(() => props.cotizaciones)

const paginationData = computed(() => props.pagination)

const auditoriaForModal = computed(() => {
  if (!fila.value) return null
  return {
    creado_por: fila.value.creado_por_nombre || 'Sistema',
    creado_en: fila.value.created_at
  }
})
</script>

<template>
  <Head title="Cotizaciones" />

  <div class="min-h-screen">
    <div class="w-full px-4 sm:px-6 py-6">
      <!-- Banner WhatsApp Business -->
      <Transition name="modal-fade">
        <div v-if="whatsappCotizacionReciente" class="mb-6 p-6 bg-emerald-600 rounded-xl shadow-2xl shadow-emerald-600/20 flex flex-col md:flex-row items-center justify-between gap-6 border-4 border-emerald-500/30">
          <div class="flex items-center gap-6">
            <div class="w-14 h-14 rounded-xl bg-white/20 flex items-center justify-center text-white backdrop-blur-xl border border-white/20">
              <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" /></svg>
            </div>
            <div>
              <p class="text-xs font-black text-white/70 uppercase tracking-[0.3em] mb-1">Operación Registrada</p>
              <h3 class="text-xl font-black text-white uppercase tracking-wider">Propuesta {{ whatsappCotizacionReciente.numero_cotizacion || ('#' + whatsappCotizacionReciente.id) }} lista</h3>
            </div>
          </div>
          <div class="flex items-center gap-4">
            <button v-if="whatsappCotizacionReciente.cliente?.telefono" @click="enviarWhatsappDesdeBanner" class="px-8 py-4 bg-white text-emerald-600 text-[10px] font-black uppercase tracking-[0.2em] rounded-xl shadow-xl hover:scale-[1.03] active:scale-[0.97] transition-all">
              Confirmar en Inbox
            </button>
            <button @click="cerrarBannerWhatsappReciente" class="px-6 py-4 text-white/70 hover:text-white text-[10px] font-black uppercase tracking-wide transition-colors">
              Cerrar Atajo
            </button>
          </div>
        </div>
      </Transition>

      <CrudPageHeader title="Cotizaciones" subtitle="Gestión de cotizaciones">
        <template #actions>
          <div class="flex items-center gap-2">
            <div class="relative">
              <input v-model="searchTerm" @keyup.enter="onSearch" type="text" placeholder="Buscar..."
                class="w-48 lg:w-64 px-4 py-2.5 text-sm border border-slate-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all" />
            </div>
            <select v-model="filtroEstado" @change="onSearch"
              class="px-4 py-2.5 text-sm border border-slate-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all">
              <option value="">Todos los estados</option>
              <option value="pendiente">Pendiente</option>
              <option value="aprobada">Aprobada</option>
              <option value="enviado_pedido">Enviado a Pedido</option>
              <option value="cancelado">Cancelado</option>
            </select>
            <Link :href="route('cotizaciones.create')"
              class="inline-flex items-center px-4 py-2.5 bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold rounded-xl transition-all duration-200 shadow-sm">
              <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Nueva Cotización
            </Link>
          </div>
        </template>
      </CrudPageHeader>

      <IndexTable
        :columns="columns"
        :rows="documentosCotizaciones"
        empty-text="No hay cotizaciones registradas"
        empty-subtext="Crea la primera cotización usando el botón Nueva Cotización"
      >
        <template #actions="{ row }">
          <div class="flex justify-end gap-1.5">
            <button v-if="['pendiente', 'borrador'].includes(row.estado?.toLowerCase())" @click="aprobarCotizacion(row)"
              class="w-9 h-9 flex items-center justify-center rounded-xl transition-all duration-200 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/30"
              title="Aprobar">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </button>
            <button @click="verDetalles(row)"
              class="w-9 h-9 flex items-center justify-center rounded-xl transition-all duration-200 bg-sky-50 dark:bg-sky-900/20 text-sky-600 dark:text-sky-400 hover:bg-sky-100 dark:hover:bg-sky-900/30"
              title="Ver detalles">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </button>
            <button @click="editarCotizacion(row.id)"
              class="w-9 h-9 flex items-center justify-center rounded-xl transition-all duration-200 bg-brand-50 dark:bg-brand-900/20 text-brand-600 dark:text-brand-400 hover:bg-brand-100 dark:hover:bg-brand-900/30"
              title="Editar">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <button @click="imprimirCotizacion(row)"
              class="w-9 h-9 flex items-center justify-center rounded-xl transition-all duration-200 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-600"
              title="Imprimir">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
              </svg>
            </button>
            <button @click="enviarCotizacionPorEmail(row)"
              class="w-9 h-9 flex items-center justify-center rounded-xl transition-all duration-200 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/30"
              title="Enviar por Email">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
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
          <div v-if="paginationData.links" class="flex justify-between items-center">
            <div class="text-sm text-slate-500">
              Mostrando {{ paginationData.from || 0 }} - {{ paginationData.to || 0 }} de {{ paginationData.total || 0 }}
            </div>
            <div class="flex gap-1.5">
              <Link v-for="(link, i) in paginationData.links" :key="i"
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

      <!-- Visor de Cotización (Expediente 360°) -->
      <ModalCotizacion
        :show="showModal && modalMode === 'details'"
        :selected="fila"
        :auditoria="auditoriaForModal"
        @close="cerrarModal"
        @editar="editarCotizacion"
        @enviar-a-pedido="enviarAPedido"
        @aprobar="aprobarCotizacion"
      />

      <!-- Modales de Acción (Confirmaciones) -->
      <Modal
        v-if="modalMode !== 'details'"
        :show="showModal"
        :mode="modalMode"
        :tipo="'cotizaciones'"
        :selected="fila"
        @close="cerrarModal"
        @confirm-delete="eliminarCotizacion"
        @confirm-email="confirmarEnvioEmail"
      >
         <div class="p-8 text-center">
            <!-- Icono Dinámico según modo -->
            <div v-if="modalMode === 'confirm'" class="w-20 h-20 bg-rose-50 dark:bg-rose-900/20 rounded-xl flex items-center justify-center mx-auto mb-6 text-rose-600 border border-rose-100 dark:border-rose-800">
               <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </div>
            <div v-else-if="modalMode === 'confirm-email'" class="w-20 h-20 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl flex items-center justify-center mx-auto mb-6 text-emerald-600 border border-emerald-100 dark:border-emerald-800">
               <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>

            <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-wider mb-2">
              {{ modalMode === 'confirm' ? '¿Revocar Propuesta?' : (modalMode === 'confirm-email' ? 'Notificar Cliente' : 'Confirmar Acción') }}
            </h3>

            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide leading-loose mb-8">
               {{ modalMode === 'confirm' ? 'Esta acción cancelará la propuesta comercial de forma permanente.' : (modalMode === 'confirm-email' ? `Se enviará la propuesta al correo ${fila?.email_destino}.` : '¿Desea proceder con esta operación?') }}
            </p>

            <div class="flex gap-4">
               <button @click="cerrarModal" class="flex-1 py-4 bg-slate-100 dark:bg-slate-800 text-[10px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wide rounded-xl transition-all">Cancelar</button>
               <button
                 v-if="modalMode === 'confirm'"
                 @click="eliminarCotizacion"
                 class="flex-1 py-4 bg-rose-600 text-white text-[10px] font-black uppercase tracking-wide rounded-xl shadow-xl shadow-rose-600/20 transition-all hover:bg-rose-700"
               >Confirmar Revocación</button>
               <button
                 v-if="modalMode === 'confirm-email'"
                 @click="confirmarEnvioEmail"
                 class="flex-1 py-4 bg-emerald-600 text-white text-[10px] font-black uppercase tracking-wide rounded-xl shadow-xl shadow-emerald-600/20 transition-all hover:bg-emerald-700"
               >Enviar Ahora</button>
            </div>
         </div>
      </Modal>
    </div>
  </div>
</template>

<style>
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.5s ease, transform 0.5s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; transform: scale(0.95); }
</style>
