<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import PublicFooter from '@/Components/PublicFooter.vue';

const props = defineProps({
    empresa: Object,
    logos: Array,
    marcas: Array,
});

const cssVars = computed(() => ({
    '--color-primary': props.empresa?.color_principal || '#3b82f6',
    '--color-primary-soft': (props.empresa?.color_principal || '#3b82f6') + '15',
    '--color-secondary': props.empresa?.color_secundario || '#6b7280',
    '--color-tertiary': props.empresa?.color_terciario || '#fbbf24',
}));

const companyStats = [
    { value: '10+', label: 'Anos impulsando operaciones con tecnologia' },
    { value: 'Empresas y hogares', label: 'Soluciones adaptadas a cada necesidad' },
    { value: 'Soporte y ejecucion', label: 'Instalacion, diagnostico y seguimiento real' },
];

const workProcess = [
    {
        step: '01',
        title: 'Entendemos tu operacion',
        desc: 'Partimos de tu problema real, tu giro y tus prioridades antes de proponer cualquier equipo o servicio.',
        icon: 'comments',
    },
    {
        step: '02',
        title: 'Diseñamos una solucion viable',
        desc: 'Aterrizamos alcance, propuesta y tecnologia para que la inversion tenga sentido tecnico y comercial.',
        icon: 'clipboard-check',
    },
    {
        step: '03',
        title: 'Implementamos y acompañamos',
        desc: 'Ejecutamos con orden, capacitamos y damos seguimiento para que la solucion realmente funcione en el dia a dia.',
        icon: 'tools',
    },
];

const differentiators = [
    {
        title: 'Diagnostico antes de vender',
        desc: 'No empujamos equipos por catalogo. Primero entendemos el problema y despues proponemos.',
        icon: 'bullseye',
    },
    {
        title: 'Soluciones integrales',
        desc: 'Combinamos seguridad, conectividad, soporte y desarrollo para resolver mas con un solo aliado.',
        icon: 'layer-group',
    },
    {
        title: 'Seguimiento postventa',
        desc: 'No desaparecemos despues de instalar. Damos continuidad para que todo siga operando como debe.',
        icon: 'check-double',
    },
];

const serviceFootprint = [
    'Empresas',
    'Oficinas',
    'Comercios',
    'Bodegas',
    'Escuelas',
    'Hogares',
];

