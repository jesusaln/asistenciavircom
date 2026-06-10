<template>
    <AppLayout title="Buzón de Denuncias NOM-035">
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-black text-[var(--ui-text)] uppercase tracking-wider">
                        Buzón de Denuncias
                    </h2>
                    <p class="text-xs font-bold text-[var(--ui-text-soft)] uppercase tracking-widest mt-1">
                        Mecanismo de recepción y seguimiento de quejas (NOM-035-STPS-2018)
                    </p>
                </div>
            </div>
        </template>

        <div class="py-12 px-6 max-w-7xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Stats Cards -->
                <div class="bg-[var(--ui-surface-soft)] p-6 rounded-[2rem] border border-[var(--ui-border)] shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-amber-500/10 flex items-center justify-center text-amber-500">
                            <FontAwesomeIcon icon="clock" class="text-xl" />
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-wider">Pendientes</p>
                            <p class="text-2xl font-black text-[var(--ui-text)]">{{ complaints.filter(c => c.status === 'pending').length }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-[var(--ui-surface-soft)] p-6 rounded-[2rem] border border-[var(--ui-border)] shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-blue-500/10 flex items-center justify-center text-blue-500">
                            <FontAwesomeIcon icon="sync-alt" class="text-xl" />
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-wider">En Revisión</p>
                            <p class="text-2xl font-black text-[var(--ui-text)]">{{ complaints.filter(c => c.status === 'in_review').length }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-[var(--ui-surface-soft)] p-6 rounded-[2rem] border border-[var(--ui-border)] shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                            <FontAwesomeIcon icon="check-circle" class="text-xl" />
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-wider">Resueltas</p>
                            <p class="text-2xl font-black text-[var(--ui-text)]">{{ complaints.filter(c => c.status === 'resolved').length }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-[var(--ui-surface-soft)] rounded-[2.5rem] border border-[var(--ui-border)] shadow-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-[var(--ui-border)] bg-[var(--ui-surface-alt)]">
                                <th class="px-6 py-4 text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest">Folio</th>
                                <th class="px-6 py-4 text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest">Tipo</th>
                                <th class="px-6 py-4 text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest">Informante</th>
                                <th class="px-6 py-4 text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest">Fecha Reporte</th>
                                <th class="px-6 py-4 text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest">Estado</th>
                                <th class="px-6 py-4 text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--ui-border)]">
                            <tr v-for="complaint in complaints" :key="complaint.id" class="hover:bg-[var(--ui-surface)] transition-colors group">
                                <td class="px-6 py-5">
                                    <span class="text-xs font-black text-[var(--ui-text)]">{{ complaint.folio }}</span>
                                </td>
                                <td class="px-6 py-5">
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border" 
                                          :class="getTypeClass(complaint.type)">
                                        {{ complaint.type }}
                                    </span>
                                </td>
                                <td class="px-6 py-5">
                                    <div v-if="complaint.is_anonymous" class="flex items-center gap-2">
                                        <FontAwesomeIcon icon="user-secret" class="text-slate-400" />
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Anónimo</span>
                                    </div>
                                    <div v-else>
                                        <p class="text-xs font-bold text-[var(--ui-text)]">{{ complaint.reporter_name }}</p>
                                        <p class="text-[9px] font-medium text-[var(--ui-text-soft)]">{{ complaint.reporter_email }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <span class="text-xs font-bold text-[var(--ui-text-soft)]">{{ formatDate(complaint.created_at) }}</span>
                                </td>
                                <td class="px-6 py-5">
                                    <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest"
                                          :class="getStatusClass(complaint.status)">
                                        {{ getStatusLabel(complaint.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-5 text-right">
                                    <Link :href="route('nom035.complaints.show', complaint.id)" 
                                          class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] text-[var(--ui-text-soft)] hover:text-[var(--ui-accent)] hover:border-[var(--ui-accent)]/30 transition-all duration-300">
                                        <FontAwesomeIcon icon="eye" />
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="complaints.length === 0">
                                <td colspan="6" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-20 h-20 rounded-full bg-[var(--ui-surface-alt)] flex items-center justify-center text-[var(--ui-text-soft)] mb-4 border border-[var(--ui-border)]">
                                            <FontAwesomeIcon icon="inbox" class="text-3xl" />
                                        </div>
                                        <h3 class="text-sm font-black text-[var(--ui-text)] uppercase tracking-widest">No hay denuncias</h3>
                                        <p class="text-xs font-bold text-[var(--ui-text-soft)] uppercase mt-1">El buzón está vacío actualmente</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import dayjs from 'dayjs';
import 'dayjs/locale/es';

dayjs.locale('es');

const props = defineProps({
    complaints: Array
});

const formatDate = (date) => {
    return dayjs(date).format('DD MMM, YYYY');
};

const getStatusLabel = (status) => {
    const labels = {
        'pending': 'Pendiente',
        'in_review': 'En Revisión',
        'resolved': 'Resuelta',
        'dismissed': 'Desestimada'
    };
    return labels[status] || status;
};

const getStatusClass = (status) => {
    const classes = {
        'pending': 'bg-amber-500/10 text-amber-600 border-amber-500/20',
        'in_review': 'bg-blue-500/10 text-blue-600 border-blue-500/20',
        'resolved': 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
        'dismissed': 'bg-slate-500/10 text-slate-600 border-slate-500/20'
    };
    return classes[status] || '';
};

const getTypeClass = (type) => {
    const classes = {
        'violencia': 'bg-rose-500/10 text-rose-600 border-rose-500/20',
        'acoso': 'bg-purple-500/10 text-purple-600 border-purple-500/20',
        'condiciones': 'bg-amber-500/10 text-amber-600 border-amber-500/20',
        'otro': 'bg-slate-500/10 text-slate-600 border-slate-500/20'
    };
    return classes[type.toLowerCase()] || 'bg-blue-500/10 text-blue-600 border-blue-500/20';
};
</script>
