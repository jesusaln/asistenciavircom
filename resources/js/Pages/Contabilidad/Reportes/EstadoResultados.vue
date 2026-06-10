<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    reportData: Object,
    filters: Object
});

const mes = ref(props.filters.mes);
const anio = ref(props.filters.anio);

const updateReport = () => {
    aiContent.value = ''; // Limpiar analisis al cambiar de periodo
    router.get(route('contabilidad.reportes.estado-resultados'), {
        mes: mes.value,
        anio: anio.value
    }, { preserveState: true });
};

const exportPdf = () => {
    window.open(route('contabilidad.reportes.estado-resultados.pdf', {
        mes: mes.value,
        anio: anio.value
    }), '_blank');
};

const formatCurrency = (n) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(n);
const formatPercent = (n) => new Intl.NumberFormat('es-MX', { style: 'percent', minimumFractionDigits: 1, maximumFractionDigits: 1 }).format(n / 100);

const meses = [
    { id: '01', nombre: 'Enero' }, { id: '02', nombre: 'Febrero' }, { id: '03', nombre: 'Marzo' },
    { id: '04', nombre: 'Abril' }, { id: '05', nombre: 'Mayo' }, { id: '06', nombre: 'Junio' },
    { id: '07', nombre: 'Julio' }, { id: '08', nombre: 'Agosto' }, { id: '09', nombre: 'Septiembre' },
    { id: '10', nombre: 'Octubre' }, { id: '11', nombre: 'Noviembre' }, { id: '12', nombre: 'Diciembre' }
];
const anios = ['2024', '2025', '2026'];

// IA Analisis
const aiModalOpen = ref(false);
const aiLoading = ref(false);
const aiContent = ref('');
const aiCached = ref(true);
const aiError = ref('');

const abrirAnalisisAi = async (refresh = false) => {
    aiModalOpen.value = true;
    if (!refresh && aiContent.value) return;
    
    aiLoading.value = true;
    aiError.value = '';
    try {
        const res = await axios.post(route('contabilidad.api.estado-resultados-ai'), {
            mes: mes.value,
            anio: anio.value,
            refresh: refresh
        });
        if (res.data.success) {
            aiContent.value = res.data.analisis;
            aiCached.value = res.data.cached;
        } else {
            aiError.value = res.data.message || 'Error al obtener el análisis.';
        }
    } catch (err) {
        aiError.value = err.response?.data?.message || 'Error de conexión con el servidor.';
    } finally {
        aiLoading.value = false;
    }
};

