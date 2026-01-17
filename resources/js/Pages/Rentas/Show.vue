<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';

defineOptions({ layout: AppLayout });

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
    renta: {
        type: Object,
        required: true,
    },
    cuentasBancarias: {
        type: Array,
        default: () => [],
    },
});

// Estado de modales
const showPagoModal = ref(false);
const cuentaSeleccionada = ref(null);
const montoPago = ref('');
const notasPago = ref('');
const cuentaBancariaId = ref('');

// Formateo de moneda
const formatCurrency = (value) => {
    const num = parseFloat(value) || 0;
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(num);
};

// Formateo de fecha
const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
};

// Nombre del mes
const getMonthName = (date) => {
    return new Date(date).toLocaleDateString('es-MX', { month: 'short' }).toUpperCase();
};

const getYear = (date) => {
    return new Date(date).getFullYear();
};

// Clases de estado de la renta
const estadoClasses = computed(() => {
    const classes = {
        'activo': 'bg-green-100 text-green-800 border-green-200',
        'proximo_vencimiento': 'bg-orange-100 text-orange-800 border-orange-200',
        'vencido': 'bg-red-100 text-red-800 border-red-200',
        'moroso': 'bg-red-200 text-red-900 border-red-300',
        'suspendido': 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'finalizado': 'bg-gray-100 text-gray-600 border-gray-200',
    };
    return classes[props.renta.estado] || 'bg-gray-100 text-gray-600';
});

const estadoLabels = {
    'activo': 'Activo',
    'proximo_vencimiento': 'Próximo Vencimiento',
    'vencido': 'Vencido',
    'moroso': 'Moroso',
    'suspendido': 'Suspendido',
    'finalizado': 'Finalizado',
    'borrador': 'Borrador',
};

// Estadísticas de cobranza
const cobranzaStats = computed(() => {
    const cuentas = props.renta.cuentas_por_cobrar || [];
    const mensualidades = cuentas.filter(c => c.notas === 'Mensualidad');
    const hoy = new Date();
    
    const pagadas = mensualidades.filter(c => c.estado === 'pagado');
    const pendientes = mensualidades.filter(c => c.estado === 'pendiente');
    const vencidas = mensualidades.filter(c => {
        const venc = new Date(c.fecha_vencimiento);
        return c.estado !== 'pagado' && venc < hoy;
    });
    
    const totalPendiente = cuentas
        .filter(c => c.estado !== 'pagado')
        .reduce((sum, c) => sum + parseFloat(c.monto_pendiente || 0), 0);
    
    const proximoVencimiento = pendientes
        .filter(c => new Date(c.fecha_vencimiento) >= hoy)
        .sort((a, b) => new Date(a.fecha_vencimiento) - new Date(b.fecha_vencimiento))[0];
    
    return {
        total: mensualidades.length,
        pagadas: pagadas.length,
        pendientes: pendientes.length,
        vencidas: vencidas.length,
        totalPendiente,
        proximoVencimiento,
        porcentajePagado: mensualidades.length > 0 ? Math.round((pagadas.length / mensualidades.length) * 100) : 0
    };
});

// Estado de salud del contrato
const saludContrato = computed(() => {
    const { vencidas, pendientes } = cobranzaStats.value;
    if (vencidas > 2) return { color: 'red', label: 'Crítico', icon: '🔴' };
    if (vencidas > 0) return { color: 'yellow', label: 'Con Mora', icon: '🟡' };
    return { color: 'green', label: 'Al Día', icon: '🟢' };
});

// Timeline de cobranzas ordenado
const timelineCuentas = computed(() => {
    const cuentas = props.renta.cuentas_por_cobrar || [];
    return [...cuentas]
        .sort((a, b) => new Date(a.fecha_vencimiento) - new Date(b.fecha_vencimiento));
});

// Obtener estado visual de cuenta
const getCuentaStatus = (cuenta) => {
    if (cuenta.estado === 'pagado') {
        return { icon: '✓', color: 'bg-green-500', label: 'Pagado', textColor: 'text-white' };
    }
    const hoy = new Date();
    const venc = new Date(cuenta.fecha_vencimiento);
    if (venc < hoy) {
        return { icon: '!', color: 'bg-red-500', label: 'Vencido', textColor: 'text-white' };
    }
    // Próximo a vencer (7 días)
    const diffDays = Math.ceil((venc - hoy) / (1000 * 60 * 60 * 24));
    if (diffDays <= 7) {
        return { icon: '⌛', color: 'bg-orange-400', label: 'Próximo', textColor: 'text-white' };
    }
    return { icon: '○', color: 'bg-gray-300', label: 'Pendiente', textColor: 'text-gray-600' };
};

