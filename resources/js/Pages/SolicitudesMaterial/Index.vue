<script setup>
import { useFormatters } from '@/Composables/useFormatters';
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
        case 'Pendiente': return 'bg-brand-100 text-brand-800 dark:text-brand-200 dark:bg-brand-900/30 dark:text-amber-400';
        case 'Aprobada': return 'bg-emerald-100 text-emerald-800 dark:text-emerald-200 dark:bg-slate-800/30 dark:text-slate-400';
        case 'Rechazada': return 'bg-rose-100 text-rose-800 dark:text-rose-200 dark:bg-rose-900/30 dark:text-rose-400';
        case 'En Proceso': return 'bg-sky-100 text-sky-800 dark:text-sky-200 dark:bg-sky-900/30 dark:text-blue-400';
        case 'Entregada': return 'bg-slate-100 text-slate-800 dark:bg-slate-700/30 dark:text-slate-400';
        default: return 'bg-slate-100 text-slate-800';
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
            <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">
                Solicitudes de Material de Técnicos
            </h2>
        </template>

        <div class="py-12">
            <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-12">
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-xl sm:rounded p-6">
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead>
                                <tr class="border-b dark:border-slate-700">
                                    <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-200">Folio</th>
                                    <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-200">Técnico</th>
                                    <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-200">Tipo</th>
                                    <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-200">Prioridad</th>
                                    <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-200">Estado</th>
                                    <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-200">Fecha</th>
                                    <th class="py-3 px-4 text-right font-semibold text-slate-700 dark:text-slate-200">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="solicitud in solicitudes.data" :key="solicitud.id" 
                                    class="border-b dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                    <td class="py-3 px-4 font-mono font-bold text-blue-600 dark:text-blue-400">
                                        {{ solicitud.folio }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-medium text-slate-900 dark:text-slate-100">{{ solicitud.user.name }}</div>
                                    </td>
                                    <td class="py-3 px-4 text-slate-500 dark:text-slate-400">
                                        {{ solicitud.tipo }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-xs font-bold uppercase" :class="solicitud.prioridad === 'Alta' ? 'text-rose-500' : 'text-slate-500'">
                                            {{ solicitud.prioridad }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span :class="getStatusClass(solicitud.estado)" class="px-2.5 py-0.5 rounded-xl text-xs font-medium">
                                            {{ solicitud.estado }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-sm text-slate-500 dark:text-slate-400">
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
                                    <td colspan="7" class="py-12 text-center text-slate-500">
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

        <!-- Modal de Gestión Mejorado -->
        <Modal :show="showModal" @close="showModal = false" max-width="4xl">
            <div class="relative overflow-hidden bg-white dark:bg-slate-900 rounded-2xl shadow-2xl">
                <!-- Background Accents -->
                <div class="absolute -top-24 -right-24 w-64 h-64 bg-brand-500/5 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-blue-500/5 rounded-full blur-3xl"></div>

                <!-- Header Section -->
                <div class="relative z-10 px-8 py-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/30 backdrop-blur-sm">
                    <div class="flex items-center gap-5">
                        <div class="w-14 h-14 bg-gradient-to-br from-brand-400 to-brand-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-brand-500/20">
                            <i class="fas fa-clipboard-list text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                                Gestionar Solicitud <span class="text-brand-500">{{ selectedSolicitud?.folio }}</span>
                            </h3>
                            <div class="flex items-center gap-2 mt-1">
                                <img :src="selectedSolicitud?.user.profile_photo_url || '/images/default-profile.svg'" class="w-5 h-5 rounded-full" />
                                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
                                    Solicitado por <span class="text-slate-700 dark:text-slate-200">{{ selectedSolicitud?.user.name }}</span>
                                </p>
                                <span class="text-slate-300 dark:text-slate-700">•</span>
                                <p class="text-xs font-bold text-slate-400">{{ formatDate(selectedSolicitud?.created_at) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <span :class="getStatusClass(selectedSolicitud?.estado)" class="px-4 py-1.5 rounded-xl text-xs font-black uppercase tracking-widest shadow-sm">
                            {{ selectedSolicitud?.estado }}
                        </span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">Estado Actual</span>
                    </div>
                </div>

                <div class="p-8 relative z-10">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Left Column: Details & Motivo -->
                        <div class="lg:col-span-1 space-y-6">
                            <div class="space-y-4">
                                <div class="bg-slate-50 dark:bg-slate-800/50 p-5 rounded-2xl border border-slate-100 dark:border-slate-700/50 shadow-sm group hover:border-brand-500/30 transition-all duration-300">
                                    <label class="flex items-center gap-2 text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">
                                        <i class="fas fa-info-circle text-brand-500"></i>
                                        Detalles de Solicitud
                                    </label>
                                    <div class="space-y-3">
                                        <div class="flex justify-between items-center">
                                            <span class="text-xs font-bold text-slate-500">Tipo:</span>
                                            <span class="text-sm font-black text-slate-700 dark:text-slate-200">{{ selectedSolicitud?.tipo }}</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-xs font-bold text-slate-500">Prioridad:</span>
                                            <span :class="selectedSolicitud?.prioridad === 'Alta' ? 'text-rose-500' : 'text-brand-500'" class="text-sm font-black">{{ selectedSolicitud?.prioridad }}</span>
                                        </div>
                                        <div v-if="selectedSolicitud?.fecha_requerida" class="flex justify-between items-center">
                                            <span class="text-xs font-bold text-slate-500">Requerido para:</span>
                                            <span class="text-sm font-black text-slate-700 dark:text-slate-200">{{ selectedSolicitud?.fecha_requerida }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-slate-50 dark:bg-slate-800/50 p-5 rounded-2xl border border-slate-100 dark:border-slate-700/50 shadow-sm">
                                    <label class="flex items-center gap-2 text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">
                                        <i class="fas fa-comment-alt text-blue-500"></i>
                                        Motivo del Técnico
                                    </label>
                                    <div class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed font-medium">
                                        <template v-if="selectedSolicitud?.motivo.startsWith('http')">
                                            <p class="mb-4 text-slate-500 italic">El técnico adjuntó un enlace externo:</p>
                                            <a :href="selectedSolicitud?.motivo" target="_blank" 
                                               class="flex items-center justify-center gap-3 w-full py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-xl font-bold transition-all shadow-lg shadow-blue-500/20 active:scale-95">
                                                <i class="fas fa-external-link-alt"></i>
                                                Ver Referencia / Link
                                            </a>
                                        </template>
                                        <template v-else>
                                            <p class="italic">"{{ selectedSolicitud?.motivo }}"</p>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Articles & Form -->
                        <div class="lg:col-span-2 space-y-8">
                            <!-- Articles Table -->
                            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                                <div class="px-5 py-4 bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center">
                                    <span class="text-xs font-black text-slate-500 uppercase tracking-widest">Artículos Solicitados</span>
                                    <span class="text-[10px] font-bold px-2 py-0.5 bg-slate-200 dark:bg-slate-700 rounded text-slate-600 dark:text-slate-400">
                                        {{ selectedSolicitud?.items.length }} Item(s)
                                    </span>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                                        <thead>
                                            <tr class="bg-slate-50/30 dark:bg-slate-800/20">
                                                <th class="py-3 px-5 text-left text-[10px] font-black text-slate-400 uppercase tracking-tighter w-24">Cantidad</th>
                                                <th class="py-3 px-5 text-left text-[10px] font-black text-slate-400 uppercase tracking-tighter">Descripción / Producto</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                                            <tr v-for="item in selectedSolicitud?.items" :key="item.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                                <td class="py-4 px-5">
                                                    <div class="flex flex-col">
                                                        <span class="text-lg font-black text-blue-600 dark:text-blue-400 leading-none">{{ item.cantidad }}</span>
                                                        <span class="text-[10px] font-bold text-slate-400 uppercase mt-1">{{ item.unidad_medida || 'Pza' }}</span>
                                                    </div>
                                                </td>
                                                <td class="py-4 px-5">
                                                    <p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ item.producto?.nombre || item.descripcion }}</p>
                                                    <p v-if="item.producto?.sku" class="text-[10px] text-slate-400 font-mono mt-1">SKU: {{ item.producto.sku }}</p>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Update Form -->
                            <form @submit.prevent="updateStatus" class="space-y-6 pt-6 border-t border-slate-100 dark:border-slate-800">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Cambiar Estado</label>
                                        <div class="relative">
                                            <i class="fas fa-exchange-alt absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                            <select id="estado" v-model="form.estado" 
                                                class="pl-11 block w-full bg-slate-50 dark:bg-slate-800 border-transparent dark:border-slate-700 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 rounded-2xl font-bold transition-all h-12 shadow-sm">
                                                <option value="Pendiente">🟡 Pendiente</option>
                                                <option value="En Proceso">🔵 En Proceso</option>
                                                <option value="Aprobada">✅ Aprobada</option>
                                                <option value="Rechazada">❌ Rechazada</option>
                                                <option value="Entregada">📦 Entregada</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Comentarios / Respuesta</label>
                                        <div class="relative">
                                            <textarea id="comentarios" v-model="form.comentarios_admin" 
                                                class="block w-full bg-slate-50 dark:bg-slate-800 border-transparent dark:border-slate-700 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 rounded-2xl font-medium transition-all shadow-sm"
                                                rows="2" placeholder="Instrucciones de entrega o motivo de rechazo..."></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-end items-center gap-4 pt-4">
                                    <button type="button" @click="showModal = false" 
                                        class="px-8 py-3 rounded-2xl font-black text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors uppercase tracking-widest text-[11px]">
                                        Cancelar
                                    </button>
                                    <button type="submit" :disabled="form.processing"
                                        class="px-10 py-4 bg-gradient-to-r from-brand-500 to-brand-600 text-white rounded-2xl font-black uppercase tracking-widest text-[11px] shadow-xl shadow-brand-500/25 hover:shadow-brand-500/40 hover:-translate-y-0.5 active:scale-95 transition-all disabled:opacity-50">
                                        <i class="fas fa-save mr-2"></i>
                                        Guardar Cambios
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
