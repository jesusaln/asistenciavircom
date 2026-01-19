<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
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
    ticketsMesActual: Array,
    historicoConsumo: Array,
    consumoPorCategoria: Array // Nuevo prop
});

import { Bar, Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement } from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement);

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                font: { weight: 'bold', size: 10 }
            }
        }
    },
    scales: {
        y: { beginAtZero: true }
    }
};

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'right',
            labels: {
                usePointStyle: true,
                pointStyle: 'circle',
                font: { size: 9 }
            }
        }
    },
    cutout: '60%' // Dona más delgada
};

const consumoData = computed(() => {
    if (!props.consumoPorCategoria || props.consumoPorCategoria.length === 0) {
        return { labels: [], datasets: [{ data: [], backgroundColor: [] }] };
    }

    const labels = props.consumoPorCategoria.map(item => item.categoria);
    const data = props.consumoPorCategoria.map(item => parseFloat(item.total_horas));
    
    // Paleta de colores suaves
    const backgroundColors = [
        '#3B82F6', // Blue 500
        '#10B981', // Emerald 500
        '#8B5CF6', // Violet 500
        '#F59E0B', // Amber 500
        '#EF4444', // Red 500
        '#06B6D4', // Cyan 500
        '#EC4899', // Pink 500
    ];

    return {
        labels: labels,
        datasets: [{
            data: data,
            backgroundColor: backgroundColors.slice(0, data.length),
            borderWidth: 0,
            hoverOffset: 4
        }]
    };
});

const chartData = computed(() => {
    const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    const labels = [];
    const tickets = [];
    const visitas = [];

    // Agrupar por mes
    const dataByMonth = {};
    props.historicoConsumo?.forEach(item => {
        const key = `${item.year}-${item.month}`;
        if (!dataByMonth[key]) {
            dataByMonth[key] = { tickets: 0, visitas: 0, label: `${months[item.month - 1]} ${item.year}` };
        }
        if (item.tipo === 'ticket') dataByMonth[key].tickets = item.total;
        if (item.tipo === 'visita') dataByMonth[key].visitas = item.total;
    });

    Object.values(dataByMonth).forEach(d => {
        labels.push(d.label);
        tickets.push(d.tickets);
        visitas.push(d.visitas);
    });

    return {
        labels,
        datasets: [
            {
                label: 'Tickets',
                backgroundColor: '#3b82f6',
                data: tickets
            },
            {
                label: 'Visitas',
                backgroundColor: '#10b981',
                data: visitas
            }
        ]
    };
});

const mostrarAlertaLimite = computed(() => {
    return props.poliza.porcentaje_tickets >= 80 || props.poliza.porcentaje_horas >= 80;
});

const severityAlerta = computed(() => {
    if (props.poliza.porcentaje_tickets >= 100 || props.poliza.porcentaje_horas >= 100) return 'critical';
    return 'warning';
});

