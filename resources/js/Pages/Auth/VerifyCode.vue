<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, onMounted, nextTick } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { useDarkMode } from '@/Utils/useDarkMode';

const page = usePage();
useDarkMode(page.props.empresa_config);

const codeInput = ref(null);
const form = useForm({ 
    code: ''
});
const resendForm = useForm({});

onMounted(() => {
    nextTick(() => { if (codeInput.value) codeInput.value.focus(); });
});

const submit = () => {
    form.post(route('verify.store'), { onFinish: () => form.reset('code') });
};

const resend = () => { resendForm.post(route('verify.resend')); };
</script>

<template>
    <Head title="Verificación de Seguridad" />
    <div class="min-h-screen bg-[var(--ui-surface)] flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8 transition-colors duration-500">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <!-- Header con Logo o Icono -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-brand-500 shadow-xl shadow-brand-500/30 mb-4">
                    <font-awesome-icon icon="shield-halved" class="text-white text-3xl" />
                </div>
                <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">PROTECCIÓN ACTIVA</h2>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 px-6">
                    Hemos enviado un código de acceso a tu correo registrado para asegurar que solo tú puedas entrar.
                </p>
            </div>

            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-2xl rounded-3xl border border-slate-200 dark:border-slate-800">
                <div class="p-8">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="code" value="CÓDIGO DE 6 DÍGITOS" class="text-center text-xs font-black tracking-wide text-slate-400 uppercase mb-4" />
                            <div class="relative">
                                <TextInput 
                                    id="code" 
                                    v-model="form.code" 
                                    type="text" 
                                    class="block w-full text-center text-4xl font-black tracking-[0.5em] py-5 border-2 border-slate-100 dark:border-slate-800 focus:border-brand-500 dark:focus:border-brand-500 rounded-2xl bg-[var(--ui-surface)] text-slate-900 dark:text-brand-500 transition-all"
                                    maxlength="6" 
                                    placeholder="000000"
                                    ref="codeInput" 
                                    required
                                />
                            </div>
                            <InputError :message="form.errors.code" class="mt-2 text-center" />
                        </div>

                        <div class="pt-2">
                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="w-full py-4 px-6 bg-brand-500 hover:bg-brand-600 active:scale-[0.98] text-white rounded-2xl font-black text-sm tracking-wide transition-all shadow-xl shadow-brand-500/25 flex items-center justify-center gap-2"
                            >
                                <font-awesome-icon v-if="form.processing" icon="spinner" spin />
                                <span v-else>CONFIRMAR ACCESO</span>
                            </button>
                        </div>
                    </form>

                    <!-- Acciones secundarias -->
                    <div class="mt-10 pt-6 border-t border-slate-100 dark:border-slate-800 space-y-6">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-500">¿No recibiste el código?</span>
                            <button @click="resend" :disabled="resendForm.processing" class="text-xs font-black text-brand-500 hover:text-brand-600 uppercase tracking-wider">
                                Reenviar ahora
                            </button>
                        </div>
                        
                        <a href="https://wa.me/5216641234567" target="_blank" class="flex items-center justify-center gap-2 w-full py-3 px-4 bg-slate-100 dark:bg-slate-800 hover:bg-slate-700 dark:hover:bg-emerald-900/20 text-slate-500 dark:text-slate-200 hover:text-emerald-600 dark:hover:text-emerald-400 rounded-xl transition-colors border border-transparent hover:border-brand-200 dark:border-brand-800/30 dark:hover:border-amber-800">
                            <font-awesome-icon :icon="['fab', 'whatsapp']" />
                            <span class="text-xs font-bold uppercase tracking-wider">Solicitar ayuda por WhatsApp</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center">
                <Link :href="route('logout')" method="post" as="button" class="text-xs font-bold text-slate-400 hover:text-brand-600 dark:hover:text-slate-200 transition-colors uppercase tracking-wide">
                    Cerrar sesión y volver
                </Link>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Estilo para espaciado de los dígitos del código */
#code::placeholder {
    letter-spacing: 0.5em;
    opacity: 0.3;
}
</style>
