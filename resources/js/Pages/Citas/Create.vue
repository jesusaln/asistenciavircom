<template>
    <div>
        <Head title="Crear Cita" />
        <div class="max-w-4xl mx-auto">
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h1 class="text-2xl font-semibold mb-6 text-gray-800">Crear Cita</h1>

            <!-- Alertas globales -->
            <div v-if="hasGlobalErrors" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Error en el formulario</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li v-for="error in Object.values(form.errors)" :key="error">{{ error }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notificación de éxito -->
            <div v-if="showSuccessMessage" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800">¡Cita creada exitosamente!</h3>
                        <p class="text-sm text-green-700 mt-1">La cita ha sido guardada correctamente en el sistema.</p>
                    </div>
                </div>
            </div>

            <!-- Formulario -->
            <form @submit.prevent="submit" class="space-y-8">
                <!-- Sección: Información del Cliente y Técnico -->
                <div class="border-b border-gray-200 pb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-medium text-gray-900">Asignación</h2>
                        <div v-if="form.ticket_id" class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-bold flex items-center gap-2">
                             <span>🎫</span> Ticket #{{ form.ticket_id }}
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Buscador de Cliente Mejorado -->
                        <div class="md:col-span-2">
                            <BuscarCliente
                                ref="buscarClienteRef"
                                :clientes="clientes"
                                :cliente-seleccionado="selectedCliente"
                                @cliente-seleccionado="onClienteSeleccionado"
                                @crear-nuevo-cliente="onCrearNuevoCliente"
                                label-busqueda="Cliente"
                                placeholder-busqueda="Buscar cliente por nombre, email, teléfono o RFC..."
                                :requerido="true"
                                titulo-cliente-seleccionado="Cliente Seleccionado"
                                mensaje-vacio="No hay cliente seleccionado"
                                submensaje-vacio="Busca y selecciona un cliente para continuar"
                                :mostrar-opcion-nuevo-cliente="true"
                                :mostrar-estado-cliente="true"
                                :mostrar-info-comercial="true"
                            />
                            <p v-if="form.errors.cliente_id" class="mt-1 text-sm text-red-600">{{ form.errors.cliente_id }}</p>
                        </div>

                        <FormField
                            v-model="form.tecnico_id"
                            label="Técnico"
                            type="select"
                            id="tecnico_id"
                            :options="tecnicosOptions"
                            :error="form.errors.tecnico_id"
                            required
                        />
                    </div>
                </div>

                <!-- Sección: Detalles del Servicio -->
                <div class="border-b border-gray-200 pb-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Detalles del Servicio</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <!-- Fecha con botones rápidos -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de la Cita <span class="text-red-500">*</span></label>
                            
                            <!-- Botones rápidos de fecha -->
                            <div class="flex flex-wrap gap-2 mb-3">
                                <button 
                                    type="button"
                                    @click="setQuickDate('today')"
                                    :class="[
                                        'px-4 py-2 text-sm font-medium rounded-lg border transition-all',
                                        selectedDateType === 'today' 
                                            ? 'bg-blue-600 text-white border-blue-600' 
                                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                    ]"
                                >
                                    📅 Hoy
                                </button>
                                <button 
                                    type="button"
                                    @click="setQuickDate('tomorrow')"
                                    :class="[
                                        'px-4 py-2 text-sm font-medium rounded-lg border transition-all',
                                        selectedDateType === 'tomorrow' 
                                            ? 'bg-blue-600 text-white border-blue-600' 
                                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                    ]"
                                >
                                    ⏭️ Mañana
                                </button>
                                <button 
                                    type="button"
                                    @click="setQuickDate('next_monday')"
                                    :class="[
                                        'px-4 py-2 text-sm font-medium rounded-lg border transition-all',
                                        selectedDateType === 'next_monday' 
                                            ? 'bg-blue-600 text-white border-blue-600' 
                                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                    ]"
                                >
                                    📆 Próximo Lunes
                                </button>
                                <button 
                                    type="button"
                                    @click="setQuickDate('custom')"
                                    :class="[
                                        'px-4 py-2 text-sm font-medium rounded-lg border transition-all',
                                        selectedDateType === 'custom' 
                                            ? 'bg-blue-600 text-white border-blue-600' 
                                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                    ]"
                                >
                                    🗓️ Otra Fecha
                                </button>
                            </div>
                            
                            <!-- Input de fecha (visible siempre pero editable) -->
                            <div class="flex gap-4">
                                <div class="flex-1">
                                    <input 
                                        type="date" 
                                        v-model="selectedDate"
                                        @change="updateDateTime"
                                        :min="todayDate"
                                        class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 shadow-sm text-lg py-3"
                                    >
                                </div>
                                <div class="flex items-center text-gray-400 font-medium">
                                    {{ formatDateDisplay }}
                                </div>
                            </div>
                            <p v-if="form.errors.fecha_hora" class="mt-1 text-sm text-red-600">{{ form.errors.fecha_hora }}</p>
                        </div>

                        <!-- Hora con franjas visuales -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Hora de la Cita <span class="text-red-500">*</span></label>
                            
                            <!-- Franjas de tiempo -->
                            <div class="mb-3">
                                <p class="text-xs text-gray-500 mb-2">Selecciona una franja horaria:</p>
                                <div class="flex flex-wrap gap-2">
                                    <button 
                                        type="button"
                                        @click="selectTimeSlot('morning')"
                                        :class="[
                                            'px-4 py-2 text-sm font-medium rounded-lg border transition-all flex items-center gap-2',
                                            selectedTimeSlot === 'morning' 
                                                ? 'bg-amber-500 text-white border-amber-500' 
                                                : 'bg-white text-gray-700 border-gray-300 hover:bg-amber-50'
                                        ]"
                                    >
                                        🌅 Mañana <span class="text-xs opacity-75">(8:00-12:00)</span>
                                    </button>
                                    <button 
                                        type="button"
                                        @click="selectTimeSlot('afternoon')"
                                        :class="[
                                            'px-4 py-2 text-sm font-medium rounded-lg border transition-all flex items-center gap-2',
                                            selectedTimeSlot === 'afternoon' 
                                                ? 'bg-orange-500 text-white border-orange-500' 
                                                : 'bg-white text-gray-700 border-gray-300 hover:bg-orange-50'
                                        ]"
                                    >
                                        ☀️ Tarde <span class="text-xs opacity-75">(12:00-17:00)</span>
                                    </button>
                                    <button 
                                        type="button"
                                        @click="selectTimeSlot('evening')"
                                        :class="[
                                            'px-4 py-2 text-sm font-medium rounded-lg border transition-all flex items-center gap-2',
                                            selectedTimeSlot === 'evening' 
                                                ? 'bg-indigo-500 text-white border-indigo-500' 
                                                : 'bg-white text-gray-700 border-gray-300 hover:bg-indigo-50'
                                        ]"
                                    >
                                        🌆 Noche <span class="text-xs opacity-75">(17:00-20:00)</span>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Grid de horas específicas -->
                            <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-2">
                                <button 
                                    v-for="hora in horasDisponibles" 
                                    :key="hora"
                                    type="button"
                                    @click="selectTime(hora)"
                                    :class="[
                                        'py-2.5 px-2 text-sm font-medium rounded-lg border transition-all',
                                        selectedTime === hora 
                                            ? 'bg-blue-600 text-white border-blue-600 ring-2 ring-blue-300' 
                                            : 'bg-white text-gray-700 border-gray-200 hover:bg-blue-50 hover:border-blue-300'
                                    ]"
                                >
                                    {{ hora }}
                                </button>
                            </div>
                            
                            <!-- Hora seleccionada visual -->
                            <div v-if="selectedTime" class="mt-3 p-3 bg-blue-50 rounded-lg flex items-center gap-3">
                                <span class="text-2xl">⏰</span>
                                <div>
                                    <p class="text-sm font-medium text-blue-900">Hora seleccionada</p>
                                    <p class="text-lg font-bold text-blue-700">{{ selectedTime }} hrs</p>
                                </div>
                            </div>
                        </div>

                        <FormField
                            v-model="form.estado"
                            label="Estado"
                            type="select"
                            id="estado"
                            :options="estadoOptions"
                            :error="form.errors.estado"
                            required
                        />

                        <FormField
                            v-model="form.prioridad"
                            label="Prioridad"
                            type="select"
                            id="prioridad"
                            :options="prioridadOptions"
                            :error="form.errors.prioridad"
                        />

                        <FormField
                            v-model="form.tipo_servicio"
                            label="Tipo de Servicio"
                            type="select"
                            id="tipo_servicio"
                            :options="tipoServicioOptions"
                            :error="form.errors.tipo_servicio"
                            required
                        />

                        <!-- Advertencia de Límite de Visitas en Sitio -->
                        <div v-if="visitInfo && form.tipo_servicio === 'soporte_sitio'" 
                             class="md:col-span-2 p-4 rounded-lg flex items-start gap-3 border mb-4"
                             :class="visitInfo.excede_limite ? 'bg-amber-50 border-amber-200' : 'bg-blue-50 border-blue-200'">
                            <span class="text-xl">{{ visitInfo.excede_limite ? '⚠️' : 'ℹ️' }}</span>
                            <div class="flex-1">
                                <h3 class="font-bold text-sm" :class="visitInfo.excede_limite ? 'text-amber-800' : 'text-blue-800'">
                                    {{ visitInfo.excede_limite ? 'Límite de Visitas Excedido' : 'Información de Póliza' }}
                                </h3>
                                <p class="text-xs" :class="visitInfo.excede_limite ? 'text-amber-700' : 'text-blue-700'">
                                    Este cliente tiene <strong>{{ visitInfo.visitas_consumidas }} / {{ visitInfo.visitas_incluidas }}</strong> visitas en sitio consumidas este mes.
                                </p>
                                <p v-if="visitInfo.excede_limite" class="mt-1 text-xs font-semibold text-amber-900 border-t border-amber-100 pt-1">
                                    Esta visita tendrá un costo adicional de ${{ visitInfo.costo_extra || '0.00' }}.
                                </p>
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <FormField
                                v-model="form.descripcion"
                                label="Descripción del Servicio"
                                type="textarea"
                                id="descripcion"
                                :error="form.errors.descripcion"
                                placeholder="Descripción detallada del servicio a realizar..."
                                :rows="3"
                            />
                        </div>
                    </div>
                </div>

                <!-- Sección: Información del Equipo -->
                <div class="border-b border-gray-200 pb-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Información del Equipo</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <FormField
                            v-model="form.tipo_equipo"
                            label="Tipo de Equipo"
                            type="select"
                            id="tipo_equipo"
                            :options="tipoEquipoOptions"
                            :error="form.errors.tipo_equipo"
                        />

                        <FormField
                            v-model="form.marca_equipo"
                            label="Marca"
                            type="select"
                            id="marca_equipo"
                            :options="marcasOptions"
                            :error="form.errors.marca_equipo"
                        />

                        <FormField
                            v-model="form.modelo_equipo"
                            label="Modelo"
                            type="text"
                            id="modelo_equipo"
                            :error="form.errors.modelo_equipo"
                            placeholder="Ej. AR12MV"
                            @input="convertirAMayusculas('modelo_equipo')"
                        />
                    </div>
                </div>

                <!-- Sección: Información Adicional -->
                <div class="border-b border-gray-200 pb-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Información Adicional</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <FormField
                            v-model="form.direccion_servicio"
                            label="Dirección del Servicio"
                            type="textarea"
                            id="direccion_servicio"
                            :error="form.errors.direccion_servicio"
                            placeholder="Dirección completa donde se realizará el servicio..."
                            :rows="2"
                        />

                        <FormField
                            v-model="form.observaciones"
                            label="Observaciones"
                            type="textarea"
                            id="observaciones"
                            :error="form.errors.observaciones"
                            placeholder="Observaciones adicionales, instrucciones especiales..."
                            :rows="2"
                        />

                    </div>
                </div>



                <!-- Sección: Notas -->
                <div class="border-b border-gray-200 pb-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Notas Adicionales</h2>
                    <div>
                        <textarea
                            v-model="form.notas"
                            class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-vertical"
                            rows="4"
                            placeholder="Agrega notas adicionales, términos y condiciones, o información relevante para la cita..."
                        ></textarea>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                    <div class="flex space-x-4">
                        <button
                            type="button"
                            @click="resetForm"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                        >
                            <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Limpiar Formulario
                        </button>

                        <button
                            type="button"
                            @click="saveDraft"
                            :disabled="form.processing"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                            </svg>
                            Guardar Borrador
                        </button>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing || !selectedCliente"
                        class="px-6 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        <span v-if="form.processing" class="flex items-center">
                            <svg class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Guardando...
                        </span>
                        <span v-else class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Crear Cita
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </div>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, nextTick } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import FormField from '@/Components/FormField.vue';
import BuscarCliente from '@/Components/CreateComponents/BuscarCliente.vue';


