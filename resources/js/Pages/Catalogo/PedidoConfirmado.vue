<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'
import PublicNavbar from '@/Components/PublicNavbar.vue'
import PublicFooter from '@/Components/PublicFooter.vue'

const props = defineProps({
    pedido: Object,
    empresa: Object,
    cliente: Object,
    canLogin: Boolean
})

const page = usePage()
const empresaData = computed(() => ({
    ...page.props.empresa_config,
    ...props.empresa
}))

const cssVars = computed(() => ({
    '--color-primary': empresaData.value.color_principal || '#3b82f6',
    '--color-primary-soft': (empresaData.value.color_principal || '#3b82f6') + '15',
}))

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { 
        style: 'currency', 
        currency: 'MXN' 
    }).format(value || 0)
}

const steps = computed(() => {
    if (props.pedido.metodo_pago === 'transferencia') {
        return [
            { title: 'Realiza la transferencia', desc: 'Usa los datos bancarios que aparecen abajo.', icon: 'bank' },
            { title: 'Carga tu comprobante', desc: 'Envíanos el comprobante por WhatsApp o correo.', icon: 'upload' },
            { title: 'Validación', desc: 'Validaremos tu pago en menos de 24 horas hábiles.', icon: 'check' },
            { title: 'Preparación', desc: 'Una vez validado, prepararemos tu envío.', icon: 'box' }
        ]
    }
    if (props.pedido.metodo_pago === 'efectivo') {
        // Detectar si es a domicilio
        const esDomicilio = !props.pedido.direccion_envio?.tipo || props.pedido.direccion_envio.tipo !== 'recoger_en_tienda';

        if (esDomicilio) {
             return [
                { title: 'Prepara el Efectivo', desc: `Ten listo el monto exacto de ${formatCurrency(props.pedido.total)}.`, icon: 'cash' },
                { title: 'Espera tu Pedido', desc: 'Llevaremos el producto a tu domicilio.', icon: 'truck' },
                { title: 'Pago Contra Entrega', desc: 'Pagas al repartidor al recibir tu pedido.', icon: 'check' },
                { title: '¡Disfruta!', desc: 'Gracias por tu preferencia.', icon: 'star' }
            ]
        } else {
            return [
                { title: 'Acude a sucursal', desc: 'Visítanos en nuestra sucursal física.', icon: 'home' },
                { title: 'Menciona tu pedido', desc: `Indica el número de pedido #${props.pedido.numero_pedido}.`, icon: 'message' },
                { title: 'Realiza el pago', desc: 'Paga en caja con efectivo o tarjeta.', icon: 'cash' },
                { title: 'Entrega', desc: 'Recibe tus productos al momento (sujeto a stock local).', icon: 'box' }
            ]
        }
    }

    // Si el pedido está pendiente de pago (Mercado Pago o PayPal)
    if (props.pedido.estado === 'pendiente' && (props.pedido.metodo_pago === 'mercadopago' || props.pedido.metodo_pago === 'paypal')) {
        return [
            { title: 'Esperando Pago', desc: 'Completa tu transacción en el panel de pasarela de pago.', icon: 'clock' },
            { title: 'Pago Recibido', desc: 'Validaremos la transacción de forma inmediata.', icon: 'check' },
            { title: 'Preparación', desc: 'Estamos preparando tu paquete para salida.', icon: 'box' },
            { title: 'Envío', desc: 'En breve recibirás tu número de guía.', icon: 'truck' }
        ]
    }

    return [
        { title: 'Pago Recibido', desc: 'Hemos validado tu transacción con éxito.', icon: 'check' },
        { title: 'Preparación', desc: 'Estamos preparando tu paquete para salida.', icon: 'box' },
        { title: 'Envío', desc: 'En breve recibirás tu número de guía.', icon: 'truck' }
    ]
})

const metodoPagoLabel = computed(() => {
    const labels = {
        'mercadopago': 'Mercado Pago',
        'paypal': 'PayPal',
        'transferencia': 'Transferencia Bancaria',
        'efectivo': 'Efectivo en Sucursal',
        'credito': 'Crédito Comercial'
    }
    return labels[props.pedido.metodo_pago] || props.pedido.metodo_pago
})

