<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import { Notyf } from 'notyf'

const props = defineProps({
    show: Boolean,
    cfdi: Object
})

const emit = defineEmits(['close', 'created'])

const notyf = new Notyf({
    duration: 4000,
    position: { x: 'right', y: 'top' },
    types: [
        { type: 'success', background: '#10b981', icon: false },
        { type: 'error', background: '#ef4444', icon: false }
    ]
})

// Estado del modal
const isLoading = ref(false)
const isSaving = ref(false)
const datosCuenta = ref(null)
const error = ref(null)

// Formulario de nueva entidad (proveedor/cliente)
const showFormEntidad = ref(false)
const formEntidad = ref({
    rfc: '',
    nombre_razon_social: '',
    nombre: '',
    email: '',
    telefono: '',
    regimen_fiscal: '',
    codigo_postal: ''
})
const isCreatingEntidad = ref(false)

// Formulario de cuenta
const formCuenta = ref({
    fecha_vencimiento: '',
    notas: ''
})

// Watchers
watch(() => props.show, async (newVal) => {
    if (newVal && props.cfdi) {
        await prepararDatos()
    } else {
        reset()
    }
})

// Computed
const esCuentaPorPagar = computed(() => datosCuenta.value?.tipo_cuenta === 'pagar')
const tipoEntidad = computed(() => esCuentaPorPagar.value ? 'Proveedor' : 'Cliente')
const entidadEncontrada = computed(() => datosCuenta.value?.entidad?.encontrado)

// Métodos
const reset = () => {
    datosCuenta.value = null
    error.value = null
    showFormEntidad.value = false
    formEntidad.value = { 
        rfc: '', 
        nombre_razon_social: '', 
        nombre: '', 
        email: '', 
        telefono: '',
        regimen_fiscal: '',
        codigo_postal: ''
    }
    formCuenta.value = { fecha_vencimiento: '', notas: '' }
}

const prepararDatos = async () => {
    isLoading.value = true
    error.value = null
    
    try {
        const response = await axios.get(route('cfdi.preparar-cuenta', props.cfdi.id))
        if (response.data.success) {
            datosCuenta.value = response.data.data
            formCuenta.value.fecha_vencimiento = datosCuenta.value.fecha_vencimiento_sugerida
            
            // Prellenar formulario de entidad si no existe
            if (!datosCuenta.value.entidad.encontrado && datosCuenta.value.entidad.datos_sugeridos) {
                const sugeridos = datosCuenta.value.entidad.datos_sugeridos
                formEntidad.value.rfc = sugeridos.rfc || ''
                formEntidad.value.nombre_razon_social = sugeridos.nombre_razon_social || sugeridos.nombre || ''
                formEntidad.value.nombre = sugeridos.nombre || ''
                formEntidad.value.regimen_fiscal = sugeridos.regimen_fiscal || ''
                formEntidad.value.codigo_postal = sugeridos.codigo_postal || ''
            }
        } else {
            error.value = response.data.message || 'Error al preparar datos'
        }
    } catch (e) {
        error.value = e.response?.data?.message || 'Error al conectar con el servidor'
    } finally {
        isLoading.value = false
    }
}

const crearEntidad = async () => {
    isCreatingEntidad.value = true
    
    try {
        const url = esCuentaPorPagar.value 
            ? route('cfdi.crear-proveedor-desde-cfdi')
            : route('cfdi.crear-cliente-desde-cfdi')
        
        const data = esCuentaPorPagar.value 
            ? { 
                rfc: formEntidad.value.rfc, 
                nombre_razon_social: formEntidad.value.nombre_razon_social, 
                email: formEntidad.value.email, 
                telefono: formEntidad.value.telefono,
                regimen_fiscal: formEntidad.value.regimen_fiscal,
                codigo_postal: formEntidad.value.codigo_postal
            }
            : { 
                rfc: formEntidad.value.rfc, 
                nombre: formEntidad.value.nombre || formEntidad.value.nombre_razon_social, 
                email: formEntidad.value.email, 
                telefono: formEntidad.value.telefono,
                regimen_fiscal: formEntidad.value.regimen_fiscal,
                codigo_postal: formEntidad.value.codigo_postal
            }
        
        const response = await axios.post(url, data)
        
        if (response.data.success) {
            notyf.success(`${tipoEntidad.value} creado correctamente`)
            // Actualizar datos de cuenta con el nuevo proveedor/cliente
            datosCuenta.value.entidad.encontrado = true
            datosCuenta.value.entidad.datos = esCuentaPorPagar.value 
                ? response.data.proveedor 
                : response.data.cliente
            showFormEntidad.value = false
        } else {
            notyf.error(response.data.message || 'Error al crear')
        }
    } catch (e) {
        notyf.error(e.response?.data?.message || 'Error al crear entidad')
    } finally {
        isCreatingEntidad.value = false
    }
}