defineOptions({ layout: AppLayout });

const props = defineProps({
    tecnicos: Array,
    clientes: Array,
    marcas: {
        type: Array,
        default: () => []
    },
    categorias: {
        type: Array,
        default: () => []
    },
    prefill: {
        type: Object,
        default: () => ({})
    },
});


// Referencias reactivas para el buscador de clientes
const selectedCliente = ref(null);
const showSuccessMessage = ref(false);
const visitInfo = ref(null);
const loadingVisits = ref(false);

// Referencias a los componentes
const buscarClienteRef = ref(null);

// Formulario usando useForm de Inertia con campos adicionales
const form = useForm({
    tecnico_id: '',
    cliente_id: '',
    fecha_hora: '',
    descripcion: '',
    numero_serie: '',
    tipo_servicio: '',
    estado: 'pendiente',
    prioridad: '',
    garantia: '',
    fecha_compra: '',
    direccion_servicio: '',
    observaciones: '',
    evidencias: '',
    foto_equipo: null,
    foto_hoja_servicio: null,
    foto_identificacion: null,
    notas: '',
    tipo_equipo: '',
    marca_equipo: '',
    modelo_equipo: '',
    ticket_id: '',
});



// Opciones de selección mejoradas
const tecnicosOptions = computed(() => [
    { value: '', text: 'Selecciona un técnico', disabled: true },
    ...props.tecnicos.map(tecnico => ({
        value: tecnico.id,
        text: tecnico.name || `${tecnico.nombre || ''} ${tecnico.apellido || ''}`.trim() || 'Sin nombre',
        disabled: false
    }))
]);


