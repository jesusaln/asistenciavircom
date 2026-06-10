<script setup>
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
    empresa: Object,
    servicios: {
        type: Array,
        default: () => [
            { id: 'instalacion', nombre: 'Instalación de Equipo', icono: 'tools' },
            { id: 'mantenimiento', nombre: 'Mantenimiento Preventivo', icono: 'cogs' },
            { id: 'reparacion', nombre: 'Reparación/Emergencia', icono: 'bolt' },
            { id: 'cotizacion', nombre: 'Cotización de Proyecto', icono: 'clipboard-list' },
        ]
    },
    initialService: {
        type: String,
        default: null
    },
    isSimplified: {
        type: Boolean,
        default: false
    }
});

const isSubmitting = ref(false);
const showSuccess = ref(false);
const selectedService = ref(props.initialService);
const errorMessage = ref('');
const showSessionExpired = ref(false);
const acceptedTerms = ref(false);

const form = useForm({
    nombre: '',
    telefono: '',
    email: '',
    servicio: props.initialService || '',
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
    today.setDate(today.getDate() + 1);
    return today.toISOString().split('T')[0];
});

const horasDisponibles = [
    '08:00', '09:00', '10:00', '11:00', '12:00',
    '13:00', '14:00', '15:00', '16:00', '17:00', '18:00'
];

const selectService = (servicio) => {
    selectedService.value = servicio.id;
    form.servicio = servicio.id;
};

const reloadPage = () => {
    window.location.reload();
};

const getServiceIcon = (icono) => {
    if (!icono) return 'clipboard-list';
    const containsEmoji = /[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}]/u.test(icono);
    return containsEmoji ? 'clipboard-list' : icono;
};

const handlePhoneInput = (e) => {
    const cleanValue = e.target.value.replace(/\D/g, '').substring(0, 10);
    form.telefono = cleanValue;
};

const isPhoneValid = computed(() => {
    return form.telefono.length === 10;
});

const canSubmit = computed(() => {
    if (props.isSimplified) {
        return form.nombre && isPhoneValid.value && acceptedTerms.value;
    }
    return form.nombre && form.telefono && form.servicio && isPhoneValid.value;
});

