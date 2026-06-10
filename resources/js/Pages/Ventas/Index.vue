<script setup>
import { ref, shallowRef, computed, watch, onMounted } from 'vue'
import { router, Head, usePage, Link } from '@inertiajs/vue3'
import axios from 'axios'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import AppLayout from '@/Layouts/AppLayout.vue'
import VentasHeader from '@/Components/IndexComponents/VentasHeader.vue'
import IndexTable from '@/Components/IndexTable.vue'
import ModalVenta from '@/Components/IndexComponents/ModalVenta.vue'
import DialogModal from '@/Components/DialogModal.vue'
import Swal from '@/Utils/Swal'
import debounce from 'lodash-es/debounce'

defineOptions({ layout: AppLayout, inheritAttrs: false })

const props = defineProps({
  ventas: { type: Object, default: () => ({ data: [] }) },
  estadisticas: { type: Object, default: () => ({ total: 0, borrador: 0, aprobadas: 0, pendientes: 0, cancelada: 0 }) },
  filters: { type: Object, default: () => ({}) },
  sorting: { type: Object, default: () => ({ sort_by: 'created_at', sort_direction: 'desc' }) },
  pagination: { type: Object, default: () => ({}) },
  cuentasBancarias: { type: Array, default: () => [] },
  usuariosCobro: { type: Array, default: () => [] }
})

const page = usePage()

const usuariosCobroLista = computed(() => {
  const fromApi = props.usuariosCobro
  if (Array.isArray(fromApi) && fromApi.length > 0) return fromApi
  const u = page.props.auth?.user
  if (u?.id) return [{ id: u.id, name: u.name || u.email || 'Usuario actual' }]
  return []
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
  const flash = page.props.flash
  if (flash?.success) notyf.success(flash.success)
  if (flash?.error) notyf.error(flash.error)
})

/* =========================
   Estado local y modal
========================= */
const ventasList = shallowRef([])
watch(() => props.ventas, (newVal) => {
  ventasList.value = newVal?.data || []
}, { immediate: true })

const showModal = ref(false)
const fila = ref(null)
const selectedId = ref(null)
const loading = ref(false)

const showPaymentModal = ref(false)
const selectedVenta = ref(null)
const metodoPago = ref('')
const cuentaBancariaId = ref('')
const notasPago = ref('')
const pagadoPorUserId = ref('')

const showCancelModal = ref(false)
const selectedVentaCancel = ref(null)
const motivoCancelacion = ref('')
const forceWithPayments = ref(false)

const showDeleteModal = ref(false)
const selectedVentaDelete = ref(null)

const showEmailModal = ref(false)
const selectedVentaEmail = ref(null)

const searchTerm = ref('')
const sortBy = ref('fecha-desc')
const filtroCfdi = ref('')
const perPage = ref(10)

/* =========================
   Paginación
========================= */
const paginationData = computed(() => ({
  current_page: props.pagination?.current_page || 1,
  last_page: props.pagination?.last_page || 1,
  per_page: props.pagination?.per_page || 10,
  from: props.pagination?.from || 0,
  to: props.pagination?.to || 0,
  total: props.pagination?.total || 0,
}))

const goToPage = (page) => {
  router.get('/ventas', {
    page, search: searchTerm.value, cfdi: filtroCfdi.value,
    sort_by: sortBy.value.split('-')[0], sort_direction: sortBy.value.split('-')[1] || 'desc', per_page: paginationData.value.per_page
  }, { preserveState: true, preserveScroll: true, replace: true, only: ['ventas', 'pagination', 'filters'] })
}

const onSearch = () => goToPage(1)

const handleFilter = () => onSearch()

const debouncedSearch = debounce(() => {
  goToPage(1)
}, 400)

watch([searchTerm, filtroCfdi, sortBy], () => {
  debouncedSearch()
})

const handleLimpiarFiltros = () => {
  searchTerm.value = ''
  filtroCfdi.value = ''
  sortBy.value = 'fecha-desc'
  perPage.value = 10
  notyf.success('Filtros limpiados correctamente')
}

