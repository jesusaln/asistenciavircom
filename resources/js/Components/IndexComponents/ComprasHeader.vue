<template>
  <div class="bg-white dark:bg-slate-950 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-800 overflow-hidden transition-all duration-300 index-header-root">
    <!-- Header principal -->
    <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800" :style="{ background: `linear-gradient(135deg, ${colors.principal}08 0%, ${colors.secundario}05 100%)` }">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center space-x-5">
          <div class="w-14 h-14 rounded-2xl flex items-center justify-center shadow-xl transform transition-transform hover:scale-105" :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }">
            <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
          <div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-wider">Compras</h1>
            <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">Gestión de Abastecimiento</p>
          </div>
        </div>

        <div class="flex items-center space-x-3">
          <button
            @click="onImportarXml"
            class="group inline-flex items-center px-6 py-3.5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-[10px] font-black uppercase tracking-[0.2em] rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 active:translate-y-0"
          >
            <svg class="w-4 h-4 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Importar XML
          </button>
          <button
            @click="onCrearNueva"
            class="group inline-flex items-center px-6 py-3.5 text-white text-[10px] font-black uppercase tracking-[0.2em] rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 active:translate-y-0"
            :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }"
          >
            <svg class="w-4 h-4 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nueva Compra
          </button>
        </div>
      </div>
    </div>

    <!-- Estadísticas -->
    <div class="px-8 py-6 bg-transparent/30 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-800">
      <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <!-- Card 1: Total -->
        <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-md p-5 rounded-2xl border border-slate-100 dark:border-slate-700/50 shadow-sm transition-all hover:bg-white dark:hover:bg-slate-800/50">
          <div class="flex flex-col">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Total Compras</span>
                <div class="w-7 h-7 rounded-xl flex items-center justify-center bg-slate-100 dark:bg-slate-700">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-slate-900 dark:text-white">{{ total }}</p>
          </div>
        </div>

        <!-- Card 2: Procesadas -->
        <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-md p-5 rounded-2xl border border-slate-100 dark:border-slate-700/50 shadow-sm transition-all hover:bg-white dark:hover:bg-slate-800/50">
          <div class="flex flex-col">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[9px] font-black text-emerald-600 dark:text-emerald-500 uppercase tracking-wide">Procesadas</span>
                <div class="w-7 h-7 rounded-xl flex items-center justify-center bg-emerald-100/50 dark:bg-emerald-900/30">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-emerald-600 dark:text-emerald-500">{{ procesadas }}</p>
          </div>
        </div>

        <!-- Card 3: Canceladas -->
        <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-md p-5 rounded-2xl border border-slate-100 dark:border-slate-700/50 shadow-sm transition-all hover:bg-white dark:hover:bg-slate-800/50">
          <div class="flex flex-col">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[9px] font-black text-rose-600 dark:text-rose-500 uppercase tracking-wide">Canceladas</span>
                <div class="w-7 h-7 rounded-xl flex items-center justify-center bg-rose-100/50 dark:bg-rose-900/30">
                    <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-rose-600 dark:text-rose-500">{{ canceladas }}</p>
          </div>
        </div>

        <!-- Card 4: Monto Total -->
        <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-md p-5 rounded-2xl border border-slate-100 dark:border-slate-700/50 shadow-sm transition-all hover:bg-white dark:hover:bg-slate-800/50">
          <div class="flex flex-col">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Inversión Total</span>
                <div class="w-7 h-7 rounded-xl flex items-center justify-center bg-slate-100 dark:bg-slate-700">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" /></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-slate-900 dark:text-white">${{ formatearMoneda(montoTotal) }}</p>
          </div>
        </div>

        <!-- Card 5: Pendientes Pago -->
        <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-md p-5 rounded-2xl border border-slate-100 dark:border-slate-700/50 shadow-sm transition-all hover:bg-white dark:hover:bg-slate-800/50">
          <div class="flex flex-col">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[9px] font-black text-brand-600 dark:text-brand-500 uppercase tracking-wide">Pendiente Pago</span>
                <div class="w-7 h-7 rounded-xl flex items-center justify-center bg-brand-100/50 dark:bg-brand-900/30">
                    <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-brand-600 dark:text-brand-500">${{ formatearMoneda(pendientesPago) }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filtros -->
    <div class="px-8 py-5 bg-white dark:bg-slate-900">
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <!-- Búsqueda -->
        <div class="flex-1 max-w-xl">
          <div class="relative group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-emerald-500">
              <svg class="h-5 w-5 text-slate-400 group-hover:text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input
              v-model="searchTerm"
              type="text"
              aria-label="Buscar compras por proveedor o folio"
              placeholder="Buscar compras por proveedor, folio..."
              class="block w-full pl-12 pr-4 py-3.5 bg-transparent dark:bg-slate-950 border-2 border-transparent dark:border-slate-800 rounded-2xl font-bold text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 focus:bg-white dark:focus:bg-slate-900 focus:border-slate-900 dark:focus:border-slate-700 focus:ring-0 transition-all duration-300 text-sm"
              @input="onSearchChange"
            />
          </div>
        </div>

        <!-- Filtros adicionales -->
        <div class="flex items-center gap-3">
          <!-- Filtro Estado -->
          <div class="relative min-w-[160px]">
            <select
              v-model="filtroEstado"
              class="appearance-none block w-full pl-4 pr-10 py-3.5 bg-transparent dark:bg-slate-950 border-2 border-transparent dark:border-slate-800 rounded-2xl font-black uppercase text-[10px] tracking-wide text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:border-slate-900 dark:focus:border-slate-700 focus:ring-0 transition-all duration-300"
              @change="onFiltroEstadoChange"
            >
              <option value="">Estados</option>
              <option value="procesada">Procesadas</option>
              <option value="cancelada">Canceladas</option>
            </select>
            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
              <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </div>
          </div>

          <!-- Filtro Origen -->
          <div class="relative min-w-[160px]">
            <select
              v-model="filtroOrigen"
              class="appearance-none block w-full pl-4 pr-10 py-3.5 bg-transparent dark:bg-slate-950 border-2 border-transparent dark:border-slate-800 rounded-2xl font-black uppercase text-[10px] tracking-wide text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:border-slate-900 dark:focus:border-slate-700 focus:ring-0 transition-all duration-300"
              @change="onFiltroOrigenChange"
            >
              <option value="">Orígenes</option>
              <option value="directa">Directas</option>
              <option value="orden_compra">O. Compra</option>
            </select>
            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
              <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </div>
          </div>

          <!-- Limpiar filtros -->
          <button
            @click="onLimpiarFiltros"
            class="inline-flex items-center px-5 py-3.5 bg-transparent dark:bg-slate-950 border-2 border-transparent dark:border-slate-800 rounded-2xl font-black uppercase text-[10px] tracking-wide text-slate-500 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white hover:border-slate-900 dark:hover:border-slate-700 transition-all duration-300"
          >
            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            Limpiar
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
@media (max-width: 640px) {
  .grid-cols-2.md\\:grid-cols-5 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

button:focus-visible { outline: 2px solid; outline-offset: 2px; }

@media (hover: none) {
  .hover\\:bg-transparent:hover { background-color: transparent; }
}
</style>
