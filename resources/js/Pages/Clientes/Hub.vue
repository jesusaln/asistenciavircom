<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    cliente: Object,
    saldo_pendiente: Number,
    poliza_activa: Object,
});

const activeTab = ref('resumen');

const setActiveTab = (tab) => {
    activeTab.value = tab;
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const getStatusClass = (status) => {
    const classes = {
        abierto: 'bg-sky-100 text-sky-800 dark:text-sky-200',
        en_progreso: 'bg-brand-100 text-brand-800 dark:text-amber-200',
        pendiente: 'bg-brand-100 text-amber-800',
        resuelto: 'bg-emerald-100 text-emerald-800 dark:text-emerald-200',
        cerrado: 'bg-slate-100 text-slate-700',
        programado: 'bg-sky-100 text-sky-800 dark:text-sky-200',
        completado: 'bg-emerald-100 text-emerald-800 dark:text-emerald-200',
        cancelado: 'bg-rose-100 text-rose-800 dark:text-rose-200',
    };
    return classes[status] || 'bg-slate-200 text-slate-800';
};

const getProgressBarClass = (percentage) => {
    if (percentage >= 80) return 'bg-brand-500';
    if (percentage >= 50) return 'bg-brand-500';
    return 'bg-brand-500';
};

const getIconForTab = (tabName) => {
    const icons = {
        resumen: 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z',
        polizas: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
        tickets: 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z',
        citas: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    };
    return icons[tabName];
};

</script>

<template>
    <AppLayout title="Hub del Cliente">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">
                    Hub del Cliente
                </h2>
                <Link :href="route('clientes.index')" class="text-sm text-slate-500 dark:text-slate-200 hover:text-slate-900 dark:hover:text-slate-100">
                    &larr; Volver a Clientes
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-xl dark:shadow-none sm:rounded">
                    <!-- Header con información clave -->
                    <div class="p-6 sm:px-20 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ cliente.nombre_razon_social }}</h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                                    <span class="font-mono">{{ cliente.rfc }}</span> | <span>{{ cliente.email }}</span> | <span>{{ cliente.telefono }}</span>
                                </p>
                                <p v-if="cliente.direccion_completa" class="text-sm text-slate-500 dark:text-slate-200 mt-2 flex items-center gap-1">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                    <span>{{ cliente.direccion_completa }}</span>
                                </p>
                            </div>
                             <div class="text-right">
                                <p class="text-sm text-slate-500 dark:text-slate-400">Saldo Pendiente</p>
                                <p class="text-2xl font-bold text-rose-600 dark:text-rose-400">{{ new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(saldo_pendiente) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Pestañas -->
                    <div class="bg-white dark:bg-slate-800 px-6">
                        <div class="border-b border-slate-200 dark:border-slate-700">
                            <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" role="tablist">
                                <li v-for="tab in ['resumen', 'polizas', 'tickets', 'citas']" :key="tab" class="mr-2" role="presentation">
                                    <button @click="setActiveTab(tab)" :class="['inline-flex items-center gap-2 p-4 border-b-2 rounded-t-lg', activeTab === tab ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-brand-600 dark:hover:text-slate-300 hover:border-brand-500 dark:hover:border-brand-500']" role="tab">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getIconForTab(tab)"></path></svg>
                                        <span class="capitalize">{{ tab }}</span>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Contenido de las Pestañas -->
                    <div class="p-6 bg-[var(--ui-surface)] dark:bg-slate-800">
                        <!-- Resumen -->
                        <div v-if="activeTab === 'resumen'" role="tabpanel">
                           <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                               <div class="p-6 bg-white dark:bg-slate-800 rounded-2xl shadow-sm">
                                   <h4 class="font-bold text-slate-800 dark:text-slate-100">Póliza Activa</h4>
                                    <div v-if="poliza_activa" class="mt-2">
                                        <p class="text-indigo-600 dark:text-indigo-400 font-semibold">{{ poliza_activa.nombre }}</p>
                                        <p class="text-sm text-slate-500 dark:text-slate-400">Vence: {{ formatDate(poliza_activa.fecha_fin) }}</p>
                                        <Link :href="route('polizas-servicio.show', poliza_activa.id)" class="text-sm text-indigo-500 dark:text-indigo-400 hover:underline mt-2 inline-block">Ver Póliza &rarr;</Link>
                                    </div>
                                   <div v-else><p class="text-slate-500 dark:text-slate-400 mt-2">No hay póliza activa.</p></div>
                               </div>
                               <div class="p-6 bg-white dark:bg-slate-800 rounded-2xl shadow-sm">
                                   <h4 class="font-bold text-slate-800 dark:text-slate-100">Próxima Cita</h4>
                                   <div v-if="cliente.citas && cliente.citas.length > 0" class="mt-2">
                                       <p class="font-semibold text-slate-800 dark:text-slate-100">{{ formatDate(cliente.citas[0].fecha_hora) }}</p>
                                       <p class="text-sm text-slate-500 dark:text-slate-200 truncate">{{ cliente.citas[0].descripcion }}</p>
                                       <p class="text-sm text-slate-500 dark:text-slate-400">Técnico: {{ cliente.citas[0].tecnico?.name || 'No asignado' }}</p>
                                   </div>
                                   <div v-else><p class="text-slate-500 dark:text-slate-400 mt-2">No hay citas próximas.</p></div>
                               </div>
                               <div class="p-6 bg-white dark:bg-slate-800 rounded-2xl shadow-sm">
                                   <h4 class="font-bold text-slate-800 dark:text-slate-100">Último Ticket</h4>
                                   <div v-if="cliente.tickets && cliente.tickets.length > 0" class="mt-2">
                                        <p class="font-semibold text-slate-800 dark:text-slate-100 truncate">{{ cliente.tickets[0].titulo }}</p>
                                        <p class="text-sm text-slate-500 dark:text-slate-200">Estado: <span :class="['font-semibold px-2 py-0.5 rounded-xl text-xs', getStatusClass(cliente.tickets[0].estado)]">{{ cliente.tickets[0].estado }}</span></p>
                                        <Link :href="route('soporte.show', cliente.tickets[0].id)" class="text-sm text-indigo-500 dark:text-indigo-400 hover:underline mt-2 inline-block">Ver Ticket &rarr;</Link>
                                   </div>
                                   <div v-else><p class="text-slate-500 dark:text-slate-400 mt-2">No hay tickets recientes.</p></div>
                               </div>
                           </div>
                        </div>

                        <!-- Pólizas -->
                        <div v-if="activeTab === 'polizas'" role="tabpanel">
                            <h3 class="text-lg font-semibold mb-4 text-slate-800 dark:text-slate-100">Póliza de Servicio Activa</h3>
                            <div v-if="poliza_activa" class="p-6 bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl shadow-sm space-y-6">
                               <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-xl font-bold text-indigo-700 dark:text-indigo-400">{{ poliza_activa.nombre }}</p>
                                        <p class="font-mono text-sm text-slate-500 dark:text-slate-400">{{ poliza_activa.folio }}</p>
                                    </div>
                                    <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full capitalize" :class="getStatusClass('programado')">{{ poliza_activa.estado }}</span>
                               </div>
                               <div class="grid grid-cols-2 gap-4 text-sm">
                                   <p><strong class="text-slate-800 dark:text-slate-100">Inicio:</strong> <span class="text-slate-500 dark:text-slate-200">{{ formatDate(poliza_activa.fecha_inicio) }}</span></p>
                                   <p><strong class="text-slate-800 dark:text-slate-100">Fin:</strong> <span class="text-slate-500 dark:text-slate-200">{{ formatDate(poliza_activa.fecha_fin) }}</span></p>
                               </div>
                               <!-- Barras de Consumo -->
                               <div class="space-y-3 pt-4">
                                   <div v-if="poliza_activa.limite_mensual_tickets">
                                       <div class="flex justify-between text-sm mb-1">
                                           <span class="font-medium text-slate-700 dark:text-slate-200">Tickets de Soporte</span>
                                           <span class="text-slate-500 dark:text-slate-200">{{ poliza_activa.tickets_soporte_consumidos_mes || 0 }} / {{ poliza_activa.limite_mensual_tickets }}</span>
                                       </div>
                                       <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2.5"><div :class="getProgressBarClass((poliza_activa.tickets_soporte_consumidos_mes/poliza_activa.limite_mensual_tickets)*100)" class="h-2.5 rounded-full" :style="{width: `${(poliza_activa.tickets_soporte_consumidos_mes/poliza_activa.limite_mensual_tickets)*100}%`}"></div></div>
                                   </div>
                                   <div v-if="poliza_activa.horas_incluidas_mensual">
                                       <div class="flex justify-between text-sm mb-1">
                                           <span class="font-medium text-slate-700 dark:text-slate-200">Horas de Servicio</span>
                                           <span class="text-slate-500 dark:text-slate-200">{{ poliza_activa.horas_consumidas_mes || 0 }} / {{ poliza_activa.horas_incluidas_mensual }}</span>
                                       </div>
                                       <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2.5"><div :class="getProgressBarClass((poliza_activa.horas_consumidas_mes/poliza_activa.horas_incluidas_mensual)*100)" class="h-2.5 rounded-full" :style="{width: `${(poliza_activa.horas_consumidas_mes/poliza_activa.horas_incluidas_mensual)*100}%`}"></div></div>
                                   </div>
                                    <div v-if="poliza_activa.visitas_sitio_mensuales">
                                       <div class="flex justify-between text-sm mb-1">
                                           <span class="font-medium text-slate-700 dark:text-slate-200">Visitas en Sitio</span>
                                           <span class="text-slate-500 dark:text-slate-200">{{ poliza_activa.visitas_sitio_consumidas_mes || 0 }} / {{ poliza_activa.visitas_sitio_mensuales }}</span>
                                       </div>
                                       <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2.5"><div :class="getProgressBarClass((poliza_activa.visitas_sitio_consumidas_mes/poliza_activa.visitas_sitio_mensuales)*100)" class="h-2.5 rounded-full" :style="{width: `${(poliza_activa.visitas_sitio_consumidas_mes/poliza_activa.visitas_sitio_mensuales)*100}%`}"></div></div>
                                   </div>
                               </div>
                            </div>
                             <div v-else>
                                <p class="text-slate-500 dark:text-slate-400 text-center py-10">El cliente no tiene una póliza de servicio activa.</p>
                            </div>
                        </div>

                        <!-- Tickets -->
                        <div v-if="activeTab === 'tickets'" role="tabpanel" class="space-y-6">
                             <div v-for="ticket in cliente.tickets" :key="ticket.id" class="p-4 bg-white dark:bg-slate-800 rounded-2xl shadow-sm border dark:border-slate-700">
                                <div class="flex justify-between items-start">
                                    <Link :href="route('soporte.show', ticket.id)" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ ticket.titulo }}
                                    </Link>
                                    <span :class="['px-2.5 py-0.5 text-xs font-medium rounded-full capitalize', getStatusClass(ticket.estado)]">{{ ticket.estado }}</span>
                                </div>
                               <div class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                                   <span>#{{ ticket.folio }}</span> &bull;
                                   <span>Creado: {{ formatDate(ticket.created_at) }}</span> &bull;
                                   <span>Agente: {{ ticket.asignado?.name || 'N/A' }}</span>
                               </div>
                            </div>
                             <div v-if="!cliente.tickets || cliente.tickets.length === 0"><p class="text-slate-500 dark:text-slate-400 text-center py-10">No hay tickets registrados para este cliente.</p></div>
                        </div>
                        
                        <!-- Citas -->
                        <div v-if="activeTab === 'citas'" role="tabpanel" class="space-y-6">
                             <div v-for="cita in cliente.citas" :key="cita.id" class="p-4 bg-white dark:bg-slate-800 rounded-2xl shadow-sm border dark:border-slate-700">
                                <div class="flex justify-between items-start">
                                   <Link :href="route('citas.show', cita.id)" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ cita.descripcion || 'Cita de servicio' }}
                                   </Link>
                                    <span :class="['px-2.5 py-0.5 text-xs font-medium rounded-full capitalize', getStatusClass(cita.estado)]">{{ cita.estado }}</span>
                                </div>
                                <div class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                                   <span>#{{ cita.folio }}</span> &bull;
                                   <span>Fecha: {{ formatDate(cita.fecha_hora) }}</span> &bull;
                                   <span>Técnico: {{ cita.tecnico?.name || 'N/A' }}</span>
                               </div>
                            </div>
                              <div v-if="!cliente.citas || cliente.citas.length === 0"><p class="text-slate-500 dark:text-slate-400 text-center py-10">No hay citas registradas para este cliente.</p></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
