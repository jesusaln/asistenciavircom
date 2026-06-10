<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';

const props = defineProps({
    contrato: Object
});

const notyf = new Notyf();
const step = ref(1); // 1: Review, 2: Sign
const showPassword = ref(false);

const form = useForm({
    cer: null,
    key: null,
    password: ''
});

const handleCerUpload = (e) => {
    form.cer = e.target.files[0];
};

const handleKeyUpload = (e) => {
    form.key = e.target.files[0];
};

const submitSignature = () => {
    form.post(route('contratos.public.submit', props.contrato.signing_token), {
        onSuccess: () => {
            notyf.success('Contrato firmado exitosamente');
        },
        onError: (err) => {
            notyf.error(err.error || 'Error al validar la e.firma');
        }
    });
};
</script>

<template>
    <div class="min-h-screen bg-[#F8FAFC] dark:bg-slate-950 font-sans text-slate-900 dark:text-slate-100 selection:bg-indigo-100 selection:text-indigo-700">
        <Head :title="'Firmar Contrato - ' + contrato.titulo" />

        <!-- Header -->
        <header class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 py-4 px-6 sticky top-0 z-30 shadow-sm transition-colors">
            <div class="max-w-5xl mx-auto flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-indigo-500/20">
                        <font-awesome-icon icon="file-signature" />
                    </div>
                    <div>
                        <h1 class="text-sm font-black text-slate-800 dark:text-slate-100 uppercase tracking-tighter">Portal de Firma Digital</h1>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">LegalTech Secure Node</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                    <span class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">Sesión Segura Encriptada</span>
                </div>
            </div>
        </header>

        <main class="py-12 px-6 max-w-5xl mx-auto">
            <!-- Progress Bar -->
            <div class="flex items-center justify-center gap-12 mb-12">
                <div @click="step = 1" class="flex flex-col items-center gap-2 cursor-pointer group">
                    <div :class="step >= 1 ? 'bg-indigo-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-500'" class="w-10 h-10 rounded-full flex items-center justify-center font-black text-sm transition-all shadow-lg shadow-indigo-500/10">1</div>
                    <span :class="step >= 1 ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500'" class="text-[10px] font-black uppercase tracking-widest">Revisión</span>
                </div>
                <div class="h-[2px] w-24 bg-slate-200 dark:bg-slate-800">
                    <div :class="step >= 2 ? 'w-full' : 'w-0'" class="h-full bg-indigo-600 transition-all duration-500"></div>
                </div>
                <div @click="step = 2" class="flex flex-col items-center gap-2 cursor-pointer group">
                    <div :class="step >= 2 ? 'bg-indigo-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-500'" class="w-10 h-10 rounded-full flex items-center justify-center font-black text-sm transition-all shadow-lg shadow-indigo-500/10">2</div>
                    <span :class="step >= 2 ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500'" class="text-[10px] font-black uppercase tracking-widest">Firma e.firma</span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Document Viewer -->
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-slate-900 rounded-[2rem] shadow-xl border border-slate-200 dark:border-slate-800 overflow-hidden min-h-[800px] flex flex-col transition-colors">
                        <div class="p-8 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/50">
                            <h2 class="text-xl font-black text-slate-800 dark:text-slate-100 uppercase tracking-tight">{{ contrato.titulo }}</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Identificador Único: <span class="font-mono text-indigo-600 dark:text-indigo-400">{{ contrato.signing_token.substring(0,8) }}</span></p>
                        </div>
                        <div class="flex-1 p-12 overflow-y-auto bg-slate-50/20 dark:bg-slate-900/50">
                            <div class="prose prose-slate dark:prose-invert max-w-none font-serif leading-relaxed text-slate-700 dark:text-slate-300 whitespace-pre-wrap text-base">
                                {{ contrato.contenido }}
                            </div>
                        </div>
                        <div class="p-8 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                            <div class="flex items-center gap-3 text-slate-400 dark:text-slate-500">
                                <font-awesome-icon icon="shield-alt" class="text-xl" />
                                <p class="text-[10px] font-bold uppercase leading-tight">Documento protegido por criptografía<br>SHA-256 / AES-256</p>
                            </div>
                            <button v-if="step === 1" @click="step = 2" class="px-10 py-4 bg-indigo-600 text-white rounded-2xl font-black text-xs shadow-xl shadow-indigo-500/20 hover:bg-indigo-700 transition-all flex items-center gap-2">
                                CONTINUAR A FIRMAR <font-awesome-icon icon="arrow-right" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Sidebar / Signature Panel -->
                <div class="lg:col-span-1">
                    <div v-if="step === 2" class="bg-white dark:bg-slate-900 rounded-[2rem] shadow-xl border border-slate-200 dark:border-slate-800 p-8 sticky top-28 animate-in fade-in slide-in-from-right-10 duration-500 transition-colors">
                        <h3 class="text-lg font-black text-slate-800 dark:text-slate-100 uppercase tracking-tight mb-2">Firma con e.firma (FIEL)</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-8 leading-relaxed">Usa tus archivos del SAT para firmar digitalmente. Este proceso es legalmente vinculante.</p>

                        <div class="space-y-6">
                            <!-- Certificate -->
                            <div>
                                <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2 block">Archivo Certificado (.cer)</label>
                                <div class="relative group">
                                    <input type="file" @change="handleCerUpload" accept=".cer" class="absolute inset-0 opacity-0 cursor-pointer z-10" />
                                    <div class="w-full bg-slate-50 dark:bg-slate-800/50 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl p-4 flex flex-col items-center justify-center group-hover:border-indigo-400 dark:group-hover:border-indigo-500 transition-all">
                                        <font-awesome-icon :icon="form.cer ? 'check-circle' : 'certificate'" :class="form.cer ? 'text-emerald-500' : 'text-slate-300 dark:text-slate-600'" class="text-2xl mb-2" />
                                        <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase">{{ form.cer ? form.cer.name : 'Subir archivo .cer' }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Private Key -->
                            <div>
                                <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2 block">Llave Privada (.key)</label>
                                <div class="relative group">
                                    <input type="file" @change="handleKeyUpload" accept=".key" class="absolute inset-0 opacity-0 cursor-pointer z-10" />
                                    <div class="w-full bg-slate-50 dark:bg-slate-800/50 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl p-4 flex flex-col items-center justify-center group-hover:border-indigo-400 dark:group-hover:border-indigo-500 transition-all">
                                        <font-awesome-icon :icon="form.key ? 'check-circle' : 'key'" :class="form.key ? 'text-emerald-500' : 'text-slate-300 dark:text-slate-600'" class="text-2xl mb-2" />
                                        <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase">{{ form.key ? form.key.name : 'Subir archivo .key' }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Password -->
                            <div>
                                <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2 block">Contraseña de la Llave</label>
                                <div class="relative">
                                    <input v-model="form.password" :type="showPassword ? 'text' : 'password'" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-slate-100 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500/20 pr-12 transition-colors" placeholder="••••••••" />
                                    <button type="button" @click="showPassword = !showPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 focus:outline-none">
                                        <font-awesome-icon :icon="showPassword ? 'eye-slash' : 'eye'" />
                                    </button>
                                </div>
                            </div>

                            <button @click="submitSignature" :disabled="form.processing || !form.cer || !form.key" class="w-full py-5 bg-emerald-600 text-white rounded-2xl font-black text-xs shadow-xl shadow-emerald-500/20 hover:bg-emerald-700 transition-all disabled:opacity-50 mt-4">
                                <font-awesome-icon v-if="form.processing" icon="spinner" spin class="mr-2" />
                                VALIDAR Y FIRMAR CONTRATO
                            </button>

                            <p class="text-[9px] text-slate-400 dark:text-slate-500 text-center leading-relaxed px-4">
                                Al hacer clic en "Firmar", manifiestas tu consentimiento para firmar este documento mediante firma electrónica avanzada.
                            </p>
                        </div>
                    </div>

                    <!-- Review Sidebar -->
                    <div v-else class="bg-indigo-600 dark:bg-indigo-900 rounded-[2rem] shadow-xl p-8 text-white sticky top-28 transition-colors">
                        <h3 class="text-lg font-black uppercase tracking-tight mb-4">Instrucciones</h3>
                        <ul class="space-y-4 text-sm font-medium opacity-90">
                            <li class="flex gap-3">
                                <span class="w-5 h-5 bg-white/20 rounded-full flex items-center justify-center text-[10px] shrink-0">1</span>
                                Lee atentamente el documento en la pantalla de la izquierda.
                            </li>
                            <li class="flex gap-3">
                                <span class="w-5 h-5 bg-white/20 rounded-full flex items-center justify-center text-[10px] shrink-0">2</span>
                                Asegúrate de que todos tus datos (Nombre, RFC) sean correctos.
                            </li>
                            <li class="flex gap-3">
                                <span class="w-5 h-5 bg-white/20 rounded-full flex items-center justify-center text-[10px] shrink-0">3</span>
                                Haz clic en "Continuar a Firmar" para subir tus archivos del SAT.
                            </li>
                        </ul>
                        <div class="mt-8 pt-8 border-t border-white/10">
                            <p class="text-[10px] font-black uppercase tracking-widest mb-2 opacity-50">Soporte Técnico</p>
                            <p class="text-xs font-bold">Si tienes problemas para firmar, contacta a Climas del Desierto.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="py-12 border-t border-slate-200 mt-12">
            <div class="max-w-5xl mx-auto px-6 text-center">
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">© {{ new Date().getFullYear() }} Climas del Desierto</p>
            </div>
        </footer>
    </div>
</template>

<style>
@keyframes slide-in-from-right-10 {
  from { transform: translateX(10%); opacity: 0; }
  to { transform: translateX(0); opacity: 1; }
}
.animate-in { animation: slide-in-from-right-10 0.5s ease-out; }
</style>
