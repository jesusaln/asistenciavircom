<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    empresa: Object,
    tiendas: Object,
    horarios: Object,
    diasDisponibles: Array,
    tiposServicio: Object,
    tiposEquipo: Object,
});

// Variables CSS dinámicas
const cssVars = computed(() => ({
    '--color-primary': props.empresa?.color_principal || '#FF6B35',
    '--color-primary-soft': (props.empresa?.color_principal || '#FF6B35') + '15',
    '--color-primary-medium': (props.empresa?.color_principal || '#FF6B35') + '40',
}));

// Estado del formulario multi-step
const currentStep = ref(1);
const totalSteps = 6;
const isSubmitting = ref(false);

// Formulario con Inertia
const form = useForm({
    // Paso 1: Datos personales
    nombre: '',
    telefono: '',
    email: '',
    
    // Paso 2: Dirección
    direccion_calle: '',
    direccion_colonia: '',
    direccion_cp: '',
    direccion_referencias: '',
    
    // Paso 3: Días preferidos
    dias_preferidos: [],
    
    // Paso 4: Horario preferido
    horario_preferido: '',
    
    // Paso 5: Servicio
    tipo_servicio: '',
    tipo_equipo: '',
    origen_tienda: '',
    numero_ticket_tienda: '',
    descripcion: '',
    
    // Paso 6: Confirmación
    acepta_terminos: false,
});

// Validación por paso
const stepErrors = ref({});

const validateStep = (step) => {
    stepErrors.value = {};
    
    switch (step) {
        case 1:
            if (!form.nombre.trim()) stepErrors.value.nombre = 'El nombre es requerido';
            if (!form.telefono.trim()) stepErrors.value.telefono = 'El teléfono es requerido';
            else if (!/^\d{10}$/.test(form.telefono.replace(/\D/g, ''))) {
                stepErrors.value.telefono = 'Ingresa un teléfono válido de 10 dígitos';
            }
            if (!form.email.trim()) {
                stepErrors.value.email = 'El email es requerido para enviarte tu folio de cita';
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
                stepErrors.value.email = 'Ingresa un email válido';
            }
            break;
            
        case 2:
            if (!form.direccion_calle.trim()) stepErrors.value.direccion_calle = 'La calle es requerida';
            if (!form.direccion_colonia.trim()) stepErrors.value.direccion_colonia = 'La colonia es requerida';
            break;
            
        case 3:
            if (form.dias_preferidos.length === 0) {
                stepErrors.value.dias_preferidos = 'Selecciona al menos un día';
            }
            break;
            
        case 4:
            if (!form.horario_preferido) stepErrors.value.horario_preferido = 'Selecciona un horario';
            break;
            
        case 5:
            if (!form.tipo_servicio) stepErrors.value.tipo_servicio = 'Selecciona el tipo de servicio';
            if (!form.tipo_equipo) stepErrors.value.tipo_equipo = 'Selecciona el tipo de equipo';
            if (!form.origen_tienda) stepErrors.value.origen_tienda = 'Selecciona la tienda de origen';
            break;
            
        case 6:
            if (!form.acepta_terminos) stepErrors.value.acepta_terminos = 'Debes aceptar los términos';
            break;
    }
    
    return Object.keys(stepErrors.value).length === 0;
};

