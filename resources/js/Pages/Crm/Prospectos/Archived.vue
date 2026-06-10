<template>
    <Head title="Prospectos Archivados" />

    <div class="min-h-screen bg-white dark:bg-slate-900 dark:bg-gray-900 transition-colors">
        <div class="w-full px-4 lg:px-8 py-8">
            <div class="mb-6 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link href="/crm/prospectos" class="p-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 shadow-sm transition-all">
                        <FontAwesomeIcon :icon="['fas', 'arrow-left']" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <FontAwesomeIcon :icon="['fas', 'box-archive']" class="text-red-500" />
                            Prospectos Archivados
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Historial de prospectos fuera de la operación diaria</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="relative">
                        <FontAwesomeIcon :icon="['fas', 'search']" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-4 h-4" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Buscar archivado..."
                            class="pl-10 pr-4 py-2.5 w-64 border border-gray-200 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white transition-all"
                            @keyup.enter="handleSearch"
                        />
                    </div>

                    <select v-if="isAdmin" v-model="selectedVendedor" @change="handleSearch" class="px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white transition-all outline-none">
                        <option value="">Todos los vendedores</option>
                        <option v-for="vendedor in vendedores" :key="vendedor.id" :value="vendedor.id">{{ vendedor.name }}</option>
                    </select>

                    <button v-if="hasActiveFilters" @click="clearFilters" class="px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                        Limpiar filtros
                    </button>
                </div>
            </div>

            <div class="mb-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="rounded-2xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Archivados</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ prospectos.total }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Prospectos fuera del flujo diario</p>
                </div>
                <div class="rounded-2xl border border-red-100 dark:border-red-900/40 bg-red-50/70 dark:bg-red-900/10 p-4 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wider text-red-600 dark:text-red-400">Archivado reciente</p>
                    <p class="mt-2 text-2xl font-bold text-red-700 dark:text-red-300">{{ archivedThisMonth }}</p>
                    <p class="text-sm text-red-700/80 dark:text-red-300/80">Restaurables desde aquí</p>
                </div>
                <div class="rounded-2xl border border-emerald-100 dark:border-emerald-900/40 bg-emerald-50/70 dark:bg-emerald-900/10 p-4 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Convertidos</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ convertedCount }}</p>
                    <p class="text-sm text-emerald-700/80 dark:text-emerald-300/80">Ya tenían cliente asociado</p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-xl overflow-hidden transition-colors">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Prospecto</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Etapa Final</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cerrado</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Archivado</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Vendedor</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                            <tr v-for="prospecto in prospectos.data" :key="prospecto.id" class="hover:bg-red-50/30 dark:hover:bg-red-900/10 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center text-white font-bold text-sm shadow-sm">
                                            {{ getInitials(prospecto.nombre) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-900 dark:text-white">{{ prospecto.nombre }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ prospecto.empresa || prospecto.email || prospecto.telefono || 'Sin referencia' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="getEtapaBadge(prospecto.etapa)" class="px-3 py-1 rounded-full text-xs font-bold border">
                                        {{ formatEtapa(prospecto.etapa) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    {{ formatDate(prospecto.cerrado_at) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    {{ formatDate(prospecto.deleted_at) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    {{ prospecto.vendedor?.name || 'Sin asignar' }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <button @click="restoreProspecto(prospecto)" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 text-sm font-semibold transition-all">
                                            <FontAwesomeIcon :icon="['fas', 'rotate-left']" />
                                            Restaurar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!prospectos.data.length">
                                <td colspan="6" class="px-6 py-16 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center gap-4">
                                        <div class="w-20 h-20 rounded-full bg-gray-50 dark:bg-gray-700 flex items-center justify-center">
                                            <FontAwesomeIcon :icon="['fas', 'box-open']" class="h-10 w-10 opacity-30" />
                                        </div>
                                        <div>
                                            <p class="text-lg font-medium">No hay prospectos archivados</p>
                                            <p class="text-sm">Cuando archives o pase el periodo de 3 meses aparecerán aquí</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="prospectos.last_page > 1" class="px-6 py-6 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Mostrando {{ prospectos.from }} a {{ prospectos.to }} de {{ prospectos.total }} archivados
                    </span>
                    <div class="flex gap-1.5">
                        <Link
                            v-for="link in prospectos.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            v-html="link.label"
                            :class="[
                                'px-3.5 py-1.5 rounded-lg text-sm font-medium transition-all duration-200 border',
                                link.active ? 'bg-red-500 border-red-500 text-white shadow-sm ring-2 ring-red-500/20' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700',
                                !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : ''
                            ]"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

defineOptions({ layout: AppLayout });

const props = defineProps({
    prospectos: Object,
    vendedores: Array,
    isAdmin: Boolean,
    filtros: Object,
});

const search = ref(props.filtros.search || '');
const selectedVendedor = ref(props.filtros.vendedor_id || '');
let searchTimeout = null;
let skipNextWatch = false;

const handleSearch = () => {
    router.get('/crm/prospectos/archivados', {
        search: search.value || undefined,
        vendedor_id: selectedVendedor.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

watch(search, () => {
    if (skipNextWatch) {
        skipNextWatch = false;
        return;
    }

    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(handleSearch, 350);
});

const clearFilters = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    skipNextWatch = true;
    search.value = '';
    selectedVendedor.value = '';
    handleSearch();
};

const hasActiveFilters = computed(() => Boolean(search.value || selectedVendedor.value));
const archivedThisMonth = computed(() => props.prospectos.data.filter((prospecto) => {
    if (!prospecto.deleted_at) return false;
    const deletedAt = new Date(prospecto.deleted_at);
    const now = new Date();
    return deletedAt.getMonth() === now.getMonth() && deletedAt.getFullYear() === now.getFullYear();
}).length);
const convertedCount = computed(() => props.prospectos.data.filter((prospecto) => Boolean(prospecto.cliente_id)).length);

const getInitials = (nombre) => {
    if (!nombre) return '?';
    const parts = nombre.split(' ');
    return parts.length > 1 ? (parts[0][0] + (parts[1]?.[0] || '')).toUpperCase() : nombre.substring(0, 2).toUpperCase();
};

const formatDate = (fecha) => fecha ? new Date(fecha).toLocaleString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' }) : 'Sin fecha';

const formatEtapa = (etapa) => ({
    cerrado_ganado: 'Cerrado Ganado',
    cerrado_perdido: 'Cerrado Perdido',
}[etapa] || etapa);

const getEtapaBadge = (etapa) => ({
    cerrado_ganado: 'bg-emerald-50 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-700',
    cerrado_perdido: 'bg-red-50 dark:bg-red-900/40 text-red-700 dark:text-red-400 border-red-200 dark:border-red-700',
}[etapa] || 'bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700');

const restoreProspecto = (prospecto) => {
    if (!confirm(`¿Restaurar "${prospecto.nombre}" al CRM activo?`)) return;

    router.post(`/crm/prospectos/${prospecto.id}/restore`, {}, {
        preserveScroll: true,
    });
};
</script>
