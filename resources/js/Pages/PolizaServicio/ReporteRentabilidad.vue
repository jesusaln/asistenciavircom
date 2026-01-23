<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    resumen: Object,
    polizas: Array,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value || 0);
};

const getClasificacionClass = (clasificacion) => {
    switch (clasificacion) {
        case 'rentable':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'marginal':
            return 'bg-amber-100 text-amber-700 border-amber-200';
        case 'perdida':
            return 'bg-red-100 text-red-700 border-red-200';
        default:
            return 'bg-gray-100 text-gray-700';
    }
};

const getMargenColor = (margen) => {
    if (margen >= 50) return 'text-emerald-600';
    if (margen >= 30) return 'text-emerald-500';
    if (margen >= 0) return 'text-amber-500';
    return 'text-red-500';
};
</script>

<template>
    <Head title="Reporte de Rentabilidad" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-black text-xl text-gray-800 dark:text-gray-100 dark:text-white uppercase tracking-tight">
                        📊 Reporte de Rentabilidad
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">
                        Análisis de ingresos vs costos operativos por póliza
                    </p>
                </div>
                <Link :href="route('polizas-servicio.dashboard')" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-gray-200 dark:hover:bg-gray-600 transition-all">
                    ← Volver al Dashboard
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                
                <!-- KPIs Principales -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Ingresos</p>
                        <p class="text-2xl font-black text-gray-900 dark:text-white dark:text-white">{{ formatCurrency(resumen.total_ingresos) }}</p>
                    </div>
                    <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Costos</p>
                        <p class="text-2xl font-black text-red-500">{{ formatCurrency(resumen.total_costos) }}</p>
                    </div>
                    <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Utilidad Neta</p>
                        <p class="text-2xl font-black" :class="resumen.utilidad_neta >= 0 ? 'text-emerald-600' : 'text-red-600'">
                            {{ formatCurrency(resumen.utilidad_neta) }}
                        </p>
                    </div>
                    <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Margen Promedio</p>
                        <p class="text-2xl font-black" :class="getMargenColor(resumen.margen_promedio)">
                            {{ resumen.margen_promedio }}%
                        </p>
                    </div>
                </div>

                <!-- Resumen por Clasificación -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl p-6 border border-emerald-100 dark:border-emerald-800">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-800 flex items-center justify-center text-xl">💰</div>
                            <div>
                                <p class="font-black text-emerald-700 dark:text-emerald-400 uppercase text-xs">Rentables</p>
                                <p class="text-2xl font-black text-emerald-800 dark:text-emerald-300">{{ resumen.resumen?.rentables?.cantidad || 0 }}</p>
                            </div>
                        </div>
                        <p class="text-sm text-emerald-600 dark:text-emerald-400">
                            Utilidad: {{ formatCurrency(resumen.resumen?.rentables?.utilidad) }}
                        </p>
                    </div>
                    <div class="bg-amber-50 dark:bg-amber-900/20 rounded-2xl p-6 border border-amber-100 dark:border-amber-800">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-800 flex items-center justify-center text-xl">⚠️</div>
                            <div>
                                <p class="font-black text-amber-700 dark:text-amber-400 uppercase text-xs">Marginales</p>
                                <p class="text-2xl font-black text-amber-800 dark:text-amber-300">{{ resumen.resumen?.marginales?.cantidad || 0 }}</p>
                            </div>
                        </div>
                        <p class="text-sm text-amber-600 dark:text-amber-400">
                            Utilidad: {{ formatCurrency(resumen.resumen?.marginales?.utilidad) }}
                        </p>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/20 rounded-2xl p-6 border border-red-100 dark:border-red-800">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-800 flex items-center justify-center text-xl">📉</div>
                            <div>
                                <p class="font-black text-red-700 dark:text-red-400 uppercase text-xs">En Pérdida</p>
                                <p class="text-2xl font-black text-red-800 dark:text-red-300">{{ resumen.resumen?.en_perdida?.cantidad || 0 }}</p>
                            </div>
                        </div>
                        <p class="text-sm text-red-600 dark:text-red-400">
                            Pérdida: {{ formatCurrency(resumen.resumen?.en_perdida?.utilidad) }}
                        </p>
                    </div>
                </div>

                <!-- Tabla de Pólizas -->
                <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="font-black text-gray-900 dark:text-white dark:text-white uppercase tracking-tight">
                            Detalle por Póliza
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">
                            Costo hora técnico: {{ formatCurrency(resumen.costo_hora_tecnico) }}
                        </p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-slate-950 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-[10px] font-black text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-widest">Póliza</th>
                                    <th class="px-6 py-3 text-left text-[10px] font-black text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-widest">Cliente</th>
                                    <th class="px-6 py-3 text-right text-[10px] font-black text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-widest">Ingreso</th>
                                    <th class="px-6 py-3 text-right text-[10px] font-black text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-widest">Hrs</th>
                                    <th class="px-6 py-3 text-right text-[10px] font-black text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-widest">Costo</th>
                                    <th class="px-6 py-3 text-right text-[10px] font-black text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-widest">Utilidad</th>
                                    <th class="px-6 py-3 text-right text-[10px] font-black text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-widest">Margen</th>
                                    <th class="px-6 py-3 text-center text-[10px] font-black text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-widest">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr v-for="p in polizas" :key="p.poliza_id" class="hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="px-6 py-4">
                                        <Link :href="route('polizas-servicio.show', p.poliza_id)" class="font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                            {{ p.folio }}
                                        </Link>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ p.plan }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ p.cliente }}</td>
                                    <td class="px-6 py-4 text-right text-sm font-bold text-gray-900 dark:text-white dark:text-white">{{ formatCurrency(p.ingreso_mensual) }}</td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-600 dark:text-gray-300 dark:text-gray-400">{{ p.horas_consumidas }}h</td>
                                    <td class="px-6 py-4 text-right text-sm text-red-500">{{ formatCurrency(p.costo_operativo) }}</td>
                                    <td class="px-6 py-4 text-right text-sm font-bold" :class="p.utilidad >= 0 ? 'text-emerald-600' : 'text-red-600'">
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
