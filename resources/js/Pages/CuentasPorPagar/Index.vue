<template>
    <AppLayout title="Cuentas por Pagar">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Cuentas por Pagar
                </h2>
                <div class="flex space-x-3">
                    <button
                        @click="showImportPaymentModal = true"
                        class="bg-orange-500 hover:bg-orange-700 text-white font-bold py-2 px-4 rounded flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        Importar XML de Pago
                    </button>
                    <Link
                        :href="route('cuentas-por-pagar.create')"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                    >
                        Nueva Cuenta por Pagar
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
                        <label class="block text-sm font-medium text-gray-700">Proveedor</label>
                        <select v-model="filters.proveedor_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">Todos</option>
                            <option v-for="proveedor in proveedores" :key="proveedor.id" :value="proveedor.id">
                                {{ proveedor.nombre_razon_social }}
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
                                    Compra
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Proveedor
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
                                    {{ cuenta.compra ? cuenta.compra.numero_compra : 'Sin compra' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ cuenta.compra?.proveedor?.nombre_razon_social || 'Sin proveedor' }}
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
                                        'bg-red-100 text-red-800': estadoPara(cuenta) === 'vencido',
                                        'bg-yellow-100 text-yellow-800': estadoPara(cuenta) === 'parcial',
                                        'bg-green-100 text-green-800': estadoPara(cuenta) === 'pagado',
                                        'bg-gray-100 text-gray-800': estadoPara(cuenta) === 'pendiente'
                                    }" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                        {{ estadoPara(cuenta) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center gap-2">
                                        <!-- Ver -->
                                        <Link 
                                            :href="route('cuentas-por-pagar.show', cuenta.id)" 
                                            class="inline-flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-colors"
                                            title="Ver detalles"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </Link>
                                        
                                        <!-- Editar (solo si no está pagado) -->
                                        <Link 
                                            v-if="estadoPara(cuenta) !== 'pagado'"
                                            :href="route('cuentas-por-pagar.edit', cuenta.id)" 
                                            class="inline-flex items-center justify-center w-8 h-8 bg-amber-100 text-amber-600 rounded-lg hover:bg-amber-200 transition-colors"
                                            title="Editar"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </Link>
                                        
                                        <!-- Eliminar (solo si no está pagado) -->
                                        <button
                                            v-if="estadoPara(cuenta) !== 'pagado'"
                                            type="button"
                                            class="inline-flex items-center justify-center w-8 h-8 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors"
                                            title="Eliminar"
                                            @click="removeCuenta(cuenta)"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
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

        <!-- Modal Importar XML de Pago -->
        <ImportPaymentXmlModal
            :show="showImportPaymentModal"
            :cuentas-bancarias="cuentasBancarias"
            @close="showImportPaymentModal = false"
            @imported="handlePaymentImported"
        />
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ImportPaymentXmlModal from '@/Components/CuentasPorPagar/ImportPaymentXmlModal.vue';

const props = defineProps({
    cuentas: Object,
    stats: Object,
    filters: Object,
    cuentasBancarias: Array,
    proveedores: Array,
});

const proveedores = computed(() => props.proveedores || []);
const cuentasBancarias = computed(() => props.cuentasBancarias || []);

const filters = ref({
    estado: props.filters.estado || '',
    proveedor_id: props.filters.proveedor_id || '',
});

const showImportPaymentModal = ref(false);

const handlePaymentImported = () => {
    console.log('Pagos importados exitosamente');
};

const currencyFormatter = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

const toNumber = (value) => {
    if (value === null || value === undefined) {
        return 0;
    }

    const number = Number(value);
    return Number.isFinite(number) ? number : 0;
};

const formatCurrency = (value) => currencyFormatter.format(toNumber(value));

const estadoPara = (cuenta) => {
    if (cuenta?.pagado || toNumber(cuenta?.monto_pendiente) <= 0) {
        return 'pagado';
    }
    return cuenta?.estado || 'pendiente';
};

const removeCuenta = (cuenta) => {
    if (!confirm(`¿Eliminar la cuenta #${cuenta.id}?`)) {
        return;
    }

    router.delete(route('cuentas-por-pagar.destroy', cuenta.id), {
        preserveScroll: true,
    });
};

const applyFilters = () => {
    // Implementar filtrado
    window.location.href = route('cuentas-por-pagar.index', filters.value);
};

// La carga de datos se realiza ahora via props desde el controlador
</script>




