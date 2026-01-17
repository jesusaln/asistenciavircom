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
    incluir_cerrados: props.filtros?.incluir_cerrados || false,
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
    filtros.value = { buscar: '', estado: '', prioridad: '', asignado_id: '', categoria_id: '', incluir_cerrados: false };
    aplicarFiltros();
};

const getEstadoBadge = (estado) => {
    const colores = {
        abierto: 'bg-blue-100 text-blue-800',
        en_progreso: 'bg-yellow-100 text-yellow-800',
        pendiente: 'bg-orange-100 text-orange-800',
        resuelto: 'bg-green-100 text-green-800',
        cerrado: 'bg-gray-100 text-gray-800',
    };
    return colores[estado] || 'bg-gray-100 text-gray-800';
};

const getPrioridadBadge = (prioridad) => {
    const colores = {
        urgente: 'bg-red-100 text-red-800',
        alta: 'bg-orange-100 text-orange-800',
        media: 'bg-yellow-100 text-yellow-800',
        baja: 'bg-green-100 text-green-800',
    };
    return colores[prioridad] || 'bg-gray-100 text-gray-800';
};

const getSlaStatusClass = (status) => {
    const clases = {
        vencido: 'text-red-600 font-bold',
        critico: 'text-orange-600 font-semibold',
        advertencia: 'text-yellow-600',
        ok: 'text-green-600',
        completado: 'text-gray-500',
        sin_sla: 'text-gray-400',
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
                        <h1 class="text-2xl font-bold text-gray-900">Soporte Técnico</h1>
                        <p class="text-gray-600">Gestiona tickets de soporte y atención al cliente</p>
                    </div>
                    <div class="flex gap-3">
                        <Link :href="route('soporte.dashboard')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                            📊 Dashboard
                        </Link>
                        <Link :href="route('soporte.create')" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition shadow-lg">
                            + Nuevo Ticket
                        </Link>
                    </div>
                </div>

                <!-- Estadísticas rápidas -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                    <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 border-blue-500">
                        <div class="text-2xl font-bold text-gray-900">{{ stats.abiertos }}</div>
                        <div class="text-sm text-gray-500">Tickets Abiertos</div>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 border-yellow-500">
                        <div class="text-2xl font-bold text-gray-900">{{ stats.sin_asignar }}</div>
                        <div class="text-sm text-gray-500">Sin Asignar</div>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 border-red-500">
                        <div class="text-2xl font-bold text-gray-900">{{ stats.vencidos }}</div>
                        <div class="text-sm text-gray-500">SLA Vencido</div>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 border-green-500">
                        <div class="text-2xl font-bold text-gray-900">{{ stats.completados_hoy }}</div>
                        <div class="text-sm text-gray-500">Completados Hoy</div>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 border-gray-400 cursor-pointer hover:bg-white transition" @click="filtros.incluir_cerrados = !filtros.incluir_cerrados; aplicarFiltros()">
                        <div class="text-2xl font-bold text-gray-600">{{ stats.cerrados }}</div>
                        <div class="text-sm text-gray-500 flex items-center gap-1">
                            Cerrados
                            <span v-if="filtros.incluir_cerrados" class="text-xs text-green-600">✓ Mostrando</span>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
                        <input 
                            v-model="filtros.buscar"
                            type="text"
                            placeholder="Buscar ticket, cliente, teléfono..."
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500"
                            @keyup.enter="aplicarFiltros"
                        />
                        <select v-model="filtros.estado" class="w-full px-4 py-2 border rounded-lg" @change="aplicarFiltros">
                            <option v-for="e in estados" :key="e.value" :value="e.value">{{ e.label }}</option>
                        </select>
                        <select v-model="filtros.prioridad" class="w-full px-4 py-2 border rounded-lg" @change="aplicarFiltros">
                            <option v-for="p in prioridades" :key="p.value" :value="p.value">{{ p.label }}</option>
                        </select>
                        <select v-model="filtros.asignado_id" class="w-full px-4 py-2 border rounded-lg" @change="aplicarFiltros">
                            <option value="">Todos los técnicos</option>
                            <option value="sin_asignar">Sin asignar</option>
                            <option v-for="u in usuarios" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                        <select v-model="filtros.categoria_id" class="w-full px-4 py-2 border rounded-lg" @change="aplicarFiltros">
                            <option value="">Todas las categorías</option>
                            <option v-for="c in categorias" :key="c.id" :value="c.id">{{ c.nombre }}</option>
                        </select>
                        <!-- Toggle Cerrados -->
                        <label class="flex items-center gap-2 px-4 py-2 border rounded-lg cursor-pointer hover:bg-white" :class="filtros.incluir_cerrados ? 'bg-gray-100 border-gray-400' : ''">
                            <input 
                                type="checkbox" 
                                v-model="filtros.incluir_cerrados" 
                                @change="aplicarFiltros"
                                class="rounded text-orange-500 focus:ring-orange-500"
                            />
                            <span class="text-sm text-gray-600">Incluir cerrados</span>
                        </label>
                    </div>
                    <div class="flex justify-end mt-3">
                        <button @click="limpiarFiltros" class="text-sm text-gray-500 hover:text-gray-700">
                            Limpiar filtros
                        </button>
                    </div>
                </div>

                <!-- Lista de Tickets -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-white">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prioridad</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asignado</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SLA</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Creado</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-white cursor-pointer" @click="router.visit(route('soporte.show', ticket.id))">
                                    <td class="px-4 py-4">
                                        <div class="font-mono text-sm text-orange-600 flex items-center gap-1">
                                            {{ ticket.numero }}
                                            <font-awesome-icon v-if="ticket.poliza_id" icon="shield-halved" class="text-green-500 text-[10px]" title="Tiene Póliza" />
                                        </div>
                                        <div class="text-sm font-medium text-gray-900 truncate max-w-xs">{{ ticket.titulo }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="text-sm text-gray-900">{{ ticket.cliente?.nombre || ticket.nombre_contacto || '-' }}</div>
                                        <div class="text-xs text-gray-500">{{ ticket.telefono_contacto }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span :class="['px-2 py-1 text-xs font-medium rounded-full', getPrioridadBadge(ticket.prioridad)]">
                                            {{ ticket.prioridad }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span :class="['px-2 py-1 text-xs font-medium rounded-full', getEstadoBadge(ticket.estado)]">
                                            {{ ticket.estado.replace('_', ' ') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-500">
                                        {{ ticket.asignado?.name || 'Sin asignar' }}
                                    </td>
                                    <td class="px-4 py-4">
                                        <span :class="['text-xs', getSlaStatusClass(ticket.sla_status)]">
                                            {{ ticket.sla_status === 'vencido' ? '⚠️ Vencido' : ticket.sla_status === 'critico' ? '🔴 Crítico' : ticket.sla_status === 'advertencia' ? '🟡 Pronto' : ticket.sla_status === 'ok' ? '🟢 OK' : '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-500">
                                        {{ new Date(ticket.created_at).toLocaleDateString() }}
                                    </td>
                                </tr>
                                <tr v-if="tickets.data.length === 0">
                                    <td colspan="7" class="px-4 py-12 text-center text-gray-500">
                                        No hay tickets que mostrar
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div v-if="tickets.last_page > 1" class="px-4 py-3 bg-white border-t flex justify-between items-center">
                        <span class="text-sm text-gray-500">
                            Mostrando {{ tickets.from }} a {{ tickets.to }} de {{ tickets.total }} tickets
                        </span>
                        <div class="flex gap-1">
                            <Link 
                                v-for="link in tickets.links" 
                                :key="link.label"
                                :href="link.url"
                                :class="['px-3 py-1 text-sm rounded', link.active ? 'bg-orange-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-100']"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
