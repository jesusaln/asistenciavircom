<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionSection from '@/Components/ActionSection.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import InputError from '@/Components/InputError.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    setTimeout(() => passwordInput.value.focus(), 250);
};

const deleteUser = () => {
    form.delete(route('current-user.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.reset();
};
</script>

<template>
    <ActionSection>
        <template #title>
            <h2 class="text-black font-semibold text-xl leading-tight">
                Eliminar Cuenta
            </h2>
        </template>

        <template #description>
            <p class="text-black">
                Elimina permanentemente tu cuenta.
            </p>
        </template>

        <template #content>
            <div class="max-w-xl text-sm text-white">
                Una vez que tu cuenta sea eliminada, todos sus recursos y datos serán eliminados permanentemente. Antes de eliminar tu cuenta, por favor descarga cualquier dato o información que desees conservar.
            </div>

            <div class="mt-5">
                <DangerButton @click="confirmUserDeletion" class="text-black">
                    Eliminar Cuenta
                </DangerButton>
            </div>

            <!-- Modal de Confirmación para Eliminar Cuenta -->
            <DialogModal :show="confirmingUserDeletion" @close="closeModal">
                <template #title>
                    <h2 class="text-black font-semibold text-xl leading-tight">
                        Eliminar Cuenta
                    </h2>
                </template>

                <template #content>
                    <p class="text-black">
                        ¿Estás seguro de que deseas eliminar tu cuenta? Una vez que tu cuenta sea eliminada, todos sus recursos y datos serán eliminados permanentemente. Por favor, introduce tu contraseña para confirmar que deseas eliminar tu cuenta permanentemente.
                    </p>

                    <div class="mt-4">
                        <TextInput
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            class="mt-1 block w-3/4 text-black"
                            placeholder="Contraseña"
                            autocomplete="current-password"
                            @keyup.enter="deleteUser"
                        />

                        <InputError :message="form.errors.password" class="mt-2 text-black" />
                    </div>
                </template>

                <template #footer>
                    <SecondaryButton @click="closeModal" class="text-black">
                        Cancelar
                    </SecondaryButton>

                    <DangerButton
                        class="ms-3 text-black"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        Eliminar Cuenta
                    </DangerButton>
                </template>
            </DialogModal>
        </template>
    </ActionSection>
</template>

