<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import ModalGasto from './ModalGasto.vue';
import ImportXmlGastoModal from '@/Components/Gastos/ImportXmlGastoModal.vue';
import { useCompanyColors } from '@/Composables/useCompanyColors';

const props = defineProps({
    gastos: Object,
    categorias: Array,
    proyectos: Array,
    filters: Object,
});

const { colors, cssVars, headerGradientStyle, focusRingStyle, primaryButtonStyle } = useCompanyColors();

const search = ref(props.filters?.search || '');
const categoriaId = ref(props.filters?.categoria_id || '');
const proyectoId = ref(props.filters?.proyecto_id || '');
const estado = ref(props.filters?.estado || '');

const applyFilters = () => {
    router.get(route('gastos.index'), {
        search: search.value,
        categoria_id: categoriaId.value,
        proyecto_id: proyectoId.value,
        estado: estado.value,
    }, { preserveState: true });
};

const clearFilters = () => {
    search.value = '';
    categoriaId.value = '';
    proyectoId.value = '';
    estado.value = '';
    router.get(route('gastos.index'));
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const datePart = dateString.split('T')[0];
    const [year, month, day] = datePart.split('-');
    const date = new Date(year, month - 1, day);
    return date.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
};

const getEstadoBadge = (estado) => {
    const badges = {
        'procesada': 'bg-green-100 text-green-800',
        'cancelada': 'bg-red-100 text-red-800',
    };
    return badges[estado] || 'bg-gray-100 text-gray-800';
};

const estadoBadgeStyle = (valor) => {
    if (valor === 'procesada') {
        return { backgroundColor: `${colors.value.principal}20`, color: colors.value.principal };
    }
    if (valor === 'cancelada') {
        return { backgroundColor: '#fee2e2', color: '#b91c1c' };
    }
    return { backgroundColor: '#f3f4f6', color: '#4b5563' };
};

const stats = computed(() => {
    const total = props.gastos?.total || 0;
    const rows = Array.isArray(props.gastos?.data) ? props.gastos.data : [];
    const procesadas = rows.filter((g) => g.estado === 'procesada').length;
    const canceladas = rows.filter((g) => g.estado === 'cancelada').length;
    return { total, procesadas, canceladas };
});

const cancelGasto = (id) => {
    if (confirm('¿Estás seguro de cancelar este gasto?')) {
        router.post(route('gastos.cancel', id));
    }
};

const deleteGasto = (id) => {
    if (confirm('¿Estás seguro de eliminar este gasto?')) {
        router.delete(route('gastos.destroy', id));
    }
};

const showingModal = ref(false);
const selectedGasto = ref(null);

const showGasto = (gasto) => {
    selectedGasto.value = gasto;
    showingModal.value = true;
};

// Estado para modal de importación XML
const showImportXmlModal = ref(false);

const importarDesdeXml = () => {
  showImportXmlModal.value = true;
};

const handleXmlImport = (cfdiData) => {
  // Guardar datos del CFDI en sessionStorage para usar en Create
  sessionStorage.setItem('cfdi_gasto_import_data', JSON.stringify(cfdiData));
  
  // Redirigir a Create con parámetro de importación
  router.visit('/gastos/create?from_xml=1');
};

const closeModal = () => {
    showingModal.value = false;
    setTimeout(() => {
        selectedGasto.value = null;
    }, 200);
};
</script>

