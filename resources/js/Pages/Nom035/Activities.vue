<script setup>
import { ref } from 'vue'
import { router, useForm, Head, Link } from '@inertiajs/vue3'
import Swal from '@/Utils/Swal'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

const notyf = new Notyf({ duration: 4000 })

const props = defineProps({
    activities: Array
})

const showForm = ref(false)

const form = useForm({
    type: 'capacitación',
    title: '',
    description: '',
    activity_date: '',
    participants_count: '',
    evidence_file: null,
    status: 'completed'
})

const submit = () => {
    form.post(route('nom035.activities.store'), {
        forceFormData: true,
        onSuccess: () => {
            showForm.value = false
            form.reset()
            notyf.success('Actividad registrada correctamente')
        }
    })
}

const deleteActivity = async (id) => {
    const result = await Swal.fire({
        title: 'Eliminar registro',
        text: '¿Estás seguro de eliminar este registro de cumplimiento?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
    });

    if (result.isConfirmed) {
        router.delete(route('nom035.activities.destroy', id), {
            onSuccess: () => notyf.success('Actividad eliminada')
        })
    }
}

const getTypeLabel = (type) => {
    const labels = {
        'capacitación': 'Capacitación',
        'medida_control': 'Medida de Control',
        'evento_bienestar': 'Bienestar/Clima',
        'difusion': 'Difusión/Política'
    }
    return labels[type] || type
}

const getTypeColor = (type) => {
    const colors = {
        'capacitación': 'bg-blue-500/10 text-blue-500 border-blue-500/20',
        'medida_control': 'bg-rose-500/10 text-rose-500 border-rose-500/20',
        'evento_bienestar': 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20',
        'difusion': 'bg-purple-500/10 text-purple-500 border-purple-500/20'
    }
    return colors[type] || 'bg-slate-500/10 text-slate-500 border-slate-500/20'
}
</script>

