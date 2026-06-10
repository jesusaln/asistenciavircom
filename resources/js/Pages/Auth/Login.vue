<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, onMounted, nextTick, computed, watch } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { useDarkMode } from '@/Utils/useDarkMode';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const page = usePage();

// Integrar modo oscuro centralizado
useDarkMode(page.props.empresa_config);

const emailInput = ref(null);
const showPassword = ref(false);
const emailTouched = ref(false);
const shakeError = ref(false);
const isAutofilled = ref(false);
const isPasswordManuallyTyped = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: true,
});

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

// Detectar cuando el navegador autocompleta el formulario
const detectAutofill = (event) => {
    // Chrome y otros navegadores marcan los campos autofill con un pseudo-elemento
    // La forma más confiable es verificar si el campo tiene valor al cargar
    setTimeout(() => {
        const emailField = document.getElementById('email');
        const passwordField = document.getElementById('password');
        
        if (passwordField && passwordField.value && !isPasswordManuallyTyped.value) {
            isAutofilled.value = true;
            showPassword.value = false; // Asegurar que está oculto
        }
    }, 100);
};

// Marcar cuando el usuario escribe manualmente en el password
const handlePasswordInput = () => {
    isPasswordManuallyTyped.value = true;
    isAutofilled.value = false;
};

// Solo mostrar el ojito si el usuario escribió manualmente la contraseña
const canShowPasswordToggle = computed(() => {
    return !isAutofilled.value && form.password.length > 0;
});

// Validación de email en tiempo real
const emailIsValid = computed(() => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(form.email);
});

const emailHasError = computed(() => {
    return emailTouched.value && form.email && !emailIsValid.value;
});

// Trigger shake animation on errors
watch(() => form.errors.email || form.errors.password, (newVal) => {
    if (newVal) {
        shakeError.value = true;
        setTimeout(() => { shakeError.value = false; }, 500);
    }
});

// Marcar email como touched al salir del campo
const markEmailTouched = () => {
    emailTouched.value = true;
};

onMounted(() => {
    nextTick(() => {
        if (!emailInput.value) return;
        emailInput.value.focus();

        // Detectar autocompletado después de que el navegador rellene los campos
        // Chrome a veces tarda más en aplicar el autofill
        setTimeout(() => {
            const passwordField = document.getElementById('password');
            if (passwordField && passwordField.value && passwordField.value.length > 0) {
                // Verificar si parece autocompletado (campo tiene valor pero el usuario no ha interactuado)
                if (!isPasswordManuallyTyped.value) {
                    isAutofilled.value = true;
                    showPassword.value = false;
                }
            }
        }, 300);
        
        // Segunda verificación más tardía para navegadores lentos
        setTimeout(() => {
            const passwordField = document.getElementById('password');
            if (passwordField && passwordField.value && passwordField.value.length > 0 && !isPasswordManuallyTyped.value) {
                isAutofilled.value = true;
                showPassword.value = false;
            }
        }, 1000);
    });
});

