<script setup>
import { ref, computed } from 'vue';
import { useFormatters } from '@/Composables/useFormatters';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { Notyf } from 'notyf';
import Swal from '@/Utils/Swal';
import AppLayout from '@/Layouts/AppLayout.vue';

const notyf = new Notyf({ position: { x: 'right', y: 'top' } });

const props = defineProps({
    poliza: Object,
    stats: Object,
    isModal: {
        type: Boolean,
        default: false
    }
});

const { formatCurrency } = useFormatters();

// Modal de historial por equipo
const showEquipoModal = ref(false);
const equipoSeleccionado = ref(null);
const historialEquipo = ref([]);
const cargandoHistorial = ref(false);

const page = usePage();
const userCanCreateCotizacion = computed(() => page.props?.user?.can?.('create', 'cotizaciones') ?? true);

async function abrirHistorialEquipo(equipo) {
    equipoSeleccionado.value = equipo;
    showEquipoModal.value = true;
    cargandoHistorial.value = true;
    historialEquipo.value = { citas: [], cotizaciones: [] };
    try {
        const res = await axios.get(route('citas.historial-equipo', props.poliza.id), { params: { equipo: equipo.nombre } });
        historialEquipo.value = res.data;
        cargandoHistorial.value = false;
    } catch (e) {
        cargandoHistorial.value = false;
        notyf.error('Error al cargar historial');
    }
}

function cerrarHistorialEquipo() {
    showEquipoModal.value = false;
    equipoSeleccionado.value = null;
    historialEquipo.value = { citas: [], cotizaciones: [] };
}

function cotizarEquipo() {
    if (!equipoSeleccionado.value) return;
    router.post(route('cotizaciones.desde-equipo'), {
        poliza_id: props.poliza.id,
        equipo_nombre: equipoSeleccionado.value.nombre,
    });
}

function agendarServicioEquipo() {
    if (!equipoSeleccionado.value) return;
    router.visit(route('citas.create', {
        cliente_id: props.poliza.cliente_id,
        poliza_id: props.poliza.id,
        equipo_nombre: equipoSeleccionado.value.nombre,
    }));
}

function getFotoUrl(foto) {
    if (!foto) return '';
    if (foto.startsWith('http')) return foto;
    return '/storage/' + foto;
}

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-MX', { 
        day: '2-digit', month: 'short', year: 'numeric' 
    });
};

const getEstadoBadge = (estado) => {
    const colores = {
        activa: 'bg-emerald-100 text-emerald-800 dark:text-emerald-200 border-emerald-200 dark:border-emerald-800/30',
        inactiva: 'bg-brand-100 text-brand-800 dark:text-brand-200 border-brand-200 dark:border-brand-800/30',
        vencida: 'bg-rose-100 text-rose-800 dark:text-rose-200 border-rose-200 dark:border-rose-800/30',
        cancelada: 'bg-slate-100 text-slate-800 border-slate-200',
    };
    return colores[estado] || 'bg-slate-100 text-slate-800';
};

const getEstadoCobroBadge = (estado) => {
    const colores = {
        pagado: 'bg-emerald-100 text-emerald-800 dark:text-emerald-200 border-emerald-200 dark:border-emerald-800/30',
        pendiente: 'bg-brand-100 text-brand-800 dark:text-brand-200 border-brand-200 dark:border-brand-800/30',
        parcial: 'bg-sky-100 text-sky-800 dark:text-sky-200 border-sky-200 dark:border-sky-800/30',
        vencido: 'bg-rose-100 text-rose-800 dark:text-rose-200 border-rose-200 dark:border-rose-800/30',
        cancelada: 'bg-slate-100 text-slate-800 border-slate-200',
    };
    return colores[estado] || 'bg-slate-100 text-slate-800';
};

// Acciones Rápidas
const generarCobro = async () => {
    const result = await Swal.fire({
        title: 'Generar cobro',
        text: '¿Generar un nuevo cobro para esta póliza?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, generar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#3b82f6',
    });

    if (result.isConfirmed) {
        router.post(route('polizas-servicio.generar-cobro', props.poliza.id));
    }
};

const enviarRecordatorio = async () => {
    const result = await Swal.fire({
        title: 'Enviar recordatorio',
        text: '¿Enviar recordatorio de renovación al cliente?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, enviar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#3b82f6',
    });

    if (result.isConfirmed) {
        router.post(route('polizas-servicio.enviar-recordatorio', props.poliza.id));
    }
};

// Indicador de salud de la póliza
const getSaludPoliza = () => {
    const diasVencer = props.poliza.dias_para_vencer;
    const excedeHoras = props.poliza.excede_horas;
    const porcentajeHoras = props.poliza.porcentaje_horas || 0;
    
    if (diasVencer !== null && diasVencer <= 0) return { color: 'bg-brand-500', label: 'Vencida', icon: '🔴' };
    if (diasVencer !== null && diasVencer <= 7) return { color: 'bg-orange-500', label: 'Urgente', icon: '🟠' };
    if (excedeHoras) return { color: 'bg-purple-500', label: 'Excedida', icon: '🟣' };
    if (porcentajeHoras >= 80) return { color: 'bg-brand-500', label: 'Atención', icon: '🟡' };
    return { color: 'bg-brand-500', label: 'Saludable', icon: '🟢' };
};
</script>

