<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'
import { useCart } from '@/composables/useCart'
import { computed, ref } from 'vue'
import PublicNavbar from '@/Components/PublicNavbar.vue'
import PublicFooter from '@/Components/PublicFooter.vue'
import WhatsAppWidget from '@/Components/WhatsAppWidget.vue'

const props = defineProps({
    empresa: Object,
    recomendados: Array
})

const page = usePage();

// Combinar datos globales con props para asegurar colores corporativos
const empresaData = computed(() => {
    const globalConfig = page.props.empresa_config || {};
    const localProp = props.empresa || {};
    return { ...globalConfig, ...localProp };
});

const { 
    items, itemCount, subtotal, subtotalSinIva, iva, isEmpty, 
    removeItem, incrementQuantity, decrementQuantity, clearCart,
    syncWithServer 
} = useCart()

const cssVars = computed(() => ({
    '--color-primary': empresaData.value.color_principal || '#FF6B35',
    '--color-primary-soft': (empresaData.value.color_principal || '#FF6B35') + '15',
    '--color-primary-dark': (empresaData.value.color_principal || '#FF6B35') + 'dd',
    '--color-secondary': empresaData.value.color_secundario || '#D97706',
    '--color-terciary': empresaData.value.color_terciario || '#B45309',
    '--color-terciary-soft': (empresaData.value.color_terciario || '#B45309') + '15',
}));

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { 
        style: 'currency', 
        currency: 'MXN' 
    }).format(value || 0)
}

const getImageUrl = (imagen) => {
    if (!imagen) return null
    let urlStr = String(imagen).trim()
    
    // Si el backend nos mandó /storage/http... por error, lo limpiamos
    if (urlStr.startsWith('/storage/http')) {
        urlStr = urlStr.replace('/storage/', '')
    }
    
    // Si ya es una URL absoluta o relativa al protocolo
    if (urlStr.toLowerCase().startsWith('http') || urlStr.startsWith('//')) {
        try {
            return route('img.proxy', { u: btoa(urlStr) })
        } catch (e) {
            return route('img.proxy', { url: urlStr })
        }
    }
    
    // Si ya tiene el prefijo storage o empieza con /
    if (urlStr.startsWith('/storage/') || urlStr.startsWith('/')) {
        return urlStr
    }
    
    return `/storage/${urlStr}`
}

// $100 base + 16% IVA = $116
const costoEnvio = computed(() => 116)
const total = computed(() => subtotal.value + costoEnvio.value)

const isValidating = ref(false)

const handleCheckout = async () => {
    isValidating.value = true
    try {
        const result = await syncWithServer()
        if (result.error) {
            alert(result.error)
            return
        }

        if (result.changed) {
            alert('Algunos precios o existencias han cambiado. Por favor revisa tu carrito antes de continuar.')
            return
        }

        if (!result.valid) {
            alert('Hay problemas con algunos artículos en tu carrito.')
            return
        }

        // Redirigir al checkout
        window.location.href = route('tienda.checkout')
    } catch (e) {
        console.error(e)
        alert('Ocurrió un error al validar tu pedido.')
    } finally {
        isValidating.value = false
    }
}
</script>

