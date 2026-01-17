<template>
    <AppLayout title="Crear Cuenta por Pagar">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Crear Cuenta por Pagar
                </h2>
                <Link
                    :href="route('cuentas-por-pagar.index')"
                    class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                >
                    Cancelar
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="w-full sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <form @submit.prevent="submit">
                            <!-- Compra -->
                            <div v-if="compra" class="mb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Información de la Compra</h3>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Número de Compra</label>
                                            <p class="mt-1 text-sm text-gray-900">{{ compra.numero_compra }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Proveedor</label>
                                            <p class="mt-1 text-sm text-gray-900">{{ compra.proveedor?.nombre_razon_social || 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Total de la Compra</label>
                                            <p class="mt-1 text-sm text-gray-900">{{ compra ? formatCurrency(compra.total) : 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Fecha</label>
                                            <p class="mt-1 text-sm text-gray-900">{{ new Date(compra.created_at).toLocaleDateString() }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Seleccionar Compra (si no viene por parámetro) -->
                            <div v-if="!compra" class="mb-6">
                                <label for="compra_id" class="block text-sm font-medium text-gray-700">
                                    Seleccionar Compra
                                </label>
                                <select
                                    v-model="form.compra_id"
                                    id="compra_id"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500"
                                    required
                                >
                                    <option value="">Seleccione una compra</option>
                                    <option
                                        v-for="compraOption in compras"
                                        :key="compraOption.id"
                                        :value="compraOption.id"
                                    >
                                        {{ compraOption.numero_compra }} - {{ compraOption.proveedor?.nombre_razon_social || 'N/A' }} ({{ formatCurrency(compraOption.total) }})
                                    </option>
                                </select>
                                <p v-if="form.errors.compra_id" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.compra_id }}
                                </p>
                            </div>

                            <!-- Monto Total -->
                            <div class="mb-6">
                                <label for="monto_total" class="block text-sm font-medium text-gray-700">
                                    Monto Total
                                </label>
                                <input
                                    v-model="form.monto_total"
                                    type="number"
                                    step="0.01"
                                    id="monto_total"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500"
                                    :class="{ 'border-red-500': form.errors.monto_total }"
                                    required
                                />
                                <p v-if="form.errors.monto_total" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.monto_total }}
                                </p>
                            </div>

                            <!-- Fecha de Vencimiento -->
                            <div class="mb-6">
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
                                <p class="mt-1 text-sm text-gray-500">
                                    Opcional. Si no se especifica, se establecerá automáticamente en 30 días.
                                </p>
                                <p v-if="form.errors.fecha_vencimiento" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.fecha_vencimiento }}
                                </p>
                            </div>

                            <!-- Notas -->
                            <div class="mb-6">
                                <label for="notas" class="block text-sm font-medium text-gray-700">
                                    Notas
                                </label>
                                <textarea
                                    v-model="form.notas"
                                    id="notas"
                                    rows="4"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500"
                                    :class="{ 'border-red-500': form.errors.notas }"
                                    placeholder="Notas adicionales sobre la cuenta por pagar..."
                                ></textarea>
                                <p v-if="form.errors.notas" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.notas }}
                                </p>
                            </div>

                            <!-- Botones -->
                            <div class="flex items-center justify-end">
                                <Link
                                    :href="route('cuentas-por-pagar.index')"
                                    class="mr-4 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded"
                                >
                                    Cancelar
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                                >
                                    <span v-if="form.processing">Creando...</span>
                                    <span v-else>Crear Cuenta por Pagar</span>
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
import { ref, onMounted } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    compra: Object,
    compras: Array,
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

const form = useForm({
    compra_id: props.compra ? props.compra.id : '',
    monto_total: props.compra ? props.compra.total : '',
    fecha_vencimiento: '',
    notas: '',
});

const submit = () => {
    form.post(route('cuentas-por-pagar.store'), {
        onSuccess: () => {
            // Redirigir al índice
        },
        onError: (errors) => {
            console.error('Errores:', errors);
        },
    });
};

onMounted(() => {
    // Si hay una compra específica, establecer valores por defecto
    if (props.compra) {
        form.compra_id = props.compra.id;
        form.monto_total = props.compra.total;

        // Establecer fecha de vencimiento por defecto (30 días)
        const fechaVencimiento = new Date();
        fechaVencimiento.setDate(fechaVencimiento.getDate() + 30);
        form.fecha_vencimiento = fechaVencimiento.toISOString().split('T')[0];
    }
});
</script>

