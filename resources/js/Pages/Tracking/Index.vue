<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
    tecnicos: { type: Array, default: () => [] },
})

const page = usePage()
const tecnicos = ref([...props.tecnicos])
const mapContainer = ref(null)
const ultimaActualizacion = ref(null)
const selectedTecnico = ref(null)
const isLoading = ref(false)
const viewMode = ref('mapa')

let map = null
let markers = []
let fitTimeout = null
let pollInterval = null

function detectarEstado(tecnico) {
    if (!tecnico.cita_actual) return 'disponible'
    if (tecnico.cita_actual.estado === 'en_proceso') return 'en_proceso'
    if (tecnico.cita_actual.estado === 'programado') return 'programado'
    return 'disponible'
}

function gpsStatus(tecnico) {
    if (!tecnico.ultima_fecha_gps) return 'offline'
    const diff = (Date.now() - new Date(tecnico.ultima_fecha_gps).getTime()) / 1000
    if (diff < 300) return 'reciente'
    if (diff < 1800) return 'media'
    if (diff < 7200) return 'antigua'
    return 'offline'
}

function gpsBadge(tecnico) {
    const gps = gpsStatus(tecnico)
    const map = {
        reciente: { color: 'bg-emerald-100 text-emerald-700', text: 'GPS reciente' },
        media: { color: 'bg-amber-100 text-amber-700', text: 'GPS hace >5min' },
        antigua: { color: 'bg-orange-100 text-orange-700', text: 'GPS hace >30min' },
        offline: { color: 'bg-gray-100 text-gray-500', text: 'Sin GPS' },
    }
    return map[gps]
}

function statusBadge(estado) {
    const map = { en_proceso: 'En Servicio', programado: 'En Tránsito', disponible: 'Disponible' }
    return map[estado] || estado
}

function statusColor(estado) {
    const map = { en_proceso: 'bg-emerald-500', programado: 'bg-blue-500', disponible: 'bg-green-400' }
    return map[estado] || 'bg-gray-400'
}

function markerColor(estado) {
    const map = {
        en_proceso: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-orange.png',
        programado: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
        disponible: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
    }
    return map[estado] || 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-grey.png'
}

function initMap() {
    if (typeof L === 'undefined') {
        setTimeout(initMap, 200)
        return
    }

    map = L.map(mapContainer.value, {
        zoomControl: true,
        attributionControl: false,
    }).setView([29.0892, -110.9613], 12)

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
    }).addTo(map)

    map.zoomControl.setPosition('topright')
    renderMarkers()
}

function gpsStaleClass(tecnico) {
    const gps = gpsStatus(tecnico)
    if (gps === 'offline' || gps === 'antigua') return 'opacity-40 grayscale'
    if (gps === 'media') return 'opacity-70'
    return ''
}

function renderMarkers() {
    if (!map) return
    markers.forEach(m => map.removeLayer(m))
    markers = []

    const bounds = []

    tecnicos.value.forEach(t => {
        if (!t.latitud || !t.longitud) return

        const estado = detectarEstado(t)
        const lat = parseFloat(t.latitud)
        const lng = parseFloat(t.longitud)
        if (isNaN(lat) || isNaN(lng)) return

        bounds.push([lat, lng])

        const icon = L.icon({
            iconUrl: markerColor(estado),
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41],
        })

        const cita = t.cita_actual
        const gpsClass = gpsStaleClass(t)
        const gpsBdg = gpsBadge(t)
        const popupHtml = `
            <div class="min-w-[220px]">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xs">${t.nombre.charAt(0)}</div>
                    <div>
                        <p class="font-bold text-sm text-gray-900">${t.nombre}</p>
                        <p class="text-xs text-gray-500">${t.telefono || 'Sin teléfono'}</p>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 mb-2">
                    <span class="w-2 h-2 rounded-full ${statusColor(estado)}"></span>
                    <span class="text-xs font-semibold text-gray-600">${statusBadge(estado)}</span>
                    ${cita ? `<span class="text-xs text-gray-400">• ${cita.folio || '#'+cita.id}</span>` : ''}
                </div>
                ${cita ? `
                    <div class="bg-gray-50 rounded-lg p-2 text-xs text-gray-600 space-y-1">
                        <p class="font-medium text-gray-800">${cita.cliente_nombre || 'Cliente'}</p>
                        <p class="text-gray-400">${cita.direccion || ''}</p>
                    </div>
                ` : ''}
                <div class="flex items-center gap-1 mt-2">
                    <span class="${gpsBdg.color} text-[10px] font-semibold px-1.5 py-0.5 rounded-full">${gpsBdg.text}</span>
                    <span class="text-[10px] text-gray-400 ml-auto">${t.ultima_fecha_gps_humano || '—'}</span>
                </div>
            </div>
        `

        const marker = L.marker([lat, lng], { icon })
            .addTo(map)
            .bindPopup(popupHtml, { className: 'tracking-popup', closeButton: true, maxWidth: 300 })

        marker.on('click', () => { selectedTecnico.value = t })
        markers.push(marker)
    })

    if (bounds.length > 0) {
        clearTimeout(fitTimeout)
        fitTimeout = setTimeout(() => {
            map.fitBounds(bounds, { padding: [50, 50], maxZoom: 14 })
        }, 300)
    }
}