const submit = () => {
    emailTouched.value = true;
    form.transform(data => ({
        ...data,
        remember: form.remember ? 'on' : '',
    })).post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Iniciar Sesión" />

    <div class="min-h-screen bg-[var(--ui-surface)] flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans transition-colors duration-500 relative overflow-hidden">
        <!-- Background Decorative Elements -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none z-0">
            <div class="absolute -top-24 -left-24 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-brand-600/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s"></div>
        </div>

        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center relative z-10">
            <Link href="/" class="flex justify-center mb-6">
                <img v-if="$page.props.empresa_config?.logo_url" :src="$page.props.empresa_config.logo_url" class="h-20 w-auto object-contain drop-shadow-2xl" :alt="$page.props.empresa_config.nombre_empresa">
                <div v-else class="flex flex-col items-center">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-brand-500 to-brand-600 flex items-center justify-center shadow-xl transform rotate-3">
                        <span class="text-white text-2xl font-black">C</span>
                    </div>
                    <span class="text-2xl font-black text-[var(--ui-text)] mt-4 uppercase tracking-wide">{{ $page.props.empresa_config?.nombre_empresa || 'Climas' }}</span>
                </div>
            </Link>
            <h2 class="text-4xl font-black text-[var(--ui-text)] tracking-tight uppercase">
                Bienvenido
            </h2>
            <p class="mt-2 text-sm text-[var(--ui-text-soft)] font-bold uppercase tracking-wide">
                Acceso Administrativo
            </p>
        </div>

        <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0 relative z-10">
            <div class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-2xl py-10 px-6 sm:px-12 shadow-[0_20px_50px_rgba(0,0,0,0.1)] dark:shadow-[0_20px_50px_rgba(0,0,0,0.5)] sm:rounded-[3rem] border border-white/30 dark:border-slate-800/50 relative overflow-hidden group transition-all duration-500 hover:shadow-brand-500/10" :class="{ 'animate-shake': shakeError }">

                <!-- Status Message -->
                <div v-if="status" class="mb-6 p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 bg-[var(--ui-surface)]/20 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-emerald-300 text-xs font-bold uppercase tracking-wide border border-emerald-100 dark:border-emerald-800 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    {{ status }}
                </div>

                <!-- Error Banner -->
                <div v-if="form.errors.email || form.errors.password" class="mb-6 p-4 rounded-2xl bg-rose-50 dark:bg-rose-900/20 text-rose-800 dark:text-rose-200 dark:text-rose-200 dark:text-rose-300 text-xs font-bold uppercase tracking-wide border border-rose-100 dark:border-rose-800 flex items-center gap-2">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>{{ form.errors.email || form.errors.password }}</span>
                </div>

                <form class="space-y-6 relative z-10" @submit.prevent="submit">
                    <!-- Email Field -->
                    <div>
                        <InputLabel for="email" value="Correo Electrónico" class="dark:text-slate-400 font-bold uppercase text-[10px] tracking-wide" />
                        <div class="relative mt-2">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <TextInput
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="block w-full bg-[var(--ui-surface)] dark:bg-slate-950/50 border-[var(--ui-border)] focus:ring-brand-500 focus:border-brand-500 rounded-2xl pl-12 pr-5 py-4 transition-all"
                                :class="{
                                    'border-emerald-500 focus:ring-brand-500 focus:border-emerald-500': emailIsValid,
                                    'border-rose-500 focus:ring-brand-500 focus:border-rose-500': emailHasError
                                }"
                                required
                                autocomplete="username"
                                ref="emailInput"
                                placeholder="tu@correo.com"
                                @blur="markEmailTouched"
                            />
                            <!-- Validación icon -->
                            <div v-if="emailIsValid || emailHasError" class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                <svg v-if="emailIsValid" class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <svg v-else class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </div>
                        </div>
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <!-- Password Field -->
                    <div>
                        <div class="flex justify-between items-center">
                            <InputLabel for="password" value="Contraseña" class="dark:text-slate-400 font-bold uppercase text-[10px] tracking-wide" />
                            <Link v-if="canResetPassword" :href="route('password.request')" class="text-[10px] font-black uppercase tracking-wide text-brand-500 hover:text-brand-400 transition-colors">
                                Recuperar clave
                            </Link>
                        </div>
                        <div class="relative mt-2">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a6 6 0 00-6-6h-2a6 6 0 00-6 6v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <TextInput
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                class="block w-full bg-[var(--ui-surface)] dark:bg-slate-950/50 border-[var(--ui-border)] focus:ring-brand-500 focus:border-brand-500 rounded-2xl pl-12 pr-14 py-4 transition-all"
                                :class="{ 'pr-12': !canShowPasswordToggle }"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••"
                                @input="handlePasswordInput"
                            />
                            <button
                                v-if="canShowPasswordToggle"
                                type="button"
                                @click="togglePasswordVisibility"
                                class="absolute right-3 top-1/2 -translate-y-1/2 p-2 text-slate-400 hover:text-brand-500 dark:text-slate-500 dark:hover:text-brand-400 transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500 rounded-xl"
                                :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                            >
                                <svg v-if="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                            <!-- Indicador de autocompletado -->
                            <div v-if="isAutofilled && form.password" class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                        </div>
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <label for="remember" class="flex items-center cursor-pointer group">
                            <div class="relative">
                                <Checkbox id="remember" v-model:checked="form.remember" name="remember" class="h-5 w-5 rounded-xl text-brand-500 focus:ring-brand-500 focus:ring-2 dark:bg-slate-950 dark:border-slate-800 transition-all" />
                            </div>
                            <span class="ms-3 text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200 group-hover:text-slate-900 dark:group-hover:text-white transition-colors select-none">Recordar sesión</span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-5 bg-gradient-to-r from-brand-500 to-brand-600 text-white rounded-[1.5rem] font-black text-sm uppercase tracking-[0.2em] shadow-xl shadow-brand-500/20 hover:shadow-xl hover:shadow-xl hover:shadow-2xl hover:shadow-brand-500/30 transition-all flex items-center justify-center gap-3 disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:translate-y-0 disabled:hover:shadow-xl active:scale-95 touch-manipulation shine-effect"
                        >
                            <svg v-if="form.processing" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span v-if="form.processing">Ingresando...</span>
                            <template v-else>
                                <span>Entrar al Panel</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                            </template>
                        </button>
                    </div>

                    <!-- Register Link -->
                    <div class="text-center pt-4 border-t border-[var(--ui-border)]">
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-500">
                            ¿Necesitas una cuenta?
                        </p>
                        <Link :href="route('register')" class="inline-block mt-4 text-xs font-black uppercase tracking-wide text-white bg-gradient-to-r from-slate-800 to-slate-900 dark:from-slate-700 dark:to-slate-800 px-8 py-3 rounded-2xl hover:from-brand-500 hover:to-brand-600 transition-all duration-200 shadow-md hover:shadow-xl hover:shadow-xl hover:shadow-xl.5">
                            Solicitar Registro
                        </Link>
                    </div>
                </form>
            </div>

            <p class="mt-10 text-center text-[9px] font-bold uppercase tracking-[0.4em] text-slate-500 dark:text-slate-700">
                &copy; {{ new Date().getFullYear() }} {{ $page.props.empresa_config?.nombre_empresa }} &bull; Seguridad Certificada
            </p>
        </div>
    </div>
