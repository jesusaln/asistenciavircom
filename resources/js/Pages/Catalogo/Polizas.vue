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

const periodoSeleccionado = ref('mensual');
const cantidadesEquipos = ref({}); // plan.id => cantidad (simple)
const cantidadesPorTipo = ref({}); // plan.id => { 'tipo_index': cantidad }

// Variables CSS con fallback al naranja corporativo de Climas del Desierto
const cssVars = computed(() => ({
    '--color-primary': empresaData.value.color_principal || '#FF6B35',
    '--color-primary-soft': (empresaData.value.color_principal || '#FF6B35') + '15',
    '--color-primary-dark': (empresaData.value.color_principal || '#FF6B35') + 'dd',
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

const getCantidadTotal = (plan) => {
    if (plan.precios_por_tipo?.length) {
        const tipos = cantidadesPorTipo.value[plan.id] || {};
        return Object.values(tipos).reduce((a, b) => a + (b || 0), 0);
    }
    return cantidadesEquipos.value[plan.id] || plan.min_equipos || 1;
};

const getCantidadTipo = (plan, idx) => {
    const tipos = cantidadesPorTipo.value[plan.id] || {};
    return tipos[idx] || 0;
};

const setCantidadTipo = (plan, idx, val) => {
    if (!cantidadesPorTipo.value[plan.id]) {
        cantidadesPorTipo.value[plan.id] = {};
    }
    cantidadesPorTipo.value[plan.id][idx] = Math.max(0, val);
};

const getSubtotalTipo = (plan, idx) => {
    const tipo = plan.precios_por_tipo[idx];
    if (!tipo) return 0;
    return (tipo.precio || 0) * getCantidadTipo(plan, idx);
};

const getTotalAnual = (plan) => {
    if (plan.precios_por_tipo?.length) {
        return plan.precios_por_tipo.reduce((total, tipo, idx) => {
            return total + getSubtotalTipo(plan, idx);
        }, 0);
    }
    const cant = getCantidadTotal(plan);
    if (plan.precio_por_equipo > 0) {
        return plan.precio_por_equipo * cant;
    }
    return plan.precio_mensual * 12;
};

const getPrecio = (plan) => {
    const anual = getTotalAnual(plan);
    if (periodoSeleccionado.value === 'anual') {
        return anual * (1 - 0.15);
    }
    return anual / 12;
};

const getColorPlan = (plan) => {
    // Si el plan tiene un color específico en BD, lo usamos
    if (plan.color) return plan.color;
    
    // Si no, usamos el color corporativo por defecto
    return empresaData.value.color_principal || '#FF6B35';
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
        const mensual = parseFloat(plan.precio_mensual) || 0;
        const anualSinDescuento = mensual * 12;
        const descuento = 0.15;
        const precioAnual = anualSinDescuento > 0 ? anualSinDescuento * (1 - descuento) : 0;
        const ahorro = anualSinDescuento - precioAnual;
        
        return {
            ...plan,
            precio_mensual: mensual,
            precio_anual: precioAnual,
            ahorro_anual: ahorro,
        };
    });
});
</script>

