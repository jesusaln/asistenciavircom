<script setup>
import { Link, Head } from '@inertiajs/vue3';
import ClientLayout from './Layout/ClientLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    tickets: Object,
    cliente: Object,
    polizas: Array,
    pagosPendientes: Array,
    pedidos: Array,
    ventas: Array, // Historial de ventas
    credenciales: Array,
    empresa: Object, // Pasado desde el controlador
    rentas: Array,
});

const activeTab = ref('resumen');
const revealedPasswords = ref({});
const isLoadingPassword = ref({});

const revealPassword = async (id) => {
    if (revealedPasswords.value[id]) {
        delete revealedPasswords.value[id];
        return;
    }

    try {
        isLoadingPassword.value[id] = true;
        const response = await axios.post(route('portal.credenciales.revelar', id));
        revealedPasswords.value[id] = response.data.password;
    } catch (error) {
        console.error('Error al revelar credencial:', error);
        alert('No se pudo revelar la contraseña. Intente de nuevo.');
    } finally {
        isLoadingPassword.value[id] = false;
    }
};

const payingWithCredit = ref({});

const payWithCredit = async (ventaId) => {
    if (!confirm('¿Está seguro de querer pagar esta factura usando su crédito comercial?')) return;

    try {
        payingWithCredit.value[ventaId] = true;
        const response = await axios.post(route('portal.ventas.pagar-credito'), { venta_id: ventaId });
        
        if (response.data.success) {
            alert(response.data.message);
            window.location.reload(); // Recargar para actualizar saldos y estados
        }
    } catch (error) {
        console.error('Error al pagar con crédito:', error);
        alert(error.response?.data?.message || 'Error al procesar el pago.');
    } finally {
        payingWithCredit.value[ventaId] = false;
    }
};

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
                <button @click="activeTab = 'pagos'" class="relative z-10 w-full sm:w-auto px-8 py-4 bg-red-500 text-white font-black text-sm uppercase tracking-widest rounded-2xl hover:bg-red-600 hover:shadow-lg hover:shadow-red-500/30 transition-all">
                    Gestionar Pagos
                </button>
            </div>

            <!-- Resumen de Crédito (Vista Rápida) -->
            <div v-if="cliente.credito_activo || cliente.estado_credito !== 'sin_credito'" 
                 class="mb-10 grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <div class="bg-white p-8 rounded-[2rem] border border-gray-100 shadow-xl shadow-gray-200/50 flex items-center justify-between group overflow-hidden relative">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-[var(--color-primary-soft)] rounded-full -mr-12 -mt-12 group-hover:scale-125 transition-transform duration-500"></div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Estado de Crédito</p>
                        <h3 class="text-xl font-black text-gray-900 flex items-center gap-2">
                             <font-awesome-icon 
                                :icon="cliente.estado_credito === 'autorizado' ? 'check-circle' : 'info-circle'" 
                                :class="cliente.estado_credito === 'autorizado' ? 'text-emerald-500' : 'text-amber-500'" 
                             />
                             {{ cliente.estado_credito === 'autorizado' ? 'Autorizado' : (cliente.estado_credito === 'en_revision' ? 'En Revisión' : cliente.estado_credito) }}
                        </h3>
                    </div>
                </div>

                <div v-if="cliente.credito_activo" class="bg-white p-8 rounded-[2rem] border border-gray-100 shadow-xl shadow-gray-200/50 group overflow-hidden relative">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-50 rounded-full -mr-12 -mt-12 group-hover:scale-125 transition-transform duration-500"></div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Crédito Disponible</p>
                        <h3 class="text-3xl font-black text-emerald-600">
                             ${{ Number(cliente.credito_disponible).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                        </h3>
                    </div>
                </div>

                <div v-if="cliente.credito_activo" class="bg-white p-8 rounded-[2rem] border border-gray-100 shadow-xl shadow-gray-200/50 group overflow-hidden relative">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-blue-50 rounded-full -mr-12 -mt-12 group-hover:scale-125 transition-transform duration-500"></div>
                    <div class="relative z-10">
                         <Link :href="route('portal.credito.index')" class="flex items-center justify-between w-full group/link">
                            <div>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Línea Total</p>
                                <h3 class="text-xl font-black text-gray-900">
                                    ${{ Number(cliente.limite_credito).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                                </h3>
                            </div>
                            <font-awesome-icon icon="chevron-right" class="text-gray-300 group-hover/link:text-[var(--color-primary)] transition-colors" />
                         </Link>
                    </div>
                </div>
            </div>

            <!-- Main Content Tabs -->
            <div class="grid lg:grid-cols-4 gap-10">
                
                <!-- Sidebar Navigation -->
                <aside class="lg:col-span-1 space-y-2">
                    <button 
                        @click="activeTab = 'resumen'" 
                        :class="[
                            'w-full flex items-center gap-4 px-6 py-4 rounded-2xl font-bold transition-all text-left',
                            activeTab === 'resumen' 
                                ? 'bg-[var(--color-primary)] text-white shadow-xl shadow-[var(--color-primary)]/20 shadow-sm' 
                                : 'bg-white text-gray-500 hover:bg-gray-100 hover:text-gray-900 border border-gray-100'
                        ]"
                    >
                        <font-awesome-icon icon="th-large" /> 
                        <span class="text-sm uppercase tracking-widest">Resumen</span>
                    </button>

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
                        @click="activeTab = 'credenciales'" 
                        :class="[
                            'w-full flex items-center gap-4 px-6 py-4 rounded-2xl font-bold transition-all text-left',
                            activeTab === 'credenciales' 
                                ? 'bg-[var(--color-primary)] text-white shadow-xl shadow-[var(--color-primary)]/20 shadow-sm' 
                                : 'bg-white text-gray-500 hover:bg-gray-100 hover:text-gray-900 border border-gray-100'
                        ]"
                    >
                        <font-awesome-icon icon="lock" /> 
                        <span class="text-sm uppercase tracking-widest">Mis Accesos</span>
                    </button>

                    <button 
                        @click="activeTab = 'pedidos'" 
                        :class="[
                            'w-full flex items-center gap-4 px-6 py-4 rounded-2xl font-bold transition-all text-left',
                            activeTab === 'pedidos' 
                                ? 'bg-[var(--color-primary)] text-white shadow-xl shadow-[var(--color-primary)]/20 shadow-sm' 
                                : 'bg-white text-gray-500 hover:bg-gray-100 hover:text-gray-900 border border-gray-100'
                        ]"
                    >
                        <font-awesome-icon icon="shopping-cart" /> 
                        <span class="text-sm uppercase tracking-widest">Mis Pedidos</span>
                    </button>

                    <Link 
                        :href="route('portal.credito.index')" 
                        class="w-full flex items-center gap-4 px-6 py-4 rounded-2xl font-bold transition-all text-left bg-white text-gray-500 hover:bg-gray-100 hover:text-gray-900 border border-gray-100"
                    >
                        <font-awesome-icon icon="credit-card" /> 
                        <span class="text-sm uppercase tracking-widest">Mi Crédito</span>
                    </Link>

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
                    
                    <!-- Tab: Resumen (NUEVO) -->
                    <div v-show="activeTab === 'resumen'" class="animate-fade-in space-y-8">
                        <div class="grid sm:grid-cols-2 gap-6">
                            <!-- Widget: Soporte -->
                            <div class="bg-white rounded-[2rem] p-8 shadow-xl shadow-gray-200/50 border border-gray-100 relative overflow-hidden group">
                                <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-50 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
                                <div class="relative z-10">
                                    <div class="flex items-center gap-4 mb-6">
                                        <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center text-xl">
                                            <font-awesome-icon icon="ticket-alt" />
                                        </div>
                                        <h3 class="text-lg font-black text-gray-900 uppercase tracking-tight">Soporte Técnico</h3>
                                    </div>
                                    <div class="flex items-end justify-between">
                                        <div>
                                            <p class="text-4xl font-black text-gray-900">{{ tickets.total || 0 }}</p>
                                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Tickets Totales</p>
                                        </div>
                                        <button @click="activeTab = 'tickets'" class="text-xs font-black text-blue-600 hover:underline uppercase tracking-widest">Ver Historial</button>
                                    </div>
                                    <Link :href="route('portal.tickets.create')" class="mt-8 w-full flex items-center justify-center py-4 bg-gray-900 text-white rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-[var(--color-primary)] transition-all">
                                        + Solicitar Ayuda Ahora
                                    </Link>
                                </div>
                            </div>

                            <!-- Widget: Pagos -->
                            <div class="bg-white rounded-[2rem] p-8 shadow-xl shadow-gray-200/50 border border-gray-100 relative overflow-hidden group">
                                <div class="absolute -right-4 -top-4 w-24 h-24 bg-red-50 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
                                <div class="relative z-10">
                                    <div class="flex items-center gap-4 mb-6">
                                        <div class="w-12 h-12 bg-red-100 text-red-600 rounded-2xl flex items-center justify-center text-xl">
                                            <font-awesome-icon icon="receipt" />
                                        </div>
                                        <h3 class="text-lg font-black text-gray-900 uppercase tracking-tight">Finanzas</h3>
                                    </div>
                                    <div class="flex items-end justify-between">
                                        <div>
                                            <p class="text-4xl font-black text-red-500">${{ Number(pagosPendientes.reduce((acc, p) => acc + parseFloat(p.total), 0)).toLocaleString('es-MX', {minimumFractionDigits: 2}) }}</p>
                                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Por Pagar</p>
                                        </div>
                                        <button @click="activeTab = 'pagos'" class="text-xs font-black text-red-600 hover:underline uppercase tracking-widest">Detalles</button>
                                    </div>
                                    <button @click="activeTab = 'pagos'" class="mt-8 w-full flex items-center justify-center py-4 bg-red-500 text-white rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-red-600 transition-all">
                                        Pagar Facturas Pendientes
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Fila Inferior: Póliza Destacada & Equipos -->
                        <div class="grid lg:grid-cols-3 gap-6">
                             <!-- Póliza Widget -->
                             <div class="lg:col-span-2 bg-gradient-to-br from-gray-900 to-gray-800 rounded-[2rem] p-8 text-white shadow-2xl relative overflow-hidden group">
                                <div class="absolute right-0 bottom-0 opacity-10 group-hover:scale-110 transition-transform duration-500">
                                    <font-awesome-icon icon="shield-alt" class="text-[12rem]" />
                                </div>
                                <div class="relative z-10">
                                    <div class="flex justify-between items-start mb-10">
                                        <div>
                                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em] mb-2 font-mono">Contrato Vigente</p>
                                            <h3 class="text-2xl font-black">{{ polizas[0]?.nombre || 'Sin Póliza Activa' }}</h3>
                                        </div>
                                        <div class="px-4 py-2 bg-white/10 backdrop-blur-md rounded-xl text-[10px] font-bold uppercase tracking-widest">
                                            {{ polizas[0]?.estado || 'N/A' }}
                                        </div>
                                    </div>

                                    <div v-if="polizas[0]" class="grid sm:grid-cols-2 gap-8 mb-8 text-center sm:text-left">
                                        <div class="p-6 bg-white/5 rounded-3xl border border-white/5">
                                            <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Días Restantes</p>
                                            <p class="text-3xl font-black">{{ polizas[0]?.dias_para_vencer }} <span class="text-xs text-gray-500">Días</span></p>
                                        </div>
                                        <div class="p-6 bg-white/5 rounded-3xl border border-white/5">
                                            <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Tickets del Mes</p>
                                            <p class="text-3xl font-black">{{ polizas[0]?.tickets_mes_actual_count }} <span class="text-xs text-gray-500">Consumidos</span></p>
                                        </div>
                                    </div>

                                    <button @click="activeTab = 'polizas'" class="flex items-center gap-2 group/btn font-black text-[10px] uppercase tracking-widest text-[var(--color-primary)]">
                                        Ver todas mis pólizas
                                        <font-awesome-icon icon="arrow-right" class="group-hover/btn:translate-x-1 transition-transform" />
                                    </button>
                                </div>
                             </div>

                             <!-- Acceso Rápido Tienda -->
                             <div class="bg-[var(--color-primary)] rounded-[2rem] p-8 text-white flex flex-col justify-between shadow-xl shadow-[var(--color-primary)]/30">
                                 <div>
                                     <h3 class="text-xl font-black uppercase tracking-tight mb-2">Comprar Insumos</h3>
                                     <p class="text-white/80 text-sm font-medium leading-relaxed">¿Necesitas refacciones o equipo nuevo? Visita nuestra tienda online.</p>
                                 </div>
                                 <a :href="route('catalogo.index')" class="w-full py-4 bg-white text-[var(--color-primary)] rounded-2xl font-black text-xs uppercase tracking-widest text-center shadow-lg hover:shadow-2xl transition-all">
                                     Ir a la Tienda 🛍️
                                 </a>
                             </div>
                        </div>
                    </div>

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

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t border-gray-50">
                                        <!-- Barra de Horas -->
                                        <div v-if="poliza.horas_incluidas_mensual > 0">
                                            <div class="flex justify-between items-end mb-2">
                                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Horas de Soporte</p>
                                                <p class="text-[10px] font-bold" :class="poliza.excede_horas ? 'text-red-500' : 'text-gray-600'">
                                                    {{ poliza.horas_consumidas_mes || 0 }} / {{ poliza.horas_incluidas_mensual }} hrs
                                                </p>
                                            </div>
                                            <div class="w-full bg-gray-50 rounded-full h-1.5 overflow-hidden">
                                                 <div 
                                                    class="h-full rounded-full transition-all duration-1000 ease-out" 
                                                    :class="poliza.excede_horas ? 'bg-red-500' : 'bg-blue-500'"
                                                    :style="{ width: Math.min(poliza.porcentaje_horas || 0, 100) + '%' }"
                                                 ></div>
                                            </div>
                                        </div>

                                        <!-- Barra de Tickets -->
                                        <div v-if="poliza.limite_mensual_tickets > 0">
                                            <div class="flex justify-between items-end mb-2">
                                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Tickets Incluidos</p>
                                                <p class="text-[10px] font-bold text-gray-600">
                                                    {{ poliza.tickets_mes_actual_count || 0 }} / {{ poliza.limite_mensual_tickets }}
                                                </p>
                                            </div>
                                            <div class="w-full bg-gray-50 rounded-full h-1.5 overflow-hidden">
                                                 <div 
                                                    class="bg-emerald-500 h-full rounded-full transition-all duration-1000 ease-out" 
                                                    :style="{ width: Math.min(poliza.porcentaje_tickets || 0, 100) + '%' }"
                                                 ></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4 flex items-center justify-between">
                                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                            Vencimiento: <span class="text-gray-900 ml-1">{{ formatDate(poliza.fecha_fin) }}</span>
                                        </p>
                                        <p v-if="poliza.dias_para_vencer <= 30" class="text-[10px] font-black text-orange-500 uppercase">
                                            Vence en {{ poliza.dias_para_vencer }} días
                                        </p>
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
                                        <Link :href="route('portal.polizas.show', poliza.id)" class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--color-primary)] hover:underline flex items-center gap-2">
                                            <font-awesome-icon icon="eye" /> Detalles
                                        </Link>
                                    </div>
                                </div>
                            </div>

                            <div v-if="polizas.length === 0" class="col-span-full py-20 text-center bg-white rounded-[2rem] border-2 border-dashed border-gray-100">
                                <p class="text-gray-400 font-bold mb-6">No tiene pólizas vigentes en su cuenta.</p>
                                <Link :href="route('catalogo.polizas')" class="px-8 py-4 bg-[var(--color-primary)] text-white rounded-2xl font-black text-sm uppercase tracking-widest hover:shadow-xl transition-all inline-block">Ver Catálogo de Pólizas</Link>
                            </div>
                        </div>
                    </div>

                    <div v-show="activeTab === 'equipos'" class="animate-fade-in space-y-6">
                        <div class="px-2">
                             <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Inventario de Equipos Protegidos</h2>
                             <p class="text-gray-500 text-sm font-medium">Equipos registrados bajo sus contratos de soporte.</p>
                        </div>

                        <div class="grid gap-6 md:grid-cols-2">
                            <template v-for="poliza in polizas" :key="'e-'+poliza.id">
                                <div v-for="equipo in poliza.equipos" :key="equipo.id" 
                                     class="bg-white rounded-[2rem] p-6 shadow-xl shadow-gray-200/50 border border-gray-100 flex items-center gap-6 group hover:border-[var(--color-primary)] transition-all">
                                    <div class="w-14 h-14 bg-gray-50 rounded-2xl flex items-center justify-center text-gray-400 group-hover:bg-[var(--color-primary-soft)] group-hover:text-[var(--color-primary)] transition-all text-xl">
                                        <font-awesome-icon :icon="equipo.tipo === 'Laptop' || equipo.tipo === 'Servidor' ? 'server' : 'desktop'" />
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-bold text-gray-900">{{ equipo.nombre || equipo.modelo }}</h4>
                                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest font-mono">S/N: {{ equipo.numero_serie || 'N/A' }}</p>
                                        <div class="flex items-center gap-2 mt-2">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                            <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest">Cubierto por {{ poliza.folio }}</span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            
                            <!-- Equipos manuales de la póliza (condiciones especiales) -->
                            <template v-for="poliza in polizas" :key="'ce-'+poliza.id">
                                <div v-for="(equipo, idx) in poliza.condiciones_especiales?.equipos_cliente" :key="poliza.id + '-ce-' + idx"
                                     class="bg-white rounded-[2rem] p-6 shadow-xl shadow-gray-200/50 border border-gray-100 flex items-center gap-6 group hover:border-[var(--color-primary)] transition-all">
                                    <div class="w-14 h-14 bg-gray-50 rounded-2xl flex items-center justify-center text-gray-400 group-hover:bg-[var(--color-primary-soft)] group-hover:text-[var(--color-primary)] transition-all text-xl">
                                        <font-awesome-icon icon="desktop" />
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-bold text-gray-900">{{ equipo.nombre }}</h4>
                                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest font-mono">S/N: {{ equipo.serie || 'N/A' }}</p>
                                        <div class="flex items-center gap-2 mt-2">
                                            <div class="px-2 py-0.5 bg-blue-50 text-blue-600 rounded text-[9px] font-black uppercase">Contrato Activo</div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div v-if="!polizas.some(p => p.equipos?.length > 0 || p.condiciones_especiales?.equipos_cliente?.length > 0)" 
                                 class="col-span-full py-20 bg-white rounded-[2rem] border-2 border-dashed border-gray-100 text-center">
                                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300">
                                    <font-awesome-icon icon="desktop" size="lg" />
                                </div>
                                <h3 class="text-lg font-black text-gray-900 mb-1">Sin equipos vinculados</h3>
                                <p class="text-gray-500 font-medium text-sm">Comuníquese con soporte para registrar sus activos.</p>
                            </div>
                        </div>

                        <!-- Equipos de Renta (POS, etc) -->
                        <div v-if="rentas && rentas.length > 0" class="mt-12">
                            <div class="px-2 mb-6">
                                 <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Equipos en Renta (POS)</h2>
                                 <p class="text-gray-500 text-sm font-medium">Equipos activos actualmente bajo contrato de arrendamiento.</p>
                            </div>
                            
                            <div class="grid gap-6 md:grid-cols-2">
                                <template v-for="renta in rentas" :key="'r-'+renta.id">
                                    <div v-for="equipo in renta.equipos" :key="'re-'+equipo.id" 
                                         class="bg-white rounded-[2rem] p-6 shadow-xl shadow-gray-200/50 border border-gray-100 flex items-center gap-6 group hover:border-emerald-500 transition-all relative overflow-hidden">
                                        
                                        <!-- Badge Renta -->
                                        <div class="absolute top-0 right-0 px-4 py-1 bg-emerald-500 text-white text-[8px] font-black uppercase tracking-widest rounded-bl-xl shadow-sm">
                                            Renta
                                        </div>

                                        <div class="w-14 h-14 bg-gray-50 rounded-2xl flex items-center justify-center text-gray-400 group-hover:bg-emerald-50 group-hover:text-emerald-600 transition-all text-xl">
                                            <font-awesome-icon :icon="equipo.tipo === 'Laptop' || equipo.tipo === 'Servidor' ? 'server' : 'desktop'" />
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="font-bold text-gray-900">{{ equipo.nombre || equipo.modelo }}</h4>
                                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest font-mono">S/N: {{ equipo.numero_serie || 'N/A' }}</p>
                                            <div class="flex items-center gap-2 mt-2">
                                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                                <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest">Contrato {{ renta.numero_contrato }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
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
                                                <div class="flex justify-end gap-2">
                                                    <button v-if="venta.estado === 'pendiente' && cliente.credito_activo && cliente.credito_disponible >= venta.total"
                                                            @click="payWithCredit(venta.id)"
                                                            :disabled="payingWithCredit[venta.id]"
                                                            class="px-3 py-1 bg-emerald-500 text-white rounded-lg text-[10px] font-black uppercase tracking-widest hover:bg-emerald-600 transition-all disabled:opacity-50"
                                                            title="Pagar con Crédito">
                                                        {{ payingWithCredit[venta.id] ? '...' : 'Pagar con Crédito 💳' }}
                                                    </button>
                                                    <button class="text-gray-300 cursor-not-allowed" title="Próximamente">
                                                        <font-awesome-icon icon="file-invoice-dollar" />
                                                    </button>
                                                </div>
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

                    <!-- Tab: Credenciales -->
                    <div v-show="activeTab === 'credenciales'" class="animate-fade-in space-y-6">
                        <div class="px-2">
                             <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Caja Fuerte de Accesos</h2>
                             <p class="text-gray-500 text-sm font-medium">Sus claves de acceso se encuentran encriptadas y cada revelación es auditada.</p>
                        </div>

                        <div class="grid gap-6">
                            <div v-for="cred in credenciales" :key="cred.id" 
                                 class="bg-white rounded-[2rem] p-8 shadow-xl shadow-gray-200/50 border border-gray-100 border-l-4 border-l-[var(--color-primary)]">
                                <div class="flex flex-col md:flex-row justify-between gap-6">
                                    <div class="flex-1 space-y-4">
                                        <div>
                                            <h3 class="text-xl font-black text-gray-900">{{ cred.nombre }}</h3>
                                            <p v-if="cred.host" class="text-xs font-bold text-gray-400 uppercase tracking-widest">{{ cred.host }}{{ cred.puerto ? ':' + cred.puerto : '' }}</p>
                                        </div>
                                        
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div class="bg-gray-50 p-4 rounded-xl">
                                                <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Usuario</p>
                                                <p class="text-sm font-bold text-gray-800">{{ cred.usuario }}</p>
                                            </div>
                                            <div class="bg-gray-50 p-4 rounded-xl relative overflow-hidden">
                                                <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Contraseña</p>
                                                <div class="flex items-center justify-between gap-2">
                                                    <p class="text-sm font-mono font-bold text-gray-800">
                                                        {{ revealedPasswords[cred.id] || '••••••••••••' }}
                                                    </p>
                                                    <button 
                                                        @click="revealPassword(cred.id)"
                                                        class="p-1 hover:text-[var(--color-primary)] transition-colors text-gray-400"
                                                        :disabled="isLoadingPassword[cred.id]"
                                                    >
                                                        <font-awesome-icon :icon="isLoadingPassword[cred.id] ? 'spinner' : (revealedPasswords[cred.id] ? 'eye-slash' : 'eye')" :spin="isLoadingPassword[cred.id]" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div v-if="cred.notas" class="border-t border-gray-100 pt-4 mt-4">
                                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Notas Adicionales</p>
                                            <p class="text-xs text-gray-600 font-medium">{{ cred.notas }}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex md:flex-col justify-end gap-2">
                                        <div class="px-4 py-2 bg-blue-50 text-blue-600 rounded-xl text-[10px] font-black uppercase text-center flex items-center gap-2">
                                            <font-awesome-icon icon="shield-check" /> Protegido
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="credenciales.length === 0" class="py-20 bg-white rounded-[2rem] border-2 border-dashed border-gray-100 text-center">
                                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300">
                                    <font-awesome-icon icon="lock" size="lg" />
                                </div>
                                <h3 class="text-lg font-black text-gray-900 mb-1">No hay credenciales</h3>
                                <p class="text-gray-500 font-medium text-sm">Nuestro equipo aún no ha registrado claves de acceso para su cuenta.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Pedidos -->
                    <div v-show="activeTab === 'pedidos'" class="animate-fade-in space-y-6">
                        <div class="px-2">
                             <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Mis Pedidos Online</h2>
                             <p class="text-gray-500 text-sm font-medium">Siga el estado de sus compras realizadas en nuestra tienda.</p>
                        </div>

                        <div class="grid gap-6">
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
                                            class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border border-blue-100 bg-blue-50 text-blue-600"
                                        >
                                            {{ pedido.estado }}
                                        </span>
                                        <p class="text-lg font-black text-gray-900">${{ Number(pedido.total).toLocaleString('es-MX', {minimumFractionDigits: 2}) }}</p>
                                    </div>
                                    
                                    <div class="flex gap-4">
                                         <Link :href="route('portal.pedidos.show', pedido.id)" class="px-6 py-3 bg-gray-50 text-gray-700 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-gray-100 transition-all border border-gray-200">
                                            Detalles
                                        </Link>
                                    </div>
                                </div>
                                
                                <div v-if="pedido.numero_guia" class="mt-6 pt-6 border-t border-gray-50 flex items-center gap-4 text-emerald-600">
                                    <font-awesome-icon icon="truck" />
                                    <p class="text-xs font-bold uppercase tracking-widest">Guía: <span class="text-gray-900 ml-2 font-mono">{{ pedido.numero_guia }}</span> ({{ pedido.empresa_envio }})</p>
                                </div>
                            </div>

                            <div v-if="pedidos.length === 0" class="py-20 bg-white rounded-[2rem] border-2 border-dashed border-gray-100 text-center">
                                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300">
                                    <font-awesome-icon icon="shopping-cart" size="lg" />
                                </div>
                                <h3 class="text-lg font-black text-gray-900 mb-1">No hay pedidos</h3>
                                <p class="text-gray-500 font-medium text-sm">Aún no ha realizado compras en nuestra tienda en línea.</p>
                                <a :href="route('catalogo.index')" class="mt-6 inline-block px-8 py-4 bg-[var(--color-primary)] text-white rounded-2xl font-black text-sm uppercase tracking-widest hover:shadow-xl transition-all">Ir a la Tienda</a>
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
