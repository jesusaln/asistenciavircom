<script setup>
import { Head, usePage, useForm } from '@inertiajs/vue3'
import { useCart } from '@/composables/useCart'
import { computed, ref, onMounted, watch } from 'vue'
import axios from 'axios'
import PublicNavbar from '@/Components/PublicNavbar.vue'
import PublicFooter from '@/Components/PublicFooter.vue'

const props = defineProps({
    empresa: Object,
    cliente: Object,
    canLogin: Boolean
})

const page = usePage()
const { items, subtotal, subtotalSinIva, iva, clearCart, syncWithServer } = useCart()
const processing = ref(false)
const isValidating = ref(false)

const cssVars = computed(() => ({
    '--color-primary': props.empresa?.color_principal || '#3b82f6',
    '--color-primary-soft': (props.empresa?.color_principal || '#3b82f6') + '15',
    '--color-secondary': props.empresa?.color_secundario || '#6b7280',
    '--color-terciary': props.empresa?.color_terciario || '#fbbf24',
    '--color-terciary-soft': (props.empresa?.color_terciario || '#fbbf24') + '15',
}));

const form = useForm({
    nombre: props.cliente?.nombre || '',
    email: props.cliente?.email || '',
    telefono: props.cliente?.telefono || '',
    direccion: {
        calle: props.cliente?.direccion_predeterminada?.calle || '',
        colonia: props.cliente?.direccion_predeterminada?.colonia || '',
        ciudad: props.cliente?.direccion_predeterminada?.ciudad || '',
        estado: props.cliente?.direccion_predeterminada?.estado || '',
        cp: props.cliente?.direccion_predeterminada?.cp || '',
    },
    tipo_entrega: 'domicilio',
    metodo_pago: 'mercadopago',
    items: [],
})

const costoEnvio = ref(0)
const shippingDetails = ref(null)
const loadingShipping = ref(false)

// Calcular costo de envío dinámicamente
const fetchShippingCost = async () => {
    if (form.tipo_entrega === 'recoger') {
        costoEnvio.value = 0
        shippingDetails.value = null
        return
    }

    if (form.direccion.cp?.length !== 5) return

    loadingShipping.value = true
    try {
        const response = await axios.post(route('tienda.cva.shipping'), {
            cp: form.direccion.cp,
            items: items.value.map(i => ({
                id: i.producto_id,
                cantidad: i.cantidad,
                peso: i.peso // Si viene del carrito
            }))
        })
        
        if (response.data.success) {
            costoEnvio.value = response.data.costo
            shippingDetails.value = response.data
        }
    } catch (e) {
        console.error('Error calculando envío:', e)
        
        // Fallback inteligente basado en PESO
        const pesoTotal = items.value.reduce((total, item) => total + ((item.peso || 1) * item.cantidad), 0);
        
        if (form.direccion.cp && form.direccion.cp.startsWith('83')) {
            // Local (Hermosillo): $100 base. 
            // Si pesa más de 25kg (requiere camioneta/ayudante), cobramos $200
            costoEnvio.value = pesoTotal > 25 ? 200 : 100; 
        } else {
             // Nacional: $250 base (hasta 5kg) + $40 por kg extra (Protección alta)
             const kilosExtra = Math.max(0, pesoTotal - 5);
             costoEnvio.value = 250 + (kilosExtra * 40);
        }

        shippingDetails.value = {
            proveedor: 'Envío Estándar (Calculado por Peso)',
            tiempo_entrega: '3 a 6 días hábiles'
        }
    } finally {
        loadingShipping.value = false
    }
}


watch(() => form.tipo_entrega, (newVal) => {
    if (newVal === 'recoger') {
        costoEnvio.value = 0
    } else {
        costoEnvio.value = 100 // Costo estándar visible de inmediato
        if (form.direccion.cp?.length === 5) {
            fetchShippingCost()
        }
    }
})

watch(() => form.direccion.cp, (newVal) => {
    if (newVal?.length === 5 && form.tipo_entrega === 'domicilio') {
        fetchShippingCost()
    }
})
const total = computed(() => subtotal.value + costoEnvio.value)

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { 
        style: 'currency', 
        currency: 'MXN' 
    }).format(value || 0)
}

