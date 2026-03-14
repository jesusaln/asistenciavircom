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

        <!-- Why Us Section (Mexico Context) -->
        <section class="py-24 bg-gradient-to-b from-white to-slate-50 dark:from-slate-900 dark:to-slate-950 transition-colors duration-300">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-5xl font-black text-gray-900 dark:text-white tracking-tighter mb-4">¿Por qué confiar en nosotros?</h2>
                    <p class="text-[var(--color-primary)] font-black uppercase tracking-[0.2em] text-xs">Experiencia Local para un mundo Global</p>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    <div v-for="(reason, i) in [
                        {title: 'Soporte 24/7 en México', desc: 'Entendemos los tiempos y la urgencia de las empresas locales.', icon: 'headset'},
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

        <!-- Marcas / Proveedores Section -->
        <section v-if="marcas?.length" class="py-24 bg-slate-50 dark:bg-slate-950 transition-colors duration-300">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-widest mb-4">Marcas que Respaldamos</h2>
                    <p class="text-gray-500 dark:text-gray-400 font-medium">Somos expertos certificados en las marcas líderes de la industria.</p>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
                    <div v-for="marca in marcas" :key="marca.id" class="bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800 p-8 rounded-[2rem] flex items-center justify-center grayscale hover:grayscale-0 hover:border-[var(--color-primary)] hover:shadow-xl hover:shadow-[var(--color-primary)]/5 transition-all duration-500 group">
                        <img :src="marca.logo_url" :alt="marca.nombre" class="max-h-12 w-full object-contain group-hover:scale-110 transition-transform duration-500">
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

        <PublicFooter :empresa="empresa" />
    </div>
</template>

<style scoped>
.group:hover .w-16 {
    background-color: var(--color-primary);
    color: white;
}
</style>
