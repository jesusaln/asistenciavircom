<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    empresa: Object,
    // Datos de oferta desde el backend
    oferta: {
        type: Object,
        default: null
    }
});

const cssVars = computed(() => ({
    '--color-primary': props.empresa?.color_principal || '#FF6B35',
    '--color-primary-soft': (props.empresa?.color_principal || '#FF6B35') + '20',
}));

// Countdown logic
const timeLeft = ref({
    hours: 0,
    minutes: 0,
    seconds: 0
});

const isExpired = ref(false);
let intervalId = null;

// Datos de la oferta (desde backend o fallback)
const titulo = computed(() => props.oferta?.titulo || '💼 VENTA O RENTA POS');
const subtitulo = computed(() => props.oferta?.subtitulo || 'Kit de Punto de Venta - Equipamiento Completo');
const descuento = computed(() => props.oferta?.descuento || 20);
const precioOriginal = computed(() => props.oferta?.precio_original || 15500);

// Calcular precio con descuento
const precioDescuento = computed(() => {
    if (props.oferta?.precio_oferta) {
        return props.oferta.precio_oferta;
    }
    return 12500; // Precio de compra solicitado
});

const ahorro = computed(() => {
    return precioOriginal.value - precioDescuento.value;
});

// Características
const caracteristicas = computed(() => {
    if (props.oferta?.caracteristicas && props.oferta.caracteristicas.length > 0) {
        return props.oferta.caracteristicas;
    }
    return [
        'COMPRA: $12,500 (¡Incluye Báscula!)',
        'RENTA BÁSICA: $1,000 + IVA (Sin báscula)',
        'RENTA COMPLETA: $1,250 + IVA (Con báscula)',
        'CPU, Monitor, Teclado, Cajón e Impresora'
    ];
});

const formatPrice = (price) => {
    return new Intl.NumberFormat('es-MX', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(price);
};

// Calcular tiempo restante
const calculateTimeLeft = () => {
    let endTime;
    
    // Si hay fecha_fin del backend, usarla
    if (props.oferta?.fecha_fin) {
        endTime = new Date(props.oferta.fecha_fin);
    } else {
        // Fallback: usar localStorage o crear nueva
        let stored = localStorage.getItem('oferta_end_time');
        
        if (!stored || new Date(stored) <= new Date()) {
            // Crear nueva fecha de expiración (24 horas)
            const newEndTime = new Date(Date.now() + (24 * 60 * 60 * 1000));
            localStorage.setItem('oferta_end_time', newEndTime.toISOString());
            stored = newEndTime.toISOString();
        }
        
        endTime = new Date(stored);
    }
    
    const now = new Date();
    const diff = endTime - now;
    
    if (diff <= 0) {
        isExpired.value = true;
        timeLeft.value = { hours: 0, minutes: 0, seconds: 0 };
        localStorage.removeItem('oferta_end_time');
        return;
    }
    
    isExpired.value = false;
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);
    
    timeLeft.value = { hours, minutes, seconds };
};

onMounted(() => {
    calculateTimeLeft();
    intervalId = setInterval(calculateTimeLeft, 1000);
});

onUnmounted(() => {
    if (intervalId) clearInterval(intervalId);
});

const padZero = (num) => String(num).padStart(2, '0');
</script>

