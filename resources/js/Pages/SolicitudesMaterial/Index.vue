<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, router } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    solicitudes: Object,
});

const selectedSolicitud = ref(null);
const showModal = ref(false);

const form = useForm({
    estado: '',
    comentarios_admin: '',
});

const openSolicitud = (solicitud) => {
    selectedSolicitud.value = solicitud;
    form.estado = solicitud.estado;
    form.comentarios_admin = solicitud.comentarios_admin || '';
    showModal.value = true;
};

const updateStatus = () => {
    form.put(route('solicitudes-material.update', selectedSolicitud.value.id), {
        onSuccess: () => {
            showModal.value = false;
        },
    });
};

const getStatusClass = (status) => {
    switch (status) {
        case 'Pendiente': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
        case 'Aprobada': return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
        case 'Rechazada': return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
        case 'En Proceso': return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
        case 'Entregada': return 'bg-gray-100 text-gray-800 dark:bg-gray-700/30 dark:text-gray-400';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('es-MX', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <AppLayout title="Solicitudes de Material">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Solicitudes de Material de Técnicos
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b dark:border-gray-700">
                                    <th class="py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Folio</th>
                                    <th class="py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Técnico</th>
                                    <th class="py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Tipo</th>
                                    <th class="py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Prioridad</th>
                                    <th class="py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Estado</th>
                                    <th class="py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Fecha</th>
                                    <th class="py-3 px-4 text-right font-semibold text-gray-700 dark:text-gray-300">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="solicitud in solicitudes.data" :key="solicitud.id" 
                                    class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="py-3 px-4 font-mono font-bold text-blue-600 dark:text-blue-400">
                                        {{ solicitud.folio }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ solicitud.user.name }}</div>
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">
                                        {{ solicitud.tipo }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-xs font-bold uppercase" :class="solicitud.prioridad === 'Alta' ? 'text-red-500' : 'text-gray-500'">
                                            {{ solicitud.prioridad }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span :class="getStatusClass(solicitud.estado)" class="px-2.5 py-0.5 rounded-full text-xs font-medium">
                                            {{ solicitud.estado }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatDate(solicitud.created_at) }}
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <button @click="openSolicitud(solicitud)" 
                                            class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 font-bold flex items-center justify-end gap-1 ml-auto">
                                            <i class="fas fa-eye w-4 h-4"></i>
                                            Gestionar
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="solicitudes.data.length === 0">
                                    <td colspan="7" class="py-12 text-center text-gray-500">
                                        No se encontraron solicitudes pendientes.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        <Pagination :links="solicitudes.links" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Gestión -->
        <Modal :show="showModal" @close="showModal = false" max-width="2xl">
            <div class="p-6">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            Gestionar Solicitud {{ selectedSolicitud?.folio }}
                        </h3>
                        <p class="text-sm text-gray-500">Solicitado por {{ selectedSolicitud?.user.name }}</p>
                    </div>
                    <span :class="getStatusClass(selectedSolicitud?.estado)" class="px-3 py-1 rounded-full text-sm font-bold">
                        {{ selectedSolicitud?.estado }}
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Motivo del Técnico</label>
                        <p class="text-sm text-gray-700 dark:text-gray-300 italic">"{{ selectedSolicitud?.motivo }}"</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Detalles</label>
                        <div class="text-sm">
                            <span class="font-bold">Tipo:</span> {{ selectedSolicitud?.tipo }}<br>
                            <span class="font-bold">Prioridad:</span> {{ selectedSolicitud?.prioridad }}
                        </div>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Artículos Solicitados</label>
                    <div class="border dark:border-gray-700 rounded-xl overflow-hidden">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="py-2 px-4 text-left">Cant.</th>
                                    <th class="py-2 px-4 text-left">Descripción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in selectedSolicitud?.items" :key="item.id" class="border-t dark:border-gray-700">
                                    <td class="py-2 px-4 font-bold text-blue-600">{{ item.cantidad }} {{ item.unidad_medida || 'Pza' }}</td>
                                    <td class="py-2 px-4 dark:text-gray-300">{{ item.producto?.nombre || item.descripcion }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <form @submit.prevent="updateStatus" class="space-y-4 pt-4 border-t dark:border-gray-700">
                    <div>
                        <InputLabel for="estado" value="Cambiar Estado" />
                        <select id="estado" v-model="form.estado" 
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                            <option value="Pendiente">Pendiente</option>
                            <option value="En Proceso">En Proceso</option>
                            <option value="Aprobada">Aprobada</option>
                            <option value="Rechazada">Rechazada</option>
                            <option value="Entregada">Entregada</option>
                        </select>
                    </div>

                    <div>
                        <InputLabel for="comentarios" value="Comentarios / Respuesta" />
                        <textarea id="comentarios" v-model="form.comentarios_admin" 
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                            rows="3" placeholder="Instrucciones de entrega o motivo de rechazo..."></textarea>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <SecondaryButton @click="showModal = false">Cancelar</SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            Guardar Cambios
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
