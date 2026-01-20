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
        abierto: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        en_progreso: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        pendiente: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
        resuelto: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        cerrado: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400',
    };
    return colores[estado] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400';
};

const getPrioridadBadge = (prioridad) => {
    const colores = {
        urgente: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        alta: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
        media: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        baja: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    };
    return colores[prioridad] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400';
};

const getSlaStatusClass = (status) => {
    const clases = {
        vencido: 'text-red-600 dark:text-red-400 font-bold',
        critico: 'text-orange-600 dark:text-orange-400 font-semibold',
        advertencia: 'text-yellow-600 dark:text-yellow-400',
        ok: 'text-green-600 dark:text-green-400',
        completado: 'text-gray-500 dark:text-gray-400',
        sin_sla: 'text-gray-400 dark:text-gray-500',
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
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors">Soporte Técnico</h1>
                        <p class="text-gray-600 dark:text-gray-400 transition-colors">Gestiona tickets de soporte y atención al cliente</p>
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
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm dark:shadow-none border border-gray-100 dark:border-gray-700 border-l-4 border-l-blue-500 transition-colors">
                        <div class="text-2xl font-black text-gray-900 dark:text-white transition-colors">{{ stats.abiertos }}</div>
                        <div class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest transition-colors">Tickets Abiertos</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm dark:shadow-none border border-gray-100 dark:border-gray-700 border-l-4 border-l-yellow-500 transition-colors">
                        <div class="text-2xl font-black text-gray-900 dark:text-white transition-colors">{{ stats.sin_asignar }}</div>
                        <div class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest transition-colors">Sin Asignar</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm dark:shadow-none border border-gray-100 dark:border-gray-700 border-l-4 border-l-red-500 transition-colors">
                        <div class="text-2xl font-black text-gray-900 dark:text-white transition-colors">{{ stats.vencidos }}</div>
                        <div class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest transition-colors">SLA Vencido</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm dark:shadow-none border border-gray-100 dark:border-gray-700 border-l-4 border-l-green-500 transition-colors">
                        <div class="text-2xl font-black text-gray-900 dark:text-white transition-colors">{{ stats.completados_hoy }}</div>
                        <div class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest transition-colors">Completados Hoy</div>
                    </div>
                    <div 
                        class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm dark:shadow-none border border-gray-100 dark:border-gray-700 border-l-4 border-l-gray-400 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors" 
                        @click="filtros.incluir_finalizados = !filtros.incluir_finalizados; aplicarFiltros()"
                    >
                        <div class="text-2xl font-black text-gray-600 dark:text-gray-300 transition-colors">{{ stats.cerrados }}</div>
                        <div class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest flex items-center gap-2 transition-colors">
                            Finalizados
                            <span v-if="filtros.incluir_finalizados" class="text-[10px] text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/30 px-1.5 py-0.5 rounded">✓</span>
                        </div>
                    </div>
                </div>

                <!-- Filtros Premium -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm dark:shadow-none border border-gray-100 dark:border-gray-700 p-5 mb-6 transition-colors">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
                        <input 
                            v-model="filtros.buscar"
                            type="text"
                            placeholder="Buscar ticket, cliente, teléfono..."
                            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-colors"
                            @keyup.enter="aplicarFiltros"
                        />
                        <select v-model="filtros.estado" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 transition-colors" @change="aplicarFiltros">
                            <option v-for="e in estados" :key="e.value" :value="e.value">{{ e.label }}</option>
                        </select>
                        <select v-model="filtros.prioridad" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 transition-colors" @change="aplicarFiltros">
                            <option v-for="p in prioridades" :key="p.value" :value="p.value">{{ p.label }}</option>
                        </select>
                        <select v-model="filtros.asignado_id" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 transition-colors" @change="aplicarFiltros">
                            <option value="">Todos los técnicos</option>
                            <option value="sin_asignar">Sin asignar</option>
                            <option v-for="u in usuarios" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                        <select v-model="filtros.categoria_id" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 transition-colors" @change="aplicarFiltros">
                            <option value="">Todas las categorías</option>
                            <option v-for="c in categorias" :key="c.id" :value="c.id">{{ c.nombre }}</option>
                        </select>
                        <!-- Toggle Finalizados Premium -->
                        <label 
                            class="flex items-center gap-2 px-4 py-2.5 border rounded-xl cursor-pointer transition-colors" 
                            :class="filtros.incluir_finalizados 
                                ? 'bg-amber-50 dark:bg-amber-900/20 border-amber-300 dark:border-amber-700' 
                                : 'bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800'"
                        >
                            <input 
                                type="checkbox" 
                                v-model="filtros.incluir_finalizados" 
                                @change="aplicarFiltros"
                                class="rounded text-amber-500 focus:ring-amber-500 dark:bg-gray-800 dark:border-gray-600"
                            />
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-300 transition-colors">Incluir finalizados</span>
                        </label>
                    </div>
                    <div class="flex justify-end mt-3">
                        <button @click="limpiarFiltros" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                            Limpiar filtros
                        </button>
                    </div>
                </div>

                <!-- Lista de Tickets Premium -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm dark:shadow-none border border-gray-100 dark:border-gray-700 overflow-hidden transition-colors">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest transition-colors">Ticket</th>
                                    <th class="px-4 py-3 text-left text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest transition-colors">Cliente</th>
                                    <th class="px-4 py-3 text-left text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest transition-colors">Prioridad</th>
                                    <th class="px-4 py-3 text-left text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest transition-colors">Estado</th>
                                    <th class="px-4 py-3 text-left text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest transition-colors">Asignado</th>
                                    <th class="px-4 py-3 text-left text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest transition-colors">SLA</th>
                                    <th class="px-4 py-3 text-left text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest transition-colors">Creado</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700 transition-colors">
                                <tr 
                                    v-for="ticket in tickets.data" 
                                    :key="ticket.id" 
                                    class="hover:bg-gray-50 dark:hover:bg-gray-750 cursor-pointer transition-colors" 
                                    @click="router.visit(route('soporte.show', ticket.id))"
                                >
                                    <td class="px-4 py-4">
                                        <div class="font-mono text-sm text-amber-600 dark:text-amber-400 flex items-center gap-1 transition-colors">
                                            {{ ticket.numero }}
                                            <font-awesome-icon v-if="ticket.poliza_id" icon="shield-halved" class="text-green-500 dark:text-green-400 text-[10px]" title="Tiene Póliza" />
                                        </div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-xs transition-colors">{{ ticket.titulo }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="text-sm text-gray-900 dark:text-white transition-colors">{{ ticket.cliente?.nombre || ticket.nombre_contacto || '-' }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 transition-colors">{{ ticket.telefono_contacto }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span :class="['px-2.5 py-1 text-xs font-bold rounded-full uppercase tracking-wide', getPrioridadBadge(ticket.prioridad)]">
                                            {{ ticket.prioridad }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span :class="['px-2.5 py-1 text-xs font-bold rounded-full uppercase tracking-wide', getEstadoBadge(ticket.estado)]">
                                            {{ ticket.estado.replace('_', ' ') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300 transition-colors">
                                        {{ ticket.asignado?.name || 'Sin asignar' }}
                                    </td>
                                    <td class="px-4 py-4">
                                        <span :class="['text-xs font-medium', getSlaStatusClass(ticket.sla_status)]">
                                            {{ ticket.sla_status === 'vencido' ? '⚠️ Vencido' : ticket.sla_status === 'critico' ? '🔴 Crítico' : ticket.sla_status === 'advertencia' ? '🟡 Pronto' : ticket.sla_status === 'ok' ? '🟢 OK' : '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400 transition-colors">
                                        {{ new Date(ticket.created_at).toLocaleDateString() }}
                                    </td>
                                </tr>
                                <tr v-if="tickets.data.length === 0">
                                    <td colspan="7" class="px-4 py-16 text-center">
                                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl transition-colors">
                                            ✨
                                        </div>
                                        <p class="text-gray-500 dark:text-gray-400 font-medium transition-colors">No hay tickets que mostrar</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación Premium -->
                    <div v-if="tickets.last_page > 1" class="px-4 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700 flex justify-between items-center transition-colors">
                        <span class="text-sm text-gray-500 dark:text-gray-400 transition-colors">
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
                                        : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-600'
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
