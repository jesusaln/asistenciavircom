<template>
    <Head title="Listado de Prospectos" />

    <div class="prospectos-index min-h-screen bg-white dark:bg-gray-900 transition-colors">
        <div class="w-full px-4 lg:px-8 py-8">
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4">
                <Link href="/crm" class="p-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 shadow-sm transition-all">
                    <FontAwesomeIcon :icon="['fas', 'arrow-left']" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2 transition-colors">
                        <FontAwesomeIcon :icon="['fas', 'users']" class="text-amber-500" />
                        Prospectos
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 text-sm transition-colors">Gestiona la lista completa de tus leads</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <!-- Buscador -->
                <div class="relative">
                    <FontAwesomeIcon :icon="['fas', 'search']" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500 w-4 h-4" />
                    <input 
                        v-model="search" 
                        type="text" 
                        placeholder="Buscar prospecto..." 
                        class="pl-10 pr-4 py-2.5 w-64 border border-gray-200 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 transition-all"
                        @keyup.enter="handleSearch"
                    />
                </div>

                <!-- Filtro Etapa -->
                <select v-model="selectedEtapa" @change="handleSearch" class="px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 transition-all outline-none">
                    <option value="">Todas las etapas</option>
                    <option v-for="(label, key) in etapas" :key="key" :value="key">{{ label }}</option>
                </select>
            </div>
        </div>

        <!-- Tabla de Prospectos -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-xl overflow-hidden transition-colors">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50/50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700 transition-colors">
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Prospecto</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contacto</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Etapa</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Prioridad</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Valor</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Vendedor</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50 transition-colors">
                        <tr v-for="prospecto in prospectos.data" :key="prospecto.id" class="hover:bg-amber-50/30 dark:hover:bg-amber-900/10 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div :class="getAvatarColor(prospecto.id)" class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-sm shadow-sm ring-1 ring-white/20">
                                        {{ getInitials(prospecto.nombre) }}
                                    </div>
                                    <div>
                                        <Link :href="`/crm/prospectos/${prospecto.id}`" class="font-bold text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400 block transition-colors">
                                            {{ prospecto.nombre }}
                                        </Link>
                                        <p v-if="prospecto.empresa" class="text-xs text-gray-500 dark:text-gray-400">{{ prospecto.empresa }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    <div v-if="prospecto.telefono" class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                        <FontAwesomeIcon :icon="['fas', 'phone']" class="w-3 text-amber-500" />
                                        {{ prospecto.telefono }}
                                    </div>
                                    <div v-if="prospecto.email" class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                        <FontAwesomeIcon :icon="['fas', 'envelope']" class="w-3 text-amber-500" />
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
                                <span class="font-bold text-emerald-600 dark:text-emerald-400">${{ formatMonto(prospecto.valor_estimado) }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-[10px] font-bold text-gray-600 dark:text-gray-300">
                                        {{ getInitials(prospecto.vendedor?.name) }}
                                    </div>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ prospecto.vendedor?.name?.split(' ')[0] }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <Link :href="`/crm/prospectos/${prospecto.id}`" class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/60 transition-all" title="Ver Detalle">
                                        <FontAwesomeIcon :icon="['fas', 'eye']" />
                                    </Link>
                                    <button v-if="!prospecto.cliente_id" @click="convertir(prospecto)" class="w-8 h-8 flex items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-900/60 transition-all" title="Convertir a Cliente">
                                        <FontAwesomeIcon :icon="['fas', 'user-plus']" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!prospectos.data.length">
                            <td colspan="7" class="px-6 py-16 text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center gap-4">
                                    <div class="w-20 h-20 rounded-full bg-gray-50 dark:bg-gray-700 flex items-center justify-center">
                                        <FontAwesomeIcon :icon="['fas', 'inbox']" class="h-10 w-10 opacity-30 dark:opacity-50" />
                                    </div>
                                    <div>
                                        <p class="text-lg font-medium">No se encontraron prospectos</p>
                                        <p class="text-sm">Intenta cambiar los filtros de búsqueda</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="prospectos.last_page > 1" class="px-6 py-6 bg-gray-50/50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between transition-colors">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Mostrando {{ prospectos.from }} a {{ prospectos.to }} de {{ prospectos.total }} leads
                </span>
                <div class="flex gap-1.5 font-sans">
                    <Link 
                        v-for="link in prospectos.links" 
                        :key="link.label"
                        :href="link.url || '#'"
                        v-html="link.label"
                        :class="[
                            'px-3.5 py-1.5 rounded-lg text-sm font-medium transition-all duration-200 border',
                            link.active ? 'bg-amber-500 border-amber-500 text-white shadow-sm ring-2 ring-amber-500/20' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700',
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
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

defineOptions({ layout: AppLayout });

// Estado reactivo para Modo Oscuro
const isDark = ref(false)
let observer = null

onMounted(() => {
  isDark.value = document.documentElement.classList.contains('dark')
  observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      if (mutation.attributeName === 'class') {
        isDark.value = document.documentElement.classList.contains('dark')
      }
    })
  })
  observer.observe(document.documentElement, { attributes: true })
})

onBeforeUnmount(() => {
  if (observer) observer.disconnect()
})

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
        prospecto: 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700',
        contactado: 'bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 border-blue-200 dark:border-blue-700',
        interesado: 'bg-yellow-50 dark:bg-yellow-900/40 text-yellow-600 dark:text-yellow-400 border-yellow-200 dark:border-yellow-700',
        cotizado: 'bg-purple-50 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 border-purple-200 dark:border-purple-700',
        negociacion: 'bg-orange-50 dark:bg-orange-900/40 text-orange-600 dark:text-orange-400 border-orange-200 dark:border-orange-700',
        cerrado_ganado: 'bg-green-50 dark:bg-green-900/40 text-green-600 dark:text-green-400 border-green-200 dark:border-green-700',
        cerrado_perdido: 'bg-red-50 dark:bg-red-900/40 text-red-600 dark:text-red-400 border-red-200 dark:border-red-700'
    };
    return styles[etapa] || 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700';
};

const getPrioridadColor = (prioridad) => {
    const styles = {
        alta: 'bg-red-50 dark:bg-red-900/40 text-red-700 dark:text-red-400 border-red-100 dark:border-red-800/50',
        media: 'bg-amber-50 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400 border-amber-100 dark:border-amber-800/50',
        baja: 'bg-green-50 dark:bg-green-900/40 text-green-700 dark:text-green-400 border-green-100 dark:border-green-800/50'
    };
    return styles[prioridad] || 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-400 border-gray-100 dark:border-gray-700';
};

const convertir = (prospecto) => {
    if (confirm(`¿Convertir "${prospecto.nombre}" a cliente?`)) {
        router.post(`/crm/prospectos/${prospecto.id}/convertir`);
    }
};
</script>
