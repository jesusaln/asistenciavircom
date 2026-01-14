<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    gasto: Object,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value || 0);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-MX', { 
        day: '2-digit', 
        month: 'long', 
        year: 'numeric' 
    });
};

const getEstadoBadge = (estado) => {
    const badges = {
        'procesada': 'bg-green-100 text-green-800',
        'cancelada': 'bg-red-100 text-red-800',
    };
    return badges[estado] || 'bg-gray-100 text-gray-800';
};

const cancelGasto = () => {
    if (confirm('¿Estás seguro de cancelar este gasto?')) {
        router.post(route('gastos.cancel', props.gasto.id));
    }
};

const deleteGasto = () => {
    if (confirm('¿Estás seguro de eliminar este gasto?')) {
        router.delete(route('gastos.destroy', props.gasto.id));
    }
};
</script>

<template>
    <AppLayout title="Detalle de Gasto">
        <Head :title="`Gasto ${gasto.numero_compra}`" />

        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Gasto {{ gasto.numero_compra }}
                </h2>
                <Link :href="route('gastos.index')"
                    class="text-gray-600 hover:text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Volver
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <!-- Header -->
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ gasto.numero_compra }}</h3>
                            <p class="text-sm text-gray-500">{{ formatDate(gasto.fecha_compra) }}</p>
                        </div>
                        <span :class="getEstadoBadge(gasto.estado)"
                            class="px-3 py-1 text-sm font-semibold rounded-full">
                            {{ gasto.estado }}
                        </span>
                    </div>

                    <!-- Detalles -->
                    <div class="p-6">
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Categoría</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ gasto.categoria_gasto?.nombre || '-' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Proveedor</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ gasto.proveedor?.nombre || 'Sin proveedor' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Método de Pago</dt>
                                <dd class="mt-1 text-sm text-gray-900 capitalize">
                                    {{ gasto.metodo_pago || '-' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Estado CxP</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <span v-if="gasto.cuentas_por_pagar" 
                                        :class="gasto.cuentas_por_pagar.estado === 'pagada' ? 'text-green-600' : 'text-yellow-600'">
                                        {{ gasto.cuentas_por_pagar.estado }}
                                    </span>
                                    <span v-else class="text-gray-400">-</span>
                                </dd>
                            </div>
                            <div class="md:col-span-2">
                                <dt class="text-sm font-medium text-gray-500">Descripción</dt>
                                <dd class="mt-1 text-sm text-gray-900 whitespace-pre-line">
                                    {{ gasto.notas || '-' }}
                                </dd>
                            </div>
                        </dl>

                        <!-- Totales -->
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <div class="flex justify-end">
                                <div class="w-64">
                                    <div class="flex justify-between py-2">
                                        <span class="text-gray-600">Subtotal:</span>
                                        <span class="text-gray-900">{{ formatCurrency(gasto.subtotal) }}</span>
                                    </div>
                                    <div class="flex justify-between py-2">
                                        <span class="text-gray-600">IVA:</span>
                                        <span class="text-gray-900">{{ formatCurrency(gasto.iva) }}</span>
                                    </div>
                                    <div class="flex justify-between py-3 border-t border-gray-200 font-bold">
                                        <span class="text-gray-900">Total:</span>
                                        <span class="text-xl text-gray-900">{{ formatCurrency(gasto.total) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                        <button v-if="gasto.estado === 'procesada'" @click="cancelGasto"
                            class="px-4 py-2 bg-yellow-100 text-yellow-800 rounded-md hover:bg-yellow-200 transition">
                            Cancelar Gasto
                        </button>
                        <button @click="deleteGasto"
                            class="px-4 py-2 bg-red-100 text-red-800 rounded-md hover:bg-red-200 transition">
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

