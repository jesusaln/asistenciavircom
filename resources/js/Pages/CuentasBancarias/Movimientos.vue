<template>
  <div class="min-h-screen bg-[var(--ui-surface)] transition-colors duration-200">
    <Head :title="`Movimientos - ${cuenta.nombre}`" />

    <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-8">
        <div class="flex items-start gap-3 min-w-0">
          <Link
            :href="route('cuentas-bancarias.show', { cuentas_bancaria: cuenta.id })"
            class="shrink-0 p-2 rounded-xl text-slate-500 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-700 border border-transparent hover:border-brand-500 dark:hover:border-brand-500 transition-colors"
          >
            <FontAwesomeIcon :icon="['fas', 'arrow-left']" />
          </Link>
          <div class="min-w-0">
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white tracking-tight">Movimientos bancarios</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1">{{ cuenta.nombre }} · {{ cuenta.banco }}</p>
          </div>
        </div>
        <div class="text-left sm:text-right rounded-2xl border px-4 py-3 bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-600 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Saldo actual</p>
          <p class="text-2xl font-black text-slate-900 dark:text-white tabular-nums">${{ formatMonto(cuenta.saldo_actual) }}</p>
        </div>
      </div>

      <!-- Filtros -->
      <div
        class="rounded-2xl border p-6 mb-6 bg-white dark:bg-slate-800 border-slate-200/80 dark:border-slate-800 shadow-[0_10px_40px_rgba(0,0,0,0.04)] dark:shadow-[0_10px_40px_rgba(0,0,0,0.2)]"
      >
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Desde</label>
            <input
              v-model="filters.fecha_desde"
              type="date"
              class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-brand-500 focus:border-transparent"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Hasta</label>
            <input
              v-model="filters.fecha_hasta"
              type="date"
              class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-brand-500 focus:border-transparent"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Tipo</label>
            <select
              v-model="filters.tipo"
              class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-brand-500 focus:border-transparent"
            >
              <option value="">Todos</option>
              <option value="deposito">Depósitos</option>
              <option value="retiro">Retiros</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Origen</label>
            <select
              v-model="filters.origen_tipo"
              class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-brand-500 focus:border-transparent"
            >
              <option value="">Todos</option>
              <option value="venta">Ventas</option>
              <option value="renta">Rentas</option>
              <option value="cobro">Cobros</option>
              <option value="prestamo">Préstamos</option>
              <option value="traspaso">Traspasos</option>
              <option value="pago">Pagos</option>
              <option value="otro">Otros</option>
            </select>
          </div>
        </div>
        <div class="mt-4 flex flex-wrap justify-end gap-2">
          <button
            type="button"
            class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700 font-medium transition-colors"
            @click="limpiarFiltros"
          >
            Limpiar
          </button>
          <button
            type="button"
            class="inline-flex items-center px-4 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700 dark:bg-brand-500 dark:hover:bg-blue-400 font-semibold shadow-md transition-colors"
            @click="aplicarFiltros"
          >
            <FontAwesomeIcon :icon="['fas', 'search']" class="mr-2" />
            Filtrar
          </button>
        </div>
      </div>

      <!-- Estadísticas del período -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div
          class="rounded-2xl p-5 text-white border border-white/10 shadow-xl relative overflow-hidden bg-gradient-to-br from-emerald-600 to-emerald-800 dark:from-emerald-900/90 dark:to-emerald-950"
        >
          <div class="absolute inset-0 bg-gradient-to-tr from-white/10 to-transparent pointer-events-none" />
          <div class="relative">
            <p class="text-emerald-100 dark:text-emerald-200/90 text-sm font-medium">Total depósitos</p>
            <p class="text-2xl font-black tabular-nums mt-1">${{ formatMonto(stats.total_depositos) }}</p>
          </div>
        </div>
        <div
          class="rounded-2xl p-5 text-white border border-white/10 shadow-xl relative overflow-hidden bg-gradient-to-br from-rose-600 to-rose-800 dark:from-rose-900/90 dark:to-rose-950"
        >
          <div class="absolute inset-0 bg-gradient-to-tr from-white/10 to-transparent pointer-events-none" />
          <div class="relative">
            <p class="text-rose-100 dark:text-rose-200/90 text-sm font-medium">Total retiros</p>
            <p class="text-2xl font-black tabular-nums mt-1">${{ formatMonto(stats.total_retiros) }}</p>
          </div>
        </div>
        <div
          class="rounded-2xl p-5 text-white border border-white/10 shadow-xl relative overflow-hidden bg-gradient-to-br from-blue-600 to-blue-800 dark:from-slate-800 dark:to-slate-900"
        >
          <div class="absolute inset-0 bg-gradient-to-tr from-white/10 to-transparent pointer-events-none" />
          <div class="relative">
            <p class="text-blue-100 dark:text-slate-200 text-sm font-medium">Movimientos</p>
            <p class="text-2xl font-black tabular-nums mt-1">{{ stats.cantidad_movimientos }}</p>
          </div>
        </div>
      </div>

      <!-- Tabla de movimientos -->
      <div
        class="rounded-2xl border overflow-hidden bg-white dark:bg-slate-800 border-slate-200/80 dark:border-slate-800 shadow-[0_10px_40px_rgba(0,0,0,0.04)] dark:shadow-[0_10px_40px_rgba(0,0,0,0.25)]"
      >
        <div
          class="px-4 sm:px-6 py-4 border-b border-slate-300 dark:border-slate-600 flex flex-wrap items-center justify-between gap-2 bg-slate-50/80 dark:bg-slate-800/50"
        >
          <h3 class="text-lg font-bold text-slate-900 dark:text-white">Movimientos</h3>
          <span class="text-sm text-slate-500 dark:text-slate-400">
            {{ movimientos.from || 0 }} – {{ movimientos.to || 0 }} de {{ movimientos.total || 0 }}
          </span>
        </div>

        <div v-if="movimientos.data && movimientos.data.length > 0" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-800/50">
              <tr>
                <th
                  class="px-4 sm:px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-200 uppercase tracking-wider"
                >
                  Fecha
                </th>
                <th
                  class="px-4 sm:px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-200 uppercase tracking-wider"
                >
                  Concepto
                </th>
                <th
                  class="px-4 sm:px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-200 uppercase tracking-wider"
                >
                  Origen
                </th>
                <th
                  class="px-4 sm:px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-200 uppercase tracking-wider"
                >
                  Tipo
                </th>
                <th
                  class="px-4 sm:px-6 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-200 uppercase tracking-wider"
                >
                  Monto
                </th>
                <th
                  class="px-4 sm:px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-200 uppercase tracking-wider"
                >
                  Estado
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
              <tr
                v-for="mov in movimientos.data"
                :key="mov.id"
                class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
              >
                <td class="px-4 sm:px-6 py-4 text-sm text-slate-900 dark:text-slate-100 whitespace-nowrap">
                  {{ formatFecha(mov.fecha) }}
                </td>
                <td class="px-4 sm:px-6 py-4 text-sm text-slate-900 dark:text-slate-100">
                  <div>{{ mov.concepto || '—' }}</div>
                  <div v-if="mov.referencia" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Ref: {{ mov.referencia }}
                  </div>
                </td>
                <td class="px-4 sm:px-6 py-4">
                  <span :class="getOrigenClass(mov.origen_tipo)" class="px-2.5 py-1 rounded-xl text-xs font-medium">
                    {{ getOrigenLabel(mov.origen_tipo) }}
                  </span>
                </td>
                <td class="px-4 sm:px-6 py-4">
                  <span
                    :class="
                      mov.tipo === 'deposito'
                        ? 'bg-emerald-100 text-emerald-800 dark:text-emerald-200 dark:bg-slate-800/50 dark:text-emerald-300'
                        : 'bg-rose-100 text-rose-800 dark:text-rose-200 dark:bg-rose-900/40 dark:text-rose-300'
                    "
                    class="px-2.5 py-1 rounded-xl text-xs font-medium"
                  >
                    {{ mov.tipo === 'deposito' ? 'Depósito' : 'Retiro' }}
                  </span>
                </td>
                <td
                  class="px-4 sm:px-6 py-4 text-sm text-right font-semibold tabular-nums"
                  :class="
                    mov.tipo === 'deposito'
                      ? 'text-emerald-600 dark:text-slate-400'
                      : 'text-rose-600 dark:text-rose-400'
                  "
                >
                  {{ mov.tipo === 'deposito' ? '+' : '−' }}${{ formatMonto(Math.abs(Number(mov.monto))) }}
                </td>
                <td class="px-4 sm:px-6 py-4">
                  <span :class="getEstadoClass(mov.estado)" class="px-2.5 py-1 rounded-xl text-xs font-medium">
                    {{ getEstadoLabel(mov.estado) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="p-12 text-center">
          <FontAwesomeIcon :icon="['fas', 'receipt']" class="h-12 w-12 text-slate-300 dark:text-slate-500 mb-4 mx-auto" />
          <p class="text-slate-500 dark:text-slate-400 font-medium">No hay movimientos para el período seleccionado</p>
        </div>

        <!-- Paginación -->
        <div
          v-if="movimientos.links && movimientos.links.length > 3"
          class="px-4 sm:px-6 py-4 border-t border-slate-300 dark:border-slate-600 flex flex-wrap items-center justify-center gap-2 bg-slate-50/50 dark:bg-slate-800/30"
        >
          <template v-for="(link, index) in movimientos.links" :key="index">
            <Link
              v-if="link.url"
              :href="link.url"
              :class="[
                'px-3 py-1.5 rounded-xl text-sm font-medium transition-colors',
                link.active
                  ? 'bg-blue-600 text-white dark:bg-brand-500'
                  : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700',
              ]"
              preserve-scroll
              v-html="link.label"
            />
            <span v-else class="px-3 py-1.5 text-sm text-slate-400 dark:text-slate-500" v-html="link.label" />
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { ref } from 'vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  cuenta: { type: Object, required: true },
  movimientos: { type: Object, default: () => ({ data: [], links: [] }) },
  filtros: { type: Object, default: () => ({}) },
  stats: { type: Object, default: () => ({ total_depositos: 0, total_retiros: 0, cantidad_movimientos: 0 }) },
  origenes_disponibles: { type: Array, default: () => [] },
})

