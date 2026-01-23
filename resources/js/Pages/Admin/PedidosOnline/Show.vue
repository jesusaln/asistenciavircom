<script setup>
import { Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref } from 'vue'

const props = defineProps({
  pedido: Object
})

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value || 0)
}

const formatDate = (date) => {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString('es-MX', {
        year: 'numeric', month: 'long', day: 'numeric',
        hour: '2-digit', minute: '2-digit'
    })
}

// Formulario para actualizar estado
const form = useForm({
    estatus_pago: props.pedido.estatus_pago,
    estatus_pedido: props.pedido.estatus_pedido,
    guia_envio: props.pedido.guia_envio,
    descripcion_bitacora: ''
})

const updateStatus = () => {
    form.descripcion_bitacora = `Actualización manual de estado: Pago ${form.estatus_pago}, Pedido ${form.estatus_pedido}`
    form.post(route('pedidos-online.update-status', props.pedido.id), {
        preserveScroll: true,
        onSuccess: () => {
            alert('Pedido actualizado correctamente')
        }
    })
}

const getDireccion = (dir) => {
    if (!dir) return 'N/A'
    // Si viene como string JSON, parsear, si es objeto usar directo
    let d = dir
    if (typeof dir === 'string') {
        try { d = JSON.parse(dir) } catch (e) { return dir }
    }
    
    if (d.tipo === 'recoger_en_tienda') return 'RECOGER EN TIENDA'
    
    return `${d.calle} ${d.numero_exterior || ''}, ${d.colonia}, ${d.ciudad}, ${d.estado}, CP ${d.cp}`
}
</script>

