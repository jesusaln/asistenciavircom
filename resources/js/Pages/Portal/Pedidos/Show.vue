<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { Head, Link } from '@inertiajs/vue3';
import ClientLayout from '../Layout/ClientLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
    pedido: Object,
    empresa: Object,
});

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: 'numeric' });
};

const getStatusClasses = (estado) => {
    const maps = {
        'pendiente': 'bg-brand-50 dark:bg-brand-900/20 text-brand-600 border-amber-100',
        'confirmado': 'bg-sky-50 dark:bg-sky-900/20 text-blue-600 border-blue-100',
        'en_camino': 'bg-indigo-50 text-indigo-600 border-indigo-100',
        'entregado': 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 border-emerald-100',
        'cancelado': 'bg-rose-50 dark:bg-rose-900/20 text-rose-600 border-rose-100',
    };
    return maps[estado] || 'bg-white text-slate-500 border-slate-100';
};
</script>

<template>
    <Head :title="`Pedido #${pedido.numero_pedido || pedido.id}`" />

    <ClientLayout :empresa="empresa">
        <div class="px-2 sm:px-0">
            <div class="mb-10">
                <Link :href="route('portal.dashboard')" class="text-xs uppercase tracking-wide font-bold text-slate-400 hover:text-[var(--color-primary)] mb-4 inline-block transition-colors">
                    ← Volver al Panel
                </Link>
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Pedido #{{ pedido.numero_pedido || pedido.id }}</h1>
                        <p class="text-slate-500 font-medium">Realizado el {{ formatDate(pedido.fecha_pedido || pedido.created_at) }}</p>
                    </div>
                    <span 
                        :class="['px-6 py-2 rounded-xl text-xs font-black uppercase tracking-wide border', getStatusClasses(pedido.estado)]"
                    >
                        {{ pedido.estado }}
                    </span>
                </div>
            </div>

            <div class="grid lg:grid-cols-3 gap-10">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Productos -->
                    <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
                        <div class="px-8 py-6 border-b border-slate-50 bg-white/50">
                            <h3 class="font-black text-slate-900 uppercase tracking-wider text-sm">Resumen de Productos</h3>
                        </div>
                        <div class="divide-y divide-slate-50">
                            <div v-for="item in pedido.items" :key="item.id" class="p-8 flex items-center gap-6">
                                <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center text-slate-300 text-2xl border border-slate-100 overflow-hidden">
                                    <img v-if="item.pedible?.imagen_url" :src="item.pedible.imagen_url" class="w-full h-full object-cover" />
                                    <font-awesome-icon v-else icon="box" />
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-bold text-slate-900">{{ item.pedible?.nombre || item.pedible?.descripcion || 'Producto' }}</h4>
                                    <p class="text-xs text-slate-500 font-medium mt-1">Cantidad: <span class="text-slate-900">{{ item.cantidad }}</span></p>
                                </div>
                                <div class="text-right">
                                    <p class="font-black text-slate-900">${{ Number(item.precio * item.cantidad).toLocaleString('es-MX', {minimumFractionDigits: 2}) }}</p>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wide">${{ Number(item.precio).toLocaleString('es-MX', {minimumFractionDigits: 2}) }} c/u</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Envíos / Tracking -->
                    <div v-if="pedido.numero_guia || pedido.direccion_entrega" class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-100 p-8">
                        <h3 class="font-black text-slate-900 uppercase tracking-wider mb-6 flex items-center gap-2">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 flex items-center justify-center text-sm">
                                <font-awesome-icon icon="truck" />
                            </div>
                            Información de Entrega
                        </h3>
                        
                        <div class="grid md:grid-cols-2 gap-10">
                            <div v-if="pedido.direccion_entrega">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-wide mb-2">Dirección de Envío</p>
                                <p class="text-sm text-slate-500 font-medium leading-relaxed">{{ pedido.direccion_entrega }}</p>
                            </div>
                            <div v-if="pedido.numero_guia">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-wide mb-2">Seguimiento</p>
                                <div class="bg-emerald-50 dark:bg-emerald-900/20 p-4 rounded-2xl border border-emerald-100">
                                    <p class="text-xs font-bold text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 uppercase tracking-wide">{{ pedido.empresa_envio || 'Mensajería' }}</p>
                                    <p class="text-lg font-black text-slate-900 font-mono mt-1">{{ pedido.numero_guia }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Totales -->
                    <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-100 p-8">
                        <h3 class="font-black text-slate-900 uppercase tracking-wider mb-6 text-sm">Resumen de Pago</h3>
                        <div class="space-y-6">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500 font-medium">Subtotal</span>
                                <span class="font-bold text-slate-900">${{ Number(pedido.subtotal).toLocaleString('es-MX', {minimumFractionDigits: 2}) }}</span>
                            </div>
                            <div v-if="pedido.iva > 0" class="flex justify-between text-sm">
                                <span class="text-slate-500 font-medium">IVA (16%)</span>
                                <span class="font-bold text-slate-900">${{ Number(pedido.iva).toLocaleString('es-MX', {minimumFractionDigits: 2}) }}</span>
                            </div>
                            <div v-if="pedido.descuento_general > 0" class="flex justify-between text-sm">
                                <span class="text-emerald-500 font-medium">Descuento</span>
                                <span class="font-bold text-emerald-500">-${{ Number(pedido.descuento_general).toLocaleString('es-MX', {minimumFractionDigits: 2}) }}</span>
                            </div>
                            <div class="pt-4 border-t border-slate-50 flex justify-between items-end">
                                <span class="font-black text-slate-900 uppercase tracking-wider">Total</span>
                                <span class="text-2xl font-black text-[var(--color-primary)]">${{ Number(pedido.total).toLocaleString('es-MX', {minimumFractionDigits: 2}) }}</span>
                            </div>
                        </div>
                        
                        <div class="mt-8 pt-8 border-t border-slate-50">
                             <p class="text-[10px] font-black text-slate-400 uppercase tracking-wide mb-2">Método de Pago</p>
                             <p class="text-sm font-bold text-slate-900 uppercase">{{ pedido.metodo_pago || 'Pendiente' }}</p>
                             <p v-if="pedido.pagado_at" class="text-[10px] text-emerald-600 font-bold uppercase mt-1">Pagado el {{ formatDate(pedido.pagado_at) }}</p>
                        </div>
                    </div>

                    <!-- Ayuda -->
                    <div class="bg-slate-900 rounded-[2rem] p-8 text-white relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -mr-16 -mt-16 group-hover:scale-105 transition-transform"></div>
                        <h4 class="text-lg font-black mb-2 relative z-10">¿Necesita ayuda?</h4>
                        <p class="text-slate-400 text-sm mb-6 relative z-10 font-medium">Si tiene dudas sobre su pedido o necesita una factura, contáctenos.</p>
                        <Link :href="route('portal.tickets.create', { titulo: 'Ayuda con pedido #' + (pedido.numero_pedido || pedido.id) })" class="block w-full text-center py-4 bg-white text-slate-900 rounded-2xl font-black text-sm uppercase tracking-wide hover:bg-[var(--color-primary)] hover:text-white transition-all">
                            Contactar Soporte
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </ClientLayout>
</template>
