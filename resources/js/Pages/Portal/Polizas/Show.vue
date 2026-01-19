<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import ClientLayout from '../Layout/ClientLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import DialogModal from '@/Components/DialogModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

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

// Cálculo de ahorro del cliente
const PRECIO_SERVICIO_NORMAL = 650; // Precio por hora o visita sin póliza

const ahorroMensual = () => {
    const horasUsadas = props.poliza.horas_consumidas_mes || 0;
    const visitasUsadas = props.poliza.visitas_sitio_consumidas_mes || 0;
    const ticketsUsados = props.poliza.tickets_soporte_mes_count || props.poliza.tickets_mes_actual_count || 0;
    
    // Cada hora y visita tiene un valor de $650
    const valorServiciosUsados = (horasUsadas + visitasUsadas) * PRECIO_SERVICIO_NORMAL;
    // Cada ticket tiene un valor estimado de $150
    const valorTickets = ticketsUsados * 150;
    
    return valorServiciosUsados + valorTickets;
};

// Calcular próximo cobro basado en día de cobro
const proximoCobro = () => {
    const dia = props.poliza.dia_cobro || 1;
    const hoy = new Date();
    let fecha = new Date(hoy.getFullYear(), hoy.getMonth(), dia);
    if (fecha <= hoy) {
        fecha.setMonth(fecha.getMonth() + 1);
    }
    return fecha.toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric' });
};

// Visitas en sitio restantes
const visitasRestantes = () => {
    const limite = props.poliza.visitas_sitio_mensuales || 0;
    const consumidas = props.poliza.visitas_sitio_consumidas_mes || 0;
    return Math.max(0, limite - consumidas);
};

// Tickets restantes
const ticketsRestantes = () => {
    const limite = props.poliza.limite_mensual_tickets || 0;
    const consumidos = props.poliza.tickets_mes_actual_count || props.poliza.tickets_soporte_consumidos_mes || 0;
    return Math.max(0, limite - consumidos);
};

// Tipo de póliza icono
const tipoPolizaIcono = () => {
    const tipo = props.poliza.plan_poliza?.tipo || props.poliza.tipo || 'soporte';
    const iconos = {
        'soporte': '💻',
        'cctv': '📹',
        'alarmas': '🚨',
        'pos': '🛒',
        'asesoria': '💡',
        'premium': '💎',
    };
    return iconos[tipo] || '🛡️';
};

// --- MANTENIMIENTOS (FASE 2) ---
const modalSolicitudAbierto = ref(false);
const mantenimientoSeleccionado = ref(null);
const formSolicitud = useForm({
    mantenimiento_id: null,
    fecha_solicitada: '',
    hora_solicitada: '',
    notas: '',
});

const abrirSolicitud = (mantenimiento) => {
    mantenimientoSeleccionado.value = mantenimiento;
    formSolicitud.reset();
    formSolicitud.mantenimiento_id = mantenimiento.id;
    // Pre-set fecha para mañana
    const mañana = new Date();
    mañana.setDate(mañana.getDate() + 1);
    formSolicitud.fecha_solicitada = mañana.toISOString().split('T')[0];
    formSolicitud.hora_solicitada = '09:00';
    
    modalSolicitudAbierto.value = true;
};

const cerrarSolicitud = () => {
    modalSolicitudAbierto.value = false;
    formSolicitud.reset();
    mantenimientoSeleccionado.value = null;
};

