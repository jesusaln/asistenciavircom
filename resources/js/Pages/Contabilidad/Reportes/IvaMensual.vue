<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';

const notyf = new Notyf({ duration: 4000 });

const props = defineProps({
    reportData: Object,
    xmlData: Object,
    filters: Object
});

const breakdown = ref([])
const loadingRayosX = ref(false)

const ejecutarRayosX = async () => {
    loadingRayosX.value = true
    try {
        const res = await axios.get(route('contabilidad.api.rayos-x'), {
            params: { mes: mes.value, anio: anio.value }
        })
        if (res.data.success) {
            breakdown.value = res.data.data || []
            notyf.success(`Rayos X: ${breakdown.value.length} registros`)
        }
    } catch (e) {
        notyf.error('Error al cargar Rayos X')
    } finally {
        loadingRayosX.value = false
    }
}

const mes = ref(props.filters.mes);
const anio = ref(props.filters.anio);

const updateReport = () => {
    router.get(route('contabilidad.reportes.iva-mensual'), {
        mes: mes.value,
        anio: anio.value
    }, { preserveState: true });
};

const mesActual = computed(() => {
    const m = meses.find(m => m.id === mes.value);
    return m ? m.nombre : mes.value;
});

const formatCurrency = (n) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(n);

const diff = (val1, val2) => Math.abs(val1 - val2);
const isDiff = (val1, val2) => diff(val1, val2) > 0.05;

const meses = [
    { id: 'anual', nombre: 'Todo el año' },
    { id: '01', nombre: 'Enero' }, { id: '02', nombre: 'Febrero' }, { id: '03', nombre: 'Marzo' },
    { id: '04', nombre: 'Abril' }, { id: '05', nombre: 'Mayo' }, { id: '06', nombre: 'Junio' },
    { id: '07', nombre: 'Julio' }, { id: '08', nombre: 'Agosto' }, { id: '09', nombre: 'Septiembre' },
    { id: '10', nombre: 'Octubre' }, { id: '11', nombre: 'Noviembre' }, { id: '12', nombre: 'Diciembre' }
];
const anios = ['2024', '2025', '2026'];

// Devoluciones: usar el mayor valor entre Pólizas y XML (si no hay póliza, el XML es la verdad del SAT)
const devolucionesUsadas = computed(() => {
    const devPol = props.reportData?.iva_devoluciones_gastos ?? 0;
    const devXml = props.xmlData?.iva_devoluciones_gastos ?? 0;
    return Math.max(devPol, devXml);
});

// IVA Neto = Trasladado - Acreditable + Devoluciones
const ivaNeto = computed(() => {
    const trasladado = props.reportData?.trasladado ?? 0;
    const acreditable = props.reportData?.acreditable ?? 0;
    return trasladado - acreditable + devolucionesUsadas.value;
});

// Auditoría IA
const loadingAiAudit = ref(false);
const aiAuditSummary = ref(null);

const ejecutarAuditoriaAi = async () => {
    loadingAiAudit.value = true;
    try {
        const res = await axios.get(route('contabilidad.api.rayos-x-ai'), {
            params: { mes: mes.value, anio: anio.value }
        });
        if (res.data.success) {
            aiAuditSummary.value = res.data.summary_md;
            notyf.success('Diagnóstico de IA generado exitosamente');
        } else {
            notyf.error(res.data.summary_md || 'Error al generar diagnóstico IA');
        }
    } catch (e) {
        notyf.error('Error al conectar con el servicio de IA');
    } finally {
        loadingAiAudit.value = false;
    }
};

