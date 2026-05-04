<template>
    <Head :title="`Comisiones - ${vendedor.nombre}`" />

    <div class="w-full px-6 py-8 animate-fade-in">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-4">
                <Link href="/comisiones" class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 transition-colors">
                    <FontAwesomeIcon :icon="['fas', 'arrow-left']" class="w-4 h-4 text-gray-600" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ vendedor.nombre }}</h1>
                    <span :class="vendedor.type_label === 'Técnico' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'" class="px-2 py-1 rounded-full text-xs font-medium">
                        {{ vendedor.type_label }}
                    </span>
                </div>
            </div>
            <p class="text-gray-500">
                Periodo: {{ filtros.fecha_inicio }} - {{ filtros.fecha_fin }}
            </p>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-sm text-gray-500">Total Ventas</p>
                <p class="text-2xl font-bold text-gray-900">{{ detalle.num_ventas }}</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-sm text-gray-500">Comisión del Periodo</p>
                <p class="text-2xl font-bold text-gray-900">${{ formatMonto(detalle.total_comision_bruto) }}</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-sm text-gray-500">Ya Pagado</p>
                <p class="text-2xl font-bold text-blue-600">${{ formatMonto(detalle.total_comision_bruto - detalle.total_comision) }}</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-sm text-gray-500">Pendiente por Pagar</p>
                <p class="text-2xl font-bold" :class="detalle.total_comision > 0 ? 'text-amber-600' : 'text-green-600'">${{ formatMonto(detalle.total_comision) }}</p>
            </div>
        </div>

        <!-- Tabla de detalles -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-white/50">
                <h3 class="text-lg font-semibold text-gray-900">Detalle por Venta</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-white">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-10">
                                <input type="checkbox" @change="toggleAll" :checked="isAllSelected" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Venta</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Estado Pago</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Comisión</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <tr v-if="!detalle.detalles || detalle.detalles.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                No hay ventas en este periodo
                            </td>
                        </tr>
                        <template v-for="venta in detalle.detalles" :key="venta.venta_id">
                            <tr class="hover:bg-amber-50/50 transition-colors border-l-4" :class="[venta.rol === 'Técnico' ? 'border-l-purple-500' : 'border-l-blue-500', venta.comision_pagada ? 'bg-gray-50 opacity-75' : '']">
                                <td class="px-6 py-4">
                                    <input 
                                        type="checkbox" 
                                        v-model="selectedVentas" 
                                        :value="venta.venta_id" 
                                        :disabled="venta.comision_pagada"
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 disabled:opacity-50"
                                    >
                                </td>
                                <td @click="toggleVenta(venta.venta_id)" class="px-6 py-4 whitespace-nowrap font-medium text-gray-900 cursor-pointer">
                                    <div class="flex items-center gap-2">
                                        <FontAwesomeIcon :icon="['fas', expandedVentas.includes(venta.venta_id) ? 'chevron-down' : 'chevron-right']" class="w-3 h-3 text-gray-400" />
                                        {{ venta.numero_venta }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                    {{ formatFecha(venta.fecha) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                    {{ venta.cliente }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="venta.comision_pagada" class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-[10px] font-bold uppercase">
                                        <FontAwesomeIcon :icon="['fas', 'check-circle']" class="mr-1" /> PAGADO
                                    </span>
                                    <span v-else class="bg-amber-100 text-amber-700 px-2 py-1 rounded-full text-[10px] font-bold uppercase">
                                        Pendiente
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-bold text-green-600">
                                    ${{ formatMonto(venta.comision_total) }}
                                </td>
                            </tr>
                            <!-- Breakdown Table -->
                            <tr v-if="expandedVentas.includes(venta.venta_id)" class="bg-gray-50 animate-fade-in">
                                <td colspan="8" class="px-12 py-4">
                                    <div class="border rounded-lg overflow-hidden shadow-inner">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Item</th>
                                                    <th class="px-4 py-2 text-center text-xs font-semibold text-gray-500 uppercase">Tipo</th>
                                                    <th class="px-4 py-2 text-center text-xs font-semibold text-gray-500 uppercase">Cant.</th>
                                                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Precio</th>
                                                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Comisión</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-100">
                                                <tr v-for="item in venta.items" :key="item.nombre">
                                                    <td class="px-4 py-2 text-sm text-gray-900">{{ item.nombre }}</td>
                                                    <td class="px-4 py-2 text-center">
                                                        <span :class="item.tipo === 'Servicio' ? 'text-purple-600' : 'text-blue-600'" class="text-xs font-medium">
                                                            {{ item.tipo }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-2 text-center text-sm text-gray-600">{{ item.cantidad }}</td>
                                                    <td class="px-4 py-2 text-right text-sm text-gray-600">${{ formatMonto(item.precio) }}</td>
                                                    <td class="px-4 py-2 text-right text-sm font-bold text-green-600">${{ formatMonto(item.comision) }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="bg-white">
                        <tr>
                             <td colspan="5" class="px-6 py-4 font-bold text-gray-900">TOTAL PENDIENTE</td>
                            <td class="px-6 py-4 text-right font-bold text-green-600">${{ formatMonto(detalle.total_comision) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <!-- Sticky Payment Bar -->
        <div v-if="selectedVentas.length > 0" class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4 shadow-2xl z-50 transform animate-slide-up">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600">
                        <FontAwesomeIcon :icon="['fas', 'money-bill-wave']" class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Seleccionadas: <span class="font-bold text-gray-900">{{ selectedVentas.length }} ventas</span></p>
                        <p class="text-xl font-bold text-gray-900">Total a pagar: <span class="text-green-600">${{ formatMonto(selectedAmount) }}</span></p>
                    </div>
                </div>
                <button 
                    @click="confirmarPago"
                    class="px-8 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl shadow-lg shadow-green-200 transition-all active:scale-95"
                >
                    Registrar Pago de Selección
                </button>
            </div>
        </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import Swal from 'sweetalert2';

defineOptions({ layout: AppLayout });

const props = defineProps({
    vendedor: Object,
    detalle: Object,
    filtros: Object,
});

const expandedVentas = ref([]);
const selectedVentas = ref([]);

// Monto total de las ventas seleccionadas
const selectedAmount = computed(() => {
    return props.detalle.detalles
        .filter(v => selectedVentas.value.includes(v.venta_id))
        .reduce((sum, v) => sum + parseFloat(v.comision_total), 0);
});

// Selección masiva
const isAllSelected = computed(() => {
    const pendings = props.detalle.detalles.filter(v => !v.comision_pagada);
    return pendings.length > 0 && selectedVentas.value.length === pendings.length;
});

const toggleAll = (e) => {
    if (e.target.checked) {
        selectedVentas.value = props.detalle.detalles
            .filter(v => !v.comision_pagada)
            .map(v => v.venta_id);
    } else {
        selectedVentas.value = [];
    }
};

const confirmarPago = () => {
    Swal.fire({
        title: '¿Confirmar pago de comisiones?',
        text: `Se registrará un pago por $${formatMonto(selectedAmount.value)} correspondiente a ${selectedVentas.value.length} ventas.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, registrar pago',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            registrarPago();
        }
    });
};

const registrarPago = () => {
    router.post(route('comisiones.pagar'), {
        vendedor_id: props.vendedor.id,
        vendedor_type: props.vendedor.type,
        periodo_inicio: props.filtros.fecha_inicio,
        periodo_fin: props.filtros.fecha_fin,
        monto_pagado: selectedAmount.value,
        venta_ids: selectedVentas.value,
        metodo_pago: 'efectivo', // Por defecto para liquidaciones rápidas
    }, {
        onSuccess: () => {
            selectedVentas.value = [];
            Swal.fire('¡Éxito!', 'El pago ha sido registrado correctamente.', 'success');
        }
    });
};

const toggleVenta = (id) => {
    if (expandedVentas.value.includes(id)) {
        expandedVentas.value = expandedVentas.value.filter(v => v !== id);
    } else {
        expandedVentas.value.push(id);
    }
};

const formatMonto = (valor) => {
    return Number(valor || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatFecha = (fecha) => {
    if (!fecha) return '-';
    return new Date(fecha).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
};
</script>
