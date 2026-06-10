<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    reportData: Object,
    filters: Object
});

const mes = ref(props.filters.mes);
const anio = ref(props.filters.anio);

const updateReport = () => {
    router.get(route('contabilidad.reportes.flujo-efectivo'), {
        mes: mes.value,
        anio: anio.value
    }, { preserveState: true });
};

const formatCurrency = (n) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(n);

const meses = [
    { id: '01', nombre: 'Enero' }, { id: '02', nombre: 'Febrero' }, { id: '03', nombre: 'Marzo' },
    { id: '04', nombre: 'Abril' }, { id: '05', nombre: 'Mayo' }, { id: '06', nombre: 'Junio' },
    { id: '07', nombre: 'Julio' }, { id: '08', nombre: 'Agosto' }, { id: '09', nombre: 'Septiembre' },
    { id: '10', nombre: 'Octubre' }, { id: '11', nombre: 'Noviembre' }, { id: '12', nombre: 'Diciembre' }
];
const anios = ['2024', '2025', '2026'];
</script>

<template>
    <AppLayout title="Flujo de Efectivo">
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="font-black text-2xl text-slate-800 dark:text-white tracking-tight">
                        Flujo de <span class="text-indigo-600 dark:text-indigo-400">Efectivo</span>
                    </h2>
                    <p class="text-xs text-slate-500 uppercase font-bold tracking-widest mt-0.5">Estado de Flujos de Efectivo NIF B-2</p>
                </div>
            </div>
        </template>

        <div class="py-12 bg-slate-50 dark:bg-slate-950 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-slate-900 shadow-2xl rounded-[3rem] overflow-hidden border border-slate-100 dark:border-slate-800 p-8 sm:p-10">

                    <!-- Filtros -->
                    <div class="flex flex-wrap gap-4 mb-8 bg-slate-50 dark:bg-slate-800/50 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 items-end justify-between">
                        <div class="flex flex-wrap items-center gap-4">
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1 ml-1">Mes</label>
                                <select v-model="mes" @change="updateReport" class="bg-white dark:bg-slate-800 border-none rounded-2xl font-bold text-sm shadow-sm focus:ring-indigo-500 min-w-[120px]">
                                    <option v-for="m in meses" :key="m.id" :value="m.id">{{ m.nombre }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1 ml-1">Año</label>
                                <select v-model="anio" @change="updateReport" class="bg-white dark:bg-slate-800 border-none rounded-2xl font-bold text-sm shadow-sm focus:ring-indigo-500 min-w-[100px]">
                                    <option v-for="a in anios" :key="a" :value="a">{{ a }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Saldo Inicial -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-5 border border-slate-100 dark:border-slate-800">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Saldo Inicial</p>
                            <p class="text-2xl font-black font-mono text-slate-800 dark:text-white">{{ formatCurrency(reportData.saldo_inicial) }}</p>
                        </div>
                        <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl p-5 border border-emerald-100 dark:border-emerald-800">
                            <p class="text-[10px] font-black uppercase tracking-widest text-emerald-600 dark:text-emerald-400 mb-1">Flujo Neto del Período</p>
                            <p class="text-2xl font-black font-mono" :class="reportData.flujo_neto >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                {{ formatCurrency(reportData.flujo_neto) }}
                            </p>
                        </div>
                        <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-2xl p-5 border border-indigo-100 dark:border-indigo-800">
                            <p class="text-[10px] font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400 mb-1">Saldo Final</p>
                            <p class="text-2xl font-black font-mono" :class="reportData.saldo_final >= 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-rose-600 dark:text-rose-400'">
                                {{ formatCurrency(reportData.saldo_final) }}
                            </p>
                        </div>
                    </div>

                    <!-- Ingresos -->
                    <div class="mb-8">
                        <div class="flex items-center justify-between px-5 py-4 bg-gradient-to-r from-emerald-600 to-emerald-700 rounded-2xl mb-2">
                            <h4 class="font-black text-white uppercase tracking-wider text-sm flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Ingresos de Efectivo
                            </h4>
                            <span class="text-white font-black font-mono text-lg">{{ formatCurrency(reportData.total_ingresos) }}</span>
                        </div>

                        <div class="overflow-x-auto rounded-2xl border border-slate-100 dark:border-slate-800">
                            <table class="w-full text-xs text-left font-sans">
                                <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-400 border-b border-slate-100 dark:border-slate-800">
                                    <tr>
                                        <th class="px-5 py-3 font-black uppercase tracking-wider text-[10px]">Fecha</th>
                                        <th class="px-5 py-3 font-black uppercase tracking-wider text-[10px]">Póliza</th>
                                        <th class="px-5 py-3 font-black uppercase tracking-wider text-[10px]">Concepto</th>
                                        <th class="px-5 py-3 font-black uppercase tracking-wider text-[10px]">Tipo</th>
                                        <th class="px-5 py-3 font-black uppercase tracking-wider text-[10px] text-right">Monto</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/60">
                                    <tr v-for="(item, i) in reportData.ingresos" :key="i"
                                        class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors">
                                        <td class="px-5 py-2.5 font-mono text-slate-500 dark:text-slate-400">{{ item.fecha }}</td>
                                        <td class="px-5 py-2.5 font-mono text-slate-600 dark:text-slate-300">{{ item.numero }}</td>
                                        <td class="px-5 py-2.5 text-slate-800 dark:text-slate-200">{{ item.concepto }}</td>
                                        <td class="px-5 py-2.5">
                                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider"
                                                :class="item.tipo === 'ingreso' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">
                                                {{ item.tipo === 'ingreso' ? 'Ingreso' : 'Egreso' }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-2.5 text-right font-mono font-medium text-emerald-600 dark:text-emerald-400">
                                            {{ formatCurrency(item.monto) }}
                                        </td>
                                    </tr>
                                    <tr v-if="reportData.ingresos?.length === 0">
                                        <td colspan="5" class="px-6 py-12 text-center text-slate-400 italic">No hubo ingresos de efectivo en este período</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Egresos -->
                    <div class="mb-8">
                        <div class="flex items-center justify-between px-5 py-4 bg-gradient-to-r from-rose-600 to-rose-700 rounded-2xl mb-2">
                            <h4 class="font-black text-white uppercase tracking-wider text-sm flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                Egresos de Efectivo
                            </h4>
                            <span class="text-white font-black font-mono text-lg">{{ formatCurrency(reportData.total_egresos) }}</span>
                        </div>

                        <div class="overflow-x-auto rounded-2xl border border-slate-100 dark:border-slate-800">
                            <table class="w-full text-xs text-left font-sans">
                                <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-400 border-b border-slate-100 dark:border-slate-800">
                                    <tr>
                                        <th class="px-5 py-3 font-black uppercase tracking-wider text-[10px]">Fecha</th>
                                        <th class="px-5 py-3 font-black uppercase tracking-wider text-[10px]">Póliza</th>
                                        <th class="px-5 py-3 font-black uppercase tracking-wider text-[10px]">Concepto</th>
                                        <th class="px-5 py-3 font-black uppercase tracking-wider text-[10px]">Tipo</th>
                                        <th class="px-5 py-3 font-black uppercase tracking-wider text-[10px] text-right">Monto</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/60">
                                    <tr v-for="(item, i) in reportData.egresos" :key="i"
                                        class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors">
                                        <td class="px-5 py-2.5 font-mono text-slate-500 dark:text-slate-400">{{ item.fecha }}</td>
                                        <td class="px-5 py-2.5 font-mono text-slate-600 dark:text-slate-300">{{ item.numero }}</td>
                                        <td class="px-5 py-2.5 text-slate-800 dark:text-slate-200">{{ item.concepto }}</td>
                                        <td class="px-5 py-2.5">
                                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider"
                                                :class="item.tipo === 'egreso' ? 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">
                                                {{ item.tipo === 'egreso' ? 'Egreso' : 'Ingreso' }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-2.5 text-right font-mono font-medium text-rose-600 dark:text-rose-400">
                                            {{ formatCurrency(Math.abs(item.monto)) }}
                                        </td>
                                    </tr>
                                    <tr v-if="reportData.egresos?.length === 0">
                                        <td colspan="5" class="px-6 py-12 text-center text-slate-400 italic">No hubo egresos de efectivo en este período</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Resumen -->
                    <div class="mt-6 p-6 bg-slate-50 dark:bg-slate-800/50 rounded-[2rem] border border-slate-100 dark:border-slate-800">
                        <h4 class="font-black text-sm uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-4">Resumen del Período</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Saldo Inicial</p>
                                <p class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ formatCurrency(reportData.saldo_inicial) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Total Ingresos</p>
                                <p class="font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ formatCurrency(reportData.total_ingresos) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Total Egresos</p>
                                <p class="font-mono font-bold text-rose-600 dark:text-rose-400">{{ formatCurrency(reportData.total_egresos) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Saldo Final</p>
                                <p class="font-mono font-black text-lg" :class="reportData.saldo_final >= 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-rose-600 dark:text-rose-400'">
                                    {{ formatCurrency(reportData.saldo_final) }}
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>
