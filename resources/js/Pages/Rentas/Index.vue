<!-- /resources/js/Pages/Rentas/Index.vue -->
<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { ref, computed, onMounted } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import { generarPDF } from '@/Utils/pdfGenerator'
import axios from 'axios'

import RentasHeader from '@/Components/IndexComponents/RentasHeader.vue'

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

const page = usePage()
onMounted(() => {
  const flash = page.props.flash
  if (flash?.success) notyf.success(flash.success)
  if (flash?.error) notyf.error(flash.error)
})

// Props
const props = defineProps({
  rentas: { type: [Object, Array], required: true },
  stats: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  sorting: { type: Object, default: () => ({ sort_by: 'created_at', sort_direction: 'desc' }) },
  defaults: { type: Object, default: () => ({ ivaPorcentaje: 16 }) },
})

// Estado UI
const showModal = ref(false)
const modalMode = ref('details')
const selectedRenta = ref(null)
const selectedId = ref(null)

// Filtros
const searchTerm = ref(props.filters?.search ?? '')
const sortBy = ref('created_at-desc')
const filtroEstado = ref('')

// Paginación
const perPage = ref(10)

// Función para crear nueva renta
const crearNuevaRenta = () => {
  router.visit(route('rentas.create'))
}

// Función para limpiar filtros
const limpiarFiltros = () => {
  searchTerm.value = ''
  sortBy.value = 'created_at-desc'
  filtroEstado.value = ''
  router.visit(route('rentas.index'))
  notyf.success('Filtros limpiados correctamente')
}

// Próximos cobros desde el backend
const proximosCobros = computed(() => props.stats?.proximos_cobros || [])

// Datos
const rentasPaginator = computed(() => props.rentas)
const rentasData = computed(() => rentasPaginator.value?.data || [])

// Estadísticas (ahora incluye datos de cobranza del backend)
const estadisticas = computed(() => ({
  total: props.stats?.total ?? 0,
  activas: props.stats?.activas ?? 0,
  vencidas: props.stats?.vencidas ?? 0,
  cobros_proximos_7dias: props.stats?.cobros_proximos_7dias ?? 0,
  contratos_en_mora: props.stats?.contratos_en_mora ?? 0,
  monto_pendiente_total: props.stats?.monto_pendiente_total ?? 0,
  monto_vencido: props.stats?.monto_vencido ?? 0,
  ingresos_mensuales_esperados: props.stats?.ingresos_mensuales_esperados ?? 0,
  activasPorcentaje: props.stats?.activas > 0 ? Math.round((props.stats.activas / props.stats.total) * 100) : 0,
  vencidasPorcentaje: props.stats?.vencidas > 0 ? Math.round((props.stats.vencidas / props.stats.total) * 100) : 0
}))

