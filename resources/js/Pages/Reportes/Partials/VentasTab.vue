<template>
    <div>
        <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Resumen de Ventas</h3>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600">{{ formatCurrency(corteFiltrado) }}</div>
                    <div class="text-sm text-blue-600">Total Ventas</div>
                </div>
                <div class="bg-indigo-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-amber-600">{{ formatCurrency(ivaFiltrado) }}</div>
                    <div class="text-sm text-amber-600">IVA Total</div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-green-600">{{ formatCurrency(utilidadFiltrada) }}</div>
                    <div class="text-sm text-green-600">Utilidad Total</div>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-purple-600">{{ ventasFiltradas.length }}</div>
                    <div class="text-sm text-purple-600">Número de Ventas</div>
                </div>
                <div class="bg-orange-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-orange-600">{{ clientesUnicosVentas }}</div>
                    <div class="text-sm text-orange-600">Clientes Atendidos</div>
                </div>
            </div>
        </div>

        <!-- Accesos directos a reportes avanzados de inventario -->
        <div class="mt-4 flex flex-wrap gap-2">
            <Link :href="route('reportes.inventario.stock-por-almacen')" class="inline-flex items-center px-3 py-2 text-sm rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200">
                Stock por almacén
            </Link>
            <Link :href="route('reportes.inventario.productos-bajo-stock')" class="inline-flex items-center px-3 py-2 text-sm rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200">
                Productos bajo stock
            </Link>
            <Link :href="route('reportes.inventario.movimientos-por-periodo')" class="inline-flex items-center px-3 py-2 text-sm rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200">
                Movimientos por período
            </Link>
            <Link :href="route('reportes.inventario.costos')" class="inline-flex items-center px-3 py-2 text-sm rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200">
                Costos de inventario
            </Link>
        </div>

        <div class="overflow-x-auto mt-6">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
                <thead class="bg-white dark:bg-slate-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cliente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">N° Venta</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Subtotal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">IVA</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Costo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Utilidad</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-800">
                    <template v-for="venta in ventasFiltradas" :key="venta.id">
                    <tr class="hover:bg-white dark:bg-slate-900">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ formatDate(venta.created_at) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ venta.cliente?.nombre_razon_social || 'N/A' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900 dark:text-white">{{ venta.numero_venta || venta.id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ formatCurrency((venta.total - venta.iva) || 0) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ formatCurrency(venta.iva) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ formatCurrency(venta.total) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ formatCurrency(venta.costo_total) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" :class="calculateProfit(venta) >= 0 ? 'text-green-600' : 'text-red-600'">
                            {{ formatCurrency(calculateProfit(venta)) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="{
                                'bg-green-100 text-green-800': venta.pagado && venta.estado === 'aprobada',
                                'bg-blue-100 text-blue-800': venta.estado === 'aprobada' && !venta.pagado,
                                'bg-yellow-100 text-yellow-800': venta.estado === 'pendiente',
                                'bg-gray-100 text-gray-800 dark:text-gray-100': venta.estado === 'borrador',
                                'bg-red-100 text-red-800': venta.estado === 'cancelada'
                            }" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                {{ venta.pagado ? 'Pagada' : 'Pendiente' }} - {{ venta.estado || 'Borrador' }}
                            </span>
                            <button
                                class="ml-3 text-xs text-blue-600 hover:text-blue-800 underline"
                                @click="toggleVentaDetails(venta.id)"
                            >
                                {{ expandedVentas[venta.id] ? 'Ocultar detalles' : 'Ver detalles' }}
                            </button>
                        </td>
                    </tr>
                    <tr v-if="expandedVentas[venta.id]" class="bg-white dark:bg-slate-900">
                        <td colspan="9" class="px-6 py-4">
                            <div class="text-sm text-gray-700 font-medium mb-2">Detalle de productos y costos</div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800 text-sm">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Tipo</th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Nombre</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Cant.</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Precio Venta</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Costo Unitario</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Costo Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-800">
                                        <tr v-for="item in (venta.items || [])" :key="item.id">
                                            <td class="px-4 py-2 whitespace-nowrap text-gray-600 dark:text-gray-300">{{ item.ventable_type?.includes('Producto') ? 'Producto' : 'Servicio' }}</td>
                                            <td class="px-4 py-2 whitespace-nowrap text-gray-900 dark:text-white">
                                                {{ item.ventable?.nombre || 'N/A' }}
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap text-right">{{ item.cantidad || 0 }}</td>
                                            <td class="px-4 py-2 whitespace-nowrap text-right">{{ formatCurrency(item.precio || 0) }}</td>
                                            <td class="px-4 py-2 whitespace-nowrap text-right">
                                                {{ formatCurrency(item.costo_unitario || 0) }}
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap text-right">
                                                {{ formatCurrency(((item.costo_unitario || 0) * (item.cantidad || 0))) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                    </template>
                    <tr v-if="ventasFiltradas.length === 0">
                        <td colspan="9" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            No hay ventas en el período seleccionado
                        </td>
                    </tr>
                </tbody>
                <tfoot class="bg-gray-100 font-bold border-t-2 border-gray-300">
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-right text-gray-900 dark:text-white">Totales:</td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white">{{ formatCurrency(totales.subtotal) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white">{{ formatCurrency(totales.iva) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white">{{ formatCurrency(totales.total) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white">{{ formatCurrency(totales.costo) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap" :class="totales.utilidad >= 0 ? 'text-green-600' : 'text-red-600'">
                            {{ formatCurrency(totales.utilidad) }}
                        </td>
                        <td class="px-6 py-4"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Resumen adicional -->
        <div v-if="ventasFiltradas.length > 0" class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Ventas por estado -->
            <div class="bg-white dark:bg-slate-900 p-6 rounded-lg">
                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Ventas por Estado</h4>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Pagadas y Aprobadas:</span>
                        <span class="font-medium">{{ ventasPagadasYAprobadas }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Pendientes de Pago:</span>
                        <span class="font-medium">{{ ventasPendientesPago }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-300">En Borrador:</span>
                        <span class="font-medium">{{ ventasBorrador }}</span>
                    </div>
                </div>
            </div>

            <!-- Top clientes -->
            <div class="bg-white dark:bg-slate-900 p-6 rounded-lg">
                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Top Clientes</h4>
                <div class="space-y-3">
                    <div v-for="cliente in topClientes" :key="cliente.nombre" class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-300 truncate mr-2">{{ cliente.nombre }}:</span>
                        <span class="font-medium">{{ formatCurrency(cliente.total) }}</span>
                    </div>
                    <div v-if="topClientes.length === 0" class="text-sm text-gray-500 dark:text-gray-400 text-center">
                        No hay datos de clientes
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';

const props = defineProps({
    ventasFiltradas: { type: Array, default: () => [] },
    corteFiltrado: { type: Number, default: 0 },
    ivaFiltrado: { type: Number, default: 0 },
    utilidadFiltrada: { type: Number, default: 0 },
    clientesUnicosVentas: { type: Number, default: 0 },
    ventasPagadasYAprobadas: { type: Number, default: 0 },
    ventasPendientesPago: { type: Number, default: 0 },
    ventasBorrador: { type: Number, default: 0 },
    topClientes: { type: Array, default: () => [] },
});

const expandedVentas = ref({});

// Helper para asegurar números
const parseMonto = (valor) => {
    if (valor === null || valor === undefined) return 0;
    if (typeof valor === 'number') return valor;
    // Si viene como string con comas, limpiarlo
    const limpio = String(valor).replace(/,/g, '');
    const numero = parseFloat(limpio);
    return isNaN(numero) ? 0 : numero;
};

const totales = computed(() => {
    return props.ventasFiltradas.reduce((acc, venta) => {
        const total = parseMonto(venta.total);
        const iva = parseMonto(venta.iva);
        const costo = parseMonto(venta.costo_total);
        
        const subtotal = total - iva;
        const utilidad = calculateProfit(venta);
        
        acc.subtotal += subtotal;
        acc.iva += iva;
        acc.total += total;
        acc.costo += costo;
        acc.utilidad += utilidad;
        
        return acc;
    }, { subtotal: 0, iva: 0, total: 0, costo: 0, utilidad: 0 });
});

const toggleVentaDetails = (id) => {
    expandedVentas.value[id] = !expandedVentas.value[id];
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value);
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return format(new Date(dateString), 'dd MMM yyyy HH:mm', { locale: es });
};

const calculateProfit = (venta) => {
    const total = parseMonto(venta.total);
    const iva = parseMonto(venta.iva);
    const costo = parseMonto(venta.costo_total);
    const gananciaTotal = parseMonto(venta.ganancia_total);

    // Si la utilidad calculada es 0, intentar recalcular con la fórmula simple: (Total - IVA) - Costo
    if (gananciaTotal && gananciaTotal !== 0) return gananciaTotal;
    
    // Fórmula solicitada: Precio Venta (sin IVA) - Costo
    const ventaSinIva = total - iva;
    return ventaSinIva - costo;
};
</script>