const submitForm = () => {
    if (!canSubmit.value) return;

    errorMessage.value = '';
    showSessionExpired.value = false;
    isSubmitting.value = true;

    form.post(route('public.cita.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showSuccess.value = true;
            form.reset();
            acceptedTerms.value = false;
            if (props.initialService) {
                form.servicio = props.initialService;
                selectedService.value = props.initialService;
            } else {
                selectedService.value = null;
            }
            setTimeout(() => {
                showSuccess.value = false;
            }, 8000);
        },
        onError: (errors) => {
            if (Object.keys(errors).length === 0) {
                showSessionExpired.value = true;
                return;
            }
            if (errors.hora_preferida) {
                errorMessage.value = errors.hora_preferida;
            } else if (errors.general) {
                errorMessage.value = errors.general;
            } else {
                // Mostrar errores de validación genéricos
                const firstError = Object.values(errors)[0];
                errorMessage.value = typeof firstError === 'string' ? firstError : 'Hubo un problema al enviar tu solicitud. Por favor verifica tus datos e intenta de nuevo.';
            }
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
};
</script>

<template>
    <section id="agendar-cita" :style="cssVars">
        <!-- ============================================ -->
        <!-- SIMPLIFIED MODE: Premium inline section -->
        <!-- ============================================ -->
        <template v-if="isSimplified">
            <div class="relative overflow-hidden bg-gray-950 py-20 md:py-28">
                <!-- Decorative blurs -->
                <div class="absolute top-0 left-1/3 w-[600px] h-[600px] bg-[var(--color-primary)] rounded-full blur-[250px] opacity-[0.07]"></div>
                <div class="absolute bottom-0 right-0 w-[400px] h-[400px] bg-blue-600 rounded-full blur-[200px] opacity-[0.05]"></div>
                <!-- Subtle grid pattern -->
                <div class="absolute inset-0 opacity-[0.03]" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23ffffff&quot; fill-opacity=&quot;0.4&quot;%3E%3Cpath d=&quot;M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>

                <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <!-- Success Message -->
                    <Transition
                        enter-active-class="transition-all duration-500 ease-out"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition-all duration-300 ease-in"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div v-if="showSuccess" class="max-w-2xl mx-auto mb-10">
                            <div class="p-8 bg-emerald-500/10 border border-emerald-500/20 rounded-3xl backdrop-blur-sm text-center">
                                <div class="w-16 h-16 bg-emerald-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <h4 class="text-2xl font-black text-white mb-2">¡Cita Registrada con Éxito!</h4>
                                <p class="text-emerald-300 text-base">Uno de nuestros asesores te contactará en las próximas horas para confirmar los detalles de tu visita.</p>
                            </div>
                        </div>
                    </Transition>

                    <!-- Error / Session Messages -->
                    <Transition enter-active-class="transition-all duration-500 ease-out" enter-from-class="opacity-0 -translate-y-4" enter-to-class="opacity-100 translate-y-0">
                        <div v-if="errorMessage" class="max-w-2xl mx-auto mb-10 p-6 bg-rose-500/10 border border-rose-500/20 rounded-2xl flex items-center gap-4">
                            <font-awesome-icon icon="triangle-exclamation" class="text-rose-400 text-2xl" />
                            <p class="text-rose-300 text-sm flex-1">{{ errorMessage }}</p>
                            <button @click="errorMessage = ''" class="text-rose-400 hover:text-rose-300"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                        </div>
                    </Transition>
                    <Transition enter-active-class="transition-all duration-500 ease-out" enter-from-class="opacity-0 -translate-y-4" enter-to-class="opacity-100 translate-y-0">
                        <div v-if="showSessionExpired" class="max-w-2xl mx-auto mb-10 p-6 bg-brand-500/10 border border-brand-500/20 rounded-2xl">
                            <div class="flex items-start gap-4">
                                <font-awesome-icon icon="sync" class="text-brand-400 text-2xl" />
                                <div class="flex-1">
                                    <p class="text-brand-300 text-sm mb-3">Tu sesión ha expirado. Por favor, recarga la página e intenta nuevamente.</p>
                                    <button @click="reloadPage" class="px-4 py-2 bg-brand-500 text-white rounded-xl font-bold text-sm hover:bg-brand-600 transition-colors">Recargar Página</button>
                                </div>
                            </div>
                        </div>
                    </Transition>

                    <div class="grid lg:grid-cols-2 gap-12 xl:gap-20 items-center">
                        <!-- Left Column: Copy & Trust -->
                        <div>
                            <span class="inline-flex items-center gap-2 px-4 py-2 bg-white/5 border border-white/10 rounded-full mb-8">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                                <span class="text-emerald-400 text-[10px] font-black uppercase tracking-[0.2em]">Respondemos en menos de 2 horas</span>
                            </span>

                            <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white tracking-tight leading-[0.95] mb-6">
                                Agenda tu<br>
                                <span class="text-transparent bg-clip-text bg-gradient-to-r from-[var(--color-primary)] to-amber-400">Visita Técnica</span>
                            </h2>
                            <p class="text-lg md:text-xl text-slate-400 leading-relaxed mb-10 max-w-lg">
                                Déjanos tu nombre y teléfono. Un asesor especializado te llamará para confirmar fecha y horario.
                            </p>

                            <!-- Trust Indicators -->
                            <div class="space-y-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-white/5 border border-white/10 rounded-2xl flex items-center justify-center text-xl">
                                        <font-awesome-icon icon="shield-halved" />
                                    </div>
                                    <div>
                                        <p class="text-white font-bold text-sm">Técnicos Certificados</p>
                                        <p class="text-slate-500 text-xs">Personal capacitado y uniformado</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-white/5 border border-white/10 rounded-2xl flex items-center justify-center text-xl">
                                        <font-awesome-icon icon="check-circle" />
                                    </div>
                                    <div>
                                        <p class="text-white font-bold text-sm">Garantía por Escrito</p>
                                        <p class="text-slate-500 text-xs">En cada servicio que realizamos</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-white/5 border border-white/10 rounded-2xl flex items-center justify-center text-xl">
                                        <font-awesome-icon icon="lock" />
                                    </div>
                                    <div>
                                        <p class="text-white font-bold text-sm">Datos Protegidos</p>
                                        <p class="text-slate-500 text-xs">Tu información está segura con nosotros</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Form Card -->
                        <div class="relative">
                            <!-- Glow behind card -->
                            <div class="absolute -inset-4 bg-gradient-to-br from-[var(--color-primary)]/20 to-brand-500/10 rounded-[3rem] blur-2xl opacity-40"></div>

                            <div class="relative bg-white dark:bg-slate-800 rounded-[2rem] p-8 md:p-10 shadow-2xl border border-slate-100 dark:border-slate-700">
                                <!-- Form Header -->
                                <div class="text-center mb-8">
                                    <div class="w-16 h-16 bg-[var(--color-primary-soft)] rounded-2xl flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-8 h-8 text-[var(--color-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-2xl font-black text-slate-900 dark:text-white">Solicita tu Visita</h3>
                                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Completa estos datos y te llamamos</p>
                                </div>

                                <form @submit.prevent="submitForm" class="space-y-5">
                                    <!-- Nombre -->
                                    <div>
                                        <label for="simplified-nombre" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                                            <span class="inline-flex items-center gap-2">
                                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                                Nombre completo
                                            </span>
                                        </label>
                                        <input
                                            id="simplified-nombre"
                                            v-model="form.nombre"
                                            type="text"
                                            required
                                            placeholder="Ej: Juan Pérez"
                                            class="w-full px-5 py-4 rounded-xl bg-slate-50 dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-700 text-slate-900 dark:text-white text-base outline-none transition-all focus:border-[var(--color-primary)] focus:bg-white dark:focus:bg-slate-800 focus:shadow-lg focus:shadow-[var(--color-primary)]/5 placeholder:text-slate-400"
                                        >
                                    </div>

                                    <!-- Teléfono -->
                                    <div>
                                        <div class="flex items-end justify-between mb-2">
                                            <label for="simplified-telefono" class="block text-sm font-bold text-slate-700 dark:text-slate-300">
                                                <span class="inline-flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                                    Teléfono (10 dígitos)
                                                </span>
                                            </label>
                                            <Transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 translate-x-2" enter-to-class="opacity-100 translate-x-0">
                                                <span v-if="form.telefono && !isPhoneValid" class="text-[10px] text-rose-500 font-extrabold animate-pulse">
                                                    {{ 10 - form.telefono.length }} dígitos restantes
                                                </span>
                                                <span v-else-if="isPhoneValid" class="text-[10px] text-emerald-500 font-extrabold flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                                    Válido
                                                </span>
                                            </Transition>
                                        </div>
                                        <input
                                            id="simplified-telefono"
                                            :value="form.telefono"
                                            @input="handlePhoneInput"
                                            type="tel"
                                            required
                                            maxlength="10"
                                            placeholder="6621234567"
                                            class="w-full px-5 py-4 rounded-xl bg-slate-50 dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-700 text-slate-900 dark:text-white text-base outline-none transition-all focus:border-[var(--color-primary)] focus:bg-white dark:focus:bg-slate-800 focus:shadow-lg focus:shadow-[var(--color-primary)]/5 placeholder:text-slate-400"
                                            :class="{
                                                'border-rose-300 bg-rose-50 dark:bg-rose-900/10': form.telefono && !isPhoneValid,
                                                'border-emerald-400 bg-emerald-50/50 dark:bg-emerald-900/10': isPhoneValid
                                            }"
                                        >
                                    </div>

                                    <!-- Terms & Privacy Checkbox -->
                                    <div class="pt-2">
                                        <label class="flex items-start gap-3 cursor-pointer group select-none">
                                            <div class="relative flex-shrink-0 mt-0.5">
                                                <input
                                                    type="checkbox"
                                                    v-model="acceptedTerms"
                                                    class="sr-only peer"
                                                >
                                                <div class="w-5 h-5 rounded-xl border-2 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 transition-all peer-checked:bg-[var(--color-primary)] peer-checked:border-[var(--color-primary)] group-hover:border-[var(--color-primary)]/50 flex items-center justify-center">
                                                    <svg class="w-3 h-3 text-white opacity-0 peer-checked:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="4">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </div>
                                                <!-- Checkmark that shows when checked -->
                                                <svg v-if="acceptedTerms" class="absolute inset-0 w-5 h-5 text-white p-[3px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            <span class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                                Acepto el
                                                <Link :href="route('public.privacidad')" class="text-[var(--color-primary)] font-bold hover:underline">Aviso de Privacidad</Link>
                                                y los
                                                <Link :href="route('public.terminos')" class="text-[var(--color-primary)] font-bold hover:underline">Términos y Condiciones</Link>
                                                del servicio.
                                            </span>
                                        </label>
                                    </div>

                                    <!-- Submit Button -->
                                    <button
                                        type="submit"
                                        :disabled="!canSubmit || isSubmitting"
                                        class="w-full py-4 md:py-5 bg-gradient-to-r from-[var(--color-primary)] to-[var(--color-primary)] text-white rounded-xl font-black text-lg tracking-wide shadow-xl shadow-[var(--color-primary)]/25 transition-all duration-300 flex items-center justify-center gap-3 disabled:opacity-40 disabled:grayscale disabled:cursor-not-allowed disabled:shadow-none enabled:hover:shadow-2xl enabled:hover:shadow-[var(--color-primary)]/40 enabled:hover:-translate-y-0.5 enabled:hover:brightness-110"
                                    >
                                        <template v-if="isSubmitting">
                                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Registrando tu cita...
                                        </template>
                                        <template v-else>
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            Agendar Mi Cita
                                        </template>
                                    </button>

                                    <!-- Micro-trust -->
                                    <div class="flex items-center justify-center gap-4 pt-2">
                                        <div class="flex items-center gap-1.5 text-slate-400 text-[10px] font-bold uppercase tracking-wider">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                            Conexión segura
                                        </div>
                                        <span class="text-slate-300 dark:text-slate-600">•</span>
                                        <div class="flex items-center gap-1.5 text-slate-400 text-[10px] font-bold uppercase tracking-wider">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                            Sin spam
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- ============================================ -->
        <!-- FULL MODE: Complete form with service select -->
        <!-- ============================================ -->
        <template v-else>
            <div class="py-20 md:py-24 bg-gradient-to-b from-white to-slate-50 dark:from-slate-900 dark:to-slate-950 relative overflow-hidden transition-colors duration-300">
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
                        <h2 class="text-3xl md:text-5xl font-black text-slate-900 dark:text-white tracking-tight mb-4 transition-colors">
                            Agenda tu <span class="text-[var(--color-primary)]">Cita</span> Hoy
                        </h2>
                        <p class="text-lg text-slate-500 dark:text-slate-400 max-w-2xl mx-auto transition-colors">
                            Completa el formulario y nos pondremos en contacto contigo para confirmar tu cita.
                        </p>
                    </div>

                    <!-- Success / Error Messages -->
                    <Transition enter-active-class="transition-all duration-500 ease-out" enter-from-class="opacity-0 -translate-y-4" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-300 ease-in" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-4">
                        <div v-if="showSuccess" class="mb-8 p-6 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-2xl flex items-center gap-4">
                            <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-800 rounded-full flex items-center justify-center text-2xl"><font-awesome-icon icon="check-circle" /></div>
                            <div><h4 class="font-bold text-emerald-800 dark:text-emerald-300">¡Cita Registrada!</h4><p class="text-emerald-700 dark:text-emerald-400 text-sm">Te contactaremos pronto para confirmar los detalles.</p></div>
                        </div>
                    </Transition>
                    <Transition enter-active-class="transition-all duration-500 ease-out" enter-from-class="opacity-0 -translate-y-4" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-300 ease-in" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-4">
                        <div v-if="errorMessage" class="mb-8 p-6 bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 rounded-2xl flex items-center gap-4">
                            <div class="w-12 h-12 bg-rose-100 dark:bg-rose-800 rounded-full flex items-center justify-center text-2xl"><font-awesome-icon icon="triangle-exclamation" /></div>
                            <div class="flex-1"><h4 class="font-bold text-rose-800 dark:text-rose-300">Horario No Disponible</h4><p class="text-rose-700 dark:text-rose-400 text-sm">{{ errorMessage }}</p></div>
                            <button @click="errorMessage = ''" class="text-rose-400 hover:text-rose-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                        </div>
                    </Transition>
                    <Transition enter-active-class="transition-all duration-500 ease-out" enter-from-class="opacity-0 -translate-y-4" enter-to-class="opacity-100 translate-y-0">
                        <div v-if="showSessionExpired" class="mb-8 p-6 bg-brand-50 dark:bg-brand-900/30 border border-brand-200 dark:border-brand-800 rounded-2xl">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-brand-100 dark:bg-brand-800 rounded-full flex items-center justify-center text-2xl flex-shrink-0"><font-awesome-icon icon="sync" /></div>
                                <div class="flex-1">
                                    <h4 class="font-bold text-brand-800 dark:text-brand-300 mb-1">Sesión Expirada</h4>
                                    <p class="text-brand-700 dark:text-brand-400 text-sm mb-4">Tu sesión ha expirado. Por favor, recarga la página.</p>
                                    <button @click="reloadPage" class="px-5 py-2.5 bg-brand-500 text-white rounded-xl font-bold text-sm hover:bg-brand-600 transition-colors">Recargar</button>
                                </div>
                            </div>
                        </div>
                    </Transition>

                    <div class="grid lg:grid-cols-2 gap-12 items-start">
                        <!-- Left: Service Selection -->
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6 transition-colors">1. ¿Qué servicio necesitas?</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <button
                                    v-for="servicio in servicios"
                                    :key="servicio.id"
                                    @click="selectService(servicio)"
                                    class="p-6 rounded-2xl border-2 text-left transition-all duration-300 group"
                                    :class="selectedService === servicio.id
                                        ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)] dark:bg-[var(--color-primary)]/20 shadow-lg'
                                        : 'border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-[var(--color-primary)]/30 hover:shadow-md'"
                                >
                                    <span class="text-3xl mb-4 block group-hover:scale-110 transition-transform"><font-awesome-icon :icon="getServiceIcon(servicio.icono)" /></span>
                                    <span class="font-bold text-slate-900 dark:text-white block text-sm transition-colors">{{ servicio.nombre }}</span>
                                </button>
                            </div>
                            <div class="mt-10 p-6 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700 transition-colors">
                                <p class="text-xs font-black text-slate-400 uppercase tracking-wide mb-4">¿Por qué agendar con nosotros?</p>
                                <div class="space-y-3">
                                    <div v-for="t in ['Respuesta en menos de 2 horas', 'Técnicos certificados y uniformados', 'Garantía por escrito en todos los servicios']" :key="t" class="flex items-center gap-3">
                                        <span class="w-8 h-8 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center text-emerald-600 dark:text-emerald-400 text-sm"><font-awesome-icon icon="check" /></span>
                                        <span class="text-slate-700 dark:text-slate-300 text-sm font-medium transition-colors">{{ t }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Form -->
                        <div class="bg-white dark:bg-slate-800 p-8 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-700 transition-colors">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6 transition-colors">2. Tus datos de contacto</h3>
                            <form @submit.prevent="submitForm" class="space-y-5">
                                <div>
                                    <label for="full-nombre" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Nombre completo *</label>
                                    <input id="full-nombre" v-model="form.nombre" type="text" required placeholder="Ej: Juan Pérez" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-white focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary)]/20 transition-all outline-none">
                                </div>
                                <div>
                                    <div class="flex justify-between items-end mb-2">
                                        <label for="full-telefono" class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Teléfono *</label>
                                        <span v-if="form.telefono && !isPhoneValid" class="text-[10px] text-rose-500 font-bold animate-pulse">10 dígitos requeridos</span>
                                    </div>
                                    <input id="full-telefono" :value="form.telefono" @input="handlePhoneInput" type="tel" required maxlength="10" placeholder="6621234567" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-white focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary)]/20 transition-all outline-none" :class="{'border-rose-300': form.telefono && !isPhoneValid}">
                                </div>
                                <div>
                                    <label for="full-email" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Email (opcional)</label>
                                    <input id="full-email" v-model="form.email" type="email" placeholder="tu@email.com" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-white focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary)]/20 transition-all outline-none">
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="full-fecha" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Fecha preferida</label>
                                        <input id="full-fecha" v-model="form.fecha_preferida" type="date" :min="minDate" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:[color-scheme:dark] focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary)]/20 transition-all outline-none">
                                    </div>
                                    <div>
                                        <label for="full-hora" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Hora preferida</label>
                                        <select id="full-hora" v-model="form.hora_preferida" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary)]/20 transition-all outline-none bg-white dark:bg-slate-900 dark:text-white">
                                            <option value="">Seleccionar</option>
                                            <option v-for="hora in horasDisponibles" :key="hora" :value="hora">{{ hora }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label for="full-descripcion" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Detalles adicionales</label>
                                    <textarea id="full-descripcion" v-model="form.descripcion" rows="3" placeholder="Describe brevemente tu necesidad..." class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-white focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary)]/20 transition-all outline-none resize-none"></textarea>
                                </div>
                                <button type="submit" :disabled="isSubmitting || !form.nombre || !form.telefono || !form.servicio || !isPhoneValid" class="w-full py-4 bg-[var(--color-primary)] text-white rounded-xl font-bold text-lg hover:shadow-lg hover:shadow-[var(--color-primary)]/25 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3">
                                    <template v-if="isSubmitting"><svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Enviando...</template>
                                    <template v-else><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>Agendar Cita</template>
                                </button>
                                <p class="text-center text-xs text-slate-400 mt-4">
                                    Al enviar, aceptas nuestro <Link :href="route('public.privacidad')" class="text-[var(--color-primary)] hover:underline">Aviso de Privacidad</Link> y <Link :href="route('public.terminos')" class="text-[var(--color-primary)] hover:underline">Términos y Condiciones</Link>.
                                </p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </section>
</template>