const loadingPago = ref(false)
const errorPago = ref('')

const pagarPedido = async () => {
    loadingPago.value = true
    errorPago.value = ''
    try {
        if (props.pedido.metodo_pago === 'mercadopago') {
            const res = await axios.post(route('pago.mercadopago.crear'), {
                pedido_id: props.pedido.id
            })
            if (res.data.success && res.data.init_point) {
                window.location.href = res.data.init_point
            } else {
                throw new Error('No se pudo obtener la URL de Mercado Pago.')
            }
        } else if (props.pedido.metodo_pago === 'paypal') {
            const res = await axios.post(route('pago.paypal.crear'), {
                pedido_id: props.pedido.id
            })
            if (res.data.success && res.data.approve_url) {
                window.location.href = res.data.approve_url
            } else {
                throw new Error('No se pudo obtener la URL de PayPal.')
            }
        }
    } catch (e) {
        console.error(e)
        errorPago.value = e.response?.data?.message || e.message || 'Error al iniciar el pago.'
    } finally {
        loadingPago.value = false
    }
}

const handleWhatsAppClick = () => {
    if (!empresaData.value.whatsapp) return
    const phone = empresaData.value.whatsapp.replace(/\D/g, '')
    const text = encodeURIComponent(`Hola, acabo de realizar el pedido #${props.pedido.numero_pedido} y me gustaría dar seguimiento a mi pago por ${metodoPagoLabel.value}.`)
    window.open(`https://wa.me/${phone}?text=${text}`, '_blank')
}

// Facebook Pixel Event: Purchase
onMounted(() => {
    if (window.fbq) {
        window.fbq('track', 'Purchase', {
            content_ids: props.pedido.items.map(item => item.producto_id),
            content_type: 'product',
            value: props.pedido.total,
            currency: 'MXN',
            num_items: props.pedido.items.reduce((sum, item) => sum + item.cantidad, 0),
            order_id: props.pedido.numero_pedido
        });
    }
});
</script>

