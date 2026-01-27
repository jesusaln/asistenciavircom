<script setup>
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
        abierto: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-200 dark:border-blue-900/50',
        en_progreso: 'bg-yellow-500/10 text-yellow-600 dark:text-yellow-400 border-yellow-200 dark:border-yellow-900/50',
        pendiente: 'bg-orange-500/10 text-orange-600 dark:text-orange-400 border-orange-200 dark:border-orange-900/50',
        resuelto: 'bg-green-500/10 text-green-600 dark:text-green-400 border-green-200 dark:border-green-900/50',
        cerrado: 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800',
    };
    return colores[estado] || 'bg-slate-100 text-slate-800 dark:text-slate-100';
};

const getPrioridadBadge = (prioridad) => {
    const colores = {
        urgente: 'bg-red-500/10 text-red-600 dark:text-red-400 border-red-200 dark:border-red-900/50',
        alta: 'bg-orange-500/10 text-orange-600 dark:text-orange-400 border-orange-200 dark:border-orange-900/50',
        media: 'bg-yellow-500/10 text-yellow-600 dark:text-yellow-400 border-yellow-200 dark:border-yellow-900/50',
        baja: 'bg-green-500/10 text-green-600 dark:text-green-400 border-green-200 dark:border-green-900/50',
    };
    return colores[prioridad] || 'bg-slate-100 text-slate-600 border-slate-200';
};

const getSlaStatusClass = (status) => {
    const clases = {
        vencido: 'text-red-600 dark:text-red-400 font-bold',
        critico: 'text-orange-600 dark:text-orange-400 font-semibold',
        advertencia: 'text-yellow-600 dark:text-yellow-400',
        ok: 'text-green-600 dark:text-green-400',
        completado: 'text-gray-500 dark:text-gray-400 dark:text-gray-400',
        sin_sla: 'text-gray-400 dark:text-gray-500 dark:text-gray-400',
    };
    return clases[status] || '';
};
</script>

