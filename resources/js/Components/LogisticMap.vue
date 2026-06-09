<script setup>
import { ref, onMounted, watch, computed } from 'vue';

const props = defineProps({
    direccion: String,
    latitud: [Number, String],
    longitud: [Number, String],
    citasCercanas: {
        type: Array,
        default: () => []
    },
    height: {
        type: String,
        default: '400px'
    }
});

const emit = defineEmits(['update:latitud', 'update:longitud', 'location-found']);

const mapContainer = ref(null);
let map = null;
let mainMarker = null;
let nearbyMarkers = [];
const isLoading = ref(false);

// Coordenadas por defecto (Hermosillo)
const defaultLat = 29.0892;
const defaultLon = -110.9613;

const initMap = () => {
    if (typeof L === 'undefined') {
        setTimeout(initMap, 100);
        return;
    }

    const initialLat = props.latitud ? parseFloat(props.latitud) : defaultLat;
    const initialLon = props.longitud ? parseFloat(props.longitud) : defaultLon;

    map = L.map(mapContainer.value).setView([initialLat, initialLon], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Marcador principal (arrastrable)
    mainMarker = L.marker([initialLat, initialLon], { 
        draggable: true,
        icon: L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        })
    }).addTo(map);

    mainMarker.on('dragend', function(event) {
        const marker = event.target;
        const position = marker.getLatLng();
        updateCoordinates(position.lat, position.lng);
    });

    updateNearbyMarkers();
};

const updateCoordinates = (lat, lon) => {
    emit('update:latitud', lat);
    emit('update:longitud', lon);
    emit('location-found', { lat, lon });
};

const updateNearbyMarkers = () => {
    if (!map) return;

    // Limpiar marcadores previos
    nearbyMarkers.forEach(m => map.removeLayer(m));
    nearbyMarkers = [];

    const orangeIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-orange.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });

    props.citasCercanas.forEach(cita => {
        const lat = parseFloat(cita.latitud);
        const lon = parseFloat(cita.longitud);
        
        if (!isNaN(lat) && !isNaN(lon)) {
            const m = L.marker([lat, lon], { icon: orangeIcon })
                .addTo(map)
                .bindPopup(`
                    <div class="p-2 min-w-[150px]">
                        <p class="font-black text-gray-800 mb-1 border-b border-gray-100 pb-1">Cita ${cita.folio || '#' + cita.id}</p>
                        <div class="space-y-1">
                            <p class="text-[10px] flex items-center gap-1">
                                <span class="text-gray-400">🕐</span> 
                                <span class="font-bold text-blue-600">${cita.inicio} - ${cita.fin}</span>
                            </p>
                            <p class="text-[10px] flex items-center gap-1">
                                <span class="text-gray-400">🛠️</span> 
                                <span class="font-medium text-gray-600 capitalize">${cita.tipo_servicio.replace('_', ' ')}</span>
                            </p>
                            <p class="text-[9px] leading-tight text-gray-400 mt-2 bg-gray-50 p-1.5 rounded-lg border border-gray-100 italic">
                                ${cita.direccion || 'Sin dirección registrada'}
                            </p>
                        </div>
                    </div>
                `);
            nearbyMarkers.push(m);
        }
    });
};

const geocodeAddress = async (address) => {
    if (!address || address.length < 5) return;
    
    isLoading.value = true;
    try {
        const query = encodeURIComponent(address + ", Hermosillo, Sonora");
        const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${query}&limit=1`);
        const data = await response.json();
        
        if (data && data.length > 0) {
            const { lat, lon } = data[0];
            const newLat = parseFloat(lat);
            const newLon = parseFloat(lon);
            
            if (map && mainMarker) {
                const newPos = [newLat, newLon];
                mainMarker.setLatLng(newPos);
                map.panTo(newPos);
                updateCoordinates(newLat, newLon);
            }
        }
    } catch (error) {
        console.error('Error geocoding:', error);
    } finally {
        isLoading.value = false;
    }
};

// Observar cambios en la dirección para geocodificar
watch(() => props.direccion, (newVal) => {
    // Debounce simple para no saturar Nominatim
    clearTimeout(window._geocodeTimer);
    window._geocodeTimer = setTimeout(() => {
        geocodeAddress(newVal);
    }, 1500);
});

// Observar cambios en las citas cercanas
watch(() => props.citasCercanas, () => {
    updateNearbyMarkers();
}, { deep: true });

onMounted(() => {
    // Cargar Leaflet desde CDN si no existe
    if (!document.getElementById('leaflet-css')) {
        const link = document.createElement('link');
        link.id = 'leaflet-css';
        link.rel = 'stylesheet';
        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(link);
    }

    if (!document.getElementById('leaflet-js')) {
        const script = document.createElement('script');
        script.id = 'leaflet-js';
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.onload = initMap;
        document.head.appendChild(script);
    } else {
        initMap();
    }
});
</script>

<template>
    <div class="logistic-map-container relative rounded-[24px] overflow-hidden border border-gray-200 dark:border-gray-700 shadow-inner">
        <div ref="mapContainer" :style="{ height: height }" class="z-0"></div>
        
        <!-- Overlay de carga -->
        <div v-if="isLoading" class="absolute inset-0 bg-white/50 dark:bg-slate-900/50 backdrop-blur-[2px] flex items-center justify-center z-10 transition-all">
            <div class="flex flex-col items-center gap-3 bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700">
                <div class="w-8 h-8 border-4 border-blue-600/20 border-t-blue-600 rounded-full animate-spin"></div>
                <span class="text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">Geolocalizando...</span>
            </div>
        </div>

        <!-- Leyenda -->
        <div class="absolute bottom-4 left-4 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md p-3 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 z-[1000] space-y-2 pointer-events-none">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-blue-600 shadow-sm shadow-blue-300"></div>
                <span class="text-[10px] font-bold text-gray-600 dark:text-gray-300 uppercase tracking-tight">Cita Actual</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-orange-500 shadow-sm shadow-orange-300"></div>
                <span class="text-[10px] font-bold text-gray-600 dark:text-gray-300 uppercase tracking-tight">Otras Citas del Técnico</span>
            </div>
        </div>
    </div>
</template>

<style>
/* Estilos globales para Leaflet Popups para que se vean premium */
.leaflet-popup-content-wrapper {
    border-radius: 16px !important;
    padding: 4px !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
}
.leaflet-popup-tip {
    box-shadow: none !important;
}
.logistic-map-container .leaflet-container {
    font-family: inherit;
}
</style>
