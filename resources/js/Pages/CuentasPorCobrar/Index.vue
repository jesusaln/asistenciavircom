<template>
    <AppLayout title="Cuentas por Cobrar">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
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

        <!-- Estadísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-bold">!</span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Total Vencido
                            </dt>
                            <dd class="text-lg font-semibold text-gray-900">
                                {{ formatCurrency(stats.total_vencido) }}
                            </dd>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-bold">P</span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Total Pendiente
                            </dt>
                            <dd class="text-lg font-semibold text-gray-900">
                                {{ formatCurrency(stats.total_pendiente) }}
                            </dd>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-bold">{{ stats.cuentas_pendientes }}</span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Cuentas Pendientes
                            </dt>
                            <dd class="text-lg font-semibold text-gray-900">
                                {{ stats.cuentas_pendientes }}
                            </dd>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-bold">{{ stats.cuentas_vencidas }}</span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Cuentas Vencidas
                            </dt>
                            <dd class="text-lg font-semibold text-gray-900">
                                {{ stats.cuentas_vencidas }}
                            </dd>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <form @submit.prevent="applyFilters" class="flex flex-wrap gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Estado</label>
                        <select v-model="filters.estado" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">Todos</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="parcial">Parcial</option>
                            <option value="pagado">Pagado</option>
                            <option value="vencido">Vencido</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cliente</label>
                        <select v-model="filters.cliente_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
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
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Origen
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Cliente
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Monto Total
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Pendiente
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Vencimiento
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Estado
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="cuenta in cuentas.data" :key="cuenta.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <template v-if="cuenta.cobrable_type && cuenta.cobrable_type.includes('Venta')">
                                        Venta #{{ cuenta.cobrable?.numero_venta || cuenta.cobrable_data?.numero_venta || '' }}
                                    </template>
                                    <template v-else-if="cuenta.cobrable_type && cuenta.cobrable_type.includes('Renta')">
                                        Renta #{{ cuenta.cobrable?.numero_contrato }}
                                        <div class="text-xs text-gray-500">{{ cuenta.notas }}</div>
                                    </template>
                                    <template v-else>
                                        {{ cuenta.venta?.numero_venta || '---' }}
                                    </template>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ cuenta.cobrable?.cliente?.nombre_razon_social || cuenta.cobrable_data?.cliente?.nombre_razon_social || cuenta.venta?.cliente?.nombre_razon_social || 'Cliente no disponible' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ formatCurrency(cuenta.monto_total) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ formatCurrency(cuenta.monto_pendiente) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ cuenta.fecha_vencimiento ? new Date(cuenta.fecha_vencimiento).toLocaleDateString() : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="{
                                        'bg-red-100 text-red-800': cuenta.estado === 'vencido',
                                        'bg-yellow-100 text-yellow-800': cuenta.estado === 'parcial',
                                        'bg-green-100 text-green-800': cuenta.estado === 'pagado',
                                        'bg-gray-100 text-gray-800': cuenta.estado === 'pendiente'
                                    }" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                        {{ cuenta.estado }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center justify-start space-x-2">
                                        <!-- Ver detalles -->
                                        <button @click="abrirModalDetalles(cuenta.id)"
                                              class="group/btn relative inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 hover:text-blue-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:ring-offset-1"
                                              title="Ver detalles">
                                            <svg class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>

                                        <!-- Editar -->
                                        <Link :href="route('cuentas-por-cobrar.edit', cuenta.id)"
                                              class="group/btn relative inline-flex items-center justify-center w-9 h-9 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 hover:text-amber-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:ring-offset-1"
                                              title="Editar">
                                            <svg class="w-4 h-4 transition-transform duration-200 group-hover/btn:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </Link>

                                        <!-- Enviar recordatorio de pago (solo si está pendiente o parcial) - Discapacitado para Rentas por ahora -->
                                        <button v-if="['pendiente', 'parcial'].includes(cuenta.estado) && cuenta.cobrable?.cliente?.email && (!cuenta.cobrable_type || cuenta.cobrable_type.includes('Venta'))"
                                                @click="enviarRecordatorio(cuenta)"
                                                class="group/btn relative inline-flex items-center justify-center w-9 h-9 rounded-lg bg-green-50 text-green-600 hover:bg-green-100 hover:text-green-700 hover:shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500/50 focus:ring-offset-1"
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
                        <div class="text-sm text-gray-700">
                            Mostrando {{ cuentas.from }} a {{ cuentas.to }} de {{ cuentas.total }} resultados
                        </div>
                        <div class="flex space-x-1">
                            <Link
                                v-for="link in cuentas.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                v-html="link.label"
                                :class="[
                                    'px-3 py-2 text-sm border rounded',
                                    link.active ? 'bg-blue-500 text-white border-blue-500' : (link.url ? 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' : 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed')
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
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/IndexComponents/Modales.vue';
import ShowModal from '@/Pages/CuentasPorCobrar/Partials/ShowModal.vue';
import ImportPaymentXmlModal from '@/Components/CuentasPorCobrar/ImportPaymentXmlModal.vue';

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
const cuentasBancarias = ref([]);

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
    // Implementar filtrado
    window.location.href = route('cuentas-por-cobrar.index', filters.value);
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





