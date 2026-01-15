<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import ShowPoliza from './Show.vue';
import axios from 'axios';

const props = defineProps({
    polizas: Object,
    filters: Object,
});

const filtros = ref({
    search: props.filters?.search || '',
    estado: props.filters?.estado || '',
});

const aplicarFiltros = () => {
    router.get(route('polizas-servicio.index'), filtros.value, { preserveState: true });
};

const limpiarFiltros = () => {
    filtros.value = { search: '', estado: '' };
    aplicarFiltros();
};

const getEstadoBadge = (estado) => {
    const colores = {
        activa: 'bg-green-100 text-green-800',
        inactiva: 'bg-yellow-100 text-yellow-800',
        vencida: 'bg-red-100 text-red-800',
        cancelada: 'bg-gray-100 text-gray-800',
    };
    return colores[estado] || 'bg-gray-100 text-gray-800';
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value);
};

const showModal = ref(false);
const selectedPoliza = ref(null);
const loading = ref(false);

const abrirDetalle = async (id) => {
    loading.value = true;
    showModal.value = true;
    try {
        const response = await axios.get(route('polizas-servicio.show', id), {
            headers: { 'X-Inertia-Partial-Data': 'poliza,stats' }
        });
        // Si el controlador devuelve un render de Inertia, axios obtendrá el JSON si enviamos los headers correctos
        // Pero el controlador de Show de Laravel devuelve Inertia::render.
        // Una mejor forma es llamar a una ruta que devuelva JSON o usar local data si ya la tenemos.
        // Pero Show tiene data extra (tickets, stats, etc).
        
        // Vamos a usar Inertia.visit con preventScroll y preserveState para cargar la data en props extra?
        // No, mejor una petición normal de axios para obtener la data del show.
        
        // Reintentamos con una petición simple si el controlador lo permite o creamos una ruta API.
        // Por ahora, asumimos que obtendremos el HTML si no tenemos cuidado.
        // En Laravel, si pides JSON a una ruta de Inertia, te devuelve el JSON de los props.
        const res = await axios.get(route('polizas-servicio.show', id), {
            headers: { 'X-Inertia': true, 'X-Inertia-Version': router.page.version }
        });
        selectedPoliza.value = res.data.props;
    } catch (error) {
        console.error("Error al cargar detalle:", error);
    } finally {
        loading.value = false;
    }
};

const cerrarModal = () => {
    showModal.value = false;
    selectedPoliza.value = null;
};
</script>

<template>
    <AppLayout title="Pólizas de Servicio">
        <Head title="Pólizas de Servicio" />

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Pólizas de Servicio</h1>
                        <p class="text-gray-600">Gestiona los contratos de mantenimiento y soporte recurrente</p>
                    </div>
                    <div class="flex gap-3">
                        <Link :href="route('polizas-servicio.dashboard')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition font-semibold flex items-center gap-2">
                            📊 Dashboard
                        </Link>
                        <Link :href="route('polizas-servicio.create')" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow-lg flex items-center gap-2">
                            <span>+</span> Nueva Póliza
                        </Link>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <input 
                            v-model="filtros.search"
                            type="text"
                            placeholder="Buscar por folio, cliente o nombre..."
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                            @keyup.enter="aplicarFiltros"
                        />
                        <select v-model="filtros.estado" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" @change="aplicarFiltros">
                            <option value="">Todos los estados</option>
                            <option value="activa">Activa</option>
                            <option value="inactiva">Inactiva</option>
                            <option value="vencida">Vencida</option>
                            <option value="cancelada">Cancelada</option>
                        </select>
                        <div class="flex items-center gap-2">
                            <button @click="aplicarFiltros" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition">
                                Filtrar
                            </button>
                            <button @click="limpiarFiltros" class="text-sm text-gray-500 hover:text-gray-700">
                                Limpiar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Lista de Pólizas -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Póliza</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto Mensual</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Día Cobro</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inicio</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="poliza in polizas.data" :key="poliza.id" class="hover:bg-gray-50 transition border-transparent border-l-4 hover:border-blue-500">
                                    <td class="px-4 py-4">
                                        <div class="font-mono text-xs font-bold text-blue-600">{{ poliza.folio }}</div>
                                        <div class="text-sm font-medium text-gray-900">{{ poliza.nombre }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="text-sm text-gray-900 font-semibold">{{ poliza.cliente?.nombre_razon_social }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="text-sm font-bold text-gray-900">{{ formatCurrency(poliza.monto_mensual) }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="text-sm text-gray-600">Día {{ poliza.dia_cobro }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span :class="['px-2 py-1 text-xs font-bold rounded-full uppercase', getEstadoBadge(poliza.estado)]">
                                            {{ poliza.estado }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-500">
                                        {{ poliza.fecha_inicio }}
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <button @click="abrirDetalle(poliza.id)" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Ver detalle">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                            <Link :href="route('polizas-servicio.edit', poliza.id)" class="p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="polizas.data.length === 0">
                                    <td colspan="7" class="px-4 py-12 text-center">
                                        <div class="flex flex-col items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <p class="text-gray-500 font-medium">No se encontraron pólizas</p>
                                            <Link :href="route('polizas-servicio.create')" class="text-blue-600 hover:underline">Crear la primera póliza</Link>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div v-if="polizas.last_page > 1" class="px-4 py-3 bg-gray-50 border-t flex flex-col sm:flex-row justify-between items-center gap-4">
                        <span class="text-sm text-gray-500">
                            Mostrando {{ polizas.from }} a {{ polizas.to }} de {{ polizas.total }} pólizas
                        </span>
                        <div class="flex flex-wrap justify-center gap-1">
                            <Link 
                                v-for="link in polizas.links" 
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="['px-3 py-1 text-sm rounded-lg transition', 
                                    link.active ? 'bg-blue-600 text-white font-bold' : 'bg-white text-gray-700 hover:bg-gray-100 border',
                                    !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Detalle de Póliza -->
        <Modal :show="showModal" @close="cerrarModal" maxWidth="7xl">
            <div class="bg-white rounded-xl overflow-hidden relative">
                <!-- Botón de Cerrar Modal -->
                <button @click="cerrarModal" class="absolute top-4 right-4 z-50 p-2 bg-gray-100 hover:bg-gray-200 rounded-full text-gray-500 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Cargando -->
                <div v-if="loading" class="flex flex-col items-center justify-center p-20">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
                    <p class="text-gray-500 font-medium">Cargando detalles de la póliza...</p>
                </div>

                <!-- Contenido del Detalle -->
                <div v-else-if="selectedPoliza" class="max-h-[90vh] overflow-y-auto">
                    <ShowPoliza :poliza="selectedPoliza.poliza" :stats="selectedPoliza.stats" :is-modal="true" />
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
