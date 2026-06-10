<template>
    <div>
        <div class="mb-6">
            <h3 class="text-lg font-medium text-slate-900 mb-4">Estado del Inventario</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-sky-50 dark:bg-sky-900/20 p-4 rounded-xl">
                    <div class="text-2xl font-bold text-blue-600">{{ inventarioFiltrado.length }}</div>
                    <div class="text-sm text-blue-600">Total Productos</div>
                </div>
                <div class="bg-emerald-50 dark:bg-emerald-900/20 p-4 rounded-xl">
                    <div class="text-2xl font-bold text-emerald-600">{{ productosEnStock }}</div>
                    <div class="text-sm text-emerald-600">En Stock</div>
                </div>
                <div class="bg-brand-50 dark:bg-brand-900/20 p-4 rounded-xl">
                    <div class="text-2xl font-bold text-amber-600">{{ productosBajoStock }}</div>
                    <div class="text-sm text-amber-600">Bajo Stock</div>
                </div>
                <div class="bg-rose-50 dark:bg-rose-900/20 p-4 rounded-xl">
                    <div class="text-2xl font-bold text-rose-600">{{ productosAgotados }}</div>
                    <div class="text-sm text-rose-600">Agotados</div>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-800/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Producto</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Categoría</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Stock</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Precio Compra</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Precio Venta</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Utilidad</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                    <tr v-for="producto in inventarioFiltrado" :key="producto.id">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">{{ producto.nombre }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">{{ producto.categoria?.nombre || 'N/A' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">{{ producto.stock }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">{{ formatCurrency(producto.precio_compra) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">{{ formatCurrency(producto.precio_venta) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" :class="calculateUtility(producto) >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                            {{ formatCurrency(calculateUtility(producto)) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="getEstadoClass(producto.stock, producto.stock_minimo)" class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium">
                                {{ getEstadoText(producto.stock, producto.stock_minimo) }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
const props = defineProps({
    inventarioFiltrado: { type: Array, default: () => [] },
    productosEnStock: { type: Number, default: 0 },
    productosBajoStock: { type: Number, default: 0 },
    productosAgotados: { type: Number, default: 0 },
});

const calculateUtility = (producto) => {
    const precioVenta = parseFloat(producto.precio_venta) || 0;
    const precioCompra = parseFloat(producto.precio_compra) || 0;
    return precioVenta - precioCompra;
};

const { formatCurrency } = useFormatters();

const getEstadoClass = (stock, minimo) => {
    if (stock <= 0) return 'bg-rose-100 text-rose-800 dark:text-rose-200';
    if (stock <= (minimo || 0)) return 'bg-brand-100 text-brand-800 dark:text-amber-200';
    return 'bg-emerald-100 text-emerald-800 dark:text-emerald-200';
};

const getEstadoText = (stock, minimo) => {
    if (stock <= 0) return 'Agotado';
    if (stock <= (minimo || 0)) return 'Bajo Stock';
    return 'En Stock';
};
</script>

