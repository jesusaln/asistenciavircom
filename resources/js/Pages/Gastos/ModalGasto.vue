<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import DialogModal from '@/Components/DialogModal.vue';
import { router } from '@inertiajs/vue3';
import Swal from '@/Utils/Swal';

const props = defineProps({
    show: Boolean,
    gasto: Object,
});

const emit = defineEmits(['close']);

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
        router.post(route('gastos.cancel', props.gasto.id), {}, {
            onSuccess: () => emit('close'),
        });
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
                <div class="mb-6 flex justify-between text-sm text-slate-500">
                    <span>Fecha: {{ formatDate(gasto.fecha_compra) }}</span>
                </div>

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
                            {{ gasto.proveedor?.nombre_razon_social || 'Sin proveedor' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Método de Pago</dt>
                        <dd class="mt-1 text-sm text-slate-900 capitalize">
                            {{ gasto.metodo_pago || '-' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Cuenta Bancaria</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                            {{ gasto.cuenta_bancaria ? `${gasto.cuenta_bancaria.banco} - ${gasto.cuenta_bancaria.nombre}` : '-' }}
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
                        <dd class="mt-1 text-sm text-slate-900 whitespace-pre-line bg-slate-50 p-3 rounded-xl border border-slate-100">
                            {{ gasto.notas || '-' }}
                        </dd>
                    </div>
                    <!-- Comprobante Preview (NUEVO) -->
                    <div v-if="gasto.comprobante_path" class="md:col-span-2 mt-4">
                        <dt class="text-sm font-medium text-slate-500 mb-2">Comprobante / Ticket</dt>
                        <dd class="relative rounded-2xl overflow-hidden border border-slate-200 shadow-sm group">
                            <img :src="`/storage/${gasto.comprobante_path}`" 
                                 class="w-full h-auto max-h-96 object-contain bg-slate-100" 
                                 alt="Comprobante de gasto" />
                            <a :href="`/storage/${gasto.comprobante_path}`" 
                               target="_blank"
                               class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white font-bold gap-2">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                Ver imagen completa
                            </a>
                        </dd>
                    </div>
                </dl>

                <!-- Totales -->
                <div class="mt-6 pt-6 border-t border-slate-200">
                    <div class="flex justify-end">
                        <div class="w-full md:w-1/2">
                            <div class="flex justify-between py-1">
                                <span class="text-slate-500 text-sm">Subtotal:</span>
                                <span class="text-slate-900 text-sm">{{ formatCurrency(gasto.subtotal) }}</span>
                            </div>
                            <div class="flex justify-between py-1">
                                <span class="text-slate-500 text-sm">IVA:</span>
                                <span class="text-slate-900 text-sm">{{ formatCurrency(gasto.iva) }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-t border-slate-200 font-bold mt-2">
                                <span class="text-slate-900">Total:</span>
                                <span class="text-lg text-slate-900">{{ formatCurrency(gasto.total) }}</span>
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
                        class="px-4 py-2 bg-brand-100 text-brand-800 dark:text-brand-200 rounded-xl hover:bg-brand-200 transition text-sm">
                        Cancelar Gasto
                    </button>
                    <button v-if="gasto" @click="deleteGasto"
                        class="px-4 py-2 bg-rose-100 text-rose-800 dark:text-rose-200 rounded-xl hover:bg-rose-200 transition text-sm">
                        Eliminar
                    </button>
                </div>
                <button @click="emit('close')"
                    class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition text-sm border border-slate-300">
                    Cerrar
                </button>
            </div>
        </template>
    </DialogModal>
</template>

