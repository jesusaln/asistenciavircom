<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { ref, computed, watch } from 'vue'
import { Head, router, Link } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  format,
  parseISO,
  startOfWeek,
  endOfWeek,
  subWeeks,
  subDays,
  startOfMonth,
  endOfMonth,
} from 'date-fns'
import { es } from 'date-fns/locale'
import { route } from 'ziggy-js'

const props = defineProps({
  registros: { type: Array, default: () => [] },
  resumen_por_tecnico: { type: Array, default: () => [] },
  tecnicos: { type: Array, default: () => [] },
  estadisticas: { type: Object, default: () => ({}) },
  total_rows: { type: Number, default: 0 },
  truncated: { type: Boolean, default: false },
  filtros: { type: Object, default: () => ({}) },
})

const fechaInicio = ref(props.filtros.fecha_inicio || '')
const fechaFin = ref(props.filtros.fecha_fin || '')
const tecnicoId = ref(props.filtros.tecnico_id ?? '')
const estado = ref(props.filtros.estado || 'todos')
const soloConTecnico = ref(
  props.filtros.solo_con_tecnico !== undefined && props.filtros.solo_con_tecnico !== null
    ? !!props.filtros.solo_con_tecnico
    : true
)
const busqueda = ref('')
const loading = ref(false)
const expanded = ref({})
/** Atajo de periodo activo (resalta botones); null si el usuario editó fechas a mano. */
const presetActivo = ref(null)

const estadosOpciones = [
  { value: 'todos', label: 'Todos los estados' },
  { value: 'pendiente', label: 'Pendiente' },
  { value: 'pendiente_asignacion', label: 'Pendiente asignación' },
  { value: 'programado', label: 'Programado' },
  { value: 'en_proceso', label: 'En proceso' },
  { value: 'completado', label: 'Completado' },
  { value: 'cancelado', label: 'Cancelado' },
  { value: 'reprogramado', label: 'Reprogramado' },
]

const tiposServicio = {
  instalacion: 'Instalación',
  mantenimiento: 'Mantenimiento',
  reparacion: 'Reparación',
  garantia: 'Garantía',
  diagnostico: 'Diagnóstico',
  servicio_limpieza: 'Limpieza',
  otro: 'Otro',
}

const prioridadLabel = (p) =>
  ({ baja: 'Baja', media: 'Media', alta: 'Alta', urgente: 'Urgente' }[p] || p || '—')

watch(
  () => [props.filtros?.fecha_inicio, props.filtros?.fecha_fin],
  ([ini, fin]) => {
    if (ini != null && ini !== '') fechaInicio.value = ini
    if (fin != null && fin !== '') fechaFin.value = fin
  }
)

const aplicar = () => {
  loading.value = true
  router.get(
    route('reportes.citas-por-tecnico'),
    {
      fecha_inicio: fechaInicio.value,
      fecha_fin: fechaFin.value,
      tecnico_id: tecnicoId.value === '' ? null : tecnicoId.value,
      estado: estado.value,
      solo_con_tecnico: soloConTecnico.value ? 1 : 0,
    },
    {
      preserveState: true,
      preserveScroll: true,
      onFinish: () => {
        loading.value = false
      },
    }
  )
}

const onFechaManual = () => {
  presetActivo.value = null
  aplicar()
}

/**
 * Semana laboral MX: lunes a domingo (weekStartsOn: 1).
 * @param {'hoy'|'ayer'|'semana'|'semana_ant'|'mes'} tipo
 */
const aplicarPresetPeriodo = (tipo) => {
  const now = new Date()
  let a = ''
  let b = ''
  switch (tipo) {
    case 'hoy':
      a = b = format(now, 'yyyy-MM-dd')
      break
    case 'ayer': {
      const y = subDays(now, 1)
      a = b = format(y, 'yyyy-MM-dd')
      break
    }
    case 'semana': {
      const s = startOfWeek(now, { weekStartsOn: 1 })
      const e = endOfWeek(now, { weekStartsOn: 1 })
      a = format(s, 'yyyy-MM-dd')
      b = format(e, 'yyyy-MM-dd')
      break
    }
    case 'semana_ant': {
      const ref = subWeeks(now, 1)
      const s = startOfWeek(ref, { weekStartsOn: 1 })
      const e = endOfWeek(ref, { weekStartsOn: 1 })
      a = format(s, 'yyyy-MM-dd')
      b = format(e, 'yyyy-MM-dd')
      break
    }
    case 'mes': {
      const s = startOfMonth(now)
      const e = endOfMonth(now)
      a = format(s, 'yyyy-MM-dd')
      b = format(e, 'yyyy-MM-dd')
      break
    }
    default:
      return
  }
  fechaInicio.value = a
  fechaFin.value = b
  presetActivo.value = tipo
  aplicar()
}

const fmtFecha = (iso) => {
  if (!iso) return '—'
  try {
    return format(parseISO(iso), "d MMM yyyy, HH:mm", { locale: es })
  } catch {
    return iso
  }
}

const fmtMoneda = (n) => {
  if (n == null || n === '') return '—'
  return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(n))
}

const fmtMin = (m) => {
  if (m == null) return '—'
  const h = Math.floor(m / 60)
  const min = m % 60
  if (h > 0) return `${h}h ${min}m`
  return `${min} min`
}

const estadoChip = (est) => {
  const map = {
    pendiente: 'bg-brand-100 dark:bg-brand-500/15 text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-brand-200 ring-1 ring-brand-200 dark:ring-brand-500/30',
    pendiente_asignacion: 'bg-brand-100 dark:bg-brand-500/15 text-brand-700 dark:text-orange-200 ring-1 ring-orange-200 dark:ring-brand-500/30',
    programado: 'bg-sky-100 dark:bg-sky-500/15 text-sky-700 dark:text-sky-200 ring-1 ring-sky-200 dark:ring-sky-500/30',
    en_proceso: 'bg-violet-100 dark:bg-violet-500/15 text-violet-700 dark:text-violet-200 ring-1 ring-violet-200 dark:ring-violet-500/30',
    completado: 'bg-emerald-100 dark:bg-brand-500/15 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-emerald-200 ring-1 ring-emerald-200 dark:ring-emerald-500/30',
    cancelado: 'bg-rose-100 dark:bg-brand-500/15 text-rose-800 dark:text-rose-200 dark:text-rose-200 dark:text-rose-200 ring-1 ring-rose-200 dark:ring-rose-500/30',
    reprogramado: 'bg-cyan-100 dark:bg-cyan-500/15 text-cyan-700 dark:text-cyan-200 ring-1 ring-cyan-200 dark:ring-cyan-500/30',
  }
  return map[est] || 'bg-slate-100 dark:bg-slate-600/40 text-slate-700 dark:text-slate-200 ring-1 ring-slate-200 dark:ring-slate-500/30'
}

const estadoLegible = (est) =>
  estadosOpciones.find((e) => e.value === est)?.label || est || '—'

const tipoServicioLegible = (t) => tiposServicio[t] || t || '—'

