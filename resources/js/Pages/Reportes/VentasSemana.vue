<script setup>
import { ref, computed, watch } from 'vue'
import { Head, router, Link } from '@inertiajs/vue3'
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
  resumen_por_vendedor: { type: Array, default: () => [] },
  vendedores: { type: Array, default: () => [] },
  totales: { type: Object, default: () => ({}) },
  por_metodo_pago: { type: Array, default: () => [] },
  total_rows: { type: Number, default: 0 },
  truncated: { type: Boolean, default: false },
  filtros: { type: Object, default: () => ({}) },
})

const fechaInicio = ref(props.filtros.fecha_inicio || '')
const fechaFin = ref(props.filtros.fecha_fin || '')
const vendedorId = ref(props.filtros.vendedor_id ?? '')
const soloPagadas = ref(
  props.filtros.solo_pagadas !== undefined && props.filtros.solo_pagadas !== null
    ? !!props.filtros.solo_pagadas
    : false
)
const busqueda = ref('')
const loading = ref(false)
const expanded = ref({})
const presetActivo = ref(null)

watch(
  () => [props.filtros?.fecha_inicio, props.filtros?.fecha_fin],
  ([ini, fin]) => {
    if (ini != null && ini !== '') fechaInicio.value = ini
    if (fin != null && fin !== '') fechaFin.value = fin
  }
)

watch(
  () => props.filtros?.vendedor_id,
  (v) => {
    vendedorId.value = v ?? ''
  }
)

watch(
  () => props.filtros?.solo_pagadas,
  (v) => {
    if (v !== undefined && v !== null) soloPagadas.value = !!v
  }
)

const aplicar = () => {
  loading.value = true
  router.get(
    route('reportes.ventas-semana'),
    {
      fecha_inicio: fechaInicio.value,
      fecha_fin: fechaFin.value,
      vendedor_id: vendedorId.value === '' ? null : vendedorId.value,
      solo_pagadas: soloPagadas.value ? 1 : 0,
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
    return format(parseISO(iso), 'd MMM yyyy, HH:mm', { locale: es })
  } catch {
    return iso
  }
}

const fmtMoneda = (n) => {
  if (n == null || n === '') return '—'
  return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(n))
}

