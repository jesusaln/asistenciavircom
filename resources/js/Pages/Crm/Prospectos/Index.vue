<template>
    <Head title="Listado de Prospectos" />

    <div class="prospectos-index min-h-screen bg-white dark:bg-slate-900 dark:bg-gray-900 transition-colors">
        <div class="w-full px-4 lg:px-8 py-8">
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4">
                <Link href="/crm" class="p-2 rounded-lg bg-white dark:bg-slate-900 dark:bg-gray-800 border border-gray-200 dark:border-slate-800 dark:border-gray-700 text-gray-600 dark:text-gray-300 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-700 shadow-sm transition-all">
                    <FontAwesomeIcon :icon="['fas', 'arrow-left']" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-white flex items-center gap-2 transition-colors">
                        <FontAwesomeIcon :icon="['fas', 'users']" class="text-amber-500" />
                        Prospectos
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400 text-sm transition-colors">Gestiona la lista completa de tus leads</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <!-- Buscador -->
                <div class="relative">
                    <FontAwesomeIcon :icon="['fas', 'search']" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500 dark:text-gray-400 w-4 h-4" />
                    <input 
                        v-model="search" 
                        type="text" 
                        placeholder="Buscar por nombre, empresa, teléfono o email..." 
                        class="pl-10 pr-4 py-2.5 w-64 border border-gray-200 dark:border-slate-800 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 bg-white dark:bg-slate-900 dark:bg-gray-800 text-gray-900 dark:text-white dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 transition-all"
                        @keyup.enter="handleSearch"
                    />
                    <button v-if="search" @click="clearSearch" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <FontAwesomeIcon :icon="['fas', 'times']" class="w-4 h-4" />
                    </button>
                </div>

                <!-- Filtro Etapa -->
                <select v-model="selectedEtapa" @change="handleSearch" class="px-4 py-2.5 border border-gray-200 dark:border-slate-800 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 bg-white dark:bg-slate-900 dark:bg-gray-800 text-gray-900 dark:text-white dark:text-gray-100 transition-all outline-none">
                    <option value="">Todas las etapas</option>
                    <option v-for="(label, key) in etapas" :key="key" :value="key">{{ label }}</option>
                </select>

                <!-- Filtro Prioridad -->
                <select v-model="selectedPrioridad" @change="handleSearch" class="px-4 py-2.5 border border-gray-200 dark:border-slate-800 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 bg-white dark:bg-slate-900 dark:bg-gray-800 text-gray-900 dark:text-white dark:text-gray-100 transition-all outline-none">
                    <option value="">Todas las prioridades</option>
                    <option v-for="(label, key) in prioridades" :key="key" :value="key">{{ label }}</option>
                </select>

                <select v-model="selectedOrigen" @change="handleSearch" class="px-4 py-2.5 border border-gray-200 dark:border-slate-800 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 bg-white dark:bg-slate-900 dark:bg-gray-800 text-gray-900 dark:text-white dark:text-gray-100 transition-all outline-none">
                    <option value="">Todos los orígenes</option>
                    <option v-for="(label, key) in origenes" :key="key" :value="key">{{ label }}</option>
                </select>

                <select v-if="isAdmin" v-model="selectedVendedor" @change="handleSearch" class="px-4 py-2.5 border border-gray-200 dark:border-slate-800 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 bg-white dark:bg-slate-900 dark:bg-gray-800 text-gray-900 dark:text-white dark:text-gray-100 transition-all outline-none">
                    <option value="">Todos los vendedores</option>
                    <option v-for="vendedor in vendedores" :key="vendedor.id" :value="vendedor.id">{{ vendedor.name }}</option>
                </select>

                <label class="inline-flex items-center gap-2 px-3 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800">
                    <input v-model="showClosed" type="checkbox" @change="handleSearch" class="rounded border-gray-300 text-amber-500 focus:ring-amber-500" />
                    Mostrar cerrados
                </label>

                <Link href="/crm/prospectos/archivados" class="px-4 py-2.5 rounded-xl border border-red-200 dark:border-red-900/40 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all">
                    Archivados
                </Link>

                <button v-if="hasActiveFilters" @click="clearFilters" class="px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                    Limpiar filtros
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
            <div class="rounded-2xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Resultados</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ prospectos.total }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Prospectos con filtros actuales</p>
            </div>
            <div class="rounded-2xl border border-amber-100 dark:border-amber-900/40 bg-amber-50/70 dark:bg-amber-900/10 p-4 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Seguimiento Vencido</p>
                <p class="mt-2 text-2xl font-bold text-amber-700 dark:text-amber-300">{{ overdueCount }}</p>
                <p class="text-sm text-amber-700/80 dark:text-amber-300/80">Prospectos de esta página requieren atención</p>
            </div>
            <div class="rounded-2xl border border-blue-100 dark:border-blue-900/40 bg-blue-50/70 dark:bg-blue-900/10 p-4 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">Sin Próxima Actividad</p>
                <p class="mt-2 text-2xl font-bold text-blue-700 dark:text-blue-300">{{ withoutNextActivityCount }}</p>
                <p class="text-sm text-blue-700/80 dark:text-blue-300/80">Prospectos que pueden estancarse</p>
            </div>
            <div class="rounded-2xl border border-emerald-100 dark:border-emerald-900/40 bg-emerald-50/70 dark:bg-emerald-900/10 p-4 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Seguimiento Hoy</p>
                <p class="mt-2 text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ todayCount }}</p>
                <p class="text-sm text-emerald-700/80 dark:text-emerald-300/80">Prospectos programados para hoy</p>
            </div>
        </div>

        <!-- Tabla de Prospectos -->
        <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-xl overflow-hidden transition-colors">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-slate-950/50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700 transition-colors">
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">Prospecto</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">Contacto</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">Etapa</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">Prioridad</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">Seguimiento</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">Sin Contacto</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider text-right">Valor</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">Vendedor</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider text-center">Acciones</th>
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
                                        <Link :href="`/crm/prospectos/${prospecto.id}`" class="font-bold text-gray-900 dark:text-white dark:text-white hover:text-amber-600 dark:hover:text-amber-400 block transition-colors">
                                            {{ prospecto.nombre }}
                                        </Link>
                                        <p v-if="prospecto.empresa" class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ prospecto.empresa }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    <div v-if="prospecto.telefono" class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300 dark:text-gray-400">
                                        <FontAwesomeIcon :icon="['fas', 'phone']" class="w-3 text-amber-500" />
                                        {{ prospecto.telefono }}
                                    </div>
                                    <div v-if="prospecto.email" class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300 dark:text-gray-400">
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
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    <span :class="getSeguimientoBadge(prospecto)" class="inline-flex items-center gap-2 px-2.5 py-1 rounded-lg text-xs font-bold border w-fit">
                                        <span class="w-2 h-2 rounded-full bg-current opacity-70"></span>
                                        {{ getSeguimientoLabel(prospecto) }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ getSeguimientoSubtext(prospecto) }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="getDaysWithoutContactBadge(prospecto)" class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border">
                                    {{ getDaysWithoutContact(prospecto) }} días
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="font-bold text-emerald-600 dark:text-emerald-400">${{ formatMonto(prospecto.valor_estimado) }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-[10px] font-bold text-gray-600 dark:text-gray-300 dark:text-gray-300">
                                        {{ getInitials(prospecto.vendedor?.name) }}
                                    </div>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ prospecto.vendedor?.name?.split(' ')[0] }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <a v-if="prospecto.telefono" :href="`tel:${prospecto.telefono}`" class="w-8 h-8 flex items-center justify-center rounded-lg bg-green-50 dark:bg-green-900/40 text-green-600 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/60 transition-all" title="Llamar">
                                        <FontAwesomeIcon :icon="['fas', 'phone']" />
                                    </a>
                                    <a v-if="prospecto.telefono" :href="`https://wa.me/52${cleanPhone(prospecto.telefono)}`" target="_blank" class="w-8 h-8 flex items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 transition-all" title="WhatsApp">
                                        <FontAwesomeIcon :icon="['fab', 'whatsapp']" />
                                    </a>
                                    <a v-if="prospecto.email" :href="`mailto:${prospecto.email}`" class="w-8 h-8 flex items-center justify-center rounded-lg bg-purple-50 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 hover:bg-purple-100 dark:hover:bg-purple-900/60 transition-all" title="Email">
                                        <FontAwesomeIcon :icon="['fas', 'envelope']" />
                                    </a>
                                    <button @click="openActividadModal(prospecto)" class="w-8 h-8 flex items-center justify-center rounded-lg bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 transition-all" title="Registrar Actividad">
                                        <FontAwesomeIcon :icon="['fas', 'note-sticky']" />
                                    </button>
                                    <Link :href="`/crm/prospectos/${prospecto.id}`" class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/60 transition-all" title="Ver Detalle">
                                        <FontAwesomeIcon :icon="['fas', 'eye']" />
                                    </Link>
                                    <button v-if="!prospecto.cliente_id" @click="convertir(prospecto)" class="w-8 h-8 flex items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-900/60 transition-all" title="Convertir a Cliente">
                                        <FontAwesomeIcon :icon="['fas', 'user-plus']" />
                                    </button>
                                    <button v-if="isAdmin || $page.props.auth.user.id === prospecto.vendedor_id" @click="eliminarProspecto(prospecto)" class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-50 dark:bg-red-900/40 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/60 transition-all" title="Eliminar">
                                        <FontAwesomeIcon :icon="['fas', 'trash']" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!prospectos.data.length">
                            <td colspan="9" class="px-6 py-16 text-center text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                <div class="flex flex-col items-center gap-4">
                                    <div class="w-20 h-20 rounded-full bg-gray-50 dark:bg-slate-950 dark:bg-gray-700 flex items-center justify-center">
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
            <div v-if="prospectos.last_page > 1" class="px-6 py-6 bg-gray-50 dark:bg-slate-950/50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between transition-colors">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
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
                            link.active ? 'bg-amber-500 border-amber-500 text-white shadow-sm ring-2 ring-amber-500/20' : 'bg-white dark:bg-slate-900 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-slate-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-700',
                            !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : ''
                        ]"
                    />
                </div>
            </div>
        </div>

        <div v-if="showActividadModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeActividadModal">
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>
                <div class="relative bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Registrar Actividad</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ actividadProspecto?.nombre }}</p>
                        </div>
                        <button @click="closeActividadModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <FontAwesomeIcon :icon="['fas', 'times']" />
                        </button>
                    </div>

                    <form @submit.prevent="registrarActividadRapida" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">Tipo</label>
                                <select v-model="formActividad.tipo" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white">
                                    <option v-for="(label, key) in tiposActividad" :key="key" :value="key">{{ label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">Resultado</label>
                                <select v-model="formActividad.resultado" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white">
                                    <option value="">Seleccionar...</option>
                                    <option v-for="(label, key) in resultadosActividad" :key="key" :value="key">{{ label }}</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">Notas</label>
                            <textarea v-model="formActividad.notas" rows="4" class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white" placeholder="Resumen de la interacción..."></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">Próxima Actividad</label>
                            <input v-model="formActividad.proxima_actividad_at" type="datetime-local" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white" />
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="closeActividadModal" class="px-4 py-2.5 rounded-xl text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Cancelar</button>
                            <button type="submit" :disabled="savingActividad" class="px-5 py-2.5 rounded-xl bg-amber-500 text-white font-semibold hover:bg-amber-600 disabled:opacity-50">
                                <FontAwesomeIcon v-if="savingActividad" :icon="['fas', 'spinner']" class="animate-spin mr-2" />
                                Guardar actividad
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
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

const props = defineProps({
    prospectos: Object,
    etapas: Object,
    prioridades: Object,
    origenes: Object,
    vendedores: Array,
    isAdmin: Boolean,
    tiposActividad: Object,
    resultadosActividad: Object,
    filtros: Object,
});

const search = ref(props.filtros.search || '');
const selectedEtapa = ref(props.filtros.etapa || '');
const selectedPrioridad = ref(props.filtros.prioridad || '');
const selectedOrigen = ref(props.filtros.origen || '');
const selectedVendedor = ref(props.filtros.vendedor_id || '');
const showClosed = ref(Boolean(props.filtros.show_closed));
const showActividadModal = ref(false);
const savingActividad = ref(false);
const actividadProspecto = ref(null);
const formActividad = ref(initActividadForm());
let searchTimeout = null;
let skipNextSearchWatch = false;

function initActividadForm() {
    return {
        tipo: 'llamada',
        resultado: '',
        notas: '',
        proxima_actividad_at: '',
    };
}

const handleSearch = () => {
    router.get('/crm/prospectos', {
        search: search.value || undefined,
        etapa: selectedEtapa.value || undefined,
        prioridad: selectedPrioridad.value || undefined,
        origen: selectedOrigen.value || undefined,
        vendedor_id: selectedVendedor.value || undefined,
        show_closed: showClosed.value ? 1 : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

watch(search, () => {
    if (skipNextSearchWatch) {
        skipNextSearchWatch = false;
        return;
    }

    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        handleSearch();
    }, 350);
});

const clearSearch = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    skipNextSearchWatch = true;
    search.value = '';
    handleSearch();
};

const clearFilters = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    skipNextSearchWatch = true;
    search.value = '';
    selectedEtapa.value = '';
    selectedPrioridad.value = '';
    selectedOrigen.value = '';
    selectedVendedor.value = '';
    showClosed.value = false;
    handleSearch();
};

onBeforeUnmount(() => {
    if (observer) observer.disconnect()
    if (searchTimeout) clearTimeout(searchTimeout);
})

const hasActiveFilters = computed(() => Boolean(
    search.value ||
    selectedEtapa.value ||
    selectedPrioridad.value ||
    selectedOrigen.value ||
    selectedVendedor.value ||
    showClosed.value
));
const overdueCount = computed(() => props.prospectos.data.filter((prospecto) => isOverdue(prospecto)).length);
const withoutNextActivityCount = computed(() => props.prospectos.data.filter((prospecto) => !prospecto.proxima_actividad_at).length);
const todayCount = computed(() => props.prospectos.data.filter((prospecto) => isDueToday(prospecto)).length);

const formatMonto = (valor) => Number(valor || 0).toLocaleString('es-MX', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
const cleanPhone = (telefono) => String(telefono || '').replace(/\D/g, '');
const formatShortDate = (fecha) => new Date(fecha).toLocaleDateString('es-MX', { day: '2-digit', month: 'short' });

const isOverdue = (prospecto) => {
    if (!prospecto.proxima_actividad_at) return false;
    return new Date(prospecto.proxima_actividad_at) < new Date();
};

const isDueToday = (prospecto) => {
    if (!prospecto.proxima_actividad_at) return false;
    return new Date(prospecto.proxima_actividad_at).toDateString() === new Date().toDateString();
};

const getSeguimientoLabel = (prospecto) => {
    if (isOverdue(prospecto)) return 'Vencido';
    if (isDueToday(prospecto)) return 'Hoy';
    if (prospecto.proxima_actividad_at) return 'Programado';
    return 'Sin agenda';
};

const getSeguimientoSubtext = (prospecto) => {
    if (prospecto.proxima_actividad_at) {
        return `Próxima actividad ${formatShortDate(prospecto.proxima_actividad_at)}`;
    }

    if (prospecto.ultima_actividad_at) {
        return `Última actividad ${formatShortDate(prospecto.ultima_actividad_at)}`;
    }

    return 'Sin actividad registrada';
};

const getSeguimientoBadge = (prospecto) => {
    if (isOverdue(prospecto)) return 'bg-red-50 dark:bg-red-900/40 text-red-700 dark:text-red-400 border-red-200 dark:border-red-700';
    if (isDueToday(prospecto)) return 'bg-amber-50 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-700';
    if (prospecto.proxima_actividad_at) return 'bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-700';
    return 'bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700';
};

const getDaysWithoutContact = (prospecto) => {
    const baseDate = prospecto.ultima_actividad_at || prospecto.created_at;
    if (!baseDate) return 0;
    const diff = Math.floor((new Date() - new Date(baseDate)) / (1000 * 60 * 60 * 24));
    return Math.max(0, diff);
};

const getDaysWithoutContactBadge = (prospecto) => {
    const days = getDaysWithoutContact(prospecto);
    if (days >= 7) return 'bg-red-50 dark:bg-red-900/40 text-red-700 dark:text-red-400 border-red-200 dark:border-red-700';
    if (days >= 3) return 'bg-amber-50 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-700';
    return 'bg-emerald-50 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-700';
};

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
        prospecto: 'bg-white dark:bg-slate-900 dark:bg-gray-800 text-gray-600 dark:text-gray-300 dark:text-gray-400 border-gray-200 dark:border-slate-800 dark:border-gray-700',
        contactado: 'bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 border-blue-200 dark:border-blue-700',
        interesado: 'bg-yellow-50 dark:bg-yellow-900/40 text-yellow-600 dark:text-yellow-400 border-yellow-200 dark:border-yellow-700',
        cotizado: 'bg-purple-50 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 border-purple-200 dark:border-purple-700',
        negociacion: 'bg-orange-50 dark:bg-orange-900/40 text-orange-600 dark:text-orange-400 border-orange-200 dark:border-orange-700',
        cerrado_ganado: 'bg-green-50 dark:bg-green-900/40 text-green-600 dark:text-green-400 border-green-200 dark:border-green-700',
        cerrado_perdido: 'bg-red-50 dark:bg-red-900/40 text-red-600 dark:text-red-400 border-red-200 dark:border-red-700'
    };
    return styles[etapa] || 'bg-white dark:bg-slate-900 dark:bg-gray-800 text-gray-600 dark:text-gray-300 dark:text-gray-400 border-gray-200 dark:border-slate-800 dark:border-gray-700';
};

