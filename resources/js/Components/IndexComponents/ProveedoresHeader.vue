<template>
  <div class="space-y-8 index-header-root">
    <!-- Main Header Card -->
    <div class="relative overflow-hidden bg-white dark:bg-slate-950 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 transition-all duration-500">
      <!-- Decorative Background Elements -->
      <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-slate-50 to-blue-50/20 dark:from-slate-900/20 dark:to-blue-900/10 rounded-full -mr-32 -mt-32 blur-3xl opacity-50"></div>
      
      <div class="relative p-8 md:p-10">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-8">
          <!-- Title & Subtitle -->
          <div class="flex items-center gap-6">
            <div class="w-20 h-20 rounded-[2rem] flex items-center justify-center shadow-2xl transform transition-transform hover:scale-110 duration-500 group" :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }">
              <svg class="w-10 h-10 text-white transition-transform group-hover:rotate-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
            </div>
            <div>
              <h1 class="text-4xl font-black text-slate-900 dark:text-white uppercase tracking-wide leading-none mb-2">Proveedores</h1>
              <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">{{ activos }} SOCIOS COMERCIALES ACTIVOS</p>
              </div>
            </div>
          </div>

          <!-- Quick Stats Desktop -->
          <div class="hidden xl:flex items-center gap-12">
            <div class="text-center">
              <p class="text-[10px] font-black text-slate-400 uppercase tracking-wide mb-1">Volumen Total</p>
              <p class="text-2xl font-black text-slate-900 dark:text-white">{{ total }}</p>
            </div>
            <div class="w-px h-10 bg-slate-100 dark:bg-slate-800"></div>
            <div class="text-center">
              <p class="text-[10px] font-black text-slate-400 uppercase tracking-wide mb-1">Soporte Digital</p>
              <p class="text-2xl font-black text-blue-600 dark:text-blue-400">{{ con_email }}</p>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-3">
            <button
              @click="onCrearNueva"
              class="group relative flex items-center gap-3 px-8 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl font-black uppercase text-[10px] tracking-[0.2em] shadow-2xl hover:scale-[1.02] active:scale-[0.98] transition-all duration-300"
            >
              <svg class="w-4 h-4 transition-transform group-hover:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
              </svg>
              Nuevo Proveedor
            </button>
          </div>
        </div>

        <!-- Dashboard Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-12">
          <!-- Activos Card -->
          <div class="group p-6 bg-transparent dark:bg-slate-900/50 rounded-[2rem] border border-slate-100 dark:border-slate-800 transition-all hover:bg-white dark:hover:bg-slate-900 hover:shadow-xl">
            <div class="flex items-center justify-between mb-4">
              <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              </div>
              <span class="text-[10px] font-black text-emerald-600 uppercase italic tracking-wide">Activos</span>
            </div>
            <p class="text-3xl font-black text-slate-900 dark:text-white mb-1">{{ activos }}</p>
            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wide">Disponibles para operación</p>
          </div>

          <!-- Inactivos Card -->
          <div class="group p-6 bg-transparent dark:bg-slate-900/50 rounded-[2rem] border border-slate-100 dark:border-slate-800 transition-all hover:bg-white dark:hover:bg-slate-900 hover:shadow-xl">
            <div class="flex items-center justify-between mb-4">
              <div class="w-10 h-10 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              </div>
              <span class="text-[10px] font-black text-rose-600 uppercase italic tracking-wide">Pausados</span>
            </div>
            <p class="text-3xl font-black text-slate-900 dark:text-white mb-1">{{ inactivos }}</p>
            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wide">Fuera de línea temporalmente</p>
          </div>

          <!-- Personas Físicas -->
          <div class="group p-6 bg-transparent dark:bg-slate-900/50 rounded-[2rem] border border-slate-100 dark:border-slate-800 transition-all hover:bg-white dark:hover:bg-slate-900 hover:shadow-xl">
            <div class="flex items-center justify-between mb-4">
              <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
              </div>
              <span class="text-[10px] font-black text-blue-600 uppercase italic tracking-wide">Físicas</span>
            </div>
            <p class="text-3xl font-black text-slate-900 dark:text-white mb-1">{{ personas_fisicas }}</p>
            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wide">Régimen Individual</p>
          </div>

          <!-- Personas Morales -->
          <div class="group p-6 bg-transparent dark:bg-slate-900/50 rounded-[2rem] border border-slate-100 dark:border-slate-800 transition-all hover:bg-white dark:hover:bg-slate-900 hover:shadow-xl">
            <div class="flex items-center justify-between mb-4">
              <div class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
              </div>
              <span class="text-[10px] font-black text-purple-600 uppercase italic tracking-wide">Morales</span>
            </div>
            <p class="text-3xl font-black text-slate-900 dark:text-white mb-1">{{ personas_morales }}</p>
            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wide">Empresas y Organizaciones</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Toolbar: Search & Filters -->
    <div class="flex flex-col lg:flex-row items-center gap-4 bg-white/50 dark:bg-slate-900/50 backdrop-blur-xl p-4 rounded-[2rem] border border-white dark:border-slate-800 shadow-xl">
      <!-- Search Input -->
      <div class="relative w-full lg:flex-1 group">
        <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
          <svg class="h-4 w-4 text-slate-400 group-focus-within:text-slate-900 dark:group-focus-within:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
        <input
          v-model="searchTerm"
          type="text"
          placeholder="BUSCAR PROVEEDOR POR NOMBRE O RFC..."
          class="block w-full pl-12 pr-4 py-4 bg-white dark:bg-slate-950 border-none rounded-2xl text-[10px] font-black uppercase tracking-wide placeholder-slate-400 focus:ring-2 focus:ring-slate-900 dark:focus:ring-white transition-all shadow-sm"
          @input="onSearchChange"
        />
      </div>

      <!-- Filters Group -->
      <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
        <!-- Tipo Persona -->
        <select
          v-model="filtroTipoPersona"
          @change="onFiltroTipoPersonaChange"
          class="flex-1 lg:w-48 py-4 px-4 bg-white dark:bg-slate-950 border-none rounded-2xl text-[10px] font-black uppercase tracking-wide focus:ring-2 focus:ring-slate-900 dark:focus:ring-white transition-all shadow-sm"
        >
          <option value="">Régimen Fiscal</option>
          <option value="fisica">Personas Físicas</option>
          <option value="moral">Personas Morales</option>
        </select>

        <!-- Estado Activo -->
        <select
          v-model="filtroEstado"
          @change="onFiltroEstadoChange"
          class="flex-1 lg:w-48 py-4 px-4 bg-white dark:bg-slate-950 border-none rounded-2xl text-[10px] font-black uppercase tracking-wide focus:ring-2 focus:ring-slate-900 dark:focus:ring-white transition-all shadow-sm"
        >
          <option value="">Estatus</option>
          <option value="1">Operativos (Activos)</option>
          <option value="0">Suspendidos (Inactivos)</option>
        </select>

        <!-- Limpiar -->
        <button
          @click="onLimpiarFiltros"
          class="p-4 bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded-2xl transition-all shadow-sm hover:rotate-90"
          title="Limpiar filtros"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useCompanyColors } from '@/Composables/useCompanyColors'

