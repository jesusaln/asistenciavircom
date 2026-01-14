<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    stats: Object,
    proximasVencer: Array,
    excesoTickets: Array,
    excesoHoras: Array,
    topConsumo: Array,
    ultimosCobros: Array,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value || 0);
};

const getAlertLevel = (dias) => {
    if (dias <= 0) return { bg: 'bg-red-100', text: 'text-red-800', border: 'border-red-300' };
    if (dias <= 7) return { bg: 'bg-orange-100', text: 'text-orange-800', border: 'border-orange-300' };
    return { bg: 'bg-yellow-100', text: 'text-yellow-800', border: 'border-yellow-300' };
};

const getConsumoColor = (porcentaje) => {
    if (porcentaje >= 100) return 'bg-red-500';
    if (porcentaje >= 80) return 'bg-yellow-500';
    return 'bg-green-500';
};
</script>

<template>
    <AppLayout title="Dashboard Pólizas">
        <Head title="Dashboard Pólizas de Servicio" />

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Dashboard de Pólizas</h1>
                        <p class="text-gray-600 mt-1">Monitoreo de alertas, consumo y facturación</p>
                    </div>
                    <div class="flex gap-3">
                        <Link :href="route('polizas-servicio.index')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition font-semibold">
                            📋 Ver Todas
                        </Link>
                        <Link :href="route('polizas-servicio.create')" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold shadow-lg">
                            + Nueva Póliza
                        </Link>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white shadow-lg">
                        <div class="text-3xl font-black">{{ stats.total_activas }}</div>
                        <div class="text-blue-100 text-xs font-semibold uppercase tracking-wider">Pólizas Activas</div>
                    </div>
                    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-4 text-white shadow-lg">
                        <div class="text-2xl font-black">{{ formatCurrency(stats.ingresos_mensuales) }}</div>
                        <div class="text-green-100 text-xs font-semibold uppercase tracking-wider">Ingresos/Mes</div>
                    </div>
                    <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-4 text-white shadow-lg">
                        <div class="text-3xl font-black">{{ proximasVencer.length }}</div>
                        <div class="text-orange-100 text-xs font-semibold uppercase tracking-wider">Por Vencer</div>
                    </div>
                    <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-4 text-white shadow-lg">
                        <div class="text-3xl font-black">{{ stats.con_exceso_tickets }}</div>
                        <div class="text-red-100 text-xs font-semibold uppercase tracking-wider">Exceso Tickets</div>
                    </div>
                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white shadow-lg">
                        <div class="text-3xl font-black">{{ stats.con_exceso_horas }}</div>
                        <div class="text-purple-100 text-xs font-semibold uppercase tracking-wider">Exceso Horas</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Próximas a Vencer -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b bg-gradient-to-r from-orange-50 to-yellow-50">
                            <h2 class="font-bold text-gray-900 flex items-center gap-2">
                                ⏰ Próximas a Vencer
                                <span v-if="proximasVencer.length" class="px-2 py-0.5 bg-orange-500 text-white text-xs rounded-full">{{ proximasVencer.length }}</span>
                            </h2>
                        </div>
                        <div class="divide-y max-h-96 overflow-y-auto">
                            <div v-for="p in proximasVencer" :key="p.id" class="px-6 py-4 hover:bg-gray-50 transition">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <Link :href="route('polizas-servicio.show', p.id)" class="font-bold text-gray-900 hover:text-blue-600">
                                            {{ p.nombre }}
                                        </Link>
                                        <div class="text-sm text-gray-500">{{ p.cliente }}</div>
                                    </div>
                                    <div class="text-right">
                                        <span :class="['px-2 py-1 rounded-lg text-xs font-bold', getAlertLevel(p.dias_restantes).bg, getAlertLevel(p.dias_restantes).text]">
                                            {{ p.dias_restantes <= 0 ? '¡VENCIDA!' : p.dias_restantes + ' días' }}
                                        </span>
                                        <div class="text-xs text-gray-400 mt-1">{{ p.fecha_fin }}</div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="proximasVencer.length === 0" class="px-6 py-8 text-center text-gray-400">
                                ✅ No hay pólizas próximas a vencer
                            </div>
                        </div>
                    </div>

                    <!-- Exceso de Tickets -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b bg-gradient-to-r from-red-50 to-pink-50">
                            <h2 class="font-bold text-gray-900 flex items-center gap-2">
                                🎫 Exceso de Tickets
                                <span v-if="excesoTickets.length" class="px-2 py-0.5 bg-red-500 text-white text-xs rounded-full">{{ excesoTickets.length }}</span>
                            </h2>
                        </div>
                        <div class="divide-y max-h-96 overflow-y-auto">
                            <div v-for="p in excesoTickets" :key="p.id" class="px-6 py-4 hover:bg-gray-50 transition">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <Link :href="route('polizas-servicio.show', p.id)" class="font-bold text-gray-900 hover:text-blue-600">
                                            {{ p.nombre }}
                                        </Link>
                                        <div class="text-sm text-gray-500">{{ p.cliente }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-black text-red-600">{{ p.tickets_usados }}/{{ p.limite }}</div>
                                        <div class="text-xs text-gray-400">{{ p.porcentaje }}%</div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="excesoTickets.length === 0" class="px-6 py-8 text-center text-gray-400">
                                ✅ No hay pólizas con exceso de tickets
                            </div>
                        </div>
                    </div>

                    <!-- Exceso de Horas -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b bg-gradient-to-r from-purple-50 to-indigo-50">
                            <h2 class="font-bold text-gray-900 flex items-center gap-2">
                                ⏱️ Exceso de Horas
                                <span v-if="excesoHoras.length" class="px-2 py-0.5 bg-purple-500 text-white text-xs rounded-full">{{ excesoHoras.length }}</span>
                            </h2>
                        </div>
                        <div class="divide-y max-h-96 overflow-y-auto">
                            <div v-for="p in excesoHoras" :key="p.id" class="px-6 py-4 hover:bg-gray-50 transition">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <Link :href="route('polizas-servicio.show', p.id)" class="font-bold text-gray-900 hover:text-blue-600">
                                            {{ p.nombre }}
                                        </Link>
                                        <div class="text-sm text-gray-500">{{ p.cliente }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-black text-purple-600">{{ p.horas_usadas }}h / {{ p.horas_incluidas }}h</div>
                                        <div v-if="p.costo_extra" class="text-xs text-orange-600 font-semibold">
                                            Extra: {{ formatCurrency(p.costo_extra) }}/hr
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="excesoHoras.length === 0" class="px-6 py-8 text-center text-gray-400">
                                ✅ No hay pólizas con exceso de horas
                            </div>
                        </div>
                    </div>

                    <!-- Top Consumo de Horas -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b bg-gradient-to-r from-blue-50 to-cyan-50">
                            <h2 class="font-bold text-gray-900">📊 Top Consumo Mensual</h2>
                        </div>
                        <div class="divide-y max-h-96 overflow-y-auto">
                            <div v-for="(p, index) in topConsumo" :key="p.id" class="px-6 py-3 hover:bg-gray-50 transition">
                                <div class="flex justify-between items-center mb-2">
                                    <div class="flex items-center gap-3">
                                        <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold">
                                            {{ index + 1 }}
                                        </span>
                                        <Link :href="route('polizas-servicio.show', p.id)" class="font-semibold text-gray-900 hover:text-blue-600 text-sm">
                                            {{ p.nombre }}
                                        </Link>
                                    </div>
                                    <span class="text-sm font-bold text-gray-600">{{ p.horas_usadas }}h</span>
                                </div>
                                <div class="ml-9">
                                    <div class="w-full bg-gray-100 rounded-full h-2">
                                        <div 
                                            :class="['h-2 rounded-full transition-all', getConsumoColor(p.porcentaje)]"
                                            :style="{ width: Math.min(p.porcentaje, 100) + '%' }"
                                        ></div>
                                    </div>
                                    <div class="flex justify-between text-xs text-gray-400 mt-1">
                                        <span>{{ p.cliente }}</span>
                                        <span>{{ p.porcentaje }}%</span>
                                    </div>
                                </div>
                            </div>
                            <div v-if="topConsumo.length === 0" class="px-6 py-8 text-center text-gray-400">
                                No hay consumo de horas registrado este mes
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Últimos Cobros Generados -->
                <div class="mt-6 bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b bg-gradient-to-r from-green-50 to-emerald-50">
                        <h2 class="font-bold text-gray-900">💰 Últimos Cobros Generados</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Monto</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="c in ultimosCobros" :key="c.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ c.fecha }}</td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ c.cliente }}</td>
                                    <td class="px-6 py-4 text-sm font-bold text-gray-900 text-right">{{ formatCurrency(c.monto) }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span :class="[
                                            'px-2 py-1 text-xs font-bold rounded-full',
                                            c.estado === 'pagado' ? 'bg-green-100 text-green-800' :
                                            c.estado === 'pendiente' ? 'bg-yellow-100 text-yellow-800' :
                                            'bg-gray-100 text-gray-800'
                                        ]">
                                            {{ c.estado }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="ultimosCobros.length === 0">
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-400">
                                        No hay cobros generados recientemente
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
