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
        ciudad: 'HERMOSILLO',
        estado: 'SONORA',
        cp: props.cliente?.direccion_predeterminada?.cp || '',
    },
    tipo_entrega: 'domicilio',
    metodo_pago: 'efectivo',
    items: [],
})

const cpError = ref('')
const costoEnvio = ref(100)
const shippingDetails = { metodo: 'Envío estándar', costo: 100, tiempo: '2-4 días hábiles' }
const total = computed(() => subtotal.value + costoEnvio.value)

const validarCp = (cp) => {
    if (cp.length === 5 && !cp.startsWith('83')) {
        cpError.value = 'Solo enviamos a Hermosillo, Sonora (CP 83xxx)'
        costoEnvio.value = 0
    } else if (cp.length === 5 && cp.startsWith('83')) {
        cpError.value = ''
        costoEnvio.value = 100
    } else {
        cpError.value = ''
    }
}

const emailExistente = ref(false)
const enviandoPassword = ref(false)
const passwordEnviado = ref(false)
let emailTimeout = null

const verificarEmail = () => {
    clearTimeout(emailTimeout)
    if (!form.email || form.email.length < 5) { emailExistente.value = false; return }
    emailTimeout = setTimeout(async () => {
        try {
            const res = await axios.get('/api/check-email', { params: { email: form.email } })
            emailExistente.value = res.data.exists
        } catch (e) { emailExistente.value = false }
    }, 600)
}

