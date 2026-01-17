<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import PublicFooter from '@/Components/PublicFooter.vue';

const props = defineProps({
    empresa: Object,
    plan: Object,
});

const cssVars = computed(() => ({
    '--color-primary': props.empresa?.color_principal || '#3b82f6',
    '--color-secondary': props.empresa?.color_secundario || '#1e40af',
    '--color-primary-light': `${props.empresa?.color_principal || '#3b82f6'}20`,
}));

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value || 0);
};

const getColorPlan = computed(() => {
    if (props.plan.color) return props.plan.color;
    const colores = {
        mantenimiento: '#3B82F6',
        soporte: '#10B981',
        garantia: '#8B5CF6',
        premium: '#F59E0B',
        personalizado: '#EC4899',
    };
    return colores[props.plan.tipo] || props.empresa?.color_principal || '#3b82f6';
});
</script>

<template>
    <Head :title="`${plan.nombre} - ${empresa?.nombre}`" />

    <div class="min-h-screen bg-slate-50" :style="cssVars">
        <!-- Navbar -->
        <PublicNavbar :empresa="empresa" activeTab="polizas" />

        <main class="py-12">
            <div class="w-full px-4">
                <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100">
                    <div class="grid grid-cols-1 lg:grid-cols-2">
                        
                        <!-- Columna Izquierda: Visual -->
                        <div class="p-8 lg:p-12 text-white flex flex-col justify-center text-center lg:text-left" 
                             :style="{ background: `linear-gradient(135deg, ${getColorPlan} 0%, ${getColorPlan}cc 100%)` }">
                            <div class="inline-flex items-center justify-center lg:justify-start gap-3 mb-6">
                                <span class="text-6xl">{{ plan.icono_display }}</span>
                            </div>
                            <h1 class="text-4xl lg:text-5xl font-extrabold mb-4">{{ plan.nombre }}</h1>
                            <p class="text-xl text-white/90 mb-8 leading-relaxed">
                                {{ plan.descripcion_corta || 'Protección integral para tus equipos y sistemas con respuesta garantizada.' }}
                            </p>
                            
                            <div class="space-y-4">
                                <div class="flex items-center gap-4 bg-white/10 p-4 rounded-2xl backdrop-blur-sm">
                                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center text-2xl">⏱️</div>
                                    <div>
                                        <div class="font-bold text-lg">{{ plan.horas_incluidas || 'Horas ilimitadas' }}</div>
                                        <div class="text-white/70 text-sm">Servicio Técnico Incluido</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4 bg-white/10 p-4 rounded-2xl backdrop-blur-sm">
                                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center text-2xl">⚡</div>
                                    <div>
                                        <div class="font-bold text-lg">{{ plan.sla_horas_respuesta }} Horas</div>
                                        <div class="text-white/70 text-sm">Tiempo Máximo de Respuesta</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Columna Derecha: Detalles y Precio -->
                        <div class="p-8 lg:p-12">
                            <div class="mb-8">
                                <h2 class="text-2xl font-bold text-gray-900 mb-6 underline decoration-[var(--color-primary)] decoration-4 underline-offset-8">
                                    Incluido en este Plan
                                </h2>
                                <ul class="grid grid-cols-1 gap-4">
                                    <li v-for="(beneficio, index) in plan.beneficios" :key="index" class="flex items-start gap-3 text-gray-700">
                                        <svg class="w-6 h-6 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span class="font-medium">{{ beneficio }}</span>
                                    </li>
                                </ul>
                            </div>

                            <div v-if="plan.descripcion" class="mb-8 text-gray-600 leading-relaxed">
                                <p>{{ plan.descripcion }}</p>
                            </div>

                            <!-- Card de Precio -->
                            <div class="bg-slate-50 rounded-2xl p-6 border-2 border-dashed border-gray-200">
                                <div class="flex justify-between items-end mb-4">
                                    <div>
                                        <div class="text-gray-500 text-sm font-semibold uppercase tracking-wider">Inversión Mensual</div>
                                        <div class="text-4xl font-extrabold text-gray-900">{{ formatCurrency(plan.precio_mensual) }}</div>
                                    </div>
                                    <div v-if="plan.ahorro_anual > 0" class="text-right">
                                        <div class="text-green-600 font-bold text-sm bg-green-100 px-3 py-1 rounded-full">-{{ plan.porcentaje_descuento_anual }}% Anual</div>
                                    </div>
                                </div>

                                <div v-if="plan.precio_instalacion > 0" class="text-sm text-gray-500 mb-6 italic">
                                    * Costo único de activación: {{ formatCurrency(plan.precio_instalacion) }}
                                </div>

                                <Link 
                                    :href="route('contratacion.show', plan.slug)"
                                    class="block w-full py-4 bg-[var(--color-primary)] text-white text-center font-bold text-lg rounded-xl shadow-lg hover:opacity-90 transition-all transform hover:-translate-y-1"
                                >
                                    Contratar Plan Ahora
                                </Link>
                                
                                <p class="text-center text-xs text-gray-400 mt-4">
                                    Sujeto a términos y condiciones establecidos en el contrato de servicio.
                                </p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </main>

        <!-- Public Footer -->
        <PublicFooter :empresa="empresa" />
    </div>
</template>
