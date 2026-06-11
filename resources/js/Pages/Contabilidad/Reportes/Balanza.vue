<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    reportData: Array,
    totales: Object,
    filters: Object
});

const mes = ref(props.filters.mes);
const anio = ref(props.filters.anio);

const updateReport = () => {
    aiContent.value = ''; // Limpiar analisis al cambiar de periodo
    router.get(route('contabilidad.reportes.balanza'), {
        mes: mes.value,
        anio: anio.value
    }, { preserveState: true });
};

const exportPdf = () => {
    window.open(route('contabilidad.reportes.balanza.pdf', { mes: mes.value, anio: anio.value }), '_blank');
};

const formatCurrency = (n) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(n);

const handlePrint = () => {
    window.print();
};

const meses = [
    { id: '01', nombre: 'Enero' }, { id: '02', nombre: 'Febrero' }, { id: '03', nombre: 'Marzo' },
    { id: '04', nombre: 'Abril' }, { id: '05', nombre: 'Mayo' }, { id: '06', nombre: 'Junio' },
    { id: '07', nombre: 'Julio' }, { id: '08', nombre: 'Agosto' }, { id: '09', nombre: 'Septiembre' },
    { id: '10', nombre: 'Octubre' }, { id: '11', nombre: 'Noviembre' }, { id: '12', nombre: 'Diciembre' }
];

const anios = ['2024', '2025', '2026'];

