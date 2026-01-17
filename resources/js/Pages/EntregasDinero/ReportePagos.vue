<template>
    <AppLayout title="Reporte de Pagos Recibidos">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Reporte de Pagos Recibidos
                </h2>
                <Link
                    :href="route('entregas-dinero.index')"
                    class="bg-white0 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                >
                    ← Volver a Entregas
                </Link>
            </div>
        </template>

        <!-- Filtros -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <form @submit.prevent="applyFilters" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fecha Inicio</label>
                        <input
                            v-model="filters.fecha_inicio"
                            type="date"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fecha Fin</label>
                        <input
                            v-model="filters.fecha_fin"
                            type="date"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Usuario</label>
                        <select
                            v-model="filters.usuario_id"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="">Todos</option>
                            <option v-for="usuario in usuarios" :key="usuario.id" :value="usuario.id">
                                {{ usuario.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Recibido Por</label>
                        <select
                            v-model="filters.recibido_por"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="">Todos</option>
                            <option v-for="responsable in responsables" :key="responsable.id" :value="responsable.id">
                                {{ responsable.name }}
                            </option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button
                            type="submit"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded w-full"
                        >
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-bold">$</span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Total Recibido
                            </dt>
                            <dd class="text-lg font-semibold text-gray-900">
                                {{ formatCurrency(stats.total_recibido) }}
                            </dd>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-bold">{{ stats.cantidad_entregas }}</span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Entregas
                            </dt>
                            <dd class="text-lg font-semibold text-gray-900">
                                {{ stats.cantidad_entregas }}
                            </dd>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-bold">{{ stats.usuarios_unicos }}</span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Usuarios
                            </dt>
                            <dd class="text-lg font-semibold text-gray-900">
                                {{ stats.usuarios_unicos }}
                            </dd>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-bold">{{ stats.responsables_unicos }}</span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Responsables
                            </dt>
                            <dd class="text-lg font-semibold text-gray-900">
                                {{ stats.responsables_unicos }}
                            </dd>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resumen por Método de Pago -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Resumen por Método de Pago en Entrega</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div v-for="metodo in resumenMetodos" :key="metodo.metodo" class="flex items-center justify-between p-4 bg-white rounded-lg">
                            <div>
                                <div class="font-medium text-gray-900">{{ metodo.label }}</div>
                                <div class="text-sm text-gray-500">{{ metodo.cantidad }} entregas</div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-lg text-gray-900">{{ formatCurrency(metodo.total) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Desglose de Montos</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Efectivo en Entrega:</span>
                            <span class="font-semibold">{{ formatCurrency(metodoEntregaStats.efectivo || 0) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Cheques en Entrega:</span>
                            <span class="font-semibold">{{ formatCurrency(metodoEntregaStats.cheque || 0) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Tarjetas en Entrega:</span>
                            <span class="font-semibold">{{ formatCurrency(metodoEntregaStats.tarjeta || 0) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Mixto en Entrega:</span>
                            <span class="font-semibold">{{ formatCurrency(metodoEntregaStats.mixto || 0) }}</span>
                        </div>
                        <hr class="my-2">
                        <div class="flex items-center justify-between font-bold text-lg">
                            <span>Total Entregas:</span>
                            <span>{{ formatCurrency(stats.total_recibido) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla Detallada -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="px-6 py-4 bg-white border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Detalle de Pagos Recibidos</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-white">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Fecha Entrega
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Usuario
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Recibido Por
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Método de Pago Original
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Método de Pago Entrega
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Origen
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Monto
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Notas
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Entregado Responsable
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="entrega in entregas" :key="entrega.id">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ formatearFecha(entrega.fecha_entrega) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ entrega.usuario?.name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ entrega.recibidoPor?.name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="getMetodoPagoClass(entrega)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                    {{ getMetodoPagoLabel(entrega) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="getMetodoPagoEntregaClass(entrega)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                    {{ getMetodoPagoEntregaLabel(entrega) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span v-if="entrega.tipo_origen === 'venta'" class="text-green-600">
                                    Venta #{{ getVentaNumero(entrega) }}
                                </span>
                                <span v-else-if="entrega.tipo_origen === 'cobranza'" class="text-blue-600">
                                    Cobranza #{{ entrega.id_origen }}
                                </span>
                                <span v-else class="text-gray-600">
                                    Manual
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                {{ formatCurrency(entrega.total) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                {{ entrega.notas_recibido || entrega.notas || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span v-if="entrega.entregado_responsable" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Sí
                                </span>
                                <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Pendiente
                                </span>
                                <div v-if="entrega.fecha_entregado_responsable" class="text-xs text-gray-500 mt-1">
                                    {{ formatearFecha(entrega.fecha_entregado_responsable) }}
                                </div>
                                <div v-if="entrega.responsable_organizacion" class="text-xs text-gray-500">
                                    {{ entrega.responsable_organizacion }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button
                                    v-if="!entrega.entregado_responsable"
                                    @click="marcarEntregadoResponsable(entrega)"
                                    class="inline-flex items-center gap-2 px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors text-sm"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Entregar
                                </button>
                                <span v-else class="text-sm text-gray-500">
                                    Entregado
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    entregas: Array,
    resumenMetodos: Array,
    stats: Object,
    metodoEntregaStats: Object,
    usuarios: Array,
    responsables: Array,
    filters: Object,
});

const usuarios = ref([]);
const responsables = ref([]);

const filters = ref({
    fecha_inicio: props.filters.fecha_inicio || '',
    fecha_fin: props.filters.fecha_fin || '',
    usuario_id: props.filters.usuario_id || '',
    recibido_por: props.filters.recibido_por || '',
});

const currencyFormatter = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

const formatCurrency = (value) => currencyFormatter.format(value || 0);

const formatearFecha = (date) => {
    if (!date) return 'N/A';
    try {
        return new Date(date).toLocaleDateString('es-MX');
    } catch {
        return 'Fecha inválida';
    }
};

const getMetodoPagoLabel = (entrega) => {
    if (entrega.monto_efectivo > 0 && entrega.monto_cheques == 0 && entrega.monto_tarjetas == 0) {
        return 'Efectivo';
    } else if (entrega.monto_cheques > 0 && entrega.monto_efectivo == 0 && entrega.monto_tarjetas == 0) {
        return 'Cheque';
    } else if (entrega.monto_tarjetas > 0 && entrega.monto_efectivo == 0 && entrega.monto_cheques == 0) {
        return 'Tarjeta';
    } else {
        return 'Mixto';
    }
};

const getMetodoPagoClass = (entrega) => {
    const metodo = getMetodoPagoLabel(entrega).toLowerCase();
    const classes = {
        'efectivo': 'bg-green-100 text-green-800',
        'cheque': 'bg-blue-100 text-blue-800',
        'tarjeta': 'bg-purple-100 text-purple-800',
        'mixto': 'bg-orange-100 text-orange-800'
    };
    return classes[metodo] || 'bg-gray-100 text-gray-800';
};

const getMetodoPagoEntregaLabel = (entrega) => {
    // Determinar método de pago basado en los montos
    if (entrega.monto_efectivo > 0 && entrega.monto_cheques == 0 && entrega.monto_tarjetas == 0) {
        return 'Efectivo';
    } else if (entrega.monto_cheques > 0 && entrega.monto_efectivo == 0 && entrega.monto_tarjetas == 0) {
        return 'Cheque';
    } else if (entrega.monto_tarjetas > 0 && entrega.monto_efectivo == 0 && entrega.monto_cheques == 0) {
        return 'Tarjeta';
    } else {
        return 'Mixto';
    }
};

const getMetodoPagoEntregaClass = (entrega) => {
    const metodo = getMetodoPagoEntregaLabel(entrega).toLowerCase();
    const classes = {
        'efectivo': 'bg-green-100 text-green-800',
        'cheque': 'bg-blue-100 text-blue-800',
        'tarjeta': 'bg-purple-100 text-purple-800',
        'mixto': 'bg-orange-100 text-orange-800'
    };
    return classes[metodo] || 'bg-gray-100 text-gray-800';
};

const getVentaNumero = (entrega) => {
    // Esta función necesitaría acceso a la venta para obtener el número
    return entrega.id_origen || 'N/A';
};

const applyFilters = () => {
    const params = {};
    if (filters.value.fecha_inicio) params.fecha_inicio = filters.value.fecha_inicio;
    if (filters.value.fecha_fin) params.fecha_fin = filters.value.fecha_fin;
    if (filters.value.usuario_id) params.usuario_id = filters.value.usuario_id;
    if (filters.value.recibido_por) params.recibido_por = filters.value.recibido_por;

    window.location.href = route('entregas-dinero.reporte-pagos', params);
};

const marcarEntregadoResponsable = async (entrega) => {
    const responsableNombre = prompt('Nombre del responsable que recibe el dinero:', '');
    if (!responsableNombre) return;

    const notas = prompt('Notas de la entrega (opcional):', '');

    try {
        const response = await fetch(route('entregas-dinero.marcar-entregado-responsable', entrega.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                responsable_nombre: responsableNombre,
                notas_entrega: notas || null
            })
        });

        const result = await response.json();

        if (result.success) {
            notyf.success('Entrega marcada como entregada al responsable');
            // Recargar la página para actualizar los datos
            window.location.reload();
        } else {
            notyf.error(result.error || 'Error al marcar la entrega');
        }
    } catch (error) {
        notyf.error('Error de conexión');
    }
};

onMounted(() => {
    // Aquí podrías cargar usuarios y responsables si es necesario
    // Por ahora se dejan vacíos ya que el controlador debería proporcionar esta información
});
</script>

