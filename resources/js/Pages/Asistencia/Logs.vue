<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    registros: Object,
    filters: Object,
    users: Array,
    stats: { type: Object, default: () => ({}) },
});

const filterForm = ref({
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    user_id: props.filters.user_id,
    tipo: props.filters.tipo,
    incidencia: props.filters.incidencia,
});

const selfieModal = ref(null);

const applyFilters = () => {
    router.get(route('asistencia.logs'), filterForm.value, { preserveState: true, replace: true });
};

const resetFilters = () => {
    filterForm.value = { date_from: null, date_to: null, user_id: null, tipo: null, incidencia: null };
    router.get(route('asistencia.logs'), {}, { preserveState: true, replace: true });
};

const getTipoLabel = (tipo) => ({ entry: 'Entrada', exit: 'Salida', break_start: 'Descanso', break_end: 'Regreso' }[tipo] || tipo);
const getTipoIcon = (tipo) => ({ entry: '🟢', exit: '🔴', break_start: '☕', break_end: '▶️' }[tipo] || '⬜');
const getTipoClass = (tipo) => ({
    entry: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
    exit: 'bg-rose-500/10 text-rose-400 border-rose-500/20',
    break_start: 'bg-amber-500/10 text-amber-400 border-amber-500/20',
    break_end: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
}[tipo] || 'bg-neutral-500/10 text-neutral-400 border-neutral-500/20');

