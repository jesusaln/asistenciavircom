<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    poliza: Object,
    consumoPorMes: Array,
    ticketsConHoras: Array,
});

const { formatCurrency } = useFormatters();

const formatMes = (fecha) => {
    if (!fecha) return '-';
    return new Date(fecha).toLocaleDateString('es-MX', { year: 'numeric', month: 'long' });
};

const getEstadoBadge = (estado) => {
    const colores = {
        abierto: 'bg-sky-100 text-sky-800 dark:text-sky-200',
        en_progreso: 'bg-brand-100 text-brand-800 dark:text-amber-200',
        pendiente: 'bg-brand-100 text-amber-800',
        resuelto: 'bg-emerald-100 text-emerald-800 dark:text-emerald-200',
        cerrado: 'bg-slate-100 text-slate-800',
    };
    return colores[estado] || 'bg-slate-100 text-slate-800';
};

const totalHorasHistorico = () => {
    return props.consumoPorMes.reduce((sum, m) => sum + parseFloat(m.total_horas || 0), 0);
};

const totalTicketsHistorico = () => {
    return props.consumoPorMes.reduce((sum, m) => sum + parseInt(m.total_tickets || 0), 0);
};
</script>

<template>
    <AppLayout :title="`Historial - ${poliza.nombre}`">
        <Head :title="`Historial Consumo - ${poliza.folio}`" />

        <div class="py-6 min-h-screen bg-[var(--ui-surface)] text-slate-800 dark:text-slate-200 transition-colors">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <Link :href="route('polizas-servicio.show', poliza.id)" class="text-blue-600 dark:text-blue-400 hover:text-sky-800 dark:text-sky-200 dark:hover:text-blue-300 text-sm mb-2 inline-block">
                        ← Volver a la póliza
                    </Link>
                    <div class="flex justify-between items-start">
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ poliza.nombre }}</h1>
                            <p class="text-slate-500 dark:text-slate-400">{{ poliza.cliente?.nombre_razon_social }}</p>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium bg-sky-100 dark:bg-brand-500/10 text-sky-800 dark:text-sky-200 dark:text-blue-400 mt-2">
                                Folio: {{ poliza.folio }}
                            </span>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-slate-500 dark:text-slate-400">Consumo Actual</div>
                            <div class="text-2xl font-black text-blue-600 dark:text-blue-400">
                                {{ poliza.horas_consumidas_mes || 0 }}h
                                <span class="text-lg font-medium text-slate-400 dark:text-slate-500">/ {{ poliza.horas_incluidas_mensual }}h</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resumen Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-4 border border-slate-200 dark:border-slate-800/50 border-l-4 border-l-blue-500 dark:border-l-blue-500">
                        <div class="text-2xl font-black text-blue-600 dark:text-blue-400">{{ poliza.horas_incluidas_mensual || '∞' }}h</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold">Horas Incluidas/Mes</div>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-4 border border-slate-200 dark:border-slate-800/50 border-l-4 border-l-emerald-500 dark:border-l-emerald-500">
                        <div class="text-2xl font-black text-emerald-600 dark:text-slate-400">{{ totalHorasHistorico().toFixed(1) }}h</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold">Total Histórico</div>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-4 border border-slate-200 dark:border-slate-800/50 border-l-4 border-l-purple-500 dark:border-l-purple-500">
                        <div class="text-2xl font-black text-purple-600 dark:text-purple-400">{{ totalTicketsHistorico() }}</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold">Tickets Atendidos</div>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-4 border border-slate-200 dark:border-slate-800/50 border-l-4 border-l-brand-500 dark:border-l-orange-500">
                        <div class="text-2xl font-black text-brand-600 dark:text-orange-400">{{ formatCurrency(poliza.costo_hora_excedente) }}</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold">Costo Hora Extra</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Consumo por Mes -->
                    <div class="lg:col-span-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-800/50 rounded-2xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-[var(--ui-surface)] dark:bg-black/50">
                            <h2 class="font-bold text-slate-900 dark:text-white">📊 Consumo por Mes</h2>
                        </div>
                        <div class="divide-y divide-slate-200 dark:divide-slate-800 max-h-96 overflow-y-auto custom-scrollbar">
                            <div v-for="mes in consumoPorMes" :key="mes.mes" class="px-6 py-4">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <div class="font-semibold text-slate-900 dark:text-slate-200 capitalize">{{ formatMes(mes.mes) }}</div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400">{{ mes.total_tickets }} tickets</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ parseFloat(mes.total_horas).toFixed(1) }}h</div>
                                    </div>
                                </div>
                                <div class="mt-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2">
                                    <div 
                                        class="h-2 rounded-full bg-brand-500 transition-all"
                                        :style="{ width: Math.min((parseFloat(mes.total_horas) / poliza.horas_incluidas_mensual) * 100, 100) + '%' }"
                                    ></div>
                                </div>
                            </div>
                            <div v-if="consumoPorMes.length === 0" class="px-6 py-8 text-center text-slate-400 dark:text-slate-500">
                                No hay historial de consumo
                            </div>
                        </div>
                    </div>

                    <!-- Tickets con Horas -->
                    <div class="lg:col-span-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-800/50 rounded-2xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-[var(--ui-surface)] dark:bg-black/50">
                            <h2 class="font-bold text-slate-900 dark:text-white">🎫 Detalle de Tickets con Horas</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                <thead class="bg-slate-50 dark:bg-slate-800/50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Ticket</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Título</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Técnico</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Estado</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Horas</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Fecha</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                                    <tr v-for="t in ticketsConHoras" :key="t.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                        <td class="px-4 py-3">
                                            <Link :href="route('soporte.show', t.id)" class="font-mono text-blue-600 dark:text-blue-400 hover:text-sky-800 dark:text-sky-200 dark:hover:text-blue-300 text-sm font-bold">
                                                {{ t.folio }}
                                            </Link>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-900 dark:text-slate-200 max-w-xs truncate">{{ t.titulo }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400">{{ t.tecnico }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span :class="['px-2 py-0.5 text-xs font-bold rounded-full', getEstadoBadge(t.estado)]">
                                                {{ t.estado }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="font-bold text-blue-600 dark:text-blue-400">{{ t.horas }}h</span>
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm text-slate-500 dark:text-slate-400">{{ t.fecha }}</td>
                                    </tr>
                                    <tr v-if="ticketsConHoras.length === 0">
                                        <td colspan="6" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">
                                            No hay tickets con horas registradas
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