// Transformación de datos con información de cobranza
const rentasDocumentos = computed(() => {
  const hoy = new Date()
  const IVA_RATE = (props.defaults?.ivaPorcentaje ?? 16) / 100
  
  return rentasData.value.map(r => {
    const cuentas = r.cuentas_por_cobrar || []
    const mensualidades = cuentas.filter(c => c.notas === 'Mensualidad')
    
    // Calcular próximo vencimiento
    const pendientes = mensualidades.filter(c => c.estado !== 'pagado')
    const proximasCuentas = pendientes
      .filter(c => new Date(c.fecha_vencimiento) >= hoy)
      .sort((a, b) => new Date(a.fecha_vencimiento) - new Date(b.fecha_vencimiento))
    const proximoVencimiento = proximasCuentas[0]?.fecha_vencimiento || null
    
    // Calcular meses en mora (vencidos no pagados)
    const vencidas = pendientes.filter(c => new Date(c.fecha_vencimiento) < hoy)
    const mesesMora = vencidas.length
    
    // Determinar estado de salud del contrato
    let saludCobranza = 'verde' // Al día
    if (mesesMora > 2) saludCobranza = 'rojo' // Crítico
    else if (mesesMora > 0) saludCobranza = 'amarillo' // Con mora
    else if (proximoVencimiento) {
      const diasParaVencer = Math.ceil((new Date(proximoVencimiento) - hoy) / (1000 * 60 * 60 * 24))
      if (diasParaVencer <= 7) saludCobranza = 'naranja' // Próximo a vencer
    }
    
    // Total pendiente
    const totalPendiente = pendientes.reduce((sum, c) => sum + parseFloat(c.monto_pendiente || 0), 0)
    
    // Calcular monto mensual CON IVA
    const montoSinIva = parseFloat(r.monto_mensual) || 0
    const montoConIva = Math.round(montoSinIva * (1 + IVA_RATE) * 100) / 100
    
    return {
      id: r.id,
      titulo: r.numero_contrato || `Contrato #${r.id}`,
      subtitulo: r.cliente?.nombre_razon_social || 'Sin cliente',
      estado: r.estado,
      pagoSinIva: montoSinIva,
      pago: montoConIva, // Ahora incluye IVA
      anticipoPagado: (r.deposito_garantia && r.deposito_garantia > 0) ? 'Pagado' : 'No pagado',
      extra: `Equipos: ${r.equipos?.length || 0} | Inicio: ${r.fecha_inicio ? new Date(r.fecha_inicio).toLocaleDateString('es-MX') : 'N/A'}`,
      fecha: r.created_at,
      raw: r,
      // Nuevos campos de cobranza
      proximoVencimiento,
      mesesMora,
      saludCobranza,
      totalPendiente
    }
  })
})

