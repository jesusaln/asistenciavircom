<template>
    <div v-if="mantenimiento" class="space-y-6">
        <!-- Badge y Título -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50 p-4 rounded-xl border border-slate-100">
            <div class="flex items-center gap-3">
                <div :class="[iconBgClass, 'p-2.5 rounded-xl shadow-sm border border-white/50']">
                    <component :is="serviceIcon" class="w-6 h-6" />
                </div>
                <div>
                    <h4 class="text-lg font-bold text-slate-900 leading-tight">{{ mantenimiento.tipo }}</h4>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">{{ mantenimiento.folio || 'S/F' }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span :class="[statusClasses.bg, statusClasses.text, 'px-3 py-1 rounded-full text-xs font-bold border border-white shadow-sm transition-all duration-300 transform hover:scale-105']">
                    {{ statusLabel }}
                </span>
                <span v-if="mantenimiento.prioridad" :class="[priorityClasses.bg, priorityClasses.text, 'px-3 py-1 rounded-full text-xs font-bold border border-white shadow-sm']">
                    {{ priorityLabel }}
                </span>
            </div>
        </div>

        <!-- Información Principal -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-4">
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow group">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2 bg-blue-50 text-blue-600 rounded-xl group-hover:bg-blue-600 group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m4-4l-4-4" />
                            </svg>
                        </div>
                        <h5 class="font-bold text-slate-800">Vehículo</h5>
                    </div>
                    <div class="pl-11">
                        <p class="text-slate-900 font-semibold">{{ vehiculoLabel }}</p>
                        <p v-if="mantenimiento.carro?.placa" class="text-xs text-slate-500 font-medium">Placas: <span class="bg-slate-100 px-1.5 py-0.5 rounded-xl">{{ mantenimiento.carro.placa }}</span></p>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow group">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2 bg-emerald-50 text-emerald-600 rounded-xl group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                            </svg>
                        </div>
                        <h5 class="font-bold text-slate-800">Costo y Registro</h5>
                    </div>
                    <div class="pl-11 grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-slate-500 font-medium uppercase tracking-wide mb-0.5">Costo Total</p>
                            <p class="text-slate-900 font-bold text-lg">{{ formatMoney(mantenimiento.costo) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 font-medium uppercase tracking-wide mb-0.5">Kilometraje</p>
                            <p class="text-slate-900 font-bold text-lg">{{ formatNumber(mantenimiento.kilometraje_actual) }} <span class="text-xs font-normal text-slate-400">km</span></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow group">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2 bg-indigo-50 text-indigo-600 rounded-xl group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h5 class="font-bold text-slate-800">Cronograma</h5>
                    </div>
                    <div class="pl-11 space-y-3">
                        <div class="flex items-center justify-between py-1 border-b border-dashed border-slate-100">
                            <span class="text-sm text-slate-500 font-medium">Realizado el</span>
                            <span class="text-sm text-slate-800 font-bold">{{ formatearFecha(mantenimiento.fecha) }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1">
                            <span class="text-sm text-slate-500 font-medium text-orange-600">Próximo servicio</span>
                            <span class="text-sm text-orange-800 font-bold bg-orange-50 px-2 py-0.5 rounded-xl">{{ formatearFecha(mantenimiento.proximo_mantenimiento) }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow group">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2 bg-purple-50 text-purple-600 rounded-xl group-hover:bg-purple-600 group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <h5 class="font-bold text-slate-800">Proveedor / Taller</h5>
                    </div>
                    <div class="pl-11">
                        <p class="text-slate-900 font-semibold">{{ mantenimiento.taller || 'No especificado' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Descripciones y Notas con estilo premium -->
        <div v-if="mantenimiento.descripcion || mantenimiento.notas || mantenimiento.observaciones_alerta" class="space-y-4">
            <div v-if="mantenimiento.descripcion" class="bg-brand-50/30 p-5 rounded-2xl border border-brand-100/50 shadow-inner">
                <h5 class="text-xs font-black text-brand-700 uppercase tracking-wide mb-3 flex items-center gap-2">
                    <span class="w-1.5 h-1.5 bg-brand-500 rounded-full"></span>
                    Descripción detallada
                </h5>
                <p class="text-slate-700 text-sm leading-relaxed whitespace-pre-wrap">{{ mantenimiento.descripcion }}</p>
            </div>

            <div v-if="mantenimiento.notas" class="bg-slate-50 p-5 rounded-2xl border border-slate-200/50">
                <h5 class="text-xs font-black text-slate-500 uppercase tracking-wide mb-3 flex items-center gap-2">
                    <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                    Notas internas
                </h5>
                <p class="text-slate-600 text-sm italic leading-relaxed whitespace-pre-wrap">{{ mantenimiento.notas }}</p>
            </div>

            <div v-if="mantenimiento.observaciones_alerta" class="bg-rose-50/50 p-5 rounded-2xl border border-rose-100/50">
                <h5 class="text-xs font-black text-rose-700 uppercase tracking-wide mb-3 flex items-center gap-2">
                    <span class="w-1.5 h-1.5 bg-rose-500 rounded-full"></span>
                    Observaciones de alerta
                </h5>
                <p class="text-rose-900 text-sm font-medium leading-relaxed">{{ mantenimiento.observaciones_alerta }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { computed } from 'vue';

const props = defineProps({
    mantenimiento: { type: Object, required: true }
});

// Helpers de formato
const formatNumber = (n) => new Intl.NumberFormat('es-MX').format(Number(n) || 0);
const formatMoney = (value) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(value) || 0);

const formatearFecha = (date) => {
    if (!date) return '—';
    try {
        const d = typeof date === 'string' && date.length <= 10 ? new Date(date + 'T12:00:00') : new Date(date);
        return d.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });
    } catch {
        return '—';
    }
};

const vehiculoLabel = computed(() => {
    const c = props.mantenimiento?.carro;
    if (!c) return 'Vehículo no asignado';
    return `${c.marca || ''} ${c.modelo || ''}`.trim() || 'Vehículo';
});

// Iconografía dinámica
const serviceIcon = computed(() => {
    const type = (props.mantenimiento.tipo || '').toLowerCase();
    if (type.includes('aceite')) return OilIcon;
    if (type.includes('freno')) return BrakeIcon;
    if (type.includes('llanta') || type.includes('frenos')) return WheelIcon;
    if (type.includes('batería')) return BatteryIcon;
    return DefaultIcon;
});

const iconBgClass = computed(() => {
    const type = (props.mantenimiento.tipo || '').toLowerCase();
    if (type.includes('aceite')) return 'bg-brand-100 text-amber-600';
    if (type.includes('freno')) return 'bg-rose-100 text-rose-600';
    if (type.includes('batería')) return 'bg-yellow-100 text-yellow-600';
    return 'bg-sky-100 text-sky-700';
});

// Mapeo de estados
const statusClasses = computed(() => {
    const estado = (props.mantenimiento.estado || '').toLowerCase();
    if (estado === 'completado') return { bg: 'bg-emerald-100', text: 'text-emerald-700' };
    if (estado === 'en_proceso') return { bg: 'bg-sky-100', text: 'text-sky-700' };
    return { bg: 'bg-amber-100', text: 'text-amber-700' };
});

const statusLabel = computed(() => {
    const labels = { 'completado': 'Completado', 'en_proceso': 'En Taller', 'pendiente': 'Pendiente' };
    return labels[props.mantenimiento.estado] || props.mantenimiento.estado;
});

// Mapeo de prioridades
const priorityClasses = computed(() => {
    const p = (props.mantenimiento.prioridad || 'media').toLowerCase();
    if (p === 'critica') return { bg: 'bg-rose-600', text: 'text-white' };
    if (p === 'alta') return { bg: 'bg-amber-100', text: 'text-orange-700' };
    if (p === 'media') return { bg: 'bg-sky-100', text: 'text-blue-700' };
    return { bg: 'bg-slate-100', text: 'text-slate-700' };
});

const priorityLabel = computed(() => {
    const labels = { 'critica': 'URGENTE', 'alta': 'Alta', 'media': 'Media', 'baja': 'Baja' };
    return labels[props.mantenimiento.prioridad] || props.mantenimiento.prioridad;
});

// Componentes funcionales para iconos (para no importar externos)
const OilIcon = { template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.631.316a6 6 0 01-3.854.517l-2.387-.477a2 2 0 00-1.022.547l-1.022.547a2 2 0 000 3.106l1.022.547a2 2 0 001.022.547l2.387-.477a6 6 0 013.854-.517l.631.316a6 6 0 003.86-.517l2.387.477a2 2 0 001.022-.547l1.022-.547a2 2 0 000-3.106l-1.022-.547z" /></svg>' };
const BrakeIcon = { template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>' };
const WheelIcon = { template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 22a10 10 0 100-20 10 10 0 000 20zM12 18a6 6 0 100-12 6 6 0 000 12zM12 12h.01" /></svg>' };
const BatteryIcon = { template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>' };
const DefaultIcon = { template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>' };

</script>

<style scoped>
/* Transiciones suaves */
.group:hover .p-2 {
    transform: translateY(-2px);
}
</style>
