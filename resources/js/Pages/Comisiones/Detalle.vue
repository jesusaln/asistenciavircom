<template>
    <Head :title="`Comisiones - ${vendedor.nombre}`" />

    <div class="w-full px-6 py-8 animate-fade-in">
        <!-- Header (Standardized) -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-6">
                <Link href="/comisiones" class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-brand-500 hover:text-white flex items-center justify-center transition-all duration-300 shadow-sm">
                    <FontAwesomeIcon :icon="['fas', 'arrow-left']" class="w-4 h-4" />
                </Link>
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-white shadow-xl shadow-brand-500/20">
                        <FontAwesomeIcon :icon="vendedor.type_label === 'Técnico' ? ['fas', 'tools'] : ['fas', 'user-tie']" class="h-6 w-6" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">{{ vendedor.nombre }}</h1>
                        <div class="flex items-center gap-2 mt-1">
                            <span :class="vendedor.type_label === 'Técnico' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'" class="px-2.5 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider">
                                {{ vendedor.type_label }}
                            </span>
                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 border-l border-slate-200 dark:border-slate-700 pl-2">
                                {{ filtros.fecha_inicio }} - {{ filtros.fecha_fin }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-6 mb-8 stagger-children">
            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 transition-colors">
                <p class="text-sm text-slate-500 dark:text-slate-400">Total Ventas</p>
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ detalle.num_ventas }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 transition-colors">
                <p class="text-sm text-slate-500 dark:text-slate-400">Comisión del Periodo</p>
                <p class="text-2xl font-bold text-brand-600 dark:text-brand-400">${{ formatMonto(detalle.total_comision_bruto) }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 transition-colors">
                <p class="text-sm text-slate-500 dark:text-slate-400">Ya Pagado</p>
                <p class="text-2xl font-bold text-sky-600 dark:text-sky-400">${{ formatMonto(detalle.total_comision_bruto - detalle.total_comision) }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 transition-colors">
                <p class="text-sm text-slate-500 dark:text-slate-400">Pendiente por Pagar</p>
                <p class="text-2xl font-bold" :class="detalle.total_comision > 0 ? 'text-brand-600 dark:text-brand-500' : 'text-emerald-600 dark:text-emerald-400'">${{ formatMonto(detalle.total_comision) }}</p>
            </div>
        </div>

        <!-- Tabla de detalles -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-white/50">
                <h3 class="text-lg font-semibold text-slate-900">Detalle por Venta</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase w-10">
                                <input type="checkbox" @change="toggleAll" :checked="isAllSelected" class="rounded-xl border-slate-300 text-blue-600 focus:ring-brand-500">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Venta</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Estado Pago</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Comisión</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                        <tr v-if="!detalle.detalles || detalle.detalles.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                No hay ventas en este periodo
                            </td>
                        </tr>
                        <template v-for="venta in detalle.detalles" :key="venta.venta_id">
                            <tr class="hover:bg-slate-50/50 transition-colors border-l-4" :class="[['Técnico', 'Técnico Líder', 'Ayudante'].includes(venta.rol) ? 'border-l-purple-500' : 'border-l-blue-500', venta.comision_pagada ? 'bg-slate-50 opacity-75' : '']">
                                <td class="px-6 py-4">
                                    <input 
                                        type="checkbox" 
                                        v-model="selectedVentas" 
                                        :value="venta.venta_id" 
                                        :disabled="venta.comision_pagada"
                                        class="rounded-xl border-slate-300 text-blue-600 focus:ring-brand-500 disabled:opacity-50"
                                    >
                                </td>
                                <td @click="toggleVenta(venta.venta_id)" class="px-6 py-4 whitespace-nowrap font-medium text-slate-900 cursor-pointer">
                                    <div class="flex items-center gap-2">
                                        <FontAwesomeIcon :icon="['fas', expandedVentas.includes(venta.venta_id) ? 'chevron-down' : 'chevron-right']" class="w-3 h-3 text-slate-400" />
                                        {{ venta.numero_venta }}
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-bold uppercase mt-1 pl-5">
                                        Rol: <span class="text-blue-600 dark:text-blue-400">{{ venta.rol }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-slate-500">
                                    {{ formatFecha(venta.fecha) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-slate-500">
                                    {{ venta.cliente }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="venta.comision_pagada" class="bg-emerald-100 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 px-2 py-1 rounded-full text-[10px] font-bold uppercase">
                                        <FontAwesomeIcon :icon="['fas', 'check-circle']" class="mr-1" /> PAGADO
                                    </span>
                                    <span v-else class="bg-brand-50 text-brand-800 dark:bg-brand-900/30 dark:text-brand-200 px-2 py-1 rounded-full text-[10px] font-bold uppercase">
                                        Pendiente
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-bold text-brand-600 dark:text-brand-400">
                                    ${{ formatMonto(venta.comision_total) }}
                                </td>
                            </tr>
                            <!-- Breakdown Table -->
                            <tr v-if="expandedVentas.includes(venta.venta_id)" class="bg-slate-50 animate-fade-in">
                                <td colspan="8" class="px-12 py-4">
                                    <div class="border rounded-xl overflow-hidden shadow-inner">
                                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                            <thead class="bg-slate-50 dark:bg-slate-800/50">
                                                <tr>
                                                    <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase">Item</th>
                                                    <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500 uppercase">Tipo</th>
                                                    <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500 uppercase">Cant.</th>
                                                    <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500 uppercase">Precio</th>
                                                    <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500 uppercase">Comisión</th>
                                                    <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase">Motivo</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                                                <tr v-for="item in venta.items" :key="item.nombre">
                                                    <td class="px-4 py-2 text-sm text-slate-900">{{ item.nombre }}</td>
                                                    <td class="px-4 py-2 text-center">
                                                        <span :class="item.tipo === 'Servicio' ? 'bg-purple-50 text-purple-700 dark:bg-purple-900/20 dark:text-purple-300' : 'bg-sky-50 text-sky-700 dark:bg-sky-900/20 dark:text-sky-300'" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase">
                                                            {{ item.tipo }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-2 text-center text-sm text-slate-500">{{ item.cantidad }}</td>
                                                    <td class="px-4 py-2 text-right text-sm text-slate-500 dark:text-slate-400">${{ formatMonto(item.precio) }}</td>
                                                    <td class="px-4 py-2 text-right text-sm font-bold" :class="item.comision > 0 ? 'text-brand-600 dark:text-brand-400' : 'text-slate-400'">${{ formatMonto(item.comision) }}</td>
                                                    <td class="px-4 py-2 text-left text-xs">
                                                        <span v-if="item.motivo_cero" class="text-amber-500 italic">{{ item.motivo_cero }}</span>
                                                        <span v-else class="text-slate-300">—</span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700">
                        <tr>
                             <td colspan="5" class="px-6 py-4 font-bold text-slate-900 dark:text-white uppercase tracking-tighter">TOTAL PENDIENTE SELECCIONADO</td>
                            <td class="px-6 py-4 text-right font-bold text-brand-600 dark:text-brand-400 text-xl">${{ formatMonto(selectedAmount) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <!-- Sticky Payment Bar -->
        <div v-if="selectedVentas.length > 0" class="fixed bottom-0 left-0 right-0 bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 p-4 shadow-2xl z-50 transform animate-slide-up transition-colors">
            <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-12 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-brand-50 dark:bg-brand-900/30 rounded-full flex items-center justify-center text-brand-600 dark:text-brand-400 shadow-inner">
                        <FontAwesomeIcon :icon="['fas', 'money-bill-wave']" class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Seleccionadas: <span class="font-bold text-slate-900 dark:text-white">{{ selectedVentas.length }} ventas</span></p>
                        <p class="text-xl font-bold text-slate-900 dark:text-white">Total a liquidar: <span class="text-brand-600 dark:text-brand-500">${{ formatMonto(selectedAmount) }}</span></p>
                    </div>
                </div>
                <button 
                    @click="confirmarPago"
                    class="px-8 py-3 bg-brand-500 hover:bg-brand-600 text-white font-bold rounded-2xl shadow-xl shadow-brand-500/20 transition-all active:scale-95 flex items-center gap-2"
                >
                    <FontAwesomeIcon :icon="['fas', 'check']" />
                    Registrar Pago de Selección
                </button>
            </div>
        </div>
        </div>
    </div>
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
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
    // Evitar bug de zona horaria: si la fecha viene como "YYYY-MM-DD", agregar T12:00:00
    const d = typeof fecha === 'string' && fecha.match(/^\d{4}-\d{2}-\d{2}$/)
        ? new Date(fecha + 'T12:00:00')
        : new Date(fecha);
    return d.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
};
</script>
