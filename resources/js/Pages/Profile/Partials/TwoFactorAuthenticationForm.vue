<script setup>
import { ref, computed, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import ActionSection from '@/Components/ActionSection.vue';
import ConfirmsPassword from '@/Components/ConfirmsPassword.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    requiresConfirmation: Boolean,
});

const page = usePage();
const enabling = ref(false);
const confirming = ref(false);
const disabling = ref(false);
const qrCode = ref(null);
const setupKey = ref(null);
const recoveryCodes = ref([]);

const confirmationForm = useForm({
    code: '',
});

const twoFactorEnabled = computed(
    () => !enabling.value && page.props.auth.user?.two_factor_enabled,
);

watch(twoFactorEnabled, () => {
    if (!twoFactorEnabled.value) {
        confirmationForm.reset();
        confirmationForm.clearErrors();
    }
});

const enableTwoFactorAuthentication = () => {
    enabling.value = true;

    router.post(route('two-factor.enable'), {}, {
        preserveScroll: true,
        onSuccess: () => Promise.all([
            showQrCode(),
            showSetupKey(),
            showRecoveryCodes(),
        ]),
        onFinish: () => {
            enabling.value = false;
            confirming.value = props.requiresConfirmation;
        },
    });
};

const showQrCode = () => {
    return axios.get(route('two-factor.qr-code')).then(response => {
        qrCode.value = response.data.svg;
    });
};

const showSetupKey = () => {
    return axios.get(route('two-factor.secret-key')).then(response => {
        setupKey.value = response.data.secretKey;
    });
};

const showRecoveryCodes = () => {
    return axios.get(route('two-factor.recovery-codes')).then(response => {
        recoveryCodes.value = response.data;
    });
};

const confirmTwoFactorAuthentication = () => {
    confirmationForm.post(route('two-factor.confirm'), {
        errorBag: "confirmTwoFactorAuthentication",
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            confirming.value = false;
            qrCode.value = null;
            setupKey.value = null;
        },
    });
};

const regenerateRecoveryCodes = () => {
    axios
        .post(route('two-factor.recovery-codes'))
        .then(() => showRecoveryCodes());
};

const disableTwoFactorAuthentication = () => {
    disabling.value = true;

    router.delete(route('two-factor.disable'), {
        preserveScroll: true,
        onSuccess: () => {
            disabling.value = false;
            confirming.value = false;
        },
    });
};
</script>

