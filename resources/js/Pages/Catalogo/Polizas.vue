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

// Combinar datos globales con props para asegurar colores corporativos e información completa
const empresaData = computed(() => {
    const globalConfig = page.props.empresa_config || {};
    const localProp = props.empresa || {};
    return { ...globalConfig, ...localProp };
});

const periodoSeleccionado = ref('mensual'); // mensual o anual

// Variables CSS con fallback al naranja corporativo de Climas del Desierto
const cssVars = computed(() => ({
    '--color-primary': empresaData.value.color_principal || '#3B82F6',
    '--color-primary-soft': (empresaData.value.color_principal || '#3B82F6') + '15',
    '--color-primary-dark': (empresaData.value.color_principal || '#3B82F6') + 'dd',
    '--color-secondary': empresaData.value.color_secundario || '#D97706',
    '--color-terciary': empresaData.value.color_terciario || '#FBBF24',
    '--color-terciary-soft': (empresaData.value.color_terciario || '#FBBF24') + '15',
}));

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { 
        style: 'currency', 
        currency: 'MXN',
        minimumFractionDigits: 2
    }).format(value || 0);
};

const getPrecio = (plan) => {
    if (periodoSeleccionado.value === 'anual' && plan.precio_anual) {
        return plan.precio_anual / 12; // precio mensual equivalente
    }
    return plan.precio_mensual;
};

const getTotalAnual = (plan) => {
    if (periodoSeleccionado.value === 'anual' && plan.precio_anual) {
        return plan.precio_anual;
    }
    return plan.precio_mensual * 12;
};

const getColorPlan = (plan) => {
    // Si el plan tiene un color específico en BD, lo usamos
    if (plan.color) return plan.color;
    
    // Si no, usamos el color corporativo por defecto
    return empresaData.value.color_principal || '#3B82F6';
};

