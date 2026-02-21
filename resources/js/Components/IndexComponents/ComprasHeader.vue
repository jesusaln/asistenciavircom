<template>
  <div class="compras-header-container space-y-8">
    
    <!-- Stats Banner -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
      
      <!-- Total Card -->
      <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2rem] p-6 border border-slate-200/50 dark:border-slate-800/50 hover:border-blue-500/30 transition-all duration-500 hover:shadow-2xl hover:shadow-blue-500/10 overflow-hidden">
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-blue-600/5 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
        <div class="relative z-10 flex flex-col justify-between h-full">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform duration-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 118 0m-4 8v2a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2h2m2 4h10a2 2 0 002-2v-1M8 7V6a1 1 0 011-1h6a1 1 0 011 1v1M8 7H5a2 2 0 00-2 2v2a2 2 0 002 2h3m0 0h10a2 2 0 002-2v-2a2 2 0 00-2-2h-3M12 17h.01" /></svg>
                </div>
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">Total</span>
            </div>
            <div class="flex flex-col">
                <span class="text-3xl font-black text-slate-900 dark:text-white tracking-tighter">{{ total }}</span>
                <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1">Registradas</span>
            </div>
        </div>
      </div>

      <!-- Procesadas Card -->
      <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2rem] p-6 border border-slate-200/50 dark:border-slate-800/50 hover:border-emerald-500/30 transition-all duration-500 hover:shadow-2xl hover:shadow-emerald-500/10 overflow-hidden">
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-emerald-600/5 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
        <div class="relative z-10 flex flex-col justify-between h-full">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform duration-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">Procesadas</span>
            </div>
            <div class="flex flex-col">
                <span class="text-3xl font-black text-slate-900 dark:text-white tracking-tighter text-emerald-600 dark:text-emerald-400">{{ procesadas }}</span>
                <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1">Listas</span>
            </div>
        </div>
      </div>

      <!-- Canceladas Card -->
      <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2rem] p-6 border border-slate-200/50 dark:border-slate-800/50 hover:border-rose-500/30 transition-all duration-500 hover:shadow-2xl hover:shadow-rose-500/10 overflow-hidden">
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-rose-600/5 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
        <div class="relative z-10 flex flex-col justify-between h-full">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center text-rose-600 dark:text-rose-400 group-hover:scale-110 transition-transform duration-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">Canceladas</span>
            </div>
            <div class="flex flex-col">
                <span class="text-3xl font-black text-slate-900 dark:text-white tracking-tighter text-rose-600 dark:text-rose-400">{{ canceladas }}</span>
                <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1">Anuladas</span>
            </div>
        </div>
      </div>

      <!-- Monto Card -->
      <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2rem] p-6 border border-slate-200/50 dark:border-slate-800/50 hover:border-indigo-500/30 transition-all duration-500 hover:shadow-2xl hover:shadow-indigo-500/10 overflow-hidden lg:col-span-1">
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-indigo-600/5 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
        <div class="relative z-10 flex flex-col justify-between h-full">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform duration-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" /></svg>
                </div>
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">Inversión</span>
            </div>
            <div class="flex flex-col">
                <div class="flex items-baseline gap-1">
                    <span class="text-xs font-black text-indigo-400">$</span>
                    <span class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter truncate">{{ formatearMoneda(montoTotal) }}</span>
                </div>
                <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1">Total Compras</span>
            </div>
        </div>
      </div>

      <!-- Pendientes Card -->
      <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2rem] p-6 border border-slate-200/50 dark:border-slate-800/50 hover:border-amber-500/30 transition-all duration-500 hover:shadow-2xl hover:shadow-amber-500/10 overflow-hidden lg:col-span-1">
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-amber-600/5 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
        <div class="relative z-10 flex flex-col justify-between h-full">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400 group-hover:scale-110 transition-transform duration-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">CX Pagar</span>
            </div>
            <div class="flex flex-col">
                <div class="flex items-baseline gap-1">
                    <span class="text-xs font-black text-amber-400">$</span>
                    <span class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter truncate text-amber-600 dark:text-amber-400">{{ formatearMoneda(pendientesPago) }}</span>
                </div>
                <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1">Por liquidar</span>
            </div>
        </div>
      </div>
    </div>

    <!-- Actions & Filter Bar -->
    <div class="bg-white/60 dark:bg-slate-900/60 backdrop-blur-2xl rounded-[2.5rem] border border-slate-200/50 dark:border-slate-800/50 p-6 lg:p-8 shadow-xl shadow-slate-200/20 dark:shadow-none">
      <div class="flex flex-col lg:flex-row gap-6 items-center">
        
        <!-- Main Search -->
        <div class="flex-1 w-full relative">
          <div class="absolute inset-y-0 left-6 flex items-center pointer-events-none">
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          </div>
          <input
            v-model="searchTerm"
            type="text"
            placeholder="Proveedor, Folio, Producto..."
            class="w-full pl-14 pr-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-blue-600/20 transition-all"
            @input="onSearchChange"
          />
        </div>

        <div class="flex flex-wrap lg:flex-nowrap items-center gap-3 w-full lg:w-auto">
          <!-- Advanced Selects -->
          <div class="relative group">
            <select
              v-model="filtroEstado"
              @change="onFiltroEstadoChange"
              class="appearance-none pl-6 pr-10 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-xs font-black uppercase tracking-widest text-slate-600 dark:text-slate-300 focus:ring-2 focus:ring-blue-600/20 cursor-pointer min-w-[140px]"
            >
              <option value="">Estados</option>
              <option value="procesada">Procesadas</option>
              <option value="cancelada">Canceladas</option>
            </select>
          </div>

          <div class="relative group">
            <select
              v-model="filtroOrigen"
              @change="onFiltroOrigenChange"
              class="appearance-none pl-6 pr-10 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-xs font-black uppercase tracking-widest text-slate-600 dark:text-slate-300 focus:ring-2 focus:ring-blue-600/20 cursor-pointer min-w-[140px]"
            >
              <option value="">Orígenes</option>
              <option value="directa">Directas</option>
              <option value="orden_compra">Orden Compra</option>
            </select>
          </div>

          <div class="relative group">
            <select
              v-model="sortBy"
              @change="onSortChange"
              class="appearance-none pl-6 pr-10 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-xs font-black uppercase tracking-widest text-slate-600 dark:text-slate-300 focus:ring-2 focus:ring-blue-600/20 cursor-pointer min-w-[160px]"
            >
              <option value="created_at-desc">Recientes</option>
              <option value="created_at-asc">Antiguos</option>
              <option value="total-desc">Mayor Monto</option>
              <option value="total-asc">Menor Monto</option>
            </select>
          </div>

          <!-- Buttons -->
          <button
            @click="onLimpiarFiltros"
            class="w-12 h-14 flex items-center justify-center bg-slate-100/50 dark:bg-slate-950/50 text-slate-400 hover:text-rose-500 rounded-2xl transition-all"
            title="Limpiar filtros"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>

          <div class="w-px h-8 bg-slate-200 dark:bg-slate-800 mx-2 hidden lg:block"></div>

          <button
            @click="onImportarXml"
            class="flex items-center gap-2 px-6 py-4 bg-emerald-600/10 text-emerald-600 dark:text-emerald-400 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-600 hover:text-white transition-all duration-300 active:scale-95"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            <span class="hidden sm:inline">XML</span>
          </button>

          <button
            @click="onCrearNueva"
            class="flex items-center gap-3 px-8 py-4 bg-blue-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-blue-600/20 hover:shadow-blue-600/40 hover:-translate-y-1 transition-all duration-300 active:scale-95 bg-gradient-to-r from-blue-600 to-indigo-600"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
            Nueva
          </button>
        </div>
      </div>
    </div>
  </div>
