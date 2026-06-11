<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import ModalGasto from './ModalGasto.vue';
import ImportXmlGastoModal from '@/Components/Gastos/ImportXmlGastoModal.vue';
import { useCompanyColors } from '@/Composables/useCompanyColors';
import Swal from '@/Utils/Swal';
import { Doughnut, Bar } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, ArcElement, CategoryScale, LinearScale, BarElement } from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, ArcElement, CategoryScale, LinearScale, BarElement);

const props = defineProps({
    gastos: Object,
    categorias: Array,
    proyectos: Array,
    filters: Object,
    totalMonto: Number,
    cajaChicaResumen: Array,
    tecnicos: Array,
    statsData: Object,
    misGastos: Boolean
});

const { colors, cssVars, focusRingStyle } = useCompanyColors();

const search = ref(props.filters?.search || '');
const categoriaId = ref(props.filters?.categoria_id || '');
const proyectoId = ref(props.filters?.proyecto_id || '');
const estado = ref(props.filters?.estado || '');
const misGastos = ref(props.misGastos);
const fechaDesde = ref(props.filters?.fecha_desde || '');
const fechaHasta = ref(props.filters?.fecha_hasta || '');
const isRefreshing = ref(false);

const applyFilters = () => {
    isRefreshing.value = true;
    router.get(route('gastos.index'), {
        search: search.value,
        categoria_id: categoriaId.value,
        proyecto_id: proyectoId.value,
        estado: estado.value,
        mis_gastos: misGastos.value ? 1 : 0,
        fecha_desde: fechaDesde.value,
        fecha_hasta: fechaHasta.value,
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
    fechaDesde.value = '';
    fechaHasta.value = '';
    applyFilters();
};

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false }
    },
    cutout: '70%'
};

const categoryChartData = computed(() => ({
    labels: props.statsData?.por_categoria?.map(i => i.nombre) || [],
    datasets: [{
        data: props.statsData?.por_categoria?.map(i => i.total) || [],
        backgroundColor: ['#F59E0B', '#10B981', '#3B82F6', '#8B5CF6', '#EC4899'],
        borderWidth: 0
    }]
}));

const projectChartData = computed(() => ({
    labels: props.statsData?.por_proyecto?.map(i => i.nombre) || [],
    datasets: [{
        label: 'Inversión',
        data: props.statsData?.por_proyecto?.map(i => i.total) || [],
        backgroundColor: '#F59E0B',
        borderRadius: 8
    }]
}));

const { formatCurrency } = useFormatters();

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const datePart = dateString.split('T')[0];
    const [year, month, day] = datePart.split('-');
    const date = new Date(year, month - 1, day);
    return date.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
};

const stats = computed(() => {
    const totalCount = props.gastos?.total || 0;
    return { 
        totalCount, 
        montoVisible: Number(props.totalMonto) || 0
    };
});

const cancelGasto = async (id) => {
    const { isConfirmed } = await Swal.fire({
        title: 'Cancelar gasto',
        text: '¿Estás seguro de cancelar este gasto?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'No',
    });
    if (isConfirmed) {
        router.post(route('gastos.cancel', id));
    }
};

