<template>
    <AppLayout :title="'Denuncia ' + complaint.folio">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('nom035.complaints.index')" class="w-10 h-10 rounded-xl bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] flex items-center justify-center text-[var(--ui-text-soft)] hover:text-[var(--ui-accent)] transition-all duration-300">
                        <FontAwesomeIcon icon="arrow-left" />
                    </Link>
                    <div>
                        <h2 class="text-2xl font-black text-[var(--ui-text)] uppercase tracking-wider">
                            Detalle de Denuncia
                        </h2>
                        <p class="text-xs font-bold text-[var(--ui-text-soft)] uppercase tracking-widest mt-1">
                            Folio: {{ complaint.folio }} • Recibido el {{ formatDate(complaint.created_at) }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-[0.2em] shadow-sm border"
                          :class="getStatusClass(complaint.status)">
                        {{ getStatusLabel(complaint.status) }}
                    </span>
                </div>
            </div>
        </template>

        <div class="py-12 px-6 max-w-5xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Description -->
                    <div class="bg-[var(--ui-surface-soft)] rounded-[2.5rem] p-8 border border-[var(--ui-border)] shadow-xl">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-xl bg-brand-500/10 flex items-center justify-center text-brand-500">
                                <FontAwesomeIcon icon="file-lines" />
                            </div>
                            <h3 class="text-sm font-black text-[var(--ui-text)] uppercase tracking-widest">Descripción de los Hechos</h3>
                        </div>
                        <div class="bg-[var(--ui-surface)] p-6 rounded-3xl border border-[var(--ui-border)] min-h-[200px]">
                            <p class="text-sm leading-relaxed text-[var(--ui-text)] whitespace-pre-wrap">{{ complaint.description }}</p>
                        </div>
                    </div>

                    <!-- Resolution (if resolved) -->
                    <div v-if="complaint.resolution_details" class="bg-emerald-500/5 rounded-[2.5rem] p-8 border border-emerald-500/20 shadow-xl">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                                <FontAwesomeIcon icon="check-double" />
                            </div>
                            <h3 class="text-sm font-black text-emerald-600 uppercase tracking-widest">Resolución y Acciones</h3>
                        </div>
                        <div class="bg-white/50 dark:bg-black/20 p-6 rounded-3xl border border-emerald-500/10">
                            <p class="text-sm leading-relaxed text-emerald-700 dark:text-emerald-300 whitespace-pre-wrap">{{ complaint.resolution_details }}</p>
                            <p class="text-[10px] font-black text-emerald-600/60 uppercase tracking-widest mt-4">Resuelto el {{ formatDate(complaint.resolved_at) }}</p>
                        </div>
                    </div>

                    <!-- Update Status Form -->
                    <div class="bg-[var(--ui-surface-soft)] rounded-[2.5rem] p-8 border border-[var(--ui-border)] shadow-xl">
                        <div class="flex items-center gap-3 mb-8">
                            <div class="w-10 h-10 rounded-xl bg-[var(--ui-accent)]/10 flex items-center justify-center text-[var(--ui-accent)]">
                                <FontAwesomeIcon icon="cog" />
                            </div>
                            <h3 class="text-sm font-black text-[var(--ui-text)] uppercase tracking-widest">Gestión de la Denuncia</h3>
                        </div>

                        <form @submit.prevent="updateComplaint" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest ml-1">Cambiar Estado</label>
                                    <select v-model="form.status" class="w-full bg-[var(--ui-surface)] border-[var(--ui-border)] rounded-2xl text-xs font-bold text-[var(--ui-text)] focus:ring-[var(--ui-accent)] focus:border-[var(--ui-accent)] transition-all">
                                        <option value="pending">Pendiente</option>
                                        <option value="in_review">En Revisión</option>
                                        <option value="resolved">Resuelta</option>
                                        <option value="dismissed">Desestimada</option>
                                    </select>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest ml-1">Notas Administrativas (Privadas)</label>
                                <textarea v-model="form.admin_notes" rows="4" class="w-full bg-[var(--ui-surface)] border-[var(--ui-border)] rounded-3xl text-sm text-[var(--ui-text)] focus:ring-[var(--ui-accent)] focus:border-[var(--ui-accent)] transition-all" placeholder="Escriba notas internas sobre el caso..."></textarea>
                            </div>

                            <div v-if="form.status === 'resolved'" class="space-y-2 animate-fade-in">
                                <label class="text-[10px] font-black text-emerald-600 uppercase tracking-widest ml-1">Detalles de la Resolución (Visible para el informante)</label>
                                <textarea v-model="form.resolution_details" rows="4" class="w-full bg-emerald-500/5 border-emerald-500/20 rounded-3xl text-sm text-emerald-700 dark:text-emerald-300 focus:ring-emerald-500 focus:border-emerald-500 transition-all" placeholder="Explique qué acciones se tomaron para resolver el caso..."></textarea>
                            </div>

                            <div class="flex justify-end pt-4">
                                <button type="submit" 
                                        :disabled="form.processing"
                                        class="px-8 py-4 rounded-2xl bg-gradient-to-r from-[var(--ui-accent)] to-brand-600 text-xs font-black text-white uppercase tracking-widest shadow-lg shadow-[var(--ui-accent)]/20 hover:scale-105 active:scale-95 transition-all disabled:opacity-50">
                                    <span v-if="form.processing">Guardando...</span>
                                    <span v-else>Actualizar Denuncia</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Sidebar Info -->
                <div class="space-y-6">
                    <!-- Informant Card -->
                    <div class="bg-[var(--ui-surface-soft)] rounded-[2.5rem] p-6 border border-[var(--ui-border)] shadow-lg">
                        <h4 class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-[0.2em] mb-6 border-b border-[var(--ui-border)] pb-4">Informante</h4>
                        
                        <div v-if="complaint.is_anonymous" class="flex flex-col items-center py-4">
                            <div class="w-20 h-20 rounded-full bg-slate-500/10 flex items-center justify-center text-slate-400 mb-4 border-2 border-slate-500/20">
                                <FontAwesomeIcon icon="user-secret" class="text-3xl" />
                            </div>
                            <span class="text-xs font-black text-slate-500 uppercase tracking-widest">Caso Anónimo</span>
                            <p class="text-[10px] font-medium text-slate-400 text-center mt-2 leading-relaxed italic">
                                "El informante ha solicitado mantener su identidad en secreto según el protocolo NOM-035."
                            </p>
                        </div>
                        
                        <div v-else class="space-y-4">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl bg-[var(--ui-accent)]/10 flex items-center justify-center text-[var(--ui-accent)] border border-[var(--ui-accent)]/20">
                                    <FontAwesomeIcon icon="user" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest leading-none mb-1">Nombre</p>
                                    <p class="text-xs font-bold text-[var(--ui-text)] truncate">{{ complaint.reporter_name }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl bg-blue-500/10 flex items-center justify-center text-blue-500 border border-blue-500/20">
                                    <FontAwesomeIcon icon="envelope" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest leading-none mb-1">Email</p>
                                    <p class="text-xs font-bold text-[var(--ui-text)] truncate">{{ complaint.reporter_email }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Meta Info Card -->
                    <div class="bg-[var(--ui-surface-soft)] rounded-[2.5rem] p-6 border border-[var(--ui-border)] shadow-lg">
                        <h4 class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-[0.2em] mb-6 border-b border-[var(--ui-border)] pb-4">Detalles Técnicos</h4>
                        
                        <div class="space-y-6">
                            <div>
                                <p class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest mb-2">Tipo de Incidente</p>
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border" :class="getTypeClass(complaint.type)">
                                    {{ complaint.type }}
                                </span>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest mb-1">Fecha del Incidente</p>
                                <p class="text-xs font-bold text-[var(--ui-text)]">{{ complaint.incident_date ? formatDate(complaint.incident_date) : 'No especificada' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest mb-1">Evidencia</p>
                                <div v-if="complaint.evidence_paths?.length" class="space-y-2 mt-2">
                                    <a v-for="(path, idx) in complaint.evidence_paths" :key="idx" :href="path" target="_blank" 
                                       class="flex items-center gap-2 p-2 rounded-xl bg-[var(--ui-surface)] border border-[var(--ui-border)] text-[10px] font-bold text-[var(--ui-accent)] hover:bg-[var(--ui-accent)]/5 transition-colors">
                                        <FontAwesomeIcon icon="paperclip" />
                                        Adjunto {{ idx + 1 }}
                                    </a>
                                </div>
                                <p v-else class="text-[10px] font-bold text-slate-400 italic">No se adjuntó evidencia</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import dayjs from 'dayjs';
import 'dayjs/locale/es';

dayjs.locale('es');

const props = defineProps({
    complaint: Object
});

const form = useForm({
    status: props.complaint.status,
    admin_notes: props.complaint.admin_notes || '',
    resolution_details: props.complaint.resolution_details || ''
});

const updateComplaint = () => {
    form.put(route('nom035.complaints.update', props.complaint.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Optional: Show toast
        }
    });
};

const formatDate = (date) => {
    if (!date) return '';
    return dayjs(date).format('DD [de] MMMM, YYYY');
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
