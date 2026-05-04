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
    <div class="space-y-8">
        <div class="p-4 bg-rose-500/5 border border-rose-500/10 rounded-2xl">
            <p class="text-xs text-rose-200/60 font-medium leading-relaxed">
                Una vez que tu cuenta sea eliminada, todos sus recursos y datos serán eliminados permanentemente. Antes de proceder, asegúrate de haber respaldado cualquier información importante.
            </p>
        </div>

        <div class="flex items-center pt-4 border-t border-white/5">
            <button @click="confirmUserDeletion" class="h-12 px-8 bg-rose-500/10 hover:bg-rose-500 text-rose-500 hover:text-white font-black uppercase tracking-widest text-xs rounded-2xl border border-rose-500/20 transition-all shadow-xl">
                Eliminar Permanentemente
            </button>
        </div>

        <!-- Modal de Confirmación -->
        <DialogModal :show="confirmingUserDeletion" @close="closeModal">
            <template #title>
                <div class="text-xl font-bold text-rose-500">¿Confirmar Eliminación?</div>
            </template>

            <template #content>
                 <p class="text-slate-400 font-medium mb-6 leading-relaxed">
                    Esta acción no se puede deshacer. Por favor, introduce tu contraseña para confirmar la eliminación definitiva de tu cuenta y todos sus datos asociados.
                </p>

                <div class="group">
                    <div class="relative">
                        <div class="absolute -inset-0.5 bg-rose-500/20 rounded-2xl blur opacity-0 group-focus-within:opacity-100 transition duration-500"></div>
                        <input
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            class="relative w-full h-12 bg-slate-900 border border-white/10 rounded-2xl px-5 text-white focus:border-rose-500/50 focus:ring-0 transition-all outline-none"
                            placeholder="Introduce tu contraseña"
                            autocomplete="current-password"
                            @keyup.enter="deleteUser"
                        />
                    </div>
                    <InputError :message="form.errors.password" class="mt-2" />
                </div>
            </template>

            <template #footer>
                <div class="flex gap-3">
                    <button @click="closeModal" class="px-6 py-3 text-slate-400 font-bold uppercase tracking-widest text-xs hover:text-white transition-colors">
                        Cancelar
                    </button>

                    <button
                        class="h-12 px-8 bg-rose-600 text-white font-black uppercase tracking-widest text-xs rounded-2xl hover:bg-rose-500 transition-all shadow-xl shadow-rose-900/40"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        Eliminar Todo
                    </button>
                </div>
            </template>
        </DialogModal>
    </div>
</template>