const formatMarkdown = (text) => {
    if (!text) return '';
    return text
        .replace(/### (.*)/g, '<h3 class="text-xl font-black mt-8 mb-4 text-indigo-400 border-b border-indigo-500/20 pb-2 flex items-center gap-2"><svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>$1</h3>')
        .replace(/\*\*(.*?)\*\*/g, '<strong class="font-black text-white">$1</strong>')
        .replace(/\*(.*?)\*/g, '<em class="italic text-slate-300">$1</em>')
        .replace(/- (.*)/g, '<li class="ml-6 mb-2.5 flex items-start gap-2.5 text-slate-200"><span class="w-1.5 h-1.5 bg-indigo-500 rounded-full mt-2.5 shrink-0"></span><span>$1</span></li>')
        .replace(/\n\n/g, '<br/><br/>')
        .replace(/\n/g, '<br/>');
};
</script>

<template>
    <AppLayout title="Estado de Resultados">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-black text-2xl text-slate-800 dark:text-white tracking-tight">
                    Estado de <span class="text-indigo-600 dark:text-indigo-400">Resultados</span>
                </h2>
                <div class="flex gap-3">
                    <button @click="abrirAnalisisAi(false)" class="px-5 py-2.5 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700 text-white text-xs font-black uppercase tracking-widest rounded-2xl transition-all shadow-lg shadow-indigo-600/20 flex items-center gap-2">
                        <svg class="w-4 h-4 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        Análisis AI
                    </button>
                    <button @click="exportPdf" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-black uppercase tracking-widest rounded-2xl transition-all shadow-lg shadow-rose-600/20 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        PDF
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12 bg-slate-50 dark:bg-slate-950 min-h-screen">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Filtros y Resumen de Márgenes -->
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Filtros -->
                    <div class="bg-white dark:bg-slate-900 p-6 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col justify-center gap-4">
                        <select v-model="mes" @change="updateReport" class="bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-sm font-bold focus:ring-indigo-500">
                            <option v-for="m in meses" :key="m.id" :value="m.id">{{ m.nombre }}</option>
                        </select>
                        <select v-model="anio" @change="updateReport" class="bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-sm font-bold focus:ring-indigo-500">
                            <option v-for="a in anios" :key="a" :value="a">{{ a }}</option>
                        </select>
                    </div>

                    <!-- Margen Bruto -->
                    <div class="bg-white dark:bg-slate-900 p-6 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-slate-800">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Margen Bruto</p>
                        <h4 class="text-2xl font-black text-slate-800 dark:text-white">{{ formatPercent(reportData.resumen.margen_bruto) }}</h4>
                        <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full mt-3 overflow-hidden">
                            <div class="bg-emerald-500 h-full rounded-full" :style="{ width: reportData.resumen.margen_bruto + '%' }"></div>
                        </div>
                    </div>

                    <!-- Margen Operativo -->
                    <div class="bg-white dark:bg-slate-900 p-6 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-slate-800">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Margen Operativo</p>
                        <h4 class="text-2xl font-black text-slate-800 dark:text-white">{{ formatPercent(reportData.resumen.margen_operativo) }}</h4>
                        <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full mt-3 overflow-hidden">
                            <div class="bg-indigo-500 h-full rounded-full" :style="{ width: Math.max(0, reportData.resumen.margen_operativo) + '%' }"></div>
                        </div>
                    </div>

                    <!-- Margen Neto -->
                    <div class="bg-white dark:bg-slate-900 p-6 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-slate-800">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Margen Neto Final</p>
                        <h4 class="text-2xl font-black" :class="reportData.resumen.utilidad_neta >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                            {{ formatPercent(reportData.resumen.margen_neto) }}
                        </h4>
                        <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full mt-3 overflow-hidden">
                            <div :class="reportData.resumen.utilidad_neta >= 0 ? 'bg-emerald-500' : 'bg-rose-500'" class="h-full rounded-full" :style="{ width: Math.max(0, Math.min(100, reportData.resumen.margen_neto)) + '%' }"></div>
                        </div>
                    </div>
                </div>

                <!-- Detalle del Reporte NIF B-3 -->
                <div class="bg-white dark:bg-slate-900 shadow-2xl rounded-[3rem] overflow-hidden border border-slate-100 dark:border-slate-800">
                    <div class="p-10 border-b border-slate-50 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h3 class="text-xl font-black text-slate-800 dark:text-white">Estado de Resultados Integral</h3>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Norma de Información Financiera NIF B-3</p>
                        </div>
                        <div class="text-sm text-slate-500 font-bold bg-white dark:bg-slate-800 px-5 py-2.5 rounded-2xl border border-slate-100 dark:border-slate-700/50 shadow-sm">
                            Periodo: <span class="text-indigo-600 dark:text-indigo-400 uppercase">{{ meses.find(m => m.id === mes)?.nombre }} {{ anio }}</span>
                        </div>
                    </div>

                    <div class="p-10 space-y-12">
                        <!-- INGRESOS Y COSTOS -->
                        <div v-for="sec in reportData.secciones.filter(s => ['ingresos', 'costos'].includes(s.key))" :key="sec.key">
                            <div v-if="sec.items.length > 0">
                                <div class="flex justify-between items-end mb-6 border-b border-slate-100 dark:border-slate-800 pb-2">
                                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.3em]">{{ sec.titulo }}</h3>
                                    <span class="text-[10px] font-bold text-slate-300 uppercase tracking-widest">% VENTAS</span>
                                </div>
                                
                                <div class="space-y-3">
                                    <div v-for="item in sec.items" :key="item.codigo" 
                                         class="group flex justify-between items-center px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800/50 rounded-2xl transition-all"
                                         :class="{'opacity-60 scale-95 ml-4': item.nivel > 2, 'font-bold': item.nivel === 2}">
                                        <div class="flex items-center gap-4">
                                            <span class="text-[9px] font-mono text-slate-400 group-hover:text-indigo-500 transition-colors">{{ item.codigo }}</span>
                                            <span class="text-sm text-slate-700 dark:text-slate-300">{{ item.nombre }}</span>
                                        </div>
                                        <div class="flex items-center gap-12">
                                            <span class="text-sm font-bold text-slate-800 dark:text-white">{{ formatCurrency(item.monto) }}</span>
                                            <span class="text-[10px] font-black text-slate-400 w-12 text-right">{{ item.porcentaje.toFixed(1) }}%</span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex justify-between items-center bg-slate-50 dark:bg-slate-800/40 p-5 rounded-[1.5rem] mt-4 border border-slate-100 dark:border-slate-800">
                                        <span class="text-xs font-black text-slate-500 uppercase tracking-widest">Total {{ sec.titulo }}</span>
                                        <div class="flex items-center gap-12">
                                            <span class="text-lg font-black text-slate-900 dark:text-white">{{ formatCurrency(sec.total) }}</span>
                                            <span class="text-xs font-black text-indigo-600 dark:text-indigo-400 w-12 text-right">
                                                {{ reportData.resumen.ventas_netas > 0 ? ((sec.total / reportData.resumen.ventas_netas) * 100).toFixed(1) : '0.0' }}%
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PASO 1: UTILIDAD BRUTA -->
                        <div class="my-8 p-6 bg-gradient-to-r from-slate-900 to-indigo-950 rounded-[2.5rem] shadow-xl text-white flex flex-col md:flex-row justify-between items-center gap-4 border border-indigo-500/20">
                            <div>
                                <span class="text-[10px] font-black uppercase tracking-[0.3em] opacity-60 text-indigo-300">Paso 1 del Ejercicio</span>
                                <h3 class="text-2xl font-black mt-1">Utilidad Bruta</h3>
                            </div>
                            <div class="flex items-center gap-8">
                                <div class="text-right">
                                    <span class="text-3xl font-black text-emerald-400">{{ formatCurrency(reportData.resumen.utilidad_bruta) }}</span>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Margen Bruto: {{ formatPercent(reportData.resumen.margen_bruto) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- GASTOS OPERATIVOS -->
                        <div v-for="sec in reportData.secciones.filter(s => ['gastos_venta', 'gastos_admin'].includes(s.key))" :key="sec.key">
                            <div v-if="sec.items.length > 0">
                                <div class="flex justify-between items-end mb-6 border-b border-slate-100 dark:border-slate-800 pb-2">
                                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.3em]">{{ sec.titulo }}</h3>
                                    <span class="text-[10px] font-bold text-slate-300 uppercase tracking-widest">% VENTAS</span>
                                </div>
                                
                                <div class="space-y-3">
                                    <div v-for="item in sec.items" :key="item.codigo" 
                                         class="group flex justify-between items-center px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800/50 rounded-2xl transition-all"
                                         :class="{'opacity-60 scale-95 ml-4': item.nivel > 2, 'font-bold': item.nivel === 2}">
                                        <div class="flex items-center gap-4">
                                            <span class="text-[9px] font-mono text-slate-400 group-hover:text-indigo-500 transition-colors">{{ item.codigo }}</span>
                                            <span class="text-sm text-slate-700 dark:text-slate-300">{{ item.nombre }}</span>
                                        </div>
                                        <div class="flex items-center gap-12">
                                            <span class="text-sm font-bold text-slate-800 dark:text-white">{{ formatCurrency(item.monto) }}</span>
                                            <span class="text-[10px] font-black text-slate-400 w-12 text-right">{{ item.porcentaje.toFixed(1) }}%</span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex justify-between items-center bg-slate-50 dark:bg-slate-800/40 p-5 rounded-[1.5rem] mt-4 border border-slate-100 dark:border-slate-800">
                                        <span class="text-xs font-black text-slate-500 uppercase tracking-widest">Total {{ sec.titulo }}</span>
                                        <div class="flex items-center gap-12">
                                            <span class="text-lg font-black text-slate-900 dark:text-white">{{ formatCurrency(sec.total) }}</span>
                                            <span class="text-xs font-black text-indigo-600 dark:text-indigo-400 w-12 text-right">
                                                {{ reportData.resumen.ventas_netas > 0 ? ((sec.total / reportData.resumen.ventas_netas) * 100).toFixed(1) : '0.0' }}%
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PASO 2: UTILIDAD OPERATIVA (EBIT) -->
                        <div class="my-8 p-6 bg-gradient-to-r from-slate-900 to-indigo-950 rounded-[2.5rem] shadow-xl text-white flex flex-col md:flex-row justify-between items-center gap-4 border border-indigo-500/20">
                            <div>
                                <span class="text-[10px] font-black uppercase tracking-[0.3em] opacity-60 text-indigo-300">Paso 2 del Ejercicio</span>
                                <h3 class="text-2xl font-black mt-1">Utilidad de Operación (EBIT)</h3>
                            </div>
                            <div class="flex items-center gap-8">
                                <div class="text-right">
                                    <span class="text-3xl font-black" :class="reportData.resumen.utilidad_operacion >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                                        {{ formatCurrency(reportData.resumen.utilidad_operacion) }}
                                    </span>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Margen Operativo: {{ formatPercent(reportData.resumen.margen_operativo) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- RIF Y OTROS -->
                        <div v-for="sec in reportData.secciones.filter(s => ['gastos_fin', 'otros'].includes(s.key))" :key="sec.key">
                            <div v-if="sec.items.length > 0">
                                <div class="flex justify-between items-end mb-6 border-b border-slate-100 dark:border-slate-800 pb-2">
                                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.3em]">{{ sec.titulo }}</h3>
                                    <span class="text-[10px] font-bold text-slate-300 uppercase tracking-widest">% VENTAS</span>
                                </div>
                                
                                <div class="space-y-3">
                                    <div v-for="item in sec.items" :key="item.codigo" 
                                         class="group flex justify-between items-center px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800/50 rounded-2xl transition-all"
                                         :class="{'opacity-60 scale-95 ml-4': item.nivel > 2, 'font-bold': item.nivel === 2}">
                                        <div class="flex items-center gap-4">
                                            <span class="text-[9px] font-mono text-slate-400 group-hover:text-indigo-500 transition-colors">{{ item.codigo }}</span>
                                            <span class="text-sm text-slate-700 dark:text-slate-300">{{ item.nombre }}</span>
                                        </div>
                                        <div class="flex items-center gap-12">
                                            <span class="text-sm font-bold text-slate-800 dark:text-white">{{ formatCurrency(item.monto) }}</span>
                                            <span class="text-[10px] font-black text-slate-400 w-12 text-right">{{ item.porcentaje.toFixed(1) }}%</span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex justify-between items-center bg-slate-50 dark:bg-slate-800/40 p-5 rounded-[1.5rem] mt-4 border border-slate-100 dark:border-slate-800">
                                        <span class="text-xs font-black text-slate-500 uppercase tracking-widest">Total {{ sec.titulo }}</span>
                                        <div class="flex items-center gap-12">
                                            <span class="text-lg font-black text-slate-900 dark:text-white">{{ formatCurrency(sec.total) }}</span>
                                            <span class="text-xs font-black text-indigo-600 dark:text-indigo-400 w-12 text-right">
                                                {{ reportData.resumen.ventas_netas > 0 ? ((sec.total / reportData.resumen.ventas_netas) * 100).toFixed(1) : '0.0' }}%
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PASO 3: UTILIDAD ANTES DE IMPUESTOS (EBT) -->
                        <div class="my-8 p-6 bg-gradient-to-r from-slate-900 to-indigo-950 rounded-[2.5rem] shadow-xl text-white flex flex-col md:flex-row justify-between items-center gap-4 border border-indigo-500/20">
                            <div>
                                <span class="text-[10px] font-black uppercase tracking-[0.3em] opacity-60 text-indigo-300">Paso 3 del Ejercicio</span>
                                <h3 class="text-2xl font-black mt-1">Utilidad antes de Impuestos (UAI)</h3>
                            </div>
                            <div class="flex items-center gap-8">
                                <div class="text-right">
                                    <span class="text-3xl font-black" :class="reportData.resumen.utilidad_antes_impuestos >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                                        {{ formatCurrency(reportData.resumen.utilidad_antes_impuestos) }}
                                    </span>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Margen antes de Impuestos: {{ formatPercent(reportData.resumen.margen_antes_impuestos) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- IMPUESTOS -->
                        <div v-for="sec in reportData.secciones.filter(s => s.key === 'impuestos')" :key="sec.key">
                            <div v-if="sec.items.length > 0">
                                <div class="flex justify-between items-end mb-6 border-b border-slate-100 dark:border-slate-800 pb-2">
                                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.3em]">{{ sec.titulo }}</h3>
                                    <span class="text-[10px] font-bold text-slate-300 uppercase tracking-widest">% VENTAS</span>
                                </div>
                                
                                <div class="space-y-3">
                                    <div v-for="item in sec.items" :key="item.codigo" 
                                         class="group flex justify-between items-center px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800/50 rounded-2xl transition-all font-bold">
                                        <div class="flex items-center gap-4">
                                            <span class="text-[9px] font-mono text-slate-400 group-hover:text-indigo-500 transition-colors">{{ item.codigo }}</span>
                                            <span class="text-sm text-slate-700 dark:text-slate-300">{{ item.nombre }}</span>
                                        </div>
                                        <div class="flex items-center gap-12">
                                            <span class="text-sm font-bold text-slate-800 dark:text-white">{{ formatCurrency(item.monto) }}</span>
                                            <span class="text-[10px] font-black text-slate-400 w-12 text-right">{{ item.porcentaje.toFixed(1) }}%</span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex justify-between items-center bg-slate-50 dark:bg-slate-800/40 p-5 rounded-[1.5rem] mt-4 border border-slate-100 dark:border-slate-800">
                                        <span class="text-xs font-black text-slate-500 uppercase tracking-widest">Total Impuestos del Ejercicio</span>
                                        <div class="flex items-center gap-12">
                                            <span class="text-lg font-black text-slate-900 dark:text-white">{{ formatCurrency(sec.total) }}</span>
                                            <span class="text-xs font-black text-indigo-600 dark:text-indigo-400 w-12 text-right">
                                                {{ reportData.resumen.ventas_netas > 0 ? ((sec.total / reportData.resumen.ventas_netas) * 100).toFixed(1) : '0.0' }}%
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TOTALES FINALES -->
                        <div class="pt-8 border-t-2 border-dashed border-slate-100 dark:border-slate-800">
                            <div class="flex justify-between items-center p-10 rounded-[3rem] shadow-2xl transition-all"
                                 :class="reportData.resumen.utilidad_neta >= 0 ? 'bg-slate-900 text-white shadow-emerald-500/10' : 'bg-rose-600 text-white shadow-rose-500/20'">
                                <div>
                                    <span class="text-[10px] font-black uppercase tracking-[0.4em] opacity-60">Utilidad Neta del Ejercicio</span>
                                    <h2 class="text-5xl font-black mt-2">{{ formatCurrency(reportData.resumen.utilidad_neta) }}</h2>
                                    <p class="text-xs mt-4 font-bold opacity-80 uppercase tracking-widest">
                                        Margen de Rentabilidad Neta Final: {{ formatPercent(reportData.resumen.margen_neto) }}
                                    </p>
                                </div>
                                <div class="hidden md:block">
                                    <svg v-if="reportData.resumen.utilidad_neta >= 0" class="w-24 h-24 text-emerald-400 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                                    <svg v-else class="w-24 h-24 text-white opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" /></svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MODAL DE EXPLICACION AI -->
                <div v-if="aiModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-md transition-all">
                    <div class="bg-slate-950 border border-slate-800 rounded-[3rem] shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden text-white">
                        <div class="p-8 border-b border-slate-800 flex justify-between items-center bg-slate-900/50">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-violet-600 to-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-500/30">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 01-2 2h-1a2 2 0 01-2-2v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-black">Asesor AI del Estado de Resultados</h3>
                                    <p class="text-xs text-indigo-400 font-bold uppercase tracking-wider">Periodo: {{ meses.find(m => m.id === mes)?.nombre }} {{ anio }}</p>
                                </div>
                            </div>
                            <button @click="aiModalOpen = false" class="w-10 h-10 rounded-full bg-slate-800 hover:bg-slate-700 flex items-center justify-center text-slate-400 hover:text-white transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        
                        <div class="p-8 overflow-y-auto flex-1 font-sans">
                            <!-- Loading State -->
                            <div v-if="aiLoading" class="py-20 flex flex-col items-center justify-center text-center">
                                <div class="w-16 h-16 border-4 border-indigo-500/30 border-t-indigo-500 rounded-full animate-spin mb-6"></div>
                                <h4 class="text-lg font-black tracking-wide mb-2 animate-pulse text-indigo-400">Analizando rubros y evaluando rentabilidad...</h4>
                                <p class="text-xs text-slate-400 max-w-sm">La Inteligencia Artificial está auditando ingresos, costos y gastos fijos para brindarte un diagnóstico preciso en segundos.</p>
                            </div>
                            
                            <!-- Error State -->
                            <div v-else-if="aiError" class="py-16 text-center">
                                <div class="w-16 h-16 bg-rose-500/10 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-4 border border-rose-500/20">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <h4 class="text-lg font-black text-rose-400 mb-2">No se pudo generar el análisis</h4>
                                <p class="text-xs text-slate-400 mb-6">{{ aiError }}</p>
                                <button @click="abrirAnalisisAi(true)" class="px-6 py-2.5 bg-slate-800 hover:bg-slate-700 text-white rounded-2xl text-xs font-bold transition-all">Reintentar</button>
                            </div>
                            
                            <!-- Content State -->
                            <div v-else-if="aiContent" class="space-y-6">
                                <div v-if="aiCached" class="bg-indigo-500/10 border border-indigo-500/20 rounded-2xl p-4 flex items-center justify-between text-xs text-indigo-300">
                                    <div class="flex items-center gap-2 font-bold">
                                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Mostrando análisis guardado en memoria instantánea (Cero consumo de tokens).
                                    </div>
                                    <button @click="abrirAnalisisAi(true)" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-black rounded-xl transition-all flex items-center gap-1.5 shadow-lg shadow-indigo-600/30">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        Actualizar Análisis AI
                                    </button>
                                </div>
                                <div v-else class="bg-emerald-500/10 border border-emerald-500/20 rounded-2xl p-3 text-center text-xs text-emerald-400 font-bold">
                                    ✨ Análisis financiero fresco generado exitosamente por Gemini AI.
                                </div>

                                <div class="prose prose-invert max-w-none text-slate-200 leading-relaxed font-sans" v-html="formatMarkdown(aiContent)"></div>
                            </div>
                        </div>

                        <div class="p-6 border-t border-slate-800 bg-slate-900/50 flex justify-end gap-3">
                            <button @click="aiModalOpen = false" class="px-6 py-2.5 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold rounded-2xl transition-colors">Cerrar Panel</button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.font-mono {
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
}
</style>