const periodoLabel = computed(() => {
  if (!fechaInicio.value && !fechaFin.value) return 'Periodo'
  try {
    if (fechaInicio.value === fechaFin.value) {
      return format(parseISO(fechaInicio.value), 'EEEE d MMM yyyy', { locale: es })
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

const registrosFiltrados = computed(() => {
  if (!busqueda.value.trim()) return props.registros
  const s = busqueda.value.toLowerCase()
  return props.registros.filter((r) => {
    const blob = [
      r.numero_venta,
      r.cliente,
      r.vendedor_nombre,
      r.items_resumen,
      r.metodo_pago,
      r.estado,
      r.cita_folio != null ? String(r.cita_folio) : '',
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

const presetBtnClass = (id) =>
  presetActivo.value === id
    ? 'bg-cyan-600/30 text-cyan-100 ring-2 ring-cyan-400/60 shadow-lg shadow-cyan-900/20'
    : 'bg-slate-950/60 text-slate-300 ring-1 ring-white/10 hover:bg-slate-800/80 hover:text-white'
</script>

<template>
  <Head title="Ventas del periodo (vendedor y cita)" />

  <AppLayout title="Ventas del periodo">
    <div
      class="report-ventas-semana min-h-[calc(100vh-5rem)] w-full max-w-none min-w-0 bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950 text-slate-100 px-2 sm:px-3 lg:px-4 pb-10 pt-2 sm:pt-4 border-t border-white/5"
    >
      <div class="w-full min-w-0 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6 mb-6 sm:mb-8">
        <div>
          <p class="text-[10px] font-black uppercase tracking-[0.35em] text-cyan-400/90 mb-2">Reportes operativos</p>
          <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
            Ventas del periodo
            <span class="block sm:inline sm:ml-2 text-base sm:text-lg font-bold text-slate-400">vendedor, detalle y cita</span>
          </h1>
          <p class="mt-2 max-w-3xl text-sm text-slate-400 leading-relaxed">
            Lo vendido en fechas elegidas: quién vendió, importe (útil para corte: marca “solo cobradas”), método de pago y
            <strong class="text-slate-200">cita vinculada</strong> cuando la venta ya está relacionada con una cita.
            Periodo: <span class="text-slate-200 font-semibold">{{ periodoLabel }}</span>
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <Link
            :href="route('reportes.index')"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest bg-white/5 hover:bg-white/10 text-slate-200 ring-1 ring-white/10 transition-colors"
          >
            ← Reportes
          </Link>
        </div>
      </div>

      <div class="w-full min-w-0 max-w-none space-y-5 sm:space-y-6">
        <div
          class="sticky top-0 z-10 -mx-1 px-1 py-3 bg-slate-950/90 backdrop-blur-md border-b border-white/5 rounded-2xl sm:rounded-3xl"
        >
          <div class="rounded-2xl sm:rounded-3xl bg-slate-900/80 ring-1 ring-white/10 p-4 sm:p-5 shadow-2xl shadow-black/40 w-full min-w-0">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Atajos de periodo</p>
            <div class="flex flex-wrap gap-2 mb-5">
              <button
                type="button"
                class="px-3 py-2 rounded-xl text-[11px] font-black uppercase tracking-wide transition-all"
                :class="presetBtnClass('hoy')"
                @click="aplicarPresetPeriodo('hoy')"
              >
                Hoy
              </button>
              <button
                type="button"
                class="px-3 py-2 rounded-xl text-[11px] font-black uppercase tracking-wide transition-all"
                :class="presetBtnClass('ayer')"
                @click="aplicarPresetPeriodo('ayer')"
              >
                Ayer
              </button>
              <button
                type="button"
                class="px-3 py-2 rounded-xl text-[11px] font-black uppercase tracking-wide transition-all"
                :class="presetBtnClass('semana')"
                @click="aplicarPresetPeriodo('semana')"
              >
                Esta semana
                <span class="block text-[9px] font-bold text-slate-400 normal-case tracking-normal">Lun → dom</span>
              </button>
              <button
                type="button"
                class="px-3 py-2 rounded-xl text-[11px] font-black uppercase tracking-wide transition-all"
                :class="presetBtnClass('semana_ant')"
                @click="aplicarPresetPeriodo('semana_ant')"
              >
                Semana anterior
              </button>
              <button
                type="button"
                class="px-3 py-2 rounded-xl text-[11px] font-black uppercase tracking-wide transition-all"
                :class="presetBtnClass('mes')"
                @click="aplicarPresetPeriodo('mes')"
              >
                Este mes
              </button>
            </div>
            <div class="flex flex-wrap gap-3 sm:gap-4 items-end">
              <div class="min-w-[140px]">
                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1.5">Desde</label>
                <input
                  v-model="fechaInicio"
                  type="date"
                  class="w-full rounded-xl border-0 bg-slate-950/80 text-slate-100 text-sm px-3 py-2.5 ring-1 ring-white/10 focus:ring-2 focus:ring-cyan-500/50"
                  @change="onFechaManual"
                />
              </div>
              <div class="min-w-[140px]">
                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1.5">Hasta</label>
                <input
                  v-model="fechaFin"
                  type="date"
                  class="w-full rounded-xl border-0 bg-slate-950/80 text-slate-100 text-sm px-3 py-2.5 ring-1 ring-white/10 focus:ring-2 focus:ring-cyan-500/50"
                  @change="onFechaManual"
                />
              </div>
              <div class="min-w-[200px] flex-1 sm:flex-none">
                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1.5">Vendedor</label>
                <select
                  v-model="vendedorId"
                  class="w-full rounded-xl border-0 bg-slate-950/80 text-slate-100 text-sm px-3 py-2.5 ring-1 ring-white/10 focus:ring-2 focus:ring-cyan-500/50"
                  @change="aplicar"
                >
                  <option value="">Todos</option>
                  <option v-for="v in vendedores" :key="v.id" :value="v.id">{{ v.name }}</option>
                </select>
              </div>
              <label
                class="inline-flex items-center gap-2.5 px-3 py-2 rounded-xl bg-slate-950/50 ring-1 ring-white/10 text-xs text-slate-300 cursor-pointer"
                title="Solo ventas marcadas como pagadas (útil para corte de caja)"
              >
                <input v-model="soloPagadas" type="checkbox" class="rounded border-slate-600 text-cyan-500 focus:ring-cyan-500/40" @change="aplicar" />
                <span class="font-semibold">Solo cobradas</span>
              </label>
              <button
                type="button"
                class="ml-auto sm:ml-0 px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest bg-gradient-to-r from-cyan-600 to-blue-600 text-white shadow-lg shadow-cyan-900/30 hover:from-cyan-500 hover:to-blue-500 disabled:opacity-50 transition-all"
                :disabled="loading"
                @click="aplicar"
              >
                {{ loading ? 'Actualizando…' : 'Aplicar' }}
              </button>
            </div>
            <p v-if="truncated" class="mt-4 text-xs text-amber-300/95 flex items-start gap-2">
              <span class="mt-0.5 inline-block h-1.5 w-1.5 rounded-full bg-amber-400 shrink-0" />
              Hay más ventas en el rango de las que se muestran. Acota fechas o filtra por vendedor. Total en rango:
              <strong class="text-white">{{ total_rows }}</strong>.
            </p>
          </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3 sm:gap-4">
          <div class="rounded-2xl p-4 bg-slate-900/60 ring-1 ring-white/10">
            <p class="text-[10px] font-black uppercase text-slate-500">Total (documentos)</p>
            <p class="mt-1 text-xl font-black text-white tabular-nums">{{ fmtMoneda(totales.monto_total) }}</p>
            <p class="text-[11px] text-slate-500 mt-1">{{ totales.cantidad ?? 0 }} ventas</p>
          </div>
          <div class="rounded-2xl p-4 bg-emerald-950/40 ring-1 ring-emerald-500/25">
            <p class="text-[10px] font-black uppercase text-emerald-400/90">Cobrado</p>
            <p class="mt-1 text-xl font-black text-emerald-200 tabular-nums">{{ fmtMoneda(totales.monto_pagado) }}</p>
            <p class="text-[11px] text-slate-500 mt-1">{{ totales.cantidad_pagadas ?? 0 }} pagadas</p>
          </div>
          <div class="rounded-2xl p-4 bg-amber-950/30 ring-1 ring-amber-500/25">
            <p class="text-[10px] font-black uppercase text-amber-300/90">Pendiente</p>
            <p class="mt-1 text-xl font-black text-amber-200 tabular-nums">{{ fmtMoneda(totales.monto_pendiente) }}</p>
            <p class="text-[11px] text-slate-500 mt-1">{{ totales.cantidad_pendientes ?? 0 }} sin cobrar</p>
          </div>
          <div class="rounded-2xl p-4 bg-violet-950/40 ring-1 ring-violet-500/25">
            <p class="text-[10px] font-black uppercase text-violet-300/90">Con cita</p>
            <p class="mt-1 text-xl font-black text-violet-100 tabular-nums">{{ totales.con_cita ?? 0 }}</p>
            <p class="text-[11px] text-slate-500 mt-1">{{ totales.sin_cita ?? 0 }} sin cita</p>
          </div>
          <div class="rounded-2xl p-4 bg-slate-900/60 ring-1 ring-white/10 col-span-2 md:col-span-1 xl:col-span-1">
            <p class="text-[10px] font-black uppercase text-slate-500">Método (solo cobradas)</p>
            <ul class="mt-2 space-y-1 text-[11px] text-slate-300 max-h-24 overflow-y-auto">
              <li v-for="m in por_metodo_pago" :key="m.metodo" class="flex justify-between gap-2">
                <span class="truncate">{{ m.metodo }}</span>
                <span class="font-bold text-white shrink-0">{{ fmtMoneda(m.total) }}</span>
              </li>
              <li v-if="!por_metodo_pago?.length" class="text-slate-500">—</li>
            </ul>
          </div>
        </div>

        <div v-if="resumen_por_vendedor?.length" class="rounded-2xl bg-slate-900/50 ring-1 ring-white/10 p-4 sm:p-5">
          <h2 class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-3">Por vendedor</h2>
          <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <div
              v-for="s in resumen_por_vendedor"
              :key="s.vendedor_nombre"
              class="rounded-xl bg-slate-950/60 ring-1 ring-white/5 px-4 py-3"
            >
              <p class="text-sm font-bold text-white truncate">{{ s.vendedor_nombre }}</p>
              <p class="text-[11px] text-slate-500 mt-1">{{ s.cantidad }} ventas</p>
              <p class="text-lg font-black text-cyan-300 mt-1 tabular-nums">{{ fmtMoneda(s.total) }}</p>
              <p class="text-[10px] text-slate-500 mt-1">
                Cobrado {{ fmtMoneda(s.total_pagado) }} · Pendiente {{ fmtMoneda(s.total_pendiente) }}
              </p>
            </div>
          </div>
        </div>

        <div class="rounded-2xl sm:rounded-3xl bg-slate-900/40 ring-1 ring-white/10 overflow-hidden">
          <div class="px-4 sm:px-5 py-4 border-b border-white/5 flex flex-col sm:flex-row sm:items-center gap-3">
            <h2 class="text-sm font-black text-white uppercase tracking-wide">Detalle de ventas</h2>
            <input
              v-model="busqueda"
              type="search"
              placeholder="Buscar en resultados…"
              class="sm:ml-auto max-w-md w-full rounded-xl border-0 bg-slate-950/80 text-slate-100 text-sm px-3 py-2 ring-1 ring-white/10 focus:ring-2 focus:ring-cyan-500/50"
            />
          </div>
          <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left text-sm">
              <thead class="bg-slate-950/80 text-[10px] font-black uppercase tracking-widest text-slate-500">
                <tr>
                  <th class="px-2 py-3 w-8" />
                  <th class="px-2 py-3">Fecha</th>
                  <th class="px-2 py-3">Folio</th>
                  <th class="px-2 py-3">Vendedor</th>
                  <th class="px-2 py-3">Cliente</th>
                  <th class="px-2 py-3 min-w-[200px]">Qué se vendió</th>
                  <th class="px-2 py-3 text-right">Total</th>
                  <th class="px-2 py-3">Pago</th>
                  <th class="px-2 py-3">Cita</th>
                  <th class="px-2 py-3 text-right w-28">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <template v-for="r in registrosFiltrados" :key="r.id">
                  <tr
                    class="border-b border-white/5 hover:bg-white/[0.04] align-top cursor-pointer transition-colors"
                    @click="toggleRow(r.id)"
                  >
                    <td class="px-2 py-2.5">
                      <button
                        type="button"
                        class="p-1 rounded-lg hover:bg-white/10 text-slate-400"
                        :aria-expanded="!!expanded[r.id]"
                        @click.stop="toggleRow(r.id)"
                      >
                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-90': expanded[r.id] }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                      </button>
                    </td>
                    <td class="px-2 py-2.5 whitespace-nowrap text-slate-300 tabular-nums">{{ fmtFecha(r.fecha) }}</td>
                    <td class="px-2 py-2.5 font-mono text-amber-200/90">{{ r.numero_venta || '—' }}</td>
                    <td class="px-2 py-2.5 text-slate-200">{{ r.vendedor_nombre || '—' }}</td>
                    <td class="px-2 py-2.5 text-slate-200 min-w-0 break-words">{{ r.cliente || '—' }}</td>
                    <td class="px-2 py-2.5 text-slate-400 text-[11px] leading-snug min-w-[250px] align-top">
                      <template v-if="r.items_detalle && r.items_detalle.length">
                        <div v-for="(it, idx) in r.items_detalle" :key="idx" class="mb-1.5 last:mb-0 pb-1.5 border-b border-white/5 last:border-0 relative pl-3">
                          <span class="absolute left-0 top-1 w-1 h-1 bg-cyan-500/50 rounded-full"></span>
                          <span class="font-mono text-cyan-300/90 font-bold">{{ it.cantidad }}x</span> 
                          <span class="text-slate-200 ml-1 whitespace-normal break-words inline-block leading-relaxed">
                            {{ it.nombre }} 
                            <span class="text-slate-400/80 font-normal whitespace-nowrap ml-1">({{ fmtMoneda(it.precio) }} c/u)</span>
                          </span>
                          <div v-if="it.tipo" class="text-slate-500 text-[9px] uppercase tracking-wider mt-0.5 ml-4">{{ it.tipo }}</div>
                        </div>
                      </template>
                      <template v-else>
                        <span class="whitespace-normal break-words leading-relaxed">{{ r.items_resumen || '—' }}</span>
                      </template>
                    </td>
                    <td class="px-2 py-2.5 text-right font-bold text-white tabular-nums align-top">{{ fmtMoneda(r.total) }}</td>
                    <td class="px-2 py-2.5">
                      <span class="text-[10px] font-bold uppercase" :class="r.pagado ? 'text-emerald-400' : 'text-amber-400'">
                        {{ r.pagado ? 'Pagado' : 'Pendiente' }}
                      </span>
                      <span class="block text-[10px] text-slate-500">{{ r.metodo_pago || '—' }}</span>
                    </td>
                    <td class="px-2 py-2.5 text-[11px]">
                      <template v-if="r.cita_id">
                        <span class="text-slate-300">#{{ r.cita_folio || r.cita_id }}</span>
                        <span class="block text-slate-500">{{ fmtFecha(r.cita_fecha) }}</span>
                      </template>
                      <span v-else class="text-slate-600">—</span>
                    </td>
                    <td class="px-2 py-2.5 text-right" @click.stop>
                      <div class="inline-flex flex-col gap-1 items-end">
                        <Link
                          v-if="$can('view ventas')"
                          :href="route('ventas.show', r.id)"
                          class="text-[10px] font-black uppercase text-cyan-400 hover:text-cyan-300"
                        >
                          Ver venta
                        </Link>
                        <Link
                          v-if="r.cita_id && $can('view citas')"
                          :href="route('citas.show', r.cita_id)"
                          class="text-[10px] font-black uppercase text-violet-300 hover:text-violet-200"
                        >
                          Ver cita
                        </Link>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="expanded[r.id]" class="bg-slate-950/70 border-b border-white/5">
                    <td colspan="10" class="px-4 sm:px-6 py-4">
                      <p class="text-[10px] font-black uppercase text-slate-500 mb-2">Líneas de la venta</p>
                      <ul class="space-y-2 text-xs text-slate-300">
                        <li v-for="(it, idx) in r.items_detalle || []" :key="idx" class="flex flex-wrap gap-x-3 gap-y-1 border-b border-white/5 pb-2 last:border-0">
                          <span class="font-mono text-amber-200/80">{{ it.cantidad }}×</span>
                          <span class="flex-1 min-w-0">{{ it.nombre }}</span>
                          <span v-if="it.tipo" class="text-slate-500 text-[10px]">{{ it.tipo }}</span>
                          <span class="font-bold text-white tabular-nums">{{ fmtMoneda(it.subtotal) }}</span>
                        </li>
                        <li v-if="!(r.items_detalle && r.items_detalle.length)" class="text-slate-500">Sin líneas cargadas.</li>
                      </ul>
                    </td>
                  </tr>
                </template>
                <tr v-if="!registrosFiltrados.length">
                  <td colspan="10" class="px-6 py-16 text-center text-slate-500">
                    <p class="text-lg font-bold text-slate-400">Sin resultados</p>
                    <p class="mt-1 text-sm">Ajusta fechas o filtros; o limpia la búsqueda local.</p>
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
      </div>
    </div>
  </AppLayout>
</template>
