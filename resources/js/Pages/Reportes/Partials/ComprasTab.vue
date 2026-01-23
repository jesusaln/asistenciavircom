<template>
    <div>
        <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Resumen de Compras</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-red-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-red-600">{{ formatCurrency(totalComprasFiltrado) }}</div>
                    <div class="text-sm text-red-600">Total Compras</div>
                </div>
                <div class="bg-orange-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-orange-600">{{ comprasFiltradas.length }}</div>
                    <div class="text-sm text-orange-600">Número de Compras</div>
                </div>
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600">{{ proveedoresUnicos }}</div>
                    <div class="text-sm text-blue-600">Proveedores</div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-green-600">{{ productosComprados }}</div>
                    <div class="text-sm text-green-600">Productos Comprados</div>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
                <thead class="bg-white dark:bg-slate-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Proveedor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Factura</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Productos</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-800">
                    <tr v-for="compra in comprasFiltradas" :key="compra.id" class="hover:bg-white dark:bg-slate-900">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ formatDate(compra.created_at) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ compra.proveedor?.nombre_razon_social || 'N/A' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ compra.factura || 'N/A' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ formatCurrency(compra.total) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="{
                                'bg-green-100 text-green-800': compra.estado === 'completada',
                                'bg-yellow-100 text-yellow-800': compra.estado === 'pendiente',
                                'bg-red-100 text-red-800': compra.estado === 'cancelada'
                            }" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                {{ compra.estado || 'Pendiente' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ compra.productos?.length || 0 }} productos
                        </td>
                    </tr>
                    <tr v-if="comprasFiltradas.length === 0">
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            No hay compras en el período seleccionado
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Resumen por proveedor -->
        <div v-if="comprasPorProveedor.length > 0" class="mt-8">
            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Compras por Proveedor</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="proveedor in comprasPorProveedor" :key="proveedor.nombre" class="bg-white dark:bg-slate-900 p-4 rounded-lg">
                    <div class="font-medium text-gray-900 dark:text-white">{{ proveedor.nombre }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ proveedor.compras }} compras</div>
                    <div class="text-lg font-bold text-red-600">{{ formatCurrency(proveedor.total) }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { format } from 'date-fns';
import { es } from 'date-fns/locale';

const props = defineProps({
    comprasFiltradas: { type: Array, default: () => [] },
    totalComprasFiltrado: { type: Number, default: 0 },
    proveedoresUnicos: { type: Number, default: 0 },
    productosComprados: { type: Number, default: 0 },
    comprasPorProveedor: { type: Array, default: () => [] },
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value);
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return format(new Date(dateString), 'dd MMM yyyy HH:mm', { locale: es });
};
</script>

