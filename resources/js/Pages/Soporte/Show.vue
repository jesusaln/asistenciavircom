<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    ticket: Object,
    historialCliente: Array,
    categorias: Array,
    usuarios: Array,
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

const cambiarEstado = (nuevoEstado) => {
    // Si es resuelto/cerrado y tiene póliza con control de horas, pedir horas
    if (['resuelto', 'cerrado'].includes(nuevoEstado) && props.ticket.poliza?.horas_incluidas_mensual) {
        estadoPendiente.value = nuevoEstado;
        showHorasModal.value = true;
    } else {
        enviarCambioEstado(nuevoEstado, null);
    }
};

const confirmarConsumoHoras = () => {
    const horas = horasTrabajadas.value ? parseFloat(horasTrabajadas.value) : null;
    enviarCambioEstado(estadoPendiente.value, horas);
    showHorasModal.value = false;
    horasTrabajadas.value = '';
    estadoPendiente.value = '';
};

const cancelarConsumoHoras = () => {
    // Permitir cerrar sin registrar horas
    enviarCambioEstado(estadoPendiente.value, null);
    showHorasModal.value = false;
    horasTrabajadas.value = '';
    estadoPendiente.value = '';
};

const enviarCambioEstado = (estado, horas) => {
    const datos = { estado };
    if (horas !== null) {
        datos.horas_trabajadas = horas;
    }
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
    return colores[prioridad] || 'bg-gray-500 text-white';
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
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
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
                        <div class="flex gap-2">
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
                                        comentario.es_interno ? 'bg-yellow-50 border-l-4 border-yellow-400' : 'bg-gray-50'
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

        <!-- Modal de Registro de Horas (Phase 2) -->
        <Teleport to="body">
            <div v-if="showHorasModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black/50 transition-opacity" @click="cancelarConsumoHoras"></div>
                    
                    <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 z-10">
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 mx-auto bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                <span class="text-3xl">⏱️</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900">Registrar Horas Trabajadas</h3>
                            <p class="text-gray-500 text-sm mt-2">
                                Este ticket está vinculado a una póliza con control de horas.
                                Ingresa las horas trabajadas para actualizar el consumo.
                            </p>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Horas Trabajadas</label>
                            <div class="relative">
                                <input 
                                    v-model="horasTrabajadas" 
                                    type="number" 
                                    step="0.25" 
                                    min="0" 
                                    placeholder="Ej: 1.5"
                                    class="w-full text-center text-2xl font-bold py-4 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                                />
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 font-medium">hrs</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-2 text-center">
                                Consumo actual: {{ ticket.poliza?.horas_consumidas_mes || 0 }} / {{ ticket.poliza?.horas_incluidas_mensual }} hrs
                            </p>
                        </div>

                        <div class="flex gap-3">
                            <button 
                                @click="cancelarConsumoHoras" 
                                class="flex-1 px-4 py-3 border border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 transition"
                            >
                                Omitir
                            </button>
                            <button 
                                @click="confirmarConsumoHoras" 
                                class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition shadow-lg"
                            >
                                Registrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
