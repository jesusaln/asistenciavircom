<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import CategoryManager from '@/Components/Soporte/CategoryManager.vue';
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useCompanyColors } from '@/Composables/useCompanyColors';

const { isDarkMode, colors } = useCompanyColors();

const props = defineProps({
    porEstado: Object,
    porPrioridad: Object,
    porTecnico: Array,
    tiempoPromedioResolucion: Number,
    ticketsUltimos7Dias: Array,
    cumplimientoSla: Number,
    stats: Object,
    categorias: Array,
    horasPorTecnico: Array,
    horasPorPoliza: Array,
    estadoColores: Object,
});

const showCategoryModal = ref(false);

const estadoColores = {
    abierto: '#3B82F6',
    en_progreso: '#F59E0B',
    pendiente: '#F97316',
    resuelto: '#10B981',
    cerrado: '#6B7280',
};

const prioridadColores = {
    urgente: '#EF4444',
    alta: '#F97316',
    media: '#F59E0B',
    baja: '#10B981',
};
</script>

<template>
    <AppLayout title="Dashboard de Soporte">
        <Head title="Dashboard de Soporte" />

        <div class="min-h-screen bg-slate-950 text-slate-200 py-12">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Header -->
                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-8 mb-12 animate-in fade-in slide-in-from-top-4 duration-700">
                    <div class="flex items-center gap-6">
                        <div class="relative group">
                            <div class="absolute -inset-1 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-2xl blur opacity-25 group-hover:opacity-50 transition duration-500"></div>
                            <div class="relative w-16 h-16 rounded-2xl bg-slate-900 border border-white/10 flex items-center justify-center shadow-2xl backdrop-blur-xl">
                                <svg class="w-8 h-8 text-blue-400 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h1 class="text-4xl font-black text-white tracking-tighter mb-1 uppercase">
                                Métricas de <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-indigo-400">Rendimiento</span>
                            </h1>
                            <p class="text-slate-500 text-sm font-bold uppercase tracking-[0.2em] italic">Análisis orbital de soporte técnico</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-4">
                        <a 
                            :href="route('soporte.reporte.horas-tecnico')" 
                            target="_blank"
                            class="px-6 py-3 bg-slate-900 border border-white/5 hover:border-blue-500/50 text-slate-300 text-xs font-black uppercase tracking-widest rounded-2xl transition-all shadow-xl flex items-center gap-2 group"
                        >
                            <svg class="w-4 h-4 text-blue-400 group-hover:rotate-12 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            Reporte de Horas
                        </a>
                        <button @click="showCategoryModal = true" class="px-6 py-3 bg-slate-900 border border-white/5 hover:border-amber-500/50 text-slate-300 text-xs font-black uppercase tracking-widest rounded-2xl transition-all shadow-xl flex items-center gap-2 group">
                            <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                            Categorías
                        </button>
                        <Link :href="route('soporte.index')" class="px-6 py-3 bg-amber-600 hover:bg-amber-500 text-white text-xs font-black uppercase tracking-widest rounded-2xl transition-all shadow-lg shadow-amber-600/20 flex items-center gap-2 group">
                            Gestión de Tickets
                            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                        </Link>
                    </div>
                </div>

                <!-- Principal Matrix Grid -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 mb-12 animate-in fade-in slide-in-from-bottom-4 duration-700 delay-150">
                    <div class="relative group">
                        <div class="absolute -inset-0.5 bg-gradient-to-br from-amber-500/20 to-transparent rounded-[2.5rem] blur opacity-0 group-hover:opacity-100 transition duration-500"></div>
                        <div class="relative bg-slate-900/40 backdrop-blur-xl border border-white/5 p-8 rounded-[2.5rem] group-hover:bg-slate-900/60 transition-all duration-300 overflow-hidden">
                             <div class="absolute -right-4 -top-4 w-24 h-24 bg-amber-500/5 rounded-full blur-3xl"></div>
                             <div class="text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] mb-4 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                Tickets Abiertos
                             </div>
                             <div class="text-6xl font-black text-white tracking-tighter mb-2">{{ stats.total_abiertos }}</div>
                             <div class="text-[10px] font-black text-amber-500/80 uppercase tracking-widest italic flex items-center gap-1 group-hover:translate-x-1 transition-transform">
                                Pendientes en cola →
                             </div>
                        </div>
                    </div>
                    <div class="relative group">
                        <div class="absolute -inset-0.5 bg-gradient-to-br from-rose-500/20 to-transparent rounded-[2.5rem] blur opacity-0 group-hover:opacity-100 transition duration-500"></div>
                        <div class="relative bg-slate-900/40 backdrop-blur-xl border border-white/5 p-8 rounded-[2.5rem] group-hover:bg-slate-900/60 transition-all duration-300 overflow-hidden">
                             <div class="absolute -right-4 -top-4 w-24 h-24 bg-rose-500/5 rounded-full blur-3xl"></div>
                             <div class="text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] mb-4 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                                Alta Prioridad
                             </div>
                             <div class="text-6xl font-black text-white tracking-tighter mb-2">{{ stats.urgentes }}</div>
                             <div class="text-[10px] font-black text-rose-500/80 uppercase tracking-widest italic flex items-center gap-1 group-hover:translate-x-1 transition-transform">
                                Requiere acción inmediata →
                             </div>
                        </div>
                    </div>
                    <div class="relative group">
                        <div class="absolute -inset-0.5 bg-gradient-to-br from-orange-500/20 to-transparent rounded-[2.5rem] blur opacity-0 group-hover:opacity-100 transition duration-500"></div>
                        <div class="relative bg-slate-900/40 backdrop-blur-xl border border-white/5 p-8 rounded-[2.5rem] group-hover:bg-slate-900/60 transition-all duration-300 overflow-hidden">
                             <div class="absolute -right-4 -top-4 w-24 h-24 bg-orange-500/5 rounded-full blur-3xl"></div>
                             <div class="text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] mb-4 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-orange-500 animate-pulse"></span>
                                SLA Crítico
                             </div>
                             <div class="text-6xl font-black text-white tracking-tighter mb-2">{{ stats.vencidos }}</div>
                             <div class="text-[10px] font-black text-orange-500/80 uppercase tracking-widest italic flex items-center gap-1 group-hover:translate-x-1 transition-transform">
                                Compromisos fuera de tiempo →
                             </div>
                        </div>
                    </div>
                    <div class="relative group">
                        <div class="absolute -inset-0.5 bg-gradient-to-br from-emerald-500/20 to-transparent rounded-[2.5rem] blur opacity-0 group-hover:opacity-100 transition duration-500"></div>
                        <div class="relative bg-slate-900/40 backdrop-blur-xl border border-white/5 p-8 rounded-[2.5rem] group-hover:bg-slate-900/60 transition-all duration-300 overflow-hidden">
                             <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-500/5 rounded-full blur-3xl"></div>
                             <div class="text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] mb-4 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                Resueltos Hoy
                             </div>
                             <div class="text-6xl font-black text-white tracking-tighter mb-2">{{ stats.resueltos_hoy }}</div>
                             <div class="text-[10px] font-black text-emerald-500/80 uppercase tracking-widest italic flex items-center gap-1 group-hover:translate-x-1 transition-transform">
                                Eficiencia operacional →
                             </div>
                        </div>
                    </div>
                </div>

                <!-- Charts and Distributions Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 mb-12 animate-in fade-in slide-in-from-bottom-8 duration-700 delay-300">
                    <!-- Distribution by Status -->
                    <div class="relative group">
                        <div class="absolute -inset-0.5 bg-gradient-to-br from-blue-500/10 to-transparent rounded-[3rem] blur opacity-0 group-hover:opacity-100 transition duration-500"></div>
                        <div class="relative bg-slate-900/40 backdrop-blur-xl border border-white/5 p-10 rounded-[3rem] shadow-2xl">
                            <div class="flex items-center justify-between mb-10">
                                <h3 class="text-xs font-black text-white uppercase tracking-[0.3em] flex items-center gap-3">
                                    <div class="w-3 h-3 bg-blue-500 rounded-full shadow-[0_0_10px_rgba(59,130,246,0.5)]"></div>
                                    Distribución Operativa
                                </h3>
                                <span class="text-[9px] font-bold text-slate-500 tracking-widest italic">POR ESTADO</span>
                            </div>
                            
                            <div class="space-y-8">
                                <div v-for="(total, estado) in porEstado" :key="estado" class="group/item">
                                    <div class="flex justify-between items-end mb-3">
                                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest transition-colors group-hover/item:text-white capitalize">{{ estado.replace('_', ' ') }}</span>
                                        <div class="flex items-baseline gap-2">
                                            <span class="text-lg font-black text-white tracking-tighter">{{ total }}</span>
                                            <span class="text-[9px] font-bold text-slate-600">UNIDADES</span>
                                        </div>
                                    </div>
                                    <div class="h-1.5 w-full bg-slate-950/50 rounded-full overflow-hidden border border-white/5 p-0.5">
                                        <div 
                                            class="h-full rounded-full transition-all duration-1000 group-hover/item:brightness-125 group-hover/item:shadow-[0_0_15px_rgba(255,255,255,0.2)]"
                                            :style="{ 
                                                width: `${(total / (Object.values(porEstado).reduce((a,b) => a+b, 0) || 1)) * 100}%`,
                                                backgroundColor: estadoColores[estado]
                                            }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Focus by Priority -->
                    <div class="relative group">
                        <div class="absolute -inset-0.5 bg-gradient-to-br from-rose-500/10 to-transparent rounded-[3rem] blur opacity-0 group-hover:opacity-100 transition duration-500"></div>
                        <div class="relative bg-slate-900/40 backdrop-blur-xl border border-white/5 p-10 rounded-[3rem] shadow-2xl">
                             <div class="flex items-center justify-between mb-10">
                                <h3 class="text-xs font-black text-white uppercase tracking-[0.3em] flex items-center gap-3">
                                    <div class="w-3 h-3 bg-rose-500 rounded-full shadow-[0_0_10px_rgba(244,63,94,0.5)]"></div>
                                    Enfoque de Prioridad
                                </h3>
                                <span class="text-[9px] font-bold text-slate-500 tracking-widest italic">POR CRITICIDAD</span>
                            </div>
                            
                            <div class="space-y-8">
                                <div v-for="(total, prioridad) in porPrioridad" :key="prioridad" class="group/item">
                                    <div class="flex justify-between items-end mb-3">
                                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest transition-colors group-hover/item:text-white capitalize">{{ prioridad }}</span>
                                        <div class="flex items-baseline gap-2">
                                            <span class="text-lg font-black text-white tracking-tighter">{{ total }}</span>
                                            <span class="text-[9px] font-bold text-slate-600">ENTIDADES</span>
                                        </div>
                                    </div>
                                    <div class="h-1.5 w-full bg-slate-950/50 rounded-full overflow-hidden border border-white/5 p-0.5">
                                        <div 
                                            class="h-full rounded-full transition-all duration-1000 group-hover/item:brightness-125"
                                            :style="{ 
                                                width: `${(total / (Object.values(porPrioridad).reduce((a,b) => a+b, 0) || 1)) * 100}%`,
                                                backgroundColor: prioridadColores[prioridad]
                                            }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Secondary Metrics Row -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12 animate-in fade-in slide-in-from-bottom-8 duration-700 delay-500">
                    <!-- Resolution Time Card -->
                    <div class="relative group">
                        <div class="absolute -inset-0.5 bg-gradient-to-br from-orange-500 to-amber-600 rounded-[2.5rem] blur opacity-10 group-hover:opacity-30 transition duration-500"></div>
                        <div class="relative h-full bg-gradient-to-br from-orange-500/10 to-transparent border border-orange-500/20 p-8 rounded-[2.5rem] backdrop-blur-xl">
                            <h3 class="text-[10px] font-black text-orange-400 uppercase tracking-[0.3em] mb-6">Tiempo de Resolución</h3>
                            <div class="flex items-baseline gap-2">
                                <div class="text-7xl font-black text-white tracking-tighter">{{ tiempoPromedioResolucion }}</div>
                                <div class="text-xl font-bold text-orange-400 italic">HRS</div>
                            </div>
                            <p class="text-[10px] font-medium text-slate-500 uppercase tracking-widest mt-6 italic">PROMEDIO CIERRE / 30 DÍAS</p>
                        </div>
                    </div>

                    <!-- SLA Compliance Card -->
                    <div class="relative group">
                        <div class="absolute -inset-0.5 bg-gradient-to-br from-emerald-500/20 to-transparent rounded-[2.5rem] blur opacity-0 group-hover:opacity-100 transition duration-500"></div>
                        <div class="relative h-full bg-slate-900/40 border border-white/5 p-8 rounded-[2.5rem] backdrop-blur-xl">
                            <h3 class="text-[10px] font-black text-emerald-400 uppercase tracking-[0.3em] mb-6">Cumplimiento SLA</h3>
                            <div class="flex items-baseline gap-2 mb-6">
                                <div class="text-7xl font-black tracking-tighter" :class="cumplimientoSla >= 90 ? 'text-emerald-400' : cumplimientoSla >= 70 ? 'text-amber-400' : 'text-rose-400'">
                                    {{ cumplimientoSla }}%
                                </div>
                            </div>
                            <div class="h-2 w-full bg-slate-950 rounded-full overflow-hidden border border-white/5">
                                <div 
                                    class="h-full rounded-full transition-all duration-1000"
                                    :class="cumplimientoSla >= 90 ? 'bg-emerald-500' : cumplimientoSla >= 70 ? 'bg-amber-500' : 'bg-rose-500'"
                                    :style="{ width: `${cumplimientoSla}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <!-- Technician Activity -->
                    <div class="relative group">
                         <div class="absolute -inset-0.5 bg-gradient-to-br from-indigo-500/20 to-transparent rounded-[2.5rem] blur opacity-0 group-hover:opacity-100 transition duration-500"></div>
                         <div class="relative h-full bg-slate-900/40 border border-white/5 p-8 rounded-[2.5rem] backdrop-blur-xl">
                            <h3 class="text-[10px] font-black text-indigo-400 uppercase tracking-[0.3em] mb-6">Actividad por Técnico</h3>
                            <div class="space-y-3 max-h-[140px] overflow-y-auto pr-2 custom-scrollbar">
                                <div v-for="item in porTecnico" :key="item.asignado_id || 'sin'" class="flex items-center justify-between py-2 border-b border-white/5 last:border-0">
                                    <span class="text-[11px] font-black text-slate-300 uppercase tracking-tight">{{ item.asignado?.name || 'DESASIGNADOS' }}</span>
                                    <span class="text-xs font-black text-white bg-slate-950 px-3 py-1 rounded-lg border border-white/10">{{ item.total }}</span>
                                </div>
                            </div>
                         </div>
                    </div>
                </div>

                <!-- Last 7 Days Activity Grid -->
                <div class="bg-slate-900/40 backdrop-blur-xl border border-white/5 rounded-[4rem] p-12 shadow-2xl mb-12 animate-in fade-in slide-in-from-bottom-8 duration-700 delay-700 overflow-hidden relative">
                    <div class="absolute -right-20 -top-20 w-80 h-80 bg-amber-500/5 rounded-full blur-[100px]"></div>
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-8 mb-16">
                         <div>
                            <h3 class="text-lg font-black text-white uppercase tracking-[0.4em] mb-2 flex items-center gap-4">
                                <div class="w-1 h-8 bg-amber-500"></div>
                                Flujo Semanal de Tickets
                            </h3>
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest italic tracking-[0.2em]">Detección de tendencias por intervalo solar (7D)</p>
                         </div>
                         <div class="flex items-center gap-3">
                             <div class="w-2 h-2 rounded-full bg-amber-500 shadow-[0_0_8px_rgba(245,158,11,0.8)]"></div>
                             <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">REGISTRO ACTIVO</span>
                         </div>
                    </div>

                    <div class="flex items-end gap-6 sm:gap-12 h-48 px-4">
                        <div 
                            v-for="dia in ticketsUltimos7Dias" 
                            :key="dia.fecha"
                            class="flex-1 flex flex-col items-center group/bar"
                        >
                            <div class="relative w-full h-full flex flex-col justify-end">
                                <!-- Tooltip Digital -->
                                <div class="absolute bottom-full mb-4 left-1/2 -translate-x-1/2 px-4 py-2 bg-slate-900 border border-white/10 rounded-xl opacity-0 group-hover/bar:opacity-100 transition-all duration-300 -translate-y-2 group-hover/bar:translate-y-0 shadow-2xl z-20 pointer-events-none">
                                     <div class="text-[10px] font-black text-amber-500 uppercase tracking-widest mb-1">{{ dia.total }} TICKETS</div>
                                     <div class="text-[8px] font-bold text-slate-500 whitespace-nowrap">{{ new Date(dia.fecha).toLocaleDateString() }}</div>
                                </div>
                                
                                <div 
                                    class="w-full bg-gradient-to-t from-amber-600 to-amber-400 rounded-2xl group-hover/bar:brightness-125 transition-all duration-700 shadow-[0_10px_30px_-5px_rgba(245,158,11,0.2)] group-hover/bar:shadow-[0_15px_40px_-5px_rgba(245,158,11,0.4)]"
                                    :style="{ 
                                        height: `${Math.max(5, (dia.total / Math.max(...ticketsUltimos7Dias.map(d => d.total || 1))) * 100)}%`
                                    }"
                                >
                                    <div class="w-full h-full bg-[linear-gradient(45deg,transparent_25%,rgba(255,255,255,0.1)_50%,transparent_75%)] bg-[length:20px_20px] animate-[pulse_3s_infinite]"></div>
                                </div>
                            </div>
                            <div class="text-[10px] text-slate-500 font-black uppercase tracking-[0.2em] mt-6 transition-colors group-hover/bar:text-white">{{ new Date(dia.fecha).toLocaleDateString('es-MX', { weekday: 'short' }) }}</div>
                        </div>
                    </div>
                </div>

                <!-- Hours Registries Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 animate-in fade-in slide-in-from-bottom-8 duration-700 delay-1000">
                    <!-- Hours by Tech -->
                    <div class="relative group">
                        <div class="absolute -inset-0.5 bg-gradient-to-br from-amber-500/10 to-transparent rounded-[3rem] blur opacity-0 group-hover:opacity-100 transition duration-500"></div>
                        <div class="relative bg-slate-900/40 backdrop-blur-xl border border-white/5 p-10 rounded-[3rem] shadow-2xl h-full">
                            <div class="flex items-center justify-between mb-10">
                                <h3 class="text-xs font-black text-white uppercase tracking-[0.3em] flex items-center gap-3">
                                    <div class="w-3 h-3 bg-amber-500 rounded-full shadow-[0_0_10px_rgba(245,158,11,0.5)]"></div>
                                    Esfuerzo por Técnico
                                </h3>
                                <span class="text-[9px] font-bold text-slate-500 tracking-widest italic">ÚLT. 30 DÍAS</span>
                            </div>
                            
                            <div v-if="horasPorTecnico && horasPorTecnico.length > 0" class="space-y-6">
                                <div v-for="item in horasPorTecnico" :key="item.asignado_id" class="flex items-center justify-between p-4 bg-slate-950/50 border border-white/5 rounded-2xl hover:bg-slate-950 transition-all duration-300 group/row relative overflow-hidden">
                                     <div class="absolute inset-y-0 left-0 w-1 bg-amber-500/50"></div>
                                     <div class="flex items-center gap-5">
                                        <div class="w-12 h-12 bg-slate-900 border border-white/10 rounded-2xl flex items-center justify-center text-amber-500 font-black text-sm uppercase shadow-inner">
                                            {{ item.asignado?.name?.charAt(0) || '?' }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-black text-white uppercase tracking-tight">{{ item.asignado?.name || 'DESASIGNADO' }}</div>
                                            <div class="text-[9px] font-bold text-slate-500 uppercase tracking-widest mt-1 italic">{{ item.total_tickets }} TICKETS GESTIONADOS</div>
                                        </div>
                                     </div>
                                     <div class="text-right">
                                        <div class="text-2xl font-black text-amber-400 tracking-tighter">{{ Number(item.total_horas).toFixed(1) }}h</div>
                                        <div class="text-[9px] font-bold text-slate-600 uppercase tracking-widest">{{ (item.total_horas / (item.total_tickets || 1)).toFixed(1) }}H/TICKET</div>
                                     </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-20">
                                <span class="text-4xl block mb-4">⏱️</span>
                                <p class="text-[10px] font-black text-slate-600 uppercase tracking-widest italic">No se detectaron registros de esfuerzo</p>
                            </div>
                        </div>
                    </div>

                    <!-- Hours by Policy -->
                    <div class="relative group">
                        <div class="absolute -inset-0.5 bg-gradient-to-br from-emerald-500/10 to-transparent rounded-[3rem] blur opacity-0 group-hover:opacity-100 transition duration-500"></div>
                        <div class="relative bg-slate-900/40 backdrop-blur-xl border border-white/5 p-10 rounded-[3rem] shadow-2xl h-full">
                            <div class="flex items-center justify-between mb-10">
                                <h3 class="text-xs font-black text-white uppercase tracking-[0.3em] flex items-center gap-3">
                                    <div class="w-3 h-3 bg-emerald-500 rounded-full shadow-[0_0_10px_rgba(16,185,129,0.5)]"></div>
                                    Consumo por Póliza
                                </h3>
                                <span class="text-[9px] font-bold text-slate-500 tracking-widest italic">MES ACTUAL</span>
                            </div>
                            
                            <div v-if="horasPorPoliza && horasPorPoliza.length > 0" class="space-y-4">
                                <div v-for="item in horasPorPoliza" :key="item.poliza_id" 
                                    class="p-5 rounded-[2rem] border transition-all duration-300 relative overflow-hidden" 
                                    :class="item.poliza?.horas_incluidas_mensual && Number(item.total_horas) > item.poliza.horas_incluidas_mensual ? 'bg-rose-500/5 border-rose-500/20' : 'bg-slate-950/50 border-white/5 hover:bg-slate-950'">
                                    
                                    <div v-if="item.poliza?.horas_incluidas_mensual && Number(item.total_horas) > item.poliza.horas_incluidas_mensual" class="absolute top-4 right-6 animate-bounce">
                                        <span class="text-[10px] font-black text-rose-500 uppercase tracking-widest bg-rose-500/10 px-3 py-1 rounded-full border border-rose-500/20">⚠️ CRÍTICO</span>
                                    </div>

                                    <div class="flex justify-between items-start mb-6">
                                        <div>
                                            <div class="text-sm font-black text-white uppercase tracking-tight mb-1">{{ item.poliza?.nombre || 'Póliza' }}</div>
                                            <div class="text-[9px] font-mono text-emerald-500 font-bold uppercase tracking-widest italic">{{ item.poliza?.folio }}</div>
                                            <div class="text-[9px] text-slate-500 font-bold uppercase tracking-widest mt-2 truncate max-w-[200px]">{{ item.poliza?.cliente?.nombre_razon_social }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-3xl font-black tracking-tighter" :class="Number(item.total_horas) > (item.poliza?.horas_incluidas_mensual || 9999) ? 'text-rose-400' : 'text-emerald-400'">
                                                {{ Number(item.total_horas).toFixed(1) }}h
                                            </div>
                                            <div v-if="item.poliza?.horas_incluidas_mensual" class="text-[9px] font-black uppercase tracking-widest mt-1" :class="Number(item.total_horas) > item.poliza.horas_incluidas_mensual ? 'text-rose-500' : 'text-slate-500'">
                                                / {{ item.poliza.horas_incluidas_mensual }}H INCLUÍDAS
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div v-if="item.poliza?.horas_incluidas_mensual" class="relative pt-0.5">
                                        <div class="h-1.5 w-full bg-slate-900 rounded-full overflow-hidden p-0.5">
                                            <div 
                                                class="h-full rounded-full transition-all duration-1000"
                                                :class="Number(item.total_horas) > item.poliza.horas_incluidas_mensual ? 'bg-rose-500' : 'bg-emerald-500'"
                                                :style="{ width: Math.min((item.total_horas / item.poliza.horas_incluidas_mensual) * 100, 100) + '%' }"
                                            ></div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex justify-between items-center mt-6">
                                        <div class="text-[9px] font-black text-slate-500 uppercase tracking-widest italic">{{ item.total_tickets }} ENTIDADES ATENDIDAS</div>
                                        <a 
                                            :href="route('soporte.reporte.consumo-poliza', item.poliza_id)" 
                                            target="_blank"
                                            class="text-[9px] font-black text-emerald-500 hover:text-emerald-400 uppercase tracking-widest flex items-center gap-2 group/link"
                                        >
                                            ANÁLISIS DETALLADO
                                            <svg class="w-3 h-3 transform group-hover/link:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-20">
                                <span class="text-4xl block mb-4">🛡️</span>
                                <p class="text-[10px] font-black text-slate-600 uppercase tracking-widest italic">Naturaleza bajo control (Sin consumos)</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Category Management Modal Premium -->
        <Modal :show="showCategoryModal" @close="showCategoryModal = false" maxWidth="4xl">
            <div class="bg-slate-900 border border-white/10 rounded-[3rem] shadow-[0_50px_100px_-20px_rgba(0,0,0,0.5)] overflow-hidden">
                <div class="p-10 border-b border-white/5 flex items-center justify-between bg-gradient-to-r from-amber-500/5 to-transparent">
                    <div class="flex items-center gap-5">
                        <div class="w-12 h-12 rounded-2xl bg-amber-500/10 flex items-center justify-center text-amber-500 border border-amber-500/20">
                             <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                        </div>
                        <div>
                             <h2 class="text-xl font-black text-white uppercase tracking-tighter">Gestión de Taxonomía</h2>
                             <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest italic tracking-[0.2em] mt-1">Configuración de categorías de soporte</p>
                        </div>
                    </div>
                    <button @click="showCategoryModal = false" class="w-10 h-10 rounded-xl hover:bg-white/5 flex items-center justify-center transition-colors text-slate-500">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="p-10 bg-slate-900">
                    <CategoryManager :categorias="props.categorias" />
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.2);
}
</style>