const nombreMesSeleccionado = computed(() => {
    const found = meses.find(m => m.id === mes.value);
    return found ? found.nombre : '';
});

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
        const res = await axios.post(route('contabilidad.api.balanza-ai'), {
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
    <AppLayout title="Balanza de Comprobación">
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="font-black text-2xl text-slate-800 dark:text-white tracking-tight">
                        Balanza de <span class="text-indigo-600 dark:text-indigo-400">Comprobación</span>
                    </h2>
                    <p class="text-xs text-slate-500 uppercase font-bold tracking-widest mt-0.5">Normativa Contable NIF y Anexo 24 SAT (DOF México)</p>
                </div>
            </div>
        </template>

        <div class="py-12 bg-slate-50 dark:bg-slate-950 min-h-screen">
            <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-12">
                <div class="bg-white dark:bg-slate-900 shadow-2xl rounded-[3rem] overflow-hidden border border-slate-100 dark:border-slate-800 p-8 sm:p-10">
                    
                    <!-- Filtros y Opciones -->
                    <div class="flex flex-wrap gap-4 mb-8 bg-slate-50 dark:bg-slate-800/50 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 items-end justify-between">
                        <div class="flex flex-wrap items-center gap-4">
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1 ml-1">Mes</label>
                                <select v-model="mes" @change="updateReport" class="bg-white dark:bg-slate-800 border-none rounded-2xl font-bold text-sm shadow-sm focus:ring-indigo-500 min-w-[120px]">
                                    <option v-for="m in meses" :key="m.id" :value="m.id">{{ m.nombre }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1 ml-1">Año</label>
                                <select v-model="anio" @change="updateReport" class="bg-white dark:bg-slate-800 border-none rounded-2xl font-bold text-sm shadow-sm focus:ring-indigo-500 min-w-[100px]">
                                    <option v-for="a in anios" :key="a" :value="a">{{ a }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button @click="abrirAnalisisAi(false)" class="px-5 py-2.5 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700 text-white text-xs font-bold uppercase tracking-wider rounded-2xl transition-all shadow-lg shadow-indigo-600/20 flex items-center gap-2">
                                <svg class="w-4 h-4 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                Análisis AI
                            </button>
                            <button @click="handlePrint" class="px-5 py-2.5 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-2xl font-bold text-xs uppercase tracking-wider hover:bg-slate-300 dark:hover:bg-slate-700 transition-all flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                Imprimir
                            </button>
                            <button @click="exportPdf" class="px-5 py-2.5 bg-rose-600 text-white rounded-2xl font-bold text-xs uppercase tracking-wider hover:bg-rose-500 transition-all shadow-lg shadow-rose-600/20 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                Exportar PDF
                            </button>
                        </div>
                    </div>

                    <!-- Tabla de Comprobación -->
                    <div class="overflow-x-auto rounded-[2rem] border border-slate-100 dark:border-slate-800 shadow-sm">
                        <table class="w-full text-xs text-left font-sans">
                            <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-400 border-b border-slate-100 dark:border-slate-800">
                                <tr>
                                    <th class="px-5 py-4 font-black uppercase tracking-wider text-[10px]">Código</th>
                                    <th class="px-5 py-4 font-black uppercase tracking-wider text-[10px]">Cuenta Contable</th>
                                    <th class="px-5 py-4 font-black uppercase tracking-wider text-[10px] text-right">S. Inicial Deudor</th>
                                    <th class="px-5 py-4 font-black uppercase tracking-wider text-[10px] text-right">S. Inicial Acreedor</th>
                                    <th class="px-5 py-4 font-black uppercase tracking-wider text-[10px] text-right">Cargos (Debe)</th>
                                    <th class="px-5 py-4 font-black uppercase tracking-wider text-[10px] text-right">Abonos (Haber)</th>
                                    <th class="px-5 py-4 font-black uppercase tracking-wider text-[10px] text-right">S. Final Deudor</th>
                                    <th class="px-5 py-4 font-black uppercase tracking-wider text-[10px] text-right">S. Final Acreedor</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 dark:divide-slate-800/60">
                                <tr v-for="cta in reportData" :key="cta.codigo" 
                                    class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors"
                                    :class="{'bg-slate-50/80 dark:bg-slate-800/30 font-bold': cta.nivel === 1, 'opacity-90': cta.nivel > 1}">
                                    <td class="px-5 py-3.5 font-mono text-slate-500 dark:text-slate-400">{{ cta.codigo }}</td>
                                    <td class="px-5 py-3.5 text-slate-800 dark:text-slate-200" :class="{'pl-8': cta.nivel === 2, 'pl-12 italic text-slate-600 dark:text-slate-400': cta.nivel === 3}">
                                        <div class="flex items-center gap-2">
                                            <span v-if="cta.nivel === 1" class="w-1.5 h-1.5 rounded-full bg-indigo-500 shrink-0"></span>
                                            <span>{{ cta.nombre }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-mono" :class="cta.naturaleza === 'deudora' && Math.abs(cta.saldo_inicial) > 0.009 ? (cta.saldo_inicial < 0 ? 'text-rose-500' : 'text-slate-800 dark:text-slate-200') : 'text-slate-300 dark:text-slate-600'">
                                        {{ cta.naturaleza === 'deudora' && Math.abs(cta.saldo_inicial) > 0.009 ? formatCurrency(cta.saldo_inicial) : '-' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-mono" :class="cta.naturaleza === 'acreedora' && Math.abs(cta.saldo_inicial) > 0.009 ? (cta.saldo_inicial < 0 ? 'text-rose-500' : 'text-slate-800 dark:text-slate-200') : 'text-slate-300 dark:text-slate-600'">
                                        {{ cta.naturaleza === 'acreedora' && Math.abs(cta.saldo_inicial) > 0.009 ? formatCurrency(cta.saldo_inicial) : '-' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-mono text-emerald-600 dark:text-emerald-400 font-medium">{{ cta.cargos > 0 ? formatCurrency(cta.cargos) : '-' }}</td>
                                    <td class="px-5 py-3.5 text-right font-mono text-rose-500 dark:text-rose-400 font-medium">{{ cta.abonos > 0 ? formatCurrency(cta.abonos) : '-' }}</td>
                                    <td class="px-5 py-3.5 text-right font-mono font-bold" :class="cta.naturaleza === 'deudora' && Math.abs(cta.saldo_final) > 0.009 ? (cta.saldo_final < 0 ? 'text-rose-600' : 'text-emerald-600 dark:text-emerald-400 font-black') : 'text-slate-300 dark:text-slate-600 font-normal'">
                                        {{ cta.naturaleza === 'deudora' && Math.abs(cta.saldo_final) > 0.009 ? formatCurrency(cta.saldo_final) : '-' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-mono font-bold" :class="cta.naturaleza === 'acreedora' && Math.abs(cta.saldo_final) > 0.009 ? (cta.saldo_final < 0 ? 'text-rose-600' : 'text-indigo-600 dark:text-indigo-400 font-black') : 'text-slate-300 dark:text-slate-600 font-normal'">
                                        {{ cta.naturaleza === 'acreedora' && Math.abs(cta.saldo_final) > 0.009 ? formatCurrency(cta.saldo_final) : '-' }}
                                    </td>
                                </tr>
                                <tr v-if="reportData.length === 0">
                                    <td colspan="8" class="px-6 py-16 text-center text-slate-400 italic">No hay registros ni saldos en el catálogo contable para este periodo</td>
                                </tr>
                            </tbody>
                            <tfoot v-if="reportData.length > 0">
                                <tr class="bg-slate-100 dark:bg-slate-800/80 border-t-2 border-slate-200 dark:border-slate-700 font-bold uppercase tracking-wider text-[11px]">
                                    <td colspan="2" class="px-5 py-5 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <span v-if="Math.abs(totales.cargos - totales.abonos) > 0.01" class="px-3 py-1 bg-rose-500/10 text-rose-500 rounded-lg animate-pulse font-black text-[10px]">⚠ DESCUADRE DETECTADO</span>
                                            <span v-else class="px-3 py-1 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-lg font-black text-[10px]">✔ BALANZA CUADRADA</span>
                                            <span class="text-slate-600 dark:text-slate-300">Sumas Iguales:</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-5 text-right font-mono font-bold text-slate-800 dark:text-white">{{ formatCurrency(totales.inicial_deudor) }}</td>
                                    <td class="px-5 py-5 text-right font-mono font-bold text-slate-800 dark:text-white">{{ formatCurrency(totales.inicial_acreedor) }}</td>
                                    <td class="px-5 py-5 text-right font-mono font-black text-emerald-600 dark:text-emerald-400">{{ formatCurrency(totales.cargos) }}</td>
                                    <td class="px-5 py-5 text-right font-mono font-black text-rose-500 dark:text-rose-400">{{ formatCurrency(totales.abonos) }}</td>
                                    <td class="px-5 py-5 text-right font-mono font-black text-emerald-600 dark:text-emerald-400 underline">{{ formatCurrency(totales.final_deudor) }}</td>
                                    <td class="px-5 py-5 text-right font-mono font-black text-indigo-600 dark:text-indigo-400 underline">{{ formatCurrency(totales.final_acreedor) }}</td>
                                </tr>
                            </tfoot>
                        </table>
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
                            <h3 class="text-xl font-black">Auditor & Asesor Financiero AI</h3>
                            <p class="text-xs text-indigo-400 font-bold uppercase tracking-wider">Periodo: {{ nombreMesSeleccionado }} {{ anio }}</p>
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
                        <h4 class="text-lg font-black tracking-wide mb-2 animate-pulse text-indigo-400">Auditando cargos, abonos y saldos de la balanza...</h4>
                        <p class="text-xs text-slate-400 max-w-sm">La Inteligencia Artificial está analizando la integridad por partida doble y la liquidez/capital de trabajo en segundos.</p>
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
                                Mostrando auditoría guardada en memoria instantánea (Cero consumo de tokens).
                            </div>
                            <button @click="abrirAnalisisAi(true)" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-black rounded-xl transition-all flex items-center gap-1.5 shadow-lg shadow-indigo-600/30">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Actualizar Auditoría AI
                            </button>
                        </div>
                        <div v-else class="bg-emerald-500/10 border border-emerald-500/20 rounded-2xl p-3 text-center text-xs text-emerald-400 font-bold">
                            ✨ Análisis de balanza fresco generado exitosamente por Gemini AI.
                        </div>

                        <div class="prose prose-invert max-w-none text-slate-200 leading-relaxed font-sans" v-html="formatMarkdown(aiContent)"></div>
                    </div>
                </div>

                <div class="p-6 border-t border-slate-800 bg-slate-900/50 flex justify-end gap-3">
                    <button @click="aiModalOpen = false" class="px-6 py-2.5 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold rounded-2xl transition-colors">Cerrar Panel</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
