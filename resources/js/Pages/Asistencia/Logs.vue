<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import Pagination from '@/Components/Pagination.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
    registros: Object,
    filters: Object,
    users: Array,
});

const filterForm = ref({
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    user_id: props.filters.user_id,
    tipo: props.filters.tipo,
});

const applyFilters = () => {
    router.get(route('asistencia.logs'), filterForm.value, {
        preserveState: true,
        replace: true,
    });
};

const getTipoLabel = (tipo) => {
    const labels = {
        'entry': 'Entrada',
        'exit': 'Salida',
        'break_start': 'Inic. Descanso',
        'break_end': 'Fin Descanso',
    };
    return labels[tipo] || tipo;
};

const getTipoClass = (tipo) => {
    const classes = {
        'entry': 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
        'exit': 'bg-rose-500/10 text-rose-400 border-rose-500/30',
        'break_start': 'bg-amber-500/10 text-amber-400 border-amber-500/30',
        'break_end': 'bg-blue-500/10 text-blue-400 border-blue-500/30',
    };
    return classes[tipo] || 'bg-neutral-500/10 text-neutral-400 border-neutral-500/30';
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString('es-MX', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const faceStatusClass = (status) => {
    const classes = {
        verified: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
        enrolled: 'bg-blue-500/10 text-blue-400 border-blue-500/30',
        pending: 'bg-amber-500/10 text-amber-400 border-amber-500/30',
        rejected: 'bg-rose-500/10 text-rose-400 border-rose-500/30',
    };
    return classes[status] || 'bg-neutral-500/10 text-neutral-400 border-neutral-500/30';
};

const faceStatusLabel = (status) => {
    const labels = {
        verified: 'Verificado',
        enrolled: 'Enrolado',
        pending: 'Pendiente',
        rejected: 'Rechazado',
    };
    return labels[status] || 'N/D';
};

const qualityValue = (value, decimals = 2) => {
    if (value === null || value === undefined || Number.isNaN(Number(value))) return 'N/D';
    return Number(value).toFixed(decimals);
};

const qualityBadgeClass = (passed) => {
    if (passed === true) return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30';
    if (passed === false) return 'bg-rose-500/10 text-rose-400 border-rose-500/30';
    return 'bg-neutral-500/10 text-neutral-400 border-neutral-500/30';
};

const qualityBadgeLabel = (passed) => {
    if (passed === true) return 'Calidad OK';
    if (passed === false) return 'Calidad baja';
    return 'Sin datos';
};
</script>

<template>
    <Head title="Bitácora de Asistencia" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <h2 class="text-2xl font-black text-white tracking-tight uppercase">
                    Bitácora de <span class="text-blue-500">Asistencia</span>
                </h2>
                <Link 
                    :href="route('asistencia.checador')"
                    class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all shadow-lg shadow-blue-600/20 text-center"
                >
                    Ir al Checador
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Filters Card -->
            <div class="bg-neutral-900/50 border border-white/5 rounded-3xl p-6 backdrop-blur-sm">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-neutral-500 uppercase tracking-widest">Desde</label>
                        <input 
                            type="date" 
                            v-model="filterForm.date_from"
                            class="w-full bg-black border-white/10 rounded-xl text-sm text-white focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-neutral-500 uppercase tracking-widest">Hasta</label>
                        <input 
                            type="date" 
                            v-model="filterForm.date_to"
                            class="w-full bg-black border-white/10 rounded-xl text-sm text-white focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-neutral-500 uppercase tracking-widest">Empleado</label>
                        <select 
                            v-model="filterForm.user_id"
                            class="w-full bg-black border-white/10 rounded-xl text-sm text-white focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option :value="null">Todos los empleados</option>
                            <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button 
                            @click="applyFilters"
                            class="flex-1 bg-white/5 hover:bg-white/10 border border-white/10 text-white py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all"
                        >
                            Filtrar
                        </button>
                        <Link 
                            :href="route('asistencia.logs')"
                            class="p-2 border border-white/10 rounded-xl text-neutral-500 hover:text-white transition-colors"
                        >
                             <FontAwesomeIcon icon="sync-alt" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-neutral-900/50 border border-white/5 rounded-3xl overflow-hidden shadow-2xl backdrop-blur-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-white/5 bg-black/20">
                                <th class="px-6 py-4 text-[10px] font-black text-neutral-500 uppercase tracking-widest text-center">Tipo</th>
                                <th class="px-6 py-4 text-[10px] font-black text-neutral-500 uppercase tracking-widest">Empleado</th>
                                <th class="px-6 py-4 text-[10px] font-black text-neutral-500 uppercase tracking-widest">Fecha y Hora</th>
                                <th class="px-6 py-4 text-[10px] font-black text-neutral-500 uppercase tracking-widest">Ubicación</th>
                                <th class="px-6 py-4 text-[10px] font-black text-neutral-500 uppercase tracking-widest text-center">Incidencia</th>
                                <th class="px-6 py-4 text-[10px] font-black text-neutral-500 uppercase tracking-widest text-center">Biometría</th>
                                <th class="px-6 py-4 text-[10px] font-black text-neutral-500 uppercase tracking-widest text-center">Evidencia</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr v-for="reg in registros.data" :key="reg.id" class="hover:bg-white/[0.02] transition-colors group">
                                <td class="px-6 py-4 text-center">
                                    <span :class="['px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest border', getTipoClass(reg.tipo)]">
                                        {{ getTipoLabel(reg.tipo) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-white group-hover:text-blue-400 transition-colors">{{ reg.user?.name }}</div>
                                    <div class="text-[10px] text-neutral-500 uppercase font-bold">{{ reg.origen }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-neutral-300 font-medium tabular-nums">{{ formatDate(reg.registrado_at) }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-[10px] text-neutral-400 max-w-xs truncate" :title="reg.direccion">
                                        {{ reg.direccion || 'No capturada' }}
                                    </div>
                                    <div v-if="reg.latitud" class="text-[9px] text-neutral-600 font-mono mt-0.5">
                                        {{ reg.latitud }}, {{ reg.longitud }} ({{ reg.precision_metros }}m)
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div v-if="reg.es_incidencia" class="group relative inline-block">
                                        <div class="text-amber-500 animate-pulse cursor-help">
                                            <FontAwesomeIcon icon="exclamation-triangle" />
                                        </div>
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2 bg-amber-600 text-[9px] text-white font-black uppercase rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10 shadow-xl">
                                            {{ reg.motivo_incidencia }}
                                        </div>
                                    </div>
                                    <div v-else class="text-emerald-500 opacity-20">
                                        <FontAwesomeIcon icon="check-circle" />
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="space-y-1.5">
                                        <span :class="['px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest border', faceStatusClass(reg.face_verification_status)]" :title="reg.face_verification_notes || ''">
                                            {{ faceStatusLabel(reg.face_verification_status) }}
                                        </span>
                                        <div class="text-[9px] text-neutral-500 leading-relaxed">
                                            <span :class="['px-2 py-0.5 rounded-full border font-black uppercase tracking-wide', qualityBadgeClass(reg.face_capture_quality_passed)]">
                                                {{ qualityBadgeLabel(reg.face_capture_quality_passed) }}
                                            </span>
                                            <div class="mt-1 font-mono text-neutral-600">
                                                R: {{ reg.face_detected_count ?? 'N/D' }}
                                                | B: {{ qualityValue(reg.face_quality_brightness) }}
                                                | N: {{ qualityValue(reg.face_quality_sharpness) }}
                                            </div>
                                            <div class="font-mono text-neutral-600">
                                                A: {{ qualityValue(reg.face_quality_area_ratio) }}
                                                | C: {{ qualityValue(reg.face_quality_center_offset) }}
                                            </div>
                                            <div v-if="reg.face_quality_message" class="text-[9px] text-neutral-500 mt-0.5 max-w-[14rem] mx-auto truncate" :title="reg.face_quality_message">
                                                {{ reg.face_quality_message }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <a v-if="reg.selfie_path" :href="`/storage/${reg.selfie_path}`" target="_blank" class="inline-block p-2 bg-white/5 hover:bg-blue-600 border border-white/10 rounded-lg text-neutral-400 hover:text-white transition-all transform hover:scale-110">
                                        <FontAwesomeIcon icon="camera" />
                                    </a>
                                    <span v-else class="text-neutral-700">
                                        <FontAwesomeIcon icon="minus" />
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="registros.data.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="text-neutral-600 font-black uppercase tracking-[0.2em] text-[10px]">No se encontraron registros</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-white/5 bg-black/10">
                    <Pagination :links="registros.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Transiciones suaves para los tooltips */
.group:hover .group-hover\:opacity-100 {
    opacity: 1;
}
</style>
