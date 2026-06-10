<script setup>
import { useFormatters } from '@/Composables/useFormatters';
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

const props = defineProps({
  registros: { type: Array, default: () => [] },
  resumen: { type: Object, default: () => ({}) },
  filtros: { type: Object, default: () => ({}) },
})

const { formatCurrency, formatShortDate } = useFormatters();

const fechaInicio = ref(props.filtros.fecha_inicio || '')
const fechaFin = ref(props.filtros.fecha_fin || '')
const busqueda = ref('')
const loading = ref(false)
const presetActivo = ref(null)
const expandedRows = ref({})

const toggleRow = (id) => {
  expandedRows.value[id] = !expandedRows.value[id]
}

const aplicar = () => {
  loading.value = true
  router.get(
    route('reportes.ventas-utilidad'),
    {
      fecha_inicio: fechaInicio.value,
      fecha_fin: fechaFin.value,
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
      const refDate = subWeeks(now, 1)
      const s = startOfWeek(refDate, { weekStartsOn: 1 })
      const e = endOfWeek(refDate, { weekStartsOn: 1 })
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

const registrosFiltrados = computed(() => {
  if (!busqueda.value.trim()) return props.registros
  const s = busqueda.value.toLowerCase()
  return props.registros.filter((r) => {
    return [
      r.numero_venta,
      r.cliente,
      r.vendedor,
      r.metodo_pago,
    ].filter(Boolean).some(val => val.toLowerCase().includes(s))
  })
})

const presetBtnClass = (id) =>
  presetActivo.value === id
    ? 'bg-brand-500 text-white shadow-lg shadow-brand-500/20 ring-2 ring-brand-500/50'
    : 'bg-white dark:bg-white/5 text-slate-600 dark:text-slate-300 ring-1 ring-slate-200 dark:ring-white/10 hover:bg-slate-50 dark:hover:bg-white/10'

</script>

<template>
  <Head title="Reporte de Ventas y Utilidad" />

  <AppLayout title="Reporte de Ventas y Utilidad">
    <div class="p-4 sm:p-6 lg:p-8 bg-[var(--ui-surface)] min-h-screen">
      <!-- Header -->
      <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
          <p class="text-[10px] font-black uppercase tracking-[0.3em] text-brand-500 mb-2">Análisis Financiero</p>
          <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">
            Ventas y Utilidad
          </h1>
          <p class="mt-2 text-slate-500 dark:text-slate-400 max-w-2xl text-sm leading-relaxed">
            Monitorea el rendimiento económico de tu negocio. Compara ingresos contra costos directos para conocer tu utilidad bruta real por periodo.
          </p>
        </div>
        <div class="flex items-center gap-3">
          <Link :href="route('panel')" class="px-4 py-2 rounded-xl bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 transition-all">
            ← Volver al Panel
          </Link>
        </div>
      </div>

      <!-- Filters -->
      <div class="mb-8 p-6 rounded-3xl bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-white/10 shadow-xl shadow-slate-200/50 dark:shadow-none">
        <div class="flex flex-wrap gap-2 mb-6">
          <button v-for="p in ['hoy', 'ayer', 'semana', 'semana_ant', 'mes']" :key="p" @click="aplicarPresetPeriodo(p)" :class="presetBtnClass(p)" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all">
            {{ p === 'semana_ant' ? 'Semana Ant.' : p }}
          </button>
        </div>
        
        <div class="flex flex-wrap items-end gap-4">
          <div class="flex-1 min-w-[150px]">
            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Fecha Inicio</label>
            <input v-model="fechaInicio" type="date" @change="onFechaManual" class="w-full rounded-xl border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-slate-950/50 text-sm focus:ring-brand-500/50" />
          </div>
          <div class="flex-1 min-w-[150px]">
            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Fecha Fin</label>
            <input v-model="fechaFin" type="date" @change="onFechaManual" class="w-full rounded-xl border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-slate-950/50 text-sm focus:ring-brand-500/50" />
          </div>
          <button @click="aplicar" :disabled="loading" class="px-8 py-2.5 rounded-xl bg-brand-500 text-white text-xs font-black uppercase tracking-widest hover:bg-brand-600 transition-all shadow-lg shadow-brand-500/30 disabled:opacity-50">
            {{ loading ? 'Cargando...' : 'Actualizar Reporte' }}
          </button>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="p-6 rounded-3xl bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-white/10 shadow-sm">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-2xl bg-blue-500/10 flex items-center justify-center text-blue-500">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2z" /></svg>
            </div>
            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Ventas (Neto s/IVA)</span>
          </div>
          <p class="text-2xl font-black text-slate-900 dark:text-white tabular-nums">{{ formatCurrency(resumen.monto_total) }}</p>
          <div class="mt-1 flex flex-col gap-0.5 border-t border-slate-100 dark:border-white/5 pt-2">
            <p class="text-[9px] text-slate-400 font-bold uppercase tracking-tighter">📦 Prod: {{ formatCurrency(resumen.total_productos) }}</p>
            <p class="text-[9px] text-slate-400 font-bold uppercase tracking-tighter">🛠️ Serv: {{ formatCurrency(resumen.total_servicios) }}</p>
            <div class="mt-1 pt-1 border-t border-dotted border-slate-200 dark:border-white/10">
                <p class="text-[8px] text-slate-400 uppercase">IVA: {{ formatCurrency(resumen.monto_iva) }}</p>
                <p class="text-[8px] text-slate-400 font-black uppercase">Total: {{ formatCurrency(resumen.monto_con_iva) }}</p>
            </div>
          </div>
        </div>

        <div class="p-6 rounded-3xl bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-white/10 shadow-sm">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-2xl bg-rose-500/10 flex items-center justify-center text-rose-500">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
            </div>
            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Costo Directo</span>
          </div>
          <p class="text-2xl font-black text-slate-900 dark:text-white tabular-nums">{{ formatCurrency(resumen.costo_total) }}</p>
          <div class="mt-1 flex flex-col gap-0.5 border-t border-slate-100 dark:border-white/5 pt-2">
            <p class="text-[9px] text-slate-400 font-bold uppercase tracking-tighter">📦 Prod: {{ formatCurrency(resumen.costo_productos) }}</p>
            <p class="text-[9px] text-slate-400 font-bold uppercase tracking-tighter">🛠️ Serv: {{ formatCurrency(resumen.costo_servicios) }}</p>
          </div>
        </div>

        <div class="p-6 rounded-3xl bg-emerald-500/10 border border-emerald-500/20 shadow-sm">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-2xl bg-emerald-500 flex items-center justify-center text-white shadow-lg shadow-emerald-500/30">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
            </div>
            <span class="text-[10px] font-black uppercase tracking-widest text-emerald-600 dark:text-emerald-400">Utilidad Bruta</span>
          </div>
          <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 tabular-nums">{{ formatCurrency(resumen.utilidad_total) }}</p>
          <div class="mt-1 flex flex-col gap-0.5 border-t border-slate-100 dark:border-white/5 pt-2 mb-2">
            <p class="text-[9px] text-slate-400 font-bold uppercase tracking-tighter">📦 Prod: {{ formatCurrency(resumen.utilidad_productos) }}</p>
            <p class="text-[9px] text-slate-400 font-bold uppercase tracking-tighter">🛠️ Serv: {{ formatCurrency(resumen.utilidad_servicios) }}</p>
          </div>
          <div class="flex items-center gap-2 mt-1">
            <span class="px-2 py-0.5 rounded-md bg-emerald-500 text-white text-[10px] font-black">{{ resumen.margen_promedio }}%</span>
            <span class="text-[10px] text-emerald-600/70 font-bold uppercase">Margen prom.</span>
          </div>
        </div>

        <div class="p-6 rounded-3xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 shadow-xl shadow-slate-900/20 dark:shadow-none">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-2xl bg-white/10 dark:bg-slate-900/10 flex items-center justify-center">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
            </div>
            <span class="text-[10px] font-black uppercase tracking-widest opacity-60">Rendimiento</span>
          </div>
          <p class="text-2xl font-black tabular-nums">{{ formatCurrency(resumen.total_ventas > 0 ? resumen.monto_total / resumen.total_ventas : 0) }}</p>
          <p class="text-[10px] font-bold uppercase opacity-60 mt-1">Ticket Promedio</p>
        </div>
      </div>

      <!-- Table Section -->
      <div class="rounded-3xl bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-white/10 overflow-hidden shadow-sm">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-white/5 flex items-center justify-between bg-slate-50/50 dark:bg-transparent">
          <h2 class="text-xs font-black uppercase tracking-widest text-slate-500">Detalle de Operaciones</h2>
          <div class="relative w-64">
            <input v-model="busqueda" type="text" placeholder="Filtrar por folio o cliente..." class="w-full rounded-xl border-slate-200 dark:border-white/10 bg-white dark:bg-slate-950/50 text-xs py-1.5 pl-8 focus:ring-brand-500/40" />
            <svg class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
          </div>
        </div>
        
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50/80 dark:bg-slate-900/30 text-[10px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-100 dark:border-white/5">
                <th class="px-4 py-4 w-10"></th>
                <th class="px-6 py-4">Fecha</th>
                <th class="px-6 py-4">Folio</th>
                <th class="px-6 py-4">Cliente / Vendedor</th>
                <th class="px-6 py-4 text-right">Monto Venta</th>
                <th class="px-6 py-4 text-right">Costo Directo</th>
                <th class="px-6 py-4 text-right">Utilidad</th>
                <th class="px-6 py-4 text-center">Margen</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-white/5">
              <template v-for="r in registrosFiltrados" :key="r.id">
                <tr @click="toggleRow(r.id)" class="hover:bg-slate-50/50 dark:hover:bg-white/[0.02] transition-colors group cursor-pointer">
                  <td class="px-4 py-4">
                    <button class="p-1 rounded-lg hover:bg-slate-200 dark:hover:bg-white/10 transition-colors" :class="{ 'rotate-90': expandedRows[r.id] }">
                      <svg class="w-4 h-4 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </button>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-[11px] font-bold text-slate-500 tabular-nums uppercase">{{ format(parseISO(r.fecha), 'dd MMM yyyy', { locale: es }) }}</span>
                  </td>
                  <td class="px-6 py-4">
                    <Link :href="route('ventas.show', r.id)" class="text-[11px] font-black text-brand-500 hover:underline" @click.stop>{{ r.numero_venta }}</Link>
                  </td>
                  <td class="px-6 py-4 min-w-[200px]">
                    <p class="text-[12px] font-black text-slate-700 dark:text-white truncate max-w-[180px]">{{ r.cliente }}</p>
                    <div class="flex items-center gap-2 mt-1">
                      <p class="text-[10px] text-slate-400 font-bold uppercase">{{ r.vendedor }}</p>
                      <span v-if="r.tiene_productos" class="px-1.5 py-0.5 rounded bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-[8px] font-black uppercase tracking-tighter">Producto</span>
                      <span v-if="r.tiene_servicios" class="px-1.5 py-0.5 rounded bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 text-[8px] font-black uppercase tracking-tighter">Servicio</span>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-right font-black text-slate-900 dark:text-white tabular-nums">{{ formatCurrency(r.total) }}</td>
                  <td class="px-6 py-4 text-right font-bold text-slate-500 tabular-nums">{{ formatCurrency(r.costo) }}</td>
                  <td class="px-6 py-4 text-right font-black text-emerald-600 dark:text-emerald-400 tabular-nums">
                    {{ formatCurrency(r.utilidad) }}
                  </td>
                  <td class="px-6 py-4 text-center">
                    <span class="px-2 py-0.5 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-[10px] font-black">
                      {{ r.margen }}%
                    </span>
                  </td>
                </tr>
                <!-- Detail Row -->
                <tr v-if="expandedRows[r.id]" class="bg-slate-50/30 dark:bg-white/[0.01]">
                  <td colspan="8" class="px-10 py-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                      <div class="space-y-3">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4">Artículos y Servicios</p>
                        <div v-for="(it, idx) in r.items" :key="idx" class="flex items-center justify-between gap-4 p-3 rounded-2xl bg-white dark:bg-slate-900/50 border border-slate-100 dark:border-white/5 shadow-sm">
                          <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl flex items-center justify-center text-[10px]" :class="it.tipo === 'Servicio' ? 'bg-purple-500/10 text-purple-500' : 'bg-blue-500/10 text-blue-500'">
                              {{ it.tipo === 'Servicio' ? '🛠️' : '📦' }}
                            </div>
                            <div>
                              <p class="text-xs font-black text-slate-700 dark:text-slate-200">{{ it.nombre }}</p>
                              <p class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">{{ it.cantidad }} unidades x {{ formatCurrency(it.precio) }}</p>
                            </div>
                          </div>
                          <p class="text-xs font-black text-slate-900 dark:text-white">{{ formatCurrency(it.subtotal) }}</p>
                        </div>
                      </div>
                      <div class="p-6 rounded-3xl bg-white dark:bg-slate-900/50 border border-slate-100 dark:border-white/5 space-y-4 shadow-sm">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Resumen Financiero de la Nota</p>
                        <div class="flex justify-between text-xs">
                          <span class="text-slate-500 font-bold uppercase tracking-tighter">Subtotal (Neto)</span>
                          <span class="font-black text-slate-900 dark:text-white">{{ formatCurrency(r.subtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                          <span class="text-slate-500 font-bold uppercase tracking-tighter">Costo Directo</span>
                          <span class="font-bold text-slate-400">{{ formatCurrency(r.costo) }}</span>
                        </div>
                        <div v-if="r.tiene_productos || r.tiene_servicios" class="pl-4 space-y-1 text-[10px] border-l-2 border-slate-200 dark:border-white/10">
                          <div v-if="r.tiene_productos" class="flex justify-between text-slate-400">
                            <span>📦 Costo Prod:</span>
                            <span>{{ formatCurrency(r.costo_productos) }}</span>
                          </div>
                          <div v-if="r.tiene_servicios" class="flex justify-between text-slate-400">
                            <span>🛠️ Costo Serv:</span>
                            <span>{{ formatCurrency(r.costo_servicios) }}</span>
                          </div>
                        </div>
                        <div class="pt-4 border-t border-slate-100 dark:border-white/5 flex justify-between">
                          <span class="text-xs font-black uppercase tracking-widest text-emerald-600">Utilidad Bruta</span>
                          <span class="text-sm font-black text-emerald-600">{{ formatCurrency(r.utilidad) }}</span>
                        </div>
                        <div v-if="r.tiene_productos || r.tiene_servicios" class="pl-4 space-y-1 text-[10px] border-l-2 border-emerald-500/30">
                          <div v-if="r.tiene_productos" class="flex justify-between text-emerald-600/70 dark:text-emerald-400/70">
                            <span>📦 Util. Prod:</span>
                            <span>{{ formatCurrency(r.utilidad_productos) }}</span>
                          </div>
                          <div v-if="r.tiene_servicios" class="flex justify-between text-emerald-600/70 dark:text-emerald-400/70">
                            <span>🛠️ Util. Serv:</span>
                            <span>{{ formatCurrency(r.utilidad_servicios) }}</span>
                          </div>
                        </div>
                        <div class="flex justify-between items-center">
                          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Margen Bruto</span>
                          <span class="text-[10px] font-black px-2 py-1 rounded-lg bg-emerald-500 text-white shadow-lg shadow-emerald-500/30">{{ r.margen }}%</span>
                        </div>
                        <div class="pt-2 flex justify-between text-[10px]">
                          <span class="text-slate-400">Total c/ IVA (Referencia)</span>
                          <span class="text-slate-400">{{ formatCurrency(r.total) }}</span>
                        </div>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>
              <tr v-if="!registrosFiltrados.length">
                <td colspan="8" class="px-6 py-20 text-center">
                  <div class="flex flex-col items-center">
                    <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-900 flex items-center justify-center text-slate-300 dark:text-slate-700 mb-4">
                      <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
                    </div>
                    <p class="text-sm font-bold text-slate-500">No se encontraron ventas en este periodo</p>
                    <p class="text-xs text-slate-400 mt-1">Intenta ajustando los filtros de fecha o la búsqueda.</p>
                  </div>
                </td>
              </tr>
            </tbody>
            <tfoot v-if="registrosFiltrados.length" class="bg-slate-50/50 dark:bg-slate-900/20 border-t border-slate-100 dark:border-white/5">
              <tr class="text-[11px] font-black text-slate-900 dark:text-white tabular-nums">
                <td colspan="4" class="px-6 py-4 text-right uppercase tracking-widest text-slate-400">Totales filtrados:</td>
                <td class="px-6 py-4 text-right">{{ formatCurrency(registrosFiltrados.reduce((acc, r) => acc + r.total, 0)) }}</td>
                <td class="px-6 py-4 text-right text-slate-500">{{ formatCurrency(registrosFiltrados.reduce((acc, r) => acc + r.costo, 0)) }}</td>
                <td class="px-6 py-4 text-right text-emerald-600 dark:text-emerald-400">{{ formatCurrency(registrosFiltrados.reduce((acc, r) => acc + r.utilidad, 0)) }}</td>
                <td class="px-6 py-4"></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(0,0,0,0.1);
  border-radius: 10px;
}
</style>
