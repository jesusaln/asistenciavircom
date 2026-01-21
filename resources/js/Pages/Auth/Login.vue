<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, onMounted, nextTick, computed } from 'vue';
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

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

onMounted(() => {
    nextTick(() => {
        if (!document.activeElement || document.activeElement === document.body) {
            emailInput.value?.focus();
        }
    });
});

const submit = () => {
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

    <div class="min-h-screen bg-white dark:bg-gray-900 flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans transition-colors duration-300">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <Link href="/" class="flex justify-center mb-6">
                <img v-if="$page.props.empresa_config?.logo_url" :src="$page.props.empresa_config.logo_url" class="h-20 w-auto object-contain" :alt="$page.props.empresa_config.nombre_empresa">
                <div v-else class="flex flex-col items-center">
                    <span class="text-4xl">🔒</span>
                    <span class="text-2xl font-black text-gray-900 dark:text-white mt-2 uppercase tracking-tight transition-colors">{{ $page.props.empresa_config?.nombre_empresa || 'Vircom' }}</span>
                </div>
            </Link>
            <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight uppercase transition-colors">
                Bienvenido al Panel
            </h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 font-medium transition-colors">
                Accede a tus herramientas administrativas
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
            <div class="bg-white dark:bg-gray-900 py-10 px-6 sm:px-12 shadow-2xl shadow-gray-200/50 dark:shadow-none sm:rounded-[3rem] border border-gray-100 dark:border-gray-800 relative overflow-hidden group transition-colors">
                 <!-- Decoración -->
                <div class="absolute -top-12 -right-12 w-32 h-32 bg-[var(--color-primary-soft)] rounded-full group-hover:scale-110 transition-transform duration-500"></div>

                <div v-if="status" class="mb-6 p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 text-xs font-bold uppercase tracking-widest border border-emerald-100 dark:border-emerald-700 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    {{ status }}
                </div>

                <form class="space-y-6 relative z-10" @submit.prevent="submit">
                    <div>
                        <InputLabel for="email" value="Usuario o Email" />
                        <TextInput
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="mt-2 block w-full"
                            required
                            autocomplete="username"
                            ref="emailInput"
                            placeholder="nombre@empresa.com"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div>
                        <div class="flex justify-between items-center">
                            <InputLabel for="password" value="Contraseña" />
                            <Link v-if="canResetPassword" :href="route('password.request')" class="text-[10px] font-black uppercase tracking-widest text-[var(--color-primary)] hover:underline">
                                ¿Olvidaste tu clave?
                            </Link>
                        </div>
                        <TextInput
                            id="password"
                            v-model="form.password"
                            type="password"
                            class="mt-2 block w-full"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                        />
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div class="flex items-center">
                        <Checkbox id="remember" v-model:checked="form.remember" name="remember" />
                        <label for="remember" class="ms-3 text-xs font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 cursor-pointer hover:text-gray-600 dark:hover:text-gray-300 transition-colors">Mantener sesión iniciada</label>
                    </div>

                    <div class="pt-2">
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="w-full py-5 bg-[var(--color-primary)] text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl shadow-[var(--color-primary)]/20 hover:-translate-y-1 hover:shadow-2xl hover:shadow-[var(--color-primary)]/30 transition-all flex items-center justify-center gap-3 disabled:opacity-50"
                        >
                            <span>Ingresar al Sistema</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                        </button>
                    </div>

                    <div class="text-center pt-4">
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-300 dark:text-gray-600 transition-colors">
                            ¿Aún no tienes cuenta administrativa?
                        </p>
                        <Link :href="route('register')" class="inline-block mt-3 text-xs font-black uppercase tracking-widest text-[var(--color-primary)] bg-[var(--color-primary-soft)] px-6 py-2 rounded-xl hover:bg-[var(--color-primary)] hover:text-white transition-all">
                            Solicitar Acceso
                        </Link>
                    </div>
                </form>
            </div>

            <p class="mt-10 text-center text-[10px] font-black uppercase tracking-[0.3em] text-gray-400 dark:text-gray-600 transition-colors">
                &copy; {{ new Date().getFullYear() }} {{ $page.props.empresa_config?.nombre_empresa }} &bull; Panel Seguro
            </p>
        </div>
    </div>
</template>
