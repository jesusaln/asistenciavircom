<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ClientLayout from '../Layout/ClientLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
    poliza: Object,
    empresa: Object,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-MX', { 
        day: '2-digit', month: 'short', year: 'numeric' 
    });
};

const getEstadoBadge = (estado) => {
    const colores = {
        activa: 'bg-emerald-50 text-emerald-600 border-emerald-100',
        inactiva: 'bg-amber-50 text-amber-600 border-amber-100',
        vencida: 'bg-red-50 text-red-600 border-red-100',
        cancelada: 'bg-white text-gray-500 border-gray-100',
        pendiente_pago: 'bg-purple-50 text-purple-600 border-purple-100',
    };
    return colores[estado] || 'bg-white text-gray-800';
};
</script>

<template>
    <Head :title="`Póliza ${poliza.folio}`" />

    <ClientLayout :empresa="empresa">
        <div class="px-2 sm:px-0">
            <!-- Header -->
            <div class="mb-8">
                <Link :href="route('portal.dashboard')" class="text-xs uppercase tracking-widest font-bold text-gray-400 hover:text-[var(--color-primary)] mb-4 inline-block transition-colors">
                    ← Volver al Panel
                </Link>
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-8 rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <span class="font-mono text-sm font-black text-[var(--color-primary)] uppercase tracking-widest">{{ poliza.folio }}</span>
                            <span :class="['px-3 py-1 text-[10px] font-black rounded-full border uppercase tracking-widest', getEstadoBadge(poliza.estado)]">
                                {{ poliza.estado?.replace('_', ' ') }}
                            </span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight">{{ poliza.nombre }}</h1>
                        <p class="text-gray-500 font-medium text-sm mt-1">Vence: <strong class="text-gray-700">{{ formatDate(poliza.fecha_fin) }}</strong></p>
                    </div>
                    <div class="flex gap-3 flex-wrap">
                        <a :href="route('portal.polizas.imprimir', poliza.id)" target="_blank" class="px-6 py-3 bg-white text-gray-700 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-gray-100 transition-all border border-gray-200">
                            <font-awesome-icon icon="file-signature" class="mr-2" /> Contrato
                        </a>
                        <Link :href="route('portal.tickets.create', { poliza_id: poliza.id })" class="px-6 py-3 bg-[var(--color-primary)] text-white rounded-xl font-black text-xs uppercase tracking-widest hover:shadow-lg transition-all">
                            <font-awesome-icon icon="life-ring" class="mr-2" /> Solicitar Soporte
                        </Link>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Columna Principal -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Descripción -->
                    <div class="bg-white rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100 p-8">
                        <h3 class="font-black text-gray-900 uppercase tracking-tight mb-4 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-[var(--color-primary-soft)] text-[var(--color-primary)] flex items-center justify-center text-sm">
                                <font-awesome-icon icon="info-circle" />
                            </div>
                            Alcance del Servicio
                        </h3>
                        <p class="text-gray-600 font-medium leading-relaxed whitespace-pre-wrap">{{ poliza.descripcion || 'Sin descripción detallada.' }}</p>
                    </div>

                    <!-- Consumo Actual -->
                    <div class="bg-white rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100 p-8">
                         <h3 class="font-black text-gray-900 uppercase tracking-tight mb-6 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                                <font-awesome-icon icon="chart-pie" />
                            </div>
                            Consumo Mensual
                        </h3>

                        <div class="grid sm:grid-cols-2 gap-8">
                            <!-- Barra de Horas -->
                            <div v-if="poliza.horas_incluidas_mensual > 0">
                                <div class="flex justify-between items-end mb-2">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Horas de Soporte</p>
                                    <p class="text-xs font-bold" :class="poliza.excede_horas ? 'text-red-500' : 'text-gray-600'">
                                        {{ poliza.horas_consumidas_mes || 0 }} / {{ poliza.horas_incluidas_mensual }} hrs
                                    </p>
                                </div>
                                <div class="w-full bg-white rounded-full h-2 overflow-hidden">
                                        <div 
                                        class="h-full rounded-full transition-all duration-1000 ease-out" 
                                        :class="poliza.excede_horas ? 'bg-red-500' : 'bg-[var(--color-primary)]'"
                                        :style="{ width: Math.min(poliza.porcentaje_horas || 0, 100) + '%' }"
                                        ></div>
                                </div>
                                <p class="text-[10px] text-gray-400 mt-2 font-medium">Se reinicia el día {{ poliza.dia_cobro }} de cada mes.</p>
                            </div>

                            <!-- Barra de Tickets -->
                            <div v-if="poliza.limite_mensual_tickets > 0">
                                <div class="flex justify-between items-end mb-2">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Tickets Incluidos</p>
                                    <p class="text-xs font-bold text-gray-600">
                                        {{ poliza.tickets_mes_actual_count || 0 }} / {{ poliza.limite_mensual_tickets }}
                                    </p>
                                </div>
                                <div class="w-full bg-white rounded-full h-2 overflow-hidden">
                                        <div 
                                        class="bg-emerald-500 h-full rounded-full transition-all duration-1000 ease-out" 
                                        :style="{ width: Math.min(poliza.porcentaje_tickets || 0, 100) + '%' }"
                                        ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Equipos Vinculados -->
                    <div class="bg-white rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100 p-8">
                         <h3 class="font-black text-gray-900 uppercase tracking-tight mb-6 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                                <font-awesome-icon icon="desktop" />
                            </div>
                            Equipos Protegidos ({{ poliza.equipos?.length || 0 }})
                        </h3>
                        
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div v-for="equipo in poliza.equipos" :key="equipo.id" class="flex items-center gap-4 p-4 border border-gray-50 rounded-2xl hover:border-blue-100 hover:bg-blue-50/30 transition-all">
                                <div class="w-10 h-10 bg-white border border-gray-100 rounded-xl flex items-center justify-center text-gray-400">
                                    <font-awesome-icon icon="desktop" />
                                </div>
                                <div>
                                    <p class="font-bold text-gray-900 text-sm">{{ equipo.nombre }}</p>
                                    <p class="font-mono text-[10px] text-gray-400 uppercase tracking-widest">{{ equipo.serie || 'S/N: N/A' }}</p>
                                </div>
                            </div>
                             <div v-if="(!poliza.equipos || poliza.equipos.length === 0)" class="col-span-full py-8 text-center text-gray-400 text-sm font-medium italic">
                                No hay equipos vinculados específicamente.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-8">
                     <!-- Detalles Técnicos -->
                    <div class="bg-white rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100 p-8">
                        <h3 class="font-black text-gray-900 uppercase tracking-tight mb-4 text-sm">Características</h3>
                        <ul class="space-y-4">
                            <li class="flex justify-between items-center text-sm">
                                <span class="text-gray-500 font-medium">SLA Respuesta</span>
                                <span class="font-black text-[var(--color-primary)] bg-blue-50 px-3 py-1 rounded-full text-xs">{{ poliza.sla_horas_respuesta ? poliza.sla_horas_respuesta + ' hrs' : 'Estándar' }}</span>
                            </li>
                             <li class="flex justify-between items-center text-sm">
                                <span class="text-gray-500 font-medium">Renovación</span>
                                <span class="font-bold text-gray-900">{{ poliza.renovacion_automatica ? 'Automática' : 'Manual' }}</span>
                            </li>
                             <li class="flex justify-between items-center text-sm">
                                <span class="text-gray-500 font-medium">Facturación</span>
                                <span class="font-bold text-gray-900">Mensual</span>
                            </li>
                             <li class="flex justify-between items-center text-sm pt-4 border-t border-gray-50">
                                <span class="text-gray-500 font-medium">Precio Base</span>
                                <span class="font-black text-gray-900">{{ formatCurrency(poliza.monto_mensual) }}<span class="text-[10px] font-normal text-gray-400">/mes</span></span>
                            </li>
                        </ul>
                    </div>

                    <!-- Aviso de privacidad o Legal simplificado -->
                     <div class="bg-white rounded-[2rem] p-6 text-center border border-gray-100">
                        <p class="text-[10px] text-gray-400 uppercase tracking-widest font-black mb-2">Soporte Técnico</p>
                        <p class="text-xs text-gray-500 font-medium mb-4">¿Tiene problemas con sus equipos cubiertos?</p>
                         <Link :href="route('portal.tickets.create', { poliza_id: poliza.id })" class="inline-block px-6 py-3 bg-white border border-gray-200 text-gray-700 rounded-xl font-black text-xs uppercase tracking-widest hover:border-[var(--color-primary)] hover:text-[var(--color-primary)] transition-all">
                            Abrir Ticket
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </ClientLayout>
</template>
