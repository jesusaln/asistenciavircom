<script setup>
import { computed, ref, onMounted, watch } from 'vue';

const props = defineProps({
    empresa: Object,
    direccion: String,
    latitud: {
        type: [Number, String],
        default: null
    },
    longitud: {
        type: [Number, String],
        default: null
    },
    zoom: {
        type: Number,
        default: 16
    },
    height: {
        type: String,
        default: '300px'
    },
    rounded: {
        type: Boolean,
        default: true
    }
});

const isLoading = ref(true);
const hasError = ref(false);
const geocodedLat = ref(null);
const geocodedLon = ref(null);

// Coordenadas por defecto: Climas del Desierto - Opatas 115, Hermosillo, Sonora
const defaultLat = 29.1565728;
const defaultLon = -111.0078557;

// Obtener coordenadas finales (prioridad: props > geocodificadas > default)
const lat = computed(() => {
    if (props.latitud) return parseFloat(props.latitud);
    if (geocodedLat.value) return geocodedLat.value;
    return defaultLat;
});

const lon = computed(() => {
    if (props.longitud) return parseFloat(props.longitud);
    if (geocodedLon.value) return geocodedLon.value;
    return defaultLon;
});

// Geocodificar dirección usando Nominatim (OpenStreetMap)
const geocodeAddress = async () => {
    const address = props.direccion || props.empresa?.direccion || props.empresa?.direccion_completa;
    
    if (!address || props.latitud || props.longitud) {
        isLoading.value = false;
        return;
    }
    
    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 5000);
        
        const response = await fetch(
            `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`,
            {
                signal: controller.signal,
                headers: {
                    'User-Agent': 'ClimasDelDesierto/1.0'
                }
            }
        );
        clearTimeout(timeoutId);
        
        if (response.ok) {
            const data = await response.json();
            if (data && data.length > 0) {
                geocodedLat.value = parseFloat(data[0].lat);
                geocodedLon.value = parseFloat(data[0].lon);
            }
        }
    } catch (error) {
        // Silencioso - usa coordenadas por defecto
        console.log('Geocoding silently failed, using defaults');
    }
};

onMounted(() => {
    geocodeAddress();
});

// Construir URL de OpenStreetMap embed
const mapUrl = computed(() => {
    // Bounding box para el zoom level
    const delta = 0.005 / (props.zoom / 16);
    const bbox = `${lon.value - delta},${lat.value - delta},${lon.value + delta},${lat.value + delta}`;
    
    return `https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&layer=mapnik&marker=${lat.value},${lon.value}`;
});

// URL para abrir en OpenStreetMap
const openStreetMapLink = computed(() => {
    return `https://www.openstreetmap.org/?mlat=${lat.value}&mlon=${lon.value}#map=${props.zoom}/${lat.value}/${lon.value}`;
});

// URL para abrir en Google Maps
const googleMapsLink = computed(() => {
    const direccionCompleta = props.direccion || props.empresa?.direccion;
    if (direccionCompleta) {
        return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(direccionCompleta)}`;
    }
    return `https://www.google.com/maps?q=${lat.value},${lon.value}`;
});

const onLoad = () => {
    isLoading.value = false;
};

const onError = () => {
    isLoading.value = false;
    hasError.value = true;
};
</script>

<template>
    <div 
        class="relative overflow-hidden bg-gray-100"
        :class="{ 'rounded-2xl': rounded }"
        :style="{ height: height }"
    >
        <!-- Loading State -->
        <Transition
            enter-active-class="transition-opacity duration-300"
            enter-from-class="opacity-100"
            enter-to-class="opacity-0"
            leave-active-class="transition-opacity duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="isLoading" class="absolute inset-0 flex items-center justify-center bg-gray-100 z-10">
                <div class="flex flex-col items-center gap-3">
                    <div class="w-10 h-10 border-3 border-gray-300 border-t-[var(--color-primary)] rounded-full animate-spin"></div>
                    <span class="text-sm text-gray-500">Cargando mapa...</span>
                </div>
            </div>
        </Transition>
        
        <!-- Error State -->
        <div v-if="hasError" class="absolute inset-0 flex flex-col items-center justify-center bg-gray-50 z-10">
            <span class="text-4xl mb-3 opacity-50">📍</span>
            <p class="text-gray-500 text-sm mb-3">No se pudo cargar el mapa</p>
            <a 
                :href="googleMapsLink"
                target="_blank"
                rel="noopener noreferrer"
                class="text-[var(--color-primary)] text-sm font-semibold hover:underline flex items-center gap-1"
            >
                Ver en Google Maps
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
            </a>
        </div>
        
        <!-- Map iframe (OpenStreetMap) -->
        <iframe
            v-show="!hasError"
            :src="mapUrl"
            :key="mapUrl"
            class="w-full h-full border-0"
            allowfullscreen=""
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            @load="onLoad"
            @error="onError"
            title="Mapa de ubicación"
        ></iframe>
        
        <!-- Overlay Links -->
        <div 
            v-if="!hasError && !isLoading"
            class="absolute bottom-3 right-3 flex gap-2 z-20"
        >
            <!-- Open in OpenStreetMap -->
            <a 
                :href="openStreetMapLink"
                target="_blank"
                rel="noopener noreferrer"
                class="px-3 py-2 bg-white rounded-lg shadow-lg text-xs font-semibold text-gray-600 hover:bg-gray-50 transition-colors flex items-center gap-1.5"
                title="Ver en OpenStreetMap"
            >
                <svg class="w-4 h-4 text-green-600" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5S10.62 6.5 12 6.5s2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                </svg>
                OSM
            </a>
            
            <!-- Open in Google Maps -->
            <a 
                :href="googleMapsLink"
                target="_blank"
                rel="noopener noreferrer"
                class="px-3 py-2 bg-white rounded-lg shadow-lg text-xs font-semibold text-gray-600 hover:bg-gray-50 transition-colors flex items-center gap-1.5"
                title="Ver en Google Maps"
            >
                <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5S10.62 6.5 12 6.5s2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                </svg>
                Google
            </a>
        </div>
        
        <!-- Attribution (required by OSM) -->
        <div class="absolute bottom-0 left-0 px-2 py-0.5 bg-white/80 text-[9px] text-gray-500 z-20">
            © <a href="https://www.openstreetmap.org/copyright" target="_blank" class="hover:underline">OpenStreetMap</a>
        </div>
    </div>
</template>

<style scoped>
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

.border-3 {
    border-width: 3px;
}
</style>
