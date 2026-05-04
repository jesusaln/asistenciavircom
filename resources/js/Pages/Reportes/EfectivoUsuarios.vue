<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';

const props = defineProps({
    data: Array,
    filtros: Object,
    totales: Object
});

const fechaInicio = ref(props.filtros.fecha_inicio);
const fechaFin = ref(props.filtros.fecha_fin);
const search = ref('');

const filtrar = () => {
    router.get(route('reportes.efectivo-usuarios'), {
        fecha_inicio: fechaInicio.value,
        fecha_fin: fechaFin.value,
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN'
    }).format(value);
};

const filteredData = computed(() => {
    if (!search.value) return props.data;
    const s = search.value.toLowerCase();
    return props.data.filter(item => 
        item.name.toLowerCase().includes(s)
    );
});

const totalSaldo = computed(() => props.totales.total_saldo);
</script>

<template>
    <AppLayout title="Control de Efectivo por Usuario">
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Control de Efectivo: ¿Quién tiene el dinero?
                </h2>
                <div class="flex flex-wrap gap-2 items-center">
                    <div class="flex items-center bg-white rounded-lg border border-gray-300 px-3 py-1 shadow-sm">
                        <span class="text-xs font-bold text-gray-500 uppercase mr-2">Desde:</span>
                        <input type="date" v-model="fechaInicio" @change="filtrar" class="border-none focus:ring-0 text-sm p-1" />
                    </div>
                    <div class="flex items-center bg-white rounded-lg border border-gray-300 px-3 py-1 shadow-sm">
                        <span class="text-xs font-bold text-gray-500 uppercase mr-2">Hasta:</span>
                        <input type="date" v-model="fechaFin" @change="filtrar" class="border-none focus:ring-0 text-sm p-1" />
                    </div>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Stat Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center">
                        <div class="p-4 bg-green-100 rounded-xl text-green-600 mr-4">
                            <FontAwesomeIcon icon="cash-register" size="lg" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Recolectado (Cash)</p>
                            <h3 class="text-2xl font-black text-gray-900">{{ formatCurrency(totales.total_recolectado) }}</h3>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center">
                        <div class="p-4 bg-blue-100 rounded-xl text-blue-600 mr-4">
                            <FontAwesomeIcon icon="paper-plane" size="lg" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Entregado/Gastado</p>
                            <h3 class="text-2xl font-black text-gray-900">{{ formatCurrency(totales.total_entregado) }}</h3>
                        </div>
                    </div>

                    <div :class="[totalSaldo > 0 ? 'bg-orange-50 border-orange-200' : 'bg-green-50 border-green-200', 'rounded-2xl shadow-sm border p-6 flex items-center transition-colors']">
                        <div :class="[totalSaldo > 0 ? 'bg-orange-100 text-orange-600' : 'bg-green-100 text-green-600', 'p-4 rounded-xl mr-4']">
                            <FontAwesomeIcon :icon="totalSaldo > 0 ? 'wallet' : 'check-circle'" size="lg" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Pendiente en Manos</p>
                            <h3 :class="[totalSaldo > 0 ? 'text-orange-700' : 'text-green-700', 'text-2xl font-black']">{{ formatCurrency(totalSaldo) }}</h3>
                        </div>
                    </div>
                </div>

                <!-- Main Table -->
                <div class="bg-white shadow-xl sm:rounded-2xl overflow-hidden border border-gray-100">
                    <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
                        <div class="relative w-full md:w-64">
                            <input 
                                v-model="search" 
                                type="text" 
                                placeholder="Buscar usuario..." 
                                class="w-full pl-10 pr-4 py-2 rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm"
                            />
                            <FontAwesomeIcon icon="search" class="absolute left-3 top-3 text-gray-400" />
                        </div>
                        <div class="flex gap-2">
                             <!-- Summary note -->
                             <p class="text-xs text-gray-500 italic">
                                * Saldo = (Efectivo Ventas + Efectivo Cobranzas + Ingresos Caja Chica) - (Entregas Dinero + Egresos Caja Chica)
                             </p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600">
                                    <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider">Usuario / Técnico</th>
                                    <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-right">Cobrado (Cash)</th>
                                    <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-right">Entregas/Gastos</th>
                                    <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-right">Saldo Actual</th>
                                    <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="user in filteredData" :key="user.user_id" class="hover:bg-blue-50 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold mr-3 group-hover:bg-blue-200 transition-colors">
                                                {{ user.name.charAt(0) }}
                                            </div>
                                            <div>
                                                <p class="font-bold text-gray-900">{{ user.name }}</p>
                                                <p class="text-xs text-gray-500">ID: {{ user.user_id }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <p class="font-bold text-gray-900">{{ formatCurrency(user.total_recolectado) }}</p>
                                        <div class="text-[10px] text-gray-400">
                                            V: {{ formatCurrency(user.ventas_efectivo) }} | C: {{ formatCurrency(user.cobranzas_efectivo) }} | Rec: {{ formatCurrency(user.entregas_recibidas) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <p class="font-bold text-gray-900">{{ formatCurrency(user.total_entregado) }}</p>
                                        <div class="text-[10px] text-gray-400">
                                            Ent: {{ formatCurrency(user.entregas_efectivo) }} | Ch: {{ formatCurrency(user.caja_chica_egresos) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span :class="[user.saldo > 1 ? 'text-orange-600' : (user.saldo < -1 ? 'text-red-500' : 'text-green-600'), 'font-black text-lg']">
                                            {{ formatCurrency(user.saldo) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span v-if="user.saldo > 1" class="bg-orange-100 text-orange-700 px-3 py-1 rounded-full text-xs font-bold border border-orange-200">
                                            Dinero en mano
                                        </span>
                                        <span v-else-if="user.saldo < -1" class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-bold border border-red-200">
                                            Debe entregar
                                        </span>
                                        <span v-else class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold border border-green-200">
                                            Al día
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="filteredData.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                        <FontAwesomeIcon icon="info-circle" size="2x" class="mb-4 block mx-auto opacity-20" />
                                        No se encontraron movimientos de efectivo en este período.
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-50 font-black">
                                    <td class="px-6 py-4 text-gray-700">TOTAL GENERAL</td>
                                    <td class="px-6 py-4 text-right text-gray-900">{{ formatCurrency(totales.total_recolectado) }}</td>
                                    <td class="px-6 py-4 text-right text-gray-900">{{ formatCurrency(totales.total_entregado) }}</td>
                                    <td class="px-6 py-4 text-right text-blue-700 text-xl">{{ formatCurrency(totales.total_saldo) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Guidance/Info -->
                <div class="mt-8 bg-blue-50 rounded-2xl p-6 border border-blue-100">
                    <h4 class="text-blue-800 font-bold mb-2 flex items-center">
                        <FontAwesomeIcon icon="lightbulb" class="mr-2" />
                        ¿Cómo interpretar este reporte?
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-blue-700">
                        <p>
                            <strong>Cobrado (Cash):</strong> Es la suma de todas las ventas y cobranzas marcadas como "Pagado en Efectivo" donde este usuario fue el responsable del cobro.
                        </p>
                        <p>
                            <strong>Entregas/Gastos:</strong> Es el dinero que el usuario ya no tiene porque lo entregó a administración (Entrega de Dinero) o lo gastó en compras autorizadas (Caja Chica).
                        </p>
                        <p>
                            <strong>Saldo Actual:</strong> Representa el dinero líquido que el usuario debería tener físicamente consigo. Si es positivo, tiene pendiente entregarlo.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.font-black { font-weight: 900; }
</style>