const estadoOptions = [
    { value: '', text: 'Selecciona el estado', disabled: true },
    { value: 'pendiente', text: 'Pendiente' },
    { value: 'programado', text: 'Programado' },
    { value: 'en_proceso', text: 'En Proceso' },
    { value: 'completado', text: 'Completado' },
    { value: 'cancelado', text: 'Cancelado' },
    { value: 'reprogramado', text: 'Reprogramado' }
];

const prioridadOptions = [
    { value: '', text: 'Selecciona la prioridad', disabled: true },
    { value: 'baja', text: 'Baja' },
    { value: 'media', text: 'Media' },
    { value: 'alta', text: 'Alta' },
    { value: 'urgente', text: 'Urgente' }
];

const tipoServicioOptions = [
    { value: '', text: 'Selecciona el tipo de servicio', disabled: true },
    { value: 'soporte_sitio', text: 'Soporte en Sitio' },
    { value: 'garantia', text: 'Garantía' },
    { value: 'instalacion', text: 'Instalación' },
    { value: 'reparacion', text: 'Reparación' },
    { value: 'mantenimiento', text: 'Mantenimiento' },
    { value: 'diagnostico', text: 'Diagnóstico' },
    { value: 'otro', text: 'Otro' }
];

// Tipo de equipo ahora viene de la base de datos (categorías)
const tipoEquipoOptions = computed(() => [
    { value: '', text: 'Selecciona el tipo de equipo', disabled: true },
    ...props.categorias.map(cat => ({
        value: cat.nombre,
        text: cat.nombre,
        disabled: false
    }))
]);

