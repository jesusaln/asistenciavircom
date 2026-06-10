<template>
  <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700 overflow-hidden index-header-root transition-all duration-300">
    <!-- Header con gradiente de empresa -->
    <div 
      class="px-6 py-8 border-b border-slate-200/60 dark:border-slate-700/60 transition-colors"
      :style="{ background: isDark ? 'linear-gradient(135deg, #1f2937 0%, #111827 100%)' : `linear-gradient(135deg, ${colors.principal}15 0%, ${colors.secundario}10 100%)` }"
    >
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
        <div>
          <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tighter flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white shadow-lg" :style="{ backgroundColor: colors.principal }">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
              </svg>
            </div>
            Corte de Caja y Cobranza
          </h1>
          <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 font-medium">Formaliza las cobranzas de mostrador y genera lotes de entrega hacia tesorería.</p>
        </div>
        
        <button
          @click="onCrearNueva"
          class="inline-flex items-center px-6 py-3 text-white text-sm font-bold rounded-xl shadow-lg transition-all duration-300 transform hover:scale-105 active:scale-95 focus:outline-none focus:ring-4"
          :style="{ backgroundColor: colors.principal, '--tw-ring-color': `${colors.principal}40` }"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
          </svg>
          NUEVA ENTREGA
        </button>
      </div>

      <!-- Estadísticas Modernas -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div 
          v-for="stat in statCards" 
          :key="stat.label"
          class="bg-white/40 dark:bg-slate-900/50 backdrop-blur-md rounded-2xl p-5 border border-white/60 dark:border-slate-700/40 shadow-sm hover:shadow-md transition-all group"
        >
          <div class="flex items-center justify-between">
            <div class="space-y-1">
              <p class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide">{{ stat.label }}</p>
              <p class="text-2xl font-black transition-colors" :class="stat.textClass">${{ formatearMoneda(stat.value) }}</p>
            </div>
            <div 
              class="w-12 h-12 rounded-xl flex items-center justify-center transition-all duration-300 group-hover:rotate-12 group-hover:scale-110"
              :class="stat.bgClass"
            >
              <svg class="w-6 h-6" :class="stat.iconClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="stat.iconPath" />
              </svg>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Barra de Filtros Inteligente -->
    <div class="px-6 py-5 bg-transparent dark:bg-slate-900/50 backdrop-blur-sm border-b border-slate-200/60 dark:border-slate-700/60">
      <div class="flex flex-col xl:flex-row gap-4">
        <!-- Búsqueda -->
        <div class="flex-1 relative group">
          <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-slate-400 group-focus-within:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <input
            v-model="searchTerm"
            type="text"
            placeholder="Buscar por vendedor, folio o monto..."
            class="block w-full pl-11 pr-4 py-3 border-2 border-slate-200/60 dark:border-slate-700/60 rounded-xl leading-5 bg-white dark:bg-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-0 focus:border-brand-500 transition-all text-sm"
            @input="onSearchChange"
          />
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <!-- Filtro Estado -->
          <div class="relative min-w-[160px]">
            <select
              v-model="filtroEstado"
              @change="onFiltroEstadoChange"
              class="appearance-none block w-full pl-4 pr-10 py-3 text-sm font-bold border-2 border-slate-200/60 dark:border-slate-700/60 rounded-xl bg-white dark:bg-slate-800 dark:text-white focus:outline-none focus:border-brand-500 transition-all cursor-pointer"
            >
              <option value="">TODOS LOS ESTADOS</option>
              <option value="pendiente">PENDIENTES</option>
              <option value="recibido">RECIBIDOS</option>
              <option value="cancelado">CANCELADOS</option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-500">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
          </div>

          <!-- Filtro Usuario -->
          <div class="relative min-w-[160px]">
            <select
              v-model="filtroUsuario"
              @change="onFiltroUsuarioChange"
              class="appearance-none block w-full pl-4 pr-10 py-3 text-sm font-bold border-2 border-slate-200/60 dark:border-slate-700/60 rounded-xl bg-white dark:bg-slate-800 dark:text-white focus:outline-none focus:border-brand-500 transition-all cursor-pointer"
            >
              <option value="">TODOS LOS VENDEDORES</option>
              <option v-for="usuario in usuarios" :key="usuario.id" :value="usuario.id">
                {{ usuario.name.toUpperCase() }}
              </option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-500">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
          </div>

          <!-- Limpiar -->
          <button
            @click="onLimpiarFiltros"
            class="p-3 bg-white dark:bg-slate-800 border-2 border-slate-200/60 dark:border-slate-700/60 rounded-xl hover:bg-rose-50 dark:hover:bg-rose-900/20 hover:border-rose-200 dark:hover:border-rose-800 text-slate-400 hover:text-rose-600 transition-all"
            title="Limpiar Filtros"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useCompanyColors } from '@/Composables/useCompanyColors'

const { colors } = useCompanyColors()
const isDark = ref(false)
let observer = null

onMounted(() => {
  isDark.value = document.documentElement.classList.contains('dark')
  observer = new MutationObserver(() => isDark.value = document.documentElement.classList.contains('dark'))
  observer.observe(document.documentElement, { attributes: true })
})

onBeforeUnmount(() => observer?.disconnect())

const props = defineProps({
  total: Number,
  totalPendientes: Number,
  totalRecibidas: Number,
  totalEfectivo: Number,
  totalOtros: Number,
  usuarios: Array,
})

const emit = defineEmits([
  'crear-nueva', 'search-change', 'filtro-estado-change', 'filtro-usuario-change', 'sort-change', 'limpiar-filtros'
])

const searchTerm = defineModel('searchTerm')
const sortBy = defineModel('sortBy')
const filtroEstado = defineModel('filtroEstado')
const filtroUsuario = defineModel('filtroUsuario')

const statCards = computed(() => [
  { 
    label: 'Ingreso Total', 
    value: props.total, 
    textClass: 'text-slate-900 dark:text-white', 
    bgClass: 'bg-sky-50 dark:bg-sky-900/20/40', 
    iconClass: 'text-sky-600 dark:text-sky-400', 
    iconPath: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1' 
  },
  { 
    label: 'Pendientes', 
    value: props.totalPendientes, 
    textClass: 'text-brand-600 dark:text-amber-400', 
    bgClass: 'bg-brand-50 dark:bg-brand-900/20/40', 
    iconClass: 'text-brand-600 dark:text-amber-400', 
    iconPath: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' 
  },
  { 
    label: 'Efectivo', 
    value: props.totalEfectivo, 
    textClass: 'text-emerald-600 dark:text-emerald-400', 
    bgClass: 'bg-emerald-100 dark:bg-emerald-900/40', 
    iconClass: 'text-emerald-600 dark:text-emerald-400', 
    iconPath: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z' 
  },
  { 
    label: 'Recibidas', 
    value: props.totalRecibidas, 
    textClass: 'text-emerald-600 dark:text-emerald-400', 
    bgClass: 'bg-emerald-50 dark:bg-emerald-900/20/40', 
    iconClass: 'text-emerald-600 dark:text-emerald-400', 
    iconPath: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' 
  },
])

const formatearMoneda = (num) => new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2 }).format(num || 0)

const onCrearNueva = () => emit('crear-nueva')
const onSearchChange = () => emit('search-change', searchTerm.value)
const onFiltroEstadoChange = () => emit('filtro-estado-change', filtroEstado.value)
const onFiltroUsuarioChange = () => emit('filtro-usuario-change', filtroUsuario.value)
const onLimpiarFiltros = () => emit('limpiar-filtros')
</script>

<style scoped>
.index-header-root { font-family: 'Inter', sans-serif; }
</style>