const { colors } = useCompanyColors()

const props = defineProps({
  total: { type: Number, default: 0 },
  activos: { type: Number, default: 0 },
  inactivos: { type: Number, default: 0 },
  personas_fisicas: { type: Number, default: 0 },
  personas_morales: { type: Number, default: 0 },
  con_email: { type: Number, default: 0 },
})

const emit = defineEmits([
  'crear-nueva', 'search-change', 'filtro-tipo-persona-change', 'filtro-estado-change', 'filtro-estado-mexico-change', 'sort-change', 'limpiar-filtros'
])

const searchTerm = defineModel('searchTerm', { type: String, default: '' })
const sortBy = defineModel('sortBy', { type: String, default: 'created_at-desc' })
const filtroTipoPersona = defineModel('filtroTipoPersona', { type: String, default: '' })
const filtroEstado = defineModel('filtroEstado', { type: String, default: '' })
const filtroEstadoMexico = defineModel('filtroEstadoMexico', { type: String, default: '' })

const onCrearNueva = () => emit('crear-nueva')
const onSearchChange = () => emit('search-change', searchTerm.value)
const onFiltroTipoPersonaChange = () => emit('filtro-tipo-persona-change', filtroTipoPersona.value)
const onFiltroEstadoChange = () => emit('filtro-estado-change', filtroEstado.value)
const onLimpiarFiltros = () => emit('limpiar-filtros')
</script>
