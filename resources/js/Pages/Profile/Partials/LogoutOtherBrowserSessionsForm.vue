<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import ActionSection from '@/Components/ActionSection.vue';
import DialogModal from '@/Components/DialogModal.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    sessions: Array,
});

const confirmingLogout = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmLogout = () => {
    confirmingLogout.value = true;

    setTimeout(() => passwordInput.value.focus(), 250);
};

const logoutOtherBrowserSessions = () => {
    form.delete(route('other-browser-sessions.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingLogout.value = false;

    form.reset();
};
</script>

<template>
    <div class="space-y-8">
        <div class="max-w-xl text-sm text-slate-400 font-medium leading-relaxed">
            Si es necesario, puedes cerrar sesión en todas tus otras sesiones de navegador en todos tus dispositivos. Si sientes que tu cuenta ha sido comprometida, también deberías actualizar tu contraseña.
        </div>

        <!-- Otras Sesiones del Navegador -->
        <div v-if="sessions.length > 0" class="space-y-4">
            <div v-for="(session, i) in sessions" :key="i" class="flex items-center p-4 bg-white/5 border border-white/5 rounded-2xl hover:border-white/10 transition-all">
                <div class="w-12 h-12 flex items-center justify-center bg-[#0b0f19] rounded-xl text-slate-500">
                    <svg v-if="session.agent.is_desktop" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />
                    </svg>
                    <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                    </svg>
                </div>

                <div class="ms-4 flex-1">
                    <div class="text-sm font-bold text-white">
                        {{ session.agent.platform ? session.agent.platform : 'Desconocido' }} - {{ session.agent.browser ? session.agent.browser : 'Desconocido' }}
                    </div>

                    <div class="text-xs text-slate-500 font-medium">
                        {{ session.ip_address }},
                        <span v-if="session.is_current_device" class="text-emerald-400 font-black uppercase tracking-tighter">Este dispositivo</span>
                        <span v-else>Activo ha {{ session.last_active }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center pt-4 border-t border-white/5">
            <button @click="confirmLogout" class="h-12 px-6 bg-white hover:bg-[#ff6600] hover:text-white text-[#0b0f19] font-black uppercase tracking-widest text-xs rounded-2xl transition-all shadow-xl">
                Cerrar sesión en otros dispositivos
            </button>

            <ActionMessage :on="form.recentlySuccessful" class="ms-4 text-emerald-400 font-bold text-xs uppercase tracking-widest">
                ¡Listo!
            </ActionMessage>
        </div>

        <!-- Modal de Confirmación -->
        <DialogModal :show="confirmingLogout" @close="closeModal">
            <template #title>
                <div class="text-xl font-bold text-white">Confirmación de Seguridad</div>
            </template>

            <template #content>
                 <p class="text-slate-400 font-medium mb-6">
                    Introduce tu contraseña para confirmar que deseas cerrar sesión en todos tus otros dispositivos.
                </p>

                <div class="group">
                    <div class="relative">
                        <div class="absolute -inset-0.5 bg-[#ff6600]/20 rounded-2xl blur opacity-0 group-focus-within:opacity-100 transition duration-500"></div>
                        <input
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            class="relative w-full h-12 bg-slate-900 border border-white/10 rounded-2xl px-5 text-white focus:border-[#ff6600]/50 focus:ring-0 transition-all outline-none"
                            placeholder="Introduce tu contraseña"
                            autocomplete="current-password"
                            @keyup.enter="logoutOtherBrowserSessions"
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
                        class="h-12 px-8 bg-[#ff6600] text-black font-black uppercase tracking-widest text-xs rounded-2xl hover:bg-white transition-all shadow-xl shadow-[#ff6600]/10"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="logoutOtherBrowserSessions"
                    >
                        Confirmar Cierre
                    </button>
                </div>
            </template>
        </DialogModal>
    </div>
</template>

