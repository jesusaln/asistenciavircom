<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    misTicketsUrgentes: Array,
    misProximasCitas: Array,
    ticketsVencidosCount: Number,
});

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' });
};

const getPrioridadClass = (prioridad) => {
    const classes = {
        urgente: 'border-red-500 bg-red-50',
        alta: 'border-orange-500 bg-orange-50',
        media: 'border-yellow-500 bg-yellow-50',
        baja: 'border-gray-300 bg-gray-50 dark:bg-slate-950',
    };
    return classes[prioridad] || 'border-gray-300 bg-gray-50 dark:bg-slate-950';
};

const getPrioridadTextClass = (prioridad) => {
     const classes = {
        urgente: 'text-red-600',
        alta: 'text-orange-600',
        media: 'text-yellow-600',
        baja: 'text-gray-600 dark:text-gray-300',
    };
    return classes[prioridad] || 'text-gray-600 dark:text-gray-300';
}

</script>

<template>
    <AppLayout title="Mi Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                Mi Dashboard de Agente
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Alerta de Tickets Vencidos -->
                <div v-if="ticketsVencidosCount > 0" class="mb-6 p-4 bg-red-100 border-l-4 border-red-500 rounded-r-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                             <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-bold text-red-700">
                                ¡Atención! Tienes {{ ticketsVencidosCount }} ticket(s) con SLA vencido.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Columna de Tickets -->
                    <div class="lg:col-span-2">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Mis Tickets Más Urgentes</h3>
                        <div class="space-y-4">
                            <div v-if="misTicketsUrgentes.length > 0" v-for="ticket in misTicketsUrgentes" :key="ticket.id" :class="['p-4 bg-white dark:bg-slate-900 rounded-lg shadow-md border-l-4', getPrioridadClass(ticket.prioridad)]">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <Link :href="route('soporte.show', ticket.id)" class="font-bold text-gray-800 dark:text-gray-100 hover:text-indigo-600">
                                            {{ ticket.titulo }}
                                        </Link>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Cliente: {{ ticket.cliente?.nombre_razon_social || 'N/A' }}
                                        </p>
                                    </div>
                                    <span :class="['px-2 py-1 text-xs font-bold rounded-full capitalize', getPrioridadTextClass(ticket.prioridad)]">
                                        {{ ticket.prioridad }}
                                    </span>
                                </div>
                                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    <span>Folio: #{{ ticket.folio }}</span> | <span>Límite: {{ formatDate(ticket.fecha_limite) }}</span>
                                </div>
                            </div>
                            <div v-else class="text-center py-10 bg-white dark:bg-slate-900 rounded-lg shadow-md">
                                <p class="text-gray-500 dark:text-gray-400">¡Excelente! No tienes tickets urgentes asignados.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Columna de Citas -->
                    <div class="lg:col-span-1">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Mis Próximas Citas</h3>
                        <div class="space-y-4">
                            <div v-if="misProximasCitas.length > 0" v-for="cita in misProximasCitas" :key="cita.id" class="p-4 bg-white dark:bg-slate-900 rounded-lg shadow-md border-l-4 border-blue-500">
                                <p class="font-semibold text-gray-800 dark:text-gray-100">{{ formatDate(cita.fecha_hora) }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-300 truncate">{{ cita.descripcion }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Cliente: {{ cita.cliente?.nombre_razon_social || 'N/A' }}</p>
                                <Link :href="route('citas.show', cita.id)" class="text-sm text-indigo-500 hover:underline mt-2 inline-block">
                                    Ver detalles &rarr;
                                </Link>
                            </div>
                            <div v-else class="text-center py-10 bg-white dark:bg-slate-900 rounded-lg shadow-md">
                                <p class="text-gray-500 dark:text-gray-400">No tienes citas próximas en tu agenda.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
