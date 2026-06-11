<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    categorias: Array,
    estadisticas: Object,
    filtros: Object,
    allCategorias: Array,
});

const fechaInicio = ref(props.filtros.fecha_inicio);
const fechaFin = ref(props.filtros.fecha_fin);
const categoriaId = ref(props.filtros.categoria_id);

const aplicarFiltros = () => {
    router.get(route('reportes-inventario.rotacion'), {
        fecha_inicio: fechaInicio.value,
        fecha_fin: fechaFin.value,
        categoria_id: categoriaId.value,
    }, { preserveState: true });
};

const formatCurrency = (n) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(n);

const getRotacionColor = (rotacion) => {
    if (rotacion >= 6) return 'text-emerald-600 dark:text-emerald-400';
    if (rotacion >= 3) return 'text-amber-600 dark:text-amber-400';
    return 'text-rose-600 dark:text-rose-400';
};

const getRotacionBg = (rotacion) => {
    if (rotacion >= 6) return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300';
    if (rotacion >= 3) return 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300';
    return 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300';
};

const getDiasColor = (dias) => {
    if (dias <= 60) return 'text-emerald-600 dark:text-emerald-400';
    if (dias <= 120) return 'text-amber-600 dark:text-amber-400';
    return 'text-rose-600 dark:text-rose-400';
};
</script>

<template>
    <AppLayout title="Rotación de Inventario">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-black text-2xl text-slate-800 dark:text-white tracking-tight">
                        Rotación de <span class="text-indigo-600 dark:text-indigo-400">Inventario</span>
                    </h2>
                    <p class="text-xs text-slate-500 uppercase font-bold tracking-widest mt-0.5">Costo de Ventas vs Valor del Inventario</p>
                </div>
            </div>
        </template>

        <div class="py-12 bg-slate-50 dark:bg-slate-950 min-h-screen">
            <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-12">
                <!-- Filtros -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 mb-6">
                    <div class="flex flex-wrap gap-4 items-end">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1 ml-1">Fecha Inicio</label>
                            <input type="date" v-model="fechaInicio" @change="aplicarFiltros"
                                class="bg-slate-50 dark:bg-slate-800 border-none rounded-2xl font-bold text-sm shadow-sm focus:ring-indigo-500 px-4 py-2" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1 ml-1">Fecha Fin</label>
                            <input type="date" v-model="fechaFin" @change="aplicarFiltros"
                                class="bg-slate-50 dark:bg-slate-800 border-none rounded-2xl font-bold text-sm shadow-sm focus:ring-indigo-500 px-4 py-2" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1 ml-1">Categoría</label>
                            <select v-model="categoriaId" @change="aplicarFiltros"
                                class="bg-slate-50 dark:bg-slate-800 border-none rounded-2xl font-bold text-sm shadow-sm focus:ring-indigo-500 min-w-[160px]">
                                <option value="">Todas las categorías</option>
                                <option v-for="cat in allCategorias" :key="cat.id" :value="cat.id">{{ cat.nombre }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- KPIs Generales -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm p-5">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Rotación General</p>
                        <p class="text-2xl font-black font-mono" :class="getRotacionColor(estadisticas.rotacion_general)">
                            {{ estadisticas.rotacion_general }}x
                        </p>
                        <p class="text-xs text-slate-400 mt-1">Veces que se renueva el inventario</p>
                    </div>
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm p-5">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Días en Inventario</p>
                        <p class="text-2xl font-black font-mono" :class="getDiasColor(estadisticas.dias_inventario_general)">
                            {{ estadisticas.dias_inventario_general }} días
                        </p>
                        <p class="text-xs text-slate-400 mt-1">Tiempo promedio en almacén</p>
                    </div>
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm p-5">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Costo de Ventas</p>
                        <p class="text-2xl font-black font-mono text-rose-600 dark:text-rose-400">{{ formatCurrency(estadisticas.total_costo_ventas) }}</p>
                        <p class="text-xs text-slate-400 mt-1">Período: {{ estadisticas.periodo_inicio }} al {{ estadisticas.periodo_fin }}</p>
                    </div>
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm p-5">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Valor Inventario</p>
                        <p class="text-2xl font-black font-mono text-indigo-600 dark:text-indigo-400">{{ formatCurrency(estadisticas.total_valor_inventario) }}</p>
                        <p class="text-xs text-slate-400 mt-1">Costo total del inventario actual</p>
                    </div>
                </div>

                <!-- Tabla por Categoría -->
                <div class="bg-white dark:bg-slate-900 shadow-xl rounded-[2.5rem] overflow-hidden border border-slate-200 dark:border-slate-800">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left font-sans">
                            <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-400 border-b border-slate-200 dark:border-slate-800">
                                <tr>
                                    <th class="px-5 py-4 font-black uppercase tracking-wider text-[10px]">Categoría</th>
                                    <th class="px-5 py-4 font-black uppercase tracking-wider text-[10px] text-right">Productos</th>
                                    <th class="px-5 py-4 font-black uppercase tracking-wider text-[10px] text-right">Stock Total</th>
                                    <th class="px-5 py-4 font-black uppercase tracking-wider text-[10px] text-right">Valor Inventario</th>
                                    <th class="px-5 py-4 font-black uppercase tracking-wider text-[10px] text-right">Costo Ventas</th>
                                    <th class="px-5 py-4 font-black uppercase tracking-wider text-[10px] text-right">Unid. Vendidas</th>
                                    <th class="px-5 py-4 font-black uppercase tracking-wider text-[10px] text-right">Rotación</th>
                                    <th class="px-5 py-4 font-black uppercase tracking-wider text-[10px] text-right">Días en Inv.</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                                <tr v-for="cat in categorias" :key="cat.id"
                                    class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="px-5 py-3.5 font-semibold text-slate-800 dark:text-slate-200">{{ cat.nombre }}</td>
                                    <td class="px-5 py-3.5 text-right font-mono text-slate-600 dark:text-slate-400">{{ cat.total_productos }}</td>
                                    <td class="px-5 py-3.5 text-right font-mono text-slate-600 dark:text-slate-400">{{ cat.stock_total }}</td>
                                    <td class="px-5 py-3.5 text-right font-mono text-indigo-600 dark:text-indigo-400 font-medium">{{ formatCurrency(cat.valor_inventario) }}</td>
                                    <td class="px-5 py-3.5 text-right font-mono text-rose-600 dark:text-rose-400 font-medium">{{ formatCurrency(cat.costo_ventas) }}</td>
                                    <td class="px-5 py-3.5 text-right font-mono text-slate-600 dark:text-slate-400">{{ cat.unidades_vendidas }}</td>
                                    <td class="px-5 py-3.5 text-right">
                                        <span class="px-2.5 py-1 rounded-xl text-[11px] font-black font-mono" :class="getRotacionBg(cat.rotacion)">
                                            {{ cat.rotacion }}x
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-mono font-bold" :class="getDiasColor(cat.dias_inventario)">
                                        {{ cat.dias_inventario }} días
                                    </td>
                                </tr>
                                <tr v-if="categorias.length === 0">
                                    <td colspan="8" class="px-6 py-16 text-center text-slate-400 italic">No hay datos de inventario para el período seleccionado</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Leyenda -->
                <div class="mt-6 p-4 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800">
                    <p class="text-xs font-bold text-slate-500 mb-2 uppercase tracking-wider">Interpretación de Rotación</p>
                    <div class="flex flex-wrap gap-4 text-xs text-slate-500">
                        <span class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                            6x+ = Excelente (alta rotación)
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                            3x-6x = Moderada
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                            &lt;3x = Baja (capital ocioso)
                        </span>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
