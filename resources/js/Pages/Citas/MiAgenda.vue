<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router, usePage, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Notyf } from 'notyf';

const notyf = new Notyf({ position: { x: 'right', y: 'top' } });
const page = usePage();

onMounted(() => {
    const flash = page.props.flash;
    if (flash?.success) notyf.success(flash.success);
    if (flash?.error) notyf.error(flash.error);
});

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
const procesando = ref(false);

// Formulario de Cierre
const formCierre = useForm({
    trabajo_realizado: '',
    fotos_finales: [],
    cerrar_ticket: false,
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
        'pendiente': { label: 'Pendiente', bg: 'bg-yellow-100', text: 'text-yellow-800', icon: '⏳' },
        'pendiente_asignacion': { label: 'Sin Asignar', bg: 'bg-orange-100', text: 'text-orange-800', icon: '📋' },
        'programado': { label: 'Programado', bg: 'bg-blue-100', text: 'text-blue-800', icon: '📅' },
        'en_proceso': { label: 'En Proceso', bg: 'bg-indigo-100', text: 'text-indigo-800', icon: '🔧' },
        'completado': { label: 'Completado', bg: 'bg-green-100', text: 'text-green-800', icon: '✅' },
        'cancelado': { label: 'Cancelado', bg: 'bg-red-100', text: 'text-red-800', icon: '❌' },
    };
    return estados[estado] || { label: estado, bg: 'bg-gray-100', text: 'text-gray-800', icon: '❓' };
}

function getTipoServicioLabel(tipo) {
    const tipos = {
        'instalacion': 'Instalación',
        'mantenimiento': 'Mantenimiento',
        'reparacion': 'Reparación',
        'garantia': 'Garantía',
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

function confirmarAccion(cita, accion) {
    citaActiva.value = cita;
    confirmAction.value = accion;
    
    if (accion === 'completar') {
        formCierre.reset();
        previewFotos.value = [];
        showCierreModal.value = true;
    } else {
        showConfirmModal.value = true;
    }
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
            showCierreModal.value = false;
            citaActiva.value = null;
            formCierre.reset();
            previewFotos.value = [];
            notyf.success('¡Servicio completado!');
        },
        onError: () => {
            notyf.error('Error al enviar el reporte. Verifica el tamaño de las fotos.');
        }
    });
}

