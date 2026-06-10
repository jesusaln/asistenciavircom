<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import PublicFooter from '@/Components/PublicFooter.vue';
import WhatsAppWidget from '@/Components/WhatsAppWidget.vue';

const props = defineProps({
    empresa: Object,
    planes: Array,
});

const page = usePage();

const empresaData = computed(() => {
    const globalConfig = page.props.empresa_config || {};
    const localProp = props.empresa || {};
    return { ...globalConfig, ...localProp };
});

const cssVars = computed(() => ({
    '--color-primary': '#10b981', // Emerald for Rentas
    '--color-primary-soft': '#10b98115',
    '--color-primary-dark': '#059669',
    '--color-secondary': '#3b82f6',
    '--color-terciary': '#f59e0b',
    '--color-terciary-soft': '#f59e0b15',
}));

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { 
        style: 'currency', 
        currency: 'MXN',
        minimumFractionDigits: 2
    }).format(value || 0);
};

const getFaIcon = (tipo) => {
    const iconos = {
        pdv: 'cash-register',
        oficina: 'laptop',
        gaming: 'gamepad',
        laptop: 'mobile-alt',
        personalizado: 'tools',
    };
    return iconos[tipo] || 'desktop';
};
</script>

<template>
    <Head :title="`Planes de Renta PDV - ${empresaData?.nombre_empresa || 'Servicios'}`">
        <meta name="description" content="Arrendamiento de equipos de cómputo y puntos de venta. Planes mensuales con mantenimiento y soporte técnico incluido." />
    </Head>

    <div class="min-h-screen bg-[var(--ui-surface)] transition-colors duration-200" :style="cssVars">
        <WhatsAppWidget :whatsapp="empresaData?.whatsapp" :empresaNombre="empresaData?.nombre_empresa || empresaData?.nombre" />
        <PublicNavbar :empresa="empresaData" activeTab="rentas" />

        <!-- Hero Section -->
        <section class="relative pt-24 pb-20 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-white via-emerald-50/20 to-white dark:from-slate-900 dark:via-emerald-950/10 dark:to-slate-900 -z-20"></div>
            
            <div class="w-full px-4 text-center">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-100 dark:bg-slate-800/30 border border-emerald-200 dark:border-emerald-800/30 dark:border-emerald-800 mb-8 animate-fade-in shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-brand-500 animate-pulse"></span>
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-emerald-300">Equipamiento en Arrendamiento</span>
                </div>
                
                <h1 class="text-5xl md:text-7xl font-black text-slate-900 dark:text-white mb-8 tracking-tighter leading-[1.1]">
                    Renta de Equipos <br/>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-600 to-blue-600">Todo Incluido</span>
                </h1>
                
                <p class="text-xl text-slate-500 dark:text-slate-400 font-medium mb-8 max-w-3xl mx-auto leading-relaxed">
                    Olvídese de la inversión inicial. Obtenga equipos de última generación para su negocio con soporte técnico y mantenimiento preventivo garantizado.
                </p>
            </div>
        </section>

        <!-- Planes Grid -->
        <main class="w-full px-4 pb-32">
            <div v-if="planes?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-7xl mx-auto">
                <article 
                    v-for="plan in planes" 
                    :key="plan.id"
                    :class="[
                        'relative bg-white dark:bg-slate-800 p-8 rounded-[2.5rem] border shadow-2xl transition-all duration-500 flex flex-col group',
                        plan.destacado ? 'border-emerald-500 ring-4 ring-emerald-500/10 lg:-translate-y-4 shadow-emerald-200/50' : 'border-slate-50 dark:border-slate-700 shadow-slate-100/50 dark:shadow-none hover:shadow-xl hover:shadow-xl'
                    ]"
                >
                    <!-- Badge Destacado -->
                    <div v-if="plan.destacado" class="absolute -top-5 left-1/2 -translate-x-1/2 px-6 py-2 bg-emerald-600 text-white text-[10px] font-black uppercase tracking-wide rounded-full shadow-xl">
                        Más Popular
                    </div>

                    <!-- Header -->
                    <div class="mb-8">
                        <div 
                            class="w-16 h-16 rounded-2xl flex items-center justify-center text-3xl mb-6 transition-all duration-500 group-hover:scale-105 shadow-xl"
                            :style="{ backgroundColor: plan.color + '15', color: plan.color }"
                        >
                            {{ plan.icono || '🖥️' }}
                        </div>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white mb-1 leading-tight">{{ plan.nombre }}</h2>
                        <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">{{ plan.tipo_label }}</span>
                    </div>

                    <!-- Precio -->
                    <div class="mb-8 pt-6 border-t border-slate-50 dark:border-slate-700 transition-colors">
                        <div class="flex items-baseline gap-1">
                            <span class="text-slate-400 dark:text-slate-500 text-xl font-bold">$</span>
                            <span class="text-5xl font-black text-slate-900 dark:text-white tracking-tighter">
                                {{ formatCurrency(plan.precio_mensual).replace('$', '').replace('.00', '') }}
                            </span>
                            <span class="text-slate-400 dark:text-slate-500 font-bold">/mes</span>
                        </div>
                            <p class="text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mt-2">+ {{ formatCurrency(plan.deposito_garantia) }} de depósito</p>
                    </div>

                    <!-- Equipamiento -->
                    <div class="mb-6">
                        <h4 class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-4">Equipamiento:</h4>
                        <ul class="space-y-3">
                            <li v-for="equipo in plan.equipamiento_incluido" :key="equipo" class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-brand-500 flex-shrink-0"></span>
                                <span class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ equipo }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Beneficios -->
                    <div class="mb-10 flex-grow pt-6 border-t border-slate-50 dark:border-slate-700">
                        <h4 class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-4">Incluye:</h4>
                        <ul class="space-y-3">
                            <li v-for="beneficio in plan.beneficios" :key="beneficio" class="flex items-start gap-3">
                                <svg class="w-4 h-4 text-emerald-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                <span class="text-sm font-medium text-slate-500 dark:text-slate-400 leading-tight">{{ beneficio }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Botón de Acción -->
                    <Link 
                        :href="route('contratacion.renta.show', plan.slug)"
                        class="w-full py-4 bg-emerald-600 text-white rounded-2xl font-black text-xs uppercase tracking-wide text-center shadow-xl shadow-emerald-500/20 hover:bg-emerald-700 hover:shadow-emerald-500/40 transition-all flex items-center justify-center gap-3"
                    >
                        Contratar Plan
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                    </Link>
                </article>
            </div>

            <!-- Empty State -->
            <div v-else class="py-24 text-center">
                <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-8 text-4xl">📦</div>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-2">Planes en preparación</h3>
                <p class="text-slate-500 dark:text-slate-400 font-medium">Estamos configurando los mejores paquetes para tu negocio.</p>
            </div>
        </main>

        <PublicFooter :empresa="empresaData" />
    </div>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.8s ease-out forwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>