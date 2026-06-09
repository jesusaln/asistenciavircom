<script setup>
import { computed, ref, onMounted } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import PublicFooter from '@/Components/PublicFooter.vue';
import WhatsAppWidget from '@/Components/WhatsAppWidget.vue';
import { useDarkMode } from '@/Utils/useDarkMode';

const props = defineProps({
    empresa: Object,
});

const page = usePage();

const empresaData = computed(() => {
    const globalConfig = page.props.empresa_config || {};
    const localProp = props.empresa || {};
    return { ...globalConfig, ...localProp };
});

const cssVars = computed(() => ({
    '--color-primary': empresaData.value.color_principal || '#c2410c',
    '--color-primary-soft': (empresaData.value.color_principal || '#c2410c') + '15',
    '--color-primary-glow': (empresaData.value.color_principal || '#c2410c') + '40',
    '--color-secondary': empresaData.value.color_secundario || '#0f172a',
    '--color-accent': empresaData.value.color_terciario || '#f59e0b',
}));

const { isDarkMode, enableDarkMode, toggleDarkMode } = useDarkMode(empresaData.value);

const form = useForm({
    nombre: '',
    telefono: '',
    horario_contacto: '',
    mensaje: '',
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

// Step animation on mount
const mounted = ref(false);
onMounted(() => {
    setTimeout(() => { mounted.value = true; }, 100);
    enableDarkMode(); // Fuerza Dark Premium en esta landing
});

const features = [
    {
        icon: '⚡',
        title: '1 minuto',
        desc: 'Solo pedimos lo esencial para devolverte la llamada.',
        accent: 'orange',
    },
    {
        icon: '🧑‍🔧',
        title: 'Atención humana',
        desc: 'Una persona de nuestro equipo te contacta para cerrar la cita.',
        accent: 'blue',
    },
    {
        icon: '🚀',
        title: 'Más rápido',
        desc: 'Ideal si prefieres llamada en lugar de capturar todo en línea.',
        accent: 'amber',
    },
];

// Current step for progress indicator
const currentStep = computed(() => {
    if (form.nombre && form.telefono) return 3;
    if (form.nombre) return 2;
    return 1;
});
</script>

<template>
    <Head :title="`Agendar Cita - ${empresaData?.nombre || empresaData?.nombre_empresa || 'Empresa'}`">
        <meta
            name="description"
            content="Solicita una llamada rápida para agendar tu cita de aire acondicionado. Deja tu nombre y teléfono."
        />
    </Head>

    <div
        class="appointment-shell min-h-screen transition-colors duration-500"
        :class="isDarkMode ? 'dark text-slate-100' : 'text-slate-900 bg-slate-50'"
        :style="cssVars"
    >
        <WhatsAppWidget :whatsapp="empresaData?.whatsapp" :empresaNombre="empresaData?.nombre || empresaData?.nombre_empresa" />
        <PublicNavbar :empresa="empresaData" />
        
        <!-- Toggle para modo oscuro flotante -->
        <button 
            @click="toggleDarkMode" 
            class="fixed bottom-6 right-6 z-50 p-3 rounded-full shadow-lg transition-colors border"
            :class="isDarkMode ? 'bg-slate-800 border-slate-700 text-yellow-400 hover:bg-slate-700' : 'bg-white border-gray-200 text-gray-500 hover:bg-gray-50'"
            title="Alternar tema"
        >
            <svg v-if="isDarkMode" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
        </button>

        <main>
            <!-- Hero Section -->
            <section class="relative overflow-hidden">
                <!-- Animated Background Orbs -->
                <div class="absolute inset-0 pointer-events-none overflow-hidden">
                    <div class="orb orb-1"></div>
                    <div class="orb orb-2"></div>
                    <div class="orb orb-3"></div>
                    <!-- Grid Pattern -->
                    <div class="absolute inset-0 bg-grid-pattern opacity-[0.03] dark:opacity-[0.04]"></div>
                </div>

                <div class="relative mx-auto grid max-w-7xl gap-12 px-4 py-16 lg:grid-cols-[1.15fr_0.85fr] lg:gap-16 lg:py-24 xl:py-28">
                    <!-- Left: Content -->
                    <div
                        class="relative transition-all duration-700"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
                    >
                        <!-- Badge -->
                        <div class="mb-8 inline-flex items-center gap-2.5 rounded-full border border-orange-200/80 bg-white/80 px-5 py-2.5 text-[10px] font-black uppercase tracking-[0.22em] text-orange-700 shadow-sm backdrop-blur-lg transition-colors dark:border-white/10 dark:bg-white/[0.06] dark:text-orange-300 dark:shadow-none">
                            <span class="relative flex h-2.5 w-2.5">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[var(--color-primary)] opacity-75"></span>
                                <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-[var(--color-primary)]"></span>
                            </span>
                            Agenda fácil · Sin esperas
                        </div>

                        <!-- Headline -->
                        <h1 class="max-w-2xl text-[2.75rem] font-black leading-[1.08] tracking-tight text-slate-950 transition-colors dark:text-white sm:text-5xl lg:text-[3.5rem]">
                            Déjanos tu nombre y teléfono.
                            <span class="mt-2 block bg-gradient-to-r from-[var(--color-primary)] via-orange-500 to-amber-500 bg-clip-text text-transparent">Nosotros te llamamos.</span>
                        </h1>

                        <p class="mt-7 max-w-xl text-lg leading-8 text-slate-600 transition-colors dark:text-slate-400">
                            Sin formularios largos. Déjanos tus datos y nuestro equipo te contacta para confirmar el servicio, el horario y resolver dudas.
                        </p>

                        <!-- Feature Cards -->
                        <div class="mt-12 grid gap-4 sm:grid-cols-3">
                            <div
                                v-for="(feat, i) in features"
                                :key="feat.title"
                                class="feature-card group relative rounded-[1.75rem] border bg-white/70 p-6 shadow-sm backdrop-blur-sm transition-all duration-500 hover:-translate-y-1 hover:shadow-xl dark:bg-white/[0.04] dark:shadow-none dark:hover:bg-white/[0.07]"
                                :class="[
                                    feat.accent === 'orange' ? 'border-orange-200/60 hover:border-orange-300 dark:border-orange-500/15 dark:hover:border-orange-400/30' :
                                    feat.accent === 'blue' ? 'border-slate-200/60 hover:border-blue-300 dark:border-white/10 dark:hover:border-blue-400/30' :
                                    'border-amber-200/60 hover:border-amber-300 dark:border-amber-500/15 dark:hover:border-amber-400/30',
                                    mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'
                                ]"
                                :style="{ transitionDelay: `${300 + i * 150}ms` }"
                            >
                                <!-- Glow effect on hover -->
                                <div
                                    class="absolute inset-0 rounded-[1.75rem] opacity-0 transition-opacity duration-500 group-hover:opacity-100"
                                    :class="
                                        feat.accent === 'orange' ? 'bg-gradient-to-br from-orange-100/50 to-transparent dark:from-orange-500/[0.08]' :
                                        feat.accent === 'blue' ? 'bg-gradient-to-br from-blue-100/50 to-transparent dark:from-blue-500/[0.08]' :
                                        'bg-gradient-to-br from-amber-100/50 to-transparent dark:from-amber-500/[0.08]'
                                    "
                                ></div>
                                <div class="relative">
                                    <span class="mb-3 block text-2xl">{{ feat.icon }}</span>
                                    <p
                                        class="text-sm font-black uppercase tracking-[0.16em] transition-colors"
                                        :class="
                                            feat.accent === 'orange' ? 'text-orange-600 dark:text-orange-400' :
                                            feat.accent === 'blue' ? 'text-slate-900 dark:text-white' :
                                            'text-amber-700 dark:text-amber-400'
                                        "
                                    >{{ feat.title }}</p>
                                    <p class="mt-2 text-sm leading-relaxed text-slate-600 transition-colors dark:text-slate-400">{{ feat.desc }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Trust indicators -->
                        <div
                            class="mt-10 flex flex-wrap items-center gap-6 transition-all duration-700"
                            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                            :style="{ transitionDelay: '800ms' }"
                        >
                            <div class="flex items-center gap-2 text-sm text-slate-500 transition-colors dark:text-slate-400">
                                <svg class="h-5 w-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                Sin compromisos
                            </div>
                            <div class="flex items-center gap-2 text-sm text-slate-500 transition-colors dark:text-slate-400">
                                <svg class="h-5 w-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                100% gratuito
                            </div>
                            <div class="flex items-center gap-2 text-sm text-slate-500 transition-colors dark:text-slate-400">
                                <svg class="h-5 w-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                Respuesta en minutos
                            </div>
                        </div>
                    </div>

                    <!-- Right: Form Card -->
                    <div
                        class="relative transition-all duration-700"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'"
                        :style="{ transitionDelay: '200ms' }"
                    >
                        <!-- Form Card -->
                        <div class="form-card relative overflow-hidden rounded-[2.5rem] border border-slate-200/80 bg-white/90 p-7 shadow-2xl shadow-orange-200/30 backdrop-blur-xl transition-all duration-500 dark:border-white/[0.08] dark:bg-slate-900/80 dark:shadow-black/40 sm:p-9">
                            <!-- Decorative corner gradients -->
                            <div class="absolute -right-12 -top-12 h-36 w-36 rounded-full bg-gradient-to-br from-[var(--color-primary-soft)] to-transparent blur-2xl"></div>
                            <div class="absolute -bottom-12 -left-12 h-28 w-28 rounded-full bg-gradient-to-tr from-amber-200/30 to-transparent blur-2xl dark:from-amber-500/10"></div>

                            <!-- Success message -->
                            <div v-if="page.props.flash?.success" class="relative mb-7 rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-teal-50 p-5 text-emerald-800 transition-colors dark:border-emerald-500/20 dark:from-emerald-500/10 dark:to-teal-500/10 dark:text-emerald-200">
                                <div class="flex items-start gap-3">
                                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-lg dark:bg-emerald-500/20">✅</div>
                                    <div>
                                        <p class="font-black text-sm uppercase tracking-wider">Solicitud recibida</p>
                                        <p class="mt-1 text-sm opacity-80">{{ page.props.flash.success }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="relative">
                                <!-- Header -->
                                <div class="mb-7">
                                    <div class="mb-4 flex items-center gap-3">
                                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-[var(--color-primary)] to-orange-500 text-lg text-white shadow-lg shadow-orange-300/30 dark:shadow-orange-500/20">
                                            📞
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 transition-colors dark:text-slate-500">Te llamamos</p>
                                            <h2 class="text-xl font-black text-slate-950 transition-colors dark:text-white">Pide tu cita en corto</h2>
                                        </div>
                                    </div>
                                    <p class="text-sm text-slate-500 transition-colors dark:text-slate-400">Captura tus datos y lo tomamos desde aquí.</p>
                                </div>

                                <!-- Progress Steps -->
                                <div class="mb-7 flex items-center gap-2">
                                    <div
                                        v-for="step in 3"
                                        :key="step"
                                        class="h-1.5 flex-1 rounded-full transition-all duration-500"
                                        :class="step <= currentStep
                                            ? 'bg-gradient-to-r from-[var(--color-primary)] to-orange-400'
                                            : 'bg-slate-100 dark:bg-white/10'"
                                    ></div>
                                </div>

                                <!-- Form -->
                                <form class="space-y-5" @submit.prevent="submit">
                                    <div class="group">
                                        <label class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-700 transition-colors dark:text-slate-300">
                                            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-slate-100 text-[10px] font-black text-slate-500 transition-colors dark:bg-white/10 dark:text-slate-400">1</span>
                                            Nombre
                                        </label>
                                        <input
                                            v-model="form.nombre"
                                            type="text"
                                            placeholder="Ej. Juan Pérez"
                                            class="input-field w-full rounded-2xl border border-slate-200/80 bg-slate-50/80 px-5 py-4 text-slate-900 outline-none transition-all duration-300 placeholder:text-slate-400 focus:border-[var(--color-primary)] focus:bg-white focus:shadow-lg focus:shadow-[var(--color-primary-soft)] focus:ring-0 dark:border-white/10 dark:bg-slate-950/60 dark:text-white dark:placeholder:text-slate-600 dark:focus:border-[var(--color-primary)] dark:focus:bg-slate-950 dark:focus:shadow-[var(--color-primary-glow)]"
                                        >
                                        <p v-if="form.errors.nombre" class="mt-2 text-sm font-medium text-red-500">{{ form.errors.nombre }}</p>
                                    </div>

                                    <div class="group">
                                        <label class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-700 transition-colors dark:text-slate-300">
                                            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-slate-100 text-[10px] font-black text-slate-500 transition-colors dark:bg-white/10 dark:text-slate-400">2</span>
                                            Teléfono
                                        </label>
                                        <input
                                            :value="form.telefono"
                                            type="tel"
                                            inputmode="numeric"
                                            placeholder="6621234567"
                                            class="input-field w-full rounded-2xl border border-slate-200/80 bg-slate-50/80 px-5 py-4 text-slate-900 outline-none transition-all duration-300 placeholder:text-slate-400 focus:border-[var(--color-primary)] focus:bg-white focus:shadow-lg focus:shadow-[var(--color-primary-soft)] focus:ring-0 dark:border-white/10 dark:bg-slate-950/60 dark:text-white dark:placeholder:text-slate-600 dark:focus:border-[var(--color-primary)] dark:focus:bg-slate-950 dark:focus:shadow-[var(--color-primary-glow)]"
                                            @input="formatPhone"
                                        >
                                        <p class="mt-2 text-xs text-slate-400 transition-colors dark:text-slate-500">Usa un número de 10 dígitos para que podamos devolverte la llamada.</p>
                                        <p v-if="form.errors.telefono" class="mt-2 text-sm font-medium text-red-500">{{ form.errors.telefono }}</p>
                                    </div>

                                    <div class="group">
                                        <label class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-700 transition-colors dark:text-slate-300">
                                            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-slate-100 text-[10px] font-black text-slate-500 transition-colors dark:bg-white/10 dark:text-slate-400">3</span>
                                            ¿Cuándo prefieres que te llamemos?
                                            <span class="text-slate-400 dark:text-slate-600">(opcional)</span>
                                        </label>
                                        <select
                                            v-model="form.horario_contacto"
                                            class="input-field w-full appearance-none rounded-2xl border border-slate-200/80 bg-slate-50/80 px-5 py-4 text-slate-900 outline-none transition-all duration-300 focus:border-[var(--color-primary)] focus:bg-white focus:shadow-lg focus:shadow-[var(--color-primary-soft)] focus:ring-0 dark:border-white/10 dark:bg-slate-950/60 dark:text-white dark:focus:border-[var(--color-primary)] dark:focus:bg-slate-950 dark:focus:shadow-[var(--color-primary-glow)] dark:[color-scheme:dark]"
                                        >
                                            <option value="">En el siguiente horario disponible</option>
                                            <option value="mañana">🌅 Por la mañana (9am - 12pm)</option>
                                            <option value="mediodía">☀️ Al mediodía (12pm - 3pm)</option>
                                            <option value="tarde">🌇 Por la tarde (3pm - 6pm)</option>
                                        </select>
                                    </div>

                                    <div class="group">
                                        <label class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-700 transition-colors dark:text-slate-300">
                                            Cuéntanos brevemente qué necesitas
                                            <span class="text-slate-400 dark:text-slate-600">(opcional)</span>
                                        </label>
                                        <textarea
                                            v-model="form.mensaje"
                                            rows="3"
                                            placeholder="Ej. Mi minisplit no enfría y quiero agendar una revisión."
                                            class="input-field w-full resize-none rounded-2xl border border-slate-200/80 bg-slate-50/80 px-5 py-4 text-slate-900 outline-none transition-all duration-300 placeholder:text-slate-400 focus:border-[var(--color-primary)] focus:bg-white focus:shadow-lg focus:shadow-[var(--color-primary-soft)] focus:ring-0 dark:border-white/10 dark:bg-slate-950/60 dark:text-white dark:placeholder:text-slate-600 dark:focus:border-[var(--color-primary)] dark:focus:bg-slate-950 dark:focus:shadow-[var(--color-primary-glow)]"
                                        ></textarea>
                                        <p v-if="form.errors.mensaje" class="mt-2 text-sm font-medium text-red-500">{{ form.errors.mensaje }}</p>
                                    </div>

                                    <p v-if="form.errors.general" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 transition-colors dark:border-red-400/20 dark:bg-red-500/10 dark:text-red-300">
                                        {{ form.errors.general }}
                                    </p>

                                    <!-- Submit Button -->
                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="submit-btn group relative flex w-full items-center justify-center gap-3 overflow-hidden rounded-2xl bg-gradient-to-r from-[var(--color-primary)] via-orange-600 to-orange-500 px-6 py-5 text-base font-black uppercase tracking-wider text-white shadow-xl shadow-orange-400/30 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-2xl hover:shadow-orange-400/40 active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-60 dark:shadow-orange-500/20 dark:hover:shadow-orange-500/30"
                                    >
                                        <!-- Shine effect -->
                                        <div class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/20 to-transparent transition-transform duration-700 group-hover:translate-x-full"></div>
                                        <span class="relative">{{ form.processing ? 'Enviando...' : '📞 Quiero que me llamen' }}</span>
                                        <svg v-if="!form.processing" class="relative h-5 w-5 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                        </svg>
                                        <svg v-else class="relative h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </button>

                                    <p class="text-center text-[11px] leading-5 text-slate-400 transition-colors dark:text-slate-500">
                                        Al enviar aceptas que usemos tus datos para contactarte y coordinar tu cita. 🔒 Datos protegidos.
                                    </p>
                                </form>
                            </div>
                        </div>

                        <!-- Direct Contact Card -->
                        <div class="contact-card mt-5 overflow-hidden rounded-[2rem] border border-slate-200/50 bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 p-6 shadow-xl transition-colors dark:border-white/[0.06] dark:from-white/[0.04] dark:to-white/[0.02]">
                            <div class="flex items-center gap-5">
                                <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-amber-500 text-xl shadow-lg shadow-amber-500/30">
                                    📱
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[10px] font-black uppercase tracking-[0.22em] text-amber-400 dark:text-amber-300">Contacto directo</p>
                                    <p class="mt-1 truncate text-lg font-black text-white dark:text-slate-100">{{ empresaData?.telefono || 'Teléfono disponible en breve' }}</p>
                                    <p class="mt-1 text-sm text-slate-400 dark:text-slate-500">Llámanos o escríbenos por WhatsApp</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <PublicFooter :empresa="empresaData" />
    </div>
</template>

<style scoped>
/* ═══════ Light Mode Background ═══════ */
.appointment-shell {
    background:
        radial-gradient(ellipse 80% 50% at 50% -10%, rgba(255, 237, 213, 0.7), transparent 50%),
        radial-gradient(ellipse 60% 40% at 80% 20%, rgba(254, 243, 199, 0.4), transparent 50%),
        linear-gradient(180deg, #fffbf5 0%, #ffffff 40%, #f8fafc 100%);
}

/* ═══════ Dark Mode Background ═══════ */
:global(.dark) .appointment-shell {
    background:
        radial-gradient(ellipse 80% 50% at 50% -15%, rgba(194, 65, 12, 0.15), transparent 50%),
        radial-gradient(ellipse 60% 40% at 80% 20%, rgba(245, 158, 11, 0.06), transparent 50%),
        linear-gradient(180deg, #020617 0%, #0f172a 45%, #111827 100%);
}

/* ═══════ Animated Orbs ═══════ */
.orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.5;
    animation: float 20s ease-in-out infinite;
}

.orb-1 {
    top: -5%;
    left: 20%;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(251, 146, 60, 0.25), transparent 70%);
    animation-delay: 0s;
}

.orb-2 {
    top: 30%;
    right: -5%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(245, 158, 11, 0.2), transparent 70%);
    animation-delay: -7s;
}

.orb-3 {
    bottom: -10%;
    left: 40%;
    width: 350px;
    height: 350px;
    background: radial-gradient(circle, rgba(194, 65, 12, 0.12), transparent 70%);
    animation-delay: -14s;
}

:global(.dark) .orb-1 {
    background: radial-gradient(circle, rgba(251, 146, 60, 0.12), transparent 70%);
}
:global(.dark) .orb-2 {
    background: radial-gradient(circle, rgba(245, 158, 11, 0.08), transparent 70%);
}
:global(.dark) .orb-3 {
    background: radial-gradient(circle, rgba(194, 65, 12, 0.06), transparent 70%);
}

@keyframes float {
    0%, 100% { transform: translate(0, 0) scale(1); }
    25% { transform: translate(30px, -20px) scale(1.05); }
    50% { transform: translate(-20px, 15px) scale(0.95); }
    75% { transform: translate(15px, 25px) scale(1.02); }
}

/* ═══════ Grid Pattern ═══════ */
.bg-grid-pattern {
    background-image:
        linear-gradient(rgba(0,0,0,0.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,0,0,0.05) 1px, transparent 1px);
    background-size: 40px 40px;
}

:global(.dark) .bg-grid-pattern {
    background-image:
        linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px);
}

/* ═══════ Form Card Glass Effect ═══════ */
.form-card {
    backdrop-filter: blur(20px);
}

:global(.dark) .form-card {
    background: rgba(15, 23, 42, 0.85);
    border-color: rgba(255, 255, 255, 0.06);
    box-shadow:
        0 0 0 1px rgba(255, 255, 255, 0.03) inset,
        0 25px 50px -12px rgba(0, 0, 0, 0.5);
}

/* ═══════ Input Fields ═══════ */
.input-field {
    font-weight: 500;
    letter-spacing: 0.01em;
}

.input-field:focus {
    transform: translateY(-1px);
}

/* ═══════ Feature Cards ═══════ */
.feature-card::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    padding: 1px;
    background: linear-gradient(135deg, rgba(255,255,255,0.8), transparent 50%);
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.5s;
}

.feature-card:hover::before {
    opacity: 1;
}

:global(.dark) .feature-card::before {
    background: linear-gradient(135deg, rgba(255,255,255,0.1), transparent 50%);
}

/* ═══════ Contact Card ═══════ */
.contact-card {
    position: relative;
}

.contact-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(251, 191, 36, 0.4), transparent);
}

:global(.dark) .contact-card {
    background: linear-gradient(135deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02));
}

/* ═══════ Submit Button ═══════ */
.submit-btn::before {
    content: '';
    position: absolute;
    inset: -2px;
    border-radius: 1rem;
    padding: 2px;
    background: linear-gradient(135deg, rgba(255,255,255,0.3), transparent, rgba(255,255,255,0.1));
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    pointer-events: none;
}

/* ═══════ Smooth transitions for all interactive elements ═══════ */
* {
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
