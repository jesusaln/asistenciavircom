<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Head, Link } from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    misTareas: Array,
    tareasDisponibles: Array,
    stats: Object,
});

const formTomar = useForm({});

import Swal from '@/Utils/Swal'

const tomarTarea = async (id) => {
    const { isConfirmed } = await Swal.fire({ title: '¿Asignarte tarea?', text: '¿Deseas asignarte esta tarea?', icon: 'question', showCancelButton: true, confirmButtonText: 'Asignarme', cancelButtonText: 'Cancelar' })
    if (isConfirmed) {
        formTomar.post(route('admin.mantenimientos.tecnico.tomar', id));
    }
};

// --- Modal Iniciar ---
const tareaAIniciar = ref(null);
const modalIniciarAbierto = ref(false);
const formIniciar = useForm({
    notas_iniciales: '',
    fotos_antes: [],
});

const abrirIniciar = (tarea) => {
    tareaAIniciar.value = tarea;
    formIniciar.reset();
    modalIniciarAbierto.value = true;
};

const cerrarIniciar = () => {
    modalIniciarAbierto.value = false;
    tareaAIniciar.value = null;
    formIniciar.reset();
};

const enviarIniciar = () => {
    formIniciar.post(route('admin.mantenimientos.tecnico.iniciar', tareaAIniciar.value.id), {
        onSuccess: () => {
             cerrarIniciar();
        }
    });
};

const handleFotosAntes = (event) => {
    formIniciar.fotos_antes = Array.from(event.target.files);
};

// --- Modal Completar ---
const tareaACompletar = ref(null);
const modalCompletarAbierto = ref(false);
const formCompletar = useForm({
    resultado: 'exitoso',
    notas_tecnico: '',
    numero_serie: '',
    fotos_despues: [],
});

const abrirCompletar = (tarea) => {
    tareaACompletar.value = tarea;
    formCompletar.reset();
    modalCompletarAbierto.value = true;
};

const cerrarCompletar = () => {
    modalCompletarAbierto.value = false;
    tareaACompletar.value = null;
    formCompletar.reset();
}

const enviarCompletar = () => {
    formCompletar.post(route('admin.mantenimientos.tecnico.completar', tareaACompletar.value.id), {
        onSuccess: () => {
             cerrarCompletar();
        }
    });
};

const handleFotosDespues = (event) => {
    formCompletar.fotos_despues = Array.from(event.target.files);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-MX', { 
        weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit'
    });
};

const getPrioridadBadge = (prioridad) => {
    const styles = {
        alta: 'bg-brand-500/10 text-rose-400 border-rose-500/20',
        media: 'bg-brand-500/10 text-brand-400 border-brand-500/20',
        baja: 'bg-brand-500/10 text-blue-400 border-blue-500/20',
    };
    return styles[prioridad] || 'bg-slate-500/10 text-slate-400 border-slate-500/20';
};
</script>

