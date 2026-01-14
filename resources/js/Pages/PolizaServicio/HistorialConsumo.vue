<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    poliza: Object,
    consumoPorMes: Array,
    ticketsConHoras: Array,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value || 0);
};

const formatMes = (fecha) => {
    if (!fecha) return '-';
    return new Date(fecha).toLocaleDateString('es-MX', { year: 'numeric', month: 'long' });
};

const getEstadoBadge = (estado) => {
    const colores = {
        abierto: 'bg-blue-100 text-blue-800',
        en_progreso: 'bg-yellow-100 text-yellow-800',
        pendiente: 'bg-orange-100 text-orange-800',
        resuelto: 'bg-green-100 text-green-800',
        cerrado: 'bg-gray-100 text-gray-800',
    };
    return colores[estado] || 'bg-gray-100 text-gray-800';
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

        <div class="py-6">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <Link :href="route('polizas-servicio.show', poliza.id)" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">
                        ← Volver a la póliza
                    </Link>
                    <div class="flex justify-between items-start">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">{{ poliza.nombre }}</h1>
                            <p class="text-gray-600">{{ poliza.cliente?.nombre_razon_social }}</p>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mt-2">
                                Folio: {{ poliza.folio }}
                            </span>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-gray-500">Consumo Actual</div>
                            <div class="text-3xl font-black text-blue-600">
                                {{ poliza.horas_consumidas_mes || 0 }}h
                                <span class="text-lg font-medium text-gray-400">/ {{ poliza.horas_incluidas_mensual }}h</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resumen Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-blue-500">
                        <div class="text-2xl font-black text-blue-600">{{ poliza.horas_incluidas_mensual || '∞' }}h</div>
                        <div class="text-xs text-gray-500 uppercase font-semibold">Horas Incluidas/Mes</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-green-500">
                        <div class="text-2xl font-black text-green-600">{{ totalHorasHistorico().toFixed(1) }}h</div>
                        <div class="text-xs text-gray-500 uppercase font-semibold">Total Histórico</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-purple-500">
                        <div class="text-2xl font-black text-purple-600">{{ totalTicketsHistorico() }}</div>
                        <div class="text-xs text-gray-500 uppercase font-semibold">Tickets Atendidos</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-orange-500">
                        <div class="text-2xl font-black text-orange-600">{{ formatCurrency(poliza.costo_hora_excedente) }}</div>
                        <div class="text-xs text-gray-500 uppercase font-semibold">Costo Hora Extra</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Consumo por Mes -->
                    <div class="lg:col-span-1 bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b bg-gradient-to-r from-blue-50 to-indigo-50">
                            <h2 class="font-bold text-gray-900">📊 Consumo por Mes</h2>
                        </div>
                        <div class="divide-y max-h-96 overflow-y-auto">
                            <div v-for="mes in consumoPorMes" :key="mes.mes" class="px-6 py-4">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <div class="font-semibold text-gray-900 capitalize">{{ formatMes(mes.mes) }}</div>
                                        <div class="text-xs text-gray-500">{{ mes.total_tickets }} tickets</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xl font-bold text-blue-600">{{ parseFloat(mes.total_horas).toFixed(1) }}h</div>
                                    </div>
                                </div>
                                <div class="mt-2 w-full bg-gray-100 rounded-full h-2">
                                    <div 
                                        class="h-2 rounded-full bg-blue-500 transition-all"
                                        :style="{ width: Math.min((parseFloat(mes.total_horas) / poliza.horas_incluidas_mensual) * 100, 100) + '%' }"
                                    ></div>
                                </div>
                            </div>
                            <div v-if="consumoPorMes.length === 0" class="px-6 py-8 text-center text-gray-400">
                                No hay historial de consumo
                            </div>
                        </div>
                    </div>

                    <!-- Tickets con Horas -->
                    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b bg-gradient-to-r from-green-50 to-emerald-50">
                            <h2 class="font-bold text-gray-900">🎫 Detalle de Tickets con Horas</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ticket</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Título</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Técnico</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Horas</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="t in ticketsConHoras" :key="t.id" class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <Link :href="route('soporte.show', t.id)" class="font-mono text-blue-600 hover:text-blue-800 text-sm font-bold">
                                                {{ t.folio }}
                                            </Link>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 max-w-xs truncate">{{ t.titulo }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ t.tecnico }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span :class="['px-2 py-0.5 text-xs font-bold rounded-full', getEstadoBadge(t.estado)]">
                                                {{ t.estado }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="font-bold text-blue-600">{{ t.horas }}h</span>
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-500">{{ t.fecha }}</td>
                                    </tr>
                                    <tr v-if="ticketsConHoras.length === 0">
                                        <td colspan="6" class="px-4 py-8 text-center text-gray-400">
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
