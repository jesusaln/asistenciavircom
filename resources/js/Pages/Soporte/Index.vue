<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    tickets: Object,
    stats: Object,
    categorias: Array,
    usuarios: Array,
    filtros: Object,
});

const filtros = ref({
    buscar: props.filtros?.buscar || '',
    estado: props.filtros?.estado || '',
    prioridad: props.filtros?.prioridad || '',
    asignado_id: props.filtros?.asignado_id || '',
    categoria_id: props.filtros?.categoria_id || '',
    incluir_finalizados: props.filtros?.incluir_finalizados || props.filtros?.incluir_cerrados || false,
});

const estados = [
    { value: '', label: 'Todos los estados' },
    { value: 'abierto', label: 'Abierto', color: 'blue' },
    { value: 'en_progreso', label: 'En Progreso', color: 'yellow' },
    { value: 'pendiente', label: 'Pendiente', color: 'orange' },
    { value: 'resuelto', label: 'Resuelto', color: 'green' },
    { value: 'cerrado', label: 'Cerrado', color: 'gray' },
];

const prioridades = [
    { value: '', label: 'Todas las prioridades' },
    { value: 'urgente', label: '🔴 Urgente' },
    { value: 'alta', label: '🟠 Alta' },
    { value: 'media', label: '🟡 Media' },
    { value: 'baja', label: '🟢 Baja' },
];

const aplicarFiltros = () => {
    router.get(route('soporte.index'), filtros.value, { preserveState: true });
};

const limpiarFiltros = () => {
    filtros.value = { buscar: '', estado: '', prioridad: '', asignado_id: '', categoria_id: '', incluir_finalizados: false };
    aplicarFiltros();
};

const getEstadoBadge = (estado) => {
    const colores = {
        abierto: 'bg-brand-500/10 text-blue-400 border-blue-500/20',
        en_progreso: 'bg-brand-500/10 text-brand-400 border-brand-500/20',
        pendiente: 'bg-brand-500/10 text-orange-400 border-brand-500/20',
        resuelto: 'bg-brand-500/10 text-emerald-400 border-emerald-500/20',
        cerrado: 'bg-slate-500/10 text-slate-400 border-slate-500/20',
    };
    return colores[estado] || 'bg-slate-100 text-slate-800';
};

const getPrioridadBadge = (prioridad) => {
    const colores = {
        urgente: 'bg-brand-500/10 text-rose-400 border-rose-500/20',
        alta: 'bg-brand-500/10 text-orange-400 border-brand-500/20',
        media: 'bg-brand-500/10 text-brand-400 border-brand-500/20',
        baja: 'bg-brand-500/10 text-emerald-400 border-emerald-500/20',
    };
    return colores[prioridad] || 'bg-slate-100 text-slate-500 border-slate-200';
};

const getSlaStatusClass = (status) => {
    const clases = {
        vencido: 'text-rose-400 font-black',
        critico: 'text-orange-400 font-bold',
        advertencia: 'text-amber-400',
        ok: 'text-emerald-400',
        completado: 'text-slate-500',
        sin_sla: 'text-slate-500',
    };
    return clases[status] || '';
};
</script>

