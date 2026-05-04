<script setup>
import DialogModal from '@/Components/DialogModal.vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    gasto: Object,
});

const emit = defineEmits(['close']);

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
        router.post(route('gastos.cancel', props.gasto.id), {}, {
            onSuccess: () => emit('close'),
        });
    }
};

const deleteGasto = () => {
    if (confirm('¿Estás seguro de eliminar este gasto?')) {
        router.delete(route('gastos.destroy', props.gasto.id), {
            onSuccess: () => emit('close'),
        });
    }
};
</script>

<template>
    <DialogModal :show="show" @close="emit('close')" max-width="2xl">
        <template #title>
            <div class="flex justify-between items-center pr-4">
                <span>Detalle de Gasto {{ gasto?.numero_compra }}</span>
                <span v-if="gasto" :class="getEstadoBadge(gasto.estado)"
                    class="px-3 py-1 text-sm font-semibold rounded-full">
                    {{ gasto.estado }}
                </span>
            </div>
        </template>

        <template #content>
            <div v-if="gasto" class="mt-4">
                <!-- Header Info -->
                <div class="mb-6 flex justify-between text-sm text-gray-500">
                    <span>Fecha: {{ formatDate(gasto.fecha_compra) }}</span>
                </div>

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
                            {{ gasto.proveedor?.nombre_razon_social || 'Sin proveedor' }}
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
                        <dd class="mt-1 text-sm text-gray-900 whitespace-pre-line bg-white p-3 rounded-md">
                            {{ gasto.notas || '-' }}
                        </dd>
                    </div>
                </dl>

                <!-- Totales -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex justify-end">
                        <div class="w-full md:w-1/2">
                            <div class="flex justify-between py-1">
                                <span class="text-gray-600 text-sm">Subtotal:</span>
                                <span class="text-gray-900 text-sm">{{ formatCurrency(gasto.subtotal) }}</span>
                            </div>
                            <div class="flex justify-between py-1">
                                <span class="text-gray-600 text-sm">IVA:</span>
                                <span class="text-gray-900 text-sm">{{ formatCurrency(gasto.iva) }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-t border-gray-200 font-bold mt-2">
                                <span class="text-gray-900">Total:</span>
                                <span class="text-lg text-gray-900">{{ formatCurrency(gasto.total) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <div class="flex justify-between w-full">
                 <div class="flex gap-2">
                    <button v-if="gasto?.estado === 'procesada'" @click="cancelGasto"
                        class="px-4 py-2 bg-yellow-100 text-yellow-800 rounded-md hover:bg-yellow-200 transition text-sm">
                        Cancelar Gasto
                    </button>
                    <button v-if="gasto" @click="deleteGasto"
                        class="px-4 py-2 bg-red-100 text-red-800 rounded-md hover:bg-red-200 transition text-sm">
                        Eliminar
                    </button>
                </div>
                <button @click="emit('close')"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition text-sm border border-gray-300">
                    Cerrar
                </button>
            </div>
        </template>
    </DialogModal>
</template>

