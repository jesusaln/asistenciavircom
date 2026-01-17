<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import CategoryManager from '@/Components/Soporte/CategoryManager.vue';
import { ref } from 'vue';

const props = defineProps({
    porEstado: Object,
    porPrioridad: Object,
    porTecnico: Array,
    tiempoPromedioResolucion: Number,
    ticketsUltimos7Dias: Array,
    cumplimientoSla: Number,
    stats: Object,
    categorias: Array,
    horasPorTecnico: Array,
    horasPorPoliza: Array,
});

const showCategoryModal = ref(false);

const estadoColores = {
    abierto: '#3B82F6',
    en_progreso: '#F59E0B',
    pendiente: '#F97316',
    resuelto: '#10B981',
    cerrado: '#6B7280',
};

const prioridadColores = {
    urgente: '#EF4444',
    alta: '#F97316',
    media: '#F59E0B',
    baja: '#10B981',
};
</script>

<template>
    <AppLayout title="Dashboard de Soporte">
        <Head title="Dashboard de Soporte" />

        <div class="py-6">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Dashboard de Soporte</h1>
                        <p class="text-gray-600">Métricas y rendimiento del equipo</p>
                    </div>
                    <div class="flex gap-2 flex-wrap">
                        <a 
                            :href="route('soporte.reporte.horas-tecnico')" 
                            target="_blank"
                            class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition flex items-center gap-2"
                        >
                            <span>📄</span> Reporte Horas
                        </a>
                        <button @click="showCategoryModal = true" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                            <font-awesome-icon icon="tags" class="mr-2" />
                            Categorías
                        </button>
                        <Link :href="route('soporte.index')" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition">
                            Ver Tickets →
                        </Link>
                    </div>
                </div>

                <!-- Stats principales -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-xl p-5 shadow-sm">
                        <div class="text-3xl font-bold text-blue-600">{{ stats.total_abiertos }}</div>
                        <div class="text-sm text-gray-500">Tickets Abiertos</div>
                    </div>
                    <div class="bg-white rounded-xl p-5 shadow-sm">
                        <div class="text-3xl font-bold text-red-600">{{ stats.urgentes }}</div>
                        <div class="text-sm text-gray-500">Urgentes</div>
                    </div>
                    <div class="bg-white rounded-xl p-5 shadow-sm">
                        <div class="text-3xl font-bold text-orange-600">{{ stats.vencidos }}</div>
                        <div class="text-sm text-gray-500">SLA Vencido</div>
                    </div>
                    <div class="bg-white rounded-xl p-5 shadow-sm">
                        <div class="text-3xl font-bold text-green-600">{{ stats.resueltos_hoy }}</div>
                        <div class="text-sm text-gray-500">Resueltos Hoy</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Por Estado -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="font-semibold text-gray-900 mb-4">Tickets por Estado</h3>
                        <div class="space-y-3">
                            <div v-for="(total, estado) in porEstado" :key="estado" class="flex items-center gap-3">
                                <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: estadoColores[estado] }"></div>
                                <span class="flex-1 text-sm text-gray-700 capitalize">{{ estado.replace('_', ' ') }}</span>
                                <span class="text-sm font-semibold text-gray-900">{{ total }}</span>
                                <div class="w-24 h-2 bg-gray-100 rounded-full overflow-hidden">
                                    <div 
                                        class="h-full rounded-full" 
                                        :style="{ 
                                            width: `${(total / Object.values(porEstado).reduce((a,b) => a+b, 0)) * 100}%`,
                                            backgroundColor: estadoColores[estado]
                                        }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Por Prioridad -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="font-semibold text-gray-900 mb-4">Tickets Abiertos por Prioridad</h3>
                        <div class="space-y-3">
                            <div v-for="(total, prioridad) in porPrioridad" :key="prioridad" class="flex items-center gap-3">
                                <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: prioridadColores[prioridad] }"></div>
                                <span class="flex-1 text-sm text-gray-700 capitalize">{{ prioridad }}</span>
                                <span class="text-sm font-semibold text-gray-900">{{ total }}</span>
                                <div class="w-24 h-2 bg-gray-100 rounded-full overflow-hidden">
                                    <div 
                                        class="h-full rounded-full" 
                                        :style="{ 
                                            width: `${(total / Object.values(porPrioridad).reduce((a,b) => a+b, 1)) * 100}%`,
                                            backgroundColor: prioridadColores[prioridad]
                                        }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Tiempo promedio resolución -->
                    <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-sm p-6 text-white">
                        <h3 class="font-semibold mb-2 opacity-90">Tiempo Promedio de Resolución</h3>
                        <div class="text-4xl font-bold">{{ tiempoPromedioResolucion }}h</div>
                        <div class="text-sm opacity-75 mt-1">Últimos 30 días</div>
                    </div>

                    <!-- Cumplimiento SLA -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="font-semibold text-gray-900 mb-2">Cumplimiento SLA</h3>
                        <div class="flex items-end gap-2">
                            <div class="text-4xl font-bold" :class="cumplimientoSla >= 90 ? 'text-green-600' : cumplimientoSla >= 70 ? 'text-yellow-600' : 'text-red-600'">
                                {{ cumplimientoSla }}%
                            </div>
                            <div class="text-sm text-gray-500 mb-1">Últimos 30 días</div>
                        </div>
                        <div class="mt-3 h-3 bg-gray-100 rounded-full overflow-hidden">
                            <div 
                                class="h-full rounded-full transition-all"
                                :class="cumplimientoSla >= 90 ? 'bg-green-500' : cumplimientoSla >= 70 ? 'bg-yellow-500' : 'bg-red-500'"
                                :style="{ width: `${cumplimientoSla}%` }"
                            ></div>
                        </div>
                    </div>

                    <!-- Por Técnico -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="font-semibold text-gray-900 mb-4">Tickets por Técnico</h3>
                        <div class="space-y-2">
                            <div v-for="item in porTecnico" :key="item.asignado_id || 'sin'" class="flex items-center justify-between py-1">
                                <span class="text-sm text-gray-700">{{ item.asignado?.name || 'Sin asignar' }}</span>
                                <span class="text-sm font-semibold bg-orange-100 text-orange-700 px-2 py-0.5 rounded">{{ item.total }}</span>
                            </div>
                            <div v-if="porTecnico.length === 0" class="text-sm text-gray-400 text-center py-2">
                                Sin datos
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tickets últimos 7 días -->
                <div class="bg-white rounded-xl shadow-sm p-6 mt-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Tickets Creados (Últimos 7 días)</h3>
                    <div class="flex items-end gap-2 h-32">
                        <div 
                            v-for="dia in ticketsUltimos7Dias" 
                            :key="dia.fecha"
                            class="flex-1 flex flex-col items-center"
                        >
                            <div 
                                class="w-full bg-orange-500 rounded-t transition-all hover:bg-orange-600"
                                :style="{ 
                                    height: `${Math.max(10, (dia.total / Math.max(...ticketsUltimos7Dias.map(d => d.total))) * 100)}%`
                                }"
                            ></div>
                            <div class="text-xs text-gray-500 mt-2">{{ new Date(dia.fecha).toLocaleDateString('es-MX', { weekday: 'short' }) }}</div>
                            <div class="text-xs font-semibold text-gray-700">{{ dia.total }}</div>
                        </div>
                        </div>
                    </div>
                </div>

                <!-- NUEVO: Sección de Horas Trabajadas -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                    <!-- Horas por Técnico (30 días) -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <div class="flex items-center gap-2 mb-4">
                            <span class="text-2xl">⏱️</span>
                            <h3 class="font-semibold text-gray-900">Horas Trabajadas por Técnico</h3>
                            <span class="text-xs text-gray-400 ml-auto">Últimos 30 días</span>
                        </div>
                        <div v-if="horasPorTecnico && horasPorTecnico.length > 0" class="space-y-3">
                            <div v-for="item in horasPorTecnico" :key="item.asignado_id" class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-sm font-bold">
                                        {{ item.asignado?.name?.charAt(0) || '?' }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ item.asignado?.name || 'Sin asignar' }}</div>
                                        <div class="text-xs text-gray-500">{{ item.total_tickets }} tickets</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-blue-600">{{ Number(item.total_horas).toFixed(1) }}h</div>
                                    <div class="text-xs text-gray-400">{{ (item.total_horas / item.total_tickets).toFixed(1) }}h/ticket</div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-gray-400">
                            <div class="text-3xl mb-2">📊</div>
                            No hay datos de horas registradas
                        </div>
                    </div>

                    <!-- Horas por Póliza (Mes actual) -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <div class="flex items-center gap-2 mb-4">
                            <span class="text-2xl">🛡️</span>
                            <h3 class="font-semibold text-gray-900">Consumo de Horas por Póliza</h3>
                            <span class="text-xs text-gray-400 ml-auto">Mes actual</span>
                        </div>
                        <div v-if="horasPorPoliza && horasPorPoliza.length > 0" class="space-y-3">
                            <div v-for="item in horasPorPoliza" :key="item.poliza_id" class="p-3 rounded-lg border" :class="item.poliza?.horas_incluidas_mensual && Number(item.total_horas) > item.poliza.horas_incluidas_mensual ? 'border-red-200 bg-red-50' : 'border-gray-100 bg-white'">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <div class="text-sm font-bold text-gray-900">{{ item.poliza?.nombre || 'Póliza' }}</div>
                                        <div class="text-xs text-gray-500 font-mono">{{ item.poliza?.folio }}</div>
                                        <div class="text-xs text-gray-400">{{ item.poliza?.cliente?.nombre_razon_social }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-bold" :class="item.poliza?.horas_incluidas_mensual && Number(item.total_horas) > item.poliza.horas_incluidas_mensual ? 'text-red-600' : 'text-green-600'">
                                            {{ Number(item.total_horas).toFixed(1) }}h
                                        </div>
                                        <div v-if="item.poliza?.horas_incluidas_mensual" class="text-xs" :class="Number(item.total_horas) > item.poliza.horas_incluidas_mensual ? 'text-red-500 font-bold' : 'text-gray-400'">
                                            / {{ item.poliza.horas_incluidas_mensual }}h incluídas
                                        </div>
                                    </div>
                                </div>
                                <!-- Barra de progreso -->
                                <div v-if="item.poliza?.horas_incluidas_mensual" class="mt-2">
                                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div 
                                            class="h-full rounded-full transition-all"
                                            :class="Number(item.total_horas) > item.poliza.horas_incluidas_mensual ? 'bg-red-500' : 'bg-green-500'"
                                                :style="{ width: Math.min((item.total_horas / item.poliza.horas_incluidas_mensual) * 100, 100) + '%' }"
                                        ></div>
                                    </div>
                                    <div v-if="Number(item.total_horas) > item.poliza.horas_incluidas_mensual" class="mt-1 text-xs text-red-600 font-bold flex items-center gap-1">
                                        ⚠️ Excedido por {{ (item.total_horas - item.poliza.horas_incluidas_mensual).toFixed(1) }}h - Considerar ajuste de póliza
                                    </div>
                                </div>
                                <div class="flex justify-between items-center mt-2">
                                    <div class="text-xs text-gray-400">{{ item.total_tickets }} tickets atendidos</div>
                                    <a 
                                        :href="route('soporte.reporte.consumo-poliza', item.poliza_id)" 
                                        target="_blank"
                                        class="text-xs text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1"
                                    >
                                        📄 Ver Reporte
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-gray-400">
                            <div class="text-3xl mb-2">🛡️</div>
                            No hay consumo de horas en pólizas este mes
                        </div>
                    </div>
                </div>
            </div>
        

        <!-- Modal de Gestión de Categorías -->
        <Modal :show="showCategoryModal" @close="showCategoryModal = false" maxWidth="4xl">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800">Gestión de Categorías</h2>
                    <button @click="showCategoryModal = false" class="text-gray-500 hover:text-gray-700">
                        <font-awesome-icon icon="times" class="w-5 h-5" />
                    </button>
                </div>
                <!-- Componente Gestor -->
                <CategoryManager :categorias="props.categorias" />
            </div>
        </Modal>
    </AppLayout>
</template>