const getPrioridadColor = (prioridad) => {
    const styles = {
        alta: 'bg-red-50 dark:bg-red-900/40 text-red-700 dark:text-red-400 border-red-100 dark:border-red-800/50',
        media: 'bg-amber-50 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400 border-amber-100 dark:border-amber-800/50',
        baja: 'bg-green-50 dark:bg-green-900/40 text-green-700 dark:text-green-400 border-green-100 dark:border-green-800/50'
    };
    return styles[prioridad] || 'bg-white dark:bg-slate-900 dark:bg-gray-800 text-gray-700 dark:text-gray-400 border-gray-100 dark:border-gray-700';
};

const convertir = (prospecto) => {
    if (confirm(`¿Convertir "${prospecto.nombre}" a cliente?`)) {
        router.post(`/crm/prospectos/${prospecto.id}/convertir`);
    }
};

const openActividadModal = (prospecto) => {
    actividadProspecto.value = prospecto;
    formActividad.value = initActividadForm();
    showActividadModal.value = true;
};

const closeActividadModal = () => {
    actividadProspecto.value = null;
    formActividad.value = initActividadForm();
    showActividadModal.value = false;
};

const registrarActividadRapida = () => {
    if (!actividadProspecto.value) return;

    savingActividad.value = true;
    router.post(`/crm/prospectos/${actividadProspecto.value.id}/actividad`, formActividad.value, {
        preserveScroll: true,
        onSuccess: () => {
            savingActividad.value = false;
            closeActividadModal();
        },
        onError: () => {
            savingActividad.value = false;
        }
    });
};

const eliminarProspecto = (prospecto) => {
    if (!confirm(`¿Estás seguro de que deseas eliminar el prospecto "${prospecto.nombre}"? Esta acción eliminará también sus actividades y tareas.`)) return;

    router.delete(`/crm/prospectos/${prospecto.id}`, {
        preserveScroll: true,
    });
};
</script>