<template>
    <AppLayout title="Centro de Trabajo Técnico">
        <Head title="Mantenimientos - Técnico" />

        <div class="min-h-screen bg-[var(--ui-surface)] dark:bg-[#0F172A] text-slate-500 dark:text-slate-200 pb-12 transition-colors">
            <!-- Hero Header Section -->
            <div class="relative overflow-hidden bg-white dark:bg-black/50 border-b border-slate-200 dark:border-slate-800 pt-8 pb-12 mb-8">
                <div class="absolute top-0 right-0 -translate-y-1/2 translate-x-1/2 w-96 h-96 bg-blue-600/10 blur-[100px] rounded-full"></div>
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-2 py-0.5 bg-brand-500/20 text-blue-600 dark:text-blue-400 text-[10px] font-black uppercase tracking-wide rounded-xl border border-blue-500/20">Portal Técnico</span>
                                <span class="text-slate-400 dark:text-slate-500">•</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Gestión de Campo</span>
                            </div>
                            <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter">Mi Centro de <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-500 to-indigo-500 dark:from-blue-400 dark:to-indigo-400">Trabajo</span></h1>
                            <p class="text-slate-500 dark:text-slate-400 mt-2 font-medium">Visualiza y gestiona tus mantenimientos programados hoy.</p>
                        </div>

                        <!-- Quick Stats Gird -->
                        <div class="grid grid-cols-2 gap-3 w-full md:w-auto">
                            <div class="bg-white dark:bg-slate-800/50 backdrop-blur-xl border border-slate-200 dark:border-slate-700/50 p-4 rounded-2xl shadow-xl min-w-[140px]">
                                <div class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Pendientes</div>
                                <div class="text-2xl font-black text-blue-600 dark:text-blue-400">{{ stats.pendientes }}</div>
                            </div>
                            <div class="bg-white dark:bg-slate-800/50 backdrop-blur-xl border border-slate-200 dark:border-slate-700/50 p-4 rounded-2xl shadow-xl min-w-[140px]">
                                <div class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Completadas Hoy</div>
                                <div class="text-2xl font-black text-emerald-600 dark:text-slate-400">{{ stats.completadas_hoy }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Dashboard Principal: Tareas Asignadas -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="flex items-center justify-between mb-2">
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span class="text-blue-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </span>
                                Tareas en mi Agenda
                            </h2>
                            <span class="text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wide">{{ misTareas.length }} asignadas</span>
                        </div>

                        <div v-if="misTareas.length === 0" class="bg-white dark:bg-slate-800/30 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-[2rem] p-12 text-center group transition-all hover:bg-slate-50 dark:hover:bg-slate-700/50 hover:border-brand-500/30">
                            <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-105 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-400 dark:text-slate-500 group-hover:text-blue-500 dark:group-hover:text-blue-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-slate-700 dark:text-slate-200">No hay trabajo pendiente</h3>
                            <p class="text-slate-500 dark:text-slate-400 mt-2 max-w-xs mx-auto">Selecciona una tarea de la bolsa de trabajo disponible para comenzar.</p>
                        </div>

                        <!-- Card de Tarea Premium -->
                        <div v-for="tarea in misTareas" :key="tarea.id" 
                            class="group relative bg-white dark:bg-slate-800/50 backdrop-blur-xl border border-slate-200 dark:border-slate-700/50 rounded-[2rem] overflow-hidden hover:border-brand-500/50 transition-all duration-200 hover:shadow-2xl hover:shadow-blue-900/10 dark:hover:shadow-blue-900/20">
                            
                            <!-- Barra de Estado Lateral -->
                            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-brand-500 shadow-[0_0_15px_rgba(59,130,246,0.5)]"></div>

                            <div class="p-8">
                                <div class="flex flex-col sm:flex-row justify-between items-start gap-4 mb-6">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-3">
                                            <span :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border', getPrioridadBadge(tarea.mantenimiento.prioridad)]">
                                                {{ tarea.mantenimiento.prioridad || 'Media' }}
                                            </span>
                                            <span class="text-[11px] font-mono text-slate-400 dark:text-slate-500 font-bold tracking-wide">MTTO-ID-{{ String(tarea.id).padStart(5, '0') }}</span>
                                        </div>
                                        <h4 class="text-2xl font-black text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors leading-tight mb-1">{{ tarea.mantenimiento.nombre }}</h4>
                                        <div class="flex items-center gap-2 text-blue-600 dark:text-blue-400 font-bold">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <span class="text-sm truncate">{{ tarea.mantenimiento.poliza?.cliente?.nombre_razon_social }}</span>
                                        </div>
                                    </div>

                                    <div class="bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 p-4 rounded-2xl min-w-[180px] text-center sm:text-right">
                                        <div class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Visita Programada</div>
                                        <div :class="['text-sm font-black', new Date(tarea.fecha_programada) < new Date() ? 'text-rose-500 dark:text-rose-400' : 'text-slate-700 dark:text-slate-200']">
                                            {{ formatDate(tarea.fecha_programada) }}
                                        </div>
                                        <div v-if="new Date(tarea.fecha_programada) < new Date()" class="text-[9px] text-rose-500 font-bold uppercase mt-1 animate-pulse italic">¡Tarea Atrasada!</div>
                                    </div>
                                </div>

                                <!-- Detalles Técnicos Area -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-5 border border-slate-100 dark:border-slate-800 group-hover:border-brand-500 dark:group-hover:border-brand-500 transition-colors">
                                        <div class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-3 flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            Ubicación de Servicio
                                        </div>
                                        <p class="text-sm text-slate-700 dark:text-slate-200 font-medium leading-relaxed">
                                            {{ tarea.mantenimiento.poliza?.direccion?.calle || 'Calle no especificada' }} 
                                            {{ tarea.mantenimiento.poliza?.direccion?.numero_exterior }},
                                            {{ tarea.mantenimiento.poliza?.direccion?.colonia }}
                                        </p>
                                    </div>
                                    
                                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-5 border border-slate-100 dark:border-slate-800 group-hover:border-brand-500 dark:group-hover:border-brand-500 transition-colors">
                                        <div class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-3 flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Instrucciones del Trabajo
                                        </div>
                                        <p class="text-sm text-slate-500 dark:text-slate-400 italic">
                                            {{ tarea.mantenimiento.descripcion || 'Realizar mantenimiento de rutina según guía de póliza.' }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Acciones en la Card -->
                                <div class="flex items-center justify-between border-t border-slate-200 dark:border-slate-800 pt-6">
                                    <div class="flex gap-4">
                                        <div v-if="tarea.estado === 'en_proceso'" class="flex items-center gap-2 text-amber-500 text-xs font-bold">
                                            <span class="w-2 h-2 bg-amber-500 rounded-full animate-ping"></span>
                                            Servicio En Proceso
                                        </div>
                                        <div v-if="tarea.notas_reprogramacion" class="flex items-center gap-2 text-brand-500 text-xs font-bold">
                                            <span class="w-2 h-2 bg-brand-500 rounded-full animate-ping"></span>
                                            Reprogramado: {{ tarea.notas_reprogramacion }}
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <button 
                                            v-if="tarea.estado === 'pendiente' || tarea.estado === 'reprogramado'"
                                            @click="abrirIniciar(tarea)"
                                            class="px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white rounded-xl font-black text-xs uppercase tracking-wide shadow-xl shadow-blue-900/10 dark:shadow-blue-900/20 transition-all hover:shadow-xl"
                                        >
                                            Iniciar Servicio
                                        </button>
                                        <button 
                                            v-if="tarea.estado === 'en_proceso'"
                                            @click="abrirCompletar(tarea)"
                                            class="px-8 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-xl font-black text-xs uppercase tracking-wide shadow-xl shadow-emerald-900/10 dark:shadow-emerald-900/20 transition-all hover:shadow-xl"
                                        >
                                            Completar Servicio
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar: Bolsa de Trabajo / Disponibles -->
                    <div class="lg:col-span-1">
                        <div class="sticky top-6 space-y-6">
                            <div class="flex items-center justify-between mb-4 px-2">
                                <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <span class="text-brand-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </span>
                                    Bolsa de Trabajo
                                </h2>
                                <span class="text-[10px] font-black text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded-xl border border-slate-200 dark:border-slate-700 uppercase">{{ tareasDisponibles.length }} Libres</span>
                            </div>

                            <div v-if="tareasDisponibles.length === 0" class="bg-white dark:bg-slate-800/30 border border-slate-200 dark:border-slate-700/50 rounded-3xl p-8 text-center">
                                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">No hay mantenimientos libres por ahora.</p>
                            </div>

                            <div class="space-y-6">
                                <div v-for="tarea in tareasDisponibles" :key="tarea.id" 
                                    class="bg-white dark:bg-slate-800/30 backdrop-blur-md border border-slate-200 dark:border-slate-700/50 rounded-3xl p-6 hover:border-brand-500/30 transition-all group shadow-md dark:shadow-xl">
                                    <div class="flex items-center gap-2 mb-4">
                                        <div class="w-10 h-10 rounded-2xl bg-[var(--ui-surface)] flex items-center justify-center text-blue-600 dark:text-blue-400 border border-slate-200 dark:border-slate-800 group-hover:scale-105 transition-transform">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h5 class="font-bold text-slate-900 dark:text-white text-sm line-clamp-1 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{{ tarea.mantenimiento.nombre }}</h5>
                                            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider line-clamp-1">{{ tarea.mantenimiento.poliza?.cliente?.nombre_razon_social }}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-6">
                                        <div class="flex items-center justify-between bg-[var(--ui-surface)] dark:bg-black/50 rounded-xl p-3 border border-slate-100 dark:border-slate-800/50">
                                            <div class="text-[9px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide">Fecha Sugerida</div>
                                            <div class="text-xs font-bold text-slate-700 dark:text-slate-200">📅 {{ formatDate(tarea.fecha_programada).split(',')[1] }}</div>
                                        </div>

                                        <button @click="tomarTarea(tarea.id)" 
                                            class="w-full py-3 bg-sky-50 dark:bg-sky-900/20 dark:bg-blue-600/10 hover:bg-blue-600 text-blue-600 dark:text-blue-400 hover:text-white border border-sky-200 dark:border-sky-800/30 dark:border-blue-500/30 rounded-2xl font-black text-[10px] uppercase tracking-[0.1em] transition-all flex items-center justify-center gap-2 hover:shadow-[0_0_20px_rgba(59,130,246,0.2)]">
                                            <span>Tomar este Trabajo</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div v-if="tareasDisponibles.length > 5" class="py-4 text-center">
                                <Link href="#" class="text-[10px] font-black text-slate-500 dark:text-slate-400 hover:text-brand-600 dark:hover:text-blue-400 uppercase tracking-[0.2em] transition-colors">Cargar más sugerencias...</Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Professional Modal: Iniciar Mantenimiento -->
        <DialogModal :show="modalIniciarAbierto" @close="cerrarIniciar" maxWidth="2xl">
            <template #title>
                <div class="p-6 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-800">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-brand-500/10 rounded-2xl flex items-center justify-center text-blue-600 dark:text-blue-400 border border-blue-500/20 shadow-xl shadow-blue-900/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-black text-slate-900 dark:text-white">Iniciar Servicio</h3>
                            <p class="text-slate-500 dark:text-slate-400 text-xs font-medium uppercase tracking-wide mt-0.5">Registro de Estado Inicial (Antes)</p>
                        </div>
                    </div>
                </div>
            </template>

            <template #content>
                <div class="p-8 space-y-6 bg-white dark:bg-slate-800">
                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-5 border border-slate-200 dark:border-slate-700/50">
                        <h4 class="text-blue-600 dark:text-blue-400 font-black text-sm mb-1">{{ tareaAIniciar?.mantenimiento.nombre }}</h4>
                        <div class="flex items-center gap-2 text-slate-500 dark:text-slate-400 text-xs font-bold uppercase">
                            <span>Cliente:</span>
                            <span class="text-slate-700 dark:text-slate-200">{{ tareaAIniciar?.mantenimiento.poliza?.cliente?.nombre_razon_social }}</span>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <label class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide text-left mb-2 block font-bold">Condiciones Iniciales del Equipo (Notas antes)</label>
                            <textarea v-model="formIniciar.notas_iniciales" rows="4" 
                                class="w-full bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10 rounded-2xl text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-600 text-sm font-medium transition-all"
                                placeholder="Describe el estado en que encuentras el equipo (ej. suciedad acumulada, ruidos extraños, etc.)..."></textarea>
                            <InputError :message="formIniciar.errors.notas_iniciales" class="mt-2" />
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide text-left mb-2 block font-bold">Evidencia Fotográfica del "Antes"</label>
                            <input type="file" multiple @change="handleFotosAntes" accept="image/*"
                                class="w-full text-sm text-slate-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-55 file:text-blue-700 dark:file:bg-slate-700 dark:file:text-slate-200 hover:file:bg-blue-100" />
                            <InputError :message="formIniciar.errors.fotos_antes" class="mt-2" />
                        </div>
                    </div>
                </div>
            </template>

            <template #footer>
                <div class="p-6 bg-[var(--ui-surface)] border-t border-slate-100 dark:border-slate-800 flex justify-end gap-4 shadow-none border-0">
                    <button @click="cerrarIniciar" class="px-6 py-3 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white font-black text-[10px] uppercase tracking-wide transition-colors">
                        Cancelar
                    </button>
                    <button 
                        @click="enviarIniciar" 
                        :disabled="formIniciar.processing"
                        class="px-8 py-3 bg-blue-600 hover:bg-slate-500 text-white rounded-xl font-black text-[10px] uppercase tracking-wide shadow-xl shadow-blue-900/30 transition-all disabled:opacity-50"
                    >
                        {{ formIniciar.processing ? 'Iniciando...' : 'Iniciar y Registrar' }}
                    </button>
                </div>
            </template>
        </DialogModal>

        <!-- Professional Modal: Completar Mantenimiento -->
        <DialogModal :show="modalCompletarAbierto" @close="cerrarCompletar" maxWidth="2xl">
            <template #title>
                <div class="p-6 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-800">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-brand-500/10 rounded-2xl flex items-center justify-center text-emerald-600 dark:text-slate-400 border border-emerald-500/20 shadow-xl shadow-emerald-900/10 dark:shadow-emerald-900/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-black text-slate-900 dark:text-white">Reporte de Ejecución</h3>
                            <p class="text-slate-500 dark:text-slate-400 text-xs font-medium uppercase tracking-wide mt-0.5">Finalización de Tarea Técnica</p>
                        </div>
                    </div>
                </div>
            </template>

            <template #content>
                <div class="p-8 space-y-6 bg-white dark:bg-slate-800">
                    <!-- Resumen breve de que se esta completando -->
                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-5 border border-slate-200 dark:border-slate-700/50">
                        <h4 class="text-blue-600 dark:text-blue-400 font-black text-sm mb-1">{{ tareaACompletar?.mantenimiento.nombre }}</h4>
                        <div class="flex items-center gap-2 text-slate-500 dark:text-slate-400 text-xs font-bold uppercase">
                            <span>Cliente:</span>
                            <span class="text-slate-700 dark:text-slate-200">{{ tareaACompletar?.mantenimiento.poliza?.cliente?.nombre_razon_social }}</span>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <label class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide">Nivel de Resultado</label>
                                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-800 px-2 rounded-xl">Requerido *</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <button type="button" @click="formCompletar.resultado = 'exitoso'" 
                                    :class="['p-4 rounded-2xl border-2 transition-all text-center flex flex-col items-center gap-2', 
                                    formCompletar.resultado === 'exitoso' ? 'bg-brand-500/10 border-emerald-500 text-emerald-600 dark:text-slate-400' : 'bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-400 dark:text-slate-500 hover:border-brand-500 dark:hover:border-brand-500']">
                                    <span class="text-xl">✅</span>
                                    <span class="text-[10px] font-black uppercase tracking-wider">Exitoso</span>
                                </button>
                                <button type="button" @click="formCompletar.resultado = 'con_observaciones'" 
                                    :class="['p-4 rounded-2xl border-2 transition-all text-center flex flex-col items-center gap-2', 
                                    formCompletar.resultado === 'con_observaciones' ? 'bg-brand-500/10 border-brand-500 text-brand-600 dark:text-amber-400' : 'bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-400 dark:text-slate-500 hover:border-brand-500 dark:hover:border-brand-500']">
                                    <span class="text-xl">⚠️</span>
                                    <span class="text-[10px] font-black uppercase tracking-wider">Observación</span>
                                </button>
                                <button type="button" @click="formCompletar.resultado = 'fallido'" 
                                    :class="['p-4 rounded-2xl border-2 transition-all text-center flex flex-col items-center gap-2', 
                                    formCompletar.resultado === 'fallido' ? 'bg-brand-500/10 border-rose-500 text-rose-600 dark:text-rose-400' : 'bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-400 dark:text-slate-500 hover:border-brand-500 dark:hover:border-brand-500']">
                                    <span class="text-xl">🔴</span>
                                    <span class="text-[10px] font-black uppercase tracking-wider">Falla/Pend.</span>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide text-left mb-2 block font-bold">Número de Serie del Equipo</label>
                            <input v-model="formCompletar.numero_serie" type="text"
                                class="w-full bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10 rounded-2xl text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-600 text-sm font-medium transition-all"
                                placeholder="Ingresa el número de serie de la placa del equipo..." />
                            <InputError :message="formCompletar.errors.numero_serie" class="mt-2" />
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <label class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide text-left mt-4 mb-2">Resumen Operativo y Bitácora (Después)</label>
                            </div>
                            <textarea v-model="formCompletar.notas_tecnico" rows="5" 
                                class="w-full bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10 rounded-2xl text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-600 text-sm font-medium transition-all"
                                placeholder="Describe el trabajo realizado, refacciones usadas, voltajes medidos o piezas por cambiar..."></textarea>
                            <InputError :message="formCompletar.errors.notas_tecnico" class="mt-2" />
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide text-left mb-2 block font-bold">Evidencia Fotográfica del "Después"</label>
                            <input type="file" multiple @change="handleFotosDespues" accept="image/*"
                                class="w-full text-sm text-slate-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-55 file:text-blue-700 dark:file:bg-slate-700 dark:file:text-slate-200 hover:file:bg-blue-100" />
                            <InputError :message="formCompletar.errors.fotos_despues" class="mt-2" />
                        </div>
                    </div>
                </div>
            </template>

            <template #footer>
                <div class="p-6 bg-[var(--ui-surface)] border-t border-slate-100 dark:border-slate-800 flex justify-end gap-4 shadow-none border-0">
                    <button @click="cerrarCompletar" class="px-6 py-3 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white font-black text-[10px] uppercase tracking-wide transition-colors">
                        Cancelar
                    </button>
                    <button 
                        @click="enviarCompletar" 
                        :disabled="formCompletar.processing"
                        class="px-8 py-3 bg-blue-600 hover:bg-slate-500 text-white rounded-xl font-black text-[10px] uppercase tracking-wide shadow-xl shadow-blue-900/30 transition-all disabled:opacity-50"
                    >
                        {{ formCompletar.processing ? 'Guardando...' : 'Finalizar y Guardar' }}
                    </button>
                </div>
            </template>
        </DialogModal>
    </AppLayout>
</template>

<style scoped>
/* Transiciones suaves para hover effects */
.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 300ms;
}

/* Scrollbar personalizado para zonas de scroll */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}
.overflow-y-auto::-webkit-scrollbar-track {
    background: #0f172a;
}
.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #1e293b;
    border-radius: 10px;
}
.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #334155;
}

/* Efectos de vidrio premium */
.backdrop-blur-xl {
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
}
</style>