</template>

<style scoped>
.shine-effect {
    position: relative;
    overflow: hidden;
}
.shine-effect::after {
    content: '';
    position: absolute;
    top: -50%;
    left: -100%;
    width: 60%;
    height: 200%;
    background: linear-gradient(
        to right,
        transparent,
        rgba(255, 255, 255, 0.3),
        transparent
    );
    transform: rotate(30deg);
    transition: all 0.5s;
    animation: shine 4s infinite;
}

@keyframes shine {
    0% { left: -100%; }
    20% { left: 150%; }
    100% { left: 150%; }
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-4px); }
    20%, 40%, 60%, 80% { transform: translateX(4px); }
}

.animate-shake {
    animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
}

/* Forzar modo oscuro en campos autocompletados cuando el HTML tiene clase 'dark' */
:deep(.dark input:-webkit-autofill),
:deep(.dark input:-webkit-autofill:hover),
:deep(.dark input:-webkit-autofill:focus),
:deep(.dark input:-internal-autofill-selected) {
    -webkit-box-shadow: 0 0 0px 1000px #0f172a inset !important;
    box-shadow: 0 0 0px 1000px #0f172a inset !important;
    -webkit-text-fill-color: #f8fafc !important;
    caret-color: #f8fafc !important;
    color: #f8fafc !important;
    background-color: #0f172a !important;
    background-clip: content-box !important;
}
</style>