const puedeRenovar = computed(() => {
    return props.poliza.dias_para_vencer <= 30 && props.poliza.estado !== 'cancelada';
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

// Cálculo de ahorro del cliente con precios reales (Mejora 2.4)
const ahorroMensual = () => {
    // Obtener costos unitarios (de la póliza o defaults)
    const costoHora = parseFloat(props.poliza.costo_hora_excedente || props.poliza.plan_poliza?.costo_hora_extra || 650);
    const costoVisita = parseFloat(props.poliza.costo_visita_sitio_extra || props.poliza.plan_poliza?.costo_visita_extra || 650);
    const costoTicket = parseFloat(props.poliza.plan_poliza?.costo_ticket_extra || 150);

    const horasUsadas = parseFloat(props.poliza.horas_consumidas_mes || 0);
    const visitasUsadas = parseInt(props.poliza.visitas_sitio_consumidas_mes || 0);
    const ticketsUsados = parseInt(props.poliza.tickets_mes_actual_count || props.poliza.tickets_soporte_consumidos_mes || 0);
    
    const ahorroHoras = horasUsadas * costoHora;
    const ahorroVisitas = visitasUsadas * costoVisita;
    const ahorroTickets = ticketsUsados * costoTicket;
    
    return ahorroHoras + ahorroVisitas + ahorroTickets;
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

                <!-- Alerta de Límite de Consumo (Mejora 4.2) -->
                <div v-if="mostrarAlertaLimite" 
                    :class="[
                        'mb-6 p-4 rounded-2xl border flex items-center gap-4 animate-pulse',
                        severityAlerta === 'critical' ? 'bg-red-50 border-red-100 text-red-700' : 'bg-amber-50 border-amber-100 text-amber-700'
                    ]">
                    <div :class="['w-10 h-10 rounded-full flex items-center justify-center', severityAlerta === 'critical' ? 'bg-red-100' : 'bg-amber-100']">
                        <font-awesome-icon :icon="severityAlerta === 'critical' ? 'exclamation-triangle' : 'info-circle'" />
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-black uppercase tracking-tight">
                            {{ severityAlerta === 'critical' ? '¡Límite alcanzado!' : 'Aviso de consumo' }}
                        </p>
                        <p class="text-xs font-medium opacity-80">
                            {{ severityAlerta === 'critical' ? 'Has utilizado el 100% de tus recursos incluidos. No te preocupes, seguiremos atendiéndote, se aplicarán cargos por excedentes.' : 'Has utilizado más del 80% de tus recursos del mes. Te sugerimos moderar el uso o considerar un upgrade.' }}
                        </p>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-8 rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <span class="font-mono text-sm font-black text-[var(--color-primary)] uppercase tracking-widest">{{ poliza.folio }}</span>
                            <span :class="['px-3 py-1 text-[10px] font-black rounded-full border uppercase tracking-widest', getEstadoBadge(poliza.estado)]">
                                {{ poliza.estado?.replace('_', ' ') }}
                            </span>
                            <!-- Badge de Firma -->
                            <span v-if="poliza.firmado_at" class="px-3 py-1 text-[10px] font-black rounded-full border uppercase tracking-widest bg-emerald-50 text-emerald-600 border-emerald-200">
                                ✓ Firmado
                            </span>
                            <span v-else class="px-3 py-1 text-[10px] font-black rounded-full border uppercase tracking-widest bg-amber-50 text-amber-600 border-amber-200 animate-pulse">
                                ⚠️ Sin Firmar
                            </span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight">{{ poliza.nombre }}</h1>
                        <p class="text-gray-500 font-medium text-sm mt-1">Vence: <strong class="text-gray-700">{{ formatDate(poliza.fecha_fin) }}</strong></p>
                    </div>
                    <div class="flex gap-2 flex-wrap">
                        <!-- Botón de Firma Digital (Si no está firmada) -->
                        <Link v-if="!poliza.firmado_at" :href="route('portal.polizas.firmar', poliza.id)" class="px-4 py-3 bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest hover:from-purple-600 hover:to-indigo-700 transition-all shadow-lg shadow-purple-200 flex items-center gap-2 animate-pulse">
                            <font-awesome-icon icon="signature" /> 
                            <span>Firmar Contrato</span>
                        </Link>
                        <a :href="route('portal.polizas.contrato.pdf', poliza.id)" target="_blank" class="px-4 py-3 bg-white text-slate-600 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-slate-50 transition-all border-2 border-slate-100 flex items-center gap-2">
                            <font-awesome-icon icon="file-pdf" /> 
                            <span>Contrato</span>
                        </a>
                        <a :href="route('portal.polizas.beneficios.pdf', poliza.id)" target="_blank" class="px-4 py-3 bg-emerald-50 text-emerald-600 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-emerald-100 transition-all border-2 border-emerald-100 flex items-center gap-2">
                            <font-awesome-icon icon="chart-pie" /> 
                            <span>Informe Ahorro</span>
                        </a>
                        <!-- Botón de Renovación Anticipada (Mejora 4.3) -->
                        <Link v-if="puedeRenovar" :href="route('portal.checkout', { plan: poliza.plan_poliza_id, poliza_id: poliza.id })" class="px-4 py-3 bg-amber-500 text-white rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-amber-600 transition-all shadow-lg shadow-amber-200 flex items-center gap-2">
                            <font-awesome-icon icon="sync" /> 
                            <span>Renovar Ahora</span>
                        </Link>
                        <a :href="route('portal.polizas.export-calendar', poliza.id)" class="px-4 py-3 bg-white text-indigo-600 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-indigo-50 transition-all border-2 border-indigo-100 flex items-center gap-2">
                            <font-awesome-icon icon="calendar-alt" /> 
                            <span>Calendario</span>
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
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="font-black text-gray-900 uppercase tracking-tight flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                                    <font-awesome-icon icon="chart-pie" />
                                </div>
                                Consumo Mensual
                            </h3>
                            <Link :href="route('portal.polizas.historial', poliza.id)" class="text-[10px] font-black text-[var(--color-primary)] uppercase tracking-widest hover:underline">
                                Ver Historial →
                            </Link>
                        </div>

                        <div class="grid lg:grid-cols-2 gap-8">
                            <!-- Gráfica de Dona -->
                            <div v-if="consumoData && consumoData.datasets[0].data.length > 0" class="flex flex-col items-center justify-center p-4 bg-gray-50/50 rounded-2xl border border-gray-100">
                                <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Distribución de Horas</h4>
                                <div class="w-48 h-48 relative">
                                    <Doughnut :data="consumoData" :options="doughnutOptions" />
                                </div>
                            </div>

                            <!-- Barra de Horas y Detalles -->
                            <div class="space-y-6">
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
                        </div> <!-- Cierra el grid de 2 columnas -->

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
                                    <p class="text-xs text-emerald-600/70 mt-1">vs. pagar servicios individuales a tarifas estándar</p>
                                </div>
                                <div class="text-5xl opacity-30">🎉</div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfica de Consumo Histórico (Mejora 4.1) -->
                    <div class="bg-white rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100 p-8">
                        <h3 class="font-black text-gray-900 uppercase tracking-tight mb-6 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm">
                                <font-awesome-icon icon="chart-bar" />
                            </div>
                            Tendencia de Consumo (Últimos 6 meses)
                        </h3>
                        <div class="h-64">
                            <Bar v-if="historicoConsumo?.length" :data="chartData" :options="chartOptions" />
                            <div v-else class="h-full flex flex-col items-center justify-center text-gray-400">
                                <font-awesome-icon icon="chart-bar" size="2x" class="mb-2 opacity-20" />
                                <p class="text-xs font-medium">Aún no hay suficiente historial para mostrar la gráfica.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Detalle de Tickets Consumidos este mes (Mejora 4.4) -->
                    <div class="bg-white rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100 p-8">
                        <h3 class="font-black text-gray-900 uppercase tracking-tight mb-6 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                                <font-awesome-icon icon="ticket-alt" />
                            </div>
                            Tickets Aplicados a Póliza (Este Mes)
                        </h3>
                        
                        <div v-if="ticketsMesActual?.length" class="space-y-4">
                            <div v-for="ticket in ticketsMesActual" :key="ticket.id" class="flex items-center justify-between p-4 border border-gray-50 rounded-2xl bg-gray-50/30">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center text-blue-500 font-black text-xs shadow-sm border border-gray-100">
                                        #{{ ticket.folio }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900 text-sm mb-0.5">{{ ticket.titulo }}</p>
                                        <p class="text-[10px] font-medium text-gray-400 uppercase tracking-widest">{{ formatDate(ticket.created_at) }} • {{ ticket.categoria?.nombre || 'Soporte' }}</p>
                                    </div>
                                </div>
                                <Link :href="route('portal.tickets.show', ticket.id)" class="px-4 py-2 bg-white text-gray-600 border border-gray-100 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-gray-50 transition-all">
                                    Ver Detalle
                                </Link>
                            </div>
                        </div>
                        <div v-else class="text-center py-8">
                            <p class="text-sm font-medium text-gray-400 italic">No se han registrado consumos de tickets en el periodo actual.</p>
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
                                <div class="flex items-center gap-1 group relative cursor-help">
                                    <span class="text-gray-500 font-medium border-b border-dashed border-gray-300">Garantía SLA</span>
                                    <font-awesome-icon icon="circle-info" class="text-gray-300 text-[10px]" />
                                    <!-- Tooltip simple -->
                                    <div class="absolute bottom-full left-0 mb-2 w-48 p-2 bg-gray-800 text-white text-[10px] rounded shadow-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10">
                                        Tiempo máximo garantizado para iniciar la atención de sus reportes.
                                    </div>
                                </div>
                                <span class="font-black text-[var(--color-primary)] bg-blue-50 px-3 py-1 rounded-full text-xs">{{ poliza.sla_horas_respuesta ? poliza.sla_horas_respuesta + ' horas' : 'Estándar' }}</span>
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
                                <span class="text-gray-500 font-medium">Inversión</span>
                                <div class="text-right">
                                    <span class="font-black text-gray-900 block">{{ formatCurrency(poliza.monto_mensual) }}</span>
                                    <span class="text-[10px] font-bold text-slate-500 block">+ IVA / mes</span>
                                </div>
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
