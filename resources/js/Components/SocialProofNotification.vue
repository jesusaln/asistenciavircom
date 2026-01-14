<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'

const props = defineProps({
    // Productos reales del catálogo
    productos: {
        type: Array,
        default: () => []
    },
    // Tiempo que dura visible (ms)
    duration: {
        type: Number,
        default: 3000
    },
    // Delay inicial antes de mostrar (ms)
    initialDelay: {
        type: Number,
        default: 2000
    },
    // Intervalo entre notificaciones (ms)
    interval: {
        type: Number,
        default: 15000
    }
})

const isVisible = ref(false)
const currentNotification = ref(null)
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
    
    hideTimeout = setTimeout(() => {
        isVisible.value = false
    }, props.duration)
}

onMounted(() => {
    if (!shouldShow.value) return
    
    // Mostrar primera notificación después del delay inicial
    showTimeout = setTimeout(() => {
        showNotification()
        
        // Luego mostrar periódicamente
        intervalId = setInterval(showNotification, props.interval)
    }, props.initialDelay)
})

onUnmounted(() => {
    if (showTimeout) clearTimeout(showTimeout)
    if (hideTimeout) clearTimeout(hideTimeout)
    if (intervalId) clearInterval(intervalId)
})
</script>

<template>
    <Transition name="slide-fade">
        <div 
            v-if="isVisible && currentNotification" 
            class="fixed bottom-4 left-4 z-50 max-w-sm"
        >
            <!-- Notificación con efecto cristal -->
            <div class="relative bg-white/70 backdrop-blur-xl rounded-2xl shadow-2xl border border-white/50 p-4 overflow-hidden">
                <!-- Efecto de brillo superior -->
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white to-transparent"></div>
                
                <!-- Contenido -->
                <div class="flex items-center gap-4">
                    <!-- Avatar -->
                    <div class="relative">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg">
                            {{ currentNotification.nombre.charAt(0) }}
                        </div>
                        <!-- Indicador de verificado -->
                        <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-500 rounded-full flex items-center justify-center border-2 border-white">
                            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                    
                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-800">
                            <span class="font-bold">{{ currentNotification.nombre }}</span>
                            <span class="text-gray-500"> de </span>
                            <span class="font-medium">{{ currentNotification.ciudad }}</span>
                        </p>
                        <p class="text-sm text-gray-600 truncate">
                            compró <span class="font-semibold text-gray-800">{{ currentNotification.producto }}</span>
                        </p>
                        <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                            <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                            {{ currentNotification.tiempo }}
                        </p>
                    </div>
                </div>
                
                <!-- Barra de progreso -->
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-gray-200/50">
                    <div 
                        class="h-full bg-gradient-to-r from-green-400 to-emerald-500 progress-bar"
                        :style="{ animationDuration: `${duration}ms` }"
                    ></div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.slide-fade-enter-active {
    animation: slideIn 0.5s ease-out;
}

.slide-fade-leave-active {
    animation: fadeOut 1s ease-out;
}

@keyframes slideIn {
    from {
        transform: translateX(-100%);
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
        transform: translateX(0);
    }
    100% {
        opacity: 0;
        transform: translateY(10px);
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
