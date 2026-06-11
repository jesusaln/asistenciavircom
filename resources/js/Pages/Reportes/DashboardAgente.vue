<script setup>
import { useFormatters } from '@/Composables/useFormatters';
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
        urgente: 'border-rose-500 bg-rose-50 dark:bg-rose-900/20',
        alta: 'border-brand-500 bg-orange-50',
        media: 'border-brand-500 bg-brand-50 dark:bg-brand-900/20',
        baja: 'border-slate-300 bg-slate-50',
    };
    return classes[prioridad] || 'border-slate-300 bg-slate-50';
};

const getPrioridadTextClass = (prioridad) => {
     const classes = {
        urgente: 'text-rose-600',
        alta: 'text-orange-600',
        media: 'text-amber-600',
        baja: 'text-slate-500',
    };
    return classes[prioridad] || 'text-slate-500';
}

</script>

<template>
    <AppLayout title="Mi Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                Mi Dashboard de Agente
            </h2>
        </template>

        <div class="py-12">
            <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-12">
                <!-- Alerta de Tickets Vencidos -->
                <div v-if="ticketsVencidosCount > 0" class="mb-6 p-4 bg-rose-100 border-l-4 border-rose-500 rounded-r-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                             <svg class="h-6 w-6 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-bold text-rose-800 dark:text-rose-200 dark:text-rose-200">
                                ¡Atención! Tienes {{ ticketsVencidosCount }} ticket(s) con SLA vencido.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Columna de Tickets -->
                    <div class="lg:col-span-2">
                        <h3 class="text-lg font-semibold text-slate-900 mb-4">Mis Tickets Más Urgentes</h3>
                        <div class="space-y-6">
                            <div v-if="misTicketsUrgentes.length > 0" v-for="ticket in misTicketsUrgentes" :key="ticket.id" :class="['p-4 bg-white rounded-2xl shadow-xl border-l-4', getPrioridadClass(ticket.prioridad)]">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <Link :href="route('soporte.show', ticket.id)" class="font-bold text-slate-800 hover:text-amber-600">
                                            {{ ticket.titulo }}
                                        </Link>
                                        <p class="text-sm text-slate-500">
                                            Cliente: {{ ticket.cliente?.nombre_razon_social || 'N/A' }}
                                        </p>
                                    </div>
                                    <span :class="['px-2.5 py-0.5 text-xs font-bold rounded-full capitalize', getPrioridadTextClass(ticket.prioridad)]">
                                        {{ ticket.prioridad }}
                                    </span>
                                </div>
                                <div class="mt-2 text-xs text-slate-500">
                                    <span>Folio: #{{ ticket.folio }}</span> | <span>Límite: {{ formatDate(ticket.fecha_limite) }}</span>
                                </div>
                            </div>
                            <div v-else class="text-center py-10 bg-white rounded-2xl shadow-xl">
                                <p class="text-slate-500">¡Excelente! No tienes tickets urgentes asignados.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Columna de Citas -->
                    <div class="lg:col-span-1">
                        <h3 class="text-lg font-semibold text-slate-900 mb-4">Mis Próximas Citas</h3>
                        <div class="space-y-6">
                            <div v-if="misProximasCitas.length > 0" v-for="cita in misProximasCitas" :key="cita.id" class="p-4 bg-white rounded-2xl shadow-xl border-l-4 border-blue-500">
                                <p class="font-semibold text-slate-800">{{ formatDate(cita.fecha_hora) }}</p>
                                <p v-if="cita.folio" class="text-xs font-mono font-bold text-brand-800 dark:text-brand-200 dark:text-brand-200 mt-0.5">Folio {{ cita.folio }}</p>
                                <p class="text-sm text-slate-500 truncate">{{ cita.descripcion }}</p>
                                <p class="text-xs text-slate-500">Cliente: {{ cita.cliente?.nombre_razon_social || 'N/A' }}</p>
                                <Link :href="route('citas.show', cita.id)" class="text-sm text-indigo-500 hover:underline mt-2 inline-block">
                                    Ver detalles &rarr;
                                </Link>
                            </div>
                            <div v-else class="text-center py-10 bg-white rounded-2xl shadow-xl">
                                <p class="text-slate-500">No tienes citas próximas en tu agenda.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
