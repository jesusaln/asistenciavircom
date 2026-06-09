<template>
  <div class="space-y-8 animate-fade-in-up">
    
    <!-- Top Row: Title & Principal Actions -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div class="space-y-2">
            <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter uppercase">Kits de Productos</h1>
            <div class="flex items-center gap-4">
                <span class="text-[10px] font-black uppercase tracking-[0.3em] text-blue-600 dark:text-blue-400">Configuraciones de Venta</span>
                <div class="h-1 w-1 rounded-full bg-slate-200 dark:bg-slate-800"></div>
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]"></div>
                    <span class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">Gestión de Paquetes</span>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <Link
                href="/kits/create"
                class="px-8 py-4 bg-blue-600 text-white rounded-[2rem] text-[11px] font-black uppercase tracking-widest shadow-2xl shadow-blue-600/20 hover:shadow-blue-600/40 hover:-translate-y-1 transition-all active:scale-95 flex items-center gap-3"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                Nuevo Kit Comercial
            </Link>
        </div>
    </div>

    <!-- Stats Row: High Impact Data -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Kits -->
        <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl p-8 rounded-[2.5rem] border border-slate-200/50 dark:border-slate-800/50 shadow-2xl shadow-slate-900/5 hover:border-blue-500/30 transition-all duration-500">
            <div class="flex items-start justify-between">
                <div class="space-y-4">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] block">Catálogo Total</span>
                    <div class="text-3xl font-black text-slate-900 dark:text-white tracking-tighter leading-none">{{ totalKits }}</div>
                    <div class="flex items-center gap-2 px-2 py-1 bg-blue-500/10 text-blue-500 rounded-full w-fit">
                        <span class="text-[8px] font-black uppercase tracking-widest">Kits Registrados</span>
                    </div>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-blue-500/10 flex items-center justify-center text-blue-500 transition-transform group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                </div>
            </div>
        </div>

        <!-- Kits Activos -->
        <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl p-8 rounded-[2.5rem] border border-slate-200/50 dark:border-slate-800/50 shadow-2xl shadow-slate-900/5 hover:border-emerald-500/30 transition-all duration-500">
            <div class="flex items-start justify-between">
                <div class="space-y-4">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] block">Disponibilidad</span>
                    <div class="text-3xl font-black text-slate-900 dark:text-white tracking-tighter leading-none">{{ kitsActivos }}</div>
                    <div class="flex items-center gap-2 px-2 py-1 bg-emerald-500/10 text-emerald-500 rounded-full w-fit">
                        <span class="text-[8px] font-black uppercase tracking-widest">Kits en Venta</span>
                    </div>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-emerald-500 transition-transform group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>
        </div>

        <!-- Valor Total -->
        <div class="group relative bg-blue-600 p-8 rounded-[2.5rem] shadow-2xl shadow-blue-600/30 hover:-translate-y-1 transition-all duration-500 lg:col-span-2 overflow-hidden">
            <!-- Decorative background elements -->
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-10 -left-10 w-24 h-24 bg-white/5 rounded-full blur-2xl"></div>

            <div class="relative z-10 flex items-center justify-between">
                <div class="space-y-4">
                    <span class="text-[10px] font-black text-white/60 uppercase tracking-[0.2em] block">Potencial Comercial (PV)</span>
                    <div class="text-4xl font-black text-white tracking-tighter leading-none">${{ formatearMoneda(valorTotal) }}</div>
                    <div class="flex items-center gap-2 px-3 py-1 bg-white/20 text-white rounded-full w-fit backdrop-blur-md">
                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></div>
                        <span class="text-[9px] font-black uppercase tracking-widest">Valor Real en Catálogo</span>
                    </div>
                </div>
                <div class="w-16 h-16 rounded-3xl bg-white/10 border border-white/20 flex items-center justify-center text-white backdrop-blur-xl">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" /></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter Row -->
    <div class="flex flex-col md:flex-row items-center justify-between gap-6 bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl p-4 rounded-[2.5rem] border border-slate-200/50 dark:border-slate-800/50 shadow-2xl shadow-slate-900/5">
        <div class="relative flex-1 w-full group">
            <div class="absolute inset-y-0 left-6 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-500 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </div>
            <input
                v-model="searchTerm"
                type="text"
                placeholder="LOCALIZAR KIT POR NOMBRE O CÓDIGO OPERATIVO..."
                class="w-full pl-16 pr-8 py-5 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-3xl text-[11px] font-black uppercase tracking-widest text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-blue-600/20 transition-all"
                @input="onSearchChange"
                @keydown="onSearchKeydown"
            />
        </div>

        <div class="flex items-center gap-4 w-full md:w-auto">
            <button
                @click="onLimpiarFiltros"
                class="w-12 h-14 flex items-center justify-center bg-slate-100/50 dark:bg-slate-950/50 text-slate-400 hover:text-rose-500 rounded-2xl transition-all border border-transparent hover:border-rose-500/20 active:scale-95"
                title="Limpiar filtros"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  totalKits: { type: Number, default: 0 },
  kitsActivos: { type: Number, default: 0 },
  valorTotal: { type: Number, default: 0 },
})

const emit = defineEmits([
  'search-change', 'limpiar-filtros'
])

const searchTerm = defineModel('searchTerm', { type: String, default: '' })

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

const onLimpiarFiltros = () => emit('limpiar-filtros')
</script>

<style scoped>
.animate-fade-in-up { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
</style>
