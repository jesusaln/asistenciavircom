<template>
  <div class="relative overflow-hidden mb-10 transition-all duration-500 animate-fade-in-up">
    <!-- Ambient Background Effects -->
    <div class="absolute inset-x-0 bottom-0 top-0 pointer-events-none overflow-hidden select-none z-0">
        <div class="absolute -top-[50%] -left-[10%] w-[40%] h-[150%] bg-blue-600/5 rounded-full blur-[120px] transition-opacity duration-1000"></div>
        <div class="absolute -bottom-[50%] -right-[10%] w-[40%] h-[150%] bg-emerald-600/5 rounded-full blur-[120px] transition-opacity duration-1000"></div>
    </div>

    <div class="relative z-10 bg-white/40 dark:bg-slate-900/40 backdrop-blur-3xl rounded-[2.5rem] border border-slate-200/50 dark:border-slate-800/50 shadow-2xl shadow-slate-200/20 dark:shadow-none p-8 md:p-10 space-y-10">
      
      <!-- Top Section -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-8">
        <div class="space-y-3">
          <div class="flex items-center gap-3">
              <div class="w-1.5 h-8 bg-blue-600 rounded-full"></div>
              <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter uppercase">Almacenes</h1>
          </div>
          <div class="flex items-center gap-4">
              <span class="text-[10px] font-black uppercase tracking-[0.3em] text-blue-600 dark:text-blue-400">Centro de Distribución</span>
              <div class="h-1 w-1 rounded-full bg-slate-300 dark:bg-slate-700"></div>
              <span class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">Gestión de Inventarios</span>
          </div>
        </div>
        
        <div class="flex items-center gap-4">
            <button
                @click="onCrearNueva"
                class="group relative px-8 py-4 bg-blue-600 text-white rounded-3xl text-[11px] font-black uppercase tracking-widest shadow-2xl shadow-blue-600/30 hover:shadow-blue-600/50 hover:-translate-y-1 transition-all duration-500 active:scale-95 flex items-center gap-3 overflow-hidden"
            >
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                <svg class="w-4 h-4 transition-transform duration-500 group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Registrar Almacén
            </button>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-2 lg:grid-cols-5 gap-6">
          <div v-for="(stat, idx) in statsItems" :key="idx" 
               class="group p-6 rounded-[2.2rem] bg-white/50 dark:bg-slate-950/50 border border-slate-100 dark:border-slate-800/50 hover:border-blue-500/30 transition-all duration-500 hover:shadow-xl hover:shadow-blue-500/5">
              <div class="flex flex-col gap-4">
                  <div class="w-10 h-10 rounded-2xl flex items-center justify-center transition-colors duration-500" :class="stat.bg">
                      <svg class="w-5 h-5" :class="stat.color" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="stat.icon"></svg>
                  </div>
                  <div class="space-y-1">
                      <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ stat.label }}</p>
                      <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter">{{ stat.value }}</span>
                        <span v-if="stat.suffix" class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">{{ stat.suffix }}</span>
                      </div>
                  </div>
              </div>
          </div>
      </div>

      <!-- Filters & Search -->
      <div class="pt-6 border-t border-slate-200/50 dark:border-slate-800/50">
        <div class="flex flex-col lg:flex-row lg:items-center gap-6">
          <!-- Search Bar -->
          <div class="flex-1 relative group">
            <div class="absolute inset-y-0 left-6 flex items-center pointer-events-none">
              <svg class="h-4 w-4 text-slate-400 group-focus-within:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input
              v-model="searchTerm"
              type="text"
              placeholder="RASTREAR ALMACÉN O UBICACIÓN..."
              class="premium-input pl-14"
              @input="onSearchInput"
            />
          </div>

          <!-- Selects -->
          <div class="flex flex-wrap items-center gap-4">
            <div class="relative min-w-[200px]">
                <select v-model="filtroEstado" @change="onFiltroEstadoChange" class="premium-input appearance-none py-3.5 pr-10">
                    <option value="">TODOS LOS ESTADOS</option>
                    <option value="activo">VIGENTE / OPERATIVO</option>
                    <option value="inactivo">INACTIVO / CERRADO</option>
                </select>
                <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                </div>
            </div>

            <div class="relative min-w-[200px]">
                <select v-model="sortBy" @change="onSortChange" class="premium-input appearance-none py-3.5 pr-10">
                    <option value="nombre-asc">NOMBRE (A-Z)</option>
                    <option value="nombre-desc">NOMBRE (Z-A)</option>
                    <option value="created_at-desc">ORDEN CRONOLÓGICO</option>
                </select>
                <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                </div>
            </div>

            <button
              @click="onLimpiarFiltros"
              class="w-12 h-12 flex items-center justify-center bg-slate-100 dark:bg-slate-800 text-slate-400 rounded-2xl hover:bg-rose-500/10 hover:text-rose-500 transition-all duration-300 active:scale-90"
              title="Resetear Filtros"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  total: { type: Number, default: 0 },
  activos: { type: Number, default: 0 },
  inactivos: { type: Number, default: 0 },
  conResponsable: { type: Number, default: 0 },
  conTelefono: { type: Number, default: 0 },
})

