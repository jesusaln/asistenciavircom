<template>
    <div class="space-y-8">
        <form @submit.prevent="updateProfileInformation" class="grid grid-cols-6 gap-6">
            <!-- Foto de Perfil -->
            <div v-if="$page.props.jetstream.managesProfilePhotos" class="col-span-6 flex flex-col items-center sm:items-start mb-4">
                <InputLabel for="photo" value="Imagen de Perfil" class="mb-4 text-xs font-black uppercase tracking-widest text-slate-500" />
                
                <input
                    id="photo"
                    ref="photoInput"
                    type="file"
                    class="hidden"
                    @change="updatePhotoPreview"
                >

                <div class="flex items-center gap-6">
                    <!-- Foto de Perfil Actual -->
                    <div v-show="!photoPreview && props.user.profile_photo_url" class="relative group">
                        <div class="absolute -inset-1 bg-gradient-to-r from-[#ff6600] to-amber-500 rounded-full blur opacity-20 group-hover:opacity-40 transition"></div>
                        <img :src="props.user.profile_photo_url" :alt="props.user.name" class="relative rounded-full size-24 object-cover border-2 border-white/10 shadow-2xl">
                    </div>

                    <!-- Vista Previa de Nueva Foto -->
                    <div v-show="photoPreview" class="relative">
                        <span
                            class="block rounded-full size-24 bg-cover bg-no-repeat bg-center border-2 border-[#ff6600]"
                            :style="'background-image: url(\'' + photoPreview + '\');'"
                        />
                    </div>

                    <div class="flex flex-col gap-2">
                        <button type="button" @click.prevent="selectNewPhoto" class="px-4 py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-xs font-bold uppercase tracking-widest text-white transition-all shadow-sm">
                            Subir nueva
                        </button>
                        <button
                            v-if="props.user.profile_photo_path"
                            type="button"
                            class="px-4 py-2 bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 text-xs font-bold uppercase tracking-widest rounded-xl transition-all"
                            @click.prevent="deletePhoto"
                        >
                            Quitar foto
                        </button>
                    </div>
                </div>

                <InputError :message="form.errors.photo" class="mt-2" />
            </div>

            <!-- Nombre -->
            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="name" value="Nombre Completo" class="text-xs font-black uppercase tracking-widest text-slate-500 mb-2" />
                <div class="relative group">
                    <div class="absolute -inset-0.5 bg-[#ff6600]/20 rounded-2xl blur opacity-0 group-focus-within:opacity-100 transition duration-500"></div>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="relative w-full h-12 bg-slate-900/50 border border-white/5 rounded-2xl px-5 text-white focus:border-[#ff6600]/50 focus:ring-0 transition-all outline-none"
                        required
                        autocomplete="name"
                        placeholder="Escribe tu nombre"
                    />
                </div>
                <InputError :message="form.errors.name" class="mt-2" />
            </div>

            <!-- Correo Electrónico -->
            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="email" value="Correo Electrónico" class="text-xs font-black uppercase tracking-widest text-slate-500 mb-2" />
                <div class="relative group">
                    <div class="absolute -inset-0.5 bg-[#ff6600]/20 rounded-2xl blur opacity-0 group-focus-within:opacity-100 transition duration-500"></div>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="relative w-full h-12 bg-slate-900/50 border border-white/5 rounded-2xl px-5 text-white focus:border-[#ff6600]/50 focus:ring-0 transition-all outline-none"
                        required
                        autocomplete="username"
                        placeholder="email@ejemplo.com"
                    />
                </div>
                <InputError :message="form.errors.email" class="mt-2" />

                <div v-if="$page.props.jetstream.hasEmailVerification && props.user.email_verified_at === null">
                    <div class="mt-4 p-4 bg-amber-500/5 border border-amber-500/20 rounded-2xl">
                        <p class="text-xs font-medium text-amber-200 uppercase tracking-wide">
                            Tu email no está verificado.
                        </p>
                        <button
                            type="button"
                            class="mt-2 text-xs font-black text-amber-500 hover:text-amber-400 uppercase tracking-tighter"
                            @click.prevent="sendEmailVerification"
                        >
                            Reenviar enlace de verificación
                        </button>
                    </div>

                    <div v-show="verificationLinkSent" class="mt-2 text-xs font-bold text-emerald-400 uppercase tracking-widest text-center">
                        Enlace enviado con éxito.
                    </div>
                </div>
            </div>

            <!-- Botón de Acción -->
            <div class="col-span-6 flex items-center justify-end mt-4 pt-4 border-t border-white/5">
                <ActionMessage :on="form.recentlySuccessful" class="me-4 text-emerald-400 font-bold text-xs uppercase tracking-widest animate-pulse">
                    ¡Actualizado!
                </ActionMessage>

                <button 
                    type="submit" 
                    :class="{ 'opacity-25': form.processing }" 
                    :disabled="form.processing"
                    class="h-12 px-8 bg-[#ff6600] text-black font-black uppercase tracking-widest text-xs rounded-2xl hover:bg-white hover:scale-105 transition-all shadow-xl shadow-[#ff6600]/10"
                >
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
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
