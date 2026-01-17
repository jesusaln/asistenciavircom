<template>
    <Head title="Listado de Prospectos" />

    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-gray-50 to-amber-50/30 p-4 md:p-6">
        <!-- Header -->
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4">
                <Link href="/crm" class="p-2 rounded-lg bg-white border border-gray-200 text-gray-600 hover:bg-white shadow-sm transition-all">
                    <FontAwesomeIcon :icon="['fas', 'arrow-left']" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <FontAwesomeIcon :icon="['fas', 'users']" class="text-amber-500" />
                        Prospectos
                    </h1>
                    <p class="text-gray-500 text-sm">Gestiona la lista completa de tus leads</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <!-- Buscador -->
                <div class="relative">
                    <FontAwesomeIcon :icon="['fas', 'search']" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-4 h-4" />
                    <input 
                        v-model="search" 
                        type="text" 
                        placeholder="Buscar por nombre, empresa..." 
                        class="pl-10 pr-4 py-2.5 w-64 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 bg-white/80 backdrop-blur-sm transition-all"
                        @keyup.enter="handleSearch"
                    />
                </div>

                <!-- Filtro Etapa -->
                <select v-model="selectedEtapa" @change="handleSearch" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 bg-white/80 backdrop-blur-sm transition-all">
                    <option value="">Todas las etapas</option>
                    <option v-for="(label, key) in etapas" :key="key" :value="key">{{ label }}</option>
                </select>
            </div>
        </div>

        <!-- Tabla de Prospectos -->
        <div class="bg-white/80 backdrop-blur-md rounded-2xl border border-gray-100 shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-white/50 border-b border-gray-100">
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Prospecto</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Contacto</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Etapa</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Prioridad</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Valor</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Vendedor</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="prospecto in prospectos.data" :key="prospecto.id" class="hover:bg-amber-50/30 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div :class="getAvatarColor(prospecto.id)" class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-sm shadow-sm">
                                        {{ getInitials(prospecto.nombre) }}
                                    </div>
                                    <div>
                                        <Link :href="`/crm/prospectos/${prospecto.id}`" class="font-bold text-gray-900 hover:text-amber-600 block transition-colors">
                                            {{ prospecto.nombre }}
                                        </Link>
                                        <p v-if="prospecto.empresa" class="text-xs text-gray-500">{{ prospecto.empresa }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    <div v-if="prospecto.telefono" class="flex items-center gap-2 text-xs text-gray-600">
                                        <FontAwesomeIcon :icon="['fas', 'phone']" class="w-3" />
                                        {{ prospecto.telefono }}
                                    </div>
                                    <div v-if="prospecto.email" class="flex items-center gap-2 text-xs text-gray-600">
                                        <FontAwesomeIcon :icon="['fas', 'envelope']" class="w-3" />
                                        <span class="truncate max-w-[150px]">{{ prospecto.email }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="getEtapaBadge(prospecto.etapa)" class="px-3 py-1 rounded-full text-xs font-bold border">
                                    {{ etapas[prospecto.etapa] }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="getPrioridadColor(prospecto.prioridad)" class="px-2 py-1 rounded-lg text-xs font-bold border">
                                    {{ prospecto.prioridad?.toUpperCase() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="font-bold text-emerald-600">${{ formatMonto(prospecto.valor_estimado) }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center text-[10px] font-bold text-gray-600">
                                        {{ getInitials(prospecto.vendedor?.name) }}
                                    </div>
                                    <span class="text-sm text-gray-700">{{ prospecto.vendedor?.name?.split(' ')[0] }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <Link :href="`/crm/prospectos/${prospecto.id}`" class="p-2 text-gray-400 hover:text-amber-500 transition-colors" title="Ver Detalle">
                                        <FontAwesomeIcon :icon="['fas', 'eye']" />
                                    </Link>
                                    <button v-if="!prospecto.cliente_id" @click="convertir(prospecto)" class="p-2 text-gray-400 hover:text-blue-500 transition-colors" title="Convertir a Cliente">
                                        <FontAwesomeIcon :icon="['fas', 'user-plus']" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!prospectos.data.length">
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <FontAwesomeIcon :icon="['fas', 'inbox']" class="h-12 w-12 mb-4 opacity-50" />
                                <p class="text-lg">No se encontraron prospectos</p>
                                <p class="text-sm">Intenta cambiar los filtros de búsqueda</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="prospectos.last_page > 1" class="px-6 py-4 bg-white/50 border-t border-gray-100 flex items-center justify-between">
                <span class="text-sm text-gray-500">
                    Mostrando {{ prospectos.from }} a {{ prospectos.to }} de {{ prospectos.total }} leads
                </span>
                <div class="flex gap-2">
                    <Link 
                        v-for="link in prospectos.links" 
                        :key="link.label"
                        :href="link.url || '#'"
                        v-html="link.label"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-sm transition-all',
                            link.active ? 'bg-amber-500 text-white font-bold' : 'bg-white text-gray-700 border border-gray-200 hover:bg-white',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

defineOptions({ layout: AppLayout });

const props = defineProps({
    prospectos: Object,
    etapas: Object,
    filtros: Object,
});

const search = ref(props.filtros.search || '');
const selectedEtapa = ref(props.filtros.etapa || '');

const handleSearch = () => {
    router.get('/crm/prospectos', {
        search: search.value,
        etapa: selectedEtapa.value
    }, {
        preserveState: true,
        replace: true
    });
};

const formatMonto = (valor) => Number(valor || 0).toLocaleString('es-MX', { minimumFractionDigits: 0, maximumFractionDigits: 0 });

const getInitials = (nombre) => {
    if (!nombre) return '?';
    const parts = nombre.split(' ');
    return parts.length > 1 ? (parts[0][0] + (parts[1] ? parts[1][0] : '')).toUpperCase() : nombre.substring(0, 2).toUpperCase();
};

const getAvatarColor = (id) => {
    const colors = [
        'bg-gradient-to-br from-blue-500 to-blue-600',
        'bg-gradient-to-br from-purple-500 to-purple-600',
        'bg-gradient-to-br from-emerald-500 to-emerald-600',
        'bg-gradient-to-br from-amber-500 to-amber-600',
        'bg-gradient-to-br from-rose-500 to-rose-600',
    ];
    return colors[id % colors.length];
};

const getEtapaBadge = (etapa) => {
    const styles = {
        prospecto: 'bg-white text-gray-600 border-gray-200',
        contactado: 'bg-blue-50 text-blue-600 border-blue-200',
        interesado: 'bg-yellow-50 text-yellow-600 border-yellow-200',
        cotizado: 'bg-purple-50 text-purple-600 border-purple-200',
        negociacion: 'bg-orange-50 text-orange-600 border-orange-200',
        cerrado_ganado: 'bg-green-50 text-green-600 border-green-200',
        cerrado_perdido: 'bg-red-50 text-red-600 border-red-200'
    };
    return styles[etapa] || 'bg-white text-gray-600 border-gray-200';
};

const getPrioridadColor = (prioridad) => {
    const styles = {
        alta: 'bg-red-50 text-red-700 border-red-100',
        media: 'bg-amber-50 text-amber-700 border-amber-100',
        baja: 'bg-green-50 text-green-700 border-green-100'
    };
    return styles[prioridad] || 'bg-white text-gray-700 border-gray-100';
};

const convertir = (prospecto) => {
    if (confirm(`¿Convertir "${prospecto.nombre}" a cliente?`)) {
        router.post(`/crm/prospectos/${prospecto.id}/convertir`);
    }
};
</script>
