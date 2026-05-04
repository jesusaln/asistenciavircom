<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    productos: {
        type: Array,
        default: () => [],
    },
});

const searchTerm = ref('');

const productosFiltrados = computed(() => {
    if (!searchTerm.value) {
        return props.productos;
    }
    const s = searchTerm.value.toLowerCase();
    return props.productos.filter((p) => {
        return (
            (p.nombre && p.nombre.toLowerCase().includes(s)) ||
            (p.codigo && p.codigo.toLowerCase().includes(s))
        );
    });
});

const fmtMoneda = (val) => {
    if (val == null) return '$0.00';
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
    }).format(val);
};

const totalInversionCalculada = computed(() => {
    let sum = 0;
    for (const p of productosFiltrados.value) {
        // Suggested purchase: diff between stock_minimo and current stock + some buffer, OR just 1 if not calculated.
        const min = p.stock_minimo || 5;
        let diff = min - p.stock;
        if (diff < 1) diff = 1;
        sum += (p.precio_compra || 0) * diff;
    }
    return sum;
});
</script>

<template>
    <AppLayout title="Para Comprar (Poco Stock)">
        <Head title="Productos para comprar" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-black text-gray-800 dark:text-gray-100 uppercase tracking-widest flex items-center gap-2">
                            <span class="text-rose-500">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </span>
                            Sugerencias de Compra
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Productos con bajo inventario (<= Stock Mínimo), ordenados por mayor cantidad de ventas históricas.
                        </p>
                    </div>

                    <Link :href="route('reportes.index')" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm text-sm font-black text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 uppercase tracking-widest transition-colors">
                        ← Regresar
                    </Link>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-[2rem] p-6 shadow-xl border border-gray-100 dark:border-gray-700/50 relative overflow-hidden">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-rose-500/10 rounded-full blur-2xl"></div>
                        <h3 class="text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Productos a Comprar</h3>
                        <p class="text-3xl font-black text-gray-900 dark:text-white tabular-nums">{{ productosFiltrados.length }}</p>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 rounded-[2rem] p-6 shadow-xl border border-gray-100 dark:border-gray-700/50 relative overflow-hidden">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-500/10 rounded-full blur-2xl"></div>
                        <h3 class="text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Inversión Estimada (Llenar al mínimo)</h3>
                        <p class="text-3xl font-black text-emerald-600 dark:text-emerald-400 tabular-nums">{{ fmtMoneda(totalInversionCalculada) }}</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-xl sm:rounded-[2rem] border border-gray-100 dark:border-gray-700/50 overflow-hidden">
                    
                    <div class="p-6 border-b border-gray-100 dark:border-gray-700/50 bg-gray-50/50 dark:bg-gray-800/50">
                        <div class="max-w-md relative">
                            <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </span>
                            <input 
                                v-model="searchTerm" 
                                type="search"
                                placeholder="Buscar por código o nombre..."
                                class="w-full pl-10 pr-4 py-3 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:border-cyan-500 focus:ring focus:ring-cyan-500/20 shadow-sm text-sm"
                            >
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800/80">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Código</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Producto</th>
                                    <th scope="col" class="px-6 py-4 text-center text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Ventas Totales</th>
                                    <th scope="col" class="px-6 py-4 text-center text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">S. Actual</th>
                                    <th scope="col" class="px-6 py-4 text-center text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">S. Mínimo</th>
                                    <th scope="col" class="px-6 py-4 text-right text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Sugerido (Faltante)</th>
                                    <th scope="col" class="px-6 py-4 text-right text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Costo Compra</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700/50">
                                <tr v-for="producto in productosFiltrados" :key="producto.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-6 py-4 font-mono font-bold text-gray-600 dark:text-gray-300">
                                        {{ producto.codigo || 'S/C' }}
                                    </td>
                                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                        <div class="line-clamp-2" :title="producto.nombre">{{ producto.nombre }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-xs font-black uppercase"
                                              :class="producto.total_vendido > 10 ? 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'">
                                            🔥 {{ producto.total_vendido }} u.
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-xs font-black uppercase bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-300">
                                            {{ producto.stock }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center font-bold text-gray-500 dark:text-gray-400 tabular-nums">
                                        {{ producto.stock_minimo || 5 }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-black text-blue-600 dark:text-blue-400 tabular-nums">
                                        +{{ Math.max(((producto.stock_minimo || 5) - producto.stock), 1) }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-semibold text-gray-600 dark:text-gray-300 tabular-nums">
                                        {{ fmtMoneda(producto.precio_compra) }}
                                    </td>
                                </tr>
                                <tr v-if="productosFiltrados.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                        No hay productos con poco inventario por reabastecer.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