<template>
    <AppLayout title="Gastos Operativos">
        <Head title="Gastos Operativos" />

        <template #header>
            <div class="rounded-xl border border-gray-200/60 overflow-hidden" :style="cssVars">
                <div class="px-6 py-6 text-white" :style="headerGradientStyle">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-md" :style="headerGradientStyle">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold tracking-tight">Gastos Operativos</h2>
                                <p class="text-sm text-white/90 mt-0.5">Controla gastos, proveedores y categorias</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                @click="importarDesdeXml"
                                class="inline-flex items-center px-4 py-2 text-xs font-semibold uppercase tracking-widest rounded-lg text-white shadow-sm hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-white/70 focus:ring-offset-2 focus:ring-offset-transparent transition"
                                :style="primaryButtonStyle"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                Importar XML
                            </button>
                            <Link
                                :href="route('gastos.create')"
                                class="inline-flex items-center px-4 py-2 text-xs font-semibold uppercase tracking-widest rounded-lg text-white shadow-sm hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-white/70 focus:ring-offset-2 focus:ring-offset-transparent transition"
                                :style="primaryButtonStyle"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Nuevo Gasto
                            </Link>
                        </div>
                    </div>
                    <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="bg-white/85 backdrop-blur-sm rounded-xl p-4 border border-white/40">
                            <p class="text-sm font-medium text-gray-600">Total</p>
                            <p class="text-2xl font-bold" :style="{ color: colors.principal }">{{ stats.total }}</p>
                        </div>
                        <div class="bg-white/85 backdrop-blur-sm rounded-xl p-4 border border-white/40">
                            <p class="text-sm font-medium text-gray-600">Procesadas</p>
                            <p class="text-2xl font-bold" :style="{ color: colors.principal }">{{ stats.procesadas }}</p>
                        </div>
                        <div class="bg-white/85 backdrop-blur-sm rounded-xl p-4 border border-white/40">
                            <p class="text-sm font-medium text-gray-600">Canceladas</p>
                            <p class="text-2xl font-bold text-red-600">{{ stats.canceladas }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-6" :style="cssVars">
            <div class="px-4 sm:px-6 lg:px-8">
                <!-- Filtros -->
                <div class="bg-white shadow rounded-lg mb-6 p-4">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                            <input type="text" v-model="search" @keyup.enter="applyFilters"
                                placeholder="Número, descripción..."
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-2 focus:border-transparent"
                                :style="focusRingStyle"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                            <select v-model="categoriaId" @change="applyFilters"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-2 focus:border-transparent"
                                :style="focusRingStyle"
                            >
                                <option value="">Todas</option>
                                <option v-for="cat in categorias" :key="cat.id" :value="cat.id">
                                    {{ cat.nombre }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                            <select v-model="estado" @change="applyFilters"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-2 focus:border-transparent"
                                :style="focusRingStyle"
                            >
                                <option value="">Todos</option>
                                <option value="procesada">Procesado</option>
                                <option value="cancelada">Cancelado</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Proyecto</label>
                            <select v-model="proyectoId" @change="applyFilters"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-2 focus:border-transparent"
                                :style="focusRingStyle"
                            >
                                <option value="">Todos</option>
                                <option v-for="proyecto in proyectos" :key="proyecto.id" :value="proyecto.id">
                                    {{ proyecto.nombre }}
                                </option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button @click="clearFilters"
                                class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition focus:outline-none focus:ring-2 focus:border-transparent"
                                :style="focusRingStyle"
                            >
                                Limpiar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla -->
                <div class="bg-white shadow rounded-lg overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Número</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoría</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descripción</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Proveedor</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Proyecto</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Monto</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="gasto in gastos.data" :key="gasto.id" class="hover:bg-white">
                                <td class="px-3 py-4 whitespace-nowrap">
                                    <button @click="showGasto(gasto)" class="text-amber-600 hover:text-indigo-900 font-medium">
                                        {{ gasto.numero_compra }}
                                    </button>
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ formatDate(gasto.fecha_compra) }}
                                </td>
                                <td class="px-3 py-4 text-sm text-gray-900">
                                    {{ gasto.categoria_gasto?.nombre || '-' }}
                                </td>
                                <td class="px-3 py-4 text-sm text-gray-600 max-w-xs truncate" :title="gasto.notas">
                                    {{ gasto.notas || '-' }}
                                </td>
                                <td class="px-3 py-4 text-sm text-gray-500">
                                    {{ gasto.proveedor?.nombre_razon_social || 'Sin proveedor' }}
                                </td>
                                <td class="px-3 py-4 text-sm text-gray-500">
                                    <span v-if="gasto.proyecto" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                                        {{ gasto.proyecto.nombre }}
                                    </span>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                                <td class="px-3 py-4 text-sm text-gray-500">
                                    <div class="flex items-center gap-1">
                                        <div class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center text-[10px] uppercase font-bold text-gray-600">
                                            {{ gasto.created_by?.name?.charAt(0) || '?' }}
                                        </div>
                                        <span class="truncate max-w-[80px] text-xs">{{ gasto.created_by?.name || 'Sistema' }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">
                                    {{ formatCurrency(gasto.total) }}
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap text-center">
                                    <span :class="getEstadoBadge(gasto.estado)"
                                        class="px-2 py-1 text-xs font-semibold rounded-full"
                                        :style="estadoBadgeStyle(gasto.estado)"
                                    >
                                        {{ gasto.estado }}
                                    </span>
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <div class="flex justify-center gap-1">
                                        <!-- Ver -->
                                        <button @click="showGasto(gasto)"
                                            class="inline-flex items-center justify-center w-7 h-7 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition-colors focus:outline-none focus:ring-2 focus:border-transparent"
                                            :style="focusRingStyle"
                                            title="Ver detalles">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </button>
                                        <!-- Editar -->
                                        <Link v-if="gasto.estado?.toLowerCase() === 'procesada'" :href="route('gastos.edit', gasto.id)"
                                            class="inline-flex items-center justify-center w-7 h-7 bg-amber-100 text-amber-600 rounded hover:bg-amber-200 transition-colors focus:outline-none focus:ring-2 focus:border-transparent"
                                            :style="focusRingStyle"
                                            title="Editar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </Link>
                                        <!-- Cancelar -->
                                        <button v-if="gasto.estado?.toLowerCase() === 'procesada'" @click="cancelGasto(gasto.id)"
                                            class="inline-flex items-center justify-center w-7 h-7 bg-yellow-100 text-yellow-600 rounded hover:bg-yellow-200 transition-colors focus:outline-none focus:ring-2 focus:border-transparent"
                                            :style="focusRingStyle"
                                            title="Cancelar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                            </svg>
                                        </button>
                                        <!-- Eliminar -->
                                        <button @click="deleteGasto(gasto.id)"
                                            class="inline-flex items-center justify-center w-7 h-7 bg-red-100 text-red-600 rounded hover:bg-red-200 transition-colors focus:outline-none focus:ring-2 focus:border-transparent"
                                            :style="focusRingStyle"
                                            title="Eliminar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!gastos.data?.length">
                                <td colspan="10" class="px-6 py-12 text-center text-gray-500">
                                    No hay gastos registrados
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="gastos.links?.length > 3" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-700">
                                Mostrando {{ gastos.from }} a {{ gastos.to }} de {{ gastos.total }} resultados
                            </span>
                            <div class="flex gap-1">
                                <Link v-for="link in gastos.links" :key="link.label"
                                    :href="link.url || '#'"
                                    :class="[
                                        'px-3 py-1 text-sm border rounded',
                                        link.active ? 'text-white' : 'bg-white text-gray-700 border-gray-300 hover:bg-white',
                                        !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                    ]"
                                    :style="link.active ? headerGradientStyle : null"
                                    v-html="link.label" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Detalle -->
        <ModalGasto 
            :show="showingModal" 
            :gasto="selectedGasto" 
            @close="closeModal" 
        />

        <!-- Modal de importación XML -->
        <ImportXmlGastoModal
            :show="showImportXmlModal"
            @close="showImportXmlModal = false"
            @import="handleXmlImport"
        />
    </AppLayout>
</template>



