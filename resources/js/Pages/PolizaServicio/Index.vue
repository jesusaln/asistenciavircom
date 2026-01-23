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
        cancelada: 'bg-gray-100 text-gray-800 dark:text-gray-100',
    };
    return colores[estado] || 'bg-gray-100 text-gray-800 dark:text-gray-100';
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-MX', { 
        day: '2-digit', month: 'short', year: 'numeric' 
    });
};

const showModal = ref(false);
const selectedPoliza = ref(null);
const loading = ref(false);

const abrirDetalle = async (id) => {
    loading.value = true;
    showModal.value = true;
    try {
        // Usar Inertia headers para obtener los props como JSON
        const res = await axios.get(route('polizas-servicio.show', id), {
            headers: { 
                'X-Inertia': 'true',
                'Accept': 'application/json'
            }
        });
        selectedPoliza.value = res.data.props || res.data;
    } catch (error) {
        console.error("Error al cargar detalle:", error);
        showModal.value = false;
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

        <div class="py-6 min-h-screen bg-[#0F172A] text-slate-300">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                <!-- Header Premium -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                    <div>
                        <h1 class="text-3xl font-bold bg-gradient-to-r from-white to-slate-400 bg-clip-text text-transparent">
                            Pólizas de Servicio
                        </h1>
                        <p class="text-slate-400 mt-1 font-medium">Gestiona contratos y niveles de servicio VIP</p>
                    </div>
                    <div class="flex gap-3">
                        <Link :href="route('polizas-servicio.dashboard')" class="px-5 py-2.5 bg-slate-800/50 hover:bg-slate-800 border border-slate-700/50 text-slate-300 rounded-xl transition-all font-semibold flex items-center gap-2 backdrop-blur-sm hover:shadow-lg hover:shadow-slate-900/50 group">
                            <span class="group-hover:scale-110 transition-transform">📊</span> Dashboard
                        </Link>
                        <Link :href="route('polizas-servicio.create')" class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white rounded-xl shadow-lg shadow-blue-900/40 transition-all font-bold flex items-center gap-2 hover:translate-y-[-1px]">
                            <span class="text-lg">+</span> Nueva Póliza
                        </Link>
                    </div>
                </div>

                <!-- Filtros Glassmorphism -->
                <div class="bg-slate-800/40 backdrop-blur-xl rounded-2xl border border-slate-700/50 p-5 mb-8 shadow-xl">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-slate-500 group-focus-within:text-blue-400 transition-colors" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <input 
                                v-model="filtros.search"
                                type="text"
                                placeholder="Buscar póliza o cliente..."
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-900/50 border border-slate-700 rounded-xl text-slate-200 placeholder-slate-500 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all"
                                @keyup.enter="aplicarFiltros"
                            />
                        </div>
                        
                        <select v-model="filtros.estado" class="w-full px-4 py-2.5 bg-slate-900/50 border border-slate-700 rounded-xl text-slate-200 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all" @change="aplicarFiltros">
                            <option value="" class="bg-slate-900">Todos los estados</option>
                            <option value="activa" class="bg-slate-900 text-green-400">✅ Activa</option>
                            <option value="inactiva" class="bg-slate-900 text-yellow-400">⏸️ Inactiva</option>
                            <option value="vencida" class="bg-slate-900 text-red-400">🛑 Vencida</option>
                            <option value="cancelada" class="bg-slate-900 text-slate-400">❌ Cancelada</option>
                        </select>
                        
                        <div class="flex items-center gap-3">
                            <button @click="aplicarFiltros" class="flex-1 px-4 py-2.5 bg-slate-700 hover:bg-slate-600 text-white rounded-xl transition-all font-medium border border-slate-600 hover:border-slate-500 shadow-lg shadow-black/20">
                                Buscar
                            </button>
                            <button @click="limpiarFiltros" class="px-4 py-2.5 text-slate-400 hover:text-white transition-colors font-medium border border-transparent hover:border-slate-700 rounded-xl">
                                Limpiar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Lista de Pólizas -->
                <div class="bg-slate-800/30 backdrop-blur-md rounded-2xl border border-slate-700/50 overflow-hidden shadow-2xl">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-700/50">
                            <thead class="bg-slate-900/50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Detalle Póliza</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Cliente Asignado</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Facturación</th>
                                    <th class="px-6 py-4 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Vigencia</th>
                                    <th class="px-6 py-4 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Control</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700/50">
                                <tr v-for="poliza in polizas.data" :key="poliza.id" class="group hover:bg-slate-700/30 transition-all duration-200">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg bg-blue-500/10 flex items-center justify-center text-blue-400 group-hover:scale-110 transition-transform">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-200 group-hover:text-white transition-colors">{{ poliza.nombre }}</div>
                                                <div class="text-xs font-mono text-blue-400/80">{{ poliza.folio }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-slate-300 font-medium">{{ poliza.cliente?.nombre_razon_social }}</div>
                                        <div class="text-xs text-slate-500">{{ poliza.cliente?.email }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-bold text-emerald-400">{{ formatCurrency(poliza.monto_mensual) }}</div>
                                        <div class="text-xs text-slate-500">Cobro: Día {{ poliza.dia_cobro }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span :class="['px-3 py-1 text-[10px] font-black rounded-full uppercase tracking-wide border', 
                                            poliza.estado === 'activa' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 
                                            poliza.estado === 'vencida' ? 'bg-red-500/10 text-red-400 border-red-500/20' : 
                                            'bg-slate-700/50 text-slate-400 border-slate-600']">
                                            {{ poliza.estado }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2 text-sm text-slate-400">
                                            <svg class="w-4 h-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            <div class="flex flex-col">
                                                <span class="text-slate-300 font-bold whitespace-nowrap">{{ formatDate(poliza.fecha_inicio) }}</span>
                                                <span class="text-[10px] text-slate-500 uppercase tracking-tighter">al {{ formatDate(poliza.fecha_fin) }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2 opacity-80 group-hover:opacity-100 transition-opacity">
                                            <Link :href="route('polizas-servicio.show', poliza.id)" class="p-2 bg-slate-800 hover:bg-blue-600/20 text-blue-400 hover:text-blue-300 rounded-lg transition-all border border-transparent hover:border-blue-500/30" title="Ver detalle">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </Link>
                                            <Link :href="route('polizas-servicio.edit', poliza.id)" class="p-2 bg-slate-800 hover:bg-amber-600/20 text-amber-400 hover:text-amber-300 rounded-lg transition-all border border-transparent hover:border-amber-500/30" title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="polizas.data.length === 0">
                                    <td colspan="6" class="px-6 py-20 text-center">
                                        <div class="flex flex-col items-center gap-4">
                                            <div class="w-16 h-16 bg-slate-800 rounded-full flex items-center justify-center mb-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </div>
                                            <h3 class="text-slate-300 font-bold text-lg">No hay pólizas registradas</h3>
                                            <p class="text-slate-500 max-w-sm">No se encontraron contratos que coincidan con tu búsqueda.</p>
                                            <Link :href="route('polizas-servicio.create')" class="text-blue-400 hover:text-blue-300 font-semibold hover:underline mt-2">Crear la primera póliza →</Link>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación Dark -->
                    <div v-if="polizas.last_page > 1" class="px-6 py-4 bg-slate-800/50 border-t border-slate-700/50 flex flex-col sm:flex-row justify-between items-center gap-4">
                        <span class="text-sm text-slate-400">
                            Mostrando {{ polizas.from }} a {{ polizas.to }} de {{ polizas.total }} pólizas
                        </span>
                        <div class="flex flex-wrap justify-center gap-1">
                            <Link 
                                v-for="link in polizas.links" 
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="['px-3 py-1.5 text-xs rounded-lg transition-all font-medium border', 
                                    link.active ? 'bg-blue-600 border-blue-500 text-white shadow-lg shadow-blue-500/20' : 'bg-slate-800 border-slate-700 text-slate-400 hover:bg-slate-700 hover:text-white',
                                    !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Detalle de Póliza (Dark Mode) -->
        <Modal :show="showModal" @close="cerrarModal" maxWidth="7xl">
            <div class="bg-slate-900 border border-slate-700/50 rounded-xl overflow-hidden relative text-slate-200">
                <!-- Botón de Cerrar Modal -->
                <button @click="cerrarModal" class="absolute top-4 right-4 z-50 p-2 bg-slate-800 hover:bg-slate-700 rounded-full text-slate-400 transition-colors border border-slate-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Cargando -->
                <div v-if="loading" class="flex flex-col items-center justify-center p-20">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mb-4 shadow-[0_0_15px_rgba(59,130,246,0.5)]"></div>
                    <p class="text-slate-400 font-medium animate-pulse">Cargando detalles...</p>
                </div>

                <!-- Contenido del Detalle -->
                <div v-else-if="selectedPoliza" class="max-h-[90vh] overflow-y-auto custom-scrollbar">
                    <ShowPoliza :poliza="selectedPoliza.poliza" :stats="selectedPoliza.stats" :is-modal="true" class="dark-mode-content" />
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
