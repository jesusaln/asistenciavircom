<script setup>
import { computed, ref, onMounted } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { useDarkMode } from '@/Utils/useDarkMode';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import PublicFooter from '@/Components/PublicFooter.vue';
import WhatsAppWidget from '@/Components/WhatsAppWidget.vue';

const props = defineProps({
    empresa: Object,
});

const page = usePage();

const empresaData = computed(() => {
    const globalConfig = page.props.empresa_config || {};
    const localProp = props.empresa || {};
    return { ...globalConfig, ...localProp };
});

const { isDarkMode, enableDarkMode, toggleDarkMode } = useDarkMode(empresaData.value);

const form = useForm({
    nombre: '',
    telefono: '',
    horario_contacto: '',
    mensaje: '',
});

const mounted = ref(false);
onMounted(() => {
    enableDarkMode(); // Force dark mode immediately
    setTimeout(() => { mounted.value = true; }, 100);
});

const formatPhone = (event) => {
    let value = event.target.value.replace(/\D/g, '');
    if (value.length > 10) value = value.slice(0, 10);
    form.telefono = value;
};

const submit = () => {
    form.post(route('public.agenda-rapida.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const features = [
    { icon: 'certificate', title: 'Garantía', desc: 'Por escrito en cada servicio.' },
    { icon: 'map-marker-alt', title: 'Hermosillo', desc: 'Atención en toda la ciudad.' },
    { icon: 'clock', title: 'Inmediato', desc: 'Respuesta en < 15 min.' }
];

const cssVars = computed(() => {
    const primary = empresaData.value.color_principal || '#FF6B35';
    return {
        '--color-primary': primary,
        '--color-primary-soft': primary + '20',
        '--color-primary-glow': primary + '40',
    };
});
</script>

<template>
    <Head :title="`Agendar Cita Premium - ${empresaData?.nombre || 'Empresa'}`" />

    <div 
        class="premium-wrapper min-h-screen selection:bg-brand-500/30 selection:text-orange-200"
        :style="cssVars"
    >
        <WhatsAppWidget :whatsapp="empresaData?.whatsapp" :empresaNombre="empresaData?.nombre || empresaData?.nombre_empresa" />
        <PublicNavbar :empresa="empresaData" activeTab="contacto" />
        <!-- Background Elements -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-[var(--color-primary)] opacity-[0.07] blur-[120px] rounded-full"></div>
            <div class="absolute top-[20%] -right-[5%] w-[30%] h-[30%] bg-blue-600 opacity-[0.05] blur-[100px] rounded-full"></div>
            <div class="absolute -bottom-[10%] left-[20%] w-[50%] h-[50%] bg-[var(--color-primary)] opacity-[0.03] blur-[150px] rounded-full"></div>

        </div>

        <div class="relative max-w-7xl mx-auto px-4 py-12 md:py-24">

            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <!-- Left Column: Copy -->
                <div 
                    class="transition-all duration-700 delay-100"
                    :class="mounted ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-12'"
                >
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-500/10 border border-brand-500/20 text-orange-400 text-[10px] font-black uppercase tracking-wide mb-6">
                        <span class="flex h-2 w-2 rounded-full bg-brand-500 animate-pulse"></span>
                        Pocos Lugares Disponibles
                    </div>
                    <h2 class="text-5xl md:text-7xl font-black text-white leading-[1.05] tracking-tight mb-8">
                        Recupera el confort de tu hogar <br />
                        <span class="bg-gradient-to-r from-[var(--color-primary)] via-orange-400 to-brand-300 bg-clip-text text-transparent italic">hoy mismo.</span>
                    </h2>
                    
                    <p class="text-xl text-slate-400 leading-relaxed mb-10 max-w-lg">
                        Especialistas en reparación, instalación y mantenimiento de aire acondicionado. <b>Atención profesional garantizada en Hermosillo.</b>
                    </p>

                    <!-- Professional Image Preview -->
                    <div class="relative rounded-3xl overflow-hidden border border-white/10 mb-10 group shadow-2xl max-w-md hidden md:block">
                        <img 
                            src="/images/tecnico_mirage.png" 
                            alt="Técnico Mirage Profesional"
                            class="w-full grayscale-[0.1] group-hover:grayscale-0 transition-all duration-700"
                        >
                        <div class="absolute inset-0 bg-gradient-to-t from-[#020617] via-transparent to-transparent opacity-60"></div>
                        <div class="absolute bottom-4 left-6 flex items-center gap-2">
                            <div class="p-2 bg-brand-500 rounded-xl">
                                <font-awesome-icon icon="certificate" class="w-4 h-4 text-white" />
                            </div>
                            <span class="text-xs font-bold text-white uppercase tracking-wider">Servicio Certificado</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div v-for="feat in features" :key="feat.title" class="p-4 rounded-2xl bg-white/[0.03] border border-white/[0.05] hover:border-brand-500/30 transition-colors">
                            <span class="text-xl mb-2 block text-orange-500">
                                <font-awesome-icon :icon="feat.icon" />
                            </span>
                            <h3 class="font-bold text-white text-[13px] mb-1 leading-tight">{{ feat.title }}</h3>
                            <p class="text-[11px] text-slate-500">{{ feat.desc }}</p>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Card -->
                <div 
                    class="transition-all duration-700 delay-300"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-12'"
                >
                    <div class="relative group">
                        <!-- Card Glow -->
                        <div class="absolute -inset-1 bg-gradient-to-r from-[var(--color-primary)] to-brand-600 rounded-[2.5rem] opacity-20 blur-xl group-hover:opacity-30 transition-opacity"></div>
                        
                        <div class="relative bg-[#020617]/80 backdrop-blur-2xl border border-white/10 rounded-[2.5rem] p-8 md:p-12 shadow-2xl">
                            
                            <div v-if="page.props.flash?.success" class="mb-8 p-6 rounded-3xl bg-brand-500/10 border border-emerald-500/20 text-emerald-400">
                                <p class="font-black uppercase tracking-wide text-xs mb-1">Solicitud Enviada</p>
                                <p class="text-sm opacity-90 font-medium">{{ page.props.flash.success }}</p>
                            </div>

                            <form @submit.prevent="submit" class="space-y-6">
                                <div class="space-y-2">
                                    <label class="text-xs font-black uppercase tracking-wide text-slate-500 ml-1">Nombre Completo</label>
                                    <input 
                                        v-model="form.nombre"
                                        type="text" 
                                        placeholder="Ej. JUAN PÉREZ"
                                        class="w-full bg-white/[0.03] border border-white/10 rounded-2xl px-6 py-4 text-white placeholder:text-slate-500 outline-none focus:border-[var(--color-primary)] focus:bg-white/[0.06] focus:ring-2 focus:ring-[var(--color-primary-soft)] transition-all antialiased h-16 text-lg font-medium uppercase"
                                        required
                                    >
                                    <p v-if="form.errors.nombre" class="text-rose-400 text-xs mt-1 ml-1">{{ form.errors.nombre }}</p>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-xs font-black uppercase tracking-wide text-slate-500 ml-1">WhatsApp / Teléfono</label>
                                    <div class="relative">
                                        <span class="absolute left-6 top-1/2 -translate-y-1/2 text-slate-500 font-bold border-r border-white/10 pr-4">+52</span>
                                        <input 
                                            :value="form.telefono"
                                            @input="formatPhone"
                                            type="tel" 
                                            placeholder="10 dígitos"
                                            maxlength="10"
                                            class="w-full bg-white/[0.03] border border-white/10 rounded-2xl pl-20 pr-6 py-4 text-white placeholder:text-slate-500 outline-none focus:border-[var(--color-primary)] focus:bg-white/[0.06] focus:ring-2 focus:ring-[var(--color-primary-soft)] transition-all h-16 text-lg font-medium font-mono tracking-wider"
                                            required
                                        >
                                    </div>
                                    <p v-if="form.errors.telefono" class="text-rose-400 text-xs mt-1 ml-1">{{ form.errors.telefono }}</p>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-xs font-black uppercase tracking-wide text-slate-500 ml-1">Horario de Contacto</label>
                                    <div class="relative">
                                        <select 
                                            v-model="form.horario_contacto"
                                            class="w-full bg-white/[0.03] border border-white/10 rounded-2xl px-6 py-4 text-white outline-none focus:border-[var(--color-primary)] focus:bg-white/[0.06] transition-all appearance-none h-16 text-lg font-medium cursor-pointer"
                                        >
                                            <option value="" class="bg-[#020617]">Lo antes posible</option>
                                            <option value="mañana" class="bg-[#020617]">🌅 Mañana (9:00 - 12:00)</option>
                                            <option value="mediodía" class="bg-[#020617]">☀️ Mediodía (12:00 - 15:00)</option>
                                            <option value="tarde" class="bg-[#020617]">🌇 Tarde (15:00 - 18:00)</option>
                                        </select>
                                        <div class="absolute right-6 top-1/2 -translate-y-1/2 pointer-events-none text-slate-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                                        </div>
                                    </div>
                                </div>

                                <button 
                                    type="submit" 
                                    :disabled="form.processing"
                                    class="group/btn w-full h-20 bg-gradient-to-r from-[var(--color-primary)] to-brand-600 rounded-3xl text-white font-black uppercase tracking-[0.15em] shadow-xl shadow-brand-600/20 hover:scale-[1.02] active:scale-[0.98] transition-all disabled:opacity-50 disabled:hover:scale-100 flex items-center justify-center gap-4 text-lg"
                                >
                                    <template v-if="!form.processing">
                                        Solicitar Asistencia Ahora
                                        <svg class="w-10 h-10 group-hover/btn:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                        </svg>
                                    </template>
                                    <template v-else>
                                        <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                        </svg>
                                        Procesando...
                                    </template>
                                </button>

                                <div class="flex items-center justify-center gap-2 text-[10px] text-slate-500 font-bold uppercase tracking-[0.2em] mt-2">
                                    <span class="w-2 h-2 rounded-full bg-brand-500 animate-pulse"></span>
                                    Técnicos disponibles en este momento
                                </div>

                                <p class="text-[10px] text-center text-slate-500 uppercase font-black tracking-wide mt-6">
                                    🛡️ Tus datos están protegidos por SSL 256-bit
                                </p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-20">
                <PublicFooter :empresa="empresaData" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.premium-wrapper {
    font-family: 'Outfit', 'Inter', sans-serif;
    background-attachment: fixed;
}

.animate-bounce-x {
    animation: bounce-x 1s infinite;
}

@keyframes bounce-x {
    0%, 100% { transform: translateX(0); }
    50% { transform: translateX(5px); }
}

input::placeholder {
    font-size: 0.9em;
}

select {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
}
</style>
