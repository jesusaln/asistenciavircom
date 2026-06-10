<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { Head, Link, router } from '@inertiajs/vue3';
import Swal from '@/Utils/Swal';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    stats: Object,
    proximasVencer: Array,
    excesoTickets: Array,
    excesoHoras: Array,
    topConsumo: Array,
    ultimosCobros: Array,
});

const { formatCurrency } = useFormatters();

const getAlertLevel = (dias) => {
    if (dias <= 0) return { bg: 'bg-rose-100', text: 'text-rose-800 dark:text-rose-200', border: 'border-rose-300' };
    if (dias <= 7) return { bg: 'bg-amber-100', text: 'text-orange-800', border: 'border-orange-300' };
    return { bg: 'bg-amber-100', text: 'text-brand-800 dark:text-amber-200', border: 'border-amber-300' };
};

const getConsumoColor = (porcentaje) => {
    if (porcentaje >= 100) return 'bg-brand-500';
    if (porcentaje >= 80) return 'bg-brand-500';
    return 'bg-brand-500';
};

const enviarRecordatorio = async (polizaId) => {
    const result = await Swal.fire({
        title: 'Enviar recordatorio',
        text: '¿Enviar recordatorio de renovación al cliente?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, enviar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#3b82f6',
    });

    if (result.isConfirmed) {
        router.post(route('polizas-servicio.enviar-recordatorio', polizaId));
    }
};

const generarCobro = async (polizaId) => {
    const result = await Swal.fire({
        title: 'Generar cobro',
        text: '¿Generar cobro para esta póliza?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, generar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#3b82f6',
    });

    if (result.isConfirmed) {
        router.post(route('polizas-servicio.generar-cobro', polizaId));
    }
};
</script>

