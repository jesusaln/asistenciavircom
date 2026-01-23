<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import ClientLayout from '../Layout/ClientLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
    poliza: Object,
    consumos: Array,
    stats: Object,
    filtros: Object,
    empresa: Object,
});

const formMes = ref(props.filtros.mes);

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-MX', { 
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
};

const getTipoBadge = (tipo) => {
    const tipos = {
        ticket: 'bg-blue-50 text-blue-600 border-blue-100',
        visita: 'bg-purple-50 text-purple-600 border-purple-100',
        hora: 'bg-amber-50 text-amber-600 border-amber-100',
    };
    return tipos[tipo] || 'bg-gray-50 dark:bg-slate-950 text-gray-600 dark:text-gray-300 border-gray-100';
};

const getTipoIcon = (tipo) => {
    const iconos = {
        ticket: 'ticket-alt',
        visita: 'car',
        hora: 'clock',
    };
    return iconos[tipo] || 'info-circle';
};

watch(formMes, (newMes) => {
    router.get(route('portal.polizas.historial', props.poliza.id), { mes: newMes }, {
        preserveState: true,
        preserveScroll: true,
        only: ['consumos', 'stats', 'filtros']
    });
});

const mesesDisponibles = () => {
    const meses = [];
    const hoy = new Date();
    for (let i = 0; i < 12; i++) {
        const d = new Date(hoy.getFullYear(), hoy.getMonth() - i, 1);
        const val = d.toISOString().substring(0, 7);
        const label = d.toLocaleDateString('es-MX', { month: 'long', year: 'numeric' });
        meses.push({ val, label });
    }
    return meses;
};
</script>

<template>
    <Head :title="`Historial - ${poliza.folio}`" />

    <ClientLayout :empresa="empresa">
        <div class="px-2 sm:px-0">
            <!-- Header -->
            <div class="mb-8">
                <Link :href="route('portal.polizas.show', poliza.id)" class="text-xs uppercase tracking-widest font-bold text-gray-400 hover:text-[var(--color-primary)] mb-4 inline-block transition-colors">
                    ← Volver a la Póliza
                </Link>
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 bg-white dark:bg-slate-900 p-8 rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white tracking-tight">Historial de Consumo</h1>
                        <p class="text-gray-500 dark:text-gray-400 font-medium text-sm mt-1">
                            Póliza: <strong class="text-gray-900 dark:text-white">{{ poliza.nombre }}</strong> ({{ poliza.folio }})
                        </p>
                    </div>
                    
                    <div class="flex items-end gap-2 w-full md:w-auto">
                        <div class="w-full md:w-64">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Seleccionar Mes</label>
                            <select v-model="formMes" class="w-full bg-gray-50 dark:bg-slate-950 border-gray-200 dark:border-slate-800 rounded-xl font-bold text-gray-700 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] transition-all">
                                <option v-for="m in mesesDisponibles()" :key="m.val" :value="m.val">
                                    {{ m.label.charAt(0).toUpperCase() + m.label.slice(1) }}
                                </option>
                            </select>
                        </div>
                        <a :href="route('portal.polizas.reporte.pdf', { poliza: poliza.id, mes: formMes.split('-')[1], anio: formMes.split('-')[0] })" target="_blank" class="px-6 py-3 bg-white dark:bg-slate-900 border-2 border-slate-100 text-slate-600 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-slate-50 transition-all flex items-center gap-2 h-[42px]">
                            <font-awesome-icon icon="file-pdf" />
                            <span class="hidden sm:inline">Reporte</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-white dark:bg-slate-900 p-6 rounded-[2rem] border border-gray-100 shadow-lg shadow-gray-200/30">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Ahorro Total</p>
                    <p class="text-2xl font-black text-emerald-600">{{ formatCurrency(stats.total_ahorro) }}</p>
                </div>
                <div class="bg-white dark:bg-slate-900 p-6 rounded-[2rem] border border-gray-100 shadow-lg shadow-gray-200/30">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Tickets</p>
                    <p class="text-2xl font-black text-blue-600">{{ stats.total_tickets }}</p>
                </div>
                <div class="bg-white dark:bg-slate-900 p-6 rounded-[2rem] border border-gray-100 shadow-lg shadow-gray-200/30">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Visitas</p>
                    <p class="text-2xl font-black text-purple-600">{{ stats.total_visitas }}</p>
                </div>
                <div class="bg-white dark:bg-slate-900 p-6 rounded-[2rem] border border-gray-100 shadow-lg shadow-gray-200/30">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Horas</p>
                    <p class="text-2xl font-black text-amber-600">{{ stats.total_horas }}</p>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl shadow-gray-200/50 border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-slate-950/50 border-b border-gray-100">
                                <th class="px-8 py-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Fecha y Hora</th>
                                <th class="px-8 py-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Servicio</th>
                                <th class="px-8 py-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Detalle</th>
                                <th class="px-8 py-6 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Ahorro Est.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="consumo in consumos" :key="consumo.id" class="hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950/50 transition-colors group">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-2 h-2 rounded-full bg-emerald-500 group-hover:scale-125 transition-transform" v-if="new Date(consumo.fecha_consumo) > new Date(Date.now() - 86400000)"></div>
                                        <span class="text-sm font-bold text-gray-700">{{ formatDate(consumo.fecha_consumo) }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <span :class="['px-3 py-1.5 text-[10px] font-black rounded-xl border uppercase tracking-widest flex items-center gap-2 w-fit', getTipoBadge(consumo.tipo)]">
                                        <font-awesome-icon :icon="getTipoIcon(consumo.tipo)" />
                                        {{ consumo.tipo }}
                                    </span>
                                </td>
                                <td class="px-8 py-6">
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300 leading-relaxed max-w-md">
                                        {{ consumo.descripcion }}
                                    </p>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <span class="text-sm font-black text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-xl">
                                        + {{ formatCurrency(consumo.ahorro) }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="consumos.length === 0">
                                <td colspan="4" class="px-8 py-20 text-center">
                                    <div class="max-w-xs mx-auto">
                                        <div class="text-4xl mb-4">📭</div>
                                        <h3 class="text-gray-900 dark:text-white font-black text-lg">Sin consumos</h3>
                                        <p class="text-gray-500 dark:text-gray-400 font-medium text-sm mt-2">No se registraron consumos de servicios para el periodo seleccionado.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Total Footer -->
            <div class="mt-8 flex justify-end" v-if="consumos.length > 0">
                <div class="bg-gray-900 text-white p-8 rounded-[2rem] shadow-2xl flex items-center gap-8 border border-gray-800">
                    <div class="text-right">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Ahorro Estimado Total</p>
                        <p class="text-3xl font-black text-emerald-400">{{ formatCurrency(stats.total_ahorro) }}</p>
                        <p class="text-xs text-gray-400 mt-1 font-medium italic">Calculado vs precios de lista sin póliza.</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-emerald-400/10 text-emerald-400 flex items-center justify-center text-xl">
                        💰
                    </div>
                </div>
            </div>
        </div>
    </ClientLayout>
</template>
