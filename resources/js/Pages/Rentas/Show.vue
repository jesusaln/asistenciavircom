<script setup>
import { useFormatters } from '@/Composables/useFormatters';
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
const metodoPago = ref('');

// Formateo de moneda
const { formatCurrency } = useFormatters();

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
        'activo': 'bg-emerald-100 text-emerald-800 dark:text-emerald-200 border-emerald-200 dark:border-emerald-800/30',
        'proximo_vencimiento': 'bg-brand-100 text-brand-800 border-orange-200',
        'vencido': 'bg-rose-100 text-rose-800 dark:text-rose-200 border-rose-200 dark:border-rose-800/30',
        'moroso': 'bg-rose-200 text-rose-900 border-rose-300',
        'suspendido': 'bg-brand-100 text-brand-800 dark:text-brand-200 border-brand-200 dark:border-brand-800/30',
        'finalizado': 'bg-slate-100 text-slate-500 border-slate-200',
        'cancelado': 'bg-red-100 text-red-700 border-red-200',
    };
    return classes[props.renta.estado] || 'bg-slate-100 text-slate-500';
});

const estadoLabels = {
    'activo': 'Activo',
    'proximo_vencimiento': 'Próximo Vencimiento',
    'vencido': 'Vencido',
    'moroso': 'Moroso',
    'suspendido': 'Suspendido',
    'finalizado': 'Finalizado',
    'borrador': 'Borrador',
    'cancelado': 'Cancelado',
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
        return { icon: '✓', color: 'bg-brand-500', label: 'Pagado', textColor: 'text-white' };
    }
    const hoy = new Date();
    const venc = new Date(cuenta.fecha_vencimiento);
    if (venc < hoy) {
        return { icon: '!', color: 'bg-brand-500', label: 'Vencido', textColor: 'text-white' };
    }
    // Próximo a vencer (7 días)
    const diffDays = Math.ceil((venc - hoy) / (1000 * 60 * 60 * 24));
    if (diffDays <= 7) {
        return { icon: '⌛', color: 'bg-orange-400', label: 'Próximo', textColor: 'text-white' };
    }
    return { icon: '○', color: 'bg-slate-300', label: 'Pendiente', textColor: 'text-slate-500' };
};

// Abrir modal de pago
const abrirModalPago = (cuenta) => {
    cuentaSeleccionada.value = cuenta;
    montoPago.value = cuenta.monto_pendiente;
    notasPago.value = '';
    cuentaBancariaId.value = '';
    metodoPago.value = '';
    showPagoModal.value = true;
};

const requiereCuentaBancaria = computed(() => {
    return ['transferencia', 'cheque', 'tarjeta', 'tarjeta_credito', 'tarjeta_debito'].includes(metodoPago.value);
});

const puedeConfirmarPago = computed(() => {
    const monto = parseFloat(montoPago.value);
    if (!monto || monto <= 0) return false;
    if (!metodoPago.value) return false;
    if (requiereCuentaBancaria.value && !cuentaBancariaId.value) return false;
    return true;
});

// Confirmar pago
const confirmarPago = async () => {
    const monto = parseFloat(montoPago.value);
    if (!monto || monto <= 0) {
        notyf.error('Ingresa un monto válido');
        return;
    }
    if (!metodoPago.value) {
        notyf.error('Selecciona un método de pago');
        return;
    }
    if (requiereCuentaBancaria.value && !cuentaBancariaId.value) {
        notyf.error('Selecciona una cuenta bancaria');
        return;
    }

    try {
        const response = await fetch(route('cuentas-por-cobrar.registrar-pago', cuentaSeleccionada.value.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                monto: monto,
                notas: notasPago.value || null,
                cuenta_bancaria_id: cuentaBancariaId.value || null,
                metodo_pago: metodoPago.value
            })
        });

        if (response.ok) {
            notyf.success('Pago registrado correctamente');
            showPagoModal.value = false;
            router.reload();
        } else {
            let errorMsg = 'Error al registrar el pago';
            try {
                const data = await response.json();
                errorMsg = data.message || data.error || (data.errors ? Object.values(data.errors).flat()[0] : errorMsg);
            } catch (parseErr) {
                errorMsg = `Error ${response.status} al registrar el pago`;
            }
            notyf.error(errorMsg);
        }
    } catch (error) {
        notyf.error('Error de conexión');
    }
};