<template>
    <AppLayout title="Soporte">
        <Head title="Soporte - Tickets" />

        <div class="py-6">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-white transition-colors">Soporte Técnico</h1>
                        <p class="text-gray-600 dark:text-gray-300 dark:text-gray-400 transition-colors">Gestiona tickets de soporte y atención al cliente</p>
                    </div>
                    <div class="flex gap-3">
                        <Link :href="route('soporte.dashboard')" class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                            📊 Dashboard
                        </Link>
                        <Link :href="route('soporte.create')" class="px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition shadow-lg shadow-amber-500/20">
                            + Nuevo Ticket
                        </Link>
                    </div>
                </div>

                <!-- Estadísticas rápidas Premium -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                    <div class="bg-white dark:bg-slate-900/50 backdrop-blur-sm rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-slate-800 border-l-4 border-l-blue-500 transition-all hover:scale-[1.02]">
                        <div class="text-3xl font-black text-gray-900 dark:text-white">{{ stats.abiertos }}</div>
                        <div class="text-[10px] font-black text-gray-500 dark:text-slate-400 uppercase tracking-widest mt-1">Tickets Abiertos</div>
                    </div>
                    <div class="bg-white dark:bg-slate-900/50 backdrop-blur-sm rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-slate-800 border-l-4 border-l-yellow-500 transition-all hover:scale-[1.02]">
                        <div class="text-3xl font-black text-gray-900 dark:text-white">{{ stats.sin_asignar }}</div>
                        <div class="text-[10px] font-black text-gray-500 dark:text-slate-400 uppercase tracking-widest mt-1">Sin Asignar</div>
                    </div>
                    <div class="bg-white dark:bg-slate-900/50 backdrop-blur-sm rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-slate-800 border-l-4 border-l-red-500 transition-all hover:scale-[1.02]">
                        <div class="text-3xl font-black text-gray-900 dark:text-white">{{ stats.vencidos }}</div>
                        <div class="text-[10px] font-black text-gray-500 dark:text-slate-400 uppercase tracking-widest mt-1">SLA Vencido</div>
                    </div>
                    <div class="bg-white dark:bg-slate-900/50 backdrop-blur-sm rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-slate-800 border-l-4 border-l-green-500 transition-all hover:scale-[1.02]">
                        <div class="text-3xl font-black text-gray-900 dark:text-white">{{ stats.completados_hoy }}</div>
                        <div class="text-[10px] font-black text-gray-500 dark:text-slate-400 uppercase tracking-widest mt-1">Completados Hoy</div>
                    </div>
                    <div 
                        class="bg-white dark:bg-slate-900/50 backdrop-blur-sm rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-slate-800 border-l-4 border-l-slate-400 cursor-pointer hover:bg-orange-50/50 dark:hover:bg-orange-500/10 transition-all hover:scale-[1.02]" 
                        @click="filtros.incluir_finalizados = !filtros.incluir_finalizados; aplicarFiltros()"
                    >
                        <div class="text-3xl font-black text-gray-600 dark:text-slate-300">{{ stats.cerrados }}</div>
                        <div class="text-[10px] font-black text-gray-500 dark:text-slate-400 uppercase tracking-widest flex items-center gap-2 mt-1">
                            Finalizados
                            <span v-if="filtros.incluir_finalizados" class="text-[9px] text-green-600 dark:text-green-400 bg-green-500/10 border border-green-500/20 px-1.5 py-0.5 rounded-full">✓ VISTA ACTIVA</span>
                        </div>
                    </div>
                </div>

                <!-- Filtros Premium -->
                <div class="bg-white dark:bg-slate-900/50 backdrop-blur-sm rounded-2xl shadow-sm border border-gray-100 dark:border-slate-800 p-6 mb-8">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
                        <div class="relative lg:col-span-2">
                             <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
                             <input 
                                v-model="filtros.buscar"
                                type="text"
                                placeholder="Buscar ticket, cliente o folio..."
                                class="w-full pl-11 pr-4 py-3 bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-600 focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition-all"
                                @keyup.enter="aplicarFiltros"
                            />
                        </div>
                        <select v-model="filtros.estado" class="w-full px-4 py-3 bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 rounded-xl text-gray-900 dark:text-white focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition-all cursor-pointer font-medium" @change="aplicarFiltros">
                            <option v-for="e in estados" :key="e.value" :value="e.value">{{ e.label }}</option>
                        </select>
                        <select v-model="filtros.prioridad" class="w-full px-4 py-3 bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 rounded-xl text-gray-900 dark:text-white focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition-all cursor-pointer font-medium" @change="aplicarFiltros">
                            <option v-for="p in prioridades" :key="p.value" :value="p.value">{{ p.label }}</option>
                        </select>
                        <select v-model="filtros.asignado_id" class="w-full px-4 py-3 bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 rounded-xl text-gray-900 dark:text-white focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition-all cursor-pointer font-medium" @change="aplicarFiltros">
                            <option value="">Todos los técnicos</option>
                            <option value="sin_asignar">Sin asignar</option>
                            <option v-for="u in usuarios" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                        
                        <button @click="limpiarFiltros" class="w-full py-3 text-xs font-black uppercase tracking-widest text-gray-400 dark:text-slate-500 hover:text-orange-500 dark:hover:text-amber-400 transition-colors">
                            Limpiar Filtros ×
                        </button>
                    </div>
                </div>

                <!-- Lista de Tickets Premium -->
                <div class="bg-white dark:bg-slate-900/50 backdrop-blur-sm rounded-2xl shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
                            <thead class="bg-gray-50 dark:bg-slate-950/50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 dark:text-slate-400 uppercase tracking-widest transition-colors">Folio y Asunto</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 dark:text-slate-400 uppercase tracking-widest transition-colors">Cliente</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 dark:text-slate-400 uppercase tracking-widest transition-colors text-center">Prioridad</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 dark:text-slate-400 uppercase tracking-widest text-center">Estado</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 dark:text-slate-400 uppercase tracking-widest">Responsable</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 dark:text-slate-400 uppercase tracking-widest">SLA</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 dark:text-slate-400 uppercase tracking-widest">Fecha</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-transparent divide-y divide-gray-100 dark:divide-slate-800">
                                <tr 
                                    v-for="ticket in tickets.data" 
                                    :key="ticket.id" 
                                    class="group hover:bg-orange-50/30 dark:hover:bg-orange-500/5 cursor-pointer transition-all border-l-4 border-transparent hover:border-orange-500" 
                                    @click="router.visit(route('soporte.show', ticket.id))"
                                >
                                    <td class="px-6 py-5">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="font-mono text-xs font-black text-orange-600 dark:text-orange-400 bg-orange-500/10 px-2 py-0.5 rounded leading-none">{{ ticket.numero }}</span>
                                            <div v-if="ticket.poliza_id" class="text-[10px] bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 px-2 py-0.5 rounded font-black uppercase tracking-tighter flex items-center gap-1">
                                                <span>🛡️ PÓLIZA</span>
                                            </div>
                                        </div>
                                        <div class="text-sm font-black text-gray-900 dark:text-white group-hover:text-orange-600 dark:group-hover:text-amber-400 transition-colors truncate max-w-xs">{{ ticket.titulo }}</div>
                                    </td>
                                    <td class="px-6 py-5 focus-within:">
                                        <div class="text-sm font-bold text-gray-900 dark:text-slate-200">{{ ticket.cliente?.nombre || ticket.nombre_contacto || '-' }}</div>
                                        <div class="text-[10px] text-gray-500 dark:text-slate-500 font-medium uppercase tracking-wider mt-0.5">{{ ticket.telefono_contacto }}</div>
                                    </td>
                                    <td class="px-6 py-5 text-center">
                                        <span :class="['px-3 py-1 text-[10px] font-black rounded-full uppercase tracking-widest border shadow-sm', getPrioridadBadge(ticket.prioridad)]">
                                            {{ ticket.prioridad }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-5 text-center">
                                        <span :class="['px-3 py-1 text-[10px] font-black rounded-full uppercase tracking-widest border shadow-sm', getEstadoBadge(ticket.estado)]">
                                            {{ ticket.estado.replace('_', ' ') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="flex items-center gap-2">
                                            <div class="w-7 h-7 bg-gray-100 dark:bg-slate-800 rounded-full flex items-center justify-center text-[10px] font-black text-gray-600 dark:text-slate-400 border border-gray-200 dark:border-slate-700">
                                                {{ ticket.asignado?.name?.charAt(0) || '?' }}
                                            </div>
                                            <span class="text-sm font-bold text-gray-700 dark:text-slate-300">
                                                {{ ticket.asignado?.name || 'Por asignar' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div :class="['text-[11px] font-black uppercase tracking-wider flex items-center gap-1.5', getSlaStatusClass(ticket.sla_status)]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-current animate-pulse"></span>
                                            {{ ticket.sla_status }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 text-xs text-gray-500 dark:text-slate-500 font-medium uppercase tracking-tighter">
                                        {{ new Date(ticket.created_at).toLocaleDateString() }}
                                    </td>
                                </tr>
                                <tr v-if="tickets.data.length === 0">
                                    <td colspan="7" class="px-6 py-24 text-center">
                                        <div class="text-5xl mb-4">✨</div>
                                        <p class="text-gray-500 dark:text-slate-400 font-black uppercase tracking-widest text-sm">No hay tickets activos en esta vista</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación Premium -->
                    <div v-if="tickets.last_page > 1" class="px-4 py-4 bg-gray-50 dark:bg-slate-950 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700 flex justify-between items-center transition-colors">
                        <span class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 transition-colors">
                            Mostrando {{ tickets.from }} a {{ tickets.to }} de {{ tickets.total }} tickets
                        </span>
                        <div class="flex gap-1">
                            <Link 
                                v-for="link in tickets.links" 
                                :key="link.label"
                                :href="link.url"
                                :class="[
                                    'px-3 py-1.5 text-sm font-medium rounded-lg transition-colors',
                                    link.active 
                                        ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/20' 
                                        : 'bg-white dark:bg-slate-900 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-slate-800 dark:border-gray-600'
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