<template>
    <div class="space-y-8">
        <div class="flex items-center gap-4 p-4 bg-white/5 border border-white/5 rounded-2xl">
            <div class="w-12 h-12 flex items-center justify-center bg-[#0b0f19] rounded-xl text-amber-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                </svg>
            </div>
            <div>
                <h3 v-if="twoFactorEnabled && !confirming" class="text-sm font-black text-emerald-400 uppercase tracking-widest">
                    Seguridad Activada
                </h3>
                <h3 v-else-if="twoFactorEnabled && confirming" class="text-sm font-black text-amber-500 uppercase tracking-widest">
                    Pendiente de Confirmación
                </h3>
                <h3 v-else class="text-sm font-black text-slate-500 uppercase tracking-widest">
                    Seguridad Básica
                </h3>
                <p class="text-xs text-slate-400 font-medium">Añade una capa extra de seguridad a tu cuenta.</p>
            </div>
        </div>

        <div class="max-w-xl text-sm text-slate-400 font-medium leading-relaxed">
            Cuando la autenticación de dos factores está habilitada, se te pedirá un token seguro y aleatorio durante la autenticación. Puedes obtener este token desde la aplicación Google Authenticator o similares.
        </div>

        <div v-if="twoFactorEnabled">
            <div v-if="qrCode">
                <div class="mt-4 p-6 bg-white/5 border border-white/5 rounded-3xl flex flex-col items-center">
                    <p class="text-xs font-bold text-white uppercase tracking-widest mb-4 text-center">
                        {{ confirming ? 'Escanea este código para finalizar' : 'Código QR de configuración' }}
                    </p>
                    
                    <div class="p-4 bg-white rounded-2xl shadow-2xl" v-html="qrCode" />

                    <div v-if="setupKey" class="mt-6 text-center">
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Clave de Configuración Manual</p>
                        <code class="px-4 py-2 bg-black rounded-xl text-amber-500 font-mono text-xs border border-white/5" v-html="setupKey"></code>
                    </div>
                </div>

                <div v-if="confirming" class="mt-6 group">
                    <InputLabel for="code" value="Introduce el código de tu App" class="text-xs font-black uppercase tracking-widest text-slate-500 mb-2" />
                    <div class="relative">
                        <div class="absolute -inset-0.5 bg-[#ff6600]/20 rounded-2xl blur opacity-0 group-focus-within:opacity-100 transition duration-500"></div>
                        <input
                            id="code"
                            v-model="confirmationForm.code"
                            type="text"
                            inputmode="numeric"
                            class="relative w-full h-12 bg-slate-900 border border-white/10 rounded-2xl px-5 text-white focus:border-[#ff6600]/50 focus:ring-0 transition-all outline-none font-mono text-center tracking-[1em]"
                            autofocus
                            placeholder="000000"
                            autocomplete="one-time-code"
                            @keyup.enter="confirmTwoFactorAuthentication"
                        />
                    </div>
                    <InputError :message="confirmationForm.errors.code" class="mt-2" />
                </div>
            </div>

            <div v-if="recoveryCodes.length > 0 && !confirming" class="mt-6">
                <div class="p-6 bg-amber-500/5 border border-amber-500/10 rounded-3xl">
                    <p class="text-xs font-black text-amber-500 uppercase tracking-widest mb-4">Códigos de Recuperación</p>
                    <p class="text-xs text-amber-200/60 font-medium mb-4">Guarda estos códigos en un lugar seguro. Te permitirán acceder si pierdes tu dispositivo.</p>
                    
                    <div class="grid grid-cols-2 gap-2 font-mono text-xs">
                        <div v-for="code in recoveryCodes" :key="code" class="px-3 py-2 bg-black/40 rounded-xl text-white border border-white/5 text-center">
                            {{ code }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap gap-4 pt-4 border-t border-white/5">
            <div v-if="!twoFactorEnabled">
                <ConfirmsPassword @confirmed="enableTwoFactorAuthentication">
                    <button type="button" :class="{ 'opacity-25': enabling }" :disabled="enabling" class="h-12 px-8 bg-[#ff6600] text-black font-black uppercase tracking-widest text-xs rounded-2xl hover:bg-white transition-all shadow-xl shadow-[#ff6600]/10">
                        Activar 2FA
                    </button>
                </ConfirmsPassword>
            </div>

            <div v-else class="flex flex-wrap gap-3">
                <ConfirmsPassword @confirmed="confirmTwoFactorAuthentication">
                    <button
                        v-if="confirming"
                        type="button"
                        class="h-12 px-8 bg-[#ff6600] text-black font-black uppercase tracking-widest text-xs rounded-2xl hover:bg-white transition-all shadow-xl"
                        :class="{ 'opacity-25': enabling }"
                        :disabled="enabling"
                    >
                        Confirmar Código
                    </button>
                </ConfirmsPassword>

                <ConfirmsPassword @confirmed="regenerateRecoveryCodes">
                    <button
                        v-if="recoveryCodes.length > 0 && !confirming"
                        class="h-12 px-6 bg-white/5 hover:bg-white/10 text-white font-black uppercase tracking-widest text-[10px] rounded-2xl border border-white/10 transition-all"
                    >
                        Regenerar Recuperación
                    </button>
                </ConfirmsPassword>

                <ConfirmsPassword @confirmed="showRecoveryCodes">
                    <button
                        v-if="recoveryCodes.length === 0 && !confirming"
                        class="h-12 px-6 bg-white/5 hover:bg-white/10 text-white font-black uppercase tracking-widest text-[10px] rounded-2xl border border-white/10 transition-all"
                    >
                        Ver Códigos
                    </button>
                </ConfirmsPassword>

                <ConfirmsPassword @confirmed="disableTwoFactorAuthentication">
                    <button
                        v-if="confirming"
                        class="h-12 px-6 text-slate-500 font-black uppercase tracking-widest text-[10px] hover:text-white transition-all"
                        :class="{ 'opacity-25': disabling }"
                        :disabled="disabling"
                    >
                        Cancelar
                    </button>
                </ConfirmsPassword>

                <ConfirmsPassword @confirmed="disableTwoFactorAuthentication">
                    <button
                        v-if="!confirming"
                        class="h-12 px-6 bg-rose-500/10 hover:bg-rose-500/20 text-rose-500 font-black uppercase tracking-widest text-[10px] rounded-2xl border border-rose-500/20 transition-all"
                        :class="{ 'opacity-25': disabling }"
                        :disabled="disabling"
                    >
                        Desactivar 2FA
                    </button>
                </ConfirmsPassword>
            </div>
        </div>
    </div>
</template>