const submitOrder = async () => {
    processing.value = true
    isValidating.value = true
    
    try {
        // Validación final de Stock y Precios
        const validation = await syncWithServer()
        if (validation.error) {
            alert(validation.error)
            processing.value = false
            isValidating.value = false
            return
        }

        if (validation.changed) {
            alert('¡Atención! Algunos precios o existencias han cambiado en el último momento. Por favor, revisa el resumen de tu pedido.')
            processing.value = false
            isValidating.value = false
            return
        }

        if (!validation.valid) {
            alert('Lo sentimos, algunos artículos ya no están disponibles en las cantidades solicitadas.')
            processing.value = false
            isValidating.value = false
            return
        }

        // Preparar items del carrito
        form.items = items.value.map(item => ({
            producto_id: item.producto_id,
            cantidad: item.cantidad
        }))

        form.post(route('tienda.checkout.procesar'), {
            onSuccess: () => {
                 clearCart() // Limpiar carrito tras éxito
            },
            onFinish: () => {
                processing.value = false
                isValidating.value = false
            }
        })
    } catch (e) {
        console.error(e)
        alert('Error al procesar el pedido.')
        processing.value = false
        isValidating.value = false
    }
}

// Pre-llenar datos si el usuario se loguea después de entrar al checkout
onMounted(() => {
    if (props.cliente) {
        form.nombre = props.cliente.nombre
        form.email = props.cliente.email
        form.telefono = props.cliente.telefono
    }
    
    // Calcular envío inicial si ya hay CP
    if (form.tipo_entrega === 'domicilio') {
        if (form.direccion.cp?.length === 5) {
            fetchShippingCost()
        } else {
            costoEnvio.value = 100
        }
    }
})
    // Variables para CP
    const coloniasDisponibles = ref([])
    const loadingCP = ref(false)

    // Lógica para CP
    watch(() => form.direccion.cp, async (newVal) => {
        if (newVal?.length === 5) {
            loadingCP.value = true
            coloniasDisponibles.value = [] // Limpiar previas
            
            try {
                const response = await axios.get(route('api.cp', newVal))
                if (response.data) {
                    form.direccion.estado = response.data.estado?.toUpperCase() || ''
                    form.direccion.ciudad = response.data.municipio?.toUpperCase() || ''
                    
                    if (response.data.colonias && Array.isArray(response.data.colonias)) {
                        coloniasDisponibles.value = response.data.colonias.map(c => c.toUpperCase())
                        // Auto-seleccionar si solo hay una
                        if (coloniasDisponibles.value.length === 1) {
                            form.direccion.colonia = coloniasDisponibles.value[0]
                        } else {
                            form.direccion.colonia = '' 
                        }
                    }
                }
            } catch (e) {
                console.error("No se encontraron datos para el CP", e)
            } finally {
                loadingCP.value = false
                // Calcular envío
                if (form.tipo_entrega === 'domicilio') fetchShippingCost()
            }
        }
    })
    
    // Formateadores
    const toUpper = (e, field) => { 
        form[field] = e.target.value.toUpperCase() 
    }
    const toUpperNested = (e, parent, field) => { 
        form[parent][field] = e.target.value.toUpperCase() 
    }
    const toLower = (e, field) => { 
        form[field] = e.target.value.toLowerCase() 
    }
    const formatPhone = (e) => {
        let val = e.target.value.replace(/\D/g, '').substring(0, 10)
        form.telefono = val
    }
</script>

