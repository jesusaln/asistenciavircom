<template>
    <AppLayout title="Dashboard de Reportes">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                Dashboard de Reportes
            </h2>
        </template>
        
        <!-- Actions slot is not standard in AppLayout header but we can put it in the main content or customize AppLayout header slot if needed. 
             However, usually AppLayout header slot expects just title or simple content. 
             We'll put the period selector in the main content area top right if possible or just below header. -->
        
        <div class="py-6">
            <div class="w-full sm:px-6 lg:px-8">
                
                <div class="flex justify-end mb-6">
                    <select 
                        v-model="periodo"
                        @change="cambiarPeriodo"
                        class="block w-48 pl-3 pr-10 py-2 text-base border-gray-300 dark:border-slate-700 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md shadow-sm bg-white dark:bg-slate-900 dark:text-gray-100 transition-colors"
                    >
                        <option value="dia">Hoy</option>
                        <option value="semana">Esta Semana</option>
                        <option value="mes">Este Mes</option>
                        <option value="trimestre">Este Trimestre</option>
                        <option value="año">Este Año</option>
                    </select>
                </div>

                <!-- KPI Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <KpiCard 
                        title="Ventas Totales" 
                        :value="formatCurrency(categorias.ventas.estadisticas.total)" 
                        icon="shopping-cart" 
                        color="blue"
                    />
                     <KpiCard 
                        title="Ganancia Neta" 
                        :value="formatCurrency(categorias.finanzas.estadisticas.ganancia_neta)" 
                        icon="chart-line" 
                        color="green"
                    />
                     <KpiCard 
                        title="Cuentas por Cobrar" 
                        :value="formatCurrency(categorias.rentas.estadisticas.cobranzas_pendientes)" 
                        icon="hand-holding-usd" 
                        color="yellow"
                    />
                     <KpiCard 
                        title="Valor Inventario" 
                        :value="formatCurrency(categorias.inventario.estadisticas.valor_inventario)" 
                        icon="boxes" 
                        color="purple"
                    />
                </div>

                <!-- Sales Chart -->
                <div v-if="salesChartData" class="bg-white dark:bg-slate-900 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-slate-800 mb-8 transition-colors">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Evolución de Ventas</h3>
                    <div class="h-80">
                        <StatChart :data="salesChartData" type="line" />
                    </div>
                </div>

                <!-- Categories Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div v-for="(cat, key) in categorias" :key="key" class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-100 dark:border-slate-800 overflow-hidden transition-colors">
                        <div class="p-5 border-b border-gray-50 dark:border-slate-800 flex justify-between items-center bg-white dark:bg-slate-900/50">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ cat.titulo }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ cat.descripcion }}</p>
                            </div>
                            <div class="p-2 bg-white dark:bg-slate-800 rounded-lg border border-gray-100 dark:border-slate-700 shadow-sm">
                                <FontAwesomeIcon :icon="getIcon(key)" class="text-gray-400 dark:text-gray-500" />
                            </div>
                        </div>
                        
                        <div class="p-5">
                            <!-- Quick Stats per Category -->
                            <div class="grid grid-cols-3 gap-4 mb-6">
                                <div v-for="(stat, statKey) in cat.estadisticas" :key="statKey" class="text-center p-3 bg-gray-50 dark:bg-slate-800/50 rounded-lg">
                                    <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">{{ formatStatLabel(statKey) }}</div>
                                    <div class="font-semibold text-gray-800 dark:text-gray-100 text-sm truncate">
                                        {{ formatValue(stat, statKey) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="space-y-2">
                                <Link 
                                    v-for="report in cat.reportes" 
                                    :key="report.nombre"
                                    :href="report.url || route(report.ruta)"
                                    class="flex items-center justify-between p-3 rounded-lg hover:bg-blue-50 dark:hover:bg-slate-800/80 group transition-colors border border-transparent hover:border-blue-100 dark:hover:border-slate-700"
                                >
                                    <div class="flex items-center">
                                        <span class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center mr-3 group-hover:bg-blue-200 dark:group-hover:bg-blue-900/50 transition-colors">
                                            <FontAwesomeIcon :icon="getReportIcon(report.icono)" class="text-xs" />
                                        </span>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-blue-800 dark:group-hover:text-white">{{ report.nombre }}</span>
                                    </div>
                                    <FontAwesomeIcon icon="chevron-right" class="text-gray-300 dark:text-gray-600 group-hover:text-blue-400 dark:group-hover:text-blue-300 text-xs" />
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import KpiCard from '@/Components/Reportes/KpiCard.vue';
import StatChart from '@/Components/Reportes/StatChart.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
    categorias: Object,
    periodo: String,
    graficas: Object,
});

const salesChartData = computed(() => {
    if (!props.graficas || !props.graficas.labels) return null;

    return {
        labels: props.graficas.labels,
        datasets: [
            {
                label: 'Ventas',
                data: props.graficas.data,
                borderColor: '#3B82F6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4
            }
        ]
    };
});

const periodo = ref(props.periodo || 'mes');

const cambiarPeriodo = () => {
    router.visit(route('reportes.dashboard'), {
        data: { periodo: periodo.value },
        preserveScroll: true,
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value);
};

const formatStatLabel = (key) => {
    return key.replace(/_/g, ' ');
};

const formatValue = (value, key) => {
    if (typeof value === 'number') {
        if (key.includes('total') || key.includes('utilidad') || key.includes('ingresos') || key.includes('gastos') || key.includes('ganancia') || key.includes('valor')) {
            return formatCurrency(value);
        }
        return value.toLocaleString();
    }
    return value;
};

const getIcon = (key) => {
    const map = {
        ventas: 'shopping-cart',
        pagos: 'money-bill-wave',
        clientes: 'users',
        inventario: 'boxes',
        servicios: 'tools',
        rentas: 'handshake',
        finanzas: 'chart-pie',
        personal: 'user-tie',
        auditoria: 'shield-alt'
    };
    return map[key] || 'file-alt';
};

const getReportIcon = (faClass) => {
    // Helper to strip 'fas fa-' prefix from legacy data references if they exist in props
    if (typeof faClass === 'string' && faClass.startsWith('fas fa-')) {
        return faClass.replace('fas fa-', '');
    }
    return faClass || 'circle';
};
</script>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>

