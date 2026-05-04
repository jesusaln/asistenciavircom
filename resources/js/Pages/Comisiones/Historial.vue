<template>
    <Head title="Historial de Pagos de Comisiones" />

    <div class="w-full px-6 py-8 animate-fade-in">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-4">
                <Link href="/comisiones" class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 transition-colors">
                    <FontAwesomeIcon :icon="['fas', 'arrow-left']" class="w-4 h-4 text-gray-600" />
                </Link>
                <h1 class="text-2xl font-bold text-gray-900">Historial de Pagos de Comisiones</h1>
            </div>
            
            <!-- Filtros -->
            <div class="flex items-center gap-4">
                <select v-model="filtroEstado" @change="filtrar" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Todos los estados</option>
                    <option value="pagado">Pagado</option>
                    <option value="parcial">Parcial</option>
                    <option value="pendiente">Pendiente</option>
                </select>
            </div>
        </div>

        <!-- Tabla de pagos -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-white">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Vendedor</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Periodo</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Comisión</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Pagado</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Fecha Pago</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <tr v-if="pagos.data.length === 0">
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                No hay pagos registrados
                            </td>
                        </tr>
                        <tr v-for="pago in pagos.data" :key="pago.id" class="hover:bg-white">
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">#{{ pago.id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                                {{ pago.vendedor?.name || 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                {{ formatFecha(pago.periodo_inicio) }} - {{ formatFecha(pago.periodo_fin) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-gray-900">
                                ${{ formatMonto(pago.monto_comision) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-green-600 font-medium">
                                ${{ formatMonto(pago.monto_pagado) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span v-if="pago.estado === 'pagado'" class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                    Pagado
                                </span>
                                <span v-else-if="pago.estado === 'parcial'" class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                                    Parcial
                                </span>
                                <span v-else class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                    Pendiente
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                {{ pago.fecha_pago ? formatFecha(pago.fecha_pago) : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <a :href="`/comisiones/recibo/${pago.id}`" target="_blank" class="p-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 inline-flex" title="Descargar recibo">
                                    <FontAwesomeIcon :icon="['fas', 'file-pdf']" class="w-4 h-4" />
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div v-if="pagos.links && pagos.links.length > 3" class="px-6 py-4 border-t border-gray-100 bg-white/50">
                <nav class="flex items-center justify-center gap-1">
                    <Link
                        v-for="link in pagos.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-2 text-sm rounded-lg transition-colors',
                            link.active ? 'bg-amber-500 text-white' : link.url ? 'text-gray-600 hover:bg-gray-100' : 'text-gray-300 cursor-not-allowed'
                        ]"
                        v-html="link.label"
                    />
                </nav>
            </div>
        </div>
    </div>
</template>

<script setup>
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