<template>
    <Head title="Finalizar Compra" />

    <div class="min-h-screen bg-gray-50 flex flex-col font-sans" :style="cssVars">
        <PublicNavbar :empresa="empresa" activeTab="tienda" />

        <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12 w-full">
            <!-- (Header section unchanged) -->
             <div class="mb-10">
                <h1 class="text-3xl font-black text-gray-900 flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-[var(--color-primary-soft)] flex items-center justify-center text-[var(--color-primary)]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </span>
                    Finalizar Compra
                </h1>
                <p class="text-gray-500 font-medium ml-14 mt-1">Completa tus datos para recibir tu pedido.</p>
            </div>

            <form @submit.prevent="submitOrder" class="grid lg:grid-cols-3 gap-8">
                
                <!-- Columna Izquierda: Datos de Envío -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Sección: Datos Personales -->
                    <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-gray-100">
                        <h2 class="text-xl font-black text-gray-900 mb-6 flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-500 text-sm">1</span>
                            Datos de Contacto
                        </h2>
                        
                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="space-y-1">
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-1">Nombre Completo</label>
                                <input :value="form.nombre" @input="toUpper($event, 'nombre')" type="text" class="w-full px-5 py-3 bg-gray-50 border-gray-100 rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] transition-all font-bold text-gray-800 placeholder-gray-300" placeholder="Ej. JUAN PÉREZ" required>
                                <div v-if="form.errors.nombre" class="text-red-500 text-[10px] font-bold mt-1 uppercase">{{ form.errors.nombre }}</div>
                            </div>

                             <div class="space-y-1">
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-1">Teléfono (10 dígitos)</label>
                                <input :value="form.telefono" @input="formatPhone" type="tel" maxlength="10" class="w-full px-5 py-3 bg-gray-50 border-gray-100 rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] transition-all font-bold text-gray-800 placeholder-gray-300" placeholder="6621234567" required>
                                <div v-if="form.errors.telefono" class="text-red-500 text-[10px] font-bold mt-1 uppercase">{{ form.errors.telefono }}</div>
                            </div>

                            <div class="space-y-1 md:col-span-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-1">Correo Electrónico</label>
                                <input :value="form.email" @input="toLower($event, 'email')" type="email" class="w-full px-5 py-3 bg-gray-50 border-gray-100 rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] transition-all font-bold text-gray-800 placeholder-gray-300" placeholder="juan@ejemplo.com" required>
                                <div v-if="form.errors.email" class="text-red-500 text-[10px] font-bold mt-1 uppercase">{{ form.errors.email }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección: Método de Entrega (Unchanged, included for context structure if needed or just skipped if replace works properly) -->
                    <!-- ... -->
                    <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-gray-100">
                        <!-- ... -->
                        <!-- Resumiendo el bloque Método de entrega para no sobrescribir cambios, me enfocaré en la Dirección -->
                        <!-- ... -->
                         <h2 class="text-xl font-black text-gray-900 mb-6 flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-500 text-sm">2</span>
                            Método de Entrega
                        </h2>
                        
                        <div class="grid md:grid-cols-2 gap-4">
                             <!-- Radio domicilio -->
                            <label class="cursor-pointer relative group">
                                <input type="radio" v-model="form.tipo_entrega" value="domicilio" class="peer sr-only">
                                <div class="p-6 rounded-2xl border-2 border-gray-100 bg-gray-50 peer-checked:bg-[var(--color-primary-soft)] peer-checked:border-[var(--color-primary)] transition-all flex flex-col gap-2">
                                    <div class="flex justify-between items-center">
                                        <span class="font-black text-gray-900 uppercase tracking-tight">A Domicilio</span>
                                        <svg class="w-6 h-6 text-[var(--color-primary)] opacity-0 peer-checked:opacity-100" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                    </div>
                                    <p class="text-xs text-gray-500 font-medium leading-relaxed">Recibe tu pedido en la puerta de tu casa u oficina.</p>
                                    <span class="text-sm font-black text-[var(--color-primary)] mt-2">+ {{ formatCurrency(costoEnvio) }} (Envío Estándar)</span>
                                </div>
                            </label>

                            <!-- Radio recoger -->
                            <label class="cursor-pointer relative group">
                                <input type="radio" v-model="form.tipo_entrega" value="recoger" class="peer sr-only">
                                <div class="p-6 rounded-2xl border-2 border-gray-100 bg-gray-50 peer-checked:bg-[var(--color-primary-soft)] peer-checked:border-[var(--color-primary)] transition-all flex flex-col gap-2">
                                    <div class="flex justify-between items-center">
                                        <span class="font-black text-gray-900 uppercase tracking-tight">Recoger en Tienda</span>
                                    </div>
                                    <p class="text-xs text-gray-500 font-medium leading-relaxed">Pasa por tu pedido a nuestra sucursal sin costo adicional.</p>
                                    <span class="text-sm font-black text-green-600 mt-2">¡GRATIS!</span>
                                </div>
                            </label>
                        </div>
                    </div>


                    <!-- Sección: Dirección REFORMADA -->
                    <div v-if="form.tipo_entrega === 'domicilio'" class="bg-white rounded-[2rem] p-8 shadow-sm border border-gray-100">
                        <h2 class="text-xl font-black text-gray-900 mb-6 flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-500 text-sm">3</span>
                            Dirección de Envío
                        </h2>

                        <div class="space-y-6">
                            <!-- Fila 1: CP primero para detonar la API -->
                             <div class="grid grid-cols-2 gap-6">
                                <div class="space-y-1">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-1">Código Postal</label>
                                    <div class="relative">
                                        <input v-model="form.direccion.cp" type="text" maxlength="5" class="w-full px-5 py-3 bg-gray-50 border-gray-100 rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] transition-all font-bold text-gray-800 placeholder-gray-300" placeholder="Ej. 83000" required>
                                        <div v-if="loadingCP" class="absolute right-3 top-3 text-gray-400 text-xs animate-spin">⌛</div>
                                    </div>
                                     <div v-if="form.errors['direccion.cp']" class="text-red-500 text-[10px] font-bold mt-1 uppercase">{{ form.errors['direccion.cp'] }}</div>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-1">Estado</label>
                                    <input :value="form.direccion.estado" readonly type="text" class="w-full px-5 py-3 bg-gray-100 border-gray-100 rounded-xl font-bold text-gray-500 cursor-not-allowed" placeholder="Se llena automático" required>
                                     <div v-if="form.errors['direccion.estado']" class="text-red-500 text-[10px] font-bold mt-1 uppercase">{{ form.errors['direccion.estado'] }}</div>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-6">
                                <div class="space-y-1">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-1">Ciudad / Municipio</label>
                                    <input :value="form.direccion.ciudad" readonly type="text" class="w-full px-5 py-3 bg-gray-100 border-gray-100 rounded-xl font-bold text-gray-500 cursor-not-allowed" placeholder="Se llena automático" required>
                                     <div v-if="form.errors['direccion.ciudad']" class="text-red-500 text-[10px] font-bold mt-1 uppercase">{{ form.errors['direccion.ciudad'] }}</div>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-1">Colonia</label>
                                    <!-- Select si hay colonias, input si no -->
                                    <select v-if="coloniasDisponibles.length > 0" v-model="form.direccion.colonia" class="w-full px-5 py-3 bg-gray-50 border-gray-100 rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] transition-all font-bold text-gray-800">
                                        <option value="" disabled>Selecciona una colonia</option>
                                        <option v-for="col in coloniasDisponibles" :key="col" :value="col">{{ col }}</option>
                                    </select>
                                    <input v-else :value="form.direccion.colonia" @input="toUpperNested($event, 'direccion', 'colonia')" type="text" class="w-full px-5 py-3 bg-gray-50 border-gray-100 rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] transition-all font-bold text-gray-800 placeholder-gray-300" placeholder="Ej. CENTRO" required>
                                    
                                     <div v-if="form.errors['direccion.colonia']" class="text-red-500 text-[10px] font-bold mt-1 uppercase">{{ form.errors['direccion.colonia'] }}</div>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-1">Calle y Número</label>
                                <input :value="form.direccion.calle" @input="toUpperNested($event, 'direccion', 'calle')" type="text" class="w-full px-5 py-3 bg-gray-50 border-gray-100 rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] transition-all font-bold text-gray-800 placeholder-gray-300" placeholder="AV. REFORMA 123" required>
                                <div v-if="form.errors['direccion.calle']" class="text-red-500 text-[10px] font-bold mt-1 uppercase">{{ form.errors['direccion.calle'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Resumen y Pago -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-[2rem] p-8 shadow-xl shadow-gray-200/50 border border-gray-100 sticky top-28">
                        <h3 class="text-lg font-black text-gray-900 mb-6 uppercase tracking-tight">Resumen del Pedido</h3>
                        
                        <!-- Lista de Items Compacta -->
                        <div class="space-y-4 mb-6 max-h-60 overflow-y-auto pr-2 custom-scrollbar">
                            <div v-for="item in items" :key="item.producto_id" class="flex items-center gap-3 py-2 border-b border-gray-50 last:border-0">
                                <div class="w-12 h-12 bg-gray-50 rounded-lg flex-shrink-0 flex items-center justify-center">
                                     <span class="text-xs font-bold text-gray-400">x{{ item.cantidad }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-gray-900 truncate">{{ item.nombre }}</p>
                                    <p class="text-[10px] text-gray-500">{{ formatCurrency(item.precio) }}</p>
                                </div>
                                <div class="text-xs font-bold text-gray-900">
                                    {{ formatCurrency(item.precio * item.cantidad) }}
                                </div>
                            </div>
                        </div>

                        <div class="h-px bg-gray-100 my-4"></div>

                        <!-- Totales -->
                         <div class="space-y-3 mb-8">
                            <div class="flex justify-between text-gray-400 font-medium text-xs uppercase tracking-widest">
                                <span>Subtotal (sin IVA)</span>
                                <span class="font-bold text-gray-600">{{ formatCurrency(subtotalSinIva) }}</span>
                            </div>
                            <div class="flex justify-between text-gray-400 font-medium text-xs uppercase tracking-widest">
                                <span>IVA (16%)</span>
                                <span class="font-bold text-gray-600">{{ formatCurrency(iva) }}</span>
                            </div>
                            <div class="flex justify-between text-gray-500 font-medium text-sm pt-2 border-t border-gray-50 items-center">
                                <span>Costo de Envío</span>
                                <div class="flex flex-col items-end">
                                    <span v-if="loadingShipping" class="flex items-center gap-1 text-[10px] text-blue-500 animate-pulse">
                                        Calculando...
                                    </span>
                                    <span v-else class="font-bold text-gray-900">
                                        {{ formatCurrency(costoEnvio) }}
                                    </span>
                                    <span v-if="shippingDetails" class="text-[9px] text-gray-400 font-medium uppercase">
                                        {{ shippingDetails.proveedor }}
                                    </span>
                                </div>
                            </div>
                            <div v-if="shippingDetails?.tiempo_entrega" class="p-2 bg-blue-50 rounded-lg text-[9px] font-bold text-blue-700">
                                🕒 {{ shippingDetails.tiempo_entrega }}
                            </div>
                            <div class="flex justify-between text-lg mt-4 pt-4 border-t-2 border-gray-100">
                                <span class="font-black text-gray-900 uppercase tracking-tight">Total a Pagar</span>
                                <span class="font-black text-2xl text-[var(--color-primary)]">
                                    {{ formatCurrency(total) }}
                                </span>
                            </div>
                        </div>

                        <!-- Método de Pago -->
                        <div class="mb-8">
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 block mb-3">Método de Pago</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label v-if="cliente?.credito_activo" class="cursor-pointer relative group">
                                    <input type="radio" v-model="form.metodo_pago" value="credito" class="peer sr-only" :disabled="cliente.credito_disponible < total">
                                    <div :class="[
                                        'p-4 rounded-xl border transition-all text-center h-full flex flex-col items-center justify-center gap-1 group-hover:shadow-md',
                                        cliente.credito_disponible < total 
                                            ? 'bg-gray-100 border-gray-100 opacity-50 cursor-not-allowed' 
                                            : 'border-gray-200 bg-gray-50 peer-checked:bg-emerald-50 peer-checked:border-emerald-500 peer-checked:text-emerald-700 group-hover:bg-white'
                                    ]">
                                        <font-awesome-icon icon="credit-card" class="mb-1" />
                                        <span class="font-bold text-[10px] uppercase leading-tight">Crédito Comercial</span>
                                        <span class="text-[8px] font-black opacity-60">DP: {{ formatCurrency(cliente.credito_disponible) }}</span>
                                    </div>
                                </label>
                                <label class="cursor-pointer relative group">
                                    <input type="radio" v-model="form.metodo_pago" value="mercadopago" class="peer sr-only">
                                    <div class="p-4 rounded-xl border border-gray-200 bg-gray-50 peer-checked:bg-[var(--color-primary-soft)] peer-checked:border-[var(--color-primary)] peer-checked:text-[var(--color-primary)] transition-all text-center h-full flex flex-col items-center justify-center gap-2 group-hover:bg-white group-hover:shadow-md">
                                        <span class="font-bold text-[10px] uppercase">MercadoPago</span>
                                    </div>
                                </label>
                                <label class="cursor-pointer relative group">
                                    <input type="radio" v-model="form.metodo_pago" value="paypal" class="peer sr-only">
                                    <div class="p-4 rounded-xl border border-gray-200 bg-gray-50 peer-checked:bg-blue-50 peer-checked:border-blue-500 peer-checked:text-blue-600 transition-all text-center h-full flex flex-col items-center justify-center gap-2 group-hover:bg-white group-hover:shadow-md">
                                        <span class="font-bold text-[10px] uppercase">PayPal</span>
                                    </div>
                                </label>
                                <label class="cursor-pointer relative group">
                                    <input type="radio" v-model="form.metodo_pago" value="transferencia" class="peer sr-only">
                                    <div class="p-4 rounded-xl border border-gray-200 bg-gray-50 peer-checked:bg-green-50 peer-checked:border-green-600 peer-checked:text-green-700 transition-all text-center h-full flex flex-col items-center justify-center gap-2 group-hover:bg-white group-hover:shadow-md">
                                        <span class="font-bold text-[10px] uppercase">Transferencia</span>
                                    </div>
                                </label>
                                <label class="cursor-pointer relative group">
                                    <input type="radio" v-model="form.metodo_pago" value="efectivo" class="peer sr-only">
                                    <div class="p-4 rounded-xl border border-gray-200 bg-gray-50 peer-checked:bg-amber-50 peer-checked:border-amber-600 peer-checked:text-amber-700 transition-all text-center h-full flex flex-col items-center justify-center gap-2 group-hover:bg-white group-hover:shadow-md">
                                        <span class="font-bold text-[10px] uppercase">Efectivo</span>
                                    </div>
                                </label>
                            </div>
                            <p v-if="cliente?.credito_activo && cliente.credito_disponible < total" class="text-[9px] font-bold text-red-500 mt-2 uppercase tracking-tight">
                                ⚠️ Saldo insuficiente en su línea de crédito.
                            </p>
                            <div v-if="form.errors.metodo_pago" class="text-red-500 text-[10px] font-bold mt-1 uppercase">{{ form.errors.metodo_pago }}</div>
                        </div>

                        <!-- Botón de Acción -->
                        <button type="submit" 
                            :disabled="processing || items.length === 0"
                            class="w-full py-4 bg-[var(--color-primary)] text-white rounded-xl font-black text-sm uppercase tracking-widest shadow-xl shadow-[var(--color-primary)]/20 hover:-translate-y-1 hover:shadow-2xl hover:shadow-[var(--color-primary)]/30 transition-all flex items-center justify-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span v-if="!processing">Confirmar Pedido</span>
                            <span v-else>Procesando...</span>
                            <svg v-if="!processing" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </button>
                    </div>

                    <div class="mt-8 text-center">
                        <p class="text-gray-400 text-[10px] font-medium leading-relaxed">
                            Al confirmar tu pedido, aceptas nuestros términos y condiciones y política de privacidad.
                        </p>
                    </div>
                </div>
            </form>
        </main>
        
        <!-- Public Footer -->
        <PublicFooter :empresa="empresa" />
    </div>
</template>