const garantiaOptions = [
    { value: '', text: 'Selecciona opción', disabled: true },
    { value: 'si', text: 'Sí, tiene garantía' },
    { value: 'no', text: 'No tiene garantía' },
    { value: 'no_seguro', text: 'No está seguro' }
];

// Marcas ahora vienen de la base de datos
const marcasOptions = computed(() => [
    { value: '', text: 'Selecciona una marca', disabled: true },
    ...props.marcas.map(marca => ({
        value: marca.nombre,
        text: marca.nombre,
        disabled: false
    }))
]);

// Fallback para datalist (nombres de marcas)
const marcasComunes = computed(() => props.marcas.map(m => m.nombre));



// =====================================================
// NUEVO: Variables para selector de fecha/hora mejorado
// =====================================================
const selectedDate = ref('');
const selectedTime = ref('');
const selectedDateType = ref('');
const selectedTimeSlot = ref('');

// Horas disponibles para selección
const horasDisponibles = [
    '08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
    '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30',
    '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'
];

// Computed para mostrar fecha formateada
const formatDateDisplay = computed(() => {
    if (!selectedDate.value) return '';
    const date = new Date(selectedDate.value + 'T12:00:00');
    const options = { weekday: 'long', day: 'numeric', month: 'long' };
    return date.toLocaleDateString('es-MX', options);
});