<template>
    <AppLayout title="NOM-035 - Matriz de Acciones">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-black text-[var(--ui-text-main)] uppercase tracking-tight">
                        Matriz de Acciones y Control
                    </h2>
                    <p class="text-sm text-[var(--ui-text-soft)] mt-1">Evidencia de cumplimiento, capacitación y medidas preventivas.</p>
                </div>
                <button 
                    @click="showForm = !showForm"
                    class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-lg shadow-indigo-500/20 transition-all gap-2"
                >
                    <font-awesome-icon :icon="showForm ? 'times' : 'plus'" />
                    {{ showForm ? 'Cancelar' : 'Nueva Acción' }}
                </button>
            </div>
        </template>

        <div class="py-12 px-6 max-w-7xl mx-auto space-y-8">
            
            <!-- Formulario Nueva Acción -->
            <div v-if="showForm" class="bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] rounded-[2.5rem] shadow-xl overflow-hidden animate-in slide-in-from-top-4 duration-300">
                <div class="p-8 space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest ml-1">Tipo de Acción</label>
                            <select v-model="form.type" class="w-full bg-[var(--ui-surface)] border-[var(--ui-border)] rounded-2xl px-5 py-4 text-sm focus:ring-indigo-500/30 transition-all">
                                <option value="capacitación">Capacitación / Sensibilización</option>
                                <option value="medida_control">Medida de Control (Acción Correctiva)</option>
                                <option value="evento_bienestar">Evento de Bienestar / Clima</option>
                                <option value="difusion">Difusión de la Política / Información</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest ml-1">Título de la Actividad</label>
                            <input v-model="form.title" type="text" placeholder="Ej. Curso de Manejo de Estrés para Técnicos" class="w-full bg-[var(--ui-surface)] border-[var(--ui-border)] rounded-2xl px-5 py-4 text-sm focus:ring-indigo-500/30 transition-all" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest ml-1">Descripción y Objetivos</label>
                        <textarea v-model="form.description" rows="3" placeholder="Describe brevemente lo que se realizó y cómo impacta en el cumplimiento de la NOM-035..." class="w-full bg-[var(--ui-surface)] border-[var(--ui-border)] rounded-2xl px-5 py-4 text-sm focus:ring-indigo-500/30 transition-all"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest ml-1">Fecha</label>
                            <input v-model="form.activity_date" type="date" class="w-full bg-[var(--ui-surface)] border-[var(--ui-border)] rounded-2xl px-5 py-4 text-sm focus:ring-indigo-500/30 transition-all" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest ml-1">N. Participantes (Opcional)</label>
                            <input v-model="form.participants_count" type="text" placeholder="Ej. 15 colaboradores" class="w-full bg-[var(--ui-surface)] border-[var(--ui-border)] rounded-2xl px-5 py-4 text-sm focus:ring-indigo-500/30 transition-all" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest ml-1">Evidencia (PDF/Imagen)</label>
                            <input type="file" @input="form.evidence_file = $event.target.files[0]" class="w-full text-xs text-[var(--ui-text-soft)] file:mr-4 file:py-3 file:px-6 file:rounded-2xl file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-all" />
                        </div>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button 
                            @click="submit"
                            :disabled="form.processing"
                            class="px-10 py-4 bg-indigo-600 text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl shadow-indigo-500/20 hover:bg-indigo-700 transition-all disabled:opacity-50"
                        >
                            {{ form.processing ? 'Registrando...' : 'Registrar Acción en Matriz' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Lista de Actividades -->
            <div class="grid grid-cols-1 gap-6">
                <div v-for="activity in activities" :key="activity.id" class="bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] rounded-[2rem] p-6 hover:shadow-xl hover:shadow-indigo-500/5 transition-all group">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="flex items-start gap-5">
                            <div :class="['h-14 w-14 rounded-2xl flex items-center justify-center text-xl border transition-all', getTypeColor(activity.type)]">
                                <font-awesome-icon :icon="activity.type === 'capacitación' ? 'graduation-cap' : (activity.type === 'medida_control' ? 'tools' : 'heart')" />
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-3">
                                    <h3 class="font-bold text-[var(--ui-text-main)] text-lg">{{ activity.title }}</h3>
                                    <span :class="['px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest border', getTypeColor(activity.type)]">
                                        {{ getTypeLabel(activity.type) }}
                                    </span>
                                </div>
                                <p class="text-xs text-[var(--ui-text-soft)] leading-relaxed max-w-2xl">{{ activity.description }}</p>
                                <div class="flex items-center gap-4 pt-2">
                                    <div class="flex items-center gap-2 text-[10px] font-bold text-[var(--ui-text-soft)] uppercase tracking-wider">
                                        <font-awesome-icon icon="calendar" class="opacity-50" />
                                        {{ activity.activity_date }}
                                    </div>
                                    <div v-if="activity.participants_count" class="flex items-center gap-2 text-[10px] font-bold text-[var(--ui-text-soft)] uppercase tracking-wider">
                                        <font-awesome-icon icon="users" class="opacity-50" />
                                        {{ activity.participants_count }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <a v-if="activity.evidence_file" :href="'/storage/' + activity.evidence_file" target="_blank" class="px-5 py-2.5 bg-white border border-slate-200 text-slate-700 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition-all flex items-center gap-2">
                                <font-awesome-icon icon="paperclip" />
                                Evidencia
                            </a>
                            <button @click="deleteActivity(activity.id)" class="h-10 w-10 bg-rose-50 text-rose-500 rounded-xl hover:bg-rose-500 hover:text-white transition-all">
                                <font-awesome-icon icon="trash" />
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="activities.length === 0" class="py-32 text-center bg-[var(--ui-surface-soft)] rounded-[3rem] border border-dashed border-[var(--ui-border)]">
                    <div class="max-w-xs mx-auto space-y-4 opacity-30">
                        <font-awesome-icon icon="folder-open" class="text-6xl" />
                        <h3 class="text-sm font-black uppercase tracking-widest">Matriz Vacía</h3>
                        <p class="text-xs font-bold uppercase leading-relaxed">No has registrado ninguna medida de control o capacitación aún.</p>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
