<template>
    <Head title="Crear Mantenimiento" />
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex items-center mb-6">
                <div class="bg-blue-500 p-3 rounded-lg mr-4">
                    <i class="fas fa-wrench text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Crear Mantenimiento</h1>
                    <p class="text-gray-600">Registra un nuevo servicio de mantenimiento para tu vehículo</p>
                    <p class="text-sm text-blue-600 mt-1">
                        <i class="fas fa-info-circle mr-1"></i>
                        Fecha de hoy: {{ todayFormatted }}
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- COMPONENTE: VehicleSelect -->
                <!-- Selección de Carro -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <label for="carro-select" class="block text-gray-700 text-sm font-semibold mb-3">
                        <i class="fas fa-car mr-2"></i>Seleccionar Vehículo
                    </label>
                    <select
                        id="carro-select"
                        v-model.number="form.carro_id"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                        required
                        @change="updateKilometraje"
                    >
                        <option value="" disabled>Selecciona un vehículo</option>
                        <option v-for="carro in props.carros" :key="carro.id" :value="carro.id">
                            {{ carro.marca }} {{ carro.modelo }} {{ carro.año || '' }} - {{ formatNumber(carro.kilometraje) }} km
                        </option>
                    </select>
                    <div v-if="selectedCarro" class="mt-3 p-3 bg-blue-50 rounded-lg border border-blue-200">
                        <div class="flex items-center text-sm text-blue-800">
                            <i class="fas fa-info-circle mr-2"></i>
                            <span>Vehículo seleccionado: <strong>{{ selectedCarro.marca }} {{ selectedCarro.modelo }}</strong></span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-sm text-blue-700 mt-2">
                            <div>Kilometraje actual: <strong>{{ formatNumber(selectedCarro.kilometraje) }} km</strong></div>
                            <div v-if="selectedCarro.año">Año: <strong>{{ selectedCarro.año }}</strong></div>
                        </div>
                        <div v-if="selectedCarro.taller_preferido" class="text-sm text-blue-600 mt-1">
                            <i class="fas fa-wrench mr-1"></i>
                            Taller preferido: {{ selectedCarro.taller_preferido }}
                        </div>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <!-- COMPONENTE: ServicePicker -->
                    <!-- Tipo de Servicio -->
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-3">
                            <i class="fas fa-cogs mr-2"></i>Tipo de Servicio
                        </label>
                        <select
                            v-model="form.tipo"
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                            required
                            @change="handleServiceChange"
                        >
                            <option value="" disabled>Selecciona el tipo de servicio</option>
                            <option v-for="servicio in tiposServicio" :key="servicio.value" :value="servicio.value">
                                {{ servicio.label }}
                            </option>
                        </select>

                        <!-- Campo para "Otro servicio" -->
                        <div v-if="form.tipo === 'Otro servicio'" class="mt-3">
                            <input
                                v-model="form.otro_servicio"
                                type="text"
                                placeholder="Especifica el tipo de servicio..."
                                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                                required
                            >
                        </div>
                    </div>

                    <!-- Fecha del Servicio -->
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-3">
                            <i class="fas fa-calendar mr-2"></i>Fecha del Servicio
                        </label>
                        <input
                            v-model="form.fecha"
                            type="date"
                            :max="today"
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                            required
                        >
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Kilometraje -->
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-3">
                            <i class="fas fa-tachometer-alt mr-2"></i>Kilometraje del Servicio
                        </label>
                        <input
                            v-model.number="form.kilometraje_actual"
                            type="number"
                            :min="selectedCarro?.kilometraje || 0"
                            placeholder="Ingresa el kilometraje actual"
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                            required
                        >
                        <p class="text-sm text-gray-500 mt-2 flex items-center">
                            <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                            Debe ser mayor o igual al kilometraje actual del vehículo
                        </p>

                        <!-- Sugerencias contextuales basadas en kilometraje -->
                        <div v-if="selectedCarro && form.tipo" class="mt-3 p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                            <div class="text-sm text-yellow-800">
                                <i class="fas fa-lightbulb mr-2"></i>
                                <strong>Sugerencias para {{ selectedCarro.marca }} {{ selectedCarro.modelo }}:</strong>
                            </div>
                            <div class="text-xs text-yellow-700 mt-1">
                                {{ sugerenciasContextuales }}
                            </div>
                        </div>
                    </div>

                    <!-- Próximo Mantenimiento -->
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-3">
                            <i class="fas fa-calendar-plus mr-2"></i>Próximo Mantenimiento
                        </label>
                        <input
                            v-model="form.proximo_mantenimiento"
                            type="date"
                            :min="form.fecha || today"
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                            required
                        >
                        <div class="mt-2 flex space-x-2">
                            <button
                                type="button"
                                @click="calcularProximoMantenimiento(3)"
                                class="text-xs bg-gray-100 hover:bg-gray-200 px-2 py-1 rounded transition-colors"
                            >
                                +3 meses
                            </button>
                            <button
                                type="button"
                                @click="calcularProximoMantenimiento(6)"
                                class="text-xs bg-gray-100 hover:bg-gray-200 px-2 py-1 rounded transition-colors"
                            >
                                +6 meses
                            </button>
                            <button
                                type="button"
                                @click="calcularProximoMantenimiento(12)"
                                class="text-xs bg-gray-100 hover:bg-gray-200 px-2 py-1 rounded transition-colors"
                            >
                                +1 año
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Costo del Servicio -->
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-3">
                            <i class="fas fa-dollar-sign mr-2"></i>Costo del Servicio (Opcional)
                        </label>
                        <div class="flex gap-2">
                            <input
                                v-model.number="form.costo"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                class="flex-1 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                            >
                            <button
                                v-if="form.tipo && getCostoSugerido() > 0"
                                type="button"
                                @click="form.costo = getCostoSugerido()"
                                :disabled="form.costo > 0"
                                class="px-3 py-3 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 disabled:bg-gray-100 disabled:text-gray-400 transition-colors text-sm font-medium"
                                title="Usar costo sugerido"
                            >
                                💰 Sugerido
                            </button>
                        </div>
                        <p v-if="form.tipo && getCostoSugerido() > 0" class="text-xs text-gray-500 mt-1">
                            Costo sugerido para {{ form.tipo }}: {{ formatMoney(getCostoSugerido()) }}
                        </p>
                    </div>

                    <!-- Taller/Lugar -->
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-3">
                            <i class="fas fa-map-marker-alt mr-2"></i>Taller/Lugar (Opcional)
                        </label>
                        <input
                            v-model="form.taller"
                            type="text"
                            placeholder="Nombre del taller o lugar"
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                        >
                    </div>
                </div>

                <!-- COMPONENTE: AlertConfig -->
                <!-- Configuración de Alertas y Prioridad -->
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                    <h3 class="text-lg font-semibold text-blue-800 mb-4 flex items-center">
                        <i class="fas fa-bell mr-2"></i>
                        Configuración de Alertas y Prioridad
                    </h3>

                    <div class="grid md:grid-cols-3 gap-4">
                        <!-- Prioridad -->
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-3">
                                <i class="fas fa-exclamation-triangle mr-2"></i>Prioridad
                            </label>
                            <select
                                v-model="form.prioridad"
                                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                                required
                            >
                                <option value="baja">🟢 Baja</option>
                                <option value="media">🔵 Media</option>
                                <option value="alta">🟠 Alta</option>
                                <option value="critica">🔴 Crítica</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ getDescripcionPrioridad(form.prioridad) }}
                            </p>
                        </div>

                        <!-- Días de Anticipación -->
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-3">
                                <i class="fas fa-clock mr-2"></i>Días de Anticipación
                            </label>
                            <input
                                v-model.number="form.dias_anticipacion_alerta"
                                type="number"
                                min="1"
                                max="365"
                                :placeholder="getDiasAnticipacionSugeridos()"
                                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                                required
                            >
                            <p class="text-xs text-gray-500 mt-1">
                                Días antes para enviar alerta
                            </p>
                        </div>

                        <!-- Requiere Aprobación -->
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-3">
                                <i class="fas fa-check-circle mr-2"></i>Requiere Aprobación
                            </label>
                            <div class="flex items-center">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input
                                        v-model="form.requiere_aprobacion"
                                        type="checkbox"
                                        class="sr-only peer"
                                    >
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    <span class="ml-3 text-sm font-medium text-gray-700">
                                        {{ form.requiere_aprobacion ? 'Sí' : 'No' }}
                                    </span>
                                </label>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                Si necesita aprobación especial
                            </p>
                        </div>
                    </div>

                    <!-- Observaciones de Alerta -->
                    <div class="mt-4">
                        <label class="block text-gray-700 text-sm font-semibold mb-3">
                            <i class="fas fa-sticky-note mr-2"></i>Observaciones de Alerta (Opcional)
                        </label>
                        <textarea
                            v-model="form.observaciones_alerta"
                            rows="2"
                            placeholder="Notas adicionales para la alerta..."
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 resize-y"
                            maxlength="500"
                        ></textarea>
                        <div class="flex justify-end text-sm text-gray-500 mt-1">
                            <span>{{ form.observaciones_alerta.length }}/500 caracteres</span>
                        </div>
                    </div>
                </div>

                <!-- COMPONENTE: MaintenanceSummary -->
                <!-- Resumen del Mantenimiento -->
                <div v-if="form.carro_id && form.tipo && form.fecha" class="bg-green-50 p-4 rounded-lg border border-green-200">
                    <h3 class="text-lg font-semibold text-green-800 mb-3 flex items-center">
                        <i class="fas fa-clipboard-check mr-2"></i>
                        Resumen del Mantenimiento
                    </h3>
                    <div class="grid md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <strong>Vehículo:</strong> {{ selectedCarro?.marca }} {{ selectedCarro?.modelo }}
                        </div>
                        <div>
                            <strong>Tipo de Servicio:</strong> {{ form.tipo === 'Otro servicio' ? form.otro_servicio : form.tipo }}
                        </div>
                        <div>
                            <strong>Fecha del Servicio:</strong> {{ new Date(form.fecha).toLocaleDateString('es-MX') }}
                        </div>
                        <div>
                            <strong>Próximo Mantenimiento:</strong> {{ form.proximo_mantenimiento ? new Date(form.proximo_mantenimiento).toLocaleDateString('es-MX') : 'No definido' }}
                        </div>
                        <div>
                            <strong>Prioridad:</strong>
                            <span :class="getClasesPrioridad(form.prioridad)" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ml-2">
                                {{ getLabelPrioridad(form.prioridad) }}
                            </span>
                        </div>
                        <div>
                            <strong>Días de Anticipación:</strong> {{ form.dias_anticipacion_alerta }} días
                        </div>
                        <div v-if="form.costo">
                            <strong>Costo:</strong> {{ formatMoney(form.costo) }}
                        </div>
                        <div v-if="form.taller">
                            <strong>Taller:</strong> {{ form.taller }}
                        </div>
                    </div>
                    <div v-if="form.proximo_mantenimiento" class="mt-3 p-3 bg-green-100 rounded-lg">
                        <div class="text-sm text-green-800">
                            <i class="fas fa-bell mr-2"></i>
                            <strong>Alerta programada:</strong> Se enviará una notificación {{ form.dias_anticipacion_alerta }} días antes del próximo mantenimiento
                            ({{ new Date(form.proximo_mantenimiento).toLocaleDateString('es-MX') }})
                        </div>
                    </div>
                </div>

                <!-- Notas -->
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-3">
                        <i class="fas fa-sticky-note mr-2"></i>Notas y Observaciones
                    </label>
                    <textarea
                        v-model="form.notas"
                        rows="4"
                        placeholder="Describe detalles del servicio, piezas cambiadas, observaciones, etc..."
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 resize-y"
                    ></textarea>
                    <div class="flex justify-between text-sm text-gray-500 mt-1">
                        <span>Opcional</span>
                        <span>{{ form.notas.length }}/500 caracteres</span>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                    <button
                        type="button"
                        @click="resetForm"
                        class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all duration-200 flex items-center"
                    >
                        <i class="fas fa-undo mr-2"></i>
                        Limpiar Formulario
                    </button>

                    <div class="flex space-x-3">
                        <button
                            type="button"
                            @click="router.visit(route('mantenimientos.index'))"
                            class="px-6 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-all duration-200 flex items-center"
                        >
                            <i class="fas fa-times mr-2"></i>
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            :disabled="form.processing || !form.carro_id || !form.tipo"
                            class="px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:bg-blue-300 transition-all duration-200 flex items-center min-w-[160px]"
                        >
                            <i v-if="!form.processing" class="fas fa-save mr-2"></i>
                            <i v-else class="fas fa-spinner fa-spin mr-2"></i>
                            {{ form.processing ? 'Guardando...' : 'Crear Mantenimiento' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { reactive, computed, ref } from 'vue';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';
import AppLayout from '@/Layouts/AppLayout.vue';

// Define el layout del dashboard
defineOptions({ layout: AppLayout });

const props = defineProps({ carros: Array });

const isSubmitting = ref(false);

const notyf = new Notyf({
    duration: 4000,
    position: { x: 'right', y: 'top' },
    types: [
        {
            type: 'success',
            background: 'linear-gradient(135deg, #4caf50, #45a049)',
            icon: { className: 'fas fa-check-circle', tagName: 'i', color: '#fff' }
        },
        {
            type: 'error',
            background: 'linear-gradient(135deg, #f44336, #e53935)',
            icon: { className: 'fas fa-exclamation-triangle', tagName: 'i', color: '#fff' }
        },
        {
            type: 'warning',
            background: 'linear-gradient(135deg, #ff9800, #f57c00)',
            icon: { className: 'fas fa-exclamation-circle', tagName: 'i', color: '#fff' }
        },
    ],
});

// Fecha de hoy compatible con zona horaria (Hermosillo) - definir primero
const today = computed(() => {
    const d = new Date();
    // Ajuste específico para zona horaria de Hermosillo (UTC-7)
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
    return d.toISOString().slice(0,10);
});

// Fecha formateada para mostrar al usuario (más legible)
const todayFormatted = computed(() => {
    return new Date(today.value).toLocaleDateString('es-MX', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
});

// Función para formatear fechas en zona horaria local
const formatDateLocal = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString + 'T00:00:00');
    return date.toLocaleDateString('es-MX', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
};

// Valores iniciales seguros para reset
const initialForm = () => ({
    carro_id: null,
    tipo: '',
    otro_servicio: '',
    fecha: today.value,
    proximo_mantenimiento: '',
    notas: '',
    kilometraje_actual: '',
    costo: '',
    taller: '',
    prioridad: 'media',
    dias_anticipacion_alerta: 30,
    requiere_aprobacion: false,
    observaciones_alerta: '',
});

// Usar useForm de Inertia para mejor manejo de errores y estado
const form = useForm(initialForm());

// ==========================================
// ESTRUCTURA PARA FUTURA COMPONENTIZACIÓN
// ==========================================
// Sugerencias de componentes:
// 1. VehicleSelect - selección de vehículo
// 2. ServicePicker - tipo de servicio + campo personalizado
// 3. AlertConfig - configuración de alertas y prioridad
// 4. MaintenanceSummary - resumen del mantenimiento
// 5. CostInput - input de costo con botón sugerido
// ==========================================

// Configuración de servicios con días separados (anticipación vs intervalo)
const SERVICE_CONFIG = {
    'Cambio de aceite': {
        prioridad: 'media',
        anticipacionDias: 30,     // para alertas
        intervaloDias: 180,       // para proximo_mantenimiento
        costo: 800,
        descripcion: 'Mantenimiento preventivo básico del motor',
    },
    'Revisión periódica': {
        prioridad: 'media',
        anticipacionDias: 60,
        intervaloDias: 365,
        costo: 1200,
        descripcion: 'Inspección completa del vehículo',
    },
    'Servicio de frenos': {
        prioridad: 'alta',
        anticipacionDias: 90,
        intervaloDias: 180,
        costo: 2500,
        descripcion: 'Mantenimiento crítico para seguridad',
    },
    'Servicio de llantas': {
        prioridad: 'media',
        anticipacionDias: 180,
        intervaloDias: 365,
        costo: 600,
        descripcion: 'Rotación y revisión de neumáticos',
    },
    'Servicio de batería': {
        prioridad: 'alta',
        anticipacionDias: 180,
        intervaloDias: 730,
        costo: 1800,
        descripcion: 'Pruebas y posible reemplazo de batería',
    },
    'Servicio de motor': {
        prioridad: 'alta',
        anticipacionDias: 120,
        intervaloDias: 365,
        costo: 3500,
        descripcion: 'Mantenimiento mayor del sistema motor',
    },
    'Revisión de luces': {
        prioridad: 'baja',
        anticipacionDias: 30,
        intervaloDias: 180,
        costo: 300,
        descripcion: 'Verificación del sistema eléctrico',
    },
    'Alineación y balanceo': {
        prioridad: 'media',
        anticipacionDias: 180,
        intervaloDias: 180,
        costo: 800,
        descripcion: 'Ajuste de suspensión y dirección',
    },
    'Cambio de filtros': {
        prioridad: 'media',
        anticipacionDias: 60,
        intervaloDias: 180,
        costo: 400,
        descripcion: 'Reemplazo de filtros de aire, aceite y combustible',
    },
    'Revisión de transmisión': {
        prioridad: 'critica',
        anticipacionDias: 120,
        intervaloDias: 365,
        costo: 2000,
        descripcion: 'Mantenimiento especializado de transmisión',
    },
    'Otro servicio': {
        prioridad: 'media',
        anticipacionDias: 30,
        intervaloDias: 30,
        costo: 0,
        descripcion: 'Servicio personalizado',
    }
};

// Tipos de servicio predefinidos
const tiposServicio = [
    { value: 'Cambio de aceite', label: '🛢️ Cambio de aceite' },
    { value: 'Revisión periódica', label: '🔍 Revisión periódica' },
    { value: 'Servicio de frenos', label: '🛑 Servicio de frenos' },
    { value: 'Servicio de llantas', label: '🛞 Servicio de llantas' },
    { value: 'Servicio de batería', label: '🔋 Servicio de batería' },
    { value: 'Servicio de motor', label: '⚙️ Servicio de motor' },
    { value: 'Revisión de luces', label: '💡 Revisión de luces' },
    { value: 'Alineación y balanceo', label: '⚖️ Alineación y balanceo' },
    { value: 'Cambio de filtros', label: '🔧 Cambio de filtros' },
    { value: 'Revisión de transmisión', label: '🔄 Revisión de transmisión' },
    { value: 'Otro servicio', label: '📝 Otro servicio' },
];

// Carro seleccionado
const selectedCarro = computed(() => {
    return props.carros?.find(carro => carro.id === form.carro_id);
});

// Formatear números y moneda
const formatNumber = (number) => {
    return new Intl.NumberFormat('es-MX').format(Number(number) || 0);
};

// Formatear moneda mexicana
const formatMoney = (value) => {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN'
    }).format(Number(value) || 0);
};

