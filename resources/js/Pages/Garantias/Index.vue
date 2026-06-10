<template>
    <Head title="Garantías - Series Vendidas" />

    <div class="min-h-screen bg-[var(--ui-surface)] p-4 md:p-6 transition-colors">
        <!-- Header Premium -->
        <div class="mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-orange-400 via-brand-500 to-brand-500 flex items-center justify-center text-white shadow-xl shadow-brand-500/30">
                        <FontAwesomeIcon :icon="['fas', 'shield-alt']" class="h-7 w-7" />
                    </div>
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold bg-gradient-to-r from-slate-900 to-slate-600 dark:from-white dark:to-slate-300 bg-clip-text text-transparent">
                            Garantías
                        </h1>
                        <p class="text-slate-500 dark:text-slate-400 text-sm">Gestión de series vendidas y garantías</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Buscador rápido -->
                    <div class="relative">
                        <FontAwesomeIcon :icon="['fas', 'search']" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4" />
                        <input 
                            v-model="searchTerm" 
                            type="text" 
                            placeholder="Buscar serie..." 
                            class="pl-10 pr-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-brand-500 w-48 bg-white/80 dark:bg-slate-800 dark:text-white backdrop-blur-sm transition-colors"
                            @keyup.enter="aplicarFiltros"
                        />
                    </div>
                    <Link 
                        href="/garantias/buscar-serie" 
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-medium rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 hover:border-brand-500 dark:hover:border-brand-500 transition-all"
                    >
                        <FontAwesomeIcon :icon="['fas', 'barcode']" />
                        Buscar Serie
                    </Link>
                    <Link 
                        href="/citas/create" 
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-brand-500 to-brand-500 text-white font-semibold rounded-xl hover:from-brand-600 hover:to-brand-600 transition-all shadow-xl shadow-brand-500/25 hover:shadow-brand-500/40"
                    >
                        <FontAwesomeIcon :icon="['fas', 'plus']" />
                        Nueva Cita
                    </Link>
                </div>
            </div>
        </div>

        <!-- Stats Cards Premium -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white/70 dark:bg-slate-800/50 backdrop-blur-sm p-5 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-xl transition-all">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-xl bg-gradient-to-br from-brand-500 to-brand-600 text-white shadow-xl shadow-blue-500/30">
                        <FontAwesomeIcon :icon="['fas', 'boxes']" class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">Total Series</p>
                        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ seriesVendidas.total }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white/70 dark:bg-slate-800/50 backdrop-blur-sm p-5 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-xl transition-all">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-xl bg-gradient-to-br from-brand-500 to-brand-600 text-white shadow-xl shadow-emerald-500/20">
                        <FontAwesomeIcon :icon="['fas', 'check-circle']" class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">Vigentes</p>
                        <p class="text-2xl font-bold text-emerald-600 dark:text-slate-400">{{ stats.vigentes }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white/70 dark:bg-slate-800/50 backdrop-blur-sm p-5 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-xl transition-all">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-xl bg-gradient-to-br from-brand-500 to-brand-600 text-white shadow-xl shadow-rose-500/30">
                        <FontAwesomeIcon :icon="['fas', 'exclamation-triangle']" class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">Vencidas</p>
                        <p class="text-2xl font-bold text-rose-600 dark:text-rose-400">{{ stats.vencidas }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white/70 dark:bg-slate-800/50 backdrop-blur-sm p-5 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-xl transition-all">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-xl bg-gradient-to-br from-brand-500 to-brand-600 text-white shadow-xl shadow-brand-500/30">
                        <FontAwesomeIcon :icon="['fas', 'calendar-check']" class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">Con Cita</p>
                        <p class="text-2xl font-bold text-brand-600 dark:text-amber-400">{{ stats.conCita }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros Expandibles -->
        <div class="bg-white/70 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm mb-6 overflow-hidden transition-colors">
            <button @click="showFilters = !showFilters" class="w-full px-5 py-4 flex items-center justify-between hover:bg-white/50 dark:hover:bg-slate-700/50 transition-colors">
                <div class="flex items-center gap-2">
                    <FontAwesomeIcon :icon="['fas', 'filter']" class="text-slate-400 dark:text-slate-500" />
                    <span class="font-medium text-slate-700 dark:text-slate-200">Filtros Avanzados</span>
                    <span v-if="activeFiltersCount" class="px-2 py-0.5 bg-brand-100 dark:bg-brand-900/50 text-brand-700 dark:text-orange-300 text-xs font-bold rounded-full">
                        {{ activeFiltersCount }} activos
                    </span>
                </div>
                <FontAwesomeIcon :icon="['fas', showFilters ? 'chevron-up' : 'chevron-down']" class="text-slate-400 dark:text-slate-500" />
            </button>
            <div v-show="showFilters" class="px-5 pb-5 border-t border-slate-100 dark:border-slate-700">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pt-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Estado Serie</label>
                        <select v-model="filterEstado" class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 bg-white dark:bg-slate-700 dark:text-white transition-colors">
                            <option value="">Todos</option>
                            <option value="vendido">Vendido</option>
                            <option value="en_stock">En Stock</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Garantía</label>
                        <select v-model="filterGarantia" class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 bg-white dark:bg-slate-700 dark:text-white transition-colors">
                            <option value="">Todas</option>
                            <option value="vigente">Vigente</option>
                            <option value="vencida">Vencida</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Desde</label>
                        <input v-model="filterDesde" type="date" class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 bg-white dark:bg-slate-700 dark:text-white transition-colors" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Hasta</label>
                        <input v-model="filterHasta" type="date" class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 bg-white dark:bg-slate-700 dark:text-white transition-colors" />
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-4">
                    <button @click="limpiarFiltros" class="px-4 py-2 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 font-medium">
                        Limpiar
                    </button>
                    <button @click="aplicarFiltros" class="px-4 py-2 bg-brand-500 text-white rounded-xl hover:bg-brand-600 font-medium">
                        <FontAwesomeIcon :icon="['fas', 'search']" class="mr-2" />
                        Aplicar
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabla Premium -->
        <div class="bg-white/70 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden transition-colors">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 dark:text-white flex items-center gap-2">
                    <FontAwesomeIcon :icon="['fas', 'list']" class="text-orange-500" />
                    Series Vendidas
                </h3>
                <span class="text-sm text-slate-500 dark:text-slate-400">{{ seriesVendidas.from }}-{{ seriesVendidas.to }} de {{ seriesVendidas.total }}</span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Serie</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Producto</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cliente</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Venta</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Garantía</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                        <tr v-for="serie in seriesVendidas.data" :key="serie.producto_serie_id" class="hover:bg-orange-50/30 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-slate-700 to-slate-900 text-white flex items-center justify-center text-xs font-bold">
                                        <FontAwesomeIcon :icon="['fas', 'barcode']" />
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-900 dark:text-white">{{ serie.numero_serie }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ serie.almacen_nombre || 'Sin almacén' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-medium text-slate-900 dark:text-white truncate max-w-[200px]">{{ serie.producto_nombre }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ serie.producto_codigo }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-medium text-slate-900 dark:text-white truncate max-w-[180px]">{{ serie.cliente_nombre }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ serie.cliente_email }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <Link :href="`/ventas/${serie.venta_id}`" class="text-blue-600 dark:text-blue-400 hover:text-sky-800 dark:text-sky-200 dark:hover:text-blue-300 font-medium">
                                    #{{ serie.numero_venta }}
                                </Link>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ formatFecha(serie.venta_fecha) }}</p>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <div v-if="serie.cita_id" class="inline-flex items-center gap-1 px-2.5 py-1 bg-brand-50 dark:bg-brand-900/20/50 text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-brand-300 rounded-xl text-xs font-medium">
                                    <FontAwesomeIcon :icon="['fas', 'calendar-check']" />
                                    Cita #{{ serie.cita_id }}
                                </div>
                                <div v-else-if="serie.garantia_vigente" class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-100 dark:bg-slate-800/50 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-emerald-300 rounded-xl text-xs font-medium">
                                    <FontAwesomeIcon :icon="['fas', 'check']" />
                                    {{ serie.dias_restantes_garantia }}d restantes
                                </div>
                                <div v-else class="inline-flex items-center gap-1 px-2.5 py-1 bg-rose-50 dark:bg-rose-900/20/50 text-rose-800 dark:text-rose-200 dark:text-rose-200 dark:text-rose-300 rounded-xl text-xs font-medium">
                                    <FontAwesomeIcon :icon="['fas', 'times']" />
                                    Vencida
                                </div>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span 
                                    class="px-2.5 py-1 rounded-xl text-xs font-medium"
                                    :class="serie.estado_serie === 'vendido' ? 'bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300' : 'bg-blue-50 dark:bg-sky-900/20/50 text-sky-800 dark:text-sky-200 dark:text-blue-300'"
                                >
                                    {{ serie.estado_serie === 'vendido' ? 'Vendido' : 'En Stock' }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <button 
                                        v-if="!serie.cita_id && serie.garantia_vigente"
                                        @click="crearCitaGarantia(serie.producto_serie_id)"
                                        class="p-2 rounded-xl bg-orange-50 dark:bg-brand-900/30 text-brand-600 dark:text-orange-400 hover:bg-brand-100 dark:hover:bg-orange-800/50 transition-colors"
                                        title="Crear cita de garantía"
                                    >
                                        <FontAwesomeIcon :icon="['fas', 'calendar-plus']" />
                                    </button>
                                    <Link 
                                        v-if="serie.cita_id"
                                        :href="`/citas/${serie.cita_id}/edit`"
                                        class="p-2 rounded-xl bg-brand-50 dark:bg-brand-900/20 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400 hover:bg-brand-100 dark:hover:bg-brand-800/50 transition-colors"
                                        title="Ver cita"
                                    >
                                        <FontAwesomeIcon :icon="['fas', 'eye']" />
                                    </Link>
                                    <Link 
                                        :href="`/ventas/${serie.venta_id}`"
                                        class="p-2 rounded-xl bg-sky-50 dark:bg-sky-900/20 dark:bg-sky-900/30 text-blue-600 dark:text-blue-400 hover:bg-sky-100 dark:hover:bg-blue-800/50 transition-colors"
                                        title="Ver venta"
                                    >
                                        <FontAwesomeIcon :icon="['fas', 'receipt']" />
                                    </Link>
                                    <Link 
                                        :href="`/clientes/${serie.cliente_id}`"
                                        class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 dark:bg-slate-800/30 text-emerald-600 dark:text-slate-400 hover:bg-emerald-100 dark:hover:bg-emerald-800/50 transition-colors"
                                        title="Ver cliente"
                                    >
                                        <FontAwesomeIcon :icon="['fas', 'user']" />
                                    </Link>
                                </div>
                            </td>
                        </tr>
                        <!-- Empty state -->
                        <tr v-if="!seriesVendidas.data?.length">
                            <td colspan="7" class="px-5 py-16 text-center">
                                <FontAwesomeIcon :icon="['fas', 'box-open']" class="h-12 w-12 text-slate-300 dark:text-slate-500 mb-4" />
                                <p class="text-slate-500 dark:text-slate-400 font-medium">No se encontraron series</p>
                                <p class="text-sm text-slate-400 dark:text-slate-500 mt-1">Intenta ajustar los filtros</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación Premium -->
            <div class="px-5 py-4 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between bg-white/50 dark:bg-slate-700/50">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Mostrando <span class="font-semibold">{{ seriesVendidas.from || 0 }}</span> a 
                    <span class="font-semibold">{{ seriesVendidas.to || 0 }}</span> de 
                    <span class="font-semibold">{{ seriesVendidas.total }}</span> resultados
                </p>
                <div class="flex items-center gap-2">
                    <Link 
                        v-if="seriesVendidas.prev_page_url"
                        :href="seriesVendidas.prev_page_url"
                        class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-700 hover:border-brand-500 dark:hover:border-brand-500 transition-all shadow-sm"
                    >
                        <FontAwesomeIcon :icon="['fas', 'chevron-left']" class="mr-1" />
                        Anterior
                    </Link>
                    <Link 
                        v-if="seriesVendidas.next_page_url"
                        :href="seriesVendidas.next_page_url"
                        class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-700 hover:border-brand-500 dark:hover:border-brand-500 transition-all shadow-sm"
                    >
                        Siguiente
                        <FontAwesomeIcon :icon="['fas', 'chevron-right']" class="ml-1" />
                    </Link>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de elección -->
    <EleccionGarantiaModal 
        :show="mostrarModalEleccion"
        :serie="datosGarantiaParaModal?.serie"
        :cliente="datosGarantiaParaModal?.cliente"
        @close="mostrarModalEleccion = false"
        @select="onOpcionGarantiaSeleccionada"
    />
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import Swal from '@/Utils/Swal';
import EleccionGarantiaModal from '@/Components/Garantias/EleccionGarantiaModal.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    seriesVendidas: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({ vigentes: 0, vencidas: 0, conCita: 0 }) },
});

