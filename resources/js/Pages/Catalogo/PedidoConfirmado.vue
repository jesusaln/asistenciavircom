<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
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
        return [
            { title: 'Acude a sucursal', desc: 'Visítanos en nuestra sucursal física.', icon: 'home' },
            { title: 'Menciona tu pedido', desc: `Indica el número de pedido #${props.pedido.numero_pedido}.`, icon: 'message' },
            { title: 'Realiza el pago', desc: 'Paga en caja con efectivo o tarjeta.', icon: 'cash' },
            { title: 'Entrega', desc: 'Recibe tus productos al momento (sujeto a stock local).', icon: 'box' }
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
        'efectivo': 'Efectivo en Sucursal'
    }
    return labels[props.pedido.metodo_pago] || props.pedido.metodo_pago
})

const handleWhatsAppClick = () => {
    if (!empresaData.value.whatsapp) return
    const phone = empresaData.value.whatsapp.replace(/\D/g, '')
    const text = encodeURIComponent(`Hola, acabo de realizar el pedido #${props.pedido.numero_pedido} y me gustaría dar seguimiento a mi pago por ${metodoPagoLabel.value}.`)
    window.open(`https://wa.me/${phone}?text=${text}`, '_blank')
}
</script>

<template>
    <Head title="Pedido Confirmado" />

    <div class="min-h-screen bg-gray-50 flex flex-col font-sans" :style="cssVars">
        <PublicNavbar :empresa="empresaData" activeTab="tienda" />

        <main class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 w-full">
            
            <!-- Encabezado de Éxito -->
            <div class="text-center mb-12">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 text-green-600 mb-6 animate-bounce">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h1 class="text-4xl font-black text-gray-900 mb-2 uppercase tracking-tight">¡Pedido Recibido!</h1>
                <p class="text-xl text-gray-500 font-medium">Gracias por tu compra, <span class="text-gray-900 font-bold">{{ pedido.nombre }}</span></p>
                <div class="mt-4 inline-block bg-white px-6 py-2 rounded-2xl border border-gray-100 shadow-sm font-black text-[var(--color-primary)] text-lg">
                    PEDIDO #{{ pedido.numero_pedido }}
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-8 items-start">
                
                <!-- Columna: Pasos a seguir -->
                <div class="space-y-6">
                    <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-gray-100 relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-4 opacity-5">
                            <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                        </div>

                        <h2 class="text-xl font-black text-gray-900 mb-8 uppercase">Pasos a seguir</h2>
                        
                        <div class="space-y-8 relative">
                            <!-- Línea conectora -->
                            <div class="absolute left-6 top-2 bottom-2 w-0.5 bg-gray-100"></div>

                            <div v-for="(step, index) in steps" :key="index" class="flex gap-6 relative">
                                <div class="w-12 h-12 rounded-2xl bg-white border-2 border-gray-100 flex items-center justify-center flex-shrink-0 z-10 group-hover:border-[var(--color-primary)] transition-colors shadow-sm">
                                    <span class="text-sm font-black text-[var(--color-primary)]">{{ index + 1 }}</span>
                                </div>
                                <div class="pt-1">
                                    <h3 class="font-black text-gray-900 text-sm uppercase mb-1">{{ step.title }}</h3>
                                    <p class="text-xs text-gray-500 font-medium leading-relaxed">{{ step.desc }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botón de Contacto -->
                    <button @click="handleWhatsAppClick" class="w-full py-6 bg-green-500 hover:bg-green-600 text-white rounded-[2rem] font-black text-sm uppercase tracking-widest shadow-xl shadow-green-500/20 transition-all flex items-center justify-center gap-4">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        Seguimiento por WhatsApp
                    </button>
                </div>

                <!-- Columna: Detalles del Pago -->
                <div class="space-y-6">
                    <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-gray-100">
                        <h2 class="text-xl font-black text-gray-900 mb-6 uppercase">Detalles del Pedido</h2>
                        
                        <div class="space-y-4">
                            <div class="flex justify-between items-center py-2 border-b border-gray-50">
                                <span class="text-xs font-bold text-gray-400 uppercase">Método de Pago</span>
                                <span class="text-xs font-black text-gray-900 uppercase bg-gray-100 px-3 py-1 rounded-full">{{ metodoPagoLabel }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-50">
                                <span class="text-xs font-bold text-gray-400 uppercase">Costo Envío</span>
                                <span class="text-xs font-black text-gray-900">{{ formatCurrency(pedido.costo_envio) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-4">
                                <span class="text-sm font-black text-gray-900 uppercase">Total Final</span>
                                <span class="text-xl font-black text-[var(--color-primary)]">{{ formatCurrency(pedido.total) }}</span>
                            </div>
                        </div>

                        <!-- Datos Bancarios (Si aplica) -->
                        <div v-if="pedido.metodo_pago === 'transferencia'" class="mt-8 pt-8 border-t border-gray-100">
                            <h3 class="text-sm font-black text-gray-900 uppercase mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                Datos para Transferencia
                            </h3>
                            <div class="bg-gray-50 rounded-2xl p-6 space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <p class="text-[9px] font-bold text-gray-400 uppercase">Banco</p>
                                        <p class="text-xs font-black text-gray-900">{{ empresaData.banco }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-[9px] font-bold text-gray-400 uppercase">Sucursal</p>
                                        <p class="text-xs font-black text-gray-900">{{ empresaData.sucursal }}</p>
                                    </div>
                                    <div class="space-y-1 col-span-2">
                                        <p class="text-[9px] font-bold text-gray-400 uppercase">Titular</p>
                                        <p class="text-xs font-black text-gray-900">{{ empresaData.titular || empresaData.razon_social }}</p>
                                    </div>
                                    <div class="space-y-1 col-span-2">
                                        <p class="text-[9px] font-bold text-gray-400 uppercase">CLABE Interbancaria</p>
                                        <p class="text-sm font-black text-[var(--color-primary)] tracking-wider">{{ empresaData.clabe }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-[9px] font-bold text-gray-400 uppercase">Cuenta</p>
                                        <p class="text-xs font-black text-gray-800">{{ empresaData.cuenta }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-[9px] font-bold text-gray-400 uppercase">Referencia</p>
                                        <p class="text-xs font-black text-blue-600">PK{{ pedido.numero_pedido }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                         <!-- Información de Efectivo (Si aplica) -->
                         <div v-if="pedido.metodo_pago === 'efectivo'" class="mt-8 pt-8 border-t border-gray-100">
                            <h3 class="text-sm font-black text-gray-900 uppercase mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                Ubicación de Sucursal
                            </h3>
                            <div class="bg-gray-50 rounded-2xl p-6">
                                <p class="text-xs font-bold text-gray-600 leading-relaxed mb-4">
                                    {{ empresaData.direccion_completa || 'Calle Principal #123, Col. Centro, Hermosillo, Sonora.' }}
                                </p>
                                <div class="flex gap-4">
                                    <div class="h-2 w-2 rounded-full bg-green-500 mt-1"></div>
                                    <p class="text-[10px] font-medium text-gray-500 uppercase tracking-wide">Abierto Lunes a Viernes de 9am a 7pm</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <Link :href="route('catalogo.index')" class="block w-full py-4 text-center text-xs font-black text-gray-400 uppercase tracking-widest hover:text-[var(--color-primary)] transition-colors">
                        Regresar a la tienda
                    </Link>
                </div>
            </div>

            <!-- Resumen de Dirección -->
            <div class="mt-8 bg-white rounded-[2rem] p-8 shadow-sm border border-gray-100">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex items-start gap-4">
                        <div class="p-3 bg-gray-50 rounded-xl">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </div>
                        <div>
                            <h4 class="text-xs font-black text-gray-900 uppercase">Información de Envío</h4>
                            <p v-if="pedido.direccion_envio.tipo === 'recoger_en_tienda'" class="text-xs font-bold text-gray-500 italic">Pasa a recoger en nuestra sucursal física.</p>
                            <p v-else class="text-xs font-medium text-gray-500 max-w-sm mt-1">
                                {{ pedido.direccion_envio.calle }}, {{ pedido.direccion_envio.colonia }}. CP {{ pedido.direccion_envio.cp }}. {{ pedido.direccion_envio.ciudad }}, {{ pedido.direccion_envio.estado }}.
                            </p>
                        </div>
                    </div>
                    <div class="flex -space-x-2">
                        <div v-for="(item, idx) in pedido.items.slice(0, 5)" :key="idx" class="w-10 h-10 rounded-full bg-gray-100 border-2 border-white flex items-center justify-center text-[10px] font-black text-gray-400">
                            {{ item.nombre.charAt(0) }}
                        </div>
                        <div v-if="pedido.items.length > 5" class="w-10 h-10 rounded-full bg-gray-200 border-2 border-white flex items-center justify-center text-[10px] font-black text-gray-600">
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
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 10px;
}
</style>
