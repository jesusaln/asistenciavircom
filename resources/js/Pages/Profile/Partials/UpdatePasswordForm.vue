<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('user-password.update'), {
        errorBag: 'updatePassword',
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }

            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <FormSection @submitted="updatePassword">
        <template #title>
            <h2 class="text-black font-semibold text-xl leading-tight">
                Actualizar Contraseña
            </h2>
        </template>

        <template #description>
            <p class="text-black">
                Asegúrate de que tu cuenta utilice una contraseña larga y aleatoria para mantenerla segura.
            </p>
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="current_password" value="Contraseña Actual" class="text-black" />
                <TextInput
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="mt-1 block w-full text-black"
                    autocomplete="current-password"
                />
                <InputError :message="form.errors.current_password" class="mt-2 text-black" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="password" value="Nueva Contraseña" class="text-black" />
                <TextInput
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full text-black"
                    autocomplete="new-password"
                />
                <InputError :message="form.errors.password" class="mt-2 text-black" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="password_confirmation" value="Confirmar Contraseña" class="text-black" />
                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 block w-full text-black"
                    autocomplete="new-password"
                />
                <InputError :message="form.errors.password_confirmation" class="mt-2 text-black" />
            </div>
        </template>

        <template #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3 text-black">
                Guardado.
            </ActionMessage>

            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" class="text-black">
                Guardar
            </PrimaryButton>
        </template>
    </FormSection>
</template>

