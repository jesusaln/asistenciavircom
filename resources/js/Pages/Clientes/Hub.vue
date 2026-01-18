<script setup>
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
        abierto: 'bg-blue-100 text-blue-800',
        en_progreso: 'bg-yellow-100 text-yellow-800',
        pendiente: 'bg-orange-100 text-orange-800',
        resuelto: 'bg-green-100 text-green-800',
        cerrado: 'bg-gray-100 text-gray-700',
        programado: 'bg-blue-100 text-blue-800',
        completado: 'bg-green-100 text-green-800',
        cancelado: 'bg-red-100 text-red-800',
    };
    return classes[status] || 'bg-gray-200 text-gray-800';
};

const getProgressBarClass = (percentage) => {
    if (percentage >= 80) return 'bg-red-500';
    if (percentage >= 50) return 'bg-yellow-500';
    return 'bg-green-500';
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
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Hub del Cliente
                </h2>
                <Link :href="route('clientes.index')" class="text-sm text-gray-600 hover:text-gray-900">
                    &larr; Volver a Clientes
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <!-- Header con información clave -->
                    <div class="p-6 sm:px-20 bg-white border-b border-gray-200">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900">{{ cliente.nombre_razon_social }}</h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    <span class="font-mono">{{ cliente.rfc }}</span> | <span>{{ cliente.email }}</span> | <span>{{ cliente.telefono }}</span>
                                </p>
                            </div>
                             <div class="text-right">
                                <p class="text-sm text-gray-500">Saldo Pendiente</p>
                                <p class="text-2xl font-bold text-red-600">{{ new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(saldo_pendiente) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Pestañas -->
                    <div class="bg-white px-6">
                        <div class="border-b border-gray-200">
                            <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" role="tablist">
                                <li v-for="tab in ['resumen', 'polizas', 'tickets', 'citas']" :key="tab" class="mr-2" role="presentation">
                                    <button @click="setActiveTab(tab)" :class="['inline-flex items-center gap-2 p-4 border-b-2 rounded-t-lg', activeTab === tab ? 'border-indigo-500 text-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300']" role="tab">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getIconForTab(tab)"></path></svg>
                                        <span class="capitalize">{{ tab }}</span>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Contenido de las Pestañas -->
                    <div class="p-6 bg-gray-50">
                        <!-- Resumen -->
                        <div v-if="activeTab === 'resumen'" role="tabpanel">
                           <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                               <div class="p-6 bg-white rounded-lg shadow-sm">
                                   <h4 class="font-bold text-gray-800">Póliza Activa</h4>
                                    <div v-if="poliza_activa" class="mt-2">
                                        <p class="text-indigo-600 font-semibold">{{ poliza_activa.nombre }}</p>
                                        <p class="text-sm text-gray-500">Vence: {{ formatDate(poliza_activa.fecha_fin) }}</p>
                                        <Link :href="route('polizas-servicio.show', poliza_activa.id)" class="text-sm text-indigo-500 hover:underline mt-2 inline-block">Ver Póliza &rarr;</Link>
                                    </div>
                                   <div v-else><p class="text-gray-500 mt-2">No hay póliza activa.</p></div>
                               </div>
                               <div class="p-6 bg-white rounded-lg shadow-sm">
                                   <h4 class="font-bold text-gray-800">Próxima Cita</h4>
                                   <div v-if="cliente.citas && cliente.citas.length > 0" class="mt-2">
                                       <p class="font-semibold">{{ formatDate(cliente.citas[0].fecha_hora) }}</p>
                                       <p class="text-sm text-gray-600 truncate">{{ cliente.citas[0].descripcion }}</p>
                                       <p class="text-sm text-gray-500">Técnico: {{ cliente.citas[0].tecnico?.name || 'No asignado' }}</p>
                                   </div>
                                   <div v-else><p class="text-gray-500 mt-2">No hay citas próximas.</p></div>
                               </div>
                               <div class="p-6 bg-white rounded-lg shadow-sm">
                                   <h4 class="font-bold text-gray-800">Último Ticket</h4>
                                   <div v-if="cliente.tickets && cliente.tickets.length > 0" class="mt-2">
                                        <p class="font-semibold text-gray-800 truncate">{{ cliente.tickets[0].titulo }}</p>
                                        <p class="text-sm text-gray-600">Estado: <span :class="['font-semibold px-2 py-0.5 rounded-full text-xs', getStatusClass(cliente.tickets[0].estado)]">{{ cliente.tickets[0].estado }}</span></p>
                                        <Link :href="route('soporte.show', cliente.tickets[0].id)" class="text-sm text-indigo-500 hover:underline mt-2 inline-block">Ver Ticket &rarr;</Link>
                                   </div>
                                   <div v-else><p class="text-gray-500 mt-2">No hay tickets recientes.</p></div>
                               </div>
                           </div>
                        </div>

                        <!-- Pólizas -->
                        <div v-if="activeTab === 'polizas'" role="tabpanel">
                            <h3 class="text-lg font-semibold mb-4 text-gray-800">Póliza de Servicio Activa</h3>
                            <div v-if="poliza_activa" class="p-6 bg-white border rounded-lg shadow-sm space-y-4">
                               <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-xl font-bold text-indigo-700">{{ poliza_activa.nombre }}</p>
                                        <p class="font-mono text-sm text-gray-500">{{ poliza_activa.folio }}</p>
                                    </div>
                                    <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full capitalize" :class="getStatusClass('programado')">{{ poliza_activa.estado }}</span>
                               </div>
                               <div class="grid grid-cols-2 gap-4 text-sm">
                                   <p><strong>Inicio:</strong> {{ formatDate(poliza_activa.fecha_inicio) }}</p>
                                   <p><strong>Fin:</strong> {{ formatDate(poliza_activa.fecha_fin) }}</p>
                               </div>
                               <!-- Barras de Consumo -->
                               <div class="space-y-3 pt-4">
                                   <div v-if="poliza_activa.limite_mensual_tickets">
                                       <div class="flex justify-between text-sm mb-1">
                                           <span class="font-medium">Tickets de Soporte</span>
                                           <span>{{ poliza_activa.tickets_soporte_consumidos_mes || 0 }} / {{ poliza_activa.limite_mensual_tickets }}</span>
                                       </div>
                                       <div class="w-full bg-gray-200 rounded-full h-2.5"><div :class="getProgressBarClass((poliza_activa.tickets_soporte_consumidos_mes/poliza_activa.limite_mensual_tickets)*100)" class="h-2.5 rounded-full" :style="{width: `${(poliza_activa.tickets_soporte_consumidos_mes/poliza_activa.limite_mensual_tickets)*100}%`}"></div></div>
                                   </div>
                                   <div v-if="poliza_activa.horas_incluidas_mensual">
                                       <div class="flex justify-between text-sm mb-1">
                                           <span class="font-medium">Horas de Servicio</span>
                                           <span>{{ poliza_activa.horas_consumidas_mes || 0 }} / {{ poliza_activa.horas_incluidas_mensual }}</span>
                                       </div>
                                       <div class="w-full bg-gray-200 rounded-full h-2.5"><div :class="getProgressBarClass((poliza_activa.horas_consumidas_mes/poliza_activa.horas_incluidas_mensual)*100)" class="h-2.5 rounded-full" :style="{width: `${(poliza_activa.horas_consumidas_mes/poliza_activa.horas_incluidas_mensual)*100}%`}"></div></div>
                                   </div>
                                    <div v-if="poliza_activa.visitas_sitio_mensuales">
                                       <div class="flex justify-between text-sm mb-1">
                                           <span class="font-medium">Visitas en Sitio</span>
                                           <span>{{ poliza_activa.visitas_sitio_consumidas_mes || 0 }} / {{ poliza_activa.visitas_sitio_mensuales }}</span>
                                       </div>
                                       <div class="w-full bg-gray-200 rounded-full h-2.5"><div :class="getProgressBarClass((poliza_activa.visitas_sitio_consumidas_mes/poliza_activa.visitas_sitio_mensuales)*100)" class="h-2.5 rounded-full" :style="{width: `${(poliza_activa.visitas_sitio_consumidas_mes/poliza_activa.visitas_sitio_mensuales)*100}%`}"></div></div>
                                   </div>
                               </div>
                            </div>
                             <div v-else>
                                <p class="text-gray-500 text-center py-10">El cliente no tiene una póliza de servicio activa.</p>
                            </div>
                        </div>

                        <!-- Tickets -->
                        <div v-if="activeTab === 'tickets'" role="tabpanel" class="space-y-4">
                             <div v-for="ticket in cliente.tickets" :key="ticket.id" class="p-4 bg-white rounded-lg shadow-sm border">
                                <div class="flex justify-between items-start">
                                    <Link :href="route('soporte.show', ticket.id)" class="font-semibold text-indigo-600 hover:underline">
                                        {{ ticket.titulo }}
                                    </Link>
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full capitalize', getStatusClass(ticket.estado)]">{{ ticket.estado }}</span>
                                </div>
                               <div class="text-sm text-gray-500 mt-1">
                                   <span>#{{ ticket.folio }}</span> &bull;
                                   <span>Creado: {{ formatDate(ticket.created_at) }}</span> &bull;
                                   <span>Agente: {{ ticket.asignado?.name || 'N/A' }}</span>
                               </div>
                            </div>
                             <div v-if="!cliente.tickets || cliente.tickets.length === 0"><p class="text-gray-500 text-center py-10">No hay tickets registrados para este cliente.</p></div>
                        </div>
                        
                        <!-- Citas -->
                        <div v-if="activeTab === 'citas'" role="tabpanel" class="space-y-4">
                             <div v-for="cita in cliente.citas" :key="cita.id" class="p-4 bg-white rounded-lg shadow-sm border">
                                <div class="flex justify-between items-start">
                                   <Link :href="route('citas.show', cita.id)" class="font-semibold text-indigo-600 hover:underline">
                                        {{ cita.descripcion || 'Cita de servicio' }}
                                   </Link>
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full capitalize', getStatusClass(cita.estado)]">{{ cita.estado }}</span>
                                </div>
                                <div class="text-sm text-gray-500 mt-1">
                                   <span>#{{ cita.folio }}</span> &bull;
                                   <span>Fecha: {{ formatDate(cita.fecha_hora) }}</span> &bull;
                                   <span>Técnico: {{ cita.tecnico?.name || 'N/A' }}</span>
                               </div>
                            </div>
                              <div v-if="!cliente.citas || cliente.citas.length === 0"><p class="text-gray-500 text-center py-10">No hay citas registradas para este cliente.</p></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