<template>
    <component :is="isModal ? 'div' : AppLayout" :title="`Póliza ${poliza.folio}`">
        <Head v-if="!isModal" :title="`Póliza ${poliza.folio}`" />

        <div :class="isModal ? 'py-2 text-slate-800 dark:text-slate-200' : 'py-6 min-h-screen bg-[var(--ui-surface)] text-slate-800 dark:text-slate-200 transition-colors'">
            <div :class="isModal ? 'w-full' : 'w-full px-4 sm:px-6 lg:px-8'">
                <!-- Header -->
                <div v-if="!isModal" class="mb-6">
                    <Link :href="route('polizas-servicio.index')" class="text-blue-600 dark:text-blue-400 hover:text-sky-800 dark:text-sky-200 dark:hover:text-blue-300 text-sm mb-2 inline-block">
                        ← Volver al listado
                    </Link>
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xl font-bold text-blue-600 dark:text-blue-400">{{ poliza.folio }}</span>
                                <span :class="['px-3 py-1 text-sm font-bold rounded-full border', getEstadoBadge(poliza.estado)]">
                                    {{ poliza.estado?.toUpperCase() || 'PÓLIZA' }}
                                </span>
                            </div>
                            <h1 class="text-3xl font-bold text-slate-900 dark:text-white mt-1">{{ poliza.nombre }}</h1>
                            <p class="text-slate-500 dark:text-slate-400">{{ poliza.cliente?.nombre_razon_social }}</p>
                        </div>
                        <div class="flex gap-2 flex-wrap">
                            <!-- Indicador de Salud -->
                            <div :class="['px-3 py-2 rounded-xl font-bold text-white text-sm flex items-center gap-2', getSaludPoliza().color]">
                                {{ getSaludPoliza().icon }} {{ getSaludPoliza().label }}
                            </div>
                            <!-- Acciones Rápidas -->
                            <button @click="generarCobro" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-semibold shadow-xl transition">
                                💰 Cobrar Ahora
                            </button>
                            <button v-if="poliza.dias_para_vencer !== null && poliza.dias_para_vencer <= 30" @click="enviarRecordatorio" class="px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white rounded-xl font-semibold shadow-xl transition">
                                📧 Recordar Renovación
                            </button>
                            <a :href="route('polizas-servicio.pdf-beneficios', poliza.id)" target="_blank" class="px-4 py-2 bg-sky-100 dark:bg-brand-500/10 border border-blue-300 dark:border-blue-500/30 rounded-xl hover:bg-blue-200 dark:hover:bg-slate-500/20 font-semibold text-sky-800 dark:text-sky-200 dark:text-blue-400">
                                📄 Beneficios
                            </a>
                            <a :href="route('polizas-servicio.pdf-contrato', poliza.id)" target="_blank" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 font-semibold text-slate-700 dark:text-slate-200">
                                📝 Contrato
                            </a>
                            <Link v-if="poliza.horas_incluidas_mensual" :href="route('polizas-servicio.historial', poliza.id)" class="px-4 py-2 bg-purple-100 dark:bg-purple-500/10 border border-purple-300 dark:border-purple-500/30 rounded-xl hover:bg-purple-200 dark:hover:bg-purple-500/20 font-semibold text-purple-700 dark:text-purple-400">
                                📊 Historial
                            </Link>
                            <Link :href="route('polizas-servicio.edit', poliza.id)" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-2xl shadow-sm hover:bg-slate-50 dark:hover:bg-slate-700 font-semibold text-slate-700 dark:text-slate-200">
                                ⚙️ Editar
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Header Modal -->
                <div v-if="isModal" class="p-6 border-b border-slate-200 dark:border-slate-800 bg-white/50 dark:bg-black/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-lg font-bold text-blue-600 dark:text-blue-400">{{ poliza.folio }}</span>
                            <span :class="['px-2 py-0.5 text-xs font-bold rounded-full border', getEstadoBadge(poliza.estado)]">
                                {{ poliza.estado?.toUpperCase() || 'PÓLIZA' }}
                            </span>
                        </div>
                        <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ poliza.nombre }}</h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">{{ poliza.cliente?.nombre_razon_social }}</p>
                    </div>
                    <div class="flex gap-2">
                        <a :href="route('polizas-servicio.pdf-beneficios', poliza.id)" target="_blank" class="p-2 bg-emerald-50 dark:bg-emerald-900/20 dark:bg-brand-500/10 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-slate-400 rounded-xl hover:bg-emerald-100 dark:hover:bg-slate-500/20 transition-colors border border-emerald-200 dark:border-emerald-800/30 dark:border-emerald-500/30" title="Ver PDF de Beneficios">
                            📄 Beneficios
                        </a>
                        <a :href="route('polizas-servicio.pdf-contrato', poliza.id)" target="_blank" class="p-2 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors border border-slate-200 dark:border-slate-700" title="Ver PDF Contrato">
                            📝 Contrato
                        </a>
                        <Link :href="route('polizas-servicio.edit', poliza.id)" class="p-2 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors border border-slate-200 dark:border-slate-700" title="Editar Póliza">
                            ⚙️ Editar
                        </Link>
                    </div>
                </div>

                <div :class="['grid grid-cols-1 lg:grid-cols-3 gap-6', isModal ? 'p-6' : '']">
                    <!-- Columna Principal -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Detalles y Alcance -->
                        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-800/50 rounded-2xl shadow-sm p-6">
                            <h3 class="font-bold text-slate-900 dark:text-white mb-4 border-b border-slate-200 dark:border-slate-800 pb-2 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Descripción y Alcance
                            </h3>
                            <p class="text-slate-700 dark:text-slate-200 whitespace-pre-wrap">{{ poliza.descripcion || 'Sin descripción detallada.' }}</p>
                            <div v-if="poliza.notas" class="mt-4 p-4 bg-sky-50 dark:bg-sky-900/20 dark:bg-blue-950/20 rounded-xl text-sm text-sky-800 dark:text-sky-200 dark:text-blue-400">
                                <strong>Notas Administrativas:</strong><br> {{ poliza.notas }}
                            </div>
                        </div>

                        <!-- Servicios Incluidos -->
                        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-800/50 rounded-2xl shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800">
                                <h3 class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Servicios Cubiertos del Catálogo
                                </h3>
                            </div>
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                <thead class="bg-slate-50 dark:bg-slate-800/50">
                                    <tr>
                                        <th class="px-6 py-3 text-left">Servicio</th>
                                        <th class="px-6 py-3 text-center">Cant. Mensual</th>
                                        <th class="px-6 py-3 text-right">Precio Acordado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                                    <tr v-for="servicio in poliza.servicios" :key="servicio.id">
                                        <td class="px-6 py-4 text-sm font-medium text-slate-900 dark:text-slate-200">{{ servicio.nombre }}</td>
                                        <td class="px-6 py-4 text-center text-sm text-slate-500 dark:text-slate-400">{{ servicio.pivot.cantidad }}</td>
                                        <td class="px-6 py-4 text-right text-sm font-mono text-slate-900 dark:text-slate-200">
                                            {{ servicio.pivot.precio_especial ? formatCurrency(servicio.pivot.precio_especial) : '-' }}
                                        </td>
                                    </tr>
                                    <tr v-if="poliza.servicios.length === 0">
                                        <td colspan="3" class="px-6 py-8 text-center text-slate-400 dark:text-slate-500 text-sm">
                                            No se han especificado servicios individuales del catálogo.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Servicios Acordados en la Póliza -->
                        <div v-if="poliza.condiciones_especiales?.servicios_incluidos?.length || poliza.condiciones_especiales?.rutina_preventiva?.items?.length" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-800/50 rounded-2xl shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-gradient-to-r from-indigo-50 to-blue-50 dark:from-indigo-900/20 dark:to-blue-900/20">
                                <h3 class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <font-awesome-icon icon="handshake" class="text-indigo-500 text-lg" />
                                    Servicios Acordados en la Póliza
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Cobertura y beneficios incluidos en el contrato.</p>
                            </div>

                            <!-- Beneficios -->
                            <div v-if="poliza.condiciones_especiales?.servicios_incluidos?.length" class="p-6 border-b border-slate-100 dark:border-slate-700">
                                <h4 class="text-xs font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-3 flex items-center gap-1">
                                    <font-awesome-icon icon="star" /> Beneficios y Ventajas
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div v-for="(s, i) in poliza.condiciones_especiales.servicios_incluidos" :key="i"
                                        class="p-3 bg-[var(--ui-surface)] dark:bg-slate-700/40 rounded-xl border border-slate-200 dark:border-slate-700">
                                        <div class="flex items-start gap-3">
                                            <div class="w-9 h-9 shrink-0 rounded-lg bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-300 flex items-center justify-center">
                                                <font-awesome-icon :icon="s.icono || 'check'" class="text-sm" />
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="font-bold text-sm text-slate-900 dark:text-white">{{ s.titulo }}</div>
                                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">{{ s.descripcion }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Rutina Preventiva -->
                            <div v-if="poliza.condiciones_especiales?.rutina_preventiva?.items?.length" class="p-6 border-b border-slate-100 dark:border-slate-700">
                                <h4 class="text-xs font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mb-3 flex items-center gap-1">
                                    <font-awesome-icon icon="broom" /> {{ poliza.condiciones_especiales.rutina_preventiva.titulo }}
                                </h4>
                                <ul class="space-y-2">
                                    <li v-for="(item, i) in poliza.condiciones_especiales.rutina_preventiva.items" :key="i" class="flex items-start gap-2 text-sm text-slate-700 dark:text-slate-200">
                                        <font-awesome-icon icon="check-circle" class="text-emerald-500 mt-0.5 shrink-0 text-xs" />
                                        <span>{{ item }}</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Calendario de Visitas -->
                            <div v-if="poliza.condiciones_especiales?.calendario_visitas" class="p-6 border-b border-slate-100 dark:border-slate-700">
                                <h4 class="text-xs font-black text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-3 flex items-center gap-1">
                                    <font-awesome-icon icon="calendar-alt" /> {{ poliza.condiciones_especiales.calendario_visitas.titulo }}
                                </h4>
                                <p class="text-sm text-slate-600 dark:text-slate-300 mb-3">{{ poliza.condiciones_especiales.calendario_visitas.descripcion }}</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div v-for="(ronda, i) in poliza.condiciones_especiales.calendario_visitas.rondas" :key="i"
                                        :class="[
                                            'p-3 rounded-xl border-l-4',
                                            ronda.estado === 'urgente' ? 'bg-orange-50 dark:bg-orange-900/20 border-orange-500' : 'bg-sky-50 dark:bg-sky-900/20 border-blue-500'
                                        ]">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-sm font-black uppercase tracking-wider" :class="ronda.estado === 'urgente' ? 'text-orange-700 dark:text-orange-300' : 'text-blue-700 dark:text-blue-300'">
                                                {{ ronda.periodo }}
                                            </span>
                                            <span :class="['text-[9px] font-black px-2 py-0.5 rounded-full uppercase', ronda.estado === 'urgente' ? 'bg-orange-200 text-orange-800 dark:bg-orange-800/40 dark:text-orange-200' : 'bg-blue-200 text-blue-800 dark:bg-blue-800/40 dark:text-blue-200']">
                                                {{ ronda.estado }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-700 dark:text-slate-300">{{ ronda.descripcion }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Cargos Adicionales / Exclusiones -->
                            <div v-if="poliza.condiciones_especiales?.cargos_adicionales?.items?.length" class="p-6 border-b border-slate-100 dark:border-slate-700">
                                <h4 class="text-xs font-black text-rose-600 dark:text-rose-400 uppercase tracking-wider mb-3 flex items-center gap-1">
                                    <font-awesome-icon icon="ban" /> {{ poliza.condiciones_especiales.cargos_adicionales.titulo }}
                                </h4>
                                <ul class="space-y-2">
                                    <li v-for="(item, i) in poliza.condiciones_especiales.cargos_adicionales.items" :key="i" class="flex items-start gap-2 text-sm text-slate-700 dark:text-slate-200">
                                        <font-awesome-icon icon="times-circle" class="text-rose-500 mt-0.5 shrink-0 text-xs" />
                                        <span>{{ item }}</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Atención de Emergencias -->
                            <div v-if="poliza.condiciones_especiales?.atencion_emergencias?.items?.length" class="p-6">
                                <h4 class="text-xs font-black text-amber-600 dark:text-amber-400 uppercase tracking-wider mb-3 flex items-center gap-1">
                                    <font-awesome-icon icon="exclamation-triangle" /> {{ poliza.condiciones_especiales.atencion_emergencias.titulo }}
                                </h4>
                                <ul class="space-y-2">
                                    <li v-for="(item, i) in poliza.condiciones_especiales.atencion_emergencias.items" :key="i" class="flex items-start gap-2 text-sm text-slate-700 dark:text-slate-200">
                                        <font-awesome-icon :icon="item.icono || 'info-circle'" :class="item.icono === 'check' ? 'text-emerald-500' : 'text-amber-500'" class="mt-0.5 shrink-0 text-xs" />
                                        <span>{{ item.texto }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Historial de Facturación y Cobros -->
                        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm overflow-hidden border border-blue-100 dark:border-blue-900/30">
                            <div class="px-6 py-4 border-b border-blue-100 dark:border-blue-900/30 bg-sky-50 dark:bg-sky-900/20/50 dark:bg-blue-950/10">
                                <h3 class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                    </svg>
                                    Historial de Facturación y Cobros
                                </h3>
                            </div>
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                <thead class="bg-slate-50 dark:bg-slate-800/50">
                                    <tr>
                                        <th class="px-6 py-3 text-left">Fecha</th>
                                        <th class="px-6 py-3 text-left">Concepto</th>
                                        <th class="px-6 py-3 text-center">Estado</th>
                                        <th class="px-6 py-3 text-right">Monto</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                                    <tr v-for="cobro in poliza.cuentas_por_cobrar" :key="cobro.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                        <td class="px-6 py-4 text-xs font-medium text-slate-500 dark:text-slate-400">{{ formatDate(cobro.created_at) }}</td>
                                        <td class="px-6 py-4 text-sm text-slate-900 dark:text-slate-200">
                                            <div class="font-medium">Mensualidad Póliza</div>
                                            <div class="text-[10px] text-slate-500 dark:text-slate-400 truncate max-w-xs">{{ cobro.notas }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span :class="['px-2 py-0.5 text-[9px] font-black rounded-xl uppercase border', getEstadoCobroBadge(cobro.estado)]">
                                                {{ cobro.estado }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm font-bold text-slate-900 dark:text-slate-200">
                                            {{ formatCurrency(cobro.monto_total) }}
                                        </td>
                                    </tr>
                                    <tr v-if="!poliza.cuentas_por_cobrar || poliza.cuentas_por_cobrar.length === 0">
                                        <td colspan="4" class="px-6 py-8 text-center text-slate-400 dark:text-slate-500 text-sm italic">
                                            No hay registros de cobros generados automáticamente aún.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Historial de Servicios (Tickets) -->
                        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-800/50 rounded-2xl shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center">
                                <h3 class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Historial Reciente de Servicios
                                </h3>
                                <Link :href="route('soporte.create', { poliza_id: poliza.id, cliente_id: poliza.cliente_id })" class="text-sm font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                    + Reportar Servicio
                                </Link>
                            </div>
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                <thead class="bg-slate-50 dark:bg-slate-800/50">
                                    <tr>
                                        <th class="px-6 py-3 text-left">Folio</th>
                                        <th class="px-6 py-3 text-left">Título / Asunto</th>
                                        <th class="px-6 py-3 text-left">Estado</th>
                                        <th class="px-6 py-3 text-right">Fecha</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                                    <tr v-for="ticket in poliza.tickets" :key="ticket.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer" @click="router.visit(route('soporte.show', ticket.id))">
                                        <td class="px-6 py-4 font-mono text-xs font-bold text-blue-600 dark:text-blue-400">{{ ticket.numero }}</td>
                                        <td class="px-6 py-4 text-sm text-slate-900 dark:text-slate-200 truncate max-w-xs">{{ ticket.titulo }}</td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 uppercase">
                                                {{ ticket.estado }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right text-xs text-slate-500 dark:text-slate-400">
                                            {{ formatDate(ticket.created_at) }}
                                        </td>
                                    </tr>
                                    <tr v-if="poliza.tickets.length === 0">
                                        <td colspan="4" class="px-6 py-8 text-center text-slate-400 dark:text-slate-500 text-sm">
                                            No hay servicios registrados aún bajo esta póliza.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Sidebar Stats & Info -->
                    <div class="space-y-6">
                        <!-- Card de Resumen -->
                        <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl shadow-xl p-6 text-white">
                            <h3 class="text-lg font-bold mb-4 opacity-90">Resumen Mensual</h3>
                            
                            <div class="space-y-6">
                                <!-- Consumo de Servicios Remotos (Soporte Técnico) -->
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <div class="text-[10px] opacity-75 uppercase font-black tracking-wider flex items-center gap-1">
                                            <span>📞</span> Soporte Técnico (Remoto)
                                        </div>
                                        <div class="text-[10px] font-black opacity-60">Mensual</div>
                                    </div>
                                    <div class="flex items-end gap-2">
                                        <span class="text-4xl font-black">{{ stats.tickets_mes }}</span>
                                        <span v-if="poliza.limite_mensual_tickets" class="text-lg opacity-75 pb-1">/ {{ poliza.limite_mensual_tickets }}</span>
                                        <span v-else class="text-[10px] opacity-60 pb-1 font-bold uppercase">Sin límite</span>
                                    </div>
                                    <div v-if="poliza.limite_mensual_tickets" class="mt-2 w-full bg-blue-900/50 rounded-full h-2">
                                        <div 
                                            class="h-2 rounded-full transition-all duration-500" 
                                            :class="stats.excede_limite ? 'bg-rose-400' : 'bg-emerald-400'"
                                            :style="{ width: Math.min((stats.tickets_mes / poliza.limite_mensual_tickets) * 100, 100) + '%' }"
                                        ></div>
                                    </div>
                                    <p class="mt-1.5 text-[11px] opacity-80 leading-tight">
                                        Incluye: Windows, Software, Impresoras y fallas de Sistema.
                                    </p>
                                    <p v-if="stats.excede_limite" class="mt-2 text-[10px] font-black bg-brand-500/30 p-1 rounded-xl text-center text-rose-100">
                                        ⚠️ LÍMITE DE SOPORTE EXCEDIDO
                                    </p>
                                </div>

                                <!-- Consumo de Visitas en Sitio -->
                                <div class="pt-4 border-t border-white/10">
                                    <div class="flex justify-between items-center mb-1">
                                        <div class="text-[10px] opacity-75 uppercase font-black tracking-wider flex items-center gap-1">
                                            <span>🏠</span> Visitas en Sitio
                                        </div>
                                        <div class="text-[10px] font-black opacity-60">Mensual</div>
                                    </div>
                                    <div class="flex items-end gap-2">
                                        <span class="text-4xl font-black">{{ stats.visitas_mes }}</span>
                                        <span v-if="poliza.visitas_sitio_mensuales" class="text-lg opacity-75 pb-1">/ {{ poliza.visitas_sitio_mensuales }}</span>
                                        <span v-else class="text-[10px] opacity-60 pb-1 font-bold uppercase">Sin límite</span>
                                    </div>
                                    <div v-if="poliza.visitas_sitio_mensuales" class="mt-2 w-full bg-blue-900/40 rounded-full h-2">
                                        <div 
                                            class="h-2 rounded-full transition-all duration-500" 
                                            :class="stats.excede_visitas ? 'bg-rose-400' : 'bg-amber-400'"
                                            :style="{ width: Math.min((stats.visitas_mes / poliza.visitas_sitio_mensuales) * 100, 100) + '%' }"
                                        ></div>
                                    </div>
                                    <p v-if="stats.excede_visitas" class="mt-2 text-[10px] font-black bg-brand-500/30 p-1 rounded-xl text-center text-rose-100 uppercase">
                                        ⚠️ Visita Extra (Con Costo)
                                    </p>
                                </div>

                                <!-- Asesorías (No consumen póliza) -->
                                <div class="pt-4 border-t border-white/10">
                                    <div class="flex justify-between items-center mb-1">
                                        <div class="text-[10px] opacity-75 uppercase font-black tracking-wider flex items-center gap-1">
                                            <span>💡</span> Asesorías y Consultas
                                        </div>
                                        <div class="bg-emerald-400/20 text-emerald-300 text-[8px] px-1.5 rounded-xl font-black uppercase">Ilimitado</div>
                                    </div>
                                    <div class="flex items-end gap-2">
                                        <span class="text-2xl font-black">{{ stats.tickets_asesoria || 0 }}</span>
                                        <span class="text-sm opacity-60 pb-1 italic">realizadas este mes</span>
                                    </div>
                                    <p class="mt-1 text-[10px] opacity-70 italic">Consultas sobre uso y procedimientos.</p>
                                </div>

                                <!-- Consumo de Horas (Phase 2) -->
                                <div v-if="poliza.horas_incluidas_mensual" class="pt-3 border-t border-white/10">
                                    <div class="text-xs opacity-75 uppercase font-bold tracking-wider">Consumo de Horas</div>
                                    <div class="flex items-end gap-2 mt-1">
                                        <span class="text-2xl font-black">{{ poliza.horas_consumidas_mes || 0 }}</span>
                                        <span class="text-base opacity-75 pb-0.5">/ {{ poliza.horas_incluidas_mensual }}h</span>
                                    </div>
                                    <div class="mt-2 w-full bg-blue-900/50 rounded-full h-2">
                                        <div 
                                            class="h-2 rounded-full transition-all duration-500" 
                                            :class="poliza.excede_horas ? 'bg-rose-400' : poliza.porcentaje_horas > 80 ? 'bg-amber-400' : 'bg-emerald-400'"
                                            :style="{ width: Math.min(poliza.porcentaje_horas || 0, 100) + '%' }"
                                        ></div>
                                    </div>
                                    <div class="flex justify-between mt-1">
                                        <span class="text-[10px] opacity-60">{{ poliza.porcentaje_horas || 0 }}% usado</span>
                                        <span v-if="poliza.costo_hora_excedente" class="text-[10px] opacity-60">
                                            Excedente: ${{ poliza.costo_hora_excedente }}/hr
                                        </span>
                                    </div>
                                    <p v-if="poliza.excede_horas" class="mt-2 text-[10px] font-bold bg-brand-500/30 p-1 rounded-xl text-center text-rose-100">
                                        ⚠️ HORAS EXCEDIDAS
                                    </p>
                                </div>

                                <div class="pt-4 border-t border-white/10">
                                    <div class="text-xs opacity-75 uppercase font-bold tracking-wider mb-2">Próximo Cobro</div>
                                    <div class="text-2xl font-bold">{{ formatCurrency(poliza.monto_mensual) }}</div>
                                    <p class="text-[10px] opacity-75 mt-1">Programado para el día {{ poliza.dia_cobro }} de cada mes</p>
                                </div>
                            </div>
                        </div>

                        <!-- Alerta de Vencimiento (Phase 2) -->
                        <div v-if="poliza.dias_para_vencer !== null && poliza.dias_para_vencer <= 30" 
                             :class="[
                                 'rounded-2xl shadow-sm p-4 border-2',
                                 poliza.dias_para_vencer <= 0 ? 'bg-rose-50 dark:bg-rose-900/20 dark:bg-rose-950/20 border-rose-300 dark:border-rose-500/30' :
                                 poliza.dias_para_vencer <= 7 ? 'bg-orange-50 dark:bg-orange-950/20 border-orange-300 dark:border-brand-500/30' :
                                 'bg-brand-50 dark:bg-brand-900/20 dark:bg-amber-950/20 border-brand-300 dark:border-brand-500/30'
                             ]"
                        >
                            <div class="flex items-center gap-2">
                                <div :class="[
                                    'w-10 h-10 rounded-full flex items-center justify-center text-lg',
                                    poliza.dias_para_vencer <= 0 ? 'bg-rose-100 dark:bg-brand-500/20 text-rose-600 dark:text-rose-400' :
                                    poliza.dias_para_vencer <= 7 ? 'bg-brand-100 dark:bg-brand-500/20 text-brand-600 dark:text-orange-400' :
                                    'bg-brand-100 dark:bg-brand-500/20 text-brand-600 dark:text-amber-400'
                                ]">
                                    ⏰
                                </div>
                                <div>
                                    <div :class="[
                                        'text-sm font-black',
                                        poliza.dias_para_vencer <= 0 ? 'text-rose-800 dark:text-rose-200 dark:text-rose-400' :
                                        poliza.dias_para_vencer <= 7 ? 'text-orange-800 dark:text-orange-400' :
                                        'text-brand-800 dark:text-brand-200 dark:text-amber-400'
                                    ]">
                                        {{ poliza.dias_para_vencer <= 0 ? '¡PÓLIZA VENCIDA!' : 
                                           poliza.dias_para_vencer === 1 ? '¡Vence mañana!' :
                                           `Vence en ${poliza.dias_para_vencer} días` }}
                                    </div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ formatDate(poliza.fecha_fin) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Detalles Técnicos Sidebar -->
                        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-800/50 rounded-2xl shadow-sm p-4">
                            <h3 class="font-bold text-slate-900 dark:text-white mb-3 border-b border-slate-200 dark:border-slate-800 pb-2 text-sm">Información del Contrato</h3>
                            <dl class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <dt class="text-slate-500 dark:text-slate-400 font-medium">Fecha Inicio</dt>
                                    <dd class="text-slate-900 dark:text-slate-200 font-bold">{{ formatDate(poliza.fecha_inicio) }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-slate-500 dark:text-slate-400 font-medium">Vence</dt>
                                    <dd class="text-slate-900 dark:text-slate-200 font-bold">{{ formatDate(poliza.fecha_fin) }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-slate-500 dark:text-slate-400 font-medium">Renovación</dt>
                                    <dd class="text-slate-900 dark:text-slate-200">
                                        <span v-if="poliza.renovacion_automatica" class="text-emerald-600 font-bold">Automatica</span>
                                        <span v-else class="text-slate-500">Manual</span>
                                    </dd>
                                </div>
                                <div class="pt-2 border-t border-slate-200 dark:border-slate-800 flex justify-between items-center">
                                    <dt class="text-slate-500 dark:text-slate-400 font-medium">SLA Respuesta</dt>
                                    <dd class="text-sky-800 dark:text-sky-200 dark:text-blue-400 font-black px-2 py-0.5 bg-sky-50 dark:bg-sky-900/20 dark:bg-brand-500/10 rounded-xl">
                                        {{ poliza.sla_horas_respuesta ? poliza.sla_horas_respuesta + ' Horas' : 'Sin definir' }}
                                    </dd>
                                </div>
                                <div v-if="poliza.meses_mantenimiento && poliza.meses_mantenimiento.length" class="flex justify-between items-start pt-2">
                                    <dt class="text-slate-500 dark:text-slate-400 font-medium text-xs">Visitas al año</dt>
                                    <dd class="text-right">
                                        <div class="flex flex-wrap gap-1 justify-end">
                                            <span v-for="m in poliza.meses_mantenimiento" :key="m"
                                                class="text-[9px] font-black px-1.5 py-0.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/30 rounded-full">
                                                {{ ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'][m-1] }}
                                            </span>
                                        </div>
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Equipos Vinculados -->
                        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-800/50 rounded-2xl shadow-sm p-4">
                            <h3 class="font-bold text-slate-900 dark:text-white mb-3 border-b border-slate-200 dark:border-slate-800 pb-2 text-sm flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                </svg>
                                Equipos Cubiertos
                                <span class="text-[10px] font-bold px-2 py-0.5 bg-blue-50 dark:bg-sky-900/20/30 dark:bg-blue-900/20 text-sky-800 dark:text-blue-400 border border-sky-200 dark:border-blue-500/20 rounded-full">
                                    {{ (poliza.equipos?.length || 0) + ((poliza.condiciones_especiales?.equipos_cliente?.length) || 0) }}
                                </span>
                            </h3>
                            <div class="space-y-2 max-h-48 overflow-y-auto custom-scrollbar pr-1">
                                <template v-for="equipo in poliza.equipos" :key="'cat-' + equipo.id">
                                    <div @click="abrirHistorialEquipo({ nombre: equipo.nombre, serie_evaporador: equipo.serie || '', serie_condensadora: '' })" class="p-2 bg-[var(--ui-surface)] dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700/50 text-xs cursor-pointer hover:border-indigo-400 dark:hover:border-indigo-500 hover:shadow-md transition-all">
                                        <div class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1">
                                            <span>{{ equipo.nombre }}</span>
                                            <span class="text-[8px] text-blue-500 font-mono px-1 py-0.5 bg-blue-50 dark:bg-blue-900/20 rounded">catálogo</span>
                                        </div>
                                        <div class="text-slate-500 dark:text-slate-400 font-mono">S/N: {{ equipo.serie || 'N/A' }}</div>
                                    </div>
                                </template>
                                <template v-for="(equipo, idx) in (poliza.condiciones_especiales?.equipos_cliente || [])" :key="'extra-' + idx">
                                    <div @click="abrirHistorialEquipo(equipo)" class="p-2 bg-[var(--ui-surface)] dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700/50 text-xs cursor-pointer hover:border-indigo-400 dark:hover:border-indigo-500 hover:shadow-md transition-all">
                                        <div class="font-bold text-slate-800 dark:text-slate-200">{{ equipo.nombre }}</div>
                                        <div class="flex gap-3 text-slate-500 dark:text-slate-400 font-mono">
                                            <template v-if="equipo.tipo_equipo === 'minisplit'">
                                                <span v-if="equipo.serie_evaporador" class="text-[10px]">Eva: {{ equipo.serie_evaporador }}</span>
                                                <span v-if="equipo.serie_condensadora" class="text-[10px]">Cond: {{ equipo.serie_condensadora }}</span>
                                                <span v-if="!equipo.serie_evaporador && !equipo.serie_condensadora" class="text-[10px]">S/N: N/A</span>
                                            </template>
                                            <template v-else>
                                                <span v-if="equipo.numero_serie" class="text-[10px]">S/N: {{ equipo.numero_serie }}</span>
                                                <span v-else-if="equipo.serie_evaporador" class="text-[10px]">S/N: {{ equipo.serie_evaporador }}</span>
                                                <span v-else-if="equipo.serie" class="text-[10px]">S/N: {{ equipo.serie }}</span>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                                <div v-if="(!poliza.equipos || poliza.equipos.length === 0) && (!poliza.condiciones_especiales?.equipos_cliente || poliza.condiciones_especiales.equipos_cliente.length === 0)" class="text-center py-4 text-slate-400 dark:text-slate-500 text-xs italic">
                                    No hay equipos específicos listados.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de historial por equipo -->
        <Teleport to="body">
            <div v-if="showEquipoModal" class="fixed inset-0 z-50 overflow-y-auto custom-scrollbar px-4 py-8">
                <div class="flex min-h-full items-center justify-center">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="cerrarHistorialEquipo"></div>
                    <div class="relative bg-white dark:bg-slate-800 rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden transition-colors">
                        <div v-if="equipoSeleccionado" class="bg-gradient-to-r from-indigo-600 to-blue-600 p-6 text-white">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-xl font-bold flex items-center gap-2">
                                        <font-awesome-icon icon="microchip" />
                                        {{ equipoSeleccionado.nombre }}
                                    </h3>
                                    <div class="flex gap-4 mt-2 text-indigo-100 text-xs font-mono">
                                        <template v-if="equipoSeleccionado.tipo_equipo === 'minisplit'">
                                            <span v-if="equipoSeleccionado.serie_evaporador">Eva: {{ equipoSeleccionado.serie_evaporador }}</span>
                                            <span v-if="equipoSeleccionado.serie_condensadora">Cond: {{ equipoSeleccionado.serie_condensadora }}</span>
                                            <span v-if="!equipoSeleccionado.serie_evaporador && !equipoSeleccionado.serie_condensadora" class="italic opacity-70">Sin número de serie</span>
                                        </template>
                                        <template v-else>
                                            <span v-if="equipoSeleccionado.numero_serie">S/N: {{ equipoSeleccionado.numero_serie }}</span>
                                            <span v-else-if="equipoSeleccionado.serie_evaporador">S/N: {{ equipoSeleccionado.serie_evaporador }}</span>
                                            <span v-else-if="equipoSeleccionado.serie">S/N: {{ equipoSeleccionado.serie }}</span>
                                        </template>
                                    </div>
                                </div>
                                <button @click="cerrarHistorialEquipo" class="text-white/70 hover:text-white text-xl">&times;</button>
                            </div>
                        </div>

                        <div class="p-6 max-h-[65vh] overflow-y-auto custom-scrollbar space-y-6">
                            <div v-if="cargandoHistorial" class="text-center py-12 text-slate-400">
                                <font-awesome-icon icon="spinner" spin class="text-3xl mb-3" />
                                <p class="text-sm">Cargando historial...</p>
                            </div>

                            <template v-else>
                                <!-- Cotizaciones -->
                                <div v-if="historialEquipo.cotizaciones?.length">
                                    <h4 class="text-xs font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-wide mb-3 flex items-center gap-1">
                                        <font-awesome-icon icon="file-invoice" /> Cotizaciones
                                    </h4>
                                    <div v-for="cot in historialEquipo.cotizaciones" :key="cot.id"
                                        class="p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl border border-indigo-200 dark:border-indigo-800/30 mb-2">
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <Link :href="route('cotizaciones.edit', cot.id)" class="text-xs font-bold text-indigo-700 dark:text-indigo-300 hover:underline font-mono">{{ cot.numero }}</Link>
                                                <span class="text-[10px] text-slate-500 ml-2">{{ cot.fecha }}</span>
                                            </div>
                                            <span class="text-[9px] font-black px-2 py-0.5 rounded-full uppercase"
                                                :class="cot.estado === 'Aprobada' ? 'bg-emerald-100 text-emerald-700' : cot.estado === 'Convertida' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600'">
                                                {{ cot.estado }}
                                            </span>
                                        </div>
                                        <div class="text-xs font-black text-right mt-1">${{ (cot.total || 0).toFixed(2) }}</div>
                                        <!-- Ventas derivadas -->
                                        <div v-for="v in cot.ventas" :key="v.id" class="mt-2 pt-2 border-t border-indigo-200 dark:border-indigo-800/30">
                                            <div class="flex justify-between text-[10px]">
                                                <Link :href="route('ventas.show', v.id)" class="font-bold text-emerald-700 dark:text-emerald-300 hover:underline font-mono">Venta {{ v.numero }}</Link>
                                                <span class="font-bold">${{ (v.total || 0).toFixed(2) }}</span>
                                            </div>
                                            <div v-if="v.items?.length" class="text-[9px] text-slate-500 mt-1 space-y-0.5">
                                                <div v-for="item in v.items" :key="item.nombre" class="flex gap-1">
                                                    <span>×{{ item.cantidad }}</span>
                                                    <span class="truncate">{{ item.nombre }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Citas / Servicios -->
                                <div v-if="historialEquipo.citas?.length">
                                    <h4 class="text-xs font-black text-blue-600 dark:text-blue-400 uppercase tracking-wide mb-3 flex items-center gap-1 mt-4">
                                        <font-awesome-icon icon="tools" /> Servicios Realizados
                                    </h4>
                                    <div v-for="cita in historialEquipo.citas" :key="cita.id"
                                        class="p-4 bg-[var(--ui-surface)] dark:bg-slate-700/40 rounded-2xl border border-slate-200 dark:border-slate-700 mb-3">
                                        <div class="flex justify-between items-start mb-2">
                                            <div>
                                                <span class="text-xs font-black text-indigo-600 dark:text-indigo-400 font-mono">{{ cita.folio }}</span>
                                                <span class="text-[10px] text-slate-400 ml-2">{{ cita.fecha }}</span>
                                            </div>
                                            <span class="text-[9px] font-black px-2 py-0.5 bg-sky-50 dark:bg-sky-900/20 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800/30 rounded-full uppercase">{{ cita.tipo }}</span>
                                        </div>
                                        <div v-if="cita.trabajo_realizado" class="mb-3">
                                            <p class="text-xs text-slate-700 dark:text-slate-200 whitespace-pre-wrap leading-relaxed">{{ cita.trabajo_realizado }}</p>
                                        </div>
                                        <div v-if="cita.fotos?.length" class="grid grid-cols-4 gap-2 mt-2">
                                            <div v-for="(foto, fi) in cita.fotos" :key="fi"
                                                class="aspect-square rounded-xl overflow-hidden border border-slate-200 dark:border-slate-600 cursor-pointer hover:opacity-80 transition-opacity"
                                                @click="window.open(getFotoUrl(foto), '_blank')">
                                                <img :src="getFotoUrl(foto)" class="w-full h-full object-cover" />
                                            </div>
                                        </div>
                                        <div v-else class="mt-1 text-[10px] text-slate-400 italic">Sin fotos</div>
                                        <div class="mt-2 text-[10px] text-slate-400 flex items-center gap-1">
                                            <font-awesome-icon icon="user-cog" class="text-indigo-400" />
                                            {{ cita.tecnico }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Empty state -->
                                <div v-if="!historialEquipo.citas?.length && !historialEquipo.cotizaciones?.length" class="text-center py-12 text-slate-400">
                                    <font-awesome-icon icon="history" class="text-4xl mb-3 text-slate-300 dark:text-slate-600" />
                                    <p class="text-sm font-medium">Sin historial</p>
                                    <p class="text-xs mt-1">Este equipo aún no tiene servicios ni cotizaciones registradas.</p>
                                </div>
                            </template>
                        </div>

                        <div class="p-4 bg-[var(--ui-surface)] border-t border-slate-100 dark:border-slate-700 flex gap-2">
                            <button @click="cotizarEquipo"
                                class="flex-1 py-2.5 bg-indigo-600 text-white rounded-xl font-bold text-xs hover:bg-indigo-700 transition-all flex items-center justify-center gap-1.5">
                                <font-awesome-icon icon="file-invoice" /> Cotizar
                            </button>
                            <button @click="agendarServicioEquipo"
                                class="flex-1 py-2.5 bg-emerald-600 text-white rounded-xl font-bold text-xs hover:bg-emerald-700 transition-all flex items-center justify-center gap-1.5">
                                <font-awesome-icon icon="calendar-plus" /> Agendar Servicio
                            </button>
                            <button @click="cerrarHistorialEquipo"
                                class="px-4 py-2.5 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl font-bold text-xs hover:bg-slate-200 dark:hover:bg-slate-600 transition-all">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </component>
</template>