const getFaIcon = (plan) => {
    if (plan.icono && plan.icono.includes('-')) return plan.icono;
    
    const iconos = {
        mantenimiento: 'wrench',
        soporte: 'headset',
        garantia: 'shield-halved',
        premium: 'crown',
        personalizado: 'building-shield',
    };
    return iconos[plan.tipo] || 'shield-halved';
};
// Procesar planes para asegurar cálculo de descuento del 15%
const planesCalculados = computed(() => {
    return (props.planes || []).map(plan => {
        if (parseFloat(plan.precio_mensual) > 0) {
             const mensual = parseFloat(plan.precio_mensual);
             const anualSinDescuento = mensual * 12;
             const descuento = 0.15; // 15% solicitado
             const precioAnual = anualSinDescuento * (1 - descuento);
             const ahorro = anualSinDescuento - precioAnual;
             
             return {
                 ...plan,
                 precio_mensual: mensual,
                 precio_anual: precioAnual,
                 ahorro_anual: ahorro
             };
        return plan;
    });
});

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
    <Head :title="`Planes de Póliza - ${empresaData?.nombre_empresa || 'Servicios'}`">
        <meta name="description" :content="`Contrata planes de mantenimiento y soporte técnico para tu empresa u hogar en ${empresaData?.ciudad || 'Hermosillo'}. Pólizas mensuales y anuales con descuentos exclusivos.`" />
    </Head>

    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-orange-50/30 dark:from-gray-950 dark:to-gray-900 transition-colors duration-300" :style="cssVars">
        <!-- Widget Flotante de WhatsApp -->
        <WhatsAppWidget :whatsapp="empresaData?.whatsapp" :empresaNombre="empresaData?.nombre_empresa || empresaData?.nombre" />

        <!-- Navbar -->
        <PublicNavbar :empresa="empresaData" activeTab="polizas" />

        <!-- Hero Section con Estética Premium -->
        <section class="relative pt-24 pb-32 overflow-hidden">
            <!-- Background Elements -->
            <div class="absolute inset-0 bg-gradient-to-br from-white via-gray-50 to-white dark:from-gray-900 dark:via-gray-950 dark:to-gray-900 -z-20 transition-colors"></div>
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[1000px] h-[600px] bg-[var(--color-primary)] opacity-[0.03] rounded-full blur-[120px] -z-10"></div>
            <div class="absolute -bottom-24 left-0 w-[600px] h-[600px] bg-[var(--color-terciary)] opacity-[0.05] rounded-full blur-[100px] -z-10"></div>

            <div class="w-full px-4 text-center">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-[var(--color-terciary-soft)] border border-[var(--color-terciary-soft)] mb-8 animate-fade-in shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-[var(--color-terciary)] animate-pulse"></span>
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--color-terciary)]">Pólizas Premium de Servicio</span>
                </div>
                
                <h1 class="text-5xl md:text-7xl font-black text-gray-900 dark:text-white dark:text-white mb-8 tracking-tighter leading-[1.1] transition-colors">
                    Seguridad y Confort <br/>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-[var(--color-primary)] to-[var(--color-secondary)]">Sin Límites</span>
                </h1>
                
                <p class="text-xl text-gray-500 dark:text-gray-400 dark:text-gray-400 font-medium mb-12 w-full leading-relaxed transition-colors">
                    Protección total para sus equipos de climatización. Atención prioritaria, mantenimientos preventivos incluidos y la tranquilidad que su hogar necesita.
                </p>
                
                <!-- Toggle Mensual/Anual Premium -->
                <div class="inline-flex p-1.5 bg-white dark:bg-slate-900 dark:bg-gray-800 shadow-xl shadow-gray-200/50 dark:shadow-none rounded-2xl border border-gray-100 dark:border-gray-700 transition-colors">
                    <button 
                        @click="periodoSeleccionado = 'mensual'"
                        :class="[
                            'px-8 py-3 rounded-xl font-black text-xs uppercase tracking-widest transition-all',
                            periodoSeleccionado === 'mensual' ? 'bg-[var(--color-primary)] text-white shadow-lg' : 'text-gray-400 dark:text-gray-500 dark:text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300'
                        ]"
                    >
                        Mensual
                    </button>
                    <button 
                        @click="periodoSeleccionado = 'anual'"
                        :class="[
                            'px-8 py-3 rounded-xl font-black text-xs uppercase tracking-widest transition-all gap-3 flex items-center',
                            periodoSeleccionado === 'anual' ? 'bg-[var(--color-primary)] text-white shadow-lg' : 'text-gray-400 dark:text-gray-500 dark:text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300'
                        ]"
                    >
                        Anual
                        <span v-if="periodoSeleccionado !== 'anual'" class="px-2 py-0.5 bg-green-100 dark:bg-green-900/20 text-green-600 dark:text-green-300 rounded-full text-[8px]">-15%</span>
                        <span v-else class="px-2 py-0.5 bg-white dark:bg-slate-900/20 text-white rounded-full text-[8px]">Ahorro</span>
                    </button>
                </div>
            </div>
        </section>

        <!-- Planes Grid -->
        <main class="w-full px-4 pb-32">
            <div v-if="planesCalculados?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <article 
                    v-for="plan in planesCalculados" 
                    :key="plan.id"
                    :class="[
                        'relative bg-white dark:bg-slate-900 dark:bg-gray-800 p-10 rounded-[3rem] border shadow-2xl transition-all duration-500 flex flex-col group',
                        plan.destacado ? 'border-[var(--color-primary)] ring-4 ring-[var(--color-primary-soft)] lg:-translate-y-4 shadow-xl' : 'border-gray-50 dark:border-gray-700 shadow-gray-100/50 dark:shadow-none hover:-translate-y-2'
                    ]"
                >
                    <!-- Badge Destacado -->
                    <div v-if="plan.destacado" class="absolute -top-5 left-1/2 -translate-x-1/2 px-6 py-2 bg-[var(--color-primary)] text-white text-[10px] font-black uppercase tracking-widest rounded-full shadow-xl">
                        Recomendado
                    </div>

                    <!-- Cabecera del Plan -->
                    <div class="mb-10 text-center">
                        <div 
                            class="w-20 h-20 rounded-[2rem] flex items-center justify-center text-3xl mb-6 mx-auto transition-all duration-500 group-hover:scale-110 shadow-lg group-hover:shadow-xl"
                            :style="{ 
                                backgroundColor: plan.destacado ? 'var(--color-primary)' : 'var(--color-primary-soft)', 
                                color: plan.destacado ? 'white' : 'var(--color-primary)' 
                            }"
                        >
                            <font-awesome-icon :icon="getFaIcon(plan)" />
                        </div>
                        <h2 class="text-3xl font-black text-gray-900 dark:text-white dark:text-white mb-2 leading-tight transition-colors">{{ plan.nombre }}</h2>
                        <span class="text-[10px] font-black text-gray-400 dark:text-gray-500 dark:text-gray-400 uppercase tracking-[0.2em] transition-colors">{{ plan.tipo_label }}</span>
                    </div>

                    <!-- Precio -->
                    <div class="mb-10 text-center pt-8 border-t border-gray-50 dark:border-gray-700 transition-colors">
                        <template v-if="getPrecio(plan) > 0">
                            <div class="flex items-baseline justify-center gap-1">
                                <span class="text-gray-400 dark:text-gray-500 dark:text-gray-400 text-2xl font-bold">$</span>
                                <Transition mode="out-in">
                                    <span :key="periodoSeleccionado" class="text-6xl font-black text-gray-900 dark:text-white dark:text-white tracking-tighter transition-colors">
                                        {{ formatCurrency(getPrecio(plan)).replace('$', '').replace('.00', '') }}
                                    </span>
                                </Transition>
                            </div>
                            <p class="text-xs font-black text-gray-400 dark:text-gray-500 dark:text-gray-400 uppercase tracking-widest mt-2">Pesos por mes</p>
                        </template>
                        <template v-else>
                            <div class="py-2">
                                <span class="text-4xl font-black text-gray-900 dark:text-white dark:text-white tracking-tighter uppercase transition-colors">Plan Empresa</span>
                            </div>
                            <p class="text-xs font-black text-orange-600 dark:text-orange-400 uppercase tracking-widest mt-2">Soluciones a Medida</p>
                        </template>
                        
                        <!-- Ahorro Anual -->
                        <div v-if="periodoSeleccionado === 'anual' && getPrecio(plan) > 0" class="mt-4 inline-block px-4 py-2 bg-green-50 dark:bg-green-900/20 rounded-2xl border border-green-100 dark:border-green-700 animate-fade-in shadow-sm">
                            <p class="text-[10px] font-black text-green-600 dark:text-green-300 uppercase">Ahorras {{ formatCurrency(plan.ahorro_anual) }} al año</p>
                        </div>
                        <div v-else class="h-[42px]"></div>
                    </div>

                    <!-- Beneficios -->
                    <ul class="space-y-4 mb-12 flex-grow">
                        <li v-for="beneficio in plan.beneficios_array" :key="beneficio" class="flex items-start gap-4">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-bold" :style="{ backgroundColor: getColorPlan(plan) }">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                            </span>
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-300 dark:text-gray-300 leading-relaxed transition-colors">{{ beneficio }}</span>
                        </li>
                    </ul>

                    <!-- Botón de Acción -->
                    <Link 
                        v-if="getPrecio(plan) > 0"
                        :href="route('contratacion.show', plan.slug)"
                        class="w-full py-5 rounded-2xl font-black text-[11px] uppercase tracking-[0.2em] transition-all flex items-center justify-center gap-3 shadow-xl"
                        :class="plan.destacado ? 'bg-gray-900 text-white hover:bg-black shadow-gray-200' : 'bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600 shadow-gray-100 dark:shadow-none'"
                    >
                        Contratar Plan
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                    </Link>
                    <a 
                        v-else
                        :href="'https://wa.me/' + (empresaData.whatsapp || '521234567890')"
                        target="_blank"
                        class="w-full py-5 rounded-2xl font-black text-[11px] uppercase tracking-[0.2em] transition-all flex items-center justify-center gap-3 shadow-xl bg-[var(--color-primary)] text-white hover:opacity-90"
                    >
                        Contactar Ventas
                        <font-awesome-icon :icon="['fab', 'whatsapp']" class="text-lg" />
                    </a>
                </article>
            </div>

            <!-- Empty State -->
            <div v-else class="py-24 text-center">
                <div class="w-24 h-24 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-8 text-4xl">🏷️</div>
                <h3 class="text-2xl font-black text-gray-900 dark:text-white dark:text-white mb-2 transition-colors">Próximamente estaremos listos</h3>
                <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400 font-medium transition-colors">Estamos preparando nuestros nuevos planes para ti.</p>
            </div>
        </main>

        <!-- SECCIÓN: ¿POR QUÉ UNA PÓLIZA? (MEJORA SOLICITADA) -->
        <section class="w-full px-4 py-24 bg-white dark:bg-slate-900 transition-colors">
            <div class="max-w-6xl mx-auto">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-5xl font-black text-gray-900 dark:text-white mb-6 tracking-tight">¿Por qué contratar una Póliza?</h2>
                    <p class="text-lg text-gray-500 dark:text-gray-400 font-medium max-w-3xl mx-auto">
                        Invertir en una póliza no es un gasto, es blindar la productividad de su hogar o negocio.
                    </p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <!-- Ventaja 1 -->
                    <div class="p-8 rounded-[2rem] bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all group">
                        <div class="w-14 h-14 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition-transform">
                            <font-awesome-icon icon="clock-rotate-left" />
                        </div>
                        <h4 class="text-xl font-black text-gray-900 dark:text-white mb-3 tracking-tight">Atención Prioritaria</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed font-medium">
                            Olvídese de las filas. Su solicitud entra directamente a nuestra línea de **Soporte VIP**, garantizando tiempos de respuesta mínimos.
                        </p>
                    </div>

                    <!-- Ventaja 2 -->
                    <div class="p-8 rounded-[2rem] bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all group">
                        <div class="w-14 h-14 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition-transform">
                            <font-awesome-icon icon="hand-holding-dollar" />
                        </div>
                        <h4 class="text-xl font-black text-gray-900 dark:text-white mb-3 tracking-tight">Deducción de Impuestos</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed font-medium">
                            En México, las pólizas de mantenimiento son **100% deducibles de ISR** para empresas y personas físicas con actividad empresarial.
                        </p>
                    </div>

                    <!-- Ventaja 3 -->
                    <div class="p-8 rounded-[2rem] bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all group">
                        <div class="w-14 h-14 bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition-transform">
                            <font-awesome-icon icon="shield-check" />
                        </div>
                        <h4 class="text-xl font-black text-gray-900 dark:text-white mb-3 tracking-tight">Prevención vs Crisis</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed font-medium">
                            Es **70% más barato** mantener un equipo que repararlo de emergencia. Evite paros operativos costosos.
                        </p>
                    </div>

                    <!-- Ventaja 4 -->
                    <div class="p-8 rounded-[2rem] bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all group">
                        <div class="w-14 h-14 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition-transform">
                            <font-awesome-icon icon="tags" />
                        </div>
                        <h4 class="text-xl font-black text-gray-900 dark:text-white mb-3 tracking-tight">Precios Preferenciales</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed font-medium">
                            Como cliente de póliza, accede a **tarifas especiales** en refacciones y servicios no incluidos en su plan original.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECCIÓN: SIMULADOR DE PÓLIZA (MEJORA SOLICITADA) -->
        <section class="w-full px-4 py-24 bg-gradient-to-br from-gray-900 to-black text-white relative overflow-hidden">
            <!-- Partículas de fondo -->
            <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-[var(--color-primary)] opacity-10 blur-[150px] -z-0"></div>
            
            <div class="max-w-6xl mx-auto relative z-10">
                <div class="grid lg:grid-cols-2 gap-16 items-center">
                    <div>
                        <h2 class="text-4xl md:text-6xl font-black mb-8 tracking-tighter leading-tight">
                            Configure su Póliza <br/>
                            <span class="text-[var(--color-primary)]">a la Medida</span>
                        </h2>
                        <p class="text-xl text-gray-400 font-medium mb-12 leading-relaxed">
                            Deje de adivinar. Use nuestro simulador inteligente para calcular la inversión exacta según las necesidades de su infraestructura.
                        </p>
                        
                        <div class="space-y-6">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center text-xl">✔️</div>
                                <div>
                                    <p class="font-bold">Precios Transparentes</p>
                                    <p class="text-sm text-gray-400">Sin cargos ocultos ni sorpresas mensuales.</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center text-xl">📈</div>
                                <div>
                                    <p class="font-bold">Escalabilidad Real</p>
                                    <p class="text-sm text-gray-400">Pague solo por los equipos que realmente necesita proteger.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CALCULADORA INTERACTIVA -->
                    <div class="bg-white dark:bg-gray-900 rounded-[3rem] p-10 text-gray-900 dark:text-white shadow-2xl">
                        <div class="space-y-8">
                            <!-- Input de PCs -->
                            <div>
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4 block font-mono">Número de Computadoras</label>
                                <div class="flex items-center gap-6">
                                    <input type="range" v-model="simulador.pcs" min="1" max="50" class="flex-grow h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-[var(--color-primary)]" />
                                    <span class="text-4xl font-black w-20 text-center text-[var(--color-primary)]">{{ simulador.pcs }}</span>
                                </div>
                            </div>

                            <!-- Checkboxes de Servicios -->
                            <div>
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4 block font-mono">Servicios Adicionales</label>
                                <div class="grid grid-cols-2 gap-4">
                                    <button 
                                        v-for="(costo, serv) in simulador.addonsConfig" 
                                        :key="serv"
                                        @click="toggleAddon(serv)"
                                        :class="[
                                            'px-4 py-3 rounded-2xl border-2 text-[10px] font-black uppercase tracking-widest transition-all',
                                            simulador.addons[serv] ? 'bg-[var(--color-primary)] border-[var(--color-primary)] text-white shadow-lg' : 'border-gray-50 dark:border-gray-800 text-gray-400 hover:border-gray-200'
                                        ]"
                                    >
                                        {{ serv }}
                                    </button>
                                </div>
                            </div>

                            <!-- Resultado -->
                            <div class="pt-8 border-t border-gray-50 dark:border-gray-800">
                                <div class="flex justify-between items-end">
                                    <div>
                                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Inversión Estimada</p>
                                        <p class="text-5xl font-black text-[var(--color-primary)] tracking-tighter">{{ formatCurrency(costoSimulado) }}</p>
                                        <p class="text-[10px] font-bold text-gray-400 mt-2 uppercase">* Mensual + IVA</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs font-black text-emerald-500 uppercase tracking-widest">{{ horasSimuladas }} Horas Incluidas</p>
                                        <p class="text-[9px] text-gray-400 font-bold">Hora Extra: $500</p>
                                    </div>
                                </div>
                                <button class="w-full mt-8 py-5 bg-gray-900 dark:bg-white dark:text-gray-900 text-white rounded-2xl font-black text-[11px] uppercase tracking-[0.2em] shadow-xl hover:opacity-95 transition-all transform hover:-translate-y-1">
                                    Solicitar Cotización Formal
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
.animate-fade-in {
    animation: fadeIn 0.8s ease-out forwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.v-enter-active,
.v-leave-active {
    transition: all 0.3s ease;
}

.v-enter-from {
    opacity: 0;
    transform: translateY(10px);
}

.v-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>
