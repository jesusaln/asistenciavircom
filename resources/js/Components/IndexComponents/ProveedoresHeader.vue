<template>
  <div class="space-y-8 animate-fade-in-up">
    
    <!-- Top Row: Title & Principal Action -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-2">
            <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter uppercase">Proveedores</h1>
            <div class="flex items-center gap-4">
                <span class="text-[10px] font-black uppercase tracking-[0.3em] text-emerald-600 dark:text-emerald-400">Aliados Estratégicos</span>
                <div class="h-1 w-1 rounded-full bg-slate-200 dark:bg-slate-800"></div>
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-1.5 rounded-full bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.5)]"></div>
                    <span class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">Gestión de Suministro</span>
                </div>
            </div>
        </div>

        <button
            @click="$emit('crear-nueva')"
            class="group relative flex items-center justify-center gap-3 px-10 py-5 bg-emerald-600 text-white rounded-3xl text-[11px] font-black uppercase tracking-widest shadow-2xl shadow-emerald-600/20 hover:shadow-emerald-600/40 hover:-translate-y-1 transition-all duration-500 active:scale-95 bg-gradient-to-r from-emerald-600 to-teal-600 overflow-hidden"
        >
            <div class="absolute inset-0 bg-white/10 translate-y-full group-hover:translate-y-0 transition-transform duration-500"></div>
            <svg class="w-5 h-5 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" /></svg>
            <span class="relative z-10">Nuevo Perfil Proveedor</span>
        </button>
    </div>

    <!-- Stats Section -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
      <!-- Total -->
      <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] p-6 border border-slate-200/50 dark:border-slate-800/50 hover:border-blue-500/30 transition-all duration-500 hover:shadow-2xl hover:shadow-blue-500/10 overflow-hidden">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-500/5 rounded-full blur-2xl group-hover:bg-blue-500/10 transition-colors"></div>
        <div class="relative flex flex-col gap-4">
          <div class="w-10 h-10 bg-blue-500/10 rounded-2xl flex items-center justify-center text-blue-600 dark:text-blue-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
          </div>
          <div>
            <span class="text-[9px] font-black uppercase tracking-[0.25em] text-slate-400 group-hover:text-blue-500 transition-colors">Total Unidades</span>
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
            <span class="text-[9px] font-black uppercase tracking-[0.25em] text-slate-400 group-hover:text-emerald-500 transition-colors">Operativas</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter">{{ activos }}</div>
          </div>
        </div>
      </div>

      <!-- Personas Fisicas -->
      <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] p-6 border border-slate-200/50 dark:border-slate-800/50 hover:border-amber-500/30 transition-all duration-500 hover:shadow-2xl hover:shadow-amber-500/10 overflow-hidden">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-amber-500/5 rounded-full blur-2xl group-hover:bg-amber-500/10 transition-colors"></div>
        <div class="relative flex flex-col gap-4">
          <div class="w-10 h-10 bg-amber-500/10 rounded-2xl flex items-center justify-center text-amber-600 dark:text-amber-400">
             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
          </div>
          <div>
            <span class="text-[9px] font-black uppercase tracking-[0.25em] text-slate-400 group-hover:text-amber-500 transition-colors">P. Físicas</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter">{{ personas_fisicas }}</div>
          </div>
        </div>
      </div>

      <!-- Digitalizados -->
      <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] p-6 border border-slate-200/50 dark:border-slate-800/50 hover:border-indigo-500/30 transition-all duration-500 hover:shadow-2xl hover:shadow-indigo-500/10 overflow-hidden">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-indigo-500/5 rounded-full blur-2xl group-hover:bg-indigo-500/10 transition-colors"></div>
        <div class="relative flex flex-col gap-4">
          <div class="w-10 h-10 bg-indigo-500/10 rounded-2xl flex items-center justify-center text-indigo-600 dark:text-indigo-400">
             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
          </div>
          <div>
            <span class="text-[9px] font-black uppercase tracking-[0.25em] text-slate-400 group-hover:text-indigo-500 transition-colors">Con Contacto</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter">{{ con_email }}</div>
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
                    @input="$emit('search-change', $event.target.value)"
                    type="text"
                    placeholder="BUSCAR PROVEEDOR POR NOMBRE O RFC..."
                    class="w-full pl-14 pr-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-emerald-600/20 transition-all tracking-wider"
                />
            </div>

            <!-- Filters -->
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <!-- Sort Select -->
                <select 
                    v-model="sortBy"
                    @change="$emit('sort-change', $event.target.value)"
                    class="flex-1 sm:w-48 px-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-[10px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-400 focus:ring-2 focus:ring-emerald-600/20 transition-all appearance-none"
                >
                    <option value="created_at-desc">ORDEN: MÁS RECIENTES</option>
                    <option value="created_at-asc">ORDEN: MÁS ANTIGUOS</option>
                    <option value="nombre_razon_social-asc">A-Z: NOMBRE</option>
                    <option value="nombre_razon_social-desc">Z-A: NOMBRE</option>
                </select>

                <select 
                    v-model="filtroEstado"
                    @change="$emit('filtro-estado-change', $event.target.value)"
                    class="flex-1 sm:w-40 px-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-[10px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-400 focus:ring-2 focus:ring-emerald-600/20 transition-all appearance-none"
                >
                    <option value="">ESTADO: TODOS</option>
                    <option value="1">ACTIVOS</option>
                    <option value="0">INACTIVOS</option>
                </select>

                <select 
                    v-model="filtroTipoPersona"
                    @change="$emit('filtro-tipo-persona-change', $event.target.value)"
                    class="flex-1 sm:w-48 px-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-[10px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-400 focus:ring-2 focus:ring-emerald-600/20 transition-all appearance-none"
                >
                    <option value="">TIPO: TODOS</option>
                    <option value="Fisica">P. FÍSICA</option>
                    <option value="Moral">P. MORAL</option>
                </select>

                 <button
                    @click="$emit('limpiar-filtros')"
                    class="w-14 h-14 flex items-center justify-center bg-slate-100/50 dark:bg-slate-950/50 text-slate-400 hover:text-rose-500 rounded-2xl transition-all border border-transparent hover:border-rose-500/20"
                    title="Limpiar búsqueda"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
        </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  total: { type: Number, default: 0 },
  activos: { type: Number, default: 0 },
  inactivos: { type: Number, default: 0 },
  personas_fisicas: { type: Number, default: 0 },
  personas_morales: { type: Number, default: 0 },
  con_email: { type: Number, default: 0 },
})

const searchTerm = defineModel('searchTerm', { type: String, default: '' })
const sortBy = defineModel('sortBy', { type: String, default: 'created_at-desc' })
const filtroEstado = defineModel('filtroEstado', { type: String, default: '' })
const filtroTipoPersona = defineModel('filtroTipoPersona', { type: String, default: '' })
const filtroEstadoMexico = defineModel('filtroEstadoMexico', { type: String, default: '' })

const emit = defineEmits([
  'crear-nueva', 'search-change', 'filtro-estado-change', 'filtro-tipo-persona-change', 
  'filtro-estado-mexico-change', 'sort-change', 'limpiar-filtros'
])
</script>

<style scoped>
.animate-fade-in-up { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
</style>
