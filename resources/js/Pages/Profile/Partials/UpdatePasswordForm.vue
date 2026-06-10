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
    <div class="space-y-6">
        <p class="text-sm text-slate-500 font-medium leading-relaxed">Protege tu cuenta utilizando una contraseña robusta. Recomendamos el uso de gestores de contraseñas para mayor seguridad.</p>

        <form @submit.prevent="updatePassword" class="space-y-6">
            <!-- Contraseña Actual -->
            <div class="group">
                <InputLabel for="current_password" value="Contraseña Actual" class="text-xs font-black uppercase tracking-wide text-slate-500 mb-2" />
                <div class="relative">
                    <div class="absolute -inset-0.5 bg-[#ff6600]/20 rounded-2xl blur opacity-0 group-focus-within:opacity-100 transition duration-500"></div>
                    <input
                        id="current_password"
                        ref="currentPasswordInput"
                        v-model="form.current_password"
                        type="password"
                        class="relative w-full h-12 bg-black/50 border border-white/5 rounded-2xl px-5 text-white focus:border-[#ff6600]/50 focus:ring-0 transition-all outline-none"
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />
                </div>
                <InputError :message="form.errors.current_password" class="mt-2" />
            </div>

            <!-- Nueva Contraseña -->
            <div class="group">
                <InputLabel for="password" value="Nueva Contraseña" class="text-xs font-black uppercase tracking-wide text-slate-500 mb-2" />
                <div class="relative">
                    <div class="absolute -inset-0.5 bg-[#ff6600]/20 rounded-2xl blur opacity-0 group-focus-within:opacity-100 transition duration-500"></div>
                    <input
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="relative w-full h-12 bg-black/50 border border-white/5 rounded-2xl px-5 text-white focus:border-[#ff6600]/50 focus:ring-0 transition-all outline-none"
                        autocomplete="new-password"
                        placeholder="Mínimo 8 caracteres"
                    />
                </div>
                <InputError :message="form.errors.password" class="mt-2" />
            </div>

            <!-- Confirmar Contraseña -->
            <div class="group">
                <InputLabel for="password_confirmation" value="Confirmar Nueva Contraseña" class="text-xs font-black uppercase tracking-wide text-slate-500 mb-2" />
                <div class="relative">
                    <div class="absolute -inset-0.5 bg-[#ff6600]/20 rounded-2xl blur opacity-0 group-focus-within:opacity-100 transition duration-500"></div>
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        class="relative w-full h-12 bg-black/50 border border-white/5 rounded-2xl px-5 text-white focus:border-[#ff6600]/50 focus:ring-0 transition-all outline-none"
                        autocomplete="new-password"
                        placeholder="Repite la contraseña"
                    />
                </div>
                <InputError :message="form.errors.password_confirmation" class="mt-2" />
            </div>

            <!-- Acciones -->
            <div class="flex items-center justify-end pt-4 border-t border-white/5">
                <ActionMessage :on="form.recentlySuccessful" class="me-4 text-emerald-400 font-bold text-xs uppercase tracking-wide animate-pulse">
                    ¡Seguridad actualizada!
                </ActionMessage>

                <button 
                    type="submit" 
                    :class="{ 'opacity-25': form.processing }" 
                    :disabled="form.processing"
                    class="h-12 px-8 bg-[#ff6600] text-black font-black uppercase tracking-wide text-xs rounded-2xl hover:bg-white hover:scale-105 transition-all shadow-xl shadow-[#ff6600]/10"
                >
                    Actualizar Contraseña
                </button>
            </div>
        </form>
    </div>
</template>
