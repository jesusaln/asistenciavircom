<template>
    <AppLayout title="Editar Cuenta por Cobrar">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                    Editar Cuenta por Cobrar
                </h2>
                <Link
                    :href="route('cuentas-por-cobrar.index')"
                    class="bg-white dark:bg-slate-9000 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                >
                    Cancelar
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="w-full sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-slate-900 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white dark:bg-slate-900 border-b border-gray-200 dark:border-slate-800">
                        <!-- Información de la Venta -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Información de la Venta</h3>
                            <div class="bg-white dark:bg-slate-900 p-4 rounded-lg">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Número de Venta</label>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ cuenta.venta?.numero_venta || 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Cliente</label>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ cuenta.venta?.cliente?.nombre_razon_social || 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Total de la Venta</label>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ cuenta.venta ? formatCurrency(cuenta.venta.total) : 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Estado Actual</label>
                                        <span :class="{
                                            'bg-red-100 text-red-800': cuenta.estado === 'vencido',
                                            'bg-yellow-100 text-yellow-800': cuenta.estado === 'parcial',
                                            'bg-green-100 text-green-800': cuenta.estado === 'pagado',
                                            'bg-gray-100 text-gray-800 dark:text-gray-100': cuenta.estado === 'pendiente'
                                        }" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                            {{ cuenta.estado }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información de Pagos -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Estado de Pagos</h3>
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-blue-700">Monto Total</label>
                                        <p class="mt-1 text-lg font-semibold text-blue-900">{{ formatCurrency(cuenta.monto_total) }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-blue-700">Monto Pagado</label>
                                        <p class="mt-1 text-lg font-semibold text-green-600">{{ formatCurrency(cuenta.monto_pagado) }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-blue-700">Monto Pendiente</label>
                                        <p class="mt-1 text-lg font-semibold text-red-600">{{ formatCurrency(cuenta.monto_pendiente) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form @submit.prevent="submit">
                            <!-- Registrar Pago -->
                            <div class="mb-6">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Registrar Nuevo Pago</h3>
                                <div class="bg-green-50 p-4 rounded-lg">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="monto_pago" class="block text-sm font-medium text-gray-700">
                                                Monto del Pago
                                            </label>
                                            <input
                                                v-model="pagoForm.monto"
                                                type="number"
                                                step="0.01"
                                                id="monto_pago"
                                                :max="montoPendiente"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                                :class="{ 'border-red-500': pagoForm.errors.monto }"
                                                placeholder="0.00"
                                            />
                                            <p v-if="pagoForm.errors.monto" class="mt-2 text-sm text-red-600">
                                                {{ pagoForm.errors.monto }}
                                            </p>
                                        </div>
                                        <div>
                                            <label for="notas_pago" class="block text-sm font-medium text-gray-700">
                                                Notas del Pago
                                            </label>
                                            <input
                                                v-model="pagoForm.notas"
                                                type="text"
                                                id="notas_pago"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                                :class="{ 'border-red-500': pagoForm.errors.notas }"
                                                placeholder="Referencia del pago..."
                                            />
                                            <p v-if="pagoForm.errors.notas" class="mt-2 text-sm text-red-600">
                                                {{ pagoForm.errors.notas }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <button
                                            type="button"
                                            @click="registrarPago"
                                            :disabled="pagoForm.processing || !pagoForm.monto"
                                            class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                                        >
                                            <span v-if="pagoForm.processing">Registrando...</span>
                                            <span v-else>Registrar Pago</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Editar Información -->
                            <div class="mb-6">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Editar Información</h3>
                                <div class="bg-white dark:bg-slate-900 p-4 rounded-lg">
                                    <!-- Fecha de Vencimiento -->
                                    <div class="mb-4">
                                        <label for="fecha_vencimiento" class="block text-sm font-medium text-gray-700">
                                            Fecha de Vencimiento
                                        </label>
                                        <input
                                            v-model="form.fecha_vencimiento"
                                            type="date"
                                            id="fecha_vencimiento"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500"
                                            :class="{ 'border-red-500': form.errors.fecha_vencimiento }"
                                        />
                                        <p v-if="form.errors.fecha_vencimiento" class="mt-2 text-sm text-red-600">
                                            {{ form.errors.fecha_vencimiento }}
                                        </p>
                                    </div>

                                    <!-- Notas -->
                                    <div class="mb-4">
                                        <label for="notas" class="block text-sm font-medium text-gray-700">
                                            Notas
                                        </label>
                                        <textarea
                                            v-model="form.notas"
                                            id="notas"
                                            rows="4"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500"
                                            :class="{ 'border-red-500': form.errors.notas }"
                                            placeholder="Notas adicionales sobre la cuenta por cobrar..."
                                        ></textarea>
                                        <p v-if="form.errors.notas" class="mt-2 text-sm text-red-600">
                                            {{ form.errors.notas }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Botones -->
                            <div class="flex items-center justify-end">
                                <Link
                                    :href="route('cuentas-por-cobrar.show', cuenta.id)"
                                    class="mr-4 bg-gray-300 hover:bg-gray-400 text-gray-800 dark:text-gray-100 font-bold py-2 px-4 rounded"
                                >
                                    Ver Detalles
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                                >
                                    <span v-if="form.processing">Guardando...</span>
                                    <span v-else>Guardar Cambios</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    cuenta: {
        type: Object,
        required: true,
    },
});

const currencyFormatter = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

const toNumber = (value) => {
    if (value === null || value === undefined) {
        return 0;
    }

    const number = Number(value);
    return Number.isFinite(number) ? number : 0;
};

const formatCurrency = (value) => currencyFormatter.format(toNumber(value));

const montoPendiente = computed(() => toNumber(props.cuenta?.monto_pendiente));

const form = useForm({
    fecha_vencimiento: props.cuenta.fecha_vencimiento ? new Date(props.cuenta.fecha_vencimiento).toISOString().split('T')[0] : '',
    notas: props.cuenta.notas || '',
});

const pagoForm = useForm({
    monto: '',
    notas: '',
});

const submit = () => {
    form.put(route('cuentas-por-cobrar.update', props.cuenta.id), {
        onSuccess: () => {
            // Redirigir al show
        },
        onError: (errors) => {
            console.error('Errores:', errors);
        },
    });
};

const registrarPago = () => {
    pagoForm.post(route('cuentas-por-cobrar.registrar-pago', props.cuenta.id), {
        onSuccess: () => {
            // Limpiar formulario y recargar página
            pagoForm.reset();
            window.location.reload();
        },
        onError: (errors) => {
            console.error('Errores:', errors);
        },
    });
};
</script>