const finalWhatsappHref = computed(() => {
    const phone = String(props.empresa?.whatsapp || props.empresa?.telefono || '').replace(/\D/g, '');
    if (!phone) return route('public.contacto');
    const message = 'Hola, quiero conocer mas sobre sus servicios y recibir una propuesta para mi negocio.';
    return `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
});

const downloadCurriculum = () => {
    window.open(route('public.curriculum.pdf'), '_blank');
};

</script>

<template>
    <Head title="Quienes Somos" />

    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 transition-colors duration-300" :style="cssVars">
        <PublicNavbar :empresa="empresa" activeTab="quienes-somos" />

        <!-- Hero Section -->
        <header class="relative py-20 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-[var(--color-primary)]/10 to-transparent"></div>
            <div class="max-w-7xl mx-auto px-4 relative z-10 text-center">
                <span class="inline-block px-4 py-1.5 rounded-full bg-[var(--color-primary-soft)] text-[var(--color-primary)] text-xs font-black uppercase tracking-widest mb-6">Nuestra Identidad</span>
                <h1 class="text-5xl md:text-7xl font-black text-gray-900 dark:text-white mb-6 tracking-tight">
                    Expertos en <span class="text-[var(--color-primary)]">Tecnología</span> <br>y Soluciones Integrales
                </h1>
                <p class="max-w-2xl mx-auto text-lg text-gray-600 dark:text-gray-400 font-medium leading-relaxed">
                    Somos una empresa 100% mexicana comprometida con la vanguardia tecnológica. Protegemos el patrimonio y optimizamos la operación de nuestros clientes con soluciones diseñadas para los desafíos del entorno actual.
                </p>
                <div class="mt-12 grid md:grid-cols-3 gap-4 max-w-5xl mx-auto">
                    <div v-for="stat in companyStats" :key="stat.label" class="rounded-[2rem] border border-gray-100 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md px-6 py-6 shadow-sm">
                        <p class="text-xl md:text-2xl font-black text-gray-900 dark:text-white tracking-tight">{{ stat.value }}</p>
                        <p class="text-[11px] font-black uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400 mt-2">{{ stat.label }}</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Curriculum Section -->
        <section class="py-20 bg-white dark:bg-slate-900 overflow-hidden">
            <div class="max-w-7xl mx-auto px-4">
                <div class="grid md:grid-cols-2 gap-16 items-center">
                    <div class="order-2 md:order-1">
                        <div class="space-y-12">
                            <div class="flex gap-6 group p-6 rounded-3xl hover:bg-white dark:hover:bg-slate-800 transition-all duration-500 hover:shadow-xl hover:shadow-[var(--color-primary-soft)] border border-transparent hover:border-gray-100 dark:hover:border-slate-700">
                                <div class="w-16 h-16 shrink-0 bg-blue-50 dark:bg-blue-900/20 rounded-2xl flex items-center justify-center text-3xl group-hover:bg-[var(--color-primary)] group-hover:text-white transition-all shadow-sm">
                                    <font-awesome-icon icon="bullseye" />
                                </div>
                                <div>
                                    <h3 class="text-xl font-black text-gray-900 dark:text-white mb-2 uppercase tracking-wide">Nuestra Misión</h3>
                                    <p class="text-gray-600 dark:text-gray-400 leading-relaxed text-sm md:text-base">{{ empresa.mision }}</p>
                                </div>
                            </div>
                            <div class="flex gap-6 group p-6 rounded-3xl hover:bg-white dark:hover:bg-slate-800 transition-all duration-500 hover:shadow-xl hover:shadow-[var(--color-primary-soft)] border border-transparent hover:border-gray-100 dark:hover:border-slate-700">
                                <div class="w-16 h-16 shrink-0 bg-amber-50 dark:bg-amber-900/20 rounded-2xl flex items-center justify-center text-3xl group-hover:bg-[var(--color-primary)] group-hover:text-white transition-all shadow-sm">
                                    <font-awesome-icon icon="eye" />
                                </div>
                                <div>
                                    <h3 class="text-xl font-black text-gray-900 dark:text-white mb-2 uppercase tracking-wide">Nuestra Visión</h3>
                                    <p class="text-gray-600 dark:text-gray-400 leading-relaxed text-sm md:text-base">{{ empresa.vision }}</p>
                                </div>
                            </div>
                            <div class="flex gap-6 group p-6 rounded-3xl hover:bg-white dark:hover:bg-slate-800 transition-all duration-500 hover:shadow-xl hover:shadow-[var(--color-primary-soft)] border border-transparent hover:border-gray-100 dark:hover:border-slate-700">
                                <div class="w-16 h-16 shrink-0 bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl flex items-center justify-center text-3xl group-hover:bg-[var(--color-primary)] group-hover:text-white transition-all shadow-sm">
                                    <font-awesome-icon icon="gem" />
                                </div>
                                <div>
                                    <h3 class="text-xl font-black text-gray-900 dark:text-white mb-2 uppercase tracking-wide">Nuestros Valores</h3>
                                    <div class="flex flex-wrap gap-2 mt-3">
                                        <span v-for="valor in empresa.valores" :key="valor" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-gray-700 dark:text-gray-300 text-xs font-black rounded-xl uppercase tracking-wider border border-gray-200 dark:border-slate-700">{{ valor }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botón Descargar Curriculum -->
                        <div class="mt-12">
                            <button 
                                @click="downloadCurriculum"
                                class="inline-flex items-center gap-4 px-8 py-5 bg-[var(--color-primary)] text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl shadow-[var(--color-primary)]/20 hover:-translate-y-1 hover:shadow-2xl hover:shadow-[var(--color-primary)]/30 transition-all group"
                            >
                                <svg class="w-6 h-6 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Descargar Curriculum (PDF)
                            </button>
                        </div>
                    </div>
                    
                    <div class="order-1 md:order-2 relative">
                        <div class="aspect-square bg-gradient-to-tr from-[var(--color-primary)] to-indigo-600 rounded-[4rem] rotate-3 relative overflow-hidden shadow-2xl group">
                             <img src="/img/quienes-somos.png" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-1000" alt="Nuestra Empresa">
                             <div class="absolute inset-0 bg-black/20 backdrop-blur-[2px] group-hover:bg-black/0 transition-all duration-700"></div>
                             <img v-if="empresa.logo_url" :src="empresa.logo_url" class="absolute inset-0 m-auto w-1/3 h-auto object-contain filter invert brightness-200 opacity-80 group-hover:scale-110 transition-transform duration-700" :alt="empresa.nombre">
                        </div>
                        <!-- Decorative dots -->
                        <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-[radial-gradient(circle_at_center,_var(--color-primary)_1px,_transparent_1px)] bg-[size:20px_20px] opacity-20"></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-24 bg-slate-50 dark:bg-slate-950 transition-colors duration-300">
            <div class="max-w-7xl mx-auto px-4">
                <div class="max-w-3xl mb-14">
                    <p class="text-[var(--color-primary)] font-black uppercase tracking-[0.22em] text-xs mb-4">Como Trabajamos</p>
                    <h2 class="text-3xl md:text-5xl font-black text-gray-900 dark:text-white tracking-tighter mb-4">Un proceso claro para darte resultados y no solo promesas</h2>
                    <p class="text-lg text-gray-600 dark:text-gray-400 leading-relaxed">
                        Nuestra forma de trabajar busca reducir improvisacion, acelerar decisiones y asegurar que cada proyecto quede alineado con la realidad operativa del cliente.
                    </p>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    <div v-for="item in workProcess" :key="item.step" class="rounded-[2.5rem] bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800 p-8 shadow-sm hover:shadow-2xl hover:-translate-y-1 transition-all duration-500">
                        <div class="flex items-center justify-between mb-8">
                            <div class="w-14 h-14 rounded-2xl bg-[var(--color-primary-soft)] text-[var(--color-primary)] flex items-center justify-center text-xl">
                                <font-awesome-icon :icon="item.icon" />
                            </div>
                            <span class="text-3xl font-black text-gray-200 dark:text-slate-700">{{ item.step }}</span>
                        </div>
                        <h3 class="text-xl font-black text-gray-900 dark:text-white tracking-tight mb-3">{{ item.title }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ item.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Why Us Section (Mexico Context) -->
        <section class="py-24 bg-gradient-to-b from-white to-slate-50 dark:from-slate-900 dark:to-slate-950 transition-colors duration-300">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-5xl font-black text-gray-900 dark:text-white tracking-tighter mb-4">¿Por qué confiar en nosotros?</h2>
                    <p class="text-[var(--color-primary)] font-black uppercase tracking-[0.2em] text-xs">Experiencia Local para un mundo Global</p>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    <div v-for="(reason, i) in [
                        {title: 'Cobertura 24/7 con poliza de servicio', desc: 'Ofrecemos atencion prioritaria y cobertura extendida para clientes con poliza activa.', icon: 'headset'},
                        {title: 'Ingeniería de Vanguardia', desc: 'Instalaciones certificadas con los estándares internacionales más altos.', icon: 'tools'},
                        {title: 'Seguridad Tecnológica', desc: 'Sistemas inteligentes que se adaptan a la realidad de tu industria.', icon: 'shield-halved'}
                    ]" :key="i" class="bg-white dark:bg-slate-900 p-10 rounded-[2.5rem] border border-gray-100 dark:border-slate-800 shadow-lg hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 group">
                        <div class="text-5xl mb-6 text-[var(--color-primary)] group-hover:scale-110 transition-transform duration-500">
                            <font-awesome-icon :icon="reason.icon" />
                        </div>
                        <h4 class="text-xl font-black text-gray-900 dark:text-white mb-4">{{ reason.title }}</h4>
                        <p class="text-gray-500 dark:text-gray-400 leading-relaxed text-sm">{{ reason.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-24 bg-white dark:bg-slate-900 transition-colors duration-300">
            <div class="max-w-7xl mx-auto px-4">
                <div class="grid lg:grid-cols-[1.1fr_0.9fr] gap-10 items-start">
                    <div class="rounded-[2.5rem] border border-gray-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-8 md:p-10">
                        <p class="text-[var(--color-primary)] font-black uppercase tracking-[0.22em] text-xs mb-4">Lo Que Nos Distingue</p>
                        <h2 class="text-3xl md:text-4xl font-black text-gray-900 dark:text-white tracking-tighter mb-8">Mas que proveedores, buscamos ser un aliado que resuelva de verdad</h2>
                        <div class="space-y-5">
                            <div v-for="item in differentiators" :key="item.title" class="flex gap-4 rounded-2xl bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800 p-5">
                                <div class="w-12 h-12 rounded-2xl bg-[var(--color-primary-soft)] text-[var(--color-primary)] flex items-center justify-center shrink-0 text-lg">
                                    <font-awesome-icon :icon="item.icon" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-black text-gray-900 dark:text-white tracking-tight mb-1">{{ item.title }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ item.desc }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[2.5rem] border border-slate-900 dark:border-slate-700 bg-slate-900 dark:bg-slate-950 text-white p-8 md:p-10 shadow-2xl">
                        <p class="text-sky-300 font-black uppercase tracking-[0.22em] text-xs mb-4">Donde Generamos Valor</p>
                        <h3 class="text-3xl font-black tracking-tight mb-5">Sectores y entornos donde nuestra experiencia suma mas</h3>
                        <p class="text-sm text-gray-300 leading-relaxed mb-8">
                            Trabajamos con operaciones que necesitan seguridad, control, conectividad y soporte confiable para seguir creciendo sin fricciones.
                        </p>
                        <div class="flex flex-wrap gap-3">
                            <span v-for="item in serviceFootprint" :key="item" class="px-4 py-2 rounded-full bg-white/10 border border-white/10 text-[11px] font-black uppercase tracking-[0.16em] text-white">
                                {{ item }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>


        <!-- Logos Clientes Section -->
        <section v-if="logos?.length" class="py-24 bg-white dark:bg-slate-900 transition-colors duration-300">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-widest mb-4">Casos de Éxito</h2>
                    <p class="text-gray-500 dark:text-gray-400 font-medium">Empresas que ya confían en nuestra experiencia y soluciones.</p>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-6 gap-6">
                    <div v-for="logo in logos" :key="logo.id" class="flex items-center justify-center p-6 bg-slate-50 dark:bg-slate-800/50 rounded-2xl hover:bg-white dark:hover:bg-slate-800 transition-all duration-300">
                        <img :src="logo.logo_url" :alt="logo.nombre_empresa" class="max-h-10 w-full object-contain opacity-60 hover:opacity-100 transition-opacity">
                    </div>
                </div>
            </div>
        </section>

        <section class="py-24 bg-slate-950 relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-r from-[var(--color-primary)]/10 to-transparent"></div>
            <div class="max-w-5xl mx-auto px-4 relative z-10 text-center">
                <p class="text-sky-300 font-black uppercase tracking-[0.22em] text-xs mb-4">Siguiente Paso</p>
                <h2 class="text-4xl md:text-5xl font-black text-white tracking-tighter mb-6">Si buscas un equipo serio para resolver tecnologia, seguridad o soporte, hablemos</h2>
                <p class="max-w-3xl mx-auto text-lg text-gray-400 leading-relaxed mb-10">
                    Cuéntanos qué necesitas y te orientamos con una propuesta clara, aterrizada y pensada para tu operación real.
                </p>
                <div class="flex flex-wrap justify-center gap-4">
                    <a :href="finalWhatsappHref" target="_blank" rel="noopener noreferrer" class="px-8 py-4 rounded-2xl bg-[var(--color-primary)] text-white font-black text-xs uppercase tracking-[0.16em] shadow-xl hover:brightness-110 transition-all">
                        Hablar por WhatsApp
                    </a>
                    <Link :href="route('public.contacto')" class="px-8 py-4 rounded-2xl bg-white/10 border border-white/15 text-white font-black text-xs uppercase tracking-[0.16em] hover:bg-white/15 transition-all">
                        Solicitar Propuesta
                    </Link>
                </div>
            </div>
        </section>

        <PublicFooter :empresa="empresa" />
    </div>
</template>

<style scoped>
.group:hover .w-16 {
    background-color: var(--color-primary);
    color: white;
}
</style>