const searchTerm = ref(props.filters.search || '');
const showFilters = ref(false);
const filterEstado = ref(props.filters.estado || '');
const filterGarantia = ref(props.filters.garantia || '');
const filterDesde = ref(props.filters.fecha_desde || '');
const filterHasta = ref(props.filters.fecha_hasta || '');

// Estado para el modal de elección
const mostrarModalEleccion = ref(false);
const datosGarantiaParaModal = ref(null);
const responseDataGarantia = ref(null);

const activeFiltersCount = computed(() => {
    let count = 0;
    if (filterEstado.value) count++;
    if (filterGarantia.value) count++;
    if (filterDesde.value) count++;
    if (filterHasta.value) count++;
    return count;
});

const formatFecha = (fecha) => {
    if (!fecha) return '';
    return new Date(fecha).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
};

const aplicarFiltros = () => {
    const params = {};
    if (searchTerm.value) params.search = searchTerm.value;
    if (filterEstado.value) params.estado = filterEstado.value;
    if (filterGarantia.value) params.garantia = filterGarantia.value;
    if (filterDesde.value) params.fecha_desde = filterDesde.value;
    if (filterHasta.value) params.fecha_hasta = filterHasta.value;
    router.get('/garantias', params, { preserveState: true });
};

const limpiarFiltros = () => {
    searchTerm.value = '';
    filterEstado.value = '';
    filterGarantia.value = '';
    filterDesde.value = '';
    filterHasta.value = '';
    router.get('/garantias');
};

