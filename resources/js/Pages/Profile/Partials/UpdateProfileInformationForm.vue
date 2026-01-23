<template>
    <FormSection @submitted="updateProfileInformation">
        <template #title>
            <h2 class="text-black font-semibold text-xl leading-tight">
                Información del Perfil
            </h2>
        </template>

        <template #description>
            <p class="text-black">
                Actualiza la información del perfil y la dirección de correo electrónico de tu cuenta.
            </p>
        </template>

        <template #form>
            <!-- Foto de Perfil -->
            <div v-if="$page.props.jetstream.managesProfilePhotos" class="col-span-6 sm:col-span-4">
                <!-- Entrada de Archivo de Foto de Perfil -->
                <input
                    id="photo"
                    ref="photoInput"
                    type="file"
                    class="hidden"
                    @change="updatePhotoPreview"
                >

                <InputLabel for="photo" value="Foto" class="text-black" />

                <!-- Foto de Perfil Actual -->
                <div v-show="!photoPreview && props.user.profile_photo_url" class="mt-2">
                    <img :src="props.user.profile_photo_url" :alt="props.user.name" class="rounded-full size-20 object-cover">
                </div>

                <!-- Vista Previa de Nueva Foto de Perfil -->
                <div v-show="photoPreview" class="mt-2">
                    <span
                        class="block rounded-full size-20 bg-cover bg-no-repeat bg-center"
                        :style="'background-image: url(\'' + photoPreview + '\');'"
                    />
                </div>

                <SecondaryButton class="mt-2 me-2 text-black" type="button" @click.prevent="selectNewPhoto">
                    Seleccionar Nueva Foto
                </SecondaryButton>

                <SecondaryButton
                    v-if="props.user.profile_photo_path"
                    type="button"
                    class="mt-2 text-black"
                    @click.prevent="deletePhoto"
                >
                    Eliminar Foto
                </SecondaryButton>

                <InputError :message="form.errors.photo" class="mt-2 text-black" />
            </div>

            <!-- Nombre -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="name" value="Nombre" class="text-black" />
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full text-black"
                    required
                    autocomplete="name"
                />
                <InputError :message="form.errors.name" class="mt-2 text-black" />
            </div>

            <!-- Correo Electrónico -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="email" value="Correo Electrónico" class="text-black" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full text-black"
                    required
                    autocomplete="username"
                />
                <InputError :message="form.errors.email" class="mt-2 text-black" />

                <div v-if="$page.props.jetstream.hasEmailVerification && props.user.email_verified_at === null">
                    <p class="text-sm mt-2 text-black">
                        Tu dirección de correo electrónico no está verificada.

                        <Link
                            :href="route('verification.send')"
                            method="post"
                            as="button"
                            class="underline text-sm text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500"
                            @click.prevent="sendEmailVerification"
                        >
                            Haz clic aquí para reenviar el correo de verificación.
                        </Link>
                    </p>

                    <div v-show="verificationLinkSent" class="mt-2 font-medium text-sm text-green-600">
                        Se ha enviado un nuevo enlace de verificación a tu dirección de correo electrónico.
                    </div>
                </div>
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
<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    user: Object,
});

const form = useForm({
    _method: 'PUT',
    name: props.user.name,
    email: props.user.email,
    photo: null,
});

const verificationLinkSent = ref(null);
const photoPreview = ref(null);
const photoInput = ref(null);

const updateProfileInformation = () => {
    if (photoInput.value?.files[0]) {
        form.photo = photoInput.value.files[0];
    }

    form.post(route('user-profile-information.update'), {
        errorBag: 'updateProfileInformation',
        preserveScroll: true,
        forceFormData: true, // Asegúrate de que el formulario envíe los datos como FormData
        onSuccess: () => {
            clearPhotoFileInput();
            // Actualiza la URL de la foto en el estado del componente
            props.user.profile_photo_url = URL.createObjectURL(form.photo);
        },
    });
};

const sendEmailVerification = () => {
    verificationLinkSent.value = true;
};

const selectNewPhoto = () => {
    photoInput.value.click();
};

const updatePhotoPreview = () => {
    const photo = photoInput.value.files[0];

    if (!photo) return;

    const reader = new FileReader();

    reader.onload = (e) => {
        photoPreview.value = e.target.result;
    };

    reader.readAsDataURL(photo);
};

const deletePhoto = () => {
    router.delete(route('current-user-photo.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            photoPreview.value = null;
            clearPhotoFileInput();
            // Actualiza la URL de la foto en el estado del componente
            props.user.profile_photo_url = null;
        },
    });
};

const clearPhotoFileInput = () => {
    if (photoInput.value?.value) {
        photoInput.value.value = null;
    }
};
</script>