async function fetchUbicaciones() {
    try {
        isLoading.value = true
        const res = await axios.get(route('tracking.data'))
        if (res.data.success) {
            tecnicos.value = res.data.data
            renderMarkers()
        }
    } catch (e) {
        console.error('Error fetching tracking data:', e)
    } finally {
        isLoading.value = false
        ultimaActualizacion.value = new Date()
    }
}

function selectTecnico(t) {
    selectedTecnico.value = t
    if (t.latitud && t.longitud && map) {
        map.setView([parseFloat(t.latitud), parseFloat(t.longitud)], 15)
        const marker = markers.find((_, i) => tecnicos.value[i]?.id === t.id)
        if (marker) marker.openPopup()
    }
}

const stats = computed(() => {
    const total = tecnicos.value.length
    const enServicio = tecnicos.value.filter(t => detectarEstado(t) === 'en_proceso').length
    const enTransito = tecnicos.value.filter(t => detectarEstado(t) === 'programado').length
    const disponibles = tecnicos.value.filter(t => detectarEstado(t) === 'disponible').length
    const conGpsReciente = tecnicos.value.filter(t => gpsStatus(t) === 'reciente').length
    return { total, enServicio, enTransito, disponibles, conGpsReciente }
})

const tiempoActualizacion = ref('')
let tiempoInterval = null

onMounted(() => {
    ultimaActualizacion.value = new Date()
    if (!document.getElementById('leaflet-css')) {
        const link = document.createElement('link')
        link.id = 'leaflet-css'
        link.rel = 'stylesheet'
        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'
        document.head.appendChild(link)
    }
    if (!document.getElementById('leaflet-js')) {
        const script = document.createElement('script')
        script.id = 'leaflet-js'
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'
        script.onload = initMap
        document.head.appendChild(script)
    } else {
        initMap()
    }
    pollInterval = setInterval(fetchUbicaciones, 30000)
    tiempoInterval = setInterval(() => {
        if (ultimaActualizacion.value) {
            const diff = Math.floor((new Date() - ultimaActualizacion.value) / 1000)
            tiempoActualizacion.value = diff < 60 ? `${diff}s` : `${Math.floor(diff / 60)}m ${diff % 60}s`
        }
    }, 1000)
})

onUnmounted(() => {
    clearInterval(pollInterval)
    clearInterval(tiempoInterval)
    clearTimeout(fitTimeout)
    markers.forEach(m => map?.removeLayer(m))
    markers = []
    map?.remove()
    map = null
})
</script>

