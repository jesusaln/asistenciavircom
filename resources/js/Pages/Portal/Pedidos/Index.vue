<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ClientLayout from '../Layout/ClientLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
    pedidos: Array,
    empresa: Object,
});

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: 'numeric' });
};

const getStatusClasses = (estado) => {
    const maps = {
        'pendiente': 'bg-amber-50 text-amber-600 border-amber-100',
        'confirmado': 'bg-blue-50 text-blue-600 border-blue-100',
        'en_camino': 'bg-indigo-50 text-indigo-600 border-indigo-100',
        'entregado': 'bg-emerald-50 text-emerald-600 border-emerald-100',
        'cancelado': 'bg-red-50 text-red-600 border-red-100',
    };
    return maps[estado] || 'bg-white text-gray-500 border-gray-100';
};
</script>

<template>
    <Head title="Mis Pedidos" />

    <ClientLayout :empresa="empresa">
        <div class="px-2 sm:px-0">
            <div class="mb-10">
                <Link :href="route('portal.dashboard')" class="text-xs uppercase tracking-widest font-bold text-gray-400 hover:text-[var(--color-primary)] mb-4 inline-block transition-colors">
                    ← Volver al Panel
                </Link>
                <h1 class="text-3xl font-black text-gray-900 tracking-tight">Mis Pedidos Online</h1>
                <p class="text-gray-500 font-medium">Consulte el estado de sus compras y envíos.</p>
            </div>

            <div class="space-y-6">
                <div v-for="pedido in pedidos" :key="pedido.id" 
                     class="bg-white rounded-[2rem] p-8 shadow-xl shadow-gray-200/50 border border-gray-100 group hover:border-[var(--color-primary)] transition-all">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                        <div class="flex items-center gap-6">
                            <div class="w-16 h-16 bg-[var(--color-primary-soft)] rounded-2xl flex items-center justify-center text-[var(--color-primary)] text-2xl">
                                <font-awesome-icon icon="box" />
                            </div>
                            <div>
                                <h3 class="text-xl font-black text-gray-900 group-hover:text-[var(--color-primary)] transition-colors">Pedido #{{ pedido.numero_pedido || pedido.id }}</h3>
                                <p class="text-sm font-bold text-gray-500">{{ formatDate(pedido.fecha_pedido || pedido.created_at) }}</p>
                            </div>
                        </div>
                        
                        <div class="flex flex-col items-end gap-2">
                            <span 
                                :class="['px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border', getStatusClasses(pedido.estado)]"
                            >
                                {{ pedido.estado }}
                            </span>
                            <p class="text-lg font-black text-gray-900">${{ Number(pedido.total).toLocaleString('es-MX', {minimumFractionDigits: 2}) }}</p>
                        </div>
                        
                        <div class="flex gap-4">
                             <Link :href="route('portal.pedidos.show', pedido.id)" class="px-8 py-4 bg-[var(--color-primary)] text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:shadow-xl transition-all">
                                Ver Detalles
                            </Link>
                        </div>
                    </div>
                </div>

                <div v-if="pedidos.length === 0" class="py-20 bg-white rounded-[2rem] border-2 border-dashed border-gray-100 text-center">
                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300">
                        <font-awesome-icon icon="shopping-cart" size="lg" />
                    </div>
                    <h3 class="text-lg font-black text-gray-900 mb-1">No hay pedidos</h3>
                    <p class="text-gray-500 font-medium text-sm">Aún no ha realizado compras en nuestra tienda en línea.</p>
                    <a :href="route('catalogo.index')" class="mt-6 inline-block px-8 py-4 bg-[var(--color-primary)] text-white rounded-2xl font-black text-sm uppercase tracking-widest hover:shadow-xl transition-all">Ir a la Tienda</a>
                </div>
            </div>
        </div>
    </ClientLayout>
</template>
