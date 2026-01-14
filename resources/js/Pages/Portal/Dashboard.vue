<script setup>
import { Link, Head } from '@inertiajs/vue3';
import ClientLayout from './Layout/ClientLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { ref } from 'vue';

const props = defineProps({
    tickets: Object,
    cliente: Object,
    polizas: Array,
    pagosPendientes: Array,
    ventas: Array, // Historial de ventas
    empresa: Object, // Pasado desde el controlador
});

const activeTab = ref('tickets');

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: 'numeric' });
};

// Mapeo de estados a clases estéticas
const getStatusClasses = (estado) => {
    const maps = {
        'abierto': 'bg-blue-50 text-blue-600 border-blue-100',
        'resuelto': 'bg-emerald-50 text-emerald-600 border-emerald-100',
        'en_progreso': 'bg-amber-50 text-amber-600 border-amber-100',
        'cerrado': 'bg-gray-50 text-gray-500 border-gray-100',
    };
    return maps[estado] || 'bg-gray-50 text-gray-500 border-gray-100';
};
</script>

<template>
    <Head title="Mi Panel de Soporte" />
    
    <ClientLayout :empresa="empresa">
        <div class="px-2 sm:px-0">
            
            <!-- Header de Bienvenida -->
            <div class="mb-10">
                <h1 class="text-3xl font-black text-gray-900 tracking-tight mb-2">
                    Hola, <span class="text-[var(--color-primary)]">{{ cliente.nombre_razon_social?.split(' ')[0] }}</span>
                </h1>
                <p class="text-gray-500 font-medium">Gestione sus servicios y soporte técnico desde un solo lugar.</p>
            </div>

            <!-- Alerta Pagos Pendientes Premium -->
            <div v-if="pagosPendientes && pagosPendientes.length > 0" 
                 class="mb-10 bg-white border border-red-100 rounded-[2rem] p-8 flex flex-col sm:flex-row items-center justify-between gap-6 shadow-xl shadow-red-500/5 overflow-hidden relative group">
                <div class="absolute top-0 right-0 w-32 h-32 bg-red-50 rounded-full -mr-16 -mt-16 group-hover:scale-110 transition-transform"></div>
                
                <div class="flex items-center gap-6 relative z-10">
                    <div class="w-16 h-16 bg-red-50 rounded-2xl flex items-center justify-center text-red-500 text-2xl">
                        ⚠️
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-gray-900">Pagos Pendientes</h3>
                        <p class="text-gray-500 font-medium">Tiene {{ pagosPendientes.length }} factura(s) que requieren su atención.</p>
                    </div>
                </div>
                <button class="relative z-10 w-full sm:w-auto px-8 py-4 bg-red-500 text-white font-black text-sm uppercase tracking-widest rounded-2xl hover:bg-red-600 hover:shadow-lg hover:shadow-red-500/30 transition-all">
                    Pagar Ahora
                </button>
            </div>

            <!-- Main Content Tabs -->
            <div class="grid lg:grid-cols-4 gap-10">
                
                <!-- Sidebar Navigation -->
                <aside class="lg:col-span-1 space-y-2">
                    <button 
                        @click="activeTab = 'tickets'" 
                        :class="[
                            'w-full flex items-center gap-4 px-6 py-4 rounded-2xl font-bold transition-all text-left',
                            activeTab === 'tickets' 
                                ? 'bg-[var(--color-primary)] text-white shadow-xl shadow-[var(--color-primary)]/20 shadow-sm' 
                                : 'bg-white text-gray-500 hover:bg-gray-100 hover:text-gray-900 border border-gray-100'
                        ]"
                    >
                        <font-awesome-icon icon="ticket-alt" /> 
                        <span class="text-sm uppercase tracking-widest">Mis Tickets</span>
                    </button>
                    
                    <button 
                        @click="activeTab = 'polizas'" 
                        :class="[
                            'w-full flex items-center gap-4 px-6 py-4 rounded-2xl font-bold transition-all text-left',
                            activeTab === 'polizas' 
                                ? 'bg-[var(--color-primary)] text-white shadow-xl shadow-[var(--color-primary)]/20 shadow-sm' 
                                : 'bg-white text-gray-500 hover:bg-gray-100 hover:text-gray-900 border border-gray-100'
                        ]"
                    >
                        <font-awesome-icon icon="file-contract" /> 
                        <span class="text-sm uppercase tracking-widest">Mis Pólizas</span>
                    </button>

                    <button 
                        @click="activeTab = 'equipos'" 
                        :class="[
                            'w-full flex items-center gap-4 px-6 py-4 rounded-2xl font-bold transition-all text-left',
                            activeTab === 'equipos' 
                                ? 'bg-[var(--color-primary)] text-white shadow-xl shadow-[var(--color-primary)]/20 shadow-sm' 
                                : 'bg-white text-gray-500 hover:bg-gray-100 hover:text-gray-900 border border-gray-100'
                        ]"
                    >
                        <font-awesome-icon icon="desktop" /> 
                        <span class="text-sm uppercase tracking-widest">Mis Equipos</span>
                    </button>

                     <button 
                        @click="activeTab = 'pagos'" 
                        :class="[
                            'w-full flex items-center gap-4 px-6 py-4 rounded-2xl font-bold transition-all text-left',
                            activeTab === 'pagos' 
                                ? 'bg-[var(--color-primary)] text-white shadow-xl shadow-[var(--color-primary)]/20 shadow-sm' 
                                : 'bg-white text-gray-500 hover:bg-gray-100 hover:text-gray-900 border border-gray-100'
                        ]"
                    >
                        <font-awesome-icon icon="receipt" /> 
                        <span class="text-sm uppercase tracking-widest">Historial Pagos</span>
                    </button>
                    <a 
                        :href="route('catalogo.index')"
                        class="w-full flex items-center gap-4 px-6 py-4 rounded-2xl font-bold transition-all text-left bg-gradient-to-r from-emerald-500 to-emerald-600 text-white shadow-lg shadow-emerald-500/20 hover:shadow-xl hover:-translate-y-0.5"
                    >
                        <font-awesome-icon icon="shopping-bag" /> 
                        <span class="text-sm uppercase tracking-widest">Ir a la Tienda</span>
                    </a>
                </aside>

                <!-- Tab Panels Area -->
                <div class="lg:col-span-3">
                    
                    <!-- Tab: Tickets -->
                    <div v-show="activeTab === 'tickets'" class="animate-fade-in space-y-6">
                        <div class="flex justify-between items-center px-2">
                            <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Historial de Soporte</h2>
                            <Link
                                :href="route('portal.tickets.create')"
                                class="px-6 py-3 bg-[var(--color-terciary)] text-white rounded-xl font-black text-xs uppercase tracking-widest hover:shadow-lg transition-all"
                            >
                                + Nuevo Ticket
                            </Link>
                        </div>

                        <div class="bg-white rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden">
                            <div class="divide-y divide-gray-50">
                                <div v-for="ticket in tickets.data" :key="ticket.id" class="group">
                                    <Link :href="route('portal.tickets.show', ticket)" class="block p-6 hover:bg-gray-50/50 transition-all">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                            <div class="flex gap-4 items-start">
                                                <div class="mt-1 w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-gray-400 group-hover:bg-[var(--color-primary-soft)] group-hover:text-[var(--color-primary)] transition-all">
                                                    <font-awesome-icon icon="ticket-alt" />
                                                </div>
                                                <div>
                                                    <h3 class="font-bold text-gray-900 group-hover:text-[var(--color-primary)] transition-colors">{{ ticket.titulo }}</h3>
                                                    <div class="flex items-center gap-3 mt-1">
                                                        <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 font-mono">{{ ticket.numero }}</span>
                                                        <span class="h-1 w-1 rounded-full bg-gray-300"></span>
                                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ ticket.categoria?.nombre || 'General' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-between sm:justify-end gap-6">
                                                <div class="text-right hidden sm:block">
                                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Fecha</p>
                                                    <p class="text-xs font-bold text-gray-600">{{ formatDate(ticket.created_at) }}</p>
                                                </div>
                                                <span 
                                                    class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border"
                                                    :class="getStatusClasses(ticket.estado)"
                                                >
                                                    {{ ticket.estado }}
                                                </span>
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                                <div v-if="tickets.data.length === 0" class="p-20 text-center">
                                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6 text-2xl">
                                        ✨
                                    </div>
                                    <h3 class="text-lg font-black text-gray-900 mb-2">Todo en Orden</h3>
                                    <p class="text-gray-500 font-medium">No tiene solicitudes de soporte activas en este momento.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination Premium -->
                        <div v-if="tickets.next_page_url || tickets.prev_page_url" class="flex justify-center gap-4 pt-4">
                             <Link v-if="tickets.prev_page_url" :href="tickets.prev_page_url" class="px-6 py-3 bg-white border border-gray-100 rounded-xl font-black text-[10px] uppercase tracking-widest text-gray-500 hover:bg-gray-50 transition-all">Anterior</Link>
                             <Link v-if="tickets.next_page_url" :href="tickets.next_page_url" class="px-6 py-3 bg-white border border-gray-100 rounded-xl font-black text-[10px] uppercase tracking-widest text-gray-500 hover:bg-gray-50 transition-all">Siguiente</Link>
                        </div>
                    </div>

                    <!-- Tab: Pólizas -->
                    <div v-show="activeTab === 'polizas'" class="animate-fade-in space-y-6">
                        <div class="px-2">
                             <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Mis Servicios Activos</h2>
                        </div>
                        
                        <div class="grid gap-6 md:grid-cols-2">
                            <div v-for="poliza in polizas" :key="poliza.id" class="bg-white rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden group hover:-translate-y-1 transition-all duration-300">
                                <div class="p-8">
                                    <div class="flex justify-between items-start mb-6">
                                        <div class="w-14 h-14 bg-[var(--color-primary-soft)] rounded-2xl flex items-center justify-center text-[var(--color-primary)] text-xl group-hover:scale-110 transition-transform">
                                            <font-awesome-icon icon="shield-alt" />
                                        </div>
                                        <span class="px-3 py-1 bg-emerald-50 text-emerald-600 text-[10px] font-black rounded-full uppercase tracking-widest border border-emerald-100">
                                            {{ poliza.estado }}
                                        </span>
                                    </div>
                                    
                                    <h3 class="text-xl font-black text-gray-900 mb-1 group-hover:text-[var(--color-primary)] transition-colors">{{ poliza.nombre }}</h3>
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-6 font-mono">{{ poliza.folio }}</p>

                                    <div class="space-y-4 pt-6 border-t border-gray-50">
                                        <div class="flex justify-between items-end">
                                            <div>
                                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Consumo del Mes</p>
                                                <p class="text-sm font-bold text-gray-900">{{ poliza.horas_consumidas_mes || 0 }} hrs / {{ poliza.horas_incluidas_mensual }} hrs</p>
                                            </div>
                                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Expira: <span class="text-gray-900 ml-1">{{ formatDate(poliza.fecha_fin) }}</span></p>
                                        </div>
                                        
                                        <!-- Barra de Progreso Premium -->
                                        <div class="w-full bg-gray-50 rounded-full h-2.5 overflow-hidden">
                                             <div 
                                                class="bg-gradient-to-r from-[var(--color-primary)] to-[var(--color-primary-dark)] h-full rounded-full transition-all duration-1000 ease-out" 
                                                :style="{ width: Math.min(((poliza.horas_consumidas_mes || 0) / (poliza.horas_incluidas_mensual || 1)) * 100, 100) + '%' }"
                                             ></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-8 py-4 flex justify-between items-center group-hover:bg-[var(--color-primary-soft)] transition-colors">
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest group-hover:text-[var(--color-primary)]">
                                        {{ poliza.renovacion_automatica ? 'Renovación Automática' : 'Renovación Manual' }}
                                    </span>
                                    <div class="flex gap-2">
                                        <a :href="route('portal.polizas.imprimir', poliza.id)" target="_blank" class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 hover:text-[var(--color-primary)] hover:underline flex items-center gap-1">
                                            <font-awesome-icon icon="file-pdf" /> Contrato
                                        </a>
                                        <!-- <button class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--color-primary)] hover:underline">Detalles</button> -->
                                    </div>
                                </div>
                            </div>

                            <div v-if="polizas.length === 0" class="col-span-full py-20 text-center bg-white rounded-[2rem] border-2 border-dashed border-gray-100">
                                <p class="text-gray-400 font-bold mb-6">No tiene pólizas vigentes en su cuenta.</p>
                                <Link :href="route('catalogo.polizas')" class="px-8 py-4 bg-[var(--color-primary)] text-white rounded-2xl font-black text-sm uppercase tracking-widest hover:shadow-xl transition-all inline-block">Ver Catálogo de Pólizas</Link>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Equipos -->
                    <div v-show="activeTab === 'equipos'" class="animate-fade-in">
                        <div class="bg-white rounded-[3rem] shadow-xl p-16 text-center border border-gray-50">
                             <div class="w-24 h-24 bg-gray-50 rounded-3xl flex items-center justify-center mx-auto mb-8 text-3xl">
                                💻
                            </div>
                            <h3 class="text-2xl font-black text-gray-900 mb-4">Inventario de Infraestructura</h3>
                            <p class="text-gray-500 font-medium max-w-md mx-auto leading-relaxed">
                                Estamos vinculando sus activos y equipos registrados a su panel. Pronto podrá ver el estado, historial y cubrimiento de cada dispositivo.
                            </p>
                        </div>
                    </div>

                    <!-- Tab: Historial Pagos -->
                    <div v-show="activeTab === 'pagos'" class="animate-fade-in space-y-6">
                        <div class="px-2">
                             <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Historial de Transacciones</h2>
                        </div>

                        <div class="bg-white rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead class="bg-gray-50 border-b border-gray-100">
                                        <tr>
                                            <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Folio</th>
                                            <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Fecha</th>
                                            <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Concepto</th>
                                            <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Monto</th>
                                            <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Estado</th>
                                            <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <tr v-for="venta in ventas" :key="venta.id" class="hover:bg-gray-50/50 transition-colors">
                                            <td class="px-8 py-4 font-mono text-xs font-bold text-gray-500">#{{ venta.folio || venta.id }}</td>
                                            <td class="px-8 py-4 text-xs font-bold text-gray-900">{{ formatDate(venta.fecha) }}</td>
                                            <td class="px-8 py-4 text-xs font-medium text-gray-600 max-w-xs truncate">{{ venta.notas || 'Venta General' }}</td>
                                            <td class="px-8 py-4 text-xs font-black text-gray-900 text-right">${{ Number(venta.total).toLocaleString('es-MX', {minimumFractionDigits: 2}) }}</td>
                                            <td class="px-8 py-4 text-center">
                                                <span 
                                                    class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest border"
                                                    :class="{
                                                        'bg-green-50 text-green-600 border-green-100': venta.estado === 'pagado',
                                                        'bg-yellow-50 text-yellow-600 border-yellow-100': venta.estado === 'pendiente',
                                                        'bg-red-50 text-red-600 border-red-100': venta.estado === 'cancelado'
                                                    }"
                                                >
                                                    {{ venta.estado }}
                                                </span>
                                            </td>
                                            <td class="px-8 py-4 text-right">
                                                <button class="text-gray-300 cursor-not-allowed" title="Próximamente">
                                                    <font-awesome-icon icon="file-invoice-dollar" />
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="!ventas || ventas.length === 0">
                                            <td colspan="6" class="px-8 py-12 text-center text-gray-400 text-sm font-medium">No se encontraron registros de ventas.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ClientLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