<template>
    <section v-if="oferta || true" class="py-6 relative overflow-hidden" :style="cssVars">
        <!-- Background con gradiente premium -->
        <div class="absolute inset-0 bg-gradient-to-r from-gray-900 via-gray-800 to-gray-900"></div>
        
        <!-- Patrón decorativo -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-1/4 w-96 h-96 bg-[var(--color-primary)] rounded-full blur-[150px]"></div>
            <div class="absolute bottom-0 right-1/4 w-64 h-64 bg-orange-500 rounded-full blur-[100px]"></div>
        </div>
        
        <!-- Líneas animadas -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-[var(--color-primary)] to-transparent animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-[var(--color-primary)] to-transparent animate-pulse"></div>
        </div>
        
        <div class="max-w-7xl mx-auto px-4 relative z-10">
            <div class="flex flex-col lg:flex-row items-center justify-between gap-8">
                
                <!-- Lado izquierdo: Info de la oferta -->
                <div class="flex-1 text-center lg:text-left">
                    <!-- Badge animado -->
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-[var(--color-primary)] rounded-full mb-4 animate-bounce-gentle">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                        </span>
                        <span class="text-white text-xs font-black uppercase tracking-wider">{{ titulo }}</span>
                    </div>
                    
                    <!-- Título de la oferta -->
                    <h2 class="text-2xl lg:text-3xl font-black text-white mb-3">
                        {{ subtitulo }}
                    </h2>
                    
                    <!-- Características -->
                    <div class="flex flex-wrap items-center justify-center lg:justify-start gap-4 mb-4">
                        <span 
                            v-for="(caracteristica, index) in caracteristicas" 
                            :key="index"
                            class="inline-flex items-center gap-1.5 text-sm text-gray-300"
                        >
                            <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ caracteristica }}
                        </span>
                    </div>
                    
                    <!-- Precios -->
                    <div class="flex items-center justify-center lg:justify-start gap-4 mb-2">
                        <span class="text-gray-500 line-through text-lg">${{ formatPrice(1249) }}</span>
                        <div class="flex flex-col items-start leading-none">
                            <span class="text-3xl lg:text-4xl font-black text-white">${{ formatPrice(999) }} <span class="text-lg font-bold text-gray-400">/ mes</span></span>
                        </div>
                        <span class="px-3 py-1 bg-[var(--color-primary)] text-white text-xs font-bold rounded-full animate-pulse shadow-lg shadow-[var(--color-primary)]/40">
                            ¡Con Báscula!
                        </span>
                    </div>
                    <p class="text-green-400 text-sm font-semibold">
                        Ahorras $250 mensuales en tu renta
                    </p>
                </div>
                
                <!-- Centro: Countdown -->
                <div class="flex flex-col items-center">
                    <p class="text-[10px] font-black text-[var(--color-primary)] uppercase tracking-[0.3em] mb-3">
                        ⏰ La oferta termina en:
                    </p>
                    
                    <div class="flex items-center gap-2 lg:gap-3">
                        <!-- Horas -->
                        <div class="flex flex-col items-center">
                            <div class="w-16 h-16 lg:w-20 lg:h-20 bg-white/10 backdrop-blur-sm rounded-xl flex items-center justify-center border border-white/20">
                                <span class="text-2xl lg:text-3xl font-black text-white tabular-nums">
                                    {{ padZero(timeLeft.hours) }}
                                </span>
                            </div>
                            <span class="text-[9px] font-bold text-gray-400 uppercase mt-1">Horas</span>
                        </div>
                        
                        <span class="text-2xl font-black text-[var(--color-primary)] animate-pulse">:</span>
                        
                        <!-- Minutos -->
                        <div class="flex flex-col items-center">
                            <div class="w-16 h-16 lg:w-20 lg:h-20 bg-white/10 backdrop-blur-sm rounded-xl flex items-center justify-center border border-white/20">
                                <span class="text-2xl lg:text-3xl font-black text-white tabular-nums">
                                    {{ padZero(timeLeft.minutes) }}
                                </span>
                            </div>
                            <span class="text-[9px] font-bold text-gray-400 uppercase mt-1">Minutos</span>
                        </div>
                        
                        <span class="text-2xl font-black text-[var(--color-primary)] animate-pulse">:</span>
                        
                        <!-- Segundos -->
                        <div class="flex flex-col items-center">
                            <div class="w-16 h-16 lg:w-20 lg:h-20 bg-[var(--color-primary)]/20 backdrop-blur-sm rounded-xl flex items-center justify-center border border-[var(--color-primary)]/50">
                                <span class="text-2xl lg:text-3xl font-black text-[var(--color-primary)] tabular-nums">
                                    {{ padZero(timeLeft.seconds) }}
                                </span>
                            </div>
                            <span class="text-[9px] font-bold text-gray-400 uppercase mt-1">Segundos</span>
                        </div>
                    </div>
                </div>
                
                <!-- Lado derecho: CTA -->
                <div class="flex flex-col items-center lg:items-end gap-3">
                    <a 
                        :href="`https://wa.me/${empresa?.whatsapp?.replace(/\\D/g, '')}?text=${encodeURIComponent('🔥 Hola! Me interesa la oferta: ' + subtitulo + ' a ' + formatPrice(precioDescuento) + ' (Ahorro de ' + formatPrice(ahorro) + '). ¿Está disponible?')}`"
                        target="_blank"
                        class="group relative px-8 py-4 bg-green-500 text-white font-bold rounded-xl hover:bg-green-600 hover:shadow-lg hover:shadow-green-500/30 transition-all duration-300 overflow-hidden"
                    >
                        <!-- Efecto shine -->
                        <div class="absolute inset-0 -translate-x-full group-hover:translate-x-full transition-transform duration-700 bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                        <span class="relative flex items-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            <span>¡Quiero esta Oferta!</span>
                        </span>
                    </a>
                    
                    <p class="text-[10px] text-gray-500">
                        *Oferta válida hasta agotar existencias
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
@keyframes bounce-gentle {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-5px);
    }
}

.animate-bounce-gentle {
    animation: bounce-gentle 2s ease-in-out infinite;
}

.tabular-nums {
    font-variant-numeric: tabular-nums;
}
</style>
