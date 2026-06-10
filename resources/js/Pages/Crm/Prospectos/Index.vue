<template>
    <Head title="Listado de Prospectos" />

    <div class="prospectos-index min-h-screen bg-[var(--ui-surface)] transition-colors">
        <div class="w-full px-4 lg:px-8 py-8">
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4">
                <Link href="/crm" class="p-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-sm transition-all">
                    <FontAwesomeIcon :icon="['fas', 'arrow-left']" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2 transition-colors">
                        <FontAwesomeIcon :icon="['fas', 'users']" class="text-brand-500" />
                        Prospectos
                    </h1>
                    <p class="text-slate-500 dark:text-slate-400 text-sm transition-colors">Gestiona la lista completa de tus leads</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <Link href="/crm" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-medium rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 hover:border-brand-500 transition-all shadow-sm">
                    <FontAwesomeIcon :icon="['fas', 'columns']" />
                    Ver Tablero
                </Link>
                <!-- Buscador -->
                <div class="relative">
                    <FontAwesomeIcon :icon="['fas', 'search']" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500 w-4 h-4" />
                    <input 
                        v-model="search" 
                        type="text" 
                        placeholder="Buscar prospecto..." 
                        class="pl-10 pr-4 py-2.5 w-64 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-brand-500 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-500 dark:placeholder-slate-400 transition-all"
                        @keyup.enter="handleSearch"
                    />
                </div>

                <!-- Filtro Etapa -->
                <select v-model="selectedEtapa" @change="handleSearch" class="px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-brand-500 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 transition-all outline-none">
                    <option value="">Todas las etapas</option>
                    <option v-for="(label, key) in etapas" :key="key" :value="key">{{ label }}</option>
                </select>
            </div>
        </div>

        <!-- Tabla de Prospectos -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-xl overflow-hidden transition-colors">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead>
                        <tr class="bg-slate-50/50 dark:bg-black/50 border-b border-slate-100 dark:border-slate-700 transition-colors">
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Prospecto</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Contacto</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Etapa</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Prioridad</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-right">Valor</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Vendedor</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                        <tr v-for="prospecto in prospectos.data" :key="prospecto.id" class="hover:bg-slate-50/30 dark:hover:bg-brand-900/10 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div :class="getAvatarColor(prospecto.id)" class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-sm shadow-sm ring-1 ring-white/20">
                                        {{ getInitials(prospecto.nombre) }}
                                    </div>
                                    <div>
                                        <Link :href="`/crm/prospectos/${prospecto.id}`" class="font-bold text-slate-900 dark:text-white hover:text-brand-600 dark:hover:text-brand-400 block transition-colors">
                                            {{ prospecto.nombre }}
                                        </Link>
                                        <p v-if="prospecto.empresa" class="text-xs text-slate-500 dark:text-slate-400">{{ prospecto.empresa }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    <div v-if="prospecto.telefono" class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                        <FontAwesomeIcon :icon="['fas', 'phone']" class="w-3 text-brand-500" />
                                        {{ prospecto.telefono }}
                                    </div>
                                    <div v-if="prospecto.email" class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                        <FontAwesomeIcon :icon="['fas', 'envelope']" class="w-3 text-brand-500" />
                                        <span class="truncate max-w-[150px]">{{ prospecto.email }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="getEtapaBadge(prospecto.etapa)" class="px-3 py-1 rounded-xl text-xs font-bold border">
                                    {{ etapas[prospecto.etapa] }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="getPrioridadColor(prospecto.prioridad)" class="px-2 py-1 rounded-xl text-xs font-bold border">
                                    {{ prospecto.prioridad?.toUpperCase() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="font-bold text-emerald-600 dark:text-slate-400">${{ formatMonto(prospecto.valor_estimado) }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-[10px] font-bold text-slate-500 dark:text-slate-200">
                                        {{ getInitials(prospecto.vendedor?.name) }}
                                    </div>
                                    <span class="text-sm text-slate-700 dark:text-slate-200">{{ prospecto.vendedor?.name?.split(' ')[0] }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <Link :href="`/crm/prospectos/${prospecto.id}`" class="w-10 h-10 flex items-center justify-center rounded-xl bg-sky-50 dark:bg-sky-900/20 dark:bg-sky-900/40 text-blue-600 dark:text-blue-400 hover:bg-sky-100 dark:hover:bg-blue-900/60 transition-all" title="Ver Detalle">
                                        <FontAwesomeIcon :icon="['fas', 'eye']" />
                                    </Link>
                                    <button v-if="!prospecto.cliente_id" @click="convertir(prospecto)" class="w-10 h-10 flex items-center justify-center rounded-xl bg-brand-50 dark:bg-brand-900/20 dark:bg-brand-900/40 text-brand-600 dark:text-brand-400 hover:bg-brand-100 dark:hover:bg-brand-900/60 transition-all" title="Convertir a Cliente">
                                        <FontAwesomeIcon :icon="['fas', 'user-plus']" />
                                    </button>
                                    <button
                                        v-if="!prospecto.cliente_id"
                                        @click="eliminarProspecto(prospecto)"
                                        class="w-10 h-10 flex items-center justify-center rounded-xl bg-rose-50 dark:bg-rose-900/20 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900/60 transition-all"
                                        title="Eliminar Prospecto"
                                    >
                                        <FontAwesomeIcon :icon="['fas', 'trash']" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!prospectos.data.length">
                            <td colspan="7" class="px-6 py-16 text-center text-slate-500 dark:text-slate-400">
                                <div class="flex flex-col items-center gap-4">
                                    <div class="w-16 h-16 rounded-full bg-[var(--ui-surface)] dark:bg-slate-700 flex items-center justify-center">
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
            <div v-if="prospectos.last_page > 1" class="px-6 py-6 bg-slate-50/50 dark:bg-black/50 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between transition-colors">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                    Mostrando {{ prospectos.from }} a {{ prospectos.to }} de {{ prospectos.total }} leads
                </span>
                <div class="flex gap-1.5 font-sans">
                    <Link 
                        v-for="link in prospectos.links" 
                        :key="link.label"
                        :href="link.url || '#'"
                        v-html="link.label"
                        :class="[
                            'px-3.5 py-1.5 rounded-xl text-sm font-medium transition-all duration-200 border',
                            link.active ? 'bg-brand-500 border-brand-500 text-white shadow-sm ring-2 ring-brand-500/20' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700',
                            !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : ''
                        ]"
                    />
                </div>
            </div>
        </div>

        <ConfirmationModal :show="prospectoAEliminar != null" @close="cerrarModalEliminar" max-width="md">
            <template #title>
                Eliminar prospecto
            </template>

            <template #content>
                Se eliminara
                <span class="font-bold text-[var(--ui-text)]">{{ prospectoAEliminar?.nombre }}</span>
                junto con sus actividades y tareas relacionadas.
                <div class="mt-3 text-xs font-bold uppercase tracking-wide text-rose-500 dark:text-rose-400">
                    Esta accion no se puede deshacer
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="cerrarModalEliminar">
                    Cancelar
                </SecondaryButton>

                <DangerButton class="ms-3" @click="confirmarEliminarProspecto">
                    Eliminar
                </DangerButton>
            </template>
        </ConfirmationModal>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Swal from '@/Utils/Swal';

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
const prospectoAEliminar = ref(null);

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
        'bg-gradient-to-br from-brand-500 to-amber-600',
        'bg-gradient-to-br from-brand-500 to-amber-600',
        'bg-gradient-to-br from-brand-500 to-amber-600',
        'bg-gradient-to-br from-brand-500 to-amber-600',
        'bg-gradient-to-br from-brand-500 to-amber-600',
    ];
    return colors[id % colors.length];
};

const getEtapaBadge = (etapa) => {
    const styles = {
        prospecto: 'bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-700',
        contactado: 'bg-sky-50 dark:bg-sky-900/20 dark:bg-sky-900/40 text-blue-600 dark:text-blue-400 border-sky-200 dark:border-sky-800/30 dark:border-blue-700',
        interesado: 'bg-brand-50 dark:bg-brand-900/20 dark:bg-brand-900/40 text-brand-600 dark:text-brand-400 border-brand-200 dark:border-brand-800/30 dark:border-amber-700',
        cotizado: 'bg-purple-50 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 border-purple-200 dark:border-purple-700',
        negociacion: 'bg-orange-50 dark:bg-brand-900/40 text-brand-600 dark:text-orange-400 border-orange-200 dark:border-orange-700',
        cerrado_ganado: 'bg-emerald-50 dark:bg-emerald-900/20 dark:bg-slate-800/50 text-emerald-600 dark:text-slate-400 border-emerald-200 dark:border-emerald-800/30 dark:border-emerald-700',
        cerrado_perdido: 'bg-rose-50 dark:bg-rose-900/20 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-800/30 dark:border-rose-700'
    };
    return styles[etapa] || 'bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-700';
};

const getPrioridadColor = (prioridad) => {
    const styles = {
        alta: 'bg-rose-50 dark:bg-rose-900/20 dark:bg-rose-900/40 text-rose-800 dark:text-rose-200 dark:text-rose-200 dark:text-rose-400 border-rose-100 dark:border-rose-800/50',
        media: 'bg-brand-50 dark:bg-brand-900/20 dark:bg-brand-900/40 text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-brand-400 border-brand-100 dark:border-brand-800/50',
        baja: 'bg-emerald-50 dark:bg-emerald-900/20 dark:bg-slate-800/50 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-slate-400 border-emerald-100 dark:border-emerald-800/50'
    };
    return styles[prioridad] || 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-400 border-slate-100 dark:border-slate-700';
};

const convertir = async (prospecto) => {
    const { isConfirmed } = await Swal.fire({ title: 'Convertir a cliente', text: `¿Convertir "${prospecto.nombre}" a cliente?`, icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí, convertir', cancelButtonText: 'No' });
    if (!isConfirmed) return;

    router.post(`/crm/prospectos/${prospecto.id}/convertir`);
};

const eliminarProspecto = (prospecto) => {
    if (prospecto.cliente_id) return;
    prospectoAEliminar.value = prospecto;
};

const cerrarModalEliminar = () => {
    prospectoAEliminar.value = null;
};

const confirmarEliminarProspecto = () => {
    if (!prospectoAEliminar.value) return;

    router.delete(`/crm/prospectos/${prospectoAEliminar.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            cerrarModalEliminar();
        }
    });
};
</script>
