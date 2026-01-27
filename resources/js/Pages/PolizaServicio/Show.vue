<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import MantenimientosManager from './Partials/MantenimientosManager.vue';

const props = defineProps({
    poliza: Object,
    stats: Object,
    isModal: {
        type: Boolean,
        default: false
    }
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-MX', { 
        day: '2-digit', month: 'short', year: 'numeric' 
    });
};

const getEstadoBadge = (estado) => {
    const colores = {
        activa: 'bg-green-100 text-green-800 border-green-200',
        inactiva: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        vencida: 'bg-red-100 text-red-800 border-red-200',
        cancelada: 'bg-gray-100 text-gray-800 dark:text-gray-100 border-gray-200 dark:border-slate-800',
    };
    return colores[estado] || 'bg-gray-100 text-gray-800 dark:text-gray-100';
};

const getEstadoCobroBadge = (estado) => {
    const colores = {
        pagado: 'bg-green-100 text-green-800 border-green-200',
        pendiente: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        parcial: 'bg-blue-100 text-blue-800 border-blue-200',
        vencido: 'bg-red-100 text-red-800 border-red-200',
        cancelada: 'bg-gray-100 text-gray-800 dark:text-gray-100 border-gray-200 dark:border-slate-800',
    };
    return colores[estado] || 'bg-gray-100 text-gray-800 dark:text-gray-100';
};

// Acciones Rápidas
const generarCobro = () => {
    if (confirm('¿Generar un nuevo cobro para esta póliza?')) {
        router.post(route('polizas-servicio.generar-cobro', props.poliza.id));
    }
};

const enviarRecordatorio = () => {
    if (confirm('¿Enviar recordatorio de renovación al cliente?')) {
        router.post(route('polizas-servicio.enviar-recordatorio', props.poliza.id));
    }
};

// Indicador de salud de la póliza
const getSaludPoliza = () => {
    const diasVencer = props.poliza.dias_para_vencer;
    const excedeHoras = props.poliza.excede_horas;
    const porcentajeHoras = props.poliza.porcentaje_horas || 0;
    
    if (diasVencer !== null && diasVencer <= 0) return { color: 'bg-red-500', label: 'Vencida', icon: '🔴' };
    if (diasVencer !== null && diasVencer <= 7) return { color: 'bg-orange-500', label: 'Urgente', icon: '🟠' };
    if (excedeHoras) return { color: 'bg-purple-500', label: 'Excedida', icon: '🟣' };
    if (porcentajeHoras >= 80) return { color: 'bg-yellow-500', label: 'Atención', icon: '🟡' };
    return { color: 'bg-green-500', label: 'Saludable', icon: '🟢' };
};
</script>

