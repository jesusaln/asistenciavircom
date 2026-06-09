<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import PublicFooter from '@/Components/PublicFooter.vue';
import WhatsAppWidget from '@/Components/WhatsAppWidget.vue';

const props = defineProps({
    empresa: Object,
    planes: Array,
});

const page = usePage();

// Combinar datos globales con props para asegurar colores corporativos e información completa
const empresaData = computed(() => {
    const globalConfig = page.props.empresa_config || {};
    const localProp = props.empresa || {};
    return { ...globalConfig, ...localProp };
});

const billingCycle = ref('monthly'); // 'monthly' or 'yearly'

// Variables CSS con fallback
const cssVars = computed(() => ({
    '--color-primary': empresaData.value.color_principal || '#3B82F6',
    '--color-primary-soft': (empresaData.value.color_principal || '#3B82F6') + '15',
    '--color-primary-dark': (empresaData.value.color_principal || '#3B82F6') + 'dd',
    '--color-secondary': empresaData.value.color_secundario || '#D97706',
    '--color-terciary': empresaData.value.color_terciario || '#fbbf24',
    '--color-terciary-soft': (empresaData.value.color_terciario || '#fbbf24') + '15',
}));

const formatPrice = (precio) => {
    const num = parseFloat(precio);
    return isNaN(num) ? '0.00' : num.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const getFaIcon = (plan) => {
    if (plan.icono && plan.icono.includes('-')) return plan.icono;
    
    const iconos = {
        mantenimiento: 'wrench',
        soporte: 'headset',
        garantia: 'shield-halved',
        premium: 'crown',
        personalizado: 'building-shield',
        cctv: 'camera',
        alarmas: 'bell',
        pos: 'cash-register',
        asesoria: 'handshake'
    };
    
    const name = plan.nombre.toLowerCase();
    if (name.includes('cctv')) return 'camera';
    if (name.includes('alarma')) return 'bell';
    if (name.includes('pos')) return 'cash-register';
    if (name.includes('asesor')) return 'handshake';
    
    return iconos[plan.tipo] || 'shield-halved';
};

// Procesar planes para asegurar cálculo de descuento del 15%
const planesCalculados = computed(() => {
    return (props.planes || []).map(plan => {
        if (parseFloat(plan.precio_mensual) > 0) {
             const mensual = parseFloat(plan.precio_mensual);
             const anualSinDescuento = mensual * 12;
             const descuento = 0.15; // 15% estándar
             const precioAnual = plan.precio_anual && plan.precio_anual > 0 ? parseFloat(plan.precio_anual) : (anualSinDescuento * (1 - descuento));
             const ahorro = anualSinDescuento - precioAnual;
             
             return {
                 ...plan,
                 precio_mensual: mensual,
                 precio_anual: precioAnual,
                 ahorro_anual: ahorro
             };
        }
        return plan;
    });
});

const isVisible = ref(false);
onMounted(() => {
    isVisible.value = true;
});

// Simulador State
const simulador = ref({
    pcs: 5,
    addons: {
        'CONTPAQi': false,
        'Servidores': false,
        'CCTV': false,
        'Redes': false
    },
    addonsConfig: {
        'CONTPAQi': 850,
        'Servidores': 1200,
        'CCTV': 600,
        'Redes': 750
    }
});

const toggleAddon = (name) => {
    simulador.value.addons[name] = !simulador.value.addons[name];
};

const costoSimulado = computed(() => {
    let base = 1500;
    const pcs = simulador.value.pcs;
    if (pcs > 5) {
        let pcPrice = 250;
        if (pcs > 20) pcPrice = 200;
        base = pcs * pcPrice;
    }
    let totalAddons = 0;
    Object.keys(simulador.value.addons).forEach(key => {
        if (simulador.value.addons[key]) {
            totalAddons += simulador.value.addonsConfig[key];
        }
    });
    return base + totalAddons;
});

const horasSimuladas = computed(() => {
    const pcs = simulador.value.pcs;
    if (pcs <= 5) return 3;
    return Math.ceil(pcs / 2);
});
</script>

<template>
    <Head :title="`Pólizas de Servicio - ${empresaData?.nombre_empresa || 'Servicios'}`">
        <meta name="description" :content="`Protección total con nuestras pólizas de soporte técnico y mantenimiento en ${empresaData?.ciudad || 'Hermosillo'}.`" />
    </Head>

    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 transition-colors duration-300 overflow-x-hidden" :style="cssVars">
        <!-- Floating Elements -->
        <WhatsAppWidget :whatsapp="empresaData?.whatsapp" :empresaNombre="empresaData?.nombre_empresa" />
        <PublicNavbar :empresa="empresaData" activeTab="polizas" />

        <!-- Hero Section con Diseño de Landing -->
        <section class="relative pt-32 pb-24 overflow-hidden">
            <div class="absolute inset-0 z-0 opacity-30 dark:opacity-20 pointer-events-none">
                <div class="absolute top-0 left-1/4 w-[500px] h-[500px] bg-[var(--color-primary)] blur-[120px] rounded-full animate-pulse-slow"></div>
                <div class="absolute bottom-0 right-1/4 w-[400px] h-[400px] bg-purple-600 blur-[100px] rounded-full"></div>
            </div>

            <div class="w-full px-4 relative z-10 text-center">
                <div class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-white/50 dark:bg-white/5 border border-white dark:border-white/10 shadow-xl backdrop-blur-md mb-10 transform hover:scale-105 transition-all duration-300">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[var(--color-primary)] opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-[var(--color-primary)]"></span>
                    </span>
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-600 dark:text-gray-300">Pólizas Premium 2026</span>
                </div>

                <h1 class="text-6xl md:text-8xl font-black text-gray-900 dark:text-white tracking-tighter leading-tight mb-8">
                    Tranquilidad <br/>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-[var(--color-primary)] via-blue-600 to-indigo-600">Total 24/7</span>
                </h1>
                
                <p class="text-xl text-gray-500 dark:text-gray-400 font-medium max-w-3xl mx-auto mb-16 leading-relaxed">
                    Soporte técnico de alto nivel, mantenimiento preventivo y respuesta prioritaria para asegurar que su negocio nunca se detenga.
                </p>

                <!-- Billing Toggle Estilo Landing -->
                <div class="flex items-center justify-center gap-6 mb-20">
                    <span :class="billingCycle === 'monthly' ? 'text-gray-900 dark:text-white font-bold' : 'text-gray-400'" class="text-sm tracking-widest uppercase transition-colors cursor-pointer" @click="billingCycle = 'monthly'">Mensual</span>
                    <button @click="billingCycle = billingCycle === 'monthly' ? 'yearly' : 'monthly'" class="w-20 h-10 bg-gray-200 dark:bg-gray-800 rounded-full relative p-1 transition-all duration-300 shadow-inner border border-gray-300 dark:border-gray-700 focus:outline-none">
                        <div :class="billingCycle === 'yearly' ? 'translate-x-10 bg-[var(--color-primary)] shadow-[0_0_15px_var(--color-primary)]' : 'translate-x-0 bg-gray-400'" class="w-8 h-8 rounded-full transition-all duration-500"></div>
                    </button>
                    <span :class="billingCycle === 'yearly' ? 'text-[var(--color-primary)] font-bold' : 'text-gray-400'" class="text-sm flex items-center gap-3 tracking-widest uppercase transition-colors cursor-pointer" @click="billingCycle = 'yearly'">
                        Anual <span class="px-3 py-1 bg-[var(--color-primary)]/10 text-[var(--color-primary)] border border-[var(--color-primary)]/20 rounded-lg text-[10px] font-black">-15% Ahorro</span>
                    </span>
                </div>
            </div>
        </section>

        <!-- Main Plans Grid -->
        <main class="w-full px-4 pb-40">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 lg:gap-12">
                <article 
                    v-for="plan in planesCalculados" 
                    :key="plan.id"
                    :class="[
                        'relative p-10 rounded-[3.5rem] border transition-all duration-700 flex flex-col group',
                        plan.destacado 
                            ? 'bg-white dark:bg-slate-900 border-[var(--color-primary)] shadow-[0_0_60px_-15px_rgba(59,130,246,0.3)] lg:-translate-y-8 z-20' 
                            : 'bg-white/80 dark:bg-slate-900/40 border-gray-100 dark:border-gray-800 backdrop-blur-xl hover:-translate-y-3 hover:shadow-2xl'
                    ]"
                >
                    <!-- Featured Badge -->
                    <div v-if="plan.destacado" class="absolute -top-6 left-1/2 -translate-x-1/2">
                        <div class="relative px-8 py-3 bg-[var(--color-primary)] text-white text-[10px] font-black uppercase tracking-widest rounded-2xl shadow-xl flex items-center gap-2">
                            <font-awesome-icon icon="crown" class="animate-bounce" /> Recomendado
                        </div>
                    </div>

                    <!-- Plan Header -->
                    <div class="mb-12 text-center">
                        <div 
                            class="w-24 h-24 rounded-3xl flex items-center justify-center text-4xl mb-8 mx-auto transition-all duration-700 group-hover:scale-110 group-hover:rotate-6 shadow-2xl relative"
                            :style="{ 
                                background: plan.destacado ? `linear-gradient(135deg, var(--color-primary), #1e40af)` : 'linear-gradient(135deg, #f8fafc, #f1f5f9)',
                                color: plan.destacado ? 'white' : 'var(--color-primary)'
                            }"
                        >
                             <div class="dark:hidden" v-if="!plan.destacado"></div>
                             <!-- Dark mode adjustments for icon bg -->
                             <div v-if="!plan.destacado" class="absolute inset-0 dark:bg-slate-800 rounded-3xl -z-10 hidden dark:block"></div>
                             
                             <font-awesome-icon :icon="getFaIcon(plan)" class="drop-shadow-lg" />
                        </div>
                        <h2 class="text-4xl font-black text-gray-900 dark:text-white mb-3 tracking-tight group-hover:text-[var(--color-primary)] transition-colors">{{ plan.nombre }}</h2>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.3em]">{{ plan.tipo_label }}</p>
                    </div>

                    <!-- Pricing Section -->
                    <div class="mb-12 text-center flex-shrink-0">
                        <template v-if="parseFloat(plan.precio_mensual) > 0">
                            <div class="flex items-baseline justify-center gap-1 mb-2">
                                <span class="text-2xl text-gray-400 font-bold">$</span>
                                <span class="text-7xl font-black text-transparent bg-clip-text bg-gradient-to-b from-gray-900 to-gray-500 dark:from-white dark:to-slate-400 tracking-tighter">
                                    {{ billingCycle === 'monthly' ? formatPrice(plan.precio_mensual).split('.')[0] : formatPrice(plan.precio_anual / 12).split('.')[0] }}
                                </span>
                                <span class="text-2xl text-gray-400 font-bold">.{{ formatPrice(plan.precio_mensual).split('.')[1] }}</span>
                            </div>
                            <p class="text-xs text-gray-400 font-black uppercase tracking-widest">pesos por mes</p>
                            
                            <div v-if="billingCycle === 'yearly'" class="mt-6 animate-fade-in">
                                <span class="px-5 py-2 bg-green-500/10 text-green-500 border border-green-500/20 rounded-xl text-[10px] font-black uppercase tracking-tighter">
                                    Ahorras ${{ formatPrice(plan.ahorro_anual) }} al año
                                </span>
                            </div>
                        </template>
                        <template v-else>
                            <div class="py-4">
                                <h3 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase leading-none mb-4">Soluciones <br/> a Medida</h3>
                                <p class="text-[var(--color-primary)] text-[10px] font-black uppercase tracking-[0.25em]">Diseño y Soporte VIP</p>
                            </div>
                        </template>
                    </div>

                    <!-- Features List -->
                    <ul class="space-y-6 mb-12 flex-grow">
                        <li v-for="beneficio in plan.beneficios_array" :key="beneficio" class="flex items-start gap-4 group/item">
                            <div class="w-6 h-6 rounded-full bg-[var(--color-primary-soft)] dark:bg-white/5 flex items-center justify-center text-[var(--color-primary)] text-[10px] flex-shrink-0 group-hover/item:bg-[var(--color-primary)] group-hover/item:text-white transition-all duration-300">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7" /></svg>
                            </div>
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-300 leading-snug group-hover/item:text-gray-900 dark:group-hover/item:text-white transition-colors">{{ beneficio }}</span>
                        </li>
                    </ul>

                    <!-- Call to Action -->
                    <Link 
                        v-if="parseFloat(plan.precio_mensual) > 0"
                        :href="route('contratacion.show', plan.slug)"
                        class="w-full py-6 rounded-3xl font-black text-[11px] uppercase tracking-widest text-center transition-all duration-500 relative overflow-hidden group/btn shadow-xl"
                        :class="plan.destacado ? 'bg-[var(--color-primary)] text-white hover:shadow-[var(--color-primary)]/50' : 'bg-gray-900 text-white dark:bg-white dark:text-gray-900 hover:opacity-90'"
                    >
                        <div class="absolute inset-0 bg-white/20 translate-y-full group-hover/btn:translate-y-0 transition-transform duration-500"></div>
                        <span class="relative z-10">Contratar Plan</span>
                    </Link>
                    <a 
                        v-else
                        :href="'https://wa.me/' + (empresaData.whatsapp || '521234567890')"
                        target="_blank"
                        class="w-full py-6 rounded-3xl font-black text-[11px] uppercase tracking-widest text-center border-2 border-[var(--color-primary)] text-[var(--color-primary)] hover:bg-[var(--color-primary)] hover:text-white transition-all duration-500 shadow-xl"
                    >
                        Contactar Ventas
                    </a>
                </article>
            </div>
        </main>

        <!-- SECCIÓN: SIMULADOR INTELIGENTE (ESTÉTICA LANDING) -->
        <section class="w-full px-4 py-32 bg-gray-900 dark:bg-black text-white relative overflow-hidden">
             <!-- Cyber background effects -->
             <div class="absolute inset-0">
                <div class="absolute top-0 right-0 w-full h-full bg-[radial-gradient(circle_at_top_right,rgba(59,130,246,0.1),transparent_50%)]"></div>
             </div>

             <div class="max-w-7xl mx-auto relative z-10">
                <div class="grid lg:grid-cols-2 gap-24 items-center">
                    <div>
                        <div class="inline-block px-4 py-2 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 text-[10px] font-black uppercase tracking-widest mb-8">Calculadora de Inversión</div>
                        <h2 class="text-5xl md:text-7xl font-black mb-8 tracking-tighter leading-tight">
                            Personalice su <br/>
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-[var(--color-primary)] to-cyan-400">Protección TI</span>
                        </h2>
                        <p class="text-xl text-gray-400 font-medium mb-12 leading-relaxed">
                            Ajuste su póliza según el tamaño de su empresa. Obtenga una estimación instantánea y transparente de su inversión mensual.
                        </p>
                        
                        <div class="space-y-8">
                            <div class="flex items-start gap-6 group">
                                <div class="w-14 h-14 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-2xl group-hover:bg-[var(--color-primary)] transition-all duration-500">⚡</div>
                                <div>
                                    <h4 class="font-bold text-lg mb-1">Cálculo en Tiempo Real</h4>
                                    <p class="text-gray-400 text-sm">Visualice el impacto de cada equipo adicional al instante.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-6 group">
                                <div class="w-14 h-14 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-2xl group-hover:bg-[var(--color-primary)] transition-all duration-500">🛡️</div>
                                <div>
                                    <h4 class="font-bold text-lg mb-1">Garantía de Servicio</h4>
                                    <p class="text-gray-400 text-sm">Nuestros algoritmos aseguran el mejor SLA según su carga.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Calculadora Layout -->
                    <div class="bg-gray-800 dark:bg-indigo-950/20 backdrop-blur-3xl rounded-[4rem] p-12 lg:p-16 border border-white/10 shadow-3xl">
                        <div class="space-y-12">
                            <div>
                                <div class="flex justify-between items-center mb-6">
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Estaciones de Trabajo (PC/Laptop)</label>
                                    <span class="px-4 py-2 bg-blue-500 text-white font-black rounded-xl text-xl shadow-lg shadow-blue-500/30">{{ simulador.pcs }}</span>
                                </div>
                                <input type="range" v-model="simulador.pcs" min="1" max="50" class="w-full h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer accent-[var(--color-primary)]" />
                            </div>

                            <div>
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-6 block">Módulos Especializados</label>
                                <div class="grid grid-cols-2 gap-4">
                                    <button 
                                        v-for="(costo, serv) in simulador.addonsConfig" 
                                        :key="serv"
                                        @click="toggleAddon(serv)"
                                        :class="[
                                            'px-6 py-4 rounded-2xl border-2 transition-all duration-500 text-[10px] font-black uppercase tracking-widest flex items-center justify-center',
                                            simulador.addons[serv] ? 'bg-[var(--color-primary)] border-[var(--color-primary)] text-white shadow-lg' : 'border-white/5 bg-white/5 text-gray-400 hover:border-gray-600'
                                        ]"
                                    >
                                        {{ serv }}
                                    </button>
                                </div>
                            </div>

                            <div class="pt-12 border-t border-white/10 flex flex-col md:flex-row justify-between items-center gap-10">
                                <div>
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Inversión Mensual Estimada</p>
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-gray-400 text-2xl font-bold">$</span>
                                        <span class="text-6xl font-black text-white tracking-tighter">{{ formatPrice(costoSimulado).split('.')[0] }}</span>
                                        <span class="text-2xl text-gray-400 font-bold">.{{ formatPrice(costoSimulado).split('.')[1] }}</span>
                                    </div>
                                    <p class="text-[10px] text-blue-400 font-bold mt-3 uppercase tracking-tighter">* Basado en {{ horasSimuladas }} horas de soporte incluidas</p>
                                </div>
                                <button class="w-full md:w-auto px-10 py-6 bg-white text-gray-900 rounded-[2rem] font-black text-[11px] uppercase tracking-widest shadow-2xl hover:scale-105 transition-all">
                                    Lo quiero
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
             </div>
        </section>

        <!-- Footer -->
        <PublicFooter :empresa="empresaData" />
    </div>
</template>

<style scoped>
@keyframes pulse-slow {
    0%, 100% { opacity: 0.1; transform: scale(1); }
    50% { opacity: 0.2; transform: scale(1.1); }
}
.animate-pulse-slow {
    animation: pulse-slow 8s ease-in-out infinite;
}
.animate-fade-in {
    animation: fadeIn 0.8s ease-out forwards;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Custom pricing text-fill effect */
.dark span.text-transparent {
    -webkit-text-stroke: 1px rgba(255, 255, 255, 0.1);
}
</style>