<template>
    <AppLayout title="Dashboard Pólizas">
        <Head title="Dashboard Pólizas de Servicio" />

        <div class="py-6 min-h-screen bg-[var(--ui-surface)] text-slate-800 dark:text-slate-200 transition-colors">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Dashboard de Pólizas</h1>
                        <p class="text-slate-500 dark:text-slate-400 mt-1">Control financiero y alertas operativas</p>
                    </div>
                    <div class="flex gap-3">
                        <Link :href="route('polizas-servicio.rentabilidad')" class="px-4 py-2 bg-emerald-100 dark:bg-brand-500/10 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-slate-400 rounded-xl hover:bg-emerald-200 dark:hover:bg-slate-500/20 transition font-semibold">
                            📊 Rentabilidad
                        </Link>
                        <Link :href="route('polizas-servicio.index')" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 transition font-semibold">
                            📋 Ver Todas
                        </Link>
                        <Link :href="route('polizas-servicio.create')" class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition font-semibold shadow-xl shadow-sky-500/30">
                            + Nueva Póliza
                        </Link>
                    </div>
                </div>

                <!-- KPIs Financieros Premium -->
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-8">
                    <!-- Ingresos Mensuales -->
                    <div class="bg-gradient-to-br from-brand-500 to-brand-600 rounded-xl p-4 text-white shadow-xl col-span-2">
                        <div class="text-2xl font-black">{{ formatCurrency(stats.ingresos_mensuales) }}</div>
                        <div class="text-emerald-100 text-xs font-semibold uppercase tracking-wider mt-1">Ingresos Recurrentes/Mes</div>
                        <div class="text-[10px] text-emerald-200 mt-2 opacity-80">
                            Proyección Anual: <span class="font-bold">{{ formatCurrency(stats.ingresos_anuales_proyectados) }}</span>
                        </div>
                    </div>
                    
                    <!-- Pólizas Activas -->
                    <div class="bg-gradient-to-br from-brand-500 to-brand-600 rounded-xl p-4 text-white shadow-xl">
                        <div class="text-2xl font-black">{{ stats.total_activas }}</div>
                        <div class="text-blue-100 text-xs font-semibold uppercase tracking-wider">Activas</div>
                    </div>
                    
                    <!-- Cobros Pendientes (ALERTA) -->
                    <div :class="['rounded-xl p-4 shadow-xl', stats.cobros_pendientes > 0 ? 'bg-gradient-to-br from-brand-500 to-brand-600 text-white animate-pulse' : 'bg-white dark:bg-slate-800 border-2 border-slate-200 dark:border-white/10 text-slate-500 dark:text-slate-400']">
                        <div :class="['text-2xl font-black', stats.cobros_pendientes > 0 ? 'text-white' : 'text-slate-400 dark:text-slate-500']">
                            {{ formatCurrency(stats.cobros_pendientes) }}
                        </div>
                        <div :class="['text-xs font-semibold uppercase tracking-wider', stats.cobros_pendientes > 0 ? 'text-rose-100' : 'text-slate-500 dark:text-slate-400']">
                            Cobros Pendientes
                        </div>
                        <div v-if="stats.polizas_con_deuda > 0" class="text-[10px] mt-2 bg-rose-900/30 rounded-xl px-2 py-1 text-center">
                            ⚠️ {{ stats.polizas_con_deuda }} pólizas con deuda
                        </div>
                    </div>
                    
                    <!-- Ingresos por Excedentes -->
                    <div class="bg-gradient-to-br from-brand-500 to-brand-600 rounded-xl p-4 text-white shadow-xl">
                        <div class="text-2xl font-black">{{ formatCurrency(stats.ingresos_excedentes) }}</div>
                        <div class="text-brand-100 text-xs font-semibold uppercase tracking-wider">Por Cobrar (Excedentes)</div>
                    </div>
                    
                    <!-- Tasa de Retención -->
                    <div class="bg-gradient-to-br from-brand-500 to-brand-600 rounded-xl p-4 text-white shadow-xl">
                        <div class="text-2xl font-black">{{ stats.tasa_retencion }}%</div>
                        <div class="text-purple-100 text-xs font-semibold uppercase tracking-wider">Tasa Retención</div>
                    </div>
                </div>

                <!-- Alertas Críticas -->
                <div v-if="stats.polizas_con_deuda > 0 || stats.con_exceso_horas > 0 || stats.con_exceso_tickets > 0" class="mb-6 p-4 bg-rose-50 dark:bg-rose-900/20 dark:bg-rose-950/20 border-2 border-rose-200 dark:border-rose-800/30 dark:border-rose-500/30 rounded-xl">
                    <h3 class="font-bold text-rose-800 dark:text-rose-200 dark:text-rose-400 mb-3 flex items-center gap-2">
                        🚨 Alertas que Requieren Atención Inmediata
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div v-if="stats.polizas_con_deuda > 0" class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-rose-200 dark:border-rose-800/30 dark:border-rose-500/30">
                            <div class="text-2xl font-black text-rose-600 dark:text-rose-400">{{ stats.polizas_con_deuda }}</div>
                            <div class="text-xs text-rose-800 dark:text-rose-200 dark:text-rose-200 dark:text-rose-300 font-medium">Pólizas con cobros vencidos</div>
                            <div class="text-[10px] text-rose-400 dark:text-rose-500 mt-1">Considere bloquear soporte hasta regularizar</div>
                        </div>
                        <div v-if="stats.con_exceso_horas > 0" class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-purple-200 dark:border-purple-500/30">
                            <div class="text-2xl font-black text-purple-600 dark:text-purple-400">{{ stats.con_exceso_horas }}</div>
                            <div class="text-xs text-purple-700 dark:text-purple-300 font-medium">Exceden horas incluidas</div>
                            <div class="text-[10px] text-purple-400 dark:text-purple-500 mt-1">Facturar horas adicionales</div>
                        </div>
                        <div v-if="stats.con_exceso_tickets > 0" class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-orange-200 dark:border-brand-500/30">
                            <div class="text-2xl font-black text-brand-600 dark:text-orange-400">{{ stats.con_exceso_tickets }}</div>
                            <div class="text-xs text-brand-700 dark:text-orange-300 font-medium">Exceden límite de tickets</div>
                            <div class="text-[10px] text-orange-400 dark:text-brand-500 mt-1">Contactar para upgrade de plan</div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Próximas a Vencer -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700/50 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700/50 bg-gradient-to-r from-orange-50 to-brand-50 dark:from-slate-800 dark:to-slate-850">
                            <h2 class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                ⏰ Próximas a Vencer
                                <span v-if="proximasVencer.length" class="px-2 py-0.5 bg-brand-500 text-white text-xs rounded-full">{{ proximasVencer.length }}</span>
                            </h2>
                        </div>
                        <div class="divide-y divide-slate-200 dark:divide-slate-700/50 max-h-96 overflow-y-auto custom-scrollbar">
                            <div v-for="p in proximasVencer" :key="p.id" class="px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <Link :href="route('polizas-servicio.show', p.id)" class="font-bold text-slate-800 dark:text-slate-100 hover:text-brand-600 dark:hover:text-blue-400">
                                            {{ p.nombre }}
                                        </Link>
                                        <div class="text-sm text-slate-500 dark:text-slate-400">{{ p.cliente }}</div>
                                        <div class="text-xs text-emerald-600 dark:text-slate-400 font-bold mt-1">{{ formatCurrency(p.monto_mensual) }}/mes</div>
                                    </div>
                                    <div class="text-right">
                                        <span :class="['px-2 py-1 rounded-xl text-xs font-bold', getAlertLevel(p.dias_restantes).bg, getAlertLevel(p.dias_restantes).text]">
                                            {{ p.dias_restantes <= 0 ? '¡VENCIDA!' : p.dias_restantes + ' días' }}
                                        </span>
                                        <div class="text-xs text-slate-400 dark:text-slate-500 mt-1">{{ p.fecha_fin }}</div>
                                        <button @click="enviarRecordatorio(p.id)" class="mt-2 text-[10px] bg-sky-100 dark:bg-brand-500/10 text-sky-800 dark:text-sky-200 dark:text-blue-400 px-2 py-1 rounded-xl font-bold hover:bg-blue-200 dark:hover:bg-slate-500/20 transition">
                                            📧 Recordar
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-if="proximasVencer.length === 0" class="px-6 py-8 text-center text-slate-400 dark:text-slate-500">
                                ✅ No hay pólizas próximas a vencer
                            </div>
                        </div>
                    </div>

                    <!-- Exceso de Horas (con botón de cobro) -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700/50 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700/50 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-slate-800 dark:to-slate-850">
                            <h2 class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                ⏱️ Exceso de Horas (Facturar)
                                <span v-if="excesoHoras.length" class="px-2 py-0.5 bg-purple-500 text-white text-xs rounded-full">{{ excesoHoras.length }}</span>
                            </h2>
                        </div>
                        <div class="divide-y divide-slate-200 dark:divide-slate-700/50 max-h-96 overflow-y-auto custom-scrollbar">
                            <div v-for="p in excesoHoras" :key="p.id" class="px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <Link :href="route('polizas-servicio.show', p.id)" class="font-bold text-slate-800 dark:text-slate-100 hover:text-brand-600 dark:hover:text-blue-400">
                                            {{ p.nombre }}
                                        </Link>
                                        <div class="text-sm text-slate-500 dark:text-slate-400">{{ p.cliente }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-black text-purple-600 dark:text-purple-400">{{ p.horas_usadas }}h / {{ p.horas_incluidas }}h</div>
                                        <div v-if="p.costo_extra" class="text-xs text-brand-600 font-semibold">
                                            Excedente: {{ formatCurrency((p.horas_usadas - p.horas_incluidas) * p.costo_extra) }}
                                        </div>
                                        <button @click="generarCobro(p.id)" class="mt-2 text-[10px] bg-emerald-100 dark:bg-brand-500/10 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-slate-400 px-2 py-1 rounded-xl font-bold hover:bg-emerald-200 dark:hover:bg-slate-500/20 transition">
                                            💰 Generar Cobro
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-if="excesoHoras.length === 0" class="px-6 py-8 text-center text-slate-400 dark:text-slate-500">
                                ✅ No hay pólizas con exceso de horas
                            </div>
                        </div>
                    </div>

                    <!-- Exceso de Tickets -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700/50 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700/50 bg-gradient-to-r from-rose-50 to-pink-50 dark:from-slate-800 dark:to-slate-850">
                            <h2 class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                🎫 Exceso de Tickets
                                <span v-if="excesoTickets.length" class="px-2 py-0.5 bg-brand-500 text-white text-xs rounded-full">{{ excesoTickets.length }}</span>
                            </h2>
                        </div>
                        <div class="divide-y divide-slate-200 dark:divide-slate-700/50 max-h-96 overflow-y-auto custom-scrollbar">
                            <div v-for="p in excesoTickets" :key="p.id" class="px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <Link :href="route('polizas-servicio.show', p.id)" class="font-bold text-slate-800 dark:text-slate-100 hover:text-brand-600 dark:hover:text-blue-400">
                                            {{ p.nombre }}
                                        </Link>
                                        <div class="text-sm text-slate-500 dark:text-slate-400">{{ p.cliente }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-black text-rose-600 dark:text-rose-400">{{ p.tickets_usados }}/{{ p.limite }}</div>
                                        <div class="text-xs text-slate-400 dark:text-slate-500">{{ p.porcentaje }}% usado</div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="excesoTickets.length === 0" class="px-6 py-8 text-center text-slate-400 dark:text-slate-500">
                                ✅ No hay pólizas con exceso de tickets
                            </div>
                        </div>
                    </div>

                    <!-- Top Consumo de Horas -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700/50 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700/50 bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-slate-800 dark:to-slate-850">
                            <h2 class="font-bold text-slate-900 dark:text-white">📊 Top Consumo Mensual</h2>
                        </div>
                        <div class="divide-y divide-slate-200 dark:divide-slate-700/50 max-h-96 overflow-y-auto custom-scrollbar">
                            <div v-for="(p, index) in topConsumo" :key="p.id" class="px-6 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                                <div class="flex justify-between items-center mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="w-10 h-10 rounded-full bg-sky-100 dark:bg-brand-500/20 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xs font-bold">
                                            {{ index + 1 }}
                                        </span>
                                        <Link :href="route('polizas-servicio.show', p.id)" class="font-semibold text-slate-800 dark:text-slate-100 hover:text-brand-600 dark:hover:text-blue-400 text-sm">
                                            {{ p.nombre }}
                                        </Link>
                                    </div>
                                    <span class="text-sm font-bold text-slate-500 dark:text-slate-400">{{ p.horas_usadas }}h</span>
                                </div>
                                <div class="ml-9">
                                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2">
                                        <div 
                                            :class="['h-2 rounded-full transition-all', getConsumoColor(p.porcentaje)]"
                                            :style="{ width: Math.min(p.porcentaje, 100) + '%' }"
                                        ></div>
                                    </div>
                                    <div class="flex justify-between text-xs text-slate-400 dark:text-slate-500 mt-1">
                                        <span>{{ p.cliente }}</span>
                                        <span>{{ p.porcentaje }}%</span>
                                    </div>
                                </div>
                            </div>
                            <div v-if="topConsumo.length === 0" class="px-6 py-8 text-center text-slate-400 dark:text-slate-500">
                                No hay consumo de horas registrado este mes
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Últimos Cobros Generados -->
                <div class="mt-6 bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700/50 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700/50 bg-gradient-to-r from-emerald-50 to-emerald-50 dark:from-slate-800 dark:to-slate-850">
                        <h2 class="font-bold text-slate-900 dark:text-white">💰 Últimos Cobros Generados</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead class="bg-slate-50 dark:bg-slate-800/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Fecha</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Cliente</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Monto</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                                <tr v-for="c in ultimosCobros" :key="c.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                    <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400">{{ c.fecha }}</td>
                                    <td class="px-6 py-4 text-sm font-medium text-slate-900 dark:text-slate-100">{{ c.cliente }}</td>
                                    <td class="px-6 py-4 text-sm font-bold text-slate-900 dark:text-slate-100 text-right">{{ formatCurrency(c.monto) }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span :class="[
                                            'px-2.5 py-0.5 text-xs font-bold rounded-full',
                                            c.estado === 'pagado' ? 'bg-emerald-100 text-emerald-800 dark:text-emerald-200 dark:bg-brand-500/10 dark:text-slate-400' :
                                            c.estado === 'pendiente' ? 'bg-brand-100 text-brand-800 dark:text-brand-200 dark:bg-brand-500/10 dark:text-amber-400' :
                                            c.estado === 'vencido' ? 'bg-rose-100 text-rose-800 dark:text-rose-200 dark:bg-brand-500/10 dark:text-rose-400' :
                                            'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-400'
                                        ]">
                                            {{ c.estado }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="ultimosCobros.length === 0">
                                    <td colspan="4" class="px-6 py-8 text-center text-slate-400 dark:text-slate-500">
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