const enviarSolicitud = () => {
    formSolicitud.post(route('portal.polizas.mantenimientos.store'), {
        onSuccess: () => {
            cerrarSolicitud();
            // Opcional: Mostrar toast de éxito si ClientLayout lo soporta o confiar en el flash message
        },
    });
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
                    <div class="flex gap-2 flex-wrap">
                        <a :href="route('portal.polizas.contrato.pdf', poliza.id)" target="_blank" class="px-4 py-3 bg-white text-slate-600 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-slate-50 transition-all border-2 border-slate-100 flex items-center gap-2">
                            <font-awesome-icon icon="file-pdf" /> 
                            <span>Contrato</span>
                        </a>
                        <a :href="route('portal.polizas.beneficios.pdf', poliza.id)" target="_blank" class="px-4 py-3 bg-emerald-50 text-emerald-600 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-emerald-100 transition-all border-2 border-emerald-100 flex items-center gap-2">
                            <font-awesome-icon icon="chart-pie" /> 
                            <span>Informe Ahorro</span>
                        </a>
                        <Link :href="route('portal.tickets.create', { poliza_id: poliza.id })" class="px-6 py-3 bg-blue-600 shadow-lg shadow-blue-200 text-white rounded-xl font-black text-xs uppercase tracking-widest hover:bg-blue-700 transition-all flex items-center gap-2">
                            <font-awesome-icon icon="life-ring" /> 
                            <span>Solicitar Soporte</span>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Columna Principal -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Descripción -->
                    <div class="bg-white rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100 p-8">
                        <h3 class="font-black text-gray-900 uppercase tracking-tight mb-6 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                                <font-awesome-icon icon="star" />
                            </div>
                            Beneficios Incluidos
                        </h3>
                        <div v-if="poliza.plan_poliza?.beneficios" class="grid sm:grid-cols-2 gap-4">
                            <div v-for="(beneficio, idx) in poliza.plan_poliza.beneficios" :key="idx" class="flex items-start gap-3 p-4 bg-gray-50/50 rounded-2xl border border-gray-100">
                                <font-awesome-icon icon="check-circle" class="text-blue-500 mt-0.5" />
                                <span class="text-sm font-bold text-gray-700 leading-snug">{{ beneficio }}</span>
                            </div>
                        </div>
                        <p v-else class="text-gray-600 font-medium leading-relaxed whitespace-pre-wrap">{{ poliza.descripcion || 'Servicios integrales de soporte y mantenimiento.' }}</p>
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

                            <!-- Barra de Visitas en Sitio -->
                            <div v-if="poliza.visitas_sitio_mensuales > 0">
                                <div class="flex justify-between items-end mb-2">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Visitas en Sitio</p>
                                    <p class="text-xs font-bold" :class="visitasRestantes() <= 0 ? 'text-amber-500' : 'text-gray-600'">
                                        {{ poliza.visitas_sitio_consumidas_mes || 0 }} / {{ poliza.visitas_sitio_mensuales }}
                                    </p>
                                </div>
                                <div class="w-full bg-white rounded-full h-2 overflow-hidden">
                                        <div 
                                        class="h-full rounded-full transition-all duration-1000 ease-out"
                                        :class="visitasRestantes() <= 0 ? 'bg-amber-500' : 'bg-purple-500'"
                                        :style="{ width: Math.min(((poliza.visitas_sitio_consumidas_mes || 0) / poliza.visitas_sitio_mensuales) * 100, 100) + '%' }"
                                        ></div>
                                </div>
                                <p v-if="visitasRestantes() <= 0" class="text-[10px] text-amber-500 mt-2 font-bold">
                                    ⚠️ Visitas adicionales: {{ formatCurrency(poliza.costo_visita_sitio_extra || 650) }} c/u
                                </p>
                            </div>
                        </div>

                        <!-- Info de Reinicio -->
                        <div class="mt-6 p-4 bg-gray-50 rounded-xl flex items-center justify-between">
                            <div>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Próximo Reinicio de Consumos</p>
                                <p class="text-sm font-bold text-gray-700">{{ proximoCobro() }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Próximo Cobro</p>
                                <p class="text-sm font-bold text-[var(--color-primary)]">{{ formatCurrency(poliza.monto_mensual) }}</p>
                            </div>
                        </div>

                        <!-- Ahorro del Cliente -->
                        <div v-if="ahorroMensual() > 0" class="mt-8 p-6 bg-gradient-to-r from-emerald-50 to-teal-50 rounded-2xl border border-emerald-100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-1">💰 Tu Ahorro Este Mes</p>
                                    <p class="text-3xl font-black text-emerald-700">{{ formatCurrency(ahorroMensual()) }}</p>
                                    <p class="text-xs text-emerald-600/70 mt-1">vs. pagar servicios individuales a $650 c/u</p>
                                </div>
                                <div class="text-5xl opacity-30">🎉</div>
                            </div>
                        </div>
                    </div>

                    <!-- FASE 2: Mantenimientos Incluidos (Autoservicio) -->
                    <div v-if="poliza.mantenimientos && poliza.mantenimientos.length > 0" class="bg-white rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100 p-8">
                         <h3 class="font-black text-gray-900 uppercase tracking-tight mb-6 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-sm">
                                <font-awesome-icon icon="tools" />
                            </div>
                            Mantenimientos Incluidos
                        </h3>
                        
                        <div class="grid gap-4">
                            <div v-for="mant in poliza.mantenimientos" :key="mant.id" class="flex flex-col sm:flex-row sm:items-center justify-between p-4 border border-gray-100 rounded-2xl bg-gray-50/50 hover:bg-white hover:shadow-lg transition-all">
                                <div class="mb-4 sm:mb-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-widest bg-gray-200 text-gray-600 rounded-full">{{ mant.frecuencia }}</span>
                                        <span v-if="mant.requiere_visita" class="px-2 py-0.5 text-[10px] font-black uppercase tracking-widest bg-amber-100 text-amber-700 rounded-full">Requiere Visita</span>
                                    </div>
                                    <h4 class="font-bold text-gray-900">{{ mant.nombre }}</h4>
                                    <p class="text-xs text-gray-500 mt-1">{{ mant.descripcion || 'Mantenimiento preventivo programado.' }}</p>
                                </div>
                                <button @click="abrirSolicitud(mant)" class="px-4 py-2 bg-white text-purple-600 border border-purple-200 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-purple-600 hover:text-white transition-all shadow-sm">
                                    Solicitar Ahora
                                </button>
                            </div>
                        </div>

                        <!-- Historial de Ejecuciones Recientes -->
                        <div v-if="poliza.mantenimientos_ejecuciones && poliza.mantenimientos_ejecuciones.length > 0" class="mt-8">
                            <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4">Actividad Reciente</h4>
                            <div class="space-y-3">
                                <div v-for="ejc in poliza.mantenimientos_ejecuciones" :key="ejc.id" class="flex items-center gap-3 text-sm">
                                    <div class="w-2 h-2 rounded-full" :class="{
                                        'bg-emerald-500': ejc.resultado === 'ok' || ejc.estado === 'completado',
                                        'bg-amber-500': ejc.resultado === 'alerta' || ejc.estado === 'pendiente',
                                        'bg-red-500': ejc.resultado === 'critico' || ejc.estado === 'vencido'
                                    }"></div>
                                    <span class="text-gray-900 font-medium flex-1">
                                        {{ ejc.mantenimiento ? ejc.mantenimiento.nombre : 'Mantenimiento' }}
                                    </span>
                                    <span class="text-gray-400 text-xs tabular-nums">
                                        {{ formatDate(ejc.fecha_programada) }}
                                    </span>
                                    <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-md" :class="{
                                        'bg-emerald-100 text-emerald-700': ejc.estado === 'completado',
                                        'bg-amber-100 text-amber-700': ejc.estado === 'pendiente',
                                    }">{{ ejc.estado }}</span>
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

        <!-- Modal Solicitud Mantenimiento -->
        <DialogModal :show="modalSolicitudAbierto" @close="cerrarSolicitud">
            <template #title>
                Solicitar Mantenimiento: {{ mantenimientoSeleccionado?.nombre }}
            </template>
            <template #content>
                <div class="space-y-4">
                    <p class="text-sm text-gray-600">
                        Indica cuándo te gustaría recibir este servicio. Un técnico confirmará la disponibilidad.
                    </p>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Fecha Preferida" />
                            <input type="date" v-model="formSolicitud.fecha_solicitada" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" :min="new Date().toISOString().split('T')[0]">
                            <InputError :message="formSolicitud.errors.fecha_solicitada" />
                        </div>
                        <div>
                            <InputLabel value="Hora Preferida" />
                            <input type="time" v-model="formSolicitud.hora_solicitada" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <InputError :message="formSolicitud.errors.hora_solicitada" />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Notas Adicionales (Opcional)" />
                        <textarea v-model="formSolicitud.notas" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="Ej: Favor de revisar específicamente el equipo del mostrador..."></textarea>
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="cerrarSolicitud">Cancelar</SecondaryButton>
                <PrimaryButton class="ml-2" @click="enviarSolicitud" :disabled="formSolicitud.processing">
                    {{ formSolicitud.processing ? 'Enviando...' : 'Agendar Solicitud' }}
                </PrimaryButton>
            </template>
        </DialogModal>

    </ClientLayout>
</template>
