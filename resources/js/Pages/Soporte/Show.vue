<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    ticket: Object,
    historialCliente: Array,
    categorias: Array,
    usuarios: Array,
    canDelete: Boolean, // Permiso de super-admin para eliminar
});

const nuevoComentario = useForm({
    contenido: '',
    es_interno: false,
});

const agregarComentario = () => {
    nuevoComentario.post(route('soporte.comentario', props.ticket.id), {
        preserveScroll: true,
        onSuccess: () => {
            nuevoComentario.reset();
        },
    });
};

// Phase 2: Estado y modal para horas
const showHorasModal = ref(false);
const estadoPendiente = ref('');
const horasTrabajadas = ref('');
const servicioInicio = ref('');
const servicioFin = ref('');
const tipoServicio = ref(props.ticket.tipo_servicio || 'garantia');
const generarVentaAlCerrar = ref(!props.ticket.poliza && !props.ticket.venta_id); // Por defecto true si no tiene póliza

import { watch } from 'vue';
import {  differenceInMinutes, parseISO } from 'date-fns';

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
    // SIEMPRE pedir horas al resolver o cerrar para llevar registro de horas-hombre
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
    
    // Enviar cambio de estado con flag de generar venta
    const datos = { 
        estado: estadoPendiente.value,
        generar_venta: debeGenerarVenta 
    };
    if (horas !== null) datos.horas_trabajadas = horas;
    if (servicioInicio.value) datos.servicio_inicio_at = servicioInicio.value;
    if (servicioFin.value) datos.servicio_fin_at = servicioFin.value;
    if (tipoServicio.value) datos.tipo_servicio = tipoServicio.value;
    
    router.post(route('soporte.cambiar-estado', props.ticket.id), datos, { preserveScroll: true });
    
    // Reset y cerrar
    showHorasModal.value = false;
    horasTrabajadas.value = '';
    servicioInicio.value = '';
    servicioFin.value = '';
    estadoPendiente.value = '';
    generarVentaAlCerrar.value = false;
};