// Formatear moneda en tiempo real mientras escribe
const formatMoneyInput = (value) => {
    const numValue = Number(value) || 0;
    if (numValue === 0) return '';
    return formatMoney(numValue);
};

// Función para formatear kilometraje con separadores de miles
const formatKilometraje = (value) => {
    const numValue = Number(value) || 0;
    return new Intl.NumberFormat('es-MX').format(numValue) + ' km';
};

// ==========================================
// REGLAS DE NEGOCIO PARA ESTADOS DE MANTENIMIENTO
// ==========================================
// Vencido: proximo_mantenimiento < hoy y estado != completado.
// Por vencer: alert_at <= hoy <= proximo_mantenimiento y estado != completado.
// Al día: hoy < alert_at y estado != completado.
// Completado: estado = completado (excluir de alertas).
//
// alert_at = proximo_mantenimiento - dias_anticipacion_alerta (días)
// ==========================================

// Función auxiliar para comparar fechas
const isAfter = (dateA, dateB) => {
    return new Date(dateA).getTime() > new Date(dateB).getTime();
};

// Función auxiliar para comparar fechas (igualdad)
const isSameOrBefore = (dateA, dateB) => {
    return new Date(dateA).getTime() <= new Date(dateB).getTime();
};

// Función auxiliar para comparar fechas (solo antes)
const isBefore = (dateA, dateB) => {
    return new Date(dateA).getTime() < new Date(dateB).getTime();
};

