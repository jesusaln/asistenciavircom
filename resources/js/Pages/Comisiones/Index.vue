<template>
    <Head title="Comisiones" />

    <div class="w-full px-6 py-8 animate-fade-in">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                        <span class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center text-white shadow-lg">
                            <FontAwesomeIcon :icon="['fas', 'hand-holding-usd']" class="h-6 w-6" />
                        </span>
                        Comisiones
                    </h1>
                    <p class="mt-2 text-gray-500">
                        {{ resumen.periodo_label }}
                    </p>
                </div>
                
                <!-- Filtros de periodo y acciones -->
                <div class="flex flex-wrap items-center gap-3">
                    <select 
                        v-model="periodoSeleccionado" 
                        @change="cambiarPeriodo"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500"
                    >
                        <option value="semana">Esta semana</option>
                        <option value="mes">Este mes</option>
                    </select>
                    <button 
                        @click="exportarExcel" 
                        class="inline-flex items-center gap-2 px-4 py-2 bg-green-500 text-white font-semibold rounded-lg hover:bg-green-600 transition-colors"
                    >
                        <FontAwesomeIcon :icon="['fas', 'file-excel']" />
                        Exportar
                    </button>
                    <Link
                        href="/comisiones/historial"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-colors"
                    >
                        <FontAwesomeIcon :icon="['fas', 'history']" />
                        Historial
                    </Link>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-6 mb-8 stagger-children">
            <!-- Total Comisiones -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 card-hover">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-lg bg-green-100 text-green-600">
                        <FontAwesomeIcon :icon="['fas', 'dollar-sign']" class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Comisiones</p>
                        <p class="text-2xl font-bold text-gray-900">${{ formatMonto(resumen.total_comisiones) }}</p>
                    </div>
                </div>
            </div>

            <!-- Pagadas -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 card-hover">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-lg bg-blue-100 text-blue-600">
                        <FontAwesomeIcon :icon="['fas', 'check-circle']" class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Pagadas</p>
                        <p class="text-2xl font-bold text-blue-600">${{ formatMonto(resumen.total_pagado) }}</p>
                    </div>
                </div>
            </div>

            <!-- Pendientes -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 card-hover">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-lg bg-amber-100 text-amber-600">
                        <FontAwesomeIcon :icon="['fas', 'clock']" class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Pendientes</p>
                        <p class="text-2xl font-bold text-amber-600">${{ formatMonto(resumen.total_pendiente) }}</p>
                    </div>
                </div>
            </div>

            <!-- Mejor Vendedor -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 card-hover">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-lg bg-purple-100 text-purple-600">
                        <FontAwesomeIcon :icon="['fas', 'trophy']" class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Mejor Vendedor</p>
                        <p class="text-lg font-bold text-gray-900 truncate">{{ mejorVendedor?.nombre || 'N/A' }}</p>
                        <p v-if="mejorVendedor" class="text-sm text-green-600">${{ formatMonto(mejorVendedor.comision) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ranking y Tabla -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
            <!-- Top 5 Vendedores -->
            <div class="lg:col-span-1 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-purple-500 to-purple-600">
                    <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                        <FontAwesomeIcon :icon="['fas', 'medal']" />
                        Top 5 Vendedores
                    </h3>
                </div>
                <div class="p-4 space-y-3">
                    <div 
                        v-for="(vendedor, index) in top5Vendedores" 
                        :key="vendedor.id"
                        class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                        <span :class="getMedalClass(index)" class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold">
                            {{ index + 1 }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 truncate">{{ vendedor.nombre }}</p>
                            <p class="text-sm text-gray-500">{{ vendedor.num_ventas }} ventas</p>
                        </div>
                        <span class="text-green-600 font-bold whitespace-nowrap">${{ formatMonto(vendedor.comision) }}</span>
                    </div>
                    <div v-if="top5Vendedores.length === 0" class="text-center py-8 text-gray-400">
                        Sin datos
                    </div>
                </div>
            </div>

            <!-- Tabla de vendedores -->
            <div class="lg:col-span-3 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="text-lg font-semibold text-gray-900">Comisiones por Vendedor</h3>
                </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Vendedor</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tipo</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Ventas</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total Ventas</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Comisión</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <tr v-if="resumen.vendedores.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center">
                                <FontAwesomeIcon :icon="['fas', 'inbox']" class="h-12 w-12 text-gray-300 mb-4" />
                                <p class="text-gray-500">No hay comisiones en este periodo</p>
                            </td>
                        </tr>
                        <tr v-for="vendedor in resumen.vendedores" :key="`${vendedor.type}-${vendedor.id}`" class="hover:bg-amber-50/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-semibold">
                                        {{ vendedor.nombre.charAt(0) }}
                                    </div>
                                    <span class="font-medium text-gray-900">{{ vendedor.nombre }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="vendedor.type_label === 'Técnico' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'" class="px-2 py-1 rounded-full text-xs font-medium">
                                    {{ vendedor.type_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="text-gray-900 font-medium">{{ vendedor.num_ventas }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="text-gray-700">${{ formatMonto(vendedor.total_ventas) }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="text-lg font-bold text-green-600">${{ formatMonto(vendedor.comision) }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span v-if="vendedor.estado === 'pagado'" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                    <FontAwesomeIcon :icon="['fas', 'check']" class="mr-1" />
                                    Pagado
                                </span>
                                <span v-else-if="vendedor.estado === 'parcial'" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                                    Parcial
                                </span>
                                <span v-else class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                    Pendiente
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <Link 
                                        :href="`/comisiones/vendedor/${vendedor.type === 'App\\\\Models\\\\User' ? 'user' : 'tecnico'}/${vendedor.id}?periodo=${periodoSeleccionado}`"
                                        class="p-2 rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200 transition-colors"
                                        title="Ver detalle"
                                    >
                                        <FontAwesomeIcon :icon="['fas', 'eye']" class="w-4 h-4" />
                                    </Link>
                                    <button 
                                        v-if="vendedor.estado !== 'pagado'"
                                        @click="abrirModalPago(vendedor)"
                                        class="p-2 rounded-lg bg-green-100 text-green-600 hover:bg-green-200 transition-colors"
                                        title="Pagar comisión"
                                    >
                                        <FontAwesomeIcon :icon="['fas', 'money-bill']" class="w-4 h-4" />
                                    </button>
                                    <button 
                                        v-if="vendedor.pago_id"
                                        @click="descargarRecibo(vendedor.pago_id)"
                                        class="p-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors"
                                        title="Descargar recibo"
                                    >
                                        <FontAwesomeIcon :icon="['fas', 'file-pdf']" class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            </div>
        </div>

        <!-- Modal de Pago -->
        <div v-if="showModalPago" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showModalPago = false">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
                
                <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6 animate-scale-in">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-900">Registrar Pago de Comisión</h3>
                        <button @click="showModalPago = false" class="text-gray-400 hover:text-gray-600">
                            <FontAwesomeIcon :icon="['fas', 'times']" />
                        </button>
                    </div>

                    <form @submit.prevent="procesarPago">
                        <div class="space-y-4">
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <p class="text-sm text-gray-600">Vendedor</p>
                                <p class="font-semibold text-gray-900">{{ vendedorSeleccionado?.nombre }}</p>
                                <p class="text-2xl font-bold text-green-600 mt-2">${{ formatMonto(vendedorSeleccionado?.comision) }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Monto a Pagar</label>
                                <input 
                                    v-model.number="formPago.monto_pagado" 
                                    type="number" 
                                    step="0.01"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500"
                                    required
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Método de Pago</label>
                                <select v-model="formPago.metodo_pago" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="efectivo">Efectivo</option>
                                    <option value="transferencia">Transferencia</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>

                            <div v-if="formPago.metodo_pago === 'transferencia'">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Cuenta Bancaria</label>
                                <select v-model="formPago.cuenta_bancaria_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                                    <option value="">Seleccionar...</option>
                                    <option v-for="cuenta in cuentasBancarias" :key="cuenta.id" :value="cuenta.id">
                                        {{ cuenta.banco }} - {{ cuenta.nombre }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Referencia (opcional)</label>
                                <input 
                                    v-model="formPago.referencia_pago" 
                                    type="text" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500"
                                    placeholder="Número de transferencia, cheque, etc."
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notas (opcional)</label>
                                <textarea 
                                    v-model="formPago.notas" 
                                    rows="2"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500"
                                ></textarea>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 mt-6">
                            <button type="button" @click="showModalPago = false" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="procesando" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors disabled:opacity-50">
                                <FontAwesomeIcon v-if="procesando" :icon="['fas', 'spinner']" class="animate-spin mr-2" />
                                Registrar Pago
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

defineOptions({ layout: AppLayout });

const props = defineProps({
    resumen: Object,
    pagosRecientes: Array,
    cuentasBancarias: Array,
    filtros: Object,
});

const periodoSeleccionado = ref(props.filtros.periodo);
const showModalPago = ref(false);
const vendedorSeleccionado = ref(null);
const procesando = ref(false);

const formPago = ref({
    vendedor_type: '',
    vendedor_id: '',
    periodo_inicio: '',
    periodo_fin: '',
    monto_pagado: 0,
    metodo_pago: '',
    referencia_pago: '',
    cuenta_bancaria_id: '',
    notas: '',
});

const formatMonto = (valor) => {
    return Number(valor || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const cambiarPeriodo = () => {
    router.get('/comisiones', { periodo: periodoSeleccionado.value }, { preserveState: true });
};

const abrirModalPago = (vendedor) => {
    vendedorSeleccionado.value = vendedor;
    formPago.value = {
        vendedor_type: vendedor.type,
        vendedor_id: vendedor.id,
        periodo_inicio: props.filtros.fecha_inicio,
        periodo_fin: props.filtros.fecha_fin,
        monto_pagado: vendedor.pendiente,
        metodo_pago: '',
        referencia_pago: '',
        cuenta_bancaria_id: '',
        notas: '',
    };
    showModalPago.value = true;
};

const procesarPago = () => {
    procesando.value = true;
    router.post('/comisiones/pagar', formPago.value, {
        onSuccess: () => {
            showModalPago.value = false;
            procesando.value = false;
        },
        onError: () => {
            procesando.value = false;
        },
    });
};

const descargarRecibo = (pagoId) => {
    window.open(`/comisiones/recibo/${pagoId}`, '_blank');
};

// Computed: Mejor vendedor del periodo
const mejorVendedor = computed(() => {
    if (!props.resumen?.vendedores?.length) return null;
    return props.resumen.vendedores[0]; // Ya viene ordenado por comisión desc
});

// Computed: Top 5 vendedores
const top5Vendedores = computed(() => {
    return (props.resumen?.vendedores || []).slice(0, 5);
});

// Helper: Clases CSS para medallas del ranking
const getMedalClass = (index) => {
    switch (index) {
        case 0: return 'bg-yellow-400 text-yellow-900'; // Oro
        case 1: return 'bg-gray-300 text-gray-700';     // Plata
        case 2: return 'bg-amber-600 text-white';       // Bronce
        default: return 'bg-gray-100 text-gray-600';
    }
};

// Exportar a Excel (CSV)
const exportarExcel = () => {
    const vendedores = props.resumen?.vendedores || [];
    if (vendedores.length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No hay datos para exportar'
        });
        return;
    }

    // Construir CSV
    const headers = ['Vendedor', 'Tipo', 'Ventas', 'Total Ventas', 'Comisión', 'Estado'];
    const rows = vendedores.map(v => [
        v.nombre,
        v.type_label,
        v.num_ventas,
        v.total_ventas,
        v.comision,
        v.estado
    ]);

    const csvContent = [headers, ...rows]
        .map(row => row.map(cell => `"${cell}"`).join(','))
        .join('\n');

    // Descargar archivo
    const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `comisiones_${props.filtros.fecha_inicio}_${props.filtros.fecha_fin}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
};
</script>
