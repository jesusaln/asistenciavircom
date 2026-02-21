<template>
  <div class="space-y-8 animate-fade-in-up">
    
    <!-- Top Row: Title & Principal Action -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-2">
            <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter uppercase">Órdenes de Compra</h1>
            <div class="flex items-center gap-4">
                <span class="text-[10px] font-black uppercase tracking-[0.3em] text-blue-600 dark:text-blue-400">Suministros Globales</span>
                <div class="h-1 w-1 rounded-full bg-slate-200 dark:bg-slate-800"></div>
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]"></div>
                    <span class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">Canal de Adquisición</span>
                </div>
            </div>
        </div>

        <button
            @click="$emit('crear-nueva')"
            class="group relative flex items-center justify-center gap-3 px-10 py-5 bg-blue-600 text-white rounded-3xl text-[11px] font-black uppercase tracking-widest shadow-2xl shadow-blue-600/20 hover:shadow-blue-600/40 hover:-translate-y-1 transition-all duration-500 active:scale-95 bg-gradient-to-r from-blue-600 to-indigo-600 overflow-hidden"
        >
            <div class="absolute inset-0 bg-white/10 translate-y-full group-hover:translate-y-0 transition-transform duration-500"></div>
            <svg class="w-5 h-5 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" /></svg>
            <span class="relative z-10">Nueva Orden de Compra</span>
        </button>
    </div>

    <!-- Stats Section -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-6">
      <!-- Total -->
      <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] p-6 border border-slate-200/50 dark:border-slate-800/50 hover:border-blue-500/30 transition-all duration-500 hover:shadow-2xl hover:shadow-blue-500/10 overflow-hidden">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-500/5 rounded-full blur-2xl group-hover:bg-blue-500/10 transition-colors"></div>
        <div class="relative flex flex-col gap-4">
          <div class="w-10 h-10 bg-blue-500/10 rounded-2xl flex items-center justify-center text-blue-600 dark:text-blue-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
          </div>
          <div>
            <span class="text-[9px] font-black uppercase tracking-[0.25em] text-slate-400 group-hover:text-blue-500 transition-colors">Totales</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter">{{ total }}</div>
          </div>
        </div>
      </div>

      <!-- Pendientes -->
      <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] p-6 border border-slate-200/50 dark:border-slate-800/50 hover:border-amber-500/30 transition-all duration-500 hover:shadow-2xl hover:shadow-amber-500/10 overflow-hidden">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-amber-500/5 rounded-full blur-2xl group-hover:bg-amber-500/10 transition-colors"></div>
        <div class="relative flex flex-col gap-4">
          <div class="w-10 h-10 bg-amber-500/10 rounded-2xl flex items-center justify-center text-amber-600 dark:text-amber-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          </div>
          <div>
            <span class="text-[9px] font-black uppercase tracking-[0.25em] text-slate-400 group-hover:text-amber-500 transition-colors">Pendientes</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter">{{ pendientes }}</div>
          </div>
        </div>
      </div>

      <!-- Enviadas -->
      <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] p-6 border border-slate-200/50 dark:border-slate-800/50 hover:border-indigo-500/30 transition-all duration-500 hover:shadow-2xl hover:shadow-indigo-500/10 overflow-hidden">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-indigo-500/5 rounded-full blur-2xl group-hover:bg-indigo-500/10 transition-colors"></div>
        <div class="relative flex flex-col gap-4">
          <div class="w-10 h-10 bg-indigo-500/10 rounded-2xl flex items-center justify-center text-indigo-600 dark:text-indigo-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
          </div>
          <div>
            <span class="text-[9px] font-black uppercase tracking-[0.25em] text-slate-400 group-hover:text-indigo-500 transition-colors">Enviadas</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter">{{ enviadas_a_proveedor }}</div>
          </div>
        </div>
      </div>

      <!-- Procesadas -->
      <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] p-6 border border-slate-200/50 dark:border-slate-800/50 hover:border-emerald-500/30 transition-all duration-500 hover:shadow-2xl hover:shadow-emerald-500/10 overflow-hidden">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-500/5 rounded-full blur-2xl group-hover:bg-emerald-500/10 transition-colors"></div>
        <div class="relative flex flex-col gap-4">
          <div class="w-10 h-10 bg-emerald-500/10 rounded-2xl flex items-center justify-center text-emerald-600 dark:text-emerald-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          </div>
          <div>
            <span class="text-[9px] font-black uppercase tracking-[0.25em] text-slate-400 group-hover:text-emerald-500 transition-colors">Procesadas</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter">{{ procesadas }}</div>
          </div>
        </div>
      </div>

      <!-- Canceladas -->
      <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] p-6 border border-slate-200/50 dark:border-slate-800/50 hover:border-rose-500/30 transition-all duration-500 hover:shadow-2xl hover:shadow-rose-500/10 overflow-hidden">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-rose-500/5 rounded-full blur-2xl group-hover:bg-rose-500/10 transition-colors"></div>
        <div class="relative flex flex-col gap-4">
          <div class="w-10 h-10 bg-rose-500/10 rounded-2xl flex items-center justify-center text-rose-600 dark:text-rose-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </div>
          <div>
            <span class="text-[9px] font-black uppercase tracking-[0.25em] text-slate-400 group-hover:text-rose-500 transition-colors">Canceladas</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter">{{ canceladas }}</div>
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
                    placeholder="BUSCAR ORDEN O PROVEEDOR..."
                    class="w-full pl-14 pr-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-blue-600/20 transition-all tracking-wider"
                    @input="$emit('search-change', $event.target.value)"
                />
            </div>

            <!-- Filters Group -->
            <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                <div class="relative flex-1 sm:flex-none">
                    <select
                        v-model="filtroEstado"
                        class="w-full sm:w-auto appearance-none pl-6 pr-10 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-[10px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-300 focus:ring-2 focus:ring-blue-600/20 cursor-pointer transition-all"
                        @change="$emit('filtro-estado-change', $event.target.value)"
                    >
                        <option value="">TODOS LOS ESTADOS</option>
                        <option value="pendiente">PENDIENTES</option>
                        <option value="enviado_a_proveedor">ENVIADAS</option>
                        <option value="procesada">PROCESADAS</option>
                        <option value="cancelada">CANCELADAS</option>
                    </select>
                    <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-slate-400 line-height-0">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                </div>

                <div class="relative flex-1 sm:flex-none">
                    <select
                        v-model="sortBy"
                        class="w-full sm:w-auto appearance-none pl-6 pr-10 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-[10px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-300 focus:ring-2 focus:ring-blue-600/20 cursor-pointer transition-all"
                        @change="$emit('sort-change', $event.target.value)"
                    >
                        <option value="created_at-desc">RECIENTES</option>
                        <option value="created_at-asc">ANTIGUAS</option>
                        <option value="total-desc">MONTO MAYOR</option>
                        <option value="total-asc">MONTO MENOR</option>
                    </select>
                    <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                </div>

                <button
                    @click="$emit('limpiar-filtros')"
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
const props = defineProps({
  total: { type: Number, default: 0 },
  pendientes: { type: Number, default: 0 },
  enviadas_a_proveedor: { type: Number, default: 0 },
  procesadas: { type: Number, default: 0 },
  canceladas: { type: Number, default: 0 },
})

const emit = defineEmits([
  'crear-nueva', 'search-change', 'filtro-estado-change', 'sort-change', 'limpiar-filtros'
])

const searchTerm = defineModel('searchTerm', { type: String, default: '' })
const sortBy = defineModel('sortBy', { type: String, default: 'created_at-desc' })
const filtroEstado = defineModel('filtroEstado', { type: String, default: '' })
</script>

<style scoped>
.animate-fade-in-up { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
</style>
