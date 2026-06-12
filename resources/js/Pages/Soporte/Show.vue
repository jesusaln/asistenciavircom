<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { ref, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { differenceInMinutes, parseISO } from 'date-fns';
import Swal from '@/Utils/Swal';

const props = defineProps({
    ticket: Object,
    historialCliente: Array,
    categorias: Array,
    usuarios: Array,
    canDelete: Boolean,
});

const nuevoComentario = useForm({
    contenido: '',
    es_interno: false,
    archivos: [],
});

const agregarComentario = () => {
    nuevoComentario.post(route('soporte.comentario', props.ticket.id), {
        preserveScroll: true,
        onSuccess: () => {
            nuevoComentario.reset('contenido', 'archivos', 'es_interno');
        },
    });
};

const onArchivosChange = (e) => {
    nuevoComentario.archivos = e.target.files;
};

const showHorasModal = ref(false);
const estadoPendiente = ref('');
const horasTrabajadas = ref('');
const servicioInicio = ref('');
const servicioFin = ref('');
const sinPoliza = !props.ticket.poliza;
const tipoServicio = ref(props.ticket.tipo_servicio || (sinPoliza ? 'costo' : 'garantia'));
const generarVentaAlCerrar = ref(sinPoliza && !props.ticket.venta_id);

watch([servicioInicio, servicioFin], ([inicio, fin]) => {
    if (inicio && fin) {
        const d1 = new Date(inicio);
        const d2 = new Date(fin);
        if (d2 > d1) {
            const diffMinutes = (d2 - d1) / (1000 * 60);
            horasTrabajadas.value = (diffMinutes / 60).toFixed(2);
        }
    }
});

const cambiarEstado = (nuevoEstado) => {
    if (['resuelto', 'cerrado'].includes(nuevoEstado)) {
        estadoPendiente.value = nuevoEstado;
        showHorasModal.value = true;
    } else {
        enviarCambioEstado(nuevoEstado, null);
    }
};

const confirmarConsumoHoras = () => {
    const horas = horasTrabajadas.value ? parseFloat(horasTrabajadas.value) : null;
    const debeGenerarVenta = generarVentaAlCerrar.value;
    
    const datos = { 
        estado: estadoPendiente.value,
        generar_venta: debeGenerarVenta 
    };
    if (horas !== null) datos.horas_trabajadas = horas;
    if (servicioInicio.value) datos.servicio_inicio_at = servicioInicio.value;
    if (servicioFin.value) datos.servicio_fin_at = servicioFin.value;
    if (tipoServicio.value) datos.tipo_servicio = tipoServicio.value;
    
    router.post(route('soporte.cambiar-estado', props.ticket.id), datos, { preserveScroll: true });
    
    showHorasModal.value = false;
    horasTrabajadas.value = '';
    servicioInicio.value = '';
    servicioFin.value = '';
    estadoPendiente.value = '';
    generarVentaAlCerrar.value = false;
};

const cancelarConsumoHoras = () => {
    showHorasModal.value = false;
    horasTrabajadas.value = '';
    servicioInicio.value = '';
    servicioFin.value = '';
    estadoPendiente.value = '';
};

const enviarCambioEstado = (estado, horas, inicio = null, fin = null, tipo = null) => {
    const datos = { estado };
    if (horas !== null) datos.horas_trabajadas = horas;
    if (inicio) datos.servicio_inicio_at = inicio;
    if (fin) datos.servicio_fin_at = fin;
    if (tipo) datos.tipo_servicio = tipo;
    
    router.post(route('soporte.cambiar-estado', props.ticket.id), datos, { preserveScroll: true });
};

const asignarA = (usuarioId) => {
    router.post(route('soporte.asignar', props.ticket.id), { asignado_id: usuarioId }, { preserveScroll: true });
};

const generarVenta = async () => {
    const result = await Swal.fire({
        title: 'Generar venta',
        text: '¿Generar una nota de venta para este ticket?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, generar',
        cancelButtonText: 'Cancelar',
    })
    if (result.isConfirmed) {
        router.post(route('soporte.generar-venta', props.ticket.id));
    }
};

const agendarCita = () => {
    router.get(route('citas.create'), { 
        ticket_id: props.ticket.id,
        cliente_id: props.ticket.cliente_id,
        tipo_servicio: props.ticket.tipo_servicio,
        descripcion: props.ticket.titulo + "\n" + props.ticket.descripcion
    });
};

const eliminarTicket = async () => {
    const result = await Swal.fire({
        title: '¿Eliminar ticket?',
        text: `¿Estás seguro de eliminar el ticket ${props.ticket.numero}? Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    })
    if (result.isConfirmed) {
        router.delete(route('soporte.destroy', props.ticket.id));
    }
};

const states_list = ['abierto', 'en_progreso', 'pendiente', 'resuelto', 'cerrado'];

const getEstadoBadge = (estado) => {
    const colores = {
        abierto: 'bg-brand-500/10 text-blue-400 border-blue-500/20',
        en_progreso: 'bg-brand-500/10 text-brand-400 border-brand-500/20',
        pendiente: 'bg-brand-500/10 text-orange-400 border-brand-500/20',
        resuelto: 'bg-brand-500/10 text-emerald-400 border-emerald-500/20',
        cerrado: 'bg-slate-500/10 text-slate-400 border-slate-500/20',
    };
    return colores[estado] || 'bg-slate-100 text-slate-800';
};

const getPrioridadBadge = (prioridad) => {
    const colores = {
        urgente: 'bg-brand-500/10 text-rose-400 border-rose-500/20',
        alta: 'bg-brand-500/10 text-orange-400 border-brand-500/20',
        media: 'bg-brand-500/10 text-brand-400 border-brand-500/20',
        baja: 'bg-brand-500/10 text-emerald-400 border-emerald-500/20',
    };
    return colores[prioridad] || 'bg-slate-100 text-slate-500 border-slate-200';
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('es-MX', { 
        day: '2-digit', month: 'short', year: 'numeric', 
        hour: '2-digit', minute: '2-digit' 
    });
};
</script>

<template>
    <AppLayout :title="`Ticket ${ticket.numero}`">
        <Head :title="`Ticket ${ticket.numero}`" />

        <div class="min-h-screen bg-[var(--ui-surface)] text-slate-800 dark:text-slate-200 py-12 px-4 sm:px-6 lg:px-8 transition-colors">
            <div class="max-w-[1600px] mx-auto">
                
                <!-- Breadcrumbs & Header -->
                <div class="mb-12 animate-in fade-in slide-in-from-top-4 duration-700">
                    <Link :href="route('soporte.index')" class="inline-flex items-center gap-2 text-[10px] font-black text-slate-400 dark:text-slate-500 hover:text-brand-600 dark:hover:text-brand-500 uppercase tracking-[0.2em] mb-8 transition-colors group">
                        <svg class="w-4 h-4 transform group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                        Volver al Centro de Mando
                    </Link>
                    
                    <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-8">
                        <div>
                            <div class="flex flex-wrap items-center gap-3 mb-6">
                                <span class="font-black text-sm text-brand-500 bg-brand-500/10 px-4 py-1.5 rounded-xl border border-brand-500/20 shadow-2xl tracking-tighter">{{ ticket.numero }}</span>
                                <span v-if="ticket.folio_externo" class="font-black text-xs text-amber-500 bg-amber-500/10 px-4 py-1.5 rounded-xl border border-amber-500/20 shadow-2xl">📋 {{ ticket.folio_externo }}</span>
                                <span :class="['px-4 py-1.5 text-[10px] font-black rounded-full uppercase tracking-wide border shadow-xl transition-all duration-500', getEstadoBadge(ticket.estado)]">
                                    {{ ticket.estado.replace('_', ' ') }}
                                </span>
                                <span :class="['px-4 py-1.5 text-[10px] font-black rounded-full uppercase tracking-wide border shadow-xl transition-all duration-500', getPrioridadBadge(ticket.prioridad)]">
                                    {{ ticket.prioridad?.toUpperCase() || 'NORMAL' }}
                                </span>
                                <span v-if="ticket.tipo_servicio === 'costo'" class="px-4 py-1.5 text-[10px] font-black uppercase tracking-wide rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 shadow-xl">
                                    💰 CON CARGO
                                </span>
                                <span v-else class="px-4 py-1.5 text-[10px] font-black uppercase tracking-wide rounded-full bg-brand-500/10 text-emerald-400 border border-emerald-500/20 shadow-xl">
                                    🛡️ BAJO PÓLIZA
                                </span>
                            </div>
                            <h1 class="text-4xl md:text-5xl font-black text-slate-900 dark:text-white tracking-tighter mb-2 uppercase leading-none">{{ ticket.titulo }}</h1>
                            <p class="text-slate-500 text-[10px] font-black uppercase tracking-[0.2em] italic">Detección inicial: {{ formatDate(ticket.created_at) }}</p>
                        </div>
                        
                        <div class="flex flex-wrap gap-4">
                             <button 
                                v-if="ticket.tipo_servicio === 'costo' && !ticket.venta_id"
                                @click="generarVenta"
                                class="px-6 py-4 bg-sky-600 hover:bg-sky-700 text-white text-[10px] font-black uppercase tracking-wide rounded-2xl transition-all shadow-xl shadow-indigo-600/20 flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                                Generar Venta
                            </button>
                             <button 
                                @click="agendarCita"
                                class="px-6 py-4 bg-brand-600 hover:bg-brand-700 text-white text-[10px] font-black uppercase tracking-wide rounded-2xl transition-all shadow-xl shadow-brand-600/20 flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                Agendar Intervención
                            </button>
                             <Link 
                                :href="route('todos.index', { ticket_id: ticket.id })"
                                class="px-6 py-4 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-600 dark:text-white text-[10px] font-black uppercase tracking-wide rounded-2xl transition-all flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                                Crear Pendiente
                            </Link>
                             <Link 
                                v-if="ticket.venta_id"
                                :href="route('ventas.edit', ticket.venta_id)"
                                class="px-6 py-4 bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-black uppercase tracking-wide rounded-2xl transition-all shadow-xl shadow-emerald-600/20 flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                Registro de Venta
                            </Link>
                            <button 
                                v-if="canDelete"
                                @click="eliminarTicket"
                                class="px-6 py-4 bg-brand-500/10 dark:bg-rose-950/50 border border-rose-500/20 hover:bg-slate-500 text-rose-600 dark:text-rose-500 hover:text-white text-[10px] font-black uppercase tracking-wide rounded-2xl transition-all shadow-xl flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                Purgar Entidad
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-12 gap-12">
                    <!-- Main Content Panel -->
                    <div class="xl:col-span-8 space-y-12 animate-in fade-in slide-in-from-left-8 duration-700 delay-150">
                        
                        <!-- Article/Description Card -->
                        <div class="relative group">
                            <div class="absolute -inset-1 bg-gradient-to-br from-brand-500/10 to-transparent rounded-[3rem] blur opacity-0 group-hover:opacity-100 transition duration-700"></div>
                            <div class="relative bg-white dark:bg-slate-800/50 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-[3rem] p-10 md:p-14 overflow-hidden shadow-md dark:shadow-2xl">
                                <div class="absolute -right-20 -top-20 w-80 h-80 bg-brand-500/5 rounded-full blur-[100px]"></div>
                                <div class="flex items-center gap-5 mb-10">
                                    <div class="w-14 h-14 rounded-2xl bg-brand-500/10 flex items-center justify-center text-brand-500 border border-brand-500/20">
                                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </div>
                                    <div>
                                        <h3 class="text-xs font-black text-slate-500 uppercase tracking-[0.4em] mb-1">Descripción de la Incidencia</h3>
                                        <div class="w-12 h-1 bg-brand-500"></div>
                                    </div>
                                </div>
                                
                                <div class="bg-slate-50 dark:bg-black/20 p-8 rounded-[2rem] border border-slate-200 dark:border-white/5 shadow-inner">
                                    <p class="text-lg text-slate-700 dark:text-slate-200 whitespace-pre-wrap leading-relaxed font-medium italic">"{{ ticket.description || ticket.descripcion }}"</p>
                                    <div v-if="ticket.archivos?.length > 0" class="mt-4 grid grid-cols-4 gap-2">
                                      <a v-for="(f, i) in ticket.archivos" :key="i" :href="'/storage/' + f" target="_blank" class="block aspect-square rounded-xl overflow-hidden border border-slate-200 dark:border-white/10 hover:opacity-80 transition-opacity">
                                        <img :src="'/storage/' + f" class="w-full h-full object-cover" />
                                      </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Conversation Timeline Panel -->
                        <div class="bg-white dark:bg-slate-800/50 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-[3.5rem] p-10 shadow-md dark:shadow-2xl overflow-hidden relative">
                            <div class="flex items-center justify-between mb-12">
                                <h3 class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-[0.4em] flex items-center gap-4">
                                     <div class="w-1 h-8 bg-brand-500"></div>
                                     Bitácora de Intervenciones
                                </h3>
                                <span class="text-[9px] font-black text-slate-500 uppercase tracking-wide italic tracking-[0.2em] bg-[var(--ui-surface)] dark:bg-white/5 px-4 py-2 rounded-full border border-slate-200 dark:border-white/5">PROTOCOLO DE COMUNICACIÓN ACTIVO</span>
                            </div>
                            
                            <div class="space-y-6 mb-12 max-h-[1000px] overflow-y-auto px-4 custom-scrollbar">
                                <div 
                                    v-for="(comentario, idx) in ticket.comentarios" 
                                    :key="comentario.id"
                                    class="relative animate-in fade-in slide-in-from-bottom-8 duration-700"
                                    :style="{ 'animation-delay': (idx * 100) + 'ms' }"
                                >
                                    <div 
                                        :class="[
                                            'relative p-8 rounded-[2.5rem] transition-all duration-500 group overflow-hidden border',
                                            comentario.es_interno 
                                                ? 'bg-brand-500/5 border-brand-500/20 shadow-[0_20px_40px_-15px_rgba(245,158,11,0.1)]' 
                                                : 'bg-slate-50 dark:bg-slate-950/40 border-slate-200 dark:border-white/5 shadow-md dark:shadow-2xl hover:bg-slate-100 dark:hover:bg-slate-950/60'
                                        ]"
                                    >
                                        <div v-if="comentario.es_interno" class="absolute inset-y-0 right-0 w-1 bg-brand-500"></div>
                                        
                                        <div class="flex justify-between items-start mb-6">
                                            <div class="flex items-center gap-5">
                                                <div class="relative">
                                                     <div class="absolute -inset-1 bg-gradient-to-br from-brand-500 to-brand-600 rounded-2xl blur-sm opacity-20"></div>
                                                     <div class="relative w-10 h-10 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-white/10 flex items-center justify-center text-slate-800 dark:text-white text-sm font-black shadow-xl overflow-hidden uppercase italic">
                                                         {{ comentario.user?.name?.charAt(0) || '?' }}
                                                     </div>
                                                </div>
                                                <div>
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">{{ comentario.user?.name }}</span>
                                                        <span v-if="comentario.es_interno" class="text-[8px] uppercase tracking-wide font-black text-brand-500 bg-brand-500/10 px-3 py-1 rounded-full border border-brand-500/20">
                                                            SECCIÓN INTERNA
                                                        </span>
                                                        <span v-if="comentario.tipo === 'estado'" class="text-[8px] uppercase tracking-wide font-black text-indigo-400 bg-indigo-500/10 px-3 py-1 rounded-full border border-indigo-500/20">
                                                            EVOLUCIÓN SISTEMA
                                                        </span>
                                                    </div>
                                                    <div class="text-[9px] text-slate-500 font-bold uppercase tracking-[0.2em] mt-1.5 italic">
                                                        Sincronizado: {{ formatDate(comentario.created_at) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="pl-1 space-y-6">
                                            <p class="text-slate-700 dark:text-slate-200 text-sm leading-relaxed font-medium">{{ comentario.contenido }}</p>
                                            <div v-if="comentario.metadata?.archivos?.length > 0" class="mt-3 grid grid-cols-4 gap-2">
                                              <a v-for="(f, i) in comentario.metadata.archivos" :key="i" :href="'/storage/' + f" target="_blank" class="block aspect-square rounded-xl overflow-hidden border border-slate-200 dark:border-white/10 hover:opacity-80 transition-opacity">
                                                <img :src="'/storage/' + f" class="w-full h-full object-cover" />
                                              </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="ticket.comentarios.length === 0" class="text-center py-20 bg-[var(--ui-surface)] dark:bg-slate-950/20 rounded-[3rem] border border-dashed border-slate-200 dark:border-white/5 border-spacing-8">
                                    <div class="w-16 h-16 bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-white/5 rounded-2xl flex items-center justify-center mx-auto mb-6 opacity-30">
                                        <svg class="w-10 h-10 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                                    </div>
                                    <div class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.4em] italic leading-loose">Silencio operacional detectado.<br>A la espera del primer reporte de campo.</div>
                                </div>
                            </div>

                            <!-- Comment Input Premium -->
                            <form @submit.prevent="agregarComentario" class="relative group">
                                <div class="absolute -inset-0.5 bg-gradient-to-r from-brand-500/20 via-brand-600/20 to-transparent rounded-[2.5rem] blur opacity-0 group-focus-within:opacity-100 transition duration-700"></div>
                                <div class="relative bg-[var(--ui-surface)] dark:bg-slate-950/60 backdrop-blur-3xl border border-slate-200 dark:border-white/5 rounded-[2.5rem] p-8 shadow-inner ring-1 ring-slate-200 dark:ring-white/5 focus-within:ring-brand-500/30 transition-all duration-500">
                                    <div class="flex items-center justify-between mb-4">
                                         <label class="text-[9px] font-black text-slate-500 uppercase tracking-wide italic flex items-center gap-2">
                                             <div class="w-1.5 h-1.5 rounded-full bg-brand-500 animate-pulse"></div>
                                             Nueva Entrada de Bitácora
                                         </label>
                                         <label class="flex items-center gap-2 text-[9px] font-black text-slate-500 dark:text-slate-500 uppercase tracking-wide cursor-pointer hover:text-brand-500 transition-colors">
                                            <input type="checkbox" v-model="nuevoComentario.es_interno" class="w-4 h-4 rounded-xl bg-[var(--ui-surface)] border-slate-200 dark:border-white/5 text-brand-600 focus:ring-brand-500/30" />
                                            Nivel Interno
                                         </label>
                                    </div>
                                    <textarea
                                        v-model="nuevoComentario.contenido"
                                        rows="4"
                                        placeholder="Escribe el reporte técnico aquí..."
                                        class="w-full bg-transparent border-none focus:ring-0 text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-700 text-sm font-medium resize-none min-h-[120px] custom-scrollbar"
                                    ></textarea>
                                    
                                    <div class="flex items-center gap-4 mt-4">
                                        <label class="flex items-center gap-2 text-[9px] font-black text-slate-500 dark:text-slate-500 uppercase tracking-wide cursor-pointer hover:text-brand-500 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                            <input type="file" multiple accept="image/*" @change="onArchivosChange" class="hidden" />
                                            {{ nuevoComentario.archivos?.length > 0 ? `${nuevoComentario.archivos.length} foto(s)` : 'Agregar Fotos' }}
                                        </label>
                                    </div>
                                    <div class="flex justify-end mt-6">
                                        <button 
                                            type="submit"
                                            :disabled="!nuevoComentario.contenido || nuevoComentario.processing"
                                            class="px-8 py-4 bg-brand-600 hover:bg-brand-700 text-white text-[10px] font-black uppercase tracking-wide rounded-2xl transition-all shadow-xl shadow-brand-600/20 flex items-center gap-2 disabled:opacity-30 disabled:cursor-not-allowed group/btn"
                                        >
                                            <svg v-if="nuevoComentario.processing" class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            <svg v-else class="w-4 h-4 transform group-hover/btn:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                            Inyectar Reporte
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Sidebar Panel -->
                    <div class="xl:col-span-4 space-y-6 animate-in fade-in slide-in-from-right-8 duration-700 delay-300">
                        
                        <!-- Management Card -->
                        <div class="bg-white dark:bg-slate-800/50 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-[3rem] p-10 shadow-md dark:shadow-2xl relative overflow-hidden">
                             <div class="absolute -left-10 -top-10 w-40 h-40 bg-indigo-500/5 rounded-full blur-[60px]"></div>
                             <h3 class="text-[10px] font-black text-slate-800 dark:text-white uppercase tracking-[0.4em] mb-10 flex items-center gap-4">
                                <span class="text-brand-500 text-xl font-normal">⚡</span>
                                Control Operativo
                             </h3>
                            
                             <!-- Status Mutation -->
                             <div class="mb-10">
                                <label class="block text-[9px] uppercase tracking-[0.2em] font-black text-slate-500 mb-6 flex justify-between items-center px-1">
                                    <span>Evolución del Estado</span>
                                    <div class="flex gap-1">
                                        <div class="w-1 h-1 rounded-full bg-brand-500 shadow-[0_0_8px_rgba(245,158,11,0.8)]"></div>
                                    </div>
                                </label>
                                <div class="grid grid-cols-1 gap-3">
                                    <button
                                        v-for="e in [
                                            { id: 'abierto', label: 'Abierto', icon: '🆕', color: 'blue' },
                                            { id: 'en_progreso', label: 'En Progreso', icon: '⚡', color: 'amber' },
                                            { id: 'pendiente', label: 'Pendiente', icon: '⏳', color: 'orange' },
                                            { id: 'resuelto', label: 'Resuelto', icon: '✅', color: 'emerald' },
                                            { id: 'cerrado', label: 'Cerrado', icon: '🔒', color: 'slate' }
                                        ]"
                                        :key="e.id"
                                        @click="cambiarEstado(e.id)"
                                        :class="[
                                            'w-full px-5 py-4 rounded-[1.25rem] text-left transition-all duration-500 border flex items-center justify-between group relative overflow-hidden',
                                            ticket.estado === e.id 
                                                ? `bg-${e.color}-500 text-white border-${e.color}-400 shadow-[0_15px_30px_-10px_rgba(0,0,0,0.3)] ring-4 ring-${e.color}-500/20 scale-[1.03] z-10` 
                                                : 'bg-slate-50 dark:bg-slate-950/40 border-slate-200 dark:border-white/5 text-slate-500 dark:text-slate-400 hover:border-brand-500/40 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-950'
                                        ]"
                                    >
                                        <div class="flex items-center gap-4">
                                            <span class="text-xl filter group-hover:brightness-125 transition-all">{{ e.icon }}</span>
                                            <span class="font-black text-[11px] uppercase tracking-wide">{{ e.label }}</span>
                                        </div>
                                        <div v-if="ticket.estado === e.id" class="w-2 h-2 rounded-full bg-white animate-pulse shadow-[0_0_8px_rgba(255,255,255,1)]"></div>
                                        <div v-else class="opacity-0 group-hover:opacity-100 transition-all -translate-x-2 group-hover:translate-x-0">
                                            <span class="text-[8px] font-black uppercase tracking-wide">Update →</span>
                                        </div>
                                    </button>
                                </div>
                             </div>

                             <!-- Assignment Select -->
                             <div class="p-6 bg-[var(--ui-surface)] dark:bg-slate-950/50 rounded-2xl border border-slate-200 dark:border-white/5">
                                 <label class="block text-[9px] uppercase tracking-[0.2em] font-black text-slate-500 mb-4 px-1 italic">Asignación de Unidad</label>
                                 <div class="relative group">
                                      <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-brand-500/50">
                                         <svg class="w-4 h-4 group-hover:text-brand-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                     </div>
                                     <select 
                                         :value="ticket.asignado_id" 
                                         @change="asignarA($event.target.value)"
                                         class="w-full px-12 py-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/5 rounded-2xl text-[10px] font-black uppercase tracking-wide text-slate-800 dark:text-white shadow-inner focus:ring-2 focus:ring-brand-500/30 transition-all appearance-none cursor-pointer"
                                     >
                                         <option value="">SIN ENTIDAD RESPONSABLE</option>
                                         <option v-for="u in usuarios" :key="u.id" :value="u.id">{{ u.name }}</option>
                                     </select>
                                     <svg class="absolute right-4 top-4 w-4 h-4 text-slate-400 dark:text-slate-500 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
                                 </div>
                             </div>
                        </div>

                        <!-- Technical Matrix Information -->
                        <div class="bg-white dark:bg-slate-800/50 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-[3rem] p-10 shadow-md dark:shadow-2xl relative group">
                            <h3 class="text-[10px] font-black text-slate-800 dark:text-white uppercase tracking-[0.4em] mb-8 flex items-center gap-4">
                                <span class="text-brand-500 text-lg">ℹ️</span>
                                Matriz Técnica
                            </h3>
                            <div class="space-y-6">
                                <div v-for="item in [
                                    { k: 'Categoría', v: ticket.categoria?.nombre || '-' },
                                    { k: 'Origen', v: ticket.origen },
                                    { k: 'Autor', v: ticket.creador?.name },
                                    { k: 'Límite SLA', v: formatDate(ticket.fecha_limite), cond: ticket.sla_status }
                                ]" :key="item.k" class="flex justify-between items-center py-3 border-b border-slate-100 dark:border-white/5 group/row">
                                    <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide group-hover/row:text-slate-500 dark:group-hover/row:text-slate-400 transition-colors">{{ item.k }}</span>
                                    <span :class="['text-[11px] font-black uppercase tracking-wide truncate max-w-[150px]', 
                                        item.cond === 'vencido' ? 'text-rose-500 brightness-125' : 
                                        item.cond === 'critico' ? 'text-orange-500' : 'text-slate-700 dark:text-slate-200']">
                                        {{ item.v }}
                                    </span>
                                </div>
                                
                                <div v-if="ticket.horas_trabajadas" class="mt-8 p-6 bg-brand-500/5 border border-brand-500/20 rounded-3xl group/duration overflow-hidden relative">
                                    <div class="absolute inset-y-0 left-0 w-1 bg-brand-500 group-hover:w-full transition-all duration-700 opacity-20"></div>
                                    <div class="relative flex justify-between items-center">
                                         <div class="flex flex-col">
                                            <span class="text-[9px] font-black text-brand-500 uppercase tracking-[0.2em] mb-1 italic">Esfuerzo Acumulado</span>
                                            <span class="text-2xl font-black text-slate-800 dark:text-white tracking-tighter">{{ ticket.horas_trabajadas }} <span class="text-xs text-brand-500">HRS</span></span>
                                         </div>
                                         <div class="w-10 h-10 rounded-2xl bg-brand-500/10 flex items-center justify-center text-brand-500 shadow-xl group-hover:scale-105 transition-transform">
                                            <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                         </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Policy Safeguard (If Active) -->
                        <div v-if="ticket.poliza" class="relative group overflow-hidden">
                             <div class="absolute -inset-0.5 bg-gradient-to-br from-emerald-500/40 to-transparent rounded-[3rem] blur opacity-10 group-hover:opacity-30 transition duration-700"></div>
                             <div class="relative bg-white dark:bg-slate-800/50 backdrop-blur-xl border border-emerald-500/20 rounded-[3rem] p-10 shadow-md dark:shadow-2xl">
                                <div class="flex items-center gap-5 mb-8">
                                    <div class="w-14 h-14 rounded-2xl bg-brand-500/10 flex items-center justify-center text-emerald-500 dark:text-slate-400 border border-emerald-500/30 shadow-2xl">
                                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                    </div>
                                    <div>
                                        <h3 class="text-xs font-black text-emerald-600 dark:text-slate-400 uppercase tracking-[0.4em] mb-1">Protección Activa</h3>
                                        <div class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide italic">NIVEL: {{ ticket.poliza.folio }}</div>
                                    </div>
                                </div>
                                <div class="bg-slate-50 dark:bg-black/50 p-6 rounded-2xl border border-slate-200 dark:border-white/5 mb-6">
                                    <div class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-wider mb-2">{{ ticket.poliza.nombre }}</div>
                                    <div class="flex justify-between items-center text-[10px] font-black text-slate-500 uppercase tracking-wide italic">
                                        <span>CONSUMO GLOBAL</span>
                                        <span class="text-emerald-600 dark:text-slate-400">{{ ticket.poliza.tickets_mes_actual_count }} / {{ ticket.poliza.limite_mensual_tickets || '∞' }} TKS</span>
                                    </div>
                                    <div class="h-1.5 w-full bg-slate-200 dark:bg-slate-950 rounded-full mt-3 overflow-hidden p-0.5 border border-slate-300 dark:border-white/5">
                                        <div class="h-full bg-brand-500 rounded-full" :style="{ width: Math.min(((ticket.poliza.tickets_mes_actual_count || 0) / (ticket.poliza.limite_mensual_tickets || 1)) * 100, 100) + '%' }"></div>
                                    </div>
                                </div>
                                <Link :href="route('polizas-servicio.show', ticket.poliza.id)" class="w-full py-4 bg-brand-500/10 hover:bg-slate-500 text-emerald-600 dark:text-emerald-500 hover:text-white text-[9px] font-black uppercase tracking-wide rounded-2xl transition-all flex items-center justify-center gap-3 active:scale-95">
                                    ENTRAR A LA BÓVEDA DE PÓLIZA
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                </Link>
                             </div>
                        </div>

                        <!-- Client Identity Card -->
                        <div v-if="ticket.cliente" class="bg-white dark:bg-slate-800/50 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-[3rem] p-10 shadow-md dark:shadow-2xl relative group overflow-hidden">
                             <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-brand-500/5 rounded-full blur-[60px]"></div>
                             <h3 class="text-[10px] font-black text-slate-800 dark:text-white uppercase tracking-[0.4em] mb-10 flex items-center gap-4">
                                <span class="text-brand-500 text-lg">👤</span>
                                Identidad Cliente
                             </h3>
                             <div class="flex flex-col gap-6">
                                <div class="p-6 bg-[var(--ui-surface)] border border-slate-200 dark:border-white/5 rounded-3xl shadow-inner group/card hover:border-brand-500/20 transition-all duration-500">
                                    <div class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-wider mb-4 leading-none group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors">{{ ticket.cliente.nombre }}</div>
                                    <div class="space-y-3">
                                        <div class="text-[10px] font-black text-slate-500 uppercase tracking-wide flex items-center gap-2">
                                            <svg class="w-4 h-4 text-brand-500/50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                            {{ ticket.cliente.email }}
                                        </div>
                                        <div class="text-[10px] font-black text-slate-500 uppercase tracking-wide flex items-center gap-2">
                                            <svg class="w-4 h-4 text-brand-500/50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                            {{ ticket.cliente.telefono || ticket.cliente.celular }}
                                        </div>
                                    </div>
                                </div>
                                <Link :href="route('clientes.show', ticket.cliente.id)" class="w-full py-5 flex items-center justify-center bg-[var(--ui-surface)] border border-slate-200 dark:border-white/5 hover:border-brand-500 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white rounded-[1.5rem] text-[10px] font-black uppercase tracking-wide transition-all shadow-xl group/btn">
                                    Ficha Completa de Cliente
                                    <svg class="w-4 h-4 ml-3 transform group-hover/btn:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                </Link>
                             </div>
                        </div>

                        <!-- Product Artifact Index -->
                        <div v-if="ticket.producto" class="bg-white dark:bg-slate-800/50 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-[3rem] p-10 shadow-md dark:shadow-2xl relative group overflow-hidden">
                             <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-indigo-500/5 rounded-full blur-[60px]"></div>
                             <h3 class="text-[10px] font-black text-slate-800 dark:text-white uppercase tracking-[0.4em] mb-10 flex items-center gap-4">
                                <span class="text-brand-500 text-lg">📦</span>
                                Unidades Afectadas
                             </h3>
                             <div class="p-6 bg-[var(--ui-surface)] border border-slate-200 dark:border-white/5 rounded-3xl group/card relative overflow-hidden">
                                <div class="absolute inset-y-0 right-0 w-1 bg-indigo-500 opacity-20"></div>
                                <div class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-wider mb-2 italic">{{ ticket.producto.nombre }}</div>
                                <div class="text-[9px] font-mono text-indigo-600 dark:text-indigo-400 font-bold uppercase tracking-wide">REGISTRO SKU: {{ ticket.producto.sku || 'N/A' }}</div>
                             </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rapid Resolution Modal Premium -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 translate-y-4"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-4"
            >
                <div v-if="showHorasModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-xl">
                    <!-- Overlay -->
                    <div class="fixed inset-0" @click="cancelarConsumoHoras"></div>
                    
                    <!-- Modal Card -->
                    <div class="relative bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 rounded-[3.5rem] shadow-[0_50px_100px_-20px_rgba(0,0,0,0.5)] w-full max-w-lg overflow-hidden animate-in zoom-in-95 duration-200">
                        
                        <!-- Header with Gradient Area -->
                        <div :class="[
                            'px-10 py-12 text-center relative overflow-hidden',
                            estadoPendiente === 'cerrado' 
                                ? 'bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-950 border-b border-slate-200 dark:border-white/5' 
                                : 'bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-600/20 dark:to-emerald-950/40 border-b border-emerald-200 dark:border-emerald-800/30 dark:border-emerald-500/20'
                        ]">
                            <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/5 rounded-full blur-3xl"></div>
                            <div class="relative w-16 h-16 mx-auto bg-white dark:bg-slate-950/50 backdrop-blur border border-slate-200 dark:border-white/10 rounded-3xl flex items-center justify-center mb-6 shadow-xl dark:shadow-2xl">
                                <span class="text-5xl drop-shadow-xl">{{ estadoPendiente === 'cerrado' ? '🔒' : '🎯' }}</span>
                            </div>
                            <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-wide">
                                {{ estadoPendiente === 'cerrado' ? 'Sellar Expediente' : 'Reportar Resolución' }}
                            </h3>
                            <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.3em] mt-3 italic tracking-wide">IDENTIFICADOR: {{ ticket.numero }}</p>
                        </div>

                        <!-- Content Form -->
                        <div class="p-10 space-y-10">
                            
                            <!-- Esfuerzo Selector -->
                            <div>
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wide mb-6 px-1 italic">Detección de Esfuerzo Operacional</label>
                                
                                <div class="grid grid-cols-4 gap-3 mb-6">
                                    <button 
                                        v-for="t in [0.5, 1, 1.5, 2]" 
                                        :key="t"
                                        type="button"
                                        @click="horasTrabajadas = t"
                                        :class="[
                                            'py-4 rounded-2xl text-[10px] font-black uppercase tracking-wide border transition-all duration-200 active:scale-90 shadow-xl',
                                            parseFloat(horasTrabajadas) === t 
                                                ? 'bg-brand-600 text-white border-brand-500 shadow-brand-600/30' 
                                                : 'bg-slate-50 dark:bg-slate-950/50 border-slate-200 dark:border-white/5 text-slate-400 dark:text-slate-500 hover:border-brand-500/30 dark:hover:text-white'
                                        ]"
                                    >
                                        {{ t }}H
                                    </button>
                                </div>
                                
                                <div class="relative group">
                                    <input 
                                        v-model="horasTrabajadas" 
                                        type="number" 
                                        step="0.25" 
                                        min="0.25" 
                                        placeholder="INPUT MANUAL..."
                                        class="w-full bg-[var(--ui-surface)] dark:bg-slate-950/80 text-center text-2xl font-black text-slate-900 dark:text-white py-5 border border-slate-200 dark:border-white/5 rounded-3xl focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all uppercase tracking-wide shadow-inner"
                                    />
                                    <span class="absolute right-6 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide italic tracking-[0.2em] pointer-events-none">HORAS-HOMBRE</span>
                                </div>
                            </div>

                            <!-- Policy Logic -->
                            <div v-if="ticket.poliza" class="pt-10 border-t border-slate-200 dark:border-white/5">
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wide mb-6 px-1 italic">Atribución de Servicio</label>
                                <div class="grid grid-cols-2 gap-4">
                                    <button 
                                        type="button"
                                        @click="tipoServicio = 'garantia'"
                                        :class="[
                                            'relative p-6 rounded-3xl border text-left transition-all duration-500 group overflow-hidden',
                                            tipoServicio === 'garantia' 
                                                ? 'bg-brand-500/10 border-emerald-500 shadow-xl scale-[1.02]' 
                                                : 'bg-slate-50 dark:bg-slate-950/50 border-slate-200 dark:border-white/5 grayscale hover:grayscale-0 hover:border-brand-500/30'
                                        ]"
                                    >
                                        <div class="absolute -right-4 -bottom-4 text-6xl opacity-10 transform group-hover:rotate-12 transition-transform">🛡️</div>
                                        <div class="text-[10px] font-black uppercase tracking-wide mb-1" :class="tipoServicio === 'garantia' ? 'text-emerald-600 dark:text-slate-400' : 'text-slate-500'">Bajo Póliza</div>
                                        <p class="text-[8px] font-bold text-slate-500 leading-tight uppercase tracking-wide italic">Consume tiempo del contrato activo.</p>
                                    </button>
                                    <button 
                                        type="button"
                                        @click="tipoServicio = 'costo'"
                                        :class="[
                                            'relative p-6 rounded-3xl border text-left transition-all duration-500 group overflow-hidden',
                                            tipoServicio === 'costo' 
                                                ? 'bg-indigo-500/10 border-indigo-500 shadow-xl scale-[1.02]' 
                                                : 'bg-slate-50 dark:bg-slate-950/50 border-slate-200 dark:border-white/5 grayscale hover:grayscale-0 hover:border-brand-500/30'
                                        ]"
                                    >
                                        <div class="absolute -right-4 -bottom-4 text-6xl opacity-10 transform group-hover:rotate-12 transition-transform">💰</div>
                                        <div class="text-[10px] font-black uppercase tracking-wide mb-1" :class="tipoServicio === 'costo' ? 'text-indigo-400' : 'text-slate-500'">Con Cargo</div>
                                        <p class="text-[8px] font-bold text-slate-500 leading-tight uppercase tracking-wide italic">Servicio adicional fuera de plan.</p>
                                    </button>
                                </div>
                            </div>

                            <!-- Sin póliza: info de costo automático -->
                            <div v-if="!ticket.poliza && !ticket.venta_id" class="pt-6">
                                <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-[10px] text-amber-700 dark:text-amber-300 font-medium mb-6">
                                    ⚡ Cliente sin póliza activa — el servicio se registrará como <strong>Con Cargo</strong> y se generará una venta automáticamente al cerrar el ticket.
                                </div>
                            </div>

                            <!-- Auto-Sales Check -->
                            <div v-if="!ticket.poliza && !ticket.venta_id" class="border-t border-slate-200 dark:border-white/5 pt-10">
                                <label 
                                    class="flex items-center gap-6 p-6 rounded-[2rem] border cursor-pointer transition-all duration-500 group relative overflow-hidden"
                                    :class="generarVentaAlCerrar ? 'bg-indigo-500/10 border-indigo-500/50' : 'bg-slate-50 dark:bg-slate-950/50 border-slate-200 dark:border-white/5 hover:border-brand-500'"
                                >
                                     <div v-if="generarVentaAlCerrar" class="absolute inset-y-0 right-0 w-1 bg-indigo-500"></div>
                                     <div class="relative w-10 h-10 flex items-center justify-center">
                                        <input 
                                            type="checkbox" 
                                            v-model="generarVentaAlCerrar"
                                            class="w-10 h-10 rounded-xl bg-[var(--ui-surface)] border-slate-200 dark:border-white/10 text-indigo-500 focus:ring-2 focus:ring-brand-500/20 transition-all cursor-pointer"
                                        >
                                     </div>
                                     <div class="flex-1">
                                        <div class="text-[10px] font-black text-slate-900 dark:text-white uppercase tracking-wide mb-1">Generar Registro Comercial</div>
                                        <p class="text-[8px] font-bold text-slate-500 uppercase tracking-wide italic leading-tight">Crea automáticamente una nota de venta para facturación orbital.</p>
                                     </div>
                                </label>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="px-10 pb-12 flex gap-4">
                            <button 
                                @click="cancelarConsumoHoras" 
                                class="flex-1 h-16 bg-[var(--ui-surface)] border border-slate-200 dark:border-white/5 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-500 rounded-3xl text-[10px] font-black uppercase tracking-wide transition-all"
                            >
                                Abortar
                            </button>
                            <button 
                                @click="confirmarConsumoHoras" 
                                :disabled="!horasTrabajadas || parseFloat(horasTrabajadas) <= 0"
                                :class="[
                                    'flex-[2] h-16 rounded-3xl font-black text-[10px] uppercase tracking-[0.2em] transition-all shadow-2xl flex items-center justify-center gap-3 active:scale-95 disabled:opacity-30 disabled:grayscale',
                                    estadoPendiente === 'cerrado' 
                                        ? 'bg-slate-700 hover:bg-slate-600 text-white' 
                                        : 'bg-emerald-600 hover:bg-emerald-700 text-white'
                                ]"
                            >
                                <span v-if="!horasTrabajadas">ESPERANDO EFECTO...</span>
                                <span v-else class="flex items-center gap-2">
                                    <svg class="w-4 h-4 shadow-emerald-500/50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                    {{ estadoPendiente === 'resuelto' ? 'Confirmar Resolución' : 'Clausurar Entidad' }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.02);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.1);
}
</style>
