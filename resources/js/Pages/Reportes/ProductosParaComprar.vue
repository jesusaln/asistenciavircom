<script setup>
import { ref, computed } from 'vue'
import { Head, router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { route } from 'ziggy-js'

const props = defineProps({
  productos: { type: Array, default: () => [] },
  categorias: { type: Array, default: () => [] },
  filtros: { type: Object, default: () => ({}) },
})

const searchTerm = ref('')
const categoriaId = ref(props.filtros.categoria_id ?? '')
const loading = ref(false)
const groupByCategoria = ref(true)

const aplicar = () => {
  loading.value = true
  router.get(
    route('reportes.productos-para-comprar'),
    {
      categoria_id: categoriaId.value === '' ? null : categoriaId.value,
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

const productosFiltrados = computed(() => {
  if (!searchTerm.value.trim()) return props.productos
  const s = searchTerm.value.toLowerCase()
  return props.productos.filter((p) => {
    return (
      (p.nombre && p.nombre.toLowerCase().includes(s)) ||
      (p.codigo && p.codigo.toLowerCase().includes(s)) ||
      (p.categoria_nombre && p.categoria_nombre.toLowerCase().includes(s))
    )
  })
})

const totalInversionCalculada = computed(() => {
  let sum = 0
  for (const p of productosFiltrados.value) {
    const min = p.stock_minimo || 5
    let diff = min - p.stock
    if (diff < 1) diff = 1
    sum += (p.precio_compra || 0) * diff
  }
  return sum
})

const productosAgrupados = computed(() => {
  if (!groupByCategoria.value) return null
  const groups = {}
  for (const p of productosFiltrados.value) {
    const cat = p.categoria_nombre || 'Sin categoría'
    if (!groups[cat]) groups[cat] = []
    groups[cat].push(p)
  }
  return groups
})

const fmtMoneda = (val) => {
  if (val == null) return '$0.00'
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN',
  }).format(val)
}

const getCategoryTotal = (prods) => {
  return prods.reduce((sum, p) => {
    const min = p.stock_minimo || 5
    let diff = min - p.stock
    if (diff < 1) diff = 1
    return sum + (p.precio_compra || 0) * diff
  }, 0)
}
</script>

<template>
  <Head title="Productos para comprar" />

  <AppLayout title="Sugerencias de Compra">
    <div
      class="report-compras min-h-[calc(100vh-5rem)] w-full bg-[var(--ui-surface)] text-slate-800 dark:text-slate-100 px-4 pb-10 pt-4 border-t border-slate-200 dark:border-slate-800 transition-all"
    >
      <!-- Header Section -->
      <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6 mb-8">
        <div>
          <p class="text-[10px] font-black uppercase tracking-[0.35em] text-rose-600 dark:text-rose-400/90 mb-2">Abastecimiento e Inventario</p>
          <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">
            Sugerencias de Compra
            <span class="block sm:inline sm:ml-2 text-lg font-bold text-slate-500 dark:text-slate-400">bajo stock y alta rotación</span>
          </h1>
          <p class="mt-2 max-w-3xl text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
            Listado de productos con inventario igual o inferior a su <strong class="text-slate-800 dark:text-slate-200">Stock Mínimo</strong>. 
            Ordenados priorizando los más vendidos históricamente para asegurar disponibilidad de lo que más se mueve.
          </p>
        </div>
        <div class="flex items-center gap-2">
          <Link
            :href="route('panel')"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-wide bg-white dark:bg-white/5 hover:bg-slate-100 dark:hover:bg-white/10 text-slate-700 dark:text-slate-200 ring-1 ring-slate-200 dark:ring-white/10 transition-colors shadow-sm"
          >
            ← Regresar
          </Link>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="relative overflow-hidden rounded-[2rem] p-6 bg-white dark:bg-slate-800/50 ring-1 ring-slate-100 dark:ring-white/10 shadow-sm">
          <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-brand-500/10 blur-2xl"></div>
          <p class="text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500">Productos Críticos</p>
          <p class="mt-1 text-2xl font-black text-slate-800 dark:text-white tabular-nums">{{ productosFiltrados.length }}</p>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Requieren atención inmediata</p>
        </div>

        <div class="relative overflow-hidden rounded-[2rem] p-6 bg-white dark:bg-slate-800/50 ring-1 ring-emerald-100 dark:ring-emerald-500/20 shadow-sm">
          <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-brand-500/10 blur-2xl"></div>
          <p class="text-[10px] font-black uppercase tracking-wide text-emerald-600 dark:text-slate-400/80">Inversión Estimada</p>
          <p class="mt-1 text-2xl font-black text-emerald-600 dark:text-emerald-300 tabular-nums">{{ fmtMoneda(totalInversionCalculada) }}</p>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Costo para surtir al stock mínimo</p>
        </div>

        <div class="relative overflow-hidden rounded-[2rem] p-6 bg-white dark:bg-slate-800/50 ring-1 ring-cyan-100 dark:ring-cyan-500/20 shadow-sm">
          <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-cyan-500/10 blur-2xl"></div>
          <p class="text-[10px] font-black uppercase tracking-wide text-cyan-600 dark:text-cyan-400/80">Categorías Afectadas</p>
          <p class="mt-1 text-2xl font-black text-cyan-600 dark:text-cyan-200 tabular-nums">{{ Object.keys(productosAgrupados || {}).length }}</p>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Diversidad de inventario bajo</p>
        </div>

        <div class="relative overflow-hidden rounded-[2rem] p-6 bg-white dark:bg-slate-800/50 ring-1 ring-brand-100 dark:ring-brand-500/20 shadow-sm">
          <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-brand-500/10 blur-2xl"></div>
          <p class="text-[10px] font-black uppercase tracking-wide text-brand-600 dark:text-brand-400/80">Inversión Promedio</p>
          <p class="mt-1 text-2xl font-black text-brand-600 dark:text-brand-200 tabular-nums">{{ fmtMoneda(productosFiltrados.length ? totalInversionCalculada / productosFiltrados.length : 0) }}</p>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Costo por unidad sugerida</p>
        </div>
      </div>

      <!-- Filters Area -->
      <div class="rounded-3xl bg-white dark:bg-slate-800/50 ring-1 ring-slate-100 dark:ring-white/10 p-5 mb-8 dark:backdrop-blur-md shadow-sm">
        <div class="flex flex-wrap gap-4 items-end">
          <div class="flex-1 min-w-[280px]">
            <label class="block text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 mb-2">Búsqueda Rápida</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 dark:text-slate-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
              </span>
              <input 
                v-model="searchTerm" 
                type="text"
                placeholder="Código, nombre o categoría..."
                class="w-full pl-10 pr-4 py-3 rounded-xl border-0 bg-[var(--ui-surface)] dark:bg-slate-950/80 text-slate-800 dark:text-slate-100 text-sm ring-1 ring-slate-200 dark:ring-white/10 focus:ring-2 focus:ring-brand-500/50"
              >
            </div>
          </div>

          <div class="w-full md:w-64">
            <label class="block text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 mb-2">Filtrar por Categoría</label>
            <select 
              v-model="categoriaId"
              class="w-full rounded-xl border-0 bg-[var(--ui-surface)] dark:bg-slate-950/80 text-slate-800 dark:text-slate-100 text-sm px-4 py-3 ring-1 ring-slate-200 dark:ring-white/10 focus:ring-2 focus:ring-brand-500/50"
              @change="aplicar"
            >
              <option value="">Todas las categorías</option>
              <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.nombre }}</option>
            </select>
          </div>

          <div class="flex items-center gap-2">
            <label class="inline-flex items-center gap-2 px-4 py-3 rounded-xl bg-[var(--ui-surface)] dark:bg-slate-950/50 ring-1 ring-slate-200 dark:ring-white/10 text-xs text-slate-500 dark:text-slate-200 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-950/80 transition-colors">
              <input v-model="groupByCategoria" type="checkbox" class="rounded-xl border-slate-300 dark:border-slate-700 text-rose-600 focus:ring-brand-500/40">
              <span class="font-bold uppercase tracking-wide">Agrupar por Categoría</span>
            </label>
          </div>

          <button
            type="button"
            class="px-6 py-3 rounded-xl text-xs font-black uppercase tracking-wide bg-gradient-to-r from-rose-600 to-brand-600 text-white shadow-xl shadow-rose-900/30 hover:from-rose-500 hover:to-brand-500 disabled:opacity-50 transition-all ml-auto"
            :disabled="loading"
            @click="aplicar"
          >
            {{ loading ? 'Actualizando...' : 'Actualizar' }}
          </button>
        </div>
      </div>

      <!-- Category Navigation -->
      <div v-if="groupByCategoria && Object.keys(productosAgrupados || {}).length > 1" class="flex flex-wrap gap-2 mb-6">
        <a 
          v-for="(_, catName) in productosAgrupados" 
          :key="'nav-'+catName"
          :href="'#' + catName.replace(/\s+/g, '-')"
          class="px-3 py-1.5 rounded-full bg-white dark:bg-white/5 hover:bg-slate-50 dark:hover:bg-slate-500/20 ring-1 ring-slate-100 dark:ring-white/10 text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400 hover:text-rose-600 dark:hover:text-rose-300 transition-all shadow-sm"
        >
          {{ catName }}
        </a>
      </div>

      <!-- Main Content Table/Groups -->
      <div v-if="groupByCategoria" class="space-y-12">
        <div v-for="(prods, catName) in productosAgrupados" :key="catName" :id="catName.replace(/\s+/g, '-')" class="group/section scroll-mt-24">
          <div class="flex items-center gap-4 mb-4">
            <div class="h-px w-8 bg-brand-500/50"></div>
            <h2 class="text-xs font-black uppercase tracking-[0.4em] text-rose-600 dark:text-rose-400 group-hover/section:text-rose-500 dark:group-hover/section:text-rose-300 transition-colors flex items-center gap-2">
              {{ catName }}
              <span class="px-2 py-0.5 rounded-xl bg-rose-100 dark:bg-brand-500/10 text-[9px] font-bold text-rose-800 dark:text-rose-200 dark:text-rose-200 dark:text-rose-300 ring-1 ring-rose-200 dark:ring-rose-500/20">{{ prods.length }} ítems</span>
              <span class="px-2 py-0.5 rounded-xl bg-emerald-100 dark:bg-brand-500/10 text-[9px] font-bold text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-slate-400 ring-1 ring-emerald-200 dark:ring-emerald-500/20">{{ fmtMoneda(getCategoryTotal(prods)) }}</span>
            </h2>
            <div class="h-px flex-1 bg-gradient-to-r from-rose-500/50 to-transparent"></div>
          </div>

          <div class="rounded-3xl bg-white dark:bg-slate-800/50 ring-1 ring-slate-100 dark:ring-white/10 overflow-hidden shadow-sm dark:shadow-2xl dark:backdrop-blur-sm">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
              <thead>
                <tr class="text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 bg-[var(--ui-surface)] dark:bg-slate-950/80 border-b border-slate-100 dark:border-white/5">
                  <th class="px-6 py-4 w-32">Código</th>
                  <th class="px-6 py-4">Producto</th>
                  <th class="px-6 py-4 text-center">Rotación (Ventas)</th>
                  <th class="px-6 py-4 text-center">Stock Actual</th>
                  <th class="px-6 py-4 text-center">Stock Mínimo</th>
                  <th class="px-6 py-4 text-right">Sugerencia Surtido</th>
                  <th class="px-6 py-4 text-right">Costo Est.</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                <tr v-for="p in prods" :key="p.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.03] transition-colors group">
                  <td class="px-6 py-4 font-mono font-bold text-rose-600 dark:text-rose-300/80">{{ p.codigo || 'S/C' }}</td>
                  <td class="px-6 py-4">
                    <p class="font-bold text-slate-700 dark:text-slate-100 group-hover:text-slate-900 dark:group-hover:text-white transition-colors">{{ p.nombre }}</p>
                  </td>
                  <td class="px-6 py-4 text-center">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase"
                          :class="p.total_vendido > 10 ? 'bg-brand-100 dark:bg-brand-500/10 text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-brand-400 ring-1 ring-brand-200 dark:ring-brand-500/30' : 'bg-slate-100 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400'">
                      🔥 {{ p.total_vendido }} un.
                    </span>
                  </td>
                  <td class="px-6 py-4 text-center">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-rose-50 dark:bg-rose-900/20 dark:bg-brand-500/10 text-rose-600 dark:text-rose-300 font-black ring-1 ring-rose-200 dark:ring-rose-500/30">
                      {{ p.stock }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-center font-bold text-slate-400 dark:text-slate-500">{{ p.stock_minimo || 5 }}</td>
                  <td class="px-6 py-4 text-right">
                    <div class="flex flex-col items-end">
                      <span class="text-sm font-black text-cyan-600 dark:text-cyan-400">+{{ Math.max(((p.stock_minimo || 5) - p.stock), 1) }}</span>
                      <span class="text-[9px] uppercase font-bold text-slate-400 dark:text-slate-500">piezas</span>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-right font-bold text-slate-700 dark:text-slate-200 tabular-nums">
                    {{ fmtMoneda(p.precio_compra) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Flat Table (Optional) -->
      <div v-else class="rounded-3xl bg-white dark:bg-slate-800/50 ring-1 ring-slate-100 dark:ring-white/10 overflow-hidden shadow-sm dark:shadow-2xl dark:backdrop-blur-sm">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
          <thead>
            <tr class="text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 bg-[var(--ui-surface)] dark:bg-slate-950/80 border-b border-slate-100 dark:border-white/5">
              <th class="px-6 py-4">Producto / Categoría</th>
              <th class="px-6 py-4 text-center">Ventas</th>
              <th class="px-6 py-4 text-center">Stock</th>
              <th class="px-6 py-4 text-right">Sugerencia</th>
              <th class="px-6 py-4 text-right">Costo</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
            <tr v-for="p in productosFiltrados" :key="p.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.03] transition-colors group">
              <td class="px-6 py-4">
                <div class="flex flex-col">
                  <span class="font-mono text-[10px] text-rose-500 dark:text-rose-300/60">{{ p.codigo || 'S/C' }}</span>
                  <span class="font-bold text-slate-700 dark:text-slate-100">{{ p.nombre }}</span>
                  <span class="text-[10px] uppercase font-black text-slate-400 dark:text-slate-500 tracking-wide">{{ p.categoria_nombre || 'Sin categoría' }}</span>
                </div>
              </td>
              <td class="px-6 py-4 text-center">
                <span class="text-xs font-black text-brand-600 dark:text-amber-400">{{ p.total_vendido }}</span>
              </td>
              <td class="px-6 py-4 text-center">
                <span class="px-2 py-1 rounded-xl bg-rose-50 dark:bg-rose-900/20 dark:bg-brand-500/10 text-rose-600 dark:text-rose-400 font-black ring-1 ring-rose-200 dark:ring-rose-500/20">{{ p.stock }} / {{ p.stock_minimo || 5 }}</span>
              </td>
              <td class="px-6 py-4 text-right font-black text-cyan-600 dark:text-cyan-400">
                +{{ Math.max(((p.stock_minimo || 5) - p.stock), 1) }}
              </td>
              <td class="px-6 py-4 text-right font-bold text-slate-700 dark:text-slate-200">{{ fmtMoneda(p.precio_compra) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- No Results -->
      <div v-if="productosFiltrados.length === 0" class="flex flex-col items-center justify-center py-20 bg-slate-100 dark:bg-slate-800/20 rounded-3xl border border-dashed border-slate-200 dark:border-white/10 shadow-inner">
        <div class="w-16 h-16 rounded-full bg-slate-200 dark:bg-slate-800 flex items-center justify-center mb-4 text-slate-400 dark:text-slate-500">
          <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
        </div>
        <p class="text-lg font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Inventario al día</p>
        <p class="text-sm text-slate-500 dark:text-slate-500">No hay productos que requieran reabastecimiento crítico.</p>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
/* Custom transitions and glassmorphism micro-effects */
.report-compras input, .report-compras select {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
