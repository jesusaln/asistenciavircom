<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

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
        abierto: 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-200/50 dark:border-indigo-500/20',
        en_progreso: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-200/50 dark:border-amber-500/20',
        pendiente: 'bg-orange-500/10 text-orange-600 dark:text-orange-400 border-orange-200/50 dark:border-orange-500/20',
        resuelto: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-200/50 dark:border-emerald-500/20',
        cerrado: 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-200/50 dark:border-slate-800',
    };
    return colores[estado] || 'bg-slate-100 text-slate-800 dark:text-slate-100';
};

const getPrioridadBadge = (prioridad) => {
    const colores = {
        urgente: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-200/50 dark:border-rose-500/20',
        alta: 'bg-orange-500/10 text-orange-600 dark:text-orange-400 border-orange-200/50 dark:border-orange-500/20',
        media: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-200/50 dark:border-amber-500/20',
        baja: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-200/50 dark:border-emerald-500/20',
    };
    return colores[prioridad] || 'bg-slate-100 text-slate-600 border-slate-200';
};

const getSlaStatusClass = (status) => {
    const clases = {
        vencido: 'text-rose-600 dark:text-rose-400 font-black',
        critico: 'text-orange-600 dark:text-orange-400 font-bold',
        advertencia: 'text-amber-600 dark:text-amber-400 font-bold',
        ok: 'text-emerald-600 dark:text-emerald-400 font-bold',
        completado: 'text-slate-500 dark:text-slate-400',
        sin_sla: 'text-slate-400 dark:text-slate-600',
    };
    return clases[status] || '';
};
</script>