// Abrir modal de pago
const abrirModalPago = (cuenta) => {
    cuentaSeleccionada.value = cuenta;
    montoPago.value = cuenta.monto_pendiente;
    notasPago.value = '';
    cuentaBancariaId.value = '';
    showPagoModal.value = true;
};

// Confirmar pago
const confirmarPago = async () => {
    const monto = parseFloat(montoPago.value);
    if (!monto || monto <= 0) {
        notyf.error('Ingresa un monto válido');
        return;
    }
    
    try {
        const response = await fetch(route('cuentas-por-cobrar.registrar-pago', cuentaSeleccionada.value.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                monto: monto,
                notas: notasPago.value || null,
                cuenta_bancaria_id: cuentaBancariaId.value || null
            })
        });
        
        if (response.ok) {
            notyf.success('Pago registrado correctamente');
            showPagoModal.value = false;
            router.reload();
        } else {
            const error = await response.json();
            notyf.error(error.message || 'Error al registrar el pago');
        }
    } catch (error) {
        notyf.error('Error de conexión');
    }
};
</script>

<template>
    <Head :title="`Renta ${renta.numero_contrato}`" />
    
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-orange-50/30 py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            
            <!-- Cabecera -->
            <div class="mb-8">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <Link :href="route('rentas.index')" class="text-gray-500 hover:text-gray-700">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </Link>
                            <h1 class="text-2xl font-bold text-gray-900">{{ renta.numero_contrato }}</h1>
                            <span :class="estadoClasses" class="px-3 py-1 rounded-full text-sm font-medium border">
                                {{ estadoLabels[renta.estado] || renta.estado }}
                            </span>
                        </div>
                        <p class="text-gray-600">
                            <span class="font-medium">{{ renta.cliente?.nombre_razon_social }}</span>
                            · Contrato de {{ renta.meses_duracion }} meses
                        </p>
                    </div>
                    
                    <div class="flex gap-2">
                        <Link :href="route('rentas.edit', renta.id)" class="px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Editar
                        </Link>
                        <a :href="route('rentas.contrato', renta.id)" target="_blank" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Contrato PDF
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Grid principal -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Columna izquierda: Info y Equipos -->
                <div class="lg:col-span-1 space-y-6">
                    
                    <!-- Información del Contrato -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-4 py-3">
                            <h2 class="text-white font-semibold flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Información del Contrato
                            </h2>
                        </div>
                        <div class="p-4 space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-500 text-sm">Fecha Inicio</span>
                                <span class="font-medium">{{ formatDate(renta.fecha_inicio) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 text-sm">Fecha Fin</span>
                                <span class="font-medium">{{ formatDate(renta.fecha_fin) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 text-sm">Día de Pago</span>
                                <span class="font-medium">Día {{ renta.dia_pago }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 text-sm">Forma de Pago</span>
                                <span class="font-medium capitalize">{{ renta.forma_pago }}</span>
                            </div>
                            <hr class="my-2">
                            <div class="flex justify-between">
                                <span class="text-gray-500 text-sm">Mensualidad</span>
                                <span class="font-bold text-lg text-orange-600">{{ formatCurrency(renta.monto_mensual) }}</span>
                            </div>
                            <div v-if="renta.deposito_garantia > 0" class="flex justify-between">
                                <span class="text-gray-500 text-sm">Depósito</span>
                                <span class="font-medium">{{ formatCurrency(renta.deposito_garantia) }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Equipos Rentados -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-4 py-3">
                            <h2 class="text-white font-semibold flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                                Equipos Rentados ({{ renta.equipos?.length || 0 }})
                            </h2>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <div v-for="equipo in renta.equipos" :key="equipo.id" class="p-4 hover:bg-white">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ equipo.nombre }}</p>
                                        <p class="text-sm text-gray-500">{{ equipo.codigo }} · {{ equipo.marca }} {{ equipo.modelo }}</p>
                                    </div>
                                    <span class="text-sm font-semibold text-blue-600">
                                        {{ formatCurrency(equipo.pivot?.precio_mensual || equipo.precio_renta_mensual) }}/mes
                                    </span>
                                </div>
                            </div>
                            <div v-if="!renta.equipos?.length" class="p-4 text-center text-gray-500">
                                No hay equipos asociados
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Columna derecha: Cobranzas -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Resumen de Cobranza -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-4 py-3">
                            <h2 class="text-white font-semibold flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Estado de Cobranza
                                <span class="ml-auto text-sm">{{ saludContrato.icon }} {{ saludContrato.label }}</span>
                            </h2>
                        </div>
                        <div class="p-4">
                            <!-- Estadísticas -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                <div class="text-center p-3 bg-green-50 rounded-lg">
                                    <div class="text-2xl font-bold text-green-600">{{ cobranzaStats.pagadas }}</div>
                                    <div class="text-xs text-green-700">Pagadas</div>
                                </div>
                                <div class="text-center p-3 bg-white rounded-lg">
                                    <div class="text-2xl font-bold text-gray-600">{{ cobranzaStats.pendientes }}</div>
                                    <div class="text-xs text-gray-600">Pendientes</div>
                                </div>
                                <div class="text-center p-3 bg-red-50 rounded-lg">
                                    <div class="text-2xl font-bold text-red-600">{{ cobranzaStats.vencidas }}</div>
                                    <div class="text-xs text-red-700">Vencidas</div>
                                </div>
                                <div class="text-center p-3 bg-orange-50 rounded-lg">
                                    <div class="text-lg font-bold text-orange-600">{{ formatCurrency(cobranzaStats.totalPendiente) }}</div>
                                    <div class="text-xs text-orange-700">Total Pendiente</div>
                                </div>
                            </div>
                            
                            <!-- Barra de progreso -->
                            <div class="mb-4">
                                <div class="flex justify-between text-sm text-gray-600 mb-1">
                                    <span>Progreso del Contrato</span>
                                    <span>{{ cobranzaStats.porcentajePagado }}% ({{ cobranzaStats.pagadas }}/{{ cobranzaStats.total }})</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3">
                                    <div class="bg-green-500 h-3 rounded-full transition-all duration-500" :style="{ width: cobranzaStats.porcentajePagado + '%' }"></div>
                                </div>
                            </div>
                            
                            <!-- Próximo vencimiento -->
                            <div v-if="cobranzaStats.proximoVencimiento" class="bg-orange-50 border border-orange-200 rounded-lg p-3 flex items-center justify-between">
                                <div>
                                    <span class="text-sm text-orange-700">Próximo vencimiento:</span>
                                    <span class="font-semibold text-orange-800 ml-2">{{ formatDate(cobranzaStats.proximoVencimiento.fecha_vencimiento) }}</span>
                                </div>
                                <button @click="abrirModalPago(cobranzaStats.proximoVencimiento)" class="px-3 py-1.5 bg-orange-500 text-white text-sm rounded-lg hover:bg-orange-600 transition-colors">
                                    Registrar Pago
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Timeline de Cobranzas -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-4 py-3">
                            <h2 class="text-white font-semibold flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Calendario de Pagos
                            </h2>
                        </div>
                        <div class="p-4">
                            <!-- Grid de meses -->
                            <div class="grid grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-2">
                                <div 
                                    v-for="cuenta in timelineCuentas" 
                                    :key="cuenta.id"
                                    @click="cuenta.estado !== 'pagado' ? abrirModalPago(cuenta) : null"
                                    :class="[
                                        'relative p-2 rounded-lg border-2 text-center transition-all',
                                        cuenta.estado === 'pagado' 
                                            ? 'bg-green-50 border-green-300' 
                                            : getCuentaStatus(cuenta).label === 'Vencido'
                                                ? 'bg-red-50 border-red-300 cursor-pointer hover:border-red-500'
                                                : getCuentaStatus(cuenta).label === 'Próximo'
                                                    ? 'bg-orange-50 border-orange-300 cursor-pointer hover:border-orange-500'
                                                    : 'bg-white border-gray-200 cursor-pointer hover:border-gray-400'
                                    ]"
                                >
                                    <!-- Indicador de estado -->
                                    <div :class="[getCuentaStatus(cuenta).color, 'w-6 h-6 rounded-full mx-auto mb-1 flex items-center justify-center text-xs font-bold', getCuentaStatus(cuenta).textColor]">
                                        {{ getCuentaStatus(cuenta).icon }}
                                    </div>
                                    
                                    <!-- Mes y año -->
                                    <div class="text-xs font-semibold text-gray-700">{{ getMonthName(cuenta.fecha_vencimiento) }}</div>
                                    <div class="text-[10px] text-gray-500">{{ getYear(cuenta.fecha_vencimiento) }}</div>
                                    
                                    <!-- Monto -->
                                    <div :class="['text-xs font-medium mt-1', cuenta.estado === 'pagado' ? 'text-green-600' : 'text-gray-600']">
                                        {{ formatCurrency(cuenta.monto_total).replace('MX$', '$') }}
                                    </div>
                                    
                                    <!-- Tipo -->
                                    <div v-if="cuenta.notas !== 'Mensualidad'" class="text-[10px] text-purple-600 mt-0.5">
                                        {{ cuenta.notas }}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Leyenda -->
                            <div class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap gap-4 text-xs">
                                <div class="flex items-center gap-1">
                                    <div class="w-4 h-4 bg-green-500 rounded-full flex items-center justify-center text-white text-[10px]">✓</div>
                                    <span class="text-gray-600">Pagado</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <div class="w-4 h-4 bg-red-500 rounded-full flex items-center justify-center text-white text-[10px]">!</div>
                                    <span class="text-gray-600">Vencido</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <div class="w-4 h-4 bg-orange-400 rounded-full flex items-center justify-center text-white text-[10px]">⌛</div>
                                    <span class="text-gray-600">Próximo (7 días)</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <div class="w-4 h-4 bg-gray-300 rounded-full flex items-center justify-center text-gray-600 text-[10px]">○</div>
                                    <span class="text-gray-600">Pendiente</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Observaciones -->
                    <div v-if="renta.observaciones" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="bg-gradient-to-r from-gray-500 to-gray-600 px-4 py-3">
                            <h2 class="text-white font-semibold">Observaciones</h2>
                        </div>
                        <div class="p-4">
                            <p class="text-gray-700 whitespace-pre-line">{{ renta.observaciones }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Pago -->
    <div v-if="showPagoModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showPagoModal = false">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4">
                <h3 class="text-lg font-semibold text-white">Registrar Pago</h3>
            </div>
            
            <div class="p-6 space-y-4">
                <div class="bg-white rounded-lg p-4">
                    <div class="flex justify-between mb-2">
                        <span class="text-sm text-gray-600">Vencimiento:</span>
                        <span class="font-medium">{{ formatDate(cuentaSeleccionada?.fecha_vencimiento) }}</span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm text-gray-600">Concepto:</span>
                        <span class="font-medium">{{ cuentaSeleccionada?.notas }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Monto Pendiente:</span>
                        <span class="font-bold text-lg text-orange-600">{{ formatCurrency(cuentaSeleccionada?.monto_pendiente) }}</span>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Monto a Pagar</label>
                    <input 
                        v-model="montoPago" 
                        type="number" 
                        step="0.01" 
                        :max="cuentaSeleccionada?.monto_pendiente"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    />
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notas (opcional)</label>
                    <textarea 
                        v-model="notasPago" 
                        rows="2" 
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500"
                        placeholder="Referencia, método de pago, etc."
                    ></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Depositar a Cuenta Bancaria
                        <span class="text-gray-400 font-normal">(opcional)</span>
                    </label>
                    <select 
                        v-model="cuentaBancariaId" 
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    >
                        <option value="">Sin depositar a banco</option>
                        <option 
                            v-for="cuenta in cuentasBancarias" 
                            :key="cuenta.id" 
                            :value="cuenta.id"
                        >
                            {{ cuenta.banco }} - {{ cuenta.nombre }}
                        </option>
                    </select>
                    <p v-if="cuentaBancariaId" class="mt-1 text-xs text-green-600">
                        ✓ Se registrará un depósito en esta cuenta
                    </p>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 px-6 py-4 bg-white border-t">
                <button @click="showPagoModal = false" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                    Cancelar
                </button>
                <button @click="confirmarPago" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    Registrar Pago
                </button>
            </div>
        </div>
    </div>
</template>