<template>
    <Head :title="`Carrito - ${empresaData?.nombre || empresaData?.nombre_empresa || 'Tienda'}`" />
    
    <div class="min-h-screen bg-white dark:bg-gray-900 flex flex-col font-sans transition-colors duration-300" :style="cssVars">
        <!-- Widget Flotante de WhatsApp -->
        <WhatsAppWidget :whatsapp="empresaData?.whatsapp" :empresaNombre="empresaData?.nombre || empresaData?.nombre_empresa" />

        <!-- Navbar Corporativo -->
        <PublicNavbar :empresa="empresaData" activeTab="tienda" />

        <!-- Main Content -->
        <main class="flex-grow w-full px-4 sm:px-6 lg:px-8 py-8 lg:py-12 w-full">
            <h1 class="text-3xl font-black text-gray-900 dark:text-white mb-8 flex items-center gap-3 transition-colors">
                <span class="w-10 h-10 rounded-xl bg-[var(--color-primary-soft)] flex items-center justify-center text-[var(--color-primary)]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </span>
                Carrito de Compras
                <span v-if="itemCount > 0" class="text-lg font-bold text-gray-400 dark:text-gray-500 transition-colors">({{ itemCount }})</span>
            </h1>

            <div v-if="isEmpty" class="text-center py-20 bg-white dark:bg-gray-800 rounded-[2rem] shadow-xl shadow-gray-100 dark:shadow-none border border-gray-100 dark:border-gray-700 transition-colors">
                <div class="w-32 h-32 mx-auto mb-8 bg-[var(--color-primary-soft)] rounded-full flex items-center justify-center">
                    <svg class="w-16 h-16 text-[var(--color-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-black text-gray-900 dark:text-white mb-4 transition-colors">Tu carrito está vacío</h2>
                <p class="text-gray-500 dark:text-gray-400 font-medium mb-8 transition-colors">Parece que aún no has agregado nada.</p>
                <Link :href="route('catalogo.index')" 
                      class="inline-flex items-center gap-2 px-8 py-4 bg-[var(--color-primary)] text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl shadow-[var(--color-primary)]/20 hover:-translate-y-1 hover:shadow-2xl hover:shadow-[var(--color-primary)]/30 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    Explorar Productos
                </Link>
            </div>

            <div v-else class="grid lg:grid-cols-3 gap-8">
                <!-- Products List -->
                <div class="lg:col-span-2 space-y-4">
                    <div v-for="item in items" :key="item.producto_id"
                         class="bg-white dark:bg-gray-800 rounded-[1.5rem] p-4 shadow-sm border border-gray-100 dark:border-gray-700 flex gap-6 hover:shadow-md transition-all">
                        <!-- Image -->
                        <div class="w-28 h-28 flex-shrink-0 bg-white dark:bg-gray-900 rounded-2xl overflow-hidden border border-gray-100 dark:border-gray-700 transition-colors">
                            <img v-if="getImageUrl(item.imagen)" 
                                 :src="getImageUrl(item.imagen)" 
                                 :alt="item.nombre"
                                 class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Details -->
                        <div class="flex-1 min-w-0 flex flex-col justify-between py-1">
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white text-lg truncate transition-colors">{{ item.nombre }}</h3>
                                <p class="text-sm font-black text-[var(--color-primary)] mt-1 tracking-wide">
                                    {{ formatCurrency(item.precio) }}
                                </p>
                                <span v-if="String(item.producto_id).startsWith('CVA-')" class="text-[9px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded uppercase mt-1 inline-block">
                                    Bajo Pedido
                                </span>
                            </div>
                            
                            <button @click="removeItem(item.producto_id)"
                                    class="text-xs font-bold text-red-500 hover:text-red-700 transition-colors flex items-center gap-1 self-start mt-2 uppercase tracking-wide">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                Eliminar
                            </button>
                        </div>

                        <!-- Quantity & Total -->
                        <div class="flex flex-col items-end justify-between py-1">
                             <p class="text-lg font-black text-gray-900 dark:text-white transition-colors">
                                {{ formatCurrency(item.precio * item.cantidad) }}
                            </p>

                            <!-- Quantity Controls -->
                            <div class="flex items-center gap-1 bg-white dark:bg-gray-700 rounded-xl p-1 border border-gray-100 dark:border-gray-600 transition-colors">
                                <button @click="decrementQuantity(item.producto_id)"
                                        class="w-8 h-8 rounded-lg bg-white dark:bg-gray-600 flex items-center justify-center text-gray-500 dark:text-gray-300 hover:text-[var(--color-primary)] hover:shadow-sm transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                    </svg>
                                </button>
                                <span class="w-8 text-center font-bold text-gray-900 dark:text-white text-sm transition-colors">{{ item.cantidad }}</span>
                                <button @click="incrementQuantity(item.producto_id)"
                                        class="w-8 h-8 rounded-lg bg-white dark:bg-gray-600 flex items-center justify-center text-gray-500 dark:text-gray-300 hover:text-[var(--color-primary)] hover:shadow-sm transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-gray-800 rounded-[2rem] p-8 shadow-xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 sticky top-28 transition-colors">
                        <h2 class="text-xl font-black text-gray-900 dark:text-white mb-6 uppercase tracking-tight transition-colors">Resumen</h2>
                        
                        <div class="space-y-4 mb-8">
                            <div class="flex justify-between text-gray-400 dark:text-gray-500 font-medium text-xs uppercase tracking-widest transition-colors">
                                <span>Subtotal (sin IVA)</span>
                                <span class="font-bold text-gray-600 dark:text-gray-300 transition-colors">{{ formatCurrency(subtotalSinIva) }}</span>
                            </div>
                            <div class="flex justify-between text-gray-400 dark:text-gray-500 font-medium text-xs uppercase tracking-widest transition-colors">
                                <span>IVA (16%)</span>
                                <span class="font-bold text-gray-600 dark:text-gray-300 transition-colors">{{ formatCurrency(iva) }}</span>
                            </div>
                            <div class="flex justify-between text-gray-500 dark:text-gray-400 font-medium text-sm transition-colors">
                                <span>Costo de Envío</span>
                                <span class="font-bold text-gray-900 dark:text-white transition-colors">
                                    {{ formatCurrency(costoEnvio) }}
                                </span>
                            </div>
                            
                            <div class="p-3 bg-[var(--color-primary-soft)] rounded-xl border border-[var(--color-primary)]/20 text-[10px] font-black uppercase tracking-widest text-[var(--color-primary)] flex items-center gap-2">
                                🚚 Envío a domicilio: {{ formatCurrency(116) }} (incl. IVA)
                            </div>

                            <div v-if="items.some(i => String(i.producto_id).startsWith('CVA-'))" class="p-3 bg-blue-50 rounded-xl border border-blue-100 text-[10px] font-bold text-blue-700 flex items-center gap-2">
                                📦 Productos bajo pedido (Entrega 2-4 días hábiles)
                            </div>
                            <div class="h-px bg-gray-100 dark:bg-gray-700 my-2 transition-colors"></div>
                            <div class="flex justify-between text-lg">
                                <span class="font-black text-gray-900 dark:text-white transition-colors">Total</span>
                                <span class="font-black text-2xl text-[var(--color-primary)]">
                                    {{ formatCurrency(total) }}
                                </span>
                            </div>
                        </div>

                        <!-- Checkout Button -->
                        <button @click="handleCheckout"
                                :disabled="isValidating"
                                class="block w-full py-4 bg-[var(--color-primary)] text-white rounded-xl font-black text-sm uppercase tracking-widest text-center shadow-xl shadow-[var(--color-primary)]/20 hover:-translate-y-1 hover:shadow-2xl hover:shadow-[var(--color-primary)]/30 transition-all disabled:opacity-50 disabled:cursor-wait">
                            <span v-if="isValidating" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Validando...
                            </span>
                            <span v-else>Pagar Ahora</span>
                        </button>
                        
                        <div class="mt-4 text-center">
                             <Link :href="route('catalogo.index')" class="text-xs font-bold text-gray-400 hover:text-[var(--color-primary)] uppercase tracking-widest transition-colors">
                                Seguir comprando
                            </Link>
                        </div>
                    </div>
                    
                    <!-- Seguridad -->
                    <div class="mt-6 flex items-center justify-center gap-2 text-[10px] uppercase font-black tracking-widest text-gray-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        Pago 100% Seguro
                    </div>
                </div>
            </div>

            <!-- Recommended Products -->
            <div v-if="recomendados?.length" class="mt-20 border-t border-gray-100 dark:border-gray-800 pt-12 transition-colors">
                <h2 class="text-2xl font-black text-gray-900 dark:text-white mb-8 transition-colors">Te podría interesar</h2>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
                    <Link v-for="rel in recomendados" :key="rel.id"
                          :href="route('catalogo.show', rel.id)"
                          class="group bg-white dark:bg-gray-800 rounded-3xl overflow-hidden border border-gray-100 dark:border-gray-700 hover:shadow-2xl hover:border-white dark:hover:border-gray-600 transition-all duration-500">
                        <div class="aspect-square bg-white overflow-hidden relative">
                            <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent pointer-events-none z-10"></div>
                            <img v-if="getImageUrl(rel.imagen)" 
                                 :src="getImageUrl(rel.imagen)" 
                                 :alt="rel.nombre"
                                 class="w-full h-full object-contain group-hover:scale-110 transition-transform duration-500 p-4" />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div v-if="rel.stock <= 0" class="absolute top-2 right-2 z-20">
                                <span class="bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">Preguntar</span>
                            </div>
                        </div>
                        <div class="p-5">
                            <h3 class="font-bold text-gray-900 dark:text-white text-sm line-clamp-2 group-hover:text-[var(--color-primary)] transition-colors mb-2">
                                {{ rel.nombre }}
                            </h3>
                            <div class="flex items-center justify-between">
                                <p class="font-black text-lg" style="color: var(--color-primary);">
                                    {{ formatCurrency(rel.precio_con_iva) }}
                                </p>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </main>
        
    </div>
</template>
