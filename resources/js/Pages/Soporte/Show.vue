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
    enviarCambioEstado(estadoPendiente.value, horas, servicioInicio.value, servicioFin.value, tipoServicio.value);
    
    // Reset y cerrar
    showHorasModal.value = false;
    horasTrabajadas.value = '';
    servicioInicio.value = '';
    servicioFin.value = '';
    estadoPendiente.value = '';
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
        abierto: 'bg-blue-100 text-blue-800 border-blue-300',
        en_progreso: 'bg-yellow-100 text-yellow-800 border-yellow-300',
        pendiente: 'bg-orange-100 text-orange-800 border-orange-300',
        resuelto: 'bg-green-100 text-green-800 border-green-300',
        cerrado: 'bg-gray-100 text-gray-800 border-gray-300',
    };
    return colores[estado] || 'bg-gray-100 text-gray-800';
};

const getPrioridadBadge = (prioridad) => {
    const colores = {
        urgente: 'bg-red-500 text-white',
        alta: 'bg-orange-500 text-white',
        media: 'bg-yellow-500 text-white',
        baja: 'bg-green-500 text-white',
    };
    return colores[prioridad] || 'bg-white0 text-white';
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
                                <span :class="['px-3 py-1 text-sm font-medium rounded-full border', getEstadoBadge(ticket.estado)]">
                                    {{ ticket.estado.replace('_', ' ') }}
                                </span>
                                <span :class="['px-2 py-1 text-xs font-bold rounded', getPrioridadBadge(ticket.prioridad)]">
                                    {{ ticket.prioridad?.toUpperCase() || 'NORMAL' }}
                                </span>
                                <span v-if="ticket.tipo_servicio === 'costo'" class="px-2 py-1 text-xs font-bold rounded bg-purple-100 text-purple-700 border border-purple-200">
                                    💰 CON COSTO
                                </span>
                                <span v-else class="px-2 py-1 text-xs font-bold rounded bg-green-100 text-green-700 border border-green-200">
                                    🛡️ GARANTÍA
                                </span>
                            </div>
                            <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ ticket.titulo }}</h1>
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
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <h3 class="font-semibold text-gray-900 mb-3">Descripción</h3>
                            <p class="text-gray-700 whitespace-pre-wrap">{{ ticket.descripcion }}</p>
                        </div>

                        <!-- Timeline de comentarios -->
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <h3 class="font-semibold text-gray-900 mb-4">Conversación</h3>
                            
                            <div class="space-y-4">
                                <div 
                                    v-for="comentario in ticket.comentarios" 
                                    :key="comentario.id"
                                    :class="[
                                        'p-4 rounded-lg',
                                        comentario.es_interno ? 'bg-yellow-50 border-l-4 border-yellow-400' : 'bg-white'
                                    ]"
                                >
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-full bg-orange-500 flex items-center justify-center text-white text-sm font-bold">
                                                {{ comentario.user?.name?.charAt(0) || '?' }}
                                            </div>
                                            <div>
                                                <span class="font-medium text-gray-900">{{ comentario.user?.name }}</span>
                                                <span v-if="comentario.es_interno" class="ml-2 text-xs text-yellow-700 bg-yellow-200 px-2 py-0.5 rounded">
                                                    Nota interna
                                                </span>
                                                <span v-if="comentario.tipo === 'estado'" class="ml-2 text-xs text-blue-700 bg-blue-100 px-2 py-0.5 rounded">
                                                    Cambio de estado
                                                </span>
                                            </div>
                                        </div>
                                        <span class="text-xs text-gray-500">{{ formatDate(comentario.created_at) }}</span>
                                    </div>
                                    <p class="text-gray-700 ml-10">{{ comentario.contenido }}</p>
                                </div>

                                <div v-if="ticket.comentarios.length === 0" class="text-center py-8 text-gray-400">
                                    No hay comentarios aún
                                </div>
                            </div>

                            <!-- Agregar comentario -->
                            <form @submit.prevent="agregarComentario" class="mt-6 pt-4 border-t">
                                <textarea
                                    v-model="nuevoComentario.contenido"
                                    rows="3"
                                    placeholder="Escribe una respuesta..."
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 resize-none"
                                ></textarea>
                                <div class="flex justify-between items-center mt-3">
                                    <label class="flex items-center gap-2 text-sm text-gray-600">
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
                    <div class="lg:col-span-1 space-y-4">
                        <!-- Acciones rápidas -->
                        <div class="bg-white rounded-xl shadow-sm p-4">
                            <h3 class="font-semibold text-gray-900 mb-3">Acciones</h3>
                            
                            <!-- Cambiar estado -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                                <div class="flex flex-wrap gap-1">
                                    <button
                                        v-for="e in estados"
                                        :key="e"
                                        @click="cambiarEstado(e)"
                                        :class="[
                                            'px-2 py-1 text-xs rounded transition',
                                            ticket.estado === e 
                                                ? 'bg-orange-500 text-white' 
                                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                        ]"
                                    >
                                        {{ e.replace('_', ' ') }}
                                    </button>
                                </div>
                            </div>

                            <!-- Asignar -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Asignado a</label>
                                <select 
                                    :value="ticket.asignado_id" 
                                    @change="asignarA($event.target.value)"
                                    class="w-full px-3 py-2 border rounded-lg text-sm"
                                >
                                    <option value="">Sin asignar</option>
                                    <option v-for="u in usuarios" :key="u.id" :value="u.id">{{ u.name }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Info del ticket -->
                        <div class="bg-white rounded-xl shadow-sm p-4">
                            <h3 class="font-semibold text-gray-900 mb-3">Detalles</h3>
                            <dl class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">Categoría</dt>
                                    <dd class="text-gray-900">{{ ticket.categoria?.nombre || '-' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">Origen</dt>
                                    <dd class="text-gray-900">{{ ticket.origen }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">Creado</dt>
                                    <dd class="text-gray-900">{{ formatDate(ticket.created_at) }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">Por</dt>
                                    <dd class="text-gray-900">{{ ticket.creador?.name }}</dd>
                                </div>
                                <div v-if="ticket.fecha_limite" class="flex justify-between">
                                    <dt class="text-gray-500">SLA Límite</dt>
                                    <dd :class="ticket.sla_status === 'vencido' ? 'text-red-600 font-bold' : ticket.sla_status === 'critico' ? 'text-orange-600' : 'text-gray-900'">
                                        {{ formatDate(ticket.fecha_limite) }}
                                    </dd>
                                </div>
                                <div v-if="ticket.resuelto_at" class="flex justify-between">
                                    <dt class="text-gray-500">Resuelto</dt>
                                    <dd class="text-green-600">{{ formatDate(ticket.resuelto_at) }}</dd>
                                </div>
                                <div v-if="ticket.servicio_inicio_at" class="flex justify-between">
                                    <dt class="text-gray-500">Inicio Servicio</dt>
                                    <dd class="text-gray-900">{{ formatDate(ticket.servicio_inicio_at) }}</dd>
                                </div>
                                <div v-if="ticket.servicio_fin_at" class="flex justify-between">
                                    <dt class="text-gray-500">Fin Servicio</dt>
                                    <dd class="text-gray-900">{{ formatDate(ticket.servicio_fin_at) }}</dd>
                                </div>
                                <div v-if="ticket.horas_trabajadas" class="flex justify-between bg-blue-50 -mx-2 px-2 py-1 rounded">
                                    <dt class="text-blue-600 font-semibold">⏱️ Duración Total</dt>
                                    <dd class="text-blue-800 font-bold">{{ ticket.horas_trabajadas }} hrs</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Citas Relacionadas -->
                        <div v-if="ticket.citas && ticket.citas.length > 0" class="bg-blue-50 rounded-xl shadow-sm p-4 border border-blue-200">
                             <h3 class="font-semibold text-blue-900 mb-2 flex items-center gap-2">
                                 <span>📅</span>
                                 Citas del Servicio
                             </h3>
                             <div class="space-y-3">
                                 <div v-for="cita in ticket.citas" :key="cita.id" class="text-sm bg-white p-3 rounded-lg border border-blue-100 shadow-sm">
                                     <div class="flex justify-between items-start mb-1">
                                         <Link :href="route('citas.show', cita.id)" class="font-bold text-blue-700 hover:underline">
                                             {{ cita.folio }}
                                         </Link>
                                         <span :class="['text-[10px] px-2 py-0.5 rounded-full font-bold uppercase', 
                                            cita.estado === 'completado' ? 'bg-green-100 text-green-700' :
                                            cita.estado === 'cancelado' ? 'bg-red-100 text-red-700' :
                                            'bg-blue-100 text-blue-700'
                                         ]">
                                             {{ cita.estado }}
                                         </span>
                                     </div>
                                     <div class="text-xs text-gray-600">
                                         {{ formatDate(cita.fecha_hora) }}
                                     </div>
                                 </div>
                             </div>
                        </div>

                        <!-- Info de Póliza -->
                        <div v-if="ticket.poliza" class="bg-green-50 rounded-xl shadow-sm p-4 border border-green-200">
                             <h3 class="font-semibold text-green-900 mb-2 flex items-center gap-2">
                                 <font-awesome-icon icon="shield-halved" />
                                 Póliza de Servicio
                             </h3>
                             <div class="text-sm">
                                 <div class="font-bold text-green-800">{{ ticket.poliza.nombre }}</div>
                                 <div class="text-green-700 font-mono text-xs">Folio: {{ ticket.poliza.folio }}</div>
                                 <div class="mt-2 text-xs text-green-600">
                                     Consumos mes: {{ ticket.poliza.tickets_mes_actual_count }} / {{ ticket.poliza.limite_mensual_tickets || 'Sin límite' }}
                                 </div>
                                 <Link :href="route('polizas-servicio.show', ticket.poliza.id)" class="inline-block mt-2 text-green-800 hover:text-green-900 text-xs font-bold">
                                     Detalles de la póliza →
                                 </Link>
                             </div>
                        </div>

                        <!-- Info del cliente -->
                        <div v-if="ticket.cliente" class="bg-white rounded-xl shadow-sm p-4">
                            <h3 class="font-semibold text-gray-900 mb-3">Cliente</h3>
                            <div class="text-sm">
                                <div class="font-medium text-gray-900">{{ ticket.cliente.nombre }}</div>
                                <div class="text-gray-600">{{ ticket.cliente.email }}</div>
                                <div class="text-gray-600">{{ ticket.cliente.telefono || ticket.cliente.celular }}</div>
                                
                                <Link :href="route('clientes.show', ticket.cliente.id)" class="inline-block mt-2 text-orange-600 hover:text-orange-800 text-xs">
                                    Ver ficha completa →
                                </Link>
                            </div>

                            <!-- Historial de tickets del cliente -->
                            <div v-if="historialCliente && historialCliente.length > 0" class="mt-4 pt-3 border-t">
                                <div class="text-xs font-semibold text-gray-500 mb-2">Otros tickets:</div>
                                <div v-for="t in historialCliente" :key="t.id" class="text-xs py-1">
                                    <Link :href="route('soporte.show', t.id)" class="text-orange-600 hover:underline">
                                        {{ t.numero }}
                                    </Link>
                                    <span class="text-gray-500 ml-1">{{ t.estado }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Producto relacionado -->
                        <div v-if="ticket.producto" class="bg-white rounded-xl shadow-sm p-4">
                            <h3 class="font-semibold text-gray-900 mb-3">Producto</h3>
                            <div class="text-sm">
                                <div class="font-medium text-gray-900">{{ ticket.producto.nombre }}</div>
                                <div class="text-gray-600 font-mono text-xs">{{ ticket.producto.sku }}</div>
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
                        <div class="relative bg-white rounded-3xl shadow-2xl max-w-sm w-full overflow-hidden z-10 transform transition-all">
                            
                            <!-- Header con gradiente -->
                            <div :class="[
                                'px-6 py-5 text-center',
                                estadoPendiente === 'cerrado' 
                                    ? 'bg-gradient-to-br from-gray-600 to-gray-800' 
                                    : 'bg-gradient-to-br from-green-500 to-emerald-600'
                            ]">
                                <div class="w-14 h-14 mx-auto bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center mb-3">
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
                                                    : 'bg-gray-50 border-gray-200 text-gray-600 hover:border-gray-300'
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
                                            class="w-full text-center text-lg font-bold py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition"
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
                                                    : 'bg-gray-50 border-gray-200 hover:border-gray-300'
                                            ]"
                                        >
                                            <div class="text-2xl mb-1">🛡️</div>
                                            <div class="text-sm font-bold" :class="tipoServicio === 'garantia' ? 'text-green-700' : 'text-gray-600'">Bajo Póliza</div>
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
                                                    : 'bg-gray-50 border-gray-200 hover:border-gray-300'
                                            ]"
                                        >
                                            <div class="text-2xl mb-1">💰</div>
                                            <div class="text-sm font-bold" :class="tipoServicio === 'costo' ? 'text-purple-700' : 'text-gray-600'">Con Cargo</div>
                                            <div class="text-[10px] text-gray-400">Servicio extra</div>
                                            <div v-if="tipoServicio === 'costo'" class="absolute top-2 right-2 w-5 h-5 bg-purple-500 rounded-full flex items-center justify-center">
                                                <span class="text-white text-xs">✓</span>
                                            </div>
                                        </button>
                                    </div>
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
                                    class="flex-1 px-4 py-3.5 border-2 border-gray-200 text-gray-600 rounded-xl font-semibold hover:bg-gray-50 transition"
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