<template>
    <Head :title="`Planes de Póliza - ${empresaData?.nombre_empresa || 'Servicios'}`">
        <meta name="description" :content="`Contrata planes de mantenimiento y soporte técnico para tu empresa u hogar en ${empresaData?.ciudad || 'Hermosillo'}. Pólizas mensuales y anuales con descuentos exclusivos.`" />
    </Head>

    <div class="min-h-screen bg-[var(--ui-surface)] transition-colors duration-200" :style="cssVars">
        <!-- Widget Flotante de WhatsApp -->
        <WhatsAppWidget :whatsapp="empresaData?.whatsapp" :empresaNombre="empresaData?.nombre_empresa || empresaData?.nombre" />

        <!-- Navbar -->
        <PublicNavbar :empresa="empresaData" activeTab="polizas" />

        <!-- Hero Section con Estética Premium -->
        <section class="relative pt-24 pb-32 overflow-hidden">
            <!-- Background Elements -->
            <div class="absolute inset-0 bg-gradient-to-br from-white via-slate-50 to-white dark:from-slate-900 dark:via-slate-950 dark:to-slate-900 -z-20 transition-colors"></div>
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[1000px] h-[600px] bg-[var(--color-primary)] opacity-[0.03] rounded-full blur-[120px] -z-10"></div>
            <div class="absolute -bottom-24 left-0 w-[600px] h-[600px] bg-[var(--color-terciary)] opacity-[0.05] rounded-full blur-[100px] -z-10"></div>

            <div class="w-full px-4 text-center">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-[var(--color-terciary-soft)] border border-[var(--color-terciary-soft)] mb-8 animate-fade-in shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-[var(--color-terciary)] animate-pulse"></span>
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--color-terciary)]">Pólizas Premium de Servicio</span>
                </div>
                
                <h1 class="text-5xl md:text-7xl font-black text-slate-900 dark:text-white mb-8 tracking-tighter leading-[1.1] transition-colors">
                    Seguridad y Confort <br/>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-[var(--color-primary)] to-[var(--color-secondary)]">Sin Límites</span>
                </h1>
                
                <p class="text-xl text-slate-500 dark:text-slate-400 font-medium mb-12 w-full leading-relaxed transition-colors">
                    Protección total para sus equipos de climatización. Atención prioritaria, mantenimientos preventivos incluidos y la tranquilidad que su hogar necesita.
                </p>
                
                <!-- Toggle Mensual/Anual Premium -->
                <div class="inline-flex p-1.5 bg-white dark:bg-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-none rounded-2xl border border-slate-100 dark:border-slate-700 transition-colors">
                    <button 
                        @click="periodoSeleccionado = 'mensual'"
                        :class="[
                            'px-8 py-3 rounded-xl font-black text-xs uppercase tracking-wide transition-all',
                            periodoSeleccionado === 'mensual' ? 'bg-[var(--color-primary)] text-white shadow-xl' : 'text-slate-400 dark:text-slate-500 hover:text-brand-600 dark:hover:text-slate-300'
                        ]"
                    >
                        Mensual
                    </button>
                    <button 
                        @click="periodoSeleccionado = 'anual'"
                        :class="[
                            'px-8 py-3 rounded-xl font-black text-xs uppercase tracking-wide transition-all gap-3 flex items-center',
                            periodoSeleccionado === 'anual' ? 'bg-[var(--color-primary)] text-white shadow-xl' : 'text-slate-400 dark:text-slate-500 hover:text-brand-600 dark:hover:text-slate-300'
                        ]"
                    >
                        Anual
                        <span v-if="periodoSeleccionado !== 'anual'" class="px-2 py-0.5 bg-emerald-100 dark:bg-slate-800/20 text-emerald-600 dark:text-emerald-300 rounded-full text-[8px]">-15%</span>
                        <span v-else class="px-2 py-0.5 bg-white/20 text-white rounded-full text-[8px]">Ahorro</span>
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
                        'relative bg-white dark:bg-slate-800 p-10 rounded-[3rem] border shadow-2xl transition-all duration-500 flex flex-col group',
                        plan.destacado ? 'border-[var(--color-primary)] ring-4 ring-[var(--color-primary-soft)] lg:-translate-y-4 shadow-xl' : 'border-slate-50 dark:border-slate-700 shadow-slate-100/50 dark:shadow-none hover:shadow-xl hover:shadow-xl'
                    ]"
                >
                    <!-- Badge Destacado -->
                    <div v-if="plan.destacado" class="absolute -top-5 left-1/2 -translate-x-1/2 px-6 py-2 bg-[var(--color-primary)] text-white text-[10px] font-black uppercase tracking-wide rounded-full shadow-xl">
                        Recomendado
                    </div>

                    <!-- Cabecera del Plan -->
                    <div class="mb-10 text-center">
                        <div 
                            class="w-16 h-16 rounded-[2rem] flex items-center justify-center text-3xl mb-6 mx-auto transition-all duration-500 group-hover:scale-105 shadow-xl group-hover:shadow-xl"
                            :style="{ 
                                backgroundColor: plan.destacado ? 'var(--color-primary)' : 'var(--color-primary-soft)', 
                                color: plan.destacado ? 'white' : 'var(--color-primary)' 
                            }"
                        >
                            <font-awesome-icon :icon="getFaIcon(plan)" />
                        </div>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white mb-2 leading-tight transition-colors">{{ plan.nombre }}</h2>
                        <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] transition-colors">{{ plan.tipo_label }}</span>
                    </div>

                    <!-- Selector de equipos por tipo (precios_por_tipo) -->
                    <div v-if="plan.precios_por_tipo?.length" class="mb-6 px-4">
                        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-2xl p-4 border border-slate-100 dark:border-slate-700">
                            <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-3 block text-center">Selecciona tus equipos</label>
                            <div class="space-y-3">
                                <div v-for="(tipo, idx) in plan.precios_por_tipo" :key="idx"
                                    class="flex items-center gap-3 p-2.5 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-600">
                                    <div class="w-9 h-9 rounded-lg bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-300 flex items-center justify-center text-sm shrink-0">
                                        <font-awesome-icon :icon="tipo.icono || 'microchip'" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">{{ tipo.nombre }}</div>
                                        <div class="text-[9px] text-slate-400 dark:text-slate-500 font-mono">{{ tipo.descripcion || '' }}</div>
                                        <div class="text-[10px] text-indigo-600 dark:text-indigo-400 font-black mt-0.5">{{ formatCurrency(tipo.precio) }}/mes</div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button @click="setCantidadTipo(plan, idx, getCantidadTipo(plan, idx) - 1)"
                                            class="w-8 h-8 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 text-slate-500 font-bold flex items-center justify-center hover:bg-slate-100 transition-all text-sm"
                                            :disabled="getCantidadTipo(plan, idx) <= 0">−</button>
                                        <span class="w-10 text-center text-lg font-black text-slate-900 dark:text-white tabular-nums">{{ getCantidadTipo(plan, idx) }}</span>
                                        <button @click="setCantidadTipo(plan, idx, getCantidadTipo(plan, idx) + 1)"
                                            class="w-8 h-8 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 text-slate-500 font-bold flex items-center justify-center hover:bg-slate-100 transition-all text-sm">+</button>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 pt-2 border-t border-slate-200 dark:border-slate-600 flex justify-between text-xs">
                                <span class="text-slate-500 dark:text-slate-400 font-medium">Total equipos: <strong class="text-slate-900 dark:text-white">{{ getCantidadTotal(plan) }}</strong></span>
                                <span class="text-indigo-600 dark:text-indigo-400 font-black">Subtotal: {{ formatCurrency(getPrecio(plan)) }}/mes</span>
                            </div>
                        </div>
                    </div>

                    <!-- Selector simple de cantidad (para precio por equipo sin tipos) -->
                    <div v-else-if="plan.precio_por_equipo > 0" class="mb-6 px-4">
                        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-2xl p-4 border border-slate-100 dark:border-slate-700">
                            <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2 block text-center">¿Cuántos equipos?</label>
                            <div class="flex items-center justify-center gap-4">
                                <button @click="cantidadesEquipos[plan.id] = Math.max(1, (cantidadesEquipos[plan.id] || plan.min_equipos || 1) - 1)" 
                                    class="w-10 h-10 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-300 font-bold text-lg hover:bg-slate-100 transition-all flex items-center justify-center shadow-sm"
                                    :disabled="(cantidadesEquipos[plan.id] || plan.min_equipos || 1) <= 1">−</button>
                                <span class="text-2xl font-black text-slate-900 dark:text-white w-16 text-center tabular-nums">{{ cantidadesEquipos[plan.id] || plan.min_equipos || 1 }}</span>
                                <button @click="cantidadesEquipos[plan.id] = Math.min(plan.max_equipos || 100, (cantidadesEquipos[plan.id] || plan.min_equipos || 1) + 1)" 
                                    class="w-10 h-10 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-300 font-bold text-lg hover:bg-slate-100 transition-all flex items-center justify-center shadow-sm"
                                    :disabled="plan.max_equipos && (cantidadesEquipos[plan.id] || plan.min_equipos || 1) >= plan.max_equipos">+</button>
                            </div>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 text-center mt-2">{{ formatCurrency(plan.precio_por_equipo) }} por equipo/mes</p>
                        </div>
                    </div>

                    <!-- Precio -->
                    <div class="mb-10 text-center pt-6 border-t border-slate-50 dark:border-slate-700 transition-colors">
                        <template v-if="getPrecio(plan) > 0">
                            <div class="flex items-baseline justify-center gap-1">
                                <span class="text-slate-400 dark:text-slate-500 text-2xl font-bold">$</span>
                                <Transition mode="out-in">
                                    <span :key="periodoSeleccionado + '-' + getCantidad(plan)" class="text-6xl font-black text-slate-900 dark:text-white tracking-tighter transition-colors">
                                        {{ formatCurrency(getPrecio(plan)).replace('$', '').replace('.00', '') }}
                                    </span>
                                </Transition>
                            </div>
                            <p class="text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mt-2">{{ periodoSeleccionado === 'anual' ? 'Pesos por año' : 'Pesos por mes' }}</p>
                            <p v-if="plan.precios_por_tipo?.length" class="text-[9px] text-slate-400 dark:text-slate-500 mt-1">{{ getCantidadTotal(plan) }} equipo(s) seleccionados</p>
                            <p v-else-if="plan.precio_por_equipo > 0" class="text-[9px] text-slate-400 dark:text-slate-500 mt-1">{{ formatCurrency(plan.precio_por_equipo) }}/año × {{ getCantidadTotal(plan) }} equipo(s)</p>
                            <p v-else-if="plan.precio_mensual > 0" class="text-[9px] text-slate-400 dark:text-slate-500 mt-1">{{ formatCurrency(plan.precio_mensual) }}/mes precio fijo</p>
                        </template>
                        <template v-else>
                            <div class="py-2">
                                <span class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter uppercase transition-colors">Plan Empresa</span>
                            </div>
                            <p class="text-xs font-black text-brand-600 dark:text-orange-400 uppercase tracking-wide mt-2">Soluciones a Medida</p>
                        </template>
                        
                        <!-- Ahorro Anual -->
                        <div v-if="periodoSeleccionado === 'anual' && getTotalAnual(plan) > 0" class="mt-4 inline-block px-4 py-2 bg-emerald-50 dark:bg-emerald-900/20 dark:bg-slate-800/20 rounded-2xl border border-emerald-100 dark:border-emerald-700 animate-fade-in shadow-sm">
                            <p class="text-[10px] font-black text-emerald-600 dark:text-emerald-300 uppercase">Ahorras {{ formatCurrency(getTotalAnual(plan) * 0.15) }} al año</p>
                        </div>
                        <div v-else class="h-[42px]"></div>
                    </div>

                    <!-- Beneficios -->
                    <ul class="space-y-6 mb-12 flex-grow">
                        <li v-for="beneficio in plan.beneficios_array" :key="beneficio" class="flex items-start gap-4">
                            <span class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-white text-xs font-bold" :style="{ backgroundColor: getColorPlan(plan) }">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                            </span>
                            <span class="text-sm font-medium text-slate-500 dark:text-slate-200 leading-relaxed transition-colors">{{ beneficio }}</span>
                        </li>
                    </ul>

                    <!-- Botón de Acción -->
                    <Link 
                        v-if="getPrecio(plan) > 0"
                        :href="route('contratacion.show', plan.slug)"
                        class="w-full py-5 rounded-2xl font-black text-[11px] uppercase tracking-[0.2em] transition-all flex items-center justify-center gap-3 shadow-xl"
                        :class="plan.destacado ? 'bg-slate-900 text-white hover:bg-black shadow-slate-200' : 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white hover:bg-slate-100 dark:hover:bg-slate-600 shadow-slate-100 dark:shadow-none'"
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
                <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-8 text-4xl">🏷️</div>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-2 transition-colors">Próximamente estaremos listos</h3>
                <p class="text-slate-500 dark:text-slate-400 font-medium transition-colors">Estamos preparando nuestros nuevos planes para ti.</p>
            </div>
        </main>

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
