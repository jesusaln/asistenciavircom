<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

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

        <div :class="isModal ? 'py-2' : 'py-6'">
            <div :class="isModal ? 'w-full' : 'w-full px-4 sm:px-6 lg:px-8'">
                <!-- Header -->
                <div v-if="!isModal" class="mb-6">
                    <Link :href="route('polizas-servicio.index')" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">
                        ← Volver al listado
                    </Link>
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <div class="flex items-center gap-3">
                                <span class="font-mono text-xl font-bold text-blue-600">{{ poliza.folio }}</span>
                                <span :class="['px-3 py-1 text-sm font-bold rounded-full border', getEstadoBadge(poliza.estado)]">
                                    {{ poliza.estado?.toUpperCase() || 'PÓLIZA' }}
                                </span>
                            </div>
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ poliza.nombre }}</h1>
                            <p class="text-gray-500 dark:text-gray-400">{{ poliza.cliente?.nombre_razon_social }}</p>
                        </div>
                        <div class="flex gap-2 flex-wrap">
                            <!-- Indicador de Salud -->
                            <div :class="['px-3 py-2 rounded-lg font-bold text-white text-sm flex items-center gap-2', getSaludPoliza().color]">
                                {{ getSaludPoliza().icon }} {{ getSaludPoliza().label }}
                            </div>
                            <!-- Acciones Rápidas -->
                            <button @click="generarCobro" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold shadow-lg transition">
                                💰 Cobrar Ahora
                            </button>
                            <button v-if="poliza.dias_para_vencer !== null && poliza.dias_para_vencer <= 30" @click="enviarRecordatorio" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 font-semibold shadow-lg transition">
                                📧 Recordar Renovación
                            </button>
                            <a :href="route('polizas-servicio.pdf-beneficios', poliza.id)" target="_blank" class="px-4 py-2 bg-blue-100 border border-blue-300 rounded-lg hover:bg-blue-200 font-semibold text-blue-700">
                                📄 Beneficios
                            </a>
                            <a :href="route('polizas-servicio.pdf-contrato', poliza.id)" target="_blank" class="px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 font-semibold text-gray-700">
                                📝 Contrato
                            </a>
                            <Link v-if="poliza.horas_incluidas_mensual" :href="route('polizas-servicio.historial', poliza.id)" class="px-4 py-2 bg-purple-100 border border-purple-300 rounded-lg hover:bg-purple-200 font-semibold text-purple-700">
                                📊 Historial
                            </Link>
                            <Link :href="route('polizas-servicio.edit', poliza.id)" class="px-4 py-2 bg-white dark:bg-slate-900 border border-gray-300 rounded-lg shadow-sm hover:bg-white dark:bg-slate-900 font-semibold text-gray-700">
                                ⚙️ Editar
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Header Modal -->
                <div v-if="isModal" class="p-6 border-b bg-white dark:bg-slate-900/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="font-mono text-lg font-bold text-blue-600">{{ poliza.folio }}</span>
                            <span :class="['px-2 py-0.5 text-xs font-bold rounded-full border', getEstadoBadge(poliza.estado)]">
                                {{ poliza.estado?.toUpperCase() || 'PÓLIZA' }}
                            </span>
                        </div>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ poliza.nombre }}</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">{{ poliza.cliente?.nombre_razon_social }}</p>
                    </div>
                    <div class="flex gap-2">
                        <a :href="route('polizas-servicio.pdf-beneficios', poliza.id)" target="_blank" class="p-2 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition-colors border border-green-200" title="Ver PDF de Beneficios">
                            📄 Beneficios
                        </a>
                        <a :href="route('polizas-servicio.pdf-contrato', poliza.id)" target="_blank" class="p-2 bg-white dark:bg-slate-900 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors border border-gray-200 dark:border-slate-800" title="Ver PDF Contrato">
                            📝 Contrato
                        </a>
                        <Link :href="route('polizas-servicio.edit', poliza.id)" class="p-2 bg-white dark:bg-slate-900 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors border border-gray-200 dark:border-slate-800" title="Editar Póliza">
                            ⚙️ Editar
                        </Link>
                    </div>
                </div>

                <div :class="['grid grid-cols-1 lg:grid-cols-3 gap-6', isModal ? 'p-6' : '']">
                    <!-- Columna Principal -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Detalles y Alcance -->
                        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-4 border-b pb-2 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Descripción y Alcance
                            </h3>
                            <p class="text-gray-700 whitespace-pre-wrap">{{ poliza.descripcion || 'Sin descripción detallada.' }}</p>
                            <div v-if="poliza.notas" class="mt-4 p-4 bg-blue-50 rounded-lg text-sm text-blue-800">
                                <strong>Notas Administrativas:</strong><br> {{ poliza.notas }}
                            </div>
                        </div>

                        <!-- Servicios Incluidos -->
                        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b">
                                <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Servicios Cubiertos del Catálogo
                                </h3>
                            </div>
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
                                <thead class="bg-white dark:bg-slate-900 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">
                                    <tr>
                                        <th class="px-6 py-3 text-left">Servicio</th>
                                        <th class="px-6 py-3 text-center">Cant. Mensual</th>
                                        <th class="px-6 py-3 text-right">Precio Acordado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-slate-800">
                                    <tr v-for="servicio in poliza.servicios" :key="servicio.id">
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ servicio.nombre }}</td>
                                        <td class="px-6 py-4 text-center text-sm text-gray-600 dark:text-gray-300">{{ servicio.pivot.cantidad }}</td>
                                        <td class="px-6 py-4 text-right text-sm font-mono text-gray-900 dark:text-white">
                                            {{ servicio.pivot.precio_especial ? formatCurrency(servicio.pivot.precio_especial) : '-' }}
                                        </td>
                                    </tr>
                                    <tr v-if="poliza.servicios.length === 0">
                                        <td colspan="3" class="px-6 py-8 text-center text-gray-400 text-sm">
                                            No se han especificado servicios individuales del catálogo.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Historial de Facturación y Cobros -->
                        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm overflow-hidden border border-blue-100">
                            <div class="px-6 py-4 border-b bg-blue-50/50">
                                <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                    </svg>
                                    Historial de Facturación y Cobros
                                </h3>
                            </div>
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
                                <thead class="bg-white dark:bg-slate-900 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">
                                    <tr>
                                        <th class="px-6 py-3 text-left">Fecha</th>
                                        <th class="px-6 py-3 text-left">Concepto</th>
                                        <th class="px-6 py-3 text-center">Estado</th>
                                        <th class="px-6 py-3 text-right">Monto</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-slate-800">
                                    <tr v-for="cobro in poliza.cuentas_por_cobrar" :key="cobro.id" class="hover:bg-white dark:bg-slate-900">
                                        <td class="px-6 py-4 text-xs font-medium text-gray-600 dark:text-gray-300">{{ formatDate(cobro.created_at) }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                            <div class="font-medium">Mensualidad Póliza</div>
                                            <div class="text-[10px] text-gray-500 dark:text-gray-400 truncate max-w-xs">{{ cobro.notas }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span :class="['px-2 py-0.5 text-[9px] font-black rounded uppercase border', getEstadoCobroBadge(cobro.estado)]">
                                                {{ cobro.estado }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm font-bold text-gray-900 dark:text-white">
                                            {{ formatCurrency(cobro.monto_total) }}
                                        </td>
                                    </tr>
                                    <tr v-if="!poliza.cuentas_por_cobrar || poliza.cuentas_por_cobrar.length === 0">
                                        <td colspan="4" class="px-6 py-8 text-center text-gray-400 text-sm italic">
                                            No hay registros de cobros generados automáticamente aún.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Historial de Servicios (Tickets) -->
                        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b flex justify-between items-center">
                                <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Historial Reciente de Servicios
                                </h3>
                                <Link :href="route('soporte.create', { poliza_id: poliza.id, cliente_id: poliza.cliente_id })" class="text-sm font-bold text-blue-600 hover:underline">
                                    + Reportar Servicio
                                </Link>
                            </div>
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
                                <thead class="bg-white dark:bg-slate-900 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">
                                    <tr>
                                        <th class="px-6 py-3 text-left">Folio</th>
                                        <th class="px-6 py-3 text-left">Título / Asunto</th>
                                        <th class="px-6 py-3 text-left">Estado</th>
                                        <th class="px-6 py-3 text-right">Fecha</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-slate-800">
                                    <tr v-for="ticket in poliza.tickets" :key="ticket.id" class="hover:bg-white dark:bg-slate-900 cursor-pointer" @click="router.visit(route('soporte.show', ticket.id))">
                                        <td class="px-6 py-4 font-mono text-xs font-bold text-blue-600">{{ ticket.numero }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white truncate max-w-xs">{{ ticket.titulo }}</td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-gray-100 text-gray-700 uppercase">
                                                {{ ticket.estado }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right text-xs text-gray-500 dark:text-gray-400">
                                            {{ formatDate(ticket.created_at) }}
                                        </td>
                                    </tr>
                                    <tr v-if="poliza.tickets.length === 0">
                                        <td colspan="4" class="px-6 py-8 text-center text-gray-400 text-sm">
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
                        <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-xl shadow-lg p-6 text-white">
                            <h3 class="text-lg font-bold mb-4 opacity-90">Resumen Mensual</h3>
                            
                            <div class="space-y-4">
                                <!-- Consumo de Servicios Remotos (Soporte Técnico) -->
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <div class="text-[10px] opacity-75 uppercase font-black tracking-wider flex items-center gap-1">
                                            <span>📞</span> Soporte Técnico (Remoto)
                                        </div>
                                        <div class="text-[10px] font-black opacity-60">Mensual</div>
                                    </div>
                                    <div class="flex items-end gap-2">
                                        <span class="text-4xl font-extrabold">{{ stats.tickets_mes }}</span>
                                        <span class="text-lg opacity-75 pb-1">/ {{ poliza.limite_mensual_tickets || '10' }}</span>
                                    </div>
                                    <div class="mt-2 w-full bg-blue-900/50 rounded-full h-2">
                                        <div 
                                            class="h-2 rounded-full transition-all duration-500" 
                                            :class="stats.excede_limite ? 'bg-red-400' : 'bg-green-400'"
                                            :style="{ width: Math.min((stats.tickets_mes / (poliza.limite_mensual_tickets || 10)) * 100, 100) + '%' }"
                                        ></div>
                                    </div>
                                    <p class="mt-1.5 text-[11px] opacity-80 leading-tight">
                                        Incluye: Windows, Software, Impresoras y fallas de Sistema.
                                    </p>
                                    <p v-if="stats.excede_limite" class="mt-2 text-[10px] font-black bg-red-500/30 p-1 rounded text-center text-red-100">
                                        ⚠️ LÍMITE DE SOPORTE EXCEDIDO
                                    </p>
                                </div>

                                <!-- Consumo de Visitas en Sitio -->
                                <div class="pt-4 border-t border-white/10">
                                    <div class="flex justify-between items-center mb-1">
                                        <div class="text-[10px] opacity-75 uppercase font-black tracking-wider flex items-center gap-1">
                                            <span>🏠</span> Visitas en Sitio
                                        </div>
                                        <div class="text-[10px] font-black opacity-60">Mensual</div>
                                    </div>
                                    <div class="flex items-end gap-2">
                                        <span class="text-4xl font-extrabold">{{ stats.visitas_mes }}</span>
                                        <span class="text-lg opacity-75 pb-1">/ {{ poliza.visitas_sitio_mensuales || '1' }}</span>
                                    </div>
                                    <div class="mt-2 w-full bg-blue-900/40 rounded-full h-2">
                                        <div 
                                            class="h-2 rounded-full transition-all duration-500" 
                                            :class="stats.excede_visitas ? 'bg-red-400' : 'bg-amber-400'"
                                            :style="{ width: Math.min((stats.visitas_mes / (poliza.visitas_sitio_mensuales || 1)) * 100, 100) + '%' }"
                                        ></div>
                                    </div>
                                    <p v-if="stats.excede_visitas" class="mt-2 text-[10px] font-black bg-red-500/30 p-1 rounded text-center text-red-100 uppercase">
                                        ⚠️ Visita Extra (Con Costo)
                                    </p>
                                </div>

                                <!-- Asesorías (No consumen póliza) -->
                                <div class="pt-4 border-t border-white/10">
                                    <div class="flex justify-between items-center mb-1">
                                        <div class="text-[10px] opacity-75 uppercase font-black tracking-wider flex items-center gap-1">
                                            <span>💡</span> Asesorías y Consultas
                                        </div>
                                        <div class="bg-green-400/20 text-green-300 text-[8px] px-1.5 rounded font-black uppercase">Ilimitado</div>
                                    </div>
                                    <div class="flex items-end gap-2">
                                        <span class="text-3xl font-extrabold">{{ stats.tickets_asesoria || 0 }}</span>
                                        <span class="text-sm opacity-60 pb-1 italic">realizadas este mes</span>
                                    </div>
                                    <p class="mt-1 text-[10px] opacity-70 italic">Consultas sobre uso y procedimientos.</p>
                                </div>

                                <!-- Consumo de Horas (Phase 2) -->
                                <div v-if="poliza.horas_incluidas_mensual" class="pt-3 border-t border-white/10">
                                    <div class="text-xs opacity-75 uppercase font-bold tracking-wider">Consumo de Horas</div>
                                    <div class="flex items-end gap-2 mt-1">
                                        <span class="text-3xl font-extrabold">{{ poliza.horas_consumidas_mes || 0 }}</span>
                                        <span class="text-base opacity-75 pb-0.5">/ {{ poliza.horas_incluidas_mensual }}h</span>
                                    </div>
                                    <div class="mt-2 w-full bg-blue-900/50 rounded-full h-2">
                                        <div 
                                            class="h-2 rounded-full transition-all duration-500" 
                                            :class="poliza.excede_horas ? 'bg-red-400' : poliza.porcentaje_horas > 80 ? 'bg-yellow-400' : 'bg-green-400'"
                                            :style="{ width: Math.min(poliza.porcentaje_horas || 0, 100) + '%' }"
                                        ></div>
                                    </div>
                                    <div class="flex justify-between mt-1">
                                        <span class="text-[10px] opacity-60">{{ poliza.porcentaje_horas || 0 }}% usado</span>
                                        <span v-if="poliza.costo_hora_excedente" class="text-[10px] opacity-60">
                                            Excedente: ${{ poliza.costo_hora_excedente }}/hr
                                        </span>
                                    </div>
                                    <p v-if="poliza.excede_horas" class="mt-2 text-[10px] font-bold bg-red-500/30 p-1 rounded text-center text-red-100">
                                        ⚠️ HORAS EXCEDIDAS
                                    </p>
                                </div>

                                <div class="pt-4 border-t border-white/10">
                                    <div class="text-xs opacity-75 uppercase font-bold tracking-wider mb-2">Próximo Cobro</div>
                                    <div class="text-2xl font-bold">{{ formatCurrency(poliza.monto_mensual) }}</div>
                                    <p class="text-[10px] opacity-75 mt-1">Programado para el día {{ poliza.dia_cobro }} de cada mes</p>
                                </div>
                            </div>
                        </div>

                        <!-- Alerta de Vencimiento (Phase 2) -->
                        <div v-if="poliza.dias_para_vencer !== null && poliza.dias_para_vencer <= 30" 
                             :class="[
                                 'rounded-xl shadow-sm p-4 border-2',
                                 poliza.dias_para_vencer <= 0 ? 'bg-red-50 border-red-300' :
                                 poliza.dias_para_vencer <= 7 ? 'bg-orange-50 border-orange-300' :
                                 'bg-yellow-50 border-yellow-300'
                             ]"
                        >
                            <div class="flex items-center gap-3">
                                <div :class="[
                                    'w-10 h-10 rounded-full flex items-center justify-center text-lg',
                                    poliza.dias_para_vencer <= 0 ? 'bg-red-100 text-red-600' :
                                    poliza.dias_para_vencer <= 7 ? 'bg-orange-100 text-orange-600' :
                                    'bg-yellow-100 text-yellow-600'
                                ]">
                                    ⏰
                                </div>
                                <div>
                                    <div :class="[
                                        'text-sm font-black',
                                        poliza.dias_para_vencer <= 0 ? 'text-red-800' :
                                        poliza.dias_para_vencer <= 7 ? 'text-orange-800' :
                                        'text-yellow-800'
                                    ]">
                                        {{ poliza.dias_para_vencer <= 0 ? '¡PÓLIZA VENCIDA!' : 
                                           poliza.dias_para_vencer === 1 ? '¡Vence mañana!' :
                                           `Vence en ${poliza.dias_para_vencer} días` }}
                                    </div>
                                    <div class="text-xs text-gray-600 dark:text-gray-300">
                                        {{ formatDate(poliza.fecha_fin) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Detalles Técnicos Sidebar -->
                        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-4">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-3 text-sm">Información del Contrato</h3>
                            <dl class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400 font-medium">Fecha Inicio</dt>
                                    <dd class="text-gray-900 dark:text-white font-bold">{{ formatDate(poliza.fecha_inicio) }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400 font-medium">Vence</dt>
                                    <dd class="text-gray-900 dark:text-white font-bold">{{ formatDate(poliza.fecha_fin) }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400 font-medium">Renovación</dt>
                                    <dd class="text-gray-900 dark:text-white">
                                        <span v-if="poliza.renovacion_automatica" class="text-green-600 font-bold">Automatica</span>
                                        <span v-else class="text-gray-500 dark:text-gray-400">Manual</span>
                                    </dd>
                                </div>
                                <div class="pt-2 border-t flex justify-between items-center">
                                    <dt class="text-gray-500 dark:text-gray-400 font-medium">SLA Respuesta</dt>
                                    <dd class="text-blue-700 font-black px-2 py-0.5 bg-blue-50 rounded">
                                        {{ poliza.sla_horas_respuesta ? poliza.sla_horas_respuesta + ' Horas' : 'Sin definir' }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Equipos Vinculados -->
                        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-4">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-3 text-sm flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                </svg>
                                Equipos Cubiertos ({{ poliza.equipos.length }})
                            </h3>
                            <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                                <div v-for="equipo in poliza.equipos" :key="equipo.id" class="p-2 bg-white dark:bg-slate-900 rounded border text-xs">
                                    <div class="font-bold text-gray-800 dark:text-gray-100">{{ equipo.nombre }}</div>
                                    <div class="text-gray-500 dark:text-gray-400 font-mono">S/N: {{ equipo.serie }}</div>
                                </div>
                                <div v-if="poliza.equipos.length === 0" class="text-center py-4 text-gray-400 text-xs italic">
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