const formatMarkdown = (text) => {
    if (!text) return '';
    return text
        .replace(/### (.*)/g, '<h3 class="text-xl font-black mt-6 mb-3 text-indigo-300 border-b border-indigo-500/20 pb-2">$1</h3>')
        .replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-white">$1</strong>')
        .replace(/\*(.*?)\*/g, '<em class="italic text-slate-300">$1</em>')
        .replace(/- (.*)/g, '<li class="ml-6 mb-2 list-disc">$1</li>')
        .replace(/\n\n/g, '<br/><br/>')
        .replace(/\n/g, '<br/>');
};
</script>

<template>
    <AppLayout title="Conciliación de Impuestos">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-black text-2xl text-slate-800 dark:text-white leading-tight tracking-tight">
                    Conciliación Fiscal: <span class="text-indigo-600 dark:text-indigo-400">Pólizas vs XML</span>
                </h2>
                <div class="flex gap-4">
                    <select v-model="mes" @change="updateReport" class="bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-black shadow-sm">
                        <option v-for="m in meses" :key="m.id" :value="m.id">{{ m.nombre }}</option>
                    </select>
                    <select v-model="anio" @change="updateReport" class="bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-black shadow-sm">
                        <option v-for="a in anios" :key="a" :value="a">{{ a }}</option>
                    </select>
                </div>
            </div>
        </template>

        <div class="py-12 bg-slate-50 dark:bg-slate-950 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Alerta de Discrepancia -->
                <div v-if="isDiff(reportData.trasladado, xmlData.trasladado) || isDiff(reportData.acreditable, xmlData.acreditable)" 
                     class="mb-8 p-4 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800/30 rounded-3xl flex items-center gap-4 animate-pulse">
                    <div class="w-12 h-12 bg-rose-500 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-rose-500/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <div>
                        <h4 class="text-rose-800 dark:text-rose-400 font-black">Discrepancia Detectada</h4>
                        <p class="text-rose-600 dark:text-rose-500 text-sm">Tus pólizas no coinciden exactamente con los XMLs del SAT. Revisa los montos en rojo.</p>
                    </div>
                </div>

                <!-- PANEL DE AUDITORÍA IA -->
                <div class="mb-12 bg-gradient-to-r from-indigo-950 via-slate-900 to-purple-950 rounded-[3rem] p-8 md:p-12 text-white shadow-2xl border border-indigo-500/30 relative overflow-hidden">
                    <div class="absolute -right-20 -bottom-20 w-80 h-80 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute -left-20 -top-20 w-80 h-80 bg-purple-500/10 rounded-full blur-3xl pointer-events-none"></div>
                    
                    <div class="relative z-10">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-8 pb-8 border-b border-white/10">
                            <div>
                                <div class="inline-flex items-center gap-2 px-3 py-1 bg-indigo-500/20 border border-indigo-400/30 rounded-full text-indigo-300 text-xs font-black uppercase tracking-widest mb-3">
                                    <svg class="w-4 h-4 text-indigo-400 animate-pulse" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                                    Gemini 2.5 Flash AI
                                </div>
                                <h3 class="text-2xl md:text-3xl font-black text-white tracking-tight">Auditoría Fiscal Predictiva <span class="text-indigo-400">("Rayos X IA")</span></h3>
                                <p class="text-slate-300 text-sm mt-1">Análisis experto en segundos para detectar riesgos y omisiones antes de tu declaración ante el SAT.</p>
                            </div>
                            
                            <button @click="ejecutarAuditoriaAi" :disabled="loadingAiAudit"
                                class="px-8 py-4 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 font-black text-xs uppercase tracking-widest rounded-2xl shadow-xl shadow-indigo-500/20 transition-all transform active:scale-95 disabled:opacity-50 flex items-center gap-3 border border-white/20 cursor-pointer">
                                <svg v-if="loadingAiAudit" class="animate-spin w-5 h-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4"/></svg>
                                <svg v-else class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                {{ loadingAiAudit ? 'Analizando con IA...' : 'Generar Diagnóstico Fiscal AI' }}
                            </button>
                        </div>

                        <!-- RESULTADO DE LA IA -->
                        <div v-if="loadingAiAudit" class="py-12 text-center flex flex-col items-center justify-center gap-4">
                            <div class="relative w-20 h-20">
                                <div class="absolute inset-0 rounded-full border-4 border-indigo-500/20"></div>
                                <div class="absolute inset-0 rounded-full border-4 border-indigo-400 border-t-transparent animate-spin"></div>
                            </div>
                            <p class="text-indigo-300 font-bold tracking-wide animate-pulse">Gemini 2.5 Flash está auditando tus pólizas y comprobantes...</p>
                        </div>
                        
                        <div v-else-if="aiAuditSummary" class="bg-slate-950/60 backdrop-blur-md rounded-3xl p-6 md:p-8 border border-white/10 prose prose-invert max-w-none text-slate-200 leading-relaxed font-sans shadow-inner" v-html="formatMarkdown(aiAuditSummary)">
                        </div>

                        <div v-else class="py-8 text-center text-slate-400 border border-dashed border-white/10 rounded-3xl bg-white/5">
                            <p class="text-sm">Haz clic en <strong>"Generar Diagnóstico Fiscal AI"</strong> para obtener una auditoría completa de tus impuestos.</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- TABLA DE CONCILIACIÓN -->
                    <div class="lg:col-span-2 bg-white dark:bg-slate-900 shadow-2xl shadow-slate-200/50 dark:shadow-none rounded-[3rem] overflow-hidden border border-slate-100 dark:border-slate-800">
                        <div class="p-8 border-b border-slate-50 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                            <h3 class="text-lg font-black text-slate-800 dark:text-white">Resumen de Conciliación</h3>
                            <p class="text-sm text-slate-500">Comparativa de montos acumulados — <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ mesActual }} {{ anio }}</span></p>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="text-[10px] uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500 font-black">
                                        <th class="px-8 py-6">Concepto</th>
                                        <th class="px-8 py-6 text-right">Según Pólizas</th>
                                        <th class="px-8 py-6 text-right">Según XML</th>
                                        <th class="px-8 py-6 text-right">Diferencia</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                                    <!-- IVA TRASLADADO -->
                                    <tr class="group hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all">
                                        <td class="px-8 py-6">
                                            <div class="font-bold text-slate-700 dark:text-slate-200">IVA Trasladado</div>
                                            <div class="text-[10px] text-slate-400">Ventas Cobradas (002)</div>
                                        </td>
                                        <td class="px-8 py-6 text-right font-mono font-bold text-slate-800 dark:text-white">{{ formatCurrency(reportData.trasladado) }}</td>
                                        <td class="px-8 py-6 text-right font-mono font-bold text-slate-600 dark:text-slate-400">{{ formatCurrency(xmlData.trasladado) }}</td>
                                        <td class="px-8 py-6 text-right font-mono font-black" 
                                            :class="isDiff(reportData.trasladado, xmlData.trasladado) ? 'text-rose-500 bg-rose-50/50 dark:bg-rose-900/10' : 'text-emerald-500'">
                                            {{ formatCurrency(reportData.trasladado - xmlData.trasladado) }}
                                        </td>
                                    </tr>
                                    <!-- IVA ACREDITABLE -->
                                    <tr class="group hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all">
                                        <td class="px-8 py-6">
                                            <div class="font-bold text-slate-700 dark:text-slate-200">IVA Acreditable</div>
                                            <div class="text-[10px] text-slate-400">Gastos Pagados (002)</div>
                                        </td>
                                        <td class="px-8 py-6 text-right font-mono font-bold text-slate-800 dark:text-white">{{ formatCurrency(reportData.acreditable) }}</td>
                                        <td class="px-8 py-6 text-right font-mono font-bold text-slate-600 dark:text-slate-400">{{ formatCurrency(xmlData.acreditable) }}</td>
                                        <td class="px-8 py-6 text-right font-mono font-black" 
                                            :class="isDiff(reportData.acreditable, xmlData.acreditable) ? 'text-rose-500 bg-rose-50/50 dark:bg-rose-900/10' : 'text-emerald-500'">
                                            {{ formatCurrency(reportData.acreditable - xmlData.acreditable) }}
                                        </td>
                                    </tr>
                                    <!-- IVA DEVOLUCIONES -->
                                    <tr v-if="reportData.iva_devoluciones_gastos > 0 || xmlData.iva_devoluciones_gastos > 0" class="group hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all">
                                        <td class="px-8 py-6">
                                            <div class="font-bold text-slate-700 dark:text-slate-200">IVA por Devoluciones</div>
                                            <div class="text-[10px] text-slate-400">Descuentos/Bonificaciones en Gastos</div>
                                        </td>
                                        <td class="px-8 py-6 text-right font-mono font-bold text-slate-800 dark:text-white">{{ formatCurrency(reportData.iva_devoluciones_gastos) }}</td>
                                        <td class="px-8 py-6 text-right font-mono font-bold text-slate-600 dark:text-slate-400">{{ formatCurrency(xmlData.iva_devoluciones_gastos) }}</td>
                                        <td class="px-8 py-6 text-right font-mono font-black" 
                                            :class="isDiff(reportData.iva_devoluciones_gastos, xmlData.iva_devoluciones_gastos) ? 'text-rose-500 bg-rose-50/50 dark:bg-rose-900/10' : 'text-emerald-500'">
                                            {{ formatCurrency(reportData.iva_devoluciones_gastos - xmlData.iva_devoluciones_gastos) }}
                                        </td>
                                    </tr>
                                    <!-- INGRESOS BRUTOS -->
                                    <tr class="group hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all">
                                        <td class="px-8 py-6">
                                            <div class="font-bold text-slate-700 dark:text-slate-200">Ingresos Brutos</div>
                                            <div class="text-[10px] text-slate-400">Base para ISR RESICO</div>
                                        </td>
                                        <td class="px-8 py-6 text-right font-mono font-bold text-slate-800 dark:text-white">{{ formatCurrency(reportData.ingresos_brutos) }}</td>
                                        <td class="px-8 py-6 text-right font-mono font-bold text-slate-600 dark:text-slate-400">{{ formatCurrency(xmlData.ingresos_brutos) }}</td>
                                        <td class="px-8 py-6 text-right font-mono font-black" 
                                            :class="isDiff(reportData.ingresos_brutos, xmlData.ingresos_brutos) ? 'text-rose-500 bg-rose-50/50 dark:bg-rose-900/10' : 'text-emerald-500'">
                                            {{ formatCurrency(reportData.ingresos_brutos - xmlData.ingresos_brutos) }}
                                        </td>
                                    </tr>
                                    <!-- ISR RETENIDO -->
                                    <tr class="group hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all">
                                        <td class="px-8 py-6">
                                            <div class="font-bold text-slate-700 dark:text-slate-200">ISR Retenido a Favor</div>
                                            <div class="text-[10px] text-slate-400">Retenciones de Clientes (PUE + REP)</div>
                                        </td>
                                        <td class="px-8 py-6 text-right font-mono font-bold text-slate-800 dark:text-white">{{ formatCurrency(reportData.isr_retenido_clientes) }}</td>
                                        <td class="px-8 py-6 text-right font-mono font-bold text-slate-600 dark:text-slate-400">{{ formatCurrency(xmlData.isr_retenido_clientes) }}</td>
                                        <td class="px-8 py-6 text-right font-mono font-black" 
                                            :class="isDiff(reportData.isr_retenido_clientes, xmlData.isr_retenido_clientes) ? 'text-rose-500 bg-rose-50/50 dark:bg-rose-900/10' : 'text-emerald-500'">
                                            {{ formatCurrency(reportData.isr_retenido_clientes - xmlData.isr_retenido_clientes) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- CARD DE ISR FINAL -->
                    <div class="space-y-8">
                        <div class="bg-indigo-600 dark:bg-indigo-900 rounded-[3rem] p-8 text-white shadow-2xl shadow-indigo-500/20 relative overflow-hidden">
                            <div class="relative z-10">
                                <span class="text-[10px] font-black uppercase tracking-[0.3em] opacity-60">Pago de ISR RESICO (Neto)</span>
                                <div class="mt-4 flex items-baseline gap-2">
                                    <h4 class="text-5xl font-black">{{ formatCurrency(xmlData.isr_neto_pagar ?? reportData.isr_neto_pagar) }}</h4>
                                    <span class="text-sm opacity-60">MXN</span>
                                </div>
                                <div class="mt-6 pt-6 border-t border-white/10 grid grid-cols-3 gap-2 text-xs">
                                    <div>
                                        <p class="text-[10px] uppercase font-black opacity-60">ISR Bruto ({{ (reportData.tasa_isr * 100).toFixed(1) }}%)</p>
                                        <p class="text-base font-black">{{ formatCurrency(reportData.isr_resico) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] uppercase font-black opacity-60">Retenido PUE</p>
                                        <p class="text-base font-black text-emerald-300">-{{ formatCurrency(xmlData.detalle_flujo?.retenciones_pue ?? 0) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] uppercase font-black opacity-60">Retenido REP</p>
                                        <p class="text-base font-black text-emerald-300">-{{ formatCurrency(xmlData.detalle_flujo?.retenciones_rep ?? 0) }}</p>
                                    </div>
                                </div>
                            </div>
                            <!-- Decoración -->
                            <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-white/10 rounded-full blur-3xl"></div>
                        </div>

                        <!-- CARD DE IVA NETO -->
                        <div class="bg-slate-900 dark:bg-slate-800 rounded-[3rem] p-8 text-white shadow-2xl shadow-slate-900/20 border border-white/5">
                            <span class="text-[10px] font-black uppercase tracking-[0.3em] opacity-60">IVA Neto a Pagar</span>
                            <div class="mt-4 flex items-baseline gap-2">
                                <h4 class="text-5xl font-black" :class="ivaNeto < 0 ? 'text-emerald-400' : 'text-white'">
                                    {{ formatCurrency(Math.abs(ivaNeto)) }}
                                </h4>
                            </div>
                            <div v-if="devolucionesUsadas > 0" class="mt-3 text-[10px] text-amber-400/80 font-bold">
                                Incluye {{ formatCurrency(devolucionesUsadas) }} por devoluciones/notas de crédito
                            </div>
                            <p class="mt-4 text-xs text-slate-400 font-medium italic">
                                {{ ivaNeto >= 0 ? '* Recuerda pagar antes del día 17.' : '* Tienes saldo a favor para el siguiente mes.' }}
                            </p>
                        </div>
                    </div>

                </div>

                <div class="mt-12 p-8 bg-white dark:bg-slate-900 rounded-[3rem] border border-slate-100 dark:border-slate-800">
                    <h4 class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-widest mb-6">Detalle Técnico de Flujo</h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase font-bold mb-1">ISR Retenido a Favor</p>
                            <p class="text-xl font-black text-slate-700 dark:text-slate-200">{{ formatCurrency(reportData.isr_retenido_clientes) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase font-bold mb-1">ISR Retenido (Nómina)</p>
                            <p class="text-xl font-black text-slate-700 dark:text-slate-200">{{ formatCurrency(reportData.isr_retenido_nomina) }}</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-[10px] text-slate-400 uppercase font-bold mb-1">Nota Informativa</p>
                            <p class="text-xs text-slate-500 italic">Los montos "Según Pólizas" consideran el 100% de los asientos asentados en las cuentas 213 y 118. Si hay diferencias con el XML, revisa si hay facturas canceladas o pagos no contabilizados.</p>
                        </div>
                    </div>
                </div>

                <!-- TABLA DE RAYOS X (DETALLE PÓLIZA VS XML) -->
                <div class="mt-12 bg-white dark:bg-slate-900 shadow-2xl shadow-slate-200/50 dark:shadow-none rounded-[3rem] overflow-hidden border border-slate-100 dark:border-slate-800">
                    <div class="p-8 border-b border-slate-50 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-black text-slate-800 dark:text-white">Rayos X: Pólizas vs XML</h3>
                            <p class="text-sm text-slate-500">Desglose detallado uno a uno para detectar discrepancias</p>
                        </div>
                        <button @click="ejecutarRayosX" :disabled="loadingRayosX"
                            class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow-sm disabled:opacity-50 flex items-center gap-2">
                            <svg v-if="loadingRayosX" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4"/></svg>
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            {{ loadingRayosX ? 'Cargando...' : 'Ejecutar Rayos X' }}
                        </button>
                    </div>
                    <div v-if="breakdown.length > 0">
                    
                    <div class="overflow-x-auto max-h-[600px]">
                        <table class="w-full text-left border-collapse">
                            <thead class="sticky top-0 bg-white dark:bg-slate-900 shadow-sm z-10">
                                <tr class="text-[10px] uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500 font-black border-b border-slate-100 dark:border-slate-800">
                                    <th class="px-6 py-4">Concepto / UUID</th>
                                    <th class="px-6 py-4">Póliza</th>
                                    <th class="px-6 py-4 text-center">F. Póliza</th>
                                    <th class="px-6 py-4 text-center">F. XML</th>
                                    <th class="px-6 py-4">Tipo</th>
                                    <th class="px-6 py-4 text-right bg-slate-50/50 dark:bg-slate-800/20">IVA (Póliza)</th>
                                    <th class="px-6 py-4 text-right bg-slate-50/50 dark:bg-slate-800/20">IVA (XML)</th>
                                    <th class="px-6 py-4 text-right">Diferencia</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
                                <tr v-for="item in breakdown" :key="item.uuid" 
                                    class="group hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all"
                                    :class="{'bg-rose-50/30 dark:bg-rose-900/10': isDiff(item.tipo === 'Gasto' ? item.iva_acreditable_poliza : item.iva_trasladado_poliza, item.tipo === 'Gasto' ? item.iva_acreditable_xml : item.iva_trasladado_xml)}">
                                    <td class="px-6 py-4 max-w-xs">
                                        <div class="font-bold text-slate-700 dark:text-slate-200 truncate" :title="item.concepto">{{ item.concepto }}</div>
                                        <div class="text-[9px] font-mono text-slate-400 truncate">{{ item.uuid || 'SIN UUID' }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                              :class="item.numero_poliza === 'Falta Póliza' ? 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300' : 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300'">
                                            {{ item.numero_poliza }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-xs font-mono font-medium text-slate-500" :class="{'text-rose-500 font-bold': item.fecha_poliza === 'N/A'}">{{ item.fecha_poliza }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-xs font-mono font-medium text-slate-500" :class="{'text-rose-500 font-bold': item.fecha_xml === 'N/A'}">{{ item.fecha_xml }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-xs font-bold" :class="item.tipo === 'Gasto' ? 'text-brand-500' : 'text-emerald-500'">{{ item.tipo }}</span>
                                    </td>
                                    <!-- IVA POLIZA -->
                                    <td class="px-6 py-4 text-right font-mono font-bold text-slate-700 dark:text-slate-300 bg-slate-50/30 dark:bg-slate-800/10">
                                        {{ formatCurrency(item.tipo === 'Gasto' ? item.iva_acreditable_poliza : item.iva_trasladado_poliza) }}
                                    </td>
                                    <!-- IVA XML -->
                                    <td class="px-6 py-4 text-right font-mono font-bold text-slate-700 dark:text-slate-300 bg-slate-50/30 dark:bg-slate-800/10">
                                        {{ formatCurrency(item.tipo === 'Gasto' ? item.iva_acreditable_xml : item.iva_trasladado_xml) }}
                                    </td>
                                    <!-- DIFERENCIA -->
                                    <td class="px-6 py-4 text-right font-mono font-black"
                                        :class="isDiff(item.tipo === 'Gasto' ? item.iva_acreditable_poliza : item.iva_trasladado_poliza, item.tipo === 'Gasto' ? item.iva_acreditable_xml : item.iva_trasladado_xml) ? 'text-rose-500' : 'text-emerald-500'">
                                        {{ formatCurrency((item.tipo === 'Gasto' ? item.iva_acreditable_poliza : item.iva_trasladado_poliza) - (item.tipo === 'Gasto' ? item.iva_acreditable_xml : item.iva_trasladado_xml)) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    </div>
                    <div v-else class="p-8 text-center text-slate-400">
                        <p class="text-sm">Haz clic en <strong>"Ejecutar Rayos X"</strong> para cargar el detalle</p>
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
