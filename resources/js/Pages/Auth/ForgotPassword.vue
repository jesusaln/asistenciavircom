<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

// Definir las propiedades que recibe el componente
defineProps({
    status: String, // Estado del proceso (por ejemplo, mensaje de éxito)
});

// Objeto de formulario para manejar los datos del correo electrónico
const form = useForm({
    email: '', // Correo electrónico del usuario
});

// Función para enviar el formulario de restablecimiento de contraseña
const submit = () => {
    form.post(route('password.email')); // Enviar una solicitud para restablecer la contraseña
};
</script>

<template>
    <!-- Configuración del título de la página -->
    <Head title="Restablecer Contraseña" />

    <!-- Componente principal de autenticación -->
    <AuthenticationCard>
        <!-- Slot para el logo personalizado -->
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <!-- Mensaje informativo sobre el restablecimiento de contraseña -->
        <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            ¿Olvidaste tu contraseña? No te preocupes. Simplemente indícanos tu dirección de correo electrónico y te enviaremos un enlace para restablecer la contraseña que te permitirá elegir una nueva.
        </div>

        <!-- Mensaje de estado (por ejemplo, "Correo enviado correctamente") -->
        <div v-if="status" class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            {{ status }}
        </div>

        <!-- Formulario para solicitar el restablecimiento de contraseña -->
        <form @submit.prevent="submit">
            <!-- Campo de correo electrónico -->
            <div>
                <InputLabel for="email" value="Correo electrónico" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autofocus
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <!-- Botón para enviar el enlace de restablecimiento -->
            <div class="flex items-center justify-end mt-4">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Enviar enlace para restablecer contraseña
                </PrimaryButton>
            </div>
        </form>
    </AuthenticationCard>
</template>

