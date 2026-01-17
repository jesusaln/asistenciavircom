<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import PublicFooter from '@/Components/PublicFooter.vue';

const props = defineProps({
    plan: Object,
    poliza: Object,
    empresa: Object,
    venta: Object,
    pending: Boolean,
    message: String,
});

const cssVars = computed(() => ({
    '--color-primary': props.empresa?.color_principal || '#3b82f6',
    '--color-primary-soft': (props.empresa?.color_principal || '#3b82f6') + '15',
}));

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value || 0);
};
</script>

<template>
    <Head :title="pending ? 'Pago en Proceso' : '¡Contratación Exitosa!'" />

    <div class="min-h-screen bg-white flex flex-col" :style="cssVars">
        <PublicNavbar :empresa="empresa" />
        
        <div class="flex-grow flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl shadow-xl max-w-lg w-full overflow-hidden text-center p-8 md:p-12">
                
                <!-- Icono de Estado -->
                <div v-if="pending" class="w-24 h-24 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-6 animate-pulse">
                    <svg class="w-12 h-12 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div v-else class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>

                <!-- Título y Mensaje -->
                <h1 class="text-3xl font-extrabold text-gray-900 mb-4">
                    {{ pending ? '¡Pago en Proceso!' : '¡Gracias por tu confianza!' }}
                </h1>
                
                <p class="text-gray-600 mb-8 text-lg">
                    <template v-if="pending">
                        Tu pago está siendo procesado.<br>
                        Te notificaremos por correo cuando se confirme.
                    </template>
                    <template v-else>
                        Has contratado exitosamente el plan <span class="font-bold text-gray-800">{{ poliza?.nombre || plan?.nombre }}</span>.
                        <br>
                        Hemos enviado los detalles de acceso y tu factura a tu correo electrónico.
                    </template>
                </p>

                <!-- Información de la Póliza -->
                <div v-if="poliza" class="bg-[var(--color-primary-soft)] rounded-2xl p-6 mb-6 border border-[var(--color-primary)]/20 text-left">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-black text-[var(--color-primary)] uppercase tracking-widest text-xs">Tu Póliza</h3>
                        <span 
                            :class="[
                                'text-[10px] font-black px-2 py-1 rounded',
                                poliza.estado === 'activa' ? 'bg-green-100 text-green-700' :
                                poliza.estado === 'pendiente_pago' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700'
                            ]"
                        >
                            {{ poliza.estado === 'activa' ? 'ACTIVA' : poliza.estado === 'pendiente_pago' ? 'PENDIENTE' : poliza.estado?.toUpperCase() }}
                        </span>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Plan:</span>
                            <span class="font-bold text-gray-900">{{ poliza.nombre }}</span>
                        </div>
                        <div v-if="poliza.fecha_inicio" class="flex justify-between">
                            <span class="text-gray-500">Vigencia:</span>
                            <span class="font-bold text-gray-900">
                                {{ new Date(poliza.fecha_inicio).toLocaleDateString('es-MX') }} - {{ new Date(poliza.fecha_fin).toLocaleDateString('es-MX') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Resumen de Venta -->
                <div v-if="venta" class="bg-white rounded-2xl p-6 mb-8 border border-gray-100 text-left">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-black text-gray-900 uppercase tracking-widest text-xs">Resumen de Venta</h3>
                        <span 
                            :class="[
                                'text-[10px] font-black px-2 py-1 rounded uppercase',
                                venta.estado === 'pagado' ? 'bg-green-100 text-green-700' :
                                venta.estado === 'pendiente' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700'
                            ]"
                        >
                            {{ venta.estado }}
                        </span>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Folio:</span>
                            <span class="font-bold text-gray-900">{{ venta.folio || 'S/F' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Fecha:</span>
                            <span class="font-bold text-gray-900">{{ new Date(venta.fecha).toLocaleDateString('es-MX') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Método:</span>
                            <span class="font-bold text-gray-900 capitalize">
                                {{ venta.metodo_pago === 'tarjeta' ? '💳 Tarjeta' : venta.metodo_pago === 'paypal' ? '🅿️ PayPal' : venta.metodo_pago === 'mercadopago' ? '🤝 MercadoPago' : venta.metodo_pago }}
                            </span>
                        </div>
                        <div class="flex justify-between border-t border-gray-200 pt-2 mt-2">
                            <span class="text-gray-900 font-bold">Total:</span>
                            <span class="font-black text-[var(--color-primary)] text-lg">
                                {{ formatCurrency(venta.total) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="space-y-3">
                    <a v-if="venta && venta.estado === 'pagado'" :href="route('ventas.pdf.public', venta.id)" target="_blank" class="block w-full py-4 bg-[var(--color-primary)] text-white font-black text-sm uppercase tracking-widest rounded-xl shadow-lg hover:opacity-90 transition flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        Descargar Comprobante PDF
                    </a>
                    
                    <Link :href="route('portal.dashboard')" class="block w-full py-4 border-2 border-[var(--color-primary)] text-[var(--color-primary)] font-black text-sm uppercase tracking-widest rounded-xl hover:bg-[var(--color-primary-soft)] transition flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        Acceder a mi Portal
                    </Link>
                    
                    <Link href="/" class="block w-full py-3 text-gray-400 hover:text-gray-600 font-bold text-xs">
                        Volver al Inicio
                    </Link>
                </div>
            </div>
        </div>

        <PublicFooter :empresa="empresa" />
    </div>
</template>
