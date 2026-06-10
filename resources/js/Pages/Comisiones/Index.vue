<template>
    <Head title="Comisiones" />

    <div class="w-full px-6 py-8 animate-fade-in">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-white shadow-xl shadow-brand-500/20">
                            <FontAwesomeIcon :icon="['fas', 'hand-holding-usd']" class="h-6 w-6" />
                        </span>
                        Comisiones
                    </h1>
                    <p class="mt-1 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">
                        {{ resumen.periodo_label }}
                    </p>
                </div>
                
                <!-- Filtros de periodo y acciones -->
                <div class="flex flex-wrap items-center gap-3">
                    <select 
                        v-model="periodoSeleccionado" 
                        @change="cambiarPeriodo"
                        class="px-4 py-2 border border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white rounded-xl text-sm focus:ring-2 focus:ring-brand-500"
                    >
                        <option value="semana">Esta semana</option>
                        <option value="mes">Este mes</option>
                    </select>
                    <select 
                        v-model="filtroRol" 
                        class="px-4 py-2 border border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white rounded-xl text-sm focus:ring-2 focus:ring-brand-500"
                    >
                        <option value="todos">Todos los Roles</option>
                        <option value="Técnico">Técnicos</option>
                        <option value="Vendedor">Vendedores</option>
                    </select>
                    <button 
                        @click="exportarExcel" 
                        class="inline-flex items-center gap-2 px-4 py-2 bg-brand-500 text-white font-semibold rounded-xl hover:bg-brand-600 transition-colors shadow-lg shadow-brand-500/20"
                    >
                        <FontAwesomeIcon :icon="['fas', 'file-excel']" />
                        Exportar
                    </button>
                    <Link
                        href="/comisiones/historial"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
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
            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 card-hover transition-colors">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-xl bg-brand-50 dark:bg-brand-900/20 text-brand-600 dark:text-brand-400">
                        <FontAwesomeIcon :icon="['fas', 'dollar-sign']" class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Comisiones</p>
                        <p class="text-2xl font-bold text-slate-900 dark:text-white">${{ formatMonto(resumen.total_comisiones) }}</p>
                    </div>
                </div>
            </div>

            <!-- Pagadas -->
            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 card-hover transition-colors">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-xl bg-sky-50 dark:bg-sky-900/20 text-sky-700 dark:text-sky-400">
                        <FontAwesomeIcon :icon="['fas', 'check-circle']" class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Pagadas</p>
                        <p class="text-2xl font-bold text-sky-600 dark:text-sky-400">${{ formatMonto(resumen.total_pagado) }}</p>
                    </div>
                </div>
            </div>

            <!-- Pendientes -->
            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 card-hover transition-colors">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-xl bg-brand-50 dark:bg-brand-900/20 text-brand-600 dark:text-brand-400">
                        <FontAwesomeIcon :icon="['fas', 'clock']" class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Pendientes</p>
                        <p class="text-2xl font-bold text-brand-600 dark:text-brand-500">${{ formatMonto(resumen.total_pendiente) }}</p>
                    </div>
                </div>
            </div>

            <!-- Mejor Vendedor -->
            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 card-hover transition-colors">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-xl bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400">
                        <FontAwesomeIcon :icon="['fas', 'trophy']" class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Mejor Vendedor</p>
                        <p class="text-lg font-bold text-slate-900 dark:text-white truncate">{{ mejorVendedor?.nombre || 'N/A' }}</p>
                        <p v-if="mejorVendedor" class="text-sm text-brand-600 dark:text-brand-400 font-bold">${{ formatMonto(mejorVendedor.comision) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ranking y Tabla -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
            <!-- Top 5 Vendedores -->
            <div class="lg:col-span-1 bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden transition-colors">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 bg-gradient-to-r from-brand-500 to-brand-700">
                    <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                        <FontAwesomeIcon :icon="['fas', 'medal']" />
                        Top 5 Vendedores
                    </h3>
                </div>
                <div class="p-4 space-y-3">
                    <div 
                        v-for="(vendedor, index) in top5Vendedores" 
                        :key="vendedor.id"
                        class="flex items-center gap-2 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
                    >
                        <span :class="getMedalClass(index)" class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold">
                            {{ index + 1 }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-slate-900 dark:text-white truncate">{{ vendedor.nombre }}</p>
                            <p class="text-sm text-slate-500">{{ vendedor.num_ventas }} ventas</p>
                        </div>
                        <span class="text-emerald-600 font-bold whitespace-nowrap">${{ formatMonto(vendedor.comision) }}</span>
                    </div>
                    <div v-if="top5Vendedores.length === 0" class="py-8 text-center text-slate-400">
                        Sin datos
                    </div>
                </div>
            </div>

            <!-- Tabla de vendedores -->
            <div class="lg:col-span-3 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-white/50">
                    <h3 class="text-lg font-semibold text-slate-900">Comisiones por Vendedor</h3>
                </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Vendedor</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Tipo</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Ventas</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Total Ventas</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Comisión</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Pendiente</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                         <tr v-if="vendedoresFiltrados.length === 0">
                            <td colspan="8" class="px-6 py-12 text-center">
                                <FontAwesomeIcon :icon="['fas', 'inbox']" class="h-12 w-12 text-slate-300 mb-4" />
                                <p class="text-slate-500">No hay comisiones para este filtro</p>
                            </td>
                        </tr>
                        <tr v-for="vendedor in vendedoresFiltrados" :key="`${vendedor.type}-${vendedor.id}`" class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center text-slate-500 font-semibold">
                                        {{ vendedor.nombre.charAt(0) }}
                                    </div>
                                    <span class="font-medium text-slate-900">{{ vendedor.nombre }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="vendedor.type_label === 'Técnico' ? 'bg-purple-100 text-purple-700' : 'bg-sky-100 text-sky-800 dark:text-sky-200'" class="px-2 py-1 rounded-xl text-xs font-medium">
                                    {{ vendedor.type_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="text-slate-900 font-medium">{{ vendedor.num_ventas }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="text-slate-700">${{ formatMonto(vendedor.total_ventas) }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="text-lg font-bold text-brand-600 dark:text-brand-400">${{ formatMonto(vendedor.comision_bruto || vendedor.comision) }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="text-lg font-bold" :class="vendedor.pendiente > 0 ? 'text-amber-500 dark:text-amber-400' : 'text-slate-400 dark:text-slate-600'">${{ formatMonto(vendedor.pendiente) }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span v-if="vendedor.estado === 'pagado'" class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-medium bg-emerald-100 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200">
                                    <FontAwesomeIcon :icon="['fas', 'check']" class="mr-1" />
                                    Pagado
                                </span>
                                <span v-else-if="vendedor.estado === 'parcial'" class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-medium bg-brand-100 text-brand-800 dark:text-brand-200 dark:text-amber-200">
                                    Parcial
                                </span>
                                <span v-else class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400">
                                    Pendiente
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <Link 
                                        :href="`/comisiones/vendedor/${vendedor.type === 'App\\\\Models\\\\User' ? 'user' : 'tecnico'}/${vendedor.id}?periodo=${periodoSeleccionado}`"
                                        class="p-2 rounded-xl bg-sky-50 text-sky-600 dark:bg-sky-900/20 dark:text-sky-400 hover:bg-sky-100 dark:hover:bg-sky-900/40 transition-all duration-300"
                                        title="Ver detalle"
                                    >
                                        <FontAwesomeIcon :icon="['fas', 'eye']" class="w-4 h-4" />
                                    </Link>
                                    <button 
                                        v-if="vendedor.estado !== 'pagado'"
                                        @click="abrirModalPago(vendedor)"
                                        class="p-2 rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 transition-all duration-300"
                                        title="Pagar comisión"
                                    >
                                        <FontAwesomeIcon :icon="['fas', 'money-bill-wave']" class="w-4 h-4" />
                                    </button>
                                    <button 
                                        v-if="vendedor.pago_id"
                                        @click="descargarRecibo(vendedor.pago_id)"
                                        class="p-2 rounded-xl bg-slate-50 text-slate-500 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-all duration-300"
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
        <div v-if="showModalPago" class="fixed inset-0 z-50 overflow-y-auto custom-scrollbar" @click.self="showModalPago = false">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
                
                <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-xl max-w-md w-full p-6 animate-scale-in border border-transparent dark:border-slate-800">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white">Registrar Pago</h3>
                        <button @click="showModalPago = false" class="text-slate-400 hover:text-rose-500 transition-colors">
                            <FontAwesomeIcon :icon="['fas', 'times']" />
                        </button>
                    </div>

                    <form @submit.prevent="procesarPago">
                        <div class="space-y-6">
                            <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-700">
                                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Vendedor</p>
                                <p class="font-black text-slate-900 dark:text-white uppercase mt-1">{{ vendedorSeleccionado?.nombre }}</p>
                                <p class="text-3xl font-black text-brand-600 dark:text-brand-400 mt-2">${{ formatMonto(vendedorSeleccionado?.comision) }}</p>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Monto a Pagar</label>
                                <input 
                                    v-model.number="formPago.monto_pagado" 
                                    type="number" 
                                    step="0.01"
                                    class="w-full px-4 py-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-brand-500 transition-all"
                                    required
                                />
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Método de Pago</label>
                                <select v-model="formPago.metodo_pago" class="w-full px-4 py-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-brand-500 transition-all" required>
                                    <option value="">Seleccionar método...</option>
                                    <option value="efectivo">Efectivo</option>
                                    <option value="transferencia">Transferencia</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>

                            <div v-if="formPago.metodo_pago === 'transferencia'">
                                <label class="block text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Cuenta Bancaria</label>
                                <select v-model="formPago.cuenta_bancaria_id" class="w-full px-4 py-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-brand-500 transition-all">
                                    <option value="">Seleccionar cuenta...</option>
                                    <option v-for="cuenta in cuentasBancarias" :key="cuenta.id" :value="cuenta.id">
                                        {{ cuenta.banco }} - {{ cuenta.nombre }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Referencia (opcional)</label>
                                <input 
                                    v-model="formPago.referencia_pago" 
                                    type="text" 
                                    class="w-full px-4 py-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-brand-500 transition-all"
                                    placeholder="Ej. Transferencia 12345"
                                />
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Notas (opcional)</label>
                                <textarea 
                                    v-model="formPago.notas" 
                                    rows="2"
                                    class="w-full px-4 py-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-brand-500 transition-all"
                                    placeholder="Detalles adicionales..."
                                ></textarea>
                            </div>
                        </div>

                        <div class="mt-8">
                            <button 
                                type="submit" 
                                :disabled="procesando" 
                                class="w-full py-4 bg-brand-500 text-white font-black uppercase tracking-widest rounded-2xl hover:bg-brand-600 transition-all duration-300 shadow-xl shadow-brand-500/30 active:scale-95 disabled:opacity-50"
                            >
                                <FontAwesomeIcon v-if="procesando" :icon="['fas', 'spinner']" class="animate-spin mr-2" />
                                Confirmar y Pagar
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
import Swal from '@/Utils/Swal';

defineOptions({ layout: AppLayout });

const props = defineProps({
    resumen: Object,
    pagosRecientes: Array,
    cuentasBancarias: Array,
    filtros: Object,
});

const periodoSeleccionado = ref(props.filtros.periodo);
const filtroRol = ref('todos');
const showModalPago = ref(false);
const vendedorSeleccionado = ref(null);
const procesando = ref(false);

const vendedoresFiltrados = computed(() => {
    if (filtroRol.value === 'todos') return props.resumen.vendedores;
    return props.resumen.vendedores.filter(v => v.type_label === filtroRol.value);
});

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
        case 0: return 'bg-brand-400 text-amber-900'; // Oro
        case 1: return 'bg-slate-300 text-slate-700';     // Plata
        case 2: return 'bg-brand-600 text-white';       // Bronce
        default: return 'bg-slate-100 text-slate-500';
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