<template>
    <component :is="isModal ? 'div' : AppLayout" :title="`Póliza ${poliza.folio}`">
        <Head v-if="!isModal" :title="`Póliza ${poliza.folio}`" />

        <div :class="isModal ? 'py-2 bg-slate-900 text-slate-300' : 'py-6 bg-[#0F172A] min-h-screen text-slate-300'">
            <div :class="isModal ? 'w-full' : 'w-full px-4 sm:px-6 lg:px-8'">
                <!-- Header -->
                <div v-if="!isModal" class="mb-8">
                    <Link :href="route('polizas-servicio.index')" class="text-blue-400 hover:text-blue-300 text-sm font-semibold flex items-center gap-2 mb-4 transition-all w-fit">
                        ← Volver al listado
                    </Link>
                    <div class="flex flex-col xl:flex-row justify-between items-start xl:items-end gap-6">
                        <div class="flex items-start gap-4">
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-blue-900/40 border border-blue-500/20 shrink-0">
                                <span class="text-3xl">🛡️</span>
                            </div>
                            <div>
                                <div class="flex items-center gap-3 mb-1">
                                    <span class="font-mono text-sm font-bold text-blue-400 tracking-wider">{{ poliza.folio }}</span>
                                    <span :class="['px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full border', getEstadoBadge(poliza.estado)]">
                                        {{ poliza.estado?.toUpperCase() || 'PÓLIZA' }}
                                    </span>
                                </div>
                                <h1 class="text-3xl sm:text-4xl font-black text-white tracking-tight mb-1">{{ poliza.nombre }}</h1>
                                <p class="text-slate-400 text-lg font-medium">{{ poliza.cliente?.nombre_razon_social }}</p>
                            </div>
                        </div>
                        
                        <div class="flex flex-wrap gap-2 w-full xl:w-auto">
                            <!-- Indicador de Salud -->
                            <div :class="['px-4 py-2.5 rounded-xl font-bold text-white text-sm flex items-center gap-2 shadow-lg', getSaludPoliza().color]">
                                {{ getSaludPoliza().icon }} <span class="hidden sm:inline">{{ getSaludPoliza().label }}</span>
                            </div>
                            
                            <!-- Acciones Rápidas -->
                            <button @click="generarCobro" class="flex-1 xl:flex-none px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold shadow-lg shadow-emerald-900/20 transition-all border border-emerald-500/50 flex items-center justify-center gap-2">
                                <span>💰</span> <span class="hidden sm:inline">Cobrar</span>
                            </button>
                            
                            <button v-if="poliza.dias_para_vencer !== null && poliza.dias_para_vencer <= 30" @click="enviarRecordatorio" class="flex-1 xl:flex-none px-4 py-2.5 bg-orange-500 hover:bg-orange-400 text-white rounded-xl font-bold shadow-lg shadow-orange-900/20 transition-all flex items-center justify-center gap-2">
                                <span>📧</span> <span class="hidden sm:inline">Recordar</span>
                            </button>

                            <div class="hidden sm:flex bg-slate-800 rounded-xl p-1 border border-slate-700">
                                <a :href="route('polizas-servicio.pdf-beneficios', poliza.id)" target="_blank" class="px-3 py-1.5 text-slate-300 hover:text-white hover:bg-slate-700 rounded-lg transition-colors text-sm font-medium" title="Beneficios">
                                    📄 PDF
                                </a>
                                <a :href="route('polizas-servicio.pdf-contrato', poliza.id)" target="_blank" class="px-3 py-1.5 text-slate-300 hover:text-white hover:bg-slate-700 rounded-lg transition-colors text-sm font-medium" title="Contrato Legal">
                                    📝 Contrato
                                </a>
                            </div>

                            <Link v-if="poliza.horas_incluidas_mensual" :href="route('polizas-servicio.historial', poliza.id)" class="flex-1 xl:flex-none px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold shadow-lg shadow-indigo-900/20 transition-all border border-indigo-500/50 flex items-center justify-center gap-2">
                                <span>📊</span> <span class="hidden sm:inline">Historial</span>
                            </Link>
                            
                            <Link :href="route('polizas-servicio.edit', poliza.id)" class="flex-1 xl:flex-none px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-white rounded-xl font-bold shadow-lg border border-slate-600 flex items-center justify-center gap-2">
                                <span>⚙️</span> <span class="hidden sm:inline">Editar</span>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Header Modal -->
                <div v-if="isModal" class="p-6 border-b border-slate-700 bg-slate-900 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="font-mono text-lg font-bold text-blue-400">{{ poliza.folio }}</span>
                            <span :class="['px-2 py-0.5 text-[10px] font-black rounded-full border', getEstadoBadge(poliza.estado)]">
                                {{ poliza.estado?.toUpperCase() || 'PÓLIZA' }}
                            </span>
                        </div>
                        <h1 class="text-xl font-bold text-white">{{ poliza.nombre }}</h1>
                        <p class="text-sm text-slate-400 font-medium">{{ poliza.cliente?.nombre_razon_social }}</p>
                    </div>
                    <div class="flex gap-2">
                        <!-- Botones Modal simplificados -->
                        <Link :href="route('polizas-servicio.edit', poliza.id)" class="p-2 bg-slate-800 text-slate-300 rounded-lg hover:bg-slate-700 transition-colors border border-slate-700">
                            ⚙️
                        </Link>
                    </div>
                </div>

                <div :class="['grid grid-cols-1 lg:grid-cols-3 gap-8', isModal ? 'p-6' : '']">
                    <!-- Columna Principal -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Detalles y Alcance -->
                        <div class="bg-slate-800/40 backdrop-blur border border-slate-700/50 rounded-2xl shadow-xl p-6">
                            <h3 class="font-bold text-white mb-6 border-b border-slate-700/50 pb-4 flex items-center gap-3">
                                <div class="p-2 bg-blue-500/20 rounded-lg text-blue-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                Descripción y Alcance
                            </h3>
                            <p class="text-slate-300 whitespace-pre-wrap leading-relaxed">{{ poliza.descripcion || 'Sin descripción detallada.' }}</p>
                            <div v-if="poliza.notas" class="mt-6 p-4 bg-blue-900/20 border border-blue-800/50 rounded-xl text-sm text-blue-300">
                                <strong class="text-blue-200 block mb-1">Notas Administrativas:</strong> {{ poliza.notas }}
                            </div>
                        </div>

                        <!-- Planes de Mantenimiento y Checklist -->
                        <MantenimientosManager :poliza="poliza" />

                        <!-- Servicios Incluidos -->
                        <div class="bg-slate-800/40 backdrop-blur border border-slate-700/50 rounded-2xl shadow-xl overflow-hidden">
                            <div class="px-6 py-5 border-b border-slate-700/50">
                                <h3 class="font-bold text-white flex items-center gap-3">
                                    <div class="p-2 bg-indigo-500/20 rounded-lg text-indigo-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    Servicios Cubiertos del Catálogo
                                </h3>
                            </div>
                            <table class="min-w-full divide-y divide-slate-700/50">
                                <thead class="bg-slate-900/30 text-xs font-bold text-slate-400 uppercase tracking-wider">
                                    <tr>
                                        <th class="px-6 py-4 text-left">Servicio</th>
                                        <th class="px-6 py-4 text-center">Cant. Mensual</th>
                                        <th class="px-6 py-4 text-right">Precio Acordado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-700/50">
                                    <tr v-for="servicio in poliza.servicios" :key="servicio.id" class="hover:bg-slate-700/30 transition-colors">
                                        <td class="px-6 py-4 text-sm font-medium text-slate-200">{{ servicio.nombre }}</td>
                                        <td class="px-6 py-4 text-center text-sm text-slate-400">{{ servicio.pivot.cantidad }}</td>
                                        <td class="px-6 py-4 text-right text-sm font-mono text-emerald-400 font-bold">
                                            {{ servicio.pivot.precio_especial ? formatCurrency(servicio.pivot.precio_especial) : '-' }}
                                        </td>
                                    </tr>
                                    <tr v-if="poliza.servicios.length === 0">
                                        <td colspan="3" class="px-6 py-12 text-center text-slate-500 text-sm italic">
                                            No se han especificado servicios individuales del catálogo.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Historial de Facturación y Smart Billing -->
                        <div class="bg-slate-800/40 backdrop-blur border border-blue-900/30 rounded-2xl shadow-xl overflow-hidden relative">
                            <!-- Glow effect -->
                            <div class="absolute top-0 right-0 w-64 h-64 bg-blue-600/10 rounded-full blur-3xl -z-10 transform translate-x-1/2 -translate-y-1/2"></div>
                            
                            <div class="px-6 py-5 border-b border-slate-700/50 bg-slate-800/60 flex justify-between items-center">
                                <h3 class="font-bold text-white flex items-center gap-3">
                                    <div class="p-2 bg-sky-500/20 rounded-lg text-sky-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                        </svg>
                                    </div>
                                    Cargos y Facturación (Smart Billing)
                                </h3>
                                <span class="bg-blue-600 shadow-lg shadow-blue-500/40 text-white text-[10px] px-3 py-1 rounded-full font-black uppercase tracking-widest border border-white/10">
                                    Nuevo
                                </span>
                            </div>
                            <table class="min-w-full divide-y divide-slate-700/50">
                                <thead class="bg-slate-900/30 text-xs font-bold text-slate-400 uppercase tracking-wider">
                                    <tr>
                                        <th class="px-6 py-4 text-left">Emisión / Vence</th>
                                        <th class="px-6 py-4 text-left">Concepto</th>
                                        <th class="px-6 py-4 text-center">Estado</th>
                                        <th class="px-6 py-4 text-right">Subtotal / IVA</th>
                                        <th class="px-6 py-4 text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-700/50">
                                    <tr v-for="cargo in poliza.cargos" :key="cargo.id" class="hover:bg-slate-700/30 transition-colors">
                                        <td class="px-6 py-4 text-xs">
                                            <div class="font-bold text-slate-200">{{ formatDate(cargo.fecha_emision) }}</div>
                                            <div class="text-[10px] text-red-400 font-bold mt-1">Vence: {{ formatDate(cargo.fecha_vencimiento) }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-300">
                                            <div class="font-medium truncate max-w-[200px]">{{ cargo.concepto }}</div>
                                            <div class="text-[10px] text-slate-500 uppercase font-black tracking-wider mt-0.5">{{ cargo.tipo_ciclo }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span :class="['px-2.5 py-1 text-[9px] font-black rounded uppercase border border-transparent', getEstadoCobroBadge(cargo.estado)]">
                                                {{ cargo.estado }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right text-xs">
                                            <div class="text-slate-400">{{ formatCurrency(cargo.subtotal) }}</div>
                                            <div class="text-[10px] text-slate-600 font-bold">+ {{ formatCurrency(cargo.iva) }} IVA</div>
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm font-black text-white">
                                            {{ formatCurrency(cargo.total) }}
                                        </td>
                                    </tr>
                                    <tr v-if="!poliza.cargos || poliza.cargos.length === 0">
                                        <td colspan="5" class="px-6 py-12 text-center text-slate-500 text-sm">
                                            Aún no hay cargos generados por el motor Smart Billing.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Historial de Servicios (Tickets) -->
                        <div class="bg-slate-800/40 backdrop-blur border border-slate-700/50 rounded-2xl shadow-xl overflow-hidden">
                            <div class="px-6 py-5 border-b border-slate-700/50 flex justify-between items-center">
                                <h3 class="font-bold text-white flex items-center gap-3">
                                    <div class="p-2 bg-purple-500/20 rounded-lg text-purple-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    Historial Reciente de Servicios
                                </h3>
                                <Link :href="route('soporte.create', { poliza_id: poliza.id, cliente_id: poliza.cliente_id })" class="text-xs font-bold bg-blue-600 hover:bg-blue-500 text-white px-3 py-1.5 rounded-lg shadow-lg shadow-blue-900/30 transition-all border border-blue-400/30">
                                    + Reportar Servicio
                                </Link>
                            </div>
                            <table class="min-w-full divide-y divide-slate-700/50">
                                <thead class="bg-slate-900/30 text-xs font-bold text-slate-400 uppercase tracking-wider">
                                    <tr>
                                        <th class="px-6 py-4 text-left">Folio</th>
                                        <th class="px-6 py-4 text-left">Título / Asunto</th>
                                        <th class="px-6 py-4 text-center">Estado</th>
                                        <th class="px-6 py-4 text-right">Fecha</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-700/50">
                                    <tr v-for="ticket in poliza.tickets" :key="ticket.id" class="hover:bg-slate-700/30 cursor-pointer transition-colors" @click="router.visit(route('soporte.show', ticket.id))">
                                        <td class="px-6 py-4 font-mono text-xs font-bold text-blue-400">{{ ticket.numero }}</td>
                                        <td class="px-6 py-4 text-sm text-slate-200 truncate max-w-xs">{{ ticket.titulo }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="px-2.5 py-1 text-[10px] font-bold rounded bg-slate-700 text-slate-300 border border-slate-600 uppercase tracking-wide">
                                                {{ ticket.estado }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right text-xs text-slate-500">
                                            {{ formatDate(ticket.created_at) }}
                                        </td>
                                    </tr>
                                    <tr v-if="poliza.tickets.length === 0">
                                        <td colspan="4" class="px-6 py-12 text-center text-slate-500 text-sm">
                                            No hay servicios registrados aún bajo esta póliza.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Sidebar Stats & Info -->
                    <div class="space-y-6">
                        <!-- Card de Resumen -->
                        <div class="bg-gradient-to-br from-blue-600 to-indigo-800 rounded-2xl shadow-xl shadow-blue-900/40 border border-blue-400/30 p-6 text-white relative overflow-hidden">
                            <!-- Texture decoration -->
                            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full blur-2xl -translate-y-1/2 translate-x-1/2"></div>
                            
                            <h3 class="text-lg font-bold mb-6 opacity-90 flex items-center gap-2">
                                <span class="bg-white/20 p-1.5 rounded-lg">📊</span> Resumen Mensual
                            </h3>
                            
                            <div class="space-y-6">
                                <!-- Consumo de Servicios Remotos (Soporte Técnico) -->
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <div class="text-[10px] opacity-75 uppercase font-black tracking-wider flex items-center gap-1">
                                            <span>📞</span> Soporte Técnico
                                        </div>
                                        <div class="text-[10px] font-black opacity-60 bg-black/20 px-2 py-0.5 rounded">Remoto</div>
                                    </div>
                                    <div class="flex items-end gap-2">
                                        <span class="text-4xl font-black tracking-tight">{{ stats.tickets_mes }}</span>
                                        <span class="text-lg opacity-60 pb-1 font-medium">/ {{ poliza.limite_mensual_tickets || '10' }}</span>
                                    </div>
                                    <div class="mt-2 w-full bg-black/20 rounded-full h-2.5 backdrop-blur-sm border border-white/5">
                                        <div 
                                            class="h-2.5 rounded-full transition-all duration-700 ease-out shadow-lg" 
                                            :class="stats.excede_limite ? 'bg-red-400' : 'bg-emerald-400'"
                                            :style="{ width: Math.min((stats.tickets_mes / (poliza.limite_mensual_tickets || 10)) * 100, 100) + '%' }"
                                        ></div>
                                    </div>
                                    <p v-if="stats.excede_limite" class="mt-2 text-[10px] font-black bg-red-500/30 border border-red-500/30 p-1.5 rounded text-center text-red-100 uppercase tracking-wide">
                                        ⚠️ Límite Excedido
                                    </p>
                                </div>

                                <!-- Consumo de Visitas en Sitio -->
                                <div class="pt-4 border-t border-white/10">
                                    <div class="flex justify-between items-center mb-1">
                                        <div class="text-[10px] opacity-75 uppercase font-black tracking-wider flex items-center gap-1">
                                            <span>🏠</span> Visitas en Sitio
                                        </div>
                                    </div>
                                    <div class="flex items-end gap-2">
                                        <span class="text-4xl font-black tracking-tight">{{ stats.visitas_mes }}</span>
                                        <span class="text-lg opacity-60 pb-1 font-medium">/ {{ poliza.visitas_sitio_mensuales || '1' }}</span>
                                    </div>
                                    <div class="mt-2 w-full bg-black/20 rounded-full h-2.5 backdrop-blur-sm border border-white/5">
                                        <div 
                                            class="h-2.5 rounded-full transition-all duration-700 ease-out shadow-lg" 
                                            :class="stats.excede_visitas ? 'bg-red-400' : 'bg-amber-400'"
                                            :style="{ width: Math.min((stats.visitas_mes / (poliza.visitas_sitio_mensuales || 1)) * 100, 100) + '%' }"
                                        ></div>
                                    </div>
                                    <p v-if="stats.excede_visitas" class="mt-2 text-[10px] font-black bg-red-500/30 border border-red-500/30 p-1.5 rounded text-center text-red-100 uppercase tracking-wide">
                                        ⚠️ Visita Extra (Costo Adicional)
                                    </p>
                                </div>

                                <!-- Asesorías (No consumen póliza) -->
                                <div class="pt-4 border-t border-white/10 flex justify-between items-center">
                                    <div>
                                        <div class="text-[10px] opacity-75 uppercase font-black tracking-wider flex items-center gap-1 mb-1">
                                            <span>💡</span> Asesorías
                                        </div>
                                        <div class="flex items-end gap-2">
                                            <span class="text-2xl font-bold">{{ stats.tickets_asesoria || 0 }}</span>
                                            <span class="text-xs opacity-60 pb-1 italic">realizadas</span>
                                        </div>
                                    </div>
                                    <div class="bg-emerald-400/20 text-emerald-100 border border-emerald-400/30 text-[9px] px-2 py-1 rounded font-black uppercase text-center">
                                        Ilimitado
                                    </div>
                                </div>

                                <!-- Consumo de Horas (Phase 2) -->
                                <div v-if="poliza.horas_incluidas_mensual" class="pt-4 border-t border-white/10">
                                    <div class="text-[10px] opacity-75 uppercase font-black tracking-wider mb-1">⏱️ Consumo de Horas</div>
                                    <div class="flex items-end gap-2">
                                        <span class="text-3xl font-black tracking-tight">{{ poliza.horas_consumidas_mes || 0 }}</span>
                                        <span class="text-sm opacity-60 pb-1">/ {{ poliza.horas_incluidas_mensual }}h</span>
                                    </div>
                                    <div class="mt-2 w-full bg-black/20 rounded-full h-2.5 backdrop-blur-sm border border-white/5">
                                        <div 
                                            class="h-2.5 rounded-full transition-all duration-700 ease-out shadow-lg" 
                                            :class="poliza.excede_horas ? 'bg-red-400' : poliza.porcentaje_horas > 80 ? 'bg-yellow-400' : 'bg-green-400'"
                                            :style="{ width: Math.min(poliza.porcentaje_horas || 0, 100) + '%' }"
                                        ></div>
                                    </div>
                                    <div class="flex justify-between mt-1.5">
                                        <span class="text-[9px] opacity-60 font-bold uppercase">{{ poliza.porcentaje_horas || 0 }}% usado</span>
                                        <span v-if="poliza.costo_hora_excedente" class="text-[9px] opacity-60 font-mono">
                                            Extra: ${{ poliza.costo_hora_excedente }}/hr
                                        </span>
                                    </div>
                                </div>

                                <div class="pt-6 border-t border-white/10">
                                    <div class="text-[10px] opacity-75 uppercase font-black tracking-wider mb-2">Próximo Cobro Programado</div>
                                    <div class="text-3xl font-black tracking-tight">{{ formatCurrency(poliza.monto_mensual) }}</div>
                                    <div class="flex items-center gap-2 mt-2">
                                        <span class="bg-indigo-500/30 px-2 py-0.5 rounded text-[10px] font-bold border border-indigo-400/30">
                                            Día {{ poliza.dia_cobro }} de cada mes
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Alerta de Vencimiento -->
                        <div v-if="poliza.dias_para_vencer !== null && poliza.dias_para_vencer <= 30" 
                             :class="[
                                 'rounded-2xl shadow-xl p-5 border',
                                 poliza.dias_para_vencer <= 0 ? 'bg-red-900/20 border-red-500/50' :
                                 poliza.dias_para_vencer <= 7 ? 'bg-orange-900/20 border-orange-500/50' :
                                 'bg-yellow-900/20 border-yellow-500/50'
                             ]"
                        >
                            <div class="flex items-center gap-4">
                                <div :class="[
                                    'w-12 h-12 rounded-xl flex items-center justify-center text-2xl shadow-lg border border-white/10',
                                    poliza.dias_para_vencer <= 0 ? 'bg-red-500 text-white' :
                                    poliza.dias_para_vencer <= 7 ? 'bg-orange-500 text-white' :
                                    'bg-yellow-500 text-white'
                                ]">
                                    ⏰
                                </div>
                                <div>
                                    <div :class="[
                                        'text-base font-black uppercase tracking-wide',
                                        poliza.dias_para_vencer <= 0 ? 'text-red-400' :
                                        poliza.dias_para_vencer <= 7 ? 'text-orange-400' :
                                        'text-yellow-400'
                                    ]">
                                        {{ poliza.dias_para_vencer <= 0 ? '¡PÓLIZA VENCIDA!' : 
                                           poliza.dias_para_vencer === 1 ? '¡Vence mañana!' :
                                           `Vence en ${poliza.dias_para_vencer} días` }}
                                    </div>
                                    <div class="text-sm text-slate-400 font-mono mt-0.5">
                                        Expira: {{ formatDate(poliza.fecha_fin) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Detalles Técnicos Sidebar -->
                        <div class="bg-slate-800/40 backdrop-blur border border-slate-700/50 rounded-2xl shadow-xl p-6">
                            <h3 class="font-bold text-white mb-4 text-sm uppercase tracking-wider border-b border-slate-700/50 pb-2">Información del Contrato</h3>
                            <dl class="space-y-4 text-sm mt-4">
                                <div class="flex justify-between items-center">
                                    <dt class="text-slate-400 font-medium">Fecha Inicio</dt>
                                    <dd class="text-white font-mono font-bold">{{ formatDate(poliza.fecha_inicio) }}</dd>
                                </div>
                                <div class="flex justify-between items-center">
                                    <dt class="text-slate-400 font-medium">Vence</dt>
                                    <dd class="text-white font-mono font-bold">{{ formatDate(poliza.fecha_fin) }}</dd>
                                </div>
                                <div class="flex justify-between items-center">
                                    <dt class="text-slate-400 font-medium">Renovación</dt>
                                    <dd>
                                        <span v-if="poliza.renovacion_automatica" class="text-emerald-400 font-bold bg-emerald-400/10 px-2 py-0.5 rounded border border-emerald-400/20 text-xs uppercase">Automática</span>
                                        <span v-else class="text-slate-400 bg-slate-700/50 px-2 py-0.5 rounded border border-slate-600 text-xs uppercase">Manual</span>
                                    </dd>
                                </div>
                                <div class="pt-3 border-t border-slate-700/50 flex justify-between items-center">
                                    <dt class="text-slate-400 font-medium">SLA Respuesta</dt>
                                    <dd class="text-blue-300 font-black px-2 py-1 bg-blue-900/30 border border-blue-500/30 rounded text-xs">
                                        {{ poliza.sla_horas_respuesta ? poliza.sla_horas_respuesta + ' Horas' : 'Sin definir' }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Equipos Vinculados -->
                        <div class="bg-slate-800/40 backdrop-blur border border-slate-700/50 rounded-2xl shadow-xl p-6">
                            <h3 class="font-bold text-white mb-4 text-sm uppercase tracking-wider flex items-center gap-2 border-b border-slate-700/50 pb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                </svg>
                                Equipos Cubiertos <span class="text-slate-500 ml-1">({{ poliza.equipos.length }})</span>
                            </h3>
                            <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                                <div v-for="equipo in poliza.equipos" :key="equipo.id" class="p-3 bg-slate-800 rounded-lg border border-slate-700 hover:border-blue-500/30 transition-colors group">
                                    <div class="font-bold text-slate-200 text-xs group-hover:text-blue-300 transition-colors">{{ equipo.nombre }}</div>
                                    <div class="text-slate-500 text-[10px] font-mono mt-0.5">S/N: {{ equipo.serie }}</div>
                                </div>
                                <div v-if="poliza.equipos.length === 0" class="text-center py-6 text-slate-500 text-xs italic bg-slate-900/20 rounded-lg border border-slate-800 border-dashed">
                                    No hay equipos específicos listados.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>
