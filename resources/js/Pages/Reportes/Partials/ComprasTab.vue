<template>
    <div>
        <div class="mb-6">
            <h3 class="text-lg font-medium text-slate-900 mb-4">Resumen de Compras</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-rose-50 dark:bg-rose-900/20 p-4 rounded-xl">
                    <div class="text-2xl font-bold text-rose-600">{{ formatCurrency(totalComprasFiltrado) }}</div>
                    <div class="text-sm text-rose-600">Total Compras</div>
                </div>
                <div class="bg-orange-50 p-4 rounded-xl">
                    <div class="text-2xl font-bold text-orange-600">{{ comprasFiltradas.length }}</div>
                    <div class="text-sm text-orange-600">Número de Compras</div>
                </div>
                <div class="bg-sky-50 dark:bg-sky-900/20 p-4 rounded-xl">
                    <div class="text-2xl font-bold text-blue-600">{{ proveedoresUnicos }}</div>
                    <div class="text-sm text-blue-600">Proveedores</div>
                </div>
                <div class="bg-emerald-50 dark:bg-emerald-900/20 p-4 rounded-xl">
                    <div class="text-2xl font-bold text-emerald-600">{{ productosComprados }}</div>
                    <div class="text-sm text-emerald-600">Productos Comprados</div>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-800/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Fecha</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Proveedor</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Factura</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Productos</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                    <tr v-for="compra in comprasFiltradas" :key="compra.id" class="hover:bg-white">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">{{ formatDate(compra.created_at) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">{{ compra.proveedor?.nombre_razon_social || 'N/A' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">{{ compra.factura || 'N/A' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">{{ formatCurrency(compra.total) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="{
                                'bg-emerald-100 text-emerald-800 dark:text-emerald-200': compra.estado === 'completada',
                                'bg-brand-100 text-brand-800 dark:text-amber-200': compra.estado === 'pendiente',
                                'bg-rose-100 text-rose-800 dark:text-rose-200': compra.estado === 'cancelada'
                            }" class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium">
                                {{ compra.estado || 'Pendiente' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            {{ compra.productos?.length || 0 }} productos
                        </td>
                    </tr>
                    <tr v-if="comprasFiltradas.length === 0">
                        <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                            No hay compras en el período seleccionado
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Resumen por proveedor -->
        <div v-if="comprasPorProveedor.length > 0" class="mt-8">
            <h4 class="text-md font-medium text-slate-900 mb-4">Compras por Proveedor</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="proveedor in comprasPorProveedor" :key="proveedor.nombre" class="bg-white p-4 rounded-xl">
                    <div class="font-medium text-slate-900">{{ proveedor.nombre }}</div>
                    <div class="text-sm text-slate-500">{{ proveedor.compras }} compras</div>
                    <div class="text-lg font-bold text-rose-600">{{ formatCurrency(proveedor.total) }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';

const props = defineProps({
    comprasFiltradas: { type: Array, default: () => [] },
    totalComprasFiltrado: { type: Number, default: 0 },
    proveedoresUnicos: { type: Number, default: 0 },
    productosComprados: { type: Number, default: 0 },
    comprasPorProveedor: { type: Array, default: () => [] },
});

const { formatCurrency } = useFormatters();

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return format(new Date(dateString), 'dd MMM yyyy HH:mm', { locale: es });
};
</script>

