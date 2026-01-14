<template>
    <div>
        <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Estado del Inventario</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600">{{ inventarioFiltrado.length }}</div>
                    <div class="text-sm text-blue-600">Total Productos</div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-green-600">{{ productosEnStock }}</div>
                    <div class="text-sm text-green-600">En Stock</div>
                </div>
                <div class="bg-yellow-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-yellow-600">{{ productosBajoStock }}</div>
                    <div class="text-sm text-yellow-600">Bajo Stock</div>
                </div>
                <div class="bg-red-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-red-600">{{ productosAgotados }}</div>
                    <div class="text-sm text-red-600">Agotados</div>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoría</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio Compra</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio Venta</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilidad</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="producto in inventarioFiltrado" :key="producto.id">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ producto.nombre }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ producto.categoria?.nombre || 'N/A' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ producto.stock }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatCurrency(producto.precio_compra) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatCurrency(producto.precio_venta) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" :class="calculateUtility(producto) >= 0 ? 'text-green-600' : 'text-red-600'">
                            {{ formatCurrency(calculateUtility(producto)) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="getEstadoClass(producto.stock, producto.stock_minimo)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
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

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value);
};

const getEstadoClass = (stock, minimo) => {
    if (stock <= 0) return 'bg-red-100 text-red-800';
    if (stock <= (minimo || 0)) return 'bg-yellow-100 text-yellow-800';
    return 'bg-green-100 text-green-800';
};

const getEstadoText = (stock, minimo) => {
    if (stock <= 0) return 'Agotado';
    if (stock <= (minimo || 0)) return 'Bajo Stock';
    return 'En Stock';
};
</script>

