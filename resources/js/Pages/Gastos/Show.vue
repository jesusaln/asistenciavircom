<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Swal from '@/Utils/Swal';

const props = defineProps({
    gasto: Object,
});

const { formatCurrency } = useFormatters();

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
        'procesada': 'bg-emerald-100 text-emerald-800 dark:text-emerald-200',
        'cancelada': 'bg-rose-100 text-rose-800 dark:text-rose-200',
    };
    return badges[estado] || 'bg-slate-100 text-slate-800';
};

const cancelGasto = async () => {
    const { isConfirmed } = await Swal.fire({
        title: 'Cancelar gasto',
        text: '¿Estás seguro de cancelar este gasto?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'No',
    });
    if (isConfirmed) {
        router.post(route('gastos.cancel', props.gasto.id));
    }
};

const deleteGasto = async () => {
    const { isConfirmed } = await Swal.fire({
        title: 'Eliminar gasto',
        text: '¿Estás seguro de eliminar este gasto?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'No',
    });
    if (isConfirmed) {
        router.delete(route('gastos.destroy', props.gasto.id));
    }
};
</script>

<template>
    <AppLayout title="Detalle de Gasto">
        <Head :title="`Gasto ${gasto.numero_compra}`" />

        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                    Gasto {{ gasto.numero_compra }}
                </h2>
                <Link :href="route('gastos.index')"
                    class="text-slate-500 hover:text-slate-900 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Volver
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="w-full sm:px-6 lg:px-8">
                <div class="bg-white shadow rounded-xl overflow-hidden">
                    <!-- Header -->
                    <div class="px-6 py-4 bg-white border-b border-slate-200 flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">{{ gasto.numero_compra }}</h3>
                            <p class="text-sm text-slate-500">{{ formatDate(gasto.fecha_compra) }}</p>
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
                                <dt class="text-sm font-medium text-slate-500">Categoría</dt>
                                <dd class="mt-1 text-sm text-slate-900">
                                    {{ gasto.categoria_gasto?.nombre || '-' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-500">Proveedor</dt>
                                <dd class="mt-1 text-sm text-slate-900">
                                    {{ gasto.proveedor?.nombre || 'Sin proveedor' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-500">Método de Pago</dt>
                                <dd class="mt-1 text-sm text-slate-900 capitalize">
                                    {{ gasto.metodo_pago || '-' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-500">Estado CxP</dt>
                                <dd class="mt-1 text-sm text-slate-900">
                                    <span v-if="gasto.cuentas_por_pagar" 
                                        :class="gasto.cuentas_por_pagar.estado === 'pagada' ? 'text-emerald-600' : 'text-amber-600'">
                                        {{ gasto.cuentas_por_pagar.estado }}
                                    </span>
                                    <span v-else class="text-slate-400">-</span>
                                </dd>
                            </div>
                            <div class="md:col-span-2">
                                <dt class="text-sm font-medium text-slate-500">Descripción</dt>
                                <dd class="mt-1 text-sm text-slate-900 whitespace-pre-line">
                                    {{ gasto.notas || '-' }}
                                </dd>
                            </div>
                        </dl>

                        <!-- Totales -->
                        <div class="mt-6 pt-6 border-t border-slate-200">
                            <div class="flex justify-end">
                                <div class="w-64">
                                    <div class="flex justify-between py-2">
                                        <span class="text-slate-500">Subtotal:</span>
                                        <span class="text-slate-900">{{ formatCurrency(gasto.subtotal) }}</span>
                                    </div>
                                    <div class="flex justify-between py-2">
                                        <span class="text-slate-500">IVA:</span>
                                        <span class="text-slate-900">{{ formatCurrency(gasto.iva) }}</span>
                                    </div>
                                    <div class="flex justify-between py-3 border-t border-slate-200 font-bold">
                                        <span class="text-slate-900">Total:</span>
                                        <span class="text-xl text-slate-900">{{ formatCurrency(gasto.total) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="px-6 py-4 bg-white border-t border-slate-200 flex justify-end gap-3">
                        <button v-if="gasto.estado === 'procesada'" @click="cancelGasto"
                            class="px-4 py-2 bg-brand-100 text-brand-800 dark:text-brand-200 rounded-xl hover:bg-brand-200 transition">
                            Cancelar Gasto
                        </button>
                        <button @click="deleteGasto"
                            class="px-4 py-2 bg-rose-100 text-rose-800 dark:text-rose-200 rounded-xl hover:bg-rose-200 transition">
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