// Función para establecer fecha rápida
const setQuickDate = (type) => {
    selectedDateType.value = type;
    const today = new Date();
    
    switch(type) {
        case 'today':
            selectedDate.value = today.toISOString().split('T')[0];
            break;
        case 'tomorrow':
            today.setDate(today.getDate() + 1);
            selectedDate.value = today.toISOString().split('T')[0];
            break;
        case 'next_monday':
            const daysUntilMonday = (8 - today.getDay()) % 7 || 7;
            today.setDate(today.getDate() + daysUntilMonday);
            selectedDate.value = today.toISOString().split('T')[0];
            break;
        case 'custom':
            // Solo marcar como custom, el usuario seleccionará manualmente
            break;
    }
    updateDateTime();
};

// Función para seleccionar franja horaria
const selectTimeSlot = (slot) => {
    selectedTimeSlot.value = slot;
    
    // Seleccionar hora por defecto de cada franja
    switch(slot) {
        case 'morning':
            selectTime('09:00');
            break;
        case 'afternoon':
            selectTime('14:00');
            break;
        case 'evening':
            selectTime('18:00');
            break;
    }
};

// Función para seleccionar hora específica
const selectTime = (hora) => {
    selectedTime.value = hora;
    
    // Determinar franja horaria automáticamente
    const horaNum = parseInt(hora.split(':')[0]);
    if (horaNum < 12) {
        selectedTimeSlot.value = 'morning';
    } else if (horaNum < 17) {
        selectedTimeSlot.value = 'afternoon';
    } else {
        selectedTimeSlot.value = 'evening';
    }
    
    updateDateTime();
};

// Función para actualizar el campo fecha_hora del formulario
const updateDateTime = () => {
    if (selectedDate.value && selectedTime.value) {
        form.fecha_hora = `${selectedDate.value}T${selectedTime.value}`;
    } else if (selectedDate.value) {
        form.fecha_hora = `${selectedDate.value}T10:00`;
    }
};

// Fechas para validación
const todayDate = computed(() => {
    return new Date().toISOString().split('T')[0];
});

const minDateTime = computed(() => {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    return now.toISOString().slice(0, 16);
});

// Funciones para manejo del nuevo componente BuscarCliente
const onClienteSeleccionado = (cliente) => {
    selectedCliente.value = cliente;
    form.cliente_id = cliente ? cliente.id : '';

    // Auto-llenar dirección si existe
    if (cliente && cliente.direccion) {
        form.direccion_servicio = cliente.direccion;
    }

    checkVisitsLimit();
};

const checkVisitsLimit = async () => {
    if (!form.cliente_id || form.tipo_servicio !== 'soporte_sitio') {
        visitInfo.value = null;
        return;
    }

    loadingVisits.value = true;
    try {
        const response = await axios.get(route('citas.check-visits-limit'), {
            params: { cliente_id: form.cliente_id }
        });
        if (response.data.has_policy) {
            visitInfo.value = response.data;
        } else {
            visitInfo.value = null;
        }
    } catch (error) {
        console.error('Error al consultar límites de visitas:', error);
    } finally {
        loadingVisits.value = false;
    }
};

