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
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Pedido #{{ pedido.numero_pedido }}
                </h2>
                <Link :href="route('pedidos-online.index')" class="text-sm text-gray-600 hover:text-gray-900">
                    &larr; Volver al listado
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <!-- Columna Izquierda: Detalles Principales -->
                    <div class="md:col-span-2 space-y-6">
                        <!-- Items del Pedido -->
                        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                            <h3 class="text-lg font-bold mb-4 border-b pb-2">Productos</h3>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="text-left text-xs font-bold text-gray-500 uppercase">Producto</th>
                                        <th class="text-right text-xs font-bold text-gray-500 uppercase">Cant.</th>
                                        <th class="text-right text-xs font-bold text-gray-500 uppercase">Precio</th>
                                        <th class="text-right text-xs font-bold text-gray-500 uppercase">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="item in pedido.items" :key="item.id">
                                        <td class="py-3">
                                            <div class="text-sm font-medium text-gray-900">{{ item.nombre }}</div>
                                            <div class="text-xs text-gray-500">{{ item.codigo }}</div>
                                        </td>
                                        <td class="py-3 text-right text-sm">{{ item.cantidad }}</td>
                                        <td class="py-3 text-right text-sm">{{ formatCurrency(item.precio) }}</td>
                                        <td class="py-3 text-right text-sm font-bold">{{ formatCurrency(item.subtotal) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-right font-bold pt-4">Subtotal:</td>
                                        <td class="text-right pt-4">{{ formatCurrency(pedido.subtotal) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-right font-bold">Envío:</td>
                                        <td class="text-right">{{ formatCurrency(pedido.costo_envio) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-right font-black text-lg">Total:</td>
                                        <td class="text-right font-black text-lg text-blue-600">{{ formatCurrency(pedido.total) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Bitácora -->
                        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                            <h3 class="text-lg font-bold mb-4 border-b pb-2">Historial de Eventos (Bitácora)</h3>
                            <div class="space-y-4">
                                <div v-for="evento in pedido.bitacora" :key="evento.id" class="flex gap-4 text-sm border-l-2 border-gray-200 pl-4 py-1">
                                    <div class="text-gray-500 text-xs w-32 flex-shrink-0">{{ formatDate(evento.created_at) }}</div>
                                    <div>
                                        <span class="font-bold block text-gray-800">{{ evento.accion }}</span>
                                        <span class="text-gray-600">{{ evento.descripcion }}</span>
                                        <div v-if="evento.usuario" class="text-xs text-blue-500 mt-1">Por: {{ evento.usuario.name }}</div>
                                    </div>
                                </div>
                                <div v-if="!pedido.bitacora.length" class="text-gray-500 italic">No hay eventos registrados.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha: Panel de Control y Datos -->
                    <div class="space-y-6">
                        
                        <!-- Panel de Gestión -->
                        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 border border-blue-100">
                            <h3 class="text-lg font-bold mb-4 text-blue-800">Gestionar Pedido</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Estatus Pago</label>
                                    <select v-model="form.estatus_pago" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                                        <option value="pendiente">Pendiente</option>
                                        <option value="pagado">Pagado</option>
                                        <option value="reembolsado">Reembolsado</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Estatus Pedido</label>
                                    <select v-model="form.estatus_pedido" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                                        <option value="nuevo">Nuevo</option>
                                        <option value="procesando">Procesando</option>
                                        <option value="enviado">Enviado</option>
                                        <option value="entregado">Entregado</option>
                                        <option value="cancelado">Cancelado</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Guía de Envío / Seguimiento</label>
                                    <input v-model="form.guia_envio" type="text" class="w-full border-gray-300 rounded-md shadow-sm text-sm" placeholder="Ej. FEDEX-123456">
                                </div>

                                <button @click="updateStatus" :disabled="form.processing" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow">
                                    Actualizar Estado
                                </button>
                            </div>
                        </div>

                        <!-- Datos del Cliente -->
                        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                            <h3 class="text-lg font-bold mb-4 border-b pb-2">Cliente</h3>
                            <div class="space-y-3 text-sm">
                                <div>
                                    <div class="font-bold text-gray-500 text-xs uppercase">Nombre</div>
                                    <div class="font-medium">{{ pedido.nombre }}</div>
                                </div>
                                <div>
                                    <div class="font-bold text-gray-500 text-xs uppercase">Email</div>
                                    <Link :href="`mailto:${pedido.email}`" class="text-blue-600 hover:underline">{{ pedido.email }}</Link>
                                </div>
                                <div>
                                    <div class="font-bold text-gray-500 text-xs uppercase">Teléfono</div>
                                    <div class="text-gray-800">{{ pedido.telefono }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Dirección de Envío -->
                        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                            <h3 class="text-lg font-bold mb-4 border-b pb-2">Envío</h3>
                             <div class="space-y-3 text-sm">
                                <div>
                                    <div class="font-bold text-gray-500 text-xs uppercase">Método</div>
                                    <div class="font-medium bg-gray-100 inline-block px-2 py-1 rounded">
                                        {{ pedido.direccion_envio?.tipo === 'recoger_en_tienda' ? 'Recoger en Tienda' : 'Envío a Domicilio' }}
                                    </div>
                                </div>
                                <div v-if="pedido.direccion_envio?.tipo !== 'recoger_en_tienda'">
                                    <div class="font-bold text-gray-500 text-xs uppercase mt-2">Dirección</div>
                                    <div class="text-gray-700 whitespace-pre-line leading-relaxed">
                                        {{ getDireccion(pedido.direccion_envio) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
