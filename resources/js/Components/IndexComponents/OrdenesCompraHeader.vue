<template>
  <div class="bg-white dark:bg-slate-950 rounded-[2.5rem] shadow-xl border border-slate-100 dark:border-slate-800 overflow-hidden transition-all duration-300 index-header-root">
    <!-- Header principal -->
    <div class="px-10 py-8 border-b border-slate-100 dark:border-slate-800" :style="{ background: `linear-gradient(135deg, ${colors.principal}08 0%, ${colors.secundario}05 100%)` }">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-8">
        <div class="flex items-center space-x-6">
          <div class="w-16 h-16 rounded-[2rem] flex items-center justify-center shadow-2xl transform transition-transform hover:scale-110" :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }">
            <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>
          </div>
          <div>
            <h1 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-wider">Órdenes de Compra</h1>
            <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.3em]">Gestión de Adquisiciones</p>
          </div>
        </div>

        <div class="flex items-center space-x-4">
          <button
            @click="onCrearNueva"
            class="group inline-flex items-center px-8 py-4 text-white text-[10px] font-black uppercase tracking-[0.2em] rounded-[1.5rem] shadow-2xl hover:shadow-emerald-500/20 transition-all duration-500 transform hover:-translate-y-1 active:translate-y-0"
            :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }"
          >
            <svg class="w-4 h-4 mr-3 group-hover:rotate-90 transition-transform duration-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nueva Orden
          </button>
        </div>
      </div>
    </div>

    <!-- Estadísticas Premium -->
    <div class="px-10 py-8 bg-transparent/30 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-800">
      <div class="grid grid-cols-2 md:grid-cols-5 gap-6">
        <!-- Card 1: Total -->
        <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-md p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm transition-all hover:shadow-xl hover:bg-white dark:hover:bg-slate-800/50 group">
          <div class="flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Total</span>
                <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-slate-100 dark:bg-slate-700 group-hover:rotate-12 transition-transform">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
            </div>
            <p class="text-3xl font-black text-slate-900 dark:text-white tracking-tighter">{{ total }}</p>
          </div>
        </div>

        <!-- Card 2: Pendientes -->
        <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-md p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm transition-all hover:shadow-xl hover:bg-white dark:hover:bg-slate-800/50 group">
          <div class="flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <span class="text-[9px] font-black text-brand-600 dark:text-brand-500 uppercase tracking-[0.2em]">Pendientes</span>
                <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-brand-100/50 dark:bg-brand-900/30 group-hover:-rotate-12 transition-transform">
                    <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>
            <p class="text-3xl font-black text-brand-600 dark:text-brand-500 tracking-tighter">{{ pendientes }}</p>
          </div>
        </div>

        <!-- Card 3: Enviadas -->
        <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-md p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm transition-all hover:shadow-xl hover:bg-white dark:hover:bg-slate-800/50 group">
          <div class="flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <span class="text-[9px] font-black text-blue-600 dark:text-blue-500 uppercase tracking-[0.2em]">Enviadas</span>
                <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-sky-100/50 dark:bg-sky-900/30 group-hover:scale-110 transition-transform">
                    <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                </div>
            </div>
            <p class="text-3xl font-black text-blue-600 dark:text-blue-500 tracking-tighter">{{ enviadas_a_proveedor }}</p>
          </div>
        </div>

        <!-- Card 4: Procesadas -->
        <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-md p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm transition-all hover:shadow-xl hover:bg-white dark:hover:bg-slate-800/50 group">
          <div class="flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <span class="text-[9px] font-black text-emerald-600 dark:text-emerald-500 uppercase tracking-[0.2em]">Procesadas</span>
                <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-emerald-100/50 dark:bg-emerald-900/30 group-hover:rotate-12 transition-transform">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>
            <p class="text-3xl font-black text-emerald-600 dark:text-emerald-500 tracking-tighter">{{ procesadas }}</p>
          </div>
        </div>

        <!-- Card 5: Canceladas -->
        <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-md p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm transition-all hover:shadow-xl hover:bg-white dark:hover:bg-slate-800/50 group">
          <div class="flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <span class="text-[9px] font-black text-rose-600 dark:text-rose-500 uppercase tracking-[0.2em]">Canceladas</span>
                <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-rose-100/50 dark:bg-rose-900/30 group-hover:-rotate-12 transition-transform">
                    <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </div>
            </div>
            <p class="text-3xl font-black text-rose-600 dark:text-rose-500 tracking-tighter">{{ canceladas }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filtros de Alta Precisión -->
    <div class="px-10 py-6 bg-white dark:bg-slate-900">
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
        <!-- Búsqueda -->
        <div class="flex-1 max-w-2xl">
          <div class="relative group">
            <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none transition-colors group-focus-within:text-emerald-500">
              <svg class="h-5 w-5 text-slate-400 group-hover:text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input
              v-model="searchTerm"
              type="text"
              placeholder="Localizar orden por proveedor o número de control..."
              class="block w-full pl-14 pr-6 py-4 bg-transparent dark:bg-slate-950 border-2 border-transparent dark:border-slate-800 rounded-[1.5rem] font-bold text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 focus:bg-white dark:focus:bg-slate-900 focus:border-slate-900 dark:focus:border-slate-700 focus:ring-0 transition-all duration-500 text-sm"
              @input="onSearchChange"
            />
          </div>
        </div>

        <!-- Panel de Filtros -->
        <div class="flex items-center gap-4">
          <!-- Filtro Estado -->
          <div class="relative min-w-[200px]">
            <select
              v-model="filtroEstado"
              class="appearance-none block w-full pl-6 pr-12 py-4 bg-transparent dark:bg-slate-950 border-2 border-transparent dark:border-slate-800 rounded-[1.5rem] font-black uppercase text-[10px] tracking-wide text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:border-slate-900 dark:focus:border-slate-700 focus:ring-0 transition-all duration-500"
              @change="onFiltroEstadoChange"
            >
              <option value="">Todos los Estados</option>
              <option value="pendiente">Pendientes</option>
              <option value="enviado_a_proveedor">Enviadas</option>
              <option value="procesada">Procesadas</option>
              <option value="cancelada">Canceladas</option>
            </select>
            <div class="absolute inset-y-0 right-0 flex items-center pr-6 pointer-events-none">
              <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </div>
          </div>

          <!-- Limpiar -->
          <button
            @click="onLimpiarFiltros"
            class="inline-flex items-center px-6 py-4 bg-transparent dark:bg-slate-950 border-2 border-transparent dark:border-slate-800 rounded-[1.5rem] font-black uppercase text-[10px] tracking-wide text-slate-500 hover:text-slate-900 dark:hover:text-white hover:border-slate-900 dark:hover:border-slate-700 transition-all duration-500"
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

const { colors } = useCompanyColors()

const props = defineProps({
  total: { type: Number, default: 0 },
  pendientes: { type: Number, default: 0 },
  enviadas_a_proveedor: { type: Number, default: 0 },
  procesadas: { type: Number, default: 0 },
  canceladas: { type: Number, default: 0 },
})

const emit = defineEmits([
  'crear-nueva', 'search-change', 'filtro-estado-change', 'limpiar-filtros'
])

const searchTerm = defineModel('searchTerm', { type: String, default: '' })
const filtroEstado = defineModel('filtroEstado', { type: String, default: '' })

const onCrearNueva = () => emit('crear-nueva')
const onSearchChange = () => emit('search-change', searchTerm.value)
const onFiltroEstadoChange = () => emit('filtro-estado-change', filtroEstado.value)
const onLimpiarFiltros = () => emit('limpiar-filtros')

watch([searchTerm, filtroEstado], () => {}, { immediate: true })
</script>

<style scoped>
.index-header-root {
  animation: slideDown 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes slideDown {
  from { opacity: 0; transform: translateY(-20px); }
  to { opacity: 1; transform: translateY(0); }
}
select {
  cursor: pointer;
}
</style>
