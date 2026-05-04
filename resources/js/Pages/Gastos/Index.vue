<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import ModalGasto from './ModalGasto.vue';
import ImportXmlGastoModal from '@/Components/Gastos/ImportXmlGastoModal.vue';
import { useCompanyColors } from '@/Composables/useCompanyColors';

const props = defineProps({
    gastos: Object,
    categorias: Array,
    proyectos: Array,
    filters: Object,
    /** Backend puede enviar string por decimales DB / serialización JSON */
    totalMonto: { type: [Number, String], default: 0 },
});

const { colors, cssVars, focusRingStyle } = useCompanyColors();

const search = ref(props.filters?.search || '');
const categoriaId = ref(props.filters?.categoria_id || '');
const proyectoId = ref(props.filters?.proyecto_id || '');
const estado = ref(props.filters?.estado || '');
const isRefreshing = ref(false);

const applyFilters = () => {
    isRefreshing.value = true;
    router.get(route('gastos.index'), {
        search: search.value,
        categoria_id: categoriaId.value,
        proyecto_id: proyectoId.value,
        estado: estado.value,
        per_page: props.filters?.per_page || 15
    }, { 
        preserveState: true,
        onFinish: () => isRefreshing.value = false
    });
};

const clearFilters = () => {
    search.value = '';
    categoriaId.value = '';
    proyectoId.value = '';
    estado.value = '';
    router.get(route('gastos.index'));
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const datePart = dateString.split('T')[0];
    const [year, month, day] = datePart.split('-');
    const date = new Date(year, month - 1, day);
    return date.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
};

// Computed statistics more advanced
const stats = computed(() => {
    const totalCount = props.gastos?.total || 0;
    const rows = Array.isArray(props.gastos?.data) ? props.gastos.data : [];
    
    const procesadas = rows.filter((g) => g.estado?.toLowerCase() === 'procesada').length;
    const canceladas = rows.filter((g) => g.estado?.toLowerCase() === 'cancelada').length;
    
    return { 
        totalCount, 
        procesadas, 
        canceladas,
        montoVisible: Number(props.totalMonto) || 0
    };
});

const cancelGasto = (id) => {
    if (confirm('¿Estás seguro de cancelar este gasto?')) {
        router.post(route('gastos.cancel', id));
    }
};

const deleteGasto = (id) => {
    if (confirm('¿Estás seguro de eliminar este gasto?')) {
        router.delete(route('gastos.destroy', id), {}, {
            onSuccess: () => router.reload()
        });
    }
};

const showingModal = ref(false);
const selectedGasto = ref(null);

const showGasto = (gasto) => {
    selectedGasto.value = gasto;
    showingModal.value = true;
};

const showImportXmlModal = ref(false);

const importarDesdeXml = () => {
  showImportXmlModal.value = true;
};

const handleXmlImport = (cfdiData) => {
  sessionStorage.setItem('cfdi_gasto_import_data', JSON.stringify(cfdiData));
  router.visit('/gastos/create?from_xml=1');
};

const closeModal = () => {
    showingModal.value = false;
    setTimeout(() => {
        selectedGasto.value = null;
    }, 200);
};

// Animación de entrada
const isLoaded = ref(false);
onMounted(() => {
    setTimeout(() => isLoaded.value = true, 100);
});
</script>