</template>


<script setup>
import { computed, watch } from 'vue'
import { useCompanyColors } from '@/Composables/useCompanyColors'

// Colores de empresa
const { colors } = useCompanyColors()

const props = defineProps({
  total: { type: Number, default: 0 },
  procesadas: { type: Number, default: 0 },
  canceladas: { type: Number, default: 0 },
  montoTotal: { type: Number, default: 0 },
  pendientesPago: { type: Number, default: 0 },
})

const emit = defineEmits([
  'crear-nueva', 'importar-xml', 'search-change', 'filtro-estado-change', 'filtro-origen-change', 'sort-change', 'limpiar-filtros'
])

// Estados locales para filtros
const searchTerm = defineModel('searchTerm', { type: String, default: '' })
const sortBy = defineModel('sortBy', { type: String, default: 'created_at-desc' })
const filtroEstado = defineModel('filtroEstado', { type: String, default: '' })
const filtroOrigen = defineModel('filtroOrigen', { type: String, default: '' })

// Función para formatear moneda
const formatearMoneda = (num) => {
  const value = parseFloat(num);
  const safe = Number.isFinite(value) ? value : 0;
  return new Intl.NumberFormat('es-MX', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(safe);
}

// Métodos de emisión
const onCrearNueva = () => emit('crear-nueva')
const onImportarXml = () => emit('importar-xml')
const onSearchChange = () => emit('search-change', searchTerm.value)
const onFiltroEstadoChange = () => emit('filtro-estado-change', filtroEstado.value)
const onFiltroOrigenChange = () => emit('filtro-origen-change', filtroOrigen.value)
const onSortChange = () => emit('sort-change', sortBy.value)
const onLimpiarFiltros = () => emit('limpiar-filtros')

// Watch para limpiar filtros automáticamente
watch([searchTerm, sortBy, filtroEstado, filtroOrigen], () => {
  // Emitir cambios automáticamente
}, { immediate: true })
</script>

<style scoped>
/* Estilos específicos para el header de compras */
.compras-header {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
}

@media (max-width: 768px) {
  .compras-header .grid {
    grid-template-columns: 1fr;
  }

  .compras-header h1 {
    font-size: 1.5rem;
  }
}
</style>

