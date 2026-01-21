<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

const cssVars = computed(() => ({
    '--color-primary': (usePage().props.empresa_config?.color_principal || '#3b82f6'),
    '--color-primary-soft': (usePage().props.empresa_config?.color_principal || '#3b82f6') + '15',
    '--color-secondary': (usePage().props.empresa_config?.color_secundario || '#6b7280'),
}));

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    terms: false,
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Registro de Usuario" />

    <div :style="cssVars" class="min-h-screen bg-white dark:bg-gray-900 flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans transition-colors duration-300">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <Link href="/" class="flex justify-center mb-6">
                <img v-if="$page.props.empresa_config?.logo_url" :src="$page.props.empresa_config.logo_url" class="h-20 w-auto object-contain" :alt="$page.props.empresa_config.nombre_empresa">
                <div v-else class="flex flex-col items-center">
                    <span class="text-4xl">🔒</span>
                    <span class="text-2xl font-black text-gray-900 dark:text-white mt-2 uppercase tracking-tight transition-colors">{{ $page.props.empresa_config?.nombre_empresa || 'Vircom' }}</span>
                </div>
            </Link>
            <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight uppercase transition-colors">
                Crear una cuenta
            </h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 font-medium transition-colors">
                Únete a nuestra plataforma corporativa
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
            <div class="bg-white dark:bg-gray-900 py-8 px-4 shadow-2xl shadow-gray-200/50 dark:shadow-none sm:rounded-[2rem] sm:px-10 border border-gray-100 dark:border-gray-800 relative overflow-hidden group transition-colors">
                <form class="space-y-6 relative z-10" @submit.prevent="submit">
                    <div>
                        <InputLabel for="name" value="Nombre Completo" />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                            autofocus
                            autocomplete="name"
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="email" value="Correo Electrónico" />
                        <TextInput
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="mt-1 block w-full"
                            required
                            autocomplete="username"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div>
                        <InputLabel for="password" value="Contraseña" />
                        <TextInput
                            id="password"
                            v-model="form.password"
                            type="password"
                            class="mt-1 block w-full"
                            required
                            autocomplete="new-password"
                        />
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div>
                        <InputLabel for="password_confirmation" value="Confirmar Contraseña" />
                        <TextInput
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            class="mt-1 block w-full"
                            required
                            autocomplete="new-password"
                        />
                        <InputError class="mt-2" :message="form.errors.password_confirmation" />
                    </div>

                    <div v-if="$page.props.jetstream.hasTermsAndPrivacyPolicyFeature">
                        <InputLabel for="terms">
                            <div class="flex items-center">
                                <Checkbox id="terms" v-model:checked="form.terms" name="terms" required />

                                <div class="ms-2 text-xs text-gray-600 dark:text-gray-400">
                                    Acepto los <a target="_blank" :href="route('terms.show')" class="underline text-gray-600 dark:text-gray-400 hover:text-[var(--color-primary)] font-bold">Términos de Servicio</a> y la <a target="_blank" :href="route('policy.show')" class="underline text-gray-600 dark:text-gray-400 hover:text-[var(--color-primary)] font-bold">Política de Privacidad</a>
                                </div>
                            </div>
                            <InputError class="mt-2" :message="form.errors.terms" />
                        </InputLabel>
                    </div>

                    <div class="flex flex-col gap-4 pt-4">
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="w-full py-4 bg-[var(--color-primary)] text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl shadow-[var(--color-primary)]/20 hover:-translate-y-1 hover:shadow-2xl hover:shadow-[var(--color-primary)]/30 transition-all disabled:opacity-50"
                        >
                            Registrarme
                        </button>

                        <div class="text-center mt-2">
                            <Link :href="route('login')" class="text-xs font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 hover:text-[var(--color-primary)] transition-colors">
                                ¿Ya tienes una cuenta? Inicia sesión
                            </Link>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