const enviarPasswordTemporal = async () => {
    enviandoPassword.value = true
    try {
        await axios.post('/api/enviar-contrasena-temporal', { email: form.email })
        passwordEnviado.value = true
    } catch (e) {
        alert('Error al enviar la contraseña')
    } finally {
        enviandoPassword.value = false
    }
}

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

    // Google Maps Autocomplete
    const addressInput = ref(null)
    const initAutocomplete = () => {
        if (!window.google || !window.google.maps || !window.google.maps.places) return

        const autocomplete = new google.maps.places.Autocomplete(addressInput.value, {
            componentRestrictions: { country: "mx" },
            fields: ["address_components", "geometry"],
            types: ["address"],
        })

        autocomplete.addListener("place_changed", () => {
            const place = autocomplete.getPlace()
            if (!place.address_components) return

            // Reset campos
            form.direccion.calle = ''
            form.direccion.cp = ''
            form.direccion.colonia = ''
            form.direccion.ciudad = ''
            form.direccion.estado = ''

            let streetNumber = ''
            let route = ''

            for (const component of place.address_components) {
                const componentType = component.types[0]

                switch (componentType) {
                    case "street_number":
                        streetNumber = component.long_name
                        break
                    case "route":
                        route = component.long_name
                        break
                    case "sublocality_level_1":
                    case "neighborhood":
                        form.direccion.colonia = component.long_name.toUpperCase()
                        break
                    case "locality":
                        form.direccion.ciudad = component.long_name.toUpperCase()
                        break
                    case "administrative_area_level_1":
                        form.direccion.estado = component.long_name.toUpperCase()
                        break
                    case "postal_code":
                        form.direccion.cp = component.long_name
                        break
                }
            }

            form.direccion.calle = `${route} ${streetNumber}`.trim().toUpperCase()
            
            // Si no se encontró colonia en Google (pasa mucho en MX), intentar detonar la búsqueda por CP
            if (form.direccion.cp && !form.direccion.colonia) {
                // El watch de CP hará el resto
            }
        })
    }

    onMounted(() => {
        if (props.cliente) {
            form.nombre = props.cliente.nombre
            form.email = props.cliente.email
            form.telefono = props.cliente.telefono
        }
        
        // Facebook Pixel Event: InitiateCheckout
        if (window.fbq) {
            window.fbq('track', 'InitiateCheckout', {
                value: subtotal.value,
                currency: 'MXN',
                content_ids: items.value.map(i => i.producto_id),
                content_type: 'product',
                num_items: items.value.reduce((sum, i) => sum + i.cantidad, 0)
            });
        }

        // Envío estándar: $100
        if (form.tipo_entrega === 'domicilio') {
            if (form.direccion.cp?.length === 5) {
            } else {
                costoEnvio.value = 100
            }
        }

        // Inicializar Autocomplete
        setTimeout(initAutocomplete, 1000) // Pequeño delay para asegurar que el script cargó
    })
    // Variables para CP
    const coloniasDisponibles = ref([])
    const loadingCP = ref(false)

    // Lógica para CP
    watch(() => form.direccion.cp, async (newVal) => {
        validarCp(newVal || '')
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

    <div class="min-h-screen bg-[var(--ui-surface)] flex flex-col font-sans" :style="cssVars">
        <PublicNavbar :empresa="empresa" activeTab="tienda" />

        <main class="flex-grow w-full px-4 sm:px-6 lg:px-8 py-8 lg:py-12 w-full">
            <!-- (Header section unchanged) -->
             <div class="mb-10">
                <h1 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="w-10 h-10 rounded-xl bg-[var(--color-primary-soft)] flex items-center justify-center text-[var(--color-primary)]">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </span>
                    Finalizar Compra
                </h1>
                <p class="text-slate-500 dark:text-slate-400 font-medium ml-14 mt-1">Completa tus datos para recibir tu pedido.</p>
            </div>

            <form @submit.prevent="submitOrder" class="grid lg:grid-cols-3 gap-8">
                
                <!-- Columna Izquierda: Datos de Envío -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Sección: Datos Personales -->
                    <div class="bg-white dark:bg-slate-800 rounded-[2rem] p-8 shadow-sm border border-slate-100 dark:border-slate-700">
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                            <span class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500 dark:text-slate-200 text-sm">1</span>
                            Datos de Contacto
                        </h2>
                        
                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="space-y-1">
                                <label class="text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 ml-1">Nombre Completo</label>
                                <input :value="form.nombre" @input="toUpper($event, 'nombre')" type="text" class="w-full px-5 py-3 bg-white dark:bg-slate-700 border-slate-100 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] transition-all font-bold text-slate-800 dark:text-slate-200 placeholder-slate-300 dark:placeholder-slate-400" placeholder="Ej. JUAN PÉREZ" required>
                                <div v-if="form.errors.nombre" class="text-rose-500 dark:text-rose-400 text-[10px] font-bold mt-1 uppercase">{{ form.errors.nombre }}</div>
                            </div>

                             <div class="space-y-1">
                                <label class="text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 ml-1">Teléfono (10 dígitos)</label>
                                <input :value="form.telefono" @input="formatPhone" type="tel" maxlength="10" class="w-full px-5 py-3 bg-white dark:bg-slate-700 border-slate-100 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] transition-all font-bold text-slate-800 dark:text-slate-200 placeholder-slate-300 dark:placeholder-slate-400" placeholder="6621234567" required>
                                <div v-if="form.errors.telefono" class="text-rose-500 dark:text-rose-400 text-[10px] font-bold mt-1 uppercase">{{ form.errors.telefono }}</div>
                            </div>

                            <div class="space-y-1 md:col-span-2">
                                <label class="text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 ml-1">Correo Electrónico</label>
                                <input :value="form.email" @input="toLower($event, 'email'); verificarEmail()" type="email" class="w-full px-5 py-3 bg-white dark:bg-slate-700 border-slate-100 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] transition-all font-bold text-slate-800 dark:text-slate-200 placeholder-slate-300 dark:placeholder-slate-400" placeholder="juan@ejemplo.com" required>
                                <div v-if="form.errors.email" class="text-rose-500 dark:text-rose-400 text-[10px] font-bold mt-1 uppercase">{{ form.errors.email }}</div>
                                <div v-if="emailExistente && !passwordEnviado" class="mt-3 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-700">
                                    <p class="text-xs font-bold text-amber-800 dark:text-amber-300">👤 Este correo ya tiene una cuenta</p>
                                    <p class="text-[10px] text-amber-600 dark:text-amber-400 mt-1">Inicia sesión para agilizar tu pedido</p>
                                    <button @click="enviarPasswordTemporal" :disabled="enviandoPassword" class="mt-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-[10px] font-bold rounded-lg transition-colors disabled:opacity-50">
                                        {{ enviandoPassword ? 'Enviando...' : 'Enviar contraseña al correo' }}
                                    </button>
                                </div>
                                <div v-if="passwordEnviado" class="mt-3 p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border border-emerald-200 dark:border-emerald-700">
                                    <p class="text-xs font-bold text-emerald-800 dark:text-emerald-300">✅ Contraseña enviada a tu correo</p>
                                    <p class="text-[10px] text-emerald-600 dark:text-emerald-400 mt-1">Revisa tu bandeja de entrada para iniciar sesión</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección: Método de Entrega (Unchanged, included for context structure if needed or just skipped if replace works properly) -->
                    <!-- ... -->
                    <div class="bg-white dark:bg-slate-800 rounded-[2rem] p-8 shadow-sm border border-slate-100 dark:border-slate-700">
                        <!-- ... -->
                        <!-- Resumiendo el bloque Método de entrega para no sobrescribir cambios, me enfocaré en la Dirección -->
                        <!-- ... -->
                         <h2 class="text-2xl font-black text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                            <span class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500 dark:text-slate-200 text-sm">2</span>
                            Método de Entrega
                        </h2>
                        
                        <div class="grid md:grid-cols-2 gap-4">
                             <!-- Radio domicilio -->
                            <label class="cursor-pointer relative group">
                                <input type="radio" v-model="form.tipo_entrega" value="domicilio" class="peer sr-only">
                                <div class="p-6 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-700 peer-checked:bg-[var(--color-primary-soft)] peer-checked:border-[var(--color-primary)] transition-all flex flex-col gap-2">
                                    <div class="flex justify-between items-center">
                                        <span class="font-black text-slate-900 dark:text-white uppercase tracking-wider">A Domicilio</span>
                                        <svg class="w-10 h-10 text-[var(--color-primary)] opacity-0 peer-checked:opacity-100" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium leading-relaxed">Recibe tu pedido en la puerta de tu casa u oficina.</p>
                                    <span class="text-sm font-black text-[var(--color-primary)] mt-2">+ {{ formatCurrency(costoEnvio) }} (Envío Estándar)</span>
                                </div>
                            </label>

                         </div>
                    </div>


                    <!-- Sección: Dirección REFORMADA -->
                    <div v-if="form.tipo_entrega === 'domicilio'" class="bg-white dark:bg-slate-800 rounded-[2rem] p-8 shadow-sm border border-slate-100 dark:border-slate-700">
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                            <span class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500 dark:text-slate-200 text-sm">3</span>
                            Dirección de Envío
                        </h2>

                        <div class="space-y-6">
                            <!-- Fila 1: CP primero para detonar la API -->
                             <div class="grid grid-cols-2 gap-6">
                                <div class="space-y-1">
                                    <label class="text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 ml-1">Código Postal</label>
                                    <div class="relative">
                                        <input v-model="form.direccion.cp" type="text" maxlength="5" class="w-full px-5 py-3 bg-white dark:bg-slate-700 border-slate-100 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] transition-all font-bold text-slate-800 dark:text-slate-200 placeholder-slate-300 dark:placeholder-slate-400" placeholder="Ej. 83000" required>
                                        <div v-if="loadingCP" class="absolute right-3 top-3 text-slate-400 dark:text-slate-500 text-xs animate-spin">⌛</div>
                                    </div>
                                     <div v-if="cpError" class="p-3 bg-rose-50 dark:bg-rose-900/20 rounded-xl text-[10px] font-bold text-rose-600 dark:text-rose-400">
                                        ⚠️ {{ cpError }}
                                    </div>
                                     <div v-if="form.errors['direccion.cp']" class="text-rose-500 dark:text-rose-400 text-[10px] font-bold mt-1 uppercase">{{ form.errors['direccion.cp'] }}</div>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 ml-1">Estado</label>
                                    <input :value="form.direccion.estado" readonly type="text" class="w-full px-5 py-3 bg-slate-100 dark:bg-slate-700 border-slate-100 dark:border-slate-700 rounded-xl font-bold text-slate-500 dark:text-slate-400 cursor-not-allowed" placeholder="Se llena automático" required>
                                     <div v-if="form.errors['direccion.estado']" class="text-rose-500 dark:text-rose-400 text-[10px] font-bold mt-1 uppercase">{{ form.errors['direccion.estado'] }}</div>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-6">
                                <div class="space-y-1">
                                    <label class="text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 ml-1">Ciudad / Municipio</label>
                                    <input :value="form.direccion.ciudad" readonly type="text" class="w-full px-5 py-3 bg-slate-100 dark:bg-slate-700 border-slate-100 dark:border-slate-700 rounded-xl font-bold text-slate-500 dark:text-slate-400 cursor-not-allowed" placeholder="Se llena automático" required>
                                     <div v-if="form.errors['direccion.ciudad']" class="text-rose-500 dark:text-rose-400 text-[10px] font-bold mt-1 uppercase">{{ form.errors['direccion.ciudad'] }}</div>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 ml-1">Colonia</label>
                                    <!-- Select si hay colonias, input si no -->
                                    <select v-if="coloniasDisponibles.length > 0" v-model="form.direccion.colonia" class="w-full px-5 py-3 bg-white dark:bg-slate-700 border-slate-100 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] transition-all font-bold text-slate-800 dark:text-slate-200">
                                        <option value="" disabled>Selecciona una colonia</option>
                                        <option v-for="col in coloniasDisponibles" :key="col" :value="col">{{ col }}</option>
                                    </select>
                                    <input v-else :value="form.direccion.colonia" @input="toUpperNested($event, 'direccion', 'colonia')" type="text" class="w-full px-5 py-3 bg-white dark:bg-slate-700 border-slate-100 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] transition-all font-bold text-slate-800 dark:text-slate-200 placeholder-slate-300 dark:placeholder-slate-400" placeholder="Ej. CENTRO" required>
                                    
                                     <div v-if="form.errors['direccion.colonia']" class="text-rose-500 dark:text-rose-400 text-[10px] font-bold mt-1 uppercase">{{ form.errors['direccion.colonia'] }}</div>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 ml-1">Calle y Número</label>
                                <input ref="addressInput" :value="form.direccion.calle" @input="toUpperNested($event, 'direccion', 'calle')" type="text" class="w-full px-5 py-3 bg-white dark:bg-slate-700 border-slate-100 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] transition-all font-bold text-slate-800 dark:text-slate-200 placeholder-slate-300 dark:placeholder-slate-400" placeholder="AV. REFORMA 123" required>
                                <div v-if="form.errors['direccion.calle']" class="text-rose-500 dark:text-rose-400 text-[10px] font-bold mt-1 uppercase">{{ form.errors['direccion.calle'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Resumen y Pago -->
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-slate-800 rounded-[2rem] p-8 shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-700 sticky top-28">
                        <h3 class="text-lg font-black text-slate-900 dark:text-white mb-6 uppercase tracking-wider">Resumen del Pedido</h3>
                        
                        <!-- Lista de Items Compacta -->
                        <div class="space-y-6 mb-6 max-h-60 overflow-y-auto pr-2 custom-scrollbar">
                            <div v-for="item in items" :key="item.producto_id" class="flex items-center gap-2 py-2 border-b border-slate-50 dark:border-slate-700 last:border-0">
                                <div class="w-10 h-10 bg-white dark:bg-slate-700 rounded-xl flex-shrink-0 flex items-center justify-center">
                                     <span class="text-xs font-bold text-slate-400 dark:text-slate-200">x{{ item.cantidad }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ item.nombre }}</p>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ formatCurrency(item.precio) }}</p>
                                </div>
                                <div class="text-xs font-bold text-slate-900 dark:text-white">
                                    {{ formatCurrency(item.precio * item.cantidad) }}
                                </div>
                            </div>
                        </div>

                        <div class="h-px bg-slate-100 dark:bg-slate-700 my-4"></div>

                        <!-- Totales -->
                         <div class="space-y-3 mb-8">
                            <div class="flex justify-between text-slate-400 dark:text-slate-500 font-medium text-xs uppercase tracking-wide">
                                <span>Subtotal (sin IVA)</span>
                                <span class="font-bold text-slate-500 dark:text-slate-200">{{ formatCurrency(subtotalSinIva) }}</span>
                            </div>
                            <div class="flex justify-between text-slate-400 dark:text-slate-500 font-medium text-xs uppercase tracking-wide">
                                <span>IVA (16%)</span>
                                <span class="font-bold text-slate-500 dark:text-slate-200">{{ formatCurrency(iva) }}</span>
                            </div>
                            <div class="flex justify-between text-slate-500 dark:text-slate-400 font-medium text-sm pt-2 border-t border-slate-50 dark:border-slate-700 items-center">
                                <span>Costo de Envío</span>
                                <div class="flex flex-col items-end">
                                    <span class="font-bold text-slate-900 dark:text-white">
                                        {{ formatCurrency(costoEnvio) }}
                                    </span>
                                     <span class="text-[9px] text-slate-400 dark:text-slate-500 font-medium uppercase">
                                        {{ shippingDetails.metodo }}
                                    </span>
                                </div>
                            </div>
                            <div class="p-2 bg-sky-50 dark:bg-sky-900/20 rounded-xl text-[9px] font-bold text-sky-800 dark:text-sky-200">
                                🕒 {{ shippingDetails.tiempo }}
                            </div>
                            <div class="flex justify-between text-lg mt-4 pt-4 border-t-2 border-slate-100 dark:border-slate-700">
                                <span class="font-black text-slate-900 dark:text-white uppercase tracking-wider">Total a Pagar</span>
                                <span class="font-black text-2xl text-[var(--color-primary)]">
                                    {{ formatCurrency(total) }}
                                </span>
                            </div>
                        </div>

                        <!-- Método de Pago -->
                        <div class="mb-8">
                            <label class="text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500 block mb-3">Método de Pago</label>
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-700 text-center">
                                <p class="text-sm font-bold text-slate-700 dark:text-slate-200">💰 Pago contra entrega</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Efectivo o Transferencia al recibir tu pedido</p>
                            </div>
                            <input type="hidden" v-model="form.metodo_pago" value="efectivo" />
                        </div>

                        <!-- Botón de Acción -->
                        <button type="submit" 
                            :disabled="processing || items.length === 0"
                            class="w-full py-4 bg-[var(--color-primary)] text-white rounded-xl font-black text-sm uppercase tracking-wide shadow-xl shadow-[var(--color-primary)]/20 hover:shadow-xl hover:shadow-xl hover:shadow-2xl hover:shadow-[var(--color-primary)]/30 transition-all flex items-center justify-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span v-if="!processing">Confirmar Pedido</span>
                            <span v-else>Procesando...</span>
                            <svg v-if="!processing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </button>
                    </div>

                    <div class="mt-8 text-center">
                        <p class="text-slate-400 dark:text-slate-500 text-[10px] font-medium leading-relaxed">
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
