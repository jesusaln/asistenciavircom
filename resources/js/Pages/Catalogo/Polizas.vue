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
    if (plan.color) return plan.color;
    
    const colores = {
        mantenimiento: '#3B82F6', // blue
        soporte: '#10B981',       // green
        garantia: '#8B5CF6',      // purple
        premium: '#FF6B35',       // corporativo
        personalizado: '#EC4899', // pink
    };
    return colores[plan.tipo] || empresaData.value.color_principal || '#FF6B35';
};
</script>

<template>
    <Head :title="`Planes de Póliza - ${empresaData?.nombre_empresa || 'Servicios'}`" />

    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-orange-50/30" :style="cssVars">
        <!-- Widget Flotante de WhatsApp -->
        <WhatsAppWidget :whatsapp="empresaData?.whatsapp" :empresaNombre="empresaData?.nombre_empresa || empresaData?.nombre" />

        <!-- Navbar -->
        <PublicNavbar :empresa="empresaData" activeTab="polizas" />

        <!-- Hero Section con Estética Premium -->
        <section class="relative pt-24 pb-32 overflow-hidden">
            <!-- Background Elements -->
            <div class="absolute inset-0 bg-gradient-to-br from-white via-gray-50 to-white -z-20"></div>
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[1000px] h-[600px] bg-[var(--color-primary)] opacity-[0.03] rounded-full blur-[120px] -z-10"></div>
            <div class="absolute -bottom-24 left-0 w-[600px] h-[600px] bg-[var(--color-terciary)] opacity-[0.05] rounded-full blur-[100px] -z-10"></div>

            <div class="max-w-4xl mx-auto px-4 text-center">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-[var(--color-terciary-soft)] border border-[var(--color-terciary-soft)] mb-8 animate-fade-in shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-[var(--color-terciary)] animate-pulse"></span>
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--color-terciary)]">Pólizas Premium de Servicio</span>
                </div>
                
                <h1 class="text-5xl md:text-7xl font-black text-gray-900 mb-8 tracking-tighter leading-[1.1]">
                    Seguridad y Confort <br/>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-[var(--color-primary)] to-[var(--color-secondary)]">Sin Límites</span>
                </h1>
                
                <p class="text-xl text-gray-500 font-medium mb-12 max-w-2xl mx-auto leading-relaxed">
                    Protección total para sus equipos de climatización. Atención prioritaria, mantenimientos preventivos incluidos y la tranquilidad que su hogar necesita.
                </p>
                
                <!-- Toggle Mensual/Anual Premium -->
                <div class="inline-flex p-1.5 bg-white shadow-xl shadow-gray-200/50 rounded-2xl border border-gray-100">
                    <button 
                        @click="periodoSeleccionado = 'mensual'"
                        :class="[
                            'px-8 py-3 rounded-xl font-black text-xs uppercase tracking-widest transition-all',
                            periodoSeleccionado === 'mensual' ? 'bg-[var(--color-primary)] text-white shadow-lg' : 'text-gray-400 hover:text-gray-600'
                        ]"
                    >
                        Mensual
                    </button>
                    <button 
                        @click="periodoSeleccionado = 'anual'"
                        :class="[
                            'px-8 py-3 rounded-xl font-black text-xs uppercase tracking-widest transition-all gap-3 flex items-center',
                            periodoSeleccionado === 'anual' ? 'bg-[var(--color-primary)] text-white shadow-lg' : 'text-gray-400 hover:text-gray-600'
                        ]"
                    >
                        Anual
                        <span v-if="periodoSeleccionado !== 'anual'" class="px-2 py-0.5 bg-green-100 text-green-600 rounded-full text-[8px]">-20%</span>
                        <span v-else class="px-2 py-0.5 bg-white/20 text-white rounded-full text-[8px]">Ahorro</span>
                    </button>
                </div>
            </div>
        </section>

        <!-- Planes Grid -->
        <main class="max-w-7xl mx-auto px-4 pb-32">
            <div v-if="planes?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <article 
                    v-for="plan in planes" 
                    :key="plan.id"
                    :class="[
                        'relative bg-white p-10 rounded-[3rem] border shadow-2xl transition-all duration-500 flex flex-col group',
                        plan.destacado ? 'border-[var(--color-primary)] ring-4 ring-[var(--color-primary-soft)] lg:-translate-y-4 shadow-primary/20' : 'border-gray-50 shadow-gray-100/50 hover:-translate-y-2'
                    ]"
                >
                    <!-- Badge Destacado -->
                    <div v-if="plan.destacado" class="absolute -top-5 left-1/2 -translate-x-1/2 px-6 py-2 bg-[var(--color-primary)] text-white text-[10px] font-black uppercase tracking-widest rounded-full shadow-xl">
                        Recomendado
                    </div>

                    <!-- Cabecera del Plan -->
                    <div class="mb-10 text-center">
                        <div 
                            class="w-20 h-20 rounded-3xl flex items-center justify-center text-4xl mb-6 mx-auto transition-transform duration-500 group-hover:rotate-6 group-hover:scale-110"
                            :style="{ backgroundColor: getColorPlan(plan) + '15', color: getColorPlan(plan) }"
                        >
                            {{ plan.icono_display }}
                        </div>
                        <h2 class="text-3xl font-black text-gray-900 mb-2 leading-tight">{{ plan.nombre }}</h2>
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">{{ plan.tipo_label }}</span>
                    </div>

                    <!-- Precio -->
                    <div class="mb-10 text-center pt-8 border-t border-gray-50">
                        <div class="flex items-baseline justify-center gap-1">
                            <span class="text-gray-400 text-2xl font-bold">$</span>
                            <Transition mode="out-in">
                                <span :key="periodoSeleccionado" class="text-6xl font-black text-gray-900 tracking-tighter">
                                    {{ formatCurrency(getPrecio(plan)).replace('$', '').replace('.00', '') }}
                                </span>
                            </Transition>
                        </div>
                        <p class="text-xs font-black text-gray-400 uppercase tracking-widest mt-2">Pesos por mes</p>
                        
                        <!-- Ahorro Anual -->
                        <div v-if="periodoSeleccionado === 'anual'" class="mt-4 inline-block px-4 py-2 bg-green-50 rounded-2xl border border-green-100 animate-fade-in shadow-sm">
                            <p class="text-[10px] font-black text-green-600 uppercase">Ahorras {{ formatCurrency(plan.ahorro_anual) }} al año</p>
                        </div>
                        <div v-else class="h-[42px]"></div>
                    </div>

                    <!-- Beneficios -->
                    <ul class="space-y-4 mb-12 flex-grow">
                        <li v-for="beneficio in plan.beneficios_array" :key="beneficio" class="flex items-start gap-4">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-bold" :style="{ backgroundColor: getColorPlan(plan) }">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                            </span>
                            <span class="text-sm font-medium text-gray-600 leading-relaxed">{{ beneficio }}</span>
                        </li>
                    </ul>

                    <!-- Botón de Acción -->
                    <Link 
                        :href="route('contratacion.show', plan.slug)"
                        class="w-full py-5 rounded-2xl font-black text-[11px] uppercase tracking-[0.2em] transition-all flex items-center justify-center gap-3 shadow-xl"
                        :class="plan.destacado ? 'bg-gray-900 text-white hover:bg-black shadow-gray-200' : 'bg-gray-50 text-gray-900 hover:bg-gray-100 shadow-gray-100'"
                    >
                        Contratar Plan
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                    </Link>
                </article>
            </div>

            <!-- Empty State -->
            <div v-else class="py-24 text-center">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-8 text-4xl">🏷️</div>
                <h3 class="text-2xl font-black text-gray-900 mb-2">Próximamente estaremos listos</h3>
                <p class="text-gray-500 font-medium">Estamos preparando nuestros nuevos planes para ti.</p>
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