const crearCitaGarantia = async (serieId) => {
    try {
        const response = await fetch(route('garantias.crear-cita', serieId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        const data = await response.json();
        if (response.ok && data.success) {
            responseDataGarantia.value = data.data;
            datosGarantiaParaModal.value = {
                serie: data.data.numero_serie,
                cliente: data.data.cliente_nombre
            };
            mostrarModalEleccion.value = true;
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error al crear cita',
                text: data.mensaje || data.error || 'No se pudo crear la cita'
            });
            if (data.cita_id) window.location.reload();
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error interno',
            text: 'Error interno del servidor'
        });
    }
};

const onOpcionGarantiaSeleccionada = (opcion) => {
    mostrarModalEleccion.value = false;
    const d = responseDataGarantia.value;
    
    if (opcion === 'cita') {
        const params = new URLSearchParams({
            cliente_id: d.cliente_id,
            numero_serie: d.numero_serie,
            descripcion: `Garantía - Serie: ${d.numero_serie} - Producto: ${d.producto_nombre}`,
            direccion: d.direccion,
            tipo_servicio: 'garantia',
            producto_serie_id: d.producto_serie_id
        });
        window.location.href = route('citas.create') + '?' + params.toString();
    } else {
        const params = new URLSearchParams({
            cliente_id: d.cliente_id,
            cliente_nombre: d.cliente_nombre,
            cliente_telefono: d.cliente_telefono,
            equipo_serie: d.numero_serie,
            equipo_modelo: d.producto_nombre,
            equipo_marca: d.marca_nombre || d.producto_nombre,
            problema_reportado: `Garantía - Serie: ${d.numero_serie} - Producto: ${d.producto_nombre}`
        });
        window.location.href = route('taller.create') + '?' + params.toString();
    }
};
</script>
