<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    cuentas: Array,
});

const form = useForm({
    cuenta_origen_id: '',
    cuenta_destino_id: '',
    monto: '',
    fecha: new Date().toISOString().substr(0, 10),
    referencia: '',
    notas: '',
});

const cuentasOrigen = computed(() => props.cuentas);

const cuentasDestino = computed(() => {
    if (!form.cuenta_origen_id) return props.cuentas;
    return props.cuentas.filter(c => c.id !== form.cuenta_origen_id);
});

const submit = () => {
    form.post(route('traspasos-bancarios.store'), {
        onSuccess: () => form.reset(),
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
    }).format(value);
};
</script>

<template>
    <AppLayout title="Nuevo Traspaso">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                Nuevo Traspaso entre Cuentas
            </h2>
        </template>

        <div class="py-12">
            <div class="w-full sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-slate-900 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Origen -->
                            <div>
                                <label class="block font-medium text-sm text-gray-700">Cuenta Origen (Retiro)</label>
                                <select v-model="form.cuenta_origen_id" class="border-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm mt-1 block w-full">
                                    <option value="">Seleccione una cuenta</option>
                                    <option v-for="cuenta in cuentasOrigen" :key="cuenta.id" :value="cuenta.id">
                                        {{ cuenta.banco }} - {{ cuenta.nombre }} ({{ formatCurrency(cuenta.saldo_actual) }})
                                    </option>
                                </select>
                                <div v-if="form.errors.cuenta_origen_id" class="text-red-500 text-xs mt-1">{{ form.errors.cuenta_origen_id }}</div>
                            </div>

                            <!-- Destino -->
                            <div>
                                <label class="block font-medium text-sm text-gray-700">Cuenta Destino (Depósito)</label>
                                <select v-model="form.cuenta_destino_id" class="border-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm mt-1 block w-full">
                                    <option value="">Seleccione una cuenta</option>
                                    <option v-for="cuenta in cuentasDestino" :key="cuenta.id" :value="cuenta.id">
                                        {{ cuenta.banco }} - {{ cuenta.nombre }}
                                    </option>
                                </select>
                                <div v-if="form.errors.cuenta_destino_id" class="text-red-500 text-xs mt-1">{{ form.errors.cuenta_destino_id }}</div>
                            </div>

                            <!-- Monto y Fecha -->
                            <div>
                                <label class="block font-medium text-sm text-gray-700">Monto</label>
                                <input v-model="form.monto" type="number" step="0.01" class="border-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm mt-1 block w-full" placeholder="0.00">
                                <div v-if="form.errors.monto" class="text-red-500 text-xs mt-1">{{ form.errors.monto }}</div>
                            </div>

                            <div>
                                <label class="block font-medium text-sm text-gray-700">Fecha</label>
                                <input v-model="form.fecha" type="date" class="border-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm mt-1 block w-full">
                                <div v-if="form.errors.fecha" class="text-red-500 text-xs mt-1">{{ form.errors.fecha }}</div>
                            </div>

                            <!-- Referencia y Notas -->
                            <div class="col-span-2">
                                <label class="block font-medium text-sm text-gray-700">Referencia / Folio</label>
                                <input v-model="form.referencia" type="text" class="border-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm mt-1 block w-full" placeholder="Ej. TR-12345">
                            </div>

                            <div class="col-span-2">
                                <label class="block font-medium text-sm text-gray-700">Notas Adicionales</label>
                                <textarea v-model="form.notas" class="border-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm mt-1 block w-full" rows="3"></textarea>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <Link :href="route('traspasos-bancarios.index')" class="text-sm text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:text-white mr-4">
                                Volver al listado
                            </Link>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition" :disabled="form.processing">
                                Registrar Traspaso
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