const nextStep = () => {
    if (validateStep(currentStep.value)) {
        if (currentStep.value < totalSteps) {
            currentStep.value++;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const goToStep = (step) => {
    // Solo permitir ir a pasos anteriores o al paso actual + 1 si el actual es válido
    if (step < currentStep.value) {
        currentStep.value = step;
    } else if (step === currentStep.value + 1 && validateStep(currentStep.value)) {
        currentStep.value = step;
    }
};

// Submit del formulario
const submitForm = () => {
    if (!validateStep(currentStep.value)) return;
    
    isSubmitting.value = true;
    form.post(route('agendar.store'), {
        onError: () => {
            isSubmitting.value = false;
        },
    });
};

// Toggle día seleccionado
const toggleDia = (fecha) => {
    const index = form.dias_preferidos.indexOf(fecha);
    if (index > -1) {
        form.dias_preferidos.splice(index, 1);
    } else {
        if (form.dias_preferidos.length < 3) {
            form.dias_preferidos.push(fecha);
        }
    }
};

// Formatear teléfono
const formatPhone = (e) => {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 10) value = value.slice(0, 10);
    form.telefono = value;
};

// Agrupar días disponibles por mes
const diasPorMes = computed(() => {
    const grupos = {};
    props.diasDisponibles?.forEach(dia => {
        const fecha = new Date(dia.fecha + 'T12:00:00');
        const mesKey = `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, '0')}`;
        const mesNombre = fecha.toLocaleDateString('es-MX', { month: 'long', year: 'numeric' });
        
        if (!grupos[mesKey]) {
            grupos[mesKey] = { nombre: mesNombre, dias: [] };
        }
        grupos[mesKey].dias.push({
            ...dia,
            diaSemana: fecha.toLocaleDateString('es-MX', { weekday: 'short' }),
            diaMes: fecha.getDate(),
        });
    });
    return grupos;
});

// Progreso del formulario
const progress = computed(() => ((currentStep.value - 1) / (totalSteps - 1)) * 100);

// Step labels
const stepLabels = [
    { num: 1, label: 'Datos', icon: '👤' },
    { num: 2, label: 'Dirección', icon: '📍' },
    { num: 3, label: 'Fecha', icon: '📅' },
    { num: 4, label: 'Horario', icon: '⏰' },
    { num: 5, label: 'Servicio', icon: '🔧' },
    { num: 6, label: 'Confirmar', icon: '✅' },
];
</script>

<template>
    <Head :title="`Agendar Cita - ${empresa?.nombre || 'Servicio'}`" />
    
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100" :style="cssVars">
        <!-- Header -->
        <header class="bg-white shadow-sm sticky top-0 z-50">
            <div class="w-full px-4 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[var(--color-primary)] flex items-center justify-center text-white font-bold">
                            {{ empresa?.nombre?.charAt(0) || 'C' }}
                        </div>
                        <div>
                            <h1 class="font-bold text-gray-900">{{ empresa?.nombre || 'Climas del Desierto' }}</h1>
                            <p class="text-xs text-gray-500">Agenda tu servicio</p>
                        </div>
                    </div>
                    <a 
                        v-if="empresa?.whatsapp"
                        :href="`https://wa.me/${empresa.whatsapp.replace(/\D/g, '')}?text=Hola, necesito ayuda para agendar una cita`"
                        target="_blank"
                        class="flex items-center gap-1.5 text-green-600 text-sm font-medium hover:text-green-700"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                        </svg>
                        <span class="hidden sm:inline">Ayuda</span>
                    </a>
                </div>
            </div>
        </header>

        <!-- Progress Bar -->
        <div class="bg-white border-b">
            <div class="w-full px-4 py-3">
                <!-- Steps Indicators -->
                <div class="flex items-center justify-between mb-3 overflow-x-auto pb-2">
                    <button
                        v-for="step in stepLabels"
                        :key="step.num"
                        @click="goToStep(step.num)"
                        :class="[
                            'flex flex-col items-center min-w-[50px] transition-all duration-300',
                            currentStep >= step.num ? 'text-[var(--color-primary)]' : 'text-gray-400'
                        ]"
                    >
                        <span 
                            :class="[
                                'w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300',
                                currentStep > step.num ? 'bg-[var(--color-primary)] text-white' : 
                                currentStep === step.num ? 'bg-[var(--color-primary)] text-white ring-4 ring-[var(--color-primary-soft)]' : 
                                'bg-gray-200 text-gray-500'
                            ]"
                        >
                            <span v-if="currentStep > step.num">✓</span>
                            <span v-else>{{ step.icon }}</span>
                        </span>
                        <span class="text-[10px] font-medium mt-1 whitespace-nowrap">{{ step.label }}</span>
                    </button>
                </div>
                
                <!-- Progress Bar Visual -->
                <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
                    <div 
                        class="h-full bg-[var(--color-primary)] rounded-full transition-all duration-500 ease-out"
                        :style="{ width: `${progress}%` }"
                    ></div>
                </div>
            </div>
        </div>

        <!-- Form Content -->
        <main class="w-full px-4 py-6">
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                
                <!-- PASO 1: Datos Personales -->
                <div v-if="currentStep === 1" class="p-6">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-[var(--color-primary-soft)] rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <span class="text-3xl">👤</span>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900">¿Cómo te llamas?</h2>
                        <p class="text-gray-500 text-sm mt-1">Necesitamos tus datos para contactarte</p>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Nombre completo *
                            </label>
                            <input 
                                v-model="form.nombre"
                                type="text"
                                placeholder="Ej: Juan Pérez García"
                                class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-[var(--color-primary)] focus:ring-0 transition-colors"
                                :class="{ 'border-red-400': stepErrors.nombre }"
                            />
                            <p v-if="stepErrors.nombre" class="text-red-500 text-xs mt-1">{{ stepErrors.nombre }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                WhatsApp *
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">+52</span>
                                <input 
                                    :value="form.telefono"
                                    @input="formatPhone"
                                    type="tel"
                                    placeholder="10 dígitos"
                                    maxlength="10"
                                    class="w-full pl-14 pr-4 py-3 rounded-xl border-2 border-gray-200 focus:border-[var(--color-primary)] focus:ring-0 transition-colors"
                                    :class="{ 'border-red-400': stepErrors.telefono }"
                                />
                            </div>
                            <p v-if="stepErrors.telefono" class="text-red-500 text-xs mt-1">{{ stepErrors.telefono }}</p>
                            <p class="text-gray-400 text-xs mt-1">Te enviaremos la confirmación por WhatsApp</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Email (opcional)
                            </label>
                            <input 
                                v-model="form.email"
                                type="email"
                                placeholder="tucorreo@ejemplo.com"
                                class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-[var(--color-primary)] focus:ring-0 transition-colors"
                                :class="{ 'border-red-400': stepErrors.email }"
                            />
                            <p v-if="stepErrors.email" class="text-red-500 text-xs mt-1">{{ stepErrors.email }}</p>
                        </div>
                    </div>
                </div>

                <!-- PASO 2: Dirección -->
                <div v-if="currentStep === 2" class="p-6">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-[var(--color-primary-soft)] rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <span class="text-3xl">📍</span>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900">¿Dónde realizamos el servicio?</h2>
                        <p class="text-gray-500 text-sm mt-1">Ingresa la dirección completa</p>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Calle y número *
                            </label>
                            <input 
                                v-model="form.direccion_calle"
                                type="text"
                                placeholder="Ej: Av. Constitución #1234"
                                class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-[var(--color-primary)] focus:ring-0 transition-colors"
                                :class="{ 'border-red-400': stepErrors.direccion_calle }"
                            />
                            <p v-if="stepErrors.direccion_calle" class="text-red-500 text-xs mt-1">{{ stepErrors.direccion_calle }}</p>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Colonia *
                                </label>
                                <input 
                                    v-model="form.direccion_colonia"
                                    type="text"
                                    placeholder="Nombre de colonia"
                                    class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-[var(--color-primary)] focus:ring-0 transition-colors"
                                    :class="{ 'border-red-400': stepErrors.direccion_colonia }"
                                />
                                <p v-if="stepErrors.direccion_colonia" class="text-red-500 text-xs mt-1">{{ stepErrors.direccion_colonia }}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    C.P.
                                </label>
                                <input 
                                    v-model="form.direccion_cp"
                                    type="text"
                                    placeholder="00000"
                                    maxlength="5"
                                    class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-[var(--color-primary)] focus:ring-0 transition-colors"
                                />
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Referencias para llegar
                            </label>
                            <textarea 
                                v-model="form.direccion_referencias"
                                rows="3"
                                placeholder="Ej: Entre calle Juárez y calle Hidalgo, casa color azul con portón negro"
                                class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-[var(--color-primary)] focus:ring-0 transition-colors resize-none"
                            ></textarea>
                            <p class="text-gray-400 text-xs mt-1">Ayúdanos a encontrar tu domicilio más fácil</p>
                        </div>
                    </div>
                </div>

                <!-- PASO 3: Selección de Días -->
                <div v-if="currentStep === 3" class="p-6">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-[var(--color-primary-soft)] rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <span class="text-3xl">📅</span>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900">¿Cuándo te queda bien?</h2>
                        <p class="text-gray-500 text-sm mt-1">Selecciona hasta 3 días de tu preferencia</p>
                    </div>
                    
                    <p v-if="stepErrors.dias_preferidos" class="text-red-500 text-sm text-center mb-4">{{ stepErrors.dias_preferidos }}</p>
                    
                    <div class="space-y-6">
                        <div v-for="(mes, key) in diasPorMes" :key="key">
                            <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-3 capitalize">
                                {{ mes.nombre }}
                            </h3>
                            <div class="grid grid-cols-4 sm:grid-cols-5 gap-2">
                                <button
                                    v-for="dia in mes.dias"
                                    :key="dia.fecha"
                                    @click="toggleDia(dia.fecha)"
                                    type="button"
                                    :class="[
                                        'relative p-3 rounded-xl border-2 transition-all duration-200 text-center',
                                        form.dias_preferidos.includes(dia.fecha) 
                                            ? 'border-[var(--color-primary)] bg-[var(--color-primary)] text-white shadow-lg scale-105' 
                                            : 'border-gray-200 hover:border-[var(--color-primary)] hover:bg-[var(--color-primary-soft)]',
                                        form.dias_preferidos.length >= 3 && !form.dias_preferidos.includes(dia.fecha) 
                                            ? 'opacity-50 cursor-not-allowed' 
                                            : ''
                                    ]"
                                    :disabled="form.dias_preferidos.length >= 3 && !form.dias_preferidos.includes(dia.fecha)"
                                >
                                    <div class="text-2xl font-bold">{{ dia.diaMes }}</div>
                                    <div class="text-xs uppercase opacity-70">{{ dia.diaSemana }}</div>
                                    
                                    <!-- Indicador de disponibilidad -->
                                    <div 
                                        v-if="!form.dias_preferidos.includes(dia.fecha)"
                                        class="absolute -top-1 -right-1 w-3 h-3 rounded-full"
                                        :class="dia.porcentaje_ocupacion > 70 ? 'bg-yellow-400' : 'bg-green-400'"
                                    ></div>
                                    
                                    <!-- Check si está seleccionado -->
                                    <div 
                                        v-if="form.dias_preferidos.includes(dia.fecha)"
                                        class="absolute -top-1 -right-1 w-5 h-5 bg-white rounded-full flex items-center justify-center text-[var(--color-primary)] text-xs shadow"
                                    >
                                        ✓
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Días seleccionados -->
                    <div v-if="form.dias_preferidos.length > 0" class="mt-6 p-4 bg-[var(--color-primary-soft)] rounded-xl">
                        <p class="text-sm font-medium text-gray-700 mb-2">Días seleccionados:</p>
                        <div class="flex flex-wrap gap-2">
                            <span 
                                v-for="fecha in form.dias_preferidos" 
                                :key="fecha"
                                class="inline-flex items-center gap-1 px-3 py-1 bg-[var(--color-primary)] text-white text-sm rounded-full"
                            >
                                {{ new Date(fecha + 'T12:00:00').toLocaleDateString('es-MX', { weekday: 'short', day: 'numeric', month: 'short' }) }}
                                <button @click="toggleDia(fecha)" class="ml-1 hover:text-red-200">×</button>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- PASO 4: Horario Preferido -->
                <div v-if="currentStep === 4" class="p-6">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-[var(--color-primary-soft)] rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <span class="text-3xl">⏰</span>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900">¿A qué hora prefieres?</h2>
                        <p class="text-gray-500 text-sm mt-1">Selecciona el horario más conveniente</p>
                    </div>
                    
                    <p v-if="stepErrors.horario_preferido" class="text-red-500 text-sm text-center mb-4">{{ stepErrors.horario_preferido }}</p>
                    
                    <div class="space-y-3">
                        <button
                            v-for="(horario, key) in horarios"
                            :key="key"
                            @click="form.horario_preferido = key"
                            type="button"
                            :class="[
                                'w-full p-4 rounded-xl border-2 transition-all duration-200 text-left flex items-center gap-4',
                                form.horario_preferido === key 
                                    ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)] shadow-lg' 
                                    : 'border-gray-200 hover:border-[var(--color-primary)] hover:bg-white'
                            ]"
                        >
                            <span class="text-3xl">{{ horario.emoji }}</span>
                            <div class="flex-1">
                                <div class="font-bold text-gray-900">{{ horario.nombre }}</div>
                                <div class="text-sm text-gray-500">{{ horario.inicio }} - {{ horario.fin }}</div>
                            </div>
                            <div 
                                v-if="form.horario_preferido === key"
                                class="w-6 h-6 bg-[var(--color-primary)] rounded-full flex items-center justify-center text-white text-sm"
                            >
                                ✓
                            </div>
                        </button>
                    </div>
                    
                    <p class="text-gray-400 text-xs text-center mt-4">
                        * El técnico llegará dentro del rango de horario seleccionado
                    </p>
                </div>

                <!-- PASO 5: Detalles del Servicio -->
                <div v-if="currentStep === 5" class="p-6">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-[var(--color-primary-soft)] rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <span class="text-3xl">🔧</span>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900">¿Qué servicio necesitas?</h2>
                        <p class="text-gray-500 text-sm mt-1">Cuéntanos sobre tu equipo y el servicio</p>
                    </div>
                    
                    <div class="space-y-4">
                        <!-- Tipo de Servicio -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Tipo de servicio *
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    v-for="(label, key) in tiposServicio"
                                    :key="key"
                                    @click="form.tipo_servicio = key"
                                    type="button"
                                    :class="[
                                        'p-3 rounded-xl border-2 text-sm font-medium transition-all',
                                        form.tipo_servicio === key 
                                            ? 'border-[var(--color-primary)] bg-[var(--color-primary)] text-white' 
                                            : 'border-gray-200 hover:border-[var(--color-primary)]'
                                    ]"
                                >
                                    {{ label }}
                                </button>
                            </div>
                            <p v-if="stepErrors.tipo_servicio" class="text-red-500 text-xs mt-1">{{ stepErrors.tipo_servicio }}</p>
                        </div>
                        
                        <!-- Tipo de Equipo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Tipo de equipo *
                            </label>
                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    v-for="(label, key) in tiposEquipo"
                                    :key="key"
                                    @click="form.tipo_equipo = key"
                                    type="button"
                                    :class="[
                                        'p-3 rounded-xl border-2 text-xs font-medium transition-all',
                                        form.tipo_equipo === key 
                                            ? 'border-[var(--color-primary)] bg-[var(--color-primary)] text-white' 
                                            : 'border-gray-200 hover:border-[var(--color-primary)]'
                                    ]"
                                >
                                    {{ label }}
                                </button>
                            </div>
                            <p v-if="stepErrors.tipo_equipo" class="text-red-500 text-xs mt-1">{{ stepErrors.tipo_equipo }}</p>
                        </div>
                        
                        <!-- Tienda de Origen -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                ¿Dónde compraste el equipo? *
                            </label>
                            <select 
                                v-model="form.origen_tienda"
                                class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-[var(--color-primary)] focus:ring-0 transition-colors appearance-none bg-white"
                                :class="{ 'border-red-400': stepErrors.origen_tienda }"
                            >
                                <option value="">Seleccionar tienda...</option>
                                <option v-for="(label, key) in tiendas" :key="key" :value="key">
                                    {{ label }}
                                </option>
                            </select>
                            <p v-if="stepErrors.origen_tienda" class="text-red-500 text-xs mt-1">{{ stepErrors.origen_tienda }}</p>
                        </div>
                        
                        <!-- Número de Ticket -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Número de ticket/factura (opcional)
                            </label>
                            <input 
                                v-model="form.numero_ticket_tienda"
                                type="text"
                                placeholder="Ej: FAC-2024-001234"
                                class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-[var(--color-primary)] focus:ring-0 transition-colors"
                            />
                        </div>
                        
                        <!-- Descripción -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Describe el servicio que necesitas
                            </label>
                            <textarea 
                                v-model="form.descripcion"
                                rows="3"
                                placeholder="Ej: Instalación de minisplit en recámara principal, segundo piso..."
                                class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-[var(--color-primary)] focus:ring-0 transition-colors resize-none"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- PASO 6: Confirmación -->
                <div v-if="currentStep === 6" class="p-6">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-[var(--color-primary-soft)] rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <span class="text-3xl">✅</span>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900">Confirma tu solicitud</h2>
                        <p class="text-gray-500 text-sm mt-1">Revisa que todo esté correcto</p>
                    </div>
                    
                    <!-- Resumen -->
                    <div class="space-y-4 mb-6">
                        <!-- Datos personales -->
                        <div class="p-4 bg-white rounded-xl">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-lg">👤</span>
                                <span class="font-semibold text-gray-700">Datos personales</span>
                                <button @click="goToStep(1)" class="ml-auto text-[var(--color-primary)] text-sm">Editar</button>
                            </div>
                            <div class="text-sm text-gray-600">
                                <p><strong>{{ form.nombre }}</strong></p>
                                <p>📱 +52 {{ form.telefono }}</p>
                                <p v-if="form.email">✉️ {{ form.email }}</p>
                            </div>
                        </div>
                        
                        <!-- Dirección -->
                        <div class="p-4 bg-white rounded-xl">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-lg">📍</span>
                                <span class="font-semibold text-gray-700">Dirección</span>
                                <button @click="goToStep(2)" class="ml-auto text-[var(--color-primary)] text-sm">Editar</button>
                            </div>
                            <div class="text-sm text-gray-600">
                                <p>{{ form.direccion_calle }}</p>
                                <p>{{ form.direccion_colonia }} {{ form.direccion_cp ? `C.P. ${form.direccion_cp}` : '' }}</p>
                                <p v-if="form.direccion_referencias" class="text-gray-500 italic mt-1">"{{ form.direccion_referencias }}"</p>
                            </div>
                        </div>
                        
                        <!-- Fecha y Hora -->
                        <div class="p-4 bg-white rounded-xl">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-lg">📅</span>
                                <span class="font-semibold text-gray-700">Fecha y horario</span>
                                <button @click="goToStep(3)" class="ml-auto text-[var(--color-primary)] text-sm">Editar</button>
                            </div>
                            <div class="text-sm text-gray-600">
                                <div class="flex flex-wrap gap-1 mb-1">
                                    <span 
                                        v-for="fecha in form.dias_preferidos" 
                                        :key="fecha"
                                        class="px-2 py-0.5 bg-[var(--color-primary-medium)] text-gray-700 rounded text-xs"
                                    >
                                        {{ new Date(fecha + 'T12:00:00').toLocaleDateString('es-MX', { weekday: 'short', day: 'numeric', month: 'short' }) }}
                                    </span>
                                </div>
                                <p v-if="horarios[form.horario_preferido]">
                                    ⏰ {{ horarios[form.horario_preferido].emoji }} {{ horarios[form.horario_preferido].nombre }} ({{ horarios[form.horario_preferido].inicio }} - {{ horarios[form.horario_preferido].fin }})
                                </p>
                            </div>
                        </div>
                        
                        <!-- Servicio -->
                        <div class="p-4 bg-white rounded-xl">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-lg">🔧</span>
                                <span class="font-semibold text-gray-700">Servicio</span>
                                <button @click="goToStep(5)" class="ml-auto text-[var(--color-primary)] text-sm">Editar</button>
                            </div>
                            <div class="text-sm text-gray-600">
                                <p><strong>{{ tiposServicio[form.tipo_servicio] }}</strong> - {{ tiposEquipo[form.tipo_equipo] }}</p>
                                <p>🏪 Comprado en: {{ tiendas[form.origen_tienda] }}</p>
                                <p v-if="form.numero_ticket_tienda">🎫 Ticket: {{ form.numero_ticket_tienda }}</p>
                                <p v-if="form.descripcion" class="text-gray-500 mt-1">{{ form.descripcion }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Términos -->
                    <div class="mb-6">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input 
                                type="checkbox" 
                                v-model="form.acepta_terminos"
                                class="w-5 h-5 mt-0.5 rounded border-gray-300 text-[var(--color-primary)] focus:ring-[var(--color-primary)]"
                            />
                            <span class="text-sm text-gray-600">
                                Acepto los <a href="#" class="text-[var(--color-primary)] underline">términos y condiciones</a> 
                                y autorizo el uso de mis datos para la prestación del servicio.
                            </span>
                        </label>
                        <p v-if="stepErrors.acepta_terminos" class="text-red-500 text-xs mt-1 ml-8">{{ stepErrors.acepta_terminos }}</p>
                    </div>
                    
                    <!-- Error general del servidor -->
                    <div v-if="form.errors.general" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-red-600 text-sm">
                        {{ form.errors.general }}
                    </div>
                </div>

                <!-- Footer con Botones -->
                <div class="px-6 py-4 bg-white border-t flex items-center justify-between gap-4">
                    <button
                        v-if="currentStep > 1"
                        @click="prevStep"
                        type="button"
                        class="px-5 py-2.5 text-gray-600 font-medium rounded-xl hover:bg-gray-200 transition-colors"
                    >
                        ← Atrás
                    </button>
                    <div v-else></div>
                    
                    <button
                        v-if="currentStep < totalSteps"
                        @click="nextStep"
                        type="button"
                        class="px-6 py-2.5 bg-[var(--color-primary)] text-white font-bold rounded-xl hover:opacity-90 transition-opacity shadow-lg"
                    >
                        Siguiente →
                    </button>
                    
                    <button
                        v-else
                        @click="submitForm"
                        type="button"
                        :disabled="isSubmitting || form.processing"
                        class="px-6 py-2.5 bg-green-500 text-white font-bold rounded-xl hover:bg-green-600 transition-colors shadow-lg disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                    >
                        <svg v-if="isSubmitting || form.processing" class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ isSubmitting || form.processing ? 'Enviando...' : '✓ Confirmar Cita' }}</span>
                    </button>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="text-center py-6 text-gray-400 text-sm">
            <p>{{ empresa?.nombre || 'Climas del Desierto' }} © {{ new Date().getFullYear() }}</p>
            <p class="mt-1">
                <a :href="`tel:${empresa?.telefono}`" class="hover:text-[var(--color-primary)]">{{ empresa?.telefono }}</a>
            </p>
        </footer>
    </div>
</template>