<template>
    <Head title="Pedido Confirmado" />

    <div class="min-h-screen bg-[var(--ui-surface)] flex flex-col font-sans" :style="cssVars">
        <PublicNavbar :empresa="empresaData" activeTab="tienda" />

        <main class="flex-grow w-full px-4 sm:px-6 lg:px-8 py-12 w-full">
            
            <!-- Encabezado de Éxito -->
            <div class="text-center mb-12">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 dark:bg-slate-800/20 text-emerald-600 dark:text-slate-400 mb-6 animate-bounce">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h1 class="text-4xl font-black text-slate-900 dark:text-white mb-2 uppercase tracking-wider">¡Pedido Recibido!</h1>
                <p class="text-xl text-slate-500 dark:text-slate-400 font-medium">Gracias por tu compra, <span class="text-slate-900 dark:text-white font-bold">{{ pedido.nombre }}</span></p>
                <div class="mt-4 inline-block bg-white dark:bg-slate-800 px-6 py-2 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm font-black text-[var(--color-primary)] text-lg">
                    PEDIDO #{{ pedido.numero_pedido }}
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-8 items-start">
                
                <!-- Columna: Pasos a seguir -->
                <div class="space-y-6">
                    <div class="bg-white dark:bg-slate-800 rounded-[2rem] p-8 shadow-sm border border-slate-100 dark:border-slate-700 relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-4 opacity-5">
                            <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                        </div>

                        <h2 class="text-2xl font-black text-slate-900 dark:text-white mb-8 uppercase">Pasos a seguir</h2>
                        
                        <div class="space-y-6 relative">
                            <!-- Línea conectora -->
                            <div class="absolute left-6 top-2 bottom-2 w-0.5 bg-slate-100 dark:bg-slate-700"></div>

                            <div v-for="(step, index) in steps" :key="index" class="flex gap-6 relative">
                                <div class="w-10 h-10 rounded-2xl bg-white dark:bg-slate-700 border-2 border-slate-100 dark:border-slate-700 flex items-center justify-center flex-shrink-0 z-10 group-hover:border-[var(--color-primary)] transition-colors shadow-sm">
                                    <span class="text-sm font-black text-[var(--color-primary)]">{{ index + 1 }}</span>
                                </div>
                                <div class="pt-1">
                                    <h3 class="font-black text-slate-900 dark:text-white text-sm uppercase mb-1">{{ step.title }}</h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium leading-relaxed">{{ step.desc }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botón de Contacto -->
                    <button @click="handleWhatsAppClick" class="w-full py-6 bg-brand-500 hover:bg-emerald-600 text-white rounded-[2rem] font-black text-sm uppercase tracking-wide shadow-xl shadow-emerald-500/20 transition-all flex items-center justify-center gap-4">
                        <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        Seguimiento por WhatsApp
                    </button>
                </div>

                <!-- Columna: Detalles del Pago -->
                <div class="space-y-6">
                    <div class="bg-white dark:bg-slate-800 rounded-[2rem] p-8 shadow-sm border border-slate-100 dark:border-slate-700">
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white mb-6 uppercase">Detalles del Pedido</h2>
                        
                        <div class="space-y-6">
                            <div class="flex justify-between items-center py-2 border-b border-slate-50 dark:border-slate-700">
                                <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase">Método de Pago</span>
                                <span class="text-xs font-black text-slate-900 dark:text-white uppercase bg-slate-100 dark:bg-slate-700 px-3 py-1 rounded-full">{{ metodoPagoLabel }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-slate-50 dark:border-slate-700">
                                <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase">Costo Envío</span>
                                <span class="text-xs font-black text-slate-900 dark:text-white">{{ formatCurrency(pedido.costo_envio) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-4">
                                <span class="text-sm font-black text-slate-900 dark:text-white uppercase">Total Final</span>
                                <span class="text-xl font-black text-[var(--color-primary)]">{{ formatCurrency(pedido.total) }}</span>
                            </div>
                        </div>

                        <!-- Botón de Pago para pasarelas online -->
                        <div v-if="pedido.estado === 'pendiente' && (pedido.metodo_pago === 'mercadopago' || pedido.metodo_pago === 'paypal')" class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-700 space-y-4">
                            <div class="p-4 bg-amber-50 dark:bg-slate-700/50 rounded-2xl border border-amber-200/50 dark:border-slate-700 flex items-start gap-3">
                                <span class="text-amber-500 mt-0.5">⚠️</span>
                                <div class="text-xs">
                                    <p class="font-black text-amber-800 dark:text-amber-200 uppercase">Pago Pendiente</p>
                                    <p class="text-slate-500 dark:text-slate-400 font-medium">Por favor, completa tu pago para poder procesar y enviar tu pedido.</p>
                                </div>
                            </div>
                            
                            <button @click="pagarPedido" :disabled="loadingPago" class="w-full py-4 bg-[var(--color-primary)] text-white rounded-xl font-black text-sm uppercase tracking-wide shadow-xl shadow-[var(--color-primary)]/20 hover:shadow-2xl hover:shadow-[var(--color-primary)]/30 transition-all flex items-center justify-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span v-if="!loadingPago">Pagar con {{ metodoPagoLabel }}</span>
                                <span v-else>Procesando Pago...</span>
                                <svg v-if="!loadingPago" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </button>
                            
                            <p v-if="errorPago" class="text-[10px] font-bold text-rose-500 dark:text-rose-400 uppercase tracking-wider text-center">
                                ❌ {{ errorPago }}
                            </p>
                        </div>

                        <!-- Datos Bancarios (Si aplica) -->
                        <div v-if="pedido.metodo_pago === 'transferencia'" class="mt-8 pt-8 border-t border-slate-100 dark:border-slate-700">
                            <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase mb-4 flex items-center gap-2">
                                <svg class="w-4 h-4 text-emerald-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                Datos para Transferencia
                            </h3>
                            <div class="bg-white dark:bg-slate-700 rounded-2xl p-6 space-y-6">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase">Banco</p>
                                        <p class="text-xs font-black text-slate-900 dark:text-slate-100">{{ empresaData.banco }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase">Sucursal</p>
                                        <p class="text-xs font-black text-slate-900 dark:text-slate-100">{{ empresaData.sucursal }}</p>
                                    </div>
                                    <div class="space-y-1 col-span-2">
                                        <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase">Titular</p>
                                        <p class="text-xs font-black text-slate-900 dark:text-slate-100">{{ empresaData.titular || empresaData.razon_social }}</p>
                                    </div>
                                    <div class="space-y-1 col-span-2">
                                        <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase">CLABE Interbancaria</p>
                                        <p class="text-sm font-black text-[var(--color-primary)] tracking-wider">{{ empresaData.clabe }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase">Cuenta</p>
                                        <p class="text-xs font-black text-slate-800 dark:text-slate-200">{{ empresaData.cuenta }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase">Referencia</p>
                                        <p class="text-xs font-black text-blue-600 dark:text-blue-400">PK{{ pedido.numero_pedido }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                         <!-- Información de Efectivo (Solo si es en sucursal) -->
                         <div v-if="pedido.metodo_pago === 'efectivo' && pedido.direccion_envio?.tipo === 'recoger_en_tienda'" class="mt-8 pt-8 border-t border-slate-100 dark:border-slate-700">
                            <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase mb-4 flex items-center gap-2">
                                <svg class="w-4 h-4 text-brand-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                Ubicación de Sucursal
                            </h3>
                            <div class="bg-white dark:bg-slate-700 rounded-2xl p-6">
                                <p class="text-xs font-bold text-slate-500 dark:text-slate-200 leading-relaxed mb-4">
                                    {{ empresaData.direccion_completa || 'Calle Principal #123, Col. Centro, Hermosillo, Sonora.' }}
                                </p>
                                <div class="flex gap-4">
                                    <div class="h-2 w-2 rounded-full bg-brand-500 dark:bg-emerald-400 mt-1"></div>
                                    <p class="text-[10px] font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">Abierto Lunes a Viernes de 9am a 7pm</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <Link :href="route('catalogo.index')" class="block w-full py-4 text-center text-xs font-black text-slate-400 uppercase tracking-wide hover:text-[var(--color-primary)] transition-colors">
                        Regresar a la tienda
                    </Link>
                </div>
            </div>

            <!-- Resumen de Dirección -->
            <div class="mt-8 bg-white dark:bg-slate-800 rounded-[2rem] p-8 shadow-sm border border-slate-100 dark:border-slate-700">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex items-start gap-4">
                        <div class="p-3 bg-white dark:bg-slate-700 rounded-xl">
                            <svg class="w-10 h-10 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </div>
                        <div>
                            <h4 class="text-xs font-black text-slate-900 dark:text-white uppercase">Información de Envío</h4>
                            <p v-if="pedido.direccion_envio.tipo === 'recoger_en_tienda'" class="text-xs font-bold text-slate-500 dark:text-slate-400 italic">Pasa a recoger en nuestra sucursal física.</p>
                            <p v-else class="text-xs font-medium text-slate-500 dark:text-slate-400 max-w-sm mt-1">
                                {{ pedido.direccion_envio.calle }}, {{ pedido.direccion_envio.colonia }}. CP {{ pedido.direccion_envio.cp }}. {{ pedido.direccion_envio.ciudad }}, {{ pedido.direccion_envio.estado }}.
                            </p>
                        </div>
                    </div>
                    <div class="flex -space-x-2">
                        <div v-for="(item, idx) in pedido.items.slice(0, 5)" :key="idx" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-700 border-2 border-white dark:border-slate-800 flex items-center justify-center text-[10px] font-black text-slate-400 dark:text-slate-500">
                            {{ item.nombre.charAt(0) }}
                        </div>
                        <div v-if="pedido.items.length > 5" class="w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-600 border-2 border-white dark:border-slate-800 flex items-center justify-center text-[10px] font-black text-slate-500 dark:text-slate-200">
                            +{{ pedido.items.length - 5 }}
                        </div>
                    </div>
                </div>
            </div>

        </main>
        
        <PublicFooter :empresa="empresaData" />
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 10px;
}
</style>