<template>
    <AppLayout title="Soporte">
        <Head title="Soporte - Tickets" />

        <div class="min-h-screen bg-[var(--ui-surface)] text-slate-800 dark:text-slate-200 py-12 transition-colors">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Header Section -->
                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-8 mb-12 animate-in fade-in slide-in-from-top-4 duration-700">
                    <div class="flex items-center gap-6">
                        <div class="relative group">
                            <div class="absolute -inset-1 bg-gradient-to-r from-brand-500 to-brand-600 rounded-2xl blur opacity-25 group-hover:opacity-50 transition duration-500"></div>
                            <div class="relative w-16 h-16 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 flex items-center justify-center shadow-xl dark:shadow-2xl backdrop-blur-xl">
                                <svg class="w-10 h-10 text-brand-500 group-hover:scale-105 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter mb-1 uppercase">
                                Soporte <span class="bg-clip-text text-transparent bg-gradient-to-r from-brand-500 to-brand-500 dark:from-brand-400 dark:to-orange-400">Técnico</span>
                            </h1>
                            <p class="text-slate-500 dark:text-slate-400 text-sm font-bold uppercase tracking-[0.2em] italic">Centro de mando y atención al cliente</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-4">
                        <Link :href="route('soporte.dashboard')" class="px-6 py-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/5 hover:border-brand-500/50 text-slate-700 dark:text-slate-200 text-xs font-black uppercase tracking-wide rounded-2xl transition-all shadow-md dark:shadow-xl flex items-center gap-2 group">
                            <svg class="w-4 h-4 text-brand-500 group-hover:rotate-12 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                            Dashboard
                        </Link>
                        <Link :href="route('soporte.kb.index')" class="px-6 py-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/5 hover:border-brand-500/50 text-slate-700 dark:text-slate-200 text-xs font-black uppercase tracking-wide rounded-2xl transition-all shadow-md dark:shadow-xl flex items-center gap-2 group">
                            <svg class="w-4 h-4 text-indigo-500 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                            Knowledge Base
                        </Link>
                        <Link :href="route('soporte.create')" class="px-6 py-3 bg-brand-600 hover:bg-brand-700 text-white text-xs font-black uppercase tracking-wide rounded-2xl transition-all shadow-xl shadow-brand-600/20 flex items-center gap-2 group">
                            <svg class="w-4 h-4 group-hover:rotate-90 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" /></svg>
                            Nuevo Ticket
                        </Link>
                    </div>
                </div>

                <!-- Stats Grid Premium -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-6 mb-12 animate-in fade-in slide-in-from-bottom-4 duration-700 delay-150">
                    <div class="relative group">
                        <div class="absolute -inset-0.5 bg-gradient-to-br from-blue-500/20 to-transparent rounded-3xl blur opacity-0 group-hover:opacity-100 transition duration-500"></div>
                        <div class="relative bg-white dark:bg-slate-800/50 backdrop-blur-xl border border-slate-200 dark:border-white/5 p-6 rounded-3xl group-hover:border-brand-500/20 transition-all duration-200 shadow-sm">
                             <div class="text-4xl font-black text-blue-500 dark:text-blue-400 mb-1 tracking-tighter">{{ stats.abiertos }}</div>
                             <div class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide italic">Tickets Abiertos</div>
                        </div>
                    </div>
                    <div class="relative group">
                        <div class="absolute -inset-0.5 bg-gradient-to-br from-brand-500/20 to-transparent rounded-3xl blur opacity-0 group-hover:opacity-100 transition duration-500"></div>
                        <div class="relative bg-white dark:bg-slate-800/50 backdrop-blur-xl border border-slate-200 dark:border-white/5 p-6 rounded-3xl group-hover:border-brand-500/20 transition-all duration-200 shadow-sm">
                             <div class="text-4xl font-black text-brand-500 dark:text-brand-400 mb-1 tracking-tighter">{{ stats.sin_asignar }}</div>
                             <div class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide italic">Sin Asignar</div>
                        </div>
                    </div>
                    <div class="relative group">
                        <div class="absolute -inset-0.5 bg-gradient-to-br from-rose-500/20 to-transparent rounded-3xl blur opacity-0 group-hover:opacity-100 transition duration-500"></div>
                        <div class="relative bg-white dark:bg-slate-800/50 backdrop-blur-xl border border-slate-200 dark:border-white/5 p-6 rounded-3xl group-hover:border-brand-500/20 transition-all duration-200 shadow-sm">
                             <div class="text-4xl font-black text-rose-500 dark:text-rose-400 mb-1 tracking-tighter">{{ stats.vencidos }}</div>
                             <div class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide italic">SLA Vencido</div>
                        </div>
                    </div>
                    <div class="relative group">
                        <div class="absolute -inset-0.5 bg-gradient-to-br from-emerald-500/20 to-transparent rounded-3xl blur opacity-0 group-hover:opacity-100 transition duration-500"></div>
                        <div class="relative bg-white dark:bg-slate-800/50 backdrop-blur-xl border border-slate-200 dark:border-white/5 p-6 rounded-3xl group-hover:border-brand-500/20 transition-all duration-200 shadow-sm">
                             <div class="text-4xl font-black text-emerald-500 dark:text-slate-400 mb-1 tracking-tighter">{{ stats.completados_hoy }}</div>
                             <div class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide italic">Resueltos Hoy</div>
                        </div>
                    </div>
                    <div 
                        class="relative group cursor-pointer"
                        @click="filtros.incluir_finalizados = !filtros.incluir_finalizados; aplicarFiltros()"
                    >
                        <div class="absolute -inset-0.5 bg-gradient-to-br from-slate-500/20 to-transparent rounded-3xl blur opacity-0 group-hover:opacity-100 transition duration-500"></div>
                        <div 
                            class="relative bg-white dark:bg-slate-800/50 backdrop-blur-xl border border-slate-200 dark:border-white/5 p-6 rounded-3xl transition-all duration-200 shadow-sm"
                            :class="filtros.incluir_finalizados ? 'border-brand-500/30 bg-brand-500/5 dark:bg-brand-500/5' : 'group-hover:border-brand-500/20'"
                        >
                             <div class="text-4xl font-black text-slate-500 dark:text-slate-400 mb-1 tracking-tighter">{{ stats.cerrados }}</div>
                             <div class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide italic flex items-center justify-between">
                                Finalizados
                                <span v-if="filtros.incluir_finalizados" class="w-2 h-2 rounded-full bg-brand-500 animate-pulse"></span>
                             </div>
                        </div>
                    </div>
                </div>

                <!-- Filters Toolbar Premium -->
                <div class="bg-white dark:bg-slate-800/50 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-[2.5rem] p-4 mb-8 shadow-md dark:shadow-2xl animate-in fade-in slide-in-from-bottom-4 duration-700 delay-300">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-10 gap-3">
                        <div class="relative lg:col-span-3">
                             <svg class="absolute left-5 top-4 w-4 h-4 text-brand-500/50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                             <input 
                                v-model="filtros.buscar"
                                type="text"
                                placeholder="CLIENTE, FOLIO O ASUNTO..."
                                class="w-full pl-14 pr-6 py-4 bg-[var(--ui-surface)] dark:bg-slate-950/50 border border-slate-200 dark:border-white/5 rounded-3xl text-[10px] font-black uppercase tracking-wide text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 focus:ring-2 focus:ring-brand-500/50 focus:border-brand-500 transition-all shadow-inner"
                                @keyup.enter="aplicarFiltros"
                            />
                        </div>
                        
                        <div class="lg:col-span-2">
                            <select v-model="filtros.estado" @change="aplicarFiltros" class="w-full px-6 py-4 bg-[var(--ui-surface)] dark:bg-slate-950/50 border border-slate-200 dark:border-white/5 rounded-3xl text-[10px] font-black uppercase tracking-wide text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-brand-500/50 focus:border-brand-500 transition-all appearance-none cursor-pointer">
                                <option v-for="e in estados" :key="e.value" :value="e.value">{{ e.label }}</option>
                            </select>
                        </div>

                        <div class="lg:col-span-2">
                            <select v-model="filtros.prioridad" @change="aplicarFiltros" class="w-full px-6 py-4 bg-[var(--ui-surface)] dark:bg-slate-950/50 border border-slate-200 dark:border-white/5 rounded-3xl text-[10px] font-black uppercase tracking-wide text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-brand-500/50 focus:border-brand-500 transition-all appearance-none cursor-pointer">
                                <option v-for="p in prioridades" :key="p.value" :value="p.value">{{ p.label }}</option>
                            </select>
                        </div>

                        <div class="lg:col-span-2">
                            <select v-model="filtros.asignado_id" @change="aplicarFiltros" class="w-full px-6 py-4 bg-[var(--ui-surface)] dark:bg-slate-950/50 border border-slate-200 dark:border-white/5 rounded-3xl text-[10px] font-black uppercase tracking-wide text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-brand-500/50 focus:border-brand-500 transition-all appearance-none cursor-pointer">
                                <option value="">Técnicos</option>
                                <option value="sin_asignar">Sin asignar</option>
                                <option v-for="u in usuarios" :key="u.id" :value="u.id">{{ u.name }}</option>
                            </select>
                        </div>
                        
                        <button @click="limpiarFiltros" class="w-full py-4 text-[10px] font-black uppercase tracking-wide text-slate-400 hover:text-rose-500 transition-colors bg-[var(--ui-surface)] dark:bg-slate-950/30 rounded-3xl border border-slate-200 dark:border-white/5">
                            Reset ×
                        </button>
                    </div>
                </div>

                <!-- Tickets List Premium -->
                <div class="bg-white dark:bg-slate-800/50 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-[3rem] shadow-md dark:shadow-2xl overflow-hidden animate-in fade-in slide-in-from-bottom-8 duration-700">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead class="bg-slate-50 dark:bg-slate-800/50">
                                <tr>
                                    <th class="px-8 py-6 text-left text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide italic">Folio & Información Principal</th>
                                    <th class="px-8 py-6 text-left text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide italic">Cliente / Contacto</th>
                                    <th class="px-8 py-6 text-center text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide italic">Prioridad</th>
                                    <th class="px-8 py-6 text-center text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide italic">Estado Operativo</th>
                                    <th class="px-8 py-6 text-left text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide italic">Responsable</th>
                                    <th class="px-8 py-6 text-left text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide italic">SLA STATUS</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                                <tr 
                                    v-for="(ticket, idx) in tickets.data" 
                                    :key="ticket.id" 
                                    class="group hover:bg-slate-50 dark:hover:bg-white/5 cursor-pointer transition-all border-l-[6px] border-transparent hover:border-brand-500 animate-in fade-in slide-in-from-left-4"
                                    :style="{ 'animation-delay': (idx * 40) + 'ms' }"
                                    @click="router.visit(route('soporte.show', ticket.id))"
                                >
                                    <td class="px-8 py-8">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="font-black text-xs text-brand-600 dark:text-brand-500 bg-brand-500/10 px-3 py-1 rounded-xl border border-brand-500/20 shadow-sm dark:shadow-xl tracking-tighter">{{ ticket.numero }}</span>
                                            <div v-if="ticket.poliza_id" class="text-[9px] bg-brand-500/10 text-emerald-600 dark:text-slate-400 px-3 py-1 rounded-full font-black uppercase tracking-wide border border-emerald-500/20">
                                                🛡️ Póliza ACTIVA
                                            </div>
                                        </div>
                                        <div class="text-lg font-black text-slate-900 dark:text-white group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors truncate max-w-sm tracking-tight leading-tight uppercase">{{ ticket.titulo }}</div>
                                    </td>
                                    <td class="px-8 py-8">
                                        <div class="text-sm font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider group-hover:translate-x-1 transition-transform truncate max-w-xs">{{ ticket.cliente?.nombre || ticket.nombre_contacto || '-' }}</div>
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-[0.15em] mt-1.5 flex items-center gap-2">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                            {{ ticket.telefono_contacto }}
                                        </div>
                                    </td>
                                    <td class="px-8 py-8 text-center">
                                        <span :class="['px-5 py-2 text-[9px] font-black rounded-full uppercase tracking-[0.2em] border shadow-sm dark:shadow-2xl transition-all duration-500 group-hover:scale-105', getPrioridadBadge(ticket.prioridad)]">
                                            {{ ticket.prioridad }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-8 text-center">
                                        <span :class="['px-5 py-2 text-[9px] font-black rounded-full uppercase tracking-[0.2em] border shadow-sm dark:shadow-2xl transition-all duration-500 group-hover:brightness-125', getEstadoBadge(ticket.estado)]">
                                            {{ ticket.estado.replace('_', ' ') }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-8">
                                        <div class="flex items-center gap-4">
                                            <div class="relative">
                                                <div class="absolute -inset-1 bg-brand-500/20 rounded-full blur opacity-0 group-hover:opacity-100 transition duration-500"></div>
                                                <div class="relative w-10 h-10 bg-[var(--ui-surface)] border border-slate-200 dark:border-white/10 rounded-xl flex items-center justify-center text-[10px] font-black text-brand-600 dark:text-brand-500 shadow-sm dark:shadow-xl overflow-hidden uppercase">
                                                    {{ ticket.asignado?.name?.charAt(0) || '?' }}
                                                </div>
                                            </div>
                                            <div>
                                                <span class="text-[10px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wide">{{ ticket.asignado?.name || 'SIN TÉCNICO' }}</span>
                                                <p class="text-[8px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mt-0.5">Responsable</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-8">
                                        <div :class="['text-[11px] font-black uppercase tracking-[0.15em] flex items-center gap-2 group-hover:translate-x-1 transition-all', getSlaStatusClass(ticket.sla_status)]">
                                            <div class="relative w-2 h-2">
                                                <div class="absolute inset-0 bg-current rounded-full animate-ping opacity-75"></div>
                                                <div class="absolute inset-0 bg-current rounded-full"></div>
                                            </div>
                                            {{ ticket.sla_status }}
                                        </div>
                                        <div class="text-[9px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wide mt-2">{{ new Date(ticket.created_at).toLocaleDateString() }}</div>
                                    </td>
                                </tr>
                                <tr v-if="tickets.data.length === 0">
                                    <td colspan="6" class="px-8 py-32 text-center">
                                        <div class="w-16 h-16 bg-[var(--ui-surface)] rounded-3xl flex items-center justify-center mx-auto mb-8 border border-dashed border-slate-200 dark:border-white/10 opacity-50 shadow-sm dark:shadow-2xl">
                                             <svg class="w-10 h-10 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                        </div>
                                        <div class="text-xl font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Naturaleza en reposo</div>
                                        <p class="text-xs text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wide mt-2 italic">No se detectaron tickets activos con los parámetros orbitales actuales.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Premium -->
                    <div v-if="tickets.last_page > 1" class="px-10 py-8 bg-[var(--ui-surface)] dark:bg-slate-950/50 border-t border-slate-100 dark:border-white/5 flex flex-col sm:flex-row justify-between items-center gap-6">
                        <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.3em] italic">
                            REPRESENTACIÓN: {{ tickets.from }}-{{ tickets.to }} DE {{ tickets.total }} ENTIDADES
                        </span>
                        <div class="flex gap-2">
                            <Link 
                                v-for="link in tickets.links" 
                                :key="link.label"
                                :href="link.url"
                                :class="[
                                    'px-4 py-2 text-[10px] font-black uppercase tracking-wide rounded-xl transition-all border',
                                    link.active 
                                        ? 'bg-brand-600 text-white border-brand-500 shadow-xl shadow-brand-600/30' 
                                        : 'bg-white dark:bg-black/50 text-slate-500 border-slate-200 dark:border-white/5 hover:text-slate-900 dark:hover:text-white hover:border-brand-500/50'
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
