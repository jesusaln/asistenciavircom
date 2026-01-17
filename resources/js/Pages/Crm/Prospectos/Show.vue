<template>
    <Head :title="`Prospecto: ${prospecto.nombre}`" />

    <div class="prospecto-show min-h-screen bg-white dark:bg-gray-900 transition-colors">
        <div class="w-full px-4 lg:px-8 py-8 animate-fade-in">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center gap-4 mb-4">
                <Link href="/crm/prospectos" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 transition-colors">
                    <FontAwesomeIcon :icon="['fas', 'arrow-left']" />
                </Link>
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors">{{ prospecto.nombre }}</h1>
                        <span :class="getEtapaColor(prospecto.etapa)" class="px-3 py-1 rounded-full text-xs font-bold border transition-colors shadow-sm">
                            {{ etapas[prospecto.etapa] }}
                        </span>
                        <span :class="getPrioridadColor(prospecto.prioridad)" class="px-2 py-1 rounded-full text-xs font-bold border transition-colors shadow-sm">
                            {{ (prospecto.prioridad || '').toUpperCase() }}
                        </span>
                    </div>
                    <p v-if="prospecto.empresa" class="text-gray-500 dark:text-gray-400 mt-1 transition-colors">{{ prospecto.empresa }}</p>
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
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 transition-colors">
                    <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2 transition-colors">
                        <FontAwesomeIcon :icon="['fas', 'id-card']" class="text-amber-500" />
                        Información de Contacto
                    </h3>
                    <div class="space-y-4">
                        <div v-if="prospecto.telefono" class="flex items-center gap-3 group">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400 transition-colors">
                                <FontAwesomeIcon :icon="['fas', 'phone']" class="w-4 h-4" />
                            </div>
                            <div class="flex-1">
                                <p class="text-[10px] text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider">Teléfono</p>
                                <div class="flex items-center gap-2">
                                    <a :href="`tel:${prospecto.telefono}`" class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 underline decoration-blue-500/30 underline-offset-4 transition-colors">{{ prospecto.telefono }}</a>
                                    <a :href="`https://wa.me/52${prospecto.telefono.replace(/\D/g,'')}`" target="_blank" class="w-6 h-6 rounded-full bg-green-50 dark:bg-green-900/30 flex items-center justify-center text-green-500 dark:text-green-400 hover:scale-110 transition-transform shadow-sm">
                                        <FontAwesomeIcon :icon="['fab', 'whatsapp']" class="text-xs" />
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div v-if="prospecto.email" class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400 transition-colors">
                                <FontAwesomeIcon :icon="['fas', 'envelope']" class="w-4 h-4" />
                            </div>
                            <div class="flex-1">
                                <p class="text-[10px] text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider">Email</p>
                                <a :href="`mailto:${prospecto.email}`" class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-amber-600 dark:hover:text-amber-400 underline decoration-amber-500/30 underline-offset-4 transition-colors truncate block max-w-[200px]">{{ prospecto.email }}</a>
                            </div>
                        </div>
                        <div v-if="prospecto.vendedor" class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400 transition-colors">
                                <FontAwesomeIcon :icon="['fas', 'user-tie']" class="w-4 h-4" />
                            </div>
                            <div class="flex-1">
                                <p class="text-[10px] text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider">Asignado a</p>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors">{{ prospecto.vendedor.name }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Valor y fechas -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 transition-colors">
                    <h3 class="font-bold text-gray-900 dark:text-white mb-4 transition-colors">Detalles del Negocio</h3>
                    <div class="space-y-4">
                        <div v-if="prospecto.valor_estimado" class="flex justify-between items-center bg-emerald-50/30 dark:bg-emerald-900/10 p-3 rounded-lg border border-emerald-100/50 dark:border-emerald-800/30 transition-colors">
                            <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">Valor Estimado</span>
                            <span class="text-lg font-bold text-emerald-600 dark:text-emerald-400">${{ formatMonto(prospecto.valor_estimado) }}</span>
                        </div>
                        <div class="flex justify-between items-center p-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Origen</span>
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded transition-colors">{{ prospecto.origen || 'No definido' }}</span>
                        </div>
                        <div v-if="prospecto.proxima_actividad_at" class="flex flex-col gap-1 p-2">
                            <span class="text-xs text-gray-500 dark:text-gray-400 uppercase font-bold tracking-wider">Próxima Actividad</span>
                            <div class="flex items-center gap-2">
                                <FontAwesomeIcon :icon="['fas', 'calendar-check']" :class="esProximaActividadVencida(prospecto.proxima_actividad_at) ? 'text-red-500' : 'text-blue-500'" />
                                <span :class="esProximaActividadVencida(prospecto.proxima_actividad_at) ? 'text-red-500 font-bold' : 'text-gray-700 dark:text-gray-300'" class="text-sm transition-colors">
                                    {{ formatFechaCompleta(prospecto.proxima_actividad_at) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cambiar etapa -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 transition-colors">
                    <h3 class="font-bold text-gray-900 dark:text-white mb-4 transition-colors">Gestión de Pipeline</h3>
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Etapa Actual</label>
                        <select v-model="etapaSeleccionada" @change="cambiarEtapa" class="w-full px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 dark:text-white rounded-lg focus:ring-2 focus:ring-amber-500 outline-none transition-colors">
                            <option v-for="(label, key) in etapas" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    
                    <!-- Acciones del Prospecto -->
                    <div class="space-y-3 pt-4 border-t border-gray-100 dark:border-gray-700 transition-colors">
                        <!-- Botón Convertir a Cliente -->
                        <button 
                            v-if="!prospecto.cliente_id"
                            @click="convertirACliente"
                            :disabled="procesandoConversion"
                            class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-blue-600 dark:bg-blue-500 text-white font-bold rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 shadow-md shadow-blue-500/20 disabled:opacity-50 transition-all active:scale-95"
                        >
                            <FontAwesomeIcon v-if="procesandoConversion" :icon="['fas', 'spinner']" class="animate-spin" />
                            <FontAwesomeIcon v-else :icon="['fas', 'user-plus']" />
                            Convertir a Cliente
                        </button>
                        
                        <!-- Badge si ya es cliente -->
                        <div v-else class="flex flex-col gap-2 p-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/50 rounded-lg transition-colors">
                            <div class="flex items-center gap-2 text-emerald-700 dark:text-emerald-400">
                                <FontAwesomeIcon :icon="['fas', 'check-circle']" />
                                <span class="font-bold text-sm">Convertido a Cliente</span>
                            </div>
                            <Link v-if="prospecto.cliente" :href="`/clientes/${prospecto.cliente_id}`" class="text-xs text-blue-600 dark:text-blue-400 font-bold hover:underline">
                                Ver expediente del cliente →
                            </Link>
                        </div>
                        
                        <!-- Botón Crear Cotización -->
                        <button 
                            @click="crearCotizacion"
                            class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-amber-500 dark:bg-amber-600 text-white font-bold rounded-lg hover:bg-amber-600 dark:hover:bg-amber-700 shadow-md shadow-amber-500/20 transition-all active:scale-95"
                        >
                            <FontAwesomeIcon :icon="['fas', 'file-invoice-dollar']" />
                            Crear Cotización
                        </button>
                    </div>
                </div>

                <!-- Scripts disponibles -->
                <div v-if="scripts.length" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 transition-colors">
                    <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2 transition-colors">
                        <FontAwesomeIcon :icon="['fas', 'scroll']" class="text-amber-500" />
                        Scripts de Venta
                    </h3>
                    <div class="space-y-3">
                        <div 
                            v-for="script in scripts" 
                            :key="script.id"
                            @click="mostrarScript(script)"
                            class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-amber-300 dark:hover:border-amber-500 hover:bg-amber-50/30 dark:hover:bg-amber-900/10 cursor-pointer transition-all active:scale-95 group"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400 font-bold uppercase transition-colors">{{ script.tipo }}</span>
                                    <span class="font-bold text-gray-800 dark:text-gray-200 group-hover:text-amber-700 dark:group-hover:text-amber-400 transition-colors">{{ script.nombre }}</span>
                                </div>
                                <FontAwesomeIcon :icon="['fas', 'chevron-right']" class="w-3 h-3 text-gray-300 dark:text-gray-600 group-hover:text-amber-500 transition-colors" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notas -->
                <div v-if="prospecto.notas" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 transition-colors">
                    <h3 class="font-bold text-gray-900 dark:text-white mb-3 transition-colors">Notas Internas</h3>
                    <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-700 transition-colors">
                        <p class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-line transition-colors">{{ prospecto.notas }}</p>
                    </div>
                </div>
            </div>

            <!-- Columna derecha: Timeline de actividades -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden transition-colors">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/30 dark:bg-gray-900/30 transition-colors">
                        <h3 class="font-bold text-gray-900 dark:text-white transition-colors">Historial de Actividades</h3>
                    </div>
                    <div class="p-6">
                        <div v-if="prospecto.actividades?.length" class="space-y-8 relative before:absolute before:inset-y-0 before:left-5 before:w-0.5 before:bg-gray-100 dark:before:bg-gray-700 transition-colors">
                            <div v-for="actividad in prospecto.actividades" :key="actividad.id" class="flex gap-4 relative">
                                <div :class="getActividadIconColor(actividad.tipo)" class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center relative z-10 shadow-sm transition-colors border-2 border-white dark:border-gray-800">
                                    <FontAwesomeIcon :icon="['fas', getActividadIcon(actividad.tipo)]" class="w-4 h-4" />
                                </div>
                                <div class="flex-1 bg-gray-50/50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-700 transition-colors">
                                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-1 mb-2">
                                        <span class="font-bold text-gray-900 dark:text-white transition-colors">{{ tiposActividad[actividad.tipo] }}</span>
                                        <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ formatFechaCompleta(actividad.created_at) }}</span>
                                    </div>
                                    <div v-if="actividad.resultado" class="mb-3">
                                        <span :class="getResultadoColor(actividad.resultado)" class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border transition-colors shadow-sm">
                                            {{ resultadosActividad[actividad.resultado] }}
                                        </span>
                                    </div>
                                    <p v-if="actividad.notas" class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line mb-3 transition-colors">{{ actividad.notas }}</p>
                                    <div class="flex items-center gap-2">
                                        <div class="w-5 h-5 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-[8px] font-bold text-gray-500 dark:text-gray-400">
                                            {{ (actividad.usuario?.name || '?').substring(0,2).toUpperCase() }}
                                        </div>
                                        <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Registrado por {{ actividad.usuario?.name }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-16 text-gray-400 dark:text-gray-500">
                            <div class="mb-4 flex justify-center">
                                <div class="w-16 h-16 rounded-full bg-gray-50 dark:bg-gray-900/50 flex items-center justify-center shadow-inner">
                                    <FontAwesomeIcon :icon="['fas', 'history']" class="h-8 w-8 opacity-30 dark:opacity-50" />
                                </div>
                            </div>
                            <p class="text-lg font-medium">Sin actividades registradas</p>
                            <p class="text-sm mb-6">Comienza a registrar las interacciones con este prospecto</p>
                            <button @click="showModalActividad = true" class="px-6 py-2.5 bg-amber-500 dark:bg-amber-600 text-white font-bold rounded-lg hover:bg-amber-600 dark:hover:bg-amber-700 shadow-lg shadow-amber-500/20 transition-all active:scale-95">
                                Registrar primera actividad
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Registrar Actividad -->
        <div v-if="showModalActividad" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showModalActividad = false">
            <div class="flex items-center justify-center min-h-screen px-4 p-4 text-center">
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-all"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full p-8 animate-scale-in text-left transition-colors">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white transition-colors">Registrar Actividad</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 uppercase font-bold tracking-widest">Nueva interacción</p>
                        </div>
                        <button @click="showModalActividad = false" class="text-gray-400 hover:text-gray-600">
                            <FontAwesomeIcon :icon="['fas', 'times']" />
                        </button>
                    </div>

                    <form @submit.prevent="registrarActividad">
                        <div class="space-y-5">
                            <div class="grid grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Tipo *</label>
                                    <select v-model="formActividad.tipo" required class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-amber-500 outline-none transition-colors">
                                        <option v-for="(label, key) in tiposActividad" :key="key" :value="key">{{ label }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Resultado</label>
                                    <select v-model="formActividad.resultado" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-amber-500 outline-none transition-colors">
                                        <option value="">Seleccionar...</option>
                                        <option v-for="(label, key) in resultadosActividad" :key="key" :value="key">{{ label }}</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Notas Detalladas</label>
                                <textarea v-model="formActividad.notas" rows="4" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-amber-500 outline-none transition-colors placeholder-gray-400 dark:placeholder-gray-600" placeholder="Describe los puntos clave de la interacción..."></textarea>
                            </div>
                            <div class="bg-blue-50/50 dark:bg-blue-900/10 p-4 rounded-xl border border-blue-100 dark:border-blue-800/50 transition-colors">
                                <label class="block text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-2">Programar Seguimiento</label>
                                <input v-model="formActividad.proxima_actividad_at" type="datetime-local" class="w-full px-4 py-2.5 bg-white dark:bg-gray-800 border border-blue-200 dark:border-blue-700 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 outline-none transition-colors" />
                            </div>
                        </div>

                        <div class="flex justify-end items-center gap-4 mt-8">
                            <button type="button" @click="showModalActividad = false" class="px-6 py-2.5 text-sm font-bold text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors uppercase tracking-widest">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="procesando" class="px-8 py-2.5 bg-amber-500 dark:bg-amber-600 text-white font-bold rounded-xl hover:bg-amber-600 dark:hover:bg-amber-700 shadow-lg shadow-amber-500/20 disabled:opacity-50 transition-all active:scale-95 uppercase tracking-widest">
                                <FontAwesomeIcon v-if="procesando" :icon="['fas', 'spinner']" class="animate-spin mr-2" />
                                <span v-else>Registrar</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Script -->
        <div v-if="scriptActivo" class="fixed inset-0 z-50 overflow-y-auto" @click.self="scriptActivo = null">
            <div class="flex items-center justify-center min-h-screen px-4 p-4 text-center">
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-all"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-2xl w-full p-8 animate-scale-in text-left transition-colors">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white transition-colors">{{ scriptActivo.nombre }}</h3>
                            <p class="text-[10px] text-amber-500 font-bold uppercase tracking-widest mt-1">Sugerencia de venta ({{ scriptActivo.tipo }})</p>
                        </div>
                        <button @click="scriptActivo = null" class="w-8 h-8 flex items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:text-red-500 transition-all">
                            <FontAwesomeIcon :icon="['fas', 'times']" />
                        </button>
                    </div>
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700/50 rounded-2xl p-8 mb-6 transition-colors shadow-inner">
                        <p class="text-gray-800 dark:text-gray-200 whitespace-pre-line text-lg leading-relaxed font-medium font-serif transition-colors italic">"{{ scriptActivo.contenido }}"</p>
                    </div>
                    <div v-if="scriptActivo.tips" class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/50 rounded-xl p-5 transition-colors">
                        <h4 class="font-bold text-blue-800 dark:text-blue-400 mb-2 flex items-center gap-2">
                            <FontAwesomeIcon :icon="['fas', 'lightbulb']" />
                            Secretos del experto
                        </h4>
                        <p class="text-sm text-blue-700 dark:text-blue-300 leading-relaxed transition-colors">{{ scriptActivo.tips }}</p>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

defineOptions({ layout: AppLayout });

// Estado reactivo para Modo Oscuro
const isDark = ref(false)
let observer = null

onMounted(() => {
  isDark.value = document.documentElement.classList.contains('dark')
  observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      if (mutation.attributeName === 'class') {
        isDark.value = document.documentElement.classList.contains('dark')
      }
    })
  })
  observer.observe(document.documentElement, { attributes: true })
})

