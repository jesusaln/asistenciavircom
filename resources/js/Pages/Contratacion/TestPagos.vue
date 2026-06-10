<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from '@/Utils/Swal';

// Props recibidos desde el controlador (si los hubiera)
const props = defineProps({
    poliza: Object,
    gateways: Object,
});

const processing = ref(false);
const error = ref(null);
const logs = ref([]);

const addLog = (msg, type = 'info') => {
    logs.value.unshift({
        id: Date.now(),
        time: new Date().toLocaleTimeString(),
        msg,
        type
    });
};

const handlePayPal = async () => {
    if (!props.poliza) return;
    processing.value = true;
    error.value = null;
    addLog('Iniciando pago con PayPal...');

    try {
        const response = await axios.post(route('pago.poliza.paypal.crear'), {
            poliza_id: props.poliza.id
        });

        addLog(`Orden creada: ${response.data.order_id}`, 'success');
        addLog(`Redirigiendo a sandbox: ${response.data.approve_url}`, 'warning');
        
        // Abrir en nueva ventana para no perder los logs
        window.open(response.data.approve_url, '_blank');
        
        addLog('Simula el pago en la ventana emergente.', 'info');
        
        // Simulación de captura manual (en producción esto es automático tras el redirect)
        const confirmResult = await Swal.fire({
            title: 'Capturar pago',
            text: '¿Ya aprobaste el pago en PayPal Sandbox? Confirma para CAPTURAR el pago.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, capturar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#3b82f6',
        });

        if (confirmResult.isConfirmed) {
            addLog('Capturando pago...');
            const captureResponse = await axios.post(route('pago.poliza.paypal.capturar'), {
               order_id: response.data.order_id,
               poliza_id: props.poliza.id
            });
            addLog('¡Pago capturado exitosamente!', 'success');
            console.log(captureResponse.data);
            await Swal.fire({
                title: 'Pago completado',
                text: 'Pago completado exitosamente',
                icon: 'success',
                confirmButtonText: 'Aceptar',
                confirmButtonColor: '#3b82f6',
            });
            window.location.reload();
        }

    } catch (e) {
        console.error(e);
        error.value = e.response?.data?.message || e.message;
        addLog(`Error: ${error.value}`, 'error');
    } finally {
        processing.value = false;
    }
};

const handleMercadoPago = async () => {
    if (!props.poliza) return;
    processing.value = true;
    error.value = null;
    addLog('Creando preferencia MercadoPago...');

    try {
        const response = await axios.post(route('pago.poliza.mercadopago.crear'), {
            poliza_id: props.poliza.id
        });

        addLog(`Preferencia creada: ${response.data.preference_id}`, 'success');
        
        const url = response.data.sandbox_init_point;
        addLog(`Abriendo Sandbox: ${url}`, 'warning');
        window.open(url, '_blank');

    } catch (e) {
        console.error(e);
        error.value = e.response?.data?.message || e.message;
        addLog(`Error: ${error.value}`, 'error');
    } finally {
        processing.value = false;
    }
};

const handleStripe = async () => {
    if (!props.poliza) return;
    processing.value = true;
    error.value = null;
    addLog('Creando sesión de Stripe Checkout...');

    try {
        const response = await axios.post(route('pago.poliza.stripe.checkout'), {
            poliza_id: props.poliza.id
        });

        addLog(`Sesión creada: ${response.data.session_id}`, 'success');
        
        const url = response.data.checkout_url;
        addLog(`Redirigiendo a Stripe: ${url}`, 'warning');
        window.location.href = url;

    } catch (e) {
        console.error(e);
        error.value = e.response?.data?.message || e.message;
        addLog(`Error: ${error.value}`, 'error');
    } finally {
        processing.value = false;
    }
};

</script>

