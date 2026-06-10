<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Notyf } from 'notyf';
import axios from 'axios';

import { watch } from 'vue';

const notyf = new Notyf({ position: { x: 'right', y: 'top' } });
const page = usePage();

function handleFlashes() {
    const flash = page.props.flash;
    if (flash?.success) notyf.success(flash.success);
    if (flash?.error) notyf.error(flash.error);
    if (flash?.whatsapp_url) {
        window.open(flash.whatsapp_url, '_blank');
    }
}

onMounted(() => {
    handleFlashes();
});

watch(() => page.props.flash, () => {
    handleFlashes();
}, { deep: true });

const props = defineProps({
    citasHoy: { type: Array, default: () => [] },
    citasProximas: { type: Array, default: () => [] },
    fecha: { type: String, required: true },
    tecnico: { type: Object, required: true },
});

// Estado
const citaActiva = ref(null);
const showConfirmModal = ref(false);
const confirmAction = ref(null);
const showCierreModal = ref(false);
const showPostVentasModal = ref(false);
const ultimaCitaCompletadaId = ref(null);
const procesando = ref(false);

// Estado para captura de seriales
const showSerialesModal = ref(false);
const equiposPendientes = ref([]);
const serialesForm = ref([]);
const guardandoSeriales = ref(false);
const saltarSeriales = ref(false);

// Formulario de Cierre
const formCierre = useForm({
    trabajo_realizado: '',
    fotos_finales: [],
    cerrar_ticket: false,
    equipos_servicio: [],
});

const previewFotos = ref([]);

function handleFileUpload(e) {
    const files = Array.from(e.target.files);
    files.forEach(file => {
        formCierre.fotos_finales.push(file);
        const reader = new FileReader();
        reader.onload = (e) => previewFotos.value.push(e.target.result);
        reader.readAsDataURL(file);
    });
}

function removeFoto(index) {
    formCierre.fotos_finales.splice(index, 1);
    previewFotos.value.splice(index, 1);
}

// Datos
const citasOrdenadas = computed(() => {
    return [...props.citasHoy].sort((a, b) => {
        const horaA = a.hora_confirmada || a.fecha_hora;
        const horaB = b.hora_confirmada || b.fecha_hora;
        return horaA.localeCompare(horaB);
    });
});

// Helpers
function formatHora(datetime) {
    if (!datetime) return '';
    
    // Si es solo hora (HH:MM:SS)
    if (/^\d{2}:\d{2}(:\d{2})?$/.test(datetime)) {
        const [hours, minutes] = datetime.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'p.m.' : 'a.m.';
        const hour12 = hour % 12 || 12;
        return `${hour12}:${minutes} ${ampm}`;
    }
    
    const date = new Date(datetime);
    if (isNaN(date.getTime())) return '';
    return date.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', hour12: true });
}

function getEstadoInfo(estado) {
    const estados = {
        'pendiente': { label: 'Pendiente', bg: 'bg-brand-50 dark:bg-brand-900/20/30', text: 'text-brand-800 dark:text-brand-200 dark:text-amber-400', icon: 'hourglass-half' },
        'pendiente_asignacion': { label: 'Sin Asignar', bg: 'bg-brand-100 dark:bg-brand-900/30', text: 'text-orange-800 dark:text-orange-400', icon: 'clipboard-list' },
        'programado': { label: 'Programado', bg: 'bg-blue-50 dark:bg-sky-900/20/30', text: 'text-sky-800 dark:text-sky-200 dark:text-blue-400', icon: 'calendar-alt' },
        'programada': { label: 'Programada', bg: 'bg-blue-50 dark:bg-sky-900/20/30', text: 'text-sky-800 dark:text-sky-200 dark:text-blue-400', icon: 'calendar-alt' },
        'en_proceso': { label: 'En Proceso', bg: 'bg-sky-100 dark:bg-sky-900/30', text: 'text-sky-800 dark:text-sky-400', icon: 'tools' },
        'completado': { label: 'Completado', bg: 'bg-emerald-100 dark:bg-slate-800/30', text: 'text-emerald-800 dark:text-emerald-200 dark:text-slate-400', icon: 'check-circle' },
        'completada': { label: 'Completada', bg: 'bg-emerald-100 dark:bg-slate-800/30', text: 'text-emerald-800 dark:text-emerald-200 dark:text-slate-400', icon: 'check-circle' },
        'cancelado': { label: 'Cancelado', bg: 'bg-rose-50 dark:bg-rose-900/20/30', text: 'text-rose-800 dark:text-rose-200 dark:text-rose-400', icon: 'times-circle' },
        'cancelada': { label: 'Cancelada', bg: 'bg-rose-50 dark:bg-rose-900/20/30', text: 'text-rose-800 dark:text-rose-200 dark:text-rose-400', icon: 'times-circle' },
    };
    return estados[estado] || { label: estado, bg: 'bg-slate-100 dark:bg-slate-700', text: 'text-slate-800 dark:text-slate-200', icon: 'question-circle' };
}

