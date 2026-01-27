<script setup>
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

const tomarTarea = (id) => {
    if (confirm('¿Deseas asignarte esta tarea?')) {
        formTomar.post(route('admin.mantenimientos.tecnico.tomar', id));
    }
};

// --- Modal Completar ---
const tareaACompletar = ref(null);
const modalCompletarAbierto = ref(false);
const formCompletar = useForm({
    resultado: 'exitoso',
    notas_tecnico: '',
    checklist: [],
});

const abrirCompletar = (tarea) => {
    tareaACompletar.value = tarea;
    formCompletar.reset();
    formCompletar.resultado = 'exitoso';
    formCompletar.notas_tecnico = '';
    // Inicializar checklist desde la tarea, o vacío si no tiene
    formCompletar.checklist = tarea.checklist ? JSON.parse(JSON.stringify(tarea.checklist)) : [];
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

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-MX', { 
        weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit'
    });
};

const getPrioridadBadge = (prioridad) => {
    const styles = {
        alta: 'bg-red-500/10 text-red-400 border-red-500/20',
        media: 'bg-amber-500/10 text-amber-400 border-amber-500/20',
        baja: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
    };
    return styles[prioridad] || 'bg-slate-500/10 text-slate-400 border-slate-500/20';
};
</script>

<template>
    <AppLayout title="Centro de Trabajo Técnico">
        <Head title="Mantenimientos - Técnico" />

        <div class="min-h-screen bg-[#0F172A] text-slate-300 pb-12">
            <!-- Hero Header Section -->
            <div class="relative overflow-hidden bg-slate-900/50 border-b border-slate-800 pt-8 pb-12 mb-8">
                <div class="absolute top-0 right-0 -translate-y-1/2 translate-x-1/2 w-96 h-96 bg-blue-600/10 blur-[100px] rounded-full"></div>
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-2 py-0.5 bg-blue-500/20 text-blue-400 text-[10px] font-black uppercase tracking-widest rounded-md border border-blue-500/20">Portal Técnico</span>
                                <span class="text-slate-500">•</span>
                                <span class="text-xs text-slate-400 font-medium">Gestión de Campo</span>
                            </div>
                            <h1 class="text-4xl font-black text-white tracking-tighter">Mi Centro de <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-400">Trabajo</span></h1>
                            <p class="text-slate-400 mt-2 font-medium">Visualiza y gestiona tus mantenimientos programados hoy.</p>
                        </div>

                        <!-- Quick Stats Gird -->
                        <div class="grid grid-cols-2 gap-3 w-full md:w-auto">
                            <div class="bg-slate-800/40 backdrop-blur-xl border border-slate-700/50 p-4 rounded-2xl shadow-xl min-w-[140px]">
                                <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Pendientes</div>
                                <div class="text-3xl font-black text-blue-400">{{ stats.pendientes }}</div>
                            </div>
                            <div class="bg-slate-800/40 backdrop-blur-xl border border-slate-700/50 p-4 rounded-2xl shadow-xl min-w-[140px]">
                                <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Completadas Hoy</div>
                                <div class="text-3xl font-black text-emerald-400">{{ stats.completadas_hoy }}</div>
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
                            <h2 class="text-xl font-bold text-white flex items-center gap-2">
                                <span class="text-blue-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </span>
                                Tareas en mi Agenda
                            </h2>
                            <span class="text-xs text-slate-500 font-bold uppercase tracking-widest">{{ misTareas.length }} asignadas</span>
                        </div>

                        <div v-if="misTareas.length === 0" class="bg-slate-800/30 border-2 border-dashed border-slate-800 rounded-[2rem] p-12 text-center group transition-all hover:bg-slate-800/50 hover:border-blue-500/30">
                            <div class="w-20 h-20 bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-600 group-hover:text-blue-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-slate-300">No hay trabajo pendiente</h3>
                            <p class="text-slate-500 mt-2 max-w-xs mx-auto">Selecciona una tarea de la bolsa de trabajo disponible para comenzar.</p>
                        </div>

                        <!-- Card de Tarea Premium -->
                        <div v-for="tarea in misTareas" :key="tarea.id" 
                            class="group relative bg-slate-800/40 backdrop-blur-xl border border-slate-700/50 rounded-[2rem] overflow-hidden hover:border-blue-500/50 transition-all duration-300 hover:shadow-2xl hover:shadow-blue-900/20">
                            
                            <!-- Barra de Estado Lateral -->
                            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-blue-500 shadow-[0_0_15px_rgba(59,130,246,0.5)]"></div>

                            <div class="p-8">
                                <div class="flex flex-col sm:flex-row justify-between items-start gap-4 mb-6">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-3">
                                            <span :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border', getPrioridadBadge(tarea.mantenimiento.prioridad)]">
                                                {{ tarea.mantenimiento.prioridad || 'Media' }}
                                            </span>
                                            <span class="text-[11px] font-mono text-slate-500 font-bold tracking-widest">MTTO-ID-{{ String(tarea.id).padStart(5, '0') }}</span>
                                        </div>
                                        <h4 class="text-2xl font-black text-white group-hover:text-blue-400 transition-colors leading-tight mb-1">{{ tarea.mantenimiento.nombre }}</h4>
                                        <div class="flex items-center gap-2 text-blue-400 font-bold">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <span class="text-sm truncate">{{ tarea.mantenimiento.poliza?.cliente?.nombre_razon_social }}</span>
                                        </div>
                                    </div>

                                    <div class="bg-slate-900/80 border border-slate-700 p-4 rounded-2xl min-w-[180px] text-center sm:text-right">
                                        <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Visita Programada</div>
                                        <div :class="['text-sm font-black', new Date(tarea.fecha_programada) < new Date() ? 'text-rose-400' : 'text-slate-200']">
                                            {{ formatDate(tarea.fecha_programada) }}
                                        </div>
                                        <div v-if="new Date(tarea.fecha_programada) < new Date()" class="text-[9px] text-rose-500 font-bold uppercase mt-1 animate-pulse italic">¡Tarea Atrasada!</div>
                                    </div>
                                </div>

                                <!-- Detalles Técnicos Area -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                                    <div class="bg-slate-900/40 rounded-2xl p-5 border border-slate-800 group-hover:border-slate-700 transition-colors">
                                        <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            Ubicación de Servicio
                                        </div>
                                        
                                        <div v-if="tarea.mantenimiento.requiere_visita">
                                            <p class="text-sm text-slate-300 font-medium leading-relaxed">
                                                {{ tarea.mantenimiento.poliza?.direccion?.calle || 'Calle no especificada' }} 
                                                {{ tarea.mantenimiento.poliza?.direccion?.numero_exterior }},
                                                {{ tarea.mantenimiento.poliza?.direccion?.colonia }}
                                            </p>
                                        </div>
                                        <div v-else class="flex items-center gap-3">
                                            <div class="p-2 bg-indigo-500/10 rounded-lg text-indigo-400">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-sm text-indigo-300 font-bold">Servicio Remoto</p>
                                                <p class="text-xs text-slate-500">No se requiere visita en sitio</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-slate-900/40 rounded-2xl p-5 border border-slate-800 group-hover:border-slate-700 transition-colors">
                                        <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Instrucciones del Trabajo
                                        </div>
                                        <p class="text-sm text-slate-400 italic">
                                            {{ tarea.mantenimiento.descripcion || 'Realizar mantenimiento de rutina según guía de póliza.' }}
                                        </p>
                                        
                                        <div v-if="tarea.mantenimiento.guia_tecnica || tarea.mantenimiento.nombre.toLowerCase().includes('disco')" class="mt-4 pt-4 border-t border-slate-800/50">
                                            <component 
                                                :is="tarea.mantenimiento.guia_tecnica?.route_name ? 'a' : 'div'"
                                                :href="tarea.mantenimiento.guia_tecnica?.route_name ? route(tarea.mantenimiento.guia_tecnica.route_name) : (tarea.mantenimiento.nombre.toLowerCase().includes('disco') ? route('soporte.guias.discos') : '#')" 
                                                :target="tarea.mantenimiento.guia_tecnica?.route_name ? '_blank' : ''"
                                                class="flex items-center gap-2 text-xs font-bold text-amber-500 hover:text-amber-400 transition-colors group/link p-2 bg-amber-500/10 rounded-lg border border-amber-500/20 hover:border-amber-500/50 cursor-pointer"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 group-hover/link:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                                </svg>
                                                <span>{{ tarea.mantenimiento.guia_tecnica?.nombre || 'Guía Técnica Recomendada' }}</span>
                                                <svg v-if="tarea.mantenimiento.guia_tecnica?.route_name || tarea.mantenimiento.nombre.toLowerCase().includes('disco')" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-auto opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                            </component>
                                        </div>
                                    </div>
                                </div>

                                <!-- Acciones en la Card -->
                                <div class="flex items-center justify-between border-t border-slate-800 pt-6">
                                    <div class="flex gap-4">
                                        <div v-if="tarea.notas_reprogramacion" class="flex items-center gap-2 text-amber-400 text-xs font-bold">
                                            <span class="w-2 h-2 bg-amber-400 rounded-full animate-ping"></span>
                                            Reprogramado: {{ tarea.notas_reprogramacion }}
                                        </div>
                                    </div>
                                    <button 
                                        @click="abrirCompletar(tarea)"
                                        class="px-8 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-xl font-black text-xs uppercase tracking-widest shadow-xl shadow-emerald-900/20 transition-all hover:-translate-y-0.5"
                                    >
                                        Marcar como Completado
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar: Bolsa de Trabajo / Disponibles -->
                    <div class="lg:col-span-1">
                        <div class="sticky top-6 space-y-6">
                            <div class="flex items-center justify-between mb-4 px-2">
                                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                                    <span class="text-amber-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </span>
                                    Bolsa de Trabajo
                                </h2>
                                <span class="text-[10px] font-black text-slate-500 bg-slate-800 px-2 py-1 rounded border border-slate-700 uppercase">{{ tareasDisponibles.length }} Libres</span>
                            </div>

                            <div v-if="tareasDisponibles.length === 0" class="bg-slate-800/30 border border-slate-700/50 rounded-3xl p-8 text-center">
                                <p class="text-slate-500 text-sm font-medium">No hay mantenimientos libres por ahora.</p>
                            </div>

                            <div class="space-y-4">
                                <div v-for="tarea in tareasDisponibles" :key="tarea.id" 
                                    class="bg-slate-800/30 backdrop-blur-md border border-slate-700/50 rounded-3xl p-6 hover:border-blue-500/30 transition-all group shadow-lg">
                                    <div class="flex items-center gap-3 mb-4">
                                        <div class="w-10 h-10 rounded-2xl bg-slate-900 flex items-center justify-center text-blue-400 border border-slate-800 group-hover:scale-110 transition-transform">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h5 class="font-bold text-white text-sm line-clamp-1 group-hover:text-blue-400 transition-colors">{{ tarea.mantenimiento.nombre }}</h5>
                                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-tight line-clamp-1">{{ tarea.mantenimiento.poliza?.cliente?.nombre_razon_social }}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-4">
                                        <div class="flex items-center justify-between bg-slate-900/50 rounded-xl p-3 border border-slate-800/50">
                                            <div class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Fecha Sugerida</div>
                                            <div class="text-xs font-bold text-slate-300">📅 {{ formatDate(tarea.fecha_programada).split(',')[1] }}</div>
                                        </div>

                                        <button @click="tomarTarea(tarea.id)" 
                                            class="w-full py-3 bg-blue-600/10 hover:bg-blue-600 text-blue-400 hover:text-white border border-blue-500/30 rounded-2xl font-black text-[10px] uppercase tracking-[0.1em] transition-all flex items-center justify-center gap-2 group-hover:shadow-[0_0_20px_rgba(59,130,246,0.2)]">
                                            <span>Tomar este Trabajo</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div v-if="tareasDisponibles.length > 5" class="py-4 text-center">
                                <Link href="#" class="text-[10px] font-black text-slate-500 hover:text-blue-400 uppercase tracking-[0.2em] transition-colors">Cargar más sugerencias...</Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Professional Modal: Completar Mantenimiento -->
        <DialogModal :show="modalCompletarAbierto" @close="cerrarCompletar" maxWidth="2xl">
            <template #title>
                <div class="p-6 bg-slate-900 border-b border-slate-800">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-emerald-500/10 rounded-2xl flex items-center justify-center text-emerald-400 border border-emerald-500/20 shadow-lg shadow-emerald-900/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-white">Reporte de Ejecución</h3>
                            <p class="text-slate-500 text-xs font-medium uppercase tracking-widest mt-0.5">Finalización de Tarea Técnica</p>
                        </div>
                    </div>
                </div>
            </template>

            <template #content>
                <div class="p-8 space-y-8 bg-slate-900">
                    <!-- Resumen breve de que se esta completando -->
                    <div class="bg-slate-800/50 rounded-2xl p-5 border border-slate-700/50">
                        <h4 class="text-blue-400 font-black text-sm mb-1">{{ tareaACompletar?.mantenimiento.nombre }}</h4>
                        <div class="flex items-center gap-2 text-slate-500 text-xs font-bold uppercase">
                            <span>Cliente:</span>
                            <span class="text-slate-300">{{ tareaACompletar?.mantenimiento.poliza?.cliente?.nombre_razon_social }}</span>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Nivel de Resultado</label>
                                <span class="text-[10px] font-bold text-slate-600 bg-slate-800 px-2 rounded">Requerido *</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <button type="button" @click="formCompletar.resultado = 'exitoso'" 
                                    :class="['p-4 rounded-2xl border-2 transition-all text-center flex flex-col items-center gap-2', 
                                    formCompletar.resultado === 'exitoso' ? 'bg-emerald-500/10 border-emerald-500 text-emerald-400' : 'bg-slate-800 border-slate-700 text-slate-500 hover:border-slate-600']">
                                    <span class="text-xl">✅</span>
                                    <span class="text-[10px] font-black uppercase tracking-tight">Exitoso</span>
                                </button>
                                <button type="button" @click="formCompletar.resultado = 'con_observaciones'" 
                                    :class="['p-4 rounded-2xl border-2 transition-all text-center flex flex-col items-center gap-2', 
                                    formCompletar.resultado === 'con_observaciones' ? 'bg-amber-500/10 border-amber-500 text-amber-400' : 'bg-slate-800 border-slate-700 text-slate-500 hover:border-slate-600']">
                                    <span class="text-xl">⚠️</span>
                                    <span class="text-[10px] font-black uppercase tracking-tight">Observación</span>
                                </button>
                                <button type="button" @click="formCompletar.resultado = 'fallido'" 
                                    :class="['p-4 rounded-2xl border-2 transition-all text-center flex flex-col items-center gap-2', 
                                    formCompletar.resultado === 'fallido' ? 'bg-rose-500/10 border-rose-500 text-rose-400' : 'bg-slate-800 border-slate-700 text-slate-500 hover:border-slate-600']">
                                    <span class="text-xl">🔴</span>
                                    <span class="text-[10px] font-black uppercase tracking-tight">Falla/Pend.</span>
                                </button>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest text-left mt-4 mb-2">Resumen Operativo y Bitácora</label>
                            </div>
                            <textarea v-model="formCompletar.notas_tecnico" rows="5" 
                                class="w-full bg-slate-800 border-slate-700 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 rounded-2xl text-slate-200 placeholder-slate-600 text-sm font-medium transition-all"
                                placeholder="Describe el trabajo realizado, refacciones usadas, voltajes medidos o piezas por cambiar..."></textarea>
                            <InputError :message="formCompletar.errors.notas_tecnico" class="mt-2" />
                        </div>

                        <!-- Checklist de Tareas -->
                        <div v-if="formCompletar.checklist && formCompletar.checklist.length > 0">
                            <div class="flex justify-between items-center mb-2">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest text-left mt-4 mb-2">Lista de Verificación</label>
                                <span class="text-[10px] font-bold text-slate-600 bg-slate-800 px-2 rounded">{{ formCompletar.checklist.filter(i => i.checked).length }} / {{ formCompletar.checklist.length }}</span>
                            </div>
                            <div class="space-y-2 bg-slate-800/30 p-4 rounded-2xl border border-slate-700/50">
                                <div v-for="(item, index) in formCompletar.checklist" :key="index" 
                                    class="flex items-center gap-3 p-2 rounded-xl hover:bg-slate-800 transition-colors cursor-pointer"
                                    @click="item.checked = !item.checked">
                                    <div :class="['w-5 h-5 rounded flex items-center justify-center border transition-all', item.checked ? 'bg-blue-500 border-blue-500 text-white' : 'border-slate-600 bg-transparent']">
                                        <svg v-if="item.checked" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <span :class="['text-sm font-medium select-none', item.checked ? 'text-slate-300 line-through decoration-slate-600' : 'text-slate-400']">{{ item.label }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Placeholder para Evidencia Fotográfica (Futuro) -->
                        <div class="p-6 border-2 border-dashed border-slate-800 rounded-3xl text-center bg-slate-800/20">
                            <div class="text-slate-600 mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mx-auto opacity-30 shadow-none border-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <p class="text-[10px] font-black text-slate-600 uppercase tracking-widest">Módulo de Evidencia Fotográfica (Próximamente)</p>
                        </div>
                    </div>
                </div>
            </template>

            <template #footer>
                <div class="p-6 bg-slate-900 border-t border-slate-800 flex justify-end gap-4 shadow-none border-0">
                    <button @click="cerrarCompletar" class="px-6 py-3 text-slate-500 hover:text-white font-black text-[10px] uppercase tracking-widest transition-colors">
                        Cancelar
                    </button>
                    <button 
                        @click="enviarCompletar" 
                        :disabled="formCompletar.processing"
                        class="px-8 py-3 bg-blue-600 hover:bg-blue-500 text-white rounded-xl font-black text-[10px] uppercase tracking-widest shadow-xl shadow-blue-900/30 transition-all disabled:opacity-50"
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
    width: 4px;
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