// Observar cambios en tipo de servicio
import { watch } from 'vue';
watch(() => form.tipo_servicio, (newVal) => {
    if (newVal === 'soporte_sitio') {
        checkVisitsLimit();
    } else {
        visitInfo.value = null;
    }
});

const applyPrefillFromProps = () => {
    const prefill = props.prefill || {};
    if (!prefill || Object.keys(prefill).length === 0) return;

    if (prefill.cliente_id) {
        const id = Number(prefill.cliente_id);
        const cliente = props.clientes?.find(c => Number(c.id) === id);
        if (cliente) {
            onClienteSeleccionado(cliente);
        } else {
            form.cliente_id = id;
        }
    }

    if (prefill.numero_serie) form.numero_serie = prefill.numero_serie;
    if (prefill.descripcion) form.descripcion = prefill.descripcion;
    if (prefill.direccion_servicio) form.direccion_servicio = prefill.direccion_servicio;
    if (prefill.tipo_servicio) form.tipo_servicio = prefill.tipo_servicio;
    if (prefill.garantia) form.garantia = prefill.garantia;

    // Si es una cita de garantía, establecer prioridad media y estado programado
    if (prefill.tipo_servicio === 'garantia') {
        form.prioridad = 'media';
        form.estado = 'programado';
    }

    if (prefill.ticket_id) {
        form.ticket_id = prefill.ticket_id;
    }
};

const onCrearNuevoCliente = (nombreBuscado) => {
    // Abrir ventana para crear nuevo cliente
    window.open(route('clientes.create'), '_blank');
};







// Computed para errores globales
const hasGlobalErrors = computed(() => {
    return Object.keys(form.errors).length > 0;
});

// Función para limpiar cliente seleccionado
const clearClienteSelection = () => {
    selectedCliente.value = null;
    form.cliente_id = '';
};

// Funciones de utilidad
const convertirAMayusculas = (campo) => {
    if (form[campo]) {
        form[campo] = form[campo].toString().toUpperCase().trim();
    }
};

const saveDraft = () => {
    const draftData = {
        ...form.data(),
        selectedCliente: selectedCliente.value,
        timestamp: new Date().toISOString()
    };

    try {
        sessionStorage.setItem('citaDraft', JSON.stringify(draftData));
        showTemporaryMessage('Borrador guardado correctamente', 'success');
    } catch (error) {
        console.error('Error al guardar borrador:', error);
        showTemporaryMessage('Error al guardar borrador', 'error');
    }
};

const loadDraft = () => {
    try {
        const draftData = sessionStorage.getItem('citaDraft');
        if (draftData) {
            const parsed = JSON.parse(draftData);

            // Verificar que el borrador no sea muy antiguo (más de 24 horas)
            const draftDate = new Date(parsed.timestamp);
            const now = new Date();
            const hoursDiff = (now - draftDate) / (1000 * 60 * 60);

            if (hoursDiff < 24) {
                // Cargar datos del formulario
                Object.keys(form.data()).forEach(key => {
                    if (parsed[key] !== undefined && key !== 'tipo_equipo' && key !== 'marca_equipo' && key !== 'modelo_equipo' && key !== 'foto_equipo' && key !== 'foto_hoja_servicio' && key !== 'foto_identificacion') {
                        form[key] = parsed[key];
                    }
                });


                // Cargar cliente seleccionado usando el nuevo componente
                if (parsed.selectedCliente) {
                    selectedCliente.value = parsed.selectedCliente;
                    // El componente BuscarCliente se actualizará automáticamente con el cliente seleccionado
                }

                showTemporaryMessage('Borrador cargado correctamente', 'info');
            } else {
                // Limpiar borrador antiguo
                sessionStorage.removeItem('citaDraft');
            }
        }
    } catch (error) {
        console.error('Error al cargar borrador:', error);
        sessionStorage.removeItem('citaDraft');
    }
};