function getTipoServicioLabel(tipo) {
    const tipos = {
        'instalacion': 'Instalación',
        'mantenimiento': 'Mantenimiento',
        'reparacion': 'Reparación',
        'garantia': 'Garantía',
        'diagnostico': 'Diagnóstico',
        'servicio_limpieza': 'Servicio limpieza',
        'otro': 'Otro',
    };
    return tipos[tipo] || tipo;
}

// Acciones
function abrirWhatsApp(telefono) {
    if (!telefono) return;
    const numero = telefono.replace(/\D/g, '');
    window.open(`https://wa.me/52${numero}`, '_blank');
}

function abrirMaps(cita) {
    // Priorizamos la dirección escrita del servicio si existe
    if (cita.direccion_servicio && String(cita.direccion_servicio).trim()) {
        const ds = String(cita.direccion_servicio).trim().replace(/,/g, ' ');
        window.open(`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(ds)}`, '_blank');
        return;
    }

    const direccion = [
        cita.direccion_calle,
        cita.direccion_colonia,
        cita.direccion_cp,
        'México'
    ].filter(Boolean).join(', ');
    
    window.open(`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(direccion)}`, '_blank');
}

function llamar(telefono) {
    if (!telefono) return;
    window.location.href = `tel:${telefono}`;
}

async function confirmarAccion(cita, accion) {
    citaActiva.value = cita;
    confirmAction.value = accion;

    if (accion === 'iniciar' && cita.poliza_id) {
        try {
            const res = await axios.get(route('citas.equipos-sin-serializar', cita.id));
            const data = res.data;
            if (data.equipos?.length > 0) {
                equiposPendientes.value = data.equipos;
                serialesForm.value = data.equipos.map(e => ({
                    index: e.index,
                    serie_evaporador: e.serie_evaporador || '',
                    serie_condensadora: e.serie_condensadora || '',
                    evaporador_no_visible: false,
                    condensadora_no_visible: false,
                }));
                saltarSeriales.value = false;
                showSerialesModal.value = true;
                return;
            }
        } catch (e) {
            // Si falla la consulta, continuar normalmente
        }
    }
    
    if (accion === 'completar') {
        formCierre.reset();
        previewFotos.value = [];
        showCierreModal.value = true;
    } else {
        showConfirmModal.value = true;
    }
}

async function guardarSeriales() {
    if (!citaActiva.value) return;
    guardandoSeriales.value = true;
    try {
        await axios.post(route('citas.capturar-seriales', citaActiva.value.id), {
            equipos: serialesForm.value,
        });
        showSerialesModal.value = false;
        notyf.success('Seriales guardados');
        showConfirmModal.value = true;
    } catch (e) {
        notyf.error('Error al guardar seriales');
    } finally {
        guardandoSeriales.value = false;
    }
}

function saltarCapturaSeriales() {
    saltarSeriales.value = true;
    showSerialesModal.value = false;
    showConfirmModal.value = true;
}

function ejecutarAccion() {
    if (!citaActiva.value || !confirmAction.value) return;
    
    procesando.value = true;
    
    const rutas = {
        'iniciar': 'citas.iniciar',
        'cancelar': 'citas.cancelar',
    };
    
    router.post(route(rutas[confirmAction.value], citaActiva.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showConfirmModal.value = false;
            citaActiva.value = null;
            confirmAction.value = null;
            procesando.value = false;
        },
        onError: () => {
            procesando.value = false;
            notyf.error('Error al procesar la acción');
        }
    });
}

function enviarReporteCierre() {
    if (!citaActiva.value) return;
    
    formCierre.post(route('citas.completar', citaActiva.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            const idCompletada = citaActiva.value?.id;
            showCierreModal.value = false;
            citaActiva.value = null;
            formCierre.reset();
            previewFotos.value = [];
            notyf.success('¡Servicio completado!');
            if (idCompletada) {
                ultimaCitaCompletadaId.value = idCompletada;
                showPostVentasModal.value = true;
            } else {
                router.reload({ preserveScroll: true });
            }
        },
        onError: () => {
            notyf.error('Error al enviar el reporte. Verifica el tamaño de las fotos.');
        }
    });
}

function irAVentasTrasCompletar() {
    const id = ultimaCitaCompletadaId.value;
    showPostVentasModal.value = false;
    ultimaCitaCompletadaId.value = null;
    if (!id) return;
    router.visit(route('ventas.create', { cita_id: id }));
}

function verFicha360(cita) {
    router.visit(route('citas.show', cita.id));
}

function omitirVentasTrasCompletar() {
    showPostVentasModal.value = false;
    ultimaCitaCompletadaId.value = null;
    router.reload({ preserveScroll: true });
}

// Equipos para selector en cierre
const equiposLista = ref([]);
watch(showCierreModal, async (val) => {
    if (!val || !citaActiva.value?.poliza_id) return;
    try {
        const res = await axios.get(route('citas.equipos-sin-serializar', citaActiva.value.id), { params: { all: true } });
        equiposLista.value = res.data.equipos || [];
    } catch (e) {
        equiposLista.value = [];
    }
});

function toggleEquipoCierre(nombre) {
    const idx = formCierre.equipos_servicio.indexOf(nombre);
    if (idx === -1) {
        formCierre.equipos_servicio.push(nombre);
    } else {
        formCierre.equipos_servicio.splice(idx, 1);
    }
}

