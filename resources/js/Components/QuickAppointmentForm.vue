<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    empresa: Object,
    servicios: {
        type: Array,
        default: () => [
            { id: 'instalacion', nombre: 'Instalación de Equipo', icono: '🔧' },
            { id: 'mantenimiento', nombre: 'Mantenimiento Preventivo', icono: '⚙️' },
            { id: 'reparacion', nombre: 'Reparación/Emergencia', icono: '⚡' },
            { id: 'cotizacion', nombre: 'Cotización de Proyecto', icono: '📋' },
        ]
    }
});

const isSubmitting = ref(false);
const showSuccess = ref(false);
const selectedService = ref(null);
const errorMessage = ref('');
const showSessionExpired = ref(false); // Para manejar error 419

const form = useForm({
    nombre: '',
    telefono: '',
    email: '',
    servicio: '',
    fecha_preferida: '',
    hora_preferida: '',
    descripcion: '',
});

const cssVars = computed(() => ({
    '--color-primary': props.empresa?.color_principal || '#FF6B35',
    '--color-primary-soft': (props.empresa?.color_principal || '#FF6B35') + '15',
}));

const minDate = computed(() => {
    const today = new Date();
    today.setDate(today.getDate() + 1); // Mínimo mañana
    return today.toISOString().split('T')[0];
});

const horasDisponibles = [
    '08:00', '09:00', '10:00', '11:00', '12:00',
    '13:00', '14:00', '15:00', '16:00', '17:00', '18:00'
];

const selectService = (servicio) => {
    selectedService.value = servicio.id;
    form.servicio = servicio.id; // Enviar el ID, no el nombre
};

const reloadPage = () => {
    window.location.reload();
};

