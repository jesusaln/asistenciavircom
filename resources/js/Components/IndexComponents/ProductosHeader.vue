<template>
  <div class="space-y-8 animate-fade-in-up">
    
    <!-- Top Row: Title & Principal Actions -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div class="space-y-2">
            <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter uppercase">Productos</h1>
            <div class="flex items-center gap-4">
                <span class="text-[10px] font-black uppercase tracking-[0.3em] text-blue-600 dark:text-blue-400">Inventario Central</span>
                <div class="h-1 w-1 rounded-full bg-slate-200 dark:bg-slate-800"></div>
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]"></div>
                    <span class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">Gestión de Activos</span>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <Link
                v-if="$page.props.empresa_config?.cva_active"
                :href="route('cva.import')"
                class="flex items-center justify-center gap-3 px-8 py-4 bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-300 rounded-3xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-200 dark:hover:bg-slate-800 transition-all active:scale-95 border border-slate-200/50 dark:border-slate-800/50"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                Importar CVA
            </Link>

            <button
                @click="$emit('crear-nueva')"
                class="group relative flex items-center justify-center gap-3 px-10 py-5 bg-blue-600 text-white rounded-3xl text-[11px] font-black uppercase tracking-widest shadow-2xl shadow-blue-600/20 hover:shadow-blue-600/40 hover:-translate-y-1 transition-all duration-500 active:scale-95 bg-gradient-to-r from-blue-600 to-indigo-600 overflow-hidden"
            >
                <div class="absolute inset-0 bg-white/10 translate-y-full group-hover:translate-y-0 transition-transform duration-500"></div>
                <svg class="w-5 h-5 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" /></svg>
                <span class="relative z-10">Nuevo Producto</span>
            </button>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
      <!-- Total -->
      <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] p-6 border border-slate-200/50 dark:border-slate-800/50 hover:border-blue-500/30 transition-all duration-500 hover:shadow-2xl hover:shadow-blue-500/10 overflow-hidden">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-500/5 rounded-full blur-2xl group-hover:bg-blue-500/10 transition-colors"></div>
        <div class="relative flex flex-col gap-4">
          <div class="w-10 h-10 bg-blue-500/10 rounded-2xl flex items-center justify-center text-blue-600 dark:text-blue-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
          </div>
          <div>
            <span class="text-[9px] font-black uppercase tracking-[0.25em] text-slate-400 group-hover:text-blue-500 transition-colors">Totales</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter">{{ total }}</div>
          </div>
        </div>
      </div>

      <!-- Activos -->
      <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] p-6 border border-slate-200/50 dark:border-slate-800/50 hover:border-emerald-500/30 transition-all duration-500 hover:shadow-2xl hover:shadow-emerald-500/10 overflow-hidden">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-500/5 rounded-full blur-2xl group-hover:bg-emerald-500/10 transition-colors"></div>
        <div class="relative flex flex-col gap-4">
          <div class="w-10 h-10 bg-emerald-500/10 rounded-2xl flex items-center justify-center text-emerald-600 dark:text-emerald-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          </div>
          <div>
            <span class="text-[9px] font-black uppercase tracking-[0.25em] text-slate-400 group-hover:text-emerald-500 transition-colors">Activos</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter">{{ activos }}</div>
          </div>
        </div>
      </div>

      <!-- Inactivos / Agotados -->
      <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] p-6 border border-slate-200/50 dark:border-slate-800/50 hover:border-rose-500/30 transition-all duration-500 hover:shadow-2xl hover:shadow-rose-500/10 overflow-hidden">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-rose-500/5 rounded-full blur-2xl group-hover:bg-rose-500/10 transition-colors"></div>
        <div class="relative flex flex-col gap-4">
          <div class="w-10 h-10 bg-rose-500/10 rounded-2xl flex items-center justify-center text-rose-600 dark:text-rose-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          </div>
          <div>
            <span class="text-[9px] font-black uppercase tracking-[0.25em] text-slate-400 group-hover:text-rose-500 transition-colors">Inactivos</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter">{{ inactivos }}</div>
          </div>
        </div>
      </div>

      <!-- Valor Costo -->
      <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] p-6 border border-slate-200/50 dark:border-slate-800/50 hover:border-amber-500/30 transition-all duration-500 hover:shadow-2xl hover:shadow-amber-500/10 overflow-hidden">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-amber-500/5 rounded-full blur-2xl group-hover:bg-amber-500/10 transition-colors"></div>
        <div class="relative flex flex-col gap-4">
          <div class="w-10 h-10 bg-amber-500/10 rounded-2xl flex items-center justify-center text-amber-600 dark:text-amber-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" /></svg>
          </div>
          <div>
            <span class="text-[9px] font-black uppercase tracking-[0.25em] text-slate-400 group-hover:text-amber-500 transition-colors">Valor Adq.</span>
            <div class="text-xl font-black text-slate-900 dark:text-white tracking-tighter">${{ formatearMoneda(valorTotalCosto) }}</div>
          </div>
        </div>
      </div>

      <!-- Valor Venta -->
       <div class="lg:col-span-2 group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] p-6 border border-slate-200/50 dark:border-slate-800/50 hover:border-blue-600/30 transition-all duration-500 hover:shadow-2xl hover:shadow-blue-600/10 overflow-hidden">
        <div class="absolute -right-4 -top-4 w-40 h-40 bg-blue-600/5 rounded-full blur-3xl group-hover:bg-blue-600/10 transition-colors"></div>
        <div class="relative flex items-center justify-between">
          <div class="flex items-center gap-5">
              <div class="w-14 h-14 bg-blue-600/10 rounded-3xl flex items-center justify-center text-blue-600 dark:text-blue-400">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
              </div>
              <div>
                <span class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 group-hover:text-blue-600 transition-colors">Proyección Comercial</span>
                <div class="text-3xl font-black text-slate-900 dark:text-white tracking-tighter">${{ formatearMoneda(valorTotalVenta) }}</div>
              </div>
          </div>
          <div class="hidden sm:block text-right">
              <div class="text-[9px] font-black text-emerald-500 uppercase tracking-widest">+{{ agotado }} Agotados</div>
              <div class="w-20 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full mt-2 overflow-hidden">
                  <div class="h-full bg-emerald-500" :style="{ width: `${(activos/total)*100}%` }"></div>
              </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Row: Search & Filters -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-2">
        <div class="flex flex-col sm:flex-row items-center gap-4 w-full">
            <!-- Search bar -->
            <div class="relative flex-1 min-w-full sm:min-w-[400px]">
                <div class="absolute inset-y-0 left-6 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </div>
                <input
                    v-model="searchTerm"
                    type="text"
                    placeholder="BUSCAR NOMBRE, CÓDIGO O ESPECIFICACIÓN..."
                    class="w-full pl-14 pr-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-blue-600/20 transition-all tracking-wider"
                    @input="onSearchChange"
                    @keydown.enter="onSearchKeydown"
                />
            </div>

            <!-- Filters Group -->
            <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                <div class="relative flex-1 sm:flex-none">
                    <select
                        v-model="filtroEstado"
                        class="w-full sm:w-auto appearance-none pl-6 pr-10 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-[10px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-300 focus:ring-2 focus:ring-blue-600/20 cursor-pointer transition-all"
                        @change="onFiltroEstadoChange"
                    >
                        <option value="">TODOS LOS ESTADOS</option>
                        <option value="activo">SOLO ACTIVOS</option>
                        <option value="inactivo">INACTIVOS</option>
                        <option value="agotado">SIN STOCK</option>
                    </select>
                    <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                </div>

                <div class="relative flex-1 sm:flex-none">
                    <select
                        v-model="sortBy"
                        class="w-full sm:w-auto appearance-none pl-6 pr-10 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-[10px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-300 focus:ring-2 focus:ring-blue-600/20 cursor-pointer transition-all"
                        @change="onSortChange"
                    >
                        <option value="nombre-asc">NOMBRE (A-Z)</option>
                        <option value="nombre-desc">NOMBRE (Z-A)</option>
                        <option value="precio_venta-desc">PRECIO MAYOR</option>
                        <option value="precio_venta-asc">PRECIO MENOR</option>
                        <option value="stock-desc">STOCK MAYOR</option>
                        <option value="stock-asc">STOCK MENOR</option>
                    </select>
                    <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                </div>

                <button
                    @click="onLimpiarFiltros"
                    class="w-12 h-14 flex items-center justify-center bg-slate-100/50 dark:bg-slate-950/50 text-slate-400 hover:text-rose-500 rounded-2xl transition-all border border-transparent hover:border-rose-500/20"
                    title="Limpiar filtros"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
        </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  total: { type: Number, default: 0 },
  activos: { type: Number, default: 0 },
  inactivos: { type: Number, default: 0 },
  agotado: { type: Number, default: 0 },
  valorTotalCosto: { type: Number, default: 0 },
  valorTotalVenta: { type: Number, default: 0 },
})

const emit = defineEmits([
  'crear-nueva', 'search-change', 'filtro-estado-change', 'sort-change', 'limpiar-filtros'
])

const searchTerm = defineModel('searchTerm', { type: String, default: '' })
const sortBy = defineModel('sortBy', { type: String, default: 'nombre-asc' })
const filtroEstado = defineModel('filtroEstado', { type: String, default: '' })

let searchTimeout = null

const formatearMoneda = (num) => {
  return new Intl.NumberFormat('es-MX', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(num || 0);
}

const onSearchChange = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    emit('search-change', searchTerm.value)
  }, 400)
}

const onSearchKeydown = (event) => {
  if (event.key === 'Enter') {
    if (searchTimeout) clearTimeout(searchTimeout)
    emit('search-change', searchTerm.value)
  }
}

const onFiltroEstadoChange = () => emit('filtro-estado-change', filtroEstado.value)
const onSortChange = () => emit('sort-change', sortBy.value)
const onLimpiarFiltros = () => emit('limpiar-filtros')
</script>

<style scoped>
.animate-fade-in-up { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
</style>
