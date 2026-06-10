<template>
    <Head title="Historial de Pagos de Comisiones" />

    <div class="w-full px-6 py-8 animate-fade-in">
        <!-- Header (Standardized) -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-6">
                <Link href="/comisiones" class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-brand-500 hover:text-white flex items-center justify-center transition-all duration-300 shadow-sm">
                    <FontAwesomeIcon :icon="['fas', 'arrow-left']" class="w-4 h-4" />
                </Link>
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-400 to-brand-500 flex items-center justify-center text-white shadow-xl shadow-indigo-500/20">
                        <FontAwesomeIcon :icon="['fas', 'history']" class="h-6 w-6" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">Historial de Pagos</h1>
                        <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mt-1">Registro histórico de comisiones liquidadas</p>
                    </div>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="flex items-center gap-4">
                <select v-model="filtroEstado" @change="filtrar" class="px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-xl text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-500">
                    <option value="">Todos los estados</option>
                    <option value="pagado">Pagado</option>
                    <option value="parcial">Parcial</option>
                    <option value="pendiente">Pendiente</option>
                </select>
            </div>
        </div>

        <!-- Tabla de pagos -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Vendedor</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Periodo</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Comisión</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pagado</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Fecha Pago</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                        <tr v-if="pagos.data.length === 0">
                            <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                                No hay pagos registrados
                            </td>
                        </tr>
                        <tr v-for="pago in pagos.data" :key="pago.id" class="hover:bg-white">
                            <td class="px-6 py-4 whitespace-nowrap text-slate-500">#{{ pago.id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-slate-900">
                                {{ pago.vendedor?.name || 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-slate-500">
                                {{ formatFecha(pago.periodo_inicio) }} - {{ formatFecha(pago.periodo_fin) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-slate-900 dark:text-white font-medium">
                                ${{ formatMonto(pago.monto_comision) }}
                            </td>
                             <td class="px-6 py-4 whitespace-nowrap text-right text-brand-600 dark:text-brand-400 font-bold">
                                ${{ formatMonto(pago.monto_pagado) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span v-if="pago.estado === 'pagado'" class="px-2.5 py-1 rounded-xl text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                                    Pagado
                                </span>
                                <span v-else-if="pago.estado === 'parcial'" class="px-2.5 py-1 rounded-xl text-[10px] font-bold uppercase bg-brand-50 text-brand-800 dark:bg-brand-900/30 dark:text-brand-300">
                                    Parcial
                                </span>
                                <span v-else class="px-2.5 py-1 rounded-xl text-[10px] font-bold uppercase bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400">
                                    Pendiente
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-slate-500">
                                {{ pago.fecha_pago ? formatFecha(pago.fecha_pago) : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <a :href="`/comisiones/recibo/${pago.id}`" target="_blank" class="p-2 rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200 inline-flex" title="Descargar recibo">
                                    <FontAwesomeIcon :icon="['fas', 'file-pdf']" class="w-4 h-4" />
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div v-if="pagos.links && pagos.links.length > 3" class="px-6 py-4 border-t border-slate-100 bg-white/50">
                <nav class="flex items-center justify-center gap-1">
                    <Link
                        v-for="link in pagos.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-2 text-sm rounded-xl transition-colors',
                            link.active ? 'bg-brand-500 text-white' : link.url ? 'text-slate-500 hover:bg-slate-100' : 'text-slate-300 cursor-not-allowed'
                        ]"
                        v-html="link.label"
                    />
                </nav>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

defineOptions({ layout: AppLayout });

const props = defineProps({
    pagos: Object,
    filtros: Object,
});

const filtroEstado = ref(props.filtros?.estado || '');

const formatMonto = (valor) => {
    return Number(valor || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatFecha = (fecha) => {
    if (!fecha) return '-';
    return new Date(fecha).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
};

const filtrar = () => {
    router.get('/comisiones/historial', { estado: filtroEstado.value || undefined }, { preserveState: true });
};
</script>