function getAccionInfo(accion) {
    const acciones = {
        'iniciar': { 
            label: 'Iniciar Servicio', 
            description: '¿Confirmas que has llegado al domicilio y vas a iniciar el servicio?',
            icon: '🔧',
            btnClass: 'bg-indigo-600 hover:bg-indigo-700'
        },
        'completar': { 
            label: 'Completar Servicio', 
            description: '¿El servicio se ha completado satisfactoriamente?',
            icon: '✅',
            btnClass: 'bg-green-600 hover:bg-green-700'
        },
        'cancelar': { 
            label: 'Cancelar Cita', 
            description: '¿Estás seguro de cancelar esta cita? Esta acción no se puede deshacer.',
            icon: '❌',
            btnClass: 'bg-red-600 hover:bg-red-700'
        },
    };
    return acciones[accion] || {};
}
function isAtrasada(cita) {
    if (['completado', 'cancelado'].includes(cita.estado)) return false;
    
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
        <div class="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100 py-6">
            <div class="max-w-2xl mx-auto px-4">
                
                <!-- Header -->
                <div class="mb-6">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white text-xl font-bold shadow-lg">
                            {{ tecnico.name?.charAt(0) || 'T' }}
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">¡Hola, {{ tecnico.name?.split(' ')[0] }}!</h1>
                            <p class="text-gray-500 text-sm">{{ new Date(fecha + 'T12:00:00').toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long' }) }}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Resumen del día -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div>
                            <div class="text-2xl font-bold text-indigo-600">{{ citasHoy.length }}</div>
                            <div class="text-xs text-gray-500">Citas Hoy</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-green-600">{{ citasHoy.filter(c => c.estado === 'completado').length }}</div>
                            <div class="text-xs text-gray-500">Completadas</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-orange-500">{{ citasHoy.filter(c => ['programado', 'pendiente'].includes(c.estado)).length }}</div>
                            <div class="text-xs text-gray-500">Pendientes</div>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de citas -->
                <div class="space-y-4">
                    <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                        <span>📋</span> Mis Citas de Hoy
                    </h2>
                    
                    <!-- Sin citas -->
                    <div v-if="citasOrdenadas.length === 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
                        <div class="text-5xl mb-4">🎉</div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">¡Sin citas programadas!</h3>
                        <p class="text-gray-500 text-sm">No tienes citas asignadas para hoy.</p>
                    </div>
                    
                    <!-- Citas -->
                    <div 
                        v-for="cita in citasOrdenadas" 
                        :key="cita.id"
                        class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
                    >
                        <!-- Header de la cita -->
                        <div :class="[
                            'px-4 py-3 flex items-center justify-between',
                            isAtrasada(cita) ? 'bg-red-50 text-red-800 border-b border-red-100' :
                            cita.estado === 'en_proceso' ? 'bg-indigo-500 text-white' :
                            cita.estado === 'completado' ? 'bg-green-500 text-white' :
                            cita.estado === 'cancelado' ? 'bg-red-100' : 'bg-gray-50'
                        ]">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">{{ isAtrasada(cita) ? '⚠️' : getEstadoInfo(cita.estado).icon }}</span>
                                <div>
                                    <div class="font-bold flex items-center gap-2">
                                        {{ formatHora(cita.hora_confirmada || cita.fecha_hora) }}
                                        <span v-if="isAtrasada(cita)" class="text-[10px] uppercase bg-red-600 text-white px-1.5 py-0.5 rounded">Atrasada</span>
                                    </div>
                                    <div :class="cita.estado === 'en_proceso' || cita.estado === 'completado' ? 'text-white/80' : 'text-gray-500'" class="text-xs">
                                        {{ getTipoServicioLabel(cita.tipo_servicio) }} • <span :class="{'font-bold text-red-600': isAtrasada(cita)}">{{ isAtrasada(cita) ? formatCitaFecha(cita) : cita.tipo_equipo || 'Minisplit' }}</span>
                                    </div>
                                </div>
                            </div>
                            <span :class="[
                                'px-2 py-1 rounded-full text-xs font-medium',
                                isAtrasada(cita) ? 'bg-red-200 text-red-900 border border-red-300' :
                                cita.estado === 'en_proceso' ? 'bg-white/20 text-white' :
                                cita.estado === 'completado' ? 'bg-white/20 text-white' :
                                getEstadoInfo(cita.estado).bg + ' ' + getEstadoInfo(cita.estado).text
                            ]">
                                {{ isAtrasada(cita) ? 'Vencida' : getEstadoInfo(cita.estado).label }}
                            </span>
                        </div>

                        <!-- Banner de aviso para citas atrasadas -->
                        <div v-if="isAtrasada(cita)" class="px-4 py-3 bg-red-600 text-white flex items-center justify-between">
                            <div class="text-xs font-medium flex items-center gap-2">
                                <span>🗓️ Debió ser el: <strong>{{ formatCitaFecha(cita) }}</strong></span>
                            </div>
                            <a :href="route('citas.recordatorio-reprogramacion', cita.id)" 
                               class="text-[10px] font-bold bg-white text-red-600 px-2 py-1 rounded uppercase hover:bg-red-50 transition-colors shadow-sm"
                            >
                                WhatsApp Recordatorio
                            </a>
                        </div>
                        
                        <!-- Contenido -->
                        <div class="p-4 space-y-3">
                            <!-- Cliente -->
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center text-gray-600">
                                    👤
                                </div>
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-900">{{ cita.cliente?.nombre_razon_social || 'Cliente' }}</div>
                                    <div class="text-sm text-gray-500">{{ cita.cliente?.telefono }}</div>
                                </div>
                                <!-- Botones de contacto -->
                                <div class="flex gap-2">
                                    <button 
                                        @click="llamar(cita.cliente?.telefono)"
                                        class="w-10 h-10 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center hover:bg-blue-100 transition-colors"
                                        title="Llamar"
                                    >
                                        📞
                                    </button>
                                    <button 
                                        @click="abrirWhatsApp(cita.cliente?.telefono)"
                                        class="w-10 h-10 bg-green-50 text-green-600 rounded-full flex items-center justify-center hover:bg-green-100 transition-colors"
                                        title="WhatsApp"
                                    >
                                        💬
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Dirección -->
                            <div 
                                @click="abrirMaps(cita)"
                                class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors"
                            >
                                <span class="text-xl">📍</span>
                                <div class="flex-1">
                                    <div class="text-sm text-gray-900">{{ cita.direccion_calle || 'Sin dirección' }}</div>
                                    <div class="text-xs text-gray-500">
                                        {{ cita.direccion_colonia }}{{ cita.direccion_cp ? `, C.P. ${cita.direccion_cp}` : '' }}
                                    </div>
                                    <div v-if="cita.direccion_referencias" class="text-xs text-gray-400 italic mt-1">
                                        "{{ cita.direccion_referencias }}"
                                    </div>
                                </div>
                                <span class="text-blue-500 text-sm font-medium">Ver mapa →</span>
                            </div>
                            
                            <!-- Notas/Descripción -->
                            <div v-if="cita.descripcion || cita.problema_reportado" class="p-3 bg-yellow-50 rounded-xl">
                                <div class="text-xs text-yellow-600 font-medium mb-1">📝 Notas</div>
                                <div class="text-sm text-gray-700">{{ cita.descripcion || cita.problema_reportado }}</div>
                            </div>
                        </div>
                        
                        <!-- Acciones -->
                        <div v-if="!['completado', 'cancelado'].includes(cita.estado)" class="px-4 py-3 border-t border-gray-100 flex gap-2">
                            <button 
                                v-if="cita.estado === 'programado' || cita.estado === 'pendiente'"
                                @click="confirmarAccion(cita, 'iniciar')"
                                class="flex-1 py-2.5 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 transition-colors flex items-center justify-center gap-2"
                            >
                                🔧 Iniciar Servicio
                            </button>
                            <button 
                                v-if="cita.estado === 'en_proceso'"
                                @click="confirmarAccion(cita, 'completar')"
                                class="flex-1 py-2.5 bg-green-600 text-white rounded-xl font-medium hover:bg-green-700 transition-colors flex items-center justify-center gap-2"
                            >
                                ✅ Completar
                            </button>
                            <button 
                                @click="confirmarAccion(cita, 'cancelar')"
                                class="px-4 py-2.5 bg-gray-100 text-gray-600 rounded-xl font-medium hover:bg-gray-200 transition-colors"
                            >
                                Cancelar
                            </button>
                        </div>
                        
                        <!-- Estado completado -->
                        <div v-if="cita.estado === 'completado'" class="border-t border-green-100 bg-green-50">
                            <div class="px-4 py-3 text-center border-b border-green-100">
                                <span class="text-green-600 font-bold flex items-center justify-center gap-2">
                                    <span>✅</span> Servicio completado
                                </span>
                            </div>
                            
                            <!-- Resumen del trabajo (Solo si existe) -->
                            <div v-if="cita.trabajo_realizado || cita.fotos_finales" class="p-4 space-y-3">
                                <div v-if="cita.trabajo_realizado">
                                    <div class="text-[10px] font-bold text-green-700 uppercase mb-1">Trabajo Realizado:</div>
                                    <p class="text-xs text-gray-700 italic bg-white/50 p-2 rounded-lg border border-green-200/50">
                                        {{ cita.trabajo_realizado }}
                                    </p>
                                </div>
                                
                                <div v-if="cita.fotos_finales?.length > 0">
                                    <div class="text-[10px] font-bold text-green-700 uppercase mb-2">Evidencias:</div>
                                    <div class="grid grid-cols-4 gap-2">
                                        <div v-for="(foto, idx) in cita.fotos_finales" :key="idx" class="aspect-square rounded-lg overflow-hidden border border-green-200">
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
                    <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <span>📆</span> Próximas Citas
                    </h2>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 divide-y">
                        <div 
                            v-for="cita in citasProximas.slice(0, 5)" 
                            :key="cita.id"
                            class="p-4 flex items-center gap-3"
                        >
                            <div class="text-center">
                                <div class="text-xs text-gray-400">{{ new Date(cita.fecha_confirmada || cita.fecha_hora).toLocaleDateString('es-MX', { weekday: 'short' }) }}</div>
                                <div class="text-lg font-bold text-gray-900">{{ new Date(cita.fecha_confirmada || cita.fecha_hora).getDate() }}</div>
                            </div>
                            <div class="flex-1">
                                <div class="font-medium text-gray-900">{{ cita.cliente?.nombre_razon_social }}</div>
                                <div class="text-xs text-gray-500">{{ formatHora(cita.hora_confirmada || cita.fecha_hora) }} • {{ getTipoServicioLabel(cita.tipo_servicio) }}</div>
                            </div>
                            <span :class="[getEstadoInfo(cita.estado).bg, getEstadoInfo(cita.estado).text, 'px-2 py-1 rounded-full text-xs font-medium']">
                                {{ getEstadoInfo(cita.estado).label }}
                            </span>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- Modal de reporte de cierre -->
        <Teleport to="body">
            <div v-if="showCierreModal" class="fixed inset-0 z-50 overflow-y-auto px-4 py-8">
                <div class="flex min-h-full items-center justify-center">
                    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showCierreModal = false"></div>
                    
                    <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in duration-300">
                        <!-- Header -->
                        <div class="bg-indigo-600 p-6 text-white">
                            <h3 class="text-xl font-bold flex items-center gap-2">
                                <span>✅</span> Finalizar Servicio
                            </h3>
                            <p class="text-indigo-100 text-sm opacity-90">Completa el reporte de trabajo para terminar la cita.</p>
                        </div>

                        <div class="p-6 space-y-6 max-h-[70vh] overflow-y-auto">
                            <!-- Descripción -->
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wider">
                                    ¿Qué trabajo se realizó?
                                </label>
                                <textarea 
                                    v-model="formCierre.trabajo_realizado"
                                    rows="4"
                                    class="w-full bg-gray-50 border-gray-200 rounded-2xl focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition-all text-sm"
                                    placeholder="Describe detalladamente las reparaciones o mantenimientos hechos..."
                                ></textarea>
                            </div>

                            <!-- OPCIÓN PARA CERRAR TICKET ASOCIADO (Solo si tiene ticket_id) -->
                            <div v-if="citaActiva?.ticket_id" class="p-4 bg-indigo-50 border border-indigo-100 rounded-2xl">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input 
                                        type="checkbox" 
                                        v-model="formCierre.cerrar_ticket"
                                        class="h-5 w-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                    >
                                    <div class="flex-1">
                                        <div class="text-xs font-bold text-indigo-900 uppercase">Resolver Ticket #{{ citaActiva.ticket_id }}</div>
                                        <p class="text-[10px] text-indigo-600">Marcar el ticket como resuelto automáticamente.</p>
                                    </div>
                                </label>
                            </div>

                            <!-- Fotos Finales -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-sm font-bold text-gray-700 uppercase tracking-wider">
                                        Evidencias Finales
                                    </label>
                                    <span class="text-[10px] bg-gray-100 text-gray-500 px-2 py-1 rounded-full font-bold">
                                        {{ formCierre.fotos_finales.length }} FOTOS
                                    </span>
                                </div>
                                
                                <div class="grid grid-cols-3 gap-3">
                                    <!-- Botón de subida -->
                                    <label class="aspect-square rounded-2xl border-2 border-dashed border-gray-200 hover:border-indigo-400 hover:bg-indigo-50 transition-all cursor-pointer flex flex-col items-center justify-center gap-1 group">
                                        <div class="text-2xl group-hover:scale-110 transition-transform">📸</div>
                                        <span class="text-[10px] font-bold text-gray-400 group-hover:text-indigo-600 uppercase">Añadir</span>
                                        <input type="file" @change="handleFileUpload" multiple accept="image/*" class="hidden">
                                    </label>

                                    <!-- Previews -->
                                    <div 
                                        v-for="(foto, index) in previewFotos" 
                                        :key="index"
                                        class="relative aspect-square rounded-2xl overflow-hidden group shadow-sm bg-gray-100"
                                    >
                                        <img :src="foto" class="w-full h-full object-cover">
                                        <button 
                                            @click="removeFoto(index)"
                                            class="absolute top-1 right-1 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center text-xs shadow-lg hover:scale-110 transition-transform"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                </div>
                                <p class="mt-3 text-[10px] text-gray-400 italic">
                                    Tip: Toma fotos del equipo funcionando o de las piezas reemplazadas.
                                </p>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="p-6 bg-gray-50 border-t border-gray-100 flex gap-3">
                            <button 
                                @click="showCierreModal = false"
                                class="flex-1 py-3 text-gray-600 font-bold text-sm uppercase tracking-widest hover:bg-gray-200 rounded-2xl transition-all"
                            >
                                Atrás
                            </button>
                            <button 
                                @click="enviarReporteCierre"
                                :disabled="formCierre.processing"
                                class="flex-[2] py-3 bg-indigo-600 text-white font-bold text-sm uppercase tracking-widest rounded-2xl shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:scale-[1.02] active:scale-[0.98] transition-all disabled:opacity-50"
                            >
                                <span v-if="formCierre.processing">Enviando...</span>
                                <span v-else>Finalizar Servicio</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
        
        <!-- Modal de confirmación -->
        <Teleport to="body">
            <div v-if="showConfirmModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showConfirmModal = false"></div>
                    
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
                        <div class="text-5xl mb-4">{{ getAccionInfo(confirmAction).icon }}</div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">{{ getAccionInfo(confirmAction).label }}</h3>
                        <p class="text-gray-500 text-sm mb-6">{{ getAccionInfo(confirmAction).description }}</p>
                        
                        <div class="flex gap-3">
                            <button 
                                @click="showConfirmModal = false"
                                class="flex-1 py-2.5 bg-gray-100 text-gray-700 rounded-xl font-medium hover:bg-gray-200 transition-colors"
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
