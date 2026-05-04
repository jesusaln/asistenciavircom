<script setup>
import { useForm, Link, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
    traspaso: Object,
});

const form = useForm({
    referencia: props.traspaso.referencia || '',
    notas: props.traspaso.notas || '',
});

const submit = () => {
    form.put(route('traspasos-bancarios.update', props.traspaso.id));
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
    }).format(value);
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-MX', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};
</script>

<template>
    <Head title="Editar Traspaso" />

    <AppLayout title="Editar Traspaso">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Editar Traspaso entre Cuentas #{{ traspaso.id }}
            </h2>
        </template>

        <div class="py-12">
            <div class="w-full sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="mb-8 p-4 bg-white rounded-lg border border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Cuenta Origen</span>
                                <p class="text-sm font-bold text-gray-900">{{ traspaso.origen.banco }} - {{ traspaso.origen.nombre }}</p>
                            </div>
                            <div>
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Cuenta Destino</span>
                                <p class="text-sm font-bold text-gray-900">{{ traspaso.destino.banco }} - {{ traspaso.destino.nombre }}</p>
                            </div>
                            <div>
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Monto y Fecha</span>
                                <p class="text-sm font-bold text-gray-900">{{ formatCurrency(traspaso.monto) }}</p>
                                <p class="text-xs text-gray-500">{{ formatDate(traspaso.fecha) }}</p>
                            </div>
                        </div>
                        <div class="mt-4 p-2 bg-amber-50 rounded border border-amber-200 flex items-center">
                            <FontAwesomeIcon icon="info-circle" class="text-amber-600 mr-2" />
                            <span class="text-xs text-amber-800">Solo se pueden editar la referencia y las notas para mantener la integridad bancaria.</span>
                        </div>
                    </div>

                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 gap-6">
                            <!-- Referencia -->
                            <div>
                                <label class="block font-medium text-sm text-gray-700">Referencia / Folio</label>
                                <input v-model="form.referencia" type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" placeholder="Ej. TR-12345">
                                <div v-if="form.errors.referencia" class="text-red-500 text-xs mt-1">{{ form.errors.referencia }}</div>
                            </div>

                            <!-- Notas -->
                            <div>
                                <label class="block font-medium text-sm text-gray-700">Notas Adicionales</label>
                                <textarea v-model="form.notas" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" rows="4" placeholder="Algún detalle adicional sobre el movimiento..."></textarea>
                                <div v-if="form.errors.notas" class="text-red-500 text-xs mt-1">{{ form.errors.notas }}</div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-8 pt-6 border-t border-gray-100">
                            <Link :href="route('traspasos-bancarios.index')" class="text-sm text-gray-600 hover:text-gray-900 mr-6">
                                Cancelar
                            </Link>
                            <button type="submit" class="inline-flex items-center px-6 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-sm text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition disabled:opacity-25 shadow-md shadow-indigo-100" :disabled="form.processing">
                                Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