/**
 * Calcular fecha de alerta basada en próximo mantenimiento y días de anticipación
 * @param {string} proximoMantenimiento - Fecha del próximo mantenimiento (YYYY-MM-DD)
 * @param {number} diasAnticipacion - Días de anticipación para la alerta
 * @returns {Date} Fecha en que debe activarse la alerta
 */
const calcularFechaAlerta = (proximoMantenimiento, diasAnticipacion) => {
    const fecha = new Date(proximoMantenimiento);
    fecha.setDate(fecha.getDate() - diasAnticipacion);
    return fecha;
};

/**
 * Determinar el estado de un mantenimiento según las reglas de negocio
 * @param {Object} mantenimiento - Objeto con datos del mantenimiento
 * @param {string} mantenimiento.proximo_mantenimiento - Fecha del próximo mantenimiento
 * @param {number} mantenimiento.dias_anticipacion_alerta - Días de anticipación
 * @param {string} mantenimiento.estado - Estado actual del mantenimiento
 * @returns {Object} Estado calculado con explicación
 */
const calcularEstadoMantenimiento = (mantenimiento) => {
    const hoy = new Date(today.value);
    const proximo = new Date(mantenimiento.proximo_mantenimiento);
    const estado = mantenimiento.estado;

    // Si ya está completado, excluir de alertas
    if (estado === 'completado') {
        return {
            estado: 'completado',
            descripcion: 'Servicio completado',
            clase: 'text-green-700 bg-green-100',
            diasRestantes: 0,
            esVencido: false,
            esProximo: false
        };
    }

    // Calcular fecha de alerta
    const alertAt = calcularFechaAlerta(mantenimiento.proximo_mantenimiento, mantenimiento.dias_anticipacion_alerta);

    // Calcular días restantes
    const diasRestantes = Math.ceil((proximo.getTime() - hoy.getTime()) / (1000 * 60 * 60 * 24));

    // Aplicar reglas de negocio
    if (isBefore(proximo, hoy)) {
        // VENCIDO: proximo_mantenimiento < hoy y estado != completado
        return {
            estado: 'vencido',
            descripcion: `Vencido hace ${Math.abs(diasRestantes)} días`,
            clase: 'text-red-700 bg-red-100',
            diasRestantes: diasRestantes,
            esVencido: true,
            esProximo: false
        };
    } else if (isSameOrBefore(alertAt, hoy) && isBefore(hoy, proximo)) {
        // POR VENCER: alert_at <= hoy <= proximo_mantenimiento y estado != completado
        return {
            estado: 'por_vencer',
            descripcion: `Vence en ${diasRestantes} días (alerta activa)`,
            clase: 'text-orange-700 bg-orange-100',
            diasRestantes: diasRestantes,
            esVencido: false,
            esProximo: true
        };
    } else {
        // AL DÍA: hoy < alert_at y estado != completado
        return {
            estado: 'al_dia',
            descripcion: `Próximo en ${diasRestantes} días`,
            clase: 'text-blue-700 bg-blue-100',
            diasRestantes: diasRestantes,
            esVencido: false,
            esProximo: false
        };
    }
};