const cancelarConsumoHoras = () => {
    // Cancelar la operación de cambio de estado
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

const generarVenta = () => {
    if (confirm('¿Generar una nota de venta para este ticket?')) {
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

const eliminarTicket = () => {
    if (confirm(`¿Estás seguro de eliminar el ticket ${props.ticket.numero}?\n\nEsta acción no se puede deshacer.`)) {
        router.delete(route('soporte.destroy', props.ticket.id));
    }
};

const estados = ['abierto', 'en_progreso', 'pendiente', 'resuelto', 'cerrado'];

const getEstadoBadge = (estado) => {
    const colores = {
        abierto: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-200 dark:border-blue-900/50',
        en_progreso: 'bg-yellow-500/10 text-yellow-600 dark:text-yellow-400 border-yellow-200 dark:border-yellow-900/50',
        pendiente: 'bg-orange-500/10 text-orange-600 dark:text-orange-400 border-orange-200 dark:border-orange-900/50',
        resuelto: 'bg-green-500/10 text-green-600 dark:text-green-400 border-green-200 dark:border-green-900/50',
        cerrado: 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800',
    };
    return colores[estado] || 'bg-slate-100 text-slate-800 dark:text-slate-100';
};

const getPrioridadBadge = (prioridad) => {
    const colores = {
        urgente: 'bg-red-500/10 text-red-600 dark:text-red-400 border-red-200 dark:border-red-900/50',
        alta: 'bg-orange-500/10 text-orange-600 dark:text-orange-400 border-orange-200 dark:border-orange-900/50',
        media: 'bg-yellow-500/10 text-yellow-600 dark:text-yellow-400 border-yellow-200 dark:border-yellow-900/50',
        baja: 'bg-green-500/10 text-green-600 dark:text-green-400 border-green-200 dark:border-green-900/50',
    };
    return colores[prioridad] || 'bg-slate-100 text-slate-600 border-slate-200';
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

        <div class="py-6">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <Link :href="route('soporte.index')" class="text-orange-600 hover:text-orange-800 text-sm mb-2 inline-block">
                        ← Volver a tickets
                    </Link>
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <div class="flex items-center gap-3">
                                <span class="font-mono text-lg text-orange-600">{{ ticket.numero }}</span>
                                <span :class="['px-3 py-1 text-xs font-black uppercase tracking-widest rounded-full border shadow-sm transition-all', getEstadoBadge(ticket.estado)]">
                                    {{ ticket.estado.replace('_', ' ') }}
                                </span>
                                <span :class="['px-3 py-1 text-xs font-black uppercase tracking-widest rounded-full border shadow-sm transition-all', getPrioridadBadge(ticket.prioridad)]">
                                    {{ ticket.prioridad?.toUpperCase() || 'NORMAL' }}
                                </span>
                                <span v-if="ticket.tipo_servicio === 'costo'" class="px-3 py-1 text-xs font-black uppercase tracking-widest rounded-full bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-200 dark:border-purple-900/50 shadow-sm">
                                    💰 CON COSTO
                                </span>
                                <span v-else class="px-3 py-1 text-xs font-black uppercase tracking-widest rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/50 shadow-sm">
                                    🛡️ GARANTÍA
                                </span>
                            </div>
                            <h1 class="text-3xl font-black text-gray-900 dark:text-white mt-3 tracking-tight">{{ ticket.titulo }}</h1>
                        </div>
                        
                        <!-- Acciones Principales -->
                        <div class="flex gap-2 flex-wrap">
                             <button 
                                v-if="ticket.tipo_servicio === 'costo' && !ticket.venta_id"
                                @click="generarVenta"
                                class="px-4 py-2 bg-purple-600 text-white rounded-lg shadow hover:bg-purple-700 font-bold flex items-center gap-2"
                            >
                                <span>🛒</span> Generar Venta
                            </button>
                             <button 
                                @click="agendarCita"
                                class="px-4 py-2 bg-orange-600 text-white rounded-lg shadow hover:bg-orange-700 font-bold flex items-center gap-2"
                            >
                                <span>📅</span> Agendar Cita
                            </button>
                             <Link 
                                v-if="ticket.venta_id"
                                :href="route('ventas.edit', ticket.venta_id)"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg shadow hover:bg-green-700 font-bold flex items-center gap-2"
                            >
                                <span>✅</span> Ir a Venta
                            </Link>
                            <!-- Botón Eliminar (solo super-admin) -->
                            <button 
                                v-if="canDelete"
                                @click="eliminarTicket"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg shadow hover:bg-red-700 font-bold flex items-center gap-2"
                            >
                                <span>🗑️</span> Eliminar
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Contenido principal -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Descripción -->
                        <div class="bg-white dark:bg-slate-900/50 backdrop-blur-sm border border-gray-100 dark:border-slate-800 rounded-2xl shadow-sm p-6">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <span class="text-orange-500 text-xl font-normal">📝</span>
                                Descripción
                            </h3>
                            <div class="bg-gray-50 dark:bg-slate-950/50 p-4 rounded-xl border border-gray-100 dark:border-slate-800/50">
                                <p class="text-gray-700 dark:text-slate-300 whitespace-pre-wrap leading-relaxed">{{ ticket.description || ticket.descripcion }}</p>
                            </div>
                        </div>

                        <!-- Timeline de comentarios -->
                        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Conversación</h3>
                            
                            <div class="space-y-4">
                                <div 
                                    v-for="comentario in ticket.comentarios" 
                                    :key="comentario.id"
                                    :class="[
                                        'p-5 rounded-2xl transition-all duration-300',
                                        comentario.es_interno 
                                            ? 'bg-yellow-50 dark:bg-yellow-900/10 border border-yellow-200 dark:border-yellow-700/30' 
                                            : 'bg-white dark:bg-slate-900/50 border border-gray-100 dark:border-slate-800/50'
                                    ]"
                                >
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white text-sm font-bold shadow-sm">
                                                {{ comentario.user?.name?.charAt(0) || '?' }}
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-gray-900 dark:text-white">{{ comentario.user?.name }}</span>
                                                    <span v-if="comentario.es_interno" class="text-[10px] uppercase tracking-wider font-extrabold text-yellow-700 dark:text-yellow-500 bg-yellow-200/50 dark:bg-yellow-500/10 px-2 py-0.5 rounded-full border border-yellow-300/30 dark:border-yellow-500/20">
                                                        Nota interna
                                                    </span>
                                                    <span v-if="comentario.tipo === 'estado'" class="text-[10px] uppercase tracking-wider font-extrabold text-blue-700 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/30 px-2 py-0.5 rounded-full border border-blue-200/30 dark:border-blue-400/20">
                                                        Cambio de estado
                                                    </span>
                                                </div>
                                                <div class="text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-widest mt-0.5">
                                                    {{ formatDate(comentario.created_at) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="pl-13">
                                        <p class="text-gray-700 dark:text-slate-300 whitespace-pre-wrap leading-relaxed">{{ comentario.contenido }}</p>
                                    </div>
                                </div>

                                <div v-if="ticket.comentarios.length === 0" class="text-center py-8 text-gray-400">
                                    No hay comentarios aún
                                </div>
                            </div>

                            <form @submit.prevent="agregarComentario" class="mt-8 pt-6 border-t border-gray-100 dark:border-slate-800">
                                <div class="relative group">
                                    <textarea
                                        v-model="nuevoComentario.contenido"
                                        rows="3"
                                        placeholder="Escribe una respuesta técnica o nota..."
                                        class="w-full px-5 py-4 bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 rounded-2xl focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition-all resize-none text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-600"
                                    ></textarea>
                                </div>
                                <div class="flex justify-between items-center mt-3">
                                    <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                        <input type="checkbox" v-model="nuevoComentario.es_interno" class="rounded text-yellow-500" />
                                        Nota interna (no visible para cliente)
                                    </label>
                                    <button 
                                        type="submit"
                                        :disabled="!nuevoComentario.contenido || nuevoComentario.processing"
                                        class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 disabled:opacity-50"
                                    >
                                        Enviar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="lg:col-span-1 space-y-6">
                        <!-- Acciones rápidas -->
                        <div class="bg-white dark:bg-slate-900/50 backdrop-blur-sm border border-gray-100 dark:border-slate-800 rounded-2xl shadow-sm p-6">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-5 flex items-center gap-2">
                                <span class="text-orange-500">⚡</span>
                                Gestión del Ticket
                            </h3>
                            
                            <!-- Cambiar estado -->
                            <div class="mb-6">
                                <label class="block text-[10px] uppercase tracking-widest font-extrabold text-gray-500 dark:text-slate-400 mb-4 flex justify-between items-center">
                                    <span>Estado Operativo</span>
                                    <span class="w-1 h-1 rounded-full bg-orange-500 animate-pulse"></span>
                                </label>
                                <div class="grid grid-cols-1 gap-2">
                                    <button
                                        v-for="e in [
                                            { id: 'abierto', label: 'Abierto', icon: '🆕', color: 'blue' },
                                            { id: 'en_progreso', label: 'En Progreso', icon: '⚡', color: 'yellow' },
                                            { id: 'pendiente', label: 'Pendiente', icon: '⏳', color: 'orange' },
                                            { id: 'resuelto', label: 'Resuelto', icon: '✅', color: 'green' },
                                            { id: 'cerrado', label: 'Cerrado', icon: '🔒', color: 'slate' }
                                        ]"
                                        :key="e.id"
                                        @click="cambiarEstado(e.id)"
                                        :class="[
                                            'w-full px-4 py-3 rounded-xl text-left transition-all duration-300 border flex items-center justify-between group relative overflow-hidden',
                                            ticket.estado === e.id 
                                                ? `bg-${e.color}-500 dark:bg-${e.color}-600 border-${e.color}-400 dark:border-${e.color}-500 text-white shadow-lg shadow-${e.color}-500/20 scale-[1.02] z-10` 
                                                : 'bg-gray-50 dark:bg-slate-950/50 border-gray-100 dark:border-slate-800 text-gray-700 dark:text-slate-300 hover:border-orange-500/50 hover:bg-orange-50/10 dark:hover:bg-orange-500/5'
                                        ]"
                                    >
                                        <div class="flex items-center gap-3">
                                            <span class="text-lg transition-transform group-hover:scale-110" v-html="e.icon"></span>
                                            <span class="font-bold text-[13px] uppercase tracking-tight">{{ e.label }}</span>
                                        </div>
                                        <div v-if="ticket.estado === e.id" class="flex items-center">
                                            <div class="w-2 h-2 rounded-full bg-white animate-pulse"></div>
                                        </div>
                                        <div v-else class="opacity-0 group-hover:opacity-100 transition-opacity">
                                            <span class="text-[10px] font-black uppercase">Cambiar →</span>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <!-- Asignar -->
                            <div>
                                <label class="block text-[10px] uppercase tracking-widest font-extrabold text-gray-500 dark:text-slate-400 mb-3">Asignado a</label>
                                <select 
                                    :value="ticket.asignado_id" 
                                    @change="asignarA($event.target.value)"
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 rounded-xl text-sm font-medium text-gray-900 dark:text-white focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition-all cursor-pointer"
                                >
                                    <option value="">👤 Sin asignar</option>
                                    <option v-for="u in usuarios" :key="u.id" :value="u.id">{{ u.name }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Detalles del ticket -->
                        <div class="bg-white dark:bg-slate-900/50 backdrop-blur-sm border border-gray-100 dark:border-slate-800 rounded-2xl shadow-sm p-6 text-[13px]">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <span class="text-orange-500 text-lg">ℹ️</span>
                                Información Técnica
                            </h3>
                            <dl class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Categoría</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ ticket.categoria?.nombre || '-' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Origen</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ ticket.origen }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Creado</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ formatDate(ticket.created_at) }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Por</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ ticket.creador?.name }}</dd>
                                </div>
                                <div v-if="ticket.fecha_limite" class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">SLA Límite</dt>
                                    <dd :class="ticket.sla_status === 'vencido' ? 'text-red-600 font-bold' : ticket.sla_status === 'critico' ? 'text-orange-600' : 'text-gray-900 dark:text-white'">
                                        {{ formatDate(ticket.fecha_limite) }}
                                    </dd>
                                </div>
                                <div v-if="ticket.resuelto_at" class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Resuelto</dt>
                                    <dd class="text-green-600">{{ formatDate(ticket.resuelto_at) }}</dd>
                                </div>
                                <div v-if="ticket.servicio_inicio_at" class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Inicio Servicio</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ formatDate(ticket.servicio_inicio_at) }}</dd>
                                </div>
                                <div v-if="ticket.servicio_fin_at" class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Fin Servicio</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ formatDate(ticket.servicio_fin_at) }}</dd>
                                </div>
                                <div v-if="ticket.horas_trabajadas" class="flex justify-between bg-orange-500/10 dark:bg-orange-500/20 -mx-3 px-3 py-2 rounded-xl mt-3 border border-orange-500/20">
                                    <dt class="text-orange-600 dark:text-orange-400 font-bold uppercase tracking-widest text-[10px] flex items-center gap-1">
                                        <span>⏱️</span> Duración Total
                                    </dt>
                                    <dd class="text-orange-700 dark:text-orange-300 font-black text-sm">{{ ticket.horas_trabajadas }} hrs</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Citas Relacionadas -->
                        <div v-if="ticket.citas && ticket.citas.length > 0" class="bg-blue-500/5 dark:bg-blue-500/10 backdrop-blur-sm rounded-2xl shadow-sm p-6 border border-blue-200 dark:border-blue-500/20">
                             <h3 class="font-bold text-blue-900 dark:text-blue-300 mb-4 flex items-center gap-2">
                                 <span class="text-xl">📅</span>
                                 Citas del Servicio
                             </h3>
                             <div class="space-y-4">
                                 <div v-for="cita in ticket.citas" :key="cita.id" class="text-sm bg-white/80 dark:bg-slate-950/50 p-4 rounded-xl border border-blue-100 dark:border-blue-500/20 shadow-sm transition-all hover:shadow-md">
                                     <div class="flex justify-between items-start mb-2">
                                         <Link :href="route('citas.show', cita.id)" class="font-black text-blue-700 dark:text-blue-400 hover:underline">
                                             {{ cita.folio }}
                                         </Link>
                                         <span :class="['text-[10px] px-2 py-0.5 rounded-full font-black uppercase tracking-wider shadow-sm border', 
                                            cita.estado === 'completado' ? 'bg-green-500/10 text-green-600 border-green-200 dark:border-green-900/30' :
                                            cita.estado === 'cancelado' ? 'bg-red-500/10 text-red-600 border-red-200 dark:border-red-900/30' :
                                            'bg-blue-500/10 text-blue-600 border-blue-200 dark:border-blue-900/30'
                                         ]">
                                             {{ cita.estado }}
                                         </span>
                                     </div>
                                     <div class="text-[11px] text-gray-500 dark:text-slate-400 font-medium flex items-center gap-1">
                                         <span>🕒</span> {{ formatDate(cita.fecha_hora) }}
                                     </div>
                                 </div>
                             </div>
                        </div>

                        <!-- Info de Póliza -->
                        <div v-if="ticket.poliza" class="bg-emerald-500/5 dark:bg-emerald-500/10 backdrop-blur-sm rounded-2xl shadow-sm p-6 border border-emerald-200 dark:border-emerald-500/20">
                             <h3 class="font-bold text-emerald-900 dark:text-emerald-300 mb-4 flex items-center gap-2">
                                 <span class="text-xl">🛡️</span>
                                 Póliza de Servicio
                             </h3>
                             <div class="p-4 bg-white/80 dark:bg-slate-950/50 rounded-xl border border-emerald-100 dark:border-emerald-500/20 shadow-sm">
                                 <div class="font-black text-emerald-800 dark:text-emerald-400 text-sm">{{ ticket.poliza.nombre }}</div>
                                 <div class="text-emerald-600 dark:text-emerald-500/80 font-mono text-[10px] mt-1 tracking-wider uppercase">Folio: {{ ticket.poliza.folio }}</div>
                                 <div class="mt-3 pt-3 border-t border-emerald-100/50 dark:border-emerald-500/10 flex items-center justify-between">
                                     <span class="text-[11px] text-emerald-700 dark:text-emerald-500/70 font-bold uppercase tracking-tighter">Consumos mes</span>
                                     <span class="text-[11px] font-black text-emerald-900 dark:text-emerald-300">
                                         {{ ticket.poliza.tickets_mes_actual_count }} / {{ ticket.poliza.limite_mensual_tickets || '∞' }}
                                     </span>
                                 </div>
                                 <Link :href="route('polizas-servicio.show', ticket.poliza.id)" class="w-full mt-4 py-2 flex items-center justify-center bg-emerald-500/10 dark:bg-emerald-500/20 hover:bg-emerald-500 dark:hover:bg-emerald-600 text-emerald-700 dark:text-emerald-400 hover:text-white dark:hover:text-white rounded-lg text-[10px] font-black uppercase tracking-widest transition-all">
                                     Detalles →
                                 </Link>
                             </div>
                        </div>

                        <!-- Info del cliente -->
                        <div v-if="ticket.cliente" class="bg-white dark:bg-slate-900/50 backdrop-blur-sm border border-gray-100 dark:border-slate-800 rounded-2xl shadow-sm p-6">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <span class="text-orange-500 text-lg">👤</span>
                                Cliente
                            </h3>
                            <div class="space-y-3">
                                <div class="p-3 bg-gray-50 dark:bg-slate-950/50 rounded-xl border border-gray-100 dark:border-slate-800/50">
                                    <div class="font-bold text-gray-900 dark:text-white text-sm">{{ ticket.cliente.nombre }}</div>
                                    <div class="text-gray-500 dark:text-gray-400 text-xs mt-1 flex items-center gap-1">
                                        <span>📧</span> {{ ticket.cliente.email }}
                                    </div>
                                    <div class="text-gray-500 dark:text-gray-400 text-xs mt-1 flex items-center gap-1">
                                        <span>📞</span> {{ ticket.cliente.telefono || ticket.cliente.celular }}
                                    </div>
                                </div>
                                
                                <Link :href="route('clientes.show', ticket.cliente.id)" class="w-full py-2.5 flex items-center justify-center bg-gray-100 dark:bg-slate-800 hover:bg-orange-500 dark:hover:bg-orange-600 text-gray-700 dark:text-slate-300 hover:text-white dark:hover:text-white rounded-xl text-xs font-bold transition-all gap-2">
                                    <span>Ver ficha completa</span>
                                    <span>→</span>
                                </Link>
                            </div>

                            <!-- Historial de tickets del cliente -->
                            <div v-if="historialCliente && historialCliente.length > 0" class="mt-4 pt-3 border-t">
                                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2">Otros tickets:</div>
                                <div v-for="t in historialCliente" :key="t.id" class="text-xs py-1">
                                    <Link :href="route('soporte.show', t.id)" class="text-orange-600 hover:underline">
                                        {{ t.numero }}
                                    </Link>
                                    <span class="text-gray-500 dark:text-gray-400 ml-1">{{ t.estado }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Producto relacionado -->
                        <div v-if="ticket.producto" class="bg-white dark:bg-slate-900/50 backdrop-blur-sm border border-gray-100 dark:border-slate-800 rounded-2xl shadow-sm p-6">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <span class="text-orange-500 text-lg">📦</span>
                                Producto
                            </h3>
                            <div class="p-4 bg-gray-50 dark:bg-slate-950/50 rounded-xl border border-gray-100 dark:border-slate-800/50 shadow-sm">
                                <div class="font-black text-gray-900 dark:text-white text-sm">{{ ticket.producto.nombre }}</div>
                                <div class="text-gray-500 dark:text-gray-400 font-mono text-[10px] mt-1 tracking-wider uppercase">SKU: {{ ticket.producto.sku }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Rápido de Cierre/Resolución (Rediseñado) -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showHorasModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex items-center justify-center min-h-screen px-4 py-6">
                        <!-- Overlay -->
                        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="cancelarConsumoHoras"></div>
                        
                        <!-- Modal Card -->
                        <div class="relative bg-white dark:bg-slate-900 rounded-3xl shadow-2xl max-w-sm w-full overflow-hidden z-10 transform transition-all">
                            
                            <!-- Header con gradiente -->
                            <div :class="[
                                'px-6 py-5 text-center',
                                estadoPendiente === 'cerrado' 
                                    ? 'bg-gradient-to-br from-gray-600 to-gray-800' 
                                    : 'bg-gradient-to-br from-green-500 to-emerald-600'
                            ]">
                                <div class="w-14 h-14 mx-auto bg-white dark:bg-slate-900/20 backdrop-blur rounded-2xl flex items-center justify-center mb-3">
                                    <span class="text-3xl">{{ estadoPendiente === 'cerrado' ? '✅' : '🎉' }}</span>
                                </div>
                                <h3 class="text-xl font-bold text-white">
                                    {{ estadoPendiente === 'cerrado' ? 'Cerrar Ticket' : 'Marcar como Resuelto' }}
                                </h3>
                                <p class="text-white/80 text-sm mt-1">{{ ticket.numero }}</p>
                            </div>

                            <!-- Contenido -->
                            <div class="p-6 space-y-5">
                                
                                <!-- Selector rápido de duración -->
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-3">⏱️ ¿Cuánto tiempo tomó?</label>
                                    
                                    <!-- Botones de tiempo rápido -->
                                    <div class="grid grid-cols-4 gap-2 mb-3">
                                        <button 
                                            v-for="t in [0.5, 1, 1.5, 2]" 
                                            :key="t"
                                            type="button"
                                            @click="horasTrabajadas = t"
                                            :class="[
                                                'py-3 rounded-xl text-sm font-bold border-2 transition-all',
                                                parseFloat(horasTrabajadas) === t 
                                                    ? 'bg-blue-50 border-blue-500 text-blue-700 shadow-md scale-105' 
                                                    : 'bg-gray-50 dark:bg-slate-950 border-gray-200 dark:border-slate-800 text-gray-600 dark:text-gray-300 hover:border-gray-300'
                                            ]"
                                        >
                                            {{ t }}h
                                        </button>
                                    </div>
                                    
                                    <!-- Input personalizado -->
                                    <div class="relative">
                                        <input 
                                            v-model="horasTrabajadas" 
                                            type="number" 
                                            step="0.25" 
                                            min="0.25" 
                                            placeholder="Otro..."
                                            class="w-full text-center text-lg font-bold py-3 border-2 border-gray-200 dark:border-slate-800 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition"
                                        />
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm">horas</span>
                                    </div>
                                </div>

                                <!-- Tipo de servicio (solo si tiene póliza) -->
                                <div v-if="ticket.poliza" class="pt-4 border-t">
                                    <label class="block text-sm font-bold text-gray-700 mb-3">🏷️ Tipo de servicio</label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <button 
                                            type="button"
                                            @click="tipoServicio = 'garantia'"
                                            :class="[
                                                'relative p-4 rounded-xl border-2 text-left transition-all',
                                                tipoServicio === 'garantia' 
                                                    ? 'bg-green-50 border-green-500 shadow-md' 
                                                    : 'bg-gray-50 dark:bg-slate-950 border-gray-200 dark:border-slate-800 hover:border-gray-300'
                                            ]"
                                        >
                                            <div class="text-2xl mb-1">🛡️</div>
                                            <div class="text-sm font-bold" :class="tipoServicio === 'garantia' ? 'text-green-700' : 'text-gray-600 dark:text-gray-300'">Bajo Póliza</div>
                                            <div class="text-[10px] text-gray-400">Consume del plan</div>
                                            <div v-if="tipoServicio === 'garantia'" class="absolute top-2 right-2 w-5 h-5 bg-green-500 rounded-full flex items-center justify-center">
                                                <span class="text-white text-xs">✓</span>
                                            </div>
                                        </button>
                                        <button 
                                            type="button"
                                            @click="tipoServicio = 'costo'"
                                            :class="[
                                                'relative p-4 rounded-xl border-2 text-left transition-all',
                                                tipoServicio === 'costo' 
                                                    ? 'bg-purple-50 border-purple-500 shadow-md' 
                                                    : 'bg-gray-50 dark:bg-slate-950 border-gray-200 dark:border-slate-800 hover:border-gray-300'
                                            ]"
                                        >
                                            <div class="text-2xl mb-1">💰</div>
                                            <div class="text-sm font-bold" :class="tipoServicio === 'costo' ? 'text-purple-700' : 'text-gray-600 dark:text-gray-300'">Con Cargo</div>
                                            <div class="text-[10px] text-gray-400">Servicio extra</div>
                                            <div v-if="tipoServicio === 'costo'" class="absolute top-2 right-2 w-5 h-5 bg-purple-500 rounded-full flex items-center justify-center">
                                                <span class="text-white text-xs">✓</span>
                                            </div>
                                        </button>
                                    </div>
                                </div>

                                <!-- Generar Venta (para clientes SIN póliza) -->
                                <div v-if="!ticket.poliza && !ticket.venta_id" class="pt-4 border-t">
                                    <label 
                                        class="flex items-center gap-4 p-4 rounded-xl border-2 cursor-pointer transition-all"
                                        :class="generarVentaAlCerrar ? 'bg-purple-50 border-purple-400' : 'bg-gray-50 dark:bg-slate-950 border-gray-200 dark:border-slate-800 hover:border-gray-300'"
                                    >
                                        <input 
                                            type="checkbox" 
                                            v-model="generarVentaAlCerrar"
                                            class="w-5 h-5 rounded text-purple-600 border-gray-300 focus:ring-purple-500"
                                        >
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xl">💳</span>
                                                <span class="font-bold text-gray-800 dark:text-gray-100">Generar Nota de Venta</span>
                                            </div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                Se creará automáticamente una venta para facturar este servicio
                                            </p>
                                        </div>
                                    </label>
                                </div>

                                <!-- Info de póliza si aplica -->
                                <div v-if="ticket.poliza && tipoServicio === 'garantia'" class="bg-green-50 border border-green-200 rounded-xl p-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <span class="text-lg">📊</span>
                                        </div>
                                        <div class="flex-1">
                                            <div class="text-xs text-green-600 font-semibold">Consumo de póliza</div>
                                            <div class="text-sm font-bold text-green-800">
                                                {{ ticket.poliza?.horas_consumidas_mes || 0 }} / {{ ticket.poliza?.horas_incluidas_mensual || '∞' }} hrs usadas
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer con botones -->
                            <div class="px-6 pb-6 flex gap-3">
                                <button 
                                    @click="cancelarConsumoHoras" 
                                    class="flex-1 px-4 py-3.5 border-2 border-gray-200 dark:border-slate-800 text-gray-600 dark:text-gray-300 rounded-xl font-semibold hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 transition"
                                >
                                    Cancelar
                                </button>
                                <button 
                                    @click="confirmarConsumoHoras" 
                                    :disabled="!horasTrabajadas || parseFloat(horasTrabajadas) <= 0"
                                    :class="[
                                        'flex-1 px-4 py-3.5 rounded-xl font-bold transition shadow-lg disabled:opacity-50 disabled:cursor-not-allowed',
                                        estadoPendiente === 'cerrado' 
                                            ? 'bg-gray-700 hover:bg-gray-800 text-white' 
                                            : 'bg-green-600 hover:bg-green-700 text-white'
                                    ]"
                                >
                                    <span v-if="!horasTrabajadas">Ingresa tiempo</span>
                                    <span v-else>{{ estadoPendiente === 'resuelto' ? '✓ Resolver' : '✓ Cerrar' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>
