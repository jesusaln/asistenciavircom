<template>
    <AppLayout title="Cuentas por Cobrar">
        <Head title="Cuentas por Cobrar" />

        <div class="min-h-screen bg-[#0F172A] text-slate-300 pb-12">
            <!-- Hero Header Section -->
            <div class="relative overflow-hidden bg-slate-900/50 border-b border-slate-800 pt-8 pb-12 mb-8">
                <div class="absolute top-0 right-0 -translate-y-1/2 translate-x-1/2 w-96 h-96 bg-indigo-600/10 blur-[100px] rounded-full"></div>
                <div class="absolute bottom-0 left-0 translate-y-1/2 -translate-x-1/2 w-64 h-64 bg-emerald-600/10 blur-[80px] rounded-full"></div>
                
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-2 py-0.5 bg-indigo-500/10 text-indigo-400 text-[10px] font-black uppercase tracking-widest rounded-md border border-indigo-500/20">Finanzas</span>
                                <span class="text-slate-500">•</span>
                                <span class="text-xs text-slate-400 font-medium">Gestión de Cobranza</span>
                            </div>
                            <h1 class="text-4xl font-black text-white tracking-tighter">
                                Cuentas por <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-emerald-400">Cobrar</span>
                            </h1>
                            <p class="text-slate-400 mt-2 font-medium max-w-2xl">Administra y da seguimiento a los pagos pendientes, vencidos y el historial de cobranza.</p>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <button
                                @click="showImportPaymentModal = true"
                                class="inline-flex items-center px-5 py-2.5 bg-slate-800 border border-slate-700 text-slate-300 text-xs font-bold uppercase tracking-wide rounded-xl hover:bg-slate-700 hover:text-white hover:border-slate-600 transition-all shadow-lg hover:shadow-indigo-900/10"
                            >
                                <svg class="w-4 h-4 mr-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                Importar XML
                            </button>
                            <Link
                                :href="route('cuentas-por-cobrar.create')"
                                class="inline-flex items-center px-5 py-2.5 bg-indigo-600 border border-indigo-500 text-white text-xs font-bold uppercase tracking-wide rounded-xl hover:bg-indigo-500 transition-all shadow-lg shadow-indigo-900/20"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Nueva Cuenta
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <!-- Stat Card: Cobros Vencidos -->
                    <div class="bg-slate-800/40 backdrop-blur-xl border border-red-500/20 rounded-2xl p-5 relative overflow-hidden group">
                        <div class="absolute inset-0 bg-gradient-to-br from-red-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="flex justify-between items-start mb-4 relative z-10">
                            <div class="p-2 bg-red-500/10 rounded-lg text-red-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <span class="text-xs font-black uppercase tracking-wider text-red-400 bg-red-500/10 px-2 py-1 rounded">Crítico</span>
                        </div>
                        <div class="relative z-10">
                            <dt class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Total Vencido</dt>
                            <dd class="text-2xl font-black text-white group-hover:text-red-400 transition-colors">{{ formatCurrency(stats.total_vencido) }}</dd>
                        </div>
                    </div>

                    <!-- Stat Card: Total Pendiente -->
                    <div class="bg-slate-800/40 backdrop-blur-xl border border-amber-500/20 rounded-2xl p-5 relative overflow-hidden group">
                        <div class="absolute inset-0 bg-gradient-to-br from-amber-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="flex justify-between items-start mb-4 relative z-10">
                            <div class="p-2 bg-amber-500/10 rounded-lg text-amber-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                        </div>
                        <div class="relative z-10">
                            <dt class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Total Pendiente</dt>
                            <dd class="text-2xl font-black text-white group-hover:text-amber-400 transition-colors">{{ formatCurrency(stats.total_pendiente) }}</dd>
                        </div>
                    </div>

                    <!-- Stat Card: Cuentas Pendientes -->
                    <div class="bg-slate-800/40 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-5 relative overflow-hidden group">
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="flex justify-between items-start mb-4 relative z-10">
                            <div class="p-2 bg-blue-500/10 rounded-lg text-blue-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                            </div>
                            <span class="text-xs font-bold text-slate-400">{{ stats.cuentas_pendientes }} docs</span>
                        </div>
                        <div class="relative z-10">
                            <dt class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Por Cobrar</dt>
                            <dd class="text-2xl font-black text-white group-hover:text-blue-400 transition-colors">{{ stats.cuentas_pendientes }}</dd>
                        </div>
                    </div>

                    <!-- Stat Card: Cuentas Vencidas -->
                    <div class="bg-slate-800/40 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-5 relative overflow-hidden group">
                        <div class="absolute inset-0 bg-gradient-to-br from-purple-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="flex justify-between items-start mb-4 relative z-10">
                            <div class="p-2 bg-purple-500/10 rounded-lg text-purple-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <span class="text-xs font-bold text-slate-400">{{ stats.cuentas_vencidas }} docs</span>
                        </div>
                        <div class="relative z-10">
                            <dt class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Vencidas</dt>
                            <dd class="text-2xl font-black text-white group-hover:text-purple-400 transition-colors">{{ stats.cuentas_vencidas }}</dd>
                        </div>
                    </div>
                </div>

                <!-- Filters Bar -->
                <div class="bg-slate-800/40 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-5 mb-6 shadow-xl">
                    <form @submit.prevent="applyFilters" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                        <div class="md:col-span-5">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Búsqueda General</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-slate-500 group-focus-within:text-indigo-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input 
                                    type="text" 
                                    v-model="filters.search" 
                                    placeholder="Cliente, Folio, RFC..." 
                                    class="block w-full pl-10 pr-3 py-2.5 bg-slate-900 border border-slate-700/50 rounded-xl text-slate-200 placeholder-slate-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all text-sm font-medium" 
                                />
                            </div>
                        </div>

                        <div class="md:col-span-2">
                             <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Estado</label>
                            <select v-model="filters.estado" class="block w-full py-2.5 bg-slate-900 border border-slate-700/50 rounded-xl text-slate-200 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all">
                                <option value="">Todos</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="parcial">Parcial</option>
                                <option value="pagado">Pagado</option>
                                <option value="vencido">Vencido</option>
                            </select>
                        </div>

                        <div class="md:col-span-3">
                             <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Cliente (Opcional)</label>
                             <!-- Nota: Si la lista de clientes está vacía, mostrar un input o mensaje -->
                            <select v-model="filters.cliente_id" class="block w-full py-2.5 bg-slate-900 border border-slate-700/50 rounded-xl text-slate-200 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all">
                                <option value="">-- Todos los Clientes --</option>
                                <option v-for="cliente in clientes" :key="cliente.id" :value="cliente.id">
                                    {{ cliente.nombre_razon_social }}
                                </option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl transition-all shadow-lg shadow-indigo-900/20 text-sm uppercase tracking-wide">
                                Filtrar
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Results Table -->
                <div class="bg-slate-800/40 backdrop-blur-xl border border-slate-700/50 rounded-3xl overflow-hidden shadow-2xl">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-700/50">
                            <thead class="bg-slate-900/50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Origen</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Cliente</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Total</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Pendiente</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Vencimiento</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Estado</th>
                                    <th class="px-6 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700/50 text-slate-300">
                                <tr v-for="cuenta in cuentas.data" :key="cuenta.id" class="hover:bg-slate-700/30 transition-colors group">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-white mb-0.5">
                                                <template v-if="cuenta.cobrable_type && cuenta.cobrable_type.includes('Venta')">
                                                    Venta <span class="text-indigo-400">#{{ cuenta.cobrable?.numero_venta || cuenta.cobrable_data?.numero_venta || '---' }}</span>
                                                </template>
                                                <template v-else-if="cuenta.cobrable_type && (cuenta.cobrable_type.includes('Renta') || cuenta.cobrable_type === 'renta')">
                                                    Renta <span class="text-purple-400">#{{ cuenta.cobrable?.numero_contrato || cuenta.cobrable_data?.numero_contrato || '---' }}</span>
                                                </template>
                                                <template v-else-if="cuenta.cobrable_type && (cuenta.cobrable_type.includes('Poliza') || cuenta.cobrable_type === 'poliza_servicio')">
                                                    Póliza <span class="text-emerald-400">#{{ cuenta.cobrable?.folio || cuenta.cobrable_data?.folio || '---' }}</span>
                                                </template>
                                                <template v-else>
                                                    <span class="text-slate-400">Ref: {{ cuenta.cobrable?.folio || cuenta.cobrable?.numero_venta || 'N/A' }}</span>
                                                </template>
                                            </span>
                                            <div v-if="cuenta.notas" class="text-xs text-slate-500 italic max-w-[200px] truncate" :title="cuenta.notas">
                                                {{ cuenta.notas }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-slate-200">
                                            {{ cuenta.cobrable?.cliente?.nombre_razon_social || cuenta.cobrable_data?.cliente?.nombre_razon_social || cuenta.cliente?.nombre_razon_social || 'Sin identificar' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-mono text-slate-300">
                                            {{ formatCurrency(cuenta.monto_total) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-mono font-bold" :class="cuenta.monto_pendiente > 0 ? 'text-amber-400' : 'text-slate-500'">
                                            {{ formatCurrency(cuenta.monto_pendiente) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-xs font-mono" :class="cuenta.estado === 'vencido' ? 'text-red-400 font-bold' : 'text-slate-400'">
                                            {{ cuenta.fecha_vencimiento ? new Date(cuenta.fecha_vencimiento).toLocaleDateString() : 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="{
                                            'bg-red-500/10 text-red-500 border-red-500/20': cuenta.estado === 'vencido',
                                            'bg-amber-500/10 text-amber-500 border-amber-500/20': cuenta.estado === 'parcial',
                                            'bg-emerald-500/10 text-emerald-500 border-emerald-500/20': cuenta.estado === 'pagado',
                                            'bg-slate-700 text-slate-400 border-slate-600': cuenta.estado === 'pendiente'
                                        }" class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-black uppercase tracking-wider border">
                                            {{ cuenta.estado }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-2 opacity-80 group-hover:opacity-100 transition-opacity">
                                            <!-- Ver detalles -->
                                            <button @click="abrirModalDetalles(cuenta.id)"
                                                  class="p-2 rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500 hover:text-white transition-all border border-blue-500/20"
                                                  title="Ver detalles">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>

                                            <!-- Pagar -->
                                            <button v-if="cuenta.estado !== 'pagado'"
                                                    @click="abrirModalPago(cuenta.id)"
                                                    class="p-2 rounded-lg bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500 hover:text-white transition-all border border-emerald-500/20"
                                                    title="Registrar Pago">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </button>

                                            <!-- Recordatorio -->
                                            <button v-if="['pendiente', 'parcial'].includes(cuenta.estado) && cuenta.cobrable?.cliente?.email && (!cuenta.cobrable_type || cuenta.cobrable_type.includes('Venta'))"
                                                    @click="enviarRecordatorio(cuenta)"
                                                    class="p-2 rounded-lg bg-indigo-500/10 text-indigo-400 hover:bg-indigo-500 hover:text-white transition-all border border-indigo-500/20"
                                                    title="Enviar Recordatorio de Pago">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12l-1.5-1.5m0 0L9 12m1.5-1.5V9" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="cuentas.links && cuentas.links.length > 3" class="px-6 py-4 border-t border-slate-700/50 bg-slate-900/50">
                        <div class="flex justify-between items-center">
                            <div class="text-xs text-slate-500">
                                Mostrando <span class="font-bold text-slate-300">{{ cuentas.from }}</span> a <span class="font-bold text-slate-300">{{ cuentas.to }}</span> de <span class="font-bold text-slate-300">{{ cuentas.total }}</span> resultados
                            </div>
                            <div class="flex space-x-1">
                                <Link
                                    v-for="link in cuentas.links"
                                    :key="link.label"
                                    :href="link.url || '#'"
                                    v-html="link.label"
                                    :class="[
                                        'px-3 py-1.5 text-xs font-medium rounded-lg transition-all',
                                        link.active 
                                            ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/30' 
                                            : (link.url 
                                                ? 'bg-slate-800 text-slate-400 hover:bg-slate-700 hover:text-white border border-slate-700' 
                                                : 'bg-slate-900 text-slate-600 border border-slate-800 cursor-not-allowed')
                                    ]"
                                    :preserve-scroll="link.url ? true : false"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- NOTE: Modals container (kept functional logic, just updated structure) -->
        <Modal
            :show="showModal"
            :mode="modalMode"
            tipo="ventas"
            :selected="fila || {}"
            @close="cerrarModal"
            @confirm-email="confirmarEnvioEmail"
        />

        <ImportPaymentXmlModal
            :show="showImportPaymentModal"
            :cuentas-bancarias="cuentasBancarias"
            @close="showImportPaymentModal = false"
            @imported="handlePaymentImported"
        />

        <ShowModal
            :show="showDetailsModal"
            :cuenta="selectedCuenta"
            :cuentas-bancarias="cuentasBancarias"
            @close="cerrarModalDetalles"
        />

        <PaymentModal
            :show="showPaymentModal"
            :cuenta="selectedCuenta"
            :cuentas-bancarias="cuentasBancarias"
            @close="cerrarModalPago"
        />
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/IndexComponents/Modales.vue';
import ShowModal from '@/Pages/CuentasPorCobrar/Partials/ShowModal.vue';
import ImportPaymentXmlModal from '@/Components/CuentasPorCobrar/ImportPaymentXmlModal.vue';
import PaymentModal from '@/Pages/CuentasPorCobrar/Partials/PaymentModal.vue';

// ... (resto del código)

// Configuración de notificaciones
const notyf = new Notyf({
    duration: 4000,
    position: { x: 'right', y: 'top' },
    types: [
        { type: 'success', background: '#10b981', icon: false },
        { type: 'error', background: '#ef4444', icon: false },
        { type: 'warning', background: '#f59e0b', icon: false }
    ]
});

const props = defineProps({
    cuentas: Object,
    stats: Object,
    filters: Object,
});

const clientes = ref([]);

// Estado del modal de email
const showModal = ref(false);
const fila = ref(null);
const modalMode = ref('details');

// Estado del modal de detalles
const showDetailsModal = ref(false);
const selectedCuenta = ref(null);
const showImportPaymentModal = ref(false);
const showPaymentModal = ref(false); // Para el nuevo modal de pago directo
const cuentasBancarias = ref([]);

// Función para abrir modal de pago directo
const abrirModalPago = async (id) => {
    try {
        // Reutilizamos la ruta show para obtener info fresca y cuentas bancarias
        const response = await axios.get(route('cuentas-por-cobrar.show', id));
        if (response.data) {
            selectedCuenta.value = response.data.cuenta;
            cuentasBancarias.value = response.data.cuentasBancarias;
            showPaymentModal.value = true;
        }
    } catch (error) {
        console.error('Error al cargar datos de cuenta:', error);
        notyf.error('Error al cargar la información de la cuenta');
    }
};

const cerrarModalPago = () => {
    showPaymentModal.value = false;
    selectedCuenta.value = null;
    // Opcional: Recargar la página si se realizó un pago (Inertia reload)
    // Pero PaymentModal usa router.post que ya hace reload
};

// Función para abrir modal de confirmación de email
const abrirModalEmail = (cuenta) => {
    fila.value = {
        ...cuenta,
        numero_venta: cuenta.venta?.numero_venta || `V${String(cuenta.venta_id).padStart(3, '0')}`,
        email_destino: cuenta.venta?.cliente?.email,
        // Indicar que es un recordatorio de pago
        tipo_envio: 'recordatorio_pago'
    };
    modalMode.value = 'confirm-email';
    showModal.value = true;
};

// Función para cerrar modal email
const cerrarModal = () => {
    showModal.value = false;
    fila.value = null;
};

// Función para abrir modal de detalles
const abrirModalDetalles = async (id) => {
    try {
        const response = await axios.get(route('cuentas-por-cobrar.show', id));
        if (response.data) {
            selectedCuenta.value = response.data.cuenta;
            cuentasBancarias.value = response.data.cuentasBancarias;
            showDetailsModal.value = true;
        }
    } catch (error) {
        console.error('Error al cargar detalles:', error);
        notyf.error('Error al cargar los detalles de la cuenta');
    }
};

const cerrarModalDetalles = () => {
    showDetailsModal.value = false;
    selectedCuenta.value = null;
};

const handlePaymentImported = () => {
    // La página se recargará desde el modal
    console.log('Pagos importados exitosamente');
};

// Función para confirmar envío de email
const confirmarEnvioEmail = async () => {
    try {
        const cuenta = fila.value;
        if (!cuenta?.email_destino) {
            notyf.error('Email de destino no válido');
            return;
        }

        cerrarModal();

        // Usar axios para tener control total sobre la respuesta
        const { data } = await axios.post(`/ventas/${cuenta.venta.id}/enviar-email`, {
            email_destino: cuenta.email_destino,
        });

        if (data?.success) {
            notyf.success(data.message || 'Recordatorio de pago enviado correctamente');
        } else {
            throw new Error(data?.error || 'Error desconocido al enviar recordatorio');
        }

    } catch (error) {
        console.error('Error al enviar recordatorio:', error);
        const errorMessage = error.response?.data?.error || error.response?.data?.message || error.message;
        notyf.error('Error al enviar recordatorio: ' + errorMessage);
    }
};

const filters = ref({
    estado: props.filters.estado || '',
    cliente_id: props.filters.cliente_id || '',
    type: props.filters.type || '',
    search: props.filters.search || '',
});

const currencyFormatter = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

const toNumber = (value) => {
    if (value === null || value === undefined) {
        return 0;
    }

    const number = Number(value);
    return Number.isFinite(number) ? number : 0;
};

const formatCurrency = (value) => currencyFormatter.format(toNumber(value));

const applyFilters = () => {
    router.get(route('cuentas-por-cobrar.index'), filters.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const enviarRecordatorio = (cuenta) => {
    // Verificar que el cliente tenga email
    if (!cuenta.venta?.cliente?.email) {
        notyf.error('El cliente no tiene email configurado');
        return;
    }

    // Abrir modal de confirmación
    abrirModalEmail(cuenta);
};

// onMounted(() => {
//     // Cargar clientes para el filtro - temporalmente deshabilitado
//     // fetch(route('clientes.index', { per_page: 100 }))
//     //     .then(response => response.json())
//     //     .then(data => {
//     //         clientes.value = data.data || [];
//     //     })
//     //     .catch(error => {
//     //         console.error('Error loading clientes:', error);
//     //     });
// });
</script>