<template>
    <Head title="Tracker en Vivo" />

    <div class="h-[calc(100vh-3.5rem)] flex flex-col">
        <div class="flex items-center justify-between px-4 py-2 bg-white dark:bg-slate-900 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <h1 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                    Tracker en Vivo
                </h1>
                <div class="flex items-center gap-3 text-xs text-gray-400">
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>{{ stats.enServicio }} en servicio</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-500"></span>{{ stats.enTransito }} en tránsito</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-green-400"></span>{{ stats.disponibles }} disponibles</span>
                    <span class="ml-2 pl-2 border-l border-gray-200 dark:border-gray-600 text-gray-400">🛰️ {{ stats.conGpsReciente }}/{{ stats.total }} con GPS</span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-400">
                    🛰️ {{ tiempoActualizacion ? `hace ${tiempoActualizacion}` : 'actualizando...' }}
                </span>
                <button @click="fetchUbicaciones" :disabled="isLoading" class="text-xs px-3 py-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 font-semibold transition-colors disabled:opacity-50">
                    {{ isLoading ? 'Actualizando...' : 'Actualizar' }}
                </button>
                <div class="flex rounded-lg border border-gray-200 dark:border-gray-600 overflow-hidden">
                    <button @click="viewMode = 'mapa'" :class="viewMode === 'mapa' ? 'bg-indigo-500 text-white' : 'bg-white dark:bg-slate-800 text-gray-600 dark:text-gray-300'" class="px-3 py-1.5 text-xs font-semibold transition-colors">Mapa</button>
                    <button @click="viewMode = 'lista'" :class="viewMode === 'lista' ? 'bg-indigo-500 text-white' : 'bg-white dark:bg-slate-800 text-gray-600 dark:text-gray-300'" class="px-3 py-1.5 text-xs font-semibold transition-colors">Lista</button>
                </div>
            </div>
        </div>

        <div class="flex-1 flex overflow-hidden">
            <div class="relative flex-1">
                <div ref="mapContainer" class="absolute inset-0"></div>
                <div v-if="isLoading" class="absolute top-3 right-3 z-[1000] bg-white/90 dark:bg-slate-900/90 backdrop-blur-sm px-3 py-2 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 flex items-center gap-2">
                    <div class="w-4 h-4 border-2 border-indigo-500/30 border-t-indigo-500 rounded-full animate-spin"></div>
                    <span class="text-xs font-semibold text-gray-500">Actualizando...</span>
                </div>
                <div class="absolute bottom-4 left-4 z-[1000] bg-white/90 dark:bg-slate-900/90 backdrop-blur-md p-3 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 space-y-1.5 pointer-events-none">
                    <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-orange-500"></span><span class="text-[10px] font-bold text-gray-600 dark:text-gray-300">En Servicio</span></div>
                    <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-blue-500"></span><span class="text-[10px] font-bold text-gray-600 dark:text-gray-300">En Tránsito</span></div>
                    <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-green-400"></span><span class="text-[10px] font-bold text-gray-600 dark:text-gray-300">Disponible</span></div>
                    <div class="mt-1 pt-1 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-1">
                            <svg class="w-2.5 h-2.5 text-emerald-500" viewBox="0 0 20 20" fill="currentColor"><path d="M10 18a8 8 0 100-16 8 8 0 000 16z"/></svg>
                            <span class="text-[9px] text-gray-400">GPS reciente (&lt;5min)</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <svg class="w-2.5 h-2.5 text-amber-500" viewBox="0 0 20 20" fill="currentColor"><path d="M10 18a8 8 0 100-16 8 8 0 000 16z"/></svg>
                            <span class="text-[9px] text-gray-400">GPS desactualizado</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <svg class="w-2.5 h-2.5 text-gray-400" viewBox="0 0 20 20" fill="currentColor"><path d="M10 18a8 8 0 100-16 8 8 0 000 16z"/></svg>
                            <span class="text-[9px] text-gray-400">Sin GPS</span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-show="viewMode === 'mapa'" class="w-80 border-l border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-900 overflow-y-auto hidden lg:block">
                <div class="p-3 space-y-2">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider px-1">Técnicos ({{ tecnicos.length }})</p>
                    <div v-for="t in tecnicos" :key="t.id"
                        @click="selectTecnico(t)"
                        :class="['p-3 rounded-xl cursor-pointer border transition-all', selectedTecnico?.id === t.id ? 'border-indigo-300 dark:border-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 shadow-sm' : 'border-gray-100 dark:border-gray-700 hover:border-gray-200 dark:hover:border-gray-600 hover:shadow-sm']">
                        <div class="flex items-start gap-3">
                            <div :class="['w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm shrink-0', statusColor(detectarEstado(t))]">
                                {{ t.nombre.charAt(0) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="font-semibold text-sm text-gray-900 dark:text-white truncate">{{ t.nombre }}</p>
                                    <span :class="['w-2 h-2 rounded-full shrink-0', statusColor(detectarEstado(t))]"></span>
                                </div>
                                <div class="flex items-center gap-1 mt-0.5 flex-wrap">
                                    <span class="text-[10px] font-semibold text-gray-500">{{ statusBadge(detectarEstado(t)) }}</span>
                                    <span :class="['text-[9px] font-semibold px-1.5 py-0.5 rounded-full', gpsBadge(t).color]">{{ gpsBadge(t).text }}</span>
                                </div>
                                <div v-if="t.cita_actual" class="mt-1.5 bg-gray-50 dark:bg-slate-800 rounded-lg p-2 text-[11px] space-y-0.5">
                                    <p class="font-medium text-gray-800 dark:text-gray-200 truncate">{{ t.cita_actual.cliente_nombre || 'Cliente' }}</p>
                                    <p class="text-gray-400 truncate">{{ t.cita_actual.direccion || '' }}</p>
                                    <p v-if="t.cita_actual.folio" class="text-gray-400">Folio: {{ t.cita_actual.folio }}</p>
                                </div>
                                <p v-else class="text-[10px] text-gray-400 mt-1">Sin cita activa</p>
                            </div>
                        </div>
                    </div>
                    <div v-if="tecnicos.length === 0" class="text-center py-8 text-gray-400 text-sm">No hay técnicos activos</div>
                </div>
            </div>

            <div v-show="viewMode === 'lista'" class="flex-1 overflow-y-auto p-4 bg-gray-50 dark:bg-slate-800/50">
                <div class="max-w-3xl mx-auto space-y-3">
                    <div v-for="t in tecnicos" :key="t.id"
                        class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-gray-700 p-4 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-start gap-4">
                            <div :class="['w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg shrink-0', statusColor(detectarEstado(t))]">
                                {{ t.nombre.charAt(0) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <h3 class="font-bold text-gray-900 dark:text-white">{{ t.nombre }}</h3>
                                    <span :class="['px-2 py-0.5 rounded-full text-[10px] font-bold text-white', statusColor(detectarEstado(t))]">{{ statusBadge(detectarEstado(t)) }}</span>
                                    <span :class="['text-[10px] font-semibold px-1.5 py-0.5 rounded-full', gpsBadge(t).color]">{{ gpsBadge(t).text }}</span>
                                </div>
                                <p v-if="t.telefono" class="text-sm text-gray-500">{{ t.telefono }}</p>
                                <div v-if="t.cita_actual" class="mt-2 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl p-3 border border-indigo-100 dark:border-indigo-800/30">
                                    <div class="grid grid-cols-2 gap-2 text-sm">
                                        <div><span class="text-gray-400">Cliente</span><p class="font-medium text-gray-800 dark:text-gray-200">{{ t.cita_actual.cliente_nombre || '—' }}</p></div>
                                        <div><span class="text-gray-400">Folio</span><p class="font-medium text-gray-800 dark:text-gray-200">{{ t.cita_actual.folio || '—' }}</p></div>
                                        <div class="col-span-2"><span class="text-gray-400">Dirección</span><p class="font-medium text-gray-800 dark:text-gray-200">{{ t.cita_actual.direccion || '—' }}</p></div>
                                    </div>
                                </div>
                                <div v-else class="mt-2 text-sm text-gray-400 italic">Sin cita asignada</div>
                                <p class="text-xs text-gray-400 mt-2">🛰️ Último GPS: {{ t.ultima_fecha_gps_humano || 'Sin reporte' }}</p>
                            </div>
                            <div v-if="t.latitud && t.longitud">
                                <a :href="`https://www.google.com/maps?q=${t.latitud},${t.longitud}`" target="_blank" class="flex items-center gap-1 text-xs text-indigo-500 hover:text-indigo-600 font-semibold px-3 py-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Maps
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.tracking-popup .leaflet-popup-content-wrapper {
    border-radius: 16px !important;
    padding: 6px !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
}
.tracking-popup .leaflet-popup-tip {
    box-shadow: none !important;
}
.tracking-popup .leaflet-popup-content {
    margin: 8px 12px !important;
}
</style>
