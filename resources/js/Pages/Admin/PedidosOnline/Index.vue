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
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-gray-50 to-blue-50/30 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 p-4 md:p-6 transition-colors">
            
            <!-- Header Premium -->
            <div class="mb-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/30">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                        </div>
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                                Pedidos Online
                            </h1>
                            <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400 text-sm">Gestiona las órdenes de la tienda en línea</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros Premium -->
            <div class="bg-white dark:bg-slate-900/70 dark:bg-gray-800/70 backdrop-blur-sm rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm mb-6 p-4">
                <div class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="w-full md:w-1/3">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Buscar</label>
                        <div class="relative">
                            <input 
                                v-model="search" 
                                @keyup.enter="handleSearch" 
                                type="text" 
                                placeholder="Pedido, cliente, email..." 
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-xl leading-5 bg-white dark:bg-slate-900 dark:bg-gray-700 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                            >
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                        </div>
                    </div>
                    <div class="w-full md:w-1/4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pago</label>
                        <select v-model="estatusPago" @change="handleSearch" class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-xl bg-white dark:bg-slate-900 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                            <option value="">Todos los pagos</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="pagado">Pagado</option>
                        </select>
                    </div>
                    <div class="w-full md:w-1/4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Estado</label>
                        <select v-model="estatusPedido" @change="handleSearch" class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-xl bg-white dark:bg-slate-900 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                            <option value="">Todos los estados</option>
                            <option value="nuevo">Nuevo</option>
                            <option value="procesando">Procesando</option>
                            <option value="enviado">Enviado</option>
                            <option value="entregado">Entregado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                    <div class="w-full md:w-auto">
                        <button @click="handleSearch" class="w-full md:w-auto px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-xl hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-lg shadow-blue-500/30 transition-all">
                            Filtrar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabla Premium -->
            <div class="bg-white dark:bg-slate-900/70 dark:bg-gray-800/70 backdrop-blur-sm rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-white dark:bg-slate-900/80 dark:bg-gray-800/80 border-b border-gray-100 dark:border-gray-700">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">Pedido</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">Cliente</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">Total</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">Pago</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-for="pedido in pedidos.data" :key="pedido.id" class="group hover:bg-blue-50/50 dark:hover:bg-blue-900/10 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-xs">
                                            #
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-gray-900 dark:text-white dark:text-white">{{ pedido.numero_pedido }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ pedido.metodo_pago }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white dark:text-white">{{ pedido.nombre }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ pedido.email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900 dark:text-white dark:text-white bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded inline-block">
                                        {{ formatCurrency(pedido.total) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    {{ formatDate(pedido.created_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        :class="pedido.estatus_pago === 'pagado' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300'">
                                        <span class="w-1.5 h-1.5 rounded-full mr-1.5 self-center" :class="pedido.estatus_pago === 'pagado' ? 'bg-green-500' : 'bg-red-500'"></span>
                                        {{ pedido.estatus_pago }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        :class="{
                                            'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300': pedido.estatus_pedido === 'nuevo',
                                            'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300': pedido.estatus_pedido === 'procesando',
                                            'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300': pedido.estatus_pedido === 'enviado',
                                            'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300': pedido.estatus_pedido === 'entregado',
                                            'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100 dark:text-gray-300': pedido.estatus_pedido === 'cancelado'
                                        }"
                                    >
                                        {{ pedido.estatus_pedido }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link :href="route('pedidos-online.show', pedido.id)" class="inline-flex items-center gap-1 text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 font-bold bg-blue-50 dark:bg-blue-900/20 px-3 py-1.5 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors">
                                        Ver Detalles
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="pedidos.data.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 dark:text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                        <p class="text-lg font-medium">No se encontraron pedidos</p>
                                        <p class="text-sm mt-1">Intenta ajustar los filtros de búsqueda</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginación Premium -->
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-slate-950/50 dark:bg-gray-800/50 flex items-center justify-between" v-if="pedidos.prev_page_url || pedidos.next_page_url">
                     <Link 
                       v-if="pedidos.prev_page_url" 
                       :href="pedidos.prev_page_url"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-slate-900 dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-700 transition-colors"
                     >
                      ← Anterior
                     </Link>
                     <div v-else></div>

                     <Link 
                       v-if="pedidos.next_page_url" 
                       :href="pedidos.next_page_url"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-slate-900 dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-700 transition-colors"
                     >
                      Siguiente →
                     </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