const cancelarRenta = async () => {
    const confirmed = await Swal.fire({
        title: '¿Cancelar renta?',
        text: 'Las cuentas pendientes se cancelarán y los equipos quedarán disponibles.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'No',
    });
    if (!confirmed.isConfirmed) return;
    try {
        await router.post(route('rentas.cancelar', props.renta.id));
        router.reload();
    } catch (e) {
        Swal.fire('Error', 'No se pudo cancelar la renta', 'error');
    }
};
</script>

<template>
    <Head :title="`Renta ${renta.numero_contrato}`" />
    
    <div class="min-h-screen bg-[var(--ui-surface)] py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            
            <!-- Cabecera -->
            <div class="mb-8">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <Link :href="route('rentas.index')" class="text-slate-500 hover:text-slate-700">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </Link>
                            <h1 class="text-2xl font-bold text-slate-900">{{ renta.numero_contrato }}</h1>
                            <span :class="estadoClasses" class="px-3 py-1 rounded-full text-sm font-medium border">
                                {{ estadoLabels[renta.estado] || renta.estado }}
                            </span>
                        </div>
                        <p class="text-slate-500">
                            <span class="font-medium">{{ renta.cliente?.nombre_razon_social }}</span>
                            · Contrato de {{ renta.meses_duracion }} meses
                        </p>
                    </div>
                    
                    <div class="flex gap-2">
                        <Link :href="route('rentas.edit', renta.id)" class="px-4 py-2 bg-brand-500 text-white rounded-xl hover:bg-brand-600 transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Editar
                        </Link>
                        <a :href="route('rentas.contrato', renta.id)" target="_blank" class="px-4 py-2 bg-rose-600 text-white rounded-xl hover:bg-rose-700 transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Contrato PDF
                        </a>
                        <button v-if="renta.estado !== 'cancelado'" @click="cancelarRenta" class="px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Grid principal -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Columna izquierda: Info y Equipos -->
                <div class="lg:col-span-1 space-y-6">
                    
                    <!-- Información del Contrato -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                        <div class="bg-gradient-to-r from-brand-500 to-brand-600 px-4 py-3">
                            <h2 class="text-white font-semibold flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Información del Contrato
                            </h2>
                        </div>
                        <div class="p-4 space-y-3">
                            <div class="flex justify-between">
                                <span class="text-slate-500 text-sm">Fecha Inicio</span>
                                <span class="font-medium">{{ formatDate(renta.fecha_inicio) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500 text-sm">Fecha Fin</span>
                                <span class="font-medium">{{ formatDate(renta.fecha_fin) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500 text-sm">Día de Pago</span>
                                <span class="font-medium">Día {{ renta.dia_pago }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500 text-sm">Forma de Pago</span>
                                <span class="font-medium capitalize">{{ renta.forma_pago }}</span>
                            </div>
                            <hr class="my-2">
                            <div class="flex justify-between">
                                <span class="text-slate-500 text-sm">Mensualidad</span>
                                <span class="font-bold text-lg text-orange-600">{{ formatCurrency(renta.monto_mensual) }}</span>
                            </div>
                            <div v-if="renta.deposito_garantia > 0" class="flex justify-between">
                                <span class="text-slate-500 text-sm">Depósito</span>
                                <span class="font-medium">{{ formatCurrency(renta.deposito_garantia) }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Equipos Rentados -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                        <div class="bg-gradient-to-r from-brand-500 to-brand-600 px-4 py-3">
                            <h2 class="text-white font-semibold flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                                Equipos Rentados ({{ renta.equipos?.length || 0 }})
                            </h2>
                        </div>
                        <div class="divide-y divide-slate-100">
                            <div v-for="equipo in renta.equipos" :key="equipo.id" class="p-4 hover:bg-white">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="font-medium text-slate-900">{{ equipo.nombre }}</p>
                                        <p class="text-sm text-slate-500">{{ equipo.codigo }} · {{ equipo.marca }} {{ equipo.modelo }}</p>
                                    </div>
                                    <span class="text-sm font-semibold text-blue-600">
                                        {{ formatCurrency(equipo.pivot?.precio_mensual || equipo.precio_renta_mensual) }}/mes
                                    </span>
                                </div>
                            </div>
                            <div v-if="!renta.equipos?.length" class="p-4 text-center text-slate-500">
                                No hay equipos asociados
                            </div>
                        </div>
                    </div>

                    <!-- Expediente Digital (NUEVO) -->
                    <div v-if="renta.firmado_at" class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                        <div class="bg-gradient-to-r from-brand-500 to-brand-600 px-4 py-3">
                            <h2 class="text-white font-semibold flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Expediente Digital
                            </h2>
                        </div>
                        <div class="p-4 space-y-6">
                            <!-- Firma -->
                            <div class="p-3 bg-[var(--ui-surface)] rounded-xl border border-slate-200">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-wide mb-2">Firma Digital</p>
                                <img :src="renta.firma_digital" class="h-20 object-contain mx-auto bg-white border rounded-xl p-1" alt="Firma">
                                <p class="text-[10px] text-center text-slate-500 mt-2">Firmado por: {{ renta.firmado_nombre }}<br>{{ formatDate(renta.firmado_at) }} IP: {{ renta.firmado_ip }}</p>
                            </div>

                            <!-- Documentos -->
                            <div class="grid grid-cols-2 gap-3">
                                <a v-if="renta.ine_frontal" :href="'/storage/' + renta.ine_frontal" target="_blank" class="flex flex-col items-center p-3 border rounded-xl hover:bg-slate-50 transition-all group">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-2">🪪</div>
                                    <span class="text-[10px] font-bold text-slate-500 group-hover:text-emerald-800 dark:text-emerald-200 dark:text-emerald-200">INE Frontal</span>
                                </a>
                                <a v-if="renta.ine_trasera" :href="'/storage/' + renta.ine_trasera" target="_blank" class="flex flex-col items-center p-3 border rounded-xl hover:bg-slate-50 transition-all group">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-2">🪪</div>
                                    <span class="text-[10px] font-bold text-slate-500 group-hover:text-emerald-800 dark:text-emerald-200 dark:text-emerald-200">INE Trasera</span>
                                </a>
                                <a v-if="renta.comprobante_domicilio" :href="'/storage/' + renta.comprobante_domicilio" target="_blank" class="flex flex-col items-center p-3 border rounded-xl hover:bg-slate-50 transition-all group">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-2">🏠</div>
                                    <span class="text-[10px] font-bold text-slate-500 group-hover:text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 text-center">Comprobante</span>
                                </a>
                                <a v-if="renta.solicitud_renta" :href="'/storage/' + renta.solicitud_renta" target="_blank" class="flex flex-col items-center p-3 border rounded-xl hover:bg-slate-50 transition-all group">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-2">📄</div>
                                    <span class="text-[10px] font-bold text-slate-500 group-hover:text-emerald-800 dark:text-emerald-200 dark:text-emerald-200">Solicitud</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Columna derecha: Cobranzas -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Resumen de Cobranza -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                        <div class="bg-gradient-to-r from-brand-500 to-brand-600 px-4 py-3">
                            <h2 class="text-white font-semibold flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Estado de Cobranza
                                <span class="ml-auto text-sm">{{ saludContrato.icon }} {{ saludContrato.label }}</span>
                            </h2>
                        </div>
                        <div class="p-4">
                            <!-- Estadísticas -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                <div class="text-center p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl">
                                    <div class="text-2xl font-bold text-emerald-600">{{ cobranzaStats.pagadas }}</div>
                                    <div class="text-xs text-emerald-800 dark:text-emerald-200 dark:text-emerald-200">Pagadas</div>
                                </div>
                                <div class="text-center p-3 bg-white rounded-xl">
                                    <div class="text-2xl font-bold text-slate-500">{{ cobranzaStats.pendientes }}</div>
                                    <div class="text-xs text-slate-500">Pendientes</div>
                                </div>
                                <div class="text-center p-3 bg-rose-50 dark:bg-rose-900/20 rounded-xl">
                                    <div class="text-2xl font-bold text-rose-600">{{ cobranzaStats.vencidas }}</div>
                                    <div class="text-xs text-rose-800 dark:text-rose-200 dark:text-rose-200">Vencidas</div>
                                </div>
                                <div class="text-center p-3 bg-orange-50 rounded-xl">
                                    <div class="text-lg font-bold text-orange-600">{{ formatCurrency(cobranzaStats.totalPendiente) }}</div>
                                    <div class="text-xs text-orange-700">Total Pendiente</div>
                                </div>
                            </div>
                            
                            <!-- Barra de progreso -->
                            <div class="mb-4">
                                <div class="flex justify-between text-sm text-slate-500 mb-1">
                                    <span>Progreso del Contrato</span>
                                    <span>{{ cobranzaStats.porcentajePagado }}% ({{ cobranzaStats.pagadas }}/{{ cobranzaStats.total }})</span>
                                </div>
                                <div class="w-full bg-slate-200 rounded-full h-3">
                                    <div class="bg-brand-500 h-3 rounded-full transition-all duration-500" :style="{ width: cobranzaStats.porcentajePagado + '%' }"></div>
                                </div>
                            </div>
                            
                            <!-- Próximo vencimiento -->
                            <div v-if="cobranzaStats.proximoVencimiento" class="bg-orange-50 border border-orange-200 rounded-xl p-3 flex items-center justify-between">
                                <div>
                                    <span class="text-sm text-orange-700">Próximo vencimiento:</span>
                                    <span class="font-semibold text-orange-800 ml-2">{{ formatDate(cobranzaStats.proximoVencimiento.fecha_vencimiento) }}</span>
                                </div>
                                <button @click="abrirModalPago(cobranzaStats.proximoVencimiento)" class="px-3 py-1.5 bg-brand-500 text-white text-sm rounded-xl hover:bg-brand-600 transition-colors">
                                    Registrar Pago
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Timeline de Cobranzas -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                        <div class="bg-gradient-to-r from-brand-500 to-brand-600 px-4 py-3">
                            <h2 class="text-white font-semibold flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                        'relative p-2 rounded-xl border-2 text-center transition-all',
                                        cuenta.estado === 'pagado' 
                                            ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-300' 
                                            : getCuentaStatus(cuenta).label === 'Vencido'
                                                ? 'bg-rose-50 dark:bg-rose-900/20 border-rose-300 cursor-pointer hover:border-brand-500'
                                                : getCuentaStatus(cuenta).label === 'Próximo'
                                                    ? 'bg-orange-50 border-orange-300 cursor-pointer hover:border-brand-500'
                                                    : 'bg-white border-slate-200 cursor-pointer hover:border-brand-500'
                                    ]"
                                >
                                    <!-- Indicador de estado -->
                                    <div :class="[getCuentaStatus(cuenta).color, 'w-10 h-10 rounded-full mx-auto mb-1 flex items-center justify-center text-xs font-bold', getCuentaStatus(cuenta).textColor]">
                                        {{ getCuentaStatus(cuenta).icon }}
                                    </div>
                                    
                                    <!-- Mes y año -->
                                    <div class="text-xs font-semibold text-slate-700">{{ getMonthName(cuenta.fecha_vencimiento) }}</div>
                                    <div class="text-[10px] text-slate-500">{{ getYear(cuenta.fecha_vencimiento) }}</div>
                                    
                                    <!-- Monto -->
                                    <div :class="['text-xs font-medium mt-1', cuenta.estado === 'pagado' ? 'text-emerald-600' : 'text-slate-500']">
                                        {{ formatCurrency(cuenta.monto_total).replace('MX$', '$') }}
                                    </div>
                                    
                                    <!-- Tipo -->
                                    <div v-if="cuenta.notas !== 'Mensualidad'" class="text-[10px] text-purple-600 mt-0.5">
                                        {{ cuenta.notas }}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Leyenda -->
                            <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap gap-4 text-xs">
                                <div class="flex items-center gap-1">
                                    <div class="w-4 h-4 bg-brand-500 rounded-full flex items-center justify-center text-white text-[10px]">✓</div>
                                    <span class="text-slate-500">Pagado</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <div class="w-4 h-4 bg-brand-500 rounded-full flex items-center justify-center text-white text-[10px]">!</div>
                                    <span class="text-slate-500">Vencido</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <div class="w-4 h-4 bg-orange-400 rounded-full flex items-center justify-center text-white text-[10px]">⌛</div>
                                    <span class="text-slate-500">Próximo (7 días)</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <div class="w-4 h-4 bg-slate-300 rounded-full flex items-center justify-center text-slate-500 text-[10px]">○</div>
                                    <span class="text-slate-500">Pendiente</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Observaciones -->
                    <div v-if="renta.observaciones" class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                        <div class="bg-gradient-to-r from-brand-500 to-brand-600 px-4 py-3">
                            <h2 class="text-white font-semibold">Observaciones</h2>
                        </div>
                        <div class="p-4">
                            <p class="text-slate-700 whitespace-pre-line">{{ renta.observaciones }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Pago -->
    <div v-if="showPagoModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showPagoModal = false">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
            <div class="bg-gradient-to-r from-brand-500 to-brand-600 px-6 py-4">
                <h3 class="text-lg font-semibold text-white">Registrar Pago</h3>
            </div>
            
            <div class="p-6 space-y-6">
                <div class="bg-white rounded-xl p-4">
                    <div class="flex justify-between mb-2">
                        <span class="text-sm text-slate-500">Vencimiento:</span>
                        <span class="font-medium">{{ formatDate(cuentaSeleccionada?.fecha_vencimiento) }}</span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm text-slate-500">Concepto:</span>
                        <span class="font-medium">{{ cuentaSeleccionada?.notas }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-500">Monto Pendiente:</span>
                        <span class="font-bold text-lg text-orange-600">{{ formatCurrency(cuentaSeleccionada?.monto_pendiente) }}</span>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Monto a Pagar</label>
                    <input 
                        v-model="montoPago" 
                        type="number" 
                        step="0.01" 
                        :max="cuentaSeleccionada?.monto_pendiente"
                        class="w-full border border-slate-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-brand-500 focus:border-emerald-500"
                    />
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Notas (opcional)</label>
                    <textarea
                        v-model="notasPago"
                        rows="2"
                        class="w-full border border-slate-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-brand-500 focus:border-emerald-500"
                        placeholder="Referencia, método de pago, etc."
                    ></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Método de Pago <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model="metodoPago"
                        class="w-full border border-slate-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-brand-500 focus:border-emerald-500"
                        required
                    >
                        <option value="">Seleccionar método...</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="cheque">Cheque</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="tarjeta_credito">Tarjeta de Crédito</option>
                        <option value="tarjeta_debito">Tarjeta de Débito</option>
                        <option value="otros">Otros</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Depositar a Cuenta Bancaria
                        <span v-if="requiereCuentaBancaria" class="text-red-500">*</span>
                        <span v-else class="text-slate-400 font-normal">(opcional)</span>
                    </label>
                    <select
                        v-model="cuentaBancariaId"
                        class="w-full border border-slate-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-brand-500 focus:border-emerald-500"
                        :required="requiereCuentaBancaria"
                    >
                        <option v-if="!requiereCuentaBancaria" value="">Sin depositar a banco</option>
                        <option v-else value="">Seleccionar cuenta bancaria...</option>
                        <option
                            v-for="cuenta in cuentasBancarias"
                            :key="cuenta.id"
                            :value="cuenta.id"
                        >
                            {{ cuenta.banco }} - {{ cuenta.nombre }}
                        </option>
                    </select>
                    <p v-if="cuentaBancariaId" class="mt-1 text-xs text-emerald-600">
                        ✓ Se registrará un depósito en esta cuenta
                    </p>
                </div>
            </div>

            <div class="flex justify-end gap-3 px-6 py-4 bg-white border-t">
                <button @click="showPagoModal = false" class="px-4 py-2 bg-slate-300 text-slate-700 rounded-xl hover:bg-slate-400 transition-colors">
                    Cancelar
                </button>
                <button @click="confirmarPago" :disabled="!puedeConfirmarPago" class="px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    Registrar Pago
                </button>
            </div>
        </div>
    </div>
</template>