const submitForm = () => {
    if (!form.nombre || !form.telefono || !form.servicio) {
        return;
    }
    
    errorMessage.value = '';
    showSessionExpired.value = false;
    isSubmitting.value = true;
    
    form.post(route('public.cita.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showSuccess.value = true;
            form.reset();
            selectedService.value = null;
            setTimeout(() => {
                showSuccess.value = false;
            }, 5000);
        },
        onError: (errors) => {
            // Detectar error 419 (CSRF / Sesión expirada)
            // Inertia convierte el 419 en un error con página completa o un objeto vacío
            if (Object.keys(errors).length === 0) {
                // Probablemente es un error 419
                showSessionExpired.value = true;
                return;
            }
            
            // Mostrar error de validación del backend
            if (errors.hora_preferida) {
                errorMessage.value = errors.hora_preferida;
            } else if (errors.general) {
                errorMessage.value = errors.general;
            } else {
                // Fallback: Redirigir a WhatsApp
                const servicioNombre = props.servicios.find(s => s.id === form.servicio)?.nombre || form.servicio;
                const mensaje = `Hola, me gustaría agendar una cita para ${servicioNombre}. Mi nombre es ${form.nombre} y mi teléfono es ${form.telefono}.`;
                const whatsappUrl = `https://wa.me/${props.empresa?.whatsapp?.replace(/\D/g, '')}?text=${encodeURIComponent(mensaje)}`;
                window.open(whatsappUrl, '_blank');
            }
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
};
</script>

<template>
    <section id="agendar-cita" class="py-24 bg-gradient-to-b from-white to-gray-50 relative overflow-hidden" :style="cssVars">
        <!-- Decorative Elements -->
        <div class="absolute top-0 left-0 w-96 h-96 bg-[var(--color-primary)] rounded-full blur-[200px] opacity-5 -translate-x-1/2 -translate-y-1/2"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-blue-500 rounded-full blur-[200px] opacity-5 translate-x-1/2 translate-y-1/2"></div>
        
        <div class="max-w-6xl mx-auto px-4 relative z-10">
            <!-- Header -->
            <div class="text-center mb-16">
                <span class="inline-flex items-center gap-2 px-4 py-2 bg-[var(--color-primary-soft)] rounded-full mb-6">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[var(--color-primary)] opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-[var(--color-primary)]"></span>
                    </span>
                    <span class="text-[var(--color-primary)] text-[10px] font-black uppercase tracking-[0.2em]">Agenda en 2 minutos</span>
                </span>
                
                <h2 class="text-3xl md:text-5xl font-black text-gray-900 tracking-tight mb-4">
                    Agenda tu <span class="text-[var(--color-primary)]">Cita</span> Hoy
                </h2>
                <p class="text-lg text-gray-500 max-w-2xl mx-auto">
                    Completa el formulario y nos pondremos en contacto contigo para confirmar tu cita.
                </p>
            </div>
            
            <!-- Success Message -->
            <Transition
                enter-active-class="transition-all duration-500 ease-out"
                enter-from-class="opacity-0 -translate-y-4"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-300 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-4"
            >
                <div v-if="showSuccess" class="mb-8 p-6 bg-green-50 border border-green-200 rounded-2xl flex items-center gap-4">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center text-2xl">
                        ✅
                    </div>
                    <div>
                        <h4 class="font-bold text-green-800">¡Cita Registrada!</h4>
                        <p class="text-green-700 text-sm">Te contactaremos pronto para confirmar los detalles.</p>
                    </div>
                </div>
            </Transition>

            <!-- Error Message -->
            <Transition
                enter-active-class="transition-all duration-500 ease-out"
                enter-from-class="opacity-0 -translate-y-4"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-300 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-4"
            >
                <div v-if="errorMessage" class="mb-8 p-6 bg-red-50 border border-red-200 rounded-2xl flex items-center gap-4">
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center text-2xl">
                        ⚠️
                    </div>
                    <div class="flex-1">
                        <h4 class="font-bold text-red-800">Horario No Disponible</h4>
                        <p class="text-red-700 text-sm">{{ errorMessage }}</p>
                    </div>
                    <button @click="errorMessage = ''" class="text-red-400 hover:text-red-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </Transition>

            <!-- Session Expired Message (Error 419) -->
            <Transition
                enter-active-class="transition-all duration-500 ease-out"
                enter-from-class="opacity-0 -translate-y-4"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-300 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-4"
            >
                <div v-if="showSessionExpired" class="mb-8 p-6 bg-amber-50 border border-amber-200 rounded-2xl">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center text-2xl flex-shrink-0">
                            🔄
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-amber-800 mb-1">Sesión Expirada</h4>
                            <p class="text-amber-700 text-sm mb-4">
                                Tu sesión ha expirado por inactividad. Por favor, recarga la página e intenta nuevamente.
                            </p>
                            <button 
                                @click="reloadPage"
                                class="px-5 py-2.5 bg-amber-500 text-white rounded-xl font-bold text-sm hover:bg-amber-600 transition-colors flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Recargar Página
                            </button>
                        </div>
                        <button @click="showSessionExpired = false" class="text-amber-400 hover:text-amber-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </Transition>

            <div class="grid lg:grid-cols-2 gap-12 items-start">
                <!-- Left: Service Selection -->
                <div>
                    <h3 class="text-lg font-bold text-gray-900 mb-6">1. ¿Qué servicio necesitas?</h3>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <button
                            v-for="servicio in servicios"
                            :key="servicio.id"
                            @click="selectService(servicio)"
                            class="p-6 rounded-2xl border-2 text-left transition-all duration-300 group"
                            :class="selectedService === servicio.id 
                                ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)] shadow-lg' 
                                : 'border-gray-100 bg-white hover:border-[var(--color-primary)]/30 hover:shadow-md'"
                        >
                            <span class="text-3xl mb-4 block group-hover:scale-110 transition-transform">{{ servicio.icono }}</span>
                            <span class="font-bold text-gray-900 block text-sm">{{ servicio.nombre }}</span>
                        </button>
                    </div>
                    
                    <!-- Trust Badges -->
                    <div class="mt-10 p-6 bg-gray-50 rounded-2xl border border-gray-100">
                        <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4">¿Por qué agendar con nosotros?</p>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center text-green-600 text-sm">✓</span>
                                <span class="text-gray-700 text-sm font-medium">Respuesta en menos de 2 horas</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center text-green-600 text-sm">✓</span>
                                <span class="text-gray-700 text-sm font-medium">Técnicos certificados y uniformados</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center text-green-600 text-sm">✓</span>
                                <span class="text-gray-700 text-sm font-medium">Garantía por escrito en todos los servicios</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right: Form -->
                <div class="bg-white p-8 rounded-3xl shadow-xl border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 mb-6">2. Tus datos de contacto</h3>
                    
                    <form @submit.prevent="submitForm" class="space-y-5">
                        <!-- Nombre -->
                        <div>
                            <label for="appointment-nombre" class="block text-sm font-semibold text-gray-700 mb-2">Nombre completo *</label>
                            <input
                                id="appointment-nombre"
                                v-model="form.nombre"
                                type="text"
                                required
                                placeholder="Ej: Juan Pérez"
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary)]/20 transition-all outline-none"
                            >
                        </div>
                        
                        <!-- Teléfono -->
                        <div>
                            <label for="appointment-telefono" class="block text-sm font-semibold text-gray-700 mb-2">Teléfono *</label>
                            <input
                                id="appointment-telefono"
                                v-model="form.telefono"
                                type="tel"
                                required
                                placeholder="Ej: 614 123 4567"
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary)]/20 transition-all outline-none"
                            >
                        </div>
                        
                        <!-- Email (opcional) -->
                        <div>
                            <label for="appointment-email" class="block text-sm font-semibold text-gray-700 mb-2">Email (opcional)</label>
                            <input
                                id="appointment-email"
                                v-model="form.email"
                                type="email"
                                placeholder="tu@email.com"
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary)]/20 transition-all outline-none"
                            >
                        </div>
                        
                        <!-- Fecha y Hora en grid -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="appointment-fecha" class="block text-sm font-semibold text-gray-700 mb-2">Fecha preferida</label>
                                <input
                                    id="appointment-fecha"
                                    v-model="form.fecha_preferida"
                                    type="date"
                                    :min="minDate"
                                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary)]/20 transition-all outline-none"
                                >
                            </div>
                            <div>
                                <label for="appointment-hora" class="block text-sm font-semibold text-gray-700 mb-2">Hora preferida</label>
                                <select
                                    id="appointment-hora"
                                    v-model="form.hora_preferida"
                                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary)]/20 transition-all outline-none bg-white"
                                >
                                    <option value="">Seleccionar</option>
                                    <option v-for="hora in horasDisponibles" :key="hora" :value="hora">{{ hora }}</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Descripción -->
                        <div>
                            <label for="appointment-descripcion" class="block text-sm font-semibold text-gray-700 mb-2">Detalles adicionales</label>
                            <textarea
                                id="appointment-descripcion"
                                v-model="form.descripcion"
                                rows="3"
                                placeholder="Describe brevemente tu necesidad..."
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary)]/20 transition-all outline-none resize-none"
                            ></textarea>
                        </div>
                        
                        <!-- Submit Button -->
                        <button
                            type="submit"
                            :disabled="isSubmitting || !form.nombre || !form.telefono || !form.servicio"
                            class="w-full py-4 bg-[var(--color-primary)] text-white rounded-xl font-bold text-lg hover:shadow-lg hover:shadow-[var(--color-primary)]/25 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3"
                        >
                            <template v-if="isSubmitting">
                                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Enviando...
                            </template>
                            <template v-else>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Agendar Cita
                            </template>
                        </button>
                        
                        <p class="text-center text-xs text-gray-400 mt-4">
                            Al enviar, aceptas nuestros <a href="#" class="text-[var(--color-primary)] hover:underline">términos de servicio</a>.
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </section>
</template>
