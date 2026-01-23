<template>
    <Head :title="`Comisiones - ${vendedor.nombre}`" />

    <div class="w-full px-6 py-8 animate-fade-in">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-4">
                <Link href="/comisiones" class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 transition-colors">
                    <FontAwesomeIcon :icon="['fas', 'arrow-left']" class="w-4 h-4 text-gray-600 dark:text-gray-300" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ vendedor.nombre }}</h1>
                    <span :class="vendedor.type_label === 'Técnico' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'" class="px-2 py-1 rounded-full text-xs font-medium">
                        {{ vendedor.type_label }}
                    </span>
                </div>
            </div>
            <p class="text-gray-500 dark:text-gray-400">
                Periodo: {{ filtros.fecha_inicio }} - {{ filtros.fecha_fin }}
            </p>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
            <div class="bg-white dark:bg-slate-900 p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Ventas</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ detalle.num_ventas }}</p>
            </div>
            <div class="bg-white dark:bg-slate-900 p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-sm text-gray-500 dark:text-gray-400">Monto Vendido</p>
                <p class="text-2xl font-bold text-blue-600">${{ formatMonto(detalle.total_ventas) }}</p>
            </div>
            <div class="bg-white dark:bg-slate-900 p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-sm text-gray-500 dark:text-gray-400">Comisión Total</p>
                <p class="text-2xl font-bold text-green-600">${{ formatMonto(detalle.total_comision) }}</p>
            </div>
        </div>

        <!-- Tabla de detalles -->
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-white dark:bg-slate-900/50">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Detalle por Venta</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
                    <thead class="bg-white dark:bg-slate-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Venta</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Cliente</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Total Venta</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Comisión Prod.</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Comisión Serv.</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Total Comisión</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-100">
                        <tr v-if="!detalle.detalles || detalle.detalles.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                No hay ventas en este periodo
                            </td>
                        </tr>
                        <tr v-for="venta in detalle.detalles" :key="venta.venta_id" class="hover:bg-white dark:bg-slate-900">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900 dark:text-white">
                                {{ venta.numero_venta }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600 dark:text-gray-300">
                                {{ formatFecha(venta.fecha) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600 dark:text-gray-300">
                                {{ venta.cliente }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-gray-900 dark:text-white">
                                ${{ formatMonto(venta.total_venta) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-blue-600">
                                ${{ formatMonto(venta.comision_productos) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-purple-600">
                                ${{ formatMonto(venta.comision_servicios) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right font-bold text-green-600">
                                ${{ formatMonto(venta.comision_total) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-white dark:bg-slate-900">
                        <tr>
                            <td colspan="3" class="px-6 py-4 font-bold text-gray-900 dark:text-white">TOTAL</td>
                            <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">${{ formatMonto(detalle.total_ventas) }}</td>
                            <td colspan="2"></td>
                            <td class="px-6 py-4 text-right font-bold text-green-600">${{ formatMonto(detalle.total_comision) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

defineOptions({ layout: AppLayout });

const props = defineProps({
    vendedor: Object,
    detalle: Object,
    filtros: Object,
});

const formatMonto = (valor) => {
    return Number(valor || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatFecha = (fecha) => {
    if (!fecha) return '-';
    return new Date(fecha).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
};
</script>