const registrosFiltrados = computed(() => {
  if (!busqueda.value.trim()) return props.registros
  const s = busqueda.value.toLowerCase()
  return props.registros.filter((r) => {
    const blob = [
      r.tecnico_nombre,
      r.tecnico_email,
      r.cliente,
      r.cliente_telefono,
      r.folio,
      r.tipo_servicio,
      r.direccion,
      r.equipo,
      r.problema_reportado,
      r.trabajo_realizado,
      r.descripcion,
      r.notas,
      r.estado,
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()
    return blob.includes(s)
  })
})

const toggleRow = (id) => {
  expanded.value = { ...expanded.value, [id]: !expanded.value[id] }
}

const periodoLabel = computed(() => {
  if (!fechaInicio.value && !fechaFin.value) return 'Periodo'
  try {
    if (fechaInicio.value === fechaFin.value) {
      return format(parseISO(fechaInicio.value), "EEEE d MMM yyyy", { locale: es })
    }
    return `${format(parseISO(fechaInicio.value), 'd MMM', { locale: es })} → ${format(
      parseISO(fechaFin.value),
      'd MMM yyyy',
      { locale: es }
    )}`
  } catch {
    return `${fechaInicio.value || '…'} → ${fechaFin.value || '…'}`
  }
})

const pctCompletadas = computed(() => {
  const m = props.estadisticas?.mostrados ?? 0
  const c = props.estadisticas?.completadas ?? 0
  if (!m) return 0
  return Math.round((c / m) * 100)
})

/** Métricas sobre las filas ya cargadas (coinciden con “en pantalla” salvo truncamiento global). */
const statsFilas = computed(() => {
  const rows = props.registros || []
  const n = rows.length
  let canceladas = 0
  let pipeline = 0
  let sumMinCompletadas = 0
  let nCompletadasConMin = 0
  let sumImporte = 0
  for (const r of rows) {
    const e = r.estado
    if (e === 'cancelado') canceladas++
    else if (['pendiente', 'pendiente_asignacion', 'programado', 'en_proceso', 'reprogramado'].includes(e)) {
      pipeline++
    }
    if (e === 'completado' && r.tiempo_servicio_minutos != null) {
      sumMinCompletadas += Number(r.tiempo_servicio_minutos) || 0
      nCompletadasConMin++
    }
    sumImporte += Number(r.total) || 0
  }
  return {
    canceladas,
    pipeline,
    importe_promedio: n ? sumImporte / n : 0,
    mins_promedio_completadas:
      nCompletadasConMin > 0 ? Math.round(sumMinCompletadas / nCompletadasConMin) : null,
  }
})

const pctCanceladasFilas = computed(() => {
  const n = props.estadisticas?.mostrados ?? 0
  if (!n) return 0
  return Math.round((statsFilas.value.canceladas / n) * 100)
})

const presetBtnClass = (id) =>
  presetActivo.value === id
    ? 'bg-cyan-600/30 text-cyan-100 ring-2 ring-cyan-400/60 shadow-xl shadow-cyan-900/20'
    : 'bg-slate-950/60 text-slate-300 ring-1 ring-white/10 hover:bg-slate-800/80 hover:text-white'

/** Modal en este reporte: ventas del cliente de la cita + vincular (sin Teleport / sin salir a otra pantalla). */
const modalVentaAbierto = ref(false)
const modalCita = ref(null)
const modalVentas = ref([])
const modalVentaCargando = ref(false)
const modalVentaError = ref(null)
const vinculandoVentaId = ref(null)

const cerrarModalVenta = () => {
  modalVentaAbierto.value = false
  modalCita.value = null
  modalVentas.value = []
  modalVentaError.value = null
  vinculandoVentaId.value = null
}

const fmtFechaVentaModal = (iso) => {
  if (!iso) return '—'
  try {
    return format(parseISO(iso), 'd MMM yyyy, HH:mm', { locale: es })
  } catch {
    return String(iso)
  }
}

const estadoVentaModal = (e) => {
  if (e == null) return '—'
  if (typeof e === 'object' && e !== null && 'value' in e) return String(e.value)
  return String(e)
}

const abrirModalVentaCita = async (r) => {
  modalCita.value = r
  modalVentaAbierto.value = true
  modalVentas.value = []
  modalVentaError.value = null
  modalVentaCargando.value = true
  try {
    const { data } = await axios.get(route('citas.ventas-cliente-candidatas', r.id), {
      headers: { Accept: 'application/json' },
    })
    modalVentas.value = Array.isArray(data.ventas) ? data.ventas : []
  } catch (e) {
    modalVentaError.value =
      e.response?.data?.message || 'No se pudieron cargar las ventas del cliente.'
    modalVentas.value = []
  } finally {
    modalVentaCargando.value = false
  }
}

const vincularVentaDesdeModal = async (ventaId) => {
  const c = modalCita.value
  if (!c?.id) return
  vinculandoVentaId.value = ventaId
  modalVentaError.value = null
  try {
    await axios.post(
      route('citas.vincular-venta', c.id),
      { venta_id: ventaId },
      { headers: { Accept: 'application/json', 'Content-Type': 'application/json' } },
    )
    cerrarModalVenta()
    aplicar()
  } catch (e) {
    modalVentaError.value =
      e.response?.data?.message ||
      e.response?.data?.errors?.venta_id?.[0] ||
      e.response?.data?.errors?.cita_id?.[0] ||
      'No se pudo vincular la venta.'
  } finally {
    vinculandoVentaId.value = null
  }
}

/** Modal: detalle completo de venta (sin salir del reporte) */
const detalleVentaAbierto = ref(false)
const detalleVenta = ref(null)
const detalleVentaCargando = ref(false)
const detalleVentaError = ref(null)

const abrirDetalleVenta = async (ventaId) => {
  if (!ventaId) return
  detalleVentaAbierto.value = true
  detalleVenta.value = null
  detalleVentaCargando.value = true
  detalleVentaError.value = null
  try {
    const { data } = await axios.get(`/api/ventas/${ventaId}`, {
      headers: { Accept: 'application/json' },
    })
    // El API envuelve la data en un campo 'data' por el ApiResponse trait
    detalleVenta.value = data.data || data
  } catch (e) {
    detalleVentaError.value = e.response?.data?.message || 'Error al cargar los detalles de la venta.'
  } finally {
    detalleVentaCargando.value = false
  }
}

const cerrarDetalleVenta = () => {
  detalleVentaAbierto.value = false
  detalleVenta.value = null
}
</script>

<template>
  <Head title="Citas por técnico (detalle)" />

  <AppLayout title="Citas por técnico">
    <div
      class="report-citas-tecnico min-h-[calc(100vh-5rem)] w-full max-w-none min-w-0 bg-[var(--ui-surface)] text-slate-800 dark:text-slate-100 px-2 sm:px-3 lg:px-4 pb-10 pt-2 sm:pt-4 border-t border-slate-200 dark:border-slate-800 transition-all"
    >
      <!-- Hero + acciones -->
      <div class="w-full min-w-0 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6 mb-6 sm:mb-8">
        <div>
          <p class="text-[10px] font-black uppercase tracking-[0.35em] text-cyan-600 dark:text-cyan-400 mb-2">Reportes operativos</p>
          <h1 class="text-2xl sm:text-2xl font-black tracking-tight text-slate-900 dark:text-white">
            Citas por técnico
            <span class="block sm:inline sm:ml-2 text-base sm:text-lg font-bold text-slate-500 dark:text-slate-400">detalle ejecutivo</span>
          </h1>
          <p class="mt-2 max-w-3xl text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
            Agenda, cliente, ubicación, equipo, tiempos de servicio e importes en el periodo.
            <span class="text-slate-400 dark:text-slate-500">No incluye tickets de soporte.</span>
            Periodo: <span class="text-slate-800 dark:text-slate-200 font-semibold">{{ periodoLabel }}</span>
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <Link
            :href="route('panel')"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-wide bg-white dark:bg-white/5 hover:bg-slate-100 dark:hover:bg-white/10 text-slate-700 dark:text-slate-200 ring-1 ring-slate-200 dark:ring-white/10 transition-colors shadow-sm"
          >
            ← Reportes
          </Link>
        </div>
      </div>

      <div class="w-full min-w-0 max-w-none space-y-5 sm:space-y-6">
        <!-- Filtros sticky: z-10 para no tapar el sidebar (aside z-20); mismo ancho que el resto del reporte -->
        <div
          class="sticky top-0 z-10 -mx-1 px-1 py-3 bg-slate-50/90 dark:bg-slate-950/90 backdrop-blur-md border-b border-slate-200/60 dark:border-white/5 rounded-2xl sm:rounded-3xl"
        >
          <div class="rounded-2xl sm:rounded-3xl bg-white dark:bg-slate-800/80 ring-1 ring-slate-200 dark:ring-white/10 p-4 sm:p-5 shadow-xl dark:shadow-2xl dark:shadow-black/40 w-full min-w-0">
            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 mb-2">Atajos de periodo</p>
            <div class="flex flex-wrap gap-2 mb-5">
              <button
                type="button"
                class="px-3 py-2 rounded-xl text-[11px] font-black uppercase tracking-wide transition-all"
                :class="presetActivo === 'hoy' ? 'bg-cyan-600/30 text-cyan-700 dark:text-cyan-100 ring-2 ring-cyan-500/60 shadow-xl' : 'bg-slate-100 dark:bg-slate-950/60 text-slate-500 dark:text-slate-200 ring-1 ring-slate-200 dark:ring-white/10 hover:bg-slate-200 dark:hover:bg-slate-700/80'"
                @click="aplicarPresetPeriodo('hoy')"
              >
                Hoy
              </button>
              <button
                type="button"
                class="px-3 py-2 rounded-xl text-[11px] font-black uppercase tracking-wide transition-all"
                :class="presetActivo === 'ayer' ? 'bg-cyan-600/30 text-cyan-700 dark:text-cyan-100 ring-2 ring-cyan-500/60 shadow-xl' : 'bg-slate-100 dark:bg-slate-950/60 text-slate-500 dark:text-slate-200 ring-1 ring-slate-200 dark:ring-white/10 hover:bg-slate-200 dark:hover:bg-slate-700/80'"
                @click="aplicarPresetPeriodo('ayer')"
              >
                Ayer
              </button>
              <button
                type="button"
                class="px-3 py-2 rounded-xl text-[11px] font-black uppercase tracking-wide transition-all"
                :class="presetActivo === 'semana' ? 'bg-cyan-600/30 text-cyan-700 dark:text-cyan-100 ring-2 ring-cyan-500/60 shadow-xl' : 'bg-slate-100 dark:bg-slate-950/60 text-slate-500 dark:text-slate-200 ring-1 ring-slate-200 dark:ring-white/10 hover:bg-slate-200 dark:hover:bg-slate-700/80'"
                @click="aplicarPresetPeriodo('semana')"
              >
                Esta semana
                <span class="block text-[9px] font-bold text-slate-400 normal-case tracking-normal">Lun → dom</span>
              </button>
              <button
                type="button"
                class="px-3 py-2 rounded-xl text-[11px] font-black uppercase tracking-wide transition-all"
                :class="presetActivo === 'semana_ant' ? 'bg-cyan-600/30 text-cyan-700 dark:text-cyan-100 ring-2 ring-cyan-500/60 shadow-xl' : 'bg-slate-100 dark:bg-slate-950/60 text-slate-500 dark:text-slate-200 ring-1 ring-slate-200 dark:ring-white/10 hover:bg-slate-200 dark:hover:bg-slate-700/80'"
                @click="aplicarPresetPeriodo('semana_ant')"
              >
                Semana anterior
              </button>
              <button
                type="button"
                class="px-3 py-2 rounded-xl text-[11px] font-black uppercase tracking-wide transition-all"
                :class="presetActivo === 'mes' ? 'bg-cyan-600/30 text-cyan-700 dark:text-cyan-100 ring-2 ring-cyan-500/60 shadow-xl' : 'bg-slate-100 dark:bg-slate-950/60 text-slate-500 dark:text-slate-200 ring-1 ring-slate-200 dark:ring-white/10 hover:bg-slate-200 dark:hover:bg-slate-700/80'"
                @click="aplicarPresetPeriodo('mes')"
              >
                Este mes
              </button>
            </div>
            <div class="flex flex-wrap gap-3 sm:gap-4 items-end">
              <div class="min-w-[140px]">
                <label class="block text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 mb-1.5">Desde</label>
                <input
                  v-model="fechaInicio"
                  type="date"
                  class="w-full rounded-xl border-0 bg-[var(--ui-surface)] dark:bg-slate-950/80 text-slate-800 dark:text-slate-100 text-sm px-3 py-2.5 ring-1 ring-slate-200 dark:ring-white/10 focus:ring-2 focus:ring-brand-500/50"
                  @change="onFechaManual"
                />
              </div>
              <div class="min-w-[140px]">
                <label class="block text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 mb-1.5">Hasta</label>
                <input
                  v-model="fechaFin"
                  type="date"
                  class="w-full rounded-xl border-0 bg-[var(--ui-surface)] dark:bg-slate-950/80 text-slate-800 dark:text-slate-100 text-sm px-3 py-2.5 ring-1 ring-slate-200 dark:ring-white/10 focus:ring-2 focus:ring-brand-500/50"
                  @change="onFechaManual"
                />
              </div>
              <div class="min-w-[200px] flex-1 sm:flex-none">
                <label class="block text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 mb-1.5">Técnico</label>
                <select
                  v-model="tecnicoId"
                  class="w-full rounded-xl border-0 bg-[var(--ui-surface)] dark:bg-slate-950/80 text-slate-800 dark:text-slate-100 text-sm px-3 py-2.5 ring-1 ring-slate-200 dark:ring-white/10 focus:ring-2 focus:ring-brand-500/50"
                  @change="aplicar"
                >
                  <option value="">Todos</option>
                  <option v-for="t in tecnicos" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
              </div>
              <div class="min-w-[200px] flex-1 sm:flex-none">
                <label class="block text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 mb-1.5">Estado</label>
                <select
                  v-model="estado"
                  class="w-full rounded-xl border-0 bg-[var(--ui-surface)] dark:bg-slate-950/80 text-slate-800 dark:text-slate-100 text-sm px-3 py-2.5 ring-1 ring-slate-200 dark:ring-white/10 focus:ring-2 focus:ring-brand-500/50"
                  @change="aplicar"
                >
                  <option v-for="e in estadosOpciones" :key="e.value" :value="e.value">{{ e.label }}</option>
                </select>
              </div>
              <label
                class="inline-flex items-center gap-2.5 px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-950/50 ring-1 ring-slate-200 dark:ring-white/10 text-xs text-slate-500 dark:text-slate-200 cursor-pointer max-w-full"
                title="Desactívalo para incluir citas sin técnico asignado."
              >
                <input v-model="soloConTecnico" type="checkbox" class="rounded-xl border-slate-300 dark:border-slate-700 text-cyan-600 focus:ring-brand-500/40" @change="aplicar" />
                <span class="font-semibold leading-snug">Solo con técnico</span>
              </label>
              <button
                type="button"
                class="ml-auto sm:ml-0 px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wide bg-gradient-to-r from-cyan-600 to-blue-600 text-white shadow-xl shadow-cyan-900/30 hover:from-cyan-500 hover:to-blue-500 disabled:opacity-50 transition-all"
                :disabled="loading"
                @click="aplicar"
              >
                {{ loading ? 'Actualizando…' : 'Aplicar' }}
              </button>
            </div>
            <p v-if="truncated" class="mt-4 text-xs text-brand-600 dark:text-brand-300/95 flex items-start gap-2">
              <span class="mt-0.5 inline-block h-1.5 w-1.5 rounded-full bg-brand-500 shrink-0" />
              Hay más registros de los que se muestran. Acota fechas o filtra. Total en rango:
              <strong class="text-slate-800 dark:text-white">{{ total_rows }}</strong>.
            </p>
          </div>
        </div>

        <!-- KPIs -->
        <div>
          <h2 class="text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500 mb-3">Estadísticas del periodo</h2>
          <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
            <div class="relative overflow-hidden rounded-2xl p-4 sm:p-5 bg-white dark:bg-slate-800/50 ring-1 ring-slate-100 dark:ring-white/10 shadow-sm dark:shadow-xl">
              <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-cyan-500/10 blur-2xl" />
              <p class="text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500">Citas en rango (BD)</p>
              <p class="mt-1 text-2xl sm:text-2xl font-black text-slate-800 dark:text-white tabular-nums">{{ estadisticas.total_en_rango ?? 0 }}</p>
              <p class="mt-2 text-[11px] text-slate-400 dark:text-slate-500">Total que cumple fecha, estado y técnico en servidor.</p>
            </div>
            <div class="relative overflow-hidden rounded-2xl p-4 sm:p-5 bg-white dark:bg-slate-800/50 ring-1 ring-slate-100 dark:ring-white/10 shadow-sm dark:shadow-xl">
              <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-violet-500/10 blur-2xl" />
              <p class="text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500">Cargadas en tabla</p>
              <p class="mt-1 text-2xl sm:text-2xl font-black text-slate-800 dark:text-white tabular-nums">{{ estadisticas.mostrados ?? 0 }}</p>
              <p class="mt-2 text-[11px] text-slate-400 dark:text-slate-500">
                <span v-if="truncated" class="text-brand-500 dark:text-brand-400/90 font-semibold">Corte por límite.</span>
                <span v-else>Coinciden con el total en rango.</span>
              </p>
            </div>
            <div class="relative overflow-hidden rounded-2xl p-4 sm:p-5 bg-white dark:bg-slate-800/50 ring-1 ring-emerald-500/20 shadow-sm dark:shadow-xl">
              <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-brand-500/10 blur-2xl" />
              <p class="text-[10px] font-black uppercase tracking-wide text-emerald-600 dark:text-slate-400/80">Completadas</p>
              <p class="mt-1 text-2xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-300 tabular-nums">{{ estadisticas.completadas ?? 0 }}</p>
              <p class="mt-2 text-[11px] text-slate-400 dark:text-slate-500">{{ pctCompletadas }}% de las filas cargadas</p>
            </div>
            <div class="relative overflow-hidden rounded-2xl p-4 sm:p-5 bg-white dark:bg-slate-800/50 ring-1 ring-rose-500/20 shadow-sm dark:shadow-xl">
              <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-brand-500/10 blur-2xl" />
              <p class="text-[10px] font-black uppercase tracking-wide text-rose-600 dark:text-rose-300/90">Canceladas</p>
              <p class="mt-1 text-2xl sm:text-2xl font-black text-rose-600 dark:text-rose-200 tabular-nums">{{ statsFilas.canceladas }}</p>
              <p class="mt-2 text-[11px] text-slate-400 dark:text-slate-500">{{ pctCanceladasFilas }}% de las filas cargadas</p>
            </div>
            <div class="relative overflow-hidden rounded-2xl p-4 sm:p-5 bg-white dark:bg-slate-800/50 ring-1 ring-brand-500/15 shadow-sm dark:shadow-xl">
              <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-brand-500/10 blur-2xl" />
              <p class="text-[10px] font-black uppercase tracking-wide text-brand-600 dark:text-brand-200/80">En pipeline</p>
              <p class="mt-1 text-2xl sm:text-2xl font-black text-brand-600 dark:text-brand-100 tabular-nums">{{ statsFilas.pipeline }}</p>
              <p class="mt-2 text-[11px] text-slate-400 dark:text-slate-500">Pendiente, programado, en proceso o reprogramado.</p>
            </div>
            <div class="relative overflow-hidden rounded-2xl p-4 sm:p-5 bg-white dark:bg-slate-800/50 ring-1 ring-cyan-500/25 shadow-sm dark:shadow-xl">
              <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-brand-500/10 blur-2xl" />
              <p class="text-[10px] font-black uppercase tracking-wide text-cyan-600 dark:text-cyan-400/90">Importe ∑ (tabla)</p>
              <p class="mt-1 text-lg sm:text-xl font-black text-cyan-600 dark:text-cyan-200 tabular-nums break-all">
                {{ fmtMoneda(estadisticas.importe_total_mostrado) }}
              </p>
              <p class="mt-2 text-[11px] text-slate-400 dark:text-slate-500">Suma de totales en filas visibles.</p>
            </div>
            <div class="relative overflow-hidden rounded-2xl p-4 sm:p-5 bg-white dark:bg-slate-800/50 ring-1 ring-slate-100 dark:ring-white/10 shadow-sm dark:shadow-xl">
              <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-slate-500/10 blur-2xl" />
              <p class="text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500">Ticket promedio</p>
              <p class="mt-1 text-lg sm:text-xl font-black text-slate-800 dark:text-slate-100 tabular-nums">
                {{ fmtMoneda(statsFilas.importe_promedio) }}
              </p>
              <p class="mt-2 text-[11px] text-slate-400 dark:text-slate-500">Importe medio por cita en pantalla.</p>
            </div>
            <div class="relative overflow-hidden rounded-2xl p-4 sm:p-5 bg-white dark:bg-slate-800/50 ring-1 ring-indigo-500/20 shadow-sm dark:shadow-xl">
              <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-indigo-500/10 blur-2xl" />
              <p class="text-[10px] font-black uppercase tracking-wide text-indigo-600 dark:text-indigo-300/90">Tiempo medio servicio</p>
              <p class="mt-1 text-lg sm:text-xl font-black text-indigo-600 dark:text-indigo-100 tabular-nums">
                {{ statsFilas.mins_promedio_completadas != null ? fmtMin(statsFilas.mins_promedio_completadas) : '—' }}
              </p>
              <p class="mt-2 text-[11px] text-slate-400 dark:text-slate-500">Solo citas completadas con minutos registrados.</p>
            </div>
          </div>
        </div>

        <!-- Resumen por técnico -->
        <div v-if="resumen_por_tecnico.length" class="rounded-2xl sm:rounded-3xl bg-white dark:bg-black/50 ring-1 ring-slate-200 dark:ring-white/10 overflow-hidden shadow-sm dark:shadow-2xl">
          <div class="px-4 sm:px-6 py-4 border-b border-slate-100 dark:border-white/5 flex items-center justify-between gap-3 bg-slate-50/50 dark:bg-slate-950/20">
            <h2 class="text-sm font-black uppercase tracking-[0.2em] text-slate-700 dark:text-slate-200">Resumen por técnico</h2>
            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500">{{ resumen_por_tecnico.length }} técnicos</span>
          </div>
          <div class="hidden lg:block w-full min-w-0 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
              <colgroup>
                <col class="w-[30%]" />
                <col class="w-[12%]" />
                <col class="w-[12%]" />
                <col class="w-[12%]" />
                <col class="w-[14%]" />
                <col class="w-[20%]" />
              </colgroup>
              <thead>
                <tr class="text-left text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-white/5">
                  <th class="px-4 xl:px-6 py-3">Técnico</th>
                  <th class="px-3 py-3 text-right">Citas</th>
                  <th class="px-3 py-3 text-right">Completadas</th>
                  <th class="px-3 py-3 text-right">Canceladas</th>
                  <th class="px-3 py-3 text-right">Min. servicio ∑</th>
                  <th class="px-4 xl:px-6 py-3 text-right">Importe ∑</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                <tr
                  v-for="(row, idx) in resumen_por_tecnico"
                  :key="idx"
                  class="hover:bg-slate-50 dark:hover:bg-white/[0.03] transition-colors"
                >
                  <td class="px-4 xl:px-6 py-3.5 font-semibold text-slate-800 dark:text-slate-100 align-top break-words min-w-0">{{ row.tecnico_nombre }}</td>
                  <td class="px-3 py-3.5 text-right tabular-nums text-slate-700 dark:text-slate-200 align-top">{{ row.cantidad_citas }}</td>
                  <td class="px-3 py-3.5 text-right tabular-nums text-emerald-600 dark:text-emerald-300/90 align-top">{{ row.completadas ?? '—' }}</td>
                  <td class="px-3 py-3.5 text-right tabular-nums text-rose-600 dark:text-rose-300/90 align-top">{{ row.canceladas ?? '—' }}</td>
                  <td class="px-3 py-3.5 text-right tabular-nums text-slate-500 dark:text-slate-200 align-top">{{ row.minutos_servicio ?? 0 }}</td>
                  <td class="px-4 xl:px-6 py-3.5 text-right font-bold text-cyan-600 dark:text-cyan-200 tabular-nums align-top whitespace-nowrap">{{ fmtMoneda(row.total_importe) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="lg:hidden divide-y divide-slate-100 dark:divide-white/5 bg-white dark:bg-transparent">
            <div v-for="(row, idx) in resumen_por_tecnico" :key="'m-' + idx" class="p-4 space-y-2">
              <p class="font-bold text-slate-800 dark:text-white">{{ row.tecnico_nombre }}</p>
              <div class="grid grid-cols-2 gap-2 text-xs text-slate-500 dark:text-slate-400">
                <span>Citas: <strong class="text-slate-700 dark:text-slate-200">{{ row.cantidad_citas }}</strong></span>
                <span>Complet.: <strong class="text-emerald-600 dark:text-emerald-300">{{ row.completadas ?? '—' }}</strong></span>
                <span>Cancel.: <strong class="text-rose-600 dark:text-rose-300">{{ row.canceladas ?? '—' }}</strong></span>
                <span>Min. ∑: <strong class="text-slate-700 dark:text-slate-200">{{ row.minutos_servicio ?? 0 }}</strong></span>
              </div>
              <p class="text-sm font-black text-cyan-600 dark:text-cyan-200">{{ fmtMoneda(row.total_importe) }}</p>
            </div>
          </div>
        </div>

        <!-- Búsqueda local -->
        <div class="flex flex-col sm:flex-row gap-3 sm:items-stretch">
          <div class="relative flex-1">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </span>
            <input
              v-model="busqueda"
              type="search"
              placeholder="Buscar en técnico, cliente, teléfono, folio, dirección, equipo, problema, trabajo…"
              class="w-full pl-11 pr-4 py-3 rounded-2xl bg-white dark:bg-slate-800/80 text-slate-800 dark:text-slate-100 text-sm ring-1 ring-slate-200 dark:ring-white/10 border-0 placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-brand-500/40"
            />
          </div>
        </div>

        <!-- Tabla detalle -->
        <div class="rounded-2xl sm:rounded-3xl bg-white dark:bg-slate-800/50 ring-1 ring-slate-200 dark:ring-white/10 overflow-hidden shadow-sm dark:shadow-2xl w-full min-w-0">
          <div class="w-full min-w-0 overflow-x-auto lg:overflow-x-visible">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
              <thead>
                <tr class="text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 bg-[var(--ui-surface)] dark:bg-slate-950/80 border-b border-slate-100 dark:border-white/10">
                  <th class="px-2 py-3 w-8" />
                  <th class="px-2 py-3 w-[6%] lg:w-[5%]">Folio</th>
                  <th class="px-2 py-3 w-[14%] lg:w-[12%]">Técnico</th>
                  <th class="px-2 py-3 w-[16%] lg:w-[13%]">Cliente</th>
                  <th class="px-2 py-3 w-[14%] hidden lg:table-cell">Ubicación</th>
                  <th class="px-2 py-3 w-[12%] lg:w-[10%]">Servicio</th>
                  <th class="px-2 py-3 w-[6%] lg:w-[5%]">Prior.</th>
                  <th class="px-2 py-3 w-[8%] lg:w-[7%]">Estado</th>
                  <th class="px-2 py-3 w-[10%] lg:w-[9%]">Agenda</th>
                  <th class="px-2 py-3 w-[12%] lg:w-[11%]">Inicio / fin</th>
                  <th class="px-2 py-3 w-[6%] lg:w-[5%]">Dur.</th>
                  <th class="px-2 py-3 w-[7%] lg:w-[6%] text-right">Total</th>
                  <th class="px-2 py-3 w-[5.5rem] sm:w-28 lg:w-32 text-right">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <template v-for="r in registrosFiltrados" :key="r.id">
                  <tr
                    class="border-b border-slate-100 dark:border-white/5 bg-white dark:bg-transparent hover:bg-slate-50 dark:hover:bg-white/[0.04] align-top transition-colors cursor-pointer group text-slate-700 dark:text-slate-200"
                    @click="toggleRow(r.id)"
                  >
                    <td class="px-3 py-2.5 text-slate-400 dark:text-slate-500">
                      <button
                        type="button"
                        class="p-1 rounded-xl hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 dark:text-slate-400 group-hover:text-cyan-600 dark:group-hover:text-cyan-300 transition-colors"
                        :aria-expanded="!!expanded[r.id]"
                        @click.stop="toggleRow(r.id)"
                      >
                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-90': expanded[r.id] }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                      </button>
                    </td>
                    <td class="px-2 py-2.5 whitespace-nowrap font-mono text-cyan-600 dark:text-cyan-200/90">{{ r.folio || '—' }}</td>
                    <td class="px-2 py-2.5 min-w-0 align-top break-words">
                      <div class="font-semibold text-slate-800 dark:text-slate-100 leading-snug">{{ r.tecnico_nombre }}</div>
                      <div v-if="r.tecnico_email" class="text-[11px] text-slate-400 dark:text-slate-500 break-all">{{ r.tecnico_email }}</div>
                    </td>
                    <td class="px-2 py-2.5 min-w-0 align-top break-words">
                      <div class="font-medium text-slate-800 dark:text-slate-100 leading-snug">{{ r.cliente || '—' }}</div>
                      <div v-if="r.cliente_telefono" class="text-[11px] text-slate-400 dark:text-slate-500">{{ r.cliente_telefono }}</div>
                    </td>
                    <td class="px-2 py-2.5 min-w-0 text-slate-500 dark:text-slate-400 text-[11px] leading-snug break-words hidden lg:table-cell align-top">
                      {{ r.direccion || '—' }}
                    </td>
                    <td class="px-2 py-2.5">
                      <div class="text-slate-700 dark:text-slate-200">{{ tipoServicioLegible(r.tipo_servicio) }}</div>
                      <div v-if="r.equipo" class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5 line-clamp-2">{{ r.equipo }}</div>
                    </td>
                    <td class="px-2 py-2.5 whitespace-nowrap">
                      <span class="inline-flex px-2 py-0.5 rounded-xl text-[10px] font-bold uppercase bg-slate-100 dark:bg-white/5 text-slate-500 dark:text-slate-200 ring-1 ring-slate-200 dark:ring-white/10">
                        {{ prioridadLabel(r.prioridad) }}
                      </span>
                    </td>
                    <td class="px-2 py-2.5 whitespace-nowrap">
                      <span
                        class="inline-flex px-2 py-0.5 rounded-xl text-[10px] font-black uppercase tracking-wide ring-1"
                        :class="estadoChip(r.estado)"
                      >
                        {{ estadoLegible(r.estado) }}
                      </span>
                    </td>
                    <td class="px-2 py-2.5 whitespace-nowrap text-slate-500 dark:text-slate-200 tabular-nums">{{ fmtFecha(r.fecha_hora) }}</td>
                    <td class="px-2 py-2.5 whitespace-nowrap text-slate-500 dark:text-slate-200 tabular-nums">
                      <div>{{ fmtFecha(r.inicio_servicio) }}</div>
                      <div class="text-slate-400 dark:text-slate-500 text-[11px]">{{ fmtFecha(r.fin_servicio) }}</div>
                    </td>
                    <td class="px-2 py-2.5 whitespace-nowrap text-slate-500 dark:text-slate-200 tabular-nums">{{ fmtMin(r.tiempo_servicio_minutos) }}</td>
                    <td class="px-2 py-2.5 whitespace-nowrap text-right font-bold text-slate-800 dark:text-white tabular-nums">{{ fmtMoneda(r.total) }}</td>
                    <td class="px-2 py-2.5 text-right align-top" @click.stop>
                      <div class="inline-flex flex-col sm:flex-row sm:items-center sm:justify-end gap-1.5 sm:gap-2">
                        <Link
                          :href="route('citas.show', r.id)"
                          class="inline-flex items-center justify-end gap-1 text-[11px] font-black uppercase tracking-wider text-cyan-600 dark:text-cyan-400 hover:text-cyan-500 dark:hover:text-cyan-300"
                        >
                          Ver
                          <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </Link>
                        <button
                          v-if="$can('view ventas') && r.venta_id"
                          type="button"
                          class="inline-flex items-center justify-end gap-1 rounded-xl px-2 py-1 text-[10px] sm:text-[11px] font-black uppercase tracking-wider bg-violet-100 dark:bg-violet-600/20 text-violet-700 dark:text-violet-200 ring-1 ring-violet-200 dark:ring-violet-500/35 hover:bg-violet-200/60 dark:hover:bg-violet-600/30"
                          :title="r.venta_numero ? `Venta ${r.venta_numero}` : 'Ver venta vinculada'"
                          @click="abrirDetalleVenta(r.venta_id)"
                        >
                          Ver venta
                          <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                        <button
                          v-else-if="$can('view ventas')"
                          type="button"
                          class="inline-flex items-center justify-end gap-1 rounded-xl px-2 py-1 text-[10px] sm:text-[11px] font-black uppercase tracking-wider bg-sky-100 dark:bg-indigo-600/20 text-indigo-700 dark:text-indigo-200 ring-1 ring-indigo-200 dark:ring-indigo-500/35 hover:bg-indigo-200/60 dark:hover:bg-indigo-600/30"
                          title="Ver ventas del cliente y vincular una a esta cita (sin salir del reporte)"
                          @click="abrirModalVentaCita(r)"
                        >
                          Venta / cita
                          <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                        </button>
                        <Link
                          v-else-if="$can('create ventas')"
                          :href="route('ventas.create', { cita_id: r.id })"
                          class="inline-flex items-center justify-end gap-1 rounded-xl px-2 py-1 text-[10px] sm:text-[11px] font-black uppercase tracking-wider bg-emerald-100 dark:bg-emerald-600/20 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-emerald-200 ring-1 ring-emerald-200 dark:ring-emerald-500/35 hover:bg-emerald-200/60 dark:hover:bg-emerald-600/30"
                          title="Registrar venta nueva vinculada a esta cita"
                        >
                          Nueva venta
                          <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </Link>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="expanded[r.id]" class="bg-slate-50 dark:bg-slate-950/70 border-b border-slate-100 dark:border-white/5">
                    <td colspan="13" class="px-4 sm:px-6 py-4">
                      <div class="grid md:grid-cols-2 gap-4 text-xs sm:text-sm">
                        <div class="space-y-3">
                          <div v-if="r.direccion" class="lg:hidden">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 mb-1">Ubicación</p>
                            <p class="text-slate-700 dark:text-slate-200 leading-relaxed">{{ r.direccion }}</p>
                          </div>
                          <div v-if="r.descripcion && (r.descripcion || '').trim() !== (r.problema_reportado || '').trim()">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 mb-1">Descripción</p>
                            <p class="text-slate-700 dark:text-slate-200 leading-relaxed whitespace-pre-wrap">{{ r.descripcion }}</p>
                          </div>
                          <div v-if="r.problema_reportado">
                            <p class="text-[10px] font-black uppercase tracking-wide text-brand-600 dark:text-brand-500/90 mb-1">Problema reportado</p>
                            <p class="text-slate-700 dark:text-slate-200 leading-relaxed whitespace-pre-wrap">{{ r.problema_reportado }}</p>
                          </div>
                        </div>
                        <div class="space-y-3">
                          <div v-if="r.trabajo_realizado">
                            <p class="text-[10px] font-black uppercase tracking-wide text-emerald-600 dark:text-slate-400/90 mb-1">Trabajo realizado</p>
                            <p class="text-slate-700 dark:text-slate-200 leading-relaxed whitespace-pre-wrap">{{ r.trabajo_realizado }}</p>
                          </div>
                          <div v-if="r.notas">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 mb-1">Notas</p>
                            <p class="text-slate-500 dark:text-slate-400 leading-relaxed whitespace-pre-wrap">{{ r.notas }}</p>
                          </div>
                          <div class="flex flex-wrap gap-x-6 gap-y-1 text-[11px] text-slate-400 dark:text-slate-500">
                            <span v-if="r.fecha_hora_fin">Fin programado: <span class="text-slate-500 dark:text-slate-200">{{ fmtFecha(r.fecha_hora_fin) }}</span></span>
                            <span>Actualizado: <span class="text-slate-500 dark:text-slate-200">{{ fmtFecha(r.actualizado_at) }}</span></span>
                            <span v-if="r.creado_at">Creado: <span class="text-slate-500 dark:text-slate-200">{{ fmtFecha(r.creado_at) }}</span></span>
                          </div>
                          <div v-if="$can('view ventas') && r.venta_id" class="pt-3 border-t border-slate-200 dark:border-white/10 flex flex-wrap gap-2">
                            <button
                              type="button"
                              class="inline-flex items-center gap-2 rounded-xl bg-violet-600/20 px-3 py-2 text-[11px] font-black uppercase tracking-wider text-violet-700 dark:text-violet-200 ring-1 ring-violet-500/35 hover:bg-violet-600/30"
                              @click="abrirDetalleVenta(r.venta_id)"
                            >
                              Ver venta{{ r.venta_numero ? ` ${r.venta_numero}` : '' }}
                            </button>
                          </div>
                          <div v-else-if="$can('view ventas')" class="pt-3 border-t border-slate-200 dark:border-white/10 flex flex-wrap gap-2">
                            <button
                              type="button"
                              class="inline-flex items-center gap-2 rounded-xl bg-indigo-600/20 px-3 py-2 text-[11px] font-black uppercase tracking-wider text-indigo-700 dark:text-indigo-200 ring-1 ring-indigo-500/35 hover:bg-indigo-600/30"
                              @click="abrirModalVentaCita(r)"
                            >
                              Ventas del cliente / vincular
                            </button>
                          </div>
                          <div v-else-if="$can('create ventas')" class="pt-3 border-t border-slate-200 dark:border-white/10 flex flex-wrap gap-2">
                            <Link
                              :href="route('ventas.create', { cita_id: r.id })"
                              class="inline-flex items-center gap-2 rounded-xl bg-emerald-600/20 px-3 py-2 text-[11px] font-black uppercase tracking-wider text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-emerald-200 ring-1 ring-emerald-500/35 hover:bg-emerald-600/30"
                            >
                              Nueva venta para esta cita
                            </Link>
                          </div>
                        </div>
                      </div>
                    </td>
                  </tr>
                </template>
                <tr v-if="!registrosFiltrados.length">
                  <td colspan="13" class="px-6 py-16 text-center text-slate-400 dark:text-slate-500">
                    <p class="text-lg font-bold text-slate-700 dark:text-slate-400">Sin resultados</p>
                    <p class="mt-1 text-sm">Ajusta fechas, técnico o estado; o limpia la búsqueda local.</p>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <p v-if="busqueda && registrosFiltrados.length !== registros.length" class="text-center text-xs text-slate-500">
          Mostrando <strong class="text-slate-300">{{ registrosFiltrados.length }}</strong> de
          <strong class="text-slate-300">{{ registros.length }}</strong> filas (filtro local).
        </p>

        <!-- Modal: ventas del cliente + vincular (overlay dentro del reporte) -->
        <Teleport to="body">
          <div
            v-if="modalVentaAbierto && modalCita"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm transition-all"
            @click.self="cerrarModalVenta"
          >
            <div
              class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl max-w-3xl w-full max-h-[88vh] overflow-hidden flex flex-col ring-1 ring-black/5 dark:ring-white/15"
              @click.stop
            >
              <div class="px-5 py-4 border-b border-slate-200 dark:border-white/10 flex items-start justify-between gap-3 shrink-0">
                <div class="min-w-0">
                  <h2 class="text-lg font-black text-slate-900 dark:text-white">Ventas del cliente</h2>
                  <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Cita #{{ modalCita.id }} · {{ modalCita.cliente || 'Cliente' }}
                  </p>
                  <p class="text-xs text-slate-400 dark:text-slate-500 mt-2 leading-relaxed max-w-xl">
                    Elige una venta existente para vincularla a esta cita. Si no aparece el cobro, usa <strong class="text-slate-500 dark:text-slate-200">Crear venta</strong>.
                  </p>
                </div>
                <button
                  type="button"
                  class="shrink-0 p-2 rounded-xl text-slate-400 hover:text-brand-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-white/10"
                  aria-label="Cerrar"
                  @click="cerrarModalVenta"
                >
                  <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
              </div>
              <div class="shrink-0 px-4 py-3 border-b border-slate-200 dark:border-white/10 bg-emerald-50 dark:bg-emerald-900/20 dark:bg-emerald-950/40 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <p class="text-xs text-emerald-800 dark:text-emerald-200 dark:text-slate-200 font-medium">
                  ¿No está la venta en la lista? Abre el formulario de nueva venta con esta cita.
                </p>
                <Link
                  :href="route('ventas.create', { cita_id: modalCita.id })"
                  class="shrink-0 inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black uppercase tracking-wide shadow-md"
                  @click="cerrarModalVenta"
                >
                  Crear venta
                </Link>
              </div>
              <p v-if="modalVentaError" class="mx-4 mt-3 text-sm text-rose-600 dark:text-rose-300 bg-rose-50 dark:bg-rose-900/20 dark:bg-rose-950/50 ring-1 ring-rose-200 dark:ring-rose-500/30 rounded-xl px-3 py-2">
                {{ modalVentaError }}
              </p>
              <div class="overflow-y-auto flex-1 min-h-0 p-4">
                <p v-if="modalVentaCargando" class="text-center text-slate-400 py-10 text-sm">Cargando ventas…</p>
                <table v-else-if="modalVentas.length" class="w-full text-sm">
                  <thead>
                    <tr class="text-left text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 border-b border-slate-200 dark:border-white/10">
                      <th class="pb-2 pr-2">Folio</th>
                      <th class="pb-2 pr-2">Fecha</th>
                      <th class="pb-2 pr-2 text-right">Total</th>
                      <th class="pb-2 pr-2">Pago</th>
                      <th class="pb-2 text-right">Acción</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="v in modalVentas" :key="v.id" class="border-b border-slate-100 dark:border-white/5">
                      <td class="py-2.5 pr-2 font-mono font-bold text-brand-600 dark:text-brand-300/95">{{ v.numero_venta }}</td>
                      <td class="py-2.5 pr-2 text-slate-500 dark:text-slate-200">{{ fmtFechaVentaModal(v.fecha) }}</td>
                      <td class="py-2.5 pr-2 text-right font-bold text-slate-900 dark:text-white tabular-nums">{{ fmtMoneda(v.total) }}</td>
                      <td class="py-2.5 pr-2">
                        <span class="text-xs text-slate-700 dark:text-slate-200">{{ v.pagado ? 'Pagado' : 'Pendiente' }}</span>
                        <span class="block text-[10px] text-slate-500">{{ estadoVentaModal(v.estado) }}</span>
                      </td>
                      <td class="py-2.5 text-right">
                        <span
                          v-if="v.cita_id && Number(v.cita_id) === Number(modalCita.id)"
                          class="inline-flex px-2 py-1 rounded-xl text-[10px] font-black uppercase bg-emerald-100 dark:bg-brand-500/15 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-emerald-300 ring-1 ring-emerald-200 dark:ring-emerald-500/30"
                        >
                          En esta cita
                        </span>
                        <span
                          v-else-if="v.cita_id && Number(v.cita_id) !== Number(modalCita.id)"
                          class="inline-block text-[10px] font-bold text-brand-600 dark:text-brand-300/90 text-right max-w-[9rem] leading-snug"
                        >
                          Cita #{{ v.cita_id }}
                        </span>
                        <button
                          v-else
                          type="button"
                          class="px-3 py-1.5 rounded-xl text-xs font-black uppercase bg-emerald-600 text-white hover:bg-slate-500 disabled:opacity-50 transition-colors"
                          :disabled="vinculandoVentaId === v.id"
                          @click="vincularVentaDesdeModal(v.id)"
                        >
                          {{ vinculandoVentaId === v.id ? '…' : 'Vincular' }}
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
                <div v-else class="py-10 text-center space-y-6">
                  <p class="text-slate-400 text-sm max-w-md mx-auto leading-relaxed">
                    No hay ventas recientes de este cliente o la cita no tiene cliente asignado.
                  </p>
                  <Link
                    :href="route('ventas.create', { cita_id: modalCita.id })"
                    class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black uppercase tracking-wide shadow-md"
                    @click="cerrarModalVenta"
                  >
                    Crear venta
                  </Link>
                </div>
              </div>
              <div class="shrink-0 px-6 py-4 border-t border-slate-200 dark:border-white/10 flex flex-wrap justify-end gap-2 bg-[var(--ui-surface)] dark:bg-slate-950/80">
                <button
                  type="button"
                  class="px-4 py-2 text-sm font-bold text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white"
                  @click="cerrarModalVenta"
                >
                  Cerrar
                </button>
                <Link
                  :href="route('ventas.index', { search: modalCita.cliente || '' })"
                  class="px-4 py-2 text-sm font-bold text-cyan-600 dark:text-cyan-400 hover:text-cyan-500 dark:hover:text-cyan-300"
                  @click="cerrarModalVenta"
                >
                  Ir a ventas
                </Link>
              </div>
            </div>
          </div>
        </Teleport>

        <!-- Modal: detalle de venta rápido -->
        <Teleport to="body">
          <div
            v-if="detalleVentaAbierto"
            class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/50 dark:bg-black/50 backdrop-blur-md transition-all"
            @click.self="cerrarDetalleVenta"
          >
            <div
              class="bg-white dark:bg-slate-800 rounded-[32px] shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col ring-1 ring-black/5 dark:ring-white/20"
              @click.stop
            >
              <!-- Header Modal -->
              <div class="px-8 py-6 border-b border-slate-100 dark:border-white/10 flex items-center justify-between shrink-0 bg-slate-50/50 dark:bg-slate-950/40">
                <div class="flex items-center gap-4">
                  <div class="w-10 h-10 rounded-2xl bg-violet-600/10 dark:bg-violet-600/20 flex items-center justify-center text-2xl text-violet-600 dark:text-violet-400 ring-1 ring-violet-500/20 dark:ring-violet-500/30">
                    💰
                  </div>
                  <div>
                    <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-wider">Detalle de Venta</h2>
                    <p v-if="detalleVenta" class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide mt-0.5">
                      Folio: <span class="text-brand-600 dark:text-brand-400/90 font-mono">{{ detalleVenta.numero_venta }}</span>
                    </p>
                  </div>
                </div>
                <button
                  type="button"
                  class="p-2 rounded-xl text-slate-400 hover:text-brand-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-white/10 transition-colors"
                  @click="cerrarDetalleVenta"
                >
                  <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
              </div>

              <!-- Content -->
              <div class="flex-1 overflow-y-auto p-8 min-h-0 space-y-6 custom-scrollbar bg-white dark:bg-slate-800">
                <div v-if="detalleVentaCargando" class="py-20 flex flex-col items-center justify-center space-y-6">
                  <div class="w-10 h-10 border-4 border-violet-500/20 dark:border-violet-500/30 border-t-violet-500 rounded-full animate-spin"></div>
                  <p class="text-sm font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">Cargando información...</p>
                </div>

                <div v-else-if="detalleVentaError" class="py-10 text-center space-y-6">
                  <div class="inline-flex w-16 h-16 rounded-full bg-brand-500/10 items-center justify-center text-rose-500 text-3xl">⚠️</div>
                  <p class="text-rose-600 dark:text-rose-300 font-bold">{{ detalleVentaError }}</p>
                  <button @click="cerrarDetalleVenta" class="text-xs font-black uppercase text-slate-400 hover:text-brand-600 dark:hover:text-white transition-colors">Cerrar</button>
                </div>

                <div v-else-if="detalleVenta" class="space-y-6 animate-in fade-in duration-200">
                  <!-- Info Grid -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-slate-50 dark:bg-white/5 rounded-3xl p-5 ring-1 ring-slate-100 dark:ring-white/10 shadow-sm">
                      <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-3">Cliente</p>
                      <p class="text-base font-black text-slate-900 dark:text-white leading-tight mb-1">{{ detalleVenta.cliente?.nombre_razon_social }}</p>
                      <p class="text-xs text-slate-500 dark:text-slate-400">{{ detalleVenta.cliente?.telefono || 'Sin teléfono' }}</p>
                      <p v-if="detalleVenta.cliente?.email" class="text-xs text-slate-400 dark:text-slate-500 mt-1 truncate">{{ detalleVenta.cliente.email }}</p>
                    </div>
                    <div class="bg-slate-50 dark:bg-white/5 rounded-3xl p-5 ring-1 ring-slate-100 dark:ring-white/10 shadow-sm">
                      <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-3">Operación</p>
                      <div class="space-y-2 text-xs">
                        <div class="flex justify-between border-b border-slate-200/50 dark:border-white/5 pb-1">
                          <span class="text-slate-500">Fecha:</span>
                          <span class="text-slate-800 dark:text-slate-200 font-bold">{{ fmtFecha(detalleVenta.fecha) }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-200/50 dark:border-white/5 pb-1">
                          <span class="text-slate-500">Método:</span>
                          <span class="text-slate-800 dark:text-slate-200 font-bold uppercase">{{ detalleVenta.metodo_pago_etiqueta || detalleVenta.metodo_pago }}</span>
                        </div>
                        <div class="flex justify-between">
                          <span class="text-slate-500">Estado:</span>
                          <span :class="detalleVenta.pagado ? 'text-emerald-600 dark:text-slate-400' : 'text-brand-600 dark:text-amber-400'" class="font-black uppercase tracking-wide">
                            {{ detalleVenta.pagado ? 'Pagado' : 'Pendiente' }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Conceptos -->
                  <div>
                    <h3 class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-4 ml-2">Conceptos de Venta</h3>
                    <div class="bg-white dark:bg-slate-950/40 rounded-3xl overflow-hidden ring-1 ring-slate-200 dark:ring-white/10 shadow-sm">
                      <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead class="bg-slate-50 dark:bg-slate-800/50">
                          <tr>
                            <th class="px-5 py-3">Item</th>
                            <th class="px-3 py-3 text-center">Cant.</th>
                            <th class="px-5 py-3 text-right">Importe</th>
                          </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                          <tr v-for="item in detalleVenta.items" :key="item.id" class="hover:bg-slate-50/50 dark:hover:bg-white/[0.02] transition-colors">
                            <td class="px-5 py-3.5">
                              <div class="font-bold text-slate-800 dark:text-slate-200 uppercase leading-tight">{{ item.nombre }}</div>
                              <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">{{ item.tipo }} <span v-if="item.codigo" class="ml-2 font-mono">#{{ item.codigo }}</span></div>
                              <!-- Series -->
                              <div v-if="item.series?.length" class="mt-2 flex flex-wrap gap-1">
                                <span v-for="s in item.series" :key="s" class="px-1.5 py-0.5 rounded-xl bg-violet-100 dark:bg-violet-500/10 text-violet-600 dark:text-violet-400 font-mono text-[9px] border border-violet-200 dark:border-violet-500/20">
                                  {{ s }}
                                </span>
                              </div>
                            </td>
                            <td class="px-3 py-3.5 text-center font-black text-slate-500 dark:text-slate-400">{{ item.cantidad }}</td>
                            <td class="px-5 py-3.5 text-right font-black text-slate-900 dark:text-white tabular-nums">{{ fmtMoneda(item.precio * item.cantidad) }}</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <!-- Totales -->
                  <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-white/5">
                    <div class="w-full max-w-[240px] space-y-2.5">
                      <div class="flex justify-between text-[11px] font-bold">
                        <span class="text-slate-400 dark:text-slate-500 uppercase tracking-wide">Subtotal</span>
                        <span class="text-slate-500 dark:text-slate-200">{{ fmtMoneda(detalleVenta.subtotal) }}</span>
                      </div>
                      <div v-if="detalleVenta.iva > 0" class="flex justify-between text-[11px] font-bold">
                        <span class="text-slate-400 dark:text-slate-500 uppercase tracking-wide">IVA (16%)</span>
                        <span class="text-slate-500 dark:text-slate-200">{{ fmtMoneda(detalleVenta.iva) }}</span>
                      </div>
                      <div v-if="detalleVenta.descuento_general > 0" class="flex justify-between text-[11px] font-bold">
                        <span class="text-rose-500 dark:text-rose-400 uppercase tracking-wide">Descuento</span>
                        <span class="text-rose-500 dark:text-rose-400">-{{ fmtMoneda(detalleVenta.descuento_general) }}</span>
                      </div>
                      <div class="flex justify-between items-end pt-2 border-t border-slate-100 dark:border-white/5 mt-2">
                        <span class="text-xs font-black text-slate-400 dark:text-slate-400 uppercase tracking-[0.2em]">Total</span>
                        <span class="text-2xl font-black text-slate-900 dark:text-white tabular-nums">{{ fmtMoneda(detalleVenta.total) }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- Notas -->
                  <div v-if="detalleVenta.notas" class="bg-brand-50 dark:bg-brand-900/20 dark:bg-brand-400/5 rounded-2xl p-4 border border-brand-200 dark:border-brand-800/30 dark:border-brand-400/10">
                    <p class="text-[9px] font-black text-brand-600 dark:text-brand-500 uppercase tracking-wide mb-2">Observaciones</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 italic leading-relaxed">{{ detalleVenta.notas }}</p>
                  </div>
                </div>
              </div>

              <!-- Footer Modal -->
              <div class="px-8 py-5 border-t border-slate-100 dark:border-white/10 flex items-center justify-between bg-[var(--ui-surface)] dark:bg-slate-950/60 shrink-0">
                <button
                  type="button"
                  class="px-6 py-2.5 text-xs font-black uppercase tracking-wide text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors"
                  @click="cerrarDetalleVenta"
                >
                  Cerrar
                </button>
                <div class="flex gap-3">
                  <a
                    v-if="detalleVenta"
                    :href="route('ventas.pdf', detalleVenta.id)"
                    target="_blank"
                    class="px-5 py-2.5 rounded-xl bg-slate-200 dark:bg-white/5 hover:bg-slate-300 dark:hover:bg-white/10 text-slate-700 dark:text-slate-200 text-[10px] font-black uppercase tracking-wide border border-slate-300 dark:border-white/10 transition-all shadow-sm"
                  >
                    PDF
                  </a>
                  <a
                    v-if="detalleVenta"
                    :href="route('ventas.show', detalleVenta.id)"
                    target="_blank"
                    class="px-5 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-500 text-white text-[10px] font-black uppercase tracking-wide shadow-xl shadow-violet-900/20 transition-all active:scale-95"
                  >
                    Abrir pantalla completa
                  </a>
                </div>
              </div>
            </div>
          </div>
        </Teleport>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