const updateSort = (newSort) => { if (newSort) sortBy.value = newSort }

/* =========================
   Helpers
========================= */
const formatearMoneda = (num) => {
  const value = parseFloat(num)
  const safe = Number.isFinite(value) ? value : 0
  return new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(safe)
}

const formatearFecha = (v) => {
  if (!v) return '-'
  try { return new Date(v).toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' }) }
  catch { return '-' }
}

const estadisticasLocales = computed(() => {
  if (props.estadisticas?.total > 0) {
    return { total: props.estadisticas.total || 0, borrador: props.estadisticas.borrador || 0, aprobados: props.estadisticas.aprobadas || 0, pendientes: props.estadisticas.pendientes || 0, cancelado: props.estadisticas.cancelada || 0 }
  }
  const stats = { total: 0, aprobados: 0, pendientes: 0, borrador: 0, cancelado: 0 }
  return stats
})

const columns = [
  { key: 'fecha', label: 'Fecha', format: (v, row) => formatearFecha(row.fecha || row.created_at) },
  { key: 'cliente', label: 'Cliente', format: (v, row) => row.cliente?.nombre_razon_social || row.cliente?.nombre || 'Público en general' },
  { key: 'numero_venta', label: 'Número', format: (v) => v || 'N/A' },
  { key: 'total', label: 'Total', format: (v) => '$' + formatearMoneda(v) },
  { key: 'estado', label: 'Estado' },
  { key: 'tiene_entrega_dinero', label: 'Corte / Entrega' },
]

/* =========================
   Acciones CRUD
========================= */
const verDetalles = (venta) => {
  if (!venta?.id) { notyf.error('ID de venta no válido'); return }
  fila.value = venta
  showModal.value = true
}

const cerrarModal = () => { showModal.value = false; fila.value = null }

const editarVenta = (id) => {
  if (!id) { notyf.error('ID de venta no válido'); return }
  router.visit(`/ventas/${id}/edit`)
}

const crearNuevaVenta = () => router.visit('/ventas/create')

const facturarVenta = (row) => {
  if (!row?.id) return;
  router.visit(route('facturas.create', { cliente_id: row.cliente_id, venta_id: row.id }));
}

const duplicarVenta = async (venta) => {
  if (!venta?.id) { notyf.error('Venta no válida'); return }
  const result = await Swal.fire({
    title: '¿Duplicar venta?',
    text: `¿Duplicar venta #${venta.numero_venta || venta.id}?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Sí, duplicar',
    cancelButtonText: 'Cancelar',
  })
  if (!result.isConfirmed) return
  try {
    loading.value = true; notyf.success('Duplicando venta...')
    const { data } = await axios.post(`/ventas/${venta.id}/duplicate`)
    if (data?.success) { notyf.success(data.message || 'Venta duplicada exitosamente'); router.visit('/ventas', { method: 'get', replace: true }) }
    else throw new Error(data?.error || 'Error al duplicar')
  } catch (error) {
    notyf.error('Error al duplicar la venta')
  } finally { loading.value = false }
}

const imprimirVenta = async (venta) => {
  try {
    const response = await axios.get(`/ventas/${venta.id}/pdf`, { responseType: 'blob' })
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url; link.download = `venta-${venta.numero_venta || venta.id}.pdf`
    document.body.appendChild(link); link.click(); document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (error) { notyf.error('Error al descargar el PDF') }
}

const confirmarEliminacion = (id) => {
  if (!id) { notyf.error('ID de venta no válido'); return }
  selectedId.value = id
  const venta = ventasList.value?.find?.(v => v.id === id)
  if (venta) fila.value = venta
  showModal.value = true
}

const eliminarVenta = async (idParam = null) => {
  if (idParam && typeof idParam !== 'object') selectedId.value = idParam
  const ventaId = selectedId.value || fila.value?.id
  if (!ventaId) { notyf.error('No se seleccionó ninguna venta'); return }
  const venta = ventasList.value?.find?.(v => v.id === ventaId)
  const isCancelada = venta?.estado === 'cancelada' || venta?.estado === 'cancelado'
  if (isCancelada) confirmarBorradoReal(ventaId)
  else cancelarVenta(ventaId)
}



const marcarComoPagado = (venta) => {
  selectedVenta.value = venta
  metodoPago.value = ''; cuentaBancariaId.value = ''; notasPago.value = ''
  pagadoPorUserId.value = String(page.props.auth?.user?.id ?? '')
  showPaymentModal.value = true
}

const cerrarPaymentModal = () => { showPaymentModal.value = false; selectedVenta.value = null; metodoPago.value = ''; cuentaBancariaId.value = ''; notasPago.value = ''; pagadoPorUserId.value = '' }

const confirmarPago = async () => {
  if (!metodoPago.value) { notyf.error('Debe seleccionar un método de pago'); return }
  if (!selectedVenta.value) { notyf.error('No hay venta seleccionada'); return }
  if (['cancelada', 'cancelado'].includes(selectedVenta.value.estado)) { notyf.error('No se puede marcar como pagada una venta cancelada'); return }
  if (selectedVenta.value.pagado) { notyf.error('Esta venta ya está marcada como pagada'); return }
  if (!selectedVenta.value.total || selectedVenta.value.total <= 0) { notyf.error('El total de la venta debe ser mayor a cero'); return }
  try {
    loading.value = true; notyf.success('Procesando pago...')
    const { data } = await axios.post(`/ventas/${selectedVenta.value.id}/marcar-pagado`, {
      metodo_pago: metodoPago.value, cuenta_bancaria_id: cuentaBancariaId.value || null, notas_pago: notasPago.value,
      pagado_por_user_id: pagadoPorUserId.value ? Number(pagadoPorUserId.value) : undefined
    })
    if (data?.success) {
      notyf.success(data.message || 'Venta marcada como pagada exitosamente')
      cerrarPaymentModal()
      router.visit('/ventas', { method: 'get', replace: true })
    } else throw new Error(data?.error || 'Error al procesar el pago')
  } catch (error) { notyf.error('Error al procesar el pago') }
  finally { loading.value = false }
}

const cancelarVenta = (id) => {
  const venta = ventasList.value?.find?.(v => v.id === id)
  if (!venta) { notyf.error('Venta no encontrada'); return }
  selectedVentaCancel.value = venta; motivoCancelacion.value = ''; showCancelModal.value = true
}

const confirmarCancelacion = () => {
  if (!selectedVentaCancel.value) return
  router.post(route('ventas.cancelar', selectedVentaCancel.value.id), {
    motivo: motivoCancelacion.value, force_with_payments: forceWithPayments.value,
  }, {
    onStart: () => { loading.value = true; notyf.success('Cancelando venta...') },
    onSuccess: () => { notyf.success('Venta cancelada exitosamente'); showCancelModal.value = false; selectedVentaCancel.value = null; motivoCancelacion.value = ''; loading.value = false; router.visit('/ventas', { method: 'get', replace: true }) },
    onError: (errors) => { notyf.error(errors.error || 'Error al cancelar la venta'); loading.value = false },
    onFinish: () => { loading.value = false }
  })
}

const cerrarCancelModal = () => { showCancelModal.value = false; selectedVentaCancel.value = null; motivoCancelacion.value = ''; forceWithPayments.value = false }

const cerrarDeleteModal = () => { showDeleteModal.value = false; selectedVentaDelete.value = null }

const cerrarEmailModal = () => { showEmailModal.value = false; selectedVentaEmail.value = null }

const onOpenEmailModal = (venta) => { if (!venta) return; selectedVentaEmail.value = venta; showEmailModal.value = true }

const confirmarEnviarEmail = async () => {
  if (!selectedVentaEmail.value) return
  try {
    loading.value = true; notyf.success('Enviando correo...')
    const { data } = await axios.post(`/ventas/${selectedVentaEmail.value.id}/email`)
    if (data?.success) { notyf.success(data.message || 'Correo enviado exitosamente'); cerrarEmailModal() }
    else throw new Error(data?.error || 'Error al enviar el correo')
  } catch (error) { notyf.error('Error al enviar el correo') }
  finally { loading.value = false }
}

const confirmarBorradoReal = (id) => {
  const venta = ventasList.value?.find?.(v => v.id === id)
  if (!venta) { notyf.error('Venta no encontrada'); return }
  showModal.value = false
  selectedVentaDelete.value = venta; showDeleteModal.value = true
}

const enviarWhatsApp = (venta) => {
  if (!venta?.cliente?.telefono) { notyf.error('El cliente no tiene un teléfono registrado'); return }
  const telefono = String(venta.cliente.telefono).replace(/\D/g, '')
  if (telefono.length < 10) { notyf.error('El teléfono del cliente no es válido'); return }
  let mensaje = `Hola ${venta.cliente.nombre_razon_social}, le envío su nota de venta #${venta.numero_venta || venta.id}. Total: $${formatearMoneda(venta.total)}`
  if (venta.sharing_token) mensaje += `\n\nPuedes descargar tu nota aquí:\n${window.location.origin}/share/venta/${venta.sharing_token}/pdf`
  window.open(`https://web.whatsapp.com/send?phone=52${telefono}&text=${encodeURIComponent(mensaje)}`, 'whatsapp_climas')
}

const ejecutarBorrado = async () => {
  if (!selectedVentaDelete.value) return
  const ventaId = selectedVentaDelete.value.id
  try {
    loading.value = true; notyf.success('Eliminando venta...')
    await axios.delete(`/ventas/${ventaId}`)
    notyf.success('Venta eliminada exitosamente'); showDeleteModal.value = false; selectedVentaDelete.value = null
    router.visit('/ventas', { method: 'get', replace: true })
  } catch (error) { notyf.error('Error al eliminar la venta') }
  finally { loading.value = false }
}

const editarFila = (id) => editarVenta(id)
const imprimirFila = () => { if (fila.value) imprimirVenta(fila.value) }
</script>

<template>
  <Head title="Ventas" />
  <div class="min-h-screen">
    <div class="w-full px-4 sm:px-6 py-6">
      <VentasHeader
        :total="estadisticasLocales.total"
        :borrador="estadisticasLocales.borrador"
        :aprobadas="estadisticasLocales.aprobados"
        :pendientes="estadisticasLocales.pendientes"
        :cancelada="estadisticasLocales.cancelado"
        v-model:search-term="searchTerm"
        v-model:sort-by="sortBy"
        v-model:filtro-cfdi="filtroCfdi"
        @filtro-cfdi-change="handleFilter"
        @limpiar-filtros="handleLimpiarFiltros"
        @crear-nuevo="crearNuevaVenta"
      />

      <IndexTable
        :columns="columns"
        :rows="ventasList || []"
        empty-text="No hay ventas registradas"
        empty-subtext="Crea la primera venta usando el botón Crear Venta"
      >
        <template #cell-estado="{ row }">
          <div class="flex items-center">
            <span v-if="row.estado === 'borrador'" class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
              Borrador
            </span>
            <span v-else-if="row.estado === 'aprobada' || row.estado === 'aprobado'" class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/30">
              Aprobada
            </span>
            <span v-else-if="row.estado === 'cancelada' || row.estado === 'cancelado'" class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-600 dark:bg-rose-950/30 dark:text-rose-400 border border-rose-100 dark:border-rose-900/30">
              Cancelada
            </span>
            <span v-else class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider bg-amber-50 text-amber-600 dark:bg-amber-950/30 dark:text-amber-400 border border-amber-100 dark:border-amber-900/30">
              {{ row.estado }}
            </span>
          </div>
        </template>

        <template #cell-tiene_entrega_dinero="{ row }">
          <div class="flex items-center">
            <!-- Caso: Ya se entregó o recibió dinero -->
            <Link v-if="row.tiene_entrega_dinero" 
              :href="route('entregas-dinero.index')"
              class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all hover:brightness-110 active:scale-95 shadow-sm border"
              :class="row.entrega_dinero_estado === 'recibido' 
                ? 'bg-emerald-500 text-white border-emerald-400 shadow-emerald-500/20' 
                : 'bg-indigo-500 text-white border-indigo-400 shadow-indigo-500/20'"
              :title="row.entrega_dinero_estado === 'recibido' ? 'Dinero recibido por administración. Ver listado.' : 'Dinero entregado, pendiente de recibo. Ver listado.'"
            >
              <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path v-if="row.entrega_dinero_estado === 'recibido'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              {{ row.entrega_dinero_estado === 'recibido' ? 'Dinero en Caja' : 'Entregado' }}
            </Link>

            <!-- Caso: Es efectivo pero NO se ha entregado -->
            <Link v-else-if="row.metodo_pago === 'efectivo' && !row.tiene_entrega_dinero"
              :href="route('entregas-dinero.create', { venta_id: row.id })"
              class="inline-flex items-center px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest bg-rose-600 text-white shadow-lg shadow-rose-600/20 transition-all hover:bg-rose-700 hover:-translate-y-0.5 active:translate-y-0 animate-pulse-slow border-2 border-rose-400/30"
              title="Venta en efectivo pendiente de entrega. Clic para realizar corte de esta venta."
            >
              <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              Entregar Dinero
            </Link>

            <!-- Caso: No es efectivo (N/A) -->
            <div v-else class="flex flex-col">
               <span class="text-slate-400 dark:text-slate-500 text-[9px] font-black uppercase tracking-tighter opacity-60">No Aplica</span>
               <span class="text-[8px] text-slate-300 dark:text-slate-600 font-bold uppercase truncate max-w-[80px]">{{ row.metodo_pago }}</span>
            </div>
          </div>
        </template>
        <template #actions="{ row }">
          <div class="flex justify-end gap-1.5">
            <button @click="verDetalles(row)"
              class="w-9 h-9 flex items-center justify-center rounded-xl transition-all duration-200 bg-sky-50 dark:bg-sky-900/20 text-sky-600 dark:text-sky-400 hover:bg-sky-100 dark:hover:bg-sky-900/30"
              title="Ver detalles">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </button>
            <button v-if="row.estado !== 'cancelada'" @click="facturarVenta(row)"
              class="w-9 h-9 flex items-center justify-center rounded-xl transition-all duration-200 bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-900/30"
              title="Facturar Venta">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </button>
            <button @click="editarVenta(row.id)"
              class="w-9 h-9 flex items-center justify-center rounded-xl transition-all duration-200 bg-brand-50 dark:bg-brand-900/20 text-brand-600 dark:text-brand-400 hover:bg-brand-100 dark:hover:bg-brand-900/30"
              title="Editar">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <button v-if="!row.pagado && row.estado !== 'cancelada'" @click="marcarComoPagado(row)"
              class="w-9 h-9 flex items-center justify-center rounded-xl transition-all duration-200 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/30"
              title="Marcar como pagado">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </button>

            <button @click="enviarWhatsApp(row)"
              class="w-9 h-9 flex items-center justify-center rounded-xl transition-all duration-200 bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/30"
              title="Enviar WhatsApp">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" /></svg>
            </button>
            <button @click="onOpenEmailModal(row)"
              class="w-9 h-9 flex items-center justify-center rounded-xl transition-all duration-200 bg-violet-50 dark:bg-violet-900/20 text-violet-600 dark:text-violet-400 hover:bg-violet-100 dark:hover:bg-violet-900/30"
              title="Enviar Email">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
            </button>
            <button @click="eliminarVenta(row.id)"
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
              Mostrando {{ paginationData.from }} - {{ paginationData.to }} de {{ paginationData.total }}
            </div>
            <div class="flex gap-1.5">
              <button @click="goToPage(paginationData.current_page - 1)" :disabled="paginationData.current_page === 1"
                class="px-3 py-1.5 text-sm rounded-lg transition-all duration-150 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 disabled:opacity-50">Anterior</button>
              <Link v-for="(link, i) in (props.ventas.links || [])" :key="i"
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

      <!-- ModalVenta (Detalles) -->
      <ModalVenta
        v-if="showModal"
        :show="showModal"
        :selected="fila"
        @close="cerrarModal"
        @editar="editarFila"
        @imprimir="imprimirFila"
        @eliminar="confirmarBorradoReal"
      />

      <!-- Modal de Pago -->
      <DialogModal :show="showPaymentModal" @close="cerrarPaymentModal" maxWidth="md">
        <template #content>
          <div class="bg-white dark:bg-slate-950 rounded-xl shadow-xl w-full overflow-hidden border border-slate-100 dark:border-slate-800">
            <div class="px-8 py-6 bg-white dark:bg-slate-800 border-b border-slate-50 dark:border-slate-800 flex justify-between items-center">
              <h3 class="font-black uppercase tracking-[0.15em] text-sm text-slate-900 dark:text-white">Registrar Pago</h3>
              <button @click="cerrarPaymentModal" class="text-slate-300 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white transition-colors">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
            </div>
            <div class="p-8 space-y-6 dark:bg-black/50">
              <div v-if="selectedVenta" class="mb-4">
                <p class="text-[10px] text-slate-400 dark:text-slate-500 font-black uppercase tracking-wide mb-1">{{ selectedVenta.cliente?.nombre_razon_social || 'Venta' }} #{{ selectedVenta.numero_venta }}</p>
                <p class="text-2xl font-black text-slate-900 dark:text-white">Monto: ${{ formatearMoneda(selectedVenta.total) }}</p>
              </div>
              <div>
                <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2">Método de Pago</label>
                <select v-model="metodoPago" class="w-full py-4 px-5 bg-white dark:bg-slate-800 border-2 border-slate-100 dark:border-slate-800 rounded-xl font-bold text-slate-900 dark:text-white focus:border-slate-900 dark:focus:border-slate-400 focus:ring-0 transition-all">
                  <option value="">Seleccionar...</option>
                  <option value="efectivo">Efectivo</option>
                  <option value="transferencia">Transferencia</option>
                  <option value="cheque">Cheque</option>
                  <option value="tarjeta">Tarjeta</option>
                  <option value="otros">Otros</option>
                </select>
              </div>
              <div v-if="usuariosCobroLista.length">
                <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2">¿Quién recibió el dinero?</label>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">Define el corte de caja y reportes; puede ser distinto de quien registra la venta.</p>
                <select v-model="pagadoPorUserId" class="w-full py-4 px-5 bg-white dark:bg-slate-800 border-2 border-slate-100 dark:border-slate-800 rounded-xl font-bold text-slate-900 dark:text-white focus:border-slate-900 dark:focus:border-slate-400 focus:ring-0 transition-all">
                  <option v-for="u in usuariosCobroLista" :key="u.id" :value="String(u.id)">{{ u.name }}</option>
                </select>
              </div>
              <div v-if="['tarjeta', 'transferencia'].includes(metodoPago)">
                <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2">Cuenta Destino</label>
                <select v-model="cuentaBancariaId" class="w-full py-4 px-5 bg-white dark:bg-slate-800 border-2 border-slate-100 dark:border-slate-800 rounded-xl font-bold text-slate-900 dark:text-white focus:border-slate-900 dark:focus:border-slate-400 focus:ring-0 transition-all">
                  <option value="">Seleccionar cuenta</option>
                  <option v-for="cuenta in cuentasBancarias" :key="cuenta.id" :value="cuenta.id">{{ cuenta.nombre }} - {{ cuenta.banco }}</option>
                </select>
              </div>
              <div>
                <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2">Notas / Referencia</label>
                <textarea v-model="notasPago" rows="2" class="w-full px-5 py-4 bg-white dark:bg-slate-800 border-2 border-slate-100 dark:border-slate-800 rounded-xl font-bold text-slate-900 dark:text-white focus:border-slate-900 dark:focus:border-slate-400 focus:ring-0 transition-all" placeholder="Referencia de pago..."></textarea>
              </div>
            </div>
            <div class="px-8 py-6 bg-white/50 dark:bg-slate-950 border-t border-slate-100 dark:border-slate-800 flex flex-col gap-3">
              <button @click="confirmarPago" :disabled="loading" class="w-full py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-xl font-black uppercase text-[10px] tracking-[0.2em] shadow-xl disabled:opacity-50 flex items-center justify-center gap-3 transition-transform active:scale-95">
                <span v-if="loading" class="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin"></span>
                {{ loading ? 'Procesando...' : 'Confirmar Pago' }}
              </button>
              <button @click="cerrarPaymentModal" class="w-full py-3 font-black text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white uppercase text-[10px] tracking-wide transition-colors">Cancelar</button>
            </div>
          </div>
        </template>
      </DialogModal>

      <!-- Modal de Cancelación -->
      <DialogModal :show="showCancelModal" @close="cerrarCancelModal" maxWidth="md">
        <template #content>
          <div class="bg-white dark:bg-slate-950 rounded-xl shadow-xl w-full overflow-hidden border border-rose-100 dark:border-rose-900/30">
            <div class="px-8 py-6 bg-rose-50 dark:bg-rose-900/20 border-b border-rose-100 dark:border-rose-900/30 flex justify-between items-center">
              <h3 class="font-black uppercase tracking-[0.15em] text-sm text-rose-800 dark:text-rose-200">Cancelar Venta</h3>
              <button @click="cerrarCancelModal" class="text-rose-300 hover:text-rose-800 dark:text-rose-200 transition-colors">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
            </div>
            <div class="p-8 space-y-6">
              <div class="flex items-start gap-4 p-4 bg-rose-50 dark:bg-rose-900/10 rounded-xl border border-rose-100/50 dark:border-rose-900/20">
                <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-900/20 flex items-center justify-center flex-shrink-0">
                  <svg class="w-10 h-10 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                </div>
                <div>
                  <p class="text-[10px] font-black text-rose-800 dark:text-rose-400 uppercase tracking-wide mb-1">¡Atención!</p>
                  <p class="text-xs font-bold text-rose-600/80 dark:text-rose-400/60 leading-relaxed italic">Esta acción liberará el stock y cancelará los folios de las series vendidas. Esta operación NO se puede deshacer.</p>
                </div>
              </div>
              <div>
                <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2">Motivo de Cancelación</label>
                <textarea v-model="motivoCancelacion" rows="3" class="w-full px-5 py-4 bg-white dark:bg-slate-800 border-2 border-rose-100 dark:border-rose-900/30 rounded-xl font-bold text-slate-900 dark:text-white focus:border-rose-500 focus:ring-0 transition-all" placeholder="Describa el motivo..."></textarea>
              </div>
              <div class="flex items-center gap-2 p-4 bg-brand-50 dark:bg-brand-900/10 rounded-xl border border-brand-100 dark:border-brand-900/20">
                <input type="checkbox" v-model="forceWithPayments" id="forceCancel" class="w-4 h-4 rounded-xl border-2 border-brand-200 dark:border-brand-900/30 text-brand-600 focus:ring-0 bg-white dark:bg-slate-800 transition-all cursor-pointer">
                <label for="forceCancel" class="text-[10px] font-black text-brand-800 dark:text-brand-400 uppercase tracking-wide cursor-pointer">Forzar cancelación (Admin)</label>
              </div>
            </div>
            <div class="px-8 py-6 bg-rose-50 dark:bg-slate-950 border-t border-rose-100 dark:border-rose-900/30 flex flex-col gap-3">
              <button @click="confirmarCancelacion" :disabled="loading" class="w-full py-4 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-black uppercase text-[10px] tracking-[0.2em] shadow-xl transition-all flex items-center justify-center gap-3">
                {{ loading ? 'Cancelando...' : 'Confirmar Cancelación' }}
              </button>
              <button @click="cerrarCancelModal" class="w-full py-3 font-black text-rose-400 dark:text-rose-500 hover:text-rose-800 dark:text-rose-200 uppercase text-[10px] tracking-wide transition-colors">Abortar</button>
            </div>
          </div>
        </template>
      </DialogModal>

      <!-- Modal de Eliminación -->
      <DialogModal :show="showDeleteModal" @close="cerrarDeleteModal" maxWidth="md">
        <template #content>
          <div class="bg-white dark:bg-slate-950 rounded-xl shadow-xl w-full overflow-hidden border border-rose-100 dark:border-rose-900/30">
            <div class="px-8 py-10 flex flex-col items-center text-center">
              <div class="w-16 h-16 rounded-full bg-rose-50 dark:bg-rose-900/20 flex items-center justify-center mb-6">
                <svg class="w-10 h-10 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
              </div>
              <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-wide mb-2">Eliminar Definitivamente</h3>
              <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] leading-relaxed">¿Estás seguro de borrar la venta #{{ selectedVentaDelete?.numero_venta || selectedVentaDelete?.id }}? Esta acción es irreversible.</p>
            </div>
            <div class="px-8 py-6 bg-slate-50 dark:bg-black/50 border-t border-slate-100 dark:border-slate-800 flex flex-col gap-3">
              <button @click="ejecutarBorrado" :disabled="loading" class="w-full py-4 bg-rose-600 text-white rounded-xl font-black uppercase text-[10px] tracking-[0.2em] shadow-xl transition-all">
                {{ loading ? 'Borrando...' : 'Sí, Eliminar Registro' }}
              </button>
              <button @click="cerrarDeleteModal" class="w-full py-3 font-black text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white uppercase text-[10px] tracking-wide transition-colors">Mantener Registro</button>
            </div>
          </div>
        </template>
      </DialogModal>

      <!-- Modal de Enviar Email -->
      <DialogModal :show="showEmailModal" @close="cerrarEmailModal" maxWidth="md">
        <template #content>
          <div class="bg-white dark:bg-slate-950 rounded-xl shadow-xl w-full overflow-hidden border border-blue-100 dark:border-blue-900/30">
            <div class="px-8 py-10 flex flex-col items-center text-center">
              <div class="w-16 h-16 rounded-full bg-sky-50 dark:bg-sky-900/20 flex items-center justify-center mb-6">
                <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
              </div>
              <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-wide mb-2">Enviar Documento</h3>
              <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-6">Se enviará el PDF de la venta al cliente:</p>
              <div class="w-full p-4 bg-sky-50 dark:bg-blue-900/10 rounded-xl border border-blue-100 dark:border-blue-900/20 text-left">
                <p class="text-[9px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-wide mb-1">Destinatario</p>
                <p class="text-sm font-black text-slate-900 dark:text-white truncate">{{ selectedVentaEmail?.cliente?.nombre_razon_social }}</p>
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 italic mb-2">{{ selectedVentaEmail?.cliente?.email }}</p>
              </div>
            </div>
            <div class="px-8 py-6 bg-slate-50 dark:bg-black/50 border-t border-slate-100 dark:border-slate-800 flex flex-col gap-3">
              <button @click="confirmarEnviarEmail" :disabled="loading" class="w-full py-4 bg-blue-600 text-white rounded-xl font-black uppercase text-[10px] tracking-[0.2em] shadow-xl transition-all flex items-center justify-center gap-3">
                <span v-if="loading" class="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin"></span>
                {{ loading ? 'Enviando...' : 'Enviar Correo Ahora' }}
              </button>
              <button @click="cerrarEmailModal" class="w-full py-3 font-black text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white uppercase text-[10px] tracking-wide transition-colors">Cancelar</button>
            </div>
          </div>
        </template>
      </DialogModal>

      <!-- Loading overlay -->
      <div v-if="loading" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 backdrop-blur-sm">
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