// Handlers
function handleSearchChange(newSearch) {
  searchTerm.value = newSearch
  router.get(route('rentas.index'), {
    search: newSearch,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    estado: filtroEstado.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

function handleEstadoChange(newEstado) {
  filtroEstado.value = newEstado
  router.get(route('rentas.index'), {
    search: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    estado: newEstado,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

function handleSortChange(newSort) {
  sortBy.value = newSort
  router.get(route('rentas.index'), {
    search: searchTerm.value,
    sort_by: newSort.split('-')[0],
    sort_direction: newSort.split('-')[1] || 'desc',
    estado: filtroEstado.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const verDetalles = (doc) => {
  selectedRenta.value = doc.raw
  modalMode.value = 'details'
  showModal.value = true
}

const editarRenta = (id) => {
  router.visit(route('rentas.edit', id))
}

const confirmarEliminacion = (id) => {
  selectedId.value = id
  modalMode.value = 'confirm'
  showModal.value = true
}

const eliminarRenta = () => {
  router.delete(route('rentas.destroy', selectedId.value), {
    preserveScroll: true,
    onSuccess: () => {
      notyf.success('Renta eliminada correctamente')
      showModal.value = false
      selectedId.value = null
      router.reload()
    },
    onError: (errors) => {
      notyf.error('No se pudo eliminar la renta')
    }
  })
}


const imprimirRenta = async (renta) => {
  const rentaConFecha = {
    ...renta,
    fecha: renta.fecha_inicio || renta.created_at || new Date().toISOString()
  }
  const validaciones = [
    [rentaConFecha.id, 'ID del documento no encontrado'],
    [rentaConFecha.cliente?.nombre, 'Datos del cliente no encontrados'],
    [Array.isArray(rentaConFecha.equipos) && rentaConFecha.equipos.length > 0, 'Lista de equipos no válida'],
    [rentaConFecha.fecha, 'Fecha no especificada']
  ]
  for (const [cond, msg] of validaciones) {
    if (!cond) return notyf.error(`Error: ${msg}`)
  }
  try {
    notyf.success('Generando PDF...')
    await generarPDF('Contrato de Renta', rentaConFecha)
    notyf.success('PDF generado correctamente')
  } catch (error) {
    notyf.error(`Error al generar PDF: ${error.message}`)
  }
}

const suspenderRenta = async (renta) => {
  if (!confirm(`¿Suspender la renta #${renta.numero_contrato}? Esto liberará los equipos.`)) return
  try {
    const response = await axios.post(route('rentas.suspender', renta.id))
    if (response.data.success) {
      notyf.success(response.data.message || 'Renta suspendida correctamente')
      router.reload()
    } else {
      notyf.error(response.data.error || 'Error al suspender la renta')
    }
  } catch (error) {
    const msg = error.response?.data?.error || 'Error al suspender la renta'
    notyf.error(msg)
  }
}

const reactivarRenta = async (renta) => {
  if (!confirm(`¿Reactivar la renta #${renta.numero_contrato}? Esto marcará los equipos como rentados.`)) return
  try {
    const response = await axios.post(route('rentas.reactivar', renta.id))
    if (response.data.success) {
      notyf.success(response.data.message || 'Renta reactivada correctamente')
      router.reload()
    } else {
      notyf.error(response.data.error || 'Error al reactivar la renta')
    }
  } catch (error) {
    const msg = error.response?.data?.error || 'Error al reactivar la renta'
    notyf.error(msg)
  }
}

const finalizarRenta = async (renta) => {
  if (!confirm(`¿Finalizar la renta #${renta.numero_contrato}? Esto liberará permanentemente los equipos.`)) return
  try {
    const response = await axios.post(route('rentas.finalizar', renta.id))
    if (response.data.success) {
      notyf.success(response.data.message || 'Renta finalizada correctamente')
      router.reload()
    } else {
      notyf.error(response.data.error || 'Error al finalizar la renta')
    }
  } catch (error) {
    const msg = error.response?.data?.error || 'Error al finalizar la renta'
    notyf.error(msg)
  }
}

const renovarRenta = async (renta) => {
  const meses = prompt('¿Cuántos meses deseas renovar?', '12')
  if (!meses || isNaN(meses) || meses <= 0) return

  try {
    const response = await axios.post(route('rentas.renovar', renta.id), {
      meses_renovacion: parseInt(meses)
    })
    if (response.data.success) {
      notyf.success(response.data.message || 'Renta renovada correctamente')
      router.reload()
    } else {
      notyf.error(response.data.error || 'Error al renovar la renta')
    }
  } catch (error) {
    const msg = error.response?.data?.error || 'Error al renovar la renta'
    notyf.error(msg)
  }
}

// Paginación
const paginationData = computed(() => {
  const p = rentasPaginator.value || {}
  return {
    currentPage: p.current_page ?? 1,
    lastPage:    p.last_page ?? 1,
    perPage:     p.per_page ?? 10,
    from:        p.from ?? 0,
    to:          p.to ?? 0,
    total:       p.total ?? 0,
    prevPageUrl: p.prev_page_url ?? null,
    nextPageUrl: p.next_page_url ?? null,
    links:       p.links ?? []
  }
})

const handlePerPageChange = (newPerPage) => {
  router.get(route('rentas.index'), {
    ...props.filters,
    ...props.sorting,
    per_page: newPerPage,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const handlePageChange = (newPage) => {
  router.get(route('rentas.index'), {
    ...props.filters,
    ...props.sorting,
    page: newPage
  }, { preserveState: true, preserveScroll: true })
}

// Helpers
const formatNumber = (num) => new Intl.NumberFormat('es-ES').format(num)
const formatearFecha = (date) => {
  if (!date) return 'Fecha no disponible'
  try {
    const d = new Date(date)
    return d.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' })
  } catch {
    return 'Fecha inválida'
  }
}

const obtenerClasesEstado = (estado) => {
  const clases = {
    'activo': 'bg-emerald-100 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200',
    'proximo_vencimiento': 'bg-brand-100 text-amber-800',
    'vencido': 'bg-rose-100 text-rose-800 dark:text-rose-200 dark:text-rose-200',
    'moroso': 'bg-rose-200 text-rose-800 dark:text-rose-200',
    'suspendido': 'bg-brand-100 text-brand-800 dark:text-brand-200 dark:text-amber-200',
    'finalizado': 'bg-slate-100 text-slate-500',
    'anulado': 'bg-slate-100 text-slate-500'
  }
  return clases[estado] || 'bg-slate-100 text-slate-700'
}

const obtenerLabelEstado = (estado) => {
  const labels = {
    'activo': 'Activo',
    'proximo_vencimiento': 'Próximo Vencimiento',
    'vencido': 'Vencido',
    'moroso': 'Moroso',
    'suspendido': 'Suspendido',
    'finalizado': 'Finalizado',
    'anulado': 'Anulado'
  }
  return labels[estado] || 'Pendiente'
}
</script>

<template>
  <Head title="Rentas" />
  <div class="rentas-index min-h-screen bg-[var(--ui-surface)]">
    <div class="w-full px-6 py-8">
      <!-- Header específico de rentas -->
      <RentasHeader
        :total="estadisticas.total"
        :activas="estadisticas.activas"
        :cobros_proximos_7dias="estadisticas.cobros_proximos_7dias"
        :vencidas="estadisticas.vencidas"
        :contratos_en_mora="estadisticas.contratos_en_mora"
        :monto_vencido="estadisticas.monto_vencido"
        :ingresos_mensuales="estadisticas.ingresos_mensuales_esperados"
        v-model:search-term="searchTerm"
        v-model:sort-by="sortBy"
        v-model:filtro-estado="filtroEstado"
        @crear-nueva="crearNuevaRenta"
        @search-change="handleSearchChange"
        @filtro-estado-change="handleEstadoChange"
        @sort-change="handleSortChange"
        @limpiar-filtros="limpiarFiltros"
      />

      <!-- Sección de Cobros Próximos -->
      <div v-if="proximosCobros.length > 0" class="mt-6 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-orange-50 to-amber-50">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-10 h-10 bg-brand-100 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-slate-900">Cobros Próximos</h3>
                <p class="text-sm text-slate-500">Próximos vencimientos de mensualidades</p>
              </div>
            </div>
            <span class="px-3 py-1 bg-brand-100 text-brand-800 rounded-full text-sm font-medium">
              {{ proximosCobros.length }} pendientes
            </span>
          </div>
        </div>
        <div class="divide-y divide-slate-100">
          <div v-for="cobro in proximosCobros" :key="cobro.id" class="px-6 py-4 hover:bg-white transition-colors duration-150">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-4">
                <div 
                  class="w-2 h-2 rounded-full"
                  :class="{
                    'bg-brand-500': cobro.dias_restantes > 7,
                    'bg-orange-400': cobro.dias_restantes >= 3 && cobro.dias_restantes <= 7,
                    'bg-brand-500': cobro.dias_restantes < 3
                  }"
                  :title="cobro.dias_restantes <= 0 ? 'Vencido' : `${cobro.dias_restantes} días restantes`"
                ></div>
                <div>
                  <p class="font-medium text-slate-900">{{ cobro.cliente }}</p>
                  <p class="text-sm text-slate-500">{{ cobro.numero_contrato }} • {{ cobro.notas }}</p>
                </div>
              </div>
              <div class="text-right">
                <p class="font-semibold text-slate-900">${{ formatNumber(cobro.monto) }}</p>
                <p class="text-sm" :class="{
                  'text-emerald-600': cobro.dias_restantes > 7,
                  'text-orange-600': cobro.dias_restantes >= 3 && cobro.dias_restantes <= 7,
                  'text-rose-600': cobro.dias_restantes < 3
                }">
                  {{ cobro.dias_restantes === 0 ? 'Vence hoy' : cobro.dias_restantes === 1 ? 'Vence mañana' : `En ${cobro.dias_restantes} días` }}
                </p>
              </div>
              <Link 
                :href="route('rentas.show', cobro.renta_id)" 
                class="ml-4 px-3 py-1.5 text-sm bg-sky-50 dark:bg-sky-900/20 text-blue-600 rounded-xl hover:bg-sky-100 transition-colors duration-150"
              >
                Ver
              </Link>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabla -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-800/50">
              <tr>
                <th class="px-4 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Contrato</th>
                <th class="px-4 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Cliente</th>
                <th class="px-4 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Mensualidad</th>
                <th class="px-4 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Próximo Venc.</th>
                <th class="px-4 py-4 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">Cobranza</th>
                <th class="px-4 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Estado</th>
                <th class="px-4 py-4 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
              <tr v-for="renta in rentasDocumentos" :key="renta.id" class="hover:bg-white transition-colors duration-150">
                <td class="px-4 py-4">
                  <div class="text-sm font-medium text-slate-900">{{ renta.titulo }}</div>
                  <div class="text-xs text-slate-500">{{ formatearFecha(renta.fecha) }}</div>
                </td>
                <td class="px-4 py-4">
                  <div class="text-sm text-slate-700">{{ renta.subtitulo }}</div>
                </td>
                <td class="px-4 py-4">
                  <div class="text-sm font-semibold text-slate-900">${{ formatNumber(renta.pago) }}</div>
                  <div class="text-xs text-slate-500">IVA incluido</div>
                </td>
                <td class="px-4 py-4">
                  <div v-if="renta.proximoVencimiento" class="text-sm">
                    <span :class="renta.saludCobranza === 'naranja' ? 'text-brand-600 font-medium' : 'text-slate-700'">
                      {{ formatearFecha(renta.proximoVencimiento) }}
                    </span>
                  </div>
                  <div v-else class="text-sm text-slate-400">—</div>
                </td>
                <td class="px-4 py-4 text-center">
                  <!-- Indicador de salud de cobranza -->
                  <div class="flex items-center justify-center gap-1">
                    <span 
                      :class="{
                        'bg-brand-500': renta.saludCobranza === 'verde',
                        'bg-orange-400': renta.saludCobranza === 'naranja',
                        'bg-brand-500': renta.saludCobranza === 'amarillo',
                        'bg-brand-500': renta.saludCobranza === 'rojo'
                      }"
                      class="w-2 h-2 rounded-full"
                      :title="renta.saludCobranza === 'verde' ? 'Al día' : renta.saludCobranza === 'naranja' ? 'Próximo a vencer' : renta.saludCobranza === 'amarillo' ? 'Con mora' : 'Crítico'"
                    ></span>
                    <span v-if="renta.mesesMora > 0" class="text-xs font-medium text-rose-600">
                      {{ renta.mesesMora }} {{ renta.mesesMora === 1 ? 'mes' : 'meses' }}
                    </span>
                  </div>
                </td>
                <td class="px-4 py-4">
                  <span :class="obtenerClasesEstado(renta.estado)" class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium">
                    {{ obtenerLabelEstado(renta.estado) }}
                  </span>
                </td>
                <td class="px-4 py-4 text-right">
                  <div class="flex items-center justify-end gap-1">
                    <!-- Botón Ver -->
                    <Link 
                      :href="route('rentas.show', renta.id)" 
                      class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-900/20 text-blue-600 hover:bg-sky-100 transition-all duration-200" 
                      title="Ver detalles"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </Link>
                    
                    <!-- Botón Editar -->
                    <button 
                      @click="editarRenta(renta.id)" 
                      class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-brand-50 dark:bg-brand-900/20 text-brand-600 hover:bg-brand-100 transition-all duration-200" 
                      title="Editar"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    
                    <!-- Botón PDF Contrato -->
                    <a
                      :href="route('rentas.contrato', renta.id)"
                      target="_blank"
                      class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 text-white text-xs font-medium hover:from-rose-600 hover:to-rose-700 transition-all duration-200 shadow-sm hover:shadow"
                      title="Descargar Contrato PDF"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                      </svg>
                      <span class="hidden sm:inline">PDF</span>
                    </a>
                    
                    <!-- Menú de acciones adicionales -->
                    <div class="relative" v-if="renta.raw.estado !== 'finalizado' && renta.raw.estado !== 'anulado'">
                      <div class="flex items-center gap-1">
                        <!-- Renovar -->
                        <button
                          v-if="['activo', 'proximo_vencimiento', 'vencido'].includes(renta.raw.estado)"
                          @click="renovarRenta(renta.raw)"
                          class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 hover:bg-sky-100 transition-all duration-200"
                          title="Renovar contrato"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                          </svg>
                        </button>
                        
                        <!-- Suspender (solo si está activo) -->
                        <button
                          v-if="renta.raw.estado === 'activo'"
                          @click="suspenderRenta(renta.raw)"
                          class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-orange-50 text-brand-600 hover:bg-brand-100 transition-all duration-200"
                          title="Suspender renta"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                          </svg>
                        </button>
                        
                        <!-- Reactivar (solo si está suspendido) -->
                        <button
                          v-if="renta.raw.estado === 'suspendido'"
                          @click="reactivarRenta(renta.raw)"
                          class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 hover:bg-emerald-100 transition-all duration-200"
                          title="Reactivar renta"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                          </svg>
                        </button>
                        
                        <!-- Finalizar -->
                        <button
                          v-if="['activo', 'proximo_vencimiento', 'vencido'].includes(renta.raw.estado)"
                          @click="finalizarRenta(renta.raw)"
                          class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-purple-50 text-purple-600 hover:bg-purple-100 transition-all duration-200"
                          title="Finalizar contrato"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                          </svg>
                        </button>
                      </div>
                    </div>
                    
                    <!-- Botón Eliminar -->
                    <button 
                      @click="confirmarEliminacion(renta.id)" 
                      class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-900/20 text-rose-600 hover:bg-rose-100 transition-all duration-200" 
                      title="Eliminar"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="rentasDocumentos.length === 0">
                <td colspan="7" class="px-6 py-16 text-center">
                  <div class="flex flex-col items-center space-y-6">
                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center">
                      <svg class="w-10 h-10 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                    </div>
                    <div class="space-y-1">
                      <p class="text-slate-700 font-medium">No hay rentas</p>
                      <p class="text-sm text-slate-500">Las rentas aparecerán aquí cuando se creen</p>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div v-if="paginationData.lastPage > 1" class="bg-white border-t border-slate-200 px-4 py-3 sm:px-6">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4">
              <p class="text-sm text-slate-700">
                Mostrando {{ paginationData.from }} - {{ paginationData.to }} de {{ paginationData.total }} resultados
              </p>
              <select
                :value="paginationData.perPage"
                @change="handlePerPageChange(parseInt($event.target.value))"
                class="border border-slate-300 rounded-xl text-sm py-1 px-2 bg-white"
              >
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
              </select>
            </div>

            <nav class="relative z-0 inline-flex rounded-xl shadow-sm -space-x-px">
              <button
                v-if="paginationData.prevPageUrl"
                @click="handlePageChange(paginationData.currentPage - 1)"
                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-slate-300 bg-white text-sm font-medium text-slate-500 hover:bg-white"
              >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </button>

              <span v-else class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-slate-300 bg-slate-100 text-sm font-medium text-slate-400">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </span>

              <button
                v-for="page in [paginationData.currentPage - 1, paginationData.currentPage, paginationData.currentPage + 1].filter(p => p > 0 && p <= paginationData.lastPage)"
                :key="page"
                @click="handlePageChange(page)"
                :class="page === paginationData.currentPage ? 'bg-sky-50 dark:bg-sky-900/20 border-blue-500 text-blue-600' : 'bg-white border-slate-300 text-slate-500 hover:bg-white'"
                class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
              >
                {{ page }}
              </button>

              <button
                v-if="paginationData.nextPageUrl"
                @click="handlePageChange(paginationData.currentPage + 1)"
                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-slate-300 bg-white text-sm font-medium text-slate-500 hover:bg-white"
              >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
              </button>

              <span v-else class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-slate-300 bg-slate-100 text-sm font-medium text-slate-400">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
              </span>
            </nav>
          </div>
        </div>
      </div>

      <!-- Modal mejorado -->
      <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showModal = false">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto custom-scrollbar">
          <!-- Header del modal -->
          <div class="flex items-center justify-between p-6 border-b border-slate-200">
            <h3 class="text-lg font-medium text-slate-900">
              {{ modalMode === 'details' ? 'Detalles de la Renta' : 'Confirmar Eliminación' }}
            </h3>
            <button @click="showModal = false" class="text-slate-400 hover:text-brand-600 transition-colors">
              <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="p-6">
            <div v-if="modalMode === 'details' && selectedRenta">
              <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="space-y-3">
                    <div>
                      <label class="block text-sm font-medium text-slate-700">Número de Contrato</label>
                      <p class="mt-1 text-sm text-slate-900 bg-white px-3 py-2 rounded-xl">{{ selectedRenta.numero_contrato || 'N/A' }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-slate-700">Cliente</label>
                      <p class="mt-1 text-sm text-slate-900 bg-white px-3 py-2 rounded-xl">{{ selectedRenta.cliente?.nombre || 'Sin cliente' }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-slate-700">Fecha de Inicio</label>
                      <p class="mt-1 text-sm text-slate-900 bg-white px-3 py-2 rounded-xl">{{ formatearFecha(selectedRenta.fecha_inicio) }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-slate-700">Fecha de Fin</label>
                      <p class="mt-1 text-sm text-slate-900 bg-white px-3 py-2 rounded-xl">{{ formatearFecha(selectedRenta.fecha_fin) }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-slate-700">Estado</label>
                      <span :class="obtenerClasesEstado(selectedRenta.estado)" class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-medium mt-1">
                        {{ obtenerLabelEstado(selectedRenta.estado) }}
                      </span>
                    </div>
                  </div>
                  <div class="space-y-3">
                    <div>
                      <label class="block text-sm font-medium text-slate-700">Equipos Rentados</label>
                      <p class="mt-1 text-sm text-slate-900 bg-white px-3 py-2 rounded-xl">{{ selectedRenta.equipos?.length || 0 }} equipos</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-slate-700">Fecha de Creación</label>
                      <p class="mt-1 text-sm text-slate-900 bg-white px-3 py-2 rounded-xl">{{ formatearFecha(selectedRenta.created_at) }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-slate-700">Última Actualización</label>
                      <p class="mt-1 text-sm text-slate-900 bg-white px-3 py-2 rounded-xl">{{ formatearFecha(selectedRenta.updated_at) }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="modalMode === 'confirm'">
              <div class="text-center">
                <div class="w-10 h-10 mx-auto bg-rose-100 rounded-full flex items-center justify-center mb-4">
                  <svg class="w-10 h-10 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                  </svg>
                </div>
                <h3 class="text-lg font-medium text-slate-900 mb-2">¿Eliminar Renta?</h3>
                <p class="text-sm text-slate-500 mb-4">
                  ¿Estás seguro de que deseas eliminar la renta <strong>{{ selectedRenta?.numero_contrato }}</strong>?
                  Esta acción no se puede deshacer.
                </p>
              </div>
            </div>
          </div>

          <!-- Footer del modal -->
          <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-200 bg-white">
            <button @click="showModal = false" class="px-4 py-2 bg-slate-300 text-slate-700 rounded-xl hover:bg-slate-400 transition-colors">
              {{ modalMode === 'details' ? 'Cerrar' : 'Cancelar' }}
            </button>
            <div v-if="modalMode === 'details'" class="flex gap-2">
              <button @click="editarRenta(selectedRenta.id)" class="px-4 py-2 bg-brand-600 text-white rounded-xl hover:bg-brand-700 transition-colors">
                Editar
              </button>
            </div>
            <button v-if="modalMode === 'confirm'" @click="eliminarRenta" class="px-4 py-2 bg-rose-600 text-white rounded-xl hover:bg-rose-700 transition-colors">
              Eliminar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.rentas-index {
  min-height: 100vh;
}
</style>