const filters = ref({
  fecha_desde: props.filtros.fecha_desde || '',
  fecha_hasta: props.filtros.fecha_hasta || '',
  tipo: props.filtros.tipo || '',
  origen_tipo: props.filtros.origen_tipo || '',
})

const formatMonto = (val) => {
  const num = Number(val) || 0
  return num.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatFecha = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const getEstadoClass = (estado) => {
  const map = {
    pendiente: 'bg-brand-100 text-brand-800 dark:text-brand-200 dark:bg-brand-900/40 dark:text-amber-300',
    conciliado: 'bg-emerald-100 text-emerald-800 dark:text-emerald-200 dark:bg-slate-800/50 dark:text-emerald-300',
    ignorado: 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
  }
  return map[estado] || 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200'
}

const getEstadoLabel = (estado) => {
  return (
    {
      pendiente: 'Pendiente',
      conciliado: 'Conciliado',
      ignorado: 'Ignorado',
    }[estado] || estado
  )
}

const getOrigenClass = (origen) => {
  const map = {
    venta: 'bg-sky-100 text-sky-800 dark:text-sky-200 dark:bg-sky-900/40 dark:text-blue-300',
    renta: 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
    cobro: 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-indigo-300',
    prestamo: 'bg-emerald-100 text-emerald-800 dark:text-emerald-200 dark:bg-slate-800/50 dark:text-emerald-300',
    traspaso: 'bg-brand-100 text-brand-800 dark:bg-brand-900/40 dark:text-orange-300',
    pago: 'bg-pink-100 text-pink-800 dark:bg-pink-900/40 dark:text-pink-300',
    otro: 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
  }
  return map[origen] || 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200'
}

const getOrigenLabel = (origen) => {
  return (
    {
      venta: 'Venta',
      renta: 'Renta',
      cobro: 'Cobro',
      prestamo: 'Préstamo',
      traspaso: 'Traspaso',
      pago: 'Pago',
      otro: 'Otro',
    }[origen] || 'Sin origen'
  )
}

const aplicarFiltros = () => {
  router.get(
    route('cuentas-bancarias.movimientos', { cuentas_bancaria: props.cuenta.id }),
    {
      fecha_desde: filters.value.fecha_desde || undefined,
      fecha_hasta: filters.value.fecha_hasta || undefined,
      tipo: filters.value.tipo || undefined,
      origen_tipo: filters.value.origen_tipo || undefined,
    },
    {
      preserveState: true,
      preserveScroll: true,
    },
  )
}

const limpiarFiltros = () => {
  const now = new Date()
  filters.value = {
    fecha_desde: new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0],
    fecha_hasta: new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0],
    tipo: '',
    origen_tipo: '',
  }
  aplicarFiltros()
}
</script>