const deleteGasto = async (id) => {
    const { isConfirmed } = await Swal.fire({
        title: 'Eliminar gasto',
        text: '¿Estás seguro de eliminar este gasto?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'No',
    });
    if (isConfirmed) {
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
const importarDesdeXml = () => { showImportXmlModal.value = true; };

const handleXmlImport = (cfdiData) => {
  sessionStorage.setItem('cfdi_gasto_import_data', JSON.stringify(cfdiData));
  router.visit('/gastos/create?from_xml=1');
};

const exportExcel = () => {
    window.location.href = route('gastos.export.excel', {
        search: search.value,
        categoria_id: categoriaId.value,
        proyecto_id: proyectoId.value,
        estado: estado.value,
        fecha_desde: fechaDesde.value,
        fecha_hasta: fechaHasta.value,
        mis_gastos: misGastos.value ? 1 : 0,
    });
};

const monthlyChartData = computed(() => ({
    labels: props.statsData?.por_mes?.map(i => i.mes) || [],
    datasets: [{
        label: 'Gastos por Mes',
        data: props.statsData?.por_mes?.map(i => i.total) || [],
        backgroundColor: colors.value.principal || '#F59E0B',
        borderRadius: 8
    }]
}));

const technicianChartData = computed(() => ({
    labels: props.statsData?.por_tecnico?.map(i => i.name.split(' ')[0]) || [],
    datasets: [{
        label: 'Por Técnico',
        data: props.statsData?.por_tecnico?.map(i => i.total) || [],
        backgroundColor: '#10B981',
        borderRadius: 8
    }]
}));

const closeModal = () => {
    showingModal.value = false;
    setTimeout(() => { selectedGasto.value = null; }, 200);
};

const showingFondeoModal = ref(false);
const fondeoForm = ref({
    user_id: null,
    monto: '',
    concepto: 'Asignación de efectivo',
    nota: '',
    fecha: new Date().toISOString().substr(0, 10),
});

const submitFondeo = () => {
    router.post(route('caja-chica.store'), {
        ...fondeoForm.value,
        tipo: 'ingreso',
        categoria: 'Fondeo'
    }, {
        onSuccess: () => {
            showingFondeoModal.value = false;
            fondeoForm.value.monto = '';
        }
    });
};
</script>

<template>
    <AppLayout title="Gastos Operativos">
        <Head title="Gastos Operativos" />

        <div class="min-h-screen bg-[var(--ui-surface)] text-slate-900 dark:text-slate-200 font-sans selection:bg-brand-500/30 pb-20" :style="cssVars">
            
            <!-- Floating Header -->
            <div class="sticky top-0 z-40 bg-white/80 dark:bg-slate-950/80 backdrop-blur-xl border-b border-slate-200 dark:border-white/5 px-6 py-4 mb-8">
                <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="flex items-center space-x-5">
                        <div class="p-3.5 rounded-2xl bg-gradient-to-br from-brand-500 to-brand-600 shadow-xl shadow-brand-500/20 transform hover:scale-105 transition-transform">
                            <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white m-0">Control de Gastos</h1>
                            <div class="flex items-center gap-3 mt-1">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-brand-500 animate-pulse"></span>
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Gestión Sonora</p>
                                </div>
                                <div v-if="$page.props.auth.user.is_admin" 
                                     class="flex p-1 bg-slate-100 dark:bg-white/5 rounded-xl border border-slate-200 dark:border-white/10">
                                    <button @click="misGastos = true; applyFilters()" 
                                            :class="misGastos ? 'bg-white dark:bg-slate-800 text-brand-600 shadow-sm' : 'text-slate-500'"
                                            class="px-3 py-1 rounded-lg text-[10px] font-black uppercase transition-all">Mis Gastos</button>
                                    <button @click="misGastos = false; applyFilters()" 
                                            :class="!misGastos ? 'bg-white dark:bg-slate-800 text-brand-600 shadow-sm' : 'text-slate-500'"
                                            class="px-3 py-1 rounded-lg text-[10px] font-black uppercase transition-all">Todos</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button @click="importarDesdeXml" 
                                class="group flex items-center gap-2.5 px-5 py-3 rounded-2xl bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 border border-slate-200 dark:border-white/10 text-slate-800 dark:text-white font-bold transition-all active:scale-95">
                            <svg class="w-4 h-4 text-orange-400 group-hover:rotate-12 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            <span class="text-sm">Importar XML</span>
                        </button>
                        
                        <button @click="showingFondeoModal = true" 
                                class="flex items-center gap-2.5 px-6 py-3 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold shadow-xl shadow-emerald-600/20 transition-all active:scale-95">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-sm">Entregar Efectivo</span>
                        </button>

                        <button @click="exportExcel" 
                                class="flex items-center gap-2.5 px-6 py-3 rounded-2xl bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 text-slate-700 dark:text-white font-bold transition-all active:scale-95 shadow-sm">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span class="text-sm">Excel</span>
                        </button>

                        <Link :href="route('gastos.create')" 
                               class="flex items-center gap-2.5 px-6 py-3 rounded-2xl bg-brand-600 hover:bg-brand-500 text-white font-bold shadow-xl shadow-brand-600/20 transition-all hover:shadow-xl hover:shadow-xl.5 active:scale-95">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span class="text-sm">Nuevo Gasto</span>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-12 space-y-8">
                
                <!-- Advanced Stats & Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-in slide-in-from-bottom-4 duration-700">
                    <div class="lg:col-span-4 space-y-4">
                        <div class="bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-white/5 rounded-[2rem] p-6 backdrop-blur-sm shadow-sm relative overflow-hidden group">
                            <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                                <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 20 20"><path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z" /><path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z" /></svg>
                            </div>
                            <div class="flex items-center justify-between mb-8">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Inversión Total</span>
                                <div class="w-8 h-8 rounded-lg bg-brand-500/10 flex items-center justify-center text-brand-600"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>
                            </div>
                            <p class="text-4xl font-black text-slate-900 dark:text-white leading-none tracking-tighter">{{ formatCurrency(stats.montoVisible) }}</p>
                            <p class="text-[10px] text-slate-500 mt-4 font-black uppercase tracking-widest flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> {{ stats.totalCount }} Transacciones Activas
                            </p>
                        </div>

                        <div class="bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-white/5 rounded-[2rem] p-6 backdrop-blur-sm shadow-sm flex items-center gap-6">
                            <div class="w-24 h-24 shrink-0">
                                <Doughnut :data="categoryChartData" :options="chartOptions" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Top Categorías</h4>
                                <div class="space-y-1.5">
                                    <div v-for="(cat, i) in statsData.por_categoria" :key="cat.nombre" class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <div class="w-1.5 h-1.5 rounded-full" :style="{ backgroundColor: ['#F59E0B', '#10B981', '#3B82F6', '#8B5CF6', '#EC4899'][i] }"></div>
                                            <span class="text-[10px] font-bold text-slate-600 dark:text-slate-300 truncate">{{ cat.nombre }}</span>
                                        </div>
                                        <span class="text-[10px] font-black text-slate-900 dark:text-white">{{ formatCurrency(cat.total) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-white/5 rounded-[2.5rem] p-8 backdrop-blur-sm shadow-sm flex flex-col">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest leading-tight">Tendencia Mensual</h3>
                                <div class="px-3 py-1 bg-brand-500/10 text-brand-600 rounded-lg text-[9px] font-black uppercase">Últimos 6 Meses</div>
                            </div>
                            <div class="flex-1 min-h-[150px]">
                                <Bar :data="monthlyChartData" :options="{ ...chartOptions, scales: { y: { display: false }, x: { grid: { display: false }, ticks: { font: { size: 10, weight: 'bold' }, color: '#64748b' } } } }" />
                            </div>
                        </div>

                        <div class="bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-white/5 rounded-[2.5rem] p-8 backdrop-blur-sm shadow-sm flex flex-col">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest leading-tight">Gastos por Técnico</h3>
                                <div class="px-3 py-1 bg-emerald-500/10 text-emerald-600 rounded-lg text-[9px] font-black uppercase">Top 5</div>
                            </div>
                            <div class="flex-1 min-h-[150px]">
                                <Bar :data="technicianChartData" :options="{ ...chartOptions, scales: { y: { display: false }, x: { grid: { display: false }, ticks: { font: { size: 10, weight: 'bold' }, color: '#64748b' } } } }" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Team Liquidity Section -->
                <div v-if="cajaChicaResumen?.length" class="animate-in slide-in-from-bottom-4 duration-700 delay-100">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.25em] ml-2">Liquidez de Equipo (Caja Chica)</h3>
                        <div class="h-px flex-1 bg-slate-200 dark:bg-white/5 ml-6"></div>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                        <div v-for="user in cajaChicaResumen" :key="user.id" 
                             class="bg-white dark:bg-slate-800/40 border rounded-[1.5rem] p-3 transition-all hover:scale-[1.03] hover:shadow-lg shadow-sm relative overflow-hidden group"
                             :class="user.id === $page.props.auth.user.id ? 'border-brand-500/50 bg-brand-500/5 ring-1 ring-brand-500/20' : 'border-slate-200 dark:border-white/5 hover:border-brand-500/30'">
                            <div v-if="user.id === $page.props.auth.user.id" class="absolute top-0 left-0 w-full h-1 bg-brand-500"></div>
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-brand-500 shrink-0 bg-slate-100 dark:bg-white/5 shadow-inner">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[9px] font-black uppercase truncate tracking-wide text-slate-500">{{ user.name.split(' ')[0] }}</p>
                                    <p class="text-[13px] font-black tracking-tight" :class="user.balance < 0 ? 'text-rose-500' : 'text-emerald-500'">{{ formatCurrency(user.balance) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters Toolbar -->
                <div class="bg-white dark:bg-slate-800/30 border border-slate-200 dark:border-white/5 rounded-3xl p-6 backdrop-blur-sm animate-in fade-in duration-700">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wide ml-1">Búsqueda</label>
                            <input type="text" v-model="search" @keyup.enter="applyFilters" placeholder="Número, descripción..." class="w-full bg-slate-100 dark:bg-slate-950/50 border-slate-200 dark:border-white/10 rounded-2xl px-4 py-4 text-sm outline-none focus:ring-2 focus:ring-brand-500/50" />
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wide ml-1">Categoría</label>
                            <select v-model="categoriaId" @change="applyFilters" class="w-full bg-slate-100 dark:bg-slate-950/50 border-slate-200 dark:border-white/10 rounded-2xl px-5 py-4 text-sm outline-none">
                                <option value="">Todas</option>
                                <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.nombre }}</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wide ml-1">Estado</label>
                            <select v-model="estado" @change="applyFilters" class="w-full bg-slate-100 dark:bg-slate-950/50 border-slate-200 dark:border-white/10 rounded-2xl px-5 py-4 text-sm outline-none">
                                <option value="">Cualquiera</option>
                                <option value="procesada">Procesada</option>
                                <option value="cancelada">Cancelada</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wide ml-1">Proyecto</label>
                            <select v-model="proyectoId" @change="applyFilters" class="w-full bg-slate-100 dark:bg-slate-950/50 border-slate-200 dark:border-white/10 rounded-2xl px-5 py-4 text-sm outline-none">
                                <option value="">Sin proyecto</option>
                                <option v-for="p in proyectos" :key="p.id" :value="p.id">{{ p.nombre }}</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wide ml-1">Desde</label>
                            <input type="date" v-model="fechaDesde" @change="applyFilters" class="w-full bg-slate-100 dark:bg-slate-950/50 border-slate-200 dark:border-white/10 rounded-2xl px-4 py-4 text-xs outline-none focus:ring-2 focus:ring-brand-500/50 dark:text-slate-300" />
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wide ml-1">Hasta</label>
                            <input type="date" v-model="fechaHasta" @change="applyFilters" class="w-full bg-slate-100 dark:bg-slate-950/50 border-slate-200 dark:border-white/10 rounded-2xl px-4 py-4 text-xs outline-none focus:ring-2 focus:ring-brand-500/50 dark:text-slate-300" />
                        </div>
                        <div class="lg:col-span-1 flex gap-2">
                            <button @click="applyFilters" class="flex-1 py-4 rounded-2xl bg-slate-800 text-white font-bold text-sm transition-all hover:bg-slate-700">Filtrar</button>
                            <button @click="clearFilters" class="p-4 rounded-2xl bg-slate-100 dark:bg-white/5 text-slate-500 transition-all hover:bg-slate-200"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                        </div>
                    </div>
                </div>

                <!-- Main Datatable -->
                <div class="bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-white/5 rounded-[2.5rem] overflow-hidden shadow-2xl">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-white/5">
                                    <th class="px-6 py-5 text-[10px] font-black text-slate-500 uppercase tracking-widest text-left">Referencia</th>
                                    <th class="px-6 py-5 text-[10px] font-black text-slate-500 uppercase tracking-widest text-left">Fecha</th>
                                    <th class="px-6 py-5 text-[10px] font-black text-slate-500 uppercase tracking-widest text-left">Categoría / Proyecto</th>
                                    <th class="px-6 py-5 text-[10px] font-black text-slate-500 uppercase tracking-widest text-left">Descripción</th>
                                    <th class="px-6 py-5 text-[10px] font-black text-slate-500 uppercase tracking-widest text-right">Inversión</th>
                                    <th class="px-6 py-5 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Estado</th>
                                    <th class="px-6 py-5 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                                <tr v-for="gasto in gastos.data" :key="gasto.id" class="group hover:bg-slate-50 dark:hover:bg-white/5 transition-all">
                                    <td class="px-6 py-6">
                                        <button @click="showGasto(gasto)" class="text-brand-600 dark:text-brand-400 font-bold font-mono text-sm hover:underline">{{ gasto.numero_compra }}</button>
                                    </td>
                                    <td class="px-6 py-6">
                                        <div class="text-slate-900 dark:text-white font-bold text-sm">{{ formatDate(gasto.fecha_compra) }}</div>
                                    </td>
                                    <td class="px-6 py-6">
                                        <div class="text-slate-700 dark:text-slate-200 font-bold text-xs uppercase">{{ gasto.categoria_gasto?.nombre || '-' }}</div>
                                        <div v-if="gasto.proyecto" class="mt-1 text-[10px] font-black text-brand-600 uppercase">{{ gasto.proyecto.nombre }}</div>
                                    </td>
                                    <td class="px-6 py-6 max-w-sm truncate text-sm italic text-slate-600 dark:text-slate-400">"{{ gasto.notas || '-' }}"</td>
                                    <td class="px-6 py-6 text-right">
                                        <div class="text-lg font-black text-slate-900 dark:text-white">{{ formatCurrency(gasto.total) }}</div>
                                        <div class="text-[10px] text-slate-500 uppercase font-black">{{ gasto.metodo_pago }}</div>
                                    </td>
                                    <td class="px-6 py-6 text-center">
                                        <span :class="gasto.estado?.toLowerCase() === 'procesada' ? 'bg-emerald-500/10 text-emerald-600' : 'bg-rose-500/10 text-rose-600'" class="px-3 py-1 text-[10px] font-black rounded-xl border uppercase tracking-widest">
                                            {{ gasto.estado }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-6">
                                        <div class="flex items-center justify-center gap-2">
                                            <button @click="showGasto(gasto)" class="p-2.5 rounded-xl bg-indigo-500/10 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></button>
                                            <Link v-if="gasto.estado === 'procesada'" :href="route('gastos.edit', gasto.id)" class="p-2.5 rounded-xl bg-brand-500/10 text-brand-600 hover:bg-brand-500 hover:text-white transition-all"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></Link>
                                            <button @click="deleteGasto(gasto.id)" class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 hover:bg-rose-600 hover:text-white transition-all"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <ModalGasto :show="showingModal" :gasto="selectedGasto" @close="closeModal" />
        <ImportXmlGastoModal :show="showImportXmlModal" @close="showImportXmlModal = false" @import="handleXmlImport" />

        <div v-if="showingFondeoModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] w-full max-w-md border border-slate-200 dark:border-white/10 shadow-2xl p-8">
                <h2 class="text-2xl font-black text-slate-900 dark:text-white mb-6">Entregar Efectivo</h2>
                <div class="space-y-4">
                    <select v-model="fondeoForm.user_id" class="w-full bg-slate-100 dark:bg-slate-800 rounded-2xl p-4 text-sm font-bold outline-none">
                        <option :value="null">Seleccionar Usuario...</option>
                        <option v-for="u in tecnicos" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                    <input type="number" v-model="fondeoForm.monto" placeholder="Monto $0.00" class="w-full bg-slate-100 dark:bg-slate-800 rounded-2xl p-4 text-lg font-black text-emerald-500 outline-none" />
                    <input type="text" v-model="fondeoForm.concepto" placeholder="Concepto..." class="w-full bg-slate-100 dark:bg-slate-800 rounded-2xl p-4 text-sm outline-none" />
                </div>
                <div class="grid grid-cols-2 gap-4 mt-8">
                    <button @click="showingFondeoModal = false" class="py-4 rounded-2xl bg-slate-100 dark:bg-white/5 text-slate-500 font-bold">Cancelar</button>
                    <button @click="submitFondeo" class="py-4 rounded-2xl bg-emerald-600 text-white font-bold shadow-xl shadow-emerald-600/20">Confirmar</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
.animate-in { animation-fill-mode: both; }
.slide-in-from-bottom-4 { animation-name: fadeInUp; }
</style>
