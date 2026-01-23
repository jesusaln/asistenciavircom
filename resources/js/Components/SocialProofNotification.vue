<script setup>
import { ref, onMounted, onUnmounted, computed, inject } from 'vue'

const props = defineProps({
    // Productos reales del catálogo
    productos: {
        type: Array,
        default: () => []
    },
    // Color corporativo (hex)
    colorCorporativo: {
        type: String,
        default: '#3b82f6' // blue-500 por defecto
    },
    // Tiempo que dura visible (ms)
    duration: {
        type: Number,
        default: 4000
    },
    // Delay inicial antes de mostrar primera notificación (ms)
    initialDelay: {
        type: Number,
        default: 10000 // 10 segundos
    },
    // Intervalo entre notificaciones después de la primera (ms)
    interval: {
        type: Number,
        default: 600000 // 10 minutos
    }
})

const isVisible = ref(false)
const currentNotification = ref(null)
const notificationCount = ref(0)
let showTimeout = null
let hideTimeout = null
let intervalId = null

// Filtrar productos para mostrar solo los que cuestan más de $1000
const productosFiltrados = computed(() => {
    return (props.productos || []).filter(p => {
        const precio = parseFloat(p.precio_venta || p.precio || 0);
        return precio >= 1000;
    });
});

// Nombres aleatorios para simular compradores
const nombres = [
    'María G.', 'Carlos R.', 'Ana L.', 'Jorge M.', 'Laura P.', 
    'Roberto S.', 'Patricia V.', 'Miguel A.', 'Sandra H.', 'Fernando C.',
    'Guadalupe M.', 'José L.', 'Carmen R.', 'Luis E.', 'Rosa M.'
]

// Ciudades de Sonora
const ciudades = [
    'Hermosillo', 'Ciudad Obregón', 'Nogales', 'San Luis Río Colorado',
    'Navojoa', 'Guaymas', 'Los Mochis', 'Agua Prieta', 'Caborca', 'Empalme'
]

// Tiempos aleatorios
const tiempos = [
    'hace 1 min', 'hace 2 min', 'hace 3 min', 'hace 5 min', 
    'hace 8 min', 'hace 10 min', 'hace 15 min', 'hace 20 min'
]

const getRandom = (arr) => arr[Math.floor(Math.random() * arr.length)]

const getRandomNotification = () => {
    // Si hay productos reales filtrados, usarlos
    if (productosFiltrados.value.length > 0) {
        const producto = getRandom(productosFiltrados.value)
        return {
            nombre: getRandom(nombres),
            ciudad: getRandom(ciudades),
            producto: producto.nombre || producto.name || 'Producto',
            tiempo: getRandom(tiempos)
        }
    }
    
    // Fallback si no hay productos que cumplan el criterio
    return {
        nombre: getRandom(nombres),
        ciudad: getRandom(ciudades),
        producto: 'Servicio de Mantenimiento Preventivo',
        tiempo: getRandom(tiempos)
    }
}

// Solo mostrar si hay productos que cumplan el criterio o para el fallback
const shouldShow = computed(() => true)

const showNotification = () => {
    if (!shouldShow.value) return
    
    currentNotification.value = getRandomNotification()
    isVisible.value = true
    notificationCount.value++
    
    hideTimeout = setTimeout(() => {
        isVisible.value = false
    }, props.duration)
}

onMounted(() => {
    if (!shouldShow.value) return
    
    // Mostrar primera notificación después del delay inicial (10 segundos)
    showTimeout = setTimeout(() => {
        showNotification()
        
        // Luego mostrar periódicamente cada 10 minutos
        intervalId = setInterval(showNotification, props.interval)
    }, props.initialDelay)
})

onUnmounted(() => {
    if (showTimeout) clearTimeout(showTimeout)
    if (hideTimeout) clearTimeout(hideTimeout)
    if (intervalId) clearInterval(intervalId)
})

// Color corporativo en formato CSS
const corporateColor = computed(() => props.colorCorporativo || '#3b82f6')
const corporateColorLight = computed(() => `${corporateColor.value}40`) // 25% opacity
</script>

<template>
    <Transition name="slide-fade">
        <div 
            v-if="isVisible && currentNotification" 
            class="fixed bottom-4 left-4 z-50 max-w-sm"
        >
            <!-- Notificación con efecto vidrio templado premium -->
            <div class="notification-glass relative rounded-2xl p-4 overflow-hidden">
                <!-- Efecto de brillo superior -->
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/60 to-transparent"></div>
                
                <!-- Borde brillante -->
                <div class="absolute inset-0 rounded-2xl border border-white/20 pointer-events-none"></div>
                
                <!-- Contenido -->
                <div class="relative flex items-center gap-4">
                    <!-- Avatar con color corporativo -->
                    <div class="relative">
                        <div 
                            class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg"
                            :style="{ background: `linear-gradient(135deg, ${corporateColor}, ${corporateColor}dd)` }"
                        >
                            {{ currentNotification.nombre.charAt(0) }}
                        </div>
                        <!-- Indicador de verificado con color corporativo -->
                        <div 
                            class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full flex items-center justify-center border-2 border-white/30"
                            :style="{ backgroundColor: corporateColor }"
                        >
                            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                    
                    <!-- Info con texto claro -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-white">
                            <span class="font-bold">{{ currentNotification.nombre }}</span>
                            <span class="text-white/70"> de </span>
                            <span class="font-medium text-white/90">{{ currentNotification.ciudad }}</span>
                        </p>
                        <p class="text-sm text-white/80 truncate">
                            compró <span class="font-semibold text-white">{{ currentNotification.producto }}</span>
                        </p>
                        <p class="text-xs text-white/50 mt-1 flex items-center gap-1.5">
                            <span 
                                class="w-2 h-2 rounded-full animate-pulse"
                                :style="{ backgroundColor: corporateColor }"
                            ></span>
                            {{ currentNotification.tiempo }}
                        </p>
                    </div>
                    
                    <!-- Botón cerrar -->
                    <button 
                        @click="isVisible = false"
                        class="absolute top-2 right-2 w-6 h-6 flex items-center justify-center text-white/40 hover:text-white/80 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <!-- Barra de progreso con color corporativo -->
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-white/10">
                    <div 
                        class="h-full progress-bar"
                        :style="{ 
                            animationDuration: `${duration}ms`,
                            background: `linear-gradient(90deg, ${corporateColor}, ${corporateColor}cc)`
                        }"
                    ></div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
/* Efecto vidrio templado premium */
.notification-glass {
    background: rgba(15, 23, 42, 0.75); /* slate-900 con transparencia */
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    box-shadow: 
        0 25px 50px -12px rgba(0, 0, 0, 0.5),
        0 0 0 1px rgba(255, 255, 255, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.slide-fade-enter-active {
    animation: slideIn 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}

.slide-fade-leave-active {
    animation: fadeOut 0.8s ease-out;
}

@keyframes slideIn {
    from {
        transform: translateX(-120%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes fadeOut {
    0% {
        opacity: 1;
        transform: translateX(0) scale(1);
    }
    100% {
        opacity: 0;
        transform: translateX(-20px) scale(0.95);
    }
}

.progress-bar {
    animation: shrink linear forwards;
}

@keyframes shrink {
    from {
        width: 100%;
    }
    to {
        width: 0%;
    }
}
</style>
