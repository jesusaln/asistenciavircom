<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    resumen: Object,
    polizas: Array,
});

const activeTab = ref('polizas');

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value || 0);
};

const getClasificacionClass = (clasificacion) => {
    switch (clasificacion) {
        case 'altamente_rentable':
            return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'rentable':
            return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'marginal':
            return 'bg-amber-100 text-amber-800 border-amber-200';
        case 'bajo_margen':
            return 'bg-red-100 text-red-800 border-red-200';
        default:
            return 'bg-gray-100 text-gray-700';
    }
};

const getMargenColor = (margen) => {
    if (margen >= 40) return 'text-emerald-600';
    if (margen >= 20) return 'text-blue-500';
    if (margen >= 10) return 'text-amber-500';
    return 'text-red-500';
};
</script>

<template>
    <Head title="Reporte de Rentabilidad Premium" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-black text-xl text-gray-800 dark:text-gray-100 uppercase tracking-tight">
                        💎 Profitability & IFRS15 Intelligence
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Análisis de rentabilidad basado en costos reales de técnicos e ingresos devengados.
                    </p>
                </div>
                <Link :href="route('polizas-servicio.dashboard')" class="px-4 py-2 bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-slate-700 rounded-xl font-bold text-xs uppercase tracking-widest hover:shadow-md transition-all">
                    ← Dashboard
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                
                <!-- KPIs Principales -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 shadow-xl border border-gray-100 dark:border-slate-800">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Ingresos Reconocidos</p>
                                <p class="text-2xl font-black text-gray-900 dark:text-white">{{ formatCurrency(resumen.stats.total_ingresos_reconocidos) }}</p>
                            </div>
                            <div class="p-2 bg-emerald-50 dark:bg-emerald-900/30 rounded-lg text-emerald-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <p class="text-[10px] text-gray-500 mt-4 italic">IFRS15: Devengado a la fecha</p>
                    </div>

                    <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 shadow-xl border border-gray-100 dark:border-slate-800">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Costo Técnico Real</p>
                                <p class="text-2xl font-black text-red-500">{{ formatCurrency(resumen.stats.total_costos_reales) }}</p>
                            </div>
                            <div class="p-2 bg-red-50 dark:bg-red-900/30 rounded-lg text-red-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <p class="text-[10px] text-gray-500 mt-4 italic">Basado en costo/hora por técnico</p>
                    </div>

                    <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 shadow-xl border border-gray-100 dark:border-slate-800">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Utilidad Bruta</p>
                                <p class="text-2xl font-black" :class="resumen.stats.utilidad_neta >= 0 ? 'text-blue-600' : 'text-red-600'">
                                    {{ formatCurrency(resumen.stats.utilidad_neta) }}
                                </p>
                            </div>
                            <div class="p-2 bg-blue-50 dark:bg-blue-900/30 rounded-lg text-blue-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            </div>
                        </div>
                        <p class="text-[10px] text-gray-500 mt-4 italic">Margen Global: {{ resumen.stats.margen_global }}%</p>
                    </div>

                    <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 shadow-xl border border-gray-100 dark:border-slate-800">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Ingresos Diferidos</p>
                                <p class="text-2xl font-black text-amber-600">{{ formatCurrency(resumen.stats.total_ingresos_diferidos) }}</p>
                            </div>
                            <div class="p-2 bg-amber-50 dark:bg-amber-900/30 rounded-lg text-amber-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <p class="text-[10px] text-gray-500 mt-4 italic">Pasivo: Por devengar en meses futuros</p>
                    </div>
                </div>

                <!-- Tabs de Navegación -->
                <div class="flex space-x-4 border-b border-gray-200 dark:border-slate-800">
                    <button 
                        @click="activeTab = 'polizas'"
                        :class="['pb-4 px-4 text-sm font-black uppercase tracking-widest transition-all', activeTab === 'polizas' ? 'border-b-4 border-blue-600 text-blue-600' : 'text-gray-400 hover:text-gray-600']"
                    >
                        Pólizas
                    </button>
                    <button 
                        @click="activeTab = 'tecnicos'"
                        :class="['pb-4 px-4 text-sm font-black uppercase tracking-widest transition-all', activeTab === 'tecnicos' ? 'border-b-4 border-blue-600 text-blue-600' : 'text-gray-400 hover:text-gray-600']"
                    >
                        Eficiencia Técnica
                    </button>
                </div>

                <!-- Contenido de Pólizas -->
                <div v-if="activeTab === 'polizas'" class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 dark:border-slate-800 flex justify-between items-center">
                        <div>
                            <h3 class="font-black text-gray-900 dark:text-white uppercase tracking-tight">
                                Detalle de Rentabilidad por Póliza
                            </h3>
                            <p class="text-xs text-gray-500 mt-1 italic">Costos calculados en tiempo real según consumos registrados.</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-slate-950">
                                <tr>
                                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Folio / Plan</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Cliente</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest">Reconocido (IFRS15)</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest">Costo Técnico</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest">Utilidad</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest">Margen %</th>
                                    <th class="px-6 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">Salud</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                                <tr v-for="p in polizas" :key="p.poliza_id" class="hover:bg-blue-50/30 dark:hover:bg-slate-800/50 transition-colors group">
                                    <td class="px-6 py-4">
                                        <Link :href="route('polizas-servicio.show', p.poliza_id)" class="font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                            {{ p.folio }}
                                        </Link>
                                        <p class="text-[10px] text-gray-400 font-medium">{{ p.plan }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ p.cliente }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ formatCurrency(p.ingreso_reconocido) }}</p>
                                        <p v-if="p.ingreso_diferido > 0" class="text-[10px] text-amber-500 font-bold">Diferido: {{ formatCurrency(p.ingreso_diferido) }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <p class="text-sm font-bold text-red-500">{{ formatCurrency(p.costo_tecnico_real) }}</p>
                                        <p class="text-[10px] text-gray-400">{{ p.horas_consumidas }}h consumidas</p>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-bold" :class="p.utilidad >= 0 ? 'text-emerald-600' : 'text-red-600'">
                                        {{ formatCurrency(p.utilidad) }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="text-sm font-black" :class="getMargenColor(p.margen_porcentaje)">
                                            {{ p.margen_porcentaje }}%
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase border', getClasificacionClass(p.clasificacion)]">
                                            {{ p.clasificacion.replace('_', ' ') }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Contenido de Técnicos -->
                <div v-if="activeTab === 'tecnicos'" class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 dark:border-slate-800">
                        <h3 class="font-black text-gray-900 dark:text-white uppercase tracking-tight">
                            Eficiencia Operativa por Técnico
                        </h3>
                        <p class="text-xs text-gray-500 mt-1 italic">Monitoreo de costos generados y volumen de servicio por técnico.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-slate-950">
                                <tr>
                                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Técnico</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Servicios</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest">Horas Equivalentes</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest">Costo Generado</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest">Costo x Servicio</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                                <tr v-for="t in resumen.eficiencia_tecnica" :key="t.tecnico" class="hover:bg-blue-50/30 dark:hover:bg-slate-800/50 transition-colors">
                                    <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">{{ t.tecnico }}</td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-600 dark:text-gray-300">{{ t.servicios }}</td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-600 dark:text-gray-300">{{ t.horas }}h</td>
                                    <td class="px-6 py-4 text-right text-sm font-bold text-red-500">{{ formatCurrency(t.costo_total) }}</td>
                                    <td class="px-6 py-4 text-right text-sm font-bold text-gray-900 dark:text-white">{{ formatCurrency(t.costo_promedio_servicio) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Footer Summary Info -->
                <div class="bg-blue-600 rounded-2xl p-8 text-white shadow-2xl overflow-hidden relative">
                    <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-6">
                        <div>
                            <h4 class="text-2xl font-black mb-2">Visibilidad Financiera Full-Stack</h4>
                            <p class="text-blue-100 max-w-2xl">
                                Este reporte integra los costos internos configurados por usuario y el motor de reconocimiento de ingresos IFRS15 para darte una visión real de la rentabilidad de cada contrato.
                            </p>
                        </div>
                        <div class="flex gap-4">
                            <div class="text-center">
                                <p class="text-3xl font-black">{{ resumen.top_oportunidades.length }}</p>
                                <p class="text-[10px] uppercase font-bold text-blue-200">Oportunidades</p>
                            </div>
                            <div class="w-px h-12 bg-blue-500"></div>
                            <div class="text-center">
                                <p class="text-3xl font-black">{{ resumen.top_riesgos.length }}</p>
                                <p class="text-[10px] uppercase font-bold text-blue-200">Pólizas en Riesgo</p>
                            </div>
                        </div>
                    </div>
                    <!-- Subtle background decoration -->
                    <div class="absolute -right-20 -bottom-20 w-64 h-64 bg-blue-500 rounded-full opacity-20"></div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
