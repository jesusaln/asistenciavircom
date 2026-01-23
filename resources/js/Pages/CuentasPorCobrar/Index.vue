<template>
    <AppLayout title="Cuentas por Cobrar">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 dark:text-gray-200 leading-tight">
                    Cuentas por Cobrar
                </h2>
                <div class="flex space-x-3">
                    <button
                        @click="showImportPaymentModal = true"
                        class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        Importar XML de Pago
                    </button>
                    <Link
                        :href="route('cuentas-por-cobrar.create')"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                    >
                        Nueva Cuenta por Cobrar
                    </Link>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6 font-black uppercase">
            <div class="bg-white dark:bg-slate-900 dark:bg-slate-900 overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 dark:border-slate-800 transition-all">
                <div class="p-6 text-gray-900 dark:text-white dark:text-white">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-bold">!</span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 truncate">
                                Total Vencido
                            </dt>
                            <dd class="text-lg font-semibold text-gray-900 dark:text-white dark:text-gray-100">
                                {{ formatCurrency(stats.total_vencido) }}
                            </dd>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 dark:bg-slate-900 overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 dark:border-slate-800 transition-all">
                <div class="p-6 text-gray-900 dark:text-white dark:text-white">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-bold">P</span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 truncate">
                                Total Pendiente
                            </dt>
                            <dd class="text-lg font-semibold text-gray-900 dark:text-white dark:text-gray-100">
                                {{ formatCurrency(stats.total_pendiente) }}
                            </dd>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 dark:bg-slate-900 overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 dark:border-slate-800 transition-all">
                <div class="p-6 text-gray-900 dark:text-white dark:text-white">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-bold">{{ stats.cuentas_pendientes }}</span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 truncate">
                                Cuentas Pendientes
                            </dt>
                            <dd class="text-lg font-semibold text-gray-900 dark:text-white dark:text-gray-100">
                                {{ stats.cuentas_pendientes }}
                            </dd>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 dark:bg-slate-900 overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 dark:border-slate-800 transition-all">
                <div class="p-6 text-gray-900 dark:text-white dark:text-white">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-bold">{{ stats.cuentas_vencidas }}</span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 truncate">
                                Cuentas Vencidas
                            </dt>
                            <dd class="text-lg font-semibold text-gray-900 dark:text-white dark:text-gray-100">
                                {{ stats.cuentas_vencidas }}
                            </dd>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white dark:bg-slate-900 dark:bg-slate-900 overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 dark:border-slate-800 mb-6 transition-all">
            <div class="p-6">
                <form @submit.prevent="applyFilters" class="flex flex-wrap gap-4 items-end">
                    <div class="w-full md:w-1/3 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Buscar</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <input type="text" v-model="filters.search" placeholder="Cliente, Folio, RFC..." 
                                   class="block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-700 dark:text-white" />
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado</label>
                        <select v-model="filters.estado" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm min-w-[150px] dark:bg-gray-700 dark:text-white">
                            <option value="">Todos</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="parcial">Parcial</option>
                            <option value="pagado">Pagado</option>
                            <option value="vencido">Vencido</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest">Cliente</label>
                        <select v-model="filters.cliente_id" class="mt-1 block w-full border-gray-300 dark:border-slate-800 rounded-xl shadow-sm dark:bg-slate-950 dark:text-white focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Todos</option>
                            <option v-for="cliente in clientes" :key="cliente.id" :value="cliente.id">
                                {{ cliente.nombre_razon_social }}
                            </option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla de cuentas -->
        <div class="bg-white dark:bg-slate-900 dark:bg-slate-900 overflow-hidden shadow-sm sm:rounded-3xl border border-gray-100 dark:border-slate-800 transition-all">
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800 dark:divide-slate-800">
                        <thead class="bg-gray-50 dark:bg-slate-950 dark:bg-slate-800/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                                    Origen
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                                    Cliente
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                                    Monto Total
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                                    Pendiente
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                                    Vencimiento
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                                    Estado
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-900 dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-800 dark:divide-slate-800">
                            <tr v-for="cuenta in cuentas.data" :key="cuenta.id" class="hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-700 transaction-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white dark:text-gray-100">
                                    <template v-if="cuenta.cobrable_type && cuenta.cobrable_type.includes('Venta')">
                                        Venta #{{ cuenta.cobrable?.numero_venta || cuenta.cobrable_data?.numero_venta || '' }}
                                    </template>
                                    <template v-else-if="cuenta.cobrable_type && (cuenta.cobrable_type.includes('Renta') || cuenta.cobrable_type === 'renta')">
                                        Renta #{{ cuenta.cobrable?.numero_contrato || cuenta.cobrable_data?.numero_contrato || '' }}
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ cuenta.notas }}</div>
                                    </template>
                                    <template v-else-if="cuenta.cobrable_type && (cuenta.cobrable_type.includes('Poliza') || cuenta.cobrable_type === 'poliza_servicio')">
                                        Póliza #{{ cuenta.cobrable?.folio || cuenta.cobrable_data?.folio || '' }}
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ cuenta.notas }}</div>
                                    </template>
                                    <template v-else>
                                        {{ cuenta.cobrable?.folio || cuenta.cobrable?.numero_venta || cuenta.cobrable_data?.folio || cuenta.cobrable_data?.numero_venta || '---' }}
                                    </template>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    {{ cuenta.cobrable?.cliente?.nombre_razon_social || cuenta.cobrable_data?.cliente?.nombre_razon_social || cuenta.cliente?.nombre_razon_social || 'Sin identificar' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    {{ formatCurrency(cuenta.monto_total) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    {{ formatCurrency(cuenta.monto_pendiente) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    {{ cuenta.fecha_vencimiento ? new Date(cuenta.fecha_vencimiento).toLocaleDateString() : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="{
                                        'bg-red-100 text-red-800': cuenta.estado === 'vencido',
                                        'bg-yellow-100 text-yellow-800': cuenta.estado === 'parcial',
                                        'bg-green-100 text-green-800': cuenta.estado === 'pagado',
                                        'bg-gray-100 text-gray-800 dark:text-gray-100': cuenta.estado === 'pendiente'
                                    }" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                        {{ cuenta.estado }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center justify-start space-x-2">
                                        <!-- Ver detalles -->
                                        <!-- Ver detalles -->
                                        <button @click="abrirModalDetalles(cuenta.id)"
                                              class="group/btn relative inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/60 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:ring-offset-1"
                                              title="Ver detalles">
                                            <svg class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>

                                        <!-- Pagar (Reemplaza Editar) -->
                                        <!-- Pagar (Reemplaza Editar) -->
                                        <button v-if="cuenta.estado !== 'pagado'"
                                                @click="abrirModalPago(cuenta.id)"
                                                class="group/btn relative inline-flex items-center justify-center w-9 h-9 rounded-lg bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:ring-offset-1"
                                                title="Registrar Pago">
                                            <svg class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>

                                        <!-- Enviar recordatorio de pago (solo si está pendiente o parcial) - Discapacitado para Rentas por ahora -->
                                        <!-- Enviar recordatorio de pago (solo si está pendiente o parcial) - Discapacitado para Rentas por ahora -->
                                        <button v-if="['pendiente', 'parcial'].includes(cuenta.estado) && cuenta.cobrable?.cliente?.email && (!cuenta.cobrable_type || cuenta.cobrable_type.includes('Venta'))"
                                                @click="enviarRecordatorio(cuenta)"
                                                class="group/btn relative inline-flex items-center justify-center w-9 h-9 rounded-lg bg-green-50 dark:bg-green-900/40 text-green-600 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/60 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500/50 focus:ring-offset-1"
                                                title="Enviar Recordatorio de Pago">
                                            <svg class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12l-1.5-1.5m0 0L9 12m1.5-1.5V9" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="mt-4">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-gray-700 dark:text-gray-300">
                            Mostrando {{ cuentas.from }} a {{ cuentas.to }} de {{ cuentas.total }} resultados
                        </div>
                        <div class="flex space-x-1">
                            <Link
                                v-for="link in cuentas.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                v-html="link.label"
                                :class="[
                                    'px-3 py-2 text-sm border rounded transition-colors',
                                    link.active ? 'bg-blue-500 text-white border-blue-500' : (link.url ? 'bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-600' : 'bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500 dark:text-gray-400 border-gray-200 dark:border-slate-800 dark:border-gray-700 cursor-not-allowed')
                                ]"
                                :preserve-scroll="link.url ? true : false"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de confirmación de email -->
        <Modal
            :show="showModal"
            :mode="modalMode"
            tipo="ventas"
            :selected="fila || {}"
            @close="cerrarModal"
            @confirm-email="confirmarEnvioEmail"
        />

        <!-- Modal de Detalles -->
        <!-- Modal Importar XML de Pago -->
        <ImportPaymentXmlModal
            :show="showImportPaymentModal"
            :cuentas-bancarias="cuentasBancarias"
            @close="showImportPaymentModal = false"
            @imported="handlePaymentImported"
        />

        <ShowModal
            :show="showDetailsModal"
            :cuenta="selectedCuenta"
            :cuentas-bancarias="cuentasBancarias"
            @close="cerrarModalDetalles"
        />

        <PaymentModal
            :show="showPaymentModal"
            :cuenta="selectedCuenta"
            :cuentas-bancarias="cuentasBancarias"
            @close="cerrarModalPago"
        />
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/IndexComponents/Modales.vue';
import ShowModal from '@/Pages/CuentasPorCobrar/Partials/ShowModal.vue';
import ImportPaymentXmlModal from '@/Components/CuentasPorCobrar/ImportPaymentXmlModal.vue';
import PaymentModal from '@/Pages/CuentasPorCobrar/Partials/PaymentModal.vue';

// ... (resto del código)

// Configuración de notificaciones
const notyf = new Notyf({
    duration: 4000,
    position: { x: 'right', y: 'top' },
    types: [
        { type: 'success', background: '#10b981', icon: false },
        { type: 'error', background: '#ef4444', icon: false },
        { type: 'warning', background: '#f59e0b', icon: false }
    ]
});

const props = defineProps({
    cuentas: Object,
    stats: Object,
    filters: Object,
});

const clientes = ref([]);

// Estado del modal de email
const showModal = ref(false);
const fila = ref(null);
const modalMode = ref('details');

// Estado del modal de detalles
const showDetailsModal = ref(false);
const selectedCuenta = ref(null);
const showImportPaymentModal = ref(false);
const showPaymentModal = ref(false); // Para el nuevo modal de pago directo
const cuentasBancarias = ref([]);

// Función para abrir modal de pago directo
const abrirModalPago = async (id) => {
    try {
        // Reutilizamos la ruta show para obtener info fresca y cuentas bancarias
        const response = await axios.get(route('cuentas-por-cobrar.show', id));
        if (response.data) {
            selectedCuenta.value = response.data.cuenta;
            cuentasBancarias.value = response.data.cuentasBancarias;
            showPaymentModal.value = true;
        }
    } catch (error) {
        console.error('Error al cargar datos de cuenta:', error);
        notyf.error('Error al cargar la información de la cuenta');
    }
};

const cerrarModalPago = () => {
    showPaymentModal.value = false;
    selectedCuenta.value = null;
    // Opcional: Recargar la página si se realizó un pago (Inertia reload)
    // Pero PaymentModal usa router.post que ya hace reload
};

// Función para abrir modal de confirmación de email
const abrirModalEmail = (cuenta) => {
    fila.value = {
        ...cuenta,
        numero_venta: cuenta.venta?.numero_venta || `V${String(cuenta.venta_id).padStart(3, '0')}`,
        email_destino: cuenta.venta?.cliente?.email,
        // Indicar que es un recordatorio de pago
        tipo_envio: 'recordatorio_pago'
    };
    modalMode.value = 'confirm-email';
    showModal.value = true;
};

// Función para cerrar modal email
const cerrarModal = () => {
    showModal.value = false;
    fila.value = null;
};

// Función para abrir modal de detalles
const abrirModalDetalles = async (id) => {
    try {
        const response = await axios.get(route('cuentas-por-cobrar.show', id));
        if (response.data) {
            selectedCuenta.value = response.data.cuenta;
            cuentasBancarias.value = response.data.cuentasBancarias;
            showDetailsModal.value = true;
        }
    } catch (error) {
        console.error('Error al cargar detalles:', error);
        notyf.error('Error al cargar los detalles de la cuenta');
    }
};

const cerrarModalDetalles = () => {
    showDetailsModal.value = false;
    selectedCuenta.value = null;
};

const handlePaymentImported = () => {
    // La página se recargará desde el modal
    console.log('Pagos importados exitosamente');
};

// Función para confirmar envío de email
const confirmarEnvioEmail = async () => {
    try {
        const cuenta = fila.value;
        if (!cuenta?.email_destino) {
            notyf.error('Email de destino no válido');
            return;
        }

        cerrarModal();

        // Usar axios para tener control total sobre la respuesta
        const { data } = await axios.post(`/ventas/${cuenta.venta.id}/enviar-email`, {
            email_destino: cuenta.email_destino,
        });

        if (data?.success) {
            notyf.success(data.message || 'Recordatorio de pago enviado correctamente');
        } else {
            throw new Error(data?.error || 'Error desconocido al enviar recordatorio');
        }

    } catch (error) {
        console.error('Error al enviar recordatorio:', error);
        const errorMessage = error.response?.data?.error || error.response?.data?.message || error.message;
        notyf.error('Error al enviar recordatorio: ' + errorMessage);
    }
};

const filters = ref({
    estado: props.filters.estado || '',
    cliente_id: props.filters.cliente_id || '',
    type: props.filters.type || '',
    search: props.filters.search || '',
});

const currencyFormatter = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

const toNumber = (value) => {
    if (value === null || value === undefined) {
        return 0;
    }

    const number = Number(value);
    return Number.isFinite(number) ? number : 0;
};

const formatCurrency = (value) => currencyFormatter.format(toNumber(value));

const applyFilters = () => {
    router.get(route('cuentas-por-cobrar.index'), filters.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const enviarRecordatorio = (cuenta) => {
    // Verificar que el cliente tenga email
    if (!cuenta.venta?.cliente?.email) {
        notyf.error('El cliente no tiene email configurado');
        return;
    }

    // Abrir modal de confirmación
    abrirModalEmail(cuenta);
};

// onMounted(() => {
//     // Cargar clientes para el filtro - temporalmente deshabilitado
//     // fetch(route('clientes.index', { per_page: 100 }))
//     //     .then(response => response.json())
//     //     .then(data => {
//     //         clientes.value = data.data || [];
//     //     })
//     //     .catch(error => {
//     //         console.error('Error loading clientes:', error);
//     //     });
// });
</script>





