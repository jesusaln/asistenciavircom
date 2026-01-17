<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import Pagination from '@/Components/Pagination.vue';
import { ref } from 'vue';
import Swal from 'sweetalert2';

defineOptions({ layout: AppLayout });

const props = defineProps({
    traspasos: {
        type: Object,
        required: true
    }
});

const deleting = ref(false);

const deleteTraspaso = (traspaso) => {
    Swal.fire({
        title: '¿Estás seguro?',
        text: `Se revertirá el traspaso de $${formatMonto(traspaso.monto)}. Los fondos regresarán a ${traspaso.origen.nombre} y se retirarán de ${traspaso.destino.nombre}.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar y revertir',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('traspasos-bancarios.destroy', traspaso.id), {
                onBefore: () => deleting.value = true,
                onFinish: () => deleting.value = false,
                onSuccess: () => {
                    Swal.fire('Eliminado', 'El traspaso ha sido eliminado y los saldos revertidos.', 'success');
                }
            });
        }
    });
};

const formatMonto = (val) => {
    const num = Number(val) || 0;
    return num.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-MX', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
</script>

<template>
    <Head title="Traspasos entre Cuentas" />

    <div class="w-full px-6 py-8 animate-fade-in">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 flex items-center">
                    <FontAwesomeIcon icon="exchange-alt" class="h-8 w-8 text-indigo-600 mr-3" />
                    Traspasos entre Cuentas
                </h1>
                <p class="text-gray-600 mt-1">Historial de transferencias realizadas entre tus cuentas bancarias</p>
            </div>
            <Link
                :href="route('traspasos-bancarios.create')"
                class="mt-4 md:mt-0 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center justify-center shadow-md hover:shadow-lg"
            >
                <FontAwesomeIcon icon="plus" class="mr-2" />
                Nuevo Traspaso
            </Link>
        </div>

        <!-- Notification Flash -->
        <div v-if="$page.props.flash?.success" class="mb-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-800 rounded-r shadow-sm">
            {{ $page.props.flash.success }}
        </div>

        <!-- Table Card -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-400 text-xs uppercase tracking-wider">
                            <th class="px-6 py-4 font-semibold">Fecha</th>
                            <th class="px-6 py-4 font-semibold">Origen</th>
                            <th class="px-6 py-4 font-semibold">Destino</th>
                            <th class="px-6 py-4 font-semibold text-right">Monto</th>
                            <th class="px-6 py-4 font-semibold">Referencia</th>
                            <th class="px-6 py-4 font-semibold">Usuario</th>
                            <th class="px-6 py-4 font-semibold text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="traspaso in traspasos.data" :key="traspaso.id" class="hover:bg-gray-50/50 transition-colors group">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900">{{ formatDate(traspaso.fecha) }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm font-semibold text-gray-800">{{ traspaso.origen?.nombre }}</span>
                                    <span class="text-xs text-gray-500">{{ traspaso.origen?.banco }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm font-semibold text-gray-800">{{ traspaso.destino?.nombre }}</span>
                                    <span class="text-xs text-gray-500">{{ traspaso.destino?.banco }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <span class="text-sm font-bold text-gray-900">${{ formatMonto(traspaso.monto) }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="max-w-xs truncate" :title="traspaso.referencia || traspaso.notas">
                                    <span class="text-sm text-gray-600">{{ traspaso.referencia || 'Sin ref.' }}</span>
                                    <p v-if="traspaso.notas" class="text-xs text-gray-400 truncate">{{ traspaso.notas }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 text-xs font-bold mr-2">
                                        {{ traspaso.usuario?.name.charAt(0) }}
                                    </div>
                                    <span class="text-sm text-gray-700">{{ traspaso.usuario?.name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <Link
                                        :href="route('traspasos-bancarios.edit', traspaso.id)"
                                        class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                        title="Editar"
                                    >
                                        <FontAwesomeIcon icon="edit" />
                                    </Link>
                                    <button
                                        @click="deleteTraspaso(traspaso)"
                                        class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                        title="Eliminar y Revertir"
                                    >
                                        <FontAwesomeIcon icon="trash" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="traspasos.data.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center">
                                <FontAwesomeIcon icon="exchange-alt" class="h-12 w-12 text-gray-200 mb-4" />
                                <p class="text-gray-500 font-medium">No se han registrado traspasos bancarios aún.</p>
                                <Link :href="route('traspasos-bancarios.create')" class="text-indigo-600 hover:text-indigo-800 text-sm font-bold mt-2 inline-block">
                                    Realizar el primer traspaso &rarr;
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div v-if="traspasos.links.length > 3" class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                <Pagination :links="traspasos.links" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
