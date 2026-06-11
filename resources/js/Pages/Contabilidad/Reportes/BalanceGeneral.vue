<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    reportData: Object,
    filters: Object
});

const fecha = ref(props.filters.fecha);

const updateReport = () => {
    router.get(route('contabilidad.reportes.balance-general'), {
        fecha: fecha.value
    }, { preserveState: true });
};

const formatCurrency = (n) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(n);
const formatPercent = (n) => new Intl.NumberFormat('es-MX', { style: 'percent', minimumFractionDigits: 1, maximumFractionDigits: 1 }).format(n / 100);

const secciones = computed(() => props.reportData?.secciones ?? []);

const cuentaCuadra = computed(() => {
    const diff = Math.abs(props.reportData.total_activo - props.reportData.total_pasivo_capital);
    return diff < 0.01;
});
</script>

<template>
    <AppLayout title="Balance General">
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="font-black text-2xl text-slate-800 dark:text-white tracking-tight">
                        Balance <span class="text-indigo-600 dark:text-indigo-400">General</span>
                    </h2>
                    <p class="text-xs text-slate-500 uppercase font-bold tracking-widest mt-0.5">Estado de Situación Financiera NIF A-5 (DOF México)</p>
                </div>
            </div>
        </template>

        <div class="py-12 bg-slate-50 dark:bg-slate-950 min-h-screen">
            <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-12">
                <div class="bg-white dark:bg-slate-900 shadow-2xl rounded-[3rem] overflow-hidden border border-slate-100 dark:border-slate-800 p-8 sm:p-10">

                    <!-- Filtros -->
                    <div class="flex flex-wrap gap-4 mb-8 bg-slate-50 dark:bg-slate-800/50 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 items-end justify-between">
                        <div class="flex flex-wrap items-center gap-4">
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1 ml-1">Fecha de Corte</label>
                                <input type="date" v-model="fecha" @change="updateReport"
                                    class="bg-white dark:bg-slate-800 border-none rounded-2xl font-bold text-sm shadow-sm focus:ring-indigo-500 px-4 py-2" />
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button @click="window.print()" class="px-5 py-2.5 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-2xl font-bold text-xs uppercase tracking-wider hover:bg-slate-300 dark:hover:bg-slate-700 transition-all flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                Imprimir
                            </button>
                        </div>
                    </div>

                    <!-- Encabezado del Balance -->
                    <div class="text-center mb-8">
                        <h3 class="text-xl font-black text-slate-800 dark:text-white">Balance General</h3>
                        <p class="text-sm text-slate-500 font-bold">Al {{ fecha }}</p>
                    </div>

                    <!-- Secciones del Balance -->
                    <div v-for="seccion in secciones" :key="seccion.key" class="mb-8">
                        <div class="flex items-center justify-between px-5 py-4 bg-gradient-to-r from-indigo-600 to-indigo-700 rounded-2xl mb-2">
                            <h4 class="font-black text-white uppercase tracking-wider text-sm">{{ seccion.titulo }}</h4>
                            <span class="text-white font-black font-mono text-lg">{{ formatCurrency(seccion.total) }}</span>
                        </div>

                        <div class="overflow-x-auto rounded-2xl border border-slate-100 dark:border-slate-800">
                            <table class="w-full text-xs text-left font-sans">
                                <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-400 border-b border-slate-100 dark:border-slate-800">
                                    <tr>
                                        <th class="px-5 py-3 font-black uppercase tracking-wider text-[10px]">Código</th>
                                        <th class="px-5 py-3 font-black uppercase tracking-wider text-[10px]">Cuenta</th>
                                        <th class="px-5 py-3 font-black uppercase tracking-wider text-[10px] text-right">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/60">
                                    <tr v-for="item in seccion.items" :key="item.codigo"
                                        class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors"
                                        :class="{ 'bg-slate-50/80 dark:bg-slate-800/30 font-bold': item.nivel === 2 }">
                                        <td class="px-5 py-2.5 font-mono text-slate-500 dark:text-slate-400">{{ item.codigo }}</td>
                                        <td class="px-5 py-2.5 text-slate-800 dark:text-slate-200"
                                            :class="{ 'pl-8 italic text-slate-600 dark:text-slate-400': item.nivel > 2 }">
                                            <span>{{ item.nombre }}</span>
                                        </td>
                                        <td class="px-5 py-2.5 text-right font-mono"
                                            :class="item.saldo < 0 ? 'text-rose-500 font-medium' : 'text-emerald-600 dark:text-emerald-400 font-medium'">
                                            {{ formatCurrency(Math.abs(item.saldo)) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Verificación de cuadratura -->
                    <div class="mt-8 p-6 rounded-[2rem] border"
                        :class="cuentaCuadra ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800' : 'bg-rose-50 dark:bg-rose-900/20 border-rose-200 dark:border-rose-800'">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div v-if="cuentaCuadra"
                                    class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-800 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <div v-else
                                    class="w-12 h-12 rounded-full bg-rose-100 dark:bg-rose-800 flex items-center justify-center animate-pulse">
                                    <svg class="w-6 h-6 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                </div>
                                <div>
                                    <p class="font-black text-sm uppercase tracking-wider" :class="cuentaCuadra ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300'">
                                        {{ cuentaCuadra ? 'Balance Cuadrado' : 'Balance Descuadrado' }}
                                    </p>
                                    <p class="text-xs text-slate-500 mt-0.5 font-mono">
                                        Activo: {{ formatCurrency(reportData.total_activo) }}
                                        &nbsp;|&nbsp; Pasivo + Capital: {{ formatCurrency(reportData.total_pasivo_capital) }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right font-mono font-black text-lg"
                                :class="cuentaCuadra ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                {{ formatCurrency(reportData.total_pasivo_capital) }}
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>