const crearCuenta = async () => {
    if (!datosCuenta.value?.entidad?.encontrado) {
        notyf.error(`Primero debes crear o seleccionar un ${tipoEntidad.value.toLowerCase()}`)
        return
    }
    
    isSaving.value = true
    
    try {
        const url = esCuentaPorPagar.value 
            ? route('cfdi.crear-cuenta-pagar', props.cfdi.id)
            : route('cfdi.crear-cuenta-cobrar', props.cfdi.id)
        
        const data = {
            [esCuentaPorPagar.value ? 'proveedor_id' : 'cliente_id']: datosCuenta.value.entidad.datos.id,
            fecha_vencimiento: formCuenta.value.fecha_vencimiento,
            notas: formCuenta.value.notas
        }
        
        const response = await axios.post(url, data)
        
        if (response.data.success) {
            notyf.success(response.data.message || 'Cuenta creada correctamente')
            emit('created', response.data.cuenta)
            emit('close')
        } else {
            notyf.error(response.data.message || 'Error al crear cuenta')
        }
    } catch (e) {
        notyf.error(e.response?.data?.message || 'Error al crear cuenta')
    } finally {
        isSaving.value = false
    }
}

const formatCurrency = (value) => {
    const num = parseFloat(value)
    if (isNaN(num)) return '$0.00'
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(num)
}
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="$emit('close')"></div>
            
            <!-- Modal -->
            <div class="relative bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-lg p-8 animate-fadeIn">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-xl font-black text-gray-900 dark:text-white tracking-tight italic">
                            {{ esCuentaPorPagar ? 'Crear Cuenta por Pagar' : 'Crear Cuenta por Cobrar' }}
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Desde CFDI {{ cfdi?.folio || cfdi?.uuid?.substr(0,8) }}</p>
                    </div>
                    <button @click="$emit('close')" class="p-2 hover:bg-gray-100 rounded-xl transition-colors">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <!-- Loading -->
                <div v-if="isLoading" class="py-12 text-center">
                    <div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
                    <p class="text-gray-500 dark:text-gray-400 font-bold">Analizando CFDI...</p>
                </div>
                
                <!-- Error -->
                <div v-else-if="error" class="py-8 text-center">
                    <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-red-600 font-bold mb-4">{{ error }}</p>
                    <button @click="prepararDatos" class="text-blue-600 hover:text-blue-700 font-bold">Reintentar</button>
                </div>
                
                <!-- Ya tiene cuenta -->
                <div v-else-if="datosCuenta?.tiene_cuenta" class="py-8 text-center">
                    <div class="w-16 h-16 bg-amber-50 text-amber-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <p class="text-gray-800 font-bold mb-2">Este CFDI ya tiene una cuenta vinculada</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Para evitar duplicados, no es posible crear otra cuenta.</p>
                </div>
                
                <!-- Contenido principal -->
                <div v-else-if="datosCuenta" class="space-y-6">
                    <!-- Info CFDI -->
                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl p-4 border border-gray-200 dark:border-slate-800">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total</span>
                                <p class="text-xl font-black text-emerald-600 tracking-tight">{{ formatCurrency(datosCuenta.cfdi?.total) }}</p>
                            </div>
                            <div>
                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Fecha Emisión</span>
                                <p class="text-sm font-bold text-gray-700">{{ datosCuenta.cfdi?.fecha_emision }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección Proveedor/Cliente -->
                    <div class="border border-gray-200 dark:border-slate-800 rounded-2xl p-4">
                        <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-3">{{ tipoEntidad }}</h3>
                        
                        <!-- Entidad encontrada -->
                        <div v-if="entidadEncontrada" class="flex items-center gap-3 bg-emerald-50 rounded-xl p-3 border border-emerald-200">
                            <div class="w-10 h-10 bg-emerald-500 text-white rounded-full flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-gray-800 truncate">{{ datosCuenta.entidad.datos?.nombre }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ datosCuenta.entidad.datos?.rfc }}</p>
                            </div>
                        </div>
                        
                        <!-- Entidad NO encontrada -->
                        <div v-else>
                            <div v-if="!showFormEntidad" class="bg-amber-50 rounded-xl p-4 border border-amber-200">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 bg-amber-500 text-white rounded-full flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-bold text-gray-800">{{ tipoEntidad }} no encontrado</p>
                                        <p class="text-xs text-gray-600 mt-1">
                                            RFC: <span class="font-mono font-bold">{{ datosCuenta.entidad.datos_sugeridos?.rfc || 'N/A' }}</span>
                                        </p>
                                        <button @click="showFormEntidad = true" 
                                                class="mt-3 text-xs font-black text-amber-700 hover:text-amber-800 uppercase tracking-widest">
                                            + Crear {{ tipoEntidad }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Formulario inline para crear entidad -->
                            <div v-else class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">RFC *</label>
                                    <input v-model="formEntidad.rfc" type="text" 
                                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 dark:border-slate-800 rounded-xl font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           placeholder="XAXX010101000">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">
                                        {{ esCuentaPorPagar ? 'Razón Social *' : 'Nombre *' }}
                                    </label>
                                    <input v-if="esCuentaPorPagar" v-model="formEntidad.nombre_razon_social" 
                                           type="text" 
                                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 dark:border-slate-800 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <input v-else v-model="formEntidad.nombre" 
                                           type="text" 
                                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 dark:border-slate-800 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-600 mb-1">Régimen Fiscal</label>
                                        <input v-model="formEntidad.regimen_fiscal" type="text" 
                                               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 dark:border-slate-800 rounded-xl font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               placeholder="601">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-600 mb-1">C.P.</label>
                                        <input v-model="formEntidad.codigo_postal" type="text" 
                                               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 dark:border-slate-800 rounded-xl font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               placeholder="83000">
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button @click="showFormEntidad = false" 
                                            class="flex-1 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl font-bold text-xs transition-colors">
                                        Cancelar
                                    </button>
                                    <button @click="crearEntidad" :disabled="isCreatingEntidad"
                                            class="flex-1 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-xs transition-colors disabled:opacity-50">
                                        {{ isCreatingEntidad ? 'Creando...' : `Crear ${tipoEntidad}` }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Formulario de cuenta -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Fecha de Vencimiento</label>
                            <input v-model="formCuenta.fecha_vencimiento" type="date" 
                                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 dark:border-slate-800 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Notas (opcional)</label>
                            <textarea v-model="formCuenta.notas" rows="2"
                                      class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 dark:border-slate-800 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                                      placeholder="Notas adicionales..."></textarea>
                        </div>
                    </div>
                    
                    <!-- Botones -->
                    <div class="flex gap-3 pt-4">
                        <button @click="$emit('close')" 
                                class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl font-black text-xs uppercase tracking-widest transition-all">
                            Cancelar
                        </button>
                        <button @click="crearCuenta" :disabled="isSaving || !entidadEncontrada"
                                class="flex-1 px-4 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white rounded-xl font-black text-xs uppercase tracking-widest transition-all shadow-lg shadow-emerald-500/25 disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ isSaving ? 'Creando...' : 'Crear Cuenta' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}

.animate-fadeIn {
    animation: fadeIn 0.3s ease-out forwards;
}
</style>