const emit = defineEmits([
  'crear-nueva', 'search-change', 'filtro-estado-change', 'filtro-tipo-change', 'sort-change', 'limpiar-filtros'
])

const searchTerm = defineModel('searchTerm', { type: String, default: '' })
const sortBy = defineModel('sortBy', { type: String, default: 'nombre-asc' })
const filtroEstado = defineModel('filtroEstado', { type: String, default: '' })
const filtroTipo = defineModel('filtroTipo', { type: String, default: '' })

let searchTimeout = null;

const onSearchInput = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        emit('search-change', searchTerm.value);
    }, 400);
}

const statsItems = computed(() => [
  { 
    label: 'Inventario Total', 
    value: props.total, 
    bg: 'bg-blue-500/10', 
    color: 'text-blue-500', 
    icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />' 
  },
  { 
    label: 'Red Operativa', 
    value: props.activos, 
    bg: 'bg-emerald-500/10', 
    color: 'text-emerald-500', 
    suffix: 'VIGENTES',
    icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />' 
  },
  { 
    label: 'Capacidad Inactiva', 
    value: props.inactivos, 
    bg: 'bg-rose-500/10', 
    color: 'text-rose-500', 
    icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />' 
  },
  { 
    label: 'Supervisores', 
    value: props.conResponsable, 
    bg: 'bg-amber-500/10', 
    color: 'text-amber-500', 
    icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />' 
  },
  { 
    label: 'Enlace Directo', 
    value: props.conTelefono, 
    bg: 'bg-indigo-500/10', 
    color: 'text-indigo-500', 
    icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />' 
  },
])

const onCrearNueva = () => emit('crear-nueva')
const onFiltroEstadoChange = () => emit('filtro-estado-change', filtroEstado.value)
const onSortChange = () => emit('sort-change', sortBy.value)
const onLimpiarFiltros = () => emit('limpiar-filtros')
</script>

<style scoped>
.premium-input {
    width: 100%;
    padding: 1rem 1.5rem;
    background: rgba(241, 245, 249, 0.5);
    border: 1px solid rgba(226, 232, 240, 0.5);
    border-radius: 1.25rem;
    font-size: 0.75rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: 0.1em;
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.dark .premium-input {
    background: rgba(15, 23, 42, 0.5);
    border-color: rgba(30, 41, 59, 0.5);
    color: #ffffff;
}
.premium-input:focus {
    background: white;
    border-color: #3b82f6;
    box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.1);
    transform: translateY(-1px);
    outline: none;
}
.dark .premium-input:focus {
    background: #0f172a;
    border-color: #3b82f6;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
}

.animate-fade-in-up {
    animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

