<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, watch } from 'vue'

const props = defineProps({
  pedidos: Object,
  filters: Object
})

const search = ref(props.filters.search || '')
const estatusPago = ref(props.filters.estatus_pago || '')
const estatusPedido = ref(props.filters.estatus_pedido || '')

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value || 0)
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('es-MX', {
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit'
    })
}

// Búsqueda simple
const handleSearch = () => {
    router.get(route('pedidos-online.index'), { 
        search: search.value,
        estatus_pago: estatusPago.value,
        estatus_pedido: estatusPedido.value
    }, { preserveState: true, replace: true })
}
</script>

<template>
    <AppLayout title="Pedidos Tienda Online">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Pedidos Tienda Online
            </h2>
        </template>

        <div class="py-12">
            <div class="w-full sm:px-6 lg:px-8">
                <!-- Filtros -->
                <div class="bg-white p-4 rounded-lg shadow mb-6 flex gap-4">
                    <input v-model="search" @keyup.enter="handleSearch" type="text" placeholder="Buscar por pedido, email..." class="border-gray-300 rounded-md shadow-sm">
                    <select v-model="estatusPago" @change="handleSearch" class="border-gray-300 rounded-md shadow-sm">
                        <option value="">Todos los pagos</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="pagado">Pagado</option>
                    </select>
                    <select v-model="estatusPedido" @change="handleSearch" class="border-gray-300 rounded-md shadow-sm">
                        <option value="">Todos los estados</option>
                        <option value="nuevo">Nuevo</option>
                        <option value="procesando">Procesando</option>
                        <option value="enviado">Enviado</option>
                        <option value="entregado">Entregado</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                    <button @click="handleSearch" class="bg-blue-600 text-white px-4 py-2 rounded-md">Filtrar</button>
                </div>

                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pedido</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pago</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="pedido in pedidos.data" :key="pedido.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900">{{ pedido.numero_pedido }}</div>
                                    <div class="text-xs text-gray-500">{{ pedido.metodo_pago }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ pedido.nombre }}</div>
                                    <div class="text-xs text-gray-500">{{ pedido.email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-bold">
                                    {{ formatCurrency(pedido.total) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ formatDate(pedido.created_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        :class="pedido.estatus_pago === 'pagado' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                        {{ pedido.estatus_pago }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        {{ pedido.estatus_pedido }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link :href="route('pedidos-online.show', pedido.id)" class="text-indigo-600 hover:text-indigo-900 font-bold">Ver Detalles</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <!-- Paginación básica -->
                    <div class="p-4 flex justify-between" v-if="pedidos.links">
                         <!-- Puedes agregar componente de paginación aquí si lo tienes -->
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
