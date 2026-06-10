<script setup>
import axios from 'axios';
import { useFormatters } from '@/Composables/useFormatters';
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

import { defineAsyncComponent } from 'vue';

const Bar = defineAsyncComponent(() => import('@/Components/Reportes/AsyncBarChart.vue'));
const Doughnut = defineAsyncComponent(() => import('@/Components/Reportes/AsyncDoughnutChart.vue'));

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
        '#FF6B35', // Blue 500
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

const { formatCurrency } = useFormatters();

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-MX', { 
        day: '2-digit', month: 'short', year: 'numeric' 
    });
};

const getEstadoBadge = (estado) => {
    const colores = {
        activa: 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 border-emerald-100',
        inactiva: 'bg-brand-50 dark:bg-brand-900/20 text-brand-600 border-amber-100',
        vencida: 'bg-rose-50 dark:bg-rose-900/20 text-rose-600 border-rose-100',
        cancelada: 'bg-white text-slate-500 border-slate-100',
        pendiente_pago: 'bg-purple-50 text-purple-600 border-purple-100',
    };
    return colores[estado] || 'bg-white text-slate-800';
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

// --- DETALLE EJECUCIÓN MANTENIMIENTO ---
const modalDetalleEjecucionAbierto = ref(false);
const ejecucionSeleccionada = ref(null);

const abrirDetalleEjecucion = (ejc) => {
    if (ejc.estado === 'completado') {
        ejecucionSeleccionada.value = ejc;
        modalDetalleEjecucionAbierto.value = true;
    }
};

const cerrarDetalleEjecucion = () => {
    modalDetalleEjecucionAbierto.value = false;
    ejecucionSeleccionada.value = null;
};

// --- HISTORIAL POR EQUIPO (HOJA DE VIDA) ---
const modalEquipoAbierto = ref(false);
const equipoSeleccionado = ref(null);
const cargandoHistorial = ref(false);
const historialEquipo = ref({ citas: [], mantenimientos: [], cotizaciones: [] });

const abrirHistorialEquipo = async (equipo) => {
    equipoSeleccionado.value = equipo;
    modalEquipoAbierto.value = true;
    cargandoHistorial.value = true;
    historialEquipo.value = { citas: [], mantenimientos: [], cotizaciones: [] };
    try {
        const response = await axios.get(route('portal.polizas.historial-equipo', props.poliza.id), {
            params: { equipo: equipo.nombre }
        });
        if (response.data) {
            historialEquipo.value = {
                citas: response.data.citas || [],
                mantenimientos: response.data.mantenimientos || [],
                cotizaciones: response.data.cotizaciones || []
            };
        }
    } catch (error) {
        console.error('Error al cargar historial del equipo:', error);
    } finally {
        cargandoHistorial.value = false;
    }
};

const cerrarHistorialEquipo = () => {
    modalEquipoAbierto.value = false;
    equipoSeleccionado.value = null;
    historialEquipo.value = { citas: [], mantenimientos: [], cotizaciones: [] };
};

const getFotoUrl = (foto) => {
    if (!foto) return '';
    if (foto.startsWith('http')) return foto;
    return '/storage/' + foto;
};

// Helper para parsear fecha en formato "d/m/Y H:i" o "d/m/Y"
const parseDateString = (dateStr) => {
    if (!dateStr) return new Date(0);
    const parts = dateStr.split(' ');
    const dateParts = parts[0].split('/');
    let year = parseInt(dateParts[2]);
    let month = parseInt(dateParts[1]) - 1;
    let day = parseInt(dateParts[0]);
    let hour = 0;
    let min = 0;
    if (parts[1]) {
        const timeParts = parts[1].split(':');
        hour = parseInt(timeParts[0]);
        min = parseInt(timeParts[1]);
    }
    return new Date(year, month, day, hour, min);
};

const combinedTimeline = computed(() => {
    const list = [];
    
    // 1. Citas
    historialEquipo.value.citas.forEach(c => {
        list.push({
            id: 'cita_' + c.id,
            fecha_raw: c.fecha,
            fecha_sort: parseDateString(c.fecha),
            fecha: c.fecha,
            tipo: 'Servicio Soporte',
            icon: 'wrench',
            color: 'text-blue-500 bg-blue-50 dark:bg-blue-950/40 border-blue-200 dark:border-blue-800/30',
            titulo: c.tipo || 'Visita Técnica',
            subtitulo: `Técnico: ${c.tecnico}`,
            descripcion: c.trabajo_realizado || 'Sin detalles.',
            fotos: c.fotos || []
        });
    });

    // 2. Mantenimientos
    historialEquipo.value.mantenimientos.forEach(m => {
        list.push({
            id: 'maint_' + m.id,
            fecha_raw: m.fecha,
            fecha_sort: parseDateString(m.fecha),
            fecha: m.fecha,
            tipo: 'Mantenimiento Póliza',
            icon: 'check-double',
            color: 'text-emerald-500 bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800/30',
            titulo: m.nombre,
            subtitulo: `S/N: ${m.numero_serie || 'N/A'} | Nivel: ${m.resultado?.toUpperCase()}`,
            descripcion: m.notas_tecnico || 'Mantenimiento preventivo realizado.',
            mantenimiento_detalles: m
        });
    });

    // 3. Cotizaciones
    historialEquipo.value.cotizaciones.forEach(c => {
        list.push({
            id: 'cot_' + c.id,
            fecha_raw: c.fecha,
            fecha_sort: parseDateString(c.fecha),
            fecha: c.fecha,
            tipo: 'Cotización Reparación',
            icon: 'file-invoice-dollar',
            color: 'text-purple-500 bg-purple-50 dark:bg-purple-950/40 border-purple-200 dark:border-purple-800/30',
            titulo: `Cotización #${c.numero}`,
            subtitulo: `Estado: ${c.estado?.toUpperCase()}`,
            descripcion: `Total cotizado: ${formatCurrency(c.total)}`,
        });
    });

    // Ordenar de más reciente a más antiguo
    return list.sort((a, b) => b.fecha_sort - a.fecha_sort);
});
</script>

<template>
    <Head :title="`Póliza ${poliza.folio}`" />

    <ClientLayout :empresa="empresa">
        <div class="px-2 sm:px-0">
            <!-- Header -->
            <div class="mb-8">
                <Link :href="route('portal.dashboard')" class="text-xs uppercase tracking-wide font-bold text-slate-400 dark:text-slate-500 hover:text-[var(--color-primary)] mb-4 inline-block transition-colors">
                    ← Volver al Panel
                </Link>

                <!-- Alerta de Límite de Consumo (Mejora 4.2) -->
                <!-- Alerta de Límite de Consumo (Mejora 4.2) -->
                <div v-if="mostrarAlertaLimite" 
                    :class="[
                        'mb-6 p-4 rounded-2xl border flex items-center gap-4 animate-pulse',
                        severityAlerta === 'critical' 
                            ? 'bg-rose-50 dark:bg-rose-900/20 dark:bg-rose-900/30 border-rose-100 dark:border-rose-900/50 text-rose-800 dark:text-rose-200 dark:text-rose-200 dark:text-rose-300' 
                            : 'bg-brand-50 dark:bg-brand-900/20 dark:bg-brand-900/30 border-brand-100 dark:border-brand-900/50 text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-amber-300'
                    ]">
                    <div :class="['w-10 h-10 rounded-full flex items-center justify-center', severityAlerta === 'critical' ? 'bg-rose-50 dark:bg-rose-900/20/50' : 'bg-brand-50 dark:bg-brand-900/20/50']">
                        <font-awesome-icon :icon="severityAlerta === 'critical' ? 'exclamation-triangle' : 'info-circle'" />
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-black uppercase tracking-wider">
                            {{ severityAlerta === 'critical' ? '¡Límite alcanzado!' : 'Aviso de consumo' }}
                        </p>
                        <p class="text-xs font-medium opacity-80">
                            {{ severityAlerta === 'critical' ? 'Has utilizado el 100% de tus recursos incluidos. No te preocupes, seguiremos atendiéndote, se aplicarán cargos por excedentes.' : 'Has utilizado más del 80% de tus recursos del mes. Te sugerimos moderar el uso o considerar un upgrade.' }}
                        </p>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white dark:bg-slate-800/50 p-8 rounded-[2rem] shadow-xl dark:shadow-2xl shadow-slate-200/50 dark:shadow-black/50 border border-slate-100 dark:border-white/10 dark:backdrop-blur-xl transition-all">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="font-mono text-sm font-black text-[var(--color-primary)] uppercase tracking-wide">{{ poliza.folio }}</span>
                            <span :class="['px-3 py-1 text-[10px] font-black rounded-full border uppercase tracking-wide dark:bg-opacity-20', getEstadoBadge(poliza.estado)]">
                                {{ poliza.estado?.replace('_', ' ') }}
                            </span>
                            <!-- Badge de Firma -->
                            <span v-if="poliza.firmado_at" class="px-3 py-1 text-[10px] font-black rounded-full border uppercase tracking-wide bg-emerald-50 dark:bg-emerald-900/20 dark:bg-slate-800/30 text-emerald-600 dark:text-slate-400 border-emerald-200 dark:border-emerald-800/30">
                                ✓ Firmado
                            </span>
                            <span v-else class="px-3 py-1 text-[10px] font-black rounded-full border uppercase tracking-wide bg-brand-50 dark:bg-brand-900/20 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400 border-brand-200 dark:border-brand-800/30 animate-pulse">
                                ⚠️ Sin Firmar
                            </span>
                        </div>
                        <h1 class="text-2xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ poliza.nombre }}</h1>
                        <p class="text-slate-500 dark:text-slate-400 font-medium text-sm mt-1">Vence: <strong class="text-slate-700 dark:text-slate-200">{{ formatDate(poliza.fecha_fin) }}</strong></p>
                    </div>
                    <div class="flex gap-2 flex-wrap">
                        <!-- Botón de Firma Digital (Si no está firmada) -->
                        <Link v-if="!poliza.firmado_at" :href="route('portal.polizas.firmar', poliza.id)" class="px-4 py-3 bg-gradient-to-r from-brand-500 to-brand-600 text-white rounded-xl font-black text-[10px] uppercase tracking-wide hover:from-purple-600 hover:to-indigo-700 transition-all shadow-xl shadow-purple-200 dark:shadow-none flex items-center gap-2 animate-pulse">
                            <font-awesome-icon icon="signature" /> 
                            <span>Firmar Contrato</span>
                        </Link>
                        <a :href="route('portal.polizas.contrato.pdf', poliza.id)" target="_blank" class="px-4 py-3 bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-200 rounded-xl font-black text-[10px] uppercase tracking-wide hover:bg-slate-50 dark:hover:bg-slate-700 transition-all border-2 border-slate-100 dark:border-slate-700/50 flex items-center gap-2">
                            <font-awesome-icon icon="file-pdf" /> 
                            <span>Contrato</span>
                        </a>
                        <a :href="route('portal.polizas.beneficios.pdf', poliza.id)" target="_blank" class="px-4 py-3 bg-emerald-50 dark:bg-emerald-900/20 dark:bg-slate-800/20 text-emerald-600 dark:text-slate-400 rounded-xl font-black text-[10px] uppercase tracking-wide hover:bg-emerald-100 dark:hover:bg-emerald-900/40 transition-all border-2 border-emerald-100 dark:border-emerald-800/30 flex items-center gap-2">
                            <font-awesome-icon icon="chart-pie" /> 
                            <span>Informe Ahorro</span>
                        </a>
                        <!-- Botón de Renovación Anticipada (Mejora 4.3) -->
                        <Link v-if="puedeRenovar" :href="route('portal.checkout', { plan: poliza.plan_poliza_id, poliza_id: poliza.id })" class="px-4 py-3 bg-brand-500 text-white rounded-xl font-black text-[10px] uppercase tracking-wide hover:bg-brand-600 transition-all shadow-xl shadow-brand-200 flex items-center gap-2">
                            <font-awesome-icon icon="sync" /> 
                            <span>Renovar Ahora</span>
                        </Link>
                        <a :href="route('portal.polizas.export-calendar', poliza.id)" class="px-4 py-3 bg-white dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 rounded-xl font-black text-[10px] uppercase tracking-wide hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all border-2 border-indigo-100 dark:border-indigo-900/30 flex items-center gap-2">
                            <font-awesome-icon icon="calendar-alt" /> 
                            <span>Calendario</span>
                        </a>
                        <Link :href="route('portal.tickets.create', { poliza_id: poliza.id })" class="px-6 py-3 bg-blue-600 shadow-xl shadow-sky-200 text-white rounded-xl font-black text-xs uppercase tracking-wide hover:bg-blue-700 transition-all flex items-center gap-2">
                            <font-awesome-icon icon="life-ring" /> 
                            <span>Solicitar Soporte</span>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Columna Principal -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Descripción -->
                    <div class="bg-white dark:bg-slate-800/50 rounded-[2rem] shadow-xl dark:shadow-2xl shadow-slate-200/50 dark:shadow-black/50 border border-slate-100 dark:border-white/10 p-8 dark:backdrop-blur-xl transition-all">
                        <h3 class="font-black text-slate-900 dark:text-white uppercase tracking-wider mb-6 flex items-center gap-2">
                            <div class="w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-900/20 dark:bg-sky-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center text-sm">
                                <font-awesome-icon icon="star" />
                            </div>
                            Beneficios Incluidos
                        </h3>
                        <div v-if="poliza.plan_poliza?.beneficios" class="grid sm:grid-cols-2 gap-4">
                            <div v-for="(beneficio, idx) in poliza.plan_poliza.beneficios" :key="idx" class="flex items-start gap-3 p-4 bg-slate-50/50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-white/5">
                                <font-awesome-icon icon="check-circle" class="text-blue-500 dark:text-blue-400 mt-0.5" />
                                <span class="text-sm font-bold text-slate-700 dark:text-slate-200 leading-snug">{{ beneficio }}</span>
                            </div>
                        </div>
                        <p v-else class="text-slate-500 dark:text-slate-400 font-medium leading-relaxed whitespace-pre-wrap">{{ poliza.descripcion || 'Servicios integrales de soporte y mantenimiento.' }}</p>
                    </div>

                    <!-- Consumo Actual -->
                    <div class="bg-white dark:bg-slate-800/50 rounded-[2rem] shadow-xl dark:shadow-2xl shadow-slate-200/50 dark:shadow-black/50 border border-slate-100 dark:border-white/10 p-8 dark:backdrop-blur-xl transition-all">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                                <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 dark:bg-slate-800/30 text-emerald-600 dark:text-slate-400 flex items-center justify-center text-sm">
                                    <font-awesome-icon icon="chart-pie" />
                                </div>
                                Consumo Mensual
                            </h3>
                            <Link :href="route('portal.polizas.historial', poliza.id)" class="text-[10px] font-black text-[var(--color-primary)] uppercase tracking-wide hover:underline">
                                Ver Historial →
                            </Link>
                        </div>

                        <div class="grid lg:grid-cols-2 gap-8">
                            <!-- Gráfica de Dona -->
                            <div v-if="consumoData && consumoData.datasets[0].data.length > 0" class="flex flex-col items-center justify-center p-4 bg-slate-50/50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-white/5">
                                <h4 class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-4">Distribución de Horas</h4>
                                <div class="w-48 h-48 relative">
                                    <Doughnut :data="consumoData" :options="doughnutOptions" />
                                </div>
                            </div>

                            <!-- Barra de Horas y Detalles -->
                            <div class="space-y-6">
                            <div v-if="poliza.horas_incluidas_mensual > 0">
                                <div class="flex justify-between items-end mb-2">
                                    <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Horas de Soporte</p>
                                    <p class="text-xs font-bold" :class="poliza.excede_horas ? 'text-rose-500 dark:text-rose-400' : 'text-slate-500 dark:text-slate-200'">
                                        {{ poliza.horas_consumidas_mes || 0 }} / {{ poliza.horas_incluidas_mensual }} hrs
                                    </p>
                                </div>
                                <div class="w-full bg-white dark:bg-slate-800 rounded-full h-2 overflow-hidden border border-slate-100 dark:border-white/5">
                                        <div 
                                        class="h-full rounded-full transition-all duration-700 ease-out" 
                                        :class="poliza.excede_horas ? 'bg-brand-500' : 'bg-[var(--color-primary)]'"
                                        :style="{ width: Math.min(poliza.porcentaje_horas || 0, 100) + '%' }"
                                        ></div>
                                </div>
                                <p class="text-[10px] text-slate-400 mt-2 font-medium">Se reinicia el día {{ poliza.dia_cobro }} de cada mes.</p>
                            </div>

                            <!-- Barra de Tickets -->
                            <div v-if="poliza.limite_mensual_tickets > 0">
                                <div class="flex justify-between items-end mb-2">
                                    <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Tickets Incluidos</p>
                                    <p class="text-xs font-bold text-slate-500 dark:text-slate-200">
                                        {{ poliza.tickets_mes_actual_count || 0 }} / {{ poliza.limite_mensual_tickets }}
                                    </p>
                                </div>
                                <div class="w-full bg-white dark:bg-slate-800 rounded-full h-2 overflow-hidden border border-slate-100 dark:border-white/5">
                                        <div 
                                        class="bg-brand-500 h-full rounded-full transition-all duration-700 ease-out" 
                                        :style="{ width: Math.min(poliza.porcentaje_tickets || 0, 100) + '%' }"
                                        ></div>
                                </div>
                            </div>

                            <!-- Barra de Visitas en Sitio -->
                            <div v-if="poliza.visitas_sitio_mensuales > 0">
                                <div class="flex justify-between items-end mb-2">
                                    <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Visitas en Sitio</p>
                                    <p class="text-xs font-bold" :class="visitasRestantes() <= 0 ? 'text-brand-500' : 'text-slate-500 dark:text-slate-200'">
                                        {{ poliza.visitas_sitio_consumidas_mes || 0 }} / {{ poliza.visitas_sitio_mensuales }}
                                    </p>
                                </div>
                                <div class="w-full bg-white dark:bg-slate-800 rounded-full h-2 overflow-hidden border border-slate-100 dark:border-white/5">
                                        <div 
                                        class="h-full rounded-full transition-all duration-700 ease-out"
                                        :class="visitasRestantes() <= 0 ? 'bg-brand-500' : 'bg-purple-500'"
                                        :style="{ width: Math.min(((poliza.visitas_sitio_consumidas_mes || 0) / poliza.visitas_sitio_mensuales) * 100, 100) + '%' }"
                                        ></div>
                                </div>
                                <p v-if="visitasRestantes() <= 0" class="text-[10px] text-brand-500 mt-2 font-bold">
                                    ⚠️ Visitas adicionales: {{ formatCurrency(poliza.costo_visita_sitio_extra || 650) }} c/u
                                </p>
                            </div>
                            </div>
                        </div> <!-- Cierra el grid de 2 columnas -->

                        <!-- Info de Reinicio -->
                        <div class="mt-6 p-4 bg-[var(--ui-surface)] dark:bg-slate-800/50 rounded-xl flex items-center justify-between">
                            <div>
                                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Próximo Reinicio de Consumos</p>
                                <p class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ proximoCobro() }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Próximo Cobro</p>
                                <p class="text-sm font-bold text-[var(--color-primary)]">{{ formatCurrency(poliza.monto_mensual) }}</p>
                            </div>
                        </div>

                        <!-- Ahorro del Cliente -->
                        <div v-if="ahorroMensual() > 0" class="mt-8 p-6 bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-2xl border border-emerald-100 dark:border-emerald-800/30">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] font-black text-emerald-600 dark:text-slate-400 uppercase tracking-wide mb-1">💰 Tu Ahorro Este Mes</p>
                                    <p class="text-2xl font-black text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-emerald-300">{{ formatCurrency(ahorroMensual()) }}</p>
                                    <p class="text-xs text-emerald-600/70 dark:text-slate-400/70 mt-1">vs. pagar servicios individuales a tarifas estándar</p>
                                </div>
                                <div class="text-5xl opacity-30 dark:opacity-20">🎉</div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfica de Consumo Histórico (Mejora 4.1) -->
                    <div class="bg-white dark:bg-slate-800/50 rounded-[2rem] shadow-xl dark:shadow-2xl shadow-slate-200/50 dark:shadow-black/50 border border-slate-100 dark:border-white/10 p-8 dark:backdrop-blur-xl transition-all">
                        <h3 class="font-black text-slate-900 dark:text-white uppercase tracking-wider mb-6 flex items-center gap-2">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-sky-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-sm">
                                <font-awesome-icon icon="chart-bar" />
                            </div>
                            Tendencia de Consumo (Últimos 6 meses)
                        </h3>
                        <div class="h-64">
                            <Bar v-if="historicoConsumo?.length" :data="chartData" :options="chartOptions" />
                            <div v-else class="h-full flex flex-col items-center justify-center text-slate-400 dark:text-slate-500">
                                <font-awesome-icon icon="chart-bar" size="2x" class="mb-2 opacity-20" />
                                <p class="text-xs font-medium">Aún no hay suficiente historial para mostrar la gráfica.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Detalle de Tickets Consumidos este mes (Mejora 4.4) -->
                    <div class="bg-white dark:bg-slate-800/50 rounded-[2rem] shadow-xl dark:shadow-2xl shadow-slate-200/50 dark:shadow-black/50 border border-slate-100 dark:border-white/10 p-8 dark:backdrop-blur-xl transition-all">
                        <h3 class="font-black text-slate-900 dark:text-white uppercase tracking-wider mb-6 flex items-center gap-2">
                            <div class="w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-900/20 dark:bg-sky-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center text-sm">
                                <font-awesome-icon icon="ticket-alt" />
                            </div>
                            Tickets Aplicados a Póliza (Este Mes)
                        </h3>
                        
                        <div v-if="ticketsMesActual?.length" class="space-y-6">
                            <div v-for="ticket in ticketsMesActual" :key="ticket.id" class="flex items-center justify-between p-4 border border-slate-50 dark:border-white/5 rounded-2xl bg-slate-50/30 dark:bg-slate-800/30 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-700/50 flex items-center justify-center text-blue-500 dark:text-blue-400 font-black text-xs shadow-sm border border-slate-100 dark:border-white/5">
                                        #{{ ticket.folio }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900 dark:text-white text-sm mb-0.5">{{ ticket.titulo }}</p>
                                        <p class="text-[10px] font-medium text-slate-400 dark:text-slate-500 uppercase tracking-wide">{{ formatDate(ticket.created_at) }} • {{ ticket.categoria?.nombre || 'Soporte' }}</p>
                                    </div>
                                </div>
                                <Link :href="route('portal.tickets.show', ticket.id)" class="px-4 py-2 bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-200 border border-slate-100 dark:border-white/10 rounded-xl text-[10px] font-black uppercase tracking-wide hover:bg-slate-50 dark:hover:bg-slate-700 transition-all">
                                    Ver Detalle
                                </Link>
                            </div>
                        </div>
                        <div v-else class="py-8 text-center">
                            <p class="text-sm font-medium text-slate-400 italic">No se han registrado consumos de tickets en el periodo actual.</p>
                        </div>
                    </div>

                    <!-- FASE 2: Mantenimientos Incluidos (Autoservicio) -->
                    <div v-if="poliza.mantenimientos && poliza.mantenimientos.length > 0" class="bg-white dark:bg-slate-800/50 rounded-[2rem] shadow-xl dark:shadow-2xl shadow-slate-200/50 dark:shadow-black/50 border border-slate-100 dark:border-white/10 p-8 dark:backdrop-blur-xl transition-all">
                         <h3 class="font-black text-slate-900 dark:text-white uppercase tracking-wider mb-6 flex items-center gap-2">
                            <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 flex items-center justify-center text-sm">
                                <font-awesome-icon icon="tools" />
                            </div>
                            Mantenimientos Incluidos
                        </h3>
                        
                        <div class="grid gap-6">
                            <div v-for="mant in poliza.mantenimientos" :key="mant.id" class="flex flex-col sm:flex-row sm:items-center justify-between p-4 border border-slate-100 dark:border-white/10 rounded-2xl bg-slate-50/50 dark:bg-slate-800/30 hover:bg-white dark:hover:bg-slate-700 hover:shadow-xl dark:hover:shadow-black/40 transition-all">
                                <div class="mb-4 sm:mb-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wide bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-200 rounded-full">{{ mant.frecuencia }}</span>
                                        <span v-if="mant.requiere_visita" class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wide bg-brand-50 dark:bg-brand-900/20/40 text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-brand-400 rounded-full">Requiere Visita</span>
                                    </div>
                                    <h4 class="font-bold text-slate-900 dark:text-white">{{ mant.nombre }}</h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ mant.descripcion || 'Mantenimiento preventivo programado.' }}</p>
                                </div>
                                <button @click="abrirSolicitud(mant)" class="px-4 py-2 bg-white dark:bg-slate-700 text-purple-600 dark:text-purple-400 border border-purple-200 dark:border-purple-600/30 rounded-xl text-xs font-black uppercase tracking-wide hover:bg-purple-600 hover:text-white dark:hover:bg-purple-600 dark:hover:text-white transition-all shadow-sm">
                                    Solicitar Ahora
                                </button>
                            </div>
                        </div>

                        <!-- Historial de Ejecuciones Recientes -->
                        <div v-if="poliza.mantenimientos_ejecuciones && poliza.mantenimientos_ejecuciones.length > 0" class="mt-8">
                            <h4 class="text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-4">Actividad Reciente</h4>
                             <div class="space-y-3">
                                <div v-for="ejc in poliza.mantenimientos_ejecuciones" :key="ejc.id" 
                                    @click="abrirDetalleEjecucion(ejc)"
                                    :class="['flex items-center gap-2 text-sm p-2 rounded-xl transition-all', ejc.estado === 'completado' ? 'cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800' : '']">
                                    <div class="w-2 h-2 rounded-full" :class="{
                                        'bg-emerald-500': ejc.resultado === 'exitoso' || ejc.estado === 'completado',
                                        'bg-amber-500': ejc.resultado === 'con_observaciones' || ejc.estado === 'pendiente' || ejc.estado === 'en_proceso',
                                        'bg-rose-500': ejc.resultado === 'fallido' || ejc.estado === 'vencido'
                                    }"></div>
                                    <span class="text-slate-900 dark:text-slate-200 font-medium flex-1">
                                        {{ ejc.mantenimiento ? ejc.mantenimiento.nombre : 'Mantenimiento' }}
                                    </span>
                                    <span class="text-slate-400 text-xs tabular-nums">
                                        {{ formatDate(ejc.fecha_ejecucion || ejc.fecha_programada) }}
                                    </span>
                                    <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-xl" :class="{
                                        'bg-emerald-100 text-emerald-800 dark:text-emerald-200 dark:text-emerald-250': ejc.estado === 'completado',
                                        'bg-amber-100 text-amber-800 dark:text-amber-200 dark:text-amber-250': ejc.estado === 'pendiente' || ejc.estado === 'en_proceso',
                                    }">{{ ejc.estado }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Equipos Vinculados -->
                    <div class="bg-white dark:bg-slate-800/50 rounded-[2rem] shadow-xl dark:shadow-2xl shadow-slate-200/50 dark:shadow-black/50 border border-slate-100 dark:border-white/10 p-8 dark:backdrop-blur-xl transition-all">
                         <h3 class="font-black text-slate-900 dark:text-white uppercase tracking-wider mb-6 flex items-center gap-2">
                            <div class="w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-900/20 dark:bg-sky-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center text-sm">
                                <font-awesome-icon icon="desktop" />
                            </div>
                            Equipos Protegidos ({{ (poliza.equipos?.length || 0) + (poliza.condiciones_especiales?.equipos_cliente?.length || 0) }})
                        </h3>
                        
                        <div class="grid sm:grid-cols-2 gap-4">
                            <!-- Equipos de Catálogo -->
                            <div v-for="equipo in poliza.equipos" :key="'cat-' + equipo.id" @click="abrirHistorialEquipo({ nombre: equipo.nombre, serie_evaporador: equipo.serie || '', serie_condensadora: '', tipo: 'catalogo' })" class="flex items-center gap-4 p-4 border border-slate-50 dark:border-white/5 rounded-2xl hover:border-brand-100 dark:hover:border-brand-500/30 hover:bg-slate-50/30 dark:hover:bg-blue-900/10 cursor-pointer transition-all">
                                <div class="w-10 h-10 bg-white dark:bg-slate-700/50 border border-slate-100 dark:border-white/5 rounded-xl flex items-center justify-center text-slate-400 dark:text-slate-200">
                                    <font-awesome-icon icon="desktop" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-1.5 mb-0.5">
                                        <p class="font-bold text-slate-900 dark:text-slate-100 text-sm truncate">{{ equipo.nombre }}</p>
                                        <span class="text-[8px] font-black text-blue-500 px-1 py-0.5 bg-blue-50 dark:bg-blue-950/30 rounded uppercase shrink-0">Catálogo</span>
                                    </div>
                                    <p class="font-mono text-[10px] text-slate-400 dark:text-slate-500 uppercase tracking-wide truncate">{{ equipo.serie || 'S/N: N/A' }}</p>
                                </div>
                            </div>

                            <!-- Equipos de Condiciones Especiales -->
                            <div v-for="(equipo, idx) in poliza.condiciones_especiales?.equipos_cliente" :key="'extra-' + idx" @click="abrirHistorialEquipo({ nombre: equipo.nombre, serie_evaporador: equipo.serie_evaporador || '', serie_condensadora: equipo.serie_condensadora || '', tipo: 'extra' })" class="flex items-center gap-4 p-4 border border-slate-50 dark:border-white/5 rounded-2xl hover:border-brand-100 dark:hover:border-brand-500/30 hover:bg-slate-50/30 dark:hover:bg-blue-900/10 cursor-pointer transition-all">
                                <div class="w-10 h-10 bg-white dark:bg-slate-700/50 border border-slate-100 dark:border-white/5 rounded-xl flex items-center justify-center text-slate-400 dark:text-slate-200">
                                    <font-awesome-icon icon="desktop" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-1.5 mb-0.5">
                                        <p class="font-bold text-slate-900 dark:text-slate-100 text-sm truncate">{{ equipo.nombre }}</p>
                                        <span class="text-[8px] font-black text-brand-500 px-1 py-0.5 bg-brand-50 dark:bg-brand-950/30 rounded uppercase shrink-0">Póliza</span>
                                    </div>
                                    <div class="flex gap-2 font-mono text-[9px] text-slate-400 dark:text-slate-500 tracking-wide truncate">
                                        <span v-if="equipo.serie_evaporador">Eva: {{ equipo.serie_evaporador }}</span>
                                        <span v-if="equipo.serie_condensadora">Cond: {{ equipo.serie_condensadora }}</span>
                                        <span v-if="!equipo.serie_evaporador && !equipo.serie_condensadora">S/N: N/A</span>
                                    </div>
                                </div>
                            </div>

                             <div v-if="(!poliza.equipos || poliza.equipos.length === 0) && (!poliza.condiciones_especiales?.equipos_cliente || poliza.condiciones_especiales.equipos_cliente.length === 0)" class="col-span-full py-8 text-center text-slate-400 dark:text-slate-500 text-sm font-medium italic">
                                No hay equipos vinculados específicamente.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                     <!-- Detalles Técnicos -->
                    <div class="bg-white dark:bg-slate-800/50 rounded-[2rem] shadow-xl dark:shadow-2xl shadow-slate-200/50 dark:shadow-black/50 border border-slate-100 dark:border-white/10 p-8 dark:backdrop-blur-xl transition-all">
                        <h3 class="font-black text-slate-900 dark:text-white uppercase tracking-wider mb-4 text-sm">Características</h3>
                        <ul class="space-y-6">
                            <li class="flex justify-between items-center text-sm">
                                <div class="flex items-center gap-1 group relative cursor-help">
                                    <span class="text-slate-500 dark:text-slate-400 font-medium border-b border-dashed border-slate-300 dark:border-slate-700">Garantía SLA</span>
                                    <font-awesome-icon icon="circle-info" class="text-slate-300 dark:text-slate-500 text-[10px]" />
                                    <!-- Tooltip simple -->
                                    <div class="absolute bottom-full left-0 mb-2 w-48 p-2 bg-slate-800 dark:bg-black text-white text-[10px] rounded-xl shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10">
                                        Tiempo máximo garantizado para iniciar la atención de sus reportes.
                                    </div>
                                </div>
                                <span class="font-black text-[var(--color-primary)] bg-sky-50 dark:bg-sky-900/20 dark:bg-sky-900/20 px-3 py-1 rounded-xl text-xs">{{ poliza.sla_horas_respuesta ? poliza.sla_horas_respuesta + ' horas' : 'Estándar' }}</span>
                            </li>
                             <li class="flex justify-between items-center text-sm">
                                <span class="text-slate-500 dark:text-slate-400 font-medium">Renovación</span>
                                <span class="font-bold text-slate-900 dark:text-slate-200">{{ poliza.renovacion_automatica ? 'Automática' : 'Manual' }}</span>
                            </li>
                             <li class="flex justify-between items-center text-sm">
                                <span class="text-slate-500 dark:text-slate-400 font-medium">Facturación</span>
                                <span class="font-bold text-slate-900 dark:text-slate-200">Mensual</span>
                            </li>
                              <li class="flex justify-between items-center text-sm pt-4 border-t border-slate-50 dark:border-white/5">
                                <span class="text-slate-500 dark:text-slate-400 font-medium">Inversión</span>
                                <div class="text-right">
                                    <span class="font-black text-slate-900 dark:text-white block">{{ formatCurrency(poliza.monto_mensual) }}</span>
                                    <span class="text-[10px] font-bold text-slate-500 dark:text-slate-500 block">+ IVA / mes</span>
                                </div>
                            </li>
                        </ul>
                    </div>

                     <!-- Aviso de privacidad o Legal simplificado -->
                     <div class="bg-white dark:bg-slate-800/50 rounded-[2rem] p-6 text-center border border-slate-100 dark:border-white/10 dark:backdrop-blur-xl transition-all">
                        <p class="text-[10px] text-slate-400 dark:text-slate-500 uppercase tracking-wide font-black mb-2">Soporte Técnico</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mb-4">¿Tiene problemas con sus equipos cubiertos?</p>
                         <Link :href="route('portal.tickets.create', { poliza_id: poliza.id })" class="inline-block px-6 py-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 text-slate-700 dark:text-slate-200 rounded-xl font-black text-xs uppercase tracking-wide hover:border-[var(--color-primary)] hover:text-[var(--color-primary)] dark:hover:text-white dark:hover:border-[var(--color-primary)] transition-all">
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
                <div class="space-y-6">
                    <p class="text-sm text-slate-500">
                        Indica cuándo te gustaría recibir este servicio. Un técnico confirmará la disponibilidad.
                    </p>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Fecha Preferida" />
                            <input type="date" v-model="formSolicitud.fecha_solicitada" class="w-full border-slate-300 rounded-xl shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" :min="new Date().toISOString().split('T')[0]">
                            <InputError :message="formSolicitud.errors.fecha_solicitada" />
                        </div>
                        <div>
                            <InputLabel value="Hora Preferida" />
                            <input type="time" v-model="formSolicitud.hora_solicitada" class="w-full border-slate-300 rounded-xl shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                            <InputError :message="formSolicitud.errors.hora_solicitada" />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Notas Adicionales (Opcional)" />
                        <textarea v-model="formSolicitud.notas" rows="3" class="w-full border-slate-300 rounded-xl shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" placeholder="Ej: Favor de revisar específicamente el equipo del mostrador..."></textarea>
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

        <!-- Modal Detalle Ejecución Mantenimiento (Comparación Antes/Después) -->
        <DialogModal :show="modalDetalleEjecucionAbierto" @close="cerrarDetalleEjecucion" maxWidth="3xl">
            <template #title>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded-xl flex items-center justify-center text-lg">
                        <font-awesome-icon icon="tools" />
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white">Detalle de Servicio Realizado</h3>
                        <p class="text-xs font-medium text-slate-400 dark:text-slate-500 uppercase tracking-wide">
                            {{ ejecucionSeleccionada?.mantenimiento?.nombre }}
                        </p>
                    </div>
                </div>
            </template>
            <template #content>
                <div class="space-y-6">
                    <!-- Fila de metadatos básicos -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-white/5 text-xs">
                        <div>
                            <span class="text-slate-400 dark:text-slate-500 block uppercase font-bold tracking-wide">Fecha de Ejecución</span>
                            <span class="font-bold text-slate-700 dark:text-slate-200">{{ formatDate(ejecucionSeleccionada?.fecha_ejecucion) }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 dark:text-slate-500 block uppercase font-bold tracking-wide">Técnico</span>
                            <span class="font-bold text-slate-700 dark:text-slate-200">{{ ejecucionSeleccionada?.tecnico?.name || 'Técnico de Campo' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 dark:text-slate-500 block uppercase font-bold tracking-wide">Número de Serie</span>
                            <span class="font-mono font-bold text-slate-900 dark:text-slate-200">{{ ejecucionSeleccionada?.numero_serie || 'S/N: N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 dark:text-slate-500 block uppercase font-bold tracking-wide">Nivel de Resultado</span>
                            <span class="font-bold uppercase inline-block px-2 py-0.5 rounded-lg mt-1 text-[10px]" :class="{
                                'bg-emerald-100 text-emerald-800': ejecucionSeleccionada?.resultado === 'exitoso',
                                'bg-amber-100 text-amber-800': ejecucionSeleccionada?.resultado === 'con_observaciones',
                                'bg-rose-100 text-rose-800': ejecucionSeleccionada?.resultado === 'fallido',
                            }">{{ ejecucionSeleccionada?.resultado }}</span>
                        </div>
                    </div>

                    <!-- Comparación Antes / Después -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Estado Inicial (Antes) -->
                        <div class="p-5 bg-slate-50/50 dark:bg-slate-800/30 rounded-2xl border border-slate-100 dark:border-white/5 space-y-4">
                            <h4 class="font-black text-xs text-blue-600 dark:text-blue-400 uppercase tracking-wider border-b border-slate-100 dark:border-white/5 pb-2">🔍 Estado Inicial (Antes)</h4>
                            <p class="text-xs text-slate-600 dark:text-slate-355 leading-relaxed whitespace-pre-wrap min-h-[60px]">
                                {{ ejecucionSeleccionada?.notas_iniciales || 'Sin observaciones iniciales registradas.' }}
                            </p>
                            
                            <div v-if="ejecucionSeleccionada?.fotos_antes && ejecucionSeleccionada.fotos_antes.length > 0" class="space-y-2">
                                <span class="text-[10px] font-bold text-slate-400 uppercase block">Fotos "Antes":</span>
                                <div class="grid grid-cols-2 gap-2">
                                    <div v-for="(img, idx) in ejecucionSeleccionada.fotos_antes" :key="idx" class="aspect-[4/3] rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700 bg-black flex items-center justify-center">
                                        <img :src="'/storage/' + img" class="w-full h-full object-cover hover:scale-105 transition-transform" alt="Antes" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Trabajo Realizado (Después) -->
                        <div class="p-5 bg-slate-50/50 dark:bg-slate-800/30 rounded-2xl border border-slate-100 dark:border-white/5 space-y-4">
                            <h4 class="font-black text-xs text-emerald-600 dark:text-emerald-400 uppercase tracking-wider border-b border-slate-100 dark:border-white/5 pb-2">🛠️ Trabajo Realizado (Después)</h4>
                            <p class="text-xs text-slate-600 dark:text-slate-355 leading-relaxed whitespace-pre-wrap min-h-[60px]">
                                {{ ejecucionSeleccionada?.notas_tecnico || 'Mantenimiento preventivo completado según protocolo.' }}
                            </p>

                            <div v-if="ejecucionSeleccionada?.fotos_despues && ejecucionSeleccionada.fotos_despues.length > 0" class="space-y-2">
                                <span class="text-[10px] font-bold text-slate-400 uppercase block">Fotos "Después":</span>
                                <div class="grid grid-cols-2 gap-2">
                                    <div v-for="(img, idx) in ejecucionSeleccionada.fotos_despues" :key="idx" class="aspect-[4/3] rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700 bg-black flex items-center justify-center">
                                        <img :src="'/storage/' + img" class="w-full h-full object-cover hover:scale-105 transition-transform" alt="Después" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="cerrarDetalleEjecucion">Cerrar</SecondaryButton>
            </template>
        </DialogModal>

        <!-- Modal Hoja de Vida del Equipo (Timeline de Intervenciones) -->
        <DialogModal :show="modalEquipoAbierto" @close="cerrarHistorialEquipo" maxWidth="3xl">
            <template #title>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-brand-500/10 text-brand-600 dark:text-brand-400 rounded-xl flex items-center justify-center text-lg">
                        <font-awesome-icon icon="desktop" />
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white">Hoja de Vida del Equipo</h3>
                        <p class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wide">
                            {{ equipoSeleccionado?.nombre }} ({{ equipoSeleccionado?.tipo === 'catalogo' ? 'Catálogo' : 'Condición Especial' }})
                        </p>
                    </div>
                </div>
            </template>
            <template #content>
                <div v-if="cargandoHistorial" class="py-12 text-center text-slate-400 dark:text-slate-500 text-sm font-medium">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-[var(--color-primary)] border-r-transparent align-[-0.125em] motion-reduce:animate-[spin_1.5s_linear_infinite] mb-4"></div>
                    <p>Cargando historial del equipo...</p>
                </div>
                <div v-else class="space-y-6 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
                    <div v-if="combinedTimeline.length === 0" class="py-12 text-center text-slate-400 dark:text-slate-500 text-sm font-medium italic">
                         No hay registros de soporte, mantenimientos o cotizaciones para este equipo aún.
                    </div>
                    <div v-else class="relative border-l border-slate-200 dark:border-slate-800 ml-4 space-y-8 pb-4">
                        <div v-for="item in combinedTimeline" :key="item.id" class="relative pl-8">
                            <!-- Icono de Timeline -->
                            <span class="absolute -left-4 top-1.5 flex h-8 w-8 items-center justify-center rounded-full border shadow-sm" :class="item.color">
                                <font-awesome-icon :icon="item.icon" class="text-xs" />
                            </span>
                            
                            <div class="space-y-1.5">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                                    <span class="text-xs font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ item.tipo }}</span>
                                    <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full">{{ item.fecha }}</span>
                                </div>
                                <h4 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ item.titulo }}</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">{{ item.subtitulo }}</p>
                                <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed bg-slate-50/50 dark:bg-slate-800/20 p-3 rounded-xl border border-slate-100/50 dark:border-white/5">{{ item.descripcion }}</p>
                                
                                <!-- Fotos de Soporte (si aplica) -->
                                <div v-if="item.fotos && item.fotos.length" class="mt-3">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase block mb-1.5">Evidencias:</span>
                                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                                        <div v-for="(img, idx) in item.fotos" :key="idx" class="aspect-[4/3] rounded-lg overflow-hidden border border-slate-200 dark:border-slate-800 bg-black flex items-center justify-center">
                                            <a :href="'/storage/' + img" target="_blank" class="w-full h-full">
                                                <img :src="'/storage/' + img" class="w-full h-full object-cover hover:scale-105 transition-transform" alt="Soporte" />
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Detalles Mantenimiento Antes / Después (si aplica) -->
                                <div v-if="item.mantenimiento_detalles" class="mt-3 space-y-4">
                                     <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                         <!-- Antes -->
                                         <div class="p-4 bg-slate-50/50 dark:bg-slate-800/30 rounded-xl border border-slate-100 dark:border-white/5 space-y-3">
                                             <h5 class="font-black text-[10px] text-blue-600 dark:text-blue-400 uppercase tracking-wider border-b border-slate-100 dark:border-white/5 pb-1">🔍 Antes (Estado Inicial)</h5>
                                             <p class="text-xs text-slate-500 dark:text-slate-400 leading-normal">
                                                 {{ item.mantenimiento_detalles.notes_iniciales || item.mantenimiento_detalles.notas_iniciales || 'Sin notas iniciales.' }}
                                             </p>
                                             <div v-if="item.mantenimiento_detalles.fotos_antes && item.mantenimiento_detalles.fotos_antes.length > 0" class="grid grid-cols-2 gap-1.5 mt-2">
                                                 <div v-for="(img, idx) in item.mantenimiento_detalles.fotos_antes" :key="idx" class="aspect-[4/3] rounded-lg overflow-hidden border border-slate-200 dark:border-slate-800 bg-black flex items-center justify-center">
                                                     <a :href="'/storage/' + img" target="_blank" class="w-full h-full">
                                                         <img :src="'/storage/' + img" class="w-full h-full object-cover hover:scale-105 transition-transform" alt="Antes" />
                                                     </a>
                                                 </div>
                                             </div>
                                         </div>
                                         
                                         <!-- Después -->
                                         <div class="p-4 bg-slate-50/50 dark:bg-slate-800/30 rounded-xl border border-slate-100 dark:border-white/5 space-y-3">
                                             <h5 class="font-black text-[10px] text-emerald-600 dark:text-emerald-400 uppercase tracking-wider border-b border-slate-100 dark:border-white/5 pb-1">🛠️ Después (Trabajo Realizado)</h5>
                                             <p class="text-xs text-slate-500 dark:text-slate-400 leading-normal">
                                                 {{ item.mantenimiento_detalles.notas_tecnico || 'Mantenimiento completado.' }}
                                             </p>
                                             <div v-if="item.mantenimiento_detalles.fotos_despues && item.mantenimiento_detalles.fotos_despues.length > 0" class="grid grid-cols-2 gap-1.5 mt-2">
                                                 <div v-for="(img, idx) in item.mantenimiento_detalles.fotos_despues" :key="idx" class="aspect-[4/3] rounded-lg overflow-hidden border border-slate-200 dark:border-slate-800 bg-black flex items-center justify-center">
                                                     <a :href="'/storage/' + img" target="_blank" class="w-full h-full">
                                                         <img :src="'/storage/' + img" class="w-full h-full object-cover hover:scale-105 transition-transform" alt="Después" />
                                                     </a>
                                                 </div>
                                             </div>
                                         </div>
                                     </div>

                                     <!-- Mediciones Técnicas y Rutina Preventiva (Fase 3) -->
                                     <div v-if="item.mantenimiento_detalles.presion_gas || item.mantenimiento_detalles.amperaje || item.mantenimiento_detalles.voltaje || (item.mantenimiento_detalles.checklist_rutina && Object.keys(item.mantenimiento_detalles.checklist_rutina).length > 0)" 
                                          class="p-5 bg-slate-50/30 dark:bg-slate-850/40 rounded-2xl border border-slate-100 dark:border-white/5 space-y-4 backdrop-blur-md">
                                         
                                         <!-- Grid de Mediciones Técnicas -->
                                         <div v-if="item.mantenimiento_detalles.presion_gas || item.mantenimiento_detalles.amperaje || item.mantenimiento_detalles.voltaje" class="space-y-2">
                                             <h6 class="text-[10px] font-black text-indigo-500 dark:text-indigo-400 uppercase tracking-wider">📊 Mediciones de Parámetros Técnicos</h6>
                                             <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                                                 <div v-if="item.mantenimiento_detalles.presion_gas" class="p-3 bg-white dark:bg-slate-800/80 rounded-xl border border-slate-100 dark:border-white/5 text-center">
                                                     <span class="text-[9px] text-slate-400 uppercase font-black block tracking-wide">Presión Gas</span>
                                                     <span class="text-xs font-black text-slate-700 dark:text-slate-200">{{ item.mantenimiento_detalles.presion_gas }}</span>
                                                 </div>
                                                 <div v-if="item.mantenimiento_detalles.amperaje" class="p-3 bg-white dark:bg-slate-800/80 rounded-xl border border-slate-100 dark:border-white/5 text-center">
                                                     <span class="text-[9px] text-slate-400 uppercase font-black block tracking-wide">Amperaje</span>
                                                     <span class="text-xs font-black text-slate-700 dark:text-slate-200">{{ item.mantenimiento_detalles.amperaje }}</span>
                                                 </div>
                                                 <div v-if="item.mantenimiento_detalles.voltaje" class="p-3 bg-white dark:bg-slate-800/80 rounded-xl border border-slate-100 dark:border-white/5 text-center">
                                                     <span class="text-[9px] text-slate-400 uppercase font-black block tracking-wide">Voltaje</span>
                                                     <span class="text-xs font-black text-slate-700 dark:text-slate-200">{{ item.mantenimiento_detalles.voltaje }}</span>
                                                 </div>
                                                 <div v-if="item.mantenimiento_detalles.temperatura_inyeccion" class="p-3 bg-white dark:bg-slate-800/80 rounded-xl border border-slate-100 dark:border-white/5 text-center">
                                                     <span class="text-[9px] text-slate-400 uppercase font-black block tracking-wide">T. Inyección</span>
                                                     <span class="text-xs font-black text-slate-700 dark:text-slate-200">{{ item.mantenimiento_detalles.temperatura_inyeccion }}</span>
                                                 </div>
                                                 <div v-if="item.mantenimiento_detalles.temperatura_retorno" class="p-3 bg-white dark:bg-slate-800/80 rounded-xl border border-slate-100 dark:border-white/5 text-center">
                                                     <span class="text-[9px] text-slate-400 uppercase font-black block tracking-wide">T. Retorno</span>
                                                     <span class="text-xs font-black text-slate-700 dark:text-slate-200">{{ item.mantenimiento_detalles.temperatura_retorno }}</span>
                                                 </div>
                                             </div>
                                         </div>

                                         <!-- Checklist de Rutina Preventiva -->
                                         <div v-if="item.mantenimiento_detalles.checklist_rutina && Object.keys(item.mantenimiento_detalles.checklist_rutina).length > 0" class="space-y-2">
                                             <h6 class="text-[10px] font-black text-teal-600 dark:text-teal-400 uppercase tracking-wider">📋 Rutina Preventiva Realizada</h6>
                                             <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                                 <div class="flex items-center gap-2 p-2.5 bg-white dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-white/5">
                                                     <font-awesome-icon icon="check-circle" :class="item.mantenimiento_detalles.checklist_rutina.lavado_evaporador ? 'text-emerald-500' : 'text-slate-300 dark:text-slate-600'" />
                                                     <span :class="item.mantenimiento_detalles.checklist_rutina.lavado_evaporador ? 'text-slate-700 dark:text-slate-300 font-bold' : 'text-slate-400 line-through'">Lavado evaporador c/hidro profunda.</span>
                                                 </div>
                                                 <div class="flex items-center gap-2 p-2.5 bg-white dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-white/5">
                                                     <font-awesome-icon icon="check-circle" :class="item.mantenimiento_detalles.checklist_rutina.lavado_condensadora ? 'text-emerald-500' : 'text-slate-300 dark:text-slate-600'" />
                                                     <span :class="item.mantenimiento_detalles.checklist_rutina.lavado_condensadora ? 'text-slate-700 dark:text-slate-300 font-bold' : 'text-slate-400 line-through'">Lavado a fondo de condensadora exterior.</span>
                                                 </div>
                                                 <div class="flex items-center gap-2 p-2.5 bg-white dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-white/5">
                                                     <font-awesome-icon icon="check-circle" :class="item.mantenimiento_detalles.checklist_rutina.limpieza_desague ? 'text-emerald-500' : 'text-slate-300 dark:text-slate-600'" />
                                                     <span :class="item.mantenimiento_detalles.checklist_rutina.limpieza_desague ? 'text-slate-700 dark:text-slate-300 font-bold' : 'text-slate-400 line-through'">Limpieza de charola y desagüe.</span>
                                                 </div>
                                                 <div class="flex items-center gap-2 p-2.5 bg-white dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-white/5">
                                                     <font-awesome-icon icon="check-circle" :class="item.mantenimiento_detalles.checklist_rutina.desinfeccion_serpentin ? 'text-emerald-500' : 'text-slate-300 dark:text-slate-600'" />
                                                     <span :class="item.mantenimiento_detalles.checklist_rutina.desinfeccion_serpentin ? 'text-slate-700 dark:text-slate-300 font-bold' : 'text-slate-400 line-through'">Desinfección de serpentín (químicos).</span>
                                                 </div>
                                                 <div class="flex items-center gap-2 p-2.5 bg-white dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-white/5">
                                                     <font-awesome-icon icon="check-circle" :class="item.mantenimiento_detalles.checklist_rutina.limpieza_turbina ? 'text-emerald-500' : 'text-slate-300 dark:text-slate-600'" />
                                                     <span :class="item.mantenimiento_detalles.checklist_rutina.limpieza_turbina ? 'text-slate-700 dark:text-slate-300 font-bold' : 'text-slate-400 line-through'">Limpieza de turbina, filtros y plásticos.</span>
                                                 </div>
                                                 <div class="flex items-center gap-2 p-2.5 bg-white dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-white/5">
                                                     <font-awesome-icon icon="check-circle" :class="item.mantenimiento_detalles.checklist_rutina.revision_presiones ? 'text-emerald-500' : 'text-slate-300 dark:text-slate-600'" />
                                                     <span :class="item.mantenimiento_detalles.checklist_rutina.revision_presiones ? 'text-slate-700 dark:text-slate-300 font-bold' : 'text-slate-400 line-through'">Revisión de presiones de refrigerante.</span>
                                                 </div>
                                                 <div class="flex items-center gap-2 p-2.5 bg-white dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-white/5">
                                                     <font-awesome-icon icon="check-circle" :class="item.mantenimiento_detalles.checklist_rutina.medicion_amperaje ? 'text-emerald-500' : 'text-slate-300 dark:text-slate-600'" />
                                                     <span :class="item.mantenimiento_detalles.checklist_rutina.medicion_amperaje ? 'text-slate-700 dark:text-slate-300 font-bold' : 'text-slate-400 line-through'">Medición de amperaje y voltaje.</span>
                                                 </div>
                                                 <div class="flex items-center gap-2 p-2.5 bg-white dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-white/5">
                                                     <font-awesome-icon icon="check-circle" :class="item.mantenimiento_detalles.checklist_rutina.reporte_detallado ? 'text-emerald-500' : 'text-slate-300 dark:text-slate-600'" />
                                                     <span :class="item.mantenimiento_detalles.checklist_rutina.reporte_detallado ? 'text-slate-700 dark:text-slate-300 font-bold' : 'text-slate-400 line-through'">Reporte técnico detallado post-servicio.</span>
                                                 </div>
                                             </div>
                                          </div>
                                      </div>
                                  </div>
                                 </div>
                            </div>
                        </div>
                    </div>
            </template>
            <template #footer>
                <SecondaryButton @click="cerrarHistorialEquipo">Cerrar</SecondaryButton>
            </template>
        </DialogModal>

    </ClientLayout>
</template>