<template>
    <AppLayout title="Gastos Operativos">
        <Head title="Gastos Operativos" />

        <div class="min-h-screen bg-slate-950 text-slate-200 font-sans selection:bg-orange-500/30 pb-20" :style="cssVars">
            
            <!-- Floating Header -->
            <div class="sticky top-0 z-40 bg-slate-950/80 backdrop-blur-xl border-b border-white/5 px-6 py-4 mb-8">
                <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="flex items-center space-x-5">
                        <div class="p-3.5 rounded-2xl bg-gradient-to-br from-orange-500 to-amber-600 shadow-lg shadow-orange-500/20 transform hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-black tracking-tight text-white m-0">Control de Gastos</h1>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="w-2 h-2 rounded-full bg-orange-500 animate-pulse"></span>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Gestión Operativa • Sonora</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button @click="importarDesdeXml" 
                                class="group flex items-center gap-2.5 px-5 py-3 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-white font-bold transition-all active:scale-95">
                            <svg class="w-5 h-5 text-orange-400 group-hover:rotate-12 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            <span class="text-sm">Importar XML</span>
                        </button>
                        
                        <Link :href="route('gastos.create')" 
                              class="flex items-center gap-2.5 px-6 py-3 rounded-2xl bg-orange-600 hover:bg-orange-500 text-white font-bold shadow-xl shadow-orange-600/20 transition-all hover:-translate-y-0.5 active:scale-95">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span class="text-sm">Nuevo Gasto</span>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-6 space-y-10">
                
                <!-- Advanced Stats Section -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 animate-in slide-in-from-bottom-4 duration-700">
                    <div class="group bg-slate-900/40 border border-white/5 rounded-[2rem] p-6 backdrop-blur-sm transition-all hover:bg-slate-900/60 hover:border-white/10">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-2xl bg-orange-500/10 flex items-center justify-center text-orange-400 group-hover:bg-orange-500 group-hover:text-white transition-all duration-500">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                            </div>
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Gastos Totales</span>
                        </div>
                        <p class="text-3xl font-black text-white leading-none">{{ stats.totalCount }}</p>
                        <p class="text-xs text-slate-500 mt-2 font-medium">Registros en el periodo</p>
                    </div>

                    <div class="group bg-slate-900/40 border border-white/5 rounded-[2rem] p-6 backdrop-blur-sm transition-all hover:bg-slate-900/60 hover:border-emerald-500/20">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-emerald-400 group-hover:bg-emerald-500 group-hover:text-white transition-all duration-500">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Procesadas</span>
                        </div>
                        <p class="text-3xl font-black text-white leading-none">{{ stats.procesadas }}</p>
                        <p class="text-xs text-emerald-500/70 mt-2 font-bold uppercase tracking-tight">Efectivas</p>
                    </div>

                    <div class="group bg-slate-900/40 border border-white/5 rounded-[2rem] p-6 backdrop-blur-sm transition-all hover:bg-slate-900/60 hover:border-rose-500/20">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-2xl bg-rose-500/10 flex items-center justify-center text-rose-400 group-hover:bg-rose-500 group-hover:text-white transition-all duration-500">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Canceladas</span>
                        </div>
                        <p class="text-3xl font-black text-white leading-none">{{ stats.canceladas }}</p>
                        <p class="text-xs text-rose-500/70 mt-2 font-bold uppercase tracking-tight">Anuladas</p>
                    </div>

                    <div class="group bg-slate-900/50 border border-orange-500/20 rounded-[2rem] p-6 backdrop-blur-sm shadow-xl shadow-orange-500/5 transition-all hover:border-orange-500/40">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-2xl bg-orange-500 flex items-center justify-center text-white shadow-lg shadow-orange-500/20">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Inversión Total</span>
                        </div>
                        <p class="text-2xl font-black text-orange-400 leading-none">{{ formatCurrency(stats.montoVisible) }}</p>
                        <p class="text-xs text-slate-400 mt-2 font-medium italic">Filtro aplicado</p>
                    </div>
                </div>

                <!-- Filters Toolbar -->
                <div class="bg-slate-900/30 border border-white/5 rounded-3xl p-6 backdrop-blur-sm animate-in fade-in duration-1000">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Búsqueda Inteligente</label>
                            <div class="relative group">
                                <input type="text" v-model="search" @keyup.enter="applyFilters"
                                    placeholder="Número, descripción..."
                                    class="w-full bg-slate-950/50 border-white/10 rounded-2xl pl-12 pr-4 py-4 text-sm text-white focus:ring-2 focus:ring-orange-500/50 focus:border-orange-500/50 transition-all outline-none"
                                />
                                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-600 transition-colors group-focus-within:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Categoría</label>
                            <select v-model="categoriaId" @change="applyFilters"
                                class="w-full bg-slate-950/50 border-white/10 rounded-2xl px-5 py-4 text-sm text-white focus:ring-2 focus:ring-orange-500/50 outline-none transition-all appearance-none"
                            >
                                <option value="">Todas las categorías</option>
                                <option v-for="cat in categorias" :key="cat.id" :value="cat.id">
                                    {{ cat.nombre }}
                                </option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Estado</label>
                            <select v-model="estado" @change="applyFilters"
                                class="w-full bg-slate-950/50 border-white/10 rounded-2xl px-5 py-4 text-sm text-white focus:ring-2 focus:ring-orange-500/50 outline-none transition-all appearance-none"
                            >
                                <option value="">Cualquier estado</option>
                                <option value="procesada">Procesada</option>
                                <option value="cancelada">Cancelada</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Asignar Proyecto</label>
                            <select v-model="proyectoId" @change="applyFilters"
                                class="w-full bg-slate-950/50 border-white/10 rounded-2xl px-5 py-4 text-sm text-white focus:ring-2 focus:ring-orange-500/50 outline-none transition-all appearance-none"
                            >
                                <option value="">Sin proyecto</option>
                                <option v-for="proyecto in proyectos" :key="proyecto.id" :value="proyecto.id">
                                    {{ proyecto.nombre }}
                                </option>
                            </select>
                        </div>

                        <div class="flex gap-2">
                            <button @click="applyFilters" 
                                    class="flex-1 py-4 rounded-2xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-sm transition-all flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" :class="{'animate-spin': isRefreshing}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                Aplicar
                            </button>
                            <button @click="clearFilters" 
                                    class="p-4 rounded-2xl bg-white/5 hover:bg-white/10 text-slate-400 transition-all shadow-inner" title="Limpiar Filtros">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Main Datatable -->
                <div class="bg-slate-900/40 border border-white/5 rounded-[2.5rem] overflow-hidden backdrop-blur-sm animate-in zoom-in-95 duration-700 shadow-2xl">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-white/5">
                                    <th class="px-6 py-5 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Referencia</th>
                                    <th class="px-6 py-5 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Fecha</th>
                                    <th class="px-6 py-5 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Categoría / Proyecto</th>
                                    <th class="px-6 py-5 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Descripción & Proveedor</th>
                                    <th class="px-6 py-5 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] text-right">Inversión</th>
                                    <th class="px-6 py-5 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] text-center">Estado</th>
                                    <th class="px-6 py-5 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr v-for="(gasto, index) in gastos.data" :key="gasto.id" 
                                    class="group hover:bg-white/5 transition-all duration-300"
                                    :style="{ animationDelay: `${index * 50}ms` }">
                                    <td class="px-6 py-6">
                                        <button @click="showGasto(gasto)" class="text-orange-400 hover:text-orange-300 font-bold font-mono text-sm underline decoration-orange-500/20 underline-offset-4 transition-all">
                                            {{ gasto.numero_compra }}
                                        </button>
                                    </td>
                                    <td class="px-6 py-6">
                                        <div class="text-white font-bold text-sm">{{ formatDate(gasto.fecha_compra) }}</div>
                                        <div class="text-[10px] text-slate-600 font-medium uppercase mt-0.5 tracking-tighter">Registrado</div>
                                    </td>
                                    <td class="px-6 py-6">
                                        <div class="text-slate-300 font-bold text-xs uppercase tracking-tight">{{ gasto.categoria_gasto?.nombre || '-' }}</div>
                                        <div v-if="gasto.proyecto" class="mt-1.5 inline-flex items-center px-2 py-0.5 rounded-lg bg-indigo-500/10 text-indigo-400 text-[10px] font-bold border border-indigo-500/20 uppercase">
                                            {{ gasto.proyecto.nombre }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-6 max-w-sm">
                                        <p class="text-white font-medium text-sm line-clamp-1 italic text-slate-100" :title="gasto.notas">"{{ gasto.notas || '-' }}"</p>
                                        <p class="text-[10px] text-slate-500 mt-1 font-bold uppercase tracking-wider">{{ gasto.proveedor?.nombre_razon_social || 'Proveedor Independiente' }}</p>
                                    </td>
                                    <td class="px-6 py-6 text-right">
                                        <div class="text-lg font-black text-white leading-none">{{ formatCurrency(gasto.total) }}</div>
                                        <div class="text-[10px] text-slate-600 font-bold uppercase mt-1 tracking-widest">Neto MXN</div>
                                    </td>
                                    <td class="px-6 py-6 text-center">
                                        <span :class="gasto.estado?.toLowerCase() === 'procesada' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border-rose-500/20'"
                                              class="px-3 py-1.5 text-[10px] font-black rounded-xl border uppercase tracking-[0.1em]">
                                            {{ gasto.estado }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-6">
                                        <div class="flex justify-center items-center gap-2">
                                            <button @click="showGasto(gasto)" 
                                                    class="p-2.5 rounded-xl bg-indigo-500/10 text-indigo-400 hover:bg-indigo-500 hover:text-white transition-all active:scale-95 shadow-lg shadow-indigo-500/0 hover:shadow-indigo-500/10"
                                                    title="Ver Detalle">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </button>
                                            
                                            <Link v-if="gasto.estado?.toLowerCase() === 'procesada'" 
                                                  :href="route('gastos.edit', gasto.id)"
                                                  class="p-2.5 rounded-xl bg-orange-500/10 text-orange-400 hover:bg-orange-500 hover:text-white transition-all active:scale-95 shadow-lg shadow-orange-500/0 hover:shadow-orange-500/10"
                                                  title="Editar Transacción">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </Link>

                                            <button v-if="gasto.estado?.toLowerCase() === 'procesada'" 
                                                    @click="cancelGasto(gasto.id)"
                                                    class="p-2.5 rounded-xl bg-rose-500/10 text-rose-400 hover:bg-rose-500 hover:text-white transition-all active:scale-95 shadow-lg shadow-rose-500/0 hover:shadow-rose-500/10"
                                                    title="Cancelar Registro">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            </button>
                                            
                                            <button @click="deleteGasto(gasto.id)"
                                                    class="p-2.5 rounded-xl bg-slate-800 text-slate-500 hover:bg-rose-600 hover:text-white transition-all active:scale-95"
                                                    title="Eliminar Permanente">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!gastos.data?.length">
                                    <td colspan="7" class="px-6 py-24 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="w-16 h-16 rounded-full bg-slate-900 flex items-center justify-center text-slate-700 border border-white/5 animate-pulse">
                                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
                                            </div>
                                            <p class="text-slate-500 font-bold uppercase tracking-widest text-xs">Sin registros que mostrar</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Modern Pagination -->
                    <div v-if="gastos.links?.length > 3" class="px-8 py-6 bg-white/5 border-t border-white/5 flex flex-col sm:flex-row justify-between items-center gap-6">
                        <span class="text-xs text-slate-500 font-bold uppercase tracking-widest">
                            Mostrando <span class="text-white">{{ gastos.from }}</span> a <span class="text-white">{{ gastos.to }}</span> de <span class="text-white">{{ gastos.total }}</span> resultados
                        </span>
                        <div class="flex items-center gap-1.5">
                            <Link v-for="link in gastos.links" :key="link.label"
                                :href="link.url || '#'"
                                :disabled="!link.url"
                                :class="[
                                    'px-4 py-2 text-[10px] font-black rounded-xl border transition-all uppercase tracking-tighter',
                                    link.active ? 'bg-orange-600 border-orange-500 text-white shadow-lg shadow-orange-600/20' : 'bg-white/5 border-white/5 text-slate-400 hover:bg-white/10 hover:text-white',
                                    !link.url ? 'opacity-30 cursor-not-allowed grayscale' : ''
                                ]"
                                v-html="link.label" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Modal (existing component) -->
        <ModalGasto 
            :show="showingModal" 
            :gasto="selectedGasto" 
            @close="closeModal" 
        />

        <!-- XML Import Modal (existing component) -->
        <ImportXmlGastoModal
            :show="showImportXmlModal"
            @close="showImportXmlModal = false"
            @import="handleXmlImport"
        />
    </AppLayout>
</template>

<style scoped>
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Animations */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes zoomIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}

.animate-in {
    animation-fill-mode: both;
}

.slide-in-from-bottom-4 {
    animation-name: fadeInUp;
}

.zoom-in-95 {
    animation-name: zoomIn;
}

/* Scrollbar styling for dark mode */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
::-webkit-scrollbar-track {
    background: #020617;
}
::-webkit-scrollbar-thumb {
    background: #1e293b;
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
    background: #334155;
}
</style>