/**
 * Función auxiliar para formatear días restantes de manera legible
 * @param {number} dias - Número de días
 * @returns {string} Texto formateado
 */
const formatearDiasRestantes = (dias) => {
    if (dias === 0) return 'Hoy';
    if (dias === 1) return '1 día';
    if (dias === -1) return '1 día atrasado';
    if (dias > 0) return `${dias} días`;
    return `${Math.abs(dias)} días atrasado`;
};

// ==========================================
// GUÍA DE QA MANUAL - VERIFICACIÓN FINAL
// ==========================================
// PASOS PARA VERIFICAR EL MÓDULO COMPLETO:
//
// 1. EJECUTAR SEEDER:
//    php artisan db:seed --class=MantenimientoSeeder
//
// 2. VERIFICAR CREATE.VUE:
//    - Crear mantenimiento con vehículo existente
//    - Verificar tipos seguros (v-model.number)
//    - Verificar fecha local de Hermosillo
//    - Verificar validaciones de duplicados recientes
//    - Verificar auto-completado inteligente
//
// 3. VERIFICAR INDEX.VUE:
//    - Ver estadísticas por reglas de negocio
//    - Probar filtros (estado, tipo, vehículo, prioridad)
//    - Verificar ordenación por urgencia
//    - Probar acciones rápidas (completar, posponer)
//    - Verificar estados calculados correctamente
//
// 4. VERIFICAR REGLAS DE NEGOCIO:
//    - Vencido: proximo_mantenimiento < hoy
//    - Por vencer: alert_at <= hoy <= proximo_mantenimiento
//    - Al día: hoy < alert_at
//    - Completado: excluir de alertas
//
// 5. VERIFICAR FORM REQUEST:
//    - Validar reglas de validación mejoradas
//    - Verificar mensajes de error personalizados
//    - Probar límites de fechas y costos
//
// 6. VERIFICAR CONTROLADOR:
//    - Filtros avanzados funcionan correctamente
//    - Estadísticas se calculan correctamente
//    - Rutas PATCH responden adecuadamente
//
// 7. VERIFICAR ZONA HORARIA:
//    - Fechas se muestran correctamente en Hermosillo
//    - Cálculos de días restantes son precisos
//    - Estados se calculan con zona horaria correcta
// ==========================================