function getAccionInfo(accion) {
    const acciones = {
        'iniciar': { 
            label: 'Iniciar Servicio', 
            description: '¿Confirmas que has llegado al domicilio y vas a iniciar el servicio?',
            icon: 'tools',
            btnClass: 'bg-indigo-600 hover:bg-indigo-700'
        },
        'completar': { 
            label: 'Completar Servicio', 
            description: '¿El servicio se ha completado satisfactoriamente?',
            icon: 'check-circle',
            btnClass: 'bg-emerald-600 hover:bg-emerald-700'
        },
        'cancelar': { 
            label: 'Cancelar Cita', 
            description: '¿Estás seguro de cancelar esta cita? Esta acción no se puede deshacer.',
            icon: 'times-circle',
            btnClass: 'bg-rose-600 hover:bg-rose-700'
        },
    };
    return acciones[accion] || {};
}
function isAtrasada(cita) {
    if (['completado', 'completada', 'cancelado', 'cancelada'].includes(cita.estado)) return false;
    
    // Extraer solo la parte YYYY-MM-DD
    let fechaCitaStr = cita.fecha_confirmada;
    if (!fechaCitaStr && cita.fecha_hora) {
        fechaCitaStr = cita.fecha_hora.includes('T') ? cita.fecha_hora.split('T')[0] : cita.fecha_hora.split(' ')[0];
    }
    
    if (!fechaCitaStr) return false;
    
    // Convertir a objetos Date comparables
    const fechaCita = new Date(fechaCitaStr + 'T23:59:59'); 
    const hoy = new Date(props.fecha + 'T00:00:00');
    
    return fechaCita < hoy;
}

function formatDateCompact(date) {
    if (!date) return '';
    return new Date(date + 'T12:00:00').toLocaleDateString('es-MX', { day: 'numeric', month: 'short' });
}

function formatCitaFecha(cita) {
    const fecha = cita.fecha_confirmada || (cita.fecha_hora ? cita.fecha_hora.split('T')[0] : null);
    if (!fecha) return 'Sin fecha';
    return new Date(fecha + 'T12:00:00').toLocaleDateString('es-MX', { weekday: 'short', day: 'numeric', month: 'short' });
}
</script>

