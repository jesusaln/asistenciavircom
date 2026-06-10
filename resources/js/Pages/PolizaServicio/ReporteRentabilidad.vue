<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    resumen: Object,
    polizas: Array,
});

const { formatCurrency } = useFormatters();

const getClasificacionClass = (clasificacion) => {
    switch (clasificacion) {
        case 'rentable':
            return 'bg-emerald-100 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 border-emerald-200 dark:border-emerald-800/30';
        case 'marginal':
            return 'bg-brand-100 text-brand-800 dark:text-brand-200 dark:text-brand-200 border-brand-200 dark:border-brand-800/30';
        case 'perdida':
            return 'bg-rose-100 text-rose-800 dark:text-rose-200 dark:text-rose-200 border-rose-200 dark:border-rose-800/30';
        default:
            return 'bg-slate-100 text-slate-700';
    }
};

const getMargenColor = (margen) => {
    if (margen >= 50) return 'text-emerald-600';
    if (margen >= 30) return 'text-emerald-500';
    if (margen >= 0) return 'text-brand-500';
    return 'text-rose-500';
};
</script>

<template>
    <Head title="Reporte de Rentabilidad" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-black text-xl text-slate-800 dark:text-white uppercase tracking-wider">
                        📊 Reporte de Rentabilidad
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Análisis de ingresos vs costos operativos por póliza
                    </p>
                </div>
                <Link :href="route('polizas-servicio.dashboard')" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-xl font-bold text-xs uppercase tracking-wide hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                    ← Volver al Dashboard
                </Link>
            </div>
        </template>

        <div class="py-8 min-h-screen bg-[var(--ui-surface)] text-slate-800 dark:text-slate-200 transition-colors">
            <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
                
                <!-- KPIs Principales -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-xl border border-slate-100 dark:border-slate-800">
                        <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-1">Total Ingresos</p>
                        <p class="text-2xl font-black text-slate-900 dark:text-white">{{ formatCurrency(resumen.total_ingresos) }}</p>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-xl border border-slate-100 dark:border-slate-800">
                        <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-1">Total Costos</p>
                        <p class="text-2xl font-black text-rose-500">{{ formatCurrency(resumen.total_costos) }}</p>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-xl border border-slate-100 dark:border-slate-800">
                        <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-1">Utilidad Neta</p>
                        <p class="text-2xl font-black" :class="resumen.utilidad_neta >= 0 ? 'text-emerald-600 dark:text-slate-400' : 'text-rose-600 dark:text-rose-400'">
                            {{ formatCurrency(resumen.utilidad_neta) }}
                        </p>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-xl border border-slate-100 dark:border-slate-800">
                        <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-1">Margen Promedio</p>
                        <p class="text-2xl font-black" :class="getMargenColor(resumen.margen_promedio)">
                            {{ resumen.margen_promedio }}%
                        </p>
                    </div>
                </div>

                <!-- Resumen por Clasificación -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-emerald-50 dark:bg-emerald-900/20 dark:bg-emerald-950/20 rounded-2xl p-6 border border-emerald-100 dark:border-emerald-800/30">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-800 flex items-center justify-center text-xl">💰</div>
                            <div>
                                <p class="font-black text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-slate-400 uppercase text-xs">Rentables</p>
                                <p class="text-2xl font-black text-emerald-800 dark:text-emerald-200 dark:text-emerald-300">{{ resumen.resumen?.rentables?.cantidad || 0 }}</p>
                            </div>
                        </div>
                        <p class="text-sm text-emerald-600 dark:text-slate-400">
                            Utilidad: {{ formatCurrency(resumen.resumen?.rentables?.utilidad) }}
                        </p>
                    </div>
                    <div class="bg-brand-50 dark:bg-brand-900/20 dark:bg-amber-950/20 rounded-2xl p-6 border border-brand-100 dark:border-brand-800/30">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-10 h-10 rounded-xl bg-brand-100 dark:bg-brand-800 flex items-center justify-center text-xl">⚠️</div>
                            <div>
                                <p class="font-black text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-brand-400 uppercase text-xs">Marginales</p>
                                <p class="text-2xl font-black text-brand-800 dark:text-brand-200 dark:text-amber-300">{{ resumen.resumen?.marginales?.cantidad || 0 }}</p>
                            </div>
                        </div>
                        <p class="text-sm text-brand-600 dark:text-amber-400">
                            Utilidad: {{ formatCurrency(resumen.resumen?.marginales?.utilidad) }}
                        </p>
                    </div>
                    <div class="bg-rose-50 dark:bg-rose-900/20 dark:bg-rose-950/20 rounded-2xl p-6 border border-rose-100 dark:border-rose-800/30">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-800 flex items-center justify-center text-xl">📉</div>
                            <div>
                                <p class="font-black text-rose-800 dark:text-rose-200 dark:text-rose-200 dark:text-rose-400 uppercase text-xs">En Pérdida</p>
                                <p class="text-2xl font-black text-rose-800 dark:text-rose-200 dark:text-rose-300">{{ resumen.resumen?.en_perdida?.cantidad || 0 }}</p>
                            </div>
                        </div>
                        <p class="text-sm text-rose-600 dark:text-rose-400">
                            Pérdida: {{ formatCurrency(resumen.resumen?.en_perdida?.utilidad) }}
                        </p>
                    </div>
                </div>

                <!-- Tabla de Pólizas -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-800/50 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="font-black text-slate-900 dark:text-white uppercase tracking-wider">
                            Detalle por Póliza
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Costo hora técnico: {{ formatCurrency(resumen.costo_hora_tecnico) }}
                        </p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead class="bg-slate-50 dark:bg-slate-800/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide">Póliza</th>
                                    <th class="px-6 py-3 text-left text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide">Cliente</th>
                                    <th class="px-6 py-3 text-right text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide">Ingreso</th>
                                    <th class="px-6 py-3 text-right text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide">Hrs</th>
                                    <th class="px-6 py-3 text-right text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide">Costo</th>
                                    <th class="px-6 py-3 text-right text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide">Utilidad</th>
                                    <th class="px-6 py-3 text-right text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide">Margen</th>
                                    <th class="px-6 py-3 text-center text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                                <tr v-for="p in polizas" :key="p.poliza_id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                                    <td class="px-6 py-4">
                                        <Link :href="route('polizas-servicio.show', p.poliza_id)" class="font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                            {{ p.folio }}
                                        </Link>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ p.plan }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-700 dark:text-slate-200">{{ p.cliente }}</td>
                                    <td class="px-6 py-4 text-right text-sm font-bold text-slate-900 dark:text-white">{{ formatCurrency(p.ingreso_mensual) }}</td>
                                    <td class="px-6 py-4 text-right text-sm text-slate-500 dark:text-slate-400">{{ p.horas_consumidas }}h</td>
                                    <td class="px-6 py-4 text-right text-sm text-rose-500">{{ formatCurrency(p.costo_operativo) }}</td>
                                    <td class="px-6 py-4 text-right text-sm font-bold" :class="p.utilidad >= 0 ? 'text-emerald-600 dark:text-slate-400' : 'text-rose-600 dark:text-rose-400'">
                                        {{ formatCurrency(p.utilidad) }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-bold" :class="getMargenColor(p.margen_porcentaje)">
                                        {{ p.margen_porcentaje }}%
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span :class="['px-2 py-1 rounded-full text-[10px] font-black uppercase border', getClasificacionClass(p.clasificacion)]">
                                            {{ p.clasificacion }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
