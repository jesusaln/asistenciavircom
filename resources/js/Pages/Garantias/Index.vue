<template>
    <Head title="Garantías - Series Vendidas" />

    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-gray-50 to-orange-50/30 p-4 md:p-6">
        <!-- Header Premium -->
        <div class="mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-orange-400 via-amber-500 to-yellow-500 flex items-center justify-center text-white shadow-lg shadow-orange-500/30">
                        <FontAwesomeIcon :icon="['fas', 'shield-alt']" class="h-7 w-7" />
                    </div>
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent">
                            Garantías
                        </h1>
                        <p class="text-gray-500 text-sm">Gestión de series vendidas y garantías</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Buscador rápido -->
                    <div class="relative">
                        <FontAwesomeIcon :icon="['fas', 'search']" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-4 h-4" />
                        <input 
                            v-model="searchTerm" 
                            type="text" 
                            placeholder="Buscar serie..." 
                            class="pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-orange-500 w-48 bg-white/80 backdrop-blur-sm"
                            @keyup.enter="aplicarFiltros"
                        />
                    </div>
                    <Link 
                        href="/garantias/buscar-serie" 
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-white text-gray-700 font-medium rounded-xl border border-gray-200 hover:bg-white hover:border-gray-300 transition-all"
                    >
                        <FontAwesomeIcon :icon="['fas', 'barcode']" />
                        Buscar Serie
                    </Link>
                    <Link 
                        href="/citas/create" 
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-orange-500 to-amber-500 text-white font-semibold rounded-xl hover:from-orange-600 hover:to-amber-600 transition-all shadow-lg shadow-orange-500/25 hover:shadow-orange-500/40"
                    >
                        <FontAwesomeIcon :icon="['fas', 'plus']" />
                        Nueva Cita
                    </Link>
                </div>
            </div>
        </div>

        <!-- Stats Cards Premium -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white/70 backdrop-blur-sm p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-lg shadow-blue-500/30">
                        <FontAwesomeIcon :icon="['fas', 'boxes']" class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Series</p>
                        <p class="text-2xl font-bold text-gray-900">{{ seriesVendidas.total }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white/70 backdrop-blur-sm p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 text-white shadow-lg shadow-green-500/30">
                        <FontAwesomeIcon :icon="['fas', 'check-circle']" class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Vigentes</p>
                        <p class="text-2xl font-bold text-green-600">{{ stats.vigentes }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white/70 backdrop-blur-sm p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-xl bg-gradient-to-br from-red-500 to-rose-600 text-white shadow-lg shadow-red-500/30">
                        <FontAwesomeIcon :icon="['fas', 'exclamation-triangle']" class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Vencidas</p>
                        <p class="text-2xl font-bold text-red-600">{{ stats.vencidas }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white/70 backdrop-blur-sm p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-lg shadow-amber-500/30">
                        <FontAwesomeIcon :icon="['fas', 'calendar-check']" class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Con Cita</p>
                        <p class="text-2xl font-bold text-amber-600">{{ stats.conCita }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros Expandibles -->
        <div class="bg-white/70 backdrop-blur-sm rounded-2xl border border-gray-100 shadow-sm mb-6 overflow-hidden">
            <button @click="showFilters = !showFilters" class="w-full px-5 py-4 flex items-center justify-between hover:bg-white/50 transition-colors">
                <div class="flex items-center gap-3">
                    <FontAwesomeIcon :icon="['fas', 'filter']" class="text-gray-400" />
                    <span class="font-medium text-gray-700">Filtros Avanzados</span>
                    <span v-if="activeFiltersCount" class="px-2 py-0.5 bg-orange-100 text-orange-700 text-xs font-bold rounded-full">
                        {{ activeFiltersCount }} activos
                    </span>
                </div>
                <FontAwesomeIcon :icon="['fas', showFilters ? 'chevron-up' : 'chevron-down']" class="text-gray-400" />
            </button>
            <div v-show="showFilters" class="px-5 pb-5 border-t border-gray-100">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Estado Serie</label>
                        <select v-model="filterEstado" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500">
                            <option value="">Todos</option>
                            <option value="vendido">Vendido</option>
                            <option value="en_stock">En Stock</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Garantía</label>
                        <select v-model="filterGarantia" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500">
                            <option value="">Todas</option>
                            <option value="vigente">Vigente</option>
                            <option value="vencida">Vencida</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Desde</label>
                        <input v-model="filterDesde" type="date" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Hasta</label>
                        <input v-model="filterHasta" type="date" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500" />
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-4">
                    <button @click="limpiarFiltros" class="px-4 py-2 text-gray-600 hover:text-gray-800 font-medium">
                        Limpiar
                    </button>
                    <button @click="aplicarFiltros" class="px-4 py-2 bg-orange-500 text-white rounded-xl hover:bg-orange-600 font-medium">
                        <FontAwesomeIcon :icon="['fas', 'search']" class="mr-2" />
                        Aplicar
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabla Premium -->
        <div class="bg-white/70 backdrop-blur-sm rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-bold text-gray-800 flex items-center gap-2">
                    <FontAwesomeIcon :icon="['fas', 'list']" class="text-orange-500" />
                    Series Vendidas
                </h3>
                <span class="text-sm text-gray-500">{{ seriesVendidas.from }}-{{ seriesVendidas.to }} de {{ seriesVendidas.total }}</span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-white/80">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Serie</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Producto</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Cliente</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Venta</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Garantía</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="serie in seriesVendidas.data" :key="serie.producto_serie_id" class="hover:bg-orange-50/30 transition-colors">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-gray-700 to-gray-900 text-white flex items-center justify-center text-xs font-bold">
                                        <FontAwesomeIcon :icon="['fas', 'barcode']" />
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ serie.numero_serie }}</p>
                                        <p class="text-xs text-gray-500">{{ serie.almacen_nombre || 'Sin almacén' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-medium text-gray-900 truncate max-w-[200px]">{{ serie.producto_nombre }}</p>
                                <p class="text-xs text-gray-500">{{ serie.producto_codigo }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-medium text-gray-900 truncate max-w-[180px]">{{ serie.cliente_nombre }}</p>
                                <p class="text-xs text-gray-500">{{ serie.cliente_email }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <Link :href="`/ventas/${serie.venta_id}`" class="text-blue-600 hover:text-blue-800 font-medium">
                                    #{{ serie.numero_venta }}
                                </Link>
                                <p class="text-xs text-gray-500">{{ formatFecha(serie.venta_fecha) }}</p>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <div v-if="serie.cita_id" class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-100 text-amber-700 rounded-lg text-xs font-semibold">
                                    <FontAwesomeIcon :icon="['fas', 'calendar-check']" />
                                    Cita #{{ serie.cita_id }}
                                </div>
                                <div v-else-if="serie.garantia_vigente" class="inline-flex items-center gap-1 px-2.5 py-1 bg-green-100 text-green-700 rounded-lg text-xs font-semibold">
                                    <FontAwesomeIcon :icon="['fas', 'check']" />
                                    {{ serie.dias_restantes_garantia }}d restantes
                                </div>
                                <div v-else class="inline-flex items-center gap-1 px-2.5 py-1 bg-red-100 text-red-700 rounded-lg text-xs font-semibold">
                                    <FontAwesomeIcon :icon="['fas', 'times']" />
                                    Vencida
                                </div>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span 
                                    class="px-2.5 py-1 rounded-lg text-xs font-semibold"
                                    :class="serie.estado_serie === 'vendido' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'"
                                >
                                    {{ serie.estado_serie === 'vendido' ? 'Vendido' : 'En Stock' }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <button 
                                        v-if="!serie.cita_id && serie.garantia_vigente"
                                        @click="crearCitaGarantia(serie.producto_serie_id)"
                                        class="p-2 rounded-lg bg-orange-50 text-orange-600 hover:bg-orange-100 transition-colors"
                                        title="Crear cita de garantía"
                                    >
                                        <FontAwesomeIcon :icon="['fas', 'calendar-plus']" />
                                    </button>
                                    <Link 
                                        v-if="serie.cita_id"
                                        :href="`/citas/${serie.cita_id}/edit`"
                                        class="p-2 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 transition-colors"
                                        title="Ver cita"
                                    >
                                        <FontAwesomeIcon :icon="['fas', 'eye']" />
                                    </Link>
                                    <Link 
                                        :href="`/ventas/${serie.venta_id}`"
                                        class="p-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors"
                                        title="Ver venta"
                                    >
                                        <FontAwesomeIcon :icon="['fas', 'receipt']" />
                                    </Link>
                                    <Link 
                                        :href="`/clientes/${serie.cliente_id}`"
                                        class="p-2 rounded-lg bg-green-50 text-green-600 hover:bg-green-100 transition-colors"
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
                                <FontAwesomeIcon :icon="['fas', 'box-open']" class="h-12 w-12 text-gray-300 mb-4" />
                                <p class="text-gray-500 font-medium">No se encontraron series</p>
                                <p class="text-sm text-gray-400 mt-1">Intenta ajustar los filtros</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación Premium -->
            <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between bg-white/50">
                <p class="text-sm text-gray-600">
                    Mostrando <span class="font-semibold">{{ seriesVendidas.from || 0 }}</span> a 
                    <span class="font-semibold">{{ seriesVendidas.to || 0 }}</span> de 
                    <span class="font-semibold">{{ seriesVendidas.total }}</span> resultados
                </p>
                <div class="flex items-center gap-2">
                    <Link 
                        v-if="seriesVendidas.prev_page_url"
                        :href="seriesVendidas.prev_page_url"
                        class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-white transition-colors"
                    >
                        <FontAwesomeIcon :icon="['fas', 'chevron-left']" class="mr-1" />
                        Anterior
                    </Link>
                    <Link 
                        v-if="seriesVendidas.next_page_url"
                        :href="seriesVendidas.next_page_url"
                        class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-white transition-colors"
                    >
                        Siguiente
                        <FontAwesomeIcon :icon="['fas', 'chevron-right']" class="ml-1" />
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import Swal from 'sweetalert2';

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
        const response = await fetch(`/garantias/${serieId}/crear-cita`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        const data = await response.json();
        if (response.ok && data.success) {
            window.location.href = data.url;
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
</script>