onBeforeUnmount(() => {
  if (observer) observer.disconnect()
})

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
    const colors = { 
        prospecto: 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-400 border-gray-200 dark:border-gray-700', 
        contactado: 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800/50', 
        interesado: 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-400 border-yellow-200 dark:border-yellow-800/50', 
        cotizado: 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-400 border-purple-200 dark:border-purple-800/50', 
        negociacion: 'bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-400 border-orange-200 dark:border-orange-800/50', 
        cerrado_ganado: 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800/50', 
        cerrado_perdido: 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800/50' 
    };
    return colors[etapa] || 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-400 border-gray-200 dark:border-gray-700';
};

const getPrioridadColor = (prioridad) => {
    const colors = { 
        alta: 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800/50', 
        media: 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-400 border-yellow-200 dark:border-yellow-800/50', 
        baja: 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800/50' 
    };
    return colors[prioridad] || 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-400 border-gray-200 dark:border-gray-700';
};

const getActividadIcon = (tipo) => {
    const icons = { llamada: 'phone', email: 'envelope', whatsapp: 'comment', reunion: 'users', visita: 'building', nota: 'sticky-note' };
    return icons[tipo] || 'comment';
};

const getActividadIconColor = (tipo) => {
    const colors = { 
        llamada: 'bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400', 
        email: 'bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400', 
        whatsapp: 'bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400', 
        reunion: 'bg-orange-100 dark:bg-orange-900/40 text-orange-600 dark:text-orange-400', 
        visita: 'bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400', 
        nota: 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400' 
    };
    return colors[tipo] || 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400';
};

const getResultadoColor = (resultado) => {
    const colors = { 
        contactado: 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800/50', 
        interesado: 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800/50', 
        cita_agendada: 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800/50', 
        cotizacion_enviada: 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-400 border-purple-200 dark:border-purple-800/50', 
        no_contesto: 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-400 border-yellow-200 dark:border-yellow-800/50', 
        buzon: 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-400 border-yellow-200 dark:border-yellow-800/50', 
        no_interesado: 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800/50', 
        pendiente: 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-400 border-gray-200 dark:border-gray-700' 
    };
    return colors[resultado] || 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-400 border-gray-200 dark:border-gray-700';
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