<template>
    <Head title="Mi Agenda" />
    
    <AppLayout>
        <div class="min-h-screen bg-[var(--ui-surface)] py-6 transition-colors">
            <div class="w-full px-4">
                
                <!-- Header -->
                <div class="mb-6">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-10 h-10 bg-gradient-to-br from-brand-500 to-brand-600 rounded-full flex items-center justify-center text-white text-xl font-bold shadow-xl">
                            {{ tecnico.name?.charAt(0) || 'T' }}
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-slate-900 dark:text-white transition-colors">¡Hola, {{ tecnico.name?.split(' ')[0] }}!</h1>
                            <p class="text-slate-500 dark:text-slate-400 text-sm transition-colors">{{ new Date(fecha + 'T12:00:00').toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long' }) }}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Resumen del día -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl-sm dark:shadow-none border border-slate-100 dark:border-slate-700 p-4 mb-6 transition-colors">
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div>
                            <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ citasHoy.length }}</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400 transition-colors">Citas Hoy</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-emerald-600 dark:text-slate-400">{{ citasHoy.filter(c => ['completado', 'completada'].includes(c.estado)).length }}</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400 transition-colors">Completadas</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-brand-500 dark:text-orange-400">{{ citasHoy.filter(c => ['programado', 'programada', 'pendiente', 'pendiente_asignacion'].includes(c.estado)).length }}</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400 transition-colors">Pendientes</div>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de citas -->
                <div class="space-y-6">
                    <h2 class="text-lg font-semibold text-slate-800 dark:text-white flex items-center gap-2 transition-colors">
                        <font-awesome-icon icon="clipboard-list" class="text-indigo-500 dark:text-indigo-400" />
                        <span>Mis Citas de Hoy</span>
                    </h2>
                    
                    <!-- Sin citas -->
                    <div v-if="citasOrdenadas.length === 0" class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl-sm dark:shadow-none border border-slate-100 dark:border-slate-700 p-8 text-center transition-colors">
                        <div class="mb-4">
                            <font-awesome-icon icon="calendar-check" class="text-5xl text-emerald-500 dark:text-slate-400" />
                        </div>
                        <h3 class="text-lg font-semibold text-slate-700 dark:text-slate-200 mb-2 transition-colors">¡Sin citas programadas!</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-sm transition-colors">No tienes citas asignadas para hoy.</p>
                    </div>
                    
                    <!-- Citas -->
                    <div 
                        v-for="cita in citasOrdenadas" 
                        :key="cita.id"
                        class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl-sm dark:shadow-none border border-slate-100 dark:border-slate-700 overflow-hidden transition-colors"
                    >
                        <!-- Header de la cita -->
                        <div :class="[
                            'px-4 py-3 flex items-center justify-between',
                            isAtrasada(cita) ? 'bg-rose-50 dark:bg-rose-900/20 dark:bg-rose-900/30 text-rose-800 dark:text-rose-200 dark:text-rose-300 border-b border-rose-100 dark:border-rose-800' :
                            cita.estado === 'en_proceso' ? 'bg-sky-500 text-white' :
                            ['completado', 'completada'].includes(cita.estado) ? 'bg-brand-500 text-white' :
                            ['cancelado', 'cancelada'].includes(cita.estado) ? 'bg-rose-50 dark:bg-rose-900/20/30' : 'bg-white dark:bg-slate-800'
                        ]">
                            <div class="flex items-center gap-2">
                                <font-awesome-icon :icon="isAtrasada(cita) ? 'triangle-exclamation' : getEstadoInfo(cita.estado).icon" class="text-2xl" />
                                <div>
                                    <div class="font-bold flex items-center gap-2">
                                        {{ formatHora(cita.hora_confirmada || cita.fecha_hora) }}
                                        <span v-if="isAtrasada(cita)" class="text-[10px] uppercase bg-rose-600 text-white px-1.5 py-0.5 rounded-xl">Atrasada</span>
                                    </div>
                                    <div :class="cita.estado === 'en_proceso' || ['completado', 'completada'].includes(cita.estado) ? 'text-white/80' : 'text-slate-500 dark:text-slate-400'" class="text-xs transition-colors">
                                        {{ getTipoServicioLabel(cita.tipo_servicio) }} • <span :class="{'font-bold text-rose-600 dark:text-rose-400': isAtrasada(cita)}">{{ isAtrasada(cita) ? formatCitaFecha(cita) : cita.tipo_equipo || 'Minisplit' }}</span>
                                    </div>
                                </div>
                            </div>
                            <span :class="[
                                'px-2 py-1 rounded-xl text-xs font-medium',
                                isAtrasada(cita) ? 'bg-rose-200 dark:bg-rose-800 text-rose-900 dark:text-rose-200 border border-rose-300 dark:border-rose-700' :
                                cita.estado === 'en_proceso' ? 'bg-white/20 text-white' :
                                ['completado', 'completada'].includes(cita.estado) ? 'bg-white/20 text-white' :
                                getEstadoInfo(cita.estado).bg + ' ' + getEstadoInfo(cita.estado).text
                            ]">
                                {{ isAtrasada(cita) ? 'Vencida' : getEstadoInfo(cita.estado).label }}
                            </span>
                        </div>

                        <!-- Banner de aviso para citas atrasadas -->
                        <div v-if="isAtrasada(cita)" class="px-4 py-3 bg-rose-600 text-white flex items-center justify-between">
                            <div class="text-xs font-medium flex items-center gap-2">
                                <font-awesome-icon icon="calendar-day" />
                                <span>Debió ser el: <strong>{{ formatCitaFecha(cita) }}</strong></span>
                            </div>
                            <Link :href="route('citas.recordatorio-reprogramacion', cita.id)" 
                               class="text-[10px] font-bold bg-white text-rose-600 px-2 py-1 rounded-xl uppercase hover:bg-slate-50 transition-colors shadow-sm"
                            >
                                WhatsApp Recordatorio
                            </Link>
                        </div>
                        
                        <!-- Contenido -->
                        <div class="p-4 space-y-3">
                            <!-- Cliente -->
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center text-slate-500 dark:text-slate-400 transition-colors">
                                    <font-awesome-icon icon="user-circle" />
                                </div>
                                <div class="flex-1">
                                    <div class="font-semibold text-slate-900 dark:text-white transition-colors">{{ cita.cliente?.nombre_razon_social || 'Cliente' }}</div>
                                    <div class="text-sm text-slate-500 dark:text-slate-400 transition-colors">{{ cita.cliente?.telefono }}</div>
                                </div>
                                <!-- Botones de contacto -->
                                <div class="flex gap-2">
                                    <button 
                                        @click="llamar(cita.cliente?.telefono)"
                                        class="w-10 h-10 bg-sky-50 dark:bg-sky-900/20 dark:bg-sky-900/30 text-blue-600 dark:text-blue-400 rounded-full flex items-center justify-center hover:bg-sky-100 dark:hover:bg-blue-900/50 transition-colors"
                                        title="Llamar"
                                    >
                                        <font-awesome-icon icon="phone" />
                                    </button>
                                    <button 
                                        @click="abrirWhatsApp(cita.cliente?.telefono)"
                                        class="w-10 h-10 bg-emerald-50 dark:bg-emerald-900/20 dark:bg-slate-800/30 text-emerald-600 dark:text-slate-400 rounded-full flex items-center justify-center hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors"
                                        title="WhatsApp"
                                    >
                                        <font-awesome-icon :icon="['fab', 'whatsapp']" />
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Dirección -->
                            <div 
                                @click="abrirMaps(cita)"
                                class="flex items-start gap-3 p-3 bg-[var(--ui-surface)] dark:bg-slate-700/50 rounded-xl cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                            >
                                <font-awesome-icon icon="map-marker-alt" class="text-xl text-blue-500 dark:text-blue-400" />
                                <div class="flex-1">
                                    <div class="text-sm text-slate-900 dark:text-white transition-colors">{{ cita.direccion_calle || 'Sin dirección' }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 transition-colors">
                                        {{ cita.direccion_colonia }}{{ cita.direccion_cp ? `, C.P. ${cita.direccion_cp}` : '' }}
                                    </div>
                                    <div v-if="cita.direccion_referencias" class="text-xs text-slate-400 dark:text-slate-500 italic mt-1 transition-colors">
                                        "{{ cita.direccion_referencias }}"
                                    </div>
                                </div>
                                <span class="text-sky-800 dark:text-sky-200 dark:text-blue-300 text-sm font-bold transition-colors">Ver mapa →</span>
                            </div>
                            
                            <!-- Notas/Descripción -->
                            <div v-if="cita.descripcion || cita.problema_reportado" class="p-3 bg-brand-50 dark:bg-brand-900/20 rounded-xl border border-brand-100 dark:border-brand-800 transition-colors">
                                <div class="text-xs text-brand-600 dark:text-brand-400 font-medium mb-1 transition-colors flex items-center gap-2">
                                    <font-awesome-icon icon="sticky-note" />
                                    <span>Notas</span>
                                </div>
                                <div class="text-sm text-slate-700 dark:text-slate-200 transition-colors">{{ cita.descripcion || cita.problema_reportado }}</div>
                            </div>
                        </div>
                        <!-- Acciones Globales (Siempre visibles) -->
                        <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/10 flex items-center justify-between transition-colors">
                            <div class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest flex items-center gap-2">
                                <font-awesome-icon icon="info-circle" />
                                <span>Operaciones de Cita</span>
                            </div>
                            <div v-if="['completado', 'completada'].includes(cita.estado)" class="text-[10px] font-black text-emerald-600 uppercase tracking-widest flex items-center gap-1">
                                <font-awesome-icon icon="check-double" />
                                <span>Finalizado</span>
                            </div>
                        </div>

                        <!-- Botones de Operación -->
                        <div v-if="!['completado', 'completada', 'cancelado', 'cancelada'].includes(cita.estado)" class="px-4 py-3 border-t border-slate-100 dark:border-slate-700 flex gap-2 transition-colors">
                            <button 
                                v-if="['programado', 'programada', 'pendiente'].includes(cita.estado)"
                                @click="confirmarAccion(cita, 'iniciar')"
                                class="flex-1 py-2.5 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 transition-colors flex items-center justify-center gap-2"
                            >
                                <font-awesome-icon icon="tools" />
                                <span class="hidden sm:inline">Iniciar</span>
                                <span class="sm:hidden">Iniciar</span>
                            </button>
                            <button 
                                v-if="cita.estado === 'en_proceso'"
                                @click="confirmarAccion(cita, 'completar')"
                                class="flex-1 py-2.5 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700 transition-colors flex items-center justify-center gap-2"
                            >
                                <font-awesome-icon icon="check-circle" />
                                <span class="hidden sm:inline">Completar</span>
                                <span class="sm:hidden">Listo</span>
                            </button>
                            
                            <button 
                                @click="verFicha360(cita)"
                                class="flex-1 py-2.5 bg-sky-600 text-white rounded-xl font-medium hover:bg-sky-700 transition-colors flex items-center justify-center gap-2"
                            >
                                <font-awesome-icon icon="file-invoice" />
                                <span class="hidden sm:inline">Ver Detalles</span>
                                <span class="sm:hidden">Detalle</span>
                            </button>

                            <button 
                                @click="confirmarAccion(cita, 'cancelar')"
                                class="px-4 py-2.5 bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-200 rounded-xl font-medium hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors"
                            >
                                <font-awesome-icon icon="times" class="sm:hidden" />
                                <span class="hidden sm:inline">Cancelar</span>
                            </button>
                        </div>
                        
                        <!-- Estado completado -->
                        <div v-if="['completado', 'completada'].includes(cita.estado)" class="border-t border-emerald-100 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20 dark:bg-slate-800/20 transition-colors">
                            <div class="px-4 py-3 text-center border-b border-emerald-100 dark:border-emerald-800">
                                <span class="text-emerald-600 dark:text-slate-400 font-bold flex items-center justify-center gap-2 transition-colors">
                                    <font-awesome-icon icon="check-circle" />
                                    <span>Servicio completado</span>
                                </span>
                            </div>
                            
                            <!-- Resumen del trabajo (Solo si existe) -->
                            <div v-if="cita.trabajo_realizado || cita.fotos_finales" class="p-4 space-y-3">
                                <div v-if="cita.trabajo_realizado">
                                    <div class="text-[10px] font-bold text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-slate-400 uppercase mb-1 transition-colors">Trabajo Realizado:</div>
                                    <p class="text-xs text-slate-700 dark:text-slate-200 italic bg-white/50 dark:bg-slate-800/50 p-2 rounded-xl border border-emerald-200 dark:border-emerald-800/30/50 dark:border-emerald-700/50 transition-colors">
                                        {{ cita.trabajo_realizado }}
                                    </p>
                                </div>
                                
                                <div v-if="cita.fotos_finales?.length > 0">
                                    <div class="text-[10px] font-bold text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-slate-400 uppercase mb-2 transition-colors">Evidencias:</div>
                                    <div class="grid grid-cols-4 gap-2">
                                        <div v-for="(foto, idx) in cita.fotos_finales" :key="idx" class="aspect-square rounded-xl overflow-hidden border border-emerald-200 dark:border-emerald-800/30 dark:border-emerald-700">
                                            <img :src="'/storage/' + foto" class="w-full h-full object-cover">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Próximas citas -->
                <div v-if="citasProximas.length > 0" class="mt-8">
                    <h2 class="text-lg font-semibold text-slate-800 dark:text-white mb-4 flex items-center gap-2 transition-colors">
                        <font-awesome-icon icon="calendar-alt" class="text-indigo-500 dark:text-indigo-400" />
                        <span>Próximas Citas</span>
                    </h2>
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl-sm dark:shadow-none border border-slate-100 dark:border-slate-700 divide-y divide-slate-100 dark:divide-slate-700 transition-colors">
                        <div 
                            v-for="cita in citasProximas.slice(0, 5)" 
                            :key="cita.id"
                            class="p-4 flex items-center gap-2"
                        >
                            <div class="text-center">
                                <div class="text-xs text-slate-400 dark:text-slate-500 transition-colors">{{ new Date(cita.fecha_confirmada || cita.fecha_hora).toLocaleDateString('es-MX', { weekday: 'short' }) }}</div>
                                <div class="text-lg font-bold text-slate-900 dark:text-white transition-colors">{{ new Date(cita.fecha_confirmada || cita.fecha_hora).getDate() }}</div>
                            </div>
                            <div class="flex-1">
                                <div class="font-medium text-slate-900 dark:text-white transition-colors">{{ cita.cliente?.nombre_razon_social }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400 transition-colors">{{ formatHora(cita.hora_confirmada || cita.fecha_hora) }} • {{ getTipoServicioLabel(cita.tipo_servicio) }}</div>
                            </div>
                            <span :class="[getEstadoInfo(cita.estado).bg, getEstadoInfo(cita.estado).text, 'px-2 py-1 rounded-xl text-xs font-medium']">
                                {{ getEstadoInfo(cita.estado).label }}
                            </span>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- Modal de reporte de cierre -->
        <Teleport to="body">
            <div v-if="showCierreModal" class="fixed inset-0 z-50 overflow-y-auto custom-scrollbar px-4 py-8">
                <div class="flex min-h-full items-center justify-center">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showCierreModal = false"></div>
                    
                    <div class="relative bg-white dark:bg-slate-800 rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in duration-200 transition-colors">
                        <!-- Header -->
                        <div class="bg-indigo-600 p-6 text-white">
                            <h3 class="text-xl font-bold flex items-center gap-2">
                                <font-awesome-icon icon="check-circle" />
                                <span>Finalizar Servicio</span>
                            </h3>
                            <p class="text-indigo-100 text-sm opacity-90">Completa el reporte de trabajo para terminar la cita.</p>
                        </div>

                        <div class="p-6 space-y-6 max-h-[70vh] overflow-y-auto custom-scrollbar">
                            <!-- Descripción -->
                            <div>
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-2 uppercase tracking-wider transition-colors">
                                    ¿Qué trabajo se realizó?
                                </label>
                                <textarea 
                                    v-model="formCierre.trabajo_realizado"
                                    rows="4"
                                    class="w-full bg-[var(--ui-surface)] border-slate-300 dark:border-slate-600 text-slate-900 dark:text-white rounded-2xl focus:ring-2 focus:ring-indigo-100 dark:focus:ring-indigo-900 focus:border-brand-500 transition-all text-sm"
                                    placeholder="Describe detalladamente las reparaciones o mantenimientos hechos..."
                                ></textarea>
                            </div>

                            <!-- Equipos trabajados (solo si tiene póliza) -->
                            <div v-if="citaActiva?.poliza_id && equiposLista.length > 0">
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-2 uppercase tracking-wider transition-colors">
                                    <font-awesome-icon icon="microchip" class="mr-1" />
                                    ¿Qué equipo(s) trabajaste?
                                </label>
                                <div class="grid grid-cols-2 gap-2">
                                    <label v-for="eq in equiposLista" :key="eq.index"
                                        @click="toggleEquipoCierre(eq.nombre)"
                                        class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition-all text-xs font-medium"
                                        :class="formCierre.equipos_servicio.includes(eq.nombre)
                                            ? 'bg-indigo-100 dark:bg-indigo-900/40 border-indigo-300 dark:border-indigo-600 text-indigo-800 dark:text-indigo-200'
                                            : 'bg-[var(--ui-surface)] dark:bg-slate-700/30 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-indigo-300'"
                                    >
                                        <font-awesome-icon :icon="formCierre.equipos_servicio.includes(eq.nombre) ? 'check-square' : 'square'" class="text-xs" />
                                        <span class="truncate">{{ eq.nombre }}</span>
                                    </label>
                                </div>
                                <p class="text-[10px] text-slate-400 mt-1.5 italic">Selecciona los equipos en los que realizaste el servicio para guardar su historial.</p>
                            </div>

                            <!-- OPCIÓN PARA CERRAR TICKET ASOCIADO (Solo si tiene ticket_id) -->
                            <div v-if="citaActiva?.ticket_id" class="p-4 bg-indigo-50 dark:bg-sky-900/30 border border-indigo-100 dark:border-indigo-800 rounded-2xl transition-colors">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input 
                                        type="checkbox" 
                                        v-model="formCierre.cerrar_ticket"
                                        class="h-5 w-5 text-indigo-600 border-slate-300 dark:border-slate-700 rounded-xl focus:ring-brand-500 dark:bg-slate-800"
                                    >
                                    <div class="flex-1">
                                        <div class="text-xs font-bold text-indigo-900 dark:text-indigo-300 uppercase transition-colors">Resolver Ticket #{{ citaActiva.ticket_id }}</div>
                                        <p class="text-[10px] text-indigo-600 dark:text-indigo-400 transition-colors">Marcar el ticket como resuelto automáticamente.</p>
                                    </div>
                                </label>
                            </div>

                            <!-- Fotos Finales -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider transition-colors">
                                        Evidencias Finales
                                    </label>
                                    <span class="text-[10px] bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 px-2 py-1 rounded-full font-bold transition-colors">
                                        {{ formCierre.fotos_finales.length }} FOTOS
                                    </span>
                                </div>
                                
                                <div class="grid grid-cols-3 gap-3">
                                    <!-- Botón de subida -->
                                    <label class="aspect-square rounded-2xl border-2 border-dashed border-slate-300 dark:border-slate-600 hover:border-brand-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-all cursor-pointer flex flex-col items-center justify-center gap-1 group">
                                        <font-awesome-icon icon="camera" class="text-2xl text-slate-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 group-hover:scale-105 transition-all" />
                                        <span class="text-[10px] font-bold text-slate-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 uppercase transition-colors">Añadir</span>
                                        <input type="file" @change="handleFileUpload" multiple accept="image/*" class="hidden">
                                    </label>

                                    <!-- Previews -->
                                    <div 
                                        v-for="(foto, index) in previewFotos" 
                                        :key="index"
                                        class="relative aspect-square rounded-2xl overflow-hidden group shadow-sm bg-slate-100 dark:bg-slate-700"
                                    >
                                        <img :src="foto" class="w-full h-full object-cover">
                                        <button 
                                            @click="removeFoto(index)"
                                            class="absolute top-1 right-1 w-10 h-10 bg-brand-500 text-white rounded-full flex items-center justify-center text-xs shadow-xl hover:scale-105 transition-transform"
                                        >
                                            <font-awesome-icon icon="times" />
                                        </button>
                                    </div>
                                </div>
                                <p class="mt-3 text-[10px] text-slate-400 dark:text-slate-500 italic transition-colors">
                                    Tip: Toma fotos del equipo funcionando o de las piezas reemplazadas.
                                </p>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="p-6 bg-[var(--ui-surface)] border-t border-slate-100 dark:border-slate-700 flex gap-3 transition-colors">
                            <button 
                                @click="showCierreModal = false"
                                class="flex-1 py-3 text-slate-500 dark:text-slate-400 font-bold text-sm uppercase tracking-wide hover:bg-slate-200 dark:hover:bg-slate-700 rounded-2xl transition-all"
                            >
                                Atrás
                            </button>
                            <button 
                                @click="enviarReporteCierre"
                                :disabled="formCierre.processing"
                                class="flex-[2] py-3 bg-indigo-600 text-white font-bold text-sm uppercase tracking-wide rounded-2xl shadow-xl shadow-indigo-200 dark:shadow-indigo-900/30 hover:bg-indigo-700 hover:scale-[1.02] active:scale-[0.98] transition-all disabled:opacity-50"
                            >
                                <span v-if="formCierre.processing">Enviando...</span>
                                <span v-else>Finalizar Servicio</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ¿Hubo venta tras completar servicio? -->
        <Teleport to="body">
            <div v-if="showPostVentasModal" class="fixed inset-0 z-[60] overflow-y-auto custom-scrollbar px-4 py-8">
                <div class="flex min-h-full items-center justify-center">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="omitirVentasTrasCompletar"></div>
                    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-md p-6 text-center border border-slate-100 dark:border-slate-700 transition-colors">
                        <div class="text-4xl mb-3 text-emerald-600 dark:text-slate-400">
                            <font-awesome-icon icon="cart-shopping" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">¿Hubo venta en este servicio?</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-sm mb-6">
                            Si cobraste materiales o un servicio adicional al cliente, puedes registrar la venta y vincularla a esta cita.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <button
                                type="button"
                                class="flex-1 py-3 rounded-xl font-bold text-sm uppercase tracking-wider bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors"
                                @click="omitirVentasTrasCompletar"
                            >
                                No
                            </button>
                            <button
                                type="button"
                                class="flex-1 py-3 rounded-xl font-bold text-sm uppercase tracking-wider bg-indigo-600 hover:bg-indigo-700 text-white transition-colors"
                                @click="irAVentasTrasCompletar"
                            >
                                Sí, ir a ventas
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
        
        <!-- Modal de captura de seriales -->
        <Teleport to="body">
            <div v-if="showSerialesModal" class="fixed inset-0 z-50 overflow-y-auto custom-scrollbar px-4 py-8">
                <div class="flex min-h-full items-center justify-center">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="saltarCapturaSeriales"></div>
                    <div class="relative bg-white dark:bg-slate-800 rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in duration-200 transition-colors">
                        <div class="bg-indigo-600 p-6 text-white">
                            <h3 class="text-xl font-bold flex items-center gap-2">
                                <font-awesome-icon icon="microchip" />
                                <span>Capturar Números de Serie</span>
                            </h3>
                            <p class="text-indigo-100 text-sm opacity-90">
                                Los siguientes equipos de la póliza no tienen número de serie registrado.
                                Captúralos antes de iniciar o márcalos como "no visible".
                            </p>
                        </div>
                        <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto custom-scrollbar">
                            <div v-for="(eq, i) in equiposPendientes" :key="i"
                                class="p-4 bg-[var(--ui-surface)] dark:bg-slate-700/50 rounded-2xl border border-slate-200 dark:border-slate-700">
                                <div class="font-bold text-sm text-slate-800 dark:text-slate-200 mb-3 flex items-center gap-2">
                                    <font-awesome-icon icon="microchip" class="text-indigo-500" />
                                    {{ eq.nombre }}
                                </div>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Serie Evaporador (unidad interior)</label>
                                        <div class="flex gap-2 items-center">
                                            <input v-model="serialesForm[i].serie_evaporador" type="text"
                                                :disabled="serialesForm[i].evaporador_no_visible"
                                                class="flex-1 bg-white dark:bg-black/50 border border-slate-200 dark:border-slate-700 rounded-xl h-9 text-xs font-mono text-slate-800 dark:text-slate-200 placeholder-slate-400 disabled:opacity-40 disabled:cursor-not-allowed px-3 transition-all" />
                                            <label class="flex items-center gap-1.5 text-[9px] text-slate-400 cursor-pointer shrink-0">
                                                <input type="checkbox" v-model="serialesForm[i].evaporador_no_visible"
                                                    class="h-3.5 w-3.5 rounded text-slate-400 border-slate-300 dark:border-slate-600 focus:ring-0" />
                                                No visible
                                            </label>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Serie Condensadora (unidad exterior)</label>
                                        <div class="flex gap-2 items-center">
                                            <input v-model="serialesForm[i].serie_condensadora" type="text"
                                                :disabled="serialesForm[i].condensadora_no_visible"
                                                class="flex-1 bg-white dark:bg-black/50 border border-slate-200 dark:border-slate-700 rounded-xl h-9 text-xs font-mono text-slate-800 dark:text-slate-200 placeholder-slate-400 disabled:opacity-40 disabled:cursor-not-allowed px-3 transition-all" />
                                            <label class="flex items-center gap-1.5 text-[9px] text-slate-400 cursor-pointer shrink-0">
                                                <input type="checkbox" v-model="serialesForm[i].condensadora_no_visible"
                                                    class="h-3.5 w-3.5 rounded text-slate-400 border-slate-300 dark:border-slate-600 focus:ring-0" />
                                                No visible
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p class="text-[10px] text-slate-400 italic">Marca "No visible" si el equipo está instalado y no puedes acceder al número de serie.</p>
                        </div>
                        <div class="p-6 bg-[var(--ui-surface)] border-t border-slate-100 dark:border-slate-700 flex gap-3 transition-colors">
                            <button @click="saltarCapturaSeriales"
                                class="flex-1 py-3 text-slate-500 dark:text-slate-400 font-bold text-xs uppercase tracking-wide hover:bg-slate-100 dark:hover:bg-slate-700 rounded-2xl transition-all">
                                Omitir
                            </button>
                            <button @click="guardarSeriales" :disabled="guardandoSeriales"
                                class="flex-[2] py-3 bg-indigo-600 text-white font-bold text-xs uppercase tracking-wide rounded-2xl shadow-xl shadow-indigo-200 dark:shadow-indigo-900/30 hover:bg-indigo-700 transition-all disabled:opacity-50">
                                <span v-if="guardandoSeriales">Guardando...</span>
                                <span v-else>Guardar y Continuar</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal de confirmación -->
        <Teleport to="body">
            <div v-if="showConfirmModal" class="fixed inset-0 z-50 overflow-y-auto custom-scrollbar">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showConfirmModal = false"></div>
                    
                    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-sm p-6 text-center transition-colors">
                        <div class="text-5xl mb-4">{{ getAccionInfo(confirmAction).icon }}</div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2 transition-colors">{{ getAccionInfo(confirmAction).label }}</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-sm mb-6 transition-colors">{{ getAccionInfo(confirmAction).description }}</p>
                        
                        <div class="flex gap-3">
                            <button 
                                @click="showConfirmModal = false"
                                class="flex-1 py-2.5 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl font-medium hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors"
                            >
                                Cancelar
                            </button>
                            <button 
                                @click="ejecutarAccion"
                                :disabled="procesando"
                                :class="[getAccionInfo(confirmAction).btnClass, 'flex-1 py-2.5 text-white rounded-xl font-medium transition-colors disabled:opacity-50']"
                            >
                                {{ procesando ? 'Procesando...' : 'Confirmar' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
        
    </AppLayout>
</template>