const formatDate = (dateString) => new Date(dateString).toLocaleString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
const formatDateShort = (dateString) => new Date(dateString).toLocaleString('es-MX', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit', hour12: false });

const faceStatusConfig = (status) => ({
    verified: { label: 'Verificado', class: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20', icon: '✓' },
    enrolled: { label: 'Enrolado', class: 'bg-blue-500/10 text-blue-400 border-blue-500/20', icon: '⊕' },
    pending: { label: 'Pendiente', class: 'bg-amber-500/10 text-amber-400 border-amber-500/20', icon: '⏳' },
    rejected: { label: 'Rechazado', class: 'bg-rose-500/10 text-rose-400 border-rose-500/20', icon: '✗' },
}[status] || { label: 'N/D', class: 'bg-neutral-500/10 text-neutral-400 border-neutral-500/20', icon: '—' });

const exportCSV = () => {
    const params = new URLSearchParams(filterForm.value).toString();
    // Build CSV from current page data (client-side for simplicity)
    const rows = props.registros.data;
    if (!rows.length) return;
    const headers = ['Empleado', 'Tipo', 'Fecha', 'Ubicación', 'Incidencia', 'Biometría', 'Calidad', 'Notas'];
    const csvRows = [headers.join(',')];
    rows.forEach(r => {
        csvRows.push([
            `"${r.user?.name || ''}"`,
            getTipoLabel(r.tipo),
            formatDate(r.registrado_at),
            `"${r.direccion || ''}"`,
            r.es_incidencia ? 'Sí' : 'No',
            faceStatusConfig(r.face_verification_status).label,
            r.face_capture_quality_passed ? 'OK' : 'Baja',
            `"${(r.face_quality_message || '').replace(/"/g, "'")}"`,
        ].join(','));
    });
    const blob = new Blob(['\ufeff' + csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = `asistencia_${filterForm.value.date_from || 'all'}_${filterForm.value.date_to || 'all'}.csv`;
    a.click(); URL.revokeObjectURL(url);
};

const statCards = computed(() => [
    { label: 'Registros', value: props.stats.total || 0, icon: '📊', color: 'from-blue-500/15 to-blue-600/5', border: 'border-blue-500/15' },
    { label: 'Entradas', value: props.stats.entries || 0, icon: '🟢', color: 'from-emerald-500/15 to-emerald-600/5', border: 'border-emerald-500/15' },
    { label: 'Salidas', value: props.stats.exits || 0, icon: '🔴', color: 'from-rose-500/15 to-rose-600/5', border: 'border-rose-500/15' },
    { label: 'Incidencias', value: props.stats.incidencias || 0, icon: '⚠️', color: 'from-amber-500/15 to-amber-600/5', border: 'border-amber-500/15' },
    { label: 'Verificados', value: `${props.stats.faceVerifiedPct || 0}%`, icon: '🔐', color: 'from-violet-500/15 to-violet-600/5', border: 'border-violet-500/15' },
    { label: 'Empleados', value: props.stats.uniqueEmployees || 0, icon: '👥', color: 'from-cyan-500/15 to-cyan-600/5', border: 'border-cyan-500/15' },
]);
</script>

<template>
    <Head title="Bitácora de Asistencia" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-black text-white tracking-tight">
                        Bitácora de <span class="bg-gradient-to-r from-blue-400 to-violet-400 bg-clip-text text-transparent">Asistencia</span>
                    </h2>
                    <p class="text-[10px] text-neutral-500 font-medium mt-1 uppercase tracking-widest">Control y monitoreo de checadas</p>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="exportCSV" class="bg-white/[0.03] hover:bg-white/[0.06] border border-white/[0.06] text-neutral-400 hover:text-white px-4 py-2 rounded-xl text-[10px] font-extrabold uppercase tracking-widest transition-all flex items-center gap-2">
                        <span>📥</span> Exportar CSV
                    </button>
                    <Link :href="route('asistencia.checador')" class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white px-6 py-2 rounded-xl text-[10px] font-extrabold uppercase tracking-widest transition-all shadow-lg shadow-blue-600/20 text-center">
                        Ir al Checador
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- ═══════ STAT CARDS ═══════ -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <div v-for="card in statCards" :key="card.label" :class="['rounded-2xl border p-4 transition-all hover:scale-[1.02]', card.border]" :style="`background: linear-gradient(135deg, ${card.color.split(' ')[0].replace('from-', '').replace('/15', '')}15 0%, transparent 100%);`">
                    <div class="flex items-center gap-3">
                        <div class="text-lg">{{ card.icon }}</div>
                        <div>
                            <div class="text-lg font-black text-white tabular-nums leading-tight">{{ card.value }}</div>
                            <div class="text-[8px] uppercase font-extrabold tracking-[0.15em] text-neutral-500">{{ card.label }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════ FILTERS ═══════ -->
            <div class="rounded-[1.5rem] border border-white/[0.06] p-5 backdrop-blur-sm" style="background: linear-gradient(180deg, rgba(255,255,255,0.02) 0%, rgba(0,0,0,0.3) 100%);">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                    <div class="space-y-1.5">
                        <label class="text-[8px] font-extrabold text-neutral-600 uppercase tracking-[0.15em]">Desde</label>
                        <input type="date" v-model="filterForm.date_from" class="w-full bg-black/40 border-white/[0.06] rounded-xl text-sm text-white focus:ring-blue-500/30 focus:border-blue-500/30 placeholder-neutral-600" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[8px] font-extrabold text-neutral-600 uppercase tracking-[0.15em]">Hasta</label>
                        <input type="date" v-model="filterForm.date_to" class="w-full bg-black/40 border-white/[0.06] rounded-xl text-sm text-white focus:ring-blue-500/30 focus:border-blue-500/30" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[8px] font-extrabold text-neutral-600 uppercase tracking-[0.15em]">Empleado</label>
                        <select v-model="filterForm.user_id" class="w-full bg-black/40 border-white/[0.06] rounded-xl text-sm text-white focus:ring-blue-500/30 focus:border-blue-500/30">
                            <option :value="null">Todos</option>
                            <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[8px] font-extrabold text-neutral-600 uppercase tracking-[0.15em]">Tipo</label>
                        <select v-model="filterForm.tipo" class="w-full bg-black/40 border-white/[0.06] rounded-xl text-sm text-white focus:ring-blue-500/30 focus:border-blue-500/30">
                            <option :value="null">Todos</option>
                            <option value="entry">Entrada</option>
                            <option value="exit">Salida</option>
                            <option value="break_start">Descanso</option>
                            <option value="break_end">Regreso</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button @click="applyFilters" class="flex-1 bg-blue-600/20 hover:bg-blue-600/30 border border-blue-500/20 text-blue-400 py-2.5 rounded-xl text-[9px] font-extrabold uppercase tracking-widest transition-all">
                            Filtrar
                        </button>
                        <button @click="resetFilters" class="p-2.5 border border-white/[0.06] rounded-xl text-neutral-600 hover:text-white hover:border-white/10 transition-all" title="Limpiar filtros">
                            ↺
                        </button>
                    </div>
                </div>
            </div>

            <!-- ═══════ TABLE ═══════ -->
            <div class="rounded-[1.5rem] border border-white/[0.06] overflow-hidden shadow-2xl" style="background: linear-gradient(180deg, rgba(255,255,255,0.015) 0%, rgba(0,0,0,0.2) 100%);">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-white/[0.05]" style="background: rgba(0,0,0,0.3);">
                                <th class="px-5 py-4 text-[8px] font-extrabold text-neutral-600 uppercase tracking-[0.15em] text-center w-20">Tipo</th>
                                <th class="px-5 py-4 text-[8px] font-extrabold text-neutral-600 uppercase tracking-[0.15em]">Empleado</th>
                                <th class="px-5 py-4 text-[8px] font-extrabold text-neutral-600 uppercase tracking-[0.15em]">Fecha</th>
                                <th class="px-5 py-4 text-[8px] font-extrabold text-neutral-600 uppercase tracking-[0.15em] hidden lg:table-cell">Ubicación</th>
                                <th class="px-5 py-4 text-[8px] font-extrabold text-neutral-600 uppercase tracking-[0.15em] text-center w-20">Estado</th>
                                <th class="px-5 py-4 text-[8px] font-extrabold text-neutral-600 uppercase tracking-[0.15em] text-center w-32">Biometría</th>
                                <th class="px-5 py-4 text-[8px] font-extrabold text-neutral-600 uppercase tracking-[0.15em] text-center w-16">📸</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.03]">
                            <tr v-for="reg in registros.data" :key="reg.id" class="hover:bg-white/[0.015] transition-colors group">
                                <!-- Tipo -->
                                <td class="px-5 py-3.5 text-center">
                                    <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[9px] font-bold border', getTipoClass(reg.tipo)]">
                                        <span class="text-xs">{{ getTipoIcon(reg.tipo) }}</span>
                                        <span class="hidden sm:inline">{{ getTipoLabel(reg.tipo) }}</span>
                                    </span>
                                </td>
                                <!-- Empleado -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-7 h-7 rounded-lg bg-white/[0.04] border border-white/[0.06] flex items-center justify-center overflow-hidden flex-shrink-0">
                                            <img v-if="reg.user?.profile_photo_path" :src="`/storage/${reg.user.profile_photo_path}`" class="w-full h-full object-cover" />
                                            <span v-else class="text-[9px] font-bold text-neutral-600">{{ reg.user?.name?.[0] }}</span>
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold text-white group-hover:text-blue-400 transition-colors leading-tight">{{ reg.user?.name }}</div>
                                            <div class="text-[9px] text-neutral-600 font-medium mt-0.5">{{ reg.origen === 'token_link' ? '🔗 Enlace' : '🖥️ Panel' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <!-- Fecha -->
                                <td class="px-5 py-3.5">
                                    <div class="text-xs text-neutral-300 font-medium tabular-nums">{{ formatDateShort(reg.registrado_at) }}</div>
                                </td>
                                <!-- Ubicación -->
                                <td class="px-5 py-3.5 hidden lg:table-cell">
                                    <div class="text-[10px] text-neutral-500 max-w-[200px] truncate" :title="reg.direccion">{{ reg.direccion || '—' }}</div>
                                    <div v-if="reg.latitud" class="text-[8px] text-neutral-700 font-mono mt-0.5">{{ Number(reg.latitud).toFixed(4) }}, {{ Number(reg.longitud).toFixed(4) }}</div>
                                </td>
                                <!-- Incidencia -->
                                <td class="px-5 py-3.5 text-center">
                                    <div v-if="reg.es_incidencia" class="group/tip relative inline-block cursor-help">
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-400 text-[9px] font-bold">⚠️ Inc.</span>
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-52 p-3 bg-neutral-900 border border-white/10 text-[9px] text-neutral-300 font-medium rounded-xl opacity-0 group-hover/tip:opacity-100 transition-opacity pointer-events-none z-20 shadow-2xl">
                                            {{ reg.motivo_incidencia }}
                                        </div>
                                    </div>
                                    <span v-else class="text-emerald-500/30 text-xs">✓</span>
                                </td>
                                <!-- Biometría -->
                                <td class="px-5 py-3.5 text-center">
                                    <div class="space-y-1">
                                        <span :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[9px] font-bold border', faceStatusConfig(reg.face_verification_status).class]" :title="reg.face_verification_notes || ''">
                                            {{ faceStatusConfig(reg.face_verification_status).icon }}
                                            {{ faceStatusConfig(reg.face_verification_status).label }}
                                        </span>
                                        <div class="flex items-center justify-center gap-1.5 text-[8px]">
                                            <span :class="['px-1.5 py-0.5 rounded border font-bold', reg.face_capture_quality_passed ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' : 'bg-rose-500/10 text-rose-500 border-rose-500/20']">
                                                {{ reg.face_capture_quality_passed ? 'HD' : 'LQ' }}
                                            </span>
                                            <span class="text-neutral-700 font-mono">R:{{ reg.face_detected_count ?? '?' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <!-- Selfie -->
                                <td class="px-5 py-3.5 text-center">
                                    <button v-if="reg.selfie_path" @click="selfieModal = reg" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-white/[0.03] hover:bg-blue-600/20 border border-white/[0.06] text-neutral-500 hover:text-blue-400 transition-all hover:scale-110">
                                        📷
                                    </button>
                                    <span v-else class="text-neutral-800">—</span>
                                </td>
                            </tr>
                            <tr v-if="registros.data.length === 0">
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <div class="text-2xl mb-3 opacity-30">📋</div>
                                    <div class="text-neutral-600 font-extrabold uppercase tracking-[0.2em] text-[10px]">No se encontraron registros</div>
                                    <p class="text-[10px] text-neutral-700 mt-1">Ajusta los filtros para ampliar la búsqueda.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-white/[0.04]" style="background: rgba(0,0,0,0.15);">
                    <div class="flex items-center justify-between">
                        <div class="text-[9px] text-neutral-600 font-medium">
                            Mostrando {{ registros.from }}–{{ registros.to }} de {{ registros.total }} registros
                        </div>
                        <Pagination :links="registros.links" />
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════ SELFIE MODAL ═══════ -->
        <Teleport to="body">
            <transition name="modal-fade">
                <div v-if="selfieModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="selfieModal = null"></div>
                    <div class="relative max-w-md w-full rounded-[2rem] overflow-hidden border border-white/[0.08] shadow-2xl" style="background: linear-gradient(180deg, rgba(30,30,40,0.98) 0%, rgba(10,10,15,0.98) 100%);">
                        <!-- Close button (always visible, floating) -->
                        <button @click="selfieModal = null" class="absolute top-4 right-4 z-10 w-10 h-10 flex items-center justify-center rounded-full bg-black/60 hover:bg-black/80 text-white text-lg font-bold transition-all border border-white/10 shadow-lg backdrop-blur-sm">✕</button>
                        <div class="p-5 border-b border-white/[0.05]">
                            <div class="text-sm font-bold text-white">{{ selfieModal.user?.name }}</div>
                            <div class="text-[10px] text-neutral-500 mt-0.5">{{ formatDate(selfieModal.registrado_at) }} · {{ getTipoLabel(selfieModal.tipo) }}</div>
                        </div>
                        <div class="aspect-square">
                            <img :src="`/storage/${selfieModal.selfie_path}`" class="w-full h-full object-cover" />
                        </div>
                        <div class="p-4 space-y-2">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span :class="['px-2 py-0.5 rounded-lg text-[9px] font-bold border', faceStatusConfig(selfieModal.face_verification_status).class]">
                                    {{ faceStatusConfig(selfieModal.face_verification_status).icon }} {{ faceStatusConfig(selfieModal.face_verification_status).label }}
                                </span>
                                <span v-if="selfieModal.face_match_score" class="text-[9px] text-neutral-500 font-mono">Score: {{ Number(selfieModal.face_match_score).toFixed(2) }}</span>
                                <span v-if="selfieModal.es_incidencia" class="text-[9px] px-2 py-0.5 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20 font-bold">⚠️ Incidencia</span>
                            </div>
                            <div v-if="selfieModal.face_verification_notes" class="text-[9px] text-neutral-600 leading-relaxed max-h-20 overflow-y-auto">{{ selfieModal.face_verification_notes }}</div>
                            <div v-if="selfieModal.direccion" class="text-[9px] text-neutral-500 flex items-start gap-1.5">
                                <span>📍</span> {{ selfieModal.direccion }}
                            </div>
                        </div>
                        <!-- Bottom close button for mobile -->
                        <button @click="selfieModal = null" class="w-full py-3 border-t border-white/[0.05] text-[10px] font-extrabold uppercase tracking-widest text-neutral-500 hover:text-white hover:bg-white/[0.03] transition-all">Cerrar</button>
                    </div>
                </div>
            </transition>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: all .25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}
.modal-fade-enter-from .relative,
.modal-fade-leave-to .relative {
    transform: scale(0.95) translateY(10px);
}
</style>
