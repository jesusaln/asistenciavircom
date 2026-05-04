<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ClientLayout from '../Layout/ClientLayout.vue';

const props = defineProps({
    citas: Object,
    empresa: Object,
});

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString('es-MX', { dateStyle: 'long', timeStyle: 'short' });
};

const getStatusClass = (status) => {
    const classes = {
        programado: 'bg-blue-100 text-blue-800',
        completado: 'bg-green-100 text-green-800',
        cancelado: 'bg-red-100 text-red-700',
        en_proceso: 'bg-yellow-100 text-yellow-800',
    };
    return classes[status] || 'bg-gray-200 text-gray-800';
};
</script>

<template>
    <Head title="Mis Citas" />
    <ClientLayout :empresa="empresa">
        <div class="px-4 sm:px-6 lg:px-8 py-10">
            <div class="mb-8">
                <h1 class="text-3xl font-black text-gray-900 tracking-tight">Historial de Citas</h1>
                <p class="text-gray-500 font-medium mt-1">Aquí puedes ver todas tus citas de servicio, pasadas y futuras.</p>
            </div>

            <div class="bg-white rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden">
                <div class="divide-y divide-gray-100">
                    <div v-for="cita in citas.data" :key="cita.id" class="p-6 hover:bg-gray-50/50 transition-colors">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex gap-4 items-center">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl" :class="getStatusClass(cita.estado)">
                                    <font-awesome-icon icon="calendar-check" />
                                </div>
                                <div>
                                    <p class="font-bold text-gray-800">{{ formatDate(cita.fecha_hora) }}</p>
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Técnico: {{ cita.tecnico?.name || 'Por Asignar' }}</p>
                                </div>
                            </div>
                            <div class="flex-1 px-4">
                                <p class="text-sm text-gray-700 font-medium truncate">{{ cita.descripcion }}</p>
                            </div>
                            <div class="text-right">
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border" :class="getStatusClass(cita.estado)">
                                    {{ cita.estado }}
                                </span>
                            </div>
                        </div>
                    </div>
                     <div v-if="citas.data.length === 0" class="p-20 text-center">
                        <h3 class="text-lg font-black text-gray-900 mb-2">No hay citas</h3>
                        <p class="text-gray-500 font-medium">No se han registrado citas en su cuenta.</p>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="citas.links.length > 3" class="flex justify-center gap-2 mt-8">
                <template v-for="(link, key) in citas.links" :key="key">
                    <Link 
                        v-if="link.url"
                        :href="link.url"
                        class="px-4 py-2 text-xs font-bold rounded-lg border"
                        :class="link.active ? 'bg-[var(--color-primary)] text-white border-[var(--color-primary)]' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                        v-html="link.label"
                    />
                    <span v-else class="px-4 py-2 text-xs text-gray-400" v-html="link.label"></span>
                </template>
            </div>
        </div>
    </ClientLayout>
</template>