<template>
    <Head title="Test de Pagos" />

    <div class="min-h-screen bg-slate-100 p-8">
        <div class="w-full space-y-6">
            
            <!-- Header -->
            <div class="bg-white rounded-2xl shadow-xl p-6">
                <h1 class="text-2xl font-bold text-slate-800">🛠️ Test de Pasarelas de Pago</h1>
                <p class="text-slate-500 mt-2">
                    Herramienta para desarrolladores. Simula pagos para pólizas en entornos Sandbox.
                </p>
                <div v-if="!poliza" class="mt-4 p-4 bg-brand-50 dark:bg-brand-900/20 border-l-4 border-brand-400 text-brand-800 dark:text-brand-200 dark:text-amber-200">
                    ⚠️ No se encontró ninguna póliza pendiente de pago. <br>
                    Crea una contratación nueva primero para probar.
                </div>
            </div>

            <!-- Detalles de la Póliza -->
            <div v-if="poliza" class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <div class="bg-white px-6 py-4 border-b">
                    <h3 class="text-lg font-medium text-slate-900">Póliza Pendiente #{{ poliza.id }}</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <span class="block text-sm text-slate-500">Cliente</span>
                        <span class="font-medium">{{ poliza.cliente?.nombre_comercial || 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="block text-sm text-slate-500">Plan</span>
                        <span class="font-medium">{{ poliza.nombre }}</span>
                    </div>
                    <div>
                        <span class="block text-sm text-slate-500">Monto Mensual/Anual</span>
                        <span class="font-medium">${{ poliza.monto_mensual }}</span>
                    </div>
                    <div>
                        <span class="block text-sm text-slate-500">Estado</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium bg-rose-100 text-rose-800 dark:text-rose-200">
                            {{ poliza.estado }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div v-if="poliza" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <!-- PayPal -->
                <div class="bg-white rounded-2xl shadow-xl p-6 border-t-4 border-blue-500">
                    <h3 class="font-bold text-lg mb-4 flex items-center">
                        <span class="text-2xl mr-2">🅿️</span> PayPal
                    </h3>
                    <p class="text-sm text-slate-500 mb-4 h-12">
                        Crea una orden y redirige a sandbox.paypal.com
                    </p>
                    <button 
                        @click="handlePayPal"
                        :disabled="processing"
                        class="w-full bg-sky-600 hover:bg-sky-700 text-white font-bold py-2 px-4 rounded-xl transition disabled:opacity-50">
                        Probar PayPal
                    </button>
                    <div class="mt-2 text-xs text-slate-400 text-center">
                        Requiere Client ID & Secret
                    </div>
                </div>

                <!-- MercadoPago -->
                <div class="bg-white rounded-2xl shadow-xl p-6 border-t-4 border-blue-400">
                    <h3 class="font-bold text-lg mb-4 flex items-center">
                        <span class="text-2xl mr-2">🤝</span> MercadoPago
                    </h3>
                    <p class="text-sm text-slate-500 mb-4 h-12">
                        Crea preferencia y abre Checkout Pro (Sandbox).
                    </p>
                    <button 
                        @click="handleMercadoPago"
                        :disabled="processing"
                        class="w-full bg-brand-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-xl transition disabled:opacity-50">
                        Probar MP
                    </button>
                    <div class="mt-2 text-xs text-slate-400 text-center">
                        Requiere Access Token
                    </div>
                </div>

                <!-- Stripe -->
                <div class="bg-white rounded-2xl shadow-xl p-6 border-t-4 border-purple-500">
                    <h3 class="font-bold text-lg mb-4 flex items-center">
                        <span class="text-2xl mr-2">💳</span> Stripe
                    </h3>
                    <p class="text-sm text-slate-500 mb-4 h-12">
                        Crea Checkout Session y redirige a formulario alojado.
                    </p>
                    <button 
                        @click="handleStripe"
                        :disabled="processing"
                        class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-xl transition disabled:opacity-50">
                        Probar Stripe
                    </button>
                    <div class="mt-2 text-xs text-slate-400 text-center">
                        Requiere Secret Key
                    </div>
                </div>
            </div>

            <!-- Consola de Logs -->
            <div class="bg-slate-900 rounded-2xl shadow-xl p-4 font-mono text-sm h-64 overflow-y-auto custom-scrollbar">
                <div class="text-slate-400 mb-2 pb-2 border-b border-slate-700 flex justify-between">
                    <span>> Consola de depuración</span>
                    <button @click="logs = []" class="hover:text-white">Limpiar</button>
                </div>
                <div v-if="logs.length === 0" class="text-slate-500 italic">
                    Esperando acciones...
                </div>
                <div v-for="log in logs" :key="log.id" class="mb-1">
                    <span class="text-slate-500">[{{ log.time }}]</span>
                    <span :class="{
                        'text-emerald-400': log.type === 'success',
                        'text-rose-400': log.type === 'error',
                        'text-amber-400': log.type === 'warning',
                        'text-blue-300': log.type === 'info'
                    }"> {{ log.msg }}</span>
                </div>
            </div>

        </div>
    </div>
</template>