const showTemporaryMessage = (message, type) => {
    // Crear elemento de notificación temporal
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg transition-all duration-300 ${
        type === 'success' ? 'bg-green-500 text-white' :
        type === 'error' ? 'bg-red-500 text-white' :
        'bg-blue-500 text-white'
    }`;
    notification.textContent = message;

    document.body.appendChild(notification);

    // Remover después de 3 segundos
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
};

const resetForm = () => {
    form.reset();
    form.estado = 'pendiente';
    form.prioridad = '';

    // Limpiar selección de cliente
    clearClienteSelection();

    // Limpiar los componentes de búsqueda
    if (buscarClienteRef.value) {
        buscarClienteRef.value.limpiarBusqueda();
    }

    // Restablecer fecha y hora actual
    const now = new Date();
    const offset = now.getTimezoneOffset();
    now.setMinutes(now.getMinutes() - offset);
    form.fecha_hora = now.toISOString().slice(0, 16);

    // Limpiar borrador
    sessionStorage.removeItem('citaDraft');

    showTemporaryMessage('Formulario limpiado', 'info');
};

const validateForm = () => {
    const errors = [];

    if (!selectedCliente.value || !form.cliente_id) {
        errors.push('Debe seleccionar un cliente');
    }

    if (!form.tecnico_id) {
        errors.push('Debe seleccionar un técnico');
    }

    if (!form.tipo_servicio) {
        errors.push('Debe seleccionar el tipo de servicio');
    }

    if (!form.fecha_hora) {
        errors.push('Debe especificar la fecha y hora');
    }

    // Removido: validación de problema_reportado ya no es requerido

    // Validar fecha no sea en el pasado (excepto hoy)
    const selectedDate = new Date(form.fecha_hora);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (selectedDate < today) {
        errors.push('La fecha de la cita no puede ser anterior a hoy');
    }

    return errors;
};

const submit = () => {
    // Validar formulario antes de enviar
    const validationErrors = validateForm();

    if (validationErrors.length > 0) {
        showTemporaryMessage(`Errores de validación: ${validationErrors.join(', ')}`, 'error');
        return;
    }

    const formData = new FormData();

    // Agregar todos los campos del formulario
    for (const key in form.data()) {
        if (key === 'foto_equipo' || key === 'foto_hoja_servicio' || key === 'foto_identificacion') {
            // Solo agregar archivos si están seleccionados
            if (form[key]) {
                formData.append(key, form[key]);
            }
        } else {
            formData.append(key, form[key] || '');
        }
    }

    // Adjuntar items como JSON (vacio)
    // formData.append('items', JSON.stringify([])); 
    
    // No enviar totales de venta



    form.post(route('citas.store'), {
        data: formData,
        preserveScroll: true,
        onStart: () => {
            // Limpiar mensajes anteriores
            showSuccessMessage.value = false;
        },
        onSuccess: () => {
            showSuccessMessage.value = true;

            // Limpiar borrador después del éxito
            sessionStorage.removeItem('citaDraft');

            // Limpiar formulario después de 2 segundos
            setTimeout(() => {
                resetForm();
                showSuccessMessage.value = false;
            }, 3000);

            // Scroll hacia arriba
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        onError: (errors) => {
            console.error('Error al crear la cita:', errors);

            // Scroll hacia el primer error
            setTimeout(() => {
                const firstErrorElement = document.querySelector('.text-red-500, .border-red-300');
                if (firstErrorElement) {
                    firstErrorElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            }, 100);

            showTemporaryMessage('Error al crear la cita. Revisa los campos marcados.', 'error');
        },
    });
};

// Auto-guardar borrador cada 30 segundos
let autoSaveInterval;

onMounted(() => {
    applyPrefillFromProps();

    // Iniciar auto-guardado
    autoSaveInterval = setInterval(() => {
        if (form.isDirty && (selectedCliente.value || form.cliente_id)) {
            saveDraft();
        }
    }, 30000); // 30 segundos
});

// Limpiar interval al desmontar componente
onUnmounted(() => {
    if (autoSaveInterval) {
        clearInterval(autoSaveInterval);
    }
});

// Detectar cuando el usuario intenta salir de la página sin guardar
window.addEventListener('beforeunload', (e) => {
    if (form.isDirty && !form.processing) {
        e.preventDefault();
        e.returnValue = '¿Estás seguro de que quieres salir? Los cambios no guardados se perderán.';
        return e.returnValue;
    }
});
</script>

