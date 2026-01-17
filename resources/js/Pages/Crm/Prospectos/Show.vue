<template>
    <Head :title="`Prospecto: ${prospecto.nombre}`" />

    <div class="w-full px-6 py-8 animate-fade-in">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center gap-4 mb-4">
                <Link href="/crm" class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600">
                    <FontAwesomeIcon :icon="['fas', 'arrow-left']" />
                </Link>
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-gray-900">{{ prospecto.nombre }}</h1>
                        <span :class="getEtapaColor(prospecto.etapa)" class="px-3 py-1 rounded-full text-sm font-medium">
                            {{ etapas[prospecto.etapa] }}
                        </span>
                        <span :class="getPrioridadColor(prospecto.prioridad)" class="px-2 py-1 rounded-full text-xs font-medium">
                            {{ prospecto.prioridad }}
                        </span>
                    </div>
                    <p v-if="prospecto.empresa" class="text-gray-500 mt-1">{{ prospecto.empresa }}</p>
                </div>
                <button @click="showModalActividad = true" class="px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600">
                    <FontAwesomeIcon :icon="['fas', 'plus']" class="mr-2" />
                    Registrar Actividad
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Columna izquierda: Info del prospecto -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Datos de contacto -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Información de Contacto</h3>
                    <div class="space-y-3">
                        <div v-if="prospecto.telefono" class="flex items-center gap-3">
                            <FontAwesomeIcon :icon="['fas', 'phone']" class="text-gray-400 w-4" />
                            <a :href="`tel:${prospecto.telefono}`" class="text-blue-600 hover:underline">{{ prospecto.telefono }}</a>
                            <a :href="`https://wa.me/52${prospecto.telefono.replace(/\D/g,'')}`" target="_blank" class="text-green-500 hover:text-green-600">
                                <FontAwesomeIcon :icon="['fab', 'whatsapp']" />
                            </a>
                        </div>
                        <div v-if="prospecto.email" class="flex items-center gap-3">
                            <FontAwesomeIcon :icon="['fas', 'envelope']" class="text-gray-400 w-4" />
                            <a :href="`mailto:${prospecto.email}`" class="text-blue-600 hover:underline">{{ prospecto.email }}</a>
                        </div>
                        <div v-if="prospecto.vendedor" class="flex items-center gap-3">
                            <FontAwesomeIcon :icon="['fas', 'user-tie']" class="text-gray-400 w-4" />
                            <span class="text-gray-700">{{ prospecto.vendedor.name }}</span>
                        </div>
                    </div>
                </div>

                <!-- Valor y fechas -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Detalles</h3>
                    <div class="space-y-3">
                        <div v-if="prospecto.valor_estimado" class="flex justify-between">
                            <span class="text-gray-500">Valor Estimado</span>
                            <span class="font-bold text-green-600">${{ formatMonto(prospecto.valor_estimado) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Origen</span>
                            <span class="text-gray-700">{{ prospecto.origen }}</span>
                        </div>
                        <div v-if="prospecto.proxima_actividad_at" class="flex justify-between">
                            <span class="text-gray-500">Próxima Actividad</span>
                            <span :class="esProximaActividadVencida(prospecto.proxima_actividad_at) ? 'text-red-500 font-medium' : 'text-gray-700'">
                                {{ formatFechaCompleta(prospecto.proxima_actividad_at) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Cambiar etapa -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Cambiar Etapa</h3>
                    <select v-model="etapaSeleccionada" @change="cambiarEtapa" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 mb-4">
                        <option v-for="(label, key) in etapas" :key="key" :value="key">{{ label }}</option>
                    </select>
                    
                    <!-- Acciones del Prospecto -->
                    <div class="space-y-2 pt-4 border-t border-gray-100">
                        <!-- Botón Convertir a Cliente -->
                        <button 
                            v-if="!prospecto.cliente_id"
                            @click="convertirACliente"
                            :disabled="procesandoConversion"
                            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50 transition-colors"
                        >
                            <FontAwesomeIcon v-if="procesandoConversion" :icon="['fas', 'spinner']" class="animate-spin" />
                            <FontAwesomeIcon v-else :icon="['fas', 'user-plus']" />
                            Convertir a Cliente
                        </button>
                        
                        <!-- Badge si ya es cliente -->
                        <div v-else class="flex items-center gap-2 px-4 py-2.5 bg-green-50 border border-green-200 rounded-lg text-green-700">
                            <FontAwesomeIcon :icon="['fas', 'check-circle']" />
                            <span class="font-medium">Ya es cliente</span>
                            <Link v-if="prospecto.cliente" :href="`/clientes/${prospecto.cliente_id}`" class="ml-auto text-blue-600 hover:underline text-sm">
                                Ver cliente →
                            </Link>
                        </div>
                        
                        <!-- Botón Crear Cotización -->
                        <button 
                            @click="crearCotizacion"
                            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-colors"
                        >
                            <FontAwesomeIcon :icon="['fas', 'file-invoice-dollar']" />
                            Crear Cotización
                        </button>
                    </div>
                </div>

                <!-- Scripts disponibles -->
                <div v-if="scripts.length" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <FontAwesomeIcon :icon="['fas', 'scroll']" class="text-amber-500" />
                        Scripts de Venta
                    </h3>
                    <div class="space-y-3">
                        <div 
                            v-for="script in scripts" 
                            :key="script.id"
                            @click="mostrarScript(script)"
                            class="p-3 rounded-lg border border-gray-200 hover:border-amber-300 hover:bg-amber-50/50 cursor-pointer transition-colors"
                        >
                            <div class="flex items-center gap-2">
                                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">{{ script.tipo }}</span>
                                <span class="font-medium text-gray-800">{{ script.nombre }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notas -->
                <div v-if="prospecto.notas" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Notas</h3>
                    <p class="text-gray-600 whitespace-pre-line">{{ prospecto.notas }}</p>
                </div>
            </div>

            <!-- Columna derecha: Timeline de actividades -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                        <h3 class="font-semibold text-gray-900">Historial de Actividades</h3>
                    </div>
                    <div class="p-6">
                        <div v-if="prospecto.actividades?.length" class="space-y-6">
                            <div v-for="actividad in prospecto.actividades" :key="actividad.id" class="flex gap-4">
                                <div :class="getActividadIconColor(actividad.tipo)" class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center">
                                    <FontAwesomeIcon :icon="['fas', getActividadIcon(actividad.tipo)]" class="w-4 h-4" />
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="font-medium text-gray-900">{{ tiposActividad[actividad.tipo] }}</span>
                                        <span class="text-sm text-gray-500">{{ formatFechaCompleta(actividad.created_at) }}</span>
                                    </div>
                                    <div v-if="actividad.resultado" class="mb-2">
                                        <span :class="getResultadoColor(actividad.resultado)" class="px-2 py-1 rounded-full text-xs font-medium">
                                            {{ resultadosActividad[actividad.resultado] }}
                                        </span>
                                    </div>
                                    <p v-if="actividad.notas" class="text-gray-600 whitespace-pre-line">{{ actividad.notas }}</p>
                                    <p class="text-sm text-gray-400 mt-1">por {{ actividad.usuario?.name }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-12 text-gray-400">
                            <FontAwesomeIcon :icon="['fas', 'history']" class="h-12 w-12 mb-4" />
                            <p>Sin actividades registradas</p>
                            <button @click="showModalActividad = true" class="mt-4 px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600">
                                Registrar primera actividad
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Registrar Actividad -->
        <div v-if="showModalActividad" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showModalActividad = false">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black bg-opacity-50"></div>
                <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full p-6 animate-scale-in">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-900">Registrar Actividad</h3>
                        <button @click="showModalActividad = false" class="text-gray-400 hover:text-gray-600">
                            <FontAwesomeIcon :icon="['fas', 'times']" />
                        </button>
                    </div>

                    <form @submit.prevent="registrarActividad">
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                                    <select v-model="formActividad.tipo" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                                        <option v-for="(label, key) in tiposActividad" :key="key" :value="key">{{ label }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Resultado</label>
                                    <select v-model="formActividad.resultado" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                                        <option value="">Seleccionar...</option>
                                        <option v-for="(label, key) in resultadosActividad" :key="key" :value="key">{{ label }}</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                                <textarea v-model="formActividad.notas" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500" placeholder="Describe la interacción..."></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Programar próxima actividad</label>
                                <input v-model="formActividad.proxima_actividad_at" type="datetime-local" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500" />
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 mt-6">
                            <button type="button" @click="showModalActividad = false" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="procesando" class="px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 disabled:opacity-50">
                                <FontAwesomeIcon v-if="procesando" :icon="['fas', 'spinner']" class="animate-spin mr-2" />
                                Registrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Script -->
        <div v-if="scriptActivo" class="fixed inset-0 z-50 overflow-y-auto" @click.self="scriptActivo = null">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black bg-opacity-50"></div>
                <div class="relative bg-white rounded-xl shadow-xl max-w-2xl w-full p-6 animate-scale-in">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-900">{{ scriptActivo.nombre }}</h3>
                        <button @click="scriptActivo = null" class="text-gray-400 hover:text-gray-600">
                            <FontAwesomeIcon :icon="['fas', 'times']" />
                        </button>
                    </div>
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-6 mb-4">
                        <p class="text-gray-800 whitespace-pre-line text-lg leading-relaxed">{{ scriptActivo.contenido }}</p>
                    </div>
                    <div v-if="scriptActivo.tips" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h4 class="font-semibold text-blue-800 mb-2">💡 Tips</h4>
                        <p class="text-blue-700 whitespace-pre-line">{{ scriptActivo.tips }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

defineOptions({ layout: AppLayout });

const props = defineProps({
    prospecto: Object,
    scripts: Array,
    etapas: Object,
    tiposActividad: Object,
    resultadosActividad: Object,
});

const showModalActividad = ref(false);
const procesando = ref(false);
const etapaSeleccionada = ref(props.prospecto.etapa);
const scriptActivo = ref(null);

const formActividad = ref({
    tipo: 'llamada',
    resultado: '',
    notas: '',
    proxima_actividad_at: '',
});

const formatMonto = (valor) => Number(valor || 0).toLocaleString('es-MX', { minimumFractionDigits: 2 });
const formatFechaCompleta = (fecha) => fecha ? new Date(fecha).toLocaleString('es-MX', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '';
const esProximaActividadVencida = (fecha) => new Date(fecha) < new Date();

const getEtapaColor = (etapa) => {
    const colors = { prospecto: 'bg-gray-100 text-gray-700', contactado: 'bg-blue-100 text-blue-700', interesado: 'bg-yellow-100 text-yellow-700', cotizado: 'bg-purple-100 text-purple-700', negociacion: 'bg-orange-100 text-orange-700', cerrado_ganado: 'bg-green-100 text-green-700', cerrado_perdido: 'bg-red-100 text-red-700' };
    return colors[etapa] || 'bg-gray-100 text-gray-700';
};

const getPrioridadColor = (prioridad) => {
    const colors = { alta: 'bg-red-100 text-red-700', media: 'bg-yellow-100 text-yellow-700', baja: 'bg-green-100 text-green-700' };
    return colors[prioridad] || 'bg-gray-100 text-gray-700';
};

const getActividadIcon = (tipo) => {
    const icons = { llamada: 'phone', email: 'envelope', whatsapp: 'comment', reunion: 'users', visita: 'building', nota: 'sticky-note' };
    return icons[tipo] || 'comment';
};

const getActividadIconColor = (tipo) => {
    const colors = { llamada: 'bg-blue-100 text-blue-600', email: 'bg-purple-100 text-purple-600', whatsapp: 'bg-green-100 text-green-600', reunion: 'bg-orange-100 text-orange-600', visita: 'bg-amber-100 text-amber-600', nota: 'bg-gray-100 text-gray-600' };
    return colors[tipo] || 'bg-gray-100 text-gray-600';
};

const getResultadoColor = (resultado) => {
    const colors = { contactado: 'bg-blue-100 text-blue-700', interesado: 'bg-green-100 text-green-700', cita_agendada: 'bg-green-100 text-green-700', cotizacion_enviada: 'bg-purple-100 text-purple-700', no_contesto: 'bg-yellow-100 text-yellow-700', buzon: 'bg-yellow-100 text-yellow-700', no_interesado: 'bg-red-100 text-red-700', pendiente: 'bg-gray-100 text-gray-700' };
    return colors[resultado] || 'bg-gray-100 text-gray-700';
};

const cambiarEtapa = () => {
    router.patch(`/crm/prospectos/${props.prospecto.id}/etapa`, { etapa: etapaSeleccionada.value }, { preserveState: true });
};

const registrarActividad = () => {
    procesando.value = true;
    router.post(`/crm/prospectos/${props.prospecto.id}/actividad`, formActividad.value, {
        onSuccess: () => {
            showModalActividad.value = false;
            formActividad.value = { tipo: 'llamada', resultado: '', notas: '', proxima_actividad_at: '' };
            procesando.value = false;
        },
        onError: () => { procesando.value = false; },
    });
};

const mostrarScript = (script) => {
    scriptActivo.value = script;
};

// Ref para estado de conversión
const procesandoConversion = ref(false);

// Convertir prospecto a cliente
const convertirACliente = () => {
    if (confirm(`¿Convertir "${props.prospecto.nombre}" a cliente?`)) {
        procesandoConversion.value = true;
        router.post(`/crm/prospectos/${props.prospecto.id}/convertir`, {}, {
            onSuccess: () => {
                procesandoConversion.value = false;
            },
            onError: () => {
                procesandoConversion.value = false;
            }
        });
    }
};

// Crear cotización para este prospecto
const crearCotizacion = () => {
    if (props.prospecto.cliente_id) {
        router.visit(`/cotizaciones/create?cliente_id=${props.prospecto.cliente_id}&prospecto_id=${props.prospecto.id}`);
    } else {
        if (confirm(`El prospecto no tiene cliente asociado. ¿Desea convertirlo a cliente primero?`)) {
            procesandoConversion.value = true;
            router.post(`/crm/prospectos/${props.prospecto.id}/convertir`, {}, {
                onSuccess: () => {
                    procesandoConversion.value = false;
                    // Después de convertir, redirigir a crear cotización
                    router.reload({
                        onSuccess: () => {
                            if (props.prospecto.cliente_id) {
                                router.visit(`/cotizaciones/create?cliente_id=${props.prospecto.cliente_id}&prospecto_id=${props.prospecto.id}`);
                            }
                        }
                    });
                },
                onError: () => {
                    procesandoConversion.value = false;
                }
            });
        }
    }
};
</script>