<template>
    <AppLayout title="Soporte Técnico">
        <Head title="Soporte - Tickets" />

        <div class="py-10 bg-slate-50/50 dark:bg-slate-950/20 min-h-screen">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header Premium -->
                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-12">
                    <div class="animate-in slide-in-from-left-4 duration-700">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 rounded-xl bg-indigo-500 flex items-center justify-center text-white shadow-lg shadow-indigo-500/20">
                                <FontAwesomeIcon icon="headset" />
                            </div>
                            <h1 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Soporte Técnico</h1>
                        </div>
                        <p class="text-slate-500 dark:text-slate-400 font-medium">Gestión integral de incidencias y atención especializada</p>
                    </div>
                    
                    <div class="flex items-center gap-4 animate-in slide-in-from-right-4 duration-700">
                        <Link :href="route('soporte.dashboard')" class="px-6 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-50 dark:hover:bg-slate-800 transition-all shadow-sm">
                            <FontAwesomeIcon icon="chart-pie" class="mr-2" />
                            Dashboard
                        </Link>
                        <Link :href="route('soporte.create')" class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl font-black text-xs uppercase tracking-widest transition-all shadow-xl shadow-indigo-500/30 flex items-center">
                            <span class="mr-2 text-lg">+</span>
                            Nuevo Ticket
                        </Link>
                    </div>
                </div>

                <!-- Estadísticas Premium -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-6 mb-12">
                    <div class="group bg-white dark:bg-slate-900/60 p-8 rounded-[2rem] border border-slate-100 dark:border-slate-800/60 shadow-xl hover:shadow-2xl transition-all duration-300">
                        <div class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.2em] mb-3">Abiertos</div>
                        <div class="flex items-end justify-between">
                            <div class="text-4xl font-black text-slate-900 dark:text-white">{{ stats.abiertos }}</div>
                            <div class="w-10 h-10 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-500 group-hover:scale-110 transition-transform">
                                <FontAwesomeIcon icon="folder-open" />
                            </div>
                        </div>
                    </div>
                    
                    <div class="group bg-gradient-to-br from-amber-500/5 to-amber-600/10 p-8 rounded-[2rem] border border-amber-200/30 dark:border-amber-500/20 shadow-xl hover:shadow-2xl transition-all duration-300">
                        <div class="text-[10px] font-black text-amber-600 dark:text-amber-400 uppercase tracking-[0.2em] mb-3">Sin Asignar</div>
                        <div class="flex items-end justify-between">
                            <div class="text-4xl font-black text-amber-700 dark:text-amber-400">{{ stats.sin_asignar }}</div>
                            <div class="w-10 h-10 rounded-xl bg-amber-500/20 flex items-center justify-center text-amber-600 group-hover:scale-110 transition-transform">
                                <FontAwesomeIcon icon="user-clock" />
                            </div>
                        </div>
                    </div>

                    <div class="group bg-gradient-to-br from-rose-500/5 to-rose-600/10 p-8 rounded-[2rem] border border-rose-200/30 dark:border-rose-500/20 shadow-xl hover:shadow-2xl transition-all duration-300">
                        <div class="text-[10px] font-black text-rose-600 dark:text-rose-400 uppercase tracking-[0.2em] mb-3">SLA Crítico</div>
                        <div class="flex items-end justify-between">
                            <div class="text-4xl font-black text-rose-700 dark:text-rose-400">{{ stats.vencidos }}</div>
                            <div class="w-10 h-10 rounded-xl bg-rose-500/20 flex items-center justify-center text-rose-600 animate-pulse group-hover:scale-110 transition-transform">
                                <FontAwesomeIcon icon="triangle-exclamation" />
                            </div>
                        </div>
                    </div>

                    <div class="group bg-gradient-to-br from-emerald-500/5 to-emerald-600/10 p-8 rounded-[2rem] border border-emerald-200/30 dark:border-emerald-500/20 shadow-xl hover:shadow-2xl transition-all duration-300">
                        <div class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-[0.2em] mb-3">Resueltos Hoy</div>
                        <div class="flex items-end justify-between">
                            <div class="text-4xl font-black text-emerald-700 dark:text-emerald-400">{{ stats.completados_hoy }}</div>
                            <div class="w-10 h-10 rounded-xl bg-emerald-500/20 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform">
                                <FontAwesomeIcon icon="check-double" />
                            </div>
                        </div>
                    </div>

                    <div 
                        class="group bg-white dark:bg-slate-900/40 p-8 rounded-[2rem] border border-slate-100 dark:border-slate-800/60 shadow-xl cursor-pointer hover:border-slate-300 dark:hover:border-slate-600 transition-all duration-300"
                        @click="filtros.incluir_finalizados = !filtros.incluir_finalizados; aplicarFiltros()"
                    >
                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 flex items-center justify-between">
                            Cerrados
                            <span v-if="filtros.incluir_finalizados" class="flex h-2 w-2 rounded-full bg-emerald-500 ring-4 ring-emerald-500/20"></span>
                        </div>
                        <div class="flex items-end justify-between">
                            <div class="text-4xl font-black text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-300 transition-colors">{{ stats.cerrados }}</div>
                            <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:scale-110 transition-transform">
                                <FontAwesomeIcon icon="archive" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros Avanzados Premium -->
                <div class="bg-white dark:bg-slate-900/60 backdrop-blur-xl rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800/60 p-8 mb-12">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-6">
                        <div class="relative lg:col-span-2 group">
                             <div class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors">
                                 <FontAwesomeIcon icon="search" />
                             </div>
                             <input 
                                v-model="filtros.buscar"
                                type="text"
                                placeholder="Buscar por cliente, folio o asunto..."
                                class="w-full pl-14 pr-6 py-4 bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 rounded-2xl text-slate-900 dark:text-white placeholder-slate-400 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-medium"
                                @keyup.enter="aplicarFiltros"
                            />
                        </div>
                        
                        <div class="relative">
                            <select v-model="filtros.estado" @change="aplicarFiltros" class="w-full pl-6 pr-10 py-4 bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 rounded-2xl text-slate-900 dark:text-white appearance-none cursor-pointer focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-bold text-xs uppercase tracking-widest">
                                <option v-for="e in estados" :key="e.value" :value="e.value">{{ e.label }}</option>
                            </select>
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                <FontAwesomeIcon icon="chevron-down" size="xs" />
                            </div>
                        </div>

                        <div class="relative">
                            <select v-model="filtros.prioridad" @change="aplicarFiltros" class="w-full pl-6 pr-10 py-4 bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 rounded-2xl text-slate-900 dark:text-white appearance-none cursor-pointer focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-bold text-xs uppercase tracking-widest">
                                <option v-for="p in prioridades" :key="p.value" :value="p.value">{{ p.label }}</option>
                            </select>
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                <FontAwesomeIcon icon="chevron-down" size="xs" />
                            </div>
                        </div>

                        <div class="relative">
                            <select v-model="filtros.asignado_id" @change="aplicarFiltros" class="w-full pl-6 pr-10 py-4 bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 rounded-2xl text-slate-900 dark:text-white appearance-none cursor-pointer focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-bold text-xs uppercase tracking-widest">
                                <option value="">Técnicos</option>
                                <option value="sin_asignar">Sin asignar</option>
                                <option v-for="u in usuarios" :key="u.id" :value="u.id">{{ u.name }}</option>
                            </select>
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                <FontAwesomeIcon icon="chevron-down" size="xs" />
                            </div>
                        </div>
                        
                        <button @click="limpiarFiltros" class="w-full py-4 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 hover:text-rose-500 transition-colors group">
                            <FontAwesomeIcon icon="times" class="mr-2 group-hover:rotate-90 transition-transform" />
                            Limpiar
                        </button>
                    </div>
                </div>

                <!-- Tabla de Tickets Premium -->
                <div class="bg-white dark:bg-slate-900/60 backdrop-blur-xl rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800/60 overflow-hidden animate-in fade-in duration-1000">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800/60">
                            <thead class="bg-slate-50/50 dark:bg-slate-950/50">
                                <tr>
                                    <th class="px-8 py-5 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Identificador</th>
                                    <th class="px-8 py-5 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Solicitante</th>
                                    <th class="px-8 py-5 text-center text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Prioridad</th>
                                    <th class="px-8 py-5 text-center text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Estado</th>
                                    <th class="px-8 py-5 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Responsable</th>
                                    <th class="px-8 py-5 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">SLA Status</th>
                                    <th class="px-8 py-5 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Registro</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 dark:divide-slate-800/40">
                                <tr 
                                    v-for="ticket in tickets.data" 
                                    :key="ticket.id" 
                                    class="group hover:bg-slate-50/50 dark:hover:bg-indigo-500/[0.02] cursor-pointer transition-all duration-300" 
                                    @click="router.visit(route('soporte.show', ticket.id))"
                                >
                                    <td class="px-8 py-6">
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 bg-indigo-500/10 px-2 py-0.5 rounded-lg tracking-wider">{{ ticket.numero }}</span>
                                            <div v-if="ticket.poliza_id" class="flex items-center gap-1.5 px-2 py-0.5 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-[10px] font-black uppercase tracking-tighter">
                                                <FontAwesomeIcon icon="shield-halved" class="text-[8px]" />
                                                Póliza
                                            </div>
                                        </div>
                                        <div class="text-sm font-black text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-amber-400 transition-colors truncate max-w-xs">{{ ticket.titulo }}</div>
                                    </td>
                                    
                                    <td class="px-8 py-6">
                                        <div class="text-xs font-black text-slate-900 dark:text-slate-200 uppercase tracking-tight">{{ ticket.cliente?.nombre || ticket.nombre_contacto || '-' }}</div>
                                        <div class="text-[10px] text-slate-500 dark:text-slate-500 font-bold uppercase tracking-widest mt-1">
                                            <FontAwesomeIcon icon="phone" class="mr-1 opacity-50" />
                                            {{ ticket.telefono_contacto || 'S/T' }}
                                        </div>
                                    </td>

                                    <td class="px-8 py-6 text-center">
                                        <span :class="['px-4 py-1.5 text-[9px] font-black rounded-full uppercase tracking-widest border-2 shadow-sm inline-block min-w-[90px]', getPrioridadBadge(ticket.prioridad)]">
                                            {{ ticket.prioridad }}
                                        </span>
                                    </td>

                                    <td class="px-8 py-6 text-center">
                                        <span :class="['px-4 py-1.5 text-[9px] font-black rounded-full uppercase tracking-widest border-2 shadow-sm inline-block min-w-[110px]', getEstadoBadge(ticket.estado)]">
                                            {{ ticket.estado.replace('_', ' ') }}
                                        </span>
                                    </td>

                                    <td class="px-8 py-6">
                                        <div class="flex items-center gap-3">
                                            <div class="relative">
                                                <div class="w-9 h-9 bg-slate-100 dark:bg-slate-800 rounded-xl flex items-center justify-center text-xs font-black text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 overflow-hidden">
                                                    {{ ticket.asignado?.name?.charAt(0) || '?' }}
                                                </div>
                                                <div v-if="ticket.asignado_id" class="absolute -bottom-1 -right-1 w-3.5 h-3.5 bg-emerald-500 border-2 border-white dark:border-slate-900 rounded-full"></div>
                                            </div>
                                            <span class="text-xs font-black text-slate-700 dark:text-slate-300 uppercase tracking-tight">
                                                {{ ticket.asignado?.name || 'Pendiente' }}
                                            </span>
                                        </div>
                                    </td>

                                    <td class="px-8 py-6">
                                        <div :class="['text-[10px] font-black uppercase tracking-[0.1em] flex items-center gap-2', getSlaStatusClass(ticket.sla_status)]">
                                            <div class="w-2 h-2 rounded-full bg-current shadow-[0_0_8px_rgba(currentColor)] animate-pulse"></div>
                                            {{ ticket.sla_status }}
                                        </div>
                                    </td>

                                    <td class="px-8 py-6 text-right">
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400 font-black uppercase tracking-widest">
                                            {{ new Date(ticket.created_at).toLocaleDateString('es-MX', { day: '2-digit', month: 'short' }) }}
                                        </div>
                                        <div class="text-[9px] text-slate-400 dark:text-slate-600 font-bold uppercase mt-1">Registrado</div>
                                    </td>
                                </tr>
                                
                                <tr v-if="tickets.data.length === 0">
                                    <td colspan="7" class="px-8 py-32 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-24 h-24 rounded-[2rem] bg-slate-50 dark:bg-slate-800/50 flex items-center justify-center text-5xl mb-6 grayscale opacity-40">
                                                🎫
                                            </div>
                                            <p class="text-slate-500 dark:text-slate-400 font-black uppercase tracking-[0.2em] text-sm">Sin tickets en esta sección</p>
                                            <Link :href="route('soporte.create')" class="mt-8 text-indigo-500 font-black text-[10px] uppercase tracking-widest hover:underline">Crear nuevo ticket ahora →</Link>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación Premium -->
                    <div v-if="tickets.last_page > 1" class="px-8 py-6 bg-slate-50/50 dark:bg-slate-950/50 border-t border-slate-100 dark:border-slate-800/60 flex flex-col md:flex-row justify-between items-center gap-6">
                        <span class="text-[11px] font-black text-slate-400 uppercase tracking-widest font-mono">
                            Pagina <span class="text-slate-900 dark:text-white">{{ tickets.current_page }}</span> de <span class="text-slate-900 dark:text-white">{{ tickets.last_page }}</span> • {{ tickets.total }} resultados
                        </span>
                        <div class="flex gap-2">
                            <Link 
                                v-for="link in tickets.links" 
                                :key="link.label"
                                :href="link.url"
                                :class="[
                                    'px-4 py-2 text-[10px] font-black rounded-xl transition-all uppercase tracking-widest',
                                    link.active 
                                        ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/20' 
                                        : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 shadow-sm'
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