<template>
    <AppLayout title="Detalle de Pedido Online">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-gray-50 to-blue-50/30 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 p-4 md:p-6 transition-colors">
            
            <!-- Header con Navegación -->
            <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                     <Link :href="route('pedidos-online.index')" class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-center text-gray-500 dark:text-gray-400 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:border-blue-200 dark:hover:border-blue-900 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </Link>
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                            Pedido #{{ pedido.numero_pedido }}
                        </h1>
                        <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400 text-sm flex items-center gap-2">
                             <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                             {{ formatDate(pedido.created_at) }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Columna Izquierda: Detalles del Pedido y Bitácora -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Productos del Pedido -->
                    <div class="bg-white dark:bg-slate-900/70 dark:bg-gray-800/70 backdrop-blur-sm rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-white dark:bg-slate-900/50 dark:bg-gray-800/50">
                            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 dark:text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                Productos
                            </h3>
                            <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-xs font-bold">
                                {{ pedido.items.length }} Items
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 dark:bg-slate-950/50 dark:bg-gray-700/30">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">Producto</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">Precio</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">Cant.</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    <tr v-for="item in pedido.items" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950/50 dark:hover:bg-gray-700/30 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded-lg text-gray-400 dark:text-gray-500 dark:text-gray-400">
                                                     <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white dark:text-white">{{ item.nombre }}</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 font-mono">{{ item.codigo }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 font-mono">
                                            {{ formatCurrency(item.precio) }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm text-gray-900 dark:text-white dark:text-white font-bold">
                                            {{ item.cantidad }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm font-bold text-gray-900 dark:text-white dark:text-white font-mono">
                                            {{ formatCurrency(item.subtotal) }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-gray-50 dark:bg-slate-950/30 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-700">
                                    <tr>
                                        <td colspan="3" class="px-6 py-3 text-right text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">Subtotal:</td>
                                        <td class="px-6 py-3 text-right text-sm font-medium text-gray-900 dark:text-white dark:text-white font-mono">{{ formatCurrency(pedido.subtotal) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="px-6 py-3 text-right text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">Envío:</td>
                                        <td class="px-6 py-3 text-right text-sm font-medium text-gray-900 dark:text-white dark:text-white font-mono">{{ formatCurrency(pedido.costo_envio) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="px-6 py-4 text-right text-base font-bold text-gray-900 dark:text-white dark:text-white">Total:</td>
                                        <td class="px-6 py-4 text-right text-xl font-bold text-blue-600 dark:text-blue-400 font-mono">{{ formatCurrency(pedido.total) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Bitácora de Eventos -->
                    <div class="bg-white dark:bg-slate-900/70 dark:bg-gray-800/70 backdrop-blur-sm rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-white dark:bg-slate-900/50 dark:bg-gray-800/50">
                             <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 dark:text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Historial de Eventos
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-6 relative before:absolute before:inset-y-0 before:left-2.5 before:w-0.5 before:bg-gray-200 dark:before:bg-gray-700 ml-2">
                                <div v-for="evento in pedido.bitacora" :key="evento.id" class="relative pl-8">
                                    <div class="absolute left-0 top-1.5 w-5 h-5 rounded-full border-4 border-white dark:border-gray-800 bg-blue-500 shadow-sm"></div>
                                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start group">
                                        <div class="space-y-1">
                                            <p class="text-sm font-bold text-gray-900 dark:text-white dark:text-white">
                                                {{ evento.accion }}
                                            </p>
                                            <p class="text-sm text-gray-600 dark:text-gray-300 dark:text-gray-400">
                                                {{ evento.descripcion }}
                                            </p>
                                            <div v-if="evento.usuario" class="flex items-center gap-2 pt-1">
                                                 <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 dark:text-gray-300 px-2 py-0.5 rounded-full flex items-center gap-1">
                                                     <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                                     {{ evento.usuario.name }}
                                                 </span>
                                            </div>
                                        </div>
                                        <span class="text-xs text-gray-400 dark:text-gray-500 dark:text-gray-400 font-mono mt-1 sm:mt-0 bg-white dark:bg-slate-900 dark:bg-gray-800 px-2 py-1 rounded border border-gray-100 dark:border-gray-700">
                                            {{ formatDate(evento.created_at) }}
                                        </span>
                                    </div>
                                </div>
                                <div v-if="!pedido.bitacora.length" class="pl-8 text-gray-500 dark:text-gray-400 dark:text-gray-400 italic font-sm">No hay eventos registrados aún.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Gestión y Cliente -->
                <div class="space-y-6">
                    
                    <!-- Panel de Gestión -->
                    <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-2xl border border-blue-100 dark:border-blue-900/30 shadow-lg shadow-blue-500/5 relative overflow-hidden">
                        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-blue-500/10 rounded-full blur-2xl"></div>
                        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2 bg-gradient-to-r from-blue-50/50 to-transparent dark:from-blue-900/10">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <h3 class="text-lg font-bold text-blue-800 dark:text-blue-400">Gestión del Pedido</h3>
                        </div>
                        <div class="p-6 space-y-5">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase mb-1.5">Estatus de Pago</label>
                                <div class="relative">
                                    <select v-model="form.estatus_pago" class="block w-full pl-3 pr-10 py-2 text-base border-gray-200 dark:border-slate-800 dark:border-gray-600 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-xl bg-gray-50 dark:bg-slate-950 dark:bg-gray-700 dark:text-white transition-colors">
                                        <option value="pendiente">Pendiente</option>
                                        <option value="pagado">Pagado</option>
                                        <option value="reembolsado">Reembolsado</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase mb-1.5">Estatus del Pedido</label>
                                <div class="relative">
                                    <select v-model="form.estatus_pedido" class="block w-full pl-3 pr-10 py-2 text-base border-gray-200 dark:border-slate-800 dark:border-gray-600 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-xl bg-gray-50 dark:bg-slate-950 dark:bg-gray-700 dark:text-white transition-colors">
                                        <option value="nuevo">Nuevo</option>
                                        <option value="procesando">Procesando</option>
                                        <option value="enviado">Enviado</option>
                                        <option value="entregado">Entregado</option>
                                        <option value="cancelado">Cancelado</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase mb-1.5">Guía de Envío</label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </div>
                                    <input v-model="form.guia_envio" type="text" class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-slate-950 dark:bg-gray-700 dark:text-white transition-colors py-2" placeholder="Ej. FEDEX-123456">
                                </div>
                            </div>

                            <button @click="updateStatus" :disabled="form.processing" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-xl shadow-sm text-sm font-bold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all transform active:scale-[0.98]">
                                <span v-if="form.processing" class="flex items-center gap-2">
                                     <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                     Guardando...
                                </span>
                                <span v-else>Actualizar Estado</span>
                            </button>
                        </div>
                    </div>

                    <!-- Información del Cliente -->
                    <div class="bg-white dark:bg-slate-900/70 dark:bg-gray-800/70 backdrop-blur-sm rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                         <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2 bg-white dark:bg-slate-900/50 dark:bg-gray-800/50">
                            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                             <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 dark:text-white">Cliente</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-500 dark:text-gray-400 dark:text-gray-400 font-bold text-lg">
                                    {{ pedido.nombre.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 dark:text-white dark:text-white">{{ pedido.nombre }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">Cliente</div>
                                </div>
                            </div>
                            
                            <div class="border-t border-gray-100 dark:border-gray-700 pt-4 space-y-3">
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                    <Link :href="`mailto:${pedido.email}`" class="text-sm text-blue-600 dark:text-blue-400 hover:underline break-all">{{ pedido.email }}</Link>
                                </div>
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                    <div class="text-sm text-gray-700 dark:text-gray-300">{{ pedido.telefono }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Datos de Envío -->
                    <div class="bg-white dark:bg-slate-900/70 dark:bg-gray-800/70 backdrop-blur-sm rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2 bg-white dark:bg-slate-900/50 dark:bg-gray-800/50">
                             <svg class="w-5 h-5 text-gray-500 dark:text-gray-400 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                             <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 dark:text-white">Envío</h3>
                        </div>
                        <div class="p-6">
                            <div class="mb-4">
                                <span class="px-3 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 rounded-lg text-xs font-bold uppercase tracking-wider">
                                     {{ pedido.direccion_envio?.tipo === 'recoger_en_tienda' ? 'Recoger en Tienda' : 'A Domicilio' }}
                                </span>
                            </div>
                            
                            <div v-if="pedido.direccion_envio?.tipo !== 'recoger_en_tienda'" class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                                    {{ getDireccion(pedido.direccion_envio) }}
                                </p>
                            </div>
                            <div v-else class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 italic">
                                El cliente pasará a recoger el pedido a la tienda.
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>