// Configuración de días mínimos entre servicios del mismo tipo
const DIAS_MINIMOS_ENTRE_SERVICIOS = {
    'Cambio de aceite': 30,
    'Revisión periódica': 90,
    'Servicio de frenos': 180,
    'Servicio de llantas': 60,
    'Servicio de batería': 180,
    'Servicio de motor': 365,
    'Revisión de luces': 90,
    'Alineación y balanceo': 90,
    'Cambio de filtros': 60,
    'Revisión de transmisión': 365,
    'Otro servicio': 7 // Muy permisivo para servicios personalizados
};

// Buscar servicios existentes del mismo tipo
const buscarServiciosExistentes = async (carroId, tipoServicio) => {
    try {
        // En producción, usar la ruta correcta de tu aplicación
        const response = await fetch(`/mantenimientos/api/${carroId}/servicios/${encodeURIComponent(tipoServicio)}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                // Agregar token CSRF si es necesario
                // 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        });

        if (response.ok) {
            const servicios = await response.json();
            console.log(`Encontrados ${servicios.length} servicios existentes de ${tipoServicio} para vehículo ${carroId}`);
            return servicios;
        } else {
            console.warn('Error al buscar servicios existentes:', response.status);
            return [];
        }
    } catch (error) {
        console.error('Error de conexión buscando servicios existentes:', error);
        // Retornar array vacío para no bloquear el formulario
        return [];
    }
};

// Función para actualizar el kilometraje cuando se selecciona un carro
const updateKilometraje = () => {
    if (selectedCarro.value) {
        form.kilometraje_actual = selectedCarro.value.kilometraje;

        // Auto-llenar taller si el vehículo tiene uno preferido
        if (selectedCarro.value.taller_preferido && !form.taller) {
            form.taller = selectedCarro.value.taller_preferido;
        }
    } else {
        form.kilometraje_actual = '';
        form.taller = '';
    }
};

const handleServiceChange = () => {
    if (form.tipo !== 'Otro servicio') {
        form.otro_servicio = '';
    }

    // Auto-completar todos los campos de manera inteligente
    autoCompletarCamposInteligente();

    // Mostrar notificación de ayuda
    if (form.tipo && form.tipo !== 'Otro servicio') {
        const cfg = SERVICE_CONFIG[form.tipo];
        if (cfg) {
            notyf.success(`Config aplicada: prioridad ${cfg.prioridad}, alerta ${cfg.anticipacionDias} días, intervalo ${cfg.intervaloDias} días, costo $${cfg.costo}`);
        }
    }
};

// Calcular próximo mantenimiento
const calcularProximoMantenimiento = (meses) => {
    if (!form.fecha) {
        notyf.open({ type: 'warning', message: 'Primero selecciona la fecha del servicio' });
        return;
    }

    const fechaServicio = new Date(form.fecha);
    fechaServicio.setMonth(fechaServicio.getMonth() + meses);
    form.proximo_mantenimiento = fechaServicio.toISOString().split('T')[0];
};

// Validaciones (ahora asíncrona)
const validateForm = async () => {
    const errors = [];

    // Validaciones básicas de campos requeridos
    if (!form.carro_id) errors.push('Debes seleccionar un vehículo');
    if (!form.tipo) errors.push('Debes seleccionar un tipo de servicio');
    if (form.tipo === 'Otro servicio' && !form.otro_servicio) errors.push('Debes especificar el tipo de servicio');
    if (!form.fecha) errors.push('Debes seleccionar la fecha del servicio');
    if (!form.proximo_mantenimiento) errors.push('Debes establecer la fecha del próximo mantenimiento');
    if (!form.kilometraje_actual) errors.push('Debes ingresar el kilometraje actual');
    if (!form.prioridad) errors.push('Debes seleccionar una prioridad');
    if (!form.dias_anticipacion_alerta) errors.push('Debes especificar los días de anticipación para alertas');

    // Validaciones de integridad de datos
    const erroresIntegridad = verificarIntegridadDatos();
    errors.push(...erroresIntegridad);

    // Validaciones de lógica de negocio
    if (selectedCarro.value && parseInt(form.kilometraje_actual) < selectedCarro.value.kilometraje) {
        errors.push(`El kilometraje debe ser mayor o igual a ${formatNumber(selectedCarro.value.kilometraje)} km (kilometraje actual del vehículo)`);
    }

    if (isAfter(form.fecha, today.value)) {
        errors.push('La fecha del servicio no puede ser futura');
    }

    if (!isAfter(form.proximo_mantenimiento, form.fecha)) {
        errors.push('El próximo mantenimiento debe ser posterior a la fecha del servicio');
    }

    // Validar servicios duplicados recientes
    if (form.carro_id && form.tipo && form.fecha) {
        const diasMinimos = DIAS_MINIMOS_ENTRE_SERVICIOS[form.tipo] || 7;
        const erroresDuplicados = await validarServiciosDuplicados(form.carro_id, form.tipo, form.fecha, diasMinimos);
        errors.push(...erroresDuplicados);
    }

    // Validar que los días de anticipación estén en un rango razonable
    const diasAnticipacion = parseInt(form.dias_anticipacion_alerta);
    if (diasAnticipacion < 1) {
        errors.push('Los días de anticipación deben ser al menos 1 día');
    }
    if (diasAnticipacion > 365) {
        errors.push('Los días de anticipación no pueden ser más de 365 días');
    }

    // Validar que el costo sea positivo si se proporciona
    if (form.costo && parseFloat(form.costo) < 0) {
        errors.push('El costo no puede ser negativo');
    }

    // Validaciones de longitud de texto
    if (form.notas.length > 500) {
        errors.push('Las notas no pueden exceder 500 caracteres');
    }

    if (form.observaciones_alerta.length > 500) {
        errors.push('Las observaciones de alerta no pueden exceder 500 caracteres');
    }

    if (form.otro_servicio.length > 100) {
        errors.push('La descripción del servicio personalizado no puede exceder 100 caracteres');
    }

    if (form.taller.length > 100) {
        errors.push('El nombre del taller no puede exceder 100 caracteres');
    }

    return errors;
};

// Reset seguro que preserva tipos y valores por defecto
const resetForm = () => {
    Object.assign(form, initialForm());
    notyf.success('Formulario limpiado');
};

// Enviar formulario usando useForm de Inertia
const submit = async () => {
    // Validación adicional del lado del cliente
    const errors = await validateForm();
    if (errors.length > 0) {
        errors.forEach(error => notyf.error(error));
        return;
    }

    // Datos preparados para envío
    const datosAEnviar = {
        ...form,
        requiere_aprobacion: Boolean(form.requiere_aprobacion),
        costo: form.costo ? Number(form.costo) : 0
    };

    console.log('Datos a enviar:', datosAEnviar);

    // Usar useForm para envío con manejo integrado de errores
    form.post(route('mantenimientos.store'), {
        onSuccess: (response) => {
            notyf.success('¡Mantenimiento creado exitosamente!');
            resetForm();
        },
        onError: (errors) => {
            // Mostrar errores específicos usando el sistema de errores de Inertia
            Object.values(errors).forEach(error => notyf.error(String(error)));
        },
        onFinish: () => {
            // Resetear estado de carga
        }
    });
};

// Funciones para manejar prioridades y alertas
const getDescripcionPrioridad = (prioridad) => {
    const descripciones = {
        'baja': 'Mantenimiento rutinario, no urgente',
        'media': 'Mantenimiento importante, programar pronto',
        'alta': 'Mantenimiento crítico, requiere atención prioritaria',
        'critica': 'Mantenimiento urgente, requiere atención inmediata'
    };
    return descripciones[prioridad] || 'Selecciona una prioridad';
};

const getClasesPrioridad = (prioridad) => {
    const clases = {
        'baja': 'bg-green-100 text-green-700 border-green-200',
        'media': 'bg-blue-100 text-blue-700 border-blue-200',
        'alta': 'bg-orange-100 text-orange-700 border-orange-200',
        'critica': 'bg-red-100 text-red-700 border-red-200'
    };
    return clases[prioridad] || 'bg-gray-100 text-gray-700 border-gray-200';
};

const getLabelPrioridad = (prioridad) => {
    const labels = {
        'baja': 'Baja',
        'media': 'Media',
        'alta': 'Alta',
        'critica': 'Crítica'
    };
    return labels[prioridad] || 'Media';
};

const getDiasAnticipacionSugeridos = () => {
    if (!form.tipo) return 30;
    return SERVICE_CONFIG[form.tipo]?.anticipacionDias || 30;
};

const getCostoSugerido = () => {
    if (!form.tipo) return 0;
    return SERVICE_CONFIG[form.tipo]?.costo || 0;
};

// Auto-llenar costo sugerido
const autoLlenarCosto = () => {
    if (form.tipo && !form.costo) {
        const costoSugerido = getCostoSugerido();
        if (costoSugerido > 0) {
            form.costo = costoSugerido;
        }
    }
};

// Auto-ajustar días de anticipación según el tipo de servicio
const autoAjustarDiasAnticipacion = () => {
    if (form.tipo && !form.dias_anticipacion_alerta) {
        form.dias_anticipacion_alerta = getDiasAnticipacionSugeridos();
    }
};

// Auto-ajustar prioridad según el tipo de servicio
const autoAjustarPrioridad = () => {
    if (form.tipo && !form.prioridad) {
        const prioridadesAutomaticas = {
            'Cambio de aceite': 'media',
            'Revisión periódica': 'media',
            'Servicio de frenos': 'alta',
            'Servicio de llantas': 'media',
            'Servicio de batería': 'alta',
            'Servicio de motor': 'alta',
            'Revisión de luces': 'baja',
            'Alineación y balanceo': 'media',
            'Cambio de filtros': 'media',
            'Revisión de transmisión': 'critica',
            'Otro servicio': 'media'
        };

        if (prioridadesAutomaticas[form.tipo]) {
            form.prioridad = prioridadesAutomaticas[form.tipo];
        }
    }
};

// Función para verificar la integridad de los datos
const verificarIntegridadDatos = () => {
    const errores = [];

    // Verificar que el kilometraje sea numérico y válido
    if (form.kilometraje_actual && isNaN(parseFloat(form.kilometraje_actual))) {
        errores.push('El kilometraje debe ser un número válido');
    }

    // Verificar que el costo sea numérico si se proporciona
    if (form.costo && isNaN(parseFloat(form.costo))) {
        errores.push('El costo debe ser un número válido');
    }

    // Verificar que los días de anticipación sean numéricos
    if (form.dias_anticipacion_alerta && isNaN(parseInt(form.dias_anticipacion_alerta))) {
        errores.push('Los días de anticipación deben ser un número válido');
    }

    // Verificar fechas válidas
    if (form.fecha && !Date.parse(form.fecha)) {
        errores.push('La fecha del servicio no es válida');
    }

    if (form.proximo_mantenimiento && !Date.parse(form.proximo_mantenimiento)) {
        errores.push('La fecha del próximo mantenimiento no es válida');
    }

    // Verificar que la fecha del próximo mantenimiento sea posterior
    if (form.fecha && form.proximo_mantenimiento) {
        if (!isAfter(form.proximo_mantenimiento, form.fecha)) {
            errores.push('La fecha del próximo mantenimiento debe ser posterior a la fecha del servicio');
        }
    }

    return errores;
};

// Función para validar servicios duplicados recientes
const validarServiciosDuplicados = async (carroId, tipoServicio, fechaNueva, diasMinimos) => {
    const errores = [];

    try {
        // Buscar servicios existentes del mismo tipo para este vehículo
        const serviciosExistentes = await buscarServiciosExistentes(carroId, tipoServicio);

        // Verificar cada servicio existente
        for (const servicio of serviciosExistentes) {
            const fechaServicioExistente = new Date(servicio.fecha);
            const fechaNuevaDate = new Date(fechaNueva);

            // Calcular días de diferencia
            const diferenciaMs = Math.abs(fechaNuevaDate.getTime() - fechaServicioExistente.getTime());
            const diferenciaDias = Math.ceil(diferenciaMs / (1000 * 60 * 60 * 24));

            // Si el servicio nuevo está muy cerca de uno existente
            if (diferenciaDias < diasMinimos) {
                const tipoServicioLabel = tipoServicio === 'Otro servicio' ? servicio.otro_servicio || tipoServicio : tipoServicio;
                errores.push(
                    `Ya existe un servicio de "${tipoServicioLabel}" registrado el ${new Date(servicio.fecha).toLocaleDateString('es-MX')} (${diferenciaDias} días atrás). ` +
                    `Debe esperar al menos ${diasMinimos} días entre servicios del mismo tipo.`
                );
            }
        }
    } catch (error) {
        console.error('Error validando servicios duplicados:', error);
        // No agregar errores aquí para no bloquear el formulario si hay problemas de conexión
    }

    return errores;
};

// Función para auto-completar todos los campos de manera inteligente
const autoCompletarCamposInteligente = () => {
    if (!form.tipo) return;

    const cfg = SERVICE_CONFIG[form.tipo];
    if (cfg) {
        // Solo auto-completar si el campo está vacío
        if (!form.prioridad) form.prioridad = cfg.prioridad;
        if (!form.dias_anticipacion_alerta) form.dias_anticipacion_alerta = cfg.anticipacionDias;
        if (!form.costo) form.costo = cfg.costo;

        // Agregar observaciones automáticas si no hay
        if (!form.observaciones_alerta && cfg.descripcion) {
            form.observaciones_alerta = `${cfg.descripcion}. Revisar manual del vehículo para especificaciones.`;
        }

        // Calcular fecha del próximo mantenimiento automáticamente usando intervaloDias
        if (form.fecha && !form.proximo_mantenimiento) {
            const d = new Date(form.fecha);
            d.setDate(d.getDate() + cfg.intervaloDias);
            form.proximo_mantenimiento = d.toISOString().split('T')[0];
        }
    }
};

// Computed property para sugerencias contextuales (mejor rendimiento)
const sugerenciasContextuales = computed(() => {
    if (!selectedCarro.value || !form.tipo) return '';

    const km = Number(selectedCarro.value.kilometraje) || 0;
    const tips = [];

    // Sugerencias basadas en kilometraje
    if (km < 10000) {
        tips.push('Vehículo nuevo: mantenimientos preventivos básicos');
    } else if (km < 50000) {
        tips.push('En garantía: seguir servicios recomendados');
    } else if (km < 100000) {
        tips.push('Revisiones más frecuentes');
    } else {
        tips.push('Alto kilometraje: priorizar preventivos');
    }

    // Sugerencias específicas por servicio
    const porServicio = {
        'Cambio de aceite': 'Cada 5,000–10,000 km según aceite',
        'Servicio de frenos': 'Cada 10,000–15,000 km o si hay ruidos',
        'Servicio de llantas': 'Rotación cada 8,000–10,000 km',
        'Servicio de batería': 'Revisar cada 6 meses, reemplazar cada 2-3 años',
        'Revisión de transmisión': 'Servicio mayor cada 60,000-100,000 km',
        'Cambio de filtros': 'Cada 10,000–15,000 km según condiciones'
    };

    if (porServicio[form.tipo]) {
        tips.push(porServicio[form.tipo]);
    }

    return tips.join('. ');
});

// Establecer fecha por defecto al montar el componente
if (!form.fecha) {
    form.fecha = today.value;
}
</script>

<style scoped>
@import "tailwindcss" reference;
/* Animaciones y estilos adicionales */
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s;
}
.fade-enter, .fade-leave-to {
    opacity: 0;
}

/* Efectos hover para inputs */
input:focus, select:focus, textarea:focus {
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Estilos para botones pequeños */
button[type="button"]:hover {
    transform: translateY(-1px);
}
</style>

